<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AB\Includes\Models\Category_Model;
use AB\Includes\Language\Translation_Service;

$model      = new Category_Model();
$categories = $model->all();

// Load language-specific strings for active WP / WPML language
$_cur_lang  = Translation_Service::get_current_language();
$_lang_strs = Translation_Service::get_i18n_strings( $_cur_lang );
$t          = function( $key, $fallback ) use ( $_lang_strs ) {
	return ! empty( $_lang_strs[ $key ] ) ? $_lang_strs[ $key ] : $fallback;
};

$edit    = null;
$edit_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
if ( $edit_id ) {
	$edit = $model->find( $edit_id );
}
?>
<div class="wrap ab-wrap">
	<h1><?php echo esc_html( $t( 'cat_page_title', __( 'Treatment Categories', 'appointment-booking-system' ) ) ); ?></h1>
	<?php include __DIR__ . '/partials/notice.php'; ?>

	<div class="ab-columns">
		<div class="ab-col ab-col-form">
			<h2><?php echo $edit ? esc_html( $t( 'cat_edit_heading', __( 'Edit Category', 'appointment-booking-system' ) ) ) : esc_html( $t( 'cat_add_heading', __( 'Add New Category', 'appointment-booking-system' ) ) ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'ab_admin_nonce' ); ?>
				<input type="hidden" name="action" value="ab_save_category" />
				<input type="hidden" name="id" value="<?php echo esc_attr( $edit['id'] ?? '' ); ?>" />

				<table class="form-table">
					<tr>
						<th><label for="name"><?php echo esc_html( $t( 'cat_field_name', __( 'Category Name', 'appointment-booking-system' ) ) ); ?> *</label></th>
						<td><input type="text" id="name" name="name" class="regular-text" required value="<?php echo esc_attr( $edit['name'] ?? '' ); ?>" /></td>
					</tr>
					<tr>
						<th><label for="slug"><?php echo esc_html( $t( 'srv_field_slug', __( 'Slug', 'appointment-booking-system' ) ) ); ?></label></th>
						<td><input type="text" id="slug" name="slug" class="regular-text" placeholder="<?php echo esc_attr( $t( 'cat_slug_ph', __( 'Auto-generated if left blank', 'appointment-booking-system' ) ) ); ?>" value="<?php echo esc_attr( $edit['slug'] ?? '' ); ?>" /></td>
					</tr>
					<tr>
						<th><label for="description"><?php echo esc_html( $t( 'srv_field_description', __( 'Description', 'appointment-booking-system' ) ) ); ?></label></th>
						<td><textarea id="description" name="description" class="large-text" rows="3"><?php echo esc_textarea( $edit['description'] ?? '' ); ?></textarea></td>
					</tr>
					<tr>
						<th><label for="icon"><?php echo esc_html( $t( 'cat_field_icon', __( 'Icon (optional)', 'appointment-booking-system' ) ) ); ?></label></th>
						<td>
							<input type="text" id="icon" name="icon" class="regular-text ab-media-field" value="<?php echo esc_attr( $edit['icon'] ?? '' ); ?>" />
							<button type="button" class="button ab-media-upload" data-target="#icon"><?php echo esc_html( $t( 'doc_btn_choose_image', __( 'Choose Image', 'appointment-booking-system' ) ) ); ?></button>
						</td>
					</tr>
					<tr>
						<th><label for="display_order"><?php echo esc_html( $t( 'cat_field_order', __( 'Display Order', 'appointment-booking-system' ) ) ); ?></label></th>
						<td><input type="number" id="display_order" name="display_order" value="<?php echo esc_attr( $edit['display_order'] ?? 0 ); ?>" /></td>
					</tr>
					<tr>
						<th><?php echo esc_html( $t( 'col_status', __( 'Status', 'appointment-booking-system' ) ) ); ?></th>
						<td><label><input type="checkbox" name="status" <?php checked( ! isset( $edit ) || ! empty( $edit['status'] ) ); ?> /> <?php echo esc_html( $t( 'status_active', __( 'Active', 'appointment-booking-system' ) ) ); ?></label></td>
					</tr>
				</table>
				<p class="submit">
					<button type="submit" class="button button-primary"><?php echo $edit ? esc_html( $t( 'cat_btn_update', __( 'Update Category', 'appointment-booking-system' ) ) ) : esc_html( $t( 'cat_btn_add', __( 'Add Category', 'appointment-booking-system' ) ) ); ?></button>
					<?php if ( $edit ) : ?>
						<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ab-categories' ) ); ?>"><?php echo esc_html( $t( 'btn_cancel', __( 'Cancel', 'appointment-booking-system' ) ) ); ?></a>
					<?php endif; ?>
				</p>
			</form>
		</div>

		<div class="ab-col">
			<h2><?php echo esc_html( $t( 'cat_list_title', __( 'All Categories', 'appointment-booking-system' ) ) ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php echo esc_html( $t( 'col_name', __( 'Name', 'appointment-booking-system' ) ) ); ?></th>
						<th><?php echo esc_html( $t( 'srv_field_slug', __( 'Slug', 'appointment-booking-system' ) ) ); ?></th>
						<th><?php echo esc_html( $t( 'col_order', __( 'Order', 'appointment-booking-system' ) ) ); ?></th>
						<th><?php echo esc_html( $t( 'col_status', __( 'Status', 'appointment-booking-system' ) ) ); ?></th>
						<?php if ( Translation_Service::is_wpml_active() ) : ?>
							<th><?php echo esc_html( $t( 'col_translations', __( 'Translations', 'appointment-booking-system' ) ) ); ?></th>
						<?php endif; ?>
						<th><?php echo esc_html( $t( 'col_actions', __( 'Actions', 'appointment-booking-system' ) ) ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( $categories ) : ?>
					<?php foreach ( $categories as $cat ) : ?>
						<tr>
							<td><?php echo esc_html( $cat['name'] ); ?></td>
							<td><?php echo esc_html( $cat['slug'] ); ?></td>
							<td><?php echo esc_html( $cat['display_order'] ); ?></td>
							<td><?php echo $cat['status'] ? '<span class="ab-badge ab-badge-confirmed">' . esc_html( $t( 'status_active', __( 'Active', 'appointment-booking-system' ) ) ) . '</span>' : '<span class="ab-badge ab-badge-cancelled">' . esc_html( $t( 'status_inactive', __( 'Inactive', 'appointment-booking-system' ) ) ) . '</span>'; ?></td>
							<?php if ( Translation_Service::is_wpml_active() ) : ?>
								<td>
									<?php
									echo Translation_Service::render_translation_column(
										Translation_Service::TYPE_CATEGORY,
										$cat['id']
									);
									?>
								</td>
							<?php endif; ?>
							<td>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=ab-categories&edit=' . $cat['id'] ) ); ?>"><?php echo esc_html( $t( 'btn_edit', __( 'Edit', 'appointment-booking-system' ) ) ); ?></a> |
								<a class="ab-delete-link" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ab_delete_category&id=' . $cat['id'] ), 'ab_admin_nonce' ) ); ?>"><?php echo esc_html( $t( 'btn_delete', __( 'Delete', 'appointment-booking-system' ) ) ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr><td colspan="6"><?php echo esc_html( $t( 'cat_no_categories', __( 'No categories yet.', 'appointment-booking-system' ) ) ); ?></td></tr>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
