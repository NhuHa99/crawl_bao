<?php
namespace AIOSEO\Plugin\Extend\VideoSitemap;

/**
 * Handles our sitemaps.
 *
 * @since 1.0.0
 */
class Sitemap {

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'wp_loaded', function() {
			if ( ! isset( aioseo()->sitemap ) ) {
				return;
			}
			aioseo()->sitemap->addAddon( 'video', [
				'sitemap' => $this,
				'content' => new Content(),
				'output'  => new Output(),
				'ping'    => new Ping(),
				'root'    => new Root(),
				'query'   => new Query(),
				'file'    => new File(),
				'helpers' => new Helpers(),
				'rewrite' => new Rewrite(),
			]);
		} );

		$this->video = new Video();
	}

	/**
	 * Adds our sitemap params to the query vars whitelist.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $params The array of whitelisted query variable names.
	 * @return array $params The filtered array of whitelisted query variable names.
	 */
	public function addWhitelistParams( $params ) {
		if ( aioseo()->options->sitemap->video->indexes ) {
			$params[] = 'aiosp_sitemap_page';
			// Check if user has a custom filename from the V3 migration.
			$params[] = 'aioseo_video_sitemap';
		}
		return $params;
	}

	/**
	 * Determines the current sitemap context.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function determineContext() {
		global $wp_query;
		if ( preg_match( '#.*video#', aioseo()->sitemap->indexName ) || isset( $wp_query->query_vars['aioseo_video_sitemap'] ) ) {
			aioseo()->sitemap->type          = 'video';
			aioseo()->sitemap->filename      = 'video-sitemap';
			aioseo()->sitemap->indexes       = aioseo()->options->sitemap->video->indexes;
			aioseo()->sitemap->linksPerIndex = aioseo()->options->sitemap->video->linksPerIndex;
			aioseo()->sitemap->offset        = aioseo()->sitemap->linksPerIndex * aioseo()->sitemap->pageNumber;
			// The sitemap isn't statically generated if we get here.
			aioseo()->sitemap->isStatic = false;

			// Set index name to root for exact matches so that we allow access if indexes are disabled.
			if ( 'video' === aioseo()->sitemap->indexName ) {
				aioseo()->sitemap->indexName = 'root';
			}
		}
	}

	/**
	 * Checks if static file should be served and generates it if it doesn't exist.
	 *
	 * This essentially acts as a safety net in case a file doesn't exist yet or has been deleted.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function doesFileExist() {
		if (
			'video' !== aioseo()->sitemap->type ||
			! aioseo()->options->sitemap->video->advancedSettings->enable ||
			! in_array( 'staticVideoSitemap', aioseo()->internalOptions->internal->deprecatedOptions, true ) ||
			aioseo()->options->deprecated->sitemap->video->advancedSettings->dynamic
		) {
			return;
		}

		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		if ( ! file_exists( get_home_path() . $_SERVER['REQUEST_URI'] ) ) {
			aioseo()->sitemap->scheduleRegeneration();
		}
	}

	/**
	 * Returns the sitemap stylesheet.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function xsl() {
		if ( preg_match( '#(/video\.xsl)$#i', $_SERVER['REQUEST_URI'] ) ) {
			aioseo()->sitemap->headers();
			$charset = get_option( 'blog_charset' );

			echo '<?xml version="1.0" encoding="' . esc_attr( $charset ) . '"?>';
			include_once( AIOSEO_VIDEO_SITEMAP_DIR . '/app/Views/xsl/video.php' );
			exit();
		}
	}
}