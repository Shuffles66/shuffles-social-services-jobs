<?php
/**
 * Stock photos via Unsplash (Australian-tuned), used for the demo tour banners and demo-post imagery.
 *
 * Off until an admin adds an Unsplash Access Key (Settings, General). Images are fetched ON ADMIN ACTION
 * (the "Load demo photos" button), downloaded into the Media Library once, and reused, so public page
 * loads make no API calls and we stay well inside Unsplash's rate limits. Photographer attribution is
 * stored and shown, per the Unsplash API guidelines.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Stock {

	const DEMO_OPT = 'sssj_demo_images'; // map: persona key => { id, credit_name, credit_url }
	const UTM      = '?utm_source=just_tasks&utm_medium=referral';

	public static function register() {
		add_action( 'admin_post_sssj_load_demo_photos', array( __CLASS__, 'handle_load_demo_photos' ) );
	}

	public static function key() {
		$o = get_option( 'shuffles_ssj_settings', array() );
		return is_array( $o ) && ! empty( $o['unsplash_access_key'] ) ? trim( (string) $o['unsplash_access_key'] ) : '';
	}
	public static function enabled() {
		return '' !== self::key();
	}

	/** Bias a query toward Australian imagery. */
	private static function au_query( $query ) {
		$q = trim( str_replace( ',', ' ', (string) $query ) );
		if ( false === stripos( $q, 'australia' ) ) {
			$q .= ' australia';
		}
		return $q;
	}

	/**
	 * Fetch one matching photo. Returns [ url, thumb, credit_name, credit_url, download_location ] or WP_Error.
	 */
	public static function fetch_one( $query ) {
		$key = self::key();
		if ( '' === $key ) {
			return new WP_Error( 'sssj_no_key', __( 'Add an Unsplash Access Key first (Settings, General).', 'shuffles-social-services-jobs' ) );
		}
		$url  = add_query_arg(
			array( 'query' => rawurlencode( self::au_query( $query ) ), 'orientation' => 'landscape', 'content_filter' => 'high' ),
			'https://api.unsplash.com/photos/random'
		);
		$resp = wp_remote_get( $url, array(
			'timeout' => 25,
			'headers' => array( 'Authorization' => 'Client-ID ' . $key, 'Accept-Version' => 'v1' ),
		) );
		if ( is_wp_error( $resp ) ) {
			return $resp;
		}
		$code = (int) wp_remote_retrieve_response_code( $resp );
		$b    = json_decode( (string) wp_remote_retrieve_body( $resp ), true );
		if ( 200 !== $code || empty( $b['urls']['regular'] ) ) {
			$msg = isset( $b['errors'][0] ) ? $b['errors'][0] : ( 'HTTP ' . $code );
			return new WP_Error( 'sssj_unsplash', is_string( $msg ) ? $msg : 'Unsplash error' );
		}
		return array(
			'url'               => $b['urls']['regular'] . '&fm=jpg&w=1400&q=80',
			'thumb'             => isset( $b['urls']['small'] ) ? $b['urls']['small'] : $b['urls']['regular'],
			'credit_name'       => isset( $b['user']['name'] ) ? (string) $b['user']['name'] : 'Unsplash',
			'credit_url'        => isset( $b['user']['links']['html'] ) ? (string) $b['user']['links']['html'] : 'https://unsplash.com',
			'download_location' => isset( $b['links']['download_location'] ) ? (string) $b['links']['download_location'] : '',
		);
	}

	/** Unsplash guideline: ping the download endpoint when a photo is actually used. Fire and forget. */
	private static function trigger_download( $download_location ) {
		if ( '' === (string) $download_location ) {
			return;
		}
		wp_remote_get( add_query_arg( 'client_id', self::key(), $download_location ), array( 'timeout' => 8, 'blocking' => false ) );
	}

	/** Download a photo URL into the Media Library. Returns attachment id or WP_Error. */
	private static function sideload( $photo_url, $desc, $post_id = 0 ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$tmp = download_url( $photo_url, 30 );
		if ( is_wp_error( $tmp ) ) {
			return $tmp;
		}
		$file = array( 'name' => 'sssj-stock-' . substr( md5( $photo_url ), 0, 10 ) . '.jpg', 'tmp_name' => $tmp );
		$id   = media_handle_sideload( $file, (int) $post_id, $desc );
		if ( is_wp_error( $id ) ) {
			if ( file_exists( $tmp ) ) {
				wp_delete_file( $tmp );
			}
			return $id;
		}
		return (int) $id;
	}

	/** Fetch a photo for a query and store it as $post_id's featured image (with attribution meta). */
	public static function attach_to_post( $post_id, $query ) {
		$post_id = (int) $post_id;
		if ( ! $post_id || has_post_thumbnail( $post_id ) ) {
			return false;
		}
		$p = self::fetch_one( $query );
		if ( is_wp_error( $p ) ) {
			return false;
		}
		$att = self::sideload( $p['url'], get_the_title( $post_id ), $post_id );
		if ( is_wp_error( $att ) || ! $att ) {
			return false;
		}
		set_post_thumbnail( $post_id, $att );
		update_post_meta( $att, '_sssj_stock_credit', array( 'name' => $p['credit_name'], 'url' => $p['credit_url'] ) );
		update_post_meta( $att, '_sssj_demo', 1 );
		self::trigger_download( $p['download_location'] );
		return $att;
	}

	/** Stored demo image for a persona key: [ url, credit_name, credit_url ] or null. */
	public static function demo_image( $persona_key ) {
		$opt = get_option( self::DEMO_OPT, array() );
		if ( empty( $opt[ $persona_key ]['id'] ) ) {
			return null;
		}
		$id  = (int) $opt[ $persona_key ]['id'];
		$src = wp_get_attachment_image_url( $id, 'large' );
		if ( ! $src ) {
			return null;
		}
		return array(
			'url'         => $src,
			'credit_name' => isset( $opt[ $persona_key ]['credit_name'] ) ? (string) $opt[ $persona_key ]['credit_name'] : 'Unsplash',
			'credit_url'  => isset( $opt[ $persona_key ]['credit_url'] ) ? (string) $opt[ $persona_key ]['credit_url'] . self::UTM : 'https://unsplash.com' . self::UTM,
		);
	}

	/**
	 * Admin action: fetch one Australian photo per demo-tour persona (stored for the tour), and attach a
	 * featured image to each existing demo post. Idempotent (skips personas/posts that already have one).
	 */
	public static function load_demo_photos() {
		$r = array( 'personas' => 0, 'posts' => 0, 'errors' => array() );
		if ( ! self::enabled() ) {
			$r['errors'][] = 'No Unsplash key set.';
			return $r;
		}

		// 1) Persona banners for the demo tour.
		$opt = get_option( self::DEMO_OPT, array() );
		if ( ! is_array( $opt ) ) {
			$opt = array();
		}
		if ( class_exists( 'Shuffles_SSJ_Demo_Tour' ) ) {
			foreach ( Shuffles_SSJ_Demo_Tour::personas() as $key => $p ) {
				if ( ! empty( $opt[ $key ]['id'] ) && get_post( (int) $opt[ $key ]['id'] ) ) {
					continue;
				}
				$photo = self::fetch_one( isset( $p['query'] ) ? $p['query'] : $p['role'] );
				if ( is_wp_error( $photo ) ) {
					$r['errors'][] = $key . ': ' . $photo->get_error_message();
					continue;
				}
				$att = self::sideload( $photo['url'], $p['name'] . ' (demo)' );
				if ( is_wp_error( $att ) || ! $att ) {
					$r['errors'][] = $key . ': image could not be saved.';
					continue;
				}
				update_post_meta( $att, '_sssj_demo', 1 );
				self::trigger_download( $photo['download_location'] );
				$opt[ $key ] = array( 'id' => $att, 'credit_name' => $photo['credit_name'], 'credit_url' => $photo['credit_url'] );
				$r['personas']++;
			}
			update_option( self::DEMO_OPT, $opt, false );
		}

		// 2) Featured images on existing demo posts, by type.
		$type_query = array(
			'sssj_job'    => 'support work team',
			'sssj_worker' => 'support worker carer',
			'sssj_org'    => 'care team office',
			'sssj_need'   => 'community support outdoors',
		);
		foreach ( $type_query as $pt => $q ) {
			$ids = get_posts( array( 'post_type' => $pt, 'post_status' => 'any', 'posts_per_page' => 20, 'fields' => 'ids', 'meta_key' => '_sssj_demo' ) );
			foreach ( $ids as $pid ) {
				if ( has_post_thumbnail( (int) $pid ) ) {
					continue;
				}
				if ( self::attach_to_post( (int) $pid, $q ) ) {
					$r['posts']++;
				}
			}
		}
		return $r;
	}

	public static function handle_load_demo_photos() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'shuffles-social-services-jobs' ) );
		}
		check_admin_referer( 'sssj_load_demo_photos' );
		$res = self::load_demo_photos();
		set_transient( 'sssj_demo_photos_result', $res, 120 );
		wp_safe_redirect( add_query_arg( array( 'page' => 'shuffles-ssj', 'tab' => 'demo', 'sssj_photos' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
