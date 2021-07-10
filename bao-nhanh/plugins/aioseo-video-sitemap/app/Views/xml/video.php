<?php
/**
 * XML template for our video sitemap index pages.
 *
 * @since 4.0.0
 */
// phpcs:disable
?>
<urlset
	xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
	xmlns:video="https://www.google.com/schemas/sitemap-video/1.1"
>
<?php foreach ( $entries as $entry ) {
	if (
		! is_array( $entry ) ||
		( ! array_key_exists( 'loc', $entry ) || ! $entry['loc'] ) ||
		( ! array_key_exists( 'videos', $entry ) || ! $entry['videos'] )
	) {
		continue;
	}
	?>
	<url>
		<loc><?php aioseo()->sitemap->output->escapeAndEcho( $entry['loc'] ); ?></loc><?php
	if ( array_key_exists( 'lastmod', $entry ) && $entry['lastmod'] ) {
			?>

		<lastmod><?php aioseo()->sitemap->output->escapeAndEcho( $entry['lastmod'] ); ?></lastmod><?php
		}
	if ( array_key_exists( 'changefreq', $entry ) && $entry['changefreq'] ) {
			?>

		<changefreq><?php aioseo()->sitemap->output->escapeAndEcho( $entry['changefreq'] ); ?></changefreq><?php
	}
	if ( array_key_exists( 'priority', $entry ) && $entry['priority'] ) {
			?>

		<priority><?php aioseo()->sitemap->output->escapeAndEcho( $entry['priority'] ); ?></priority><?php
	}
		foreach ( $entry['videos'] as $video ) {
			?>

		<video:video>
			<video:title><?php aioseo()->sitemap->output->escapeAndEcho( $video->title ); ?></video:title>
			<video:description><?php aioseo()->sitemap->output->escapeAndEcho( $video->description ); ?></video:description>
			<video:player_loc><?php aioseo()->sitemap->output->escapeAndEcho( $video->playerLoc ); ?></video:player_loc>
			<video:thumbnail_loc><?php aioseo()->sitemap->output->escapeAndEcho( $video->thumbnailLoc ); ?></video:thumbnail_loc><?php

			if ( isset( $video->duration ) && $video->duration ) {
			?>

			<video:duration><?php aioseo()->sitemap->output->escapeAndEcho( $video->duration ); ?></video:duration><?php
			}

			if ( isset( $video->publicationDate ) && $video->publicationDate ) {
				?>
	
			<video:publication_date><?php aioseo()->sitemap->output->escapeAndEcho( $video->publicationDate ); ?></video:publication_date><?php
			}

			if ( isset( $video->uploader ) && $video->uploader ) {
				?>
	
			<video:uploader><?php aioseo()->sitemap->output->escapeAndEcho( $video->uploader ); ?></video:uploader><?php
			}
			?>

		</video:video><?php
		}
	?>

	</url>
<?php } ?>
</urlset>