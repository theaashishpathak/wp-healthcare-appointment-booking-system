<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AB\Includes\Logger;
use AB\Includes\Language\Translation_Service;

$_cur_lang  = Translation_Service::get_current_language();
$_lang_strs = Translation_Service::get_i18n_strings( $_cur_lang );
$t          = function( $key, $fallback ) use ( $_lang_strs ) {
	return ! empty( $_lang_strs[ $key ] ) ? $_lang_strs[ $key ] : $fallback;
};

$action_type = isset( $_GET['action_type'] ) ? sanitize_key( $_GET['action_type'] ) : '';
$search      = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$paged       = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
$per_page    = 25;
$offset      = ( $paged - 1 ) * $per_page;

$args = array(
	'action_type' => $action_type,
	'search'      => $search,
	'limit'       => $per_page,
	'offset'      => $offset,
);

$logs        = Logger::get_logs( $args );
$total_logs  = Logger::count_logs( $args );
$total_pages = ceil( $total_logs / $per_page );

$types = array(
	''                   => $t( 'log_type_all', __( 'All Activity Types', 'appointment-booking-system' ) ),
	'doctor'             => $t( 'doc_page_title', __( 'Doctors', 'appointment-booking-system' ) ),
	'category'           => $t( 'cat_page_title', __( 'Treatment Categories', 'appointment-booking-system' ) ),
	'service'            => $t( 'srv_page_title', __( 'Services', 'appointment-booking-system' ) ),
	'availability'       => $t( 'avail_page_title', __( 'Availability', 'appointment-booking-system' ) ),
	'appointment'        => $t( 'app_page_title', __( 'Appointments / Bookings', 'appointment-booking-system' ) ),
	'translation'        => __( 'Item Translations', 'appointment-booking-system' ),
	'string_translation' => __( 'Static String Translations', 'appointment-booking-system' ),
	'settings'           => $t( 'set_page_title', __( 'Plugin Settings', 'appointment-booking-system' ) ),
	'plugin_lifecycle'   => __( 'Plugin Lifecycle (Activation/Deactivation)', 'appointment-booking-system' ),
	'security'           => __( 'Security & User Logins', 'appointment-booking-system' ),
);
?>

