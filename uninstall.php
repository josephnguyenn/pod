<?php
/**
 * Uninstall script for Freight Signs Customizer
 * This file is executed when the plugin is deleted
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete custom post type posts
$posts = get_posts(array(
    'post_type' => 'freight_product',
    'numberposts' => -1,
    'post_status' => 'any'
));

foreach ($posts as $post) {
    wp_delete_post($post->ID, true);
}

// Delete custom database table
global $wpdb;
$table_name = $wpdb->prefix . 'fsc_customizations';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

// Delete plugin options
delete_option('fsc_version');
delete_option('fsc_settings');

// Clear any cached data
wp_cache_flush();

// Remove rewrite rules
flush_rewrite_rules();
