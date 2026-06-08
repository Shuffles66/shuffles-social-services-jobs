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
		// Enforce profile visibility / hidden-org at the page level, not just the directory query + noindex.
		add_action( 'template_redirect', array( $this, 'guard_private_singles' ) );
		// Keep non-public worker profiles and hidden orgs out of the core XML sitemap.
		add_filter( 'wp_sitemaps_posts_query_args', array( $this, 'sitemap_query_args' ), 10, 2 );
	}

	/**
	 * Block direct-permalink access to a worker profile whose visibility is not 'public', and to a
	 * hidden organisation, for anyone who could not see it in the directory. The owner and site admins
	 * can always preview their own page. Guests hitting a members-only profile are sent to log in;
	 * everyone else gets a clean 404 (so the page's existence is never confirmed).
	 */
	public function guard_private_singles() {
		if ( is_singular( 'sssj_worker' ) ) {
			$id  = get_queried_object_id();
			$vis = (string) get_post_meta( $id, 'visibility', true );
			if ( '' === $vis || 'public' === $vis ) {
				return;
			}
			if ( self::viewer_owns( (int) get_post_meta( $id, 'worker_user_id', true ) ) ) {
				return; // owner or site admin can always preview their own profile
			}
			if ( 'logged_in' === $vis ) {
				if ( is_user_logged_in() ) {
					return; // members-only profile, viewer is a logged-in member
				}
				wp_safe_redirect( wp_login_url( get_permalink( $id ) ) );
				exit;
			}
			// 'verified_only' (or any other non-public value): not shown to non-owners yet.
			self::block_404();
			return;
		}

		if ( is_singular( 'sssj_org' ) ) {
			$id = get_queried_object_id();
			if ( '1' !== (string) get_post_meta( $id, 'org_hidden', true ) ) {
				return;
			}
			$uid = get_current_user_id();
			if ( $uid && ( current_user_can( 'manage_options' )
				|| ( class_exists( 'Shuffles_SSJ_Org_Team' ) && Shuffles_SSJ_Org_Team::is_member( $id, $uid ) ) ) ) {
				return; // hidden orgs stay visible to their own team and to site admins
			}
			self::block_404();
		}
	}

	/** True if the current user is the given owner id or a site admin. */
	private static function viewer_owns( $owner_id ) {
		$uid = get_current_user_id();
		return $uid && ( $uid === (int) $owner_id || current_user_can( 'manage_options' ) );
	}

	/** Turn the current request into a 404 without confirming the resource exists. */
	private static function block_404() {
		global $wp_query;
		if ( $wp_query instanceof WP_Query ) {
			$wp_query->set_404();
		}
		status_header( 404 );
		nocache_headers();
	}

	/**
	 * Exclude non-public worker profiles and hidden organisations from the core wp-sitemap.xml.
	 * (sssj_need is registered non-public, so core already omits it.)
	 *
	 * @param array  $args      WP_Query args for the sitemap provider.
	 * @param string $post_type Current post type.
	 * @return array
	 */
	public function sitemap_query_args( $args, $post_type ) {
		$mq = ( isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ) ? $args['meta_query'] : array();
		if ( 'sssj_worker' === $post_type ) {
			$mq[] = array( 'key' => 'visibility', 'value' => 'public', 'compare' => '=' );
			$args['meta_query'] = $mq;
		} elseif ( 'sssj_org' === $post_type ) {
			$mq[] = array(
				'relation' => 'OR',
				array( 'key' => 'org_hidden', 'compare' => 'NOT EXISTS' ),
				array( 'key' => 'org_hidden', 'value' => '1', 'compare' => '!=' ),
			);
			$args['meta_query'] = $mq;
		}
		return $args;
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

		$desc = trim( wp_strip_all_tags( (string) $post->post_content ) );
		if ( '' === $desc ) {
			$desc = wp_strip_all_tags( get_the_title( $id ) ); // Google requires a non-empty description.
		}
		$data = array(
			'@context'    => 'https://schema.org/',
			'@type'       => 'JobPosting',
			'title'       => wp_strip_all_tags( get_the_title( $id ) ),
			'description' => $desc,
			'datePosted'  => get_post_time( 'Y-m-d', true, $id ),
			'directApply' => true,
			'identifier'  => array(
				'@type' => 'PropertyValue',
				'name'  => get_bloginfo( 'name' ),
				'value' => (string) $id,
			),
		);

		$expires = (string) get_post_meta( $id, 'expires_at', true );
		if ( $expires ) {
			$data['validThrough'] = $expires;
		}

		$emp = wp_get_post_terms( $id, 'sssjt_employment_type', array( 'fields' => 'names' ) );
		if ( ! is_wp_error( $emp ) && ! empty( $emp ) ) {
			$data['employmentType'] = $this->map_employment_type( $emp[0] );
		}

		// Anonymous ads keep the advertiser's name out of structured data / search.
		if ( get_post_meta( $id, 'is_anonymous', true ) ) {
			$org_name = __( 'Private advertiser', 'shuffles-social-services-jobs' );
		} else {
			$org_name = get_the_author_meta( 'display_name', (int) $post->post_author );
			$org_name = $org_name ? $org_name : get_bloginfo( 'name' );
		}
		$data['hiringOrganization'] = array(
			'@type' => 'Organization',
			'name'  => $org_name,
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

		// Remote / hybrid roles: Google for Jobs wants jobLocationType + applicantLocationRequirements.
		$mode = strtolower( (string) get_post_meta( $id, 'work_mode', true ) );
		if ( false !== strpos( $mode, 'remote' ) || false !== strpos( $mode, 'hybrid' ) ) {
			$data['jobLocationType']               = 'TELECOMMUTE';
			$data['applicantLocationRequirements'] = array(
				'@type' => 'Country',
				'name'  => 'Australia',
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
