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

	/**
	 * Admin list status summary used for AJAX in-place updates.
	 *
	 * Layout: ampel + label / reason / checked datetime.
	 *
	 * @param string $status     ok|broken|unknown.
	 * @param int    $code       HTTP code.
	 * @param string $message    Optional detail message.
	 * @param int    $checked_at Unix timestamp of last check (0 = omit).
	 * @return string HTML
	 */
	public static function admin_cell( string $status, int $code = 0, string $message = '', int $checked_at = 0 ): string {
		$status = sanitize_key( $status );
		if ( ! in_array( $status, array( 'ok', 'broken', 'unknown' ), true ) ) {
			$status = 'unknown';
		}

		$labels = array(
			'ok'      => __( 'OK', 'wp-usefull-blocks' ),
			'broken'  => __( 'Broken', 'wp-usefull-blocks' ),
			'unknown' => __( 'Unknown', 'wp-usefull-blocks' ),
		);

		$reason = $message;
		if ( $code > 0 ) {
			$reason = ( '' !== $reason )
				? sprintf( '%s (%d)', $reason, $code )
				: (string) $code;
		}

		$out  = '<div class="ub-status-line">';
		$out .= self::indicator( $status );
		$out .= ' <strong class="ub-status-label">' . esc_html( $labels[ $status ] ) . '</strong>';
		$out .= '</div>';

		if ( '' !== $reason ) {
			$out .= '<div class="ub-status-reason description">' . esc_html( $reason ) . '</div>';
		}

		if ( $checked_at > 0 ) {
			$out .= '<div class="ub-status-checked description">' . esc_html(
				wp_date(
					get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
					$checked_at
				)
			) . '</div>';
		}

		return $out;
	}
}
