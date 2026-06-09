<?php
/**
 * Whole-site machine translation (Option 1), with a seam to hand off to TranslatePress later.
 *
 * The CALD language picker calls /sssj/v1/translate with the visible page strings; this class translates
 * them via DeepL and caches each string (so a given sentence is translated once and reused for everyone,
 * keeping cost low). Off until a DeepL key is set (Settings, CALD & Access). If TranslatePress is active,
 * provider() returns 'translatepress' and the client defers to it instead of machine-translating.
 *
 * Privacy: only on-page text the visitor can already see is sent for translation; participant-protected
 * data is pseudonymous on the page already, so nothing private is exposed beyond what is rendered.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Translate {

	/** Our CALD language codes -> DeepL target codes. Languages DeepL cannot do are omitted (e.g. Punjabi). */
	public static function deepl_map() {
		return array(
			'ar' => 'AR', 'zh' => 'ZH', 'el' => 'EL', 'it' => 'IT', 'id' => 'ID',
			'de' => 'DE', 'fr' => 'FR', 'es' => 'ES', 'pt' => 'PT-PT', 'pl' => 'PL',
			'nl' => 'NL', 'ja' => 'JA', 'ko' => 'KO', 'ru' => 'RU', 'tr' => 'TR', 'uk' => 'UK',
		);
	}

	public static function register() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function deepl_key() {
		$o = get_option( 'shuffles_ssj_settings', array() );
		return is_array( $o ) && ! empty( $o['deepl_api_key'] ) ? trim( (string) $o['deepl_api_key'] ) : '';
	}

	/** 'translatepress' (future hand-off), 'machine' (DeepL configured), or 'none'. */
	public static function provider() {
		if ( defined( 'TRP_PLUGIN_VERSION' ) || class_exists( 'TRP_Translate_Press' ) ) {
			return 'translatepress';
		}
		return '' !== self::deepl_key() ? 'machine' : 'none';
	}

	public static function supported( $lang ) {
		$map = self::deepl_map();
		return isset( $map[ $lang ] );
	}

	/** Supported CALD codes for the client (so it only attempts languages DeepL can do). */
	public static function supported_codes() {
		return array_keys( self::deepl_map() );
	}

	public static function routes() {
		register_rest_route( 'sssj/v1', '/translate', array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'callback'            => array( __CLASS__, 'rest_translate' ),
		) );
	}

	public static function rest_translate( WP_REST_Request $req ) {
		if ( 'machine' !== self::provider() ) {
			return new WP_REST_Response( array( 'map' => array() ), 200 );
		}
		$lang = sanitize_key( (string) $req->get_param( 'lang' ) );
		$map  = self::deepl_map();
		if ( ! isset( $map[ $lang ] ) ) {
			return new WP_REST_Response( array( 'map' => array() ), 200 );
		}
		$texts = (array) $req->get_param( 'texts' );
		$texts = array_slice( $texts, 0, 200 ); // cap per request
		$out   = array();
		$todo  = array(); // cache-key => original text

		foreach ( $texts as $t ) {
			$t = trim( (string) $t );
			if ( '' === $t || mb_strlen( $t ) > 1500 ) {
				continue;
			}
			$ck = 'sssj_tr_' . $lang . '_' . md5( $t );
			$c  = get_transient( $ck );
			if ( false !== $c ) {
				$out[ $t ] = $c;
			} else {
				$todo[ $ck ] = $t;
			}
		}

		if ( $todo ) {
			$keys = array_keys( $todo );
			$vals = array_values( $todo );
			$tr   = self::deepl_batch( $vals, $map[ $lang ] );
			foreach ( $vals as $i => $orig ) {
				$translated = isset( $tr[ $i ] ) ? (string) $tr[ $i ] : '';
				if ( '' !== $translated ) {
					$out[ $orig ] = $translated;
					set_transient( $keys[ $i ], $translated, MONTH_IN_SECONDS );
				}
			}
		}

		return new WP_REST_Response( array( 'map' => $out ), 200 );
	}

	/** Translate a batch of strings via DeepL. Returns indexed array (original kept on failure). */
	private static function deepl_batch( $texts, $target ) {
		$key = self::deepl_key();
		if ( '' === $key ) {
			return array();
		}
		// Free keys end in ":fx" and use the free endpoint.
		$endpoint = ( ':fx' === substr( $key, -3 ) ) ? 'https://api-free.deepl.com/v2/translate' : 'https://api.deepl.com/v2/translate';
		$resp     = wp_remote_post( $endpoint, array(
			'timeout' => 30,
			'headers' => array( 'Authorization' => 'DeepL-Auth-Key ' . $key, 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode( array( 'text' => array_values( $texts ), 'target_lang' => $target, 'source_lang' => 'EN' ) ),
		) );
		if ( is_wp_error( $resp ) || 200 !== (int) wp_remote_retrieve_response_code( $resp ) ) {
			return array(); // fail soft: callers keep the original English
		}
		$b   = json_decode( (string) wp_remote_retrieve_body( $resp ), true );
		$out = array();
		foreach ( array_values( $texts ) as $i => $orig ) {
			$out[ $i ] = isset( $b['translations'][ $i ]['text'] ) ? (string) $b['translations'][ $i ]['text'] : '';
		}
		return $out;
	}
}
