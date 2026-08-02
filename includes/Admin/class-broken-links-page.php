<?php
/**
 * Admin work page: Broken Links list.
 *
 * @package WpUsefullBlocks
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Broken Links screen under Useful.
 */
final class WP_Usefull_Blocks_Broken_Links_Page {

	public const PAGE_SLUG = 'useful';

	/**
	 * Hook registration.
	 */
	public static function init(): void {
		add_action( 'admin_post_wp_usefull_blocks_scan_links', array( self::class, 'handle_scan' ) );
		add_action( 'admin_post_wp_usefull_blocks_replace_url', array( self::class, 'handle_replace' ) );
		add_action( 'admin_post_wp_usefull_blocks_recheck_url', array( self::class, 'handle_recheck' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
	}

	/**
	 * Traffic-light styles on the Broken Links screen.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public static function enqueue_assets( string $hook_suffix ): void {
		if ( ! str_ends_with( $hook_suffix, '_page_useful' ) && 'toplevel_page_useful' !== $hook_suffix ) {
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
	 * Render the page.
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		require_once WP_USEFULL_BLOCKS_PATH . 'includes/Admin/class-broken-links-table.php';

		$table = new WP_Usefull_Blocks_Broken_Links_Table();
		$table->prepare_items();

		$index      = WP_Usefull_Blocks_Link_Scanner::get_index();
		$scanned_at = (int) $index['scanned_at'];
		$notice     = isset( $_GET['ub_notice'] ) ? sanitize_key( (string) wp_unslash( $_GET['ub_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$count      = isset( $_GET['ub_count'] ) ? absint( $_GET['ub_count'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Broken Links', 'wp-usefull-blocks' ); ?></h1>
			<p>
				<?php
				echo esc_html__(
					'Lists links found in published posts and pages with a traffic-light status. Update a URL to replace it everywhere it appears; the new link is rechecked automatically.',
					'wp-usefull-blocks'
				);
				?>
			</p>

			<?php if ( 'scanned' === $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p>
					<?php
					printf(
						/* translators: 1: posts scanned, 2: urls found, 3: broken count */
						esc_html__( 'Scan complete. Checked %1$d posts/pages, %2$d unique URLs, %3$d broken.', 'wp-usefull-blocks' ),
						absint( $_GET['ub_posts'] ?? 0 ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						absint( $_GET['ub_urls'] ?? 0 ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						absint( $_GET['ub_broken'] ?? 0 ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					);
					?>
				</p></div>
			<?php elseif ( 'replaced' === $notice ) : ?>
				<?php
				$new_status = isset( $_GET['ub_status'] ) ? sanitize_key( (string) wp_unslash( $_GET['ub_status'] ) ) : 'unknown'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( ! in_array( $new_status, array( 'ok', 'broken', 'unknown' ), true ) ) {
					$new_status = 'unknown';
				}
				$ampel         = class_exists( 'WP_Usefull_Blocks_Status_Render' )
					? WP_Usefull_Blocks_Status_Render::indicator( $new_status )
					: '';
				$status_labels = array(
					'ok'      => __( 'OK', 'wp-usefull-blocks' ),
					'broken'  => __( 'Broken', 'wp-usefull-blocks' ),
					'unknown' => __( 'Unknown', 'wp-usefull-blocks' ),
				);
				$notice_class  = ( 'ok' === $new_status ) ? 'notice-success' : 'notice-warning';
				?>
				<div class="notice <?php echo esc_attr( $notice_class ); ?> is-dismissible"><p>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d: number of posts updated */
							__( 'Updated the URL in %d post(s).', 'wp-usefull-blocks' ),
							$count
						)
					);
					echo ' ';
					echo esc_html__( 'New link status:', 'wp-usefull-blocks' );
					echo ' ';
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- indicator() returns escaped HTML.
					echo $ampel;
					echo ' ' . esc_html( $status_labels[ $new_status ] );
					?>
				</p></div>
			<?php elseif ( 'rechecked' === $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p>
					<?php esc_html_e( 'URL rechecked.', 'wp-usefull-blocks' ); ?>
				</p></div>
			<?php elseif ( 'error' === $notice ) : ?>
				<div class="notice notice-error is-dismissible"><p>
					<?php esc_html_e( 'Something went wrong. Please try again.', 'wp-usefull-blocks' ); ?>
				</p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:1rem 0;">
				<?php wp_nonce_field( 'wp_usefull_blocks_scan_links', 'ub_scan_nonce' ); ?>
				<input type="hidden" name="action" value="wp_usefull_blocks_scan_links" />
				<?php
				submit_button(
					__( 'Scan now', 'wp-usefull-blocks' ),
					'primary',
					'submit',
					false
				);
				?>
				<?php if ( $scanned_at > 0 ) : ?>
					<span class="description" style="margin-left:0.75rem;">
						<?php
						printf(
							/* translators: %s: localized datetime */
							esc_html__( 'Last scan: %s', 'wp-usefull-blocks' ),
							esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $scanned_at ) )
						);
						?>
					</span>
				<?php endif; ?>
			</form>

			<?php $table->display(); ?>
		</div>
		<?php
	}

