<?php
namespace AIOSEO\Plugin\Extend {

	/**
	 * Main class.
	 *
	 * @since 1.0.0
	 */
	final class NewsSitemap {
		/**
		 * Holds the instance of the plugin currently in use.
		 *
		 * @since 1.0.0
		 *
		 * @var AIOSEO\Plugin\Extend\NewsSitemap
		 */
		private static $instance;

		/**
		 * Plugin version for enqueueing, etc.
		 * The value is retrieved from the version constant.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public $version = '';

		/**
		 * Main NewsSitemap Instance.
		 *
		 * Insures that only one instance of NewsSitemap exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @since 1.0.0
		 *
		 * @return NewsSitemap
		 */
		public static function instance() {
			if ( null === self::$instance || ! self::$instance instanceof self ) {
				self::$instance = new self();
				self::$instance->constants();
				self::$instance->includes();
				self::$instance->load();
			}

			return self::$instance;
		}

		/**
		 * Setup plugin constants.
		 * All the path/URL related constants are defined in main plugin file.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		private function constants() {
			$defaultHeaders = [
				'name'    => 'Plugin Name',
				'version' => 'Version',
			];

			$pluginData = get_file_data( AIOSEO_NEWS_SITEMAP_FILE, $defaultHeaders );

			$constants = [
				'AIOSEO_NEWS_SITEMAP_VERSION' => $pluginData['version']
			];

			foreach ( $constants as $constant => $value ) {
				if ( ! defined( $constant ) ) {
					define( $constant, $value );
				}
			}

			$this->version = AIOSEO_NEWS_SITEMAP_VERSION;
		}

		/**
		 * Including the new files with PHP 5.3 style.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		private function includes() {
			$dependencies = [
				'/vendor/autoload.php'
			];

			foreach ( $dependencies as $path ) {
				if ( ! file_exists( AIOSEO_NEWS_SITEMAP_DIR ) ) {
					// Something is not right.
					status_header( 500 );
					wp_die( esc_html__( 'Plugin is missing required dependencies. Please contact support for more information.', 'aioseo-news-sitemap' ) );
				}
				require AIOSEO_NEWS_SITEMAP_DIR . $path;
			}
		}

		/**
		 * Load our classes.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function load() {
			require_once( AIOSEO_NEWS_SITEMAP_DIR . '/extend/updates.php' );
			new \AIOSEOUpdates( 'aioseo-news-sitemap', $this->version, plugin_basename( AIOSEO_NEWS_SITEMAP_FILE ) );

			new NewsSitemap\Sitemap();
		}
	}
}

namespace {
	/**
	 * The function which returns the one NewsSitemap instance.
	 *
	 * @since 1.0.0
	 *
	 * @return AIOSEO\Plugin\Extend\NewsSitemap
	 */
	function aioseoNewsSitemap() {
		return AIOSEO\Plugin\Extend\NewsSitemap::instance();
	}
}