<?php
/**
 * Plugin Name:       WP Usefull Blocks
 * Description:       Some useful blocks for the WordPress Gutenberg editor.
 * Version:           0.3.0
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

define( 'WP_USEFULL_BLOCKS_VERSION', '0.3.0' );
define( 'WP_USEFULL_BLOCKS_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_USEFULL_BLOCKS_URL', plugin_dir_url( __FILE__ ) );

require_once WP_USEFULL_BLOCKS_PATH . 'includes/ub-gallery.php';
require_once WP_USEFULL_BLOCKS_PATH . 'includes/class-url-status.php';
require_once WP_USEFULL_BLOCKS_PATH . 'includes/class-file-mirror.php';
require_once WP_USEFULL_BLOCKS_PATH . 'includes/class-status-render.php';
require_once WP_USEFULL_BLOCKS_PATH . 'includes/class-rest.php';

/**
 * Registers the block(s) metadata from the `blocks-manifest.php`.
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
