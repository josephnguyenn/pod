<?php
/**
 * Block Registration
 * 
 * @package AdvancedProductDesigner
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class APD_Block_Registration {
    
    public function __construct() {
        add_action('init', array($this, 'register_blocks'));
    }
    
    public function register_blocks() {
        // Check if Gutenberg is available
        if (!function_exists('register_block_type')) {
            error_log('APD: register_block_type function not available');
            return;
        }
        
        // Register the product list block
        $result = register_block_type('apd/product-list', array(
            'editor_script' => 'apd-product-block',
            'editor_style' => 'apd-product-block',
            'style' => 'apd-product-block',
            'render_callback' => array($this, 'render_product_list_block'),
            'attributes' => array(
                'showTitle' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showDescription' => array(
                    'type' => 'boolean',
                    'default' => true
            ),
                'showPrice' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showSale' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'columns' => array(
                    'type' => 'number',
                    'default' => 3
                ),
                'itemsPerPage' => array(
                    'type' => 'number',
                    'default' => 12
                )
            )
        ));
        
    }
    
    public function render_product_list_block($attributes) {
        // Debug: Log block render call
        error_log('APD Block: render_product_list_block called with attributes: ' . print_r($attributes, true));
        
        $show_title = isset($attributes['showTitle']) ? (bool) $attributes['showTitle'] : true;
        $show_description = isset($attributes['showDescription']) ? (bool) $attributes['showDescription'] : true;
        $show_price = isset($attributes['showPrice']) ? (bool) $attributes['showPrice'] : true;
        $show_sale = isset($attributes['showSale']) ? (bool) $attributes['showSale'] : true;
        $columns = isset($attributes['columns']) ? intval($attributes['columns']) : 3;
        $items_per_page = isset($attributes['itemsPerPage']) ? intval($attributes['itemsPerPage']) : 12;
        
        // Convert attributes to shortcode format
        $shortcode_atts = array(
            'show_title' => $show_title ? 'true' : 'false',
            'show_description' => $show_description ? 'true' : 'false',
            'show_price' => $show_price ? 'true' : 'false',
            'show_sale' => $show_sale ? 'true' : 'false',
            'columns' => $columns,
            'items_per_page' => $items_per_page
        );
        
        // Use the existing product list shortcode
        $shortcode_output = do_shortcode('[apd_product_list ' . http_build_query($shortcode_atts, '', ' ') . ']');
        
        // Debug: Log shortcode output
        error_log('APD Block: Shortcode output length: ' . strlen($shortcode_output));
        
        // If shortcode returns empty, show debug info
        if (empty($shortcode_output)) {
            return '<div style="background: #ffebee; border: 2px solid #f44336; padding: 20px; margin: 10px 0;">
                <h3>⚠️ Product List Block Debug</h3>
                <p><strong>Block rendered:</strong> ' . date('Y-m-d H:i:s') . '</p>
                <p><strong>Shortcode output:</strong> Empty</p>
                <p><strong>Try shortcode directly:</strong> [apd_debug]</p>
            </div>';
        }
        
        return $shortcode_output;
    }
}