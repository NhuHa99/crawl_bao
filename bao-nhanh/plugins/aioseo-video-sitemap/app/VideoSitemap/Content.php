<?php
namespace AIOSEO\Plugin\Extend\VideoSitemap;

/**
 * Determines which content should be included in the sitemap.
 *
 * @since 1.0.0
 */
class Content {

	/**
	 * Returns the entries for the requested sitemap.
	 *
	 * @since 1.0.0
	 *
	 * @return array The sitemap entries.
	 */
	public function get() {
		if ( ! aioseo()->sitemap->content->isEnabled() ) {
			return [];
		}

		// Check if requested sitemap has a dedicated method.
		if ( method_exists( $this, aioseo()->sitemap->type ) ) {
			return $this->{aioseo()->sitemap->type}();
		}
	}

	/**
	 * Returns the video sitemap entries.
	 *
	 * @since 1.0.0
	 *
	 * @return array The sitemap entries.
	 */
	private function video() {
		if ( ! aioseo()->sitemap->indexes ) {
			return array_merge(
				$this->videoPosts( aioseo()->sitemap->helpers->includedPostTypes() ),
				$this->videoTerms( aioseo()->sitemap->helpers->includedTaxonomies() )
			);
		}

		if ( 'root' === aioseo()->sitemap->indexName ) {
			return aioseo()->sitemap->root->indexes();
		}

		// Parse index name to determine which exact index is being requested.
		aioseo()->sitemap->indexName = preg_replace( '#-video#', '', aioseo()->sitemap->indexName );
		if ( 'addl' === aioseo()->sitemap->indexName ) {
			// @TODO: [V4+] Add support for additional pages.
			// return $this->videoAddl();
		}
		if ( in_array( aioseo()->sitemap->indexName, aioseo()->sitemap->helpers->includedPostTypes(), true ) ) {
			return $this->videoPosts( aioseo()->sitemap->indexName );
		}
		if ( in_array( aioseo()->sitemap->indexName, aioseo()->sitemap->helpers->includedTaxonomies(), true ) ) {
			return $this->videoTerms( aioseo()->sitemap->indexName );
		}
	}

	/**
	 * Returns the video sitemap entries for a given post type.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $postType       The name of the post type.
	 * @param  array  $additionalArgs Any additional arguments for the post query.
	 * @return array                  The sitemap entries.
	 */
	public function videoPosts( $postType, $additionalArgs = [] ) {
		$posts = aioseo()->sitemap->addons['video']['query']->videoPosts( $postType );
		if ( ! $posts ) {
			return [];
		}

		// Don't build the entries if we just need the post count for the root index.
		if ( ! empty( $additionalArgs['root'] ) ) {
			return $posts;
		}

		$entries = [];
		foreach ( $posts as $post ) {
			if ( ! $post->videos ) {
				continue;
			}
			$entries[] = [
				'loc'     => get_permalink( $post->ID ),
				'lastmod' => aioseo()->helpers->formatDateTime( $post->post_modified_gmt ),
				'videos'  => json_decode( $post->videos )
			];
		}
		return apply_filters( 'aioseo_video_sitemap_posts', $entries, $postType );
	}

	/**
	 * Returns the video sitemap entries for a given taxonomy.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $taxonomy The name of the taxonomy.
	 * @param  boolean $limit    Whether the amount of queried terms should be limited.
	 * @return array             The sitemap entries.
	 */
	public function videoTerms( $taxonomy, $limit = true ) {
		$terms = aioseo()->sitemap->addons['video']['query']->videoTerms( $taxonomy, $limit );
		if ( ! $terms ) {
			return [];
		}

		// Get all registered post types for the taxonomy.
		$postTypes = [];
		foreach ( get_post_types() as $postType ) {
			$taxonomies = get_object_taxonomies( $postType );
			foreach ( $taxonomies as $name ) {
				if ( $taxonomy === $name ) {
					$postTypes[] = $postType;
				}
			}
		}

		// Return if we're determining the root indexes.
		if ( ! $limit ) {
			foreach ( $terms as $term ) {
				$term->lastmod = aioseo()->sitemap->content->getTermLastModified( $term->term_id );
			}
			return $terms;
		}

		$entries = [];
		foreach ( $terms as $term ) {
			if ( ! $term->videos ) {
				continue;
			}
			$entries[] = [
				'loc'     => get_term_link( $term->term_id ),
				'lastmod' => aioseo()->sitemap->content->getTermLastModified( $term->term_id ),
				'videos'  => json_decode( $term->videos )
			];
		}
		return apply_filters( 'aioseo_video_sitemap_terms', $entries, $taxonomy );
	}
}