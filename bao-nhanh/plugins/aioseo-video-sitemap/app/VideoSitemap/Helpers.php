<?php
namespace AIOSEO\Plugin\Extend\VideoSitemap;

/**
 * Contains general helper methods specific to the sitemap.
 *
 * @since 4.0.0
 */
class Helpers {

	/**
	 * Returns the URLs of all active sitemaps.
	 *
	 * @since 1.0.0
	 *
	 * @return array $urls The sitemap URLs.
	 */
	public function getSitemapUrls() {
		static $urls = [];
		if ( $urls ) {
			return $urls;
		}

		// Check if user has a custom filename from the V3 migration.
		$filename = aioseo()->sitemap->helpers->filename( 'video' ) ? aioseo()->sitemap->helpers->filename( 'video' ) : 'video-sitemap';
		if ( aioseo()->options->sitemap->video->enable ) {
			$urls[] = 'Sitemap: ' . trailingslashit( home_url() ) . $filename . '.xml';
		}
		return $urls;
	}
}