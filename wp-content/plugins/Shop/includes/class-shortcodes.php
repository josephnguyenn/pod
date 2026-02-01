<?php

/**
 * Class APD_Shortcodes
 * 
 * Handles registration and rendering of all plugin shortcodes
 *
 * @package AdvancedProductDesigner
 */

if (!defined('ABSPATH')) {
    exit;
}

class APD_Shortcodes
{
    /**
     * Cart service instance
     * @var APD_Cart_Service
     */
    private $cart_service;

    /**
     * Constructor
     * 
     * @param APD_Cart_Service $cart_service The cart service instance
     */
    public function __construct($cart_service)
    {
        $this->cart_service = $cart_service;
    }

    /**
     * Register all shortcodes and related hooks
     */
    public function register()
    {
        add_shortcode('apd_customizer', array($this, 'customizer_shortcode'));
        add_shortcode('apd_product_list', array($this, 'product_list_shortcode'));
        add_shortcode('apd_products_by_company', array($this, 'products_by_company_shortcode'));
        add_shortcode('apd_test', array($this, 'test_shortcode'));
        add_shortcode('apd_debug', array($this, 'debug_shortcode'));
        
        // Cart and Checkout shortcodes
        add_shortcode('apd_cart', array($this, 'shortcode_cart'));
        add_shortcode('apd_cart_count', array($this, 'shortcode_cart_count'));
        add_shortcode('apd_checkout', array($this, 'shortcode_checkout'));
        add_shortcode('apd_thankyou', array($this, 'shortcode_thankyou'));
        add_shortcode('apd_orders', array($this, 'shortcode_orders'));
        
        // Product Detail
        add_shortcode('apd_product_detail', array($this, 'product_detail_shortcode'));
        
        // Render floating cart icon in footer
        add_action('wp_footer', array($this, 'render_floating_cart_icon'));
    }

