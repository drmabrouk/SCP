<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Control_Notifications {

	public static function init() {
		add_action( 'phpmailer_init', array( __CLASS__, 'configure_smtp' ) );

		// Schedule Engagement Reminder check
		if ( ! wp_next_scheduled( 'control_engagement_check' ) ) {
			wp_schedule_event( time(), 'daily', 'control_engagement_check' );
		}
		add_action( 'control_engagement_check', array( __CLASS__, 'check_inactive_users' ) );
	}

	/**
	 * Configure professional SMTP delivery.
	 */
	public static function configure_smtp( $phpmailer ) {
		global $wpdb;
		$settings = $wpdb->get_results( "SELECT setting_key, setting_value FROM {$wpdb->prefix}control_settings WHERE setting_key LIKE 'smtp_%' OR setting_key LIKE 'sender_%'", OBJECT_K );

		if ( ! empty($settings['smtp_host']->setting_value) ) {
			$phpmailer->isSMTP();
			$phpmailer->Host       = $settings['smtp_host']->setting_value;
			$phpmailer->SMTPAuth   = true;
			$phpmailer->Port       = $settings['smtp_port']->setting_value;
			$phpmailer->Username   = $settings['smtp_user']->setting_value;
			$phpmailer->Password   = $settings['smtp_pass']->setting_value;
			$phpmailer->SMTPSecure = $settings['smtp_encryption']->setting_value;
		}

		$phpmailer->From     = $settings['sender_email']->setting_value ?: get_option('admin_email');
		$phpmailer->FromName = $settings['sender_name']->setting_value ?: 'Control System';
	}

	/**
	 * Send branded HTML notification.
	 */
	public static function send( $template_key, $to_email, $placeholders = array() ) {
		global $wpdb;
		$template = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}control_email_templates WHERE template_key = %s", $template_key ) );

		if ( ! $template ) return false;

		$subject = $template->subject;
		$content = $template->content;

		// Merge system-wide placeholders
		$system_name = $wpdb->get_var( "SELECT setting_value FROM {$wpdb->prefix}control_settings WHERE setting_key = 'system_name'" ) ?: 'Control';
		$placeholders['{system_name}'] = $system_name;
		$placeholders['{site_url}']    = site_url();

		foreach ( $placeholders as $tag => $val ) {
			$subject = str_replace( $tag, $val, $subject );
			$content = str_replace( $tag, $val, $content );
		}

		$html_body = self::get_html_wrapper( $content );

		$headers = array('Content-Type: text/html; charset=UTF-8');

		return wp_mail( $to_email, $subject, $html_body, $headers );
	}

	/**
	 * Professional HTML Email Wrapper with branding.
	 */
	private static function get_html_wrapper( $content ) {
		global $wpdb;
		$system_name = $wpdb->get_var( "SELECT setting_value FROM {$wpdb->prefix}control_settings WHERE setting_key = 'system_name'" ) ?: 'Control';
		$logo_url    = $wpdb->get_var( "SELECT setting_value FROM {$wpdb->prefix}control_settings WHERE setting_key = 'company_logo'" );
		$primary_color = '#000000'; // Default

		// Try to get dynamic color if exists
		$design_bg = $wpdb->get_var( "SELECT setting_value FROM {$wpdb->prefix}control_settings WHERE setting_key = 'design_sidebar_bg'" );
		if ( $design_bg ) $primary_color = $design_bg;

		$header_content = '<h1>' . $system_name . '</h1>';
		if ( $logo_url ) {
			$header_content = '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr($system_name) . '" style="max-height: 80px;">';
		}

		return '
		<!DOCTYPE html>
		<html dir="rtl" lang="ar">
		<head>
			<meta charset="UTF-8">
			<style>
				body { font-family: "Rubik", Tahoma, Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
				.email-container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
				.header { background: ' . $primary_color . '; padding: 30px; text-align: center; color: #ffffff; }
				.header h1 { margin: 0; font-size: 24px; }
				.body { padding: 40px 30px; line-height: 1.6; color: #333; text-align: right; }
				.footer { background: #f8fafc; padding: 20px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
				.btn { display: inline-block; padding: 12px 25px; background: ' . $primary_color . '; color: #ffffff !important; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 20px; }
			</style>
		</head>
		<body>
			<div class="email-container">
				<div class="header">
					' . $header_content . '
				</div>
				<div class="body">
					' . $content . '
				</div>
				<div class="footer">
					&copy; ' . date('Y') . ' ' . $system_name . ' - ' . __('جميع الحقوق محفوظة', 'control') . '<br>
					' . __('هذا البريد تم إرساله تلقائياً من نظام الإدارة.', 'control') . '
				</div>
			</div>
		</body>
		</html>';
	}

	/**
	 * Trigger Engagement Reminder for inactive users.
	 */
	public static function check_inactive_users() {
		global $wpdb;
		$table = $wpdb->prefix . 'control_staff';

		// Find users who haven't been active for 30 days
		$inactive_users = $wpdb->get_results( "SELECT * FROM $table WHERE last_activity < DATE_SUB(NOW(), INTERVAL 30 DAY)" );

		foreach ( $inactive_users as $user ) {
			if ( ! empty($user->email) ) {
				self::send( 'engagement_reminder', $user->email, array(
					'{user_name}' => $user->name
				) );

				// Update last_activity to prevent spamming (resets the 30-day counter)
				$wpdb->update($table, array('last_activity' => current_time('mysql')), array('id' => $user->id));
			}
		}
	}
}
