<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( !class_exists('FPD_IPS_Addons') ) {

	class FPD_IPS_Addons {

		public static function get_options() {

			return apply_filters('fpd_ips_addons_settings', array(

				array(
					'title' 		=> __('Dynamic Views', 'radykal'),
					'type' 			=> 'section-title',
					'id' 			=> 'dynamic-views-section',
					'unbordered'	=> true
				),

				array(
					'title' 	=> __( 'Enable Dynamic Views', 'radykal' ),
					'id' 		=> 'enableDynamicViews',
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
					'title' 		=> __( 'Price Per CM2', 'radykal' ),
					'id' 			=> 'dynamic_views_pricePerArea',
					'default'		=> 0,
					'type' 			=> 'number',
					'unbordered'	=> true
				),

				array(
					'title' 		=> __('Color Selection', 'radykal'),
					'type' 			=> 'section-title',
					'id' 			=> 'color-selection-section',
					'unbordered'	=> true
				),

				array(
					'title' 		=> __( 'Placement', 'radykal' ),
					'id' 			=> 'color_selection_placement',
					'default'		=> '',
					'type' 			=> 'select',
					'class'			=> 'semantic-select',
					'options'		=> FPD_Settings_Addons::get_color_selection_placements(),
				),

				array(
					'title' 		=> __('Bulk Variations', 'radykal'),
					'type' 			=> 'section-title',
					'id' 			=> 'bulk-variations-section',
					'unbordered'	=> true
				),

				array(
					'title' 		=> __( 'Placement', 'radykal' ),
					'id' 			=> 'bulkVariationsPlacement',
					'default'		=> '',
					'type' 			=> 'select',
					'class'			=> 'semantic-select',
					'options'		=> FPD_Settings_Addons::get_bulk_add_form_placements(),
				),

				array(
					'title' 		=> __( 'Data', 'radykal' ),
					'id' 			=> 'bulkVariations_written',
					'default'		=> '',
					'type' 			=> 'text',
					'placeholder'	=> __('e.g. Size=M|L;Colors=Blue|Red', 'radykal'),
					'unbordered'	=> true
				),

				array(
					'title' 		=> __('Pricing Rules', 'radykal'),
					'type' 			=> 'section-title',
					'id' 			=> 'pricing-rules-section',
					'unbordered'	=> true
				),

				array(
					'title' 		=> __( 'Groups', 'radykal' ),
					'placeholder'	=> __( 'Select Groups', 'radykal' ),
					'id' 			=> 'pricing_rules',
					'default'		=> '',
					'type' 			=> 'multiselect',
					'class'			=> 'semantic-select  pointing bottom left',
					'options'		=> FPD_Settings_Addons::get_pricing_group_names(),
				),

				array(
					'title' 		=> __('3D Preview', 'radykal'),
					'type' 			=> 'section-title',
					'id' 			=> '3d-preview-section',
					'unbordered'	=> true
				),

				array(
					'title' 		=> __( 'Placement', 'radykal' ),
					'id' 			=> '3d_preview_placement',
					'default'		=> 'designer',
					'type' 			=> 'select',
					'options'   	=> FPD_Settings_Addons::get_3d_preview_placement_options(),
				),

			));
		}

	}

}

?>