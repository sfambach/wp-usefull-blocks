<?php
/**
 * Enhance all standard WordPress content links with status UI.
 *
 * When enabled in Useful → Settings, every normal <a href> in post/page
 * content gets the traffic-light indicator and optional strike-through — same
 * look as the UB Link block, site-wide.
 *
 * @package WpUsefullBlocks
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filters front-end content and enqueues shared link styles.
 */
final class WP_Usefull_Blocks_Content_Links {

	private const MAX_LIVE_CHECKS = 5;

	/** @var int */
	private static int $live_checks = 0;

	/**
	 * Hook registration.
	 */
	public static function init(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		add_filter( 'the_content', array( self::class, 'filter_content' ), 12 );
	}

	/**
	 * Whether any front-end enhancement is active.
	 */
	private static function is_active(): bool {
		if ( ! class_exists( 'WP_Usefull_Blocks_Settings' ) ) {
			return false;
		}

		$settings = WP_Usefull_Blocks_Settings::get();
		$position = $settings['link_status_position'];

		return ( 'off' !== $position ) || ! empty( $settings['strike_broken_links'] );
	}

	/**
	 * Front-end CSS for status dots + strike-through on standard links.
	 */
	public static function enqueue_assets(): void {
		if ( is_admin() || ! self::is_active() ) {
			return;
		}

		wp_enqueue_style(
			'wp-usefull-blocks-content-links',
			WP_USEFULL_BLOCKS_URL . 'assets/content-links.css',
			array(),
			WP_USEFULL_BLOCKS_VERSION
		);
	}

	/**
	 * Enhance standard anchors in post content.
	 *
	 * @param string $content Post content HTML.
	 * @return string
	 */
	public static function filter_content( string $content ): string {
		if ( is_admin() || ! self::is_active() ) {
			return $content;
		}

		if ( '' === $content ) {
			return $content;
		}

		if ( false === stripos( $content, '<a ' ) && false === stripos( $content, '<a>' ) ) {
			return $content;
		}

		self::$live_checks = 0;

		return self::process_html( $content );
	}

	/**
	 * Parse HTML and enhance eligible anchors.
	 *
	 * @param string $html Content HTML.
	 * @return string
	 */
	private static function process_html( string $html ): string {
		if ( ! class_exists( 'DOMDocument' ) ) {
			return $html;
		}

		$previous = libxml_use_internal_errors( true );
		$dom      = new DOMDocument();
		$wrapped  = '<div id="ub-content-links-root">' . $html . '</div>';
		$loaded   = $dom->loadHTML(
			'<?xml encoding="utf-8" ?>' . $wrapped,
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);

		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		if ( false === $loaded ) {
			return $html;
		}

		$root = $dom->getElementById( 'ub-content-links-root' );
		if ( ! $root instanceof DOMElement ) {
			return $html;
		}

		$anchors = array();
		foreach ( $root->getElementsByTagName( 'a' ) as $anchor ) {
			if ( $anchor instanceof DOMElement ) {
				$anchors[] = $anchor;
			}
		}

		$settings = WP_Usefull_Blocks_Settings::get();

		foreach ( $anchors as $anchor ) {
			self::enhance_anchor( $dom, $anchor, $settings );
		}

		$out = '';
		foreach ( $root->childNodes as $child ) {
			$out .= $dom->saveHTML( $child );
		}

		return $out;
	}

	/**
	 * Enhance a single anchor when eligible.
	 *
	 * @param DOMDocument          $dom      Document.
	 * @param DOMElement           $anchor   Anchor element.
	 * @param array<string, mixed> $settings Plugin settings.
	 */
	private static function enhance_anchor( DOMDocument $dom, DOMElement $anchor, array $settings ): void {
		if ( self::should_skip( $anchor ) ) {
			return;
		}

		$href = trim( $anchor->getAttribute( 'href' ) );
		if ( ! self::is_checkable_href( $href ) ) {
			return;
		}

		$status = self::resolve_status( $href, $settings );
		$position = isset( $settings['link_status_position'] )
			? (string) $settings['link_status_position']
			: 'before';
		$show_status = in_array( $position, array( 'before', 'after' ), true );
		$strike      = ! empty( $settings['strike_broken_links'] ) && ( 'broken' === $status );

		if ( ! $show_status && ! $strike ) {
			return;
		}

		$parent = $anchor->parentNode;
		if ( ! $parent instanceof DOMNode ) {
			return;
		}

		$classes = array(
			'ub-content-link',
			'ub-content-link--' . $status,
		);
		if ( $show_status ) {
			$classes[] = ( 'before' === $position )
				? 'ub-content-link--status-before'
				: 'ub-content-link--status-after';
		}
		if ( $strike ) {
			$classes[] = 'is-broken';
		}

		$wrapper = $dom->createElement( 'span' );
		$wrapper->setAttribute( 'class', implode( ' ', $classes ) );

		$anchor_class = trim( $anchor->getAttribute( 'class' ) . ' ub-content-link__anchor' );
		$anchor->setAttribute( 'class', trim( $anchor_class ) );
		if ( $strike ) {
			$title = $anchor->getAttribute( 'title' );
			if ( '' === $title ) {
				$anchor->setAttribute(
					'title',
					__( 'This link appears to be broken.', 'wp-usefull-blocks' )
				);
			}
		}

		$parent->insertBefore( $wrapper, $anchor );

		$indicator = null;
		if ( $show_status && class_exists( 'WP_Usefull_Blocks_Status_Render' ) ) {
			$indicator = self::import_html(
				$dom,
				WP_Usefull_Blocks_Status_Render::indicator( $status )
			);
		}

		if ( $show_status && 'before' === $position && $indicator instanceof DOMNode ) {
			$wrapper->appendChild( $indicator );
		}

		$wrapper->appendChild( $anchor );

		if ( $show_status && 'after' === $position && $indicator instanceof DOMNode ) {
			$wrapper->appendChild( $indicator );
		}
	}

