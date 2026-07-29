<?php
namespace AB\Includes\Validation;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lightweight field validator. Accumulates named errors so the frontend
 * / admin JS can highlight individual fields.
 */
class Validator {

	/** @var array<string,string> */
	protected $errors = array();

	/**
	 * @param string $field   Field key.
	 * @param mixed  $value   Value to check.
	 * @param string $message Error message if empty.
	 * @return $this
	 */
	public function required( $field, $value, $message ) {
		if ( '' === trim( (string) $value ) ) {
			$this->errors[ $field ] = $message;
		}
		return $this;
	}

	/**
	 * @param string $field
	 * @param string $value
	 * @param string $message
	 * @return $this
	 */
	public function email( $field, $value, $message ) {
		if ( ! empty( $value ) && ! is_email( $value ) ) {
			$this->errors[ $field ] = $message;
		}
		return $this;
	}

	/**
	 * @param string $field
	 * @param string $value
	 * @param string $message
	 * @return $this
	 */
	public function phone( $field, $value, $message ) {
		if ( ! empty( $value ) && ! preg_match( '/^[0-9+\-\s()]{6,20}$/', $value ) ) {
			$this->errors[ $field ] = $message;
		}
		return $this;
	}

	/**
	 * @param string $field
	 * @param string $date Y-m-d date string.
	 * @param string $message
	 * @return $this
	 */
	public function not_past_date( $field, $date, $message ) {
		if ( ! empty( $date ) && strtotime( $date ) < strtotime( 'today' ) ) {
			$this->errors[ $field ] = $message;
		}
		return $this;
	}

	/**
	 * @return bool
	 */
	public function passes() {
		return empty( $this->errors );
	}

	/**
	 * @return array
	 */
	public function get_errors() {
		return $this->errors;
	}
}
