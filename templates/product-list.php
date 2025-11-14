<?php
/**
 * Product List Template (Grid with Pagination)
 * * @package AdvancedProductDesigner
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Extract template data
$categories = $template_data['categories'];
$show_title = $template_data['show_title'];
$show_description = $template_data['show_description'];
$show_price = $template_data['show_price'];
$show_sale = $template_data['show_sale'];
// Show all products - no pagination
$items_per_page = -1;
?>

<div class="apd-product-list-container">
    <div class="apd-product-list-header">
        <h2 class="apd-product-list-title">Our Products</h2>
        <p class="apd-product-list-subtitle">Choose from our wide range of customizable products</p>
    </div>
    
    <div class="apd-category-tabs">
        <div class="apd-tab-nav">
            <?php 
            $first_category = true;
            foreach ($categories as $category_name => $products): 
            ?>
                <button class="apd-tab-btn <?php echo $first_category ? 'active' : ''; ?>" 
                        data-category="<?php echo esc_attr(strtolower(str_replace(' ', '-', $category_name))); ?>">
                    <?php echo esc_html($category_name); ?>
                    <span class="apd-product-count">(<?php echo count($products); ?>)</span>
                </button>
            <?php 
                $first_category = false;
            endforeach; 
            ?>
        </div>
    </div>
    
    <div class="apd-product-grid-container">
        <?php $first_category = true; foreach ($categories as $category_name => $products): 
            $category_slug = esc_attr(strtolower(str_replace(' ', '-', $category_name)));
        ?>
            <div class="apd-category-content <?php echo $first_category ? 'active' : ''; ?>" 
                 data-category="<?php echo $category_slug; ?>">
                
                <div class="apd-product-grid apd-grid-cols-3" data-category="<?php echo $category_slug; ?>">
                    <?php foreach ($products as $product): ?>
                        <div class="apd-product-card" data-product-id="<?php echo esc_attr($product['id']); ?>">
                            
                            <div class="apd-product-image">
                                <div class="apd-card-preview" data-product-id="<?php echo esc_attr($product['id']); ?>"></div>
                                <div class="apd-fallback-image" style="display: none; position: absolute; inset: 0; z-index: 0;">
                                    <img src="<?php echo esc_url(!empty($product['thumbnail']) ? $product['thumbnail'] : 'https://v0-pod-website-ui.vercel.app/phone-case-mockup.jpg'); ?>" 
                                         alt="<?php echo esc_attr($product['title']); ?>"
                                         loading="lazy">
                                </div>
                                
                                <?php if ($show_sale && !empty($product['sale_price'])): ?>
                                    <div class="apd-sale-badge">Sale</div>
                                <?php endif; ?>
                                
                                <div class="apd-product-actions">
                                </div>
                            </div>
                            
                            <div class="apd-product-info">
                                <p class="apd-product-category">
                                    <?php echo esc_html($category_name); ?>
                                </p>
                                
                                <?php if ($show_title): ?>
                                    <h3 class="apd-product-title">
                                        <a href="<?php echo esc_url($product['permalink']); ?>">
                                            <?php echo esc_html($product['title']); ?>
                                        </a>
                                    </h3>
                                <?php endif; ?>
                                
                                <div class="apd-product-pricing-row">
                                    <?php if ($show_price): ?>
                                        <div class="apd-product-pricing">
                                            <?php if (!empty($product['sale_price']) && $show_sale): ?>
                                                <span class="apd-sale-price">$<?php echo esc_html($product['sale_price']); ?></span>
                                                <span class="apd-regular-price apd-crossed">$<?php echo esc_html($product['price']); ?></span>
                                            <?php else: ?>
                                                <span class="apd-regular-price">$<?php echo esc_html($product['price']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <button class="apd-product-list-cta-btn">Customize</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination removed - showing all products -->

            </div>
        <?php $first_category = false; endforeach; ?>
    </div>
</div>

<style>
/* --- Root Variables (based on Tailwind theme) --- */
:root {
    --color-primary: hsl(199, 89%, 48%);
    --color-primary-hover: hsl(199, 89%, 38%);
    --color-secondary: hsl(215, 20%, 65%);
    --color-background: hsl(0, 0%, 98%);
    --color-foreground: hsl(0, 0%, 9%);
    --color-muted: hsl(210, 40%, 96%);
    --color-muted-foreground: hsl(215, 16%, 47%);
    --color-border: hsl(214, 32%, 91%);
    --color-ring: hsl(215, 20%, 65%);
    --color-accent: hsl(210, 40%, 98%);
    --color-destructive: hsl(0, 84%, 60%);
    --shadow-xs: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --shadow-sm: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
    --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
}

