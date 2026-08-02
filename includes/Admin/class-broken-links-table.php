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
			'status'  => __( 'Status', 'wp-usefull-blocks' ),
			'old_url' => __( 'Old URL', 'wp-usefull-blocks' ),
			'copy'    => '',
			'new_url' => __( 'New URL', 'wp-usefull-blocks' ),
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
	 * Keep stable row identity for in-place AJAX status updates.
	 *
	 * @param array<string,mixed> $item Item.
	 */
	public function single_row( $item ): void {
		$url = isset( $item['url'] ) ? (string) $item['url'] : '';
		echo '<tr data-ub-url="' . esc_attr( $url ) . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Traffic light + reason, with Recheck under the status.
	 *
	 * @param array<string,mixed> $item Item.
	 * @return string
	 */
	protected function column_status( array $item ): string {
		$status  = isset( $item['status'] ) ? sanitize_key( (string) $item['status'] ) : 'unknown';
		$code    = isset( $item['code'] ) ? (int) $item['code'] : 0;
		$message = isset( $item['message'] ) ? (string) $item['message'] : '';
		$url     = isset( $item['url'] ) ? (string) $item['url'] : '';

		$inner = class_exists( 'WP_Usefull_Blocks_Status_Render' )
			? WP_Usefull_Blocks_Status_Render::admin_cell( $status, $code, $message )
			: esc_html( $status );

		$action_url = admin_url( 'admin-post.php' );
		ob_start();
		?>
		<div class="ub-row-status">
			<div class="ub-status-summary">
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- admin_cell() returns escaped HTML.
				echo $inner;
				?>
			</div>
			<?php if ( '' !== $url ) : ?>
				<form method="post" action="<?php echo esc_url( $action_url ); ?>" class="ub-broken-links-recheck">
					<?php wp_nonce_field( 'wp_usefull_blocks_recheck_url', 'ub_recheck_nonce' ); ?>
					<input type="hidden" name="action" value="wp_usefull_blocks_recheck_url" />
					<input type="hidden" name="url" value="<?php echo esc_attr( $url ); ?>" />
					<?php
					submit_button(
						__( 'Recheck', 'wp-usefull-blocks' ),
						'link',
						'submit',
						false,
						array( 'class' => 'button-link ub-recheck-button' )
					);
					?>
				</form>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Old URL as clickable link, with "Found in" under it.
	 *
	 * @param array<string,mixed> $item Item.
	 * @return string
	 */
	protected function column_old_url( array $item ): string {
		$url = isset( $item['url'] ) ? (string) $item['url'] : '';
		if ( '' === $url ) {
			return '&mdash;';
		}

		$out = sprintf(
			'<a class="ub-old-url" href="%1$s" target="_blank" rel="noopener noreferrer" data-ub-old-url="%2$s">%3$s</a>',
			esc_url( $url ),
			esc_attr( $url ),
			esc_html( $url )
		);

		$posts_html = $this->render_found_in( $item );
		if ( '' !== $posts_html ) {
			$out .= '<div class="ub-found-in description">' . $posts_html . '</div>';
		}

		return $out;
	}

	/**
	 * Copy-old-URL-to-the-right button.
	 *
	 * @param array<string,mixed> $item Item.
	 * @return string
	 */
	protected function column_copy( array $item ): string {
		$url = isset( $item['url'] ) ? (string) $item['url'] : '';
		$id  = 'ub-new-url-' . md5( $url );

		return sprintf(
			'<button type="button" class="button ub-copy-old-url" data-ub-target="%1$s" data-ub-old-url="%2$s" title="%3$s" aria-label="%3$s">&rarr;</button>',
			esc_attr( $id ),
			esc_attr( $url ),
			esc_attr__( 'Copy old URL into the new URL field', 'wp-usefull-blocks' )
		);
	}

	/**
	 * New URL field + Save beside it.
	 *
	 * @param array<string,mixed> $item Item.
	 * @return string
	 */
	protected function column_new_url( array $item ): string {
		$url   = isset( $item['url'] ) ? (string) $item['url'] : '';
		$hrefs = isset( $item['hrefs'] ) && is_array( $item['hrefs'] ) ? $item['hrefs'] : array();
		$posts = isset( $item['posts'] ) && is_array( $item['posts'] ) ? $item['posts'] : array();
		$ids   = array();
		foreach ( $posts as $post ) {
			if ( is_array( $post ) && ! empty( $post['id'] ) ) {
				$ids[] = (int) $post['id'];
			}
		}

		$form_id    = 'ub-replace-form-' . md5( $url );
		$field_id   = 'ub-new-url-' . md5( $url );
		$action_url = admin_url( 'admin-post.php' );
		ob_start();
		?>
		<form
			id="<?php echo esc_attr( $form_id ); ?>"
			method="post"
			action="<?php echo esc_url( $action_url ); ?>"
			class="ub-broken-links-replace"
		>
			<?php wp_nonce_field( 'wp_usefull_blocks_replace_url', 'ub_replace_nonce' ); ?>
			<input type="hidden" name="action" value="wp_usefull_blocks_replace_url" />
			<input type="hidden" name="old_url" value="<?php echo esc_attr( $url ); ?>" />
			<input type="hidden" name="post_ids" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>" />
			<input type="hidden" name="hrefs" value="<?php echo esc_attr( wp_json_encode( array_values( $hrefs ) ) ); ?>" />
			<div class="ub-new-url-row">
				<label class="screen-reader-text" for="<?php echo esc_attr( $field_id ); ?>">
					<?php esc_html_e( 'New URL', 'wp-usefull-blocks' ); ?>
				</label>
				<textarea
					class="ub-new-url"
					rows="1"
					id="<?php echo esc_attr( $field_id ); ?>"
					name="new_url"
					placeholder="https://"
					required
				></textarea>
				<?php
				submit_button(
					__( 'Save', 'wp-usefull-blocks' ),
					'secondary small',
					'submit',
					false,
					array( 'class' => 'button button-secondary button-small ub-save-button' )
				);
				?>
			</div>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Compact "Found in" links for under the old URL.
	 *
	 * @param array<string,mixed> $item Item.
	 * @return string
	 */
	private function render_found_in( array $item ): string {
		$posts = isset( $item['posts'] ) && is_array( $item['posts'] ) ? $item['posts'] : array();
		if ( array() === $posts ) {
			return '';
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

		if ( array() === $links ) {
			return '';
		}

		return esc_html__( 'Found in:', 'wp-usefull-blocks' ) . ' ' . implode( ', ', $links );
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
