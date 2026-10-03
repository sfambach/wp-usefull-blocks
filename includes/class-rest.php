<?php
/**
 * REST routes for UB Link / UB File editor actions.
 *
 * @package WpUsefullBlocks
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers plugin REST routes.
 */
final class WP_Usefull_Blocks_Rest {

	/**
	 * Hook registration.
	 */
	public static function init(): void {
		add_action( 'rest_api_init', array( self::class, 'register_routes' ) );
	}

	/**
	 * Register routes.
	 */
	public static function register_routes(): void {
		register_rest_route(
			'wp-usefull-blocks/v1',
			'/check-url',
			array(
				'methods'             => 'POST',
				'callback'            => array( self::class, 'check_url' ),
				'permission_callback' => static function () {
					return current_user_can( 'edit_posts' );
				},
				'args'                => array(
					'url' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'esc_url_raw',
					),
				),
			)
		);

		register_rest_route(
			'wp-usefull-blocks/v1',
			'/file-mirror',
			array(
				'methods'             => 'POST',
				'callback'            => array( self::class, 'file_mirror' ),
				'permission_callback' => static function () {
					return current_user_can( 'upload_files' );
				},
				'args'                => array(
					'url' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'esc_url_raw',
					),
				),
			)
		);
	}

	/**
	 * POST /check-url
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function check_url( WP_REST_Request $request ): WP_REST_Response {
		$url    = (string) $request->get_param( 'url' );
		$result = WP_Usefull_Blocks_Url_Status::check( $url );
		WP_Usefull_Blocks_Url_Status::store( $url, $result );

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * POST /file-mirror
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function file_mirror( WP_REST_Request $request ) {
		$url    = (string) $request->get_param( 'url' );
		$result = WP_Usefull_Blocks_File_Mirror::mirror( $url );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $result, 200 );
	}
}

WP_Usefull_Blocks_Rest::init();
