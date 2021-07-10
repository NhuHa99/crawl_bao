<?php
namespace AIOSEO\Plugin\Extend\NewsSitemap;

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

			aioseo()->sitemap->addAddon( 'news', [
				'sitemap' => $this,
				'content' => new Content(),
				'output'  => new Output(),
			]);
		} );
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
		return $params;
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
		return;
	}

	/**
	 * Determines the current sitemap context.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function determineContext() {
		$indexName = apply_filters( 'aioseo_news_sitemap_index_name', 'news' );
		if ( aioseo()->sitemap->indexName === $indexName ) {
			aioseo()->sitemap->indexName = 'news';
			aioseo()->sitemap->type      = 'news';
			return;
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
		if ( preg_match( '#(/news\.xsl)$#i', $_SERVER['REQUEST_URI'] ) ) {
			aioseo()->sitemap->headers();
			$charset = get_option( 'blog_charset' );

			echo '<?xml version="1.0" encoding="' . esc_attr( $charset ) . '"?>';
			include_once( AIOSEO_NEWS_SITEMAP_DIR . '/app/Views/xsl/news.php' );
			exit();
		}
	}
}