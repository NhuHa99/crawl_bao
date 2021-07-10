<?php
namespace AIOSEO\Plugin\Extend {

	/**
	 * Main class.
	 *
	 * @since 1.0.0
	 */
	final class VideoSitemap {
		/**
		 * Holds the instance of the plugin currently in use.
		 *
		 * @since 1.0.0
		 *
		 * @var AIOSEO\Plugin\Extend\VideoSitemap
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
		 * Main VideoSitemap Instance.
		 *
		 * Insures that only one instance of VideoSitemap exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @since 1.0.0
		 *
		 * @return VideoSitemap
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

			$pluginData = get_file_data( AIOSEO_VIDEO_SITEMAP_FILE, $defaultHeaders );

			$constants = [
				'AIOSEO_VIDEO_SITEMAP_VERSION' => $pluginData['version']
			];

			foreach ( $constants as $constant => $value ) {
				if ( ! defined( $constant ) ) {
					define( $constant, $value );
				}
			}

			$this->version = AIOSEO_VIDEO_SITEMAP_VERSION;
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
				if ( ! file_exists( AIOSEO_VIDEO_SITEMAP_DIR ) ) {
					// Something is not right.
					status_header( 500 );
					wp_die( esc_html__( 'Plugin is missing required dependencies. Please contact support for more information.', 'aioseo-video-sitemap' ) );
				}
				require AIOSEO_VIDEO_SITEMAP_DIR . $path;
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
			require_once( AIOSEO_VIDEO_SITEMAP_DIR . '/extend/updates.php' );
			new \AIOSEOUpdates( 'aioseo-video-sitemap', $this->version, plugin_basename( AIOSEO_VIDEO_SITEMAP_FILE ) );

			new VideoSitemap\Sitemap();
		}
	}
}

namespace {
	/**
	 * The function which returns the one VideoSitemap instance.
	 *
	 * @since 1.0.0
	 *
	 * @return AIOSEO\Plugin\Extend\VideoSitemap
	 */
	function aioseoVideoSitemap() {
		return AIOSEO\Plugin\Extend\VideoSitemap::instance();
	}
}