<?php
namespace AB\Frontend\Shortcodes;

use AB\Includes\Models\Category_Model;
use AB\Includes\Security\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the [appointment_booking] multi-step wizard markup.
 * All dynamic data (doctors, services, dates, slots) loads via AJAX,
 * so the shortcode output itself stays lightweight and cacheable.
 */
class Booking_Shortcode {

	/**
	 * @param array $atts Shortcode attributes (currently unused, reserved for future).
	 * @return string
	 */
	public function render( $atts = array() ) {
		// Force frontend assets to load even on builders that skip has_shortcode() detection.
		add_filter( 'ab_force_enqueue_assets', '__return_true' );

		$category_model = new Category_Model();
		$categories     = $category_model->all( true );

		if ( ! $categories ) {
			return '<div class="ab-notice">' . esc_html__( 'Appointment booking is not available yet. Please check back soon.', 'appointment-booking-system' ) . '</div>';
		}

		ob_start();
		include AB_PLUGIN_DIR . 'frontend/views/booking-form.php';
		return ob_get_clean();
	}
}
