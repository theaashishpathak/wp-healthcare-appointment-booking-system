<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AB\Includes\Language\Translation_Service;

$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'strings';
$active_lang = isset( $_GET['lang'] ) ? sanitize_text_field( wp_unslash( $_GET['lang'] ) ) : 'de';

$languages = Translation_Service::get_languages();

if ( empty( $languages[ $active_lang ] ) ) {
	$languages[ $active_lang ] = array(
		'code'         => $active_lang,
		'display_name' => strtoupper( $active_lang ),
	);
}

// All editable string keys with human-readable descriptions and default fallbacks
$string_fields = array(
	'Step Titles & Header Labels' => array(
		'step_category' => array( 'label' => __( 'Step 1 Heading (Select Category)', 'appointment-booking-system' ), 'default' => 'Select Treatment Category' ),
		'step_doctor'   => array( 'label' => __( 'Step 2 Heading (Choose Doctor)', 'appointment-booking-system' ), 'default' => 'Choose Doctor' ),
		'step_services' => array( 'label' => __( 'Step 3 Heading (Choose Services)', 'appointment-booking-system' ), 'default' => 'Choose Services' ),
		'step_date'     => array( 'label' => __( 'Step 4 Heading (Choose Date)', 'appointment-booking-system' ), 'default' => 'Choose Appointment Date' ),
		'step_time'     => array( 'label' => __( 'Step 5 Heading (Choose Time)', 'appointment-booking-system' ), 'default' => 'Choose Available Time' ),
		'step_details'  => array( 'label' => __( 'Step 6 Heading (Personal Information)', 'appointment-booking-system' ), 'default' => 'Personal Information' ),
		'step_review'   => array( 'label' => __( 'Step 7 Heading (Review Appointment)', 'appointment-booking-system' ), 'default' => 'Review Appointment' ),
		'label_category'=> array( 'label' => __( 'Step Indicator 1 Label', 'appointment-booking-system' ), 'default' => 'Category' ),
		'label_doctor'  => array( 'label' => __( 'Step Indicator 2 Label', 'appointment-booking-system' ), 'default' => 'Doctor' ),
		'label_services'=> array( 'label' => __( 'Step Indicator 3 Label', 'appointment-booking-system' ), 'default' => 'Services' ),
		'label_date'    => array( 'label' => __( 'Step Indicator 4 Label', 'appointment-booking-system' ), 'default' => 'Date' ),
		'label_time'    => array( 'label' => __( 'Step Indicator 5 Label', 'appointment-booking-system' ), 'default' => 'Time' ),
		'label_details' => array( 'label' => __( 'Step Indicator 6 Label', 'appointment-booking-system' ), 'default' => 'Details' ),
		'label_review'  => array( 'label' => __( 'Step Indicator 7 Label', 'appointment-booking-system' ), 'default' => 'Review' ),
		'label_done'    => array( 'label' => __( 'Step Indicator 8 Label', 'appointment-booking-system' ), 'default' => 'Done' ),
	),
	'Navigation & Buttons' => array(
		'btn_back'    => array( 'label' => __( 'Back Button', 'appointment-booking-system' ), 'default' => 'Back' ),
		'btn_next'    => array( 'label' => __( 'Next Button', 'appointment-booking-system' ), 'default' => 'Next' ),
		'btn_submit'  => array( 'label' => __( 'Confirm Appointment Button', 'appointment-booking-system' ), 'default' => 'Confirm Appointment' ),
		'btn_restart' => array( 'label' => __( 'Book Another Appointment Button', 'appointment-booking-system' ), 'default' => 'Book Another Appointment' ),
	),
	'Form Field Labels & Summaries' => array(
		'field_first_name'    => array( 'label' => __( 'First Name Label', 'appointment-booking-system' ), 'default' => 'First Name' ),
		'field_last_name'     => array( 'label' => __( 'Last Name Label', 'appointment-booking-system' ), 'default' => 'Last Name' ),
		'field_email'         => array( 'label' => __( 'Email Label', 'appointment-booking-system' ), 'default' => 'Email' ),
		'field_phone'         => array( 'label' => __( 'Phone Number Label', 'appointment-booking-system' ), 'default' => 'Phone Number' ),
		'field_message'       => array( 'label' => __( 'Message Label', 'appointment-booking-system' ), 'default' => 'Message (Optional)' ),
		'total_duration'      => array( 'label' => __( 'Total Duration Prefix', 'appointment-booking-system' ), 'default' => 'Total Duration:' ),
		'total_duration_unit' => array( 'label' => __( 'Duration Unit Label', 'appointment-booking-system' ), 'default' => 'Minutes' ),
	),
	'Validation & Alert Messages' => array(
		'selectCategory' => array( 'label' => __( 'Alert: Select Category', 'appointment-booking-system' ), 'default' => 'Please select a treatment category to continue.' ),
		'selectDoctor'   => array( 'label' => __( 'Alert: Select Doctor', 'appointment-booking-system' ), 'default' => 'Please select a doctor to continue.' ),
		'selectService'  => array( 'label' => __( 'Alert: Select Service', 'appointment-booking-system' ), 'default' => 'Please select at least one service to continue.' ),
		'selectDate'     => array( 'label' => __( 'Alert: Select Date', 'appointment-booking-system' ), 'default' => 'Please choose an appointment date to continue.' ),
		'selectTime'     => array( 'label' => __( 'Alert: Select Time', 'appointment-booking-system' ), 'default' => 'Please choose an appointment time to continue.' ),
		'requiredField'  => array( 'label' => __( 'Alert: Field Required', 'appointment-booking-system' ), 'default' => 'This field is required.' ),
		'invalidEmail'   => array( 'label' => __( 'Alert: Invalid Email', 'appointment-booking-system' ), 'default' => 'Please enter a valid email address.' ),
		'invalidPhone'   => array( 'label' => __( 'Alert: Invalid Phone', 'appointment-booking-system' ), 'default' => 'Please enter a valid phone number.' ),
		'genericError'   => array( 'label' => __( 'Alert: Generic Error', 'appointment-booking-system' ), 'default' => 'Something went wrong. Please try again.' ),
		'noSlots'        => array( 'label' => __( 'Alert: No Time Slots', 'appointment-booking-system' ), 'default' => 'No time slots available on this date.' ),
	),
	'Success Confirmation Screen' => array(
		'success_heading' => array( 'label' => __( 'Success Title', 'appointment-booking-system' ), 'default' => 'Appointment Submitted Successfully' ),
		'success_p1'      => array( 'label' => __( 'Success Paragraph 1', 'appointment-booking-system' ), 'default' => 'Thank you for booking your appointment. Our team has received your request.' ),
		'success_p2'      => array( 'label' => __( 'Success Paragraph 2', 'appointment-booking-system' ), 'default' => 'A confirmation email has been sent to your registered email address.' ),
		'success_p3'      => array( 'label' => __( 'Success Paragraph 3', 'appointment-booking-system' ), 'default' => 'Our representative will contact you shortly if further information is required.' ),
	),
);

