<?php
/**
 * Editor quality-of-life: heading level toolbar on core/heading.
 *
 * @package WpUsefullBlocks
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueues the heading ± toolbar script in the block editor.
 */
final class WP_Usefull_Blocks_Heading_Toolbar {

	/**
	 * Hook registration.
	 */
	public static function init(): void {
		add_action( 'enqueue_block_editor_assets', array( self::class, 'enqueue' ) );
	}

	/**
	 * Register and enqueue the editor script.
	 */
	public static function enqueue(): void {
		$path = WP_USEFULL_BLOCKS_PATH . 'assets/editor-heading-toolbar.js';
		if ( ! file_exists( $path ) ) {
			return;
		}

		wp_enqueue_script(
			'wp-usefull-blocks-heading-toolbar',
			WP_USEFULL_BLOCKS_URL . 'assets/editor-heading-toolbar.js',
			array(
				'wp-block-editor',
				'wp-components',
				'wp-compose',
				'wp-element',
				'wp-hooks',
				'wp-i18n',
			),
			(string) filemtime( $path ),
			true
		);
	}
}

WP_Usefull_Blocks_Heading_Toolbar::init();
