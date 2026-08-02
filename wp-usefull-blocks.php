<?php
/**
 * Plugin Name:       WP Usefull Blocks
 * Description:       Some useful blocks for the WordPress Gutenberg editor.
 * Version:           0.2.0
 * Requires at least: 6.8
 * Requires PHP:      8.1
 * Author:            The WordPress Contributors
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-usefull-blocks
 *
 * @package WpUsefullBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'WP_USEFULL_BLOCKS_VERSION', '0.2.0' );
define( 'WP_USEFULL_BLOCKS_PATH', plugin_dir_path( __FILE__ ) );

require_once WP_USEFULL_BLOCKS_PATH . 'includes/ub-gallery.php';

/**
 * Registers the block(s) metadata from the `blocks-manifest.php` and registers the block type(s)
 * based on the registered block metadata. Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://make.wordpress.org/core/2025/03/13/more-efficient-block-type-registration-in-6-8/
 * @see https://make.wordpress.org/core/2024/10/17/new-block-type-registration-apis-to-improve-performance-in-wordpress-6-7/
 */
function wp_usefull_blocks_block_init(): void {
	$manifest = WP_USEFULL_BLOCKS_PATH . 'build/blocks-manifest.php';

	if ( ! file_exists( $manifest ) ) {
		return;
	}

	wp_register_block_types_from_metadata_collection(
		WP_USEFULL_BLOCKS_PATH . 'build',
		$manifest
	);
}
add_action( 'init', 'wp_usefull_blocks_block_init' );
