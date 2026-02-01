<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( !class_exists('FPD_UI_Layout_Composer') ) {

	class FPD_UI_Layout_Composer {

		public static function get_default_json_url() {

			return FPD_PLUGIN_DIR.'/assets/json/default_ui_layout.json';

		}

		public static function get_layouts() {

			global $wpdb;
			return $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->options} WHERE option_name LIKE %s", "fpd_ui_layout_%") );

		}

		public static function get_layout( $id='' ) {

			if( get_option('fpd_ui_layout_'.$id) !== false ) {
				return json_decode( stripslashes(get_option('fpd_ui_layout_'.$id)), true);
			}
			else if( get_option('fpd_ui_layout_default') !== false ) {
				return json_decode( stripslashes(get_option('fpd_ui_layout_default') ), true);
			}
			else {

				$default_layout = file_get_contents(self::get_default_json_url());
				$default_layout = json_encode(json_decode($default_layout));
				update_option('fpd_ui_layout_default', $default_layout);

				return json_decode($default_layout, true);

			}

		}

		public static function save_layout( $id, $saved_layout) {

			if( is_array( $saved_layout ) )
				$saved_layout = (object) $saved_layout;

			update_option( 'fpd_ui_layout_'.$id, addslashes(json_encode($saved_layout)) );

			return array(
				'message' => __('Layout saved.', 'radykal'),
				'type'    => 'success'
			);

		}

		public static function get_css_from_layout( $layout ) {

			if( is_array($layout) )
				$layout = json_decode(json_encode($layout)); //convert array to stdclass

			$primary_color = @$layout->css_colors && @$layout->css_colors->primary_color ? $layout->css_colors->primary_color : '#000000';
			$secondary_color = @$layout->css_colors && @$layout->css_colors->secondary_color ? $layout->css_colors->secondary_color : '#27ae60';

			return ":root {--fpd-primary-color: $primary_color;--fpd-secondary-color: $secondary_color;}";

		}

	}

}

?>