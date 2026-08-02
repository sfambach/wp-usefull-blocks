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
		add_action( 'wp_ajax_wp_usefull_blocks_replace_url', array( self::class, 'ajax_replace' ) );
		add_action( 'wp_ajax_wp_usefull_blocks_recheck_url', array( self::class, 'ajax_recheck' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
	}

	/**
	 * Styles + in-place update script on the Broken Links screen.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public static function enqueue_assets( string $hook_suffix ): void {
		if ( ! str_ends_with( $hook_suffix, '_page_useful' ) && 'toplevel_page_useful' !== $hook_suffix ) {
			return;
		}

		$css_path = WP_USEFULL_BLOCKS_PATH . 'assets/content-links.css';
		$js_path  = WP_USEFULL_BLOCKS_PATH . 'assets/admin-broken-links.js';
		$css_ver  = is_readable( $css_path ) ? (string) filemtime( $css_path ) : WP_USEFULL_BLOCKS_VERSION;
		$js_ver   = is_readable( $js_path ) ? (string) filemtime( $js_path ) : WP_USEFULL_BLOCKS_VERSION;

		wp_enqueue_style(
			'wp-usefull-blocks-content-links',
			WP_USEFULL_BLOCKS_URL . 'assets/content-links.css',
			array(),
			$css_ver
		);

		wp_enqueue_script(
			'wp-usefull-blocks-admin-broken-links',
			WP_USEFULL_BLOCKS_URL . 'assets/admin-broken-links.js',
			array(),
			$js_ver,
			true
		);

		wp_localize_script(
			'wp-usefull-blocks-admin-broken-links',
			'wpUsefullBlocksBrokenLinks',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'replaceAction' => 'wp_usefull_blocks_replace_url',
				'recheckAction' => 'wp_usefull_blocks_recheck_url',
				'i18n'          => array(
					'updated'    => __( 'URL updated. Traffic light refreshed; reload the page to refresh the list.', 'wp-usefull-blocks' ),
					'rechecked'  => __( 'URL rechecked.', 'wp-usefull-blocks' ),
					'error'      => __( 'Something went wrong. Please try again.', 'wp-usefull-blocks' ),
					'missingUrl' => __( 'Please enter a new URL.', 'wp-usefull-blocks' ),
				),
			)
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
					'Lists links found in published posts and pages with a traffic-light status. Update a URL to replace it everywhere it appears; only the traffic light updates until you reload the page.',
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
	 * Classic form POST fallback for Update URL.
	 */
	public static function handle_replace(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden.', 'wp-usefull-blocks' ) );
		}
		check_admin_referer( 'wp_usefull_blocks_replace_url', 'ub_replace_nonce' );

		$outcome = self::process_replace_request();
		if ( is_wp_error( $outcome ) ) {
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

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'      => self::PAGE_SLUG,
					'ub_notice' => 'replaced',
					'ub_count'  => (int) $outcome['updated'],
					'ub_status' => (string) $outcome['status'],
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * AJAX Update URL: replace in content, recheck, return status HTML only.
	 */
	public static function ajax_replace(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Forbidden.', 'wp-usefull-blocks' ),
				),
				403
			);
		}

		check_ajax_referer( 'wp_usefull_blocks_replace_url', 'ub_replace_nonce' );

		$outcome = self::process_replace_request();
		if ( is_wp_error( $outcome ) ) {
			wp_send_json_error(
				array(
					'message' => $outcome->get_error_message(),
				)
			);
		}

		$status     = (string) $outcome['status'];
		$code       = (int) $outcome['code'];
		$message    = (string) $outcome['message'];
		$checked_at = (int) $outcome['checked_at'];
		$html       = class_exists( 'WP_Usefull_Blocks_Status_Render' )
			? WP_Usefull_Blocks_Status_Render::admin_cell( $status, $code, $message, $checked_at )
			: esc_html( $status );

		wp_send_json_success(
			array(
				'status'     => $status,
				'statusHtml' => $html,
				'updated'    => (int) $outcome['updated'],
				'message'    => sprintf(
					/* translators: 1: number of posts, 2: status label */
					__( 'Updated the URL in %1$d post(s). Status: %2$s', 'wp-usefull-blocks' ),
					(int) $outcome['updated'],
					self::status_label( $status )
				),
			)
		);
	}

	/**
	 * AJAX Recheck: verify URL and return updated status HTML (row stays put).
	 */
	public static function ajax_recheck(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Forbidden.', 'wp-usefull-blocks' ),
				),
				403
			);
		}

		check_ajax_referer( 'wp_usefull_blocks_recheck_url', 'ub_recheck_nonce' );

		$outcome = self::process_recheck_request();
		if ( is_wp_error( $outcome ) ) {
			wp_send_json_error(
				array(
					'message' => $outcome->get_error_message(),
				)
			);
		}

		$status     = (string) $outcome['status'];
		$code       = (int) $outcome['code'];
		$message    = (string) $outcome['message'];
		$checked_at = (int) $outcome['checked_at'];
		$html       = class_exists( 'WP_Usefull_Blocks_Status_Render' )
			? WP_Usefull_Blocks_Status_Render::admin_cell( $status, $code, $message, $checked_at )
			: esc_html( $status );

		wp_send_json_success(
			array(
				'status'     => $status,
				'statusHtml' => $html,
				'message'    => sprintf(
					/* translators: %s: status label */
					__( 'URL rechecked. Status: %s', 'wp-usefull-blocks' ),
					self::status_label( $status )
				),
			)
		);
	}

	/**
	 * Shared replace + status check logic.
	 *
	 * @return array{updated:int,status:string,code:int,message:string,checked_at:int}|WP_Error
	 */
	private static function process_replace_request() {
		$old_url   = isset( $_POST['old_url'] ) ? esc_url_raw( (string) wp_unslash( $_POST['old_url'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$new_url   = isset( $_POST['new_url'] ) ? esc_url_raw( (string) wp_unslash( $_POST['new_url'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$ids_raw   = isset( $_POST['post_ids'] ) ? sanitize_text_field( (string) wp_unslash( $_POST['post_ids'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$hrefs_raw = isset( $_POST['hrefs'] ) ? (string) wp_unslash( $_POST['hrefs'] ) : '[]'; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$post_ids = array_filter( array_map( 'absint', explode( ',', $ids_raw ) ) );
		$hrefs    = json_decode( $hrefs_raw, true );
		if ( ! is_array( $hrefs ) ) {
			$hrefs = array();
		}
		$hrefs = array_values( array_filter( array_map( 'strval', $hrefs ) ) );

		if ( '' === $old_url || '' === $new_url || array() === $post_ids ) {
			return new WP_Error(
				'ub_invalid_replace',
				__( 'Something went wrong. Please try again.', 'wp-usefull-blocks' )
			);
		}

		$result = WP_Usefull_Blocks_Url_Replacer::replace_in_posts( $post_ids, $old_url, $new_url, $hrefs );

		$check = array(
			'status'  => 'unknown',
			'code'    => 0,
			'message' => '',
		);
		if ( class_exists( 'WP_Usefull_Blocks_Url_Status' ) ) {
			$check              = WP_Usefull_Blocks_Url_Status::check( $new_url );
			$check['checkedAt'] = time();
			WP_Usefull_Blocks_Url_Status::store( $new_url, $check );
			delete_transient( 'ub_url_status_' . md5( $old_url ) );
		}

		// Persist for the next full page load. The current list row stays put via AJAX UI.
		WP_Usefull_Blocks_Link_Scanner::replace_url_entry( $old_url, $new_url, $check );

		$new_status = sanitize_key( (string) ( $check['status'] ?? 'unknown' ) );
		if ( ! in_array( $new_status, array( 'ok', 'broken', 'unknown' ), true ) ) {
			$new_status = 'unknown';
		}

		$checked_at = isset( $check['checkedAt'] ) ? (int) $check['checkedAt'] : time();

		return array(
			'updated'    => (int) $result['updated'],
			'status'     => $new_status,
			'code'       => isset( $check['code'] ) ? (int) $check['code'] : 0,
			'message'    => isset( $check['message'] ) ? (string) $check['message'] : '',
			'checked_at' => $checked_at,
		);
	}

	/**
	 * Shared single-URL recheck logic.
	 *
	 * Prefers checking the provided URL (may be the new URL from the textarea).
	 * Optionally patches a different index row via index_url (the visible old URL).
	 *
	 * @return array{status:string,code:int,message:string,checked_at:int}|WP_Error
	 */
	private static function process_recheck_request() {
		$url = isset( $_POST['url'] ) ? esc_url_raw( (string) wp_unslash( $_POST['url'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$index_url = isset( $_POST['index_url'] ) ? esc_url_raw( (string) wp_unslash( $_POST['index_url'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( '' === $url || ! class_exists( 'WP_Usefull_Blocks_Url_Status' ) ) {
			return new WP_Error(
				'ub_invalid_recheck',
				__( 'Something went wrong. Please try again.', 'wp-usefull-blocks' )
			);
		}
		if ( '' === $index_url ) {
			$index_url = $url;
		}

		$result              = WP_Usefull_Blocks_Url_Status::check( $url );
		$result['checkedAt'] = time();
		WP_Usefull_Blocks_Url_Status::store( $url, $result );
		WP_Usefull_Blocks_Link_Scanner::apply_status( $url, $result );
		if ( $index_url !== $url ) {
			WP_Usefull_Blocks_Link_Scanner::apply_status( $index_url, $result );
		}

		$status = sanitize_key( (string) ( $result['status'] ?? 'unknown' ) );
		if ( ! in_array( $status, array( 'ok', 'broken', 'unknown' ), true ) ) {
			$status = 'unknown';
		}

		return array(
			'status'     => $status,
			'code'       => isset( $result['code'] ) ? (int) $result['code'] : 0,
			'message'    => isset( $result['message'] ) ? (string) $result['message'] : '',
			'checked_at' => (int) $result['checkedAt'],
		);
	}

	/**
	 * @param string $status Status key.
	 */
	private static function status_label( string $status ): string {
		$labels = array(
			'ok'      => __( 'OK', 'wp-usefull-blocks' ),
			'broken'  => __( 'Broken', 'wp-usefull-blocks' ),
			'unknown' => __( 'Unknown', 'wp-usefull-blocks' ),
		);

		return $labels[ $status ] ?? $labels['unknown'];
	}

	/**
	 * Classic form POST fallback for Recheck.
	 */
	public static function handle_recheck(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden.', 'wp-usefull-blocks' ) );
		}
		check_admin_referer( 'wp_usefull_blocks_recheck_url', 'ub_recheck_nonce' );

		$outcome = self::process_recheck_request();
		if ( is_wp_error( $outcome ) ) {
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
