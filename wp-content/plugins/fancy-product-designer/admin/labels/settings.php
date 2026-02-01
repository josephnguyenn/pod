<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( !class_exists('FPD_Labels_Settings') ) {

	class FPD_Labels_Settings {

		public static function get_labels() {

			return apply_filters('fpd_admin_labels_settings',
				array (
					'general' => __( 'General', 'radykal' ),
					'elementProperties' => __( 'Element Properties', 'radykal' ),
					'labels' => __( 'Labels', 'radykal' ),
					'fonts' => __( 'Fonts', 'radykal' ),
					'colors' => __( 'Colors', 'radykal' ),
					'woocommerce' => __( 'WooCommerce', 'radykal' ),
					'advanced' => __( 'Advanced', 'radykal' ),
					'resetOptions' => __( 'Reset Options', 'radykal' ),
					'resetOptionsText' => __( 'Are you sure to reset current showing options?', 'radykal' ),
					'save' => __( 'Save Changes', 'radykal' ),
					'reset' => __( 'Reset', 'radykal' ),
					'searchOption' => __( 'Search Option...', 'radykal' ),
					'display' => __( 'Display', 'radykal' ),
					'modules' => __( 'Modules', 'radykal' ),
					'actions' => __( 'Actions', 'radykal' ),
					'social-share' => __( 'Social Share', 'radykal' ),
					'pricing-rules' => __( 'Pricing Rules', 'radykal' ),
					'rest-api' => __( 'REST API', 'radykal' ),
					'images' => __( 'Images', 'radykal' ),
					'custom-images' => __( 'Custom Images', 'radykal' ),
					'custom-texts' => __( 'Custom Texts', 'radykal' ),
                    'coloring' => __( 'Coloring', 'radykal' ),
					'toolbar' => __( 'Toolbar', 'radykal' ),
					'image_editor' => __( 'Image Editor', 'radykal' ),
					'misc' => __( 'Miscellaneous', 'radykal' ),
					'color-names' => __( 'Color Names', 'radykal' ),
					'color-prices' => __( 'Color Prices', 'radykal' ),
					'color-general' => __( 'Color General', 'radykal' ),
					'product-page' => __( 'Product Page', 'radykal' ),
					'cart' => __( 'Cart', 'radykal' ),
					'order' => __( 'Order', 'radykal' ),
					'catalog-listing' => __( 'Catalog Listing', 'radykal' ),
					'global-product-designer' => __( 'Global Product Designer', 'radykal' ),
					'cross-sells' => __( 'Cross Sells', 'radykal' ),
					'dokan' => __( 'Dokan', 'radykal' ),
					'troubleshooting' => __( 'Troubleshooting', 'radykal' ),
					'tools' => __( 'Tools', 'radykal' ),
					'textTemplates' => __( 'Text Templates', 'radykal' ),
					'textTemplatesAdd' => __( 'Add Text Template', 'radykal' ),
					'textTemplatesTextsize' => __( 'Text Size', 'radykal' ),
					'textTemplatesAlign' => __( 'Text Alignment', 'radykal' ),
					'textTemplatesDelete' => __( 'Delete', 'radykal' ),
					'pro-general' => __( 'General', 'radykal' ),
					'printful' => __( 'Printful', 'radykal' ),
					'color-lists' => __( 'Color Lists', 'radykal' ),
					'addons' => __( 'Addons', 'radykal' ),
					'color-selection' => __( 'Color Selection', 'radykal' ),
					'bulk-variations' => __( 'Bulk Variations', 'radykal' ),
					'3d-preview' => __( '3D Preview', 'radykal' ),
					'dynamic-views' => __( 'Dynamic Views', 'radykal' ),
					'layout' => __( 'Layout', 'radykal' ),
					'view-thumbnails' => __( 'View Thumbnails', 'radykal' ),
					'names-numbers' => __( 'Names & Numbers', 'radykal' ),
					'ai-services' => __( 'AI Services', 'radykal' ),

					//container
					'loadingOptions' => __( 'Loading Options...', 'radykal' ),
					'updatingOptions' => __( 'Updating Options...', 'radykal' ),
					'selectImage' => __( 'Select Image', 'radykal' ),
				)
			);

		}
	}

}

?>