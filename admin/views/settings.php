<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AB\Includes\Language\Translation_Service;

$settings = wp_parse_args( get_option( 'ab_settings', array() ), ab_get_default_settings() );

// Load language-specific strings for active WP / WPML language
$_cur_lang  = Translation_Service::get_current_language();
$_lang_strs = Translation_Service::get_i18n_strings( $_cur_lang );
$t          = function( $key, $fallback ) use ( $_lang_strs ) {
	return ! empty( $_lang_strs[ $key ] ) ? $_lang_strs[ $key ] : $fallback;
};
?>
<div class="wrap ab-wrap">
	<h1><?php echo esc_html( $t( 'set_page_title', __( 'Appointment Booking Settings', 'appointment-booking-system' ) ) ); ?></h1>
	<?php include __DIR__ . '/partials/notice.php'; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'ab_admin_nonce' ); ?>
		<input type="hidden" name="action" value="ab_save_settings" />

		<h2 class="title"><?php echo esc_html( $t( 'set_sec_general', __( 'General', 'appointment-booking-system' ) ) ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="clinic_name"><?php echo esc_html( $t( 'set_clinic_name', __( 'Clinic Name', 'appointment-booking-system' ) ) ); ?></label></th>
				<td><input type="text" id="clinic_name" name="clinic_name" class="regular-text" value="<?php echo esc_attr( $settings['clinic_name'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="clinic_email"><?php echo esc_html( $t( 'set_clinic_email', __( 'Clinic Email', 'appointment-booking-system' ) ) ); ?></label></th>
				<td><input type="email" id="clinic_email" name="clinic_email" class="regular-text" value="<?php echo esc_attr( $settings['clinic_email'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="clinic_phone"><?php echo esc_html( $t( 'field_phone', __( 'Phone Number', 'appointment-booking-system' ) ) ); ?></label></th>
				<td><input type="text" id="clinic_phone" name="clinic_phone" class="regular-text" value="<?php echo esc_attr( $settings['clinic_phone'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label><?php echo esc_html( $t( 'set_timezone', __( 'Timezone', 'appointment-booking-system' ) ) ); ?></label></th>
				<td>
					<input type="text" class="regular-text" value="<?php echo esc_attr( wp_timezone_string() ); ?>" readonly />
					<p class="description"><?php echo esc_html( $t( 'set_tz_desc', __( 'Timezone is managed in WordPress Settings → General.', 'appointment-booking-system' ) ) ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="date_format"><?php echo esc_html( $t( 'set_date_format', __( 'Date Format', 'appointment-booking-system' ) ) ); ?></label></th>
				<td><input type="text" id="date_format" name="date_format" value="<?php echo esc_attr( $settings['date_format'] ); ?>" /> <span class="description"><?php echo esc_html( $t( 'set_df_desc', __( 'PHP date() format, e.g. d M Y', 'appointment-booking-system' ) ) ); ?></span></td>
			</tr>
			<tr>
				<th><label for="time_format"><?php echo esc_html( $t( 'set_time_format', __( 'Time Format', 'appointment-booking-system' ) ) ); ?></label></th>
				<td><input type="text" id="time_format" name="time_format" value="<?php echo esc_attr( $settings['time_format'] ); ?>" /> <span class="description"><?php echo esc_html( $t( 'set_tf_desc', __( 'PHP date() format, e.g. h:i A', 'appointment-booking-system' ) ) ); ?></span></td>
			</tr>
		</table>

		<h2 class="title"><?php echo esc_html( $t( 'set_sec_email', __( 'Email Settings', 'appointment-booking-system' ) ) ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="admin_email"><?php echo esc_html( $t( 'set_admin_email', __( 'Admin Email', 'appointment-booking-system' ) ) ); ?></label></th>
				<td><input type="email" id="admin_email" name="admin_email" class="regular-text" value="<?php echo esc_attr( $settings['admin_email'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="email_subject_customer"><?php echo esc_html( $t( 'set_cust_email_sub', __( 'Customer Email Subject', 'appointment-booking-system' ) ) ); ?></label></th>
				<td><input type="text" id="email_subject_customer" name="email_subject_customer" class="regular-text" value="<?php echo esc_attr( $settings['email_subject_customer'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="email_subject_admin"><?php echo esc_html( $t( 'set_admin_email_sub', __( 'Admin Email Subject', 'appointment-booking-system' ) ) ); ?></label></th>
				<td><input type="text" id="email_subject_admin" name="email_subject_admin" class="regular-text" value="<?php echo esc_attr( $settings['email_subject_admin'] ); ?>" /></td>
			</tr>
		</table>

		<h2 class="title"><?php echo esc_html( $t( 'set_sec_notifications', __( 'Notification Settings', 'appointment-booking-system' ) ) ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php echo esc_html( $t( 'set_cust_email_toggle', __( 'Customer Email', 'appointment-booking-system' ) ) ); ?></th>
				<td><label><input type="checkbox" name="enable_customer_email" <?php checked( $settings['enable_customer_email'] ); ?> /> <?php echo esc_html( $t( 'set_enable_cust_email', __( 'Enable customer confirmation email', 'appointment-booking-system' ) ) ); ?></label></td>
			</tr>
			<tr>
				<th><?php echo esc_html( $t( 'set_admin_email_toggle', __( 'Admin Email', 'appointment-booking-system' ) ) ); ?></th>
				<td><label><input type="checkbox" name="enable_admin_email" <?php checked( $settings['enable_admin_email'] ); ?> /> <?php echo esc_html( $t( 'set_enable_admin_email', __( 'Enable admin notification email', 'appointment-booking-system' ) ) ); ?></label></td>
			</tr>
			<tr>
				<th><?php echo esc_html( $t( 'set_reminder_email_toggle', __( 'Reminder Email', 'appointment-booking-system' ) ) ); ?></th>
				<td><label><input type="checkbox" name="enable_reminder_email" <?php checked( $settings['enable_reminder_email'] ); ?> /> <?php echo esc_html( $t( 'set_enable_reminder_email', __( 'Enable reminder email (requires WP-Cron)', 'appointment-booking-system' ) ) ); ?></label></td>
			</tr>
		</table>

		<h2 class="title"><?php echo esc_html( $t( 'set_sec_appearance', __( 'Appearance', 'appointment-booking-system' ) ) ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="primary_color"><?php echo esc_html( $t( 'set_primary_color', __( 'Primary Color', 'appointment-booking-system' ) ) ); ?></label></th>
				<td><input type="text" id="primary_color" name="primary_color" class="ab-color-field" value="<?php echo esc_attr( $settings['primary_color'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="secondary_color"><?php echo esc_html( $t( 'set_secondary_color', __( 'Secondary Color', 'appointment-booking-system' ) ) ); ?></label></th>
				<td><input type="text" id="secondary_color" name="secondary_color" class="ab-color-field" value="<?php echo esc_attr( $settings['secondary_color'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="border_radius"><?php echo esc_html( $t( 'set_border_radius', __( 'Border Radius (px)', 'appointment-booking-system' ) ) ); ?></label></th>
				<td><input type="number" id="border_radius" name="border_radius" min="0" max="40" value="<?php echo esc_attr( $settings['border_radius'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="button_style"><?php echo esc_html( $t( 'set_button_style', __( 'Button Style', 'appointment-booking-system' ) ) ); ?></label></th>
				<td>
					<select id="button_style" name="button_style">
						<option value="solid" <?php selected( $settings['button_style'], 'solid' ); ?>><?php echo esc_html( $t( 'set_solid', __( 'Solid', 'appointment-booking-system' ) ) ); ?></option>
						<option value="outline" <?php selected( $settings['button_style'], 'outline' ); ?>><?php echo esc_html( $t( 'set_outline', __( 'Outline', 'appointment-booking-system' ) ) ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="loader_style"><?php echo esc_html( $t( 'set_loader_style', __( 'Loader Style', 'appointment-booking-system' ) ) ); ?></label></th>
				<td>
					<select id="loader_style" name="loader_style">
						<option value="spinner" <?php selected( $settings['loader_style'], 'spinner' ); ?>><?php echo esc_html( $t( 'set_spinner', __( 'Spinner', 'appointment-booking-system' ) ) ); ?></option>
						<option value="dots" <?php selected( $settings['loader_style'], 'dots' ); ?>><?php echo esc_html( $t( 'set_dots', __( 'Dots', 'appointment-booking-system' ) ) ); ?></option>
					</select>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php echo esc_html( $t( 'set_sec_advanced', __( 'Advanced', 'appointment-booking-system' ) ) ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="recaptcha_site_key"><?php echo esc_html( $t( 'set_recaptcha_site_key', __( 'Google reCAPTCHA Site Key', 'appointment-booking-system' ) ) ); ?></label></th>
				<td><input type="text" id="recaptcha_site_key" name="recaptcha_site_key" class="regular-text" value="<?php echo esc_attr( $settings['recaptcha_site_key'] ); ?>" /> <span class="description"><?php echo esc_html( $t( 'set_recaptcha_desc', __( '(Reserved for future release)', 'appointment-booking-system' ) ) ); ?></span></td>
			</tr>
			<tr>
				<th><label for="recaptcha_secret_key"><?php echo esc_html( $t( 'set_recaptcha_secret_key', __( 'Google reCAPTCHA Secret Key', 'appointment-booking-system' ) ) ); ?></label></th>
				<td><input type="text" id="recaptcha_secret_key" name="recaptcha_secret_key" class="regular-text" value="<?php echo esc_attr( $settings['recaptcha_secret_key'] ); ?>" /></td>
			</tr>
			<tr>
				<th><?php echo esc_html( $t( 'set_smtp_title', __( 'SMTP', 'appointment-booking-system' ) ) ); ?></th>
				<td><p class="description"><?php echo esc_html( $t( 'set_smtp_desc', __( 'Emails are sent via wp_mail() and are fully compatible with any SMTP plugin you already have configured.', 'appointment-booking-system' ) ) ); ?></p></td>
			</tr>
			<tr>
				<th><?php echo esc_html( $t( 'set_uninstall_title', __( 'Uninstall', 'appointment-booking-system' ) ) ); ?></th>
				<td><label><input type="checkbox" name="keep_data_on_uninstall" <?php checked( $settings['keep_data_on_uninstall'] ); ?> /> <?php echo esc_html( $t( 'set_keep_data_desc', __( 'Keep all appointment data when the plugin is deleted', 'appointment-booking-system' ) ) ); ?></label></td>
			</tr>
		</table>

		<p class="submit"><button type="submit" class="button button-primary"><?php echo esc_html( $t( 'set_btn_save', __( 'Save Settings', 'appointment-booking-system' ) ) ); ?></button></p>
	</form>
</div>