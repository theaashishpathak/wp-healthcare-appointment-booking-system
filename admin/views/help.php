<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap ab-wrap">
	<h1><?php esc_html_e( 'Help & Documentation', 'appointment-booking-system' ); ?></h1>

	<div class="ab-help-box">
		<h2><?php esc_html_e( 'Plugin Version', 'appointment-booking-system' ); ?></h2>
		<p><?php echo esc_html( AB_VERSION ); ?></p>

		<h2><?php esc_html_e( 'Shortcode', 'appointment-booking-system' ); ?></h2>
		<p><?php esc_html_e( 'Use the shortcode below on any page to display the booking form. It works inside the Divi Text, Code, or Shortcode module with no dependency on any Divi modules.', 'appointment-booking-system' ); ?></p>
		<p><code>[appointment_booking]</code></p>
		<p><?php esc_html_e( 'The booking form will automatically load all available categories, doctors, services and appointment slots.', 'appointment-booking-system' ); ?></p>

		<h2><?php esc_html_e( 'Getting Started', 'appointment-booking-system' ); ?></h2>
		<ol>
			<li><?php esc_html_e( 'Create at least one Treatment Category.', 'appointment-booking-system' ); ?></li>
			<li><?php esc_html_e( 'Add Doctors and assign them to one or more categories.', 'appointment-booking-system' ); ?></li>
			<li><?php esc_html_e( 'Add Services under each category.', 'appointment-booking-system' ); ?></li>
			<li><?php esc_html_e( 'Configure each doctor\'s weekly availability and any holidays.', 'appointment-booking-system' ); ?></li>
			<li><?php esc_html_e( 'Place the [appointment_booking] shortcode on any page.', 'appointment-booking-system' ); ?></li>
		</ol>

		<h2><?php esc_html_e( 'Support', 'appointment-booking-system' ); ?></h2>
		<p><?php esc_html_e( 'For support, please contact your plugin developer or agency.', 'appointment-booking-system' ); ?></p>
	</div>
</div>
