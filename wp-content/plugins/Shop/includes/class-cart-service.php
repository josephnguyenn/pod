<?php
/**
 * Cart Service Class
 * 
 * Handles all cart operations with database persistence using WordPress transients
 * Replaces PHP session-based cart for scalability and multi-server compatibility
 *
 * @package AdvancedProductDesigner
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class APD_Cart_Service
{
    /**
     * Transient expiration time (7 days in seconds)
     */
    const CART_EXPIRATION = 604800;

    /**
     * Cart key prefix for transients
     */
    const CART_KEY_PREFIX = 'apd_cart_';

    /**
     * Pricing service instance
     * @var APD_Pricing_Service
     */
    private $pricing_service;

    /**
     * Constructor
     */
    public function __construct() {
        $this->pricing_service = new APD_Pricing_Service();
    }

    /**
     * Get unique cart identifier for current user/session
     *
     * @return string Cart identifier
     */
    private function get_cart_key()
    {
        $user_id = get_current_user_id();
        
        if ($user_id > 0) {
            // Logged-in user: use user ID
            return self::CART_KEY_PREFIX . 'user_' . $user_id;
        } else {
            // Guest user: use cookie-based session ID
            if (!isset($_COOKIE['apd_cart_session'])) {
                $session_id = wp_generate_password(32, false);
                
                // Only set cookie if headers haven't been sent yet
                if (!headers_sent()) {
                    setcookie('apd_cart_session', $session_id, time() + self::CART_EXPIRATION, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
                } else {
                    // Headers already sent, log for debugging but continue with session ID
                    error_log('APD Cart Service - Cannot set cookie, headers already sent. Using session ID: ' . substr($session_id, 0, 10) . '...');
                }
            } else {
                $session_id = sanitize_text_field($_COOKIE['apd_cart_session']);
            }
            return self::CART_KEY_PREFIX . 'guest_' . $session_id;
        }
    }

    /**
     * Get cart contents
     *
     * @return array Cart items
     */
    public function get_cart()
    {
        $cart_key = $this->get_cart_key();
        $cart = get_transient($cart_key);

        if (false === $cart || !is_array($cart)) {
            $cart = array();
        }

        // Debug logging
        error_log('APD Cart Service - get_cart(): Key: ' . $cart_key . ', Items: ' . count($cart));

        return $cart;
    }

    /**
     * Save cart contents
     *
     * @param array $cart Cart items
     * @return bool Success status
     */
    public function save_cart($cart)
    {
        if (!is_array($cart)) {
            $cart = array();
        }

        $cart_key = $this->get_cart_key();
        $result = set_transient($cart_key, $cart, self::CART_EXPIRATION);

        // Debug logging
        error_log('APD Cart Service - save_cart(): Key: ' . $cart_key . ', Items: ' . count($cart) . ', Result: ' . ($result ? 'success' : 'failed'));

        return $result;
    }

    /**
     * Add item to cart
     *
     * @param int $product_id Product ID
     * @param int $quantity Quantity
     * @param array $customization_data Customization data
     * @return array Cart item or WP_Error on failure
     */
    public function add_to_cart($product_id, $quantity, $customization_data = array())
    {
        // Validate product
        $product = get_post($product_id);
        if (!$product || $product->post_type !== 'apd_product') {
            return new WP_Error('invalid_product', 'Product not found');
        }

        // Get pricing with quantity-based discounts
        $pricing = $this->calculate_item_price($product_id, $customization_data, max(1, intval($quantity)));
        if (is_wp_error($pricing)) {
            return $pricing;
        }

        // Generate unique cart item ID
        $cart_item_id = 'item_' . $product_id . '_' . time() . '_' . wp_rand(1000, 9999);

        $cart_item = array(
            'id' => $cart_item_id,
            'product_id' => $product_id,
            'product_name' => $product->post_title,
            'price' => $pricing['price'],
            'base_price' => $pricing['base_price'],
            'sale_price' => $pricing['sale_price'],
            'material_price' => $pricing['material_price'],
            'quantity' => max(1, intval($quantity)),
            'total' => $pricing['price'] * max(1, intval($quantity)),
            'customization_data' => $customization_data,
            'added_at' => current_time('Y-m-d H:i:s'),
            'print_color' => isset($customization_data['print_color']) ? $customization_data['print_color'] : '',
            'vinyl_material' => isset($customization_data['vinyl_material']) ? $customization_data['vinyl_material'] : ''
        );

        // Add to cart
        $cart = $this->get_cart();
        $cart[$cart_item_id] = $cart_item;
        $this->save_cart($cart);

        error_log('APD Cart Service - add_to_cart(): Added item ' . $cart_item_id . ', Cart now has ' . count($cart) . ' items');

        return $cart_item;
    }

    /**
     * Calculate item price with materials and quantity-based discounts
     *
     * @param int $product_id Product ID
     * @param array $customization_data Customization data
     * @param int $quantity Quantity (default 1)
     * @return array|WP_Error Pricing data or error
     */
    private function calculate_item_price($product_id, $customization_data, $quantity = 1)
    {
        // Get base price
        $base_price = floatval(get_post_meta($product_id, '_fsc_price', true));
        if (!$base_price) {
            $base_price = 29.99; // Default price
        }

        // Get sale price
        $sale_price = get_post_meta($product_id, '_fsc_sale_price', true);
        $sale_price = !empty($sale_price) ? floatval($sale_price) : null;

        // Use sale price if available
        $product_base_price = ($sale_price && $sale_price > 0) ? $sale_price : $base_price;

        // Get material price
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

        // Determine final base price per unit and variant SKU
        $variant_sku = '';
        if (isset($customization_data['variants']) && isset($customization_data['variants']['price'])) {
            // Variant-specific price (with fallback to product price)
            $variant_price = floatval($customization_data['variants']['price']);
            $price = $variant_price > 0 ? $variant_price : $product_base_price;
            
            // Get variant SKU for variant-specific tiered pricing
            if (isset($customization_data['variants']['sku'])) {
                $variant_sku = $customization_data['variants']['sku'];
            }
        } elseif (isset($customization_data['product_price'])) {
            // Frontend-provided price
            $price = floatval($customization_data['product_price']);
        } else {
            // Calculate from base + material
            $price = $product_base_price + $material_price;
        }

        // Apply tiered pricing based on quantity (with variant support)
        $pricing_result = $this->pricing_service->calculate_tiered_price($product_id, $quantity, $price, $variant_sku);
        $price = $pricing_result['price']; // Use discounted price per unit

        return array(
            'price' => $price,
            'base_price' => $base_price,
            'sale_price' => $sale_price,
            'material_price' => $material_price
        );
    }

    /**
     * Get materials (temporary method - should be moved to MaterialService)
     *
     * @return array Materials data
     */
    private function get_materials()
    {
        // This is a placeholder - in full refactor, this should come from a MaterialService
        global $advanced_product_designer;
        if ($advanced_product_designer && method_exists($advanced_product_designer, 'get_materials')) {
            return $advanced_product_designer->get_materials();
        }
        return array();
    }

    /**
     * Update cart item quantity
     *
     * @param string $cart_item_id Cart item ID
     * @param int $quantity New quantity
     * @return array|WP_Error Updated cart item or error
     */
    public function update_cart_item($cart_item_id, $quantity)
    {
        $quantity = max(1, intval($quantity));
        $cart = $this->get_cart();

        if (!isset($cart[$cart_item_id])) {
            return new WP_Error('item_not_found', 'Cart item not found');
        }

        // Recalculate price with new quantity (for tiered pricing)
        $product_id = $cart[$cart_item_id]['product_id'];
        $customization_data = $cart[$cart_item_id]['customization_data'];
        $price_data = $this->calculate_item_price($product_id, $customization_data, $quantity);
        
        if (!is_wp_error($price_data)) {
            $cart[$cart_item_id]['price'] = $price_data['price'];
        }

        $cart[$cart_item_id]['quantity'] = $quantity;
        $cart[$cart_item_id]['total'] = $cart[$cart_item_id]['price'] * $quantity;

        $this->save_cart($cart);

        return $cart[$cart_item_id];
    }

    /**
     * Remove item from cart
     *
     * @param string $cart_item_id Cart item ID
     * @return bool Success status
     */
    public function remove_cart_item($cart_item_id)
    {
        $cart = $this->get_cart();

        if (!isset($cart[$cart_item_id])) {
            return false;
        }

        unset($cart[$cart_item_id]);
        $this->save_cart($cart);

        return true;
    }

    /**
     * Clear all items from cart
     *
     * @return bool Success status
     */
    public function clear_cart()
    {
        return $this->save_cart(array());
    }

    /**
     * Get cart totals
     *
     * @return array Cart totals (subtotal, count, items)
     */
    public function get_cart_totals()
    {
        $cart = $this->get_cart();
        $total = 0;
        $count = 0;

        foreach ($cart as $item) {
            $total += $item['total'];
            $count += $item['quantity'];
        }

        return array(
            'subtotal' => $total,
            'count' => $count,
            'items' => count($cart)
        );
    }

    /**
     * Get cart count (total items)
     *
     * @return int Total items in cart
     */
    public function get_cart_count()
    {
        $cart = $this->get_cart();
        return count($cart);
    }

    /**
     * Merge guest cart with user cart on login
     *
     * @param int $user_id User ID
     * @return bool Success status
     */
    public function merge_guest_cart_on_login($user_id)
    {
        // Get guest cart
        $guest_key = self::CART_KEY_PREFIX . 'guest_' . (isset($_COOKIE['apd_cart_session']) ? sanitize_text_field($_COOKIE['apd_cart_session']) : '');
        $guest_cart = get_transient($guest_key);

        if (empty($guest_cart) || !is_array($guest_cart)) {
            return false;
        }

        // Get user cart
        $user_key = self::CART_KEY_PREFIX . 'user_' . $user_id;
        $user_cart = get_transient($user_key);
        if (!is_array($user_cart)) {
            $user_cart = array();
        }

        // Merge carts (guest cart takes priority for duplicate items)
        $merged_cart = array_merge($user_cart, $guest_cart);

        // Save merged cart to user
        set_transient($user_key, $merged_cart, self::CART_EXPIRATION);

        // Clear guest cart
        delete_transient($guest_key);

        error_log('APD Cart Service - merge_guest_cart_on_login(): Merged ' . count($guest_cart) . ' guest items with ' . count($user_cart) . ' user items');

        return true;
    }

    /**
     * Clean up expired carts (should be run via cron)
     *
     * @return int Number of carts cleaned
     */
    public static function cleanup_expired_carts()
    {
        global $wpdb;

        $cleaned = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE %s 
                AND option_name NOT LIKE %s",
                '_transient_timeout_' . self::CART_KEY_PREFIX . '%',
                '_transient_' . self::CART_KEY_PREFIX . '%'
            )
        );

        error_log('APD Cart Service - cleanup_expired_carts(): Cleaned ' . $cleaned . ' expired carts');

        return $cleaned;
    }
}
