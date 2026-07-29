<?php
namespace AB\Includes\Language;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Translation Service
 *
 * Handles all WPML translation lookups for custom database tables.
 *
 * Supported object types:
 * - doctor
 * - category
 * - service
 */
class Translation_Service {

	/**
	 * Translation table.
	 *
	 * @var string
	 */
	private static $table = 'ab_translation_map';

	// Object type constants
	public const TYPE_DOCTOR   = 'doctor';
	public const TYPE_CATEGORY = 'category';
	public const TYPE_SERVICE  = 'service';


	/**
	 * Get translation table.
	 *
	 * @return string
	 */
	private static function table() {
		global $wpdb;

		return $wpdb->prefix . self::$table;
	}

	/**
	 * Get all active languages.
	 *
	 * Returns an array indexed by language code.
	 *
	 * Example:
	 * [
	 *     'en' => [
	 *         'code' => 'en',
	 *         'display_name' => 'English',
	 *         'native_name' => 'English',
	 *         'default_locale' => 'en_US',
	 *         'is_default' => true,
	 *     ],
	 *     'de' => [
	 *         'code' => 'de',
	 *         'display_name' => 'German',
	 *         'native_name' => 'Deutsch',
	 *         'default_locale' => 'de_DE',
	 *         'is_default' => false,
	 *     ]
	 * ]
	 *
	 * @return array
	 */
	public static function get_languages() {

		if ( ! self::is_wpml_active() ) {
			$languages = array(
				'en' => array(
					'code'           => 'en',
					'display_name'   => 'English',
					'native_name'    => 'English',
					'default_locale' => 'en_US',
					'is_default'     => true,
				),
			);

			// Detect current WordPress site locale (e.g. pt_PT, de_DE, es_ES, it_IT, fr_FR)
			$site_locale = get_locale();
			if ( ! empty( $site_locale ) ) {
				$lang_code = strtolower( substr( $site_locale, 0, 2 ) );
				if ( 'en' !== $lang_code && empty( $languages[ $lang_code ] ) ) {
					require_once ABSPATH . 'wp-admin/includes/translation-install.php';
					$translations = wp_get_available_translations();
					$name = ! empty( $translations[ $site_locale ]['native_name'] ) ? $translations[ $site_locale ]['native_name'] : strtoupper( $lang_code );
					$languages[ $lang_code ] = array(
						'code'           => $lang_code,
						'display_name'   => $name,
						'native_name'    => $name,
						'default_locale' => $site_locale,
						'is_default'     => false,
					);
				}
			}

			// Include any extra installed WordPress site languages
			$avail_locales = get_available_languages();
			if ( is_array( $avail_locales ) ) {
				require_once ABSPATH . 'wp-admin/includes/translation-install.php';
				$translations = wp_get_available_translations();
				foreach ( $avail_locales as $loc ) {
					$c = strtolower( substr( $loc, 0, 2 ) );
					if ( ! empty( $c ) && empty( $languages[ $c ] ) ) {
						$n = ! empty( $translations[ $loc ]['native_name'] ) ? $translations[ $loc ]['native_name'] : strtoupper( $c );
						$languages[ $c ] = array(
							'code'           => $c,
							'display_name'   => $n,
							'native_name'    => $n,
							'default_locale' => $loc,
							'is_default'     => false,
						);
					}
				}
			}

			return $languages;
		}

		$languages = apply_filters(
			'wpml_active_languages',
			null,
			array(
				'skip_missing' => 0,
				'orderby'      => 'code',
			)
		);

		if ( empty( $languages ) || ! is_array( $languages ) ) {
			return array();
		}

		$default = self::get_default_language();

		foreach ( $languages as $code => &$language ) {

			$language['is_default'] = ( $code === $default );

			if ( empty( $language['code'] ) ) {
				$language['code'] = $code;
			}

			if ( empty( $language['display_name'] ) ) {
				$language['display_name'] = strtoupper( $code );
			}

			if ( empty( $language['native_name'] ) ) {
				$language['native_name'] = $language['display_name'];
			}

		}

		return $languages;
	}

