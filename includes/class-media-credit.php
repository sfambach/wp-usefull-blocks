<?php
/**
 * Media credit (source attribution) for images and files.
 *
 * Credit fields are stored once per attachment in the media library and rendered
 * automatically below every block that uses that attachment.
 *
 * @package WpUsefullBlocks
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers credit fields on attachments and appends the credit line on render.
 */
final class WP_Usefull_Blocks_Media_Credit {

	public const META_AUTHOR  = '_ub_credit_author';
	public const META_URL     = '_ub_credit_url';
	public const META_LICENSE = '_ub_credit_license';

	private const HANDLE = 'wp-usefull-blocks-media-credit';

	/**
	 * Block name => attribute holding the attachment ID.
	 *
	 * @var array<string,string>
	 */
	private const BLOCKS = array(
		'core/image'                  => 'id',
		'core/file'                   => 'id',
		'wp-usefull-blocks/ub-file'   => 'attachmentId',
	);

	/**
	 * Hook registration.
	 */
	public static function init(): void {
		/** @see https://developer.wordpress.org/reference/hooks/init/ */
		add_action( 'init', array( self::class, 'register_meta' ) );

		/** @see https://developer.wordpress.org/reference/hooks/attachment_fields_to_edit/ */
		add_filter( 'attachment_fields_to_edit', array( self::class, 'fields_to_edit' ), 10, 2 );

		/** @see https://developer.wordpress.org/reference/hooks/attachment_fields_to_save/ */
		add_filter( 'attachment_fields_to_save', array( self::class, 'fields_to_save' ), 10, 2 );

		/** @see https://developer.wordpress.org/reference/hooks/wp_enqueue_scripts/ */
		add_action( 'wp_enqueue_scripts', array( self::class, 'register_assets' ) );

		/** @see https://developer.wordpress.org/reference/hooks/enqueue_block_editor_assets/ */
		add_action( 'enqueue_block_editor_assets', array( self::class, 'enqueue_editor' ) );

		/** @see https://developer.wordpress.org/reference/hooks/render_block/ */
		add_filter( 'render_block', array( self::class, 'append_credit' ), 10, 2 );
	}

