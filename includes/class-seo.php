<?php
/**
 * SEO: JobPosting JSON-LD on public job pages; noindex on participant needs and
 * non-public worker profiles (privacy beats discoverability — always).
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_SEO {

	/** @var Shuffles_SSJ_Settings */
	private $settings;

	public function __construct( $settings ) {
		$this->settings = $settings;
	}

	public function register() {
		add_action( 'wp_head', array( $this, 'head' ), 1 );
	}

	public function head() {
		// Participant needs: never indexable.
		if ( is_singular( 'sssj_need' ) ) {
			echo "<meta name=\"robots\" content=\"noindex,nofollow\" />\n";
			return;
		}

		// Worker profiles: indexable only when explicitly public.
		if ( is_singular( 'sssj_worker' ) ) {
			$visibility = (string) get_post_meta( get_queried_object_id(), 'visibility', true );
			if ( 'public' !== $visibility ) {
				echo "<meta name=\"robots\" content=\"noindex,nofollow\" />\n";
			} else {
				$this->keywords_meta();
			}
			return;
		}

		// Organisation profiles: Organization structured data (public, indexable) — unless hidden.
		if ( is_singular( 'sssj_org' ) ) {
			if ( '1' === (string) get_post_meta( get_queried_object_id(), 'org_hidden', true ) ) {
				echo "<meta name=\"robots\" content=\"noindex,nofollow\" />\n";
				return;
			}
			$this->keywords_meta();
			if ( '1' === (string) $this->settings->get( 'seo_enabled', '1' ) ) {
				$this->org_jsonld( get_queried_object() );
			}
			return;
		}

		// Job ads: JobPosting structured data (Google for Jobs).
		if ( is_singular( 'sssj_job' ) ) {
			$this->keywords_meta();
			if ( '1' === (string) $this->settings->get( 'seo_enabled', '1' ) ) {
				$this->job_jsonld( get_queried_object() );
			}
		}
	}

	/**
	 * Emit a keywords meta tag from the configured "Focus programs" (branding/SEO), on indexable
	 * plugin pages only. Lists NDIS / Aged Care / DVA / Foundational Supports / Thriving Kids etc.
	 */
	private function keywords_meta() {
		$kw = trim( (string) $this->settings->get( 'focus_programs', '' ) );
		if ( '' === $kw ) {
			return;
		}
		echo '<meta name="keywords" content="' . esc_attr( $kw ) . "\" />\n";
	}

	/** Place node from suburb/state/postcode (null if all empty). */
	private function place( $sub, $state, $pc ) {
		if ( '' === trim( (string) $sub . (string) $state . (string) $pc ) ) {
			return null;
		}
		return array(
			'@type'   => 'Place',
			'address' => array_filter(
				array(
					'@type'           => 'PostalAddress',
					'addressLocality' => $sub,
					'addressRegion'   => $state,
					'postalCode'      => $pc,
					'addressCountry'  => 'AU',
				)
			),
		);
	}

	/** Emit Organization JSON-LD for an org profile (with all its locations). */
	private function org_jsonld( $post ) {
		if ( ! $post instanceof WP_Post ) {
			return;
		}
		$id   = $post->ID;
		$data = array(
			'@context' => 'https://schema.org/',
			'@type'    => 'Organization',
			'name'     => wp_strip_all_tags( get_the_title( $id ) ),
			'url'      => get_permalink( $id ),
		);
		$desc = wp_strip_all_tags( (string) $post->post_content );
		if ( $desc ) {
			$data['description'] = $desc;
		}
		$logo = get_the_post_thumbnail_url( $id, 'medium' );
		if ( $logo ) {
			$data['logo'] = $logo;
		}
		$same = array();
		$web  = (string) get_post_meta( $id, 'org_website', true );
		if ( $web ) {
			$same[] = $web;
		}
		if ( class_exists( 'Shuffles_SSJ_Org' ) ) {
			$same = array_merge( $same, Shuffles_SSJ_Org::social_urls( $id ) );
		}
		if ( $same ) {
			$data['sameAs'] = array_values( array_unique( $same ) );
		}
		$phone = (string) get_post_meta( $id, 'org_phone', true );
		if ( $phone ) {
			$data['telephone'] = $phone;
		}

		$places  = array();
		$primary = $this->place( get_post_meta( $id, 'location_suburb', true ), get_post_meta( $id, 'location_state', true ), get_post_meta( $id, 'location_postcode', true ) );
		if ( $primary ) {
			$places[] = $primary;
		}
		$extra = json_decode( (string) get_post_meta( $id, 'locations', true ), true );
		if ( is_array( $extra ) ) {
			foreach ( $extra as $loc ) {
				$p = $this->place(
					isset( $loc['suburb'] ) ? $loc['suburb'] : '',
					isset( $loc['state'] ) ? $loc['state'] : '',
					isset( $loc['postcode'] ) ? $loc['postcode'] : ''
				);
				if ( $p ) {
					$places[] = $p;
				}
			}
		}
		if ( $places ) {
			$data['location'] = $places;
		}

		echo "<script type=\"application/ld+json\">" . wp_json_encode( $data ) . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Emit JobPosting JSON-LD for a job.
	 *
	 * @param WP_Post $post Job post.
	 */
	private function job_jsonld( $post ) {
		if ( ! $post instanceof WP_Post ) {
			return;
		}
		$id = $post->ID;

		$data = array(
			'@context'    => 'https://schema.org/',
			'@type'       => 'JobPosting',
			'title'       => wp_strip_all_tags( get_the_title( $id ) ),
			'description' => wp_strip_all_tags( (string) $post->post_content ),
			'datePosted'  => get_post_time( 'Y-m-d', true, $id ),
			'directApply' => true,
		);

		$expires = (string) get_post_meta( $id, 'expires_at', true );
		if ( $expires ) {
			$data['validThrough'] = $expires;
		}

		$emp = wp_get_post_terms( $id, 'sssjt_employment_type', array( 'fields' => 'names' ) );
		if ( ! is_wp_error( $emp ) && ! empty( $emp ) ) {
			$data['employmentType'] = $this->map_employment_type( $emp[0] );
		}

		$org_name = get_the_author_meta( 'display_name', (int) $post->post_author );
		$data['hiringOrganization'] = array(
			'@type' => 'Organization',
			'name'  => $org_name ? $org_name : get_bloginfo( 'name' ),
		);

		$suburb   = (string) get_post_meta( $id, 'location_suburb', true );
		$state    = (string) get_post_meta( $id, 'location_state', true );
		$postcode = (string) get_post_meta( $id, 'location_postcode', true );
		if ( $suburb || $state || $postcode ) {
			$data['jobLocation'] = array(
				'@type'   => 'Place',
				'address' => array_filter(
					array(
						'@type'           => 'PostalAddress',
						'addressLocality' => $suburb,
						'addressRegion'   => $state,
						'postalCode'      => $postcode,
						'addressCountry'  => 'AU',
					)
				),
			);
		}

		$rate_min = (float) get_post_meta( $id, 'rate_min', true );
		$rate_max = (float) get_post_meta( $id, 'rate_max', true );
		$rate_unit = (string) get_post_meta( $id, 'rate_unit', true );
		if ( $rate_min > 0 || $rate_max > 0 ) {
			$data['baseSalary'] = array(
				'@type'    => 'MonetaryAmount',
				'currency' => 'AUD',
				'value'    => array_filter(
					array(
						'@type'    => 'QuantitativeValue',
						'minValue' => $rate_min > 0 ? $rate_min : null,
						'maxValue' => $rate_max > 0 ? $rate_max : null,
						'unitText' => $this->map_rate_unit( $rate_unit ),
					)
				),
			);
		}

		echo "<script type=\"application/ld+json\">" . wp_json_encode( $data ) . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	private function map_employment_type( $label ) {
		$l = strtolower( $label );
		if ( false !== strpos( $l, 'full' ) ) { return 'FULL_TIME'; }
		if ( false !== strpos( $l, 'part' ) ) { return 'PART_TIME'; }
		if ( false !== strpos( $l, 'casual' ) || false !== strpos( $l, 'on-call' ) ) { return 'TEMPORARY'; }
		if ( false !== strpos( $l, 'contract' ) || false !== strpos( $l, 'sole' ) || false !== strpos( $l, 'fee' ) ) { return 'CONTRACTOR'; }
		return 'OTHER';
	}

	private function map_rate_unit( $unit ) {
		switch ( $unit ) {
			case 'day':
				return 'DAY';
			case 'annum':
			case 'year':
				return 'YEAR';
			case 'hour':
			default:
				return 'HOUR';
		}
	}
}
