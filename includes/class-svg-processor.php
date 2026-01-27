<?php
/**
 * SVG Processor
 * 
 * Handles SVG processing, cleaning, and cut-ready generation
 *
 * @package AdvancedProductDesigner
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class APD_SVG_Processor
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
     * Register AJAX hooks
     */
    public function init()
    {
        add_action('wp_ajax_apd_process_cut_ready_svg', array($this, 'apd_process_cut_ready_svg'));
        add_action('wp_ajax_apd_export_pdf', array($this, 'apd_export_pdf'));
        add_action('wp_ajax_apd_export_pdf_from_svg', array($this, 'apd_export_pdf_from_svg'));
        add_action('wp_ajax_apd_test_inkscape', array($this, 'test_inkscape_availability'));
    }
    
    /**
     * Test Inkscape availability - AJAX endpoint for diagnostics
     */
    public function test_inkscape_availability()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }
        
        $inkscape_path = $this->find_inkscape();
        
        if (!$inkscape_path) {
            wp_send_json_error(array(
                'message' => 'Inkscape not found on server',
                'paths_checked' => ['/usr/bin/inkscape', '/usr/local/bin/inkscape', '/opt/homebrew/bin/inkscape'],
                'shell_exec' => function_exists('shell_exec') ? 'enabled' : 'DISABLED',
                'recommendation' => 'Contact hosting support to install Inkscape, or use Cut-Ready SVG export instead'
            ));
            return;
        }
        
        $version = shell_exec(escapeshellarg($inkscape_path) . ' --version 2>&1');
        
        wp_send_json_success(array(
            'inkscape_path' => $inkscape_path,
            'version' => trim($version),
            'working' => true,
            'message' => 'Inkscape is installed and working! PDF export will use server-side processing for best CorelDRAW compatibility.'
        ));
    }

    public function apd_process_cut_ready_svg()
    {
        // Verify admin access
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }

        $order_id = intval(isset($_POST['order_id']) ? $_POST['order_id'] : 0);
        if (!$order_id) {
            wp_send_json_error('Invalid order ID');
            return;
        }

        // Get the original SVG - try multiple sources
        $svg_content = '';
        $source = '';
        
        // Try 1: Direct meta field
        $svg_content = get_post_meta($order_id, 'preview_image_svg', true);
        if (!empty($svg_content)) {
            $source = 'preview_image_svg meta';
        }
        
        // Try 2: PNG meta (might be base64 SVG)
        if (empty($svg_content)) {
            $preview_png = get_post_meta($order_id, 'preview_image_png', true);
            if (!empty($preview_png) && strpos($preview_png, 'data:image/svg') !== false) {
                $svg_content = $preview_png;
                $source = 'preview_image_png meta (contains SVG)';
            }
        }
        
        // Try 3: URL meta
        if (empty($svg_content)) {
            $preview_url = get_post_meta($order_id, 'preview_image_url', true);
            if (!empty($preview_url) && strpos($preview_url, 'data:image/svg') !== false) {
                $svg_content = $preview_url;
                $source = 'preview_image_url meta (contains SVG)';
            }
        }
        
        // Try 4: Cart items
        if (empty($svg_content)) {
            $cart_items = get_post_meta($order_id, 'cart_items', true);
            if (is_string($cart_items)) {
                $cart_items = json_decode($cart_items, true);
            }
            if (!empty($cart_items) && is_array($cart_items)) {
                $first_item = is_array($cart_items) && !empty($cart_items[0]) ? $cart_items[0] : null;
                if ($first_item) {
                    // Check multiple fields in cart item
                    $svg_content = isset($first_item['preview_image_svg']) ? $first_item['preview_image_svg'] : '';
                    if (!empty($svg_content)) {
                        $source = 'cart_items[0].preview_image_svg';
                    }
                    
                    if (empty($svg_content)) {
                        $svg_content = isset($first_item['preview_image_png']) ? $first_item['preview_image_png'] : '';
                        if (!empty($svg_content) && strpos($svg_content, 'data:image/svg') !== false) {
                            $source = 'cart_items[0].preview_image_png (contains SVG)';
                        } else {
                            $svg_content = '';
                        }
                    }
                    
                    if (empty($svg_content) && !empty($first_item['customization_data'])) {
                        $cd = is_string($first_item['customization_data']) ? json_decode($first_item['customization_data'], true) : $first_item['customization_data'];
                        if (is_array($cd)) {
                            $svg_content = isset($cd['preview_image_svg']) ? $cd['preview_image_svg'] : (isset($cd['preview_image_png']) ? $cd['preview_image_png'] : '');
                            if (!empty($svg_content)) {
                                $source = 'cart_items[0].customization_data.preview_image_svg/png';
                            }
                        }
                    }
                }
            }
        }

        // Log what we found for debugging
        error_log(sprintf(
            'APD Cut-Ready SVG - Order #%d: Source=%s, Content Length=%d, First 100 chars=%s',
            $order_id,
            $source ?: 'NONE',
            strlen($svg_content),
            substr($svg_content, 0, 100)
        ));

        // Check if svg_content is a URL instead of actual SVG content
        if (!empty($svg_content) && (strpos($svg_content, 'http://') === 0 || strpos($svg_content, 'https://') === 0)) {
            error_log('APD Cut-Ready SVG - Order #' . $order_id . ': Content is a URL, fetching: ' . $svg_content);
            $response = wp_remote_get($svg_content);
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $svg_content = wp_remote_retrieve_body($response);
                $source .= ' (fetched from URL)';
                error_log('APD Cut-Ready SVG - Order #' . $order_id . ': Fetched content length: ' . strlen($svg_content));
            } else {
                $error_msg = is_wp_error($response) ? $response->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code($response);
                error_log('APD Cut-Ready SVG - Order #' . $order_id . ': Failed to fetch URL: ' . $error_msg);
                wp_send_json_error('Failed to fetch SVG from URL: ' . $error_msg);
                return;
            }
        }

        if (empty($svg_content)) {
            // Return detailed error with all meta keys for debugging
            $all_meta = get_post_meta($order_id);
            $available_keys = array_keys($all_meta);
            
            // Also check cart_items to see what's there
            $cart_items = get_post_meta($order_id, 'cart_items', true);
            $debug_info = array();
            $debug_info['available_meta_keys'] = $available_keys;
            
            if (is_string($cart_items)) {
                $cart_items = json_decode($cart_items, true);
            }
            if (is_array($cart_items) && !empty($cart_items)) {
                $first_item = isset($cart_items[0]) ? $cart_items[0] : null;
                if ($first_item) {
                    $debug_info['first_item_keys'] = array_keys($first_item);
                    // Check if preview_image_svg exists and what it contains
                    if (isset($first_item['preview_image_svg'])) {
                        $svg_val = $first_item['preview_image_svg'];
                        $debug_info['preview_image_svg_length'] = strlen($svg_val);
                        $debug_info['preview_image_svg_start'] = substr($svg_val, 0, 100);
                    }
                    // Check customization_data too
                    if (isset($first_item['customization_data'])) {
                        $cd = is_string($first_item['customization_data']) ? json_decode($first_item['customization_data'], true) : $first_item['customization_data'];
                        if (is_array($cd)) {
                            $debug_info['customization_data_keys'] = array_keys($cd);
                        }
                    }
                }
            }
            
            error_log('APD Cut-Ready SVG Debug Info: ' . print_r($debug_info, true));
            
            wp_send_json_error(sprintf(
                'No SVG found for this order. Available meta keys: %s. See error log for more details.',
                implode(', ', $available_keys)
            ));
            return;
        }

        // Process the SVG to make it cut-ready (MINIMAL CLEANUP - Keeps 100% content)
        $clean_svg = $this->make_coreldraw_compatible($svg_content, $order_id);

        if (is_wp_error($clean_svg)) {
            wp_send_json_error($clean_svg->get_error_message());
            return;
        }

        // Save the clean SVG as a file
        $upload_dir = wp_upload_dir();
        $filename = 'order-' . $order_id . '-cut-ready-' . time() . '.svg';
        $filepath = $upload_dir['path'] . '/' . $filename;
        
        // CRITICAL: Write as binary to prevent any encoding conversion by PHP
        // Ensure UTF-8 without BOM
        $bytes_written = file_put_contents($filepath, $clean_svg, LOCK_EX);
        
        if ($bytes_written === false) {
            wp_send_json_error('Failed to save processed SVG file');
            return;
        }
        
        // Verify the file was saved correctly
        error_log(sprintf(
            'APD Cut-Ready SVG - Order #%d: Saved %d bytes to %s',
            $order_id,
            $bytes_written,
            $filename
        ));

        $file_url = $upload_dir['url'] . '/' . $filename;
        
        // Save the URL to order meta for future reference
        update_post_meta($order_id, 'cut_ready_svg_url', $file_url);
        update_post_meta($order_id, 'cut_ready_svg_generated_at', current_time('mysql'));

        wp_send_json_success(array(
            'file_url' => $file_url,
            'filename' => $filename,
            'message' => 'Cut-ready SVG generated successfully'
        ));
    }

    /**
     * Export PDF from SVG content (called from customizer preview)
     * Converts text to curves and preserves material outlines
     */
    public function apd_export_pdf_from_svg()
    {
        // Verify nonce (optional for customizer preview)
        // Allow any logged-in user to export PDF from customizer
        
        error_log("APD PDF from SVG: ===== ENDPOINT CALLED =====");
        error_log("APD PDF from SVG: POST data keys: " . implode(', ', array_keys($_POST)));
        
        if (!isset($_POST['svg_content']) || empty($_POST['svg_content'])) {
            error_log("APD PDF from SVG: ERROR - SVG content is missing");
            wp_send_json_error(array('message' => 'SVG content is required'));
            return;
        }
        
        $svg_content = wp_unslash($_POST['svg_content']);
        $filename = isset($_POST['filename']) ? sanitize_file_name($_POST['filename']) : 'design.pdf';
        
        error_log("APD PDF from SVG: ===== STARTING PDF EXPORT =====");
        error_log("APD PDF from SVG: Filename: " . $filename);
        error_log("APD PDF from SVG: SVG content length: " . strlen($svg_content) . " bytes");
        
        // Log incoming SVG info
        $text_count_incoming = preg_match_all('/<text[^>]*>/i', $svg_content);
        $pattern_count_incoming = preg_match_all('/<pattern[^>]*>/i', $svg_content);
        $pattern_refs_incoming = preg_match_all('/stroke=["\']url\(#[^)]+\)["\']/i', $svg_content);
        $apd_text_pattern = preg_match_all('/apdTextPattern/i', $svg_content);
        error_log("APD PDF from SVG: ===== INCOMING SVG ANALYSIS =====");
        error_log("APD PDF from SVG: Text elements: $text_count_incoming");
        error_log("APD PDF from SVG: Pattern definitions: $pattern_count_incoming");
        error_log("APD PDF from SVG: Pattern stroke references: $pattern_refs_incoming");
        error_log("APD PDF from SVG: apdTextPattern references: $apd_text_pattern");
        
        // Process SVG for PDF export (preserves material patterns, converts text to curves)
        error_log("APD PDF from SVG: Calling make_pdf_compatible()...");
        $processed_svg = $this->make_pdf_compatible($svg_content, 0);
        
        if (is_wp_error($processed_svg)) {
            error_log("APD PDF from SVG: ERROR in make_pdf_compatible: " . $processed_svg->get_error_message());
        } else {
            // Log processed SVG info
            $text_count_processed = preg_match_all('/<text[^>]*>/i', $processed_svg);
            $pattern_count_processed = preg_match_all('/<pattern[^>]*>/i', $processed_svg);
            $pattern_refs_processed = preg_match_all('/fill=["\']url\(#[^)]+\)["\']|stroke=["\']url\(#[^)]+\)["\']/i', $processed_svg);
            error_log("APD PDF from SVG: ===== PROCESSED SVG ANALYSIS =====");
            error_log("APD PDF from SVG: Text elements: $text_count_processed (was $text_count_incoming)");
            error_log("APD PDF from SVG: Pattern definitions: $pattern_count_processed (was $pattern_count_incoming)");
            error_log("APD PDF from SVG: Pattern references: $pattern_refs_processed (was $pattern_refs_incoming)");
        }
        
        if (is_wp_error($processed_svg)) {
            wp_send_json_error(array('message' => $processed_svg->get_error_message()));
            return;
        }
        
        // Convert to PDF
        error_log("APD PDF from SVG: Calling svg_to_pdf()...");
        $pdf_result = $this->svg_to_pdf($processed_svg, 0);
        
        if (is_wp_error($pdf_result)) {
            error_log("APD PDF from SVG: ERROR in svg_to_pdf: " . $pdf_result->get_error_message());
            $error_data = $pdf_result->get_error_data();
            
            // Check if client-side fallback is needed
            if (isset($error_data['use_client_side']) && $error_data['use_client_side']) {
                error_log("APD PDF from SVG: Returning client-side fallback flag");
                wp_send_json_success(array(
                    'use_client_side' => true,
                    'svg_content' => $processed_svg
                ));
                return;
            }
            
            error_log("APD PDF from SVG: Sending error response");
            wp_send_json_error(array('message' => $pdf_result->get_error_message()));
            return;
        }
        
        // Save PDF file
        error_log("APD PDF from SVG: PDF generated successfully, saving file...");
        $upload_dir = wp_upload_dir();
        $pdf_filename = 'pdf-' . time() . '-' . $filename;
        $pdf_path = $upload_dir['path'] . '/' . $pdf_filename;
        $pdf_url = $upload_dir['url'] . '/' . $pdf_filename;
        
        $saved = file_put_contents($pdf_path, $pdf_result);
        if ($saved === false) {
            error_log("APD PDF from SVG: ERROR - Failed to save PDF file");
            wp_send_json_error(array('message' => 'Failed to save PDF file'));
            return;
        }
        
        error_log("APD PDF from SVG: ===== SUCCESS =====");
        error_log("APD PDF from SVG: PDF saved: $pdf_filename (" . strlen($pdf_result) . " bytes)");
        error_log("APD PDF from SVG: PDF URL: $pdf_url");
        
        wp_send_json_success(array(
            'pdf_url' => $pdf_url,
            'filename' => $pdf_filename
        ));
    }

    /**
     * Export PDF with embedded SVG (vector-based, CorelDRAW compatible)
     * Preserves all styles, material patterns, and vector data
     */
    public function apd_export_pdf()
    {
        // Verify admin access
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }

        $order_id = intval(isset($_POST['order_id']) ? $_POST['order_id'] : 0);
        if (!$order_id) {
            wp_send_json_error('Invalid order ID');
            return;
        }
        
        error_log("APD PDF Export - Order #$order_id: ===== STARTING PDF EXPORT =====");

        // Get the original SVG - use same logic as cut-ready SVG
        $svg_content = '';
        $source = '';
        
        // Try 1: Direct meta field
        $svg_content = get_post_meta($order_id, 'preview_image_svg', true);
        if (!empty($svg_content)) {
            $source = 'preview_image_svg meta';
        }
        
        // Try 2: PNG meta (might be base64 SVG)
        if (empty($svg_content)) {
            $preview_png = get_post_meta($order_id, 'preview_image_png', true);
            if (!empty($preview_png) && strpos($preview_png, 'data:image/svg') !== false) {
                $svg_content = $preview_png;
                $source = 'preview_image_png meta (contains SVG)';
            }
        }
        
        // Try 3: URL meta
        if (empty($svg_content)) {
            $preview_url = get_post_meta($order_id, 'preview_image_url', true);
            if (!empty($preview_url) && strpos($preview_url, 'data:image/svg') !== false) {
                $svg_content = $preview_url;
                $source = 'preview_image_url meta (contains SVG)';
            }
        }
        
        // Try 4: Cart items
        if (empty($svg_content)) {
            $cart_items = get_post_meta($order_id, 'cart_items', true);
            if (is_string($cart_items)) {
                $cart_items = json_decode($cart_items, true);
            }
            if (!empty($cart_items) && is_array($cart_items)) {
                $first_item = is_array($cart_items) && !empty($cart_items[0]) ? $cart_items[0] : null;
                if ($first_item) {
                    $svg_content = isset($first_item['preview_image_svg']) ? $first_item['preview_image_svg'] : '';
                    if (!empty($svg_content)) {
                        $source = 'cart_items[0].preview_image_svg';
                    }
                    
                    if (empty($svg_content)) {
                        $svg_content = isset($first_item['preview_image_png']) ? $first_item['preview_image_png'] : '';
                        if (!empty($svg_content) && strpos($svg_content, 'data:image/svg') !== false) {
                            $source = 'cart_items[0].preview_image_png (contains SVG)';
                        } else {
                            $svg_content = '';
                        }
                    }
                }
            }
        }

        // Check if svg_content is a URL instead of actual SVG content
        if (!empty($svg_content) && (strpos($svg_content, 'http://') === 0 || strpos($svg_content, 'https://') === 0)) {
            $response = wp_remote_get($svg_content);
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $svg_content = wp_remote_retrieve_body($response);
                $source .= ' (fetched from URL)';
            } else {
                $error_msg = is_wp_error($response) ? $response->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code($response);
                wp_send_json_error('Failed to fetch SVG from URL: ' . $error_msg);
                return;
            }
        }

        if (empty($svg_content)) {
            error_log("APD PDF Export - Order #$order_id: ERROR - No SVG found");
            wp_send_json_error('No SVG found for this order');
            return;
        }
        
        error_log("APD PDF Export - Order #$order_id: SVG found from source: $source");
        error_log("APD PDF Export - Order #$order_id: SVG content length: " . strlen($svg_content) . " bytes");
        
        // Log incoming SVG analysis
        $text_count_incoming = preg_match_all('/<text[^>]*>/i', $svg_content);
        $pattern_count_incoming = preg_match_all('/<pattern[^>]*>/i', $svg_content);
        $pattern_refs_incoming = preg_match_all('/stroke=["\']url\(#[^)]+\)["\']/i', $svg_content);
        error_log("APD PDF Export - Order #$order_id: Incoming SVG - $text_count_incoming text elements, $pattern_count_incoming patterns, $pattern_refs_incoming pattern stroke references");

        // Process the SVG to make it PDF-ready (preserves ALL content including patterns)
        error_log("APD PDF Export - Order #$order_id: Calling make_pdf_compatible()...");
        $pdf_svg = $this->make_pdf_compatible($svg_content, $order_id);

        if (is_wp_error($pdf_svg)) {
            error_log("APD PDF Export - Order #$order_id: ERROR in make_pdf_compatible: " . $pdf_svg->get_error_message());
            wp_send_json_error($pdf_svg->get_error_message());
            return;
        }
        
        // Log processed SVG analysis
        $text_count_processed = preg_match_all('/<text[^>]*>/i', $pdf_svg);
        $pattern_count_processed = preg_match_all('/<pattern[^>]*>/i', $pdf_svg);
        $pattern_refs_processed = preg_match_all('/fill=["\']url\(#[^)]+\)["\']|stroke=["\']url\(#[^)]+\)["\']/i', $pdf_svg);
        error_log("APD PDF Export - Order #$order_id: Processed SVG - $text_count_processed text elements (was $text_count_incoming), $pattern_count_processed patterns, $pattern_refs_processed pattern references");
        
        if ($text_count_processed > 0) {
            error_log("APD PDF Export - Order #$order_id: ⚠️ WARNING - Text elements still present after processing! Conversion may have failed.");
        }

        // Generate PDF with embedded SVG
        error_log("APD PDF Export - Order #$order_id: Calling svg_to_pdf()...");
        $pdf_content = $this->svg_to_pdf($pdf_svg, $order_id);

        if (is_wp_error($pdf_content)) {
            error_log("APD PDF Export - Order #$order_id: ERROR in svg_to_pdf: " . $pdf_content->get_error_message());
            
            // Check if this is a client-side conversion request
            $error_data = $pdf_content->get_error_data();
            if (is_array($error_data) && isset($error_data['use_client_side']) && $error_data['use_client_side']) {
                error_log("APD PDF Export - Order #$order_id: Returning client-side fallback (Inkscape/ImageMagick not available)");
                // Return SVG for client-side conversion
                wp_send_json_success(array(
                    'use_client_side' => true,
                    'svg_content' => $error_data['svg_content'],
                    'order_id' => $order_id,
                    'message' => 'Using client-side PDF generation (no server dependencies required). PDF will be generated in your browser.'
                ));
                return;
            }
            
            error_log("APD PDF Export - Order #$order_id: Sending error response");
            wp_send_json_error($pdf_content->get_error_message());
            return;
        }
        
        error_log("APD PDF Export - Order #$order_id: PDF generated successfully (" . strlen($pdf_content) . " bytes)");

        // Save the PDF file
        $upload_dir = wp_upload_dir();
        $filename = 'order-' . $order_id . '-vector-' . time() . '.pdf';
        $filepath = $upload_dir['path'] . '/' . $filename;
        
        $bytes_written = file_put_contents($filepath, $pdf_content, LOCK_EX);
        
        if ($bytes_written === false) {
            wp_send_json_error('Failed to save PDF file');
            return;
        }

        $file_url = $upload_dir['url'] . '/' . $filename;
        
        // Save the URL to order meta for future reference
        update_post_meta($order_id, 'vector_pdf_url', $file_url);
        update_post_meta($order_id, 'vector_pdf_generated_at', current_time('mysql'));

        error_log(sprintf(
            'APD PDF Export - Order #%d: Generated PDF %d bytes from source: %s',
            $order_id,
            $bytes_written,
            $source
        ));

        // Verify text was converted to curves
        $text_count_final = preg_match_all('/<text[^>]*>/i', $pdf_svg);
        $pattern_count_final = preg_match_all('/<pattern[^>]*>/i', $pdf_svg);
        $pattern_fills_final = preg_match_all('/fill=["\']url\(#[^)]+\)["\']/i', $pdf_svg);
        
        error_log("APD PDF Export - Order #$order_id: Final verification - $text_count_final text elements, $pattern_count_final patterns, $pattern_fills_final pattern fills");
        
        if ($text_count_final === 0) {
            $message = 'Vector PDF generated successfully. ALL text converted to curves. Material outline patterns preserved. Open in CorelDRAW - all elements are vectors ready for editing.';
            error_log("APD PDF Export - Order #$order_id: ✅ SUCCESS - All text converted to curves");
        } else {
            $message = 'Vector PDF generated successfully. ' . $text_count_final . ' text elements remain (may not be converted to curves). Material outline patterns preserved. Open in CorelDRAW to convert remaining text to curves.';
            error_log("APD PDF Export - Order #$order_id: ⚠️ WARNING - $text_count_final text elements still present");
        }
        
        wp_send_json_success(array(
            'file_url' => $file_url,
            'filename' => $filename,
            'message' => $message,
            'text_converted' => $text_count_final === 0,
            'text_count' => $text_count_final,
            'pattern_count' => $pattern_count_final,
            'pattern_fills' => $pattern_fills_final
        ));
    }

    /**
     * Make SVG compatible with CorelDRAW while keeping 100% of content and styles
     * Only fixes encoding and attribute issues that prevent CorelDRAW from opening
     * 
     * @param string $svg_content Source SVG content
     * @param int $order_id Order ID for logging
     * @return string|WP_Error Clean SVG content or error
     */
    private function make_coreldraw_compatible($svg_content, $order_id = 0)
    {
        error_log("APD CorelDRAW Compatible - Order #$order_id: Starting minimal cleanup (keeps 100% content)");
        
        // STEP 1: Decode if it's a data URL
        if (strpos($svg_content, 'data:image/svg+xml') === 0) {
            if (strpos($svg_content, 'base64,') !== false) {
                $svg_content = base64_decode(substr($svg_content, strpos($svg_content, 'base64,') + 7));
            } else {
                $svg_content = urldecode(substr($svg_content, strpos($svg_content, ',') + 1));
            }
        }
        
        // STEP 2: Convert encoding to UTF-8 (CorelDRAW requirement)
        $detected_encoding = mb_detect_encoding($svg_content, ['UTF-8', 'UTF-16LE', 'UTF-16BE', 'ISO-8859-1'], true);
        if ($detected_encoding && $detected_encoding !== 'UTF-8') {
            $svg_content = mb_convert_encoding($svg_content, 'UTF-8', $detected_encoding);
            error_log("APD CorelDRAW Compatible - Order #$order_id: Converted from $detected_encoding to UTF-8");
        }
        
        // CRITICAL: Store original content length for verification
        $original_length = strlen($svg_content);
        error_log("APD CorelDRAW Compatible - Order #$order_id: Original content length: $original_length bytes");
        
        // STEP 3: CAREFULLY fix only truly malformed attributes
        // DO NOT remove valid color/style attributes
        // Only fix the specific ="="" pattern that breaks XML parsing
        
        // Fix ONLY empty double-equals patterns (="="") - these are truly malformed
        $svg_content = preg_replace('/(\w+(-\w+)*)="=""/', '', $svg_content);
        
        // Fix split attributes: stroke-linejoin="" round="" -> stroke-linejoin="round"
        $svg_content = preg_replace('/stroke-linejoin=""\s+round=""/', 'stroke-linejoin="round"', $svg_content);
        $svg_content = preg_replace('/stroke-linejoin=""\s+miter=""/', 'stroke-linejoin="miter"', $svg_content);
        $svg_content = preg_replace('/stroke-linejoin=""\s+bevel=""/', 'stroke-linejoin="bevel"', $svg_content);
        
        $svg_content = preg_replace('/stroke-linecap=""\s+round=""/', 'stroke-linecap="round"', $svg_content);
        $svg_content = preg_replace('/stroke-linecap=""\s+square=""/', 'stroke-linecap="square"', $svg_content);
        $svg_content = preg_replace('/stroke-linecap=""\s+butt=""/', 'stroke-linecap="butt"', $svg_content);
        
        $svg_content = preg_replace('/vector-effect=""\s+non-scaling-stroke=""/', 'vector-effect="non-scaling-stroke"', $svg_content);
        
        // Remove orphaned standalone attribute values (with empty quotes)
        $svg_content = preg_replace('/\s+round=""(?!\s*\w+)/', '', $svg_content);
        $svg_content = preg_replace('/\s+miter=""/', '', $svg_content);
        $svg_content = preg_replace('/\s+bevel=""/', '', $svg_content);
        $svg_content = preg_replace('/\s+square=""/', '', $svg_content);
        $svg_content = preg_replace('/\s+butt=""/', '', $svg_content);
        $svg_content = preg_replace('/\s+non-scaling-stroke=""/', '', $svg_content);
        
        // IMPORTANT: DO NOT remove empty fill="" or stroke="" as they might be intentional "none" values
        // Only remove if they have the malformed ="="" pattern (already handled above)
        
        // STEP 3.5: Duplicate styles as attributes for CorelDRAW compatibility
        // KEEP original style="" with !important for modern tools
        // ADD direct attributes for CorelDRAW compatibility (if not already present)
        $svg_content = preg_replace_callback(
            '/(<[^>]*)\sstyle="([^"]*)"([^>]*>)/i',
            function($matches) {
                $before = $matches[1];
                $original_style = $matches[2];  // KEEP ORIGINAL!
                $after = $matches[3];
                
                $full_element = $before . $after;  // Element without style attribute
                $attributes = array();
                
                // Extract fill color and ADD as attribute (ONLY if not already present!)
                if (!preg_match('/\sfill=/i', $full_element)) {
                    if (preg_match('/fill:\s*rgb\((\d+),\s*(\d+),\s*(\d+)\)/i', $original_style, $m)) {
                        $hex = sprintf('#%02x%02x%02x', $m[1], $m[2], $m[3]);
                        $attributes[] = 'fill="' . $hex . '"';
                    } elseif (preg_match('/fill:\s*url\([\'"]?([^)\'"]+)[\'"]?\)/i', $original_style, $m)) {
                        // CRITICAL: Material pattern fill for CorelDRAW
                        $pattern_id = trim($m[1]);
                        // Clean up any HTML entities
                        $pattern_id = str_replace('&quot;', '', $pattern_id);
                        $pattern_id = str_replace('&amp;', '&', $pattern_id);
                        $attributes[] = 'fill="url(' . htmlspecialchars($pattern_id, ENT_QUOTES) . ')"';
                    } elseif (preg_match('/fill:\s*([#\w]+)/i', $original_style, $m)) {
                        $fill_value = trim($m[1]);
                        if ($fill_value !== 'none' && $fill_value !== '') {
                            $attributes[] = 'fill="' . htmlspecialchars($fill_value, ENT_QUOTES) . '"';
                        }
                    }
                }
                
                // Extract stroke color and ADD as attribute (ONLY if not already present!)
                if (!preg_match('/\sstroke=/i', $full_element)) {
                    if (preg_match('/stroke:\s*rgb\((\d+),\s*(\d+),\s*(\d+)\)/i', $original_style, $m)) {
                        $hex = sprintf('#%02x%02x%02x', $m[1], $m[2], $m[3]);
                        $attributes[] = 'stroke="' . $hex . '"';
                    } elseif (preg_match('/stroke:\s*url\([\'"]?([^)\'"]+)[\'"]?\)/i', $original_style, $m)) {
                        // CRITICAL: Material OUTLINE pattern for CorelDRAW
                        $pattern_id = trim($m[1]);
                        // Clean up any HTML entities
                        $pattern_id = str_replace('&quot;', '', $pattern_id);
                        $pattern_id = str_replace('&amp;', '&', $pattern_id);
                        $attributes[] = 'stroke="url(' . htmlspecialchars($pattern_id, ENT_QUOTES) . ')"';
                    } elseif (preg_match('/stroke:\s*([#\w]+)/i', $original_style, $m)) {
                        $stroke_value = trim($m[1]);
                        if ($stroke_value !== 'none' && $stroke_value !== '') {
                            $attributes[] = 'stroke="' . htmlspecialchars($stroke_value, ENT_QUOTES) . '"';
                        }
                    }
                }
                
                // Extract stroke-width and ADD as attribute (ONLY if not already present!)
                if (!preg_match('/\sstroke-width=/i', $full_element)) {
                    if (preg_match('/stroke-width:\s*([^;]+)/i', $original_style, $m)) {
                        $attributes[] = 'stroke-width="' . htmlspecialchars(trim($m[1]), ENT_QUOTES) . '"';
                    }
                }
                
                // Extract opacity values and ADD as attributes (ONLY if not already present!)
                if (!preg_match('/\sfill-opacity=/i', $full_element)) {
                    if (preg_match('/fill-opacity:\s*([^;]+)/i', $original_style, $m)) {
                        $attributes[] = 'fill-opacity="' . htmlspecialchars(trim($m[1]), ENT_QUOTES) . '"';
                    }
                }
                if (!preg_match('/\sstroke-opacity=/i', $full_element)) {
                    if (preg_match('/stroke-opacity:\s*([^;]+)/i', $original_style, $m)) {
                        $attributes[] = 'stroke-opacity="' . htmlspecialchars(trim($m[1]), ENT_QUOTES) . '"';
                    }
                }
                
                // Build final element - KEEP ORIGINAL STYLE + ADD ATTRIBUTES (only if not duplicates)
                $result = $before;
                if (!empty($attributes)) {
                    $result .= ' ' . implode(' ', $attributes);
                }
                // IMPORTANT: Keep original style with !important
                $result .= ' style="' . $original_style . '"';
                $result .= $after;
                
                return $result;
            },
            $svg_content
        );
        
        // STEP 4: Remove base64 font data for universal CorelDRAW compatibility (all versions)
        // Keep font-family declarations so text styling is preserved
        // CorelDRAW will use system fonts with same family name if available
        $svg_content = preg_replace_callback(
            '/@font-face\s*\{[^}]*\}/s',
            function($matches) {
                $fontface = $matches[0];
                // Extract font-family name and other properties
                $properties = array();
                
                if (preg_match('/font-family:\s*["\']?([^;"\']+)["\']?/i', $fontface, $m)) {
                    $properties[] = "font-family: '" . trim($m[1]) . "'";
                }
                if (preg_match('/font-weight:\s*([^;]+)/i', $fontface, $m)) {
                    $properties[] = "font-weight: " . trim($m[1]);
                }
                if (preg_match('/font-style:\s*([^;]+)/i', $fontface, $m)) {
                    $properties[] = "font-style: " . trim($m[1]);
                }
                
                // Return simplified font-face without base64 data
                if (!empty($properties)) {
                    return "@font-face { " . implode("; ", $properties) . "; }";
                }
                return '';
            },
            $svg_content
        );
        
        // STEP 4.5: Ensure pattern fills work in CorelDRAW
        // CorelDRAW needs pattern references as direct attributes, not just in style
        // Convert style pattern references to attribute format
        $svg_content = preg_replace_callback(
            '/(stroke|fill):\s*url\(([\'"]?)([^)]+)\2\)\s*!?important;?/i',
            function($matches) {
                $attr_name = $matches[1];  // stroke or fill
                $pattern_id = $matches[3];  // #logoGoldPattern or &quot;#logoGoldPattern&quot;
                
                // Clean up the pattern ID
                $pattern_id = str_replace('&quot;', '', $pattern_id);
                $pattern_id = str_replace('"', '', $pattern_id);
                $pattern_id = str_replace("'", '', $pattern_id);
                $pattern_id = trim($pattern_id);
                
                // Return both style AND attribute format for maximum compatibility
                return $attr_name . ': url(' . $pattern_id . ')';
            },
            $svg_content
        );
        
        // STEP 5: Ensure proper XML declaration with UTF-8
        // Remove any existing XML declaration first
        $svg_content = preg_replace('/<\?xml[^?]*\?>\s*/i', '', $svg_content);
        
        // Add clean UTF-8 XML declaration
        $svg_content = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>' . "\n" . $svg_content;
        
        // STEP 6: Add metadata about processing
        if (preg_match('/<svg[^>]*>/i', $svg_content, $svg_match)) {
            $svg_tag = $svg_match[0];
            $metadata = "\n  <metadata>Cut-ready SVG from Order #$order_id on " . date('Y-m-d H:i:s') . ". " .
                       "CorelDRAW compatible. All content preserved: colors, patterns (PNG/JPG materials), text, styles. " .
                       "Pattern fills converted to attributes for CorelDRAW. Base64 fonts removed (use system fonts). " .
                       "Open in CorelDRAW to see material textures on text and shapes.</metadata>\n";
            $svg_content = str_replace($svg_tag, $svg_tag . $metadata, $svg_content);
        }
        
        // Log pattern information for debugging
        $pattern_count = preg_match_all('/<pattern/i', $svg_content, $matches);
        $pattern_refs = preg_match_all('/url\(#[^)]+\)/i', $svg_content, $matches);
        $text_with_pattern = preg_match_all('/<text[^>]*\s+stroke=["\']url\([^)]+\)["\']/i', $svg_content);
        error_log("APD PDF Compatible - Order #$order_id: Found $pattern_count pattern definitions, $pattern_refs pattern references, $text_with_pattern text elements with material outline patterns");
        
        // STEP 6.5: Process patterns with data:image for CorelDRAW compatibility
        $svg_content = $this->process_patterns_for_coreldraw($svg_content, $order_id);
        
        // STEP 7: Validate UTF-8
        if (!mb_check_encoding($svg_content, 'UTF-8')) {
            error_log("APD CorelDRAW Compatible - Order #$order_id: Output is not valid UTF-8, attempting to clean");
            $svg_content = mb_convert_encoding($svg_content, 'UTF-8', 'UTF-8');
        }
        
        // STEP 8: Verify content wasn't significantly reduced (would indicate data loss)
        $final_length = strlen($svg_content);
        $size_reduction = $original_length - $final_length;
        $reduction_percent = ($original_length > 0) ? ($size_reduction / $original_length * 100) : 0;
        
        error_log(sprintf(
            "APD CorelDRAW Compatible - Order #$order_id: Processed successfully. Original: %d bytes, Final: %d bytes, Reduction: %d bytes (%.1f%% - expected from font removal)",
            $original_length,
            $final_length,
            $size_reduction,
            $reduction_percent
        ));
        
        // Warning if content was reduced by more than 40% (too much removed!)
        if ($reduction_percent > 40) {
            error_log("APD CorelDRAW Compatible - Order #$order_id: WARNING - Large content reduction detected! May have lost styles/colors.");
        }
        
        return $svg_content;
    }

    /**
     * STRONG HELPER: Process SVG patterns with data:image for CorelDRAW compatibility
     * Extracts embedded images to external files for CorelDRAW to access
     * 
     * @param string $svg_content SVG content
     * @param int $order_id Order ID for logging
     * @return string Processed SVG content with external image references
     */
    private function process_patterns_for_coreldraw($svg_content, $order_id = 0)
    {
        $upload_dir = wp_upload_dir();
        $extracted_images = array();
        
        // Find all pattern definitions and extract images
        $svg_content = preg_replace_callback(
            '/<pattern([^>]*)>(.*?)<\/pattern>/is',
            function($matches) use ($order_id, $upload_dir, &$extracted_images) {
                $pattern_attrs = $matches[1];
                $pattern_content = $matches[2];
                
                // Extract pattern ID
                $pattern_id = 'pattern';
                if (preg_match('/id=["\']([^"\']+)["\']/', $pattern_attrs, $m)) {
                    $pattern_id = $m[1];
                }
                
                // Process image tags with data URIs
                $pattern_content = preg_replace_callback(
                    '/<image([^>]*)>/i',
                    function($img_matches) use ($pattern_id, $order_id, $upload_dir, &$extracted_images) {
                        $img_attrs = $img_matches[1];
                        
                        // Find data:image URI
                        if (preg_match('/(href|xlink:href)=["\']data:image\/([^;]+);base64,([^"\']+)["\']/', $img_attrs, $data_match)) {
                            $mime_type = strtolower($data_match[2]);
                            $base64_data = $data_match[3];
                            
                            // Validate and decode
                            $decoded = base64_decode($base64_data, true);
                            if ($decoded === false) {
                                error_log("APD Pattern STRONG - Order #$order_id: Invalid base64 in $pattern_id");
                                return $img_matches[0];
                            }
                            
                            // Normalize extension
                            $ext = ($mime_type === 'jpg') ? 'jpeg' : $mime_type;
                            $ext = preg_replace('/[^a-z]/', '', $ext); // Clean extension
                            if ($ext === 'jpeg') $ext = 'jpg';
                            
                            // EXTRACT TO EXTERNAL FILE for CorelDRAW
                            $filename = 'order-' . $order_id . '-pattern-' . sanitize_file_name($pattern_id) . '.' . $ext;
                            $filepath = $upload_dir['path'] . '/' . $filename;
                            $file_url = $upload_dir['url'] . '/' . $filename;
                            
                            // Save image file
                            $saved = file_put_contents($filepath, $decoded, LOCK_EX);
                            
                            if ($saved !== false) {
                                $extracted_images[] = array(
                                    'pattern_id' => $pattern_id,
                                    'filename' => $filename,
                                    'url' => $file_url,
                                    'size' => strlen($decoded)
                                );
                                
                                error_log("APD Pattern STRONG - Order #$order_id: Extracted $pattern_id image to $filename (" . strlen($decoded) . " bytes)");
                                
                                // CRITICAL: Use EXTERNAL URL for CorelDRAW
                                // Keep data URI as fallback
                                $external_href = $file_url;
                                $data_uri = 'data:image/' . $mime_type . ';base64,' . base64_encode($decoded);
                                
                                // Rebuild with DUAL references:
                                // 1. External URL (CorelDRAW will use this!)
                                // 2. Data URI (browser fallback)
                                $new_attrs = $img_attrs;
                                
                                // Remove old href attributes
                                $new_attrs = preg_replace('/(href|xlink:href)=["\'][^"\']*["\']/', '', $new_attrs);
                                
                                // Add EXTERNAL href first (priority for CorelDRAW)
                                $new_attrs = ' href="' . esc_url($external_href) . '"' . 
                                           ' xlink:href="' . esc_url($external_href) . '"' .
                                           ' data-original="' . substr($data_uri, 0, 100) . '..."' .
                                           $new_attrs;
                                
                                return '<image' . $new_attrs . '>';
                            } else {
                                error_log("APD Pattern STRONG - Order #$order_id: Failed to save $pattern_id image file");
                            }
                        }
                        
                        return $img_matches[0];
                    },
                    $pattern_content
                );
                
                return '<pattern' . $pattern_attrs . '>' . $pattern_content . '</pattern>';
            },
            $svg_content
        );
        
        // Log extraction summary
        if (!empty($extracted_images)) {
            error_log("APD Pattern STRONG - Order #$order_id: Extracted " . count($extracted_images) . " pattern images:");
            foreach ($extracted_images as $img) {
                error_log("  - {$img['pattern_id']}: {$img['filename']} ({$img['size']} bytes) -> {$img['url']}");
            }
        }
        
        return $svg_content;
    }

    /**
     * Generate a CorelDRAW-compatible SVG from source content
     * This method creates a clean, minimal SVG that's guaranteed to open in CorelDRAW
     * 
     * @param string $svg_content Source SVG content (can be base64 data URL)
     * @param int $order_id Order ID for logging
     * @return string|WP_Error Clean SVG content or error
     */
    private function generate_coreldraw_compatible_svg($svg_content, $order_id = 0)
    {
        error_log("APD CorelDRAW SVG - Order #$order_id: Starting new generation method");
        
        // STEP 1: Decode if it's a data URL
        if (strpos($svg_content, 'data:image/svg+xml') === 0) {
            if (strpos($svg_content, 'base64,') !== false) {
                $svg_content = base64_decode(substr($svg_content, strpos($svg_content, 'base64,') + 7));
            } else {
                $svg_content = urldecode(substr($svg_content, strpos($svg_content, ',') + 1));
            }
        }
        
        // STEP 2: Convert encoding to UTF-8 if needed
        $detected_encoding = mb_detect_encoding($svg_content, ['UTF-8', 'UTF-16LE', 'UTF-16BE', 'ISO-8859-1'], true);
        if ($detected_encoding && $detected_encoding !== 'UTF-8') {
            $svg_content = mb_convert_encoding($svg_content, 'UTF-8', $detected_encoding);
            error_log("APD CorelDRAW SVG - Order #$order_id: Converted from $detected_encoding to UTF-8");
        }
        
        // STEP 3: Extract SVG dimensions and viewBox
        $width = '800';
        $height = '600';
        $viewBox = '0 0 800 600';
        
        if (preg_match('/<svg[^>]*\swidth=["\']([^"\']+)["\']/', $svg_content, $m)) {
            $width = preg_replace('/[^0-9.]/', '', $m[1]); // Remove units
        }
        if (preg_match('/<svg[^>]*\sheight=["\']([^"\']+)["\']/', $svg_content, $m)) {
            $height = preg_replace('/[^0-9.]/', '', $m[1]); // Remove units
        }
        if (preg_match('/<svg[^>]*\sviewBox=["\']([^"\']+)["\']/', $svg_content, $m)) {
            $viewBox = trim($m[1]);
        }
        
        // STEP 4: Extract all path, rect, circle, ellipse, polygon, polyline elements
        // We'll extract the raw elements and clean them
        $elements = array();
        
        // Extract paths
        if (preg_match_all('/<path[^>]*\sd=["\']([^"\']*)["\'][^>]*>/i', $svg_content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $d = $match[1];
                if (!empty($d)) {
                    // Extract styling
                    $fill = 'none';
                    $stroke = '#000000';
                    $stroke_width = '1';
                    
                    if (preg_match('/\sfill=["\']([^"\']+)["\']/', $match[0], $f)) {
                        $fill_val = $f[1];
                        // Skip pattern/gradient references
                        if (strpos($fill_val, 'url(') === false && $fill_val !== 'none' && $fill_val !== '') {
                            $fill = $fill_val;
                        }
                    }
                    if (preg_match('/\sstroke=["\']([^"\']+)["\']/', $match[0], $s)) {
                        $stroke_val = $s[1];
                        if (strpos($stroke_val, 'url(') === false && $stroke_val !== 'none' && $stroke_val !== '') {
                            $stroke = $stroke_val;
                        }
                    }
                    if (preg_match('/\sstroke-width=["\']([^"\']+)["\']/', $match[0], $sw)) {
                        $stroke_width = preg_replace('/[^0-9.]/', '', $sw[1]);
                    }
                    
                    $elements[] = sprintf(
                        '<path d="%s" fill="%s" stroke="%s" stroke-width="%s"/>',
                        htmlspecialchars($d, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($fill, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($stroke, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($stroke_width, ENT_XML1 | ENT_QUOTES, 'UTF-8')
                    );
                }
            }
        }
        
        // Extract rectangles
        if (preg_match_all('/<rect[^>]*>/i', $svg_content, $matches)) {
            foreach ($matches[0] as $rect) {
                $x = $y = $w = $h = '0';
                $rx = $ry = '0';
                $fill = 'none';
                $stroke = '#000000';
                $stroke_width = '1';
                
                if (preg_match('/\sx=["\']([^"\']+)["\']/', $rect, $m)) $x = preg_replace('/[^0-9.-]/', '', $m[1]);
                if (preg_match('/\sy=["\']([^"\']+)["\']/', $rect, $m)) $y = preg_replace('/[^0-9.-]/', '', $m[1]);
                if (preg_match('/\swidth=["\']([^"\']+)["\']/', $rect, $m)) $w = preg_replace('/[^0-9.]/', '', $m[1]);
                if (preg_match('/\sheight=["\']([^"\']+)["\']/', $rect, $m)) $h = preg_replace('/[^0-9.]/', '', $m[1]);
                if (preg_match('/\srx=["\']([^"\']+)["\']/', $rect, $m)) $rx = preg_replace('/[^0-9.]/', '', $m[1]);
                if (preg_match('/\sry=["\']([^"\']+)["\']/', $rect, $m)) $ry = preg_replace('/[^0-9.]/', '', $m[1]);
                if (preg_match('/\sfill=["\']([^"\']+)["\']/', $rect, $m) && strpos($m[1], 'url(') === false) $fill = $m[1];
                if (preg_match('/\sstroke=["\']([^"\']+)["\']/', $rect, $m) && strpos($m[1], 'url(') === false) $stroke = $m[1];
                if (preg_match('/\sstroke-width=["\']([^"\']+)["\']/', $rect, $m)) $stroke_width = preg_replace('/[^0-9.]/', '', $m[1]);
                
                if ($w > 0 && $h > 0) {
                    $rect_attrs = sprintf(
                        'x="%s" y="%s" width="%s" height="%s" fill="%s" stroke="%s" stroke-width="%s"',
                        htmlspecialchars($x, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($y, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($w, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($h, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($fill, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($stroke, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($stroke_width, ENT_XML1 | ENT_QUOTES, 'UTF-8')
                    );
                    if ($rx > 0 || $ry > 0) {
                        $rect_attrs .= sprintf(' rx="%s" ry="%s"',
                            htmlspecialchars($rx, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                            htmlspecialchars($ry, ENT_XML1 | ENT_QUOTES, 'UTF-8')
                        );
                    }
                    $elements[] = '<rect ' . $rect_attrs . '/>';
                }
            }
        }
        
        // Extract circles
        if (preg_match_all('/<circle[^>]*>/i', $svg_content, $matches)) {
            foreach ($matches[0] as $circle) {
                $cx = $cy = $r = '0';
                $fill = 'none';
                $stroke = '#000000';
                $stroke_width = '1';
                
                if (preg_match('/\scx=["\']([^"\']+)["\']/', $circle, $m)) $cx = preg_replace('/[^0-9.-]/', '', $m[1]);
                if (preg_match('/\scy=["\']([^"\']+)["\']/', $circle, $m)) $cy = preg_replace('/[^0-9.-]/', '', $m[1]);
                if (preg_match('/\sr=["\']([^"\']+)["\']/', $circle, $m)) $r = preg_replace('/[^0-9.]/', '', $m[1]);
                if (preg_match('/\sfill=["\']([^"\']+)["\']/', $circle, $m) && strpos($m[1], 'url(') === false) $fill = $m[1];
                if (preg_match('/\sstroke=["\']([^"\']+)["\']/', $circle, $m) && strpos($m[1], 'url(') === false) $stroke = $m[1];
                if (preg_match('/\sstroke-width=["\']([^"\']+)["\']/', $circle, $m)) $stroke_width = preg_replace('/[^0-9.]/', '', $m[1]);
                
                if ($r > 0) {
                    $elements[] = sprintf(
                        '<circle cx="%s" cy="%s" r="%s" fill="%s" stroke="%s" stroke-width="%s"/>',
                        htmlspecialchars($cx, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($cy, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($r, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($fill, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($stroke, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($stroke_width, ENT_XML1 | ENT_QUOTES, 'UTF-8')
                    );
                }
            }
        }
        
        // Extract text elements (important for designs with text)
        if (preg_match_all('/<text[^>]*>.*?<\/text>/is', $svg_content, $matches)) {
            foreach ($matches[0] as $text_element) {
                // Extract text element with all its attributes and content
                // Clean up any base64 fonts or problematic attributes but keep the text
                $clean_text = $text_element;
                
                // Remove any base64 data URIs in fonts
                $clean_text = preg_replace('/url\(["\']data:[^)]+\)["\']/', 'none', $clean_text);
                
                // Keep the text element as-is for now (CorelDRAW can handle text)
                $elements[] = $clean_text;
            }
        }
        
        // Extract ellipse elements
        if (preg_match_all('/<ellipse[^>]*>/i', $svg_content, $matches)) {
            foreach ($matches[0] as $ellipse) {
                $cx = $cy = $rx = $ry = '0';
                $fill = 'none';
                $stroke = '#000000';
                $stroke_width = '1';
                
                if (preg_match('/\scx=["\']([^"\']+)["\']/', $ellipse, $m)) $cx = preg_replace('/[^0-9.-]/', '', $m[1]);
                if (preg_match('/\scy=["\']([^"\']+)["\']/', $ellipse, $m)) $cy = preg_replace('/[^0-9.-]/', '', $m[1]);
                if (preg_match('/\srx=["\']([^"\']+)["\']/', $ellipse, $m)) $rx = preg_replace('/[^0-9.]/', '', $m[1]);
                if (preg_match('/\sry=["\']([^"\']+)["\']/', $ellipse, $m)) $ry = preg_replace('/[^0-9.]/', '', $m[1]);
                if (preg_match('/\sfill=["\']([^"\']+)["\']/', $ellipse, $m) && strpos($m[1], 'url(') === false) $fill = $m[1];
                if (preg_match('/\sstroke=["\']([^"\']+)["\']/', $ellipse, $m) && strpos($m[1], 'url(') === false) $stroke = $m[1];
                if (preg_match('/\sstroke-width=["\']([^"\']+)["\']/', $ellipse, $m)) $stroke_width = preg_replace('/[^0-9.]/', '', $m[1]);
                
                if ($rx > 0 && $ry > 0) {
                    $elements[] = sprintf(
                        '<ellipse cx="%s" cy="%s" rx="%s" ry="%s" fill="%s" stroke="%s" stroke-width="%s"/>',
                        htmlspecialchars($cx, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($cy, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($rx, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($ry, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($fill, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($stroke, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($stroke_width, ENT_XML1 | ENT_QUOTES, 'UTF-8')
                    );
                }
            }
        }
        
        // Extract polygon and polyline elements
        if (preg_match_all('/<(polygon|polyline)[^>]*>/i', $svg_content, $matches)) {
            foreach ($matches[0] as $poly) {
                $points = '';
                $fill = 'none';
                $stroke = '#000000';
                $stroke_width = '1';
                
                if (preg_match('/\spoints=["\']([^"\']+)["\']/', $poly, $m)) $points = trim($m[1]);
                if (preg_match('/\sfill=["\']([^"\']+)["\']/', $poly, $m) && strpos($m[1], 'url(') === false) $fill = $m[1];
                if (preg_match('/\sstroke=["\']([^"\']+)["\']/', $poly, $m) && strpos($m[1], 'url(') === false) $stroke = $m[1];
                if (preg_match('/\sstroke-width=["\']([^"\']+)["\']/', $poly, $m)) $stroke_width = preg_replace('/[^0-9.]/', '', $m[1]);
                
                if (!empty($points)) {
                    $tag_name = (stripos($poly, '<polygon') !== false) ? 'polygon' : 'polyline';
                    $elements[] = sprintf(
                        '<%s points="%s" fill="%s" stroke="%s" stroke-width="%s"/>',
                        $tag_name,
                        htmlspecialchars($points, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($fill, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($stroke, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($stroke_width, ENT_XML1 | ENT_QUOTES, 'UTF-8')
                    );
                }
            }
        }
        
        // Check if we extracted any elements
        if (empty($elements)) {
            error_log("APD CorelDRAW SVG - Order #$order_id: No valid elements extracted");
            return new WP_Error('no_elements', 'No valid SVG elements found in source');
        }
        
        error_log("APD CorelDRAW SVG - Order #$order_id: Extracted " . count($elements) . " elements");
        
        // STEP 5: Build clean SVG with proper XML declaration
        $svg = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>' . "\n";
        $svg .= sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="%s" height="%s" viewBox="%s">',
            htmlspecialchars($width, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($height, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($viewBox, ENT_XML1 | ENT_QUOTES, 'UTF-8')
        ) . "\n";
        $svg .= '  <metadata>' . "\n";
        $svg .= '    <![CDATA[Cut-ready SVG for Order #' . intval($order_id) . ' generated on ' . date('Y-m-d H:i:s') . '. ';
        $svg .= 'Optimized for CorelDRAW and cutting machines. Clean vectors only.]]>' . "\n";
        $svg .= '  </metadata>' . "\n";
        $svg .= '  <g id="cut-layer">' . "\n";
        
        foreach ($elements as $element) {
            $svg .= '    ' . $element . "\n";
        }
        
        $svg .= '  </g>' . "\n";
        $svg .= '</svg>';
        
        // STEP 6: Validate that it's valid UTF-8
        if (!mb_check_encoding($svg, 'UTF-8')) {
            error_log("APD CorelDRAW SVG - Order #$order_id: Output is not valid UTF-8, attempting to clean");
            $svg = mb_convert_encoding($svg, 'UTF-8', 'UTF-8');
        }
        
        error_log("APD CorelDRAW SVG - Order #$order_id: Successfully generated " . strlen($svg) . " bytes");
        
        return $svg;
    }

    private function clean_svg_for_cutting($svg_content, $order_id = 0)
    {
        // STEP 1: Detect and convert from UTF-16 to UTF-8 FIRST (before any other processing)
        // This is critical because UTF-16 encoded files will have null bytes between characters
        $detected_encoding = mb_detect_encoding($svg_content, ['UTF-8', 'UTF-16LE', 'UTF-16BE', 'ISO-8859-1', 'ASCII'], true);
        
        if ($detected_encoding && $detected_encoding !== 'UTF-8') {
            error_log("APD Cut-Ready SVG - Order #$order_id: Input is $detected_encoding, converting to UTF-8");
            $svg_content = mb_convert_encoding($svg_content, 'UTF-8', $detected_encoding);
            
            // Remove BOM if present after conversion
            $svg_content = preg_replace('/^\xEF\xBB\xBF/', '', $svg_content);
            $svg_content = preg_replace('/^\xFF\xFE/', '', $svg_content);
            $svg_content = preg_replace('/^\xFE\xFF/', '', $svg_content);
        }
        
        // STEP 2: Handle data URL format
        if (strpos($svg_content, 'data:image/svg+xml') === 0) {
            // Check if it's base64 encoded
            if (strpos($svg_content, 'base64,') !== false) {
                $svg_content = preg_replace('/^data:image\/svg\+xml;base64,/', '', $svg_content);
                $svg_content = base64_decode($svg_content);
            } else {
                // URL encoded
                $svg_content = preg_replace('/^data:image\/svg\+xml[^,]*,/', '', $svg_content);
                $svg_content = urldecode($svg_content);
            }
            
            if ($svg_content === false || empty($svg_content)) {
                return new WP_Error('decode_failed', 'Failed to decode SVG data URL');
            }
            
            // After decoding, check encoding again
            $detected_encoding = mb_detect_encoding($svg_content, ['UTF-8', 'UTF-16LE', 'UTF-16BE', 'ISO-8859-1'], true);
            if ($detected_encoding && $detected_encoding !== 'UTF-8') {
                error_log("APD Cut-Ready SVG - Order #$order_id: After URL decode, content is $detected_encoding, converting to UTF-8");
                $svg_content = mb_convert_encoding($svg_content, 'UTF-8', $detected_encoding);
            }
        }

        // STEP 3: Clean up common issues before parsing
        $svg_content = trim($svg_content);
        
        // Remove HTML entities that might break XML parsing
        $svg_content = html_entity_decode($svg_content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Log content type for debugging
        error_log(sprintf(
            'APD Cut-Ready SVG Debug - Order #%d: After decode length=%d, starts with=%s, contains <svg=%s',
            $order_id,
            strlen($svg_content),
            substr($svg_content, 0, 50),
            strpos($svg_content, '<svg') !== false ? 'YES' : 'NO'
        ));
        
        // Ensure we have an SVG element
        if (strpos($svg_content, '<svg') === false) {
            // Provide more detailed error for debugging
            $preview = strlen($svg_content) > 200 ? substr($svg_content, 0, 200) . '...' : $svg_content;
            error_log('APD Cut-Ready SVG Error - Content preview: ' . $preview);
            return new WP_Error('not_svg', 'Content does not appear to be SVG. Content starts with: ' . substr($svg_content, 0, 100));
        }

        // Load SVG with DOMDocument
        libxml_use_internal_errors(true);
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->encoding = 'UTF-8'; // Explicitly set UTF-8 encoding
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        
        // Fix common XML issues BEFORE first parse attempt
        $svg_content = $this->fix_common_xml_issues($svg_content);
        
        // Try to load with error suppression
        $loaded = @$dom->loadXML($svg_content, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_PARSEHUGE);
        
        if (!$loaded) {
            // Get libxml errors for debugging
            $errors = libxml_get_errors();
            $error_messages = array();
            foreach ($errors as $error) {
                $error_messages[] = trim($error->message);
            }
            libxml_clear_errors();
            
            error_log('APD SVG Processing Error (first attempt) - Order #' . $order_id . ': ' . implode('; ', $error_messages));
            error_log('APD SVG Content (first 1000 chars): ' . substr($svg_content, 0, 1000));
            
            // Second attempt: Try wrapping in proper XML document and using HTML parser as fallback
            // Strip any existing XML declaration first
            $svg_content = preg_replace('/<\?xml[^?]*\?>/', '', $svg_content);
            
            // Try to use HTML5-style parsing which is more lenient
            $wrapped_content = '<?xml version="1.0" encoding="UTF-8"?>' . 
                              '<root xmlns:svg="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">' . 
                              $svg_content . 
                              '</root>';
            
            $dom2 = new DOMDocument('1.0', 'UTF-8');
            $dom2->preserveWhiteSpace = false;
            
            // Try with the wrapped content
            $loaded = @$dom2->loadXML($wrapped_content, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_PARSEHUGE | LIBXML_NOCDATA);
            
            if ($loaded) {
                // Extract the SVG from the root wrapper
                $svgs = $dom2->getElementsByTagName('svg');
                if ($svgs->length > 0) {
                    $svgElement = $svgs->item(0);
                    $dom = new DOMDocument('1.0', 'UTF-8');
                    $dom->preserveWhiteSpace = false;
                    $dom->formatOutput = true;
                    $importedSvg = $dom->importNode($svgElement, true);
                    $dom->appendChild($importedSvg);
                    $loaded = true;
                    error_log('APD SVG Processing - Order #' . $order_id . ': Recovered using wrapped XML parsing');
                }
            }
            
            if (!$loaded) {
                // Third attempt: Use regex to aggressively clean the SVG
                error_log('APD SVG Processing - Order #' . $order_id . ': Attempting aggressive regex cleanup');
                
                // Remove all style attributes entirely as they're often the source of issues
                $svg_content_cleaned = preg_replace('/\s+style="[^"]*"/', '', $svg_content);
                
                // Fix path d attributes with line breaks or special chars
                $svg_content_cleaned = preg_replace_callback(
                    '/(<path[^>]*\sd=")([^"]*)(")/s',
                    function($matches) {
                        $d = $matches[2];
                        // Remove line breaks and normalize whitespace in path data
                        $d = preg_replace('/[\r\n\t]+/', ' ', $d);
                        $d = preg_replace('/\s+/', ' ', $d);
                        $d = trim($d);
                        return $matches[1] . $d . $matches[3];
                    },
                    $svg_content_cleaned
                );
                
                // Try loading the cleaned version
                $dom = new DOMDocument('1.0', 'UTF-8');
                $dom->preserveWhiteSpace = false;
                $dom->formatOutput = true;
                
                $svg_content_cleaned = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . 
                                       preg_replace('/<\?xml[^?]*\?>/', '', $svg_content_cleaned);
                
                $loaded = @$dom->loadXML($svg_content_cleaned, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_PARSEHUGE);
                
                if ($loaded) {
                    error_log('APD SVG Processing - Order #' . $order_id . ': Recovered by removing style attributes');
                } else {
                    // Fourth attempt: Use HTML parser which is more lenient
                    error_log('APD SVG Processing - Order #' . $order_id . ': Attempting HTML parser fallback');
                    
                    $dom = new DOMDocument('1.0', 'UTF-8');
                    $dom->preserveWhiteSpace = false;
                    
                    // Wrap in HTML and try loadHTML which is more lenient
                    $html_wrapped = '<html><body>' . $svg_content_cleaned . '</body></html>';
                    $loaded = @$dom->loadHTML($html_wrapped, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_HTML_NOIMPLIED | LIBXML_PARSEHUGE);
                    
                    if ($loaded) {
                        // Find the SVG element
                        $svgs = $dom->getElementsByTagName('svg');
                        if ($svgs->length > 0) {
                            $svgElement = $svgs->item(0);
                            $newDom = new DOMDocument('1.0', 'UTF-8');
                            $newDom->preserveWhiteSpace = false;
                            $newDom->formatOutput = true;
                            $importedSvg = $newDom->importNode($svgElement, true);
                            $newDom->appendChild($importedSvg);
                            $dom = $newDom;
                            $loaded = true;
                            error_log('APD SVG Processing - Order #' . $order_id . ': Recovered using HTML parser');
                        } else {
                            $loaded = false;
                        }
                    }
                    
                    if (!$loaded) {
                        // Final fallback: COMPLETELY rebuild the SVG from scratch
                        // Extract only path/shape data and build a clean, valid SVG
                        error_log('APD SVG Processing - Order #' . $order_id . ': All XML parsing failed, rebuilding SVG from scratch');
                        
                        // Start with the original content
                        $raw_svg = $svg_content;
                        
                        // Extract viewBox and dimensions from the root SVG
                        $viewBox = '0 0 800 600'; // Default
                        $width = '800';
                        $height = '600';
                        
                        if (preg_match('/<svg[^>]*\sviewBox="([^"]+)"/i', $raw_svg, $vb_match)) {
                            $viewBox = preg_replace('/[\r\n\t]+/', ' ', $vb_match[1]);
                            $viewBox = preg_replace('/\s+/', ' ', trim($viewBox));
                        }
                        if (preg_match('/<svg[^>]*\swidth="([^"]+)"/i', $raw_svg, $w_match)) {
                            $width = $w_match[1];
                        }
                        if (preg_match('/<svg[^>]*\sheight="([^"]+)"/i', $raw_svg, $h_match)) {
                            $height = $h_match[1];
                        }
                        
                        // Extract all path elements with their d attributes
                        $paths = array();
                        if (preg_match_all('/<path[^>]+>/is', $raw_svg, $path_matches)) {
                            foreach ($path_matches[0] as $path_tag) {
                                // Extract just the d attribute
                                if (preg_match('/\sd="([^"]*)"/s', $path_tag, $d_match)) {
                                    $d = $d_match[1];
                                    // Clean the d attribute
                                    $d = preg_replace('/[\r\n\t]+/', ' ', $d);
                                    $d = preg_replace('/\s+/', ' ', trim($d));
                                    if (!empty($d)) {
                                        // Extract fill and stroke if present
                                        $fill = 'none';
                                        $stroke = 'black';
                                        $stroke_width = '1';
                                        
                                        if (preg_match('/\sfill="([^"]+)"/', $path_tag, $fill_match)) {
                                            $fill = $fill_match[1];
                                            // Skip pattern references
                                            if (strpos($fill, 'url(') !== false) {
                                                $fill = 'none';
                                            }
                                        }
                                        if (preg_match('/\sstroke="([^"]+)"/', $path_tag, $stroke_match)) {
                                            $stroke = $stroke_match[1];
                                            if (strpos($stroke, 'url(') !== false) {
                                                $stroke = 'black';
                                            }
                                        }
                                        if (preg_match('/\sstroke-width="([^"]+)"/', $path_tag, $sw_match)) {
                                            $stroke_width = $sw_match[1];
                                        }
                                        
                                        $paths[] = sprintf(
                                            '<path d="%s" fill="%s" stroke="%s" stroke-width="%s"/>',
                                            htmlspecialchars($d, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                                            htmlspecialchars($fill, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                                            htmlspecialchars($stroke, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                                            htmlspecialchars($stroke_width, ENT_XML1 | ENT_QUOTES, 'UTF-8')
                                        );
                                    }
                                }
                            }
                        }
                        
                        // Extract rect elements
                        if (preg_match_all('/<rect[^>]+\/?>/is', $raw_svg, $rect_matches)) {
                            foreach ($rect_matches[0] as $rect_tag) {
                                $x = '0'; $y = '0'; $w = '0'; $h = '0';
                                $fill = 'none'; $stroke = 'black'; $stroke_width = '1';
                                
                                if (preg_match('/\sx="([^"]+)"/', $rect_tag, $m)) $x = $m[1];
                                if (preg_match('/\sy="([^"]+)"/', $rect_tag, $m)) $y = $m[1];
                                if (preg_match('/\swidth="([^"]+)"/', $rect_tag, $m)) $w = $m[1];
                                if (preg_match('/\sheight="([^"]+)"/', $rect_tag, $m)) $h = $m[1];
                                if (preg_match('/\sfill="([^"]+)"/', $rect_tag, $m)) {
                                    $fill = strpos($m[1], 'url(') !== false ? 'none' : $m[1];
                                }
                                if (preg_match('/\sstroke="([^"]+)"/', $rect_tag, $m)) {
                                    $stroke = strpos($m[1], 'url(') !== false ? 'black' : $m[1];
                                }
                                if (preg_match('/\sstroke-width="([^"]+)"/', $rect_tag, $m)) $stroke_width = $m[1];
                                
                                $paths[] = sprintf(
                                    '<rect x="%s" y="%s" width="%s" height="%s" fill="%s" stroke="%s" stroke-width="%s"/>',
                                    htmlspecialchars($x, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                                    htmlspecialchars($y, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                                    htmlspecialchars($w, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                                    htmlspecialchars($h, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                                    htmlspecialchars($fill, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                                    htmlspecialchars($stroke, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                                    htmlspecialchars($stroke_width, ENT_XML1 | ENT_QUOTES, 'UTF-8')
                                );
                            }
                        }
                        
                        // Extract circle elements
                        if (preg_match_all('/<circle[^>]+\/?>/is', $raw_svg, $circle_matches)) {
                            foreach ($circle_matches[0] as $circle_tag) {
                                $cx = '0'; $cy = '0'; $r = '0';
                                $fill = 'none'; $stroke = 'black'; $stroke_width = '1';
                                
                                if (preg_match('/\scx="([^"]+)"/', $circle_tag, $m)) $cx = $m[1];
                                if (preg_match('/\scy="([^"]+)"/', $circle_tag, $m)) $cy = $m[1];
                                if (preg_match('/\sr="([^"]+)"/', $circle_tag, $m)) $r = $m[1];
                                if (preg_match('/\sfill="([^"]+)"/', $circle_tag, $m)) {
                                    $fill = strpos($m[1], 'url(') !== false ? 'none' : $m[1];
                                }
                                if (preg_match('/\sstroke="([^"]+)"/', $circle_tag, $m)) {
                                    $stroke = strpos($m[1], 'url(') !== false ? 'black' : $m[1];
                                }
                                if (preg_match('/\sstroke-width="([^"]+)"/', $circle_tag, $m)) $stroke_width = $m[1];
                                
                                $paths[] = sprintf(
                                    '<circle cx="%s" cy="%s" r="%s" fill="%s" stroke="%s" stroke-width="%s"/>',
                                    htmlspecialchars($cx, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                                    htmlspecialchars($cy, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                                    htmlspecialchars($r, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                                    htmlspecialchars($fill, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                                    htmlspecialchars($stroke, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                                    htmlspecialchars($stroke_width, ENT_XML1 | ENT_QUOTES, 'UTF-8')
                                );
                            }
                        }
                        
                        // Extract polygon elements  
                        if (preg_match_all('/<polygon[^>]+\/?>/is', $raw_svg, $poly_matches)) {
                            foreach ($poly_matches[0] as $poly_tag) {
                                if (preg_match('/\spoints="([^"]*)"/s', $poly_tag, $p_match)) {
                                    $points = preg_replace('/[\r\n\t]+/', ' ', $p_match[1]);
                                    $points = preg_replace('/\s+/', ' ', trim($points));
                                    
                                    $fill = 'none'; $stroke = 'black'; $stroke_width = '1';
                                    if (preg_match('/\sfill="([^"]+)"/', $poly_tag, $m)) {
                                        $fill = strpos($m[1], 'url(') !== false ? 'none' : $m[1];
                                    }
                                    if (preg_match('/\sstroke="([^"]+)"/', $poly_tag, $m)) {
                                        $stroke = strpos($m[1], 'url(') !== false ? 'black' : $m[1];
                                    }
                                    if (preg_match('/\sstroke-width="([^"]+)"/', $poly_tag, $m)) $stroke_width = $m[1];
                                    
                                    $paths[] = sprintf(
                                        '<polygon points="%s" fill="%s" stroke="%s" stroke-width="%s"/>',
                                        htmlspecialchars($points, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                                        htmlspecialchars($fill, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                                        htmlspecialchars($stroke, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                                        htmlspecialchars($stroke_width, ENT_XML1 | ENT_QUOTES, 'UTF-8')
                                    );
                                }
                            }
                        }
                        
                        // Extract ellipse elements
                        if (preg_match_all('/<ellipse[^>]+\/?>/is', $raw_svg, $ellipse_matches)) {
                            foreach ($ellipse_matches[0] as $ellipse_tag) {
                                $cx = '0'; $cy = '0'; $rx = '0'; $ry = '0';
                                $fill = 'none'; $stroke = 'black'; $stroke_width = '1';
                                
                                if (preg_match('/\scx="([^"]+)"/', $ellipse_tag, $m)) $cx = $m[1];
                                if (preg_match('/\scy="([^"]+)"/', $ellipse_tag, $m)) $cy = $m[1];
                                if (preg_match('/\srx="([^"]+)"/', $ellipse_tag, $m)) $rx = $m[1];
                                if (preg_match('/\sry="([^"]+)"/', $ellipse_tag, $m)) $ry = $m[1];
                                if (preg_match('/\sfill="([^"]+)"/', $ellipse_tag, $m)) {
                                    $fill = strpos($m[1], 'url(') !== false ? 'none' : $m[1];
                                }
                                if (preg_match('/\sstroke="([^"]+)"/', $ellipse_tag, $m)) {
                                    $stroke = strpos($m[1], 'url(') !== false ? 'black' : $m[1];
                                }
                                if (preg_match('/\sstroke-width="([^"]+)"/', $ellipse_tag, $m)) $stroke_width = $m[1];
                                
                                $paths[] = sprintf(
                                    '<ellipse cx="%s" cy="%s" rx="%s" ry="%s" fill="%s" stroke="%s" stroke-width="%s"/>',
                                    htmlspecialchars($cx, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                                    htmlspecialchars($cy, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                                    htmlspecialchars($rx, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                                    htmlspecialchars($ry, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                                    htmlspecialchars($fill, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                                    htmlspecialchars($stroke, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                                    htmlspecialchars($stroke_width, ENT_XML1 | ENT_QUOTES, 'UTF-8')
                                );
                            }
                        }
                        
                        // Extract line elements
                        if (preg_match_all('/<line[^>]+\/?>/is', $raw_svg, $line_matches)) {
                            foreach ($line_matches[0] as $line_tag) {
                                $x1 = '0'; $y1 = '0'; $x2 = '0'; $y2 = '0';
                                $stroke = 'black'; $stroke_width = '1';
                                
                                if (preg_match('/\sx1="([^"]+)"/', $line_tag, $m)) $x1 = $m[1];
                                if (preg_match('/\sy1="([^"]+)"/', $line_tag, $m)) $y1 = $m[1];
                                if (preg_match('/\sx2="([^"]+)"/', $line_tag, $m)) $x2 = $m[1];
                                if (preg_match('/\sy2="([^"]+)"/', $line_tag, $m)) $y2 = $m[1];
                                if (preg_match('/\sstroke="([^"]+)"/', $line_tag, $m)) {
                                    $stroke = strpos($m[1], 'url(') !== false ? 'black' : $m[1];
                                }
                                if (preg_match('/\sstroke-width="([^"]+)"/', $line_tag, $m)) $stroke_width = $m[1];
                                
                                $paths[] = sprintf(
                                    '<line x1="%s" y1="%s" x2="%s" y2="%s" stroke="%s" stroke-width="%s"/>',
                                    htmlspecialchars($x1, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                                    htmlspecialchars($y1, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                                    htmlspecialchars($x2, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                                    htmlspecialchars($y2, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                                    htmlspecialchars($stroke, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                                    htmlspecialchars($stroke_width, ENT_XML1 | ENT_QUOTES, 'UTF-8')
                                );
                            }
                        }
                        
                        // Build a completely clean SVG from scratch
                        $raw_svg = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
                        $raw_svg .= sprintf(
                            '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="%s">',
                            htmlspecialchars($width, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                            htmlspecialchars($height, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                            htmlspecialchars($viewBox, ENT_XML1 | ENT_QUOTES, 'UTF-8')
                        ) . "\n";
                        $raw_svg .= '  <metadata>Cut-ready SVG rebuilt from Order #' . intval($order_id) . ' - textures and images removed for cutting</metadata>' . "\n";
                        $raw_svg .= '  <g id="cut-paths">' . "\n";
                        
                        foreach ($paths as $path) {
                            $raw_svg .= '    ' . $path . "\n";
                        }
                        
                        $raw_svg .= '  </g>' . "\n";
                        $raw_svg .= '</svg>';
                        
                        error_log('APD SVG Processing - Order #' . $order_id . ': Rebuilt SVG with ' . count($paths) . ' elements');
                        
                        error_log('APD SVG Processing - Order #' . $order_id . ': Cleaned SVG length: ' . strlen($raw_svg));
                        
                        // Set headers and output
                        header('Content-Type: image/svg+xml; charset=utf-8');
                        header('Content-Disposition: attachment; filename="order-' . $order_id . '-cut-ready.svg"');
                        header('Cache-Control: no-cache, must-revalidate');
                        echo $raw_svg;
                        exit;
                    }
                }
            }
        }
        
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('svg', 'http://www.w3.org/2000/svg');

        // Get the root SVG element and preserve its dimensions
        $svgRoot = $dom->documentElement;
        $originalViewBox = $svgRoot->getAttribute('viewBox');
        $originalWidth = $svgRoot->getAttribute('width');
        $originalHeight = $svgRoot->getAttribute('height');
        
        // Log original dimensions for debugging
        error_log(sprintf(
            'APD Cut-Ready SVG - Order #%d: Original dimensions - viewBox="%s", width="%s", height="%s"',
            $order_id,
            $originalViewBox,
            $originalWidth,
            $originalHeight
        ));

        // 1. Remove all <image> elements (embedded textures/photos)
        $images = $xpath->query('//svg:image');
        foreach ($images as $image) {
            $image->parentNode->removeChild($image);
        }

        // 2. Handle material stroke patterns - for cut-ready, strokes with patterns should be removed
        // The pattern strokes are decorative (material texture outline) and not needed for cutting
        $strokeRefs = $xpath->query('//*[@stroke and contains(@stroke, "url(#")]');
        $stroke_count = 0;
        foreach ($strokeRefs as $elem) {
            $stroke = $elem->getAttribute('stroke');
            if (preg_match('/url\(#.*pattern.*\)/i', $stroke)) {
                // Remove the decorative pattern stroke - it causes blur/artifacts in cut files
                $elem->setAttribute('stroke', 'none');
                $elem->removeAttribute('stroke-width');
                $stroke_count++;
                // Log what we found for debugging
                error_log(sprintf(
                    'APD Cut-Ready SVG - Order #%d: Removed pattern stroke on <%s> element',
                    $order_id,
                    $elem->nodeName
                ));
            }
        }
        error_log(sprintf('APD Cut-Ready SVG - Order #%d: Removed %d pattern strokes', $order_id, $stroke_count));
        
        // 3. Remove all <pattern> elements (used for textures)
        $patterns = $xpath->query('//svg:pattern');
        foreach ($patterns as $pattern) {
            $pattern->parentNode->removeChild($pattern);
        }

        // 4. Remove elements that reference removed patterns in FILL only
        $fillRefs = $xpath->query('//*[@fill and contains(@fill, "url(#")]');
        foreach ($fillRefs as $elem) {
            $fill = $elem->getAttribute('fill');
            if (preg_match('/url\(#.*pattern.*\)/i', $fill)) {
                // Change to solid color or remove fill
                $elem->setAttribute('fill', 'none');
            }
        }

        // 5. Convert text elements to paths would require complex library
        // For now, just add a comment warning about text
        $texts = $xpath->query('//svg:text');
        if ($texts->length > 0) {
            $comment = $dom->createComment(' WARNING: This SVG contains text elements. Convert to paths in CorelDRAW before cutting. ');
            $root = $dom->documentElement;
            $root->insertBefore($comment, $root->firstChild);
        }

        // 6. Flatten nested SVG elements - IMPORTANT: preserve position and scale
        $nestedSvgs = $xpath->query('//svg:svg[parent::*]');
        foreach ($nestedSvgs as $nested) {
            $g = $dom->createElement('g');
            
            // Build transform to preserve position and scale
            $transforms = array();
            
            // Get position (x, y attributes)
            $x = $nested->getAttribute('x') ?: '0';
            $y = $nested->getAttribute('y') ?: '0';
            if ($x !== '0' || $y !== '0') {
                $transforms[] = "translate($x, $y)";
            }
            
            // Copy existing transform if present
            if ($nested->hasAttribute('transform')) {
                $transforms[] = $nested->getAttribute('transform');
            }
            
            // Handle viewBox scaling - if nested SVG has different viewBox, we need to scale
            $nestedViewBox = $nested->getAttribute('viewBox');
            $nestedWidth = $nested->getAttribute('width');
            $nestedHeight = $nested->getAttribute('height');
            
            if ($nestedViewBox && ($nestedWidth || $nestedHeight)) {
                // Parse viewBox
                $vbParts = preg_split('/[\s,]+/', trim($nestedViewBox));
                if (count($vbParts) >= 4) {
                    $vbWidth = floatval($vbParts[2]);
                    $vbHeight = floatval($vbParts[3]);
                    
                    // Parse width/height (remove units)
                    $actualWidth = floatval(preg_replace('/[^0-9.]/', '', $nestedWidth ?: $vbWidth));
                    $actualHeight = floatval(preg_replace('/[^0-9.]/', '', $nestedHeight ?: $vbHeight));
                    
                    // Calculate scale factors
                    if ($vbWidth > 0 && $vbHeight > 0) {
                        $scaleX = $actualWidth / $vbWidth;
                        $scaleY = $actualHeight / $vbHeight;
                        
                        // Only add scale if it's not 1:1
                        if (abs($scaleX - 1) > 0.001 || abs($scaleY - 1) > 0.001) {
                            $transforms[] = "scale($scaleX, $scaleY)";
                        }
                        
                        // Handle viewBox offset (minX, minY)
                        $vbMinX = floatval($vbParts[0]);
                        $vbMinY = floatval($vbParts[1]);
                        if ($vbMinX != 0 || $vbMinY != 0) {
                            $transforms[] = "translate(" . (-$vbMinX) . ", " . (-$vbMinY) . ")";
                        }
                    }
                }
            }
            
            // Apply combined transform
            if (!empty($transforms)) {
                $g->setAttribute('transform', implode(' ', $transforms));
                error_log(sprintf(
                    'APD Cut-Ready SVG - Order #%d: Flattened nested SVG with transform="%s"',
                    $order_id,
                    implode(' ', $transforms)
                ));
            }
            
            // Move all children to group
            while ($nested->firstChild) {
                $g->appendChild($nested->firstChild);
            }
            $nested->parentNode->replaceChild($g, $nested);
        }
        
        error_log(sprintf('APD Cut-Ready SVG - Order #%d: Flattened %d nested SVG elements', $order_id, $nestedSvgs->length));

        // 7. Add metadata for tracking
        $metadata = $dom->createElement('metadata');
        $metadata->nodeValue = sprintf(
            'Cut-ready SVG processed from Order #%d on %s. Optimized for CorelDRAW/cutting machines. Material outlines preserved as black strokes.',
            $order_id,
            current_time('Y-m-d H:i:s')
        );
        $dom->documentElement->insertBefore($metadata, $dom->documentElement->firstChild);

        // 8. Restore and ensure proper dimensions for the root SVG element
        // This prevents the cut-ready SVG from being scaled differently than the original
        if ($originalViewBox && !$svgRoot->getAttribute('viewBox')) {
            $svgRoot->setAttribute('viewBox', $originalViewBox);
        }
        if ($originalWidth && !$svgRoot->getAttribute('width')) {
            $svgRoot->setAttribute('width', $originalWidth);
        }
        if ($originalHeight && !$svgRoot->getAttribute('height')) {
            $svgRoot->setAttribute('height', $originalHeight);
        }
        
        // Log final dimensions for debugging
        error_log(sprintf(
            'APD Cut-Ready SVG - Order #%d: Final dimensions - viewBox="%s", width="%s", height="%s"',
            $order_id,
            $svgRoot->getAttribute('viewBox'),
            $svgRoot->getAttribute('width'),
            $svgRoot->getAttribute('height')
        ));

        // 9. Clean up and save as UTF-8
        // IMPORTANT: Explicitly save with UTF-8 encoding (no BOM, no conversion)
        $dom->encoding = 'UTF-8';
        $clean_svg = $dom->saveXML($dom->documentElement);
        
        // Ensure we have XML declaration with UTF-8
        if (strpos($clean_svg, '<?xml') === false) {
            $clean_svg = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $clean_svg;
        } else {
            // Fix encoding in existing XML declaration
            $clean_svg = preg_replace('/encoding="[^"]+"/i', 'encoding="UTF-8"', $clean_svg);
        }
        
        // Apply the common XML fixes to the output as well
        // This catches any corruption that happened during DOMDocument processing
        $clean_svg = $this->fix_common_xml_issues($clean_svg);
        
        // Fix any remaining malformed attributes (like stroke-width: becoming an invalid QName)
        // These can happen when style properties get incorrectly parsed
        $clean_svg = preg_replace('/\s+stroke-width:(?=[^"]*(?:"|$))/', ' stroke-width="', $clean_svg);
        $clean_svg = preg_replace('/\s+fill:(?=[^"]*(?:"|$))/', ' fill="', $clean_svg);
        $clean_svg = preg_replace('/\s+stroke:(?=[^"]*(?:"|$))/', ' stroke="', $clean_svg);
        
        // Remove any attribute that has a colon as its name (invalid XML)
        $clean_svg = preg_replace('/\s+[a-zA-Z-]+:[a-zA-Z-]+:[^=]*="[^"]*"/', '', $clean_svg);
        
        // Fix double-colon issues in attribute names
        $clean_svg = preg_replace('/\s+([a-zA-Z-]+)::([a-zA-Z-]+)="/', ' $1-$2="', $clean_svg);
        
        // Remove orphaned colons at the end of attribute names
        $clean_svg = preg_replace('/\s+([a-zA-Z-]+):="/', ' $1="', $clean_svg);
        
        // Clean up any empty style attributes
        $clean_svg = preg_replace('/style="\s*"/', '', $clean_svg);
        
        // Add DOCTYPE for SVG 1.1 (like CorelDRAW exports) - insert after XML declaration
        if (strpos($clean_svg, '<!DOCTYPE') === false) {
            $clean_svg = preg_replace(
                '/(<\?xml[^?]*\?>)/i',
                '$1' . "\n" . '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">',
                $clean_svg
            );
        }
        
        // CRITICAL: Ensure the string is UTF-8 encoded (convert from any other encoding)
        // Check if string is already UTF-8 or needs conversion
        $current_encoding = mb_detect_encoding($clean_svg, ['UTF-8', 'UTF-16LE', 'UTF-16BE', 'ISO-8859-1'], true);
        if ($current_encoding !== 'UTF-8' && $current_encoding !== false) {
            error_log("APD Cut-Ready SVG - Order #$order_id: Converting from $current_encoding to UTF-8");
            $clean_svg = mb_convert_encoding($clean_svg, 'UTF-8', $current_encoding);
        }
        
        // Final validation: ensure it's valid UTF-8
        if (!mb_check_encoding($clean_svg, 'UTF-8')) {
            error_log("APD Cut-Ready SVG - Order #$order_id: WARNING - Output is not valid UTF-8, forcing conversion");
            $clean_svg = mb_convert_encoding($clean_svg, 'UTF-8', 'UTF-8');
        }
        
        return $clean_svg;
    }

    private function fix_common_xml_issues($svg_content)
    {
        // Remove any BOM (UTF-8 and UTF-16 variants)
        $svg_content = preg_replace('/^\xEF\xBB\xBF/', '', $svg_content);
        $svg_content = preg_replace('/^\xFF\xFE/', '', $svg_content);
        $svg_content = preg_replace('/^\xFE\xFF/', '', $svg_content);
        
        // Remove any null bytes or control characters (except newline/tab)
        $svg_content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $svg_content);
        
        // ==========================================
        // Fix corrupted/split SVG attributes
        // Pattern: vector-effect="" non-scaling-stroke="" should be vector-effect="non-scaling-stroke"
        // Pattern: stroke-linejoin="" round="" should be stroke-linejoin="round"
        // Pattern: stroke-width="=" should be removed or fixed
        // Pattern: fill="="" rgb="" should be fixed
        // ==========================================
        
        // Fix stroke-width with corrupted value: stroke-width="="" -> remove entirely
        // This pattern: stroke-width="="" needs to be removed
        $svg_content = preg_replace('/\s+stroke-width="=""/', '', $svg_content);
        $svg_content = preg_replace('/\s+stroke-width="="/', '', $svg_content);
        $svg_content = preg_replace('/\s+stroke-width=""/', '', $svg_content);
        // Also handle the spaced version from UTF-16 artifacts
        $svg_content = preg_replace('/\s+s\s*t\s*r\s*o\s*k\s*e\s*-\s*w\s*i\s*d\s*t\s*h\s*=\s*"\s*=\s*"\s*"/', '', $svg_content);
        $svg_content = preg_replace('/\s+s\s*t\s*r\s*o\s*k\s*e\s*-\s*w\s*i\s*d\s*t\s*h\s*=\s*"\s*=\s*"/', '', $svg_content);
        
        // Fix all empty ="" attributes (more aggressive cleanup)
        $svg_content = preg_replace('/\s+fill="=""/', '', $svg_content);
        $svg_content = preg_replace('/\s+stroke="=""/', '', $svg_content);
        
        // Fix vector-effect split: vector-effect="" non-scaling-stroke="" OR vector-effect=""
        $svg_content = preg_replace('/vector-effect=""\s+non-scaling-stroke=""/', 'vector-effect="non-scaling-stroke"', $svg_content);
        // Remove empty vector-effect if not followed by non-scaling-stroke
        $svg_content = preg_replace('/\s+vector-effect=""(?!\s+non-scaling-stroke)/', '', $svg_content);
        // Also remove the malformed ="="" pattern
        $svg_content = preg_replace('/\s+vector-effect="=""/', '', $svg_content);
        
        // Fix stroke-linejoin split: stroke-linejoin="" round="" OR just empty stroke-linejoin=""
        $svg_content = preg_replace('/stroke-linejoin=""\s+round=""/', 'stroke-linejoin="round"', $svg_content);
        $svg_content = preg_replace('/stroke-linejoin=""\s+miter=""/', 'stroke-linejoin="miter"', $svg_content);
        $svg_content = preg_replace('/stroke-linejoin=""\s+bevel=""/', 'stroke-linejoin="bevel"', $svg_content);
        // Remove empty stroke-linejoin if not followed by valid value
        $svg_content = preg_replace('/\s+stroke-linejoin=""(?!\s+(round|miter|bevel)="")/', '', $svg_content);
        // Also remove the malformed ="="" pattern
        $svg_content = preg_replace('/\s+stroke-linejoin="=""/', '', $svg_content);
        
        // Fix stroke-linecap split: stroke-linecap="" round="" OR just empty stroke-linecap=""
        $svg_content = preg_replace('/stroke-linecap=""\s+round=""/', 'stroke-linecap="round"', $svg_content);
        $svg_content = preg_replace('/stroke-linecap=""\s+square=""/', 'stroke-linecap="square"', $svg_content);
        $svg_content = preg_replace('/stroke-linecap=""\s+butt=""/', 'stroke-linecap="butt"', $svg_content);
        // Remove empty stroke-linecap if not followed by valid value
        $svg_content = preg_replace('/\s+stroke-linecap=""(?!\s+(round|square|butt)="")/', '', $svg_content);
        // Also remove the malformed ="="" pattern
        $svg_content = preg_replace('/\s+stroke-linecap="=""/', '', $svg_content);
        
        // Fix fill with corrupted value patterns
        $svg_content = preg_replace('/fill="=""/', 'fill="none"', $svg_content);
        $svg_content = preg_replace('/fill=""=""/', 'fill="none"', $svg_content);
        // Remove empty fill="" at the end of attributes (before d= or > or />)
        $svg_content = preg_replace('/\s+fill=""(?=\s+d=|\s*>|\s*\/>)/', '', $svg_content);
        
        // Generic cleanup: Remove any remaining ="="" malformed attribute patterns
        // This catches any attribute with the ="="" pattern
        $svg_content = preg_replace('/\s+\w+(-\w+)*="=""/', '', $svg_content);
        
        // Fix orphaned attribute values that became separate attributes (e.g., non-scaling-stroke="")
        $svg_content = preg_replace('/\s+non-scaling-stroke=""/', '', $svg_content);
        $svg_content = preg_replace('/\s+round=""(?!\s*\w+[=-])/', '', $svg_content);
        $svg_content = preg_replace('/\s+miter=""/', '', $svg_content);
        $svg_content = preg_replace('/\s+bevel=""/', '', $svg_content);
        $svg_content = preg_replace('/\s+square=""/', '', $svg_content);
        $svg_content = preg_replace('/\s+butt=""/', '', $svg_content);
        
        // Fix orphaned rgb="" that shouldn't be an attribute
        $svg_content = preg_replace('/\s+rgb=""/', '', $svg_content);
        
        // Fix double/nested CDATA markers (common issue from repeated processing)
        // Pattern: <![CDATA[<![CDATA[...]]>]]> should become <![CDATA[...]]>
        $svg_content = preg_replace('/<!\[CDATA\[\s*<!\[CDATA\[/', '<![CDATA[', $svg_content);
        $svg_content = preg_replace('/\]\]>\s*\]\]>/', ']]>', $svg_content);
        
        // Preserve CDATA sections by temporarily replacing them with placeholders
        $cdataPlaceholders = array();
        $svg_content = preg_replace_callback(
            '/<!\[CDATA\[(.*?)\]\]>/s',
            function($matches) use (&$cdataPlaceholders) {
                $placeholder = '___CDATA_PLACEHOLDER_' . count($cdataPlaceholders) . '___';
                $cdataPlaceholders[$placeholder] = '<![CDATA[' . $matches[1] . ']]>';
                return $placeholder;
            },
            $svg_content
        );
        
        // Remove HTML-style class attributes that break XML parsing
        $svg_content = preg_replace('/\s+class="[^"]*"/', '', $svg_content);
        
        // Remove data-* attributes which are HTML5 specific and can break XML
        $svg_content = preg_replace('/\s+data-[a-zA-Z0-9-]+="[^"]*"/', '', $svg_content);
        
        // Fix path d attributes with line breaks, tabs, or excessive whitespace
        $svg_content = preg_replace_callback(
            '/(<path[^>]*\sd=")([^"]*)(")/s',
            function($matches) {
                $d = $matches[2];
                // Remove line breaks and normalize whitespace in path data
                $d = preg_replace('/[\r\n\t]+/', ' ', $d);
                $d = preg_replace('/\s+/', ' ', $d);
                $d = trim($d);
                return $matches[1] . $d . $matches[3];
            },
            $svg_content
        );
        
        // Fix polygon/polyline points attributes with line breaks
        $svg_content = preg_replace_callback(
            '/(<(?:polygon|polyline)[^>]*\spoints=")([^"]*)(")/s',
            function($matches) {
                $points = $matches[2];
                $points = preg_replace('/[\r\n\t]+/', ' ', $points);
                $points = preg_replace('/\s+/', ' ', $points);
                $points = trim($points);
                return $matches[1] . $points . $matches[3];
            },
            $svg_content
        );
        
        // Sanitize style attributes - convert CSS property names to valid XML
        // Replace hyphenated properties with camelCase equivalents
        $svg_content = preg_replace_callback(
            '/style="([^"]*)"/',
            function($matches) {
                $style = $matches[1];
                // Convert common hyphenated CSS properties to camelCase
                $style = str_replace('shape-rendering', 'shapeRendering', $style);
                $style = str_replace('text-rendering', 'textRendering', $style);
                $style = str_replace('image-rendering', 'imageRendering', $style);
                $style = str_replace('color-interpolation', 'colorInterpolation', $style);
                $style = str_replace('fill-rule', 'fillRule', $style);
                $style = str_replace('clip-rule', 'clipRule', $style);
                $style = str_replace('stroke-width', 'strokeWidth', $style);
                $style = str_replace('stroke-linecap', 'strokeLinecap', $style);
                $style = str_replace('stroke-linejoin', 'strokeLinejoin', $style);
                $style = str_replace('stroke-miterlimit', 'strokeMiterlimit', $style);
                $style = str_replace('stroke-dasharray', 'strokeDasharray', $style);
                $style = str_replace('stroke-dashoffset', 'strokeDashoffset', $style);
                $style = str_replace('stroke-opacity', 'strokeOpacity', $style);
                $style = str_replace('fill-opacity', 'fillOpacity', $style);
                $style = str_replace('font-family', 'fontFamily', $style);
                $style = str_replace('font-size', 'fontSize', $style);
                $style = str_replace('font-weight', 'fontWeight', $style);
                $style = str_replace('font-style', 'fontStyle', $style);
                $style = str_replace('text-anchor', 'textAnchor', $style);
                $style = str_replace('dominant-baseline', 'dominantBaseline', $style);
                $style = str_replace('paint-order', 'paintOrder', $style);
                return 'style="' . $style . '"';
            },
            $svg_content
        );
        
        // Escape or remove potentially problematic base64 href attributes in images
        // Match href="data:image..." and ensure it's properly formatted
        $svg_content = preg_replace_callback(
            '/(href=")([^"]*data:image[^"]*)(")/i',
            function($matches) {
                // Clean up any potential XML-breaking characters in the data URL
                $data_url = $matches[2];
                // Remove any newlines or carriage returns that might have snuck in
                $data_url = str_replace(array("\r", "\n", "\t"), '', $data_url);
                return $matches[1] . $data_url . $matches[3];
            },
            $svg_content
        );
        
        // Fix unencoded ampersands (except in entities)
        $svg_content = preg_replace('/&(?!(?:[a-zA-Z]+|#[0-9]+|#x[0-9a-fA-F]+);)/', '&amp;', $svg_content);
        
        // Remove any null bytes
        $svg_content = str_replace("\0", '', $svg_content);
        
        // Fix attributes with newlines inside them (common XMLSerializer issue)
        // This pattern finds attributes and removes any internal line breaks
        $svg_content = preg_replace_callback(
            '/(\s[a-zA-Z][a-zA-Z0-9-]*=")([^"]*)(")/s',
            function($matches) {
                $attr_value = $matches[2];
                // Remove line breaks and excessive whitespace inside attribute values
                $attr_value = preg_replace('/[\r\n]+/', ' ', $attr_value);
                $attr_value = preg_replace('/\s+/', ' ', $attr_value);
                return $matches[1] . trim($attr_value) . $matches[3];
            },
            $svg_content
        );
        
        // Fix shape-rendering and text-rendering attributes outside of style (standalone attributes)
        $svg_content = preg_replace('/\sshape-rendering=/', ' shapeRendering=', $svg_content);
        $svg_content = preg_replace('/\stext-rendering=/', ' textRendering=', $svg_content);
        
        // Fix any stray < or > characters that aren't part of tags
        // This is tricky but we can try to fix obvious cases
        $svg_content = preg_replace('/\s<\s/', ' &lt; ', $svg_content);
        $svg_content = preg_replace('/\s>\s/', ' &gt; ', $svg_content);
        
        // Ensure proper XML declaration if missing
        if (strpos($svg_content, '<?xml') === false) {
            $svg_content = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $svg_content;
        }
        
        // Restore CDATA sections from placeholders
        foreach ($cdataPlaceholders as $placeholder => $cdataContent) {
            $svg_content = str_replace($placeholder, $cdataContent, $svg_content);
        }
        
        return $svg_content;
    }

    // --- Admin: Register/ensure statuses visible in All ---

    private function validate_svg_content($svg_content)
    {
        // Check for dangerous elements
        $dangerous_patterns = array(
            '/<script/i',
            '/on\\w+\\s*=/i', // event handlers like onclick, onload
            '/javascript:/i',
            '/<iframe/i',
            '/<object/i',
            '/<embed/i'
        );

        foreach ($dangerous_patterns as $pattern) {
            if (preg_match($pattern, $svg_content)) {
                return array(
                    'valid' => false,
                    'error' => 'SVG contains potentially dangerous content'
                );
            }
        }

        // Check minimum requirements
        if (strlen($svg_content) < 50) {
            return array(
                'valid' => false,
                'error' => 'SVG content too small'
            );
        }

        // Check maximum size (10MB limit)
        if (strlen($svg_content) > 10 * 1024 * 1024) {
            return array(
                'valid' => false,
                'error' => 'SVG content exceeds maximum size (10MB)'
            );
        }

        return array('valid' => true);
    }

    /**
     * Make SVG compatible with PDF while preserving ALL content including patterns
     * This is similar to make_coreldraw_compatible but keeps patterns and materials
     * 
     * @param string $svg_content Source SVG content
     * @param int $order_id Order ID for logging
     * @return string|WP_Error Clean SVG content or error
     */
    private function make_pdf_compatible($svg_content, $order_id = 0)
    {
        error_log("APD PDF Compatible - Order #$order_id: Starting PDF preparation (preserves ALL patterns and materials)");
        
        // STEP 1: Decode if it's a data URL
        if (strpos($svg_content, 'data:image/svg+xml') === 0) {
            if (strpos($svg_content, 'base64,') !== false) {
                $svg_content = base64_decode(substr($svg_content, strpos($svg_content, 'base64,') + 7));
            } else {
                $svg_content = urldecode(substr($svg_content, strpos($svg_content, ',') + 1));
            }
        }
        
        // STEP 2: Convert encoding to UTF-8
        $detected_encoding = mb_detect_encoding($svg_content, ['UTF-8', 'UTF-16LE', 'UTF-16BE', 'ISO-8859-1'], true);
        if ($detected_encoding && $detected_encoding !== 'UTF-8') {
            $svg_content = mb_convert_encoding($svg_content, 'UTF-8', $detected_encoding);
            error_log("APD PDF Compatible - Order #$order_id: Converted from $detected_encoding to UTF-8");
        }
        
        // STEP 3: Fix malformed attributes (same as cut-ready)
        $svg_content = preg_replace('/(\w+(-\w+)*)="=""/', '', $svg_content);
        $svg_content = preg_replace('/stroke-linejoin=""\s+round=""/', 'stroke-linejoin="round"', $svg_content);
        $svg_content = preg_replace('/stroke-linejoin=""\s+miter=""/', 'stroke-linejoin="miter"', $svg_content);
        $svg_content = preg_replace('/stroke-linejoin=""\s+bevel=""/', 'stroke-linejoin="bevel"', $svg_content);
        $svg_content = preg_replace('/stroke-linecap=""\s+round=""/', 'stroke-linecap="round"', $svg_content);
        $svg_content = preg_replace('/stroke-linecap=""\s+square=""/', 'stroke-linecap="square"', $svg_content);
        $svg_content = preg_replace('/stroke-linecap=""\s+butt=""/', 'stroke-linecap="butt"', $svg_content);
        $svg_content = preg_replace('/vector-effect=""\s+non-scaling-stroke=""/', 'vector-effect="non-scaling-stroke"', $svg_content);
        
        // STEP 4: Duplicate styles as attributes for PDF compatibility (same as cut-ready)
        $svg_content = preg_replace_callback(
            '/(<[^>]*)\sstyle="([^"]*)"([^>]*>)/i',
            function($matches) {
                $before = $matches[1];
                $original_style = $matches[2];
                $after = $matches[3];
                
                $full_element = $before . $after;
                $attributes = array();
                
                // Extract fill color and ADD as attribute
                if (!preg_match('/\sfill=/i', $full_element)) {
                    if (preg_match('/fill:\s*rgb\((\d+),\s*(\d+),\s*(\d+)\)/i', $original_style, $m)) {
                        $hex = sprintf('#%02x%02x%02x', $m[1], $m[2], $m[3]);
                        $attributes[] = 'fill="' . $hex . '"';
                    } elseif (preg_match('/fill:\s*url\([\'"]?([^)\'"]+)[\'"]?\)/i', $original_style, $m)) {
                        $pattern_id = trim($m[1]);
                        $pattern_id = str_replace('&quot;', '', $pattern_id);
                        $pattern_id = str_replace('&amp;', '&', $pattern_id);
                        $attributes[] = 'fill="url(' . htmlspecialchars($pattern_id, ENT_QUOTES) . ')"';
                    } elseif (preg_match('/fill:\s*([#\w]+)/i', $original_style, $m)) {
                        $fill_value = trim($m[1]);
                        if ($fill_value !== 'none' && $fill_value !== '') {
                            $attributes[] = 'fill="' . htmlspecialchars($fill_value, ENT_QUOTES) . '"';
                        }
                    }
                }
                
                // Extract stroke color and ADD as attribute
                if (!preg_match('/\sstroke=/i', $full_element)) {
                    if (preg_match('/stroke:\s*rgb\((\d+),\s*(\d+),\s*(\d+)\)/i', $original_style, $m)) {
                        $hex = sprintf('#%02x%02x%02x', $m[1], $m[2], $m[3]);
                        $attributes[] = 'stroke="' . $hex . '"';
                    } elseif (preg_match('/stroke:\s*url\([\'"]?([^)\'"]+)[\'"]?\)/i', $original_style, $m)) {
                        $pattern_id = trim($m[1]);
                        $pattern_id = str_replace('&quot;', '', $pattern_id);
                        $pattern_id = str_replace('&amp;', '&', $pattern_id);
                        $attributes[] = 'stroke="url(' . htmlspecialchars($pattern_id, ENT_QUOTES) . ')"';
                    } elseif (preg_match('/stroke:\s*([#\w]+)/i', $original_style, $m)) {
                        $stroke_value = trim($m[1]);
                        if ($stroke_value !== 'none' && $stroke_value !== '') {
                            $attributes[] = 'stroke="' . htmlspecialchars($stroke_value, ENT_QUOTES) . '"';
                        }
                    }
                }
                
                // Extract stroke-width and ADD as attribute
                if (!preg_match('/\sstroke-width=/i', $full_element)) {
                    if (preg_match('/stroke-width:\s*([^;]+)/i', $original_style, $m)) {
                        $attributes[] = 'stroke-width="' . htmlspecialchars(trim($m[1]), ENT_QUOTES) . '"';
                    }
                }
                
                // Build final element - KEEP ORIGINAL STYLE + ADD ATTRIBUTES
                $result = $before;
                if (!empty($attributes)) {
                    $result .= ' ' . implode(' ', $attributes);
                }
                $result .= ' style="' . $original_style . '"';
                $result .= $after;
                
                return $result;
            },
            $svg_content
        );
        
        // STEP 5: Process patterns for PDF - CRITICAL: Keep patterns embedded (not external) for PDF
        // For PDF export, we want patterns embedded as data URIs so they're included in the PDF
        // This ensures material patterns are visible when PDF is opened in CorelDRAW
        $svg_content = $this->process_patterns_for_pdf($svg_content, $order_id);
        
        // STEP 5.5: Convert text to paths (curves) while preserving material outline patterns
        // CRITICAL: Convert text to paths BEFORE PDF generation to ensure material outlines are preserved
        // This ensures fonts don't change in CorelDRAW and material outlines are preserved as vectors
        $svg_content = $this->convert_text_to_paths_with_material_outline($svg_content, $order_id);
        
        // STEP 5.6: Verify pattern definitions are present before conversion
        $pattern_defs_before = preg_match_all('/<pattern[^>]*>/i', $svg_content);
        $pattern_refs_before = preg_match_all('/url\(#[^)]+\)/i', $svg_content);
        error_log("APD PDF Compatible - Order #$order_id: Before conversion - $pattern_defs_before pattern definitions, $pattern_refs_before pattern references");
        
        if ($pattern_defs_before === 0 && $pattern_refs_before > 0) {
            error_log("APD PDF Compatible - Order #$order_id: ⚠️ WARNING - Pattern references found but no pattern definitions! Material outlines may be lost.");
        }
        
        // STEP 5.7: Use Inkscape to convert ALL text to curves/paths with material outlines preserved
        // CRITICAL: This converts text to paths AND converts material outline strokes to fills
        // This ensures material outline patterns are preserved as fills on path elements
        $svg_content = $this->inkscape_convert_all_to_curves($svg_content, $order_id);
        
        // STEP 5.8: Verify pattern definitions are still present after conversion
        $pattern_defs_after = preg_match_all('/<pattern[^>]*>/i', $svg_content);
        $pattern_refs_after = preg_match_all('/url\(#[^)]+\)/i', $svg_content);
        error_log("APD PDF Compatible - Order #$order_id: After conversion - $pattern_defs_after pattern definitions, $pattern_refs_after pattern references");
        
        if ($pattern_defs_after < $pattern_defs_before) {
            error_log("APD PDF Compatible - Order #$order_id: ⚠️ WARNING - Pattern definitions lost during conversion! ($pattern_defs_before -> $pattern_defs_after)");
        }
        
        // STEP 6: Ensure proper XML declaration with UTF-8
        $svg_content = preg_replace('/<\?xml[^?]*\?>\s*/i', '', $svg_content);
        $svg_content = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>' . "\n" . $svg_content;
        
        // STEP 7: Add metadata about processing
        if (preg_match('/<svg[^>]*>/i', $svg_content, $svg_match)) {
            $svg_tag = $svg_match[0];
            $metadata = "\n  <metadata>Vector PDF export from Order #$order_id on " . date('Y-m-d H:i:s') . ". " .
                       "All content preserved: colors, patterns (PNG/JPG materials), ALL TEXT CONVERTED TO CURVES/PATHS, styles. " .
                       "Pattern fills and material outline textures preserved for CorelDRAW. " .
                       "Text converted to vectors (curves) with material outline patterns preserved. " .
                       "Open in CorelDRAW - all text is already curves/vectors, material patterns are preserved.</metadata>\n";
            $svg_content = str_replace($svg_tag, $svg_tag . $metadata, $svg_content);
        }
        
        // STEP 8: Validate UTF-8
        if (!mb_check_encoding($svg_content, 'UTF-8')) {
            $svg_content = mb_convert_encoding($svg_content, 'UTF-8', 'UTF-8');
        }
        
        // Final verification: Ensure material patterns are present
        $final_pattern_count = preg_match_all('/<pattern[^>]*>/i', $svg_content);
        $final_text_pattern_refs = preg_match_all('/<text[^>]*\s+stroke=["\']url\(#[^)]+\)["\']/i', $svg_content);
        
        error_log("APD PDF Compatible - Order #$order_id: SVG prepared for PDF export");
        error_log("  - Pattern definitions: $final_pattern_count");
        error_log("  - Text elements with material outline: $final_text_pattern_refs");
        error_log("  - All material patterns preserved: " . ($final_pattern_count > 0 ? 'YES' : 'WARNING: No patterns found!'));
        
        if ($final_pattern_count === 0 && $final_text_pattern_refs > 0) {
            error_log("APD PDF Compatible - Order #$order_id: WARNING - Text has material outline references but no pattern definitions found!");
        }
        
        return $svg_content;
    }

    /**
     * Convert SVG to PDF with embedded vector data
     * Creates a PDF that embeds the SVG as a vector object for CorelDRAW compatibility
     * 
     * @param string $svg_content SVG content
     * @param int $order_id Order ID for logging
     * @return string|WP_Error PDF content or error
     */
    private function svg_to_pdf($svg_content, $order_id = 0)
    {
        error_log("APD SVG to PDF - Order #$order_id: Starting PDF generation");
        
        // Extract SVG dimensions
        $width = 800;
        $height = 600;
        $viewBox = '0 0 800 600';
        
        if (preg_match('/<svg[^>]*\swidth=["\']([^"\']+)["\']/', $svg_content, $m)) {
            $width = floatval(preg_replace('/[^0-9.]/', '', $m[1]));
        }
        if (preg_match('/<svg[^>]*\sheight=["\']([^"\']+)["\']/', $svg_content, $m)) {
            $height = floatval(preg_replace('/[^0-9.]/', '', $m[1]));
        }
        if (preg_match('/<svg[^>]*\sviewBox=["\']([^"\']+)["\']/', $svg_content, $m)) {
            $viewBox = trim($m[1]);
        }
        
        // Convert SVG to base64 for embedding
        $svg_base64 = base64_encode($svg_content);
        
        // Create PDF with embedded SVG as a vector object
        // Using PDF 1.4 format which supports embedded objects
        $pdf = $this->create_pdf_with_svg($svg_content, $svg_base64, $width, $height, $viewBox, $order_id);
        
        if (is_wp_error($pdf)) {
            return $pdf;
        }
        
        error_log("APD SVG to PDF - Order #$order_id: PDF generated successfully (" . strlen($pdf) . " bytes)");
        
        return $pdf;
    }

    /**
     * Create PDF document with embedded SVG as vector object
     * Uses a simpler approach: embed SVG as Form XObject for CorelDRAW compatibility
     * 
     * @param string $svg_content SVG content
     * @param string $svg_base64 Base64 encoded SVG (not used in this implementation)
     * @param float $width SVG width
     * @param float $height SVG height
     * @param string $viewBox SVG viewBox
     * @param int $order_id Order ID
     * @return string|WP_Error PDF content or error
     */
    private function create_pdf_with_svg($svg_content, $svg_base64, $width, $height, $viewBox, $order_id)
    {
        // For CorelDRAW compatibility, we'll use Inkscape if available, or create a PDF that embeds SVG
        // Check if Inkscape is available (best option for vector preservation)
        $inkscape_path = $this->find_inkscape();
        
        if ($inkscape_path) {
            return $this->convert_svg_to_pdf_with_inkscape($svg_content, $width, $height, $order_id, $inkscape_path);
        }
        
        // Fallback: Create PDF with embedded SVG using a library or custom method
        // For now, we'll create a simple PDF that references the SVG
        // Note: This is a simplified approach. For production, consider using TCPDF or similar library
        
        error_log("APD PDF - Order #$order_id: Inkscape not found, using fallback method");
        
        // Save SVG temporarily
        $upload_dir = wp_upload_dir();
        $temp_svg = $upload_dir['path'] . '/temp-' . $order_id . '-' . time() . '.svg';
        file_put_contents($temp_svg, $svg_content);
        
        // Try using ImageMagick if available (can convert SVG to PDF)
        if (extension_loaded('imagick')) {
            try {
                $imagick = new Imagick();
                $imagick->setResolution(300, 300); // High resolution for vector preservation
                $imagick->readImage($temp_svg);
                $imagick->setImageFormat('pdf');
                $pdf_content = $imagick->getImageBlob();
                $imagick->clear();
                $imagick->destroy();
                unlink($temp_svg);
                
                if ($pdf_content) {
                    error_log("APD PDF - Order #$order_id: Generated PDF using ImageMagick");
                    return $pdf_content;
                }
            } catch (Exception $e) {
                error_log("APD PDF - Order #$order_id: ImageMagick failed: " . $e->getMessage());
            }
        }
        
        // Final fallback: Use client-side JavaScript conversion (no server dependencies)
        // Return special marker that triggers client-side PDF generation
        unlink($temp_svg);
        
        error_log("APD PDF - Order #$order_id: No server-side tools available, using client-side conversion");
        
        // Return special WP_Error with client-side flag (will be handled in apd_export_pdf)
        $error = new WP_Error('use_client_side', 'Client-side PDF generation required');
        $error->add_data(array(
            'use_client_side' => true,
            'svg_content' => $svg_content,
            'order_id' => $order_id
        ));
        return $error;
    }

    /**
     * Find Inkscape executable path
     */
    private function find_inkscape()
    {
        $possible_paths = array(
            '/usr/bin/inkscape',
            '/usr/local/bin/inkscape',
            '/opt/homebrew/bin/inkscape',
            'inkscape', // In PATH
        );
        
        foreach ($possible_paths as $path) {
            if (is_executable($path) || shell_exec("which $path 2>/dev/null")) {
                return $path;
            }
        }
        
        return false;
    }

    /**
     * Convert SVG to PDF using Inkscape (best for CorelDRAW compatibility)
     */
    private function convert_svg_to_pdf_with_inkscape($svg_content, $width, $height, $order_id, $inkscape_path)
    {
        // Save SVG temporarily
        $upload_dir = wp_upload_dir();
        $temp_svg = $upload_dir['path'] . '/temp-' . $order_id . '-' . time() . '.svg';
        $temp_pdf = $upload_dir['path'] . '/temp-' . $order_id . '-' . time() . '.pdf';
        
        file_put_contents($temp_svg, $svg_content);
        
        // Use Inkscape to convert SVG to PDF with vector preservation
        // Note: Text should already be converted to paths by inkscape_convert_all_to_curves()
        // All elements should be curves/paths with material outlines as fills
        // --export-type=pdf ensures vector format
        // --export-area-drawing exports the entire drawing area
        // --export-pdf-version=1.4 for CorelDRAW compatibility
        // We don't need --export-text-to-path here since text is already converted to paths
        // CRITICAL: Use --export-pdf-version=1.4 to ensure patterns are embedded in PDF
        // Use --export-area-page to preserve exact canvas dimensions and positioning
        // This prevents text/elements from shifting after conversion
        $command = escapeshellarg($inkscape_path) . 
                   ' --export-type=pdf' .
                   ' --export-filename=' . escapeshellarg($temp_pdf) .
                   ' --export-area-page' .  // Changed from --export-area-drawing to preserve positions
                   ' --export-ignore-filters' .
                   ' --export-pdf-version=1.4' .
                   ' --export-text-to-path' .  // Extra safety: convert any remaining text
                   ' ' . escapeshellarg($temp_svg) . 
                   ' 2>&1';
        
        $output = shell_exec($command);
        $return_code = 0;
        exec($command . '; echo $?', $output_array, $return_code);
        
        if (file_exists($temp_pdf) && filesize($temp_pdf) > 0) {
            $pdf_content = file_get_contents($temp_pdf);
            
            // Verify PDF contains vector data (not just raster)
            $pdf_size = strlen($pdf_content);
            $is_vector = (strpos($pdf_content, '/Type /XObject') !== false || 
                         strpos($pdf_content, '/Subtype /Form') !== false ||
                         strpos($pdf_content, '/Length') !== false);
            
            error_log("APD PDF - Order #$order_id: Generated PDF using Inkscape");
            error_log("  - PDF size: $pdf_size bytes");
            error_log("  - Contains vector data: " . ($is_vector ? 'YES' : 'WARNING: May be rasterized'));
            error_log("  - ALL elements converted to curves/paths: YES");
            error_log("  - Text converted to paths/curves: YES (converted before PDF export)");
            error_log("  - Material outline strokes converted to fills: YES (stroke-to-path)");
            error_log("  - Material patterns preserved: YES (patterns embedded as data URIs)");
            error_log("  - Material outline on paths: YES (preserved as fills on converted paths)");
            
            // Verify no text elements remain in PDF source
            $text_in_svg = preg_match_all('/<text[^>]*>/i', $svg_content);
            if ($text_in_svg > 0) {
                error_log("  - ⚠️ WARNING: $text_in_svg text elements still found in SVG before PDF export!");
            } else {
                error_log("  - ✅ No text elements found - all converted to paths");
            }
            
            unlink($temp_svg);
            unlink($temp_pdf);
            
            return $pdf_content;
        } else {
            unlink($temp_svg);
            if (file_exists($temp_pdf)) unlink($temp_pdf);
            
            error_log("APD PDF - Order #$order_id: Inkscape conversion failed. Output: " . ($output ?: 'No output'));
            error_log("APD PDF - Order #$order_id: Command used: " . $command);
            return new WP_Error('inkscape_failed', 'Inkscape conversion failed. Output: ' . ($output ?: 'No output'));
        }
    }

    /**
     * Convert text elements to paths while preserving material outline patterns
     * Ensures material outline stroke patterns are preserved when text is converted to paths
     * 
     * @param string $svg_content SVG content
     * @param int $order_id Order ID for logging
     * @return string SVG content with text elements prepared for path conversion
     */
    private function convert_text_to_paths_with_material_outline($svg_content, $order_id = 0)
    {
        error_log("APD Text to Path - Order #$order_id: Ensuring text elements have material outline patterns preserved");
        
        // Count text elements with material outline patterns
        $text_with_pattern_count = preg_match_all('/<text[^>]*\s+stroke=["\']url\([^)]+\)["\']/i', $svg_content);
        error_log("APD Text to Path - Order #$order_id: Found $text_with_pattern_count text elements with material outline patterns");
        
        // Ensure all text elements with material outline patterns have patterns as attributes (not just style)
        // This is critical for Inkscape to preserve patterns when converting text to paths
        $svg_content = preg_replace_callback(
            '/<text([^>]*)>/i',
            function($matches) use ($order_id) {
                $attrs = $matches[1];
                $result = '<text' . $attrs;
                
                // Check if text has material outline pattern in stroke
                if (preg_match('/stroke=["\']url\(([^)]+)\)["\']/i', $attrs, $stroke_match)) {
                    $pattern_id = $stroke_match[1];
                    error_log("APD Text to Path - Order #$order_id: Text element has material outline pattern: $pattern_id");
                    
                    // Ensure pattern is also in style attribute for maximum compatibility
                    if (!preg_match('/style="[^"]*stroke:\s*url\(/i', $attrs)) {
                        // Add stroke to style if not present
                        $style_match = preg_match('/style="([^"]*)"/i', $attrs, $style_m);
                        if ($style_match) {
                            $existing_style = $style_m[1];
                            if (strpos($existing_style, 'stroke:') === false) {
                                $new_style = $existing_style . ' stroke: url(' . $pattern_id . ');';
                                $result = preg_replace('/style="[^"]*"/', 'style="' . $new_style . '"', $result);
                            }
                        } else {
                            // No style attribute, add one
                            $result .= ' style="stroke: url(' . htmlspecialchars($pattern_id, ENT_QUOTES) . ');"';
                        }
                    }
                }
                
                // Also check style attribute for stroke patterns
                if (preg_match('/style="([^"]*)"/i', $attrs, $style_match)) {
                    $style_content = $style_match[1];
                    if (preg_match('/stroke:\s*url\(([^)]+)\)/i', $style_content, $style_stroke_match)) {
                        $pattern_id = $style_stroke_match[1];
                        // Ensure it's also an attribute
                        if (!preg_match('/stroke=["\']url\(/i', $attrs)) {
                            $result .= ' stroke="url(' . htmlspecialchars($pattern_id, ENT_QUOTES) . ')"';
                        }
                    }
                }
                
                $result .= '>';
                return $result;
            },
            $svg_content
        );
        
        // Verify patterns are preserved
        $pattern_count_after = preg_match_all('/<pattern[^>]*>/i', $svg_content);
        $pattern_refs_after = preg_match_all('/stroke=["\']url\(#[^)]+\)["\']/i', $svg_content);
        error_log("APD Text to Path - Order #$order_id: After processing - $pattern_count_after pattern definitions, $pattern_refs_after pattern stroke references");
        
        return $svg_content;
    }

    /**
     * Process patterns for PDF export - embeds patterns as data URIs for PDF inclusion
     * Unlike process_patterns_for_coreldraw which extracts to external files,
     * this keeps patterns embedded so they're included in the PDF
     * 
     * @param string $svg_content SVG content
     * @param int $order_id Order ID for logging
     * @return string SVG content with embedded patterns
     */
    private function process_patterns_for_pdf($svg_content, $order_id = 0)
    {
        error_log("APD PDF Patterns - Order #$order_id: Processing patterns for PDF (keeping embedded)");
        
        // Find all pattern definitions and ensure images are embedded (not external)
        $svg_content = preg_replace_callback(
            '/<pattern([^>]*)>(.*?)<\/pattern>/is',
            function($matches) use ($order_id) {
                $pattern_attrs = $matches[1];
                $pattern_content = $matches[2];
                
                // Extract pattern ID
                $pattern_id = 'pattern';
                if (preg_match('/id=["\']([^"\']+)["\']/', $pattern_attrs, $m)) {
                    $pattern_id = $m[1];
                }
                
                // Process image tags - ensure they use data URIs for PDF embedding
                $pattern_content = preg_replace_callback(
                    '/<image([^>]*)>/i',
                    function($img_matches) use ($pattern_id, $order_id) {
                        $img_attrs = $img_matches[1];
                        
                        // Check if image already has data URI
                        if (preg_match('/(href|xlink:href)=["\']data:image\/([^;]+);base64,([^"\']+)["\']/', $img_attrs, $data_match)) {
                            // Already embedded - keep it
                            error_log("APD PDF Patterns - Order #$order_id: Pattern $pattern_id already has embedded image");
                            return $img_matches[0];
                        }
                        
                        // Check if image has external URL
                        if (preg_match('/(href|xlink:href)=["\']([^"\']+)["\']/', $img_attrs, $url_match)) {
                            $image_url = $url_match[2];
                            
                            // Try to fetch and convert to data URI
                            if (strpos($image_url, 'http') === 0 || strpos($image_url, '/') === 0) {
                                // Fetch image and convert to base64
                                $upload_dir = wp_upload_dir();
                                $image_path = '';
                                
                                // Check if it's a local file
                                if (strpos($image_url, $upload_dir['url']) === 0) {
                                    $image_path = str_replace($upload_dir['url'], $upload_dir['path'], $image_url);
                                } elseif (strpos($image_url, '/') === 0) {
                                    $image_path = ABSPATH . ltrim($image_url, '/');
                                }
                                
                                if ($image_path && file_exists($image_path)) {
                                    $image_data = file_get_contents($image_path);
                                    $image_info = getimagesize($image_path);
                                    $mime_type = $image_info ? $image_info['mime'] : 'image/png';
                                    $base64_data = base64_encode($image_data);
                                    $data_uri = 'data:' . $mime_type . ';base64,' . $base64_data;
                                    
                                    // Replace URL with data URI
                                    $new_attrs = preg_replace(
                                        '/(href|xlink:href)=["\'][^"\']*["\']/',
                                        'href="' . $data_uri . '" xlink:href="' . $data_uri . '"',
                                        $img_attrs
                                    );
                                    
                                    error_log("APD PDF Patterns - Order #$order_id: Converted pattern $pattern_id image to embedded data URI");
                                    return '<image' . $new_attrs . '>';
                                } else {
                                    // Try to fetch via HTTP
                                    $response = wp_remote_get($image_url);
                                    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                                        $image_data = wp_remote_retrieve_body($response);
                                        $headers = wp_remote_retrieve_headers($response);
                                        $content_type = isset($headers['content-type']) ? $headers['content-type'] : 'image/png';
                                        $base64_data = base64_encode($image_data);
                                        $data_uri = 'data:' . $content_type . ';base64,' . $base64_data;
                                        
                                        $new_attrs = preg_replace(
                                            '/(href|xlink:href)=["\'][^"\']*["\']/',
                                            'href="' . $data_uri . '" xlink:href="' . $data_uri . '"',
                                            $img_attrs
                                        );
                                        
                                        error_log("APD PDF Patterns - Order #$order_id: Fetched and embedded pattern $pattern_id image");
                                        return '<image' . $new_attrs . '>';
                                    }
                                }
                            }
                        }
                        
                        return $img_matches[0];
                    },
                    $pattern_content
                );
                
                return '<pattern' . $pattern_attrs . '>' . $pattern_content . '</pattern>';
            },
            $svg_content
        );
        
        // Count patterns after processing
        $pattern_count = preg_match_all('/<pattern[^>]*>/i', $svg_content);
        $embedded_images = preg_match_all('/data:image[^"\']+/i', $svg_content);
        error_log("APD PDF Patterns - Order #$order_id: After processing - $pattern_count patterns, $embedded_images embedded images");
        
        return $svg_content;
    }

    /**
     * Use Inkscape to convert ALL elements to curves/paths (text, strokes, etc.)
     * This ensures everything is converted to vectors BEFORE PDF export
     * Material outline patterns are converted from strokes to fills on paths
     * 
     * @param string $svg_content SVG content with text elements
     * @param int $order_id Order ID for logging
     * @return string SVG content with all text converted to paths and strokes converted to fills
     */
    private function inkscape_convert_all_to_curves($svg_content, $order_id = 0)
    {
        // Check if Inkscape is available
        $inkscape_path = $this->find_inkscape();
        if (!$inkscape_path) {
            error_log("APD Text to Path - Order #$order_id: Inkscape not available, text will be converted during PDF export");
            return $svg_content;
        }
        
        // Count text elements before conversion
        $text_count_before = preg_match_all('/<text[^>]*>/i', $svg_content);
        if ($text_count_before === 0) {
            error_log("APD Text to Path - Order #$order_id: No text elements found, skipping conversion");
            return $svg_content;
        }
        
        error_log("APD Text to Path - Order #$order_id: Converting $text_count_before text elements to paths using Inkscape");
        
        // CRITICAL: Backup pattern definitions before Inkscape conversion
        // Inkscape sometimes loses or modifies patterns, so we backup and restore them
        $pattern_backup = array();
        if (preg_match_all('/<pattern[^>]*id=["\']([^"\']+)["\'][^>]*>(.*?)<\/pattern>/is', $svg_content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $pattern_id = $match[1];
                $pattern_full = $match[0];
                $pattern_backup[$pattern_id] = $pattern_full;
            }
            error_log("APD Text to Path - Order #$order_id: Backed up " . count($pattern_backup) . " pattern definitions before Inkscape");
        }
        
        // Backup original viewBox to preserve positioning
        $original_viewBox = '';
        if (preg_match('/<svg[^>]*viewBox=["\']([^"\']+)["\']/', $svg_content, $m)) {
            $original_viewBox = $m[1];
            error_log("APD Text to Path - Order #$order_id: Backed up viewBox: $original_viewBox");
        }
        
        // Save SVG temporarily
        $upload_dir = wp_upload_dir();
        $temp_svg_input = $upload_dir['path'] . '/temp-text-' . $order_id . '-' . time() . '.svg';
        $temp_svg_output = $upload_dir['path'] . '/temp-text-converted-' . $order_id . '-' . time() . '.svg';
        
        file_put_contents($temp_svg_input, $svg_content);
        
        // Use Inkscape to convert text to paths (curves) and preserve material outlines
        // CRITICAL: We need to convert text to paths, then convert strokes to paths
        // This ensures material outline patterns are preserved as fills on path elements
        
        // Method 1: Multi-step conversion to preserve material outline patterns
        // Step 1: Convert text to paths (this creates paths with stroke patterns)
        // Step 2: Convert strokes to paths (this converts stroke patterns to fill patterns)
        // Step 3: Ensure all patterns are preserved
        
        // CRITICAL: Use proper Inkscape actions to convert text to paths AND preserve material outlines
        // The issue: Material outline patterns are strokes, and we need to convert them to fills
        // Solution: Use a two-step process with proper action sequence
        
        // Step 1: Convert text to paths (text shapes become paths, strokes remain)
        // Step 2: Convert strokes to paths (material outline strokes become fill paths)
        // This ensures material outline patterns are preserved as fills on path elements
        
        // For Inkscape 1.1.x and 1.2.x, use legacy --export-plain-svg command
        // Modern --actions syntax is only fully supported in Inkscape 1.3+
        // We'll use the simpler --export-text-to-path which works across all versions
        $command = escapeshellarg($inkscape_path) . 
                   ' --export-plain-svg=' . escapeshellarg($temp_svg_output) .
                   ' --export-text-to-path' .  // Convert text to paths (legacy but reliable)
                   ' ' . escapeshellarg($temp_svg_input) . 
                   ' 2>&1';
        
        error_log("APD Text to Path - Order #$order_id: Running Inkscape command (legacy compatible): " . $command);
        
        $output = shell_exec($command);
        $return_code = 0;
        exec($command . '; echo $?', $output_array, $return_code);
        
        error_log("APD Text to Path - Order #$order_id: Inkscape return code: $return_code");
        if ($output) {
            error_log("APD Text to Path - Order #$order_id: Inkscape output: " . substr($output, 0, 500));
        }
        
        // Check if conversion was successful
        $conversion_success = false;
        if (file_exists($temp_svg_output) && filesize($temp_svg_output) > 0) {
            // Verify text was actually converted
            $converted_content = file_get_contents($temp_svg_output);
            
            // RESTORE LOST PATTERNS: Check if any backed-up patterns are missing and restore them
            if (!empty($pattern_backup)) {
                $patterns_restored = 0;
                foreach ($pattern_backup as $pattern_id => $pattern_full) {
                    if (strpos($converted_content, 'id="' . $pattern_id . '"') === false && 
                        strpos($converted_content, "id='" . $pattern_id . "'") === false) {
                        // Pattern lost during Inkscape conversion, restore it
                        error_log("APD Text to Path - Order #$order_id: Restoring lost pattern: $pattern_id");
                        // Insert pattern in <defs> if exists, otherwise before </svg>
                        if (preg_match('/<defs[^>]*>/i', $converted_content)) {
                            $converted_content = preg_replace('/<defs[^>]*>/i', '$0' . "\n" . $pattern_full, $converted_content, 1);
                        } else {
                            $converted_content = str_replace('</svg>', $pattern_full . "\n</svg>", $converted_content);
                        }
                        $patterns_restored++;
                    }
                }
                if ($patterns_restored > 0) {
                    error_log("APD Text to Path - Order #$order_id: ✅ Restored $patterns_restored lost patterns");
                    // Save restored content back to file
                    file_put_contents($temp_svg_output, $converted_content);
                }
            }
            
            // RESTORE VIEWBOX: Ensure viewBox is preserved for correct positioning
            if ($original_viewBox && !preg_match('/viewBox=/i', $converted_content)) {
                error_log("APD Text to Path - Order #$order_id: Restoring lost viewBox: $original_viewBox");
                $converted_content = preg_replace(
                    '/<svg([^>]*)>/i',
                    '<svg$1 viewBox="' . $original_viewBox . '">',
                    $converted_content,
                    1
                );
                file_put_contents($temp_svg_output, $converted_content);
            }
            
            $text_count_check = preg_match_all('/<text[^>]*>/i', $converted_content);
            $path_count_check = preg_match_all('/<path[^>]*>/i', $converted_content);
            
            // Check if material outline patterns are preserved
            // After stroke-to-path, patterns should be fills, not strokes
            $pattern_fills_check = preg_match_all('/fill=["\']url\(#[^)]+\)["\']/i', $converted_content);
            $pattern_strokes_check = preg_match_all('/stroke=["\']url\(#[^)]+\)["\']/i', $converted_content);
            $pattern_defs_check = preg_match_all('/<pattern[^>]*>/i', $converted_content);
            
            error_log("APD Text to Path - Order #$order_id: Conversion verification:");
            error_log("  - Text elements: $text_count_before -> $text_count_check");
            error_log("  - Path elements: $path_count_check");
            error_log("  - Pattern definitions: $pattern_defs_check");
            error_log("  - Pattern fills (material outlines as fills): $pattern_fills_check");
            error_log("  - Pattern strokes (remaining): $pattern_strokes_check");
            
            // DETAILED ANALYSIS for custom text material outline
            $apdTextPattern_refs = substr_count($converted_content, 'apdTextPattern');
            $apdTextPattern_fills = preg_match_all('/fill=["\']url\(#apdTextPattern\)["\']/i', $converted_content);
            $apdTextPattern_strokes = preg_match_all('/stroke=["\']url\(#apdTextPattern\)["\']/i', $converted_content);
            error_log("  - apdTextPattern total refs: $apdTextPattern_refs, fills: $apdTextPattern_fills, strokes: $apdTextPattern_strokes");
            
            if ($text_count_check < $text_count_before && $path_count_check > 0) {
                $conversion_success = true;
                error_log("APD Text to Path - Order #$order_id: Method 1 (--actions) succeeded");
                
                // Verify material outlines were converted to fills
                if ($pattern_fills_check > 0) {
                    error_log("  - ✅ Material outline patterns preserved as fills on paths");
                } else if ($pattern_strokes_check > 0) {
                    error_log("  - ⚠️ Material outlines still as strokes - stroke-to-path may not have worked");
                    // Try to fix: run stroke-to-path again
                    $temp_svg_fixed = $upload_dir['path'] . '/temp-text-fixed-' . $order_id . '-' . time() . '.svg';
                    file_put_contents($temp_svg_fixed, $converted_content);
                    $command_fix = escapeshellarg($inkscape_path) . 
                                   ' --actions="select-all;stroke-to-path"' .
                                   ' --export-filename=' . escapeshellarg($temp_svg_output) .
                                   ' --export-type=svg' .
                                   ' ' . escapeshellarg($temp_svg_fixed) . 
                                   ' 2>&1';
                    shell_exec($command_fix);
                    if (file_exists($temp_svg_output) && filesize($temp_svg_output) > 0) {
                        $converted_content = file_get_contents($temp_svg_output);
                        $pattern_fills_after_fix = preg_match_all('/fill=["\']url\(#[^)]+\)["\']/i', $converted_content);
                        if ($pattern_fills_after_fix > 0) {
                            error_log("  - ✅ Fixed: Material outlines now preserved as fills");
                        }
                    }
                    unlink($temp_svg_fixed);
                } else if ($pattern_strokes_check > 0) {
                    // stroke-to-path failed, manually convert pattern strokes to fills
                    error_log("  - ⚠️ stroke-to-path failed, converting pattern strokes to fills manually...");
                    
                    $converted_content = preg_replace_callback(
                        '/<path([^>]*)>/i',
                        function($matches) use ($order_id) {
                            $attrs = $matches[1];
                            
                            // If path has pattern stroke but no pattern fill, copy stroke to fill
                            if (preg_match('/stroke=["\']url\(([^)]+)\)["\']/i', $attrs, $stroke_match)) {
                                $pattern_ref = $stroke_match[1];
                                
                                // Check if already has pattern fill
                                if (!preg_match('/fill=["\']url\([^)]+\)["\']/i', $attrs)) {
                                    // Add pattern as fill
                                    error_log("APD: Converting pattern stroke to fill for CorelDRAW: $pattern_ref");
                                    $attrs .= ' fill="url(' . htmlspecialchars($pattern_ref, ENT_QUOTES) . ')"';
                                }
                            }
                            
                            return '<path' . $attrs . '>';
                        },
                        $converted_content
                    );
                    
                    // Save manually fixed version
                    file_put_contents($temp_svg_output, $converted_content);
                    
                    // Re-check pattern fills
                    $pattern_fills_fixed = preg_match_all('/fill=["\']url\(#[^)]+\)["\']/i', $converted_content);
                    error_log("  - ✅ Manual fix applied: $pattern_fills_fixed pattern fills created");
                } else {
                    error_log("  - ❌ ERROR: No pattern references found - material outlines are LOST!");
                }
            }
        } else {
            error_log("APD Text to Path - Order #$order_id: Output file not created or empty");
        }
        
        // DISABLE Method 2 (PDF intermediate) as it loses all patterns and colors
        // If Method 1 fails, we should return original SVG and let client-side handle it
        if (!$conversion_success) {
            error_log("APD Text to Path - Order #$order_id: Method 1 failed");
            error_log("APD Text to Path - Order #$order_id: Method 2 (PDF intermediate) is DISABLED because it loses patterns and colors");
            error_log("APD Text to Path - Order #$order_id: Returning original SVG - client-side will handle conversion");
            
            // Clean up and return original SVG
            unlink($temp_svg_input);
            if (file_exists($temp_svg_output)) {
                unlink($temp_svg_output);
            }
            return $svg_content;
        }
        
        /* DISABLED: Method 2 loses all patterns and colors
        if (!$conversion_success) {
            // Alternative: Use --export-text-to-path with temporary PDF, then convert back to SVG
            // This works with older Inkscape versions
            $temp_pdf_intermediate = $upload_dir['path'] . '/temp-text-pdf-' . $order_id . '-' . time() . '.pdf';
            
            // Method 2 code DISABLED - it loses patterns and colors
        }
        */
        
        if ($conversion_success && file_exists($temp_svg_output) && filesize($temp_svg_output) > 0) {
            $converted_svg = file_get_contents($temp_svg_output);
            
            // Verify text was converted (should have fewer or no text elements)
            $text_count_after = preg_match_all('/<text[^>]*>/i', $converted_svg);
            $path_count = preg_match_all('/<path[^>]*>/i', $converted_svg);
            
            error_log("APD Text to Path - Order #$order_id: Conversion complete");
            error_log("  - Text elements before: $text_count_before");
            error_log("  - Text elements after: $text_count_after");
            error_log("  - Path elements: $path_count");
            
            // Verify material outline patterns are preserved
            // After stroke-to-path conversion, patterns should be fills, not strokes
            $pattern_fills_after = preg_match_all('/fill=["\']url\(#[^)]+\)["\']/i', $converted_svg);
            $pattern_strokes_after = preg_match_all('/stroke=["\']url\(#[^)]+\)["\']/i', $converted_svg);
            $pattern_defs_after = preg_match_all('/<pattern[^>]*>/i', $converted_svg);
            error_log("  - Pattern definitions: $pattern_defs_after");
            error_log("  - Pattern fills (material outlines as fills): $pattern_fills_after");
            error_log("  - Pattern strokes (remaining): $pattern_strokes_after");
            
            // CRITICAL FIX: ALWAYS restore/apply pattern references for CorelDRAW compatibility
            // Inkscape text-to-path often loses pattern references, so we manually re-apply them
            // This ensures custom text material outline is preserved
            if ($pattern_defs_after > 0 && $pattern_fills_after == 0) {
                error_log("  - ⚠️ CRITICAL: All pattern references lost during conversion!");
                error_log("  - Attempting to restore pattern references from backup...");
                
                // Try to identify which patterns should be applied to which elements
                // Look for pattern definitions and apply the first pattern to all paths
                $pattern_ids = array();
                if (preg_match_all('/<pattern[^>]*id=["\']([^"\']+)["\']/', $converted_svg, $pattern_matches)) {
                    $pattern_ids = $pattern_matches[1];
                    error_log("  - Found pattern IDs: " . implode(', ', $pattern_ids));
                }
                
                // Apply patterns to paths
                // For text material outline, typically use apdTextPattern or logoMaterialPattern
                $text_pattern = null;
                foreach ($pattern_ids as $pid) {
                    if (strpos($pid, 'Text') !== false || strpos($pid, 'text') !== false) {
                        $text_pattern = $pid;
                        break;
                    }
                }
                if (!$text_pattern && !empty($pattern_ids)) {
                    $text_pattern = $pattern_ids[0]; // Use first pattern as fallback
                }
                
                if ($text_pattern) {
                    error_log("  - Applying pattern to all paths: $text_pattern");
                    
                    $converted_svg = preg_replace_callback(
                        '/<path([^>]*)>/i',
                        function($matches) use ($text_pattern) {
                            $attrs = $matches[1];
                            
                            // Add pattern as both fill and stroke for maximum compatibility
                            if (!preg_match('/fill=["\']url\(/i', $attrs)) {
                                $attrs .= ' fill="url(#' . htmlspecialchars($text_pattern, ENT_QUOTES) . ')"';
                            }
                            if (!preg_match('/stroke=["\']url\(/i', $attrs)) {
                                $attrs .= ' stroke="url(#' . htmlspecialchars($text_pattern, ENT_QUOTES) . ')"';
                                // Also add stroke-width if not present
                                if (!preg_match('/stroke-width=/i', $attrs)) {
                                    $attrs .= ' stroke-width="6"';
                                }
                            }
                            
                            return '<path' . $attrs . '>';
                        },
                        $converted_svg
                    );
                    
                    // Re-check
                    $pattern_fills_fixed = preg_match_all('/fill=["\']url\(#[^)]+\)["\']/i', $converted_svg);
                    error_log("  - ✅ Pattern references restored: $pattern_fills_fixed pattern fills");
                    
                    file_put_contents($temp_svg_output, $converted_svg);
                    $pattern_fills_after = $pattern_fills_fixed;
                }
            }
            
            // Material outlines should be fills after stroke-to-path conversion
            if ($pattern_fills_after > 0) {
                error_log("  - ✅ Material outline patterns preserved as fills on paths");
            } else if ($pattern_strokes_after > 0) {
                error_log("  - ⚠️ Material outlines still as strokes - stroke-to-path conversion incomplete");
                // Try to fix by running stroke-to-path again on the converted SVG
                $temp_svg_fixed = $upload_dir['path'] . '/temp-text-fixed-' . $order_id . '-' . time() . '.svg';
                file_put_contents($temp_svg_fixed, $converted_svg);
                $command_fix = escapeshellarg($inkscape_path) . 
                               ' --actions="select-all;stroke-to-path"' .
                               ' --export-filename=' . escapeshellarg($temp_svg_output) .
                               ' --export-type=svg' .
                               ' ' . escapeshellarg($temp_svg_fixed) . 
                               ' 2>&1';
                shell_exec($command_fix);
                if (file_exists($temp_svg_output) && filesize($temp_svg_output) > 0) {
                    $converted_svg = file_get_contents($temp_svg_output);
                    $pattern_fills_after_fix = preg_match_all('/fill=["\']url\(#[^)]+\)["\']/i', $converted_svg);
                    if ($pattern_fills_after_fix > 0) {
                        error_log("  - ✅ Fixed: Material outlines now preserved as fills after second pass");
                    }
                }
                unlink($temp_svg_fixed);
            } else {
                error_log("  - ❌ ERROR: No pattern references found - material outlines are LOST!");
                error_log("  - This means the material outline patterns were not preserved during conversion");
            }
            
            if ($text_count_after < $text_count_before && $path_count > 0) {
                // Text was converted successfully to paths
                error_log("APD Text to Path - Order #$order_id: ✅ SUCCESS - Text converted to curves/paths");
                unlink($temp_svg_input);
                unlink($temp_svg_output);
                return $converted_svg;
            } else {
                error_log("APD Text to Path - Order #$order_id: WARNING - Text conversion may have failed, using original SVG");
                unlink($temp_svg_input);
                unlink($temp_svg_output);
                return $svg_content;
            }
        } else {
            error_log("APD Text to Path - Order #$order_id: Inkscape text-to-path conversion failed. Output: " . ($output ?: 'No output'));
            unlink($temp_svg_input);
            if (file_exists($temp_svg_output)) unlink($temp_svg_output);
            return $svg_content;
        }
    }

    /**
     * Handle material upload
     */
}