	/**
	 * Get language details by language code.
	 *
	 * Example:
	 *
	 * Translation_Service::get_language_name('de');
	 *
	 * Returns:
	 * [
	 *     'code'           => 'de',
	 *     'display_name'   => 'German',
	 *     'native_name'    => 'Deutsch',
	 *     'default_locale' => 'de_DE',
	 *     'is_default'     => false,
	 * ]
	 *
	 * Returns null if the language does not exist.
	 *
	 * @param string $code Language code.
	 *
	 * @return array|null
	 */
	public static function get_language_name( $code ) {

		$code = sanitize_key( $code );

		if ( empty( $code ) ) {
			return null;
		}

		$languages = self::get_languages();

		if ( isset( $languages[ $code ] ) ) {
			return $languages[ $code ];
		}

		foreach ( $languages as $language ) {

			if (
				isset( $language['code'] ) &&
				$language['code'] === $code
			) {
				return $language;
			}

		}

		return null;
	}

	/**
	 * Returns true if WPML is active.
	 *
	 * @return bool
	 */
	public static function is_wpml_active() {
		return has_filter( 'wpml_current_language' );
	}

	/**
	 * Returns true if WPML Translation Management is active.
	 *
	 * @return bool
	 */
	public static function is_wpml_translation_management_active() {
		return function_exists( 'wpml_load_core_tm' );
	}

	/**
	 * Get current language.
	 *
	 * @return string
	 */
	public static function get_current_language() {

		if ( wp_doing_ajax() && ! empty( $_REQUEST['lang'] ) ) {
			return sanitize_text_field( wp_unslash( $_REQUEST['lang'] ) );
		}

		if ( ! self::is_wpml_active() ) {
			return 'en';
		}

		$lang = apply_filters( 'wpml_current_language', null );

		return ( ! empty( $lang ) && 'all' !== $lang ) ? $lang : 'en';
	}

	/**
	 * Get merged i18n static strings for a given language code.
	 * Combines built-in defaults from i18n-strings.php with custom admin overrides.
	 *
	 * @param string $lang Language code (e.g. 'de', 'es'). Defaults to active language.
	 * @return array
	 */
	public static function get_i18n_strings( $lang = '' ) {
		if ( empty( $lang ) ) {
			$lang = self::get_current_language();
		}

		$file_path = AB_PLUGIN_DIR . 'includes/language/i18n-strings.php';
		$base_map  = file_exists( $file_path ) ? require $file_path : array();
		$lang_base = ! empty( $base_map[ $lang ] ) ? $base_map[ $lang ] : ( ! empty( $base_map['en'] ) ? $base_map['en'] : array() );

		// Custom overrides saved in database by admin
		$custom_all  = get_option( 'ab_custom_i18n_strings', array() );
		$custom_lang = ! empty( $custom_all[ $lang ] ) && is_array( $custom_all[ $lang ] ) ? $custom_all[ $lang ] : array();

		return array_merge( $lang_base, $custom_lang );
	}

	/**
	 * Get default language.
	 *
	 * @return string
	 */
	public static function get_default_language() {

		if ( ! self::is_wpml_active() ) {
			return 'en';
		}

		$lang = apply_filters( 'wpml_default_language', null );

		return ! empty( $lang ) ? $lang : 'en';
	}

	/**
	 * Get object table by type.
	 *
	 * @param string $object_type
	 *
	 * @return string|false
	 */
	private static function get_object_table( $object_type ) {

		global $wpdb;

		$tables = array(
			self::TYPE_DOCTOR   => $wpdb->prefix . 'ab_doctors',
			self::TYPE_CATEGORY => $wpdb->prefix . 'ab_categories',
			self::TYPE_SERVICE  => $wpdb->prefix . 'ab_services',
		);

		return isset( $tables[ $object_type ] ) ? $tables[ $object_type ] : false;
	}

