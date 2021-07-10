<?php
namespace AIOSEO\Plugin\Pro\Api;

use AIOSEO\Plugin\Common\Api as CommonApi;
use AIOSEO\Plugin\Common\Models;

/**
 * Route class for the API.
 *
 * @since 4.0.0
 */
class Sitemaps extends CommonApi\Sitemaps {
	/**
	 * Delete all static sitemap files.
	 *
	 * @since 4.0.0
	 *
	 * @return \WP_REST_Response The response.
	 */
	public static function deleteStaticFiles() {
		$response = parent::deleteStaticFiles();

		$files = list_files( get_home_path(), 1 );
		if ( ! count( $files ) ) {
			return;
		}

		$isVideoSitemapStatic = aioseo()->pro && aioseo()->options->sitemap->video->advancedSettings->enable &&
			in_array( 'staticVideoSitemap', aioseo()->internalOptions->internal->deprecatedOptions, true ) &&
			! aioseo()->options->deprecated->sitemap->video->advancedSettings->dynamic;

		$detectedFiles = [];
		if ( ! $isVideoSitemapStatic ) {
			foreach ( $files as $index => $filename ) {
				if ( preg_match( '#.*sitemap.*#', $filename ) ) {
					$isVideoSitemap = preg_match( '#.*video.*#', $filename ) ? true : false;
					if ( $isVideoSitemap ) {
						$detectedFiles[] = $filename;
					}
				}
			}
		}

		if ( ! count( $detectedFiles ) ) {
			return $response;
		}

		$wpfs = aioseo()->helpers->wpfs();
		if ( ! is_object( $wpfs ) ) {
			return $response;
		}

		foreach ( $detectedFiles as $file ) {
			@$wpfs->delete( $file, false, 'f' );
		}

		Models\Notification::deleteNotificationByName( 'sitemap-static-files' );

		return new \WP_REST_Response( [
			'success'       => true,
			'notifications' => [
				'active'    => Models\Notification::getAllActiveNotifications(),
				'dismissed' => Models\Notification::getAllDismissedNotifications()
			]
		], 200 );
	}
}