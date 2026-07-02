<?php
/**
 * Term Image block render callback.
 *
 * Renders the featured image stored against a taxonomy term via the
 * wp-term-images meta key ('image'). Outputs nothing when no term ID
 * is supplied or no image has been assigned.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block inner content (unused — dynamic block).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$term_id  = (int) ( $attributes['termId'] ?? 0 );
$img_size = sanitize_key( $attributes['imageSize'] ?? 'full' );

if ( ! $term_id ) {
	return;
}

$image_id = (int) get_term_meta( $term_id, 'image', true );

if ( ! $image_id ) {
	return;
}

$image = wp_get_attachment_image(
	$image_id,
	$img_size,
	false,
	[ 'style' => 'width:100%;height:auto;display:block;' ]
);

if ( ! $image ) {
	return;
}

printf(
	'<figure %s>%s</figure>',
	get_block_wrapper_attributes( [ 'style' => 'overflow:hidden;' ] ),
	// wp_get_attachment_image() escapes attributes internally.
	$image
);
