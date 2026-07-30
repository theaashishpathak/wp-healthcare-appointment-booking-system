<?php
namespace AB\Admin;

use AB\Includes\Security\Security;
use AB\Admin\Controllers\Category_Controller;
use AB\Admin\Controllers\Doctor_Controller;
use AB\Admin\Controllers\Service_Controller;
use AB\Admin\Controllers\Availability_Controller;
use AB\Admin\Controllers\Appointment_Controller;
use AB\Admin\Controllers\Settings_Controller;
use AB\Admin\Controllers\String_Translation_Controller;
use AB\Admin\Controllers\Activity_Log_Controller;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the "Appointment Booking" admin menu and its submenus,
 * enqueues admin assets, and wires up the individual page controllers.
 */
class Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_notices', array( $this, 'setup_notices' ) );

		// Controllers hook into admin-post.php themselves.
		new Category_Controller();
		new Doctor_Controller();
		new Service_Controller();
		new Availability_Controller();
		new Appointment_Controller();
		new Settings_Controller();
		new String_Translation_Controller();
		new Activity_Log_Controller();
	}

	public function register_menu() {
		$cap = Security::ADMIN_CAP;

		add_menu_page(
			__( 'Appointment Booking', 'appointment-booking-system' ),
			__( 'Appointment Booking', 'appointment-booking-system' ),
			$cap,
			'ab-dashboard',
			array( $this, 'render_dashboard' ),
			'dashicons-calendar-alt',
			26
		);

		add_submenu_page( 'ab-dashboard', __( 'Dashboard', 'appointment-booking-system' ), __( 'Dashboard', 'appointment-booking-system' ), $cap, 'ab-dashboard', array( $this, 'render_dashboard' ) );
		add_submenu_page( 'ab-dashboard', __( 'Treatment Categories', 'appointment-booking-system' ), __( 'Treatment Categories', 'appointment-booking-system' ), $cap, 'ab-categories', array( $this, 'render_categories' ) );
		add_submenu_page( 'ab-dashboard', __( 'Doctors', 'appointment-booking-system' ), __( 'Doctors', 'appointment-booking-system' ), $cap, 'ab-doctors', array( $this, 'render_doctors' ) );
		add_submenu_page( 'ab-dashboard', __( 'Services', 'appointment-booking-system' ), __( 'Services', 'appointment-booking-system' ), $cap, 'ab-services', array( $this, 'render_services' ) );
		add_submenu_page( 'ab-dashboard', __( 'Availability', 'appointment-booking-system' ), __( 'Availability', 'appointment-booking-system' ), $cap, 'ab-availability', array( $this, 'render_availability' ) );
		add_submenu_page( 'ab-dashboard', __( 'Appointments', 'appointment-booking-system' ), __( 'Appointments', 'appointment-booking-system' ), $cap, 'ab-appointments', array( $this, 'render_appointments' ) );
		add_submenu_page( 'ab-dashboard', __( 'Settings', 'appointment-booking-system' ), __( 'Settings', 'appointment-booking-system' ), $cap, 'ab-settings', array( $this, 'render_settings' ) );
		if ( \AB\Includes\Language\Translation_Service::is_wpml_active() ) {
			add_submenu_page( 'ab-dashboard', __( 'String Translations', 'appointment-booking-system' ), __( 'String Translations', 'appointment-booking-system' ), $cap, 'ab-string-translations', array( $this, 'render_string_translations' ) );
			add_submenu_page( null, __( 'Manage Translation', 'appointment-booking-system' ), __( 'Manage Translation', 'appointment-booking-system' ), $cap, 'ab-translation', array( $this, 'render_translation' ) );
		}
		add_submenu_page( 'ab-dashboard', __( 'Activity Logs', 'appointment-booking-system' ), __( 'Activity Logs', 'appointment-booking-system' ), $cap, 'ab-activity-logs', array( $this, 'render_activity_logs' ) );
		add_submenu_page( 'ab-dashboard', __( 'Help', 'appointment-booking-system' ), __( 'Help', 'appointment-booking-system' ), $cap, 'ab-help', array( $this, 'render_help' ) );
	}

	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'ab-' ) === false && strpos( $hook, 'page_ab-' ) === false ) {
			return;
		}

		wp_enqueue_style( 'ab-admin-css', AB_PLUGIN_URL . 'assets/css/admin.css', array(), AB_VERSION );
		wp_enqueue_media();
		wp_enqueue_script( 'ab-admin-js', AB_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), AB_VERSION, true );

		wp_localize_script(
			'ab-admin-js',
			'AB_ADMIN',
			array(
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( Security::ADMIN_NONCE ),
				'i18n'     => array(
					'confirmDelete' => __( 'Are you sure you want to delete this item? This cannot be undone.', 'appointment-booking-system' ),
				),
			)
		);
	}

	/**
	 * Show contextual admin notices guiding first-time setup.
	 */
	public function setup_notices() {
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'ab-' ) === false ) {
			return;
		}

		$categories = new \AB\Includes\Models\Category_Model();
		$doctors    = new \AB\Includes\Models\Doctor_Model();

		if ( 0 === $categories->count_all() ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'Please create at least one Treatment Category.', 'appointment-booking-system' ) . '</p></div>';
		} elseif ( 0 === $doctors->count_all() ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'Please add doctors before accepting appointments.', 'appointment-booking-system' ) . '</p></div>';
		}
	}

	protected function view( $name, $data = array() ) {
		extract( $data ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		$path = AB_PLUGIN_DIR . 'admin/views/' . $name . '.php';
		if ( file_exists( $path ) ) {
			include $path;
		}
	}

	public function render_dashboard() {
		$this->view( 'dashboard' );
	}
	public function render_categories() {
		$this->view( 'categories' );
	}
	public function render_doctors() {
		$this->view( 'doctors' );
	}
	public function render_services() {
		$this->view( 'services' );
	}
	public function render_availability() {
		$this->view( 'availability' );
	}
	public function render_appointments() {
		$this->view( 'appointments' );
	}
	public function render_settings() {
		$this->view( 'settings' );
	}
	public function render_help() {
		$this->view( 'help' );
	}
	public function render_translation() {
		if ( ! \AB\Includes\Language\Translation_Service::is_wpml_active() ) {
			wp_die( esc_html__( 'WPML / Multilingual functionality is not active on this site.', 'appointment-booking-system' ) );
		}
		$this->view( 'translation' );
	}
	public function render_string_translations() {
		if ( ! \AB\Includes\Language\Translation_Service::is_wpml_active() ) {
			wp_die( esc_html__( 'WPML / Multilingual functionality is not active on this site.', 'appointment-booking-system' ) );
		}
		$this->view( 'string-translations' );
	}
	public function render_activity_logs() {
		$this->view( 'activity-logs' );
	}
}

