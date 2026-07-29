<?php
namespace AB\Includes\Models;
use AB\Includes\Language\Translation_Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base model providing generic, safe CRUD operations over a single table.
 * Concrete models only need to declare $table and $fields.
 */
abstract class Base_Model {

	/** @var string Table name without the wp_ prefix, e.g. 'ab_categories'. */
	protected $table = '';

	/** @var \wpdb */
	protected $db;

	public function __construct() {
		global $wpdb;
		$this->db = $wpdb;
	}

	/**
	 * @return string Fully prefixed table name.
	 */
	protected function table() {
		return $this->db->prefix . $this->table;
	}

	/**
	 * @param int $id
	 * @return array|null
	 */
	public function find( $id ) {
		$sql = $this->db->prepare( "SELECT * FROM {$this->table()} WHERE id = %d", $id ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $this->db->get_row( $sql, ARRAY_A );
	}

	/**
	 * @param array $data Column => value pairs.
	 * @return int|false Insert ID or false on failure.
	 */
	public function insert( $data ) {
		$result = $this->db->insert( $this->table(), $data );
		return $result ? $this->db->insert_id : false;
	}

	/**
	 * @param int   $id
	 * @param array $data Column => value pairs.
	 * @return int|false Number of rows updated, or false on failure.
	 */
	public function update( $id, $data ) {
		return $this->db->update( $this->table(), $data, array( 'id' => $id ) );
	}

	/**
	 * @param int $id
	 * @return int|false
	 */
	public function delete( $id ) {
		return $this->db->delete( $this->table(), array( 'id' => $id ) );
	}

	/**
	 * @return int Total row count.
	 */
	public function count_all() {
		return (int) $this->db->get_var( "SELECT COUNT(*) FROM {$this->table()}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
	}


	protected function translate_rows( $object_type, array $rows ) {

		// Only translate on the public frontend or frontend AJAX calls, never on WP Admin Dashboard screens.
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $rows;
		}

		return Translation_Service::translate_records(
			$object_type,
			$rows
		);
	}

	protected function translate_row( $object_type, $row ) {

		if ( empty( $row ) ) {
			return $row;
		}

		// Only translate on the public frontend or frontend AJAX calls, never on WP Admin Dashboard screens.
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $row;
		}

		return Translation_Service::translate_record(
			$object_type,
			$row
		);
	}
}
