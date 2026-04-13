<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Control_Database {

	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$table_staff        = $wpdb->prefix . 'control_staff';
		$table_settings     = $wpdb->prefix . 'control_settings';
		$table_roles        = $wpdb->prefix . 'control_roles';
		$table_activity_logs = $wpdb->prefix . 'control_activity_logs';

		$sql = "CREATE TABLE $table_staff (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			username varchar(100),
			phone varchar(50) NOT NULL,
			password varchar(255) NOT NULL,
			name varchar(255),
			email varchar(255),
			role varchar(50) DEFAULT 'employee',
			is_restricted tinyint(1) DEFAULT 0,
			restriction_reason varchar(255),
			restriction_expiry datetime,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			last_activity datetime DEFAULT CURRENT_TIMESTAMP,

			/* Personal Info */
			profile_image varchar(255),
			gender varchar(20),

			/* Academic Info */
			degree varchar(255),
			specialization varchar(255),
			institution varchar(255),
			institution_country varchar(100),
			graduation_year varchar(10),

			/* Personal & Location Info */
			home_country varchar(100),
			state varchar(100),
			address text,

			/* Employment Info */
			employer_name varchar(255),
			employer_country varchar(100),
			work_phone varchar(50),
			work_email varchar(255),
			org_logo varchar(255),
			job_title varchar(255),

			PRIMARY KEY  (id),
			UNIQUE KEY phone (phone)
		) $charset_collate;

		CREATE TABLE $table_settings (
			setting_key varchar(100) NOT NULL,
			setting_value text,
			PRIMARY KEY  (setting_key)
		) $charset_collate;

		CREATE TABLE $table_roles (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			role_key varchar(50) NOT NULL,
			role_name varchar(100) NOT NULL,
			permissions longtext,
			is_system tinyint(1) DEFAULT 0,
			PRIMARY KEY  (id),
			UNIQUE KEY role_key (role_key)
		) $charset_collate;

		CREATE TABLE $table_activity_logs (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id varchar(100) NOT NULL,
			action_type varchar(100) NOT NULL,
			description text,
			device_type varchar(50),
			device_info text,
			ip_address varchar(50),
			meta_data longtext,
			action_date datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) $charset_collate;";

		if ( file_exists( ABSPATH . 'wp-admin/includes/upgrade.php' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}

		// Seed initial data
		self::seed_data();
		Control_Auth::sync_roles();
	}

	private static function seed_data() {
		global $wpdb;
		$table_staff    = $wpdb->prefix . 'control_staff';
		$table_settings = $wpdb->prefix . 'control_settings';
		$table_roles    = $wpdb->prefix . 'control_roles';

		// Seed initial roles
		$initial_roles = array(
			array(
				'role_key'  => 'admin',
				'role_name' => 'System Administrator',
				'permissions' => json_encode(array('all' => true)),
				'is_system' => 1
			),
			array(
				'role_key'  => 'coach',
				'role_name' => 'Sports Coach',
				'permissions' => json_encode(array('dashboard' => true, 'users_view' => true)),
				'is_system' => 1
			),
			array(
				'role_key'  => 'therapist',
				'role_name' => 'Sports Therapist',
				'permissions' => json_encode(array('dashboard' => true, 'users_view' => true)),
				'is_system' => 1
			),
			array(
				'role_key'  => 'nutritionist',
				'role_name' => 'Sports Nutrition Specialist',
				'permissions' => json_encode(array('dashboard' => true, 'users_view' => true)),
				'is_system' => 1
			),
			array(
				'role_key'  => 'pe_teacher',
				'role_name' => 'PE Teacher',
				'permissions' => json_encode(array('dashboard' => true, 'users_view' => true)),
				'is_system' => 1
			),
			array(
				'role_key'  => 'researcher',
				'role_name' => 'Sports Researcher',
				'permissions' => json_encode(array('dashboard' => true, 'users_view' => true)),
				'is_system' => 1
			)
		);

		foreach ( $initial_roles as $role ) {
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_roles WHERE role_key = %s", $role['role_key'] ) );
			if ( ! $exists ) {
				$wpdb->insert( $table_roles, $role );
			}
		}

		// Default settings
		$defaults = array(
			'fullscreen_password' => '123456789',
			'system_name'         => 'Control',
			'company_name'        => 'Control',
			'pwa_app_name'        => 'Control',
			'pwa_short_name'      => 'Control',
			'pwa_theme_color'     => '#000000',
			'pwa_bg_color'        => '#ffffff',
		);

		foreach ( $defaults as $key => $value ) {
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT setting_key FROM $table_settings WHERE setting_key = %s", $key ) );
			if ( ! $exists ) {
				$wpdb->insert( $table_settings, array(
					'setting_key'   => $key,
					'setting_value' => $value
				) );
			}
		}
	}
}
