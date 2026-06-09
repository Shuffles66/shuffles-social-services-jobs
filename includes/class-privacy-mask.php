<?php
/**
 * Per-field privacy masking for contact / sensitive fields.
 *
 * Owners can flag individual contact / sensitive fields on their worker or organisation
 * profile as "members only". A masked field is hidden from logged-out guests (who see a
 * "Log in to view" lock) but shown to logged-in members. The owner and admins always see
 * their own values so they can preview. Storage: a single `_sssj_mask` post-meta array of
 * masked field keys. This class is the SINGLE SOURCE for the form checkboxes AND the
 * display gate, never hard-code a field name in a template.
 *
 * Note: this is an additive privacy layer. The structural rules still stand, participant
 * contact is NEVER shown, worker visibility flags are enforced in the query layer, and
 * register-sourced NDIS data (outlets / phone / ABN) is read-only and not maskable here.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Privacy {

	const META = '_sssj_mask';

	/**
	 * Maskable contact / sensitive fields per entity type: key => label.
	 *
	 * @param string $entity 'worker' | 'org'
	 * @return array
	 */
	public static function maskable( $entity ) {
		$map = array(
			'worker' => array(
				'rate' => __( 'Pay rate', 'shuffles-social-services-jobs' ),
			),
			'org'    => array(
				'phone'   => __( 'Phone number', 'shuffles-social-services-jobs' ),
				'website' => __( 'Website', 'shuffles-social-services-jobs' ),
			),
		);
		/** Allow later entities / fields to opt in. */
		$map = apply_filters( 'shuffles_ssj_maskable_fields', $map );
		return isset( $map[ $entity ] ) ? $map[ $entity ] : array();
	}

	/** The field keys the owner has flagged "members only" for a post. */
	public static function masked_keys( $post_id ) {
		$v = get_post_meta( (int) $post_id, self::META, true );
		return is_array( $v ) ? array_values( $v ) : array();
	}

	public static function is_masked( $post_id, $key ) {
		return in_array( $key, self::masked_keys( $post_id ), true );
	}

	/**
	 * Save the chosen masked keys from a posted checkbox group, validated to the entity's
	 * maskable set (anything else is dropped).
	 *
	 * @param int    $post_id Post being saved.
	 * @param string $entity  'worker' | 'org'.
	 * @param array  $posted  Raw posted field keys.
	 */
	public static function save( $post_id, $entity, $posted ) {
		$valid  = array_keys( self::maskable( $entity ) );
		$chosen = array();
		foreach ( (array) $posted as $k ) {
			$k = sanitize_key( $k );
			if ( in_array( $k, $valid, true ) ) {
				$chosen[] = $k;
			}
		}
		update_post_meta( (int) $post_id, self::META, array_values( array_unique( $chosen ) ) );
	}

	/** True if the current viewer owns the post (links via author / user_id / org_user_id) or is an admin. */
	public static function is_owner_or_admin( $post_id ) {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		$uid = get_current_user_id();
		if ( ! $uid ) {
			return false;
		}
		$post = get_post( $post_id );
		if ( $post && (int) $post->post_author === $uid ) {
			return true;
		}
		foreach ( array( 'user_id', 'org_user_id' ) as $mk ) {
			if ( (int) get_post_meta( $post_id, $mk, true ) === $uid ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Should the current viewer see this field's value?
	 * Non-masked → always. Masked → logged-in members (and the owner / admin) only.
	 */
	public static function show( $post_id, $key ) {
		if ( ! self::is_masked( $post_id, $key ) ) {
			return true;
		}
		return is_user_logged_in() || self::is_owner_or_admin( $post_id );
	}

	/** Lock placeholder shown to guests in place of a masked value. */
	public static function lock_html( $label = '' ) {
		$label = $label ? $label : __( 'Log in to view', 'shuffles-social-services-jobs' );
		return '<span class="sssj-badge sssj-masked" title="' . esc_attr__( 'The owner shows this only to logged-in members.', 'shuffles-social-services-jobs' ) . '"><span aria-hidden="true">🔒</span> ' . esc_html( $label ) . '</span>';
	}

	/**
	 * Render a "members only" checkbox group for a posting form. Echoes nothing if the
	 * entity has no maskable fields.
	 *
	 * @param string $entity  'worker' | 'org'.
	 * @param int    $post_id Current post (0 for a new one).
	 */
	public static function fields_html( $entity, $post_id = 0 ) {
		$fields = self::maskable( $entity );
		if ( ! $fields ) {
			return;
		}
		$masked = self::masked_keys( $post_id );
		?>
		<fieldset class="sssj-fieldset sssj-fieldset--privacy">
			<legend><?php esc_html_e( 'Privacy, show only to logged-in members', 'shuffles-social-services-jobs' ); ?></legend>
			<p class="description"><?php esc_html_e( 'Tick a field to hide it from logged-out visitors. Signed-in members still see it; everyone else sees a “Log in to view” note. Your profile stays findable either way.', 'shuffles-social-services-jobs' ); ?></p>
			<?php foreach ( $fields as $key => $label ) : ?>
				<label class="sssj-check">
					<input type="checkbox" name="sssj_mask[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $masked, true ) ); ?> />
					<?php echo esc_html( $label ); ?>
				</label>
			<?php endforeach; ?>
		</fieldset>
		<?php
	}
}
