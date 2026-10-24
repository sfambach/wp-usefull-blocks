<?php
/**
 * Server-side render for the UB Post Tags block.
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

$expand_inline = ! isset( $attributes['expandInline'] ) || ! empty( $attributes['expandInline'] );
$per_tag       = isset( $attributes['postsPerTag'] ) ? max( 1, min( 30, (int) $attributes['postsPerTag'] ) ) : 10;
$show_count    = ! isset( $attributes['showCount'] ) || ! empty( $attributes['showCount'] );

$post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : 0;
if ( $post_id <= 0 ) {
	$post_id = (int) get_the_ID();
}

$tags = $post_id > 0 ? get_the_terms( $post_id, 'post_tag' ) : false;

if ( ! is_array( $tags ) || array() === $tags ) {
	if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		printf(
			'<p %1$s>%2$s</p>',
			get_block_wrapper_attributes( array( 'class' => 'ub-post-tags ub-post-tags--empty' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by helper.
			esc_html__( 'This post has no tags yet.', 'wp-usefull-blocks' )
		);
	}
	return;
}

usort(
	$tags,
	static function ( WP_Term $a, WP_Term $b ): int {
		return strcasecmp( $a->name, $b->name );
	}
);

$uid    = wp_unique_id( 'ub-post-tags-' );
$panels = array();

$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'ub-post-tags' ) );
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() escapes. ?>>
	<ul class="ub-post-tags__list">
		<?php
		foreach ( $tags as $tag ) :
			$link     = get_term_link( $tag );
			$link     = is_wp_error( $link ) ? '' : $link;
			$panel_id = $uid . '-' . $tag->term_id;
			$others   = max( 0, (int) $tag->count - 1 );
			?>
			<li>
				<a class="ub-post-tags__tag" href="<?php echo esc_url( $link ); ?>"
					<?php if ( $expand_inline && $others > 0 ) : ?>
						data-ub-panel="<?php echo esc_attr( $panel_id ); ?>" aria-controls="<?php echo esc_attr( $panel_id ); ?>" aria-expanded="false"
					<?php endif; ?>
				><?php echo esc_html( $tag->name ); ?>
					<?php if ( $show_count ) : ?>
						<span class="ub-post-tags__count"><?php echo esc_html( (string) $others ); ?></span>
					<?php endif; ?>
				</a>
			</li>
			<?php
			if ( $expand_inline && $others > 0 ) {
				$panels[] = array(
					'id'   => $panel_id,
					'tag'  => $tag,
					'link' => $link,
				);
			}
		endforeach;
		?>
	</ul>
	<?php
	foreach ( $panels as $panel ) :
		$tagged = get_posts(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'posts_per_page'      => $per_tag,
				'post__not_in'        => array( $post_id ),
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_tax_query -- Listing posts of one tag is the purpose of the block.
				'tax_query'           => array(
					array(
						'taxonomy' => 'post_tag',
						'field'    => 'term_id',
						'terms'    => array( (int) $panel['tag']->term_id ),
					),
				),
			)
		);
		?>
		<div class="ub-post-tags__panel" id="<?php echo esc_attr( $panel['id'] ); ?>" hidden>
			<ul class="ub-post-tags__posts">
				<?php foreach ( $tagged as $tagged_post ) : ?>
					<li><a href="<?php echo esc_url( get_permalink( $tagged_post ) ); ?>"><?php echo esc_html( get_the_title( $tagged_post ) ); ?></a></li>
				<?php endforeach; ?>
			</ul>
			<?php if ( '' !== $panel['link'] ) : ?>
				<a class="ub-post-tags__all" href="<?php echo esc_url( $panel['link'] ); ?>">
					<?php
					printf(
						/* translators: %s: tag name */
						esc_html__( 'All posts tagged “%s”', 'wp-usefull-blocks' ),
						esc_html( $panel['tag']->name )
					);
					?>
				</a>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>
