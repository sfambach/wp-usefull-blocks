<?php
/**
 * Server-side render for the UB Gallery block.
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

if ( ! function_exists( 'wp_usefull_blocks_ub_gallery_normalize_images' ) ) {
	return;
}

$raw_images     = isset( $attributes['images'] ) && is_array( $attributes['images'] ) ? $attributes['images'] : array();
$images         = wp_usefull_blocks_ub_gallery_normalize_images( $raw_images );
$on_image_click = isset( $attributes['onImageClick'] ) ? sanitize_key( (string) $attributes['onImageClick'] ) : 'lightbox';
$selected_index = isset( $attributes['selectedIndex'] ) ? absint( $attributes['selectedIndex'] ) : 0;

if ( ! in_array( $on_image_click, array( 'lightbox', 'media', 'none' ), true ) ) {
	$on_image_click = 'lightbox';
}

if ( array() === $images ) {
	return;
}

if ( $selected_index >= count( $images ) ) {
	$selected_index = 0;
}

$cache_payload = array(
	'images'        => $images,
	'selectedIndex' => $selected_index,
	'onImageClick'  => $on_image_click,
);
$cache_key     = 'ub_gallery_' . md5( (string) wp_json_encode( $cache_payload ) );
$cached_html   = get_transient( $cache_key );

if ( is_string( $cached_html ) && '' !== $cached_html ) {
	echo $cached_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Cached markup is built with escaped values below.
	return;
}

$context = array(
	'images'        => $images,
	'selectedIndex' => $selected_index,
	'onImageClick'  => $on_image_click,
	'lightboxOpen'  => false,
);

$context_json = (string) wp_json_encode(
	$context,
	JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
);

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class'                        => 'ub-gallery',
		'data-wp-interactive'          => 'wp-usefull-blocks/ub-gallery',
		'data-wp-context'              => $context_json,
		'data-wp-on-document--keydown' => 'actions.handleKeydown',
	)
);

$focus_image = $images[ $selected_index ];

ob_start();
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() escapes. ?>>
	<figure class="ub-gallery__focus">
		<?php if ( 'media' === $on_image_click ) : ?>
			<a
				class="ub-gallery__focus-media"
				data-wp-bind--href="state.focusFullUrl"
				href="<?php echo esc_url( (string) $focus_image['fullUrl'] ); ?>"
			>
				<img
					class="ub-gallery__focus-image"
					src="<?php echo esc_url( (string) $focus_image['url'] ); ?>"
					<?php if ( ! empty( $focus_image['srcset'] ) ) : ?>
						srcset="<?php echo esc_attr( (string) $focus_image['srcset'] ); ?>"
						sizes="<?php echo esc_attr( (string) $focus_image['sizes'] ); ?>"
					<?php endif; ?>
					alt="<?php echo esc_attr( (string) $focus_image['alt'] ); ?>"
					data-wp-bind--src="state.focusUrl"
					data-wp-bind--srcset="state.focusSrcset"
					data-wp-bind--sizes="state.focusSizes"
					data-wp-bind--alt="state.focusAlt"
					loading="eager"
					decoding="async"
				/>
			</a>
		<?php elseif ( 'lightbox' === $on_image_click ) : ?>
			<button
				type="button"
				class="ub-gallery__focus-button"
				data-wp-on--click="actions.openLightbox"
				aria-label="<?php echo esc_attr__( 'Open image in lightbox', 'wp-usefull-blocks' ); ?>"
			>
				<img
					class="ub-gallery__focus-image"
					src="<?php echo esc_url( (string) $focus_image['url'] ); ?>"
					<?php if ( ! empty( $focus_image['srcset'] ) ) : ?>
						srcset="<?php echo esc_attr( (string) $focus_image['srcset'] ); ?>"
						sizes="<?php echo esc_attr( (string) $focus_image['sizes'] ); ?>"
					<?php endif; ?>
					alt="<?php echo esc_attr( (string) $focus_image['alt'] ); ?>"
					data-wp-bind--src="state.focusUrl"
					data-wp-bind--srcset="state.focusSrcset"
					data-wp-bind--sizes="state.focusSizes"
					data-wp-bind--alt="state.focusAlt"
					loading="eager"
					decoding="async"
				/>
			</button>
		<?php else : ?>
			<div class="ub-gallery__focus-media ub-gallery__focus-media--static">
				<img
					class="ub-gallery__focus-image"
					src="<?php echo esc_url( (string) $focus_image['url'] ); ?>"
					<?php if ( ! empty( $focus_image['srcset'] ) ) : ?>
						srcset="<?php echo esc_attr( (string) $focus_image['srcset'] ); ?>"
						sizes="<?php echo esc_attr( (string) $focus_image['sizes'] ); ?>"
					<?php endif; ?>
					alt="<?php echo esc_attr( (string) $focus_image['alt'] ); ?>"
					data-wp-bind--src="state.focusUrl"
					data-wp-bind--srcset="state.focusSrcset"
					data-wp-bind--sizes="state.focusSizes"
					data-wp-bind--alt="state.focusAlt"
					loading="eager"
					decoding="async"
				/>
			</div>
		<?php endif; ?>

		<figcaption
			class="ub-gallery__focus-caption"
			data-wp-bind--hidden="!state.hasCaption"
			data-wp-text="state.focusCaption"
			<?php echo empty( $focus_image['caption'] ) ? 'hidden' : ''; ?>
		>
			<?php echo esc_html( (string) $focus_image['caption'] ); ?>
		</figcaption>
	</figure>

	<?php if ( count( $images ) > 1 ) : ?>
		<ul class="ub-gallery__thumbs" role="list">
			<?php foreach ( $images as $index => $image ) : ?>
				<?php
				$thumb_context = (string) wp_json_encode(
					array( 'thumbIndex' => (int) $index ),
					JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
				);
				?>
				<li class="ub-gallery__thumb-item">
					<button
						type="button"
						class="ub-gallery__thumb"
						data-wp-context="<?php echo esc_attr( $thumb_context ); ?>"
						data-image-index="<?php echo esc_attr( (string) $index ); ?>"
						data-wp-on--click="actions.selectImage"
						aria-label="<?php
						echo esc_attr(
							sprintf(
								/* translators: %d: image number in the gallery */
								__( 'Show image %d', 'wp-usefull-blocks' ),
								(int) $index + 1
							)
						);
						?>"
						aria-selected="<?php echo ( (int) $index === $selected_index ) ? 'true' : 'false'; ?>"
						data-wp-bind--aria-selected="state.isThumbSelected"
					>
						<img
							class="ub-gallery__thumb-image"
							src="<?php echo esc_url( (string) $image['thumb'] ); ?>"
							alt=""
							loading="lazy"
							decoding="async"
						/>
					</button>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ( 'lightbox' === $on_image_click ) : ?>
		<div
			class="ub-gallery__lightbox"
			data-wp-bind--hidden="!context.lightboxOpen"
			hidden
			data-wp-on--click="actions.closeLightbox"
			role="presentation"
		>
			<div
				class="ub-gallery__lightbox-dialog"
				role="dialog"
				aria-modal="true"
				aria-label="<?php echo esc_attr__( 'Image lightbox', 'wp-usefull-blocks' ); ?>"
				data-wp-on--click="actions.stopPropagation"
			>
				<button
					type="button"
					class="ub-gallery__lightbox-close"
					data-wp-on--click="actions.closeLightbox"
					aria-label="<?php echo esc_attr__( 'Close lightbox', 'wp-usefull-blocks' ); ?>"
				>
					&times;
				</button>
				<img
					class="ub-gallery__lightbox-image"
					src="<?php echo esc_url( (string) $focus_image['fullUrl'] ); ?>"
					alt="<?php echo esc_attr( (string) $focus_image['alt'] ); ?>"
					data-wp-bind--src="state.focusFullUrl"
					data-wp-bind--alt="state.focusAlt"
				/>
			</div>
		</div>
	<?php endif; ?>
</div>
<?php

$html = (string) ob_get_clean();

/**
 * Filters the cache TTL for a rendered UB Gallery block.
 *
 * @param int                  $ttl        TTL in seconds.
 * @param array<string, mixed> $attributes Block attributes used for the cache key.
 */
$ttl = (int) apply_filters( 'wp_usefull_blocks_ub_gallery_cache_ttl', HOUR_IN_SECONDS, $attributes );
if ( $ttl > 0 ) {
	set_transient( $cache_key, $html, $ttl );
}

echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is escaped when built above.
