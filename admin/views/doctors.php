<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AB\Includes\Models\Doctor_Model;
use AB\Includes\Models\Category_Model;
use AB\Includes\Language\Translation_Service;
$doctor_model   = new Doctor_Model();
$category_model = new Category_Model();
$categories     = $category_model->all();

$search      = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$filter_cat  = isset( $_GET['category_id'] ) ? absint( $_GET['category_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$doctors     = $doctor_model->all( array( 'search' => $search, 'category_id' => $filter_cat ) );

$edit    = null;
$edit_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$edit_categories = array();
if ( $edit_id ) {
	$edit = $doctor_model->find( $edit_id );
	$edit_categories = $doctor_model->get_category_ids( $edit_id );
}
?>
<div class="wrap ab-wrap">
	<h1><?php esc_html_e( 'Doctors', 'appointment-booking-system' ); ?></h1>
	<?php include __DIR__ . '/partials/notice.php'; ?>

	<div class="ab-columns">
		<div class="ab-col ab-col-form">
			<h2><?php echo $edit ? esc_html__( 'Edit Doctor', 'appointment-booking-system' ) : esc_html__( 'Add New Doctor', 'appointment-booking-system' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'ab_admin_nonce' ); ?>
				<input type="hidden" name="action" value="ab_save_doctor" />
				<input type="hidden" name="id" value="<?php echo esc_attr( $edit['id'] ?? '' ); ?>" />

				<table class="form-table">
					<tr><th><label for="name"><?php esc_html_e( 'Doctor Name', 'appointment-booking-system' ); ?> *</label></th>
						<td><input type="text" id="name" name="name" class="regular-text" required value="<?php echo esc_attr( $edit['name'] ?? '' ); ?>" /></td></tr>
					<tr><th><label for="image"><?php esc_html_e( 'Photo', 'appointment-booking-system' ); ?></label></th>
						<td><input type="text" id="image" name="image" class="regular-text ab-media-field" value="<?php echo esc_attr( $edit['image'] ?? '' ); ?>" />
							<button type="button" class="button ab-media-upload" data-target="#image"><?php esc_html_e( 'Choose Image', 'appointment-booking-system' ); ?></button></td></tr>
					<tr><th><label for="qualification"><?php esc_html_e( 'Qualification', 'appointment-booking-system' ); ?></label></th>
						<td><input type="text" id="qualification" name="qualification" class="regular-text" value="<?php echo esc_attr( $edit['qualification'] ?? '' ); ?>" /></td></tr>
					<tr><th><label for="experience"><?php esc_html_e( 'Experience', 'appointment-booking-system' ); ?></label></th>
						<td><input type="text" id="experience" name="experience" class="regular-text" placeholder="e.g. 10 Years" value="<?php echo esc_attr( $edit['experience'] ?? '' ); ?>" /></td></tr>
					<tr><th><label for="specialization"><?php esc_html_e( 'Specialization', 'appointment-booking-system' ); ?></label></th>
						<td><input type="text" id="specialization" name="specialization" class="regular-text" value="<?php echo esc_attr( $edit['specialization'] ?? '' ); ?>" /></td></tr>
					<tr><th><label for="email"><?php esc_html_e( 'Email', 'appointment-booking-system' ); ?> *</label></th>
						<td><input type="email" id="email" name="email" class="regular-text" required value="<?php echo esc_attr( $edit['email'] ?? '' ); ?>" /></td></tr>
					<tr><th><label for="phone"><?php esc_html_e( 'Phone', 'appointment-booking-system' ); ?></label></th>
						<td><input type="text" id="phone" name="phone" class="regular-text" value="<?php echo esc_attr( $edit['phone'] ?? '' ); ?>" /></td></tr>
					<tr><th><?php esc_html_e( 'Treatment Categories', 'appointment-booking-system' ); ?> *</th>
						<td>
							<?php foreach ( $categories as $cat ) : ?>
								<label style="display:block;"><input type="checkbox" name="category_ids[]" value="<?php echo esc_attr( $cat['id'] ); ?>" <?php checked( in_array( (int) $cat['id'], $edit_categories, true ) ); ?> /> <?php echo esc_html( $cat['name'] ); ?></label>
							<?php endforeach; ?>
						</td></tr>
					<tr><th><label for="bio"><?php esc_html_e( 'Biography', 'appointment-booking-system' ); ?></label></th>
						<td><textarea id="bio" name="bio" class="large-text" rows="4"><?php echo esc_textarea( $edit['bio'] ?? '' ); ?></textarea></td></tr>
					<tr><th><?php esc_html_e( 'Status', 'appointment-booking-system' ); ?></th>
						<td><label><input type="checkbox" name="status" <?php checked( ! isset( $edit ) || ! empty( $edit['status'] ) ); ?> /> <?php esc_html_e( 'Active', 'appointment-booking-system' ); ?></label></td></tr>
				</table>

				<p class="submit">
					<button type="submit" class="button button-primary"><?php echo $edit ? esc_html__( 'Update Doctor', 'appointment-booking-system' ) : esc_html__( 'Add Doctor', 'appointment-booking-system' ); ?></button>
					<?php if ( $edit ) : ?><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ab-doctors' ) ); ?>"><?php esc_html_e( 'Cancel', 'appointment-booking-system' ); ?></a><?php endif; ?>
				</p>
			</form>
		</div>

		<div class="ab-col">
			<h2><?php esc_html_e( 'All Doctors', 'appointment-booking-system' ); ?></h2>
			<form method="get" class="ab-filter-bar">
				<input type="hidden" name="page" value="ab-doctors" />
				<input type="search" name="s" placeholder="<?php esc_attr_e( 'Search doctors…', 'appointment-booking-system' ); ?>" value="<?php echo esc_attr( $search ); ?>" />
				<select name="category_id">
					<option value=""><?php esc_html_e( 'All Categories', 'appointment-booking-system' ); ?></option>
					<?php foreach ( $categories as $cat ) : ?>
						<option value="<?php echo esc_attr( $cat['id'] ); ?>" <?php selected( $filter_cat, $cat['id'] ); ?>><?php echo esc_html( $cat['name'] ); ?></option>
					<?php endforeach; ?>
				</select>
				<button class="button"><?php esc_html_e( 'Filter', 'appointment-booking-system' ); ?></button>
			</form>
			<table class="widefat striped">
				<thead><tr>
					<th><?php esc_html_e( 'Photo', 'appointment-booking-system' ); ?></th>
					<th><?php esc_html_e( 'Name', 'appointment-booking-system' ); ?></th>
					<th><?php esc_html_e( 'Email', 'appointment-booking-system' ); ?></th>
					<th><?php esc_html_e( 'Status', 'appointment-booking-system' ); ?></th>
<th><?php esc_html_e( 'Translations', 'appointment-booking-system' ); ?></th>
<th><?php esc_html_e( 'Actions', 'appointment-booking-system' ); ?></th>
				</tr></thead>
				<tbody>
				<?php if ( $doctors ) : ?>
					<?php foreach ( $doctors as $doc ) : ?>
						<tr>
							<td><?php if ( ! empty( $doc['image'] ) ) : ?><img src="<?php echo esc_url( $doc['image'] ); ?>" class="ab-thumb" alt="" /><?php endif; ?></td>
							<td><?php echo esc_html( $doc['name'] ); ?></td>
							<td><?php echo esc_html( $doc['email'] ); ?></td>
							<td><?php echo $doc['status'] ? '<span class="ab-badge ab-badge-confirmed">' . esc_html__( 'Active', 'appointment-booking-system' ) . '</span>' : '<span class="ab-badge ab-badge-cancelled">' . esc_html__( 'Inactive', 'appointment-booking-system' ) . '</span>'; ?></td>
							<td>
    <?php
    echo Translation_Service::render_translation_column(
        Translation_Service::TYPE_DOCTOR,
        $doc['id']
    );
    ?>
</td>
							<td>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=ab-doctors&edit=' . $doc['id'] ) ); ?>"><?php esc_html_e( 'Edit', 'appointment-booking-system' ); ?></a> |
								<a class="ab-delete-link" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ab_delete_doctor&id=' . $doc['id'] ), 'ab_admin_nonce' ) ); ?>"><?php esc_html_e( 'Delete', 'appointment-booking-system' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr><td colspan="5"><?php esc_html_e( 'No doctors found.', 'appointment-booking-system' ); ?></td></tr>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
