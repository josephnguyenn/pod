<?php

if( !class_exists('FPD_Resource_Options') ) {

	class FPD_Resource_Options {

		public static function get_options( $option_keys = array() ) {

			$options = array();

			foreach($option_keys as $option_key) {

				if( $option_key == 'enabled_fonts' ) { //get enabled fonts
					$options[$option_key] = FPD_Fonts::to_data(FPD_Fonts::get_enabled_fonts());
				}
				else if( $option_key == 'design_categories' ) { //get design categories

					$categories = FPD_Designs::get_categories( true );

					$design_categories = array();
					foreach($categories as $category) {
						$design_categories[$category->title] = $category->title;
					}

					$options[$option_key] = $design_categories;
				}
				else if( $option_key == 'primary_layout_props' ) { //get primary layout properties

					$main_ui = FPD_UI_Layout_Composer::get_layout(fpd_get_option('fpd_product_designer_ui_layout'));
					$plugin_options = $main_ui['plugin_options'];

					$options[$option_key] = array(
						'stageWidth' 	=> $plugin_options['stageWidth'],
						'stageHeight' 	=> $plugin_options['stageHeight']
					);

				}
				else if( $option_key == 'fpd_custom_texts_parameter_patterns' || $option_key == 'fpd_designs_parameter_patterns' ) {

					$options[$option_key] = fpd_check_file_list(
						fpd_get_option($option_key),
						FPD_WP_CONTENT_DIR . ($option_key == 'fpd_custom_texts_parameter_patterns' ? '/uploads/fpd_patterns_text/'  : '/uploads/fpd_patterns_svg/')
					);

				}
				else { //get option by key
					$options[$option_key] = fpd_get_option($option_key);
				}

			}

			return $options;

		}

		public static function get_options_group( $args = array() ) {

			$defaults = array(
				'group' 		=> 'general',
				'option_keys' 	=> null,
				'lang_code' 	=> null
			);

			$args = wp_parse_args( $args, $defaults );

			//check: get options by keys
			if( isset( $args['option_keys'] ) && is_array( $args['option_keys'] ) ) {
				return self::get_options( $args['option_keys'] );
			}

			$options_group = $args['group'];
			$global_options = array();
			$error = null;

			$all_settings = FPD_Settings::$radykal_settings->settings;

			if( $options_group == 'labels' ) {
				
				$labels_config = FPD_Settings_Labels::get_labels_configs( $args );
				
				$global_options['labels_config'] = $labels_config;


			}
			else if( $options_group == 'addons' ) {

				//get genius plan
				$genius_res = fpd_genius_request();
				
				if( is_array($genius_res) && $genius_res['status'] == 'success' ) {

					$genius_client_data = $genius_res['data'];									

					$now = new DateTime();
					$access_until = new DateTime( $genius_client_data['accessUntil'] );

					if($genius_client_data['subscription'] == 'premium' && $now < $access_until) {

						array_push(
							$all_settings['addons']['3d-preview'],
							array(
								'title' 		=> __( '3D Model Manager (Genius)', 'radykal' ),
								'description' 	=> __( 'Your Genius Plan includes unlimited access to our 3D model library. Use the Manager to add the 3D models you want.', 'radykal' ),
								'id' 			=> 'fpd_3d_model_installer',
								'placeholder'	=> __( 'Open Manager', 'radykal' ),
								'type' 			=> 'button',
								'unbordered'	=> true
							),
						);

					}	

				}

				$global_options = $all_settings[$options_group];

			}
			else
				$global_options = $all_settings[$options_group];

			if( !is_null($error) ) {
				return new WP_Error( $error['code'], $error['message'] );
			}

			if( $options_group !== 'labels' ) {

				foreach($global_options as $key_section => $section) {

					foreach($section as $key_option_entry => $option_entry) {

						$global_options[$key_section][$key_option_entry]['value'] = fpd_get_option($option_entry['id'], false);

					}

				}

			}

			return $global_options;

		}

		public static function update_options( $options = array() ) {

			$options = is_array($options) ? $options : json_decode($options, true);

			if( isset($options['labels_lang_code']) ) {

				if( isset($options['reset']) ) {

					if ( !class_exists('FPD_Settings_Labels') )
						require_once(FPD_PLUGIN_DIR.'/inc/settings/class-labels-settings.php');

					FPD_Settings_Labels::update_all_languages();					

				}

				$labels = apply_filters( 'fpd_labels_update', $options['labels'], $options['labels_lang_code'] );

				update_option('fpd_lang_'.$options['labels_lang_code'], json_encode($labels, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) );

			}
			else {

				foreach($options as $key => $value) {

					if( is_bool($value) )
						$value = $value ? 'yes' : 'no';

					$value = apply_filters( 'fpd_option_update', $value, $key );

					update_option( $key, $value );
				}

			}

			return array(
				'message' => __('Options updated.', 'radykal')
			);

		}

		//deprecated (used in rest api plugin)
		public static function get_languages() {

			return FPD_Settings_Labels::get_active_lang_codes();

		}


	}

}

?>