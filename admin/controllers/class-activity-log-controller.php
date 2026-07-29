<?php
namespace AB\Admin\Controllers;

use AB\Includes\Security\Security;
use AB\Includes\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles administrator management of activity logs.
 */
class Activity_Log_Controller {

	public function __construct() {
		add_action( 'admin_post_ab_clear_activity_logs', array( $this, 'clear_logs' ) );
	}

	public function clear_logs() {
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'ab_admin_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'appointment-booking-system' ) );
		}

		if ( ! current_user_can( Security::ADMIN_CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to clear logs.', 'appointment-booking-system' ) );
		}

		Logger::clear_all_logs();

		$url = add_query_arg(
			array(
				'page'      => 'ab-activity-logs',
				'ab_notice' => 'success',
				'ab_msg'    => rawurlencode( __( 'All activity logs cleared successfully.', 'appointment-booking-system' ) ),
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $url );
		exit;
	}
}
