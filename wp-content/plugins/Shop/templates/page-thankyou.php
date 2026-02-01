<?php
/**
 * Template Name: APD Thank You
 * 
 * WordPress page template for Thank You page
 * 
 * @package AdvancedProductDesigner
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

APD_Header_Footer::page_wrapper_start();
APD_Header_Footer::output_site_header();

// Include the thankyou template
include APD_PLUGIN_PATH . 'templates/thankyou.php';

APD_Header_Footer::output_site_footer();
APD_Header_Footer::page_wrapper_end();
