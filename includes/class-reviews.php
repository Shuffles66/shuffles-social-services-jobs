<?php
/**
 * Reviews & ratings, star ratings + written reviews for CONTRACTORS (worker profiles) and
 * PROVIDERS (organisations).
 *
 * Trust by design:
 *  - You may only review someone you have ACTUALLY ENGAGED with, proven by an existing message
 *    thread between you (all first contact goes through the relay, and applying starts a thread).
 *  - Reviews are PRE-MODERATED: they are held as `pending` and only show once an admin approves.
 *  - You cannot review yourself, and you get one (editable) review per subject.
 *  - The reviewed party may post one public Response per review.
 *  - The approved average feeds the matcher's "trust" signal and shows on the profile.
 *
 * Storage: custom table {prefix}sssj_review (see Shuffles_SSJ_Activator).
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Reviews {

	const RATING_AVG_META   = '_sssj_rating_avg';
	const RATING_COUNT_META = '_sssj_rating_count';

	/** Custom table name. */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'sssj_review';
	}

	/** Are reviews switched on? (Settings → Privacy & Moderation.) */
	public static function enabled() {
		$opts = get_option( 'shuffles_ssj_settings', array() );
		// Default ON unless explicitly disabled.
		return ! ( is_array( $opts ) && isset( $opts['reviews_enabled'] ) && ! $opts['reviews_enabled'] );
	}

	/** Subject types we accept. */
	public static function types() {
		return array( 'worker', 'org' );
	}

	/** Human label for a subject type. */
	public static function type_label( $type ) {
		return ( 'org' === $type ) ? __( 'provider', 'shuffles-social-services-jobs' ) : __( 'contractor', 'shuffles-social-services-jobs' );
	}

	/**
	 * The user account(s) that "are" this subject (so you can never review yourself, and so we can
	 * test for engagement). Worker → its owning user; Org → owner + team members.
	 *
	 * @return int[]
	 */
	public static function subject_user_ids( $type, $subject_id ) {
		$subject_id = (int) $subject_id;
		$ids        = array();
		if ( 'worker' === $type ) {
			$uid = (int) get_post_meta( $subject_id, 'worker_user_id', true );
			if ( $uid ) {
				$ids[] = $uid;
			}
		} elseif ( 'org' === $type && class_exists( 'Shuffles_SSJ_Org_Team' ) ) {
			$owner = (int) Shuffles_SSJ_Org_Team::owner_id( $subject_id );
			if ( $owner ) {
				$ids[] = $owner;
			}
			foreach ( array_keys( Shuffles_SSJ_Org_Team::members_raw( $subject_id ) ) as $mid ) {
				$ids[] = (int) $mid;
			}
		}
		return array_values( array_unique( array_filter( $ids ) ) );
	}

	/**
	 * Has the reviewer genuinely engaged with the subject? True if a relay message exists in either
	 * direction between the reviewer and any of the subject's user accounts.
	 */
	public static function engagement_exists( $reviewer_id, $subject_user_ids ) {
		global $wpdb;
		$reviewer_id      = (int) $reviewer_id;
		$subject_user_ids = array_values( array_filter( array_map( 'intval', (array) $subject_user_ids ) ) );
		if ( ! $reviewer_id || ! $subject_user_ids ) {
			return false;
		}
		$mt          = class_exists( 'Shuffles_SSJ_Messaging' ) ? Shuffles_SSJ_Messaging::table() : ( $wpdb->prefix . 'sssj_message' );
		$placeholder = implode( ',', array_fill( 0, count( $subject_user_ids ), '%d' ) );
		// (reviewer -> subject) OR (subject -> reviewer)
		$args = array_merge( array( $reviewer_id ), $subject_user_ids, $subject_user_ids, array( $reviewer_id ) );
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$mt} WHERE ( from_user_id = %d AND to_user_id IN ($placeholder) ) OR ( from_user_id IN ($placeholder) AND to_user_id = %d )",
			$args
		);
		// phpcs:enable
		return (int) $wpdb->get_var( $sql ) > 0; // phpcs:ignore WordPress.DB
	}

	/**
	 * May $reviewer_id review this subject? Returns true, or a string reason code when not:
	 *   'login' | 'self' | 'no-engagement' | 'bad-subject' | 'off'.
	 */
	public static function review_block( $reviewer_id, $type, $subject_id ) {
		if ( ! self::enabled() ) {
			return 'off';
		}
		$reviewer_id = (int) $reviewer_id;
		if ( ! $reviewer_id ) {
			return 'login';
		}
		if ( ! in_array( $type, self::types(), true ) ) {
			return 'bad-subject';
		}
		$subject_users = self::subject_user_ids( $type, $subject_id );
		if ( ! $subject_users ) {
			return 'bad-subject';
		}
		if ( in_array( $reviewer_id, $subject_users, true ) ) {
			return 'self';
		}
		// Site admins may always leave/seed a review (useful for moderation + demos).
		if ( user_can( $reviewer_id, 'manage_options' ) ) {
			return true;
		}
		if ( ! self::engagement_exists( $reviewer_id, $subject_users ) ) {
			return 'no-engagement';
		}
		return true;
	}

	/** Convenience boolean. */
	public static function can_review( $reviewer_id, $type, $subject_id ) {
		return true === self::review_block( $reviewer_id, $type, $subject_id );
	}

	/** The reviewer's existing review of this subject (any status), or null. */
	public static function existing_review( $reviewer_id, $type, $subject_id ) {
		global $wpdb;
		$t = self::table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE reviewer_user_id = %d AND subject_type = %s AND subject_id = %d", (int) $reviewer_id, $type, (int) $subject_id ) ); // phpcs:ignore WordPress.DB
	}

	/**
	 * Create or update the reviewer's review (back to `pending` for re-moderation on edit).
	 *
	 * @return int|false review id, or false on failure / not allowed.
	 */
	public static function add_or_update( $reviewer_id, $type, $subject_id, $rating, $title, $body ) {
		global $wpdb;
		if ( ! self::can_review( $reviewer_id, $type, $subject_id ) ) {
			return false;
		}
		$rating = max( 1, min( 5, (int) $rating ) );
		$title  = sanitize_text_field( (string) $title );
		$body   = wp_kses_post( (string) $body );
		$now    = current_time( 'mysql' );
		$t      = self::table();

		$existing = self::existing_review( $reviewer_id, $type, $subject_id );
		if ( $existing ) {
			$ok = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$t,
				array( 'rating' => $rating, 'title' => $title, 'body' => $body, 'status' => 'pending', 'updated_at' => $now ),
				array( 'id' => (int) $existing->id ),
				array( '%d', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);
			$id = ( false !== $ok ) ? (int) $existing->id : false;
		} else {
			$ok = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$t,
				array(
					'reviewer_user_id' => (int) $reviewer_id,
					'subject_type'     => $type,
					'subject_id'       => (int) $subject_id,
					'rating'           => $rating,
					'title'            => $title,
					'body'             => $body,
					'status'           => 'pending',
					'created_at'       => $now,
					'updated_at'       => $now,
				),
				array( '%d', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
			);
			$id = $ok ? (int) $wpdb->insert_id : false;
		}
		if ( $id ) {
			self::recompute_meta( $type, $subject_id );
			do_action( 'shuffles_ssj_review_saved', $id, $type, (int) $subject_id, (int) $reviewer_id );
		}
		return $id;
	}

	/** The reviewed party posts a single public response to a review. Owner/admin of the subject only. */
	public static function add_response( $review_id, $responder_id, $text ) {
		global $wpdb;
		$t   = self::table();
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE id = %d", (int) $review_id ) ); // phpcs:ignore WordPress.DB
		if ( ! $row ) {
			return false;
		}
		if ( ! self::user_owns_subject( (int) $responder_id, $row->subject_type, (int) $row->subject_id ) ) {
			return false;
		}
		return (bool) $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$t,
			array( 'response' => wp_kses_post( (string) $text ), 'response_at' => current_time( 'mysql' ) ),
			array( 'id' => (int) $review_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	/** Is this user the owner/admin of the subject (so they may respond)? */
	public static function user_owns_subject( $user_id, $type, $subject_id ) {
		$user_id = (int) $user_id;
		if ( ! $user_id ) {
			return false;
		}
		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}
		if ( 'org' === $type && class_exists( 'Shuffles_SSJ_Org_Team' ) ) {
			return (bool) Shuffles_SSJ_Org_Team::is_admin( $subject_id, $user_id );
		}
		if ( 'worker' === $type ) {
			return (int) get_post_meta( (int) $subject_id, 'worker_user_id', true ) === $user_id;
		}
		return false;
	}

	/** Approved reviews for a subject (newest first). */
	public static function for_subject( $type, $subject_id, $status = 'approved' ) {
		global $wpdb;
		$t = self::table();
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} WHERE subject_type = %s AND subject_id = %d AND status = %s ORDER BY updated_at DESC", $type, (int) $subject_id, $status ) ); // phpcs:ignore WordPress.DB
	}

	/** [ avg (float, 1dp), count (int) ] over APPROVED reviews. */
	public static function average( $type, $subject_id ) {
		global $wpdb;
		$t   = self::table();
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT AVG(rating) AS a, COUNT(*) AS c FROM {$t} WHERE subject_type = %s AND subject_id = %d AND status = 'approved'", $type, (int) $subject_id ) ); // phpcs:ignore WordPress.DB
		$avg = ( $row && $row->c ) ? round( (float) $row->a, 1 ) : 0.0;
		$cnt = $row ? (int) $row->c : 0;
		return array( $avg, $cnt );
	}

	/** Cache the approved average + count on the subject post (read cheaply by cards + the matcher). */
	public static function recompute_meta( $type, $subject_id ) {
		list( $avg, $cnt ) = self::average( $type, $subject_id );
		update_post_meta( (int) $subject_id, self::RATING_AVG_META, $avg );
		update_post_meta( (int) $subject_id, self::RATING_COUNT_META, $cnt );
	}

	/** Moderation: set a review's status; recompute the subject's average. */
	public static function set_status( $review_id, $status, $admin_id = 0 ) {
		global $wpdb;
		if ( ! in_array( $status, array( 'pending', 'approved', 'rejected' ), true ) ) {
			return false;
		}
		$t   = self::table();
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE id = %d", (int) $review_id ) ); // phpcs:ignore WordPress.DB
		if ( ! $row ) {
			return false;
		}
		$ok = (bool) $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$t,
			array( 'status' => $status, 'moderated_by' => (int) $admin_id, 'moderated_at' => current_time( 'mysql' ) ),
			array( 'id' => (int) $review_id ),
			array( '%s', '%d', '%s' ),
			array( '%d' )
		);
		if ( $ok ) {
			self::recompute_meta( $row->subject_type, (int) $row->subject_id );
		}
		return $ok;
	}

	/** Reviews awaiting moderation (oldest first). */
	public static function pending( $limit = 100 ) {
		global $wpdb;
		$t = self::table();
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} WHERE status = 'pending' ORDER BY created_at ASC LIMIT %d", (int) $limit ) ); // phpcs:ignore WordPress.DB
	}

	/** Recent reviews of any status (for the admin list). */
	public static function recent( $limit = 50 ) {
		global $wpdb;
		$t = self::table();
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} ORDER BY created_at DESC LIMIT %d", (int) $limit ) ); // phpcs:ignore WordPress.DB
	}

	/* ------------------------------------------------------------------ */
	/* Rendering                                                          */
	/* ------------------------------------------------------------------ */

	/** Star row for a rating (filled + empty), with an accessible label. */
	public static function stars_html( $rating, $max = 5 ) {
		$rating = (float) $rating;
		$full   = (int) floor( $rating );
		$out    = '<span class="sssj-stars" role="img" aria-label="' . esc_attr( sprintf( __( '%1$s out of %2$d stars', 'shuffles-social-services-jobs' ), rtrim( rtrim( number_format( $rating, 1 ), '0' ), '.' ), $max ) ) . '">';
		for ( $i = 1; $i <= $max; $i++ ) {
			$on   = ( $i <= $full );
			$out .= '<span class="sssj-star' . ( $on ? ' is-on' : '' ) . '" aria-hidden="true">' . ( $on ? '★' : '☆' ) . '</span>';
		}
		$out .= '</span>';
		return $out;
	}

	/**
	 * The full reviews block for a subject profile: average summary, approved reviews (+ responses),
	 * and the leave-a-review form (only for eligible, engaged members).
	 *
	 * @return string
	 */
	public static function render_for( $type, $subject_id ) {
		if ( ! self::enabled() || ! in_array( $type, self::types(), true ) ) {
			return '';
		}
		$subject_id = (int) $subject_id;
		list( $avg, $cnt ) = self::average( $type, $subject_id );
		$reviews   = (array) self::for_subject( $type, $subject_id );
		$viewer    = get_current_user_id();
		$block     = self::review_block( $viewer, $type, $subject_id );
		$can       = ( true === $block );
		$existing  = $can ? self::existing_review( $viewer, $type, $subject_id ) : null;
		$can_resp  = $viewer && self::user_owns_subject( $viewer, $type, $subject_id );

		$action = esc_url( admin_url( 'admin-post.php' ) );

		ob_start();
		?>
		<div class="sssj sssj--reviews" id="sssj-reviews">
			<div class="sssj-panel">
				<h2 style="margin-top:0"><?php esc_html_e( 'Ratings & reviews', 'shuffles-social-services-jobs' ); ?></h2>
				<?php if ( $cnt > 0 ) : ?>
					<p class="sssj-reviews__avg">
						<?php echo self::stars_html( $avg ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<strong><?php echo esc_html( number_format( $avg, 1 ) ); ?></strong>
						<span class="description"><?php echo esc_html( sprintf( _n( '%d review', '%d reviews', $cnt, 'shuffles-social-services-jobs' ), $cnt ) ); ?></span>
					</p>
				<?php else : ?>
					<p class="description"><?php esc_html_e( 'No reviews yet.', 'shuffles-social-services-jobs' ); ?></p>
				<?php endif; ?>
			</div>

			<?php foreach ( $reviews as $r ) :
				$ru   = get_userdata( (int) $r->reviewer_user_id );
				$name = $ru ? $ru->display_name : __( 'Member', 'shuffles-social-services-jobs' );
				?>
				<div class="sssj-panel sssj-review">
					<div class="sssj-review__head">
						<?php echo self::stars_html( (int) $r->rating ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php if ( ! empty( $r->title ) ) : ?>
							<strong class="sssj-review__title"><?php echo esc_html( $r->title ); ?></strong>
						<?php endif; ?>
					</div>
					<?php if ( ! empty( $r->body ) ) : ?>
						<div class="sssj-review__body"><?php echo wp_kses_post( wpautop( $r->body ) ); ?></div>
					<?php endif; ?>
					<p class="sssj-review__meta description"><?php echo esc_html( sprintf( __( 'by %1$s · %2$s', 'shuffles-social-services-jobs' ), $name, mysql2date( get_option( 'date_format' ), (string) $r->updated_at ) ) ); ?></p>

					<?php if ( ! empty( $r->response ) ) : ?>
						<div class="sssj-review__response">
							<strong><?php esc_html_e( 'Response', 'shuffles-social-services-jobs' ); ?></strong>
							<?php echo wp_kses_post( wpautop( $r->response ) ); ?>
						</div>
					<?php elseif ( $can_resp ) : ?>
						<details class="sssj-review__respondform">
							<summary><?php esc_html_e( 'Respond to this review', 'shuffles-social-services-jobs' ); ?></summary>
							<form method="post" action="<?php echo $action; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
								<input type="hidden" name="action" value="sssj_review_respond" />
								<input type="hidden" name="review_id" value="<?php echo esc_attr( $r->id ); ?>" />
								<?php wp_nonce_field( 'sssj_review_respond', 'sssj_review_resp_nonce' ); ?>
								<textarea name="response" class="sssj-input" rows="3" maxlength="1000" required></textarea>
								<button type="submit" class="sssj-btn sssj-btn--secondary sssj-btn--sm"><?php esc_html_e( 'Post response', 'shuffles-social-services-jobs' ); ?></button>
							</form>
						</details>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>

			<?php if ( $can ) : ?>
				<div class="sssj-panel sssj-review-form">
					<h3 style="margin-top:0"><?php echo esc_html( $existing ? __( 'Edit your review', 'shuffles-social-services-jobs' ) : __( 'Leave a review', 'shuffles-social-services-jobs' ) ); ?></h3>
					<p class="description"><?php esc_html_e( 'Reviews are checked by a moderator before they appear. Please be fair, factual and respectful.', 'shuffles-social-services-jobs' ); ?></p>
					<form method="post" action="<?php echo $action; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
						<input type="hidden" name="action" value="sssj_review_submit" />
						<input type="hidden" name="subject_type" value="<?php echo esc_attr( $type ); ?>" />
						<input type="hidden" name="subject_id" value="<?php echo esc_attr( $subject_id ); ?>" />
						<?php wp_nonce_field( 'sssj_review_submit', 'sssj_review_nonce' ); ?>

						<fieldset class="sssj-rate">
							<legend><?php esc_html_e( 'Your rating', 'shuffles-social-services-jobs' ); ?></legend>
							<?php
							$cur = $existing ? (int) $existing->rating : 0;
							// Reverse 5→1 so the CSS sibling hover/checked trick can fill leftwards.
							for ( $i = 5; $i >= 1; $i-- ) {
								$id = 'sssj-rate-' . $type . '-' . $subject_id . '-' . $i;
								printf(
									'<input type="radio" id="%1$s" name="rating" value="%2$d" %3$s required /><label for="%1$s" title="%4$s"><span aria-hidden="true">★</span><span class="screen-reader-text">%4$s</span></label>',
									esc_attr( $id ),
									(int) $i,
									checked( $cur, $i, false ),
									esc_attr( sprintf( _n( '%d star', '%d stars', $i, 'shuffles-social-services-jobs' ), $i ) )
								);
							}
							?>
						</fieldset>

						<label class="sssj-field">
							<span><?php esc_html_e( 'Title (optional)', 'shuffles-social-services-jobs' ); ?></span>
							<input type="text" name="title" class="sssj-input" maxlength="120" value="<?php echo esc_attr( $existing ? (string) $existing->title : '' ); ?>" />
						</label>
						<label class="sssj-field">
							<span><?php esc_html_e( 'Your review', 'shuffles-social-services-jobs' ); ?></span>
							<textarea name="body" class="sssj-input" rows="5" maxlength="3000" required><?php echo esc_textarea( $existing ? (string) $existing->body : '' ); ?></textarea>
						</label>
						<button type="submit" class="sssj-btn sssj-btn--primary"><?php echo esc_html( $existing ? __( 'Update review', 'shuffles-social-services-jobs' ) : __( 'Submit review', 'shuffles-social-services-jobs' ) ); ?></button>
						<?php if ( $existing && 'pending' === $existing->status ) : ?>
							<span class="sssj-badge"><?php esc_html_e( 'Awaiting moderation', 'shuffles-social-services-jobs' ); ?></span>
						<?php endif; ?>
					</form>
				</div>
			<?php elseif ( 'login' === $block ) : ?>
				<div class="sssj-panel"><p><a class="sssj-btn sssj-btn--secondary sssj-btn--sm" href="<?php echo esc_url( Shuffles_SSJ_Shortcodes::login_url( get_permalink( $subject_id ) ) ); ?>"><?php esc_html_e( 'Log in to leave a review', 'shuffles-social-services-jobs' ); ?></a></p></div>
			<?php elseif ( 'no-engagement' === $block ) : ?>
				<div class="sssj-panel"><p class="description"><?php esc_html_e( 'Only members who have engaged through the platform (started a message or applied) can leave a review here.', 'shuffles-social-services-jobs' ); ?></p></div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
