<?php
/**
 * What an admin page took on the server, in the footer of the page (Useful → Settings → Speed).
 *
 * The line stands behind WordPress's own footer text: seconds until the footer was reached,
 * database queries, peak memory. It names numbers only, never the page or a parameter.
 *
 * @package WpUsefullBlocks
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds the page time to the admin footer.
 */
final class WP_Usefull_Blocks_Admin_Page_Time {

	/**
	 * Hook registration.
	 */
	public static function init(): void {
		// Last, so the time covers what other plugins put into the footer text too.
		add_filter( 'admin_footer_text', array( self::class, 'footer_text' ), PHP_INT_MAX );
	}

	/**
	 * WordPress's footer text with the line of this page behind it.
	 *
	 * @param mixed $text Footer text; a filter value, so another plugin may have put anything there.
	 */
	public static function footer_text( $text ): string {
		$text = is_string( $text ) ? $text : '';

		if ( ! class_exists( 'WP_Usefull_Blocks_Settings' ) || ! WP_Usefull_Blocks_Settings::is_enabled( 'speed_admin_page_time' ) ) {
			return $text;
		}

		$started = isset( $_SERVER['REQUEST_TIME_FLOAT'] ) ? (float) $_SERVER['REQUEST_TIME_FLOAT'] : microtime( true );

		return trim(
			$text . ' ' . self::line(
				microtime( true ) - $started,
				get_num_queries(),
				(int) round( memory_get_peak_usage( true ) / MB_IN_BYTES )
			)
		);
	}

	/**
	 * The line itself.
	 *
	 * @param float $seconds   Seconds since the request began.
	 * @param int   $queries   Database queries so far.
	 * @param int   $megabytes Peak memory in MB.
	 */
	public static function line( float $seconds, int $queries, int $megabytes ): string {
		return '<span class="wp-usefull-blocks-page-time">' . esc_html(
			sprintf(
				/* translators: 1: seconds, 2: database queries, 3: megabytes */
				__( 'Page in %1$s s · %2$s queries · %3$s MB', 'wp-usefull-blocks' ),
				number_format_i18n( max( 0.0, $seconds ), 2 ),
				number_format_i18n( $queries ),
				number_format_i18n( $megabytes )
			)
		) . '</span>';
	}
}

WP_Usefull_Blocks_Admin_Page_Time::init();
