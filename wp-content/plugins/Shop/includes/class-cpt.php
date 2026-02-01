<?php

/**
 * Class APD_CPT
 * 
 * Handles Custom Post Type and Taxonomy Registration
 *
 * @package AdvancedProductDesigner
 */

if (!defined('ABSPATH')) {
    exit;
}

class APD_CPT
{
    /**
     * Register Custom Post Types and Taxonomies
     */
    public static function register()
    {
        // Templates Post Type
        register_post_type('apd_template', array(
            'labels' => array(
                'name' => 'Templates',
                'singular_name' => 'Template',
                'add_new' => 'Add New Template',
                'add_new_item' => 'Add New Template',
                'edit_item' => 'Edit Template',
                'new_item' => 'New Template',
                'view_item' => 'View Template',
                'search_items' => 'Search Templates',
                'not_found' => 'No templates found',
                'not_found_in_trash' => 'No templates found in trash'
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
            'menu_icon' => 'dashicons-layout',
            'rewrite' => array('slug' => 'templates'),
            'show_in_rest' => true,
            'capability_type' => 'post'
        ));

        // Products Post Type
        register_post_type('apd_product', array(
            'labels' => array(
                'name' => 'Products',
                'singular_name' => 'Product',
                'add_new' => 'Add New Product',
                'add_new_item' => 'Add New Product',
                'edit_item' => 'Edit Product',
                'new_item' => 'New Product',
                'view_item' => 'View Product',
                'search_items' => 'Search Products',
                'not_found' => 'No products found',
                'not_found_in_trash' => 'No products found in trash'
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
            'menu_icon' => 'dashicons-products',
            'rewrite' => array('slug' => 'products'),
            'show_in_rest' => true,
            'capability_type' => 'post',
            'taxonomies' => array('apd_company')
        ));

        // Register Company Taxonomy
        register_taxonomy('apd_company', array('apd_product'), array(
            'labels' => array(
                'name' => 'Companies',
                'singular_name' => 'Company',
                'search_items' => 'Search Companies',
                'all_items' => 'All Companies',
                'parent_item' => 'Parent Company',
                'parent_item_colon' => 'Parent Company:',
                'edit_item' => 'Edit Company',
                'update_item' => 'Update Company',
                'add_new_item' => 'Add New Company',
                'new_item_name' => 'New Company Name',
                'menu_name' => 'Companies',
            ),
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => 'company',
                'with_front' => false,
                'hierarchical' => false
            ),
            'show_in_rest' => true,
            'public' => true,
            'publicly_queryable' => true,
            'show_in_nav_menus' => true,
            'has_archive' => true,
        ));

        // Register Material Category Taxonomy
        register_taxonomy('material_category', array('apd_product'), array(
            'labels' => array(
                'name' => 'Material Categories',
                'singular_name' => 'Material Category',
                'search_items' => 'Search Material Categories',
                'all_items' => 'All Material Categories',
                'parent_item' => 'Parent Material Category',
                'parent_item_colon' => 'Parent Material Category:',
                'edit_item' => 'Edit Material Category',
                'update_item' => 'Update Material Category',
                'add_new_item' => 'Add New Material Category',
                'new_item_name' => 'New Material Category Name',
                'menu_name' => 'Material Categories',
            ),
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => 'material-category',
                'with_front' => false,
                'hierarchical' => false
            ),
            'show_in_rest' => true,
            'public' => true,
            'publicly_queryable' => true,
            'show_in_nav_menus' => true,
            'has_archive' => true,
        ));
    }
}
