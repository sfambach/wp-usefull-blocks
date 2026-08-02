<?php
/**
 * Scan posts/pages for hyperlinks and resolve their status.
 *
 * @package WpUsefullBlocks
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extracts and checks content URLs.
 */
final class WP_Usefull_Blocks_Link_Scanner {

	public const INDEX_OPTION = 'wp_usefull_blocks_link_index';

	/**
	 * Extract checkable hrefs from HTML / block content.
	 *
	 * @param string $content Post content.
	 * @return list<string>
	 */
	public static function extract_urls( string $content ): array {
		$urls = array();

		if ( '' === $content ) {
			return $urls;
		}

		if ( class_exists( 'DOMDocument' ) && ( false !== stripos( $content, '<a ' ) || false !== stripos( $content, '<a>' ) ) ) {
			$previous = libxml_use_internal_errors( true );
			$dom      = new DOMDocument();
			$dom->loadHTML(
				'<?xml encoding="utf-8" ?><div id="ub-scan">' . $content . '</div>',
				LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
			);
			libxml_clear_errors();
			libxml_use_internal_errors( $previous );

			$root = $dom->getElementById( 'ub-scan' );
			if ( $root instanceof DOMElement ) {
				foreach ( $root->getElementsByTagName( 'a' ) as $anchor ) {
					if ( ! $anchor instanceof DOMElement ) {
						continue;
					}
					$href = trim( $anchor->getAttribute( 'href' ) );
					if ( self::is_checkable( $href ) ) {
						$urls[] = $href;
					}
				}
			}
		}

		// Block attributes: "url":"https://..."
		if ( preg_match_all( '/"url"\s*:\s*"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"/', $content, $matches ) ) {
			foreach ( $matches[1] as $raw ) {
				$href = stripcslashes( (string) $raw );
				if ( self::is_checkable( $href ) ) {
					$urls[] = $href;
				}
			}
		}

		// Block attributes: "sourceUrl":"https://..."
		if ( preg_match_all( '/"sourceUrl"\s*:\s*"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"/', $content, $matches ) ) {
			foreach ( $matches[1] as $raw ) {
				$href = stripcslashes( (string) $raw );
				if ( self::is_checkable( $href ) ) {
					$urls[] = $href;
				}
			}
		}

		return array_values( array_unique( $urls ) );
	}

	/**
	 * @param string $href Href.
	 * @return bool
	 */
	public static function is_checkable( string $href ): bool {
		$href = trim( $href );
		if ( '' === $href ) {
			return false;
		}

		$lower = strtolower( $href );
		if (
			str_starts_with( $lower, '#' )
			|| str_starts_with( $lower, 'mailto:' )
			|| str_starts_with( $lower, 'tel:' )
			|| str_starts_with( $lower, 'javascript:' )
			|| str_starts_with( $lower, 'data:' )
		) {
			return false;
		}

		return true;
	}

