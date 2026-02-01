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

// Get product ID from URL parameter
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

// Include the customizer template
include APD_PLUGIN_PATH . 'templates/customizer.php';
