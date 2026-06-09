<?php
/**
 * ABN helper, normalise, checksum-validate, format.
 *
 * Recording ABNs is critical to the ABN/TFN model. This mirrors the proven pattern from
 * the Shuffles Reference Check plugin, with the addition of the official modulus-89 checksum.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_ABN {

	/**
	 * Strip everything but digits.
	 *
	 * @param string $abn Raw ABN.
	 * @return string Digits only.
	 */
	public static function normalise( $abn ) {
		return preg_replace( '/\D+/', '', (string) $abn );
	}

	/**
	 * Validate an ABN using the ATO modulus-89 checksum.
	 *
	 * @param string $abn Raw or normalised ABN.
	 * @return bool
	 */
	public static function is_valid( $abn ) {
		$abn = self::normalise( $abn );
		if ( 11 !== strlen( $abn ) ) {
			return false;
		}
		$weights = array( 10, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19 );
		$sum     = 0;
		for ( $i = 0; $i < 11; $i++ ) {
			$digit = (int) $abn[ $i ];
			if ( 0 === $i ) {
				$digit -= 1; // Subtract 1 from the first digit.
			}
			$sum += $digit * $weights[ $i ];
		}
		return 0 === ( $sum % 89 );
	}

	/**
	 * Pretty-print as "12 345 678 901" when valid-length.
	 *
	 * @param string $abn Raw ABN.
	 * @return string
	 */
	public static function format( $abn ) {
		$abn = self::normalise( $abn );
		if ( 11 !== strlen( $abn ) ) {
			return $abn;
		}
		return substr( $abn, 0, 2 ) . ' ' . substr( $abn, 2, 3 ) . ' ' . substr( $abn, 5, 3 ) . ' ' . substr( $abn, 8, 3 );
	}

	/* --------------------------------------------------------------- ABR lookup */

	/** The configured ABR Web Services GUID (free registration), '' if not set. */
	public static function abr_guid() {
		$s = get_option( 'shuffles_ssj_settings', array() );
		return ( is_array( $s ) && ! empty( $s['abr_guid'] ) ) ? trim( (string) $s['abr_guid'] ) : '';
	}

	/**
	 * Look up an ABN against the Australian Business Register (ABN Lookup JSON web service).
	 * Returns [ abn, status, name, type ] or null (no GUID / invalid / network error).
	 * Requires a free ABR GUID (Settings → Compliance). Cached for a day per ABN.
	 *
	 * @param string $abn Raw or normalised ABN.
	 * @return array|null
	 */
	public static function abr_lookup( $abn ) {
		$abn  = self::normalise( $abn );
		$guid = self::abr_guid();
		if ( 11 !== strlen( $abn ) || '' === $guid ) {
			return null;
		}
		$cache_key = 'sssj_abr_' . $abn;
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		$url  = add_query_arg( array( 'abn' => $abn, 'guid' => $guid ), 'https://abr.business.gov.au/json/AbnDetails.aspx' );
		$resp = wp_remote_get( $url, array( 'timeout' => 15 ) );
		if ( is_wp_error( $resp ) || 200 !== (int) wp_remote_retrieve_response_code( $resp ) ) {
			return null;
		}
		$body = trim( (string) wp_remote_retrieve_body( $resp ) );
		// The endpoint returns JSONP: callback({...}). Unwrap to the JSON object.
		if ( preg_match( '/\(\s*(\{.*\})\s*\)\s*;?\s*$/s', $body, $m ) ) {
			$body = $m[1];
		}
		$data = json_decode( $body, true );
		if ( ! is_array( $data ) || empty( $data['Abn'] ) ) {
			return null;
		}
		$names = array();
		if ( ! empty( $data['BusinessName'] ) && is_array( $data['BusinessName'] ) ) {
			foreach ( $data['BusinessName'] as $bn ) {
				$bn = trim( (string) $bn );
				if ( '' !== $bn ) {
					$names[] = $bn;
				}
			}
		}
		$out = array(
			'abn'         => (string) $data['Abn'],
			'status'      => isset( $data['AbnStatus'] ) ? (string) $data['AbnStatus'] : '',
			'status_from' => isset( $data['AbnStatusEffectiveFrom'] ) ? (string) $data['AbnStatusEffectiveFrom'] : '',
			'name'        => isset( $data['EntityName'] ) ? (string) $data['EntityName'] : '',
			'type'        => isset( $data['EntityTypeName'] ) ? (string) $data['EntityTypeName'] : '',
			'type_code'   => isset( $data['EntityTypeCode'] ) ? (string) $data['EntityTypeCode'] : '',
			'acn'         => isset( $data['Acn'] ) ? (string) $data['Acn'] : '',
			'gst'         => isset( $data['Gst'] ) ? (string) $data['Gst'] : '',
			'state'       => isset( $data['AddressState'] ) ? (string) $data['AddressState'] : '',
			'postcode'    => isset( $data['AddressPostcode'] ) ? (string) $data['AddressPostcode'] : '',
			'address_date' => isset( $data['AddressDate'] ) ? (string) $data['AddressDate'] : '',
			'names'       => $names,
			'message'     => isset( $data['Message'] ) ? (string) $data['Message'] : '',
		);
		$out['details'] = self::format_details( $out );
		set_transient( $cache_key, $out, DAY_IN_SECONDS );
		return $out;
	}

	/**
	 * Build a plain-text, multi-line record of EVERYTHING the ABR returned (entity + trading names,
	 * status, type, ACN, GST, main location). Stored verbatim so admins/owners can see the full
	 * register feedback. National audience: the ABR is the single source of truth for AU businesses.
	 *
	 * @param array $r abr_lookup result.
	 * @return string
	 */
	public static function format_details( $r ) {
		$lines = array();
		if ( ! empty( $r['name'] ) ) {
			$lines[] = __( 'Entity name:', 'shuffles-social-services-jobs' ) . ' ' . $r['name'];
		}
		if ( ! empty( $r['names'] ) ) {
			$lines[] = __( 'Trading / business name(s):', 'shuffles-social-services-jobs' ) . ' ' . implode( ', ', $r['names'] );
		}
		if ( ! empty( $r['abn'] ) ) {
			$lines[] = __( 'ABN:', 'shuffles-social-services-jobs' ) . ' ' . self::format( $r['abn'] );
		}
		if ( ! empty( $r['acn'] ) ) {
			$lines[] = __( 'ACN:', 'shuffles-social-services-jobs' ) . ' ' . $r['acn'];
		}
		if ( ! empty( $r['status'] ) ) {
			$s = $r['status'];
			if ( ! empty( $r['status_from'] ) ) {
				$s .= ' (' . __( 'from', 'shuffles-social-services-jobs' ) . ' ' . $r['status_from'] . ')';
			}
			$lines[] = __( 'ABN status:', 'shuffles-social-services-jobs' ) . ' ' . $s;
		}
		if ( ! empty( $r['type'] ) ) {
			$lines[] = __( 'Entity type:', 'shuffles-social-services-jobs' ) . ' ' . $r['type'] . ( ! empty( $r['type_code'] ) ? ' (' . $r['type_code'] . ')' : '' );
		}
		$lines[] = __( 'GST:', 'shuffles-social-services-jobs' ) . ' ' . ( ! empty( $r['gst'] ) ? ( __( 'Registered from', 'shuffles-social-services-jobs' ) . ' ' . $r['gst'] ) : __( 'Not currently registered', 'shuffles-social-services-jobs' ) );
		$loc = trim( (string) ( $r['state'] ?? '' ) . ' ' . (string) ( $r['postcode'] ?? '' ) );
		if ( '' !== $loc ) {
			$lines[] = __( 'Main business location:', 'shuffles-social-services-jobs' ) . ' ' . $loc;
		}
		if ( ! empty( $r['message'] ) ) {
			$lines[] = __( 'Note:', 'shuffles-social-services-jobs' ) . ' ' . $r['message'];
		}
		$lines[] = __( 'Checked:', 'shuffles-social-services-jobs' ) . ' ' . current_time( 'Y-m-d H:i' ) . ', ' . __( 'source: Australian Business Register (abr.business.gov.au)', 'shuffles-social-services-jobs' );
		return implode( "\n", $lines );
	}

	/**
	 * Fired on shuffles_ssj_abn_recorded, enrich the entity with the ABR result (name + status).
	 * No-op without a GUID. Stored in _sssj_abr_name / _sssj_abr_status / _sssj_abr_checked.
	 */
	public static function on_abn_recorded( $abn, $entity_type, $entity_id ) {
		$entity_id = (int) $entity_id;
		if ( ! $entity_id ) {
			return;
		}
		$res = self::abr_lookup( $abn );
		if ( ! $res ) {
			return;
		}
		update_post_meta( $entity_id, '_sssj_abr_name', $res['name'] );
		update_post_meta( $entity_id, '_sssj_abr_status', $res['status'] );
		update_post_meta( $entity_id, '_sssj_abr_checked', current_time( 'mysql' ) );
		// Full register feedback (incl. trading/business names) in one large recorded field.
		update_post_meta( $entity_id, '_sssj_abr_details', isset( $res['details'] ) ? (string) $res['details'] : '' );
		update_post_meta( $entity_id, '_sssj_abr_names', ! empty( $res['names'] ) ? implode( ', ', (array) $res['names'] ) : '' );
	}

	/** The stored full ABR record text for an entity ('' if never checked). Read-only register data. */
	public static function abr_record_text( $entity_id ) {
		return (string) get_post_meta( (int) $entity_id, '_sssj_abr_details', true );
	}

	/** Recorded trading / business names for an entity ('' if none). */
	public static function abr_trading_names( $entity_id ) {
		return (string) get_post_meta( (int) $entity_id, '_sssj_abr_names', true );
	}

	/**
	 * Read-only display block of the full ABR record (entity + trading names, status, type, GST,
	 * location). '' if never checked. This is the Register's own public data, shown verbatim.
	 */
	public static function abr_details_html( $entity_id ) {
		$text = self::abr_record_text( $entity_id );
		if ( '' === $text ) {
			return '';
		}
		return '<div class="sssj-abr"><h3 style="margin:0 0 6px">🏢 ' . esc_html__( 'Australian Business Register', 'shuffles-social-services-jobs' ) . '</h3>'
			. '<pre class="sssj-abr__record">' . esc_html( $text ) . '</pre>'
			. '<p class="description">' . esc_html__( 'Recorded automatically from the Australian Business Register. Read-only, it cannot be edited here.', 'shuffles-social-services-jobs' ) . '</p></div>';
	}

	/** A badge for a card/profile showing the ABR-verified entity name + status, '' if not checked. */
	public static function abr_badge_html( $entity_id ) {
		$status = (string) get_post_meta( (int) $entity_id, '_sssj_abr_status', true );
		if ( '' === $status ) {
			return '';
		}
		$name   = (string) get_post_meta( (int) $entity_id, '_sssj_abr_name', true );
		$active = ( false !== stripos( $status, 'active' ) );
		$cls    = $active ? ' sssj-badge--verified' : '';
		$label  = $active ? __( 'ABR Active', 'shuffles-social-services-jobs' ) : $status;
		$text   = $name ? ( $label . ' · ' . $name ) : $label;
		return '<span class="sssj-badge' . $cls . '" title="' . esc_attr__( 'Verified against the Australian Business Register', 'shuffles-social-services-jobs' ) . '">🏢 ' . esc_html( $text ) . '</span>';
	}
}
