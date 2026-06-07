<?php
/**
 * Smart, synonym-aware keyword search (C5).
 *
 * The plain WP `s=` search requires EVERY word to appear and knows nothing about sector
 * jargon — so "support work" misses "Disability Support Worker", "carer" or "DSW". This
 * class expands a search phrase into a set of related terms (the original phrase, its
 * individual words, and any matching synonym group) and OR-matches them against the post
 * title / excerpt / content via the `posts_search` SQL filter. OR-matching makes search
 * BROADER, not narrower — it never zeroes out a result the user clearly meant.
 *
 * AI-ready: term expansion runs through the `shuffles_ssj_search_expand_terms` filter, so a
 * future AI expander can refine / add terms without touching any query code. The synonym
 * table itself is filterable via `shuffles_ssj_search_synonyms`. Deterministic today, swappable
 * tomorrow — no member-facing copy ever names the vendor (house rule).
 *
 * Activation: a query opts in by setting the `sssj_smart_search` query var (done in
 * Shuffles_SSJ_Query). Queries without it are untouched.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Search {

	const QV = 'sssj_smart_search';

	public static function init() {
		add_filter( 'posts_search', array( __CLASS__, 'filter_search' ), 10, 2 );
	}

	/**
	 * Synonym / related-term groups (lower-case). Each group is treated as bidirectional:
	 * matching any member pulls in the whole group. Tuned for NDIS / aged-care / social-services.
	 *
	 * @return array[]
	 */
	public static function synonyms() {
		return apply_filters(
			'shuffles_ssj_search_synonyms',
			array(
				array( 'support worker', 'support work', 'disability support worker', 'dsw', 'carer', 'care worker', 'personal care assistant', 'pca', 'community support worker', 'support' ),
				array( 'aged care', 'aged-care', 'aged care worker', 'elderly care', 'home care', 'in-home care' ),
				array( 'nurse', 'registered nurse', 'rn', 'enrolled nurse', 'nursing', 'community nurse' ),
				array( 'sil', 'supported independent living', 'accommodation support', 'sda', 'specialist disability accommodation' ),
				array( 'respite', 'short term accommodation', 'sta', 'short-term accommodation' ),
				array( 'cleaner', 'cleaning', 'domestic assistance', 'house cleaning', 'household tasks' ),
				array( 'gardener', 'gardening', 'lawn', 'lawn mowing', 'yard maintenance' ),
				array( 'support coordinator', 'support coordination', 'coordinator of supports', 'coordination' ),
				array( 'plan manager', 'plan management', 'plan managing' ),
				array( 'physio', 'physiotherapist', 'physiotherapy' ),
				array( 'ot', 'occupational therapist', 'occupational therapy' ),
				array( 'speech', 'speech pathologist', 'speech pathology', 'speech therapy', 'speechie' ),
				array( 'psychologist', 'psychology', 'mental health', 'counsellor', 'counselling' ),
				array( 'physical therapy', 'exercise physiologist', 'exercise physiology', 'ep' ),
				array( 'allied health', 'therapy', 'therapist' ),
				array( 'transport', 'driver', 'driving', 'community access', 'social and community participation' ),
				array( 'meal prep', 'meal preparation', 'cooking', 'meal support' ),
			)
		);
	}

	/**
	 * Expand a raw phrase into a deduped list of search terms (phrase + words + synonym groups).
	 *
	 * @param string $phrase Raw user search.
	 * @return string[]
	 */
	public static function expand( $phrase ) {
		$phrase = strtolower( trim( (string) $phrase ) );
		if ( '' === $phrase ) {
			return array();
		}

		$terms = array( $phrase );
		foreach ( preg_split( '/\s+/', $phrase ) as $word ) {
			$word = trim( $word );
			if ( strlen( $word ) >= 3 ) {
				$terms[] = $word;
			}
		}

		foreach ( self::synonyms() as $group ) {
			$hit = false;
			foreach ( $group as $syn ) {
				if ( false !== strpos( $phrase, $syn ) || in_array( $syn, $terms, true ) ) {
					$hit = true;
					break;
				}
			}
			if ( $hit ) {
				foreach ( $group as $syn ) {
					$terms[] = $syn;
				}
			}
		}

		$terms = array_values( array_unique( array_filter( array_map( 'trim', $terms ) ) ) );

		/**
		 * AI-ready hook: a future AI expander can add / re-rank / trim terms here.
		 *
		 * @param string[] $terms  Deterministically expanded terms.
		 * @param string   $phrase The raw search phrase.
		 */
		$terms = (array) apply_filters( 'shuffles_ssj_search_expand_terms', $terms, $phrase );

		// Safety cap so a runaway expander can't explode the SQL.
		return array_slice( array_values( array_unique( array_filter( $terms ) ) ), 0, 40 );
	}

	/**
	 * Replace the default search WHERE with an OR-LIKE across the expanded terms, but only for
	 * queries that opted in via the `sssj_smart_search` query var.
	 *
	 * @param string   $search Existing search SQL.
	 * @param WP_Query $query  The query.
	 * @return string
	 */
	public static function filter_search( $search, $query ) {
		$raw = $query->get( self::QV );
		if ( ! $raw ) {
			return $search;
		}
		$terms = self::expand( $raw );
		if ( empty( $terms ) ) {
			return $search;
		}

		global $wpdb;
		$clauses = array();
		foreach ( $terms as $t ) {
			$like      = '%' . $wpdb->esc_like( $t ) . '%';
			$clauses[] = $wpdb->prepare(
				"({$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.post_excerpt LIKE %s OR {$wpdb->posts}.post_content LIKE %s)",
				$like,
				$like,
				$like
			);
		}
		if ( empty( $clauses ) ) {
			return $search;
		}

		return ' AND (' . implode( ' OR ', $clauses ) . ') ';
	}
}
