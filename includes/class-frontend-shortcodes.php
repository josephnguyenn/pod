<?php
/**
 * Frontend Shortcodes Handler
 * 
 * Handles all frontend shortcodes rendering
 *
 * @package AdvancedProductDesigner
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class APD_Frontend_Shortcodes
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
     * Register all shortcodes
     */
    public function init()
    {
        add_shortcode('apd_customizer', array($this, 'customizer_shortcode'));
        add_shortcode('apd_product_list', array($this, 'product_list_shortcode'));
        add_shortcode('apd_products_by_company', array($this, 'products_by_company_shortcode'));
        add_shortcode('apd_product_detail', array($this, 'product_detail_shortcode'));
        add_shortcode('apd_cart', array($this, 'shortcode_cart'));
        add_shortcode('apd_cart_count', array($this, 'shortcode_cart_count'));
        add_shortcode('apd_checkout', array($this, 'shortcode_checkout'));
        add_shortcode('apd_thankyou', array($this, 'shortcode_thankyou'));
        add_shortcode('apd_thank_you', array($this, 'shortcode_thankyou'));
        add_shortcode('apd_orders', array($this, 'shortcode_orders'));
        
        // Render floating cart icon
        add_action('wp_footer', array($this, 'render_floating_cart_icon'));
    }

    /**
     * Customizer shortcode
     */
    public function customizer_shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'product_id' => 0
        ), $atts);

        error_log('APD Shortcode: Called with atts: ' . print_r($atts, true));
        error_log('APD Shortcode: Product ID from shortcode: ' . $atts['product_id']);

        $this->plugin->enqueue_frontend_scripts();

        ob_start();
        $this->plugin->render_customizer($atts['product_id']);
        return ob_get_clean();
    }

    /**
     * Product list shortcode
     */
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

        $this->plugin->enqueue_frontend_scripts();

        ob_start();
        $this->plugin->render_product_list($atts);
        return ob_get_clean();
    }

    /**
     * Products by company shortcode
     */
    public function products_by_company_shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'company' => '',
            'show_title' => 'true',
            'show_description' => 'true',
            'show_price' => 'true',
            'show_sale' => 'true',
            'columns' => '3',
            'items_per_page' => '12',
            'hide_header' => 'false'
        ), $atts);

        $this->plugin->enqueue_frontend_scripts();

        ob_start();
        $this->plugin->render_products_by_company($atts);
        return ob_get_clean();
    }

    /**
     * Product detail shortcode
     */
    public function product_detail_shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'product_id' => 0
        ), $atts);

        $product_id = intval($atts['product_id']);
        
        if (!$product_id && isset($_GET['product_id'])) {
            $product_id = intval($_GET['product_id']);
        }

        ob_start();
        
        if ($product_id) {
            $template_path = APD_PLUGIN_PATH . 'templates/product-detail-page.php';
            if (file_exists($template_path)) {
                include $template_path;
            }
        } else {
            echo '<p>No product specified.</p>';
        }
        
        return ob_get_clean();
    }

    /**
     * Cart shortcode
     */
    public function shortcode_cart()
    {
        ob_start();
        ?>
        <div class="apd-cart-page">
            <div class="apd-cart-header">
                <h2>Your Cart</h2>
                <div class="apd-cart-summary">
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
        if (typeof apd_ajax === 'undefined') {
            window.apd_ajax = {
                ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
                nonce: '<?php echo wp_create_nonce('apd_ajax_nonce'); ?>',
                cart_url: '<?php echo home_url('/cart/'); ?>'
            };
        }
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Cart count shortcode
     */
    public function shortcode_cart_count()
    {
        // Access cart_service via plugin instance public getter
        $cart_service = $this->plugin->get_cart_service();
        if (!$cart_service) {
            return '0';
        }
        $cart = $cart_service->get_cart();
        $count = 0;
        foreach ($cart as $item) {
            $count += $item['quantity'];
        }

        ob_start();
        ?>
        <span class="apd-cart-count" id="apd-cart-count-display"><?php echo $count; ?></span>
        <script>
        jQuery(document).ready(function($) {
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
        if (is_admin()) {
            return;
        }
        
        // Access cart_service via plugin instance public getter
        $cart_service = $this->plugin->get_cart_service();
        if (!$cart_service) {
            return '';
        }
        $cart = $cart_service->get_cart();
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
                z-index: 9999;
                background: #111827;
                color: white;
                width: 60px;
                height: 60px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
                cursor: pointer;
                transition: all 0.3s ease;
            }
            .apd-floating-cart:hover {
                transform: scale(1.1);
                background: #1f2937;
            }
            .apd-floating-cart-count {
                position: absolute;
                top: -5px;
                right: -5px;
                background: #ef4444;
                color: white;
                border-radius: 50%;
                width: 24px;
                height: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 12px;
                font-weight: bold;
            }
        </style>
        <a href="<?php echo esc_url($cart_url); ?>" class="apd-floating-cart" id="apd-floating-cart">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="9" cy="21" r="1"></circle>
                <circle cx="20" cy="21" r="1"></circle>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
            </svg>
            <span class="apd-floating-cart-count" id="apd-floating-cart-count"><?php echo $count; ?></span>
        </a>
        <?php
    }

    /**
     * Checkout shortcode
     */
    public function shortcode_checkout()
    {
        $template_path = APD_PLUGIN_PATH . 'templates/checkout.php';
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }
        return '<p>Checkout template not found.</p>';
    }

    /**
     * Thank you shortcode
     */
    public function shortcode_thankyou()
    {
        $template_path = APD_PLUGIN_PATH . 'templates/thankyou.php';
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }
        return '<p>Thank you template not found.</p>';
    }

    /**
     * Orders shortcode
     */
    public function shortcode_orders()
    {
        $template_path = APD_PLUGIN_PATH . 'templates/orders.php';
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }
        return '<p>Orders template not found.</p>';
    }
}
