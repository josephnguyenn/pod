<?php
/*
Plugin Name: Fancy Product Designer
Plugin URI: https://fancyproductdesigner.com/
Description: Product Designer for WordPress and WooCommerce. Create and sell customizable products.
Version: 6.4.8
Author URI: https://fancyproductdesigner.com
*/

if (! defined('ABSPATH')) exit; // Exit if accessed directly

require_once(dirname(__FILE__) . '/constants.php');

if (!class_exists('Fancy_Product_Designer')) {

	class Fancy_Product_Designer
	{

		const VERSION = '6.4.8';
		const JS_VERSION = '6.3.5';
		const GOOGLE_API_KEY = 'AIzaSyAkNEOqZSWRG96CbCikDBeNFgRZnlmulGY';
		const CAPABILITY = "edit_fancy_product_desiger";
		const LOCAL = false;
		const DEBUG = false;
		const REST_API_MIN_VERSION = '1.6.4';
		const MSPC_MIN_VERSION = '1.2.7';
		const REMOTE_ASSETS_URL = 'https://d2sho4g49s1uw8.cloudfront.net/';

		public function __construct()
		{

			require_once(FPD_PLUGIN_DIR . '/inc/class-radykal-settings.php');
			require_once(FPD_PLUGIN_DIR . '/inc/settings/class-settings.php');
			require_once(FPD_PLUGIN_DIR . '/inc/fpd-functions.php');
			require_once(FPD_PLUGIN_DIR . '/inc/api/class-product-settings.php');
			require_once(FPD_PLUGIN_DIR . '/inc/api/class-category.php');
			require_once(FPD_PLUGIN_DIR . '/inc/api/class-product.php');
			require_once(FPD_PLUGIN_DIR . '/inc/api/class-view.php');
			require_once(FPD_PLUGIN_DIR . '/inc/api/class-fonts.php');
			require_once(FPD_PLUGIN_DIR . '/inc/api/class-designs.php');
			require_once(FPD_PLUGIN_DIR . '/inc/api/class-template.php');
			require_once(FPD_PLUGIN_DIR . '/inc/api/class-ui-layouts.php');
			require_once(FPD_PLUGIN_DIR . '/inc/api/class-print-job.php');
			require_once(FPD_PLUGIN_ADMIN_DIR . '/class-admin.php');
			require_once(FPD_PLUGIN_DIR . '/inc/class-install.php');
			require_once(FPD_PLUGIN_DIR . '/inc/class-scripts-styles.php');
			require_once(FPD_PLUGIN_DIR . '/inc/api/class-shortcode-order.php');
			require_once(FPD_PLUGIN_DIR . '/inc/class-file-export.php');

			add_action('plugins_loaded', array(&$this, 'plugins_loaded'));
			add_action('init', array(&$this, 'init'));
		}

		public function plugins_loaded()
		{

			load_plugin_textdomain('radykal', false, dirname(plugin_basename(__FILE__)) . '/languages/');

			require_once(FPD_PLUGIN_DIR . '/inc/addons/class-gravity-form.php');

			if (class_exists('WooCommerce'))
				require_once(FPD_PLUGIN_DIR . '/woo/class-wc.php');

			if (!is_admin())
				require_once(FPD_PLUGIN_DIR . '/inc/class-debug.php');


			if (!empty(get_option('fpd_genius_license_key', $GLOBALS['FPD_GENIUS_LICENSE_KEY']))) {

				require_once(FPD_PLUGIN_DIR . '/pro-export/class-pro-export.php');
			}
		}

		public function init()
		{

			require_once(FPD_PLUGIN_DIR . '/inc/frontend/class-frontend.php');
			require_once(FPD_PLUGIN_DIR . '/inc/addons/class-3d-preview.php');
		}

		public static function pro_export_enabled()
		{

			return class_exists('Fancy_Product_Designer_Export') || !empty(get_option('fpd_genius_license_key', $GLOBALS['FPD_GENIUS_LICENSE_KEY']));
		}

		public static function create_print_ready_file($payload, $job_async = true, $export_method = null)
		{

			if (!empty(get_option('fpd_genius_license_key', $GLOBALS['FPD_GENIUS_LICENSE_KEY']))) {

				return FPD_Pro_Export::create_print_ready_file($payload, $job_async, $export_method);
			}
		}
	}
}

new Fancy_Product_Designer();
