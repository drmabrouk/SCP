<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Control_Grades {

	public static function init() {
		add_action( 'wp_ajax_control_save_student', array( __CLASS__, 'save_student' ) );
		add_action( 'wp_ajax_control_delete_student', array( __CLASS__, 'delete_student' ) );
		add_action( 'wp_ajax_control_import_students', array( __CLASS__, 'import_students' ) );
		add_action( 'wp_ajax_control_save_grades', array( __CLASS__, 'save_grades' ) );
		add_action( 'wp_ajax_control_get_students', array( __CLASS__, 'get_students_ajax' ) );
	}

	public static function save_student() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::has_permission('grades_manage') ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$id = intval( $_POST['id'] ?? 0 );

		$data = array(
			'name'        => sanitize_text_field( $_POST['name'] ),
			'grade'       => sanitize_text_field( $_POST['grade'] ),
			'section'     => sanitize_text_field( $_POST['section'] ),
			'nationality' => sanitize_text_field( $_POST['nationality'] ),
			'email'       => sanitize_email( $_POST['email'] ),
			'phone'       => sanitize_text_field( $_POST['phone'] ),
			'national_id' => sanitize_text_field( $_POST['national_id'] ),
		);

		if ( $id ) {
			$wpdb->update( "{$wpdb->prefix}control_students", $data, array( 'id' => $id ) );
		} else {
			$wpdb->insert( "{$wpdb->prefix}control_students", $data );
			$id = $wpdb->insert_id;
		}

		wp_send_json_success( array( 'id' => $id ) );
	}

	public static function delete_student() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::has_permission('grades_manage') ) wp_send_json_error( 'Unauthorized' );

		global $wpdb;
		$id = intval( $_POST['id'] );
		$wpdb->delete( "{$wpdb->prefix}control_students", array( 'id' => $id ) );
		$wpdb->delete( "{$wpdb->prefix}control_grades", array( 'student_id' => $id ) );
		wp_send_json_success();
	}

	public static function import_students() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::has_permission('grades_manage') ) wp_send_json_error( 'Unauthorized' );

		$students = $_POST['students'] ?? array();
		if ( empty($students) ) wp_send_json_error( 'No data to import' );

		global $wpdb;
		$count = 0;
		foreach ( $students as $s ) {
			$wpdb->insert( "{$wpdb->prefix}control_students", array(
				'name'        => sanitize_text_field( $s['name'] ),
				'grade'       => sanitize_text_field( $s['grade'] ),
				'section'     => sanitize_text_field( $s['section'] ),
				'nationality' => sanitize_text_field( $s['nationality'] ),
				'email'       => sanitize_email( $s['email'] ),
				'phone'       => sanitize_text_field( $s['phone'] ),
				'national_id' => sanitize_text_field( $s['national_id'] ),
			) );
			$count++;
		}

		wp_send_json_success( array( 'imported' => $count ) );
	}

	public static function save_grades() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		if ( ! Control_Auth::has_permission('grades_manage') ) wp_send_json_error( 'Unauthorized' );

		$grades = $_POST['grades'] ?? array();
		if ( empty($grades) ) wp_send_json_error( 'No grades data' );

		global $wpdb;
		$updated = 0;
		foreach ( $grades as $student_id => $g ) {
			$student_id = intval($student_id);
			$data = array(
				'student_id'       => $student_id,
				'physical_fitness' => floatval( $g['physical_fitness'] ),
				'discipline'       => floatval( $g['discipline'] ),
				'oral_questioning' => floatval( $g['oral_questioning'] ),
				'practical_skills' => floatval( $g['practical_skills'] ),
				'behavior'         => floatval( $g['behavior'] ),
				'participation'    => floatval( $g['participation'] ),
				'total_score'      => floatval( $g['total_score'] ),
			);

			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}control_grades WHERE student_id = %d", $student_id ) );
			if ( $exists ) {
				$wpdb->update( "{$wpdb->prefix}control_grades", $data, array( 'student_id' => $student_id ) );
			} else {
				$wpdb->insert( "{$wpdb->prefix}control_grades", $data );
			}
			$updated++;
		}

		wp_send_json_success( array( 'updated' => $updated ) );
	}

	public static function get_students_ajax() {
		check_ajax_referer( 'control_nonce', 'nonce' );
		global $wpdb;

		$grade = sanitize_text_field( $_POST['grade'] ?? '' );
		$section = sanitize_text_field( $_POST['section'] ?? '' );

		$query = "SELECT s.*, g.physical_fitness, g.discipline, g.oral_questioning, g.practical_skills, g.behavior, g.participation, g.total_score
				  FROM {$wpdb->prefix}control_students s
				  LEFT JOIN {$wpdb->prefix}control_grades g ON s.id = g.student_id
				  WHERE 1=1";

		$params = array();
		if ( ! empty($grade) ) {
			$query .= " AND s.grade = %s";
			$params[] = $grade;
		}
		if ( ! empty($section) ) {
			$query .= " AND s.section = %s";
			$params[] = $section;
		}

		$results = $wpdb->get_results( !empty($params) ? $wpdb->prepare($query, $params) : $query );
		wp_send_json_success( $results );
	}
}

Control_Grades::init();
