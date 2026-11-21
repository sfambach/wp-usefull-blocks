<?php
/**
 * Server-side render for the UB Callout block.
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

$variant   = isset( $attributes['variant'] ) ? sanitize_key( (string) $attributes['variant'] ) : 'info';
$title     = isset( $attributes['title'] ) ? trim( (string) $attributes['title'] ) : '';
$body      = isset( $attributes['content'] ) ? trim( (string) $attributes['content'] ) : '';
$show_icon = ! isset( $attributes['showIcon'] ) || (bool) $attributes['showIcon'];

if ( ! in_array( $variant, array( 'info', 'tip', 'warning', 'success' ), true ) ) {
	$variant = 'info';
}

if ( '' === $title && '' === $body ) {
	return;
}

if ( $show_icon ) {
	wp_enqueue_style( 'dashicons' );
}

$labels = array(
	'info'    => __( 'Info', 'wp-usefull-blocks' ),
	'tip'     => __( 'Tip', 'wp-usefull-blocks' ),
	'warning' => __( 'Warning', 'wp-usefull-blocks' ),
	'success' => __( 'Success', 'wp-usefull-blocks' ),
);

$icons = array(
	'info'    => 'info',
	'tip'     => 'lightbulb',
	'warning' => 'warning',
	'success' => 'yes-alt',
);

$cache_payload = array(
	'locale'   => get_locale(),
	'variant'  => $variant,
	'title'    => $title,
	'content'  => $body,
	'showIcon' => $show_icon,
);
$cache_key     = 'ub_callout_v1_' . md5( (string) wp_json_encode( $cache_payload ) );
$cached_html   = get_transient( $cache_key );

if ( is_string( $cached_html ) && '' !== $cached_html ) {
	echo $cached_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Cached markup is escaped below.
	return;
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class'      => 'ub-callout is-variant-' . $variant,
		'role'       => 'note',
		'aria-label' => $labels[ $variant ],
	)
);

ob_start();
?>
<aside <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() escapes. ?>>
	<?php if ( $show_icon ) : ?>
		<span class="ub-callout__icon dashicons dashicons-<?php echo esc_attr( $icons[ $variant ] ); ?>" aria-hidden="true"></span>
	<?php endif; ?>
	<div class="ub-callout__body">
		<?php if ( '' !== $title ) : ?>
			<p class="ub-callout__title"><?php echo esc_html( $title ); ?></p>
		<?php endif; ?>
		<?php if ( '' !== $body ) : ?>
			<p class="ub-callout__content"><?php echo esc_html( $body ); ?></p>
		<?php endif; ?>
	</div>
</aside>
<?php

$html = (string) ob_get_clean();

/**
 * Filters the cache TTL for a rendered UB Callout block.
 *
 * @param int                  $ttl        TTL in seconds.
 * @param array<string, mixed> $attributes Block attributes used for the cache key.
 */
$ttl = (int) apply_filters( 'wp_usefull_blocks_ub_callout_cache_ttl', WEEK_IN_SECONDS, $attributes );
if ( $ttl > 0 ) {
	set_transient( $cache_key, $html, $ttl );
}

echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is escaped when built above.
