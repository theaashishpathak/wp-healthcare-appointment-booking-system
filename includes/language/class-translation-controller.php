<?php
/**
 * Translation Controller
 *
 * Handles admin requests for creating/editing translations.
 * Delegates to WPML_Adapter for actual WPML operations.
 *
 * @package    AB
 * @subpackage Language
 * @since      1.0.0
 */

namespace AB\Includes\Language;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Translation_Controller
 */
class Translation_Controller {

	/**
	 * WPML_Adapter instance
	 *
	 * @var WPML_Adapter
	 */
	private $wpml_adapter;

	/**
	 * Constructor
	 */
	public function __construct() {
		// Only initialize if WPML is active
		if ( ! Translation_Service::is_wpml_active() ) {
			return;
		}

		$this->wpml_adapter = new WPML_Adapter();

		add_action( 'admin_post_ab_create_translation', array( $this, 'handle_create_translation' ) );
		add_action( 'admin_post_ab_edit_translation', array( $this, 'handle_edit_translation' ) );
		add_action( 'admin_post_ab_save_translation', array( $this, 'handle_save_translation' ) );
	}

	/**
	 * Handle creating a NEW translation.
	 *
	 * Triggered when admin clicks "+" icon.
	 */
	public function handle_create_translation() {

		// Verify nonce
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'ab_create_translation' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'appointment-booking-system' ) );
		}

		// Verify permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission.', 'appointment-booking-system' ) );
		}

		// Validate required parameters
		if ( ! isset( $_GET['type'], $_GET['item_id'], $_GET['lang'] ) ) {
			wp_die( esc_html__( 'Missing required parameters.', 'appointment-booking-system' ) );
		}

		$type    = sanitize_text_field( wp_unslash( $_GET['type'] ) );
		$item_id = absint( $_GET['item_id'] );
		$lang    = sanitize_text_field( wp_unslash( $_GET['lang'] ) );

		// Validate type
		$valid_types = array(
			Translation_Service::TYPE_DOCTOR,
			Translation_Service::TYPE_CATEGORY,
			Translation_Service::TYPE_SERVICE,
		);

		if ( ! in_array( $type, $valid_types, true ) ) {
			wp_die( esc_html__( 'Invalid translation type.', 'appointment-booking-system' ) );
		}

		// Open WPML translation editor
		$this->wpml_adapter->open_translation_editor( $type, $item_id, $lang );
	}

	/**
	 * Handle EDITING an existing translation.
	 *
	 * Triggered when admin clicks "✓" icon.
	 */
	public function handle_edit_translation() {

		// Verify nonce
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'ab_edit_translation' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'appointment-booking-system' ) );
		}

		// Verify permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission.', 'appointment-booking-system' ) );
		}

		// Validate required parameters
		if ( ! isset( $_GET['type'], $_GET['item_id'], $_GET['lang'] ) ) {
			wp_die( esc_html__( 'Missing required parameters.', 'appointment-booking-system' ) );
		}

		$type    = sanitize_text_field( wp_unslash( $_GET['type'] ) );
		$item_id = absint( $_GET['item_id'] );
		$lang    = sanitize_text_field( wp_unslash( $_GET['lang'] ) );

		// Open existing translation in WPML editor
		$this->wpml_adapter->edit_translation( $type, $item_id, $lang );
	}

	/**
	 * Save manual translation.
	 */
	public function handle_save_translation() {
		// Verify nonce
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'ab_translation_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'appointment-booking-system' ) );
		}

		// Verify permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission.', 'appointment-booking-system' ) );
		}

		// Validate required parameters
		if ( ! isset( $_POST['type'], $_POST['item_id'], $_POST['lang'] ) ) {
			wp_die( esc_html__( 'Missing required parameters.', 'appointment-booking-system' ) );
		}

		$type    = sanitize_text_field( wp_unslash( $_POST['type'] ) );
		$item_id = absint( $_POST['item_id'] );
		$lang    = sanitize_text_field( wp_unslash( $_POST['lang'] ) );

		$valid_types = array(
			Translation_Service::TYPE_DOCTOR,
			Translation_Service::TYPE_CATEGORY,
			Translation_Service::TYPE_SERVICE,
		);

		if ( ! in_array( $type, $valid_types, true ) ) {
			wp_die( esc_html__( 'Invalid translation type.', 'appointment-booking-system' ) );
		}

		global $wpdb;

		// Find existing translation mapping
		$translated_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT translated_object_id FROM {$wpdb->prefix}ab_translation_map
			 WHERE object_type = %s AND source_object_id = %d AND language_code = %s",
			$type,
			$item_id,
			$lang
		) );

		// Purge stale/placeholder mappings where WPML set translated_object_id = source_object_id.
		// This ensures we never UPDATE the original row with translated content.
		if ( $translated_id && intval( $translated_id ) === intval( $item_id ) ) {
			$wpdb->delete(
				$wpdb->prefix . 'ab_translation_map',
				array(
					'object_type'          => $type,
					'source_object_id'     => $item_id,
					'translated_object_id' => $item_id,
					'language_code'        => $lang,
				)
			);
			$translated_id = null;
		}

		// A real duplicate exists only when translated_id is a different, positive row.
		$has_duplicate = ( $translated_id && intval( $translated_id ) !== intval( $item_id ) );

		if ( $type === Translation_Service::TYPE_DOCTOR ) {
			// Save Doctor
			$name           = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			$qualification  = isset( $_POST['qualification'] ) ? sanitize_text_field( wp_unslash( $_POST['qualification'] ) ) : '';
			$specialization = isset( $_POST['specialization'] ) ? sanitize_text_field( wp_unslash( $_POST['specialization'] ) ) : '';
			$experience     = isset( $_POST['experience'] ) ? sanitize_text_field( wp_unslash( $_POST['experience'] ) ) : '';
			$bio            = isset( $_POST['bio'] ) ? wp_kses_post( wp_unslash( $_POST['bio'] ) ) : '';

			if ( empty( $name ) ) {
				wp_die( esc_html__( 'Doctor name is required.', 'appointment-booking-system' ) );
			}

			// Get source doctor for non-translatable fields
			$source_doc = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}ab_doctors WHERE id = %d",
				$item_id
			), ARRAY_A );

			if ( ! $source_doc ) {
				wp_die( esc_html__( 'Original doctor not found.', 'appointment-booking-system' ) );
			}

			$data = array(
				'name'           => $name,
				'qualification'  => $qualification,
				'specialization' => $specialization,
				'experience'     => $experience,
				'bio'            => $bio,
				'image'          => $source_doc['image'],
				'email'          => $source_doc['email'],
				'phone'          => $source_doc['phone'],
				'status'         => $source_doc['status'],
			);

			if ( $has_duplicate ) {
				$wpdb->update( $wpdb->prefix . 'ab_doctors', $data, array( 'id' => $translated_id ) );
			} else {
				$wpdb->insert( $wpdb->prefix . 'ab_doctors', $data );
				$translated_id = $wpdb->insert_id;
			}

			// Sync doctor categories
			$doctor_model = new \AB\Includes\Models\Doctor_Model();
			$cat_ids = $doctor_model->get_category_ids( $item_id );
			$doctor_model->sync_categories( $translated_id, $cat_ids );

		} elseif ( $type === Translation_Service::TYPE_CATEGORY ) {
			// Save Category
			$name        = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			$description = isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '';

			if ( empty( $name ) ) {
				wp_die( esc_html__( 'Category name is required.', 'appointment-booking-system' ) );
			}

			$source_cat = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}ab_categories WHERE id = %d",
				$item_id
			), ARRAY_A );

			if ( ! $source_cat ) {
				wp_die( esc_html__( 'Original category not found.', 'appointment-booking-system' ) );
			}

			$slug = sanitize_title( $name );
			if ( ! $has_duplicate ) {
				// Ensure slug is unique
				$slug .= '-' . $lang;
			}

			$data = array(
				'name'          => $name,
				'slug'          => $slug,
				'description'   => $description,
				'icon'          => $source_cat['icon'],
				'status'        => $source_cat['status'],
				'display_order' => $source_cat['display_order'],
			);

			if ( $has_duplicate ) {
				unset( $data['slug'] ); // Keep existing slug
				$wpdb->update( $wpdb->prefix . 'ab_categories', $data, array( 'id' => $translated_id ) );
			} else {
				$wpdb->insert( $wpdb->prefix . 'ab_categories', $data );
				$translated_id = $wpdb->insert_id;
			}

		} elseif ( $type === Translation_Service::TYPE_SERVICE ) {
			// Save Service
			$name        = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			$description = isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '';

			if ( empty( $name ) ) {
				wp_die( esc_html__( 'Service name is required.', 'appointment-booking-system' ) );
			}

			$source_service = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}ab_services WHERE id = %d",
				$item_id
			), ARRAY_A );

			if ( ! $source_service ) {
				wp_die( esc_html__( 'Original service not found.', 'appointment-booking-system' ) );
			}

			$slug = sanitize_title( $name );
			if ( ! $has_duplicate ) {
				$slug .= '-' . $lang;
			}

			$data = array(
				'category_id'     => $source_service['category_id'],
				'name'            => $name,
				'slug'            => $slug,
				'duration_hour'   => $source_service['duration_hour'],
				'duration_minute' => $source_service['duration_minute'],
				'description'     => $description,
				'status'          => $source_service['status'],
			);

			if ( $has_duplicate ) {
				unset( $data['slug'] );
				$wpdb->update( $wpdb->prefix . 'ab_services', $data, array( 'id' => $translated_id ) );
			} else {
				$wpdb->insert( $wpdb->prefix . 'ab_services', $data );
				$translated_id = $wpdb->insert_id;
			}
		}

		// Save the mapping
		Translation_Service::save_translation( $type, $item_id, $translated_id, $lang );

		// Log activity event
		\AB\Includes\Logger::log( 'translation', 'updated', 'Saved ' . strtoupper( $lang ) . ' translation for ' . ucfirst( $type ) . ' ID ' . $item_id, array( 'type' => $type, 'source_id' => $item_id, 'translated_id' => $translated_id, 'lang' => $lang ) );

		// Update mapping status to completed
		$wpdb->update(
			$wpdb->prefix . 'ab_translation_map',
			array( 'status' => 'completed', 'updated_at' => current_time( 'mysql' ) ),
			array( 'object_type' => $type, 'source_object_id' => $item_id, 'language_code' => $lang )
		);

		// Redirect with success message
		$page_map = array(
			Translation_Service::TYPE_DOCTOR   => 'ab-doctors',
			Translation_Service::TYPE_CATEGORY => 'ab-categories',
			Translation_Service::TYPE_SERVICE  => 'ab-services',
		);

		$redirect_page = $page_map[ $type ] ?? 'ab-dashboard';

		$url = add_query_arg(
			array(
				'page'      => $redirect_page,
				'ab_notice' => 'success',
				'ab_msg'    => rawurlencode( __( 'Translation saved successfully.', 'appointment-booking-system' ) ),
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $url );
		exit;
	}
}