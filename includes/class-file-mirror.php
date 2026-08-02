<?php
/**
 * Download a remote file into the WordPress media library.
 *
 * @package WpUsefullBlocks
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mirrors remote files into the media library on demand.
 */
final class WP_Usefull_Blocks_File_Mirror {

	/**
	 * Default max download size (5 MB).
	 */
	private const DEFAULT_MAX_BYTES = 5242880;

	/**
	 * Download a remote URL into the media library.
	 *
	 * @param string $url Remote file URL.
	 * @return array{attachmentId:int,url:string,filename:string}|\WP_Error
	 */
	public static function mirror( string $url ) {
		$url = esc_url_raw( $url );

		if ( '' === $url ) {
			return new WP_Error( 'ub_file_empty_url', __( 'Please provide a file URL.', 'wp-usefull-blocks' ) );
		}

		if ( ! current_user_can( 'upload_files' ) ) {
			return new WP_Error( 'ub_file_forbidden', __( 'You are not allowed to upload files.', 'wp-usefull-blocks' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$tmp = download_url( $url, 30 );

		if ( is_wp_error( $tmp ) ) {
			return $tmp;
		}

		$size = filesize( $tmp );
		$max  = (int) apply_filters( 'wp_usefull_blocks_file_mirror_max_bytes', self::DEFAULT_MAX_BYTES );

		if ( false !== $size && $size > $max ) {
			wp_delete_file( $tmp );

			return new WP_Error(
				'ub_file_too_large',
				sprintf(
					/* translators: %s: max size in MB */
					__( 'File exceeds the maximum allowed size (%s MB).', 'wp-usefull-blocks' ),
					(string) round( $max / 1024 / 1024, 1 )
				)
			);
		}

		$path_part = (string) wp_parse_url( $url, PHP_URL_PATH );
		$filename  = $path_part ? wp_basename( $path_part ) : 'ub-file-download';
		$filename  = sanitize_file_name( $filename );

		if ( '' === $filename ) {
			$filename = 'ub-file-download';
		}

		$file_array = array(
			'name'     => $filename,
			'tmp_name' => $tmp,
		);

		$attachment_id = media_handle_sideload( $file_array, 0 );

		if ( is_wp_error( $attachment_id ) ) {
			if ( file_exists( $tmp ) ) {
				wp_delete_file( $tmp );
			}

			return $attachment_id;
		}

		$attachment_id = (int) $attachment_id;
		$attachment_url = (string) wp_get_attachment_url( $attachment_id );

		update_post_meta( $attachment_id, '_ub_file_source_url', $url );

		return array(
			'attachmentId' => $attachment_id,
			'url'          => $attachment_url,
			'filename'     => $filename,
		);
	}
}