    /**
     * Shortcode: [apd_customizer product_id="..."]
     */
    public function customizer_shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'product_id' => 0,
        ), $atts);

        // Enqueue frontend scripts
        APD_Assets::enqueue_frontend_scripts();

        // If product_id is not set in shortcode but is in URL, use that
        if (!$atts['product_id'] && get_query_var('customizer')) {
            $atts['product_id'] = get_query_var('customizer');
        }

        // Render the customizer interface
        ob_start();
        $this->render_customizer($atts['product_id']);
        return ob_get_clean();
    }

    /**
     * Render the customizer interface
     * 
     * @param int $product_id
     */
    private function render_customizer($product_id)
    {
        if (!$product_id) {
            echo '<div class="apd-error">No product specified for customization.</div>';
            return;
        }

        $product_id = intval($product_id);
        $template_id = get_post_meta($product_id, '_fsc_template', true);
        
        // Get product details
        $product_data = get_post($product_id);
        $product_price = get_post_meta($product_id, '_fsc_price', true);
        $product_sale_price = get_post_meta($product_id, '_fsc_sale_price', true); // Added sale price support
        $product_material = get_post_meta($product_id, '_fsc_material', true);
        $product_features = get_post_meta($product_id, '_fsc_features', true);
        
        if (!is_array($product_features)) {
            $product_features = array();
        }

        // Get customization options
        $color_opt = get_post_meta($product_id, '_fsc_enable_color_selection', true);
        $outline_opt = get_post_meta($product_id, '_fsc_enable_outline_selection', true);
        
        // Default to enabled if not set
        $enable_color_selection = ($color_opt === '' || $color_opt === '1') ? 1 : 0;
        $enable_outline_selection = ($outline_opt === '' || $outline_opt === '1') ? 1 : 0;

        // Get materials for this template
        $materials = APD_Helpers::get_materials($template_id);
        
        // Get configured colors or use defaults
        // Logic: specific field -> fallback to defaults
        $colors = array();
        // Since we don't have per-product colors yet, use global defaults
        // In future, this could be: get_post_meta($product_id, '_fsc_colors', true);
        
        if (empty($colors)) {
            $colors = APD_Helpers::get_default_colors();
        }

        // Handle Logo (SVG)
        $logo_url = get_post_meta($product_id, '_fsc_logo_file', true);
        $logo_path = '';
        if ($logo_url) {
            $upload_dir = wp_upload_dir();
            $logo_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $logo_url);
        }

        if ($logo_path && file_exists($logo_path)) {
             $product_logo_content = APD_Helpers::get_processed_svg_content($logo_path);
             
             // If manual color replacement is needed (server-side):
             // But client-side JS usually handles this.
             // We just ensure the SVG is clean.
        } else {
             $product_logo_content = '<!-- No logo found -->';
        }

        // Include the customizer template
        // Using include to allow simple variable passing
        include APD_PLUGIN_PATH . 'templates/customizer.php';
    }

    /**
     * Shortcode: [apd_product_list category="..."]
     */
    public function product_list_shortcode($atts)
    {
        // Enqueue frontend scripts
        APD_Assets::enqueue_frontend_scripts();

        // Render
        return $this->render_product_list($atts);
    }

    /**
     * Render product list
     */
    private function render_product_list($atts)
    {
        $atts = shortcode_atts(array(
            'category' => '',
            'limit' => -1
        ), $atts);

        $args = array(
            'post_type' => 'apd_product',
            'posts_per_page' => $atts['limit'],
            'post_status' => 'publish'
        );

        if (!empty($atts['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'apd_category', // Assuming custom tax, or maybe meta?
                    // Actually original code might use specific logic.
                    // Let's assume standard taxonomy for now or simple query.
                    // Checking original implementation: it uses meta_key _fsc_category if simplistic.
                    'field' => 'slug',
                    'terms' => $atts['category']
                )
            );
            // Fallback to meta query if taxonomy not used heavily
            if (!taxonomy_exists('apd_category')) {
                 unset($args['tax_query']);
                 $args['meta_key'] = '_fsc_category';
                 $args['meta_value'] = $atts['category'];
            }
        }

        $query = new WP_Query($args);
        
        ob_start();
        include APD_PLUGIN_PATH . 'templates/product-list.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: [apd_products_by_company company="..."]
     */
    public function products_by_company_shortcode($atts)
    {
        APD_Assets::enqueue_frontend_scripts();
        return $this->render_products_by_company($atts);
    }

    /**
     * Render products filtered by company taxonomy
     */
    private function render_products_by_company($atts)
    {
        $atts = shortcode_atts(array(
            'company' => '',
            'limit' => -1
        ), $atts);

        if (empty($atts['company'])) {
            return '';
        }

        // Get company term
        $company_slug = sanitize_title($atts['company']);
        $term = get_term_by('slug', $company_slug, 'apd_company');

        if (!$term) {
             // Try by name
             $term = get_term_by('name', $atts['company'], 'apd_company');
        }

        if (!$term) {
            return '<p>Company not found.</p>';
        }

        // Query products with this company term
        $args = array(
            'post_type' => 'apd_product',
            'posts_per_page' => $atts['limit'],
            'tax_query' => array(
                 array(
                     'taxonomy' => 'apd_company',
                     'field' => 'term_id',
                     'terms' => $term->term_id
                 )
            )
        );

        $query = new WP_Query($args);

        ob_start();
        include APD_PLUGIN_PATH . 'templates/product-list.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: [apd_test]
     */
    public function test_shortcode()
    {
        // Simple test to check if plugin is active
        return 'APD Plugin is active. Version: ' . APD_VERSION;
    }

    /**
     * Shortcode: [apd_debug]
     */
    public function debug_shortcode()
    {
        if (!current_user_can('manage_options')) {
            return '';
        }
        
        // Output debug info
        ob_start();
        echo '<div style="background:#f5f5f5;padding:20px;border:1px solid #ddd;">';
        echo '<h3>APD Debug</h3>';
        echo '<pre>';
        echo 'Plugin Path: ' . APD_PLUGIN_PATH . "\n";
        echo 'Plugin URL: ' . APD_PLUGIN_URL . "\n";
        echo 'Upload Dir: ' . print_r(wp_upload_dir(), true);
        echo '</pre>';
        echo '</div>';
        return ob_get_clean();
    }

    /**
     * Shortcode: [apd_cart] -- Enhanced Cart
     */
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

    /**
     * Shortcode: [apd_cart_count]
     */
    public function shortcode_cart_count()
    {
        $cart = $this->cart_service->get_cart();
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
        
        $cart = $this->cart_service->get_cart();
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
                width: 70px;
                height: 70px;
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
                font-size: 28px;
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
                    width: 60px;
                    height: 60px;
                }
                .apd-floating-cart-icon {
                    font-size: 24px;
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

    /**
     * Shortcode: [apd_checkout]
     */
    public function shortcode_checkout()
    {
        error_log('🛒 APD: shortcode_checkout() called!');
        
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
        
        // Create inline script to expose apd_ajax immediately
        wp_add_inline_script('jquery', 'window.apd_ajax = ' . wp_json_encode($apd_ajax_data) . ';', 'before');
        
        // Also localize for compatibility (in case scripts need it)
        wp_register_script('apd-checkout-stub', '', array('jquery'), APD_VERSION, true);
        wp_enqueue_script('apd-checkout-stub');
        wp_localize_script('apd-checkout-stub', 'apd_ajax', $apd_ajax_data);
        
        ob_start();
        include APD_PLUGIN_PATH . 'templates/checkout.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: [apd_thankyou]
     */
    public function shortcode_thankyou()
    {
        ob_start();
        include APD_PLUGIN_PATH . 'templates/thankyou.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: [apd_orders]
     */
    public function shortcode_orders()
    {
        ob_start();
        include APD_PLUGIN_PATH . 'templates/orders.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: [apd_product_detail id="..."]
     */
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
                                                <a href="<?php echo esc_url(APD_Helpers::get_product_detail_url($related->ID)); ?>">
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
        
        <!-- Inline CSS for Product Detail -->
        <style>
        .apd-product-detail-wrapper {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out;
            margin-bottom: 2rem;
            font-family: system-ui, -apple-system, sans-serif;
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
            color: white !important;
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
            display: block;
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
            line-height: 1.6;
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
            border-bottom: 1px solid #eee;
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
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            justify-content: center;
        }

        .apd-btn-primary {
            background: #667eea;
            color: white;
        }

        .apd-btn-primary:hover {
            background: #5a6fd8;
            color: white;
            transform: translateY(-2px);
        }

        .apd-btn-secondary {
            background: #6c757d;
            color: white;
        }

        .apd-btn-secondary:hover {
            background: #5a6268;
            color: white;
            transform: translateY(-2px);
        }

        .apd-quantity-selector {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
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
            width: 100%;
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
            display: block;
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
            color: #333;
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
            
            .apd-btn {
                width: 100%;
            }
        }
        </style>
        <?php

        return ob_get_clean();
    }
}
