<?php
namespace AIOSEO\Plugin\Pro\Schema\Graphs;

/**
 * Video Object graph class.
 *
 * @since 4.0.0
 */
class VideoObject extends Graph {

	/**
	 * Returns the graph data.
	 *
	 * @since 4.0.0
	 *
	 * @return array $data The graph data.
	 */
	public function get() {
		if ( ! is_singular() ) {
			return [];
		}

		$metaData = aioseo()->meta->metaData->getMetaData();
		if ( ! $metaData || 'video' !== $metaData->schema_type ) {
			return [];
		}

		$homeUrl      = trailingslashit( home_url() );
		$videoOptions = json_decode( $metaData->schema_type_options->video );

		// @TODO: [V4+] Finish this after V4 release.
		$data = [
			'@type'       => 'Video',
			'@id'         => $homeUrl . '#video',
			'headline'    => $videoOptions->headline,
			'description' => $videoOptions->description,
			'contentUrl'  => $videoOptions->contentUrl,
			'embedUrl'    => $videoOptions->embedUrl,
			'duration'    => $videoOptions->duration,
			'views'       => $videoOptions->views
		];
		return $data;
	}
}