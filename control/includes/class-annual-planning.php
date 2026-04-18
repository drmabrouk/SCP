<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Control_Annual_Planning {

	public static function init() {
		add_action( 'wp_ajax_control_save_annual_plan', array( __CLASS__, 'save_plan' ) );
		add_action( 'wp_ajax_control_get_annual_plan', array( __CLASS__, 'get_plan' ) );
		add_action( 'wp_ajax_control_delete_annual_plan', array( __CLASS__, 'delete_plan' ) );

		if ( ! wp_next_scheduled( 'control_lesson_reminders' ) ) {
			wp_schedule_event( time(), 'daily', 'control_lesson_reminders' );
		}
		add_action( 'control_lesson_reminders', array( __CLASS__, 'process_reminders' ) );
	}

	public static function save_plan() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::has_permission('annual_planning_manage') ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$user = Control_Auth::current_user();
		$id = intval( $_POST['id'] ?? 0 );

		$data = array(
			'creator_id'      => $user->id,
			'plan_name'       => sanitize_text_field( $_POST['plan_name'] ),
			'academic_system' => sanitize_text_field( $_POST['academic_system'] ),
			'plan_type'       => sanitize_text_field( $_POST['plan_type'] ),
			'start_date'      => sanitize_text_field( $_POST['start_date'] ),
			'end_date'        => sanitize_text_field( $_POST['end_date'] ),
			'weekly_frequency'=> intval( $_POST['weekly_frequency'] ),
			'lesson_day'      => sanitize_text_field( $_POST['lesson_day'] ),
			'lang'            => sanitize_text_field( $_POST['lang'] ?? 'ar' ),
			'plan_data'       => wp_unslash( $_POST['plan_data'] ) // JSON
		);

		if ( $id ) {
			$wpdb->update( "{$wpdb->prefix}control_annual_plans", $data, array( 'id' => $id ) );
		} else {
			$wpdb->insert( "{$wpdb->prefix}control_annual_plans", $data );
			$id = $wpdb->insert_id;
		}

		wp_send_json_success( array( 'id' => $id ) );
	}

	public static function get_plan() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		global $wpdb;
		$id = intval( $_POST['id'] );
		$plan = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}control_annual_plans WHERE id = %d", $id ) );

		if ( ! $plan ) wp_send_json_error( 'Plan not found' );

		$plan->plan_data = json_decode( $plan->plan_data );
		wp_send_json_success( $plan );
	}

	public static function delete_plan() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::has_permission('annual_planning_manage') ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$id = intval( $_POST['id'] );
		$wpdb->delete( "{$wpdb->prefix}control_annual_plans", array( 'id' => $id ) );
		wp_send_json_success();
	}

	public static function get_user_plans( $user_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}control_annual_plans WHERE creator_id = %s ORDER BY created_at DESC", $user_id ) );
	}

	/**
	 * Daily task to scan all active plans and send reminders.
	 */
	public static function process_reminders() {
		global $wpdb;
		$today = date('Y-m-d');
		$tomorrow = date('Y-m-d', strtotime('+1 day'));

		$plans = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}control_annual_plans" );

		foreach ( $plans as $p ) {
			$data = json_decode($p->plan_data, true);
			if ( empty($data) ) continue;

			foreach ( $data as $slot ) {
				$lesson_date = $slot['date'] ?? '';
				if ( empty($lesson_date) ) continue;

				$title = '';
				$msg = '';

				if ( $lesson_date === $today ) {
					$title = $p->lang === 'en' ? "Today's Lesson Reminder" : "تذكير بدرس اليوم";
					$msg = ($p->lang === 'en' ? "Today's scheduled lesson: " : "درس اليوم المقرر: ") . "<b>{$slot['title']}</b>";
				} elseif ( $lesson_date === $tomorrow ) {
					$title = $p->lang === 'en' ? "Upcoming Lesson Tomorrow" : "تذكير بدرس غداً";
					$msg = ($p->lang === 'en' ? "Upcoming lesson tomorrow: " : "درس غداً المرتقب: ") . "<b>{$slot['title']}</b>";
				}

				if ( $title ) {
					// Get creator email if available
					$creator_email = '';
					if ( strpos($p->creator_id, 'wp_') === 0 ) {
						$wp_u = get_userdata(str_replace('wp_', '', $p->creator_id));
						$creator_email = $wp_u->user_email;
					} else {
						$creator_email = $wpdb->get_var($wpdb->prepare("SELECT email FROM {$wpdb->prefix}control_staff WHERE id = %d", $p->creator_id));
					}

					Control_Notifications::send_reminder( $p->creator_id, $title, $msg, array('to' => $creator_email) );
				}
			}
		}
	}
}

Control_Annual_Planning::init();
