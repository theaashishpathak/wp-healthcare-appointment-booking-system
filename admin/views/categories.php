<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AB\Includes\Models\Category_Model;

$model      = new Category_Model();
$categories = $model->all();

$edit    = null;
$edit_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
if ( $edit_id ) {
	$edit = $model->find( $edit_id );
}
?>
<div class="wrap ab-wrap">
	<h1><?php esc_html_e( 'Treatment Categories', 'appointment-booking-system' ); ?></h1>
	<?php include __DIR__ . '/partials/notice.php'; ?>

	<div class="ab-columns">
		<div class="ab-col ab-col-form">
			<h2><?php echo $edit ? esc_html__( 'Edit Category', 'appointment-booking-system' ) : esc_html__( 'Add New Category', 'appointment-booking-system' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'ab_admin_nonce' ); ?>
				<input type="hidden" name="action" value="ab_save_category" />
				<input type="hidden" name="id" value="<?php echo esc_attr( $edit['id'] ?? '' ); ?>" />

				<table class="form-table">
					<tr>
						<th><label for="name"><?php esc_html_e( 'Category Name', 'appointment-booking-system' ); ?> *</label></th>
						<td><input type="text" id="name" name="name" class="regular-text" required value="<?php echo esc_attr( $edit['name'] ?? '' ); ?>" /></td>
					</tr>
					<tr>
						<th><label for="slug"><?php esc_html_e( 'Slug', 'appointment-booking-system' ); ?></label></th>
						<td><input type="text" id="slug" name="slug" class="regular-text" placeholder="<?php esc_attr_e( 'Auto-generated if left blank', 'appointment-booking-system' ); ?>" value="<?php echo esc_attr( $edit['slug'] ?? '' ); ?>" /></td>
					</tr>
					<tr>
						<th><label for="description"><?php esc_html_e( 'Description', 'appointment-booking-system' ); ?></label></th>
						<td><textarea id="description" name="description" class="large-text" rows="3"><?php echo esc_textarea( $edit['description'] ?? '' ); ?></textarea></td>
					</tr>
					<tr>
						<th><label for="icon"><?php esc_html_e( 'Icon (optional)', 'appointment-booking-system' ); ?></label></th>
						<td>
							<input type="text" id="icon" name="icon" class="regular-text ab-media-field" value="<?php echo esc_attr( $edit['icon'] ?? '' ); ?>" />
							<button type="button" class="button ab-media-upload" data-target="#icon"><?php esc_html_e( 'Choose Image', 'appointment-booking-system' ); ?></button>
						</td>
					</tr>
					<tr>
						<th><label for="display_order"><?php esc_html_e( 'Display Order', 'appointment-booking-system' ); ?></label></th>
						<td><input type="number" id="display_order" name="display_order" value="<?php echo esc_attr( $edit['display_order'] ?? 0 ); ?>" /></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Status', 'appointment-booking-system' ); ?></th>
						<td><label><input type="checkbox" name="status" <?php checked( ! isset( $edit ) || ! empty( $edit['status'] ) ); ?> /> <?php esc_html_e( 'Active', 'appointment-booking-system' ); ?></label></td>
					</tr>
				</table>
				<p class="submit">
					<button type="submit" class="button button-primary"><?php echo $edit ? esc_html__( 'Update Category', 'appointment-booking-system' ) : esc_html__( 'Add Category', 'appointment-booking-system' ); ?></button>
					<?php if ( $edit ) : ?>
						<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ab-categories' ) ); ?>"><?php esc_html_e( 'Cancel', 'appointment-booking-system' ); ?></a>
					<?php endif; ?>
				</p>
			</form>
		</div>

		<div class="ab-col">
			<h2><?php esc_html_e( 'All Categories', 'appointment-booking-system' ); ?></h2>
			<table class="widefat striped">
				<thead><tr>
					<th><?php esc_html_e( 'Name', 'appointment-booking-system' ); ?></th>
					<th><?php esc_html_e( 'Slug', 'appointment-booking-system' ); ?></th>
					<th><?php esc_html_e( 'Order', 'appointment-booking-system' ); ?></th>
					<th><?php esc_html_e( 'Status', 'appointment-booking-system' ); ?></th>
					<th><?php esc_html_e( 'Translations', 'appointment-booking-system' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'appointment-booking-system' ); ?></th>
				</tr></thead>
				<tbody>
				<?php if ( $categories ) : ?>
					<?php foreach ( $categories as $cat ) : ?>
						<tr>
							<td><?php echo esc_html( $cat['name'] ); ?></td>
							<td><?php echo esc_html( $cat['slug'] ); ?></td>
							<td><?php echo esc_html( $cat['display_order'] ); ?></td>
							<td><?php echo $cat['status'] ? '<span class="ab-badge ab-badge-confirmed">' . esc_html__( 'Active', 'appointment-booking-system' ) . '</span>' : '<span class="ab-badge ab-badge-cancelled">' . esc_html__( 'Inactive', 'appointment-booking-system' ) . '</span>'; ?></td>
							<td>
								<?php
								echo \AB\Includes\Language\Translation_Service::render_translation_column(
									\AB\Includes\Language\Translation_Service::TYPE_CATEGORY,
									$cat['id']
								);
								?>
							</td>
							<td>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=ab-categories&edit=' . $cat['id'] ) ); ?>"><?php esc_html_e( 'Edit', 'appointment-booking-system' ); ?></a> |
								<a class="ab-delete-link" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ab_delete_category&id=' . $cat['id'] ), 'ab_admin_nonce' ) ); ?>"><?php esc_html_e( 'Delete', 'appointment-booking-system' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr><td colspan="6"><?php esc_html_e( 'No categories yet.', 'appointment-booking-system' ); ?></td></tr>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
