<?php
namespace AIOSEO\Plugin\Pro\Migration;

// phpcs:disable WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound

use AIOSEO\Plugin\Common\Migration as CommonMigration;
use AIOSEO\Plugin\Pro\Models;
use AIOSEO\Plugin\Extend\VideoSitemap;

/**
 * Migrates the term meta from V3.
 *
 * @since 4.0.0
 */
class Meta extends CommonMigration\Meta {

	/**
	 * Instance of the Video class of the Video Sitemap.
	 *
	 * @since 4.0.2
	 *
	 * @var Video
	 */
	private $videoSitemap = null;

	/**
	 * Class constructor.
	 *
	 * @since 4.0.2
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'instantiateVideoSitemap' ] );
	}

	/**
	 * Instantiates the Video Sitemap video class if the addon is installed and active.
	 *
	 * @since 4.0.2
	 *
	 * @return void
	 */
	public function instantiateVideoSitemap() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if (
			is_plugin_active( aioseo()->addons->getAddon( 'aioseo-video-sitemap' )->basename ) &&
			aioseo()->license->isAddonAllowed( 'aioseo-video-sitemap' ) &&
			class_exists( 'AIOSEO\\Plugin\\Extend\\VideoSitemap' )
		) {
			$this->videoSitemap = new VideoSitemap\Video;
		}
	}

	/**
	 * Migrates additional post meta data.
	 *
	 * @since 4.0.2
	 *
	 * @param  int  $postId The post ID.
	 * @return void
	 */
	protected function migrateAdditionalPostMeta( $postId ) {
		if ( $this->videoSitemap ) {
			$post = get_post( $postId );
			$this->videoSitemap->scanPost( $post );
		}
	}

	/**
	 * Migrates the plugin meta data.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function migrateMeta() {
		parent::migrateMeta();

		try {
			if ( as_next_scheduled_action( 'aioseo_migrate_term_meta' ) ) {
				return;
			}

			as_schedule_single_action( time(), 'aioseo_migrate_term_meta', [], 'aioseo' );
		} catch ( \Exception $e ) {
			// Do nothing.
		}
	}

	/**
	 * Migrates the term meta data from V3.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function migrateTermMeta() {
		$termsPerAction   = 50;
		$publicTaxonomies = implode( "', '", aioseo()->helpers->getPublicTaxonomies( true ) );
		$timeStarted      = gmdate( 'Y-m-d H:i:s', get_transient( 'aioseo_v3_migration_in_progress_terms' ) );

		$termsToMigrate = aioseo()->db
			->start( 'terms' . ' as t' )
			->select( 't.term_id' )
			->leftJoin( 'aioseo_terms as at', '`t`.`term_id` = `at`.`term_id`' )
			->leftJoin( 'term_taxonomy as tt', '`t`.`term_id` = `tt`.`term_id`' )
			->whereRaw( "( at.term_id IS NULL OR at.updated < '$timeStarted' )" )
			->whereRaw( "( tt.taxonomy IN ( '$publicTaxonomies' ) )" )
			->orderBy( 't.term_id DESC' )
			->limit( $termsPerAction )
			->run()
			->result();

		if ( ! $termsToMigrate || ! count( $termsToMigrate ) ) {
			delete_transient( 'aioseo_v3_migration_in_progress_terms' );
			return;
		}

		foreach ( $termsToMigrate as $term ) {
			$newTermMeta = $this->getMigratedTermMeta( $term->term_id );

			$term = Models\Term::getTerm( $term->term_id );
			$term->set( $newTermMeta );
			$term->save();

			if ( $this->videoSitemap ) {
				$term = get_term( $term->term_id );
				$this->videoSitemap->scanTerm( $term );
			}
		}

		if ( count( $termsToMigrate ) === $termsPerAction ) {
			try {
				as_schedule_single_action( time() + 30, 'aioseo_migrate_term_meta', [], 'aioseo' );
			} catch ( \Exception $e ) {
				// Do nothing.
			}
		} else {
			delete_transient( 'aioseo_v3_migration_in_progress_terms' );
		}
	}

	/**
	 * Returns the migrated term meta for a given term.
	 *
	 * @since 4.0.3
	 *
	 * @param  int   $termId The term ID.
	 * @return array $meta   The term meta.
	 */
	public function getMigratedTermMeta( $termId ) {
		if ( null === self::$oldOptions ) {
			self::$oldOptions = get_option( 'aioseop_options' );
		}

		if ( empty( self::$oldOptions ) ) {
			return [];
		}

		$term     = get_term( $termId );
		$termMeta = aioseo()->db
			->start( 'termmeta' . ' as tm' )
			->select( '`tm`.`meta_key`, `tm`.`meta_value`' )
			->where( 'tm.term_id', $term->term_id )
			->whereRaw( "`tm`.`meta_key` LIKE '_aioseop_%'" )
			->run()
			->result();

		$mappedMeta = [
			'_aioseop_title'              => 'title',
			'_aioseop_description'        => 'description',
			'_aioseop_custom_link'        => 'canonical_url',
			'_aioseop_sitemap_exclude'    => '',
			'_aioseop_disable'            => '',
			'_aioseop_noindex'            => 'robots_noindex',
			'_aioseop_nofollow'           => 'robots_nofollow',
			'_aioseop_sitemap_priority'   => 'priority',
			'_aioseop_sitemap_frequency'  => 'frequency',
			'_aioseop_keywords'           => 'keywords',
			'_aioseop_opengraph_settings' => ''
		];

		$meta = [
			'term_id' => $term->term_id,
		];

		if ( ! $termMeta || ! count( $termMeta ) ) {
			return $meta;
		}

		foreach ( $termMeta as $record ) {
			$name  = $record->meta_key;
			$value = $record->meta_value;

			if ( ! in_array( $name, array_keys( $mappedMeta ), true ) ) {
				continue;
			}

			switch ( $name ) {
				case '_aioseop_description':
					$meta[ $mappedMeta[ $name ] ] = aioseo()->helpers->sanitizeOption( aioseo()->migration->helpers->macrosToSmartTags( $value ) );
					break;
				case '_aioseop_title':
					if ( ! empty( $value ) ) {
						$meta[ $mappedMeta[ $name ] ] = $this->getTitleValue( $term, $value );
					}
					break;
				case '_aioseop_sitemap_exclude':
					if ( empty( $value ) ) {
						break;
					}
					$this->migrateSitemapExcludedTerm( $termId );
					break;
				case '_aioseop_disable':
					if ( empty( $value ) ) {
						break;
					}
					$this->migrateExcludedTerm( $termId );
					break;
				case '_aioseop_noindex':
				case '_aioseop_nofollow':
					if ( 'on' === (string) $value ) {
						$meta['robots_default']       = false;
						$meta[ $mappedMeta[ $name ] ] = true;
					} elseif ( 'off' === (string) $value ) {
						$meta['robots_default'] = false;
					}
					break;
				case '_aioseop_keywords':
					$meta[ $mappedMeta[ $name ] ] = aioseo()->migration->helpers->oldKeywordsToNewKeywords( $value );
					break;
				case '_aioseop_opengraph_settings':
					$meta += $this->convertOpenGraphMeta( $value );
					break;
				case '_aioseop_sitemap_priority':
				case '_aioseop_sitemap_frequency':
					if ( empty( $value ) ) {
						$meta[ $mappedMeta[ $name ] ] = 'default';
						break;
					}
					$meta[ $mappedMeta[ $name ] ] = $value;
					break;
				default:
					$meta[ $mappedMeta[ $name ] ] = esc_html( wp_strip_all_tags( strval( $value ) ) );
					break;
			}
		}
		return $meta;
	}

	/**
	 * Migrates a given sitemap excluded term from V3.
	 *
	 * @since 4.0.3
	 *
	 * @param  int  $termId The term ID.
	 * @return void
	 */
	private function migrateSitemapExcludedTerm( $termId ) {
		$term = get_term( $termId );
		if ( ! is_object( $term ) ) {
			return;
		}

		aioseo()->options->sitemap->general->advancedSettings->enable = true;
		$excludedTerms = aioseo()->options->sitemap->general->advancedSettings->excludeTerms;

		foreach ( $excludedTerms as $excludedTerm ) {
			$excludedTerm = json_decode( $excludedTerm );
			if ( $excludedTerm->value === $termId ) {
				return;
			}
		}

		$excludedTerm = [
			'value' => $term->term_id,
			'type'  => $term->taxonomy,
			'label' => $term->name,
			'link'  => get_term_link( $term, $term->taxonomy )
		];

		$excludedTerms[] = wp_json_encode( $excludedTerm );
		aioseo()->options->sitemap->general->advancedSettings->excludeTerms = $excludedTerms;
	}

	/**
	 * Migrates a given disabled term from V3.
	 *
	 * @since 4.0.3
	 *
	 * @param  int  $termId The term ID.
	 * @return void
	 */
	private function migrateExcludedTerm( $termId ) {
		$term = get_term( $termId );
		if ( ! is_object( $term ) ) {
			return;
		}

		$excludedTerms = aioseo()->options->deprecated->searchAppearance->advanced->excludeTerms;
		foreach ( $excludedTerms as $excludedTerm ) {
			$excludedTerm = json_decode( $excludedTerm );
			if ( $excludedTerm->value === $termId ) {
				return;
			}
		}

		$excludedTerm = [
			'value' => $term->term_id,
			'type'  => $term->taxonomy,
			'label' => $term->name,
			'link'  => get_term_link( $term, $term->taxonomy )
		];

		$excludedTerms[] = wp_json_encode( $excludedTerm );
		aioseo()->options->deprecated->searchAppearance->advanced->excludeTerms = $excludedTerms;

		$deprecatedOptions = aioseo()->internalOptions->internal->deprecatedOptions;
		if ( ! in_array( 'excludeTerms', $deprecatedOptions, true ) ) {
			array_push( $deprecatedOptions, 'excludeTerms' );
			aioseo()->internalOptions->internal->deprecatedOptions = $deprecatedOptions;
		}
	}

	/**
	 * Returns the title as it was in V3.
	 *
	 * @since 4.0.0
	 *
	 * @param  int    $post     The term object.
	 * @param  string $seoTitle The old SEO title.
	 * @return string           The title.
	 */
	protected function getTitleValue( $term, $seoTitle = '' ) {
		if ( ! isset( $term->term_id ) ) {
			return parent::getTitleValue( $term, $seoTitle );
		}

		$titleFormat = '#taxonomy_title #separator_sa #site_title';
		if ( aioseo()->options->searchAppearance->dynamic->taxonomies->has( $term->taxonomy ) ) {
			$titleFormat = aioseo()->options->searchAppearance->dynamic->taxonomies->{$term->taxonomy}->title;
		}

		$seoTitle = preg_replace( '/(#taxonomy_title)/', $seoTitle, $titleFormat );
		return aioseo()->migration->helpers->macrosToSmartTags( $seoTitle );
	}
}