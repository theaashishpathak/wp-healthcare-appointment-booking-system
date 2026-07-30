<?php
namespace AB\Frontend\Ajax;

use AB\Includes\Security\Security;
use AB\Includes\Validation\Validator;
use AB\Includes\Models\Category_Model;
use AB\Includes\Models\Doctor_Model;
use AB\Includes\Models\Service_Model;
use AB\Includes\Models\Availability_Model;
use AB\Includes\Models\Holiday_Model;
use AB\Includes\Models\Appointment_Model;
use AB\Includes\Email\Email;
use AB\Includes\Language\Translation_Service;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles every AJAX call made by the public multi-step booking wizard.
 * All handlers are registered for both logged-in and logged-out users
 * since booking is a public-facing feature.
 */
class Frontend_Ajax {

	public function __construct() {
		// Switch language context if passed in AJAX request
		if ( wp_doing_ajax() && isset( $_POST['lang'] ) ) {
			$lang = sanitize_text_field( wp_unslash( $_POST['lang'] ) );
			do_action( 'wpml_switch_language', $lang );
			global $sitepress;
			if ( isset( $sitepress ) && is_object( $sitepress ) && method_exists( $sitepress, 'switch_lang' ) ) {
				$sitepress->switch_lang( $lang );
			}
		}

		$actions = array(
			'ab_get_doctors'          => 'get_doctors',
			'ab_get_services'         => 'get_services',
			'ab_get_available_dates'  => 'get_available_dates',
			'ab_get_time_slots'       => 'get_time_slots',
			'ab_submit_booking'       => 'submit_booking',
		);

		foreach ( $actions as $action => $method ) {
			add_action( 'wp_ajax_' . $action, array( $this, $method ) );
			add_action( 'wp_ajax_nopriv_' . $action, array( $this, $method ) );
		}
	}

	/**
	 * Step 2: doctors belonging to the selected category.
	 */
	public function get_doctors() {
		Security::verify_frontend_request();

		$category_id = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;
		if ( ! $category_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid category.', 'appointment-booking-system' ) ) );
		}

		// Resolve translated category ID back to the source ID.
		// The visitor may have selected a translated category card (e.g. Spanish ID=5),
		// but doctors are linked to the source category ID (e.g. English ID=2).
		$category_id = Translation_Service::get_source_id( Translation_Service::TYPE_CATEGORY, $category_id );

		$doctors = ( new Doctor_Model() )->all(
			array(
				'category_id' => $category_id,
				'active_only' => true,
			)
		);

		$data = array_map(
			function ( $doc ) {
				return array(
					'id'             => (int) $doc['id'],
					'name'           => $doc['name'],
					'image'          => $doc['image'],
					'qualification'  => $doc['qualification'],
					'experience'     => $doc['experience'],
					'specialization' => $doc['specialization'],
				);
			},
			$doctors
		);

		wp_send_json_success( array( 'doctors' => $data ) );
	}

	/**
	 * Step 3: services belonging to the selected category.
	 */
	public function get_services() {
		Security::verify_frontend_request();

		$category_id = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;
		if ( ! $category_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid category.', 'appointment-booking-system' ) ) );
		}

		// Resolve translated category ID back to the source ID.
		// Services store category_id using the source (default-language) row ID.
		$category_id = Translation_Service::get_source_id( Translation_Service::TYPE_CATEGORY, $category_id );

		$services = ( new Service_Model() )->all(
			array(
				'category_id' => $category_id,
				'active_only' => true,
			)
		);

		$data = array_map(
			function ( $svc ) {
				return array(
					'id'       => (int) $svc['id'],
					'name'     => $svc['name'],
					'duration' => ab_format_duration( $svc['duration_hour'], $svc['duration_minute'] ),
					'minutes'  => ab_duration_to_minutes( $svc['duration_hour'], $svc['duration_minute'] ),
				);
			},
			$services
		);

		wp_send_json_success( array( 'services' => $data ) );
	}

