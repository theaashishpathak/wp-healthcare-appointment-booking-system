<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AB\Includes\Models\Category_Model;
use AB\Includes\Models\Doctor_Model;
use AB\Includes\Models\Service_Model;
use AB\Includes\Models\Appointment_Model;
use AB\Includes\Language\Translation_Service;

$categories_model   = new Category_Model();
$doctors_model      = new Doctor_Model();
$services_model     = new Service_Model();
$appointments_model = new Appointment_Model();

$stats           = $appointments_model->get_stats();
$recent          = $appointments_model->get_recent( 8 );
$upcoming        = $appointments_model->get_upcoming_schedule( 8 );
$status_labels   = ab_get_status_labels();

// Load translated strings for active language context
$_cur_lang = Translation_Service::get_current_language();
$_lang_strs = Translation_Service::get_i18n_strings( $_cur_lang );

$t = function ( $key, $fallback ) use ( $_lang_strs ) {
	return ! empty( $_lang_strs[ $key ] ) ? $_lang_strs[ $key ] : $fallback;
};

$doctors_by_id = array();
foreach ( $doctors_model->all() as $doc ) {
	$doctors_by_id[ $doc['id'] ] = $doc['name'];
}
?>
<div class="wrap ab-wrap">
	<h1><?php echo esc_html( $t( 'dash_title', __( 'Appointment Booking Dashboard', 'appointment-booking-system' ) ) ); ?></h1>
	<?php include __DIR__ . '/partials/notice.php'; ?>

	<div class="ab-stat-grid">
		<div class="ab-stat-card">
			<span class="ab-stat-num"><?php echo esc_html( $categories_model->count_all() ); ?></span>
			<span class="ab-stat-label"><?php echo esc_html( $t( 'dash_total_categories', __( 'Total Categories', 'appointment-booking-system' ) ) ); ?></span>
		</div>
		<div class="ab-stat-card">
			<span class="ab-stat-num"><?php echo esc_html( $doctors_model->count_all() ); ?></span>
			<span class="ab-stat-label"><?php echo esc_html( $t( 'dash_total_doctors', __( 'Total Doctors', 'appointment-booking-system' ) ) ); ?></span>
		</div>
		<div class="ab-stat-card">
			<span class="ab-stat-num"><?php echo esc_html( $services_model->count_all() ); ?></span>
			<span class="ab-stat-label"><?php echo esc_html( $t( 'dash_total_services', __( 'Total Services', 'appointment-booking-system' ) ) ); ?></span>
		</div>
		<div class="ab-stat-card">
			<span class="ab-stat-num"><?php echo esc_html( $stats['today'] ); ?></span>
			<span class="ab-stat-label"><?php echo esc_html( $t( 'dash_todays_appointments', __( "Today's Appointments", 'appointment-booking-system' ) ) ); ?></span>
		</div>
		<div class="ab-stat-card">
			<span class="ab-stat-num"><?php echo esc_html( $stats['upcoming'] ); ?></span>
			<span class="ab-stat-label"><?php echo esc_html( $t( 'dash_upcoming_appointments', __( 'Upcoming Appointments', 'appointment-booking-system' ) ) ); ?></span>
		</div>
		<div class="ab-stat-card">
			<span class="ab-stat-num"><?php echo esc_html( $stats['pending'] ); ?></span>
			<span class="ab-stat-label"><?php echo esc_html( $t( 'dash_pending_appointments', __( 'Pending Appointments', 'appointment-booking-system' ) ) ); ?></span>
		</div>
		<div class="ab-stat-card">
			<span class="ab-stat-num"><?php echo esc_html( $stats['confirmed'] ); ?></span>
			<span class="ab-stat-label"><?php echo esc_html( $t( 'dash_confirmed_appointments', __( 'Confirmed Appointments', 'appointment-booking-system' ) ) ); ?></span>
		</div>
		<div class="ab-stat-card">
			<span class="ab-stat-num"><?php echo esc_html( $stats['cancelled'] ); ?></span>
			<span class="ab-stat-label"><?php echo esc_html( $t( 'dash_cancelled_appointments', __( 'Cancelled Appointments', 'appointment-booking-system' ) ) ); ?></span>
		</div>
	</div>

	<div class="ab-quick-actions">
		<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=ab-categories' ) ); ?>"><?php echo esc_html( $t( 'dash_add_category', __( '+ Add Category', 'appointment-booking-system' ) ) ); ?></a>
		<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ab-doctors' ) ); ?>"><?php echo esc_html( $t( 'dash_add_doctor', __( '+ Add Doctor', 'appointment-booking-system' ) ) ); ?></a>
		<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ab-services' ) ); ?>"><?php echo esc_html( $t( 'dash_add_service', __( '+ Add Service', 'appointment-booking-system' ) ) ); ?></a>
		<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ab-availability' ) ); ?>"><?php echo esc_html( $t( 'dash_manage_availability', __( 'Manage Availability', 'appointment-booking-system' ) ) ); ?></a>
	</div>

	<div class="ab-columns">
		<div class="ab-col">
			<h2><?php echo esc_html( $t( 'dash_recent_appointments', __( 'Recent Appointments', 'appointment-booking-system' ) ) ); ?></h2>
			<table class="widefat striped">
				<thead><tr>
					<th><?php echo esc_html( $t( 'dash_booking_id', __( 'Booking ID', 'appointment-booking-system' ) ) ); ?></th>
					<th><?php echo esc_html( $t( 'dash_patient', __( 'Patient', 'appointment-booking-system' ) ) ); ?></th>
					<th><?php echo esc_html( $t( 'dash_doctor', __( 'Doctor', 'appointment-booking-system' ) ) ); ?></th>
					<th><?php echo esc_html( $t( 'dash_date', __( 'Date', 'appointment-booking-system' ) ) ); ?></th>
					<th><?php echo esc_html( $t( 'dash_status', __( 'Status', 'appointment-booking-system' ) ) ); ?></th>
				</tr></thead>
				<tbody>
				<?php if ( $recent ) : ?>
					<?php foreach ( $recent as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row['booking_id'] ); ?></td>
							<td><?php echo esc_html( $row['patient_name'] ); ?></td>
							<td><?php echo esc_html( $doctors_by_id[ $row['doctor_id'] ] ?? '—' ); ?></td>
							<td><?php echo esc_html( ab_format_date( $row['appointment_date'] ) ); ?></td>
							<td><span class="ab-badge ab-badge-<?php echo esc_attr( $row['status'] ); ?>"><?php echo esc_html( $status_labels[ $row['status'] ] ?? $row['status'] ); ?></span></td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr><td colspan="5"><?php echo esc_html( $t( 'dash_no_appointments', __( 'No appointments yet.', 'appointment-booking-system' ) ) ); ?></td></tr>
				<?php endif; ?>
				</tbody>
			</table>
		</div>

		<div class="ab-col">
			<h2><?php echo esc_html( $t( 'dash_upcoming_schedule', __( 'Upcoming Schedule', 'appointment-booking-system' ) ) ); ?></h2>
			<table class="widefat striped">
				<thead><tr>
					<th><?php echo esc_html( $t( 'dash_date', __( 'Date', 'appointment-booking-system' ) ) ); ?></th>
					<th><?php echo esc_html( $t( 'dash_time', __( 'Time', 'appointment-booking-system' ) ) ); ?></th>
					<th><?php echo esc_html( $t( 'dash_patient', __( 'Patient', 'appointment-booking-system' ) ) ); ?></th>
					<th><?php echo esc_html( $t( 'dash_doctor', __( 'Doctor', 'appointment-booking-system' ) ) ); ?></th>
				</tr></thead>
				<tbody>
				<?php if ( $upcoming ) : ?>
					<?php foreach ( $upcoming as $row ) : ?>
						<tr>
							<td><?php echo esc_html( ab_format_date( $row['appointment_date'] ) ); ?></td>
							<td><?php echo esc_html( ab_format_time( $row['appointment_time'] ) ); ?></td>
							<td><?php echo esc_html( $row['patient_name'] ); ?></td>
							<td><?php echo esc_html( $doctors_by_id[ $row['doctor_id'] ] ?? '—' ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr><td colspan="4"><?php echo esc_html( $t( 'dash_no_upcoming', __( 'No upcoming appointments.', 'appointment-booking-system' ) ) ); ?></td></tr>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
