<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


if( !class_exists('FPD_Settings') ) {

	class FPD_Settings {

		public static $radykal_settings;

		public function __construct() {

			add_action( 'init', array( &$this, 'init') );

		}

		public function init() {

			require_once(FPD_PLUGIN_DIR.'/inc/settings/class-general-settings.php');
			require_once(FPD_PLUGIN_DIR.'/inc/settings/class-default-element-options-settings.php');
			require_once(FPD_PLUGIN_DIR.'/inc/settings/class-labels-settings.php');
			require_once(FPD_PLUGIN_DIR.'/inc/settings/class-fonts-settings.php');
			require_once(FPD_PLUGIN_DIR.'/inc/settings/class-color-settings.php');
			require_once(FPD_PLUGIN_DIR.'/inc/settings/class-advanced-settings.php');
			require_once(FPD_PLUGIN_DIR.'/inc/settings/class-wc-settings.php');
			require_once(FPD_PLUGIN_DIR.'/inc/settings/class-pro-export-settings.php');
			require_once(FPD_PLUGIN_DIR.'/inc/settings/class-text-templates-settings.php');
			require_once(FPD_PLUGIN_DIR.'/inc/settings/class-addons-settings.php');

			self::$radykal_settings = new Radykal_Settings( array(
					'page_id' => 'fpd_settings',
				)
			);
			
			//first add blocks
			$blocks = apply_filters('fpd_settings_blocks', array(
				'general' => array(
					'display' => __('Display', 'radykal'),
					'modules' => __('Modules', 'radykal'),
					'actions' => __('Actions', 'radykal'),
					'social-share' => __('Social Share', 'radykal'),
				),
				'element-properties' => array(
					'images' => __('Custom Images & Designs', 'radykal'),
					'custom-images' => __('Custom Images', 'radykal'),
					'custom-texts' => __('Custom Texts', 'radykal'),
                    'coloring' => __('Coloring', 'radykal'),
					'general' => __('General', 'radykal'),
				),
				'fonts' => array(
					'fonts' => __('Fonts for the typeface dropdown', 'radykal'),
				),
				'colors' => array(
					'color-names' => __('Color Names', 'radykal'),
					'color-prices' => __('Color Prices', 'radykal'),
					'color-lists' => __('Color Lists', 'radykal'),
					'color-general' => __('General', 'radykal'),
				),
				'woocommerce' => array(
					'product-page' => __('Product Page', 'radykal'),
					'cart' => __('Cart', 'radykal'),
					'order' => __('Order', 'radykal'),
					'catalog-listing' => __('Catalog Listing', 'radykal'),
					'global-product-designer' => __('Global Product Designer', 'radykal'),
					'cross-sells' => __('Cross-Sells', 'radykal'),
				),
				'pro-export' => array(
					'pro-general' => __('Pro Export', 'radykal'),
					'printful' => __('Printful', 'radykal'),
				),
				'text-templates' => array(
					'tt-general' => __('Text Templates', 'radykal'),
				),
				'addons' => array(
					'dynamic-views' => __('Dynamic Views', 'radykal'),
					'color-selection' => __('Color Selection', 'radykal'),
					'bulk-variations' => __('Bulk Variations', 'radykal'),
					'pricing-rules' => __('Pricing Rules', 'radykal'),
					'3d-preview' => __('3D Preview', 'radykal'),
					'view-thumbnails' => __('View Thumbnails', 'radykal'),
				),
				'advanced' => array(
					'layout' => __('Layout', 'radykal'),
					'misc' => __('Miscellaneous', 'radykal'),
					'troubleshooting' => __('Troubleshooting', 'radykal'),
				),
			));

			$genius_license_key = get_option( 'fpd_genius_license_key', '' );
			if( !empty( $genius_license_key ) ) {

				$blocks['general']['ai-services'] = __('AI Services', 'radykal');

			}

			self::$radykal_settings->add_blocks($blocks);


			//add general settings
			$general_options = FPD_Settings_General::get_options();
			self::$radykal_settings->add_block_options( 'display', $general_options['display']);
			self::$radykal_settings->add_block_options( 'modules', $general_options['modules']);
			self::$radykal_settings->add_block_options( 'actions', $general_options['actions']);
			self::$radykal_settings->add_block_options( 'social-share', $general_options['social-share']);
			self::$radykal_settings->add_block_options( 'ai-services', $general_options['ai-services']);

			//add default element options settings
			$element_properties = FPD_Settings_Default_Element_Options::get_options();
			self::$radykal_settings->add_block_options( 'images', $element_properties['images']);
			self::$radykal_settings->add_block_options( 'custom-images', $element_properties['custom-images']);
			self::$radykal_settings->add_block_options( 'custom-texts', $element_properties['custom-texts']);
            self::$radykal_settings->add_block_options( 'coloring', $element_properties['coloring']);
			self::$radykal_settings->add_block_options( 'general', $element_properties['general']);

			//add fonts settings
			$fonts_options = FPD_Settings_Fonts::get_options();
			self::$radykal_settings->add_block_options( 'fonts', $fonts_options['fonts']);

			//add advanced color settings
			$advanced_color_options = FPD_Settings_Colors::get_options();
			self::$radykal_settings->add_block_options( 'color-names', $advanced_color_options['color-names']);
			self::$radykal_settings->add_block_options( 'color-prices', $advanced_color_options['color-prices']);
			self::$radykal_settings->add_block_options( 'color-lists', $advanced_color_options['color-lists']);
			self::$radykal_settings->add_block_options( 'color-general', $advanced_color_options['color-general']);

			//add wc settings
			$wc_options = FPD_Settings_WooCommerce::get_options();
			self::$radykal_settings->add_block_options( 'product-page', $wc_options['product-page']);
			self::$radykal_settings->add_block_options( 'cart', $wc_options['cart']);
			self::$radykal_settings->add_block_options( 'order', $wc_options['order']);
			self::$radykal_settings->add_block_options( 'catalog-listing', $wc_options['catalog-listing']);
			self::$radykal_settings->add_block_options( 'global-product-designer', $wc_options['global-product-designer']);
			self::$radykal_settings->add_block_options( 'cross-sells', $wc_options['cross-sells']);

			//add pro export settings
			$pro_export_options = FPD_Settings_Pro_Export::get_options();
			self::$radykal_settings->add_block_options( 'pro-general', $pro_export_options['pro-general']);
			self::$radykal_settings->add_block_options( 'printful', $pro_export_options['printful']);

			//add addons settings
			$addons_options = FPD_Settings_Addons::get_options();
			self::$radykal_settings->add_block_options( 'dynamic-views', $addons_options['dynamic-views']);
			self::$radykal_settings->add_block_options( 'color-selection', $addons_options['color-selection']);
			self::$radykal_settings->add_block_options( 'bulk-variations', $addons_options['bulk-variations']);
			self::$radykal_settings->add_block_options( 'pricing-rules', $addons_options['pricing-rules']);
			self::$radykal_settings->add_block_options( '3d-preview', $addons_options['3d-preview']);
			self::$radykal_settings->add_block_options( 'view-thumbnails', $addons_options['view-thumbnails']);

			//add advanced settings
			$advanced_options = FPD_Settings_Advanced::get_options();
			self::$radykal_settings->add_block_options( 'layout', $advanced_options['layout']);
			self::$radykal_settings->add_block_options( 'misc', $advanced_options['misc']);
			self::$radykal_settings->add_block_options( 'troubleshooting', $advanced_options['troubleshooting']);

			//text templates settings
			$text_templates_options = FPD_Settings_Text_Templates::get_options();
			self::$radykal_settings->add_block_options( 'tt-general', $text_templates_options['tt-general']);

			do_action( 'fpd_block_options_end' );

		}
	}
}

new FPD_Settings();
?>