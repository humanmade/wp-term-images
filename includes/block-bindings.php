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

	$image_id = _wp_term_images_resolve_image_id( $source_args );

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

/**
 * Resolve the attachment ID of a term's image from binding source args.
 *
 * The term is taken from the `termId` arg when provided, otherwise from the
 * queried object, so callers track the current term on a taxonomy archive
 * without an explicit ID.
 *
 * @since 2.1.0
 *
 * @param array $source_args Binding source args. Supports `termId` (int).
 * @return int The attachment ID, or 0 when no term or image is resolved.
 */
function _wp_term_images_resolve_image_id( $source_args = array() ) {

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
		return 0;
	}

	// The image is stored as an attachment ID in term meta.
	return (int) get_term_meta( $term_id, 'image', true );
}

/**
 * Add responsive image markup to a core Image block bound to a term image.
 *
 * Block Bindings can only replace scalar attributes (`src`, `alt`, ...), so a
 * bound image never gains the `srcset`/`sizes` a natively-inserted image would.
 * Core derives those downstream from the `wp-image-{id}` class, but it only
 * rewrites an existing class — a block built around a binding has none — and in
 * block templates the content-tag filter never runs at all.
 *
 * When the displayed image (the `url` attribute) is bound to our source, this
 * injects the `wp-image-{id}` class and computes `srcset`/`sizes` for the
 * resolved attachment, giving full parity with a normal image in both post
 * content and templates.
 *
 * @since 2.1.0
 *
 * @param string $block_content The rendered block HTML.
 * @param array  $block         The parsed block.
 * @return string The block HTML, with responsive image attributes added.
 */
function _wp_term_images_add_bound_image_srcset( $block_content = '', $block = array() ) {

	$bindings = $block['attrs']['metadata']['bindings'] ?? array();

	// Only act when the displayed image is sourced from a term image.
	if ( 'wp-term-images/term-image' !== ( $bindings['url']['source'] ?? '' ) ) {
		return $block_content;
	}

	$args     = $bindings['url']['args'] ?? array();
	$image_id = _wp_term_images_resolve_image_id( $args );

	if ( empty( $image_id ) ) {
		return $block_content;
	}

	// Match the size used for the bound `url`, so srcset/sizes describe that src.
	$size = ! empty( $args['size'] )
		? sanitize_key( $args['size'] )
		: 'full';

	$processor = new WP_HTML_Tag_Processor( $block_content );

	if ( ! $processor->next_tag( 'img' ) ) {
		return $block_content;
	}

	// Ensure the wp-image-{id} class is present; srcset tooling and styles use it.
	$class       = $processor->get_attribute( 'class' );
	$image_class = "wp-image-{$image_id}";

	if ( ! is_string( $class ) || '' === $class ) {
		$processor->set_attribute( 'class', $image_class );
	} elseif ( preg_match( '/wp-image-\d+/', $class ) ) {
		$processor->set_attribute( 'class', preg_replace( '/wp-image-\d+/', $image_class, $class ) );
	} elseif ( ! str_contains( $class, $image_class ) ) {
		$processor->set_attribute( 'class', "{$class} {$image_class}" );
	}

	// Add responsive attributes, unless the markup already carries them.
	$srcset = wp_get_attachment_image_srcset( $image_id, $size );

	if ( ! empty( $srcset ) && ! $processor->get_attribute( 'srcset' ) ) {
		$processor->set_attribute( 'srcset', $srcset );

		$sizes = wp_get_attachment_image_sizes( $image_id, $size );

		if ( ! empty( $sizes ) ) {
			$processor->set_attribute( 'sizes', $sizes );
		}
	}

	return $processor->get_updated_html();
}
add_filter( 'render_block_core/image', '_wp_term_images_add_bound_image_srcset', 10, 2 );
