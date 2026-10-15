<?php
/**
 * Site-wide switch for preview images in [display-posts] lists.
 *
 * @package WpUsefullBlocks
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Drops thumbnails from [display-posts] lists unless the setting allows them; the block reads the setting as its default in render.php.
 */
final class WP_Usefull_Blocks_Related_Posts_Images {

	/**
	 * Hook registration.
	 */
	public static function init(): void {
		/** @see https://developer.wordpress.org/reference/hooks/shortcode_atts_shortcode/ */
		add_filter( 'shortcode_atts_display-posts', array( self::class, 'shortcode_without_image' ) );
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
