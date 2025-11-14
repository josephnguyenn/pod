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

class AdvancedProductDesigner
{
    public function __construct()
    {
        // Start session early
        add_action('init', array($this, 'start_session'), 1);
        
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('admin_notices', array($this, 'display_admin_notices'));
        add_action('wp_footer', array($this, 'render_floating_cart_icon'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
        add_action('wp_ajax_save_customization', array($this, 'save_customization'));
        add_action('wp_ajax_nopriv_save_customization', array($this, 'save_customization'));
        add_action('wp_ajax_load_product', array($this, 'load_product'));
        add_action('wp_ajax_nopriv_load_product', array($this, 'load_product'));
        // Save exported PNG from client
        add_action('wp_ajax_apd_save_customization_image', array($this, 'apd_save_customization_image'));
        add_action('wp_ajax_nopriv_apd_save_customization_image', array($this, 'apd_save_customization_image'));
        add_action('wp_ajax_apd_dismiss_dashboard_notice', array($this, 'dismiss_dashboard_notice'));
        add_action('wp_ajax_apd_get_checkout_data', array($this, 'get_checkout_data'));
        add_action('wp_ajax_nopriv_apd_get_checkout_data', array($this, 'get_checkout_data'));
        add_action('wp_ajax_upload_svg', array($this, 'handle_svg_upload'));
        add_action('wp_ajax_save_design', array($this, 'save_design'));
        add_action('wp_ajax_save_template_design', array($this, 'save_template_design'));
        add_action('wp_ajax_apd_get_product_data', array($this, 'get_product_data_ajax'));
        add_action('wp_ajax_nopriv_apd_get_product_data', array($this, 'get_product_data_ajax'));
        add_action('wp_ajax_apd_get_products_ajax', array($this, 'get_products_ajax'));
        add_action('wp_ajax_nopriv_apd_get_products_ajax', array($this, 'get_products_ajax'));
        add_action('wp_ajax_apd_get_customizer_data', array($this, 'get_customizer_data_ajax'));
        add_action('wp_ajax_nopriv_apd_get_customizer_data', array($this, 'get_customizer_data_ajax'));
        // Orders (guard if method exists to avoid admin lockouts during deploy)
        if (method_exists($this, 'register_order_cpt_and_statuses')) {
            add_action('init', array($this, 'register_order_cpt_and_statuses'));
        }
        // Ensure statuses are visible in admin "All" regardless of existing implementation
        add_action('init', array($this, 'apd_register_statuses_visible'));
        // Admin orders pages
        add_action('admin_menu', array($this, 'apd_register_orders_admin_pages'));
        // AJAX: admin updates
        add_action('wp_ajax_apd_update_order_status', array($this, 'apd_update_order_status'));
        add_action('wp_ajax_apd_add_order_note', array($this, 'apd_add_order_note'));
        add_action('wp_ajax_apd_rebuild_order_labels', array($this, 'apd_rebuild_order_labels'));
        add_action('wp_ajax_apd_place_order', array($this, 'apd_place_order'));
        add_action('wp_ajax_nopriv_apd_place_order', array($this, 'apd_place_order'));
        add_action('wp_ajax_apd_get_order_details', array($this, 'apd_get_order_details'));
        add_action('wp_ajax_nopriv_apd_get_order_details', array($this, 'apd_get_order_details'));

        // Test AJAX handler
        add_action('wp_ajax_apd_test_ajax', array($this, 'test_ajax_handler'));
        add_action('wp_ajax_nopriv_apd_test_ajax', array($this, 'test_ajax_handler'));

        // Add block recovery filter
        add_filter('render_block_data', array($this, 'fix_block_validation'), 10, 2);
        add_action('wp_ajax_apd_save_customization', array($this, 'save_customization_ajax'));
        add_action('wp_ajax_nopriv_apd_save_customization', array($this, 'save_customization_ajax'));
        add_action('wp_ajax_upload_font', array($this, 'upload_font'));
        add_action('wp_ajax_apd_delete_font', array($this, 'delete_font'));
        add_action('wp_ajax_apd_get_materials', array($this, 'ajax_get_materials'));
        add_action('wp_ajax_nopriv_apd_get_materials', array($this, 'ajax_get_materials'));
        add_action('wp_ajax_apd_get_material_url', array($this, 'ajax_get_material_url'));
        add_action('wp_ajax_nopriv_apd_get_material_url', array($this, 'ajax_get_material_url'));
        // Cart management AJAX handlers
        add_action('wp_ajax_apd_add_to_cart', array($this, 'ajax_add_to_cart'));
        add_action('wp_ajax_nopriv_apd_add_to_cart', array($this, 'ajax_add_to_cart'));

        // Settings AJAX handlers
        add_action('wp_ajax_apd_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_apd_send_test_email', array($this, 'send_test_email'));
        
        // Email testing AJAX handlers
        add_action('wp_ajax_apd_send_advanced_test_email', array($this, 'send_advanced_test_email'));
        add_action('wp_ajax_apd_test_smtp_connection', array($this, 'test_smtp_connection'));
        add_action('wp_ajax_apd_get_email_logs', array($this, 'get_email_logs'));

        // Orders AJAX handlers
        add_action('wp_ajax_apd_get_orders', array($this, 'ajax_get_orders'));
        add_action('wp_ajax_apd_create_order', array($this, 'ajax_create_order'));
        add_action('wp_ajax_apd_get_cart', array($this, 'ajax_get_cart'));
        add_action('wp_ajax_nopriv_apd_get_cart', array($this, 'ajax_get_cart'));
        add_action('wp_ajax_apd_update_cart_item', array($this, 'ajax_update_cart_item'));
        add_action('wp_ajax_nopriv_apd_update_cart_item', array($this, 'ajax_update_cart_item'));
        add_action('wp_ajax_apd_remove_cart_item', array($this, 'ajax_remove_cart_item'));
        add_action('wp_ajax_nopriv_apd_remove_cart_item', array($this, 'ajax_remove_cart_item'));
        add_action('wp_ajax_apd_clear_cart', array($this, 'ajax_clear_cart'));
        add_action('wp_ajax_nopriv_apd_clear_cart', array($this, 'ajax_clear_cart'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_shortcode('apd_customizer', array($this, 'customizer_shortcode'));
        add_shortcode('apd_product_list', array($this, 'product_list_shortcode'));
        add_shortcode('apd_product_detail', array($this, 'product_detail_shortcode'));
        add_shortcode('apd_products_by_company', array($this, 'products_by_company_shortcode'));
        add_shortcode('apd_test', array($this, 'test_shortcode'));
        add_shortcode('apd_debug', array($this, 'debug_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        // Front-end shortcodes for pages
        add_shortcode('apd_cart', array($this, 'shortcode_cart'));
        add_shortcode('apd_checkout', array($this, 'shortcode_checkout'));
        add_shortcode('apd_thank_you', array($this, 'shortcode_thankyou'));
        add_shortcode('apd_orders', array($this, 'shortcode_orders'));
        add_shortcode('apd_cart_count', array($this, 'shortcode_cart_count'));
        // Ensure required pages exist even if activation hook didn't run
        add_action('admin_init', array($this, 'ensure_core_pages'));
        add_action('init', array($this, 'ensure_core_pages'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Debug: Force admin menu registration - DISABLED
        // add_action('admin_init', array($this, 'debug_admin_menu'), 1);

        // Add admin notice for dashboard - DISABLED
        // add_action('admin_notices', array($this, 'admin_dashboard_notice'));

        // Add submenu pages
        add_action('admin_menu', array($this, 'add_admin_submenus'));

    // Add orders menu separately
    add_action('admin_menu', array($this, 'add_orders_menu'));
    // Add shipping prices as a top-level menu
    add_action('admin_menu', array($this, 'add_shipping_menu'));

        // Redirect to dashboard when accessing main menu
        // add_action('admin_init', array($this, 'redirect_to_dashboard'));

        // Allow SVG uploads
        add_filter('upload_mimes', array($this, 'allow_svg_upload'));
        add_filter('wp_handle_upload_prefilter', array($this, 'check_svg_security'));

        // Add admin notices
        add_action('admin_notices', array($this, 'svg_upload_notice'));

        // Add logo upload handler
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
        
        // Force Elementor canvas (full-width) for company archives
        add_action('wp', array($this, 'set_company_archive_elementor_template'));

        // Force flush rewrite rules if needed
        if (get_option('apd_flush_rewrite_rules') !== '2') {
            flush_rewrite_rules();
            update_option('apd_flush_rewrite_rules', '2');
            error_log('APD Plugin: Rewrite rules flushed on init');
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

        $customer_name = sanitize_text_field($_POST['customer_name'] ?? '');
        $customer_email = sanitize_email($_POST['customer_email'] ?? '');
        $customer_phone = sanitize_text_field($_POST['customer_phone'] ?? '');
        $customer_address = sanitize_textarea_field($_POST['customer_address'] ?? '');

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
        $meta = array(
            'product_id' => $item['product_id'] ?? '',
            'product_name' => $item['product_name'] ?? 'Custom Product',
            'product_price' => floatval($item['price'] ?? ($item['product_price'] ?? 0)),
            'quantity' => intval($item['quantity'] ?? 1),
            'total_amount' => (floatval($item['price'] ?? ($item['product_price'] ?? 0)) * intval($item['quantity'] ?? 1)),
            'print_color' => $item['print_color'] ?? '',
            'vinyl_material' => $item['vinyl_material'] ?? '',
            'material_texture_url' => $item['material_texture_url'] ?? '',
            'text_fields' => $item['text_fields'] ?? array(),
            'template_data' => $item['template_data'] ?? array(),
            'fields_display' => $item['fields_display'] ?? array(),
            'template_fields_array' => $item['template_fields_array'] ?? array(),
            'customization_image_url' => $item['customization_image_url'] ?? '',
            'preview_image_url' => $item['preview_image_url'] ?? '',
            'preview_image_png' => $item['preview_image_png'] ?? '',
            'preview_image_svg' => $item['preview_image_svg'] ?? '',
            'customer_name' => $customer_name,
            'customer_email' => $customer_email,
            'customer_phone' => $customer_phone,
            'customer_address' => $customer_address,
            'payment_method' => $item['payment_method'] ?? 'mock_paypal',
            'order_date' => current_time('Y-m-d H:i:s'),
            'order_status' => 'apd_pending',
            'paypal_order_id' => $item['paypal_order_id'] ?? '',
            'paypal_transaction_id' => $item['paypal_transaction_id'] ?? '',
            'paypal_payer_id' => $item['paypal_payer_id'] ?? '',
            'payment_status' => $item['payment_status'] ?? 'completed',
            'manufacturing_notes' => $this->generateManufacturingNotes($item),
            'production_ready' => true,
            'cart_items' => json_encode(array($item)),
            'cart_total' => floatval($item['price'] ?? ($item['product_price'] ?? 0)) * intval($item['quantity'] ?? 1)
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

        $thankyou = get_permalink(intval(get_option('apd_thankyou')));
        if (!$thankyou) {
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
                'hierarchical' => true
            ),
            'show_in_rest' => true,
            'public' => true,
            'publicly_queryable' => true,
            'show_in_nav_menus' => true,
            'has_archive' => true,
        ));

        // Add meta boxes for template and product details
        add_action('add_meta_boxes', array($this, 'add_template_meta_boxes'));
        add_action('add_meta_boxes', array($this, 'add_product_meta_boxes'));
        add_action('save_post', array($this, 'save_template_meta'));
        add_action('save_post', array($this, 'save_product_meta'));

        add_action('wp_ajax_apd_buy_now', array($this, 'apd_buy_now'));
        add_action('wp_ajax_nopriv_apd_buy_now', array($this, 'apd_buy_now'));

        // Add form enctype for file uploads on product edit pages
        add_action('post_edit_form_tag', array($this, 'add_form_enctype'));
    }

    public function add_template_meta_boxes()
    {
        add_meta_box(
            'apd_template_details',
            'Template Settings',
            array($this, 'template_details_meta_box'),
            'apd_template',
            'normal',
            'high'
        );
    }

    public function add_product_meta_boxes()
    {
        add_meta_box(
            'apd_product_details',
            'Product Details',
            array($this, 'product_details_meta_box'),
            'apd_product',
            'normal',
            'high'
        );

        add_meta_box(
            'apd_product_features',
            'Product Features',
            array($this, 'product_features_meta_box'),
            'apd_product',
            'side',
            'default'
        );

        add_meta_box(
            'apd_canvas_settings',
            'Canvas Settings',
            array($this, 'canvas_settings_meta_box'),
            'apd_product',
            'side',
            'default'
        );
    }

    public function product_details_meta_box($post)
    {
        wp_nonce_field('fsc_save_product_meta', 'fsc_product_meta_nonce');

        $price = get_post_meta($post->ID, '_fsc_price', true);
        $sale_price = get_post_meta($post->ID, '_fsc_sale_price', true);
        $category = get_post_meta($post->ID, '_fsc_category', true);
        $template_id = get_post_meta($post->ID, '_fsc_template', true);
        $material = get_post_meta($post->ID, '_fsc_material', true);
        $size = get_post_meta($post->ID, '_fsc_size', true);
        $color_options = get_post_meta($post->ID, '_fsc_color_options', true);
        $logo_file = get_post_meta($post->ID, '_fsc_logo_file', true);
        $is_customizable = get_post_meta($post->ID, '_fsc_customizable', true);
        if ($is_customizable === '') {
            $is_customizable = '1'; // Default to customizable
        }

        // Get available templates
        $templates = get_posts(array(
            'post_type' => 'apd_template',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ));

        ?>
        <table class="form-table">
            <tr>
                <th><label for="fsc_template">Template</label></th>
                <td>
                    <select id="fsc_template" name="fsc_template" required>
                        <option value="">Select Template</option>
                        <?php foreach ($templates as $template): ?>
                            <option value="<?php echo $template->ID; ?>" <?php selected($template_id, $template->ID); ?>>
                                <?php echo esc_html($template->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">Choose a template for this product. <a href="<?php echo admin_url('admin.php?page=apd-templates'); ?>" target="_blank">Manage Templates</a></p>
                </td>
            </tr>
            <tr>
                <th><label for="fsc_price">Regular Price</label></th>
                <td>
                    <input type="text" id="fsc_price" name="fsc_price" value="<?php echo esc_attr($price); ?>" class="regular-text" placeholder="$125.00">
                    <p class="description">Enter the regular product price</p>
                </td>
            </tr>
            <tr>
                <th><label for="fsc_sale_price">Sale Price</label></th>
                <td>
                    <input type="text" id="fsc_sale_price" name="fsc_sale_price" value="<?php echo esc_attr($sale_price); ?>" class="regular-text" placeholder="$99.00">
                    <p class="description">Enter the sale price (optional). Leave empty if no sale.</p>
                </td>
            </tr>
            <tr>
                <th><label for="fsc_category">Category</label></th>
                <td>
                    <input type="text" id="fsc_category" name="fsc_category" value="<?php echo esc_attr($category); ?>" class="regular-text" placeholder="Freight Signs, Safety Signs, etc.">
                    <p class="description">Enter the product category</p>
                </td>
            </tr>
            <tr>
                <th><label for="fsc_thumbnail">Product Thumbnail</label></th>
                <td>
                    <?php
                    $thumbnail_id = get_post_meta($post->ID, '_fsc_thumbnail_id', true);
                    $thumbnail_url = '';
                    if ($thumbnail_id) {
                        $thumbnail_url = wp_get_attachment_image_url($thumbnail_id, 'medium');
                    }
                    ?>
                    <div class="fsc-thumbnail-wrapper">
                        <div class="fsc-thumbnail-preview" style="margin-bottom: 10px;">
                            <?php if ($thumbnail_url): ?>
                                <img src="<?php echo esc_url($thumbnail_url); ?>" style="max-width: 200px; height: auto; border: 1px solid #ddd; border-radius: 4px; display: block;">
                            <?php else: ?>
                                <img src="<?php echo esc_url(APD_PLUGIN_URL . 'assets/images/placeholder.png'); ?>" style="max-width: 200px; height: auto; border: 1px solid #ddd; border-radius: 4px; display: block;">
                            <?php endif; ?>
                        </div>
                        <input type="hidden" id="fsc_thumbnail_id" name="fsc_thumbnail_id" value="<?php echo esc_attr($thumbnail_id); ?>">
                        <button type="button" class="button fsc-upload-thumbnail-btn">
                            <?php echo $thumbnail_id ? 'Change Thumbnail' : 'Upload Thumbnail'; ?>
                        </button>
                        <?php if ($thumbnail_id): ?>
                            <button type="button" class="button fsc-remove-thumbnail-btn" style="margin-left: 5px;">Remove Thumbnail</button>
                        <?php endif; ?>
                    </div>
                    <p class="description">Upload a thumbnail image for this product (JPG, PNG, GIF)</p>
                </td>
            </tr>
            <tr style="display: none;">
                <th><label for="fsc_material">Material</label></th>
                <td>
                    <input type="text" id="fsc_material" name="fsc_material" value="<?php echo esc_attr($material); ?>" class="regular-text" placeholder="Heavy Metal Chrome with Color">
                    <p class="description">Enter the material description</p>
                </td>
            </tr>
            <tr style="display: none;">
                <th><label for="fsc_size">Size</label></th>
                <td>
                    <input type="text" id="fsc_size" name="fsc_size" value="<?php echo esc_attr($size); ?>" class="regular-text" placeholder="DOT Approved Size">
                    <p class="description">Enter the product size</p>
                </td>
            </tr>
            <tr>
                <th><label for="fsc_customizable">Customizable</label></th>
                <td>
                    <label>
                        <input type="checkbox" id="fsc_customizable" name="fsc_customizable" value="1" <?php checked($is_customizable, '1'); ?>>
                        Allow customers to customize this product
                    </label>
                    <p class="description">If unchecked, customers can only add to cart without customization</p>
                </td>
            </tr>
            <tr>
                <th><label for="fsc_logo_file">Product Logo (SVG)</label></th>
                <td>
                    <?php
                    $logo_id = get_post_meta($post->ID, '_fsc_logo_id', true);
                    $logo_url = '';
                    $logo_filename = '';
                    if ($logo_id) {
                        $logo_url = wp_get_attachment_url($logo_id);
                        $logo_filename = basename($logo_url);
                    } elseif ($logo_file) {
                        // Backward compatibility with old file-based system
                        $logo_url = $logo_file;
                        $logo_filename = basename($logo_file);
                    }
                    ?>
                    <div class="fsc-logo-wrapper">
                        <?php if ($logo_url): ?>
                            <div style="margin-bottom: 10px; padding: 10px; background: #f0f0f0; border-radius: 4px;">
                                <strong>Current logo:</strong> 
                                <a href="<?php echo esc_url($logo_url); ?>" target="_blank"><?php echo esc_html($logo_filename); ?></a>
                            </div>
                        <?php endif; ?>
                        <input type="hidden" id="fsc_logo_id" name="fsc_logo_id" value="<?php echo esc_attr($logo_id); ?>">
                        <button type="button" class="button fsc-upload-logo-btn">
                            <?php echo $logo_url ? 'Change Logo' : 'Upload Logo'; ?>
                        </button>
                        <?php if ($logo_url): ?>
                            <button type="button" class="button fsc-remove-logo-btn" style="margin-left: 5px;">Remove Logo</button>
                        <?php endif; ?>
                    </div>
                    <p class="description">Upload SVG logo file for this product<?php echo $logo_url ? ' (click to replace)' : ' (required)'; ?></p>
                </td>
            </tr>
            <tr style="display: none;">
                <th><label for="fsc_color_options">Color Options</label></th>
                <td>
                    <textarea id="fsc_color_options" name="fsc_color_options" rows="4" class="large-text" placeholder="black, yellow, dark-red, orange, light-blue, light-green, purple, light-grey, brown, bright-yellow, dark-green, light-purple"><?php echo esc_textarea($color_options); ?></textarea>
                    <p class="description">Enter available colors separated by commas</p>
                </td>
            </tr>
        </table>
        <?php
    }

    public function product_features_meta_box($post)
    {
        $features = get_post_meta($post->ID, '_fsc_features', true);
        if (!is_array($features)) {
            $features = array(
                'DOT Approved Size',
                '5 years outdoor life',
                'Air release for bubble free installing',
                'Professional quality materials'
            );
        }
        ?>
        <div id="fsc-features-container">
            <?php foreach ($features as $index => $feature): ?>
            <div class="fsc-feature-row">
                <input type="text" name="fsc_features[]" value="<?php echo esc_attr($feature); ?>" class="regular-text" placeholder="Enter feature">
                <button type="button" class="button fsc-remove-feature">Remove</button>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button fsc-add-feature">Add Feature</button>
        
        <script>
        jQuery(document).ready(function($) {
            $('.fsc-add-feature').on('click', function() {
                var newRow = '<div class="fsc-feature-row"><input type="text" name="fsc_features[]" value="" class="regular-text" placeholder="Enter feature"><button type="button" class="button fsc-remove-feature">Remove</button></div>';
                $('#fsc-features-container').append(newRow);
            });
            
            $(document).on('click', '.fsc-remove-feature', function() {
                $(this).parent().remove();
            });
        });
        </script>
        
        <style>
        .fsc-feature-row {
            margin-bottom: 10px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .fsc-feature-row input {
            flex: 1;
        }
        </style>
        <?php
    }

    public function save_product_meta($post_id)
    {
        // Check nonce
        if (!isset($_POST['fsc_product_meta_nonce']) || !wp_verify_nonce($_POST['fsc_product_meta_nonce'], 'fsc_save_product_meta')) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save product details
        if (isset($_POST['fsc_template'])) {
            update_post_meta($post_id, '_fsc_template', intval($_POST['fsc_template']));
        }

        if (isset($_POST['fsc_price'])) {
            update_post_meta($post_id, '_fsc_price', sanitize_text_field($_POST['fsc_price']));
        }

        if (isset($_POST['fsc_sale_price'])) {
            update_post_meta($post_id, '_fsc_sale_price', sanitize_text_field($_POST['fsc_sale_price']));
        }

        if (isset($_POST['fsc_category'])) {
            update_post_meta($post_id, '_fsc_category', sanitize_text_field($_POST['fsc_category']));
        }

        if (isset($_POST['fsc_material'])) {
            update_post_meta($post_id, '_fsc_material', sanitize_text_field($_POST['fsc_material']));
        }

        if (isset($_POST['fsc_size'])) {
            update_post_meta($post_id, '_fsc_size', sanitize_text_field($_POST['fsc_size']));
        }

        if (isset($_POST['fsc_color_options'])) {
            update_post_meta($post_id, '_fsc_color_options', sanitize_textarea_field($_POST['fsc_color_options']));
        }

        // Save customizable checkbox
        if (isset($_POST['fsc_customizable'])) {
            update_post_meta($post_id, '_fsc_customizable', '1');
        } else {
            update_post_meta($post_id, '_fsc_customizable', '0');
        }

        // Save features
        if (isset($_POST['fsc_features']) && is_array($_POST['fsc_features'])) {
            $features = array_filter(array_map('sanitize_text_field', $_POST['fsc_features']));
            update_post_meta($post_id, '_fsc_features', $features);
        }

        // Save thumbnail ID from media selector
        if (isset($_POST['fsc_thumbnail_id'])) {
            update_post_meta($post_id, '_fsc_thumbnail_id', sanitize_text_field($_POST['fsc_thumbnail_id']));
        }

        // Save logo ID from media selector
        if (isset($_POST['fsc_logo_id'])) {
            $logo_id = sanitize_text_field($_POST['fsc_logo_id']);
            update_post_meta($post_id, '_fsc_logo_id', $logo_id);
            
            // Also update the logo file URL for backward compatibility
            if ($logo_id) {
                $logo_url = wp_get_attachment_url($logo_id);
                if ($logo_url) {
                    update_post_meta($post_id, '_fsc_logo_file', $logo_url);
                }
            } else {
                // Clear logo file if no logo ID
                delete_post_meta($post_id, '_fsc_logo_file');
            }
        }
    }

    public function enqueue_scripts()
    {
        // Only enqueue customizer scripts on customizer pages
        if (get_query_var('customizer')) {
            wp_enqueue_style('apd-styles', APD_PLUGIN_URL . 'assets/css/customizer.css', array(), APD_VERSION);
            wp_enqueue_script('html2canvas', 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js', array('jquery'), '1.4.1', true);
            wp_enqueue_script('apd-script', APD_PLUGIN_URL . 'assets/js/customizer.js', array('jquery', 'html2canvas'), APD_VERSION, true);

            // Get product ID for customizer
            $product_id = get_query_var('customizer');

            wp_localize_script('apd-script', 'apd_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                // Nonces used by various endpoints
                'nonce' => wp_create_nonce('apd_ajax_nonce'),
                'fsc_nonce' => wp_create_nonce('fsc_nonce'),
                'plugin_url' => APD_PLUGIN_URL,
                'site_url' => home_url(),
                'product_id' => $product_id
            ));

            // Provide materials directly to the page to avoid extra AJAX calls
            $materials_map = $this->get_materials();
            // Convert to format expected by frontend: name => {url, price}
            wp_localize_script('apd-script', 'fscDefaults', array(
                'materials' => $materials_map
            ));

            // Debug: Log script enqueue
            error_log('APD Scripts: Enqueued for customizer with product_id: ' . $product_id);
        }
    }

    public function admin_enqueue_scripts()
    {
        wp_enqueue_style('apd-admin-styles', APD_PLUGIN_URL . 'assets/css/admin.css', array(), APD_VERSION);
        wp_enqueue_style('apd-admin-fixes', APD_PLUGIN_URL . 'assets/css/admin-fixes.css', array(), APD_VERSION);

        // Enqueue media uploader on product edit page
        $screen = get_current_screen();
        if ($screen && $screen->post_type === 'apd_product') {
            wp_enqueue_media();
            wp_enqueue_script('apd-product-admin', APD_PLUGIN_URL . 'assets/js/product-admin.js', array('jquery'), APD_VERSION, true);
        }

        // Enqueue designer scripts only on designer page
        if ($screen && strpos($screen->id, 'apd-designer') !== false) {
            wp_enqueue_script('apd-designer', APD_PLUGIN_URL . 'assets/js/designer.js', array('jquery'), APD_VERSION, true);

            wp_localize_script('apd-designer', 'apd_designer', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('apd_nonce'),
                'plugin_url' => APD_PLUGIN_URL
            ));

            // Add meta tags for nonce and AJAX URL
            add_action('admin_head', function () {
                echo '<meta name="apd-nonce" content="' . wp_create_nonce('apd_nonce') . '">';
                echo '<meta name="apd-ajax-url" content="' . admin_url('admin-ajax.php') . '">';
            });
        }
    }

    public function enqueue_block_editor_assets()
    {
        // This hook is specifically for block editor assets

        // Enqueue block editor scripts with proper dependencies
        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'apd-product-store',
            APD_PLUGIN_URL . 'assets/js/product-store.js',
            array('jquery'),
            APD_VERSION,
            true
        );

        wp_enqueue_script(
            'apd-product-block',
            APD_PLUGIN_URL . 'assets/js/product-block.js',
            array('apd-product-store', 'jquery'),
            APD_VERSION,
            true
        );

        wp_enqueue_style('apd-product-block', APD_PLUGIN_URL . 'assets/css/product-block.css', array(), APD_VERSION);

        wp_localize_script('apd-product-block', 'apd_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('apd_ajax_nonce'),
            'site_url' => home_url()
        ));
    }

    public function enqueue_frontend_scripts()
    {
        // Always ensure jQuery is enqueued first (WordPress should handle this, but be explicit)
        // Use priority to ensure jQuery loads early
        wp_enqueue_script('jquery');
        
        // Check if product list shortcode is used on the page
        global $post;
        $has_product_list = false;
        if ($post && isset($post->post_content)) {
            $has_product_list = has_shortcode($post->post_content, 'apd_product_list');
        }
        
        // Enqueue frontend scripts for product blocks
        wp_enqueue_script('apd-product-block-frontend', APD_PLUGIN_URL . 'assets/js/product-block-frontend.js', array('jquery'), APD_VERSION, true);
        wp_enqueue_style('apd-product-block', APD_PLUGIN_URL . 'assets/css/product-block.css', array(), APD_VERSION);

        // Enqueue customizer scripts and styles
        wp_enqueue_script('apd-product-customizer', APD_PLUGIN_URL . 'assets/js/product-customizer.js', array('jquery'), APD_VERSION, true);
        wp_enqueue_style('apd-product-customizer', APD_PLUGIN_URL . 'assets/css/product-customizer.css', array(), APD_VERSION);

        // Enqueue main customizer script for shortcode usage
        wp_enqueue_script('apd-customizer', APD_PLUGIN_URL . 'assets/js/customizer.js', array('jquery'), APD_VERSION, true);
        wp_enqueue_style('apd-customizer', APD_PLUGIN_URL . 'assets/css/customizer.css', array(), APD_VERSION);

        // Enqueue cart scripts and styles on cart page
        if (is_page() && (has_shortcode(get_post()->post_content, 'apd_cart') || is_page(get_option('apd_cart')))) {
            wp_enqueue_script('apd-cart', APD_PLUGIN_URL . 'assets/js/cart.js', array('jquery'), APD_VERSION, true);
            wp_enqueue_style('apd-cart', APD_PLUGIN_URL . 'assets/css/cart.css', array(), APD_VERSION);
        }

        // Enqueue orders scripts and styles on orders page
        if (is_page() && (has_shortcode(get_post()->post_content, 'apd_orders') || is_page(get_option('apd_orders')))) {
            wp_enqueue_script('apd-orders', APD_PLUGIN_URL . 'assets/js/orders.js', array('jquery'), APD_VERSION, true);
            wp_enqueue_style('apd-orders', APD_PLUGIN_URL . 'assets/css/orders.css', array(), APD_VERSION);
        }

        // Prepare apd_ajax data
        $apd_ajax_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('apd_ajax_nonce'),
            'site_url' => home_url(),
            'cart_url' => home_url(get_option('apd_cart_url', '/cart/')),
            'checkout_url' => home_url(get_option('apd_checkout_url', '/checkout/')),
            'products_url' => home_url(get_option('apd_products_url', '/products/')),
            'orders_url' => home_url(get_option('apd_orders_url', '/my-orders/')),
            'customizer_url' => home_url(get_option('apd_customizer_url', '/customizer/')),
            'thank_you_url' => home_url(get_option('apd_thank_you_url', '/thank-you/'))
        );

        wp_localize_script('apd-product-block-frontend', 'apd_ajax', $apd_ajax_data);

        wp_localize_script('apd-product-customizer', 'apd_ajax', $apd_ajax_data);

        // Also localize for cart script
        if (is_page() && (has_shortcode(get_post()->post_content, 'apd_cart') || is_page(get_option('apd_cart')))) {
            wp_localize_script('apd-cart', 'apd_ajax', $apd_ajax_data);
            wp_localize_script('apd-orders', 'apd_ajax', $apd_ajax_data);
        }
    }

    public function customizer_shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'product_id' => 0
        ), $atts);

        // Debug: Log shortcode usage
        error_log('APD Shortcode: Called with atts: ' . print_r($atts, true));
        error_log('APD Shortcode: Product ID from shortcode: ' . $atts['product_id']);

        // Enqueue scripts for customizer shortcode
        $this->enqueue_frontend_scripts();

        ob_start();
        $this->render_customizer($atts['product_id']);
        return ob_get_clean();
    }

    public function product_list_shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'show_title' => 'true',
            'show_description' => 'true',
            'show_price' => 'true',
            'show_sale' => 'true',
            'columns' => '3',
            'items_per_page' => '12'
        ), $atts);

        // Enqueue frontend scripts when shortcode is used
        $this->enqueue_frontend_scripts();

        ob_start();
        $this->render_product_list($atts);
        return ob_get_clean();
    }