	/**
	 * Step 4: which dates in a given month are bookable for a doctor.
	 * Accounts for: past dates, days with no configured availability,
	 * doctor holidays (incl. recurring & ranges), and fully booked days.
	 */
	public function get_available_dates() {
		Security::verify_frontend_request();

		$doctor_id = isset( $_POST['doctor_id'] ) ? absint( $_POST['doctor_id'] ) : 0;
		$year      = isset( $_POST['year'] ) ? absint( $_POST['year'] ) : (int) current_time( 'Y' );
		$month     = isset( $_POST['month'] ) ? absint( $_POST['month'] ) : (int) current_time( 'n' );

		if ( ! $doctor_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid doctor.', 'appointment-booking-system' ) ) );
		}

		$doctor_id = Translation_Service::get_source_id( Translation_Service::TYPE_DOCTOR, $doctor_id );


		$availability_model = new Availability_Model();
		$holiday_model       = new Holiday_Model();
		$appointment_model   = new Appointment_Model();

		$availability_rows = $availability_model->get_for_doctor( $doctor_id );
		$by_day            = array();
		foreach ( $availability_rows as $row ) {
			$by_day[ (int) $row['day'] ] = $row;
		}

		$days_in_month = (int) gmdate( 't', mktime( 0, 0, 0, $month, 1, $year ) );
		$today         = current_time( 'Y-m-d' );

		$available_dates = array();

		for ( $d = 1; $d <= $days_in_month; $d++ ) {
			$date = sprintf( '%04d-%02d-%02d', $year, $month, $d );

			if ( $date < $today ) {
				continue; // Past date.
			}

			$weekday = (int) gmdate( 'w', strtotime( $date ) );
			if ( ! isset( $by_day[ $weekday ] ) ) {
				continue; // Doctor doesn't work this weekday.
			}

			if ( $holiday_model->is_holiday( $doctor_id, $date ) ) {
				continue; // Holiday.
			}

			$slots     = $this->generate_slots( $by_day[ $weekday ], $date, $doctor_id, $appointment_model );
			$has_open  = false;
			foreach ( $slots as $slot ) {
				if ( empty( $slot['booked'] ) ) {
					$has_open = true;
					break;
				}
			}
			if ( ! $has_open ) {
				continue; // Fully booked or no valid slots left today.
			}

			$available_dates[] = $date;
		}

		wp_send_json_success( array( 'available_dates' => $available_dates ) );
	}

