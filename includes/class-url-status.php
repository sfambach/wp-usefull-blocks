<?php
/**
 * Lightweight URL status checks for UB Link / UB File blocks.
 *
 * Status is resolved on page render (cached) and refreshed in the background via WP-Cron.
 *
 * @package WpUsefullBlocks
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checks remote URL availability.
 */
final class WP_Usefull_Blocks_Url_Status {

	public const STATUS_OK      = 'ok';
	public const STATUS_BROKEN  = 'broken';
	public const STATUS_UNKNOWN = 'unknown';

	private const CACHE_TTL       = 12 * HOUR_IN_SECONDS;
	private const WATCHLIST_OPTION = 'wp_usefull_blocks_url_watchlist';
	private const CRON_HOOK        = 'wp_usefull_blocks_recheck_urls';
	private const BATCH_SIZE       = 10;

	/**
	 * Register cron hooks.
	 */
	public static function init(): void {
		add_action( self::CRON_HOOK, array( self::class, 'cron_recheck' ) );
		add_action( 'init', array( self::class, 'ensure_cron_scheduled' ) );
	}

	/**
	 * Schedule the background recheck if missing.
	 */
	public static function ensure_cron_scheduled(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', self::CRON_HOOK );
		}
	}

	/**
	 * Transient key for a URL.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	private static function cache_key( string $url ): string {
		return 'ub_url_status_' . md5( $url );
	}

	/**
	 * Remember a URL for background rechecks.
	 *
	 * @param string $url URL.
	 */
	public static function watch( string $url ): void {
		$url = esc_url_raw( $url );
		if ( '' === $url ) {
			return;
		}

		$list = get_option( self::WATCHLIST_OPTION, array() );
		if ( ! is_array( $list ) ) {
			$list = array();
		}

		if ( in_array( $url, $list, true ) ) {
			return;
		}

		$list[] = $url;
		// Cap growth.
		if ( count( $list ) > 200 ) {
			$list = array_slice( $list, -200 );
		}

		update_option( self::WATCHLIST_OPTION, $list, false );
	}

	/**
	 * Store a status result for a URL.
	 *
	 * @param string               $url    URL.
	 * @param array<string, mixed> $result Result payload.
	 */
	public static function store( string $url, array $result ): void {
		$url = esc_url_raw( $url );
		if ( '' === $url ) {
			return;
		}

		$result['checkedAt'] = time();
		/**
		 * Filters the URL status cache TTL.
		 *
		 * @param int    $ttl Cache TTL in seconds.
		 * @param string $url URL.
		 */
		$ttl = (int) apply_filters( 'wp_usefull_blocks_url_status_ttl', self::CACHE_TTL, $url );
		set_transient( self::cache_key( $url ), $result, max( 60, $ttl ) );
		self::watch( $url );
	}

	/**
	 * Get cached status only.
	 *
	 * @param string $url URL.
	 * @return array{status:string,code:int,message:string,checkedAt?:int}|null
	 */
	public static function get_cached( string $url ): ?array {
		$url = esc_url_raw( $url );
		if ( '' === $url ) {
			return null;
		}

		$cached = get_transient( self::cache_key( $url ) );
		if ( ! is_array( $cached ) || empty( $cached['status'] ) ) {
			return null;
		}

		return $cached;
	}

	/**
	 * Resolve status for front-end render: use cache, otherwise check now (page load).
	 *
	 * @param string $url              URL to resolve.
	 * @param string $stored_fallback  Fallback status from block attributes.
	 * @return array{status:string,code:int,message:string,checkedAt?:int}
	 */
	public static function resolve_for_render( string $url, string $stored_fallback = 'unknown' ): array {
		$url = esc_url_raw( $url );

		if ( '' === $url ) {
			return array(
				'status'  => self::STATUS_BROKEN,
				'code'    => 0,
				'message' => __( 'Empty URL.', 'wp-usefull-blocks' ),
			);
		}

		$cached = self::get_cached( $url );
		if ( null !== $cached ) {
			self::watch( $url );
			return $cached;
		}

		// No cache yet: check on page load, then cache for subsequent views / static HTML rebuilds.
		$result = self::check( $url );
		self::store( $url, $result );

		if ( empty( $result['status'] ) && '' !== $stored_fallback ) {
			$result['status'] = sanitize_key( $stored_fallback );
		}

		return $result;
	}

	/**
	 * Check a URL and return a normalised status payload.
	 *
	 * @param string $url URL to check.
	 * @return array{status:string,code:int,message:string}
	 */
	public static function check( string $url ): array {
		$url = esc_url_raw( $url );

		if ( '' === $url ) {
			return array(
				'status'  => self::STATUS_BROKEN,
				'code'    => 0,
				'message' => __( 'Empty URL.', 'wp-usefull-blocks' ),
			);
		}

		$response = wp_remote_head(
			$url,
			array(
				'timeout'     => 6,
				'redirection' => 3,
				'user-agent'  => 'WP-Usefull-Blocks-LinkCheck/' . WP_USEFULL_BLOCKS_VERSION,
			)
		);

		if ( is_wp_error( $response ) ) {
			// Some hosts reject HEAD — fall back to a ranged GET.
			$response = wp_remote_get(
				$url,
				array(
					'timeout'     => 6,
					'redirection' => 3,
					'headers'     => array( 'Range' => 'bytes=0-0' ),
					'user-agent'  => 'WP-Usefull-Blocks-LinkCheck/' . WP_USEFULL_BLOCKS_VERSION,
				)
			);
		}

		if ( is_wp_error( $response ) ) {
			return array(
				'status'  => self::STATUS_BROKEN,
				'code'    => 0,
				'message' => $response->get_error_message(),
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( $code >= 200 && $code < 400 ) {
			$status = self::STATUS_OK;
		} elseif ( in_array( $code, array( 401, 403, 429 ), true ) ) {
			$status = self::STATUS_UNKNOWN;
		} else {
			$status = self::STATUS_BROKEN;
		}

		return array(
			'status'  => $status,
			'code'    => $code,
			'message' => (string) wp_remote_retrieve_response_message( $response ),
		);
	}

	/**
	 * Background cron: recheck a batch of watched URLs.
	 */
	public static function cron_recheck(): void {
		$list = get_option( self::WATCHLIST_OPTION, array() );
		if ( ! is_array( $list ) || array() === $list ) {
			return;
		}

		$batch = array_slice( $list, 0, self::BATCH_SIZE );
		$rest  = array_slice( $list, self::BATCH_SIZE );

		foreach ( $batch as $url ) {
			if ( ! is_string( $url ) || '' === $url ) {
				continue;
			}
			$result = self::check( $url );
			self::store( $url, $result );
		}

		// Rotate processed URLs to the end so all get rechecked over time.
		$rotated = array_merge( $rest, $batch );
		update_option( self::WATCHLIST_OPTION, $rotated, false );
	}
}

WP_Usefull_Blocks_Url_Status::init();
