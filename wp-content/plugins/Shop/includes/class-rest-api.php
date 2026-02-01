<?php
/**
 * REST API Handler
 * 
 * Handles REST API endpoints for products
 *
 * @package AdvancedProductDesigner
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class APD_REST_API
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
     * Register hooks
     */
    public function init()
    {
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }

    /**
     * Register REST routes
     */
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

    /**
     * Get products via REST
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
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

    /**
     * Get single product via REST
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
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
}
