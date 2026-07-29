<?php

namespace AB\Includes\Database;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Creates / upgrades all custom database tables used by the plugin.
 * Uses dbDelta() so it is safe to call on every activation / upgrade.
 */
class DB_Installer
{

	const DB_VERSION = '1.0.0';

	public static function install()
	{
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$prefix = $wpdb->prefix . 'ab_';

		$sql = array();

		$sql[] = "CREATE TABLE {$prefix}categories (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(191) NOT NULL,
			slug VARCHAR(191) NOT NULL,
			description TEXT NULL,
			icon VARCHAR(191) NULL,
			status TINYINT(1) NOT NULL DEFAULT 1,
			display_order INT NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY slug (slug),
			KEY status (status)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$prefix}doctors (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(191) NOT NULL,
			image VARCHAR(500) NULL,
			qualification VARCHAR(255) NULL,
			specialization VARCHAR(255) NULL,
			experience VARCHAR(100) NULL,
			email VARCHAR(191) NOT NULL,
			phone VARCHAR(50) NULL,
			bio TEXT NULL,
			status TINYINT(1) NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY email (email),
			KEY status (status)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$prefix}doctor_categories (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			doctor_id BIGINT UNSIGNED NOT NULL,
			category_id BIGINT UNSIGNED NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY doctor_category (doctor_id, category_id),
			KEY doctor_id (doctor_id),
			KEY category_id (category_id)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$prefix}services (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			category_id BIGINT UNSIGNED NOT NULL,
			name VARCHAR(191) NOT NULL,
			slug VARCHAR(191) NOT NULL,
			duration_hour SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			duration_minute SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			description TEXT NULL,
			status TINYINT(1) NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY category_id (category_id),
			KEY status (status)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$prefix}availability (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			doctor_id BIGINT UNSIGNED NOT NULL,
			day TINYINT UNSIGNED NOT NULL COMMENT '0=Sunday ... 6=Saturday',
			start_time TIME NOT NULL,
			end_time TIME NOT NULL,
			break_start TIME NULL,
			break_end TIME NULL,
			slot_duration SMALLINT UNSIGNED NOT NULL DEFAULT 30,
			PRIMARY KEY  (id),
			KEY doctor_id (doctor_id),
			KEY day (day)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$prefix}holidays (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			doctor_id BIGINT UNSIGNED NULL COMMENT 'NULL = applies to all doctors',
			holiday_date DATE NULL,
			end_date DATE NULL COMMENT 'used for date-range holidays',
			recurring_day TINYINT UNSIGNED NULL COMMENT '0-6 for a recurring weekly holiday e.g. every Sunday',
			type VARCHAR(20) NOT NULL DEFAULT 'holiday' COMMENT 'holiday or special_working',
			note VARCHAR(255) NULL,
			PRIMARY KEY  (id),
			KEY doctor_id (doctor_id),
			KEY holiday_date (holiday_date)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$prefix}appointments (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			booking_id VARCHAR(40) NOT NULL,
			category_id BIGINT UNSIGNED NOT NULL,
			doctor_id BIGINT UNSIGNED NOT NULL,
			appointment_date DATE NOT NULL,
			appointment_time TIME NOT NULL,
			total_duration SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			patient_name VARCHAR(191) NOT NULL,
			email VARCHAR(191) NOT NULL,
			phone VARCHAR(50) NOT NULL,
			country_code VARCHAR(10) NULL,
			message TEXT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, confirmed, cancelled, completed',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY booking_id (booking_id),
			KEY doctor_id (doctor_id),
			KEY category_id (category_id),
			KEY appointment_date (appointment_date),
			KEY status (status)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$prefix}appointment_services (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			appointment_id BIGINT UNSIGNED NOT NULL,
			service_id BIGINT UNSIGNED NOT NULL,
			PRIMARY KEY  (id),
			KEY appointment_id (appointment_id),
			KEY service_id (service_id)
		) {$charset_collate};";
// Translation map table - stores WPML translation relationships
$sql[] = "CREATE TABLE {$prefix}translation_map (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    object_type VARCHAR(50) NOT NULL,
    source_object_id BIGINT UNSIGNED NOT NULL,
    translated_object_id BIGINT UNSIGNED NOT NULL,
    language_code VARCHAR(10) NOT NULL,
    wpml_job_id BIGINT UNSIGNED DEFAULT NULL,
    source_post_id BIGINT UNSIGNED DEFAULT NULL,
    translated_post_id BIGINT UNSIGNED DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    created_at DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    KEY object_type_id (object_type, source_object_id),
    KEY status (status)
) {$charset_collate};";

		// Activity logs table - stores system audit logs
		$sql[] = "CREATE TABLE {$prefix}activity_logs (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			user_name VARCHAR(191) NOT NULL,
			user_email VARCHAR(191) NOT NULL,
			user_role VARCHAR(50) NULL,
			action_type VARCHAR(50) NOT NULL,
			action_name VARCHAR(50) NOT NULL,
			object_title VARCHAR(255) NOT NULL,
			details LONGTEXT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY action_type (action_type),
			KEY user_id (user_id),
			KEY created_at (created_at)
		) {$charset_collate};";

		foreach ($sql as $statement) {
			dbDelta($statement);
		}

		// Drop unique index on doctors email if it exists
		$doc_table = $prefix . 'doctors';
		$indices = $wpdb->get_results( "SHOW INDEX FROM {$doc_table} WHERE Key_name = 'email' AND Non_unique = 0" );
		if ( ! empty( $indices ) ) {
			$wpdb->query( "ALTER TABLE {$doc_table} DROP INDEX email, ADD KEY email (email)" );
		}

		// Clean up existing translated doctor emails (remove +es / +de suffixes)
		$map_table = $prefix . 'translation_map';
		$wpdb->query(
			"UPDATE {$doc_table} d
			 INNER JOIN {$map_table} m ON d.id = m.translated_object_id
			 INNER JOIN {$doc_table} s ON s.id = m.source_object_id
			 SET d.email = s.email
			 WHERE m.object_type = 'doctor' AND m.translated_object_id != m.source_object_id"
		);

		update_option('ab_db_version', self::DB_VERSION);
	}
}
