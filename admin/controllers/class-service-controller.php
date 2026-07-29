<?php
namespace AB\Admin\Controllers;

use AB\Includes\Security\Security;
use AB\Includes\Models\Service_Model;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Service_Controller {

	public function __construct() {
		add_action( 'admin_post_ab_save_service', array( $this, 'save' ) );
		add_action( 'admin_post_ab_delete_service', array( $this, 'delete' ) );
	}

	public function save() {
		Security::verify_admin_request( '_wpnonce', false );

		$model = new Service_Model();

		$id              = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$name            = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$slug            = isset( $_POST['slug'] ) && $_POST['slug'] ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : sanitize_title( $name );
		$category_id     = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;
		$duration_hour   = isset( $_POST['duration_hour'] ) ? absint( $_POST['duration_hour'] ) : 0;
		$duration_minute = isset( $_POST['duration_minute'] ) ? absint( $_POST['duration_minute'] ) : 0;
		$description     = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';
		$status          = isset( $_POST['status'] ) ? 1 : 0;

		$errors = array();
		if ( '' === $name ) {
			$errors[] = __( 'Service name is required.', 'appointment-booking-system' );
		}
		if ( ! $category_id ) {
			$errors[] = __( 'Category is required.', 'appointment-booking-system' );
		}
		if ( 0 === $duration_hour && 0 === $duration_minute ) {
			$errors[] = __( 'Duration is required.', 'appointment-booking-system' );
		}

		if ( $errors ) {
			$this->redirect_with_message( 'error', implode( ' ', $errors ) );
		}

		$data = array(
			'name'            => $name,
			'slug'            => $slug,
			'category_id'     => $category_id,
			'duration_hour'   => $duration_hour,
			'duration_minute' => $duration_minute,
			'description'     => $description,
			'status'          => $status,
		);

		if ( $id ) {
			$model->update( $id, $data );
			\AB\Includes\Logger::log( 'service', 'updated', 'Updated service "' . $name . '"', $data );
		} else {
			$new_id = $model->insert( $data );
			\AB\Includes\Logger::log( 'service', 'created', 'Created service "' . $name . '"', array_merge( array( 'id' => $new_id ), $data ) );
		}

		$this->redirect_with_message( 'success', __( 'Service saved successfully.', 'appointment-booking-system' ) );
	}

	public function delete() {
		Security::verify_admin_request( '_wpnonce', false );
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( $id ) {
			$srv = ( new Service_Model() )->find( $id );
			$srv_name = $srv ? $srv['name'] : '#' . $id;
			( new Service_Model() )->delete( $id );
			\AB\Includes\Logger::log( 'service', 'deleted', 'Deleted service "' . $srv_name . '" (ID: ' . $id . ')' );
		}
		$this->redirect_with_message( 'success', __( 'Service deleted.', 'appointment-booking-system' ) );
	}

	protected function redirect_with_message( $type, $message ) {
		$url = add_query_arg(
			array(
				'page'      => 'ab-services',
				'ab_notice' => $type,
				'ab_msg'    => rawurlencode( $message ),
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}
}
