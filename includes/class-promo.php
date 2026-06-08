<?php
/**
 * Workstream G — site self-promotion graphics.
 *
 * A small studio for the site owner / marketer: turn real, privacy-safe platform
 * positives into an on-brand square social graphic plus a ready-to-paste caption,
 * one at a time. Manual posting now; an automated drip can follow later via the
 * syndication workstream (#7).
 *
 * PRIVACY (non-negotiable): only aggregate, public data is ever used. Participant
 * needs are never counted or named, and no individual person or contact detail ever
 * appears. The curated messages come from the same single source as the daily
 * feature spotlight, so brand wording stays consistent.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Promo {

	const STATS_TRANSIENT = 'sssj_promo_stats';

	/** Capability required to use the self-promo studio (it posts for the whole site). */
	public static function cap() {
		return (string) apply_filters( 'shuffles_ssj_promo_cap', 'manage_options' );
	}

	/** May the current user open the studio? */
	public static function can_use() {
		return is_user_logged_in() && current_user_can( self::cap() );
	}

	/** A counter must reach this before we will brag about it (numbers only when they impress). */
	public static function min_to_show() {
		return max( 1, (int) apply_filters( 'shuffles_ssj_promo_min', 5 ) );
	}

	/** Site display name, used in graphics + captions (no third-party brand names anywhere). */
	public static function site_name() {
		$name = trim( (string) get_bloginfo( 'name' ) );
		return '' !== $name ? $name : __( 'our marketplace', 'shuffles-social-services-jobs' );
	}

	/** Bare host for the graphic footer, e.g. justtasks.com.au. */
	public static function site_host() {
		$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
		return preg_replace( '/^www\./', '', $host );
	}

	/**
	 * Live, privacy-safe platform numbers (short-cached). Participant needs are never counted.
	 *
	 * @return array<string,int>
	 */
	public static function stats() {
		$cached = get_transient( self::STATS_TRANSIENT );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		global $wpdb;

		$jobs = (int) ( wp_count_posts( 'sssj_job' )->publish ?? 0 );

		$basis_count = function ( $basis ) {
			$q = new WP_Query( array(
				'post_type'      => 'sssj_job',
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'no_found_rows'  => false,
				'meta_query'     => array( array( 'key' => 'engagement_basis', 'value' => $basis, 'compare' => '=' ) ),
			) );
			return (int) $q->found_posts;
		};

		$workers = (int) ( new WP_Query( array(
			'post_type'      => 'sssj_worker',
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => 1,
			'no_found_rows'  => false,
			'meta_query'     => array(
				'relation' => 'AND',
				array( 'key' => 'is_available', 'value' => '1' ),
				array( 'key' => 'visibility', 'value' => array( 'public', 'logged_in' ), 'compare' => 'IN' ),
			),
		) ) )->found_posts;

		$verified = (int) ( new WP_Query( array(
			'post_type'      => 'sssj_worker',
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => 1,
			'no_found_rows'  => false,
			'meta_query'     => array(
				'relation' => 'AND',
				array( 'key' => 'verified_at', 'compare' => 'EXISTS' ),
				array( 'key' => 'verified_at', 'value' => '', 'compare' => '!=' ),
				array( 'key' => 'visibility', 'value' => array( 'public', 'logged_in' ), 'compare' => 'IN' ),
			),
		) ) )->found_posts;

		$orgs = (int) ( new WP_Query( array(
			'post_type'      => 'sssj_org',
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => 1,
			'no_found_rows'  => false,
			'meta_query'     => array(
				'relation' => 'OR',
				array( 'key' => 'org_hidden', 'compare' => 'NOT EXISTS' ),
				array( 'key' => 'org_hidden', 'value' => '1', 'compare' => '!=' ),
			),
		) ) )->found_posts;

		$placed = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sssj_application WHERE status = 'offer'" ); // phpcs:ignore WordPress.DB

		$since    = gmdate( 'Y-m-d H:i:s', time() - 7 * DAY_IN_SECONDS );
		$new_jobs = (int) ( new WP_Query( array(
			'post_type'      => 'sssj_job',
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => 1,
			'no_found_rows'  => false,
			'date_query'     => array( array( 'after' => $since, 'inclusive' => true ) ),
		) ) )->found_posts;

		// Distinct states covered by published jobs (a "national reach" positive).
		$states = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT pm.meta_value) FROM {$wpdb->postmeta} pm
			 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			 WHERE pm.meta_key = %s AND pm.meta_value <> '' AND p.post_type = %s AND p.post_status = %s",
			'location_state', 'sssj_job', 'publish'
		) ); // phpcs:ignore WordPress.DB

		$stats = array(
			'jobs'     => $jobs,
			'tfn'      => $basis_count( 'tfn' ),
			'abn'      => $basis_count( 'abn' ),
			'vol'      => $basis_count( 'vol' ),
			'workers'  => $workers,
			'verified' => $verified,
			'orgs'     => $orgs,
			'placed'   => $placed,
			'new_jobs' => $new_jobs,
			'states'   => $states,
		);

		set_transient( self::STATS_TRANSIENT, $stats, 10 * MINUTE_IN_SECONDS );
		return $stats;
	}

	/**
	 * The promotable positives: stat milestones that clear the "impress" threshold, followed by the
	 * curated brand messages (drawn from the feature-spotlight single source). Each entry:
	 *   key, kind ('stat'|'message'), eyebrow, big (number string|''), headline, sub, emoji, accent (0-3), caption.
	 *
	 * @return array[]
	 */
	public static function positives() {
		$s     = self::stats();
		$min   = self::min_to_show();
		$site  = self::site_name();
		$url   = home_url( '/' );
		$out   = array();
		$tags  = '#NDIS #AgedCare #DisabilitySupport #SocialServices #SupportWork';

		// --- Stat milestones (only the ones worth shouting about) ---
		$stat_cards = array(
			'jobs'     => array( '💼', __( 'Open right now', 'shuffles-social-services-jobs' ), __( 'jobs on the boards', 'shuffles-social-services-jobs' ), $min ),
			'workers'  => array( '🧑‍⚕️', __( 'Ready to work', 'shuffles-social-services-jobs' ), __( 'available support workers', 'shuffles-social-services-jobs' ), $min ),
			'verified' => array( '✓', __( 'Checked by our team', 'shuffles-social-services-jobs' ), __( 'verified workers', 'shuffles-social-services-jobs' ), 1 ),
			'orgs'     => array( '🏢', __( 'On the platform', 'shuffles-social-services-jobs' ), __( 'providers and organisations', 'shuffles-social-services-jobs' ), $min ),
			'placed'   => array( '🤝', __( 'Connections made', 'shuffles-social-services-jobs' ), __( 'people matched to work', 'shuffles-social-services-jobs' ), 1 ),
			'new_jobs' => array( '✨', __( 'Fresh this week', 'shuffles-social-services-jobs' ), __( 'new jobs in the last 7 days', 'shuffles-social-services-jobs' ), 1 ),
			'states'   => array( '📍', __( 'National reach', 'shuffles-social-services-jobs' ), __( 'states and territories covered', 'shuffles-social-services-jobs' ), 2 ),
		);
		$accent = 0;
		foreach ( $stat_cards as $key => $meta ) {
			$val = isset( $s[ $key ] ) ? (int) $s[ $key ] : 0;
			if ( $val < (int) $meta[3] ) {
				continue; // not impressive enough yet — hide it
			}
			$num     = number_format_i18n( $val );
			$caption = sprintf(
				/* translators: 1: number, 2: label, 3: site name, 4: url, 5: hashtags */
				__( '%1$s %2$s on %3$s right now. %4$s %5$s', 'shuffles-social-services-jobs' ),
				$num,
				$meta[2],
				$site,
				$url,
				$tags
			);
			$out[] = array(
				'key'      => 'stat_' . $key,
				'kind'     => 'stat',
				'eyebrow'  => $meta[1],
				'big'      => $num,
				'headline' => '',
				'sub'      => $meta[2],
				'emoji'    => $meta[0],
				'accent'   => $accent % 4,
				'caption'  => $caption,
				'label'    => sprintf( '%s — %s', $num, $meta[2] ),
			);
			$accent++;
		}

		// --- Curated brand messages (same single source as the daily spotlight) ---
		if ( class_exists( 'Shuffles_SSJ_Spotlight' ) && method_exists( 'Shuffles_SSJ_Spotlight', 'features' ) ) {
			$features = (array) Shuffles_SSJ_Spotlight::features();
			$i        = 0;
			foreach ( $features as $f ) {
				$title = isset( $f['title'] ) ? (string) $f['title'] : '';
				$text  = isset( $f['text'] ) ? (string) $f['text'] : '';
				if ( '' === $title ) {
					continue;
				}
				$caption = sprintf(
					/* translators: 1: headline, 2: text, 3: site name, 4: url, 5: hashtags */
					__( '%1$s — %2$s See how %3$s works: %4$s %5$s', 'shuffles-social-services-jobs' ),
					$title,
					$text,
					$site,
					$url,
					$tags
				);
				$out[] = array(
					'key'      => 'msg_' . $i,
					'kind'     => 'message',
					'eyebrow'  => sprintf( __( 'Why members choose %s', 'shuffles-social-services-jobs' ), $site ),
					'big'      => '',
					'headline' => $title,
					'sub'      => $text,
					'emoji'    => '🛡️',
					'accent'   => $i % 4,
					'caption'  => $caption,
					'label'    => $title,
				);
				$i++;
			}
		}

		return $out;
	}

	/** Deterministic "one positive a day" index (rotates daily, like the spotlight). */
	public static function today_index( $count ) {
		$count = max( 1, (int) $count );
		return (int) ( current_time( 'z' ) % $count );
	}

	/**
	 * The swappable inner body of a promo card (goes inside [data-promo-body]). The brand frame
	 * (logo, site name, footer) lives in the template and stays constant.
	 *
	 * @param array $p A positive from positives().
	 * @return string Escaped HTML.
	 */
	public static function body_html( $p ) {
		$emoji   = isset( $p['emoji'] ) ? (string) $p['emoji'] : '';
		$eyebrow = isset( $p['eyebrow'] ) ? (string) $p['eyebrow'] : '';
		$html    = '<span class="sssj-promo__emoji" aria-hidden="true">' . esc_html( $emoji ) . '</span>';
		if ( '' !== $eyebrow ) {
			$html .= '<span class="sssj-promo__eyebrow">' . esc_html( $eyebrow ) . '</span>';
		}
		if ( 'stat' === ( isset( $p['kind'] ) ? $p['kind'] : '' ) && '' !== (string) $p['big'] ) {
			$html .= '<span class="sssj-promo__big">' . esc_html( $p['big'] ) . '</span>';
			$html .= '<span class="sssj-promo__sub">' . esc_html( isset( $p['sub'] ) ? $p['sub'] : '' ) . '</span>';
		} else {
			$html .= '<span class="sssj-promo__headline">' . esc_html( isset( $p['headline'] ) ? $p['headline'] : '' ) . '</span>';
			$html .= '<span class="sssj-promo__msg">' . esc_html( isset( $p['sub'] ) ? $p['sub'] : '' ) . '</span>';
		}
		return $html;
	}
}
