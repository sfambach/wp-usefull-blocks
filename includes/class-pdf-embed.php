<?php
/**
 * Front end: open embedded PDFs of core/file blocks without the sidebar.
 *
 * @package WpUsefullBlocks
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Appends viewer parameters to the PDF embed URL of every rendered core/file block.
 */
final class WP_Usefull_Blocks_Pdf_Embed {

	private const VIEWER_PARAMS = 'pagemode=none&navpanes=0';

	/**
	 * Hook registration.
	 */
	public static function init(): void {
		/** @see https://developer.wordpress.org/reference/hooks/render_block_this-name/ */
		add_filter( 'render_block_core/file', array( self::class, 'hide_sidebar' ) );
	}

	/**
	 * Add the viewer parameters to the embed's data URL unless it already has a fragment.
	 *
	 * @param string $block_content Rendered block HTML.
	 * @return string
	 */
	public static function hide_sidebar( string $block_content ): string {
		if ( false === stripos( $block_content, '<object' ) ) {
			return $block_content;
		}

		$tags = new WP_HTML_Tag_Processor( $block_content );

		while ( $tags->next_tag(
			array(
				'tag_name'   => 'OBJECT',
				'class_name' => 'wp-block-file__embed',
			)
		) ) {
			$data = $tags->get_attribute( 'data' );

			if ( is_string( $data ) && '' !== $data && false === strpos( $data, '#' ) ) {
				$tags->set_attribute( 'data', $data . '#' . self::VIEWER_PARAMS );
			}
		}

		return $tags->get_updated_html();
	}
}

WP_Usefull_Blocks_Pdf_Embed::init();
