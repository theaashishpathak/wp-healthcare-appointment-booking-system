<?php
namespace AB\Includes\Models;
use AB\Includes\Language\Translation_Service;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Category_Model extends Base_Model {

	protected $table = 'ab_categories';

	/**
	 * @param bool $active_only Only return status = 1 categories.
	 * @return array
	 */
	public function all( $active_only = false ) {
		$where_parts = array();

		// Always exclude internal translated duplicate rows so only source items are returned in list queries.
		$where_parts[] = "id NOT IN (SELECT translated_object_id FROM {$this->db->prefix}ab_translation_map WHERE object_type = 'category' AND translated_object_id != source_object_id)";

		if ( $active_only ) {
			$where_parts[] = 'status = 1';
		}

		$sql = "SELECT * FROM {$this->table()}";
		if ( $where_parts ) {
			$sql .= ' WHERE ' . implode( ' AND ', $where_parts );
		}
		$sql .= ' ORDER BY display_order ASC, name ASC';

		$rows = $this->db->get_results(
			$sql,
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

		return $this->translate_rows(
			Translation_Service::TYPE_CATEGORY,
			$rows
		);
	}

	/**
	 * @param string $slug
	 * @param int    $exclude_id Row ID to ignore (for update checks).
	 * @return bool
	 */
	public function slug_exists( $slug, $exclude_id = 0 ) {
		$sql = $this->db->prepare(
			"SELECT COUNT(*) FROM {$this->table()} WHERE slug = %s AND id != %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$slug,
			$exclude_id
		);
		return (int) $this->db->get_var( $sql ) > 0;
	}

	/**
	 * @param string $name
	 * @param int    $exclude_id
	 * @return bool
	 */
	public function name_exists( $name, $exclude_id = 0 ) {
		$sql = $this->db->prepare(
			"SELECT COUNT(*) FROM {$this->table()} WHERE name = %s AND id != %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$name,
			$exclude_id
		);
		return (int) $this->db->get_var( $sql ) > 0;
	}


	/**
 * Find a category by ID.
 *
 * @param int $id
 * @return array|null
 */
public function find( $id ) {

	$row = parent::find( $id );

	return $this->translate_row(
		Translation_Service::TYPE_CATEGORY,
		$row
	);

}

	/**
	 * Count only source (non-translated) categories so dashboard counts match the list.
	 *
	 * @return int
	 */
	public function count_all() {
		$sql = "SELECT COUNT(*) FROM {$this->table()}
				WHERE id NOT IN (
					SELECT translated_object_id
					FROM {$this->db->prefix}ab_translation_map
					WHERE object_type = 'category'
					AND translated_object_id != source_object_id
				)";
		return (int) $this->db->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	/**
	 * Get categories assigned to a specific doctor.
	 *
	 * @param int  $doctor_id
	 * @param bool $active_only
	 * @return array
	 */
	public function get_categories_by_doctor( $doctor_id, $active_only = true ) {
		$doctor_model = new Doctor_Model();
		$category_ids = $doctor_model->get_category_ids( $doctor_id );

		if ( empty( $category_ids ) ) {
			return $this->all( $active_only );
		}

		$pivot = $this->db->prefix . 'ab_doctor_categories';
		$sql   = $this->db->prepare(
			"SELECT DISTINCT c.* FROM {$this->table()} c
			 INNER JOIN {$pivot} dc ON dc.category_id = c.id
			 WHERE dc.doctor_id = %d
			 AND c.id NOT IN (
				 SELECT translated_object_id FROM {$this->db->prefix}ab_translation_map
				 WHERE object_type = 'category' AND translated_object_id != source_object_id
			 )",
			$doctor_id
		);

		if ( $active_only ) {
			$sql .= ' AND c.status = 1';
		}

		$sql .= ' ORDER BY c.display_order ASC, c.name ASC';

		$rows = $this->db->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( empty( $rows ) ) {
			return $this->all( $active_only );
		}

		return $this->translate_rows(
			Translation_Service::TYPE_CATEGORY,
			$rows
		);
	}
}

