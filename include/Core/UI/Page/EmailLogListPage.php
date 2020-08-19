<?php namespace EmailLog\Core\UI\Page;

use EmailLog\Core\DB\CheckEmailTableManager;
use EmailLog\Core\UI\ListTable\EmailLogListTable;

/**
 * Log List Page.
 */
class EmailLogListPage extends CheckEmailBasePage {
	/**
	 * @var LogListTable
	 */
	protected $log_list_table;

	/**
	 * Page slug.
	 */
	const PAGE_SLUG = 'check-email-logs';

	/**
	 * Nonce Field.
	 */
	const LOG_LIST_ACTION_NONCE_FIELD = 'el-log-list-nonce-field';

	/**
	 * Nonce name.
	 */
	const LOG_LIST_ACTION_NONCE = 'el-log-list-nonce';

	/**
	 * Setup hooks.
	 */
	public function load() {
		parent::load();

		add_filter( 'set-screen-option', array( $this, 'save_screen_options' ), 10, 3 );

		add_action( 'admin_enqueue_scripts', array( $this, 'load_view_logs_assets' ) );
	}
        
	public function register_page() {
		add_menu_page(
			__( 'Check Email', 'check-email' ),
			__( 'Check Email', 'check-email' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' ),
			'dashicons-email-alt',
			26
		);

		$this->page = add_submenu_page(
			self::PAGE_SLUG,
			__( 'View Logs', 'check-email'),
			__( 'View Logs', 'check-email'),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);

		add_action( "load-{$this->page}", array( $this, 'load_page' ) );

		do_action( 'el_load_log_list_page', $this->page );
	}

	public function render_page() {

		add_thickbox();

		$this->log_list_table->prepare_items();
		?>
		<div class="wrap">
			<h2><?php _e( 'Email Logs', 'check-email' ); ?></h2>
			<?php settings_errors(); ?>

			<form id="email-logs-list" method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE_SLUG ); ?>">
				<?php $this->log_list_table->search_box( __( 'Search Logs', 'check-email' ), 'search_id' ); ?>

				<?php
				// Disable the output of referrer hidden field, since it will be generated by the log list table.
				wp_nonce_field( self::LOG_LIST_ACTION_NONCE, self::LOG_LIST_ACTION_NONCE_FIELD, false );
				$this->log_list_table->display();
				?>
			</form>
		</div>
		<?php
	}

	public function load_page() {

		// Add screen options
		$this->get_screen()->add_option(
			'per_page',
			array(
				'label'   => __( 'Entries per page', 'check-email' ),
				'default' => 20,
				'option'  => 'per_page',
			)
		);

		$this->log_list_table = new EmailLogListTable( $this );
	}

	public function get_per_page() {
		$screen = get_current_screen();
		$option = $screen->get_option( 'per_page', 'option' );

		$per_page = get_user_meta( get_current_user_id(), $option, true );

		if ( empty( $per_page ) || $per_page < 1 ) {
			$per_page = $screen->get_option( 'per_page', 'default' );
		}

		return $per_page;
	}

	public function get_nonce_args() {
		return array(
			self::LOG_LIST_ACTION_NONCE_FIELD => wp_create_nonce( self::LOG_LIST_ACTION_NONCE ),
		);
	}

	public function get_table_manager() {
		$email_log = check_email();

		return $email_log->table_manager;
	}

	public function save_screen_options( $status, $option, $value ) {
		if ( 'per_page' == $option ) {
			return $value;
		} else {
			return $status;
		}
	}

	public function load_view_logs_assets( $hook ) {

		$email_log      = check_email();
		$plugin_dir_url = plugin_dir_url( $email_log->get_plugin_file() );

		wp_register_style( 'jquery-ui-css', $plugin_dir_url . 'assets/vendor/jquery-ui/themes/base/jquery-ui.min.css', array(), '1.12.1' );
		wp_enqueue_style( 'el-view-logs-css', $plugin_dir_url . 'assets/css/admin/view-logs.css', array( 'jquery-ui-css' ), $email_log->get_version() );

		wp_register_script( 'jquery-ui', $plugin_dir_url . 'assets/vendor/jquery-ui/jquery-ui.min.js', array( 'jquery' ), '1.12.1', true );
		wp_register_script( 'insertionQ', $plugin_dir_url . 'assets/vendor/insertion-query/insQ.min.js', array( 'jquery' ), '1.0.4', true );

		wp_enqueue_script( 'el-view-logs', $plugin_dir_url . 'assets/js/admin/view-logs.js', array( 'insertionQ', 'jquery-ui', 'jquery-ui-datepicker', 'jquery-ui-tooltip' ), $email_log->get_version(), true );
	}
}
