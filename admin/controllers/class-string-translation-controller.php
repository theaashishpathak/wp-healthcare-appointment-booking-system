<?php
namespace AB\Admin\Controllers;

use AB\Includes\Security\Security;
use AB\Includes\Language\Translation_Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles saving custom string translations and managing audit logs in the admin dashboard.
 */
class String_Translation_Controller {

	public function __construct() {
		add_action( 'admin_post_ab_save_string_translations', array( $this, 'save' ) );
		add_action( 'admin_post_ab_clear_translation_logs', array( $this, 'clear_logs' ) );
	}

	/**
	 * Save custom static string translations for a specific language and append to audit log.
	 */
	public function save() {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'ab_admin_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'appointment-booking-system' ) );
		}

		if ( ! Translation_Service::is_wpml_active() ) {
			wp_die( esc_html__( 'WPML / Multilingual functionality is not active on this site.', 'appointment-booking-system' ) );
		}

		if ( ! current_user_can( Security::ADMIN_CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to manage translations.', 'appointment-booking-system' ) );
		}

		$lang = isset( $_POST['lang'] ) ? sanitize_text_field( wp_unslash( $_POST['lang'] ) ) : 'de';
		$strings_input = isset( $_POST['strings'] ) && is_array( $_POST['strings'] ) ? $_POST['strings'] : array();

		// Fetch existing custom strings
		$all_custom = get_option( 'ab_custom_i18n_strings', array() );
		$old_lang_strings = ! empty( $all_custom[ $lang ] ) && is_array( $all_custom[ $lang ] ) ? $all_custom[ $lang ] : array();

		// Fetch base built-in strings for comparison
		$file_path = AB_PLUGIN_DIR . 'includes/language/i18n-strings.php';
		$base_map  = file_exists( $file_path ) ? require $file_path : array();
		$default_lang_strings = ! empty( $base_map[ $lang ] ) ? $base_map[ $lang ] : array();

		$new_lang_strings = array();
		$changes_detail   = array();

		foreach ( $strings_input as $key => $raw_val ) {
			$sanitized_key = sanitize_key( $key );
			$val           = sanitize_text_field( wp_unslash( $raw_val ) );

			if ( '' !== $val ) {
				$new_lang_strings[ $sanitized_key ] = $val;
			}

			// Determine old baseline value
			$old_val = isset( $old_lang_strings[ $sanitized_key ] )
				? $old_lang_strings[ $sanitized_key ]
				: ( isset( $default_lang_strings[ $sanitized_key ] ) ? $default_lang_strings[ $sanitized_key ] : '' );

			if ( $old_val !== $val ) {
				$changes_detail[ $sanitized_key ] = array(
					'old' => $old_val,
					'new' => $val,
				);
			}
		}

		// Update WP Option
		$all_custom[ $lang ] = $new_lang_strings;
		update_option( 'ab_custom_i18n_strings', $all_custom );

		// Record Audit Log if changes occurred
		if ( ! empty( $changes_detail ) ) {
			\AB\Includes\Logger::log(
				'string_translation',
				'updated',
				'Updated static string translations for [' . strtoupper( $lang ) . ']',
				array(
					'lang'           => $lang,
					'changes_count'  => count( $changes_detail ),
					'changes_detail' => $changes_detail,
				)
			);

			$current_user = wp_get_current_user();
			$logs         = get_option( 'ab_i18n_audit_log', array() );
			if ( ! is_array( $logs ) ) {
				$logs = array();
			}

			array_unshift(
				$logs,
				array(
					'id'             => uniqid( 'log_' ),
					'timestamp'      => current_time( 'mysql' ),
					'user_id'        => get_current_user_id(),
					'user_name'      => $current_user->display_name ?: $current_user->user_login,
					'user_email'     => $current_user->user_email,
					'lang'           => $lang,
					'changes_count'  => count( $changes_detail ),
					'changes_detail' => $changes_detail,
				)
			);

			// Limit to 100 log entries
			$logs = array_slice( $logs, 0, 100 );
			update_option( 'ab_i18n_audit_log', $logs );
		}

		$url = add_query_arg(
			array(
				'page'      => 'ab-string-translations',
				'lang'      => $lang,
				'tab'       => 'strings',
				'ab_notice' => 'success',
				'ab_msg'    => rawurlencode( sprintf( __( 'Static string translations for [%s] saved successfully.', 'appointment-booking-system' ), strtoupper( $lang ) ) ),
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Clear all audit log entries.
	 */
	public function clear_logs() {
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'ab_admin_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'appointment-booking-system' ) );
		}

		if ( ! Translation_Service::is_wpml_active() ) {
			wp_die( esc_html__( 'WPML / Multilingual functionality is not active on this site.', 'appointment-booking-system' ) );
		}

		if ( ! current_user_can( Security::ADMIN_CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to clear logs.', 'appointment-booking-system' ) );
		}

		delete_option( 'ab_i18n_audit_log' );

		$url = add_query_arg(
			array(
				'page'      => 'ab-string-translations',
				'tab'       => 'logs',
				'ab_notice' => 'success',
				'ab_msg'    => rawurlencode( __( 'Audit logs cleared successfully.', 'appointment-booking-system' ) ),
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $url );
		exit;
	}
}
