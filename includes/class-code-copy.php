<?php
/**
 * Front end: copy-to-clipboard button on core/code blocks.
 *
 * @package WpUsefullBlocks
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds a "Copy" button to every rendered core/code block (also styled by Code Syntax Block).
 */
final class WP_Usefull_Blocks_Code_Copy {

	private const HANDLE = 'wp-usefull-blocks-code-copy';

	/**
	 * Hook registration.
	 */
	public static function init(): void {
		/** @see https://developer.wordpress.org/reference/hooks/wp_enqueue_scripts/ */
		add_action( 'wp_enqueue_scripts', array( self::class, 'register_assets' ) );

		/** @see https://developer.wordpress.org/reference/hooks/render_block_this-name/ */
		add_filter( 'render_block_core/code', array( self::class, 'enqueue_on_render' ) );
	}

	/**
	 * Register script and style; they are enqueued only when a code block renders.
	 */
	public static function register_assets(): void {
		$script = WP_USEFULL_BLOCKS_PATH . 'assets/code-copy.js';
		$style  = WP_USEFULL_BLOCKS_PATH . 'assets/code-copy.css';

		wp_register_style(
			self::HANDLE,
			WP_USEFULL_BLOCKS_URL . 'assets/code-copy.css',
			array(),
			(string) filemtime( $style )
		);

		wp_register_script(
			self::HANDLE,
			WP_USEFULL_BLOCKS_URL . 'assets/code-copy.js',
			array(),
			(string) filemtime( $script ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		wp_localize_script(
			self::HANDLE,
			'wpUsefullBlocksCodeCopy',
			array(
				'copy'   => __( 'Copy', 'wp-usefull-blocks' ),
				'copied' => __( 'Copied', 'wp-usefull-blocks' ),
				'label'  => __( 'Copy code to clipboard', 'wp-usefull-blocks' ),
			)
		);
	}

	/**
	 * Enqueue assets when a code block is rendered on the front end.
	 *
	 * @param string $block_content Rendered block HTML.
	 * @return string Unchanged block HTML.
	 */
	public static function enqueue_on_render( string $block_content ): string {
		if ( ! is_admin() ) {
			wp_enqueue_style( self::HANDLE );
			wp_enqueue_script( self::HANDLE );
		}

		return $block_content;
	}
}

WP_Usefull_Blocks_Code_Copy::init();
