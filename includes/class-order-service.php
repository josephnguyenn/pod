<?php
/**
 * Order Service Class
 * 
 * Handles all order operations including creation, retrieval, and status management
 * Processes cart items, handles payments, and manages order metadata
 *
 * @package AdvancedProductDesigner
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class APD_Order_Service
{
    /**
     * Cart service instance
     * @var APD_Cart_Service
     */
    private $cart_service;

    /**
     * Email service instance
     * @var APD_Email_Service
     */
    private $email_service;

    /**
     * Constructor
     *
     * @param APD_Cart_Service $cart_service Cart service instance
     * @param APD_Email_Service $email_service Email service instance
     */
    public function __construct($cart_service = null, $email_service = null)
    {
        $this->cart_service = $cart_service ?? new APD_Cart_Service();
        $this->email_service = $email_service ?? new APD_Email_Service();
    }

    /**
     * Create order from cart and customer data
     *
     * @param array $customer_data Customer information
     * @param array $payment_data Payment information
     * @param array $cart_items Optional cart items (uses current cart if not provided)
     * @return int|WP_Error Order ID or error
     */
    public function create_order($customer_data, $payment_data, $cart_items = null)
    {
        // Validate customer data
        $validation = $this->validate_customer_data($customer_data);
        if (is_wp_error($validation)) {
            return $validation;
        }

        // Get cart items
        if (null === $cart_items) {
            $cart_items = $this->cart_service->get_cart();
        }

        if (empty($cart_items)) {
            return new WP_Error('empty_cart', 'Cart is empty');
        }

        // Process cart items and compute total
        $processed_cart = $this->process_cart_items($cart_items);
        $order_total = $processed_cart['total'];
        $cart_items = $processed_cart['items'];

        // Build order metadata
        $order_meta = $this->build_order_metadata($cart_items, $customer_data, $payment_data, $order_total);

        // Create order post
        $order_id = wp_insert_post(array(
            'post_type' => APD_Config::POST_TYPE_ORDER,
            'post_title' => 'Order ' . date('Y-m-d H:i:s'),
            'post_status' => 'apd_pending' // Use prefixed status to match registered statuses
        ));

        if (is_wp_error($order_id) || !$order_id) {
            return new WP_Error('order_creation_failed', 'Unable to create order');
        }

        // Save order metadata
        foreach ($order_meta as $key => $value) {
            update_post_meta($order_id, $key, $value);
        }

        // Clear cart after successful order
        $this->cart_service->clear_cart();

        // Send email notifications
        $this->email_service->send_order_confirmation($order_id, $order_meta);
        $this->email_service->send_admin_notification($order_id, $order_meta);

        error_log('APD Order Service - Order created successfully: #' . $order_id);

        return $order_id;
    }

    /**
     * Validate customer data
     *
     * @param array $customer_data Customer data
     * @return true|WP_Error True if valid, WP_Error otherwise
     */
    private function validate_customer_data($customer_data)
    {
        $required_fields = array('customer_name', 'customer_email', 'customer_phone');

        foreach ($required_fields as $field) {
            if (empty($customer_data[$field])) {
                return new WP_Error('missing_field', sprintf('Missing required field: %s', $field));
            }
        }

        // Validate email
        if (!is_email($customer_data['customer_email'])) {
            return new WP_Error('invalid_email', 'Invalid email address');
        }

        return true;
    }

    /**
     * Process cart items and calculate totals
     *
     * @param array $cart_items Cart items
     * @return array Processed cart with items and total
     */
    private function process_cart_items($cart_items)
    {
        $order_total = 0.0;

        // Normalize cart items to sequential array
        if ($this->is_assoc_array($cart_items)) {
            $cart_items = array_values($cart_items);
        }

        // Calculate totals
        foreach ($cart_items as &$item) {
            $price = isset($item['price']) ? floatval($item['price']) : floatval($item['product_price'] ?? APD_Config::DEFAULT_PRODUCT_PRICE);
            $qty = max(1, intval($item['quantity'] ?? 1));
            $item['total'] = $price * $qty;
            $order_total += $item['total'];
        }
        unset($item);

        // Process base64 images to file URLs
        $cart_items = $this->process_cart_images($cart_items);

        return array(
            'items' => $cart_items,
            'total' => $order_total
        );
    }

    /**
     * Process base64 images in cart items to file URLs
     *
     * @param array $cart_items Cart items
     * @return array Cart items with processed images
     */
    private function process_cart_images($cart_items)
    {
        $image_fields = array('image_url', 'preview_image_url', 'preview_image_png', 'preview_image_svg', 'customization_image_url');

        foreach ($cart_items as &$item) {
            // Process nested customization_data
            if (!empty($item['customization_data'])) {
                if (is_string($item['customization_data'])) {
                    $decoded = json_decode($item['customization_data'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $item['customization_data'] = $decoded;
                    }
                }

                if (is_array($item['customization_data'])) {
                    foreach ($image_fields as $field) {
                        if (!empty($item['customization_data'][$field])) {
                            $item['customization_data'][$field] = $this->convert_base64_to_file($item['customization_data'][$field], 'order-item-' . $field);
                        }
                    }
                }
            }

            // Process top-level image fields
            foreach ($image_fields as $field) {
                if (!empty($item[$field])) {
                    $item[$field] = $this->convert_base64_to_file($item[$field], 'order-item-' . $field);
                }
            }
        }
        unset($item);

        return $cart_items;
    }

    /**
     * Convert base64 image to uploaded file
     *
     * @param string $data Image data (base64 or URL)
     * @param string $prefix Filename prefix
     * @return string File URL or original data
     */
    private function convert_base64_to_file($data, $prefix = 'order-image')
    {
        // Skip if not base64 data
        if (strpos($data, 'data:image') !== 0) {
            return $data;
        }

        // Extract and decode base64 data
        $raw = preg_replace('#^data:image/[^;]+;base64,#', '', $data);
        $decoded = base64_decode($raw);

        if ($decoded === false || strlen($decoded) > APD_Config::MAX_FILE_SIZE) {
            return $data;
        }

        // Upload file
        $filename = $prefix . '-' . time() . '-' . wp_generate_password(6, false, false) . '.png';
        $upload = wp_upload_bits($filename, null, $decoded);

        if (empty($upload['error'])) {
            return $upload['url'];
        }

        return $data;
    }

    /**
     * Build order metadata array
     *
     * @param array $cart_items Cart items
     * @param array $customer_data Customer data
     * @param array $payment_data Payment data
     * @param float $order_total Order total
     * @return array Order metadata
     */
    private function build_order_metadata($cart_items, $customer_data, $payment_data, $order_total)
    {
        $first_item = !empty($cart_items) ? $cart_items[0] : array();

        // Extract variant data if present
        $variant_material = '';
        $variant_size = '';
        if (!empty($first_item['customization_data']['variants'])) {
            $variants = $first_item['customization_data']['variants'];
            $variant_material = $variants['material'] ?? '';
            $variant_size = $variants['size'] ?? '';
        }

        $meta = array(
            // Product Information
            'product_id' => $first_item['product_id'] ?? '',
            'product_name' => $first_item['product_name'] ?? 'Custom Product',
            'product_price' => isset($first_item['price']) ? floatval($first_item['price']) : APD_Config::DEFAULT_PRODUCT_PRICE,
            'quantity' => isset($first_item['quantity']) ? intval($first_item['quantity']) : 1,
            'total_amount' => $order_total,

            // Design Specifications
            'print_color' => $first_item['print_color'] ?? '',
            'vinyl_material' => $first_item['vinyl_material'] ?? $variant_material,
            'material_texture_url' => $first_item['material_texture_url'] ?? '',

            // Template Data
            'text_fields' => $first_item['text_fields'] ?? array(),
            'template_data' => $first_item['template_data'] ?? array(),
            'fields_display' => $first_item['fields_display'] ?? array(),
            'template_fields_array' => $first_item['template_fields_array'] ?? array(),

            // Visual References
            'customization_image_url' => $first_item['customization_image_url'] ?? '',
            'preview_image_url' => $first_item['preview_image_url'] ?? '',
            'preview_image_png' => $first_item['preview_image_png'] ?? '',
            'preview_image_svg' => $first_item['preview_image_svg'] ?? '',

            // Customer Information
            'customer_name' => sanitize_text_field($customer_data['customer_name']),
            'customer_email' => sanitize_email($customer_data['customer_email']),
            'customer_phone' => sanitize_text_field($customer_data['customer_phone']),
            'customer_address' => sanitize_textarea_field($customer_data['customer_address'] ?? ''),

            // Order Details
            'payment_method' => sanitize_text_field($payment_data['payment_method'] ?? APD_Config::PAYMENT_METHOD_PAYPAL),
            'order_date' => current_time('Y-m-d H:i:s'),
            'order_status' => 'apd_pending', // Use prefixed status to match registered statuses

            // Cart Summary
            'cart_items' => wp_json_encode($cart_items),
            'cart_total' => $order_total,

            // Payment Details
            'paypal_order_id' => sanitize_text_field($payment_data['paypal_order_id'] ?? ''),
            'paypal_transaction_id' => sanitize_text_field($payment_data['paypal_transaction_id'] ?? ''),
            'paypal_payer_id' => sanitize_text_field($payment_data['paypal_payer_id'] ?? ''),
            'payment_status' => sanitize_text_field($payment_data['payment_status'] ?? 'completed'),

            // Manufacturing Notes
            'manufacturing_notes' => $this->generate_manufacturing_notes($first_item),
            'production_ready' => true,
        );

        return $meta;
    }

    /**
     * Generate manufacturing notes from customization data
     *
     * @param array $item_data Item customization data
     * @return string Manufacturing notes
     */
    private function generate_manufacturing_notes($item_data)
    {
        $notes = array();

        $notes[] = 'PRODUCT SPECIFICATIONS:';
        $notes[] = '- Product: ' . ($item_data['product_name'] ?? 'Custom Product');
        $notes[] = '- Quantity: ' . ($item_data['quantity'] ?? 1);
        $notes[] = '- Print Color: ' . ($item_data['print_color'] ?? 'Black');
        $notes[] = '- Material: ' . ($item_data['vinyl_material'] ?? 'Standard');

        // Text fields
        if (!empty($item_data['text_fields'])) {
            $notes[] = '';
            $notes[] = 'TEXT CONTENT:';
            foreach ($item_data['text_fields'] as $field_id => $value) {
                if (is_array($value)) {
                    $notes[] = '- ' . ($value['label'] ?? $field_id) . ': ' . ($value['value'] ?? '');
                } else {
                    $notes[] = '- ' . ucwords(str_replace('_', ' ', $field_id)) . ': ' . $value;
                }
            }
        }

        // Template data
        if (!empty($item_data['template_data'])) {
            $notes[] = '';
            $notes[] = 'TEMPLATE ELEMENTS:';
            foreach ($item_data['template_data'] as $field_id => $value) {
                if (is_array($value)) {
                    $notes[] = '- ' . ($value['label'] ?? $field_id) . ': ' . ($value['value'] ?? '');
                } else {
                    $notes[] = '- ' . ucwords(str_replace('_', ' ', $field_id)) . ': ' . $value;
                }
            }
        }

        // Visual references
        if (!empty($item_data['image_url'])) {
            $notes[] = '';
            $notes[] = 'VISUAL REFERENCES:';
            $notes[] = '- Customization Image: ' . $item_data['image_url'];
        }

        $notes[] = '';
        $notes[] = 'ORDER DATE: ' . current_time('Y-m-d H:i:s');
        $notes[] = 'STATUS: Ready for Production';

        return implode("\n", $notes);
    }

    /**
     * Get order by ID
     *
     * @param int $order_id Order ID
     * @return array|WP_Error Order data or error
     */
    public function get_order($order_id)
    {
        $order = get_post($order_id);

        if (!$order || $order->post_type !== APD_Config::POST_TYPE_ORDER) {
            return new WP_Error('order_not_found', 'Order not found');
        }

        $order_meta = get_post_meta($order_id);

        // Normalize cart items
        $raw_cart = get_post_meta($order_id, 'cart_items', true);
        $cart_items = $this->normalize_cart_items($raw_cart);

        // Calculate total if not set
        $cart_total = get_post_meta($order_id, 'cart_total', true);
        if (!is_numeric($cart_total)) {
            $cart_total = $this->calculate_cart_total($cart_items);
        }

        return array(
            'id' => $order_id,
            'date' => $order->post_date,
            'status' => $order->post_status,
            'total' => number_format((float) $cart_total, 2),
            'cart_total' => (float) $cart_total,
            'cart_items' => $cart_items,
            'customer_name' => $this->get_meta_value($order_meta, 'customer_name'),
            'customer_email' => $this->get_meta_value($order_meta, 'customer_email'),
            'customer_phone' => $this->get_meta_value($order_meta, 'customer_phone'),
            'customer_address' => $this->get_meta_value($order_meta, 'customer_address'),
            'payment_method' => $this->get_meta_value($order_meta, 'payment_method'),
            'payment_status' => $this->get_meta_value($order_meta, 'payment_status'),
            'preview_image_url' => $this->get_meta_value($order_meta, 'preview_image_url'),
            'preview_image_png' => $this->get_meta_value($order_meta, 'preview_image_png'),
            'preview_image_svg' => $this->get_meta_value($order_meta, 'preview_image_svg'),
        );
    }

    /**
     * Normalize cart items from various storage formats
     *
     * @param mixed $raw_cart Raw cart data
     * @return array Normalized cart items array
     */
    private function normalize_cart_items($raw_cart)
    {
        $cart_items = $raw_cart;

        // Decode JSON string
        if (is_string($raw_cart)) {
            $decoded = json_decode($raw_cart, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $cart_items = $decoded;
            }
        }

        if (!is_array($cart_items)) {
            return array();
        }

        // Handle nested items structure
        if (isset($cart_items['items']) && is_array($cart_items['items'])) {
            $cart_items = $cart_items['items'];
        }

        // Convert associative array to sequential
        if ($this->is_assoc_array($cart_items)) {
            $cart_items = array_values($cart_items);
        }

        return $cart_items;
    }

    /**
     * Calculate total from cart items
     *
     * @param array $cart_items Cart items
     * @return float Total amount
     */
    private function calculate_cart_total($cart_items)
    {
        $total = 0;

        foreach ($cart_items as $item) {
            if (isset($item['total'])) {
                $total += (float) $item['total'];
            } else {
                $price = (float) ($item['price'] ?? $item['product_price'] ?? 0);
                $qty = (int) ($item['quantity'] ?? 1);
                $total += $price * $qty;
            }
        }

        return $total;
    }

    /**
     * Get meta value with fallback
     *
     * @param array $meta_array Post meta array
     * @param string $key Meta key
     * @return string Meta value
     */
    private function get_meta_value($meta_array, $key)
    {
        return $meta_array[$key][0] ?? $meta_array['_' . $key][0] ?? '';
    }

    /**
     * Check if array is associative
     *
     * @param array $array Array to check
     * @return bool True if associative
     */
    private function is_assoc_array($array)
    {
        if (!is_array($array) || empty($array)) {
            return false;
        }
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Update order status
     *
     * @param int $order_id Order ID
     * @param string $status New status
     * @return bool Success status
     */
    public function update_order_status($order_id, $status)
    {
        if (!APD_Config::is_valid_order_status($status)) {
            return false;
        }

        $result = wp_update_post(array(
            'ID' => $order_id,
            'post_status' => $status
        ));

        if (!is_wp_error($result)) {
            error_log('APD Order Service - Order #' . $order_id . ' status updated to: ' . $status);
            return true;
        }

        return false;
    }

    /**
     * Get thank you page URL
     *
     * @return string Thank you page URL
     */
    public function get_thank_you_url()
    {
        // Prefer slug-based URL
        $url = home_url(get_option('apd_thank_you_url', '/thank-you/'));

        // Fallback to page ID
        if (!get_option('apd_thank_you_url')) {
            $page_id = intval(get_option('apd_thankyou'));
            if ($page_id) {
                $url = get_permalink($page_id);
            }
        }

        // Final fallback - avoid ?page_id= format
        if (!$url || strpos($url, '?page_id=') !== false) {
            $url = home_url('/thank-you/');
        }

        return $url;
    }
}
