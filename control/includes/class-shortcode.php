<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Control_Shortcode {

	public function __construct() {
		add_shortcode( 'control_system', array( $this, 'render_dashboard' ) );
	}

	public function render_dashboard() {
		ob_start();

		if ( ! Control_Auth::is_logged_in() ) {
			include CONTROL_PATH . 'templates/login.php';
			return ob_get_clean();
		}

		$view = isset( $_GET['control_view'] ) ? sanitize_text_field( $_GET['control_view'] ) : 'dashboard';
		$is_admin = Control_Auth::is_admin();

		include CONTROL_PATH . 'templates/header.php';

		switch ( $view ) {
			case 'users':
				if ( ! $is_admin ) {
					echo '<p>' . __( 'ليس لديك صلاحية للوصول لهذه الصفحة.', 'control' ) . '</p>';
				} else {
					include CONTROL_PATH . 'templates/users.php';
				}
				break;
			case 'settings':
				if ( ! $is_admin ) {
					echo '<p>' . __( 'ليس لديك صلاحية للوصول لهذه الصفحة.', 'control' ) . '</p>';
				} else {
					include CONTROL_PATH . 'templates/settings.php';
				}
				break;
			default:
				include CONTROL_PATH . 'templates/dashboard-home.php';
				break;
		}

		include CONTROL_PATH . 'templates/footer.php';
		return ob_get_clean();
	}
}

new Control_Shortcode();
