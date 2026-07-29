<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AB\Includes\Language\Translation_Service;

// Verify nonce
if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'ab_translation_nonce' ) ) {
	wp_die( esc_html__( 'Security check failed.', 'appointment-booking-system' ) );
}

// Verify permissions
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have permission.', 'appointment-booking-system' ) );
}

// Validate query params
$type    = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '';
$item_id = isset( $_GET['item_id'] ) ? absint( $_GET['item_id'] ) : 0;
$lang    = isset( $_GET['lang'] ) ? sanitize_text_field( wp_unslash( $_GET['lang'] ) ) : '';

if ( ! $type || ! $item_id || ! $lang ) {
	wp_die( esc_html__( 'Missing required translation parameters.', 'appointment-booking-system' ) );
}

$lang_details = Translation_Service::get_language_name( $lang );
$lang_name    = $lang_details ? $lang_details['display_name'] : strtoupper( $lang );

global $wpdb;

// Fetch original content
$original = null;
$translated = null;
$fields = array();

// Determine mapped translation record ID.
// Only treat as a real translation if it is a DIFFERENT row than the source.
$raw_translated_id = $wpdb->get_var( $wpdb->prepare(
	"SELECT translated_object_id FROM {$wpdb->prefix}ab_translation_map
	 WHERE object_type = %s AND source_object_id = %d AND language_code = %s",
	$type,
	$item_id,
	$lang
) );
$translated_id = ( $raw_translated_id && intval( $raw_translated_id ) !== intval( $item_id ) ) ? intval( $raw_translated_id ) : null;

if ( $type === Translation_Service::TYPE_DOCTOR ) {
	$original = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM {$wpdb->prefix}ab_doctors WHERE id = %d",
		$item_id
	), ARRAY_A );

	if ( $translated_id ) {
		$translated = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}ab_doctors WHERE id = %d",
			$translated_id
		), ARRAY_A ) ?: null;
	}

	$fields = array(
		'name'           => array( 'label' => __( 'Doctor Name', 'appointment-booking-system' ), 'type' => 'text', 'required' => true ),
		'qualification'  => array( 'label' => __( 'Qualification', 'appointment-booking-system' ), 'type' => 'text' ),
		'experience'     => array( 'label' => __( 'Experience', 'appointment-booking-system' ), 'type' => 'text' ),
		'specialization' => array( 'label' => __( 'Specialization', 'appointment-booking-system' ), 'type' => 'text' ),
		'bio'            => array( 'label' => __( 'Biography', 'appointment-booking-system' ), 'type' => 'textarea' ),
	);

} elseif ( $type === Translation_Service::TYPE_CATEGORY ) {
	$original = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM {$wpdb->prefix}ab_categories WHERE id = %d",
		$item_id
	), ARRAY_A );

	if ( $translated_id ) {
		$translated = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}ab_categories WHERE id = %d",
			$translated_id
		), ARRAY_A ) ?: null;
	}

	$fields = array(
		'name'        => array( 'label' => __( 'Category Name', 'appointment-booking-system' ), 'type' => 'text', 'required' => true ),
		'description' => array( 'label' => __( 'Description', 'appointment-booking-system' ), 'type' => 'textarea' ),
	);

} elseif ( $type === Translation_Service::TYPE_SERVICE ) {
	$original = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM {$wpdb->prefix}ab_services WHERE id = %d",
		$item_id
	), ARRAY_A );

	if ( $translated_id ) {
		$translated = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}ab_services WHERE id = %d",
			$translated_id
		), ARRAY_A ) ?: null;
	}

	$fields = array(
		'name'        => array( 'label' => __( 'Service Name', 'appointment-booking-system' ), 'type' => 'text', 'required' => true ),
		'description' => array( 'label' => __( 'Description', 'appointment-booking-system' ), 'type' => 'textarea' ),
	);
}

if ( ! $original ) {
	wp_die( esc_html__( 'Original item not found.', 'appointment-booking-system' ) );
}

$cancel_page_map = array(
	Translation_Service::TYPE_DOCTOR   => 'ab-doctors',
	Translation_Service::TYPE_CATEGORY => 'ab-categories',
	Translation_Service::TYPE_SERVICE  => 'ab-services',
);
$cancel_url = admin_url( 'admin.php?page=' . ( $cancel_page_map[ $type ] ?? 'ab-dashboard' ) );

