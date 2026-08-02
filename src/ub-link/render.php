<?php
/**
 * Server-side render for the UB Link block.
 *
 * Outputs a normal WordPress-style link plus optional traffic-light status.
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
$label_raw       = isset( $attributes['label'] ) ? (string) $attributes['label'] : '';
$label           = wp_strip_all_tags( $label_raw );
$open_in_new_tab = ! empty( $attributes['openInNewTab'] );
$stored_status   = isset( $attributes['lastStatus'] ) ? sanitize_key( (string) $attributes['lastStatus'] ) : 'unknown';

$show_status = class_exists( 'WP_Usefull_Blocks_Settings' )
	? WP_Usefull_Blocks_Settings::is_enabled( 'show_link_status' )
	: true;
$strike_broken = class_exists( 'WP_Usefull_Blocks_Settings' )
	? WP_Usefull_Blocks_Settings::is_enabled( 'strike_broken_links' )
	: true;
$auto_check = class_exists( 'WP_Usefull_Blocks_Settings' )
	? WP_Usefull_Blocks_Settings::is_enabled( 'auto_check_urls' )
	: true;

if ( '' === $url ) {
	return;
}

if ( '' === trim( $label ) ) {
	$label = $url;
}

$status = $stored_status;
if ( class_exists( 'WP_Usefull_Blocks_Url_Status' ) ) {
	if ( $auto_check ) {
		$status_payload = WP_Usefull_Blocks_Url_Status::resolve_for_render( $url, $stored_status );
	} else {
		$cached         = WP_Usefull_Blocks_Url_Status::get_cached( $url );
		$status_payload = null !== $cached ? $cached : array( 'status' => $stored_status );
	}
	$status = isset( $status_payload['status'] ) ? sanitize_key( (string) $status_payload['status'] ) : 'unknown';
}

$classes = array( 'ub-link', 'ub-link--' . $status );
if ( $strike_broken && 'broken' === $status ) {
	$classes[] = 'is-broken';
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => implode( ' ', $classes ),
	)
);

$rel          = $open_in_new_tab ? 'noopener noreferrer' : '';
$anchor_title = '';
if ( $strike_broken && 'broken' === $status ) {
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
