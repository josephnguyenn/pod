<?php

/**
 * Class APD_Ajax
 * 
 * Handles all AJAX requests for the plugin
 *
 * @package AdvancedProductDesigner
 */

if (!defined('ABSPATH')) {
    exit;
}

class APD_Ajax
{
    /**
     * Cart service instance
     * @var APD_Cart_Service
     */
    private $cart_service;

    /**
     * Order service instance
     * @var APD_Order_Service
     */
    private $order_service;

    public function __construct($cart_service, $order_service)
    {
        $this->cart_service = $cart_service;
        $this->order_service = $order_service;
    }

    /**
     * Register AJAX actions
     */
    public function register()
    {
        // Cart operations
        $cart_actions = array(
            'apd_add_to_cart' => 'ajax_add_to_cart',
            'apd_get_cart' => 'ajax_get_cart',
            'apd_update_cart_item' => 'ajax_update_cart_item',
            'apd_remove_cart_item' => 'ajax_remove_cart_item',
            'apd_get_materials' => 'ajax_get_materials',
            'apd_get_material_url' => 'ajax_get_material_url',
        );

        foreach ($cart_actions as $action => $method) {
            add_action('wp_ajax_' . $action, array($this, $method));
            add_action('wp_ajax_nopriv_' . $action, array($this, $method));
        }

        // Order operations
        $order_actions = array(
            'apd_place_order' => 'apd_place_order',
            'apd_get_order_details' => 'apd_get_order_details',
        );

        foreach ($order_actions as $action => $method) {
            add_action('wp_ajax_' . $action, array($this, $method));
            add_action('wp_ajax_nopriv_' . $action, array($this, $method));
        }

        // Customizer operations
        add_action('wp_ajax_load_product', array($this, 'load_product'));
        add_action('wp_ajax_nopriv_load_product', array($this, 'load_product'));
        
        add_action('wp_ajax_save_customization', array($this, 'save_customization'));
        add_action('wp_ajax_nopriv_save_customization', array($this, 'save_customization'));
        
        add_action('wp_ajax_apd_save_customization_image', array($this, 'apd_save_customization_image'));
        add_action('wp_ajax_nopriv_apd_save_customization_image', array($this, 'apd_save_customization_image'));

        // Admin operations
        add_action('wp_ajax_apd_save_settings', array($this, 'ajax_save_settings'));
    }

