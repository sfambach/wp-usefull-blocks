<?php
/**
 * Server-side render for the UB File block.
 *
 * @package WpUsefullBlocks
 *
 * @var array $attributes Block attributes.
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$source_url      = isset( $attributes['sourceUrl'] ) ? esc_url( (string) $attributes['sourceUrl'] ) : '';
$label           = isset( $attributes['label'] ) ? sanitize_text_field( (string) $attributes['label'] ) : '';
$attachment_id   = isset( $attributes['attachmentId'] ) ? absint( $attributes['attachmentId'] ) : 0;
$link_behavior   = isset( $attributes['linkBehavior'] ) ? sanitize_key( (string) $attributes['linkBehavior'] ) : 'original-fallback-local';
$show_status     = ! isset( $attributes['showStatus'] ) || (bool) $attributes['showStatus'];
$last_status     = isset( $attributes['lastStatus'] ) ? sanitize_key( (string) $attributes['lastStatus'] ) : 'unknown';

if ( ! in_array( $link_behavior, array( 'original-fallback-local', 'original-only', 'local-only' ), true ) ) {
	$link_behavior = 'original-fallback-local';
}

if ( '' === $source_url && $attachment_id < 1 ) {
	return;
}

$local_url = $attachment_id > 0 ? (string) wp_get_attachment_url( $attachment_id ) : '';

$href = $source_url;
if ( 'local-only' === $link_behavior ) {
	$href = $local_url ? $local_url : $source_url;
} elseif ( 'original-fallback-local' === $link_behavior ) {
	if ( 'broken' === $last_status && $local_url ) {
		$href = $local_url;
	}
}

if ( '' === $href ) {
	return;
}

if ( '' === $label ) {
	$label = __( 'Download file', 'wp-usefull-blocks' );
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'ub-file',
	)
);
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by helper. ?>>
	<a class="ub-file__anchor" href="<?php echo esc_url( $href ); ?>">
		<?php echo esc_html( $label ); ?>
	</a>
	<?php
	if ( $show_status && class_exists( 'WP_Usefull_Blocks_Status_Render' ) ) {
		echo WP_Usefull_Blocks_Status_Render::indicator( $last_status ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	?>
	<?php if ( $attachment_id > 0 && $local_url ) : ?>
		<span class="ub-file__local-note">
			<?php
			echo esc_html__(
				'(local copy available)',
				'wp-usefull-blocks'
			);
			?>
		</span>
	<?php endif; ?>
</div>
<?php
