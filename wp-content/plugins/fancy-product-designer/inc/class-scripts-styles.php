<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if(!class_exists('FPD_Scripts_Styles')) {

	class FPD_Scripts_Styles {

		public function __construct() {

			add_action( 'init', array( &$this, 'register'), 20 );
			add_action( 'wp_enqueue_scripts',array( &$this,'enqueue_styles' ) );
			add_action( 'wp_head',array( &$this,'print_css' ), 100 );

		}

		public function register() {

			$local_test = Fancy_Product_Designer::LOCAL;
			$debug_mode = fpd_get_option('fpd_debug_mode');
			$timestamp = time();

			$fpd_js_url = $local_test ? 'https://localhost:8080/dev/FancyProductDesigner.js' : plugins_url('/assets/js/FancyProductDesigner-all.min.js', FPD_PLUGIN_ROOT_PHP);

			wp_register_style( 'fpd-js', plugins_url('/assets/css/FancyProductDesigner-all.min.css', FPD_PLUGIN_ROOT_PHP), false, $local_test ? $timestamp : Fancy_Product_Designer::JS_VERSION );
			wp_register_style( 'fpd-jssocials-theme', plugins_url('/assets/jssocials/jssocials-theme-flat.css', FPD_PLUGIN_ROOT_PHP), false, '1.4.0' );
			wp_register_style( 'fpd-jssocials', plugins_url('/assets/jssocials/jssocials.css', FPD_PLUGIN_ROOT_PHP), array('fpd-jssocials-theme'), '1.4.0' );

			//wp_register_script( 'fabric', plugins_url('/assets/js/fabric.min.js', FPD_PLUGIN_ROOT_PHP), false, '5.3.1' );
			wp_register_script( 'fpd-jssocials', plugins_url('/assets/jssocials/jssocials.min.js', FPD_PLUGIN_ROOT_PHP), false, '1.4.0' );

			$fpd_dep = array(
				'jquery',
			);

			wp_register_script( 'fpd-js-utils', plugins_url('/assets/js/utils.js', FPD_PLUGIN_ROOT_PHP), array(), $debug_mode ? $timestamp : Fancy_Product_Designer::JS_VERSION );
			wp_register_script( 'fpd-js', $fpd_js_url, $fpd_dep, $debug_mode ? $timestamp : Fancy_Product_Designer::JS_VERSION );

		}

		//includes scripts and styles in the frontend
		public function enqueue_styles() {

			global $post;

			if( !isset($post->ID) )
				return;

			if( fpd_get_option('fpd_sharing') )
				wp_enqueue_style( 'fpd-jssocials' );

			wp_enqueue_style( 'fpd-js' );
			wp_enqueue_style( 'fpd-single-product', plugins_url('/assets/css/fancy-product.css', FPD_PLUGIN_ROOT_PHP), false, Fancy_Product_Designer::VERSION );

			wp_enqueue_script( 'jquery' );

		}

		public function print_css() {

			global $post;

			if( isset($post->ID) && is_fancy_product($post->ID) ) {

				//only enqueue css and js files when necessary
				$product_settings = new FPD_Product_Settings( $post->ID );
				//get ui layout
				$ui_layout = FPD_UI_Layout_Composer::get_layout($product_settings->get_option('product_designer_ui_layout'));
				$css_str = FPD_UI_Layout_Composer::get_css_from_layout($ui_layout);

				?>
				<style type="text/css">

					<?php
						if( !empty($css_str) )
							echo $css_str;
						echo stripslashes( $ui_layout['custom_css'] );
					?>

					<?php
					//hide tools										
					if( isset($ui_layout['toolbar_exclude_tools'])  && is_array($ui_layout['toolbar_exclude_tools']) ) {

						foreach( $ui_layout['toolbar_exclude_tools'] as $tb_tool ) {
							echo 'fpd-element-toolbar .fpd-tool-'.$tb_tool.'{ display: none !important; }';
						}

					}
					?>

				</style>
				<?php

			}

		}

	}

}

new FPD_Scripts_Styles();

?>