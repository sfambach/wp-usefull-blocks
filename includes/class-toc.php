<?php
/**
 * Table of contents helpers (heading extraction + anchor injection).
 *
 * @package WpUsefullBlocks
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds TOC data from post blocks and ensures heading anchors on render.
 */
final class WP_Usefull_Blocks_Toc {

	/**
	 * Slug => usage count for the current front-end render pass.
	 *
	 * @var array<string, int>
	 */
	private static array $render_slug_counts = array();

	/**
	 * Hook registration.
	 */
	public static function init(): void {
		add_filter( 'the_content', array( self::class, 'prepare_content_render' ), 0 );
		add_filter( 'render_block_core/heading', array( self::class, 'ensure_heading_id' ), 10, 2 );
	}

	/**
	 * Reset id counters before post content is rendered.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public static function prepare_content_render( string $content ): string {
		self::$render_slug_counts = array();
		return $content;
	}

	/**
	 * Extract heading entries from a post's block content.
	 *
	 * @param int $post_id   Post ID.
	 * @param int $min_level Inclusive min heading level (1–6).
	 * @param int $max_level Inclusive max heading level (1–6).
	 * @return list<array{level:int,text:string,id:string}>
	 */
	public static function get_headings( int $post_id, int $min_level = 2, int $max_level = 3 ): array {
		if ( $post_id <= 0 ) {
			return array();
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return array();
		}

		$min_level = max( 1, min( 6, $min_level ) );
		$max_level = max( 1, min( 6, $max_level ) );
		if ( $min_level > $max_level ) {
			$tmp       = $min_level;
			$min_level = $max_level;
			$max_level = $tmp;
		}

		$blocks = parse_blocks( (string) $post->post_content );
		$counts = array();

		return self::collect_headings( $blocks, $min_level, $max_level, $counts );
	}

	/**
	 * Recursively collect heading blocks.
	 *
	 * @param array<int, array<string, mixed>> $blocks    Parsed blocks.
	 * @param int                              $min_level Min level.
	 * @param int                              $max_level Max level.
	 * @param array<string, int>               $counts    Slug counters (by ref).
	 * @return list<array{level:int,text:string,id:string}>
	 */
	private static function collect_headings( array $blocks, int $min_level, int $max_level, array &$counts ): array {
		$items = array();

		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			$name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';
			if ( 'core/heading' === $name ) {
				$attrs  = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
				$level  = isset( $attrs['level'] ) ? (int) $attrs['level'] : 2;
				$text   = self::heading_text_from_block( $block );
				$anchor = isset( $attrs['anchor'] ) ? (string) $attrs['anchor'] : '';

				if ( '' !== $text ) {
					// Advance the counter for every non-empty heading so TOC ids
					// match ensure_heading_id() across all levels.
					$id = self::make_unique_id( $anchor, $text, $counts );

					if ( $level >= $min_level && $level <= $max_level ) {
						$items[] = array(
							'level' => $level,
							'text'  => $text,
							'id'    => $id,
						);
					}
				}
			}

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$items = array_merge(
					$items,
					self::collect_headings( $block['innerBlocks'], $min_level, $max_level, $counts )
				);
			}
		}

		return $items;
	}

	/**
	 * Plain text from a heading block.
	 *
	 * @param array<string, mixed> $block Heading block.
	 * @return string
	 */
	private static function heading_text_from_block( array $block ): string {
		$html = '';
		if ( ! empty( $block['innerHTML'] ) && is_string( $block['innerHTML'] ) ) {
			$html = $block['innerHTML'];
		} elseif ( ! empty( $block['innerContent'] ) && is_array( $block['innerContent'] ) ) {
			foreach ( $block['innerContent'] as $chunk ) {
				if ( is_string( $chunk ) ) {
					$html .= $chunk;
				}
			}
		}

		return trim( wp_strip_all_tags( $html ) );
	}

	/**
	 * Build a unique HTML id.
	 *
	 * @param string             $anchor Explicit block anchor.
	 * @param string             $text   Heading text.
	 * @param array<string, int> $counts Slug counters (by ref).
	 * @return string
	 */
	public static function make_unique_id( string $anchor, string $text, array &$counts ): string {
		$base = sanitize_title( $anchor );
		if ( '' === $base ) {
			$base = sanitize_title( $text );
		}
		if ( '' === $base ) {
			$base = 'section';
		}

		if ( ! isset( $counts[ $base ] ) ) {
			$counts[ $base ] = 0;
			return $base;
		}

		$counts[ $base ]++;
		return $base . '-' . (string) $counts[ $base ];
	}

	/**
	 * Ensure rendered core headings expose an id matching TOC links.
	 *
	 * @param string               $block_content Rendered HTML.
	 * @param array<string, mixed> $block         Block data.
	 * @return string
	 */
	public static function ensure_heading_id( string $block_content, array $block ): string {
		if ( '' === $block_content || false === stripos( $block_content, '<h' ) ) {
			return $block_content;
		}

		$attrs  = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
		$anchor = isset( $attrs['anchor'] ) ? (string) $attrs['anchor'] : '';
		$text   = trim( wp_strip_all_tags( $block_content ) );

		if ( '' === $text ) {
			return $block_content;
		}

		$id = self::make_unique_id( $anchor, $text, self::$render_slug_counts );

		if ( preg_match( '/\sid\s*=\s*["\'][^"\']+["\']/i', $block_content ) ) {
			// Keep the existing id; counter already advanced for TOC alignment.
			return $block_content;
		}

		return (string) preg_replace(
			'/(<h[1-6]\b)([^>]*)(>)/i',
			'$1$2 id="' . esc_attr( $id ) . '"$3',
			$block_content,
			1
		);
	}
}

WP_Usefull_Blocks_Toc::init();
