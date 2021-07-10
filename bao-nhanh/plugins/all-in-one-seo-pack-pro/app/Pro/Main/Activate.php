<?php
namespace AIOSEO\Plugin\Pro\Main;

use AIOSEO\Plugin\Common\Main as CommonMain;

/**
 * Activate class with methods that are called.
 *
 * @since 4.0.0
 */
class Activate extends CommonMain\Activate {
	/**
	 * Runs on activate.
	 *
	 * @param  bool $networkWide Whether or not this is a network wide activation.
	 * @return void
	 */
	public function activate( $networkWide ) {
		if ( is_multisite() && $networkWide ) {
			global $wpdb;
			foreach ( $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" ) as $blogId ) {
				switch_to_blog( $blogId );

				aioseo()->access->addCapabilities();

				restore_current_blog();
			}
		}

		aioseo()->access->addCapabilities();

		// Make sure our tables exist.
		aioseo()->updates->addInitialCustomTablesForV4();

		// Set the activation timestamps.
		$time = time();
		aioseo()->internalOptions->internal->activated = $time;

		if ( ! aioseo()->internalOptions->internal->firstActivated ) {
			aioseo()->internalOptions->internal->firstActivated = $time;
		}
	}
}