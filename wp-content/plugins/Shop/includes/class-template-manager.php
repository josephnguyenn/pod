<?php
/**
 * Template Manager
 * 
 * Handles template operations (duplicate, delete, fonts, save design)
 *
 * @package AdvancedProductDesigner
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class APD_Template_Manager
{
    /**
     * Main plugin instance reference
     * @var AdvancedProductDesigner
     */
    private $plugin;

    /**
     * Constructor
     * 
     * @param AdvancedProductDesigner $plugin Main plugin instance
     */
    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Register hooks
     */
    public function init()
    {
        add_action('wp_ajax_upload_svg', array($this, 'handle_svg_upload'));
        add_action('wp_ajax_save_design', array($this, 'save_design'));
        add_action('wp_ajax_download_order_svg', array($this, 'download_order_svg'));
    }

    /**
     * Duplicate template
     */
    public function duplicate_template()
    {
        if (!isset($_POST['template_id']) || !wp_verify_nonce($_POST['_wpnonce'], 'duplicate_template_' . $_POST['template_id'])) {
            wp_die('Security check failed');
        }

        $template_id = intval($_POST['template_id']);
        $template = get_post($template_id);

        if (!$template || $template->post_type !== 'apd_template') {
            wp_die('Template not found');
        }

        $new_template = array(
            'post_title' => $template->post_title . ' (Copy)',
            'post_content' => $template->post_content,
            'post_status' => 'draft',
            'post_type' => 'apd_template'
        );

        $new_id = wp_insert_post($new_template);

        if ($new_id) {
            // Copy meta data
            $meta_keys = array(
                '_apd_template_width',
                '_apd_template_height',
                '_apd_template_bg_type',
                '_apd_template_bg_color',
                '_apd_template_bg_image',
                '_apd_template_data',
                '_apd_allowed_material_categories'
            );

            foreach ($meta_keys as $key) {
                $value = get_post_meta($template_id, $key, true);
                if ($value) {
                    update_post_meta($new_id, $key, $value);
                }
            }

            $url = admin_url('admin.php?page=apd-templates&duplicated=1');
            if (!headers_sent()) {
                wp_safe_redirect($url);
                exit;
            } else {
                echo '<meta http-equiv="refresh" content="0;url=' . esc_url($url) . '">';
                echo '<script>window.location.href = ' . json_encode($url) . ';</script>';
                exit;
            }
        }
    }

    /**
     * Delete template
     */
    public function delete_template()
    {
        if (!isset($_POST['template_id']) || !wp_verify_nonce($_POST['_wpnonce'], 'delete_template_' . $_POST['template_id'])) {
            wp_die('Security check failed');
        }

        $template_id = intval($_POST['template_id']);
        $template = get_post($template_id);

        if (!$template || $template->post_type !== 'apd_template') {
            wp_die('Template not found');
        }

        wp_delete_post($template_id, true);

        $url = admin_url('admin.php?page=apd-templates&deleted=1');
        if (!headers_sent()) {
            wp_safe_redirect($url);
            exit;
        } else {
            echo '<meta http-equiv="refresh" content="0;url=' . esc_url($url) . '">';
            echo '<script>window.location.href = ' . json_encode($url) . ';</script>';
            exit;
        }
    }

    /**
     * Upload font
     */
    public function upload_font()
    {
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Insufficient permissions')));
        }

        if (!isset($_FILES['font_file']) || $_FILES['font_file']['error'] !== UPLOAD_ERR_OK) {
            wp_die(json_encode(array('success' => false, 'data' => 'No file uploaded or upload error')));
        }

        $file = $_FILES['font_file'];

        // Validate file type
        $allowed_types = array('ttf', 'otf', 'woff', 'woff2');
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($file_extension, $allowed_types)) {
            wp_die(json_encode(array('success' => false, 'data' => 'Invalid file type. Only TTF, OTF, WOFF, and WOFF2 files are allowed.')));
        }

        // Validate file size (5MB limit)
        if ($file['size'] > 5 * 1024 * 1024) {
            wp_die(json_encode(array('success' => false, 'data' => 'File too large. Maximum size is 5MB.')));
        }

        // Create uploads directory
        $upload_dir = wp_upload_dir();
        $fonts_dir = $upload_dir['basedir'] . '/fonts/';

        if (!file_exists($fonts_dir)) {
            wp_mkdir_p($fonts_dir);
        }

        // Generate unique filename
        $filename = sanitize_file_name($file['name']);
        $filename = pathinfo($filename, PATHINFO_FILENAME) . '_' . time() . '.' . $file_extension;
        $file_path = $fonts_dir . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            wp_die(json_encode(array('success' => false, 'data' => 'Failed to save file')));
        }

        // Generate font family name
        $font_name = pathinfo($file['name'], PATHINFO_FILENAME);
        $font_family = str_replace(array('-', '_'), ' ', $font_name);
        $font_family = ucwords($font_family);

        // Get font weight
        $font_weight = isset($_POST['font_weight']) ? sanitize_text_field($_POST['font_weight']) : '400';
        $valid_weights = array('100', '200', '300', '400', '500', '600', '700', '800', '900');
        if (!in_array($font_weight, $valid_weights)) {
            $font_weight = '400';
        }

        // Get file URL
        $file_url = $upload_dir['baseurl'] . '/fonts/' . $filename;

        // Store font info
        $uploaded_fonts = get_option('apd_uploaded_fonts', array());
        $uploaded_fonts[] = array(
            'name' => $font_name,
            'family' => $font_family,
            'url' => $file_url,
            'file' => $filename,
            'weight' => $font_weight,
            'uploaded' => current_time('mysql')
        );
        update_option('apd_uploaded_fonts', $uploaded_fonts);

        wp_die(json_encode(array(
            'success' => true,
            'data' => array(
                'name' => $font_name,
                'family' => $font_family,
                'url' => $file_url,
                'file' => $filename,
                'weight' => $font_weight
            )
        )));
    }

    /**
     * Delete font
     */
    public function delete_font()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $uploaded_fonts = get_option('apd_uploaded_fonts', array());
        $index = isset($_POST['index']) ? intval($_POST['index']) : null;
        $fileParam = isset($_POST['file']) ? sanitize_file_name($_POST['file']) : '';

        $removed = false;
        if ($fileParam) {
            foreach ($uploaded_fonts as $i => $font) {
                if (!empty($font['file']) && $font['file'] === $fileParam) {
                    $removed = $this->remove_font_entry($uploaded_fonts, $i);
                    break;
                }
            }
        } elseif ($index !== null && isset($uploaded_fonts[$index])) {
            $removed = $this->remove_font_entry($uploaded_fonts, $index);
        }

        if ($removed) {
            update_option('apd_uploaded_fonts', $uploaded_fonts);
            wp_send_json_success(true);
        }
        wp_send_json_error('Font not found');
    }

    /**
     * Remove font entry from array and delete file
     * 
     * @param array $uploaded_fonts Fonts array (passed by reference)
     * @param int $i Index to remove
     * @return bool Success
     */
    private function remove_font_entry(&$uploaded_fonts, $i)
    {
        $upload_dir = wp_upload_dir();
        $fonts_dir = trailingslashit($upload_dir['basedir']) . 'fonts/';
        $file = !empty($uploaded_fonts[$i]['file']) ? $uploaded_fonts[$i]['file'] : '';
        if ($file && file_exists($fonts_dir . $file)) {
            @unlink($fonts_dir . $file);
        }
        array_splice($uploaded_fonts, $i, 1);
        return true;
    }

    /**
     * Save template design via AJAX
     */
    public function save_template_design()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'apd_nonce')) {
            wp_send_json_error('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $template_id = intval($_POST['template_id']);
        $template_data = wp_unslash($_POST['template_data']);

        // Validate template exists
        $template = get_post($template_id);
        if (!$template || $template->post_type !== 'apd_template') {
            wp_send_json_error('Template not found');
        }

        // Parse and validate template data
        $data = json_decode($template_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('Invalid template data: ' . json_last_error_msg());
        }

        if (!is_array($data)) {
            wp_send_json_error('Template data must be an array');
        }

        // Sanitize background image data if present
        if (isset($data['canvas']['background']['image'])) {
            $image_data = $data['canvas']['background']['image'];
            if (strpos($image_data, 'data:image/') === 0) {
                $base64_data = substr($image_data, strpos($image_data, ',') + 1);
                if (!base64_decode($base64_data, true)) {
                    wp_send_json_error('Invalid background image data');
                }
            } else {
                if (!filter_var($image_data, FILTER_VALIDATE_URL)) {
                    wp_send_json_error('Invalid background image URL');
                }
            }
        }

        // Save template data
        update_post_meta($template_id, '_apd_template_data', $template_data);

        // Update canvas settings if provided
        if (isset($data['canvas'])) {
            $canvas = $data['canvas'];

            if (isset($canvas['width'])) {
                update_post_meta($template_id, '_apd_template_width', intval($canvas['width']));
            }

            if (isset($canvas['height'])) {
                update_post_meta($template_id, '_apd_template_height', intval($canvas['height']));
            }

            if (isset($canvas['background'])) {
                $bg = $canvas['background'];

                if (isset($bg['type'])) {
                    update_post_meta($template_id, '_apd_template_bg_type', sanitize_text_field($bg['type']));
                }

                if (isset($bg['color'])) {
                    update_post_meta($template_id, '_apd_template_bg_color', sanitize_hex_color($bg['color']));
                }

                if (isset($bg['image'])) {
                    update_post_meta($template_id, '_apd_template_bg_image', esc_url_raw($bg['image']));
                }
            }
        }

        wp_send_json_success(array(
            'message' => 'Template design saved successfully',
            'template_id' => $template_id
        ));
    }

    /**
     * Handle SVG upload
     */
    public function handle_svg_upload()
    {
        check_ajax_referer('apd_nonce', 'nonce');

        if (!current_user_can('upload_files')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        if (!isset($_FILES['svg_file']) || $_FILES['svg_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => 'No file uploaded'));
        }

        $file = $_FILES['svg_file'];

        $file_type = wp_check_filetype($file['name']);
        if ($file_type['type'] !== 'image/svg+xml') {
            wp_send_json_error(array('message' => 'Invalid file type. Only SVG files are allowed.'));
        }

        if ($file['size'] > 2 * 1024 * 1024) {
            wp_send_json_error(array('message' => 'File too large. Maximum size is 2MB.'));
        }

        $upload_dir = wp_upload_dir();
        $svg_dir = $upload_dir['basedir'] . '/apd-svg/';
        if (!is_dir($svg_dir)) {
            wp_mkdir_p($svg_dir);
        }

        $filename = 'svg_' . time() . '_' . sanitize_file_name($file['name']);
        $file_path = $svg_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            chmod($file_path, 0644);
            $file_url = $upload_dir['baseurl'] . '/apd-svg/' . $filename;

            wp_send_json_success(array(
                'url' => $file_url,
                'filename' => $filename,
                'message' => 'SVG uploaded successfully'
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to save file'));
        }
    }

    /**
     * Save design
     */
    public function save_design()
    {
        check_ajax_referer('apd_nonce', 'nonce');

        $design_data = array(
            'canvas_width' => intval($_POST['canvas_width']),
            'canvas_height' => intval($_POST['canvas_height']),
            'background_color' => sanitize_hex_color($_POST['background_color']),
            'elements' => json_decode(stripslashes($_POST['elements']), true),
            'created_at' => current_time('mysql')
        );

        $design_id = wp_insert_post(array(
            'post_type' => 'apd_design',
            'post_title' => 'Design ' . date('Y-m-d H:i:s'),
            'post_status' => 'publish',
            'meta_input' => array(
                '_apd_design_data' => $design_data
            )
        ));

        if ($design_id) {
            wp_send_json_success(array(
                'design_id' => $design_id,
                'message' => 'Design saved successfully'
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to save design'));
        }
    }

    /**
     * Download order SVG
     */
    public function download_order_svg()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }

        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        if (!$order_id) {
            wp_send_json_error(array('message' => 'Invalid order ID'));
            return;
        }

        $result = APD_Order_SVG_Resolver::get_svg_for_order($order_id);
        if (!$result || empty($result['content'])) {
            wp_send_json_error(array('message' => 'No SVG found for this order'));
            return;
        }
        $svg_content = $result['content'];

        // CRITICAL: Ensure material outline for logo is preserved in SVG
        // Check if logoMaterialPattern exists but logo paths don't have stroke with it
        // This ensures material outline is preserved correctly (consistent with PDF export)
        $svg_content = $this->ensure_logo_material_outline($svg_content, $order_id);

        $upload_dir = wp_upload_dir();
        $filename = 'order-' . $order_id . '-design.svg';
        $filepath = $upload_dir['path'] . '/' . $filename;
        
        if (file_put_contents($filepath, $svg_content)) {
            $file_url = $upload_dir['url'] . '/' . $filename;
            wp_send_json_success(array(
                'file_url' => $file_url,
                'filename' => $filename,
                'message' => 'SVG downloaded successfully'
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to save SVG file'));
        }
    }

    /**
     * Ensure material outline for logo is preserved in SVG
     * Similar to applyLogoMaterialOutline() in customizer.js
     * 
     * @param string $svg_content SVG content
     * @param int $order_id Order ID for logging
     * @return string SVG content with logo material outline preserved
     */
    private function ensure_logo_material_outline($svg_content, $order_id = 0)
    {
        // Check if logoMaterialPattern pattern definition exists
        $has_logo_pattern = preg_match('/<pattern[^>]*id=["\']logoMaterialPattern["\'][^>]*>/i', $svg_content);
        
        if (!$has_logo_pattern) {
            // No logoMaterialPattern found - material outline may not be applied
            error_log("APD Download SVG - Order #$order_id: ⚠️ logoMaterialPattern not found in SVG - material outline may be missing");
            return $svg_content;
        }

        // Check if pattern has embedded image (PNG/JPEG)
        $pattern_has_image = preg_match('/<pattern[^>]*id=["\']logoMaterialPattern["\'][^>]*>.*?<image[^>]*>/is', $svg_content);
        
        if (!$pattern_has_image) {
            error_log("APD Download SVG - Order #$order_id: ⚠️ logoMaterialPattern found but has no image - material outline may not work");
            return $svg_content;
        }

        // Check if logo paths have logoMaterialPattern stroke
        $logo_paths_with_stroke = preg_match_all('/<(path|polygon|rect|circle|ellipse|line|polyline)([^>]*stroke=["\']url\(#logoMaterialPattern\)["\'][^>]*)>/i', $svg_content);
        
        if ($logo_paths_with_stroke > 0) {
            // Logo paths already have material outline - preserve as-is
            error_log("APD Download SVG - Order #$order_id: ✅ Logo material outline already applied ($logo_paths_with_stroke paths with logoMaterialPattern stroke)");
            return $svg_content;
        }

        // Logo paths don't have material outline - need to apply it
        // Find logo paths (paths, polygons, etc. that might be logo elements)
        // Strategy: Apply logoMaterialPattern stroke to paths that don't have pattern strokes yet
        // This is a fallback to ensure material outline is preserved
        
        $logo_paths_found = preg_match_all('/<(path|polygon|rect|circle|ellipse|line|polyline)([^>]*)>/i', $svg_content, $matches, PREG_SET_ORDER);
        
        if ($logo_paths_found === 0) {
            error_log("APD Download SVG - Order #$order_id: ⚠️ No logo paths found to apply material outline");
            return $svg_content;
        }

        // Apply logoMaterialPattern stroke to logo paths that don't have pattern strokes
        $paths_modified = 0;
        $svg_content = preg_replace_callback(
            '/<(path|polygon|rect|circle|ellipse|line|polyline)([^>]*)>/i',
            function($match) use (&$paths_modified, $order_id) {
                $tag = $match[1];
                $attrs = $match[2];
                
                // Skip if already has pattern stroke (any pattern)
                if (preg_match('/stroke=["\']url\(#[^)]+\)["\']/i', $attrs)) {
                    return $match[0];
                }
                
                // Skip if has fill with pattern (might be logo fill, not outline)
                if (preg_match('/fill=["\']url\(#logoMaterialPattern\)["\']/i', $attrs)) {
                    return $match[0];
                }
                
                // Apply logoMaterialPattern stroke for material outline
                $new_attrs = $attrs;
                
                // Set fill to none (outline layer)
                if (!preg_match('/fill=/i', $attrs)) {
                    $new_attrs .= ' fill="none"';
                } else {
                    $new_attrs = preg_replace('/fill=["\'][^"\']*["\']/', 'fill="none"', $new_attrs);
                }
                
                // Add pattern stroke
                $new_attrs .= ' stroke="url(#logoMaterialPattern)"';
                
                // Add stroke attributes (default width if not present)
                if (!preg_match('/stroke-width=/i', $attrs)) {
                    $new_attrs .= ' stroke-width="6"'; // Default stroke width
                }
                if (!preg_match('/stroke-linejoin=/i', $attrs)) {
                    $new_attrs .= ' stroke-linejoin="round"';
                }
                if (!preg_match('/stroke-linecap=/i', $attrs)) {
                    $new_attrs .= ' stroke-linecap="round"';
                }
                if (!preg_match('/paint-order=/i', $attrs)) {
                    $new_attrs .= ' paint-order="stroke fill"';
                }
                
                $paths_modified++;
                
                return '<' . $tag . $new_attrs . '>';
            },
            $svg_content,
            50 // Limit to avoid over-applying
        );
        
        if ($paths_modified > 0) {
            error_log("APD Download SVG - Order #$order_id: ✅ Applied logoMaterialPattern stroke to $paths_modified logo paths (material outline preserved)");
        } else {
            error_log("APD Download SVG - Order #$order_id: ⚠️ No logo paths modified - material outline may already be applied or paths have other patterns");
        }
        
        return $svg_content;
    }
}
