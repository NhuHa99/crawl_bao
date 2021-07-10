<?php
namespace AIOSEO\Plugin\Extend\NewsSitemap;

/**
 * Determines which content should be included in the sitemap.
 *
 * @since 1.0.0
 */
class Content {

	/**
	 * Returns the entries for the requested sitemap.
	 *
	 * @since 1.0.0
	 *
	 * @return array The sitemap entries.
	 */
	public function get() {
		if ( ! aioseo()->sitemap->content->isEnabled() ) {
			return [];
		}

		// Check if requested sitemap has a dedicated method.
		if ( method_exists( $this, aioseo()->sitemap->type ) ) {
			return $this->{aioseo()->sitemap->type}();
		}
	}

	/**
	 * Returns the Google News sitemap entries.
	 *
	 * @since 1.0.0
	 *
	 * @return array The sitemap entries.
	 */
	private function news() {
		$posts = aioseo()->sitemap->query->posts( aioseo()->sitemap->helpers->includedPostTypes(), [
			'maxAge' => gmdate( 'Y-m-d H:i:s', strtotime( '-48 hours' ) )
		] );

		if ( ! $posts ) {
			return [];
		}

		$publicationName = aioseo()->options->sitemap->news->publicationName;
		if ( ! $publicationName ) {
			$publicationName = aioseo()->helpers->decodeHtmlEntities( get_option( 'blogname' ) );
		}
		$locale = $this->publicationLanguage();
		// $defaultGenre    = aioseo()->options->sitemap->news->defaultGenre;

		$entries = [];
		foreach ( $posts as $post ) {
			// @TODO: [V4+] Add support for genre and stock ticker.
			// $genre       = get_post_meta( $post->ID, 'aioseo_news_genre', true );
			// $stockTicker = get_post_meta( $post->ID, 'aioseo_news_stock_ticker', true );

			$entries[] = [
				'loc'             => get_permalink( $post->ID ),
				'publication'     => [
					'name'     => $publicationName,
					'language' => $locale,
				],
				'publicationDate' => aioseo()->helpers->formatDateTime( $post->post_date_gmt ),
				'title'           => $post->post_title,
				//'genres'          => $genre ? $genre : $defaultGenre,
				//'stockTicker'     => $stockTicker ? $stockTicker : ''
			];
		}
		return $entries;
	}

	/**
	 * Returns the language code for the site in ISO 639-1 format.
	 *
	 * @since 1.0.0
	 *
	 * @return string The language code in ISO 639-1 format.
	 */
	private function publicationLanguage() {
		$locale = get_locale();

		if ( strlen( $locale ) < 2 ) {
			$locale = 'en';
			return $locale;
		}

		// These are two exceptions as stated on https://support.google.com/news/publisher-center/answer/9606710.
		if ( 'zh_CN' === $locale ) {
			return 'zh-cn';
		}

		if ( 'zh_TW' === $locale ) {
			return 'zh-tw';
		}

		return substr( $locale, 0, 2 );
	}
}