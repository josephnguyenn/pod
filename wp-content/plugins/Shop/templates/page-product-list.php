<?php
/**
 * Template Name: APD Product List
 * 
 * WordPress page template for Product List page
 * 
 * @package AdvancedProductDesigner
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

APD_Header_Footer::page_wrapper_start();
APD_Header_Footer::output_site_header();

// Get default template data if not provided
if (!isset($template_data)) {
    $template_data = array(
        'categories' => array(),
        'show_title' => true,
        'show_description' => true,
        'show_price' => true,
        'show_sale' => true,
        'hide_header' => false,
        'company_name' => ''
    );
}

// Include the product-list template
include APD_PLUGIN_PATH . 'templates/product-list.php';

APD_Header_Footer::output_site_footer();
APD_Header_Footer::page_wrapper_end();
