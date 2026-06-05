<?php
/**
 * ABN helper — normalise, checksum-validate, format.
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
}
