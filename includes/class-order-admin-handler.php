<?php
/**
 * Order Admin Handler
 * 
 * Handles order admin pages, status management, and order operations
 *
 * @package AdvancedProductDesigner
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class APD_Order_Admin_Handler
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
        add_action('init', array($this, 'apd_register_statuses_visible'));
        add_action('admin_menu', array($this, 'apd_register_orders_admin_pages'));
        add_action('wp_ajax_apd_update_order_status', array($this, 'apd_update_order_status'));
        add_action('wp_ajax_apd_add_order_note', array($this, 'apd_add_order_note'));
        add_action('wp_ajax_apd_rebuild_order_labels', array($this, 'apd_rebuild_order_labels'));
    }

    public function apd_register_statuses_visible()
    {
        // Register only the statuses we actually use
        register_post_status('apd_pending', array(
            'label' => 'Pending',
            'public' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>')
        ));
        register_post_status('apd_confirmed', array(
            'label' => 'Confirmed',
            'public' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Confirmed <span class="count">(%s)</span>', 'Confirmed <span class="count">(%s)</span>')
        ));
        register_post_status('apd_completed', array(
            'label' => 'Completed',
            'public' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Completed <span class="count">(%s)</span>', 'Completed <span class="count">(%s)</span>')
        ));
    }

    // --- Admin Pages ---
    public function apd_register_orders_admin_pages()
    {
        add_menu_page(
            'Orders', 'Orders', 'manage_options', 'apd_orders', array($this, 'apd_render_orders_list_page'), 'dashicons-cart', 26
        );
        add_submenu_page('apd_orders', 'Orders', 'All Orders', 'manage_options', 'apd_orders', array($this, 'apd_render_orders_list_page'));
        add_submenu_page('apd_orders', 'Order Detail', 'Order Detail', 'manage_options', 'apd_order_detail', array($this, 'apd_render_order_detail_page'));
    }

    private function apd_get_all_statuses()
    {
        return array(
            'apd_pending' => 'Pending',
            'apd_confirmed' => 'Confirmed',
            'apd_completed' => 'Completed'
        );
    }

    public function apd_render_orders_list_page()
    {
        if (!current_user_can('manage_options'))
            return;
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $s = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $args = array(
            'post_type' => 'apd_order',
            'post_status' => array_keys($this->apd_get_all_statuses()),
            'posts_per_page' => 20,
            's' => $s,
        );
        if ($status) {
            $args['post_status'] = $status;
        }
        $q = new WP_Query($args);
        echo '<div class="wrap"><h1>Orders</h1>';
        echo '<form method="get" style="margin:12px 0">';
        echo '<input type="hidden" name="page" value="apd_orders"/>';
        echo '<select name="status"><option value="">All statuses</option>';
        foreach ($this->apd_get_all_statuses() as $k => $v) {
            printf('<option value="%s" %s>%s</option>', esc_attr($k), selected($status, $k, false), esc_html($v));
        }
        echo '</select> ';
        printf('<input type="search" name="s" value="%s" placeholder="Search orders..."/> ', esc_attr($s));
        echo '<button class="button">Filter</button>';
        echo '</form>';
        echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>ID</th><th>Customer</th><th>Status</th><th>Total</th><th>Created</th><th>Actions</th></tr></thead><tbody>';
        if ($q->have_posts()) {
            while ($q->have_posts()) {
                $q->the_post();
                $pid = get_the_ID();
                $cust = get_post_meta($pid, 'customer_name', true);
                $total = floatval(get_post_meta($pid, 'total_amount', true));
                $status_label = get_post_status_object(get_post_status($pid));
                printf('<tr><td>%d</td><td>%s</td><td>%s</td><td>$%0.2f</td><td>%s</td><td><a class="button" href="%s">View</a></td></tr>',
                    $pid,
                    esc_html($cust ?: '-'),
                    esc_html($status_label ? $status_label->label : get_post_status($pid)),
                    $total,
                    esc_html(get_the_date('Y-m-d H:i')),
                    esc_url(admin_url('admin.php?page=apd_order_detail&order_id=' . $pid)));
            }
            wp_reset_postdata();
        } else {
            echo '<tr><td colspan="6">No orders found.</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    public function apd_render_order_detail_page()
    {
        if (!current_user_can('manage_options'))
            return;
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        if (!$order_id) {
            echo '<div class="wrap"><h1>Order Detail</h1><p>Invalid order.</p></div>';
            return;
        }
        $p = get_post($order_id);
        if (!$p || $p->post_type !== 'apd_order') {
            echo '<div class="wrap"><h1>Order Detail</h1><p>Order not found.</p></div>';
            return;
        }
        $meta = array(
            'customer_name' => get_post_meta($order_id, 'customer_name', true),
            'customer_email' => get_post_meta($order_id, 'customer_email', true),
            'customer_phone' => get_post_meta($order_id, 'customer_phone', true),
            'customer_address' => get_post_meta($order_id, 'customer_address', true),
        );
        $text_fields = get_post_meta($order_id, 'text_fields', true);
        $template_data = get_post_meta($order_id, 'template_data', true);
        $fields_display = get_post_meta($order_id, 'fields_display', true);
        $template_fields_array = get_post_meta($order_id, 'template_fields_array', true);
    $preview_image_png = get_post_meta($order_id, 'preview_image_png', true);
    $preview_image_svg = get_post_meta($order_id, 'preview_image_svg', true);
        $preview_image_url = get_post_meta($order_id, 'preview_image_url', true);
        $customization_image_url = get_post_meta($order_id, 'customization_image_url', true);
        // Load cart items (support JSON string or array shapes)
        $raw_cart = get_post_meta($order_id, 'cart_items', true);
        $cart_items = $raw_cart;
        if (is_string($raw_cart)) {
            $decoded = json_decode($raw_cart, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $cart_items = $decoded;
            }
        }
        if (!is_array($cart_items)) {
            $cart_items = array();
        }
        // Normalize shapes: {items: [...]}, associative maps -> sequential array
        if (isset($cart_items['items']) && is_array($cart_items['items'])) {
            $cart_items = $cart_items['items'];
        }
        $keys = array_keys($cart_items);
        $is_assoc = array_filter($keys, 'is_string') ? true : false;
        if ($is_assoc) {
            $cart_items = array_values($cart_items);
        }

        $cart_total = get_post_meta($order_id, 'cart_total', true);
        if (!is_numeric($cart_total)) {
            $cart_total = 0;
            foreach ($cart_items as $ci) {
                $ci_price = isset($ci['price']) ? $ci['price'] : (isset($ci['product_price']) ? $ci['product_price'] : 0);
                $ci_quantity = isset($ci['quantity']) ? $ci['quantity'] : 1;
                $cart_total += isset($ci['total']) ? (float) $ci['total'] : ((float) $ci_price * (int) $ci_quantity);
            }
        }
        $notes = get_post_meta($order_id, 'apd_notes', true);
        if (!is_array($notes))
            $notes = array();

        // Debug: Log what images we found
    error_log("APD Order Detail #$order_id - preview_image_svg: " . ($preview_image_svg ?: 'EMPTY'));
    error_log("APD Order Detail #$order_id - preview_image_png: " . ($preview_image_png ?: 'EMPTY'));
        error_log("APD Order Detail #$order_id - preview_image_url: " . ($preview_image_url ?: 'EMPTY'));
        error_log("APD Order Detail #$order_id - customization_image_url: " . ($customization_image_url ?: 'EMPTY'));

        echo '<div class="wrap"><h1>Order #' . $order_id . '</h1>';

        // Display Preview Image if available (fallback to first cart item preview if top-level missing)
        $image_to_display = $preview_image_svg ?: ($preview_image_png ?: ($preview_image_url ?: $customization_image_url));
        if (!$image_to_display && !empty($cart_items)) {
            $first = $cart_items[0];
            $image_to_display = isset($first['preview_image_svg']) ? $first['preview_image_svg'] : (isset($first['preview_image_png']) ? $first['preview_image_png'] : (isset($first['preview_image_url']) ? $first['preview_image_url'] : (isset($first['customization_image_url']) ? $first['customization_image_url'] : (isset($first['image_url']) ? $first['image_url'] : ''))));
            
            // For non-customizable products, try to get product thumbnail or logo
            if (!$image_to_display && !empty($first['product_id'])) {
                $prod_id = intval($first['product_id']);
                
                // Try thumbnail first
                $thumbnail_id = get_post_meta($prod_id, '_fsc_thumbnail_id', true);
                if ($thumbnail_id) {
                    $image_to_display = wp_get_attachment_image_url($thumbnail_id, 'large');
                }
                
                // Fallback to logo if no thumbnail
                if (!$image_to_display) {
                    $logo_url = get_post_meta($prod_id, '_fsc_logo_file', true);
                    if ($logo_url) {
                        $image_to_display = $logo_url;
                    }
                }
                
                // Last resort: post thumbnail
                if (!$image_to_display) {
                    $image_to_display = get_the_post_thumbnail_url($prod_id, 'large');
                }
            }
        }

        // Determine best SVG source (if available) for direct download
        $svg_download_url = '';
        if (!empty($preview_image_svg)) {
            $svg_download_url = $preview_image_svg;
        } elseif (!empty($cart_items)) {
            // Try to find an SVG on the first cart item
            $first = $cart_items[0];
            if (!empty($first['preview_image_svg'])) {
                $svg_download_url = $first['preview_image_svg'];
            } elseif (!empty($first['customization_data'])) {
                $cd = is_array($first['customization_data']) ? $first['customization_data'] : json_decode($first['customization_data'], true);
                if (is_array($cd) && !empty($cd['preview_image_svg'])) {
                    $svg_download_url = $cd['preview_image_svg'];
                }
            }
            
            // For non-customizable products, fetch the product's logo SVG
            if (empty($svg_download_url) && !empty($first['product_id'])) {
                $product_id_from_cart = intval($first['product_id']);
                $product_logo = get_post_meta($product_id_from_cart, '_fsc_logo_file', true);
                error_log("APD Order Detail - Looking for SVG: product_id={$product_id_from_cart}, logo_file=" . ($product_logo ?: 'EMPTY'));
                
                // Validate that it's actually an SVG file
                if (!empty($product_logo) && preg_match('/\.svg$/i', $product_logo)) {
                    // Check if it's a URL or file path
                    if (filter_var($product_logo, FILTER_VALIDATE_URL)) {
                        $svg_download_url = $product_logo;
                        error_log("APD Order Detail - SVG URL found: {$product_logo}");
                    } else {
                        // It's a file path, convert to URL if needed
                        $upload_dir = wp_upload_dir();
                        $base_dir = $upload_dir['basedir'];
                        $base_url = $upload_dir['baseurl'];
                        
                        // If path is relative to uploads dir
                        if (strpos($product_logo, $base_dir) === 0) {
                            $svg_download_url = str_replace($base_dir, $base_url, $product_logo);
                        } else {
                            $svg_download_url = $product_logo;
                        }
                        error_log("APD Order Detail - SVG path converted to URL: {$svg_download_url}");
                    }
                }
            }
        }
        // If not explicitly found, but the displayed image is an SVG data URL, allow downloading that
        if (empty($svg_download_url) && is_string($image_to_display) && preg_match('/^data:image(?:\\+svg|\\/svg(?:\\+xml|\\-xml)?);/i', $image_to_display)) {
            $svg_download_url = $image_to_display;
        }
        
        // Add download SVG button for admin
        if (current_user_can('manage_options') && !empty($svg_download_url)) {
            echo '<div class="order-svg-download-section" style="margin: 20px 0; padding: 15px; background: #f0f6fc; border: 1px solid #0073aa; border-radius: 4px;">';
            echo '<h3 style="margin: 0 0 10px 0; font-size: 14px; color: #0073aa;">Production Files</h3>';
            echo '<div style="display: flex; gap: 10px; flex-wrap: wrap;">';
            echo '<button type="button" class="button button-primary" onclick="downloadOrderSVG(' . $order_id . ')">';
            echo '<span class="dashicons dashicons-download" style="margin-top: 3px;"></span> Download Original SVG';
            echo '</button>';
            echo '<button type="button" class="button button-secondary" onclick="processCutReadySVG(' . $order_id . ')" style="background: #2271b1; color: white; border-color: #2271b1;">';
            echo '<span class="dashicons dashicons-media-code" style="margin-top: 3px;"></span> Export Cut-Ready SVG';
            echo '</button>';
            echo '<button type="button" class="button button-secondary" onclick="exportVectorPDF(' . $order_id . ')" style="background: #d63638; color: white; border-color: #d63638;">';
            echo '<span class="dashicons dashicons-media-document" style="margin-top: 3px;"></span> Export Vector PDF';
            echo '</button>';
            echo '</div>';
            echo '<p class="description" style="margin: 10px 0 0 0;">Original SVG includes textures and effects. Cut-Ready SVG is optimized for CorelDRAW/cutting machines (removes textures, flattens layers). Vector PDF preserves all styles and material patterns - open in CorelDRAW to convert to editable vectors.</p>';
            echo '</div>';
        }

        // Debug output for admin
        if (current_user_can('manage_options')) {
            echo '<!-- DEBUG: preview_image_svg = ' . esc_html($preview_image_svg ?: 'EMPTY') . ' -->';
            echo '<!-- DEBUG: preview_image_png = ' . esc_html($preview_image_png ?: 'EMPTY') . ' -->';
            echo '<!-- DEBUG: preview_image_url = ' . esc_html($preview_image_url ?: 'EMPTY') . ' -->';
            echo '<!-- DEBUG: customization_image_url = ' . esc_html($customization_image_url ?: 'EMPTY') . ' -->';
            echo '<!-- DEBUG: image_to_display = ' . esc_html($image_to_display ?: 'EMPTY') . ' -->';
            echo '<!-- DEBUG: svg_download_url = ' . esc_html($svg_download_url ?: 'EMPTY') . ' -->';
            if (!empty($cart_items)) {
                $first_item = $cart_items[0];
                echo '<!-- DEBUG: first_cart_item_product_id = ' . esc_html(isset($first_item['product_id']) ? $first_item['product_id'] : 'EMPTY') . ' -->';
                echo '<!-- DEBUG: first_cart_item_keys = ' . esc_html(implode(', ', array_keys($first_item))) . ' -->';
            }
        }

        if ($image_to_display) {
            echo '<h2 class="title">Customized Design Preview</h2>';
            echo '<div style="background:#f8f9fa;border:1px solid #e1e1e1;padding:20px;max-width:800px;margin-bottom:20px;border-radius:8px;">';
            echo '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;gap:8px;">';
            echo '<span style="font-weight:600;color:#333;">Design Preview</span>';
            echo '<div style="display:flex;gap:8px;">';
            // PNG download button (existing)
            echo '<button id="download-design-btn" class="button button-primary" style="background:#0073aa;border-color:#0073aa;color:#fff;border-radius:4px;cursor:pointer;font-size:14px;text-decoration:none;display:inline-flex;align-items:center;gap:8px;">';
            echo 'Download PNG';
            echo '</button>';
            // SVG download button (new) — only show if we have an SVG source
            echo '<button id="download-design-svg-btn" class="button" style="border-color:#2271b1;color:#2271b1;border-radius:4px;cursor:pointer;font-size:14px;text-decoration:none;display:' . (!empty($svg_download_url) ? 'inline-flex' : 'none') . ';align-items:center;gap:8px;">';
            echo 'Download SVG';
            echo '</button>';
            // PDF download button
            echo '<button id="download-design-pdf-btn" class="button" style="border-color:#d63638;color:#d63638;border-radius:4px;cursor:pointer;font-size:14px;text-decoration:none;display:inline-flex;align-items:center;gap:8px;">';
            echo 'Download PDF';
            echo '</button>';
            echo '</div>';
            echo '</div>';
            echo '<img id="preview-image" src="' . esc_attr($image_to_display) . '" alt="Customized Design" style="max-width:100%;height:auto;display:block;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,0.1);" />';
            echo '</div>';
        }

        // Order Items list
        echo '<h2 class="title" style="margin-top:20px;">Order Items</h2>';
        if (empty($cart_items)) {
            echo '<p>No items found in this order.</p>';
        } else {
            echo '<div style="background:#fff;border:1px solid #e1e1e1;padding:12px;border-radius:8px;max-width:1000px;margin-bottom:12px;">';
            foreach ($cart_items as $idx => $item) {
                $cd = array();
                if (!empty($item['customization_data'])) {
                    if (is_array($item['customization_data']))
                        $cd = $item['customization_data'];
                    elseif (is_string($item['customization_data'])) {
                        $decoded = json_decode($item['customization_data'], true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded))
                            $cd = $decoded;
                    }
                }
                $imgUrl = isset($item['preview_image_svg']) ? $item['preview_image_svg'] : (isset($item['preview_image_png']) ? $item['preview_image_png'] : (isset($item['preview_image_url']) ? $item['preview_image_url'] : (isset($item['customization_image_url']) ? $item['customization_image_url'] : (isset($item['image_url']) ? $item['image_url'] : ''))));
                if (!$imgUrl && !empty($cd)) {
                    $imgUrl = isset($cd['preview_image_svg']) ? $cd['preview_image_svg'] : (isset($cd['preview_image_png']) ? $cd['preview_image_png'] : (isset($cd['preview_image_url']) ? $cd['preview_image_url'] : (isset($cd['customization_image_url']) ? $cd['customization_image_url'] : (isset($cd['image_url']) ? $cd['image_url'] : ''))));
                }
                $pname = isset($item['product_name']) ? $item['product_name'] : (isset($cd['product_name']) ? $cd['product_name'] : 'Product');
                $qty = isset($item['quantity']) ? (int) $item['quantity'] : 1;
                $price = isset($item['total']) ? (float) $item['total'] : ((float) (isset($item['price']) ? $item['price'] : (isset($item['product_price']) ? $item['product_price'] : 0)) * $qty);
                echo '<div style="display:flex;gap:12px;align-items:center;border-bottom:1px solid #f1f1f1;padding:12px 0;">';
                if ($imgUrl) {
                    echo '<div style="width:120px;height:80px;flex:0 0 120px;overflow:hidden;border-radius:6px;border:1px solid #eee;display:flex;align-items:center;justify-content:center;background:#fafafa;"><img src="' . esc_attr($imgUrl) . '" style="max-width:100%;max-height:100%;display:block;"/></div>';
                } else {
                    echo '<div style="width:120px;height:80px;flex:0 0 120px;display:flex;align-items:center;justify-content:center;border-radius:6px;border:1px solid #eee;background:#fafafa;color:#999;font-size:24px;">📦</div>';
                }
                echo '<div style="flex:1;">';
                echo '<div style="font-weight:600;margin-bottom:6px;">' . esc_html($pname) . '</div>';
                // specs - check item, customization_data, variants, and order-level meta fields
                $specs = array();
                
                // Check for material in multiple places (customizer OR variants)
                $material = isset($item['vinyl_material']) ? $item['vinyl_material'] : (isset($cd['vinyl_material']) ? $cd['vinyl_material'] : (isset($cd['material']) ? $cd['material'] : ''));
                // Check variants array for non-customizable products
                if (empty($material) && !empty($cd['variants']['material'])) {
                    $material = $cd['variants']['material'];
                }
                if (empty($material) && $idx === 0) {
                    // For first item, fallback to order-level meta
                    $material = get_post_meta($order_id, 'vinyl_material', true);
                }
                // Always show material, use 'none' if empty
                $specs[] = 'Material: ' . (!empty($material) ? esc_html($material) : 'none');
                
                // Check for color in multiple places
                $color = isset($item['print_color']) ? $item['print_color'] : (isset($cd['print_color']) ? $cd['print_color'] : (isset($cd['color']) ? $cd['color'] : ''));
                if (empty($color) && $idx === 0) {
                    // For first item, fallback to order-level meta
                    $color = get_post_meta($order_id, 'print_color', true);
                }
                if (!empty($color)) {
                    $specs[] = 'Color: ' . esc_html($color);
                }
                
                // Check for size (customization_data OR variants)
                $size = isset($cd['size']) ? $cd['size'] : '';
                if (empty($size) && !empty($cd['variants']['size'])) {
                    $size = $cd['variants']['size'];
                }
                if (!empty($size)) {
                    $specs[] = 'Size: ' . esc_html($size);
                }
                
                if (!empty($specs)) {
                    echo '<div style="color:#666;margin-bottom:6px;">' . implode(' • ', $specs) . '</div>';
                }
                echo '<div style="color:#333;font-weight:600;">Qty: ' . esc_html($qty) . ' &nbsp; &nbsp; Price: $' . number_format($price, 2) . '</div>';
                echo '</div>';  // flex:1
                // Download buttons for SVG images
                if ($imgUrl && strpos($imgUrl, 'data:image/svg') === 0) {
                    echo '<div style="display:flex;flex-direction:column;gap:4px;">';
                    echo '<button class="download-svg-btn button" data-svg-url="' . esc_attr($imgUrl) . '" data-item-name="' . esc_attr($pname) . '" style="border-color:#2271b1;color:#2271b1;border-radius:4px;cursor:pointer;font-size:12px;text-decoration:none;display:inline-flex;align-items:center;gap:4px;padding:4px 8px;">';
                    echo 'Download SVG';
                    echo '</button>';
                    echo '<button class="download-pdf-btn button" data-img-url="' . esc_attr($imgUrl) . '" data-item-name="' . esc_attr($pname) . '" style="border-color:#d63638;color:#d63638;border-radius:4px;cursor:pointer;font-size:12px;text-decoration:none;display:inline-flex;align-items:center;gap:4px;padding:4px 8px;">';
                    echo 'Download PDF';
                    echo '</button>';
                    echo '</div>';
                }
                echo '</div>';  // item row
            }
            echo '<div style="text-align:right;margin-top:12px;font-weight:700;">Order Total: $' . number_format((float) $cart_total, 2) . '</div>';
            echo '</div>';
        }

        echo '<h2 class="title">Customer</h2><table class="widefat"><tbody>';
        foreach ($meta as $k => $v) {
            printf('<tr><th style="width:220px">%s</th><td>%s</td></tr>', esc_html(ucwords(str_replace('_', ' ', $k))), esc_html($v));
        }
        echo '</tbody></table>';

        echo '<h2 class="title" style="margin-top:20px;">Text Fields</h2><table class="widefat"><tbody>';
        $rendered_any = false;
        if (is_array($fields_display) && !empty($fields_display)) {
            foreach ($fields_display as $label => $val) {
                if ($val === '')
                    continue;
                printf('<tr><th style="width:220px">%s</th><td>%s</td></tr>', esc_html($label), esc_html($val));
                $rendered_any = true;
            }
        }
        if (!$rendered_any && is_array($template_fields_array)) {
            foreach ($template_fields_array as $row) {
                $label = is_array($row) ? (isset($row['label']) ? $row['label'] : (isset($row['id']) ? $row['id'] : '')) : '';
                $val = is_array($row) ? (isset($row['value']) ? $row['value'] : '') : '';
                if ($label && $val !== '') {
                    printf('<tr><th style="width:220px">%s</th><td>%s</td></tr>', esc_html($label), esc_html($val));
                    $rendered_any = true;
                }
            }
        }
        if (!$rendered_any && is_array($template_data)) {
            foreach ($template_data as $fid => $data) {
                $label = is_array($data) ? (isset($data['label']) ? $data['label'] : $fid) : $fid;
                $val = is_array($data) ? (isset($data['value']) ? $data['value'] : '') : $data;
                if ($val === '')
                    continue;
                printf('<tr><th style="width:220px">%s</th><td>%s</td></tr>', esc_html($label), esc_html($val));
                $rendered_any = true;
            }
        }
        if (!$rendered_any && is_array($text_fields)) {
            foreach ($text_fields as $fid => $val) {
                if (!$val)
                    continue;
                $label = ucwords(str_replace(array('fsc-', '_'), array('', ' '), $fid));
                printf('<tr><th style="width:220px">%s</th><td>%s</td></tr>', esc_html($label), esc_html($val));
            }
        }
        echo '</tbody></table>';

        $statuses = $this->apd_get_all_statuses();
        $cur = get_post_status($order_id);
        echo '<h2 class="title" style="margin-top:20px;">Status</h2>';
        echo '<select id="apd-status" style="min-width:220px;">';
        foreach ($statuses as $k => $v) {
            printf('<option value="%s" %s>%s</option>', esc_attr($k), selected($cur, $k, false), esc_html($v));
        }
        echo '</select> <button class="button button-primary" id="apd-save-status">Update</button>';

        echo '<h2 class="title" style="margin-top:20px;">Internal Notes</h2>';
        echo '<div id="apd-notes" style="background:#fff;border:1px solid #e1e1e1;padding:12px;max-width:800px;">';
        if ($notes) {
            foreach ($notes as $n) {
                printf('<div style="margin-bottom:8px;"><em>%s</em> &mdash; %s</div>', esc_html(isset($n['time']) ? $n['time'] : ''), esc_html(isset($n['text']) ? $n['text'] : ''));
            }
        }
        echo '</div>';
        echo '<textarea id="apd-note-text" rows="3" style="width:800px;margin-top:8px;" placeholder="Add a note..."></textarea><br/>';
        echo '<button class="button" id="apd-add-note">Add Note</button>';

        echo '<h2 class="title" style="margin-top:20px;">Maintenance</h2>';
        echo '<button class="button" id="apd-rebuild-labels">Rebuild Field Labels</button> <span id="apd-rebuild-result" style="margin-left:8px;color:#555;"></span>';

        // JS
        ?>
        <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/svg2pdf.js@2.2.3/dist/svg2pdf.umd.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/opentype.js@1.3.4/dist/opentype.min.js"></script>
        <script>
        window.exportVectorPDF = function(){ console.warn('PDF export loading...'); };
        </script>
        <script>
        (function(){
            const orderId = <?php echo (int) $order_id; ?>;
            document.getElementById('apd-save-status').addEventListener('click', function(){
                const status = document.getElementById('apd-status').value;
                fetch(ajaxurl, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams({ action:'apd_update_order_status', order_id: orderId, status: status, _wpnonce: '<?php echo esc_js(wp_create_nonce('apd_ajax_nonce')); ?>' }) })
                .then(r=>r.json()).then(r=>{ if(r.success){ alert('Status updated'); } else { alert('Failed: '+(r.data&&r.data.message||'unknown')); } });
            });
            document.getElementById('apd-add-note').addEventListener('click', function(){
                const text = document.getElementById('apd-note-text').value.trim();
                if(!text) return;
                fetch(ajaxurl, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams({ action:'apd_add_order_note', order_id: orderId, text: text, _wpnonce: '<?php echo esc_js(wp_create_nonce('apd_ajax_nonce')); ?>' }) })
                .then(r=>r.json()).then(r=>{ if(r.success){ location.reload(); } else { alert('Failed: '+(r.data&&r.data.message||'unknown')); } });
            });
            document.getElementById('apd-rebuild-labels').addEventListener('click', function(){
                const el = document.getElementById('apd-rebuild-result');
                el.textContent = 'Working...';
                fetch(ajaxurl, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams({ action:'apd_rebuild_order_labels', order_id: orderId, _wpnonce: '<?php echo esc_js(wp_create_nonce('apd_ajax_nonce')); ?>' }) })
                .then(r=>r.json()).then(r=>{ if(r.success){ el.textContent = 'Rebuilt '+(r.data&&r.data.count||0)+' fields.'; location.reload(); } else { el.textContent = 'Failed: '+(r.data&&r.data.message||'unknown'); } });
            });
            
            // Download image functionality
            const downloadBtn = document.getElementById('download-design-btn');
            if (downloadBtn) {
                downloadBtn.addEventListener('click', function(){
                    const img = document.getElementById('preview-image');
                    if (!img) {
                        alert('No image found to download');
                        return;
                    }
                    
                    // Create a temporary canvas to convert image to downloadable format
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    
                    // Set canvas size to match image
                    canvas.width = img.naturalWidth || img.width;
                    canvas.height = img.naturalHeight || img.height;
                    
                    // Draw image on canvas
                    ctx.drawImage(img, 0, 0);
                    
                    // Convert canvas to blob and download
                    canvas.toBlob(function(blob) {
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = 'order-' + orderId + '-design.png';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                    }, 'image/png');
                });
            }

            // Download SVG functionality (if available)
            const svgDataUrl = <?php echo json_encode($svg_download_url ?: ''); ?>;
            const downloadSvgBtn = document.getElementById('download-design-svg-btn');
            if (downloadSvgBtn) {
                if (!svgDataUrl) {
                    downloadSvgBtn.style.display = 'none';
                } else {
                    downloadSvgBtn.addEventListener('click', function(){
                        // Check if it's a data URL or regular URL
                        if (svgDataUrl.startsWith('data:')) {
                            // Normalize uncommon mime like data:image+svg to image/svg+xml
                            const href = svgDataUrl.replace(/^data:image\+svg/i, 'data:image/svg+xml');
                            const a = document.createElement('a');
                            a.href = href;
                            a.download = 'order-' + orderId + '-design.svg';
                            document.body.appendChild(a);
                            a.click();
                            document.body.removeChild(a);
                        } else {
                            // For regular URLs, fetch and download
                            fetch(svgDataUrl)
                                .then(response => {
                                    if (!response.ok) throw new Error('Failed to fetch SVG');
                                    return response.blob();
                                })
                                .then(blob => {
                                    const url = window.URL.createObjectURL(blob);
                                    const a = document.createElement('a');
                                    a.href = url;
                                    a.download = 'order-' + orderId + '-design.svg';
                                    document.body.appendChild(a);
                                    a.click();
                                    document.body.removeChild(a);
                                    window.URL.revokeObjectURL(url);
                                })
                                .catch(error => {
                                    console.error('Download error:', error);
                                    alert('Error downloading SVG file. The file may be corrupted or missing.');
                                });
                        }
                    });
                }
            }

            // Download SVG for individual order items
            document.querySelectorAll('.download-svg-btn').forEach(btn => {
                btn.addEventListener('click', function(){
                    const svgUrl = this.getAttribute('data-svg-url');
                    const itemName = this.getAttribute('data-item-name');
                    if (!svgUrl) {
                        alert('No SVG data found');
                        return;
                    }
                    
                    let svgContent = '';
                    
                    // Handle data URL (data:image/svg+xml,... or data:image/svg+xml;base64,...)
                    if (svgUrl.startsWith('data:')) {
                        const parts = svgUrl.split(',');
                        if (parts.length > 1) {
                            // Check if it's base64 encoded
                            if (parts[0].includes('base64')) {
                                svgContent = atob(parts[1]);
                            } else {
                                // It's URL encoded
                                svgContent = decodeURIComponent(parts[1]);
                            }
                        }
                    } else {
                        // It's a regular URL, we can't directly download cross-origin
                        alert('Cannot download SVG from external URL');
                        return;
                    }
                    
                    // Ensure SVG has proper XML declaration and namespace
                    if (svgContent && !svgContent.trim().startsWith('<' + '?xml')) {
                        svgContent = '<' + '?xml version="1.0" encoding="UTF-8"?' + '>\n' + svgContent;
                    }
                    
                    // Ensure SVG has proper xmlns attribute
                    if (svgContent && svgContent.includes('<svg') && !svgContent.includes('xmlns=')) {
                        svgContent = svgContent.replace('<svg', '<svg xmlns="http://www.w3.org/2000/svg"');
                    }
                    
                    // Create blob and download
                    const blob = new Blob([svgContent], { type: 'image/svg+xml;charset=utf-8' });
                    const blobUrl = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = blobUrl;
                    a.download = itemName.replace(/[^a-zA-Z0-9]/g, '-') + '-design.svg';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(blobUrl);
                });
            });

            // Download PDF functionality - Use proper SVG export with text-to-curves
            const downloadPdfBtn = document.getElementById('download-design-pdf-btn');
            if (downloadPdfBtn) {
                downloadPdfBtn.addEventListener('click', async function(){
                    const button = this;
                    const originalText = button.innerHTML;
                    button.disabled = true;
                    button.innerHTML = 'Generating PDF...';
                    
                    try {
                        // Use FSC_SVGExport to generate PDF with text-to-curves
                        if (window.FSC_SVGExport && typeof window.FSC_SVGExport.exportPDF === 'function') {
                            const success = await window.FSC_SVGExport.exportPDF('order-' + orderId + '-design.pdf');
                            if (success) {
                                console.log('✅ PDF exported successfully with text-to-curves');
                            } else {
                                alert('PDF export failed. Please try again.');
                            }
                        } else {
                            // Fallback to image-based PDF
                            const img = document.getElementById('preview-image');
                            if (!img) {
                                alert('No image found to download');
                                return;
                            }
                            
                            // Create a temporary canvas to get image data
                            const canvas = document.createElement('canvas');
                            const ctx = canvas.getContext('2d');
                            canvas.width = img.naturalWidth || img.width;
                            canvas.height = img.naturalHeight || img.height;
                            ctx.drawImage(img, 0, 0);
                            
                            // Convert to data URL
                            const imgData = canvas.toDataURL('image/png');
                            
                            // Create PDF using jsPDF
                            const { jsPDF } = window.jspdf;
                            const pdf = new jsPDF({
                                orientation: canvas.width > canvas.height ? 'landscape' : 'portrait',
                                unit: 'px',
                                format: [canvas.width, canvas.height]
                            });
                            
                            pdf.addImage(imgData, 'PNG', 0, 0, canvas.width, canvas.height);
                            pdf.save('order-' + orderId + '-design.pdf');
                        }
                    } catch (error) {
                        console.error('PDF export error:', error);
                        alert('PDF export failed: ' + error.message);
                    } finally {
                        button.disabled = false;
                        button.innerHTML = originalText;
                    }
                });
            }

            // Download PDF for individual order items
            document.querySelectorAll('.download-pdf-btn').forEach(btn => {
                btn.addEventListener('click', function(){
                    const imgUrl = this.getAttribute('data-img-url');
                    const itemName = this.getAttribute('data-item-name');
                    if (!imgUrl) {
                        alert('No image data found');
                        return;
                    }
                    
                    // Create an image element to load the SVG/image
                    const img = new Image();
                    img.onload = function() {
                        // Create canvas and draw image
                        const canvas = document.createElement('canvas');
                        const ctx = canvas.getContext('2d');
                        canvas.width = img.width;
                        canvas.height = img.height;
                        ctx.drawImage(img, 0, 0);
                        
                        // Convert to PNG data URL
                        const imgData = canvas.toDataURL('image/png');
                        
                        // Create PDF
                        const { jsPDF } = window.jspdf;
                        const pdf = new jsPDF({
                            orientation: canvas.width > canvas.height ? 'landscape' : 'portrait',
                            unit: 'px',
                            format: [canvas.width, canvas.height]
                        });
                        
                        pdf.addImage(imgData, 'PNG', 0, 0, canvas.width, canvas.height);
                        pdf.save(itemName.replace(/[^a-zA-Z0-9]/g, '-') + '-design.pdf');
                    };
                    
                    img.onerror = function() {
                        alert('Failed to load image');
                    };
                    
                    img.src = imgUrl;
                });
            });
        })();

        // Download validated SVG from order
        function downloadOrderSVG(orderId) {
            const button = event.target.closest('button');
            const originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<span class="dashicons dashicons-update-alt" style="margin-top: 3px; animation: spin 1s linear infinite;"></span> Processing...';

            // Add spinner animation
            const style = document.createElement('style');
            style.textContent = '@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }';
            document.head.appendChild(style);

            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'download_order_svg',
                    order_id: orderId
                },
                success: function(response) {
                    if (response.success) {
                        // Create download link for SVG file
                        const blob = new Blob([response.data.svg_content], { type: 'image/svg+xml' });
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = response.data.filename;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        window.URL.revokeObjectURL(url);

                        // Show success message
                        const successDiv = document.createElement('div');
                        successDiv.className = 'notice notice-success is-dismissible';
                        successDiv.innerHTML = '<p><strong>✅ Success!</strong> ' + response.data.message + '</p>';
                        button.closest('.order-svg-download-section').appendChild(successDiv);
                        
                        setTimeout(() => successDiv.remove(), 5000);
                    } else {
                        alert('Error: ' + (response.data.message || 'Failed to download SVG'));
                    }
                },
                error: function() {
                    alert('Network error occurred while downloading SVG');
                },
                complete: function() {
                    button.disabled = false;
                    button.innerHTML = originalText;
                }
            });
        }

        // Process Cut-Ready SVG
        function processCutReadySVG(orderId) {
            const button = event.target.closest('button');
            const originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<span class="dashicons dashicons-update-alt" style="margin-top: 3px; animation: spin 1s linear infinite;"></span> Processing...';

            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'apd_process_cut_ready_svg',
                    order_id: orderId,
                    _wpnonce: '<?php echo wp_create_nonce('apd_ajax_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        // Download the processed SVG file
                        const a = document.createElement('a');
                        a.href = response.data.file_url;
                        a.download = response.data.filename;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);

                        // Show success message
                        const successDiv = document.createElement('div');
                        successDiv.className = 'notice notice-success is-dismissible';
                        successDiv.innerHTML = '<p><strong>✅ Success!</strong> ' + response.data.message + '</p><p style="margin: 5px 0 0 0;"><small>File saved: ' + response.data.filename + '</small></p>';
                        button.closest('.order-svg-download-section').appendChild(successDiv);
                        
                        setTimeout(() => successDiv.remove(), 8000);
                    } else {
                        alert('Error: ' + (response.data || 'Failed to process SVG'));
                    }
                },
                error: function(xhr, status, error) {
                    alert('Network error occurred while processing SVG: ' + error);
                },
                complete: function() {
                    button.disabled = false;
                    button.innerHTML = originalText;
                }
            });
        }

        // Client-side PDF generation - creates PDF with embedded SVG for CorelDRAW
        // Now includes text-to-curves conversion using opentype.js
        // Define this FIRST so it's available when exportVectorPDF is called
        async function generateClientSidePDF(svgContent, orderId, button) {
            console.log('📄 ===== CLIENT-SIDE PDF GENERATION =====');
            console.log('📄 Converting text to curves using opentype.js...');
            
            const originalText = button.innerHTML;
            
            try {
                // Parse SVG
                const parser = new DOMParser();
                const svgDoc = parser.parseFromString(svgContent, 'image/svg+xml');
                const svgElement = svgDoc.documentElement;
                
                // Check for parse errors
                const parseError = svgDoc.querySelector('parsererror');
                if (parseError) {
                    throw new Error('SVG parse error: ' + parseError.textContent);
                }
                
                // Analyze SVG before processing
                const textCountBefore = svgElement.querySelectorAll('text').length;
                const patternCountBefore = svgElement.querySelectorAll('pattern').length;
                const patternRefsBefore = (svgContent.match(/url\(#[^)]+\)/g) || []).length;
                
                console.log('📄 SVG Analysis (before processing):');
                console.log('  - Text elements:', textCountBefore);
                console.log('  - Pattern definitions:', patternCountBefore);
                console.log('  - Pattern references:', patternRefsBefore);
                
                // CRITICAL: Convert text to paths (curves) BEFORE PDF generation
                // This ensures fonts don't change in CorelDRAW and material outlines are preserved
                console.log('📄 Converting text to curves with material outlines...');
                
                // Convert text to paths (async function)
                await convertTextToPathsWithMaterialOutline(svgDoc, svgElement);
                
                // Update svgContent after text conversion
                svgContent = new XMLSerializer().serializeToString(svgElement);
                
                // Verify conversion
                const textCountAfter = svgElement.querySelectorAll('text').length;
                const pathCountAfter = svgElement.querySelectorAll('path').length;
                console.log('📄 Conversion verification:');
                console.log('  - Text elements remaining:', textCountAfter);
                console.log('  - Path elements created:', pathCountAfter);
                if (textCountAfter === 0) {
                    console.log('📄 ✅ All text converted to curves!');
                } else {
                    console.log('📄 ⚠️ Some text elements were not converted');
                }
                
                // Get SVG dimensions
                let width = parseFloat(svgElement.getAttribute('width')) || 800;
                let height = parseFloat(svgElement.getAttribute('height')) || 600;
                const viewBox = svgElement.getAttribute('viewBox');
                if (viewBox) {
                    const vb = viewBox.split(/\s+/);
                    if (vb.length >= 4) {
                        width = parseFloat(vb[2]) || width;
                        height = parseFloat(vb[3]) || height;
                    }
                }
                
                var JsPDF = (window.jspdf && window.jspdf.jsPDF) || (typeof jspdf !== 'undefined' && jspdf.jsPDF);
                if (!JsPDF) JsPDF = window.jspdf && (window.jspdf.jsPDF || window.jspdf);
                var pdf = new JsPDF({
                    orientation: width > height ? 'landscape' : 'portrait',
                    unit: 'px',
                    format: [width, height],
                    compress: true
                });
                
                // svg2pdf.js 2.2+ exports svg2pdf object/methods, NOT a standalone function
                // It adds .svg() method to jsPDF instance
                try {
                    // Use built-in svg2pdf method on jsPDF instance
                    // Requires svg2pdf.js loaded after jsPDF (which we do)
                    const svgPromise = pdf.svg(svgElement, { x: 0, y: 0, width: width, height: height });
                    if (svgPromise && typeof svgPromise.then === 'function') await svgPromise;
                    const fn = 'order-' + orderId + '-vector-' + Date.now() + '.pdf';
                    pdf.save(fn);
                    button.disabled = false;
                    button.innerHTML = originalText;
                    console.log('📄 ✅ PDF generated (svg2pdf.js vector): ' + fn);
                    return;
                } catch (svgError) {
                    console.warn('📄 svg2pdf failed, using PNG fallback:', svgError);
                    // svg2pdf might fail due to missing SVG elements or unsupported features
                }
                
                // Fallback: Convert SVG to high-resolution PNG first
                try {
                    // Create canvas for high-resolution conversion
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    
                    // Parse SVG to get actual dimensions
                    const parser = new DOMParser();
                    const svgDoc = parser.parseFromString(svgContent, 'image/svg+xml');
                    const svgEl = svgDoc.documentElement;
                    
                    // Get actual SVG dimensions (use viewBox if available, otherwise width/height)
                    let actualWidth = parseFloat(svgEl.getAttribute('width')) || 800;
                    let actualHeight = parseFloat(svgEl.getAttribute('height')) || 600;
                    const viewBox = svgEl.getAttribute('viewBox');
                    if (viewBox) {
                        const vb = viewBox.split(/\s+/);
                        if (vb.length >= 4) {
                            actualWidth = parseFloat(vb[2]) || actualWidth;
                            actualHeight = parseFloat(vb[3]) || actualHeight;
                        }
                    }
                    
                    // Set canvas size at high DPI (300 DPI for print quality)
                    const dpi = 300;
                    const scale = dpi / 96; // 96 is screen DPI
                    canvas.width = actualWidth * scale;
                    canvas.height = actualHeight * scale;
                    
                    // Draw SVG to canvas at high resolution
                    // We need to convert SVG to canvas data
                    const svgBlob = new Blob([svgContent], { type: 'image/svg+xml;charset=utf-8' });
                    const svgUrl = URL.createObjectURL(svgBlob);
                    
                    const img = new Image();
                    img.onload = function() {
                        try {
                            // Draw image to canvas at high resolution
                            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                            
                            // Convert canvas to PNG
                            const pngData = canvas.toDataURL('image/png', 1.0);
                            
                            // Add PNG to PDF
                            pdf.addImage(pngData, 'PNG', 0, 0, width, height, undefined, 'FAST');
                            
                            // Save PDF
                            const filename = 'order-' + orderId + '-vector-' + Date.now() + '.pdf';
                            pdf.save(filename);
                            
                            // Cleanup
                            URL.revokeObjectURL(svgUrl);
                            button.disabled = false;
                            button.innerHTML = originalText;
                            console.log('📄 ✅ PDF generated (PNG fallback): ' + filename);
                            
                        } catch (err) {
                            console.error('📄 Error drawing canvas:', err);
                            URL.revokeObjectURL(svgUrl);
                            throw err;
                        }
                    };
                    img.onerror = function() {
                        URL.revokeObjectURL(svgUrl);
                        throw new Error('Failed to load SVG as PNG for PDF fallback');
                    };
                    img.src = svgUrl;
                    
                } catch (imgError) {
                    console.error('📄 Image fallback failed:', imgError);
                    throw new Error('Failed to convert SVG to PNG for PDF');
                }
                
                button.disabled = false;
                button.innerHTML = originalText;
                
                // Use fallback if svg2pdf not available or failed
                if (!useSvg2Pdf) {
                    useImageFallback();
                }
                
            } catch (error) {
                console.error('PDF generation error:', error);
                alert('PDF generation failed: ' + error.message + '. Please use the SVG export for best CorelDRAW compatibility.');
                button.disabled = false;
                button.innerHTML = originalText;
            }
        }

        // Convert text elements to paths (curves) while preserving material outline patterns
        // Uses opentype.js to convert text to paths, then converts strokes to fills to preserve material outlines
        async function convertTextToPathsWithMaterialOutline(svgDoc, svgElement) {
            const namespace = 'http://www.w3.org/2000/svg';
            const textElements = Array.from(svgElement.querySelectorAll('text'));
            
            if (textElements.length === 0) {
                return; // No text to convert
            }
            
            console.log('📄 Converting ' + textElements.length + ' text elements to curves with material outlines...');
            
            // Step 1: Extract font data from SVG @font-face
            const fontCache = new Map();
            const styleElements = svgElement.querySelectorAll('style');
            
            for (const styleEl of styleElements) {
                const styleText = styleEl.textContent || styleEl.innerText;
                const fontFaceMatches = styleText.match(/@font-face\s*\{[^}]*\}/g);
                
                if (fontFaceMatches) {
                    for (const fontFace of fontFaceMatches) {
                        // Extract font-family
                        const familyMatch = fontFace.match(/font-family:\s*['"]?([^;'"]+)['"]?/i);
                        if (!familyMatch) continue;
                        const fontFamily = familyMatch[1].trim().replace(/['"]/g, '');
                        
                        // Extract base64 font data
                        const base64Match = fontFace.match(/src:\s*url\(data:application\/[^;]+;base64,([^)]+)\)/i);
                        if (base64Match) {
                            const base64Data = base64Match[1];
                            try {
                                // Decode base64 to binary
                                const binaryString = atob(base64Data);
                                const bytes = new Uint8Array(binaryString.length);
                                for (let i = 0; i < binaryString.length; i++) {
                                    bytes[i] = binaryString.charCodeAt(i);
                                }
                                
                                // Load font with opentype.js
                                var ot = (typeof window !== 'undefined' && window.opentype) || (typeof opentype !== 'undefined' ? opentype : null);
                                if (ot) {
                                    const font = ot.parse(bytes.buffer);
                                    fontCache.set(fontFamily, font);
                                    console.log('📄 Loaded font:', fontFamily);
                                }
                            } catch (e) {
                                console.warn('📄 Failed to load font ' + fontFamily + ':', e);
                            }
                        }
                    }
                }
            }
            
            // Step 2: Convert each text element to paths
            const convertedElements = [];
            
            for (let i = 0; i < textElements.length; i++) {
                const textEl = textElements[i];
                try {
                    const text = textEl.textContent || textEl.innerText;
                    if (!text || !text.trim()) continue;
                    
                    // Get text properties
                    const fill = textEl.getAttribute('fill') || '#000000';
                    const stroke = textEl.getAttribute('stroke') || 'none';
                    const strokeWidth = parseFloat(textEl.getAttribute('stroke-width') || '0');
                    const strokeLinejoin = textEl.getAttribute('stroke-linejoin') || 'round';
                    const strokeLinecap = textEl.getAttribute('stroke-linecap') || 'round';
                    const paintOrder = textEl.getAttribute('paint-order') || 'stroke fill';
                    const fontSize = parseFloat(textEl.getAttribute('font-size') || '16');
                    const fontFamily = (textEl.getAttribute('font-family') || 'Arial').replace(/['"]/g, '');
                    const fontWeight = textEl.getAttribute('font-weight') || 'normal';
                    const x = parseFloat(textEl.getAttribute('x') || '0');
                    const y = parseFloat(textEl.getAttribute('y') || '0');
                    const textAnchor = textEl.getAttribute('text-anchor') || 'start';
                    const dominantBaseline = textEl.getAttribute('dominant-baseline') || 'auto';
                    const transform = textEl.getAttribute('transform') || '';
                    
                    // Get parent for replacement
                    const parent = textEl.parentNode;
                    if (!parent) continue;
                    
                    // Create group to hold converted paths
                    const group = document.createElementNS(namespace, 'g');
                    if (transform) {
                        group.setAttribute('transform', transform);
                    }
                    
                    // Try to get font from cache
                    let font = fontCache.get(fontFamily);
                    if (!font) {
                        // Try case-insensitive match
                        for (const [cachedFamily, cachedFont] of fontCache.entries()) {
                            if (cachedFamily.toLowerCase() === fontFamily.toLowerCase()) {
                                font = cachedFont;
                                console.log('📄 Found font (case-insensitive match):', cachedFamily);
                                break;
                            }
                        }
                    }
                    
                    if (!font) {
                        console.warn('📄 Font not found in cache:', fontFamily);
                        console.warn('📄 Available fonts:', Array.from(fontCache.keys()));
                        // Continue without conversion - text will remain as text
                        continue;
                    }
                    
                    // Convert text to path using opentype.js
                    var ot = (typeof window !== 'undefined' && window.opentype) || (typeof opentype !== 'undefined' ? opentype : null);
                    if (font && ot) {
                        // Use opentype.js to convert text to path
                        try {
                            // opentype.js getPath() uses baseline at Y coordinate
                            // SVG text y position varies by dominant-baseline
                            // We need to convert SVG y to opentype.js baseline y
                            
                            let textX = x;
                            let textY = y;
                            
                            // Adjust Y for baseline conversion
                            // SVG 'hanging': y is top of text
                            // SVG 'middle': y is middle of text  
                            // SVG 'alphabetic'/'auto': y is baseline
                            // opentype.js: always uses baseline
                            if (dominantBaseline === 'hanging') {
                                // Top to baseline: approximately fontSize * 0.8 (depends on font)
                                textY = y + fontSize * 0.8;
                            } else if (dominantBaseline === 'middle') {
                                // Middle to baseline: approximately fontSize * 0.4
                                textY = y + fontSize * 0.4;
                            }
                            // else: y is already baseline (alphabetic/auto)
                            
                            // Get path from font (opentype.js generates path at baseline)
                            const path = font.getPath(text, textX, textY, fontSize);
                            const pathData = path.toPathData();
                            
                            // Get bounding box for text-anchor adjustment
                            const bbox = path.getBoundingBox();
                            const textWidth = bbox.x2 - bbox.x1;
                            
                            // Build transform: original transform + text-anchor adjustment
                            let groupTransform = '';
                            if (transform) {
                                groupTransform = transform;
                            }
                            
                            // Adjust for text-anchor
                            if (textAnchor === 'middle') {
                                if (groupTransform) groupTransform += ' ';
                                groupTransform += 'translate(' + (-textWidth / 2) + ', 0)';
                            } else if (textAnchor === 'end') {
                                if (groupTransform) groupTransform += ' ';
                                groupTransform += 'translate(' + (-textWidth) + ', 0)';
                            }
                            
                            if (groupTransform) {
                                group.setAttribute('transform', groupTransform);
                            }
                            
                            console.log('📄 Text position: original y=' + y + ', adjusted y=' + textY + ', anchor=' + textAnchor + ', baseline=' + dominantBaseline);
                            
                            // Create fill path (text shape)
                            const fillPath = document.createElementNS(namespace, 'path');
                            fillPath.setAttribute('d', pathData);
                            fillPath.setAttribute('fill', fill);
                            fillPath.setAttribute('stroke', 'none');
                            group.appendChild(fillPath);
                            
                            // Create outline path (material outline) if stroke exists
                            if (stroke && stroke.indexOf('url(#') !== -1 && strokeWidth > 0) {
                                // CRITICAL: For CorelDRAW compatibility, we need stroke with pattern
                                // PDF/CorelDRAW should support pattern strokes, but we'll ensure it's set correctly
                                try {
                                    const outlinePath = document.createElementNS(namespace, 'path');
                                    outlinePath.setAttribute('d', pathData);
                                    
                                    // Use stroke with material pattern - CorelDRAW should support this
                                    outlinePath.setAttribute('fill', 'none');
                                    outlinePath.setAttribute('stroke', stroke); // Material pattern URL
                                    outlinePath.setAttribute('stroke-width', strokeWidth);
                                    outlinePath.setAttribute('stroke-linejoin', strokeLinejoin);
                                    outlinePath.setAttribute('stroke-linecap', strokeLinecap);
                                    outlinePath.setAttribute('paint-order', paintOrder);
                                    
                                    // Also set in style to ensure it's preserved
                                    outlinePath.setAttribute('style', 'stroke: ' + stroke + '; stroke-width: ' + strokeWidth + '; stroke-linejoin: ' + strokeLinejoin + '; stroke-linecap: ' + strokeLinecap + '; fill: none;');
                                    
                                    // Insert outline BEFORE fill (so fill renders on top)
                                    group.insertBefore(outlinePath, fillPath);
                                    
                                    console.log('📄 ✅ Text element ' + (i + 1) + ' converted to curves');
                                    console.log('📄 ✅ Material outline preserved: stroke="' + stroke + '", width=' + strokeWidth);
                                    console.log('📄 Position: x=' + x + ', y=' + y + ', anchor=' + textAnchor + ', baseline=' + dominantBaseline);
                                } catch (e) {
                                    console.warn('📄 Failed to create material outline path:', e);
                                }
                            } else {
                                console.log('📄 ✅ Text element ' + (i + 1) + ' converted to curves (no material outline)');
                            }
                            
                            // Replace text with group
                            parent.replaceChild(group, textEl);
                            convertedElements.push(i);
                            
                        } catch (e) {
                            console.warn('📄 Failed to convert text element ' + (i + 1) + ' with opentype.js:', e);
                            // Keep original text element if conversion fails
                        }
                    } else {
                        // Font not available - cannot convert
                        console.warn('📄 Font not available for text element ' + (i + 1) + ':', fontFamily);
                        console.warn('📄 Text element will remain as text (not converted to curves)');
                        // Keep text element - will be handled by svg2pdf but won't be curves
                    }
                    
                } catch (error) {
                    console.warn('📄 Error processing text element ' + (i + 1) + ':', error);
                }
            }
            
            if (convertedElements.length > 0) {
                console.log('📄 ✅ Successfully converted ' + convertedElements.length + ' of ' + textElements.length + ' text elements to curves');
                console.log('📄 ✅ Material outline patterns preserved on converted paths');
                
                // Verify patterns are still in SVG
                const patternCount = svgElement.querySelectorAll('pattern').length;
                const patternRefs = (new XMLSerializer().serializeToString(svgElement).match(/url\(#[^)]+\)/g) || []).length;
                console.log('📄 Pattern verification:');
                console.log('  - Pattern definitions:', patternCount);
                console.log('  - Pattern references:', patternRefs);
                
                if (patternRefs > 0) {
                    console.log('📄 ✅ Material outline patterns are preserved in converted paths');
                } else {
                    console.warn('📄 ⚠️ WARNING: No pattern references found - material outlines may be lost');
                }
            } else {
                console.warn('📄 ⚠️ No text elements were converted to curves');
                console.warn('📄 ⚠️ Possible reasons:');
                console.warn('  - Fonts not found in SVG @font-face');
                console.warn('  - opentype.js library not loaded');
                console.warn('  - Font format not supported');
                console.warn('📄 Text elements will remain as text (not curves)');
            }
        }

        // Export Vector PDF (preserves all styles and material patterns)
        window.exportVectorPDF = function(orderId) {
            console.log('📄 ===== STARTING PDF EXPORT =====');
            console.log('📄 Order ID:', orderId);
            
            const button = event.target.closest('button');
            const originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<span class="dashicons dashicons-update-alt" style="margin-top: 3px; animation: spin 1s linear infinite;"></span> Generating PDF...';

            console.log('📄 Sending request to server for PDF generation...');
            console.log('📄 Action: apd_export_pdf');
            console.log('📄 URL:', ajaxurl);

            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'apd_export_pdf',
                    order_id: orderId,
                    _wpnonce: '<?php echo wp_create_nonce('apd_ajax_nonce'); ?>'
                },
                success: function(response) {
                    console.log('📄 Server response received:', response);
                    if (response.success) {
                        console.log('📄 ✅ Server response: SUCCESS');
                        
                        // Check if we need to generate PDF client-side
                        if (response.data.use_client_side && response.data.svg_content) {
                            exportVectorPDF._clientSide = true;
                            console.log('📄 ⚠️ Using CLIENT-SIDE (text→curves via opentype.js)');
                            
                            var parser = new DOMParser();
                            var svgDoc = parser.parseFromString(response.data.svg_content, 'image/svg+xml');
                            var svgEl = svgDoc.documentElement;
                            console.log('📄 SVG: ' + svgEl.querySelectorAll('text').length + ' text, ' + svgEl.querySelectorAll('pattern').length + ' patterns');
                            
                            runClientSidePDF(response.data.svg_content, orderId, button, originalText);
                            return;
                        }
                        
                        // Server-side generated PDF - download it
                        console.log('📄 ✅ Server-side PDF generated successfully');
                        console.log('📄 PDF URL:', response.data.file_url);
                        console.log('📄 Filename:', response.data.filename);
                        console.log('📄 Message:', response.data.message);
                        
                        // Analyze the generated PDF if possible
                        console.log('📄 PDF Generation Results:');
                        if (response.data.text_converted !== undefined) {
                            if (response.data.text_converted) {
                                console.log('📄 ✅ Text converted to curves: YES');
                            } else {
                                console.log('📄 ⚠️ Text converted to curves: NO (' + response.data.text_count + ' text elements remain)');
                            }
                        }
                        if (response.data.text_count !== undefined) {
                            console.log('📄 Text elements in final PDF:', response.data.text_count);
                        }
                        if (response.data.pattern_count !== undefined) {
                            console.log('📄 Pattern definitions:', response.data.pattern_count);
                        }
                        if (response.data.pattern_fills !== undefined) {
                            console.log('📄 Pattern fills (material outlines):', response.data.pattern_fills);
                            if (response.data.pattern_fills > 0) {
                                console.log('📄 ✅ Material outline patterns: PRESERVED');
                            } else {
                                console.log('📄 ⚠️ Material outline patterns: NOT FOUND (may be lost)');
                            }
                        }
                        if (response.data.message) {
                            console.log('📄 Server message:', response.data.message);
                        }
                        
                        const a = document.createElement('a');
                        a.href = response.data.file_url;
                        a.download = response.data.filename;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);

                        // Show success message
                        const successDiv = document.createElement('div');
                        successDiv.className = 'notice notice-success is-dismissible';
                        successDiv.innerHTML = '<p><strong>✅ PDF Generated!</strong> ' + response.data.message + '</p><p style="margin: 5px 0 0 0;"><small>File saved: ' + response.data.filename + '</small></p>';
                        button.closest('.order-svg-download-section').appendChild(successDiv);
                        
                        setTimeout(() => successDiv.remove(), 10000);
                        console.log('📄 ===== PDF EXPORT COMPLETE =====');
                    } else {
                        console.error('📄 ❌ Server response: ERROR');
                        console.error('📄 Error data:', response.data);
                        alert('Error: ' + (response.data || 'Failed to generate PDF'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('📄 ❌ Network error occurred while generating PDF');
                    console.error('📄 Status:', status);
                    console.error('📄 Error:', error);
                    console.error('📄 XHR:', xhr);
                    alert('Network error occurred while generating PDF: ' + error);
                },
                complete: function() {
                    if (exportVectorPDF._clientSide) {
                        exportVectorPDF._clientSide = false;
                        return;
                    }
                    button.disabled = false;
                    button.innerHTML = originalText;
                }
            });
        }

        function runClientSidePDF(svgContent, orderId, button, originalText) {
            if (typeof generateClientSidePDF !== 'function') {
                alert('PDF export error: generateClientSidePDF not found.');
                button.disabled = false;
                button.innerHTML = originalText;
                return;
            }
            generateClientSidePDF(svgContent, orderId, button).then(function() {
                console.log('📄 ✅ PDF generation completed');
            }).catch(function(err) {
                console.error('📄 ❌ Client-side PDF failed:', err);
                alert('PDF generation failed: ' + (err && err.message) + '. Try SVG export instead.');
                button.disabled = false;
                button.innerHTML = originalText;
            });
        }
        </script>
        <?php
        echo '</div>';
    }

    public function apd_update_order_status()
    {
        // Enable error logging for debugging
        error_log('APD Update Order Status - Start');
        
        if (!current_user_can('manage_options'))
            wp_send_json_error(array('message' => 'forbidden'), 403);
        if (!wp_verify_nonce(isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : '', 'apd_ajax_nonce'))
            wp_send_json_error(array('message' => 'bad nonce'), 403);
        $order_id = intval(isset($_POST['order_id']) ? $_POST['order_id'] : 0);
        $status = sanitize_text_field(isset($_POST['status']) ? $_POST['status'] : '');
        if (!$order_id || !$status)
            wp_send_json_error(array('message' => 'missing'), 400);
        
        error_log('APD Update Order Status - Order: ' . $order_id . ', Status: ' . $status);
        
        // Get old status before updating
        $old_status = get_post_status($order_id);
        
        // Update the order status
        wp_update_post(array('ID' => $order_id, 'post_status' => $status));
        
        // Send email if status changed to confirmed or completed
        if ($old_status !== $status && in_array($status, array('apd_confirmed', 'apd_completed'))) {
            error_log('APD Update Order Status - Status changed from ' . $old_status . ' to ' . $status . ', sending email');
            
            // Get order data for email
            $cart_items_raw = get_post_meta($order_id, 'cart_items', true);
            
            // Decode cart_items if it's a JSON string
            if (is_string($cart_items_raw)) {
                $cart_items = json_decode($cart_items_raw, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log('APD Update Order Status - Failed to decode cart_items JSON: ' . json_last_error_msg());
                    $cart_items = array();
                }
            } else {
                $cart_items = is_array($cart_items_raw) ? $cart_items_raw : array();
            }
            
            $order_data = array(
                'customer_name' => get_post_meta($order_id, 'customer_name', true),
                'customer_email' => get_post_meta($order_id, 'customer_email', true),
                'customer_phone' => get_post_meta($order_id, 'customer_phone', true),
                'customer_address' => get_post_meta($order_id, 'customer_address', true),
                'cart_items' => $cart_items,
                'product_price' => get_post_meta($order_id, 'product_price', true),
                'shipping_cost' => get_post_meta($order_id, 'shipping_cost', true),
                'tax' => get_post_meta($order_id, 'tax', true),
                'order_date' => get_post_meta($order_id, 'order_date', true),
            );
            
            error_log('APD Update Order Status - Email to: ' . $order_data['customer_email']);
            
            try {
                if ($status === 'apd_confirmed') {
                    $result = $this->send_order_status_email($order_id, $order_data, 'confirmed');
                    error_log('APD Update Order Status - Confirmed email sent: ' . ($result ? 'yes' : 'no'));
                } elseif ($status === 'apd_completed') {
                    $result = $this->send_order_status_email($order_id, $order_data, 'completed');
                    error_log('APD Update Order Status - Completed email sent: ' . ($result ? 'yes' : 'no'));
                }
            } catch (Exception $e) {
                error_log('APD Update Order Status - Email error: ' . $e->getMessage());
            }
        }
        
        error_log('APD Update Order Status - Success');
        wp_send_json_success();
    }

    public function apd_add_order_note()
    {
        if (!current_user_can('manage_options'))
            wp_send_json_error(array('message' => 'forbidden'), 403);
        if (!wp_verify_nonce(isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : '', 'apd_ajax_nonce'))
            wp_send_json_error(array('message' => 'bad nonce'), 403);
        $order_id = intval(isset($_POST['order_id']) ? $_POST['order_id'] : 0);
        $text = wp_kses_post(isset($_POST['text']) ? $_POST['text'] : '');
        if (!$order_id || !$text)
            wp_send_json_error(array('message' => 'missing'), 400);
        $notes = get_post_meta($order_id, 'apd_notes', true);
        if (!is_array($notes))
            $notes = array();
        $notes[] = array('time' => current_time('Y-m-d H:i:s'), 'text' => $text, 'user' => get_current_user_id());
        update_post_meta($order_id, 'apd_notes', $notes);
        wp_send_json_success();
    }

    // Rebuild labels for old orders using template_data/text_fields heuristics
    public function apd_rebuild_order_labels()
    {
        if (!current_user_can('manage_options'))
            wp_send_json_error(array('message' => 'forbidden'), 403);
        if (!wp_verify_nonce(isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : '', 'apd_ajax_nonce'))
            wp_send_json_error(array('message' => 'bad nonce'), 403);
        $order_id = intval(isset($_POST['order_id']) ? $_POST['order_id'] : 0);
        if (!$order_id)
            wp_send_json_error(array('message' => 'missing order_id'), 400);
        $fields_display = get_post_meta($order_id, 'fields_display', true);
        $template_fields_array = get_post_meta($order_id, 'template_fields_array', true);
        $template_data = get_post_meta($order_id, 'template_data', true);
        $text_fields = get_post_meta($order_id, 'text_fields', true);
        $rebuilt = array();
        // Prefer existing arrays
        if (is_array($template_fields_array) && !empty($template_fields_array)) {
            foreach ($template_fields_array as $row) {
                $lbl = is_array($row) ? (isset($row['label']) ? $row['label'] : '') : '';
                $val = is_array($row) ? (isset($row['value']) ? $row['value'] : '') : '';
                if ($lbl && $val !== '')
                    $rebuilt[$lbl] = $val;
            }
        } elseif (is_array($template_data) && !empty($template_data)) {
            foreach ($template_data as $fid => $data) {
                $lbl = is_array($data) ? (isset($data['label']) ? $data['label'] : $fid) : $fid;
                $val = is_array($data) ? (isset($data['value']) ? $data['value'] : '') : $data;
                if ($val !== '')
                    $rebuilt[$lbl] = $val;
            }
        } elseif (is_array($text_fields) && !empty($text_fields)) {
            foreach ($text_fields as $fid => $val) {
                if (!$val)
                    continue;
                $lbl = ucwords(str_replace(array('fsc-', '_'), array('', ' '), $fid));
                $rebuilt[$lbl] = $val;
            }
        }
        if (!empty($rebuilt)) {
            update_post_meta($order_id, 'fields_display', $rebuilt);
            // Also create ordered array
            $ordered = array();
            foreach ($rebuilt as $k => $v) {
                $ordered[] = array('id' => $k, 'label' => $k, 'value' => $v);
            }
            update_post_meta($order_id, 'template_fields_array', $ordered);
        }
        wp_send_json_success(array('count' => count($rebuilt)));
    }

    private function generateManufacturingNotes($customization_data)
    {
        $notes = array();

        // Product specifications
        $notes[] = 'PRODUCT SPECIFICATIONS:';
        $notes[] = '- Product: ' . (isset($customization_data['product_name']) ? $customization_data['product_name'] : 'Custom Freight Sign');
        $notes[] = '- Quantity: ' . (isset($customization_data['quantity']) ? $customization_data['quantity'] : 1);
        $notes[] = '- Print Color: ' . (isset($customization_data['print_color']) ? $customization_data['print_color'] : 'Black');
        $notes[] = '- Material: ' . (isset($customization_data['vinyl_material']) ? $customization_data['vinyl_material'] : 'Standard');

        // Text fields
        if (!empty($customization_data['text_fields'])) {
            $notes[] = '';
            $notes[] = 'TEXT CONTENT:';
            foreach ($customization_data['text_fields'] as $field_id => $value) {
                if (is_array($value)) {
                    $notes[] = '- ' . (isset($value['label']) ? $value['label'] : $field_id) . ': ' . (isset($value['value']) ? $value['value'] : '');
                } else {
                    $notes[] = '- ' . ucwords(str_replace('_', ' ', $field_id)) . ': ' . $value;
                }
            }
        }

        // Template data
        if (!empty($customization_data['template_data'])) {
            $notes[] = '';
            $notes[] = 'TEMPLATE ELEMENTS:';
            foreach ($customization_data['template_data'] as $field_id => $value) {
                if (is_array($value)) {
                    $notes[] = '- ' . (isset($value['label']) ? $value['label'] : $field_id) . ': ' . (isset($value['value']) ? $value['value'] : '');
                } else {
                    $notes[] = '- ' . ucwords(str_replace('_', ' ', $field_id)) . ': ' . $value;
                }
            }
        }

        // Visual references
        if (!empty($customization_data['image_url'])) {
            $notes[] = '';
            $notes[] = 'VISUAL REFERENCES:';
            $notes[] = '- Customization Image: ' . $customization_data['image_url'];
        }

        $notes[] = '';
        $notes[] = 'ORDER DATE: ' . current_time('Y-m-d H:i:s');
        $notes[] = 'STATUS: Ready for Production';

        return implode("\n", $notes);
    }

}
