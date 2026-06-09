<?php
/**
 * Native, themed login + create-account forms ([sssj_login], [sssj_register]).
 *
 * A branded alternative to the bare wp-login.php that still creates and uses standard WordPress
 * accounts (so members, roles and every marketplace feature keep working). Put each shortcode on its
 * own page, then select those pages under Settings, Pages, "Login and create-account pages"; all the
 * plugin's "Log in" / "Create account" links will route to them.
 *
 * Security: per-form nonce, a honeypot field, same-host redirect validation, and a light per-IP
 * throttle. Administrators always keep direct access to wp-admin / wp-login.php.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Accounts {

	const ACTION_FIELD = 'sssj_account_action';

	public static function register() {
		add_shortcode( 'sssj_login', array( __CLASS__, 'login_form' ) );
		add_shortcode( 'sssj_register', array( __CLASS__, 'register_form' ) );
		add_action( 'init', array( __CLASS__, 'maybe_handle' ), 20 );
	}

	/** Is self-registration through the form allowed? Filterable. */
	public static function registration_allowed() {
		return (bool) apply_filters( 'shuffles_ssj_allow_registration', true );
	}

	/* --------------------------------------------------------------- submission handling */

	public static function maybe_handle() {
		if ( empty( $_POST[ self::ACTION_FIELD ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}
		$action = sanitize_key( wp_unslash( $_POST[ self::ACTION_FIELD ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( 'login' === $action ) {
			self::handle_login();
		} elseif ( 'register' === $action ) {
			self::handle_register();
		}
	}

	/** Resolve and validate a same-host redirect target. */
	private static function safe_redirect_target( $fallback ) {
		$to = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( (string) $_REQUEST['redirect_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$to = $to ? wp_validate_redirect( $to, $fallback ) : $fallback;
		return $to ? $to : home_url( '/' );
	}

	/** Store a one-shot error/notice for the form to show after the redirect. */
	private static function flash( $msg ) {
		$token = wp_generate_password( 12, false );
		set_transient( 'sssj_auth_msg_' . $token, $msg, 120 );
		return $token;
	}

	private static function back_with_error( $msg ) {
		$ref   = wp_get_referer();
		$ref   = $ref ? $ref : home_url( '/' );
		$token = self::flash( $msg );
		wp_safe_redirect( add_query_arg( 'sssj_auth', $token, remove_query_arg( 'sssj_auth', $ref ) ) );
		exit;
	}

	/** Light throttle: max ~10 auth attempts per IP per 10 minutes. */
	private static function throttled() {
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0';
		$key = 'sssj_auth_rl_' . md5( $ip );
		$n   = (int) get_transient( $key );
		if ( $n >= 10 ) {
			return true;
		}
		set_transient( $key, $n + 1, 10 * MINUTE_IN_SECONDS );
		return false;
	}

	private static function handle_login() {
		if ( ! isset( $_POST['sssj_login_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['sssj_login_nonce'] ) ), 'sssj_login' ) ) {
			self::back_with_error( __( 'Your session expired. Please try again.', 'shuffles-social-services-jobs' ) );
		}
		if ( ! empty( $_POST['sssj_hp'] ) ) { // honeypot
			self::back_with_error( __( 'Could not sign you in.', 'shuffles-social-services-jobs' ) );
		}
		if ( self::throttled() ) {
			self::back_with_error( __( 'Too many attempts. Please wait a few minutes and try again.', 'shuffles-social-services-jobs' ) );
		}
		$creds = array(
			'user_login'    => isset( $_POST['user_login'] ) ? sanitize_text_field( wp_unslash( $_POST['user_login'] ) ) : '',
			'user_password' => isset( $_POST['user_password'] ) ? (string) wp_unslash( $_POST['user_password'] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			'remember'      => ! empty( $_POST['rememberme'] ),
		);
		// Allow logging in with an email address.
		if ( is_email( $creds['user_login'] ) ) {
			$u = get_user_by( 'email', $creds['user_login'] );
			if ( $u ) {
				$creds['user_login'] = $u->user_login;
			}
		}
		$user = wp_signon( $creds, is_ssl() );
		if ( is_wp_error( $user ) ) {
			self::back_with_error( __( 'Those details did not match. Please check and try again.', 'shuffles-social-services-jobs' ) );
		}
		wp_set_current_user( $user->ID );
		wp_safe_redirect( self::safe_redirect_target( home_url( '/' ) ) );
		exit;
	}

	private static function handle_register() {
		if ( ! isset( $_POST['sssj_register_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['sssj_register_nonce'] ) ), 'sssj_register' ) ) {
			self::back_with_error( __( 'Your session expired. Please try again.', 'shuffles-social-services-jobs' ) );
		}
		if ( ! empty( $_POST['sssj_hp'] ) ) {
			self::back_with_error( __( 'Could not create your account.', 'shuffles-social-services-jobs' ) );
		}
		if ( ! self::registration_allowed() ) {
			self::back_with_error( __( 'Account creation is currently closed.', 'shuffles-social-services-jobs' ) );
		}
		if ( self::throttled() ) {
			self::back_with_error( __( 'Too many attempts. Please wait a few minutes and try again.', 'shuffles-social-services-jobs' ) );
		}
		$name  = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';
		$email = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';
		$pass  = isset( $_POST['user_password'] ) ? (string) wp_unslash( $_POST['user_password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		if ( ! is_email( $email ) ) {
			self::back_with_error( __( 'Please enter a valid email address.', 'shuffles-social-services-jobs' ) );
		}
		if ( email_exists( $email ) ) {
			self::back_with_error( __( 'An account with that email already exists. Try logging in instead.', 'shuffles-social-services-jobs' ) );
		}
		if ( strlen( $pass ) < 8 ) {
			self::back_with_error( __( 'Please choose a password of at least 8 characters.', 'shuffles-social-services-jobs' ) );
		}

		// Build a unique username from the email local-part.
		$base  = sanitize_user( current( explode( '@', $email ) ), true );
		$base  = $base ? $base : 'member';
		$login = $base;
		$i     = 1;
		while ( username_exists( $login ) ) {
			$login = $base . $i;
			$i++;
		}

		$uid = wp_insert_user( array(
			'user_login'   => $login,
			'user_email'   => $email,
			'user_pass'    => $pass,
			'display_name' => $name ? $name : $login,
			'first_name'   => $name,
			'role'         => 'subscriber',
		) );
		if ( is_wp_error( $uid ) ) {
			self::back_with_error( __( 'Sorry, we could not create your account. Please try again.', 'shuffles-social-services-jobs' ) );
		}

		do_action( 'shuffles_ssj_account_registered', $uid, $email );

		// Sign the new member in and send them to onboarding.
		wp_set_current_user( $uid );
		wp_set_auth_cookie( $uid, true, is_ssl() );

		$onboard  = class_exists( 'Shuffles_SSJ_Shortcodes' ) ? Shuffles_SSJ_Shortcodes::page_link( 'page_onboard', '[sssj_onboard]' ) : '';
		$fallback = $onboard ? $onboard : home_url( '/' );
		wp_safe_redirect( self::safe_redirect_target( $fallback ) );
		exit;
	}

	/* --------------------------------------------------------------- rendering */

	/** Read + clear a flashed message (returns '' if none). */
	private static function take_flash() {
		if ( empty( $_GET['sssj_auth'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return '';
		}
		$token = sanitize_key( wp_unslash( $_GET['sssj_auth'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$msg   = get_transient( 'sssj_auth_msg_' . $token );
		if ( $msg ) {
			delete_transient( 'sssj_auth_msg_' . $token );
			return (string) $msg;
		}
		return '';
	}

	private static function redirect_field() {
		$to = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( (string) $_GET['redirect_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return $to ? '<input type="hidden" name="redirect_to" value="' . esc_attr( $to ) . '" />' : '';
	}

	private static function already_in() {
		$dash = class_exists( 'Shuffles_SSJ_Shortcodes' ) ? Shuffles_SSJ_Shortcodes::page_link( 'page_my_listings', '[sssj_dashboard]' ) : '';
		$out  = '<div class="sssj"><div class="sssj-panel"><p>' . esc_html__( 'You are already signed in.', 'shuffles-social-services-jobs' ) . '</p><p class="sssj-row">';
		if ( $dash ) {
			$out .= '<a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="' . esc_url( $dash ) . '">' . esc_html__( 'Go to my dashboard', 'shuffles-social-services-jobs' ) . '</a> ';
		}
		$out .= '<a class="sssj-btn sssj-btn--ghost sssj-btn--sm" href="' . esc_url( wp_logout_url( home_url( '/' ) ) ) . '">' . esc_html__( 'Log out', 'shuffles-social-services-jobs' ) . '</a>';
		$out .= '</p></div></div>';
		return $out;
	}

	public static function login_form( $atts ) {
		wp_enqueue_style( 'sssj' );
		if ( is_user_logged_in() ) {
			return self::already_in();
		}
		$a   = shortcode_atts( array( 'register_url' => '' ), is_array( $atts ) ? $atts : array(), 'sssj_login' );
		$err = self::take_flash();
		$reg = $a['register_url'] ? $a['register_url'] : ( class_exists( 'Shuffles_SSJ_Shortcodes' ) ? Shuffles_SSJ_Shortcodes::register_url() : '' );

		ob_start();
		echo '<div class="sssj sssj--account"><div class="sssj-panel" style="max-width:460px;margin:0 auto">';
		echo '<h2 style="margin-top:0">' . esc_html__( 'Log in', 'shuffles-social-services-jobs' ) . '</h2>';
		if ( $err ) {
			echo '<div class="sssj-note sssj-note--warn">' . esc_html( $err ) . '</div>';
		}
		echo '<form method="post" class="sssj-stack" action="' . esc_url( get_permalink() ) . '">';
		echo '<input type="hidden" name="' . esc_attr( self::ACTION_FIELD ) . '" value="login" />';
		wp_nonce_field( 'sssj_login', 'sssj_login_nonce' );
		echo '<p style="position:absolute;left:-9999px" aria-hidden="true"><label>' . esc_html__( 'Leave this blank', 'shuffles-social-services-jobs' ) . '<input type="text" name="sssj_hp" tabindex="-1" autocomplete="off" /></label></p>';
		echo self::redirect_field(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<label class="sssj-field"><span>' . esc_html__( 'Email or username', 'shuffles-social-services-jobs' ) . '</span><input class="sssj-input" type="text" name="user_login" autocomplete="username" required /></label>';
		echo '<label class="sssj-field"><span>' . esc_html__( 'Password', 'shuffles-social-services-jobs' ) . '</span><input class="sssj-input" type="password" name="user_password" autocomplete="current-password" required /></label>';
		echo '<label class="sssj-check"><input type="checkbox" name="rememberme" value="1" /> ' . esc_html__( 'Keep me signed in', 'shuffles-social-services-jobs' ) . '</label>';
		echo '<div style="margin-top:6px"><button type="submit" class="sssj-btn sssj-btn--primary">' . esc_html__( 'Log in', 'shuffles-social-services-jobs' ) . '</button></div>';
		echo '<p class="description" style="margin-top:10px"><a href="' . esc_url( wp_lostpassword_url() ) . '">' . esc_html__( 'Forgot your password?', 'shuffles-social-services-jobs' ) . '</a>';
		if ( $reg ) {
			echo ' &middot; ' . esc_html__( 'New here?', 'shuffles-social-services-jobs' ) . ' <a href="' . esc_url( $reg ) . '">' . esc_html__( 'Create an account', 'shuffles-social-services-jobs' ) . '</a>';
		}
		echo '</p>';
		echo '</form></div></div>';
		return ob_get_clean();
	}

	public static function register_form( $atts ) {
		wp_enqueue_style( 'sssj' );
		if ( is_user_logged_in() ) {
			return self::already_in();
		}
		$a     = shortcode_atts( array( 'login_url' => '' ), is_array( $atts ) ? $atts : array(), 'sssj_register' );
		$err   = self::take_flash();
		$login = $a['login_url'] ? $a['login_url'] : ( class_exists( 'Shuffles_SSJ_Shortcodes' ) ? Shuffles_SSJ_Shortcodes::login_url() : '' );

		if ( ! self::registration_allowed() ) {
			return '<div class="sssj"><div class="sssj-panel"><p>' . esc_html__( 'Account creation is currently closed.', 'shuffles-social-services-jobs' ) . '</p></div></div>';
		}

		ob_start();
		echo '<div class="sssj sssj--account"><div class="sssj-panel" style="max-width:460px;margin:0 auto">';
		echo '<h2 style="margin-top:0">' . esc_html__( 'Create your account', 'shuffles-social-services-jobs' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'It takes a minute. You can set up the rest of your profile right after.', 'shuffles-social-services-jobs' ) . '</p>';
		if ( $err ) {
			echo '<div class="sssj-note sssj-note--warn">' . esc_html( $err ) . '</div>';
		}
		echo '<form method="post" class="sssj-stack" action="' . esc_url( get_permalink() ) . '">';
		echo '<input type="hidden" name="' . esc_attr( self::ACTION_FIELD ) . '" value="register" />';
		wp_nonce_field( 'sssj_register', 'sssj_register_nonce' );
		echo '<p style="position:absolute;left:-9999px" aria-hidden="true"><label>' . esc_html__( 'Leave this blank', 'shuffles-social-services-jobs' ) . '<input type="text" name="sssj_hp" tabindex="-1" autocomplete="off" /></label></p>';
		echo self::redirect_field(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<label class="sssj-field"><span>' . esc_html__( 'Your name', 'shuffles-social-services-jobs' ) . '</span><input class="sssj-input" type="text" name="full_name" autocomplete="name" required /></label>';
		echo '<label class="sssj-field"><span>' . esc_html__( 'Email', 'shuffles-social-services-jobs' ) . '</span><input class="sssj-input" type="email" name="user_email" autocomplete="email" required /></label>';
		echo '<label class="sssj-field"><span>' . esc_html__( 'Choose a password (8+ characters)', 'shuffles-social-services-jobs' ) . '</span><input class="sssj-input" type="password" name="user_password" autocomplete="new-password" minlength="8" required /></label>';
		echo '<div style="margin-top:6px"><button type="submit" class="sssj-btn sssj-btn--primary">' . esc_html__( 'Create account', 'shuffles-social-services-jobs' ) . '</button></div>';
		if ( $login ) {
			echo '<p class="description" style="margin-top:10px">' . esc_html__( 'Already have an account?', 'shuffles-social-services-jobs' ) . ' <a href="' . esc_url( $login ) . '">' . esc_html__( 'Log in', 'shuffles-social-services-jobs' ) . '</a></p>';
		}
		echo '</form></div></div>';
		return ob_get_clean();
	}
}
