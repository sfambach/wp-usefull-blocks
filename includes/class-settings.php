<?php
/**
 * Plugin settings (Settings → Usefull Blocks).
 *
 * Only a settings page for now — registered under the core Settings menu.
 *
 * @package WpUsefullBlocks
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Global plugin options for link status behaviour.
 */
final class WP_Usefull_Blocks_Settings {

	public const OPTION = 'wp_usefull_blocks_settings';

	/**
	 * Hook registration.
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( self::class, 'register_menu' ) );
		add_action( 'admin_init', array( self::class, 'register_settings' ) );
		add_filter( 'block_editor_settings_all', array( self::class, 'inject_editor_settings' ) );
	}

	/**
	 * Default option values.
	 *
	 * @return array{show_link_status:bool,strike_broken_links:bool,auto_check_urls:bool}
	 */
	public static function defaults(): array {
		return array(
			'show_link_status'    => true,
			'strike_broken_links' => true,
			'auto_check_urls'     => true,
		);
	}

	/**
	 * Get merged settings.
	 *
	 * @return array{show_link_status:bool,strike_broken_links:bool,auto_check_urls:bool}
	 */
	public static function get(): array {
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$merged = array_merge( self::defaults(), $stored );

		return array(
			'show_link_status'    => (bool) $merged['show_link_status'],
			'strike_broken_links' => (bool) $merged['strike_broken_links'],
			'auto_check_urls'     => (bool) $merged['auto_check_urls'],
		);
	}

	/**
	 * Convenience boolean getter.
	 *
	 * @param string $key Setting key.
	 * @return bool
	 */
	public static function is_enabled( string $key ): bool {
		$settings = self::get();
		return ! empty( $settings[ $key ] );
	}

	/**
	 * Settings → Usefull Blocks (no top-level menu — settings only).
	 */
	public static function register_menu(): void {
		add_options_page(
			__( 'Usefull Blocks', 'wp-usefull-blocks' ),
			__( 'Usefull Blocks', 'wp-usefull-blocks' ),
			'manage_options',
			'wp-usefull-blocks',
			array( self::class, 'render_page' )
		);
	}

	/**
	 * Register Settings API fields.
	 */
	public static function register_settings(): void {
		register_setting(
			'wp_usefull_blocks_settings_group',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( self::class, 'sanitize' ),
				'default'           => self::defaults(),
				'show_in_rest'      => false,
			)
		);

		add_settings_section(
			'wp_usefull_blocks_links',
			__( 'Links', 'wp-usefull-blocks' ),
			static function (): void {
				echo '<p>' . esc_html__(
					'UB Link behaves like a normal WordPress link. These options add a traffic-light status and strike-through for broken URLs.',
					'wp-usefull-blocks'
				) . '</p>';
			},
			'wp-usefull-blocks'
		);

		add_settings_field(
			'show_link_status',
			__( 'Show traffic-light status', 'wp-usefull-blocks' ),
			array( self::class, 'render_checkbox' ),
			'wp-usefull-blocks',
			'wp_usefull_blocks_links',
			array(
				'key'         => 'show_link_status',
				'description' => __(
					'Show the green / yellow / red status indicator next to UB Link (and UB File) in the editor and on the front end.',
					'wp-usefull-blocks'
				),
			)
		);

		add_settings_field(
			'strike_broken_links',
			__( 'Strike through broken links', 'wp-usefull-blocks' ),
			array( self::class, 'render_checkbox' ),
			'wp-usefull-blocks',
			'wp_usefull_blocks_links',
			array(
				'key'         => 'strike_broken_links',
				'description' => __(
					'On the front end, render broken UB Link / UB File URLs with a strike-through so visitors see they are unavailable.',
					'wp-usefull-blocks'
				),
			)
		);

		add_settings_field(
			'auto_check_urls',
			__( 'Auto-check URLs', 'wp-usefull-blocks' ),
			array( self::class, 'render_checkbox' ),
			'wp-usefull-blocks',
			'wp_usefull_blocks_links',
			array(
				'key'         => 'auto_check_urls',
				'description' => __(
					'Check URLs when a page is rendered (cached) and refresh them in the background via WP-Cron. Also checks automatically in the editor when a URL is set.',
					'wp-usefull-blocks'
				),
			)
		);
	}

	/**
	 * Sanitize option array.
	 *
	 * @param mixed $input Raw input.
	 * @return array{show_link_status:bool,strike_broken_links:bool,auto_check_urls:bool}
	 */
	public static function sanitize( $input ): array {
		$defaults = self::defaults();
		if ( ! is_array( $input ) ) {
			return $defaults;
		}

		return array(
			'show_link_status'    => ! empty( $input['show_link_status'] ),
			'strike_broken_links' => ! empty( $input['strike_broken_links'] ),
			'auto_check_urls'     => ! empty( $input['auto_check_urls'] ),
		);
	}

	/**
	 * Checkbox field renderer.
	 *
	 * @param array{key:string,description:string} $args Field args.
	 */
	public static function render_checkbox( array $args ): void {
		$key         = isset( $args['key'] ) ? (string) $args['key'] : '';
		$description = isset( $args['description'] ) ? (string) $args['description'] : '';
		$settings    = self::get();
		$checked     = ! empty( $settings[ $key ] );
		$name        = self::OPTION . '[' . $key . ']';
		$id          = 'wp_usefull_blocks_' . $key;
		?>
		<label for="<?php echo esc_attr( $id ); ?>">
			<input
				type="checkbox"
				id="<?php echo esc_attr( $id ); ?>"
				name="<?php echo esc_attr( $name ); ?>"
				value="1"
				<?php checked( $checked ); ?>
			/>
			<?php echo esc_html__( 'Enabled', 'wp-usefull-blocks' ); ?>
		</label>
		<?php if ( '' !== $description ) : ?>
			<p class="description"><?php echo esc_html( $description ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render the settings page.
	 */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'wp_usefull_blocks_settings_group' );
				do_settings_sections( 'wp-usefull-blocks' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Expose settings to the block editor.
	 *
	 * @param array<string, mixed> $editor_settings Block editor settings.
	 * @return array<string, mixed>
	 */
	public static function inject_editor_settings( array $editor_settings ): array {
		$editor_settings['wpUsefullBlocks'] = self::get();
		return $editor_settings;
	}
}

WP_Usefull_Blocks_Settings::init();
