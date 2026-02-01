<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


if( !class_exists('FPD_Settings_Labels') ) {

	class FPD_Settings_Labels {

		public static function get_default_json_url() {

			return FPD_PLUGIN_DIR.'/assets/json/default_lang.json';

		}

		public static function get_current_lang_code() {
			
			if( defined('ICL_LANGUAGE_CODE') ) {

				$wpml_locale = '';				
				foreach(apply_filters( 'wpml_active_languages', null ) as $languages__value) {
					if ($languages__value['active']) { $wpml_locale = $languages__value['default_locale']; break; }
				}

				return empty( $wpml_locale ) ? get_locale() : $wpml_locale;

			}
			else
				return get_locale();

		}

		public static function get_default_lang() {

			//self::update_default_lang();

			$default_lang = get_option('fpd_lang_default');
			if( empty($default_lang) ) {

				$default_lang = file_get_contents(self::get_default_json_url());
				$default_lang = json_encode(json_decode($default_lang));
				update_option('fpd_lang_default', $default_lang);

			}

			return json_decode($default_lang, true);

		}

		public static function update_default_lang() {

			$default_lang = file_get_contents(self::get_default_json_url());
			$default_lang = json_encode(json_decode($default_lang));
			update_option('fpd_lang_default', $default_lang);

			return $default_lang;

		}

		//checks if the saved languages are missing translations from the default translation
		public static function update_all_languages() {

			global $wpdb;

			$default_lang = json_decode(self::update_default_lang(), true);

			$db_langs = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name NOT LIKE 'fpd_lang_default' AND option_name NOT LIKE 'fpd_languages'", "fpd_lang_%") );
			
			foreach($db_langs as $db_lang) {

				$lang = json_decode($db_lang->option_value, true);
				$new_lang = array_merge_recursive($default_lang, $lang); //merge default into db lang
				$new_lang = array_replace_recursive($default_lang, $lang);
				$new_lang = json_encode($new_lang);

				update_option($db_lang->option_name, $new_lang);

			}

		}

		public static function get_current_lang( $lang_code= false ) {

			if($lang_code === false)
				$lang_code = self::get_current_lang_code();

			$current_lang = get_option('fpd_lang_'.$lang_code);
			
			if( empty($current_lang) ) {

				$current_lang = file_get_contents(self::get_default_json_url());
				$current_lang = json_encode(json_decode($current_lang));
				update_option('fpd_lang_'.$lang_code, $current_lang);

			}

			return json_decode($current_lang, true);

		}

		public static function get_translation($section, $key) {

			//replace old label keys with new
			$replace_keys = array(
				'automated_export:download' => 'pro_export:download'
			);

			if( isset($replace_keys[$key]) )
				$key = $replace_keys[$key];

			$lang_code = self::get_current_lang_code();

			$current_lang = get_option('fpd_lang_'.$lang_code);
			if( empty($current_lang) ) {

				$current_lang = file_get_contents(self::get_default_json_url());
				$current_lang = json_encode(json_decode($current_lang));
				update_option('fpd_lang_'.$lang_code, $current_lang);

			}

			$current_lang = json_decode($current_lang, true);

			if( isset($current_lang[$section]) ) {

				if( isset($current_lang[$section][$key]) ) {
					return htmlspecialchars( $current_lang[$section][$key], ENT_QUOTES );
				}
				else {
					$default_lang = self::get_default_lang();

					if( isset($default_lang[$section]) && isset($default_lang[$section][$key]) )
						return $default_lang[$section][$key];
				}

			}
			else
				return '';

		}

		public static function get_labels_object_string() {

			$lang_code = self::get_current_lang_code();
			
			$current_lang = get_option('fpd_lang_'.$lang_code);
			if( empty($current_lang) ) {

				$current_lang = file_get_contents(self::get_default_json_url());
				$current_lang = json_encode(json_decode($current_lang));
				update_option('fpd_lang_'.$lang_code, $current_lang);

			}

			return $current_lang;

		}

		public static function get_active_lang_codes() {

			$multi_langs_enabled = get_option( 'fpd_multi_languages', 'no' );
			return $multi_langs_enabled === 'yes' ? get_option('fpd_languages', array()) : array();
			
		}

		public static function get_labels_configs( $args = array('lang_code' => null) ) {

			$configs = array();

			$textarea_keys = array(
				'uploaded_image_size_alert',
				'not_supported_device_info',
				'info_content',
				'login_required_info'
			);

			if( $args['lang_code'] ) //get lang code from url
				$current_lang_code = $args['lang_code'];
			else {
				$current_lang_code = self::get_current_lang_code();
			}

			$multi_langs_enabled = fpd_get_option( 'fpd_multi_languages' );
			if( $multi_langs_enabled ) {

				$configs['lang_codes'] = self::get_active_lang_codes();

				if( !empty($configs['lang_codes']) )
					$current_lang_code = in_array( $current_lang_code, $configs['lang_codes'] ) ? $current_lang_code : $configs['lang_codes'][0];

			}
			
			$current_lang = self::get_current_lang($current_lang_code);			
			$default_lang = self::get_default_lang();
			
			$labels_options = array();
			foreach($default_lang as $key_section => $section) {

				$labels_options[$key_section] = array();
				foreach($section as $key_option_entry => $option_entry) {

					$label_option_data = array(
						'title' => str_replace( ':', ': ', str_replace('_', ' ', $key_option_entry) ), //replace _ with whitespace and : with :whitespace
						'default' => $option_entry,
						'id' => $key_option_entry,
						'type' => in_array($key_option_entry, $textarea_keys) ? 'textarea' : 'text',
						'value' => isset($current_lang[$key_section][$key_option_entry]) ? $current_lang[$key_section][$key_option_entry] : $option_entry,
						'column_width' => 'eight'
					);

					array_push($labels_options[$key_section], $label_option_data);

				}

			}

			$configs['current_lang'] = $current_lang_code;
			$configs['labels'] = $labels_options;

			return $configs;

		}
	}

}
