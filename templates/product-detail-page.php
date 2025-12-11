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
$is_customizable = get_post_meta($product_id, '_fsc_customizable', true);
if ($is_customizable === '') {
    $is_customizable = '1'; // Default to customizable for backward compatibility
}

// Get variant data
$variants = get_post_meta($product_id, '_apd_variants', true);
$variants_enabled = is_array($variants) && isset($variants['enabled']) && $variants['enabled'];

// Get all materials for swatches
$all_materials = get_option('apd_materials', array());

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
            <a href="<?php echo home_url('/product'); ?>" class="apd-back-link" id="backToProductsLink">
                ← Back to Products
            </a>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const backLink = document.getElementById('backToProductsLink');
            if (backLink) {
                backLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    const referrer = document.referrer;
                    // Check if there's a referrer and it's not a product-detail page
                    if (referrer && referrer.length > 0 && !referrer.includes('/product-detail/')) {
                        window.history.back();
                    } else {
                        window.location.href = this.href;
                    }
                });
            }
        });
        </script>

        <div class="apd-product-detail-wrapper">
            <div class="apd-product-gallery">
                <div class="apd-main-image" data-product-id="<?php echo $product_id; ?>">
                    <img class="apd-product-image" src="<?php echo esc_url($product_image); ?>" alt="<?php echo esc_attr($product->post_title); ?>">
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

                <!-- Price (only show for non-variant products) -->
                <?php if (!$variants_enabled): ?>
                <div class="apd-product-pricing">
                    <?php if ($has_sale): ?>
                        <span class="apd-sale-price">$<?php echo esc_html($sale_price); ?></span>
                        <span class="apd-regular-price apd-crossed">$<?php echo esc_html($price); ?></span>
                    <?php else: ?>
                        <span class="apd-regular-price">$<?php echo esc_html($display_price ?: '0.00'); ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Description -->
                <?php if ($product->post_content): ?>
                    <div class="apd-product-description">
                        <?php echo wpautop($product->post_content); ?>
                    </div>
                <?php endif; ?>

                <!-- Product Variants (SKU-based) -->
                <?php if ($variants_enabled && !empty($variants['combinations'])): ?>
                    <div class="apd-product-variants">
                        <!-- Material Buttons (Text-based) -->
                        <?php if (!empty($variants['material_options'])): ?>
                            <div class="apd-variant-section">
                                <h4>Select Material</h4>
                                <div class="apd-material-buttons">
                                    <?php foreach ($variants['material_options'] as $mat_idx => $mat): ?>
                                        <button type="button" class="apd-material-btn" 
                                             data-material-id="<?php echo esc_attr($mat['value']); ?>"
                                             data-material-name="<?php echo esc_attr($mat['label']); ?>">
                                            <?php echo esc_html($mat['label']); ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Size Dropdown -->
                        <?php if (!empty($variants['size_options'])): ?>
                            <div class="apd-variant-section">
                                <h4>Select Size</h4>
                                <select id="apd-size-select" class="apd-size-dropdown">
                                    <?php foreach ($variants['size_options'] as $size): ?>
                                        <option value="<?php echo esc_attr($size['value']); ?>">
                                            <?php echo esc_html($size['label']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <!-- Dynamic Price Display -->
                        <div class="apd-variant-pricing">
                            <div class="apd-variant-price-wrapper">
                                <span class="apd-variant-price-label">Price:</span>
                                <span class="apd-variant-price">$<span id="apd-price-display">--</span></span>
                                <span class="apd-variant-regular-price" style="display:none;"></span>
                            </div>
                            <div id="apd-stock-status" class="apd-stock-status in-stock">In Stock</div>
                        </div>

                        <!-- Pass combinations data to JavaScript -->
                        <script>
                        var apdCombinations = <?php echo json_encode($variants['combinations']); ?>;
                        var apdProductId = <?php echo intval($product_id); ?>;
                        </script>
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
                    <!-- Add to Cart button (always shown) -->
                    <button class="apd-detail-add-cart" 
                            data-product-id="<?php echo $product_id; ?>"
                            data-product-name="<?php echo esc_attr($product->post_title); ?>"
                            data-price="<?php echo esc_attr($display_price); ?>">
                        Add to cart
                    </button>
                    
                    <!-- Checkout button (always shown) -->
                    <button class="apd-detail-checkout" 
                            data-product-id="<?php echo $product_id; ?>"
                            data-product-name="<?php echo esc_attr($product->post_title); ?>"
                            data-price="<?php echo esc_attr($display_price); ?>">
                        Check out
                    </button>
                    
                    <!-- Customization button -->
                    <?php if ($variants_enabled && $is_customizable == '1'): ?>
                        <!-- For variant products that are customizable, show Start Customizing button -->
                        <button id="apd-start-customizing" class="apd-detail-customize">
                            Start Customizing
                        </button>
                    <?php elseif (!$variants_enabled && $is_customizable == '1'): ?>
                        <!-- For non-variant customizable products -->
                        <a href="<?php echo home_url('/customizer/' . $product_id . '/'); ?>" 
                           class="apd-detail-customize">
                            Customize this product
                        </a>
                    <?php endif; ?>
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
    max-width: 1140px;
    margin: 0 auto;
    padding: 0 20px;
}

