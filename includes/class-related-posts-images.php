<?php
/**
 * Site-wide switch for preview images in related-posts lists.
 *
 * @package WpUsefullBlocks
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Drops thumbnails from "UB Related Posts" blocks and [display-posts] lists unless the setting allows them.
 */
final class WP_Usefull_Blocks_Related_Posts_Images {

	private const BLOCK = 'wp-usefull-blocks/ub-related-posts';

	/**
	 * Hook registration.
	 */
	public static function init(): void {
		/** @see https://developer.wordpress.org/reference/hooks/render_block_data/ */
		add_filter( 'render_block_data', array( self::class, 'block_without_image' ) );

		/** @see https://developer.wordpress.org/reference/hooks/shortcode_atts_shortcode/ */
		add_filter( 'shortcode_atts_display-posts', array( self::class, 'shortcode_without_image' ) );
	}

	/**
	 * Turn off showImage on the related-posts block.
	 *
	 * @param array<string, mixed> $parsed_block Parsed block.
	 * @return array<string, mixed>
	 */
	public static function block_without_image( array $parsed_block ): array {
		if ( self::BLOCK !== ( $parsed_block['blockName'] ?? '' ) || self::images_allowed() ) {
			return $parsed_block;
		}

		$parsed_block['attrs']['showImage'] = false;

		return $parsed_block;
	}

	/**
	 * Clear image_size on the Display Posts shortcode.
	 *
	 * @param array<string, mixed> $atts Merged shortcode attributes.
	 * @return array<string, mixed>
	 */
	public static function shortcode_without_image( array $atts ): array {
		if ( ! self::images_allowed() ) {
			$atts['image_size'] = false;
		}

		return $atts;
	}

	/**
	 * Whether the settings page allows images.
	 */
	private static function images_allowed(): bool {
		return WP_Usefull_Blocks_Settings::is_enabled( 'related_posts_images' );
	}
}

WP_Usefull_Blocks_Related_Posts_Images::init();
