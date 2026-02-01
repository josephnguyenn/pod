<?php
/** Customizer template - renders the customizer interface */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Close PHP block
?>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<script type="module">
/**
 * Convert SVG element to data URL with embedded fonts
 * Preserves inline styles needed for SVG rendering
 * Embeds custom fonts as base64 data URLs for portability
 */
window.htmlElementToImage = function(element) {
    return new Promise(async function(resolve, reject) {
        try {
            // Validate element
            if (!element || element.tagName.toLowerCase() !== 'svg') {
                return reject(new Error("Element is not a valid SVG element."));
            }
            
            // Clone the SVG element
            var clone = element.cloneNode(true);
            
            // Collect all font-family values used in the SVG
            var usedFonts = new Set();
            function collectFonts(el) {
                // Check font-family attribute
                if (el.getAttribute && el.getAttribute('font-family')) {
                    usedFonts.add(el.getAttribute('font-family').replace(/['"]/g, '').trim());
                }
                // Check style attribute for font-family
                if (el.getAttribute && el.getAttribute('style')) {
                    var styleMatch = el.getAttribute('style').match(/font-family:\s*['"]?([^;'"]+)/i);
                    if (styleMatch) {
                        usedFonts.add(styleMatch[1].trim());
                    }
                }
                // Process children
                if (el.children) {
                    for (var i = 0; i < el.children.length; i++) {
                        collectFonts(el.children[i]);
                    }
                }
            }
            collectFonts(clone);
            console.log('[SVG Export] Fonts used:', Array.from(usedFonts));
            
            // Get uploaded fonts from global variable
            var uploadedFonts = window.apdUploadedFonts || [];
            
            // Build font embedding promises
            var fontStyleRules = [];
            var fontPromises = [];
            
            uploadedFonts.forEach(function(font) {
                if (!font.family || !font.url) return;
                
                // Check if this font is used
                var isUsed = false;
                usedFonts.forEach(function(usedFont) {
                    if (usedFont.toLowerCase().indexOf(font.family.toLowerCase()) !== -1 ||
                        font.family.toLowerCase().indexOf(usedFont.toLowerCase()) !== -1) {
                        isUsed = true;
                    }
                });
                
                if (isUsed) {
                    console.log('[SVG Export] Embedding font:', font.family, font.url);
                    var promise = fetch(font.url)
                        .then(function(response) {
                            if (!response.ok) throw new Error('Font fetch failed');
                            return response.blob();
                        })
                        .then(function(blob) {
                            return new Promise(function(res) {
                                var reader = new FileReader();
                                reader.onloadend = function() {
                                    var base64 = reader.result;
                                    var weight = font.weight || '400';
                                    var format = 'truetype';
                                    if (font.url.indexOf('.woff2') !== -1) format = 'woff2';
                                    else if (font.url.indexOf('.woff') !== -1) format = 'woff';
                                    else if (font.url.indexOf('.otf') !== -1) format = 'opentype';
                                    
                                    // Escape font family name for CSS (remove quotes and special chars)
                                    var safeFontFamily = font.family.replace(/['"\\]/g, '');
                                    
                                    fontStyleRules.push(
                                        "@font-face{font-family:'" + safeFontFamily + "';src:url(" + base64 + ") format('" + format + "');font-weight:" + weight + ";}"
                                    );
                                    res();
                                };
                                reader.onerror = function() { res(); };
                                reader.readAsDataURL(blob);
                            });
                        })
                        .catch(function(err) {
                            console.warn('[SVG Export] Failed to embed font:', font.family, err);
                        });
                    fontPromises.push(promise);
                }
            });
            
            // Wait for all fonts to be converted
            await Promise.all(fontPromises);
            
            // Remove only class attributes (keep style for visual properties)
            function cleanElement(el) {
                if (el.hasAttribute && el.hasAttribute('class')) {
                    el.removeAttribute('class');
                }
                if (el.children) {
                    for (var i = 0; i < el.children.length; i++) {
                        cleanElement(el.children[i]);
                    }
                }
            }
            cleanElement(clone);
            
            // Add required SVG attributes
            if (!clone.hasAttribute('xmlns')) {
                clone.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
            }
            if (!clone.hasAttribute('xmlns:xlink')) {
                clone.setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
            }
            if (!clone.hasAttribute('viewBox') && clone.hasAttribute('width') && clone.hasAttribute('height')) {
                clone.setAttribute('viewBox', '0 0 ' + clone.getAttribute('width') + ' ' + clone.getAttribute('height'));
            }
            
            // Remove any existing style elements with @font-face to avoid duplicates
            var existingStyles = clone.querySelectorAll('style');
            existingStyles.forEach(function(styleEl) {
                var styleText = styleEl.textContent || styleEl.innerHTML || '';
                if (styleText.indexOf('@font-face') !== -1 || styleText.indexOf('CDATA') !== -1) {
                    console.log('[SVG Export] Removing existing font style element');
                    styleEl.parentNode.removeChild(styleEl);
                }
            });
            
            // Inject embedded fonts as <style> element inside SVG
            if (fontStyleRules.length > 0) {
                var defsEl = clone.querySelector('defs');
                if (!defsEl) {
                    defsEl = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
                    clone.insertBefore(defsEl, clone.firstChild);
                }
                var styleEl = document.createElementNS('http://www.w3.org/2000/svg', 'style');
                styleEl.setAttribute('type', 'text/css');
                // Set CSS content directly - XMLSerializer will handle escaping
                var cssContent = fontStyleRules.join('\n');
                styleEl.textContent = cssContent;
                defsEl.insertBefore(styleEl, defsEl.firstChild);
                console.log('[SVG Export] Embedded', fontStyleRules.length, 'font rules');
            }
            
            // Serialize to string
            var serializer = new XMLSerializer();
            var svgString = serializer.serializeToString(clone);
            
            // Wrap style content in CDATA if not already wrapped
            // This ensures XML parsers don't choke on special chars in base64 data URLs
            // Use a more comprehensive regex that matches style content (including special chars)
            svgString = svgString.replace(/<style\s+type="text\/css">([\s\S]*?)<\/style>/g, function(match, content) {
                // Check if already CDATA wrapped (avoid double-wrapping)
                if (content.indexOf('<![CDATA[') !== -1) {
                    return match; // Already wrapped, return as-is
                }
                return '<style type="text/css"><![CDATA[' + content + ']]></style>';
            });
            
            var xmlDeclaration = '<' + '?xml version="1.0" encoding="UTF-8" standalone="no"?' + '>\n';
            var fullSvg = xmlDeclaration + svgString;
            
            // Convert to data URL
            var dataUrl = 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(fullSvg)));
            resolve(dataUrl);
            
        } catch (err) {
            console.error("Error converting SVG to data URL:", err);
            reject(err);
        }
    });
};
</script>
<?php 
// Resume PHP processing

// Inject @font-face rules for uploaded fonts
$uploaded_fonts = get_option('apd_uploaded_fonts', array());
if (!empty($uploaded_fonts)) {
    echo '<style id="apd-customizer-fonts">';
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
            echo "@font-face{font-family:'{$family_css}';src:url('{$url_css}') format('{$format}');font-weight:{$weight_css};font-display:swap;}\n";
        }
    }
    echo '</style>';
}
?>

<div class="fsc-container">
    <div class="fsc-customizer-wrapper">
        
        <!-- Preview Section -->
        <div class="fsc-preview-section">
            <div class="fsc-preview-title">Live Preview</div>
            <div class="fsc-preview-area">
                <div class="fsc-preview-content">
                    <!-- Logo SVG -->
                    <div class="fsc-logo-container">
                        <?php echo $product_logo_content; ?>
                    </div>
                    
                    <!-- Text SVG - will be populated by JavaScript -->
                    <div class="fsc-text-container">
                        <!-- Text SVG will be generated by customizer.js -->
                    </div>
                </div>
            </div>
            <div class="fsc-thumbnails">
                <!-- Thumbnail previews can be added here -->
            </div>
        </div>
        
        <!-- Customization Panel -->
        <div class="fsc-customization-panel">
            <!-- Product Info -->
            <div class="fsc-product-info" data-product-id="<?php echo $product_data ? $product_data->ID : 0; ?>">
                <div class="fsc-product-name" data-product-name="<?php echo esc_attr($product_data ? $product_data->post_title : 'Custom Product'); ?>">
                    <?php echo $product_data ? $product_data->post_title : 'Custom Product'; ?>
                </div>
                <div class="fsc-product-price" 
                     data-product-price="<?php echo esc_attr($product_price); ?>" 
                     data-product-sale-price="<?php echo esc_attr($product_sale_price); ?>">
                    <?php 
                    $display_price = !empty($product_sale_price) ? $product_sale_price : $product_price;
                    echo '$' . ($display_price ?: '29.99'); 
                    ?> - <?php echo $product_material; ?>
                </div>
            </div>
            
            <!-- Color Selection -->
            <?php if ($enable_color_selection == '1'): ?>
            <div class="fsc-form-group">
                <h4>Print Color</h4>
                <div class="fsc-selected-color" style="margin-bottom:8px;font-size:13px;color:#444;">
                    Selected: <span id="fsc-selected-color-name">None</span>
                </div>
                <div class="fsc-color-grid">
                    <?php foreach ($colors as $color_name => $color_value): ?>
                        <div class="fsc-color-item" style="display:inline-flex;flex-direction:column;align-items:center;gap:4px;margin:4px;">
                            <div class="fsc-color-option" 
                                 data-color="<?php echo esc_attr($color_name); ?>" 
                                 style="background-color: <?php echo esc_attr($color_value); ?>; width: 28px; height: 28px; border-radius: 4px; box-shadow: inset 0 0 0 1px rgba(0,0,0,0.1); cursor: pointer;"
                                 title="<?php echo esc_attr($color_name); ?>">
                            </div>
                            <div class="fsc-color-label" style="font-size: 12px; color: #555; max-width: 64px; text-align: center; word-break: break-word; line-height: 1.1;">
                                <?php echo esc_html($color_name); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        
            
            <!-- Text Fields -->
            <div class="fsc-form-group">
                <h4>Custom Text</h4>
                
                <!-- Dynamic Template Fields Container -->
                <div id="fsc-template-fields">
                    <!-- Template text fields will be populated here by JavaScript -->
                </div>
            </div>
            
            <!-- Features/Benefits -->
            <div class="fsc-form-group fsc-features">
                <h4>Benefits</h4>
                <ul class="fsc-feature-list">
                    <?php foreach ($product_features as $feature): ?>
                        <li><?php echo esc_html($feature); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <!-- Actions -->
            <div class="fsc-actions">
                <div class="fsc-quantity-group">
                    <button class="fsc-quantity-btn" id="fsc-quantity-minus">-</button>
                    <input type="number" id="fsc-quantity" value="1" min="1" max="100" class="fsc-quantity-input">
                    <button class="fsc-quantity-btn" id="fsc-quantity-plus">+</button>
                </div>
                <div class="fsc-button-group">
                    <button class="fsc-btn fsc-btn-primary fsc-btn-add-cart">ADD TO CART</button>
                    <button class="fsc-btn fsc-btn-secondary fsc-btn-reset">🔄 RESET</button>
                </div>
            </div>
            
            <!-- Checkout Section -->
            <div class="fsc-checkout-section">
                <button class="fsc-btn fsc-btn-checkout">CHECK OUT</button>
            </div>
        </div>
        
    </div>
</div>

<!-- Hidden form for AJAX submissions -->
<form id="fsc-customization-form" style="display: none;">
    <input type="hidden" name="action" value="save_customization">
    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('fsc_nonce'); ?>">
    <input type="hidden" name="product_id" id="fsc-form-product-id" value="<?php echo $product_data ? $product_data->ID : 0; ?>">
    <input type="hidden" name="vin" id="fsc-form-vin">
    <input type="hidden" name="truck_no" id="fsc-form-truck">
    <input type="hidden" name="print_color" id="fsc-form-color">
    <input type="hidden" name="vinyl_material" id="fsc-form-material">
    <input type="hidden" name="quantity" id="fsc-form-quantity">
</form>

<script>
// AJAX object for customizer
var ajaxObj = {
    ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
    nonce: '<?php echo wp_create_nonce('apd_ajax_nonce'); ?>',
    fsc_nonce: '<?php echo wp_create_nonce('fsc_nonce'); ?>',
    cart_url: '<?php echo home_url(get_option('apd_cart_url', '/cart/')); ?>',
    checkout_url: '<?php echo home_url(get_option('apd_checkout_url', '/checkout/')); ?>',
    products_url: '<?php echo home_url(get_option('apd_products_url', '/products/')); ?>',
    orders_url: '<?php echo home_url(get_option('apd_orders_url', '/my-orders/')); ?>',
    customizer_url: '<?php echo home_url(get_option('apd_customizer_url', '/customizer/')); ?>',
    thank_you_url: '<?php echo home_url(get_option('apd_thank_you_url', '/thank-you/')); ?>',
    product_id: '<?php echo $product_data ? $product_data->ID : 0; ?>'
};

// Ensure compatibility with scripts expecting `apd_ajax`
// Map the local config to the global name used across JS files
window.apd_ajax = window.apd_ajax || ajaxObj;
var apd_ajax = window.apd_ajax;

// Set default values for FSC
window.fscDefaults = {
    product_id: '<?php echo $product_data ? $product_data->ID : 0; ?>',
    product_name: '<?php echo $product_data ? esc_js($product_data->post_title) : 'Custom Freight Sign'; ?>',
    product_price: '<?php echo $product_price ?: '29.99'; ?>',
    base_price: '<?php echo $product_price ?: '29.99'; ?>', // Base price (same as product_price)
    product_sale_price: '<?php echo $product_sale_price ?: ''; ?>'
};
</script>

<script>
// Simple Buy Now handler for customizer
(function(){
    async function buyNowHandler(e){
        e.preventDefault();
        var btn = e.currentTarget;
        btn.disabled = true;
        btn.textContent = 'Preparing...';
        var quantity = parseInt(document.getElementById('fsc-quantity').value || '1',10);
        
        // Get price information from FSC object if available, otherwise use defaults
        var basePrice = 0;
        var salePrice = null;
        var materialPrice = 0;
        var productPrice = window.fscDefaults.product_price || 0;
        
        if (window.FSC) {
            // Try to get base price from FSC
            basePrice = FSC.baseProductPrice || parseFloat(window.fscDefaults.base_price) || 0;
            salePrice = FSC.salePrice || (window.fscDefaults.product_sale_price ? parseFloat(window.fscDefaults.product_sale_price) : null);
            
            // Get material price
            var currentMaterial = FSC.currentMaterial || document.getElementById('fsc-form-material')?.value || '';
            if (currentMaterial) {
                // Try to get material price from selected material element
                var materialEl = document.querySelector('.fsc-material-outline-option[data-material="' + currentMaterial + '"]');
                if (materialEl) {
                    materialPrice = parseFloat(materialEl.getAttribute('data-material-price')) || 0;
                } else if (FSC.materialsMap && FSC.materialsMap[currentMaterial]) {
                    var materialData = FSC.materialsMap[currentMaterial];
                    if (typeof materialData === 'object' && materialData.price !== undefined) {
                        materialPrice = parseFloat(materialData.price) || 0;
                    }
                }
            }
            
            // Calculate product price: base_price (or sale_price) + material_price
            var basePriceToUse = salePrice || basePrice || parseFloat(window.fscDefaults.product_price) || 0;
            productPrice = basePriceToUse + materialPrice;
        } else {
            // Fallback: use defaults
            basePrice = parseFloat(window.fscDefaults.base_price) || parseFloat(window.fscDefaults.product_price) || 0;
            salePrice = window.fscDefaults.product_sale_price ? parseFloat(window.fscDefaults.product_sale_price) : null;
            productPrice = salePrice || basePrice;
        }
        
        var payload = {
            product_id: window.fscDefaults.product_id || ajaxObj.product_id,
            product_name: window.fscDefaults.product_name || '',
            product_price: productPrice, // Total price (base + material)
            base_price: basePrice,
            sale_price: salePrice,
            material_price: materialPrice,
            quantity: quantity,
            print_color: document.getElementById('fsc-form-color')?.value || FSC?.currentColor || '',
            vinyl_material: document.getElementById('fsc-form-material')?.value || FSC?.currentMaterial || '',
            text_fields: (window.FSC && typeof FSC.getTextFields === 'function') ? FSC.getTextFields() : {},
            template_data: (window.FSC && typeof FSC.getTemplateData === 'function') ? FSC.getTemplateData() : {},
            preview_image_svg: null
        };

        // Try to capture production-ready SVG with embedded fonts
        try {
            console.log('📷 Buy Now: Starting SVG capture...');
            
            // Find all potential SVG elements
            var allSvgs = document.querySelectorAll('.fsc-preview-content svg, .fsc-logo-container svg, .apd-template-preview svg, .apd-logo-box svg');
            console.log('📷 Buy Now: Found', allSvgs.length, 'SVG elements');
            
            var logoSvg = null;
            // Prefer the largest/main SVG element
            for (var i = 0; i < allSvgs.length; i++) {
                var svg = allSvgs[i];
                console.log('📷 SVG #' + i + ':', svg.tagName, 'width:', svg.getAttribute('width'), 'height:', svg.getAttribute('height'), 'viewBox:', svg.getAttribute('viewBox'));
                if (!logoSvg || (svg.getBBox && svg.getBBox().width > (logoSvg.getBBox ? logoSvg.getBBox().width : 0))) {
                    logoSvg = svg;
                }
            }
            
            if (logoSvg && typeof window.htmlElementToImage === 'function') {
                console.log('📷 Buy Now: Capturing SVG with embedded fonts...');
                console.log('📷 Selected SVG:', logoSvg.outerHTML.substring(0, 200) + '...');
                var svgDataURL = await window.htmlElementToImage(logoSvg);
                if (svgDataURL && svgDataURL.length > 100 && svgDataURL.length < 500000) {
                    payload.preview_image_svg = svgDataURL;
                    console.log('✅ Buy Now: SVG captured with fonts, size:', svgDataURL.length, 'bytes');
                } else if (svgDataURL && svgDataURL.length >= 500000) {
                    console.warn('⚠️ Buy Now: SVG too large (' + svgDataURL.length + ' bytes), skipping');
                } else {
                    console.warn('⚠️ Buy Now: SVG capture returned empty or very small result');
                }
            }
            // Method 2: Fallback to FSC_SVGExport (async)
            if (!payload.preview_image_svg && window.FSC_SVGExport && typeof FSC_SVGExport.getSVGDataURL === 'function') {
                console.log('📷 Buy Now: Using FSC_SVGExport as fallback...');
                var svgDataURL = await FSC_SVGExport.getSVGDataURL();
                if (svgDataURL && svgDataURL.length > 100 && svgDataURL.length < 500000) {
                    payload.preview_image_svg = svgDataURL;
                    console.log('✅ Buy Now: Production SVG captured, size:', svgDataURL.length, 'bytes');
                } else if (svgDataURL && svgDataURL.length >= 500000) {
                    console.warn('⚠️ Buy Now: SVG too large (' + svgDataURL.length + ' bytes), skipping');
                }
            }
            // Method 3: Simple serialization without font embedding as last resort
            if (!payload.preview_image_svg && logoSvg) {
                console.log('📷 Buy Now: Using simple SVG serialization as last resort...');
                try {
                    var serializer = new XMLSerializer();
                    var svgString = serializer.serializeToString(logoSvg);
                    var dataUrl = 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(svgString)));
                    if (dataUrl.length < 500000) {
                        payload.preview_image_svg = dataUrl;
                        console.log('✅ Buy Now: Simple SVG serialization, size:', dataUrl.length, 'bytes');
                    }
                } catch(serErr) {
                    console.warn('⚠️ Buy Now: Simple serialization failed:', serErr);
                }
            }
            
            if (!payload.preview_image_svg) {
                console.log('ℹ️ Buy Now: No SVG capture method succeeded');
            }
        } catch(err) { 
            console.warn('⚠️ Buy Now: failed to capture SVG:', err);
            console.warn('⚠️ Stack:', err.stack);
            // Continue without preview image - it's not critical
        }

        // Log preview status
        console.log('🖼️ Buy Now: preview_image_svg is ' + (payload.preview_image_svg ? 'SET (' + payload.preview_image_svg.substring(0, 50) + '...)' : 'NULL'));

        // Persist checkout payload locally and redirect to checkout page
        try {
            // Validate payload has required fields
            if (!payload.product_id || !payload.product_name) {
                console.error('Buy Now: Invalid payload - missing product_id or product_name', payload);
                alert('Error: Missing product information. Please refresh the page and try again.');
                btn.disabled = false;
                return;
            }
            
            // Ensure price is valid
            if (!payload.product_price || payload.product_price <= 0) {
                console.warn('Buy Now: Invalid price, using default', payload);
                payload.product_price = parseFloat(window.fscDefaults.product_price) || 29.99;
                payload.base_price = parseFloat(window.fscDefaults.base_price) || payload.product_price;
            }
            
            // Log payload before saving
            console.log('🛒 Buy Now: Saving payload to localStorage:', payload);
            console.log('🛒 Buy Now: Payload keys:', Object.keys(payload));
            console.log('🛒 Buy Now: Product ID:', payload.product_id);
            console.log('🛒 Buy Now: Product Name:', payload.product_name);
            console.log('🛒 Buy Now: Price:', payload.product_price);
            
            // Check payload size before saving (localStorage has ~5-10MB limit, but we'll be conservative)
            var payloadString = JSON.stringify(payload);
            var payloadSize = new Blob([payloadString]).size;
            console.log('📦 Buy Now: Payload size:', payloadSize, 'bytes');
            
            // If payload is too large (>500KB), remove preview image and try again
            if (payloadSize > 500000) {
                console.warn('⚠️ Buy Now: Payload too large, removing preview image');
                delete payload.preview_image_svg;
                delete payload.preview_image_png;
                var reducedPayloadString = JSON.stringify(payload);
                var reducedSize = new Blob([reducedPayloadString]).size;
                console.log('📦 Buy Now: Reduced payload size:', reducedSize, 'bytes');
                
                if (reducedSize > 500000) {
                    console.error('❌ Buy Now: Payload still too large even without preview image!');
                    alert('Error: Checkout data is too large. Please try again or contact support.');
                    btn.disabled = false;
                    return;
                }
                
                // Use reduced payload
                localStorage.setItem('apd_checkout_payload_oneclick', reducedPayloadString);
                var saved = localStorage.getItem('apd_checkout_payload_oneclick');
                if (!saved || saved !== reducedPayloadString) {
                    console.error('Buy Now: Failed to save reduced payload to localStorage!');
                    alert('Error: Failed to save checkout data. Please try again.');
                    btn.disabled = false;
                    return;
                }
            } else {
                // Save one-click payload under dedicated key
                localStorage.setItem('apd_checkout_payload_oneclick', payloadString);
                
                // Verify it was saved
                var saved = localStorage.getItem('apd_checkout_payload_oneclick');
                if (!saved || saved !== payloadString) {
                    console.error('Buy Now: Failed to save payload to localStorage!');
                    alert('Error: Failed to save checkout data. Please try again.');
                    btn.disabled = false;
                    return;
                }
            }
            
            console.log('✅ Buy Now: Payload saved successfully to apd_checkout_payload_oneclick');
            
            // Clear cart (optional) to avoid confusion
            try { localStorage.removeItem('apd_cart'); } catch(_) {}
            // Clear cart selection to avoid filtering issues
            try { localStorage.removeItem('apd_cart_selected'); } catch(_) {}
            
            // Redirect to checkout with instant flag so checkout reads the oneclick payload
            var target = (ajaxObj && ajaxObj.checkout_url) ? ajaxObj.checkout_url : '/checkout/';
            if (target.indexOf('?') === -1) {
                target += '?instant=true';
            } else {
                target += '&instant=true';
            }
            
            console.log('🛒 Buy Now: Redirecting to checkout:', target);
            window.location.href = target;
            return;
        } catch (err) {
            console.error('❌ Buy Now local persist error:', err);
            console.error('❌ Error stack:', err.stack);
            console.error('❌ Payload that caused error:', payload);
            alert('Failed to start checkout. Please try again or contact support.');
            btn.disabled = false;
        }
    }

    document.addEventListener('DOMContentLoaded', function(){
        var buyBtns = document.querySelectorAll('.fsc-btn-buy-now');
        buyBtns.forEach(function(b){ b.addEventListener('click', buyNowHandler); });
    });
})();
</script>
