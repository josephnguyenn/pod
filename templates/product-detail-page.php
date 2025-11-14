<?php
/**
 * Template for product detail page
 * This page shows product details with options to Add to Cart, Customize, or Checkout
 */

get_header();

// Get product ID from URL parameter
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    echo '<div class="apd-error-message">
        <h2>Product Not Found</h2>
        <p>No product ID provided. Please select a product from our <a href="' . home_url('/products/') . '">product list</a>.</p>
    </div>';
    get_footer();
    exit;
}

// Get product data
$product = get_post($product_id);
if (!$product || $product->post_type !== 'apd_product') {
    echo '<div class="apd-error-message">
        <h2>Product Not Found</h2>
        <p>The requested product does not exist. Please browse our <a href="' . home_url('/products/') . '">product catalog</a>.</p>
    </div>';
    get_footer();
    exit;
}

// Get product meta
$price = get_post_meta($product_id, '_fsc_price', true);
$sale_price = get_post_meta($product_id, '_fsc_sale_price', true);
$category = get_post_meta($product_id, '_fsc_category', true);
$features = get_post_meta($product_id, '_fsc_features', true);
$material = get_post_meta($product_id, '_fsc_material', true);
$logo_url = get_post_meta($product_id, '_fsc_logo_file', true);
$thumbnail_id = get_post_meta($product_id, '_fsc_thumbnail_id', true);

// Get product image
$product_image = '';
if ($thumbnail_id) {
    $product_image = wp_get_attachment_image_url($thumbnail_id, 'large');
}
if (!$product_image) {
    $product_image = get_the_post_thumbnail_url($product_id, 'large');
}
if (!$product_image && $logo_url) {
    $product_image = $logo_url;
}
if (!$product_image) {
    $product_image = APD_PLUGIN_URL . 'assets/images/placeholder.png';
}

// Calculate display price
$display_price = !empty($sale_price) ? $sale_price : $price;
$has_sale = !empty($sale_price) && floatval($sale_price) < floatval($price);

?>

<div class="apd-product-detail-page">
    <div class="apd-container">
        <!-- Back to Products Link -->
        <div class="apd-breadcrumb">
            <a href="<?php echo home_url('/product'); ?>" class="apd-back-link">
                ← Back to Products
            </a>
        </div>

        <div class="apd-product-detail-wrapper">
            <!-- Product Image Gallery -->
            <div class="apd-product-gallery">
                <div class="apd-main-image">
                    <?php if (has_post_thumbnail($product_id)): ?>
                        <img class="apd-product-image" src="<?php echo get_the_post_thumbnail_url($product_id, 'large'); ?>" alt="<?php echo esc_attr($product->post_title); ?>">
                    <?php else: ?>
                        <img class="apd-product-image" src="<?php echo esc_url(APD_PLUGIN_URL . 'assets/images/placeholder.png'); ?>" alt="<?php echo esc_attr($product->post_title); ?>">
                    <?php endif; ?>
                    <?php if ($has_sale): ?>
                        <div class="apd-sale-badge">Sale</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Product Info -->
            <div class="apd-product-info">
                <!-- Category -->
                <?php if ($category): ?>
                    <div class="apd-product-category">
                        <?php echo esc_html($category); ?>
                    </div>
                <?php endif; ?>

                <!-- Title -->
                <h1 class="apd-product-title">
                    <?php echo esc_html($product->post_title); ?>
                </h1>

                <!-- Price -->
                <div class="apd-product-pricing">
                    <?php if ($has_sale): ?>
                        <span class="apd-sale-price">$<?php echo esc_html($sale_price); ?></span>
                        <span class="apd-regular-price apd-crossed">$<?php echo esc_html($price); ?></span>
                    <?php else: ?>
                        <span class="apd-regular-price">$<?php echo esc_html($display_price ?: '0.00'); ?></span>
                    <?php endif; ?>
                </div>

                <!-- Description -->
                <?php if ($product->post_content): ?>
                    <div class="apd-product-description">
                        <?php echo wpautop($product->post_content); ?>
                    </div>
                <?php endif; ?>

                <!-- Features -->
                <?php if ($features && is_array($features) && count($features) > 0): ?>
                    <div class="apd-product-features">
                        <h3>Features & Benefits</h3>
                        <ul class="apd-feature-list">
                            <?php foreach ($features as $feature): ?>
                                <li>✓ <?php echo esc_html($feature); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Material Info -->
                <?php if ($material): ?>
                    <div class="apd-product-material">
                        <strong>Material:</strong> <?php echo esc_html($material); ?>
                    </div>
                <?php endif; ?>

                <!-- Quantity Selector -->
                <div class="apd-quantity-section">
                    <label for="product-quantity">Quantity:</label>
                    <div class="apd-quantity-controls">
                        <button type="button" class="apd-qty-btn apd-qty-minus" data-action="decrease">−</button>
                        <input type="number" 
                               id="product-quantity" 
                               class="apd-quantity-input" 
                               value="1" 
                               min="1" 
                               max="100">
                        <button type="button" class="apd-qty-btn apd-qty-plus" data-action="increase">+</button>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="apd-product-actions">
                    <button class="apd-btn apd-btn-secondary apd-detail-add-cart" 
                            data-product-id="<?php echo $product_id; ?>"
                            data-product-name="<?php echo esc_attr($product->post_title); ?>"
                            data-product-price="<?php echo esc_attr($display_price); ?>">
                        Add to cart
                    </button>
                    
                    <button class="apd-btn apd-btn-checkout apd-detail-checkout" 
                            data-product-id="<?php echo $product_id; ?>"
                            data-product-name="<?php echo esc_attr($product->post_title); ?>"
                            data-product-price="<?php echo esc_attr($display_price); ?>">
                        Check out
                    </button>
                    
                    <a href="<?php echo home_url('/customizer/' . $product_id . '/'); ?>" 
                       class="apd-btn apd-btn-primary apd-detail-customize">
                        Customize this product
                    </a>
                </div>

                <!-- Trust Badges -->
                <div class="apd-trust-badges">
                    <div class="apd-badge">
                        <span class="apd-badge-icon">🔒</span>
                        <span class="apd-badge-text">Secure Checkout</span>
                    </div>
                    <div class="apd-badge">
                        <span class="apd-badge-icon">🚚</span>
                        <span class="apd-badge-text">Fast Shipping</span>
                    </div>
                    <div class="apd-badge">
                        <span class="apd-badge-icon">✅</span>
                        <span class="apd-badge-text">Quality Guaranteed</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
