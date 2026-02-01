<?php
/**
 * Template Name: APD Customizer
 * 
 * WordPress page template for Customizer page
 * 
 * @package AdvancedProductDesigner
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

APD_Header_Footer::page_wrapper_start();
APD_Header_Footer::output_site_header();

// Get product ID from URL parameter
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

// Include the customizer template
include APD_PLUGIN_PATH . 'templates/customizer.php';

APD_Header_Footer::output_site_footer();
APD_Header_Footer::page_wrapper_end();