	/**
	 * Translate object ID.
	 *
	 * Returns translated object ID if available.
	 * Otherwise returns original object ID.
	 *
	 * @param string $object_type
	 * @param int    $object_id
	 * @param string $language
	 *
	 * @return int
	 */
	public static function translate_id( $object_type, $object_id, $language = '' ) {

		global $wpdb;

		$object_id = absint( $object_id );

		if ( ! $object_id ) {
			return 0;
		}

		// Never translate IDs on WP Admin Dashboard screens — always use source/original.
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $object_id;
		}

		if ( empty( $language ) ) {
			$language = self::get_current_language();
		}

		$default = self::get_default_language();

		if ( $language === $default ) {
			return $object_id;
		}

		$sql = $wpdb->prepare(
			"SELECT translated_object_id
			FROM " . self::table() . "
			WHERE object_type=%s
			AND source_object_id=%d
			AND language_code=%s
			LIMIT 1",
			$object_type,
			$object_id,
			$language
		);

		$translated = (int) $wpdb->get_var( $sql );

		return $translated ? $translated : $object_id;
	}

	/**
	 * Reverse of translate_id(): given a translated object ID, return the source object ID.
	 *
	 * This is needed on the frontend AJAX handlers: the visitor sees translated
	 * category/doctor/service IDs, but our custom tables store relations using
	 * the source (default-language) IDs. We need to resolve back to the source
	 * before querying doctors-by-category or services-by-category.
	 *
	 * If $object_id is already a source (no mapping found), it is returned as-is.
	 *
	 * @param string $object_type 'doctor' | 'category' | 'service'
	 * @param int    $object_id   The (possibly translated) row ID.
	 * @return int Source object ID.
	 */
	public static function get_source_id( $object_type, $object_id ) {

		global $wpdb;

		$object_id = absint( $object_id );

		if ( ! $object_id ) {
			return 0;
		}

		$sql = $wpdb->prepare(
			"SELECT source_object_id
			FROM " . self::table() . "
			WHERE object_type = %s
			AND translated_object_id = %d
			AND translated_object_id != source_object_id
			LIMIT 1",
			$object_type,
			$object_id
		);

		$source = (int) $wpdb->get_var( $sql );

		// If found in map, return source; otherwise the ID is already a source.
		return $source ? $source : $object_id;
	}

	/**
	 * Translate a single record.
	 *
	 * @param string $object_type
	 * @param array  $record
	 *
	 * @return array
	 */

	public static function translate_record( $object_type, $record ) {

		if ( empty( $record['id'] ) ) {
			return $record;
		}

		$new_id = self::translate_id(
			$object_type,
			$record['id']
		);

		if ( $new_id == $record['id'] ) {
			return $record;
		}

		$table = self::get_object_table( $object_type );

		if ( ! $table ) {
			return $record;
		}

		global $wpdb;

		$sql = $wpdb->prepare(
			"SELECT *
			FROM {$table}
			WHERE id=%d",
			$new_id
		);

		$row = $wpdb->get_row( $sql, ARRAY_A );

		return $row ? $row : $record;
	}



    /**
 * Get translation status for all active languages.
 *
 * @param string $object_type Object type.
 * @param int    $object_id   Source object ID.
 *
 * @return array
 */
