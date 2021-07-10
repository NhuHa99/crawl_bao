<?php
namespace AIOSEO\Plugin\Pro\Api;

use AIOSEO\Plugin\Common\Api as CommonApi;

/**
 * Api class for the admin.
 *
 * @since 4.0.0
 */
class Api extends CommonApi\Api {
	/**
	 * The routes we use in the rest API.
	 *
	 * @since 4.0.0
	 *
	 * @var array
	 */
	protected $proRoutes = [
		// phpcs:disable WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
		'POST' => [
			'activate'                                                  => [ 'callback' => [ 'License', 'activateLicense' ] ],
			'deactivate'                                                => [ 'callback' => [ 'License', 'deactivateLicense' ] ],
			'notification/local-business-organization-reminder'         => [ 'callback' => [ 'Notifications', 'localBusinessOrganizationReminder' ] ],
			'notification/news-publication-name-reminder'               => [ 'callback' => [ 'Notifications', 'newsPublicationNameReminder' ] ],
			'notification/v3-migration-local-business-number-reminder'  => [ 'callback' => [ 'Notifications', 'migrationLocalBusinessNumberReminder' ] ],
			'notification/v3-migration-local-business-country-reminder' => [ 'callback' => [ 'Notifications', 'migrationLocalBusinessCountryReminder' ] ],
			'notification/import-local-business-country-reminder'       => [ 'callback' => [ 'Notifications', 'importLocalBusinessCountryReminder' ] ],
			'notification/import-local-business-type-reminder'          => [ 'callback' => [ 'Notifications', 'importLocalBusinessTypeReminder' ] ],
			'notification/import-local-business-number-reminder'        => [ 'callback' => [ 'Notifications', 'importLocalBusinessNumberReminder' ] ],
			'notification/import-local-business-fax-reminder'           => [ 'callback' => [ 'Notifications', 'importLocalBusinessFaxReminder' ] ],
			'notification/import-local-business-currencies-reminder'    => [ 'callback' => [ 'Notifications', 'importLocalBusinessCurrenciesReminder' ] ]
		]
		// phpcs:enable WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
	];

	/**
	 * Get all the routes to register.
	 *
	 * @since 4.0.0
	 *
	 * @return array An array of routes.
	 */
	protected function getRoutes() {
		return array_merge_recursive( $this->routes, $this->proRoutes );
	}
}