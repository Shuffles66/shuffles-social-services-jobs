<?php
/**
 * Seeds industry-standard taxonomy terms from bundled JSON on activation.
 * Idempotent: existing terms are skipped; admins may freely edit/add/delete afterwards.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Taxonomy_Seeder {

	/**
	 * Taxonomy => seed file map.
	 *
	 * @return array
	 */
	private function map() {
		return array(
			'sssjt_category'           => 'seed-categories.json',
			'sssjt_role'               => 'seed-roles.json',
			'sssjt_qualification'      => 'seed-qualifications.json',
			'sssjt_compliance_profile' => 'seed-compliance-profiles.json',
			'sssjt_mode'               => 'seed-modes.json',
			'sssjt_employment_type'    => 'seed-employment-types.json',
			'sssjt_support_category'   => 'seed-support-categories.json',
			'sssjt_funding_source'     => 'seed-funding-sources.json',
		);
	}

	/**
	 * Seed all taxonomies. Skips if already seeded for this version unless forced.
	 *
	 * @param bool $force Re-run even if already seeded.
	 */
	public function seed_all( $force = false ) {
		if ( ! $force && get_option( 'shuffles_ssj_seeded' ) === SHUFFLES_SSJ_VERSION ) {
			return;
		}
		foreach ( $this->map() as $tax => $file ) {
			$this->seed_one( $tax, $file );
		}
		update_option( 'shuffles_ssj_seeded', SHUFFLES_SSJ_VERSION );
	}

	/**
	 * Seed a single taxonomy from its JSON file.
	 *
	 * @param string $tax  Taxonomy key.
	 * @param string $file Seed filename in data/.
	 */
	public function seed_one( $tax, $file ) {
		if ( ! taxonomy_exists( $tax ) ) {
			return;
		}
		$path = SHUFFLES_SSJ_DIR . 'data/' . $file;
		if ( ! is_readable( $path ) ) {
			return;
		}
		$json  = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- bundled local file.
		$items = json_decode( $json, true );
		if ( ! is_array( $items ) ) {
			return;
		}
		foreach ( $items as $item ) {
			$name = is_array( $item ) ? ( isset( $item['name'] ) ? $item['name'] : '' ) : (string) $item;
			$desc = is_array( $item ) && isset( $item['description'] ) ? $item['description'] : '';
			$name = trim( (string) $name );
			if ( '' === $name ) {
				continue;
			}
			if ( term_exists( $name, $tax ) ) {
				continue;
			}
			wp_insert_term( $name, $tax, array( 'description' => sanitize_text_field( $desc ) ) );
		}
	}
}
