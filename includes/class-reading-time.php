<?php
/**
 * Reading-time helpers (word count + estimate).
 *
 * @package WpUsefullBlocks
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Estimates reading time from post content.
 */
final class WP_Usefull_Blocks_Reading_Time {

	/**
	 * Default words-per-minute used when calculating minutes.
	 */
	public const DEFAULT_WPM = 200;

	/**
	 * Minimum and maximum allowed WPM.
	 */
	public const MIN_WPM = 50;
	public const MAX_WPM = 600;

	/**
	 * Count words in a post's content (HTML stripped).
	 *
	 * @param int $post_id Post ID.
	 * @return int Word count (0 when unavailable).
	 */
	public static function count_words( int $post_id ): int {
		if ( $post_id <= 0 ) {
			return 0;
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return 0;
		}

		$text = wp_strip_all_tags( (string) $post->post_content );
		$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = preg_replace( '/\s+/u', ' ', trim( $text ) ) ?? '';

		if ( '' === $text ) {
			return 0;
		}

		$parts = preg_split( '/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY );
		if ( ! is_array( $parts ) ) {
			return 0;
		}

		return count( $parts );
	}

	/**
	 * Clamp words-per-minute to a safe range.
	 *
	 * @param int $wpm Candidate WPM.
	 * @return int
	 */
	public static function normalize_wpm( int $wpm ): int {
		if ( $wpm < self::MIN_WPM ) {
			return self::DEFAULT_WPM;
		}
		if ( $wpm > self::MAX_WPM ) {
			return self::MAX_WPM;
		}
		return $wpm;
	}

	/**
	 * Estimate reading minutes from a word count.
	 *
	 * Always at least 1 minute when there is any text.
	 *
	 * @param int $word_count Word count.
	 * @param int $wpm        Words per minute.
	 * @return int Minutes (0 when empty).
	 */
	public static function estimate_minutes( int $word_count, int $wpm = self::DEFAULT_WPM ): int {
		$wpm = self::normalize_wpm( $wpm );

		if ( $word_count <= 0 ) {
			return 0;
		}

		return max( 1, (int) ceil( $word_count / $wpm ) );
	}
}
