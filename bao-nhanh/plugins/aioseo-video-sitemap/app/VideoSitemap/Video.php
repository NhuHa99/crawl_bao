<?php
namespace AIOSEO\Plugin\Extend\VideoSitemap;

use \AIOSEO\Plugin as Plugins;

/**
 * Handles the video sitemap.
 *
 * @since 1.0.0
 */
class Video {
	/**
	 * The WP_oEmbed class instance.
	 *
	 * @since 1.0.0
	 *
	 * @link https://codex.wordpress.org/oEmbed OEmbed Codex Article.
	 * @link http://oembed.com/                 OEmbed Homepage.
	 *
	 * @var WP_oEmbed
	 */
	private $oEmbed;

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( ! aioseo()->options->sitemap->video->enable ) {
			return;
		}

		$this->hooks();
	}

	/**
	 * Registers our hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function hooks() {
		add_action( 'wp_insert_post', [ $this, 'scanPostManual' ], 10, 2 );
		add_action( 'edited_term', [ $this, 'scanTermManual' ] );
		add_filter( 'oembed_providers', [ $this, 'oEmbedProviders' ] );

		// Don't schedule while V3 migration is running. We'll do our scans there.
		if ( get_transient( 'aioseo_v3_migration_in_progress_posts' ) || get_transient( 'aioseo_v3_migration_in_progress_terms' ) ) {
			return;
		}

		// Action Scheduler hooks.
		add_filter( 'init', [ $this, 'scheduleScan' ], 3000 );
		add_action( 'aioseo_video_sitemap_scan', [ $this, 'scan' ] );
	}

	/**
	 * Schedules the video sitemap scan.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function scheduleScan() {
		try {
			if ( ! as_next_scheduled_action( 'aioseo_video_sitemap_scan' ) ) {
				as_schedule_single_action( time() + 10, 'aioseo_video_sitemap_scan', [], 'aioseo' );
			}
		} catch ( \Exception $e ) {
			// Do nothing.
		}
	}

	/**
	 * Adds a few more oEmbed providers to the whitelist.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $providers The whitelisted oEmbed providers.
	 * @return array $providers The filtered whitelisted oEmbed providers.
	 */
	public function oEmbedProviders( $providers ) {
		$providers['#https?:\/\/(.+)?(wistia.com|wi.st)\/(medias|embed)\/.*#i'] = [
			'http://fast.wistia.com/oembed',
			true,
		];

		/* $providers['#https?://(www\.)?videopress.com/v/.*#i'] = [
			'http://public-api.wordpress.com/oembed?for=' . urlencode( AIOSEO_PLUGIN_NAME ),
			true,
		]; */
		// @TODO: [V4+] Test if these are still needed.

		/* $providers['#https?://(www\.)?flickr\.com/.*#i'] = [
			'https://www.flickr.com/services/oembed?format={format}',
			true,
		]; */
		return $providers;
	}

	/**
	 * Scans the site for videos.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function scan() {
		$this->scanPosts();
		$this->scanTerms();
	}

	/**
	 * Triggers a manual post scan.
	 *
	 * @since 4.0.5
	 *
	 * @param  int          $postId The post ID.
	 * @param  WP_Post|null $post   The post object.
	 * @return void
	 */
	public function scanPostManual( $postId, $post = null ) {
		if ( ! aioseo()->helpers->isValidPost( $post ) ) {
			return;
		}

		static $isScanned = false;
		if ( $isScanned ) {
			return;
		}

		$this->scanPost( $postId );
		$isScanned = true;
	}

	/**
	 * Triggers a manual term scan.
	 *
	 * @since 4.0.5
	 *
	 * @param  int  $termId The term ID.
	 * @return void
	 */
	public function scanTermManual( $termId ) {
		static $isScanned = false;
		if ( $isScanned ) {
			return;
		}

		$this->scanTerm( $termId );
		$isScanned = true;
	}

	/**
	 * Scans posts for videos.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function scanPosts() {
		$postsPerScan = apply_filters( 'aioseo_video_sitemap_posts_per_scan', 50 );
		$postTypes    = implode( "', '", aioseo()->helpers->getPublicPostTypes( true ) );

		$posts = aioseo()->db
			->start( aioseo()->db->db->posts . ' as p', true )
			->select( '`p`.`ID`, `p`.`post_author`, `p`.`post_content`, `p`.`post_excerpt`, `p`.`post_date_gmt`, `p`.`post_modified_gmt`, `p`.`post_mime_type`' )
			->leftJoin( 'aioseo_posts as ap', '`ap`.`post_id` = `p`.`ID`' )
			->whereRaw( '( `ap`.`id` IS NULL OR `p`.`post_modified_gmt` > `ap`.`video_scan_date` OR `ap`.`video_scan_date` IS NULL )' )
			->where( 'p.post_status', 'publish' )
			->whereRaw( "`p`.`post_type` IN ( '$postTypes' )" )
			->limit( $postsPerScan )
			->run()
			->result();

		if ( ! $posts ) {
			return;
		}

		foreach ( $posts as $post ) {
			$this->scanPost( $post );
		}
	}

	/**
	 * Scans terms for videos.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function scanTerms() {
		$termsPerScan = apply_filters( 'aioseo_video_sitemap_terms_per_scan', 200 );
		$taxonomies   = array_diff( aioseo()->helpers->getPublicTaxonomies( true ), [ 'category', 'post_tag' ] );
		$taxonomies   = "'" . implode( "', '", $taxonomies ) . "'";

		$terms = aioseo()->db
			->start( aioseo()->db->db->term_taxonomy . ' as tt', true )
			->select( '`tt`.`term_id`, `tt`.`description`' )
			->leftJoin( 'aioseo_terms as at', '`at`.`term_id` = `tt`.`term_id`' )
			->whereRaw( '( `at`.`term_id` IS NULL OR `at`.`video_scan_date` IS NULL )' )
			->whereRaw( "`tt`.`taxonomy` IN ( $taxonomies )" )
			->limit( $termsPerScan )
			->run()
			->result();

		if ( ! $terms ) {
			return;
		}

		foreach ( $terms as $term ) {
			$this->scanTerm( $term );
		}
	}

	/**
	 * Scans a given post for videos.
	 *
	 * @since 1.0.0
	 *
	 * @param  mixed $post The post object or ID.
	 * @return void
	 */
	public function scanPost( $post ) {
		if ( is_numeric( $post ) ) {
			$post = get_post( $post );
		}

		$contentUrls   = $this->extractUrls( $post->post_content . "\r\n" . $post->post_excerpt );
		$contentVideos = $this->findVideos( $contentUrls );

		$customFieldVideos = [];
		$customFieldUrls   = $this->extractUrls( $this->customFields( $post->ID ) );
		foreach ( $this->findVideos( $customFieldUrls ) as $video ) {
			$video['includedInCustomField'] = true;
			$customFieldVideos[] = $video;
		}

		$videos = array_unique( array_merge( $contentVideos, $customFieldVideos ), SORT_REGULAR );
		$this->updatePost( $post->ID, $videos );
	}

	/**
	 * Scans a given term for videos.
	 *
	 * @since 1.0.0
	 *
	 * @param  mixed $term The term object or ID.
	 * @return void
	 */
	public function scanTerm( $term ) {
		if ( is_numeric( $term ) ) {
			$term = get_term( $term );
		}

		$urls = $this->extractUrls( $term->description );
		$this->updateTerm( $term->term_id, $this->findVideos( $urls ) );
	}

	/**
	 * Returns the videos that we were able to detect.
	 *
	 * @since 4.0.0
	 *
	 * @return array $uniqueVideos The videos.
	 */
	private function findVideos( $urls ) {
		if ( ! $urls || ! count( $urls ) ) {
			return [];
		}

		$videos = [];
		foreach ( $urls as $url ) {
			$video = $this->findOembed( $url );
			if ( ! $video ) {
				$video = $this->findSelfHosted( $url );
			}

			if ( $video ) {
				$videos[] = $video;
			}
		}

		$uniqueVideos = [];
		if ( count( $videos ) ) {
			$videoUrls = [];
			foreach ( $videos as $video ) {
				if ( ! in_array( $video['playerLoc'], $videoUrls, true ) ) {
					$videoUrls[]    = $video['playerLoc'];
					$uniqueVideos[] = $video;
					continue;
				}
			}
		}
		return $uniqueVideos;
	}

	/**
	 * Returns all URLs that are in an object.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $content The content.
	 * @return array           The URLs.
	 */
	private function extractUrls( $content ) {
		$content = $this->runShortcodes( $content );
		preg_match_all( '#\b(?:https?://|www)[^,\s()<>\[\]]+(?:\([\w\d]+\)|(?:[^,[:punct:]\s]|/))#', $content, $matches );
		if ( ! count( $matches ) || ! count( $matches[0] ) ) {
			return [];
		}

		$urls = [];
		foreach ( array_unique( $matches[0] ) as $url ) {
			if ( ! $this->couldBeVideo( $url ) ) {
				continue;
			}
			$urls[] = $this->prepareUrl( $url );
		}

		return $this->removeDuplicates( array_unique( $urls ) );
	}

	/**
	 * Returns the content from the custom fields for a given post.
	 *
	 * @since 1.0.0
	 *
	 * @param  int    $postId  The post ID.
	 * @return string $content The custom fields content.
	 */
	private function customFields( $postId ) {
		$content = '';

		$meta = get_post_meta( $postId );
		if ( ! $meta || ! is_array( $meta ) ) {
			return $content;
		}

		foreach ( $meta as $key => $value ) {
			if ( ! preg_match( '#^(_wp.*|_aioseop_.*|_oembed_.*|_edit_.*)#', $key ) ) {
				$content .= "\r\n" . aioseo()->helpers->decodeHtmlEntities( $value[0] );
			}
		}
		return $content;
	}

	/**
	 * Runs all whitelisted shortcodes in the given post content.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $content The post content.
	 * @return string          The post content after running all whitelisted shortcodes.
	 */
	private function runShortcodes( $content ) {
		$allowedShortcodes = [
			'Block Editor'           => 'video',
			'Embed Plus for YouTube' => 'embedyt'
		];
		$allowedShortcodes = apply_filters( 'aioseo_video_sitemap_allowed_shortcodes', $allowedShortcodes );

		if ( ! count( $allowedShortcodes ) ) {
			return $content;
		}

		$pattern    = get_shortcode_regex();
		$shortcodes = [];
		if ( preg_match_all( "#$pattern#s", $content, $matches ) && array_key_exists( 2, $matches ) ) {
			$shortcodes = array_unique( $matches[2] );
		}

		if ( ! count( $shortcodes ) ) {
			return $content;
		}

		$disallowedShortcodes = array_diff( $shortcodes, $allowedShortcodes );
		if ( $disallowedShortcodes ) {
			foreach ( $disallowedShortcodes as $shortcode ) {
				remove_shortcode( $shortcode );
			}
		}
		return do_shortcode( $content );
	}

	/**
	 * Checks whether the URL could be a video and doesn't refer to another media type.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $url The URL.
	 * @return boolean      Whether or not the URl could be a video.
	 */
	private function couldBeVideo( $url ) {
		$extensions = [
			'.jpeg',
			'.jpg',
			'.png',
			'.gif',
			'.svg',
			'.bmp',
			'.ico',
			'.pdf',
			'.js',
			'.css',
			'.html'
		];

		foreach ( $extensions as $extension ) {
			if ( preg_match( "#.*$extension$#", $url ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Prepares the URL for further processing.
	 *
	 * We need to map the URL to the proper endpoint and add the scheme if needed.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $url The URL
	 * @return string $url The prepared URL.
	 */
	private function prepareUrl( $url ) {
		$url = $this->mapEndpoints( $url );
		if ( ! preg_match( '#^(?:f|ht)tps?://#i', $url ) ) {
			$url = "http://$url";
		}
		if ( preg_match( '#.*wp-content/uploads/.*#', $url ) ) {
			$url = preg_replace( '#\?[^v].*$#', '', $url );
		}
		return $url;
	}

	/**
	 * Maps specific URLs to the correct oEmbed format if needed.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $url The URL.
	 * @return string $url The mapped URL.
	 */
	private function mapEndpoints( $url ) {
		$mapped = [
			'videopress.com/embed/' => 'videopress.com/v/',
			'youtube.com/embed/'    => 'youtube.com/watch?v=',
		];

		foreach ( $mapped as $sourceUrl => $endpoint ) {
			$sourceUrl = aioseo()->helpers->escapeRegex( $sourceUrl );
			$url = preg_replace( "/$sourceUrl/", $endpoint, $url );
		}

		if ( preg_match( '#.*facebook.com.*#', $url ) && preg_match( '#.*videos.*#', $url ) ) {
			$args  = array_filter( explode( '/', urldecode( $url ) ) );
			$index = array_search( 'videos', $args, true );
			if ( $index ) {
				$videoId = $args[ $index + 1 ];
				if ( is_numeric( $videoId ) ) {
					$url = "https://www.facebook.com/facebook/videos/$videoId";
				}
			}
		}
		return $url;
	}

	/**
	 * Tries to get oEmbed video data for a given URL.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $url  The URL.
	 * @return array  $data The oEmbed video data.
	 */
	private function findOembed( $url ) {
		$data = [];

		$this->loadOembed();
		$providerUrl = $this->findProvider( $url );

		if ( ! $providerUrl ) {
			return $data;
		}

		$url  = preg_replace( '#\?[^v].*$#', '', $url );
		$data = $this->oEmbed->fetch( $providerUrl, $url );

		if (
			! $data ||
			! isset( $data->html ) ||
			! isset( $data->type ) ||
			'video' !== $data->type
		) {
			return [];
		}

		return $this->prepareOembed( $url, $data );
	}

	/**
	 * Loads our own instance of the WP_oEmbed class.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function loadOembed() {
		if ( ! class_exists( 'WP_oEmbed' ) ) {
			global $wp_version;
			if ( version_compare( '5.3', $wp_version, '>' ) ) {
				include_once( ABSPATH . 'wp-includes/class-oembed.php' );
			} else {
				include_once( ABSPATH . 'wp-includes/class-wp-oembed.php' );
			}
		}
		$this->oEmbed = new \WP_oEmbed();
	}

	/**
	 * Attempts to find the oEmbed platform/provider for a given URL.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $url The URL
	 * @return string      The provider URL.
	 */
	private function findProvider( $url ) {
		$providerUrl = '';
		foreach ( $this->oEmbed->providers as $pattern => $data ) {
			list( $endpoint, $allowed ) = $data;
			// Check whether the given provider is whitelisted.
			if ( ! $allowed ) {
					continue;
			}
			if ( preg_match( $pattern, $url ) ) {
				$providerUrl = str_replace( '{format}', 'json', $endpoint );
				break;
			}
		}

		// If we haven't found a provider, let WordPress attempt to discover it the URL is on our whitelist.
		if ( ! $providerUrl && $this->isUrlWhitelisted( $url ) ) {
			$providerUrl = $this->oEmbed->discover( $url );
		}

		return $providerUrl;
	}

	/**
	 * Checks whether the URL is on our whitelist.
	 *
	 * We maintain our own whitelist to limit the amount of URLs we attempt to discover via the WP_oEmbed class.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $url The URL.
	 * @return boolean      Whether or not the URL is whitelisted.
	 */
	private function isUrlWhitelisted( $url ) {
		$allowedUrls = [
			// Providers
			'dailymotion.com',
			'facebook.com',
			'flickr.com',
			'embed.ted.com',
			'videopress.com',
			'player.vimeo.com',
			'youtube.com',
			'youtu.be',
			// Extensions
			'.mp4',
			'.m4v',
			'.mpg',
			'.mpeg',
			'.webm',
			'.mov',
			'.ogv',
			'.wmv',
			'.flv',
			'.avi',
		];

		$allowedUrls = apply_filters( 'aioseo_video_sitemap_allowed_links', $allowedUrls );
		foreach ( $allowedUrls as $allowedUrl ) {
			if ( preg_match( "#.*$allowedUrl.*#", $url ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Prepares a given oEmbed video for storage in our DB table.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $url   The URL.
	 * @param  array  $data  The oEmbed video data.
	 * @return mixed  $video The prepared oEmbed video data.
	 */
	private function prepareOembed( $url, $data ) {
		$video = [
			'playerLoc'    => $url,
			'description'  => $this->descriptionOembed( $data ),
			'thumbnailLoc' => $this->thumbnailUrl( $data )
		];

		$properties = [
			'title'       => 'title',
			'author_name' => 'uploader',
			'author_url'  => 'uploaderUrl',
			'duration'    => 'duration',
		];

		foreach ( $properties as $property => $tag ) {
			if ( isset( $data->$property ) ) {
				$video[ $tag ] = $data->$property;
			}
		}

		if ( ! isset( $video['title'] ) || ! $video['title'] ) {
			$video['title'] = $url;
		}

		return $video;
	}

	/**
	 * Returns the description for a given oEmbed video.
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $data The oEmbed video data.
	 * @return string       The video description.
	 */
	private function descriptionOembed( $data ) {
		if ( isset( $data->description ) && $data->description ) {
			return $data->description;
		}

		if ( ! empty( $data->author_name ) ) {
			if ( ! empty( $data->title ) ) {
				return sprintf( '%1$s, by %2$s.', $data->title, $data->author_name );
			}
			if ( ! empty( $data->url ) ) {
				return sprintf( '%1$s, by %2$s.', $data->url, $data->author_name );
			}
		}

		if ( ! empty( $data->title ) ) {
			return $data->title;
		}
		if ( ! empty( $data->url ) ) {
			return $data->url;
		}
		return '';
	}

	/**
	 * Attempts to find a self-hosted video for a given URL.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $url The URL.
	 * @return array       The video properties.
	 */
	private function findSelfHosted( $url ) {
		$video = [];

		// Skip if URL doesn't refer to the upload dir.
		$uploadDirUrl = aioseo()->helpers->escapeRegex( aioseo()->helpers->getWpContentUrl() );
		if ( ! preg_match( "/$uploadDirUrl.*/", $url ) ) {
			return $video;
		}

		$attachmentId = attachment_url_to_postid( $url );
		if ( ! $attachmentId ) {
			return $video;
		}

		$allowedMimeTypes = [
			'video/mpeg',
			'video/mp4',
			'video/quicktime'
		];

		$attachment = get_post( $attachmentId );
		if ( ! in_array( $attachment->post_mime_type, $allowedMimeTypes, true ) ) {
			return $video;
		}

		require_once( ABSPATH . 'wp-admin/includes/media.php' );
		$path     = get_attached_file( $attachment->ID );
		$duration = wp_read_video_metadata( $path )['length'];

		$video = [
			'playerLoc'       => $attachment->guid,
			'title'           => $attachment->post_title,
			'description'     => $this->descriptionSelfHosted( $attachment ),
			'thumbnailLoc'    => $this->thumbnailUrl( $attachment ),
			'uploader'        => get_the_author_meta( 'display_name', $attachment->post_author ),
			'publicationDate' => aioseo()->helpers->formatDateTime( $attachment->post_date_gmt ),
			'duration'        => $duration
		];

		return $video;
	}

	/**
	 * Returns the description for a given self-hosted video.
	 *
	 * @since 1.0.0
	 *
	 * @param  WP_Post $attachment The post object.
	 * @return string              The video description.
	 */
	private function descriptionSelfHosted( $attachment ) {
		if ( $attachment->post_content ) {
			return $attachment->post_content;
		}
		if ( $attachment->post_excerpt ) {
			return $attachment->post_excerpt;
		}

		$publicationDate = gmdate( 'F j, Y', strtotime( $attachment->post_date_gmt ) );
		$authorName      = get_the_author_meta( 'display_name', $attachment->post_author );
		return sprintf( '%1$s, published on %2$s by %3$s.', $attachment->post_title, $publicationDate, $authorName );
	}

	/**
	 * Returns the thumbnail URL for the video.
	 *
	 * @since 1.0.0
	 *
	 * @param  mixed  $data The oEmbed video data or post object for self-hosted videos.
	 * @return string       The thumbnail URL.
	 */
	private function thumbnailUrl( $data ) {
		// @TODO: [V4+] Check if we can get the thumbnail for Facebook videos using http://graph.facebook.com/$id/picture. The API is now protected.
		if ( isset( $data->thumbnail_url ) && $data->thumbnail_url ) {
			return $data->thumbnail_url;
		}
		if ( isset( $data->ID ) && $data->ID ) {
			// @TODO: [V4+] Check if thumbnail is stored before/after we check for videos.
			$post = Plugins\Common\Models\Post::getPost( $data->ID );
			if ( $post && $post->video_thumbnail ) {
				return $post->video_thumbnail;
			}
		}
		if ( has_custom_logo() ) {
			$attachmentId = get_theme_mod( 'custom_logo' );
			if ( $attachmentId ) {
				$attachment = get_post( get_theme_mod( 'custom_logo' ) );
				return $attachment->guid;
			}
		}
		return apply_filters( 'aioseo_video_sitemap_default_thumbnail', plugin_dir_url( AIOSEO_VIDEO_SITEMAP_FILE ) . 'app/assets/images/default-thumbnail.png' );
	}

	/**
	 * Stores the video data for a given post in our DB table.
	 *
	 * @since 1.0.0
	 *
	 * @param  int   $postId The post ID.
	 * @param  array $videos The videos.
	 * @return void
	 */
	private function updatePost( $postId, $videos ) {
		$post                    = Plugins\Common\Models\Post::getPost( $postId );
		$meta                    = $post->exists() ? [] : aioseo()->migration->meta->getMigratedPostMeta( $postId );
		$meta['post_id']         = $postId;
		$meta['videos']          = ! empty( $videos ) ? $this->removeDuplicates( $videos, 'playerLoc' ) : null;
		$meta['video_scan_date'] = gmdate( 'Y-m-d H:i:s' );

		$post->set( $meta );

		$post->save();
	}

	/**
	 * Stores the video data for a given term in our DB table.
	 *
	 * @since 1.0.0
	 *
	 * @param  int   $termId The term ID.
	 * @param  array $videos The videos.
	 * @return void
	 */
	private function updateTerm( $termId, $videos ) {
		$term                    = Plugins\Pro\Models\Term::getTerm( $termId );
		$meta                    = $term->exists() ? [] : aioseo()->migration->meta->getMigratedTermMeta( $termId );
		$meta['term_id']         = $termId;
		$meta['videos']          = ! empty( $videos ) ? $this->removeDuplicates( $videos, 'playerLoc' ) : null;
		$meta['video_scan_date'] = gmdate( 'Y-m-d H:i:s' );

		$term->set( $meta );

		$term->save();
	}

	/**
	 * Returns the unique video URLs or video objects.
	 *
	 * @since 1.0.3
	 *
	 * @param  array  $elements       The elements.
	 * @param  string $key            The key we need to compare on (in case the elements are objects).
	 * @return array  $uniqueElements The unique elements.
	 */
	private function removeDuplicates( $elements, $key = null ) {
		$uniqueElements = [];
		$guids          = [];
		foreach ( $elements as $element ) {
			$valueToCompare = $element;
			if ( $key ) {
				$valueToCompare = $element[ $key ];
			}
			// Strip off URL scheme before comparing.
			$guid = preg_replace( '(http://|https://|www.|//www.)', '', $valueToCompare );

			if ( in_array( $guid, $guids, true ) ) {
				// The video is not unique.
				continue;
			}

			$guids[]          = $guid;
			$uniqueElements[] = $element;
		}
		return $uniqueElements;
	}
}