<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AB\Includes\Models\Service_Model;
use AB\Includes\Models\Category_Model;

$service_model  = new Service_Model();
$category_model = new Category_Model();
$categories     = $category_model->all();

$search     = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$filter_cat = isset( $_GET['category_id'] ) ? absint( $_GET['category_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$orderby    = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'name'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$services   = $service_model->all( array( 'search' => $search, 'category_id' => $filter_cat, 'orderby' => $orderby ) );

$categories_by_id = array();
foreach ( $categories as $cat ) {
	$categories_by_id[ $cat['id'] ] = $cat['name'];
}

$edit    = null;
$edit_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
if ( $edit_id ) {
	$edit = $service_model->find( $edit_id );
}
?>
<div class="wrap ab-wrap">
	<h1><?php esc_html_e( 'Services', 'appointment-booking-system' ); ?></h1>
	<?php include __DIR__ . '/partials/notice.php'; ?>

	<div class="ab-columns">
		<div class="ab-col ab-col-form">
			<h2><?php echo $edit ? esc_html__( 'Edit Service', 'appointment-booking-system' ) : esc_html__( 'Add New Service', 'appointment-booking-system' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'ab_admin_nonce' ); ?>
				<input type="hidden" name="action" value="ab_save_service" />
				<input type="hidden" name="id" value="<?php echo esc_attr( $edit['id'] ?? '' ); ?>" />
				<table class="form-table">
					<tr><th><label for="name"><?php esc_html_e( 'Service Name', 'appointment-booking-system' ); ?> *</label></th>
						<td><input type="text" id="name" name="name" class="regular-text" required value="<?php echo esc_attr( $edit['name'] ?? '' ); ?>" /></td></tr>
					<tr><th><label for="slug"><?php esc_html_e( 'Slug', 'appointment-booking-system' ); ?></label></th>
						<td><input type="text" id="slug" name="slug" class="regular-text" value="<?php echo esc_attr( $edit['slug'] ?? '' ); ?>" /></td></tr>
					<tr><th><label for="category_id"><?php esc_html_e( 'Category', 'appointment-booking-system' ); ?> *</label></th>
						<td>
							<select id="category_id" name="category_id" required>
								<option value=""><?php esc_html_e( '— Select —', 'appointment-booking-system' ); ?></option>
								<?php foreach ( $categories as $cat ) : ?>
									<option value="<?php echo esc_attr( $cat['id'] ); ?>" <?php selected( (int) ( $edit['category_id'] ?? 0 ), $cat['id'] ); ?>><?php echo esc_html( $cat['name'] ); ?></option>
								<?php endforeach; ?>
							</select>
						</td></tr>
					<tr><th><?php esc_html_e( 'Duration', 'appointment-booking-system' ); ?> *</th>
						<td>
							<input type="number" name="duration_hour" min="0" max="12" style="width:80px;" value="<?php echo esc_attr( $edit['duration_hour'] ?? 0 ); ?>" /> <?php esc_html_e( 'Hours', 'appointment-booking-system' ); ?>
							<input type="number" name="duration_minute" min="0" max="59" step="5" style="width:80px;" value="<?php echo esc_attr( $edit['duration_minute'] ?? 30 ); ?>" /> <?php esc_html_e( 'Minutes', 'appointment-booking-system' ); ?>
						</td></tr>
					<tr><th><label for="description"><?php esc_html_e( 'Description', 'appointment-booking-system' ); ?></label></th>
						<td><textarea id="description" name="description" class="large-text" rows="3"><?php echo esc_textarea( $edit['description'] ?? '' ); ?></textarea></td></tr>
					<tr><th><?php esc_html_e( 'Status', 'appointment-booking-system' ); ?></th>
						<td><label><input type="checkbox" name="status" <?php checked( ! isset( $edit ) || ! empty( $edit['status'] ) ); ?> /> <?php esc_html_e( 'Active', 'appointment-booking-system' ); ?></label></td></tr>
				</table>
				<p class="submit">
					<button type="submit" class="button button-primary"><?php echo $edit ? esc_html__( 'Update Service', 'appointment-booking-system' ) : esc_html__( 'Add Service', 'appointment-booking-system' ); ?></button>
					<?php if ( $edit ) : ?><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ab-services' ) ); ?>"><?php esc_html_e( 'Cancel', 'appointment-booking-system' ); ?></a><?php endif; ?>
				</p>
			</form>
		</div>

		<div class="ab-col">
			<h2><?php esc_html_e( 'All Services', 'appointment-booking-system' ); ?></h2>
			<form method="get" class="ab-filter-bar">
				<input type="hidden" name="page" value="ab-services" />
				<input type="search" name="s" placeholder="<?php esc_attr_e( 'Search services…', 'appointment-booking-system' ); ?>" value="<?php echo esc_attr( $search ); ?>" />
				<select name="category_id">
					<option value=""><?php esc_html_e( 'All Categories', 'appointment-booking-system' ); ?></option>
					<?php foreach ( $categories as $cat ) : ?>
						<option value="<?php echo esc_attr( $cat['id'] ); ?>" <?php selected( $filter_cat, $cat['id'] ); ?>><?php echo esc_html( $cat['name'] ); ?></option>
					<?php endforeach; ?>
				</select>
				<select name="orderby">
					<option value="name" <?php selected( $orderby, 'name' ); ?>><?php esc_html_e( 'Sort by Name', 'appointment-booking-system' ); ?></option>
					<option value="duration" <?php selected( $orderby, 'duration' ); ?>><?php esc_html_e( 'Sort by Duration', 'appointment-booking-system' ); ?></option>
				</select>
				<button class="button"><?php esc_html_e( 'Filter', 'appointment-booking-system' ); ?></button>
			</form>
			<table class="widefat striped">
				<thead><tr>
					<th><?php esc_html_e( 'Name', 'appointment-booking-system' ); ?></th>
					<th><?php esc_html_e( 'Category', 'appointment-booking-system' ); ?></th>
					<th><?php esc_html_e( 'Duration', 'appointment-booking-system' ); ?></th>
					<th><?php esc_html_e( 'Status', 'appointment-booking-system' ); ?></th>
					<th><?php esc_html_e( 'Translations', 'appointment-booking-system' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'appointment-booking-system' ); ?></th>
				</tr></thead>
				<tbody>
				<?php if ( $services ) : ?>
					<?php foreach ( $services as $svc ) : ?>
						<tr>
							<td><?php echo esc_html( $svc['name'] ); ?></td>
							<td><?php echo esc_html( $categories_by_id[ $svc['category_id'] ] ?? '—' ); ?></td>
							<td><?php echo esc_html( ab_format_duration( $svc['duration_hour'], $svc['duration_minute'] ) ); ?></td>
							<td><?php echo $svc['status'] ? '<span class="ab-badge ab-badge-confirmed">' . esc_html__( 'Active', 'appointment-booking-system' ) . '</span>' : '<span class="ab-badge ab-badge-cancelled">' . esc_html__( 'Inactive', 'appointment-booking-system' ) . '</span>'; ?></td>
							<td>
								<?php
								echo \AB\Includes\Language\Translation_Service::render_translation_column(
									\AB\Includes\Language\Translation_Service::TYPE_SERVICE,
									$svc['id']
								);
								?>
							</td>
							<td>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=ab-services&edit=' . $svc['id'] ) ); ?>"><?php esc_html_e( 'Edit', 'appointment-booking-system' ); ?></a> |
								<a class="ab-delete-link" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ab_delete_service&id=' . $svc['id'] ), 'ab_admin_nonce' ) ); ?>"><?php esc_html_e( 'Delete', 'appointment-booking-system' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr><td colspan="6"><?php esc_html_e( 'No services found.', 'appointment-booking-system' ); ?></td></tr>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