	/**
	 * Register attachment meta (also exposed to the REST API for the editor).
	 */
	public static function register_meta(): void {
		$sanitizers = array(
			self::META_AUTHOR  => 'sanitize_text_field',
			self::META_URL     => 'esc_url_raw',
			self::META_LICENSE => 'sanitize_text_field',
		);

		foreach ( $sanitizers as $key => $sanitize ) {
			register_post_meta(
				'attachment',
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'default'           => '',
					'show_in_rest'      => true,
					'sanitize_callback' => $sanitize,
					'auth_callback'     => static function ( $allowed, $meta_key, $post_id ): bool {
						return current_user_can( 'edit_post', (int) $post_id );
					},
				)
			);
		}
	}

	/**
	 * Field labels keyed by meta key.
	 *
	 * @return array<string,array{label:string,helps:string}>
	 */
	private static function field_definitions(): array {
		return array(
			self::META_AUTHOR  => array(
				'label' => __( 'Source / author', 'wp-usefull-blocks' ),
				'helps' => __( 'Shown as source below images and files using this media item.', 'wp-usefull-blocks' ),
			),
			self::META_URL     => array(
				'label' => __( 'Source URL', 'wp-usefull-blocks' ),
				'helps' => __( 'Optional link to the original.', 'wp-usefull-blocks' ),
			),
			self::META_LICENSE => array(
				'label' => __( 'License', 'wp-usefull-blocks' ),
				'helps' => __( 'Optional, e.g. CC BY-SA 4.0.', 'wp-usefull-blocks' ),
			),
		);
	}

	/**
	 * Add credit fields to the media library edit screen and media modal.
	 *
	 * @param array<string,mixed> $form_fields Existing fields.
	 * @param WP_Post             $post        Attachment.
	 * @return array<string,mixed>
	 */
	public static function fields_to_edit( array $form_fields, WP_Post $post ): array {
		foreach ( self::field_definitions() as $key => $field ) {
			$form_fields[ $key ] = array(
				'label' => $field['label'],
				'input' => 'text',
				'value' => (string) get_post_meta( $post->ID, $key, true ),
				'helps' => $field['helps'],
			);
		}

		return $form_fields;
	}

	/**
	 * Persist credit fields. Nonce and capability are checked by core before this filter runs.
	 *
	 * @param array<string,mixed> $post       Attachment data.
	 * @param array<string,mixed> $attachment Submitted attachment fields.
	 * @return array<string,mixed>
	 */
	public static function fields_to_save( array $post, array $attachment ): array {
		$post_id = isset( $post['ID'] ) ? (int) $post['ID'] : 0;
		if ( $post_id < 1 || ! current_user_can( 'edit_post', $post_id ) ) {
			return $post;
		}

		foreach ( array_keys( self::field_definitions() ) as $key ) {
			if ( ! array_key_exists( $key, $attachment ) ) {
				continue;
			}
			$value = self::META_URL === $key
				? esc_url_raw( wp_unslash( (string) $attachment[ $key ] ) )
				: sanitize_text_field( wp_unslash( (string) $attachment[ $key ] ) );

			if ( '' === $value ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, $value );
			}
		}

		return $post;
	}

	/**
	 * Register the front-end style; enqueued only when a credit is rendered.
	 */
	public static function register_assets(): void {
		$path = WP_USEFULL_BLOCKS_PATH . 'assets/media-credit.css';

		wp_register_style(
			self::HANDLE,
			WP_USEFULL_BLOCKS_URL . 'assets/media-credit.css',
			array(),
			(string) filemtime( $path )
		);
	}

	/**
	 * Enqueue the block sidebar panel that edits title and credit in the media library.
	 */
	public static function enqueue_editor(): void {
		$path   = WP_USEFULL_BLOCKS_PATH . 'assets/editor-media-credit.js';
		$handle = self::HANDLE . '-editor';

		wp_enqueue_script(
			$handle,
			WP_USEFULL_BLOCKS_URL . 'assets/editor-media-credit.js',
			array(
				'wp-block-editor',
				'wp-components',
				'wp-compose',
				'wp-core-data',
				'wp-data',
				'wp-element',
				'wp-hooks',
				'wp-i18n',
				'wp-notices',
			),
			(string) filemtime( $path ),
			true
		);

		wp_set_script_translations( $handle, 'wp-usefull-blocks', WP_USEFULL_BLOCKS_PATH . 'languages' );
	}

	/**
	 * Credit data for an attachment, or null when no source is set.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return array{author:string,url:string,license:string}|null
	 */
	public static function get_credit( int $attachment_id ): ?array {
		if ( $attachment_id < 1 ) {
			return null;
		}

		$credit = array(
			'author'  => (string) get_post_meta( $attachment_id, self::META_AUTHOR, true ),
			'url'     => (string) get_post_meta( $attachment_id, self::META_URL, true ),
			'license' => (string) get_post_meta( $attachment_id, self::META_LICENSE, true ),
		);

		if ( '' === $credit['author'] && '' === $credit['url'] ) {
			return null;
		}

		return $credit;
	}

	/**
	 * Build the credit line HTML.
	 *
	 * @param array{author:string,url:string,license:string} $credit Credit data.
	 * @param string                                          $tag    Wrapper element.
	 * @return string
	 */
	public static function render_credit( array $credit, string $tag = 'p' ): string {
		$name = '' !== $credit['author'] ? $credit['author'] : (string) wp_parse_url( $credit['url'], PHP_URL_HOST );

		$source = '' !== $credit['url']
			? sprintf( '<a href="%s" rel="noopener nofollow" target="_blank">%s</a>', esc_url( $credit['url'] ), esc_html( $name ) )
			: esc_html( $name );

		if ( '' !== $credit['license'] ) {
			$source .= ', ' . esc_html( $credit['license'] );
		}

		return sprintf(
			'<%1$s class="ub-media-credit">%2$s %3$s</%1$s>',
			tag_escape( $tag ),
			esc_html__( 'Source:', 'wp-usefull-blocks' ),
			$source
		);
	}

	/**
	 * Append the credit line to supported blocks on the front end.
	 *
	 * @param string              $block_content Rendered block HTML.
	 * @param array<string,mixed> $block         Parsed block.
	 * @return string
	 */
	public static function append_credit( string $block_content, array $block ): string {
		$name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';
		if ( is_admin() || '' === $block_content || ! isset( self::BLOCKS[ $name ] ) ) {
			return $block_content;
		}

		$attribute     = self::BLOCKS[ $name ];
		$attachment_id = isset( $block['attrs'][ $attribute ] ) ? absint( $block['attrs'][ $attribute ] ) : 0;
		$credit        = self::get_credit( $attachment_id );
		if ( null === $credit ) {
			return $block_content;
		}

		wp_enqueue_style( self::HANDLE );

		// Images: keep the credit inside the figure so it stays with the image (also in galleries).
		if ( 'core/image' === $name ) {
			$position = strrpos( $block_content, '</figure>' );
			if ( false !== $position ) {
				return substr_replace( $block_content, self::render_credit( $credit, 'small' ), $position, 0 );
			}
		}

		return $block_content . self::render_credit( $credit );
	}
}

WP_Usefull_Blocks_Media_Credit::init();
