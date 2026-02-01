<?php

if (! defined('ABSPATH')) exit; // Exit if accessed directly

if (!class_exists('FPD_Admin_Ajax')) {

	class FPD_Admin_Ajax
	{

		public function __construct()
		{

			// - general
			add_action('wp_ajax_fpd_dismiss_notification', array(&$this, 'dismiss_notification'));

			// - products
			add_action('wp_ajax_fpd_get_products_config', array(&$this, 'get_products_config'));
			add_action('wp_ajax_fpd_get_products', array(&$this, 'get_products'));
			add_action('wp_ajax_fpd_create_product', array(&$this, 'create_product'));
			add_action('wp_ajax_fpd_update_product', array(&$this, 'update_product'));
			add_action('wp_ajax_fpd_delete_product', array(&$this, 'delete_product'));
			add_action('wp_ajax_fpd_export', array(&$this, 'export_product'));

			// - product categories
			add_action('wp_ajax_fpd_get_product_categories', array(&$this, 'get_product_categories'));
			add_action('wp_ajax_fpd_create_product_category', array(&$this, 'create_product_category'));
			add_action('wp_ajax_fpd_update_product_category', array(&$this, 'update_product_category'));
			add_action('wp_ajax_fpd_delete_product_category', array(&$this, 'delete_product_category'));

			// - templates
			add_action('wp_ajax_fpd_get_templates', array(&$this, 'get_templates'));

			// - views
			add_action('wp_ajax_fpd_get_view', array(&$this, 'get_view'));
			add_action('wp_ajax_fpd_update_view', array(&$this, 'update_view'));
			add_action('wp_ajax_fpd_delete_view', array(&$this, 'delete_view'));

			// - options
			add_action('wp_ajax_fpd_get_options', array(&$this, 'get_options'));
			add_action('wp_ajax_fpd_get_options_group', array(&$this, 'get_options_group'));
			add_action('wp_ajax_fpd_update_options', array(&$this, 'update_options'));

			// - UI composer
			add_action('wp_ajax_fpd_get_colors_css', array(&$this, 'get_colors_css'));
			add_action('wp_ajax_fpd_get_ui_layouts', array(&$this, 'get_ui_layouts'));
			add_action('wp_ajax_fpd_get_ui_layout', array(&$this, 'get_ui_layout'));
			add_action('wp_ajax_fpd_create_ui_layout', array(&$this, 'create_ui_layout'));
			add_action('wp_ajax_fpd_update_ui_layout', array(&$this, 'update_ui_layout'));
			add_action('wp_ajax_fpd_delete_ui_layout', array(&$this, 'delete_ui_layout'));

			// - design categories
			add_action('wp_ajax_fpd_get_design_categories', array(&$this, 'get_design_categories'));
			add_action('wp_ajax_fpd_get_design_category', array(&$this, 'get_design_category'));
			add_action('wp_ajax_fpd_create_design_category', array(&$this, 'create_design_category'));
			add_action('wp_ajax_fpd_update_design_category', array(&$this, 'update_design_category'));
			add_action('wp_ajax_fpd_delete_design_category', array(&$this, 'delete_design_category'));

			// - orders
			add_action('wp_ajax_fpd_get_orders', array(&$this, 'get_orders'));
			add_action('wp_ajax_fpd_get_order', array(&$this, 'get_order'));
			add_action('wp_ajax_fpd_update_order', array(&$this, 'update_order'));
			add_action('wp_ajax_fpd_delete_order', array(&$this, 'delete_order'));
			add_action('wp_ajax_fpd_create_file_export', array(&$this, 'create_file_export'));

			// - settings
			add_action('wp_ajax_fpd_upload_font', array(&$this, 'upload_font'));
			add_action('wp_ajax_fpd_delete_font', array(&$this, 'delete_font'));
			add_action('wp_ajax_fpd_install_3d_model', array(&$this, 'install_3d_model'));
			add_action('wp_ajax_fpd_pro_export_generate_test_file', array(&$this, 'pro_export_generate_test_file'));

			// - status
			add_action('wp_ajax_fpd_reset_image_sources', array(&$this, 'reset_image_sources'));

			// - pricing rules
			add_action('wp_ajax_fpd_update_pricing_rules', array(&$this, 'update_pricing_rules'));

			// - genius
			add_action('wp_ajax_fpd_genius_get_client_status', array(&$this, 'genius_get_client_status'));
		}

		public function dismiss_notification()
		{

			if (!isset($_POST['name'])) die;

			check_ajax_referer('fpd_ajax_nonce');

			$name = preg_replace('!\s+!', '', $_POST['name']);
			$success = update_option('fpd_notification_' . $name, true);

			echo json_encode(array(
				'name' => $_POST['name'],
				'success' => $success
			));

			die;
		}

		public function get_products_config()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$config = array(
				'threeJsModels' => FPD_3D_Preview::get_installed_models()
			);

			echo (json_encode($config));

			die;
		}

		public function get_products()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$defaults = array(
				'include_views' => true,
				'page' => 1,
				'limit' => 20,
				'search' => null,
				'category_id' => null,
				'cols' => '*',
				'filter_by' => 'ID',
				'sort_by' => 'ASC',
				'user_id' => null
			);

			$args = wp_parse_args($_GET, $defaults);

			$response = FPD_Resource_Products::get_products($args);

			if (is_wp_error($response)) {
				wp_send_json_error($response->get_error_message(), 500);
			} else {
				echo json_encode($response);
			}

			die;
		}

		public function create_product()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$payload = json_decode(file_get_contents('php://input'), true);

			$response = FPD_Resource_Products::create_product($payload);

			if (is_wp_error($response)) {
				wp_send_json_error($response->get_error_message(), 500);
			} else {
				echo json_encode($response);
			}

			die;
		}

		public function update_product()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$payload = json_decode(file_get_contents('php://input'), true);

			$response = FPD_Resource_Products::update_product($payload['id'], $payload['data']);

			if (is_wp_error($response)) {
				wp_send_json_error($response->get_error_message(), 500);
			} else {
				echo json_encode($response);
			}

			die;
		}

		public function delete_product()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$payload = json_decode(file_get_contents('php://input'), true);

			$response = FPD_Resource_Products::delete_product($payload['id']);

			if (is_wp_error($response)) {
				wp_send_json_error($response->get_error_message(), 500);
			} else {
				echo json_encode($response);
			}

			die;
		}

		public function export_product()
		{

			if (!isset($_GET['id']))
				exit;

			check_ajax_referer('fpd_ajax_nonce');

			$product_id = intval($_GET['id']);
			//$product_id = 11;

			if (!class_exists('ZipArchive')) {
				wp_send_json_error('ZipArchive extension is not enabled in your PHP environment.', 500);
				die;
			}

			$upload_dir = wp_upload_dir();
			$upload_dir = $upload_dir['basedir'];

			$exports_dir = $upload_dir . '/fpd_exports/';

			wp_mkdir_p($exports_dir);

			//temp export dir
			$temp_export_dir = $exports_dir . 'product_' . $product_id;
			wp_mkdir_p($temp_export_dir);

			$fp = new FPD_Product($product_id);

			//final_json
			$final_json = array();
			$final_json['title'] = $fp->get_title();
			$product_thumbnail = $fp->get_thumbnail();

			//product thumbnail
			if ($source_name = fpd_copy_file_to_directory($product_thumbnail, $temp_export_dir)) {
				$product_thumbnail = $source_name;
			}
			$final_json['thumbnail'] = $product_thumbnail;
			$final_json['options'] = $fp->get_options();
			$final_json['views'] = array();

			$views = $fp->get_views();

			foreach ($views as $view) {

				$elements = $view->elements;
				if (!is_array($elements)) {
					continue;
				}
				for ($i = 0; $i < sizeof($elements); $i++) {

					$source = $elements[$i]['source'];

					if ($elements[$i]['type'] == 'image' && base64_encode(base64_decode($source, true)) !== $source) {

						//image layers in views
						if ($source_name = fpd_copy_file_to_directory($source, $temp_export_dir)) {
							$elements[$i]['source'] = $source_name;
						}
					}
				}

				//final_view
				$final_view = array();
				$final_view['title'] = $view->title;
				$view_thumbnail = $view->thumbnail;
				//view thumbnail
				if ($source_name = fpd_copy_file_to_directory($view_thumbnail, $temp_export_dir)) {
					$view_thumbnail = $source_name;
				}
				$final_view['thumbnail'] = $view_thumbnail;
				$final_view['elements'] = $elements;

				$fancy_view = new FPD_View($view->ID);
				$view_options = $fancy_view->get_options();

				//export mask image
				if (isset($view_options['mask']) && isset($view_options['mask']['url'])) {
					if ($source_name = fpd_copy_file_to_directory($view_options['mask']['url'], $temp_export_dir)) {
						$view_options['mask']['url'] = $source_name;
					}
				}

				$final_view['options'] = $view_options;


				array_push($final_json['views'], $final_view);
			}

			$fop = fopen($temp_export_dir . '/product.json', 'w');
			fwrite($fop, json_encode($final_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
			fclose($fop);

			$zipname =  'product_' . $product_id . '.zip';
			$zip_path =  $exports_dir . $zipname;
			$zip = new ZipArchive;
			$zip->open($zip_path, ZipArchive::CREATE);

			if ($handle = opendir($temp_export_dir)) {

				while (false !== ($entry = readdir($handle))) {

					if ($entry != "." && $entry != ".." && !strstr($entry, '.php')) {
						$zip->addFile($temp_export_dir . '/' . $entry, $entry);
					}
				}

				closedir($handle);
			}

			$zip->close();

			fpd_admin_delete_directory($temp_export_dir);

			header("Content-type: application/zip");
			header("Content-Disposition: attachment; filename=$zipname");
			header("Content-length: " . filesize($zip_path));
			header("Pragma: no-cache");
			header("Expires: 0");
			readfile("$zip_path");

			unlink($zip_path);

			die;
		}

		public function get_product_categories()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$response = FPD_Resource_Products::get_all_product_categories();

			echo json_encode($response);

			die;
		}

		public function create_product_category()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$payload = json_decode(file_get_contents('php://input'), true);

			$response = FPD_Resource_Products::create_product_category($payload);

			if (is_wp_error($response)) {
				wp_send_json_error($response->get_error_message(), 500);
			} else {
				echo json_encode($response);
			}

			die;
		}

		public function update_product_category()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$payload = json_decode(file_get_contents('php://input'), true);

			$response = FPD_Resource_Products::update_product_category($payload['id'], $payload['data']);

			if (is_wp_error($response)) {
				wp_send_json_error($response->get_error_message(), 500);
			} else {
				echo json_encode($response);
			}

			die;
		}

		public function delete_product_category()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$payload = json_decode(file_get_contents('php://input'), true);

			$response = FPD_Resource_Products::delete_product_category($payload['id']);

			if (is_wp_error($response)) {
				wp_send_json_error($response->get_error_message(), 500);
			} else {
				echo json_encode($response);
			}

			die;
		}

		public function get_templates()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$response = FPD_Resource_Templates::get_product_templates();

			if (is_wp_error($response)) {
				wp_send_json_error($response->get_error_message(), 500);
			} else {
				echo json_encode($response);
			}

			die;
		}

		public function get_view()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$response = FPD_Resource_Views::get_product_view(intval($_GET['id']));

			if (is_wp_error($response)) {
				wp_send_json_error($response->get_error_message(), 500);
			} else {
				echo json_encode($response);
			}

			die;
		}

		public function update_view()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$payload = json_decode(file_get_contents('php://input'), true);

			$response = FPD_Resource_Views::update_product_view($payload['id'], $payload['data']);

			if (is_wp_error($response)) {
				wp_send_json_error($response->get_error_message(), 500);
			} else {
				echo json_encode($response);
			}

			die;
		}

		public function delete_view()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$payload = json_decode(file_get_contents('php://input'), true);

			$response = FPD_Resource_Views::delete_product_view($payload['id']);

			if (is_wp_error($response)) {
				wp_send_json_error($response->get_error_message(), 500);
			} else {
				echo json_encode($response);
			}

			die;
		}

		public function get_options()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$option_keys = isset($_GET['keys']) ? $_GET['keys'] : array();

			$response = FPD_Resource_Options::get_options($option_keys);

			if (is_wp_error($response)) {
				wp_send_json_error($response->get_error_message(), 500);
			} else {
				echo json_encode($response);
			}

			die;
		}

		public function get_options_group()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$defaults = array(
				'group' => 'general',
				'lang_code' => null,
			);

			$args = wp_parse_args($_GET, $defaults);

			$response = FPD_Resource_Options::get_options_group($args);

			if (is_wp_error($response)) {
				wp_send_json_error($response->get_error_message(), 500);
			} else {
				echo json_encode($response);
			}

			die;
		}

		public function update_options()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$payload = json_decode(file_get_contents('php://input'), true);

			$response = FPD_Resource_Options::update_options($payload);

			if (is_wp_error($response)) {
				wp_send_json_error($response->get_error_message(), 500);
			} else {
				echo json_encode($response);
			}

			die;
		}

		public function get_colors_css()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$payload = json_decode(file_get_contents('php://input'), true);

			if (isset($payload['primary_color'])) {

				echo json_encode(array(
					'css' => ":root {--fpd-primary-color: " . $payload['primary_color'] . ";--fpd-secondary-color: " . $payload['secondary_color'] . ";}"
				));
			}

			die;
		}

		public function get_ui_layouts()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$response = FPD_Resource_UI_Layouts::get_ui_layouts();

			if (is_wp_error($response)) {
				wp_send_json_error($response->get_error_message(), 500);
			} else {
				echo json_encode($response);
			}

			die;
		}

		public function get_ui_layout()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$response = FPD_Resource_UI_Layouts::get_ui_layout($_GET['id']);

			if (is_wp_error($response)) {
				wp_send_json_error($response->get_error_message(), 500);
			} else {
				echo json_encode($response);
			}

			die;
		}

		public function create_ui_layout()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$payload = json_decode(file_get_contents('php://input'), true);

			$response = FPD_Resource_UI_Layouts::create_ui_layout($payload);

			if (is_wp_error($response)) {
				wp_send_json_error($response->get_error_message(), 500);
			} else {
				echo json_encode($response);
			}

			die;
		}

		public function update_ui_layout()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$payload = json_decode(file_get_contents('php://input'), true);

			$response = FPD_Resource_UI_Layouts::update_ui_layout($payload['id'], $payload['data']);

			if (is_wp_error($response)) {
				wp_send_json_error($response->get_error_message(), 500);
			} else {
				echo json_encode($response);
			}

			die;
		}

		public function delete_ui_layout()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$payload = json_decode(file_get_contents('php://input'), true);

			$response = FPD_Resource_UI_Layouts::delete_ui_layout($payload['id']);

			if (is_wp_error($response)) {
				wp_send_json_error($response->get_error_message(), 500);
			} else {
				echo json_encode($response);
			}

			die;
		}

		public function get_design_categories()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$response = FPD_Resource_Designs::get_design_categories();

			if (is_wp_error($response)) {
				wp_send_json_error($response->get_error_message(), 500);
			} else {
				echo json_encode($response);
			}

			die;
		}

		public function get_design_category()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$response = FPD_Resource_Designs::get_single_category(intval($_GET['id']));

			if (is_wp_error($response)) {
				wp_send_json_error($response->get_error_message(), 500);
			} else {
				echo json_encode($response);
			}

			die;
		}

		public function create_design_category()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$payload = json_decode(file_get_contents('php://input'), true);

			$response = FPD_Resource_Designs::create_design_category($payload['title']);

			if (is_wp_error($response)) {
				wp_send_json_error($response->get_error_message(), 500);
			} else {
				echo json_encode($response);
			}

			die;
		}

		public function update_design_category()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$payload = json_decode(file_get_contents('php://input'), true);

			$response = FPD_Resource_Designs::update_design_category($payload['id'], $payload['data']);

			if (is_wp_error($response)) {
				wp_send_json_error($response->get_error_message(), 500);
			} else {
				echo json_encode($response);
			}

			die;
		}

		public function delete_design_category()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$payload = json_decode(file_get_contents('php://input'), true);

			$response = FPD_Resource_Designs::delete_design_category($payload['id']);

			if (is_wp_error($response)) {
				wp_send_json_error($response->get_error_message(), 500);
			} else {
				echo json_encode($response);
			}

			die;
		}

		public function get_orders()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$defaults = array(
				'page' => 1,
				'type' => 'shortcode',
			);

			$args = wp_parse_args($_GET, $defaults);

			$response = FPD_Resource_Orders::get_all_orders($args);

			if (is_wp_error($response)) {
				wp_send_json_error($response->get_error_message(), 500);
			} else {
				echo json_encode($response);
			}

			die;
		}

		public function get_order()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$response = FPD_Resource_Orders::get_single_order($_GET);

			if (is_wp_error($response)) {
				wp_send_json_error($response->get_error_message(), 500);
			} else {
				echo json_encode($response);
			}

			die;
		}

		public function update_order()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$payload = json_decode(file_get_contents('php://input'), true);

			$response = FPD_Resource_Orders::update_order($payload['id'], $payload['data']);

			if (is_wp_error($response)) {
				wp_send_json_error($response->get_error_message(), 500);
			} else {
				echo json_encode($response);
			}

			die;
		}

		public function delete_order()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$payload = json_decode(file_get_contents('php://input'), true);

			$response = FPD_Resource_Orders::delete_order($payload['id']);

			if (is_wp_error($response)) {
				wp_send_json_error($response->get_error_message(), 500);
			} else {
				echo json_encode($response);
			}

			die;
		}

		public function create_file_export()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$payload = json_decode(file_get_contents('php://input'), true);

			$response = $file_url = null;

			if (isset($payload['summary_json']) && is_string($payload['summary_json']))
				$payload['summary_json'] = json_decode($payload['summary_json'], true);

			//print-ready export
			if (isset($payload['print_ready']) && $payload['print_ready']) {


				if (Fancy_Product_Designer::pro_export_enabled()) {

					try {

						$res = Fancy_Product_Designer::create_print_ready_file($payload);

						if (is_string($res)) { //a string is returned as response which holds the file url to download
							$file_url = content_url('/fancy_products_orders/print_ready_files/' . $res);
						}
					} catch (Exception $e) {

						$file_url = null;
						$response = new WP_Error(
							'print-ready-export-failed',
							$e->getMessage()
						);
					}
				} else
					$response = new WP_Error(
						'pro-export-not-installed',
						__('The PRO export add-on is not installed', 'radykal')
					);
			}
			//basic export
			else if (isset($payload['svg_data'])) {

				try {
					$file_url = FPD_File_Export::svg_to_pdf($payload);
				} catch (Exception $e) {

					$file_url = null;
					$response = new WP_Error(
						'basic-export-failed',
						$e->getMessage()
					);
				}
			}

			if (is_wp_error($response)) {
				wp_send_json_error($response->get_error_message(), 500);
			} else {
				echo json_encode(array(
					'url' => $file_url
				));
			}

			die;
		}

		public function upload_font()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$file = $_FILES['file'];
			$file_name = $_POST['fileName'];

			if (!file_exists('FPD_FONTS_DIR'))
				wp_mkdir_p(FPD_FONTS_DIR);

			$filepath = FPD_FONTS_DIR . str_replace(' ', '_', $file_name) . '.ttf';

			//replace font file with the same name
			if (file_exists($filepath))
				unlink($filepath);

			$result = move_uploaded_file($file['tmp_name'], $filepath);

			if ($result) {
				echo json_encode(FPD_Settings_Fonts::get_custom_fonts(false));
			} else {
				wp_send_json_error('File could not be uploaded', 500);
			}

			die;
		}

		public function delete_font()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$payload = json_decode(file_get_contents('php://input'), true);

			if (!isset($payload['font_name']))
				exit;

			$font_name = $payload['font_name'];
			$font_name = str_replace(' ', '_', $font_name);
			$variants = array('', '__bold', '__italic', '__bolditalic');

			foreach ($variants as $variant) {

				$filepath = FPD_FONTS_DIR . $font_name . $variant . '.ttf';

				if (file_exists($filepath))
					unlink($filepath);
			}

			echo json_encode(FPD_Settings_Fonts::get_custom_fonts(false));

			die;
		}

		public function install_3d_model()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$payload = json_decode(file_get_contents('php://input'), true);

			if (!isset($payload['model']))
				exit;

			$downloaded_filename = fpd_copy_file_to_directory($payload['model']['url'], FPD_3D_Preview::$root_dir);

			if ($downloaded_filename) {

				$local_zip_path = FPD_3D_Preview::$root_dir . $downloaded_filename;

				$zip = new ZipArchive;
				$zip_res = $zip->open($local_zip_path);

				if ($zip_res === TRUE) {

					@unlink($local_zip_path);
					$zip->extractTo(FPD_3D_Preview::$root_dir);
					$zip->close();

					// Remove the '__MACOSX/' folder, if it exists
					$macosx_dir = FPD_3D_Preview::$root_dir . '__MACOSX';
					if (is_dir($macosx_dir)) {
						// Recursive directory removal
						$files = new RecursiveIteratorIterator(
							new RecursiveDirectoryIterator($macosx_dir, FilesystemIterator::SKIP_DOTS),
							RecursiveIteratorIterator::CHILD_FIRST
						);

						foreach ($files as $fileinfo) {
							$todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
							$todo($fileinfo->getRealPath());
						}

						rmdir($macosx_dir);
					}
				}
			} else {

				wp_send_json_error('ZIP could not be downloaded. Please try again!', 500);
				die;
			}

			echo json_encode(FPD_3D_Preview::get_installed_models());
			die;
		}

		public function reset_image_sources()
		{

			if (!isset($_POST['old_domain']))
				exit;

			check_ajax_referer('fpd_ajax_nonce', '_ajax_nonce');

			$old_domain = trim($_POST['old_domain']);
			if (substr($old_domain, -1) == '/') {
				$old_domain = substr($old_domain, 0, -1);
			}

			$url_parts = parse_url(site_url());
			$current_domain = $url_parts['scheme'] . '://' . $url_parts['host'];

			global $wpdb;

			//products
			$updated_products = 0;
			if (fpd_table_exists(FPD_PRODUCTS_TABLE)) {

				$products = $wpdb->get_results("SELECT ID, thumbnail FROM " . FPD_PRODUCTS_TABLE);

				foreach ($products as $product) {

					$updated_cols = array();
					$formats = array();
					$thumbnail = stripslashes(maybe_unserialize($product->thumbnail));

					if (strpos($thumbnail, $old_domain) !== false) {
						$updated_cols['thumbnail'] = str_replace($old_domain, $current_domain, $thumbnail);
						$formats[] = '%s';
					}

					if (count($updated_cols) > 0) {
						$wpdb->update(FPD_PRODUCTS_TABLE, $updated_cols, array('ID' => $product->ID), $formats, '%d');
						$updated_products++;
					}
				}
			}

			//views
			$updated_views = 0;
			if (fpd_table_exists(FPD_VIEWS_TABLE)) {

				$views = $wpdb->get_results("SELECT ID, thumbnail, elements FROM " . FPD_VIEWS_TABLE);
				foreach ($views as $view) {

					$updated_cols = array();
					$formats = array();
					$thumbnail = stripslashes(maybe_unserialize($view->thumbnail));
					$elements = maybe_unserialize($view->elements);

					if (strpos($thumbnail, $old_domain) !== false) {
						$updated_cols['thumbnail'] = str_replace($old_domain, $current_domain, $thumbnail);
						$formats[] = '%s';
					}

					if (strpos($elements, $old_domain) !== false) {
						$updated_cols['elements'] = str_replace($old_domain, $current_domain, $elements);
						$formats[] = '%s';
					}

					if (count($updated_cols) > 0) {
						$wpdb->update(FPD_VIEWS_TABLE, $updated_cols, array('ID' => $view->ID), $formats, '%d');
						$updated_views++;
					}
				}
			}

			//designs
			$updated_designs = 0;
			if (fpd_table_exists(FPD_DESIGNS_TABLE)) {

				$cats = $wpdb->get_results("SELECT ID, thumbnail, designs FROM " . FPD_DESIGNS_TABLE);
				foreach ($cats as $cat) {

					$updated_cols = array();
					$formats = array();
					$thumbnail = stripslashes(maybe_unserialize($cat->thumbnail));
					$designs = stripslashes(maybe_unserialize($cat->designs));

					if (strpos($thumbnail, $old_domain) !== false) {
						$updated_cols['thumbnail'] = str_replace($old_domain, $current_domain, $thumbnail);
						$formats[] = '%s';
					}

					if (strpos($designs, $old_domain) !== false) {
						$updated_cols['designs'] = str_replace($old_domain, $current_domain, $designs);
						$formats[] = '%s';
					}

					if (count($updated_cols) > 0) {
						$wpdb->update(FPD_DESIGNS_TABLE, $updated_cols, array('ID' => $cat->ID), $formats, '%d');
						$updated_designs++;
					}
				}
			}

			//shortcode orders
			$updated_sc_orders = 0;
			if (fpd_table_exists(FPD_ORDERS_TABLE)) {

				$sc_orders = $wpdb->get_results("SELECT ID, views FROM " . FPD_ORDERS_TABLE);
				foreach ($sc_orders as $sc_order) {

					$updated_cols = array();
					$formats = array();
					$views = stripslashes(maybe_unserialize($sc_order->views));

					if (strpos($views, $old_domain) !== false) {
						$updated_cols['views'] = str_replace($old_domain, $current_domain, $views);
						$formats[] = '%s';
					}

					if (count($updated_cols) > 0) {
						$wpdb->update(FPD_ORDERS_TABLE, $updated_cols, array('ID' => $sc_order->ID), $formats, '%d');
						$updated_sc_orders++;
					}
				}
			}

			//wc orders
			$updated_wc_orders = 0;
			if (fpd_table_exists(FPD_ORDERS_TABLE) && class_exists('FPD_WC_Order')) {

				$wc_orders = FPD_WC_Order::get_all_fpd_orders();
				foreach ($wc_orders as $wc_order) {

					foreach ($wc_order['order_items'] as $order_item) {

						$item_fpd_data = stripslashes(fpd_wc_get_order_item_meta($order_item['ID']));

						if (strpos($item_fpd_data, $old_domain) !== false) {
							wc_update_order_item_meta($order_item['ID'], '_fpd_data', str_replace($old_domain, $current_domain, $item_fpd_data));
							$updated_wc_orders++;
						}
					}
				}
			}

			echo json_encode(array(
				'old_domain' 		=> $old_domain,
				'new_domain' 		=> $current_domain,
				'updated_products' 	=> $updated_products,
				'updated_views' 	=> $updated_views,
				'updated_designs'  	=> $updated_designs,
				'updated_wc_orders' => $updated_wc_orders,
				'updated_sc_orders' => $updated_sc_orders,
				'updated_wc_orders' => $updated_wc_orders,
			));

			die;
		}

		public function update_pricing_rules()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$payload = json_decode(file_get_contents('php://input'), true);

			$response = FPD_Resource_Pricing_Rules::update_pricing_rules($payload['groups']);

			if (is_wp_error($response)) {
				wp_send_json_error($response->get_error_message(), 500);
			} else {
				echo json_encode($response);
			}

			die;
		}

		public function genius_get_client_status()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$license_key = get_option('fpd_genius_license_key', $GLOBALS['FPD_GENIUS_LICENSE_KEY']);

			if (empty($license_key)) {
				wp_send_json_error("No Genius License key entered.", 403);
				die;
			}

			$response = fpd_genius_request(
				'client'
			);

			if ($response['status'] !== 'success') {
				wp_send_json_error($response['message'], 403);
			} else {

				$license_data = $response['data'];
				$site_domain = fpd_get_domain_from_url(get_site_url());
				$access_until = isset($license_data['accessUntil']) ? strtotime($license_data['accessUntil']) : null;

				if (isset($license_data['domain']) && $license_data['domain'] != $site_domain) {
					wp_send_json_error("The Genius License key is not valid for this domain. Please first deregister, then register the license key in the Genius backend.", 403);
					die;
				} else if ($access_until && $access_until < time()) {
					wp_send_json_error('The Genius License key has expired.<br /><a href="https://fancyproductdesigner.com/pricing/" target="_blank">You need an active Genius licence in order to use the Pro Export.</a>', 403);
					die;
				}
				echo json_encode($response['message']);
			}

			die;
		}

		public function pro_export_generate_test_file()
		{

			check_ajax_referer('fpd_ajax_nonce');

			$response = $file_url = null;
			$payload = require_once(FPD_PLUGIN_DIR . '/pro-export/test-print-data.php');

			try {

				$res = Fancy_Product_Designer::create_print_ready_file($payload, true);

				if (is_string($res)) { //a string is returned as response which holds the file url to download
					$file_url = content_url('/fancy_products_orders/print_ready_files/' . $res);
				}
			} catch (Exception $e) {

				$file_url = null;
				$response = new WP_Error(
					'print-ready-export-failed',
					$e->getMessage()
				);
			}

			if (is_wp_error($response)) {
				wp_send_json_error($response->get_error_message(), 500);
			} else {
				echo json_encode(array(
					'url' => $file_url
				));
			}

			die;
		}
	}
}

new FPD_Admin_Ajax();
