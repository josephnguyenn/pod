<?php
/**
 * Template Name: APD Orders
 * 
 * WordPress page template for Orders page
 * 
 * @package AdvancedProductDesigner
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

APD_Header_Footer::page_wrapper_start();
APD_Header_Footer::output_site_header();

// Include the orders template
include APD_PLUGIN_PATH . 'templates/orders.php';

APD_Header_Footer::output_site_footer();
APD_Header_Footer::page_wrapper_end();
