<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( !class_exists('FPD_Bulk_Variations') ) {

	class FPD_Bulk_Variations {

		public static function create_variation_string( $variations ) {

			$final_str = '';

			if( is_array($variations) ) {

				foreach($variations as $key => $value) {
					$final_str .= $key . '=' . $value . ', ';
				}

				$final_str = substr_replace($final_str, '', -2); //remove last 2 chars

			}

			return $final_str;

		}

	}

}

?>