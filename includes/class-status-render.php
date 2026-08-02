<?php
/**
 * Shared traffic-light status markup.
 *
 * @package WpUsefullBlocks
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders accessible link-status indicators.
 */
final class WP_Usefull_Blocks_Status_Render {

	/**
	 * Render a traffic-light status span.
	 *
	 * @param string $status ok|broken|unknown.
	 * @return string HTML
	 */
	public static function indicator( string $status ): string {
		$status = sanitize_key( $status );

		if ( ! in_array( $status, array( 'ok', 'broken', 'unknown' ), true ) ) {
			$status = 'unknown';
		}

		$labels = array(
			'ok'      => __( 'Link status: OK', 'wp-usefull-blocks' ),
			'broken'  => __( 'Link status: Broken', 'wp-usefull-blocks' ),
			'unknown' => __( 'Link status: Unknown', 'wp-usefull-blocks' ),
		);

		$icons = array(
			'ok'      => '●',
			'broken'  => '●',
			'unknown' => '●',
		);

		return sprintf(
			'<span class="ub-status ub-status--%1$s" title="%2$s"><span class="ub-status__icon" aria-hidden="true">%3$s</span><span class="screen-reader-text">%2$s</span></span>',
			esc_attr( $status ),
			esc_attr( $labels[ $status ] ),
			esc_html( $icons[ $status ] )
		);
	}
}
