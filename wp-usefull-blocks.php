<?php
/**
 * Plugin Name:       WP Usefull Blocks
 * Description:       Some useful blocks for the WordPress Gutenberg editor.
 * Version:           0.7.0
 * Requires at least: 6.8
 * Requires PHP:      8.1
 * Author:            The WordPress Contributors
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-usefull-blocks
 * Domain Path:       /languages
 *
 * @package WpUsefullBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'WP_USEFULL_BLOCKS_VERSION', '0.7.0' );
define( 'WP_USEFULL_BLOCKS_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_USEFULL_BLOCKS_URL', plugin_dir_url( __FILE__ ) );

require_once WP_USEFULL_BLOCKS_PATH . 'includes/ub-gallery.php';
require_once WP_USEFULL_BLOCKS_PATH . 'includes/class-toc.php';
require_once WP_USEFULL_BLOCKS_PATH . 'includes/class-reading-time.php';
require_once WP_USEFULL_BLOCKS_PATH . 'includes/class-heading-toolbar.php';
require_once WP_USEFULL_BLOCKS_PATH . 'includes/class-settings.php';
require_once WP_USEFULL_BLOCKS_PATH . 'includes/class-url-status.php';
require_once WP_USEFULL_BLOCKS_PATH . 'includes/class-file-mirror.php';
require_once WP_USEFULL_BLOCKS_PATH . 'includes/class-status-render.php';
require_once WP_USEFULL_BLOCKS_PATH . 'includes/class-content-links.php';
require_once WP_USEFULL_BLOCKS_PATH . 'includes/class-link-scanner.php';
require_once WP_USEFULL_BLOCKS_PATH . 'includes/class-url-replacer.php';
require_once WP_USEFULL_BLOCKS_PATH . 'includes/class-rest.php';
require_once WP_USEFULL_BLOCKS_PATH . 'includes/Admin/class-broken-links-page.php';
require_once WP_USEFULL_BLOCKS_PATH . 'includes/Admin/class-menu.php';

/**
 * Load plugin translations.
 */
function wp_usefull_blocks_load_textdomain(): void {
	load_plugin_textdomain(
		'wp-usefull-blocks',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}
add_action( 'init', 'wp_usefull_blocks_load_textdomain', 1 );

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

/**
 * Register script translations for block editor assets.
 */
function wp_usefull_blocks_set_script_translations(): void {
	$handles = array(
		'wp-usefull-blocks-ub-callout-editor-script',
		'wp-usefull-blocks-ub-faq-editor-script',
		'wp-usefull-blocks-ub-file-editor-script',
		'wp-usefull-blocks-ub-gallery-editor-script',
		'wp-usefull-blocks-ub-link-editor-script',
		'wp-usefull-blocks-ub-reading-time-editor-script',
		'wp-usefull-blocks-ub-timeline-editor-script',
		'wp-usefull-blocks-ub-toc-editor-script',
		'wp-usefull-blocks-wp-usefull-blocks-editor-script',
	);

	foreach ( $handles as $handle ) {
		wp_set_script_translations(
			$handle,
			'wp-usefull-blocks',
			WP_USEFULL_BLOCKS_PATH . 'languages'
		);
	}
}
add_action( 'enqueue_block_editor_assets', 'wp_usefull_blocks_set_script_translations' );