?>
<div class="wrap ab-wrap">
	<h1><?php esc_html_e( 'Manage Translation', 'appointment-booking-system' ); ?></h1>
	<p class="description">
		<?php
		printf(
			/* translators: 1: item type, 2: target language name */
			esc_html__( 'Translate this %1$s to %2$s below.', 'appointment-booking-system' ),
			esc_html( $type ),
			'<strong>' . esc_html( $lang_name ) . '</strong>'
		);
		?>
	</p>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'ab_translation_nonce' ); ?>
		<input type="hidden" name="action" value="ab_save_translation" />
		<input type="hidden" name="type" value="<?php echo esc_attr( $type ); ?>" />
		<input type="hidden" name="item_id" value="<?php echo esc_attr( $item_id ); ?>" />
		<input type="hidden" name="lang" value="<?php echo esc_attr( $lang ); ?>" />

		<div class="ab-columns" style="display:flex;gap:20px;margin-top:20px;flex-wrap:wrap;">
			<!-- Left column: Original -->
			<div class="ab-col" style="flex:1;min-width:300px;background:#fff;padding:20px;border:1px solid #ccd0d4;border-radius:4px;">
				<h2 style="margin-top:0;border-bottom:1px solid #eee;padding-bottom:10px;">
					<?php esc_html_e( 'Original Content (English)', 'appointment-booking-system' ); ?>
				</h2>
				<table class="form-table" style="width:100%;">
					<?php foreach ( $fields as $key => $meta ) : ?>
						<tr style="border-bottom:1px solid #f9f9f9;">
							<th style="width:30%;padding:10px 0;vertical-align:top;font-weight:600;">
								<?php echo esc_html( $meta['label'] ); ?>
							</th>
							<td style="padding:10px 0;vertical-align:top;">
								<?php if ( $meta['type'] === 'textarea' ) : ?>
									<div style="white-space:pre-wrap;background:#f6f7f7;padding:10px;border-radius:4px;border:1px solid #dcdcde;">
										<?php echo esc_html( $original[ $key ] ?? '' ); ?>
									</div>
								<?php else : ?>
									<strong><?php echo esc_html( $original[ $key ] ?? '' ); ?></strong>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
			</div>

			<!-- Right column: Translation Form -->
			<div class="ab-col" style="flex:1;min-width:300px;background:#fff;padding:20px;border:1px solid #ccd0d4;border-radius:4px;">
				<h2 style="margin-top:0;border-bottom:1px solid #eee;padding-bottom:10px;">
					<?php printf( esc_html__( 'Translation (%s)', 'appointment-booking-system' ), esc_html( $lang_name ) ); ?>
				</h2>
				<table class="form-table" style="width:100%;">
					<?php foreach ( $fields as $key => $meta ) : ?>
						<tr>
							<th style="width:30%;padding:10px 0;vertical-align:top;">
								<label for="trans-<?php echo esc_attr( $key ); ?>">
									<?php echo esc_html( $meta['label'] ); ?>
									<?php if ( ! empty( $meta['required'] ) ) : ?>*<?php endif; ?>
								</label>
							</th>
							<td style="padding:10px 0;vertical-align:top;">
								<?php if ( $meta['type'] === 'textarea' ) : ?>
									<textarea 
										id="trans-<?php echo esc_attr( $key ); ?>" 
										name="<?php echo esc_attr( $key ); ?>" 
										class="large-text" 
										rows="6"
										<?php echo ! empty( $meta['required'] ) ? 'required' : ''; ?>
									><?php echo esc_textarea( $translated[ $key ] ?? '' ); ?></textarea>
								<?php else : ?>
									<input 
										type="text" 
										id="trans-<?php echo esc_attr( $key ); ?>" 
										name="<?php echo esc_attr( $key ); ?>" 
										class="regular-text" 
										style="width:100%;"
										value="<?php echo esc_attr( $translated[ $key ] ?? '' ); ?>" 
										<?php echo ! empty( $meta['required'] ) ? 'required' : ''; ?>
									/>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
			</div>
		</div>

		<p class="submit" style="margin-top:20px;">
			<button type="submit" class="button button-primary button-large">
				<?php esc_html_e( 'Save Translation', 'appointment-booking-system' ); ?>
			</button>
			<a class="button button-large" href="<?php echo esc_url( $cancel_url ); ?>">
				<?php esc_html_e( 'Cancel', 'appointment-booking-system' ); ?>
			</a>
		</p>
	</form>
</div>
