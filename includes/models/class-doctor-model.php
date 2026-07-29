<?php
namespace AB\Includes\Models;
use AB\Includes\Language\Translation_Service;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Doctor_Model extends Base_Model {

	protected $table = 'ab_doctors';

	/**
	 * @param array $args {
	 *     @type int    $category_id Filter by category.
	 *     @type string $search      Search by name.
	 *     @type bool   $active_only Only status = 1.
	 * }
	 * @return array
	 */
	public function all( $args = array() ) {
		$defaults = array(
			'category_id' => 0,
			'search'      => '',
			'active_only' => false,
		);
		$args = wp_parse_args( $args, $defaults );

		$pivot = $this->db->prefix . 'ab_doctor_categories';
		$sql   = "SELECT DISTINCT d.* FROM {$this->table()} d";
		$where = array();
		$params = array();

		// Always exclude internal translated duplicate rows so only source items are returned in list queries.
		$where[] = "d.id NOT IN (SELECT translated_object_id FROM {$this->db->prefix}ab_translation_map WHERE object_type = 'doctor' AND translated_object_id != source_object_id)";

		if ( ! empty( $args['category_id'] ) ) {
			$sql   .= " INNER JOIN {$pivot} dc ON dc.doctor_id = d.id";
			$where[] = 'dc.category_id = %d';
			$params[] = (int) $args['category_id'];
		}

		if ( ! empty( $args['active_only'] ) ) {
			$where[] = 'd.status = 1';
		}

		if ( ! empty( $args['search'] ) ) {
			$where[]  = 'd.name LIKE %s';
			$params[] = '%' . $this->db->esc_like( $args['search'] ) . '%';
		}

		if ( $where ) {
			$sql .= ' WHERE ' . implode( ' AND ', $where );
		}
		$sql .= ' ORDER BY d.name ASC';

		if ( $params ) {
			$sql = $this->db->prepare( $sql, $params ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		$rows = $this->db->get_results(
			$sql,
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

		return $this->translate_rows(
			Translation_Service::TYPE_DOCTOR,
			$rows
		);
	}


	/**
	 * @param int $doctor_id
	 * @return array Category IDs assigned to this doctor.
	 */
	public function get_category_ids( $doctor_id ) {
		$pivot = $this->db->prefix . 'ab_doctor_categories';
		$sql   = $this->db->prepare( "SELECT category_id FROM {$pivot} WHERE doctor_id = %d", $doctor_id ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return array_map( 'intval', $this->db->get_col( $sql ) );
	}

	/**
	 * Replace a doctor's category assignments.
	 *
	 * @param int   $doctor_id
	 * @param array $category_ids
	 */
	public function sync_categories( $doctor_id, $category_ids ) {
		$pivot = $this->db->prefix . 'ab_doctor_categories';

		$this->db->delete( $pivot, array( 'doctor_id' => $doctor_id ) );

		foreach ( array_map( 'intval', $category_ids ) as $category_id ) {
			if ( $category_id > 0 ) {
				$this->db->insert(
					$pivot,
					array(
						'doctor_id'   => $doctor_id,
						'category_id' => $category_id,
					)
				);
			}
		}
	}

	/**
	 * @param string $email
	 * @param int    $exclude_id
	 * @return bool
	 */
	public function email_exists( $email, $exclude_id = 0 ) {
		$sql = $this->db->prepare(
			"SELECT COUNT(*) FROM {$this->table()} WHERE email = %s AND id != %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$email,
			$exclude_id
		);
		return (int) $this->db->get_var( $sql ) > 0;
	}

	/**
	 * @param int $doctor_id
	 * @return int
	 */
	public function delete( $doctor_id ) {
		$pivot = $this->db->prefix . 'ab_doctor_categories';
		$this->db->delete( $pivot, array( 'doctor_id' => $doctor_id ) );
		return parent::delete( $doctor_id );
	}

	/**
 * Find a doctor by ID.
 *
 * @param int $id
 * @return array|null
 */
public function find( $id ) {

	$row = parent::find( $id );

	return $this->translate_row(
		Translation_Service::TYPE_DOCTOR,
		$row
	);

}

	/**
	 * Count only source (non-translated) doctors so dashboard counts match the list.
	 *
	 * @return int
	 */
	public function count_all() {
		$sql = "SELECT COUNT(*) FROM {$this->table()}
				WHERE id NOT IN (
					SELECT translated_object_id
					FROM {$this->db->prefix}ab_translation_map
					WHERE object_type = 'doctor'
					AND translated_object_id != source_object_id
				)";
		return (int) $this->db->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
	}
}

