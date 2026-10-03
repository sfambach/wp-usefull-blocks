<?php
/**
 * Copies image titles set in core/image blocks to the media library.
 *
 * @package WpUsefullBlocks
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keeps the attachment title in sync with the title attribute of image blocks.
 *
 * Runs on every post save and once in the background for existing content.
 */
final class WP_Usefull_Blocks_Media_Title_Sync {

	private const DONE_OPTION   = 'wp_usefull_blocks_title_sync_done';
	private const OFFSET_OPTION = 'wp_usefull_blocks_title_sync_offset';
	private const CRON_HOOK     = 'wp_usefull_blocks_title_sync_batch';
	private const BATCH_SIZE    = 50;

	/**
	 * Hook registration.
	 */
	public static function init(): void {
		/** @see https://developer.wordpress.org/reference/hooks/wp_after_insert_post/ */
		add_action( 'wp_after_insert_post', array( self::class, 'on_save' ), 10, 2 );

		/** @see https://developer.wordpress.org/reference/hooks/admin_init/ */
		add_action( 'admin_init', array( self::class, 'maybe_schedule_backfill' ) );

		add_action( self::CRON_HOOK, array( self::class, 'run_backfill_batch' ) );
	}

	/**
	 * Sync titles after a post is saved.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public static function on_save( int $post_id, WP_Post $post ): void {
		if ( 'attachment' === $post->post_type || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		self::sync_content( (string) $post->post_content );
	}

	/**
	 * Copy block image titles of the given content to their attachments.
	 *
	 * @param string $content Post content.
	 * @return int Number of updated attachments.
	 */
	public static function sync_content( string $content ): int {
		if ( false === strpos( $content, 'wp:image' ) ) {
			return 0;
		}

		$updated = 0;
		foreach ( self::collect_titles( parse_blocks( $content ) ) as $attachment_id => $title ) {
			$attachment = get_post( $attachment_id );
			if ( ! $attachment instanceof WP_Post || 'attachment' !== $attachment->post_type ) {
				continue;
			}
			if ( $attachment->post_title === $title || ( ! wp_doing_cron() && ! current_user_can( 'edit_post', $attachment_id ) ) ) {
				continue;
			}

			wp_update_post(
				array(
					'ID'         => $attachment_id,
					'post_title' => wp_slash( $title ),
				)
			);
			++$updated;
		}

		return $updated;
	}

	/**
	 * Attachment ID => title for all core/image blocks (also nested, e.g. in galleries).
	 *
	 * @param array<int,array<string,mixed>> $blocks Parsed blocks.
	 * @return array<int,string>
	 */
	private static function collect_titles( array $blocks ): array {
		$titles = array();

		foreach ( $blocks as $block ) {
			if ( 'core/image' === ( $block['blockName'] ?? '' ) ) {
				$attachment_id = absint( $block['attrs']['id'] ?? 0 );
				$title         = self::img_title( (string) ( $block['innerHTML'] ?? '' ) );
				if ( $attachment_id > 0 && '' !== $title ) {
					$titles[ $attachment_id ] = $title;
				}
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$titles = array_replace( $titles, self::collect_titles( $block['innerBlocks'] ) );
			}
		}

		return $titles;
	}

	/**
	 * Title attribute of the first img tag.
	 *
	 * @param string $html Block HTML.
	 * @return string
	 */
	private static function img_title( string $html ): string {
		$tags = new WP_HTML_Tag_Processor( $html );
		if ( ! $tags->next_tag( 'img' ) ) {
			return '';
		}

		$title = $tags->get_attribute( 'title' );

		return is_string( $title ) ? sanitize_text_field( $title ) : '';
	}

	/**
	 * Schedule the one-time backfill for existing content.
	 */
	public static function maybe_schedule_backfill(): void {
		if ( get_option( self::DONE_OPTION ) || wp_next_scheduled( self::CRON_HOOK ) ) {
			return;
		}

		wp_schedule_single_event( time() + 60, self::CRON_HOOK );
	}

	/**
	 * Process one batch of posts and reschedule until all are done.
	 */
	public static function run_backfill_batch(): void {
		$offset = (int) get_option( self::OFFSET_OPTION, 0 );
		$ids    = get_posts(
			array(
				'post_type'        => array( 'post', 'page' ),
				'post_status'      => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'posts_per_page'   => self::BATCH_SIZE,
				'offset'           => $offset,
				'fields'           => 'ids',
				'suppress_filters' => true,
			)
		);

		foreach ( $ids as $id ) {
			self::sync_content( (string) get_post_field( 'post_content', (int) $id, 'raw' ) );
		}

		if ( count( $ids ) < self::BATCH_SIZE ) {
			update_option( self::DONE_OPTION, 1, false );
			delete_option( self::OFFSET_OPTION );
			return;
		}

		update_option( self::OFFSET_OPTION, $offset + self::BATCH_SIZE, false );
		wp_schedule_single_event( time() + 30, self::CRON_HOOK );
	}
}

WP_Usefull_Blocks_Media_Title_Sync::init();
