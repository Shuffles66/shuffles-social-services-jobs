<?php
/**
 * Real-time matching engine (Phase 3). Scores workers against a job and jobs against a worker on
 * shared services, location proximity, availability, engagement basis (ABN/TFN), rate and trust.
 * No stored scores yet (that is Phase 8), candidates are gathered with a shared-category WP_Query
 * then ranked in PHP. Renders "Best matches" panels on single job/worker pages + a [sssj_matches]
 * shortcode for a logged-in worker's dashboard.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Matcher {

	const CANDIDATES = 40; // pool size to score per request
	const SHOW       = 4;  // matches shown in a panel

	/* --------------------------------------------------------------- Workers for a job */

	/**
	 * Rank workers suited to a job. Returns [ { id, score, reasons[] }, ... ] best first.
	 */
	public static function workers_for_job( $job_id, $limit = self::SHOW, $since = '' ) {
		$cats = wp_get_post_terms( $job_id, 'sssjt_category', array( 'fields' => 'ids' ) );
		if ( is_wp_error( $cats ) || ! $cats ) {
			return array();
		}
		$ctx = array(
			'cats'     => $cats,
			'lat'      => (float) get_post_meta( $job_id, 'location_lat', true ),
			'lng'      => (float) get_post_meta( $job_id, 'location_lng', true ),
			'basis'    => (string) get_post_meta( $job_id, 'engagement_basis', true ),
			'rate_max' => (float) get_post_meta( $job_id, 'rate_max', true ),
		);
		// Alerts pass a visibility set + $since; on-page use respects the viewer's login state.
		$visible = is_user_logged_in() ? array( 'public', 'logged_in' ) : array( 'public' );
		if ( $since ) {
			$visible = array( 'public', 'logged_in' ); // alert context: consider all visible-to-members
		}
		$args = array(
			'post_type'      => 'sssj_worker',
			'post_status'    => 'publish',
			'posts_per_page' => self::CANDIDATES,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'tax_query'      => array( array( 'taxonomy' => 'sssjt_category', 'field' => 'term_id', 'terms' => $cats ) ),
			'meta_query'     => array( array( 'key' => 'visibility', 'value' => $visible, 'compare' => 'IN' ) ),
		);
		if ( $since ) {
			$args['date_query'] = array( array( 'after' => $since, 'inclusive' => false ) );
		}
		$ids = ( new WP_Query( $args ) )->posts;

		$scored = array();
		foreach ( $ids as $wid ) {
			$m = self::score_worker( (int) $wid, $ctx );
			if ( $m ) {
				$scored[] = $m;
			}
		}
		return self::top( $scored, $limit );
	}

	private static function score_worker( $wid, $ctx ) {
		$wcats  = wp_get_post_terms( $wid, 'sssjt_category', array( 'fields' => 'ids' ) );
		$shared = is_wp_error( $wcats ) ? array() : array_intersect( (array) $ctx['cats'], $wcats );
		$n      = count( $shared );
		if ( $n < 1 ) {
			return null;
		}
		$score   = $n * 30;
		$reasons = array( sprintf( _n( '%d shared service', '%d shared services', $n, 'shuffles-social-services-jobs' ), $n ) );

		$wlat = (float) get_post_meta( $wid, 'location_lat', true );
		$wlng = (float) get_post_meta( $wid, 'location_lng', true );
		if ( $ctx['lat'] && $ctx['lng'] && $wlat && $wlng && class_exists( 'Shuffles_SSJ_Geo' ) ) {
			$d = Shuffles_SSJ_Geo::distance_km( $ctx['lat'], $ctx['lng'], $wlat, $wlng );
			if ( null !== $d ) {
				if ( $d <= 10 ) { $score += 25; } elseif ( $d <= 25 ) { $score += 18; } elseif ( $d <= 50 ) { $score += 10; } elseif ( $d <= 100 ) { $score += 4; }
				$reasons[] = sprintf( __( '~%d km away', 'shuffles-social-services-jobs' ), max( 1, (int) round( $d ) ) );
			}
		}
		if ( '1' === (string) get_post_meta( $wid, 'is_available', true ) ) {
			$score    += 10;
			$reasons[] = __( 'Available now', 'shuffles-social-services-jobs' );
		}
		if ( 'abn' === $ctx['basis'] ) {
			if ( '' !== (string) get_post_meta( $wid, 'worker_abn', true ) ) {
				$score    += 12;
				$reasons[] = __( 'Has an ABN', 'shuffles-social-services-jobs' );
			} else {
				$score -= 20; // ABN role but no ABN on file
			}
		}
		$wrate = (float) get_post_meta( $wid, 'rate_min', true );
		if ( $ctx['rate_max'] > 0 && $wrate > 0 && $wrate <= $ctx['rate_max'] ) {
			$score += 8;
		}
		if ( get_post_meta( $wid, 'verified_at', true ) ) { $score += 6; }
		if ( '1' === (string) get_post_meta( $wid, 'sssj_blue_tick', true ) ) { $score += 6; }
		// Trust: approved member rating (up to +8, scaled 1–5 stars; needs at least one review).
		$ravg = (float) get_post_meta( $wid, '_sssj_rating_avg', true );
		$rcnt = (int) get_post_meta( $wid, '_sssj_rating_count', true );
		if ( $rcnt > 0 && $ravg > 0 ) {
			$score += (int) round( ( $ravg / 5 ) * 8 );
			$reasons[] = sprintf( __( 'Rated %s★', 'shuffles-social-services-jobs' ), number_format( $ravg, 1 ) );
		}

		return array( 'id' => (int) $wid, 'score' => $score, 'reasons' => array_slice( $reasons, 0, 3 ) );
	}

	/* --------------------------------------------------------------- Jobs for a worker */

	public static function jobs_for_worker( $worker_id, $limit = self::SHOW, $since = '' ) {
		$cats = wp_get_post_terms( $worker_id, 'sssjt_category', array( 'fields' => 'ids' ) );
		if ( is_wp_error( $cats ) || ! $cats ) {
			return array();
		}
		$ctx = array(
			'cats'     => $cats,
			'lat'      => (float) get_post_meta( $worker_id, 'location_lat', true ),
			'lng'      => (float) get_post_meta( $worker_id, 'location_lng', true ),
			'has_abn'  => '' !== (string) get_post_meta( $worker_id, 'worker_abn', true ),
			'rate_min' => (float) get_post_meta( $worker_id, 'rate_min', true ),
		);
		$args = array(
			'post_type'      => 'sssj_job',
			'post_status'    => 'publish',
			'posts_per_page' => self::CANDIDATES,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'tax_query'      => array( array( 'taxonomy' => 'sssjt_category', 'field' => 'term_id', 'terms' => $cats ) ),
		);
		if ( $since ) {
			$args['date_query'] = array( array( 'after' => $since, 'inclusive' => false ) );
		}
		$ids = ( new WP_Query( $args ) )->posts;

		$scored = array();
		foreach ( $ids as $jid ) {
			$m = self::score_job( (int) $jid, $ctx );
			if ( $m ) {
				$scored[] = $m;
			}
		}
		return self::top( $scored, $limit );
	}

	private static function score_job( $jid, $ctx ) {
		$jcats  = wp_get_post_terms( $jid, 'sssjt_category', array( 'fields' => 'ids' ) );
		$shared = is_wp_error( $jcats ) ? array() : array_intersect( (array) $ctx['cats'], $jcats );
		$n      = count( $shared );
		if ( $n < 1 ) {
			return null;
		}
		$score   = $n * 30;
		$reasons = array( sprintf( _n( '%d shared service', '%d shared services', $n, 'shuffles-social-services-jobs' ), $n ) );

		$basis = (string) get_post_meta( $jid, 'engagement_basis', true );
		if ( 'abn' === $basis && ! $ctx['has_abn'] ) {
			$score -= 20; // ABN role, worker has no ABN recorded
		} elseif ( 'abn' === $basis && $ctx['has_abn'] ) {
			$score += 8;
		}

		$jlat = (float) get_post_meta( $jid, 'location_lat', true );
		$jlng = (float) get_post_meta( $jid, 'location_lng', true );
		if ( $ctx['lat'] && $ctx['lng'] && $jlat && $jlng && class_exists( 'Shuffles_SSJ_Geo' ) ) {
			$d = Shuffles_SSJ_Geo::distance_km( $ctx['lat'], $ctx['lng'], $jlat, $jlng );
			if ( null !== $d ) {
				if ( $d <= 10 ) { $score += 25; } elseif ( $d <= 25 ) { $score += 18; } elseif ( $d <= 50 ) { $score += 10; } elseif ( $d <= 100 ) { $score += 4; }
				$reasons[] = sprintf( __( '~%d km away', 'shuffles-social-services-jobs' ), max( 1, (int) round( $d ) ) );
			}
		}
		$jrate_max = (float) get_post_meta( $jid, 'rate_max', true );
		if ( $ctx['rate_min'] > 0 && $jrate_max > 0 && $ctx['rate_min'] <= $jrate_max ) {
			$score += 8;
		}
		if ( get_post_meta( $jid, 'is_promoted', true ) ) { $score += 5; }

		$reasons[] = ( 'tfn' === $basis ) ? __( 'Employee (TFN) role', 'shuffles-social-services-jobs' ) : __( 'Contractor (ABN) role', 'shuffles-social-services-jobs' );
		return array( 'id' => (int) $jid, 'score' => $score, 'reasons' => array_slice( $reasons, 0, 3 ) );
	}

	/* --------------------------------------------------------------- Helpers */

	private static function top( $scored, $limit ) {
		$scored = array_values( array_filter( $scored, function ( $m ) { return $m['score'] > 0; } ) );
		usort( $scored, function ( $a, $b ) { return $b['score'] <=> $a['score']; } );
		return array_slice( $scored, 0, max( 1, (int) $limit ) );
	}

	/* --------------------------------------------------------------- Rendering */

	/** Panel of matched workers (for a job page). */
	public static function render_worker_matches( $job_id, $title = '' ) {
		$matches = self::workers_for_job( $job_id );
		if ( ! $matches ) {
			return '';
		}
		$title = $title ? $title : __( 'Workers who may suit this role', 'shuffles-social-services-jobs' );
		ob_start();
		echo '<div class="sssj sssj--matches"><div class="sssj-panel"><h3 style="margin-top:0">✨ ' . esc_html( $title ) . '</h3><div class="sssj-grid">';
		foreach ( $matches as $m ) {
			$id    = $m['id'];
			$photo = get_the_post_thumbnail_url( $id, 'thumbnail' );
			$svcs  = wp_get_post_terms( $id, 'sssjt_category', array( 'fields' => 'names' ) );
			echo '<article class="sssj-card sssj-match">';
			echo '<div class="sssj-row" style="gap:10px;flex-wrap:nowrap;align-items:flex-start">';
			if ( $photo ) { echo '<img class="sssj-worker-photo sssj-worker-photo--sm" src="' . esc_url( $photo ) . '" alt="" />'; }
			echo '<h4 style="margin:0"><a href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html( get_the_title( $id ) ) . '</a> ' . ( class_exists( 'Shuffles_SSJ_Verification' ) ? Shuffles_SSJ_Verification::tick_html( $id, false ) : '' ) . '</h4>';
			echo '</div>';
			if ( ! is_wp_error( $svcs ) && $svcs ) { echo '<p class="description" style="margin:6px 0">' . esc_html( implode( ', ', array_slice( $svcs, 0, 3 ) ) ) . '</p>'; }
			echo '<p class="sssj-match__why">' . esc_html( implode( ' · ', $m['reasons'] ) ) . '</p>';
			echo '<a class="sssj-btn sssj-btn--secondary sssj-btn--sm" href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html__( 'View profile', 'shuffles-social-services-jobs' ) . '</a>';
			echo '</article>';
		}
		echo '</div></div></div>';
		return ob_get_clean();
	}

	/** Panel of matched jobs (for a worker page, or a worker's dashboard). */
	public static function render_job_matches( $worker_id, $title = '' ) {
		$matches = self::jobs_for_worker( $worker_id );
		if ( ! $matches ) {
			return '';
		}
		$title = $title ? $title : __( 'Open roles this worker may suit', 'shuffles-social-services-jobs' );
		ob_start();
		echo '<div class="sssj sssj--matches"><div class="sssj-panel"><h3 style="margin-top:0">✨ ' . esc_html( $title ) . '</h3><div class="sssj-grid">';
		foreach ( $matches as $m ) {
			$id    = $m['id'];
			$basis = (string) get_post_meta( $id, 'engagement_basis', true );
			$sub   = (string) get_post_meta( $id, 'location_suburb', true );
			$state = (string) get_post_meta( $id, 'location_state', true );
			$logo  = class_exists( 'Shuffles_SSJ_Org' ) ? Shuffles_SSJ_Org::job_logo_url( $id, 'thumbnail' ) : '';
			$mod   = 'tfn' === $basis ? 'sssj-card--tfn' : ( 'abn' === $basis ? 'sssj-card--abn' : '' );
			echo '<article class="sssj-card sssj-match ' . esc_attr( $mod ) . '">';
			echo '<div class="sssj-row" style="gap:10px;flex-wrap:nowrap;align-items:flex-start">';
			if ( $logo ) { echo '<img class="sssj-org-logo" src="' . esc_url( $logo ) . '" alt="" />'; }
			echo '<h4 style="margin:0"><a href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html( get_the_title( $id ) ) . '</a></h4>';
			echo '</div>';
			if ( $sub || $state ) { echo '<p class="description" style="margin:6px 0">📍 ' . esc_html( trim( $sub . ' ' . $state ) ) . '</p>'; }
			echo '<p class="sssj-match__why">' . esc_html( implode( ' · ', $m['reasons'] ) ) . '</p>';
			echo '<a class="sssj-btn sssj-btn--secondary sssj-btn--sm" href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html__( 'View job', 'shuffles-social-services-jobs' ) . '</a>';
			echo '</article>';
		}
		echo '</div></div></div>';
		return ob_get_clean();
	}
}
