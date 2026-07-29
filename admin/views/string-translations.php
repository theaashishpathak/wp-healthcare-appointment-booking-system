<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AB\Includes\Language\Translation_Service;

$languages   = Translation_Service::get_languages();
$active_tab  = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'strings';
$active_lang = isset( $_GET['lang'] ) ? sanitize_text_field( wp_unslash( $_GET['lang'] ) ) : Translation_Service::get_current_language();

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
	'Step 7 Review Page Labels' => array(
		'review_treatment'      => array( 'label' => __( 'Review Card: Treatment Label', 'appointment-booking-system' ), 'default' => 'Treatment' ),
		'review_doctor'         => array( 'label' => __( 'Review Card: Doctor Label', 'appointment-booking-system' ), 'default' => 'Doctor' ),
		'review_services'       => array( 'label' => __( 'Review Card: Services Label', 'appointment-booking-system' ), 'default' => 'Services' ),
		'review_date'           => array( 'label' => __( 'Review Card: Date Label', 'appointment-booking-system' ), 'default' => 'Appointment Date' ),
		'review_time'           => array( 'label' => __( 'Review Card: Time Label', 'appointment-booking-system' ), 'default' => 'Appointment Time' ),
		'review_total_duration' => array( 'label' => __( 'Review Card: Duration Label', 'appointment-booking-system' ), 'default' => 'Total Duration' ),
		'review_patient_name'   => array( 'label' => __( 'Review Card: Patient Name Label', 'appointment-booking-system' ), 'default' => 'Patient Name' ),
		'review_email'          => array( 'label' => __( 'Review Card: Email Label', 'appointment-booking-system' ), 'default' => 'Email' ),
		'review_phone'          => array( 'label' => __( 'Review Card: Phone Label', 'appointment-booking-system' ), 'default' => 'Phone' ),
		'submitting'            => array( 'label' => __( 'Submit Progress Button Text', 'appointment-booking-system' ), 'default' => 'Submitting…' ),
	),
	'Email Template Strings' => array(
		'email_customer_title' => array( 'label' => __( 'Customer Email Title', 'appointment-booking-system' ), 'default' => 'Appointment Submitted Successfully' ),
		'email_customer_body'  => array( 'label' => __( 'Customer Email Body Notice', 'appointment-booking-system' ), 'default' => 'Thank you for booking your appointment. Our team has received your request and a representative will contact you shortly if further information is required.' ),
		'email_admin_title'    => array( 'label' => __( 'Admin Email Notification Title', 'appointment-booking-system' ), 'default' => 'New Appointment Received' ),
		'email_booking_id'     => array( 'label' => __( 'Email Field: Booking ID', 'appointment-booking-system' ), 'default' => 'Booking ID' ),
		'email_doctor'         => array( 'label' => __( 'Email Field: Doctor', 'appointment-booking-system' ), 'default' => 'Doctor' ),
		'email_treatment'      => array( 'label' => __( 'Email Field: Treatment', 'appointment-booking-system' ), 'default' => 'Treatment' ),
		'email_category'       => array( 'label' => __( 'Email Field: Category', 'appointment-booking-system' ), 'default' => 'Category' ),
		'email_services'       => array( 'label' => __( 'Email Field: Services', 'appointment-booking-system' ), 'default' => 'Services' ),
		'email_date'           => array( 'label' => __( 'Email Field: Date', 'appointment-booking-system' ), 'default' => 'Date' ),
		'email_time'           => array( 'label' => __( 'Email Field: Time', 'appointment-booking-system' ), 'default' => 'Time' ),
		'email_patient'        => array( 'label' => __( 'Email Field: Patient', 'appointment-booking-system' ), 'default' => 'Patient' ),
		'email_email'          => array( 'label' => __( 'Email Field: Email', 'appointment-booking-system' ), 'default' => 'Email' ),
		'email_phone'          => array( 'label' => __( 'Email Field: Phone', 'appointment-booking-system' ), 'default' => 'Phone' ),
		'email_message'        => array( 'label' => __( 'Email Field: Message', 'appointment-booking-system' ), 'default' => 'Message' ),
		'email_clinic_contact' => array( 'label' => __( 'Email Footer: Clinic Contact Label', 'appointment-booking-system' ), 'default' => 'Clinic contact:' ),
		'email_view_admin'     => array( 'label' => __( 'Email Button: View in Admin Panel', 'appointment-booking-system' ), 'default' => 'View in Admin Panel' ),
	),
	'Admin Dashboard View Labels' => array(
		'dash_title'                 => array( 'label' => __( 'Dashboard Title', 'appointment-booking-system' ), 'default' => 'Appointment Booking Dashboard' ),
		'dash_total_categories'     => array( 'label' => __( 'Stat Card: Total Categories', 'appointment-booking-system' ), 'default' => 'Total Categories' ),
		'dash_total_doctors'        => array( 'label' => __( 'Stat Card: Total Doctors', 'appointment-booking-system' ), 'default' => 'Total Doctors' ),
		'dash_total_services'       => array( 'label' => __( 'Stat Card: Total Services', 'appointment-booking-system' ), 'default' => 'Total Services' ),
		'dash_todays_appointments'  => array( 'label' => __( 'Stat Card: Today\'s Appointments', 'appointment-booking-system' ), 'default' => 'Today\'s Appointments' ),
		'dash_upcoming_appointments'=> array( 'label' => __( 'Stat Card: Upcoming Appointments', 'appointment-booking-system' ), 'default' => 'Upcoming Appointments' ),
		'dash_pending_appointments' => array( 'label' => __( 'Stat Card: Pending Appointments', 'appointment-booking-system' ), 'default' => 'Pending Appointments' ),
		'dash_confirmed_appointments' => array( 'label' => __( 'Stat Card: Confirmed Appointments', 'appointment-booking-system' ), 'default' => 'Confirmed Appointments' ),
		'dash_cancelled_appointments' => array( 'label' => __( 'Stat Card: Cancelled Appointments', 'appointment-booking-system' ), 'default' => 'Cancelled Appointments' ),
		'dash_add_category'         => array( 'label' => __( 'Quick Action: + Add Category', 'appointment-booking-system' ), 'default' => '+ Add Category' ),
		'dash_add_doctor'           => array( 'label' => __( 'Quick Action: + Add Doctor', 'appointment-booking-system' ), 'default' => '+ Add Doctor' ),
		'dash_add_service'          => array( 'label' => __( 'Quick Action: + Add Service', 'appointment-booking-system' ), 'default' => '+ Add Service' ),
		'dash_manage_availability'  => array( 'label' => __( 'Quick Action: Manage Availability', 'appointment-booking-system' ), 'default' => 'Manage Availability' ),
		'dash_recent_appointments'  => array( 'label' => __( 'Table Title: Recent Appointments', 'appointment-booking-system' ), 'default' => 'Recent Appointments' ),
		'dash_upcoming_schedule'    => array( 'label' => __( 'Table Title: Upcoming Schedule', 'appointment-booking-system' ), 'default' => 'Upcoming Schedule' ),
	),
	'Admin Doctors Page Labels' => array(
		'doc_page_title'        => array( 'label' => __( 'Page Title', 'appointment-booking-system' ), 'default' => 'Doctors' ),
		'doc_add_heading'       => array( 'label' => __( 'Form Heading: Add Doctor', 'appointment-booking-system' ), 'default' => 'Add New Doctor' ),
		'doc_edit_heading'      => array( 'label' => __( 'Form Heading: Edit Doctor', 'appointment-booking-system' ), 'default' => 'Edit Doctor' ),
		'doc_field_name'        => array( 'label' => __( 'Field: Doctor Name', 'appointment-booking-system' ), 'default' => 'Doctor Name' ),
		'doc_field_photo'       => array( 'label' => __( 'Field: Photo', 'appointment-booking-system' ), 'default' => 'Photo' ),
		'doc_field_qualification'=> array( 'label' => __( 'Field: Qualification', 'appointment-booking-system' ), 'default' => 'Qualification' ),
		'doc_field_experience'  => array( 'label' => __( 'Field: Experience', 'appointment-booking-system' ), 'default' => 'Experience' ),
		'doc_field_specialization'=> array( 'label' => __( 'Field: Specialization', 'appointment-booking-system' ), 'default' => 'Specialization' ),
		'doc_field_email'       => array( 'label' => __( 'Field: Email', 'appointment-booking-system' ), 'default' => 'Email' ),
		'doc_field_phone'       => array( 'label' => __( 'Field: Phone', 'appointment-booking-system' ), 'default' => 'Phone' ),
		'doc_field_categories'  => array( 'label' => __( 'Field: Treatment Categories', 'appointment-booking-system' ), 'default' => 'Treatment Categories' ),
		'doc_field_bio'         => array( 'label' => __( 'Field: Biography', 'appointment-booking-system' ), 'default' => 'Biography' ),
		'doc_btn_add'           => array( 'label' => __( 'Button: Add Doctor', 'appointment-booking-system' ), 'default' => 'Add Doctor' ),
		'doc_btn_update'        => array( 'label' => __( 'Button: Update Doctor', 'appointment-booking-system' ), 'default' => 'Update Doctor' ),
		'doc_list_title'        => array( 'label' => __( 'Table Heading: All Doctors', 'appointment-booking-system' ), 'default' => 'All Doctors' ),
	),
	'Admin Services Page Labels' => array(
		'srv_page_title'        => array( 'label' => __( 'Page Title', 'appointment-booking-system' ), 'default' => 'Services' ),
		'srv_add_heading'       => array( 'label' => __( 'Form Heading: Add Service', 'appointment-booking-system' ), 'default' => 'Add New Service' ),
		'srv_edit_heading'      => array( 'label' => __( 'Form Heading: Edit Service', 'appointment-booking-system' ), 'default' => 'Edit Service' ),
		'srv_field_name'        => array( 'label' => __( 'Field: Service Name', 'appointment-booking-system' ), 'default' => 'Service Name' ),
		'srv_field_slug'        => array( 'label' => __( 'Field: Slug', 'appointment-booking-system' ), 'default' => 'Slug' ),
		'srv_field_category'    => array( 'label' => __( 'Field: Category', 'appointment-booking-system' ), 'default' => 'Category' ),
		'srv_field_duration'    => array( 'label' => __( 'Field: Duration', 'appointment-booking-system' ), 'default' => 'Duration' ),
		'srv_field_description' => array( 'label' => __( 'Field: Description', 'appointment-booking-system' ), 'default' => 'Description' ),
		'srv_btn_add'           => array( 'label' => __( 'Button: Add Service', 'appointment-booking-system' ), 'default' => 'Add Service' ),
		'srv_btn_update'        => array( 'label' => __( 'Button: Update Service', 'appointment-booking-system' ), 'default' => 'Update Service' ),
		'srv_list_title'        => array( 'label' => __( 'Table Heading: All Services', 'appointment-booking-system' ), 'default' => 'All Services' ),
	),
	'Admin Categories Page Labels' => array(
		'cat_page_title'        => array( 'label' => __( 'Page Title', 'appointment-booking-system' ), 'default' => 'Treatment Categories' ),
		'cat_add_heading'       => array( 'label' => __( 'Form Heading: Add Category', 'appointment-booking-system' ), 'default' => 'Add New Category' ),
		'cat_edit_heading'      => array( 'label' => __( 'Form Heading: Edit Category', 'appointment-booking-system' ), 'default' => 'Edit Category' ),
		'cat_field_name'        => array( 'label' => __( 'Field: Category Name', 'appointment-booking-system' ), 'default' => 'Category Name' ),
		'cat_field_icon'        => array( 'label' => __( 'Field: Icon', 'appointment-booking-system' ), 'default' => 'Icon (optional)' ),
		'cat_field_order'       => array( 'label' => __( 'Field: Display Order', 'appointment-booking-system' ), 'default' => 'Display Order' ),
		'cat_btn_add'           => array( 'label' => __( 'Button: Add Category', 'appointment-booking-system' ), 'default' => 'Add Category' ),
		'cat_btn_update'        => array( 'label' => __( 'Button: Update Category', 'appointment-booking-system' ), 'default' => 'Update Category' ),
		'cat_list_title'        => array( 'label' => __( 'Table Heading: All Categories', 'appointment-booking-system' ), 'default' => 'All Categories' ),
	),
	'Admin Availability Page Labels' => array(
		'avail_page_title'      => array( 'label' => __( 'Page Title', 'appointment-booking-system' ), 'default' => 'Availability Management' ),
		'avail_weekly_heading'  => array( 'label' => __( 'Heading: Weekly Working Hours', 'appointment-booking-system' ), 'default' => 'Weekly Working Hours' ),
		'avail_col_day'         => array( 'label' => __( 'Table Header: Day', 'appointment-booking-system' ), 'default' => 'Day' ),
		'avail_col_enabled'     => array( 'label' => __( 'Table Header: Enabled', 'appointment-booking-system' ), 'default' => 'Enabled' ),
		'avail_col_start_time'  => array( 'label' => __( 'Table Header: Start Time', 'appointment-booking-system' ), 'default' => 'Start Time' ),
		'avail_col_end_time'    => array( 'label' => __( 'Table Header: End Time', 'appointment-booking-system' ), 'default' => 'End Time' ),
		'avail_col_break_start' => array( 'label' => __( 'Table Header: Break Start', 'appointment-booking-system' ), 'default' => 'Break Start' ),
		'avail_col_break_end'   => array( 'label' => __( 'Table Header: Break End', 'appointment-booking-system' ), 'default' => 'Break End' ),
		'avail_col_slot_interval'=> array( 'label' => __( 'Table Header: Slot Interval', 'appointment-booking-system' ), 'default' => 'Slot Interval' ),
		'avail_btn_save'        => array( 'label' => __( 'Button: Save Weekly Availability', 'appointment-booking-system' ), 'default' => 'Save Weekly Availability' ),
		'hol_add_heading'       => array( 'label' => __( 'Heading: Add Holiday / Special Working Day', 'appointment-booking-system' ), 'default' => 'Add Holiday / Special Working Day' ),
		'hol_type_holiday'      => array( 'label' => __( 'Option: Holiday (disable date)', 'appointment-booking-system' ), 'default' => 'Holiday (disable date)' ),
		'hol_type_special'      => array( 'label' => __( 'Option: Special Working Day', 'appointment-booking-system' ), 'default' => 'Special Working Day (open on holiday)' ),
		'hol_list_title'        => array( 'label' => __( 'Table Heading: Holidays & Overrides', 'appointment-booking-system' ), 'default' => 'Holidays & Overrides for This Doctor' ),
	),
	'Admin Appointments Page Labels' => array(
		'app_page_title'        => array( 'label' => __( 'Page Title', 'appointment-booking-system' ), 'default' => 'Appointments' ),
		'app_btn_export_csv'    => array( 'label' => __( 'Button: Export CSV', 'appointment-booking-system' ), 'default' => 'Export CSV' ),
		'app_search_ph'         => array( 'label' => __( 'Search Placeholder', 'appointment-booking-system' ), 'default' => 'Search name, phone, email, booking ID…' ),
		'app_no_appointments'   => array( 'label' => __( 'Empty Table Message', 'appointment-booking-system' ), 'default' => 'No appointments found.' ),
	),
	'Admin Settings Page Labels' => array(
		'set_page_title'        => array( 'label' => __( 'Page Title', 'appointment-booking-system' ), 'default' => 'Appointment Booking Settings' ),
		'set_sec_general'       => array( 'label' => __( 'Section: General', 'appointment-booking-system' ), 'default' => 'General' ),
		'set_clinic_name'       => array( 'label' => __( 'Field: Clinic Name', 'appointment-booking-system' ), 'default' => 'Clinic Name' ),
		'set_clinic_email'      => array( 'label' => __( 'Field: Clinic Email', 'appointment-booking-system' ), 'default' => 'Clinic Email' ),
		'set_timezone'          => array( 'label' => __( 'Field: Timezone', 'appointment-booking-system' ), 'default' => 'Timezone' ),
		'set_date_format'       => array( 'label' => __( 'Field: Date Format', 'appointment-booking-system' ), 'default' => 'Date Format' ),
		'set_time_format'       => array( 'label' => __( 'Field: Time Format', 'appointment-booking-system' ), 'default' => 'Time Format' ),
		'set_sec_email'         => array( 'label' => __( 'Section: Email Settings', 'appointment-booking-system' ), 'default' => 'Email Settings' ),
		'set_admin_email'       => array( 'label' => __( 'Field: Admin Email', 'appointment-booking-system' ), 'default' => 'Admin Email' ),
		'set_cust_email_sub'    => array( 'label' => __( 'Field: Customer Email Subject', 'appointment-booking-system' ), 'default' => 'Customer Email Subject' ),
		'set_admin_email_sub'   => array( 'label' => __( 'Field: Admin Email Subject', 'appointment-booking-system' ), 'default' => 'Admin Email Subject' ),
		'set_sec_notifications' => array( 'label' => __( 'Section: Notification Settings', 'appointment-booking-system' ), 'default' => 'Notification Settings' ),
		'set_sec_appearance'    => array( 'label' => __( 'Section: Appearance', 'appointment-booking-system' ), 'default' => 'Appearance' ),
		'set_sec_advanced'      => array( 'label' => __( 'Section: Advanced', 'appointment-booking-system' ), 'default' => 'Advanced' ),
		'set_btn_save'          => array( 'label' => __( 'Button: Save Settings', 'appointment-booking-system' ), 'default' => 'Save Settings' ),
	),
);