	/**
	 * Skip UB Link / UB File / already-enhanced anchors and non-content UI links.
	 *
	 * @param DOMElement $anchor Anchor.
	 * @return bool
	 */
	private static function should_skip( DOMElement $anchor ): bool {
		$class = $anchor->getAttribute( 'class' );
		if ( str_contains( $class, 'ub-content-link__anchor' )
			|| str_contains( $class, 'ub-link__anchor' )
			|| str_contains( $class, 'ub-file__anchor' )
		) {
			return true;
		}

		$node = $anchor->parentNode;
		while ( $node instanceof DOMElement ) {
			$parent_class = $node->getAttribute( 'class' );
			if (
				str_contains( $parent_class, 'ub-content-link' )
				|| str_contains( $parent_class, 'ub-link' )
				|| str_contains( $parent_class, 'ub-file' )
				|| str_contains( $parent_class, 'wp-block-wp-usefull-blocks-ub-link' )
				|| str_contains( $parent_class, 'wp-block-wp-usefull-blocks-ub-file' )
			) {
				return true;
			}
			$node = $node->parentNode;
		}

		return false;
	}

	/**
	 * Whether an href should be status-checked.
	 *
	 * @param string $href Href attribute.
	 * @return bool
	 */
	private static function is_checkable_href( string $href ): bool {
		if ( '' === $href ) {
			return false;
		}

		$lower = strtolower( $href );
		if (
			str_starts_with( $lower, '#' )
			|| str_starts_with( $lower, 'mailto:' )
			|| str_starts_with( $lower, 'tel:' )
			|| str_starts_with( $lower, 'javascript:' )
			|| str_starts_with( $lower, 'data:' )
		) {
			return false;
		}

		return true;
	}

	/**
	 * Resolve status for a content link (cached / budgeted live check).
	 *
	 * @param string               $href     Href.
	 * @param array<string, mixed> $settings Settings.
	 * @return string ok|broken|unknown
	 */
	private static function resolve_status( string $href, array $settings ): string {
		if ( ! class_exists( 'WP_Usefull_Blocks_Url_Status' ) ) {
			return 'unknown';
		}

		$auto = ! empty( $settings['auto_check_urls'] );
		$url  = WP_Usefull_Blocks_Url_Status::normalize_url( $href );

		if ( '' === $url ) {
			// Keep relative internal paths checkable via normalize; if still empty, unknown.
			$url = $href;
		}

		$cached = WP_Usefull_Blocks_Url_Status::get_cached( $url );
		if ( null !== $cached && ! empty( $cached['status'] ) ) {
			// Recover false broken for internal URLs (same as block render).
			if (
				WP_Usefull_Blocks_Url_Status::STATUS_BROKEN === $cached['status']
				&& WP_Usefull_Blocks_Url_Status::is_internal_url( $url )
			) {
				$result = WP_Usefull_Blocks_Url_Status::check( $url );
				WP_Usefull_Blocks_Url_Status::store( $url, $result );
				return sanitize_key( (string) $result['status'] );
			}

			WP_Usefull_Blocks_Url_Status::watch( $url );
			return sanitize_key( (string) $cached['status'] );
		}

		if ( ! $auto ) {
			return WP_Usefull_Blocks_Url_Status::STATUS_UNKNOWN;
		}

		// Budget live checks so a page with many new URLs stays responsive.
		if ( self::$live_checks >= self::MAX_LIVE_CHECKS ) {
			WP_Usefull_Blocks_Url_Status::watch( $url );
			return WP_Usefull_Blocks_Url_Status::STATUS_UNKNOWN;
		}

		++self::$live_checks;
		$result = WP_Usefull_Blocks_Url_Status::check( $url );
		WP_Usefull_Blocks_Url_Status::store( $url, $result );

		return sanitize_key( (string) ( $result['status'] ?? 'unknown' ) );
	}

	/**
	 * Import an HTML snippet as a DOM node.
	 *
	 * @param DOMDocument $dom  Target document.
	 * @param string      $html HTML snippet.
	 * @return DOMNode|null
	 */
	private static function import_html( DOMDocument $dom, string $html ): ?DOMNode {
		if ( '' === $html ) {
			return null;
		}

		$tmp = new DOMDocument();
		$previous = libxml_use_internal_errors( true );
		$tmp->loadHTML(
			'<?xml encoding="utf-8" ?><div id="ub-import">' . $html . '</div>',
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		$container = $tmp->getElementById( 'ub-import' );
		if ( ! $container instanceof DOMElement || ! $container->firstChild ) {
			return null;
		}

		return $dom->importNode( $container->firstChild, true );
	}
}

WP_Usefull_Blocks_Content_Links::init();
