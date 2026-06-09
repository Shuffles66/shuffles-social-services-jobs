<?php
/**
 * Resale licensing client (FluentCart). Gates PREMIUM features only, core boards always work.
 *
 * Design (locked with John): resell to other operators; hard-gate premium when unlicensed; vendor
 * store = shuffles.com.au. Validation runs on a daily cron + on-demand (never the hot path) and is
 * cached. A grace window keeps a previously-valid licence working if the vendor store is briefly
 * unreachable, so an outage can't switch off premium across customer sites. Your own sites can
 * bypass entirely with `define( 'SHUFFLES_SSJ_PRO', true )` in wp-config.php.
 *
 * NOTE: this plugin is public + GPL, so this is honour-system + an updates/support gate, NOT DRM.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_License {

	const OPTION     = 'shuffles_ssj_license';
	const GRACE_DAYS = 14;

	public static function vendor_url() {
		$u = (string) ( new Shuffles_SSJ_Settings() )->get( 'vendor_url', '' );
		if ( '' === $u ) {
			$u = 'https://shuffles.com.au';
		}
		return apply_filters( 'shuffles_ssj_vendor_url', $u );
	}

	private static function settings() {
		return new Shuffles_SSJ_Settings();
	}

	public static function key() {
		return (string) self::settings()->get( 'licence_key', '' );
	}

	public static function item_id() {
		return (string) apply_filters( 'shuffles_ssj_license_item_id', self::settings()->get( 'license_item_id', '' ) );
	}

	/** Cached status array: state, message, expires, checked_at, was_valid_at. */
	public static function status() {
		$d = get_option( self::OPTION, array() );
		return is_array( $d ) ? $d : array();
	}

	private static function store( $state, $extra = array() ) {
		$d = array_merge( array( 'state' => $state, 'checked_at' => current_time( 'mysql' ) ), $extra );
		update_option( self::OPTION, $d );
		return $d;
	}

	/**
	 * Are premium features unlocked?
	 *
	 * @return bool
	 */
	public static function is_pro() {
		if ( defined( 'SHUFFLES_SSJ_PRO' ) && SHUFFLES_SSJ_PRO ) {
			return (bool) apply_filters( 'shuffles_ssj_is_pro', true );
		}
		$s  = self::status();
		$ok = false;
		if ( ! empty( $s['state'] ) ) {
			if ( 'valid' === $s['state'] ) {
				$ok = empty( $s['expires'] ) || strtotime( $s['expires'] ) >= time();
			} elseif ( 'error' === $s['state'] && ! empty( $s['was_valid_at'] ) ) {
				// Vendor store unreachable but the licence was valid recently → grace.
				$ok = ( time() - strtotime( $s['was_valid_at'] ) ) < ( self::GRACE_DAYS * DAY_IN_SECONDS );
			}
		}
		return (bool) apply_filters( 'shuffles_ssj_is_pro', $ok );
	}

	/** Short, human-readable status for the admin screen. */
	public static function status_label() {
		if ( defined( 'SHUFFLES_SSJ_PRO' ) && SHUFFLES_SSJ_PRO ) {
			return __( 'Pro unlocked on this site (SHUFFLES_SSJ_PRO).', 'shuffles-social-services-jobs' );
		}
		$s = self::status();
		$state = isset( $s['state'] ) ? $s['state'] : 'unregistered';
		$msg   = isset( $s['message'] ) ? $s['message'] : '';
		return trim( ucfirst( $state ) . ( $msg ? ', ' . $msg : '' ) );
	}

	private static function call( $action ) {
		$url = add_query_arg(
			array(
				'fluent-cart'     => $action,
				'item_id'         => self::item_id(),
				'license_key'     => self::key(),
				'site_url'        => home_url(),
				'current_version' => SHUFFLES_SSJ_VERSION,
			),
			self::vendor_url()
		);
		$res = wp_remote_get( $url, array( 'timeout' => 12 ) );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$body = json_decode( wp_remote_retrieve_body( $res ), true );
		return array(
			'code' => (int) wp_remote_retrieve_response_code( $res ),
			'body' => is_array( $body ) ? $body : array(),
		);
	}

	private static function valid_from_body( $b ) {
		$st = isset( $b['license'] ) ? $b['license'] : ( isset( $b['status'] ) ? $b['status'] : '' );
		return in_array( $st, array( 'valid', 'active' ), true ) || ! empty( $b['success'] );
	}

	public static function activate() {
		if ( '' === self::key() || '' === self::item_id() ) {
			return self::store( 'unregistered', array( 'message' => __( 'Enter a licence key and product ID first.', 'shuffles-social-services-jobs' ) ) );
		}
		$r = self::call( 'activate_license' );
		if ( is_wp_error( $r ) ) {
			return self::store( 'error', array( 'message' => $r->get_error_message() ) );
		}
		if ( self::valid_from_body( $r['body'] ) ) {
			return self::store(
				'valid',
				array(
					'expires'      => isset( $r['body']['expires'] ) ? $r['body']['expires'] : '',
					'was_valid_at' => current_time( 'mysql' ),
					'message'      => __( 'Licence active.', 'shuffles-social-services-jobs' ),
				)
			);
		}
		$err = isset( $r['body']['error'] ) ? $r['body']['error'] : __( 'Licence could not be activated.', 'shuffles-social-services-jobs' );
		return self::store( 'invalid', array( 'message' => $err ) );
	}

	public static function deactivate() {
		if ( '' !== self::key() ) {
			self::call( 'deactivate_license' );
		}
		return self::store( 'unregistered', array( 'message' => __( 'Deactivated on this site.', 'shuffles-social-services-jobs' ) ) );
	}

	/** Daily cron re-validation (cached; grace-handled). */
	public static function check() {
		if ( '' === self::key() ) {
			return;
		}
		$r = self::call( 'check_license' );
		if ( is_wp_error( $r ) ) {
			$prev  = self::status();
			$extra = array( 'message' => __( 'Could not reach the licence server.', 'shuffles-social-services-jobs' ) );
			if ( ! empty( $prev['was_valid_at'] ) ) {
				$extra['was_valid_at'] = $prev['was_valid_at'];
			}
			self::store( 'error', $extra );
			return;
		}
		if ( self::valid_from_body( $r['body'] ) ) {
			self::store(
				'valid',
				array(
					'expires'      => isset( $r['body']['expires'] ) ? $r['body']['expires'] : '',
					'was_valid_at' => current_time( 'mysql' ),
					'message'      => __( 'Licence active.', 'shuffles-social-services-jobs' ),
				)
			);
		} else {
			$st = isset( $r['body']['license'] ) ? $r['body']['license'] : '';
			self::store( 'expired' === $st ? 'expired' : 'invalid', array( 'message' => __( 'Licence not valid for this site.', 'shuffles-social-services-jobs' ) ) );
		}
	}
}