:root {
    --color-primary: hsl(199, 89%, 48%);
    --color-primary-hover: hsl(199, 89%, 38%);
    --color-secondary: hsl(215, 20%, 65%);
    --color-background: hsl(0, 0%, 98%);
    --color-foreground: hsl(0, 0%, 9%);
    --color-border: hsl(214, 32%, 91%);
    --color-success: hsl(142, 71%, 45%);
    --color-destructive: hsl(0, 84%, 60%);
}

.apd-product-detail-page {
    min-height: 100vh;
    background: var(--color-background);
    padding: 40px 0;
}

.apd-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.apd-breadcrumb {
    margin-bottom: 30px;
}

.apd-back-link {
    color: var(--color-primary);
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.apd-back-link:hover {
    color: var(--color-primary-hover);
    transform: translateX(-4px);
}

.apd-product-detail-wrapper {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    background: white;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

@media (max-width: 968px) {
    .apd-product-detail-wrapper {
        grid-template-columns: 1fr;
        gap: 40px;
    }
}

/* Product Gallery */
.apd-product-gallery {
    position: relative;
}

.apd-main-image {
    position: relative;
    width: 100%;
    padding-top: 100%; /* 1:1 aspect ratio */
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid var(--color-border);
    background: #f5f5f5;
}

.apd-card-preview {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.apd-card-preview .apd-template-canvas {
    position: absolute;
    left: 50%;
    top: 50%;
    transform-origin: center center;
}

.apd-product-image {
    width: 100%;
    height: 100%;
    position: absolute;
    top: 0;
    left: 0;
    object-fit: contain;
    display: block;
}

.apd-sale-badge {
    position: absolute;
    top: 20px;
    right: 20px;
    background: var(--color-destructive);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 700;
    z-index: 2;
}

/* Product Info */
.apd-product-info {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.apd-product-category {
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--color-secondary);
}

.apd-product-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--color-foreground);
    margin: 0;
    line-height: 1.3;
}

.apd-product-pricing {
    display: flex;
    align-items: center;
    gap: 12px;
}

.apd-sale-price {
    font-size: 2rem;
    font-weight: 900;
    color: var(--color-destructive);
}

.apd-regular-price {
    font-size: 2rem;
    font-weight: 900;
    color: var(--color-foreground);
}

.apd-regular-price.apd-crossed {
    text-decoration: line-through;
    color: var(--color-secondary);
    font-size: 1.5rem;
}

.apd-product-description {
    color: #666;
    line-height: 1.6;
    font-size: 1rem;
}

.apd-product-features h3 {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0 0 12px 0;
}

.apd-feature-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.apd-feature-list li {
    color: #555;
    font-size: 0.95rem;
    padding-left: 0;
}

.apd-product-material {
    padding: 12px 16px;
    background: var(--color-background);
    border-radius: 6px;
    font-size: 0.95rem;
}

/* Quantity Section */
.apd-quantity-section {
    display: flex;
    align-items: center;
    gap: 16px;
}

.apd-quantity-section label {
    font-weight: 600;
    color: var(--color-foreground);
}

.apd-quantity-controls {
    display: flex;
    align-items: center;
    gap: 0;
    border: 2px solid var(--color-border);
    border-radius: 6px;
    overflow: hidden;
}

.apd-qty-btn {
    width: 40px;
    height: 40px;
    border: none;
    background: white;
    color: var(--color-foreground);
    font-size: 1.25rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
}

.apd-qty-btn:hover {
    background: var(--color-background);
}

.apd-quantity-input {
    width: 60px;
    height: 40px;
    border: none;
    border-left: 1px solid var(--color-border);
    border-right: 1px solid var(--color-border);
    text-align: center;
    font-size: 1rem;
    font-weight: 600;
}

.apd-quantity-input:focus {
    outline: none;
}

/* Action Buttons */
.apd-product-actions {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-top: 10px;
}

.apd-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 24px;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    text-align: center;
}

