<?php
namespace AB\Frontend;

use AB\Includes\Security\Security;
use AB\Frontend\Shortcodes\Booking_Shortcode;
use AB\Frontend\Ajax\Frontend_Ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the [appointment_booking] shortcode, frontend assets, and
 * boots the AJAX handlers used by the multi-step booking wizard.
 */
class Frontend {

	public function __construct() {
		add_action( 'init', array( $this, 'register_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ) );

		new Frontend_Ajax();
	}

	public function register_shortcode() {
		add_shortcode( 'appointment_booking', array( new Booking_Shortcode(), 'render' ) );
	}

	/**
	 * Only load assets on pages that actually contain the shortcode,
	 * to avoid bloating every page on the site (performance requirement).
	 */
	public function maybe_enqueue_assets() {
		global $post;

		$has_shortcode = is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'appointment_booking' );

		/**
		 * Allow builders like Divi (Code / Text modules) that don't run
		 * has_shortcode() reliably to force-enqueue assets.
		 */
		$force = apply_filters( 'ab_force_enqueue_assets', false );

		if ( ! $has_shortcode && ! $force ) {
			return;
		}

		wp_enqueue_style( 'ab-frontend-css', AB_PLUGIN_URL . 'assets/css/frontend.css', array(), AB_VERSION );
		wp_enqueue_script( 'ab-frontend-js', AB_PLUGIN_URL . 'assets/js/frontend.js', array(), AB_VERSION, true );

		// Load language-specific static-string overrides (built-in + admin custom overrides).
		$cur_lang  = \AB\Includes\Language\Translation_Service::get_current_language();
		$lang_strs = \AB\Includes\Language\Translation_Service::get_i18n_strings( $cur_lang );

		// Base English defaults.
		$i18n_base = array(
			'selectCategory'        => __( 'Please select a treatment category to continue.', 'appointment-booking-system' ),
			'selectDoctor'          => __( 'Please select a doctor to continue.', 'appointment-booking-system' ),
			'selectService'         => __( 'Please select at least one service to continue.', 'appointment-booking-system' ),
			'selectDate'            => __( 'Please choose an appointment date to continue.', 'appointment-booking-system' ),
			'selectTime'            => __( 'Please choose an appointment time to continue.', 'appointment-booking-system' ),
			'requiredField'         => __( 'This field is required.', 'appointment-booking-system' ),
			'invalidEmail'          => __( 'Please enter a valid email address.', 'appointment-booking-system' ),
			'invalidPhone'          => __( 'Please enter a valid phone number.', 'appointment-booking-system' ),
			'genericError'          => __( 'Something went wrong. Please try again.', 'appointment-booking-system' ),
			'noSlots'               => __( 'No time slots available on this date.', 'appointment-booking-system' ),
			'review_treatment'      => __( 'Treatment', 'appointment-booking-system' ),
			'review_doctor'         => __( 'Doctor', 'appointment-booking-system' ),
			'review_services'       => __( 'Services', 'appointment-booking-system' ),
			'review_date'           => __( 'Appointment Date', 'appointment-booking-system' ),
			'review_time'           => __( 'Appointment Time', 'appointment-booking-system' ),
			'review_total_duration' => __( 'Total Duration', 'appointment-booking-system' ),
			'review_patient_name'   => __( 'Patient Name', 'appointment-booking-system' ),
			'review_email'          => __( 'Email', 'appointment-booking-system' ),
			'review_phone'          => __( 'Phone', 'appointment-booking-system' ),
			'submitting'            => __( 'Submitting…', 'appointment-booking-system' ),
		);

		// Merge: language-specific values override English defaults.
		$i18n_merged = array_merge( $i18n_base, array_intersect_key( $lang_strs, $i18n_base ) );

		wp_localize_script(
			'ab-frontend-js',
			'AB_FRONTEND',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( Security::FRONTEND_NONCE ),
				'lang'    => $cur_lang,
				'colors'  => array(
					'primary'   => ab_get_setting( 'primary_color', '#0B6E4F' ),
					'secondary' => ab_get_setting( 'secondary_color', '#0E2A47' ),
					'radius'    => ab_get_setting( 'border_radius', 8 ),
				),
				'i18n'    => $i18n_merged,
			)
		);
	}
}

