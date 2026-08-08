<?php
/**
 * Server-side render for the UB FAQ block.
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
$initially_open = ! empty( $attributes['initiallyOpen'] );

$items = array();
foreach ( $raw_items as $index => $raw_item ) {
	if ( ! is_array( $raw_item ) ) {
		continue;
	}

	$question = isset( $raw_item['question'] ) ? trim( (string) $raw_item['question'] ) : '';
	$answer   = isset( $raw_item['answer'] ) ? trim( (string) $raw_item['answer'] ) : '';
	$id       = isset( $raw_item['id'] ) ? sanitize_html_class( (string) $raw_item['id'] ) : '';

	if ( '' === $question && '' === $answer ) {
		continue;
	}

	if ( '' === $id ) {
		$id = 'ub-faq-' . (string) ( $index + 1 );
	}

	$items[] = array(
		'id'       => $id,
		'question' => $question,
		'answer'   => $answer,
	);
}

if ( array() === $items ) {
	return;
}

$cache_payload = array(
	'items'         => $items,
	'initiallyOpen' => $initially_open,
);

// v1: independent toggles, plain-text Q&A (timeline toggle pattern).
$cache_key   = 'ub_faq_v1_' . md5( (string) wp_json_encode( $cache_payload ) );
$cached_html = get_transient( $cache_key );

if ( is_string( $cached_html ) && '' !== $cached_html ) {
	echo $cached_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Cached markup is built with escaped values below.
	return;
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class'               => 'ub-faq',
		'data-wp-interactive' => 'wp-usefull-blocks/ub-faq',
	)
);

ob_start();
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() escapes. ?>>
	<div class="ub-faq__list">
		<?php foreach ( $items as $index => $item ) : ?>
			<?php
			$entry_id  = 'ub-faq-' . $item['id'];
			$button_id = $entry_id . '-question';
			$panel_id  = $entry_id . '-answer';
			$is_open   = $initially_open;
			$question  = '' !== $item['question']
				? $item['question']
				: sprintf(
					/* translators: %d: FAQ entry number */
					__( 'Question %d', 'wp-usefull-blocks' ),
					(int) $index + 1
				);

			$item_context = (string) wp_json_encode(
				array(
					'isOpen' => $is_open,
				),
				JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
			);
			?>
			<div
				class="ub-faq__item<?php echo $is_open ? ' is-open' : ''; ?>"
				data-wp-context="<?php echo esc_attr( $item_context ); ?>"
				data-wp-class--is-open="context.isOpen"
			>
				<h3 class="ub-faq__question-heading">
					<button
						type="button"
						id="<?php echo esc_attr( $button_id ); ?>"
						class="ub-faq__question"
						data-wp-on--click="actions.toggle"
						aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>"
						data-wp-bind--aria-expanded="context.isOpen"
						aria-controls="<?php echo esc_attr( $panel_id ); ?>"
					>
						<span class="ub-faq__question-text"><?php echo esc_html( $question ); ?></span>
						<span class="ub-faq__chevron" aria-hidden="true"></span>
					</button>
				</h3>
				<div
					id="<?php echo esc_attr( $panel_id ); ?>"
					class="ub-faq__answer"
					role="region"
					aria-labelledby="<?php echo esc_attr( $button_id ); ?>"
					<?php echo $is_open ? '' : 'hidden'; ?>
					data-wp-bind--hidden="!context.isOpen"
				>
					<?php echo esc_html( $item['answer'] ); ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
<?php

$html = (string) ob_get_clean();

/**
 * Filters the cache TTL for a rendered UB FAQ block.
 *
 * @param int                  $ttl        TTL in seconds.
 * @param array<string, mixed> $attributes Block attributes used for the cache key.
 */
$ttl = (int) apply_filters( 'wp_usefull_blocks_ub_faq_cache_ttl', WEEK_IN_SECONDS, $attributes );
if ( $ttl > 0 ) {
	set_transient( $cache_key, $html, $ttl );
}

echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is escaped when built above.