// Fetch current active values for language
$current_strings = Translation_Service::get_i18n_strings( $active_lang );

// Fetch Audit Logs
$audit_logs = get_option( 'ab_i18n_audit_log', array() );
?>

<div class="wrap ab-wrap">
	<h1><?php esc_html_e( 'String Translations & Audit Log', 'appointment-booking-system' ); ?></h1>
	<?php include __DIR__ . '/partials/notice.php'; ?>

	<h2 class="nav-tab-wrapper">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=ab-string-translations&tab=strings&lang=' . $active_lang ) ); ?>" class="nav-tab <?php echo 'strings' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-translation" style="vertical-align:text-top;margin-right:4px;"></span>
			<?php esc_html_e( 'Static String Translations', 'appointment-booking-system' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=ab-string-translations&tab=logs&lang=' . $active_lang ) ); ?>" class="nav-tab <?php echo 'logs' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-list-view" style="vertical-align:text-top;margin-right:4px;"></span>
			<?php esc_html_e( 'Audit Log', 'appointment-booking-system' ); ?>
			<?php if ( ! empty( $audit_logs ) ) : ?>
				<span class="update-plugins count-<?php echo esc_attr( count( $audit_logs ) ); ?>"><span class="plugin-count"><?php echo esc_html( count( $audit_logs ) ); ?></span></span>
			<?php endif; ?>
		</a>
	</h2>

	<?php if ( 'strings' === $active_tab ) : ?>
		<div class="ab-string-editor-box" style="background:#fff; border:1px solid #dcdcde; padding:20px; border-radius:4px; margin-top:15px;">
			<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid #eee; padding-bottom:12px;">
				<h2 style="margin:0; font-size:18px; font-weight:600;">
					<?php esc_html_e( 'Manage Frontend Static Strings', 'appointment-booking-system' ); ?>
				</h2>

				<div style="display:flex; align-items:center; gap:8px;">
					<label for="ab_lang_switcher" style="font-weight:600; font-size:13px;"><?php esc_html_e( 'Language:', 'appointment-booking-system' ); ?></label>
					<select id="ab_lang_switcher" onchange="location = this.value;" style="min-width:140px; height:32px;">
						<?php foreach ( $languages as $code => $l ) : ?>
							<?php $url = admin_url( 'admin.php?page=ab-string-translations&tab=strings&lang=' . $code ); ?>
							<option value="<?php echo esc_url( $url ); ?>" <?php selected( $active_lang, $code ); ?>>
								<?php echo esc_html( ( $l['display_name'] ?? strtoupper( $code ) ) . ' (' . strtoupper( $code ) . ')' ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'ab_admin_nonce' ); ?>
				<input type="hidden" name="action" value="ab_save_string_translations" />
				<input type="hidden" name="lang" value="<?php echo esc_attr( $active_lang ); ?>" />

				<?php foreach ( $string_fields as $group_title => $group_fields ) : ?>
					<h3 style="background:#f6f7f7; padding:8px 12px; border-left:4px solid #0B6E4F; margin-top:24px; font-size:15px;">
						<?php echo esc_html( $group_title ); ?>
					</h3>
					<table class="form-table" style="margin-top:0;">
						<tbody>
							<?php foreach ( $group_fields as $key => $meta ) : ?>
								<?php $val = isset( $current_strings[ $key ] ) ? $current_strings[ $key ] : $meta['default']; ?>
								<tr>
									<th scope="row" style="width:320px; font-weight:500; font-size:13px;">
										<label for="string_<?php echo esc_attr( $key ); ?>">
											<?php echo esc_html( $meta['label'] ); ?>
										</label>
										<div style="font-size:11px; color:#787c82; font-weight:normal; margin-top:2px;">
											<code><?php echo esc_html( $key ); ?></code>
										</div>
									</th>
									<td>
										<?php if ( strpos( $key, 'success_p' ) !== false || strpos( $key, 'select' ) !== false ) : ?>
											<textarea id="string_<?php echo esc_attr( $key ); ?>" name="strings[<?php echo esc_attr( $key ); ?>]" class="large-text" rows="2"><?php echo esc_textarea( $val ); ?></textarea>
										<?php else : ?>
											<input type="text" id="string_<?php echo esc_attr( $key ); ?>" name="strings[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $val ); ?>" class="regular-text" style="width:100%; max-width:600px;" />
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endforeach; ?>

				<p class="submit" style="margin-top:24px; border-top:1px solid #eee; padding-top:16px;">
					<button type="submit" class="button button-primary button-hero">
						<?php sprintf( esc_html_e( 'Save Translations for [%s]', 'appointment-booking-system' ), strtoupper( $active_lang ) ); ?>
						<?php echo esc_html__( 'Save String Translations', 'appointment-booking-system' ); ?>
					</button>
				</p>
			</form>
		</div>

	<?php else : ?>

		<div class="ab-audit-log-box" style="background:#fff; border:1px solid #dcdcde; padding:20px; border-radius:4px; margin-top:15px;">
			<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
				<h2 style="margin:0; font-size:18px; font-weight:600;">
					<?php esc_html_e( 'Translation Update Audit Logs', 'appointment-booking-system' ); ?>
				</h2>

				<?php if ( ! empty( $audit_logs ) ) : ?>
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ab_clear_translation_logs' ), 'ab_admin_nonce' ) ); ?>" class="button button-secondary ab-delete-link" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to clear all audit logs?', 'appointment-booking-system' ); ?>');">
						<span class="dashicons dashicons-trash" style="vertical-align:text-top; margin-right:3px;"></span>
						<?php esc_html_e( 'Clear Audit Logs', 'appointment-booking-system' ); ?>
					</a>
				<?php endif; ?>
			</div>

			<?php if ( empty( $audit_logs ) ) : ?>
				<div class="notice notice-info inline">
					<p><?php esc_html_e( 'No audit logs recorded yet. Changes made in the String Translations tab will be logged here.', 'appointment-booking-system' ); ?></p>
				</div>
			<?php else : ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th style="width:160px;"><?php esc_html_e( 'Date & Time', 'appointment-booking-system' ); ?></th>
							<th style="width:220px;"><?php esc_html_e( 'Administrator', 'appointment-booking-system' ); ?></th>
							<th style="width:100px;"><?php esc_html_e( 'Language', 'appointment-booking-system' ); ?></th>
							<th><?php esc_html_e( 'Changes Summary', 'appointment-booking-system' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $audit_logs as $log ) : ?>
							<tr>
								<td>
									<strong><?php echo esc_html( date_i18n( 'M j, Y - H:i:s', strtotime( $log['timestamp'] ) ) ); ?></strong>
								</td>
								<td>
									<div style="font-weight:600;"><?php echo esc_html( $log['user_name'] ); ?></div>
									<div style="font-size:11px; color:#666;"><?php echo esc_html( $log['user_email'] ); ?></div>
								</td>
								<td>
									<span class="ab-trans-pill ab-trans-pill--done">
										<?php echo esc_html( strtoupper( $log['lang'] ) ); ?>
									</span>
								</td>
								<td>
									<div style="font-weight:600; margin-bottom:4px; color:#0B6E4F;">
										<?php sprintf( esc_html_e( 'Updated %d string(s)', 'appointment-booking-system' ), (int) $log['changes_count'] ); ?>
										<?php echo esc_html( $log['changes_count'] . ' string(s) modified:' ); ?>
									</div>
									<div style="background:#f9f9f9; border:1px solid #e5e5e5; padding:8px 12px; border-radius:4px; font-size:12px; max-height:150px; overflow-y:auto;">
										<?php if ( ! empty( $log['changes_detail'] ) ) : ?>
											<ul style="margin:0; padding-left:15px;">
												<?php foreach ( $log['changes_detail'] as $k => $diff ) : ?>
													<li style="margin-bottom:3px;">
														<code><?php echo esc_html( $k ); ?></code>:
														<span style="color:#b32d2e; text-decoration:line-through; font-size:11px;"><?php echo esc_html( $diff['old'] ?: '(empty)' ); ?></span>
														&rarr;
														<span style="color:#146c2e; font-weight:600;"><?php echo esc_html( $diff['new'] ); ?></span>
													</li>
												<?php endforeach; ?>
											</ul>
										<?php endif; ?>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>

	<?php endif; ?>
</div>
