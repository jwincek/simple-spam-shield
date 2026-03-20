<?php
/**
 * Spam Logs List Table — extends WP_List_Table for the admin spam log viewer.
 *
 * Ported from Comment & Form Guard's My_Spam_Plugin_Spam_Logs_List_Table,
 * adapted to use our Database_Manager and namespace conventions.
 *
 * @package Simple_Spam_Shield
 */

declare( strict_types=1 );

namespace SSS\Admin;

use SSS\Core\Database_Manager;

// WP_List_Table is loaded by WordPress admin but guard against edge cases.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

final class Spam_Logs_List_Table extends \WP_List_Table {

	public function __construct() {
		parent::__construct( [
			'singular' => 'spam_log',
			'plural'   => 'spam_logs',
			'ajax'     => false,
		] );
	}

	/**
	 * Define table columns.
	 */
	public function get_columns(): array {
		return [
			'cb'         => '<input type="checkbox" />',
			'blocked_at' => __( 'Date / Time', 'simple-spam-shield' ),
			'guard'      => __( 'Guard', 'simple-spam-shield' ),
			'context'    => __( 'Context', 'simple-spam-shield' ),
			'reason'     => __( 'Reason', 'simple-spam-shield' ),
			'content'    => __( 'Content', 'simple-spam-shield' ),
			'ip_address' => __( 'IP Address', 'simple-spam-shield' ),
		];
	}

	/**
	 * Define sortable columns.
	 */
	public function get_sortable_columns(): array {
		return [
			'blocked_at' => [ 'blocked_at', true ],
			'guard'      => [ 'guard', false ],
			'context'    => [ 'context', false ],
			'ip_address' => [ 'ip_address', false ],
		];
	}

	/**
	 * Define bulk actions.
	 */
	public function get_bulk_actions(): array {
		return [
			'delete' => __( 'Delete', 'simple-spam-shield' ),
		];
	}

	/**
	 * Checkbox column.
	 */
	public function column_cb( $item ): string {
		return sprintf(
			'<input type="checkbox" name="log_id[]" value="%d" />',
			(int) $item->id
		);
	}

	/**
	 * Content column with row actions.
	 */
	public function column_content( $item ): string {
		$content = esc_html( wp_trim_words( $item->content, 15, '…' ) );

		$delete_url = wp_nonce_url(
			add_query_arg(
				[
					'page'   => 'sss-spam-logs',
					'action' => 'delete',
					'log_id' => [ (int) $item->id ],
				],
				admin_url( 'admin.php' )
			),
			'bulk-spam_logs'
		);

		$actions = [
			'delete' => sprintf(
				'<a href="%s" class="submitdelete">%s</a>',
				esc_url( $delete_url ),
				esc_html__( 'Delete', 'simple-spam-shield' )
			),
		];

		return $content . $this->row_actions( $actions );
	}

	/**
	 * Default column renderer.
	 */
	public function column_default( $item, $column_name ): string {
		return match ( $column_name ) {
			'blocked_at' => esc_html(
				get_date_from_gmt(
					$item->blocked_at,
					get_option( 'date_format' ) . ' ' . get_option( 'time_format' )
				)
			),
			'guard', 'context', 'reason', 'ip_address' => esc_html( $item->$column_name ),
			default => '',
		};
	}

	/**
	 * Message when no items exist.
	 */
	public function no_items(): void {
		esc_html_e( 'No blocked submissions recorded yet.', 'simple-spam-shield' );
	}

	/**
	 * Prepare items for display — query, sort, paginate.
	 */
	public function prepare_items(): void {
		$columns  = $this->get_columns();
		$hidden   = [];
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = [ $columns, $hidden, $sortable ];

		// Process bulk actions.
		$this->process_bulk_action();

		$per_page     = 20;
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		$orderby = sanitize_text_field( $_GET['orderby'] ?? 'blocked_at' );
		$order   = sanitize_text_field( $_GET['order'] ?? 'DESC' );

		$this->items = Database_Manager::get_logs( $per_page, $offset, $orderby, $order );

		$total_items = Database_Manager::get_count();

		$this->set_pagination_args( [
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => (int) ceil( $total_items / $per_page ),
		] );
	}

	/**
	 * Process bulk delete action.
	 */
	public function process_bulk_action(): void {
		if ( 'delete' !== $this->current_action() ) {
			return;
		}

		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-spam_logs' ) ) {
			wp_die( esc_html__( 'Nonce verification failed.', 'simple-spam-shield' ) );
		}

		$ids = array_map( 'absint', (array) ( $_REQUEST['log_id'] ?? [] ) );

		if ( ! empty( $ids ) ) {
			Database_Manager::delete_many( $ids );
		}
	}
}
