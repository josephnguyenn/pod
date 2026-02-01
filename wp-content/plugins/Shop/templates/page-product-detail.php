<?php
/**
 * Template Name: APD Product Detail
 * 
 * WordPress page template for Product Detail page
 * 
 * @package AdvancedProductDesigner
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Redirect to customizer when product has customization enabled (skip product detail page)
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($product_id > 0) {
    $product = get_post($product_id);
    if ($product && $product->post_type === 'apd_product') {
        $is_customizable = get_post_meta($product_id, '_fsc_customizable', true);
        if ($is_customizable === '') {
            $is_customizable = '1'; // Default to customizable for backward compatibility
        }
        if ($is_customizable === '1' || $is_customizable) {
            wp_safe_redirect(home_url('/customizer/' . $product_id . '/'));
            exit;
        }
    }
}

APD_Header_Footer::page_wrapper_start();
APD_Header_Footer::output_site_header();

// Include the product-detail template
include APD_PLUGIN_PATH . 'templates/product-detail-page.php';

APD_Header_Footer::output_site_footer();
APD_Header_Footer::page_wrapper_end();
