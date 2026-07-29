<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AB\Includes\Language\Translation_Service;

// Load language-specific strings for active WP / WPML language
$_cur_lang  = Translation_Service::get_current_language();
$_lang_strs = Translation_Service::get_i18n_strings( $_cur_lang );
$t          = function( $key, $fallback ) use ( $_lang_strs ) {
	return ! empty( $_lang_strs[ $key ] ) ? $_lang_strs[ $key ] : $fallback;
};
?>
<div class="wrap ab-wrap">
	<h1><?php echo esc_html( $t( 'help_page_title', __( 'Help & Documentation', 'appointment-booking-system' ) ) ); ?></h1>

	<div class="ab-help-box">
		<h2><?php echo esc_html( $t( 'help_ver_title', __( 'Plugin Version', 'appointment-booking-system' ) ) ); ?></h2>
		<p><?php echo esc_html( AB_VERSION ); ?></p>

		<h2><?php echo esc_html( $t( 'help_sc_title', __( 'Shortcode', 'appointment-booking-system' ) ) ); ?></h2>
		<p><?php echo esc_html( $t( 'help_sc_desc', __( 'Use the shortcode below on any page to display the booking form. It works inside the Divi Text, Code, or Shortcode module with no dependency on any Divi modules.', 'appointment-booking-system' ) ) ); ?></p>
		<p><code>[appointment_booking]</code></p>
		<p><?php echo esc_html( $t( 'help_sc_auto', __( 'The booking form will automatically load all available categories, doctors, services and appointment slots.', 'appointment-booking-system' ) ) ); ?></p>

		<h2><?php echo esc_html( $t( 'help_gs_title', __( 'Getting Started', 'appointment-booking-system' ) ) ); ?></h2>
		<ol>
			<li><?php echo esc_html( $t( 'help_gs_1', __( 'Create at least one Treatment Category.', 'appointment-booking-system' ) ) ); ?></li>
			<li><?php echo esc_html( $t( 'help_gs_2', __( 'Add Doctors and assign them to one or more categories.', 'appointment-booking-system' ) ) ); ?></li>
			<li><?php echo esc_html( $t( 'help_gs_3', __( 'Add Services under each category.', 'appointment-booking-system' ) ) ); ?></li>
			<li><?php echo esc_html( $t( 'help_gs_4', __( 'Configure each doctor\'s weekly availability and any holidays.', 'appointment-booking-system' ) ) ); ?></li>
			<li><?php echo esc_html( $t( 'help_gs_5', __( 'Place the [appointment_booking] shortcode on any page.', 'appointment-booking-system' ) ) ); ?></li>
		</ol>

		<h2><?php echo esc_html( $t( 'help_sup_title', __( 'Support', 'appointment-booking-system' ) ) ); ?></h2>
		<p><?php echo esc_html( $t( 'help_sup_desc', __( 'For support, please contact your plugin developer or agency.', 'appointment-booking-system' ) ) ); ?></p>
	</div>
</div>
