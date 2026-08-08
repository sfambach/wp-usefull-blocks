<?php
/**
 * Replace a URL across a post's content (href + known block attributes).
 *
 * @package WpUsefullBlocks
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared URL replacement helper.
 */
final class WP_Usefull_Blocks_Url_Replacer {

	/**
	 * Build old-URL variants to search for in content.
	 *
	 * @param string               $url   Normalized URL.
	 * @param array<int,string>    $hrefs Original href forms from the scanner.
	 * @return list<string>
	 */
	public static function variants( string $url, array $hrefs = array() ): array {
		$variants = array_merge( array( $url ), $hrefs );
		$variants[] = esc_url( $url );
		$variants[] = esc_url_raw( $url );

		if ( class_exists( 'WP_Usefull_Blocks_Url_Status' ) ) {
			$variants[] = WP_Usefull_Blocks_Url_Status::normalize_url( $url );
		}

		$home = untrailingslashit( home_url() );
		$norm = untrailingslashit( $url );
		if ( str_starts_with( $norm, $home ) ) {
			$path = substr( $norm, strlen( $home ) );
			if ( is_string( $path ) && '' !== $path ) {
				$variants[] = $path;
				$variants[] = '/' . ltrim( $path, '/' );
			}
		}

		$variants = array_values(
			array_unique(
				array_filter(
					array_map( 'strval', $variants ),
					static fn( string $v ): bool => '' !== $v
				)
			)
		);

		// Longer first so more specific replacements win.
		usort(
			$variants,
			static fn( string $a, string $b ): int => strlen( $b ) <=> strlen( $a )
		);

		return $variants;
	}

	/**
	 * Replace old URL with new URL in a content string.
	 *
	 * @param string            $content Content.
	 * @param string            $old_url Old URL.
	 * @param string            $new_url New URL.
	 * @param array<int,string> $hrefs   Extra old variants.
	 * @return string
	 */
	public static function replace_in_content( string $content, string $old_url, string $new_url, array $hrefs = array() ): string {
		$new_url = esc_url_raw( $new_url );
		if ( '' === $new_url || '' === $content ) {
			return $content;
		}

		$updated = $content;
		foreach ( self::variants( $old_url, $hrefs ) as $old ) {
			$updated = str_replace(
				array(
					'href="' . $old . '"',
					"href='" . $old . "'",
					'"url":"' . $old . '"',
					'"sourceUrl":"' . $old . '"',
				),
				array(
					'href="' . $new_url . '"',
					"href='" . $new_url . "'",
					'"url":"' . $new_url . '"',
					'"sourceUrl":"' . $new_url . '"',
				),
				$updated
			);
		}

		return $updated;
	}

	/**
	 * Replace a URL in one post.
	 *
	 * @param int               $post_id Post ID.
	 * @param string            $old_url Old URL.
	 * @param string            $new_url New URL.
	 * @param array<int,string> $hrefs   Extra variants.
	 * @return true|WP_Error
	 */
	public static function replace_in_post( int $post_id, string $old_url, string $new_url, array $hrefs = array() ) {
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return new WP_Error( 'ub_missing_post', __( 'Post not found.', 'wp-usefull-blocks' ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error( 'ub_cannot_edit', __( 'You cannot edit this post.', 'wp-usefull-blocks' ) );
		}

		$new_content = self::replace_in_content( (string) $post->post_content, $old_url, $new_url, $hrefs );
		if ( $new_content === $post->post_content ) {
			return new WP_Error( 'ub_no_change', __( 'URL not found in this post.', 'wp-usefull-blocks' ) );
		}

		$result = wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $new_content,
			),
			true
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return true;
	}

	/**
	 * Replace a URL in many posts.
	 *
	 * @param array<int,int>    $post_ids Post IDs.
	 * @param string            $old_url  Old URL.
	 * @param string            $new_url  New URL.
	 * @param array<int,string> $hrefs    Extra variants.
	 * @return array{updated:int,errors:list<string>}
	 */
	public static function replace_in_posts( array $post_ids, string $old_url, string $new_url, array $hrefs = array() ): array {
		$updated = 0;
		$errors  = array();

		foreach ( $post_ids as $post_id ) {
			$result = self::replace_in_post( (int) $post_id, $old_url, $new_url, $hrefs );
			if ( true === $result ) {
				++$updated;
			} elseif ( is_wp_error( $result ) ) {
				$errors[] = $result->get_error_message();
			}
		}

		return array(
			'updated' => $updated,
			'errors'  => $errors,
		);
	}
}