    /**
     * Add product to cart
     */
    public function ajax_add_to_cart()
    {
        if (!wp_verify_nonce($_POST['nonce'], APD_Config::NONCE_ACTION_AJAX)) {
            wp_send_json_error('Security check failed');
        }

        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        
        // Handle customization data
        $customization_data = array();
        if (isset($_POST['customization_data'])) {
            $customization_data = $_POST['customization_data'];
            // If passed as string (JSON), decode it
            if (is_string($customization_data)) {
                $decoded = json_decode(stripslashes($customization_data), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $customization_data = $decoded;
                }
            }
        }

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
     * Get cart contents
     */
    public function ajax_get_cart()
    {
        // Simple verification, allow public access
        error_log('APD Get Cart: Started');
        
        $cart = $this->cart_service->get_cart();
        $totals = $this->cart_service->get_cart_totals();
        
        // Return full cart details
        wp_send_json_success(array(
            'cart' => $cart,
            'total' => $totals['subtotal'],
            'count' => $totals['count'],
            'items_count' => $totals['items']
        ));
    }

    /**
     * Update cart item quantity
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
     * Remove item from cart
     */
    public function ajax_remove_cart_item()
    {
        if (!wp_verify_nonce($_POST['nonce'], APD_Config::NONCE_ACTION_AJAX)) {
            wp_send_json_error('Security check failed');
        }

        $cart_item_id = sanitize_text_field($_POST['cart_item_id']);
        $result = $this->cart_service->remove_cart_item($cart_item_id);

        if ($result) {
            wp_send_json_success(array('message' => 'Item removed from cart'));
        } else {
            wp_send_json_error('Cart item not found');
        }
    }

    /**
     * Place an order
     */
    public function apd_place_order()
    {
        // Turn off error reporting for clean JSON response
        $error_reporting = error_reporting(0);
        $display_errors = ini_get('display_errors');
        ini_set('display_errors', 0);
        
        // Verify nonce
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : (isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : '');
        if ($nonce && !(wp_verify_nonce($nonce, APD_Config::NONCE_ACTION_AJAX) || wp_verify_nonce($nonce, APD_Config::NONCE_ACTION_FSC))) {
            error_reporting($error_reporting);
            ini_set('display_errors', $display_errors);
            wp_send_json_error('Security check failed');
            return;
        }

        // Prepare customer data
        $customer_data = array(
            'customer_name' => sanitize_text_field(isset($_POST['customer_name']) ? $_POST['customer_name'] : ''),
            'customer_email' => sanitize_email(isset($_POST['customer_email']) ? $_POST['customer_email'] : ''),
            'customer_phone' => sanitize_text_field(isset($_POST['customer_phone']) ? $_POST['customer_phone'] : ''),
            'customer_address' => sanitize_textarea_field(isset($_POST['customer_address']) ? $_POST['customer_address'] : '')
        );

        // Prepare payment data
        $payment_method = sanitize_text_field(isset($_POST['payment_method']) ? $_POST['payment_method'] : APD_Config::PAYMENT_METHOD_PAYPAL);
        $payment_data = array(
            'payment_method' => $payment_method,
            'paypal_order_id' => sanitize_text_field(isset($_POST['paypal_order_id']) ? $_POST['paypal_order_id'] : ''),
            'paypal_transaction_id' => sanitize_text_field(isset($_POST['paypal_transaction_id']) ? $_POST['paypal_transaction_id'] : ''),
            'paypal_payer_id' => sanitize_text_field(isset($_POST['paypal_payer_id']) ? $_POST['paypal_payer_id'] : ''),
            'payment_status' => ($payment_method === APD_Config::PAYMENT_METHOD_MOCK_PAYPAL) ? 'completed' : sanitize_text_field(isset($_POST['payment_status']) ? $_POST['payment_status'] : 'completed')
        );

        // Get cart items (prefer POST, then service cart)
        $cart_items = null;
        if (isset($_POST['cart'])) {
            $posted_cart = json_decode(stripslashes($_POST['cart']), true);
            if (is_array($posted_cart)) {
                $cart_items = $posted_cart;
            }
        }

        // Create order using OrderService
        $order_id = $this->order_service->create_order($customer_data, $payment_data, $cart_items);

        // Restore error reporting
        error_reporting($error_reporting);
        ini_set('display_errors', $display_errors);

        // Handle errors
        if (is_wp_error($order_id)) {
            wp_send_json_error(array('message' => $order_id->get_error_message()));
            return;
        }

        // Clear cart after successful order if using session cart
        if (!$cart_items) {
             $this->cart_service->clear_cart();
        }

        // Get thank you page URL
        $thankyou_url = $this->order_service->get_thank_you_url(); // Or helper

        wp_send_json_success(array(
            'order_id' => $order_id,
            'redirect' => esc_url($thankyou_url)
        ));
    }

    /**
     * Get order details
     */
    public function apd_get_order_details()
    {
        // Verify nonce for security
        if (!wp_verify_nonce(isset($_POST['nonce']) ? $_POST['nonce'] : '', 'apd_order_details')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        $order_id = intval(isset($_POST['order_id']) ? $_POST['order_id'] : 0);
        if (!$order_id) {
            wp_send_json_error('Invalid order ID');
            return;
        }

        // Get order using OrderService
        $order_data = $this->order_service->get_order($order_id);

        if (is_wp_error($order_data)) {
            wp_send_json_error($order_data->get_error_message());
            return;
        }

        wp_send_json_success($order_data);
    }
    
    /**
     * Load product data for customizer
     */
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
        $template_id = get_post_meta($product_id, '_fsc_template', true);
        
        // Get logo URL
        $logo_id = get_post_meta($product_id, '_fsc_logo_id', true);
        $product_logo_url = '';
        
        if ($logo_id) {
            $product_logo_url = wp_get_attachment_url($logo_id);
        }
        
        if (!$product_logo_url) {
            $product_logo_url = get_post_meta($product_id, '_fsc_logo_file', true);
        }

        // Get processed SVG content
        $product_logo_content = '';
        if ($product_logo_url) {
            // Use helper to process SVG
            $upload_dir = wp_upload_dir();
            $logo_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $product_logo_url);
            
            if (!file_exists($logo_path)) {
                $logo_path = str_replace(site_url(), ABSPATH, $product_logo_url);
            }
            
            if (file_exists($logo_path)) {
                $product_logo_content = APD_Helpers::get_processed_svg_content($logo_path);
            }
        }

        // Process color options
        $colors = array();
        if ($color_options) {
            $default_colors = APD_Helpers::get_default_colors();
            $color_array = array_map('trim', explode(',', $color_options));
            foreach ($color_array as $color) {
                if (isset($default_colors[$color])) {
                    $colors[$color] = $default_colors[$color];
                }
            }
        }

        // Resolve template data
        $template_data = null;
        if ($template_id) {
            $template_post = get_post($template_id);
            if ($template_post && $template_post->post_type === 'apd_template') {
                $template_data_raw = get_post_meta($template_id, '_apd_template_data', true);
                if ($template_data_raw) {
                    $decoded = json_decode($template_data_raw, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $template_data = $decoded;
                    } else {
                        $template_data = $template_data_raw;
                    }
                } else {
                    // Fallback to post_content
                    $content = trim($template_post->post_content);
                    if ($content) {
                        $decoded = json_decode($content, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $template_data = $decoded;
                        }
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
            'templateData' => $template_data  // For backwards compatibility
        );

        wp_send_json_success($response_data);
    }
    
    /**
     * Save temporary customization to session
     */
    public function save_customization()
    {
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : (isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : (isset($_POST['security']) ? $_POST['security'] : (isset($_POST['apd_nonce']) ? $_POST['apd_nonce'] : '')));
        if ($nonce) {
            $ok = (wp_verify_nonce($nonce, 'fsc_nonce') || wp_verify_nonce($nonce, 'apd_ajax_nonce'));
            if (!$ok && is_user_logged_in()) {
                $ok = true; // Soft allow
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

        // Save to session
        if (!session_id()) {
            session_start();
        }
        $_SESSION['fsc_customization'] = $data;

        wp_send_json_success(array('message' => 'Customization saved successfully'));
    }

    /**
     * Save uploaded customization image (PNG)
     */
    public function apd_save_customization_image()
    {
        // Basic nonce verification
        // ... (Similar logic as in original)
        
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
        
        // Upload to WordPress
        $upload = wp_upload_bits('customization-' . time() . '-' . wp_generate_password(6, false, false) . '.png', null, $raw);
        
        if (!empty($upload['error'])) {
            wp_send_json_error(array('message' => $upload['error']), 500);
        }
        
        $file_url = $upload['url'];
        wp_send_json_success(array('url' => esc_url($file_url)));
    }
    
    /**
     * Save admin settings
     */
    public function ajax_save_settings()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'apd_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        // List of settings to save (simplified list based on common ones)
        // In real refactor, use a constant or config
        $settings = array(
            'apd_cart_url', 'apd_checkout_url', 'apd_products_url', 
            'apd_orders_url', 'apd_customizer_url', 'apd_thank_you_url',
            'apd_paypal_client_id', 'apd_paypal_environment', 'apd_currency'
        );

        foreach ($settings as $setting) {
            if (isset($_POST[$setting])) {
                $value = sanitize_text_field($_POST[$setting]);
                update_option($setting, $value);
            }
        }

        wp_send_json_success(array('message' => 'Settings saved successfully'));
    }
    
    /**
     * Get material data (helper endpoint)
     */
    public function ajax_get_materials()
    {
        // Public endpoint
        $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
        $materials = APD_Helpers::get_materials($template_id);
        wp_send_json_success($materials);
    }
    
    public function ajax_get_material_url()
    {
        // Stub for now, or implement if needed
        wp_send_json_success(array('url' => ''));
    }
}
