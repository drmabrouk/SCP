<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Control_Lessons {

	public static function init() {
		add_action( 'control_daily_maintenance', array( __CLASS__, 'prune_old_lessons' ) );
		if ( ! wp_next_scheduled( 'control_daily_maintenance' ) ) {
			wp_schedule_event( time(), 'daily', 'control_daily_maintenance' );
		}

		add_action( 'wp_ajax_control_save_lesson', array( __CLASS__, 'save_lesson' ) );
		add_action( 'wp_ajax_control_delete_lesson', array( __CLASS__, 'delete_lesson' ) );
		add_action( 'wp_ajax_control_get_lesson', array( __CLASS__, 'get_lesson' ) );
		add_action( 'wp_ajax_control_save_lesson_suggestion', array( __CLASS__, 'save_lesson_suggestion' ) );
		add_action( 'wp_ajax_control_delete_lesson_suggestion', array( __CLASS__, 'delete_lesson_suggestion' ) );
	}

	public static function save_lesson() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::has_permission('lessons_manage') ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$user = Control_Auth::current_user();
		$id = intval( $_POST['id'] ?? 0 );

		$lesson_data = $_POST['lesson_data'] ?? array();
		$title = sanitize_text_field( $lesson_data['title'] ?? '' );
		$target_group = sanitize_text_field( $lesson_data['target_group'] ?? '' );
		$duration = sanitize_text_field( $lesson_data['duration'] ?? '' );
		$lang = sanitize_text_field( $lesson_data['lang'] ?? 'ar' );

		if ( empty($title) ) wp_send_json_error( 'Title is required' );

		$data = array(
			'creator_id'   => $user->id,
			'title'        => $title,
			'target_group' => $target_group,
			'duration'     => $duration,
			'lang'         => $lang,
			'lesson_data'  => json_encode( $lesson_data )
		);

		if ( $id ) {
			// Check ownership unless admin with view_all permission
			$lesson = $wpdb->get_row( $wpdb->prepare( "SELECT creator_id FROM {$wpdb->prefix}control_lessons WHERE id = %d", $id ) );
			if ( $lesson->creator_id != $user->id && ! Control_Auth::has_permission('lessons_view_all') ) {
				wp_send_json_error( 'Access Denied' );
			}
			$wpdb->update( "{$wpdb->prefix}control_lessons", $data, array( 'id' => $id ) );
			Control_Audit::log( 'edit_lesson', "Updated lesson: $title" );
		} else {
			$wpdb->insert( "{$wpdb->prefix}control_lessons", $data );
			$id = $wpdb->insert_id;
			Control_Audit::log( 'add_lesson', "Created lesson: $title" );
		}

		wp_send_json_success( array( 'id' => $id, 'message' => __( 'تم حفظ الدرس بنجاح', 'control' ) ) );
	}

	public static function delete_lesson() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::has_permission('lessons_manage') ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$user = Control_Auth::current_user();
		$id = intval( $_POST['id'] );

		$lesson = $wpdb->get_row( $wpdb->prepare( "SELECT creator_id, title FROM {$wpdb->prefix}control_lessons WHERE id = %d", $id ) );
		if ( ! $lesson ) wp_send_json_error( 'Lesson not found' );

		if ( $lesson->creator_id != $user->id && ! Control_Auth::has_permission('lessons_view_all') ) {
			wp_send_json_error( 'Access Denied' );
		}

		$wpdb->delete( "{$wpdb->prefix}control_lessons", array( 'id' => $id ) );
		Control_Audit::log( 'delete_lesson', "Deleted lesson: {$lesson->title}" );
		wp_send_json_success( __( 'تم حذف الدرس بنجاح', 'control' ) );
	}

	public static function get_lesson() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		global $wpdb;
		$id = intval( $_POST['id'] );
		$user = Control_Auth::current_user();

		$lesson = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}control_lessons WHERE id = %d", $id ) );
		if ( ! $lesson ) wp_send_json_error( 'Lesson not found' );

		if ( $lesson->creator_id != $user->id && ! Control_Auth::has_permission('lessons_view_all') ) {
			wp_send_json_error( 'Access Denied' );
		}

		$creator_info = array();
		if ( strpos($lesson->creator_id, 'wp_') === 0 ) {
			$wp_uid = str_replace('wp_', '', $lesson->creator_id);
			$wp_u = get_userdata($wp_uid);
			$creator_info = array(
				'first_name' => $wp_u->first_name ?: $wp_u->display_name,
				'last_name'  => $wp_u->last_name,
				'job_title'  => 'Administrator',
				'employer_name' => get_bloginfo('name'),
				'home_country'  => '',
				'org_logo' => $wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}control_settings WHERE setting_key = 'company_logo'"),
			);
		} else {
			$staff = $wpdb->get_row( $wpdb->prepare( "SELECT first_name, last_name, job_title, employer_name, home_country, org_logo FROM {$wpdb->prefix}control_staff WHERE id = %d", $lesson->creator_id ), ARRAY_A );
			if ( $staff ) $creator_info = $staff;
		}

		$lesson->lesson_data = json_decode( $lesson->lesson_data, true );
		$response = (object) array_merge( (array) $lesson, $creator_info );

		wp_send_json_success( $response );
	}

	public static function save_lesson_suggestion() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$id = intval( $_POST['id'] ?? 0 );
		$topic = sanitize_text_field( $_POST['topic'] );
		$category = sanitize_text_field( $_POST['category'] ?? 'general' );
		$lang = sanitize_text_field( $_POST['lang'] ?? 'ar' );
		$content = wp_kses_post( $_POST['content'] ?? '' );
		$tags = sanitize_text_field( $_POST['tags'] ?? '' );

		if ( empty($topic) ) wp_send_json_error( 'Topic is required' );

		$data = array(
			'topic'    => $topic,
			'category' => $category,
			'lang'     => $lang,
			'content'  => $content,
			'tags'     => $tags
		);

		if ( $id ) {
			$wpdb->update( "{$wpdb->prefix}control_lesson_suggestions", $data, array( 'id' => $id ) );
		} else {
			$wpdb->insert( "{$wpdb->prefix}control_lesson_suggestions", $data );
		}

		wp_send_json_success();
	}

	public static function delete_lesson_suggestion() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::is_admin() ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$id = intval( $_POST['id'] );
		$wpdb->delete( "{$wpdb->prefix}control_lesson_suggestions", array( 'id' => $id ) );
		wp_send_json_success();
	}

	public static function get_all_lessons( $all = false ) {
		global $wpdb;
		$user = Control_Auth::current_user();
		if ( $all && Control_Auth::has_permission('lessons_view_all') ) {
			return $wpdb->get_results( "SELECT l.*, s.first_name, s.last_name FROM {$wpdb->prefix}control_lessons l LEFT JOIN {$wpdb->prefix}control_staff s ON l.creator_id = s.id ORDER BY created_at DESC" );
		}
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}control_lessons WHERE creator_id = %s ORDER BY created_at DESC", $user->id ) );
	}

	public static function get_suggestions() {
		global $wpdb;
		return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}control_lesson_suggestions ORDER BY created_at DESC" );
	}

	public static function prune_old_lessons() {
		global $wpdb;
		$one_year_ago = date( 'Y-m-d H:i:s', strtotime( '-1 year' ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}control_lessons WHERE created_at < %s", $one_year_ago ) );
	}
}

Control_Lessons::init();
