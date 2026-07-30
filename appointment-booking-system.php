<?php
/**
 * Plugin Name:       Appointment Booking System
 * Plugin URI:        https://github.com/theaashishpathak/wp-healthcare-appointment-booking-system
 * Description:        A complete appointment booking system for healthcare, clinics, hospitals, wellness centres and treatment providers. Multi-step frontend booking wizard + full admin management. Divi compatible.
 * Version:            1.4.0
 * Requires at least:  6.0
 * Requires PHP:       8.1
 * Author:             Aashish Pathak
 * Text Domain:        appointment-booking-system
 * Domain Path:        /languages
 * License:            GPL v2 or later
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core plugin constants.
 */
define( 'AB_VERSION', '1.4.0' );
define( 'AB_PLUGIN_FILE', __FILE__ );
define( 'AB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'AB_TABLE_PREFIX', 'ab_' ); // appended to $wpdb->prefix -> wp_ab_*

/**
 * Simple PSR-4-ish autoloader for the AB\ namespace.
 *
 * Maps AB\Admin\Class_Name        -> admin/class-class-name.php
 * Maps AB\Admin\Controllers\Foo   -> admin/controllers/class-foo.php
 * Maps AB\Frontend\Ajax\Foo       -> frontend/ajax/class-foo.php
 * Maps AB\Includes\Models\Foo     -> includes/models/class-foo.php
 * etc.
 */
spl_autoload_register(
	function ( $class ) {
		if ( strpos( $class, 'AB\\' ) !== 0 ) {
			return;
		}

		$relative = substr( $class, strlen( 'AB\\' ) );
		$parts    = explode( '\\', $relative );
		$class_name = array_pop( $parts );

		$path_parts = array_map(
			function ( $part ) {
				return strtolower( str_replace( '_', '-', $part ) );
			},
			$parts
		);

		$file_name = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';

		$path = AB_PLUGIN_DIR . implode( '/', $path_parts );
		$path = $path ? $path . '/' . $file_name : $file_name;

		if ( file_exists( $path ) ) {
			require_once $path;
		}
	}
);

/**
 * Load procedural helper/function files that are not classes.
 */
require_once AB_PLUGIN_DIR . 'includes/functions.php';

/**
 * Activation hook — creates database tables and default settings.
 */
function ab_activate_plugin() {
	require_once AB_PLUGIN_DIR . 'includes/database/class-db-installer.php';
	\AB\Includes\Database\DB_Installer::install();

	if ( false === get_option( 'ab_settings' ) ) {
		add_option( 'ab_settings', ab_get_default_settings() );
	}

	flush_rewrite_rules();

	require_once AB_PLUGIN_DIR . 'includes/class-logger.php';
	\AB\Includes\Logger::ensure_table_exists();
	\AB\Includes\Logger::log( 'plugin_lifecycle', 'activated', 'Appointment Booking System plugin activated' );
}
register_activation_hook( __FILE__, 'ab_activate_plugin' );

/**
 * Deactivation hook — flush rewrite rules only. Data is preserved.
 */
function ab_deactivate_plugin() {
	flush_rewrite_rules();

	require_once AB_PLUGIN_DIR . 'includes/class-logger.php';
	\AB\Includes\Logger::ensure_table_exists();
	\AB\Includes\Logger::log( 'plugin_lifecycle', 'deactivated', 'Appointment Booking System plugin deactivated' );
}
register_deactivation_hook( __FILE__, 'ab_deactivate_plugin' );

// Log any general plugin activations/deactivations across WordPress
add_action( 'activated_plugin', function( $plugin ) {
	require_once AB_PLUGIN_DIR . 'includes/class-logger.php';
	\AB\Includes\Logger::ensure_table_exists();
	$plugin_name = plugin_basename( $plugin );
	\AB\Includes\Logger::log( 'plugin_lifecycle', 'plugin_activated', 'Plugin activated: ' . $plugin_name, array( 'plugin' => $plugin_name ) );
} );

add_action( 'deactivated_plugin', function( $plugin ) {
	require_once AB_PLUGIN_DIR . 'includes/class-logger.php';
	\AB\Includes\Logger::ensure_table_exists();
	$plugin_name = plugin_basename( $plugin );
	\AB\Includes\Logger::log( 'plugin_lifecycle', 'plugin_deactivated', 'Plugin deactivated: ' . $plugin_name, array( 'plugin' => $plugin_name ) );
} );
/**
 * Register hidden post type for WPML translation bridge.
 * This post type is used internally to bridge custom tables with WPML.
 */
function ab_register_translatable_post_type() {
	register_post_type( 'ab_translatable', array(
		'public'             => false,
		'publicly_queryable' => false,
		'show_ui'            => false,
		'show_in_menu'       => false,
		'show_in_nav_menus'  => false,
		'show_in_admin_bar'  => false,
		'show_in_rest'       => false,
		'query_var'          => false,
		'rewrite'            => false,
		'capability_type'    => 'post',
		'has_archive'        => false,
		'hierarchical'       => false,
		'supports'           => array( 'title', 'editor' ),
	) );
}
add_action( 'init', 'ab_register_translatable_post_type' );
/**
 * Bootstrap the plugin once all plugins are loaded.
 */
function ab_run_plugin() {
	load_plugin_textdomain( 'appointment-booking-system', false, dirname( AB_PLUGIN_BASENAME ) . '/languages' );

	if ( is_admin() ) {
		new \AB\Admin\Admin();
	}

	new \AB\Frontend\Frontend();

	// Log user login/logout security events
	add_action( 'wp_login', function( $user_login, $user ) {
		\AB\Includes\Logger::log( 'security', 'login', 'User logged in: ' . $user_login, array( 'user_id' => $user->ID, 'email' => $user->user_email, 'role' => ! empty( $user->roles[0] ) ? ucfirst( $user->roles[0] ) : 'User' ) );
	}, 10, 2 );

	add_action( 'wp_logout', function( $user_id ) {
		$user = get_userdata( $user_id );
		$name = $user ? $user->user_login : 'User #' . $user_id;
		\AB\Includes\Logger::log( 'security', 'logout', 'User logged out: ' . $name, array( 'user_id' => $user_id ) );
	} );

	// Log mail failure details
	add_action( 'wp_mail_failed', function( $wp_error ) {
		if ( is_wp_error( $wp_error ) ) {
			\AB\Includes\Logger::log(
				'email',
				'failed',
				'Email Delivery Failed: ' . $wp_error->get_error_message(),
				array(
					'error_code'    => $wp_error->get_error_code(),
					'error_message' => $wp_error->get_error_message(),
					'error_data'    => $wp_error->get_error_data(),
				)
			);
		}
	} );
}
add_action( 'plugins_loaded', 'ab_run_plugin' );

/**
 * Initialize Translation System (WPML Integration)
 * 
 * Phase 4.2 - Translation Controller (handles admin requests)
 * Phase 4.3 - WPML Adapter (bridges custom tables with WPML)
 * 
 * Priority 20 ensures WPML is fully loaded first.
 */
function ab_init_translations() {
	// Only initialize if Translation_Service exists
	if ( ! class_exists( '\AB\Includes\Language\Translation_Service' ) ) {
		return;
	}

	// Only load WPML-specific classes if WPML and Translation Management are active
	if ( \AB\Includes\Language\Translation_Service::is_wpml_active() && \AB\Includes\Language\Translation_Service::is_wpml_translation_management_active() ) {
		
		// Load WPML Adapter (Phase 4.3)
		// This class bridges our custom database tables with WPML's translation system
		if ( ! class_exists( '\AB\Includes\Language\WPML_Adapter' ) ) {
			require_once AB_PLUGIN_DIR . 'includes/language/class-wpml-adapter.php';
		}

		// Load Translation Controller (Phase 4.2)
		// This class handles admin-post requests when users click + or ✓
		if ( ! class_exists( '\AB\Includes\Language\Translation_Controller' ) ) {
			require_once AB_PLUGIN_DIR . 'includes/language/class-translation-controller.php';
		}

		// Initialize the controller
		// This registers the admin_post_ab_create_translation and admin_post_ab_edit_translation hooks
		new \AB\Includes\Language\Translation_Controller();
	}
}
add_action( 'init', 'ab_init_translations', 20 );