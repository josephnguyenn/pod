<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


if( !class_exists('FPD_Settings_Addons') ) {

	class FPD_Settings_Addons {

		public static function get_color_selection_placements() {

			$options = array(
				'none' => __( 'None', 'radykal' ),
				'shortcode' => __( 'Via Shortcode [fpd_cs]', 'radykal' )
			);

			if( function_exists('get_woocommerce_currency') ) {
				$options['after-short-desc'] = __( 'After Short Description (WooCommerce)', 'radykal' );
			}

			return $options;

		}

		public static function get_bulk_add_form_placements() {

			$options = array(
				'none' => __( 'None', 'radykal' ),
				'shortcode' => __( 'Via Shortcode [fpd_bulk_add_form]', 'radykal' ),
			);

			if( function_exists('get_woocommerce_currency') ) {
				$options['after-short-desc'] = __( 'After Short Description (WooCommerce)', 'radykal' );
			}

			return $options;

		}

		public static function get_dynamic_views_units() {

			return array(
				'mm' => 'MM (Millimetre)',
				'cm' => 'CM (Centimetre)',
				'inch' => 'INCH'
			);

		}

		public static function get_3d_preview_placement_options() {

			$options = array(
				'designer'		=> __( 'Inside Product Designer', 'radykal' ),
				'before_fpd'	=> __( 'Before Product Designer', 'radykal' ),
				'after_fpd'		=> __( 'After Product Designer', 'radykal' ),
			);

			if( class_exists('WooCommerce') ) {
				$options['before_single_product_summary'] = __( 'Before Single Product Summary (WooCommerce)', 'radykal' );
			}

			$options['shortcode'] = __( 'Via Shortcode: [fpd_3d_preview]', 'radykal' );

			return $options;

		}

		public static function get_pricing_group_names() {

			$names = array();
			$pr_groups = get_option( 'fpd_pr_groups', array() );

			if( !is_array($pr_groups) )
				$pr_groups = json_decode(fpd_strip_multi_slahes($pr_groups), true);

			foreach($pr_groups as $pr_group) {
				$names[sanitize_key($pr_group['name'])] = $pr_group['name'];
			}

			return $names;

		}

		public static function get_view_thumbnails_placement_options() {

			$options = array(
				'none' 			=> __( 'None', 'radykal' ),
				'before_fpd'	=> __( 'Before Product Designer', 'radykal' ),
				'after_fpd'		=> __( 'After Product Designer', 'radykal' ),
				'main_wrapper'		=> __( 'Inside Main Wrapper of designer', 'radykal' ),
				'shortcode'		=> __( 'Via Shortcode: [fpd_view_thumbnails]', 'radykal' ),
			);

			return $options;

		}

		public static function get_options() {

			return apply_filters('fpd_addons_settings', array(

				'dynamic-views' => array (

					array(
						'title' 		=> __( 'Enable Dynamic Views', 'radykal' ),
						'description'	=> __( 'The customer can edit, add and delete the views/pages of your products.', 'radykal' ),
						'id' 			=> 'fpd_enableDynamicViews',
						'default'		=> 'no',
						'type' 			=> 'checkbox',
					),
	
					array(
						'title' 		=> __( 'Price Per CM2', 'radykal' ),
						'id' 			=> 'fpd_dynamic_views_pricePerArea',
						'default'		=> 0,
						'type' 			=> 'number',
					),

					array(
						'title' 		=> __( 'Unit Of Length', 'radykal' ),
						'id' 			=> 'fpd_dynamic_views_unit',
						'default'		=> 'mm',
						'type' 			=> 'select',
						'options' 		=> self::get_dynamic_views_units()
					),

					array(
						'title' 		=> __( 'Predefined Formats', 'radykal' ),
						'description' 	=> __( 'Display some predefined formats that your customers can choose from.', 'radykal' ),
						'id' 			=> 'fpd_dynamic_views_formats',
						'default'		=> '',
						'type' 			=> 'values-group',
						'options'   	=> array(
							'width' 		=> 'Width',
							'height' 		=> 'Height'
						),
						'regexs' 		=> array(
							'width' 		=> '^-?\d+\.?\d*$',
							'height' 		=> '^-?\d+\.?\d*$'
						)
					),

					array(
						'title' 		=> __( 'Minimum Width', 'radykal' ),
						'description' 	=> __( 'The minimum width a customer can enter in unit of length.', 'radykal' ),
						'id' 			=> 'fpd_dynamic_views_minWidth',
						'default'		=> 0,
						'type' 			=> 'number'
					),

					array(
						'title' 		=> __( 'Minimum Height', 'radykal' ),
						'description' 	=> __( 'The minimum height a customer can enter in unit of length.', 'radykal' ),
						'id' 			=> 'fpd_dynamic_views_minHeight',
						'default'		=> 0,
						'type' 			=> 'number'
					),

					array(
						'title' 		=> __( 'Maximum Width', 'radykal' ),
						'description' 	=> __( 'The maximum width a customer can enter in unit of length.', 'radykal' ),
						'id' 			=> 'fpd_dynamic_views_maxWidth',
						'default'		=> 1000,
						'type' 			=> 'number'
					),

					array(
						'title' 		=> __( 'Maximum Height', 'radykal' ),
						'description' 	=> __( 'The maximum height a customer can enter in unit of length.', 'radykal' ),
						'id' 			=> 'fpd_dynamic_views_maxHeight',
						'default'		=> 1000,
						'type' 			=> 'number',
						'unbordered'	=> true
					),

				),

				'color-selection' => array(

					array(
						'title' 		=> __( 'Placement', 'radykal' ),
						'id' 			=> 'fpd_color_selection_placement',
						'default'		=> '',
						'type' 			=> 'select',
						'class'			=> 'semantic-select',
						'allowclear'	=> true,
						'options'   	=> self::get_color_selection_placements(),
						'unbordered'	=> true
					),

				),

				'bulk-variations' => array(

					array(
						'title' 		=> __( 'Placement', 'radykal' ),
						'id' 			=> 'fpd_bulkVariationsPlacement',
						'default'		=> '',
						'type' 			=> 'select',
						'options' 		=> self::get_bulk_add_form_placements()
					),

					array(
						'title' 		=> __( 'Variations', 'radykal' ),
						'description' 	=> __( 'You can define variations like that: Size=M|L;Colors=Blue|Red', 'radykal' ),
						'id' 			=> 'fpd_bulkVariations_written',
						'default'		=> '',
						'type' 			=> 'text',
						'unbordered'	=> true
					),

				),

				'pricing-rules' => array(

					array(
						'title' 		=> __( 'Pricing Rule Groups', 'radykal' ),
						'description' 	=> __('Select pricing groups that will be used for all product designers.', 'radykal'),
						'id' 			=> 'fpd_pricing_rules',
						'default'		=> '',
						'type' 			=> 'multiselect',
						'options'   	=> self::get_pricing_group_names(),
						'unbordered'	=> true
					)

				),

				'3d-preview' => array(

					array(
                        'description' 		=> __( 'If you want to display the customization on a realistic 3D model, <a href="https://fancyproductdesigner.com/features/3d-preview/" target="_blank">you need to get one of our 3D models first</a>.', 'radykal' ),
                        'type' 			=> 'description',
                        'id' 			=> '3d-preview-desc'
                    ),

					array(
						'title' 		=> __( 'Placement', 'radykal' ),
						'description' 	=> __( 'Set the placement for 3D Preview.', 'radykal' ),
						'id' 			=> 'fpd_3d_preview_placement',
						'default'		=> 'designer',
						'type' 			=> 'select',
						'options'   	=> self::get_3d_preview_placement_options(),
						'unbordered'	=> true
					),

				),

				'view-thumbnails' => array(

					array(
						'title' 		=> __( 'Placement', 'radykal' ),
						'id' 			=> 'fpd_view_thumbnails_placement',
						'default'		=> 'none',
						'type' 			=> 'select',
						'options' 		=> self::get_view_thumbnails_placement_options(),
						'unbordered'	=> true
					),

				)

			));

		}

	}

}