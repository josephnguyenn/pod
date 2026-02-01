<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( !class_exists('FPD_Settings_General') ) {

	class FPD_Settings_General {

		public static function get_options() {

			$nn_module_placeholder = fpd_get_option('fpd_namesNumbersDropdown');			
			$nn_module_placeholder = empty( $nn_module_placeholder ) ? __('e.g. S | M | L', 'radykal') : $nn_module_placeholder;

			return apply_filters('fpd_general_settings', array(

				'display' => array(

					array(
						'title' 		=> __( 'Main User Interface', 'radykal' ),
						'description' 		=> sprintf( __( 'Create and customize the product designer user interface with the <a href="%s" %s>UI Composer</a>.', 'radykal' ), 'admin.php?page=fpd_ui_layout_composer', 'style="font-weight: bold;"'),
						'id' 			=> 'fpd_product_designer_ui_layout',
						'default'		=> 'default',
						'type' 			=> 'select',
						'options'   	=> self::get_saved_ui_layouts()
					),

					array(
						'title' 		=> __( 'Open Product Designer in...', 'radykal' ),
						'description' 		=> __( 'By default the product designer will display while page is loading. But you can also display the designer in a lightbox or in the next page when the user clicks on the customize button.', 'radykal' ),
						'id' 			=> 'fpd_product_designer_visibility',
						'default'		=> 'page',
						'type' 			=> 'select',
						'options'   	=> self::get_product_designer_visibilities()
					),

					array(
						'title' 		=> __( 'Main Bar Placement', 'radykal' ),
						'description' 		=> __( 'Set the Placement for the main bar. The sidebar layout will be used.', 'radykal' ),
						'id' 			=> 'fpd_main_bar_position',
						'default'		=> 'default',
						'type' 			=> 'select',
						'options'   	=> self::get_main_bar_positions()
					),

					array(
						'title' 		=> __( 'Button CSS Classes', 'radykal' ),
						'description' 		=> __( 'These CSS clases will be added e.g. to the customisation button. Add class names without the dot.', 'radykal' ),
						'id' 			=> 'fpd_start_customizing_css_class',
						'default'		=> 'fpd-secondary-btn',
						'type' 			=> 'text',
					),

					array(
						'title' 		=> __( 'Multi-Languages', 'radykal' ),
						'description' 		=> __( 'Enable multiple languages in Labels settings. This option can be used with translation plugins like WPML.', 'radykal' ),
						'id' 			=> 'fpd_multi_languages',
						'default'		=> 'no',
						'type' 			=> 'checkbox',
						'relations' => array(
                            'fpd_languages' => true,
                        )
					),

					array(
						'title' 		=> __( 'Languages', 'radykal' ),
						'description' 	=> __( 'Select the languages for the Labels.', 'radykal' ),
						'id' 			=> 'fpd_languages',
						'default'		=> array('en_US'),
						'options'		=> array(),
						'type' 			=> 'multiselect',
						'unbordered'    => true
					),

				), //display

				'modules' => array(

					array(
						'title' 		=> __('Image Uploads', 'radykal'),
						'type' 			=> 'section-title',
						'id' 			=> 'image-upload-section'
					),
                    
                    array(
                        'title' 	=> __( 'Allowed File Types', 'radykal' ),
                        'id' 		=> 'fpd_allowedImageTypes',
                        'css' 		=> 'width:300px;',
                        'default'	=> array('jpeg', 'png', 'svg', 'pdf'),
                        'type' 		=> 'multiselect',
                        'options'   => array(
                            "jpeg" 		=> 'JPEG',
                            "png" 		=> 'PNG',
                            "svg" 		=> 'SVG',
                            "pdf" 		=> 'PDF'
                        )
                    ),

					array(
						'title' 	=> __( 'Login Required', 'radykal' ),
						'description'	 	=> __( 'Users must create an account in your Wordpress site and need to be logged-in to upload images.', 'radykal' ),
						'id' 		=> 'fpd_upload_designs_php_logged_in',
						'default'	=> 'no',
						'type' 		=> 'checkbox',
                        'column_width' => 'eight',
					),

					array(
						'title' 	=> __( 'Confirm Image Rights', 'radykal' ),
						'description'	 => __( 'Before the image is uploaded the user needs to confirm an agreement to have the right to use the image.', 'radykal' ),
						'id' 		=> 'fpd_uploadAgreementModal',
						'default'	=> 'no',
						'type' 		=> 'checkbox',
                        'column_width' => 'eight',
					),
                    
                    array(
                        'title' 	=> __( 'Image Quality Rating', 'radykal' ),
                        'description'	 	=> __( 'Customers will see star ratings which rate the quality of the uploaded image. If you enable this option, you will see six input fields below. Here you can define the low, mid and high image quality in pixels. E.g.: You define a width of 500 and height of 600 for low quality, any image with a size below of 500x600px will get one star rating.', 'radykal' ),
                        'id' 		=> 'fpd_image_quality_rating',
                        'default'	=> 'no',
                        'type' 		=> 'checkbox',
                        'relations' => array(
                            'fpd_iqr_low_width' => true,
                            'fpd_iqr_low_height' => true,
                            'fpd_iqr_mid_width' => true,
                            'fpd_iqr_mid_height' => true,
                            'fpd_iqr_high_width' => true,
                            'fpd_iqr_high_height' => true,
                        )
                    ),
                    
                    array(
                        'title' => __( 'Image Quality Low Width', 'radykal' ),
                        'id' 		=> 'fpd_iqr_low_width',
                        'default'	=> '',
                        'type' 		=> 'number',
                        'custom_attributes' => array(
                            'min' 	=> 1,
                            'step' 	=> 1
                        ),
                        'column_width' => 'eight',
                    ),
                    
                    array(
                        'title' => __( 'Image Quality Low Height', 'radykal' ),
                        'id' 		=> 'fpd_iqr_low_height',
                        'default'	=> '',
                        'type' 		=> 'number',
                        'custom_attributes' => array(
                            'min' 	=> 1,
                            'step' 	=> 1
                        ),
                        'column_width' => 'eight',
                    ),
                    
                    array(
                        'title' => __( 'Image Quality Mid Width', 'radykal' ),
                        'id' 		=> 'fpd_iqr_mid_width',
                        'default'	=> '',
                        'type' 		=> 'number',
                        'custom_attributes' => array(
                            'min' 	=> 1,
                            'step' 	=> 1
                        ),
                        'column_width' => 'eight',
                    ),
                    
                    array(
                        'title' => __( 'Image Quality Mid Height', 'radykal' ),
                        'id' 		=> 'fpd_iqr_mid_height',
                        'default'	=> '',
                        'type' 		=> 'number',
                        'custom_attributes' => array(
                            'min' 	=> 1,
                            'step' 	=> 1
                        ),
                        'column_width' => 'eight',
                    ),
                    
                    array(
                        'title' => __( 'Image Quality High Width', 'radykal' ),
                        'id' 		=> 'fpd_iqr_high_width',
                        'default'	=> '',
                        'type' 		=> 'number',
                        'custom_attributes' => array(
                            'min' 	=> 1,
                            'step' 	=> 1
                        ),
                        'column_width' => 'eight',
                        'unbordered'      => true
                    ),
                    
                    array(
                        'title' => __( 'Image Quality High Height', 'radykal' ),
                        'id' 		=> 'fpd_iqr_high_height',
                        'default'	=> '',
                        'type' 		=> 'number',
                        'custom_attributes' => array(
                            'min' 	=> 1,
                            'step' 	=> 1
                        ),
                        'column_width' => 'eight',
                        'unbordered'      => true
                    ),
                    
                    array(
                        'title' 		=> __('Facebook Photos', 'radykal'),
                        'type' 			=> 'section-title',
                        'id' 			=> 'facebook-section'
                    ),

					array(
						'title' 		=> __( 'App ID', 'radykal' ),
						'description' 	=> __( 'Enter a Facebook App ID to enable Facebook photos in the images module.', 'radykal' ),
						'id' 			=> 'fpd_facebook_app_id',
						'css' 			=> 'width:500px;',
						'default'		=> '',
						'type' 			=> 'text',
                        'unbordered'      => true
					),
                    
                    array(
                        'title' 		=> __('Instagram Photos', 'radykal'),
                        'type' 			=> 'section-title',
                        'id' 			=> 'instagram-section'
                    ),

					array(
						'title' 		=> __( 'App ID', 'radykal' ),
						'description' 	=> __( 'Enter an Instagram App ID to enable Instagram photos in the images module.', 'radykal' ),
						'id' 			=> 'fpd_instagram_client_id',
						'css' 			=> 'width:500px;',
						'default'		=> '',
						'type' 			=> 'text',
                        'column_width' => 'eight',
					),

					array(
						'title' 		=> __( 'App Secret', 'radykal' ),
						'description' 	=> __( 'Enter the secret of yor Instagram app.', 'radykal' ),
						'id' 			=> 'fpd_instagram_secret_id',
						'default'		=> '',
						'type' 			=> 'password',
                        'column_width' => 'eight',
					),

					array(
						'title' 		=> __( 'Redirect URI', 'radykal' ),
						'description' 	=> __( 'This is the URI you need to paste into the "OAuth Redirect URI" input when creating a Instagram Client ID. Do not change it!', 'radykal' ),
						'id' 			=> 'fpd_instagram_redirect_uri',
						'css' 			=> 'width:500px;',
						'default'		=> plugins_url( '/assets/templates/instagram_auth.php', FPD_PLUGIN_ROOT_PHP ),
						'type' 			=> 'text',
                        'unbordered'      => true
					),
                    
                    array(
                        'title' 		=> __('Pixabay Photos', 'radykal'),
                        'type' 			=> 'section-title',
                        'id' 			=> 'pixabay-section'
                    ),

					array(
						'title' 		=> __( 'API Key', 'radykal' ),
						'description' 	=> __( 'Enter a Pixabay API key to enable the Pixabay photo library in the images module.', 'radykal' ),
						'id' 			=> 'fpd_pixabayApiKey',
						'css' 			=> 'width:500px;',
						'default'		=> '',
						'type' 			=> 'text'
					),

					array(
						'title' 		=> __( 'High-Res Images', 'radykal' ),
						'description' 	=> __( 'Let your customers add only high resolution images from Pixabay.', 'radykal' ),
						'id' 			=> 'fpd_pixabayHighResImages',
						'default'		=> 'no',
						'type' 			=> 'checkbox',
                        'column_width' => 'eight',
                        'unbordered'      => true
					),

					array(
						'title' 		=> __( 'Language', 'radykal' ),
						'description' 	=> __( 'The language to be searched in.', 'radykal' ),
						'id' 			=> 'fpd_pixabayLang',
						'default'		=> 'en',
						'type' 			=> 'select',
						'css'			=> 'width: 200px',
						'options'   	=> array(
							'en' => 'English',
							'de' => 'German',
							'fr' => 'France',
							'ru' => 'Russian',
							'es' => 'Spanish',
							'it' => 'Italian',
							'cs' => 'Czech',
							'da' => 'Danish',
							'id' => 'Indonesian',
							'hu' => 'Hungarian',
							'nl' => 'Dutch',
							'no' => 'Norwegian',
							'pl' => 'Polish',
							'pt' => 'Portuguese',
							'ro' => 'Romanian',
							'sk' => 'Slovak',
							'fi' => 'Finnish',
							'sv' => 'Swedish',
							'tr' => 'Turkish',
							'vi' => 'Vietnamese',
							'th'  => 'Thai',
							'bg'  => 'Bulgarian',
							'el' => 'Greek',
							'ja' => 'Japanese',
							'ko' => 'Korean',
							'zh' => 'Chinese',
						),
                        'column_width' => 'eight',
                        'unbordered'      => true
					),

					array(
						'title' => __('Text', 'radykal'),
						'type' => 'section',
						'id' => 'text-section'
					),

					array(
						'title' 	=> __( 'Disable Text Emojis', 'radykal' ),
						'description'	 	=> __( 'The customers can not add emojis in text elements.', 'radykal' ),
						'id' 		=> 'fpd_disableTextEmojis',
						'default'	=> 'no',
						'type' 		=> 'checkbox'
					),

					array(
						'title' 	=> __( 'Custom Text as Textbox', 'radykal' ),
						'description'	 	=> __( 'Add custom text as textbox (the max. width can be adjusted by side controls)', 'radykal' ),
						'id' 		=> 'fpd_customTextAsTextbox',
						'default'	=> 'no',
						'type' 		=> 'checkbox'
					),

					array(
						'title' => __('Products', 'radykal'),
						'type' => 'section',
						'id' => 'products-section'
					),

					array(
						'title' 	=> __( 'Replace Initial Elements', 'radykal' ),
						'description'	 	=> __( 'Keep custom added elements, when changing the main product,  only the initial elements of a Product will be replaced.', 'radykal' ),
						'id' 		=> 'fpd_replace_initial_elements',
						'default'	=> 'no',
						'type' 		=> 'checkbox',
					),

					array(
						'title' 	=> __( 'Swap Product Confirmation', 'radykal' ),
						'description'	 	=> __( 'Display a confirmation dialog when user wants to swap the product.', 'radykal' ),
						'id' 		=> 'fpd_swapProductConfirmation',
						'default'	=> 'no',
						'type' 		=> 'checkbox',
					),

					array(
						'title' => __('Names & Numbers', 'radykal'),
						'type' => 'section-title',
						'id' => 'names-numbers-section'
					),
	
					array(
						'title' 			=> __( 'Dropdown Values', 'radykal' ),
						'description'	 	=> __( 'Define values for the dropdown for a name & number entry. Useful for different shirt sizes.', 'radykal' ),
						'id' 				=> 'fpd_namesNumbersDropdown',
						'default'			=> '',
						'placeholder'		=> $nn_module_placeholder,
						'type' 				=> 'text',
					),
	
					array(
						'title' 			=> __( 'Entry Price', 'radykal' ),
						'description'	 	=> __( 'An additional price for every entry in the names and numbers list.', 'radykal' ),
						'id' 				=> 'fpd_namesNumbersEntryPrice',
						'default'			=> 0,
						'type' 				=> 'number',
						'custom_attributes' => array(
							'min' => 0,
							'step' => 0.01
						)
					),
					
					array(
						'title' => __('Designs', 'radykal'),
						'type' => 'section-title',
						'id' => 'designs-section'
					),

					array(
						'title' 		=> __( 'Design Categories', 'radykal' ),
						'description'	=> __( 'Choose specific design categories you want to use in the Designs module. If left empty, all design categories will be used.', 'radykal' ),
						'placeholder' 	=> __( 'All Design Categories', 'radykal' ),
						'id' 			=> 'fpd_design_categories',
						'default'		=> '',
						'type' 			=> 'multiselect',
						'class'			=> 'semantic-select',
						'options'   	=> fpd_output_top_level_design_cat_options()
					),

				),//modules

				//action
				'actions' => array(

					array(
						'title' => __('Download', 'radykal'),
						'type' => 'section-title',
						'id' => 'download-section'
					),

					array(
						'title' => __( 'Watermark Image', 'radykal' ),
						'description' 		=> __( 'Set a watermark image that will be added when the user downloads or prints the product. If the WooCommerce product is downloadable, the watermark will be removed when the customer downloads/prints the completed order.', 'radykal' ),
						'id' 		=> 'fpd_watermark_image',
						'css' 		=> 'width:500px;',
						'default'	=> '',
						'type' 		=> 'upload',
						'addMedia'	=> 'watermark'
					),

					array(
						'title' => __( 'Filename', 'radykal' ),
						'description' 		=> __( 'The filename when the customer downloads the product design in the frontend as image or PDF. You can use placeholder in the filename: %d for date, %id for post/product ID, %title for post/product title, %slug for post/product slug.', 'radykal' ),
						'id' 		=> 'fpd_downloadFilename',
						'default'	=> 'Product',
						'type' 		=> 'text'
					),

					array(
						'title' => __('Save', 'radykal'),
						'type' => 'section',
						'id' => 'save-section'
					),

					array(
						'title' 	=> __( 'Account Product Storage', 'radykal' ),
						'description'	 => __( 'When the user saves his products, it will be stored in his account and not in the storage of the used browser.', 'radykal' ),
						'id' 		=> 'fpd_accountProductStorage',
						'default'	=> 'no',
						'type' 		=> 'checkbox',
					),

					array(
						'title' => __('Info', 'radykal'),
						'type' => 'section',
						'id' => 'info-section'
					),

					array(
						'title' 	=> __( 'Auto-Display', 'radykal' ),
						'description'	 => __( 'Display the info modal when the product designer is loaded.', 'radykal' ),
						'id' 		=> 'fpd_autoOpenInfo',
						'default'	=> 'no',
						'type' 		=> 'checkbox',
					),

					array(
						'title' => __('Zoom', 'radykal'),
						'type' => 'section',
						'id' => 'zoom-section'
					),

					array(
						'title' => __( 'Step', 'radykal' ),
						'description' 		=> __( 'The step for zooming in and out.', 'radykal' ),
						'id' 		=> 'fpd_zoom_step',
						'css' 		=> 'width:60px;',
						'default'	=> '0.2',
						'type' 		=> 'number',
						'custom_attributes' => array(
							'min' 	=> 0,
							'step' 	=> 0.1
						),
                        'column_width' => 'eight',
                        'unbordered'      => true
					),

					array(
						'title' => __( 'Maximum', 'radykal' ),
						'description' 		=> __( 'The maximum zoom when zooming in.', 'radykal' ),
						'id' 		=> 'fpd_max_zoom',
						'css' 		=> 'width:60px;',
						'default'	=> '3',
						'type' 		=> 'number',
						'custom_attributes' => array(
							'min' 	=> 1,
							'step' 	=> 0.1
						),
                        'column_width' => 'eight',
                        'unbordered'      => true
					),

					// array(
					// 	'title' => __('Snap', 'radykal'),
					// 	'type' => 'section',
					// 	'id' => 'snap-section'
					// ),

					// array(
					// 	'title' => __( 'Grid Width', 'radykal' ),
					// 	'description' 		=> __( 'The width for the grid when snap action is enabled.', 'radykal' ),
					// 	'id' 		=> 'fpd_action_snap_grid_width',
					// 	'css' 		=> 'width:60px;',
					// 	'default'	=> '50',
					// 	'type' 		=> 'number',
					// 	'custom_attributes' => array(
					// 		'min' 	=> 0,
					// 		'step' 	=> 1
					// 	),
                    //     'column_width' => 'eight',
                    //     'unbordered'      => true
					// ),

					// array(
					// 	'title' => __( 'Grid Height', 'radykal' ),
					// 	'description' 		=> __( 'The height for the grid when snap action is enabled.', 'radykal' ),
					// 	'id' 		=> 'fpd_action_snap_grid_height',
					// 	'css' 		=> 'width:60px;',
					// 	'default'	=> '50',
					// 	'type' 		=> 'number',
					// 	'custom_attributes' => array(
					// 		'min' 	=> 0,
					// 		'step' 	=> 1
					// 	),
                    //     'column_width' => 'eight',
                    //     'unbordered'      => true
					// ),					

					array(
						'title' => __('QR-Code', 'radykal'),
						'type' => 'section',
						'id' => 'qrcode-section'
					),

					array(
						'title' 	=> __( 'Scale To Width/Height', 'radykal' ),
						'description' 		=> __( 'The width and height will be scaled proportionally.', 'radykal' ),
						'id' 		=> 'fpd_qr_code_prop_resizeToW',
						'css' 		=> 'width:70px;',
						'default'	=> '0',
						'type' 		=> 'number',
						'custom_attributes' => array(
							'min' 	=> 0,
							'step' 	=> 1
						),
                        'column_width' => 'eight',
					),

					array(
						'title' => __( 'Price', 'radykal' ),
						'id' 		=> 'fpd_qr_code_prop_price',
						'css' 		=> 'width:70px;',
						'default'	=> 0,
						'type' 		=> 'number',
						'custom_attributes' => array(
							'min' 	=> 0,
							'step' 	=> 1
						),
                        'column_width' => 'eight',
					),

					array(
						'title' => __( 'Resizable', 'radykal' ),
						'id' 		=> 'fpd_qr_code_prop_resizable',
						'default'	=> 'yes',
						'type' 		=> 'checkbox',
                        'column_width' => 'eight',
                        'unbordered'      => true
					),

					array(
						'title' 		=> __( 'Draggable', 'radykal' ),
						'id' 			=> 'fpd_qr_code_prop_draggable',
						'default'		=> 'yes',
						'type' 			=> 'checkbox',
                        'column_width' 	=> 'eight',
                        'unbordered'    => true
					),

					array(
						'title' => __('Ruler', 'radykal'),
						'type' 	=> 'section',
						'id' 	=> 'ruler-section'
					),

					array(
						'title' 		=> __( 'Unit of measurement', 'radykal' ),
						'id' 			=> 'fpd_rulerUnit',
						'default'		=> 'mm',
						'type' 			=> 'radio',
						'description'	=>  __( 'The unit of measurement for the ruler and size tooltip.', 'radykal' ),
						'options'		=> array(
							'px' 			=> __( 'Pixel', 'radykal' ),
							'mm' 			=> __( 'Milimeter', 'radykal' ),
							'cm' 			=> __( 'Centimeter', 'radykal' ),
						),
						'column_width' => 'eight',
					),

					array(
						'title' 		=> __( 'Position', 'radykal' ),
						'id' 			=> 'fpd_rulerPosition',
						'default'		=> 'canvas',
						'type' 			=> 'radio',
						'description'	=>  __( 'The position of the ruler. Around Printing Box requires a printing box in the view.', 'radykal' ),
						'options'		=> array(
							'canvas' 		=> __( 'Canvas', 'radykal' ),
							'pb' 			=> __( 'Around Printing Box', 'radykal' ),
						),
						'column_width' => 'eight',
					),

					array(
						'title' 		=> __( 'Fixed', 'radykal' ),
						'description'	=>  __( 'The ruler will always be visible and you can only hide it by using the Ruler action.', 'radykal' ),
						'id' 			=> 'fpd_rulerFixed',
						'default'		=> 'no',
						'type' 			=> 'checkbox',
                        'unbordered'    => true
					),

				),

				'social-share' => array(

					array(
						'title' => __( 'Enable Share Button', 'radykal' ),
						'description' 		=> __( 'Allow users to share their designs on social networks.', 'radykal' ),
						'id' 		=> 'fpd_sharing',
						'default'	=> 'no',
						'type' 		=> 'checkbox',
                        'relations' => array(
                            'fpd_sharing_og_image' => true,
                            'fpd_sharing_cache_days' => true,
                            'fpd_sharing_social_networks' => true,

                        )
					),

					array(
						'title' => __( 'Add Open graph image meta', 'radykal' ),
						'description' 		=> __( 'If your site does not add an open graph image meta tag, enable this option, otherwise the image of the customized product will not be shared on Facebook.', 'radykal' ),
						'id' 		=> 'fpd_sharing_og_image',
						'default'	=> 'no',
						'type' 		=> 'checkbox',
                        'column_width' => 'eight',
					),

					array(
						'title' => __( 'Cache Days', 'radykal' ),
						'description' 		=> __( 'Whenever an user shares a design, an image and database entry will be created. To delete this data after a certain period of time, you can set the days of caching. A value of 0 will store the data forever.', 'radykal' ),
						'id' 		=> 'fpd_sharing_cache_days',
						'default'	=> 5,
						'type' 		=> 'number',
						'custom_attributes' => array(
							'min' 	=> 0,
							'step' 	=> 1
						),
                        'column_width' => 'eight',
                        'unbordered'      => true
					),

					array(
						'title' => __( 'Social Networks', 'radykal' ),
						'id' 		=> 'fpd_sharing_social_networks',
						'css' 		=> 'width:300px;',
						'default'	=> array('facebook', 'twitter', 'email'),
						'type' 		=> 'multiselect',
						'options'   => array(
							"facebook" 	=> 'Facebook',
							"twitter" 	=> 'Twitter',
							"linkedin" 	=> 'Linkedin',
							"pinterest" => 'Pinterest',
							"email" 	=> 'Email',
						),
                        'unbordered'      => true
					),

				),

				'ai-services' => array(

					array(
                        'description' 		=> __( 'A <a href="https://elopage.com/s/radykal/genius-dec00fd9/payment?plan_id=384534&locale=en" target="_blank">Premium subscription of Genius</a> is required in order to use the AI services.', 'radykal' ),
                        'type' 			=> 'description',
                        'id' 			=> 'ai-services-desc'
                    ),

					array(
						'title' => __( 'Remove Background', 'radykal' ),
						'description' => __('In the "Edit Image" tab of the toolbar the customer will find an option to remove the background.', 'radykal' ),
						'id' 		=> 'fpd_ai_removeBG',
						'default'	=> 'no',
						'type' 		=> 'checkbox',
					),

					array(
						'title' => __( 'Upscale Image', 'radykal' ),
						'description' => __('Your customer will see a warning as soon as the DPI of an image is below the min. DPI you set in the settings. In this warning there is a button to upscale the image.', 'radykal' ),
						'id' 		=> 'fpd_ai_superRes',
						'default'	=> 'no',
						'type' 		=> 'checkbox',
					),

					array(
						'title' => __( 'Text to Images', 'radykal' ),
						'description' => __('Display "Text to Images" tab in Image module.', 'radykal' ),
						'id' 		=> 'fpd_ai_text2Img',
						'default'	=> 'no',
						'type' 		=> 'checkbox',
					),

				)

			));
		}

		public static function get_saved_ui_layouts() {

			$saved_layouts = FPD_UI_Layout_Composer::get_layouts();

			if(sizeof($saved_layouts) == 0) {

				return array(
					'default' => __('Default', 'radykal')
				);

			}

			$layouts = array();
			foreach($saved_layouts as $saved_layout) {

				$id = str_replace('fpd_ui_layout_', '', $saved_layout->option_name);
				$ui_layout = fpd_strip_multi_slahes($saved_layout->option_value);
				$ui_layout = json_decode($ui_layout, true);
				$layouts[$id] = $ui_layout['name'];

			}

			return $layouts;
		}

		public static function get_product_designer_visibilities() {

			$values = array(
				'page'	=> __( 'Page', 'radykal' ),
				'lightbox'	=> __( 'Lightbox (only working with sidebar layout)', 'radykal' ),
				'page-customize'	=> __( 'Page after user clicks on the customization button', 'radykal' ),
			);

			return $values;

		}

		public static function get_main_bar_positions() {

			$positions = array(
				'default'	=> __( 'Default', 'radykal' ),
				'shortcode'	=> __( 'Via Shortcode: [fpd_main_bar]', 'radykal' )
			);

			if( function_exists('get_woocommerce_currency') ) {
				$positions['after_product_title']	=  __( 'After Product Title (WooCommerce)', 'radykal' );
				$positions['after_excerpt'] 	=  __( 'After Product Excerpt (WooCommerce)', 'radykal' );
			}

			return $positions;

		}

		public static function get_bounding_box_modi() {

			return array(
				'inside' => __('Inside', 'radyal'),
				'clipping' => __('Clipping', 'radyal'),
				'limitModify' => __('Limit Modification', 'radyal'),
				'none' => __('None', 'radyal'),
			);

		}


		/**
		 * Get the available frame shadows.
		 *
		 */
		public static function get_frame_shadows() {

			return array(
				'fpd-shadow-1'	 => __( 'Shadow 1', 'radykal' ),
				'fpd-shadow-2'	 => __( 'Shadow 2', 'radykal' ),
				'fpd-shadow-3'	 => __( 'Shadow 3', 'radykal' ),
				'fpd-shadow-4'	 => __( 'Shadow 4', 'radykal' ),
				'fpd-shadow-5'	 => __( 'Shadow 5', 'radykal' ),
				'fpd-shadow-7'	 => __( 'Shadow 6', 'radykal' ),
				'fpd-no-shadow'	 => __( 'No Shadow ', 'radykal' ),
			);

		}

	}
}

?>