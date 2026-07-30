<?php
namespace AB\Admin\Controllers;

use AB\Includes\Security\Security;
use AB\Includes\Models\Appointment_Model;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Appointment_Controller {

	public function __construct() {
		add_action( 'admin_post_ab_update_appointment_status', array( $this, 'update_status' ) );
		add_action( 'admin_post_ab_delete_appointment', array( $this, 'delete' ) );
		add_action( 'admin_post_ab_export_appointments', array( $this, 'export_csv' ) );
	}

	public function update_status() {
		Security::verify_admin_request( '_wpnonce', false );

		$id     = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';

		$allowed = array_keys( ab_get_status_labels() );
		if ( $id && in_array( $status, $allowed, true ) ) {
			$app = ( new Appointment_Model() )->find( $id );
			( new Appointment_Model() )->update( $id, array( 'status' => $status ) );
			$b_id = $app ? $app['booking_id'] : '#' . $id;
			\AB\Includes\Logger::log( 'appointment', 'updated', 'Updated appointment ' . $b_id . ' status to [' . strtoupper( $status ) . ']', array( 'id' => $id, 'status' => $status ) );
		}

		$this->redirect_with_message( 'success', __( 'Appointment status updated.', 'appointment-booking-system' ) );
	}

	public function delete() {
		Security::verify_admin_request( '_wpnonce', false );
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( $id ) {
			$app = ( new Appointment_Model() )->find( $id );
			$b_id = $app ? $app['booking_id'] : '#' . $id;
			( new Appointment_Model() )->delete( $id );
			\AB\Includes\Logger::log( 'appointment', 'deleted', 'Deleted appointment ' . $b_id );
		}
		$this->redirect_with_message( 'success', __( 'Appointment deleted.', 'appointment-booking-system' ) );
	}

	public function export_csv() {
		Security::verify_admin_request( '_wpnonce', false );

		$model  = new Appointment_Model();
		$result = $model->search( array( 'per_page' => 100000, 'page' => 1 ) );

		\AB\Includes\Logger::log(
			'appointment',
			'exported',
			'Exported ' . count( $result['items'] ) . ' appointment record(s) to CSV',
			array(
				'rows_exported' => count( $result['items'] ),
				'filename'      => 'appointments-export-' . gmdate( 'Y-m-d' ) . '.csv',
			)
		);

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=appointments-export-' . gmdate( 'Y-m-d' ) . '.csv' );

		$out = fopen( 'php://output', 'w' );

		// Output UTF-8 Byte Order Mark (BOM) so Excel opens UTF-8 special characters (like Läng) correctly.
		fprintf( $out, "\xEF\xBB\xBF" );

		fputcsv(
			$out,
			array( 'Booking ID', 'Patient Name', 'Phone', 'Email', 'Doctor ID', 'Category ID', 'Date', 'Time', 'Status', 'Created At' )
		);
		foreach ( $result['items'] as $row ) {
			// Format phone, date, and created_at as text so Excel displays full text instead of scientific notation or date column width hashes (###)
			$phone      = ! empty( $row['phone'] ) ? "\t" . $row['phone'] : '';
			$date       = ! empty( $row['appointment_date'] ) ? "\t" . $row['appointment_date'] : '';
			$time       = ! empty( $row['appointment_time'] ) ? "\t" . $row['appointment_time'] : '';
			$created_at = ! empty( $row['created_at'] ) ? "\t" . $row['created_at'] : '';

			fputcsv(
				$out,
				array(
					$this->sanitize_csv_field( $row['booking_id'] ),
					$this->sanitize_csv_field( $row['patient_name'] ),
					$phone,
					$this->sanitize_csv_field( $row['email'] ),
					(int) $row['doctor_id'],
					(int) $row['category_id'],
					$date,
					$time,
					$this->sanitize_csv_field( $row['status'] ),
					$created_at,
				)
			);
		}
		fclose( $out );
		exit;
	}

	/**
	 * Neutralize dangerous formula prefixes (=, +, -, @, \t, \r) for CSV export safety (CWE-1236).
	 *
	 * @param mixed $value Raw cell value.
	 * @return string Safe cell value.
	 */
	protected function sanitize_csv_field( $value ) {
		$str = (string) $value;
		if ( '' !== $str && in_array( substr( $str, 0, 1 ), array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
			return "'" . $str;
		}
		return $str;
	}

	protected function redirect_with_message( $type, $message ) {
		$url = add_query_arg(
			array(
				'page'      => 'ab-appointments',
				'ab_notice' => $type,
				'ab_msg'    => rawurlencode( $message ),
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}
}
