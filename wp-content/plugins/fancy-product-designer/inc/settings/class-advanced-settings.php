<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( !class_exists('FPD_Settings_Advanced') ) {

	class FPD_Settings_Advanced {

		public static function get_options() {

			return apply_filters('fpd_advanced_settings', array(

				'layout' => array(

					array(
						'title' => __( 'Max. Canvas Height', 'radykal' ),
						'description' 		=> __( 'The maximum canvas height related to the window height. A percentage number between 0 and 100, e.g. 80 will set a maximum canvas height of 80% of the window height. A value of 100 will disable a calculation of a max. height.', 'radykal' ),
						'id' 		=> 'fpd_maxCanvasHeight',
						'css' 		=> 'width:60px;',
						'default'	=> '100',
						'type' 		=> 'number',
						'custom_attributes' => array(
							'min' 	=> 0,
							'max' 	=> 100,
							'step' 	=> 1
						),
					),

					array(
						'title' => __( 'Canvas Wrapper Height', 'radykal' ),
						'description' 		=> __( 'You can set a fixed wrapper height (e.g. 800px) or use "auto" and the canvas wrapper height is dynamically calculated by the canvas height set for the view of the product.', 'radykal' ),
						'id' 		=> 'fpd_canvasHeight',
						'default'	=> 'auto',
						'type' 		=> 'text',
                        'unbordered'      => true
					),

					array(
						'title' 	=> __( 'Canvas Touch Scrolling', 'radykal' ),
						'description'	 => __( 'Enable touch gesture to scroll on canvas.', 'radykal' ),
						'id' 		=> 'fpd_canvas_touch_scrolling',
						'default'	=> 'no',
						'type' 		=> 'checkbox',
					),

					array(
						'title' 	=> __( 'Responsive', 'radykal' ),
						'description'	 	=> __( 'Resizes the canvas and all elements in the canvas, so that all elements are displaying properly in the canvas container. This is useful, when your canvas is larger than the available space in the parent container.', 'radykal' ),
						'id' 		=> 'fpd_responsive',
						'default'	=> 'yes',
						'type' 		=> 'checkbox',
					),

					array(
						'title' 		=> __( 'Hide On Smartphones', 'radykal' ),
						'description'	=> __( 'Hide product designer on smartphones.', 'radykal'),
						'id' 			=> 'fpd_disable_on_smartphones',
						'default'		=> 'no',
						'type' 			=> 'checkbox',
					),

					array(
						'title' 		=> __( 'Hide On Tablets', 'radykal' ),
						'description'	=> __( 'Hide product designer on tablets.', 'radykal' ),
						'id' 			=> 'fpd_disable_on_tablets',
						'default'		=> 'no',
						'type' 			=> 'checkbox',
					),

					array(
						'title' 		=> __( 'Corner Controls Style', 'radykal' ),
						'id' 			=> 'fpd_corner_controls_style',
						'default'		=> 'advanced',
						'type' 			=> 'radio',
						'description'	=>  __( 'The style for corner controls when an element is selected.', 'radykal' ),
						'options'		=> array(
							'advanced' 		=> __( 'Advanced: Scale, Rotate, Delete, Duplicate', 'radykal' ),
							'basic' 		=> __( 'Basic: Scale, Rotate', 'radykal' ),
						),
                        'unbordered'      => true
					),

					array(
						'title' 		=> __('Responsive Breakpoints', 'radykal'),
						'type' 			=> 'section-title',
						'id' 			=> 'misc-responsive-breakpoints'
					),

					array(
						'title' => __( 'Small', 'radykal' ),
						'description' 		=> __( 'The responsive breakpoint for small devices such as smartphones.', 'radykal' ),
						'id' 		=> 'fpd_responsive_breakpoint_small',
						'css' 		=> 'width:60px;',
						'default'	=> '768',
						'type' 		=> 'number',
						'custom_attributes' => array(
							'min' 	=> 0,
							'step' 	=> 1
						),
					),

					array(
						'title' => __( 'Medium', 'radykal' ),
						'description' 		=> __( 'The responsive breakpoint for medium devices such as tablets and small laptops.', 'radykal' ),
						'id' 		=> 'fpd_responsive_breakpoint_medium',
						'css' 		=> 'width:60px;',
						'default'	=> '1024',
						'type' 		=> 'number',
						'custom_attributes' => array(
							'min' 	=> 0,
							'step' 	=> 1
						),
						'unbordered'      => true
					),

				),

				'misc' => array(

					array(
						'title' 	=> __( 'Customization Required', 'radykal' ),
						'description' 		=> __( 'The user must customize any or all views of a product in order to proceed.', 'radykal' ),
						'id' 		=> 'fpd_customization_required',
						'default'	=> 'none',
						'type' 		=> 'radio',
						'options'   => array(
							'none'	 => __( 'None', 'radykal' ),
							'any'	 => __( 'ANY view needs to be customized.', 'radykal' ),
							'all'	 => __( 'ALL views needs to be customized.', 'radykal' ),
						)
					),

					array(
						'title' 	=> __( 'Mobile Gestures Behaviour', 'radykal' ),
						'description' 		=> __( 'Enable different gesture behaviours on mobile devices.', 'radykal' ),
						'id' 		=> 'fpd_mobileGesturesBehaviour',
						'default'	=> 'none',
						'type' 		=> 'radio',
						'options'   => array(
							'none'	 => __( 'None.', 'radykal' ),
							'pinchPanCanvas'	 => __( 'Zoom in/out and pan canvas.', 'radykal' ),
							'pinchImageScale'	 => __( 'Scale selected image with pinch.', 'radykal' ),
						)
					),

					array(
						'title' 	=> __( 'Text Link Group Properties', 'radykal' ),
						'description' 		=> __( 'Define additional properties that will be applied to all elements in the same "Text Link Group", when one element in this group is changing.', 'radykal' ),
						'id' 		=> 'fpd_textLinkGroupProps',
						'css' 		=> 	'width: 100%;',
						'default'	=> array(),
						'type' 		=> 'multiselect',
						'options'	=> array(
							'fontFamily' => __( 'Font Family', 'radykal' ),
							'fontSize' => __( 'Font Size', 'radykal' ),
							'lineHeight' => __( 'Line Height', 'radykal' ),
							'letterSpacing' => __( 'Letter Spacing', 'radykal' ),
							'fontStyle' => __( 'Font Style (italic)', 'radykal' ),
							'fontWeight' => __( 'Font Weight (bold)', 'radykal' ),
							'textDecoration' => __( 'Text Decoration (underline)', 'radykal' ),
						)

					),

					array(
						'title' 	=> __( 'Smart Guides', 'radykal' ),
						'description' 		=> __( 'Snap the selected object to the edges of the other objects and to the canvas center.', 'radykal' ),
						'id' 		=> 'fpd_smartGuides',
						'default'	=> 'no',
						'type' 		=> 'checkbox',
					),

					array(
						'title' 	=> __( 'Per-Pixel Detection', 'radykal' ),
						'description'	 => __( 'Object detection happens on per-pixel basis rather than on per-bounding-box. This means transparency of an object is not clickable.', 'radykal' ),
						'id' 		=> 'fpd_canvas_per_pixel_detection',
						'default'	=> 'no',
						'type' 		=> 'checkbox',
					),

					array(
						'title' 	=> __( 'Fit Images In Canvas', 'radykal' ),
						'description'	 => __( 'If the image (custom uploaded or design) is larger than the canvas, it will be scaled down to fit into the canvas.', 'radykal' ),
						'id' 		=> 'fpd_fitImagesInCanvas',
						'default'	=> 'yes',
						'type' 		=> 'checkbox',
					),

					array(
						'title' 	=> __( 'Upload zones always on top', 'radykal' ),
						'description'	 	=> __( 'Upload zones will be always on top of all elements.', 'radykal' ),
						'id' 		=> 'fpd_uploadZonesTopped',
						'default'	=> 'yes',
						'type' 		=> 'checkbox',
					),

					array(
						'title' 	=> __( 'Unsaved Customizations Alert', 'radykal' ),
						'description'	 => __( 'The user will see a notification alert when he leaves the page without saving or adding the product to the cart.', 'radykal' ),
						'id' 		=> 'fpd_unsaved_customizations_alert',
						'default'	=> 'no',
						'type' 		=> 'checkbox',
					),

					array(
						'title' 	=> __( 'Hide Dialog On Add', 'radykal' ),
						'description'	 => __( 'The dialog/off-canvas panel will be closed as soon as an element is added to the canvas.', 'radykal' ),
						'id' 		=> 'fpd_hide_dialog_on_add',
						'default'	=> 'yes',
						'type' 		=> 'checkbox',
					),

					array(
						'title' 	=> __( 'In Canvas Text Editing', 'radykal' ),
						'description'	 => __( 'The user can edit the text via double click or tap(mobile).', 'radykal' ),
						'id' 		=> 'fpd_inCanvasTextEditing',
						'default'	=> 'yes',
						'type' 		=> 'checkbox',
					),

					array(
						'title' 	=> __( 'Open Text Input On Select', 'radykal' ),
						'description'	 => __( 'The textarea in the toolbar to change an editbale text opens when the text is selected.', 'radykal' ),
						'id' 		=> 'fpd_openTextInputOnSelect',
						'default'	=> 'no',
						'type' 		=> 'checkbox',
					),

					array(
						'title' 	=> __( 'Replace Colors In Color Group', 'radykal' ),
						'description'	 => __( ' As soon as an element with a color link group is added, the colours of this element will be used for the color group.', 'radykal' ),
						'id' 		=> 'fpd_replaceColorsInColorGroup',
						'default'	=> 'no',
						'type' 		=> 'checkbox',
					),

					array(
						'title' 	=> __( 'Size Tooltip', 'radykal' ),
						'description'	 => __( 'Display the size of the current selected element in a tooltip.', 'radykal' ),
						'id' 		=> 'fpd_imageSizeTooltip',
						'default'	=> 'no',
						'type' 		=> 'checkbox',
					),

					array(
						'title' 	=> __( 'Apply Fill When Replacing', 'radykal' ),
						'description'	 => __( 'When an element is replaced, apply fill(color) from replaced element to added element.', 'radykal' ),
						'id' 		=> 'fpd_applyFillWhenReplacing',
						'default'	=> 'yes',
						'type' 		=> 'checkbox',
					),

					array(
						'title' 	=> __( 'Apply Size When Replacing', 'radykal' ),
						'description'	 => __( 'When an element is replaced, apply size from replaced element to added element.', 'radykal' ),
						'id' 		=> 'fpd_applySizeWhenReplacing',
						'default'	=> 'no',
						'type' 		=> 'checkbox',
					),

					array(
						'title' 	=> __( 'Auto-Fill Upload Zones', 'radykal' ),
						'description' 		=> __( 'Fill Upload Zones with all uploaded images in all views (only on first upload selection). ', 'radykal' ),
						'id' 		=> 'fpd_autoFillUploadZones',
						'default'	=> 'no',
						'type' 		=> 'checkbox',
					),

					array(
						'title' 	=> __( 'Drag & Drop Images To Upload Zones', 'radykal' ),
						'description' 		=> __( 'Drag & Drop images from the images and designs module into upload zones or on canvas. ', 'radykal' ),
						'id' 		=> 'fpd_dragDropImagesToUploadZones',
						'default'	=> 'no',
						'type' 		=> 'checkbox',
					),

					array(
						'title' 	=> __( 'Multiple Elements Selection', 'radykal' ),
						'description' 		=> __( 'Users can select multiple elements simultaneously by holding down the left mouse button. ', 'radykal' ),
						'id' 		=> 'fpd_multiSelection',
						'default'	=> 'yes',
						'type' 		=> 'checkbox',
					),

					array(
						'title' => __( 'Bounding Box Stroke Width', 'radykal' ),
						'description' 		=> __( 'The stroke width of the bounding box when an element is selected.', 'radykal' ),
						'id' 		=> 'fpd_bounding_box_stroke_width',
						'css' 		=> 'width:60px;',
						'default'	=> '1',
						'type' 		=> 'number',
						'custom_attributes' => array(
							'min' 	=> 0,
							'step' 	=> 1
						)
					),

					array(
						'title' => __( 'Highlight Editable Objects', 'radykal' ),
						'description' 		=> __( 'Highlight objects (editable texts and upload zones) with a dashed border. To enable this just define a hexadecimal color value.', 'radykal' ),
						'id' 		=> 'fpd_highlightEditableObjects',
						'default'	=> '',
						'type' 		=> 'text'
					),

					array(
						'title' => __( 'FabricJS Texture Size', 'radykal' ),
						'description' 		=> __( 'When applying a filter to an image, e.g. the colorization filter on PNG images, this is the max. size in pixels that will be painted. The image parts that are exceeding the max. size are not visible. The max. value should be lower than 5000. <a href="http://fabricjs.com/fabric-filters" target="_blank">More infos about FabricJS filters</a>.', 'radykal' ),
						'id' 		=> 'fpd_fabricjs_texture_size',
						'css' 		=> 'width:60px;',
						'default'	=> '4096',
						'type' 		=> 'number',
						'custom_attributes' => array(
							'min' 	=> 0,
							'step' 	=> 1
						),
						'unbordered'      => true
					),

					array(
						'title' => __( 'Shortcoder Order Mail Notification', 'radykal' ),
						'description' 		=> __( 'Enter a comma-separated list of email addresses to send the order notfication when using shortcode (NOT WooCommerce). By default it will be sent to the admin mail address.', 'radykal' ),
						'id' 		=> 'fpd_shortcode_order_mail_addresses',
						'default'	=> get_option('admin_email'),
						'type' 		=> 'text'
					),

				), //layout-skin

				'troubleshooting' => array(

					array(
						'title' 		=> __( 'Debug Mode', 'radykal' ),
						'description' 	=> __( 'Enables Theme-Check modal and loads the unminified Javascript files.', 'radykal' ),
						'id' 			=> 'fpd_debug_mode',
						'default'		=> 'no',
						'type' 			=> 'checkbox'
					),

					array(
						'title' 		=> __( 'Disable Help Context in Admin', 'radykal' ),
						'description' 	=> __( 'Disable all help context like Get Started screen, topbar and guides.', 'radykal' ),
						'id' 			=> 'fpd_disable_help_context',
						'default'		=> 'no',
						'type' 			=> 'checkbox',
					),

					array(
						'title' 		=> __( 'WooCommerce Product Image CSS Selector', 'radykal' ),
						'description' 	=> __( 'Sometimes, themes or page builder plugins use their own CSS classes for the WooCommerce product image instead of the default ones provided by WooCommerce. This can cause our solution to fail in detecting the product image container when the "Update Product Image" option is enabled, preventing the image from being replaced as intended.', 'radykal' ),
						'id' 			=> 'fpd_wc_product_image_css_selector',
						'default'		=> '',
						'placeholder'	=> __( 'e.g. .image-slide img', 'radykal' ),
						'type' 			=> 'text'
					),

					array(
						'title' 		=> __( 'Admin: Disable Products Loading in Product Builder', 'radykal' ),
						'description' 	=> __( 'To enhance performance in the product builder admin when managing a large number of products, set the threshold for disabling products loading.', 'radykal' ),
						'id' 			=> 'fpd_disable_products_loading_count',
						'default'		=> 200,
						'type' 			=> 'number'
					),

				),

			));
		}

	}
}

?>