<?php
/**
 * Server-side render for the UB Related Posts block.
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

$taxonomy         = isset( $attributes['taxonomy'] ) && 'post_tag' === $attributes['taxonomy'] ? 'post_tag' : 'category';
$term_slug        = isset( $attributes['term'] ) ? sanitize_title( (string) $attributes['term'] ) : '';
$include_children = ! isset( $attributes['includeChildren'] ) || ! empty( $attributes['includeChildren'] );
$exclude_current  = ! isset( $attributes['excludeCurrent'] ) || ! empty( $attributes['excludeCurrent'] );
$count            = isset( $attributes['count'] ) ? max( 1, min( 50, (int) $attributes['count'] ) ) : 10;
$order_by         = isset( $attributes['orderBy'] ) && in_array( $attributes['orderBy'], array( 'date', 'title', 'modified', 'rand' ), true ) ? (string) $attributes['orderBy'] : 'date';
$show_image       = ! empty( $attributes['showImage'] );
$show_date        = ! empty( $attributes['showDate'] );
$show_excerpt     = ! empty( $attributes['showExcerpt'] );
$columns          = isset( $attributes['columns'] ) ? max( 1, min( 4, (int) $attributes['columns'] ) ) : 3;
$is_editor        = is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST );

$post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : 0;
if ( $post_id <= 0 ) {
	$post_id = (int) get_the_ID();
}

if ( '' !== $term_slug ) {
	$term_ids = array();
	$term     = get_term_by( 'slug', $term_slug, $taxonomy );
	if ( $term instanceof WP_Term ) {
		$term_ids[] = (int) $term->term_id;
	}
} else {
	$term_ids = $post_id > 0 ? wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) ) : array();
	$term_ids = is_array( $term_ids ) ? array_map( 'intval', $term_ids ) : array();
}

$wrapper_class = 'ub-related-posts' . ( $show_image ? ' ub-related-posts--images ub-related-posts--cols-' . $columns : '' );

if ( array() === $term_ids ) {
	if ( $is_editor ) {
		printf(
			'<p %1$s>%2$s</p>',
			get_block_wrapper_attributes( array( 'class' => $wrapper_class . ' ub-related-posts--empty' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by helper.
			esc_html__( 'No matching category or tag found.', 'wp-usefull-blocks' )
		);
	}
	return;
}

$query = new WP_Query(
	array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => $count,
		'orderby'             => $order_by,
		'order'               => 'title' === $order_by ? 'ASC' : 'DESC',
		'post__not_in'        => $exclude_current && $post_id > 0 ? array( $post_id ) : array(),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_tax_query -- Filtering by term is the purpose of the block.
		'tax_query'           => array(
			array(
				'taxonomy'         => $taxonomy,
				'field'            => 'term_id',
				'terms'            => $term_ids,
				'include_children' => $include_children,
			),
		),
	)
);

if ( ! $query->have_posts() ) {
	if ( $is_editor ) {
		printf(
			'<p %1$s>%2$s</p>',
			get_block_wrapper_attributes( array( 'class' => $wrapper_class . ' ub-related-posts--empty' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by helper.
			esc_html__( 'No other posts found.', 'wp-usefull-blocks' )
		);
	}
	return;
}

$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $wrapper_class ) );
?>
<ul <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() escapes. ?>>
	<?php foreach ( $query->posts as $related ) : ?>
		<li class="ub-related-posts__item">
			<?php if ( $show_image && has_post_thumbnail( $related ) ) : ?>
				<a class="ub-related-posts__image" href="<?php echo esc_url( get_permalink( $related ) ); ?>" tabindex="-1" aria-hidden="true">
					<?php echo get_the_post_thumbnail( $related, 'medium', array( 'alt' => '' ) ); ?>
				</a>
			<?php endif; ?>
			<a class="ub-related-posts__title" href="<?php echo esc_url( get_permalink( $related ) ); ?>"><?php echo esc_html( get_the_title( $related ) ); ?></a>
			<?php if ( $show_date ) : ?>
				<time class="ub-related-posts__date" datetime="<?php echo esc_attr( get_the_date( 'c', $related ) ); ?>"><?php echo esc_html( get_the_date( '', $related ) ); ?></time>
			<?php endif; ?>
			<?php if ( $show_excerpt ) : ?>
				<p class="ub-related-posts__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt( $related ), 25 ) ); ?></p>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>
</ul>
