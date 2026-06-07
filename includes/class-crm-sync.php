<?php
/**
 * CRM Sync — keeps a user's FluentCRM tags & lists in step with the selections on their profile.
 *
 * When a worker / organisation / participant profile is saved, each "on" value (a funding source
 * like NDIS, a sector, a culture/language term, or a custom-field option) that an admin has mapped
 * to a FluentCRM tag and/or list is applied to that user's contact; de-selected values are removed.
 * Every change is written to a per-user log (viewable by admins) and, if a mapped tag/list no longer
 * exists in FluentCRM, the gap is logged + surfaced as an admin alert so it can be fixed.
 *
 * Standalone-first: does nothing unless FluentCRM is active AND the master toggle is on. All CRM
 * calls are wrapped so a CRM error can never break a profile save.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_CRM_Sync {

	const MAP_OPTION     = 'sssj_crm_map';      // array of mapping rows: { token, label, tags[], lists[] }
	const MISSING_OPTION = 'sssj_crm_missing';  // map of "tag:ID"/"list:ID" => { label, token, last }

	/** @var Shuffles_SSJ_Settings */
	private $settings;

	public function __construct( $settings ) {
		$this->settings = $settings;
	}

	public function register() {
		add_action( 'shuffles_ssj_profile_saved', array( $this, 'on_profile_saved' ), 20, 3 );
		add_action( 'admin_notices', array( $this, 'missing_notice' ) );
		add_action( 'admin_post_sssj_save_crm_map', array( $this, 'handle_save_map' ) );
		add_action( 'admin_post_sssj_crm_clear_missing', array( $this, 'handle_clear_missing' ) );
		add_action( 'admin_post_sssj_crm_resync', array( $this, 'handle_resync' ) );
		// Per-user log panel on the WP user edit screen.
		add_action( 'show_user_profile', array( $this, 'user_log_panel' ) );
		add_action( 'edit_user_profile', array( $this, 'user_log_panel' ) );
	}

	/* --------------------------------------------------------------- Availability */

	/** FluentCRM present + master toggle on. */
	public function available() {
		return ( defined( 'FLUENTCRM' ) || function_exists( 'FluentCrmApi' ) )
			&& '1' === (string) $this->settings->get( 'crm_sync_enabled', '0' );
	}

	/* --------------------------------------------------------------- Table + log */

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'sssj_crm_log';
	}

	/** Append a log row (best-effort). */
	private function log( $user_id, $email, $entity, $action, $object_type, $object_id, $object_label, $token ) {
		global $wpdb;
		$wpdb->insert( // phpcs:ignore WordPress.DB
			self::table(),
			array(
				'user_id'      => (int) $user_id,
				'email'        => (string) $email,
				'entity'       => (string) $entity,
				'action'       => (string) $action,
				'object_type'  => (string) $object_type,
				'object_id'    => (int) $object_id,
				'object_label' => (string) $object_label,
				'token'        => (string) $token,
				'created_at'   => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s' )
		);
	}

	/**
	 * Read log rows. $args: user_id, action, limit.
	 *
	 * @return array
	 */
	public static function get_logs( $args = array() ) {
		global $wpdb;
		$t      = self::table();
		$where  = array( '1=1' );
		$params = array();
		if ( ! empty( $args['user_id'] ) ) {
			$where[]  = 'user_id = %d';
			$params[] = (int) $args['user_id'];
		}
		if ( ! empty( $args['action'] ) ) {
			$where[]  = 'action = %s';
			$params[] = (string) $args['action'];
		}
		$limit    = isset( $args['limit'] ) ? max( 1, (int) $args['limit'] ) : 100;
		$where    = implode( ' AND ', $where );
		$params[] = $limit; // LIMIT is placeheld too, so the query is always prepared.
		$rows     = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} WHERE {$where} ORDER BY id DESC LIMIT %d", $params ) ); // phpcs:ignore WordPress.DB
		return is_array( $rows ) ? $rows : array();
	}

	/* --------------------------------------------------------------- Mapping store */

	/** Mapping rows: array of { token, label, tags[], lists[] }. */
	public static function get_map() {
		$raw = get_option( self::MAP_OPTION, array() );
		return is_array( $raw ) ? $raw : array();
	}

	/** token => { tags[], lists[] } lookup. */
	private function map_lookup() {
		$lookup = array();
		foreach ( self::get_map() as $row ) {
			if ( empty( $row['token'] ) ) {
				continue;
			}
			$lookup[ $row['token'] ] = array(
				'tags'  => array_map( 'intval', (array) ( $row['tags'] ?? array() ) ),
				'lists' => array_map( 'intval', (array) ( $row['lists'] ?? array() ) ),
			);
		}
		return $lookup;
	}

	/* --------------------------------------------------------------- Sync */

	/**
	 * Fired on shuffles_ssj_profile_saved. Guarded so a CRM error never breaks the save.
	 *
	 * @param string $entity  worker|org|need
	 * @param int    $post_id Profile post.
	 * @param int    $user_id Owner / nominee WP user.
	 */
	public function on_profile_saved( $entity, $post_id, $user_id ) {
		if ( ! $this->available() || ! $user_id ) {
			return;
		}
		try {
			$this->sync( (string) $entity, (int) $post_id, (int) $user_id );
		} catch ( \Throwable $e ) {
			$u = get_userdata( $user_id );
			$this->log( $user_id, $u ? $u->user_email : '', $entity, 'error', '', 0, substr( $e->getMessage(), 0, 180 ), '' );
		}
	}

	/** Compute the post's "on" tokens that appear in the map, then reconcile tags/lists. */
	private function sync( $entity, $post_id, $user_id ) {
		$lookup = $this->map_lookup();
		if ( ! $lookup ) {
			return;
		}
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		$tokens = $this->post_tokens( $entity, $post_id, $lookup );

		$desired_tags  = array();
		$desired_lists = array();
		foreach ( $tokens as $tok ) {
			if ( isset( $lookup[ $tok ] ) ) {
				$desired_tags  = array_merge( $desired_tags, $lookup[ $tok ]['tags'] );
				$desired_lists = array_merge( $desired_lists, $lookup[ $tok ]['lists'] );
			}
		}
		$desired_tags  = array_values( array_unique( array_filter( $desired_tags ) ) );
		$desired_lists = array_values( array_unique( array_filter( $desired_lists ) ) );

		$prev_tags  = array_values( array_filter( array_map( 'intval', (array) get_user_meta( $user_id, '_sssj_crm_tags_' . $entity, true ) ) ) );
		$prev_lists = array_values( array_filter( array_map( 'intval', (array) get_user_meta( $user_id, '_sssj_crm_lists_' . $entity, true ) ) ) );

		$add_tags    = array_diff( $desired_tags, $prev_tags );
		$rm_tags     = array_diff( $prev_tags, $desired_tags );
		$add_lists   = array_diff( $desired_lists, $prev_lists );
		$rm_lists    = array_diff( $prev_lists, $desired_lists );

		if ( ! $add_tags && ! $rm_tags && ! $add_lists && ! $rm_lists ) {
			return; // nothing changed
		}

		$contact = $this->get_or_create_contact( $user );
		if ( ! $contact ) {
			$this->log( $user_id, $user->user_email, $entity, 'no_contact', 'contact', 0, $user->user_email, '' );
			return;
		}

		$applied_tags  = $prev_tags;
		$applied_lists = $prev_lists;

		foreach ( $add_tags as $id ) {
			$label = $this->tag_label( $id );
			if ( null === $label ) {
				$this->record_missing( 'tag', $id, $user_id, $user->user_email, $entity );
				continue;
			}
			$this->safe( function () use ( $contact, $id ) { $contact->attachTags( array( $id ) ); } );
			$applied_tags[] = $id;
			$this->log( $user_id, $user->user_email, $entity, 'attach', 'tag', $id, $label, '' );
		}
		foreach ( $rm_tags as $id ) {
			$this->safe( function () use ( $contact, $id ) { $contact->detachTags( array( $id ) ); } );
			$applied_tags = array_diff( $applied_tags, array( $id ) );
			$this->log( $user_id, $user->user_email, $entity, 'detach', 'tag', $id, $this->tag_label( $id ) ?? ( '#' . $id ), '' );
		}
		foreach ( $add_lists as $id ) {
			$label = $this->list_label( $id );
			if ( null === $label ) {
				$this->record_missing( 'list', $id, $user_id, $user->user_email, $entity );
				continue;
			}
			$this->safe( function () use ( $contact, $id ) { $contact->attachLists( array( $id ) ); } );
			$applied_lists[] = $id;
			$this->log( $user_id, $user->user_email, $entity, 'attach', 'list', $id, $label, '' );
		}
		foreach ( $rm_lists as $id ) {
			$this->safe( function () use ( $contact, $id ) { $contact->detachLists( array( $id ) ); } );
			$applied_lists = array_diff( $applied_lists, array( $id ) );
			$this->log( $user_id, $user->user_email, $entity, 'detach', 'list', $id, $this->list_label( $id ) ?? ( '#' . $id ), '' );
		}

		update_user_meta( $user_id, '_sssj_crm_tags_' . $entity, array_values( array_unique( array_map( 'intval', $applied_tags ) ) ) );
		update_user_meta( $user_id, '_sssj_crm_lists_' . $entity, array_values( array_unique( array_map( 'intval', $applied_lists ) ) ) );
	}

	/** Collect tokens that are "on" for this post and appear in the map. */
	private function post_tokens( $entity, $post_id, $lookup ) {
		$tokens = array();

		// Which taxonomies are referenced by mappings? (e.g. tax:sssjt_funding_source:123)
		$taxes = array();
		foreach ( array_keys( $lookup ) as $tok ) {
			if ( 0 === strpos( $tok, 'tax:' ) ) {
				$parts = explode( ':', $tok );
				if ( isset( $parts[1] ) ) {
					$taxes[ $parts[1] ] = true;
				}
			}
		}
		foreach ( array_keys( $taxes ) as $tax ) {
			$term_ids = wp_get_object_terms( $post_id, $tax, array( 'fields' => 'ids' ) );
			if ( ! is_wp_error( $term_ids ) ) {
				foreach ( $term_ids as $tid ) {
					$tokens[] = 'tax:' . $tax . ':' . (int) $tid;
				}
			}
		}

		// Custom fields on this entity.
		if ( class_exists( 'Shuffles_SSJ_Field_Registry' ) ) {
			foreach ( Shuffles_SSJ_Field_Registry::fields( $entity ) as $f ) {
				$val = get_post_meta( $post_id, '_sssj_cf_' . $f['key'], true );
				if ( 'toggle' === $f['type'] ) {
					if ( $val ) {
						$tokens[] = 'field:' . $f['key'] . ':on';
					}
				} else {
					foreach ( (array) $val as $v ) {
						if ( '' !== (string) $v ) {
							$tokens[] = 'field:' . $f['key'] . ':' . sanitize_title( $v );
						}
					}
				}
			}
		}
		return $tokens;
	}

	private function get_or_create_contact( $user ) {
		if ( ! function_exists( 'FluentCrmApi' ) ) {
			return null;
		}
		$api     = FluentCrmApi( 'contact' );
		$contact = $api->getContact( $user->user_email );
		if ( $contact ) {
			return $contact;
		}
		if ( '1' !== (string) $this->settings->get( 'crm_create_contact', '1' ) ) {
			return null;
		}
		return $api->createOrUpdate(
			array(
				'email'      => $user->user_email,
				'first_name' => $user->first_name,
				'last_name'  => $user->last_name,
				'status'     => 'subscribed',
			)
		);
	}

	private function safe( $fn ) {
		try {
			$fn();
		} catch ( \Throwable $e ) {
			// swallow — logged by caller context where relevant
		}
	}

	/* --------------------------------------------------------------- FluentCRM lookups */

	/** All tags as id => title (for the mapping picker). */
	public static function fluent_tags() {
		$out = array();
		if ( class_exists( '\FluentCrm\App\Models\Tag' ) ) {
			foreach ( \FluentCrm\App\Models\Tag::orderBy( 'title', 'ASC' )->get() as $t ) {
				$out[ (int) $t->id ] = (string) $t->title;
			}
		}
		return $out;
	}

	/** All lists as id => title. */
	public static function fluent_lists() {
		$out = array();
		if ( class_exists( '\FluentCrm\App\Models\Lists' ) ) {
			foreach ( \FluentCrm\App\Models\Lists::orderBy( 'title', 'ASC' )->get() as $l ) {
				$out[ (int) $l->id ] = (string) $l->title;
			}
		}
		return $out;
	}

	private function tag_label( $id ) {
		if ( ! class_exists( '\FluentCrm\App\Models\Tag' ) ) {
			return '#' . (int) $id; // can't verify — assume present
		}
		$t = \FluentCrm\App\Models\Tag::find( (int) $id );
		return $t ? (string) $t->title : null;
	}

	private function list_label( $id ) {
		if ( ! class_exists( '\FluentCrm\App\Models\Lists' ) ) {
			return '#' . (int) $id;
		}
		$l = \FluentCrm\App\Models\Lists::find( (int) $id );
		return $l ? (string) $l->title : null;
	}

	/* --------------------------------------------------------------- Missing-target alerts */

	private function record_missing( $type, $id, $user_id, $email, $entity ) {
		$miss              = get_option( self::MISSING_OPTION, array() );
		$miss              = is_array( $miss ) ? $miss : array();
		$miss[ $type . ':' . $id ] = array(
			'type'  => $type,
			'id'    => (int) $id,
			'last'  => current_time( 'mysql' ),
		);
		update_option( self::MISSING_OPTION, $miss, false );
		$this->log( $user_id, $email, $entity, 'missing', $type, $id, sprintf( '%s #%d no longer exists in FluentCRM', $type, $id ), '' );
	}

	public static function missing() {
		$m = get_option( self::MISSING_OPTION, array() );
		return is_array( $m ) ? $m : array();
	}

	public function missing_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$miss = self::missing();
		if ( ! $miss ) {
			return;
		}
		$url = admin_url( 'admin.php?page=' . Shuffles_SSJ_Admin::PAGE_SLUG . '&tab=crm' );
		echo '<div class="notice notice-warning"><p><strong>' . esc_html__( 'Shuffles Jobs — CRM sync:', 'shuffles-social-services-jobs' ) . '</strong> '
			. esc_html( sprintf( _n( '%d mapped tag/list no longer exists in FluentCRM.', '%d mapped tags/lists no longer exist in FluentCRM.', count( $miss ), 'shuffles-social-services-jobs' ), count( $miss ) ) )
			. ' <a href="' . esc_url( $url ) . '">' . esc_html__( 'Review &amp; fix', 'shuffles-social-services-jobs' ) . '</a></p></div>';
	}

	/* --------------------------------------------------------------- Admin handlers */

	public function handle_save_map() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'shuffles-social-services-jobs' ) );
		}
		check_admin_referer( 'sssj_crm_map' );

		$rows   = array();
		$tokens = isset( $_POST['token'] ) ? (array) wp_unslash( $_POST['token'] ) : array(); // phpcs:ignore WordPress.Security
		$tags   = isset( $_POST['tags'] ) ? (array) wp_unslash( $_POST['tags'] ) : array();    // phpcs:ignore WordPress.Security
		$lists  = isset( $_POST['lists'] ) ? (array) wp_unslash( $_POST['lists'] ) : array();  // phpcs:ignore WordPress.Security

		// Derive a human label for each token from the live list of mappable values.
		$labels = array();
		foreach ( self::mappable_values() as $v ) {
			$labels[ $v['token'] ] = $v['label'];
		}

		$seen = array();
		foreach ( $tokens as $i => $tok ) {
			$tok = sanitize_text_field( $tok );
			if ( '' === $tok || isset( $seen[ $tok ] ) ) {
				continue; // skip blanks + duplicate tokens
			}
			$rowtags  = array_values( array_filter( array_map( 'absint', (array) ( $tags[ $i ] ?? array() ) ) ) );
			$rowlists = array_values( array_filter( array_map( 'absint', (array) ( $lists[ $i ] ?? array() ) ) ) );
			if ( ! $rowtags && ! $rowlists ) {
				continue; // a value with no tag/list isn't a mapping
			}
			$seen[ $tok ] = true;
			$rows[]       = array(
				'token' => $tok,
				'label' => isset( $labels[ $tok ] ) ? $labels[ $tok ] : $tok,
				'tags'  => $rowtags,
				'lists' => $rowlists,
			);
		}
		update_option( self::MAP_OPTION, $rows, false );
		wp_safe_redirect( add_query_arg( array( 'page' => Shuffles_SSJ_Admin::PAGE_SLUG, 'tab' => 'crm', 'sssj_crm' => 'saved' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_clear_missing() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'shuffles-social-services-jobs' ) );
		}
		check_admin_referer( 'sssj_crm_missing' );
		delete_option( self::MISSING_OPTION );
		wp_safe_redirect( add_query_arg( array( 'page' => Shuffles_SSJ_Admin::PAGE_SLUG, 'tab' => 'crm', 'sssj_crm' => 'cleared' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/** Re-sync all of a single user's profiles (clears the managed sets so it re-evaluates from scratch). */
	public function handle_resync() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'shuffles-social-services-jobs' ) );
		}
		check_admin_referer( 'sssj_crm_resync' );
		$uid = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		if ( $uid ) {
			foreach ( array( 'worker', 'org', 'need' ) as $entity ) {
				$posts = get_posts( array(
					'post_type'      => 'sssj_' . $entity,
					'post_status'    => 'any',
					'posts_per_page' => 5,
					'fields'         => 'ids',
					'no_found_rows'  => true,
					'meta_query'     => array( array( 'key' => $entity . '_user_id', 'value' => $uid ) ),
				) );
				// 'need' uses nominee_user_id; 'worker' worker_user_id; 'org' org_user_id.
				if ( 'need' === $entity ) {
					$posts = get_posts( array( 'post_type' => 'sssj_need', 'post_status' => 'any', 'posts_per_page' => 5, 'fields' => 'ids', 'no_found_rows' => true, 'meta_query' => array( array( 'key' => 'nominee_user_id', 'value' => $uid ) ) ) );
				}
				foreach ( (array) $posts as $pid ) {
					$this->on_profile_saved( $entity, $pid, $uid );
				}
			}
		}
		wp_safe_redirect( add_query_arg( array( 'sssj_crm' => 'resynced' ), wp_get_referer() ? wp_get_referer() : admin_url() ) );
		exit;
	}

	/* --------------------------------------------------------------- Per-user log panel */

	public function user_log_panel( $user ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$rows = self::get_logs( array( 'user_id' => $user->ID, 'limit' => 30 ) );
		echo '<h2>' . esc_html__( 'Shuffles Jobs — CRM sync log', 'shuffles-social-services-jobs' ) . '</h2>';
		if ( ! $this->available() ) {
			echo '<p class="description">' . esc_html__( 'CRM sync is off or FluentCRM is not active.', 'shuffles-social-services-jobs' ) . '</p>';
		}
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin:6px 0">';
		echo '<input type="hidden" name="action" value="sssj_crm_resync" /><input type="hidden" name="user_id" value="' . esc_attr( $user->ID ) . '" />';
		wp_nonce_field( 'sssj_crm_resync' );
		echo '<button class="button">' . esc_html__( 'Re-sync this user now', 'shuffles-social-services-jobs' ) . '</button></form>';
		if ( ! $rows ) {
			echo '<p class="description">' . esc_html__( 'No CRM sync activity yet.', 'shuffles-social-services-jobs' ) . '</p>';
			return;
		}
		echo '<table class="widefat striped" style="max-width:760px"><thead><tr><th>' . esc_html__( 'When', 'shuffles-social-services-jobs' ) . '</th><th>' . esc_html__( 'Action', 'shuffles-social-services-jobs' ) . '</th><th>' . esc_html__( 'Tag / List', 'shuffles-social-services-jobs' ) . '</th><th>' . esc_html__( 'Profile', 'shuffles-social-services-jobs' ) . '</th></tr></thead><tbody>';
		foreach ( $rows as $r ) {
			echo '<tr><td>' . esc_html( $r->created_at ) . '</td><td>' . esc_html( $r->action ) . '</td><td>' . esc_html( $r->object_type . ': ' . $r->object_label ) . '</td><td>' . esc_html( $r->entity ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}

	/* --------------------------------------------------------------- Mappable values */

	/**
	 * Values an admin can map to a tag/list: taxonomy terms + custom-field options.
	 *
	 * @return array of [ token, label ]
	 */
	public static function mappable_values() {
		$out = array();
		$taxes = array(
			'sssjt_funding_source' => __( 'Funding', 'shuffles-social-services-jobs' ),
			'sssjt_category'       => __( 'Sector/Service', 'shuffles-social-services-jobs' ),
			'sssjt_support_category' => __( 'Support type', 'shuffles-social-services-jobs' ),
			'sssjt_culture'        => __( 'Culture', 'shuffles-social-services-jobs' ),
			'sssjt_language'       => __( 'Language', 'shuffles-social-services-jobs' ),
		);
		foreach ( $taxes as $tax => $group ) {
			$terms = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false ) );
			if ( is_wp_error( $terms ) ) {
				continue;
			}
			foreach ( $terms as $t ) {
				$out[] = array( 'token' => 'tax:' . $tax . ':' . (int) $t->term_id, 'label' => $group . ': ' . $t->name );
			}
		}
		if ( class_exists( 'Shuffles_SSJ_Field_Registry' ) ) {
			foreach ( Shuffles_SSJ_Field_Registry::fields() as $f ) {
				if ( 'toggle' === $f['type'] ) {
					$out[] = array( 'token' => 'field:' . $f['key'] . ':on', 'label' => $f['label'] . ': ' . __( 'On', 'shuffles-social-services-jobs' ) );
				} elseif ( in_array( $f['type'], array( 'select', 'multiselect' ), true ) ) {
					foreach ( (array) $f['options'] as $opt ) {
						$out[] = array( 'token' => 'field:' . $f['key'] . ':' . sanitize_title( $opt ), 'label' => $f['label'] . ': ' . $opt );
					}
				}
			}
		}
		return $out;
	}
}
