<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( !class_exists('FPD_Template') ) {

	class FPD_Template {

		const PREMIUM_PATH = '/uploads/fpd_product_templates/';

		public static function get_library_templates(  ) {

			$premium_templates_dir = WP_CONTENT_DIR . self::PREMIUM_PATH;
			$premium_templates_url = Fancy_Product_Designer::REMOTE_ASSETS_URL . 'premium-templates/';

			$templates_json = fpd_admin_get_file_content( $premium_templates_url . 'db.json' );
			
			if( !$templates_json ) {
				return array();
			}
			
			$templates_json = @json_decode($templates_json);	
						
			if( !is_object($templates_json) )
				return array();

			//verify genius plan
			$plan_valid = false;
			$genius_res = fpd_genius_request();	
					
			if( is_array($genius_res) && $genius_res['status'] == 'success' ) {

				$genius_client_data = $genius_res['data'];					

				$now = new DateTime();
				$access_until = new DateTime( $genius_client_data['accessUntil'] );

				if($genius_client_data['subscription'] == 'premium' && $now < $access_until) {

					$plan_valid = true;

				}	

			}

			foreach($templates_json as $catKey => $templatesCat) {

				if( $plan_valid ) {
					$templatesCat->purchase_url = null;
				}

				foreach($templatesCat->templates as $templateKey => $template) {

					if( isset($template->free) ) {

						$template->installed = true;
						$template->file_path = $premium_templates_url.$template->file;

					}
					else {

						$template->installed = $plan_valid || file_exists($premium_templates_dir.$template->file);

						if( $template->installed ) {
							$template->file_path = $plan_valid ? $premium_templates_url.$template->file :  $premium_templates_dir.$template->file;
						}

					}

					$preview_images = is_array($template->images) ? $template->images : array($template->images);
					array_walk($preview_images, function(&$value, $key) { $value = Fancy_Product_Designer::REMOTE_ASSETS_URL . 'premium-templates/_preview_imgs/' . $value; } );
					$template->images = $preview_images;

				}

			}

			return $templates_json;

		}

	}

}

?>