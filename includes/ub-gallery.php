<?php
/**
 * Helpers for the UB Gallery block.
 *
 * @package WpUsefullBlocks
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Allowed link destinations for the UB Gallery (aligned with core/gallery).
 *
 * @return array<int, string>
 */
function wp_usefull_blocks_ub_gallery_link_destinations(): array {
	return array( 'none', 'media', 'attachment', 'lightbox' );
}

/**
 * Normalize gallery images from block attributes, preferring live attachment data.
 *
 * @param array<int, mixed> $raw_images Raw image attribute values.
 * @param string            $size_slug  Registered image size slug for the focus image.
 * @return array<int, array<string, string|int>>
 */
function wp_usefull_blocks_ub_gallery_normalize_images( array $raw_images, string $size_slug = 'large' ): array {
	$size_slug  = sanitize_key( $size_slug );
	$normalized = array();

	if ( '' === $size_slug ) {
		$size_slug = 'large';
	}

	foreach ( $raw_images as $raw_image ) {
		if ( ! is_array( $raw_image ) ) {
			continue;
		}

		$attachment_id  = isset( $raw_image['id'] ) ? absint( $raw_image['id'] ) : 0;
		$url            = isset( $raw_image['url'] ) ? esc_url_raw( (string) $raw_image['url'] ) : '';
		$full_url       = isset( $raw_image['fullUrl'] ) ? esc_url_raw( (string) $raw_image['fullUrl'] ) : $url;
		$alt            = isset( $raw_image['alt'] ) ? sanitize_text_field( (string) $raw_image['alt'] ) : '';
		$caption        = isset( $raw_image['caption'] ) ? wp_strip_all_tags( (string) $raw_image['caption'] ) : '';
		$srcset         = '';
		$sizes          = '(max-width: 768px) 100vw, 768px';
		$thumb_url      = $url;
		$attachment_url = '';

		if ( $attachment_id > 0 ) {
			$sized = wp_get_attachment_image_src( $attachment_id, $size_slug );
			$full  = wp_get_attachment_image_src( $attachment_id, 'full' );
			$thumb = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );

			if ( ! is_array( $sized ) ) {
				$sized = wp_get_attachment_image_src( $attachment_id, 'large' );
			}

			if ( is_array( $sized ) ) {
				$url = (string) $sized[0];
			}

			if ( is_array( $full ) ) {
				$full_url = (string) $full[0];
			}

			$attachment_alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
			if ( is_string( $attachment_alt ) && '' !== $attachment_alt ) {
				$alt = sanitize_text_field( $attachment_alt );
			}

			$attachment_caption = wp_get_attachment_caption( $attachment_id );
			if ( is_string( $attachment_caption ) && '' !== $attachment_caption ) {
				$caption = wp_strip_all_tags( $attachment_caption );
			}

			$generated_srcset = wp_get_attachment_image_srcset( $attachment_id, $size_slug );
			if ( ! is_string( $generated_srcset ) || '' === $generated_srcset ) {
				$generated_srcset = wp_get_attachment_image_srcset( $attachment_id, 'large' );
			}
			if ( is_string( $generated_srcset ) ) {
				$srcset = $generated_srcset;
			}

			$thumb_url      = is_array( $thumb ) ? (string) $thumb[0] : $url;
			$attachment_url = (string) get_attachment_link( $attachment_id );
		}

		if ( '' === $url ) {
			continue;
		}

		$normalized[] = array(
			'id'            => $attachment_id,
			'url'           => $url,
			'fullUrl'       => '' !== $full_url ? $full_url : $url,
			'attachmentUrl' => $attachment_url,
			'alt'           => $alt,
			'caption'       => $caption,
			'srcset'        => $srcset,
			'sizes'         => $sizes,
			'thumb'         => $thumb_url,
		);
	}

	return $normalized;
}

/**
 * Resolve the gallery link destination, including legacy onImageClick values.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @return string
 */
function wp_usefull_blocks_ub_gallery_get_link_to( array $attributes ): string {
	$link_to = isset( $attributes['linkTo'] ) ? sanitize_key( (string) $attributes['linkTo'] ) : '';

	if ( '' === $link_to && isset( $attributes['onImageClick'] ) ) {
		$link_to = sanitize_key( (string) $attributes['onImageClick'] );
	}

	if ( ! in_array( $link_to, wp_usefull_blocks_ub_gallery_link_destinations(), true ) ) {
		return 'lightbox';
	}

	return $link_to;
}
