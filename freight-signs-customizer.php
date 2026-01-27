<?php

/**
 * Plugin Name: Advanced Product Designer
 * Description: Modern drag-and-drop product customizer with SVG support and text editing
 * Version: 2.0.0
 * Author: Your Name
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('APD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('APD_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('APD_VERSION', '2.0.0');

// Load autoloader
require_once APD_PLUGIN_PATH . 'includes/class-autoloader.php';
APD_Autoloader::load_services();

if (!class_exists('AdvancedProductDesigner')) {
    class AdvancedProductDesigner
    {
    /**
     * Cart service instance
     * @var APD_Cart_Service
     */
    private $cart_service;

    /**
     * Template service instance
     * @var APD_Template_Service
     */
    private $template_service;

    /**
     * Order service instance
     * @var APD_Order_Service
     */
    private $order_service;

    /**
     * Email service instance
     * @var APD_Email_Service
     */
    private $email_service;

    // New service instances
    public $admin_pages;
    public $ajax_handlers;
    public $rest_api;
    public $template_manager;
    public $material_manager;
    public $frontend_shortcodes;
    public $svg_processor;
    public $order_admin_handler;
    public $email_tester;
    public $meta_boxes;

    public function __construct()
    {
        // Initialize services
        $this->cart_service = new APD_Cart_Service();
        $this->template_service = new APD_Template_Service();
        $this->email_service = new APD_Email_Service($this->template_service);
        $this->order_service = new APD_Order_Service($this->cart_service, $this->email_service);
        
        // Initialize new modular classes
        $this->admin_pages = new APD_Admin_Pages($this);
        $this->ajax_handlers = new APD_AJAX_Handlers($this);
        $this->rest_api = new APD_REST_API($this);
        $this->template_manager = new APD_Template_Manager($this);
        $this->material_manager = new APD_Material_Manager($this);
        $this->frontend_shortcodes = new APD_Frontend_Shortcodes($this);
        $this->svg_processor = new APD_SVG_Processor($this);
        $this->order_admin_handler = new APD_Order_Admin_Handler($this);
        $this->email_tester = new APD_Email_Tester($this);
        $this->meta_boxes = new APD_Meta_Boxes($this);
        
        // Initialize hooks for new classes
        $this->admin_pages->init();
        $this->ajax_handlers->init();
        $this->rest_api->init();
        $this->frontend_shortcodes->init();
        $this->svg_processor->init();
        $this->order_admin_handler->init();
        $this->email_tester->init();
        $this->template_manager->init();
        $this->meta_boxes->init();
        
        // Start session early
        add_action('init', array($this, 'start_session'), 1);
        
        // Merge guest cart on user login
        add_action('wp_login', array($this, 'merge_guest_cart_on_login'), 10, 2);
        
        // Schedule cart cleanup cron job
        add_action('apd_cleanup_carts', array('APD_Cart_Service', 'cleanup_expired_carts'));
        if (!wp_next_scheduled('apd_cleanup_carts')) {
            wp_schedule_event(time(), 'daily', 'apd_cleanup_carts');
        }
        
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array('APD_Assets', 'enqueue_scripts'));
        add_action('wp_enqueue_scripts', array('APD_Assets', 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array('APD_Assets', 'admin_enqueue_scripts'));
        add_action('admin_notices', array($this, 'display_admin_notices'));
        add_action('wp_footer', array($this->frontend_shortcodes, 'render_floating_cart_icon'));
        add_action('enqueue_block_editor_assets', array('APD_Assets', 'enqueue_block_editor_assets'));
        
        add_action('wp_ajax_load_product', array($this->meta_boxes, 'load_product'));
        add_action('wp_ajax_nopriv_load_product', array($this->meta_boxes, 'load_product'));
        // AJAX hooks moved to APD_AJAX_Handlers, APD_Template_Manager and other specific classes
        add_action('wp_ajax_apd_save_customization_image', array($this->meta_boxes, 'apd_save_customization_image'));
        add_action('wp_ajax_nopriv_apd_save_customization_image', array($this->meta_boxes, 'apd_save_customization_image'));

        if (method_exists($this->meta_boxes, 'register_order_cpt_and_statuses')) {
            add_action('init', array($this->meta_boxes, 'register_order_cpt_and_statuses'));
        }
        // Order admin hooks moved to APD_Order_Admin_Handler class
        add_action('wp_ajax_apd_place_order', array($this->meta_boxes, 'apd_place_order'));
        add_action('wp_ajax_nopriv_apd_place_order', array($this->meta_boxes, 'apd_place_order'));
        add_action('wp_ajax_apd_get_order_details', array($this->meta_boxes, 'apd_get_order_details'));
        add_action('wp_ajax_nopriv_apd_get_order_details', array($this->meta_boxes, 'apd_get_order_details'));
        // apd_process_cut_ready_svg moved to APD_SVG_Processor class

        add_action('wp_ajax_apd_test_ajax', array($this, 'test_ajax_handler'));
        add_action('wp_ajax_nopriv_apd_test_ajax', array($this, 'test_ajax_handler'));
        add_filter('render_block_data', array($this, 'fix_block_validation'), 10, 2);

        // Template manager AJAX (moved to class but keep hooks here for now)
        add_action('wp_ajax_save_template_design', array($this->template_manager, 'save_template_design'));
        add_action('wp_ajax_upload_font', array($this->template_manager, 'upload_font'));
        add_action('wp_ajax_apd_delete_font', array($this->template_manager, 'delete_font'));

        // Email testing moved to APD_Email_Tester class
        
        // Shortcodes moved to APD_Frontend_Shortcodes class
        add_shortcode('apd_test', array($this->meta_boxes, 'test_shortcode'));
        add_shortcode('apd_debug', array($this->meta_boxes, 'debug_shortcode'));

        // Note: ensure_core_pages is handled by APD_Meta_Boxes class during its init()
        add_filter('upload_mimes', array($this, 'allow_svg_upload'));
        add_filter('wp_handle_upload_prefilter', array($this, 'check_svg_security'));
        add_action('admin_notices', array($this, 'svg_upload_notice'));
        add_action('admin_post_fsc_upload_logo', array($this, 'handle_logo_upload'));
    }

    /**
     * Start PHP session for cart functionality
     */
    public function start_session()
    {
        if (!session_id() && !headers_sent()) {
            session_start();
        }
    }

    /**
     * Merge guest cart with user cart on login
     *
     * @param string $user_login Username
     * @param WP_User $user User object
     */
    public function merge_guest_cart_on_login($user_login, $user)
    {
        $this->cart_service->merge_guest_cart_on_login($user->ID);
    }

    public function init()
    {
        // Create custom post type for products
        $this->create_custom_post_type();

        // Add rewrite rules
        add_rewrite_rule(
            'customizer/([^/]+)/?$',
            'index.php?customizer=$matches[1]',
            'top'
        );

        add_rewrite_rule(
            'product-detail/?$',
            'index.php?product_detail=1',
            'top'
        );

        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'template_redirect'));
        add_filter('single_template', array($this, 'load_single_product_template'));
        add_filter('taxonomy_template', array($this, 'load_company_taxonomy_template'));
        add_filter('page_template', array($this, 'load_page_template'));
        
        // Force Elementor canvas (full-width) for company archives
        add_action('wp', array($this, 'set_company_archive_elementor_template'));

        // Force flush rewrite rules if needed
        if (get_option('apd_flush_rewrite_rules') !== '6') {
            flush_rewrite_rules();
            update_option('apd_flush_rewrite_rules', '6');
            error_log('APD Plugin: Rewrite rules flushed on init');
        }
    }

    /**
     * Add custom query vars
     */
    public function add_query_vars($vars)
    {
        $vars[] = 'customizer';
        $vars[] = 'product_detail';
        return $vars;
    }

    /**
     * Handle template redirects for customizer and product detail pages
     */
    public function template_redirect()
    {
        $customizer_id = get_query_var('customizer');
        if ($customizer_id) {
            error_log('APD Template Redirect: Customizer detected with ID: ' . $customizer_id);
            
            get_header();
            
            // Render customizer - delegate to meta_boxes if it has the method
            if (method_exists($this->meta_boxes, 'render_customizer')) {
                $this->meta_boxes->render_customizer($customizer_id);
            } else {
                // Fallback: load customizer template directly
                include APD_PLUGIN_PATH . 'templates/customizer.php';
            }
            
            get_footer();
            exit;
        }

        if (get_query_var('product_detail')) {
            include APD_PLUGIN_PATH . 'templates/product-detail-page.php';
            exit;
        }
    }

    /**
     * Load custom template for single product posts
     */
    public function load_single_product_template($template)
    {
        global $post;

        if ($post && $post->post_type === 'apd_product') {
            $custom_template = APD_PLUGIN_PATH . 'templates/single-apd_product.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }

        return $template;
    }

    /**
     * Load custom template for company taxonomy archive
     */
    public function load_company_taxonomy_template($template)
    {
        if (is_tax('apd_company')) {
            // Force Elementor canvas template for full width
            add_filter('template_include', function($template) {
                if (function_exists('elementor_theme_do_location')) {
                    add_filter('elementor/theme/get_location_templates/template_id', function() {
                        return 'elementor_canvas';
                    });
                }
                return $template;
            }, 1);
            
            $custom_template = APD_PLUGIN_PATH . 'templates/taxonomy-apd_company.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }

        return $template;
    }

    /**
     * Load page templates from plugin directory
     * Allows WordPress to use page templates stored in plugin directory
     */
    public function load_page_template($template)
    {
        global $post;
        
        if (!$post || !is_page()) {
            return $template;
        }
        
        // Get the page template assigned to this page
        $page_template = get_post_meta($post->ID, '_wp_page_template', true);
        
        // If no template is assigned or it's the default, return original template
        if (empty($page_template) || $page_template === 'default') {
            return $template;
        }
        
        // Check if template path starts with 'templates/' (our plugin templates)
        if (strpos($page_template, 'templates/') === 0) {
            $plugin_template = APD_PLUGIN_PATH . $page_template;
            
            // If template exists in plugin directory, use it
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        // Check in theme directory first (WordPress default behavior)
        $theme_template = get_stylesheet_directory() . '/' . $page_template;
        if (file_exists($theme_template)) {
            return $theme_template;
        }
        
        // Check in parent theme directory
        $parent_template = get_template_directory() . '/' . $page_template;
        if (file_exists($parent_template)) {
            return $parent_template;
        }
        
        // Return original template if nothing found
        return $template;
    }

    /**
     * Set Elementor canvas template for company archive pages
     */
    public function set_company_archive_elementor_template()
    {
        if (is_tax('apd_company')) {
            add_filter('template_include', function($template) {
                global $wp_query;
                if (isset($wp_query->queried_object_id)) {
                    add_filter('elementor/page/get_template', function() {
                        return 'elementor_canvas';
                    }, 999);
                }
                return $template;
            }, 1);
        }
    }

    /**
     * Display admin notices from transients
     */
    public function display_admin_notices()
    {
        // Material price update success
        if (get_transient('apd_material_price_updated')) {
            delete_transient('apd_material_price_updated');
            echo '<div class="notice notice-success is-dismissible"><p>✅ Material price updated successfully!</p></div>';
        }
        
        // Material price update error
        if (get_transient('apd_material_price_error')) {
            delete_transient('apd_material_price_error');
            echo '<div class="notice notice-error is-dismissible"><p>❌ Material not found.</p></div>';
        }
        
        // Material upload success
        if (get_transient('apd_material_uploaded')) {
            delete_transient('apd_material_uploaded');
            echo '<div class="notice notice-success is-dismissible"><p>✅ Material uploaded successfully!</p></div>';
        }
        
        // Material deletion success
        if (get_transient('apd_material_deleted')) {
            delete_transient('apd_material_deleted');
            echo '<div class="notice notice-success is-dismissible"><p>✅ Material deleted successfully!</p></div>';
        }
    }

    /**
     * AJAX: Buy Now (single item) - creates an order for one item and returns redirect
     */
    public function apd_buy_now()
    {
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : (isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : '');
        // Soft-verify
        if ($nonce && !(wp_verify_nonce($nonce, 'apd_ajax_nonce') || wp_verify_nonce($nonce, 'fsc_nonce'))) {
            // continue
        }

        $customer_name = sanitize_text_field(isset($_POST['customer_name']) ? $_POST['customer_name'] : '');
        $customer_email = sanitize_email(isset($_POST['customer_email']) ? $_POST['customer_email'] : '');
        $customer_phone = sanitize_text_field(isset($_POST['customer_phone']) ? $_POST['customer_phone'] : '');
        $customer_address = sanitize_textarea_field(isset($_POST['customer_address']) ? $_POST['customer_address'] : '');

        $raw_item = isset($_POST['item']) ? $_POST['item'] : null;
        if (is_string($raw_item)) {
            $item = json_decode(stripslashes($raw_item), true);
            if (json_last_error() !== JSON_ERROR_NONE)
                $item = null;
        } elseif (is_array($raw_item)) {
            $item = $raw_item;
        } else {
            $item = null;
        }

        if (!$item) {
            wp_send_json_error(array('message' => 'Missing item data'), 400);
        }

        // Convert inline images if present
    $image_fields = array('preview_image_png', 'preview_image_url', 'customization_image_url', 'image_url');
        foreach ($image_fields as $field) {
            if (!empty($item[$field]) && strpos($item[$field], 'data:image') === 0) {
                $raw = preg_replace('#^data:image/[^;]+;base64,#', '', $item[$field]);
                $decoded = base64_decode($raw);
                if ($decoded !== false && strlen($decoded) <= 8 * 1024 * 1024) {
                    $upload = wp_upload_bits('order-preview-' . time() . '-' . wp_generate_password(6, false, false) . '.png', null, $decoded);
                    if (empty($upload['error'])) {
                        $item[$field] = $upload['url'];
                    }
                }
            }
        }

        // Build meta similar to apd_place_order but for single item
        $item_price = isset($item['price']) ? $item['price'] : (isset($item['product_price']) ? $item['product_price'] : 0);
        $item_quantity = isset($item['quantity']) ? $item['quantity'] : 1;
        $meta = array(
            'product_id' => isset($item['product_id']) ? $item['product_id'] : '',
            'product_name' => isset($item['product_name']) ? $item['product_name'] : 'Custom Product',
            'product_price' => floatval($item_price),
            'quantity' => intval($item_quantity),
            'total_amount' => (floatval($item_price) * intval($item_quantity)),
            'print_color' => isset($item['print_color']) ? $item['print_color'] : '',
            'vinyl_material' => isset($item['vinyl_material']) ? $item['vinyl_material'] : '',
            'material_texture_url' => isset($item['material_texture_url']) ? $item['material_texture_url'] : '',
            'text_fields' => isset($item['text_fields']) ? $item['text_fields'] : array(),
            'template_data' => isset($item['template_data']) ? $item['template_data'] : array(),
            'fields_display' => isset($item['fields_display']) ? $item['fields_display'] : array(),
            'template_fields_array' => isset($item['template_fields_array']) ? $item['template_fields_array'] : array(),
            'customization_image_url' => isset($item['customization_image_url']) ? $item['customization_image_url'] : '',
            'preview_image_url' => isset($item['preview_image_url']) ? $item['preview_image_url'] : '',
            'preview_image_png' => isset($item['preview_image_png']) ? $item['preview_image_png'] : '',
            'preview_image_svg' => isset($item['preview_image_svg']) ? $item['preview_image_svg'] : '',
            'customer_name' => $customer_name,
            'customer_email' => $customer_email,
            'customer_phone' => $customer_phone,
            'customer_address' => $customer_address,
            'payment_method' => isset($item['payment_method']) ? $item['payment_method'] : 'mock_paypal',
            'order_date' => current_time('Y-m-d H:i:s'),
            'order_status' => 'apd_pending',
            'paypal_order_id' => isset($item['paypal_order_id']) ? $item['paypal_order_id'] : '',
            'paypal_transaction_id' => isset($item['paypal_transaction_id']) ? $item['paypal_transaction_id'] : '',
            'paypal_payer_id' => isset($item['paypal_payer_id']) ? $item['paypal_payer_id'] : '',
            'payment_status' => isset($item['payment_status']) ? $item['payment_status'] : 'completed',
            'manufacturing_notes' => $this->generateManufacturingNotes($item),
            'production_ready' => true,
            'cart_items' => json_encode(array($item)),
            'cart_total' => floatval($item_price) * intval($item_quantity)
        );

        $order_id = wp_insert_post(array(
            'post_type' => 'apd_order',
            'post_title' => 'Order ' . date('Y-m-d H:i:s'),
            'post_status' => 'apd_pending'
        ));
        if (is_wp_error($order_id) || !$order_id) {
            wp_send_json_error(array('message' => 'Unable to create order'), 500);
        }
        foreach ($meta as $k => $v) {
            update_post_meta($order_id, $k, $v);
        }

        // Get thank you page URL - prefer slug-based option over page ID
        $thankyou = home_url(get_option('apd_thank_you_url', '/thank-you/'));
        
        // Fallback to page ID if slug option is not set
        if (!get_option('apd_thank_you_url')) {
            $page_id = intval(get_option('apd_thankyou'));
            if ($page_id) {
                $thankyou = get_permalink($page_id);
            }
        }
        
        // Final fallback
        if (!$thankyou || strpos($thankyou, '?page_id=') !== false) {
            $thankyou = home_url('/thank-you/');
        }
        
        wp_send_json_success(array('order_id' => $order_id, 'redirect' => esc_url($thankyou)));
    }

    public function create_custom_post_type()
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

        // Add meta boxes for template and product details
        // Meta boxes hooks moved to APD_Meta_Boxes class

        add_action('wp_ajax_apd_buy_now', array($this, 'apd_buy_now'));
        add_action('wp_ajax_nopriv_apd_buy_now', array($this, 'apd_buy_now'));

        // Add form enctype for file uploads on product edit pages
        add_action('post_edit_form_tag', array($this, 'add_form_enctype'));
    }

    public function fix_block_validation($parsed_block, $source_block)
    {
        // Fix block validation for apd/product-display blocks
        if (isset($parsed_block['blockName']) && $parsed_block['blockName'] === 'apd/product-display') {
            // Ensure default attributes are set correctly
            if (!isset($parsed_block['attrs']['layout'])) {
                $parsed_block['attrs']['layout'] = 'card';
            }
            if (!isset($parsed_block['attrs']['productId'])) {
                $parsed_block['attrs']['productId'] = 0;
            }
            if (!isset($parsed_block['attrs']['showPrice'])) {
                $parsed_block['attrs']['showPrice'] = true;
            }
            if (!isset($parsed_block['attrs']['showDescription'])) {
                $parsed_block['attrs']['showDescription'] = true;
            }
        }
        return $parsed_block;
    }

    private function get_material_filename($material_name)
    {
        $material_name = strtolower(trim($material_name));

        $material_map = array(
            'diamond plate' => 'Diamond_Plate.png',
            'diamond_plate' => 'Diamond_Plate.png',
            'engine turn gold' => 'Engine_turn_gold.png',
            'engine_turn_gold' => 'Engine_turn_gold.png',
            'florentine silver' => 'Florentine_Silver.png',
            'florentine_silver' => 'Florentine_Silver.png',
            'gold' => 'gold.png',
            'brush gold' => 'gold.png',
            'brush_gold' => 'gold.png'
        );

        if (isset($material_map[$material_name])) {
            return $material_map[$material_name];
        }

        // Try to find by checking if file exists
        $plugin_dir = APD_PLUGIN_PATH;
        $material_path = $plugin_dir . 'uploads/material/';

        if (is_dir($material_path)) {
            $files = glob($material_path . '*.{png,jpg,jpeg}', GLOB_BRACE);
            foreach ($files as $file) {
                $filename = pathinfo($file, PATHINFO_FILENAME);
                if (strtolower($filename) === $material_name) {
                    return basename($file);
                }
            }
        }

        return null;
    }

    /**
     * Generate manufacturing notes from customization data
     * 
     * @param array $customization_data Item customization data
     * @return string Manufacturing notes
     */
    private function generateManufacturingNotes($customization_data)
    {
        $notes = array();

        // Product specifications
        $notes[] = 'PRODUCT SPECIFICATIONS:';
        $notes[] = '- Product: ' . (isset($customization_data['product_name']) ? $customization_data['product_name'] : 'Custom Freight Sign');
        $notes[] = '- Quantity: ' . (isset($customization_data['quantity']) ? $customization_data['quantity'] : 1);
        $notes[] = '- Print Color: ' . (isset($customization_data['print_color']) ? $customization_data['print_color'] : 'Black');
        $notes[] = '- Material: ' . (isset($customization_data['vinyl_material']) ? $customization_data['vinyl_material'] : 'Standard');

        // Text fields
        if (!empty($customization_data['text_fields'])) {
            $notes[] = '';
            $notes[] = 'TEXT CONTENT:';
            foreach ($customization_data['text_fields'] as $field_id => $value) {
                if (is_array($value)) {
                    $notes[] = '- ' . (isset($value['label']) ? $value['label'] : $field_id) . ': ' . (isset($value['value']) ? $value['value'] : '');
                } else {
                    $notes[] = '- ' . ucwords(str_replace('_', ' ', $field_id)) . ': ' . $value;
                }
            }
        }

        // Template data
        if (!empty($customization_data['template_data'])) {
            $notes[] = '';
            $notes[] = 'TEMPLATE ELEMENTS:';
            foreach ($customization_data['template_data'] as $field_id => $value) {
                if (is_array($value)) {
                    $notes[] = '- ' . (isset($value['label']) ? $value['label'] : $field_id) . ': ' . (isset($value['value']) ? $value['value'] : '');
                } else {
                    $notes[] = '- ' . ucwords(str_replace('_', ' ', $field_id)) . ': ' . $value;
                }
            }
        }

        // Visual references
        if (!empty($customization_data['image_url'])) {
            $notes[] = '';
            $notes[] = 'VISUAL REFERENCES:';
            $notes[] = '- Customization Image: ' . $customization_data['image_url'];
        }

        $notes[] = '';
        $notes[] = 'ORDER DATE: ' . current_time('Y-m-d H:i:s');
        $notes[] = 'STATUS: Ready for Production';

        return implode("\n", $notes);
    }

    /**
     * Get cart service instance
     * 
     * @return APD_Cart_Service
     */
    public function get_cart_service()
    {
        return $this->cart_service;
    }

    /**
     * Get order service instance
     * 
     * @return APD_Order_Service
     */
    public function get_order_service()
    {
        return $this->order_service;
    }

    /**
     * Get processed SVG content (public wrapper for private method)
     * 
     * @param string $logo_path Path to SVG file
     * @return string|false SVG content or false on failure
     */
    public function get_processed_svg_content($logo_path)
    {
        return $this->_get_processed_svg_content($logo_path);
    }

    /**
     * Helper function to process SVG content for dynamic coloring
     * 
     * @param string $logo_path Path to SVG file
     * @return string|false SVG content or false on failure
     */
    private function _get_processed_svg_content($logo_path)
    {
        if (!file_exists($logo_path)) {
            return false;
        }

        $content = file_get_contents($logo_path);
        if ($content === false) {
            return false;
        }

        // Basic SVG processing - can be extended
        return $content;
    }

    /**
     * Allow SVG file uploads
     */
    public function allow_svg_upload($mimes)
    {
        $mimes['svg'] = 'image/svg+xml';
        $mimes['svgz'] = 'image/svg+xml';
        return $mimes;
    }

    /**
     * Check SVG security on upload
     */
    public function check_svg_security($file)
    {
        $wp_filetype = wp_check_filetype($file['name'], null);
        if ($wp_filetype['type'] === 'image/svg+xml') {
            // Basic SVG validation - can be extended
            $svg_content = file_get_contents($file['tmp_name']);
            if (empty($svg_content)) {
                $file['error'] = 'SVG file is empty';
            }
        }
        return $file;
    }

    /**
     * Display SVG upload notices
     */
    public function svg_upload_notice()
    {
        if (isset($_GET['page']) && $_GET['page'] === 'freight-products') {
            if (isset($_GET['logo_success'])) {
                echo '<div class="notice notice-success is-dismissible"><p>✅ Logo uploaded successfully!</p></div>';
            }
            if (isset($_GET['logo_error'])) {
                echo '<div class="notice notice-error is-dismissible"><p>❌ Logo upload failed.</p></div>';
            }
        }
    }

    /**
     * Handle logo upload
     */
    public function handle_logo_upload()
    {
        if (!isset($_FILES['logo_file']) || !current_user_can('manage_options')) {
            wp_redirect(admin_url('admin.php?page=freight-products&logo_error=1'));
            exit;
        }

        $file = $_FILES['logo_file'];
        $upload = wp_handle_upload($file, array('test_form' => false));

        if (isset($upload['error'])) {
            wp_redirect(admin_url('admin.php?page=freight-products&logo_error=1'));
            exit;
        }

        update_option('apd_default_logo', $upload['url']);
        wp_redirect(admin_url('admin.php?page=freight-products&logo_success=1'));
        exit;
    }

    /**
     * Test AJAX handler
     */
    public function test_ajax_handler()
    {
        error_log('APD Test AJAX: Handler called');
        error_log('APD Test AJAX: POST data: ' . print_r($_POST, true));

        wp_send_json_success(array(
            'message' => 'AJAX is working!',
            'post_data' => $_POST,
            'timestamp' => current_time('mysql')
        ));
    }

    /**
     * Add form enctype for file uploads on product edit pages
     */
    public function add_form_enctype()
    {
        global $post_type;
        if ($post_type === 'apd_product') {
            echo ' enctype="multipart/form-data"';
        }
    }
}
} // End if (!class_exists('AdvancedProductDesigner'))

// Include block registration
require_once APD_PLUGIN_PATH . 'includes/block-registration.php';

// Include health check system
if (file_exists(APD_PLUGIN_PATH . 'includes/class-apd-health-check.php')) {
    require_once APD_PLUGIN_PATH . 'includes/class-apd-health-check.php';
}

// Include debug logger
if (file_exists(APD_PLUGIN_PATH . 'includes/class-apd-debug-logger.php')) {
    require_once APD_PLUGIN_PATH . 'includes/class-apd-debug-logger.php';
}

// Initialize the plugin
if (!function_exists('apd_init')) {
    function apd_init()
    {
        global $advanced_product_designer;
        $advanced_product_designer = new AdvancedProductDesigner();
        new APD_Block_Registration();
        
        // Initialize health check and debug logger
        if (class_exists('APD_Health_Check')) {
            new APD_Health_Check();
        }
        if (class_exists('APD_Debug_Logger')) {
            APD_Debug_Logger::get_instance();
        }
    }
}

add_action('plugins_loaded', 'apd_init');

// Register activation and deactivation hooks
register_activation_hook(__FILE__, array('APD_Activation', 'activate_static'));
register_deactivation_hook(__FILE__, array('APD_Activation', 'deactivate_static'));
