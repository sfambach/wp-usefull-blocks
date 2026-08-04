<?php
/**
 * Top-level admin menu: Useful.
 *
 * Work pages first; Settings always last.
 *
 * @package WpUsefullBlocks
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the Useful admin menu.
 */
final class WP_Usefull_Blocks_Admin_Menu {

	public const MENU_SLUG = 'useful';

	/**
	 * Hook registration.
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( self::class, 'register' ) );
	}

	/**
	 * Register top-level Useful + submenus.
	 */
	public static function register(): void {
		add_menu_page(
			__( 'Useful', 'wp-usefull-blocks' ),
			__( 'Useful', 'wp-usefull-blocks' ),
			'manage_options',
			self::MENU_SLUG,
			array( 'WP_Usefull_Blocks_Broken_Links_Page', 'render' ),
			'dashicons-admin-tools',
			58
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Broken Links', 'wp-usefull-blocks' ),
			__( 'Broken Links', 'wp-usefull-blocks' ),
			'manage_options',
			self::MENU_SLUG,
			array( 'WP_Usefull_Blocks_Broken_Links_Page', 'render' )
		);

		// Settings always last.
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Useful Settings', 'wp-usefull-blocks' ),
			__( 'Settings', 'wp-usefull-blocks' ),
			'manage_options',
			'useful-settings',
			array( 'WP_Usefull_Blocks_Settings', 'render_page' )
		);
	}
}

WP_Usefull_Blocks_Admin_Menu::init();
