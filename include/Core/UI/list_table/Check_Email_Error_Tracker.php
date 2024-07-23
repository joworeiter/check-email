<?php namespace CheckEmail\Core\UI\list_table;

use CheckEmail\Util;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . WPINC . '/class-wp-list-table.php';
}

/**
 * Table to display Check Email Logs.
 */
class Check_Email_Error_Tracker extends \WP_List_Table {

	protected $page;

	public function __construct( $page, $args = array() ) {
		$this->page = $page;

		$args = wp_parse_args( $args, array(
			'singular' => 'check-email-error-tracker',     // singular name of the listed records
			'plural'   => 'check-email-error-trackers',    // plural name of the listed records
			'ajax'     => false,           // does this table support ajax?
			'screen'   => $this->page->get_screen(),
		) );

		parent::__construct( $args );
	}

	public function get_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" />',
		);
		$other_columns = array( 'created_at', 'content', 'initiator' );

		foreach ($other_columns  as $column ) {
			$columns[ $column ] = Util\wp_chill_check_email_get_column_label( $column );
		}

		return apply_filters( 'check_email_manage_log_columns', $columns );
	}

	protected function get_sortable_columns() {
		$sortable_columns = array(
			// 'created_at' => array( 'created_at', true ), // true means it's already sorted.
			
		);
		return $sortable_columns;
	}

	protected function column_default( $item, $column_name ) {

		do_action( 'check_email_display_log_columns', $column_name, $item );
	}

	protected function column_created_at( $item ) {
		$email_date = mysql2date(
			// The values within each field are already escaped.
			// phpcs:disable
			sprintf( esc_html__( '%1$s @ %2$s', 'check-email' ), get_option( 'date_format', 'F j, Y' ), 'g:i:s a' ),
			$item->created_at
		);
		// phpcs:enable

		$actions = array();

		$content_ajax_url = add_query_arg(
			array(
				'action' => 'check-email-error-tracker-detail',
				'tracker_id' => $item->id,
				'width'  => '700',
				'height' => '350',
			),
			'admin-ajax.php'
		);

		$actions['view-content'] = sprintf( '<a href="%1$s" class="thickbox" title="%2$s">%3$s</a>',
			esc_url( $content_ajax_url ),
			esc_html__( 'Email Error Content', 'check-email' ),
			esc_html__( 'View Content', 'check-email' )
		);

		

		

		$delete_url = add_query_arg(
			array(
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reason: We are not processing form information.
				'page'                   => ( isset( $_REQUEST['page'] ) ) ? sanitize_text_field( wp_unslash($_REQUEST['page']) ) : '',
				'action'                 => 'check-email-error-tracker-delete',
				$this->_args['singular'] => $item->id,
			)
		);
		$delete_url = add_query_arg( $this->page->get_nonce_args(), $delete_url );

		$actions['delete'] = sprintf( '<a href="%s">%s</a>',
			esc_url( $delete_url ),
			esc_html__( 'Delete', 'check-email' )
		);

		$actions = apply_filters( 'check_email_row_actions', $actions, $item );

		return sprintf( '%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
			/*$1%s*/ $email_date,
			/*$2%s*/ $item->id,
			/*$3%s*/ $this->row_actions( $actions )
		);
		
	}

	

	
	protected function column_content( $item ) {
		return esc_html( $item->content );
	}
	
    protected function column_initiator( $item ) {
        $file_path = $this->get_error_initiator($item->initiator);
		return esc_html( $file_path );
	}

	

	protected function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/ $this->_args['singular'],
			/*$2%s*/ $item->id
		);
	}

    protected function get_bulk_actions() {
		$actions = array(
			'check-email-error-tracker-delete'     => esc_html__( 'Delete', 'check-email' ),
			'check-email-error-tracker-delete-all' => esc_html__( 'Delete All Logs', 'check-email' ),
		);
		$actions = apply_filters( 'el_bulk_actions', $actions );

		return $actions;
	}

	

	public function prepare_items() {
		$this->_column_headers = $this->get_column_info();

		// Get current page number.
		$current_page_no = $this->get_pagenum();
		$per_page        = $this->page->get_per_page();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reason: We are not processing form information.
		list( $items, $total_items ) = $this->page->get_table_manager()->fetch_error_tracker_items( $_GET, $per_page, $current_page_no );

		$this->items = $items;

		// Register pagination options & calculations.
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page ),
		) );
	}

	public function no_items() {
		esc_html_e( 'Email error tracker is empty', 'check-email' );
	}

	public function search_box( $text, $input_id ) {
		$input_text_id  = $input_id . '-search-input';
		$input_date_id  = $input_id . '-search-date-input';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reason: We are not processing form information.
		$input_date_val = ( ! empty( $_REQUEST['d'] ) ) ? sanitize_text_field( wp_unslash($_REQUEST['d']) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reason: We are not processing form information.
		if ( ! empty( $_REQUEST['orderby'] ) )
			// phpcs:ignore
			echo '<input type="hidden" name="orderby" value="' . esc_attr( sanitize_text_field( wp_unslash($_REQUEST['orderby']) ) ) . '" />';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reason: We are not processing form information.
		if ( ! empty( $_REQUEST['order'] ) )
		// phpcs:ignore
			echo '<input type="hidden" name="order" value="' . esc_attr( sanitize_text_field( wp_unslash($_REQUEST['order']) ) ) . '" />';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reason: We are not processing form information.
		if ( ! empty( $_REQUEST['post_mime_type'] ) )
		// phpcs:ignore
			echo '<input type="hidden" name="post_mime_type" value="' . esc_attr( sanitize_text_field( wp_unslash($_REQUEST['post_mime_type']) ) ) . '" />';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reason: We are not processing form information.
		if ( ! empty( $_REQUEST['detached'] ) )
		// phpcs:ignore
			echo '<input type="hidden" name="detached" value="' . esc_attr( sanitize_text_field( wp_unslash($_REQUEST['detached']) ) ) . '" />';
		?>
		<p class="search-box">
			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $text ); ?>:</label>
			<input type="search" id="<?php echo esc_attr( $input_date_id ); ?>" name="d" value="<?php echo esc_attr( $input_date_val ); ?>" placeholder="<?php esc_attr_e( 'Search by date', 'check-email' ); ?>" />
			<?php submit_button( $text, '', '', false, array( 'id' => 'search-submit' ) ); 
			?>
		</p>
		<?php
	}

	


	

    public function get_error_initiator($initiator) {

		$initiator = (array) json_decode( $initiator, true );

		if ( empty( $initiator['file'] ) ) {
			return '';
		}
        return $initiator['file'];
	}
	
}
