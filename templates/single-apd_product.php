<?php
/**
 * Template for single product pages
 */

get_header(); ?>

<div class="apd-single-product-wrapper">
    <div class="apd-container">
        <?php while (have_posts()) : the_post(); ?>
            <article id="product-<?php the_ID(); ?>" <?php post_class('apd-single-product'); ?>>
                
                <!-- Product Header -->
                <div class="apd-product-header">
                    <h1 class="apd-product-title"><?php the_title(); ?></h1>
                    <?php if (get_post_meta(get_the_ID(), '_fsc_price', true)): ?>
                        <div class="apd-product-price">$<?php echo esc_html(get_post_meta(get_the_ID(), '_fsc_price', true)); ?></div>
                    <?php endif; ?>
                </div>

                <!-- Product Content -->
                <div class="apd-product-content">
                    
                    <!-- Product Image/Gallery -->
                    <div class="apd-product-gallery">
                        <?php if (has_post_thumbnail()): ?>
                            <div class="apd-product-image">
                                <?php the_post_thumbnail('large'); ?>
                            </div>
                        <?php else: ?>
                            <div class="apd-product-image">
                                <img src="<?php echo APD_PLUGIN_URL; ?>assets/images/placeholder.png" alt="<?php the_title(); ?>" />
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Product Details -->
                    <div class="apd-product-details">
                        
                        <!-- Product Description -->
                        <?php if (get_the_content()): ?>
                            <div class="apd-product-description">
                                <h3>Description</h3>
                                <?php the_content(); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Product Features -->
                        <?php 
                        $features = get_post_meta(get_the_ID(), '_fsc_features', true);
                        if ($features && is_array($features)): 
                        ?>
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
                        <?php 
                        $material = get_post_meta(get_the_ID(), '_fsc_material', true);
                        if ($material): 
                        ?>
                            <div class="apd-product-specs">
                                <h3>Specifications</h3>
                                <div class="apd-spec-item">
                                    <strong>Material:</strong> <?php echo esc_html($material); ?>
                                </div>
                                <?php if (get_post_meta(get_the_ID(), '_fsc_category', true)): ?>
                                    <div class="apd-spec-item">
                                        <strong>Category:</strong> <?php echo esc_html(get_post_meta(get_the_ID(), '_fsc_category', true)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Customizer Actions -->
                        <div class="apd-product-actions">
                            <div class="apd-action-buttons">
                                <a href="<?php echo home_url('/customizer/' . get_the_ID() . '/'); ?>" 
                                   class="apd-btn apd-btn-primary apd-btn-customize">
                                    🎨 Customize This Product
                                </a>
                                
                                <button class="apd-btn apd-btn-secondary apd-btn-add-cart" 
                                        data-product-id="<?php the_ID(); ?>"
                                        data-product-name="<?php echo esc_attr(get_the_title()); ?>"
                                        data-product-price="<?php echo esc_attr(get_post_meta(get_the_ID(), '_fsc_price', true) ?: '29.99'); ?>">
                                    🛒 Add to Cart
                                </button>
                            </div>
                            
                                <button class="apd-btn apd-btn-primary apd-btn-buy-now" 
                                        data-product-id="<?php echo esc_attr($post->ID); ?>"
                                        data-product-name="<?php echo esc_attr($post->post_title); ?>"
                                        data-product-price="<?php echo esc_attr($product_price); ?>">
                                        Buy Now
                                </button>

                            <!-- Quantity Selector -->
                            <div class="apd-quantity-selector">
                                <label for="apd-quantity">Quantity:</label>
                                <input type="number" id="apd-quantity" value="1" min="1" max="100" class="apd-quantity-input">
                            </div>
                        </div>

                        <!-- Related Products -->
                        <?php
                        $related_products = get_posts(array(
                            'post_type' => 'apd_product',
                            'posts_per_page' => 4,
                            'post__not_in' => array(get_the_ID()),
                            'meta_key' => '_fsc_category',
                            'meta_value' => get_post_meta(get_the_ID(), '_fsc_category', true)
                        ));
                        
                        if ($related_products): ?>
                            <div class="apd-related-products">
                                <h3>Related Products</h3>
                                <div class="apd-related-grid">
                                    <?php foreach ($related_products as $related): ?>
                                        <div class="apd-related-item">
                                            <a href="<?php echo get_permalink($related->ID); ?>">
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

                    </div>
                </div>
                
            </article>
        <?php endwhile; ?>
    </div>
</div>

                        <script>
                        // Buy Now handler - creates single-item order and redirects (single product page)
                        document.addEventListener('DOMContentLoaded', function(){
                            try {
                                var $ = window.jQuery || null;
                                function handleBuyNowClick(e){
                                    e.preventDefault();
                                    var btn = e.currentTarget;
                                    try{ btn.disabled = true; btn.innerText = 'Processing...'; } catch(_){}
                                    var productId = btn.getAttribute('data-product-id');
                                    var productName = btn.getAttribute('data-product-name');
                                    var productPrice = parseFloat(btn.getAttribute('data-product-price')||0)||0;
                                    var qtyEl = document.getElementById('apd-quantity');
                                    var quantity = qtyEl ? parseInt(qtyEl.value||1,10) : 1;

                                    var item = {
                                        product_id: productId,
                                        product_name: productName,
                                        product_price: productPrice,
                                        price: productPrice,
                                        quantity: quantity,
                                        print_color: 'black',
                                        vinyl_material: 'Standard'
                                    };

                                    try {
                                        localStorage.setItem('apd_checkout_payload_oneclick', JSON.stringify(item));
                                        try { localStorage.removeItem('apd_cart'); } catch(_) {}
                                        var checkoutUrl = (window.apd_ajax && window.apd_ajax.checkout_url) ? window.apd_ajax.checkout_url : '/checkout/';
                                        if (checkoutUrl.indexOf('?') === -1) checkoutUrl += '?instant=true'; else checkoutUrl += '&instant=true';
                                        window.location.href = checkoutUrl;
                                        return;
                                    } catch (err) {
                                        console.error('Buy Now local persist error', err);
                                        try{ btn.disabled = false; btn.innerText = 'Buy Now'; } catch(_){}
                                        alert('Failed to start checkout');
                                    }
                                }
                                var buyBtns = document.querySelectorAll('.apd-btn-buy-now');
                                buyBtns.forEach(function(b){ b.addEventListener('click', handleBuyNowClick); });
                            } catch(e){ console.error('Buy Now init error', e); }
                        });
                        </script>

<style>
.apd-single-product-wrapper {
    min-height: 100vh;
    background: #f8f9fa;
    padding: 40px 0;
}

.apd-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.apd-single-product {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    overflow: hidden;
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
    display: inline-block;
    transition: all 0.3s ease;
    cursor: pointer;
}

.apd-btn-primary {
    background: #667eea;
    color: white;
}

.apd-btn-primary:hover {
    background: #5a6fd8;
    transform: translateY(-2px);
}

.apd-btn-secondary {
    background: #6c757d;
    color: white;
}

.apd-btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

.apd-quantity-selector {
    display: flex;
    align-items: center;
    gap: 10px;
}

.apd-quantity-input {
    width: 80px;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
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
}

.apd-related-price {
    padding: 0 15px 15px;
    font-weight: 600;
    color: #667eea;
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
}
</style>

<script>
// AJAX object for product detail page
var apd_ajax = {
    ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
    nonce: '<?php echo wp_create_nonce('apd_ajax_nonce'); ?>',
    cart_url: '<?php echo home_url('/cart/'); ?>'
};

// Add to cart functionality for product detail page
jQuery(document).ready(function($) {
    $('.apd-btn-add-cart').on('click', function(e) {
        e.preventDefault();
        
        const $btn = $(this);
        const productId = $btn.data('product-id');
        const productName = $btn.data('product-name');
        const productPrice = parseFloat($btn.data('product-price'));
        const quantity = parseInt($('#apd-quantity').val()) || 1;
        
        // Show loading state
        $btn.prop('disabled', true).html('🔄 Adding...');
        
        // Add to cart via AJAX
        $.ajax({
            url: apd_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'apd_add_to_cart',
                nonce: apd_ajax.nonce,
                product_id: productId,
                quantity: quantity,
                customization_data: {
                    product_name: productName,
                    product_price: productPrice,
                    print_color: 'black', // Default color
                    vinyl_material: 'Solid', // Default material
                    quantity: quantity
                }
            },
            success: function(response) {
                if (response.success) {
                    $btn.html('✅ Added to Cart!');
                    setTimeout(() => {
                        window.location.href = apd_ajax.cart_url;
                    }, 1000);
                } else {
                    $btn.prop('disabled', false).html('❌ Error');
                    setTimeout(() => {
                        $btn.html('🛒 Add to Cart');
                    }, 2000);
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('❌ Error');
                setTimeout(() => {
                    $btn.html('🛒 Add to Cart');
                }, 2000);
            }
        });
    });
});
</script>

<?php get_footer(); ?>
