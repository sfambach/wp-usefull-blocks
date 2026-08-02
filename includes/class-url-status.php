<?php
/**
 * Lightweight URL status checks for UB Link / UB File blocks.
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
				'timeout'     => 8,
				'redirection' => 3,
				'user-agent'  => 'WP-Usefull-Blocks-LinkCheck/' . WP_USEFULL_BLOCKS_VERSION,
			)
		);

		if ( is_wp_error( $response ) ) {
			// Some hosts reject HEAD — fall back to a ranged GET.
			$response = wp_remote_get(
				$url,
				array(
					'timeout'     => 8,
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
}