public static function get_translation_statuses(
	$object_type,
	$object_id
) {

	$object_id = absint( $object_id );

	if ( ! $object_id ) {
		return array();
	}

	$languages = self::get_languages();

	if ( empty( $languages ) ) {
		return array();
	}

	$translations = self::get_available_translations(
		$object_type,
		$object_id
	);

	$translation_map = array();

	foreach ( $translations as $translation ) {

		$translation_map[ $translation['language_code'] ] = array(
			'translated' => true,
			'object_id'  => (int) $translation['translated_object_id'],
		);

	}

	$result = array();

	foreach ( $languages as $code => $language ) {

		$is_default = ! empty( $language['is_default'] );

		$result[] = array(
			'code'            => $code,
			'display_name'    => $language['display_name'],
			'native_name'     => $language['native_name'],
			'is_default'      => $is_default,
			'translated'      => $is_default
				? true
				: isset( $translation_map[ $code ] ),
			'object_id'       => $is_default
				? $object_id
				: ( $translation_map[ $code ]['object_id'] ?? null ),
			'language_code'   => $code,
		);

	}

	return $result;

}

	/**
	 * Render translation column.
	 *
	 * @param string $object_type
	 * @param int    $object_id
	 *
	 * @return string
	 */
	/**
 * Render translation column HTML.
 *
 * Outputs clickable translation icons for each active language.
 *
 * Example output:
 * 🇩🇪 +   🇫🇷 ✓   🇦🇪 +
 *
 * @param string $object_type Object type (doctor, category, service).
 * @param int    $object_id   Source object ID.
 *
 * @return string HTML
 */
public static function render_translation_column( $object_type, $object_id ) {

	$object_id = absint( $object_id );

	if ( ! $object_id ) {
		return '—';
	}

	if ( ! self::is_wpml_active() ) {
		return '';
	}

	// Get all active languages.
	$languages = self::get_languages();

	if ( empty( $languages ) ) {
		return '<em>' . esc_html__( 'No languages', 'appointment-booking-system' ) . '</em>';
	}

	$default_language = self::get_default_language();

	// Build lookup: language_code => translated_object_id.
	$translations     = self::get_available_translations( $object_type, $object_id );
	$translated_langs = array();
	foreach ( $translations as $t ) {
		$translated_langs[ $t['language_code'] ] = true;
	}

	$output = '<div class="ab-translation-icons">';

	foreach ( $languages as $code => $language ) {

		// Skip default language — source row needs no self-translation badge.
		if ( $code === $default_language ) {
			continue;
		}

		$has_translation = isset( $translated_langs[ $code ] );

		$url = wp_nonce_url(
			add_query_arg(
				array(
					'page'    => 'ab-translation',
					'type'    => $object_type,
					'item_id' => $object_id,
					'lang'    => $code,
				),
				admin_url( 'admin.php' )
			),
			'ab_translation_nonce'
		);

		if ( $has_translation ) {
			$title   = sprintf(
				/* translators: %s: language name */
				__( 'Edit %s translation', 'appointment-booking-system' ),
				$language['display_name']
			);
			$pill_class = 'ab-trans-pill ab-trans-pill--done';
			$icon       = '<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>';
		} else {
			$title   = sprintf(
				/* translators: %s: language name */
				__( 'Add %s translation', 'appointment-booking-system' ),
				$language['display_name']
			);
			$pill_class = 'ab-trans-pill ab-trans-pill--missing';
			$icon       = '<span class="dashicons dashicons-plus-alt" aria-hidden="true"></span>';
		}

		$output .= sprintf(
			'<a href="%1$s" class="%2$s" title="%3$s">%4$s<span class="ab-trans-code">%5$s</span></a>',
			esc_url( $url ),
			esc_attr( $pill_class ),
			esc_attr( $title ),
			$icon,
			esc_html( strtoupper( $code ) )
		);
	}

	$output .= '</div>';

	return $output;
}


/**
 * Update translation map with WPML-specific data.
 *
 * @param string $type            Object type.
 * @param int    $source_id       Source ID.
 * @param string $language        Language code.
 * @param array  $wpml_data       WPML job data.
 * @return bool
 */
