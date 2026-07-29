<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AB\Includes\Models\Appointment_Model;
use AB\Includes\Models\Doctor_Model;
use AB\Includes\Models\Category_Model;
use AB\Includes\Language\Translation_Service;

$appointment_model = new Appointment_Model();
$doctor_model       = new Doctor_Model();
$category_model     = new Category_Model();

// Load language-specific strings for active WP / WPML language
$_cur_lang  = Translation_Service::get_current_language();
$_lang_strs = Translation_Service::get_i18n_strings( $_cur_lang );
$t          = function( $key, $fallback ) use ( $_lang_strs ) {
	return ! empty( $_lang_strs[ $key ] ) ? $_lang_strs[ $key ] : $fallback;
};

$doctors    = $doctor_model->all();
$categories = $category_model->all();

$doctors_by_id    = wp_list_pluck( $doctors, 'name', 'id' );
$categories_by_id = wp_list_pluck( $categories, 'name', 'id' );

// phpcs:disable WordPress.Security.NonceVerification.Recommended
$args = array(
	'doctor_id'   => isset( $_GET['doctor_id'] ) ? absint( $_GET['doctor_id'] ) : 0,
	'category_id' => isset( $_GET['category_id'] ) ? absint( $_GET['category_id'] ) : 0,
	'status'      => isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '',
	'date'        => isset( $_GET['date'] ) ? sanitize_text_field( wp_unslash( $_GET['date'] ) ) : '',
	'search'      => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
	'page'        => isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1,
	'per_page'    => 20,
);
// phpcs:enable WordPress.Security.NonceVerification.Recommended