	/**
	 * Step 5: the full list of time slots for a specific doctor + date,
	 * each flagged as available or already booked. Booked slots are shown
	 * (not hidden) so the customer can see the schedule, but cannot be
	 * selected — each slot allows exactly one appointment.
	 */
	public function get_time_slots() {
		Security::verify_frontend_request();

		$doctor_id = isset( $_POST['doctor_id'] ) ? absint( $_POST['doctor_id'] ) : 0;
		$date      = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';

		if ( ! $doctor_id || ! $date || strtotime( $date ) < strtotime( current_time( 'Y-m-d' ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid doctor or date.', 'appointment-booking-system' ) ) );
		}

		$doctor_id = Translation_Service::get_source_id( Translation_Service::TYPE_DOCTOR, $doctor_id );


		$holiday_model = new Holiday_Model();
		if ( $holiday_model->is_holiday( $doctor_id, $date ) ) {
			wp_send_json_success( array( 'slots' => array() ) );
		}

		$availability_model = new Availability_Model();
		$weekday             = (int) gmdate( 'w', strtotime( $date ) );
		$row                  = $availability_model->get_for_doctor_day( $doctor_id, $weekday );

		if ( ! $row ) {
			wp_send_json_success( array( 'slots' => array() ) );
		}

		$appointment_model = new Appointment_Model();
		$slots              = $this->generate_slots( $row, $date, $doctor_id, $appointment_model );

		wp_send_json_success( array( 'slots' => $slots ) );
	}

	/**
	 * Build the full list of HH:MM time slots for a doctor's availability row
	 * on a specific date, excluding the break window and (for today) times
	 * that have already passed. Each slot allows exactly one appointment, so
	 * every slot is returned with its booked/available status rather than
	 * being hidden once taken — the frontend shows both states.
	 *
	 * @param array              $availability_row
	 * @param string             $date
	 * @param int                $doctor_id
	 * @param Appointment_Model  $appointment_model
	 * @return array List of ['time' => 'HH:MM:SS', 'label' => '09:00 AM', 'booked' => bool]
	 */
	protected function generate_slots( $availability_row, $date, $doctor_id, $appointment_model ) {
		$slots         = array();
		$interval      = max( 5, (int) $availability_row['slot_duration'] );
		$start         = strtotime( $date . ' ' . $availability_row['start_time'] );
		$end           = strtotime( $date . ' ' . $availability_row['end_time'] );
		$break_start   = ! empty( $availability_row['break_start'] ) ? strtotime( $date . ' ' . $availability_row['break_start'] ) : null;
		$break_end     = ! empty( $availability_row['break_end'] ) ? strtotime( $date . ' ' . $availability_row['break_end'] ) : null;
		$booked_counts = $appointment_model->get_booked_counts_for_date( $doctor_id, $date );
		$is_today      = ( $date === current_time( 'Y-m-d' ) );
		$now           = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested

		if ( ! $start || ! $end || $start >= $end ) {
			return $slots;
		}

		for ( $ts = $start; ( $ts + ( $interval * 60 ) ) <= $end; $ts += ( $interval * 60 ) ) {
			if ( $break_start && $break_end && $ts >= $break_start && $ts < $break_end ) {
				continue; // Inside break window.
			}
			if ( $is_today && $ts <= $now ) {
				continue; // Already passed today.
			}

			$time_key = gmdate( 'H:i:s', $ts );

			$slots[] = array(
				'time'   => $time_key,
				'label'  => date_i18n( ab_get_setting( 'time_format', 'h:i A' ), $ts ),
				'booked' => ! empty( $booked_counts[ $time_key ] ),
			);
		}

		return $slots;
	}

	/**
	 * Step 7 -> 8: validate and persist the booking, then send emails.
	 */
	public function submit_booking() {
		Security::verify_frontend_request();
		
	
    // Temporary debugging
    // ini_set('display_errors', 1);
    // ini_set('display_startup_errors', 1);
    // error_reporting(E_ALL);
    


		// Honeypot spam check.
		$honeypot = isset( $_POST['ab_website'] ) ? sanitize_text_field( wp_unslash( $_POST['ab_website'] ) ) : '';
		if ( Security::is_spam( $honeypot ) ) {
			wp_send_json_error( array( 'message' => __( 'Submission blocked.', 'appointment-booking-system' ) ) );
		}

		// IP Rate Limiting (max 5 booking submissions per IP per 5 minutes)
		if ( ! Security::check_rate_limit( 'submit_booking', 5, 300 ) ) {
			wp_send_json_error( array( 'message' => __( 'Too many booking attempts. Please wait a few minutes and try again.', 'appointment-booking-system' ) ), 429 );
		}

		$category_id  = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;
		$doctor_id    = isset( $_POST['doctor_id'] ) ? absint( $_POST['doctor_id'] ) : 0;
		$service_ids  = isset( $_POST['service_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['service_ids'] ) ) : array();
		$date         = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';
		$time         = isset( $_POST['time'] ) ? sanitize_text_field( wp_unslash( $_POST['time'] ) ) : '';
		$first_name   = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
		$last_name    = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
		$email        = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$phone        = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$country_code = isset( $_POST['country_code'] ) ? sanitize_text_field( wp_unslash( $_POST['country_code'] ) ) : '';
		$message      = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

		// Resolve IDs to source (original default-language) IDs for storage and availability lookups.
		$category_id = Translation_Service::get_source_id( Translation_Service::TYPE_CATEGORY, $category_id );
		$doctor_id   = Translation_Service::get_source_id( Translation_Service::TYPE_DOCTOR, $doctor_id );
		$service_ids = array_map( function( $sid ) {
			return Translation_Service::get_source_id( Translation_Service::TYPE_SERVICE, $sid );
		}, $service_ids );


		$validator = new Validator();
		$validator
			->required( 'category_id', $category_id, __( 'Treatment category is required.', 'appointment-booking-system' ) )
			->required( 'doctor_id', $doctor_id, __( 'Doctor is required.', 'appointment-booking-system' ) )
			->required( 'date', $date, __( 'Appointment date is required.', 'appointment-booking-system' ) )
			->required( 'time', $time, __( 'Appointment time is required.', 'appointment-booking-system' ) )
			->required( 'first_name', $first_name, __( 'First name is required.', 'appointment-booking-system' ) )
			->max_length( 'first_name', $first_name, 50, __( 'First name cannot exceed 50 characters.', 'appointment-booking-system' ) )
			->required( 'last_name', $last_name, __( 'Last name is required.', 'appointment-booking-system' ) )
			->max_length( 'last_name', $last_name, 50, __( 'Last name cannot exceed 50 characters.', 'appointment-booking-system' ) )
			->required( 'email', $email, __( 'Email is required.', 'appointment-booking-system' ) )
			->email( 'email', $email, __( 'Please enter a valid email address.', 'appointment-booking-system' ) )
			->max_length( 'email', $email, 100, __( 'Email address is too long.', 'appointment-booking-system' ) )
			->required( 'phone', $phone, __( 'Phone is required.', 'appointment-booking-system' ) )
			->phone( 'phone', $phone, __( 'Please enter a valid phone number.', 'appointment-booking-system' ) )
			->max_length( 'phone', $phone, 30, __( 'Phone number is too long.', 'appointment-booking-system' ) )
			->max_length( 'message', $message, 1000, __( 'Message cannot exceed 1000 characters.', 'appointment-booking-system' ) )
			->not_past_date( 'date', $date, __( 'Appointment date cannot be in the past.', 'appointment-booking-system' ) );

		if ( empty( $service_ids ) ) {
			$validator->required( 'service_ids', '', __( 'Please select at least one service.', 'appointment-booking-system' ) );
		}

		if ( ! $validator->passes() ) {
			wp_send_json_error( array( 'message' => __( 'Please correct the errors and try again.', 'appointment-booking-system' ), 'errors' => $validator->get_errors() ) );
		}

		$appointment_model = new Appointment_Model();

		// Re-check slot availability at submission time to prevent double booking / race conditions.
		$availability_model = new Availability_Model();
		$holiday_model       = new Holiday_Model();
		$weekday             = (int) gmdate( 'w', strtotime( $date ) );
		$availability_row    = $availability_model->get_for_doctor_day( $doctor_id, $weekday );

		if ( ! $availability_row || $holiday_model->is_holiday( $doctor_id, $date ) ) {
			wp_send_json_error( array( 'message' => __( 'Sorry, this doctor is not available on the selected date. Please choose another date.', 'appointment-booking-system' ) ) );
		}

		$booked_count = $appointment_model->count_bookings_for_slot( $doctor_id, $date, $time );

		if ( $booked_count > 0 ) {
			wp_send_json_error( array( 'message' => __( 'Sorry, this time slot was just booked by someone else. Please choose another time.', 'appointment-booking-system' ) ) );
		}

		$service_model = new Service_Model();
		$services      = $service_model->get_by_ids( $service_ids );
		$total_minutes = 0;
		foreach ( $services as $service ) {
			$total_minutes += ab_duration_to_minutes( $service['duration_hour'], $service['duration_minute'] );
		}

		$booking_id = ab_generate_booking_id();

		$appointment_id = $appointment_model->insert(
			array(
				'booking_id'       => $booking_id,
				'category_id'      => $category_id,
				'doctor_id'        => $doctor_id,
				'appointment_date' => $date,
				'appointment_time' => $time,
				'total_duration'   => $total_minutes,
				'patient_name'     => trim( $first_name . ' ' . $last_name ),
				'email'            => $email,
				'phone'            => $phone,
				'country_code'     => $country_code,
				'message'          => $message,
				'status'           => 'pending',
			)
		);

		if ( ! $appointment_id ) {
			wp_send_json_error( array( 'message' => __( 'We could not save your appointment due to a database error. Please try again.', 'appointment-booking-system' ) ) );
		}

		$appointment_model->attach_services( $appointment_id, $service_ids );

		$doctor   = ( new Doctor_Model() )->find( $doctor_id );
		$category = ( new Category_Model() )->find( $category_id );
		$appointment = $appointment_model->find( $appointment_id );

		$current_lang = Translation_Service::get_current_language();

		// Log appointment creation
		\AB\Includes\Logger::log(
			'appointment',
			'created',
			'New appointment booked: ' . $booking_id . ' by ' . trim( $first_name . ' ' . $last_name ),
			array(
				'booking_id'   => $booking_id,
				'patient_name' => trim( $first_name . ' ' . $last_name ),
				'email'        => $email,
				'date'         => $date,
				'time'         => $time,
			)
		);

		// Email failures must never break the booking confirmation for the patient.
		try {
			Email::send_booking_emails(
				$appointment,
				$services,
				$doctor,
				$category,
				$current_lang
			);
		} catch ( \Throwable $e ) {
			error_log( 'EMAIL ERROR: ' . $e->getMessage() );
		}

		wp_send_json_success(
			array(
				'booking_id' => $booking_id,
				'message'    => __( 'Appointment booked successfully.', 'appointment-booking-system' ),
			)
		);
	}
}
