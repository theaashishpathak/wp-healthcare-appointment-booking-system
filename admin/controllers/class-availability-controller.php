<?php
namespace AB\Admin\Controllers;

use AB\Includes\Security\Security;
use AB\Includes\Models\Availability_Model;
use AB\Includes\Models\Holiday_Model;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Availability_Controller {

	public function __construct() {
		add_action( 'admin_post_ab_save_availability', array( $this, 'save' ) );
		add_action( 'admin_post_ab_save_holiday', array( $this, 'save_holiday' ) );
		add_action( 'admin_post_ab_delete_holiday', array( $this, 'delete_holiday' ) );
	}

	public function save() {
		Security::verify_admin_request( '_wpnonce', false );

		$doctor_id = isset( $_POST['doctor_id'] ) ? absint( $_POST['doctor_id'] ) : 0;
		if ( ! $doctor_id ) {
			$this->redirect_with_message( 'error', __( 'Please choose a doctor.', 'appointment-booking-system' ) );
		}

		$days            = isset( $_POST['day'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['day'] ) ) : array();
		$start_times     = isset( $_POST['start_time'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['start_time'] ) ) : array();
		$end_times       = isset( $_POST['end_time'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['end_time'] ) ) : array();
		$break_starts    = isset( $_POST['break_start'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['break_start'] ) ) : array();
		$break_ends      = isset( $_POST['break_end'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['break_end'] ) ) : array();
		$slot_durations  = isset( $_POST['slot_duration'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['slot_duration'] ) ) : array();

		$rows = array();
		foreach ( $days as $index => $day ) {
			if ( empty( $start_times[ $index ] ) || empty( $end_times[ $index ] ) ) {
				continue;
			}
			$rows[] = array(
				'day'           => $day,
				'start_time'    => $start_times[ $index ],
				'end_time'      => $end_times[ $index ],
				'break_start'   => $break_starts[ $index ] ?? '',
				'break_end'     => $break_ends[ $index ] ?? '',
				'slot_duration' => $slot_durations[ $index ] ?? 30,
			);
		}

		( new Availability_Model() )->save_for_doctor( $doctor_id, $rows );
		\AB\Includes\Logger::log( 'availability', 'updated', 'Saved weekly schedule for Doctor ID ' . $doctor_id, array( 'doctor_id' => $doctor_id, 'rows_count' => count( $rows ) ) );

		$this->redirect_with_message( 'success', __( 'Availability saved successfully.', 'appointment-booking-system' ), $doctor_id );
	}

	public function save_holiday() {
		Security::verify_admin_request( '_wpnonce', false );

		$doctor_id     = isset( $_POST['doctor_id'] ) ? absint( $_POST['doctor_id'] ) : null;
		$type          = isset( $_POST['holiday_type'] ) ? sanitize_text_field( wp_unslash( $_POST['holiday_type'] ) ) : 'holiday';
		$mode          = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : 'date';
		$holiday_date  = isset( $_POST['holiday_date'] ) ? sanitize_text_field( wp_unslash( $_POST['holiday_date'] ) ) : '';
		$end_date      = isset( $_POST['end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) ) : '';
		$recurring_day = isset( $_POST['recurring_day'] ) && '' !== $_POST['recurring_day'] ? absint( $_POST['recurring_day'] ) : null;
		$note          = isset( $_POST['note'] ) ? sanitize_text_field( wp_unslash( $_POST['note'] ) ) : '';

		$data = array(
			'doctor_id'     => $doctor_id ?: null,
			'type'          => in_array( $type, array( 'holiday', 'special_working' ), true ) ? $type : 'holiday',
			'note'          => $note,
			'holiday_date'  => null,
			'end_date'      => null,
			'recurring_day' => null,
		);

		if ( 'recurring' === $mode ) {
			$data['recurring_day'] = $recurring_day;
		} else {
			$data['holiday_date'] = $holiday_date ?: null;
			$data['end_date']     = $end_date ?: null;
		}

		$h_id = ( new Holiday_Model() )->insert( $data );
		\AB\Includes\Logger::log( 'availability', 'created', 'Saved holiday/off-day exception', array_merge( array( 'id' => $h_id ), $data ) );

		$this->redirect_with_message( 'success', __( 'Holiday saved successfully.', 'appointment-booking-system' ), $doctor_id );
	}

	public function delete_holiday() {
		Security::verify_admin_request( '_wpnonce', false );
		$id        = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$doctor_id = isset( $_GET['doctor_id'] ) ? absint( $_GET['doctor_id'] ) : 0;
		if ( $id ) {
			( new Holiday_Model() )->delete( $id );
			\AB\Includes\Logger::log( 'availability', 'deleted', 'Removed holiday exception ID ' . $id );
		}
		$this->redirect_with_message( 'success', __( 'Holiday removed.', 'appointment-booking-system' ), $doctor_id );
	}

	protected function redirect_with_message( $type, $message, $doctor_id = 0 ) {
		$args = array(
			'page'      => 'ab-availability',
			'ab_notice' => $type,
			'ab_msg'    => rawurlencode( $message ),
		);
		if ( $doctor_id ) {
			$args['doctor_id'] = $doctor_id;
		}
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}
}
