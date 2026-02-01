<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( !class_exists('FPD_IPS_General') ) {

	class FPD_IPS_General {

		public static function get_options() {

			return apply_filters('fpd_ips_general_settings', array(

				array(
					'title' 	=> __( 'Main User Interface', 'radykal' ),
					'id' 		=> 'product_designer_ui_layout',
					'default'	=> '',
					'type' 		=> 'select',
					'class'		=> 'semantic-select',
					'allowclear'=> true,
					'options'   => FPD_Settings_General::get_saved_ui_layouts()
				),

				array(
					'title' 	=> __( 'Open Product Designer in...', 'radykal' ),
					'id' 		=> 'product_designer_visibility',
					'default'	=> '',
					'type' 		=> 'select',
					'class'		=> 'semantic-select',
					'allowclear'=> true,
					'options'   => FPD_Settings_General::get_product_designer_visibilities()
				),

				array(
					'title' 	=> __( 'Main Bar Position', 'radykal' ),
					'id' 		=> 'main_bar_position',
					'default'	=> '',
					'type' 		=> 'select',
					'class'		=> 'semantic-select',
					'allowclear'=> true,
					'options'   => FPD_Settings_General::get_main_bar_positions()
				),

				array(
					'title' 	=> __( 'Design Categories', 'radykal' ),
					'placeholder' => __( 'Design Categories From Main Settings', 'radykal' ),
					'id' 		=> 'design_categories',
					'default'	=> '',
					'type' 		=> 'multiselect',
					'class'		=> 'semantic-select',
					'options'   => fpd_output_top_level_design_cat_options()
				),

				array(
					'title' 	=> __( 'Available Fonts', 'radykal' ),
					'placeholder' => __( 'All Fonts', 'radykal' ),
					'id' 		=> 'font_families',
					'default'	=> '',
					'type' 		=> 'multiselect',
					'class'		=> 'semantic-select',
					'options'   => self::get_fonts_options()
				),

				array(
					'title' 	=> __( 'Replace Initial Elements', 'radykal' ),
					'id' 		=> 'replace_initial_elements',
					'default'	=> '',
					'type' 		=> 'select',
					'class'		=> 'semantic-select',
					'allowclear'=> true,
					'options'   => array(
						"0" => __('No', 'radykal'),
						"1" => __('Yes', 'radykal'),
					)
				),

				array(
					'title' 	=> __( 'Color Prices for Images', 'radykal' ),
					'id' 		=> 'enable_image_color_prices',
					'default'	=> '',
					'type' 		=> 'select',
					'class'		=> 'semantic-select',
					'allowclear'=> true,
					'options'   => array(
						"0" => __('No', 'radykal'),
						"1" => __('Yes', 'radykal'),
					)
				),

				array(
					'title' 	=> __( 'Color Prices for Texts', 'radykal' ),
					'id' 		=> 'enable_text_color_prices',
					'default'	=> '',
					'type' 		=> 'select',
					'class'		=> 'semantic-select',
					'allowclear'=> true,
					'options'   => array(
						"0" => __('No', 'radykal'),
						"1" => __('Yes', 'radykal'),
					)
				),

				array(
					'title' 	=> __( 'Hide Dialog On Add', 'radykal' ),
					'id' 		=> 'hide_dialog_on_add',
					'default'	=> '1',
					'type' 		=> 'select',
					'class'		=> 'semantic-select',
					'allowclear'=> true,
					'options'   => array(
						"0" => __('No', 'radykal'),
						"1" => __('Yes', 'radykal'),
					)
				),

				array(
					'title' 	=> __( 'Customization Required', 'radykal' ),
					'id' 		=> 'customization_required',
					'default'	=> '',
					'type' 		=> 'select',
					'class'		=> 'semantic-select',
					'allowclear'=> true,
					'options'   => array(
						"none" => __('None', 'radykal'),
						"any" => __('ANY view needs to be customized.', 'radykal'),
						"all" => __('ALL views needs to be customized.', 'radykal'),
					)
				),

				array(
					'title' 	=> __( 'Layouts', 'radykal' ),
					'id' 		=> 'layouts',
					'default'	=> '',
					'type' 		=> 'select',
					'class'		=> 'semantic-select',
					'allowclear'=> true,
					'options'   => self::get_product_layouts()
				),

				array(
					'title' 	=> __( 'Per-Pixel Detection', 'radykal' ),
					'id' 		=> 'canvas_per_pixel_detection',
					'default'	=> '0',
					'type' 		=> 'select',
					'class'		=> 'semantic-select',
					'allowclear'=> true,
					'options'   => array(
						"0" => __('No', 'radykal'),
						"1" => __('Yes', 'radykal'),
					)
				),

				array(
					'title' 		=> __( 'Ruler Position', 'radykal' ),
					'id' 			=> 'rulerPosition',
					'default'		=> '',
					'type' 		=> 'select',
					'class'		=> 'semantic-select',
					'options'		=> array(
						'canvas' 		=> __( 'Canvas', 'radykal' ),
						'pb' 			=> __( 'Around Printing Box', 'radykal' ),
					),
				),

				array(
					'title' 		=> __('Names & Numbers', 'radykal'),
					'type' 			=> 'section-title',
					'id' 			=> 'names-numbers-section',
					'unbordered'	=> true
				),

				array(
					'title' 	=> __( 'Dropdown Values', 'radykal' ),
					'id' 		=> 'namesNumbersDropdown',
					'default'	=> '',
					'type' 		=> 'text',
					'placeholder' => self::get_names_numbers_dropdown_placeholder()
				),

				array(
					'title' 			=> __( 'Entry Price', 'radykal' ),
					'id' 				=> 'namesNumbersEntryPrice',
					'placeholder'		=> fpd_get_option('fpd_namesNumbersEntryPrice'),
					'type' 				=> 'number',
					'custom_attributes' => array(
						'min' => 0,
						'step' => 0.01
					)
				),

			));
		}

		public static function get_fonts_options() {

			$fonts_options = array();

			foreach(FPD_Fonts::get_enabled_fonts()  as $key => $font) {
				$fonts_options[$font] = $font;
			}

			return $fonts_options;

		}

		public static function get_product_layouts() {

			$fpd_products = FPD_Product::get_products( array(
				'cols' => "ID, title",
				'order_by' 	=> "ID ASC",
			) );

			$layouts_options = array(
				'' => __( 'None', 'radykal' )
			);

			foreach($fpd_products as $fpd_product) {

				$layouts_options[$fpd_product->ID] = '#'.$fpd_product->ID.' - '.$fpd_product->title;

			}
			
			return $layouts_options;

		}

		public static function get_names_numbers_dropdown_placeholder() {

			$nn_module_placeholder = fpd_get_option('fpd_namesNumbersDropdown');
			$nn_module_placeholder = empty( $nn_module_placeholder ) ? __('e.g. S | M | L', 'radykal') : $nn_module_placeholder;

			return $nn_module_placeholder;

		}

	}

}

?>