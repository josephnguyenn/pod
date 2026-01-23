<?php
/**
 * AJAX Handlers
 * 
 * Handles all AJAX endpoints for the plugin
 *
 * @package AdvancedProductDesigner
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class APD_AJAX_Handlers
{
    /**
     * Main plugin instance reference
     * @var AdvancedProductDesigner
     */
    private $plugin;

    /**
     * Cart service instance
     * @var APD_Cart_Service
     */
    private $cart_service;

    /**
     * Constructor
     * 
     * @param AdvancedProductDesigner $plugin Main plugin instance
     */
    public function __construct($plugin)
    {
        $this->plugin = $plugin;
        // Note: cart_service is private, access via plugin instance methods or public properties
    }

    /**
     * Register AJAX hooks
     */
    public function init()
    {
        // Cart AJAX
        add_action('wp_ajax_apd_add_to_cart', array($this, 'ajax_add_to_cart'));
        add_action('wp_ajax_nopriv_apd_add_to_cart', array($this, 'ajax_add_to_cart'));
        add_action('wp_ajax_apd_get_cart', array($this, 'ajax_get_cart'));
        add_action('wp_ajax_nopriv_apd_get_cart', array($this, 'ajax_get_cart'));
        add_action('wp_ajax_apd_update_cart_item', array($this, 'ajax_update_cart_item'));
        add_action('wp_ajax_nopriv_apd_update_cart_item', array($this, 'ajax_update_cart_item'));
        add_action('wp_ajax_apd_remove_cart_item', array($this, 'ajax_remove_cart_item'));
        add_action('wp_ajax_nopriv_apd_remove_cart_item', array($this, 'ajax_remove_cart_item'));
        add_action('wp_ajax_apd_clear_cart', array($this, 'ajax_clear_cart'));
        add_action('wp_ajax_nopriv_apd_clear_cart', array($this, 'ajax_clear_cart'));

        // Settings AJAX
        add_action('wp_ajax_apd_save_settings', array($this, 'ajax_save_settings'));
        
        // Orders AJAX
        add_action('wp_ajax_apd_get_orders', array($this, 'ajax_get_orders'));
        add_action('wp_ajax_apd_create_order', array($this, 'ajax_create_order'));
        
        // Materials AJAX
        add_action('wp_ajax_apd_get_materials', array($this, 'ajax_get_materials'));
        add_action('wp_ajax_nopriv_apd_get_materials', array($this, 'ajax_get_materials'));
        add_action('wp_ajax_apd_get_material_url', array($this, 'ajax_get_material_url'));
        add_action('wp_ajax_nopriv_apd_get_material_url', array($this, 'ajax_get_material_url'));
        
        // Product variants AJAX
        add_action('wp_ajax_apd_get_product_variants', array($this, 'ajax_get_product_variants'));
        add_action('wp_ajax_nopriv_apd_get_product_variants', array($this, 'ajax_get_product_variants'));
        
        // Customization AJAX
        add_action('wp_ajax_save_customization_ajax', array($this, 'save_customization_ajax'));
        add_action('wp_ajax_nopriv_save_customization_ajax', array($this, 'save_customization_ajax'));
        
        // Product data AJAX
        add_action('wp_ajax_apd_get_product_data', array($this, 'get_product_data_ajax'));
        add_action('wp_ajax_nopriv_apd_get_product_data', array($this, 'get_product_data_ajax'));
        add_action('wp_ajax_apd_get_products_ajax', array($this, 'get_products_ajax'));
        add_action('wp_ajax_nopriv_apd_get_products_ajax', array($this, 'get_products_ajax'));
        add_action('wp_ajax_apd_get_customizer_data', array($this, 'get_customizer_data_ajax'));
        add_action('wp_ajax_nopriv_apd_get_customizer_data', array($this, 'get_customizer_data_ajax'));
        
        // Misc AJAX
        add_action('wp_ajax_apd_get_checkout_data', array($this, 'get_checkout_data'));
        add_action('wp_ajax_nopriv_apd_get_checkout_data', array($this, 'get_checkout_data'));
        add_action('wp_ajax_apd_dismiss_dashboard_notice', array($this, 'dismiss_dashboard_notice'));
    }

    /**
     * Save customization
     */
    public function save_customization()
    {
        // Accept multiple nonce keys
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : (isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : (isset($_POST['security']) ? $_POST['security'] : (isset($_POST['apd_nonce']) ? $_POST['apd_nonce'] : '')));
        if ($nonce) {
            $ok = (wp_verify_nonce($nonce, 'fsc_nonce') || wp_verify_nonce($nonce, 'apd_ajax_nonce'));
            if (!$ok && is_user_logged_in()) {
                $ok = true;
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
            'image_url' => esc_url_raw($_POST['image_url']),
            'text_fields' => isset($_POST['text_fields']) ? $_POST['text_fields'] : array(),
            'template_data' => isset($_POST['template_data']) ? $_POST['template_data'] : array()
        );

        if (!session_id()) {
            session_start();
        }
        $_SESSION['fsc_customization'] = $data;

        wp_send_json_success(array('message' => 'Customization saved successfully'));
    }

    /**
     * Save settings
     */
    public function ajax_save_settings()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'apd_nonce')) {
            wp_send_json_error('Security check failed');
        }

        // Save URL settings
        $url_settings = array(
            'apd_cart_url', 'apd_checkout_url', 'apd_products_url',
            'apd_orders_url', 'apd_customizer_url', 'apd_thank_you_url'
        );

        foreach ($url_settings as $setting) {
            if (isset($_POST[$setting])) {
                $value = sanitize_text_field($_POST[$setting]);
                if (!empty($value) && !str_starts_with($value, '/')) {
                    $value = '/' . $value;
                }
                update_option($setting, $value);
            }
        }

        // Save other settings
        $other_settings = array(
            'apd_paypal_client_id', 'apd_paypal_environment',
            'apd_currency', 'apd_paypal_test_mode'
        );

        foreach ($other_settings as $setting) {
            if (isset($_POST[$setting])) {
                update_option($setting, sanitize_text_field($_POST[$setting]));
            }
        }

        // Save email settings
        $email_settings = array(
            'apd_email_enabled', 'apd_email_from_name', 'apd_email_from_address',
            'apd_email_subject', 'apd_email_template', 'apd_admin_email_notifications',
            'apd_admin_email_address', 'apd_smtp_enabled', 'apd_smtp_host',
            'apd_smtp_port', 'apd_smtp_encryption', 'apd_smtp_username',
            'apd_smtp_password', 'apd_smtp_from_email', 'apd_smtp_from_name',
            'apd_smtp_debug', 'apd_email_html_enabled', 'apd_email_footer',
            'apd_email_headers', 'apd_email_attachments', 'apd_email_reply_to',
            'apd_email_cc', 'apd_email_bcc', 'apd_email_delay',
            'apd_email_retry_failed', 'apd_email_max_retries'
        );

        foreach ($email_settings as $setting) {
            if (isset($_POST[$setting])) {
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

    /**
     * Get orders
     */
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

        usort($orders, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        wp_send_json_success(array('orders' => $orders));
    }

    /**
     * Create order
     */
    public function ajax_create_order()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'apd_ajax_nonce')) {
            wp_send_json_error('Security check failed');
        }

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

    /**
     * Add to cart
     */
    public function ajax_add_to_cart()
    {
        error_log('APD Add to Cart: Started');
        error_log('APD Add to Cart POST data: ' . print_r($_POST, true));
        
        // Verify nonce (optional for guest checkout)
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        if ($nonce && !empty($nonce)) {
            if (!wp_verify_nonce($nonce, APD_Config::NONCE_ACTION_AJAX) && !wp_verify_nonce($nonce, APD_Config::NONCE_ACTION_FSC)) {
                error_log('APD Add to Cart: Nonce verification failed');
                wp_send_json_error('Security check failed');
                return;
            }
            error_log('APD Add to Cart: Nonce verified successfully');
        } else {
            error_log('APD Add to Cart: No nonce provided, allowing guest access');
        }

        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        $customization_data = isset($_POST['customization_data']) ? $_POST['customization_data'] : array();

        error_log('APD Add to Cart: product_id = ' . $product_id);
        error_log('APD Add to Cart: quantity = ' . $quantity);

        $result = $this->cart_service->add_to_cart($product_id, $quantity, $customization_data);
        
        if (is_wp_error($result)) {
            error_log('APD Add to Cart: Error - ' . $result->get_error_message());
            wp_send_json_error($result->get_error_message());
            return;
        }

        $cart_totals = $this->cart_service->get_cart_totals();
        
        error_log('APD Add to Cart: Item added successfully. Cart now has ' . $cart_totals['items'] . ' items');

        wp_send_json_success(array(
            'message' => 'Product added to cart',
            'cart_item' => $result,
            'cart_count' => $cart_totals['items']
        ));
    }

    /**
     * Get cart
     */
    public function ajax_get_cart()
    {
        error_log('APD Get Cart: Started');
        
        $cart = $this->cart_service->get_cart();
        $totals = $this->cart_service->get_cart_totals();
        
        error_log('APD Get Cart: Cart count: ' . $totals['items']);
        error_log('APD Get Cart: Total: ' . $totals['subtotal']);

        wp_send_json_success(array(
            'cart' => $cart,
            'total' => $totals['subtotal'],
            'count' => $totals['count']
        ));
    }

    /**
     * Update cart item
     */
    public function ajax_update_cart_item()
    {
        if (!wp_verify_nonce($_POST['nonce'], APD_Config::NONCE_ACTION_AJAX)) {
            wp_send_json_error('Security check failed');
        }

        $cart_item_id = sanitize_text_field($_POST['cart_item_id']);
        $quantity = intval($_POST['quantity']);

        if ($quantity < 1) {
            wp_send_json_error('Invalid quantity');
        }

        $result = $this->cart_service->update_cart_item($cart_item_id, $quantity);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
            return;
        }

        wp_send_json_success(array(
            'message' => 'Cart updated',
            'cart_item' => $result
        ));
    }

    /**
     * Remove cart item
     */
    public function ajax_remove_cart_item()
    {
        if (!wp_verify_nonce($_POST['nonce'], APD_Config::NONCE_ACTION_AJAX)) {
            wp_send_json_error('Security check failed');
        }

        $cart_item_id = sanitize_text_field($_POST['cart_item_id']);
        $result = $this->cart_service->remove_cart_item($cart_item_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
            return;
        }

        wp_send_json_success(array('message' => 'Item removed from cart'));
    }

    /**
     * Clear cart
     */
    public function ajax_clear_cart()
    {
        $this->cart_service->clear_cart();
        wp_send_json_success(array('message' => 'Cart cleared'));
    }

    /**
     * Get materials
     */
    public function ajax_get_materials()
    {
        $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
        $materials = APD_Helpers::get_materials($template_id);
        
        // Format materials for response
        $formatted_materials = array();
        foreach ($materials as $name => $data) {
            $formatted_materials[] = array(
                'name' => $name,
                'url' => is_array($data) ? $data['url'] : $data,
                'price' => is_array($data) && isset($data['price']) ? $data['price'] : 0,
                'category' => is_array($data) && isset($data['category']) ? $data['category'] : 'Uncategorized'
            );
        }
        
        wp_send_json_success($formatted_materials);
    }

    /**
     * Get material URL
     */
    public function ajax_get_material_url()
    {
        if (!isset($_POST['material_name'])) {
            wp_send_json_error('Material name is required');
        }
        
        $material_name = sanitize_text_field($_POST['material_name']);
        $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
        
        $materials = APD_Helpers::get_materials($template_id);
        
        if (isset($materials[$material_name])) {
            $material_data = $materials[$material_name];
            $url = is_array($material_data) ? $material_data['url'] : $material_data;
            
            wp_send_json_success(array(
                'url' => $url,
                'name' => $material_name
            ));
        } else {
            wp_send_json_error('Material not found');
        }
    }

    /**
     * Get product variants
     */
    public function ajax_get_product_variants()
    {
        if (!isset($_POST['product_id'])) {
            wp_send_json_error('Product ID is required');
        }
        
        $product_id = intval($_POST['product_id']);
        $variants = get_post_meta($product_id, '_apd_variants', true);
        
        if (!$variants || !isset($variants['enabled']) || !$variants['enabled']) {
            wp_send_json_success(array(
                'enabled' => false,
                'message' => 'Variants not enabled for this product'
            ));
            return;
        }
        
        wp_send_json_success(array(
            'enabled' => true,
            'size_options' => isset($variants['size_options']) ? $variants['size_options'] : array(),
            'material_options' => isset($variants['material_options']) ? $variants['material_options'] : array(),
            'combinations' => isset($variants['combinations']) ? $variants['combinations'] : array()
        ));
    }

    /**
     * Save customization AJAX
     */
    public function save_customization_ajax()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'apd_ajax_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $customization_data = isset($_POST['customization_data']) ? $_POST['customization_data'] : array();
        
        // Save to session
        if (!session_id()) {
            session_start();
        }
        $_SESSION['apd_customization'] = $customization_data;
        
        wp_send_json_success(array('message' => 'Customization saved'));
    }

    /**
     * Get product data AJAX
     */
    public function get_product_data_ajax()
    {
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

    /**
     * Get products AJAX
     */
    public function get_products_ajax()
    {
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

    /**
     * Get customizer data AJAX
     */
    public function get_customizer_data_ajax()
    {
        error_log('APD Customizer: POST data: ' . print_r($_POST, true));

        if (!isset($_POST['nonce'])) {
            error_log('APD Customizer: No nonce provided');
            wp_send_json_error('No nonce provided');
        }

        $nonce_valid = false;

        if (wp_verify_nonce($_POST['nonce'], 'apd_ajax_nonce')) {
            $nonce_valid = true;
        }

        if (!$nonce_valid) {
            $product_id = intval($_POST['product_id']);
            $product = get_post($product_id);

            if ($product && $product->post_type === 'apd_product' && $product->post_status === 'publish') {
                $nonce_valid = true;
                error_log('APD Customizer: Using fallback nonce verification for product ' . $product_id);
            }
        }

        if (!$nonce_valid) {
            error_log('APD Customizer: Nonce verification failed');
            wp_send_json_error('Security check failed');
        }

        if (!isset($_POST['product_id'])) {
            error_log('APD Customizer: No product_id provided');
            wp_send_json_error('No product_id provided');
        }

        $product_id = intval($_POST['product_id']);
        error_log('APD Customizer: Product ID requested: ' . $product_id);

        $product = get_post($product_id);

        if (!$product) {
            error_log('APD Customizer: Product not found for ID: ' . $product_id);
            wp_send_json_error('Product not found');
        }

        if ($product->post_type !== 'apd_product') {
            error_log('APD Customizer: Wrong post type');
            wp_send_json_error('Wrong post type');
        }

        // Get product data
        $price = get_post_meta($product->ID, '_fsc_price', true);
        $sale_price = get_post_meta($product->ID, '_fsc_sale_price', true);
        $logo_id = get_post_meta($product->ID, '_fsc_logo_id', true);
        $logo_file = get_post_meta($product->ID, '_fsc_logo_file', true);
        
        $image = '';
        if ($logo_id) {
            $image = wp_get_attachment_url($logo_id);
        }
        if (!$image && $logo_file) {
            $image = $logo_file;
        }
        
        $features = get_post_meta($product->ID, '_fsc_features', true);
        $template_id = get_post_meta($product->ID, '_fsc_template', true);

        $logo_content = '';
        if ($image) {
            $upload_dir = wp_upload_dir();
            $logo_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $image);
            
            if (!file_exists($logo_path)) {
                $logo_path = str_replace(site_url(), ABSPATH, $image);
            }
            
            if (file_exists($logo_path)) {
                // Access via plugin instance public method
                if (method_exists($this->plugin, 'get_processed_svg_content')) {
                    $logo_content = $this->plugin->get_processed_svg_content($logo_path);
                } else {
                    $logo_content = file_get_contents($logo_path);
                }
            }
        }
        
        $product_data = array(
            'id' => $product->ID,
            'title' => $product->post_title,
            'description' => wp_trim_words($product->post_content, 20),
            'content' => $product->post_content,
            'price' => $price ?: '0.00',
            'sale_price' => $sale_price ?: '',
            'image' => $image ?: '',
            'logo_content' => $logo_content,
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

        if (!$template_data) {
            wp_send_json_error('No template data found for product ' . $product_id);
        }

        wp_send_json_success(array(
            'product' => $product_data,
            'template' => $template,
            'templateData' => $template_data
        ));
    }

    /**
     * Get checkout data
     */
    public function get_checkout_data()
    {
        // Access cart_service via plugin instance public getter
        $cart_service = $this->plugin->get_cart_service();
        if (!$cart_service) {
            wp_send_json_error('Cart service not available');
            return;
        }
        $cart = $cart_service->get_cart();
        $totals = $cart_service->get_cart_totals();
        
        wp_send_json_success(array(
            'cart' => $cart,
            'totals' => $totals
        ));
    }

    /**
     * Dismiss dashboard notice
     */
    public function dismiss_dashboard_notice()
    {
        check_ajax_referer('apd_dismiss_notice', 'nonce');
        update_option('apd_dashboard_notice_dismissed', true);
        wp_send_json_success();
    }
}
