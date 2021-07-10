<?php
namespace AIOSEO\Plugin\Pro\Admin;

use AIOSEO\Plugin\Lite\Admin as LiteAdmin;

/**
 * Abstract class that Pro and Lite both extend.
 *
 * @since 4.0.0
 */
class PostSettings extends LiteAdmin\PostSettings {
	/**
	 * Add metabox to terms.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function init() {
		if ( ! aioseo()->license->isActive() ) {
			return parent::init();
		}

		$taxonomies = aioseo()->helpers->getPublicTaxonomies();
		$options    = aioseo()->options->noConflict();
		foreach ( $taxonomies as $taxonomy ) {
			$name = $taxonomy['name'];

			if ( $options->searchAppearance->dynamic->taxonomies->has( $name ) ) {
				$showMetabox                = aioseo()->options->searchAppearance->dynamic->taxonomies->$name->advanced->showMetaBox;
				$generalSettingsCapability  = aioseo()->access->hasCapability( 'aioseo_page_general_settings' );
				$socialSettingsCapability   = aioseo()->access->hasCapability( 'aioseo_page_social_settings' );
				$advancedSettingsCapability = aioseo()->access->hasCapability( 'aioseo_page_advanced_settings' );
				if (
					$showMetabox &&
					! (
						empty( $generalSettingsCapability ) &&
						empty( $socialSettingsCapability ) &&
						empty( $advancedSettingsCapability )
					)
				) {
					add_action( "{$taxonomy['name']}_edit_form", [ $this, 'addPostSettingsTermsMetabox' ] );
				}
			}
		}
	}

	/**
	 * Adds a meta box to edit terms screens.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function addPostSettingsTermsMetabox() {
		if ( ! aioseo()->license->isActive() ) {
			return;
		}

		wp_enqueue_media();
		?>
		<div id="poststuff">
			<div id="advanced-sortables" class="meta-box-sortables">
				<div id="aioseo-tabbed" class="postbox ">
					<h2 class="hndle">
						<span>
						<?php
							echo sprintf(
								// Translators: 1 - The plugin short name ("AIOSEO").
								esc_html__( '%1$s Settings', 'all-in-one-seo-pack' ),
								AIOSEO_PLUGIN_SHORT_NAME //phpcs:ignore
							)
						?>
						</span>
					</h2>
					<div id="aioseo-term-settings-field">
						<input type="hidden" name="aioseo-term-settings" id="aioseo-term-settings" value="" />
						<?php wp_nonce_field( 'aioseoTermSettingsNonce', 'TermSettingsNonce' ); ?>
					</div>
					<div id="aioseo-term-settings-metabox" class="inside">
						<div class="aioseo-loading-spinner dark">
							<div class="double-bounce1"></div>
							<div class="double-bounce2"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}