<?php
namespace AB\Admin\Controllers;

use AB\Includes\Security\Security;
use AB\Includes\Models\Category_Model;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Category_Controller {

	public function __construct() {
		add_action( 'admin_post_ab_save_category', array( $this, 'save' ) );
		add_action( 'admin_post_ab_delete_category', array( $this, 'delete' ) );
	}

	public function save() {
		Security::verify_admin_request( '_wpnonce', false );

		$model = new Category_Model();

		$id             = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$name           = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$slug           = isset( $_POST['slug'] ) && $_POST['slug']
			? sanitize_title( wp_unslash( $_POST['slug'] ) )
			: sanitize_title( $name );
		$description    = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';
		$icon           = isset( $_POST['icon'] ) ? esc_url_raw( wp_unslash( $_POST['icon'] ) ) : '';
		$status         = isset( $_POST['status'] ) ? 1 : 0;
		$display_order  = isset( $_POST['display_order'] ) ? absint( $_POST['display_order'] ) : 0;

		$errors = array();
		if ( '' === $name ) {
			$errors[] = __( 'Category name is required.', 'appointment-booking-system' );
		}
		if ( $model->name_exists( $name, $id ) || $model->slug_exists( $slug, $id ) ) {
			$errors[] = __( 'A category with this name already exists.', 'appointment-booking-system' );
		}

		if ( $errors ) {
			$this->redirect_with_message( 'error', implode( ' ', $errors ) );
		}

		$data = array(
			'name'          => $name,
			'slug'          => $slug,
			'description'   => $description,
			'icon'          => $icon,
			'status'        => $status,
			'display_order' => $display_order,
		);

		if ( $id ) {
			$model->update( $id, $data );
			\AB\Includes\Logger::log( 'category', 'updated', 'Updated category "' . $name . '"', $data );
			$this->redirect_with_message( 'success', __( 'Category updated successfully.', 'appointment-booking-system' ) );
		} else {
			$new_id = $model->insert( $data );
			\AB\Includes\Logger::log( 'category', 'created', 'Created category "' . $name . '"', array_merge( array( 'id' => $new_id ), $data ) );
			$this->redirect_with_message( 'success', __( 'Category created successfully.', 'appointment-booking-system' ) );
		}
	}

	public function delete() {
		Security::verify_admin_request( '_wpnonce', false );
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( $id ) {
			$cat = ( new Category_Model() )->find( $id );
			$cat_name = $cat ? $cat['name'] : '#' . $id;
			( new Category_Model() )->delete( $id );
			\AB\Includes\Logger::log( 'category', 'deleted', 'Deleted category "' . $cat_name . '" (ID: ' . $id . ')' );
		}
		$this->redirect_with_message( 'success', __( 'Category deleted.', 'appointment-booking-system' ) );
	}

	protected function redirect_with_message( $type, $message ) {
		$url = add_query_arg(
			array(
				'page'      => 'ab-categories',
				'ab_notice' => $type,
				'ab_msg'    => rawurlencode( $message ),
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}
}