public static function update_translation_map( $type, $source_id, $language, $wpml_data ) {
	global $wpdb;

	$existing = $wpdb->get_var( $wpdb->prepare(
		"SELECT id FROM " . self::table() . " 
		 WHERE object_type = %s 
		 AND source_object_id = %d 
		 AND language_code = %s",
		$type,
		$source_id,
		$language
	) );

	if ( $existing ) {
		return false !== $wpdb->update(
			self::table(),
			array_merge( $wpml_data, array( 'updated_at' => current_time( 'mysql' ) ) ),
			array( 'id' => $existing )
		);
	}

	return false !== $wpdb->insert(
		self::table(),
		array_merge(
			array(
				'object_type'          => $type,
				'source_object_id'     => $source_id,
				'translated_object_id' => $source_id,
				'language_code'        => $language,
			),
			$wpml_data,
			array( 'created_at' => current_time( 'mysql' ) )
		)
	);
}

	/**
	 * Translate multiple rows.
	 *
	 * @param string $object_type
	 * @param array  $rows
	 *
	 * @return array
	 */
	public static function translate_records( $object_type, $rows ) {

		if ( empty( $rows ) ) {
			return array();
		}

		$result = array();

		foreach ( $rows as $row ) {

			$result[] = self::translate_record(
				$object_type,
				$row
			);

		}

		return $result;
	}

	/**
	 * Save translation mapping.
	 *
	 * @param string $object_type
	 * @param int    $source_id
	 * @param int    $translated_id
	 * @param string $language
	 *
	 * @return bool
	 */
	public static function save_translation(
		$object_type,
		$source_id,
		$translated_id,
		$language
	) {

		global $wpdb;

		$data = array(
			'object_type'         => sanitize_key( $object_type ),
			'source_object_id'    => absint( $source_id ),
			'translated_object_id'=> absint( $translated_id ),
			'language_code'       => sanitize_text_field( $language ),
		);

		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id
				FROM " . self::table() . "
				WHERE object_type=%s
				AND source_object_id=%d
				AND language_code=%s",
				$object_type,
				$source_id,
				$language
			)
		);

		if ( $existing ) {

			return false !== $wpdb->update(
				self::table(),
				$data,
				array(
					'id' => $existing,
				)
			);

		}

		return false !== $wpdb->insert(
			self::table(),
			$data
		);

	}

	/**
	 * Delete translation.
	 *
	 * @param string $object_type
	 * @param int    $source_id
	 * @param string $language
	 *
	 * @return bool
	 */
	public static function delete_translation(
		$object_type,
		$source_id,
		$language
	) {

		global $wpdb;

		return false !== $wpdb->delete(
			self::table(),
			array(
				'object_type'      => $object_type,
				'source_object_id' => $source_id,
				'language_code'    => $language,
			)
		);

	}

	/**
	 * Does translation exist?
	 *
	 * @param string $object_type
	 * @param int    $source_id
	 * @param string $language
	 *
	 * @return bool
	 */
	public static function has_translation(
		$object_type,
		$source_id,
		$language
	) {

		global $wpdb;

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*)
				FROM " . self::table() . "
				WHERE object_type=%s
				AND source_object_id=%d
				AND language_code=%s",
				$object_type,
				$source_id,
				$language
			)
		);

		return $count > 0;
	}

	/**
	 * Get available translations.
	 *
	 * @param string $object_type
	 * @param int    $source_id
	 *
	 * @return array
	 */
	public static function get_available_translations(
		$object_type,
		$source_id
	) {

		global $wpdb;

		$sql = $wpdb->prepare(
			"SELECT *
			FROM " . self::table() . "
			WHERE object_type=%s
			AND source_object_id=%d",
			$object_type,
			$source_id
		);

		return $wpdb->get_results( $sql, ARRAY_A );

	}

	/**
	 * Get original object ID.
	 *
	 * If translated ID is passed,
	 * return original source object ID.
	 *
	 * @param string $object_type
	 * @param int    $translated_id
	 *
	 * @return int
	 */
	public static function get_original_id(
		$object_type,
		$translated_id
	) {

		global $wpdb;

		$sql = $wpdb->prepare(
			"SELECT source_object_id
			FROM " . self::table() . "
			WHERE object_type=%s
			AND translated_object_id=%d
			LIMIT 1",
			$object_type,
			$translated_id
		);

		$original = (int) $wpdb->get_var( $sql );

		return $original ? $original : absint( $translated_id );

	}
}