/* --- General Container --- */
.apd-product-list-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0;
}

/* --- Header Section --- */
.apd-product-list-header {
    background-color: white;
    border-bottom: 1px solid var(--color-border);
    padding: 32px 16px;
}

@media (min-width: 1024px) {
    .apd-product-list-header {
        padding: 32px;
    }
}

.apd-product-list-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--color-foreground);
    margin: 0;
    text-align: center;
}

.apd-product-list-subtitle {
    font-size: 1.1rem;
    color: var(--color-muted-foreground);
    margin: 8px 0 0 0;
    text-align: center;
}

/* --- Category Tabs Section --- */
.apd-category-tabs {
    background-color: white;
    border-bottom: 1px solid var(--color-border);
    padding: 32px 16px;
}

@media (min-width: 1024px) {
    .apd-category-tabs {
        padding: 32px;
    }
}

.apd-tab-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: flex-start;
    align-items: center;
}

.apd-tab-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
    font-size: 0.875rem;
    font-weight: 600;
    height: 32px;
    padding: 0 12px;
    border-radius: 6px;
    border: 2px solid var(--color-border);
    background-color: white;
    color: var(--color-foreground);
    cursor: pointer;
    transition: all 0.15s ease;
    box-shadow: var(--shadow-xs);
    gap: 6px;
}

.apd-tab-btn:hover {
    background-color: var(--color-accent);
    border-color: var(--color-primary);
    color: var(--color-primary);
}

.apd-tab-btn.active {
    background-color: var(--color-primary);
    border-color: var(--color-primary);
    color: white;
}

.apd-product-count {
    font-size: 0.875rem;
}

/* --- Product Grid Section --- */
.apd-product-grid-container {
    background-color: var(--color-background);
    padding: 48px 16px;
}

@media (min-width: 1024px) {
    .apd-product-grid-container {
        padding: 64px 32px;
    }
}

.apd-category-content {
    display: none;
}

.apd-category-content.active {
    display: block;
}

.apd-product-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 24px;
    margin-bottom: 40px;
}

@media (min-width: 640px) {
    .apd-product-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1024px) {
    .apd-product-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 32px;
    }
}

/* --- Product Card --- */
.apd-product-card {
    display: block;
    background: white;
    border: 2px solid var(--color-border);
    border-radius: 12px;
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    transition: all 0.3s ease;
    cursor: pointer;
}

.apd-product-card:hover {
    box-shadow: var(--shadow-xl);
    border-color: var(--color-primary);
}

.apd-product-image {
    position: relative;
    width: 100%;
    padding-top: 100%;
    background: var(--color-muted);
    overflow: hidden;
}

.apd-card-preview, .apd-fallback-image {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.apd-card-preview .apd-template-canvas {
    position: absolute;
    left: 50%;
    top: 50%;
    transform-origin: center center;
}

.apd-product-image img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: contain;
    transition: transform 0.3s ease;
}

.apd-product-card:hover .apd-product-image img {
    transform: scale(1.05);
}

.apd-product-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #ccc;
    font-size: 3rem;
}

.apd-sale-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: var(--color-destructive);
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    z-index: 2;
}

.apd-product-actions {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    display: flex;
    gap: 10px;
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: 3;
}

.apd-product-card:hover .apd-product-actions {
    opacity: 1;
}

/* --- Product Info --- */
.apd-product-info {
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.apd-product-category {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--color-muted-foreground);
    margin: 0;
}

.apd-product-title {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 700;
    line-height: 1.3;
}

.apd-product-title a {
    color: var(--color-foreground);
    text-decoration: none;
}

.apd-product-title a:hover {
    color: var(--color-primary);
}

.apd-product-description {
    color: var(--color-muted-foreground);
    margin: 0;
    line-height: 1.5;
    font-size: 0.875rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;  
    overflow: hidden;
}

.apd-product-features {
    list-style: none;
    padding: 0;
    margin: 0;
}

.apd-product-features li {
    padding: 2px 0;
    color: var(--color-muted-foreground);
    font-size: 0.875rem;
    position: relative;
    padding-left: 15px;
}

.apd-product-features li:before {
    content: "✓";
    position: absolute;
    left: 0;
    color: #22c55e;
    font-weight: bold;
}

.apd-product-pricing-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 8px;
}

.apd-product-pricing {
    display: flex;
    align-items: center;
    gap: 8px;
}

.apd-sale-price {
    font-size: 1.5rem;
    font-weight: 900;
    color: var(--color-foreground);
}

