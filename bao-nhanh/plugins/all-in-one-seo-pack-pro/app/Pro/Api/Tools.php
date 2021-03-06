<?php
namespace AIOSEO\Plugin\Pro\Api;

use AIOSEO\Plugin\Common\Api as CommonApi;

/**
 * Route class for the API.
 *
 * @since 4.0.0
 */
class Tools extends CommonApi\Tools {
	/**
	 * Restore a settings backup.
	 *
	 * @since 4.0.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function restoreBackup( $request ) {
		$response = parent::restoreBackup( $request );

		$response->data['license'] = [
			'isActive'   => aioseo()->license->isActive(),
			'isExpired'  => aioseo()->license->isExpired(),
			'isDisabled' => aioseo()->license->isDisabled(),
			'isInvalid'  => aioseo()->license->isInvalid(),
			'expires'    => aioseo()->internalOptions->internal->license->expires
		];

		return $response;
	}
}