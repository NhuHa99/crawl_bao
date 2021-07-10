<?php
namespace AIOSEO\Plugin\Pro\Schema\Graphs;

/**
 * Product graph class.
 *
 * @since 4.0.0
 */
class Product extends Graph {

	/**
	 * The product object.
	 *
	 * Only set if we're on a WooCommerce product page.
	 *
	 * @since 4.0.0
	 *
	 * @var object|boolean
	 */
	private $product = false;

	/**
	 * Class constructor.
	 *
	 * @since 4.0.0
	 */
	public function __construct() {
		if ( aioseo()->helpers->isWooCommerceActive() && is_singular( 'product' ) ) {
			$this->product = wc_get_product( get_the_ID() );
		}
	}

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
		if ( ! $metaData || 'product' !== $metaData->schema_type ) {
			return [];
		}

		$homeUrl        = trailingslashit( home_url() );
		$productOptions = json_decode( $metaData->schema_type_options )->product;

		$data = [
			'@type'           => 'Product',
			'@id'             => $homeUrl . '#product',
			'url'             => get_permalink(),
			'name'            => get_the_title(),
			'productID'       => $this->sku( $productOptions ),
			'sku'             => $this->sku( $productOptions ),
			'description'     => $this->description( $productOptions ),
			'offers'          => $this->offer( $productOptions ),
			'aggregateRating' => $this->aggregateRating(),
			'width'           => $this->product ? sprintf( '%1$d cm', $this->product->get_width() ) : '',
			'height'          => $this->product ? sprintf( '%1$d cm', $this->product->get_height() ) : '',
			'depth'           => $this->product ? sprintf( '%1$d cm', $this->product->get_length() ) : '',
			'weight'          => $this->product ? sprintf( '%1$d kg', $this->product->get_weight() ) : ''
		];

		if ( $productOptions->brand ) {
			$data['brand'] = [
				'@type' => 'Brand',
				'@id'   => $homeUrl . '#productBrand',
				'name'  => $productOptions->brand
			];
		}

		$image = $this->productImage();
		if ( $image ) {
			$data['image'] = $image;
		}
		return $data;
	}

	/**
	 * Returns the product offer data.
	 *
	 * @since 4.0.0
	 *
	 * @param  array $productOptions The product options.
	 * @return array $data           The product offer data.
	 */
	private function offer( $productOptions ) {
		$data = [
			'@type'           => 'Offer',
			'@id'             => trailingslashit( home_url() ) . '#productOffer',
			'url'             => get_permalink(),
			'price'           => $this->price( $productOptions ),
			'priceCurrency'   => $this->priceCurrency( $productOptions ),
			'priceValidUntil' => '', // @TODO: [V4+] Menu option first needs to be a datepicker.
			'availability'    => $this->availability( $productOptions ),
		];

		if ( aioseo()->pro && aioseo()->options->localBusiness->locations->general->multiple ) {
			$data['areaServed'] = aioseo()->options->localBusiness->locations->business->areaServed;
		}

		if ( $this->product ) {
			$categories = wp_get_post_terms( $this->product->get_id(), 'product_cat', [ 'fields' => 'names' ] );
			if ( $categories ) {
				$data['category'] = $categories[0];
			}
		}
		return $data;
	}

	/**
	 * Returns the product SKU.
	 *
	 * @since 4.0.0
	 *
	 * @param  array  $productOptions The product options.
	 * @return string                 The product SKU.
	 */
	private function sku( $productOptions ) {
		if ( ! empty( $productOptions->sku ) ) {
			return $productOptions->sku;
		}

		if ( $this->product ) {
			return $this->product->get_sku();
		}
	}

	/**
	 * Returns the product description.
	 *
	 * @since 4.0.0
	 *
	 * @param  array  $productOptions The product options.
	 * @return string $description    The product description.
	 */
	private function description( $productOptions ) {
		if ( ! empty( $productOptions->description ) ) {
			return $productOptions->description;
		}

		if ( $this->product ) {
			$description = $this->product->get_short_description();
			if ( ! $description ) {
				$description = $this->product->get_description();
			}
			return $description;
		}
	}

	/**
	 * Returns the product price.
	 *
	 * @since 4.0.0
	 *
	 * @param  array $productOptions The product options.
	 * @return float                 The product price.
	 */
	private function price( $productOptions ) {
		if ( ! empty( $productOptions->price ) && floatval( $productOptions->price ) ) {
			return $productOptions->price;
		}

		if ( $this->product ) {
			return $this->product->get_price();
		}
	}

	/**
	 * Returns the product currency.
	 *
	 * Needs to be in ISO 4217 format.
	 *
	 * @since 4.0.0
	 *
	 * @param  array  $productOptions The product options.
	 * @return string                 The product currency.
	 */
	private function priceCurrency( $productOptions ) {
		if ( ! empty( $productOptions->currency ) ) {
			return $productOptions->currency;
		}

		if ( $this->product ) {
			return get_option( 'woocommerce_currency' );
		}
	}

	/**
	 * Returns the aggregate rating data.
	 *
	 * @since 4.0.0
	 *
	 * @return array The aggregate rating data.
	 */
	private function aggregateRating() {
		if ( ! $this->product || ! $this->product->get_reviews_allowed() || 0 === $this->product->get_rating_count() ) {
			return [];
		}

		return [
			'@type'       => 'AggregateRating',
			'@id'         => trailingslashit( home_url() ) . '#productRating',
			'worstRating' => 1,
			'bestRating'  => 5,
			'ratingValue' => $this->product->get_average_rating(),
			'ratingCount' => $this->product->get_rating_count()
		];
	}

	/**
	 * Returns the product availability.
	 *
	 * @since 4.0.0
	 *
	 * @param  array  $productOptions The product options.
	 * @return string                 The product availability.
	 */
	private function availability( $productOptions ) {
		if ( $this->product ) {
			switch ( $this->product->get_stock_status() ) {
				case 'instock':
					return 'https://schema.org/InStock';
				case 'outofstock':
					return 'https://schema.org/OutOfStock';
				case 'onbackorder':
					return 'https://schema.org/PreOrder';
				default:
					break;
			}
		}
		return $productOptions->inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';
	}

	/**
	 * Returns the product image.
	 *
	 * @since 4.0.0
	 *
	 * @return array The product image data.
	 */
	private function productImage() {
		if ( ! $this->product ) {
			return [];
		}

		if ( $this->product->get_image_id() ) {
			return $this->image( $this->product->get_image_id(), 'productImage' );
		}
		if ( has_post_thumbnail( $this->product->get_id() ) ) {
			return $this->image( get_post_thumbnail_id(), 'productImage' );
		}
	}
}