.apd-regular-price {
    font-size: 1.5rem;
    font-weight: 900;
    color: var(--color-foreground);
}

.apd-regular-price.apd-crossed {
    text-decoration: line-through;
    color: var(--color-muted-foreground);
    font-size: 1rem;
    font-weight: 600;
}

.apd-product-list-cta-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
    font-size: 0.875rem;
    font-weight: 700;
    height: 32px;
    padding: 0 12px;
    border-radius: 6px;
    border: none;
    background-color: var(--color-secondary);
    color: white;
    cursor: pointer;
    transition: all 0.15s ease;
}

.apd-product-card:hover .apd-product-list-cta-btn {
    background-color: var(--color-primary);
}

.apd-product-list-cta-btn:hover {
    background-color: var(--color-primary-hover);
}

/* --- Pagination --- */
.apd-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin-top: 32px;
}

.apd-page-link {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 40px;
    height: 40px;
    border: 2px solid var(--color-border);
    border-radius: 50%;
    text-decoration: none;
    color: var(--color-foreground);
    font-weight: 600;
    transition: all 0.15s ease;
    background-color: white;
}

.apd-page-link:hover {
    border-color: var(--color-primary);
    background-color: hsl(199, 89%, 95%);
    color: var(--color-primary);
}

.apd-page-link.active {
    border-color: var(--color-primary);
    background-color: var(--color-primary);
    color: white;
}

