<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AB\Includes\Models\Doctor_Model;
use AB\Includes\Models\Availability_Model;
use AB\Includes\Models\Holiday_Model;
use AB\Includes\Language\Translation_Service;

$doctor_model       = new Doctor_Model();
$availability_model = new Availability_Model();
$holiday_model      = new Holiday_Model();

// Load language-specific strings for active WP / WPML language
$_cur_lang  = Translation_Service::get_current_language();
$_lang_strs = Translation_Service::get_i18n_strings( $_cur_lang );
$t          = function( $key, $fallback ) use ( $_lang_strs ) {
	return ! empty( $_lang_strs[ $key ] ) ? $_lang_strs[ $key ] : $fallback;
};

$doctors   = $doctor_model->all();
$doctor_id = isset( $_GET['doctor_id'] ) ? absint( $_GET['doctor_id'] ) : ( $doctors ? $doctors[0]['id'] : 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$existing_rows = $doctor_id ? $availability_model->get_for_doctor( $doctor_id ) : array();
$by_day        = array();
foreach ( $existing_rows as $row ) {
	$by_day[ (int) $row['day'] ] = $row;
}
$holidays  = $doctor_id ? $holiday_model->get_for_doctor( $doctor_id ) : array();

$day_names = array(
	0 => $t( 'day_sunday', __( 'Sunday', 'appointment-booking-system' ) ),
	1 => $t( 'day_monday', __( 'Monday', 'appointment-booking-system' ) ),
	2 => $t( 'day_tuesday', __( 'Tuesday', 'appointment-booking-system' ) ),
	3 => $t( 'day_wednesday', __( 'Wednesday', 'appointment-booking-system' ) ),
	4 => $t( 'day_thursday', __( 'Thursday', 'appointment-booking-system' ) ),
	5 => $t( 'day_friday', __( 'Friday', 'appointment-booking-system' ) ),
	6 => $t( 'day_saturday', __( 'Saturday', 'appointment-booking-system' ) ),
);

$intervals = array( 15, 20, 30, 45, 60 );
?>
<div class="wrap ab-wrap">
	<h1><?php echo esc_html( $t( 'avail_page_title', __( 'Availability Management', 'appointment-booking-system' ) ) ); ?></h1>
	<?php include __DIR__ . '/partials/notice.php'; ?>

	<form method="get" class="ab-filter-bar">
		<input type="hidden" name="page" value="ab-availability" />
		<label for="doctor_id"><strong><?php echo esc_html( $t( 'dash_doctor', __( 'Doctor', 'appointment-booking-system' ) ) ); ?>:</strong></label>
		<select id="doctor_id" name="doctor_id" onchange="this.form.submit()">
			<?php foreach ( $doctors as $doc ) : ?>
				<option value="<?php echo esc_attr( $doc['id'] ); ?>" <?php selected( $doctor_id, $doc['id'] ); ?>><?php echo esc_html( $doc['name'] ); ?></option>
			<?php endforeach; ?>
		</select>
	</form>

	<?php if ( ! $doctors ) : ?>
		<p><?php echo esc_html( $t( 'avail_add_doc_first', __( 'Please add a doctor first.', 'appointment-booking-system' ) ) ); ?></p>
	<?php else : ?>

	<h2><?php echo esc_html( $t( 'avail_weekly_heading', __( 'Weekly Working Hours', 'appointment-booking-system' ) ) ); ?></h2>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'ab_admin_nonce' ); ?>
		<input type="hidden" name="action" value="ab_save_availability" />
		<input type="hidden" name="doctor_id" value="<?php echo esc_attr( $doctor_id ); ?>" />

		<table class="widefat ab-availability-table">
			<thead>
				<tr>
					<th><?php echo esc_html( $t( 'avail_col_day', __( 'Day', 'appointment-booking-system' ) ) ); ?></th>
					<th><?php echo esc_html( $t( 'avail_col_enabled', __( 'Enabled', 'appointment-booking-system' ) ) ); ?></th>
					<th><?php echo esc_html( $t( 'avail_col_start_time', __( 'Start Time', 'appointment-booking-system' ) ) ); ?></th>
					<th><?php echo esc_html( $t( 'avail_col_end_time', __( 'End Time', 'appointment-booking-system' ) ) ); ?></th>
					<th><?php echo esc_html( $t( 'avail_col_break_start', __( 'Break Start', 'appointment-booking-system' ) ) ); ?></th>
					<th><?php echo esc_html( $t( 'avail_col_break_end', __( 'Break End', 'appointment-booking-system' ) ) ); ?></th>
					<th><?php echo esc_html( $t( 'avail_col_slot_interval', __( 'Slot Interval', 'appointment-booking-system' ) ) ); ?></th>
				</tr>
			</thead>
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
								<option value="<?php echo esc_attr( $mins ); ?>" <?php selected( (int) ( $row['slot_duration'] ?? 30 ), $mins ); ?>><?php echo esc_html( $mins ); ?> <?php echo esc_html( $t( 'minutes', __( 'Minutes', 'appointment-booking-system' ) ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description"><?php echo esc_html( $t( 'avail_help_text', __( 'Tick "Enabled" for each day the doctor is available, then set working hours, optional break time, and slot interval. Each time slot allows only one appointment.', 'appointment-booking-system' ) ) ); ?></p>
		<p class="submit"><button type="submit" class="button button-primary"><?php echo esc_html( $t( 'avail_btn_save', __( 'Save Weekly Availability', 'appointment-booking-system' ) ) ); ?></button></p>
	</form>

	<hr />

	<div class="ab-columns">
		<div class="ab-col ab-col-form">
			<h2><?php echo esc_html( $t( 'hol_add_heading', __( 'Add Holiday / Special Working Day', 'appointment-booking-system' ) ) ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'ab_admin_nonce' ); ?>
				<input type="hidden" name="action" value="ab_save_holiday" />
				<input type="hidden" name="doctor_id" value="<?php echo esc_attr( $doctor_id ); ?>" />
				<table class="form-table">
					<tr>
						<th><?php echo esc_html( $t( 'hol_field_type', __( 'Type', 'appointment-booking-system' ) ) ); ?></th>
						<td>
							<select name="holiday_type">
								<option value="holiday"><?php echo esc_html( $t( 'hol_type_holiday', __( 'Holiday (disable date)', 'appointment-booking-system' ) ) ); ?></option>
								<option value="special_working"><?php echo esc_html( $t( 'hol_type_special', __( 'Special Working Day (open on holiday)', 'appointment-booking-system' ) ) ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th><?php echo esc_html( $t( 'hol_field_mode', __( 'Mode', 'appointment-booking-system' ) ) ); ?></th>
						<td>
							<label><input type="radio" name="mode" value="date" checked /> <?php echo esc_html( $t( 'hol_mode_single', __( 'Single Date / Range', 'appointment-booking-system' ) ) ); ?></label>
							<label style="margin-left:12px;"><input type="radio" name="mode" value="recurring" /> <?php echo esc_html( $t( 'hol_mode_recurring', __( 'Recurring Weekly', 'appointment-booking-system' ) ) ); ?></label>
						</td>
					</tr>
					<tr>
						<th><label for="holiday_date"><?php echo esc_html( $t( 'dash_date', __( 'Date', 'appointment-booking-system' ) ) ); ?></label></th>
						<td><input type="date" id="holiday_date" name="holiday_date" /></td>
					</tr>
					<tr>
						<th><label for="end_date"><?php echo esc_html( $t( 'hol_field_end_date', __( 'End Date (optional, for ranges)', 'appointment-booking-system' ) ) ); ?></label></th>
						<td><input type="date" id="end_date" name="end_date" /></td>
					</tr>
					<tr>
						<th><label for="recurring_day"><?php echo esc_html( $t( 'hol_field_recurring_day', __( 'Recurring Day', 'appointment-booking-system' ) ) ); ?></label></th>
						<td>
							<select id="recurring_day" name="recurring_day">
								<option value=""><?php echo esc_html( $t( 'select_option', __( '— None —', 'appointment-booking-system' ) ) ); ?></option>
								<?php foreach ( $day_names as $day_num => $day_label ) : ?>
									<option value="<?php echo esc_attr( $day_num ); ?>"><?php echo esc_html( $t( 'every', __( 'Every', 'appointment-booking-system' ) ) . ' ' . $day_label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="note"><?php echo esc_html( $t( 'email_message', __( 'Note', 'appointment-booking-system' ) ) ); ?></label></th>
						<td><input type="text" id="note" name="note" class="regular-text" /></td>
					</tr>
				</table>
				<p class="submit"><button type="submit" class="button button-primary"><?php echo esc_html( $t( 'btn_save', __( 'Save', 'appointment-booking-system' ) ) ); ?></button></p>
			</form>
		</div>

		<div class="ab-col">
			<h2><?php echo esc_html( $t( 'hol_list_title', __( 'Holidays & Overrides for This Doctor', 'appointment-booking-system' ) ) ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php echo esc_html( $t( 'hol_field_type', __( 'Type', 'appointment-booking-system' ) ) ); ?></th>
						<th><?php echo esc_html( $t( 'hol_col_date_recurrence', __( 'Date / Recurrence', 'appointment-booking-system' ) ) ); ?></th>
						<th><?php echo esc_html( $t( 'email_message', __( 'Note', 'appointment-booking-system' ) ) ); ?></th>
						<th><?php echo esc_html( $t( 'col_actions', __( 'Actions', 'appointment-booking-system' ) ) ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( $holidays ) : ?>
					<?php foreach ( $holidays as $h ) : ?>
						<tr>
							<td><?php echo 'special_working' === $h['type'] ? esc_html( $t( 'hol_type_special_short', __( 'Special Working Day', 'appointment-booking-system' ) ) ) : esc_html( $t( 'hol_type_holiday_short', __( 'Holiday', 'appointment-booking-system' ) ) ); ?></td>
							<td>
								<?php
								if ( null !== $h['recurring_day'] ) {
									echo esc_html( $t( 'every', __( 'Every', 'appointment-booking-system' ) ) . ' ' . ( $day_names[ (int) $h['recurring_day'] ] ?? '' ) );
								} else {
									echo esc_html( ab_format_date( $h['holiday_date'] ) );
									if ( ! empty( $h['end_date'] ) ) {
										echo ' &ndash; ' . esc_html( ab_format_date( $h['end_date'] ) );
									}
								}
								?>
							</td>
							<td><?php echo esc_html( $h['note'] ); ?></td>
							<td>
								<a class="ab-delete-link" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ab_delete_holiday&id=' . $h['id'] . '&doctor_id=' . $doctor_id ), 'ab_admin_nonce' ) ); ?>"><?php echo esc_html( $t( 'btn_delete', __( 'Delete', 'appointment-booking-system' ) ) ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr><td colspan="4"><?php echo esc_html( $t( 'hol_no_holidays', __( 'No holidays configured.', 'appointment-booking-system' ) ) ); ?></td></tr>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
	<?php endif; ?>
</div>
