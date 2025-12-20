<?php
/**
 * Configuration Class
 * 
 * Central configuration management for all plugin constants and settings
 * Replaces hardcoded values throughout the codebase
 *
 * @package AdvancedProductDesigner
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class APD_Config
{
    /**
     * Default product price
     */
    const DEFAULT_PRODUCT_PRICE = 29.99;

    /**
     * Cart expiration time (7 days in seconds)
     */
    const CART_EXPIRATION = 604800;

    /**
     * Session expiration time (7 days in seconds)
     */
    const SESSION_EXPIRATION = 604800;

    /**
     * Maximum file upload size in bytes (10MB)
     */
    const MAX_FILE_SIZE = 10485760;

    /**
     * Allowed image MIME types
     */
    const ALLOWED_IMAGE_TYPES = array(
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/svg+xml'
    );

    /**
     * SVG processing timeout in seconds
     */
    const SVG_PROCESSING_TIMEOUT = 30;

    /**
     * Order statuses
     */
    const ORDER_STATUS_PENDING = 'pending';
    const ORDER_STATUS_PROCESSING = 'processing';
    const ORDER_STATUS_COMPLETED = 'completed';
    const ORDER_STATUS_CANCELLED = 'cancelled';
    const ORDER_STATUS_REFUNDED = 'refunded';

    /**
     * Payment methods
     */
    const PAYMENT_METHOD_PAYPAL = 'paypal';
    const PAYMENT_METHOD_MOCK_PAYPAL = 'mock_paypal';
    const PAYMENT_METHOD_STRIPE = 'stripe';
    const PAYMENT_METHOD_COD = 'cod';

    /**
     * Custom post type names
     */
    const POST_TYPE_PRODUCT = 'apd_product';
    const POST_TYPE_ORDER = 'apd_order';

    /**
     * Taxonomy names
     */
    const TAXONOMY_COMPANY = 'apd_company';
    const TAXONOMY_MATERIAL_CATEGORY = 'material_category';

    /**
     * Transient/Option key prefixes
     */
    const CART_KEY_PREFIX = 'apd_cart_';
    const SESSION_KEY_PREFIX = 'apd_session_';

    /**
     * Nonce actions
     */
    const NONCE_ACTION_AJAX = 'apd_ajax_nonce';
    const NONCE_ACTION_FSC = 'fsc_nonce';

    /**
     * Default colors
     */
    const DEFAULT_COLOR_BLACK = '#000000';
    const DEFAULT_COLOR_WHITE = '#FFFFFF';
    const DEFAULT_COLOR_RED = '#FF0000';
    const DEFAULT_COLOR_BLUE = '#0000FF';
    const DEFAULT_COLOR_GREEN = '#00FF00';
    const DEFAULT_COLOR_YELLOW = '#FFFF00';

    /**
     * Upload directories
     */
    const UPLOAD_DIR_MATERIAL = 'material';
    const UPLOAD_DIR_OBJECT = 'object';
    const UPLOAD_DIR_OBJECT_1 = 'object_1';

    /**
     * Email settings
     */
    const EMAIL_FROM_NAME = 'Advanced Product Designer';
    const EMAIL_SUBJECT_ORDER_CONFIRMATION = 'Order Confirmation';
    const EMAIL_SUBJECT_ORDER_STATUS = 'Order Status Update';

    /**
     * Pagination settings
     */
    const PRODUCTS_PER_PAGE = 12;
    const ORDERS_PER_PAGE = 20;

    /**
     * Cache settings (in seconds)
     */
    const CACHE_PRODUCTS = 3600; // 1 hour
    const CACHE_MATERIALS = 7200; // 2 hours
    const CACHE_SETTINGS = 86400; // 24 hours

    /**
     * Get all order statuses
     *
     * @return array Order statuses
     */
    public static function get_order_statuses()
    {
        return array(
            self::ORDER_STATUS_PENDING => __('Pending', 'advanced-product-designer'),
            self::ORDER_STATUS_PROCESSING => __('Processing', 'advanced-product-designer'),
            self::ORDER_STATUS_COMPLETED => __('Completed', 'advanced-product-designer'),
            self::ORDER_STATUS_CANCELLED => __('Cancelled', 'advanced-product-designer'),
            self::ORDER_STATUS_REFUNDED => __('Refunded', 'advanced-product-designer')
        );
    }

    /**
     * Get all payment methods
     *
     * @return array Payment methods
     */
    public static function get_payment_methods()
    {
        return array(
            self::PAYMENT_METHOD_PAYPAL => __('PayPal', 'advanced-product-designer'),
            self::PAYMENT_METHOD_MOCK_PAYPAL => __('Mock PayPal (Testing)', 'advanced-product-designer'),
            self::PAYMENT_METHOD_STRIPE => __('Stripe', 'advanced-product-designer'),
            self::PAYMENT_METHOD_COD => __('Cash on Delivery', 'advanced-product-designer')
        );
    }

    /**
     * Get default colors
     *
     * @return array Default colors
     */
    public static function get_default_colors()
    {
        return array(
            'black' => self::DEFAULT_COLOR_BLACK,
            'white' => self::DEFAULT_COLOR_WHITE,
            'red' => self::DEFAULT_COLOR_RED,
            'blue' => self::DEFAULT_COLOR_BLUE,
            'green' => self::DEFAULT_COLOR_GREEN,
            'yellow' => self::DEFAULT_COLOR_YELLOW
        );
    }

    /**
     * Get upload base directory
     *
     * @return string Upload base directory path
     */
    public static function get_upload_base_dir()
    {
        $upload_dir = wp_upload_dir();
        return trailingslashit($upload_dir['basedir']) . 'apd';
    }

    /**
     * Get upload base URL
     *
     * @return string Upload base directory URL
     */
    public static function get_upload_base_url()
    {
        $upload_dir = wp_upload_dir();
        return trailingslashit($upload_dir['baseurl']) . 'apd';
    }

    /**
     * Get material upload directory
     *
     * @return string Material upload directory path
     */
    public static function get_material_upload_dir()
    {
        return self::get_upload_base_dir() . '/' . self::UPLOAD_DIR_MATERIAL;
    }

    /**
     * Get object upload directory
     *
     * @return string Object upload directory path
     */
    public static function get_object_upload_dir()
    {
        return self::get_upload_base_dir() . '/' . self::UPLOAD_DIR_OBJECT;
    }

    /**
     * Check if a payment method is valid
     *
     * @param string $method Payment method to check
     * @return bool True if valid
     */
    public static function is_valid_payment_method($method)
    {
        return array_key_exists($method, self::get_payment_methods());
    }

    /**
     * Check if an order status is valid
     *
     * @param string $status Order status to check
     * @return bool True if valid
     */
    public static function is_valid_order_status($status)
    {
        return array_key_exists($status, self::get_order_statuses());
    }

    /**
     * Get plugin version
     *
     * @return string Plugin version
     */
    public static function get_version()
    {
        return '2.0.0';
    }

    /**
     * Get plugin text domain
     *
     * @return string Text domain
     */
    public static function get_text_domain()
    {
        return 'advanced-product-designer';
    }
}
