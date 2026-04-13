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
			'create_backup', 'restore_backup', 'save_role', 'delete_role'
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

	/**
	 * Standardize success response.
	 */
	private function send_success( $data = null ) {
		wp_send_json_success( $data );
	}

	/**
	 * Standardize error response.
	 */
	private function send_error( $message = 'An error occurred', $code = 400 ) {
		wp_send_json_error( array( 'message' => $message, 'code' => $code ) );
	}

	public function login() {
		check_ajax_referer( 'control_nonce', 'nonce' );

		$phone = sanitize_text_field( $_POST['phone'] ?? '' );
		$password = $_POST['password'] ?? '';

		$result = Control_Auth::login( $phone, $password );

		if ( is_wp_error( $result ) ) {
			$this->send_error( $result->get_error_message() );
		} elseif ( $result ) {
			Control_Audit::log('login', "User with phone $phone logged in");
			$this->send_success();
		} else {
			Control_Audit::log('failed_login', "Failed login attempt for phone $phone");
			$this->send_error( __( 'بيانات الدخول غير صحيحة.', 'control' ) );
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
			$this->send_error( __( 'يرجى ملء جميع الحقول المطلوبة.', 'control' ) );
		}

		if ( ! preg_match('/^\+(20|971|966|965|974|973|968)[0-9]{7,12}$/', $data['phone']) ) {
			$this->send_error( __( 'تنسيق رقم الهاتف غير صالح لهذه الدولة.', 'control' ) );
		}

		if ( strlen($data['password']) < 8 ) {
			$this->send_error( __( 'كلمة المرور يجب أن لا تقل عن 8 أحرف.', 'control' ) );
		}

		$result = Control_Auth::register_user( $data );

		if ( is_wp_error( $result ) ) {
			$this->send_error( $result->get_error_message() );
		}

		Control_Audit::log('registration', "New user registered: {$data['phone']}");
		$this->send_success();
	}

	public function logout() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		Control_Auth::logout();
		$this->send_success();
	}

	public function add_user() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::has_permission('users_manage') ) $this->send_error( 'Unauthorized', 403 );

		global $wpdb;
		$table = $wpdb->prefix . 'control_staff';
		$phone = sanitize_text_field( $_POST['phone'] );

		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE phone = %s", $phone ) );
		if ( $exists ) $this->send_error( 'Phone already registered' );

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
		$this->send_success();
	}

	public function save_user() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::has_permission('users_manage') ) $this->send_error( 'Unauthorized', 403 );

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
		$this->send_success();
	}

	public function delete_user() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::has_permission('users_delete') ) $this->send_error( 'Unauthorized', 403 );

		$id = intval( $_POST['id'] );
		global $wpdb;
		$user = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}control_staff WHERE id = %d", $id ) );
		if ( $user && ($user->username === 'admin' || $user->phone === '1234567890')) $this->send_error( 'Cannot delete admin' );

		if ( $user ) {
			Control_Audit::log( 'delete_user', sprintf(__('حذف المستخدم: %s', 'control'), $user->name), $user );
			$wpdb->delete( $wpdb->prefix . 'control_staff', array( 'id' => $id ) );
			$this->send_success();
		} else {
			$this->send_error( 'User not found', 404 );
		}
	}

	public function toggle_user_restriction() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::has_permission('users_manage') ) $this->send_error( 'Unauthorized', 403 );

		global $wpdb;
		$id = intval( $_POST['id'] );
		$user = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}control_staff WHERE id = %d", $id ) );
		if ( ! $user ) $this->send_error( 'User not found', 404 );
		if ( $user->username === 'admin' || $user->phone === '1234567890' ) $this->send_error( 'Cannot restrict admin' );

		$new_status = $user->is_restricted ? 0 : 1;
		$data = array( 'is_restricted' => $new_status );

		if ( $new_status ) {
			$reason = sanitize_text_field( $_POST['reason'] ?? '' );
			$duration = intval( $_POST['duration'] ?? 30 );
			$expiry = date( 'Y-m-d H:i:s', strtotime( "+$duration days" ) );

			$data['restriction_reason'] = $reason;
			$data['restriction_expiry'] = $expiry;
		} else {
			$data['restriction_reason'] = null;
			$data['restriction_expiry'] = null;
		}

		$wpdb->update( "{$wpdb->prefix}control_staff", $data, array( 'id' => $id ) );

		$action = $new_status ? __('تقييد', 'control') : __('إلغاء تقييد', 'control');
		Control_Audit::log( 'toggle_restriction', sprintf(__('%s حساب المستخدم: %s', 'control'), $action, $user->name) );

		$this->send_success();
	}

	public function save_settings() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::has_permission('settings_manage') ) $this->send_error( 'Unauthorized', 403 );

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

		$this->send_success();
	}

	public function export_data() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::has_permission('users_view') ) $this->send_error( 'Unauthorized', 403 );

		$type = sanitize_text_field( $_POST['type'] ?? 'users' );
		global $wpdb;
		$data = array();
		$filename = "control_{$type}_export_" . date('Y-m-d') . ".csv";

		if ( $type === 'users' ) {
			$data = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}control_staff", ARRAY_A );
		}

		if ( empty($data) ) $this->send_error( 'No data found' );

		ob_start();
		$df = fopen("php://output", 'w');
		fputcsv($df, array_keys(reset($data)));
		foreach ($data as $row) {
			fputcsv($df, $row);
		}
		fclose($df);
		$csv = ob_get_clean();

		$this->send_success( array( 'csv' => $csv, 'filename' => $filename ) );
	}

	public function import_data() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::has_permission('users_manage') ) $this->send_error( 'Unauthorized', 403 );

		$type = sanitize_text_field( $_POST['type'] ?? 'users' );
		$csv_data = $_POST['csv_data'] ?? '';

		if ( empty($csv_data) ) $this->send_error( 'Empty data' );

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
		$this->send_success( sprintf(__('تم استيراد %d سجل بنجاح.', 'control'), $count) );
	}

	public function undo_activity() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::has_permission('audit_view') ) $this->send_error( 'Unauthorized', 403 );

		global $wpdb;
		$log_id = intval( $_POST['log_id'] );
		$log = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}control_activity_logs WHERE id = %d", $log_id));

		if ( ! $log || ! $log->meta_data ) $this->send_error( 'No undo data' );

		$data = json_decode( $log->meta_data, true );
		unset($data['id']);

		if ( $log->action_type === 'delete_user' ) {
			$wpdb->insert( "{$wpdb->prefix}control_staff", $data );
			$wpdb->delete( "{$wpdb->prefix}control_activity_logs", array( 'id' => $log_id ) );
			$this->send_success();
		}

		$this->send_error( 'Cannot undo this action' );
	}

	public function create_backup() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::has_permission('backup_manage') ) $this->send_error( 'Unauthorized', 403 );

		global $wpdb;
		$tables = array( 'control_staff', 'control_settings', 'control_activity_logs' );
		$backup = array();

		foreach ( $tables as $table ) {
			$full_table_name = $wpdb->prefix . $table;
			$backup[$table] = $wpdb->get_results( "SELECT * FROM $full_table_name", ARRAY_A );
		}

		$backup_data = json_encode( $backup );
		$filename = "control_system_backup_" . date('Y-m-d_H-i') . ".json";

		$this->send_success( array( 'json' => $backup_data, 'filename' => $filename ) );
	}

	public function restore_backup() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::has_permission('backup_manage') ) $this->send_error( 'Unauthorized', 403 );

		$backup_json = $_POST['backup_data'] ?? '';
		if ( empty($backup_json) ) $this->send_error( 'No backup data provided' );

		$backup = json_decode( $backup_json, true );
		if ( ! is_array($backup) ) $this->send_error( 'Invalid backup format' );

		global $wpdb;
		foreach ( $backup as $table => $rows ) {
			$full_table_name = $wpdb->prefix . $table;
			$wpdb->query( "DELETE FROM $full_table_name" );
			foreach ( $rows as $row ) {
				$wpdb->insert( $full_table_name, $row );
			}
		}

		Control_Audit::log('restore_backup', 'System restored from a backup file');
		$this->send_success( 'System restored successfully' );
	}

	public function save_role() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::has_permission('roles_manage') ) $this->send_error( 'Unauthorized', 403 );

		global $wpdb;
		$id = intval( $_POST['id'] ?? 0 );
		$role_key = sanitize_key( $_POST['role_key'] );
		$role_name = sanitize_text_field( $_POST['role_name'] );
		$permissions = $_POST['permissions'] ?? array();

		$data = array(
			'role_key' => $role_key,
			'role_name' => $role_name,
			'permissions' => json_encode( $permissions )
		);

		if ( $id ) {
			$wpdb->update( $wpdb->prefix . 'control_roles', $data, array( 'id' => $id ) );
			Control_Audit::log('edit_role', "Updated role: $role_name");
		} else {
			$wpdb->insert( $wpdb->prefix . 'control_roles', $data );
			Control_Audit::log('add_role', "Added role: $role_name");
		}

		// Re-sync WP roles
		Control_Auth::sync_roles();
		$this->send_success();
	}

	public function delete_role() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::has_permission('roles_manage') ) $this->send_error( 'Unauthorized', 403 );

		global $wpdb;
		$id = intval( $_POST['id'] );
		$role = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}control_roles WHERE id = %d", $id ) );

		if ( ! $role ) $this->send_error( 'Role not found' );
		if ( $role->is_system ) $this->send_error( 'Cannot delete system roles' );

		$wpdb->delete( "{$wpdb->prefix}control_roles", array( 'id' => $id ) );
		Control_Audit::log('delete_role', "Deleted role: {$role->role_name}");

		// Re-sync WP roles
		Control_Auth::sync_roles();
		$this->send_success();
	}
}

new Control_Ajax();
