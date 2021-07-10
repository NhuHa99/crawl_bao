<?php
/**
 * Plugin Name: AIOSEO - Video Sitemap
 * Plugin URI:  https://aioseo.com
 * Description: Adds support for the Video Sitemap to All in One SEO.
 * Author:      All in One SEO Team
 * Author URI:  https://aioseo.com
 * Version:     1.0.3
 * Text Domain: aioseo-video-sitemap
 * Domain Path: languages
 *
 * All in One SEO is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * All in One SEO is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with All in One SEO. If not, see <https://www.gnu.org/licenses/>.
 *
 * @since     1.0.0
 * @author    All in One SEO
 * @package   AIOSEO\Extend\Sitemap
 * @license   GPL-2.0+
 * @copyright Copyright (c) 2020, All in One SEO
 */

// phpcs:disable Generic.Arrays.DisallowLongArraySyntax.Found

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'AIOSEO_VIDEO_SITEMAP_FILE', __FILE__ );
define( 'AIOSEO_VIDEO_SITEMAP_DIR', __DIR__ );
define( 'AIOSEO_VIDEO_SITEMAP_PATH', plugin_dir_path( AIOSEO_VIDEO_SITEMAP_FILE ) );
define( 'AIOSEO_VIDEO_SITEMAP_URL', plugin_dir_url( AIOSEO_VIDEO_SITEMAP_FILE ) );

// Require our translation downloader.
require_once( __DIR__ . '/extend/translations.php' );

add_action( 'init', 'aioseo_video_sitemap_translations' );
function aioseo_video_sitemap_translations() {
	$translations = new AIOSEOTranslations(
		'plugin',
		'aioseo-video-sitemap',
		'https://packages.translationspress.com/aioseo/aioseo-video-sitemap/packages.json'
	);
	$translations->init();

	// @NOTE: The slugs here need to stay as aioseo-addon.
	$addonTranslations = new AIOSEOTranslations(
		'plugin',
		'aioseo-addon',
		'https://packages.translationspress.com/aioseo/aioseo-addon/packages.json'
	);
	$addonTranslations->init();
}

// Require our plugin compatibility checker.
require_once( __DIR__ . '/extend/init.php' );

// Plugin compatibility checks.
// @NOTE: Do not change this minimum version to anything above 4.0.3 until there are no more users on 4.0.4- who use the video sitemap.
new AIOSEOExtend( 'AIOSEO - Video Sitemap', 'aioseo_video_sitemap_load', AIOSEO_VIDEO_SITEMAP_FILE, '4.0.3' );

/**
 * Function to load the Video Sitemap addon.
 *
 * @since 1.0.0
 *
 * @return void
 */
function aioseo_video_sitemap_load() {
	$levels = aioseo()->addons->getAddonLevels( 'aioseo-video-sitemap' );
	$extend = new AIOSEOExtend( 'AIOSEO - Video Sitemap', '', AIOSEO_VIDEO_SITEMAP_FILE, '4.0.5', $levels );

	// The Code below is added to fix an update bug. LEAVE ALONE.

	// Since the version numbers may vary, we only want to compare the first 3 numbers.
	$version = defined( 'AIOSEO_VERSION' ) ? explode( '-', AIOSEO_VERSION ) : null;

	if (
		empty( $version ) ||
		version_compare( $version[0], '4.0.5', '<' )
	) {
		$extend->requiresPro();
		add_filter( 'auto_update_plugin', array( $extend, 'disableAutoUpdate' ), 10, 2 );
		add_filter( 'plugin_auto_update_setting_html', array( $extend, 'modifyAutoupdaterSettingHtml' ), 11, 2 );
		require_once( __DIR__ . '/app/VideoSitemapAlt.php' );
		return;
	}

	// The Code above is added to fix an update bug. LEAVE ALONE.

	if ( ! aioseo()->pro ) {
		return $extend->requiresPro();
	}

	// We don't want to return if the plan is only expired.
	if ( aioseo()->license->isExpired() ) {
		$extend->requiresUnexpiredLicense();
		$extend->disableNotices = true;
	}

	if ( aioseo()->license->isInvalid() || aioseo()->license->isDisabled() ) {
		return $extend->requiresActiveLicense();
	}

	if ( ! aioseo()->license->isAddonAllowed( 'aioseo-video-sitemap' ) ) {
		return $extend->requiresPlanLevel();
	}

	require_once( __DIR__ . '/app/VideoSitemap.php' );

	aioseoVideoSitemap();
}