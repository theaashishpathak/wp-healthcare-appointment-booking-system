<?php
namespace AB\Includes\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Holiday_Model extends Base_Model {

	protected $table = 'ab_holidays';

	/**
	 * @param int $doctor_id
	 * @return array
	 */
	public function get_for_doctor( $doctor_id ) {
		$sql = $this->db->prepare(
			"SELECT * FROM {$this->table()} WHERE doctor_id = %d OR doctor_id IS NULL ORDER BY holiday_date ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$doctor_id
		);
		return $this->db->get_results( $sql, ARRAY_A );
	}

	/**
	 * Determine whether a given Y-m-d date is a holiday (unavailable) for a doctor,
	 * accounting for one-off dates, date ranges, and recurring weekly holidays —
	 * unless a "special working day" override exists for that same date.
	 *
	 * @param int    $doctor_id
	 * @param string $date Y-m-d.
	 * @return bool
	 */
	public function is_holiday( $doctor_id, $date ) {
		$rows      = $this->get_for_doctor( $doctor_id );
		$timestamp = strtotime( $date );
		$weekday   = (int) gmdate( 'w', $timestamp );

		$is_holiday        = false;
		$special_override  = false;

		foreach ( $rows as $row ) {
			$applies_to_doctor = empty( $row['doctor_id'] ) || (int) $row['doctor_id'] === (int) $doctor_id;
			if ( ! $applies_to_doctor ) {
				continue;
			}

			$matches = false;

			if ( ! empty( $row['recurring_day'] ) || '0' === $row['recurring_day'] ) {
				if ( (int) $row['recurring_day'] === $weekday ) {
					$matches = true;
				}
			} elseif ( ! empty( $row['holiday_date'] ) ) {
				if ( ! empty( $row['end_date'] ) ) {
					$matches = ( $date >= $row['holiday_date'] && $date <= $row['end_date'] );
				} else {
					$matches = ( $date === $row['holiday_date'] );
				}
			}

			if ( $matches ) {
				if ( 'special_working' === $row['type'] ) {
					$special_override = true;
				} else {
					$is_holiday = true;
				}
			}
		}

		return $is_holiday && ! $special_override;
	}
}
