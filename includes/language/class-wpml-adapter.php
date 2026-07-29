<?php
/**
 * WPML Adapter for Appointment Booking
 *
 * Integrates custom database tables with WPML Translation Management.
 * Uses wpml_load_core_tm() to get TranslationManagement instance.
 *
 * Phase 4.3 - WPML Adapter
 *
 * @package    AB
 * @subpackage Language
 * @since      1.0.0
 */

namespace AB\Includes\Language;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPML_Adapter
 */
class WPML_Adapter {

	/**
	 * Open WPML Translation Editor for a new translation.
	 *
	 * @param string $type            Translation type.
	 * @param int    $item_id         Item ID.
	 * @param string $target_language Target language code.
	 */
	public function open_translation_editor( $type, $item_id, $target_language ) {

		$source_language = apply_filters( 'wpml_default_language', 'en' );

		if ( $target_language === $source_language ) {
			wp_die( esc_html__( 'Cannot translate to the same language.', 'appointment-booking-system' ) );
		}

		if ( ! Translation_Service::is_wpml_translation_management_active() ) {
			wp_die(
				esc_html__( 'WPML Translation Management is required to create translations. Please install and activate the WPML Translation Management add-on.', 'appointment-booking-system' )
			);
		}

		$item_data = $this->get_item_data( $type, $item_id );
		if ( empty( $item_data ) ) {
			wp_die( esc_html__( 'Item not found.', 'appointment-booking-system' ) );
		}

		// Create bridge post
		$source_post_id = $this->create_bridge_post( $type, $item_id, $item_data );
		if ( ! $source_post_id || is_wp_error( $source_post_id ) ) {
			wp_die( esc_html__( 'Failed to create bridge post.', 'appointment-booking-system' ) );
		}

		// Register with WPML
		$trid = $this->register_with_wpml( $source_post_id, $source_language );
		if ( ! $trid ) {
			wp_die( esc_html__( 'Failed to register with WPML.', 'appointment-booking-system' ) );
		}

		// Create package and job
		$package = $this->create_translation_package( $source_post_id );
		$job_id  = $this->create_translation_job( $trid, $source_language, $target_language, $package );

		if ( ! $job_id ) {
			wp_die( esc_html__( 'Failed to create WPML translation job.', 'appointment-booking-system' ) );
		}

		// Ensure translation map entry
		if ( ! Translation_Service::has_translation( $type, $item_id, $target_language ) ) {
			Translation_Service::save_translation( $type, $item_id, $item_id, $target_language );
		}

		$this->update_translation_map( $type, $item_id, $target_language, $job_id, $source_post_id );

		// Redirect to WPML Translation Editor / Queue
		$this->redirect_to_queue( $job_id, $target_language );
	}