	/**
	 * Handle Scan now.
	 */
	public static function handle_scan(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden.', 'wp-usefull-blocks' ) );
		}
		check_admin_referer( 'wp_usefull_blocks_scan_links', 'ub_scan_nonce' );

		$result = WP_Usefull_Blocks_Link_Scanner::scan();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'      => self::PAGE_SLUG,
					'ub_notice' => 'scanned',
					'ub_posts'  => (int) $result['scanned'],
					'ub_urls'   => (int) $result['urls'],
					'ub_broken' => (int) $result['broken'],
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle URL replace.
	 */
	public static function handle_replace(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden.', 'wp-usefull-blocks' ) );
		}
		check_admin_referer( 'wp_usefull_blocks_replace_url', 'ub_replace_nonce' );

		$old_url = isset( $_POST['old_url'] ) ? esc_url_raw( (string) wp_unslash( $_POST['old_url'] ) ) : '';
		$new_url = isset( $_POST['new_url'] ) ? esc_url_raw( (string) wp_unslash( $_POST['new_url'] ) ) : '';
		$ids_raw = isset( $_POST['post_ids'] ) ? sanitize_text_field( (string) wp_unslash( $_POST['post_ids'] ) ) : '';
		$hrefs_raw = isset( $_POST['hrefs'] ) ? (string) wp_unslash( $_POST['hrefs'] ) : '[]';

		$post_ids = array_filter( array_map( 'absint', explode( ',', $ids_raw ) ) );
		$hrefs    = json_decode( $hrefs_raw, true );
		if ( ! is_array( $hrefs ) ) {
			$hrefs = array();
		}
		$hrefs = array_values( array_filter( array_map( 'strval', $hrefs ) ) );

		if ( '' === $old_url || '' === $new_url || array() === $post_ids ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'      => self::PAGE_SLUG,
						'ub_notice' => 'error',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		$result = WP_Usefull_Blocks_Url_Replacer::replace_in_posts( $post_ids, $old_url, $new_url, $hrefs );

		// Recheck the new URL and move the index row so the traffic light can turn green.
		$check = array(
			'status'  => 'unknown',
			'code'    => 0,
			'message' => '',
		);
		if ( class_exists( 'WP_Usefull_Blocks_Url_Status' ) ) {
			$check = WP_Usefull_Blocks_Url_Status::check( $new_url );
			WP_Usefull_Blocks_Url_Status::store( $new_url, $check );
			delete_transient( 'ub_url_status_' . md5( $old_url ) );
		}
		WP_Usefull_Blocks_Link_Scanner::replace_url_entry( $old_url, $new_url, $check );

		$new_status = sanitize_key( (string) ( $check['status'] ?? 'unknown' ) );
		if ( ! in_array( $new_status, array( 'ok', 'broken', 'unknown' ), true ) ) {
			$new_status = 'unknown';
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'      => self::PAGE_SLUG,
					'ub_notice' => 'replaced',
					'ub_count'  => (int) $result['updated'],
					'ub_status' => $new_status,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle single URL recheck.
	 */
	public static function handle_recheck(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden.', 'wp-usefull-blocks' ) );
		}
		check_admin_referer( 'wp_usefull_blocks_recheck_url', 'ub_recheck_nonce' );

		$url = isset( $_POST['url'] ) ? esc_url_raw( (string) wp_unslash( $_POST['url'] ) ) : '';
		if ( '' !== $url && class_exists( 'WP_Usefull_Blocks_Url_Status' ) ) {
			$result = WP_Usefull_Blocks_Url_Status::check( $url );
			WP_Usefull_Blocks_Url_Status::store( $url, $result );
			// Update only this URL in the index — do not rescan / re-live-check other URLs.
			WP_Usefull_Blocks_Link_Scanner::apply_status( $url, $result );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'      => self::PAGE_SLUG,
					'ub_notice' => 'rechecked',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}

WP_Usefull_Blocks_Broken_Links_Page::init();
