<?php
/**
 * Testimonials (Workstream F), owner-curated endorsements on CONTRACTOR (worker) and PROVIDER (org)
 * profiles.
 *
 * Distinct from Reviews:
 *  - Reviews are 1–5 star ratings, engagement-gated, and the average feeds the matcher. They are
 *    objective and earned.
 *  - Testimonials are qualitative quotes/endorsements. The profile OWNER curates which ones show
 *    (feature/unfeature), and can also add a quote they received elsewhere. Other members may submit
 *    an endorsement too. Everything is PRE-MODERATED (admin approves) before it can ever be public,
 *    and only APPROVED + FEATURED testimonials show on the profile.
 *
 * Safety: nothing shows without admin approval; the submitter chooses how they are credited (so a
 * participant never has to reveal their identity); testimonials only attach to worker/org profiles,
 * never to participant needs.
 *
 * Storage: custom table {prefix}sssj_testimonial (see Shuffles_SSJ_Activator, DB_VERSION 9).
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Testimonials {

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'sssj_testimonial';
	}

	/** Master switch (Settings → Testimonials). Default ON. */
	public static function enabled() {
		$o = get_option( 'shuffles_ssj_settings', array() );
		return ! ( is_array( $o ) && isset( $o['testimonials_enabled'] ) && ! $o['testimonials_enabled'] );
	}

	public static function types() {
		return array( 'worker', 'org' );
	}

	public static function type_label( $type ) {
		return ( 'org' === $type ) ? __( 'provider', 'shuffles-social-services-jobs' ) : __( 'contractor', 'shuffles-social-services-jobs' );
	}

	/** Is this user the owner/admin of the subject (so they may curate)? */
	public static function user_owns_subject( $user_id, $type, $subject_id ) {
		$user_id = (int) $user_id;
		if ( ! $user_id ) {
			return false;
		}
		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}
		if ( 'org' === $type && class_exists( 'Shuffles_SSJ_Org_Team' ) ) {
			return (bool) Shuffles_SSJ_Org_Team::is_admin( (int) $subject_id, $user_id );
		}
		if ( 'worker' === $type ) {
			return (int) get_post_meta( (int) $subject_id, 'worker_user_id', true ) === $user_id;
		}
		return false;
	}

	/** May $uid submit a testimonial about this subject? (Logged-in, valid subject, not the owner.) */
	public static function can_submit( $uid, $type, $subject_id ) {
		$uid = (int) $uid;
		if ( ! self::enabled() || ! $uid || ! in_array( $type, self::types(), true ) ) {
			return false;
		}
		$post = get_post( (int) $subject_id );
		if ( ! $post || ( 'sssj_worker' !== $post->post_type && 'sssj_org' !== $post->post_type ) ) {
			return false;
		}
		// You do not "submit" about yourself, owners add their own via the curate path.
		return ! self::user_owns_subject( $uid, $type, $subject_id );
	}

	/** Member submits an endorsement (held pending). Returns id|false. */
	public static function submit( $uid, $type, $subject_id, $body, $author_name = '', $author_role = '' ) {
		if ( ! self::can_submit( $uid, $type, $subject_id ) ) {
			return false;
		}
		$ru = get_userdata( (int) $uid );
		$name = '' !== trim( (string) $author_name ) ? $author_name : ( $ru ? $ru->display_name : __( 'A member', 'shuffles-social-services-jobs' ) );
		return self::insert( $type, $subject_id, (int) $uid, $name, $author_role, $body, 'submitted' );
	}

	/** Owner adds a quote they received elsewhere (held pending, then they feature it). Returns id|false. */
	public static function owner_add( $uid, $type, $subject_id, $body, $author_name = '', $author_role = '' ) {
		if ( ! self::user_owns_subject( (int) $uid, $type, $subject_id ) ) {
			return false;
		}
		return self::insert( $type, $subject_id, (int) $uid, $author_name, $author_role, $body, 'owner' );
	}

	/** Shared insert. */
	private static function insert( $type, $subject_id, $uid, $author_name, $author_role, $body, $source ) {
		global $wpdb;
		$body = trim( wp_kses_post( (string) $body ) );
		if ( '' === wp_strip_all_tags( $body ) ) {
			return false;
		}
		$now = current_time( 'mysql' );
		$ok  = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			array(
				'subject_type'   => $type,
				'subject_id'     => (int) $subject_id,
				'author_user_id' => (int) $uid,
				'author_name'    => sanitize_text_field( (string) $author_name ),
				'author_role'    => sanitize_text_field( (string) $author_role ),
				'body'           => $body,
				'source'         => ( 'owner' === $source ? 'owner' : 'submitted' ),
				'status'         => 'pending',
				'featured'       => 0,
				'sort_order'     => 0,
				'created_at'     => $now,
				'updated_at'     => $now,
			),
			array( '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s' )
		);
		if ( ! $ok ) {
			return false;
		}
		$id = (int) $wpdb->insert_id;
		do_action( 'shuffles_ssj_testimonial_submitted', $id, $type, (int) $subject_id, (int) $uid );
		return $id;
	}

	/** Load a single row. */
	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', (int) $id ) ); // phpcs:ignore WordPress.DB
	}

	/** Moderation: approve / reject / pending. */
	public static function set_status( $id, $status, $admin_id = 0 ) {
		global $wpdb;
		if ( ! in_array( $status, array( 'pending', 'approved', 'rejected' ), true ) ) {
			return false;
		}
		$row = self::get( $id );
		if ( ! $row ) {
			return false;
		}
		$data = array( 'status' => $status, 'moderated_by' => (int) $admin_id, 'moderated_at' => current_time( 'mysql' ) );
		// A rejected testimonial can never be featured.
		if ( 'approved' !== $status ) {
			$data['featured'] = 0;
		}
		return (bool) $wpdb->update( self::table(), $data, array( 'id' => (int) $id ), null, array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/** Owner curates which approved testimonials show. Only the subject's owner/admin. */
	public static function set_featured( $id, $featured, $user_id ) {
		global $wpdb;
		$row = self::get( $id );
		if ( ! $row || ! self::user_owns_subject( (int) $user_id, $row->subject_type, (int) $row->subject_id ) ) {
			return false;
		}
		if ( $featured && 'approved' !== $row->status ) {
			return false; // can only feature an approved one
		}
		return (bool) $wpdb->update( self::table(), array( 'featured' => $featured ? 1 : 0, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => (int) $id ), array( '%d', '%s' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/** Delete a testimonial. Owner/admin of the subject, or the original submitter. */
	public static function delete( $id, $user_id ) {
		global $wpdb;
		$row = self::get( $id );
		if ( ! $row ) {
			return false;
		}
		$allowed = self::user_owns_subject( (int) $user_id, $row->subject_type, (int) $row->subject_id )
			|| ( (int) $row->author_user_id === (int) $user_id && (int) $user_id > 0 );
		if ( ! $allowed ) {
			return false;
		}
		return (bool) $wpdb->delete( self::table(), array( 'id' => (int) $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/** Public: approved + featured, owner-ordered. */
	public static function for_subject_public( $type, $subject_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . " WHERE subject_type = %s AND subject_id = %d AND status = 'approved' AND featured = 1 ORDER BY sort_order ASC, created_at DESC", $type, (int) $subject_id ) ); // phpcs:ignore WordPress.DB
	}

	/** Owner view: every testimonial about the subject (any status), newest first. */
	public static function for_subject_all( $type, $subject_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE subject_type = %s AND subject_id = %d ORDER BY created_at DESC', $type, (int) $subject_id ) ); // phpcs:ignore WordPress.DB
	}

	public static function pending( $limit = 100 ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . " WHERE status = 'pending' ORDER BY created_at ASC LIMIT %d", (int) $limit ) ); // phpcs:ignore WordPress.DB
	}

	public static function recent( $limit = 50 ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' ORDER BY created_at DESC LIMIT %d', (int) $limit ) ); // phpcs:ignore WordPress.DB
	}

	/** A credit line like "- Jane, Client". */
	private static function credit( $row ) {
		$name = trim( (string) $row->author_name );
		$role = trim( (string) $row->author_role );
		if ( '' === $name && '' === $role ) {
			return __( 'A member', 'shuffles-social-services-jobs' );
		}
		if ( '' === $role ) {
			return $name;
		}
		if ( '' === $name ) {
			return $role;
		}
		return $name . ', ' . $role;
	}

	/* ------------------------------------------------------------------ rendering */

	/**
	 * The testimonials block for a subject profile: featured quotes, a "leave a testimonial" form for
	 * eligible non-owners, and (for the owner) a curation panel + "add a quote you received" form.
	 *
	 * @return string
	 */
	public static function render_for( $type, $subject_id ) {
		if ( ! self::enabled() || ! in_array( $type, self::types(), true ) ) {
			return '';
		}
		$subject_id = (int) $subject_id;
		$featured   = (array) self::for_subject_public( $type, $subject_id );
		$viewer     = get_current_user_id();
		$is_owner   = $viewer && self::user_owns_subject( $viewer, $type, $subject_id );
		$can_submit = self::can_submit( $viewer, $type, $subject_id );

		// Keep logged-out profiles clean when there is nothing to show and nothing to do.
		if ( empty( $featured ) && ! $viewer ) {
			return '';
		}

		$action = esc_url( admin_url( 'admin-post.php' ) );
		ob_start();
		?>
		<div class="sssj sssj--testimonials" id="sssj-testimonials">
			<?php
			$flag = isset( $_GET['sssj_testi'] ) ? sanitize_key( wp_unslash( $_GET['sssj_testi'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( 'pending' === $flag ) {
				echo '<div class="sssj-panel"><p class="sssj-badge sssj-badge--verified">' . esc_html__( 'Thank you, your testimonial was sent for moderation.', 'shuffles-social-services-jobs' ) . '</p></div>';
			} elseif ( 'error' === $flag ) {
				echo '<div class="sssj-panel"><p class="sssj-badge" style="background:#fee2e2;color:#b91c1c">' . esc_html__( 'Sorry, your testimonial could not be saved. Please try again.', 'shuffles-social-services-jobs' ) . '</p></div>';
			}
			?>
			<div class="sssj-panel">
				<h2 style="margin-top:0"><?php esc_html_e( 'What people say', 'shuffles-social-services-jobs' ); ?></h2>
				<?php if ( ! empty( $featured ) ) : ?>
					<div class="sssj-testi-grid">
						<?php foreach ( $featured as $t ) : ?>
							<figure class="sssj-testi">
								<blockquote class="sssj-testi__quote"><?php echo wp_kses_post( wpautop( $t->body ) ); ?></blockquote>
								<figcaption class="sssj-testi__by">- <?php echo esc_html( self::credit( $t ) ); ?></figcaption>
							</figure>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<p class="description"><?php echo esc_html( $is_owner ? __( 'No testimonials are showing yet. Add a quote you have received, or invite people to endorse you, then feature the best ones below.', 'shuffles-social-services-jobs' ) : __( 'No testimonials yet. Be the first to endorse them.', 'shuffles-social-services-jobs' ) ); ?></p>
				<?php endif; ?>
			</div>

			<?php if ( $can_submit ) : ?>
				<div class="sssj-panel">
					<details class="sssj-testi-form">
						<summary><?php esc_html_e( 'Leave a testimonial', 'shuffles-social-services-jobs' ); ?></summary>
						<p class="description"><?php esc_html_e( 'Testimonials are checked by a moderator before they appear, and the person decides which to feature. Please be genuine and respectful. You choose how you are credited, you never have to use your full name.', 'shuffles-social-services-jobs' ); ?></p>
						<form method="post" action="<?php echo $action; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
							<input type="hidden" name="action" value="sssj_testimonial_submit" />
							<input type="hidden" name="subject_type" value="<?php echo esc_attr( $type ); ?>" />
							<input type="hidden" name="subject_id" value="<?php echo esc_attr( $subject_id ); ?>" />
							<?php wp_nonce_field( 'sssj_testimonial_submit', 'sssj_testi_nonce' ); ?>
							<label class="sssj-field"><span><?php esc_html_e( 'Your endorsement', 'shuffles-social-services-jobs' ); ?></span>
								<textarea name="body" class="sssj-input" rows="4" maxlength="1500" required></textarea></label>
							<div class="sssj-row" style="gap:10px;flex-wrap:wrap">
								<label class="sssj-field" style="flex:1 1 200px"><span><?php esc_html_e( 'Show my name as', 'shuffles-social-services-jobs' ); ?></span>
									<input type="text" name="author_name" class="sssj-input" maxlength="80" placeholder="<?php esc_attr_e( 'e.g. Jane, or J.M., or leave blank', 'shuffles-social-services-jobs' ); ?>" /></label>
								<label class="sssj-field" style="flex:1 1 200px"><span><?php esc_html_e( 'Your relationship', 'shuffles-social-services-jobs' ); ?></span>
									<input type="text" name="author_role" class="sssj-input" maxlength="80" placeholder="<?php esc_attr_e( 'e.g. Client, Colleague, Person I support', 'shuffles-social-services-jobs' ); ?>" /></label>
							</div>
							<button type="submit" class="sssj-btn sssj-btn--primary sssj-btn--sm"><?php esc_html_e( 'Submit testimonial', 'shuffles-social-services-jobs' ); ?></button>
						</form>
					</details>
				</div>
			<?php elseif ( ! $viewer && ! empty( $featured ) ) : ?>
				<div class="sssj-panel"><p><a class="sssj-btn sssj-btn--secondary sssj-btn--sm" href="<?php echo esc_url( Shuffles_SSJ_Shortcodes::login_url( get_permalink( $subject_id ) ) ); ?>"><?php esc_html_e( 'Log in to leave a testimonial', 'shuffles-social-services-jobs' ); ?></a></p></div>
			<?php endif; ?>

			<?php if ( $is_owner ) : echo self::owner_panel_html( $type, $subject_id, $action ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/** Owner curation panel: feature/unfeature/delete each, plus an "add a quote you received" form. */
	private static function owner_panel_html( $type, $subject_id, $action ) {
		$rows = (array) self::for_subject_all( $type, $subject_id );
		$nonce = wp_nonce_field( 'sssj_testimonial_curate', 'sssj_testi_curate_nonce', true, false );
		ob_start();
		?>
		<div class="sssj-panel">
			<details class="sssj-testi-manage">
				<summary><?php esc_html_e( 'Manage my testimonials', 'shuffles-social-services-jobs' ); ?></summary>
				<p class="description"><?php esc_html_e( 'Feature the ones you want to show on your profile. Everything is checked by a moderator first. Featuring only works once a testimonial is approved.', 'shuffles-social-services-jobs' ); ?></p>
				<?php if ( empty( $rows ) ) : ?>
					<p class="description"><?php esc_html_e( 'Nothing yet.', 'shuffles-social-services-jobs' ); ?></p>
				<?php else : ?>
					<ul class="sssj-testi-list">
						<?php foreach ( $rows as $t ) :
							$badge = ( 'approved' === $t->status ) ? ( $t->featured ? __( 'Featured', 'shuffles-social-services-jobs' ) : __( 'Approved', 'shuffles-social-services-jobs' ) ) : ucfirst( (string) $t->status );
							?>
							<li class="sssj-testi-list__item">
								<div class="sssj-testi-list__text">
									<span class="sssj-badge"><?php echo esc_html( $badge ); ?></span>
									“<?php echo esc_html( wp_trim_words( wp_strip_all_tags( (string) $t->body ), 30 ) ); ?>”
									<span class="description">- <?php echo esc_html( self::credit( $t ) ); ?></span>
								</div>
								<div class="sssj-testi-list__actions">
									<?php
									$op_btn = function ( $op, $label, $primary = false ) use ( $action, $nonce, $t ) {
										echo '<form method="post" action="' . $action . '" style="display:inline-block;margin:0 4px 0 0">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										echo '<input type="hidden" name="action" value="sssj_testimonial_curate" />';
										echo $nonce; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										echo '<input type="hidden" name="op" value="' . esc_attr( $op ) . '" />';
										echo '<input type="hidden" name="testi_id" value="' . esc_attr( $t->id ) . '" />';
										echo '<button type="submit" class="sssj-btn sssj-btn--sm ' . ( $primary ? 'sssj-btn--primary' : 'sssj-btn--ghost' ) . '">' . esc_html( $label ) . '</button>';
										echo '</form>';
									};
									if ( 'approved' === $t->status && ! $t->featured ) {
										$op_btn( 'feature', __( 'Feature', 'shuffles-social-services-jobs' ), true );
									} elseif ( 'approved' === $t->status && $t->featured ) {
										$op_btn( 'unfeature', __( 'Hide', 'shuffles-social-services-jobs' ) );
									}
									$op_btn( 'delete', __( 'Delete', 'shuffles-social-services-jobs' ) );
									?>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<h4 style="margin:14px 0 6px"><?php esc_html_e( 'Add a quote you have received', 'shuffles-social-services-jobs' ); ?></h4>
				<form method="post" action="<?php echo $action; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
					<input type="hidden" name="action" value="sssj_testimonial_curate" />
					<input type="hidden" name="op" value="add" />
					<input type="hidden" name="subject_type" value="<?php echo esc_attr( $type ); ?>" />
					<input type="hidden" name="subject_id" value="<?php echo esc_attr( $subject_id ); ?>" />
					<?php echo $nonce; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<label class="sssj-field"><span><?php esc_html_e( 'What they said', 'shuffles-social-services-jobs' ); ?></span>
						<textarea name="body" class="sssj-input" rows="3" maxlength="1500" required></textarea></label>
					<div class="sssj-row" style="gap:10px;flex-wrap:wrap">
						<label class="sssj-field" style="flex:1 1 200px"><span><?php esc_html_e( 'Who said it', 'shuffles-social-services-jobs' ); ?></span>
							<input type="text" name="author_name" class="sssj-input" maxlength="80" /></label>
						<label class="sssj-field" style="flex:1 1 200px"><span><?php esc_html_e( 'Their relationship', 'shuffles-social-services-jobs' ); ?></span>
							<input type="text" name="author_role" class="sssj-input" maxlength="80" placeholder="<?php esc_attr_e( 'e.g. Client, Family member', 'shuffles-social-services-jobs' ); ?>" /></label>
					</div>
					<button type="submit" class="sssj-btn sssj-btn--secondary sssj-btn--sm"><?php esc_html_e( 'Add for moderation', 'shuffles-social-services-jobs' ); ?></button>
				</form>
			</details>
		</div>
		<?php
		return ob_get_clean();
	}
}
