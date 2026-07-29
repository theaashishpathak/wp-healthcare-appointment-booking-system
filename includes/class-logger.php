<?php
namespace AB\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central Logger for tracking all system activities, admin edits, creations, deletions, settings, plugin lifecycle, and logins.
 */
class Logger {

	/**
	 * Ensure the activity_logs table exists, has all required columns, and legacy audit logs are migrated.
	 */
	public static function ensure_table_exists() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'ab_activity_logs';

		// Check if table exists in current database
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) !== $table_name ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE {$table_name} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
				user_name VARCHAR(191) NOT NULL,
				user_email VARCHAR(191) NOT NULL,
				user_role VARCHAR(50) NULL,
				action_type VARCHAR(50) NOT NULL,
				action_name VARCHAR(50) NOT NULL,
				object_title VARCHAR(255) NOT NULL,
				details LONGTEXT NULL,
				ip_address VARCHAR(45) NULL,
				user_agent VARCHAR(255) NULL,
				created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY action_type (action_type),
				KEY user_id (user_id),
				KEY created_at (created_at)
			) {$charset_collate};";

			dbDelta( $sql );
		} else {
			// Check for missing columns (ip_address / user_agent)
			$col = $wpdb->get_results( "SHOW COLUMNS FROM {$table_name} LIKE 'ip_address'" );
			if ( empty( $col ) ) {
				$wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN ip_address VARCHAR(45) NULL AFTER details, ADD COLUMN user_agent VARCHAR(255) NULL AFTER ip_address" );
			}
		}

		// Migrate any legacy options-based string translation audit logs if present
		self::migrate_legacy_audit_logs();
	}

	/**
	 * Migrate legacy ab_i18n_audit_log entries to activity_logs table if not migrated yet.
	 */
	protected static function migrate_legacy_audit_logs() {
		$migrated = get_option( 'ab_audit_logs_migrated', false );
		if ( $migrated ) {
			return;
		}

		$legacy_logs = get_option( 'ab_i18n_audit_log', array() );
		if ( ! empty( $legacy_logs ) && is_array( $legacy_logs ) ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'ab_activity_logs';

			foreach ( $legacy_logs as $entry ) {
				$timestamp    = ! empty( $entry['timestamp'] ) ? $entry['timestamp'] : current_time( 'mysql' );
				$user_id      = ! empty( $entry['user_id'] ) ? absint( $entry['user_id'] ) : 0;
				$user_name    = ! empty( $entry['user_name'] ) ? sanitize_text_field( $entry['user_name'] ) : 'Administrator';
				$user_email   = ! empty( $entry['user_email'] ) ? sanitize_email( $entry['user_email'] ) : 'admin';
				$lang         = ! empty( $entry['lang'] ) ? strtoupper( $entry['lang'] ) : 'EN';
				$changes      = ! empty( $entry['changes_detail'] ) ? $entry['changes_detail'] : array();
				$title        = sprintf( 'Updated %d static string(s) for [%s]', count( $changes ), $lang );

				$wpdb->insert(
					$table_name,
					array(
						'user_id'      => $user_id,
						'user_name'    => $user_name,
						'user_email'   => $user_email,
						'user_role'    => 'Administrator',
						'action_type'  => 'string_translation',
						'action_name'  => 'updated',
						'object_title' => $title,
						'details'      => wp_json_encode( array( 'lang' => $lang, 'changes_detail' => $changes ) ),
						'created_at'   => $timestamp,
					)
				);
			}
		}

		update_option( 'ab_audit_logs_migrated', true );
	}

	/**
	 * Log an activity event.
	 *
	 * @param string $action_type Category: 'doctor', 'category', 'service', 'availability', 'appointment', 'translation', 'string_translation', 'settings', 'plugin_lifecycle', 'security'
	 * @param string $action_name Action: 'created', 'updated', 'deleted', 'activated', 'deactivated', 'login', 'logout'
	 * @param string $object_title Human readable summary title.
	 * @param array  $details Extra payload details array.
	 * @return int|false Log ID or false on failure.
	 */
	public static function log( $action_type, $action_name, $object_title, $details = array() ) {
		self::ensure_table_exists();

		global $wpdb;

		$user_id    = get_current_user_id();
		$user_name  = 'Guest / Visitor';
		$user_email = 'system';
		$user_role  = 'Public';

		if ( $user_id ) {
			$user       = wp_get_current_user();
			$user_name  = $user->display_name ?: $user->user_login;
			$user_email = $user->user_email;
			$user_role  = ! empty( $user->roles[0] ) ? ucfirst( $user->roles[0] ) : 'User';
		}

		$ip_address = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 250 ) : '';

		$table_name = $wpdb->prefix . 'ab_activity_logs';

		$inserted = $wpdb->insert(
			$table_name,
			array(
				'user_id'      => $user_id,
				'user_name'    => $user_name,
				'user_email'   => $user_email,
				'user_role'    => $user_role,
				'action_type'  => sanitize_key( $action_type ),
				'action_name'  => sanitize_key( $action_name ),
				'object_title' => sanitize_text_field( $object_title ),
				'details'      => ! empty( $details ) ? wp_json_encode( $details ) : null,
				'ip_address'   => $ip_address,
				'user_agent'   => $user_agent,
				'created_at'   => current_time( 'mysql' ),
			)
		);

		return $inserted ? $wpdb->insert_id : false;
	}

	/**
	 * Fetch activity log records.
	 *
	 * @param array $args Filter arguments.
	 * @return array Array of log rows.
	 */
	public static function get_logs( $args = array() ) {
		self::ensure_table_exists();

		global $wpdb;

		$table_name = $wpdb->prefix . 'ab_activity_logs';
		$where      = array();
		$params     = array();

		if ( ! empty( $args['action_type'] ) ) {
			$where[]  = 'action_type = %s';
			$params[] = sanitize_key( $args['action_type'] );
		}

		if ( ! empty( $args['user_id'] ) ) {
			$where[]  = 'user_id = %d';
			$params[] = absint( $args['user_id'] );
		}

		if ( ! empty( $args['search'] ) ) {
			$where[]  = '(object_title LIKE %s OR user_name LIKE %s OR user_email LIKE %s OR action_type LIKE %s OR ip_address LIKE %s)';
			$s        = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$params[] = $s;
			$params[] = $s;
			$params[] = $s;
			$params[] = $s;
			$params[] = $s;
		}

		$sql = "SELECT * FROM {$table_name}";
		if ( ! empty( $where ) ) {
			$sql .= ' WHERE ' . implode( ' AND ', $where );
		}

		$sql .= ' ORDER BY id DESC';

		$limit  = isset( $args['limit'] ) ? absint( $args['limit'] ) : 100;
		$offset = isset( $args['offset'] ) ? absint( $args['offset'] ) : 0;
		$sql   .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $limit, $offset );

		if ( ! empty( $params ) ) {
			$sql = $wpdb->prepare( $sql, $params );
		}

		return $wpdb->get_results( $sql, ARRAY_A );
	}

	/**
	 * Count total log entries matching query.
	 *
	 * @param array $args
	 * @return int
	 */
	public static function count_logs( $args = array() ) {
		self::ensure_table_exists();

		global $wpdb;

		$table_name = $wpdb->prefix . 'ab_activity_logs';
		$where      = array();
		$params     = array();

		if ( ! empty( $args['action_type'] ) ) {
			$where[]  = 'action_type = %s';
			$params[] = sanitize_key( $args['action_type'] );
		}

		if ( ! empty( $args['search'] ) ) {
			$where[]  = '(object_title LIKE %s OR user_name LIKE %s OR user_email LIKE %s OR action_type LIKE %s OR ip_address LIKE %s)';
			$s        = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$params[] = $s;
			$params[] = $s;
			$params[] = $s;
			$params[] = $s;
			$params[] = $s;
		}

		$sql = "SELECT COUNT(*) FROM {$table_name}";
		if ( ! empty( $where ) ) {
			$sql .= ' WHERE ' . implode( ' AND ', $where );
			$sql  = $wpdb->prepare( $sql, $params );
		}

		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Delete all activity log entries.
	 *
	 * @return bool
	 */
	public static function clear_all_logs() {
		self::ensure_table_exists();

		global $wpdb;
		$table_name = $wpdb->prefix . 'ab_activity_logs';
		return (bool) $wpdb->query( "TRUNCATE TABLE {$table_name}" );
	}
}
