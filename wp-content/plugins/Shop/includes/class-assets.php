<?php

/**
 * Class APD_Assets
 * 
 * Handles all script and style enqueues
 *
 * @package AdvancedProductDesigner
 */

if (!defined('ABSPATH')) {
    exit;
}

class APD_Assets
{
    /**
     * Enqueue scripts for the customizer (legacy / specific page)
     */
    public static function enqueue_scripts()
    {
        // Only enqueue customizer scripts on customizer pages
        if (get_query_var('customizer')) {
            wp_enqueue_style('apd-styles', APD_PLUGIN_URL . 'assets/css/customizer.css', array(), APD_VERSION);
            wp_enqueue_script('html2canvas', 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js', array('jquery'), '1.4.1', true);
            // Use consistent handle 'apd-customizer' instead of 'apd-script'
            wp_enqueue_script('apd-customizer', APD_PLUGIN_URL . 'assets/js/customizer.js', array('jquery', 'html2canvas'), APD_VERSION, true);

            // Get product ID for customizer
            $product_id = get_query_var('customizer');
            
            // Get template ID and customization options if product is loaded
            $template_id = 0;
            $enable_color_selection = 1;  // Default enabled (use integers for JavaScript)
            $enable_outline_selection = 1;  // Default enabled (use integers for JavaScript)
            
            if ($product_id) {
                $template_id = get_post_meta($product_id, '_fsc_template', true);
                
                // Get customization options
                $color_opt = get_post_meta($product_id, '_fsc_enable_color_selection', true);
                $outline_opt = get_post_meta($product_id, '_fsc_enable_outline_selection', true);
                
                // Default to enabled if not set, convert to integer (1 or 0) for JavaScript
                $enable_color_selection = ($color_opt === '' || $color_opt === '1') ? 1 : 0;
                $enable_outline_selection = ($outline_opt === '' || $outline_opt === '1') ? 1 : 0;
            }

            wp_localize_script('apd-customizer', 'apd_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                // Nonces used by various endpoints
                'nonce' => wp_create_nonce('apd_ajax_nonce'),
                'fsc_nonce' => wp_create_nonce('fsc_nonce'),
                'plugin_url' => APD_PLUGIN_URL,
                'site_url' => home_url(),
                'product_id' => $product_id,
                'template_id' => $template_id,
                'enable_color_selection' => $enable_color_selection,
                'enable_outline_selection' => $enable_outline_selection
            ));

            // Pass uploaded fonts to the customizer for font rendering
            $uploaded_fonts = get_option('apd_uploaded_fonts', array());
            wp_localize_script('apd-customizer', 'apdUploadedFonts', $uploaded_fonts);

            // Provide materials directly to the page to avoid extra AJAX calls
            $materials_map = APD_Helpers::get_materials($template_id);
            // Convert to format expected by frontend: name => {url, price}
            wp_localize_script('apd-customizer', 'fscDefaults', array(
                'materials' => $materials_map
            ));

            // Debug: Log script enqueue
            error_log('APD Scripts: Enqueued for customizer with product_id: ' . $product_id);
        }
    }

