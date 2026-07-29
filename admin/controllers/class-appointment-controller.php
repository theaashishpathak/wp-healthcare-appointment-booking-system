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
			( new Appointment_Model() )->update( $id, array( 'status' => $status ) );
		}

		$this->redirect_with_message( 'success', __( 'Appointment status updated.', 'appointment-booking-system' ) );
	}

	public function delete() {
		Security::verify_admin_request( '_wpnonce', false );
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( $id ) {
			( new Appointment_Model() )->delete( $id );
		}
		$this->redirect_with_message( 'success', __( 'Appointment deleted.', 'appointment-booking-system' ) );
	}

	public function export_csv() {
		Security::verify_admin_request( '_wpnonce', false );

		$model  = new Appointment_Model();
		$result = $model->search( array( 'per_page' => 100000, 'page' => 1 ) );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=appointments-export-' . gmdate( 'Y-m-d' ) . '.csv' );

		$out = fopen( 'php://output', 'w' );
		fputcsv(
			$out,
			array( 'Booking ID', 'Patient Name', 'Phone', 'Email', 'Doctor ID', 'Category ID', 'Date', 'Time', 'Status', 'Created At' )
		);
		foreach ( $result['items'] as $row ) {
			fputcsv(
				$out,
				array(
					$row['booking_id'],
					$row['patient_name'],
					$row['phone'],
					$row['email'],
					$row['doctor_id'],
					$row['category_id'],
					$row['appointment_date'],
					$row['appointment_time'],
					$row['status'],
					$row['created_at'],
				)
			);
		}
		fclose( $out );
		exit;
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