.apd-breadcrumb {
    margin-bottom: 30px;
    font-size: 0.9rem;
    color: #666;
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
    gap: 50px;
    background: white;
    padding: 40px;
    border-radius: 8px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
}

@media (max-width: 968px) {
    .apd-product-detail-wrapper {
        grid-template-columns: 1fr;
        gap: 30px;
        padding: 20px;
    }
    
    .apd-quantity-section {
        grid-template-columns: 1fr;
    }
    
    .apd-variant-pricing {
        flex-direction: column;
        align-items: flex-start;
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
    margin: 0 0 15px 0;
    line-height: 1.2;
}

.apd-product-pricing {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}

.apd-sale-price,
.apd-regular-price {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-foreground);
}

.apd-regular-price.apd-crossed {
    text-decoration: line-through;
    color: #999;
    font-size: 1rem;
    font-weight: 400;
}

.apd-product-description {
    color: #666;
    line-height: 1.6;
    font-size: 1rem;
}

.apd-product-features {
    margin-top: 30px;
    background: #f4f6f8;
    padding: 20px;
    border-radius: 8px;
}

.apd-product-features h3 {
    font-size: 1rem;
    font-weight: 700;
    margin: 0 0 15px 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #444;
}

.apd-feature-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.apd-feature-list li {
    color: #555;
    font-size: 0.9rem;
    padding-left: 0;
    display: flex;
    align-items: center;
}

.apd-feature-list li::before {
    content: '✓';
    color: var(--color-success);
    font-weight: bold;
    margin-right: 10px;
    font-size: 1.1rem;
}

.apd-product-material {
    padding: 12px 16px;
    background: var(--color-background);
    border-radius: 6px;
    font-size: 0.95rem;
}

/* Quantity Section */
.apd-quantity-section {
    display: grid;
    grid-template-columns: 120px 1fr;
    gap: 15px;
    align-items: center;
    margin-bottom: 15px;
}

.apd-quantity-section label {
    font-weight: 600;
    color: var(--color-foreground);
    font-size: 0.9rem;
}

.apd-quantity-controls {
    display: flex;
    align-items: center;
    gap: 0;
    border: 1px solid var(--color-border);
    border-radius: 8px;
    overflow: hidden;
    height: 50px;
}

.apd-qty-btn {
    width: 45px;
    height: 50px;
    border: none;
    background: #f8f8f8;
    color: var(--color-foreground);
    font-size: 1.3rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
}

.apd-qty-btn:hover {
    background: #eee;
}

.apd-quantity-input {
    flex: 1;
    height: 50px;
    border: none;
    text-align: center;
    font-size: 1rem;
    font-weight: 600;
}

.apd-quantity-input:focus {
    outline: none;
}

/* Action Buttons */
.apd-product-actions {
    display: flex !important;
    flex-direction: column;
    gap: 12px;
    margin-top: 20px;
    width: 100%;
    position: relative;
    z-index: 10;
    visibility: visible !important;
}

/* Base button styles */
.apd-detail-add-cart,
.apd-detail-checkout,
.apd-detail-customize {
    display: inline-flex !important;
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
    width: 100%;
    visibility: visible !important;
    opacity: 1 !important;
}

.apd-detail-add-cart {
    background: var(--color-secondary);
    color: white;
}

