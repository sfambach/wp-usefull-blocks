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

if ( ! function_exists( 'wp_usefull_blocks_ub_timeline_normalize_items' ) ) {
	/**
	 * Normalize timeline items from block attributes.
	 *
	 * @param mixed $raw_items Raw items attribute.
	 * @return array<int, array{id:string,title:string,description:string}>
	 */
	function wp_usefull_blocks_ub_timeline_normalize_items( mixed $raw_items ): array {
		if ( ! is_array( $raw_items ) ) {
			return array();
		}

		$items = array();

		foreach ( $raw_items as $index => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$title       = isset( $item['title'] ) ? sanitize_text_field( (string) $item['title'] ) : '';
			$description = isset( $item['description'] ) ? sanitize_textarea_field( (string) $item['description'] ) : '';
			$id          = isset( $item['id'] ) ? sanitize_key( (string) $item['id'] ) : '';

			if ( '' === $id ) {
				$id = 'item-' . (string) ( $index + 1 );
			}

			if ( '' === $title && '' === $description ) {
				continue;
			}

			$items[] = array(
				'id'          => $id,
				'title'       => $title,
				'description' => $description,
			);
		}

		return $items;
	}
}

$orientation    = isset( $attributes['orientation'] ) ? (string) $attributes['orientation'] : 'vertical';
$initially_open = ! empty( $attributes['initiallyOpen'] );
$items          = wp_usefull_blocks_ub_timeline_normalize_items( $attributes['items'] ?? array() );

if ( 'horizontal' !== $orientation ) {
	$orientation = 'vertical';
}

if ( array() === $items ) {
	return;
}

$cache_payload = array(
	'orientation'   => $orientation,
	'initiallyOpen' => $initially_open,
	'items'         => $items,
	'version'       => 'v1',
);
$cache_key     = 'ub_timeline_v1_' . md5( (string) wp_json_encode( $cache_payload ) );
$cached_html   = get_transient( $cache_key );

if ( is_string( $cached_html ) && '' !== $cached_html ) {
	echo $cached_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Cached markup is built with escaped values below.
	return;
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class'               => 'ub-timeline is-orientation-' . $orientation,
		'data-wp-interactive' => 'wp-usefull-blocks/ub-timeline',
	)
);

ob_start();
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by get_block_wrapper_attributes(). ?>>
	<ol class="ub-timeline__list">
		<?php foreach ( $items as $index => $item ) : ?>
			<?php
			$item_id          = (string) $item['id'];
			$title_id         = 'ub-timeline-title-' . $item_id;
			$panel_id         = 'ub-timeline-panel-' . $item_id;
			$item_context     = array(
				'isOpen' => $initially_open,
			);
			$item_context_json = (string) wp_json_encode(
				$item_context,
				JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
			);
			$title_label = '' !== $item['title']
				? $item['title']
				: sprintf(
					/* translators: %d: timeline entry number */
					__( 'Entry %d', 'wp-usefull-blocks' ),
					$index + 1
				);
			?>
			<li
				class="ub-timeline__item"
				data-wp-context="<?php echo esc_attr( $item_context_json ); ?>"
				data-wp-class--is-open="context.isOpen"
			>
				<span class="ub-timeline__marker" aria-hidden="true"></span>
				<div class="ub-timeline__content">
					<button
						type="button"
						id="<?php echo esc_attr( $title_id ); ?>"
						class="ub-timeline__title"
						data-wp-on--click="actions.toggle"
						data-wp-bind--aria-expanded="context.isOpen"
						aria-controls="<?php echo esc_attr( $panel_id ); ?>"
					>
						<?php echo esc_html( $title_label ); ?>
					</button>
					<div
						id="<?php echo esc_attr( $panel_id ); ?>"
						class="ub-timeline__description"
						role="region"
						aria-labelledby="<?php echo esc_attr( $title_id ); ?>"
						data-wp-bind--hidden="!context.isOpen"
					>
						<?php if ( '' !== $item['description'] ) : ?>
							<p><?php echo esc_html( $item['description'] ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			</li>
		<?php endforeach; ?>
	</ol>
</div>
<?php
$html = (string) ob_get_clean();

set_transient( $cache_key, $html, WEEK_IN_SECONDS );

echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup escaped above.
