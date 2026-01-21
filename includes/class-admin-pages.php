<?php
/**
 * Admin Pages Handler
 * 
 * Handles all admin menu pages and their rendering
 *
 * @package AdvancedProductDesigner
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class APD_Admin_Pages
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
     * Register admin menu hooks
     */
    public function init()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_menu', array($this, 'add_admin_submenus'));
        add_action('admin_menu', array($this, 'add_orders_menu'));
        add_action('admin_menu', array($this, 'add_shipping_menu'));
        add_action('admin_notices', array($this, 'admin_dashboard_notice'));
    }

    /**
     * Add main admin menu
     */
    public function add_admin_menu()
    {
        add_menu_page(
            'Product Designer',
            'Product Designer',
            'manage_options',
            'apd-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-art',
            6
        );
    }

    /**
     * Add admin submenus
     */
    public function add_admin_submenus()
    {
        // Remove the default submenu that WordPress creates
        remove_submenu_page('apd-dashboard', 'apd-dashboard');

        // Force Dashboard to be first by using position
        global $submenu;

        // Add Dashboard as the first submenu (this will be the default page)
        add_submenu_page(
            'apd-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'apd-dashboard',
            array($this, 'dashboard_page')
        );

        add_submenu_page(
            'apd-dashboard',
            'Templates',
            'Templates',
            'manage_options',
            'apd-templates',
            array($this, 'templates_page')
        );

        // Add hidden submenu for designer (not visible in menu but accessible)
        add_submenu_page(
            null,  // No parent menu
            'Template Designer',
            'Template Designer',
            'manage_options',
            'apd-designer',
            array($this, 'designer_page')
        );

        add_submenu_page(
            'apd-dashboard',
            'Materials',
            'Materials',
            'manage_options',
            'apd-materials',
            array($this, 'materials_page')
        );

        add_submenu_page(
            'apd-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'apd-settings',
            array($this, 'settings_page')
        );

        // Force reorder submenu to put Dashboard first
        if (isset($submenu['apd-dashboard'])) {
            $menu_items = $submenu['apd-dashboard'];
            $dashboard_item = null;
            $other_items = array();

            foreach ($menu_items as $key => $item) {
                if ($item[2] === 'apd-dashboard') {
                    $dashboard_item = $item;
                } else {
                    $other_items[] = $item;
                }
            }

            if ($dashboard_item) {
                $submenu['apd-dashboard'] = array_merge(array($dashboard_item), $other_items);
            }
        }
    }

    /**
     * Add orders menu
     */
    public function add_orders_menu()
    {
        add_menu_page(
            'Orders',
            'Orders',
            'manage_options',
            'apd-orders',
            array($this, 'orders_page'),
            'dashicons-cart',
            7
        );
    }

    /**
     * Add shipping prices menu
     */
    public function add_shipping_menu()
    {
        add_menu_page(
            'Shipping Prices',
            'Shipping Prices',
            'manage_options',
            'apd-shipping-prices',
            array($this, 'shipping_prices_page'),
            'dashicons-location-alt',
            8
        );
    }

    /**
     * Dashboard page
     */
    public function dashboard_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }

        // Get statistics
        $products = get_posts(array(
            'post_type' => 'apd_product',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));

        // Include dashboard template
        include APD_PLUGIN_PATH . 'templates/admin/dashboard.php';
    }

    /**
     * Templates page
     */
    public function templates_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle template actions
        if (isset($_POST['action'])) {
            $template_manager = new APD_Template_Manager($this->plugin);
            switch ($_POST['action']) {
                case 'duplicate':
                    $template_manager->duplicate_template();
                    break;
                case 'delete':
                    $template_manager->delete_template();
                    break;
            }
        }

        // Get all templates
        $templates = get_posts(array(
            'post_type' => 'apd_template',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        ));

        include APD_PLUGIN_PATH . 'templates/admin/templates.php';
    }

    /**
     * Designer page
     */
    public function designer_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $template_id = isset($_GET['template_id']) ? intval($_GET['template_id']) : 0;
        $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
        $template = null;
        $product = null;

        // If product_id is provided, get the associated template
        if ($product_id) {
            $product = get_post($product_id);
            if ($product && $product->post_type === 'apd_product') {
                $template_id = get_post_meta($product_id, '_fsc_template', true);

                if ($template_id) {
                    $template = get_post($template_id);
                    if (!$template || $template->post_type !== 'apd_template') {
                        $template = null;
                        $template_id = 0;
                    }
                } else {
                    // Try to find template by name (template-1)
                    $template_by_name = get_posts(array(
                        'post_type' => 'apd_template',
                        'name' => 'template-1',
                        'posts_per_page' => 1,
                        'post_status' => 'any'
                    ));

                    if (!empty($template_by_name)) {
                        $found_template = $template_by_name[0];
                        update_post_meta($product_id, '_fsc_template', $found_template->ID);
                        $template_id = $found_template->ID;
                        $template = $found_template;
                    }
                }
            }
        }
        // If template_id is provided directly
        elseif ($template_id) {
            $template = get_post($template_id);
            if (!$template || $template->post_type !== 'apd_template') {
                $template = null;
                $template_id = 0;
            }
        }

        // Create new template if none exists
        if (!$template_id) {
            $new_template = array(
                'post_title' => 'New Template ' . date('Y-m-d H:i:s'),
                'post_content' => '',
                'post_status' => 'draft',
                'post_type' => 'apd_template'
            );

            $template_id = wp_insert_post($new_template);

            if ($template_id) {
                update_post_meta($template_id, '_apd_template_width', 800);
                update_post_meta($template_id, '_apd_template_height', 600);
                update_post_meta($template_id, '_apd_template_bg_type', 'color');
                update_post_meta($template_id, '_apd_template_bg_color', '#ffffff');
                update_post_meta($template_id, '_apd_template_data', '{}');

                if ($product_id) {
                    update_post_meta($product_id, '_fsc_template', $template_id);
                }

                // Redirect to avoid duplicate template creation
                $redirect_url = admin_url('admin.php?page=apd-designer&template_id=' . $template_id);
                if ($product_id) {
                    $redirect_url .= '&product_id=' . $product_id;
                }
                wp_redirect($redirect_url);
                exit;
            }
        }

        // Pass variables to designer template
        $GLOBALS['apd_template_id'] = $template_id;
        $GLOBALS['apd_product_id'] = $product_id;
        $GLOBALS['apd_template'] = $template;
        
        // Get materials for the template
        $materials_data = $this->plugin->get_materials($template_id);
        $materials = array();
        foreach ($materials_data as $name => $data) {
            $materials[$name] = is_array($data) ? $data['url'] : $data;
        }

        include APD_PLUGIN_PATH . 'templates/admin/designer.php';
    }

    /**
     * Materials page
     */
    public function materials_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $material_manager = new APD_Material_Manager($this->plugin);

        // Handle category management
        if (isset($_POST['add_material_category']) && wp_verify_nonce($_POST['category_nonce'], 'manage_material_category')) {
            $material_manager->handle_add_material_category();
        }

        if (isset($_POST['delete_material_category']) && wp_verify_nonce($_POST['category_nonce'], 'manage_material_category')) {
            $material_manager->handle_delete_material_category();
        }

        // Handle material upload
        if (isset($_POST['upload_material']) && wp_verify_nonce($_POST['material_nonce'], 'upload_material')) {
            $material_manager->handle_material_upload();
        }

        // Handle material deletion
        if (isset($_POST['delete_material']) && wp_verify_nonce($_POST['material_nonce'], 'delete_material')) {
            $material_manager->handle_material_deletion();
        }

        // Handle material price update
        if (isset($_POST['update_material_price']) && wp_verify_nonce($_POST['material_price_nonce'], 'update_material_price')) {
            $material_manager->handle_material_price_update();
            echo '<script type="text/javascript">window.location.href = "' . admin_url('admin.php?page=apd-materials') . '";</script>';
            exit;
        }

        // Handle material category update
        if (isset($_POST['update_material_category']) && wp_verify_nonce($_POST['material_category_nonce'], 'update_material_category')) {
            $material_manager->handle_material_category_update();
            echo '<script type="text/javascript">window.location.href = "' . admin_url('admin.php?page=apd-materials') . '";</script>';
            exit;
        }

        // Get material categories
        $categories = $material_manager->get_material_categories();

        // Get current materials
        $materials = $material_manager->get_materials_list();

        include APD_PLUGIN_PATH . 'templates/admin/materials.php';
    }

    /**
     * Settings page
     */
    public function settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        include APD_PLUGIN_PATH . 'templates/admin/settings.php';
    }

    /**
     * Shipping prices page
     */
    public function shipping_prices_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        include APD_PLUGIN_PATH . 'templates/admin/shipping-prices.php';
    }

    /**
     * Orders page
     */
    public function orders_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Get all orders
        $orders = get_posts(array(
            'post_type' => 'apd_order',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        ));

        include APD_PLUGIN_PATH . 'templates/admin/orders.php';
    }

    /**
     * Products page
     */
    public function products_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle bulk actions
        if (isset($_POST['action']) && $_POST['action'] == 'bulk-delete') {
            $this->handle_bulk_delete();
        }

        // Get all products
        $products = get_posts(array(
            'post_type' => 'apd_product',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));

        include APD_PLUGIN_PATH . 'templates/admin/freight-products.php';
    }

    /**
     * Handle bulk delete action
     */
    private function handle_bulk_delete()
    {
        if (!isset($_POST['product_ids']) || !is_array($_POST['product_ids'])) {
            return;
        }

        check_admin_referer('bulk-products');

        foreach ($_POST['product_ids'] as $product_id) {
            wp_delete_post(intval($product_id), true);
        }

        add_settings_error(
            'apd_messages',
            'apd_message',
            __('Products deleted successfully', 'advanced-product-designer'),
            'updated'
        );
    }

    /**
     * Admin dashboard notice
     */
    public function admin_dashboard_notice()
    {
        $screen = get_current_screen();
        if ($screen->id !== 'toplevel_page_apd-dashboard') {
            return;
        }

        $dismissed = get_option('apd_dashboard_notice_dismissed', false);
        if ($dismissed) {
            return;
        }

        ?>
        <div class="notice notice-info is-dismissible apd-dashboard-notice" data-notice="dashboard">
            <p>
                <strong><?php _e('Welcome to Advanced Product Designer!', 'advanced-product-designer'); ?></strong><br>
                <?php _e('Get started by creating your first product template and material.', 'advanced-product-designer'); ?>
            </p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('.apd-dashboard-notice').on('click', '.notice-dismiss', function() {
                $.post(ajaxurl, {
                    action: 'apd_dismiss_dashboard_notice',
                    nonce: '<?php echo wp_create_nonce('apd_dismiss_notice'); ?>'
                });
            });
        });
        </script>
        <?php
    }
}
