<?php
/**
 * Server-side render for the UB Timeline block.
 *
 * Note: WordPress loads this file via require() inside an output buffer.
 * Echo markup; do not return a string (returns are discarded).
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

$raw_items      = isset( $attributes['items'] ) && is_array( $attributes['items'] ) ? $attributes['items'] : array();
$orientation    = isset( $attributes['orientation'] ) ? sanitize_key( (string) $attributes['orientation'] ) : 'vertical';
$initially_open = ! empty( $attributes['initiallyOpen'] );

if ( ! in_array( $orientation, array( 'vertical', 'horizontal' ), true ) ) {
	$orientation = 'vertical';
}

$items = array();
foreach ( $raw_items as $index => $raw_item ) {
	if ( ! is_array( $raw_item ) ) {
		continue;
	}

	$title       = isset( $raw_item['title'] ) ? trim( (string) $raw_item['title'] ) : '';
	$description = isset( $raw_item['description'] ) ? trim( (string) $raw_item['description'] ) : '';
	$id          = isset( $raw_item['id'] ) ? sanitize_html_class( (string) $raw_item['id'] ) : '';

	if ( '' === $title && '' === $description ) {
		continue;
	}

	if ( '' === $id ) {
		$id = 'ub-tl-' . (string) ( $index + 1 );
	}

	$items[] = array(
		'id'          => $id,
		'title'       => $title,
		'description' => $description,
	);
}

if ( array() === $items ) {
	return;
}

$cache_payload = array(
	'locale'        => get_locale(),
	'orientation'   => $orientation,
	'items'         => $items,
	'initiallyOpen' => $initially_open,
);

// v1: independent toggles, plain-text descriptions, orientation modifier classes.
$cache_key   = 'ub_timeline_v1_' . md5( (string) wp_json_encode( $cache_payload ) );
$cached_html = get_transient( $cache_key );

if ( is_string( $cached_html ) && '' !== $cached_html ) {
	echo $cached_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Cached markup is built with escaped values below.
	return;
}

$wrapper_classes = array(
	'ub-timeline',
	'is-orientation-' . $orientation,
);

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class'               => implode( ' ', $wrapper_classes ),
		'data-wp-interactive' => 'wp-usefull-blocks/ub-timeline',
	)
);

ob_start();
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() escapes. ?>>
	<ol class="ub-timeline__list" role="list">
		<?php foreach ( $items as $index => $item ) : ?>
			<?php
			$entry_id   = 'ub-timeline-' . $item['id'];
			$button_id  = $entry_id . '-title';
			$panel_id   = $entry_id . '-panel';
			$is_open    = $initially_open;
			$item_title = '' !== $item['title']
				? $item['title']
				: sprintf(
					/* translators: %d: timeline entry number */
					__( 'Entry %d', 'wp-usefull-blocks' ),
					(int) $index + 1
				);

			$item_context = (string) wp_json_encode(
				array(
					'isOpen' => $is_open,
				),
				JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
			);
			?>
			<li
				class="ub-timeline__item<?php echo $is_open ? ' is-open' : ''; ?>"
				data-wp-context="<?php echo esc_attr( $item_context ); ?>"
				data-wp-class--is-open="context.isOpen"
			>
				<div class="ub-timeline__marker" aria-hidden="true"></div>
				<div class="ub-timeline__content">
					<button
						type="button"
						id="<?php echo esc_attr( $button_id ); ?>"
						class="ub-timeline__title"
						data-wp-on--click="actions.toggle"
						aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>"
						data-wp-bind--aria-expanded="context.isOpen"
						aria-controls="<?php echo esc_attr( $panel_id ); ?>"
					>
						<?php echo esc_html( $item_title ); ?>
					</button>
					<div
						id="<?php echo esc_attr( $panel_id ); ?>"
						class="ub-timeline__description"
						role="region"
						aria-labelledby="<?php echo esc_attr( $button_id ); ?>"
						<?php echo $is_open ? '' : 'hidden'; ?>
						data-wp-bind--hidden="!context.isOpen"
					>
						<?php echo esc_html( $item['description'] ); ?>
					</div>
				</div>
			</li>
		<?php endforeach; ?>
	</ol>
</div>
<?php

$html = (string) ob_get_clean();

/**
 * Filters the cache TTL for a rendered UB Timeline block.
 *
 * @param int                  $ttl        TTL in seconds.
 * @param array<string, mixed> $attributes Block attributes used for the cache key.
 */
$ttl = (int) apply_filters( 'wp_usefull_blocks_ub_timeline_cache_ttl', WEEK_IN_SECONDS, $attributes );
if ( $ttl > 0 ) {
	set_transient( $cache_key, $html, $ttl );
}

echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is escaped when built above.