.apd-btn-primary {
    background: var(--color-primary);
    color: white;
}

.apd-btn-primary:hover {
    background: var(--color-primary-hover);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.apd-btn-secondary {
    background: var(--color-secondary);
    color: white;
}

.apd-btn-secondary:hover {
    background: hsl(215, 20%, 55%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.apd-btn-checkout {
    background: var(--color-success);
    color: white;
}

.apd-btn-checkout:hover {
    background: hsl(142, 71%, 35%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Trust Badges */
.apd-trust-badges {
    display: flex;
    gap: 20px;
    padding: 20px;
    background: var(--color-background);
    border-radius: 8px;
    margin-top: 10px;
}

.apd-badge {
    display: flex;
    align-items: center;
    gap: 8px;
}

.apd-badge-icon {
    font-size: 1.25rem;
}

.apd-badge-text {
    font-size: 0.875rem;
    font-weight: 600;
    color: #555;
}

/* Error Message */
.apd-error-message {
    background: white;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    text-align: center;
    max-width: 600px;
    margin: 60px auto;
}

.apd-error-message h2 {
    color: var(--color-destructive);
    margin-bottom: 15px;
}

.apd-error-message p {
    color: #666;
    font-size: 1.1rem;
}

.apd-error-message a {
    color: var(--color-primary);
    text-decoration: none;
    font-weight: 600;
}

.apd-error-message a:hover {
    text-decoration: underline;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Quantity controls
    $('.apd-qty-minus').on('click', function() {
        var $input = $('#product-quantity');
        var value = parseInt($input.val()) || 1;
        if (value > 1) {
            $input.val(value - 1);
        }
    });

    $('.apd-qty-plus').on('click', function() {
        var $input = $('#product-quantity');
        var value = parseInt($input.val()) || 1;
        if (value < 100) {
            $input.val(value + 1);
        }
    });

    // Add to Cart button
    $('.apd-detail-add-cart').on('click', function() {
        var $btn = $(this);
        var productId = $btn.data('product-id');
        var productName = $btn.data('product-name');
        var productPrice = $btn.data('product-price');
        var quantity = parseInt($('#product-quantity').val()) || 1;

        $btn.prop('disabled', true).text('Adding...');

        $.ajax({
            url: apd_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'apd_add_to_cart',
                nonce: apd_ajax.nonce,
                product_id: productId,
                quantity: quantity,
                customization_data: {
                    product_name: productName,
                    product_price: productPrice
                }
            },
            success: function(response) {
                if (response.success) {
                    $btn.text('Added to cart!');
                    setTimeout(function() {
                        $btn.prop('disabled', false).text('Add to cart');
                    }, 2000);
                } else {
                    alert('Error adding to cart: ' + (response.data || 'Unknown error'));
                    $btn.prop('disabled', false).text('Add to cart');
                }
            },
            error: function() {
                alert('Error adding to cart. Please try again.');
                $btn.prop('disabled', false).text('Add to cart');
            }
        });
    });

    // Buy Now button
    $('.apd-detail-checkout').on('click', function() {
        var $btn = $(this);
        var productId = $btn.data('product-id');
        var productName = $btn.data('product-name');
        var productPrice = $btn.data('product-price');
        var quantity = parseInt($('#product-quantity').val()) || 1;

        // Add to cart first, then redirect to checkout
        $.ajax({
            url: apd_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'apd_add_to_cart',
                nonce: apd_ajax.nonce,
                product_id: productId,
                quantity: quantity,
                customization_data: {
                    product_name: productName,
                    product_price: productPrice
                }
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = apd_ajax.checkout_url || '<?php echo home_url('/checkout/'); ?>';
                } else {
                    alert('Error: ' + (response.data || 'Could not add to cart'));
                }
            },
            error: function() {
                alert('Error processing request. Please try again.');
            }
        });
    });
});
</script>

<?php get_footer(); ?>
