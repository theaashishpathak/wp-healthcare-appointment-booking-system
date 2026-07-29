<?php
namespace AB\Includes\Models;
use AB\Includes\Language\Translation_Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Service_Model extends Base_Model {

	protected $table = 'ab_services';

	/**
	 * @param array $args {
	 *     @type int    $category_id
	 *     @type string $search
	 *     @type bool   $active_only
	 *     @type string $orderby  'name'|'duration'
	 * }
	 * @return array
	 */
	public function all( $args = array() ) {
		$defaults = array(
			'category_id' => 0,
			'search'      => '',
			'active_only' => false,
			'orderby'     => 'name',
		);
		$args = wp_parse_args( $args, $defaults );

		$sql    = "SELECT * FROM {$this->table()}";
		$where  = array();
		$params = array();

		// Always exclude internal translated duplicate rows so only source items are returned in list queries.
		$where[] = "id NOT IN (SELECT translated_object_id FROM {$this->db->prefix}ab_translation_map WHERE object_type = 'service' AND translated_object_id != source_object_id)";

		if ( ! empty( $args['category_id'] ) ) {
			$where[]  = 'category_id = %d';
			$params[] = (int) $args['category_id'];
		}
		if ( ! empty( $args['active_only'] ) ) {
			$where[] = 'status = 1';
		}
		if ( ! empty( $args['search'] ) ) {
			$where[]  = 'name LIKE %s';
			$params[] = '%' . $this->db->esc_like( $args['search'] ) . '%';
		}
		if ( $where ) {
			$sql .= ' WHERE ' . implode( ' AND ', $where );
		}

		$sql .= ( 'duration' === $args['orderby'] )
			? ' ORDER BY duration_hour ASC, duration_minute ASC'
			: ' ORDER BY name ASC';

		if ( $params ) {
			$sql = $this->db->prepare( $sql, $params ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		$rows = $this->db->get_results(
			$sql,
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

		return $this->translate_rows(
			Translation_Service::TYPE_SERVICE,
			$rows
		);
	}

	/**
	 * @param array $ids Service IDs.
	 * @return array
	 */
	public function get_by_ids( $ids ) {
		$ids = array_filter( array_map( 'intval', (array) $ids ) );
		if ( ! $ids ) {
			return array();
		}
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$sql          = $this->db->prepare( "SELECT * FROM {$this->table()} WHERE id IN ({$placeholders})", $ids ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $this->db->get_results(
    $sql,
    ARRAY_A
);

return $this->translate_rows(
    Translation_Service::TYPE_SERVICE,
    $rows
);
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
		Translation_Service::TYPE_SERVICE,
		$row
	);

}

	/**
	 * Count only source (non-translated) services so dashboard counts match the list.
	 *
	 * @return int
	 */
	public function count_all() {
		$sql = "SELECT COUNT(*) FROM {$this->table()}
				WHERE id NOT IN (
					SELECT translated_object_id
					FROM {$this->db->prefix}ab_translation_map
					WHERE object_type = 'service'
					AND translated_object_id != source_object_id
				)";
		return (int) $this->db->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
	}
}

