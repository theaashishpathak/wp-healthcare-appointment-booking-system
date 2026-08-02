<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AB\Includes\Security\Security;
use AB\Includes\Language\Translation_Service;

$primary   = ab_get_setting( 'primary_color', '#0B6E4F' );
$secondary = ab_get_setting( 'secondary_color', '#0E2A47' );
$radius    = (int) ab_get_setting( 'border_radius', 8 );
$nonce     = wp_create_nonce( Security::FRONTEND_NONCE );

// Load translated static strings for the current language (built-in + admin custom overrides).
$_cur_lang = Translation_Service::get_current_language();
$_lang_map = Translation_Service::get_i18n_strings( $_cur_lang );

/**
 * Helper: return translated string by key, or fall back to $default (English).
 *
 * @param string $key     Key in the i18n map (e.g. 'step_category').
 * @param string $default English fallback string.
 * @return string
 */
$t = function ( $key, $default ) use ( $_lang_map ) {
	return ! empty( $_lang_map[ $key ] ) ? $_lang_map[ $key ] : $default;
};
?>

<div class="ab-booking-wizard"
	style="--ab-primary: <?php echo esc_attr( $primary ); ?>; --ab-secondary: <?php echo esc_attr( $secondary ); ?>; --ab-radius: <?php echo esc_attr( $radius ); ?>px;"
	data-nonce="<?php echo esc_attr( $nonce ); ?>">

	<div class="ab-steps-indicator" aria-hidden="true">
		<?php
		$labels = array(
			$t( 'label_doctor',   __( 'Doctor', 'appointment-booking-system' ) ),
			$t( 'label_category', __( 'Category', 'appointment-booking-system' ) ),
			$t( 'label_services', __( 'Services', 'appointment-booking-system' ) ),
			$t( 'label_date',     __( 'Date', 'appointment-booking-system' ) ),
			$t( 'label_time',     __( 'Time', 'appointment-booking-system' ) ),
			$t( 'label_details',  __( 'Details', 'appointment-booking-system' ) ),
			$t( 'label_review',   __( 'Review', 'appointment-booking-system' ) ),
			$t( 'label_done',     __( 'Done', 'appointment-booking-system' ) ),
		);
		foreach ( $labels as $i => $label ) :
			?>
			<div class="ab-step-dot <?php echo 0 === $i ? 'ab-active' : ''; ?>" data-step="<?php echo esc_attr( $i + 1 ); ?>">
				<span class="ab-dot-num"><?php echo esc_html( $i + 1 ); ?></span>
				<span class="ab-dot-label"><?php echo esc_html( $label ); ?></span>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="ab-alert" role="alert" style="display:none;"></div>

	<form id="ab-booking-form" novalidate>

		<!-- honeypot spam field, must stay empty -->
		<div class="ab-honeypot" aria-hidden="true"><input type="text" name="ab_website" tabindex="-1" autocomplete="off" /></div>

		<!-- Step 1: Doctor -->
		<section class="ab-step" data-step="1">
			<h3><?php echo esc_html( $t( 'step_doctor', __( 'Choose Doctor', 'appointment-booking-system' ) ) ); ?></h3>
			<div class="ab-grid ab-doctor-grid">
				<?php foreach ( $doctors as $doc ) : ?>
					<button type="button" class="ab-card ab-doctor-card" data-id="<?php echo esc_attr( $doc['id'] ); ?>" data-name="<?php echo esc_attr( $doc['name'] ); ?>">
						<?php if ( ! empty( $doc['image'] ) ) : ?>
							<img src="<?php echo esc_url( $doc['image'] ); ?>" alt="" class="ab-doctor-photo" />
						<?php else : ?>
							<div class="ab-doctor-photo"></div>
						<?php endif; ?>
						<span class="ab-doctor-name"><?php echo esc_html( $doc['name'] ); ?></span>
						<?php if ( ! empty( $doc['qualification'] ) ) : ?>
							<span class="ab-doctor-meta"><?php echo esc_html( $doc['qualification'] ); ?></span>
						<?php endif; ?>
						<?php if ( ! empty( $doc['experience'] ) ) : ?>
							<span class="ab-doctor-meta"><?php echo esc_html( $doc['experience'] ); ?></span>
						<?php endif; ?>
						<?php if ( ! empty( $doc['specialization'] ) ) : ?>
							<span class="ab-doctor-meta"><?php echo esc_html( $doc['specialization'] ); ?></span>
						<?php endif; ?>
						<span class="ab-doctor-select-btn"><?php esc_html_e( 'Select', 'appointment-booking-system' ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>
		</section>

		<!-- Step 2: Treatment Category -->
		<section class="ab-step" data-step="2" style="display:none;">
			<h3><?php echo esc_html( $t( 'step_category', __( 'Select Treatment Category', 'appointment-booking-system' ) ) ); ?></h3>
			<div class="ab-grid ab-category-grid">
				<?php foreach ( $categories as $cat ) : ?>
					<button type="button" class="ab-card ab-category-card" data-id="<?php echo esc_attr( $cat['id'] ); ?>" data-name="<?php echo esc_attr( $cat['name'] ); ?>">
						<?php if ( ! empty( $cat['icon'] ) ) : ?>
							<img src="<?php echo esc_url( $cat['icon'] ); ?>" alt="" class="ab-card-icon" />
						<?php endif; ?>
						<span class="ab-card-title"><?php echo esc_html( $cat['name'] ); ?></span>
						<?php if ( ! empty( $cat['description'] ) ) : ?>
							<span class="ab-card-desc"><?php echo esc_html( wp_trim_words( $cat['description'], 12 ) ); ?></span>
						<?php endif; ?>
					</button>
				<?php endforeach; ?>
			</div>
			<div class="ab-step-nav">
				<button type="button" class="ab-btn ab-btn-back" data-back="1"><?php echo esc_html( $t( 'btn_back', __( 'Back', 'appointment-booking-system' ) ) ); ?></button>
			</div>
		</section>




		
		<!-- Step 3: Services -->
		<section class="ab-step" data-step="3" style="display:none;">
			<h3><?php echo esc_html( $t( 'step_services', __( 'Choose Services', 'appointment-booking-system' ) ) ); ?></h3>
			<div class="ab-service-list" data-loading-text="<?php esc_attr_e( 'Loading services…', 'appointment-booking-system' ); ?>"></div>
			<p class="ab-total-duration"><?php echo esc_html( $t( 'total_duration', __( 'Total Duration:', 'appointment-booking-system' ) ) ); ?> <strong class="ab-total-duration-value">0 <?php echo esc_html( $t( 'total_duration_unit', __( 'Minutes', 'appointment-booking-system' ) ) ); ?></strong></p>
			<div class="ab-step-nav">
				<button type="button" class="ab-btn ab-btn-back" data-back="2"><?php echo esc_html( $t( 'btn_back', __( 'Back', 'appointment-booking-system' ) ) ); ?></button>
				<button type="button" class="ab-btn ab-btn-primary ab-btn-next" data-next="4"><?php echo esc_html( $t( 'btn_next', __( 'Next', 'appointment-booking-system' ) ) ); ?></button>
			</div>
		</section>

		<!-- Step 4: Date -->
		<section class="ab-step" data-step="4" style="display:none;">
			<h3><?php echo esc_html( $t( 'step_date', __( 'Choose Appointment Date', 'appointment-booking-system' ) ) ); ?></h3>
			<div class="ab-calendar" data-loading-text="<?php esc_attr_e( 'Loading calendar…', 'appointment-booking-system' ); ?>"></div>
			<div class="ab-step-nav">
				<button type="button" class="ab-btn ab-btn-back" data-back="3"><?php echo esc_html( $t( 'btn_back', __( 'Back', 'appointment-booking-system' ) ) ); ?></button>
			</div>
		</section>

		<!-- Step 5: Time -->
		<section class="ab-step" data-step="5" style="display:none;">
			<h3><?php echo esc_html( $t( 'step_time', __( 'Choose Available Time', 'appointment-booking-system' ) ) ); ?></h3>
			<div class="ab-slot-grid" data-loading-text="<?php esc_attr_e( 'Loading time slots…', 'appointment-booking-system' ); ?>"></div>
			<div class="ab-step-nav">
				<button type="button" class="ab-btn ab-btn-back" data-back="4"><?php echo esc_html( $t( 'btn_back', __( 'Back', 'appointment-booking-system' ) ) ); ?></button>
			</div>
		</section>

		<!-- Step 6: Patient Details -->
		<section class="ab-step" data-step="6" style="display:none;">
			<h3><?php echo esc_html( $t( 'step_details', __( 'Personal Information', 'appointment-booking-system' ) ) ); ?></h3>
			<div class="ab-form-grid">
				<div class="ab-field">
					<label for="ab_first_name"><?php echo esc_html( $t( 'field_first_name', __( 'First Name', 'appointment-booking-system' ) ) ); ?> *</label>
					<input type="text" id="ab_first_name" name="first_name" required />
				</div>
				<div class="ab-field">
					<label for="ab_last_name"><?php echo esc_html( $t( 'field_last_name', __( 'Last Name', 'appointment-booking-system' ) ) ); ?> *</label>
					<input type="text" id="ab_last_name" name="last_name" required />
				</div>
				<div class="ab-field">
					<label for="ab_email"><?php echo esc_html( $t( 'field_email', __( 'Email', 'appointment-booking-system' ) ) ); ?> *</label>
					<input type="email" id="ab_email" name="email" required />
				</div>
		

		<div class="ab-field ab-phone-group">
    <label for="ab_phone">
        <?php echo esc_html( $t( 'field_phone', __( 'Phone Number', 'appointment-booking-system' ) ) ); ?> *
    </label>

    <div class="ab-phone-input">
        <input
            type="text"
            id="ab_country_code"
            name="country_code"
            class="ab-country-code"
            value="+1"
            placeholder="+1"
        >

        <input
            type="tel"
            id="ab_phone"
            name="phone"
            class="ab-phone-number"
            placeholder="<?php esc_attr_e('Phone Number', 'appointment-booking-system'); ?>"
            required
        >
    </div>
</div>
				<!--<div class="ab-field">-->
				<!--	<label for="ab_country_code"><?php esc_html_e( 'Country Code', 'appointment-booking-system' ); ?></label>-->
				<!--	<input type="text" id="ab_country_code" name="country_code" placeholder="+1" />-->
				<!--</div>-->
				<!--<div class="ab-field">-->
				<!--	<label for="ab_phone"><?php esc_html_e( 'Phone', 'appointment-booking-system' ); ?> *</label>-->
				<!--	<input type="tel" id="ab_phone" name="phone" required />-->
				<!--</div>-->
				<div class="ab-field ab-field-full">
					<label for="ab_message"><?php echo esc_html( $t( 'field_message', __( 'Message (Optional)', 'appointment-booking-system' ) ) ); ?></label>
					<textarea id="ab_message" name="message" rows="3"></textarea>
				</div>
			
			<div class="ab-step-nav">
				<button type="button" class="ab-btn ab-btn-back" data-back="5"><?php echo esc_html( $t( 'btn_back', __( 'Back', 'appointment-booking-system' ) ) ); ?></button>
				<button type="button" class="ab-btn ab-btn-primary ab-btn-next" data-next="7"><?php echo esc_html( $t( 'btn_next', __( 'Next', 'appointment-booking-system' ) ) ); ?></button>
			</div>
		</section>

		<!-- Step 7: Review -->
		<section class="ab-step" data-step="7" style="display:none;">
			<h3><?php echo esc_html( $t( 'step_review', __( 'Review Appointment', 'appointment-booking-system' ) ) ); ?></h3>
			<div class="ab-review-card"></div>
			<div class="ab-step-nav">
				<button type="button" class="ab-btn ab-btn-back" data-back="6"><?php echo esc_html( $t( 'btn_back', __( 'Back', 'appointment-booking-system' ) ) ); ?></button>
				<button type="submit" class="ab-btn ab-btn-primary ab-btn-submit"><?php echo esc_html( $t( 'btn_submit', __( 'Confirm Appointment', 'appointment-booking-system' ) ) ); ?></button>
			</div>
		</section>

		<!-- Step 8: Confirmation -->
		<section class="ab-step ab-step-success" data-step="8" style="display:none;">
			<div class="ab-success-icon">&#10003;</div>
			<h3><?php echo esc_html( $t( 'success_heading', __( 'Appointment Submitted Successfully', 'appointment-booking-system' ) ) ); ?></h3>
			<p><?php echo esc_html( $t( 'success_p1', __( 'Thank you for booking your appointment. Our team has received your request.', 'appointment-booking-system' ) ) ); ?></p>
			<p><?php echo esc_html( $t( 'success_p2', __( 'A confirmation email has been sent to your registered email address.', 'appointment-booking-system' ) ) ); ?></p>
			<p><?php echo esc_html( $t( 'success_p3', __( 'Our representative will contact you shortly if further information is required.', 'appointment-booking-system' ) ) ); ?></p>
			<p class="ab-booking-id-display"></p>
			<button type="button" class="ab-btn ab-btn-primary ab-btn-restart"><?php echo esc_html( $t( 'btn_restart', __( 'Book Another Appointment', 'appointment-booking-system' ) ) ); ?></button>
		</section>
	</form>
</div>
