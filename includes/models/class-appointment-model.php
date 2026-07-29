<?php
namespace AB\Includes\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Appointment_Model extends Base_Model {

	protected $table = 'ab_appointments';

	/**
	 * @param array $args {
	 *     @type int    $doctor_id
	 *     @type int    $category_id
	 *     @type string $status
	 *     @type string $date       Y-m-d, exact match.
	 *     @type string $search     Matches patient name / phone / email / booking id.
	 *     @type int    $per_page
	 *     @type int    $page
	 * }
	 * @return array { items: array, total: int }
	 */
	public function search( $args = array() ) {
		$defaults = array(
			'doctor_id'   => 0,
			'category_id' => 0,
			'status'      => '',
			'date'        => '',
			'search'      => '',
			'per_page'    => 20,
			'page'        => 1,
		);
		$args = wp_parse_args( $args, $defaults );

		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $args['doctor_id'] ) ) {
			$where[]  = 'doctor_id = %d';
			$params[] = (int) $args['doctor_id'];
		}
		if ( ! empty( $args['category_id'] ) ) {
			$where[]  = 'category_id = %d';
			$params[] = (int) $args['category_id'];
		}
		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$params[] = $args['status'];
		}
		if ( ! empty( $args['date'] ) ) {
			$where[]  = 'appointment_date = %s';
			$params[] = $args['date'];
		}
		if ( ! empty( $args['search'] ) ) {
			$like     = '%' . $this->db->esc_like( $args['search'] ) . '%';
			$where[]  = '(patient_name LIKE %s OR phone LIKE %s OR email LIKE %s OR booking_id LIKE %s)';
			$params   = array_merge( $params, array( $like, $like, $like, $like ) );
		}

		$where_sql = implode( ' AND ', $where );

		$count_sql = "SELECT COUNT(*) FROM {$this->table()} WHERE {$where_sql}";
		$total     = (int) $this->db->get_var( $params ? $this->db->prepare( $count_sql, $params ) : $count_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

		$per_page = max( 1, (int) $args['per_page'] );
		$page     = max( 1, (int) $args['page'] );
		$offset   = ( $page - 1 ) * $per_page;

		$data_sql = "SELECT * FROM {$this->table()} WHERE {$where_sql} ORDER BY appointment_date DESC, appointment_time DESC LIMIT %d OFFSET %d";
		$data_params = array_merge( $params, array( $per_page, $offset ) );
		$items    = $this->db->get_results( $this->db->prepare( $data_sql, $data_params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return array(
			'items' => $items,
			'total' => $total,
		);
	}

	/**
	 * @param int $appointment_id
	 * @return array Service rows attached to this appointment.
	 */
	public function get_services( $appointment_id ) {
		$pivot    = $this->db->prefix . 'ab_appointment_services';
		$services = $this->db->prefix . 'ab_services';
		$sql      = $this->db->prepare(
			"SELECT s.* FROM {$pivot} aps INNER JOIN {$services} s ON s.id = aps.service_id WHERE aps.appointment_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$appointment_id
		);
		return $this->db->get_results( $sql, ARRAY_A );
	}

	/**
	 * Attach a set of service IDs to an appointment.
	 *
	 * @param int   $appointment_id
	 * @param array $service_ids
	 */
	public function attach_services( $appointment_id, $service_ids ) {
		$pivot = $this->db->prefix . 'ab_appointment_services';
		foreach ( array_filter( array_map( 'intval', $service_ids ) ) as $service_id ) {
			$this->db->insert(
				$pivot,
				array(
					'appointment_id' => $appointment_id,
					'service_id'     => $service_id,
				)
			);
		}
	}

	/**
	 * Count how many (non-cancelled) appointments a doctor has at a specific
	 * date + time slot. Since each slot allows only one booking, any count
	 * greater than zero means the slot is already taken. Used to prevent
	 * double booking / race conditions at submission time.
	 *
	 * @param int    $doctor_id
	 * @param string $date Y-m-d.
	 * @param string $time H:i:s.
	 * @return int
	 */
	public function count_bookings_for_slot( $doctor_id, $date, $time ) {
		$sql = $this->db->prepare(
			"SELECT COUNT(*) FROM {$this->table()} WHERE doctor_id = %d AND appointment_date = %s AND appointment_time = %s AND status != 'cancelled'", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$doctor_id,
			$date,
			$time
		);
		return (int) $this->db->get_var( $sql );
	}

	/**
	 * Get every booked time (grouped by count) for a doctor on a given date.
	 * Used by the slot-availability AJAX endpoint.
	 *
	 * @param int    $doctor_id
	 * @param string $date Y-m-d.
	 * @return array time => booked_count
	 */
	public function get_booked_counts_for_date( $doctor_id, $date ) {
		$sql = $this->db->prepare(
			"SELECT appointment_time, COUNT(*) as cnt FROM {$this->table()} WHERE doctor_id = %d AND appointment_date = %s AND status != 'cancelled' GROUP BY appointment_time", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$doctor_id,
			$date
		);
		$rows   = $this->db->get_results( $sql, ARRAY_A );
		$result = array();
		foreach ( $rows as $row ) {
			$result[ $row['appointment_time'] ] = (int) $row['cnt'];
		}
		return $result;
	}

	/**
	 * Dashboard statistic counts.
	 *
	 * @return array
	 */
	public function get_stats() {
		$today = current_time( 'Y-m-d' );

		$stats = array(
			'today'     => (int) $this->db->get_var( $this->db->prepare( "SELECT COUNT(*) FROM {$this->table()} WHERE appointment_date = %s", $today ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'upcoming'  => (int) $this->db->get_var( $this->db->prepare( "SELECT COUNT(*) FROM {$this->table()} WHERE appointment_date > %s AND status != 'cancelled'", $today ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'pending'   => (int) $this->db->get_var( "SELECT COUNT(*) FROM {$this->table()} WHERE status = 'pending'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
			'confirmed' => (int) $this->db->get_var( "SELECT COUNT(*) FROM {$this->table()} WHERE status = 'confirmed'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
			'cancelled' => (int) $this->db->get_var( "SELECT COUNT(*) FROM {$this->table()} WHERE status = 'cancelled'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
		);

		return $stats;
	}

	/**
	 * @param int $limit
	 * @return array
	 */
	public function get_recent( $limit = 8 ) {
		$sql = $this->db->prepare( "SELECT * FROM {$this->table()} ORDER BY created_at DESC LIMIT %d", $limit ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $this->db->get_results( $sql, ARRAY_A );
	}

	/**
	 * @param int $limit
	 * @return array Upcoming (today & future, non-cancelled) appointments.
	 */
	public function get_upcoming_schedule( $limit = 8 ) {
		$today = current_time( 'Y-m-d' );
		$sql   = $this->db->prepare(
			"SELECT * FROM {$this->table()} WHERE appointment_date >= %s AND status != 'cancelled' ORDER BY appointment_date ASC, appointment_time ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$today,
			$limit
		);
		return $this->db->get_results( $sql, ARRAY_A );
	}
}
