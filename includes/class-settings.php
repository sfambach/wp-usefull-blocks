<?php
/**
 * Plugin settings (Useful → Settings).
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

	public const OPTION    = 'wp_usefull_blocks_settings';
	public const PAGE_SLUG = 'useful-settings';

	/**
	 * Hook registration.
	 */
	public static function init(): void {
		add_action( 'admin_init', array( self::class, 'register_settings' ) );
		add_filter( 'block_editor_settings_all', array( self::class, 'inject_editor_settings' ) );
	}

	/**
	 * Default option values.
	 *
	 * @return array{show_link_status:bool,link_status_position:string,strike_broken_links:bool,auto_check_urls:bool,link_check_exclude:string[],related_posts_images:bool,speed_conditional_assets:bool,speed_local_fonts:bool,speed_no_emoji:bool,speed_defer_scripts:bool,speed_inline_small_css:bool,speed_gallery_sizes:bool,speed_admin_page_time:bool}
	 */
	public static function defaults(): array {
		return array(
			'show_link_status'     => true,
			'link_status_position' => 'before',
			'strike_broken_links'  => true,
			'auto_check_urls'      => true,
			'link_check_exclude'   => array(),
			'related_posts_images' => false,
			'speed_conditional_assets' => true,
			'speed_local_fonts' => true,
			'speed_no_emoji' => true,
			'speed_defer_scripts' => true,
			'speed_inline_small_css' => true,
			'speed_gallery_sizes'    => true,
			'speed_no_jquery_migrate' => true,
			'speed_webp_uploads'     => true,
			'speed_admin_page_time'  => true,
		);
	}

	/**
	 * Get merged settings.
	 *
	 * @return array{show_link_status:bool,link_status_position:string,strike_broken_links:bool,auto_check_urls:bool,link_check_exclude:string[],related_posts_images:bool,speed_conditional_assets:bool,speed_local_fonts:bool,speed_no_emoji:bool,speed_defer_scripts:bool,speed_inline_small_css:bool,speed_gallery_sizes:bool,speed_admin_page_time:bool}
	 */
	public static function get(): array {
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$merged   = array_merge( self::defaults(), $stored );
		$position = sanitize_key( (string) $merged['link_status_position'] );
		if ( ! in_array( $position, array( 'before', 'after', 'off' ), true ) ) {
			$position = 'before';
		}

		// Legacy: older "Show traffic-light" checkbox off → position off.
		if (
			array_key_exists( 'show_link_status', $stored )
			&& empty( $stored['show_link_status'] )
			&& 'off' !== $position
		) {
			$position = 'off';
		}

		return array(
			'show_link_status'     => ( 'off' !== $position ),
			'link_status_position' => $position,
			'strike_broken_links'  => (bool) $merged['strike_broken_links'],
			'auto_check_urls'      => (bool) $merged['auto_check_urls'],
			'link_check_exclude'   => self::sanitize_patterns( $merged['link_check_exclude'] ),
			'related_posts_images' => (bool) $merged['related_posts_images'],
			'speed_conditional_assets' => (bool) $merged['speed_conditional_assets'],
			'speed_local_fonts' => (bool) $merged['speed_local_fonts'],
			'speed_no_emoji' => (bool) $merged['speed_no_emoji'],
			'speed_defer_scripts' => (bool) $merged['speed_defer_scripts'],
			'speed_inline_small_css' => (bool) $merged['speed_inline_small_css'],
			'speed_gallery_sizes'    => (bool) $merged['speed_gallery_sizes'],
			'speed_no_jquery_migrate' => (bool) $merged['speed_no_jquery_migrate'],
			'speed_webp_uploads'     => (bool) $merged['speed_webp_uploads'],
			'speed_admin_page_time'  => (bool) $merged['speed_admin_page_time'],
		);
	}

	/**
	 * Normalise the "Do not check" list: one URL or URL part per entry.
	 *
	 * @param mixed $value Array or newline-separated string.
	 * @return string[]
	 */
	private static function sanitize_patterns( $value ): array {
		$lines = is_array( $value ) ? $value : preg_split( '/\R/', (string) $value );
		$lines = array_map( static fn( $line ): string => trim( sanitize_text_field( (string) $line ) ), (array) $lines );

		return array_values( array_unique( array_filter( $lines, 'strlen' ) ) );
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
					'These options apply to every normal WordPress link in post and page content site-wide. When enabled, links show a traffic-light status and optional strike-through for broken URLs.',
					'wp-usefull-blocks'
				) . '</p>';
			},
			self::PAGE_SLUG
		);

		add_settings_field(
			'link_status_position',
			__( 'Traffic-light status', 'wp-usefull-blocks' ),
			array( self::class, 'render_position' ),
			self::PAGE_SLUG,
			'wp_usefull_blocks_links',
			array(
				'description' => __(
					'Show the status indicator before or after every content link, or turn it off. Default: before.',
					'wp-usefull-blocks'
				),
			)
		);

		add_settings_field(
			'strike_broken_links',
			__( 'Strike through broken links', 'wp-usefull-blocks' ),
			array( self::class, 'render_checkbox' ),
			self::PAGE_SLUG,
			'wp_usefull_blocks_links',
			array(
				'key'         => 'strike_broken_links',
				'description' => __(
					'On the front end, render broken content links with a strike-through.',
					'wp-usefull-blocks'
				),
			)
		);

		add_settings_field(
			'auto_check_urls',
			__( 'Auto-check URLs', 'wp-usefull-blocks' ),
			array( self::class, 'render_checkbox' ),
			self::PAGE_SLUG,
			'wp_usefull_blocks_links',
			array(
				'key'         => 'auto_check_urls',
				'description' => __(
					'Check URLs when a page is rendered (cached) and refresh them via WP-Cron.',
					'wp-usefull-blocks'
				),
			)
		);

		add_settings_field(
			'link_check_exclude',
			__( 'Do not check', 'wp-usefull-blocks' ),
			array( self::class, 'render_exclude' ),
			self::PAGE_SLUG,
			'wp_usefull_blocks_links'
		);

		add_settings_section(
			'wp_usefull_blocks_related_posts',
			__( 'Related posts', 'wp-usefull-blocks' ),
			'__return_null',
			self::PAGE_SLUG
		);

		add_settings_field(
			'related_posts_images',
			__( 'Show images', 'wp-usefull-blocks' ),
			array( self::class, 'render_checkbox' ),
			self::PAGE_SLUG,
			'wp_usefull_blocks_related_posts',
			array(
				'key'         => 'related_posts_images',
				'description' => __(
					'Default for the "Show preview image" switch in "UB Related Posts" blocks; a block can override it. [display-posts] lists always follow this setting.',
					'wp-usefull-blocks'
				),
			)
		);

		add_settings_section(
			'wp_usefull_blocks_speed',
			__( 'Speed', 'wp-usefull-blocks' ),
			static function (): void {
				echo '<p>' . esc_html__(
					'Front-end pages only. Each switch can be turned off on its own if something looks wrong; clear the page cache after a change.',
					'wp-usefull-blocks'
				) . '</p>';
			},
			self::PAGE_SLUG
		);

		add_settings_field(
			'speed_conditional_assets',
			__( 'Load only what a page uses', 'wp-usefull-blocks' ),
			array( self::class, 'render_checkbox' ),
			self::PAGE_SLUG,
			'wp_usefull_blocks_speed',
			array(
				'key'         => 'speed_conditional_assets',
				'description' => __(
					'Syntax highlighting only on pages with code, comment-subscription styles only where comments are open, TablePress styles only where a table is.',
					'wp-usefull-blocks'
				),
			)
		);

		add_settings_field(
			'speed_local_fonts',
			__( 'Theme font from this site', 'wp-usefull-blocks' ),
			array( self::class, 'render_checkbox' ),
			self::PAGE_SLUG,
			'wp_usefull_blocks_speed',
			array(
				'key'         => 'speed_local_fonts',
				'description' => __(
					'Serve the theme\'s Raleway font from this site instead of Google Fonts: one server less to wait for, text shows at once, and no visitor data goes to Google.',
					'wp-usefull-blocks'
				),
			)
		);

		add_settings_field(
			'speed_no_emoji',
			__( 'No emoji script', 'wp-usefull-blocks' ),
			array( self::class, 'render_checkbox' ),
			self::PAGE_SLUG,
			'wp_usefull_blocks_speed',
			array(
				'key'         => 'speed_no_emoji',
				'description' => __(
					'Leave out WordPress\' emoji detection script and styles; every current browser draws emoji itself.',
					'wp-usefull-blocks'
				),
			)
		);

		add_settings_field(
			'speed_defer_scripts',
			__( 'Defer scripts', 'wp-usefull-blocks' ),
			array( self::class, 'render_checkbox' ),
			self::PAGE_SLUG,
			'wp_usefull_blocks_speed',
			array(
				'key'         => 'speed_defer_scripts',
				'description' => __(
					'Scripts in the page head, except jQuery, wait until the page is parsed. WordPress keeps a script blocking where inline code needs it at once; footer scripts stay as they are.',
					'wp-usefull-blocks'
				),
			)
		);

		add_settings_field(
			'speed_inline_small_css',
			__( 'Inline small stylesheets', 'wp-usefull-blocks' ),
			array( self::class, 'render_checkbox' ),
			self::PAGE_SLUG,
			'wp_usefull_blocks_speed',
			array(
				'key'         => 'speed_inline_small_css',
				'description' => __(
					'Write stylesheets of this site up to 4 KB into the page instead of loading each as its own file.',
					'wp-usefull-blocks'
				),
			)
		);

		add_settings_field(
			'speed_gallery_sizes',
			__( 'Gallery image sizes', 'wp-usefull-blocks' ),
			array( self::class, 'render_checkbox' ),
			self::PAGE_SLUG,
			'wp_usefull_blocks_speed',
			array(
				'key'         => 'speed_gallery_sizes',
				'description' => __(
					'Images in a gallery tell the browser their column width, so it loads a smaller file instead of one as wide as the whole content.',
					'wp-usefull-blocks'
				),
			)
		);

		add_settings_field(
			'speed_no_jquery_migrate',
			__( 'Without jQuery Migrate', 'wp-usefull-blocks' ),
			array( self::class, 'render_checkbox' ),
			self::PAGE_SLUG,
			'wp_usefull_blocks_speed',
			array(
				'key'         => 'speed_no_jquery_migrate',
				'description' => __(
					'Pages do not load the compatibility layer for old jQuery code. Turn this off if a menu, slider or other effect stops working.',
					'wp-usefull-blocks'
				),
			)
		);

		add_settings_field(
			'speed_webp_uploads',
			__( 'WebP for new uploads', 'wp-usefull-blocks' ),
			array( self::class, 'render_checkbox' ),
			self::PAGE_SLUG,
			'wp_usefull_blocks_speed',
			array(
				'key'         => 'speed_webp_uploads',
				'description' => __(
					'The smaller copies WordPress makes of a newly uploaded JPEG or PNG are saved as WebP, about a third smaller at the same look. The original file and images uploaded before stay as they are.',
					'wp-usefull-blocks'
				),
			)
		);

		add_settings_field(
			'speed_admin_page_time',
			__( 'Page time in the admin footer', 'wp-usefull-blocks' ),
			array( self::class, 'render_checkbox' ),
			self::PAGE_SLUG,
			'wp_usefull_blocks_speed',
			array(
				'key'         => 'speed_admin_page_time',
				'description' => __(
					'Every admin page shows in its footer what it took on the server: seconds, database queries and memory.',
					'wp-usefull-blocks'
				),
			)
		);

		add_settings_section(
			'wp_usefull_blocks_ub_timeline',
			__( 'UB Timeline', 'wp-usefull-blocks' ),
			static function (): void {
				echo '<p>' . esc_html__(
					'No global settings yet. Orientation and expand behaviour are configured per block in the editor sidebar.',
					'wp-usefull-blocks'
				) . '</p>';
			},
			self::PAGE_SLUG
		);

		add_settings_section(
			'wp_usefull_blocks_ub_callout',
			__( 'UB Callout', 'wp-usefull-blocks' ),
			static function (): void {
				echo '<p>' . esc_html__(
					'No global settings yet. Type, title, and message are configured per block.',
					'wp-usefull-blocks'
				) . '</p>';
			},
			self::PAGE_SLUG
		);

		add_settings_section(
			'wp_usefull_blocks_ub_toc',
			__( 'UB Table of Contents', 'wp-usefull-blocks' ),
			static function (): void {
				echo '<p>' . esc_html__(
					'No global settings yet. Title and heading levels are configured per block. Heading ± buttons are always available on core Heading blocks in the editor.',
					'wp-usefull-blocks'
				) . '</p>';
			},
			self::PAGE_SLUG
		);
	}

	/**
	 * Sanitize option array.
	 *
	 * @param mixed $input Raw input.
	 * @return array{show_link_status:bool,link_status_position:string,strike_broken_links:bool,auto_check_urls:bool,link_check_exclude:string[],related_posts_images:bool,speed_conditional_assets:bool,speed_local_fonts:bool,speed_no_emoji:bool,speed_defer_scripts:bool,speed_inline_small_css:bool,speed_gallery_sizes:bool,speed_admin_page_time:bool}
	 */
	public static function sanitize( $input ): array {
		$defaults = self::defaults();
		if ( ! is_array( $input ) ) {
			return $defaults;
		}

		$position = isset( $input['link_status_position'] )
			? sanitize_key( (string) $input['link_status_position'] )
			: 'before';
		if ( ! in_array( $position, array( 'before', 'after', 'off' ), true ) ) {
			$position = 'before';
		}

		return array(
			'show_link_status'     => ( 'off' !== $position ),
			'link_status_position' => $position,
			'strike_broken_links'  => ! empty( $input['strike_broken_links'] ),
			'auto_check_urls'      => ! empty( $input['auto_check_urls'] ),
			'link_check_exclude'   => self::sanitize_patterns( $input['link_check_exclude'] ?? array() ),
			'related_posts_images' => ! empty( $input['related_posts_images'] ),
			'speed_conditional_assets' => ! empty( $input['speed_conditional_assets'] ),
			'speed_local_fonts' => ! empty( $input['speed_local_fonts'] ),
			'speed_no_emoji' => ! empty( $input['speed_no_emoji'] ),
			'speed_defer_scripts' => ! empty( $input['speed_defer_scripts'] ),
			'speed_inline_small_css' => ! empty( $input['speed_inline_small_css'] ),
			'speed_gallery_sizes'    => ! empty( $input['speed_gallery_sizes'] ),
			'speed_no_jquery_migrate' => ! empty( $input['speed_no_jquery_migrate'] ),
			'speed_webp_uploads'     => ! empty( $input['speed_webp_uploads'] ),
			'speed_admin_page_time'  => ! empty( $input['speed_admin_page_time'] ),
		);
	}

	/**
	 * Textarea for the "Do not check" list.
	 */
	public static function render_exclude(): void {
		$settings = self::get();
		$id       = 'wp_usefull_blocks_link_check_exclude';
		?>
		<textarea id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( self::OPTION . '[link_check_exclude]' ); ?>" rows="5" cols="60" class="large-text code"><?php echo esc_textarea( implode( "\n", $settings['link_check_exclude'] ) ); ?></textarea>
		<p class="description"><?php echo esc_html__( 'One URL or URL part per line, e.g. github.com. Matching links are not checked and get no status indicator.', 'wp-usefull-blocks' ); ?></p>
		<?php
	}

	/**
	 * Select field for traffic-light position.
	 *
	 * @param array{description?:string} $args Field args.
	 */
	public static function render_position( array $args ): void {
		$description = isset( $args['description'] ) ? (string) $args['description'] : '';
		$settings    = self::get();
		$current     = $settings['link_status_position'];
		$name        = self::OPTION . '[link_status_position]';
		$id          = 'wp_usefull_blocks_link_status_position';
		$options     = array(
			'before' => __( 'Before the link', 'wp-usefull-blocks' ),
			'after'  => __( 'After the link', 'wp-usefull-blocks' ),
			'off'    => __( 'Off', 'wp-usefull-blocks' ),
		);
		?>
		<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>">
			<?php foreach ( $options as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php if ( '' !== $description ) : ?>
			<p class="description"><?php echo esc_html( $description ); ?></p>
		<?php endif; ?>
		<?php
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
			<h1><?php echo esc_html__( 'Useful Settings', 'wp-usefull-blocks' ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'wp_usefull_blocks_settings_group' );
				do_settings_sections( self::PAGE_SLUG );
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
