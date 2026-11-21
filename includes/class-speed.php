<?php
/**
 * Front-end speed: load only what a page needs (Useful → Settings → Speed).
 *
 * Every switch is on by default and can be turned off on its own. Nothing here
 * changes content; it only decides which stylesheets and scripts a page loads
 * and how.
 *
 * @package WpUsefullBlocks
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trims the asset list of front-end pages.
 */
final class WP_Usefull_Blocks_Speed {

	/** Small local stylesheets are written into the page instead of costing a request each. */
	private const INLINE_MAX_BYTES = 4096;

	/** Handles of other plugins and the theme, as they register them. */
	private const PRISM_STYLE   = 'mkaz-code-syntax-prism-css';
	private const PRISM_SCRIPT  = 'mkaz-code-syntax-prism-js';
	private const COMMENT_STYLE = array( 'stcr-font-awesome', 'stcr-style' );
	private const TABLE_STYLE   = 'tablepress-default';
	private const THEME_FONT    = 'penguin-font';

	/** Scripts that inline code on the page may call at once; they stay blocking. */
	private const KEEP_BLOCKING = array( 'jquery', 'jquery-core', 'jquery-migrate' );

	/**
	 * Hook registration.
	 */
	public static function init(): void {
		add_action( 'init', array( self::class, 'maybe_remove_emoji' ) );
		add_action( 'wp_default_scripts', array( self::class, 'drop_jquery_migrate' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'trim_assets' ), 999 );
		// Before wp_head runs wp_maybe_inline_styles(), which does the inlining.
		add_action( 'wp_enqueue_scripts', array( self::class, 'inline_small_styles' ), 1000 );
		// wp_enqueue_scripts itself runs inside wp_head at priority 1; the preload follows it.
		add_action( 'wp_head', array( self::class, 'print_font_preload' ), 2 );
		add_filter( 'render_block_core/gallery', array( self::class, 'mark_gallery_images' ), 10, 2 );
		// Runs after WordPress added srcset and sizes to each content image.
		add_filter( 'wp_content_img_tag', array( self::class, 'gallery_image_sizes' ) );
	}

	/** Set once the local font replaced the theme's Google font on this page. */
	private static bool $local_font = false;

	/**
	 * Whether a speed switch is on.
	 *
	 * @param string $key Setting key.
	 */
	private static function on( string $key ): bool {
		return class_exists( 'WP_Usefull_Blocks_Settings' ) && WP_Usefull_Blocks_Settings::is_enabled( $key );
	}

