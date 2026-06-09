<?php
/**
 * AI Profile Card generator (in-plugin port of shuffles-profile-card).
 *
 * A member turns their worker profile into a styled, shareable PNG: OpenAI makes a TEXT-FREE artistic
 * background for the chosen style, then the browser composites the member's LOCATION + SERVICES (top)
 * and name/tagline (bottom) on a canvas, so it runs on managed hosting (no Puppeteer/Node/exec).
 *
 * Off by default: needs an OpenAI key (Settings → AI Profile Card) which bills per image, so a monthly
 * per-member limit applies (filterable for paid members). Saves to the member's media library. Never
 * uses participant data; it builds only from the member's own worker profile.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Profile_Card {

	public static function register() {
		add_action( 'rest_api_init', array( __CLASS__, 'rest_routes' ) );
		add_action( 'admin_post_sssj_card_fetch_models', array( __CLASS__, 'handle_fetch_models' ) );
	}

	/* --------------------------------------------------------------- config */

	public static function enabled() {
		$o = get_option( 'shuffles_ssj_settings', array() );
		$on = is_array( $o ) && ! empty( $o['profile_card_enabled'] );
		return $on && '' !== trim( (string) self::api_key() );
	}

	public static function api_key() {
		$o = get_option( 'shuffles_ssj_settings', array() );
		return is_array( $o ) && ! empty( $o['openai_api_key'] ) ? (string) $o['openai_api_key'] : '';
	}

	public static function model() {
		$o = get_option( 'shuffles_ssj_settings', array() );
		return is_array( $o ) && ! empty( $o['openai_image_model'] ) ? (string) $o['openai_image_model'] : 'gpt-image-1';
	}

	/* --------------------------------------------------------------- model picker */

	/**
	 * Dropdown choices (value => label). Curated image-capable models, plus any fetched live from the
	 * account, plus the currently-saved value (so a custom one is never lost). Only models the images
	 * endpoint accepts are offered.
	 */
	public static function model_choices() {
		$curated = array( 'gpt-image-1', 'dall-e-3', 'dall-e-2' );
		$cached  = get_option( 'sssj_card_models', array() );
		if ( ! is_array( $cached ) ) {
			$cached = array();
		}
		$all = array_values( array_unique( array_merge( $curated, $cached ) ) );
		$current = self::model();
		if ( '' !== $current && ! in_array( $current, $all, true ) ) {
			$all[] = $current;
		}
		$labels = array(
			'gpt-image-1' => __( 'gpt-image-1 (recommended, highest quality)', 'shuffles-social-services-jobs' ),
			'dall-e-3'    => __( 'dall-e-3 (older, good quality)', 'shuffles-social-services-jobs' ),
			'dall-e-2'    => __( 'dall-e-2 (older, lower cost)', 'shuffles-social-services-jobs' ),
		);
		$out = array();
		foreach ( $all as $id ) {
			$out[ $id ] = isset( $labels[ $id ] ) ? $labels[ $id ] : $id;
		}
		return $out;
	}

	/**
	 * Ask OpenAI which models this account can use, and keep the image-generation ones.
	 *
	 * @return array|WP_Error List of model ids, or an error.
	 */
	public static function fetch_models() {
		$key = self::api_key();
		if ( '' === trim( $key ) ) {
			return new WP_Error( 'sssj_no_key', __( 'Add and save your image-engine key first, then refresh.', 'shuffles-social-services-jobs' ) );
		}
		$resp = wp_remote_get( 'https://api.openai.com/v1/models', array(
			'timeout' => 30,
			'headers' => array( 'Authorization' => 'Bearer ' . $key ),
		) );
		if ( is_wp_error( $resp ) ) {
			return $resp;
		}
		$code = (int) wp_remote_retrieve_response_code( $resp );
		$body = json_decode( (string) wp_remote_retrieve_body( $resp ), true );
		if ( 200 !== $code || empty( $body['data'] ) || ! is_array( $body['data'] ) ) {
			return new WP_Error( 'sssj_models_http', isset( $body['error']['message'] ) ? $body['error']['message'] : ( 'HTTP ' . $code ) );
		}
		$models = array();
		foreach ( $body['data'] as $m ) {
			$id = isset( $m['id'] ) ? (string) $m['id'] : '';
			if ( '' === $id ) {
				continue;
			}
			// Image-generation-capable model ids on the images endpoint.
			if ( false !== strpos( $id, 'gpt-image' ) || 0 === strpos( $id, 'dall-e' ) ) {
				$models[] = $id;
			}
		}
		$models = array_values( array_unique( $models ) );
		sort( $models );
		update_option( 'sssj_card_models', $models, false );
		return $models;
	}

	/** Admin-post: "Refresh available models". */
	public static function handle_fetch_models() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'shuffles-social-services-jobs' ) );
		}
		check_admin_referer( 'sssj_card_fetch_models' );
		$res    = self::fetch_models();
		$status = is_wp_error( $res ) ? 'fail' : 'ok';
		if ( is_wp_error( $res ) ) {
			set_transient( 'sssj_card_models_err', $res->get_error_message(), 60 );
		}
		wp_safe_redirect( add_query_arg(
			array(
				'page'        => 'shuffles-ssj',
				'tab'         => 'profilecard',
				'sssj_models' => $status,
				'n'           => is_wp_error( $res ) ? 0 : count( $res ),
			),
			admin_url( 'admin.php' )
		) );
		exit;
	}

	/* --------------------------------------------------------------- styles */

	public static function styles() {
		return array(
			'art_nouveau'        => array( 'label' => __( 'Art Nouveau', 'shuffles-social-services-jobs' ), 'emoji' => '🌿',
				'prompt' => 'Elegant Art Nouveau decorative border and background. Flowing organic lines, stylised floral motifs, lily pads, curved botanical forms, sinuous tendrils. Rich jewel-toned palette of deep teal, gold and ivory. Fine linework in the style of Alphonse Mucha. Ornate corner flourishes. Subtle iridescent sheen.',
				'negative' => 'No cartoon elements. No modern typography. No photographs. No sharp geometric angles.',
				'scheme' => array( 'overlay' => 'rgba(0,0,0,0.35)', 'pill_bg' => 'rgba(20,80,60,0.85)', 'pill_fg' => '#f5e6c8', 'tag_bg' => 'rgba(20,80,60,0.7)', 'tag_fg' => '#f5e6c8', 'tag_border' => '', 'name_fg' => '#f5e6c8', 'tagline_fg' => '#d4c4a0', 'font' => 'Georgia, serif', 'radius' => 6 ) ),
			'antique_engraving'  => array( 'label' => __( 'Antique Engraving', 'shuffles-social-services-jobs' ), 'emoji' => '📜',
				'prompt' => 'Victorian-era copperplate engraving style certificate border and background. Fine crosshatching, stippling, ornate scrollwork, classical medallion motifs, sepia and aged-parchment tones. Aged paper texture. Formal symmetrical layout. Decorative corner cartouches.',
				'negative' => 'No digital effects. No colour photography. No neon or bright colours. No modern design elements.',
				'scheme' => array( 'overlay' => 'rgba(60,40,10,0.45)', 'pill_bg' => 'rgba(80,50,10,0.8)', 'pill_fg' => '#f0e0b0', 'tag_bg' => 'rgba(80,50,10,0.65)', 'tag_fg' => '#f0e0b0', 'tag_border' => '#b09060', 'name_fg' => '#f0e0b0', 'tagline_fg' => '#d4bc88', 'font' => '"Palatino Linotype", Palatino, serif', 'radius' => 6 ) ),
			'bauhaus'            => array( 'label' => __( 'Bauhaus', 'shuffles-social-services-jobs' ), 'emoji' => '⬛',
				'prompt' => 'Bauhaus design aesthetic. Bold geometric shapes, primary colours (red, yellow, blue, black, white), strong grid structure, minimal ornamentation, clean flat areas, geometric border elements. High contrast. Functional modernist beauty.',
				'negative' => 'No flourishes. No gradients. No organic curves. No photographic elements.',
				'scheme' => array( 'overlay' => 'rgba(0,0,0,0.55)', 'pill_bg' => '#e63322', 'pill_fg' => '#ffffff', 'tag_bg' => '#1a1a1a', 'tag_fg' => '#ffffff', 'tag_border' => '', 'name_fg' => '#ffffff', 'tagline_fg' => '#f5c800', 'font' => 'Arial, sans-serif', 'radius' => 0 ) ),
			'mid_century'        => array( 'label' => __( 'Mid-Century Modern', 'shuffles-social-services-jobs' ), 'emoji' => '🔶',
				'prompt' => 'Mid-century modern graphic design background. Atomic age motifs, starburst patterns, boomerang shapes, warm mustard, burnt orange, avocado green and cream palette. Flat illustration style, slight grain texture.',
				'negative' => 'No Victorian ornamentation. No photography. No dark moody tones.',
				'scheme' => array( 'overlay' => 'rgba(30,20,0,0.4)', 'pill_bg' => '#c44b1e', 'pill_fg' => '#fff8e8', 'tag_bg' => '#4a7c3f', 'tag_fg' => '#fff8e8', 'tag_border' => '', 'name_fg' => '#fff8e8', 'tagline_fg' => '#f5d080', 'font' => '"Trebuchet MS", sans-serif', 'radius' => 20 ) ),
			'japanese_woodblock' => array( 'label' => __( 'Japanese Woodblock', 'shuffles-social-services-jobs' ), 'emoji' => '🌊',
				'prompt' => 'Japanese ukiyo-e woodblock print inspired background. Wave patterns, cloud formations, stylised nature motifs. Limited flat colour palette of indigo, vermillion, black and cream. Bold outlines, no gradients. Visible woodgrain texture.',
				'negative' => 'No Western design elements. No photographs. No excessive detail.',
				'scheme' => array( 'overlay' => 'rgba(15,25,50,0.5)', 'pill_bg' => 'rgba(160,30,30,0.85)', 'pill_fg' => '#f5f0e0', 'tag_bg' => 'rgba(20,50,100,0.75)', 'tag_fg' => '#f5f0e0', 'tag_border' => 'rgba(255,255,255,0.3)', 'name_fg' => '#f5f0e0', 'tagline_fg' => '#c8bfa0', 'font' => 'Georgia, serif', 'radius' => 6 ) ),
			'art_deco'           => array( 'label' => __( 'Art Deco', 'shuffles-social-services-jobs' ), 'emoji' => '✨',
				'prompt' => 'Art Deco luxury poster background. Geometric sunburst patterns, chevrons, stepped pyramidal forms, gold and black palette with deep navy accents. Metallic sheen. Symmetrical composition.',
				'negative' => 'No organic curves. No pastel colours. No modern minimal aesthetics.',
				'scheme' => array( 'overlay' => 'rgba(0,10,30,0.5)', 'pill_bg' => 'rgba(180,140,20,0.9)', 'pill_fg' => '#0a0a1a', 'tag_bg' => 'rgba(0,0,0,0)', 'tag_fg' => '#d4a830', 'tag_border' => '#d4a830', 'name_fg' => '#d4a830', 'tagline_fg' => '#a08020', 'font' => '"Palatino Linotype", serif', 'radius' => 4 ) ),
			'folk_art'           => array( 'label' => __( 'Folk Art', 'shuffles-social-services-jobs' ), 'emoji' => '🎨',
				'prompt' => 'Contemporary folk art inspired background. Hand-crafted botanical and nature motifs, warm earthy ochres, terracotta, dusty blue and cream. Flat folk illustration style with cultural warmth.',
				'negative' => 'No classical European elements. No dark moody tones.',
				'scheme' => array( 'overlay' => 'rgba(40,20,0,0.38)', 'pill_bg' => 'rgba(150,70,20,0.85)', 'pill_fg' => '#fdf5e6', 'tag_bg' => 'rgba(80,100,40,0.75)', 'tag_fg' => '#fdf5e6', 'tag_border' => '', 'name_fg' => '#fdf5e6', 'tagline_fg' => '#e8d5b0', 'font' => 'Georgia, serif', 'radius' => 6 ) ),
			'watercolour'        => array( 'label' => __( 'Soft Watercolour', 'shuffles-social-services-jobs' ), 'emoji' => '💧',
				'prompt' => 'Soft watercolour wash background. Gentle blended pastel pigments, blooming edges, paper grain, calm and caring mood. Cool blues, soft greens, blush and cream, lots of light open space.',
				'negative' => 'No hard edges. No photographs. No dark heavy colours. No text.',
				'scheme' => array( 'overlay' => 'rgba(255,255,255,0.45)', 'pill_bg' => '#5b7c99', 'pill_fg' => '#ffffff', 'tag_bg' => '#eaf0f5', 'tag_fg' => '#3a566e', 'tag_border' => '#9bb3c7', 'name_fg' => '#2a3b4d', 'tagline_fg' => '#5a6b7d', 'font' => 'Georgia, serif', 'radius' => 14 ) ),
			'botanical'          => array( 'label' => __( 'Vintage Botanical', 'shuffles-social-services-jobs' ), 'emoji' => '🌸',
				'prompt' => 'Vintage botanical illustration background. Detailed pressed leaves, ferns, eucalyptus and wildflowers in a fine engraved-print style, arranged as a frame around clean cream space. Muted sage, olive, soft rose and parchment.',
				'negative' => 'No cartoon style. No photographs. No neon colours. No text.',
				'scheme' => array( 'overlay' => 'rgba(245,240,225,0.4)', 'pill_bg' => 'rgba(60,90,50,0.9)', 'pill_fg' => '#f6f1e0', 'tag_bg' => 'rgba(255,255,255,0.7)', 'tag_fg' => '#3c5a32', 'tag_border' => '#b7c4a8', 'name_fg' => '#33422c', 'tagline_fg' => '#5b6b50', 'font' => 'Georgia, serif', 'radius' => 8 ) ),
			'memphis'            => array( 'label' => __( 'Memphis Pop', 'shuffles-social-services-jobs' ), 'emoji' => '🔺',
				'prompt' => 'Playful 1980s Memphis design background. Scattered confetti shapes, squiggles, dots, zigzags and pastel-on-bright geometric confetti. Energetic, friendly, upbeat. Keep the centre and top open.',
				'negative' => 'No realism. No photographs. No muted or dark tones. No text.',
				'scheme' => array( 'overlay' => 'rgba(0,0,0,0.45)', 'pill_bg' => '#ff5d8f', 'pill_fg' => '#ffffff', 'tag_bg' => '#27c4c4', 'tag_fg' => '#04282e', 'tag_border' => '', 'name_fg' => '#ffffff', 'tagline_fg' => '#ffd166', 'font' => '"Trebuchet MS", sans-serif', 'radius' => 16 ) ),
			'scandi_minimal'     => array( 'label' => __( 'Scandinavian Minimal', 'shuffles-social-services-jobs' ), 'emoji' => '🤍',
				'prompt' => 'Minimal Scandinavian background. Lots of warm off-white space, a few thin hand-drawn arches and dots, one or two soft muted accent shapes in sage or clay at the edges. Calm, airy, uncluttered.',
				'negative' => 'No busy patterns. No photographs. No strong saturated colours. No text.',
				'scheme' => array( 'overlay' => 'rgba(255,255,255,0.5)', 'pill_bg' => '#2f3e46', 'pill_fg' => '#ffffff', 'tag_bg' => '#eef1f0', 'tag_fg' => '#2f3e46', 'tag_border' => '#cdd6d3', 'name_fg' => '#1c2429', 'tagline_fg' => '#52616b', 'font' => '"Segoe UI", Arial, sans-serif', 'radius' => 6 ) ),
			'gradient_mesh'      => array( 'label' => __( 'Modern Gradient', 'shuffles-social-services-jobs' ), 'emoji' => '🌈',
				'prompt' => 'Smooth modern gradient mesh background. Soft flowing blends of violet, blue, teal and warm pink, like coloured light. Subtle grain, no shapes, gentle and premium. Slightly darker toward the lower third.',
				'negative' => 'No hard shapes. No photographs. No text. No logos.',
				'scheme' => array( 'overlay' => 'rgba(20,10,40,0.35)', 'pill_bg' => 'rgba(255,255,255,0.9)', 'pill_fg' => '#3a1d6e', 'tag_bg' => 'rgba(255,255,255,0.18)', 'tag_fg' => '#ffffff', 'tag_border' => 'rgba(255,255,255,0.5)', 'name_fg' => '#ffffff', 'tagline_fg' => '#f0e6ff', 'font' => '"Segoe UI", Arial, sans-serif', 'radius' => 18 ) ),
			'terrazzo'           => array( 'label' => __( 'Terrazzo', 'shuffles-social-services-jobs' ), 'emoji' => '🟢',
				'prompt' => 'Terrazzo background. Speckled composite of small flecks in terracotta, sage, mustard and charcoal on a warm cream base. Even, friendly, contemporary. Keep the top and centre clear.',
				'negative' => 'No photographs. No dark background. No text.',
				'scheme' => array( 'overlay' => 'rgba(255,255,255,0.5)', 'pill_bg' => '#e76f51', 'pill_fg' => '#ffffff', 'tag_bg' => '#fdf2ec', 'tag_fg' => '#9c4221', 'tag_border' => '#e7b59f', 'name_fg' => '#2d2a26', 'tagline_fg' => '#6b6258', 'font' => '"Trebuchet MS", sans-serif', 'radius' => 20 ) ),
			'blueprint'          => array( 'label' => __( 'Blueprint', 'shuffles-social-services-jobs' ), 'emoji' => '📐',
				'prompt' => 'Technical blueprint background. Deep navy ground with fine white and pale-cyan grid lines and a few elegant draughting flourishes and compass arcs at the edges. Precise, trustworthy, clean centre.',
				'negative' => 'No photographs. No bright colours. No clutter in the centre. No text.',
				'scheme' => array( 'overlay' => 'rgba(10,30,70,0.55)', 'pill_bg' => 'rgba(255,255,255,0.92)', 'pill_fg' => '#0a2350', 'tag_bg' => 'rgba(0,0,0,0)', 'tag_fg' => '#dce8ff', 'tag_border' => '#9fc0ff', 'name_fg' => '#ffffff', 'tagline_fg' => '#bcd0f5', 'font' => '"Courier New", monospace', 'radius' => 4 ) ),
			'marble_gold'        => array( 'label' => __( 'Marble & Gold', 'shuffles-social-services-jobs' ), 'emoji' => '🪙',
				'prompt' => 'Luxurious white marble background with fine gold veins. Soft polished stone texture, thin gilded lines tracing across the edges, refined and premium. Keep the centre as clean marble.',
				'negative' => 'No photographs. No busy patterns. No dark colours. No text.',
				'scheme' => array( 'overlay' => 'rgba(255,255,255,0.42)', 'pill_bg' => 'rgba(40,40,45,0.9)', 'pill_fg' => '#e8c87a', 'tag_bg' => 'rgba(255,255,255,0.6)', 'tag_fg' => '#3a3a40', 'tag_border' => '#caa64a', 'name_fg' => '#26262b', 'tagline_fg' => '#6a6a6f', 'font' => '"Palatino Linotype", serif', 'radius' => 8 ) ),
			'clean_professional' => array( 'label' => __( 'Clean Professional', 'shuffles-social-services-jobs' ), 'emoji' => '💼',
				'prompt' => 'Clean modern professional card background. Soft gradient wash in cool slate and white, subtle geometric line texture, minimal corner accent marks, premium feel.',
				'negative' => 'No ornate decoration. No vintage elements. No strong colours.',
				'scheme' => array( 'overlay' => 'rgba(255,255,255,0.55)', 'pill_bg' => '#1a4a8a', 'pill_fg' => '#ffffff', 'tag_bg' => '#e8f0f8', 'tag_fg' => '#1a4a8a', 'tag_border' => '#1a4a8a', 'name_fg' => '#1a1a2e', 'tagline_fg' => '#404060', 'font' => '"Segoe UI", Arial, sans-serif', 'radius' => 8 ) ),
		);
	}

	public static function public_styles() {
		$out = array();
		foreach ( self::styles() as $key => $s ) {
			$out[] = array( 'key' => $key, 'label' => $s['label'], 'emoji' => $s['emoji'], 'scheme' => $s['scheme'] );
		}
		return $out;
	}

	/* --------------------------------------------------------------- member data (own profile only) */

	public static function member_data( $uid ) {
		$data = array( 'name' => '', 'location' => '', 'services' => array(), 'tagline' => '', 'category' => 'support worker' );
		if ( class_exists( 'Shuffles_SSJ_Assets' ) ) {
			$r = Shuffles_SSJ_Assets::resume_data( $uid );
			if ( is_array( $r ) ) {
				$data['name']     = (string) ( isset( $r['name'] ) ? $r['name'] : '' );
				$data['location'] = (string) ( isset( $r['location'] ) ? $r['location'] : '' );
				$data['services'] = isset( $r['services'] ) ? array_values( (array) $r['services'] ) : array();
				$data['tagline']  = (string) ( isset( $r['tagline'] ) ? $r['tagline'] : '' );
				$data['category'] = ! empty( $r['tagline'] ) ? $r['tagline'] : 'support worker';
			}
		}
		if ( '' === $data['name'] ) {
			$u = get_userdata( $uid );
			$data['name'] = $u ? $u->display_name : '';
		}
		return $data;
	}

	/* --------------------------------------------------------------- gating (monthly limit) */

	public static function limit_for( $uid ) {
		$o = get_option( 'shuffles_ssj_settings', array() );
		$default = max( 0, (int) ( is_array( $o ) && isset( $o['profile_card_limit'] ) ? $o['profile_card_limit'] : 1 ) );
		return (int) apply_filters( 'shuffles_ssj_card_limit', $default, (int) $uid );
	}
	public static function usage_key() {
		return '_sssj_card_gen_' . gmdate( 'Ym' );
	}
	public static function used_this_month( $uid ) {
		return (int) get_user_meta( (int) $uid, self::usage_key(), true );
	}
	public static function increment( $uid ) {
		update_user_meta( (int) $uid, self::usage_key(), self::used_this_month( $uid ) + 1 );
	}

	/* --------------------------------------------------------------- OpenAI */

	/** Context line shared by preset and custom prompts (member category/location/services). */
	private static function member_context( $member ) {
		$svc = implode( ', ', array_slice( (array) $member['services'], 0, 5 ) );
		$loc = ( '' !== trim( (string) $member['location'] ) ) ? $member['location'] : 'their local area';
		return ' This is a decorative, TEXT-FREE background and frame for a professional services card for a '
			. ( isset( $member['category'] ) ? $member['category'] : 'support worker' ) . ' based in ' . $loc . ' offering: ' . $svc
			. '. Do NOT include any text, names, numbers, logos or readable words. Keep the upper and central area clean for overlaying text. Square 1024x1024.';
	}

	/** POST a prompt to the image API; returns [ success, image_data | error ]. */
	private static function request_image( $prompt ) {
		$key = self::api_key();
		if ( '' === trim( $key ) ) {
			return array( 'success' => false, 'error' => 'No API key.' );
		}
		$resp = wp_remote_post( 'https://api.openai.com/v1/images/generations', array(
			'timeout' => 90,
			'headers' => array( 'Authorization' => 'Bearer ' . $key, 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode( array( 'model' => self::model(), 'prompt' => $prompt, 'n' => 1, 'size' => '1024x1024', 'quality' => 'high' ) ),
		) );
		if ( is_wp_error( $resp ) ) {
			return array( 'success' => false, 'error' => $resp->get_error_message() );
		}
		$body = json_decode( (string) wp_remote_retrieve_body( $resp ), true );
		if ( ! empty( $body['data'][0]['b64_json'] ) ) {
			return array( 'success' => true, 'image_data' => $body['data'][0]['b64_json'] );
		}
		return array( 'success' => false, 'error' => isset( $body['error']['message'] ) ? $body['error']['message'] : ( 'HTTP ' . (int) wp_remote_retrieve_response_code( $resp ) ) );
	}

	public static function generate_background( $style_key, $member ) {
		$styles = self::styles();
		if ( ! isset( $styles[ $style_key ] ) ) {
			return array( 'success' => false, 'error' => 'Unknown style.' );
		}
		$prompt = $styles[ $style_key ]['prompt'] . self::member_context( $member ) . ' ' . $styles[ $style_key ]['negative'];
		return self::request_image( $prompt );
	}

	/** Member-described ("custom") style. The description sets the look; our safety rules are always appended. */
	public static function generate_custom_background( $description, $member ) {
		$description = trim( wp_strip_all_tags( (string) $description ) );
		if ( '' === $description ) {
			return array( 'success' => false, 'error' => 'Empty description.' );
		}
		if ( function_exists( 'mb_substr' ) ) {
			$description = mb_substr( $description, 0, 280 );
		} else {
			$description = substr( $description, 0, 280 );
		}
		$prompt = 'Artistic decorative background in this style: ' . $description . '.' . self::member_context( $member )
			. ' Tasteful, professional, suitable for all audiences. No people’s faces, no brand logos, no words.';
		return self::request_image( $prompt );
	}

	/** A neutral, legible colour scheme for custom artwork (we cannot predict the colours the member asked for). */
	public static function custom_scheme() {
		return array(
			'overlay'    => 'rgba(0,0,0,0.42)',
			'pill_bg'    => 'rgba(0,0,0,0.72)',
			'pill_fg'    => '#ffffff',
			'tag_bg'     => 'rgba(255,255,255,0.16)',
			'tag_fg'     => '#ffffff',
			'tag_border' => 'rgba(255,255,255,0.5)',
			'name_fg'    => '#ffffff',
			'tagline_fg' => '#eaeaea',
			'font'       => 'Arial, sans-serif',
			'radius'     => 16,
		);
	}

	/* --------------------------------------------------------------- REST */

	public static function rest_routes() {
		register_rest_route( 'sssj/v1', '/card-styles', array(
			'methods' => 'GET', 'permission_callback' => '__return_true',
			'callback' => function () { return rest_ensure_response( self::public_styles() ); },
		) );
		register_rest_route( 'sssj/v1', '/card-generate', array(
			'methods' => 'POST', 'permission_callback' => 'is_user_logged_in',
			'callback' => array( __CLASS__, 'rest_generate' ),
		) );
		register_rest_route( 'sssj/v1', '/card-save', array(
			'methods' => 'POST', 'permission_callback' => 'is_user_logged_in',
			'callback' => array( __CLASS__, 'rest_save' ),
		) );
	}

	public static function rest_generate( WP_REST_Request $req ) {
		if ( ! self::enabled() ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Card generation is not switched on.', 'shuffles-social-services-jobs' ) ), 400 );
		}
		$uid    = get_current_user_id();
		$style  = sanitize_key( (string) $req->get_param( 'style' ) );
		$styles = self::styles();
		$is_custom = ( 'custom' === $style );
		if ( ! $is_custom && ! isset( $styles[ $style ] ) ) {
			$style = 'clean_professional';
		}
		$limit = self::limit_for( $uid );
		if ( $limit < 999 && self::used_this_month( $uid ) >= $limit ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => sprintf( __( 'You have used your %d card(s) for this month.', 'shuffles-social-services-jobs' ), $limit ) ), 403 );
		}
		$member = self::member_data( $uid );

		if ( $is_custom ) {
			$desc = (string) $req->get_param( 'custom' );
			if ( '' === trim( wp_strip_all_tags( $desc ) ) ) {
				return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Please describe the look you want.', 'shuffles-social-services-jobs' ) ), 400 );
			}
			$res    = self::generate_custom_background( $desc, $member );
			$scheme = self::custom_scheme();
		} else {
			$res    = self::generate_background( $style, $member );
			$scheme = $styles[ $style ]['scheme'];
		}

		if ( empty( $res['success'] ) ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Image generation failed: ', 'shuffles-social-services-jobs' ) . ( isset( $res['error'] ) ? $res['error'] : '' ) ), 502 );
		}
		self::increment( $uid );
		return new WP_REST_Response( array(
			'success'    => true,
			'background' => $res['image_data'],
			'member'     => $member,
			'style'      => $style,
			'scheme'     => $scheme,
			'usage'      => array( 'used' => self::used_this_month( $uid ), 'limit' => $limit ),
		), 200 );
	}

	public static function rest_save( WP_REST_Request $req ) {
		$uid = get_current_user_id();
		$png = (string) $req->get_param( 'png' );
		$style = sanitize_key( (string) $req->get_param( 'style' ) );
		if ( '' === $png ) {
			return new WP_REST_Response( array( 'success' => false ), 400 );
		}
		if ( 0 === strpos( $png, 'data:' ) ) {
			$c = strpos( $png, ',' );
			$png = false !== $c ? substr( $png, $c + 1 ) : '';
		}
		$bytes = base64_decode( $png, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
		if ( false === $bytes || "\x89PNG" !== substr( (string) $bytes, 0, 4 ) || strlen( (string) $bytes ) > 12 * MB_IN_BYTES ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => 'Invalid image.' ), 400 );
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$u    = get_userdata( $uid );
		$name = $u ? $u->display_name : 'member';
		$up   = wp_upload_bits( sanitize_file_name( 'profile-card-' . $name . '-' . ( $style ? $style : 'card' ) . '-' . gmdate( 'Ymd-His' ) . '.png' ), null, $bytes );
		if ( ! empty( $up['error'] ) ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => 'Save failed.' ), 500 );
		}
		$id = wp_insert_attachment( array( 'post_mime_type' => 'image/png', 'post_title' => 'Profile Card: ' . $name, 'post_status' => 'inherit', 'post_author' => $uid ), $up['file'] );
		if ( is_wp_error( $id ) || ! $id ) {
			return new WP_REST_Response( array( 'success' => false ), 500 );
		}
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $up['file'] ) );
		update_post_meta( $id, '_sssj_card_user', (int) $uid );
		do_action( 'shuffles_ssj_profile_card_saved', $id, $uid, $style );
		return new WP_REST_Response( array( 'success' => true, 'image_url' => wp_get_attachment_url( $id ), 'media_id' => $id ), 200 );
	}

	/* --------------------------------------------------------------- shortcode */

	public static function shortcode() {
		if ( ! is_user_logged_in() ) {
			return '<div class="sssj"><div class="sssj-panel"><p>' . esc_html__( 'Log in to create your profile card.', 'shuffles-social-services-jobs' ) . '</p></div></div>';
		}
		wp_enqueue_style( 'sssj' );
		if ( ! self::enabled() ) {
			return '<div class="sssj"><div class="sssj-panel"><p>' . esc_html__( 'The profile card generator is not available yet.', 'shuffles-social-services-jobs' ) . '</p></div></div>';
		}
		wp_enqueue_style( 'sssj-assets', SHUFFLES_SSJ_URL . 'public/assets/css/sssj-assets.css', array( 'sssj' ), SHUFFLES_SSJ_VERSION );
		wp_enqueue_script( 'sssj-profilecard', SHUFFLES_SSJ_URL . 'public/assets/js/sssj-profilecard.js', array(), SHUFFLES_SSJ_VERSION, true );
		wp_localize_script( 'sssj-profilecard', 'SSSJ_Card', array(
			'generate' => rest_url( 'sssj/v1/card-generate' ),
			'save'     => rest_url( 'sssj/v1/card-save' ),
			'styles'   => rest_url( 'sssj/v1/card-styles' ),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			'brand'    => get_bloginfo( 'name' ),
		) );
		ob_start();
		if ( class_exists( 'Shuffles_SSJ_Plugin' ) ) {
			Shuffles_SSJ_Plugin::instance()->shortcodes->load_template( 'profile-card.php', array() );
		}
		return ob_get_clean();
	}
}