.apd-detail-add-cart:hover {
    background: hsl(215, 20%, 55%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.apd-detail-checkout {
    background: var(--color-success);
    color: white;
}

.apd-detail-checkout:hover {
    background: hsl(142, 71%, 35%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.apd-detail-customize {
    background: var(--color-primary);
    color: white;
}

.apd-detail-customize:hover {
    background: var(--color-primary-hover);
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

/* Product Variants */
.apd-product-variants {
    border-top: 1px solid var(--color-border);
    padding-top: 25px;
    margin-top: 25px;
}

.apd-variant-section {
    margin-bottom: 25px;
}

.apd-variant-section h4 {
    font-size: 0.9rem;
    font-weight: 600;
    margin: 0 0 12px 0;
    color: #444;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Material Buttons (Text-based with rounded style) */
.apd-material-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.apd-material-btn {
    min-width: 80px;
    padding: 10px 20px;
    border: 2px solid #ddd;
    border-radius: 25px;
    background: white;
    color: #333;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    text-transform: capitalize;
}

.apd-material-btn:hover {
    border-color: #999;
    background: #f8f8f8;
    transform: translateY(-2px);
}

.apd-material-btn.selected {
    border-color: var(--color-primary);
    background: var(--color-primary);
    color: white;
    box-shadow: 0 4px 12px rgba(0, 115, 170, 0.3);
}

/* Size Dropdown */
.apd-size-dropdown {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid var(--color-border);
    border-radius: 6px;
    font-size: 1rem;
    background: white;
    cursor: pointer;
    transition: border-color 0.2s ease;
}

.apd-size-dropdown:focus {
    outline: none;
    border-color: var(--color-primary);
}

.apd-size-dropdown:hover {
    border-color: var(--color-secondary);
}

/* Variant Pricing */
.apd-variant-pricing {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    margin: 25px 0;
    padding: 20px;
    background: #f4f6f8;
    border-radius: 8px;
}

.apd-variant-price-wrapper {
    display: flex;
    align-items: baseline;
    gap: 8px;
}

.apd-variant-price-label {
    font-size: 0.9rem;
    font-weight: 600;
    color: #666;
}

.apd-variant-price {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--color-foreground);
    line-height: 1;
}

.apd-variant-regular-price {
    font-size: 1.5rem;
    text-decoration: line-through;
    color: var(--color-secondary);
    margin-left: 10px;
}

.apd-stock-status {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    white-space: nowrap;
}

.apd-stock-status.in-stock {
    background: #d4edda;
    color: #155724;
}

.apd-stock-status.out-of-stock {
    background: #f8d7da;
    color: #721c24;
}

/* Disabled Start Customizing Button */
#apd-start-customizing.disabled,
#apd-start-customizing:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    background: #999;
}

#apd-start-customizing.disabled:hover,
#apd-start-customizing:disabled:hover {
    background: #999;
    transform: none;
}

</style>

<script>
jQuery(document).ready(function($) {
    // Store base price and selected variants
    // For variant products, base price should be 0 as price comes from variant combo
    const basePrice = <?php echo $variants_enabled ? 0 : floatval($display_price); ?>;
    let selectedMaterial = null;
    let selectedSize = null;
    let materialPrice = 0;
    let sizePrice = 0;

    // Initialize with defaults
    const $firstMaterial = $('.apd-material-swatch.active');
    if ($firstMaterial.length) {
        selectedMaterial = $firstMaterial.data('material');
        materialPrice = parseFloat($firstMaterial.data('price')) || 0;
    }

    const $sizeDropdown = $('#apd-size-selector');
    if ($sizeDropdown.length) {
        const $firstOption = $sizeDropdown.find('option:first');
        selectedSize = $firstOption.val();
        sizePrice = parseFloat($firstOption.data('price')) || 0;
    }

    // Update displayed price
    function updatePrice() {
        const totalPrice = basePrice + materialPrice + sizePrice;
        $('.apd-sale-price, .apd-regular-price').text('$' + totalPrice.toFixed(2));
    }

    // Material swatch selection
    $('.apd-material-swatch').on('click', function() {
        $('.apd-material-swatch').removeClass('active');
        $(this).addClass('active');
        
        selectedMaterial = $(this).data('material');
        materialPrice = parseFloat($(this).data('price')) || 0;
        
        updatePrice();
    });

    // Size selection
    $sizeDropdown.on('change', function() {
        const $selected = $(this).find('option:selected');
        selectedSize = $selected.val();
        sizePrice = parseFloat($selected.data('price')) || 0;
        
        updatePrice();
    });

    // Initialize price on page load
    updatePrice();

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

    // Helper function to get variant data
    function getVariantData() {
        const variantData = {};
        if (selectedMaterial) {
            variantData.material = selectedMaterial;
            variantData.material_price = materialPrice;
        }
        if (selectedSize) {
            variantData.size = selectedSize;
            variantData.size_price = sizePrice;
        }
        return variantData;
    }

    // Add to Cart button
    $('.apd-detail-add-cart').on('click', function() {
        var $btn = $(this);
        var productId = $btn.data('product-id');
        var productName = $btn.data('product-name');
        var quantity = parseInt($('#product-quantity').val()) || 1;
        
        // Determine if this is a variant product
        var isVariantProduct = typeof apdCombinations !== 'undefined' && apdCombinations && apdCombinations.length > 0;
        var finalPrice = basePrice;
        var variantData = {};
        
        if (isVariantProduct) {
            // For variant products, get the selected combination
            var selectedMaterialId = $('.apd-material-btn.selected').data('material-id');
            var selectedMaterialName = $('.apd-material-btn.selected').data('material-name');
            var selectedSizeVal = $('#apd-size-select').val();
            
            // Check which options are available
            var hasMaterialOptions = $('.apd-material-btn').length > 0;
            var hasSizeOptions = $('#apd-size-select').length > 0;
            
            // Set to empty string if no options available
            if (!hasMaterialOptions) {
                selectedMaterialId = '';
                selectedMaterialName = '';
            }
            if (!hasSizeOptions) {
                selectedSizeVal = '';
            }
            
            // Fallback to material mapping if data attribute is not available
            if (selectedMaterialName && typeof apdMaterialNames !== 'undefined' && apdMaterialNames[selectedMaterialId]) {
                selectedMaterialName = apdMaterialNames[selectedMaterialId];
            }
            
            // Validate: must have selection for each available dimension
            if ((hasMaterialOptions && (selectedMaterialId === undefined || selectedMaterialId === null)) ||
                (hasSizeOptions && !selectedSizeVal)) {
                var missingFields = [];
                if (hasMaterialOptions && !selectedMaterialId) missingFields.push('material');
                if (hasSizeOptions && !selectedSizeVal) missingFields.push('size');
                alert('Please select ' + missingFields.join(' and '));
                return;
            }
            
            // Find the matching combination - handle empty strings and flexible size matching
            console.log('[Add to Cart Debug] Looking for combo:', {size: selectedSizeVal, material: selectedMaterialId});
            console.log('[Add to Cart Debug] All combos:', apdCombinations);
            var combo = apdCombinations.find(function(c) {
                var comboSize = String(c.size || '').trim();
                var comboMaterial = String(c.material || '').trim();
                var selSize = String(selectedSizeVal || '').trim();
                var selMaterial = String(selectedMaterialId || '').trim();
                
                // For size: check exact match OR if one value starts with the other (handles "24" vs "24\"w x 12\"h")
                var sizeMatch = false;
                if (!comboSize && !selSize) {
                    sizeMatch = true; // Both empty
                } else if (comboSize && selSize) {
                    sizeMatch = comboSize === selSize || selSize.indexOf(comboSize) === 0 || comboSize.indexOf(selSize) === 0;
                }
                
                var materialMatch = comboMaterial === selMaterial;
                console.log('[Add to Cart Debug] Combo:', c, 'sizeMatch:', sizeMatch, 'materialMatch:', materialMatch);
                return sizeMatch && materialMatch;
            });
            console.log('[Add to Cart Debug] Found:', combo);
            
            if (!combo) {
                alert('Invalid variant selection');
                return;
            }
            
            // Use variant price
            finalPrice = combo.sale_price && parseFloat(combo.sale_price) > 0 
                ? parseFloat(combo.sale_price) 
                : parseFloat(combo.price);
            
            // Build variant data with material name
            variantData = {
                size: combo.size,
                material: selectedMaterialName || 'Material ' + combo.material,
                material_id: combo.material,
                sku: combo.sku,
                price: finalPrice
            };
        } else {
            // For non-variant products, use old logic
            finalPrice = basePrice + materialPrice + sizePrice;
            variantData = getVariantData();
        }

        $btn.prop('disabled', true).text('Adding...');

        // Get product image for preview
        var productImage = $('.apd-product-image').attr('src') || '';

        // Add to cart with variant data
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
                    product_price: finalPrice,
                    preview_image_url: productImage,
                    variants: variantData
                }
            },
            success: function(response) {
                if (response.success) {
                    $btn.text('Added to cart!');
                    
                    // Update cart count if available
                    if (response.data && response.data.cart_count) {
                        var count = response.data.cart_count;
                        
                        // Update all cart count elements
                        $('.apd-cart-count, .cart-count').text(count);
                        $('#apd-cart-count-display').text(count);
                        
                        // Update floating cart count
                        var $floatingCount = $('#apd-floating-cart-count');
                        if ($floatingCount.length) {
                            $floatingCount.text(count);
                            if (count > 0) {
                                $floatingCount.removeClass('hidden');
                            } else {
                                $floatingCount.addClass('hidden');
                            }
                        }
                        
                        // Trigger custom event for other components
                        $(document).trigger('apd_cart_updated');
                        if (window.dispatchEvent) {
                            window.dispatchEvent(new Event('apd_cart_updated'));
                        }
                    }
                    
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
        var quantity = parseInt($('#product-quantity').val()) || 1;
        
        // Determine if this is a variant product
        var isVariantProduct = typeof apdCombinations !== 'undefined' && apdCombinations && apdCombinations.length > 0;
        var finalPrice = basePrice;
        var variantData = {};
        
        if (isVariantProduct) {
            // For variant products, get the selected combination
            var selectedMaterialId = $('.apd-material-btn.selected').data('material-id');
            var selectedMaterialName = $('.apd-material-btn.selected').data('material-name');
            var selectedSizeVal = $('#apd-size-select').val();
            
            // Check which options are available
            var hasMaterialOptions = $('.apd-material-btn').length > 0;
            var hasSizeOptions = $('#apd-size-select').length > 0;
            
            // Set to empty string if no options available
            if (!hasMaterialOptions) {
                selectedMaterialId = '';
                selectedMaterialName = '';
            }
            if (!hasSizeOptions) {
                selectedSizeVal = '';
            }
            
            // Fallback to material mapping if data attribute is not available
            if (selectedMaterialName && typeof apdMaterialNames !== 'undefined' && apdMaterialNames[selectedMaterialId]) {
                selectedMaterialName = apdMaterialNames[selectedMaterialId];
            }
            
            // Validate: must have selection for each available dimension
            if ((hasMaterialOptions && (selectedMaterialId === undefined || selectedMaterialId === null)) ||
                (hasSizeOptions && !selectedSizeVal)) {
                var missingFields = [];
                if (hasMaterialOptions && !selectedMaterialId) missingFields.push('material');
                if (hasSizeOptions && !selectedSizeVal) missingFields.push('size');
                alert('Please select ' + missingFields.join(' and '));
                return;
            }
            
            // Find the matching combination - handle empty strings and flexible size matching
            console.log('[Checkout Debug] Looking for combo:', {size: selectedSizeVal, material: selectedMaterialId});
            console.log('[Checkout Debug] All combos:', apdCombinations);
            var combo = apdCombinations.find(function(c) {
                var comboSize = String(c.size || '').trim();
                var comboMaterial = String(c.material || '').trim();
                var selSize = String(selectedSizeVal || '').trim();
                var selMaterial = String(selectedMaterialId || '').trim();
                
                // For size: check exact match OR if one value starts with the other (handles "24" vs "24\"w x 12\"h")
                var sizeMatch = false;
                if (!comboSize && !selSize) {
                    sizeMatch = true; // Both empty
                } else if (comboSize && selSize) {
                    sizeMatch = comboSize === selSize || selSize.indexOf(comboSize) === 0 || comboSize.indexOf(selSize) === 0;
                }
                
                var materialMatch = comboMaterial === selMaterial;
                console.log('[Checkout Debug] Combo:', c, 'sizeMatch:', sizeMatch, 'materialMatch:', materialMatch);
                return sizeMatch && materialMatch;
            });
            console.log('[Checkout Debug] Found:', combo);
            
            if (!combo) {
                alert('Invalid variant selection');
                return;
            }
            
            // Use variant price
            finalPrice = combo.sale_price && parseFloat(combo.sale_price) > 0 
                ? parseFloat(combo.sale_price) 
                : parseFloat(combo.price);
            
            // Build variant data with material name
            variantData = {
                size: combo.size,
                material: selectedMaterialName || 'Material ' + combo.material,
                material_id: combo.material,
                sku: combo.sku,
                price: finalPrice
            };
        } else {
            // For non-variant products, use old logic
            finalPrice = basePrice + materialPrice + sizePrice;
            variantData = getVariantData();
        }

        // Get product image for preview
        var productImage = $('.apd-product-image').attr('src') || '';

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
                    product_price: finalPrice,
                    preview_image_url: productImage,
                    variants: variantData
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

    // Update Customize button to include variant data in URL
    <?php if ($is_customizable == '1'): ?>
    $('.apd-detail-customize').on('click', function(e) {
        e.preventDefault();
        const baseUrl = $(this).attr('href');
        const variantData = getVariantData();
        
        let url = baseUrl;
        if (Object.keys(variantData).length > 0) {
            const params = new URLSearchParams(variantData);
            url += (baseUrl.includes('?') ? '&' : '?') + params.toString();
        }
        
        window.location.href = url;
    });
    <?php endif; ?>
});
</script>

<?php get_footer(); ?>
