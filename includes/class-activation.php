<?php
/**
 * Activation Handler
 * 
 * Handles plugin activation and deactivation
 *
 * @package AdvancedProductDesigner
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class APD_Activation
{
    /**
     * Main plugin instance reference
     * @var AdvancedProductDesigner
     */
    private $plugin;

    /**
     * Constructor
     * 
     * @param AdvancedProductDesigner $plugin Main plugin instance
     */
    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Plugin activation
     */
    public function activate()
    {
        // Create database tables if needed
        $this->create_tables();

        // Create upload directories
        $this->create_upload_directories();

        // Create custom post type for designs
        $this->create_design_post_type();

        // Auto-create core pages: cart, checkout, thank you, orders
        $this->maybe_create_core_pages();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate()
    {
        flush_rewrite_rules();
    }

    /**
     * Create database tables
     */
    public function create_tables()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'apd_customizations';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            product_id bigint(20) NOT NULL,
            vin varchar(50) NOT NULL,
            truck_no varchar(50) NOT NULL,
            print_color varchar(20) NOT NULL,
            vinyl_material varchar(50) NOT NULL,
            quantity int(11) NOT NULL DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create upload directories
     */
    public function create_upload_directories()
    {
        $plugin_dir = APD_PLUGIN_PATH;

        // Create material directory
        $material_dir = $plugin_dir . 'uploads/material/';
        if (!is_dir($material_dir)) {
            wp_mkdir_p($material_dir);
        }

        // Create object directory
        $object_dir = $plugin_dir . 'uploads/object/';
        if (!is_dir($object_dir)) {
            wp_mkdir_p($object_dir);
        }
    }

    /**
     * Create design post type
     */
    public function create_design_post_type()
    {
        register_post_type('apd_design', array(
            'labels' => array(
                'name' => 'Designs',
                'singular_name' => 'Design',
                'add_new' => 'Add New Design',
                'add_new_item' => 'Add New Design',
                'edit_item' => 'Edit Design',
                'new_item' => 'New Design',
                'view_item' => 'View Design',
                'search_items' => 'Search Designs',
                'not_found' => 'No designs found',
                'not_found_in_trash' => 'No designs found in trash'
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'apd-dashboard',
            'supports' => array('title', 'custom-fields'),
            'capability_type' => 'post'
        ));
    }

    /**
     * Maybe create core pages
     */
    private function maybe_create_core_pages()
    {
        // Define pages with their shortcodes
        $pages = array(
            array(
                'title' => 'Cart',
                'slug' => 'apd-cart',
                'shortcode' => '[apd_cart]'
            ),
            array(
                'title' => 'Checkout',
                'slug' => 'apd-checkout',
                'shortcode' => '[apd_checkout]'
            ),
            array(
                'title' => 'Thank You',
                'slug' => 'apd-thankyou',
                'shortcode' => '[apd_thankyou]'
            ),
            array(
                'title' => 'My Orders',
                'slug' => 'apd-orders',
                'shortcode' => '[apd_orders]'
            )
        );

        foreach ($pages as $page) {
            // Check if page already exists
            $existing = get_page_by_path($page['slug']);
            
            if (!$existing) {
                // Create the page
                $page_data = array(
                    'post_title' => $page['title'],
                    'post_name' => $page['slug'],
                    'post_content' => $page['shortcode'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_author' => get_current_user_id()
                );
                
                wp_insert_post($page_data);
            }
        }
    }
}
