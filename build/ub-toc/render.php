<?php
/**
 * Server-side render for the UB Table of Contents block.
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

if ( ! class_exists( 'WP_Usefull_Blocks_Toc' ) ) {
	return;
}

$title     = isset( $attributes['title'] ) ? trim( (string) $attributes['title'] ) : '';
$min_level = isset( $attributes['minLevel'] ) ? (int) $attributes['minLevel'] : 2;
$max_level = isset( $attributes['maxLevel'] ) ? (int) $attributes['maxLevel'] : 3;
$ordered   = ! empty( $attributes['ordered'] );

$post_id = 0;
if ( isset( $block->context['postId'] ) ) {
	$post_id = (int) $block->context['postId'];
}
if ( $post_id <= 0 ) {
	$post_id = (int) get_the_ID();
}

$headings = WP_Usefull_Blocks_Toc::get_headings( $post_id, $min_level, $max_level );

if ( array() === $headings ) {
	if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class' => 'ub-toc ub-toc--empty',
			)
		);
		printf(
			'<nav %1$s><p class="ub-toc__empty">%2$s</p></nav>',
			$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by helper.
			esc_html__( 'No headings found in this post for the selected levels.', 'wp-usefull-blocks' )
		);
	}
	return;
}

$cache_payload = array(
	'locale'   => get_locale(),
	'postId'   => $post_id,
	'title'    => $title,
	'minLevel' => $min_level,
	'maxLevel' => $max_level,
	'ordered'  => $ordered,
	'headings' => $headings,
	'modified' => (string) get_post_modified_time( 'U', true, $post_id ),
);
$cache_key     = 'ub_toc_v1_' . md5( (string) wp_json_encode( $cache_payload ) );
$cached_html   = get_transient( $cache_key );

if ( is_string( $cached_html ) && '' !== $cached_html ) {
	echo $cached_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Cached markup is escaped below.
	return;
}

$list_tag = $ordered ? 'ol' : 'ul';
$base     = isset( $headings[0]['level'] ) ? (int) $headings[0]['level'] : $min_level;

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class'      => 'ub-toc',
		'aria-label' => '' !== $title
			? $title
			: __( 'Table of contents', 'wp-usefull-blocks' ),
	)
);

ob_start();
?>
<nav <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() escapes. ?>>
	<?php if ( '' !== $title ) : ?>
		<p class="ub-toc__title"><?php echo esc_html( $title ); ?></p>
	<?php endif; ?>
	<<?php echo esc_html( $list_tag ); ?> class="ub-toc__list">
		<?php foreach ( $headings as $heading ) : ?>
			<?php
			$depth = max( 0, (int) $heading['level'] - $base );
			?>
			<li class="ub-toc__item is-level-<?php echo esc_attr( (string) (int) $heading['level'] ); ?>" style="--ub-toc-depth: <?php echo esc_attr( (string) $depth ); ?>;">
				<a class="ub-toc__link" href="#<?php echo esc_attr( $heading['id'] ); ?>">
					<?php echo esc_html( $heading['text'] ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</<?php echo esc_html( $list_tag ); ?>>
</nav>
<?php

$html = (string) ob_get_clean();

/**
 * Filters the cache TTL for a rendered UB TOC block.
 *
 * @param int                  $ttl        TTL in seconds.
 * @param array<string, mixed> $attributes Block attributes used for the cache key.
 */
$ttl = (int) apply_filters( 'wp_usefull_blocks_ub_toc_cache_ttl', HOUR_IN_SECONDS, $attributes );
if ( $ttl > 0 ) {
	set_transient( $cache_key, $html, $ttl );
}

echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is escaped when built above.