// Fetch current active values for language
$current_strings = Translation_Service::get_i18n_strings( $active_lang );

// Fetch Audit Logs
$audit_logs = get_option( 'ab_i18n_audit_log', array() );
?>

<div class="wrap ab-wrap">
	<style>
		.ab-translation-hero {
			background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
			color: #ffffff;
			border-radius: 12px;
			padding: 24px 28px;
			margin-top: 15px;
			margin-bottom: 24px;
			box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
			display: flex;
			justify-content: space-between;
			align-items: center;
			flex-wrap: wrap;
			gap: 16px;
		}
		.ab-translation-hero h1 {
			color: #ffffff !important;
			margin: 0 0 6px 0;
			font-size: 22px;
			font-weight: 800;
			letter-spacing: -0.5px;
			display: flex;
			align-items: center;
			gap: 10px;
		}
		.ab-translation-hero p {
			margin: 0;
			color: #94a3b8;
			font-size: 13.5px;
		}
		.ab-lang-tabs {
			display: flex;
			gap: 8px;
			flex-wrap: wrap;
			background: #f1f5f9;
			padding: 6px;
			border-radius: 8px;
			margin-bottom: 24px;
		}
		.ab-lang-tab {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			padding: 8px 16px;
			border-radius: 6px;
			font-weight: 600;
			font-size: 13px;
			color: #475569;
			text-decoration: none;
			transition: all 0.2s ease;
		}
		.ab-lang-tab:hover {
			color: #0f172a;
			background: rgba(255,255,255,0.6);
		}
		.ab-lang-tab.active {
			background: #ffffff;
			color: #2563eb;
			box-shadow: 0 1px 3px rgba(0,0,0,0.1);
		}
		.ab-lang-code-pill {
			background: #e2e8f0;
			color: #334155;
			font-size: 10px;
			padding: 2px 6px;
			border-radius: 4px;
			text-transform: uppercase;
		}
		.ab-lang-tab.active .ab-lang-code-pill {
			background: #dbeafe;
			color: #1d4ed8;
		}
		.ab-group-card {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 10px;
			box-shadow: 0 1px 3px rgba(0,0,0,0.05);
			margin-bottom: 24px;
			overflow: hidden;
		}
		.ab-group-header {
			background: #f8fafc;
			padding: 14px 20px;
			border-bottom: 1px solid #e2e8f0;
			display: flex;
			align-items: center;
			justify-content: space-between;
		}
		.ab-group-header h3 {
			margin: 0;
			font-size: 15px;
			font-weight: 700;
			color: #0f172a;
			display: flex;
			align-items: center;
			gap: 8px;
		}
		.ab-string-row {
			display: flex;
			padding: 16px 20px;
			border-bottom: 1px solid #f1f5f9;
			align-items: flex-start;
			gap: 20px;
			transition: background 0.15s ease;
		}
		.ab-string-row:last-child {
			border-bottom: none;
		}
		.ab-string-row:hover {
			background: #fafafa;
		}
		.ab-string-meta {
			flex: 0 0 320px;
		}
		.ab-string-meta label {
			font-weight: 700;
			font-size: 13.5px;
			color: #1e293b;
			display: block;
		}
		.ab-string-key-tag {
			display: inline-block;
			font-family: monospace;
			font-size: 11px;
			background: #e2e8f0;
			color: #475569;
			padding: 2px 6px;
			border-radius: 4px;
			margin-top: 4px;
		}
		.ab-string-default-preview {
			font-size: 11.5px;
			color: #64748b;
			margin-top: 6px;
			background: #f8fafc;
			border-left: 3px solid #cbd5e1;
			padding: 4px 8px;
			border-radius: 0 4px 4px 0;
		}
		.ab-string-input-wrap {
			flex: 1;
		}
		.ab-string-input-wrap input[type="text"],
		.ab-string-input-wrap textarea {
			width: 100%;
			border: 1px solid #cbd5e1;
			border-radius: 6px;
			padding: 9px 12px;
			font-size: 13.5px;
			color: #0f172a;
			transition: all 0.2s ease;
		}
		.ab-string-input-wrap input[type="text"]:focus,
		.ab-string-input-wrap textarea:focus {
			border-color: #2563eb;
			box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
			outline: none;
		}
		.ab-sticky-save-bar {
			position: sticky;
			bottom: 20px;
			background: #ffffff;
			border: 1px solid #cbd5e1;
			border-radius: 10px;
			padding: 14px 20px;
			box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15);
			display: flex;
			justify-content: space-between;
			align-items: center;
			z-index: 100;
			margin-top: 24px;
		}
	</style>

	<div class="ab-translation-hero">
		<div>
			<h1>
				<span class="dashicons dashicons-translation" style="font-size:24px; width:24px; height:24px; color:#3b82f6;"></span>
				<?php esc_html_e( 'String Translations & Internationalization', 'appointment-booking-system' ); ?>
			</h1>
			<p><?php esc_html_e( 'Customize static text labels across frontend forms, email notifications, and admin submenus for any language.', 'appointment-booking-system' ); ?></p>
		</div>

		<div style="display:flex; align-items:center; gap:12px;">
			<input type="text" id="ab_string_search" onkeyup="abFilterStrings()" placeholder="<?php esc_attr_e( 'Search keys or labels…', 'appointment-booking-system' ); ?>" style="height:36px; padding:0 12px; border-radius:6px; border:1px solid #475569; background:#1e293b; color:#fff; font-size:13px; width:220px;" />
		</div>
	</div>

	<?php include __DIR__ . '/partials/notice.php'; ?>

	<!-- Language Switcher Tabs -->
	<div class="ab-lang-tabs">
		<?php foreach ( $languages as $code => $l ) : ?>
			<?php
			$url    = admin_url( 'admin.php?page=ab-string-translations&tab=strings&lang=' . $code );
			$active = ( $active_lang === $code );
			$name   = ! empty( $l['display_name'] ) ? $l['display_name'] : strtoupper( $code );
			?>
			<a href="<?php echo esc_url( $url ); ?>" class="ab-lang-tab <?php echo $active ? 'active' : ''; ?>">
				<span class="dashicons dashicons-translation" style="font-size:14px; width:14px; height:14px;"></span>
				<?php echo esc_html( $name ); ?>
				<span class="ab-lang-code-pill"><?php echo esc_html( strtoupper( $code ) ); ?></span>
			</a>
		<?php endforeach; ?>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'ab_admin_nonce' ); ?>
		<input type="hidden" name="action" value="ab_save_string_translations" />
		<input type="hidden" name="lang" value="<?php echo esc_attr( $active_lang ); ?>" />

		<?php foreach ( $string_fields as $group_title => $group_fields ) : ?>
			<div class="ab-group-card ab-string-group-card">
				<div class="ab-group-header">
					<h3>
						<span class="dashicons dashicons-category" style="font-size:16px; color:#2563eb;"></span>
						<?php echo esc_html( $group_title ); ?>
					</h3>
					<span style="font-size:11px; background:#e2e8f0; color:#475569; padding:2px 8px; border-radius:10px; font-weight:600;">
						<?php echo esc_html( count( $group_fields ) ); ?> Strings
					</span>
				</div>

				<div>
					<?php foreach ( $group_fields as $key => $meta ) : ?>
						<?php $val = isset( $current_strings[ $key ] ) ? $current_strings[ $key ] : $meta['default']; ?>
						<div class="ab-string-row" data-search-target="<?php echo esc_attr( strtolower( $meta['label'] . ' ' . $key . ' ' . $meta['default'] ) ); ?>">
							<div class="ab-string-meta">
								<label for="string_<?php echo esc_attr( $key ); ?>">
									<?php echo esc_html( $meta['label'] ); ?>
								</label>
								<div class="ab-string-key-tag">
									<?php echo esc_html( $key ); ?>
								</div>
								<div class="ab-string-default-preview">
									<strong>Original Baseline:</strong> <?php echo esc_html( $meta['default'] ); ?>
								</div>
							</div>

							<div class="ab-string-input-wrap">
								<?php if ( strpos( $key, 'success_p' ) !== false || strpos( $key, 'select' ) !== false || strpos( $key, 'body' ) !== false || strpos( $key, 'help' ) !== false ) : ?>
									<textarea id="string_<?php echo esc_attr( $key ); ?>" name="strings[<?php echo esc_attr( $key ); ?>]" rows="2"><?php echo esc_textarea( $val ); ?></textarea>
								<?php else : ?>
									<input type="text" id="string_<?php echo esc_attr( $key ); ?>" name="strings[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $val ); ?>" />
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endforeach; ?>

		<div class="ab-sticky-save-bar">
			<div style="font-size:13px; color:#475569; font-weight:500;">
				Editing Translations for <strong><?php echo esc_html( strtoupper( $active_lang ) ); ?></strong>
			</div>
			<button type="submit" class="button button-primary button-large" style="height:38px; line-height:36px; padding:0 24px; border-radius:6px; font-weight:700;">
				<span class="dashicons dashicons-saved" style="vertical-align:text-top; margin-right:4px;"></span>
				<?php echo esc_html( sprintf( __( 'Save Translations for [%s]', 'appointment-booking-system' ), strtoupper( $active_lang ) ) ); ?>
			</button>
		</div>
	</form>

	<script>
	function abFilterStrings() {
		var input = document.getElementById('ab_string_search');
		var filter = input.value.toLowerCase().trim();
		var rows = document.querySelectorAll('.ab-string-row');

		rows.forEach(function(row) {
			var target = row.getAttribute('data-search-target');
			if (!filter || (target && target.indexOf(filter) > -1)) {
				row.style.display = 'flex';
			} else {
				row.style.display = 'none';
			}
		});

		var cards = document.querySelectorAll('.ab-string-group-card');
		cards.forEach(function(card) {
			var visibleRows = card.querySelectorAll('.ab-string-row[style="display: flex;"], .ab-string-row:not([style*="display: none"])');
			if (visibleRows.length === 0 && filter !== '') {
				card.style.display = 'none';
			} else {
				card.style.display = 'block';
			}
		});
	}
	</script>
</div>
