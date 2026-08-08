<?php
/**
 * Server-side render for the UB Reading Time block.
 *
 * @package WpUsefullBlocks
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Usefull_Blocks_Reading_Time' ) ) {
	return;
}

$wpm             = isset( $attributes['wordsPerMinute'] ) ? (int) $attributes['wordsPerMinute'] : WP_Usefull_Blocks_Reading_Time::DEFAULT_WPM;
$show_word_count = ! empty( $attributes['showWordCount'] );
$prefix          = isset( $attributes['prefix'] ) ? trim( (string) $attributes['prefix'] ) : '';
$wpm             = WP_Usefull_Blocks_Reading_Time::normalize_wpm( $wpm );

$post_id = 0;
if ( isset( $block->context['postId'] ) ) {
	$post_id = (int) $block->context['postId'];
}
if ( $post_id <= 0 ) {
	$post_id = (int) get_the_ID();
}

$word_count = WP_Usefull_Blocks_Reading_Time::count_words( $post_id );
$minutes    = WP_Usefull_Blocks_Reading_Time::estimate_minutes( $word_count, $wpm );

if ( 0 === $minutes ) {
	if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class' => 'ub-reading-time ub-reading-time--empty',
			)
		);
		printf(
			'<p %1$s>%2$s</p>',
			$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by helper.
			esc_html__( 'No readable text found in this post yet.', 'wp-usefull-blocks' )
		);
	}
	return;
}

$time_label = sprintf(
	/* translators: %d: estimated reading time in minutes */
	_n( '%d min read', '%d min read', $minutes, 'wp-usefull-blocks' ),
	$minutes
);

$parts = array();
if ( '' !== $prefix ) {
	$parts[] = $prefix;
}
$parts[] = $time_label;

if ( $show_word_count ) {
	$parts[] = sprintf(
		/* translators: %d: word count */
		_n( '(%d word)', '(%d words)', $word_count, 'wp-usefull-blocks' ),
		$word_count
	);
}

$display = implode( ' ', $parts );

$cache_payload = array(
	'postId'         => $post_id,
	'wordsPerMinute' => $wpm,
	'showWordCount'  => $show_word_count,
	'prefix'         => $prefix,
	'wordCount'      => $word_count,
	'minutes'        => $minutes,
	'modified'       => (string) get_post_modified_time( 'U', true, $post_id ),
);
$cache_key     = 'ub_reading_time_v1_' . md5( (string) wp_json_encode( $cache_payload ) );
$cached_html   = get_transient( $cache_key );

if ( is_string( $cached_html ) && '' !== $cached_html ) {
	echo $cached_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Cached markup is escaped below.
	return;
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'ub-reading-time',
	)
);

ob_start();
?>
<p <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() escapes. ?>>
	<span class="ub-reading-time__label"><?php echo esc_html( $display ); ?></span>
</p>
<?php

$html = (string) ob_get_clean();

/**
 * Filters the cache TTL for a rendered UB Reading Time block.
 *
 * @param int                  $ttl        TTL in seconds.
 * @param array<string, mixed> $attributes Block attributes used for the cache key.
 */
$ttl = (int) apply_filters( 'wp_usefull_blocks_ub_reading_time_cache_ttl', HOUR_IN_SECONDS, $attributes );
if ( $ttl > 0 ) {
	set_transient( $cache_key, $html, $ttl );
}

echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is escaped when built above.
