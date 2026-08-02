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

$source_url    = isset( $attributes['sourceUrl'] ) ? esc_url( (string) $attributes['sourceUrl'] ) : '';
$label         = isset( $attributes['label'] ) ? sanitize_text_field( (string) $attributes['label'] ) : '';
$attachment_id = isset( $attributes['attachmentId'] ) ? absint( $attributes['attachmentId'] ) : 0;
$link_behavior = isset( $attributes['linkBehavior'] ) ? sanitize_key( (string) $attributes['linkBehavior'] ) : 'original-fallback-local';
$stored_status = isset( $attributes['lastStatus'] ) ? sanitize_key( (string) $attributes['lastStatus'] ) : 'unknown';

$plugin_settings = class_exists( 'WP_Usefull_Blocks_Settings' )
	? WP_Usefull_Blocks_Settings::get()
	: array(
		'show_link_status'     => true,
		'link_status_position' => 'before',
		'strike_broken_links'  => true,
		'auto_check_urls'      => true,
	);

$show_status   = ! empty( $plugin_settings['show_link_status'] );
$status_before = 'after' !== ( $plugin_settings['link_status_position'] ?? 'before' );
$strike_broken = ! empty( $plugin_settings['strike_broken_links'] );
$auto_check    = ! empty( $plugin_settings['auto_check_urls'] );

if ( ! in_array( $link_behavior, array( 'original-fallback-local', 'original-only', 'local-only' ), true ) ) {
	$link_behavior = 'original-fallback-local';
}

if ( '' === $source_url && $attachment_id < 1 ) {
	return;
}

$status = $stored_status;
if ( '' !== $source_url && class_exists( 'WP_Usefull_Blocks_Url_Status' ) ) {
	if ( $auto_check ) {
		$status_payload = WP_Usefull_Blocks_Url_Status::resolve_for_render( $source_url, $stored_status );
	} else {
		$cached         = WP_Usefull_Blocks_Url_Status::get_cached( $source_url );
		$status_payload = null !== $cached ? $cached : array( 'status' => $stored_status );
	}
	$status = isset( $status_payload['status'] ) ? sanitize_key( (string) $status_payload['status'] ) : 'unknown';
}

$local_url = $attachment_id > 0 ? (string) wp_get_attachment_url( $attachment_id ) : '';

$href = $source_url;
if ( 'local-only' === $link_behavior ) {
	$href = $local_url ? $local_url : $source_url;
} elseif ( 'original-fallback-local' === $link_behavior ) {
	if ( 'broken' === $status && $local_url ) {
		$href = $local_url;
	}
}

if ( '' === $href ) {
	return;
}

if ( '' === $label ) {
	$label = __( 'Download file', 'wp-usefull-blocks' );
}

$classes = array( 'ub-file', 'ub-file--' . $status );
// Strike through only when the visible link still points at a broken original URL.
if ( $strike_broken && 'broken' === $status && $href === $source_url ) {
	$classes[] = 'is-broken';
}
if ( $show_status ) {
	$classes[] = $status_before ? 'ub-file--status-before' : 'ub-file--status-after';
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => implode( ' ', $classes ),
	)
);

$anchor_title = '';
if ( $strike_broken && 'broken' === $status && $href === $source_url ) {
	$anchor_title = __( 'This link appears to be broken.', 'wp-usefull-blocks' );
}

$status_html = '';
if ( $show_status && class_exists( 'WP_Usefull_Blocks_Status_Render' ) ) {
	$status_html = WP_Usefull_Blocks_Status_Render::indicator( $status );
}
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by helper. ?>>
	<?php
	if ( $status_before && '' !== $status_html ) {
		echo $status_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	?>
	<a
		class="ub-file__anchor"
		href="<?php echo esc_url( $href ); ?>"
		<?php if ( '' !== $anchor_title ) : ?>
			title="<?php echo esc_attr( $anchor_title ); ?>"
		<?php endif; ?>
	>
		<?php echo esc_html( $label ); ?>
	</a>
	<?php
	if ( ! $status_before && '' !== $status_html ) {
		echo $status_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
