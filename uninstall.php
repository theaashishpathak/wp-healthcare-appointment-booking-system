<?php
/**
 * Fired when the plugin is uninstalled (deleted) from the WordPress admin.
 * Drops all plugin tables and removes plugin options.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$settings = get_option( 'ab_settings', array() );

// Respect a "keep data on uninstall" setting if the admin enabled it.
$keep_data = ! empty( $settings['keep_data_on_uninstall'] );

if ( ! $keep_data ) {
	$tables = array(
		$wpdb->prefix . 'ab_appointment_services',
		$wpdb->prefix . 'ab_appointments',
		$wpdb->prefix . 'ab_holidays',
		$wpdb->prefix . 'ab_availability',
		$wpdb->prefix . 'ab_services',
		$wpdb->prefix . 'ab_doctor_categories',
		$wpdb->prefix . 'ab_doctors',
		$wpdb->prefix . 'ab_categories',
	);

	foreach ( $tables as $table ) {
		// Table names cannot be parameterized; each is a hardcoded, sanitized constant above.
		$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	delete_option( 'ab_settings' );
	delete_option( 'ab_db_version' );
}
