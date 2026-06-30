<?php

/**
 * Term Image block binding source.
 *
 * Exposes the image assigned to a taxonomy term (stored under the
 * wp-term-images `image` meta key) as a Block Bindings source, so a core
 * Image block can be connected to a term's image in templates or post content.
 *
 * @since 2.1.0
 *
 * @package Plugins/Terms/Metadata/Image
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Register the term-image block bindings source.
 *
 * @since 2.1.0
 */
function _wp_term_images_register_block_bindings() {

	// Block Bindings landed in WordPress 6.7.
	if ( ! function_exists( 'register_block_bindings_source' ) ) {
		return;
	}

	register_block_bindings_source(
		'wp-term-images/term-image',
		array(
			'label'              => __( 'Term Image', 'wp-term-images' ),
			'get_value_callback' => '_wp_term_images_get_binding_value',
		)
	);
}
add_action( 'init', '_wp_term_images_register_block_bindings' );

/**
 * Resolve the bound value for a term's image.
 *
 * Returns the value matching the bound block attribute:
 *
 * - `id`    The attachment ID.
 * - `url`   The attachment URL, at the size given in the `size` arg (default `full`).
 * - `alt`   The attachment alt text.
 * - `title` The attachment title.
 *
 * The term is taken from the `termId` source arg when provided, otherwise from
 * the queried object — so the binding tracks the current term on a taxonomy
 * archive template without needing an explicit ID.
 *
 * Returns `null` (leaving the block's own attribute untouched) when no term is
 * resolved, the term has no image, or the attribute is not one we supply.
 *
 * @since 2.1.0
 *
 * @param array    $source_args    Arguments passed via the binding. Supports
 *                                 `termId` (int) and `size` (string).
 * @param WP_Block $block_instance The block instance being rendered.
 * @param string   $attribute_name The block attribute being bound.
 * @return string|int|null The value for the attribute, or null to skip.
 */
function _wp_term_images_get_binding_value( $source_args = array(), $block_instance = null, $attribute_name = '' ) {

	// Resolve the term: explicit arg first, then the current queried term.
	$term_id = ! empty( $source_args['termId'] )
		? (int) $source_args['termId']
		: 0;

	if ( empty( $term_id ) ) {
		$queried = get_queried_object();

		if ( $queried instanceof WP_Term ) {
			$term_id = $queried->term_id;
		}
	}

	if ( empty( $term_id ) ) {
		return null;
	}

	// The image is stored as an attachment ID in term meta.
	$image_id = (int) get_term_meta( $term_id, 'image', true );

	if ( empty( $image_id ) ) {
		return null;
	}

	switch ( $attribute_name ) {

		case 'id':
			return $image_id;

		case 'url':
			$size = ! empty( $source_args['size'] )
				? sanitize_key( $source_args['size'] )
				: 'full';

			$url = wp_get_attachment_image_url( $image_id, $size );

			return ! empty( $url )
				? $url
				: null;

		case 'alt':
			$alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );

			return is_string( $alt )
				? $alt
				: null;

		case 'title':
			return get_the_title( $image_id );

		default:
			return null;
	}
}
