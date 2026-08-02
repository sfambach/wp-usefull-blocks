<?php
/**
 * WP_List_Table for broken content links.
 *
 * @package WpUsefullBlocks
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Broken links table.
 */
final class WP_Usefull_Blocks_Broken_Links_Table extends WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'broken_link',
				'plural'   => 'broken_links',
				'ajax'     => false,
			)
		);
	}

	/**
	 * @return array<string,string>
	 */
	public function get_columns(): array {
		return array(
			'url'     => __( 'URL', 'wp-usefull-blocks' ),
			'status'  => __( 'Status', 'wp-usefull-blocks' ),
			'posts'   => __( 'Found in', 'wp-usefull-blocks' ),
			'actions' => __( 'Change link', 'wp-usefull-blocks' ),
		);
	}

	/**
	 * Prepare items from scanner index (broken first, then unknown, then OK).
	 */
	public function prepare_items(): void {
		$index = WP_Usefull_Blocks_Link_Scanner::get_index();
		$items = array_values(
			array_filter(
				$index['items'],
				static fn( $item ): bool => is_array( $item )
			)
		);

		usort(
			$items,
			static function ( array $a, array $b ): int {
				// Newest checks first so a just-fixed (green) URL stays visible after Update.
				$ca = isset( $a['checked_at'] ) ? (int) $a['checked_at'] : 0;
				$cb = isset( $b['checked_at'] ) ? (int) $b['checked_at'] : 0;
				if ( $ca !== $cb ) {
					return $cb <=> $ca;
				}

				$order = array(
					'broken'  => 0,
					'unknown' => 1,
					'ok'      => 2,
				);
				$sa  = isset( $a['status'] ) ? (string) $a['status'] : 'unknown';
				$sb  = isset( $b['status'] ) ? (string) $b['status'] : 'unknown';
				$cmp = ( $order[ $sa ] ?? 1 ) <=> ( $order[ $sb ] ?? 1 );
				if ( 0 !== $cmp ) {
					return $cmp;
				}
				return strcmp( (string) ( $a['url'] ?? '' ), (string) ( $b['url'] ?? '' ) );
			}
		);

		$per_page = 20;
		$current  = $this->get_pagenum();
		$total    = count( $items );

		$this->items           = array_slice( $items, ( $current - 1 ) * $per_page, $per_page );
		$this->_column_headers = array( $this->get_columns(), array(), array() );

		$this->set_pagination_args(
			array(
				'total_items' => $total,
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $total / $per_page ),
			)
		);
	}

	/**
	 * @param array<string,mixed> $item Item.
	 * @return string
	 */
	protected function column_url( array $item ): string {
		$url = isset( $item['url'] ) ? (string) $item['url'] : '';
		return sprintf(
			'<code style="word-break:break-all;">%s</code>',
			esc_html( $url )
		);
	}

	/**
	 * @param array<string,mixed> $item Item.
	 * @return string
	 */
	protected function column_status( array $item ): string {
		$status  = isset( $item['status'] ) ? sanitize_key( (string) $item['status'] ) : 'unknown';
		$code    = isset( $item['code'] ) ? (int) $item['code'] : 0;
		$message = isset( $item['message'] ) ? (string) $item['message'] : '';

		if ( ! in_array( $status, array( 'ok', 'broken', 'unknown' ), true ) ) {
			$status = 'unknown';
		}

		$labels = array(
			'ok'      => __( 'OK', 'wp-usefull-blocks' ),
			'broken'  => __( 'Broken', 'wp-usefull-blocks' ),
			'unknown' => __( 'Unknown', 'wp-usefull-blocks' ),
		);

		$ampel = class_exists( 'WP_Usefull_Blocks_Status_Render' )
			? WP_Usefull_Blocks_Status_Render::indicator( $status )
			: '';

		$out = $ampel . ' <strong>' . esc_html( $labels[ $status ] ) . '</strong>';
		if ( $code > 0 ) {
			$out .= ' <span class="description">(' . esc_html( (string) $code ) . ')</span>';
		}
		if ( '' !== $message ) {
			$out .= '<br><span class="description">' . esc_html( $message ) . '</span>';
		}
		return $out;
	}

	/**
	 * @param array<string,mixed> $item Item.
	 * @return string
	 */
	protected function column_posts( array $item ): string {
		$posts = isset( $item['posts'] ) && is_array( $item['posts'] ) ? $item['posts'] : array();
		if ( array() === $posts ) {
			return '&mdash;';
		}

		$links = array();
		foreach ( $posts as $post ) {
			if ( ! is_array( $post ) ) {
				continue;
			}
			$id    = isset( $post['id'] ) ? (int) $post['id'] : 0;
			$title = isset( $post['title'] ) ? (string) $post['title'] : '';
			if ( $id < 1 ) {
				continue;
			}
			if ( '' === $title ) {
				$title = sprintf(
					/* translators: %d: post ID */
					__( 'Post #%d', 'wp-usefull-blocks' ),
					$id
				);
			}
			$edit = get_edit_post_link( $id, 'raw' );
			if ( $edit ) {
				$links[] = '<a href="' . esc_url( $edit ) . '">' . esc_html( $title ) . '</a>';
			} else {
				$links[] = esc_html( $title );
			}
		}

		return implode( '<br>', $links );
	}

	/**
	 * @param array<string,mixed> $item Item.
	 * @return string
	 */
	protected function column_actions( array $item ): string {
		$url   = isset( $item['url'] ) ? (string) $item['url'] : '';
		$hrefs = isset( $item['hrefs'] ) && is_array( $item['hrefs'] ) ? $item['hrefs'] : array();
		$posts = isset( $item['posts'] ) && is_array( $item['posts'] ) ? $item['posts'] : array();
		$ids   = array();
		foreach ( $posts as $post ) {
			if ( is_array( $post ) && ! empty( $post['id'] ) ) {
				$ids[] = (int) $post['id'];
			}
		}

		$action_url = admin_url( 'admin-post.php' );
		ob_start();
		?>
		<form method="post" action="<?php echo esc_url( $action_url ); ?>" style="display:flex;gap:0.5rem;flex-wrap:wrap;align-items:center;">
			<?php wp_nonce_field( 'wp_usefull_blocks_replace_url', 'ub_replace_nonce' ); ?>
			<input type="hidden" name="action" value="wp_usefull_blocks_replace_url" />
			<input type="hidden" name="old_url" value="<?php echo esc_attr( $url ); ?>" />
			<input type="hidden" name="post_ids" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>" />
			<input type="hidden" name="hrefs" value="<?php echo esc_attr( wp_json_encode( array_values( $hrefs ) ) ); ?>" />
			<label class="screen-reader-text" for="ub-new-url-<?php echo esc_attr( md5( $url ) ); ?>">
				<?php esc_html_e( 'New URL', 'wp-usefull-blocks' ); ?>
			</label>
			<input
				type="url"
				class="regular-text"
				id="ub-new-url-<?php echo esc_attr( md5( $url ) ); ?>"
				name="new_url"
				placeholder="https://"
				required
			/>
			<?php submit_button( __( 'Update URL', 'wp-usefull-blocks' ), 'secondary', 'submit', false ); ?>
		</form>
		<form method="post" action="<?php echo esc_url( $action_url ); ?>" style="margin-top:0.35rem;">
			<?php wp_nonce_field( 'wp_usefull_blocks_recheck_url', 'ub_recheck_nonce' ); ?>
			<input type="hidden" name="action" value="wp_usefull_blocks_recheck_url" />
			<input type="hidden" name="url" value="<?php echo esc_attr( $url ); ?>" />
			<?php submit_button( __( 'Recheck', 'wp-usefull-blocks' ), 'link', 'submit', false ); ?>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Default column.
	 *
	 * @param array<string,mixed> $item        Item.
	 * @param string              $column_name Column.
	 * @return string
	 */
	protected function column_default( $item, $column_name ): string {
		return isset( $item[ $column_name ] ) ? esc_html( (string) $item[ $column_name ] ) : '';
	}

	/**
	 * Message when empty.
	 */
	public function no_items(): void {
		esc_html_e( 'No links indexed yet. Run a scan to refresh.', 'wp-usefull-blocks' );
	}
}
