<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper function to get UTC offset for a timezone
 */
function ab_get_timezone_offset( $timezone ) {
    try {
        $date_time_zone = new DateTimeZone( $timezone );
        $date_time      = new DateTime( 'now', $date_time_zone );
        $offset         = $date_time_zone->getOffset( $date_time );
        
        $hours   = intval( $offset / 3600 );
        $minutes = abs( intval( ( $offset % 3600 ) / 60 ) );
        
        return sprintf( '%+d:%02d', $hours, $minutes );
    } catch ( Exception $e ) {
        return '';
    }
}

$settings = wp_parse_args( get_option( 'ab_settings', array() ), ab_get_default_settings() );
?>
<div class="wrap ab-wrap">
	<h1><?php esc_html_e( 'Appointment Booking Settings', 'appointment-booking-system' ); ?></h1>
	<?php include __DIR__ . '/partials/notice.php'; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'ab_admin_nonce' ); ?>
		<input type="hidden" name="action" value="ab_save_settings" />

		<h2 class="title"><?php esc_html_e( 'General', 'appointment-booking-system' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="clinic_name"><?php esc_html_e( 'Clinic Name', 'appointment-booking-system' ); ?></label></th>
				<td><input type="text" id="clinic_name" name="clinic_name" class="regular-text" value="<?php echo esc_attr( $settings['clinic_name'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="clinic_email"><?php esc_html_e( 'Clinic Email', 'appointment-booking-system' ); ?></label></th>
				<td><input type="email" id="clinic_email" name="clinic_email" class="regular-text" value="<?php echo esc_attr( $settings['clinic_email'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="clinic_phone"><?php esc_html_e( 'Phone Number', 'appointment-booking-system' ); ?></label></th>
				<td><input type="text" id="clinic_phone" name="clinic_phone" class="regular-text" value="<?php echo esc_attr( $settings['clinic_phone'] ); ?>" /></td>
			</tr>
			<tr>
    <th><label><?php esc_html_e( 'Timezone', 'appointment-booking-system' ); ?></label></th>
    <td>
        <input type="text" class="regular-text" value="<?php echo esc_attr( wp_timezone_string() ); ?>" readonly />
        <p class="description"><?php esc_html_e( 'Timezone is managed in WordPress Settings → General.', 'appointment-booking-system' ); ?></p>
    </td>
</tr>
			<tr>
				<th><label for="date_format"><?php esc_html_e( 'Date Format', 'appointment-booking-system' ); ?></label></th>
				<td><input type="text" id="date_format" name="date_format" value="<?php echo esc_attr( $settings['date_format'] ); ?>" /> <span class="description"><?php esc_html_e( 'PHP date() format, e.g. d M Y', 'appointment-booking-system' ); ?></span></td>
			</tr>
			<tr>
				<th><label for="time_format"><?php esc_html_e( 'Time Format', 'appointment-booking-system' ); ?></label></th>
				<td><input type="text" id="time_format" name="time_format" value="<?php echo esc_attr( $settings['time_format'] ); ?>" /> <span class="description"><?php esc_html_e( 'PHP date() format, e.g. h:i A', 'appointment-booking-system' ); ?></span></td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Email Settings', 'appointment-booking-system' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="admin_email"><?php esc_html_e( 'Admin Email', 'appointment-booking-system' ); ?></label></th>
				<td><input type="email" id="admin_email" name="admin_email" class="regular-text" value="<?php echo esc_attr( $settings['admin_email'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="email_subject_customer"><?php esc_html_e( 'Customer Email Subject', 'appointment-booking-system' ); ?></label></th>
				<td><input type="text" id="email_subject_customer" name="email_subject_customer" class="regular-text" value="<?php echo esc_attr( $settings['email_subject_customer'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="email_subject_admin"><?php esc_html_e( 'Admin Email Subject', 'appointment-booking-system' ); ?></label></th>
				<td><input type="text" id="email_subject_admin" name="email_subject_admin" class="regular-text" value="<?php echo esc_attr( $settings['email_subject_admin'] ); ?>" /></td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Notification Settings', 'appointment-booking-system' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Customer Email', 'appointment-booking-system' ); ?></th>
				<td><label><input type="checkbox" name="enable_customer_email" <?php checked( $settings['enable_customer_email'] ); ?> /> <?php esc_html_e( 'Enable customer confirmation email', 'appointment-booking-system' ); ?></label></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Admin Email', 'appointment-booking-system' ); ?></th>
				<td><label><input type="checkbox" name="enable_admin_email" <?php checked( $settings['enable_admin_email'] ); ?> /> <?php esc_html_e( 'Enable admin notification email', 'appointment-booking-system' ); ?></label></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Reminder Email', 'appointment-booking-system' ); ?></th>
				<td><label><input type="checkbox" name="enable_reminder_email" <?php checked( $settings['enable_reminder_email'] ); ?> /> <?php esc_html_e( 'Enable reminder email (requires WP-Cron)', 'appointment-booking-system' ); ?></label></td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Appearance', 'appointment-booking-system' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="primary_color"><?php esc_html_e( 'Primary Color', 'appointment-booking-system' ); ?></label></th>
				<td><input type="text" id="primary_color" name="primary_color" class="ab-color-field" value="<?php echo esc_attr( $settings['primary_color'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="secondary_color"><?php esc_html_e( 'Secondary Color', 'appointment-booking-system' ); ?></label></th>
				<td><input type="text" id="secondary_color" name="secondary_color" class="ab-color-field" value="<?php echo esc_attr( $settings['secondary_color'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="border_radius"><?php esc_html_e( 'Border Radius (px)', 'appointment-booking-system' ); ?></label></th>
				<td><input type="number" id="border_radius" name="border_radius" min="0" max="40" value="<?php echo esc_attr( $settings['border_radius'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="button_style"><?php esc_html_e( 'Button Style', 'appointment-booking-system' ); ?></label></th>
				<td>
					<select id="button_style" name="button_style">
						<option value="solid" <?php selected( $settings['button_style'], 'solid' ); ?>><?php esc_html_e( 'Solid', 'appointment-booking-system' ); ?></option>
						<option value="outline" <?php selected( $settings['button_style'], 'outline' ); ?>><?php esc_html_e( 'Outline', 'appointment-booking-system' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="loader_style"><?php esc_html_e( 'Loader Style', 'appointment-booking-system' ); ?></label></th>
				<td>
					<select id="loader_style" name="loader_style">
						<option value="spinner" <?php selected( $settings['loader_style'], 'spinner' ); ?>><?php esc_html_e( 'Spinner', 'appointment-booking-system' ); ?></option>
						<option value="dots" <?php selected( $settings['loader_style'], 'dots' ); ?>><?php esc_html_e( 'Dots', 'appointment-booking-system' ); ?></option>
					</select>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Advanced', 'appointment-booking-system' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="recaptcha_site_key"><?php esc_html_e( 'Google reCAPTCHA Site Key', 'appointment-booking-system' ); ?></label></th>
				<td><input type="text" id="recaptcha_site_key" name="recaptcha_site_key" class="regular-text" value="<?php echo esc_attr( $settings['recaptcha_site_key'] ); ?>" /> <span class="description"><?php esc_html_e( '(Reserved for future release)', 'appointment-booking-system' ); ?></span></td>
			</tr>
			<tr>
				<th><label for="recaptcha_secret_key"><?php esc_html_e( 'Google reCAPTCHA Secret Key', 'appointment-booking-system' ); ?></label></th>
				<td><input type="text" id="recaptcha_secret_key" name="recaptcha_secret_key" class="regular-text" value="<?php echo esc_attr( $settings['recaptcha_secret_key'] ); ?>" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'SMTP', 'appointment-booking-system' ); ?></th>
				<td><p class="description"><?php esc_html_e( 'Emails are sent via wp_mail() and are fully compatible with any SMTP plugin you already have configured.', 'appointment-booking-system' ); ?></p></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Uninstall', 'appointment-booking-system' ); ?></th>
				<td><label><input type="checkbox" name="keep_data_on_uninstall" <?php checked( $settings['keep_data_on_uninstall'] ); ?> /> <?php esc_html_e( 'Keep all appointment data when the plugin is deleted', 'appointment-booking-system' ); ?></label></td>
			</tr>
		</table>

		<p class="submit"><button type="submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'appointment-booking-system' ); ?></button></p>
	</form>
</div>