	/**
	 * Emoji: every browser in use draws emoji itself; the detection script and its styles are left out.
	 */
	public static function maybe_remove_emoji(): void {
		if ( is_admin() || ! self::on( 'speed_no_emoji' ) ) {
			return;
		}

		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'emoji_svg_url', '__return_false' );
	}

	/**
	 * jQuery Migrate only bridges code written for jQuery before 3.0; nothing on this site needs it
	 * (tested 2026-11-21: no Migrate warning on any page type).
	 *
	 * @param WP_Scripts $scripts Script registry.
	 */
	public static function drop_jquery_migrate( WP_Scripts $scripts ): void {
		if ( is_admin() || ! self::on( 'speed_no_jquery_migrate' ) || ! isset( $scripts->registered['jquery'] ) ) {
			return;
		}

		$scripts->registered['jquery']->deps = array_values( array_diff( $scripts->registered['jquery']->deps, array( 'jquery-migrate' ) ) );
	}

	/**
	 * After every plugin and the theme enqueued theirs: drop what this page does not use, defer what can wait.
	 */
	public static function trim_assets(): void {
		if ( is_admin() ) {
			return;
		}

		if ( self::on( 'speed_conditional_assets' ) ) {
			self::drop_unused();
		}

		if ( self::on( 'speed_local_fonts' ) ) {
			self::use_local_font();
		}

		if ( self::on( 'speed_defer_scripts' ) ) {
			self::defer_scripts();
		}
	}

	/**
	 * Syntax highlighting only where code is shown, comment-subscription styles only where comments are open,
	 * TablePress styles only where a table is.
	 */
	private static function drop_unused(): void {
		$content = self::shown_content();

		if ( ! self::contains( $content, array( '<code', '<pre', 'wp:code', 'wp:preformatted' ) ) ) {
			wp_dequeue_style( self::PRISM_STYLE );
			wp_dequeue_script( self::PRISM_SCRIPT );
		}

		if ( ! ( is_singular() && comments_open() ) ) {
			foreach ( self::COMMENT_STYLE as $handle ) {
				wp_dequeue_style( $handle );
			}
		}

		if ( ! self::contains( $content, array( '[table ', 'wp:tablepress/' ) ) ) {
			wp_dequeue_style( self::TABLE_STYLE );
		}
	}

	/**
	 * The raw content of every post the main query shows on this page.
	 */
	private static function shown_content(): string {
		global $wp_query;

		$posts = ( $wp_query instanceof WP_Query && is_array( $wp_query->posts ) ) ? $wp_query->posts : array();
		$parts = array();

		foreach ( $posts as $post ) {
			if ( $post instanceof WP_Post ) {
				$parts[] = $post->post_content;
			}
		}

		return implode( "\n", $parts );
	}

	/**
	 * @param string   $haystack Content.
	 * @param string[] $needles  Markers, any of which counts.
	 */
	private static function contains( string $haystack, array $needles ): bool {
		foreach ( $needles as $needle ) {
			if ( false !== stripos( $haystack, $needle ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * The theme's Google font from this site: no request to Google, no wait for a second server, and the
	 * text shows at once in a fallback font while Raleway loads.
	 */
	private static function use_local_font(): void {
		if ( ! wp_style_is( self::THEME_FONT, 'enqueued' ) ) {
			return;
		}

		wp_dequeue_style( self::THEME_FONT );

		$base = WP_USEFULL_BLOCKS_URL . 'assets/fonts/';
		$css  = '';

		// One variable file covers both weights the theme asks for (300 and 600).
		foreach ( array(
			'raleway-latin-ext.woff2' => 'U+0100-02BA,U+02BD-02C5,U+02C7-02CC,U+02CE-02D7,U+02DD-02FF,U+0304,U+0308,U+0329,U+1D00-1DBF,U+1E00-1E9F,U+1EF2-1EFF,U+2020,U+20A0-20AB,U+20AD-20C4,U+2113,U+2C60-2C7F,U+A720-A7FF',
			'raleway-latin.woff2'     => 'U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD',
		) as $file => $range ) {
			$css .= sprintf(
				"@font-face{font-family:'Raleway';font-style:normal;font-weight:300 600;font-display:swap;src:url(%s) format('woff2');unicode-range:%s}",
				esc_url( $base . $file . '?ver=' . WP_USEFULL_BLOCKS_VERSION ),
				$range
			);
		}

		wp_register_style( 'wp-usefull-blocks-font', false, array(), WP_USEFULL_BLOCKS_VERSION );
		wp_enqueue_style( 'wp-usefull-blocks-font' );
		wp_add_inline_style( 'wp-usefull-blocks-font', $css );

		self::$local_font = true;
	}

	/**
	 * The Latin file is needed on every page that uses the local font; the browser fetches it before it reads the CSS.
	 */
	public static function print_font_preload(): void {
		if ( ! self::$local_font ) {
			return;
		}

		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( WP_USEFULL_BLOCKS_URL . 'assets/fonts/raleway-latin.woff2?ver=' . WP_USEFULL_BLOCKS_VERSION )
		);
	}

	/**
	 * Every head script but jQuery waits until the page is parsed. WordPress itself keeps a script blocking when
	 * inline code it knows of or a blocking script that needs it would break. Footer scripts stay as they are:
	 * they no longer hold up the first paint, and themes call them from raw inline code WordPress cannot see.
	 */
	private static function defer_scripts(): void {
		$scripts = wp_scripts();

		foreach ( $scripts->queue as $handle ) {
			if ( in_array( $handle, self::KEEP_BLOCKING, true ) || ! isset( $scripts->registered[ $handle ] ) ) {
				continue;
			}

			if ( ! empty( $scripts->registered[ $handle ]->extra['group'] ) ) {
				continue;
			}

			if ( empty( $scripts->registered[ $handle ]->extra['strategy'] ) ) {
				wp_script_add_data( $handle, 'strategy', 'defer' );
			}
		}
	}

	/** Carries the column count from the gallery block to the image tag, where `sizes` is known. */
	private const COLUMNS_ATTRIBUTE = 'data-ub-gallery-columns';

	/**
	 * A gallery image is one column wide, but WordPress announces it as wide as the content, so the browser
	 * fetches a file two or three times too large. The gallery marks its images with its column count here;
	 * gallery_image_sizes() divides their `sizes` once WordPress has written it.
	 *
	 * @param string               $html  Rendered gallery.
	 * @param array<string, mixed> $block Parsed block.
	 */
	public static function mark_gallery_images( string $html, array $block ): string {
		if ( is_admin() || ! self::on( 'speed_gallery_sizes' ) || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			return $html;
		}

		$images  = is_array( $block['innerBlocks'] ?? null ) ? count( $block['innerBlocks'] ) : 0;
		$columns = (int) ( $block['attrs']['columns'] ?? min( max( $images, 1 ), 3 ) );

		if ( $columns < 2 ) {
			return $html;
		}

		$tags = new WP_HTML_Tag_Processor( $html );

		while ( $tags->next_tag( 'img' ) ) {
			$tags->set_attribute( self::COLUMNS_ATTRIBUTE, (string) $columns );
		}

		return $tags->get_updated_html();
	}

	/**
	 * Each width in `sizes` of a marked image is divided by the columns that apply at that width: the gallery
	 * shows two columns below 600 px and its own count above.
	 *
	 * @param string $image One content image tag.
	 */
	public static function gallery_image_sizes( string $image ): string {
		if ( ! str_contains( $image, self::COLUMNS_ATTRIBUTE ) ) {
			return $image;
		}

		$tags = new WP_HTML_Tag_Processor( $image );

		if ( ! $tags->next_tag( 'img' ) ) {
			return $image;
		}

		$columns = (int) $tags->get_attribute( self::COLUMNS_ATTRIBUTE );
		$sizes   = $tags->get_attribute( 'sizes' );
		$tags->remove_attribute( self::COLUMNS_ATTRIBUTE );

		if ( $columns >= 2 && is_string( $sizes ) && '' !== $sizes ) {
			$tags->set_attribute( 'sizes', self::divide_sizes( $sizes, $columns ) );
		}

		return $tags->get_updated_html();
	}

	/**
	 * @param string $sizes   A `sizes` attribute: comma-separated "(media) width" entries, the last without media.
	 * @param int    $columns Gallery columns from 600 px up.
	 */
	private static function divide_sizes( string $sizes, int $columns ): string {
		$entries = array();

		// Commas inside calc() never occur in sizes widths, so a plain split is safe.
		foreach ( array_map( 'trim', explode( ',', $sizes ) ) as $entry ) {
			if ( 'auto' === $entry || '' === $entry ) {
				$entries[] = $entry;
				continue;
			}

			$media = '';
			$width = $entry;

			if ( preg_match( '/^(\(.*\))\s+(.+)$/', $entry, $parts ) ) {
				$media = $parts[1] . ' ';
				$width = $parts[2];
			}

			$small   = preg_match( '/max-width:\s*(\d+)px/', $media, $max ) && (int) $max[1] < 600;
			$divisor = $small ? 2 : $columns;

			$entries[] = $media . sprintf( 'calc(%s / %d)', $width, $divisor );
		}

		return implode( ', ', $entries );
	}

	/**
	 * Small stylesheets from this site go into the page itself (WordPress inlines a style that names its file
	 * as `path`, within its own total limit).
	 */
	public static function inline_small_styles(): void {
		if ( is_admin() || ! self::on( 'speed_inline_small_css' ) ) {
			return;
		}

		$styles = wp_styles();
		$site   = trailingslashit( content_url() );

		foreach ( $styles->queue as $handle ) {
			$style = $styles->registered[ $handle ] ?? null;

			if ( ! $style || ! is_string( $style->src ) || ! empty( $style->extra['path'] ) || ! str_starts_with( $style->src, $site ) ) {
				continue;
			}

			$file = WP_CONTENT_DIR . '/' . ltrim( (string) wp_parse_url( substr( $style->src, strlen( $site ) ), PHP_URL_PATH ), '/' );

			if ( is_readable( $file ) && filesize( $file ) <= self::INLINE_MAX_BYTES ) {
				wp_style_add_data( $handle, 'path', $file );
			}
		}
	}
}

WP_Usefull_Blocks_Speed::init();
