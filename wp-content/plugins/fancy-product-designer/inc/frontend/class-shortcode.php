<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if(!class_exists('FPD_Frontend_Shortcode')) {

	class FPD_Frontend_Shortcode {

		public function __construct() {

			add_shortcode( 'fpd', array( &$this, 'fpd_shortcode_handler') );
			add_shortcode( 'fpd_form', array( &$this, 'fpd_form_shortcode_handler') );
			add_action( 'wp_ajax_fpd_newshortcodeorder', array( &$this, 'create_shortcode_order' ) );
			add_action( 'wp_ajax_nopriv_fpd_newshortcodeorder', array( &$this, 'create_shortcode_order' ) );

		}

		public function fpd_shortcode_handler( $atts ) {

			extract( shortcode_atts( array(
			), $atts, 'fpd' ) );

			wp_enqueue_script( 
				'fpd-frontend-shortcode', 
				plugins_url('/assets/js/frontend-shortcode.js', FPD_PLUGIN_ROOT_PHP), 
				array('jquery'), 
				Fancy_Product_Designer::VERSION 
			);

			ob_start();
			echo FPD_Frontend_Product::add_customize_button();
			echo FPD_Frontend_Product::add_product_designer();
			$output = ob_get_contents();
			ob_end_clean();

			return $output;

		}

		public function fpd_form_shortcode_handler( $atts ) {

			extract( shortcode_atts( array(
				'price_format' => '$%d',
			), $atts, 'fpd_form' ) );

			$name_placeholder = FPD_Settings_Labels::get_translation( 'misc', 'shortcode_form:name_placeholder' );
			$email_placeholder = FPD_Settings_Labels::get_translation( 'misc', 'shortcode_form:email_placeholder' );
			$submit_text = FPD_Settings_Labels::get_translation( 'misc', 'shortcode_form:send' );

			ob_start();
			?>
			<form name="fpd_shortcode_form">
				<?php if( !empty($price_format) ) : ?>
				<p class="fpd-shortcode-price-wrapper">
					<span class="fpd-shortcode-price" data-priceformat="<?php echo $price_format; ?>"></span>
				</p>
				<?php endif; ?>
				<input 
					type="text" 
					name="fpd_shortcode_form_name" 
					placeholder="<?php echo $name_placeholder ?>" 
					class="fpd-shortcode-form-text-input" 
					value="<?php echo Fancy_Product_Designer::DEBUG ? 'Test' : ''; ?>"
				/>
				<input 
					type="email" 
					name="fpd_shortcode_form_email" 
					placeholder="<?php echo $email_placeholder ?>" 
					class="fpd-shortcode-form-text-input" 
					value="<?php echo Fancy_Product_Designer::DEBUG ? 'test@test.test' : ''; ?>"
				/>
				<input 
					type="hidden" 
					name="fpd_product" 
				/>
				<input 
					type="submit" 
					class="fpd-disabled <?php echo fpd_get_option('fpd_start_customizing_css_class'); ?>" 
					value="<?php echo $submit_text; ?>"	
				/>
			</form>
			<?php

			$output = ob_get_contents();
			ob_end_clean();

			return $output;

		}

		public function create_shortcode_order() {

			if( !isset($_POST['order']) )
				die;

			$insert_id = FPD_Shortcode_Order::create( $_POST['name'], $_POST['email'], $_POST['order'], isset($_POST['print_order']) ? $_POST['print_order'] : null );

			if( $insert_id ) {
				echo json_encode(array(
					'id' => $insert_id,
					'message' => FPD_Settings_Labels::get_translation( 'misc', 'shortcode_order:success_sent' ),
				));
			}
			else {

				echo json_encode(array(
					'error' => FPD_Settings_Labels::get_translation( 'misc', 'shortcode_order:fail_sent' ),
				));

			}

			die;

		}


	}
}

new FPD_Frontend_Shortcode();

?>