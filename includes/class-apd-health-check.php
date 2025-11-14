<?php
/**
 * APD Health Check System
 * Monitors plugin functionality and detects issues
 * 
 * @package AdvancedProductDesigner
 */

if (!defined('ABSPATH')) {
    exit;
}

class APD_Health_Check {
    
    private $errors = array();
    private $warnings = array();
    private $success = array();
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_health_check_page'));
        add_action('wp_ajax_apd_run_health_check', array($this, 'ajax_run_health_check'));
        
        // Auto-check on plugin activation
        add_action('admin_init', array($this, 'maybe_show_health_notice'));
    }
    
    public function add_health_check_page() {
        add_submenu_page(
            'apd-dashboard',
            'Health Check',
            '🏥 Health Check',
            'manage_options',
            'apd-health-check',
            array($this, 'render_health_check_page')
        );
    }
    
    public function render_health_check_page() {
        ?>
        <div class="wrap">
            <h1>🏥 Advanced Product Designer - Health Check</h1>
            <p>This tool checks your plugin configuration and identifies any issues.</p>
            
            <div class="apd-health-check-container">
                <button type="button" class="button button-primary button-hero" id="apd-run-health-check">
                    <span class="dashicons dashicons-yes-alt"></span> Run Health Check
                </button>
                
                <div id="apd-health-results" style="margin-top: 30px;"></div>
            </div>
        </div>
        
        <style>
            .apd-health-check-container {
                background: white;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                margin-top: 20px;
            }
            
            .apd-health-section {
                margin-bottom: 25px;
                padding: 20px;
                border-radius: 6px;
                border-left: 4px solid #ccc;
            }
            
            .apd-health-section.success {
                background: #f0f9f0;
                border-left-color: #46b450;
            }
            
            .apd-health-section.warning {
                background: #fff8e5;
                border-left-color: #ffb900;
            }
            
            .apd-health-section.error {
                background: #fef0f0;
                border-left-color: #dc3232;
            }
            
            .apd-health-section h3 {
                margin-top: 0;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .apd-health-item {
                padding: 10px 0;
                border-bottom: 1px solid #eee;
            }
            
            .apd-health-item:last-child {
                border-bottom: none;
            }
            
            .apd-health-item-title {
                font-weight: 600;
                margin-bottom: 5px;
            }
            
            .apd-health-item-message {
                color: #666;
                font-size: 14px;
            }
            
            .apd-health-stats {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }
            
            .apd-stat-card {
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                text-align: center;
            }
            
            .apd-stat-number {
                font-size: 36px;
                font-weight: bold;
                margin: 10px 0;
            }
            
            .apd-stat-label {
                color: #666;
                font-size: 14px;
            }
            
            #apd-run-health-check .dashicons {
                line-height: 1.4;
            }
            
            .apd-loading {
                display: inline-block;
                animation: spin 1s linear infinite;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#apd-run-health-check').on('click', function() {
                var $btn = $(this);
                var $results = $('#apd-health-results');
                
                $btn.prop('disabled', true);
                $btn.html('<span class="dashicons dashicons-update apd-loading"></span> Running checks...');
                $results.html('<p>Please wait while we check your plugin health...</p>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'apd_run_health_check',
                        nonce: '<?php echo wp_create_nonce('apd_health_check'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $results.html(response.data.html);
                        } else {
                            $results.html('<div class="notice notice-error"><p>Failed to run health check: ' + response.data + '</p></div>');
                        }
                    },
                    error: function() {
                        $results.html('<div class="notice notice-error"><p>Ajax error occurred. Please try again.</p></div>');
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                        $btn.html('<span class="dashicons dashicons-yes-alt"></span> Run Health Check');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    public function ajax_run_health_check() {
        check_ajax_referer('apd_health_check', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $this->run_all_checks();
        
        wp_send_json_success(array(
            'html' => $this->generate_results_html()
        ));
    }
    
    private function run_all_checks() {
        $this->errors = array();
        $this->warnings = array();
        $this->success = array();
        
        $this->check_environment();
        $this->check_database();
        $this->check_file_structure();
        $this->check_pages();
        $this->check_custom_post_types();
        $this->check_ajax_endpoints();
        $this->check_cart_functionality();
        $this->check_products();
        $this->check_templates();
    }
    
    private function check_environment() {
        // PHP Version
        if (version_compare(PHP_VERSION, '7.4', '>=')) {
            $this->success[] = array(
                'title' => 'PHP Version',
                'message' => 'Running PHP ' . PHP_VERSION
            );
        } else {
            $this->errors[] = array(
                'title' => 'PHP Version',
                'message' => 'PHP 7.4+ recommended. Currently running: ' . PHP_VERSION
            );
        }
        
        // WordPress Version
        global $wp_version;
        if (version_compare($wp_version, '5.8', '>=')) {
            $this->success[] = array(
                'title' => 'WordPress Version',
                'message' => 'Running WordPress ' . $wp_version
            );
        } else {
            $this->warnings[] = array(
                'title' => 'WordPress Version',
                'message' => 'WordPress 5.8+ recommended. Currently running: ' . $wp_version
            );
        }
        
        // Memory Limit
        $memory_limit = ini_get('memory_limit');
        $memory_bytes = $this->convert_to_bytes($memory_limit);
        if ($memory_bytes >= 256 * 1024 * 1024) {
            $this->success[] = array(
                'title' => 'Memory Limit',
                'message' => 'Memory limit: ' . $memory_limit
            );
        } else {
            $this->warnings[] = array(
                'title' => 'Memory Limit',
                'message' => 'Recommended 256M+. Current: ' . $memory_limit
            );
        }
        
        // Upload Directory Writable
        $upload_dir = wp_upload_dir();
        if (is_writable($upload_dir['basedir'])) {
            $this->success[] = array(
                'title' => 'Upload Directory',
                'message' => 'Upload directory is writable'
            );
        } else {
            $this->errors[] = array(
                'title' => 'Upload Directory',
                'message' => 'Upload directory is not writable: ' . $upload_dir['basedir']
            );
        }
        
        // Plugin directories
        $plugin_dirs = array(
            APD_PLUGIN_PATH . 'uploads/object/',
            APD_PLUGIN_PATH . 'uploads/material/',
            APD_PLUGIN_PATH . 'uploads/object_1/'
        );
        
        foreach ($plugin_dirs as $dir) {
            if (is_dir($dir) && is_writable($dir)) {
                $this->success[] = array(
                    'title' => 'Plugin Directory',
                    'message' => basename($dir) . ' directory is writable'
                );
            } else {
                $this->warnings[] = array(
                    'title' => 'Plugin Directory',
                    'message' => basename($dir) . ' directory missing or not writable'
                );
            }
        }
    }
    
    private function check_database() {
        global $wpdb;
        
        // Check if session table exists (if used)
        $table_name = $wpdb->prefix . 'apd_sessions';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
            $this->success[] = array(
                'title' => 'Database Tables',
                'message' => 'APD session table exists'
            );
        }
    }
    
    private function check_file_structure() {
        $required_files = array(
            'freight-signs-customizer.php' => 'Main plugin file',
            'assets/js/customizer.js' => 'Customizer script',
            'assets/js/cart.js' => 'Cart script',
            'assets/js/product-block-frontend.js' => 'Product frontend script',
            'assets/css/customizer.css' => 'Customizer styles',
            'templates/customizer.php' => 'Customizer template',
            'templates/product-list.php' => 'Product list template',
            'templates/product-detail-page.php' => 'Product detail template',
            'templates/checkout.php' => 'Checkout template'
        );
        
        foreach ($required_files as $file => $description) {
            if (file_exists(APD_PLUGIN_PATH . $file)) {
                $this->success[] = array(
                    'title' => 'File: ' . $description,
                    'message' => $file . ' exists'
                );
            } else {
                $this->errors[] = array(
                    'title' => 'File: ' . $description,
                    'message' => $file . ' is missing!'
                );
            }
        }
    }
    
    private function check_pages() {
        $pages = array(
            'apd_cart' => 'Cart Page',
            'apd_checkout' => 'Checkout Page',
            'apd_thankyou' => 'Thank You Page',
            'apd_orders' => 'Orders Page'
        );
        
        foreach ($pages as $option => $label) {
            $page_id = get_option($option);
            if ($page_id && get_post($page_id)) {
                $this->success[] = array(
                    'title' => $label,
                    'message' => get_permalink($page_id)
                );
            } else {
                $this->warnings[] = array(
                    'title' => $label,
                    'message' => 'Page not configured or missing'
                );
            }
        }
    }
    
    private function check_custom_post_types() {
        $post_types = array('apd_product', 'apd_template', 'apd_order');
        
        foreach ($post_types as $post_type) {
            if (post_type_exists($post_type)) {
                $count = wp_count_posts($post_type);
                $total = isset($count->publish) ? $count->publish : 0;
                
                $this->success[] = array(
                    'title' => 'Post Type: ' . $post_type,
                    'message' => 'Registered (' . $total . ' items)'
                );
            } else {
                $this->errors[] = array(
                    'title' => 'Post Type: ' . $post_type,
                    'message' => 'Not registered!'
                );
            }
        }
    }
    
    private function check_ajax_endpoints() {
        $endpoints = array(
            'apd_add_to_cart',
            'apd_get_cart',
            'apd_update_cart_item',
            'apd_remove_cart_item',
            'apd_place_order',
            'apd_get_customizer_data',
            'apd_save_customization'
        );
        
        foreach ($endpoints as $action) {
            if (has_action('wp_ajax_' . $action) || has_action('wp_ajax_nopriv_' . $action)) {
                $this->success[] = array(
                    'title' => 'AJAX: ' . $action,
                    'message' => 'Endpoint registered'
                );
            } else {
                $this->errors[] = array(
                    'title' => 'AJAX: ' . $action,
                    'message' => 'Endpoint not registered!'
                );
            }
        }
    }
    
    private function check_cart_functionality() {
        // Check if session is started
        if (session_id() || defined('WP_CLI')) {
            $this->success[] = array(
                'title' => 'Session Management',
                'message' => 'PHP session active'
            );
        } else {
            $this->warnings[] = array(
                'title' => 'Session Management',
                'message' => 'Session not started (may cause cart issues)'
            );
        }
    }
    
    private function check_products() {
        $products = get_posts(array(
            'post_type' => 'apd_product',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        if (count($products) > 0) {
            $this->success[] = array(
                'title' => 'Products',
                'message' => count($products) . ' product(s) found'
            );
            
            // Check for products without required meta
            $missing_meta = array();
            foreach ($products as $product) {
                $price = get_post_meta($product->ID, '_fsc_price', true);
                $template = get_post_meta($product->ID, '_fsc_template', true);
                
                if (empty($price)) {
                    $missing_meta[] = $product->post_title . ' (missing price)';
                }
                if (empty($template)) {
                    $missing_meta[] = $product->post_title . ' (missing template)';
                }
            }
            
            if (count($missing_meta) > 0) {
                $this->warnings[] = array(
                    'title' => 'Product Configuration',
                    'message' => 'Some products missing data: ' . implode(', ', $missing_meta)
                );
            }
        } else {
            $this->warnings[] = array(
                'title' => 'Products',
                'message' => 'No products created yet'
            );
        }
    }
    
    private function check_templates() {
        $templates = get_posts(array(
            'post_type' => 'apd_template',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        if (count($templates) > 0) {
            $this->success[] = array(
                'title' => 'Templates',
                'message' => count($templates) . ' template(s) found'
            );
        } else {
            $this->warnings[] = array(
                'title' => 'Templates',
                'message' => 'No templates created yet'
            );
        }
    }
    
    private function generate_results_html() {
        $total_checks = count($this->errors) + count($this->warnings) + count($this->success);
        $health_score = ($total_checks > 0) ? round((count($this->success) / $total_checks) * 100) : 0;
        
        $html = '<div class="apd-health-stats">';
        $html .= '<div class="apd-stat-card"><div class="apd-stat-number" style="color: #46b450;">' . count($this->success) . '</div><div class="apd-stat-label">Passed</div></div>';
        $html .= '<div class="apd-stat-card"><div class="apd-stat-number" style="color: #ffb900;">' . count($this->warnings) . '</div><div class="apd-stat-label">Warnings</div></div>';
        $html .= '<div class="apd-stat-card"><div class="apd-stat-number" style="color: #dc3232;">' . count($this->errors) . '</div><div class="apd-stat-label">Errors</div></div>';
        $html .= '<div class="apd-stat-card"><div class="apd-stat-number" style="color: #0073aa;">' . $health_score . '%</div><div class="apd-stat-label">Health Score</div></div>';
        $html .= '</div>';
        
        if (count($this->errors) > 0) {
            $html .= '<div class="apd-health-section error">';
            $html .= '<h3><span class="dashicons dashicons-warning"></span> Critical Issues</h3>';
            foreach ($this->errors as $error) {
                $html .= '<div class="apd-health-item">';
                $html .= '<div class="apd-health-item-title">' . esc_html($error['title']) . '</div>';
                $html .= '<div class="apd-health-item-message">' . esc_html($error['message']) . '</div>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        
        if (count($this->warnings) > 0) {
            $html .= '<div class="apd-health-section warning">';
            $html .= '<h3><span class="dashicons dashicons-flag"></span> Warnings</h3>';
            foreach ($this->warnings as $warning) {
                $html .= '<div class="apd-health-item">';
                $html .= '<div class="apd-health-item-title">' . esc_html($warning['title']) . '</div>';
                $html .= '<div class="apd-health-item-message">' . esc_html($warning['message']) . '</div>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        
        if (count($this->success) > 0) {
            $html .= '<div class="apd-health-section success">';
            $html .= '<h3><span class="dashicons dashicons-yes-alt"></span> All Good</h3>';
            foreach ($this->success as $success) {
                $html .= '<div class="apd-health-item">';
                $html .= '<div class="apd-health-item-title">' . esc_html($success['title']) . '</div>';
                $html .= '<div class="apd-health-item-message">' . esc_html($success['message']) . '</div>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        
        return $html;
    }
    
    private function convert_to_bytes($value) {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = (int) $value;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
    
    public function maybe_show_health_notice() {
        // Show notice if health check hasn't been run recently
        $last_check = get_option('apd_last_health_check');
        if (!$last_check || (time() - $last_check) > WEEK_IN_SECONDS) {
            add_action('admin_notices', array($this, 'health_check_reminder'));
        }
    }
    
    public function health_check_reminder() {
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'apd') !== false) {
            ?>
            <div class="notice notice-info is-dismissible">
                <p><strong>Advanced Product Designer:</strong> It's been a while since your last health check. 
                <a href="<?php echo admin_url('admin.php?page=apd-health-check'); ?>">Run a health check</a> to ensure everything is working properly.</p>
            </div>
            <?php
        }
    }
}

// Note: Initialized in main plugin file
