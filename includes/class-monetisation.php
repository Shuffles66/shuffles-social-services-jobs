<?php
/**
 * Monetisation gates — the two end-user subscriptions (distinct from the resale licence).
 *
 * 1. Employer advertising subscription — free tier posts up to `free_active_listings`;
 *    a subscriber (PMPro level or filter) posts unlimited.
 * 2. Provider application-fee subscription — required to respond to participant needs / ABN tasks
 *    (rides the existing `shuffles_ssj_can_respond` filter).
 *
 * All gates are OFF unless `monetisation_enabled` is on, so a site is never accidentally locked.
 * Everything is filterable so FluentCart / custom logic can plug in. Enforced server-side.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Monetisation {

	public static function register() {
		add_filter( 'shuffles_ssj_can_respond', array( __CLASS__, 'gate_respond' ), 20, 3 );
	}

	private static function settings() {
		return new Shuffles_SSJ_Settings();
	}

	public static function enabled() {
		return '1' === (string) self::settings()->get( 'monetisation_enabled', '0' );
	}

	private static function has_pmpro_level( $uid, $level ) {
		$level = (int) $level;
		return $level > 0 && function_exists( 'pmpro_hasMembershipLevel' ) && pmpro_hasMembershipLevel( $level, (int) $uid );
	}

	/** The selected gating provider: 'pmpro' | 'fluentcart'. */
	public static function gating_provider() {
		$p = sanitize_key( (string) self::settings()->get( 'gating_provider', 'pmpro' ) );
		return in_array( $p, array( 'pmpro', 'fluentcart' ), true ) ? $p : 'pmpro';
	}

	/** The configured subscription id for a side under the active provider (PMPro level or FluentCart product). */
	private static function sub_setting( $which ) {
		if ( 'fluentcart' === self::gating_provider() ) {
			return (int) self::settings()->get( 'advertiser' === $which ? 'advertiser_fc_product' : 'provider_fc_product', 0 );
		}
		return (int) self::settings()->get( 'advertiser' === $which ? 'advertiser_pmpro_level' : 'provider_pmpro_level', 0 );
	}

	/**
	 * Does the ACTIVE provider grant this user the given subscription? Per-user only — no
	 * admin/licence bypass (this is the base used by featured placement + the public checks).
	 *
	 * @param int    $uid
	 * @param string $which 'advertiser' | 'provider'
	 * @return bool
	 */
	public static function provider_grants( $uid, $which ) {
		$uid = (int) $uid;
		$ok  = false;
		if ( $uid ) {
			$ok = ( 'fluentcart' === self::gating_provider() )
				? self::fluentcart_active( $uid, self::sub_setting( $which ) )
				: self::has_pmpro_level( $uid, self::sub_setting( $which ) );
		}
		$filter = ( 'advertiser' === $which ) ? 'shuffles_ssj_has_advertiser_sub' : 'shuffles_ssj_has_provider_sub';
		return (bool) apply_filters( $filter, (bool) $ok, $uid );
	}

	/** Site-owner resale licence (or SHUFFLES_SSJ_PRO) bypasses the end-user gates. */
	private static function licence_bypass() {
		return class_exists( 'Shuffles_SSJ_License' ) && Shuffles_SSJ_License::is_pro();
	}

	/** Does the user hold the employer advertising subscription? (resale licence bypasses). */
	public static function has_advertiser_sub( $uid ) {
		if ( self::licence_bypass() ) {
			return (bool) apply_filters( 'shuffles_ssj_has_advertiser_sub', true, (int) $uid );
		}
		return self::provider_grants( $uid, 'advertiser' );
	}

	/** Does the user hold the provider application-fee subscription? (resale licence bypasses). */
	public static function has_provider_sub( $uid ) {
		if ( self::licence_bypass() ) {
			return (bool) apply_filters( 'shuffles_ssj_has_provider_sub', true, (int) $uid );
		}
		return self::provider_grants( $uid, 'provider' );
	}

	/**
	 * Does the user have an active FluentCart subscription (optionally to a specific product)?
	 *
	 * Defensive by design: returns false when the FluentCart CORE plugin isn't active (only
	 * FluentCart Pro is installed on some sites, which needs the free core), never fatals, and
	 * is fully overridable via `shuffles_ssj_fluentcart_active`. Confirmed against the FluentCart
	 * model API (FluentCart\App\Models\{Customer,Subscription}; Customer.user_id, Subscription.status).
	 *
	 * @param int $uid
	 * @param int $product_id 0 = any active subscription qualifies.
	 * @return bool
	 */
	public static function fluentcart_active( $uid, $product_id = 0 ) {
		$uid = (int) $uid;
		if ( ! $uid ) {
			return false;
		}
		$pre = apply_filters( 'shuffles_ssj_fluentcart_active', null, $uid, (int) $product_id );
		if ( null !== $pre ) {
			return (bool) $pre;
		}
		if ( ! class_exists( '\FluentCart\App\Models\Subscription' ) || ! class_exists( '\FluentCart\App\Models\Customer' ) ) {
			return false; // FluentCart core not active.
		}
		try {
			$cust_ids = \FluentCart\App\Models\Customer::query()->where( 'user_id', $uid )->pluck( 'id' )->all();
			if ( empty( $cust_ids ) ) {
				return false;
			}
			$statuses = apply_filters( 'shuffles_ssj_fluentcart_active_statuses', array( 'active', 'trialing', 'pending' ) );
			$subs     = \FluentCart\App\Models\Subscription::query()
				->whereIn( 'customer_id', $cust_ids )
				->whereIn( 'status', $statuses )
				->get();
			if ( ! $subs || $subs->isEmpty() ) {
				return false;
			}
			$want = (int) $product_id;
			if ( $want <= 0 ) {
				return true; // any active subscription qualifies
			}
			foreach ( $subs as $sub ) {
				if ( in_array( $want, self::fc_subscription_product_ids( $sub ), true ) ) {
					return true;
				}
			}
			return false;
		} catch ( \Throwable $e ) {
			return false;
		}
	}

	/** Best-effort product id(s) behind a FluentCart subscription, fully filterable. */
	private static function fc_subscription_product_ids( $sub ) {
		$ids = array();
		foreach ( array( 'product_id', 'post_id' ) as $col ) {
			if ( isset( $sub->$col ) && $sub->$col ) {
				$ids[] = (int) $sub->$col;
			}
		}
		if ( isset( $sub->variation_id ) && $sub->variation_id && class_exists( '\FluentCart\App\Models\ProductVariation' ) ) {
			try {
				$v = \FluentCart\App\Models\ProductVariation::query()->find( (int) $sub->variation_id );
				if ( $v ) {
					foreach ( array( 'post_id', 'product_id' ) as $col ) {
						if ( isset( $v->$col ) && $v->$col ) {
							$ids[] = (int) $v->$col;
						}
					}
				}
			} catch ( \Throwable $e ) {
				$ids = $ids; // no-op; stay defensive
			}
		}
		$ids = apply_filters( 'shuffles_ssj_fluentcart_sub_product_ids', $ids, $sub );
		return array_values( array_unique( array_filter( array_map( 'intval', (array) $ids ) ) ) );
	}

	/**
	 * May this user post another job? (free-tier listing cap unless subscribed).
	 *
	 * @return bool
	 */
	/**
	 * May this provider be LISTED in the organisation directory? Providers pay to list.
	 * Free when monetisation is off; admins + advertiser/provider subscribers qualify.
	 */
	public static function can_list_directory( $uid ) {
		if ( ! self::enabled() ) {
			return true;
		}
		$uid = (int) $uid;
		if ( ! $uid ) {
			return false;
		}
		$ok = user_can( $uid, 'manage_options' ) || self::has_advertiser_sub( $uid ) || self::has_provider_sub( $uid );
		return (bool) apply_filters( 'shuffles_ssj_can_list_directory', $ok, $uid );
	}

	public static function can_post_job( $uid ) {
		if ( ! self::enabled() ) {
			return true;
		}
		$uid = (int) $uid;
		if ( ! $uid ) {
			return false;
		}
		// Participants employing directly post for FREE — only providers/businesses are charged.
		if ( class_exists( 'Shuffles_SSJ_Roles' ) && Shuffles_SSJ_Roles::is_participant( $uid ) && ! Shuffles_SSJ_Roles::is_provider( $uid ) ) {
			return (bool) apply_filters( 'shuffles_ssj_can_post_job', true, $uid );
		}
		if ( user_can( $uid, 'manage_options' ) || self::has_advertiser_sub( $uid ) ) {
			return (bool) apply_filters( 'shuffles_ssj_can_post_job', true, $uid );
		}
		$free = (int) self::settings()->get( 'free_active_listings', 1 );
		if ( $free <= 0 ) {
			return (bool) apply_filters( 'shuffles_ssj_can_post_job', true, $uid ); // 0 = unlimited free
		}
		$active = count(
			get_posts(
				array(
					'post_type'      => 'sssj_job',
					'post_status'    => 'publish',
					'author'         => $uid,
					'posts_per_page' => $free + 1,
					'fields'         => 'ids',
				)
			)
		);
		return (bool) apply_filters( 'shuffles_ssj_can_post_job', $active < $free, $uid );
	}

	/** Short reason for the upgrade prompt. */
	public static function post_job_block_reason() {
		$free = (int) self::settings()->get( 'free_active_listings', 1 );
		/* translators: %d: number of free active listings */
		return sprintf( _n( 'You have reached your free limit of %d active job. Subscribe to post more.', 'You have reached your free limit of %d active jobs. Subscribe to post more.', $free, 'shuffles-social-services-jobs' ), $free );
	}

	/**
	 * Gate responding to participant needs / ABN tasks behind the provider subscription.
	 * Only tightens (never loosens) — runs after the ABN check.
	 *
	 * @param bool   $ok    Result so far (ABN/login checks).
	 * @param string $basis 'abn' | 'tfn'.
	 * @param int    $uid   User.
	 * @return bool
	 */
	public static function gate_respond( $ok, $basis, $uid ) {
		if ( ! $ok || ! self::enabled() || 'abn' !== $basis ) {
			return $ok;
		}
		if ( user_can( (int) $uid, 'manage_options' ) ) {
			return true;
		}
		return self::has_provider_sub( $uid );
	}

	/* ------------------------------------------------------------------ *
	 * Featured placement — the "+ featured" half of the advertiser sub.
	 * Boards already sort `menu_order DESC, date DESC`, so a featured job
	 * (menu_order = 1) floats above the rest with NO change to the query
	 * layer. We keep `is_promoted` meta (for the badge) and `menu_order`
	 * synced to live subscription status at post time and on the daily cron.
	 * ------------------------------------------------------------------ */

	/**
	 * Is this job featured? Featured = the AUTHOR holds the employer advertising subscription.
	 *
	 * Deliberately does NOT honour the site-wide resale licence (`License::is_pro`) — that
	 * would feature EVERY job on a licensed site, defeating the purpose. This is a
	 * per-advertiser distinction only (PMPro level, admin, or the filter override).
	 *
	 * @param int $job_id Job post ID.
	 * @return bool
	 */
	public static function is_job_featured( $job_id ) {
		if ( ! self::enabled() ) {
			return false;
		}
		$author = (int) get_post_field( 'post_author', (int) $job_id );
		if ( ! $author ) {
			return false;
		}
		$featured = user_can( $author, 'manage_options' ) || self::provider_grants( $author, 'advertiser' );
		return (bool) apply_filters( 'shuffles_ssj_is_job_featured', (bool) $featured, (int) $job_id, $author );
	}

	/**
	 * Sync a single job's featured state into `is_promoted` meta + `menu_order`.
	 * menu_order is written directly (not via wp_update_post) to avoid re-firing save
	 * hooks and bumping post_modified on the daily refresh.
	 *
	 * @param int $job_id Job post ID.
	 * @return bool The resolved featured state.
	 */
	public static function refresh_job_feature_flag( $job_id ) {
		$job_id = (int) $job_id;
		if ( ! $job_id ) {
			return false;
		}
		$featured = self::is_job_featured( $job_id );
		update_post_meta( $job_id, 'is_promoted', $featured ? 1 : 0 );

		$desired = $featured ? 1 : 0;
		if ( (int) get_post_field( 'menu_order', $job_id ) !== $desired ) {
			global $wpdb;
			$wpdb->update( $wpdb->posts, array( 'menu_order' => $desired ), array( 'ID' => $job_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			clean_post_cache( $job_id );
		}
		return $featured;
	}

	/**
	 * Daily refresh of every published job's featured state, so a lapsed subscription
	 * un-features and a new one features without a re-save. Batched.
	 *
	 * @param int $limit Max jobs to process.
	 */
	public static function sync_featured( $limit = 500 ) {
		$ids = get_posts(
			array(
				'post_type'      => 'sssj_job',
				'post_status'    => 'publish',
				'posts_per_page' => (int) $limit,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		foreach ( $ids as $id ) {
			self::refresh_job_feature_flag( $id );
		}
	}
}
