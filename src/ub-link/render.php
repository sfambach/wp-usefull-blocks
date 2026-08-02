<?php
/**
 * Server-side render for the UB Link block.
 *
 * @package WpUsefullBlocks
 *
 * @var array $attributes Block attributes.
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$url             = isset( $attributes['url'] ) ? esc_url( (string) $attributes['url'] ) : '';
$label           = isset( $attributes['label'] ) ? sanitize_text_field( (string) $attributes['label'] ) : '';
$open_in_new_tab = ! empty( $attributes['openInNewTab'] );
$show_status     = ! isset( $attributes['showStatus'] ) || (bool) $attributes['showStatus'];
$stored_status   = isset( $attributes['lastStatus'] ) ? sanitize_key( (string) $attributes['lastStatus'] ) : 'unknown';

if ( '' === $url ) {
	return;
}

if ( '' === $label ) {
	$label = $url;
}

$status_payload = array( 'status' => $stored_status );
if ( class_exists( 'WP_Usefull_Blocks_Url_Status' ) ) {
	// Page load: use cached status, or check now and cache (background cron also refreshes).
	$status_payload = WP_Usefull_Blocks_Url_Status::resolve_for_render( $url, $stored_status );
}
$status = isset( $status_payload['status'] ) ? sanitize_key( (string) $status_payload['status'] ) : 'unknown';

$classes = array( 'ub-link', 'ub-link--' . $status );
if ( 'broken' === $status ) {
	$classes[] = 'is-broken';
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => implode( ' ', $classes ),
	)
);

$rel          = $open_in_new_tab ? 'noopener noreferrer' : '';
$anchor_title = '';
if ( 'broken' === $status ) {
	$anchor_title = __( 'This link appears to be broken.', 'wp-usefull-blocks' );
}
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by helper. ?>>
	<a
		class="ub-link__anchor"
		href="<?php echo esc_url( $url ); ?>"
		<?php if ( $open_in_new_tab ) : ?>
			target="_blank"
			rel="<?php echo esc_attr( $rel ); ?>"
		<?php endif; ?>
		<?php if ( '' !== $anchor_title ) : ?>
			title="<?php echo esc_attr( $anchor_title ); ?>"
		<?php endif; ?>
	>
		<?php echo esc_html( $label ); ?>
	</a>
	<?php
	if ( $show_status && class_exists( 'WP_Usefull_Blocks_Status_Render' ) ) {
		echo WP_Usefull_Blocks_Status_Render::indicator( $status ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	?>
</div>
<?php
