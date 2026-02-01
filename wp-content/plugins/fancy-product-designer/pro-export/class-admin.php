<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


if( !class_exists('FPD_Pro_Export_Admin') ) {

	class FPD_Pro_Export_Admin {

		public function __construct() {

			//--Status Page

			//Tools
			add_action( 'fpd_status_tools_table_end', array(&$this, 'status_tools') );

		}

		public function status_tools() {

			if( isset($_POST['fpd_empty_print_ready_dir']) ) {
				fpd_admin_delete_directory( FPD_ORDER_DIR . 'print_ready_files/' );
			}

			$dir_bytes = $this->dir_size(FPD_ORDER_DIR . 'print_ready_files/');
			$dir_bytes = number_format($dir_bytes / 1048576, 2);

			?>
			<tr>
				<td>
					<em><?php _e('Print-Ready Files Directory Size', 'radykal'); ?></em>
					<span data-variation="tiny" data-tooltip="<?php esc_attr_e('The directory for storing the created print-ready files (wp-content/fancy_products_orders/print_ready_files).', 'radykal'); ?>">
						<i class="mdi mdi-information-outline icon"></i>
					</span>
				</td>
				<td>
					<?php echo $dir_bytes; ?>MB
				</td>
				<td>
					<form method="post" onsubmit="return confirm('<?php esc_attr_e( 'Do you really want to empty the print-ready directory?', 'radykal' ); ?>');">
						<button type="submit" name="fpd_empty_print_ready_dir" class="ui secondary tiny button">
							<?php _e('Empty', 'radykal'); ?>
						</button>
					</form>
				</td>
			</tr>
			<?php

		}

		private function dir_size($dir) {

		    $size = 0;
		    foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $each) {
		        $size += is_file($each) ? filesize($each) : $this->dir_size($each);
		    }

		    return $size;

		}

	}

	new FPD_Pro_Export_Admin();

}
?>