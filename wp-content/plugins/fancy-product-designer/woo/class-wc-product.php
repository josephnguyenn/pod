<?php


if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( !class_exists('FPD_WC_Product') ) {

	class FPD_WC_Product {

		public function __construct() {

			//product listing
			add_filter( 'woocommerce_get_price_html', array( &$this, 'product_listing_price'), 10, 2 );

			//wp_head
			add_action( 'fpd_post_fpd_enabled', array( &$this, 'head_frontend'), 10, 2 );

			add_filter( 'post_class', array( &$this, 'product_css_class') );

			add_filter( 'woocommerce_product_single_add_to_cart_text', array( &$this, 'add_to_cart_text'), 20, 2 );
			//before product container
			add_action( 'woocommerce_before_single_product', array( &$this, 'before_product_container'), 1 );

			add_filter( 'fpd_frontend_setup_configs', array( &$this, 'add_app_options') );
			add_action( 'fpd_before_product_designer', array( &$this, 'before_product_designer'), 1 );
			add_action( 'fpd_after_product_designer', array( &$this, 'after_product_designer'), 1 );

			//add customize button
			$customize_btn_pos = fpd_get_option('fpd_start_customizing_button_position');
			if( $customize_btn_pos == 'under-short-desc' ) {
				add_action( 'woocommerce_single_product_summary', 'FPD_Frontend_Product::add_customize_button', 25 );
			}
			else if( $customize_btn_pos == 'before-add-to-cart-button') {
				add_action( 'woocommerce_before_add_to_cart_button', 'FPD_Frontend_Product::add_customize_button', 0 );
			}
			else {
				add_action( 'woocommerce_after_add_to_cart_button', 'FPD_Frontend_Product::add_customize_button', 0 );
			}

			//add additional form fields to cart form
			add_action( 'woocommerce_before_add_to_cart_button', array( &$this, 'add_product_designer_form') );

			//change product by variation
			add_filter( 'woocommerce_available_variation', array( &$this, 'set_variation_meta'), 20, 3 );
			add_action( 'woocommerce_after_variations_form', array( &$this, 'add_variation_handler') );

			//enable share for wc
			if( fpd_get_option('fpd_sharing') ) {
				add_action( 'woocommerce_share' , array( &$this, 'add_share' ) );
			}

			if(fpd_get_option('fpd_accountProductStorage')) {

				//modify account menu
				add_filter( 'woocommerce_account_menu_items', array( &$this, 'account__menu_items' ), 10, 1 );
				add_action( 'woocommerce_account_saved_products_endpoint', array( &$this, 'display_saved_product_in_account' ) );

			}

		}

		public function product_listing_price( $price, $product ) {


			if( is_shop() && is_fancy_product( $product->get_id() ) ) {

				$product_settings = new FPD_Product_Settings( $product->get_id() );

				if( $product_settings->get_option('get_quote') )
					$price = '';

			}

			return $price;

		}

		public function head_frontend( $post, $product_settings ) {

			$product_settings = new FPD_Product_Settings( $post->ID );
			$main_bar_pos = $product_settings->get_option('main_bar_position');

			if( $main_bar_pos === 'after_product_title' ) {
				add_action( 'woocommerce_single_product_summary', array( &$this, 'add_main_bar_container'), 7 );
			}
			else if( $main_bar_pos === 'after_excerpt' ) {
				add_action( 'woocommerce_single_product_summary', array( &$this, 'add_main_bar_container'), 25 );
			}

		}

		public function product_css_class( $classes ) {

			global $post;

			if( $post && isset($post->ID) ) {

				$product_settings = new FPD_Product_Settings( $post->ID );
				$cb_var_needed = $product_settings->get_option('wc_customize_variation_needed');

				if( $cb_var_needed ) {
					$classes[] = 'fpd-variation-needed';
				}

			}

			return $classes;

		}

		//add a main bar container
		public function add_main_bar_container() {

			echo '<div class="fpd-main-bar-position"></div>';

		}

		//custom text for the add-to-cart button in single page
		public function add_to_cart_text( $text, $product ) {

			if( is_fancy_product( $product->get_id() ) ) {

				$product_settings = new FPD_Product_Settings( $product->get_id() );

				if( is_product() ) { //only change text if on single product page and get quote is enabled
					if( $product_settings->get_option('get_quote') )
						return FPD_Settings_Labels::get_translation( 'woocommerce', 'get_a_quote' );
				}

			}

			return $text;

		}

		public function before_product_container() {

			global $post;

			if( is_fancy_product( $post->ID ) ) {

				//add product designer
				$product_settings = new FPD_Product_Settings( $post->ID );
				$position = $product_settings->get_option('placement');

				if( $position  == 'fpd-replace-image') {
					add_action( 'woocommerce_before_single_product_summary', 'FPD_Frontend_Product::add_product_designer', 15 );
				}
				else if( $position  == 'fpd-under-title') {
					add_action( 'woocommerce_single_product_summary', 'FPD_Frontend_Product::add_product_designer', 6 );
				}
				else if( $position  == 'fpd-after-summary') {
					add_action( 'woocommerce_after_single_product_summary', 'FPD_Frontend_Product::add_product_designer', 1 );
				}
				else {
					add_action( 'fpd_product_designer', 'FPD_Frontend_Product::add_product_designer' );
				}

				//remove product image, there you gonna see the product designer
				if( $product_settings->get_option('hide_product_image') || ($position == 'fpd-replace-image' && (!$product_settings->customize_button_enabled)) ) {
					remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
				}

			}
		}

		public function before_product_designer( $post ) {

			if( get_post_type( $post ) !== 'product' )
				return;

			global $product, $woocommerce;

			//added to cart, recall added product
			if( isset($_POST['fpd_product']) ) {

				if( fpd_get_option('fpd_wc_add_to_cart_product_load') == 'customized-product' ) {

					$views = strip_tags( $_POST['fpd_product'] );
					FPD_Frontend_Product::$initial_product = stripslashes($views);

				}
				

			}
			else if( isset($_GET['cart_item_key']) ) {

				//load from cart item
				$cart = $woocommerce->cart->get_cart();				

				$cart_item = $woocommerce->cart->get_cart_item( sanitize_key( $_GET['cart_item_key'] ) );
				if( !empty($cart_item) ) {

					if( isset($cart_item['fpd_data']) ) {

						if( isset( $cart_item['quantity'] ) )
							$_REQUEST['quantity'] = $cart_item['quantity'];

						$views = $cart_item['fpd_data']['fpd_product'];
						FPD_Frontend_Product::$initial_product = stripslashes($views);

						add_filter( 'woocommerce_product_single_add_to_cart_text', array( &$this, 'change_add_to_cart_btn_text') );
					}

				}
				else {

					//cart item could not be found
					echo '<p><strong>';
					echo FPD_Settings_Labels::get_translation( 'woocommerce', 'cart_item_not_found' );
					echo '</strong></p>';
					return;

				}

			}
			else if( isset($_GET['order']) && isset($_GET['item_id']) ) {

				$order = wc_get_order( intval( $_GET['order'] ) );

				//check if order belongs to customer
				if(!fpd_get_option('fpd_order_login_required')
					|| current_user_can(Fancy_Product_Designer::CAPABILITY)
					|| $order->get_user_id() === get_current_user_id()
				) {

					$item_meta = fpd_wc_get_order_item_meta( intval( $_GET['item_id'] ) );

					//V3.4.9: only order is stored in fpd_data
					FPD_Frontend_Product::$initial_product = is_array($item_meta) ? $item_meta['fpd_product'] : $item_meta;

					if( $order && $order->is_paid() ) {
						FPD_Frontend_Product::$remove_watermark = true;

						if( $product->is_downloadable() ) :
						?>
						<a href="#" id="fpd-extern-download-pdf" class="<?php echo trim(fpd_get_option('fpd_start_customizing_css_class')); ?>" style="display: inline-block; margin: 10px 10px 10px 0;">
							<?php echo FPD_Settings_Labels::get_translation( 'actions', 'download' ); ?>
						</a>
						<?php
						endif;

					}
					else {
						FPD_Frontend_Product::$remove_watermark = false;
					}

					$allowed_edit_status = array(
						'pending',
						'processing',
						'on-hold'
					);

					if( fpd_get_option('fpd_order_save_order') && in_array($order->get_status(), $allowed_edit_status) ) : ?>
						<a href="#" id="fpd-save-order" class="<?php echo trim(fpd_get_option('fpd_start_customizing_css_class')); ?>"  style="display: inline-block; margin: 10px 10px 10px 0;">
							<?php echo FPD_Settings_Labels::get_translation( 'woocommerce', 'save_order' ); ?>
						</a>
					<?php endif;

				}

			}
			else if( isset($_GET['start_customizing']) && isset($_GET['fpd_product']) ) {

				$get_fpd_product_id = intval($_GET['fpd_product']);
				
				if( FPD_Product::exists($get_fpd_product_id) ) {
					$fancy_product = new FPD_Product( $get_fpd_product_id );
					FPD_Frontend_Product::$initial_product = $fancy_product->to_JSON();
					
				}

			}

		}

		public function change_add_to_cart_btn_text( $text ) {

			$save_txt = FPD_Settings_Labels::get_translation( 'woocommerce', 'add_to_cart:save' );
			if( !empty($save_txt) )
				$text = $save_txt;
			
			return $text;

		}

		public function add_app_options($configs) {			

			if( function_exists('get_woocommerce_currency_symbol') ) {

				$currencyPos = get_option('woocommerce_currency_pos');
				$currencySymbol = '<span class="woocommerce-Price-currencySymbol">'.get_woocommerce_currency_symbol().'</span>';

				if($currencyPos == 'right') {
					$currencyFormat = '%d' . $currencySymbol;
				}
				else if($currencyPos == 'right_space') {
					$currencyFormat = '%d' . ' ' . $currencySymbol;
				}
				else if($currencyPos == 'left_space') {
					$currencyFormat = $currencySymbol . ' ' . '%d';
				}
				else {
					$currencyFormat = $currencySymbol . '%d';
				}

				$configs['app_options']['priceFormat'] = array(
					'currency' 		=> $currencyFormat,
					'decimalSep' 	=> get_option('woocommerce_price_decimal_sep'),
					'thousandSep' 	=> get_option('woocommerce_price_thousand_sep')
				);				

			}			

			return $configs;

		}

		public function after_product_designer( $post ) {

			if( is_admin() || get_post_type( $post ) !== 'product' )
				return;

			global $product;
            
            if( !method_exists($product, 'get_id') ) return;
            
			$product_settings = new FPD_Product_Settings( $product->get_id() );

			$product_price = wc_get_price_to_display( $product );
			$product_price = $product_price && is_numeric($product_price) ? $product_price : 0;

			wp_enqueue_script( 
				'fpd-frontend-woo', 
				plugins_url('/assets/js/frontend-woo.js', FPD_PLUGIN_ROOT_PHP), 
				array('fpd-js-utils'), 
				Fancy_Product_Designer::VERSION 
			);

			$woo_configs = array(
				'options' => array(
					'number_of_decimals' 			=> !apply_filters( 'woocommerce_price_trim_zeros', false ) ? intval(get_option('woocommerce_price_num_decimals')) : 0,
					'wcPrice'						=> $product_price,
					'cart_thumbnail_width' 			=> intval(fpd_get_option('fpd_wc_cart_thumbnail_width')),
					'cart_thumbnail_height' 		=> intval(fpd_get_option('fpd_wc_cart_thumbnail_height')),
					'lightbox_add_to_cart' 			=> fpd_get_option('fpd_lightbox_add_to_cart'),
					'disable_price_calculation' 	=> fpd_get_option('fpd_wc_disable_price_calculation'),
					'price_selector' 				=> apply_filters( 'fpd_price_selector', '.price:first .woocommerce-Price-amount:last' ),
					'lightbox_update_product_image' => fpd_get_option('fpd_lightbox_update_product_image'),
					'replace_initial_elements' 		=> $product_settings->get_option('replace_initial_elements'),
					'product_image_css_selector' 	=> fpd_get_option('fpd_wc_product_image_css_selector'),
				),
				'labels' => array(
					'add_to_cart' 			=> FPD_Settings_Labels::get_translation( 'woocommerce', 'add_to_cart' ),
					'order_saved' 			=> FPD_Settings_Labels::get_translation( 'woocommerce', 'order_saved' ),
					'order_saving_failed' 	=> FPD_Settings_Labels::get_translation( 'woocommerce', 'order_saving_failed' ),
					'loading_product' 		=> FPD_Settings_Labels::get_translation( 'woocommerce', 'loading_product' ),
					'product_loading_fail'	=> FPD_Settings_Labels::get_translation( 'woocommerce', 'product_loading_fail' )
				)
			);
			wp_localize_script( 'fpd-frontend-woo', 'fpd_woo_configs', $woo_configs);

		}

		//the additional form fields
		public function add_product_designer_form() {

			global $post;
			$product_settings = new FPD_Product_Settings($post->ID);

			if( $product_settings->show_designer() ) {
				?>
				<input type="hidden" value="<?php echo esc_attr( $post->ID ); ?>" name="add-to-cart" />
				<input type="hidden" value="" name="fpd_product" />
				<input type="hidden" value="<?php echo isset($_GET['cart_item_key']) ? esc_attr( $_GET['cart_item_key'] ) : ''; ?>" name="fpd_remove_cart_item" />
				<input type="hidden" value="" name="fpd_print_order" />
				<?php

				if( !fpd_get_option('fpd_wc_disable_price_calculation') )
					echo '<input type="hidden" value="" name="fpd_product_price" />';

				if( fpd_get_option('fpd_cart_custom_product_thumbnail') || fpd_get_option('fpd_order_product_thumbnail') )
					echo '<input type="hidden" value="" name="fpd_product_thumbnail" />';

				do_action('fpd_product_designer_form_end', $product_settings);
			}

		}

		//add variation product id to variation attributes
		public function set_variation_meta( $attrs, $instance, $variation ) {

			$variationProduct = get_post_meta( $variation->get_id(), 'fpd_variation_product', true );
			if( $variationProduct && !empty($variationProduct) )
				$attrs['fpd_variation_product_id'] = intval($variationProduct);

			return $attrs;

		}

		public function add_variation_handler() {

			global $product;

			wp_enqueue_script( 
				'fpd-frontend-woo-variations', 
				plugins_url('/assets/js/frontend-woo-variations.js', FPD_PLUGIN_ROOT_PHP), 
				array(), 
				Fancy_Product_Designer::VERSION 
			);

			wp_add_inline_script( 
				'fpd-frontend-woo-variations', 
				'var fpdProductId = '.$product->get_id().';', 
				'before' 
			);

		}

		public function add_share() {

			global $post;

			$product_settings = new FPD_Product_Settings( $post->ID );
			if( $product_settings->show_designer() )
				echo FPD_Share::get_share_html();

		}

		public function account__menu_items( $items ) {

			$index_logout = array_search("customer-logout",array_keys($items));
			$menu_item = array('saved_products' =>  FPD_Settings_Labels::get_translation( 'misc', 'account_storage:saved_products' ) );

			$items = array_slice($items, 0, $index_logout, true) + $menu_item +  array_slice($items, $index_logout, count($items) - 1, true) ;

			return $items;

		}

		public function display_saved_product_in_account() {

			echo do_shortcode( '[fpd_saved_products]' );

		}

	}

}

new FPD_WC_Product();

?>