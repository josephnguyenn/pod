<?php
/**
 * Orders Template
 * Template for displaying user orders
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
if (!$user_id) {
    echo '<div class="apd-error">Please log in to view your orders.</div>';
    return;
}

// Get user orders from CPT `apd_order` using customer_email match
$current_user = wp_get_current_user();
$current_email = $current_user && !empty($current_user->user_email) ? $current_user->user_email : '';

$query_args = array(
    'post_type'      => 'apd_order',
    'post_status'    => array('apd_pending','in_production','quality_check','completed','cancelled'),
    'posts_per_page' => 50,
    'orderby'        => 'date',
    'order'          => 'DESC',
);
if ($current_email) {
    $query_args['meta_query'] = array(
        array(
            'key'   => 'customer_email',
            'value' => $current_email,
            'compare' => '='
        )
    );
}
$orders_q = new WP_Query($query_args);
?>

<div class="apd-orders-page">
    <div class="apd-orders-header">
        <h1>My Orders</h1>
        <p>Track and manage your orders</p>
    </div>

    <?php if (!$orders_q->have_posts()): ?>
        <div class="apd-empty-orders">
            <div class="apd-empty-icon">📦</div>
            <h3>No orders yet</h3>
            <p>You haven't placed any orders yet. Start shopping to see your orders here.</p>
            <a href="<?php echo home_url(get_option('apd_products_url', '/products/')); ?>" class="apd-btn apd-btn-primary">
                Start Shopping
            </a>
        </div>
    <?php else: ?>
        <div class="apd-orders-list">
            <?php while ($orders_q->have_posts()): $orders_q->the_post(); $order_id = get_the_ID(); ?>
                <?php
                $created_at = get_the_date('Y-m-d H:i:s', $order_id);
                $status = get_post_status($order_id);
                $cart_items = get_post_meta($order_id, 'cart_items', true);
                if (is_string($cart_items)) {
                    $decoded_items = json_decode($cart_items, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_items)) { $cart_items = $decoded_items; }
                }
                if (!is_array($cart_items)) { $cart_items = array(); }
                // Normalize various shapes: {id: item}, {items: [...]}, [[...]] etc.
                if (isset($cart_items['items']) && is_array($cart_items['items'])) {
                    $cart_items = $cart_items['items'];
                }
                // If associative map, convert to sequential array
                $keys = array_keys($cart_items);
                $is_assoc = array_filter($keys, 'is_string') ? true : false;
                if ($is_assoc) { $cart_items = array_values($cart_items); }
                $order_total = get_post_meta($order_id, 'cart_total', true);
                if (!is_numeric($order_total)) {
                    $order_total = 0;
                    foreach ($cart_items as $ci) {
                        $order_total += isset($ci['total']) ? (float)$ci['total'] : ((float)($ci['price'] ?? $ci['product_price'] ?? 0) * (int)($ci['quantity'] ?? 1));
                    }
                }
                ?>
                <div class="apd-order-card" data-order-id="<?php echo esc_attr($order_id); ?>">
                    <div class="apd-order-header">
                        <div class="apd-order-info">
                            <h3 class="apd-order-id">Order #<?php echo esc_html($order_id); ?></h3>
                            <p class="apd-order-date"><?php echo date('M j, Y', strtotime($created_at)); ?></p>
                        </div>
                        <div class="apd-order-status">
                            <span class="apd-status-badge apd-status-<?php echo esc_attr($status); ?>"><?php echo esc_html(ucfirst($status)); ?></span>
                        </div>
                    </div>

                    <div class="apd-order-items">
                        <div class="apd-order-item-count" style="margin:8px 0; color:#64748b; font-size:12px;">Items: <?php echo count($cart_items); ?></div>
                        <?php foreach ($cart_items as $item): ?>
                            <?php
                            // Normalize customization_data possibly stored as JSON string
                            $cd = array();
                            if (!empty($item['customization_data'])) {
                                if (is_array($item['customization_data'])) {
                                    $cd = $item['customization_data'];
                                } elseif (is_string($item['customization_data'])) {
                                    $decoded = json_decode($item['customization_data'], true);
                                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                        $cd = $decoded;
                                    }
                                }
                            }
                            // Resolve preview image URL with robust fallbacks
                            $imgUrl = '';
                            if (!empty($item['preview_image_svg'])) $imgUrl = $item['preview_image_svg'];
                            elseif (!empty($item['preview_image_png'])) $imgUrl = $item['preview_image_png'];
                            elseif (!empty($item['preview_image_url'])) $imgUrl = $item['preview_image_url'];
                            elseif (!empty($item['customization_image_url'])) $imgUrl = $item['customization_image_url'];
                            elseif (!empty($item['image_url'])) $imgUrl = $item['image_url'];
                            if (!$imgUrl && !empty($cd)) {
                                if (!empty($cd['preview_image_svg'])) $imgUrl = $cd['preview_image_svg'];
                                elseif (!empty($cd['preview_image_png'])) $imgUrl = $cd['preview_image_png'];
                                elseif (!empty($cd['preview_image_url'])) $imgUrl = $cd['preview_image_url'];
                                elseif (!empty($cd['customization_image_url'])) $imgUrl = $cd['customization_image_url'];
                                elseif (!empty($cd['image_url'])) $imgUrl = $cd['image_url'];
                            }
                            // Derive display fields
                            $pname = !empty($item['product_name']) ? $item['product_name'] : 'Product';
                            $qty = isset($item['quantity']) ? (int)$item['quantity'] : 1;
                            $price = isset($item['total']) ? (float)$item['total'] : ((float)($item['price'] ?? $item['product_price'] ?? 0) * $qty);
                            ?>
                            <div class="apd-order-item">
                                <div class="apd-order-item-preview">
                                    <?php if (!empty($imgUrl)): ?>
                                        <img src="<?php echo esc_attr($imgUrl); ?>" alt="Product Preview" class="apd-preview-img" />
                                    <?php else: ?>
                                        <div class="apd-preview-placeholder"><span>📦</span></div>
                                    <?php endif; ?>
                                </div>
                                <div class="apd-order-item-details">
                                    <h4 class="apd-order-item-name"><?php echo esc_html($pname); ?></h4>
                                    <p class="apd-order-item-specs">
                                        <?php
                                        $specs = array();
                                        
                                        // Show variant info first if available (SKU-based variants)
                                        if (!empty($cd['variant_info'])) {
                                            if (!empty($cd['variant_info']['size'])) $specs[] = 'Size: ' . esc_html($cd['variant_info']['size']);
                                            if (!empty($cd['variant_info']['sku'])) $specs[] = 'SKU: ' . esc_html($cd['variant_info']['sku']);
                                        }
                                        
                                        // Show customization material/color
                                        if (!empty($item['vinyl_material'])) $specs[] = 'Material: ' . esc_html($item['vinyl_material']);
                                        if (!empty($item['print_color'])) $specs[] = 'Color: ' . esc_html($item['print_color']);
                                        
                                        // Fallback to customization_data fields
                                        if (!empty($cd)) {
                                            if (!empty($cd['material']) && empty($specs['material'])) $specs[] = 'Material: ' . esc_html($cd['material']);
                                            if (!empty($cd['color']) && empty($specs['color'])) $specs[] = 'Color: ' . esc_html($cd['color']);
                                            if (!empty($cd['size']) && empty($cd['variant_info'])) $specs[] = 'Size: ' . esc_html($cd['size']);
                                        }
                                        echo implode(' • ', $specs);
                                        ?>
                                    </p>
                                    <div class="apd-order-item-meta">
                                        <span class="apd-order-item-quantity">Qty: <?php echo esc_html($qty); ?></span>
                                        <span class="apd-order-item-price">$<?php echo number_format($price, 2); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="apd-order-footer">
                        <div class="apd-order-total"><strong>Total: $<?php echo number_format((float)$order_total, 2); ?></strong></div>
                        <div class="apd-order-actions">
                            <button class="apd-btn apd-btn-secondary apd-btn-view-order" data-order-id="<?php echo esc_attr($order_id); ?>">
                                View Details
                            </button>
                            <?php if ($status === 'pending'): ?>
                                <button class="apd-btn apd-btn-primary apd-btn-pay-order" data-order-id="<?php echo esc_attr($order_id); ?>">
                                    Pay Now
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // View order details
    $('.apd-btn-view-order').on('click', function() {
        const orderId = $(this).data('order-id');
        const $orderCard = $(this).closest('.apd-order-card');
        
        // Toggle order details visibility
        $orderCard.toggleClass('apd-order-expanded');
        
        if ($orderCard.hasClass('apd-order-expanded')) {
            $(this).text('Hide Details');
        } else {
            $(this).text('View Details');
        }
    });

    // Pay order
    $('.apd-btn-pay-order').on('click', function() {
        const orderId = $(this).data('order-id');
        
        if (confirm('Proceed to payment for order #' + orderId + '?')) {
            // Redirect to checkout with order ID
            const checkoutUrl = '<?php echo home_url(get_option('apd_checkout_url', '/checkout/')); ?>';
            window.location.href = checkoutUrl + '?order=' + orderId;
        }
    });
});
</script>
