<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AB\Includes\Models\Doctor_Model;
use AB\Includes\Models\Availability_Model;
use AB\Includes\Models\Holiday_Model;

$doctor_model      = new Doctor_Model();
$availability_model = new Availability_Model();
$holiday_model     = new Holiday_Model();

$doctors   = $doctor_model->all();
$doctor_id = isset( $_GET['doctor_id'] ) ? absint( $_GET['doctor_id'] ) : ( $doctors ? $doctors[0]['id'] : 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$existing_rows = $doctor_id ? $availability_model->get_for_doctor( $doctor_id ) : array();
$by_day        = array();
foreach ( $existing_rows as $row ) {
	$by_day[ (int) $row['day'] ] = $row;
}
$holidays  = $doctor_id ? $holiday_model->get_for_doctor( $doctor_id ) : array();
$day_names = ab_get_day_names();
$intervals = array( 15, 20, 30, 45, 60 );
?>
<div class="wrap ab-wrap">
	<h1><?php esc_html_e( 'Availability Management', 'appointment-booking-system' ); ?></h1>
	<?php include __DIR__ . '/partials/notice.php'; ?>

	<form method="get" class="ab-filter-bar">
		<input type="hidden" name="page" value="ab-availability" />
		<label for="doctor_id"><strong><?php esc_html_e( 'Doctor', 'appointment-booking-system' ); ?>:</strong></label>
		<select id="doctor_id" name="doctor_id" onchange="this.form.submit()">
			<?php foreach ( $doctors as $doc ) : ?>
				<option value="<?php echo esc_attr( $doc['id'] ); ?>" <?php selected( $doctor_id, $doc['id'] ); ?>><?php echo esc_html( $doc['name'] ); ?></option>
			<?php endforeach; ?>
		</select>
	</form>

	<?php if ( ! $doctors ) : ?>
		<p><?php esc_html_e( 'Please add a doctor first.', 'appointment-booking-system' ); ?></p>
	<?php else : ?>

	<h2><?php esc_html_e( 'Weekly Working Hours', 'appointment-booking-system' ); ?></h2>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'ab_admin_nonce' ); ?>
		<input type="hidden" name="action" value="ab_save_availability" />
		<input type="hidden" name="doctor_id" value="<?php echo esc_attr( $doctor_id ); ?>" />

		<table class="widefat ab-availability-table">
			<thead><tr>
				<th><?php esc_html_e( 'Day', 'appointment-booking-system' ); ?></th>
				<th><?php esc_html_e( 'Enabled', 'appointment-booking-system' ); ?></th>
				<th><?php esc_html_e( 'Start Time', 'appointment-booking-system' ); ?></th>
				<th><?php esc_html_e( 'End Time', 'appointment-booking-system' ); ?></th>
				<th><?php esc_html_e( 'Break Start', 'appointment-booking-system' ); ?></th>
				<th><?php esc_html_e( 'Break End', 'appointment-booking-system' ); ?></th>
				<th><?php esc_html_e( 'Slot Interval', 'appointment-booking-system' ); ?></th>
			</tr></thead>
			<tbody>
			<?php foreach ( $day_names as $day_num => $day_label ) : $row = $by_day[ $day_num ] ?? null; ?>
				<tr>
					<td><?php echo esc_html( $day_label ); ?></td>
					<td><input type="checkbox" class="ab-day-toggle" name="day[]" value="<?php echo esc_attr( $day_num ); ?>" <?php checked( (bool) $row ); ?> onchange="this.closest('tr').querySelectorAll('.ab-day-fields').forEach(f=>f.disabled=!this.checked)" /></td>
					<td><input type="time" class="ab-day-fields" name="start_time[]" value="<?php echo esc_attr( $row['start_time'] ?? '09:00' ); ?>" <?php disabled( ! $row ); ?> /></td>
					<td><input type="time" class="ab-day-fields" name="end_time[]" value="<?php echo esc_attr( $row['end_time'] ?? '17:00' ); ?>" <?php disabled( ! $row ); ?> /></td>
					<td><input type="time" class="ab-day-fields" name="break_start[]" value="<?php echo esc_attr( $row['break_start'] ?? '13:00' ); ?>" <?php disabled( ! $row ); ?> /></td>
					<td><input type="time" class="ab-day-fields" name="break_end[]" value="<?php echo esc_attr( $row['break_end'] ?? '14:00' ); ?>" <?php disabled( ! $row ); ?> /></td>
					<td>
						<select class="ab-day-fields" name="slot_duration[]" <?php disabled( ! $row ); ?>>
							<?php foreach ( $intervals as $mins ) : ?>
								<option value="<?php echo esc_attr( $mins ); ?>" <?php selected( (int) ( $row['slot_duration'] ?? 30 ), $mins ); ?>><?php echo esc_html( $mins ); ?> <?php esc_html_e( 'Minutes', 'appointment-booking-system' ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description"><?php esc_html_e( 'Tick "Enabled" for each day the doctor is available, then set working hours, optional break time, and slot interval. Each time slot allows only one appointment.', 'appointment-booking-system' ); ?></p>
		<p class="submit"><button type="submit" class="button button-primary"><?php esc_html_e( 'Save Weekly Availability', 'appointment-booking-system' ); ?></button></p>
	</form>

	<hr />

	<div class="ab-columns">
		<div class="ab-col ab-col-form">
			<h2><?php esc_html_e( 'Add Holiday / Special Working Day', 'appointment-booking-system' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'ab_admin_nonce' ); ?>
				<input type="hidden" name="action" value="ab_save_holiday" />
				<input type="hidden" name="doctor_id" value="<?php echo esc_attr( $doctor_id ); ?>" />
				<table class="form-table">
					<tr><th><?php esc_html_e( 'Type', 'appointment-booking-system' ); ?></th>
						<td>
							<select name="holiday_type">
								<option value="holiday"><?php esc_html_e( 'Holiday (disable date)', 'appointment-booking-system' ); ?></option>
								<option value="special_working"><?php esc_html_e( 'Special Working Day (open on holiday)', 'appointment-booking-system' ); ?></option>
							</select>
						</td></tr>
					<tr><th><?php esc_html_e( 'Mode', 'appointment-booking-system' ); ?></th>
						<td>
							<label><input type="radio" name="mode" value="date" checked /> <?php esc_html_e( 'Single Date / Range', 'appointment-booking-system' ); ?></label>
							<label style="margin-left:12px;"><input type="radio" name="mode" value="recurring" /> <?php esc_html_e( 'Recurring Weekly', 'appointment-booking-system' ); ?></label>
						</td></tr>
					<tr><th><label for="holiday_date"><?php esc_html_e( 'Date', 'appointment-booking-system' ); ?></label></th>
						<td><input type="date" id="holiday_date" name="holiday_date" /></td></tr>
					<tr><th><label for="end_date"><?php esc_html_e( 'End Date (optional, for ranges)', 'appointment-booking-system' ); ?></label></th>
						<td><input type="date" id="end_date" name="end_date" /></td></tr>
					<tr><th><label for="recurring_day"><?php esc_html_e( 'Recurring Day', 'appointment-booking-system' ); ?></label></th>
						<td>
							<select id="recurring_day" name="recurring_day">
								<option value=""><?php esc_html_e( '— None —', 'appointment-booking-system' ); ?></option>
								<?php foreach ( $day_names as $day_num => $day_label ) : ?>
									<option value="<?php echo esc_attr( $day_num ); ?>">Every <?php echo esc_html( $day_label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td></tr>
					<tr><th><label for="note"><?php esc_html_e( 'Note', 'appointment-booking-system' ); ?></label></th>
						<td><input type="text" id="note" name="note" class="regular-text" /></td></tr>
				</table>
				<p class="submit"><button type="submit" class="button button-primary"><?php esc_html_e( 'Save', 'appointment-booking-system' ); ?></button></p>
			</form>
		</div>

		<div class="ab-col">
			<h2><?php esc_html_e( 'Holidays & Overrides for This Doctor', 'appointment-booking-system' ); ?></h2>
			<table class="widefat striped">
				<thead><tr>
					<th><?php esc_html_e( 'Type', 'appointment-booking-system' ); ?></th>
					<th><?php esc_html_e( 'Date / Recurrence', 'appointment-booking-system' ); ?></th>
					<th><?php esc_html_e( 'Note', 'appointment-booking-system' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'appointment-booking-system' ); ?></th>
				</tr></thead>
				<tbody>
				<?php if ( $holidays ) : ?>
					<?php foreach ( $holidays as $h ) : ?>
						<tr>
							<td><?php echo 'special_working' === $h['type'] ? esc_html__( 'Special Working Day', 'appointment-booking-system' ) : esc_html__( 'Holiday', 'appointment-booking-system' ); ?></td>
							<td>
								<?php
								if ( ! empty( $h['recurring_day'] ) || '0' === $h['recurring_day'] ) {
									echo esc_html( 'Every ' . $day_names[ (int) $h['recurring_day'] ] );
								} elseif ( ! empty( $h['end_date'] ) ) {
									echo esc_html( ab_format_date( $h['holiday_date'] ) . ' – ' . ab_format_date( $h['end_date'] ) );
								} else {
									echo esc_html( ab_format_date( $h['holiday_date'] ) );
								}
								?>
							</td>
							<td><?php echo esc_html( $h['note'] ); ?></td>
							<td><a class="ab-delete-link" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ab_delete_holiday&id=' . $h['id'] . '&doctor_id=' . $doctor_id ), 'ab_admin_nonce' ) ); ?>"><?php esc_html_e( 'Remove', 'appointment-booking-system' ); ?></a></td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr><td colspan="4"><?php esc_html_e( 'No holidays configured.', 'appointment-booking-system' ); ?></td></tr>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
	<?php endif; ?>
</div>