	/**
	 * Scan publish posts/pages, check URLs, store index.
	 *
	 * @param int $max_checks Max live checks this run (rest use cache).
	 * @return array{scanned:int,broken:int,urls:int}
	 */
	public static function scan( int $max_checks = 40 ): array {
		$query = new WP_Query(
			array(
				'post_type'              => array( 'post', 'page' ),
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'fields'                 => 'ids',
			)
		);

		/** @var array<string, array{url:string,hrefs:array<int,string>,posts:array<int,array{id:int,title:string,type:string}>,status:string,code:int,message:string,checked_at:int}> $index */
		$index       = array();
		$live_checks = 0;

		foreach ( $query->posts as $post_id ) {
			$post_id = (int) $post_id;
			$post    = get_post( $post_id );
			if ( ! $post instanceof WP_Post ) {
				continue;
			}

			$found = self::extract_urls( (string) $post->post_content );
			foreach ( $found as $href ) {
				$normalized = class_exists( 'WP_Usefull_Blocks_Url_Status' )
					? WP_Usefull_Blocks_Url_Status::normalize_url( $href )
					: esc_url_raw( $href );
				if ( '' === $normalized ) {
					$normalized = $href;
				}

				if ( ! isset( $index[ $normalized ] ) ) {
					$index[ $normalized ] = array(
						'url'        => $normalized,
						'hrefs'      => array(),
						'posts'      => array(),
						'status'     => 'unknown',
						'code'       => 0,
						'message'    => '',
						'checked_at' => 0,
					);
				}

				$index[ $normalized ]['hrefs'][] = $href;
				$index[ $normalized ]['posts'][ $post_id ] = array(
					'id'    => $post_id,
					'title' => get_the_title( $post_id ),
					'type'  => (string) $post->post_type,
				);
			}
		}

		foreach ( $index as $url => &$row ) {
			$row['hrefs'] = array_values( array_unique( $row['hrefs'] ) );
			$row['posts'] = array_values( $row['posts'] );

			$cached = WP_Usefull_Blocks_Url_Status::get_cached( $url );
			if ( null !== $cached && ! empty( $cached['status'] ) ) {
				// Refresh false broken internals.
				if (
					'broken' === $cached['status']
					&& WP_Usefull_Blocks_Url_Status::is_internal_url( $url )
				) {
					if ( $live_checks < $max_checks ) {
						++$live_checks;
						$result = WP_Usefull_Blocks_Url_Status::check( $url );
						WP_Usefull_Blocks_Url_Status::store( $url, $result );
						$cached = $result;
					}
				}
				$row['status']     = sanitize_key( (string) $cached['status'] );
				$row['code']       = isset( $cached['code'] ) ? (int) $cached['code'] : 0;
				$row['message']    = isset( $cached['message'] ) ? (string) $cached['message'] : '';
				$row['checked_at'] = isset( $cached['checkedAt'] ) ? (int) $cached['checkedAt'] : time();
				continue;
			}

			if ( $live_checks < $max_checks ) {
				++$live_checks;
				$result = WP_Usefull_Blocks_Url_Status::check( $url );
				WP_Usefull_Blocks_Url_Status::store( $url, $result );
				$row['status']     = sanitize_key( (string) ( $result['status'] ?? 'unknown' ) );
				$row['code']       = isset( $result['code'] ) ? (int) $result['code'] : 0;
				$row['message']    = isset( $result['message'] ) ? (string) $result['message'] : '';
				$row['checked_at'] = time();
			} else {
				$row['status']     = 'unknown';
				$row['message']    = __( 'Not checked yet — run Scan again.', 'wp-usefull-blocks' );
				$row['checked_at'] = 0;
				WP_Usefull_Blocks_Url_Status::watch( $url );
			}
		}
		unset( $row );

		$payload = array(
			'scanned_at' => time(),
			'items'      => array_values( $index ),
		);
		update_option( self::INDEX_OPTION, $payload, false );

		$broken = 0;
		foreach ( $index as $row ) {
			if ( 'broken' === $row['status'] ) {
				++$broken;
			}
		}

		return array(
			'scanned' => count( $query->posts ),
			'urls'    => count( $index ),
			'broken'  => $broken,
		);
	}

	/**
	 * Get stored index payload.
	 *
	 * @return array{scanned_at:int,items:list<array<string,mixed>>}
	 */
	public static function get_index(): array {
		$stored = get_option( self::INDEX_OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$items = isset( $stored['items'] ) && is_array( $stored['items'] ) ? $stored['items'] : array();

		return array(
			'scanned_at' => isset( $stored['scanned_at'] ) ? (int) $stored['scanned_at'] : 0,
			'items'      => $items,
		);
	}

	/**
	 * Broken items only.
	 *
	 * @return list<array<string,mixed>>
	 */
	public static function get_broken_items(): array {
		$index = self::get_index();
		$out   = array();
		foreach ( $index['items'] as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			if ( 'broken' === ( $item['status'] ?? '' ) ) {
				$out[] = $item;
			}
		}
		return $out;
	}
}