$result      = $appointment_model->search( $args );
$total_pages = max( 1, (int) ceil( $result['total'] / $args['per_page'] ) );
$status_labels = ab_get_status_labels();
?>
<div class="wrap ab-wrap">
	<h1>
		<?php echo esc_html( $t( 'app_page_title', __( 'Appointments', 'appointment-booking-system' ) ) ); ?>
		<a class="page-title-action" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array_merge( $args, array( 'action' => 'ab_export_appointments' ) ), admin_url( 'admin-post.php' ) ), 'ab_admin_nonce' ) ); ?>"><?php echo esc_html( $t( 'app_btn_export_csv', __( 'Export CSV', 'appointment-booking-system' ) ) ); ?></a>
	</h1>
	<?php include __DIR__ . '/partials/notice.php'; ?>

	<form method="get" class="ab-filter-bar">
		<input type="hidden" name="page" value="ab-appointments" />
		<input type="search" name="s" placeholder="<?php echo esc_attr( $t( 'app_search_ph', __( 'Search name, phone, email, booking ID…', 'appointment-booking-system' ) ) ); ?>" value="<?php echo esc_attr( $args['search'] ); ?>" />
		<select name="doctor_id">
			<option value=""><?php echo esc_html( $t( 'all_doctors', __( 'All Doctors', 'appointment-booking-system' ) ) ); ?></option>
			<?php foreach ( $doctors as $doc ) : ?>
				<option value="<?php echo esc_attr( $doc['id'] ); ?>" <?php selected( $args['doctor_id'], $doc['id'] ); ?>><?php echo esc_html( $doc['name'] ); ?></option>
			<?php endforeach; ?>
		</select>
		<select name="category_id">
			<option value=""><?php echo esc_html( $t( 'all_categories', __( 'All Categories', 'appointment-booking-system' ) ) ); ?></option>
			<?php foreach ( $categories as $cat ) : ?>
				<option value="<?php echo esc_attr( $cat['id'] ); ?>" <?php selected( $args['category_id'], $cat['id'] ); ?>><?php echo esc_html( $cat['name'] ); ?></option>
			<?php endforeach; ?>
		</select>
		<select name="status">
			<option value=""><?php echo esc_html( $t( 'all_statuses', __( 'All Statuses', 'appointment-booking-system' ) ) ); ?></option>
			<?php foreach ( $status_labels as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $args['status'], $key ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<input type="date" name="date" value="<?php echo esc_attr( $args['date'] ); ?>" />
		<button class="button"><?php echo esc_html( $t( 'btn_filter', __( 'Filter', 'appointment-booking-system' ) ) ); ?></button>
		<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ab-appointments' ) ); ?>"><?php echo esc_html( $t( 'btn_reset', __( 'Reset', 'appointment-booking-system' ) ) ); ?></a>
	</form>

	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php echo esc_html( $t( 'dash_booking_id', __( 'Booking ID', 'appointment-booking-system' ) ) ); ?></th>
				<th><?php echo esc_html( $t( 'dash_patient', __( 'Patient', 'appointment-booking-system' ) ) ); ?></th>
				<th><?php echo esc_html( $t( 'col_phone', __( 'Phone', 'appointment-booking-system' ) ) ); ?></th>
				<th><?php echo esc_html( $t( 'col_email', __( 'Email', 'appointment-booking-system' ) ) ); ?></th>
				<th><?php echo esc_html( $t( 'col_category', __( 'Category', 'appointment-booking-system' ) ) ); ?></th>
				<th><?php echo esc_html( $t( 'dash_doctor', __( 'Doctor', 'appointment-booking-system' ) ) ); ?></th>
				<th><?php echo esc_html( $t( 'dash_date', __( 'Date', 'appointment-booking-system' ) ) ); ?></th>
				<th><?php echo esc_html( $t( 'dash_time', __( 'Time', 'appointment-booking-system' ) ) ); ?></th>
				<th><?php echo esc_html( $t( 'dash_status', __( 'Status', 'appointment-booking-system' ) ) ); ?></th>
				<th><?php echo esc_html( $t( 'col_actions', __( 'Actions', 'appointment-booking-system' ) ) ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( $result['items'] ) : ?>
			<?php foreach ( $result['items'] as $row ) : ?>
				<?php $services = $appointment_model->get_services( $row['id'] ); ?>
				<tr>
					<td><?php echo esc_html( $row['booking_id'] ); ?></td>
					<td><?php echo esc_html( $row['patient_name'] ); ?></td>
					<td><?php echo esc_html( $row['phone'] ); ?></td>
					<td><?php echo esc_html( $row['email'] ); ?></td>
					<td><?php echo esc_html( $categories_by_id[ $row['category_id'] ] ?? '—' ); ?></td>
					<td><?php echo esc_html( $doctors_by_id[ $row['doctor_id'] ] ?? '—' ); ?></td>
					<td><?php echo esc_html( ab_format_date( $row['appointment_date'] ) ); ?></td>
					<td><?php echo esc_html( ab_format_time( $row['appointment_time'] ) ); ?></td>
					<td><span class="ab-badge ab-badge-<?php echo esc_attr( $row['status'] ); ?>"><?php echo esc_html( $status_labels[ $row['status'] ] ?? $row['status'] ); ?></span></td>
					<td class="ab-actions-cell">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ab-inline-form">
							<?php wp_nonce_field( 'ab_admin_nonce' ); ?>
							<input type="hidden" name="action" value="ab_update_appointment_status" />
							<input type="hidden" name="id" value="<?php echo esc_attr( $row['id'] ); ?>" />
							<select name="status" onchange="this.form.submit()">
								<?php foreach ( $status_labels as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $row['status'], $key ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</form>
						<a class="ab-delete-link" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ab_delete_appointment&id=' . $row['id'] ), 'ab_admin_nonce' ) ); ?>"><?php echo esc_html( $t( 'btn_delete', __( 'Delete', 'appointment-booking-system' ) ) ); ?></a>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php else : ?>
			<tr><td colspan="10"><?php echo esc_html( $t( 'app_no_appointments', __( 'No appointments found.', 'appointment-booking-system' ) ) ); ?></td></tr>
		<?php endif; ?>
		</tbody>
	</table>

	<?php if ( $total_pages > 1 ) : ?>
		<div class="ab-pagination">
			<?php for ( $p = 1; $p <= $total_pages; $p++ ) : ?>
				<a class="<?php echo $p === $args['page'] ? 'current' : ''; ?>" href="<?php echo esc_url( add_query_arg( array_merge( $args, array( 'paged' => $p ) ), admin_url( 'admin.php?page=ab-appointments' ) ) ); ?>"><?php echo esc_html( $p ); ?></a>
			<?php endfor; ?>
		</div>
	<?php endif; ?>
</div>
