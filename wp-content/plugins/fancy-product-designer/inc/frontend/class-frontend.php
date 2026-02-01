<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if(!class_exists('FPD_Frontend_Product')) {

	class FPD_Frontend_Product {

		public static $initial_product = null;
		public static $remove_watermark = false;
		public static $color_selection_custom_placement = null;
		public static $bulk_add_form_placement = '';
		public static $view_thumbnails_placement = '';

		public function __construct() {

			require_once(FPD_PLUGIN_DIR.'/inc/api/class-parameters.php');
			
			require_once(FPD_PLUGIN_DIR.'/inc/frontend/class-user-products.php');
			require_once(FPD_PLUGIN_DIR.'/inc/frontend/class-shortcode.php');
			require_once(FPD_PLUGIN_DIR.'/inc/frontend/class-share.php');

			add_action( 'wp_head', array( &$this, 'head_frontend') );

			//SINGLE PRODUCT
			add_filter( 'body_class', array( &$this, 'add_body_classes') );

			//action shortcode
			add_shortcode( 'fpd_action', array( &$this, 'shortcode_action_handler') );

			//module shortcode
			add_shortcode( 'fpd_module', array( &$this, 'shortcode_module_handler') );

			//print-ready download for customers
			add_action( 'wp_ajax_fpd_pr_export', array( &$this, 'ajax_create_print_ready_file' ) );
			add_action( 'wp_ajax_nopriv_fpd_pr_export', array( &$this, 'ajax_create_print_ready_file' ) );

			//instagram access token
			add_action( 'wp_ajax_fpd_insta_get_token', array( &$this, 'insta_get_token' ) );
			add_action( 'wp_ajax_nopriv_fpd_insta_get_token', array( &$this, 'insta_get_token' ) );

			//ajax: custom image upload
			add_action( 'wp_ajax_fpd_custom_uplod_file', array( &$this, 'ajax_custom_uplod_file' ) );
			add_action( 'wp_ajax_nopriv_fpd_custom_uplod_file', array( &$this, 'ajax_custom_uplod_file' ) );

			//bulk variations
			add_filter( 'woocommerce_is_sold_individually', array(&$this, 'disable_all_quantity_inputs'), 100, 2 );

			//ajax: ai service
			add_action( 'wp_ajax_fpd_ai_service', array( &$this, 'ajax_ai_service' ) );
			add_action( 'wp_ajax_nopriv_fpd_ai_service', array( &$this, 'ajax_ai_service' ) );

			add_action( 'wp_footer', array( &$this, 'footer_frontend') );

		}

		public function head_frontend() {

			if( !is_admin() ) {

				global $post;
				if( isset($post->ID) && is_fancy_product( $post->ID ) ) {

					$product_settings = new FPD_Product_Settings( $post->ID );
					$main_bar_pos = $product_settings->get_option('main_bar_position');
					if( $main_bar_pos === 'shortcode' ) {
						add_shortcode( 'fpd_main_bar', array( &$this, 'return_main_bar_container') );
					}

					//color selection output
					$color_selection_placement = $product_settings->get_option('color_selection_placement');
					if( $color_selection_placement === 'after-short-desc' ) {

						add_action( 'woocommerce_single_product_summary', array(&$this, 'cs_placement_output'), 25 );
						self::$color_selection_custom_placement = '#fpd-color-selection-placement';

					}
					else if( $color_selection_placement === 'shortcode' ) {

						add_shortcode( 'fpd_cs', array(&$this, 'cs_shortcode') );
						self::$color_selection_custom_placement = '#fpd-color-selection-placement';

					}
					else if( $color_selection_placement === 'none' || !$color_selection_placement ) {
						self::$color_selection_custom_placement = '';
					}

					//bulk variations form output
					$bulk_add_form_placement = $product_settings->get_option('bulkVariationsPlacement');
					if( $bulk_add_form_placement === 'after-short-desc' ) {

						add_action( 'woocommerce_single_product_summary', array(&$this, 'bulk_add_form_placement_output'), 26 );
						self::$bulk_add_form_placement = '#fpd-bulk-add-form-placement';

					}
					else if( $bulk_add_form_placement === 'shortcode' ) {

						add_shortcode( 'fpd_bulk_add_form', array(&$this, 'bulk_add_form_shortcode') );
						self::$bulk_add_form_placement = '#fpd-bulk-add-form-placement';

					}

					if( !empty(self::$bulk_add_form_placement) ) {
						add_action( 'fpd_product_designer_form_end', array( &$this, 'add_product_designer_form_fields' ) );
					}

					//view thumbnails
					$view_thumbnails_placement = $product_settings->get_option('view_thumbnails_placement');										
					if( $view_thumbnails_placement === 'before_fpd' ||  $view_thumbnails_placement == 'after_fpd' ) {

						if($view_thumbnails_placement === 'before_fpd')
							add_action( 'fpd_before_product_designer', array(&$this, 'view_thumbnails_placement_output') );
						else
							add_action( 'fpd_after_product_designer', array(&$this, 'view_thumbnails_placement_output') );
					
						self::$view_thumbnails_placement = '#fpd-view-thumbnails-target';

					}
					else if( $view_thumbnails_placement === 'shortcode' ) {

						add_shortcode( 'fpd_view_thumbnails', array(&$this, 'view_thumbnails_shortcode') );
						self::$view_thumbnails_placement = '#fpd-view-thumbnails-target';

					}
					else if( $view_thumbnails_placement === 'main_wrapper' ) {

						self::$view_thumbnails_placement = 'main-wrapper';

					}

					do_action( 'fpd_post_fpd_enabled', $post, $product_settings );

				}

			}

		}

		//add fancy-product class in body
		public function add_body_classes( $classes ) {

			global $post;

			if( isset($post->ID) && is_fancy_product( $post->ID ) ) {

				$product_settings = new FPD_Product_Settings( $post->ID );

				$classes[] = 'fancy-product';

				if( $product_settings->customize_button_enabled ) {
					$classes[] = 'fpd-customize-button-visible';
				}
				else {
					$classes[] = 'fpd-customize-button-hidden';
				}

				//check if tablets are supported
				if( fpd_get_option( 'fpd_disable_on_tablets' ) )
					$classes[] = 'fpd-hidden-tablets';

				//check if smartphones are supported
				if( fpd_get_option( 'fpd_disable_on_smartphones' ) )
					$classes[] = 'fpd-hidden-smartphones';

				if( $product_settings->get_option( 'fullwidth_summary' ) || ( isset($_GET['order']) && isset($_GET['item_id']) ) )
					$classes[] = 'fpd-fullwidth-summary';

				if( $product_settings->get_option('hide_product_image') || ( isset($_GET['order']) && isset($_GET['item_id']) ) )
					$classes[] = 'fpd-product-images-hidden';

				if( $product_settings->get_option('get_quote') )
					$classes[] = 'fpd-get-quote-enabled';

				if( $product_settings->get_option('customization_required') != 'none' )
					$classes[] = 'fpd-customization-required';

				if( isset($_GET['order']) && isset($_GET['item_id']) )
					$classes[] = 'fpd-order-display';

			}

			return $classes;

		}

		//color selection container
		public function cs_shortcode() {

			return $this->cs_placement_output(false);

		}

		public function cs_placement_output( $echo=true ) {

			if( $echo || $echo === '' )
				echo '<div id="fpd-color-selection-placement"></div>';
			else
				return '<div id="fpd-color-selection-placement"></div>';

		}

		public function bulk_add_form_shortcode() {

			return $this->bulk_add_form_placement_output(false);

		}

		public function bulk_add_form_placement_output( $echo=true ) {

			if( $echo || $echo === '' )
				echo '<div id="fpd-bulk-add-form-placement"></div>';
			else
				return '<div id="fpd-bulk-add-form-placement"></div>';

		}

		public function view_thumbnails_shortcode() {

			return $this->view_thumbnails_placement_output(false);

		}

		public function view_thumbnails_placement_output( $echo=true ) {

			if( $echo || $echo === '' )
				echo '<div id="fpd-view-thumbnails-target"></div>';
			else
				return '<div id="fpd-view-thumbnails-target"></div>';

		}

		//only in woocommerce
		public function add_product_designer_form_fields() {

			?>
			<input type="hidden" value="" name="fpd_quantity" />
			<?php

		}

		//return main bar container
		public function return_main_bar_container() {

			return '<div class="fpd-main-bar-position"></div>';

		}

		//the actual product designer will be added
		public static function add_product_designer() {

			global $post;

			$product_settings = new FPD_Product_Settings( $post->ID );

			if( $product_settings->show_designer() ) {

				do_action( 'fpd_before_product_designer' );

				//load product from share
				if( isset($_GET['share_id']) ) {

					$transient_key = 'fpd_share_'. sanitize_key( $_GET['share_id'] );
					$transient_val = get_transient($transient_key);
					if($transient_val !== false)
						self::$initial_product = stripslashes($transient_val['product']);

				}
				else if( isset($_GET['fpd_saved_product']) ) {

					$current_user_id = get_current_user_id();

					if( $current_user_id !== 0 ) {
						
						$saved_products = get_user_meta( $current_user_id, 'fpd_saved_product_'. sanitize_key( $_GET['fpd_saved_product'] ), true );

						if( $saved_products && isset($saved_products['fpd_data']) )
							self::$initial_product = json_encode($saved_products['fpd_data']);

					}

				}

				//get ui layout
				$ui_layout = FPD_UI_Layout_Composer::get_layout($product_settings->get_option('product_designer_ui_layout'));

				//create ID and class attribute
				$selector = 'fancy-product-designer-'.$product_settings->master_id;
				$selector_classes = str_replace( ' fpd-disable-touch-scrolling', '', $ui_layout['container_classes'] );

				//remove slashes, happening since WC3.1.0
				if( !is_null(self::$initial_product) ) {
					self::$initial_product = fpd_strip_multi_slahes(self::$initial_product);
					
				}

				//get availabe fonts
				if($product_settings->get_option('font_families[]') === false) {
					$available_fonts = FPD_Fonts::get_enabled_fonts();
				}
				else {

					$available_fonts = array();
					$enabled_fonts = FPD_Fonts::get_enabled_fonts();
					$ind_product_fonts = $product_settings->get_option('font_families[]');
					if( !is_array($ind_product_fonts) ) //only when one is set
						$ind_product_fonts = str_split($ind_product_fonts, strlen($ind_product_fonts));

					//search for font url from enabled fonts
					foreach($ind_product_fonts as $value) {
						$font_key = array_search($value, $enabled_fonts);
						if( gettype($font_key) === 'string' ) {
							$available_fonts[$font_key] = $value;
						}
						else {
							$available_fonts[] = $value;
						}
					}

				}

				//create guided tour json
				$guided_tour = null;
				if( isset($ui_layout['guided_tour']) ) {
					$guided_tour = $ui_layout['guided_tour'];

					$guided_tour = isset($guided_tour[FPD_Settings_Labels::get_current_lang_code()]) ? $guided_tour[FPD_Settings_Labels::get_current_lang_code()] : $guided_tour;
										
					if( !empty($guided_tour) )
						$guided_tour = json_encode($guided_tour, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
					else
						$guided_tour = null;

				}

				$products_json_str = array();
				$source_type = $product_settings->get_source_type();

				//get assigned categories/products
				$fancy_content_ids = fpd_has_content( $product_settings->master_id );
				$fancy_content_ids = $fancy_content_ids === false ? array() : $fancy_content_ids;
				$fancy_content_ids = apply_filters ( 'fpd_categories_custom_sort', $fancy_content_ids, $product_settings->master_id );
				
				foreach($fancy_content_ids as $fancy_content_id) {

					if( empty($source_type) || $source_type == 'category' ) { //categories are used

						$fancy_category = new FPD_Category($fancy_content_id);

						if( $fancy_category->get_data() ) {

							$fancy_products_data = $fancy_category->get_products();

							$category_products = array();
							foreach($fancy_products_data as $fancy_product_data) {

								$fpd_product = new FPD_Product( $fancy_product_data->ID );
								$category_products[] = $fpd_product->to_JSON( false );

							}

							$products_json_str[] = array(
								'category' => esc_attr($fancy_category->get_data()->title),
								'products' => $category_products
							);
						}


					}
					else {

						$fpd_product = new FPD_Product( $fancy_content_id );
						$fpd_product_json = $fpd_product->to_JSON( false );
						if( !empty($fpd_product_json) )
							$products_json_str[] = $fpd_product_json;

					}

				}

				$products_json_str = json_encode($products_json_str, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

				//output designs
				$designs_json_str = null;
				if( !intval($product_settings->get_option('hide_designs_tab')) ) {

					$selected_design_cats = array();
					$ips_design_cats = $product_settings->get_option('design_categories[]');
					$main_design_cats = fpd_get_option('fpd_design_categories');
					$main_design_cats = empty( $main_design_cats ) ? array() : fpd_string_list_to_array( $main_design_cats );

					if( !empty($ips_design_cats) )
						$selected_design_cats = $ips_design_cats;
					else if( !empty($main_design_cats) )
						$selected_design_cats = $main_design_cats;

					$fpd_designs = new FPD_Designs(
						$selected_design_cats
						,$product_settings->get_image_parameters()
					);

					$designs_json_str = $fpd_designs->get_json();

				}

				//image quality ratings
				$image_quality_rating = array();
				if(fpd_get_option('fpd_image_quality_rating')) {

					if( fpd_get_option('fpd_iqr_low_width') && fpd_get_option('fpd_iqr_low_height') ) {
						$image_quality_rating['low'] = array(
							intval(fpd_get_option('fpd_iqr_low_width')),
							intval(fpd_get_option('fpd_iqr_low_height'))
						);
					}

					if( fpd_get_option('fpd_iqr_mid_width') && fpd_get_option('fpd_iqr_mid_height') ) {
						$image_quality_rating['mid'] = array(
							intval(fpd_get_option('fpd_iqr_mid_width')),
							intval(fpd_get_option('fpd_iqr_mid_height'))
						);
					}

					if( fpd_get_option('fpd_iqr_high_height') && fpd_get_option('fpd_iqr_high_height') ) {
						$image_quality_rating['high'] = array(
							intval(fpd_get_option('fpd_iqr_high_height')),
							intval(fpd_get_option('fpd_iqr_high_height'))
						);
					}

				}

				
				//layouts
				$product_id_layouts = $product_settings->get_option('layouts');
				$fpd_layouts = array();
				if( !empty($product_id_layouts) ) {

					$fpd_product_layouts = new FPD_Product( $product_id_layouts );
					$fpd_layouts = $fpd_product_layouts->to_JSON(false);

				}

				//download filename
				$current_date = date( 'Y-m-d' );
				$download_filename = fpd_get_option( 'fpd_downloadFilename' );
				$download_filename = str_replace( '%d', $current_date, $download_filename );
				$download_filename = str_replace( '%id', $post->ID, $download_filename );
				$download_filename = str_replace( '%title', $post->post_title, $download_filename );
				$download_filename = str_replace( '%slug', $post->post_name, $download_filename );

				//names & numbers
				$namesNumbersDropdownAttr = preg_replace('/\s+/', '', $product_settings->get_option('namesNumbersDropdown'));
				$namesNumbersDropdownAttr = empty($namesNumbersDropdownAttr) ? array() : explode('|', $namesNumbersDropdownAttr);

				//dynamic views: formats
				$final_formats = array();
				$formats = $product_settings->get_option('dynamic_views_formats');
				if( !empty($formats) ) {
					$formats = explode(',', $formats);
					foreach($formats as $format) {
						$final_formats[] = array_map('intval', explode(':', $format) );
					}

					$formats = is_array($final_formats) ? $final_formats : array();
				}

				//bulk variations
				$bulk_variations = array();
				$bulk_variations_written = $product_settings->get_option('bulkVariations_written');
				if( !empty($bulk_variations_written) ) {

					$bulkVariationsWritten = $bulk_variations_written;
					$attributes = explode(';', $bulkVariationsWritten);
					foreach($attributes as $attribute) {

						$attr_props = explode('=', $attribute);
						$attr_name = $attr_props[0];
						$terms = explode('|', $attr_props[1]);
						$bulk_variations[$attr_name] = $terms;

					}

				}
				
				//pricing rules
				$used_pr_groups = [];
				$pricing_rules = $product_settings->get_option('pricing_rules[]') ? $product_settings->get_option('pricing_rules[]') : get_option('fpd_pricing_rules');
				if($pricing_rules) {

					$pricing_rules = is_string($pricing_rules) ? explode(' ', $pricing_rules) : $pricing_rules;

					$pr_groups = get_option( 'fpd_pr_groups', array() );
					if( !is_array($pr_groups) )
						$pr_groups = json_decode(fpd_strip_multi_slahes($pr_groups), true);

					foreach($pr_groups as $pr_group) {
						if( in_array(sanitize_key($pr_group['name']), $pricing_rules)) {
							array_push($used_pr_groups, $pr_group['data']);
						}
					}

				}

				$fpd_products = apply_filters( 'fpd_products_json_string', $products_json_str, $post->ID );
				$fpd_designs = apply_filters( 'fpd_designs_json_string', $designs_json_str, $post->ID );

				$custom_image_props = array_merge(
					json_decode($product_settings->get_image_parameters_string(), true),
					json_decode($product_settings->get_custom_image_parameters_string(), true)
				);

				$visibility = $product_settings->get_option('product_designer_visibility');
				//do not show in lightbox when viewing order
				$visibility = ( isset($_GET['order']) && isset($_GET['item_id']) ) ? false : $visibility;

				?>
				<div class="fpd-product-designer-wrapper">
					<div id="<?php echo $selector; ?>" class="<?php echo $selector_classes; ?>"></div>
				</div>
				<?php
								
				$configs = array(
					'selector'			=> $selector,
					'post_id'			=> $post->ID,
					'current_user_id' 	=> get_current_user_id(),
					'admin_ajax_url' 	=> admin_url('admin-ajax.php'),
					'initial_product' 	=> empty(self::$initial_product) ? null : strip_tags( self::$initial_product ), 
					'misc'	=> array(
						'fabric_js_texture_size' 	=> intval(fpd_get_option('fpd_fabricjs_texture_size')),
						'store_designs_account' 	=> fpd_get_option('fpd_accountProductStorage'),
						'customization_required' 	=> $product_settings->get_option('customization_required'),
						'social_shares'				=> fpd_string_list_to_array(fpd_get_option('fpd_sharing_social_networks')),
						'login_required' 			=> fpd_get_option('fpd_upload_designs_php_logged_in') !== 0 && !is_user_logged_in(),
						'pro_export_enabled' 		=> Fancy_Product_Designer::pro_export_enabled(),
						'export_method'				=> Fancy_Product_Designer::pro_export_enabled() ? get_option( 'fpd_pro_export_method', 'nodecanvas-svg' ) : null,
						'ajax_add_to_cart_mode' 	=> fpd_get_option( 'fpd_wc_ajax_add_to_cart_mode' ),
						'print_download_designer'	=> fpd_get_option('fpd_pro_export_print_download_designer')
					),
					'labels' => array(
						'account_storage_login_required' 	=> FPD_Settings_Labels::get_translation( 'misc', 'account_storage:login_required' ),
						'share_default_text' 				=> FPD_Settings_Labels::get_translation( 'misc', 'share:_default_text' ),
						'login_required' 					=> htmlspecialchars_decode(FPD_Settings_Labels::get_translation( 'misc', 'login_required_info' ))
					),
					'app_options' 	=> [
						'langJSON' => json_decode(FPD_Settings_Labels::get_labels_object_string()),
						'fonts' => FPD_Fonts::to_data($available_fonts),
						'facebookAppId' => fpd_get_option('fpd_facebook_app_id'),
						'instagramClientId' => fpd_get_option('fpd_instagram_client_id'),
						'instagramRedirectUri' => fpd_get_option('fpd_instagram_redirect_uri'),
						'instagramTokenUri' => admin_url('admin-ajax.php').'?action=fpd_insta_get_token',
						'hexNames' => FPD_Settings_Colors::get_hex_names_array(),
						'replaceInitialElements' => $product_settings->get_option('replace_initial_elements'),
						'uploadZonesTopped' => fpd_get_option('fpd_uploadZonesTopped'),
						'mainBarContainer' => '.fpd-main-bar-position',
						'responsive' => fpd_get_option('fpd_responsive'),
						'modalMode' => $visibility == 'lightbox' ? '#fpd-start-customizing-button' : null,
						'loadFirstProductInStage' => self::$initial_product === null ? 1 : 0,
						'watermark' => self::$remove_watermark ? '' : fpd_get_option('fpd_watermark_image'),
						'unsavedProductAlert' => fpd_get_option('fpd_unsaved_customizations_alert'),
						'hideDialogOnAdd' => $product_settings->get_option('hide_dialog_on_add'),
						//'snapGridSize' => [intval(fpd_get_option('fpd_action_snap_grid_width')), intval(fpd_get_option('fpd_action_snap_grid_height'))],
						'fitImagesInCanvas' => $product_settings->get_option('fitImagesInCanvas'),
						'inCanvasTextEditing' => $product_settings->get_option('inCanvasTextEditing'),
						'openTextInputOnSelect' => $product_settings->get_option('openTextInputOnSelect'),
						'saveActionBrowserStorage' => fpd_get_option('fpd_accountProductStorage') ? 0 : 1,
						'uploadAgreementModal' => fpd_get_option('fpd_uploadAgreementModal'),
						'autoOpenInfo' => fpd_get_option('fpd_autoOpenInfo'),
						'allowedImageTypes' => fpd_get_option('fpd_allowedImageTypes', false),
						'replaceColorsInColorGroup' => fpd_get_option('fpd_replaceColorsInColorGroup'),
						'pixabayApiKey' => fpd_get_option('fpd_pixabayApiKey'),
						'pixabayHighResImages' => fpd_get_option('fpd_pixabayHighResImages'),
						'pixabayLang' => fpd_get_option('fpd_pixabayLang'),
						'sizeTooltip' => fpd_get_option('fpd_imageSizeTooltip'),
						'applyFillWhenReplacing' => fpd_get_option('fpd_applyFillWhenReplacing'),
						'applySizeWhenReplacing' => fpd_get_option('fpd_applySizeWhenReplacing'),
						'highlightEditableObjects' => fpd_get_option('fpd_highlightEditableObjects'),
						'layouts' => $fpd_layouts,
						'disableTextEmojis' => fpd_get_option('fpd_disableTextEmojis'),
						'smartGuides' => fpd_get_option('fpd_smartGuides'),
						'colorPickerPalette' => fpd_string_to_array(fpd_get_option('fpd_color_colorPickerPalette')),
						'customizationRequiredRule' => $product_settings->get_option('customization_required'),
						'swapProductConfirmation' => fpd_get_option('fpd_swapProductConfirmation'),
						'toolbarTextareaPosition' => fpd_get_option('fpd_toolbarTextareaPosition'),
						'textLinkGroupProps' => fpd_get_option('fpd_textLinkGroupProps', false),
						'dynamicDesigns' => json_decode(get_option('fpd_dynamic_designs_modules', '{}')),
						'textTemplates' => json_decode(fpd_get_option('fpd_text_templates', '{}')),
						'multiSelection' => fpd_get_option('fpd_multiSelection'),
						'maxCanvasHeight' => intval(fpd_get_option('fpd_maxCanvasHeight')) / 100,
						'mobileGesturesBehaviour' => fpd_get_option('fpd_mobileGesturesBehaviour'),
						'cornerControlsStyle' => fpd_get_option('fpd_corner_controls_style'),
						'imageQualityRatings' => $image_quality_rating,
						'downloadFilename' => $download_filename,
						'autoFillUploadZones' => fpd_get_option('fpd_autoFillUploadZones'),
						'dragDropImagesToUploadZones' => fpd_get_option('fpd_dragDropImagesToUploadZones'),
						'fileServerURL' => admin_url('admin-ajax.php').'?action=fpd_custom_uplod_file&filter='.$product_settings->get_option('imagick_filter'),
						'elementParameters' => [
							'originX' => fpd_get_option('fpd_common_parameter_originX'),
							'originY' => fpd_get_option('fpd_common_parameter_originY'),
						],
						'imageParameters' => [
							'colors' => fpd_get_option('fpd_all_image_colors'),
							'colorLinkGroup' => fpd_get_option('fpd_all_image_colorLinkGroup'),
							'colorPrices' => $product_settings->get_option('enable_image_color_prices') ? FPD_Settings_Colors::get_color_prices() : array(),
							'replaceInAllViews' => $product_settings->get_option('designs_parameter_replaceInAllViews'),
							'patterns' => fpd_check_file_list($product_settings->get_option('designs_parameter_patterns'), FPD_WP_CONTENT_DIR . '/uploads/fpd_patterns_svg/'),
							'padding' =>  0
						],
						'textParameters' => [
							'padding' => intval(fpd_get_option('fpd_padding_controls')),
							'fontFamily' => get_option('fpd_font', 'Arial'),
							'colorPrices' => $product_settings->get_option('enable_text_color_prices') ? FPD_Settings_Colors::get_color_prices() : array(),
							'replaceInAllViews' => $product_settings->get_option('custom_texts_parameter_replaceInAllViews'),
							'patterns' => fpd_check_file_list($product_settings->get_option('custom_texts_parameter_patterns'), FPD_WP_CONTENT_DIR . '/uploads/fpd_patterns_text/'),
							'strokeColors' => FPD_Parameters::parse_property('strokeColors', fpd_get_option('fpd_all_text_strokeColors'), 'text'),
							'colors' => fpd_get_option('fpd_all_text_colors'),
							'maxCurveRadius' => fpd_get_option('fpd_custom_texts_parameter_maxCurveRadius')
						],
						'customImageParameters' => $custom_image_props,
						'customTextParameters' => json_decode($product_settings->get_custom_text_parameters_string()),
						'fabricCanvasOptions' => [
							'allowTouchScrolling' => fpd_get_option('fpd_canvas_touch_scrolling'),
							'perPixelTargetFind' => $product_settings->get_option('canvas_per_pixel_detection'),
						],
						'qrCodeProps' => [
							'price' => floatval(fpd_get_option('fpd_qr_code_prop_price')),
							'resizeToW' => intval(fpd_get_option('fpd_qr_code_prop_resizeToW')),
							'resizeToH' => intval(fpd_get_option('fpd_qr_code_prop_resizeToW')),
							'draggable' => fpd_get_option('fpd_qr_code_prop_draggable'),
							'resizable' => fpd_get_option('fpd_qr_code_prop_resizable'),
							'boundingBox' => isset($custom_image_props['boundingBox']) ? $custom_image_props['boundingBox'] : null,
							'boundingBoxMode' => isset($custom_image_props['boundingBoxMode']) ? $custom_image_props['boundingBoxMode'] : 'clipping'
						],
						'boundingBoxProps' => [
							'strokeWidth' => intval(fpd_get_option('fpd_bounding_box_stroke_width'))
						],
						'guidedTour' => $guided_tour ? json_decode($guided_tour, true) : null,
						'productsJSON' => json_decode($fpd_products),
						'designsJSON' => json_decode($fpd_designs),
						'namesNumbersDropdown' => $namesNumbersDropdownAttr,
						'namesNumbersEntryPrice' => floatval($product_settings->get_option('namesNumbersEntryPrice')),
						'colorSelectionPlacement' => self::$color_selection_custom_placement,
						'enableDynamicViews' => intval($product_settings->get_option('enableDynamicViews')),
						'dynamicViewsOptions' => [
							'unit' 			=> $product_settings->get_option('dynamic_views_unit'),
							'formats' 		=> $final_formats,
							'pricePerArea' 	=> $product_settings->get_option('dynamic_views_pricePerArea'),
							'minWidth' 		=> $product_settings->get_option('dynamic_views_minWidth'),
							'minHeight'		=> $product_settings->get_option('dynamic_views_minHeight'),
							'maxWidth' 		=> $product_settings->get_option('dynamic_views_maxWidth'),
							'maxHeight' 	=> $product_settings->get_option('dynamic_views_maxHeight'),
						],
						'bulkVariationsPlacement' => self::$bulk_add_form_placement,
						'bulkVariations' => $bulk_variations,
						'pricingRules' => $used_pr_groups,
						'canvasHeight' => fpd_get_option('fpd_canvasHeight'),
						'responsiveBreakpoints' => [
							'small' => intval(fpd_get_option('fpd_responsive_breakpoint_small')),
							'medium' => intval(fpd_get_option('fpd_responsive_breakpoint_medium'))
						],
						'customTextAsTextbox' => fpd_get_option( 'fpd_customTextAsTextbox' ),
						'viewThumbnailsWrapper' => self::$view_thumbnails_placement,
						'rulerUnit' => fpd_get_option( 'fpd_rulerUnit' ),
						'rulerPosition' => $product_settings->get_option('rulerPosition'),
						'rulerFixed' => fpd_get_option( 'fpd_rulerFixed' ),
						'cropMasks' => fpd_get_files_from_uploads_by_type('fpd_masks', array('svg'))
					]
				);

				$genius_license_key = get_option( 'fpd_genius_license_key', '' );
				if( !empty( $genius_license_key ) ) {

					$configs['app_options']['aiService'] = array(
						'serverURL' 	=> admin_url('admin-ajax.php').'?action=fpd_ai_service',
						'superRes' 	=> fpd_get_option('fpd_ai_superRes'),
						'removeBG' 	=> fpd_get_option('fpd_ai_removeBG'),
						'text2Img' 	=> fpd_get_option('fpd_ai_text2Img')
					);

				}

				//app options from layout
				$layout_options = is_array($ui_layout['plugin_options']) ? $ui_layout['plugin_options'] : array();
				$configs['app_options'] = array_merge($configs['app_options'], $layout_options);

				$configs = apply_filters( 'fpd_frontend_setup_configs', $configs );				

				$fpd_js_deps = array(
					'fpd-js'
				);

				if( fpd_get_option('fpd_sharing') )
					$fpd_js_deps[] = 'fpd-jssocials';
				

				wp_enqueue_script( 
					'fpd-frontend', 
					plugins_url('/assets/js/frontend.js', FPD_PLUGIN_ROOT_PHP), 
					$fpd_js_deps, 
					Fancy_Product_Designer::VERSION 
				);
				
				wp_localize_script( 'fpd-frontend', 'fpd_setup_configs', $configs);

				if( fpd_get_option('fpd_sharing') ) {

					wp_enqueue_script( 
						'fpd-frontend-share', 
						plugins_url('/assets/js/frontend-share.js', FPD_PLUGIN_ROOT_PHP), 
						array(), 
						Fancy_Product_Designer::VERSION 
					);

				}

				do_action('fpd_after_product_designer', $post);

			}

		}

		//adds a customize button to the summary
		public static function add_customize_button( ) {

			/* The above code is declaring a global variable called  and then creating a new instance of
			the FPD_Product_Settings class with the ID of the current post. */
			global $post;			
			$product_settings = new FPD_Product_Settings($post->ID);

			$printful_enabled = get_post_meta( $post->ID, '_fpd_printful_product', true ) == '1';

			$fancy_content_ids = fpd_has_content( $post->ID );
			if( !$printful_enabled && (!is_array($fancy_content_ids) || sizeof($fancy_content_ids) === 0) ) { return; }

			if( $product_settings->customize_button_enabled ) {

				$button_class = trim(fpd_get_option('fpd_start_customizing_css_class')) == '' ? 'fpd-start-customizing-button' : fpd_get_option('fpd_start_customizing_css_class');
				$label = FPD_Settings_Labels::get_translation('misc', 'customization_button');

				$inline_js = '';
				if( $product_settings->get_option('product_designer_visibility') == 'lightbox' )
					$inline_js = 'onclick="return false"';
				else
					$button_class .= ' fpd-next-page';

				?>
				<a href="<?php echo esc_url( add_query_arg( 'start_customizing', '' ) ); ?>" id="fpd-start-customizing-button" class="<?php echo $button_class; ?>" <?php echo $inline_js; ?>><?php echo $label; ?></a>
				<?php

			}

		}

		public function shortcode_action_handler( $atts ) {

			extract( shortcode_atts( array(
				'type' => null,
				'layout' => 'icon-tooltip' //icon-tooltip, icon-text, text
			), $atts, 'fpd_action' ) );

			ob_start();
			?>
			<span class="fpd-sc-action-placeholder" data-action="<?php echo esc_attr( $type ); ?>" data-layout="<?php echo esc_attr( $layout ); ?>"></span>
			<?php
			$output = ob_get_contents();
			ob_end_clean();

			return $output;

		}

		public function shortcode_module_handler( $atts ) {

			extract( shortcode_atts( array(
				'type' => null,
				'css' => ''
			), $atts, 'fpd_module' ) );

			ob_start();
			?>
			<div class="fpd-sc-module-wrapper fpd-container" data-type="<?php echo esc_attr( $type ); ?>" style="<?php echo esc_attr( $css ); ?>"></div>
			<?php
			$output = ob_get_contents();
			ob_end_clean();

			return $output;

		}

		public function ajax_create_print_ready_file() {

			if( isset($_POST['print_data']) &&  Fancy_Product_Designer::pro_export_enabled() ) {

				$print_data = json_decode(stripslashes($_POST['print_data']), true);
				$file = Fancy_Product_Designer::create_print_ready_file( $print_data );
				$file_url = content_url( '/fancy_products_orders/print_ready_files/' . $file );

				echo json_encode(array(
					'file_url' => $file_url
				));

			}

			die;

		}

		public function insta_get_token() {

			$client_app_id = isset($_GET['client_app_id']) ? sanitize_key( $_GET['client_app_id'] ) : null;
			$redirect_uri = isset($_GET['redirect_uri']) ? sanitize_url( $_GET['redirect_uri'] ) : null;
			$code = isset($_GET['code']) ? sanitize_key( $_GET['code'] ) : null;
			$client_secret = fpd_get_option('fpd_instagram_secret_id');

			if( $client_app_id && $redirect_uri && $code && !empty($client_secret) ) {

				$url = 'https://api.instagram.com/oauth/access_token';

				$curlPost = 'client_id='. $client_app_id . '&redirect_uri=' . $redirect_uri . '&app_secret=' . $client_secret . '&code='. $code . '&grant_type=authorization_code';
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
				$data = json_decode(curl_exec($ch), true);
				$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				curl_close($ch);

				echo json_encode( $data );

			}
			else {

				echo json_encode(array(
					'error_message' => 'Client App ID, Redirect URI or Code is not set!'
				));

			}

			die;
		}

		public function ajax_custom_uplod_file() {

			if( fpd_get_option('fpd_upload_designs_php_logged_in') ) {

				if( is_user_logged_in() )
					require_once(FPD_PLUGIN_DIR . '/inc/file-handler.php');

			}
			else {
				require_once(FPD_PLUGIN_DIR . '/inc/file-handler.php');
			}

			die;

		}

		public function ajax_ai_service() {

			$license_key = get_option('fpd_genius_license_key', $GLOBALS['FPD_GENIUS_LICENSE_KEY']);

			//define the Genius license key
			define( 'FPD_GENIUS_LICENSE_KEY', $license_key );

			//define the domain that is registered for the license
			define( 'FPD_GENIUS_DOMAIN', fpd_get_domain_from_url( get_site_url() ) );

			//define the path to the uploads folder
			define( 'FPD_UPLOADS_DIR', FPD_WP_CONTENT_DIR . '/uploads/fpd_ai_uploads/' ); 

			//define the public url of the uploads folder
			define( 'FPD_UPLOADS_DIR_URL', content_url() . '/uploads/fpd_ai_uploads/' ); 

			require_once(FPD_PLUGIN_DIR.'/inc/ai/class-ai-service.php');

			$input = file_get_contents('php://input');
			$payload = json_decode($input, true);

			$ai_service = new FPD_AI_Service($payload);

			die;

		}

		//hide wc quantity on single product pages, if bulk variations form is enabled
	    public function disable_all_quantity_inputs( $return, $product ) {

		    if( is_product() && is_fancy_product($product->get_ID()) ) {

			    $product_settings = new FPD_Product_Settings( $product->get_id() );
			    $bulk_form_placement = $product_settings->get_option('bulkVariationsPlacement');
				
			    if( $bulk_form_placement === 'after-short-desc' || $bulk_form_placement === 'shortcode' )
			    	return true;

		    }

		    return $return;

		}

		public function footer_frontend() {

			global $post;

			if( !$post ) return;

			$product_settings = new FPD_Product_Settings($post->ID);
			
			//display one for admin one single post/page with designer enabled
			if( current_user_can(Fancy_Product_Designer::CAPABILITY) 
				&& is_single()
				&& $product_settings->show_designer()
			) {

				if( !wp_script_is('fpd-frontend') )
					require_once(FPD_PLUGIN_DIR.'/inc/frontend/view-notification.php');

			}
			

		}

	}
}

new FPD_Frontend_Product();

?>