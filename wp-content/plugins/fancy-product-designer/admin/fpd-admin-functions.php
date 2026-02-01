<?php

if (! defined('ABSPATH')) exit; // Exit if accessed directly

function fpd_admin_get_file_content($file)
{

	$result = false;
	if (function_exists('curl_exec')) {

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $file);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
		$result = curl_exec($ch);
		curl_close($ch);

		if ($result === false) {
			//fpd_logger( curl_error($ch) );
		}
	}

	//if curl does not work, use file_get_contents
	if ($result == false && function_exists('file_get_contents')) {
		$result = @file_get_contents($file);
	}

	return $result !== false ? $result : false;
}

function fpd_admin_upload_image_to_wp($name, $base64_image, $add_to_library = true)
{

	//upload to wordpress
	$upload = wp_upload_bits($name, null, base64_decode($base64_image));

	//add to media library
	if ($add_to_library && isset($upload['url'])) {
		media_sideload_image($upload['url'], 0);
	}

	return $upload['error'] === false ? $upload['url'] : false;
}

function fpd_admin_get_all_fancy_products()
{

	$products = FPD_Product::get_products(array(
		'order_by' 	=> "ID ASC",
	));

	$products_arr = array();
	foreach ($products as $product) {
		$products_arr[$product->ID] = $product->title;
	}

	return $products_arr;
}

function fpd_admin_get_all_fancy_product_categories()
{

	$categories = FPD_Category::get_categories(array(
		'order_by' => 'title ASC'
	));

	$categories_arr = array();

	foreach ($categories as $category) {
		$categories_arr[$category->ID] = $category->title;
	}

	return $categories_arr;
}

function fpd_admin_delete_directory($dir)
{

	$iterator = new RecursiveDirectoryIterator($dir);
	foreach (new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST) as $file) {

		if ($file->getFilename() == '.' || $file->getFilename() == '..')
			continue;

		if ($file->isDir()) {
			@rmdir($file->getPathname());
		} else {
			@unlink($file->getPathname());
		}
	}

	@rmdir($dir);
}

function fpd_output_top_level_design_cat_options($echoOutput = false)
{

	//get all created categories
	$categories = FPD_Designs::get_categories(true);

	$category_options = array();
	foreach ($categories as $category) {

		$category = (array) $category;
		$optionVal =  $category['ID'];

		if ($echoOutput)
			echo '<option value="' . $optionVal . '">' . $category['title'] . '</option>';
		else
			$category_options[$optionVal] = $category['title'];
	}

	return $category_options;
}


function fpd_output_admin_notice($type, $headline, $message, $condition = true, $name = null, $dismissable = false, $inline = false)
{

	$dismiss_option = null;
	if ($name)
		$dismiss_option = get_option('fpd_notification_' . $name);

	$inline = $inline ? ' inline' : '';

	//if condition is true and dismiss is not available or not set, show notice
	if ($condition && empty($dismiss_option)) {

?>
		<div class="notice notice-<?php echo $type . $inline; ?> fpd-dismiss-notification" style="position: relative;">
			<?php if ($dismissable) echo '<button class="notice-dismiss" value="' . $name . '"></button>'; ?>
			<?php if ($headline) echo '<h4 style="margin-bottom: 5px;">' . $headline . ' </h4>'; ?>
			<p><?php echo $message; ?></p>
		</div>
<?php

	}
}

function fpd_copy_file_to_directory($source_file, $destination_dir)
{
	try {

		// Validate input
		if (empty($source_file)) {
			return false;
		}

		// create destination directory if it doesn't exist and set its permissions
		wp_mkdir_p($destination_dir);
		chmod($destination_dir, 0777);

		// Sanitize and validate destination directory
		$destination_dir = realpath($destination_dir);
		if (!$destination_dir || !is_dir($destination_dir) || !is_writable($destination_dir)) {
			return false;
		}

		// Get file information
		$file_info = pathinfo($source_file);
		$file_name = sanitize_file_name($file_info['basename']);
		$file_extension = strtolower($file_info['extension']);

		// Define allowed file extensions (adjust as needed)
		$allowed_extensions = array('jpg', 'jpeg', 'png', 'svg', 'zip', 'pdf');

		// Check if the file extension is allowed
		if (!in_array($file_extension, $allowed_extensions)) {
			return false;
		}

		// Generate a unique filename
		$unique_filename = wp_unique_filename($destination_dir, $file_name);
		$destination_path = $destination_dir . DIRECTORY_SEPARATOR . $unique_filename;

		// Handle both local files and URLs
		if (filter_var($source_file, FILTER_VALIDATE_URL)) {
			// Remote file
			$file_content = wp_remote_request($source_file, array('sslverify' => false));
			if (is_wp_error($file_content) || wp_remote_retrieve_response_code($file_content) !== 200) {
				$error_message = is_wp_error($file_content) ? $file_content->get_error_message() : 'HTTP Error: ' . wp_remote_retrieve_response_code($file_content);
				throw new Exception('Failed to fetch file content: ' . $error_message);
			}
			$file_content = wp_remote_retrieve_body($file_content);
		} else {
			// Local file
			if (!file_exists($source_file) || !is_readable($source_file)) {
				throw new Exception('Source file does not exist or is not readable');
			}
			$file_content = file_get_contents($source_file);
			if ($file_content === false) {
				throw new Exception('Failed to read source file');
			}
		}

		// Verify file mime type
		$finfo = new finfo(FILEINFO_MIME_TYPE);
		$mime_type = $finfo->buffer($file_content);
		$allowed_mime_types = array('image/jpeg', 'image/png', 'image/svg+xml', 'application/pdf', 'application/zip');
		if (!in_array($mime_type, $allowed_mime_types)) {
			return false;
		}

		// Write file
		if (file_put_contents($destination_path, $file_content) === false) {
			return false;
		}

		return $unique_filename;
	} catch (Exception $e) {
		// Log error
		throw new Exception('Error copying file to directory: ' . $e->getMessage());
	}
}

function fpd_admin_upload_image_media($image_url, $return_id = false)
{

	$file_array = array();

	// get content type from image url
	$headers = get_headers($image_url, 1);
	$type = $headers['Content-Type'];
	//get extension from string
	$ext = explode('/', $type);

	//get file name without extension
	$file_array['name'] = basename($image_url, '.' . $ext[1]) . '.' . $ext[1];
	// Download file to temp location.
	$file_array['tmp_name'] = download_url($image_url);


	if (is_wp_error($file_array['tmp_name'])) {
		$error = $file_array['tmp_name'];
		return $image_url;
	}

	$id = media_handle_sideload($file_array, 0);


	if (is_wp_error($id)) {
		@unlink($file_array['tmp_name']);
		return $image_url;
	}

	if ($return_id)
		return $id;
	else
		return wp_get_attachment_url($id);
}

function fpd_admin_sent_admin_mail($subject, $message)
{

	$admin_mail_address = get_bloginfo('admin_email');
	wp_mail($admin_mail_address, $subject, $message);
}

?>