<?php
/**
 * Template Name: APD Product List
 * 
 * WordPress page template for Product List page.
 * Uses the shortcode so categories and products are loaded (apd-category-tabs, apd-product-grid-container).
 *
 * @package AdvancedProductDesigner
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

APD_Header_Footer::page_wrapper_start();
APD_Header_Footer::output_site_header();

// Use shortcode so product list data (categories, products) is loaded and rendered
echo do_shortcode('[apd_product_list show_title="true" show_description="true" show_price="true" show_sale="true"]');

APD_Header_Footer::output_site_footer();
APD_Header_Footer::page_wrapper_end();
