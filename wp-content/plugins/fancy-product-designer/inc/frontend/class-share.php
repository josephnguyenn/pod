<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if(!class_exists('FPD_Share')) {

	class FPD_Share {

		public function __construct() {

			add_action( 'wp_head', array( &$this, 'set_fb_tag' ), 1000 );
			add_filter( 'wp_get_attachment_url', array( &$this, 'set_product_image') );
			add_filter( 'wpseo_opengraph_image', array( &$this, 'set_product_image') );	
			add_filter( 'post_type_link', array( &$this, 'reset_share_permalink'), 10, 2 );
			add_filter( 'page_link', array( &$this, 'reset_share_permalink'), 10, 2 );
			add_action( 'wp_ajax_fpd_createshareurl', array( &$this, 'create_share_url' ) );
			add_action( 'wp_ajax_nopriv_fpd_createshareurl', array( &$this, 'create_share_url' ) );
			add_shortcode( 'fpd_share', array( &$this, 'add_share_shortcode') );

		}

		//remove filter that resets the product image url before body starts
		public function set_fb_tag() {

			global $post;

			remove_filter( 'wp_get_attachment_url', array( &$this, 'set_product_image') );		

			if( fpd_get_option('fpd_sharing_og_image') && isset($post->ID) && is_fancy_product( $post->ID ) && isset($_GET['share_id']) ) {

				$transient_key = 'fpd_share_'.sanitize_key( $_GET['share_id'] );
				$transient_val = get_transient($transient_key);

				if($transient_val) {
					echo '<meta property="og:image" content="' . $transient_val['image_url'] . '" />';
				}

			}

		}

		public function reset_share_permalink( $url, $post ) {

			$post_id = null;
			if( is_int($post) ) {
				$post_id = $post;
			}
			else if( isset($post->ID) ) {
				$post_id = $post->ID;
			}

			if( !is_null($post_id) && is_fancy_product( $post_id ) && isset($_GET['share_id']) ) {
				$url = add_query_arg( 'share_id', sanitize_key( $_GET['share_id'] ), $url );
			}

			return $url;

		}

		public function set_product_image($url) {

			global $post;

			if( isset($post->ID) && is_fancy_product( $post->ID ) && isset($_GET['share_id']) ) {

				$transient_key = 'fpd_share_'.sanitize_key( $_GET['share_id'] );
				$transient_val = get_transient($transient_key);
				return $transient_val === false ? $url : $transient_val['image_url'];

			}

			return $url;
		}

		public function add_share_shortcode() {

			return self::get_share_html();

		}

		public function create_share_url() {

			if( !isset($_POST['image']) || !isset($_POST['product']) )
				die;

			if (!preg_match('/data:([^;]*);base64,(.*)/', strip_tags( $_POST['image'] ), $matches)) {
		    	echo json_encode(array(
					'error' => __('Image string is not a valid Data URL.', 'radykal')
				));
				die;
			}

			$share_dir = FPD_WP_CONTENT_DIR . '/uploads/fpd_shares/';

			if( !file_exists($share_dir) )
				wp_mkdir_p($share_dir);

			$today = date('Y-m-d');
			$timestamp = strtotime('now');

			if( !file_exists($share_dir.'/'.$today) )
				wp_mkdir_p($share_dir.'/'.$today);

			// Decode the data
			$image_content = base64_decode($matches[2]);
			$image_name = $timestamp.".png";
			//create png from decoded base 64 string and save the image in the parent folder
			$result = @file_put_contents($share_dir.'/'.$today.'/'.$image_name, $image_content);

			if($result === false) {
				echo json_encode(array(
					'error' => __('Image could not be created. Please try again!', 'radykal')
				));
				die;
			}

			//set transient to store product
			$cache_days = intval(fpd_get_option('fpd_sharing_cache_days')) * DAY_IN_SECONDS;

			$transient_val = array(
				'image_url' => content_url('/uploads/fpd_shares/'.$today.'/'.$image_name),
				'product' => strip_tags( $_POST['product'] )
			);
			$transient_result = set_transient('fpd_share_'.$timestamp, $transient_val, $cache_days);

			if( $transient_result ) {

				echo json_encode(array(
					'share_id' => $timestamp,
					'image_url' => content_url('/uploads/fpd_shares/'.$today.'/'.$image_name)
				));

			}

			die;
		}

		public static function get_share_html() {

			ob_start();
			?>
			<div class="fpd-share-design">
				<a href="#" id="fpd-share-button" class="<?php echo fpd_get_option('fpd_start_customizing_css_class'); ?>" ><i class="fpd-icon-share"></i><?php echo FPD_Settings_Labels::get_translation( 'misc', 'share:_button' ); ?></a>
				<div>
					<p class="fpd-share-process fpd-hidden"><?php echo FPD_Settings_Labels::get_translation( 'misc', 'share:_process' ); ?></p>
					<div class="fpd-share-widget"></div>
					<a href="" target="_blank" class="fpd-share-url fpd-hidden"></a>
				</div>
			</div>
			<?php

			$output = ob_get_contents();
			ob_end_clean();

			return $output;

		}
	}
}

new FPD_Share();

?>