<?php
namespace AB\Includes\Email;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds and sends the customer confirmation and admin notification emails.
 * Uses wp_mail() so it is compatible with any SMTP plugin already configured.
 */
class Email {

	/**
	 * @param array $appointment Appointment row (array).
	 * @param array $services    Service rows attached to the appointment.
	 * @param array $doctor      Doctor row.
	 * @param array $category    Category row.
	 */
	public static function send_booking_emails( $appointment, $services, $doctor, $category ) {
		if ( ab_get_setting( 'enable_customer_email', 1 ) ) {
			self::send_customer_email( $appointment, $services, $doctor, $category );
		}
		if ( ab_get_setting( 'enable_admin_email', 1 ) ) {
			self::send_admin_email( $appointment, $services, $doctor, $category );
		}
	}

	protected static function headers() {
		$clinic_name  = ab_get_setting( 'clinic_name', get_bloginfo( 'name' ) );
		$clinic_email = ab_get_setting( 'clinic_email', get_bloginfo( 'admin_email' ) );
		return array(
			'Content-Type: text/html; charset=UTF-8',
			sprintf( 'From: %s <%s>', $clinic_name, $clinic_email ),
		);
	}

	protected static function wrap_template( $title, $body_html ) {
		$primary = esc_attr( ab_get_setting( 'primary_color', '#0B6E4F' ) );
		$clinic  = esc_html( ab_get_setting( 'clinic_name', get_bloginfo( 'name' ) ) );

		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<body style="margin:0;padding:0;background:#f4f6f8;font-family:Arial,Helvetica,sans-serif;">
			<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;padding:24px 0;">
				<tr>
					<td align="center">
						<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;">
							<tr>
								<td style="background:<?php echo $primary; ?>;padding:24px 32px;">
									<h1 style="color:#fff;font-size:20px;margin:0;"><?php echo esc_html( $clinic ); ?></h1>
								</td>
							</tr>
							<tr>
								<td style="padding:32px;color:#1f2937;">
									<h2 style="font-size:18px;margin-top:0;"><?php echo esc_html( $title ); ?></h2>
									<?php echo $body_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</td>
							</tr>
							<tr>
								<td style="padding:16px 32px;background:#f9fafb;color:#6b7280;font-size:12px;">
									&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( $clinic ); ?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	protected static function services_list_html( $services ) {
		$html = '<ul style="padding-left:18px;margin:8px 0;">';
		foreach ( $services as $service ) {
			$duration = ab_format_duration( $service['duration_hour'], $service['duration_minute'] );
			$html    .= '<li>' . esc_html( $service['name'] ) . ' &mdash; ' . esc_html( $duration ) . '</li>';
		}
		$html .= '</ul>';
		return $html;
	}

	protected static function send_customer_email( $appointment, $services, $doctor, $category ) {
		$subject = ab_get_setting( 'email_subject_customer', 'Appointment Booking Confirmation' ) . ' - ' . $appointment['booking_id'];

		$body  = '<p>' . esc_html__( 'Thank you for booking your appointment. Our team has received your request and a representative will contact you shortly if further information is required.', 'appointment-booking-system' ) . '</p>';
		$body .= '<table role="presentation" width="100%" cellpadding="6" cellspacing="0" style="margin-top:16px;">';
		$body .= '<tr><td style="color:#6b7280;">' . esc_html__( 'Booking ID', 'appointment-booking-system' ) . '</td><td><strong>' . esc_html( $appointment['booking_id'] ) . '</strong></td></tr>';
		$body .= '<tr><td style="color:#6b7280;">' . esc_html__( 'Doctor', 'appointment-booking-system' ) . '</td><td>' . esc_html( $doctor['name'] ?? '' ) . '</td></tr>';
		$body .= '<tr><td style="color:#6b7280;">' . esc_html__( 'Treatment', 'appointment-booking-system' ) . '</td><td>' . esc_html( $category['name'] ?? '' ) . '</td></tr>';
		$body .= '<tr><td style="color:#6b7280;vertical-align:top;">' . esc_html__( 'Services', 'appointment-booking-system' ) . '</td><td>' . self::services_list_html( $services ) . '</td></tr>';
		$body .= '<tr><td style="color:#6b7280;">' . esc_html__( 'Date', 'appointment-booking-system' ) . '</td><td>' . esc_html( ab_format_date( $appointment['appointment_date'] ) ) . '</td></tr>';
		$body .= '<tr><td style="color:#6b7280;">' . esc_html__( 'Time', 'appointment-booking-system' ) . '</td><td>' . esc_html( ab_format_time( $appointment['appointment_time'] ) ) . '</td></tr>';
		$body .= '</table>';

		$clinic_email = ab_get_setting( 'clinic_email', '' );
		$clinic_phone = ab_get_setting( 'clinic_phone', '' );
		if ( $clinic_email || $clinic_phone ) {
			$body .= '<p style="margin-top:16px;color:#6b7280;">' . esc_html__( 'Clinic contact:', 'appointment-booking-system' ) . ' ' . esc_html( $clinic_email ) . ' ' . esc_html( $clinic_phone ) . '</p>';
		}

		$html = self::wrap_template( __( 'Appointment Submitted Successfully', 'appointment-booking-system' ), $body );

		wp_mail( $appointment['email'], $subject, $html, self::headers() );
	}

	protected static function send_admin_email( $appointment, $services, $doctor, $category ) {
		$subject   = ab_get_setting( 'email_subject_admin', 'New Appointment Received' ) . ' - ' . $appointment['booking_id'];
		$admin_url = admin_url( 'admin.php?page=ab-appointments&booking=' . rawurlencode( $appointment['booking_id'] ) );

		$body  = '<table role="presentation" width="100%" cellpadding="6" cellspacing="0">';
		$body .= '<tr><td style="color:#6b7280;">' . esc_html__( 'Booking ID', 'appointment-booking-system' ) . '</td><td><strong>' . esc_html( $appointment['booking_id'] ) . '</strong></td></tr>';
		$body .= '<tr><td style="color:#6b7280;">' . esc_html__( 'Patient', 'appointment-booking-system' ) . '</td><td>' . esc_html( $appointment['patient_name'] ) . '</td></tr>';
		$body .= '<tr><td style="color:#6b7280;">' . esc_html__( 'Email', 'appointment-booking-system' ) . '</td><td>' . esc_html( $appointment['email'] ) . '</td></tr>';
		$body .= '<tr><td style="color:#6b7280;">' . esc_html__( 'Phone', 'appointment-booking-system' ) . '</td><td>' . esc_html( $appointment['phone'] ) . '</td></tr>';
		$body .= '<tr><td style="color:#6b7280;">' . esc_html__( 'Doctor', 'appointment-booking-system' ) . '</td><td>' . esc_html( $doctor['name'] ?? '' ) . '</td></tr>';
		$body .= '<tr><td style="color:#6b7280;">' . esc_html__( 'Category', 'appointment-booking-system' ) . '</td><td>' . esc_html( $category['name'] ?? '' ) . '</td></tr>';
		$body .= '<tr><td style="color:#6b7280;vertical-align:top;">' . esc_html__( 'Services', 'appointment-booking-system' ) . '</td><td>' . self::services_list_html( $services ) . '</td></tr>';
		$body .= '<tr><td style="color:#6b7280;">' . esc_html__( 'Date', 'appointment-booking-system' ) . '</td><td>' . esc_html( ab_format_date( $appointment['appointment_date'] ) ) . '</td></tr>';
		$body .= '<tr><td style="color:#6b7280;">' . esc_html__( 'Time', 'appointment-booking-system' ) . '</td><td>' . esc_html( ab_format_time( $appointment['appointment_time'] ) ) . '</td></tr>';
		if ( ! empty( $appointment['message'] ) ) {
			$body .= '<tr><td style="color:#6b7280;vertical-align:top;">' . esc_html__( 'Message', 'appointment-booking-system' ) . '</td><td>' . esc_html( $appointment['message'] ) . '</td></tr>';
		}
		$body .= '</table>';
		$body .= '<p style="margin-top:20px;"><a href="' . esc_url( $admin_url ) . '" style="background:' . esc_attr( ab_get_setting( 'primary_color', '#0B6E4F' ) ) . ';color:#fff;padding:10px 18px;border-radius:6px;text-decoration:none;">' . esc_html__( 'View in Admin Panel', 'appointment-booking-system' ) . '</a></p>';

		$html = self::wrap_template( __( 'New Appointment Received', 'appointment-booking-system' ), $body );

		wp_mail( ab_get_setting( 'admin_email', get_bloginfo( 'admin_email' ) ), $subject, $html, self::headers() );
	}
}