/* --- Responsive --- */
@media (max-width: 768px) {
    .apd-tab-nav {
        flex-direction: column;
        align-items: stretch;
    }
    .apd-tab-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
// Ensure jQuery is loaded before running product list scripts
(function() {
    function waitForJQuery(callback, maxAttempts) {
        maxAttempts = maxAttempts || 50; // 5 seconds max wait
        var attempts = 0;
        
        function check() {
            attempts++;
            if (typeof jQuery !== 'undefined' && typeof jQuery.fn !== 'undefined') {
                // jQuery is available
                console.log('[APD Product List] jQuery is available, initializing...');
                callback();
            } else if (attempts < maxAttempts) {
                // jQuery not ready yet, wait and check again
                if (attempts % 10 === 0) {
                    console.log('[APD Product List] Waiting for jQuery... attempt ' + attempts);
                }
                setTimeout(check, 100);
            } else {
                // jQuery not available after waiting, try to load from CDN
                console.warn('[APD Product List] jQuery not available after waiting, attempting to load from CDN...');
                loadJQueryFromCDN(callback);
            }
        }
        
        check();
    }
    
    function loadJQueryFromCDN(callback) {
        // Check if jQuery is already being loaded
        if (document.querySelector('script[src*="jquery"]')) {
            console.log('[APD Product List] jQuery script tag already exists, waiting...');
            setTimeout(function() {
                waitForJQuery(callback, 20); // Wait a bit more
            }, 500);
            return;
        }
        
        // Load jQuery from CDN
        var script = document.createElement('script');
        script.src = 'https://code.jquery.com/jquery-3.7.1.min.js';
        script.integrity = 'sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=';
        script.crossOrigin = 'anonymous';
        script.onload = function() {
            console.log('[APD Product List] jQuery loaded successfully from CDN');
            // Wait a bit for jQuery to fully initialize
            setTimeout(callback, 100);
        };
        script.onerror = function() {
            console.error('[APD Product List] Failed to load jQuery from CDN');
            alert('Error: jQuery library could not be loaded. Please refresh the page.');
        };
        document.head.appendChild(script);
    }
    
    function initProductListScript() {
        // Ensure jQuery is available
        if (typeof jQuery === 'undefined' || typeof jQuery.fn === 'undefined') {
            console.error('[APD Product List] jQuery is not available');
            return;
        }
        
        jQuery(document).ready(function($) {
    // No pagination - show all products

    // --- EVENT HANDLERS ---

    // Category tab switching
    $('.apd-tab-btn').on('click', function() {
        const category = $(this).data('category');
        
        // Update active tab
        $('.apd-tab-btn').removeClass('active');
        $(this).addClass('active');
        
        // Show corresponding content
        $('.apd-category-content').removeClass('active');
        $(`.apd-category-content[data-category="${category}"]`).addClass('active');
    });

    // Product card click handler
    $('.apd-product-card').on('click', function(e) {
        if ($(e.target).is('button, a, .apd-btn') || $(e.target).closest('button, a, .apd-btn').length) {
            return;
        }
        const productId = $(this).data('product-id');
        window.location.href = `<?php echo home_url(); ?>/product-detail/?id=${productId}`;
    });

    // CTA button click handler - goes to product detail page
    $('.apd-product-list-cta-btn').on('click', function(e) {
        e.stopPropagation(); // Prevent card click
        const productId = $(this).closest('.apd-product-card').data('product-id');
        window.location.href = `<?php echo home_url(); ?>/product-detail/?id=${productId}`;
    });

    // --- CARD TEMPLATE PREVIEW ---
    // (This part remains the same as it's independent of pagination)

    window.renderCardPreview = function(productId, $container){
        if ($container.data('initialized')) return;
        $container.data('initialized', true);
        
        $container.html('<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#666;font-size:12px;">Loading...</div>');
        
        const timeout = setTimeout(function() {
            $container.closest('.apd-product-image').find('.apd-fallback-image').show();
            $container.hide();
        }, 5000);
        
        const ajaxUrl = (window.apd_ajax && apd_ajax.ajax_url) || '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
        let nonce = (window.apd_ajax && apd_ajax.nonce) || '<?php echo esc_js(wp_create_nonce('apd_ajax_nonce')); ?>';
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'apd_get_customizer_data',
                product_id: productId,
                nonce: nonce
            }
        }).done(function(resp){
            clearTimeout(timeout);
            if (!resp || !resp.success || !resp.data || !resp.data.template_data) {
                $container.closest('.apd-product-image').find('.apd-fallback-image').show();
                $container.hide();
                return;
            }
            try {
                const tdRaw = resp.data.template_data;
                const template = (function unwrap(t){
                    let x = t;
                    if (typeof x === 'string') { try { x = JSON.parse(x); } catch(e) {} }
                    if (x && x.data && Array.isArray(x.data.fields)) return x.data;
                    if (x && x.template && Array.isArray(x.template.fields)) return x.template;
                    return x;
                })(tdRaw);
                
                if (!template || !Array.isArray(template.fields)) {
                    $container.closest('.apd-product-image').find('.apd-fallback-image').show();
                    $container.hide();
                    return;
                }
                const canvasW = (template.canvas && template.canvas.width) ? template.canvas.width : 600;
                const canvasH = (template.canvas && template.canvas.height) ? template.canvas.height : 300;
                
                const $canvas = $('<div class="apd-template-canvas">');
                $canvas.css({ width: canvasW + 'px', height: canvasH + 'px', background: '#fff', position: 'relative' });
                $container.empty().append($canvas);
                
                (template.fields || []).forEach(function(el){
                    const $el = $('<div class="apd-mini-el">');
                    $el.css({ position: 'absolute', left: (el.x||0) + 'px', top: (el.y||0) + 'px', width: (el.width||0) + 'px', height: (el.height||0) + 'px', overflow: 'hidden' });
                    if (el.type === 'text') {
                        const $t = $('<span>').text(el.label || '');
                        const p = el.properties || {};
                        if (p.fontFamily) $t.css('font-family', p.fontFamily);
                        if (p.fontSize) $t.css('font-size', p.fontSize + 'px');
                        if (p.textColor) $t.css('color', p.textColor);
                        $t.css({ display: 'block', whiteSpace: 'nowrap' });
                        $el.append($t);
                    } else if (el.type === 'logo' || el.type === 'image') {
                        const $box = $('<div>').css({ width: '100%', height: '100%', background: '#eef1f5', borderRadius: '4px' });
                        $el.append($box);
                    }
                    $canvas.append($el);
                });
                
                function fitCover(){
                    const cw = $container.innerWidth();
                    const ch = $container.innerHeight();
                    if (!cw || !ch) return;
                    const scale = Math.max(cw / canvasW, ch / canvasH);
                    $canvas.css('transform', `scale(${scale})`);
                    $container.css({ 'display': 'flex', 'align-items': 'center', 'justify-content': 'center' });
                }
                fitCover();
                $(window).on('resize.apdCardPreview', fitCover);
            } catch(e) {
                console.error('Preview render error:', e);
                $container.closest('.apd-product-image').find('.apd-fallback-image').show();
                $container.hide();
            }
        }).fail(function() {
            clearTimeout(timeout);
            $container.closest('.apd-product-image').find('.apd-fallback-image').show();
            $container.hide();
        });
    }

    // Initialize previews for ALL cards
    $('.apd-product-card').each(function(){
        const $card = $(this);
        const productId = $card.data('product-id');
        const $preview = $card.find('.apd-card-preview');
        if ($preview.length) {
            window.renderCardPreview(productId, $preview);
        }
    });
        }); // End jQuery(document).ready
    } // End initProductListScript
    
    // Start initialization - wait for jQuery first
    waitForJQuery(initProductListScript);
})(); // End IIFE
</script>
