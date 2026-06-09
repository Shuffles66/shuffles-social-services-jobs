<?php
/**
 * Self-hosted geolocation engine, the plugin's OWN geo, independent of Geo my WP (and of
 * Google when no key is set).
 *
 * Provides:
 *   - distance_km()           : true great-circle (Haversine) distance.
 *   - order_ids_by_distance() : exact radius filter + nearest-first ordering of post IDs.
 *   - geocode()               : turn a typed suburb/postcode into lat/lng with NO Google key,
 *                               via a bundled AU dataset first, then keyless OpenStreetMap
 *                               (Nominatim), cached. Fully overridable with `shuffles_ssj_geocode`.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Geo {

	/** Earth radius (km). */
	const R_KM = 6371.0088;

	/**
	 * Great-circle distance between two points, in kilometres.
	 *
	 * @return float
	 */
	public static function distance_km( $lat1, $lng1, $lat2, $lng2 ) {
		$lat1 = (float) $lat1;
		$lng1 = (float) $lng1;
		$lat2 = (float) $lat2;
		$lng2 = (float) $lng2;
		$dlat = deg2rad( $lat2 - $lat1 );
		$dlng = deg2rad( $lng2 - $lng1 );
		$a    = sin( $dlat / 2 ) ** 2 + cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) * sin( $dlng / 2 ) ** 2;
		return self::R_KM * 2 * atan2( sqrt( $a ), sqrt( max( 0.0, 1 - $a ) ) );
	}

	/**
	 * Given candidate post IDs and a centre, return the IDs within $km ordered nearest-first.
	 * Posts without coordinates are dropped (they can't be placed on the map / radius).
	 *
	 * @param array $ids
	 * @param float $lat
	 * @param float $lng
	 * @param float $km
	 * @return array ordered post IDs
	 */
	public static function order_ids_by_distance( $ids, $lat, $lng, $km ) {
		$ids = array_filter( array_map( 'intval', (array) $ids ) );
		if ( empty( $ids ) ) {
			return array();
		}
		update_meta_cache( 'post', $ids ); // one query primes all the meta we read below.
		$dist = array();
		foreach ( $ids as $id ) {
			$la = (float) get_post_meta( $id, 'location_lat', true );
			$lo = (float) get_post_meta( $id, 'location_lng', true );
			if ( ! $la || ! $lo ) {
				continue;
			}
			$d = self::distance_km( $lat, $lng, $la, $lo );
			if ( $d <= (float) $km ) {
				$dist[ $id ] = $d;
			}
		}
		asort( $dist );
		return array_keys( $dist );
	}

	/**
	 * Geocode a free-text location (suburb, "Suburb STATE", or 4-digit postcode) to coordinates,
	 * WITHOUT requiring a Google key.
	 *
	 * Order: filter override → bundled AU dataset → OpenStreetMap/Nominatim (keyless, cached) → null.
	 *
	 * @param string $query
	 * @return array|null { lat, lng, label } or null
	 */
	public static function geocode( $query ) {
		$query = trim( (string) $query );
		if ( '' === $query ) {
			return null;
		}

		// Full override / pre-empt (e.g. to plug in a different provider).
		$pre = apply_filters( 'shuffles_ssj_geocode', null, $query );
		if ( is_array( $pre ) && isset( $pre['lat'], $pre['lng'] ) ) {
			return $pre;
		}

		$cache_key = 'sssj_geo_' . md5( strtolower( $query ) );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return is_array( $cached ) ? $cached : null;
		}

		$hit = self::dataset_lookup( $query );
		if ( ! $hit ) {
			$hit = self::osm_lookup( $query );
		}

		// Cache hits for a month, misses for a day (so a typo doesn't keep hitting OSM).
		set_transient( $cache_key, $hit ? $hit : 0, $hit ? MONTH_IN_SECONDS : DAY_IN_SECONDS );
		return $hit ? $hit : null;
	}

	/**
	 * Match against the bundled AU localities dataset (instant, offline).
	 *
	 * @param string $query
	 * @return array|null
	 */
	private static function dataset_lookup( $query ) {
		$rows = self::dataset();
		if ( empty( $rows ) ) {
			return null;
		}
		$q = strtolower( trim( $query ) );

		// 4-digit postcode anywhere in the query.
		if ( preg_match( '/\b(\d{4})\b/', $q, $m ) ) {
			foreach ( $rows as $r ) {
				if ( isset( $r['postcode'] ) && (string) $r['postcode'] === $m[1] ) {
					return self::row_to_result( $r );
				}
			}
		}

		// Locality name (optionally "suburb state"). Exact first, then prefix.
		$name = preg_replace( '/[,\s]+(nsw|vic|qld|sa|wa|tas|nt|act)\b.*$/i', '', $q );
		$name = trim( preg_replace( '/\b\d{4}\b/', '', $name ) );
		if ( '' === $name ) {
			return null;
		}
		foreach ( $rows as $r ) {
			if ( isset( $r['suburb'] ) && strtolower( $r['suburb'] ) === $name ) {
				return self::row_to_result( $r );
			}
		}
		foreach ( $rows as $r ) {
			if ( isset( $r['suburb'] ) && 0 === strpos( strtolower( $r['suburb'] ), $name ) ) {
				return self::row_to_result( $r );
			}
		}
		return null;
	}

	private static function row_to_result( $r ) {
		$label = trim( ( isset( $r['suburb'] ) ? $r['suburb'] : '' ) . ' ' . ( isset( $r['state'] ) ? $r['state'] : '' ) );
		return array(
			'lat'   => (float) $r['lat'],
			'lng'   => (float) $r['lng'],
			'label' => $label,
		);
	}

	/** Lazily load + cache the bundled dataset (filterable so a site can supply a fuller one). */
	private static function dataset() {
		static $cache = null;
		if ( null !== $cache ) {
			return $cache;
		}
		$rows = array();
		$file = SHUFFLES_SSJ_DIR . 'data/au-localities.json';
		if ( is_readable( $file ) ) {
			$decoded = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			if ( is_array( $decoded ) ) {
				$rows = $decoded;
			}
		}
		$cache = apply_filters( 'shuffles_ssj_geo_dataset', $rows );
		return $cache;
	}

	/**
	 * Keyless OpenStreetMap (Nominatim) lookup, AU-scoped. Respectful: cached by the caller,
	 * identified User-Agent, short timeout, single result. Returns null on any failure.
	 *
	 * @param string $query
	 * @return array|null
	 */
	private static function osm_lookup( $query ) {
		if ( 'osm' !== self::provider() ) {
			return null;
		}
		$url = add_query_arg(
			array(
				'q'            => $query . ', Australia',
				'format'       => 'jsonv2',
				'limit'        => 1,
				'countrycodes' => 'au',
				'addressdetails' => 0,
			),
			'https://nominatim.openstreetmap.org/search'
		);
		$res = wp_remote_get(
			$url,
			array(
				'timeout' => 6,
				'headers' => array(
					'User-Agent' => 'ShufflesSSJ/' . SHUFFLES_SSJ_VERSION . ' (' . home_url( '/' ) . ')',
					'Accept'     => 'application/json',
				),
			)
		);
		if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
			return null;
		}
		$data = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( ! is_array( $data ) || empty( $data[0]['lat'] ) || empty( $data[0]['lon'] ) ) {
			return null;
		}
		return array(
			'lat'   => (float) $data[0]['lat'],
			'lng'   => (float) $data[0]['lon'],
			'label' => isset( $data[0]['display_name'] ) ? sanitize_text_field( $data[0]['display_name'] ) : $query,
		);
	}

	/** Server-side geocoder provider: 'osm' (keyless, default) | 'off' (legacy: client Google only). */
	public static function provider() {
		$p = sanitize_key( (string) ( new Shuffles_SSJ_Settings() )->get( 'geocoder_provider', 'osm' ) );
		return in_array( $p, array( 'osm', 'off' ), true ) ? $p : 'osm';
	}

	/**
	 * Resolve coordinates for a stored listing from its address parts when none were captured
	 * client-side (i.e. no Google autocomplete). Returns [lat,lng] or null.
	 *
	 * @param string $suburb
	 * @param string $state
	 * @param string $postcode
	 * @return array|null [ 'lat' => float, 'lng' => float ]
	 */
	public static function geocode_parts( $suburb, $state, $postcode ) {
		$parts = array_filter( array( trim( (string) $suburb ), trim( (string) $state ), trim( (string) $postcode ) ) );
		if ( empty( $parts ) ) {
			return null;
		}
		$hit = self::geocode( implode( ' ', $parts ) );
		return $hit ? array( 'lat' => $hit['lat'], 'lng' => $hit['lng'] ) : null;
	}
}
