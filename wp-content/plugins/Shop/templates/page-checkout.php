<?php
/**
 * Template Name: APD Checkout
 * 
 * WordPress page template for Checkout page
 * 
 * @package AdvancedProductDesigner
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

APD_Header_Footer::page_wrapper_start();
APD_Header_Footer::output_site_header();

// Include the checkout template
include APD_PLUGIN_PATH . 'templates/checkout.php';

APD_Header_Footer::output_site_footer();
APD_Header_Footer::page_wrapper_end();
