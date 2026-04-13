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
				if ( ! Control_Auth::has_permission('users_view') ) {
					echo '<p>' . __( 'ليس لديك صلاحية للوصول لهذه الصفحة.', 'control' ) . '</p>';
				} else {
					include CONTROL_PATH . 'templates/users.php';
				}
				break;
			case 'roles':
				if ( ! Control_Auth::has_permission('roles_manage') ) {
					echo '<p>' . __( 'ليس لديك صلاحية للوصول لهذه الصفحة.', 'control' ) . '</p>';
				} else {
					include CONTROL_PATH . 'templates/roles.php';
				}
				break;
			case 'settings':
				if ( ! Control_Auth::has_permission('settings_manage') ) {
					echo '<p>' . __( 'ليس لديك صلاحية للوصول لهذه الصفحة.', 'control' ) . '</p>';
				} else {
					include CONTROL_PATH . 'templates/settings.php';
				}
				break;
			default:
				if ( ! Control_Auth::has_permission('dashboard') ) {
					echo '<p>' . __( 'أهلاً بك في نظام كنترول. ليس لديك صلاحية لعرض لوحة المعلومات.', 'control' ) . '</p>';
				} else {
					include CONTROL_PATH . 'templates/dashboard-home.php';
				}
				break;
		}

		include CONTROL_PATH . 'templates/footer.php';
		return ob_get_clean();
	}
}

new Control_Shortcode();
