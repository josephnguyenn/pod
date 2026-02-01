<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


if(!class_exists('FPD_Plus_Gravity_Form')) {

	class FPD_Plus_Gravity_Form {

		private $show_order_viewer = false;

		public function __construct() {

			//FRONTEND

			add_shortcode( 'fpd_gf', array( &$this, 'gf_shortcode') );
			add_action( 'gform_entry_created', array( &$this, 'entry_created'), 10, 2 );


			//BACKEND

			// Entry Detail
			add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_styles_scripts' ) );
			add_filter( 'gform_field_content', array(&$this, 'gf_hide_fpd_order'), 10, 3 );
			add_action( 'gform_entry_detail', array(&$this, 'gf_add_order_viewer'), 10, 2 );
			add_action( 'wp_ajax_fpd_gf_get_order_data', array( &$this, 'ajax_get_order_data' ) );

		}

		public function gf_shortcode( ) {

			wp_enqueue_script( 'gform_gravityforms' );
			wp_enqueue_script( 
				'fpd-frontend-gf', 
				plugins_url('/assets/js/frontend-gf.js', FPD_PLUGIN_ROOT_PHP), 
				array(), 
				Fancy_Product_Designer::VERSION 
			);

			ob_start();
			echo  do_shortcode( '[fpd]' );
			?>
			<style type="text/css">
				.fpd-gf-price + input {
					display: none !important;
				}
			</style>
			<?php
			$output = ob_get_contents();
			ob_end_clean();

			return $output;

		}

		public function entry_created( $entry, $form ) {

			foreach($form['fields'] as $field) {

				if($field['cssClass'] == 'fpd-order') {

					$fpd_order = json_decode( $field->get_value_export( $entry, $field['id'] ), true );

					$fpd_data = array(
						'fpd_product' => $fpd_order['product'],
					);

					$additional_data = array(
						'order_id' => $entry['id'],
					);

					$fpd_data = apply_filters( 'fpd_new_order_item_data', $fpd_data, 'gf', $additional_data );

				}

			}
		}

		public function enqueue_styles_scripts( $hook ) {

			//add necessary styles and scripts into entry detail
			if( isset($_GET['page']) && $_GET['page'] === 'gf_entries' && isset($_GET['view']) ) {

				wp_enqueue_style( 'fpd-react-order-viewer', plugins_url('/admin/react-app/css/gf-order-viewer.css', FPD_PLUGIN_ROOT_PHP), array(
					'fpd-semantic-ui',
					'fpd-js',
					'fpd-admin'
				), Fancy_Product_Designer::VERSION );

				wp_enqueue_script( 'fpd-react-order-viewer', plugins_url('/admin/react-app/js/gf-order-viewer.js', FPD_PLUGIN_ROOT_PHP), array(
					'fpd-semantic-ui',
					'fpd-admin',
					'fpd-js',
				), Fancy_Product_Designer::VERSION);

			}

		}

		public function gf_hide_fpd_order( $content, $field, $value ) {

			//hide fpd-order row and create JS variable with fpd order data
			if( isset($_GET['page']) && $_GET['page'] == 'gf_entries' && strpos($field->cssClass, 'fpd-order') !== false && !empty($value) ) {
				$content = '<tr class="fpd-hidden"><td><script>var fpdGfFormId = '.$field->formId.'; var fpdGfFieldId = '.$field->id.'; var fpdGfLeadId = '.$_GET['lid'].';</script></td></tr>';
				$this->show_order_viewer = true;
			}

			return $content;

		}

		public function gf_add_order_viewer( $form, $lead ) {

			if( $this->show_order_viewer ):
			?>
			<div class="ui segment">
				<div id="fpd-react-root"></div>
			</div>
			<?php
			endif;

		}

		public function ajax_get_order_data() {

			check_ajax_referer( 'fpd_ajax_nonce' );

			$lead = RGFormsModel::get_lead($_GET['leadId']);
			$field = GFAPI::get_field( $_GET['formId'], $_GET['fieldId'] );
			$value = RGFormsModel::get_lead_field_value( $lead, $field );

			echo json_encode(array(
				'ID' => $_GET['formId'],
				'order' => is_string($value) ? json_decode($value) : $value
			));

			die;
		}

	}

}

new FPD_Plus_Gravity_Form();

?>