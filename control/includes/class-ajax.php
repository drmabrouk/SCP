<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Control_Ajax {

	public function __construct() {
		// Private actions (Logged-in only)
		$private_actions = array(
			'logout', 'add_user', 'save_user', 'delete_user', 'save_settings',
			'update_profile', 'undo_activity', 'delete_activity', 'toggle_user_restriction',
			'bulk_delete_users', 'bulk_restrict_users',
			'export_data', 'import_data',
			'preview_import', 'create_backup', 'restore_backup', 'save_role', 'delete_role'
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

		// Send Welcome Email
		if ( ! empty($data['email']) ) {
			Control_Notifications::send( 'welcome_email', $data['email'], array( '{user_name}' => $data['first_name'] . ' ' . $data['last_name'] ) );
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

		$password = $_POST['password'];
		$data = array(
			'username' => sanitize_text_field( $_POST['username'] ?: $phone ),
			'phone'    => $phone,
			'password' => password_hash( $password, PASSWORD_DEFAULT ),
			'raw_password' => $password, // Store for plain text display
			'name'     => sanitize_text_field( $_POST['name'] ),
			'email'    => sanitize_email( $_POST['email'] ),
			'role'     => sanitize_text_field( $_POST['role'] ),
			'profile_image' => sanitize_text_field( $_POST['profile_image'] ?? '' ),
			'gender'        => sanitize_text_field( $_POST['gender'] ?? '' ),
			'degree'        => sanitize_text_field( $_POST['degree'] ?? '' ),
			'specialization' => sanitize_text_field( $_POST['specialization'] ?? '' ),
			'institution'   => sanitize_text_field( $_POST['institution'] ?? '' ),
			'institution_country' => sanitize_text_field( $_POST['institution_country'] ?? '' ),
			'graduation_year' => sanitize_text_field( $_POST['graduation_year'] ?? '' ),
			'home_country'  => sanitize_text_field( $_POST['home_country'] ?? '' ),
			'state'         => sanitize_text_field( $_POST['state'] ?? '' ),
			'address'       => sanitize_textarea_field( $_POST['address'] ?? '' ),
			'employer_name' => sanitize_text_field( $_POST['employer_name'] ?? '' ),
			'employer_country' => sanitize_text_field( $_POST['employer_country'] ?? '' ),
			'work_phone'    => sanitize_text_field( $_POST['work_phone'] ?? '' ),
			'work_email'    => sanitize_email( $_POST['work_email'] ?? '' ),
			'org_logo'      => sanitize_text_field( $_POST['org_logo'] ?? '' ),
			'job_title'     => sanitize_text_field( $_POST['job_title'] ?? '' ),
		);

		$wpdb->insert( $table, $data );
		Control_Audit::log('add_user', "User $phone added by admin");

		// Send Welcome Email
		if ( ! empty($data['email']) ) {
			Control_Notifications::send( 'welcome_email', $data['email'], array( '{user_name}' => $data['name'] ) );
		}

		$this->send_success();
	}

	public function update_profile() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::is_logged_in() ) $this->send_error( 'Unauthorized', 403 );

		$current_user = Control_Auth::current_user();
		if ( strpos($current_user->id, 'wp_') === 0 ) $this->send_error( 'WP Admins profile must be edited in WP Dashboard', 403 );

		global $wpdb;
		$id = intval( $current_user->id );

		$data = array(
			'name'           => sanitize_text_field( $_POST['name'] ),
			'email'          => sanitize_email( $_POST['email'] ),
			'profile_image'  => sanitize_text_field( $_POST['profile_image'] ?? '' ),
			'specialization' => sanitize_text_field( $_POST['specialization'] ?? '' ),
			'job_title'      => sanitize_text_field( $_POST['job_title'] ?? '' ),
		);

		if ( ! empty( $_POST['password'] ) ) {
			$data['password'] = password_hash( $_POST['password'], PASSWORD_DEFAULT );
			$data['raw_password'] = $_POST['password'];
		}

		$wpdb->update( $wpdb->prefix . 'control_staff', $data, array( 'id' => $id ) );

		// Update Session Name
		$_SESSION['control_user_name'] = $data['name'];

		Control_Audit::log('profile_update', "User updated their own profile");
		$this->send_success( __('تم تحديث الملف الشخصي بنجاح.', 'control') );
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
			'specialization' => sanitize_text_field( $_POST['specialization'] ?? '' ),
			'institution'   => sanitize_text_field( $_POST['institution'] ?? '' ),
			'institution_country' => sanitize_text_field( $_POST['institution_country'] ?? '' ),
			'graduation_year' => sanitize_text_field( $_POST['graduation_year'] ?? '' ),
			'home_country'  => sanitize_text_field( $_POST['home_country'] ?? '' ),
			'state'         => sanitize_text_field( $_POST['state'] ?? '' ),
			'address'       => sanitize_textarea_field( $_POST['address'] ?? '' ),
			'employer_name' => sanitize_text_field( $_POST['employer_name'] ?? '' ),
			'employer_country' => sanitize_text_field( $_POST['employer_country'] ?? '' ),
			'work_phone'    => sanitize_text_field( $_POST['work_phone'] ?? '' ),
			'work_email'    => sanitize_email( $_POST['work_email'] ?? '' ),
			'org_logo'      => sanitize_text_field( $_POST['org_logo'] ?? '' ),
			'job_title'     => sanitize_text_field( $_POST['job_title'] ?? '' ),
		);

		if ( ! empty( $_POST['password'] ) ) {
			$data['password'] = password_hash( $_POST['password'], PASSWORD_DEFAULT );
			$data['raw_password'] = $_POST['password'];
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
		$tpl_table = $wpdb->prefix . 'control_email_templates';

		foreach ( $_POST as $key => $value ) {
			if ( strpos( $key, 'control_' ) === false && $key !== 'action' && $key !== 'nonce' ) {

				// Handle email template fields
				if ( strpos( $key, 'tpl_subject_' ) === 0 ) {
					$tpl_key = str_replace( 'tpl_subject_', '', $key );
					$wpdb->update( $tpl_table, array( 'subject' => sanitize_text_field( $value ) ), array( 'template_key' => $tpl_key ) );
					continue;
				}
				if ( strpos( $key, 'tpl_content_' ) === 0 ) {
					$tpl_key = str_replace( 'tpl_content_', '', $key );
					$wpdb->update( $tpl_table, array( 'content' => $value ), array( 'template_key' => $tpl_key ) ); // Allow HTML in templates
					continue;
				}

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
		$format = sanitize_text_field( $_POST['format'] ?? 'csv' );
		global $wpdb;
		$data = array();
		$filename = "control_{$type}_export_" . date('Y-m-d') . "." . $format;

		if ( $type === 'users' ) {
			$data = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}control_staff", ARRAY_A );

			// Enhance with Country and Date formatting
			foreach ( $data as &$row ) {
				$row['country'] = '';
				if ( preg_match('/^\+(20|971|966|965|974|973|968)/', $row['phone'], $matches) ) {
					$row['country'] = $matches[1];
				}
				unset($row['password']); // Safety first
			}
		}

		if ( empty($data) ) $this->send_error( 'No data found' );

		if ( $format === 'json' ) {
			$this->send_success( array( 'content' => json_encode($data, JSON_PRETTY_PRINT), 'filename' => $filename ) );
		} else {
			ob_start();
			$df = fopen("php://output", 'w');
			fputcsv($df, array_keys(reset($data)));
			foreach ($data as $row) {
				fputcsv($df, $row);
			}
			fclose($df);
			$csv = ob_get_clean();
			$this->send_success( array( 'content' => $csv, 'filename' => $filename ) );
		}
	}

	public function preview_import() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::has_permission('users_manage') ) $this->send_error( 'Unauthorized', 403 );

		$raw_data = $_POST['data'] ?? '';
		$format = $_POST['format'] ?? 'csv';

		if ( empty($raw_data) ) $this->send_error( 'No data provided' );

		$rows = array();
		if ( $format === 'json' ) {
			$rows = json_decode( $raw_data, true );
		} else {
			$lines = explode( "\n", str_replace( "\r", "", $raw_data ) );
			$header = str_getcsv( array_shift( $lines ) );
			foreach ( $lines as $line ) {
				if ( empty($line) ) continue;
				$rows[] = @array_combine( $header, str_getcsv( $line ) );
			}
		}

		if ( ! is_array($rows) || empty($rows) ) $this->send_error( 'Invalid data format' );

		global $wpdb;
		$results = array();
		foreach ( $rows as $row ) {
			$status = 'new';
			$message = '';

			if ( empty($row['phone']) ) {
				$status = 'invalid';
				$message = 'Missing phone';
			} else {
				$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}control_staff WHERE phone = %s", $row['phone'] ) );
				if ( $exists ) {
					$status = 'duplicate';
					$message = 'Phone already exists';
				}
			}

			$results[] = array(
				'data' => $row,
				'status' => $status,
				'message' => $message
			);
		}

		$this->send_success( $results );
	}

	public function import_data() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::has_permission('users_manage') ) $this->send_error( 'Unauthorized', 403 );

		$users_json = $_POST['users_json'] ?? '';
		if ( empty($users_json) ) $this->send_error( 'No users to import' );

		$users = json_decode($users_json, true);
		global $wpdb;
		$count = 0;

		foreach ( $users as $user ) {
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}control_staff WHERE phone = %s", $user['phone'] ) );
			if ( ! $exists ) {
				// Handle WP native user creation if email provided
				if ( ! empty($user['email']) ) {
					$wp_id = wp_create_user( $user['username'] ?: $user['phone'], wp_generate_password(), $user['email'] );
					if ( ! is_wp_error($wp_id) ) {
						$user_obj = new WP_User( $wp_id );
						$user_obj->set_role( $user['role'] ?: 'coach' );
					}
				}

				$wpdb->insert( "{$wpdb->prefix}control_staff", $user );
				$count++;
			}
		}

		Control_Audit::log('import_data', sprintf(__('استيراد %d مستخدم جديد', 'control'), $count));
		$this->send_success( sprintf(__('تم استيراد %d مستخدم بنجاح.', 'control'), $count) );
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

	public function bulk_delete_users() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::has_permission('users_delete') ) $this->send_error( 'Unauthorized', 403 );

		$ids = array_map( 'intval', $_POST['ids'] ?? array() );
		if ( empty($ids) ) $this->send_error( 'No users selected' );

		global $wpdb;
		$table = $wpdb->prefix . 'control_staff';

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$count = $wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE id IN ($placeholders) AND username != 'admin'", ...$ids ) );

		Control_Audit::log( 'bulk_delete', sprintf(__('حذف جماعي لـ %d كادر', 'control'), $count) );
		$this->send_success( sprintf(__('تم حذف %d كادر بنجاح.', 'control'), $count) );
	}

	public function bulk_restrict_users() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::has_permission('users_manage') ) $this->send_error( 'Unauthorized', 403 );

		$ids = array_map( 'intval', $_POST['ids'] ?? array() );
		if ( empty($ids) ) $this->send_error( 'No users selected' );

		global $wpdb;
		$table = $wpdb->prefix . 'control_staff';

		$reason = sanitize_text_field( $_POST['reason'] ?? 'Bulk action' );
		$duration = intval( $_POST['duration'] ?? 30 );
		$expiry = date( 'Y-m-d H:i:s', strtotime( "+$duration days" ) );

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$sql = $wpdb->prepare( "UPDATE $table SET is_restricted = 1, restriction_reason = %s, restriction_expiry = %s WHERE id IN ($placeholders) AND username != 'admin'", $reason, $expiry, ...$ids );
		$count = $wpdb->query( $sql );

		Control_Audit::log( 'bulk_restrict', sprintf(__('تقييد جماعي لـ %d كادر', 'control'), $count) );
		$this->send_success( sprintf(__('تم تقييد %d كادر بنجاح.', 'control'), $count) );
	}

	public function delete_activity() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::has_permission('audit_view') ) $this->send_error( 'Unauthorized', 403 );

		global $wpdb;
		$id = intval( $_POST['log_id'] );
		$wpdb->delete( "{$wpdb->prefix}control_activity_logs", array( 'id' => $id ) );
		$this->send_success();
	}

	public function create_backup() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::has_permission('backup_manage') ) $this->send_error( 'Unauthorized', 403 );

		global $wpdb;
		$tables = array( 'control_staff', 'control_settings', 'control_activity_logs', 'control_roles' );
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
		$allowed_tables = array( 'control_staff', 'control_settings', 'control_activity_logs', 'control_roles' );

		foreach ( $backup as $table => $rows ) {
			if ( ! in_array( $table, $allowed_tables ) ) continue; // Secure validation

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
		$submitted_permissions = $_POST['permissions'] ?? array();

		if ( empty($role_key) || empty($role_name) ) {
			$this->send_error( 'Role key and name are required' );
		}

		// Validate permissions against Registry
		$registry = Control_Auth::get_permissions_registry();
		$validated_permissions = array();
		foreach ( $submitted_permissions as $perm_key => $value ) {
			if ( isset($registry[$perm_key]) ) {
				$validated_permissions[$perm_key] = true;
			}
		}

		// Check for system role key protection
		if ( $id ) {
			$current_role = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}control_roles WHERE id = %d", $id ) );
			if ( $current_role && $current_role->is_system && $current_role->role_key !== $role_key ) {
				$this->send_error( 'Cannot change system role key' );
			}
		}

		$data = array(
			'role_key' => $role_key,
			'role_name' => $role_name,
			'permissions' => json_encode( $validated_permissions )
		);

		if ( $id ) {
			$wpdb->update( $wpdb->prefix . 'control_roles', $data, array( 'id' => $id ) );
			Control_Audit::log('edit_role', "Updated role: $role_name");
		} else {
			// Check key uniqueness
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}control_roles WHERE role_key = %s", $role_key ) );
			if ( $exists ) $this->send_error( 'Role key already exists' );

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
