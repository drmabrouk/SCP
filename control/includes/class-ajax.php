<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Control_Ajax {

	public function __construct() {
		// Private actions (Logged-in only)
		$private_actions = array(
			'logout', 'add_user', 'save_user', 'delete_user', 'save_settings',
			'undo_activity', 'toggle_user_restriction', 'export_data', 'import_data',
			'create_backup', 'restore_backup'
		);

		foreach ( $private_actions as $action ) {
			add_action( 'wp_ajax_control_' . $action, array( $this, $action ) );
		}

		// Public actions (Non-logged-in)
		$public_actions = array( 'login', 'register' );
		foreach ( $public_actions as $action ) {
			add_action( 'wp_ajax_control_' . $action, array( $this, $action ) );
			add_action( 'wp_ajax_nopriv_control_' . $action, array( $this, $action ) );
		}
	}

	public function login() {
		check_ajax_referer( 'control_nonce', 'nonce' );

		$phone = sanitize_text_field( $_POST['phone'] ?? '' );
		$password = $_POST['password'] ?? '';

		$result = Control_Auth::login( $phone, $password );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		} elseif ( $result ) {
			Control_Audit::log('login', "User with phone $phone logged in");
			wp_send_json_success();
		} else {
			Control_Audit::log('failed_login', "Failed login attempt for phone $phone");
			wp_send_json_error( array( 'message' => __( 'بيانات الدخول غير صحيحة.', 'control' ) ) );
		}
	}

	public function register() {
		check_ajax_referer( 'control_nonce', 'nonce' );

		$data = array(
			'first_name' => sanitize_text_field( $_POST['first_name'] ),
			'last_name'  => sanitize_text_field( $_POST['last_name'] ),
			'phone'      => sanitize_text_field( $_POST['phone'] ),
			'email'      => sanitize_email( $_POST['email'] ),
			'password'   => $_POST['password'],
		);

		if ( empty($data['first_name']) || empty($data['last_name']) || empty($data['phone']) || empty($data['password']) ) {
			wp_send_json_error( array( 'message' => __( 'يرجى ملء جميع الحقول المطلوبة.', 'control' ) ) );
		}

		if ( ! preg_match('/^\+(20|971|966|965|974|973|968)[0-9]{7,12}$/', $data['phone']) ) {
			wp_send_json_error( array( 'message' => __( 'تنسيق رقم الهاتف غير صالح لهذه الدولة.', 'control' ) ) );
		}

		if ( strlen($data['password']) < 8 ) {
			wp_send_json_error( array( 'message' => __( 'كلمة المرور يجب أن لا تقل عن 8 أحرف.', 'control' ) ) );
		}

		$result = Control_Auth::register_user( $data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		Control_Audit::log('registration', "New user registered: {$data['phone']}");
		wp_send_json_success();
	}

	public function logout() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		Control_Auth::logout();
		wp_send_json_success();
	}

	public function add_user() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$table = $wpdb->prefix . 'control_staff';
		$phone = sanitize_text_field( $_POST['phone'] );

		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE phone = %s", $phone ) );
		if ( $exists ) wp_send_json_error( 'Phone already registered' );

		$data = array(
			'username' => sanitize_text_field( $_POST['username'] ?: $phone ),
			'phone'    => $phone,
			'password' => password_hash( $_POST['password'], PASSWORD_DEFAULT ),
			'name'     => sanitize_text_field( $_POST['name'] ),
			'email'    => sanitize_email( $_POST['email'] ),
			'role'     => sanitize_text_field( $_POST['role'] ),

			'profile_image' => sanitize_text_field( $_POST['profile_image'] ?? '' ),
			'gender'        => sanitize_text_field( $_POST['gender'] ?? '' ),
			'degree'        => sanitize_text_field( $_POST['degree'] ?? '' ),
			'institution'   => sanitize_text_field( $_POST['institution'] ?? '' ),
			'graduation_year' => sanitize_text_field( $_POST['graduation_year'] ?? '' ),
			'employer_name' => sanitize_text_field( $_POST['employer_name'] ?? '' ),
			'employer_country' => sanitize_text_field( $_POST['employer_country'] ?? '' ),
			'work_phone'    => sanitize_text_field( $_POST['work_phone'] ?? '' ),
			'work_email'    => sanitize_email( $_POST['work_email'] ?? '' ),
			'org_logo'      => sanitize_text_field( $_POST['org_logo'] ?? '' ),
			'job_title'     => sanitize_text_field( $_POST['job_title'] ?? '' ),
		);

		$wpdb->insert( $table, $data );
		Control_Audit::log('add_user', "User $phone added by admin");
		wp_send_json_success();
	}

	public function save_user() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$id = intval( $_POST['id'] );
		$phone = sanitize_text_field( $_POST['phone'] );

		$data = array(
			'username' => sanitize_text_field( $_POST['username'] ),
			'phone'    => $phone,
			'name'     => sanitize_text_field( $_POST['name'] ),
			'email'    => sanitize_email( $_POST['email'] ),
			'role'     => sanitize_text_field( $_POST['role'] ),

			'profile_image' => sanitize_text_field( $_POST['profile_image'] ?? '' ),
			'gender'        => sanitize_text_field( $_POST['gender'] ?? '' ),
			'degree'        => sanitize_text_field( $_POST['degree'] ?? '' ),
			'institution'   => sanitize_text_field( $_POST['institution'] ?? '' ),
			'graduation_year' => sanitize_text_field( $_POST['graduation_year'] ?? '' ),
			'employer_name' => sanitize_text_field( $_POST['employer_name'] ?? '' ),
			'employer_country' => sanitize_text_field( $_POST['employer_country'] ?? '' ),
			'work_phone'    => sanitize_text_field( $_POST['work_phone'] ?? '' ),
			'work_email'    => sanitize_email( $_POST['work_email'] ?? '' ),
			'org_logo'      => sanitize_text_field( $_POST['org_logo'] ?? '' ),
			'job_title'     => sanitize_text_field( $_POST['job_title'] ?? '' ),
		);

		if ( ! empty( $_POST['password'] ) ) {
			$data['password'] = password_hash( $_POST['password'], PASSWORD_DEFAULT );
		}

		$wpdb->update( $wpdb->prefix . 'control_staff', $data, array( 'id' => $id ) );
		Control_Audit::log('edit_user', "User $phone updated by admin");
		wp_send_json_success();
	}

	public function delete_user() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		$id = intval( $_POST['id'] );
		global $wpdb;
		$user = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}control_staff WHERE id = %d", $id ) );
		if ( $user && ($user->username === 'admin' || $user->phone === '1234567890')) wp_send_json_error( 'Cannot delete admin' );

		if ( $user ) {
			Control_Audit::log( 'delete_user', sprintf(__('حذف المستخدم: %s', 'control'), $user->name), $user );
			$wpdb->delete( $wpdb->prefix . 'control_staff', array( 'id' => $id ) );
			wp_send_json_success();
		} else {
			wp_send_json_error( 'User not found' );
		}
	}

	public function toggle_user_restriction() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$id = intval( $_POST['id'] );
		$user = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}control_staff WHERE id = %d", $id ) );
		if ( ! $user ) wp_send_json_error( 'User not found' );
		if ( $user->username === 'admin' || $user->phone === '1234567890' ) wp_send_json_error( 'Cannot restrict admin' );

		$new_status = $user->is_restricted ? 0 : 1;
		$wpdb->update( "{$wpdb->prefix}control_staff", array( 'is_restricted' => $new_status ), array( 'id' => $id ) );

		$action = $new_status ? __('تقييد', 'control') : __('إلغاء تقييد', 'control');
		Control_Audit::log( 'toggle_restriction', sprintf(__('%s حساب المستخدم: %s', 'control'), $action, $user->name) );

		wp_send_json_success();
	}

	public function save_settings() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$table = $wpdb->prefix . 'control_settings';

		foreach ( $_POST as $key => $value ) {
			if ( strpos( $key, 'control_' ) === false && $key !== 'action' && $key !== 'nonce' ) {
				$wpdb->replace( $table, array(
					'setting_key'   => sanitize_key( $key ),
					'setting_value' => sanitize_text_field( $value )
				) );
			}
		}

		wp_send_json_success();
	}

	public function export_data() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		$type = sanitize_text_field( $_POST['type'] ?? 'users' );
		global $wpdb;
		$data = array();
		$filename = "control_{$type}_export_" . date('Y-m-d') . ".csv";

		if ( $type === 'users' ) {
			$data = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}control_staff", ARRAY_A );
		}

		if ( empty($data) ) wp_send_json_error( 'No data found' );

		ob_start();
		$df = fopen("php://output", 'w');
		fputcsv($df, array_keys(reset($data)));
		foreach ($data as $row) {
			fputcsv($df, $row);
		}
		fclose($df);
		$csv = ob_get_clean();

		wp_send_json_success( array(
			'csv' => $csv,
			'filename' => $filename
		) );
	}

	public function import_data() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		$type = sanitize_text_field( $_POST['type'] ?? 'users' );
		$csv_data = $_POST['csv_data'] ?? '';

		if ( empty($csv_data) ) wp_send_json_error( 'Empty data' );

		$lines = explode( "\n", str_replace( "\r", "", $csv_data ) );
		$header = str_getcsv( array_shift( $lines ) );

		global $wpdb;
		$count = 0;

		foreach ( $lines as $line ) {
			if ( empty($line) ) continue;
			$row = @array_combine( $header, str_getcsv( $line ) );
			if ( ! $row ) continue;

			if ( $type === 'users' ) {
				$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}control_staff WHERE phone = %s", $row['phone'] ) );
				if ( ! $exists ) {
					$wpdb->insert( "{$wpdb->prefix}control_staff", $row );
					$count++;
				}
			}
		}

		Control_Audit::log('import_data', sprintf(__('استيراد %d سجل من نوع %s', 'control'), $count, $type));
		wp_send_json_success( sprintf(__('تم استيراد %d سجل بنجاح.', 'control'), $count) );
	}

	public function undo_activity() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$log_id = intval( $_POST['log_id'] );
		$log = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}control_activity_logs WHERE id = %d", $log_id));

		if ( ! $log || ! $log->meta_data ) wp_send_json_error( 'No undo data' );

		$data = json_decode( $log->meta_data, true );
		unset($data['id']);

		if ( $log->action_type === 'delete_user' ) {
			$wpdb->insert( "{$wpdb->prefix}control_staff", $data );
			$wpdb->delete( "{$wpdb->prefix}control_activity_logs", array( 'id' => $log_id ) );
			wp_send_json_success();
		}

		wp_send_json_error( 'Cannot undo this action' );
	}

	public function create_backup() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$tables = array( 'control_staff', 'control_settings', 'control_activity_logs' );
		$backup = array();

		foreach ( $tables as $table ) {
			$full_table_name = $wpdb->prefix . $table;
			$backup[$table] = $wpdb->get_results( "SELECT * FROM $full_table_name", ARRAY_A );
		}

		$backup_data = json_encode( $backup );
		$filename = "control_system_backup_" . date('Y-m-d_H-i') . ".json";

		wp_send_json_success( array(
			'json'     => $backup_data,
			'filename' => $filename
		) );
	}

	public function restore_backup() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		$backup_json = $_POST['backup_data'] ?? '';
		if ( empty($backup_json) ) wp_send_json_error( 'No backup data provided' );

		$backup = json_decode( $backup_json, true );
		if ( ! is_array($backup) ) wp_send_json_error( 'Invalid backup format' );

		global $wpdb;
		foreach ( $backup as $table => $rows ) {
			$full_table_name = $wpdb->prefix . $table;

			// Clear current data
			$wpdb->query( "DELETE FROM $full_table_name" );

			// Restore
			foreach ( $rows as $row ) {
				$wpdb->insert( $full_table_name, $row );
			}
		}

		Control_Audit::log('restore_backup', 'System restored from a backup file');
		wp_send_json_success( 'System restored successfully' );
	}
}

new Control_Ajax();
