<?php
namespace AB\Includes\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Centralised nonce / capability helpers so every controller and AJAX
 * handler enforces security the same way.
 */
class Security {

	const ADMIN_NONCE    = 'ab_admin_nonce';
	const FRONTEND_NONCE = 'ab_frontend_nonce';
	const ADMIN_CAP      = 'manage_options';

	/**
	 * Verify an admin-side request (nonce + capability). Dies with a JSON
	 * error (AJAX) or wp_die() (regular POST) on failure.
	 *
	 * @param string $nonce_field Name of the $_REQUEST field holding the nonce.
	 * @param bool   $is_ajax     Whether this is an AJAX request.
	 */
	public static function verify_admin_request( $nonce_field = 'nonce', $is_ajax = true ) {
		$nonce = isset( $_REQUEST[ $nonce_field ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ $nonce_field ] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, self::ADMIN_NONCE ) ) {
			self::fail( __( 'Security check failed. Please refresh the page and try again.', 'appointment-booking-system' ), $is_ajax );
		}

		if ( ! current_user_can( self::ADMIN_CAP ) ) {
			self::fail( __( 'You do not have permission to perform this action.', 'appointment-booking-system' ), $is_ajax );
		}
	}

	/**
	 * Verify a public frontend AJAX request (nonce only — no login required).
	 *
	 * @param string $nonce_field Name of the $_REQUEST field holding the nonce.
	 */
	public static function verify_frontend_request( $nonce_field = 'nonce' ) {
		$nonce = isset( $_REQUEST[ $nonce_field ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ $nonce_field ] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, self::FRONTEND_NONCE ) ) {
			self::fail( __( 'Security check failed. Please refresh the page and try again.', 'appointment-booking-system' ), true );
		}
	}

	/**
	 * Simple honeypot spam check for the public booking form.
	 * The honeypot field must always arrive empty.
	 *
	 * @param string $value Value of the honeypot field.
	 * @return bool True if request looks like spam.
	 */
	public static function is_spam( $value ) {
		return ! empty( $value );
	}

	/**
	 * Rate limiter helper based on client IP transient counters.
	 *
	 * @param string $action         Action identifier (e.g. 'submit_booking').
	 * @param int    $max_requests   Max allowed requests in window.
	 * @param int    $period_seconds Window duration in seconds (default 300s = 5m).
	 * @return bool True if request is allowed, false if limit exceeded.
	 */
	public static function check_rate_limit( $action, $max_requests = 5, $period_seconds = 300 ) {
		$ip       = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '127.0.0.1';
		$key      = 'ab_rl_' . md5( $action . '_' . $ip );
		$requests = (int) get_transient( $key );

		if ( $requests >= $max_requests ) {
			return false;
		}

		set_transient( $key, $requests + 1, $period_seconds );
		return true;
	}

	/**
	 * Output a standard failure and terminate execution.
	 *
	 * @param string $message Message to show.
	 * @param bool   $is_ajax Whether to send a JSON response.
	 */
	protected static function fail( $message, $is_ajax ) {
		if ( $is_ajax ) {
			wp_send_json_error( array( 'message' => $message ), 403 );
		}
		wp_die( esc_html( $message ) );
	}
}
