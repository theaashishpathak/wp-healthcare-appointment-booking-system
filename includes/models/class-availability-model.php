<?php
namespace AB\Includes\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Availability_Model extends Base_Model {

	protected $table = 'ab_availability';

	/**
	 * @param int $doctor_id
	 * @return array Rows keyed by nothing in particular, ordered by day.
	 */
	public function get_for_doctor( $doctor_id ) {
		$sql = $this->db->prepare( "SELECT * FROM {$this->table()} WHERE doctor_id = %d ORDER BY day ASC", $doctor_id ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $this->db->get_results( $sql, ARRAY_A );
	}

	/**
	 * @param int $doctor_id
	 * @param int $day 0 (Sun) - 6 (Sat).
	 * @return array|null
	 */
	public function get_for_doctor_day( $doctor_id, $day ) {
		$sql = $this->db->prepare(
			"SELECT * FROM {$this->table()} WHERE doctor_id = %d AND day = %d LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$doctor_id,
			$day
		);
		return $this->db->get_row( $sql, ARRAY_A );
	}

	/**
	 * Replace all weekly availability rows for a doctor in one go.
	 *
	 * @param int   $doctor_id
	 * @param array $rows Each row: day, start_time, end_time, break_start, break_end, slot_duration.
	 */
	public function save_for_doctor( $doctor_id, $rows ) {
		$this->db->delete( $this->table(), array( 'doctor_id' => $doctor_id ) );

		foreach ( $rows as $row ) {
			$this->db->insert(
				$this->table(),
				array(
					'doctor_id'     => $doctor_id,
					'day'           => (int) $row['day'],
					'start_time'    => $row['start_time'],
					'end_time'      => $row['end_time'],
					'break_start'   => $row['break_start'] ?: null,
					'break_end'     => $row['break_end'] ?: null,
					'slot_duration' => (int) $row['slot_duration'],
				)
			);
		}
	}
}
