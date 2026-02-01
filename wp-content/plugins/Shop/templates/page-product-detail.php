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

APD_Header_Footer::page_wrapper_start();
APD_Header_Footer::output_site_header();

// Include the product-detail template
include APD_PLUGIN_PATH . 'templates/product-detail-page.php';

APD_Header_Footer::output_site_footer();
APD_Header_Footer::page_wrapper_end();
