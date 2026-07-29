<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.Security.NonceVerification.Recommended
if ( ! empty( $_GET['ab_notice'] ) && ! empty( $_GET['ab_msg'] ) ) {
	$type    = sanitize_key( wp_unslash( $_GET['ab_notice'] ) );
	$message = sanitize_text_field( wp_unslash( $_GET['ab_msg'] ) );
	$class   = 'error' === $type ? 'notice-error' : 'notice-success';
	echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
}
// phpcs:enable WordPress.Security.NonceVerification.Recommended
