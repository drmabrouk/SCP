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
			'preview_import', 'create_backup', 'restore_backup',
			'export_user_package', 'bulk_delete_all_users', 'system_data_reset',
			'save_role', 'delete_role',
			'save_policy', 'delete_policy'
		);

		foreach ( $private_actions as $action ) {
			add_action( 'wp_ajax_control_' . $action, array( $this, $action ) );
		}

		// Public actions (Non-logged-in)
		$public_actions = array( 'login', 'register', 'forgot_password', 'send_otp', 'verify_otp' );
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

		global $wpdb;
		$login_enabled = $wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}control_settings WHERE setting_key = 'auth_login_enabled'");
		if ($login_enabled === '0') {
			$this->send_error(__('نعتذر، تسجيل الدخول معطل حالياً لأعمال الصيانة.', 'control'));
		}

		$phone = sanitize_text_field( $_POST['phone'] ?? '' );
		$password = $_POST['password'] ?? '';

		$result = Control_Auth::login( $phone, $password );

		if ( is_wp_error( $result ) ) {
			$this->send_error( $result->get_error_message() );
		} elseif ( $result ) {
			Control_Audit::log('login', "User with phone $phone logged in");
			$this->send_success( __('تم تسجيل الدخول بنجاح. جاري التحويل...', 'control') );
		} else {
			Control_Audit::log('failed_login', "Failed login attempt for phone $phone");
			$this->send_error( __( 'بيانات الدخول غير صحيحة.', 'control' ) );
		}
	}

	public function register() {
		check_ajax_referer( 'control_nonce', 'nonce' );

		global $wpdb;
		$reg_enabled = $wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}control_settings WHERE setting_key = 'auth_registration_enabled'");
		if ($reg_enabled === '0') {
			$this->send_error(__('نعتذر، التسجيل مغلق حالياً بقرار إداري.', 'control'));
		}

		$reg_fields_json = $wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}control_settings WHERE setting_key = 'auth_registration_fields'");
		$reg_fields = json_decode($reg_fields_json, true) ?: array();

		$data = array();
		foreach ($reg_fields as $field) {
			// Strict synchronization: Ignore fields disabled in settings
			if (isset($field['enabled']) && ($field['enabled'] === false || $field['enabled'] === 'false' || $field['enabled'] === 0)) {
				continue;
			}

			$val = $_POST[$field['id']] ?? '';
			if ($field['id'] === 'email') $val = sanitize_email($val);
			else $val = sanitize_text_field($val);

			if (($field['required'] ?? true) && empty($val)) {
				$this->send_error(sprintf(__('الحقل (%s) مطلوب.', 'control'), $field['label']));
			}
			$data[$field['id']] = $val;
		}

		// Ensure core fields for registration logic even if not in dynamic config (should not happen if config is correct)
		if (empty($data['phone']) || empty($data['password'])) {
			$this->send_error(__('بيانات التسجيل الأساسية ناقصة.', 'control'));
		}

		// OTP Verification Guard
		if ( ! empty($data['email']) ) {
			$is_verified = $wpdb->get_var($wpdb->prepare(
				"SELECT is_verified FROM {$wpdb->prefix}control_otps WHERE email = %s AND is_verified = 1 ORDER BY id DESC LIMIT 1",
				$data['email']
			));
			if (!$is_verified) {
				$this->send_error(__('يرجى التحقق من بريدك الإلكتروني أولاً.', 'control'));
			}
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
		$this->send_success( __('تم إنشاء الحساب بنجاح. جاري تسجيل دخولك...', 'control') );
	}

	public function send_otp() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		global $wpdb;

		$email = sanitize_email( $_POST['email'] ?? '' );
		if ( ! is_email( $email ) ) {
			$this->send_error( __('البريد الإلكتروني غير صحيح.', 'control') );
		}

		// Cooldown check (60 seconds)
		$last_otp = $wpdb->get_row($wpdb->prepare(
			"SELECT created_at FROM {$wpdb->prefix}control_otps WHERE email = %s ORDER BY created_at DESC LIMIT 1",
			$email
		));
		if ($last_otp && (time() - strtotime($last_otp->created_at) < 60)) {
			$this->send_error(__('يرجى الانتظار دقيقة قبل طلب رمز جديد.', 'control'));
		}

		$otp = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
		$expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

		// Invalidate previous unverified OTPs for this email
		$wpdb->update("{$wpdb->prefix}control_otps", array('expiry' => current_time('mysql')), array('email' => $email, 'is_verified' => 0));

		$wpdb->insert("{$wpdb->prefix}control_otps", array(
			'email' => $email,
			'otp' => $otp,
			'expiry' => $expiry
		));

		$sent = Control_Notifications::send('email_verification_otp', $email, array('{otp_code}' => $otp));

		if ($sent) {
			$this->send_success(__('تم إرسال رمز التحقق إلى بريدك الإلكتروني.', 'control'));
		} else {
			$this->send_error(__('فشل إرسال البريد الإلكتروني. يرجى التأكد من إعدادات SMTP.', 'control'));
		}
	}

	public function verify_otp() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		global $wpdb;

		$email = sanitize_email( $_POST['email'] ?? '' );
		$otp = sanitize_text_field( $_POST['otp'] ?? '' );

		$record = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}control_otps WHERE email = %s AND otp = %s AND is_verified = 0 AND expiry > NOW() ORDER BY id DESC LIMIT 1",
			$email, $otp
		));

		if ($record) {
			$wpdb->update("{$wpdb->prefix}control_otps", array('is_verified' => 1), array('id' => $record->id));
			$this->send_success(__('تم التحقق من البريد الإلكتروني بنجاح.', 'control'));
		} else {
			$this->send_error(__('رمز التحقق غير صحيح أو انتهت صلاحيته.', 'control'));
		}
	}

	public function forgot_password() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		$phone = sanitize_text_field( $_POST['phone'] ?? '' );

		global $wpdb;
		$user = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}control_staff WHERE phone = %s", $phone ) );

		if ( ! $user ) {
			$this->send_error( __('رقم الهاتف غير مسجل لدينا.', 'control') );
		}

		// In a real scenario, send OTP/SMS. For now, we'll log it and return a success message.
		Control_Audit::log('forgot_password_request', "Password reset requested for phone: $phone");

		$this->send_success( __('تم استلام طلبك. يرجى مراجعة رسائل الهاتف أو التواصل مع الإدارة للحصول على كلمة مرور جديدة.', 'control') );
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
			'first_name' => sanitize_text_field( $_POST['first_name'] ),
			'last_name'  => sanitize_text_field( $_POST['last_name'] ),
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

		// Sync with WordPress native user if email provided
		if ( ! empty( $data['email'] ) ) {
			wp_insert_user( array(
				'user_login' => $data['username'],
				'user_pass'  => $password,
				'user_email' => $data['email'],
				'first_name' => $data['first_name'],
				'last_name'  => $data['last_name'],
				'role'       => $data['role']
			) );
		}

		Control_Audit::log('add_user', "User $phone added by admin");

		// Send Welcome Email
		if ( ! empty($data['email']) ) {
			Control_Notifications::send( 'welcome_email', $data['email'], array( '{user_name}' => $data['first_name'] . ' ' . $data['last_name'] ) );
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
			'first_name'     => sanitize_text_field( $_POST['first_name'] ),
			'last_name'      => sanitize_text_field( $_POST['last_name'] ),
			'email'          => sanitize_email( $_POST['email'] ),
			'username'       => sanitize_text_field( $_POST['username'] ),
			'profile_image'  => sanitize_text_field( $_POST['profile_image'] ?? '' ),
			'gender'         => sanitize_text_field( $_POST['gender'] ?? '' ),
			'degree'         => sanitize_text_field( $_POST['degree'] ?? '' ),
			'specialization' => sanitize_text_field( $_POST['specialization'] ?? '' ),
			'institution'    => sanitize_text_field( $_POST['institution'] ?? '' ),
			'employer_name'  => sanitize_text_field( $_POST['employer_name'] ?? '' ),
			'job_title'      => sanitize_text_field( $_POST['job_title'] ?? '' ),
			'work_email'     => sanitize_email( $_POST['work_email'] ?? '' ),
		);

		if ( ! empty( $_POST['password'] ) ) {
			$data['password'] = password_hash( $_POST['password'], PASSWORD_DEFAULT );
			$data['raw_password'] = $_POST['password'];
		}

		$wpdb->update( $wpdb->prefix . 'control_staff', $data, array( 'id' => $id ) );

		// Sync with WordPress native user if exists
		if ( ! empty( $data['email'] ) ) {
			$wp_user = get_user_by( 'login', $data['username'] ) ?: get_user_by( 'email', $data['email'] );
			if ( $wp_user ) {
				$wp_update_data = array(
					'ID'         => $wp_user->ID,
					'user_email' => $data['email'],
					'first_name' => $data['first_name'],
					'last_name'  => $data['last_name'],
				);
				if ( ! empty( $_POST['password'] ) ) {
					$wp_update_data['user_pass'] = $_POST['password'];
				}
				wp_update_user( $wp_update_data );
			}
		}

		// Update Session Name
		$_SESSION['control_user_first_name'] = $data['first_name'];
		$_SESSION['control_user_last_name']  = $data['last_name'];

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
			'first_name' => sanitize_text_field( $_POST['first_name'] ),
			'last_name'  => sanitize_text_field( $_POST['last_name'] ),
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

		// Sync with WordPress native user if exists
		if ( ! empty( $data['email'] ) ) {
			$wp_user = get_user_by( 'login', $data['username'] ) ?: get_user_by( 'email', $data['email'] );
			if ( $wp_user ) {
				$wp_update_data = array(
					'ID'         => $wp_user->ID,
					'user_email' => $data['email'],
					'first_name' => $data['first_name'],
					'last_name'  => $data['last_name'],
					'role'       => $data['role']
				);
				if ( ! empty( $_POST['password'] ) ) {
					$wp_update_data['user_pass'] = $_POST['password'];
				}
				wp_update_user( $wp_update_data );
			}
		}

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
			Control_Audit::log( 'delete_user', sprintf(__('حذف المستخدم: %s %s', 'control'), $user->first_name, $user->last_name), $user );
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
		Control_Audit::log( 'toggle_restriction', sprintf(__('%s حساب المستخدم: %s %s', 'control'), $action, $user->first_name, $user->last_name) );

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

				$value = wp_unslash( $value );

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

				// Selective Sanitization to prevent data corruption (JSON/HTML)
				$sanitized_value = ( $key === 'auth_registration_fields' || strpos($key, 'policies_') !== false ) ? $value : sanitize_text_field( $value );

				$wpdb->replace( $table, array(
					'setting_key'   => sanitize_key( $key ),
					'setting_value' => $sanitized_value
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
				unset($row['password']);
				unset($row['raw_password']); // Secure exports
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
					$wp_id = wp_insert_user( array(
						'user_login' => $user['username'] ?: $user['phone'],
						'user_pass'  => wp_generate_password(),
						'user_email' => $user['email'],
						'first_name' => $user['first_name'] ?? '',
						'last_name'  => $user['last_name'] ?? '',
						'role'       => $user['role'] ?: 'coach'
					) );
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
		$tables = array( 'control_staff', 'control_settings', 'control_activity_logs', 'control_roles', 'control_email_templates' );
		$backup = array(
			'metadata' => array(
				'version' => '2.0.0',
				'timestamp' => current_time('mysql'),
				'site_url' => site_url()
			),
			'data' => array()
		);

		foreach ( $tables as $table ) {
			$full_table_name = $wpdb->prefix . $table;
			$backup['data'][$table] = $wpdb->get_results( "SELECT * FROM $full_table_name", ARRAY_A );
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
		$allowed_tables = array( 'control_staff', 'control_settings', 'control_activity_logs', 'control_roles', 'control_email_templates' );

		// Handle legacy format (v1.0) and new format (v2.0)
		$data_to_restore = isset($backup['data']) ? $backup['data'] : $backup;

		foreach ( $data_to_restore as $table => $rows ) {
			if ( ! in_array( $table, $allowed_tables ) ) continue;

			$full_table_name = $wpdb->prefix . $table;
			$wpdb->query( "DELETE FROM $full_table_name" );
			foreach ( $rows as $row ) {
				$wpdb->insert( $full_table_name, $row );
			}
		}

		Control_Audit::log('restore_backup', 'System restored from a backup file');
		$this->send_success( 'System restored successfully' );
	}

	public function export_user_package() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::has_permission('backup_manage') ) $this->send_error( 'Unauthorized', 403 );

		global $wpdb;
		$users = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}control_staff", ARRAY_A );
		$logs = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}control_activity_logs", ARRAY_A );

		// Secure handle sensitive data metadata
		foreach($users as &$u) {
			unset($u['password']);
			unset($u['raw_password']); // Secure package
			$u['has_stored_credentials'] = true;
		}

		$package = array(
			'export_type' => 'user_data_package',
			'timestamp' => current_time('mysql'),
			'user_count' => count($users),
			'users' => $users,
			'activity_logs' => $logs
		);

		$this->send_success( array(
			'json' => json_encode($package, JSON_PRETTY_PRINT),
			'filename' => "control_user_package_" . date('Y-m-d') . ".json"
		) );
	}

	public function bulk_delete_all_users() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::has_permission('users_delete') ) $this->send_error( 'Unauthorized', 403 );

		global $wpdb;
		$current_user = Control_Auth::current_user();

		// Delete all but current admin
		$count = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}control_staff WHERE id != %d AND username != 'admin'", $current_user->id ) );

		Control_Audit::log( 'system_maintenance', sprintf(__('حذف شامل لجميع الكوادر (%d حساب)', 'control'), $count) );
		$this->send_success( sprintf(__('تم حذف %d كادر بنجاح.', 'control'), $count) );
	}

	public function system_data_reset() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::has_permission('settings_manage') ) $this->send_error( 'Unauthorized', 403 );

		global $wpdb;
		$current_user = Control_Auth::current_user();

		// 1. Clear Staff (preserve active admin)
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}control_staff WHERE id != %d AND username != 'admin'", $current_user->id ) );

		// 2. Clear Logs
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}control_activity_logs" );

		// 3. Keep Settings, Roles, and Email Templates (System Structure)

		Control_Audit::log( 'system_reset', 'System data reset executed' );
		$this->send_success( __('تم تصفير بيانات النظام بنجاح مع الحفاظ على الإعدادات الأساسية.', 'control') );
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
		$replacement_key = sanitize_key( $_POST['replacement_role_key'] ?? 'coach' );

		$role = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}control_roles WHERE id = %d", $id ) );

		if ( ! $role ) $this->send_error( 'Role not found' );
		if ( $role->is_system ) $this->send_error( 'Cannot delete system roles' );

		// 1. Reassign staff members
		$wpdb->update(
			"{$wpdb->prefix}control_staff",
			array( 'role' => $replacement_key ),
			array( 'role' => $role->role_key )
		);

		// 2. Reassign WP Users
		$users = get_users( array( 'role' => $role->role_key ) );
		foreach ( $users as $user ) {
			$user->set_role( $replacement_key );
		}

		// 3. Delete from DB
		$wpdb->delete( "{$wpdb->prefix}control_roles", array( 'id' => $id ) );

		Control_Audit::log('delete_role', sprintf(__('حذف الدور: %s وإعادة تعيين المستخدمين إلى: %s', 'control'), $role->role_name, $replacement_key));

		// 4. Re-sync WP roles
		Control_Auth::sync_roles();
		$this->send_success();
	}

	public function save_policy() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::has_permission('settings_manage') ) $this->send_error( 'Unauthorized', 403 );

		global $wpdb;
		$id = intval( $_POST['id'] ?? 0 );
		$title = sanitize_text_field( $_POST['title'] );
		$content = $_POST['content']; // Allow HTML

		if ( empty($title) ) $this->send_error( __('عنوان السياسة مطلوب', 'control') );

		$data = array(
			'title' => $title,
			'content' => $content
		);

		if ( $id ) {
			$wpdb->update( "{$wpdb->prefix}control_policies", $data, array( 'id' => $id ) );
			Control_Audit::log('edit_policy', "Updated policy: $title");
		} else {
			$wpdb->insert( "{$wpdb->prefix}control_policies", $data );
			Control_Audit::log('add_policy', "Added new policy: $title");
		}

		$this->send_success();
	}

	public function delete_policy() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::has_permission('settings_manage') ) $this->send_error( 'Unauthorized', 403 );

		global $wpdb;
		$id = intval( $_POST['id'] );
		$wpdb->delete( "{$wpdb->prefix}control_policies", array( 'id' => $id ) );
		Control_Audit::log('delete_policy', "Deleted policy ID: $id");
		$this->send_success();
	}
}

new Control_Ajax();