	/**
	 * Edit existing translation.
	 *
	 * @param string $type            Translation type.
	 * @param int    $item_id         Item ID.
	 * @param string $target_language Target language code.
	 */
	public function edit_translation( $type, $item_id, $target_language ) {
		global $wpdb;

		$job_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT wpml_job_id FROM {$wpdb->prefix}ab_translation_map 
			 WHERE object_type = %s AND source_object_id = %d AND language_code = %s
			 ORDER BY id DESC LIMIT 1",
			$type,
			$item_id,
			$target_language
		) );

		if ( $job_id ) {
			$this->redirect_to_queue( $job_id, $target_language );
		} else {
			$this->open_translation_editor( $type, $item_id, $target_language );
		}
	}

	/**
	 * Create hidden WordPress post to bridge with WPML.
	 *
	 * @param string $type      Item type.
	 * @param int    $item_id   Item ID.
	 * @param array  $item_data Item data.
	 * @return int|WP_Error
	 */
	private function create_bridge_post( $type, $item_id, $item_data ) {

		$existing = get_posts( array(
			'post_type'      => 'ab_translatable',
			'post_status'    => 'private',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array( 'key' => '_ab_item_type', 'value' => $type ),
				array( 'key' => '_ab_item_id', 'value' => $item_id ),
			),
		) );

		if ( ! empty( $existing ) ) {
			$post_id = $existing[0];
			wp_update_post( array(
				'ID'           => $post_id,
				'post_title'   => $item_data['title'] ?? '',
				'post_content' => $this->build_post_content( $type, $item_data ),
			) );
			return $post_id;
		}

		$post_id = wp_insert_post( array(
			'post_type'    => 'ab_translatable',
			'post_status'  => 'private',
			'post_title'   => $item_data['title'] ?? '',
			'post_content' => $this->build_post_content( $type, $item_data ),
			'post_name'    => sanitize_title( 'ab-' . $type . '-' . $item_id ),
		), true );

		if ( $post_id && ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_ab_item_type', $type );
			update_post_meta( $post_id, '_ab_item_id', $item_id );
		}

		return $post_id;
	}

	/**
	 * Build HTML content for bridge post.
	 *
	 * @param string $type      Item type.
	 * @param array  $item_data Item data.
	 * @return string
	 */
	private function build_post_content( $type, $item_data ) {
		$content = '';

		switch ( $type ) {
			case Translation_Service::TYPE_DOCTOR:
				$content .= '<h2>' . esc_html( $item_data['name'] ?? '' ) . '</h2>';
				if ( ! empty( $item_data['specialization'] ) ) {
					$content .= '<p><strong>Specialization:</strong> ' . esc_html( $item_data['specialization'] ) . '</p>';
				}
				if ( ! empty( $item_data['qualification'] ) ) {
					$content .= '<p><strong>Qualification:</strong> ' . esc_html( $item_data['qualification'] ) . '</p>';
				}
				if ( ! empty( $item_data['experience'] ) ) {
					$content .= '<p><strong>Experience:</strong> ' . esc_html( $item_data['experience'] ) . '</p>';
				}
				if ( ! empty( $item_data['bio'] ) ) {
					$content .= wp_kses_post( $item_data['bio'] );
				}
				break;

			case Translation_Service::TYPE_CATEGORY:
				$content .= '<h2>' . esc_html( $item_data['name'] ?? '' ) . '</h2>';
				break;

			case Translation_Service::TYPE_SERVICE:
				$content .= '<h2>' . esc_html( $item_data['name'] ?? '' ) . '</h2>';
				if ( ! empty( $item_data['description'] ) ) {
					$content .= wp_kses_post( $item_data['description'] );
				}
				break;
		}

		return $content;
	}

	/**
	 * Register post with WPML and get TRID.
	 *
	 * @param int    $source_post_id  Post ID.
	 * @param string $source_language Source language.
	 * @return int|false TRID.
	 */
	private function register_with_wpml( $source_post_id, $source_language ) {
		global $wpdb, $sitepress;

		$element_type = 'post_ab_translatable';

		if ( isset( $sitepress ) && is_object( $sitepress ) && method_exists( $sitepress, 'set_element_language_details' ) ) {
			$trid = $sitepress->set_element_language_details(
				$source_post_id,
				$element_type,
				null,
				$source_language,
				null
			);
			if ( $trid ) {
				return $trid;
			}
		}

		$table = $wpdb->prefix . 'icl_translations';

		$existing = $wpdb->get_row( $wpdb->prepare(
			"SELECT translation_id, trid FROM {$table} 
			 WHERE element_id = %d AND element_type = %s",
			$source_post_id,
			$element_type
		) );

		if ( $existing ) {
			$wpdb->update(
				$table,
				array( 'language_code' => $source_language ),
				array( 'translation_id' => $existing->translation_id )
			);
			return $existing->trid;
		}

		$max_trid = $wpdb->get_var( "SELECT MAX(trid) FROM {$table}" );
		$new_trid = (int) $max_trid + 1;

		$wpdb->insert(
			$table,
			array(
				'element_type'         => $element_type,
				'element_id'           => $source_post_id,
				'trid'                 => $new_trid,
				'language_code'        => $source_language,
				'source_language_code' => null,
			),
			array( '%s', '%d', '%d', '%s', null )
		);

		return $new_trid;
	}

	/**
	 * Create translation package for WPML.
	 *
	 * @param int $source_post_id Source post ID.
	 * @return array
	 */
	private function create_translation_package( $source_post_id ) {
		return array(
			'contents' => array(
				'title' => array(
					'translate' => 1,
					'data'      => base64_encode( get_the_title( $source_post_id ) ),
					'format'    => 'base64',
				),
				'body' => array(
					'translate' => 1,
					'data'      => base64_encode( get_post_field( 'post_content', $source_post_id ) ),
					'format'    => 'base64',
				),
			),
		);
	}

	/**
	 * Create translation job using wpml_load_core_tm().
	 *
	 * @param int    $trid            Translation record ID.
	 * @param string $source_language Source language.
	 * @param string $target_language Target language.
	 * @param array  $package         Translation package.
	 * @return int|false Job ID.
	 */
	private function create_translation_job( $trid, $source_language, $target_language, $package ) {
		global $wpdb;

		// Get TranslationManagement instance via wpml_load_core_tm()
		if ( function_exists( 'wpml_load_core_tm' ) ) {
			$tm = wpml_load_core_tm();

			if ( is_object( $tm ) && method_exists( $tm, 'add_translation_job' ) ) {
				$job_id = $tm->add_translation_job(
					$trid,
					get_current_user_id(),
					$package,
					array(
						'source_language_code' => $source_language,
						'language_code'        => $target_language,
					)
				);

				if ( $job_id && ! is_wp_error( $job_id ) ) {
					return $job_id;
				}
			}
		}

		// Fallback: Direct database insertion
		$job_table = $wpdb->prefix . 'icl_translate_job';

		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$job_table}'" ) !== $job_table ) {
			return false;
		}

		$max_job_id  = $wpdb->get_var( "SELECT MAX(job_id) FROM {$job_table}" );
		$next_job_id = (int) $max_job_id + 1;

		$result = $wpdb->insert(
			$job_table,
			array(
				'job_id'               => $next_job_id,
				'rid'                  => $trid,
				'translator_id'        => get_current_user_id(),
				'translated'           => 0,
				'manager_id'           => get_current_user_id(),
				'revision'             => 1,
				'source_language_code' => $source_language,
				'language_code'        => $target_language,
			),
			array( '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s' )
		);

		return $result ? $next_job_id : false;
	}

	/**
	 * Update translation map with WPML data.
	 *
	 * @param string $type            Item type.
	 * @param int    $item_id         Item ID.
	 * @param string $target_language Target language.
	 * @param int    $job_id          WPML job ID.
	 * @param int    $source_post_id  Source post ID.
	 */
	private function update_translation_map( $type, $item_id, $target_language, $job_id, $source_post_id ) {
		global $wpdb;

		$table = $wpdb->prefix . 'ab_translation_map';

		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$table} 
			 WHERE object_type = %s AND source_object_id = %d AND language_code = %s",
			$type,
			$item_id,
			$target_language
		) );

		if ( $existing ) {
			$wpdb->update(
				$table,
				array(
					'wpml_job_id'    => $job_id,
					'source_post_id' => $source_post_id,
					'status'         => 'in_progress',
					'updated_at'     => current_time( 'mysql' ),
				),
				array( 'id' => $existing )
			);
		}
	}

	/**
	 * Redirect to WPML Advanced Translation Editor.
	 *
	 * @param int|null $job_id          WPML job ID.
	 * @param string    $target_language Target language code.
	 */
	private function redirect_to_queue( $job_id = null, $target_language = '' ) {
		if ( $job_id && ! empty( $target_language ) ) {
			$ate_url = sprintf(
				'https://e.ate.wpml.org/dashboard?id=%d&language=%s',
				absint( $job_id ),
				rawurlencode( $target_language )
			);

			wp_safe_redirect( $ate_url );
			exit;
		}

		$queue_url = admin_url( 'admin.php?page=wpml-translation-management/menu/translations-queue.php' );
		if ( $job_id ) {
			$queue_url = add_query_arg( 'job_id', $job_id, $queue_url );
		}

		wp_safe_redirect( $queue_url );
		exit;
	}

	/**
	 * Get item data from custom tables.
	 *
	 * @param string $type    Translation type.
	 * @param int    $item_id Item ID.
	 * @return array
	 */
	public function get_item_data( $type, $item_id ) {
		global $wpdb;

		switch ( $type ) {
			case Translation_Service::TYPE_DOCTOR:
				$row = $wpdb->get_row( $wpdb->prepare(
					"SELECT name, bio, specialization, qualification, experience 
					 FROM {$wpdb->prefix}ab_doctors WHERE id = %d",
					$item_id
				), ARRAY_A );
				if ( $row ) {
					return array(
						'title'          => $row['name'],
						'name'           => $row['name'],
						'bio'            => $row['bio'] ?? '',
						'specialization' => $row['specialization'] ?? '',
						'qualification'  => $row['qualification'] ?? '',
						'experience'     => $row['experience'] ?? '',
					);
				}
				break;

			case Translation_Service::TYPE_CATEGORY:
				$row = $wpdb->get_row( $wpdb->prepare(
					"SELECT name FROM {$wpdb->prefix}ab_categories WHERE id = %d",
					$item_id
				), ARRAY_A );
				if ( $row ) {
					return array(
						'title' => $row['name'],
						'name'  => $row['name'],
					);
				}
				break;

			case Translation_Service::TYPE_SERVICE:
				$row = $wpdb->get_row( $wpdb->prepare(
					"SELECT name, description FROM {$wpdb->prefix}ab_services WHERE id = %d",
					$item_id
				), ARRAY_A );
				if ( $row ) {
					return array(
						'title'       => $row['name'],
						'name'        => $row['name'],
						'description' => $row['description'] ?? '',
					);
				}
				break;
		}

		return array();
	}
}