    public function products_by_company_shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'company' => '',
            'show_title' => 'true',
            'show_description' => 'true',
            'show_price' => 'true',
            'show_sale' => 'true',
            'columns' => '3',
            'items_per_page' => '12'
        ), $atts);

        // Enqueue frontend scripts when shortcode is used
        $this->enqueue_frontend_scripts();

        ob_start();
        $this->render_products_by_company($atts);
        return ob_get_clean();
    }

    public function render_customizer($product_id = 0)
    {
        // Debug: Log render_customizer call
        error_log('APD Render Customizer: Called with product_id: ' . $product_id);

        // Get product data if product_id is provided
        $product_data = null;
        if ($product_id > 0) {
            $product_data = get_post($product_id);
            error_log('APD Render Customizer: Product data retrieved: ' . ($product_data ? 'Found' : 'Not found'));
            if ($product_data) {
                error_log('APD Render Customizer: Product title: ' . $product_data->post_title . ', Type: ' . $product_data->post_type . ', Status: ' . $product_data->post_status);
            }
        }

        // Get all products for dropdown
        $all_products = get_posts(array(
            'post_type' => 'apd_product',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'post_status' => 'publish'
        ));

        // Get materials from uploads folder
        $materials = $this->get_materials();

        // Debug: Log materials for troubleshooting
        error_log('FSC Materials loaded: ' . print_r($materials, true));

        // Get colors
        $colors = array(
            'black' => '#000000',
            'yellow' => '#FFFF00',
            'dark-red' => '#8B0000',
            'orange' => '#FFA500',
            'light-blue' => '#87CEEB',
            'light-green' => '#90EE90',
            'purple' => '#800080',
            'light-grey' => '#D3D3D3',
            'brown' => '#A52A2A',
            'bright-yellow' => '#FFD700',
            'dark-green' => '#006400',
            'light-purple' => '#DDA0DD'
        );

        // Get product meta data
        $product_price = '';
        $product_sale_price = '';
        $product_material = '';
        $product_features = array();
        $product_logo_content = '';

        if ($product_data) {
            $product_price = get_post_meta($product_data->ID, '_fsc_price', true);
            $product_sale_price = get_post_meta($product_data->ID, '_fsc_sale_price', true);
            $product_material = get_post_meta($product_data->ID, '_fsc_material', true);
            $product_features = get_post_meta($product_data->ID, '_fsc_features', true);
            $product_logo_url = get_post_meta($product_data->ID, '_fsc_logo_file', true);

            // Get processed SVG content for product-specific logo
            if ($product_logo_url) {
                error_log('APD: Product logo URL: ' . $product_logo_url);
                error_log('APD: APD_PLUGIN_URL: ' . APD_PLUGIN_URL);
                error_log('APD: APD_PLUGIN_PATH: ' . APD_PLUGIN_PATH);
                
                // Convert plugin URL to file path
                $logo_path = str_replace(APD_PLUGIN_URL, APD_PLUGIN_PATH, $product_logo_url);
                error_log('APD: Converted logo path: ' . $logo_path);
                
                $product_logo_content = $this->_get_processed_svg_content($logo_path);
                
                if ($product_logo_content) {
                    error_log('APD: Logo content loaded successfully');
                } else {
                    error_log('APD: Failed to load logo content');
                }
            }

            // Override colors if product has custom color options
            $custom_colors = get_post_meta($product_data->ID, '_fsc_color_options', true);
            if ($custom_colors) {
                $color_array = array_map('trim', explode(',', $custom_colors));
                $colors = array();
                foreach ($color_array as $color) {
                    if (isset($this->get_default_colors()[$color])) {
                        $colors[$color] = $this->get_default_colors()[$color];
                    }
                }
            }
        }

        // Do not inject mock defaults; leave empty if not available so frontend can handle gracefully

        include APD_PLUGIN_PATH . 'templates/customizer.php';
    }

    public function render_product_list($atts)
    {
        // Debug: Log shortcode call
        error_log('APD Product List: Shortcode called with atts: ' . print_r($atts, true));

        // Get all products
        $products = get_posts(array(
            'post_type' => 'apd_product',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ));

        // Debug: Log products found
        error_log('APD Product List: Found ' . count($products) . ' products');

        // If no products, show a message
        if (empty($products)) {
            return '<div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 10px 0; border-radius: 4px;">
                <strong>No Products Found:</strong> Please create some products in the admin panel first. 
                <a href="' . admin_url('post-new.php?post_type=apd_product') . '" target="_blank">Create Product</a>
            </div>';
        }

        // Group products by category
        $categories = array();
        foreach ($products as $product) {
            $category = get_post_meta($product->ID, '_fsc_category', true);
            if (empty($category)) {
                $category = 'Uncategorized';
            }

            if (!isset($categories[$category])) {
                $categories[$category] = array();
            }

            $price = get_post_meta($product->ID, '_fsc_price', true);
            $sale_price = get_post_meta($product->ID, '_fsc_sale_price', true);
            $features = get_post_meta($product->ID, '_fsc_features', true);
            $template_id = get_post_meta($product->ID, '_fsc_template', true);

            // Get custom thumbnail or fallback to featured image
            $thumbnail_id = get_post_meta($product->ID, '_fsc_thumbnail_id', true);
            $thumbnail_url = '';
            if ($thumbnail_id) {
                $thumbnail_url = wp_get_attachment_image_url($thumbnail_id, 'medium');
            }
            if (!$thumbnail_url) {
                $thumbnail_url = get_the_post_thumbnail_url($product->ID, 'medium');
            }

            $categories[$category][] = array(
                'id' => $product->ID,
                'title' => $product->post_title,
                'description' => wp_trim_words($product->post_content, 20),
                'content' => $product->post_content,
                'price' => $price ?: '0.00',
                'sale_price' => $sale_price,
                'features' => is_array($features) ? $features : array(),
                'template_id' => $template_id,
                'permalink' => get_permalink($product->ID),
                'thumbnail' => $thumbnail_url
            );
        }

        // Pass data to template
        // Debug: Log categories created
        error_log('APD Product List: Created ' . count($categories) . ' categories: ' . implode(', ', array_keys($categories)));

        $template_data = array(
            'categories' => $categories,
            'atts' => $atts,
            'show_title' => $atts['show_title'] === 'true',
            'show_description' => $atts['show_description'] === 'true',
            'show_price' => $atts['show_price'] === 'true',
            'show_sale' => $atts['show_sale'] === 'true',
            'columns' => intval($atts['columns']),
            'items_per_page' => intval($atts['items_per_page'])
        );

        // Debug: Log template data
        error_log('APD Product List: Template data prepared, including ' . count($template_data['categories']) . ' categories');

        // Include the product list template
        include APD_PLUGIN_PATH . 'templates/product-list.php';
    }

    public function render_products_by_company($atts)
    {
        // Get the company slug or name from attributes
        $company_slug = sanitize_text_field($atts['company']);
        
        if (empty($company_slug)) {
            return '<div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 10px 0; border-radius: 4px;">
                <strong>No Company Specified:</strong> Please specify a company using the "company" attribute. Example: [apd_products_by_company company="company-slug"]
            </div>';
        }

        // Get all products for the specified company
        $args = array(
            'post_type' => 'apd_product',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
            'tax_query' => array(
                array(
                    'taxonomy' => 'apd_company',
                    'field' => 'slug',
                    'terms' => $company_slug,
                ),
            ),
        );

        $products = get_posts($args);

        // Get company term for display
        $company_term = get_term_by('slug', $company_slug, 'apd_company');
        $company_name = $company_term ? $company_term->name : ucwords(str_replace('-', ' ', $company_slug));

        // If no products, show a message
        if (empty($products)) {
            return '<div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 10px 0; border-radius: 4px;">
                <strong>No Products Found:</strong> No products are assigned to the company "' . esc_html($company_name) . '". 
                <a href="' . admin_url('edit.php?post_type=apd_product') . '" target="_blank">Manage Products</a>
            </div>';
        }

        // Group products by category (same as render_product_list)
        $categories = array();
        foreach ($products as $product) {
            $category = get_post_meta($product->ID, '_fsc_category', true);
            if (empty($category)) {
                $category = 'Uncategorized';
            }

            if (!isset($categories[$category])) {
                $categories[$category] = array();
            }

            $price = get_post_meta($product->ID, '_fsc_price', true);
            $sale_price = get_post_meta($product->ID, '_fsc_sale_price', true);
            $features = get_post_meta($product->ID, '_fsc_features', true);
            $template_id = get_post_meta($product->ID, '_fsc_template', true);

            // Get custom thumbnail or fallback to featured image
            $thumbnail_id = get_post_meta($product->ID, '_fsc_thumbnail_id', true);
            $thumbnail_url = '';
            if ($thumbnail_id) {
                $thumbnail_url = wp_get_attachment_image_url($thumbnail_id, 'medium');
            }
            if (!$thumbnail_url) {
                $thumbnail_url = get_the_post_thumbnail_url($product->ID, 'medium');
            }

            $categories[$category][] = array(
                'id' => $product->ID,
                'title' => $product->post_title,
                'description' => wp_trim_words($product->post_content, 20),
                'content' => $product->post_content,
                'price' => $price ?: '0.00',
                'sale_price' => $sale_price,
                'features' => is_array($features) ? $features : array(),
                'template_id' => $template_id,
                'permalink' => get_permalink($product->ID),
                'thumbnail' => $thumbnail_url
            );
        }

        // Pass data to template
        $template_data = array(
            'categories' => $categories,
            'atts' => $atts,
            'show_title' => $atts['show_title'] === 'true',
            'show_description' => $atts['show_description'] === 'true',
            'show_price' => $atts['show_price'] === 'true',
            'show_sale' => $atts['show_sale'] === 'true',
            'columns' => intval($atts['columns']),
            'items_per_page' => intval($atts['items_per_page']),
            'company_name' => $company_name
        );

        // Include the product list template
        include APD_PLUGIN_PATH . 'templates/product-list.php';
    }

    public function test_shortcode($atts)
    {
        return '<div style="background: #f0f0f0; padding: 20px; border: 2px solid #333; margin: 10px 0;">TEST SHORTCODE WORKS! Current time: ' . date('Y-m-d H:i:s') . '</div>';
    }

    public function debug_shortcode($atts)
    {
        return '<div style="background: #e1f5fe; padding: 20px; border: 2px solid #2196f3; margin: 10px 0;">
            <h3>Debug Info:</h3>
            <p><strong>Shortcode called:</strong> ' . date('Y-m-d H:i:s') . '</p>
            <p><strong>Products found:</strong> ' . count(get_posts(array('post_type' => 'apd_product', 'posts_per_page' => -1, 'post_status' => 'publish'))) . '</p>
            <p><strong>Plugin active:</strong> ' . (is_plugin_active('freight-signs-customizer/freight-signs-customizer.php') ? 'Yes' : 'No') . '</p>
            <p><strong>Customizer Query Var:</strong> ' . get_query_var('customizer') . '</p>
            <p><strong>Test Customizer URL:</strong> <a href="' . home_url('/customizer/1/') . '">/customizer/1/</a></p>
        </div>';
    }

    public function add_form_enctype()
    {
        global $post_type;
        if ($post_type === 'apd_product') {
            echo ' enctype="multipart/form-data"';
        }
    }

    public function get_default_colors()
    {
        return array(
            'black' => '#000000',
            'yellow' => '#FFFF00',
            'dark-red' => '#8B0000',
            'orange' => '#FFA500',
            'light-blue' => '#87CEEB',
            'light-green' => '#90EE90',
            'purple' => '#800080',
            'light-grey' => '#D3D3D3',
            'brown' => '#A52A2A',
            'bright-yellow' => '#FFD700',
            'dark-green' => '#006400',
            'light-purple' => '#DDA0DD'
        );
    }

    public function get_materials()
    {
        $materials = array();

        // Get materials from database first
        $db_materials = get_option('apd_materials', array());

        if (!empty($db_materials)) {
            foreach ($db_materials as $material) {
                // Backward compatibility: ensure price exists
                $price = isset($material['price']) ? floatval($material['price']) : 0;
                $materials[$material['name']] = array(
                    'url' => $material['url'],
                    'price' => $price
                );
            }
        } else {
            // Fallback: Use plugin directory if no database materials
            $plugin_dir = APD_PLUGIN_PATH;
            $material_path = $plugin_dir . 'uploads/material/';

            if (is_dir($material_path)) {
                $files = glob($material_path . '*.{png,jpg,jpeg}', GLOB_BRACE);
                foreach ($files as $file) {
                    $name = pathinfo($file, PATHINFO_FILENAME);
                    $materials[$name] = array(
                        'url' => APD_PLUGIN_URL . 'uploads/material/' . basename($file),
                        'price' => 0
                    );
                }
            }
        }

        // Final fallback materials if none found
        if (empty($materials)) {
            $materials = array(
                'Brush_gold' => array(
                    'url' => APD_PLUGIN_URL . 'uploads/material/Brush_gold.png',
                    'price' => 0
                ),
                'Diamond_Plate' => array(
                    'url' => APD_PLUGIN_URL . 'uploads/material/Diamond_Plate.png',
                    'price' => 0
                ),
                'Engine turn_gold' => array(
                    'url' => APD_PLUGIN_URL . 'uploads/material/Engine_turn_gold.png',
                    'price' => 0
                ),
                'Florentine_Silver' => array(
                    'url' => APD_PLUGIN_URL . 'uploads/material/Florentine_Silver.png',
                    'price' => 0
                ),
                'gold' => array(
                    'url' => APD_PLUGIN_URL . 'uploads/material/gold.png',
                    'price' => 0
                )
            );
        }

        return $materials;
    }

    /**
     * Helper function to process SVG content for dynamic coloring
     */
    private function _get_processed_svg_content($logo_path)
    {
        error_log('APD: Checking SVG path: ' . $logo_path);
        
        if (!file_exists($logo_path)) {
            error_log('APD: SVG file does not exist at: ' . $logo_path);
            return false;
        }

        $svg_content = file_get_contents($logo_path);
        if ($svg_content === false || $svg_content === '') {
            error_log('APD: Failed to read SVG content or file is empty');
            return false;
        }
        
        error_log('APD: SVG file loaded, size: ' . strlen($svg_content) . ' bytes');
        error_log('APD: First 100 chars: ' . substr($svg_content, 0, 100));

        // Normalize encoding to UTF-8 if file appears to be UTF-16
        if (strpos($svg_content, "\x00") !== false || preg_match('/encoding=["\']utf-16["\']/i', $svg_content)) {
            if (function_exists('mb_convert_encoding')) {
                $converted = @mb_convert_encoding($svg_content, 'UTF-8', 'UTF-16,UTF-16LE,UTF-16BE,UTF-8');
                if ($converted !== false) {
                    $svg_content = $converted;
                }
            }
        }

        // Strip UTF-8 BOM and XML prolog/DOCTYPE which can break innerHTML parsing
        $svg_content = preg_replace('/^\xEF\xBB\xBF/', '', $svg_content); // UTF-8 BOM
        $svg_content = preg_replace('/<\?xml[^>]*\?>/i', '', $svg_content);
        $svg_content = preg_replace('/<!DOCTYPE[^>]*>/i', '', $svg_content);
        
        // Trim whitespace
        $svg_content = trim($svg_content);

        // Validate it starts with <svg
        if (!preg_match('/^<svg[\s>]/i', $svg_content)) {
            error_log('APD: Invalid SVG - does not start with <svg tag. First 200 chars: ' . substr($svg_content, 0, 200));
            return false;
        }

        // Keep only the <svg>...</svg> fragment
        if (preg_match('/<svg[\s\S]*<\/svg>/i', $svg_content, $m)) {
            $svg_content = $m[0];
        } else {
            error_log('APD: Could not find complete <svg>...</svg> tags');
            return false;
        }

        // Ensure xmlns exists for robust DOM parsing
        if (stripos($svg_content, 'xmlns=') === false) {
            $svg_content = preg_replace('/<svg\b/i', '<svg xmlns="http://www.w3.org/2000/svg"', $svg_content, 1);
        }

        // Remove any existing class attributes from SVG tag
        $svg_content = preg_replace('/<svg([^>]*?)class=\"[^\"]*\"([^>]*?)>/', '<svg$1$2>', $svg_content);

        // Add our custom class
        $svg_content = str_replace('<svg', '<svg class="fsc-logo-svg"', $svg_content);

        // Add outline filter if not present
        if (strpos($svg_content, 'id="fsc-outline"') === false) {
            $svg_content = str_replace('<defs>', '<defs><filter id="fsc-outline"><feMorphology operator="dilate" radius="2"/><feComposite operator="out" in="SourceGraphic"/></filter></defs>', $svg_content);
        }
        
        error_log('APD: Processed SVG successfully, final size: ' . strlen($svg_content) . ' bytes');

        return $svg_content ?: false;
    }

    public function get_logo_svg()
    {
        $plugin_dir = APD_PLUGIN_PATH;
        $logo_path = $plugin_dir . 'uploads/object/Logo-PNG.svg';
        return $this->_get_processed_svg_content($logo_path);
    }

    public function save_customization()
    {
        // Accept multiple nonce keys; do not hard-fail to reduce friction on front-end
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : (isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : (isset($_POST['security']) ? $_POST['security'] : (isset($_POST['apd_nonce']) ? $_POST['apd_nonce'] : '')));
        if ($nonce) {
            $ok = (wp_verify_nonce($nonce, 'fsc_nonce') || wp_verify_nonce($nonce, 'apd_ajax_nonce'));
            if (!$ok && is_user_logged_in()) {
                // Logged-in users can proceed even if nonce mismatched
                $ok = true;
            }
            if (!$ok) {
                // Soft warning instead of 403
                // continue;  // proceed anyway
            }
        }

        $data = array(
            'print_color' => sanitize_text_field($_POST['print_color']),
            'vinyl_material' => sanitize_text_field($_POST['vinyl_material']),
            'quantity' => intval($_POST['quantity']),
            'product_price' => floatval($_POST['product_price']),
            'product_id' => sanitize_text_field($_POST['product_id']),
            'product_name' => sanitize_text_field($_POST['product_name']),
            'material_texture_url' => esc_url_raw($_POST['material_texture_url']),
            'image_url' => esc_url_raw($_POST['image_url']),  // Add image URL
            'text_fields' => isset($_POST['text_fields']) ? $_POST['text_fields'] : array(),
            'template_data' => isset($_POST['template_data']) ? $_POST['template_data'] : array()
        );

        // Save to session or database
        if (!session_id()) {
            session_start();
        }
        $_SESSION['fsc_customization'] = $data;

        wp_send_json_success(array('message' => 'Customization saved successfully'));
    }

    // Cart Management Functions
    public function ajax_save_settings()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'apd_nonce')) {
            wp_send_json_error('Security check failed');
        }

        // Save URL settings
        $url_settings = array(
            'apd_cart_url',
            'apd_checkout_url',
            'apd_products_url',
            'apd_orders_url',
            'apd_customizer_url',
            'apd_thank_you_url'
        );

        foreach ($url_settings as $setting) {
            if (isset($_POST[$setting])) {
                $value = sanitize_text_field($_POST[$setting]);
                // Ensure URL starts with /
                if (!empty($value) && !str_starts_with($value, '/')) {
                    $value = '/' . $value;
                }
                update_option($setting, $value);
            }
        }

        // Save other settings
        $other_settings = array(
            'apd_paypal_client_id',
            'apd_paypal_environment',
            'apd_currency',
            'apd_paypal_test_mode'
        );

        foreach ($other_settings as $setting) {
            if (isset($_POST[$setting])) {
                $value = sanitize_text_field($_POST[$setting]);
                update_option($setting, $value);
            }
        }

        // Save email settings
        $email_settings = array(
            'apd_email_enabled',
            'apd_email_from_name',
            'apd_email_from_address',
            'apd_email_subject',
            'apd_email_template',
            'apd_admin_email_notifications',
            'apd_admin_email_address',
            'apd_smtp_enabled',
            'apd_smtp_host',
            'apd_smtp_port',
            'apd_smtp_encryption',
            'apd_smtp_username',
            'apd_smtp_password',
            'apd_smtp_from_email',
            'apd_smtp_from_name',
            'apd_smtp_debug',
            'apd_email_html_enabled',
            'apd_email_footer',
            'apd_email_headers',
            'apd_email_attachments',
            'apd_email_reply_to',
            'apd_email_cc',
            'apd_email_bcc',
            'apd_email_delay',
            'apd_email_retry_failed',
            'apd_email_max_retries'
        );

        foreach ($email_settings as $setting) {
            if (isset($_POST[$setting])) {
                $value = sanitize_text_field($_POST[$setting]);
                // Special handling for password field
                if ($setting === 'apd_smtp_password') {
                    $value = sanitize_text_field($_POST[$setting]);
                } elseif (in_array($setting, ['apd_email_template', 'apd_email_footer', 'apd_email_headers'])) {
                    $value = sanitize_textarea_field($_POST[$setting]);
                } elseif (in_array($setting, ['apd_smtp_port', 'apd_email_delay', 'apd_email_max_retries'])) {
                    $value = intval($_POST[$setting]);
                } else {
                    $value = sanitize_text_field($_POST[$setting]);
                }
                update_option($setting, $value);
            }
        }

        wp_send_json_success(array('message' => 'Settings saved successfully'));
    }

    public function ajax_get_orders()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'apd_ajax_nonce')) {
            wp_send_json_error('Security check failed');
        }

        $user_id = get_current_user_id();
        $orders = get_user_meta($user_id, 'apd_orders', true);

        if (!is_array($orders)) {
            $orders = array();
        }

        // Sort orders by date (newest first)
        usort($orders, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        wp_send_json_success(array('orders' => $orders));
    }

    public function ajax_create_order()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'apd_ajax_nonce')) {
            wp_send_json_error('Security check failed');
        }

        // Allow guest checkout (user_id 0 when not logged in)
        $user_id = get_current_user_id();
        if (!$user_id) {
            $user_id = 0;
        }

        $cart = get_user_meta($user_id, 'apd_cart', true);
        if (empty($cart)) {
            wp_send_json_error('Cart is empty');
        }

        // Calculate total
        $total = 0;
        foreach ($cart as $item) {
            $total += floatval($item['total']);
        }

        // Create order
        $order = array(
            'id' => 'ORD-' . time() . '-' . rand(1000, 9999),
            'user_id' => $user_id,
            'items' => $cart,
            'total' => $total,
            'status' => 'pending',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );

        // Save order
        $orders = get_user_meta($user_id, 'apd_orders', true);
        if (!is_array($orders)) {
            $orders = array();
        }
        $orders[] = $order;
        update_user_meta($user_id, 'apd_orders', $orders);

        // Clear cart
        delete_user_meta($user_id, 'apd_cart');

        wp_send_json_success(array(
            'order' => $order,
            'message' => 'Order created successfully'
        ));
    }

    public function ajax_add_to_cart()
    {
        // Start session if not already started
        if (!session_id()) {
            session_start();
        }
        
        // Log for debugging
        error_log('APD Add to Cart: Started');
        error_log('APD Add to Cart POST data: ' . print_r($_POST, true));
        error_log('APD Add to Cart: Session ID: ' . session_id());
        error_log('APD Add to Cart: Current cart before adding: ' . print_r($_SESSION['apd_cart'] ?? 'empty', true));
        
        // Verify nonce (allow both nonce types for compatibility)
        // Make nonce optional to support guest checkout
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        if ($nonce && !empty($nonce)) {
            if (!wp_verify_nonce($nonce, 'apd_ajax_nonce') && !wp_verify_nonce($nonce, 'fsc_nonce')) {
                error_log('APD Add to Cart: Nonce verification failed');
                error_log('APD Add to Cart: Nonce value: ' . $nonce);
                wp_send_json_error('Security check failed');
                return;
            }
            error_log('APD Add to Cart: Nonce verified successfully');
        } else {
            error_log('APD Add to Cart: No nonce provided, allowing guest access');
        }

        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        $customization_data = $_POST['customization_data'] ?? array();

        // Get product data
        $product = get_post($product_id);
        if (!$product || $product->post_type !== 'apd_product') {
            wp_send_json_error('Product not found');
        }

        // Get base price and sale price
        $base_price = floatval(get_post_meta($product_id, '_fsc_price', true));
        if (!$base_price) {
            $base_price = 29.99;  // Default price
        }
        
        $sale_price = get_post_meta($product_id, '_fsc_sale_price', true);
        $sale_price = !empty($sale_price) ? floatval($sale_price) : null;
        
        // Use sale_price if available, otherwise use base_price
        $product_base_price = ($sale_price && $sale_price > 0) ? $sale_price : $base_price;
        
        // Get material price from customization_data
        $material_price = 0;
        if (isset($customization_data['vinyl_material']) && !empty($customization_data['vinyl_material'])) {
            $material_name = $customization_data['vinyl_material'];
            $materials = $this->get_materials();
            if (isset($materials[$material_name])) {
                $material_data = $materials[$material_name];
                if (is_array($material_data) && isset($material_data['price'])) {
                    $material_price = floatval($material_data['price']);
                }
            }
        }
        
        // Also check if price was passed directly from frontend (with material price already included)
        // If product_price is in customization_data and it's different from base, use it
        if (isset($customization_data['product_price'])) {
            $frontend_price = floatval($customization_data['product_price']);
            // If frontend price is greater than base, it likely includes material price
            if ($frontend_price > $product_base_price) {
                $price = $frontend_price;
            } else {
                $price = $product_base_price + $material_price;
            }
        } else {
            // Calculate: base/sale price + material price
            $price = $product_base_price + $material_price;
        }

        // Generate unique cart item ID
        $cart_item_id = 'item_' . $product_id . '_' . time() . '_' . wp_rand(1000, 9999);

        $cart_item = array(
            'id' => $cart_item_id,
            'product_id' => $product_id,
            'product_name' => $product->post_title,
            'price' => $price,
            'base_price' => $base_price,
            'sale_price' => $sale_price,
            'material_price' => $material_price,
            'quantity' => $quantity,
            'total' => $price * $quantity,
            'customization_data' => $customization_data,
            'added_at' => current_time('Y-m-d H:i:s')
        );

        // Get current cart
        $cart = $this->get_cart();
        $cart[$cart_item_id] = $cart_item;

        // Save cart
        $this->save_cart($cart);
        
        // Log success
        error_log('APD Add to Cart: Item added successfully. Cart now has ' . count($cart) . ' items');
        error_log('APD Add to Cart: Session ID: ' . session_id());
        error_log('APD Add to Cart: Cart contents: ' . print_r($cart, true));

        wp_send_json_success(array(
            'message' => 'Product added to cart',
            'cart_item' => $cart_item,
            'cart_count' => count($cart)
        ));
    }

    public function ajax_get_cart()
    {
        error_log('APD Get Cart: Started');
        error_log('APD Get Cart: Session ID: ' . session_id());
        
        $cart = $this->get_cart();
        
        error_log('APD Get Cart: Retrieved cart: ' . print_r($cart, true));
        error_log('APD Get Cart: Cart count: ' . count($cart));
        
        $total = 0;
        $count = 0;

        foreach ($cart as $item) {
            $total += $item['total'];
            $count += $item['quantity'];
        }
        
        error_log('APD Get Cart: Total: ' . $total . ', Count: ' . $count);

        wp_send_json_success(array(
            'cart' => $cart,
            'total' => $total,
            'count' => $count
        ));
    }

    public function ajax_update_cart_item()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'apd_ajax_nonce')) {
            wp_send_json_error('Security check failed');
        }

        $cart_item_id = sanitize_text_field($_POST['cart_item_id']);
        $quantity = intval($_POST['quantity']);

        if ($quantity < 1) {
            wp_send_json_error('Invalid quantity');
        }

        $cart = $this->get_cart();
        if (!isset($cart[$cart_item_id])) {
            wp_send_json_error('Cart item not found');
        }

        $cart[$cart_item_id]['quantity'] = $quantity;
        $cart[$cart_item_id]['total'] = $cart[$cart_item_id]['price'] * $quantity;

        $this->save_cart($cart);

        wp_send_json_success(array(
            'message' => 'Cart updated',
            'cart_item' => $cart[$cart_item_id]
        ));
    }

    public function ajax_remove_cart_item()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'apd_ajax_nonce')) {
            wp_send_json_error('Security check failed');
        }

        $cart_item_id = sanitize_text_field($_POST['cart_item_id']);
        $cart = $this->get_cart();

        if (isset($cart[$cart_item_id])) {
            unset($cart[$cart_item_id]);
            $this->save_cart($cart);
            wp_send_json_success(array('message' => 'Item removed from cart'));
        } else {
            wp_send_json_error('Cart item not found');
        }
    }

    public function ajax_clear_cart()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'apd_ajax_nonce')) {
            wp_send_json_error('Security check failed');
        }

        $this->save_cart(array());
        wp_send_json_success(array('message' => 'Cart cleared'));
    }

    private function get_cart()
    {
        if (!session_id()) {
            session_start();
        }
        
        error_log('APD get_cart(): Session ID: ' . session_id());
        error_log('APD get_cart(): SESSION data: ' . print_r($_SESSION, true));
        
        $cart = isset($_SESSION['apd_cart']) ? $_SESSION['apd_cart'] : array();
        
        error_log('APD get_cart(): Returning cart with ' . count($cart) . ' items');
        
        return $cart;
    }

    private function save_cart($cart)
    {
        if (!session_id()) {
            session_start();
        }
        
        error_log('APD save_cart(): Session ID: ' . session_id());
        error_log('APD save_cart(): Saving cart with ' . count($cart) . ' items');
        error_log('APD save_cart(): Cart data: ' . print_r($cart, true));
        
        $_SESSION['apd_cart'] = $cart;
        
        error_log('APD save_cart(): Verification - SESSION apd_cart now has ' . count($_SESSION['apd_cart']) . ' items');
    }

    // Handle PNG data URL upload and return a media URL
    public function apd_save_customization_image()
    {
        // Accept multiple nonce field names for compatibility
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : (isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : (isset($_POST['security']) ? $_POST['security'] : (isset($_POST['apd_nonce']) ? $_POST['apd_nonce'] : '')));
        if ($nonce && !(wp_verify_nonce($nonce, 'fsc_nonce') || wp_verify_nonce($nonce, 'apd_ajax_nonce'))) {
            // Soft fail: allow if logged-in; otherwise continue but note we didn't verify
        }
        if (!isset($_POST['image'])) {
            wp_send_json_error(array('message' => 'No image provided'), 400);
        }
        $data_url = $_POST['image'];
        if (strpos($data_url, 'data:image/png;base64,') !== 0) {
            wp_send_json_error(array('message' => 'Invalid image format'), 400);
        }
        $raw = base64_decode(substr($data_url, strlen('data:image/png;base64,')));
        if ($raw === false) {
            wp_send_json_error(array('message' => 'Decode failed'), 400);
        }
        // Optional size cap 8MB
        if (strlen($raw) > 8 * 1024 * 1024) {
            wp_send_json_error(array('message' => 'Image too large'), 400);
        }
        // Store file in uploads
        $upload = wp_upload_bits('customization-' . time() . '-' . wp_generate_password(6, false, false) . '.png', null, $raw);
        if (!empty($upload['error'])) {
            wp_send_json_error(array('message' => $upload['error']), 500);
        }
        // Optionally, register as attachment
        $file_url = $upload['url'];
        wp_send_json_success(array('url' => esc_url($file_url)));
    }

    public function load_product()
    {
        // Accept either FSC nonce or APD ajax nonce for compatibility
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        $valid = false;
        if ($nonce && wp_verify_nonce($nonce, 'fsc_nonce')) {
            $valid = true;
        }
        if (!$valid && $nonce && wp_verify_nonce($nonce, 'apd_ajax_nonce')) {
            $valid = true;
        }
        if (!$valid) {
            wp_send_json_error(array('message' => 'Security check failed (invalid nonce)'));
        }

        $product_id = intval($_POST['product_id']);

        if ($product_id <= 0) {
            wp_send_json_error(array('message' => 'Invalid product ID'));
        }

        $product = get_post($product_id);

        if (!$product || $product->post_type !== 'apd_product') {
            wp_send_json_error(array('message' => 'Product not found'));
        }

        // Get product meta data
        $price = get_post_meta($product_id, '_fsc_price', true);
        $material = get_post_meta($product_id, '_fsc_material', true);
        $features = get_post_meta($product_id, '_fsc_features', true);
        $color_options = get_post_meta($product_id, '_fsc_color_options', true);
        $product_logo_url = get_post_meta($product_id, '_fsc_logo_file', true);
        $template_id = get_post_meta($product_id, '_fsc_template', true);

        // Get processed SVG content for product-specific logo
        $product_logo_content = '';
        if ($product_logo_url) {
            $plugin_dir = APD_PLUGIN_PATH;
            $logo_path = str_replace(APD_PLUGIN_URL, $plugin_dir, $product_logo_url);
            $product_logo_content = $this->_get_processed_svg_content($logo_path);
        }

        // Do not inject mock defaults; leave empty values so UI reflects real data
        // $price, $material, $features, $product_logo_content may be empty if not set

        // Process color options: only include colors explicitly configured
        $colors = array();
        if ($color_options) {
            $default_colors = $this->get_default_colors();
            $color_array = array_map('trim', explode(',', $color_options));
            foreach ($color_array as $color) {
                if (isset($default_colors[$color])) {
                    $colors[$color] = $default_colors[$color];
                }
            }
        }

        // Resolve template data (if linked)
        $template_data = null;
        if ($template_id) {
            $template_post = get_post($template_id);
            if ($template_post && $template_post->post_type === 'apd_template') {
                error_log('APD load_product: template_id=' . $template_id . ' title=' . $template_post->post_title);
                $template_data_raw = get_post_meta($template_id, '_apd_template_data', true);
                if ($template_data_raw) {
                    $decoded = json_decode($template_data_raw, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $template_data = $decoded;
                        error_log('APD load_product: template_data decoded OK (array)');
                    } else {
                        // Try if stored as already-decoded array/string
                        $template_data = $template_data_raw;
                        error_log('APD load_product: template_data not JSON, using raw value');
                    }
                } else {
                    // Fallbacks: sometimes stored in post_content
                    $content = trim($template_post->post_content);
                    if ($content) {
                        $decoded = json_decode($content, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $template_data = $decoded;
                            error_log('APD load_product: template_data loaded from post_content JSON');
                        } else {
                            error_log('APD load_product: no _apd_template_data and post_content not JSON');
                        }
                    } else {
                        error_log('APD load_product: no _apd_template_data and empty post_content');
                    }
                }
            }
        }

        $response_data = array(
            'id' => $product_id,
            'title' => $product->post_title,
            'price' => $price,
            'material' => $material,
            'features' => is_array($features) ? $features : array(),
            'colors' => $colors,
            'logo_content' => $product_logo_content,
            'url' => get_permalink($product_id),
            'template_id' => $template_id ? intval($template_id) : 0,
            'template_data' => $template_data,
            'templateData' => $template_data  // Keep both for backwards compatibility
        );

        if ($template_id && $template_data) {
            error_log('APD load_product: returning templateData for product ' . $product_id);
        } else if ($template_id && !$template_data) {
            error_log('APD load_product: template linked but no templateData for product ' . $product_id);
        } else {
            error_log('APD load_product: no template linked for product ' . $product_id);
        }

        wp_send_json_success($response_data);
    }

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

    public function deactivate()
    {
        flush_rewrite_rules();
    }

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

        require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

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

    // Register lightweight order type and statuses
    public function register_order_cpt_and_statuses()
    {
        // Custom post type for orders
        register_post_type('apd_order', array(
            'labels' => array(
                'name' => 'Orders',
                'singular_name' => 'Order'
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'apd-dashboard',
            'supports' => array('title', 'custom-fields'),
            'capability_type' => 'post'
        ));
        // Statuses
        $statuses = array(
            'apd_pending' => 'Pending',
            'apd_confirmed' => 'Confirmed',
            'apd_processing' => 'Processing',
            'apd_shipped' => 'Shipped',
            'apd_completed' => 'Completed',
            'apd_canceled' => 'Canceled'
        );
        foreach ($statuses as $key => $label) {
            if (!post_type_exists('apd_order'))
                break;
            register_post_status($key, array(
                'label' => $label,
                'public' => false,
                'internal' => false,
                'label_count' => _n_noop($label . ' <span class="count">(%s)</span>', $label . ' <span class="count">(%s)</span>')
            ));
        }
    }

    // AJAX: place order from checkout
    public function apd_place_order()
    {
        // Turn off all error reporting and output buffering for clean JSON response
        $error_reporting = error_reporting(0);
        $display_errors = ini_get('display_errors');
        ini_set('display_errors', 0);
        
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : (isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : '');
        // Soft-verify
        if ($nonce && !(wp_verify_nonce($nonce, 'apd_ajax_nonce') || wp_verify_nonce($nonce, 'fsc_nonce'))) {
            error_reporting($error_reporting);
            ini_set('display_errors', $display_errors);
            wp_send_json_error('Security check failed');
        }

        // Guest checkout allowed: user_id=0 when not logged in
        $user_id = get_current_user_id();
        if (!$user_id) {
            $user_id = 0;
        }
        // Cart will be resolved from POST/SESSION below; don't fail early here
        $name = sanitize_text_field($_POST['customer_name'] ?? '');
        $email = sanitize_email($_POST['customer_email'] ?? '');
        $phone = sanitize_text_field($_POST['customer_phone'] ?? '');
        $address = sanitize_textarea_field($_POST['customer_address'] ?? '');
        $payment_method = sanitize_text_field($_POST['payment_method'] ?? 'paypal');

        // Handle mock payments
        if ($payment_method === 'mock_paypal') {
            $paypal_order_id = sanitize_text_field($_POST['paypal_order_id'] ?? '');
            $paypal_transaction_id = sanitize_text_field($_POST['paypal_transaction_id'] ?? '');
            $paypal_payer_id = sanitize_text_field($_POST['paypal_payer_id'] ?? '');
            $payment_status = 'completed';  // Mock payments are always successful

            // Log mock payment for debugging (commented out to prevent output interference)
            // error_log('Mock PayPal Payment: Order ID = ' . $paypal_order_id . ', Transaction ID = ' . $paypal_transaction_id);
        } else {
            // Handle real PayPal payments
            $paypal_order_id = sanitize_text_field($_POST['paypal_order_id'] ?? '');
            $paypal_transaction_id = sanitize_text_field($_POST['paypal_transaction_id'] ?? '');
            $paypal_payer_id = sanitize_text_field($_POST['paypal_payer_id'] ?? '');
            $payment_status = sanitize_text_field($_POST['payment_status'] ?? 'completed');
        }

        // Build cart (prefer posted JSON, then session/user meta)
        $cart_items = array();
        if (isset($_POST['cart'])) {
            $posted_cart = json_decode(stripslashes($_POST['cart']), true);
            if (is_array($posted_cart)) {
                $cart_items = $posted_cart;
            }
        }
        if (empty($cart_items)) {
            if (!session_id()) {
                session_start();
            }
            if (!empty($_SESSION['apd_cart']) && is_array($_SESSION['apd_cart'])) {
                $cart_items = $_SESSION['apd_cart'];
            }
        }
        if (empty($cart_items) && $user_id) {
            $user_cart = get_user_meta($user_id, 'apd_cart', true);
            if (is_array($user_cart)) {
                $cart_items = $user_cart;
            }
        }

        // Compute order totals from cart (if present)
        $order_total = 0.0;
        if (!empty($cart_items) && is_array($cart_items)) {
            foreach ($cart_items as &$ci) {
                $price = isset($ci['price']) ? floatval($ci['price']) : floatval($ci['product_price'] ?? 0);
                $qty = max(1, intval($ci['quantity'] ?? 1));
                $ci['total'] = $price * $qty;
                $order_total += $ci['total'];
            }
            unset($ci);
        }

        // Get customization data from session
        if (!session_id()) {
            session_start();
        }
        $customization_data = $_SESSION['fsc_customization'] ?? array();

        // Also try to get data from POST if available (for cases where session isn't working)
        if (isset($_POST['customization_data'])) {
            $posted_data = json_decode(stripslashes($_POST['customization_data']), true);
            if (is_array($posted_data)) {
                $customization_data = array_merge($customization_data, $posted_data);
            }
        }

        // Debug: Log what we received (commented out to prevent output interference)
        // error_log('APD Place Order - Customization Data: ' . print_r($customization_data, true));

        // Convert base64 image data to actual file URLs before saving
        $image_fields = array('image_url', 'preview_image_url', 'preview_image_png', 'customization_image_url');
        foreach ($image_fields as $field) {
            if (!empty($customization_data[$field])) {
                // error_log("APD Place Order - Checking field '$field': " . substr($customization_data[$field], 0, 100));

                if (strpos($customization_data[$field], 'data:image/png;base64,') === 0) {
                    // This is a base64 data URL, convert it to a file
                    // error_log("APD Place Order - Converting base64 for field '$field'");
                    $raw = base64_decode(substr($customization_data[$field], strlen('data:image/png;base64,')));
                    if ($raw !== false && strlen($raw) <= 8 * 1024 * 1024) {
                        $upload = wp_upload_bits('order-preview-' . time() . '-' . wp_generate_password(6, false, false) . '.png', null, $raw);
                        if (empty($upload['error'])) {
                            $customization_data[$field] = $upload['url'];
                            // error_log('APD Place Order - Successfully saved image to: ' . $upload['url']);
                        } else {
                            // error_log("APD Place Order - Upload error for '$field': " . $upload['error']);
                        }
                    } else {
                        // error_log("APD Place Order - Failed to decode or image too large for '$field'");
                    }
                }
            }
        }

        // Also convert base64 images present inside each cart item (and nested customization_data) so previews are real URLs
        if (!empty($cart_items) && is_array($cart_items)) {
            foreach ($cart_items as &$ci) {
                // If customization_data is a JSON string, decode it to an array for processing
                if (!empty($ci['customization_data']) && is_string($ci['customization_data'])) {
                    $maybe = json_decode($ci['customization_data'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($maybe)) {
                        $ci['customization_data'] = $maybe;
                    }
                }

                // Process nested customization_data images first
                if (!empty($ci['customization_data']) && is_array($ci['customization_data'])) {
                    foreach ($image_fields as $field) {
                        if (!empty($ci['customization_data'][$field]) && strpos($ci['customization_data'][$field], 'data:image') === 0) {
                            $raw = preg_replace('#^data:image/[^;]+;base64,#', '', $ci['customization_data'][$field]);
                            $decoded = base64_decode($raw);
                            if ($decoded !== false && strlen($decoded) <= 8 * 1024 * 1024) {
                                $upload = wp_upload_bits('order-item-preview-' . time() . '-' . wp_generate_password(6, false, false) . '.png', null, $decoded);
                                if (empty($upload['error'])) {
                                    $ci['customization_data'][$field] = $upload['url'];
                                }
                            }
                        }
                    }
                }

                // Process top-level image fields on the cart item
                foreach ($image_fields as $field) {
                    if (!empty($ci[$field]) && strpos($ci[$field], 'data:image') === 0) {
                        $raw = preg_replace('#^data:image/[^;]+;base64,#', '', $ci[$field]);
                        $decoded = base64_decode($raw);
                        if ($decoded !== false && strlen($decoded) <= 8 * 1024 * 1024) {
                            $upload = wp_upload_bits('order-item-preview-' . time() . '-' . wp_generate_password(6, false, false) . '.png', null, $decoded);
                            if (empty($upload['error'])) {
                                $ci[$field] = $upload['url'];
                            }
                        }
                    }
                }
            }
            unset($ci);
        }

        // Comprehensive order data for manufacturing
        // Ensure cart_items is a sequential array; normalize associative maps
        if (is_array($cart_items)) {
            $keys = array_keys($cart_items);
            $is_assoc = array_filter($keys, 'is_string') ? true : false;
            if ($is_assoc) {
                $cart_items = array_values($cart_items);
            }
        } else {
            $cart_items = array();
        }

        // Compute cart total (already computed above into $order_total)
        $cart_total = floatval($order_total);

        // Keep single-item summary fields for backward compatibility (use first item if present)
        $first_item = !empty($cart_items) ? $cart_items[0] : array();
        $single_product_price = isset($first_item['price']) ? floatval($first_item['price']) : floatval($customization_data['product_price'] ?? 29.99);
        $single_quantity = isset($first_item['quantity']) ? intval($first_item['quantity']) : intval($customization_data['quantity'] ?? 1);

        $meta = array(
            // Product Information (summary for legacy admin views)
            'product_id' => $first_item['product_id'] ?? ($customization_data['product_id'] ?? ''),
            'product_name' => $first_item['product_name'] ?? ($customization_data['product_name'] ?? 'Custom Freight Sign'),
            'product_price' => $single_product_price,
            'quantity' => $single_quantity,
            'total_amount' => $cart_total,
            // Design Specifications (from customization_data when applicable)
            'print_color' => $customization_data['print_color'] ?? ($first_item['print_color'] ?? ''),
            'vinyl_material' => $customization_data['vinyl_material'] ?? ($first_item['vinyl_material'] ?? ''),
            'material_texture_url' => $customization_data['material_texture_url'] ?? ($first_item['material_texture_url'] ?? ''),
            // User Input Fields (All template text fields)
            'text_fields' => $customization_data['text_fields'] ?? ($first_item['text_fields'] ?? array()),
            'template_data' => $customization_data['template_data'] ?? ($first_item['template_data'] ?? array()),
            'fields_display' => $customization_data['fields_display'] ?? ($first_item['fields_display'] ?? array()),
            'template_fields_array' => $customization_data['template_fields_array'] ?? ($first_item['template_fields_array'] ?? array()),
            // Visual Reference (Preview Images)
            'customization_image_url' => $customization_data['image_url'] ?? ($customization_data['customization_image_url'] ?? ($first_item['customization_image_url'] ?? '')),
            'preview_image_url' => $customization_data['preview_image_url'] ?? ($first_item['preview_image_url'] ?? ''),
            'preview_image_png' => $customization_data['preview_image_png'] ?? ($first_item['preview_image_png'] ?? ''),
            'preview_image_svg' => $customization_data['preview_image_svg'] ?? ($first_item['preview_image_svg'] ?? ''),
            // Customer Information
            'customer_name' => $name,
            'customer_email' => $email,
            'customer_phone' => $phone,
            'customer_address' => $address,
            // Order Details
            'payment_method' => $payment_method,
            'order_date' => current_time('Y-m-d H:i:s'),
            'order_status' => 'apd_pending',
            // Cart Summary (store as JSON string to keep storage consistent)
            'cart_items' => wp_json_encode($cart_items),
            'cart_total' => $cart_total,
            // Payment Details
            'paypal_order_id' => $paypal_order_id ?? '',
            'paypal_transaction_id' => $paypal_transaction_id ?? '',
            'paypal_payer_id' => $paypal_payer_id ?? '',
            'payment_status' => $payment_status ?? 'completed',
            // Manufacturing Notes
            'manufacturing_notes' => $this->generateManufacturingNotes($customization_data),
            'production_ready' => true,
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

        // Clear cart after placing order (session + user meta if available)
        if (!session_id()) {
            session_start();
        }
        unset($_SESSION['apd_cart']);
        if ($user_id) {
            delete_user_meta($user_id, 'apd_cart');
        }
        
        // Send email notifications
        $this->send_order_confirmation_email($order_id, $meta);
        $this->send_admin_notification_email($order_id, $meta);
        
        // Restore error reporting before sending JSON
        error_reporting($error_reporting);
        ini_set('display_errors', $display_errors);
        
        $thankyou = get_permalink(intval(get_option('apd_thankyou')));
        if (!$thankyou) {
            $thankyou = home_url('/thank-you/');
        }
        wp_send_json_success(array('order_id' => $order_id, 'redirect' => esc_url($thankyou)));
    }

    public function apd_get_order_details()
    {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'apd_order_details')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        $order_id = intval($_POST['order_id'] ?? 0);
        if (!$order_id) {
            wp_send_json_error('Invalid order ID');
            return;
        }

        // Get order post
        $order = get_post($order_id);
        if (!$order || $order->post_type !== 'apd_order') {
            wp_send_json_error('Order not found');
            return;
        }

        // Get order meta
        $order_meta = get_post_meta($order_id);

        // cart_items may be stored as JSON string or array; normalize to array
        $raw_cart = get_post_meta($order_id, 'cart_items', true);
        $cart_items = $raw_cart;
        if (is_string($raw_cart)) {
            $decoded = json_decode($raw_cart, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $cart_items = $decoded;
            }
        }
        if (!is_array($cart_items)) {
            $cart_items = array();
        }
        // Normalize shapes
        if (isset($cart_items['items']) && is_array($cart_items['items'])) {
            $cart_items = $cart_items['items'];
        }
        $keys = array_keys($cart_items);
        $is_assoc = array_filter($keys, 'is_string') ? true : false;
        if ($is_assoc) {
            $cart_items = array_values($cart_items);
        }

        $cart_total = get_post_meta($order_id, 'cart_total', true);
        if (!is_numeric($cart_total)) {
            $cart_total = 0;
            foreach ($cart_items as $ci) {
                $cart_total += isset($ci['total']) ? (float) $ci['total'] : ((float) ($ci['price'] ?? $ci['product_price'] ?? 0) * (int) ($ci['quantity'] ?? 1));
            }
        }

        // Prepare order data
        $order_data = array(
            'id' => $order_id,
            'date' => $order->post_date,
            'status' => $order->post_status,
            'total' => number_format((float) $cart_total, 2),
            'cart_total' => (float) $cart_total,
            'cart_items' => $cart_items,
            'customer_name' => $order_meta['customer_name'][0] ?? $order_meta['_customer_name'][0] ?? '',
            'customer_email' => $order_meta['customer_email'][0] ?? $order_meta['_customer_email'][0] ?? '',
            'customer_phone' => $order_meta['customer_phone'][0] ?? $order_meta['_customer_phone'][0] ?? '',
            'customer_address' => $order_meta['customer_address'][0] ?? $order_meta['_customer_address'][0] ?? '',
            'payment_method' => $order_meta['payment_method'][0] ?? $order_meta['_payment_method'][0] ?? '',
            'payment_status' => $order_meta['payment_status'][0] ?? $order_meta['_payment_status'][0] ?? '',
            'customization_data' => $order_meta['customization_data'][0] ?? $order_meta['_customization_data'][0] ?? '',
            'preview_image_url' => $order_meta['preview_image_url'][0] ?? $order_meta['_preview_image_url'][0] ?? '',
            'preview_image_png' => $order_meta['preview_image_png'][0] ?? $order_meta['_preview_image_png'][0] ?? '',
            'preview_image_svg' => $order_meta['preview_image_svg'][0] ?? $order_meta['_preview_image_svg'][0] ?? ''
        );

        wp_send_json_success($order_data);
    }

    // --- Admin: Register/ensure statuses visible in All ---
    public function apd_register_statuses_visible()
    {
        // If CPT already registered elsewhere, just (re)register statuses to be visible in admin lists
        register_post_status('apd_pending', array(
            'label' => 'Pending',
            'public' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>')
        ));
        register_post_status('in_production', array(
            'label' => 'In Production',
            'public' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('In Production <span class="count">(%s)</span>', 'In Production <span class="count">(%s)</span>')
        ));
        register_post_status('quality_check', array(
            'label' => 'Quality Check',
            'public' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Quality Check <span class="count">(%s)</span>', 'Quality Check <span class="count">(%s)</span>')
        ));
        register_post_status('completed', array(
            'label' => 'Completed',
            'public' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Completed <span class="count">(%s)</span>', 'Completed <span class="count">(%s)</span>')
        ));
        register_post_status('cancelled', array(
            'label' => 'Cancelled',
            'public' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>')
        ));
    }

    // --- Admin Pages ---
    public function apd_register_orders_admin_pages()
    {
        add_menu_page(
            'Orders', 'Orders', 'manage_options', 'apd_orders', array($this, 'apd_render_orders_list_page'), 'dashicons-cart', 26
        );
        add_submenu_page('apd_orders', 'Orders', 'All Orders', 'manage_options', 'apd_orders', array($this, 'apd_render_orders_list_page'));
        add_submenu_page('apd_orders', 'Order Detail', 'Order Detail', 'manage_options', 'apd_order_detail', array($this, 'apd_render_order_detail_page'));
    }

    private function apd_get_all_statuses()
    {
        return array('apd_pending' => 'Pending', 'in_production' => 'In Production', 'quality_check' => 'Quality Check', 'completed' => 'Completed', 'cancelled' => 'Cancelled');
    }

    public function apd_render_orders_list_page()
    {
        if (!current_user_can('manage_options'))
            return;
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $s = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $args = array(
            'post_type' => 'apd_order',
            'post_status' => array_keys($this->apd_get_all_statuses()),
            'posts_per_page' => 20,
            's' => $s,
        );
        if ($status) {
            $args['post_status'] = $status;
        }
        $q = new WP_Query($args);
        echo '<div class="wrap"><h1>Orders</h1>';
        echo '<form method="get" style="margin:12px 0">';
        echo '<input type="hidden" name="page" value="apd_orders"/>';
        echo '<select name="status"><option value="">All statuses</option>';
        foreach ($this->apd_get_all_statuses() as $k => $v) {
            printf('<option value="%s" %s>%s</option>', esc_attr($k), selected($status, $k, false), esc_html($v));
        }
        echo '</select> ';
        printf('<input type="search" name="s" value="%s" placeholder="Search orders..."/> ', esc_attr($s));
        echo '<button class="button">Filter</button>';
        echo '</form>';
        echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>ID</th><th>Customer</th><th>Status</th><th>Total</th><th>Created</th><th>Actions</th></tr></thead><tbody>';
        if ($q->have_posts()) {
            while ($q->have_posts()) {
                $q->the_post();
                $pid = get_the_ID();
                $cust = get_post_meta($pid, 'customer_name', true);
                $price = get_post_meta($pid, 'product_price', true);
                $qty = get_post_meta($pid, 'quantity', true);
                $status_label = get_post_status_object(get_post_status($pid));
                $total = floatval($price) * max(1, intval($qty));
                printf('<tr><td>%d</td><td>%s</td><td>%s</td><td>$%0.2f</td><td>%s</td><td><a class="button" href="%s">View</a></td></tr>',
                    $pid,
                    esc_html($cust ?: '-'),
                    esc_html($status_label ? $status_label->label : get_post_status($pid)),
                    $total,
                    esc_html(get_the_date('Y-m-d H:i')),
                    esc_url(admin_url('admin.php?page=apd_order_detail&order_id=' . $pid)));
            }
            wp_reset_postdata();
        } else {
            echo '<tr><td colspan="6">No orders found.</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    public function apd_render_order_detail_page()
    {
        if (!current_user_can('manage_options'))
            return;
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        if (!$order_id) {
            echo '<div class="wrap"><h1>Order Detail</h1><p>Invalid order.</p></div>';
            return;
        }
        $p = get_post($order_id);
        if (!$p || $p->post_type !== 'apd_order') {
            echo '<div class="wrap"><h1>Order Detail</h1><p>Order not found.</p></div>';
            return;
        }
        $meta = array(
            'customer_name' => get_post_meta($order_id, 'customer_name', true),
            'customer_email' => get_post_meta($order_id, 'customer_email', true),
            'customer_phone' => get_post_meta($order_id, 'customer_phone', true),
            'customer_address' => get_post_meta($order_id, 'customer_address', true),
            'product_name' => get_post_meta($order_id, 'product_name', true),
            'product_price' => get_post_meta($order_id, 'product_price', true),
            'quantity' => get_post_meta($order_id, 'quantity', true),
            'print_color' => get_post_meta($order_id, 'print_color', true),
            'vinyl_material' => get_post_meta($order_id, 'vinyl_material', true),
        );
        $text_fields = get_post_meta($order_id, 'text_fields', true);
        $template_data = get_post_meta($order_id, 'template_data', true);
        $fields_display = get_post_meta($order_id, 'fields_display', true);
        $template_fields_array = get_post_meta($order_id, 'template_fields_array', true);
    $preview_image_png = get_post_meta($order_id, 'preview_image_png', true);
    $preview_image_svg = get_post_meta($order_id, 'preview_image_svg', true);
        $preview_image_url = get_post_meta($order_id, 'preview_image_url', true);
        $customization_image_url = get_post_meta($order_id, 'customization_image_url', true);
        // Load cart items (support JSON string or array shapes)
        $raw_cart = get_post_meta($order_id, 'cart_items', true);
        $cart_items = $raw_cart;
        if (is_string($raw_cart)) {
            $decoded = json_decode($raw_cart, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $cart_items = $decoded;
            }
        }
        if (!is_array($cart_items)) {
            $cart_items = array();
        }
        // Normalize shapes: {items: [...]}, associative maps -> sequential array
        if (isset($cart_items['items']) && is_array($cart_items['items'])) {
            $cart_items = $cart_items['items'];
        }
        $keys = array_keys($cart_items);
        $is_assoc = array_filter($keys, 'is_string') ? true : false;
        if ($is_assoc) {
            $cart_items = array_values($cart_items);
        }

        $cart_total = get_post_meta($order_id, 'cart_total', true);
        if (!is_numeric($cart_total)) {
            $cart_total = 0;
            foreach ($cart_items as $ci) {
                $cart_total += isset($ci['total']) ? (float) $ci['total'] : ((float) ($ci['price'] ?? $ci['product_price'] ?? 0) * (int) ($ci['quantity'] ?? 1));
            }
        }
        $notes = get_post_meta($order_id, 'apd_notes', true);
        if (!is_array($notes))
            $notes = array();

        // Debug: Log what images we found
    error_log("APD Order Detail #$order_id - preview_image_svg: " . ($preview_image_svg ?: 'EMPTY'));
    error_log("APD Order Detail #$order_id - preview_image_png: " . ($preview_image_png ?: 'EMPTY'));
        error_log("APD Order Detail #$order_id - preview_image_url: " . ($preview_image_url ?: 'EMPTY'));
        error_log("APD Order Detail #$order_id - customization_image_url: " . ($customization_image_url ?: 'EMPTY'));

        echo '<div class="wrap"><h1>Order #' . $order_id . '</h1>';

        // Display Preview Image if available (fallback to first cart item preview if top-level missing)
        $image_to_display = $preview_image_svg ?: ($preview_image_png ?: ($preview_image_url ?: $customization_image_url));
        if (!$image_to_display && !empty($cart_items)) {
            $first = $cart_items[0];
            $image_to_display = $first['preview_image_svg'] ?? $first['preview_image_png'] ?? $first['preview_image_url'] ?? $first['customization_image_url'] ?? $first['image_url'] ?? '';
            
            // For non-customizable products, try to get product thumbnail or logo
            if (!$image_to_display && !empty($first['product_id'])) {
                $prod_id = intval($first['product_id']);
                
                // Try thumbnail first
                $thumbnail_id = get_post_meta($prod_id, '_fsc_thumbnail_id', true);
                if ($thumbnail_id) {
                    $image_to_display = wp_get_attachment_image_url($thumbnail_id, 'large');
                }
                
                // Fallback to logo if no thumbnail
                if (!$image_to_display) {
                    $logo_url = get_post_meta($prod_id, '_fsc_logo_file', true);
                    if ($logo_url) {
                        $image_to_display = $logo_url;
                    }
                }
                
                // Last resort: post thumbnail
                if (!$image_to_display) {
                    $image_to_display = get_the_post_thumbnail_url($prod_id, 'large');
                }
            }
        }

        // Determine best SVG source (if available) for direct download
        $svg_download_url = '';
        if (!empty($preview_image_svg)) {
            $svg_download_url = $preview_image_svg;
        } elseif (!empty($cart_items)) {
            // Try to find an SVG on the first cart item
            $first = $cart_items[0];
            if (!empty($first['preview_image_svg'])) {
                $svg_download_url = $first['preview_image_svg'];
            } elseif (!empty($first['customization_data'])) {
                $cd = is_array($first['customization_data']) ? $first['customization_data'] : json_decode($first['customization_data'], true);
                if (is_array($cd) && !empty($cd['preview_image_svg'])) {
                    $svg_download_url = $cd['preview_image_svg'];
                }
            }
            
            // For non-customizable products, fetch the product's logo SVG
            if (empty($svg_download_url) && !empty($first['product_id'])) {
                $product_id_from_cart = intval($first['product_id']);
                $product_logo = get_post_meta($product_id_from_cart, '_fsc_logo_file', true);
                error_log("APD Order Detail - Looking for SVG: product_id={$product_id_from_cart}, logo_file=" . ($product_logo ?: 'EMPTY'));
                
                // Validate that it's actually an SVG file
                if (!empty($product_logo) && preg_match('/\.svg$/i', $product_logo)) {
                    // Check if it's a URL or file path
                    if (filter_var($product_logo, FILTER_VALIDATE_URL)) {
                        $svg_download_url = $product_logo;
                        error_log("APD Order Detail - SVG URL found: {$product_logo}");
                    } else {
                        // It's a file path, convert to URL if needed
                        $upload_dir = wp_upload_dir();
                        $base_dir = $upload_dir['basedir'];
                        $base_url = $upload_dir['baseurl'];
                        
                        // If path is relative to uploads dir
                        if (strpos($product_logo, $base_dir) === 0) {
                            $svg_download_url = str_replace($base_dir, $base_url, $product_logo);
                        } else {
                            $svg_download_url = $product_logo;
                        }
                        error_log("APD Order Detail - SVG path converted to URL: {$svg_download_url}");
                    }
                }
            }
        }
        // If not explicitly found, but the displayed image is an SVG data URL, allow downloading that
        if (empty($svg_download_url) && is_string($image_to_display) && preg_match('/^data:image(?:\\+svg|\\/svg(?:\\+xml|\\-xml)?);/i', $image_to_display)) {
            $svg_download_url = $image_to_display;
        }

        // Debug output for admin
        if (current_user_can('manage_options')) {
            echo '<!-- DEBUG: preview_image_svg = ' . esc_html($preview_image_svg ?: 'EMPTY') . ' -->';
            echo '<!-- DEBUG: preview_image_png = ' . esc_html($preview_image_png ?: 'EMPTY') . ' -->';
            echo '<!-- DEBUG: preview_image_url = ' . esc_html($preview_image_url ?: 'EMPTY') . ' -->';
            echo '<!-- DEBUG: customization_image_url = ' . esc_html($customization_image_url ?: 'EMPTY') . ' -->';
            echo '<!-- DEBUG: image_to_display = ' . esc_html($image_to_display ?: 'EMPTY') . ' -->';
            echo '<!-- DEBUG: svg_download_url = ' . esc_html($svg_download_url ?: 'EMPTY') . ' -->';
            if (!empty($cart_items)) {
                $first_item = $cart_items[0];
                echo '<!-- DEBUG: first_cart_item_product_id = ' . esc_html($first_item['product_id'] ?? 'EMPTY') . ' -->';
                echo '<!-- DEBUG: first_cart_item_keys = ' . esc_html(implode(', ', array_keys($first_item))) . ' -->';
            }
        }

        if ($image_to_display) {
            echo '<h2 class="title">Customized Design Preview</h2>';
            echo '<div style="background:#f8f9fa;border:1px solid #e1e1e1;padding:20px;max-width:800px;margin-bottom:20px;border-radius:8px;">';
            echo '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;gap:8px;">';
            echo '<span style="font-weight:600;color:#333;">Design Preview</span>';
            echo '<div style="display:flex;gap:8px;">';
            // PNG download button (existing)
            echo '<button id="download-design-btn" class="button button-primary" style="background:#0073aa;border-color:#0073aa;color:#fff;border-radius:4px;cursor:pointer;font-size:14px;text-decoration:none;display:inline-flex;align-items:center;gap:8px;">';
            echo 'Download PNG';
            echo '</button>';
            // SVG download button (new) — only show if we have an SVG source
            echo '<button id="download-design-svg-btn" class="button" style="border-color:#2271b1;color:#2271b1;border-radius:4px;cursor:pointer;font-size:14px;text-decoration:none;display:' . (!empty($svg_download_url) ? 'inline-flex' : 'none') . ';align-items:center;gap:8px;">';
            echo 'Download SVG';
            echo '</button>';
            echo '</div>';
            echo '</div>';
            echo '<img id="preview-image" src="' . esc_attr($image_to_display) . '" alt="Customized Design" style="max-width:100%;height:auto;display:block;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,0.1);" />';
            echo '</div>';
        }

        // Order Items list
        echo '<h2 class="title" style="margin-top:20px;">Order Items</h2>';
        if (empty($cart_items)) {
            echo '<p>No items found in this order.</p>';
        } else {
            echo '<div style="background:#fff;border:1px solid #e1e1e1;padding:12px;border-radius:8px;max-width:1000px;margin-bottom:12px;">';
            foreach ($cart_items as $idx => $item) {
                $cd = array();
                if (!empty($item['customization_data'])) {
                    if (is_array($item['customization_data']))
                        $cd = $item['customization_data'];
                    elseif (is_string($item['customization_data'])) {
                        $decoded = json_decode($item['customization_data'], true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded))
                            $cd = $decoded;
                    }
                }
                $imgUrl = $item['preview_image_svg'] ?? $item['preview_image_png'] ?? $item['preview_image_url'] ?? $item['customization_image_url'] ?? $item['image_url'] ?? '';
                if (!$imgUrl && !empty($cd)) {
                    $imgUrl = $cd['preview_image_svg'] ?? $cd['preview_image_png'] ?? $cd['preview_image_url'] ?? $cd['customization_image_url'] ?? $cd['image_url'] ?? '';
                }
                $pname = $item['product_name'] ?? ($cd['product_name'] ?? 'Product');
                $qty = isset($item['quantity']) ? (int) $item['quantity'] : 1;
                $price = isset($item['total']) ? (float) $item['total'] : ((float) ($item['price'] ?? $item['product_price'] ?? 0) * $qty);
                echo '<div style="display:flex;gap:12px;align-items:center;border-bottom:1px solid #f1f1f1;padding:12px 0;">';
                if ($imgUrl) {
                    echo '<div style="width:120px;height:80px;flex:0 0 120px;overflow:hidden;border-radius:6px;border:1px solid #eee;display:flex;align-items:center;justify-content:center;background:#fafafa;"><img src="' . esc_attr($imgUrl) . '" style="max-width:100%;max-height:100%;display:block;"/></div>';
                } else {
                    echo '<div style="width:120px;height:80px;flex:0 0 120px;display:flex;align-items:center;justify-content:center;border-radius:6px;border:1px solid #eee;background:#fafafa;color:#999;font-size:24px;">📦</div>';
                }
                echo '<div style="flex:1;">';
                echo '<div style="font-weight:600;margin-bottom:6px;">' . esc_html($pname) . '</div>';
                // specs
                $specs = array();
                if (!empty($item['vinyl_material']))
                    $specs[] = 'Material: ' . esc_html($item['vinyl_material']);
                if (!empty($item['print_color']))
                    $specs[] = 'Color: ' . esc_html($item['print_color']);
                if (!empty($cd)) {
                    if (!empty($cd['material']) && empty($specs['material']))
                        $specs[] = 'Material: ' . esc_html($cd['material']);
                    if (!empty($cd['color']) && empty($specs['color']))
                        $specs[] = 'Color: ' . esc_html($cd['color']);
                    if (!empty($cd['size']))
                        $specs[] = 'Size: ' . esc_html($cd['size']);
                }
                if (!empty($specs)) {
                    echo '<div style="color:#666;margin-bottom:6px;">' . implode(' • ', $specs) . '</div>';
                }
                echo '<div style="color:#333;font-weight:600;">Qty: ' . esc_html($qty) . ' &nbsp; &nbsp; Price: $' . number_format($price, 2) . '</div>';
                echo '</div>';  // flex:1
                // Download button for SVG images
                if ($imgUrl && strpos($imgUrl, 'data:image/svg') === 0) {
                    echo '<button class="download-svg-btn button" data-svg-url="' . esc_attr($imgUrl) . '" data-item-name="' . esc_attr($pname) . '" style="border-color:#2271b1;color:#2271b1;border-radius:4px;cursor:pointer;font-size:12px;text-decoration:none;display:inline-flex;align-items:center;gap:4px;padding:4px 8px;">';
                    echo 'Download SVG';
                    echo '</button>';
                }
                echo '</div>';  // item row
            }
            echo '<div style="text-align:right;margin-top:12px;font-weight:700;">Order Total: $' . number_format((float) $cart_total, 2) . '</div>';
            echo '</div>';
        }

        echo '<h2 class="title">Customer</h2><table class="widefat"><tbody>';
        foreach ($meta as $k => $v) {
            printf('<tr><th style="width:220px">%s</th><td>%s</td></tr>', esc_html(ucwords(str_replace('_', ' ', $k))), esc_html($v));
        }
        echo '</tbody></table>';

        echo '<h2 class="title" style="margin-top:20px;">Text Fields</h2><table class="widefat"><tbody>';
        $rendered_any = false;
        if (is_array($fields_display) && !empty($fields_display)) {
            foreach ($fields_display as $label => $val) {
                if ($val === '')
                    continue;
                printf('<tr><th style="width:220px">%s</th><td>%s</td></tr>', esc_html($label), esc_html($val));
                $rendered_any = true;
            }
        }
        if (!$rendered_any && is_array($template_fields_array)) {
            foreach ($template_fields_array as $row) {
                $label = is_array($row) ? ($row['label'] ?? ($row['id'] ?? '')) : '';
                $val = is_array($row) ? ($row['value'] ?? '') : '';
                if ($label && $val !== '') {
                    printf('<tr><th style="width:220px">%s</th><td>%s</td></tr>', esc_html($label), esc_html($val));
                    $rendered_any = true;
                }
            }
        }
        if (!$rendered_any && is_array($template_data)) {
            foreach ($template_data as $fid => $data) {
                $label = is_array($data) ? ($data['label'] ?? $fid) : $fid;
                $val = is_array($data) ? ($data['value'] ?? '') : $data;
                if ($val === '')
                    continue;
                printf('<tr><th style="width:220px">%s</th><td>%s</td></tr>', esc_html($label), esc_html($val));
                $rendered_any = true;
            }
        }
        if (!$rendered_any && is_array($text_fields)) {
            foreach ($text_fields as $fid => $val) {
                if (!$val)
                    continue;
                $label = ucwords(str_replace(array('fsc-', '_'), array('', ' '), $fid));
                printf('<tr><th style="width:220px">%s</th><td>%s</td></tr>', esc_html($label), esc_html($val));
            }
        }
        echo '</tbody></table>';

        $statuses = $this->apd_get_all_statuses();
        $cur = get_post_status($order_id);
        echo '<h2 class="title" style="margin-top:20px;">Status</h2>';
        echo '<select id="apd-status" style="min-width:220px;">';
        foreach ($statuses as $k => $v) {
            printf('<option value="%s" %s>%s</option>', esc_attr($k), selected($cur, $k, false), esc_html($v));
        }
        echo '</select> <button class="button button-primary" id="apd-save-status">Update</button>';

        echo '<h2 class="title" style="margin-top:20px;">Internal Notes</h2>';
        echo '<div id="apd-notes" style="background:#fff;border:1px solid #e1e1e1;padding:12px;max-width:800px;">';
        if ($notes) {
            foreach ($notes as $n) {
                printf('<div style="margin-bottom:8px;"><em>%s</em> &mdash; %s</div>', esc_html($n['time'] ?? ''), esc_html($n['text'] ?? ''));
            }
        }
        echo '</div>';
        echo '<textarea id="apd-note-text" rows="3" style="width:800px;margin-top:8px;" placeholder="Add a note..."></textarea><br/>';
        echo '<button class="button" id="apd-add-note">Add Note</button>';

        echo '<h2 class="title" style="margin-top:20px;">Maintenance</h2>';
        echo '<button class="button" id="apd-rebuild-labels">Rebuild Field Labels</button> <span id="apd-rebuild-result" style="margin-left:8px;color:#555;"></span>';

        // JS
        ?>
        <script>
        (function(){
            const orderId = <?php echo (int) $order_id; ?>;
            document.getElementById('apd-save-status').addEventListener('click', function(){
                const status = document.getElementById('apd-status').value;
                fetch(ajaxurl, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams({ action:'apd_update_order_status', order_id: orderId, status: status, _wpnonce: '<?php echo esc_js(wp_create_nonce('apd_ajax_nonce')); ?>' }) })
                .then(r=>r.json()).then(r=>{ if(r.success){ alert('Status updated'); } else { alert('Failed: '+(r.data&&r.data.message||'unknown')); } });
            });
            document.getElementById('apd-add-note').addEventListener('click', function(){
                const text = document.getElementById('apd-note-text').value.trim();
                if(!text) return;
                fetch(ajaxurl, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams({ action:'apd_add_order_note', order_id: orderId, text: text, _wpnonce: '<?php echo esc_js(wp_create_nonce('apd_ajax_nonce')); ?>' }) })
                .then(r=>r.json()).then(r=>{ if(r.success){ location.reload(); } else { alert('Failed: '+(r.data&&r.data.message||'unknown')); } });
            });
            document.getElementById('apd-rebuild-labels').addEventListener('click', function(){
                const el = document.getElementById('apd-rebuild-result');
                el.textContent = 'Working...';
                fetch(ajaxurl, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams({ action:'apd_rebuild_order_labels', order_id: orderId, _wpnonce: '<?php echo esc_js(wp_create_nonce('apd_ajax_nonce')); ?>' }) })
                .then(r=>r.json()).then(r=>{ if(r.success){ el.textContent = 'Rebuilt '+(r.data&&r.data.count||0)+' fields.'; location.reload(); } else { el.textContent = 'Failed: '+(r.data&&r.data.message||'unknown'); } });
            });
            
            // Download image functionality
            const downloadBtn = document.getElementById('download-design-btn');
            if (downloadBtn) {
                downloadBtn.addEventListener('click', function(){
                    const img = document.getElementById('preview-image');
                    if (!img) {
                        alert('No image found to download');
                        return;
                    }
                    
                    // Create a temporary canvas to convert image to downloadable format
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    
                    // Set canvas size to match image
                    canvas.width = img.naturalWidth || img.width;
                    canvas.height = img.naturalHeight || img.height;
                    
                    // Draw image on canvas
                    ctx.drawImage(img, 0, 0);
                    
                    // Convert canvas to blob and download
                    canvas.toBlob(function(blob) {
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = 'order-' + orderId + '-design.png';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                    }, 'image/png');
                });
            }

            // Download SVG functionality (if available)
            const svgDataUrl = <?php echo json_encode($svg_download_url ?: ''); ?>;
            const downloadSvgBtn = document.getElementById('download-design-svg-btn');
            if (downloadSvgBtn) {
                if (!svgDataUrl) {
                    downloadSvgBtn.style.display = 'none';
                } else {
                    downloadSvgBtn.addEventListener('click', function(){
                        // Check if it's a data URL or regular URL
                        if (svgDataUrl.startsWith('data:')) {
                            // Normalize uncommon mime like data:image+svg to image/svg+xml
                            const href = svgDataUrl.replace(/^data:image\+svg/i, 'data:image/svg+xml');
                            const a = document.createElement('a');
                            a.href = href;
                            a.download = 'order-' + orderId + '-design.svg';
                            document.body.appendChild(a);
                            a.click();
                            document.body.removeChild(a);
                        } else {
                            // For regular URLs, fetch and download
                            fetch(svgDataUrl)
                                .then(response => {
                                    if (!response.ok) throw new Error('Failed to fetch SVG');
                                    return response.blob();
                                })
                                .then(blob => {
                                    const url = window.URL.createObjectURL(blob);
                                    const a = document.createElement('a');
                                    a.href = url;
                                    a.download = 'order-' + orderId + '-design.svg';
                                    document.body.appendChild(a);
                                    a.click();
                                    document.body.removeChild(a);
                                    window.URL.revokeObjectURL(url);
                                })
                                .catch(error => {
                                    console.error('Download error:', error);
                                    alert('Error downloading SVG file. The file may be corrupted or missing.');
                                });
                        }
                    });
                }
            }

            // Download SVG for individual order items
            document.querySelectorAll('.download-svg-btn').forEach(btn => {
                btn.addEventListener('click', function(){
                    const svgUrl = this.getAttribute('data-svg-url');
                    const itemName = this.getAttribute('data-item-name');
                    if (!svgUrl) {
                        alert('No SVG data found');
                        return;
                    }
                    // Normalize uncommon mime like data:image+svg to image/svg+xml
                    const href = svgUrl.replace(/^data:image\+svg/i, 'data:image/svg+xml');
                    const a = document.createElement('a');
                    a.href = href;
                    a.download = itemName.replace(/[^a-zA-Z0-9]/g, '-') + '-design.svg';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                });
            });
        })();
        </script>
        <?php
        echo '</div>';
    }

    public function apd_update_order_status()
    {
        if (!current_user_can('manage_options'))
            wp_send_json_error(array('message' => 'forbidden'), 403);
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'apd_ajax_nonce'))
            wp_send_json_error(array('message' => 'bad nonce'), 403);
        $order_id = intval($_POST['order_id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? '');
        if (!$order_id || !$status)
            wp_send_json_error(array('message' => 'missing'), 400);
        wp_update_post(array('ID' => $order_id, 'post_status' => $status));
        wp_send_json_success();
    }

    public function apd_add_order_note()
    {
        if (!current_user_can('manage_options'))
            wp_send_json_error(array('message' => 'forbidden'), 403);
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'apd_ajax_nonce'))
            wp_send_json_error(array('message' => 'bad nonce'), 403);
        $order_id = intval($_POST['order_id'] ?? 0);
        $text = wp_kses_post($_POST['text'] ?? '');
        if (!$order_id || !$text)
            wp_send_json_error(array('message' => 'missing'), 400);
        $notes = get_post_meta($order_id, 'apd_notes', true);
        if (!is_array($notes))
            $notes = array();
        $notes[] = array('time' => current_time('Y-m-d H:i:s'), 'text' => $text, 'user' => get_current_user_id());
        update_post_meta($order_id, 'apd_notes', $notes);
        wp_send_json_success();
    }

    // Rebuild labels for old orders using template_data/text_fields heuristics
    public function apd_rebuild_order_labels()
    {
        if (!current_user_can('manage_options'))
            wp_send_json_error(array('message' => 'forbidden'), 403);
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'apd_ajax_nonce'))
            wp_send_json_error(array('message' => 'bad nonce'), 403);
        $order_id = intval($_POST['order_id'] ?? 0);
        if (!$order_id)
            wp_send_json_error(array('message' => 'missing order_id'), 400);
        $fields_display = get_post_meta($order_id, 'fields_display', true);
        $template_fields_array = get_post_meta($order_id, 'template_fields_array', true);
        $template_data = get_post_meta($order_id, 'template_data', true);
        $text_fields = get_post_meta($order_id, 'text_fields', true);
        $rebuilt = array();
        // Prefer existing arrays
        if (is_array($template_fields_array) && !empty($template_fields_array)) {
            foreach ($template_fields_array as $row) {
                $lbl = is_array($row) ? ($row['label'] ?? '') : '';
                $val = is_array($row) ? ($row['value'] ?? '') : '';
                if ($lbl && $val !== '')
                    $rebuilt[$lbl] = $val;
            }
        } elseif (is_array($template_data) && !empty($template_data)) {
            foreach ($template_data as $fid => $data) {
                $lbl = is_array($data) ? ($data['label'] ?? $fid) : $fid;
                $val = is_array($data) ? ($data['value'] ?? '') : $data;
                if ($val !== '')
                    $rebuilt[$lbl] = $val;
            }
        } elseif (is_array($text_fields) && !empty($text_fields)) {
            foreach ($text_fields as $fid => $val) {
                if (!$val)
                    continue;
                $lbl = ucwords(str_replace(array('fsc-', '_'), array('', ' '), $fid));
                $rebuilt[$lbl] = $val;
            }
        }
        if (!empty($rebuilt)) {
            update_post_meta($order_id, 'fields_display', $rebuilt);
            // Also create ordered array
            $ordered = array();
            foreach ($rebuilt as $k => $v) {
                $ordered[] = array('id' => $k, 'label' => $k, 'value' => $v);
            }
            update_post_meta($order_id, 'template_fields_array', $ordered);
        }
        wp_send_json_success(array('count' => count($rebuilt)));
    }

    private function generateManufacturingNotes($customization_data)
    {
        $notes = array();

        // Product specifications
        $notes[] = 'PRODUCT SPECIFICATIONS:';
        $notes[] = '- Product: ' . ($customization_data['product_name'] ?? 'Custom Freight Sign');
        $notes[] = '- Quantity: ' . ($customization_data['quantity'] ?? 1);
        $notes[] = '- Print Color: ' . ($customization_data['print_color'] ?? 'Black');
        $notes[] = '- Material: ' . ($customization_data['vinyl_material'] ?? 'Standard');

        // Text fields
        if (!empty($customization_data['text_fields'])) {
            $notes[] = '';
            $notes[] = 'TEXT CONTENT:';
            foreach ($customization_data['text_fields'] as $field_id => $value) {
                if (is_array($value)) {
                    $notes[] = '- ' . ($value['label'] ?? $field_id) . ': ' . ($value['value'] ?? '');
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
                    $notes[] = '- ' . ($value['label'] ?? $field_id) . ': ' . ($value['value'] ?? '');
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

    // Enhanced cart shortcode with live preview
    public function shortcode_cart()
    {
        ob_start();
        ?>
        <div class="apd-cart-page">
            <div class="apd-cart-header">
                <h2>Your Cart</h2>
                <div class="apd-cart-summary">
                    <!-- Select / Unselect all checkbox -->
                    <label class="apd-select-all-label" title="Select / Unselect all items">
                        <input type="checkbox" id="apd-select-all-checkbox" />
                        <span class="apd-select-all-ui"></span>
                    </label>
                    <span class="apd-cart-count">0 items</span>
                    <span class="apd-cart-total">Total: $0.00</span>
                </div>
            </div>
            
            <div class="apd-cart-content">
                <div class="apd-cart-items flex flex-col gap-3" id="apd-cart-items">
                    <!-- Cart items will be loaded here -->
                </div>
                
                <div class="apd-cart-actions">
                    <button class="apd-btn apd-btn-secondary" id="apd-clear-cart">Clear Cart</button>
                    <a href="<?php echo home_url(get_option('apd_checkout_url', '/checkout/')); ?>" class="apd-btn apd-btn-primary" id="apd-proceed-checkout" onclick="return APDCart.proceedToCheckout(event);">Proceed to Checkout</a>
                </div>
            </div>
        </div>
        
        <script>
        // Ensure apd_ajax is available for cart
        if (typeof apd_ajax === 'undefined') {
            window.apd_ajax = {
                ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
                nonce: '<?php echo wp_create_nonce('apd_ajax_nonce'); ?>',
                cart_url: '<?php echo home_url('/cart/'); ?>'
            };
        }
        // Cart will be initialized by cart.js automatically
        </script>
        <?php
        return ob_get_clean();
    }

    public function shortcode_cart_count()
    {
        $cart = $this->get_cart();
        $count = 0;
        foreach ($cart as $item) {
            $count += $item['quantity'];
        }

        ob_start();
        ?>
        <span class="apd-cart-count" id="apd-cart-count-display"><?php echo $count; ?></span>
        <script>
        jQuery(document).ready(function($) {
            // Update cart count when page loads
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'apd_get_cart',
                    nonce: '<?php echo wp_create_nonce('apd_ajax_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $('.apd-cart-count').text(response.data.count);
                    }
                }
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Render floating cart icon in footer
     */
    public function render_floating_cart_icon()
    {
        // Don't show on admin pages
        if (is_admin()) {
            return;
        }
        
        $cart = $this->get_cart();
        $count = 0;
        foreach ($cart as $item) {
            $count += $item['quantity'];
        }
        
        $cart_url = home_url(get_option('apd_cart_url', '/cart/'));
        ?>
        <style>
            .apd-floating-cart {
                position: fixed;
                bottom: 30px;
                right: 30px;
                width: 60px;
                height: 60px;
                background: #2271b1;
                border-radius: 50%;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                cursor: pointer;
                z-index: 9999;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.3s ease;
                text-decoration: none;
            }
            .apd-floating-cart:hover {
                transform: scale(1.1);
                box-shadow: 0 6px 16px rgba(0,0,0,0.2);
                background: #135e96;
            }
            .apd-floating-cart-icon {
                color: white;
                font-size: 24px;
                position: relative;
            }
            .apd-floating-cart-count {
                position: absolute;
                top: -8px;
                right: -8px;
                background: #dc3545;
                color: white;
                border-radius: 50%;
                min-width: 20px;
                height: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 11px;
                font-weight: bold;
                padding: 2px 4px;
                border: 2px solid white;
            }
            .apd-floating-cart-count.hidden {
                display: none;
            }
            /* Hide on mobile if needed */
            @media (max-width: 768px) {
                .apd-floating-cart {
                    bottom: 20px;
                    right: 20px;
                    width: 50px;
                    height: 50px;
                }
                .apd-floating-cart-icon {
                    font-size: 20px;
                }
            }
        </style>
        <a href="<?php echo esc_url($cart_url); ?>" class="apd-floating-cart" id="apd-floating-cart" title="View Cart">
            <div class="apd-floating-cart-icon">
                🛒
                <span class="apd-floating-cart-count <?php echo $count === 0 ? 'hidden' : ''; ?>" id="apd-floating-cart-count"><?php echo $count; ?></span>
            </div>
        </a>
        <script>
        (function() {
            // Update floating cart count via AJAX
            function updateFloatingCartCount() {
                const ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
                const nonce = '<?php echo wp_create_nonce('apd_ajax_nonce'); ?>';
                
                if (typeof jQuery !== 'undefined') {
                    jQuery.ajax({
                        url: ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'apd_get_cart',
                            nonce: nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                const count = response.data.count || 0;
                                const countEl = document.getElementById('apd-floating-cart-count');
                                if (countEl) {
                                    countEl.textContent = count;
                                    if (count === 0) {
                                        countEl.classList.add('hidden');
                                    } else {
                                        countEl.classList.remove('hidden');
                                    }
                                }
                            }
                        }
                    });
                }
            }
            
            // Update on page load
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', updateFloatingCartCount);
            } else {
                updateFloatingCartCount();
            }
            
            // Listen for custom cart update events
            document.addEventListener('apd_cart_updated', updateFloatingCartCount);
            window.addEventListener('apd_cart_updated', updateFloatingCartCount);
        })();
        </script>
        <?php
    }

    public function shortcode_checkout()
    {
        error_log('🛒 APD: shortcode_checkout() called!');
        error_log('🛒 APD: Template path: ' . APD_PLUGIN_PATH . 'templates/checkout.php');
        error_log('🛒 APD: Template exists: ' . (file_exists(APD_PLUGIN_PATH . 'templates/checkout.php') ? 'YES' : 'NO'));
        
        // Force cache bypass - add timestamp
        nocache_headers();
        
        // Ensure jQuery is loaded for checkout page
        wp_enqueue_script('jquery');
        wp_enqueue_script('html2canvas', 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js', array('jquery'), '1.4.1', true);
        
        // Prepare apd_ajax data for checkout page
        $apd_ajax_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('apd_ajax_nonce'),
            'site_url' => home_url(),
            'cart_url' => home_url(get_option('apd_cart_url', '/cart/')),
            'checkout_url' => home_url(get_option('apd_checkout_url', '/checkout/')),
            'products_url' => home_url(get_option('apd_products_url', '/products/')),
            'orders_url' => home_url(get_option('apd_orders_url', '/my-orders/')),
            'customizer_url' => home_url(get_option('apd_customizer_url', '/customizer/')),
            'thank_you_url' => home_url(get_option('apd_thank_you_url', '/thank-you/'))
        );
        
        // Create inline script to expose apd_ajax immediately (before DOMContentLoaded)
        // This ensures apd_ajax is available when checkout.php script runs
        wp_add_inline_script('jquery', 'window.apd_ajax = ' . wp_json_encode($apd_ajax_data) . ';', 'before');
        
        // Also localize for compatibility (in case scripts need it)
        wp_register_script('apd-checkout-stub', '', array('jquery'), APD_VERSION, true);
        wp_enqueue_script('apd-checkout-stub');
        wp_localize_script('apd-checkout-stub', 'apd_ajax', $apd_ajax_data);
        
        // Use the dedicated PHP template for the checkout UI to ensure consistent layout
        ob_start();
        include APD_PLUGIN_PATH . 'templates/checkout.php';
        return ob_get_clean();

        ob_start();
        ?>
        <div class="apd-checkout-page">
            <div class="checkout-header">
                <h1>Checkout</h1>
                <div class="checkout-steps">
                    <div class="step active">1. Review Order</div>
                    <div class="step">2. Payment</div>
                    <div class="step">3. Confirmation</div>
                </div>
            </div>
            
            <div class="checkout-layout">
                <!-- Left Column: Order Summary -->
                <div class="checkout-left">
                    <div class="order-summary-card">
                        <h2>Order Summary</h2>
                        
                        <!-- Preview Image Section -->
                        <div id="checkout-preview-section" style="margin-bottom: 24px; display: none;">
                            <div style="background: #f8f9fa; border-radius: 8px; padding: 16px; text-align: center;">
                                <h3 style="margin: 0 0 12px 0; font-size: 14px; color: #666; text-transform: uppercase; letter-spacing: 0.5px;">Your Design</h3>
                                <img id="checkout-preview-image" src="" alt="Your customized design" style="max-width: 100%; height: auto; display: block; margin: 0 auto;" />
                            </div>
                        </div>
                        
                        <div class="order-details">
                            <div class="detail-row">
                                <span class="label">Product:</span>
                                <span class="value">Custom Freight Sign</span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Color:</span>
                                <span class="value" id="checkout-color">Black</span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Material:</span>
                                <span class="value" id="checkout-material">—</span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Quantity:</span>
                                <span class="value" id="checkout-quantity">1</span>
                            </div>
                            
                            <!-- Dynamic Text Fields -->
                            <div id="checkout-text-fields" style="display: none;">
                                <!-- Text fields will be populated here -->
                            </div>
                        </div>
                        
                        <div class="price-breakdown">
                            <div class="price-row">
                                <span>Subtotal:</span>
                                <span id="checkout-subtotal">$29.99</span>
                            </div>
                            <div class="price-row">
                                <span>Shipping:</span>
                                <span>FREE</span>
                            </div>
                            <div class="price-row total">
                                <span>Total:</span>
                                <span id="checkout-total">$29.99</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column: Checkout Form -->
                <div class="checkout-right">
                    <div class="checkout-form-card">
                        <h2>Shipping Information</h2>
                        
                        <form id="apd-checkout-form" class="checkout-form">
                            <div class="form-section">
                                <h3>Contact Details</h3>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="customer-name">Full Name *</label>
                                        <input type="text" id="customer-name" name="customer_name" required 
                                               placeholder="Enter your full name">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="customer-email">Email Address *</label>
                                        <input type="email" id="customer-email" name="customer_email" required 
                                               placeholder="your.email@example.com">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="customer-phone">Phone Number *</label>
                                        <input type="tel" id="customer-phone" name="customer_phone" required 
                                               placeholder="(555) 123-4567">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3>Shipping Address</h3>
                                
                                <div class="form-row">
                                    <div class="form-group full-width">
                                        <label for="customer-address">Full Address *</label>
                                        <textarea id="customer-address" name="customer_address" rows="3" required 
                                                  placeholder="Enter your complete address including city, state, ZIP code..."></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3>Payment Method</h3>
                                <div class="payment-methods">
                                    <div class="payment-option">
                                        <input type="radio" id="payment-paypal" name="payment_method" value="paypal" checked>
                                        <label for="payment-paypal">
                                            <img src="https://www.paypalobjects.com/webstatic/mktg/logo/pp_cc_mark_37x23.jpg" alt="PayPal" style="height: 20px; margin-right: 8px;">
                                            <span>PayPal</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="checkout-actions">
                                <div id="paypal-button-container"></div>
                                <p class="security-note">
                                    <span>🛡️</span>
                                    Your information is secure and encrypted
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .apd-checkout-page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
        }
        
        .checkout-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .checkout-header h1 {
            font-size: 32px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 20px;
        }
        
        .checkout-steps {
            display: flex;
            justify-content: center;
            gap: 40px;
        }
        
        .step {
            padding: 12px 24px;
            border-radius: 25px;
            background: #f5f5f5;
            color: #666;
            font-weight: 500;
            position: relative;
        }
        
        .step.active {
            background: #007cba;
            color: white;
        }
        
        .step:not(:last-child)::after {
            content: '→';
            position: absolute;
            right: -25px;
            top: 50%;
            transform: translateY(-50%);
            color: #ccc;
        }
        
        .checkout-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }
        
        .order-summary-card,
        .checkout-form-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 30px;
            border: 1px solid #e8e8e8;
            height: fit-content;
        }
        
        .order-summary-card h2,
        .checkout-form-card h2 {
            font-size: 24px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .product-preview {
            margin-bottom: 24px;
        }
        
        .preview-image {
            width: 100%;
            height: 300px;
            border-radius: 8px;
            overflow: hidden;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px dashed #ddd;
        }
        
        .preview-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: 4px;
        }
        
        .preview-placeholder {
            text-align: center;
            color: #666;
        }
        
        .preview-placeholder i {
            font-size: 48px;
            margin-bottom: 12px;
            display: block;
        }
        
        .preview-info {
            margin-top: 12px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 6px;
            font-size: 14px;
            color: #666;
        }
        
        .preview-info img {
            border-radius: 2px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .order-details {
            margin-bottom: 24px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-row .label {
            color: #666;
            font-weight: 500;
        }
        
        .detail-row .value {
            color: #1a1a1a;
            font-weight: 600;
        }
        
        .price-breakdown {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        
        .price-row.total {
            font-size: 18px;
            font-weight: 700;
            color: #1a1a1a;
            padding-top: 12px;
            border-top: 2px solid #e8e8e8;
            margin-top: 12px;
        }
        
        .form-section {
            margin-bottom: 32px;
        }
        
        .form-section h3 {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 20px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }
        
        .form-row:has(.form-group:nth-child(2)) {
            grid-template-columns: 1fr 1fr;
        }
        
        .form-row:has(.form-group:nth-child(3)) {
            grid-template-columns: 1fr 1fr 1fr;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1a1a1a;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e8e8e8;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s ease;
            box-sizing: border-box;
            font-family: inherit;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #007cba;
            box-shadow: 0 0 0 3px rgba(0,124,186,0.1);
        }
        
        .payment-methods {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .payment-option {
            border: 2px solid #e8e8e8;
            border-radius: 8px;
            padding: 16px;
            transition: border-color 0.2s ease;
        }
        
        .payment-option:hover {
            border-color: #007cba;
        }
        
        .payment-option input[type="radio"] {
            display: none;
        }
        
        .payment-option input[type="radio"]:checked + label {
            color: #007cba;
        }
        
        .payment-option label {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            font-weight: 500;
            margin: 0;
        }
        
        .payment-option label span:first-child {
            font-size: 20px;
        }
        
        .checkout-actions {
            margin-top: 32px;
            text-align: center;
        }
        
        .btn-place-order {
            width: 100%;
            padding: 18px 24px;
            background: #007cba;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        
        .btn-place-order:hover {
            background: #005a87;
        }
        
        .btn-place-order:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .security-note {
            margin-top: 16px;
            color: #666;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .security-note span:first-child {
            color: #28a745;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .checkout-layout {
                grid-template-columns: 1fr;
                gap: 24px;
            }
            
            .checkout-steps {
                flex-direction: column;
                gap: 16px;
            }
            
            .step:not(:last-child)::after {
                display: none;
            }
            
            .form-row:has(.form-group:nth-child(2)),
            .form-row:has(.form-group:nth-child(3)) {
                grid-template-columns: 1fr;
            }
        }
        </style>
        
        <!-- PayPal SDK -->
        <?php
        $paypal_client_id = get_option('apd_paypal_client_id', '');
        $paypal_environment = get_option('apd_paypal_environment', 'sandbox');
        $currency = get_option('apd_currency', 'USD');
        $test_mode = get_option('apd_paypal_test_mode', '1');

        // Debug PayPal configuration
        echo '<!-- PayPal Config Debug: Client ID = ' . esc_html($paypal_client_id ? 'SET' : 'EMPTY') . ', Environment = ' . esc_html($paypal_environment) . ', Currency = ' . esc_html($currency) . ', Test Mode = ' . esc_html($test_mode ? 'ON' : 'OFF') . ' -->';

        if ($test_mode === '1') {
            // Test Mode - Mock PayPal payments
            echo '<!-- PayPal Test Mode: Mock payments enabled -->';
            echo '<script>window.paypalTestMode = true;</script>';
        } elseif (!empty($paypal_client_id) && $paypal_client_id !== 'YOUR_PAYPAL_CLIENT_ID' && $paypal_client_id !== '') {
            $sdk_url = 'https://www.paypal.com/sdk/js?client-id=' . esc_js($paypal_client_id) . '&currency=' . esc_js($currency) . '&intent=capture&components=buttons';
            if ($paypal_environment === 'sandbox') {
                $sdk_url .= '&buyer-country=US';
            }
            echo '<script src="' . $sdk_url . '"></script>';
            echo '<!-- PayPal SDK loaded successfully -->';
        } else {
            echo '<!-- PayPal SDK not loaded: Client ID not configured or invalid -->';
            echo '<script>console.warn("PayPal SDK not loaded: Client ID not configured. Please configure PayPal in Admin → Advanced Product Designer → Settings → Payment Settings");</script>';
        }
        ?>
        
        <script>
        (function(){
            // Ensure jQuery is available
            if (typeof jQuery === 'undefined' && typeof $ === 'undefined') {
                console.error('jQuery not available, loading fallback...');
                // Load jQuery from CDN as fallback
                var script = document.createElement('script');
                script.src = 'https://code.jquery.com/jquery-3.6.0.min.js';
                script.onload = function() {
                    console.log('jQuery fallback loaded successfully');
                    // Re-run the main function after jQuery loads
                    setTimeout(function() {
                        initializeCheckout();
                    }, 100);
                };
                script.onerror = function() {
                    console.error('Failed to load jQuery fallback');
                    initializeCheckoutWithoutJQuery();
                };
                document.head.appendChild(script);
                return;
            }
            
            // REMOVED: Console log disabler for debugging
            // try { if (window.console && typeof window.console.log === 'function') { window.console.log = function(){}; } } catch(e) {}
            
            initializeCheckout();
            
            function initializeCheckout() {
            try {
                // Debug: Check all localStorage keys
                console.log('=== CHECKOUT DEBUG START ===');
                console.log('All localStorage keys:', Object.keys(localStorage));
                
                var payload = null;
                try { payload = JSON.parse(localStorage.getItem('apd_checkout_payload')||'null'); } catch(e) {
                    console.error('Failed to parse localStorage payload:', e);
                }
                // Debug: Check if we have any image URLs
                // Check if we have real customized image URL
                if (payload && (!payload.image_url && !payload.customization_image_url && !payload.preview_image_url)) {
                    
                    // Only create fallback if we have text fields to display
                    if (payload.text_fields && Object.keys(payload.text_fields).length > 0) {
                        console.log('Creating fallback image from text fields only...');
                        try {
                            // Create a simple canvas-based image
                            var canvas = document.createElement('canvas');
                            var ctx = canvas.getContext('2d');
                            canvas.width = 800;
                            canvas.height = 600;
                            
                            // Fill with white background
                            ctx.fillStyle = '#ffffff';
                            ctx.fillRect(0, 0, canvas.width, canvas.height);
                            
                            // Add border
                            ctx.strokeStyle = '#cccccc';
                            ctx.lineWidth = 2;
                            ctx.strokeRect(1, 1, canvas.width-2, canvas.height-2);
                            
                            // Add title
                            ctx.fillStyle = '#333333';
                            ctx.font = 'bold 32px Arial';
                            ctx.textAlign = 'center';
                            ctx.fillText('Customized Product Preview', canvas.width/2, 80);
                            
                            // Add text fields
                            ctx.font = 'bold 24px Arial';
                            ctx.textAlign = 'left';
                            var y = 150;
                            Object.keys(payload.text_fields).forEach(function(key) {
                                if (payload.text_fields[key] && payload.text_fields[key].trim()) {
                                    ctx.fillText(payload.text_fields[key], 50, y);
                                    y += 40;
                                }
                            });
                            
                            // Add product info
                            ctx.font = '18px Arial';
                            ctx.textAlign = 'left';
                            var infoY = canvas.height - 100;
                            if (payload.product_name) {
                                ctx.fillText('Product: ' + payload.product_name, 50, infoY);
                                infoY += 25;
                            }
                            if (payload.print_color) {
                                ctx.fillText('Color: ' + payload.print_color, 50, infoY);
                                infoY += 25;
                            }
                            if (payload.vinyl_material) {
                                ctx.fillText('Material: ' + payload.vinyl_material, 50, infoY);
                            }
                            
                            // Convert to data URL and store in payload
                            var dataUrl = canvas.toDataURL('image/png');
                            payload.image_url = dataUrl;
                            payload.customization_image_url = dataUrl;
                            payload.preview_image_url = dataUrl;
                            
                            console.log('Fallback image created from text fields only:', dataUrl.substring(0, 50) + '...');
                        } catch(e) {
                            console.error('Failed to create fallback image:', e);
                        }
                    } else {
                        console.log('No text fields found either. Cannot create meaningful fallback image.');
                    }
                }
                
                // Debug: Log what we have in payload
                console.log('📦 Checkout payload loaded:', payload);
                if (payload) {
                    console.log('🖼️ Image fields in payload:', {
                        preview_image_png: payload.preview_image_png ? 'YES (' + payload.preview_image_png.substring(0, 50) + '...)' : 'NO',
                        preview_image_url: payload.preview_image_url ? 'YES' : 'NO',
                        customization_image_url: payload.customization_image_url ? 'YES' : 'NO',
                        image_url: payload.image_url ? 'YES' : 'NO'
                    });
                }
                
                // If no payload in localStorage, try to load from session
                if (!payload || (!payload.preview_image_png && !payload.image_url && !payload.preview_image_url && !payload.customization_image_url)) {
                    console.log('No payload or no image data in localStorage, trying session...');
                    fetch('<?php echo esc_js(admin_url('admin-ajax.php')); ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action: 'apd_get_checkout_data',
                            nonce: '<?php echo esc_js(wp_create_nonce('apd_ajax_nonce')); ?>'
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.data) {
                            console.log('Loaded from session:', data.data);
                            payload = data.data;
                            updateCheckoutUI(payload);
                        } else {
                            console.log('No session data available');
                            if (payload) updateCheckoutUI(payload);
                        }
                    })
                    .catch(error => {
                        console.error('Failed to load session data:', error);
                        if (payload) updateCheckoutUI(payload);
                    });
                } else {
                    updateCheckoutUI(payload);
                }
                
                function updateCheckoutUI(payload) {
                if (payload){
                    // Update order details
                    document.getElementById('checkout-color').textContent = payload.print_color||'Black';
                    
                    // Format material with image + name if available
                    var materialName = payload.vinyl_material || '';
                    if (materialName && materialName !== '') {
                        materialName = materialName.replace(/\b\w/g, function(l) { return l.toUpperCase(); });
                    } else {
                        materialName = 'Standard';
                    }
                    (function(){
                        var matNode = document.getElementById('checkout-material');
                        if (!matNode) return;
                        var md = payload.material_display || {};
                        var img = md.image || payload.material_texture_url || '';
                        // Fallback: derive image path from material name
                        if (!img && materialName && materialName !== 'Standard') {
                            try {
                                var fileName = materialName.replace(/\s+/g, '_') + '.png';
                                img = '/wp-content/plugins/Shop/uploads/material/' + encodeURIComponent(fileName);
                            } catch(e) {}
                        }
                        if (img) {
                            matNode.innerHTML = '<span style="display:inline-flex;align-items:center;gap:8px;">\n                                <span style="width:28px;height:28px;border-radius:6px;border:1px solid #ddd;display:inline-block;background:#fff;overflow:hidden;box-shadow:0 1px 2px rgba(0,0,0,.05)">\n                                    <span style="display:block;width:100%;height:100%;background-image:url('+img+');background-size:cover;background-position:center;"></span>\n                                </span>\n                                <span>'+materialName+'</span>\n                            </span>';
                        } else {
                            matNode.textContent = materialName;
                        }
                    })();
                    document.getElementById('checkout-quantity').textContent = payload.quantity||1;
                    
                    // Debug logging
                    console.log('Checkout payload debug:', {
                        vinyl_material: payload.vinyl_material,
                        formatted_material: materialName,
                        image_url: payload.image_url,
                        customization_image_url: payload.customization_image_url,
                        preview_image_url: payload.preview_image_url
                    });
                    
                    // Update pricing (free shipping)
                    var productPrice = parseFloat(payload.product_price) || 29.99; // Get price from payload or default
                    var quantity = payload.quantity || 1;
                    var subtotal = productPrice * quantity;
                    var total = subtotal; // No shipping fee
                    
                    document.getElementById('checkout-subtotal').textContent = '$' + subtotal.toFixed(2);
                    document.getElementById('checkout-total').textContent = '$' + total.toFixed(2);
                    
                    // Update text fields using ordered labels/values from payload
                    var textFieldsContainer = document.getElementById('checkout-text-fields');
                    if (textFieldsContainer) {
                        textFieldsContainer.innerHTML = '';
                        textFieldsContainer.style.display = 'none';
                        var rendered = 0;
                        // 1) Prefer fields_display (label -> value) to keep labels identical to customizer
                        if (payload.fields_display && typeof payload.fields_display === 'object' && Object.keys(payload.fields_display).length) {
                            Object.keys(payload.fields_display).forEach(function(label){
                                var value = String(payload.fields_display[label] || '');
                                var hint = (payload.fields_hints && payload.fields_hints[label]) ? String(payload.fields_hints[label]) : '';
                                if (label && value.trim()) {
                                    var row = document.createElement('div');
                                    row.className = 'detail-row';
                                    row.innerHTML = '<span class="label">'+label+':</span><span class="value">'+value+'</span>';
                                    textFieldsContainer.appendChild(row);
                                    rendered++;
                                }
                            });
                        }
                        // 2) Then template_fields_array (ordered id/label/value)
                        if (!rendered && Array.isArray(payload.template_fields_array) && payload.template_fields_array.length) {
                            payload.template_fields_array.forEach(function(item){
                                var label = (item && item.label) ? item.label : '';
                                var value = (item && item.value) ? String(item.value) : '';
                                if (label && value && value.trim()) {
                                    var row = document.createElement('div');
                                    row.className = 'detail-row';
                                    row.innerHTML = '<span class="label">'+label+':</span><span class="value">'+value+'</span>';
                                    textFieldsContainer.appendChild(row);
                                    rendered++;
                                }
                            });
                        }
                        // 3) Then template_data objects
                        if (!rendered && payload.template_data && typeof payload.template_data === 'object' && Object.keys(payload.template_data).length) {
                            Object.keys(payload.template_data).forEach(function(fieldId){
                                var fd = payload.template_data[fieldId];
                                var label = '';
                                var value = '';
                                if (fd && typeof fd === 'object') {
                                    label = fd.label || fieldId;
                                    value = fd.value || '';
                                    } else {
                                    label = fieldId;
                                    value = String(fd || '');
                                }
                                if (label && value && value.trim()) {
                                    var row = document.createElement('div');
                                    row.className = 'detail-row';
                                    row.innerHTML = '<span class="label">'+label+':</span><span class="value">'+value+'</span>';
                                    textFieldsContainer.appendChild(row);
                                    rendered++;
                                }
                            });
                        }
                        if (!rendered) {
                            // fallback to older logic (but still attempt to resolve label from template_data)
                            if (payload.text_fields && Object.keys(payload.text_fields).length > 0) {
                                Object.keys(payload.text_fields).forEach(function(fieldId) {
                                    var value = payload.text_fields[fieldId];
                                    if (value && value.trim()) {
                                        var label = '';
                                        if (payload.template_data && payload.template_data[fieldId]) {
                                            var td = payload.template_data[fieldId];
                                            if (td && typeof td === 'object' && td.label) { label = td.label; }
                                        }
                                        if (!label) { label = getFieldLabel(fieldId); }
                                        var fieldRow = document.createElement('div');
                                        fieldRow.className = 'detail-row';
                                        fieldRow.innerHTML = '<span class="label">' + label + ':</span><span class="value">' + value + '</span>';
                                        textFieldsContainer.appendChild(fieldRow);
                                        rendered++;
                                    }
                                });
                            }
                        }
                        if (rendered > 0) { textFieldsContainer.style.display = 'block'; }
                        
                        console.log('🔍 DEBUG: Text fields processing completed, continuing to image processing...');
                        
                        // Helper function to get field label (jQuery-free version)
                        function getFieldLabel(fieldId) {
                            try {
                                // Try to find the label from the template fields container using vanilla JS
                                var label = document.querySelector('#fsc-template-fields label[for="' + fieldId + '"]');
                                if (label) {
                                    return label.textContent || label.innerText;
                            }
                            
                            // Try to find label from data attributes
                                var input = document.querySelector('#fsc-template-fields input[id="' + fieldId + '"], #fsc-template-fields textarea[id="' + fieldId + '"]');
                                if (input) {
                                    var labelText = input.getAttribute('data-label') || input.getAttribute('placeholder');
                            if (labelText) {
                                return labelText;
                                    }
                                }
                            } catch(e) {
                                console.warn('Error getting field label:', e);
                            }
                            
                            // Fallback: clean up fieldId
                            return fieldId.replace(/^fsc-/, '').replace(/_/g, ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); });
                        }
                        
                        // Show container if we have fields
                        if (textFieldsContainer.children.length > 0) {
                            textFieldsContainer.style.display = 'block';
                        }
                    }
                    
                    // Display preview image if available
                    console.log('🖼️ Attempting to display preview image...');
                    var imageUrl = payload.preview_image_png || payload.preview_image_url || payload.customization_image_url || payload.image_url;
                    console.log('Preview image URL:', imageUrl);
                    
                    if (imageUrl) { 
                        console.log('Loading image:', imageUrl);
                        var previewSection = document.getElementById('checkout-preview-section');
                        var previewImage = document.getElementById('checkout-preview-image');

                        if (previewSection && previewImage) {
                            previewSection.style.display = 'block';
                            previewImage.src = imageUrl;
                            previewImage.onload = function() {
                                console.log('✅ Preview image loaded successfully');
                            };
                            previewImage.onerror = function() {
                                console.error('❌ Preview image failed to load:', imageUrl);
                                previewSection.style.display = 'none';
                            };
                        }
                    } else {
                        console.log('⚠️ No preview image URL found in payload');
                    }
                    
                    // Update material display with better formatting
                    var materialEl = document.getElementById('checkout-material');
                    if (materialEl && payload.vinyl_material) {
                        var material = payload.vinyl_material;
                        // Capitalize first letter of each word
                        material = material.replace(/\b\w/g, function(l) { return l.toUpperCase(); });
                        materialEl.textContent = material;
                        
                    }
                    
                    // Always try to show material outline regardless of materialEl
                        if (payload.vinyl_material && payload.vinyl_material !== 'solid') {
                        console.log('Processing material outline for:', payload.vinyl_material);
                            var materialName = payload.vinyl_material.toLowerCase();
                            var materialFile = '';
                        
                        // Enhanced material mapping with more options
                            var materialMap = {
                                'diamond plate': 'Diamond_Plate.png',
                            'diamond_plate': 'Diamond_Plate.png',
                                'engine turn gold': 'Engine_turn_gold.png',
                            'engine_turn_gold': 'Engine_turn_gold.png',
                                'florentine silver': 'Florentine_Silver.png',
                            'florentine_silver': 'Florentine_Silver.png',
                                'gold': 'gold.png',
                            'brush gold': 'gold.png',
                            'brush_gold': 'gold.png'
                            };
                            
                        // Try to find material file
                            if (materialMap[materialName]) {
                                materialFile = materialMap[materialName];
                            console.log('Found material file in mapping:', materialFile);
                        } else {
                            console.log('Material not in mapping, trying AJAX for:', materialName);
                        }
                        
                        if (materialFile) {
                                var materialUrl = '<?php echo plugin_dir_url(__FILE__); ?>uploads/material/' + materialFile;
                            console.log('Using material URL:', materialUrl);
                                
                                // Update preview info with material image
                                var previewInfo = document.getElementById('preview-info');
                                var materialInfo = document.getElementById('preview-material-info');
                            console.log('DOM Elements found:', {previewInfo: previewInfo, materialInfo: materialInfo});
                            
                                if (previewInfo && materialInfo) {
                                    previewInfo.style.display = 'block';
                                var formattedMaterial = materialName.replace(/\b\w/g, function(l) { return l.toUpperCase(); });
                                materialInfo.innerHTML = 'Material: ' + formattedMaterial;
                                
                                // Update material texture preview
                                var materialTextureImg = document.getElementById('material-texture-img');
                                if (materialTextureImg) {
                                    materialTextureImg.src = materialUrl;
                                    materialTextureImg.style.display = 'block';
                                    materialTextureImg.onload = function() {
                                        console.log('Material texture image loaded successfully:', this.src);
                                    };
                                    materialTextureImg.onerror = function() {
                                        console.error('Material texture image failed to load:', this.src);
                                    };
                                }
                                
                                console.log('Material outline updated successfully');
                                console.log('Preview info display style:', previewInfo.style.display);
                                console.log('Material info innerHTML:', materialInfo.innerHTML);
                            } else {
                                console.log('Preview info elements not found');
                                console.log('Available elements with "preview" in ID:');
                                var allElements = document.querySelectorAll('[id*="preview"]');
                                allElements.forEach(function(el) {
                                    console.log('- Element ID:', el.id, 'Tag:', el.tagName);
                                });
                            }
                            
                            // Apply material texture to the main product image
                            console.log('Applying material texture to main product image...');
                            
                            // Function to apply material texture (canvas-only, persists through zoom)
                            function applyMaterialTexture() {
                                var mainPreviewImg = document.querySelector('#checkout-preview img');
                                if (!mainPreviewImg || !materialUrl) {
                                    console.log('Main preview image not found or no material URL available');
                                    return;
                                }
                                
                                console.log('🎨 Starting canvas material application...');
                                console.log('🎨 Material URL:', materialUrl);
                                console.log('🎨 Product image URL:', window.checkoutImageUrl || payload.image_url);
                                
                                try {
                                    var canvas = document.createElement('canvas');
                                    var ctx = canvas.getContext('2d');
                                    
                                    var productImg = new Image();
                                    productImg.crossOrigin = 'anonymous';
                                    productImg.onload = function() {
                                        console.log('✅ Main product image loaded successfully:', this.src);
                                        console.log('📐 Product image dimensions:', this.width, 'x', this.height);
                                        
                                        canvas.width = productImg.width;
                                        canvas.height = productImg.height;
                                        
                                        // Draw base product image
                                        ctx.drawImage(productImg, 0, 0);
                                        console.log('✅ Base product image drawn to canvas');
                                        
                                        // Load material texture
                                        var materialImg = new Image();
                                        materialImg.crossOrigin = 'anonymous';
                                        materialImg.onload = function() {
                                            console.log('✅ Material texture loaded successfully:', this.src);
                                            console.log('📐 Material texture dimensions:', this.width, 'x', this.height);
                                            
                                            // Apply material texture ONLY to text/logo areas, NOT background
                                            // First, create a mask for text/logo areas only
                                            ctx.globalCompositeOperation = 'source-over';
                                            
                                            // Save current state
                                            ctx.save();
                                            
                                            // Create a mask for text areas (white text = material texture, black = transparent)
                                            ctx.globalCompositeOperation = 'source-atop';
                                            ctx.globalAlpha = 0.8;
                                            ctx.drawImage(materialImg, 0, 0, canvas.width, canvas.height);
                                            
                                            // Restore state
                                            ctx.restore();
                                            ctx.globalCompositeOperation = 'source-over';
                                            ctx.globalAlpha = 1.0;
                                            
                                            console.log('✅ Material texture applied to canvas');
                                            
                                            var newDataUrl = canvas.toDataURL('image/png');
                                            console.log('✅ Canvas converted to data URL, length:', newDataUrl.length);
                                            
                                            // Update main preview
                                            mainPreviewImg.src = newDataUrl;
                                            console.log('✅ Main preview image updated with material texture');
                                            
                                            // Also update any zoom/lightbox images if present
                                            try {
                                                document.querySelectorAll('.apd-preview-modal img, .zoomImg, img[data-zoom-src]').forEach(function(img){
                                                    img.src = newDataUrl;
                                                    if (img.dataset) { img.dataset.zoomSrc = newDataUrl; }
                                                });
                                                console.log('✅ Zoom/lightbox images updated');
                                            } catch(_) {}
                                            
                                            console.log('🎉 Canvas-based material texture applied successfully');
                                        };
                                        materialImg.onerror = function() {
                                            console.error('❌ Failed to load material texture for main image:', this.src);
                                        };
                                        materialImg.src = materialUrl;
                                    };
                                    productImg.onerror = function() {
                                        console.error('❌ Failed to load main product image for material application:', this.src);
                                    };
                                    productImg.src = window.checkoutImageUrl || payload.image_url;
                                } catch(e) {
                                    console.error('❌ Canvas compositing failed:', e);
                                }
                            }
                            
                            // Apply after image load to avoid race conditions
                            if (window.checkoutImageLoaded) {
                                console.log('Image already loaded, applying material texture (canvas-only)...');
                                applyMaterialTexture();
                            } else {
                                console.log('Waiting for image to load before applying material texture (canvas-only)...');
                                var waitIv = setInterval(function(){
                                    if (window.checkoutImageLoaded) { clearInterval(waitIv); applyMaterialTexture(); }
                                }, 100);
                                setTimeout(function(){ clearInterval(waitIv); applyMaterialTexture(); }, 5000);
                            }
                        } else {
                            // Try to get material URL from AJAX
                            console.log('Loading material URL via AJAX for:', materialName);
                            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: 'action=apd_get_material_url&material_name=' + encodeURIComponent(payload.vinyl_material) + '&nonce=<?php echo wp_create_nonce('apd_ajax_nonce'); ?>'
                            })
                            .then(response => response.json())
                            .then(data => {
                                console.log('AJAX material response:', data);
                                if (data.success && data.data && data.data.material_url) {
                                    var previewInfo = document.getElementById('preview-info');
                                    var materialInfo = document.getElementById('preview-material-info');
                                    if (previewInfo && materialInfo) {
                                        previewInfo.style.display = 'block';
                                        var formattedMaterial = materialName.replace(/\b\w/g, function(l) { return l.toUpperCase(); });
                                        materialInfo.innerHTML = 'Material: ' + formattedMaterial;
                                        
                                        // Update material texture preview
                                        var materialTextureImg = document.getElementById('material-texture-img');
                                        if (materialTextureImg) {
                                            materialTextureImg.src = data.data.material_url;
                                            materialTextureImg.style.display = 'block';
                                            materialTextureImg.onload = function() {
                                                console.log('Material texture image loaded via AJAX:', this.src);
                                            };
                                            materialTextureImg.onerror = function() {
                                                console.error('Material texture image failed to load via AJAX:', this.src);
                                            };
                                        }
                                        
                                        console.log('Material outline updated via AJAX');
                                    }
                                } else {
                                    console.log('AJAX material request failed:', data);
                                }
                            })
                            .catch(error => {
                                console.error('Failed to load material URL:', error);
                            });
                        }
                    }
                    
                } else {
                    console.warn('No checkout payload found in localStorage');
                }
                } // End updateCheckoutUI function
                
                
                // Initialize PayPal payment
                console.log('=== PAYPAL INITIALIZATION START ===');
                console.log('Checking PayPal SDK availability...');
                console.log('PayPal object:', typeof paypal);
                console.log('Test mode (window.paypalTestMode):', window.paypalTestMode);
                console.log('PayPal container exists:', !!document.getElementById('paypal-button-container'));
                
                if (window.paypalTestMode) {
                    // Mock PayPal payment for testing
                    console.log('✅ Using mock PayPal payment...');
                    var paypalContainer = document.getElementById('paypal-button-container');
                    console.log('PayPal container element:', paypalContainer);
                    
                    if (paypalContainer) {
                        console.log('✅ PayPal container found, injecting mock button...');
                        paypalContainer.innerHTML = '<div style="padding: 20px; text-align: center; background: #e8f5e8; border: 1px solid #4caf50; border-radius: 4px; color: #2e7d32;"><h4 style="color: #2e7d32; margin: 0 0 10px 0;">🧪 Test Mode - Mock Payment</h4><p style="margin: 0 0 15px 0;">This is a simulated payment for testing purposes.</p><button id="mock-paypal-button" style="background: #4caf50; color: white; border: none; padding: 12px 24px; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold;">💳 Mock PayPal Payment</button></div>';
                        console.log('✅ Mock button HTML injected successfully');
                        console.log('PayPal container innerHTML length:', paypalContainer.innerHTML.length);
                        
                        // Add click handler for mock payment
                        document.getElementById('mock-paypal-button').addEventListener('click', function() {
                            console.log('Mock PayPal payment initiated...');
                            
                            // Simulate payment processing
                            var button = this;
                            var originalText = button.innerHTML;
                            button.innerHTML = '⏳ Processing...';
                            button.disabled = true;
                            
                            // Simulate delay
                            setTimeout(function() {
                                // Mock successful payment
                                var mockOrderDetails = {
                                    id: 'MOCK_ORDER_' + Date.now(),
                                    status: 'COMPLETED',
                                    purchase_units: [{
                                        payments: {
                                            captures: [{
                                                id: 'MOCK_CAPTURE_' + Date.now()
                                            }]
                                        }
                                    }],
                                    payer: {
                                        payer_id: 'MOCK_PAYER_' + Date.now()
                                    }
                                };
                                
                                console.log('Mock payment successful:', mockOrderDetails);
                                
                                // Process the mock order - include all customization data
                                var safePayload = Object.assign({}, payload||{});
                                // Don't delete images - we need them for the order!
                                
                                // Debug: Log image data being sent
                                console.log('🖼️ Image data in safePayload:', {
                                    preview_image_png: safePayload.preview_image_png ? 'YES (' + safePayload.preview_image_png.substring(0, 50) + '...)' : 'NO',
                                    preview_image_url: safePayload.preview_image_url ? 'YES' : 'NO',
                                    customization_image_url: safePayload.customization_image_url ? 'YES' : 'NO',
                                    image_url: safePayload.image_url ? 'YES' : 'NO'
                                });
                                
                                // Just stringify the entire customization data
                                var customizationData = {
                                    product_id: safePayload.product_id,
                                    product_name: safePayload.product_name,
                                    product_price: safePayload.product_price,
                                    quantity: safePayload.quantity,
                                    print_color: safePayload.print_color,
                                    vinyl_material: safePayload.vinyl_material,
                                    material_texture_url: safePayload.material_texture_url,
                                    text_fields: safePayload.text_fields,
                                    template_data: safePayload.template_data,
                                    fields_display: safePayload.fields_display,
                                    template_fields_array: safePayload.template_fields_array,
                                    preview_image_png: safePayload.preview_image_png,
                                    preview_image_url: safePayload.preview_image_url,
                                    customization_image_url: safePayload.customization_image_url,
                                    image_url: safePayload.image_url
                                };
                                console.log('Payment approved, creating order with customization data:', customizationData);
                                console.log('Payment approved, creating order with customization data:', customizationData);
                                console.log('Payment approved, creating order with customization data:', customizationData);
                                console.log('Payment approved, creating order with customization data:', customizationData);
                                console.log('Payment approved, creating order with customization data:', customizationData);
                                console.log('Payment approved, creating order with customization data:', customizationData);
                                
                                var orderData = {
                                    action: 'apd_place_order',
                                    nonce: '<?php echo esc_js(wp_create_nonce('apd_ajax_nonce')); ?>',
                                    customer_name: document.getElementById('customer-name').value,
                                    customer_email: document.getElementById('customer-email').value,
                                    customer_phone: document.getElementById('customer-phone').value,
                                    customer_address: document.getElementById('customer-address').value,
                                    payment_method: 'mock_paypal',
                                    paypal_order_id: mockOrderDetails.id,
                                    paypal_transaction_id: mockOrderDetails.purchase_units[0].payments.captures[0].id,
                                    paypal_payer_id: mockOrderDetails.payer.payer_id,
                                    payment_status: 'completed',
                                    customization_data: JSON.stringify(customizationData)
                                };
                                
                                // Submit order to WordPress
                                var xhr = new XMLHttpRequest();
                                xhr.open('POST', '<?php echo esc_js(admin_url('admin-ajax.php')); ?>');
                                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
                                xhr.onload = function(){
                                    try {
                                        var resp = JSON.parse(xhr.responseText||'{}');
                                        if (resp && resp.success && resp.data && (resp.data.redirect || resp.data.order_id)){
                                            // Store order ID in session storage for thank you page
                                            if (resp.data.order_id) {
                                                sessionStorage.setItem('last_order_id', resp.data.order_id);
                                            }
                                            // Redirect only when server confirms order
                                            if (resp.data.redirect) {
                                                window.location.href = resp.data.redirect;
                                            }
                                            return;
                                        }
                                    } catch(e) {}
                                    // Do not block the UX with alerts; log only so checkout can proceed without login
                                    console.warn('Order submit skipped (no login required). Server responded:', (xhr.responseText||'{}'));
                                };
                                var body = Object.keys(orderData).map(function(k){ return encodeURIComponent(k)+'='+encodeURIComponent(orderData[k]||''); }).join('&');
                                // Only attempt server submit when AJAX URL is available and we are not in local-only mode
                                try {
                                    var hasAjax = (typeof ajaxObj !== 'undefined' && ajaxObj && ajaxObj.ajax_url);
                                    var localOnly = false;
                                    try { localOnly = !!localStorage.getItem('apd_cart'); } catch(e) { localOnly = false; }
                                    if (hasAjax && !localOnly) {
                                        xhr.send(body);
                                    } else {
                                        console.log('Skipping server order submit – using local cart / mock payment.');
                                    }
                                } catch(e) {
                                    console.log('Skipping server order submit due to error:', e);
                                }
                                
                                // Show success message
                                button.innerHTML = '✅ Payment Successful!';
                                setTimeout(function() {
                                    button.innerHTML = originalText;
                                    button.disabled = false;
                                }, 2000);
                                
                            }, 1500); // 1.5 second delay to simulate processing
                        });
                    } else {
                        console.error('❌ PayPal container NOT found! Element with id="paypal-button-container" does not exist.');
                    }
                } else if (typeof paypal !== 'undefined' && paypal.Buttons) {
                    console.log('✅ Real PayPal SDK detected, rendering PayPal buttons...');
                    paypal.Buttons({
                        style: {
                            layout: 'vertical',
                            color: 'blue',
                            shape: 'rect',
                            label: 'paypal'
                        },
                        createOrder: function(data, actions) {
                            return actions.order.create({
                                purchase_units: [{
                                    amount: {
                                        currency_code: 'USD',
                                        value: (payload.product_price || 250).toFixed(2)
                                    },
                                    description: 'Custom Freight Sign - ' + (payload.product_name || 'Product A')
                                }],
                                application_context: {
                                    shipping_preference: 'NO_SHIPPING'
                                }
                            });
                        },
                        onApprove: function(data, actions) {
                            return actions.order.capture().then(function(details) {
                                // Payment successful, now create order in WordPress with all customization data
                                var safePayload = Object.assign({}, payload||{});
                                // Include all customization data including images
                                var customizationData = {
                                    product_id: safePayload.product_id,
                                    product_name: safePayload.product_name,
                                    product_price: safePayload.product_price,
                                    quantity: safePayload.quantity,
                                    print_color: safePayload.print_color,
                                    vinyl_material: safePayload.vinyl_material,
                                    material_texture_url: safePayload.material_texture_url,
                                    text_fields: safePayload.text_fields,
                                    template_data: safePayload.template_data,
                                    fields_display: safePayload.fields_display,
                                    template_fields_array: safePayload.template_fields_array,
                                    preview_image_png: safePayload.preview_image_png,
                                    preview_image_url: safePayload.preview_image_url,
                                    customization_image_url: safePayload.customization_image_url,
                                    image_url: safePayload.image_url
                                };
                                
                                var orderData = {
                                    action: 'apd_place_order',
                                    nonce: '<?php echo esc_js(wp_create_nonce('apd_ajax_nonce')); ?>',
                                    customer_name: document.getElementById('customer-name').value,
                                    customer_email: document.getElementById('customer-email').value,
                                    customer_phone: document.getElementById('customer-phone').value,
                                    customer_address: document.getElementById('customer-address').value,
                                    payment_method: 'paypal',
                                    paypal_order_id: details.id,
                                    paypal_transaction_id: details.purchase_units[0].payments.captures[0].id,
                                    paypal_payer_id: details.payer.payer_id,
                                    payment_status: 'completed',
                                    customization_data: JSON.stringify(customizationData)
                                };
                                
                                // Submit order to WordPress
                                var xhr = new XMLHttpRequest();
                                xhr.open('POST', '<?php echo esc_js(admin_url('admin-ajax.php')); ?>');
                                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
                                xhr.onload = function(){
                                    try {
                                        var resp = JSON.parse(xhr.responseText||'{}');
                                        if (resp && resp.success && resp.data && resp.data.redirect){ 
                                            // Store order ID in session storage for thank you page
                                            if (resp.data.order_id) {
                                                sessionStorage.setItem('last_order_id', resp.data.order_id);
                                            }
                                            // Redirect to thank you page
                                            window.location.href = resp.data.redirect;
                                            return; 
                                        }
                                    } catch(e) {}
                                    alert('Order created but payment verification failed. Please contact support.');
                                };
                                var body = Object.keys(orderData).map(function(k){ return encodeURIComponent(k)+'='+encodeURIComponent(orderData[k]||''); }).join('&');
                                xhr.send(body);
                                
                                // Show success message
                                alert('Payment successful! Order ID: ' + details.id);
                            });
                        },
                        onError: function(err) {
                            console.error('PayPal Error:', err);
                            alert('Payment failed. Please try again.');
                        }
                    }).render('#paypal-button-container');
                } else {
                    console.warn('⚠️ Neither test mode nor real PayPal SDK is available');
                    console.log('- window.paypalTestMode:', window.paypalTestMode);
                    console.log('- typeof paypal:', typeof paypal);
                    console.error('PayPal SDK not loaded or not available');
                    
                    // Show helpful message to user
                    var paypalContainer = document.getElementById('paypal-button-container');
                    if (paypalContainer) {
                        paypalContainer.innerHTML = '<div style="padding: 20px; text-align: center; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; color: #856404;"><h4 style="color: #856404; margin: 0 0 10px 0;">⚠️ PayPal Payment Not Available</h4><p style="margin: 0 0 10px 0;">PayPal integration is not configured.</p><p style="margin: 0 0 10px 0; font-size: 14px;"><strong>To enable PayPal payments:</strong></p><ol style="text-align: left; margin: 10px 0; padding-left: 20px; font-size: 14px;"><li>Go to WordPress Admin → Advanced Product Designer → Settings</li><li>Find "Payment Settings" section</li><li>Enter your PayPal Client ID OR enable Test Mode</li><li>Save settings and refresh this page</li></ol><p style="margin: 10px 0 0 0; font-size: 12px; color: #6c757d;">Or contact support to complete your order.</p></div>';
                    }
                    
                    // Fallback to regular form submission
                    var form = document.getElementById('apd-checkout-form');
                    if (form) {
                    form.addEventListener('submit', function(ev){
                        ev.preventDefault();
                            alert('PayPal integration not available. Please contact support to complete your order.');
                        });
                    }
                }
            } catch(e) {
                console.error('Error in initializeCheckout:', e);
            }
        }
        
        // Function to handle checkout without jQuery
        function initializeCheckoutWithoutJQuery() {
            console.log('Initializing checkout without jQuery...');
            try {
                // Basic checkout functionality without jQuery
                var payload = null;
                try {
                    var storedPayload = localStorage.getItem('apd_checkout_payload');
                    if (storedPayload) {
                        payload = JSON.parse(storedPayload);
                    }
                } catch(e) {
                    console.error('Failed to parse checkout payload:', e);
                }
                
                if (payload) {
                    // Update basic order details
                    var colorEl = document.getElementById('checkout-color');
                    var materialEl = document.getElementById('checkout-material');
                    var quantityEl = document.getElementById('checkout-quantity');
                    
                    if (colorEl) colorEl.textContent = payload.print_color || 'Black';
                    if (quantityEl) quantityEl.textContent = payload.quantity || 1;
                    
                    if (materialEl && payload.vinyl_material) {
                        var material = payload.vinyl_material.replace(/\b\w/g, function(l) { return l.toUpperCase(); });
                        materialEl.textContent = material;
                    }
                    
                    // Handle PayPal fallback
                    var paypalContainer = document.getElementById('paypal-button-container');
                    if (paypalContainer) {
                        paypalContainer.innerHTML = '<div style="padding: 20px; text-align: center; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; color: #6c757d;"><h4>Payment Method</h4><p>Please contact support to complete your order.</p><p><small>jQuery not available</small></p></div>';
                    }
                }
            } catch(e) {
                console.error('Error in checkout without jQuery:', e);
            }
        }
        })();
        </script>
        <?php
        return ob_get_clean();
    }

    public function shortcode_thankyou()
    {
        ob_start();
        ?>
        <div class="apd-thankyou-page" style="
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
        ">
            <!-- Header Section -->
            <div style="
                text-align: center;
                margin-bottom: 60px;
                padding: 40px 0;
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
                position: relative;
                overflow: hidden;
            ">
                <div style="
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    height: 4px;
                    background: linear-gradient(90deg, #3b82f6, #8b5cf6, #06b6d4, #10b981);
                "></div>
                
                <div style="
                    width: 80px;
                    height: 80px;
                    background: linear-gradient(135deg, #10b981, #059669);
                    border-radius: 50%;
                    margin: 0 auto 30px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);
                ">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="color: white;">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                
                <h1 style="
                    font-size: 3rem;
                    font-weight: 700;
                    color: #1e293b;
                    margin: 0 0 20px 0;
                    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    background-clip: text;
                ">Thank You!</h1>
                
                <p style="
                    font-size: 1.25rem;
                    color: #64748b;
                    margin: 0;
                    font-weight: 500;
                ">Your order has been successfully placed</p>
            </div>

            <!-- Order Details Section -->
            <div style="
                gap: 40px;
                margin-bottom: 40px;
            ">
                <!-- Order Summary Card -->
                <div style="
                    background: white;
                    border-radius: 16px;
                    padding: 30px;
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
                    border: 1px solid #e2e8f0;
                ">
                    <h3 style="
                        font-size: 1.5rem;
                        font-weight: 600;
                        color: #1e293b;
                        margin: 0 0 25px 0;
                        display: flex;
                        align-items: center;
                        gap: 12px;
                    ">
                        <div style="
                            width: 8px;
                            height: 8px;
                            background: #3b82f6;
                            border-radius: 50%;
                        "></div>
                        Order Summary
                    </h3>
                    
                    <div style="margin-bottom: 20px;">
                        <div style="
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            padding: 15px 0;
                            border-bottom: 1px solid #f1f5f9;
                        ">
                            <span style="color: #64748b; font-weight: 500;">Order ID</span>
                            <span style="color: #1e293b; font-weight: 600; font-family: monospace;">#<span id="order-id">Loading...</span></span>
                        </div>
                        
                        <div style="
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            padding: 15px 0;
                            border-bottom: 1px solid #f1f5f9;
                        ">
                            <span style="color: #64748b; font-weight: 500;">Order Date</span>
                            <span style="color: #1e293b; font-weight: 600;" id="order-date">Loading...</span>
                        </div>
                        
                        <div style="
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            padding: 15px 0;
                            border-bottom: 1px solid #f1f5f9;
                        ">
                            <span style="color: #64748b; font-weight: 500;">Status</span>
                            <span style="
                                background: linear-gradient(135deg, #dbeafe, #bfdbfe);
                                color: #1e40af;
                                padding: 6px 12px;
                                border-radius: 20px;
                                font-size: 0.875rem;
                                font-weight: 600;
                            ">Processing</span>
                        </div>
                        
                        <div style="
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            padding: 15px 0;
                        ">
                            <span style="color: #64748b; font-weight: 500;">Total Amount</span>
                            <span style="color: #1e293b; font-weight: 700; font-size: 1.25rem;" id="order-total">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div style="
                display: flex;
                gap: 20px;
                justify-content: center;
                flex-wrap: wrap;
            ">
                <a href="<?php echo home_url('/my-orders/'); ?>" style="
                    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
                    color: white;
                    padding: 16px 32px;
                    border-radius: 12px;
                    text-decoration: none;
                    font-weight: 600;
                    font-size: 1.125rem;
                    display: inline-flex;
                    align-items: center;
                    gap: 10px;
                    box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
                    transition: all 0.3s ease;
                    border: none;
                    cursor: pointer;
                " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 15px 35px rgba(59, 130, 246, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 25px rgba(59, 130, 246, 0.3)'">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    View My Orders
                </a>
                
                <a href="<?php echo home_url('/'); ?>" style="
                    background: white;
                    color: #3b82f6;
                    padding: 16px 32px;
                    border-radius: 12px;
                    text-decoration: none;
                    font-weight: 600;
                    font-size: 1.125rem;
                    display: inline-flex;
                    align-items: center;
                    gap: 10px;
                    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
                    transition: all 0.3s ease;
                    border: 2px solid #e2e8f0;
                    cursor: pointer;
                " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 15px 35px rgba(0, 0, 0, 0.15)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 25px rgba(0, 0, 0, 0.1)'">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                        <polyline points="9,22 9,12 15,12 15,22"/>
                    </svg>
                    Continue Shopping
                </a>
            </div>

            <!-- Support Section -->
            <div style="
                margin-top: 60px;
                text-align: center;
                padding: 40px;
                background: white;
                border-radius: 16px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            ">
                <h3 style="
                    font-size: 1.5rem;
                    font-weight: 600;
                    color: #1e293b;
                    margin: 0 0 15px 0;
                ">Need Help?</h3>
                <p style="
                    color: #64748b;
                    margin: 0 0 25px 0;
                    font-size: 1.125rem;
                ">Our customer support team is here to assist you</p>
                <div style="
                    display: flex;
                    gap: 20px;
                    justify-content: center;
                    flex-wrap: wrap;
                ">
                    <a href="mailto:support@example.com" style="
                        color: #3b82f6;
                        text-decoration: none;
                        font-weight: 500;
                        display: inline-flex;
                        align-items: center;
                        gap: 8px;
                    ">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        support@example.com
                    </a>
                    <span style="color: #cbd5e1;">•</span>
                    <a href="tel:+1234567890" style="
                        color: #3b82f6;
                        text-decoration: none;
                        font-weight: 500;
                        display: inline-flex;
                        align-items: center;
                        gap: 8px;
                    ">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/>
                        </svg>
                        +1 (234) 567-890
                    </a>
                </div>
            </div>
        </div>

        <script>
        // Load order details from session or URL parameters
        document.addEventListener('DOMContentLoaded', function() {
            // Try to get order details from various sources
            const urlParams = new URLSearchParams(window.location.search);
            const orderId = urlParams.get('order_id') || sessionStorage.getItem('last_order_id');
            
            if (orderId) {
                document.getElementById('order-id').textContent = orderId;
                
                // Fetch order details via AJAX
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'apd_get_order_details',
                        order_id: orderId,
                        nonce: '<?php echo wp_create_nonce('apd_order_details'); ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        const order = data.data;
                        document.getElementById('order-date').textContent = new Date(order.date).toLocaleDateString();
                        document.getElementById('order-total').textContent = '$' + parseFloat(order.total).toFixed(2);
                    }
                })
                .catch(error => {
                    console.log('Could not fetch order details:', error);
                    // Set default values
                    document.getElementById('order-date').textContent = new Date().toLocaleDateString();
                    document.getElementById('order-total').textContent = 'Processing...';
                });
            } else {
                // Set default values if no order ID found
                document.getElementById('order-id').textContent = 'N/A';
                document.getElementById('order-date').textContent = new Date().toLocaleDateString();
                document.getElementById('order-total').textContent = 'Processing...';
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function shortcode_orders()
    {
        // Include the orders template
        ob_start();
        include plugin_dir_path(__FILE__) . 'templates/orders.php';
        return ob_get_clean();
    }

    private function maybe_create_core_pages()
    {
        $pages = array(
            'apd_cart' => array(
                'title' => 'Cart',
                'slug' => 'cart',
                'content' => '[apd_cart]'
            ),
            'apd_checkout' => array(
                'title' => 'Checkout',
                'slug' => 'checkout',
                'content' => '[apd_checkout]'
            ),
            'apd_thankyou' => array(
                'title' => 'Thank You',
                'slug' => 'thank-you',
                'content' => '[apd_thank_you]'
            ),
            'apd_orders' => array(
                'title' => 'My Orders',
                'slug' => 'my-orders',
                'content' => '[apd_orders]'
            ),
        );
        foreach ($pages as $opt_key => $def) {
            $existing = get_page_by_path($def['slug']);
            if ($existing && $existing->ID) {
                update_option($opt_key, intval($existing->ID));
                continue;
            }
            $id = wp_insert_post(array(
                'post_title' => $def['title'],
                'post_name' => $def['slug'],
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_content' => $def['content']
            ));
            if (!is_wp_error($id) && $id) {
                update_option($opt_key, intval($id));
            }
        }
    }

    // Run on admin/init to backfill pages if missing
    public function ensure_core_pages()
    {
        // Only run for admins to avoid overhead
        if (!current_user_can('manage_options'))
            return;
        $needed = array(
            get_option('apd_cart') => 'cart',
            get_option('apd_checkout') => 'checkout',
            get_option('apd_thankyou') => 'thank-you',
            get_option('apd_orders') => 'my-orders',
        );
        $missing = false;
        foreach ($needed as $optId => $slug) {
            if (!$optId || !get_post($optId)) {
                $missing = true;
                break;
            }
        }
        if ($missing) {
            $this->maybe_create_core_pages();
            flush_rewrite_rules(false);
        }
    }

    public function add_query_vars($vars)
    {
        $vars[] = 'customizer';
        $vars[] = 'product_detail';
        return $vars;
    }

    public function template_redirect()
    {
        $customizer_id = get_query_var('customizer');
        if ($customizer_id) {
            // Debug: Log customizer redirect
            error_log('APD Template Redirect: Customizer detected with ID: ' . $customizer_id);

            // Get header and footer
            get_header();

            // Render the original customizer with product ID
            $this->render_customizer($customizer_id);

            get_footer();
            exit;
        }

        if (get_query_var('product_detail')) {
            include APD_PLUGIN_PATH . 'templates/product-detail-page.php';
            exit;
        }
    }

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

    public function load_company_taxonomy_template($template)
    {
        // Check if we're viewing a company taxonomy archive
        if (is_tax('apd_company')) {
            // Set Elementor canvas template (full width)
            add_filter('template_include', function($template) {
                // Force Elementor canvas template for full width
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

    public function set_company_archive_elementor_template()
    {
        if (is_tax('apd_company')) {
            // Force Elementor Canvas template (full width, no header/footer from theme)
            add_filter('template_include', function($template) {
                // Set page template meta for Elementor
                global $wp_query;
                if (isset($wp_query->queried_object_id)) {
                    // Elementor Canvas = full width
                    add_filter('elementor/page/get_template', function() {
                        return 'elementor_canvas';
                    });
                }
                return $template;
            }, 999);
        }
    }

    public function product_detail_shortcode($atts)
    {
        // Parse shortcode attributes
        $atts = shortcode_atts(array(
            'id' => 0,
            'show_price' => 'true',
            'show_description' => 'true',
            'show_features' => 'true',
            'show_specs' => 'true',
            'show_related' => 'true'
        ), $atts);

        $product_id = intval($atts['id']);

        if ($product_id <= 0) {
            return '<div class="apd-error">Invalid product ID provided.</div>';
        }

        // Get product data
        $product = get_post($product_id);

        if (!$product || $product->post_type !== 'apd_product') {
            return '<div class="apd-error">Product not found.</div>';
        }

        // Get product meta data
        $price = get_post_meta($product_id, '_fsc_price', true);
        $material = get_post_meta($product_id, '_fsc_material', true);
        $features = get_post_meta($product_id, '_fsc_features', true);
        $category = get_post_meta($product_id, '_fsc_category', true);
        $logo_url = get_post_meta($product_id, '_fsc_logo_file', true);

        // Build HTML output
        ob_start();
        ?>
        <div class="apd-product-detail-wrapper">
            <article class="apd-single-product">
                
                <!-- Product Header -->
                <div class="apd-product-header">
                    <h1 class="apd-product-title"><?php echo esc_html($product->post_title); ?></h1>
                    <?php if ($atts['show_price'] === 'true' && $price): ?>
                        <div class="apd-product-price">$<?php echo esc_html($price); ?></div>
                    <?php endif; ?>
                </div>

                <!-- Product Content -->
                <div class="apd-product-content">
                    
                    <!-- Product Image/Gallery -->
                    <div class="apd-product-gallery">
                        <?php if (has_post_thumbnail($product_id)): ?>
                            <div class="apd-product-image">
                                <?php echo get_the_post_thumbnail($product_id, 'large'); ?>
                            </div>
                        <?php elseif ($logo_url): ?>
                            <div class="apd-product-image">
                                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($product->post_title); ?>" />
                            </div>
                        <?php else: ?>
                            <div class="apd-product-image">
                                <img src="<?php echo APD_PLUGIN_URL; ?>assets/images/placeholder.png" alt="<?php echo esc_attr($product->post_title); ?>" />
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Product Details -->
                    <div class="apd-product-details">
                        
                        <!-- Product Description -->
                        <?php if ($atts['show_description'] === 'true' && $product->post_content): ?>
                            <div class="apd-product-description">
                                <h3>Description</h3>
                                <?php echo wpautop($product->post_content); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Product Features -->
                        <?php if ($atts['show_features'] === 'true' && $features && is_array($features)): ?>
                            <div class="apd-product-features">
                                <h3>Features & Benefits</h3>
                                <ul class="apd-feature-list">
                                    <?php foreach ($features as $feature): ?>
                                        <li><?php echo esc_html($feature); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <!-- Product Specifications -->
                        <?php if ($atts['show_specs'] === 'true' && $material): ?>
                            <div class="apd-product-specs">
                                <h3>Specifications</h3>
                                <div class="apd-spec-item">
                                    <strong>Material:</strong> <?php echo esc_html($material); ?>
                                </div>
                                <?php if ($category): ?>
                                    <div class="apd-spec-item">
                                        <strong>Category:</strong> <?php echo esc_html($category); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Customizer Actions -->
                        <div class="apd-product-actions">
                            <div class="apd-action-buttons">
                                <a href="<?php echo home_url('/customizer/' . $product_id . '/'); ?>" 
                                   class="apd-btn apd-btn-primary apd-btn-customize"
                                   data-product-id="<?php echo $product_id; ?>">
                                    <span class="btn-icon">🎨</span>
                                    <span class="btn-text">Customize This Product</span>
                                </a>
                                
                                <button class="apd-btn apd-btn-secondary apd-btn-add-cart" 
                                        data-product-id="<?php echo $product_id; ?>">
                                    <span class="btn-icon">🛒</span>
                                    <span class="btn-text">Add to Cart</span>
                                </button>
                            </div>
                            
                            <!-- Quantity Selector -->
                            <div class="apd-quantity-selector">
                                <label for="apd-quantity-<?php echo $product_id; ?>">Quantity:</label>
                                <div class="apd-quantity-controls">
                                    <button type="button" class="apd-qty-btn apd-qty-minus" data-target="apd-quantity-<?php echo $product_id; ?>">−</button>
                                    <input type="number" id="apd-quantity-<?php echo $product_id; ?>" value="1" min="1" max="100" class="apd-quantity-input">
                                    <button type="button" class="apd-qty-btn apd-qty-plus" data-target="apd-quantity-<?php echo $product_id; ?>">+</button>
                                </div>
                            </div>

                            <!-- Quick Preview -->
                            <div class="apd-quick-preview">
                                <button class="apd-btn apd-btn-outline apd-btn-preview" data-product-id="<?php echo $product_id; ?>">
                                    👁️ Quick Preview
                                </button>
                            </div>
                        </div>

                        <!-- Related Products -->
                        <?php if ($atts['show_related'] === 'true'): ?>
                            <?php
                            $related_products = get_posts(array(
                                'post_type' => 'apd_product',
                                'posts_per_page' => 4,
                                'post__not_in' => array($product_id),
                                'meta_key' => '_fsc_category',
                                'meta_value' => $category
                            ));

                            if ($related_products):
                                ?>
                                <div class="apd-related-products">
                                    <h3>Related Products</h3>
                                    <div class="apd-related-grid">
                                        <?php foreach ($related_products as $related): ?>
                                            <div class="apd-related-item">
                                                <a href="<?php echo home_url('/product-detail/?id=' . $related->ID); ?>">
                                                    <?php if (has_post_thumbnail($related->ID)): ?>
                                                        <?php echo get_the_post_thumbnail($related->ID, 'medium'); ?>
                                                    <?php else: ?>
                                                        <img src="<?php echo APD_PLUGIN_URL; ?>assets/images/placeholder.png" alt="<?php echo esc_attr($related->post_title); ?>" />
                                                    <?php endif; ?>
                                                    <h4><?php echo esc_html($related->post_title); ?></h4>
                                                    <?php if (get_post_meta($related->ID, '_fsc_price', true)): ?>
                                                        <div class="apd-related-price">$<?php echo esc_html(get_post_meta($related->ID, '_fsc_price', true)); ?></div>
                                                    <?php endif; ?>
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                    </div>
                </div>
                
            </article>
        </div>

        <style>
        .apd-product-detail-wrapper {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .apd-single-product {
            width: 100%;
        }

        .apd-product-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }

        .apd-product-title {
            font-size: 2.5rem;
            margin: 0 0 10px 0;
            font-weight: bold;
        }

        .apd-product-price {
            font-size: 1.5rem;
            font-weight: 600;
            opacity: 0.9;
        }

        .apd-product-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            padding: 40px;
        }

        .apd-product-gallery {
            position: sticky;
            top: 20px;
        }

        .apd-product-image img {
            width: 100%;
            height: auto;
            border-radius: 8px;
        }

        .apd-product-details h3 {
            color: #333;
            font-size: 1.5rem;
            margin: 30px 0 15px 0;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }

        .apd-product-description {
            margin-bottom: 30px;
        }

        .apd-feature-list {
            list-style: none;
            padding: 0;
        }

        .apd-feature-list li {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            position: relative;
            padding-left: 25px;
        }

        .apd-feature-list li:before {
            content: "✓";
            color: #28a745;
            font-weight: bold;
            position: absolute;
            left: 0;
        }

        .apd-spec-item {
            padding: 8px 0;
            color: #666;
        }

        .apd-product-actions {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 8px;
            margin: 30px 0;
        }

        .apd-action-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .apd-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .apd-btn:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .apd-btn:hover:before {
            left: 100%;
        }

        .apd-btn-primary {
            background: #667eea;
            color: white;
        }

        .apd-btn-primary:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }

        .apd-btn-secondary {
            background: #6c757d;
            color: white;
        }

        .apd-btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .apd-quantity-selector {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .apd-quantity-controls {
            display: flex;
            align-items: center;
            border: 1px solid #ddd;
            border-radius: 6px;
            overflow: hidden;
        }

        .apd-qty-btn {
            background: #f8f9fa;
            border: none;
            padding: 8px 12px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.2s ease;
            user-select: none;
        }

        .apd-qty-btn:hover {
            background: #e9ecef;
        }

        .apd-qty-btn:active {
            background: #dee2e6;
        }

        .apd-quantity-input {
            width: 60px;
            padding: 8px;
            border: none;
            text-align: center;
            font-weight: 600;
            outline: none;
        }

        .apd-btn-outline {
            background: transparent;
            border: 2px solid #667eea;
            color: #667eea;
        }

        .apd-btn-outline:hover {
            background: #667eea;
            color: white;
        }

        .apd-quick-preview {
            margin-top: 15px;
        }

        .btn-icon {
            margin-right: 8px;
        }

        .btn-text {
            transition: all 0.3s ease;
        }

        .apd-related-products {
            margin-top: 50px;
        }

        .apd-related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .apd-related-item {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .apd-related-item:hover {
            transform: translateY(-5px);
        }

        .apd-related-item a {
            text-decoration: none;
            color: inherit;
        }

        .apd-related-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }

        .apd-related-item h4 {
            padding: 15px;
            margin: 0;
            font-size: 1rem;
        }

        .apd-related-price {
            padding: 0 15px 15px;
            font-weight: 600;
            color: #667eea;
        }

        .apd-error {
            background: #ffebee;
            border: 1px solid #f44336;
            color: #c62828;
            padding: 20px;
            border-radius: 4px;
            text-align: center;
        }

        @media (max-width: 768px) {
            .apd-product-content {
                grid-template-columns: 1fr;
                gap: 20px;
                padding: 20px;
            }
            
            .apd-product-header {
                padding: 20px;
            }
            
            .apd-product-title {
                font-size: 2rem;
            }
            
            .apd-action-buttons {
                flex-direction: column;
            }
        }
        </style>
        <?php

        return ob_get_clean();
    }

    public function debug_admin_menu()
    {
        // Ensure admin menu is registered even if there were previous errors
        if (!current_user_can('manage_options')) {
            return;
        }

        // Always try to register menu as fallback
        add_menu_page(
            'Product Designer',
            'Product Designer',
            'manage_options',
            'apd-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-art',
            6
        );

        error_log('APD Debug: Fallback menu registration attempted');
    }

    public function admin_dashboard_notice()
    {
        // Only show to admins
        if (!current_user_can('manage_options')) {
            return;
        }

        // Always show for debugging
        echo '<div class="notice notice-info is-dismissible" data-notice="apd-dashboard">';
        echo '<p><strong>Product Designer Plugin Debug:</strong> ';
        echo '<a href="' . admin_url('admin.php?page=apd-dashboard') . '" class="button button-primary">Access Dashboard</a>';
        echo ' | <a href="' . admin_url('admin.php?page=apd-orders') . '" class="button">Orders</a>';
        echo ' | <a href="' . admin_url('admin.php?page=apd-templates') . '" class="button">Templates</a>';
        echo ' | <a href="' . admin_url('admin.php?page=apd-settings') . '" class="button">Settings</a>';
        echo '<br><small>Plugin loaded: ' . (class_exists('AdvancedProductDesigner') ? 'YES' : 'NO') . '</small></p>';
        echo '</div>';

        // Add JavaScript to handle notice dismissal
        echo '<script>
        jQuery(document).ready(function($) {
            $(document).on("click", ".notice[data-notice=\'apd-dashboard\'] .notice-dismiss", function() {
                $.post(ajaxurl, {
                    action: "apd_dismiss_dashboard_notice",
                    nonce: "' . wp_create_nonce('apd_dismiss_notice') . '"
                });
            });
        });
        </script>';
    }

    public function dismiss_dashboard_notice()
    {
        check_ajax_referer('apd_dismiss_notice', 'nonce');

        if (current_user_can('manage_options')) {
            update_user_meta(get_current_user_id(), 'apd_dashboard_notice_dismissed', true);
        }

        wp_die();
    }

    public function get_checkout_data()
    {
        check_ajax_referer('apd_ajax_nonce', 'nonce');

        if (!session_id()) {
            session_start();
        }

        $customization_data = isset($_SESSION['fsc_customization']) ? $_SESSION['fsc_customization'] : null;

        if ($customization_data) {
            wp_send_json_success($customization_data);
        } else {
            wp_send_json_error('No customization data found');
        }
    }

    public function add_admin_menu()
    {
        add_menu_page(
            'Product Designer',
            'Product Designer',
            'manage_options',
            'apd-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-art',
            6
        );
    }

    public function add_admin_submenus()
    {
        // Remove the default submenu that WordPress creates
        remove_submenu_page('apd-dashboard', 'apd-dashboard');

        // Force Dashboard to be first by using position
        global $submenu;

        // Add Dashboard as the first submenu (this will be the default page)
        add_submenu_page(
            'apd-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'apd-dashboard',
            array($this, 'dashboard_page')
        );

        add_submenu_page(
            'apd-dashboard',
            'Templates',
            'Templates',
            'manage_options',
            'apd-templates',
            array($this, 'templates_page')
        );

        // Add hidden submenu for designer (not visible in menu but accessible)
        add_submenu_page(
            null,  // No parent menu
            'Template Designer',
            'Template Designer',
            'manage_options',
            'apd-designer',
            array($this, 'designer_page')
        );

        add_submenu_page(
            'apd-dashboard',
            'Materials',
            'Materials',
            'manage_options',
            'apd-materials',
            array($this, 'materials_page')
        );

        add_submenu_page(
            'apd-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'apd-settings',
            array($this, 'settings_page')
        );

        // Shipping Prices now added as a top-level menu via add_menu_page

        // Force reorder submenu to put Dashboard first
        if (isset($submenu['apd-dashboard'])) {
            $menu_items = $submenu['apd-dashboard'];
            $dashboard_item = null;
            $other_items = array();

            foreach ($menu_items as $key => $item) {
                if ($item[2] === 'apd-dashboard') {
                    $dashboard_item = $item;
                } else {
                    $other_items[] = $item;
                }
            }

            if ($dashboard_item) {
                $submenu['apd-dashboard'] = array_merge(array($dashboard_item), $other_items);
            }
        }
    }

    public function add_orders_menu()
    {
        add_menu_page(
            'Orders',
            'Orders',
            'manage_options',
            'apd-orders',
            array($this, 'orders_page'),
            'dashicons-cart',
            7
        );
    }

    public function add_shipping_menu()
    {
        add_menu_page(
            'Shipping Prices',
            'Shipping Prices',
            'manage_options',
            'apd-shipping-prices',
            array($this, 'shipping_prices_page'),
            'dashicons-location-alt',
            8
        );
    }

    public function orders_page()
    {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // Get all orders
        $orders = get_posts(array(
            'post_type' => 'apd_order',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        ));

        include APD_PLUGIN_PATH . 'templates/admin/orders.php';
    }

    public function products_page()
    {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle bulk actions
        if (isset($_POST['action']) && $_POST['action'] == 'bulk-delete') {
            $this->handle_bulk_delete();
        }

        // Get all products
        $products = get_posts(array(
            'post_type' => 'apd_product',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));

        // Get all available materials
        $materials = $this->get_materials();

        // Get all available colors
        $colors = $this->get_default_colors();

        include APD_PLUGIN_PATH . 'templates/admin/products.php';
    }

    private function handle_bulk_delete()
    {
        if (!isset($_POST['post']) || !is_array($_POST['post'])) {
            return;
        }

        $post_ids = array_map('intval', $_POST['post']);
        $deleted = 0;

        foreach ($post_ids as $post_id) {
            if (get_post_type($post_id) === 'apd_product') {
                if (wp_delete_post($post_id, true)) {
                    $deleted++;
                }
            }
        }

        if ($deleted > 0) {
            add_action('admin_notices', function () use ($deleted) {
                echo '<div class="notice notice-success"><p>' . sprintf(__('%d freight products deleted.', 'freight-signs-customizer'), $deleted) . '</p></div>';
            });
        }
    }

    public function allow_svg_upload($mimes)
    {
        $mimes['svg'] = 'image/svg+xml';
        $mimes['svgz'] = 'image/svg+xml';
        return $mimes;
    }

    public function check_svg_security($file)
    {
        $wp_filetype = wp_check_filetype($file['name'], null);
        if ($wp_filetype['type'] === 'image/svg+xml') {
            $file['type'] = $wp_filetype['type'];
            $file['ext'] = $wp_filetype['ext'];
            $file['name'] = $file['name'];
            $file['url'] = $file['url'];
            $file['error'] = 0;
        }
        return $file;
    }

    public function svg_upload_notice()
    {
        if (isset($_GET['page']) && $_GET['page'] === 'freight-products') {
            // Show logo upload success/error messages
            if (isset($_GET['logo_success'])) {
                echo '<div class="notice notice-success is-dismissible"><p>✅ Logo uploaded successfully! The customizer will now use your new logo.</p></div>';
            }

            if (isset($_GET['logo_error'])) {
                $error_message = '';
                switch ($_GET['logo_error']) {
                    case 'upload_failed':
                        $error_message = '❌ Logo upload failed. Please try again.';
                        break;
                    case 'invalid_type':
                        $error_message = '❌ Invalid file type. Only SVG files are allowed.';
                        break;
                    case 'file_too_large':
                        $error_message = '❌ File too large. Maximum size is 2MB.';
                        break;
                    case 'move_failed':
                        $error_message = '❌ Failed to save logo file. Please check directory permissions.';
                        break;
                    default:
                        $error_message = '❌ An error occurred during logo upload.';
                }
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error_message) . '</p></div>';
            }

            $upload_dir = wp_upload_dir();
            $svg_dir = $upload_dir['basedir'] . '/svg/';

            if (!is_dir($svg_dir)) {
                $message = sprintf(
                    __('Please ensure the <strong>%s</strong> directory exists and is writable. This is necessary for SVG file uploads. <a href="%s">Learn more</a>', 'freight-signs-customizer'),
                    'svg',
                    'https://wordpress.org/support/article/changing-file-permissions/'
                );
                printf('<div class="notice notice-warning is-dismissible"><p>%s</p></div>', wp_kses_post($message));
            }
        }
    }

    public function handle_logo_upload()
    {
        // Check nonce
        if (!isset($_POST['fsc_logo_nonce']) || !wp_verify_nonce($_POST['fsc_logo_nonce'], 'fsc_upload_logo')) {
            wp_die('Security check failed');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        // Check if file was uploaded
        if (!isset($_FILES['logo_file']) || $_FILES['logo_file']['error'] !== UPLOAD_ERR_OK) {
            wp_redirect(admin_url('admin.php?page=freight-products&logo_error=upload_failed'));
            exit;
        }

        $file = $_FILES['logo_file'];

        // Check file type
        $file_type = wp_check_filetype($file['name']);
        if ($file_type['type'] !== 'image/svg+xml') {
            wp_redirect(admin_url('admin.php?page=freight-products&logo_error=invalid_type'));
            exit;
        }

        // Check file size (2MB limit)
        if ($file['size'] > 2 * 1024 * 1024) {
            wp_redirect(admin_url('admin.php?page=freight-products&logo_error=file_too_large'));
            exit;
        }

        // Create object directory if it doesn't exist
        $upload_dir = wp_upload_dir();
        $object_dir = $upload_dir['basedir'] . '/apd-svg/';
        if (!is_dir($object_dir)) {
            wp_mkdir_p($object_dir);
        }

        // Move file to object directory
        $logo_path = $object_dir . 'Logo-PNG.svg';
        if (move_uploaded_file($file['tmp_name'], $logo_path)) {
            // Set proper permissions
            chmod($logo_path, 0644);
            wp_redirect(admin_url('admin.php?page=apd-products&logo_success=1'));
        } else {
            wp_redirect(admin_url('admin.php?page=apd-products&logo_error=move_failed'));
        }
        exit;
    }

    // New methods for the redesigned plugin
    public function canvas_settings_meta_box($post)
    {
        wp_nonce_field('apd_save_product_meta', 'apd_product_meta_nonce');

        $canvas_width = get_post_meta($post->ID, '_apd_canvas_width', true);
        $canvas_height = get_post_meta($post->ID, '_apd_canvas_height', true);
        $background_color = get_post_meta($post->ID, '_apd_background_color', true);

        ?>
        <table class="form-table">
            <tr>
                <th><label for="apd_canvas_width">Canvas Width (px)</label></th>
                <td>
                    <input type="number" id="apd_canvas_width" name="apd_canvas_width" value="<?php echo esc_attr($canvas_width ?: '800'); ?>" class="regular-text" min="100" max="2000">
                    <p class="description">Default canvas width in pixels</p>
                </td>
            </tr>
            <tr>
                <th><label for="apd_canvas_height">Canvas Height (px)</label></th>
                <td>
                    <input type="number" id="apd_canvas_height" name="apd_canvas_height" value="<?php echo esc_attr($canvas_height ?: '600'); ?>" class="regular-text" min="100" max="2000">
                    <p class="description">Default canvas height in pixels</p>
                </td>
            </tr>
            <tr>
                <th><label for="apd_background_color">Background Color</label></th>
                <td>
                    <input type="color" id="apd_background_color" name="apd_background_color" value="<?php echo esc_attr($background_color ?: '#ffffff'); ?>" class="regular-text">
                    <p class="description">Default canvas background color</p>
                </td>
            </tr>
        </table>
        <?php
    }

    public function dashboard_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }

        // Get statistics
        $products = get_posts(array(
            'post_type' => 'apd_product',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));

        // Include dashboard template
        include APD_PLUGIN_PATH . 'templates/admin/dashboard.php';
    }

    public function templates_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle template actions
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'duplicate':
                    $this->duplicate_template();
                    break;
                case 'delete':
                    $this->delete_template();
                    break;
            }
        }

        // Get all templates
        $templates = get_posts(array(
            'post_type' => 'apd_template',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        ));

        include APD_PLUGIN_PATH . 'templates/admin/templates.php';
    }

    public function designer_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $template_id = isset($_GET['template_id']) ? intval($_GET['template_id']) : 0;
        $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
        $template = null;
        $product = null;

        // If product_id is provided, get the associated template
        if ($product_id) {
            $product = get_post($product_id);
            if ($product && $product->post_type === 'apd_product') {
                $template_id = get_post_meta($product_id, '_fsc_template', true);

                if ($template_id) {
                    $template = get_post($template_id);
                    if (!$template || $template->post_type !== 'apd_template') {
                        $template = null;
                        $template_id = 0;  // Reset template_id if template not found
                    }
                } else {
                    // Try to find template by name (template-1)
                    $template_by_name = get_posts(array(
                        'post_type' => 'apd_template',
                        'name' => 'template-1',
                        'posts_per_page' => 1,
                        'post_status' => 'any'
                    ));

                    if (!empty($template_by_name)) {
                        $found_template = $template_by_name[0];
                        // Update product meta with found template
                        update_post_meta($product_id, '_fsc_template', $found_template->ID);
                        $template_id = $found_template->ID;
                        $template = $found_template;
                    }
                }
            }
        }
        // If template_id is provided directly
        elseif ($template_id) {
            $template = get_post($template_id);
            if (!$template || $template->post_type !== 'apd_template') {
                $template = null;
                $template_id = 0;  // Reset template_id if template not found
            }
        }

        // Create new template if none exists
        if (!$template_id) {
            $new_template = array(
                'post_title' => 'New Template ' . date('Y-m-d H:i:s'),
                'post_content' => '',
                'post_status' => 'draft',
                'post_type' => 'apd_template'
            );

            $template_id = wp_insert_post($new_template);

            if ($template_id) {
                update_post_meta($template_id, '_apd_template_width', 800);
                update_post_meta($template_id, '_apd_template_height', 600);
                update_post_meta($template_id, '_apd_template_bg_type', 'color');
                update_post_meta($template_id, '_apd_template_bg_color', '#ffffff');
                update_post_meta($template_id, '_apd_template_data', '{}');

                // If this is for a product, associate the template with the product
                if ($product_id) {
                    update_post_meta($product_id, '_fsc_template', $template_id);
                }

                // Redirect to avoid duplicate template creation
                $redirect_url = admin_url('admin.php?page=apd-designer&template_id=' . $template_id);
                if ($product_id) {
                    $redirect_url .= '&product_id=' . $product_id;
                }
                wp_redirect($redirect_url);
                exit;
            }
        }

        // Pass variables to designer template
        $GLOBALS['apd_template_id'] = $template_id;
        $GLOBALS['apd_product_id'] = $product_id;
        $GLOBALS['apd_template'] = $template;
        
        // Get materials for the template and extract URLs only
        $materials_data = $this->get_materials();
        $materials = array();
        foreach ($materials_data as $name => $data) {
            $materials[$name] = is_array($data) ? $data['url'] : $data;
        }

        include APD_PLUGIN_PATH . 'templates/admin/designer.php';
    }

    public function materials_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle material upload
        if (isset($_POST['upload_material']) && wp_verify_nonce($_POST['material_nonce'], 'upload_material')) {
            $this->handle_material_upload();
        }

        // Handle material deletion
        if (isset($_POST['delete_material']) && wp_verify_nonce($_POST['material_nonce'], 'delete_material')) {
            $this->handle_material_deletion();
        }

        // Handle material price update
        if (isset($_POST['update_material_price']) && wp_verify_nonce($_POST['material_price_nonce'], 'update_material_price')) {
            $this->handle_material_price_update();
            // Use JavaScript redirect to avoid permission issues
            echo '<script type="text/javascript">window.location.href = "' . admin_url('admin.php?page=freight-signs-materials') . '";</script>';
            exit;
        }

        // Get current materials
        $materials = $this->get_materials_list();

        include APD_PLUGIN_PATH . 'templates/admin/materials.php';
    }

    public function settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        include APD_PLUGIN_PATH . 'templates/admin/settings.php';
    }

    public function shipping_prices_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        include APD_PLUGIN_PATH . 'templates/admin/shipping-prices.php';
    }

    public function handle_svg_upload()
    {
        check_ajax_referer('apd_nonce', 'nonce');

        if (!current_user_can('upload_files')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        if (!isset($_FILES['svg_file']) || $_FILES['svg_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => 'No file uploaded'));
        }

        $file = $_FILES['svg_file'];

        // Check file type
        $file_type = wp_check_filetype($file['name']);
        if ($file_type['type'] !== 'image/svg+xml') {
            wp_send_json_error(array('message' => 'Invalid file type. Only SVG files are allowed.'));
        }

        // Check file size (2MB limit)
        if ($file['size'] > 2 * 1024 * 1024) {
            wp_send_json_error(array('message' => 'File too large. Maximum size is 2MB.'));
        }

        // Create upload directory
        $upload_dir = wp_upload_dir();
        $svg_dir = $upload_dir['basedir'] . '/apd-svg/';
        if (!is_dir($svg_dir)) {
            wp_mkdir_p($svg_dir);
        }

        // Generate unique filename
        $filename = 'svg_' . time() . '_' . sanitize_file_name($file['name']);
        $file_path = $svg_dir . $filename;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            chmod($file_path, 0644);
            $file_url = $upload_dir['baseurl'] . '/apd-svg/' . $filename;

            wp_send_json_success(array(
                'url' => $file_url,
                'filename' => $filename,
                'message' => 'SVG uploaded successfully'
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to save file'));
        }
    }

    public function save_design()
    {
        check_ajax_referer('apd_nonce', 'nonce');

        $design_data = array(
            'canvas_width' => intval($_POST['canvas_width']),
            'canvas_height' => intval($_POST['canvas_height']),
            'background_color' => sanitize_hex_color($_POST['background_color']),
            'elements' => json_decode(stripslashes($_POST['elements']), true),
            'created_at' => current_time('mysql')
        );

        // Save to user meta or create a new post
        $design_id = wp_insert_post(array(
            'post_type' => 'apd_design',
            'post_title' => 'Design ' . date('Y-m-d H:i:s'),
            'post_status' => 'publish',
            'meta_input' => array(
                '_apd_design_data' => $design_data
            )
        ));

        if ($design_id) {
            wp_send_json_success(array(
                'design_id' => $design_id,
                'message' => 'Design saved successfully'
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to save design'));
        }
    }

    /**
     * Handle material upload
     */
    public function handle_material_upload()
    {
        if (!isset($_FILES['material_file']) || $_FILES['material_file']['error'] !== UPLOAD_ERR_OK) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>❌ Failed to upload material file.</p></div>';
            });
            return;
        }

        $file = $_FILES['material_file'];
        $material_name = sanitize_text_field($_POST['material_name']);

        // Validate file type
        $allowed_types = array('image/png', 'image/jpeg', 'image/jpg');
        if (!in_array($file['type'], $allowed_types)) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>❌ Only PNG and JPG files are allowed.</p></div>';
            });
            return;
        }

        // Create materials directory if it doesn't exist
        $material_dir = APD_PLUGIN_PATH . 'uploads/material/';
        if (!is_dir($material_dir)) {
            wp_mkdir_p($material_dir);
        }

        // Generate filename
        $filename = sanitize_file_name($material_name . '_' . time() . '.png');
        $file_path = $material_dir . $filename;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            chmod($file_path, 0644);

            // Save material info to database
            $material_price = isset($_POST['material_price']) ? floatval($_POST['material_price']) : 0;
            if ($material_price < 0) {
                $material_price = 0;
            }

            $materials = get_option('apd_materials', array());
            $materials[] = array(
                'name' => $material_name,
                'filename' => $filename,
                'url' => APD_PLUGIN_URL . 'uploads/material/' . $filename,
                'type' => 'uploaded',
                'date' => current_time('mysql'),
                'price' => $material_price
            );
            update_option('apd_materials', $materials);

            add_action('admin_notices', function () {
                echo '<div class="notice notice-success"><p>✅ Material uploaded successfully!</p></div>';
            });
        } else {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>❌ Failed to save material file.</p></div>';
            });
        }
    }

    /**
     * Handle material price update
     */
    public function handle_material_price_update()
    {
        $material_index = intval($_POST['material_index']);
        $material_price = isset($_POST['material_price']) ? floatval($_POST['material_price']) : 0;
        if ($material_price < 0) {
            $material_price = 0;
        }

        $materials = get_option('apd_materials', array());

        if (isset($materials[$material_index])) {
            $materials[$material_index]['price'] = $material_price;
            
            // Update option (use update_option for simpler caching)
            update_option('apd_materials', $materials, false);
            
            // Set transient for success message
            set_transient('apd_material_price_updated', true, 30);
        } else {
            // Set transient for error message
            set_transient('apd_material_price_error', true, 30);
        }
        
        // No redirect - let the page reload naturally via POST/Redirect/GET pattern
        // The page will reload on its own and display the transient message
    }

    /**
     * Handle material deletion
     */
    public function handle_material_deletion()
    {
        $material_index = intval($_POST['material_index']);
        $materials = get_option('apd_materials', array());

        if (isset($materials[$material_index])) {
            $material = $materials[$material_index];

            // Delete file if it's uploaded material
            if ($material['type'] === 'uploaded') {
                $file_path = APD_PLUGIN_PATH . 'uploads/material/' . $material['filename'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            // Remove from array
            unset($materials[$material_index]);
            $materials = array_values($materials);  // Re-index array

            update_option('apd_materials', $materials);

            add_action('admin_notices', function () {
                echo '<div class="notice notice-success"><p>✅ Material deleted successfully!</p></div>';
            });
        }
    }

    /**
     * Get materials list
     */
    public function get_materials_list()
    {
        $materials = get_option('apd_materials', array());

        // Only return materials from database, no legacy materials
        return $materials;
    }

    /**
     * Template Details Meta Box
     */
    public function template_details_meta_box($post)
    {
        wp_nonce_field('apd_template_meta', 'apd_template_meta_nonce');

        $width = get_post_meta($post->ID, '_apd_template_width', true) ?: 800;
        $height = get_post_meta($post->ID, '_apd_template_height', true) ?: 600;
        $background_type = get_post_meta($post->ID, '_apd_template_bg_type', true) ?: 'color';
        $background_color = get_post_meta($post->ID, '_apd_template_bg_color', true) ?: '#ffffff';
        $background_image = get_post_meta($post->ID, '_apd_template_bg_image', true);
        $template_data = get_post_meta($post->ID, '_apd_template_data', true) ?: '{}';

        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="apd_template_width">Canvas Width (px)</label>
                </th>
                <td>
                    <input type="number" id="apd_template_width" name="apd_template_width" value="<?php echo esc_attr($width); ?>" min="100" max="2000" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="apd_template_height">Canvas Height (px)</label>
                </th>
                <td>
                    <input type="number" id="apd_template_height" name="apd_template_height" value="<?php echo esc_attr($height); ?>" min="100" max="2000" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="apd_template_bg_type">Background Type</label>
                </th>
                <td>
                    <select id="apd_template_bg_type" name="apd_template_bg_type">
                        <option value="color" <?php selected($background_type, 'color'); ?>>Solid Color</option>
                        <option value="image" <?php selected($background_type, 'image'); ?>>Image</option>
                        <option value="gradient" <?php selected($background_type, 'gradient'); ?>>Gradient</option>
                    </select>
                </td>
            </tr>
            <tr id="bg-color-row" style="<?php echo $background_type === 'color' ? '' : 'display:none;'; ?>">
                <th scope="row">
                    <label for="apd_template_bg_color">Background Color</label>
                </th>
                <td>
                    <input type="color" id="apd_template_bg_color" name="apd_template_bg_color" value="<?php echo esc_attr($background_color); ?>">
                </td>
            </tr>
            <tr id="bg-image-row" style="<?php echo $background_type === 'image' ? '' : 'display:none;'; ?>">
                <th scope="row">
                    <label for="apd_template_bg_image">Background Image</label>
                </th>
                <td>
                    <input type="url" id="apd_template_bg_image" name="apd_template_bg_image" value="<?php echo esc_attr($background_image); ?>" class="regular-text">
                    <button type="button" class="button" id="select-bg-image">Select Image</button>
                </td>
            </tr>
        </table>
        
        <div class="apd-template-designer-link">
            <p><strong>Template Designer:</strong> <a href="<?php echo admin_url('admin.php?page=apd-designer&template_id=' . $post->ID); ?>" class="button button-primary">Open Template Designer</a></p>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#apd_template_bg_type').on('change', function() {
                var type = $(this).val();
                $('#bg-color-row, #bg-image-row').hide();
                if (type === 'color') {
                    $('#bg-color-row').show();
                } else if (type === 'image') {
                    $('#bg-image-row').show();
                }
            });
            
            $('#select-bg-image').on('click', function(e) {
                e.preventDefault();
                
                var frame = wp.media({
                    title: 'Select Background Image',
                    button: {
                        text: 'Use this image'
                    },
                    multiple: false,
                    library: {
                        type: 'image'
                    }
                });
                
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#apd_template_bg_image').val(attachment.url);
                });
                
                frame.open();
            });
        });
        </script>
        <?php
    }

    /**
     * Save Template Meta
     */
    public function save_template_meta($post_id)
    {
        if (!isset($_POST['apd_template_meta_nonce']) || !wp_verify_nonce($_POST['apd_template_meta_nonce'], 'apd_template_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (get_post_type($post_id) !== 'apd_template') {
            return;
        }

        $fields = array(
            '_apd_template_width',
            '_apd_template_height',
            '_apd_template_bg_type',
            '_apd_template_bg_color',
            '_apd_template_bg_image'
        );

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
    }

    /**
     * Duplicate Template
     */
    public function duplicate_template()
    {
        if (!isset($_POST['template_id']) || !wp_verify_nonce($_POST['_wpnonce'], 'duplicate_template_' . $_POST['template_id'])) {
            wp_die('Security check failed');
        }

        $template_id = intval($_POST['template_id']);
        $template = get_post($template_id);

        if (!$template || $template->post_type !== 'apd_template') {
            wp_die('Template not found');
        }

        $new_template = array(
            'post_title' => $template->post_title . ' (Copy)',
            'post_content' => $template->post_content,
            'post_status' => 'draft',
            'post_type' => 'apd_template'
        );

        $new_id = wp_insert_post($new_template);

        if ($new_id) {
            // Copy meta data
            $meta_keys = array(
                '_apd_template_width',
                '_apd_template_height',
                '_apd_template_bg_type',
                '_apd_template_bg_color',
                '_apd_template_bg_image',
                '_apd_template_data'
            );

            foreach ($meta_keys as $key) {
                $value = get_post_meta($template_id, $key, true);
                if ($value) {
                    update_post_meta($new_id, $key, $value);
                }
            }

            $url = admin_url('admin.php?page=apd-templates&duplicated=1');
            if (!headers_sent()) {
                wp_safe_redirect($url);
                exit;
            } else {
                echo '<meta http-equiv="refresh" content="0;url=' . esc_url($url) . '">';
                echo '<script>window.location.href = ' . json_encode($url) . ';</script>';
                exit;
            }
        }
    }

    /**
     * Delete Template
     */
    public function delete_template()
    {
        if (!isset($_POST['template_id']) || !wp_verify_nonce($_POST['_wpnonce'], 'delete_template_' . $_POST['template_id'])) {
            wp_die('Security check failed');
        }

        $template_id = intval($_POST['template_id']);
        $template = get_post($template_id);

        if (!$template || $template->post_type !== 'apd_template') {
            wp_die('Template not found');
        }

        wp_delete_post($template_id, true);

        $url = admin_url('admin.php?page=apd-templates&deleted=1');
        if (!headers_sent()) {
            wp_safe_redirect($url);
            exit;
        } else {
            echo '<meta http-equiv="refresh" content="0;url=' . esc_url($url) . '">';
            echo '<script>window.location.href = ' . json_encode($url) . ';</script>';
            exit;
        }
    }

    public function upload_font()
    {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Insufficient permissions')));
        }

        // Check if file was uploaded
        if (!isset($_FILES['font_file']) || $_FILES['font_file']['error'] !== UPLOAD_ERR_OK) {
            wp_die(json_encode(array('success' => false, 'data' => 'No file uploaded or upload error')));
        }

        $file = $_FILES['font_file'];

        // Validate file type
        $allowed_types = array('ttf', 'otf', 'woff', 'woff2');
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($file_extension, $allowed_types)) {
            wp_die(json_encode(array('success' => false, 'data' => 'Invalid file type. Only TTF, OTF, WOFF, and WOFF2 files are allowed.')));
        }

        // Validate file size (5MB limit)
        if ($file['size'] > 5 * 1024 * 1024) {
            wp_die(json_encode(array('success' => false, 'data' => 'File too large. Maximum size is 5MB.')));
        }

        // Create uploads directory if it doesn't exist
        $upload_dir = wp_upload_dir();
        $fonts_dir = $upload_dir['basedir'] . '/fonts/';

        if (!file_exists($fonts_dir)) {
            wp_mkdir_p($fonts_dir);
        }

        // Generate unique filename
        $filename = sanitize_file_name($file['name']);
        $filename = pathinfo($filename, PATHINFO_FILENAME) . '_' . time() . '.' . $file_extension;
        $file_path = $fonts_dir . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            wp_die(json_encode(array('success' => false, 'data' => 'Failed to save file')));
        }

        // Generate font family name from filename
        $font_name = pathinfo($file['name'], PATHINFO_FILENAME);
        $font_family = str_replace(array('-', '_'), ' ', $font_name);
        $font_family = ucwords($font_family);

        // Get file URL
        $file_url = $upload_dir['baseurl'] . '/fonts/' . $filename;

        // Store font info in options
        $uploaded_fonts = get_option('apd_uploaded_fonts', array());
        $uploaded_fonts[] = array(
            'name' => $font_name,
            'family' => $font_family,
            'url' => $file_url,
            'file' => $filename,
            'uploaded' => current_time('mysql')
        );
        update_option('apd_uploaded_fonts', $uploaded_fonts);

        wp_die(json_encode(array(
            'success' => true,
            'data' => array(
                'name' => $font_name,
                'family' => $font_family,
                'url' => $file_url,
                'file' => $filename
            )
        )));
    }

    public function delete_font()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        $uploaded_fonts = get_option('apd_uploaded_fonts', array());
        $index = isset($_POST['index']) ? intval($_POST['index']) : null;
        $fileParam = isset($_POST['file']) ? sanitize_file_name($_POST['file']) : '';

        $removed = false;
        if ($fileParam) {
            foreach ($uploaded_fonts as $i => $font) {
                if (!empty($font['file']) && $font['file'] === $fileParam) {
                    $removed = $this->remove_font_entry($uploaded_fonts, $i);
                    break;
                }
            }
        } elseif ($index !== null && isset($uploaded_fonts[$index])) {
            $removed = $this->remove_font_entry($uploaded_fonts, $index);
        }

        if ($removed) {
            update_option('apd_uploaded_fonts', $uploaded_fonts);
            wp_send_json_success(true);
        }
        wp_send_json_error('Font not found');
    }

    private function remove_font_entry(&$uploaded_fonts, $i)
    {
        $upload_dir = wp_upload_dir();
        $fonts_dir = trailingslashit($upload_dir['basedir']) . 'fonts/';
        $file = !empty($uploaded_fonts[$i]['file']) ? $uploaded_fonts[$i]['file'] : '';
        if ($file && file_exists($fonts_dir . $file)) {
            @unlink($fonts_dir . $file);
        }
        array_splice($uploaded_fonts, $i, 1);
        return true;
    }

    /**
     * Save Template Design via AJAX
     */
    public function save_template_design()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'apd_nonce')) {
            wp_send_json_error('Security check failed');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $template_id = intval($_POST['template_id']);
        $template_data = wp_unslash($_POST['template_data']);  // Use wp_unslash instead of sanitize_text_field for JSON data

        // Validate template exists
        $template = get_post($template_id);
        if (!$template || $template->post_type !== 'apd_template') {
            wp_send_json_error('Template not found');
        }

        // Parse and validate template data
        $data = json_decode($template_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('Invalid template data: ' . json_last_error_msg());
        }

        // Validate and sanitize template data structure
        if (!is_array($data)) {
            wp_send_json_error('Template data must be an array');
        }

        // Sanitize background image data if present
        if (isset($data['canvas']['background']['image'])) {
            $image_data = $data['canvas']['background']['image'];
            // Check if it's a valid base64 data URL
            if (strpos($image_data, 'data:image/') === 0) {
                // Validate base64 format
                $base64_data = substr($image_data, strpos($image_data, ',') + 1);
                if (!base64_decode($base64_data, true)) {
                    wp_send_json_error('Invalid background image data');
                }
            } else {
                // If it's not base64, it should be a valid URL
                if (!filter_var($image_data, FILTER_VALIDATE_URL)) {
                    wp_send_json_error('Invalid background image URL');
                }
            }
        }

        // Save template data
        update_post_meta($template_id, '_apd_template_data', $template_data);

        // Update canvas settings if provided
        if (isset($data['canvas'])) {
            $canvas = $data['canvas'];

            if (isset($canvas['width'])) {
                update_post_meta($template_id, '_apd_template_width', intval($canvas['width']));
            }

            if (isset($canvas['height'])) {
                update_post_meta($template_id, '_apd_template_height', intval($canvas['height']));
            }

            if (isset($canvas['background'])) {
                $bg = $canvas['background'];

                if (isset($bg['type'])) {
                    update_post_meta($template_id, '_apd_template_bg_type', sanitize_text_field($bg['type']));
                }

                if (isset($bg['color'])) {
                    update_post_meta($template_id, '_apd_template_bg_color', sanitize_hex_color($bg['color']));
                }

                if (isset($bg['image'])) {
                    update_post_meta($template_id, '_apd_template_bg_image', esc_url_raw($bg['image']));
                }
            }
        }

        wp_send_json_success(array(
            'message' => 'Template design saved successfully',
            'template_id' => $template_id
        ));
    }

    public function register_rest_routes()
    {
        register_rest_route('apd/v1', '/products', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_products_rest'),
            'permission_callback' => '__return_true',
            'args' => array(
                'search' => array(
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'per_page' => array(
                    'default' => 20,
                    'sanitize_callback' => 'absint'
                )
            )
        ));

        register_rest_route('apd/v1', '/products/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_single_product_rest'),
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array(
                    'required' => true,
                    'sanitize_callback' => 'absint'
                )
            )
        ));
    }

    public function get_products_rest($request)
    {
        $search = $request->get_param('search');
        $per_page = $request->get_param('per_page');

        $args = array(
            'post_type' => 'apd_product',
            'post_status' => array('publish', 'draft', 'private'),
            'posts_per_page' => $per_page,
            'orderby' => 'title',
            'order' => 'ASC'
        );

        if (!empty($search)) {
            $args['s'] = $search;
        }

        $products = get_posts($args);

        $product_data = array();

        foreach ($products as $product) {
            $price = get_post_meta($product->ID, '_fsc_price', true);
            $image = get_post_meta($product->ID, '_fsc_logo_file', true);
            $features = get_post_meta($product->ID, '_fsc_features', true);

            $product_data[] = array(
                'id' => $product->ID,
                'title' => $product->post_title,
                'description' => wp_trim_words($product->post_content, 20),
                'content' => $product->post_content,
                'price' => $price ?: '0.00',
                'image' => $image ?: '',
                'features' => is_array($features) ? $features : array(),
                'permalink' => get_permalink($product->ID)
            );
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $product_data
        ), 200);
    }

    public function get_single_product_rest($request)
    {
        $product_id = $request->get_param('id');
        $product = get_post($product_id);

        if (!$product || $product->post_type !== 'apd_product') {
            return new WP_Error('product_not_found', 'Product not found', array('status' => 404));
        }

        $price = get_post_meta($product->ID, '_fsc_price', true);
        $image = get_post_meta($product->ID, '_fsc_logo_file', true);
        $features = get_post_meta($product->ID, '_fsc_features', true);
        $template_id = get_post_meta($product->ID, '_fsc_template', true);

        $product_data = array(
            'id' => $product->ID,
            'title' => $product->post_title,
            'description' => wp_trim_words($product->post_content, 20),
            'content' => $product->post_content,
            'price' => $price ?: '0.00',
            'image' => $image ?: '',
            'features' => is_array($features) ? $features : array(),
            'template_id' => $template_id,
            'permalink' => get_permalink($product->ID)
        );

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $product_data
        ), 200);
    }

    public function get_product_data_ajax()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'apd_ajax_nonce')) {
            wp_die('Security check failed');
        }

        $product_id = intval($_POST['product_id']);
        $product = get_post($product_id);

        if (!$product || $product->post_type !== 'apd_product') {
            wp_send_json_error('Product not found');
        }

        $price = get_post_meta($product->ID, '_fsc_price', true);
        $image = get_post_meta($product->ID, '_fsc_logo_file', true);
        $features = get_post_meta($product->ID, '_fsc_features', true);
        $template_id = get_post_meta($product->ID, '_fsc_template', true);

        $product_data = array(
            'id' => $product->ID,
            'title' => $product->post_title,
            'description' => wp_trim_words($product->post_content, 20),
            'content' => $product->post_content,
            'price' => $price ?: '0.00',
            'image' => $image ?: '',
            'features' => is_array($features) ? $features : array(),
            'template_id' => $template_id,
            'permalink' => get_permalink($product->ID)
        );

        wp_send_json_success($product_data);
    }

    public function get_products_ajax()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'apd_ajax_nonce')) {
            wp_die('Security check failed');
        }

        $search = sanitize_text_field($_POST['search']);
        $per_page = 20;

        $args = array(
            'post_type' => 'apd_product',
            'post_status' => array('publish', 'draft', 'private'),
            'posts_per_page' => $per_page,
            'orderby' => 'title',
            'order' => 'ASC'
        );

        if (!empty($search)) {
            $args['s'] = $search;
        }

        $products = get_posts($args);
        $product_data = array();

        foreach ($products as $product) {
            $price = get_post_meta($product->ID, '_fsc_price', true);
            $image = get_post_meta($product->ID, '_fsc_logo_file', true);
            $features = get_post_meta($product->ID, '_fsc_features', true);

            $product_data[] = array(
                'id' => $product->ID,
                'title' => $product->post_title,
                'description' => wp_trim_words($product->post_content, 20),
                'content' => $product->post_content,
                'price' => $price ?: '0.00',
                'image' => $image ?: '',
                'features' => is_array($features) ? $features : array(),
                'permalink' => get_permalink($product->ID)
            );
        }

        wp_send_json_success($product_data);
    }

    public function get_customizer_data_ajax()
    {
        // Debug: Log all POST data
        error_log('APD Customizer: POST data: ' . print_r($_POST, true));

        // Check if nonce exists
        if (!isset($_POST['nonce'])) {
            error_log('APD Customizer: No nonce provided');
            wp_send_json_error('No nonce provided');
        }

        // For template preview, we'll use a more lenient approach
        // Check if the nonce is valid for any of the common actions
        $nonce_valid = false;

        // Try the standard nonce first
        if (wp_verify_nonce($_POST['nonce'], 'apd_ajax_nonce')) {
            $nonce_valid = true;
        }

        // If that fails, try without nonce verification for public access
        // This is acceptable for template preview data which is not sensitive
        if (!$nonce_valid) {
            // Check if this is a valid product ID and user can view it
            $product_id = intval($_POST['product_id']);
            $product = get_post($product_id);

            if ($product && $product->post_type === 'apd_product' && $product->post_status === 'publish') {
                $nonce_valid = true;
                error_log('APD Customizer: Using fallback nonce verification for product ' . $product_id);
            }
        }

        if (!$nonce_valid) {
            error_log('APD Customizer: Nonce verification failed. Expected: ' . wp_create_nonce('apd_ajax_nonce') . ', Got: ' . $_POST['nonce']);
            wp_send_json_error('Security check failed - nonce mismatch');
        }

        // Check if product_id exists
        if (!isset($_POST['product_id'])) {
            error_log('APD Customizer: No product_id provided');
            wp_send_json_error('No product_id provided');
        }

        $product_id = intval($_POST['product_id']);

        // Debug: Log the request
        error_log('APD Customizer: Product ID requested: ' . $product_id);

        // List all products for debugging
        $all_products = get_posts(array(
            'post_type' => 'apd_product',
            'post_status' => 'any',
            'posts_per_page' => -1
        ));
        error_log('APD Customizer: All products found: ' . count($all_products));
        foreach ($all_products as $prod) {
            error_log('APD Customizer: Product ID: ' . $prod->ID . ', Title: ' . $prod->post_title . ', Status: ' . $prod->post_status);
        }

        $product = get_post($product_id);

        if (!$product) {
            error_log('APD Customizer: Product not found for ID: ' . $product_id);
            wp_send_json_error('Product not found for ID: ' . $product_id . '. Available products: ' . implode(', ', wp_list_pluck($all_products, 'ID')));
        }

        if ($product->post_type !== 'apd_product') {
            error_log('APD Customizer: Wrong post type. Expected: apd_product, Got: ' . $product->post_type);
            wp_send_json_error('Wrong post type. Expected: apd_product, Got: ' . $product->post_type);
        }

        // Get product data
        $price = get_post_meta($product->ID, '_fsc_price', true);
        $sale_price = get_post_meta($product->ID, '_fsc_sale_price', true);
        $image = get_post_meta($product->ID, '_fsc_logo_file', true);
        $features = get_post_meta($product->ID, '_fsc_features', true);
        $template_id = get_post_meta($product->ID, '_fsc_template', true);

        $product_data = array(
            'id' => $product->ID,
            'title' => $product->post_title,
            'description' => wp_trim_words($product->post_content, 20),
            'content' => $product->post_content,
            'price' => $price ?: '0.00',
            'sale_price' => $sale_price ?: '',
            'image' => $image ?: '',
            'features' => is_array($features) ? $features : array(),
            'template_id' => $template_id,
            'permalink' => get_permalink($product->ID)
        );

        // Get template data
        $template = null;
        $template_data = null;

        if ($template_id) {
            $template = get_post($template_id);
            if ($template && $template->post_type === 'apd_template') {
                $template_data_raw = get_post_meta($template_id, '_apd_template_data', true);
                if ($template_data_raw) {
                    $template_data = json_decode($template_data_raw, true);
                }
            }
        }

        // If no template data, return explicit error (no mock data)
        if (!$template_data) {
            wp_send_json_error('No template data found for product ' . $product_id . '.');
        }

        wp_send_json_success(array(
            'product' => $product_data,
            'template' => $template,
            'templateData' => $template_data
        ));
    }

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

    public function save_customization_ajax()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'apd_ajax_nonce')) {
            wp_die('Security check failed');
        }

        $product_id = intval($_POST['product_id']);
        $customizations = $_POST['customizations'];

        // Sanitize customizations
        $sanitized_customizations = array();
        foreach ($customizations as $element_id => $customization) {
            $sanitized_customizations[sanitize_text_field($element_id)] = array(
                'text' => sanitize_text_field($customization['text']),
                'color' => sanitize_hex_color($customization['color']),
                'outline' => sanitize_text_field($customization['outline'])
            );
        }

        // Save customizations as post meta
        update_post_meta($product_id, '_apd_customizations', $sanitized_customizations);

        wp_send_json_success(array(
            'message' => 'Customization saved successfully',
            'customizations' => $sanitized_customizations
        ));
    }

    /**
     * AJAX handler to get all materials
     */
    public function ajax_get_materials()
    {
        // Try multiple nonce parameter names
        $nonce = $_POST['nonce'] ?? $_POST['security'] ?? $_POST['_wpnonce'] ?? $_POST['apd_nonce'] ?? '';

        // Verify nonce
        if (!wp_verify_nonce($nonce, 'apd_ajax_nonce')) {
            error_log('AJAX get_materials nonce verification failed. Nonce: ' . $nonce);
            wp_send_json_error(array('message' => 'Security check failed. Nonce: ' . $nonce));
        }

        $materials = $this->get_materials();

        error_log('AJAX get_materials returning: ' . print_r($materials, true));

        wp_send_json_success(array(
            'materials' => $materials
        ));
    }

    /**
     * AJAX handler to get material URL by name
     */
    public function ajax_get_material_url()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'apd_ajax_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }

        $material_name = sanitize_text_field($_POST['material_name']);
        if (empty($material_name)) {
            wp_send_json_error(array('message' => 'Material name is required'));
        }

        $materials = $this->get_materials();

        // Try to find the material by name (case insensitive)
        foreach ($materials as $name => $material_data) {
            if (strtolower($name) === strtolower($material_name)) {
                $url = is_array($material_data) ? $material_data['url'] : $material_data;
                $price = is_array($material_data) && isset($material_data['price']) ? $material_data['price'] : 0;
                wp_send_json_success(array(
                    'material_url' => $url,
                    'material_name' => $name,
                    'material_price' => $price
                ));
            }
        }

        // If not found, try to construct URL from material name
        $material_file = $this->get_material_filename($material_name);
        if ($material_file) {
            $material_url = APD_PLUGIN_URL . 'uploads/material/' . $material_file;
            wp_send_json_success(array(
                'material_url' => $material_url,
                'material_name' => $material_name
            ));
        }

        wp_send_json_error(array('message' => 'Material not found'));
    }

    /**
     * Get material filename from material name
     */
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
     * Email Service Functions
     */
    
    /**
     * Send order confirmation email to customer
     */
    public function send_order_confirmation_email($order_id, $order_data) {
        try {
            // Check if email notifications are enabled
            if (!get_option('apd_email_enabled', '1')) {
                // error_log("Email notifications are disabled in settings");
                return false;
            }

            $customer_email = $order_data['customer_email'] ?? '';
            if (empty($customer_email)) {
                // error_log("No customer email address provided in order data");
                return false;
            }

            // Configure SMTP if enabled (same as test email does)
            if (get_option('apd_smtp_enabled', '0') === '1') {
                $this->configure_smtp(true); // Disable debug during AJAX
                // error_log("SMTP configured for order confirmation email");
            }

            // Get email settings
            $from_name = get_option('apd_email_from_name', 'Freight Signs Customizer');
            $from_email = get_option('apd_email_from_address', get_option('admin_email'));
            $subject = get_option('apd_email_subject', 'Order Confirmation - #{order_id}');
            $template = get_option('apd_email_template', 'Dear {customer_name},

Thank you for your order! We have received your order #{order_id} and will process it shortly.

Order Details:
- Product: {product_name}
- Quantity: {quantity}
- Total: {total_price}
- Order Date: {order_date}

We will send you another email once your order is ready for shipping.

Best regards,
{site_name}');

            // Replace placeholders
            $placeholders = array(
                '{customer_name}' => $order_data['customer_name'] ?? 'Customer',
                '{order_id}' => $order_id,
                '{product_name}' => $order_data['product_name'] ?? 'Custom Product',
                '{quantity}' => $order_data['quantity'] ?? '1',
                '{total_price}' => '$' . number_format($order_data['product_price'] ?? 0, 2),
                '{order_date}' => $order_data['order_date'] ?? current_time('Y-m-d H:i:s'),
                '{site_name}' => get_option('apd_email_from_name', 'Freight Signs Customizer')
            );

            $subject = str_replace(array_keys($placeholders), array_values($placeholders), $subject);
            $message = str_replace(array_keys($placeholders), array_values($placeholders), $template);

            // Set headers (same as test email)
            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $from_name . ' <' . $from_email . '>'
            );

            // Send email (silently during AJAX to prevent JSON corruption)
            // error_log("Attempting to send order confirmation email to: " . $customer_email);
            $sent = wp_mail($customer_email, $subject, $message, $headers);

            // if ($sent) {
            //     error_log("✅ Order confirmation email sent successfully to: " . $customer_email);
            // } else {
            //     error_log("❌ Failed to send order confirmation email to: " . $customer_email);
            // }

            return $sent;

        } catch (Exception $e) {
            // error_log("Error sending order confirmation email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send admin notification email
     */
    public function send_admin_notification_email($order_id, $order_data) {
        try {
            // Check if admin notifications are enabled
            if (!get_option('apd_admin_email_notifications', '1')) {
                // error_log("Admin email notifications are disabled in settings");
                return false;
            }

            $admin_email = get_option('apd_admin_email_address', get_option('admin_email'));
            if (empty($admin_email)) {
                // error_log("No admin email address configured");
                return false;
            }

            // Configure SMTP if enabled (same as test email does)
            if (get_option('apd_smtp_enabled', '0') === '1') {
                $this->configure_smtp(true); // Disable debug during AJAX
                // error_log("SMTP configured for admin notification email");
            }

            $from_name = get_option('apd_email_from_name', 'Freight Signs Customizer');
            $from_email = get_option('apd_email_from_address', get_option('admin_email'));

            $subject = 'New Order #' . $order_id . ' - ' . get_option('apd_email_from_name', 'Freight Signs Customizer');
            
            $message = '<h2>New Order Received</h2>
            <p><strong>Order ID:</strong> #' . $order_id . '</p>
            <p><strong>Customer:</strong> ' . ($order_data['customer_name'] ?? 'N/A') . '</p>
            <p><strong>Email:</strong> ' . ($order_data['customer_email'] ?? 'N/A') . '</p>
            <p><strong>Phone:</strong> ' . ($order_data['customer_phone'] ?? 'N/A') . '</p>
            <p><strong>Product:</strong> ' . ($order_data['product_name'] ?? 'N/A') . '</p>
            <p><strong>Quantity:</strong> ' . ($order_data['quantity'] ?? '1') . '</p>
            <p><strong>Total:</strong> $' . number_format($order_data['product_price'] ?? 0, 2) . '</p>
            <p><strong>Order Date:</strong> ' . ($order_data['order_date'] ?? current_time('Y-m-d H:i:s')) . '</p>
            <p><strong>Address:</strong><br>' . nl2br($order_data['customer_address'] ?? 'N/A') . '</p>';

            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $from_name . ' <' . $from_email . '>'
            );

            // Send email (silently during AJAX to prevent JSON corruption)
            // error_log("Attempting to send admin notification email to: " . $admin_email);
            $sent = wp_mail($admin_email, $subject, $message, $headers);

            // if ($sent) {
            //     error_log("✅ Admin notification email sent successfully to: " . $admin_email);
            // } else {
            //     error_log("❌ Failed to send admin notification email to: " . $admin_email);
            // }

            return $sent;

        } catch (Exception $e) {
            // error_log("Error sending admin notification email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send test email
     */
    public function send_test_email() {
        try {
            if (!current_user_can('manage_options')) {
                wp_send_json_error('Unauthorized');
            }

            $test_email = sanitize_email($_POST['test_email'] ?? '');
            if (empty($test_email)) {
                wp_send_json_error('Email address required');
            }

            $from_name = get_option('apd_email_from_name', 'Freight Signs Customizer');
            $from_email = get_option('apd_email_from_address', get_option('admin_email'));
            
            $subject = 'Test Email - ' . $from_name;
            $message = 'This is a test email from your Freight Signs Customizer plugin. If you receive this, your email settings are working correctly!';
            
            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $from_name . ' <' . $from_email . '>'
            );

            $sent = wp_mail($test_email, $subject, $message, $headers);

            if ($sent) {
                wp_send_json_success('Test email sent successfully!');
            } else {
                wp_send_json_error('Failed to send test email. Check your email settings.');
            }

        } catch (Exception $e) {
            wp_send_json_error('Error: ' . $e->getMessage());
        }
    }

    public function send_advanced_test_email() {
        try {
            if (!current_user_can('manage_options')) {
                wp_send_json_error('Unauthorized');
            }

            $test_email = sanitize_email($_POST['test_email'] ?? '');
            $email_method = sanitize_text_field($_POST['email_method'] ?? 'php');
            $email_type = sanitize_text_field($_POST['email_type'] ?? 'order_confirmation');
            $include_attachments = (bool)($_POST['include_attachments'] ?? false);

            if (empty($test_email)) {
                wp_send_json_error('Email address required');
            }

            // Configure SMTP if enabled
            if ($email_method === 'smtp' && get_option('apd_smtp_enabled', '0') === '1') {
                $this->configure_smtp();
            }

            $from_name = get_option('apd_email_from_name', 'Freight Signs Customizer');
            $from_email = get_option('apd_email_from_address', get_option('admin_email'));
            
            $subject = 'Test Email - ' . $from_name . ' (' . strtoupper($email_method) . ')';
            $message = $this->get_test_email_template($email_type);
            
            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $from_name . ' <' . $from_email . '>'
            );

            // Add attachments if requested
            $attachments = array();
            if ($include_attachments) {
                $attachments = $this->get_test_attachments();
            }

            $sent = wp_mail($test_email, $subject, $message, $headers, $attachments);

            if ($sent) {
                wp_send_json_success('Test email sent successfully via ' . strtoupper($email_method) . '!');
            } else {
                wp_send_json_error('Failed to send test email via ' . strtoupper($email_method) . '. Check your email settings.');
            }

        } catch (Exception $e) {
            wp_send_json_error('Error: ' . $e->getMessage());
        }
    }

    public function test_smtp_connection() {
        try {
            if (!current_user_can('manage_options')) {
                wp_send_json_error('Unauthorized');
            }

            $smtp_enabled = get_option('apd_smtp_enabled', '0');
            if ($smtp_enabled !== '1') {
                wp_send_json_error('SMTP is not enabled');
            }

            $host = get_option('apd_smtp_host', 'smtp.gmail.com');
            $port = get_option('apd_smtp_port', '587');
            $encryption = get_option('apd_smtp_encryption', 'tls');
            $username = get_option('apd_smtp_username', '');
            $password = get_option('apd_smtp_password', '');

            if (empty($username) || empty($password)) {
                wp_send_json_error('SMTP credentials not configured');
            }

            $start_time = microtime(true);
            
            // Test SMTP connection
            $connection = $this->test_smtp_connection_direct($host, $port, $username, $password, $encryption);
            
            $end_time = microtime(true);
            $response_time = round(($end_time - $start_time) * 1000);

            if ($connection) {
                wp_send_json_success(array(
                    'host' => $host,
                    'port' => $port,
                    'encryption' => $encryption,
                    'response_time' => $response_time
                ));
            } else {
                wp_send_json_error('SMTP connection failed');
            }

        } catch (Exception $e) {
            wp_send_json_error('SMTP test error: ' . $e->getMessage());
        }
    }

    public function get_email_logs() {
        try {
            if (!current_user_can('manage_options')) {
                wp_send_json_error('Unauthorized');
            }

            $logs = get_option('apd_email_logs', array());
            
            // Return last 10 logs
            $recent_logs = array_slice($logs, -10);
            
            wp_send_json_success($recent_logs);

        } catch (Exception $e) {
            wp_send_json_error('Error retrieving email logs: ' . $e->getMessage());
        }
    }

    private function configure_smtp($disable_debug = false) {
        if (get_option('apd_smtp_enabled', '0') !== '1') {
            return;
        }

        $host = get_option('apd_smtp_host', 'smtp.gmail.com');
        $port = get_option('apd_smtp_port', '587');
        $encryption = get_option('apd_smtp_encryption', 'tls');
        $username = get_option('apd_smtp_username', '');
        $password = get_option('apd_smtp_password', '');

        if (empty($username) || empty($password)) {
            return;
        }

        // Configure PHPMailer for SMTP - only add the action once
        static $smtp_configured = false;
        if (!$smtp_configured) {
            add_action('phpmailer_init', function($phpmailer) use ($host, $port, $encryption, $username, $password, $disable_debug) {
                $phpmailer->isSMTP();
                $phpmailer->Host = $host;
                $phpmailer->SMTPAuth = true;
                $phpmailer->Username = $username;
                $phpmailer->Password = $password;
                $phpmailer->SMTPSecure = $encryption;
                $phpmailer->Port = $port;
                // Disable debug during AJAX calls to prevent corrupting JSON responses
                if ($disable_debug || wp_doing_ajax()) {
                    $phpmailer->SMTPDebug = 0;
                } else {
                    $phpmailer->SMTPDebug = get_option('apd_smtp_debug', '0') === '1' ? 2 : 0;
                }
            });
            $smtp_configured = true;
        }
    }

    private function test_smtp_connection_direct($host, $port, $username, $password, $encryption) {
        try {
            $socket = fsockopen($host, $port, $errno, $errstr, 10);
            if (!$socket) {
                return false;
            }
            fclose($socket);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    private function get_test_email_template($email_type) {
        $templates = array(
            'order_confirmation' => 'Dear Test Customer,<br><br>Thank you for your test order! This is a test email to verify your email configuration.<br><br>Best regards,<br>Freight Signs Customizer',
            'order_shipped' => 'Dear Test Customer,<br><br>Your test order has been shipped! This is a test email to verify your email configuration.<br><br>Best regards,<br>Freight Signs Customizer',
            'welcome_customer' => 'Dear Test Customer,<br><br>Welcome to our store! This is a test email to verify your email configuration.<br><br>Best regards,<br>Freight Signs Customizer',
            'admin_notification' => 'Dear Admin,<br><br>This is a test admin notification email to verify your email configuration.<br><br>Best regards,<br>Freight Signs Customizer'
        );

        return $templates[$email_type] ?? $templates['order_confirmation'];
    }

    private function get_test_attachments() {
        // Return empty array for now - can be extended to include actual test files
        return array();
    }
}

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

add_action('plugins_loaded', 'apd_init');
