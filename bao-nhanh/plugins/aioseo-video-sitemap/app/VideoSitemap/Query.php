<?php
namespace AIOSEO\Plugin\Extend\VideoSitemap;

/**
 * Handles all complex queries for the sitemap.
 *
 * @since 1.0.0
 */
class Query {

	/**
	 * Returns all eligble video sitemap entries for a given post type.
	 *
	 * @since 1.0.0
	 *
	 * @param  mixed $postType       The post type(s). Either a singular string or an array of strings.
	 * @param  array $additionalArgs Any additional arguments for the post query.
	 * @return array                 The post objects.
	 */
	public function videoPosts( $postType, $additionalArgs = [] ) {
		// Set defaults.
		$fields = '`p`.`ID`, `p`.`post_modified_gmt`, `ap`.`videos`';

		// Override defaults if passed as additional arg.
		foreach ( $additionalArgs as $name => $value ) {
			$$name = esc_sql( $value );
			if ( 'root' === $name && $value ) {
				$fields = '`p`.`ID`';
			}
		}

		$query = aioseo()->db
			->start( 'aioseo_posts' . ' as ap' )
			->select( $fields )
			->join( 'posts as p', '`ap`.`post_id` = `p`.`ID`' )
			->where( '`ap`.`videos` IS NOT', null )
			->where( 'p.post_status', 'publish' )
			->where( 'p.post_password', '' )
			->whereIn( '`p`.`post_type`', $postType );

		$excludedPosts = aioseo()->sitemap->helpers->excludedPosts();
		if ( $excludedPosts ) {
			$query->whereRaw( "( `p`.`ID` NOT IN ( $excludedPosts ) )" );
		}

		$excludedTerms = aioseo()->sitemap->helpers->excludedTerms();
		if ( $excludedTerms ) {
			$termRelationshipsTable = aioseo()->db->db->prefix . 'term_relationships';
			$query->whereRaw("
				( `p`.`ID` NOT IN
					(
						SELECT `tr`.`object_id`
						FROM `$termRelationshipsTable` as tr
						WHERE `tr`.`term_taxonomy_id` IN ( $excludedTerms )
					)
				)" );
		}

		if ( ! aioseo()->helpers->isPostTypeNoindexed( $postType ) ) {
			$query->whereRaw( '( `ap`.`robots_noindex` IS NULL OR `ap`.`robots_default` = 1 OR `ap`.`robots_noindex` = 0 )' );
		} else {
			$query->whereRaw( '( `ap`.`robots_default` = 0 AND `ap`.`robots_noindex` = 0 )' );
		}

		if ( aioseo()->sitemap->indexes && empty( $additionalArgs['root'] ) ) {
			$query->limit( aioseo()->sitemap->linksPerIndex, aioseo()->sitemap->offset );
		}

		$posts = $query->orderBy( '`ap`.`ID` ASC' )
			->run()
			->result();

		if ( ! $posts ) {
			return [];
		}

		$remainingPosts      = [];
		$includeCustomFields = aioseo()->options->sitemap->video->advancedSettings->enable && aioseo()->options->sitemap->video->advancedSettings->customFields;
		foreach ( $posts as $post ) {
			$post->ID = intval( $post->ID );

			if ( ! $includeCustomFields ) {

				$remainingVideos = [];
				foreach ( json_decode( $post->videos ) as $video ) {
					if ( ! isset( $video->includedInCustomField ) ) {
						$remainingVideos[] = $video;
					}
				}

				if ( $remainingVideos && count( $remainingVideos ) ) {
					$post->videos     = wp_json_encode( $remainingVideos );
					$remainingPosts[] = $post;
				}
			} else {
				$remainingPosts[] = $post;
			}
		}

		return aioseo()->sitemap->query->filterPosts( $postType, $remainingPosts );
	}

	/**
	 * Returns all eligble video sitemap entries for a given taxonomy.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $taxonomy The name of the taxonomy.
	 * @param  boolean $limit    Whether the amount of queried terms should be limited.
	 * @return array             The sitemap entries.
	 */
	public function videoTerms( $taxonomy, $limit = true ) {
		if ( is_array( $taxonomy ) ) {
			$taxonomy = esc_sql( implode( ', ', $taxonomy ) );
		}

		$termRelationshipsTable = aioseo()->db->db->prefix . 'term_relationships';
		$termTaxonomyTable      = aioseo()->db->db->prefix . 'term_taxonomy';
		$query = aioseo()->db
			->start( 'aioseo_terms' . ' as at' )
			->select( '`at`.`term_id`, `at`.`videos`' )
			->where( '`at`.`videos` IS NOT', null )
			->whereRaw( '( `at`.`robots_noindex` IS NULL OR `at`.`robots_noindex` IS FALSE )' )
			->whereRaw( "
			( `at`.`term_id` IN
				(
					SELECT `tt`.`term_id`
					FROM `$termTaxonomyTable` as tt
					WHERE `tt`.`taxonomy` IN ( '$taxonomy' )
				)
			)" )
			->whereRaw( "
			( `at`.`term_id` IN
				(
					SELECT `tr`.`term_taxonomy_id`
					FROM `$termRelationshipsTable` as tr
				)
			)" );

		$excludedTerms = aioseo()->sitemap->helpers->excludedTerms();
		if ( $excludedTerms ) {
			$query->whereRaw("
				( `at`.`term_id` NOT IN
					(
						SELECT `tr`.`term_taxonomy_id`
						FROM `$termRelationshipsTable` as tr
						WHERE `tr`.`term_taxonomy_id` IN ( $excludedTerms )
					)
				)" );
		}

		if ( ! aioseo()->helpers->isTaxonomyNoindexed( $taxonomy ) ) {
			$query->whereRaw( '( `at`.`robots_noindex` IS NULL OR `at`.`robots_default` = 1 OR `at`.`robots_noindex` = 0 )' );
		} else {
			$query->whereRaw( '( `at`.`robots_default` = 0 AND `at`.`robots_noindex` = 0 )' );
		}

		if ( aioseo()->sitemap->indexes && $limit ) {
			$query->limit( aioseo()->sitemap->linksPerIndex, aioseo()->sitemap->offset );
		}

		$terms = $query->orderBy( '`at`.`term_id` ASC' )
			->run()
			->result();

		foreach ( $terms as $term ) {
			// Convert ID from string to int.
			$term->term_id = intval( $term->term_id );
			// Add taxonomy name to object manually instead of querying it to prevent redundant join.
			$term->taxonomy = $taxonomy;
		}
		return $terms;
	}

	/**
	 * Wipes all scanned data and as such forces the plugin to rescan the site.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function resetVideos() {
		aioseo()->db
			->update( 'aioseo_posts' )
			->set(
				[
					'videos'          => null,
					'video_scan_date' => null
				]
			)
			->run();

		aioseo()->db
			->update( 'aioseo_terms' )
			->set(
				[
					'videos'          => null,
					'video_scan_date' => null
				]
			)
			->run();
	}
}