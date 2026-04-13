<?php
/**
 * Plugin Name: Control
 * Description: Dedicated system for managing residential apartments, hotel apartments and hospitality units.
 * Version: 1.0.0
 * Author: Control Team
 * Text Domain: control
 * Domain Path: /languages
 * Official Email: info@control.online
 * Official Website: https://control.online
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define constants
define( 'CONTROL_VERSION', time() ); // Use time() to force immediate file updates by bypassing cache
define( 'CONTROL_PATH', plugin_dir_path( __FILE__ ) );
define( 'CONTROL_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main Class
 */
class Control_System {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->define_constants();
		$this->includes();
		$this->init_hooks();
	}

	private function define_constants() {
		// Already defined above for now, but can move more here if needed.
	}

	private function includes() {
		// Module classes
		require_once CONTROL_PATH . 'includes/class-database.php';
		require_once CONTROL_PATH . 'includes/class-geo.php';
		require_once CONTROL_PATH . 'includes/class-auth.php';
		require_once CONTROL_PATH . 'includes/class-users.php';
		require_once CONTROL_PATH . 'includes/class-properties.php';
		require_once CONTROL_PATH . 'includes/class-investments.php';
		require_once CONTROL_PATH . 'includes/class-audit.php';
		require_once CONTROL_PATH . 'includes/class-pwa.php';

		// Infrastructure
		require_once CONTROL_PATH . 'includes/class-shortcode.php';
		require_once CONTROL_PATH . 'includes/class-ajax.php';
	}

	private function init_hooks() {
		register_activation_hook( __FILE__, array( 'Control_Database', 'create_tables' ) );
		add_action( 'init', array( 'Control_Auth', 'init' ) );
		add_filter( 'cron_schedules', array( $this, 'add_monthly_cron_schedule' ) );
		add_action( 'init', array( $this, 'schedule_monthly_release' ) );
		add_action( 'init', array( 'Control_PWA', 'init' ) );
		add_action( 'init', array( $this, 'send_nocache_headers' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_head', array( $this, 'add_viewport_meta' ) );
	}

	public function add_viewport_meta() {
		echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">';
	}

	public function send_nocache_headers() {
		if ( (isset( $_GET['page'] ) && strpos( $_GET['page'], 'control' ) !== false) || isset( $_GET['control_view'] ) ) {
			nocache_headers();
		}
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $_REQUEST['action'] ) && strpos( $_REQUEST['action'], 'control_' ) === 0 ) {
			nocache_headers();
		}
	}

	public function enqueue_assets() {
		wp_enqueue_media();
		wp_enqueue_style( 'dashicons' );

		// Enqueue Rubik font from Google Fonts
		wp_enqueue_style( 'control-font-rubik', 'https://fonts.googleapis.com/css2?family=Rubik:wght@400;600;700;800&display=swap', array(), CONTROL_VERSION );

		wp_enqueue_style( 'control-rtl-style', CONTROL_URL . 'assets/css/style-rtl.css', array( 'control-font-rubik' ), CONTROL_VERSION );
		wp_enqueue_style( 'control-print-style', CONTROL_URL . 'assets/css/print.css', array(), CONTROL_VERSION, 'print' );

		// Enqueue html2pdf for bulk export
		wp_enqueue_script( 'html2pdf', 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js', array(), '0.10.1', true );

		wp_enqueue_script( 'control-scripts', CONTROL_URL . 'assets/js/scripts.js', array( 'jquery' ), CONTROL_VERSION, true );

		wp_localize_script( 'control-scripts', 'control_ajax', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'control_nonce' ),
			'geo_data' => Control_Geo::get_data(),
		) );
	}

	public function add_monthly_cron_schedule( $schedules ) {
		$schedules['monthly'] = array(
			'interval' => 2635200, // 30.5 days approx
			'display'  => __( 'Once Monthly', 'control' )
		);
		return $schedules;
	}

	public function schedule_monthly_release() {
		if ( ! wp_next_scheduled( 'control_monthly_profit_release' ) ) {
			// Schedule for the last day of the month
			wp_schedule_event( strtotime( 'last day of this month 23:59:00' ), 'monthly', 'control_monthly_profit_release' );
		}
		add_action( 'control_monthly_profit_release', array( 'Control_Investments', 'release_monthly_profits' ) );
	}
}

function Control() {
	return Control_System::get_instance();
}

// Kick off the plugin
Control();
