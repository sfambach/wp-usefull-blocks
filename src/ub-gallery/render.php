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
$size_slug      = isset( $attributes['sizeSlug'] ) ? sanitize_key( (string) $attributes['sizeSlug'] ) : 'large';
$images         = wp_usefull_blocks_ub_gallery_normalize_images( $raw_images, $size_slug );
$link_to        = wp_usefull_blocks_ub_gallery_get_link_to( $attributes );
$link_target    = isset( $attributes['linkTarget'] ) ? (string) $attributes['linkTarget'] : '';
$image_crop     = ! isset( $attributes['imageCrop'] ) || (bool) $attributes['imageCrop'];
$random_order   = ! empty( $attributes['randomOrder'] );
$selected_index = isset( $attributes['selectedIndex'] ) ? absint( $attributes['selectedIndex'] ) : 0;
$gallery_caption = isset( $attributes['caption'] ) ? wp_kses_post( (string) $attributes['caption'] ) : '';
$columns        = isset( $attributes['columns'] ) ? absint( $attributes['columns'] ) : 0;

if ( '_blank' !== $link_target ) {
	$link_target = '';
}

if ( array() === $images ) {
	return;
}

if ( $random_order ) {
	shuffle( $images );
	$selected_index = 0;
}

if ( $selected_index >= count( $images ) ) {
	$selected_index = 0;
}

if ( $columns < 1 ) {
	$columns = (int) min( 3, count( $images ) );
}
$columns = (int) max( 1, min( 8, $columns, count( $images ) ) );

$cache_payload = array(
	'images'        => $images,
	'selectedIndex' => $selected_index,
	'linkTo'        => $link_to,
	'linkTarget'    => $link_target,
	'sizeSlug'      => $size_slug,
	'imageCrop'     => $image_crop,
	'columns'       => $columns,
	'caption'       => $gallery_caption,
);

// Randomized galleries must not be cached across requests.
$cache_key   = 'ub_gallery_' . md5( (string) wp_json_encode( $cache_payload ) );
$cached_html = $random_order ? false : get_transient( $cache_key );

if ( is_string( $cached_html ) && '' !== $cached_html ) {
	echo $cached_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Cached markup is built with escaped values below.
	return;
}

$context = array(
	'images'        => $images,
	'selectedIndex' => $selected_index,
	'linkTo'        => $link_to,
	'lightboxOpen'  => false,
);

$context_json = (string) wp_json_encode(
	$context,
	JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
);

$wrapper_classes = array( 'ub-gallery' );
if ( $image_crop ) {
	$wrapper_classes[] = 'is-cropped';
}
$wrapper_classes[] = 'has-nested-images';
$wrapper_classes[] = 'columns-' . $columns;

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class'                        => implode( ' ', $wrapper_classes ),
		'style'                        => '--ub-gallery-columns:' . $columns . ';',
		'data-wp-interactive'          => 'wp-usefull-blocks/ub-gallery',
		'data-wp-context'              => $context_json,
		'data-wp-on-document--keydown' => 'actions.handleKeydown',
	)
);

$focus_image = $images[ $selected_index ];
$focus_href  = '';

if ( 'media' === $link_to ) {
	$focus_href = (string) $focus_image['fullUrl'];
} elseif ( 'attachment' === $link_to ) {
	$focus_href = (string) ( $focus_image['attachmentUrl'] ?? '' );
	if ( '' === $focus_href ) {
		$focus_href = (string) $focus_image['fullUrl'];
	}
}

$link_rel = '_blank' === $link_target ? 'noopener noreferrer' : '';

ob_start();
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() escapes. ?>>
	<figure class="ub-gallery__focus">
		<?php if ( in_array( $link_to, array( 'media', 'attachment' ), true ) ) : ?>
			<a
				class="ub-gallery__focus-media"
				data-wp-bind--href="state.focusHref"
				href="<?php echo esc_url( $focus_href ); ?>"
				<?php if ( '' !== $link_target ) : ?>
					target="<?php echo esc_attr( $link_target ); ?>"
					rel="<?php echo esc_attr( $link_rel ); ?>"
				<?php endif; ?>
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
		<?php elseif ( 'lightbox' === $link_to ) : ?>
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

	<?php if ( '' !== trim( wp_strip_all_tags( $gallery_caption ) ) ) : ?>
		<figcaption class="ub-gallery__caption blocks-gallery-caption">
			<?php echo $gallery_caption; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Filtered with wp_kses_post. ?>
		</figcaption>
	<?php endif; ?>

	<?php if ( 'lightbox' === $link_to ) : ?>
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
if ( ! $random_order && $ttl > 0 ) {
	set_transient( $cache_key, $html, $ttl );
}

echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is escaped when built above.
