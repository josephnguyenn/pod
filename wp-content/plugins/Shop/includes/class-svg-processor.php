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
     * Cut-ready SVG processor
     * @var APD_SVG_Cut_Ready
     */
    private $cut_ready;

    /**
     * Constructor
     *
     * @param AdvancedProductDesigner $plugin Main plugin instance
     */
    public function __construct($plugin)
    {
        $this->plugin = $plugin;
        $this->cut_ready = new APD_SVG_Cut_Ready();
    }

    /**
     * Register AJAX hooks
     */
    public function init()
    {
        add_action('wp_ajax_apd_process_cut_ready_svg', array($this, 'apd_process_cut_ready_svg'));
        add_action('wp_ajax_apd_export_cdr_vector', array($this, 'apd_export_cdr_vector'));
        add_action('wp_ajax_apd_export_pdf', array($this, 'apd_export_pdf'));
        add_action('wp_ajax_apd_export_pdf_from_svg', array($this, 'apd_export_pdf_from_svg'));
        add_action('wp_ajax_apd_test_vector_pdf', array($this, 'apd_test_vector_pdf'));
        add_action('wp_ajax_apd_test_inkscape', array($this, 'test_inkscape_availability'));
        add_action('wp_ajax_apd_get_order_svg', array($this, 'apd_get_order_svg'));
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
        
        $inkscape_path = APD_SVG_Utils::find_inkscape();
        
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

    /**
     * Test vector PDF generation from a simple SVG - AJAX endpoint for diagnostics
     *
     * Allows admins to confirm that the end-to-end pipeline (make_pdf_compatible_new + svg_to_pdf_new)
     * is producing PDFs that contain vector data rather than a single raster image.
     */
    public function apd_test_vector_pdf()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }

        $inkscape_path = APD_SVG_Utils::find_inkscape();

        // Minimal SVG with text and a rectangle to exercise text-to-curves and shapes
        $test_svg = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="80" viewBox="0 0 200 80">'
            . '<defs>'
            . '  <pattern id="apdTextPattern" patternUnits="userSpaceOnUse" width="20" height="20">'
            . '    <rect width="20" height="20" fill="#cccccc"/>'
            . '    <path d="M0,0 L20,20 M20,0 L0,20" stroke="#888888" stroke-width="2"/>'
            . '  </pattern>'
            . '</defs>'
            . '<rect x="5" y="5" width="190" height="70" rx="10" ry="10" fill="#ffffff" stroke="#000000" stroke-width="2"/>'
            . '<text x="100" y="45" text-anchor="middle" font-family="Arial" font-size="18" '
            . ' fill="url(#apdTextPattern)" stroke="url(#apdTextPattern)" stroke-width="2">Vector Test</text>'
            . '</svg>';

        // Run through the same pipeline the real export uses
        $processed_svg = $this->make_pdf_compatible_new($test_svg, 0);
        if (is_wp_error($processed_svg)) {
            wp_send_json_error(array(
                'message' => $processed_svg->get_error_message(),
                'step' => 'make_pdf_compatible_new',
            ));
            return;
        }

        $pdf_result = $this->svg_to_pdf_new($processed_svg, 0);
        if (is_wp_error($pdf_result)) {
            $data = $pdf_result->get_error_data();
            wp_send_json_error(array(
                'message' => $pdf_result->get_error_message(),
                'step' => 'svg_to_pdf_new',
                'data' => $data,
            ));
            return;
        }

        $pdf_content = $pdf_result;
        $pdf_size = strlen($pdf_content);

        // Heuristic checks for vector content vs purely raster
        $markers = array();
        $contains_vector = false;

        if (strpos($pdf_content, '/Type /XObject') !== false) {
            $markers[] = '/Type /XObject';
        }
        if (strpos($pdf_content, '/Subtype /Form') !== false) {
            $markers[] = '/Subtype /Form';
        }
        if (strpos($pdf_content, '/Path') !== false) {
            $markers[] = '/Path';
        }

        if (!empty($markers)) {
            $contains_vector = true;
        }

        wp_send_json_success(array(
            'pdf_size' => $pdf_size,
            'contains_vector' => $contains_vector,
            'markers' => $markers,
            'inkscape_path' => $inkscape_path,
        ));
    }

    /**
     * Get order SVG content - AJAX endpoint for client-side rasterization
     */
    public function apd_get_order_svg()
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

        error_log("APD Get Order SVG - Order #$order_id: Fetching SVG content for rasterization");

        $result = APD_Order_SVG_Resolver::get_svg_for_order($order_id);
        if (!$result || empty($result['content'])) {
            error_log("APD Get Order SVG - Order #$order_id: ❌ No SVG content found");
            wp_send_json_error(array(
                'message' => 'No SVG content found for this order',
                'order_id' => $order_id
            ));
            return;
        }
        $svg_content = $result['content'];
        $source = $result['source'];

        error_log("APD Get Order SVG - Order #$order_id: ✅ SVG content found from: $source");
        error_log("APD Get Order SVG - Order #$order_id: SVG size: " . strlen($svg_content) . " bytes");

        // CRITICAL: Ensure logo material outline is preserved before rasterization
        // This ensures material outline is present in SVG before client-side rasterization
        $svg_content = $this->ensure_logo_material_outline_for_rasterization($svg_content, $order_id);

        wp_send_json_success(array(
            'svg_content' => $svg_content,
            'source' => $source,
            'order_id' => $order_id
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

        $result = APD_Order_SVG_Resolver::get_svg_for_order($order_id);
        if (!$result || empty($result['content'])) {
            $svg_content = '';
            $source = '';
        } else {
            $svg_content = $result['content'];
            $source = $result['source'];
        }

        error_log(sprintf(
            'APD Cut-Ready SVG - Order #%d: Source=%s, Content Length=%d, First 100 chars=%s',
            $order_id,
            $source ?: 'NONE',
            strlen($svg_content),
            substr($svg_content, 0, 100)
        ));

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
        $clean_svg = $this->cut_ready->make_coreldraw_compatible($svg_content, $order_id);

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
     * Export CDR Vector File: vector SVG (curves, color, material outline) for CorelDRAW.
     * Same vector pipeline as PDF export but outputs SVG file for open-in-CorelDRAW / Save As .cdr.
     */
    public function apd_export_cdr_vector()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }

        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
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
        $source = $result['source'];

        error_log(sprintf(
            'APD CDR Vector Export - Order #%d: Source=%s, Content length=%d',
            $order_id,
            $source ?: 'NONE',
            strlen($svg_content)
        ));

        // Ensure logo material outline is present (same as PDF path)
        $svg_content = $this->ensure_logo_material_outline_for_rasterization($svg_content, $order_id);

        // Same vector pipeline as PDF export (text-to-curves, embedded patterns) but output SVG
        $processed_svg = $this->make_pdf_compatible_new($svg_content, $order_id);
        if (is_wp_error($processed_svg)) {
            wp_send_json_error(array('message' => $processed_svg->get_error_message()));
            return;
        }

        // Strip Inkscape/sodipodi markup so CorelDRAW can open the file (avoids import failures)
        $processed_svg = $this->strip_inkscape_markup_for_coreldraw($processed_svg, $order_id);

        $upload_dir = wp_upload_dir();
        $filename = 'order-' . $order_id . '-design-vector-for-coreldraw-' . time() . '.svg';
        $filepath = $upload_dir['path'] . '/' . $filename;

        // Write UTF-8 without BOM (CorelDRAW expects standard UTF-8)
        $bytes_written = file_put_contents($filepath, $processed_svg, LOCK_EX);
        if ($bytes_written === false) {
            wp_send_json_error(array('message' => 'Failed to save vector SVG file'));
            return;
        }

        error_log(sprintf(
            'APD CDR Vector Export - Order #%d: Saved %d bytes to %s',
            $order_id,
            $bytes_written,
            $filename
        ));

        $file_url = $upload_dir['url'] . '/' . $filename;

        wp_send_json_success(array(
            'file_url' => $file_url,
            'filename' => $filename,
            'message' => 'Vector SVG for CorelDRAW generated. Open in CorelDRAW and use Save As .cdr for native editing.'
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
        // Use NEW refactored function that converts text and logos to curves
        error_log("APD PDF from SVG: Calling make_pdf_compatible_new()...");
        $processed_svg = $this->make_pdf_compatible_new($svg_content, 0);
        
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
        // Use NEW optimized function for CorelDraw compatibility
        error_log("APD PDF from SVG: Calling svg_to_pdf_new()...");
        $pdf_result = $this->svg_to_pdf_new($processed_svg, 0);
        
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

        $override = isset($_POST['svg_override']) ? $_POST['svg_override'] : null;
        if (!empty($override)) {
            $is_rasterized = preg_match('/<image[^>]*href=["\']data:image\/png/', $override);
            if ($is_rasterized) {
                error_log("APD PDF Export - Order #$order_id: ✅ RASTERIZED SVG received from client (Option A)");
                error_log("APD PDF Export - Order #$order_id: Material outline is BAKED IN - perfect for CorelDraw");
            } else {
                error_log("APD PDF Export - Order #$order_id: Vector SVG received from client");
            }
        }

        $result = APD_Order_SVG_Resolver::get_svg_for_order($order_id, $override);
        if (!$result || empty($result['content'])) {
            error_log("APD PDF Export - Order #$order_id: ERROR - No SVG found");
            wp_send_json_error('No SVG found for this order');
            return;
        }
        $svg_content = $result['content'];
        $source = $result['source'];

        error_log("APD PDF Export - Order #$order_id: SVG found from source: $source");
        error_log("APD PDF Export - Order #$order_id: SVG content length: " . strlen($svg_content) . " bytes");
        
        // Log incoming SVG analysis
        $text_count_incoming = preg_match_all('/<text[^>]*>/i', $svg_content);
        $pattern_count_incoming = preg_match_all('/<pattern[^>]*>/i', $svg_content);
        $pattern_refs_incoming = preg_match_all('/stroke=["\']url\(#[^)]+\)["\']/i', $svg_content);
        error_log("APD PDF Export - Order #$order_id: Incoming SVG - $text_count_incoming text elements, $pattern_count_incoming patterns, $pattern_refs_incoming pattern stroke references");

        // Process the SVG to make it PDF-ready (preserves ALL content including patterns)
        // Use NEW refactored function that converts text and logos to curves
        error_log("APD PDF Export - Order #$order_id: Calling make_pdf_compatible_new()...");
        $pdf_svg = $this->make_pdf_compatible_new($svg_content, $order_id);

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
        // Use NEW optimized function for CorelDraw compatibility
        error_log("APD PDF Export - Order #$order_id: Calling svg_to_pdf_new()...");
        $pdf_content = $this->svg_to_pdf_new($pdf_svg, $order_id);

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
        $inkscape_path = APD_SVG_Utils::find_inkscape();
        
        if ($inkscape_path) {
            return $this->convert_svg_to_pdf_with_inkscape_new($svg_content, $width, $height, $order_id, $inkscape_path);
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
     *
     * - First, honor a custom path stored in the DB option `apd_inkscape_path`
     * - Then, scan common system locations and finally rely on PATH lookup
     * - Logs what it checks so admins can debug server configuration
     *
     * @return string|false Resolved Inkscape binary path or false if not found
     */
    private function find_inkscape()
    {
        $paths_checked = array();

        // Allow admins to override the auto-detected path via option
        if (function_exists('get_option')) {
            $custom_path = trim((string) get_option('apd_inkscape_path', ''));
        } else {
            $custom_path = '';
        }

        $possible_paths = array();

        if (!empty($custom_path)) {
            $possible_paths[] = $custom_path;
        }

        // Standard locations + PATH lookup token
        $possible_paths = array_merge(
            $possible_paths,
            array(
                '/usr/bin/inkscape',
                '/usr/local/bin/inkscape',
                '/opt/homebrew/bin/inkscape',
                'inkscape', // In PATH
            )
        );
        
        foreach ($possible_paths as $path) {
            $paths_checked[] = $path;

            // Direct executable path
            if (strpos($path, '/') === 0 && is_executable($path)) {
                error_log("APD Inkscape detection: using executable path: {$path}");
                return $path;
            }

            // Resolve via shell lookup when available (for PATH-based entries)
            if (function_exists('shell_exec')) {
                $resolved = trim((string) @shell_exec('command -v ' . escapeshellarg($path) . ' 2>/dev/null'));
                if (empty($resolved)) {
                    $resolved = trim((string) @shell_exec('which ' . escapeshellarg($path) . ' 2>/dev/null'));
                }

                if (!empty($resolved) && is_executable($resolved)) {
                    error_log("APD Inkscape detection: resolved {$path} to {$resolved} via shell lookup");
                    return $resolved;
                }
            }
        }
        
        error_log('APD Inkscape detection: Inkscape not found. Paths checked: ' . implode(', ', $paths_checked));
        return false;
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
        
        // Verify PNG/JPEG images are embedded
        $png_images = preg_match_all('/data:image\/png;base64/i', $svg_content);
        $jpeg_images = preg_match_all('/data:image\/(jpeg|jpg);base64/i', $svg_content);
        
        error_log("APD PDF Patterns - Order #$order_id: After processing - $pattern_count patterns, $embedded_images embedded images");
        error_log("APD PDF Patterns - Order #$order_id: Pattern image verification:");
        error_log("  - PNG images embedded: $png_images");
        error_log("  - JPEG images embedded: $jpeg_images");
        error_log("  - Total embedded images: $embedded_images");
        
        if ($pattern_count > 0 && $embedded_images === 0) {
            error_log("APD PDF Patterns - Order #$order_id: ⚠️ WARNING - Patterns found but no embedded images detected!");
        } else if ($pattern_count > 0 && $embedded_images > 0) {
            error_log("APD PDF Patterns - Order #$order_id: ✅ Pattern images preserved (PNG/JPEG embedded)");
        }
        
        return $svg_content;
    }

    /**
     * Handle material upload
     */

    /**
     * Make SVG compatible with PDF - NEW VERSION
     * Refactored logic: Convert all text and logos to curves, apply material outline
     * 
     * @param string $svg_content Source SVG content
     * @param int $order_id Order ID for logging
     * @return string|WP_Error Clean SVG content or error
     */
    private function make_pdf_compatible_new($svg_content, $order_id = 0)
    {
        error_log("APD PDF Compatible NEW - Order #$order_id: Starting PDF preparation (refactored)");
        
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
            error_log("APD PDF Compatible NEW - Order #$order_id: Converted from $detected_encoding to UTF-8");
        }
        
        // STEP 2.5: Check if SVG is rasterized (Option A - Full Rasterization)
        // Rasterized SVG contains embedded PNG image with material outline baked in
        $is_rasterized = preg_match('/<image[^>]*href=["\']data:image\/png;base64/', $svg_content);
        
        if ($is_rasterized) {
            error_log("APD PDF Compatible NEW - Order #$order_id: ✅ RASTERIZED SVG detected (Option A)");
            error_log("APD PDF Compatible NEW - Order #$order_id: Material outline is HARDCODED/BAKED IN - perfect for CorelDraw");
            error_log("APD PDF Compatible NEW - Order #$order_id: Skipping all vector processing");
            
            // Validate SVG structure
            if (!preg_match('/<svg[^>]*>/i', $svg_content)) {
                error_log("APD PDF Compatible NEW - Order #$order_id: ⚠️ Invalid SVG structure");
                return new WP_Error('invalid_svg', 'Invalid SVG structure');
            }
            
            // Check metadata
            if (preg_match('/<metadata>([^<]+)<\/metadata>/', $svg_content, $meta_match)) {
                error_log("APD PDF Compatible NEW - Order #$order_id: Metadata: " . $meta_match[1]);
            }
            
            // Count image elements
            $image_count = preg_match_all('/<image[^>]*>/i', $svg_content);
            error_log("APD PDF Compatible NEW - Order #$order_id: Contains $image_count image element(s)");
            
            error_log("APD PDF Compatible NEW - Order #$order_id: ✅ Rasterized SVG ready for PDF - CorelDraw will display perfectly");
            
            // Return as-is - no processing needed for rasterized SVG
            return $svg_content;
        }
        
        // If not rasterized, continue with normal vector processing
        error_log("APD PDF Compatible NEW - Order #$order_id: Vector SVG detected - continuing with normal processing");
        
        // STEP 3: Fix malformed attributes
        $svg_content = preg_replace('/(\w+(-\w+)*)="=""/', '', $svg_content);
        $svg_content = preg_replace('/stroke-linejoin=""\s+round=""/', 'stroke-linejoin="round"', $svg_content);
        $svg_content = preg_replace('/stroke-linejoin=""\s+miter=""/', 'stroke-linejoin="miter"', $svg_content);
        $svg_content = preg_replace('/stroke-linejoin=""\s+bevel=""/', 'stroke-linejoin="bevel"', $svg_content);
        $svg_content = preg_replace('/stroke-linecap=""\s+round=""/', 'stroke-linecap="round"', $svg_content);
        $svg_content = preg_replace('/stroke-linecap=""\s+square=""/', 'stroke-linecap="square"', $svg_content);
        $svg_content = preg_replace('/stroke-linecap=""\s+butt=""/', 'stroke-linecap="butt"', $svg_content);
        $svg_content = preg_replace('/vector-effect=""\s+non-scaling-stroke=""/', 'vector-effect="non-scaling-stroke"', $svg_content);
        
        // STEP 4: Duplicate styles as attributes for PDF compatibility
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
        
        // STEP 5: Verify initial pattern state
        $pattern_defs_initial = preg_match_all('/<pattern[^>]*>/i', $svg_content);
        $pattern_images_initial = preg_match_all('/<pattern[^>]*>.*?<image[^>]*data:image\/(png|jpeg|jpg);base64[^>]*>.*?<\/pattern>/is', $svg_content);
        
        error_log("APD PDF Compatible NEW - Order #$order_id: Initial pattern state:");
        error_log("  - Pattern definitions: $pattern_defs_initial");
        error_log("  - Patterns with embedded PNG/JPEG images: $pattern_images_initial");
        
        // STEP 5: Process patterns for PDF - embed as data URIs
        $svg_content = $this->process_patterns_for_pdf($svg_content, $order_id);
        
        // STEP 5.5: Process pattern images for PDF - ensure PNG/JPEG images are embedded
        error_log("APD PDF Compatible NEW - Order #$order_id: Processing pattern images for PDF...");
        try {
            $svg_content = $this->process_pattern_images_for_pdf($svg_content, $order_id);
        } catch (Exception $e) {
            error_log("APD PDF Compatible NEW - Order #$order_id: ⚠️ Exception in process_pattern_images_for_pdf: " . $e->getMessage());
            // Continue without processing pattern images
        }
        
        // Verify patterns after processing
        $pattern_defs_after_processing = preg_match_all('/<pattern[^>]*>/i', $svg_content);
        $pattern_images_after_processing = preg_match_all('/<pattern[^>]*>.*?<image[^>]*data:image\/(png|jpeg|jpg);base64[^>]*>.*?<\/pattern>/is', $svg_content);
        
        // Also verify pattern references (fills and strokes)
        $pattern_fills_after_processing = preg_match_all('/fill=["\']url\(#[^)]+\)["\']/i', $svg_content);
        $pattern_strokes_after_processing = preg_match_all('/stroke=["\']url\(#[^)]+\)["\']/i', $svg_content);
        
        error_log("APD PDF Compatible NEW - Order #$order_id: Pattern state after processing:");
        error_log("  - Pattern definitions: $pattern_defs_after_processing (was $pattern_defs_initial)");
        error_log("  - Patterns with embedded PNG/JPEG images: $pattern_images_after_processing (was $pattern_images_initial)");
        error_log("  - Pattern fills: $pattern_fills_after_processing");
        error_log("  - Pattern strokes: $pattern_strokes_after_processing");
        
        if ($pattern_defs_after_processing < $pattern_defs_initial) {
            error_log("APD PDF Compatible NEW - Order #$order_id: ⚠️ WARNING - Pattern definitions lost during processing!");
        } else if ($pattern_images_after_processing < $pattern_images_initial) {
            error_log("APD PDF Compatible NEW - Order #$order_id: ⚠️ WARNING - Pattern images lost during processing!");
        } else if ($pattern_images_after_processing >= $pattern_images_initial && $pattern_defs_after_processing >= $pattern_defs_initial) {
            error_log("APD PDF Compatible NEW - Order #$order_id: ✅ Pattern definitions and PNG/JPEG images preserved/embedded");
        }
        
        // CRITICAL: Verify that material outline patterns (apdTextPattern, logoMaterialPattern) are preserved
        $apd_text_pattern_def = preg_match_all('/<pattern[^>]*id=["\']apdTextPattern["\'][^>]*>/i', $svg_content);
        $logo_material_pattern_def = preg_match_all('/<pattern[^>]*id=["\']logoMaterialPattern["\'][^>]*>/i', $svg_content);
        $apd_text_pattern_refs = preg_match_all('/url\(#apdTextPattern\)/i', $svg_content);
        $logo_material_pattern_refs = preg_match_all('/url\(#logoMaterialPattern\)/i', $svg_content);
        
        error_log("APD PDF Compatible NEW - Order #$order_id: Material outline pattern verification:");
        error_log("  - apdTextPattern definition: " . ($apd_text_pattern_def > 0 ? "✅ YES" : "❌ MISSING"));
        error_log("  - apdTextPattern references: $apd_text_pattern_refs");
        error_log("  - logoMaterialPattern definition: " . ($logo_material_pattern_def > 0 ? "✅ YES" : "❌ MISSING"));
        error_log("  - logoMaterialPattern references: $logo_material_pattern_refs");
        
        if ($apd_text_pattern_refs > 0 && $apd_text_pattern_def === 0) {
            error_log("APD PDF Compatible NEW - Order #$order_id: ⚠️ CRITICAL - apdTextPattern references found but definition missing!");
        }
        if ($logo_material_pattern_refs > 0 && $logo_material_pattern_def === 0) {
            error_log("APD PDF Compatible NEW - Order #$order_id: ⚠️ CRITICAL - logoMaterialPattern references found but definition missing!");
        }
        
        // STEP 6: Verify pattern preservation before text-to-path conversion
        $pattern_defs_before_text_to_path = preg_match_all('/<pattern[^>]*>/i', $svg_content);
        $pattern_images_before_text_to_path = preg_match_all('/<pattern[^>]*>.*?<image[^>]*data:image\/(png|jpeg|jpg);base64[^>]*>.*?<\/pattern>/is', $svg_content);
        
        // Backup pattern definitions with PNG/JPEG images for restoration if needed
        $pattern_backup_for_restore = array();
        if (preg_match_all('/<pattern([^>]*id=["\']([^"\']+)["\'][^>]*)>(.*?)<\/pattern>/is', $svg_content, $pattern_matches, PREG_SET_ORDER)) {
            foreach ($pattern_matches as $match) {
                $pattern_id = $match[2];
                $pattern_full = $match[0];
                // Only backup patterns with embedded images
                if (preg_match('/data:image\/(png|jpeg|jpg);base64/i', $pattern_full)) {
                    $pattern_backup_for_restore[$pattern_id] = $pattern_full;
                }
            }
        }
        
        error_log("APD PDF Compatible NEW - Order #$order_id: Pattern preservation before text-to-path:");
        error_log("  - Pattern definitions: $pattern_defs_before_text_to_path");
        error_log("  - Patterns with embedded PNG/JPEG images: $pattern_images_before_text_to_path");
        error_log("  - Patterns backed up for restoration: " . count($pattern_backup_for_restore));
        
        // STEP 6: Convert ALL text and logos to curves (paths)
        error_log("APD PDF Compatible NEW - Order #$order_id: Converting text and logos to curves...");
        $original_svg_for_fallback = $svg_content; // Backup for fallback
        try {
            $converted_result = $this->convert_all_to_curves_new($svg_content, $order_id);
            // convert_all_to_curves_new returns string, not WP_Error
            if (is_string($converted_result)) {
                $svg_content = $converted_result;
            } else {
                // If somehow we get something else, use original
                error_log("APD PDF Compatible NEW - Order #$order_id: ⚠️ Unexpected result from convert_all_to_curves_new");
                $svg_content = $original_svg_for_fallback;
            }
        } catch (Exception $e) {
            error_log("APD PDF Compatible NEW - Order #$order_id: ⚠️ Exception in convert_all_to_curves_new: " . $e->getMessage());
            // Continue with original SVG
            $svg_content = $original_svg_for_fallback;
        }
        
        // STEP 6.5: Preserve custom text pattern fills BEFORE applying material outline
        // This ensures custom text material outline is not lost during material outline processing
        error_log("APD PDF Compatible NEW - Order #$order_id: Preserving custom text pattern fills before material outline...");
        $custom_text_pattern_fills_before = preg_match_all('/fill=["\']url\(#apdTextPattern\)["\']/i', $svg_content);
        $custom_text_pattern_strokes_before = preg_match_all('/stroke=["\']url\(#apdTextPattern\)["\']/i', $svg_content);
        error_log("APD PDF Compatible NEW - Order #$order_id: Custom text pattern fills before material outline: $custom_text_pattern_fills_before");
        error_log("APD PDF Compatible NEW - Order #$order_id: Custom text pattern strokes before material outline: $custom_text_pattern_strokes_before");
        
        // CRITICAL: Add pattern STROKES to custom text paths if they only have fills
        // This ensures custom text paths can be converted via stroke-to-path like logo
        // CRITICAL: Preserve stroke-width từ original text để material outline đúng thickness
        if ($custom_text_pattern_fills_before > 0 && $custom_text_pattern_strokes_before === 0) {
            error_log("APD PDF Compatible NEW - Order #$order_id: ⚠️ Custom text paths have fills but no strokes - adding strokes for stroke-to-path conversion...");
            
            // Try to extract stroke-width from original SVG (before conversion)
            // This preserves material outline thickness từ viền ngoài đã chọn
            $original_stroke_width = null;
            if (preg_match_all('/<text([^>]*stroke=["\']url\(#apdTextPattern\)["\'][^>]*)>/i', $original_svg_for_fallback, $original_text_matches, PREG_SET_ORDER)) {
                foreach ($original_text_matches as $match) {
                    $attrs = $match[1];
                    if (preg_match('/stroke-width=["\']([^"\']+)["\']/', $attrs, $sw_match)) {
                        $original_stroke_width = $sw_match[1];
                        error_log("APD PDF Compatible NEW - Order #$order_id: Found stroke-width from original text: $original_stroke_width");
                        break; // Use first found stroke-width
                    }
                }
            }
            
            // Fallback to default if not found
            if (!$original_stroke_width) {
                $original_stroke_width = '48'; // Default fallback
                error_log("APD PDF Compatible NEW - Order #$order_id: Using default stroke-width: $original_stroke_width");
            }
            
            $paths_with_strokes_added = 0;
            $svg_content = preg_replace_callback(
                '/<path([^>]*fill=["\']url\(#apdTextPattern\)["\'][^>]*)>/i',
                function($matches) use (&$paths_with_strokes_added, $original_stroke_width, $order_id) {
                    $attrs = $matches[1];
                    
                    // Skip if already has pattern stroke
                    if (preg_match('/stroke=["\']url\(#apdTextPattern\)["\']/i', $attrs)) {
                        return $matches[0];
                    }
                    
                    // Add pattern stroke for stroke-to-path conversion
                    $new_attrs = $attrs;
                    $new_attrs .= ' stroke="url(#apdTextPattern)"';
                    
                    // CRITICAL: Preserve stroke-width từ original text để material outline đúng thickness
                    // Material outline từ viền ngoài được lấy từ stroke-width đã chọn trong original text
                    if (!preg_match('/stroke-width=/i', $attrs)) {
                        $new_attrs .= ' stroke-width="' . htmlspecialchars($original_stroke_width, ENT_QUOTES) . '"';
                    }
                    
                    // Add stroke attributes for proper stroke-to-path conversion
                    if (!preg_match('/stroke-linejoin=/i', $attrs)) {
                        $new_attrs .= ' stroke-linejoin="round"';
                    }
                    if (!preg_match('/stroke-linecap=/i', $attrs)) {
                        $new_attrs .= ' stroke-linecap="round"';
                    }
                    if (!preg_match('/paint-order=/i', $attrs)) {
                        $new_attrs .= ' paint-order="stroke fill"';
                    }
                    
                    $paths_with_strokes_added++;
                    error_log("APD PDF Compatible NEW - Order #$order_id: Added pattern stroke with stroke-width=$original_stroke_width to custom text path #$paths_with_strokes_added");
                    
                    return '<path' . $new_attrs . '>';
                },
                $svg_content
            );
            
            error_log("APD PDF Compatible NEW - Order #$order_id: Added pattern strokes to $paths_with_strokes_added custom text paths");
            
            // Verify strokes were added
            $custom_text_strokes_after = preg_match_all('/stroke=["\']url\(#apdTextPattern\)["\']/i', $svg_content);
            if ($custom_text_strokes_after > 0) {
                error_log("APD PDF Compatible NEW - Order #$order_id: ✅ Custom text paths now have pattern strokes - will be converted via stroke-to-path");
            }
        }
        
        // Backup paths with apdTextPattern fills
        $custom_text_paths_backup = array();
        if (preg_match_all('/<path([^>]*fill=["\']url\(#apdTextPattern\)["\'][^>]*)>/i', $svg_content, $custom_path_matches, PREG_SET_ORDER)) {
            foreach ($custom_path_matches as $idx => $match) {
                $custom_text_paths_backup[] = $match[0];
            }
            error_log("APD PDF Compatible NEW - Order #$order_id: Backed up " . count($custom_text_paths_backup) . " paths with apdTextPattern fills");
        }
        
        // STEP 7: Apply material outline to curves
        error_log("APD PDF Compatible NEW - Order #$order_id: Applying material outline to curves...");
        
        // Verify custom text paths have pattern strokes before applying material outline
        $custom_text_strokes_before = preg_match_all('/<path([^>]*stroke=["\']url\(#apdTextPattern\)["\'][^>]*)>/i', $svg_content);
        $logo_strokes_before = preg_match_all('/<path([^>]*stroke=["\']url\(#logoMaterialPattern\)["\'][^>]*)>/i', $svg_content);
        error_log("APD PDF Compatible NEW - Order #$order_id: Before material outline:");
        error_log("  - Custom text paths with apdTextPattern stroke: $custom_text_strokes_before");
        error_log("  - Logo paths with logoMaterialPattern stroke: $logo_strokes_before");
        
        if ($custom_text_strokes_before > 0) {
            error_log("APD PDF Compatible NEW - Order #$order_id: ✅ Custom text paths have pattern strokes - will be converted via stroke-to-path");
        } else {
            error_log("APD PDF Compatible NEW - Order #$order_id: ⚠️ WARNING - Custom text paths don't have pattern strokes - may not get material outline");
        }
        
        $svg_before_outline = $svg_content; // Backup
        try {
            $outline_result = $this->apply_material_outline_to_curves($svg_content, $order_id);
            // apply_material_outline_to_curves returns string, not WP_Error
            if (is_string($outline_result)) {
                $svg_content = $outline_result;
            } else {
                error_log("APD PDF Compatible NEW - Order #$order_id: ⚠️ Unexpected result from apply_material_outline_to_curves");
                $svg_content = $svg_before_outline;
            }
        } catch (Exception $e) {
            error_log("APD PDF Compatible NEW - Order #$order_id: ⚠️ Exception in apply_material_outline_to_curves: " . $e->getMessage());
            // Continue without material outline
            $svg_content = $svg_before_outline;
        }
        
        // STEP 7.5: Restore custom text pattern fills if they were lost
        $custom_text_pattern_fills_after = preg_match_all('/fill=["\']url\(#apdTextPattern\)["\']/i', $svg_content);
        $custom_text_pattern_strokes_after = preg_match_all('/stroke=["\']url\(#apdTextPattern\)["\']/i', $svg_content);
        error_log("APD PDF Compatible NEW - Order #$order_id: Custom text pattern fills after material outline: $custom_text_pattern_fills_after");
        error_log("APD PDF Compatible NEW - Order #$order_id: Custom text pattern strokes after material outline: $custom_text_pattern_strokes_after");
        
        // Check if custom text was successfully converted via stroke-to-path
        // After stroke-to-path, strokes should be converted to fills on expanded paths
        // So we should have MORE fills than before (expanded outline paths + original fill paths)
        $custom_text_paths_after = preg_match_all('/<path([^>]*)/i', $svg_content);
        $custom_text_paths_with_apd_pattern = preg_match_all('/<path([^>]*(?:fill|stroke)=["\']url\(#apdTextPattern\)["\'][^>]*)/i', $svg_content);
        
        error_log("APD PDF Compatible NEW - Order #$order_id: Custom text paths analysis:");
        error_log("  - Total paths: $custom_text_paths_after");
        error_log("  - Paths with apdTextPattern (fill or stroke): $custom_text_paths_with_apd_pattern");
        
        // If custom text was converted via stroke-to-path, we should have expanded paths
        // Don't restore if we already have paths with apdTextPattern (they were converted)
        if ($custom_text_pattern_fills_before > 0 && $custom_text_pattern_fills_after === 0 && $custom_text_paths_with_apd_pattern === 0) {
            error_log("APD PDF Compatible NEW - Order #$order_id: ⚠️ Custom text pattern fills were lost, attempting to restore...");
            
            // Try to restore by finding paths that might be from custom text and applying apdTextPattern
            // Strategy: Find paths without pattern fills that are likely from custom text
            $paths_restored = 0;
            $svg_content = preg_replace_callback(
                '/<path([^>]*)>/i',
                function($matches) use (&$paths_restored, $order_id) {
                    $attrs = $matches[1];
                    
                    // Skip if already has pattern fill
                    if (preg_match('/fill=["\']url\(#[^)]+\)["\']/i', $attrs)) {
                        return $matches[0];
                    }
                    
                    // Skip if has mask (already processed)
                    if (preg_match('/mask=/i', $attrs)) {
                        return $matches[0];
                    }
                    
                    // Apply apdTextPattern to paths without fills (likely from custom text)
                    // CRITICAL: Add BOTH pattern stroke AND fill
                    // Stroke is needed for stroke-to-path conversion to create expanded outline paths
                    // Fill is needed for the actual pattern display
                    // Only apply to first few paths to avoid over-applying
                    if ($paths_restored < 5) {
                        $new_attrs = $attrs;
                        
                        // Add pattern fill if not present
                        if (!preg_match('/fill=["\']url\(#[^)]+\)["\']/i', $attrs)) {
                            $new_attrs .= ' fill="url(#apdTextPattern)"';
                        } else {
                            // Replace existing fill with apdTextPattern
                            $new_attrs = preg_replace('/fill=["\'][^"\']*["\']/', 'fill="url(#apdTextPattern)"', $new_attrs);
                        }
                        
                        // CRITICAL: Add pattern STROKE for stroke-to-path conversion
                        // This ensures custom text paths get expanded outline paths like logo
                        if (!preg_match('/stroke=["\']url\(#[^)]+\)["\']/i', $attrs)) {
                            $new_attrs .= ' stroke="url(#apdTextPattern)"';
                            // Add stroke-width for material outline thickness
                            // Use larger stroke-width for more visible outline in CorelDraw (mask-based approach)
                            if (!preg_match('/stroke-width=/i', $attrs)) {
                                $new_attrs .= ' stroke-width="48"'; // Increased from 24 to 48 for more visible outline
                            }
                            // Add stroke attributes for proper rendering
                            if (!preg_match('/stroke-linejoin=/i', $attrs)) {
                                $new_attrs .= ' stroke-linejoin="round"';
                            }
                            if (!preg_match('/stroke-linecap=/i', $attrs)) {
                                $new_attrs .= ' stroke-linecap="round"';
                            }
                            if (!preg_match('/paint-order=/i', $attrs)) {
                                $new_attrs .= ' paint-order="stroke fill"';
                            }
                        }
                        
                        $paths_restored++;
                        error_log("APD PDF Compatible NEW - Order #$order_id: Restored apdTextPattern fill AND stroke to path #$paths_restored (for stroke-to-path conversion)");
                        return '<path' . $new_attrs . '>';
                    }
                    
                    return $matches[0];
                },
                $svg_content,
                10 // Limit to first 10 matches
            );
            
            error_log("APD PDF Compatible NEW - Order #$order_id: Restored apdTextPattern to $paths_restored paths");
            
            // Verify custom text paths now have pattern strokes for stroke-to-path conversion
            $custom_text_strokes_after_restore = preg_match_all('/<path([^>]*stroke=["\']url\(#apdTextPattern\)["\'][^>]*)>/i', $svg_content);
            error_log("APD PDF Compatible NEW - Order #$order_id: Custom text paths with apdTextPattern stroke: $custom_text_strokes_after_restore");
            
            if ($custom_text_strokes_after_restore > 0) {
                error_log("APD PDF Compatible NEW - Order #$order_id: ✅ Custom text paths now have pattern strokes - will be converted via stroke-to-path");
            } else {
                error_log("APD PDF Compatible NEW - Order #$order_id: ⚠️ WARNING - Custom text paths may not have pattern strokes");
            }
            
            // STEP 7.5.1: Apply material outline AGAIN for restored custom text paths with strokes
            // This ensures custom text paths get stroke-to-path conversion like logo
            if ($paths_restored > 0 && $custom_text_strokes_after_restore > 0) {
                error_log("APD PDF Compatible NEW - Order #$order_id: Applying material outline (stroke-to-path) for restored custom text paths...");
                try {
                    // Apply material outline processing again to convert custom text strokes to expanded paths
                    // This creates expanded outline paths with pattern fills (material outline effect)
                    $svg_content = $this->apply_material_outline_to_curves($svg_content, $order_id);
                    
                    // Verify custom text was converted
                    $custom_text_fills_after_outline = preg_match_all('/fill=["\']url\(#apdTextPattern\)["\']/i', $svg_content);
                    $custom_text_strokes_after_outline = preg_match_all('/stroke=["\']url\(#apdTextPattern\)["\']/i', $svg_content);
                    
                    error_log("APD PDF Compatible NEW - Order #$order_id: After material outline for restored custom text:");
                    error_log("  - Custom text pattern fills: $custom_text_fills_after_outline");
                    error_log("  - Custom text pattern strokes: $custom_text_strokes_after_outline");
                    
                    if ($custom_text_fills_after_outline > $custom_text_pattern_fills_after) {
                        error_log("APD PDF Compatible NEW - Order #$order_id: ✅ Custom text paths converted via stroke-to-path (expanded outline paths created)");
                    } else {
                        error_log("APD PDF Compatible NEW - Order #$order_id: ⚠️ WARNING - Custom text stroke-to-path conversion may have failed");
                    }
                } catch (Exception $e) {
                    error_log("APD PDF Compatible NEW - Order #$order_id: ⚠️ Exception applying material outline to restored custom text: " . $e->getMessage());
                }
            }
            
            // STEP 7.5.2: Apply mask creation for restored custom text paths
            // This ensures custom text paths get masks just like logo paths
            if ($paths_restored > 0) {
                error_log("APD PDF Compatible NEW - Order #$order_id: Applying mask creation for restored custom text paths...");
                try {
                    // Apply pattern masks to restored custom text paths
                    // This ensures custom text material outline works in CorelDraw just like logo
                    $svg_content = $this->apply_pattern_mask_to_paths($svg_content, $order_id);
                    
                    // Verify masks were created for custom text paths
                    $custom_text_masks = preg_match_all('/<mask[^>]*>/i', $svg_content);
                    $custom_text_mask_refs = preg_match_all('/mask=["\']url\(#[^)]+\)["\']/i', $svg_content);
                    $custom_text_pattern_fills_with_mask = preg_match_all('/<path([^>]*fill=["\']url\(#apdTextPattern\)["\'][^>]*mask=["\']url\(#[^)]+\)["\'][^>]*)>/i', $svg_content);
                    
                    error_log("APD PDF Compatible NEW - Order #$order_id: After mask creation for custom text:");
                    error_log("  - Total masks: $custom_text_masks");
                    error_log("  - Total mask references: $custom_text_mask_refs");
                    error_log("  - Custom text paths with apdTextPattern and mask: $custom_text_pattern_fills_with_mask");
                    
                    if ($custom_text_pattern_fills_with_mask > 0) {
                        error_log("APD PDF Compatible NEW - Order #$order_id: ✅ Custom text paths now have masks (consistent with logo)");
                    } else {
                        error_log("APD PDF Compatible NEW - Order #$order_id: ⚠️ WARNING - Custom text paths may not have masks");
                    }
                } catch (Exception $e) {
                    error_log("APD PDF Compatible NEW - Order #$order_id: ⚠️ Exception applying masks to custom text paths: " . $e->getMessage());
                }
            }
        }
        
        // STEP 8: Verify conversion
        $text_count_final = preg_match_all('/<text[^>]*>/i', $svg_content);
        $image_count_final = preg_match_all('/<image[^>]*>/i', $svg_content);
        $path_count_final = preg_match_all('/<path[^>]*>/i', $svg_content);
        $pattern_fills_final = preg_match_all('/fill=["\']url\(#[^)]+\)["\']/i', $svg_content);
        $pattern_defs_final = preg_match_all('/<pattern[^>]*>/i', $svg_content);
        $masks_final = preg_match_all('/<mask[^>]*>/i', $svg_content);
        $mask_refs_final = preg_match_all('/mask=["\']url\(#[^)]+\)["\']/i', $svg_content);
        
        // Verify custom text patterns
        $apd_text_pattern_final = preg_match_all('/apdTextPattern/i', $svg_content);
        $apd_text_pattern_fills = preg_match_all('/fill=["\']url\(#apdTextPattern\)["\']/i', $svg_content);
        $apd_text_pattern_fills_with_mask = preg_match_all('/<path([^>]*fill=["\']url\(#apdTextPattern\)["\'][^>]*mask=["\']url\(#[^)]+\)["\'][^>]*)>/i', $svg_content);
        $text_material_pattern_final = preg_match_all('/text-material-pattern-/i', $svg_content);
        
        // Verify logo patterns for comparison
        $logo_material_pattern_fills = preg_match_all('/fill=["\']url\(#logoMaterialPattern\)["\']/i', $svg_content);
        $logo_material_pattern_fills_with_mask = preg_match_all('/<path([^>]*fill=["\']url\(#logoMaterialPattern\)["\'][^>]*mask=["\']url\(#[^)]+\)["\'][^>]*)>/i', $svg_content);
        
        // Verify final pattern preservation
        $pattern_images_final = preg_match_all('/<pattern[^>]*>.*?<image[^>]*data:image\/(png|jpeg|jpg);base64[^>]*>.*?<\/pattern>/is', $svg_content);
        $png_images_final = preg_match_all('/data:image\/png;base64/i', $svg_content);
        $jpeg_images_final = preg_match_all('/data:image\/(jpeg|jpg);base64/i', $svg_content);
        
        error_log("APD PDF Compatible NEW - Order #$order_id: Conversion verification:");
        error_log("  - Text elements remaining: $text_count_final (should be 0)");
        error_log("  - Image elements remaining: $image_count_final");
        error_log("  - Path elements: $path_count_final");
        error_log("  - Pattern definitions: $pattern_defs_final");
        error_log("  - Pattern fills (material outlines): $pattern_fills_final");
        error_log("  - Masks created (CorelDraw compatible): $masks_final");
        error_log("  - Mask references: $mask_refs_final");
        error_log("  - Custom text patterns (apdTextPattern): $apd_text_pattern_final");
        error_log("  - Custom text pattern fills (apdTextPattern): $apd_text_pattern_fills");
        error_log("  - Custom text pattern fills WITH mask: $apd_text_pattern_fills_with_mask");
        error_log("  - Logo pattern fills (logoMaterialPattern): $logo_material_pattern_fills");
        error_log("  - Logo pattern fills WITH mask: $logo_material_pattern_fills_with_mask");
        error_log("  - Custom text patterns (text-material-pattern-*): $text_material_pattern_final");
        error_log("APD PDF Compatible NEW - Order #$order_id: Final pattern preservation verification:");
        error_log("  - Pattern definitions: $pattern_defs_final");
        error_log("  - Patterns with embedded PNG/JPEG images: $pattern_images_final");
        error_log("  - PNG images embedded: $png_images_final");
        error_log("  - JPEG images embedded: $jpeg_images_final");
        
        if ($pattern_defs_final === 0) {
            error_log("APD PDF Compatible NEW - Order #$order_id: ⚠️ CRITICAL WARNING - No pattern definitions in final SVG!");
        } else if ($pattern_images_final === 0) {
            error_log("APD PDF Compatible NEW - Order #$order_id: ⚠️ CRITICAL WARNING - No embedded PNG/JPEG images in patterns!");
        } else if ($pattern_images_final < $pattern_defs_final) {
            error_log("APD PDF Compatible NEW - Order #$order_id: ⚠️ WARNING - Some patterns may not have embedded images!");
        } else {
            error_log("APD PDF Compatible NEW - Order #$order_id: ✅ All pattern definitions have embedded PNG/JPEG images (preserved for CorelDraw)");
        }
        
        // Consistency check: Custom text và logo should have similar mask coverage
        if ($apd_text_pattern_fills > 0 && $logo_material_pattern_fills > 0) {
            $custom_text_mask_ratio = $apd_text_pattern_fills > 0 ? ($apd_text_pattern_fills_with_mask / $apd_text_pattern_fills) * 100 : 0;
            $logo_mask_ratio = $logo_material_pattern_fills > 0 ? ($logo_material_pattern_fills_with_mask / $logo_material_pattern_fills) * 100 : 0;
            
            error_log("APD PDF Compatible NEW - Order #$order_id: Consistency check:");
            error_log("  - Custom text mask coverage: " . number_format($custom_text_mask_ratio, 1) . "% ($apd_text_pattern_fills_with_mask/$apd_text_pattern_fills)");
            error_log("  - Logo mask coverage: " . number_format($logo_mask_ratio, 1) . "% ($logo_material_pattern_fills_with_mask/$logo_material_pattern_fills)");
            
            if ($custom_text_mask_ratio >= 80 && $logo_mask_ratio >= 80) {
                error_log("APD PDF Compatible NEW - Order #$order_id: ✅ Custom text and logo have consistent mask coverage");
            } else if ($custom_text_mask_ratio < $logo_mask_ratio) {
                error_log("APD PDF Compatible NEW - Order #$order_id: ⚠️ WARNING - Custom text has lower mask coverage than logo");
            }
        } else if ($apd_text_pattern_fills > 0 && $apd_text_pattern_fills_with_mask === 0) {
            error_log("APD PDF Compatible NEW - Order #$order_id: ⚠️ WARNING - Custom text has pattern fills but no masks");
        }
        
        // STEP 9: Ensure proper XML declaration with UTF-8
        $svg_content = preg_replace('/<\?xml[^?]*\?>\s*/i', '', $svg_content);
        $svg_content = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>' . "\n" . $svg_content;
        
        // STEP 10: Add metadata about processing
        if (preg_match('/<svg[^>]*>/i', $svg_content, $svg_match)) {
            $svg_tag = $svg_match[0];
            $metadata = "\n  <metadata>Vector PDF export from Order #$order_id on " . date('Y-m-d H:i:s') . ". " .
                       "All text and logos converted to curves/paths. Material outline patterns preserved as fills. " .
                       "Style and layout preserved for CorelDRAW compatibility.</metadata>\n";
            $svg_content = str_replace($svg_tag, $svg_tag . $metadata, $svg_content);
        }
        
        // STEP 11: Validate UTF-8
        if (!mb_check_encoding($svg_content, 'UTF-8')) {
            $svg_content = mb_convert_encoding($svg_content, 'UTF-8', 'UTF-8');
        }
        
        error_log("APD PDF Compatible NEW - Order #$order_id: ✅ SVG prepared for PDF export");
        
        return $svg_content;
    }

    /**
     * Convert all text and logos to curves (paths) - NEW VERSION
     * Handles both text elements and image/logo elements
     * 
     * @param string $svg_content SVG content
     * @param int $order_id Order ID for logging
     * @return string SVG content with text and logos converted to paths
     */
    private function convert_all_to_curves_new($svg_content, $order_id = 0)
    {
        error_log("APD Convert All to Curves NEW - Order #$order_id: Starting conversion");
        
        // Count elements before conversion
        $text_count_before = preg_match_all('/<text[^>]*>/i', $svg_content);
        $image_count_before = preg_match_all('/<image[^>]*>/i', $svg_content);
        $circle_count_before = preg_match_all('/<circle[^>]*>/i', $svg_content);
        $rect_count_before = preg_match_all('/<rect[^>]*>/i', $svg_content);
        $ellipse_count_before = preg_match_all('/<ellipse[^>]*>/i', $svg_content);
        $polygon_count_before = preg_match_all('/<polygon[^>]*>/i', $svg_content);
        $polyline_count_before = preg_match_all('/<polyline[^>]*>/i', $svg_content);
        $line_count_before = preg_match_all('/<line[^>]*>/i', $svg_content);
        
        $shapes_count_before = $circle_count_before + $rect_count_before + $ellipse_count_before + $polygon_count_before + $polyline_count_before + $line_count_before;
        
        error_log("APD Convert All to Curves NEW - Order #$order_id: Found $text_count_before text elements, $image_count_before image elements, $shapes_count_before shape elements (circles: $circle_count_before, rects: $rect_count_before, ellipses: $ellipse_count_before, polygons: $polygon_count_before, polylines: $polyline_count_before, lines: $line_count_before)");
        
        // Check if Inkscape is available
        $inkscape_path = APD_SVG_Utils::find_inkscape();
        if (!$inkscape_path) {
            error_log("APD Convert All to Curves NEW - Order #$order_id: ⚠️ Inkscape not available, using fallback conversion");
            // Fallback: Try to convert images to paths manually (SVG only)
            // Text will remain as text (will be converted during PDF export if possible)
            try {
                $svg_content = $this->convert_images_to_paths($svg_content, $order_id);
            } catch (Exception $e) {
                error_log("APD Convert All to Curves NEW - Order #$order_id: ⚠️ Exception in convert_images_to_paths: " . $e->getMessage());
            }
            return $svg_content;
        }
        
        // STEP 0.5: Extract and preserve embedded fonts for font shape preservation
        // Embedded fonts (@font-face with base64 data) allow Inkscape to preserve font shape when converting text to paths
        // CRITICAL: Extract and backup font definitions BEFORE any processing
        $font_backup = array();
        $has_embedded_fonts = false;
        
        // Extract @font-face definitions from <style> elements
        if (preg_match_all('/<style[^>]*>(.*?)<\/style>/is', $svg_content, $style_matches, PREG_SET_ORDER)) {
            foreach ($style_matches as $style_match) {
                $style_content = $style_match[1];
                // Check for @font-face with base64 data
                if (preg_match_all('/@font-face\s*\{([^}]*src:\s*url\(data:application\/[^)]+;base64[^}]*)\}/is', $style_content, $font_matches, PREG_SET_ORDER)) {
                    foreach ($font_matches as $font_match) {
                        $font_face_full = '@font-face {' . $font_match[1] . '}';
                        $font_backup[] = $font_face_full;
                        $has_embedded_fonts = true;
                    }
                }
            }
        }
        
        // Also check for @font-face in CDATA sections
        if (preg_match_all('/<!\[CDATA\[(.*?)\]\]>/is', $svg_content, $cdata_matches, PREG_SET_ORDER)) {
            foreach ($cdata_matches as $cdata_match) {
                $cdata_content = $cdata_match[1];
                if (preg_match_all('/@font-face\s*\{([^}]*src:\s*url\(data:application\/[^)]+;base64[^}]*)\}/is', $cdata_content, $font_matches, PREG_SET_ORDER)) {
                    foreach ($font_matches as $font_match) {
                        $font_face_full = '@font-face {' . $font_match[1] . '}';
                        if (!in_array($font_face_full, $font_backup)) {
                            $font_backup[] = $font_face_full;
                            $has_embedded_fonts = true;
                        }
                    }
                }
            }
        }
        
        if ($has_embedded_fonts) {
            error_log("APD Convert All to Curves NEW - Order #$order_id: ✅ Found " . count($font_backup) . " embedded font(s) - font shape will be preserved during conversion");
            // Log font families if available
            foreach ($font_backup as $idx => $font_def) {
                if (preg_match('/font-family:\s*["\']?([^;"\']+)["\']?/i', $font_def, $family_match)) {
                    $font_family = trim($family_match[1]);
                    error_log("APD Convert All to Curves NEW - Order #$order_id: Font #" . ($idx + 1) . ": $font_family");
                }
            }
        } else {
            error_log("APD Convert All to Curves NEW - Order #$order_id: ⚠️ No embedded fonts found - text will be converted to generic paths (font shape may be lost)");
            error_log("APD Convert All to Curves NEW - Order #$order_id: 💡 Tip: Embed fonts in SVG to preserve font shape when converting to curves");
        }
        
        // STEP 1: Track custom text elements TRƯỚC conversion
        error_log("APD Convert All to Curves NEW - Order #$order_id: Tracking custom text elements...");
        $custom_text_elements = $this->track_custom_text_elements($svg_content, $order_id);
        
        // STEP 1.5: Preserve custom text material outline patterns BEFORE conversion
        error_log("APD Convert All to Curves NEW - Order #$order_id: Preserving custom text material outline...");
        $preserve_result = $this->preserve_custom_text_material_outline($svg_content, $order_id);
        $svg_content = $preserve_result['svg'];
        $custom_text_pattern_backup = $preserve_result['patterns'];
        
        // Backup all pattern definitions with PNG/JPEG images for restoration if needed
        $pattern_backup_for_restore = array();
        if (preg_match_all('/<pattern([^>]*id=["\']([^"\']+)["\'][^>]*)>(.*?)<\/pattern>/is', $svg_content, $pattern_matches, PREG_SET_ORDER)) {
            foreach ($pattern_matches as $match) {
                $pattern_id = $match[2];
                $pattern_full = $match[0];
                // Only backup patterns with embedded images
                if (preg_match('/data:image\/(png|jpeg|jpg);base64/i', $pattern_full)) {
                    $pattern_backup_for_restore[$pattern_id] = $pattern_full;
                }
            }
            error_log("APD Convert All to Curves NEW - Order #$order_id: Backed up " . count($pattern_backup_for_restore) . " pattern definitions with PNG/JPEG images for restoration");
        }
        
        // STEP 2: Convert images/logos to paths first (before text conversion)
        // This ensures logos are also converted to curves
        $svg_content = $this->convert_images_to_paths($svg_content, $order_id);
        
        // STEP 3: Backup ALL pattern definitions and viewBox (including custom text patterns)
        $pattern_backup = array();
        // Merge custom text patterns into general backup
        $pattern_backup = array_merge($pattern_backup, $custom_text_pattern_backup);
        
        if (preg_match_all('/<pattern[^>]*id=["\']([^"\']+)["\'][^>]*>(.*?)<\/pattern>/is', $svg_content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $pattern_id = $match[1];
                $pattern_full = $match[0];
                // Only add if not already in backup (from custom text patterns)
                if (!isset($pattern_backup[$pattern_id])) {
                    $pattern_backup[$pattern_id] = $pattern_full;
                }
            }
            error_log("APD Convert All to Curves NEW - Order #$order_id: Backed up " . count($pattern_backup) . " pattern definitions (including " . count($custom_text_pattern_backup) . " custom text patterns)");
        }
        
        $original_viewBox = '';
        if (preg_match('/<svg[^>]*viewBox=["\']([^"\']+)["\']/', $svg_content, $m)) {
            $original_viewBox = $m[1];
        }
        
        // STEP 3: Ensure fonts are preserved in temp SVG file
        // CRITICAL: Ensure @font-face definitions with base64 data are in <style> element within <defs>
        // This allows Inkscape to access fonts when converting text to paths
        if (!empty($font_backup)) {
            // Ensure <defs> exists
            if (!preg_match('/<defs[^>]*>/i', $svg_content)) {
                // Insert <defs> after <svg> tag
                $svg_content = preg_replace('/(<svg[^>]*>)/i', '$1' . "\n<defs></defs>", $svg_content, 1);
            }
            
            // Check if <style> element exists in <defs>
            $style_in_defs = preg_match('/<defs[^>]*>.*?<style[^>]*>/is', $svg_content);
            
            if (!$style_in_defs) {
                // Create <style> element in <defs> with font definitions
                $fonts_css = implode("\n", $font_backup);
                $style_element = "<style type=\"text/css\"><![CDATA[\n" . $fonts_css . "\n]]></style>";
                $svg_content = preg_replace('/(<defs[^>]*>)/i', '$1' . "\n  " . $style_element . "\n", $svg_content, 1);
                error_log("APD Convert All to Curves NEW - Order #$order_id: ✅ Added " . count($font_backup) . " font definition(s) to <defs><style> for Inkscape");
            } else {
                // Append fonts to existing <style> element
                $fonts_css = implode("\n", $font_backup);
                $svg_content = preg_replace_callback(
                    '/(<defs[^>]*>.*?<style[^>]*>)(.*?)(<\/style>)/is',
                    function($matches) use ($fonts_css) {
                        $existing_content = $matches[2];
                        // Check if fonts already in style
                        if (strpos($existing_content, '@font-face') === false) {
                            return $matches[1] . $existing_content . "\n" . $fonts_css . "\n" . $matches[3];
                        }
                        return $matches[0]; // Already has fonts
                    },
                    $svg_content,
                    1
                );
                error_log("APD Convert All to Curves NEW - Order #$order_id: ✅ Ensured " . count($font_backup) . " font definition(s) are in <defs><style> for Inkscape");
            }
        }
        
        // STEP 3: Save SVG temporarily
        $upload_dir = wp_upload_dir();
        $temp_svg_input = $upload_dir['path'] . '/temp-curves-' . $order_id . '-' . time() . '.svg';
        $temp_svg_output = $upload_dir['path'] . '/temp-curves-converted-' . $order_id . '-' . time() . '.svg';
        
        file_put_contents($temp_svg_input, $svg_content);
        
        // STEP 4: Use Inkscape to convert ALL elements to paths (text, shapes, etc.)
        // CRITICAL: Convert text first with object-to-path (Inkscape will use embedded fonts if available)
        // Then convert shapes (circles, rects, etc.) to paths
        // This preserves font shape when fonts are embedded in SVG
        // Note: If fonts are embedded, Inkscape will use them to convert text to paths, preserving font shape
        $command = escapeshellarg($inkscape_path) . 
                   ' --actions="select-all:text;object-to-path;select-all;object-to-path"' .
                   ' --export-filename=' . escapeshellarg($temp_svg_output) .
                   ' --export-type=svg' .
                   ' ' . escapeshellarg($temp_svg_input) . 
                   ' 2>&1';
        
        error_log("APD Convert All to Curves NEW - Order #$order_id: Running Inkscape command to convert text and shapes to paths: " . $command);
        
        $output = shell_exec($command);
        $return_code = 0;
        exec($command . '; echo $?', $output_array, $return_code);
        
        error_log("APD Convert All to Curves NEW - Order #$order_id: Inkscape return code: $return_code");
        
        // STEP 5: Check if conversion was successful
        if (file_exists($temp_svg_output) && filesize($temp_svg_output) > 0) {
            $converted_content = file_get_contents($temp_svg_output);
            
            // Restore lost patterns (including custom text patterns)
            if (!empty($pattern_backup)) {
                $converted_content = $this->restore_custom_text_material_outline($converted_content, $pattern_backup, $order_id);
                file_put_contents($temp_svg_output, $converted_content);
            }
            
            // Restore lost fonts if they were removed during Inkscape conversion
            if (!empty($font_backup)) {
                $fonts_after_conversion = preg_match_all('/@font-face[^}]*src:\s*url\(data:application\/[^)]+;base64/i', $converted_content);
                if ($fonts_after_conversion < count($font_backup)) {
                    error_log("APD Convert All to Curves NEW - Order #$order_id: ⚠️ Fonts lost during conversion ($fonts_after_conversion/" . count($font_backup) . ") - restoring...");
                    
                    // Ensure <defs> exists
                    if (!preg_match('/<defs[^>]*>/i', $converted_content)) {
                        $converted_content = preg_replace('/(<svg[^>]*>)/i', '$1' . "\n<defs></defs>", $converted_content, 1);
                    }
                    
                    // Add fonts to <defs><style>
                    $fonts_css = implode("\n", $font_backup);
                    $style_element = "<style type=\"text/css\"><![CDATA[\n" . $fonts_css . "\n]]></style>";
                    
                    if (preg_match('/<defs[^>]*>/i', $converted_content)) {
                        // Check if style already exists
                        if (!preg_match('/<defs[^>]*>.*?<style[^>]*>/is', $converted_content)) {
                            $converted_content = preg_replace('/(<defs[^>]*>)/i', '$1' . "\n  " . $style_element . "\n", $converted_content, 1);
                        } else {
                            // Append to existing style
                            $converted_content = preg_replace_callback(
                                '/(<defs[^>]*>.*?<style[^>]*>)(.*?)(<\/style>)/is',
                                function($matches) use ($fonts_css) {
                                    $existing_content = $matches[2];
                                    if (strpos($existing_content, '@font-face') === false) {
                                        return $matches[1] . $existing_content . "\n" . $fonts_css . "\n" . $matches[3];
                                    }
                                    return $matches[0];
                                },
                                $converted_content,
                                1
                            );
                        }
                        error_log("APD Convert All to Curves NEW - Order #$order_id: ✅ Restored " . count($font_backup) . " font definition(s)");
                    }
                    
                    file_put_contents($temp_svg_output, $converted_content);
                } else {
                    error_log("APD Convert All to Curves NEW - Order #$order_id: ✅ Fonts preserved during conversion ($fonts_after_conversion/" . count($font_backup) . ")");
                }
            }
            
            // Restore viewBox
            if ($original_viewBox && !preg_match('/viewBox=/i', $converted_content)) {
                error_log("APD Convert All to Curves NEW - Order #$order_id: Restoring viewBox: $original_viewBox");
                $converted_content = preg_replace(
                    '/<svg([^>]*)>/i',
                    '<svg$1 viewBox="' . $original_viewBox . '">',
                    $converted_content,
                    1
                );
                file_put_contents($temp_svg_output, $converted_content);
            }
            
            // STEP 6: Apply custom text patterns to converted paths
            if (!empty($custom_text_elements)) {
                error_log("APD Convert All to Curves NEW - Order #$order_id: Applying custom text patterns to converted paths...");
                try {
                    $converted_content = $this->apply_custom_text_pattern_to_paths($converted_content, $custom_text_elements, $order_id);
                    file_put_contents($temp_svg_output, $converted_content);
                } catch (Exception $e) {
                    error_log("APD Convert All to Curves NEW - Order #$order_id: ⚠️ Exception in apply_custom_text_pattern_to_paths: " . $e->getMessage());
                }
            }
            
            // Verify conversion - check all element types
            $text_count_after = preg_match_all('/<text[^>]*>/i', $converted_content);
            $path_count_after = preg_match_all('/<path[^>]*>/i', $converted_content);
            $circle_count_after = preg_match_all('/<circle[^>]*>/i', $converted_content);
            $rect_count_after = preg_match_all('/<rect[^>]*>/i', $converted_content);
            $ellipse_count_after = preg_match_all('/<ellipse[^>]*>/i', $converted_content);
            $polygon_count_after = preg_match_all('/<polygon[^>]*>/i', $converted_content);
            $polyline_count_after = preg_match_all('/<polyline[^>]*>/i', $converted_content);
            $line_count_after = preg_match_all('/<line[^>]*>/i', $converted_content);
            $shapes_count_after = $circle_count_after + $rect_count_after + $ellipse_count_after + $polygon_count_after + $polyline_count_after + $line_count_after;
            $apd_text_pattern_fills = preg_match_all('/fill=["\']url\(#apdTextPattern\)["\']/i', $converted_content);
            
            // Verify pattern preservation after conversion
            $pattern_defs_after_text_to_path = preg_match_all('/<pattern[^>]*>/i', $converted_content);
            $pattern_images_after_text_to_path = preg_match_all('/<pattern[^>]*>.*?<image[^>]*data:image\/(png|jpeg|jpg);base64[^>]*>.*?<\/pattern>/is', $converted_content);
            
            // Get pattern counts before conversion for comparison
            $pattern_defs_before_text_to_path_local = preg_match_all('/<pattern[^>]*>/i', $svg_content);
            $pattern_images_before_text_to_path_local = preg_match_all('/<pattern[^>]*>.*?<image[^>]*data:image\/(png|jpeg|jpg);base64[^>]*>.*?<\/pattern>/is', $svg_content);
            
            error_log("APD Convert All to Curves NEW - Order #$order_id: Conversion results:");
            error_log("  - Text elements: $text_count_before -> $text_count_after");
            error_log("  - Shape elements: $shapes_count_before -> $shapes_count_after (circles: $circle_count_before->$circle_count_after, rects: $rect_count_before->$rect_count_after, ellipses: $ellipse_count_before->$ellipse_count_after)");
            error_log("  - Path elements: $path_count_after");
            error_log("  - apdTextPattern fills: $apd_text_pattern_fills");
            error_log("APD Convert All to Curves NEW - Order #$order_id: Pattern preservation after text-to-path:");
            error_log("  - Pattern definitions: $pattern_defs_after_text_to_path (was $pattern_defs_before_text_to_path_local)");
            error_log("  - Patterns with embedded PNG/JPEG images: $pattern_images_after_text_to_path (was $pattern_images_before_text_to_path_local)");
            
            if ($pattern_defs_after_text_to_path < $pattern_defs_before_text_to_path_local) {
                error_log("APD Convert All to Curves NEW - Order #$order_id: ⚠️ WARNING - Pattern definitions lost during text-to-path conversion!");
                // Restore lost patterns if we have backup
                if (!empty($pattern_backup_for_restore)) {
                    error_log("APD Convert All to Curves NEW - Order #$order_id: Attempting to restore lost pattern definitions...");
                    // Ensure defs exists
                    if (!preg_match('/<defs[^>]*>/i', $converted_content)) {
                        $converted_content = preg_replace('/(<svg[^>]*>)/i', '$1' . "\n<defs></defs>", $converted_content, 1);
                    }
                    // Restore patterns
                    foreach ($pattern_backup_for_restore as $pattern_id => $pattern_def) {
                        if (!preg_match('/<pattern[^>]*id=["\']' . preg_quote($pattern_id, '/') . '["\'][^>]*>/i', $converted_content)) {
                            $converted_content = preg_replace('/(<defs[^>]*>)/i', '$1' . "\n  " . $pattern_def . "\n", $converted_content, 1);
                            error_log("APD Convert All to Curves NEW - Order #$order_id: Restored pattern definition: $pattern_id");
                        }
                    }
                    // Re-verify after restoration
                    $pattern_defs_after_restore = preg_match_all('/<pattern[^>]*>/i', $converted_content);
                    error_log("APD Convert All to Curves NEW - Order #$order_id: Pattern definitions after restoration: $pattern_defs_after_restore");
                }
            } else if ($pattern_images_after_text_to_path < $pattern_images_before_text_to_path_local) {
                error_log("APD Convert All to Curves NEW - Order #$order_id: ⚠️ WARNING - Pattern images lost during text-to-path conversion!");
            } else {
                error_log("APD Convert All to Curves NEW - Order #$order_id: ✅ Pattern definitions and PNG/JPEG images preserved");
            }
            
            // Verify conversion success: text and shapes should be converted to paths
            $conversion_success = ($text_count_after < $text_count_before || $text_count_before === 0) && 
                                  ($shapes_count_after < $shapes_count_before || $shapes_count_before === 0) && 
                                  $path_count_after > 0;
            
            if ($conversion_success) {
                error_log("APD Convert All to Curves NEW - Order #$order_id: ✅ SUCCESS - All elements converted to curves");
                error_log("  - Text: $text_count_before -> $text_count_after");
                error_log("  - Shapes: $shapes_count_before -> $shapes_count_after");
                error_log("  - Total paths: $path_count_after");
                unlink($temp_svg_input);
                unlink($temp_svg_output);
                return $converted_content;
            } else {
                error_log("APD Convert All to Curves NEW - Order #$order_id: ⚠️ WARNING - Conversion may have failed");
                error_log("  - Text conversion: " . ($text_count_after < $text_count_before ? "✅" : "❌") . " ($text_count_before -> $text_count_after)");
                error_log("  - Shapes conversion: " . ($shapes_count_after < $shapes_count_before ? "✅" : "❌") . " ($shapes_count_before -> $shapes_count_after)");
                error_log("  - Paths created: $path_count_after");
            }
        }
        
        // Clean up and return
        unlink($temp_svg_input);
        if (file_exists($temp_svg_output)) {
            unlink($temp_svg_output);
        }
        
        return $svg_content;
    }

    /**
     * Apply material outline to curves - NEW VERSION
     * Auto-detects pattern strokes and converts them to fills on expanded paths
     * 
     * @param string $svg_content SVG content with curves
     * @param int $order_id Order ID for logging
     * @return string SVG content with material outline applied
     */
    private function apply_material_outline_to_curves($svg_content, $order_id = 0)
    {
        error_log("APD Apply Material Outline NEW - Order #$order_id: Starting material outline application");
        
        // STEP 1: Detect pattern strokes
        $pattern_strokes = preg_match_all('/stroke=["\']url\(#[^)]+\)["\']/i', $svg_content);
        $pattern_fills = preg_match_all('/fill=["\']url\(#[^)]+\)["\']/i', $svg_content);
        $pattern_defs = preg_match_all('/<pattern[^>]*>/i', $svg_content);
        
        error_log("APD Apply Material Outline NEW - Order #$order_id: Found $pattern_strokes pattern strokes, $pattern_fills pattern fills, $pattern_defs pattern definitions");
        
        if ($pattern_strokes === 0) {
            error_log("APD Apply Material Outline NEW - Order #$order_id: No pattern strokes found, skipping");
            return $svg_content;
        }
        
        // Check if Inkscape is available
        $inkscape_path = APD_SVG_Utils::find_inkscape();
        if (!$inkscape_path) {
            error_log("APD Apply Material Outline NEW - Order #$order_id: ⚠️ Inkscape not available, using manual conversion");
            // Fallback: Try manual conversion (limited but better than nothing)
            try {
                return $this->apply_material_outline_manual($svg_content, $order_id);
            } catch (Exception $e) {
                error_log("APD Apply Material Outline NEW - Order #$order_id: ⚠️ Exception in manual conversion: " . $e->getMessage());
                return $svg_content; // Return original if manual conversion fails
            }
        }
        
        // STEP 2: Use Inkscape to convert strokes to paths (expanded paths with pattern fills)
        $upload_dir = wp_upload_dir();
        $temp_svg_input = $upload_dir['path'] . '/temp-outline-' . $order_id . '-' . time() . '.svg';
        $temp_svg_output = $upload_dir['path'] . '/temp-outline-converted-' . $order_id . '-' . time() . '.svg';
        
        // CRITICAL: Backup ALL paths with pattern strokes BEFORE stroke-to-path
        // This includes custom text paths, logo paths, and any other paths with material outline
        // We need to track stroke-width to ensure expanded paths have correct thickness
        $paths_with_strokes_before = array();
        
        // Backup custom text paths with apdTextPattern strokes
        if (preg_match_all('/<path([^>]*stroke=["\']url\(#apdTextPattern\)["\'][^>]*)>/i', $svg_content, $custom_path_matches, PREG_SET_ORDER)) {
            foreach ($custom_path_matches as $match) {
                $attrs = $match[1];
                $stroke_width = 48; // Default stroke width for material outline
                
                // Extract stroke-width from material outline đã chọn
                if (preg_match('/stroke-width=["\']([^"\']+)["\']/', $attrs, $sw_match)) {
                    $stroke_width = floatval($sw_match[1]);
                }
                
                // Extract path data (d attribute) to identify paths after conversion
                if (preg_match('/d=["\']([^"\']+)["\']/', $attrs, $d_match)) {
                    $paths_with_strokes_before[] = array(
                        'path_data' => $d_match[1],
                        'full_path' => $match[0],
                        'pattern_id' => 'apdTextPattern',
                        'stroke_width' => $stroke_width
                    );
                }
            }
            error_log("APD Apply Material Outline NEW - Order #$order_id: Backed up " . count($paths_with_strokes_before) . " custom text paths with apdTextPattern strokes");
        }
        
        // Backup logo paths with logoMaterialPattern strokes
        if (preg_match_all('/<path([^>]*stroke=["\']url\(#logoMaterialPattern\)["\'][^>]*)>/i', $svg_content, $logo_path_matches, PREG_SET_ORDER)) {
            foreach ($logo_path_matches as $match) {
                $attrs = $match[1];
                $stroke_width = 48; // Default stroke width
                
                // Extract stroke-width from material outline đã chọn
                if (preg_match('/stroke-width=["\']([^"\']+)["\']/', $attrs, $sw_match)) {
                    $stroke_width = floatval($sw_match[1]);
                }
                
                if (preg_match('/d=["\']([^"\']+)["\']/', $attrs, $d_match)) {
                    $paths_with_strokes_before[] = array(
                        'path_data' => $d_match[1],
                        'full_path' => $match[0],
                        'pattern_id' => 'logoMaterialPattern',
                        'stroke_width' => $stroke_width
                    );
                }
            }
            error_log("APD Apply Material Outline NEW - Order #$order_id: Backed up " . (count($paths_with_strokes_before) - preg_match_all('/apdTextPattern/i', json_encode($paths_with_strokes_before))) . " logo paths with logoMaterialPattern strokes");
        }
        
        // Backup any other paths with pattern strokes
        if (preg_match_all('/<path([^>]*stroke=["\']url\(#([^)]+)\)["\'][^>]*)>/i', $svg_content, $other_path_matches, PREG_SET_ORDER)) {
            foreach ($other_path_matches as $match) {
                $attrs = $match[1];
                $pattern_id = $match[2];
                
                // Skip if already backed up (apdTextPattern or logoMaterialPattern)
                if ($pattern_id === 'apdTextPattern' || $pattern_id === 'logoMaterialPattern') {
                    continue;
                }
                
                $stroke_width = 48; // Default
                if (preg_match('/stroke-width=["\']([^"\']+)["\']/', $attrs, $sw_match)) {
                    $stroke_width = floatval($sw_match[1]);
                }
                
                if (preg_match('/d=["\']([^"\']+)["\']/', $attrs, $d_match)) {
                    $paths_with_strokes_before[] = array(
                        'path_data' => $d_match[1],
                        'full_path' => $match[0],
                        'pattern_id' => $pattern_id,
                        'stroke_width' => $stroke_width
                    );
                }
            }
        }
        
        $total_paths_with_strokes = count($paths_with_strokes_before);
        error_log("APD Apply Material Outline NEW - Order #$order_id: Total paths with pattern strokes backed up: $total_paths_with_strokes");
        
        // Count paths before stroke-to-path to verify expansion
        $paths_before_stroke_to_path = preg_match_all('/<path[^>]*>/i', $svg_content);
        error_log("APD Apply Material Outline NEW - Order #$order_id: Paths before stroke-to-path: $paths_before_stroke_to_path");
        
        // Verify paths have proper stroke attributes before stroke-to-path
        // Inkscape stroke-to-path requires paths to have stroke-width > 0
        $paths_with_stroke_width = preg_match_all('/<path[^>]*stroke-width=["\']([^"\']+)["\'][^>]*>/i', $svg_content);
        $paths_with_stroke = preg_match_all('/<path[^>]*stroke=["\']url\(#[^)]+\)["\'][^>]*>/i', $svg_content);
        error_log("APD Apply Material Outline NEW - Order #$order_id: Paths with stroke: $paths_with_stroke, paths with stroke-width: $paths_with_stroke_width");
        
        if ($paths_with_stroke > 0 && $paths_with_stroke_width === 0) {
            error_log("APD Apply Material Outline NEW - Order #$order_id: ⚠️ WARNING - Paths have strokes but no stroke-width - stroke-to-path may fail");
        }
        
        file_put_contents($temp_svg_input, $svg_content);
        
        // Command: Convert strokes to paths (this creates expanded paths with pattern fills)
        // CRITICAL: Try different approaches to ensure stroke-to-path works
        // Strategy 1: Select only paths with strokes, then stroke-to-path
        // Strategy 2: Use --actions with explicit path selection
        $command = escapeshellarg($inkscape_path) . 
                   ' --actions="select-all:path;stroke-to-path"' .
                   ' --export-filename=' . escapeshellarg($temp_svg_output) .
                   ' --export-type=svg' .
                   ' ' . escapeshellarg($temp_svg_input) . 
                   ' 2>&1';
        
        error_log("APD Apply Material Outline NEW - Order #$order_id: Running stroke-to-path command (select-all:path;stroke-to-path): " . $command);
        
        $output = shell_exec($command);
        $return_code = 0;
        exec($command . '; echo $?', $output_array, $return_code);
        
        error_log("APD Apply Material Outline NEW - Order #$order_id: Inkscape stroke-to-path return code: $return_code");
        if ($output) {
            error_log("APD Apply Material Outline NEW - Order #$order_id: Inkscape output: " . substr($output, 0, 500));
        }
        
        // STEP 3: Check if conversion was successful
        if (file_exists($temp_svg_output) && filesize($temp_svg_output) > 0) {
            $converted_content = file_get_contents($temp_svg_output);
            
            // Count paths after stroke-to-path to verify expansion
            $paths_after_stroke_to_path = preg_match_all('/<path[^>]*>/i', $converted_content);
            $paths_added = $paths_after_stroke_to_path - $paths_before_stroke_to_path;
            
            // Also check for pattern fills (stroke-to-path should convert strokes to fills)
            $pattern_fills_after_stroke_to_path = preg_match_all('/fill=["\']url\(#[^)]+\)["\']/i', $converted_content);
            $pattern_strokes_after_stroke_to_path = preg_match_all('/stroke=["\']url\(#[^)]+\)["\']/i', $converted_content);
            
            error_log("APD Apply Material Outline NEW - Order #$order_id: Paths after stroke-to-path: $paths_after_stroke_to_path (added $paths_added paths)");
            error_log("APD Apply Material Outline NEW - Order #$order_id: Pattern fills after stroke-to-path: $pattern_fills_after_stroke_to_path (was $pattern_fills)");
            error_log("APD Apply Material Outline NEW - Order #$order_id: Pattern strokes after stroke-to-path: $pattern_strokes_after_stroke_to_path (was $pattern_strokes)");
            
            // Verification: Check if stroke-to-path actually worked
            $stroke_to_path_success = false;
            
            if ($paths_added > 0) {
                error_log("APD Apply Material Outline NEW - Order #$order_id: ✅ Stroke-to-path created $paths_added expanded outline paths");
                $stroke_to_path_success = true;
            } else if ($pattern_fills_after_stroke_to_path > $pattern_fills && $pattern_strokes_after_stroke_to_path < $pattern_strokes) {
                // Pattern fills increased and strokes decreased - stroke-to-path worked but didn't create new paths
                // This can happen if Inkscape merged expanded paths with original paths
                error_log("APD Apply Material Outline NEW - Order #$order_id: ✅ Stroke-to-path worked (pattern fills increased, strokes decreased) - paths may have been merged");
                $stroke_to_path_success = true;
            } else {
                error_log("APD Apply Material Outline NEW - Order #$order_id: ⚠️ WARNING - No additional paths created (stroke-to-path may not have expanded paths)");
                error_log("APD Apply Material Outline NEW - Order #$order_id: Trying alternative stroke-to-path approach...");
                
                // Try alternative: Use different command format
                $command_alt = escapeshellarg($inkscape_path) . 
                               ' --actions="select-all;path-stroke-to-path"' .
                               ' --export-filename=' . escapeshellarg($temp_svg_output) .
                               ' --export-type=svg' .
                               ' ' . escapeshellarg($temp_svg_input) . 
                               ' 2>&1';
                
                error_log("APD Apply Material Outline NEW - Order #$order_id: Trying alternative command: " . $command_alt);
                
                $output_alt = shell_exec($command_alt);
                $return_code_alt = 0;
                exec($command_alt . '; echo $?', $output_array_alt, $return_code_alt);
                
                if (file_exists($temp_svg_output) && filesize($temp_svg_output) > 0) {
                    $converted_content_alt = file_get_contents($temp_svg_output);
                    $paths_after_alt = preg_match_all('/<path[^>]*>/i', $converted_content_alt);
                    $paths_added_alt = $paths_after_alt - $paths_before_stroke_to_path;
                    $pattern_fills_after_alt = preg_match_all('/fill=["\']url\(#[^)]+\)["\']/i', $converted_content_alt);
                    $pattern_strokes_after_alt = preg_match_all('/stroke=["\']url\(#[^)]+\)["\']/i', $converted_content_alt);
                    
                    if ($paths_added_alt > 0 || ($pattern_fills_after_alt > $pattern_fills && $pattern_strokes_after_alt < $pattern_strokes)) {
                        error_log("APD Apply Material Outline NEW - Order #$order_id: ✅ Alternative command worked - created $paths_added_alt expanded paths, fills: $pattern_fills_after_alt, strokes: $pattern_strokes_after_alt");
                        $converted_content = $converted_content_alt;
                        $paths_after_stroke_to_path = $paths_after_alt;
                        $paths_added = $paths_added_alt;
                        $pattern_fills_after_stroke_to_path = $pattern_fills_after_alt;
                        $pattern_strokes_after_stroke_to_path = $pattern_strokes_after_alt;
                        $stroke_to_path_success = true;
                    } else {
                        error_log("APD Apply Material Outline NEW - Order #$order_id: ⚠️ Alternative command also failed - will use manual expansion fallback");
                    }
                }
            }
            
            // Update pattern_fills_after and pattern_strokes_after for use in later code
            $pattern_fills_after = $pattern_fills_after_stroke_to_path;
            $pattern_strokes_after = $pattern_strokes_after_stroke_to_path;
            
            // If stroke-to-path failed, use manual expansion fallback
            if (!$stroke_to_path_success && !empty($paths_with_strokes_before)) {
                error_log("APD Apply Material Outline NEW - Order #$order_id: ⚠️ Stroke-to-path failed - using manual expansion fallback");
                try {
                    $converted_content = $this->apply_material_outline_manual_expansion($converted_content, $paths_with_strokes_before, $order_id);
                    error_log("APD Apply Material Outline NEW - Order #$order_id: ✅ Manual expansion applied");
                } catch (Exception $e) {
                    error_log("APD Apply Material Outline NEW - Order #$order_id: ⚠️ Exception in manual expansion: " . $e->getMessage());
                    // Continue with converted_content as-is
                }
            }
            
            // Verify custom text paths were converted
            $custom_text_fills_after = preg_match_all('/fill=["\']url\(#apdTextPattern\)["\']/i', $converted_content);
            $custom_text_strokes_after = preg_match_all('/stroke=["\']url\(#apdTextPattern\)["\']/i', $converted_content);
            $logo_fills_after = preg_match_all('/fill=["\']url\(#logoMaterialPattern\)["\']/i', $converted_content);
            
            // Verify pattern preservation after stroke-to-path
            $pattern_defs_after_stroke_to_path = preg_match_all('/<pattern[^>]*>/i', $converted_content);
            $pattern_images_after_stroke_to_path = preg_match_all('/<pattern[^>]*>.*?<image[^>]*data:image\/(png|jpeg|jpg);base64[^>]*>.*?<\/pattern>/is', $converted_content);
            
            error_log("APD Apply Material Outline NEW - Order #$order_id: After stroke-to-path conversion:");
            error_log("  - Pattern fills: $pattern_fills_after (was $pattern_fills)");
            error_log("  - Pattern strokes: $pattern_strokes_after (was $pattern_strokes)");
            error_log("  - Custom text (apdTextPattern) fills: $custom_text_fills_after");
            error_log("  - Custom text (apdTextPattern) strokes: $custom_text_strokes_after");
            error_log("  - Logo (logoMaterialPattern) fills: $logo_fills_after");
            error_log("APD Apply Material Outline NEW - Order #$order_id: Pattern preservation after stroke-to-path:");
            error_log("  - Pattern definitions: $pattern_defs_after_stroke_to_path");
            error_log("  - Patterns with embedded PNG/JPEG images: $pattern_images_after_stroke_to_path");
            
            if ($pattern_defs_after_stroke_to_path === 0) {
                error_log("APD Apply Material Outline NEW - Order #$order_id: ⚠️ WARNING - Pattern definitions lost during stroke-to-path conversion!");
            } else if ($pattern_images_after_stroke_to_path === 0) {
                error_log("APD Apply Material Outline NEW - Order #$order_id: ⚠️ WARNING - Pattern images lost during stroke-to-path conversion!");
            } else {
                error_log("APD Apply Material Outline NEW - Order #$order_id: ✅ Pattern definitions and PNG/JPEG images preserved");
            }
            
            // CRITICAL FIX: Restore pattern fills if they were lost during stroke-to-path
            // Inkscape's stroke-to-path creates expanded paths but may lose pattern fills
            // Strategy: Restore pattern fills based on backed-up paths with strokes
            // Material outline từ viền ngoài được lấy từ stroke-width đã chọn trong paths_with_strokes_before
            if (!empty($paths_with_strokes_before)) {
                // Group paths by pattern_id to restore correctly
                $paths_by_pattern = array();
                foreach ($paths_with_strokes_before as $path_info) {
                    $pattern_id = $path_info['pattern_id'];
                    if (!isset($paths_by_pattern[$pattern_id])) {
                        $paths_by_pattern[$pattern_id] = array();
                    }
                    $paths_by_pattern[$pattern_id][] = $path_info;
                }
                
                // Check if pattern fills were lost for any pattern
                $patterns_to_restore = array();
                foreach ($paths_by_pattern as $pattern_id => $paths) {
                    $pattern_fills_count = preg_match_all('/fill=["\']url\(#' . preg_quote($pattern_id, '/') . '\)["\']/i', $converted_content);
                    if ($pattern_fills_count === 0 && count($paths) > 0) {
                        $patterns_to_restore[$pattern_id] = $paths;
                        error_log("APD Apply Material Outline NEW - Order #$order_id: ⚠️ Pattern fills lost for $pattern_id, will restore " . count($paths) . " paths");
                    }
                }
                
                if (!empty($patterns_to_restore)) {
                    error_log("APD Apply Material Outline NEW - Order #$order_id: Restoring pattern fills for " . count($patterns_to_restore) . " patterns...");
                    
                    // For each pattern that needs restoration
                    foreach ($patterns_to_restore as $pattern_id => $paths) {
                        $expected_restored = count($paths) * 2; // Original + expanded outline path
                        $paths_restored = 0;
                        
                        // Find paths without pattern fills and restore the correct pattern
                        $converted_content = preg_replace_callback(
                            '/<path([^>]*)>/i',
                            function($matches) use (&$paths_restored, $expected_restored, $pattern_id, $order_id) {
                                $attrs = $matches[1];
                                
                                // Skip if already has pattern fill
                                if (preg_match('/fill=["\']url\(#[^)]+\)["\']/i', $attrs)) {
                                    return $matches[0];
                                }
                                
                                // Skip if has mask (already processed)
                                if (preg_match('/mask=/i', $attrs)) {
                                    return $matches[0];
                                }
                                
                                // Restore pattern fill to paths without fills (likely expanded outline paths)
                                if ($paths_restored < $expected_restored) {
                                    $new_attrs = $attrs;
                                    if (!preg_match('/fill=/i', $attrs)) {
                                        $new_attrs .= ' fill="url(#' . htmlspecialchars($pattern_id, ENT_QUOTES) . ')"';
                                    } else {
                                        $new_attrs = preg_replace('/fill=["\'][^"\']*["\']/', 'fill="url(#' . htmlspecialchars($pattern_id, ENT_QUOTES) . ')"', $new_attrs);
                                    }
                                    
                                    $paths_restored++;
                                    error_log("APD Apply Material Outline NEW - Order #$order_id: Restored $pattern_id fill to expanded outline path #$paths_restored");
                                    
                                    return '<path' . $new_attrs . '>';
                                }
                                
                                return $matches[0];
                            },
                            $converted_content,
                            50 // Limit matches to avoid over-restoring
                        );
                        
                        // Verify restoration for this pattern
                        $pattern_fills_after_restore = preg_match_all('/fill=["\']url\(#' . preg_quote($pattern_id, '/') . '\)["\']/i', $converted_content);
                        error_log("APD Apply Material Outline NEW - Order #$order_id: After restoration - $pattern_id fills: $pattern_fills_after_restore (restored $paths_restored paths)");
                    }
                    
                    // Update counts after restoration
                    $pattern_fills_after = preg_match_all('/fill=["\']url\(#[^)]+\)["\']/i', $converted_content);
                    $custom_text_fills_after = preg_match_all('/fill=["\']url\(#apdTextPattern\)["\']/i', $converted_content);
                    
                    // Verify that restored paths are actually expanded outline paths
                    $total_paths_after = preg_match_all('/<path[^>]*>/i', $converted_content);
                    $total_paths_before = preg_match_all('/<path[^>]*>/i', $svg_content);
                    
                    error_log("APD Apply Material Outline NEW - Order #$order_id: Path count analysis after restoration:");
                    error_log("  - Total paths before stroke-to-path: $total_paths_before");
                    error_log("  - Total paths after stroke-to-path: $total_paths_after");
                    
                    if ($total_paths_after > $total_paths_before) {
                        $paths_added = $total_paths_after - $total_paths_before;
                        error_log("APD Apply Material Outline NEW - Order #$order_id: ✅ Stroke-to-path created $paths_added additional paths (expanded outline paths with material outline từ viền ngoài)");
                    } else {
                        error_log("APD Apply Material Outline NEW - Order #$order_id: ⚠️ WARNING - No additional paths created by stroke-to-path (may not have expanded)");
                    }
                }
            }
            
            if ($pattern_fills_after > $pattern_fills) {
                error_log("APD Apply Material Outline NEW - Order #$order_id: ✅ SUCCESS - Material outline applied as fills");
                
                // STEP 4: Apply pattern masks to paths for proper CorelDraw display (thay clipPath bằng mask)
                error_log("APD Apply Material Outline NEW - Order #$order_id: Applying pattern masks to paths (CorelDraw compatible)...");
                try {
                    $converted_content = $this->apply_pattern_mask_to_paths($converted_content, $order_id);
                } catch (Exception $e) {
                    error_log("APD Apply Material Outline NEW - Order #$order_id: ⚠️ Exception in apply_pattern_mask_to_paths: " . $e->getMessage());
                    // Continue without masking
                }
                
                unlink($temp_svg_input);
                unlink($temp_svg_output);
                return $converted_content;
            } else if ($pattern_strokes_after > 0) {
                error_log("APD Apply Material Outline NEW - Order #$order_id: ⚠️ Some pattern strokes remain, trying manual conversion");
                // Try manual conversion as fallback
                $converted_content = $this->apply_material_outline_manual($converted_content, $order_id);
                
                // Apply pattern masks after manual conversion (thay clipPath bằng mask)
                try {
                    $converted_content = $this->apply_pattern_mask_to_paths($converted_content, $order_id);
                } catch (Exception $e) {
                    error_log("APD Apply Material Outline NEW - Order #$order_id: ⚠️ Exception in apply_pattern_mask_to_paths: " . $e->getMessage());
                }
            }
            
            unlink($temp_svg_input);
            unlink($temp_svg_output);
            return $converted_content;
        }
        
        // Clean up and return original if conversion failed
        unlink($temp_svg_input);
        if (file_exists($temp_svg_output)) {
            unlink($temp_svg_output);
        }
        
        error_log("APD Apply Material Outline NEW - Order #$order_id: ⚠️ stroke-to-path failed, using manual conversion");
        return $this->apply_material_outline_manual($svg_content, $order_id);
    }

    /**
     * Manual material outline application (fallback when Inkscape not available)
     * Converts pattern strokes to pattern fills on paths
     * 
     * @param string $svg_content SVG content
     * @param int $order_id Order ID for logging
     * @return string SVG content with material outline applied manually
     */
    private function apply_material_outline_manual($svg_content, $order_id = 0)
    {
        error_log("APD Apply Material Outline Manual - Order #$order_id: Applying material outline manually");
        
        // Convert pattern strokes to fills on paths
        $svg_content = preg_replace_callback(
            '/<path([^>]*)>/i',
            function($matches) use ($order_id) {
                $attrs = $matches[1];
                
                // If path has pattern stroke but no pattern fill, copy stroke to fill
                if (preg_match('/stroke=["\']url\(([^)]+)\)["\']/i', $attrs, $stroke_match)) {
                    $pattern_ref = $stroke_match[1];
                    
                    // Check if already has pattern fill
                    if (!preg_match('/fill=["\']url\([^)]+\)["\']/i', $attrs)) {
                        // Add pattern as fill
                        $attrs .= ' fill="url(' . htmlspecialchars($pattern_ref, ENT_QUOTES) . ')"';
                    }
                }
                
                return '<path' . $attrs . '>';
            },
            $svg_content
        );
        
        $pattern_fills_after = preg_match_all('/fill=["\']url\(#[^)]+\)["\']/i', $svg_content);
        error_log("APD Apply Material Outline Manual - Order #$order_id: Created $pattern_fills_after pattern fills");
        
        // Apply pattern masks after manual conversion (thay clipPath bằng mask)
        try {
            $svg_content = $this->apply_pattern_mask_to_paths($svg_content, $order_id);
        } catch (Exception $e) {
            error_log("APD Apply Material Outline Manual - Order #$order_id: ⚠️ Exception in apply_pattern_mask_to_paths: " . $e->getMessage());
        }
        
        return $svg_content;
    }

    /**
     * Manual expansion fallback for material outline
     * Creates expanded outline paths using scale transform and masks (similar to client-side approach)
     * Used when Inkscape stroke-to-path fails
     * 
     * @param string $svg_content SVG content after failed stroke-to-path
     * @param array $paths_with_strokes_before Backed up paths with pattern strokes
     * @param int $order_id Order ID for logging
     * @return string SVG content with manually expanded outline paths
     */
    private function apply_material_outline_manual_expansion($svg_content, $paths_with_strokes_before, $order_id = 0)
    {
        error_log("APD Manual Expansion - Order #$order_id: Starting manual expansion for " . count($paths_with_strokes_before) . " paths");
        
        // Group paths by pattern_id
        $paths_by_pattern = array();
        foreach ($paths_with_strokes_before as $path_info) {
            $pattern_id = $path_info['pattern_id'];
            if (!isset($paths_by_pattern[$pattern_id])) {
                $paths_by_pattern[$pattern_id] = array();
            }
            $paths_by_pattern[$pattern_id][] = $path_info;
        }
        
        // Ensure <defs> exists for masks
        if (!preg_match('/<defs[^>]*>/i', $svg_content)) {
            $svg_content = preg_replace('/(<svg[^>]*>)/i', '$1' . "\n<defs></defs>", $svg_content, 1);
        }
        
        $mask_counter = 0;
        $paths_expanded = 0;
        
        // For each pattern, create expanded outline paths
        foreach ($paths_by_pattern as $pattern_id => $paths) {
            error_log("APD Manual Expansion - Order #$order_id: Processing $pattern_id with " . count($paths) . " paths");
            
            // Find paths in SVG that match backed-up paths
            foreach ($paths as $path_info) {
                $path_data = $path_info['path_data'];
                $stroke_width = $path_info['stroke_width'];
                
                // Find matching path in SVG
                $path_pattern = '/<path([^>]*d=["\']' . preg_quote($path_data, '/') . '["\'][^>]*)>/i';
                if (preg_match($path_pattern, $svg_content, $path_match)) {
                    $path_attrs = $path_match[1];
                    
                    // Skip if already has pattern fill and mask (already processed)
                    if (preg_match('/fill=["\']url\(#' . preg_quote($pattern_id, '/') . '\)["\']/i', $path_attrs) && 
                        preg_match('/mask=/i', $path_attrs)) {
                        continue;
                    }
                    
                    // Extract path attributes
                    $fill = '';
                    $transform = '';
                    if (preg_match('/fill=["\']([^"\']+)["\']/', $path_attrs, $f_match)) {
                        $fill = $f_match[1];
                    }
                    if (preg_match('/transform=["\']([^"\']+)["\']/', $path_attrs, $t_match)) {
                        $transform = $t_match[1];
                    }
                    
                    // Create expanded path with scale transform (similar to client-side per-character scaling)
                    // Calculate scale factor from stroke-width (approximate)
                    $scale_factor = 1.0 + ($stroke_width / 100.0); // Simple scaling based on stroke-width
                    
                    // Create expanded path element
                    $mask_id = 'manual-outline-mask-' . $order_id . '-' . (++$mask_counter);
                    
                    // Create mask to cut out center (original path)
                    $mask_element = '<mask id="' . htmlspecialchars($mask_id, ENT_QUOTES) . '">' . "\n";
                    $mask_element .= '  <rect x="-1000" y="-1000" width="2000" height="2000" fill="white"/>' . "\n";
                    $mask_element .= '  <path d="' . htmlspecialchars($path_data, ENT_QUOTES) . '" fill="black"/>' . "\n";
                    $mask_element .= '</mask>';
                    
                    // Insert mask into <defs>
                    $svg_content = preg_replace('/(<defs[^>]*>)/i', '$1' . "\n  " . $mask_element . "\n", $svg_content, 1);
                    
                    // Create expanded path with pattern fill and mask
                    $expanded_path = '<path d="' . htmlspecialchars($path_data, ENT_QUOTES) . '"';
                    $expanded_path .= ' fill="url(#' . htmlspecialchars($pattern_id, ENT_QUOTES) . ')"';
                    $expanded_path .= ' stroke="none"';
                    $expanded_path .= ' mask="url(#' . htmlspecialchars($mask_id, ENT_QUOTES) . ')"';
                    
                    // Add scale transform to expand path
                    // Calculate center point (approximate from path data)
                    // For simplicity, use scale from origin (0,0) or calculate bounding box
                    if ($transform) {
                        $expanded_path .= ' transform="' . htmlspecialchars($transform, ENT_QUOTES) . ' scale(' . number_format($scale_factor, 4) . ')"';
                    } else {
                        $expanded_path .= ' transform="scale(' . number_format($scale_factor, 4) . ')"';
                    }
                    
                    $expanded_path .= '/>';
                    
                    // Insert expanded path before original path
                    $svg_content = preg_replace(
                        '/(<path[^>]*d=["\']' . preg_quote($path_data, '/') . '["\'][^>]*>)/i',
                        $expanded_path . "\n" . '$1',
                        $svg_content,
                        1
                    );
                    
                    $paths_expanded++;
                }
            }
        }
        
        error_log("APD Manual Expansion - Order #$order_id: ✅ Created $paths_expanded expanded outline paths with $mask_counter masks");
        
        return $svg_content;
    }

    /**
     * Convert images/logos to paths - NEW VERSION
     * Handles SVG images (inline) and raster images (trace with Inkscape if available)
     * 
     * @param string $svg_content SVG content
     * @param int $order_id Order ID for logging
     * @return string SVG content with images converted to paths
     */
    private function convert_images_to_paths($svg_content, $order_id = 0)
    {
        error_log("APD Convert Images to Paths NEW - Order #$order_id: Starting image conversion");
        
        $image_count = preg_match_all('/<image[^>]*>/i', $svg_content);
        if ($image_count === 0) {
            error_log("APD Convert Images to Paths NEW - Order #$order_id: No image elements found");
            return $svg_content;
        }
        
        error_log("APD Convert Images to Paths NEW - Order #$order_id: Found $image_count image elements");
        
        // Process each image element
        $svg_content = preg_replace_callback(
            '/<image([^>]*)>/i',
            function($matches) use ($order_id) {
                $img_attrs = $matches[1];
                
                // Extract href/xlink:href
                $href = '';
                if (preg_match('/(?:href|xlink:href)=["\']([^"\']+)["\']/', $img_attrs, $href_match)) {
                    $href = $href_match[1];
                }
                
                // Extract transform and positioning
                $transform = '';
                $x = '0';
                $y = '0';
                $width = '100';
                $height = '100';
                
                if (preg_match('/transform=["\']([^"\']+)["\']/', $img_attrs, $t_match)) {
                    $transform = $t_match[1];
                }
                if (preg_match('/x=["\']([^"\']+)["\']/', $img_attrs, $x_match)) {
                    $x = $x_match[1];
                }
                if (preg_match('/y=["\']([^"\']+)["\']/', $img_attrs, $y_match)) {
                    $y = $y_match[1];
                }
                if (preg_match('/width=["\']([^"\']+)["\']/', $img_attrs, $w_match)) {
                    $width = $w_match[1];
                }
                if (preg_match('/height=["\']([^"\']+)["\']/', $img_attrs, $h_match)) {
                    $height = $h_match[1];
                }
                
                // Check if it's SVG (data:image/svg+xml)
                if (strpos($href, 'data:image/svg+xml') === 0) {
                    error_log("APD Convert Images to Paths NEW - Order #$order_id: Found SVG image, extracting...");
                    
                    // Extract SVG content from data URI
                    $svg_data = '';
                    if (strpos($href, 'base64,') !== false) {
                        $svg_data = base64_decode(substr($href, strpos($href, 'base64,') + 7));
                    } else {
                        $svg_data = urldecode(substr($href, strpos($href, ',') + 1));
                    }
                    
                    // Extract paths from embedded SVG
                    if (preg_match_all('/<path[^>]*d=["\']([^"\']+)["\'][^>]*>/i', $svg_data, $path_matches)) {
                        $paths = $path_matches[0];
                        error_log("APD Convert Images to Paths NEW - Order #$order_id: Extracted " . count($paths) . " paths from SVG image");
                        
                        // Create group with transform
                        $group = '<g';
                        if ($transform) {
                            $group .= ' transform="' . htmlspecialchars($transform, ENT_QUOTES) . '"';
                        }
                        $group .= '>';
                        
                        // Add all paths
                        foreach ($paths as $path) {
                            $group .= $path;
                        }
                        
                        $group .= '</g>';
                        
                        return $group;
                    }
                }
                
                // For raster images, we would need Inkscape trace, but that's complex
                // For now, keep raster images as-is (they'll be embedded in PDF)
                error_log("APD Convert Images to Paths NEW - Order #$order_id: Raster image kept as-is (not converted to paths)");
                return $matches[0]; // Return original image element
            },
            $svg_content
        );
        
        $image_count_after = preg_match_all('/<image[^>]*>/i', $svg_content);
        error_log("APD Convert Images to Paths NEW - Order #$order_id: After conversion - $image_count_after image elements remaining");
        
        return $svg_content;
    }

    /**
     * Convert SVG to PDF - NEW VERSION
     * Optimized for CorelDraw compatibility
     * 
     * @param string $svg_content SVG content
     * @param int $order_id Order ID for logging
     * @return string|WP_Error PDF content or error
     */
    private function svg_to_pdf_new($svg_content, $order_id = 0)
    {
        error_log("APD SVG to PDF NEW - Order #$order_id: Starting PDF generation");
        
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
        
        // Check if Inkscape is available
        $inkscape_path = APD_SVG_Utils::find_inkscape();
        if ($inkscape_path) {
            try {
                return $this->convert_svg_to_pdf_with_inkscape_new($svg_content, $width, $height, $order_id, $inkscape_path);
            } catch (Exception $e) {
                error_log("APD SVG to PDF NEW - Order #$order_id: ⚠️ Exception in Inkscape conversion: " . $e->getMessage());
                // Fall through to fallback
            }
        }
        
        // Fallback: Use existing method (may use ImageMagick or client-side)
        error_log("APD SVG to PDF NEW - Order #$order_id: Inkscape not found or failed, using fallback");
        try {
            return $this->create_pdf_with_svg($svg_content, base64_encode($svg_content), $width, $height, $viewBox, $order_id);
        } catch (Exception $e) {
            error_log("APD SVG to PDF NEW - Order #$order_id: ⚠️ Exception in fallback: " . $e->getMessage());
            // Return error for client-side fallback
            $error = new WP_Error('pdf_generation_failed', 'PDF generation failed: ' . $e->getMessage());
            $error->add_data(array(
                'use_client_side' => true,
                'svg_content' => $svg_content,
                'order_id' => $order_id
            ));
            return $error;
        }
    }

    /**
     * Convert SVG to PDF with Inkscape - NEW VERSION
     * Optimized command for CorelDraw compatibility
     * 
     * @param string $svg_content SVG content
     * @param float $width SVG width
     * @param float $height SVG height
     * @param int $order_id Order ID
     * @param string $inkscape_path Inkscape executable path
     * @return string|WP_Error PDF content or error
     */
    private function convert_svg_to_pdf_with_inkscape_new($svg_content, $width, $height, $order_id, $inkscape_path)
    {
        // Save SVG temporarily
        $upload_dir = wp_upload_dir();
        $temp_svg = $upload_dir['path'] . '/temp-pdf-' . $order_id . '-' . time() . '.svg';
        $temp_pdf = $upload_dir['path'] . '/temp-pdf-' . $order_id . '-' . time() . '.pdf';
        
        file_put_contents($temp_svg, $svg_content);
        
        // Optimized Inkscape command for CorelDraw
        // --export-area-page: Preserve exact canvas dimensions
        // --export-pdf-version=1.4: CorelDraw compatibility
        // --export-text-to-path: Extra safety (though text should already be converted)
        $command = escapeshellarg($inkscape_path) . 
                   ' --export-type=pdf' .
                   ' --export-filename=' . escapeshellarg($temp_pdf) .
                   ' --export-area-page' .
                   ' --export-ignore-filters' .
                   ' --export-pdf-version=1.4' .
                   ' --export-text-to-path' .
                   ' ' . escapeshellarg($temp_svg) . 
                   ' 2>&1';
        
        error_log("APD PDF NEW - Order #$order_id: Running Inkscape command: " . $command);
        
        $output = shell_exec($command);
        $return_code = 0;
        exec($command . '; echo $?', $output_array, $return_code);
        
        if (file_exists($temp_pdf) && filesize($temp_pdf) > 0) {
            $pdf_content = file_get_contents($temp_pdf);
            
            // Verify PDF contains vector data
            $pdf_size = strlen($pdf_content);
            $is_vector = (strpos($pdf_content, '/Type /XObject') !== false || 
                         strpos($pdf_content, '/Subtype /Form') !== false ||
                         strpos($pdf_content, '/Length') !== false);
            
            error_log("APD PDF NEW - Order #$order_id: ✅ PDF generated successfully");
            error_log("  - PDF size: $pdf_size bytes");
            error_log("  - Contains vector data: " . ($is_vector ? 'YES' : 'WARNING: May be rasterized'));
            
            unlink($temp_svg);
            unlink($temp_pdf);
            
            return $pdf_content;
        } else {
            unlink($temp_svg);
            if (file_exists($temp_pdf)) unlink($temp_pdf);
            
            error_log("APD PDF NEW - Order #$order_id: ❌ Inkscape conversion failed. Output: " . ($output ?: 'No output'));
            return new WP_Error('inkscape_failed', 'Inkscape conversion failed. Output: ' . ($output ?: 'No output'));
        }
    }

    /**
     * Clip pattern images to paths - NEW VERSION
     * Clips pattern images (PNG/JPEG) to match path shape for proper CorelDraw display
     * 
     * @param string $svg_content SVG content with paths and pattern fills
     * @param int $order_id Order ID for logging
     * @return string SVG content with clipPaths applied
     */
    private function clip_pattern_images_to_paths($svg_content, $order_id = 0)
    {
        error_log("APD Clip Pattern Images - Order #$order_id: Starting pattern image clipping");
        
        // Find all paths with pattern fills
        $paths_with_patterns = preg_match_all('/<path([^>]*fill=["\']url\(#[^)]+\)["\'][^>]*)>/i', $svg_content);
        if ($paths_with_patterns === 0) {
            error_log("APD Clip Pattern Images - Order #$order_id: No paths with pattern fills found");
            return $svg_content;
        }
        
        error_log("APD Clip Pattern Images - Order #$order_id: Found $paths_with_patterns paths with pattern fills");
        
        // Ensure defs exists
        if (!preg_match('/<defs[^>]*>/i', $svg_content)) {
            // Insert defs after opening svg tag
            $svg_content = preg_replace('/(<svg[^>]*>)/i', '$1' . "\n<defs></defs>", $svg_content, 1);
        }
        
        $clip_path_counter = 0;
        $clip_paths_to_add = array();
        
        // Process each path with pattern fill
        $svg_content = preg_replace_callback(
            '/<path([^>]*fill=["\']url\(#([^)]+)\)["\'][^>]*)>/i',
            function($matches) use (&$clip_path_counter, &$clip_paths_to_add, $order_id) {
                $attrs = $matches[1];
                $pattern_id = $matches[2];
                
                // Extract path data (d attribute)
                $path_data = '';
                if (preg_match('/d=["\']([^"\']+)["\']/', $attrs, $d_match)) {
                    $path_data = $d_match[1];
                } else {
                    // No path data, skip
                    return $matches[0];
                }
                
                // Check if already has clip-path
                if (preg_match('/clip-path=/i', $attrs)) {
                    // Already clipped, skip
                    return $matches[0];
                }
                
                // Create clipPath ID
                $clip_path_id = 'clip-path-' . $order_id . '-' . (++$clip_path_counter);
                
                // Create clipPath definition
                $clip_path_def = '<clipPath id="' . htmlspecialchars($clip_path_id, ENT_QUOTES) . '">' .
                               '<path d="' . htmlspecialchars($path_data, ENT_QUOTES) . '"/>' .
                               '</clipPath>';
                
                // Store clipPath to add later
                $clip_paths_to_add[] = "\n  " . $clip_path_def;
                
                // Add clip-path attribute to path
                $new_attrs = $attrs . ' clip-path="url(#' . htmlspecialchars($clip_path_id, ENT_QUOTES) . ')"';
                
                error_log("APD Clip Pattern Images - Order #$order_id: Created clipPath $clip_path_id for pattern $pattern_id");
                
                return '<path' . $new_attrs . '>';
            },
            $svg_content
        );
        
        // Insert clipPaths into defs
        if (!empty($clip_paths_to_add)) {
            $clip_paths_str = implode('', $clip_paths_to_add);
            $svg_content = preg_replace('/(<defs[^>]*>)/i', '$1' . $clip_paths_str, $svg_content, 1);
        }
        
        // Verify clipPaths were created
        $clip_path_count = preg_match_all('/<clipPath[^>]*>/i', $svg_content);
        $clip_path_refs = preg_match_all('/clip-path=["\']url\(#[^)]+\)["\']/i', $svg_content);
        
        error_log("APD Clip Pattern Images - Order #$order_id: Created $clip_path_count clipPaths, $clip_path_refs clip-path references");
        
        return $svg_content;
    }

    /**
     * Preserve custom text material outline - NEW VERSION
     * Ensures custom text patterns (apdTextPattern, text-material-pattern-*) are preserved
     * 
     * @param string $svg_content SVG content
     * @param int $order_id Order ID for logging
     * @return array Array with 'svg' => processed SVG, 'patterns' => backup patterns
     */
    private function preserve_custom_text_material_outline($svg_content, $order_id = 0)
    {
        error_log("APD Preserve Custom Text Material - Order #$order_id: Starting custom text pattern preservation");
        
        $pattern_backup = array();
        
        // Detect custom text patterns (apdTextPattern, text-material-pattern-*)
        $pattern_ids_to_preserve = array('apdTextPattern');
        
        // Find all text-material-pattern-* patterns
        if (preg_match_all('/<pattern[^>]*id=["\'](text-material-pattern-[^"\']+)["\'][^>]*>/i', $svg_content, $text_pattern_matches)) {
            $pattern_ids_to_preserve = array_merge($pattern_ids_to_preserve, $text_pattern_matches[1]);
        }
        
        error_log("APD Preserve Custom Text Material - Order #$order_id: Found " . count($pattern_ids_to_preserve) . " custom text pattern IDs to preserve");
        
        // Backup pattern definitions với PNG/JPEG images
        foreach ($pattern_ids_to_preserve as $pattern_id) {
            // Match pattern with its full content including image
            $pattern_regex = '/<pattern[^>]*id=["\']' . preg_quote($pattern_id, '/') . '["\'][^>]*>(.*?)<\/pattern>/is';
            if (preg_match($pattern_regex, $svg_content, $pattern_match)) {
                $pattern_full = $pattern_match[0];
                
                // Verify pattern contains image (PNG/JPEG)
                if (preg_match('/<image[^>]*href=["\']data:image\/(png|jpeg|jpg);base64/i', $pattern_full)) {
                    $pattern_backup[$pattern_id] = $pattern_full;
                    error_log("APD Preserve Custom Text Material - Order #$order_id: Backed up pattern $pattern_id with PNG/JPEG image");
                } else {
                    error_log("APD Preserve Custom Text Material - Order #$order_id: Pattern $pattern_id found but no PNG/JPEG image detected");
                }
            }
        }
        
        // Also backup any pattern that has apdTextPattern in references
        $apd_text_pattern_refs = preg_match_all('/url\(#apdTextPattern\)/i', $svg_content);
        if ($apd_text_pattern_refs > 0) {
            error_log("APD Preserve Custom Text Material - Order #$order_id: Found $apd_text_pattern_refs references to apdTextPattern");
            
            // Ensure apdTextPattern is in backup
            if (!isset($pattern_backup['apdTextPattern'])) {
                // Try to find it with different regex
                if (preg_match('/<pattern[^>]*id=["\']apdTextPattern["\'][^>]*>(.*?)<\/pattern>/is', $svg_content, $apd_match)) {
                    $pattern_backup['apdTextPattern'] = $apd_match[0];
                    error_log("APD Preserve Custom Text Material - Order #$order_id: Found and backed up apdTextPattern");
                }
            }
        }
        
        error_log("APD Preserve Custom Text Material - Order #$order_id: Total patterns backed up: " . count($pattern_backup));
        
        return array(
            'svg' => $svg_content,
            'patterns' => $pattern_backup
        );
    }

    /**
     * Restore custom text material outline patterns
     * Restores backed-up patterns if they were lost during conversion
     * 
     * @param string $svg_content SVG content after conversion
     * @param array $pattern_backup Backed-up patterns
     * @param int $order_id Order ID for logging
     * @return string SVG content with patterns restored
     */
    private function restore_custom_text_material_outline($svg_content, $pattern_backup, $order_id = 0)
    {
        if (empty($pattern_backup)) {
            return $svg_content;
        }
        
        error_log("APD Restore Custom Text Material - Order #$order_id: Restoring " . count($pattern_backup) . " patterns");
        
        $patterns_restored = 0;
        
        foreach ($pattern_backup as $pattern_id => $pattern_full) {
            // Check if pattern still exists
            $pattern_exists = (strpos($svg_content, 'id="' . $pattern_id . '"') !== false || 
                              strpos($svg_content, "id='" . $pattern_id . "'") !== false);
            
            if (!$pattern_exists) {
                error_log("APD Restore Custom Text Material - Order #$order_id: Restoring lost pattern: $pattern_id");
                
                // Insert pattern in defs if exists, otherwise before </svg>
                if (preg_match('/<defs[^>]*>/i', $svg_content)) {
                    $svg_content = preg_replace('/<defs[^>]*>/i', '$0' . "\n  " . $pattern_full, $svg_content, 1);
                } else {
                    // Create defs and insert pattern
                    if (preg_match('/(<svg[^>]*>)/i', $svg_content, $svg_match)) {
                        $defs_with_pattern = "\n<defs>\n  " . $pattern_full . "\n</defs>";
                        $svg_content = str_replace($svg_match[0], $svg_match[0] . $defs_with_pattern, $svg_content);
                    } else {
                        // Fallback: insert before </svg>
                        $svg_content = str_replace('</svg>', $pattern_full . "\n</svg>", $svg_content);
                    }
                }
                $patterns_restored++;
            }
        }
        
        if ($patterns_restored > 0) {
            error_log("APD Restore Custom Text Material - Order #$order_id: ✅ Restored $patterns_restored lost patterns");
        } else {
            error_log("APD Restore Custom Text Material - Order #$order_id: All patterns already present");
        }
        
        return $svg_content;
    }

    /**
     * Process pattern images for PDF - NEW VERSION
     * Ensures pattern images (PNG/JPEG) are properly embedded for PDF export
     * 
     * @param string $svg_content SVG content
     * @param int $order_id Order ID for logging
     * @return string SVG content with processed pattern images
     */
    private function process_pattern_images_for_pdf($svg_content, $order_id = 0)
    {
        error_log("APD Process Pattern Images PDF - Order #$order_id: Starting pattern image processing");
        
        // Find all pattern definitions với image elements
        $pattern_count = preg_match_all('/<pattern[^>]*>/i', $svg_content);
        error_log("APD Process Pattern Images PDF - Order #$order_id: Found $pattern_count pattern definitions");
        
        // Process each pattern
        $svg_content = preg_replace_callback(
            '/<pattern([^>]*)>(.*?)<\/pattern>/is',
            function($matches) use ($order_id) {
                $pattern_attrs = $matches[1];
                $pattern_content = $matches[2];
                
                // Extract pattern ID
                $pattern_id = '';
                if (preg_match('/id=["\']([^"\']+)["\']/', $pattern_attrs, $id_match)) {
                    $pattern_id = $id_match[1];
                }
                
                // Process image elements in pattern
                $pattern_content = preg_replace_callback(
                    '/<image([^>]*)>/i',
                    function($img_matches) use ($pattern_id, $order_id) {
                        $img_attrs = $img_matches[1];
                        
                        // Check if image already has data URI
                        if (preg_match('/(?:href|xlink:href)=["\']data:image\/(png|jpeg|jpg);base64,([^"\']+)["\']/i', $img_attrs, $data_match)) {
                            // Already embedded - verify format
                            $format = strtolower($data_match[1]);
                            error_log("APD Process Pattern Images PDF - Order #$order_id: Pattern $pattern_id already has embedded $format image");
                            return $img_matches[0];
                        }
                        
                        // Check if image has external URL
                        if (preg_match('/(?:href|xlink:href)=["\']([^"\']+)["\']/', $img_attrs, $url_match)) {
                            $image_url = $url_match[1];
                            
                            // Try to fetch and convert to data URI
                            if (strpos($image_url, 'http') === 0 || strpos($image_url, '/') === 0) {
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
                                        '/(?:href|xlink:href)=["\'][^"\']*["\']/',
                                        'href="' . $data_uri . '" xlink:href="' . $data_uri . '"',
                                        $img_attrs
                                    );
                                    
                                    error_log("APD Process Pattern Images PDF - Order #$order_id: Converted pattern $pattern_id image to embedded data URI");
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
                                            '/(?:href|xlink:href)=["\'][^"\']*["\']/',
                                            'href="' . $data_uri . '" xlink:href="' . $data_uri . '"',
                                            $img_attrs
                                        );
                                        
                                        error_log("APD Process Pattern Images PDF - Order #$order_id: Fetched and embedded pattern $pattern_id image");
                                        return '<image' . $new_attrs . '>';
                                    }
                                }
                            }
                        }
                        
                        return $img_matches[0];
                    },
                    $pattern_content
                );
                
                // Ensure pattern units are correct
                $pattern_attrs_new = $pattern_attrs;
                if (!preg_match('/patternUnits=/i', $pattern_attrs_new)) {
                    $pattern_attrs_new .= ' patternUnits="userSpaceOnUse"';
                }
                if (!preg_match('/patternContentUnits=/i', $pattern_attrs_new)) {
                    $pattern_attrs_new .= ' patternContentUnits="userSpaceOnUse"';
                }
                
                return '<pattern' . $pattern_attrs_new . '>' . $pattern_content . '</pattern>';
            },
            $svg_content
        );
        
        // Verify pattern images
        $embedded_images = preg_match_all('/data:image\/(png|jpeg|jpg);base64/i', $svg_content);
        $png_images = preg_match_all('/data:image\/png;base64/i', $svg_content);
        $jpeg_images = preg_match_all('/data:image\/(jpeg|jpg);base64/i', $svg_content);
        
        // Count patterns with embedded images
        $patterns_with_images = preg_match_all('/<pattern[^>]*>.*?<image[^>]*data:image\/(png|jpeg|jpg);base64[^>]*>.*?<\/pattern>/is', $svg_content);
        
        error_log("APD Process Pattern Images PDF - Order #$order_id: After processing - $embedded_images embedded pattern images");
        error_log("APD Process Pattern Images PDF - Order #$order_id: Pattern image breakdown:");
        error_log("  - PNG images: $png_images");
        error_log("  - JPEG images: $jpeg_images");
        error_log("  - Patterns with embedded images: $patterns_with_images");
        
        if ($pattern_count > 0 && $patterns_with_images < $pattern_count) {
            error_log("APD Process Pattern Images PDF - Order #$order_id: ⚠️ WARNING - Some patterns may not have embedded images!");
        } else if ($pattern_count > 0 && $patterns_with_images === $pattern_count) {
            error_log("APD Process Pattern Images PDF - Order #$order_id: ✅ All patterns have embedded PNG/JPEG images");
        }
        
        return $svg_content;
    }

    /**
     * Track custom text elements với apdTextPattern - NEW VERSION
     * Tracks text elements với material outline pattern trước khi convert
     * 
     * @param string $svg_content SVG content
     * @param int $order_id Order ID for logging
     * @return array Array of custom text element info
     */
    private function track_custom_text_elements($svg_content, $order_id = 0)
    {
        error_log("APD Track Custom Text - Order #$order_id: Tracking custom text elements with apdTextPattern");
        
        $custom_text_elements = array();
        
        // Find all text elements với apdTextPattern
        if (preg_match_all('/<text([^>]*stroke=["\']url\(#apdTextPattern\)["\'][^>]*)>(.*?)<\/text>/is', $svg_content, $text_matches, PREG_SET_ORDER)) {
            foreach ($text_matches as $idx => $match) {
                $attrs = $match[1];
                $text_content = $match[2];
                
                // Extract position và transform
                $x = '0';
                $y = '0';
                $transform = '';
                $stroke_width = '6';
                $fill = '';
                
                if (preg_match('/x=["\']([^"\']+)["\']/', $attrs, $x_match)) {
                    $x = $x_match[1];
                }
                if (preg_match('/y=["\']([^"\']+)["\']/', $attrs, $y_match)) {
                    $y = $y_match[1];
                }
                if (preg_match('/transform=["\']([^"\']+)["\']/', $attrs, $t_match)) {
                    $transform = $t_match[1];
                }
                if (preg_match('/stroke-width=["\']([^"\']+)["\']/', $attrs, $sw_match)) {
                    $stroke_width = $sw_match[1];
                }
                if (preg_match('/fill=["\']([^"\']+)["\']/', $attrs, $f_match)) {
                    $fill = $f_match[1];
                }
                
                $text_clean = trim(strip_tags($text_content));
                
                $custom_text_elements[] = array(
                    'pattern' => 'apdTextPattern',
                    'text' => $text_clean,
                    'x' => $x,
                    'y' => $y,
                    'transform' => $transform,
                    'stroke_width' => $stroke_width,
                    'fill' => $fill,
                    'index' => $idx
                );
                
                error_log("APD Track Custom Text - Order #$order_id: Tracked text #$idx: '$text_clean' at ($x, $y) with apdTextPattern");
            }
        }
        
        // Also find text-material-pattern-* patterns
        if (preg_match_all('/<text([^>]*stroke=["\']url\(#(text-material-pattern-[^)]+)\)["\'][^>]*)>(.*?)<\/text>/is', $svg_content, $text_pattern_matches, PREG_SET_ORDER)) {
            foreach ($text_pattern_matches as $idx => $match) {
                $attrs = $match[1];
                $pattern_id = $match[2];
                $text_content = $match[3];
                
                $x = '0';
                $y = '0';
                $transform = '';
                $stroke_width = '6';
                $fill = '';
                
                if (preg_match('/x=["\']([^"\']+)["\']/', $attrs, $x_match)) {
                    $x = $x_match[1];
                }
                if (preg_match('/y=["\']([^"\']+)["\']/', $attrs, $y_match)) {
                    $y = $y_match[1];
                }
                if (preg_match('/transform=["\']([^"\']+)["\']/', $attrs, $t_match)) {
                    $transform = $t_match[1];
                }
                if (preg_match('/stroke-width=["\']([^"\']+)["\']/', $attrs, $sw_match)) {
                    $stroke_width = $sw_match[1];
                }
                if (preg_match('/fill=["\']([^"\']+)["\']/', $attrs, $f_match)) {
                    $fill = $f_match[1];
                }
                
                $text_clean = trim(strip_tags($text_content));
                
                $custom_text_elements[] = array(
                    'pattern' => $pattern_id,
                    'text' => $text_clean,
                    'x' => $x,
                    'y' => $y,
                    'transform' => $transform,
                    'stroke_width' => $stroke_width,
                    'fill' => $fill,
                    'index' => count($custom_text_elements)
                );
                
                error_log("APD Track Custom Text - Order #$order_id: Tracked text with pattern $pattern_id: '$text_clean'");
            }
        }
        
        error_log("APD Track Custom Text - Order #$order_id: Total custom text elements tracked: " . count($custom_text_elements));
        
        return $custom_text_elements;
    }

    /**
     * Apply custom text pattern to converted paths - NEW VERSION
     * Applies apdTextPattern lên paths từ custom text sau khi convert
     * 
     * @param string $svg_content SVG content after conversion
     * @param array $custom_text_elements Tracked custom text elements
     * @param int $order_id Order ID for logging
     * @return string SVG content with patterns applied
     */
    private function apply_custom_text_pattern_to_paths($svg_content, $custom_text_elements, $order_id = 0)
    {
        if (empty($custom_text_elements)) {
            return $svg_content;
        }
        
        error_log("APD Apply Custom Text Pattern - Order #$order_id: Applying patterns to " . count($custom_text_elements) . " custom text paths");
        
        // Find all paths that might be from custom text
        // Strategy: Apply pattern to paths that don't have pattern fills yet
        // and are likely from text conversion (new paths created)
        
        $paths_modified = 0;
        $pattern_applied_count = 0;
        
        // Get stroke-width from first custom text element (usually all have same stroke-width)
        $default_stroke_width = '48'; // Default fallback
        if (!empty($custom_text_elements) && isset($custom_text_elements[0]['stroke_width'])) {
            $default_stroke_width = $custom_text_elements[0]['stroke_width'];
            error_log("APD Apply Custom Text Pattern - Order #$order_id: Using stroke-width from original text: $default_stroke_width");
        }
        
        // For each custom text element, try to find corresponding paths
        foreach ($custom_text_elements as $text_info) {
            $pattern_id = $text_info['pattern'];
            $text_content = $text_info['text'];
            $stroke_width = isset($text_info['stroke_width']) ? $text_info['stroke_width'] : $default_stroke_width;
            
            // Find paths in groups or near the text position
            // Since Inkscape converts text to paths, we need to find paths that:
            // 1. Don't have pattern fills yet
            // 2. Are in the same group or position as the original text
            
            // Strategy: Apply pattern to ALL paths that don't have pattern fills
            // This is safer than trying to match positions exactly
            $svg_content = preg_replace_callback(
                '/<path([^>]*)>/i',
                function($matches) use ($pattern_id, $stroke_width, &$paths_modified, &$pattern_applied_count, $order_id) {
                    $attrs = $matches[1];
                    
                    // Skip if already has pattern fill
                    if (preg_match('/fill=["\']url\(#[^)]+\)["\']/i', $attrs)) {
                        return $matches[0];
                    }
                    
                    // Skip if has clip-path or mask (already processed)
                    if (preg_match('/(clip-path|mask)=/i', $attrs)) {
                        return $matches[0];
                    }
                    
                    // Apply apdTextPattern as fill
                    $new_attrs = $attrs;
                    $new_attrs .= ' fill="url(#' . htmlspecialchars($pattern_id, ENT_QUOTES) . ')"';
                    
                    // CRITICAL: Also add stroke with stroke-width từ original text để stroke-to-path hoạt động đúng
                    // Material outline từ viền ngoài được lấy từ stroke-width đã chọn trong original text
                    if (!preg_match('/stroke=["\']url\(/i', $attrs)) {
                        $new_attrs .= ' stroke="url(#' . htmlspecialchars($pattern_id, ENT_QUOTES) . ')"';
                    }
                    
                    // Apply stroke-width từ original text (preserve material outline thickness)
                    if (!preg_match('/stroke-width=/i', $attrs)) {
                        $new_attrs .= ' stroke-width="' . htmlspecialchars($stroke_width, ENT_QUOTES) . '"';
                    }
                    
                    // Add stroke attributes for proper stroke-to-path conversion
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
                    $pattern_applied_count++;
                    
                    error_log("APD Apply Custom Text Pattern - Order #$order_id: Applied pattern with stroke-width=$stroke_width to path #$paths_modified");
                    
                    return '<path' . $new_attrs . '>';
                },
                $svg_content,
                1 // Limit to first match per text element (safer)
            );
        }
        
        // Alternative approach: Apply pattern to paths that might be from custom text
        // Strategy: Look for paths in groups that might contain converted text
        // Or apply to paths that don't have any fill/stroke yet (likely from text conversion)
        if ($pattern_applied_count === 0 && !empty($custom_text_elements)) {
            error_log("APD Apply Custom Text Pattern - Order #$order_id: Applying pattern to paths without fills (likely from custom text)");
            
            $pattern_id = $custom_text_elements[0]['pattern']; // Use first custom text pattern
            
            $svg_content = preg_replace_callback(
                '/<path([^>]*)>/i',
                function($matches) use ($pattern_id, &$paths_modified, $order_id) {
                    $attrs = $matches[1];
                    
                    // Skip if already has pattern fill
                    if (preg_match('/fill=["\']url\(#[^)]+\)["\']/i', $attrs)) {
                        return $matches[0];
                    }
                    
                    // Skip if has clip-path or mask (already processed)
                    if (preg_match('/(clip-path|mask)=/i', $attrs)) {
                        return $matches[0];
                    }
                    
                    // Only apply if path has no fill or has default fill
                    $has_fill = preg_match('/fill=/i', $attrs);
                    $fill_value = '';
                    if (preg_match('/fill=["\']([^"\']+)["\']/', $attrs, $f_match)) {
                        $fill_value = $f_match[1];
                    }
                    
                    // Apply pattern if no fill or fill is black/default
                    if (!$has_fill || $fill_value === '#000000' || $fill_value === '#000' || $fill_value === 'black') {
                        $new_attrs = $attrs;
                        if (!$has_fill) {
                            $new_attrs .= ' fill="url(#' . htmlspecialchars($pattern_id, ENT_QUOTES) . ')"';
                        } else {
                            // Replace fill with pattern
                            $new_attrs = preg_replace('/fill=["\'][^"\']*["\']/', 'fill="url(#' . htmlspecialchars($pattern_id, ENT_QUOTES) . ')"', $new_attrs);
                        }
                        
                        if (!preg_match('/stroke=["\']url\(/i', $attrs)) {
                            $new_attrs .= ' stroke="url(#' . htmlspecialchars($pattern_id, ENT_QUOTES) . ')"';
                        }
                        
                        $paths_modified++;
                        return '<path' . $new_attrs . '>';
                    }
                    
                    return $matches[0];
                },
                $svg_content
            );
        }
        
        error_log("APD Apply Custom Text Pattern - Order #$order_id: Applied pattern to $paths_modified paths");
        
        // Verify pattern was applied
        $pattern_fills_after = preg_match_all('/fill=["\']url\(#apdTextPattern\)["\']/i', $svg_content);
        error_log("APD Apply Custom Text Pattern - Order #$order_id: Pattern fills with apdTextPattern: $pattern_fills_after");
        
        return $svg_content;
    }

    /**
     * Apply pattern mask to paths - NEW VERSION
     * Thay clipPath bằng mask cho CorelDraw compatibility
     * 
     * @param string $svg_content SVG content with paths and pattern fills
     * @param int $order_id Order ID for logging
     * @return string SVG content with masks applied
     */
    private function apply_pattern_mask_to_paths($svg_content, $order_id = 0)
    {
        error_log("APD Apply Pattern Mask - Order #$order_id: Starting pattern mask application (CorelDraw compatible)");
        
        // Find all paths with pattern fills
        $paths_with_patterns = preg_match_all('/<path([^>]*fill=["\']url\(#[^)]+\)["\'][^>]*)>/i', $svg_content);
        if ($paths_with_patterns === 0) {
            error_log("APD Apply Pattern Mask - Order #$order_id: No paths with pattern fills found");
            return $svg_content;
        }
        
        error_log("APD Apply Pattern Mask - Order #$order_id: Found $paths_with_patterns paths with pattern fills");
        
        // Count paths with different patterns for logging
        $apd_text_pattern_paths = preg_match_all('/<path([^>]*fill=["\']url\(#apdTextPattern\)["\'][^>]*)>/i', $svg_content);
        $logo_material_pattern_paths = preg_match_all('/<path([^>]*fill=["\']url\(#logoMaterialPattern\)["\'][^>]*)>/i', $svg_content);
        $logo_gold_pattern_paths = preg_match_all('/<path([^>]*fill=["\']url\(#logoGoldPattern\)["\'][^>]*)>/i', $svg_content);
        
        error_log("APD Apply Pattern Mask - Order #$order_id: Pattern breakdown:");
        error_log("  - apdTextPattern paths (custom text): $apd_text_pattern_paths");
        error_log("  - logoMaterialPattern paths (logo): $logo_material_pattern_paths");
        error_log("  - logoGoldPattern paths (logo): $logo_gold_pattern_paths");
        
        // Verify pattern definitions exist before applying masks
        $pattern_defs_before_mask = preg_match_all('/<pattern[^>]*>/i', $svg_content);
        $pattern_images_before_mask = preg_match_all('/<pattern[^>]*>.*?<image[^>]*data:image\/(png|jpeg|jpg);base64[^>]*>.*?<\/pattern>/is', $svg_content);
        
        error_log("APD Apply Pattern Mask - Order #$order_id: Pattern definitions before mask application:");
        error_log("  - Pattern definitions: $pattern_defs_before_mask");
        error_log("  - Patterns with embedded PNG/JPEG images: $pattern_images_before_mask");
        
        if ($pattern_defs_before_mask === 0) {
            error_log("APD Apply Pattern Mask - Order #$order_id: ⚠️ WARNING - No pattern definitions found!");
        } else if ($pattern_images_before_mask < $pattern_defs_before_mask) {
            error_log("APD Apply Pattern Mask - Order #$order_id: ⚠️ WARNING - Some patterns may not have embedded images!");
        } else {
            error_log("APD Apply Pattern Mask - Order #$order_id: ✅ All pattern definitions have embedded PNG/JPEG images");
        }
        
        // Ensure defs exists
        if (!preg_match('/<defs[^>]*>/i', $svg_content)) {
            $svg_content = preg_replace('/(<svg[^>]*>)/i', '$1' . "\n<defs></defs>", $svg_content, 1);
        }
        
                // Extract SVG dimensions for mask bounds
                // Use larger dimensions to ensure mask covers entire pattern area
                // CorelDraw may need larger mask bounds to display patterns correctly
                $svg_width = '200%';
                $svg_height = '200%';
                $svg_viewbox = '';
                
                if (preg_match('/<svg[^>]*viewBox=["\']([^"\']+)["\']/', $svg_content, $vb_match)) {
                    $svg_viewbox = $vb_match[1];
                    $vb_parts = preg_split('/\s+/', trim($svg_viewbox));
                    if (count($vb_parts) >= 4) {
                        // Use viewBox dimensions * 3 for larger mask coverage and visible outline effect
                        $vb_width = floatval($vb_parts[2]) * 3;
                        $vb_height = floatval($vb_parts[3]) * 3;
                        $svg_width = $vb_width;
                        $svg_height = $vb_height;
                    }
                } else {
                    if (preg_match('/<svg[^>]*width=["\']([^"\']+)["\']/', $svg_content, $w_match)) {
                        $w_val = floatval(preg_replace('/[^0-9.]/', '', $w_match[1]));
                        if ($w_val > 0) {
                            $svg_width = $w_val * 3; // Triple the width for visible outline effect
                        }
                    }
                    if (preg_match('/<svg[^>]*height=["\']([^"\']+)["\']/', $svg_content, $h_match)) {
                        $h_val = floatval(preg_replace('/[^0-9.]/', '', $h_match[1]));
                        if ($h_val > 0) {
                            $svg_height = $h_val * 3; // Triple the height for visible outline effect
                        }
                    }
                }
                
                error_log("APD Apply Pattern Mask - Order #$order_id: Using mask dimensions: width=$svg_width, height=$svg_height (3x for visible outline effect in CorelDraw)");
        
        $mask_counter = 0;
        $masks_to_add = array();
        
        // Process each path with pattern fill
        $svg_content = preg_replace_callback(
            '/<path([^>]*fill=["\']url\(#([^)]+)\)["\'][^>]*)>/i',
            function($matches) use (&$mask_counter, &$masks_to_add, $order_id, $svg_width, $svg_height) {
                $attrs = $matches[1];
                $pattern_id = $matches[2];
                
                // Extract path data
                $path_data = '';
                if (preg_match('/d=["\']([^"\']+)["\']/', $attrs, $d_match)) {
                    $path_data = $d_match[1];
                } else {
                    return $matches[0];
                }
                
                // Check if already has mask
                if (preg_match('/mask=/i', $attrs)) {
                    return $matches[0];
                }
                
                // Log pattern type for debugging
                if ($pattern_id === 'apdTextPattern') {
                    error_log("APD Apply Pattern Mask - Order #$order_id: Processing custom text path with apdTextPattern (mask #" . ($mask_counter + 1) . ")");
                }
                
                // Remove clip-path if exists (we're replacing with mask)
                $attrs = preg_replace('/\s+clip-path=["\'][^"\']*["\']/i', '', $attrs);
                
                // Create mask ID
                $mask_id = 'mask-pattern-' . $order_id . '-' . (++$mask_counter);
                
                // Create mask definition
                // Mask logic: White = show, Black = hide
                // For pattern clipping: We want pattern to show ONLY inside path
                // So: White path (show pattern), black background (hide outside)
                // Use larger mask bounds and center it to ensure pattern displays correctly in CorelDraw
                // Increased mask size to accommodate larger outline (48px stroke-width)
                // Mask dimensions: 3x SVG dimensions for better coverage and visible outline effect
                // Larger mask = more visible outline in CorelDraw
                $mask_x = is_numeric($svg_width) ? (-$svg_width) : '-100%';
                $mask_y = is_numeric($svg_height) ? (-$svg_height) : '-100%';
                $mask_w = is_numeric($svg_width) ? ($svg_width * 3) : '300%'; // Increased from 1.8x to 3x for more visible outline
                $mask_h = is_numeric($svg_height) ? ($svg_height * 3) : '300%'; // Increased from 1.8x to 3x for more visible outline
                
                // Log mask dimensions for verification
                if ($pattern_id === 'apdTextPattern') {
                    error_log("APD Apply Pattern Mask - Order #$order_id: Custom text mask dimensions - x:$mask_x, y:$mask_y, w:$mask_w, h:$mask_h (3x for visible outline effect)");
                }
                
                // Create mask with larger dimensions to create visible outline effect
                // Mask logic: White = show pattern, Black = hide pattern
                // For outline effect: We want pattern to show AROUND the path, not inside
                // So: White background (large) shows pattern everywhere
                // Black path (original size) cuts out center, creating outline effect
                // Larger mask dimensions (3x) ensure outline is visible around path
                // IMPORTANT: Path trong mask phải là path gốc (không expand) để cut out center
                // Pattern fill trên path gốc sẽ hiển thị outline xung quanh
                // Create mask with scaled path to create visible outline gap
                // Scale path in mask to 70% to create larger gap/outline
                // Smaller path in mask = larger visible outline around original path
                $mask_path_scale = 0.7; // Scale to 70% to create visible outline gap
                
                // Calculate approximate center for scaling (use SVG center as approximation)
                $svg_center_x = is_numeric($svg_width) ? ($svg_width / 2) : 400; // Default to 400 if not numeric
                $svg_center_y = is_numeric($svg_height) ? ($svg_height / 2) : 300; // Default to 300 if not numeric
                
                // Create mask with scaled path to create visible outline
                // White background shows pattern everywhere
                // Black path (scaled smaller) cuts out smaller center, creating visible gap
                $mask_def = '<mask id="' . htmlspecialchars($mask_id, ENT_QUOTES) . '">' .
                           '<rect x="' . htmlspecialchars($mask_x, ENT_QUOTES) . '" y="' . htmlspecialchars($mask_y, ENT_QUOTES) . '" ' .
                           'width="' . htmlspecialchars($mask_w, ENT_QUOTES) . '" height="' . htmlspecialchars($mask_h, ENT_QUOTES) . '" fill="white"/>' .
                           '<g transform="translate(' . htmlspecialchars($svg_center_x, ENT_QUOTES) . ',' . htmlspecialchars($svg_center_y, ENT_QUOTES) . ') scale(' . $mask_path_scale . ') translate(' . htmlspecialchars(-$svg_center_x, ENT_QUOTES) . ',' . htmlspecialchars(-$svg_center_y, ENT_QUOTES) . ')">' .
                           '<path d="' . htmlspecialchars($path_data, ENT_QUOTES) . '" fill="black"/>' .
                           '</g>' .
                           '</mask>';
                
                // Store mask to add later
                $masks_to_add[] = "\n  " . $mask_def;
                
                // Add mask attribute to path
                $new_attrs = $attrs . ' mask="url(#' . htmlspecialchars($mask_id, ENT_QUOTES) . ')"';
                
                error_log("APD Apply Pattern Mask - Order #$order_id: Created mask $mask_id for pattern $pattern_id");
                
                return '<path' . $new_attrs . '>';
            },
            $svg_content
        );
        
        // Insert masks into defs
        if (!empty($masks_to_add)) {
            $masks_str = implode('', $masks_to_add);
            $svg_content = preg_replace('/(<defs[^>]*>)/i', '$1' . $masks_str, $svg_content, 1);
        }
        
        // Verify pattern definitions are still present after mask application
        $pattern_defs_after_mask = preg_match_all('/<pattern[^>]*>/i', $svg_content);
        $pattern_images_after_mask = preg_match_all('/<pattern[^>]*>.*?<image[^>]*data:image\/(png|jpeg|jpg);base64[^>]*>.*?<\/pattern>/is', $svg_content);
        
        // Verify masks were created
        $mask_count = preg_match_all('/<mask[^>]*>/i', $svg_content);
        $mask_refs = preg_match_all('/mask=["\']url\(#[^)]+\)["\']/i', $svg_content);
        
        // Verify masks for custom text paths specifically
        $custom_text_paths_with_mask = preg_match_all('/<path([^>]*fill=["\']url\(#apdTextPattern\)["\'][^>]*mask=["\']url\(#[^)]+\)["\'][^>]*)>/i', $svg_content);
        $logo_paths_with_mask = preg_match_all('/<path([^>]*fill=["\']url\(#logoMaterialPattern\)["\'][^>]*mask=["\']url\(#[^)]+\)["\'][^>]*)>/i', $svg_content);
        
        error_log("APD Apply Pattern Mask - Order #$order_id: Created $mask_count masks, $mask_refs mask references");
        error_log("APD Apply Pattern Mask - Order #$order_id: Pattern preservation verification:");
        error_log("  - Pattern definitions: $pattern_defs_after_mask (was $pattern_defs_before_mask)");
        error_log("  - Patterns with embedded PNG/JPEG images: $pattern_images_after_mask (was $pattern_images_before_mask)");
        
        if ($pattern_defs_after_mask < $pattern_defs_before_mask) {
            error_log("APD Apply Pattern Mask - Order #$order_id: ⚠️ WARNING - Pattern definitions lost during mask application!");
        } else if ($pattern_images_after_mask < $pattern_images_before_mask) {
            error_log("APD Apply Pattern Mask - Order #$order_id: ⚠️ WARNING - Pattern images lost during mask application!");
        } else {
            error_log("APD Apply Pattern Mask - Order #$order_id: ✅ Pattern definitions and PNG/JPEG images preserved");
        }
        
        error_log("APD Apply Pattern Mask - Order #$order_id: Mask verification:");
        error_log("  - Custom text paths (apdTextPattern) with mask: $custom_text_paths_with_mask");
        error_log("  - Logo paths (logoMaterialPattern) with mask: $logo_paths_with_mask");
        
        if ($custom_text_paths_with_mask > 0) {
            error_log("APD Apply Pattern Mask - Order #$order_id: ✅ Custom text paths have masks (consistent with logo)");
        } else {
            $apd_text_pattern_paths_after = preg_match_all('/<path([^>]*fill=["\']url\(#apdTextPattern\)["\'][^>]*)>/i', $svg_content);
            if ($apd_text_pattern_paths_after > 0) {
                error_log("APD Apply Pattern Mask - Order #$order_id: ⚠️ WARNING - Found $apd_text_pattern_paths_after custom text paths but none have masks");
            }
        }
        
        // Final verification: Ensure pattern definitions with PNG/JPEG are still present
        $pattern_defs_final = preg_match_all('/<pattern[^>]*>/i', $svg_content);
        $pattern_images_final = preg_match_all('/<pattern[^>]*>.*?<image[^>]*data:image\/(png|jpeg|jpg);base64[^>]*>.*?<\/pattern>/is', $svg_content);
        
        error_log("APD Apply Pattern Mask - Order #$order_id: Final pattern preservation check:");
        error_log("  - Pattern definitions: $pattern_defs_final (was $pattern_defs_before_mask)");
        error_log("  - Patterns with embedded PNG/JPEG images: $pattern_images_final (was $pattern_images_before_mask)");
        
        if ($pattern_defs_final < $pattern_defs_before_mask) {
            error_log("APD Apply Pattern Mask - Order #$order_id: ⚠️ CRITICAL WARNING - Pattern definitions lost during mask application!");
        } else if ($pattern_images_final < $pattern_images_before_mask) {
            error_log("APD Apply Pattern Mask - Order #$order_id: ⚠️ CRITICAL WARNING - Pattern images lost during mask application!");
        } else if ($pattern_defs_final === $pattern_defs_before_mask && $pattern_images_final === $pattern_images_before_mask) {
            error_log("APD Apply Pattern Mask - Order #$order_id: ✅ Pattern definitions and PNG/JPEG images fully preserved (ready for CorelDraw)");
        }
        
        return $svg_content;
    }

    /**
     * Strip Inkscape/sodipodi markup so CorelDRAW can open the SVG.
     * CorelDRAW often fails or mis-renders SVG that contains Inkscape-specific namespaces.
     *
     * @param string $svg_content SVG content (e.g. from make_pdf_compatible_new / Inkscape)
     * @param int    $order_id   Order ID for logging
     * @return string Clean SVG for CorelDRAW
     */
    private function strip_inkscape_markup_for_coreldraw($svg_content, $order_id = 0)
    {
        // Remove Inkscape/sodipodi namespace declarations from root and any element
        $svg_content = preg_replace('/\s+xmlns:sodipodi=["\'][^"\']*["\']/i', '', $svg_content);
        $svg_content = preg_replace('/\s+xmlns:inkscape=["\'][^"\']*["\']/i', '', $svg_content);
        // Remove any attribute whose name starts with sodipodi: or inkscape:
        $svg_content = preg_replace('/\s+(sodipodi:[a-zA-Z0-9_-]+)=["\'][^"\']*["\']/i', '', $svg_content);
        $svg_content = preg_replace('/\s+(inkscape:[a-zA-Z0-9_-]+)=["\'][^"\']*["\']/i', '', $svg_content);
        // Ensure root <svg> has standard xmlns so CorelDRAW recognizes it
        if (!preg_match('/<svg\s+[^>]*xmlns=["\']http:\/\/www\.w3\.org\/2000\/svg["\']/i', $svg_content)
            && preg_match('/<svg(\s+[^>]*)>/i', $svg_content, $m)) {
            $svg_content = preg_replace(
                '/<svg(\s+[^>]*)>/i',
                '<svg$1 xmlns="http://www.w3.org/2000/svg">',
                $svg_content,
                1
            );
        }
        // Normalize XML declaration: UTF-8, no BOM
        $svg_content = preg_replace('/<\?xml[^?]*\?>\s*/i', '', $svg_content);
        $svg_content = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>' . "\n" . ltrim($svg_content);
        if ($order_id) {
            error_log("APD Strip Inkscape for CorelDRAW - Order #$order_id: Removed sodipodi/inkscape markup");
        }
        return $svg_content;
    }

    /**
     * Ensure material outline for logo is preserved in SVG before rasterization
     * Similar to ensure_logo_material_outline() in class-template-manager.php
     * This ensures logo material outline is present before client-side rasterization
     * 
     * @param string $svg_content SVG content
     * @param int $order_id Order ID for logging
     * @return string SVG content with logo material outline preserved
     */
    private function ensure_logo_material_outline_for_rasterization($svg_content, $order_id = 0)
    {
        error_log("APD Ensure Logo Material Outline - Order #$order_id: Checking logo material outline before rasterization");
        
        // Check if logoMaterialPattern pattern definition exists
        $has_logo_pattern = preg_match('/<pattern[^>]*id=["\']logoMaterialPattern["\'][^>]*>/i', $svg_content);
        
        if (!$has_logo_pattern) {
            error_log("APD Ensure Logo Material Outline - Order #$order_id: ⚠️ logoMaterialPattern not found in SVG - material outline may be missing");
            return $svg_content;
        }

        // Check if pattern has embedded image (PNG/JPEG)
        $pattern_has_image = preg_match('/<pattern[^>]*id=["\']logoMaterialPattern["\'][^>]*>.*?<image[^>]*>/is', $svg_content);
        
        if (!$pattern_has_image) {
            error_log("APD Ensure Logo Material Outline - Order #$order_id: ⚠️ logoMaterialPattern found but has no image - material outline may not work");
            return $svg_content;
        }

        // Check if logo paths have logoMaterialPattern stroke
        $logo_paths_with_stroke = preg_match_all('/<(path|polygon|rect|circle|ellipse|line|polyline)([^>]*stroke=["\']url\(#logoMaterialPattern\)["\'][^>]*)>/i', $svg_content);
        
        if ($logo_paths_with_stroke > 0) {
            error_log("APD Ensure Logo Material Outline - Order #$order_id: ✅ Logo material outline already applied ($logo_paths_with_stroke paths with logoMaterialPattern stroke)");
            return $svg_content;
        }

        // Logo paths don't have material outline - need to apply it
        error_log("APD Ensure Logo Material Outline - Order #$order_id: Logo paths found but no logoMaterialPattern stroke - applying material outline");
        
        $logo_paths_found = preg_match_all('/<(path|polygon|rect|circle|ellipse|line|polyline)([^>]*)>/i', $svg_content);
        
        if ($logo_paths_found === 0) {
            error_log("APD Ensure Logo Material Outline - Order #$order_id: ⚠️ No logo paths found to apply material outline");
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
            error_log("APD Ensure Logo Material Outline - Order #$order_id: ✅ Applied logoMaterialPattern stroke to $paths_modified logo paths (material outline preserved for rasterization)");
        } else {
            error_log("APD Ensure Logo Material Outline - Order #$order_id: ⚠️ No logo paths modified - material outline may already be applied or paths have other patterns");
        }
        
        return $svg_content;
    }
}
