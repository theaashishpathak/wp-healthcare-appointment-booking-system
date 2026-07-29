<?php
namespace AB\Admin\Controllers;

use AB\Includes\Security\Security;
use AB\Includes\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings_Controller {

	public function __construct() {
		add_action( 'admin_post_ab_save_settings', array( $this, 'save' ) );
	}

	public function save() {
		Security::verify_admin_request( '_wpnonce', false );

		$defaults = ab_get_default_settings();
		$posted   = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$old_settings = get_option( 'ab_settings', array() );

		$settings = array(
			'clinic_name'            => sanitize_text_field( $posted['clinic_name'] ?? $defaults['clinic_name'] ),
			'clinic_email'           => sanitize_email( $posted['clinic_email'] ?? $defaults['clinic_email'] ),
			'clinic_phone'           => sanitize_text_field( $posted['clinic_phone'] ?? '' ),
			'date_format'            => sanitize_text_field( $posted['date_format'] ?? $defaults['date_format'] ),
			'time_format'            => sanitize_text_field( $posted['time_format'] ?? $defaults['time_format'] ),
			'admin_email'            => sanitize_email( $posted['admin_email'] ?? $defaults['admin_email'] ),
			'email_subject_customer' => sanitize_text_field( $posted['email_subject_customer'] ?? $defaults['email_subject_customer'] ),
			'email_subject_admin'    => sanitize_text_field( $posted['email_subject_admin'] ?? $defaults['email_subject_admin'] ),
			'enable_customer_email'  => isset( $posted['enable_customer_email'] ) ? 1 : 0,
			'enable_admin_email'     => isset( $posted['enable_admin_email'] ) ? 1 : 0,
			'enable_reminder_email'  => isset( $posted['enable_reminder_email'] ) ? 1 : 0,
			'primary_color'          => sanitize_hex_color( $posted['primary_color'] ?? $defaults['primary_color'] ),
			'secondary_color'        => sanitize_hex_color( $posted['secondary_color'] ?? $defaults['secondary_color'] ),
			'border_radius'          => absint( $posted['border_radius'] ?? $defaults['border_radius'] ),
			'button_style'           => sanitize_text_field( $posted['button_style'] ?? $defaults['button_style'] ),
			'loader_style'           => sanitize_text_field( $posted['loader_style'] ?? $defaults['loader_style'] ),
			'recaptcha_site_key'     => sanitize_text_field( $posted['recaptcha_site_key'] ?? '' ),
			'recaptcha_secret_key'   => sanitize_text_field( $posted['recaptcha_secret_key'] ?? '' ),
			'keep_data_on_uninstall' => isset( $posted['keep_data_on_uninstall'] ) ? 1 : 0,
		);

		// Calculate diff for audit log
		$diff = array();
		foreach ( $settings as $k => $v ) {
			$old_v = $old_settings[ $k ] ?? null;
			if ( (string) $old_v !== (string) $v ) {
				$diff[ $k ] = array(
					'old' => $old_v,
					'new' => $v,
				);
			}
		}

		update_option( 'ab_settings', $settings );

		// Log settings update event
		Logger::log(
			'settings',
			'updated',
			'Updated Plugin Settings (' . count( $diff ) . ' change(s))',
			array(
				'changes_count'  => count( $diff ),
				'changes_detail' => $diff,
				'full_settings'  => $settings,
			)
		);

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'      => 'ab-settings',
					'ab_notice' => 'success',
					'ab_msg'    => rawurlencode( __( 'Settings saved successfully.', 'appointment-booking-system' ) ),
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}