    /**
     * Enqueue admin scripts and styles
     */
    public static function admin_enqueue_scripts()
    {
        wp_enqueue_style('apd-admin-styles', APD_PLUGIN_URL . 'assets/css/admin.css', array(), APD_VERSION);
        wp_enqueue_style('apd-admin-fixes', APD_PLUGIN_URL . 'assets/css/admin-fixes.css', array(), APD_VERSION);

        // Fix for WordPress dismissible notices
        add_action('admin_footer', function() {
            ?>
            <script>
            // Fix for dismissible notices - ensure elements exist before adding listeners
            (function() {
                if (typeof jQuery !== 'undefined') {
                    jQuery(document).ready(function($) {
                        $('.notice-dismiss').off('click.wp-dismiss-notice').on('click.wp-dismiss-notice', function(e) {
                            e.preventDefault();
                            $(this).closest('.notice').fadeOut();
                        });
                    });
                }
            })();
            </script>
            <?php
        });

        // Enqueue media uploader on product edit page
        $screen = get_current_screen();
        if ($screen && $screen->post_type === 'apd_product') {
            wp_enqueue_media();
            wp_enqueue_script('apd-product-admin', APD_PLUGIN_URL . 'assets/js/product-admin.js', array('jquery'), APD_VERSION, true);
        }

        // Order detail page: Production Files export script
        if ($screen && strpos($screen->id, 'apd_order_detail') !== false) {
            wp_enqueue_script(
                'apd-order-admin-export',
                APD_PLUGIN_URL . 'assets/js/order-admin-export.js',
                array('jquery'),
                APD_VERSION,
                true
            );
            wp_localize_script('apd-order-admin-export', 'apdOrderExport', array(
                'nonce' => wp_create_nonce('apd_ajax_nonce'),
                'ajaxurl' => admin_url('admin-ajax.php')
            ));
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

    /**
     * Enqueue block editor assets
     */
    public static function enqueue_block_editor_assets()
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

    /**
     * Enqueue frontend scripts
     */
    public static function enqueue_frontend_scripts()
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

        // Inject uploaded fonts as inline CSS for frontend rendering
        $uploaded_fonts = get_option('apd_uploaded_fonts', array());
        if (!empty($uploaded_fonts)) {
            $font_css = '';
            foreach ($uploaded_fonts as $font) {
                if (!empty($font['family']) && !empty($font['url'])) {
                    $family_css = esc_attr($font['family']);
                    $url_css = esc_url($font['url']);
                    $weight_css = isset($font['weight']) ? esc_attr($font['weight']) : '400';
                    // Determine font format based on URL extension
                    $format = 'truetype';
                    if (strpos($url_css, '.woff2') !== false) {
                        $format = 'woff2';
                    } elseif (strpos($url_css, '.woff') !== false) {
                        $format = 'woff';
                    } elseif (strpos($url_css, '.otf') !== false) {
                        $format = 'opentype';
                    }
                    $font_css .= "@font-face{font-family:'{$family_css}';src:url('{$url_css}') format('{$format}');font-weight:{$weight_css};font-display:swap;}";
                }
            }
            if ($font_css) {
                wp_add_inline_style('apd-product-block', $font_css);
            }
        }

        // Enqueue customizer scripts and styles
        wp_enqueue_script('apd-product-customizer', APD_PLUGIN_URL . 'assets/js/product-customizer.js', array('jquery'), APD_VERSION, true);
        wp_enqueue_style('apd-product-customizer', APD_PLUGIN_URL . 'assets/css/product-customizer.css', array(), APD_VERSION);

        // NOTE: customizer.js is loaded by enqueue_scripts() on customizer pages only
        // Do NOT load it here to avoid duplicate loading and conflicts

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

        // Enqueue product variants script on product detail pages
        wp_enqueue_script('apd-product-variants', APD_PLUGIN_URL . 'assets/js/product-variants.js', array('jquery'), APD_VERSION, true);
        wp_localize_script('apd-product-variants', 'apdVariantsConfig', array(
            'homeUrl' => home_url(),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('apd_ajax_nonce')
        ));

        // Prepare apd_ajax data
        $product_detail_page_id = intval(get_option('apd_product_detail'));
        $product_detail_base_url = $product_detail_page_id ? get_permalink($product_detail_page_id) : home_url('/product-detail/');
        
        $customizer_page_id = intval(get_option('apd_customizer'));
        $customizer_base_url = $customizer_page_id ? get_permalink($customizer_page_id) : home_url('/customizer/');
        
        $apd_ajax_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('apd_ajax_nonce'),
            'site_url' => home_url(),
            'cart_url' => home_url(get_option('apd_cart_url', '/cart/')),
            'checkout_url' => home_url(get_option('apd_checkout_url', '/checkout/')),
            'products_url' => home_url(get_option('apd_products_url', '/products/')),
            'orders_url' => home_url(get_option('apd_orders_url', '/my-orders/')),
            'customizer_url' => $customizer_base_url,
            'customizer_base_url' => $customizer_base_url,
            'product_detail_url' => $product_detail_base_url,
            'product_detail_base_url' => $product_detail_base_url,
            'thank_you_url' => home_url(get_option('apd_thank_you_url', '/thank-you/'))
        );
        
        // Add customization options if on customizer page
        if (get_query_var('customizer')) {
            $product_id = get_query_var('customizer');
            $template_id = 0;
            $enable_color_selection = 1;
            $enable_outline_selection = 1;
            
            if ($product_id) {
                $template_id = get_post_meta($product_id, '_fsc_template', true);
                $color_opt = get_post_meta($product_id, '_fsc_enable_color_selection', true);
                $outline_opt = get_post_meta($product_id, '_fsc_enable_outline_selection', true);
                
                $enable_color_selection = ($color_opt === '' || $color_opt === '1') ? 1 : 0;
                $enable_outline_selection = ($outline_opt === '' || $outline_opt === '1') ? 1 : 0;
            }
            
            $apd_ajax_data['product_id'] = $product_id;
            $apd_ajax_data['template_id'] = $template_id;
            $apd_ajax_data['enable_color_selection'] = $enable_color_selection;
            $apd_ajax_data['enable_outline_selection'] = $enable_outline_selection;
        }

        wp_localize_script('apd-product-block-frontend', 'apd_ajax', $apd_ajax_data);

        wp_localize_script('apd-product-customizer', 'apd_ajax', $apd_ajax_data);

        // Also localize for cart script
        if (is_page() && (has_shortcode(get_post()->post_content, 'apd_cart') || is_page(get_option('apd_cart')))) {
            wp_localize_script('apd-cart', 'apd_ajax', $apd_ajax_data);
            wp_localize_script('apd-orders', 'apd_ajax', $apd_ajax_data);
        }
    }
}
