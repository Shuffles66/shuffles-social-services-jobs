<?php
/**
 * Account verification — the "blue tick".
 *
 * Distinct from per-credential verification (the green ✓ Verified badge driven by Credentials):
 * the blue tick is an admin-granted, account-level trust mark ("we have verified this worker /
 * organisation"). Granted from a metabox on the worker/organisation edit screen; rendered as a
 * blue circular tick next to the name on cards, directories and profiles.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Verification {

	const META = 'sssj_blue_tick';

	public function register() {
		add_action( 'init', array( $this, 'register_meta' ), 6 );
		add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
		add_action( 'save_post_sssj_worker', array( $this, 'save' ), 10, 2 );
		add_action( 'save_post_sssj_org', array( $this, 'save' ), 10, 2 );
	}

	public function register_meta() {
		foreach ( array( 'sssj_worker', 'sssj_org' ) as $pt ) {
			register_post_meta(
				$pt,
				self::META,
				array(
					'type'         => 'boolean',
					'single'       => true,
					'show_in_rest' => true,
					'auth_callback' => function () {
						return current_user_can( 'manage_options' );
					},
				)
			);
		}
	}

	/* --------------------------------------------------------------- Public API */

	/** Is this worker/org blue-tick verified? */
	public static function is_verified( $post_id ) {
		return '1' === (string) get_post_meta( (int) $post_id, self::META, true );
	}

	/**
	 * Blue-tick badge HTML (empty string if not verified).
	 *
	 * @param int  $post_id    Worker or org post.
	 * @param bool $with_label Show the "Verified" word after the tick.
	 * @return string
	 */
	public static function tick_html( $post_id, $with_label = true ) {
		if ( ! self::is_verified( $post_id ) ) {
			return '';
		}
		$label = $with_label ? '<span class="sssj-bluetick__label">' . esc_html__( 'Verified', 'shuffles-social-services-jobs' ) . '</span>' : '';
		return '<span class="sssj-bluetick" title="' . esc_attr__( 'Verified by the team', 'shuffles-social-services-jobs' ) . '" aria-label="' . esc_attr__( 'Verified', 'shuffles-social-services-jobs' ) . '"><span class="sssj-bluetick__mark" aria-hidden="true"></span>' . $label . '</span>';
	}

	/* --------------------------------------------------------------- Admin metabox */

	public function add_metabox() {
		foreach ( array( 'sssj_worker', 'sssj_org' ) as $pt ) {
			add_meta_box( 'sssj-bluetick', __( 'Verification (blue tick)', 'shuffles-social-services-jobs' ), array( $this, 'metabox' ), $pt, 'side', 'high' );
		}
	}

	public function metabox( $post ) {
		wp_nonce_field( 'sssj_bluetick_' . $post->ID, 'sssj_bluetick_nonce' );
		$on = self::is_verified( $post->ID );
		echo '<p><label><input type="checkbox" name="sssj_blue_tick" value="1" ' . checked( $on, true, false ) . ' /> <strong>' . esc_html__( 'Account verified (blue tick)', 'shuffles-social-services-jobs' ) . '</strong></label></p>';
		echo '<p class="description">' . esc_html__( 'Grant the blue tick once you have verified this account (identity + the key checks for their role). It appears next to the name across the site. This is separate from the green “✓ Verified” credential badge, which is set on the Verification screen per document.', 'shuffles-social-services-jobs' ) . '</p>';
		// Organisations only: an admin-granted "Sponsored" placement (sorts to the top of the directory).
		if ( 'sssj_org' === $post->post_type ) {
			$spon = '1' === (string) get_post_meta( $post->ID, 'org_sponsored', true );
			echo '<hr /><p><label><input type="checkbox" name="sssj_org_sponsored" value="1" ' . checked( $spon, true, false ) . ' /> <strong>' . esc_html__( 'Sponsored placement', 'shuffles-social-services-jobs' ) . '</strong></label></p>';
			echo '<p class="description">' . esc_html__( 'Sponsored organisations sort to the top of the directory and show a ★ Sponsored badge. Grant this when a provider has paid for or been awarded a sponsored placement.', 'shuffles-social-services-jobs' ) . '</p>';
		}
	}

	public function save( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		$nonce = isset( $_POST['sssj_bluetick_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['sssj_bluetick_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'sssj_bluetick_' . $post_id ) ) {
			return; // metabox not submitted (e.g. quick edit / REST) — don't touch the value
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( empty( $_POST['sssj_blue_tick'] ) ) {
			delete_post_meta( $post_id, self::META );
		} else {
			update_post_meta( $post_id, self::META, '1' );
		}
		// Organisations only: sponsored placement.
		if ( 'sssj_org' === $post->post_type ) {
			if ( empty( $_POST['sssj_org_sponsored'] ) ) {
				delete_post_meta( $post_id, 'org_sponsored' );
			} else {
				update_post_meta( $post_id, 'org_sponsored', '1' );
			}
		}
	}
}
