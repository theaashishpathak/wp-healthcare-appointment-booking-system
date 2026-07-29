<?php
/**
 * Global procedural helper functions for Appointment Booking System.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default plugin settings used on activation and as a fallback.
 *
 * @return array
 */
function ab_get_default_settings() {
	return array(
		'clinic_name'             => get_bloginfo( 'name' ),
		'clinic_email'            => get_bloginfo( 'admin_email' ),
		'clinic_phone'            => '',
		'timezone'                => wp_timezone_string(),
		'date_format'             => 'd M Y',
		'time_format'             => 'h:i A',
		'admin_email'             => get_bloginfo( 'admin_email' ),
		'email_subject_customer'  => 'Appointment Booking Confirmation',
		'email_subject_admin'     => 'New Appointment Received',
		'enable_customer_email'   => 1,
		'enable_admin_email'      => 1,
		'enable_reminder_email'   => 0,
		'primary_color'           => '#0B6E4F',
		'secondary_color'         => '#0E2A47',
		'border_radius'           => 8,
		'button_style'            => 'solid',
		'loader_style'            => 'spinner',
		'recaptcha_site_key'      => '',
		'recaptcha_secret_key'    => '',
		'keep_data_on_uninstall'  => 0,
	);
}

/**
 * Fetch a single plugin setting.
 *
 * @param string $key     Setting key.
 * @param mixed  $default Fallback if not set.
 * @return mixed
 */
function ab_get_setting( $key, $default = '' ) {
	// Always return WordPress timezone - ignores saved plugin setting
	if ( $key === 'timezone' ) {
		return wp_timezone_string();
	}
	
	$settings = wp_parse_args( get_option( 'ab_settings', array() ), ab_get_default_settings() );
	return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
}

/**
 * Generate a unique, human friendly booking ID e.g. AB-20260728-4F82.
 *
 * @return string
 */
function ab_generate_booking_id() {
	return 'AB-' . gmdate( 'Ymd' ) . '-' . strtoupper( substr( wp_generate_password( 8, false, false ), 0, 4 ) );
}

/**
 * Format H:i:s time using the admin's configured time format.
 *
 * @param string $time Time string (H:i:s or H:i).
 * @return string
 */
function ab_format_time( $time ) {
	if ( empty( $time ) ) {
		return '';
	}
	$timestamp = strtotime( $time );
	return $timestamp ? date_i18n( ab_get_setting( 'time_format', 'h:i A' ), $timestamp ) : $time;
}

/**
 * Format Y-m-d date using the admin's configured date format.
 *
 * @param string $date Date string.
 * @return string
 */
function ab_format_date( $date ) {
	if ( empty( $date ) ) {
		return '';
	}
	$timestamp = strtotime( $date );
	return $timestamp ? date_i18n( ab_get_setting( 'date_format', 'd M Y' ), $timestamp ) : $date;
}

/**
 * Turn duration hour/minute pair into a readable label, e.g. "1 Hour 30 Minutes".
 *
 * @param int $hours   Whole hours.
 * @param int $minutes Additional minutes.
 * @return string
 */
function ab_format_duration( $hours, $minutes ) {
	$hours   = (int) $hours;
	$minutes = (int) $minutes;
	$parts   = array();

	if ( $hours > 0 ) {
		$parts[] = $hours . ' ' . _n( 'Hour', 'Hours', $hours, 'appointment-booking-system' );
	}
	if ( $minutes > 0 ) {
		$parts[] = $minutes . ' ' . _n( 'Minute', 'Minutes', $minutes, 'appointment-booking-system' );
	}

	return $parts ? implode( ' ', $parts ) : __( '0 Minutes', 'appointment-booking-system' );
}

/**
 * Convert duration hour/minute into total minutes.
 *
 * @param int $hours Hours.
 * @param int $minutes Minutes.
 * @return int
 */
function ab_duration_to_minutes( $hours, $minutes ) {
	return ( (int) $hours * 60 ) + (int) $minutes;
}

/**
 * Human readable labels for booking statuses, used consistently across admin + emails.
 *
 * @return array
 */
function ab_get_status_labels() {
	return array(
		'pending'   => __( 'Pending', 'appointment-booking-system' ),
		'confirmed' => __( 'Confirmed', 'appointment-booking-system' ),
		'cancelled' => __( 'Cancelled', 'appointment-booking-system' ),
		'completed' => __( 'Completed', 'appointment-booking-system' ),
	);
}

/**
 * Names of the days of the week, 0 (Sunday) through 6 (Saturday).
 *
 * @return array
 */
function ab_get_day_names() {
	return array(
		0 => __( 'Sunday', 'appointment-booking-system' ),
		1 => __( 'Monday', 'appointment-booking-system' ),
		2 => __( 'Tuesday', 'appointment-booking-system' ),
		3 => __( 'Wednesday', 'appointment-booking-system' ),
		4 => __( 'Thursday', 'appointment-booking-system' ),
		5 => __( 'Friday', 'appointment-booking-system' ),
		6 => __( 'Saturday', 'appointment-booking-system' ),
	);
}