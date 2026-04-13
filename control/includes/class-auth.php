<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Control_Auth {

	public static function init() {
		if ( ! session_id() && ! headers_sent() ) {
			session_start();
		}

		add_action( 'init', array( __CLASS__, 'sync_roles' ) );
		add_action( 'admin_init', array( __CLASS__, 'restrict_admin_access' ) );
		add_filter( 'show_admin_bar', array( __CLASS__, 'handle_admin_bar' ) );

		// Update activity for logged in users
		add_action( 'init', array( __CLASS__, 'update_last_activity' ) );
	}

	/**
	 * Update user's last activity timestamp.
	 */
	public static function update_last_activity() {
		if ( self::is_logged_in() ) {
			$user = self::current_user();
			if ( $user && is_numeric( $user->id ) ) {
				global $wpdb;
				$wpdb->update(
					$wpdb->prefix . 'control_staff',
					array( 'last_activity' => current_time( 'mysql' ) ),
					array( 'id' => $user->id )
				);
			}
		}
	}

	/**
	 * Sync system roles and remove non-explicit roles.
	 */
	public static function sync_roles() {
		$new_roles = array(
			'admin'       => array(
				'name' => __( 'System Administrator', 'control' ),
				'caps' => array( 'read' => true, 'manage_options' => true, 'upload_files' => true )
			),
			'coach'       => array(
				'name' => __( 'Sports Coach', 'control' ),
				'caps' => array( 'read' => true )
			),
			'therapist'   => array(
				'name' => __( 'Sports Therapist', 'control' ),
				'caps' => array( 'read' => true )
			),
			'nutritionist' => array(
				'name' => __( 'Sports Nutrition Specialist', 'control' ),
				'caps' => array( 'read' => true )
			),
			'pe_teacher'  => array(
				'name' => __( 'Physical Education Teacher', 'control' ),
				'caps' => array( 'read' => true )
			),
			'researcher'  => array(
				'name' => __( 'Sports Researcher', 'control' ),
				'caps' => array( 'read' => true )
			),
		);

		foreach ( $new_roles as $role_id => $role_data ) {
			if ( ! get_role( $role_id ) ) {
				add_role( $role_id, $role_data['name'], $role_data['caps'] );
			}
		}

		// Core roles to keep
		$keep_roles = array_keys( $new_roles );
		$keep_roles[] = 'administrator'; // Standard WP admin

		// Remove all other roles
		global $wp_roles;
		if ( isset( $wp_roles->roles ) && is_array( $wp_roles->roles ) ) {
			foreach ( $wp_roles->roles as $role_id => $data ) {
				if ( ! in_array( $role_id, $keep_roles ) ) {
					remove_role( $role_id );
				}
			}
		}
	}

	/**
	 * Restrict WP Dashboard access.
	 */
	public static function restrict_admin_access() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		// If not System Admin and not Site Admin (administrator), redirect away
		if ( is_admin() && ! current_user_can( 'manage_options' ) ) {
			wp_redirect( home_url() );
			exit;
		}
	}

	/**
	 * Hide admin bar for non-admins.
	 */
	public static function handle_admin_bar( $show ) {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		return false;
	}

	public static function login( $phone, $password ) {
		global $wpdb;
		$table = $wpdb->prefix . 'control_staff';

		$user = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE phone = %s", $phone ) );

		if ( $user && password_verify( $password, $user->password ) ) {
			if ( $user->is_restricted ) {
				return new WP_Error( 'restricted', __( 'هذا الحساب مقيد حالياً. يرجى التواصل مع الإدارة.', 'control' ) );
			}
			$wpdb->update( $table, array( 'last_activity' => current_time( 'mysql' ) ), array( 'id' => $user->id ) );
			return self::set_user_session( $user );
		}
		return false;
	}

	private static function set_user_session( $user ) {
		$_SESSION['control_user_id']   = $user->id;
		$_SESSION['control_phone']     = $user->phone;
		$_SESSION['control_user_role'] = $user->role;
		$_SESSION['control_user_name'] = $user->name;
		return true;
	}

	public static function register_user( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'control_staff';

		// Check if phone or email already exists
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE phone = %s OR (email IS NOT NULL AND email != '' AND email = %s)", $data['phone'], $data['email'] ) );
		if ( $exists ) {
			return new WP_Error( 'duplicate', __( 'رقم الهاتف أو البريد الإلكتروني مسجل بالفعل.', 'control' ) );
		}

		$inserted = $wpdb->insert( $table, array(
			'name'     => $data['first_name'] . ' ' . $data['last_name'],
			'phone'    => $data['phone'],
			'username' => $data['phone'], // Username is the phone number
			'email'    => $data['email'],
			'password' => password_hash( $data['password'], PASSWORD_DEFAULT ),
			'role'     => 'coach', // Default to coach
		) );

		if ( $inserted ) {
			$user_id = $wpdb->insert_id;
			$user = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $user_id ) );
			self::set_user_session( $user );
			return $user_id;
		}

		return new WP_Error( 'db_error', __( 'حدث خطأ أثناء حفظ البيانات.', 'control' ) );
	}

	public static function logout() {
		unset( $_SESSION['control_user_id'] );
		unset( $_SESSION['control_phone'] );
		unset( $_SESSION['control_user_role'] );
		unset( $_SESSION['control_user_name'] );
	}

	public static function is_logged_in() {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		return isset( $_SESSION['control_user_id'] );
	}

	public static function current_user() {
		if ( current_user_can( 'manage_options' ) ) {
			$wp_user = wp_get_current_user();
			return (object) array(
				'id'   => 'wp_' . $wp_user->ID,
				'username' => $wp_user->user_login,
				'role' => 'admin',
				'name' => $wp_user->display_name
			);
		}

		if ( ! isset( $_SESSION['control_user_id'] ) ) return null;

		return (object) array(
			'id'   => $_SESSION['control_user_id'],
			'phone' => $_SESSION['control_phone'],
			'role' => $_SESSION['control_user_role'],
			'name' => $_SESSION['control_user_name']
		);
	}

	public static function is_admin() {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		$user = self::current_user();
		return $user && $user->role === 'admin';
	}

	public static function get_all_users() {
		global $wpdb;
		$table = $wpdb->prefix . 'control_staff';
		return $wpdb->get_results( "SELECT * FROM $table ORDER BY id DESC" );
	}
}
