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
     * @param AdvancedProductDesigner|null $plugin Main plugin instance (optional for static methods)
     */
    public function __construct($plugin = null)
    {
        $this->plugin = $plugin;
    }

    /**
     * Static activation method for hook registration
     */
    public static function activate_static()
    {
        $instance = new self(null);
        $instance->activate();
    }

    /**
     * Static deactivation method for hook registration
     */
    public static function deactivate_static()
    {
        $instance = new self(null);
        $instance->deactivate();
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
     * Creates required pages and saves their IDs to options for consistency
     * Also assigns WordPress page templates to pages
     */
    private function maybe_create_core_pages()
    {
        // Define pages with their shortcodes and page templates
        $pages = array(
            'apd_cart' => array(
                'title' => 'Cart',
                'slug' => 'cart',
                'content' => '[apd_cart]',
                'template' => '' // Cart doesn't use page template, only shortcode
            ),
            'apd_checkout' => array(
                'title' => 'Checkout',
                'slug' => 'checkout',
                'content' => '[apd_checkout]',
                'template' => 'templates/page-checkout.php'
            ),
            'apd_thankyou' => array(
                'title' => 'Thank You',
                'slug' => 'thank-you',
                'content' => '[apd_thank_you]',
                'template' => 'templates/page-thankyou.php'
            ),
            'apd_orders' => array(
                'title' => 'My Orders',
                'slug' => 'my-orders',
                'content' => '[apd_orders]',
                'template' => 'templates/page-orders.php'
            ),
            'apd_product_list' => array(
                'title' => 'Product',
                'slug' => 'product',
                'content' => '[apd_product_list]',
                'template' => 'templates/page-product-list.php'
            ),
            'apd_product_detail' => array(
                'title' => 'Product Detail',
                'slug' => 'product-detail',
                'content' => '[apd_product_detail]',
                'template' => 'templates/page-product-detail.php'
            ),
        );

        foreach ($pages as $opt_key => $def) {
            // Check if page already exists by slug
            $existing = get_page_by_path($def['slug']);
            
            if ($existing && $existing->ID) {
                // Page exists, update option with its ID
                update_option($opt_key, intval($existing->ID));
                
                // Assign page template if specified and not already set
                if (!empty($def['template'])) {
                    $current_template = get_post_meta($existing->ID, '_wp_page_template', true);
                    if ($current_template !== $def['template']) {
                        update_post_meta($existing->ID, '_wp_page_template', $def['template']);
                        error_log('APD: Assigned template "' . $def['template'] . '" to existing page "' . $def['title'] . '"');
                    }
                }
                continue;
            }

            // Check if option already has a valid page ID
            $existing_id = get_option($opt_key);
            if ($existing_id && get_post($existing_id)) {
                // Assign page template if specified and not already set
                if (!empty($def['template'])) {
                    $current_template = get_post_meta($existing_id, '_wp_page_template', true);
                    if ($current_template !== $def['template']) {
                        update_post_meta($existing_id, '_wp_page_template', $def['template']);
                        error_log('APD: Assigned template "' . $def['template'] . '" to existing page "' . $def['title'] . '"');
                    }
                }
                continue;
            }

            // Create the page
            $page_data = array(
                'post_title' => $def['title'],
                'post_name' => $def['slug'],
                'post_content' => $def['content'],
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_author' => get_current_user_id() ? get_current_user_id() : 1
            );
            
            $page_id = wp_insert_post($page_data);
            
            if (!is_wp_error($page_id) && $page_id) {
                // Save page ID to option for consistency
                update_option($opt_key, intval($page_id));
                
                // Assign page template if specified
                if (!empty($def['template'])) {
                    update_post_meta($page_id, '_wp_page_template', $def['template']);
                    error_log('APD: Created page "' . $def['title'] . '" with ID ' . $page_id . ' and assigned template "' . $def['template'] . '"');
                } else {
                    error_log('APD: Created page "' . $def['title'] . '" with ID ' . $page_id);
                }
            } else {
                error_log('APD: Failed to create page "' . $def['title'] . '"');
            }
        }
    }
}