<div class="wrap ab-wrap">
	<style>
		.ab-log-card {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 8px;
			box-shadow: 0 1px 3px rgba(0,0,0,0.05);
			margin-top: 20px;
			overflow: hidden;
		}
		.ab-log-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 16px 20px;
			background: #f8fafc;
			border-bottom: 1px solid #e2e8f0;
		}
		.ab-log-header h2 {
			margin: 0;
			font-size: 16px;
			font-weight: 700;
			color: #0f172a;
			display: flex;
			align-items: center;
			gap: 8px;
		}
		.ab-log-filter-bar {
			display: flex;
			gap: 12px;
			align-items: center;
			flex-wrap: wrap;
		}
		.ab-log-table {
			width: 100%;
			border-collapse: collapse;
			font-size: 13px;
		}
		.ab-log-table th {
			background: #f1f5f9;
			color: #475569;
			font-weight: 600;
			text-transform: uppercase;
			font-size: 11px;
			letter-spacing: 0.5px;
			padding: 12px 16px;
			border-bottom: 1px solid #e2e8f0;
			text-align: left;
		}
		.ab-log-table td {
			padding: 14px 16px;
			border-bottom: 1px solid #f1f5f9;
			color: #334155;
			vertical-align: middle;
		}
		.ab-log-table tr:hover td {
			background: #f8fafc;
		}
		.ab-user-pill {
			display: flex;
			align-items: center;
			gap: 10px;
		}
		.ab-avatar-badge {
			width: 34px;
			height: 34px;
			border-radius: 50%;
			background: #3b82f6;
			color: #ffffff;
			display: flex;
			align-items: center;
			justify-content: center;
			font-weight: 700;
			font-size: 13px;
			flex-shrink: 0;
		}
		.ab-role-tag {
			display: inline-block;
			font-size: 10px;
			background: #e2e8f0;
			color: #475569;
			padding: 2px 6px;
			border-radius: 4px;
			font-weight: 600;
			margin-top: 2px;
		}
		.ab-badge-lifecycle { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
		.ab-badge-security { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
		.ab-badge-config { background: #fefce8; color: #a16207; border: 1px solid #fef08a; }
		.ab-badge-action { background: #faf5ff; color: #7e22ce; border: 1px solid #e9d5ff; }
		.ab-drawer-box {
			background: #ffffff;
			border: 1px solid #cbd5e1;
			border-radius: 6px;
			padding: 16px;
			box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
		}
		.ab-diff-table {
			width: 100%;
			border-collapse: collapse;
			font-size: 12px;
			margin-top: 8px;
		}
		.ab-diff-table th {
			background: #f8fafc;
			padding: 8px 12px;
			border: 1px solid #e2e8f0;
		}
		.ab-diff-table td {
			padding: 8px 12px;
			border: 1px solid #e2e8f0;
		}
		.ab-diff-old { background: #fef2f2; color: #991b1b; text-decoration: line-through; }
		.ab-diff-new { background: #f0fdf4; color: #166534; font-weight: 600; }
	</style>

	<div class="ab-log-card">
		<div class="ab-log-header">
			<h2>
				<span class="dashicons dashicons-shield" style="font-size:20px; color:#2563eb;"></span>
				<?php echo esc_html( $t( 'log_page_title', __( 'System Activity & Audit Logs', 'appointment-booking-system' ) ) ); ?>
			</h2>

			<?php if ( ! empty( $logs ) ) : ?>
				<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ab_clear_activity_logs' ), 'ab_admin_nonce' ) ); ?>" class="button button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to clear ALL activity logs? This action cannot be undone.', 'appointment-booking-system' ); ?>');">
					<span class="dashicons dashicons-trash" style="vertical-align:text-top; margin-right:3px;"></span>
					<?php echo esc_html( $t( 'log_clear_btn', __( 'Clear All Logs', 'appointment-booking-system' ) ) ); ?>
				</a>
			<?php endif; ?>
		</div>

		<?php include __DIR__ . '/partials/notice.php'; ?>

		<div style="padding: 16px 20px; border-bottom: 1px solid #e2e8f0; background: #fafafa;">
			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="ab-log-filter-bar">
				<input type="hidden" name="page" value="ab-activity-logs" />

				<select name="action_type" onchange="this.form.submit();" style="height:34px; border-radius:6px; border-color:#cbd5e1;">
					<?php foreach ( $types as $val => $label ) : ?>
						<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $action_type, $val ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>

				<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php echo esc_attr( $t( 'log_search_ph', __( 'Search logs by title, user, email, IP…', 'appointment-booking-system' ) ) ); ?>" style="height:34px; min-width:280px; border-radius:6px; border-color:#cbd5e1;" />

				<button type="submit" class="button button-primary" style="height:34px; line-height:32px; border-radius:6px;"><?php echo esc_html( $t( 'btn_filter', __( 'Filter', 'appointment-booking-system' ) ) ); ?></button>
				<?php if ( $action_type || $search ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=ab-activity-logs' ) ); ?>" class="button" style="height:34px; line-height:32px; border-radius:6px;"><?php echo esc_html( $t( 'btn_reset', __( 'Reset Filters', 'appointment-booking-system' ) ) ); ?></a>
				<?php endif; ?>
			</form>
		</div>

		<?php if ( empty( $logs ) ) : ?>
			<div style="padding: 40px; text-align: center; color: #64748b;">
				<span class="dashicons dashicons-info-outline" style="font-size:40px; width:40px; height:40px; color:#94a3b8;"></span>
				<p style="font-size:15px; margin-top:10px; font-weight:500;"><?php echo esc_html( $t( 'log_no_records', __( 'No activity logs found matching the selected criteria.', 'appointment-booking-system' ) ) ); ?></p>
			</div>
		<?php else : ?>
			<table class="ab-log-table">
				<thead>
					<tr>
						<th style="width:160px;"><?php echo esc_html( $t( 'dash_date', __( 'Date & Time', 'appointment-booking-system' ) ) ); ?></th>
						<th style="width:220px;"><?php echo esc_html( $t( 'log_col_user', __( 'User / Initiator', 'appointment-booking-system' ) ) ); ?></th>
						<th style="width:140px;"><?php echo esc_html( $t( 'hol_field_type', __( 'Type', 'appointment-booking-system' ) ) ); ?></th>
						<th><?php echo esc_html( $t( 'log_col_event', __( 'Activity Event', 'appointment-booking-system' ) ) ); ?></th>
						<th style="width:130px; text-align:right;"><?php echo esc_html( $t( 'col_actions', __( 'Actions', 'appointment-booking-system' ) ) ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $logs as $log ) : ?>
						<?php
						$payload   = ! empty( $log['details'] ) ? json_decode( $log['details'], true ) : null;
						$user_name = $log['user_name'] ?: 'System';
						$initial   = strtoupper( substr( $user_name, 0, 1 ) );

						$badge_class = 'ab-badge-action';
						if ( 'plugin_lifecycle' === $log['action_type'] ) {
							$badge_class = 'ab-badge-lifecycle';
						} elseif ( 'security' === $log['action_type'] ) {
							$badge_class = 'ab-badge-security';
						} elseif ( in_array( $log['action_type'], array( 'settings', 'string_translation', 'translation' ), true ) ) {
							$badge_class = 'ab-badge-config';
						}
						?>
						<tr>
							<td>
								<div style="font-weight:600; color:#1e293b;"><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $log['created_at'] ) ) ); ?></div>
								<div style="font-size:11px; color:#64748b; margin-top:2px;"><?php echo esc_html( date_i18n( 'H:i:s', strtotime( $log['created_at'] ) ) ); ?></div>
							</td>
							<td>
								<div class="ab-user-pill">
									<div class="ab-avatar-badge"><?php echo esc_html( $initial ); ?></div>
									<div>
										<div style="font-weight:600; color:#0f172a;"><?php echo esc_html( $user_name ); ?></div>
										<div style="font-size:11px; color:#64748b;"><?php echo esc_html( $log['user_email'] ); ?></div>
										<?php if ( ! empty( $log['user_role'] ) ) : ?>
											<span class="ab-role-tag"><?php echo esc_html( $log['user_role'] ); ?></span>
										<?php endif; ?>
									</div>
								</div>
							</td>
							<td>
								<span class="ab-badge <?php echo esc_attr( $badge_class ); ?>" style="padding:4px 8px; font-size:10px; font-weight:700; border-radius:4px; text-transform:uppercase;">
									<?php echo esc_html( str_replace( '_', ' ', $log['action_type'] ) ); ?>
								</span>
							</td>
							<td>
								<div style="font-weight:600; font-size:13.5px; color:#0f172a;"><?php echo esc_html( $log['object_title'] ); ?></div>
								<div style="font-size:11px; color:#64748b; margin-top:3px; display:flex; gap:12px; align-items:center;">
									<span>Action: <code style="background:#e2e8f0; color:#334155; padding:1px 5px; border-radius:3px; font-size:11px;"><?php echo esc_html( $log['action_name'] ); ?></code></span>
									<?php if ( ! empty( $log['ip_address'] ) ) : ?>
										<span>IP: <code style="background:#e2e8f0; color:#334155; padding:1px 5px; border-radius:3px; font-size:11px;"><?php echo esc_html( $log['ip_address'] ); ?></code></span>
									<?php endif; ?>
								</div>
							</td>
							<td style="text-align:right;">
								<button type="button" class="button button-secondary button-small" onclick="abToggleLogDrawer(<?php echo esc_attr( $log['id'] ); ?>)" style="border-radius:6px;">
									<span class="dashicons dashicons-visibility" style="vertical-align:text-top; font-size:13px; width:13px; height:13px; margin-right:2px;"></span>
									<?php echo esc_html( $t( 'log_view_details', __( 'View Details', 'appointment-booking-system' ) ) ); ?>
								</button>
							</td>
						</tr>

						<!-- Expanded Detail Drawer -->
						<tr id="ab-log-drawer-<?php echo esc_attr( $log['id'] ); ?>" style="display:none; background:#f8fafc;">
							<td colspan="5" style="padding:16px 20px; border-top:1px dashed #cbd5e1; border-bottom:2px solid #cbd5e1;">
								<div class="ab-drawer-box">
									<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; border-bottom:1px solid #f1f5f9; padding-bottom:8px;">
										<h4 style="margin:0; font-size:14px; font-weight:700; color:#0f172a;">
											<span class="dashicons dashicons-database" style="font-size:16px; margin-right:4px; color:#3b82f6;"></span>
											Activity Audit & Payload Breakdown (#<?php echo esc_html( $log['id'] ); ?>)
										</h4>
										<button type="button" class="button button-small" onclick="abToggleLogDrawer(<?php echo esc_attr( $log['id'] ); ?>)">&times; Close</button>
									</div>

									<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:10px; font-size:12px; background:#f1f5f9; padding:10px 14px; border-radius:6px; margin-bottom:14px;">
										<div><strong>Initiator:</strong> <?php echo esc_html( $log['user_name'] ); ?> (<?php echo esc_html( $log['user_email'] ); ?>)</div>
										<div><strong>Role:</strong> <?php echo esc_html( $log['user_role'] ?: 'System / Guest' ); ?></div>
										<div><strong>Exact Timestamp:</strong> <?php echo esc_html( $log['created_at'] ); ?></div>
										<div><strong>Remote IP:</strong> <?php echo esc_html( $log['ip_address'] ?: 'N/A' ); ?></div>
										<?php if ( ! empty( $log['user_agent'] ) ) : ?>
											<div style="grid-column: 1 / -1;"><strong>User Agent:</strong> <span style="font-family:monospace; color:#475569; font-size:11px;"><?php echo esc_html( $log['user_agent'] ); ?></span></div>
										<?php endif; ?>
									</div>

									<?php if ( ! empty( $payload['changes_detail'] ) && is_array( $payload['changes_detail'] ) ) : ?>
										<h5 style="margin:10px 0 6px; font-size:13px; font-weight:700; color:#1e293b;">Modified Field Values (Diff Table):</h5>
										<table class="ab-diff-table">
											<thead>
												<tr>
													<th style="width:25%;">Field / String Key</th>
													<th style="width:37.5%;">Original Value</th>
													<th style="width:37.5%;">Updated Value</th>
												</tr>
											</thead>
											<tbody>
												<?php foreach ( $payload['changes_detail'] as $field_k => $diff_val ) : ?>
													<?php
													$old_val = is_array( $diff_val ) ? ( $diff_val['old'] ?? '' ) : '';
													$new_val = is_array( $diff_val ) ? ( $diff_val['new'] ?? '' ) : $diff_val;
													?>
													<tr>
														<td><code style="font-weight:600;"><?php echo esc_html( $field_k ); ?></code></td>
														<td class="ab-diff-old"><?php echo '' === $old_val || null === $old_val ? '<em>(empty)</em>' : esc_html( is_string( $old_val ) ? $old_val : json_encode( $old_val ) ); ?></td>
														<td class="ab-diff-new"><?php echo esc_html( is_string( $new_val ) ? $new_val : json_encode( $new_val ) ); ?></td>
													</tr>
												<?php endforeach; ?>
											</tbody>
										</table>
									<?php elseif ( $payload ) : ?>
										<h5 style="margin:10px 0 6px; font-size:13px; font-weight:700; color:#1e293b;">Raw JSON Event Payload:</h5>
										<pre style="background:#0f172a; color:#f8fafc; padding:12px; border-radius:6px; font-size:11.5px; overflow-x:auto; max-height:260px; line-height:1.5; margin:0;"><?php echo esc_html( json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ); ?></pre>
									<?php else : ?>
										<p style="color:#64748b; font-size:12px; margin:4px 0;">No extra JSON payload data attached to this activity entry.</p>
									<?php endif; ?>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( $total_pages > 1 ) : ?>
				<div style="padding: 12px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
					<div style="font-size:12px; color:#64748b;">
						<?php printf( esc_html__( 'Total %d activity logs recorded', 'appointment-booking-system' ), (int) $total_logs ); ?>
					</div>
					<div class="tablenav-pages">
						<?php
						echo paginate_links(
							array(
								'base'      => add_query_arg( 'paged', '%#%' ),
								'format'    => '',
								'prev_text' => '&laquo;',
								'next_text' => '&raquo;',
								'total'     => $total_pages,
								'current'   => $paged,
							)
						);
						?>
					</div>
				</div>
			<?php endif; ?>

			<script>
			function abToggleLogDrawer(id) {
				var row = document.getElementById('ab-log-drawer-' + id);
				if (row) {
					if (row.style.display === 'none' || row.style.display === '') {
						row.style.display = 'table-row';
					} else {
						row.style.display = 'none';
					}
				}
			}
			</script>
		<?php endif; ?>
	</div>
</div>
