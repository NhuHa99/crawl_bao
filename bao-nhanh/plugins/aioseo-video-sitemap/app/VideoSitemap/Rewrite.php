<?php
namespace AIOSEO\Plugin\Extend\VideoSitemap;

/**
 * Handles our sitemap rewrite rules.
 *
 * @since 4.0.0
 */
class Rewrite {

	/**
	 * Returns our sitemap rewrite rules.
	 *
	 * @since 4.0.0
	 *
	 * @return array $rules The compiled array of rewrite rules, keyed by their regex pattern.
	 */
	public static function get() {
		$filename = aioseo()->sitemap->helpers->filename( 'video' );

		$rules = [];
		if ( ! empty( $filename ) && 'video-sitemap' !== $filename && aioseo()->options->sitemap->video->enable ) {
			// Check if user has a custom filename from the V3 migration.
			$rules += [
				"$filename.xml"           => 'index.php?aiosp_sitemap_path=root&aioseo_video_sitemap=1',
				"(.+)-$filename.xml"      => 'index.php?aiosp_sitemap_path=$matches[1]&aioseo_video_sitemap=1',
				"(.+)-$filename(\d+).xml" => 'index.php?aiosp_sitemap_path=$matches[1]&aiosp_sitemap_page=$matches[2]&aioseo_video_sitemap=1'
			];
		}

		return $rules;
	}
}