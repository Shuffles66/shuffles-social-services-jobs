<?php
/**
 * Profile Field Registry — admin-defined custom fields on worker / organisation / participant
 * profiles. Each field has a type (text, number, searchable select / multi-select, toggle), can be
 * marked required and "show on banner filters", and its options can be mapped to FluentCRM tags/lists
 * by the CRM Sync layer. Definitions live in the sssj_profile_fields option; values in post meta
 * (_sssj_cf_<key>). Selects render as searchable pill pickers (per the design directive).
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Field_Registry {

	const OPTION   = 'sssj_profile_fields';
	const ENTITIES = array( 'worker', 'org', 'need' );
	const TYPES    = array( 'text', 'textarea', 'number', 'select', 'multiselect', 'toggle' );

	public function register() {
		// Save custom-field values when a profile is saved (before CRM sync at priority 20).
		add_action( 'shuffles_ssj_profile_saved', array( $this, 'save_from_post' ), 10, 3 );
		add_action( 'admin_post_sssj_save_field', array( $this, 'handle_save_field' ) );
		add_action( 'admin_post_sssj_delete_field', array( $this, 'handle_delete_field' ) );
		add_action( 'admin_post_sssj_seed_provider_fields', array( $this, 'handle_seed_provider_fields' ) );
		// Apply "show on banner filters" custom fields to the directory queries.
		add_filter( 'shuffles_ssj_worker_query_args', array( $this, 'filter_worker_args' ), 10, 2 );
		add_filter( 'shuffles_ssj_need_query_args', array( $this, 'filter_need_args' ), 10, 2 );
		// (Organisations use a bespoke query — handled inline in Shortcodes::org_directory via filter_clauses.)
	}

	/* --------------------------------------------------------------- Banner filters */

	/**
	 * meta_query clauses for the active custom-field filters in the request, for one entity.
	 * A single chosen value per field; multi-select fields match within their stored array (LIKE).
	 *
	 * @return array list of meta_query clause arrays (possibly empty)
	 */
	public static function filter_clauses( $entity ) {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$in = ( isset( $_GET['sssj_cf'] ) && is_array( $_GET['sssj_cf'] ) ) ? wp_unslash( $_GET['sssj_cf'] ) : array();
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		if ( ! $in ) {
			return array();
		}
		$clauses = array();
		foreach ( self::banner_filter_fields( $entity ) as $f ) {
			$raw = isset( $in[ $f['key'] ] ) ? ( is_array( $in[ $f['key'] ] ) ? reset( $in[ $f['key'] ] ) : $in[ $f['key'] ] ) : '';
			$val = sanitize_text_field( (string) $raw );
			if ( '' === $val ) {
				continue;
			}
			if ( 'multiselect' === $f['type'] ) {
				$clauses[] = array( 'key' => '_sssj_cf_' . $f['key'], 'value' => '"' . $val . '"', 'compare' => 'LIKE' );
			} else {
				$clauses[] = array( 'key' => '_sssj_cf_' . $f['key'], 'value' => $val, 'compare' => '=' );
			}
		}
		return $clauses;
	}

	/** Merge custom-field filter clauses into a query args array's meta_query (AND). */
	public static function apply_clauses( $args, $entity ) {
		$clauses = self::filter_clauses( $entity );
		if ( ! $clauses ) {
			return $args;
		}
		if ( empty( $args['meta_query'] ) || ! is_array( $args['meta_query'] ) ) {
			$args['meta_query'] = array();
		}
		foreach ( $clauses as $c ) {
			$args['meta_query'][] = $c;
		}
		if ( count( $args['meta_query'] ) > 1 && empty( $args['meta_query']['relation'] ) ) {
			$args['meta_query']['relation'] = 'AND';
		}
		return $args;
	}

	public function filter_worker_args( $args, $extra ) {
		return self::apply_clauses( $args, 'worker' );
	}

	public function filter_need_args( $args, $extra ) {
		return self::apply_clauses( $args, 'need' );
	}

	/** Render the banner-filter custom fields as searchable single-select filter controls. */
	public static function render_banner_filters( $entity ) {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$in = ( isset( $_GET['sssj_cf'] ) && is_array( $_GET['sssj_cf'] ) ) ? wp_unslash( $_GET['sssj_cf'] ) : array();
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		foreach ( self::banner_filter_fields( $entity ) as $f ) {
			$cur = isset( $in[ $f['key'] ] ) ? sanitize_text_field( (string) ( is_array( $in[ $f['key'] ] ) ? reset( $in[ $f['key'] ] ) : $in[ $f['key'] ] ) ) : '';
			/* translators: %s: field label */
			$all = sprintf( __( 'All %s', 'shuffles-social-services-jobs' ), $f['label'] );
			echo '<select class="sssj-select" name="sssj_cf[' . esc_attr( $f['key'] ) . ']" data-placeholder="' . esc_attr( $all ) . '">';
			echo '<option value="">' . esc_html( $all ) . '</option>';
			foreach ( $f['options'] as $opt ) {
				echo '<option value="' . esc_attr( $opt ) . '" ' . selected( $cur, $opt, false ) . '>' . esc_html( $opt ) . '</option>';
			}
			echo '</select>';
		}
	}

	/* --------------------------------------------------------------- Store */

	/** All field defs (normalised). Optionally filter to one entity. */
	public static function fields( $entity = null ) {
		$raw = get_option( self::OPTION, array() );
		$raw = is_array( $raw ) ? $raw : array();
		$out = array();
		foreach ( $raw as $f ) {
			$f = self::normalise( $f );
			if ( ! $f ) {
				continue;
			}
			if ( null === $entity || in_array( $entity, $f['entities'], true ) ) {
				$out[] = $f;
			}
		}
		return $out;
	}

	private static function normalise( $f ) {
		if ( empty( $f['key'] ) || empty( $f['label'] ) ) {
			return null;
		}
		$type = in_array( ( $f['type'] ?? '' ), self::TYPES, true ) ? $f['type'] : 'text';
		$ents = array_values( array_intersect( (array) ( $f['entities'] ?? array() ), self::ENTITIES ) );
		if ( ! $ents ) {
			$ents = array( 'worker' );
		}
		return array(
			'key'      => sanitize_key( $f['key'] ),
			'label'    => (string) $f['label'],
			'entities' => $ents,
			'type'     => $type,
			'options'  => array_values( array_filter( array_map( 'strval', (array) ( $f['options'] ?? array() ) ) ) ),
			'required' => ! empty( $f['required'] ),
			'banner'   => ! empty( $f['banner'] ),
		);
	}

	private static function save_all( $fields ) {
		update_option( self::OPTION, array_values( $fields ), false );
	}

	/** Non-empty custom-field values for display: array of [ label, value-string ]. */
	public static function display_pairs( $entity, $post_id ) {
		$pairs = array();
		foreach ( self::fields( $entity ) as $f ) {
			$val = get_post_meta( $post_id, '_sssj_cf_' . $f['key'], true );
			if ( 'toggle' === $f['type'] ) {
				if ( $val ) {
					$pairs[] = array( $f['label'], __( 'Yes', 'shuffles-social-services-jobs' ) );
				}
				continue;
			}
			if ( is_array( $val ) ) {
				$val = implode( ', ', array_filter( array_map( 'strval', $val ) ) );
			}
			$val = trim( (string) $val );
			if ( '' !== $val ) {
				$pairs[] = array( $f['label'], $val );
			}
		}
		return $pairs;
	}

	/** Fields flagged to appear on directory banner filters (select/multiselect only). */
	public static function banner_filter_fields( $entity ) {
		$out = array();
		foreach ( self::fields( $entity ) as $f ) {
			if ( $f['banner'] && in_array( $f['type'], array( 'select', 'multiselect' ), true ) ) {
				$out[] = $f;
			}
		}
		return $out;
	}

	/* --------------------------------------------------------------- Front-end render + save */

	/** Render this entity's custom fields on a posting form. Pre-fills from $post_id. */
	public static function render( $entity, $post_id = 0 ) {
		$fields = self::fields( $entity );
		if ( ! $fields ) {
			return;
		}
		foreach ( $fields as $f ) {
			$val = $post_id ? get_post_meta( $post_id, '_sssj_cf_' . $f['key'], true ) : '';
			$id  = 'sssj-cf-' . $f['key'];
			echo '<div class="sssj-field">';
			if ( 'toggle' === $f['type'] ) {
				echo '<label><input type="checkbox" name="sssj_cf[' . esc_attr( $f['key'] ) . ']" value="1" ' . checked( '1', (string) $val, false ) . ' /> ' . esc_html( $f['label'] ) . '</label>';
				echo '</div>';
				continue;
			}
			echo '<label for="' . esc_attr( $id ) . '">' . esc_html( $f['label'] ) . ( $f['required'] ? ' *' : '' ) . '</label>';
			switch ( $f['type'] ) {
				case 'textarea':
					echo '<textarea class="sssj-textarea" id="' . esc_attr( $id ) . '" name="sssj_cf[' . esc_attr( $f['key'] ) . ']" rows="3" ' . ( $f['required'] ? 'required' : '' ) . '>' . esc_textarea( (string) $val ) . '</textarea>';
					break;
				case 'number':
					echo '<input class="sssj-input" type="number" id="' . esc_attr( $id ) . '" name="sssj_cf[' . esc_attr( $f['key'] ) . ']" value="' . esc_attr( (string) $val ) . '" ' . ( $f['required'] ? 'required' : '' ) . ' />';
					break;
				case 'select':
					echo '<select class="sssj-select" id="' . esc_attr( $id ) . '" name="sssj_cf[' . esc_attr( $f['key'] ) . ']" data-placeholder="' . esc_attr__( 'Choose…', 'shuffles-social-services-jobs' ) . '">';
					echo '<option value=""></option>';
					foreach ( $f['options'] as $opt ) {
						echo '<option value="' . esc_attr( $opt ) . '" ' . selected( (string) $val, $opt, false ) . '>' . esc_html( $opt ) . '</option>';
					}
					echo '</select>';
					break;
				case 'multiselect':
					$sel = (array) $val;
					echo '<select class="sssj-select" id="' . esc_attr( $id ) . '" name="sssj_cf[' . esc_attr( $f['key'] ) . '][]" multiple data-placeholder="' . esc_attr__( 'Search and add…', 'shuffles-social-services-jobs' ) . '">';
					foreach ( $f['options'] as $opt ) {
						echo '<option value="' . esc_attr( $opt ) . '" ' . ( in_array( $opt, $sel, true ) ? 'selected' : '' ) . '>' . esc_html( $opt ) . '</option>';
					}
					echo '</select>';
					break;
				default: // text
					echo '<input class="sssj-input" type="text" id="' . esc_attr( $id ) . '" name="sssj_cf[' . esc_attr( $f['key'] ) . ']" value="' . esc_attr( (string) $val ) . '" ' . ( $f['required'] ? 'required' : '' ) . ' />';
			}
			echo '</div>';
		}
	}

	/** Persist submitted custom-field values (hooked on shuffles_ssj_profile_saved, priority 10). */
	public function save_from_post( $entity, $post_id, $user_id ) {
		$fields = self::fields( $entity );
		if ( ! $fields ) {
			return;
		}
		$in = isset( $_POST['sssj_cf'] ) && is_array( $_POST['sssj_cf'] ) ? wp_unslash( $_POST['sssj_cf'] ) : array(); // phpcs:ignore WordPress.Security
		foreach ( $fields as $f ) {
			$key = $f['key'];
			$raw = $in[ $key ] ?? null;
			switch ( $f['type'] ) {
				case 'toggle':
					update_post_meta( $post_id, '_sssj_cf_' . $key, $raw ? '1' : '' );
					break;
				case 'multiselect':
					$vals = array_values( array_filter( array_map( 'sanitize_text_field', (array) $raw ) ) );
					update_post_meta( $post_id, '_sssj_cf_' . $key, $vals );
					break;
				case 'number':
					update_post_meta( $post_id, '_sssj_cf_' . $key, ( '' === (string) $raw ) ? '' : (float) $raw );
					break;
				case 'textarea':
					update_post_meta( $post_id, '_sssj_cf_' . $key, sanitize_textarea_field( (string) $raw ) );
					break;
				default:
					update_post_meta( $post_id, '_sssj_cf_' . $key, sanitize_text_field( (string) $raw ) );
			}
		}
	}

	/* --------------------------------------------------------------- Admin handlers */

	public function handle_save_field() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'shuffles-social-services-jobs' ) );
		}
		check_admin_referer( 'sssj_field' );

		$label = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';
		$key   = isset( $_POST['key'] ) && '' !== $_POST['key'] ? sanitize_key( wp_unslash( $_POST['key'] ) ) : sanitize_key( $label );
		if ( '' === $label || '' === $key ) {
			$this->redirect_fields( 'error' );
		}
		$type     = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : 'text';
		$entities = isset( $_POST['entities'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['entities'] ) ) : array();
		$options  = isset( $_POST['options'] ) ? array_filter( array_map( 'trim', explode( "\n", (string) wp_unslash( $_POST['options'] ) ) ) ) : array();

		$field = array(
			'key'      => $key,
			'label'    => $label,
			'entities' => $entities,
			'type'     => $type,
			'options'  => array_values( $options ),
			'required' => ! empty( $_POST['required'] ),
			'banner'   => ! empty( $_POST['banner'] ),
		);
		$f = self::normalise( $field );
		if ( ! $f ) {
			$this->redirect_fields( 'error' );
		}

		// Upsert by key.
		$all     = get_option( self::OPTION, array() );
		$all     = is_array( $all ) ? $all : array();
		$updated = false;
		foreach ( $all as $i => $existing ) {
			if ( isset( $existing['key'] ) && $existing['key'] === $f['key'] ) {
				$all[ $i ] = $f;
				$updated   = true;
				break;
			}
		}
		if ( ! $updated ) {
			$all[] = $f;
		}
		self::save_all( $all );
		$this->redirect_fields( 'saved' );
	}

	public function handle_delete_field() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'shuffles-social-services-jobs' ) );
		}
		check_admin_referer( 'sssj_field_delete' );
		$key = isset( $_POST['key'] ) ? sanitize_key( wp_unslash( $_POST['key'] ) ) : '';
		$all = get_option( self::OPTION, array() );
		$all = is_array( $all ) ? $all : array();
		$all = array_values( array_filter( $all, function ( $f ) use ( $key ) {
			return ! ( isset( $f['key'] ) && $f['key'] === $key );
		} ) );
		self::save_all( $all );
		$this->redirect_fields( 'deleted' );
	}

	/** Recommended provider (organisation) field set — mirrors a typical Shuffles XProfile provider set. */
	public static function provider_field_seed() {
		return array(
			array( 'key' => 'specialisations', 'label' => __( 'Specialisations', 'shuffles-social-services-jobs' ), 'entities' => array( 'org' ), 'type' => 'multiselect', 'options' => array( 'Autism', 'Intellectual disability', 'Psychosocial / mental health', 'Physical disability', 'Acquired brain injury', 'Sensory (vision / hearing)', 'Aged care', 'Complex / high needs' ), 'required' => false, 'banner' => true ),
			array( 'key' => 'services_provided', 'label' => __( 'Services Provided', 'shuffles-social-services-jobs' ), 'entities' => array( 'org' ), 'type' => 'multiselect', 'options' => array( 'Support coordination', 'Specialist support coordination', 'Plan management', 'Daily living / personal care', 'Domestic assistance', 'Community participation & social supports', 'Supported independent living (SIL)', 'Specialist disability accommodation (SDA)', 'Respite / short-term accommodation (STA)', 'Therapeutic supports / allied health (OT, physio, speech, psych)', 'Positive behaviour support', 'Early childhood (ECEI)', 'Employment supports / SLES', 'Transport', 'Home & vehicle modifications', 'Assistive technology', 'Nursing / high-intensity supports', 'Gardening / home maintenance' ), 'required' => false, 'banner' => true ),
			array( 'key' => 'service_delivery', 'label' => __( 'Service delivery', 'shuffles-social-services-jobs' ), 'entities' => array( 'org' ), 'type' => 'select', 'options' => array( 'In person', 'Telehealth', 'Both in person & telehealth' ), 'required' => false, 'banner' => true ),
			array( 'key' => 'ages_supported', 'label' => __( 'Ages supported', 'shuffles-social-services-jobs' ), 'entities' => array( 'org' ), 'type' => 'multiselect', 'options' => array( 'Children (0–12)', 'Teens (13–17)', 'Adults (18–64)', 'Older adults (65+)' ), 'required' => false, 'banner' => true ),
			array( 'key' => 'accepting_clients', 'label' => __( 'Currently accepting new clients', 'shuffles-social-services-jobs' ), 'entities' => array( 'org' ), 'type' => 'toggle', 'options' => array(), 'required' => false, 'banner' => true ),
			array( 'key' => 'accessible_premises', 'label' => __( 'Wheelchair-accessible premises', 'shuffles-social-services-jobs' ), 'entities' => array( 'org' ), 'type' => 'toggle', 'options' => array(), 'required' => false, 'banner' => false ),
			array( 'key' => 'languages_offered', 'label' => __( 'Languages offered (other than English)', 'shuffles-social-services-jobs' ), 'entities' => array( 'org' ), 'type' => 'text', 'options' => array(), 'required' => false, 'banner' => false ),
			array( 'key' => 'years_operating', 'label' => __( 'Years operating', 'shuffles-social-services-jobs' ), 'entities' => array( 'org' ), 'type' => 'number', 'options' => array(), 'required' => false, 'banner' => false ),
			array( 'key' => 'accreditations', 'label' => __( 'Accreditations / memberships', 'shuffles-social-services-jobs' ), 'entities' => array( 'org' ), 'type' => 'text', 'options' => array(), 'required' => false, 'banner' => false ),
			array( 'key' => 'peak_bodies', 'label' => __( 'Peak Bodies', 'shuffles-social-services-jobs' ), 'entities' => array( 'org' ), 'type' => 'multiselect', 'options' => array( 'National Disability Services (NDS)', 'Aged & Community Care Providers Association (ACCPA)', 'Disability Intermediaries Australia (DIA)', 'National Disability Practitioners (NDP)', 'Allied Health Professions Australia (AHPA)', 'Occupational Therapy Australia (OTA)', 'Speech Pathology Australia (SPA)', 'Australian Physiotherapy Association (APA)', 'Australian Psychological Society (APS)', 'Australian Association of Social Workers (AASW)', 'Carers Australia', 'Mental Health Australia', 'Council for Intellectual Disability (CID)', 'Other' ), 'required' => false, 'banner' => true ),
		);
	}

	/** One-shot: add the recommended provider fields that don't already exist (by key). */
	public function handle_seed_provider_fields() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'shuffles-social-services-jobs' ) );
		}
		check_admin_referer( 'sssj_seed_provider_fields' );
		$all  = get_option( self::OPTION, array() );
		$all  = is_array( $all ) ? $all : array();
		$have = array();
		foreach ( $all as $f ) {
			if ( ! empty( $f['key'] ) ) {
				$have[ $f['key'] ] = true;
			}
		}
		foreach ( self::provider_field_seed() as $f ) {
			$f = self::normalise( $f );
			if ( $f && empty( $have[ $f['key'] ] ) ) {
				$all[] = $f;
			}
		}
		self::save_all( $all );
		$this->redirect_fields( 'seeded' );
	}

	private function redirect_fields( $status ) {
		wp_safe_redirect( add_query_arg( array( 'page' => Shuffles_SSJ_Admin::PAGE_SLUG, 'tab' => 'fields', 'sssj_field' => $status ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
