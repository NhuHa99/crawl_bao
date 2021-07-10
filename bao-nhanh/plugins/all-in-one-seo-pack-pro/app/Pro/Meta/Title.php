<?php
namespace AIOSEO\Plugin\Pro\Meta;

use AIOSEO\Plugin\Common\Meta as CommonMeta;

/**
 * Handles the page title.
 *
 * @since 4.0.0
 */
class Title extends CommonMeta\Title {

	/**
	 * Returns the term title.
	 *
	 * @since 4.0.0
	 *
	 * @param  WP_Term $term    The term object.
	 * @param  boolean $default Whether we want the default value, not the post one.
	 * @return string           The term title.
	 */
	public function getTermTitle( $term, $default = false ) {
		$metaData = aioseo()->meta->metaData->getMetaData( $term );

		$title = '';
		if ( ! empty( $metaData->title ) && ! $default ) {
			$title = $metaData->title;
			// Since we might be faking the term, let's replace the title ourselves.
			if ( ! empty( $term ) ) {
				$title = preg_replace( '/#taxonomy_title/', $term->name, $title );
			}
			$title = $this->prepareTitle( $title );
		}

		$options = aioseo()->options->noConflict();
		if ( ! $title && $options->searchAppearance->dynamic->taxonomies->has( $term->taxonomy ) ) {
			$newTitle = aioseo()->options->searchAppearance->dynamic->taxonomies->{$term->taxonomy}->title;
			$newTitle = preg_replace( '/#taxonomy_title/', $term->name, $newTitle );
			$title    = $this->prepareTitle( $newTitle, false, $default );
		}
		return $title ? $title : $this->prepareTitle( single_term_title( '', false ), false, $default );
	}
}