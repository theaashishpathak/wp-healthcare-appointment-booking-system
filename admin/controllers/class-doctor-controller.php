<?php
namespace AB\Admin\Controllers;

use AB\Includes\Security\Security;
use AB\Includes\Models\Doctor_Model;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Doctor_Controller {

	public function __construct() {
		add_action( 'admin_post_ab_save_doctor', array( $this, 'save' ) );
		add_action( 'admin_post_ab_delete_doctor', array( $this, 'delete' ) );
	}

	public function save() {
		Security::verify_admin_request( '_wpnonce', false );

		$model = new Doctor_Model();

		$id             = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$name           = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$email          = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$phone          = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$qualification  = isset( $_POST['qualification'] ) ? sanitize_text_field( wp_unslash( $_POST['qualification'] ) ) : '';
		$specialization = isset( $_POST['specialization'] ) ? sanitize_text_field( wp_unslash( $_POST['specialization'] ) ) : '';
		$experience     = isset( $_POST['experience'] ) ? sanitize_text_field( wp_unslash( $_POST['experience'] ) ) : '';
		$bio            = isset( $_POST['bio'] ) ? wp_kses_post( wp_unslash( $_POST['bio'] ) ) : '';
		$image          = isset( $_POST['image'] ) ? esc_url_raw( wp_unslash( $_POST['image'] ) ) : '';
		$status         = isset( $_POST['status'] ) ? 1 : 0;
		$category_ids   = isset( $_POST['category_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['category_ids'] ) ) : array();

		$errors = array();
		if ( '' === $name ) {
			$errors[] = __( 'Doctor name is required.', 'appointment-booking-system' );
		}
		if ( '' === $email || ! is_email( $email ) ) {
			$errors[] = __( 'A valid email address is required.', 'appointment-booking-system' );
		} elseif ( $model->email_exists( $email, $id ) ) {
			$errors[] = __( 'This email is already used by another doctor.', 'appointment-booking-system' );
		}
		if ( empty( $category_ids ) ) {
			$errors[] = __( 'Please select at least one treatment category.', 'appointment-booking-system' );
		}

		if ( $errors ) {
			$this->redirect_with_message( 'error', implode( ' ', $errors ) );
		}

		$data = array(
			'name'           => $name,
			'email'          => $email,
			'phone'          => $phone,
			'qualification'  => $qualification,
			'specialization' => $specialization,
			'experience'     => $experience,
			'bio'            => $bio,
			'image'          => $image,
			'status'         => $status,
		);

		if ( $id ) {
			$model->update( $id, $data );
		} else {
			$id = $model->insert( $data );
		}

		$model->sync_categories( $id, $category_ids );

		$this->redirect_with_message( 'success', __( 'Doctor saved successfully.', 'appointment-booking-system' ) );
	}

	public function delete() {
		Security::verify_admin_request( '_wpnonce', false );
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( $id ) {
			( new Doctor_Model() )->delete( $id );
		}
		$this->redirect_with_message( 'success', __( 'Doctor deleted.', 'appointment-booking-system' ) );
	}

	protected function redirect_with_message( $type, $message ) {
		$url = add_query_arg(
			array(
				'page'      => 'ab-doctors',
				'ab_notice' => $type,
				'ab_msg'    => rawurlencode( $message ),
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}
}
