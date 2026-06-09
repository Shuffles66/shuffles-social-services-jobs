<?php
/**
 * Shareable assets (Workstream E), Phase 2: pixel-perfect server-side rendering.
 *
 * The $0 browser path (print-to-PDF + client PNG) always works and stays the default. When an
 * admin points the plugin at a self-hosted HTML-to-PDF service (Gotenberg is the recommended one:
 * a single Docker container), members get a true print-quality PDF of their résumé / service flyer
 * / job flyer instead of a browser print.
 *
 * This is a renderer SEAM: the driver is pluggable (filter shuffles_ssj_asset_render_driver), so a
 * different backend can be swapped in without touching the asset templates. Default driver targets
 * the Gotenberg "Chromium → HTML → PDF" route.
 *
 * PRIVACY: a participant-derived asset is NEVER sent to a renderer that has not been affirmed as
 * self-hosted/private. (No participant asset type exists yet; the guard is built in for when one does.)
 *
 * SECURITY: the render endpoint is login-gated, nonce-checked and ownership-checked. The render
 * target URL is admin-only configuration (not user input), so wp_remote_post is used deliberately so
 * a private host such as http://gotenberg:3000 can be reached.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Asset_Renderer {

	/** @var string Cached inlined CSS. */
	private static $css_cache = '';

	public static function register() {
		add_action( 'admin_post_sssj_asset_render', array( __CLASS__, 'handle_render' ) );
		add_action( 'admin_post_sssj_asset_render_test', array( __CLASS__, 'handle_test' ) );
		// AJAX: render the résumé server-side and save it straight into "My résumés".
		add_action( 'wp_ajax_sssj_asset_save_resume', array( __CLASS__, 'handle_save_resume' ) );
	}

	/* --------------------------------------------------------------- settings */

	private static function opt( $key, $default = '' ) {
		$o = get_option( 'shuffles_ssj_settings', array() );
		return ( is_array( $o ) && isset( $o[ $key ] ) ) ? $o[ $key ] : $default;
	}

	/** 'browser' (default) | 'server'. */
	public static function mode() {
		$m = (string) self::opt( 'asset_render_mode', 'browser' );
		return in_array( $m, array( 'browser', 'server' ), true ) ? $m : 'browser';
	}

	/** Configured render service base URL (e.g. http://127.0.0.1:3000), '' if none. */
	public static function endpoint() {
		return rtrim( (string) self::opt( 'asset_render_endpoint', '' ), '/' );
	}

	/** Has the admin affirmed the renderer is self-hosted / private? (Gates participant content.) */
	public static function is_self_hosted() {
		return '1' === (string) self::opt( 'asset_render_self_hosted', '0' );
	}

	/** Is high-quality server rendering available right now? */
	public static function enabled() {
		return 'server' === self::mode() && '' !== self::endpoint() && class_exists( 'Shuffles_SSJ_Assets' ) && Shuffles_SSJ_Assets::enabled();
	}

	/** The driver key (filterable). */
	public static function driver() {
		return (string) apply_filters( 'shuffles_ssj_asset_render_driver', 'gotenberg' );
	}

	/* --------------------------------------------------------------- rendering */

	/** Plugin CSS, inlined into the standalone document so the service needs no network access. */
	private static function css() {
		if ( '' !== self::$css_cache ) {
			return self::$css_cache;
		}
		$css = '';
		foreach ( array( 'public/assets/css/sssj.css', 'public/assets/css/sssj-assets.css' ) as $rel ) {
			$path = SHUFFLES_SSJ_DIR . $rel;
			if ( is_file( $path ) ) {
				$css .= "\n" . (string) file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			}
		}
		self::$css_cache = $css;
		return $css;
	}

	/** Convert a local upload URL to a data: URI so the renderer needs no access to the media library. */
	public static function inline_url( $url ) {
		$url = (string) $url;
		if ( '' === $url ) {
			return '';
		}
		$uploads = wp_get_upload_dir();
		if ( ! empty( $uploads['baseurl'] ) && 0 === strpos( $url, $uploads['baseurl'] ) ) {
			$path = $uploads['basedir'] . substr( $url, strlen( $uploads['baseurl'] ) );
			$path = explode( '?', $path )[0];
			if ( is_file( $path ) && filesize( $path ) > 0 && filesize( $path ) < 3 * MB_IN_BYTES ) {
				$type = wp_check_filetype( $path );
				if ( ! empty( $type['type'] ) ) {
					return 'data:' . $type['type'] . ';base64,' . base64_encode( (string) file_get_contents( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions, WordPress.PHP.DiscouragedPHPFunctions
				}
			}
		}
		return $url; // remote / unknown, leave as a URL
	}

	/** Wrap rendered asset markup into a standalone, self-contained HTML document. */
	public static function standalone_html( $inner_html, $title = 'Asset' ) {
		$lang = esc_attr( get_bloginfo( 'language' ) );
		// The .sssj-asset-print wrapper makes the print stylesheet reveal only the asset.
		return '<!DOCTYPE html><html lang="' . $lang . '"><head><meta charset="utf-8">'
			. '<meta name="viewport" content="width=device-width, initial-scale=1">'
			. '<title>' . esc_html( $title ) . '</title>'
			. '<style>' . self::css() . '</style></head>'
			. '<body><div class="sssj sssj--create-asset"><div class="sssj-asset-print">'
			. $inner_html
			. '</div></div></body></html>';
	}

	/**
	 * Render HTML to a document via the active driver.
	 *
	 * @param string $html  Full standalone HTML.
	 * @param array  $args  format ('pdf'), paper ('a4'|'square'), filename.
	 * @return array [ ok(bool), mime, body(bytes), error(string) ]
	 */
	public static function render( $html, $args = array() ) {
		$driver = self::driver();
		if ( 'gotenberg' === $driver ) {
			return self::render_gotenberg( $html, $args );
		}
		/**
		 * Custom renderer driver. Return [ 'ok'=>bool, 'mime'=>string, 'body'=>bytes, 'error'=>string ].
		 */
		$out = apply_filters( 'shuffles_ssj_asset_render_custom', null, $html, $args, $driver );
		if ( is_array( $out ) ) {
			return $out;
		}
		return array( 'ok' => false, 'mime' => '', 'body' => '', 'error' => 'Unknown render driver: ' . $driver );
	}

	/** Gotenberg Chromium HTML→PDF. */
	private static function render_gotenberg( $html, $args ) {
		$base = self::endpoint();
		if ( '' === $base ) {
			return array( 'ok' => false, 'mime' => '', 'body' => '', 'error' => 'No render endpoint configured.' );
		}
		$url = $base . '/forms/chromium/convert/html';

		$fields = array(
			'printBackground' => 'true',
			'marginTop'       => '0',
			'marginBottom'    => '0',
			'marginLeft'      => '0',
			'marginRight'     => '0',
		);
		if ( 'square' === ( isset( $args['paper'] ) ? $args['paper'] : 'a4' ) ) {
			$fields['paperWidth']  = '6';
			$fields['paperHeight'] = '6';
		} else {
			$fields['paperWidth']  = '8.27';  // A4 in inches
			$fields['paperHeight'] = '11.69';
		}

		$boundary = 'sssjboundary' . wp_generate_password( 16, false, false );
		$body     = self::multipart(
			$fields,
			array( 'files' => array( 'filename' => 'index.html', 'mime' => 'text/html', 'content' => $html ) ),
			$boundary
		);

		$resp = wp_remote_post( $url, array(
			'timeout' => 30,
			'headers' => array( 'Content-Type' => 'multipart/form-data; boundary=' . $boundary ),
			'body'    => $body,
		) );

		if ( is_wp_error( $resp ) ) {
			return array( 'ok' => false, 'mime' => '', 'body' => '', 'error' => $resp->get_error_message() );
		}
		$code = (int) wp_remote_retrieve_response_code( $resp );
		$out  = (string) wp_remote_retrieve_body( $resp );
		$mime = (string) wp_remote_retrieve_header( $resp, 'content-type' );
		if ( 200 !== $code || '' === $out ) {
			return array( 'ok' => false, 'mime' => '', 'body' => '', 'error' => 'Render service returned HTTP ' . $code . ( $out ? ': ' . wp_strip_all_tags( substr( $out, 0, 300 ) ) : '' ) );
		}
		return array( 'ok' => true, 'mime' => $mime ? $mime : 'application/pdf', 'body' => $out, 'error' => '' );
	}

	/** Build a multipart/form-data body. $files: name => [filename, mime, content]. */
	private static function multipart( $fields, $files, $boundary ) {
		$nl   = "\r\n";
		$body = '';
		foreach ( $fields as $name => $value ) {
			$body .= '--' . $boundary . $nl;
			$body .= 'Content-Disposition: form-data; name="' . $name . '"' . $nl . $nl;
			$body .= $value . $nl;
		}
		foreach ( $files as $name => $file ) {
			$body .= '--' . $boundary . $nl;
			$body .= 'Content-Disposition: form-data; name="' . $name . '"; filename="' . $file['filename'] . '"' . $nl;
			$body .= 'Content-Type: ' . $file['mime'] . $nl . $nl;
			$body .= $file['content'] . $nl;
		}
		$body .= '--' . $boundary . '--' . $nl;
		return $body;
	}

	/* --------------------------------------------------------------- the asset endpoint */

	/** Asset types this server path supports, with their template + paper. */
	private static function types() {
		return array(
			'resume' => array( 'tpl' => 'assets/resume.php',        'paper' => 'a4', 'participant' => false ),
			'flyer'  => array( 'tpl' => 'assets/service-flyer.php', 'paper' => 'a4', 'participant' => false ),
			'job'    => array( 'tpl' => 'assets/job-flyer.php',     'paper' => 'a4', 'participant' => false ),
		);
	}

	/**
	 * admin-post: build the chosen asset and stream a print-quality PDF.
	 * Login + nonce + ownership enforced. Falls back with a plain-text error on failure (the UI keeps
	 * the browser path).
	 */
	public static function handle_render() {
		if ( ! is_user_logged_in() ) {
			self::fail( 'Please log in.', 401 );
		}
		if ( ! check_ajax_referer( 'sssj_asset_render', '_wpnonce', false ) ) {
			self::fail( 'Security check failed. Reload the page and try again.', 403 );
		}
		if ( ! self::enabled() ) {
			self::fail( 'High-quality rendering is not switched on.', 400 );
		}

		$type  = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
		$types = self::types();
		if ( ! isset( $types[ $type ] ) ) {
			self::fail( 'Unknown asset type.', 400 );
		}
		$spec = $types[ $type ];

		// Privacy guard: a participant-derived asset may only be sent to a self-hosted renderer.
		if ( $spec['participant'] && ! self::is_self_hosted() ) {
			self::fail( 'This asset can only be rendered by a self-hosted renderer.', 403 );
		}

		$uid = get_current_user_id();

		// Build the asset data + render the locked template to HTML.
		if ( 'job' === $type ) {
			$job_id = isset( $_POST['job_id'] ) ? absint( $_POST['job_id'] ) : 0;
			$post   = $job_id ? get_post( $job_id ) : null;
			if ( ! $post || 'sssj_job' !== $post->post_type ) {
				self::fail( 'Job not found.', 404 );
			}
			if ( (int) $post->post_author !== $uid && ! current_user_can( 'manage_options' ) ) {
				self::fail( 'You can only make a flyer for your own job.', 403 );
			}
			$data = Shuffles_SSJ_Assets::job_data( $job_id );
			if ( ! is_array( $data ) ) {
				self::fail( 'Could not read that job.', 404 );
			}
			$data['logo'] = self::inline_url( isset( $data['logo'] ) ? $data['logo'] : '' );
			$fname        = 'job-flyer';
		} else {
			$data = Shuffles_SSJ_Assets::resume_data( $uid );
			if ( ! is_array( $data ) ) {
				self::fail( 'Create your worker profile first.', 400 );
			}
			// Apply the member's polished wording (same fields as the live preview).
			if ( isset( $_POST['tagline'] ) ) {
				$tag = sanitize_text_field( wp_unslash( $_POST['tagline'] ) );
				if ( '' !== $tag ) {
					$data['tagline'] = $tag;
				}
			}
			if ( isset( $_POST['blurb'] ) ) {
				$data['blurb'] = sanitize_textarea_field( wp_unslash( $_POST['blurb'] ) );
			}
			$data['photo'] = self::inline_url( isset( $data['photo'] ) ? $data['photo'] : '' );
			$fname         = ( 'flyer' === $type ) ? 'service-flyer' : 'resume';
		}

		// Render the template to a string.
		$inner = '';
		if ( class_exists( 'Shuffles_SSJ_Plugin' ) ) {
			ob_start();
			Shuffles_SSJ_Plugin::instance()->shortcodes->load_template( $spec['tpl'], array( 'data' => $data ) );
			$inner = (string) ob_get_clean();
		}
		if ( '' === trim( $inner ) ) {
			self::fail( 'Could not build the asset.', 500 );
		}

		$html   = self::standalone_html( $inner, $fname );
		$result = self::render( $html, array( 'format' => 'pdf', 'paper' => $spec['paper'] ) );

		if ( empty( $result['ok'] ) ) {
			self::fail( 'Render service error: ' . ( isset( $result['error'] ) ? $result['error'] : 'unknown' ), 502 );
		}

		nocache_headers();
		header( 'Content-Type: ' . ( $result['mime'] ? $result['mime'] : 'application/pdf' ) );
		header( 'Content-Disposition: attachment; filename="' . $fname . '.pdf"' );
		header( 'Content-Length: ' . strlen( $result['body'] ) );
		header( 'X-Content-Type-Options: nosniff' );
		echo $result['body']; // phpcs:ignore WordPress.Security.EscapeOutput
		exit;
	}

	/**
	 * AJAX: build the member's résumé server-side and store it in "My résumés" (the file store).
	 * Login + nonce + renderer-enabled enforced. Returns JSON so the builder can confirm in place.
	 */
	public static function handle_save_resume() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'msg' => __( 'Please log in.', 'shuffles-social-services-jobs' ) ), 401 );
		}
		if ( ! check_ajax_referer( 'sssj_asset_render', 'nonce', false ) ) {
			wp_send_json_error( array( 'msg' => __( 'Security check failed. Reload the page and try again.', 'shuffles-social-services-jobs' ) ), 403 );
		}
		if ( ! self::enabled() ) {
			wp_send_json_error( array( 'msg' => __( 'High-quality saving is not switched on yet. You can still download a PDF from the buttons above.', 'shuffles-social-services-jobs' ) ), 400 );
		}
		if ( ! class_exists( 'Shuffles_SSJ_Resumes' ) || ! class_exists( 'Shuffles_SSJ_Assets' ) ) {
			wp_send_json_error( array( 'msg' => __( 'Résumé storage is unavailable right now.', 'shuffles-social-services-jobs' ) ), 400 );
		}

		$uid  = get_current_user_id();
		$data = Shuffles_SSJ_Assets::resume_data( $uid );
		if ( ! is_array( $data ) ) {
			wp_send_json_error( array( 'msg' => __( 'Create your worker profile first, then build your résumé.', 'shuffles-social-services-jobs' ) ), 400 );
		}

		// Apply the member's polished wording from the builder (same fields as the live preview).
		if ( isset( $_POST['tagline'] ) ) {
			$tag = sanitize_text_field( wp_unslash( $_POST['tagline'] ) );
			if ( '' !== $tag ) {
				$data['tagline'] = $tag;
			}
		}
		if ( isset( $_POST['blurb'] ) ) {
			$blurb = sanitize_textarea_field( wp_unslash( $_POST['blurb'] ) );
			if ( '' !== $blurb ) {
				$data['blurb']   = $blurb;
				$data['summary'] = $blurb;
			}
		}
		// Honour the layout choice (ATS-friendly vs Styled) so the saved PDF matches the preview.
		$fmt            = ( isset( $_POST['format'] ) && 'styled' === sanitize_key( wp_unslash( $_POST['format'] ) ) ) ? 'styled' : 'ats';
		$data['format'] = $fmt;
		$data['photo']  = self::inline_url( isset( $data['photo'] ) ? $data['photo'] : '' );

		// Render the locked résumé template to HTML, then to a print-quality PDF.
		$inner = '';
		if ( class_exists( 'Shuffles_SSJ_Plugin' ) ) {
			ob_start();
			Shuffles_SSJ_Plugin::instance()->shortcodes->load_template( 'assets/resume.php', array( 'data' => $data ) );
			$inner = (string) ob_get_clean();
		}
		if ( '' === trim( $inner ) ) {
			wp_send_json_error( array( 'msg' => __( 'Could not build the résumé. Please try again.', 'shuffles-social-services-jobs' ) ), 500 );
		}

		$html   = self::standalone_html( $inner, 'resume' );
		$result = self::render( $html, array( 'format' => 'pdf', 'paper' => 'a4' ) );
		if ( empty( $result['ok'] ) || empty( $result['body'] ) ) {
			wp_send_json_error( array( 'msg' => __( 'The résumé could not be generated right now. Please try again shortly.', 'shuffles-social-services-jobs' ) ), 502 );
		}

		$label = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';
		if ( '' === $label ) {
			/* translators: %s: date the résumé was built. */
			$label = sprintf( __( 'My résumé (%s)', 'shuffles-social-services-jobs' ), wp_date( 'j M Y' ) );
		}

		$saved = Shuffles_SSJ_Resumes::add_bytes( $uid, $label, $result['body'], 'application/pdf', 'resume.pdf', false );
		if ( is_wp_error( $saved ) ) {
			wp_send_json_error( array( 'msg' => $saved->get_error_message() ), 400 );
		}

		wp_send_json_success( array(
			'msg'   => __( 'Saved to “My résumés”. Open the My résumés tab to download or set it as default.', 'shuffles-social-services-jobs' ),
			'id'    => (int) $saved,
			'label' => $label,
		) );
	}

	/** admin-post: a connectivity test for the configured renderer (admin only). Redirects back. */
	public static function handle_test() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'shuffles-social-services-jobs' ) );
		}
		check_admin_referer( 'sssj_asset_render_test' );
		$back = wp_get_referer();
		$back = $back ? $back : admin_url( 'admin.php?page=shuffles-ssj&tab=rendering' );

		if ( '' === self::endpoint() ) {
			wp_safe_redirect( add_query_arg( 'sssj_render_test', 'noendpoint', $back ) );
			exit;
		}
		$html   = self::standalone_html( '<div class="sssj-asset" style="padding:30px"><h1>Render test</h1><p>If you can read this in a PDF, the renderer works.</p></div>', 'Test' );
		$result = self::render( $html, array( 'format' => 'pdf', 'paper' => 'a4' ) );
		wp_safe_redirect( add_query_arg( 'sssj_render_test', ! empty( $result['ok'] ) ? 'ok' : 'fail', $back ) );
		exit;
	}

	/** Emit a plain-text error with a status code and stop (the front end shows it + keeps the browser path). */
	private static function fail( $message, $code = 400 ) {
		status_header( (int) $code );
		nocache_headers();
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo esc_html( $message );
		exit;
	}
}
