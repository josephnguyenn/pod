(function($) {
    'use strict';

    // Initialize product blocks on page load
    $(document).ready(function() {
        initProductBlocks();
    });

    function initProductBlocks() {
        $('.apd-product-display').each(function() {
            const $block = $(this);
            const productId = $block.data('product-id');
            const layout = $block.data('layout') || 'card';
            const showPrice = $block.data('show-price') !== false;
            const showDescription = $block.data('show-description') !== false;

            if (productId) {
                loadProductData(productId, $block, layout, showPrice, showDescription);
            }
        });
    }

    function loadProductData(productId, $block, layout, showPrice, showDescription) {
        // Show loading state
        $block.html(`
            <div class="apd-loading">
                <span class="spinner is-active"></span>
                Loading product...
            </div>
        `);

        // Fetch product data
        $.ajax({
            url: apd_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'apd_get_customizer_data',
                product_id: productId,
                nonce: apd_ajax.nonce
            },
            success: function(response) {
                if (response && response.success && response.data) {
                    var product = response.data.product || response.data;
                    var td = response.data.templateData || null;
                    if (product && (product.id || product.title)) {
                        // Prefer customizer when templateData is present
                        if (td) {
                            renderCustomizerLayout(product, $block, td);
                            return;
                        }
                        renderProduct(product, $block, layout, showPrice, showDescription);
                        return;
                    }
                }
                showError($block, 'Product not found');
            },
            error: function() {
                showError($block, 'Error loading product');
            }
        });
    }

    function renderProduct(product, $block, layout, showPrice, showDescription) {
        if (layout === 'customizer') {
            renderCustomizerLayout(product, $block);
            return;
        }
        let html = '';
        switch (layout) {
            case 'list':
                html = renderListLayout(product, showPrice, showDescription);
                break;
            case 'grid':
                html = renderGridLayout(product, showPrice, showDescription);
                break;
            default: // card
                html = renderCardLayout(product, showPrice, showDescription);
        }
        $block.html(html);
        addProductInteractions($block, product);
    }

    // ===== Customizer Layout (like image 2) =====
    function renderCustomizerLayout(product, $block, templateDataImmediate) {
        // Skeleton layout
        var layoutHtml = '' +
            '<div class="apd-customizer-container">' +
                '<div class="apd-preview-section">' +
                    '<div class="apd-preview-title">Live Preview</div>' +
                    '<div class="apd-preview-area" style="overflow:hidden; cursor:grab; display:flex; align-items:flex-start; justify-content:center">' +
                        '<div class="apd-preview-content" id="apd-preview-content" style="width:max-content;height:max-content"></div>' +
                    '</div>' +
                    '<div class="apd-thumbnails"></div>' +
                '</div>' +
                '<div class="apd-customization-panel">' +
                    '<div class="apd-product-info">' +
                        '<div class="apd-product-name">' + (product.title || '') + '</div>' +
                        '<div class="apd-product-price">$' + (product.price || '') + ' Heavy Metal Chrome with Color</div>' +
                    '</div>' +
                    '<div class="apd-form-group">' +
                        '<h4 style="margin-bottom:10px;color:#333">Print Color</h4>' +
                        '<div class="apd-color-grid" style="display:grid;grid-template-columns:repeat(6,28px);gap:10px;margin-bottom:10px"></div>' +
                    '</div>' +
                    '<div class="apd-form-group" style="background:white;padding:15px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1);margin-bottom:15px;">' +
                        '<h4 style="margin-bottom:10px;color:#333;font-size:14px;font-weight:600;">Material Outline</h4>' +
                        '<div class="apd-material-grid" style="display:grid;grid-template-columns:repeat(4,32px);gap:8px;margin-bottom:15px;"></div>' +
                        '<div class="apd-outline-width" style="display:flex;align-items:center;gap:8px;margin-bottom:10px">' +
                            '<label style="min-width:90px">Outline width</label>' +
                            '<input id="apd-outline-width" type="range" min="0" max="60" step="1" value="24" style="flex:1">' +
                            '<span id="apd-outline-width-value">24</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="apd-dynamic-fields"></div>' +
                    '<div class="apd-form-group apd-features" style="margin-top:8px;">' +
                        '<h4 style="margin-bottom:10px;color:#333;font-size:14px;font-weight:600;">Benefits</h4>' +
                        '<ul class="apd-feature-list" style="list-style:disc;margin-left:18px;color:#333;"></ul>' +
                    '</div>' +
                    '<div class="apd-actions" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">' +
                        '<input type="number" class="apd-quantity-input" id="apd-quantity-input" value="1" min="1" max="100" />' +
                        '<button class="apd-btn apd-btn-primary apd-btn-add-cart">ADD TO CART</button>' +
                        '<button class="apd-btn apd-btn-secondary apd-btn-customize">CUSTOMIZE</button>' +
                        '<button class="apd-btn apd-btn-checkout" style="flex-basis:100%;margin-top:6px;">CHECK OUT</button>' +
                    '</div>' +
                '</div>' +
            '</div>';

        $block.html(layoutHtml);

        // Helper: toggle scrollbars only if canvas exceeds viewport
        function adjustPreviewScrolling() {
            try {
                var $area = $block.find('.apd-preview-area');
                var $content = $block.find('#apd-preview-content .apd-template-container');
                if ($area.length === 0 || $content.length === 0) return;
                var areaW = $area.innerWidth();
                var areaH = $area.innerHeight();
                var canvasW = $content.outerWidth();
                var canvasH = $content.outerHeight();
                var needScroll = (canvasW > areaW) || (canvasH > areaH);
                $area.css('overflow', needScroll ? 'auto' : 'hidden');
            } catch(e) { /* noop */ }
        }

        // Use provided templateData if available, else fetch
        var handleTemplateData = function(td){
            if (!td) return; // leave skeleton
            var template = mapTemplateFromTemplateData(td, product);
            renderTemplateIntoPreview(template, $block.find('#apd-preview-content'));
            renderDynamicInputs(template, $block.find('.apd-dynamic-fields'));
            // Populate features/benefits list from product meta
            try {
                var feats = Array.isArray(product.features) ? product.features : [];
                if (feats.length === 0) {
                    feats = [
                        'DOT Approved Size',
                        '5 years outdoor life',
                        'Air release for bubble free installing',
                        'Professional quality materials'
                    ];
                }
                var $ul = $block.find('.apd-feature-list');
                $ul.empty();
                feats.forEach(function(f){ $ul.append('<li style="padding:4px 0;color:#555;">'+escapeHtml(String(f))+'</li>'); });
            } catch(e) { /* noop */ }
            wireLiveUpdates(template, $block);
            // After DOM is ready, adjust scrolling
            setTimeout(adjustPreviewScrolling, 0);
        };

        if (templateDataImmediate) {
            handleTemplateData(templateDataImmediate);
        } else {
            $.ajax({
                url: apd_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'apd_get_customizer_data',
                    product_id: product.id,
                    nonce: apd_ajax.nonce
                },
                success: function(resp) {
                    var td = resp && resp.success && resp.data ? resp.data.templateData : null;
                    handleTemplateData(td);
                }
            });
        }

        // Add simple panning for large canvases
        var $area = $block.find('.apd-preview-area');
        var dragging = false, startX = 0, startY = 0, sL = 0, sT = 0;
        $area.on('mousedown', function(e){ dragging = true; startX = e.pageX - $area.offset().left; startY = e.pageY - $area.offset().top; sL = $area.scrollLeft(); sT = $area.scrollTop(); $area.css('cursor','grabbing'); });
        $(document).on('mouseup', function(){ dragging = false; $area.css('cursor','grab'); });
        $area.on('mousemove', function(e){ if(!dragging) return; e.preventDefault(); var x = e.pageX - $area.offset().left; var y = e.pageY - $area.offset().top; $area.scrollLeft(sL - (x - startX)); $area.scrollTop(sT - (y - startY)); });
        // Re-evaluate scrolling on window resize
        $(window).on('resize', adjustPreviewScrolling);
    }

    function mapTemplateFromTemplateData(td, product) {
        console.log('=== MAP TEMPLATE DEBUG ===');
        console.log('Template data (td):', td);
        console.log('Template data elements:', td && td.elements);
        console.log('Template data colorPalette:', td && td.colorPalette);
        console.log('Template data colors:', td && td.colors);
        console.log('Template data colorOptions:', td && td.colorOptions);
        
        var allEls = Array.isArray(td.elements) ? td.elements.slice() : [];

        // Detect a dedicated logo area from elements
        var logoElIndex = -1;
        var logoEl = null;
        for (var i = 0; i < allEls.length; i++) {
            var e = allEls[i] || {};
            var t = (e.type || '').toString().toLowerCase();
            var labelStr = (e.label || e.name || e.id || '').toString();
            if (t === 'logo' || /(^|\b)logo(\b|\d|\s|_|-)/i.test(labelStr)) {
                logoElIndex = i;
                logoEl = e;
                break;
            }
        }
        if (logoElIndex >= 0) { allEls.splice(logoElIndex, 1); }

        var fields = Array.isArray(allEls) ? allEls
            .filter(function(e){
                if (!e) return false;
                var t = (e.type || '').toString().toLowerCase();
                var isText = ['text','textarea','input','label','richtext','textbox'].indexOf(t) !== -1 || (e.properties && e.properties.text != null);
                var isImage = ['image','img','picture','photo'].indexOf(t) !== -1 || !!(e.url || e.src || (e.properties && (e.properties.src || e.properties.url)));
                return isText || isImage;
            })
            .map(function(e, idx){
                var t = (e.type || '').toString().toLowerCase();
                var isImage = ['image','img','picture','photo'].indexOf(t) !== -1 || !!(e.url || e.src || (e.properties && (e.properties.src || e.properties.url)));
                var id = (e.id ? String(e.id) : (e.label || ('field_' + idx))).replace(/\s+/g, '_');
                var fontSize = (e.properties && e.properties.fontSize != null) ? e.properties.fontSize : (e.fontSize != null ? e.fontSize : undefined);
                return {
                    id: id,
                    type: isImage ? 'image' : 'text',
                    label: e.label || (isImage ? ('Image ' + (idx + 1)) : ('Text ' + (idx + 1))),
                    placeholder: e.label || '',
                    note: (e.note || e.help || e.description || e.hint || ''),
                    position: {
                        x: (typeof e.x === 'number') ? e.x : 20,
                        y: (typeof e.y === 'number') ? e.y : 60,
                        width: (typeof e.width === 'number') ? e.width : undefined,
                        height: (typeof e.height === 'number') ? e.height : undefined,
                        fontSize: fontSize
                    },
                    color: e.properties && e.properties.textColor ? e.properties.textColor : undefined,
                    fontStyle: e.properties && e.properties.fontStyle ? e.properties.fontStyle : undefined,
                    fontWeight: e.properties && e.properties.fontWeight ? e.properties.fontWeight : undefined,
                    fontFamily: e.properties && e.properties.fontFamily ? e.properties.fontFamily : undefined,
                    textAlign: e.properties && e.properties.textAlign ? e.properties.textAlign : undefined,
                    textDecoration: e.properties && e.properties.textDecoration ? e.properties.textDecoration : undefined,
                    textStrokeWidth: e.properties && e.properties.textStrokeWidth != null ? e.properties.textStrokeWidth : undefined,
                    textStrokeColor: e.properties && e.properties.textStrokeColor ? e.properties.textStrokeColor : undefined,
                    prefix: e.prefix || (e.properties && e.properties.prefix) || '',
                    suffix: e.suffix || (e.properties && e.properties.suffix) || '',
                    defaultValue: isImage ? (e.url || e.src || (e.properties && (e.properties.src || e.properties.url)) || '') : (e.properties && e.properties.text ? e.properties.text : (e.value || e.default || ''))
                };
            }) : [];

        var mappedTemplate = {
            name: product.title || 'Product Template',
            canvas: {
                width: td.canvas && typeof td.canvas.width === 'number' ? td.canvas.width : 800,
                height: td.canvas && typeof td.canvas.height === 'number' ? td.canvas.height : 600,
                background: td.canvas && td.canvas.background ? td.canvas.background : { type: 'color', color: '#ffffff' }
            },
            logo: {
                type: 'image',
                content: product.image || product.logo_url || product.logo_file || '',
                position: {
                    x: (logoEl && typeof logoEl.x === 'number') ? logoEl.x : 0,
                    y: (logoEl && typeof logoEl.y === 'number') ? logoEl.y : 0,
                    width: (logoEl && typeof logoEl.width === 'number') ? logoEl.width : 200,
                    height: (logoEl && typeof logoEl.height === 'number') ? logoEl.height : 100
                }
            },
            fields: fields,
            // Pass through color palette data
            colorPalette: td.colorPalette,
            colors: td.colors,
            colorOptions: td.colorOptions
        };
        
        console.log('Mapped template result:', mappedTemplate);
        console.log('=== END MAP TEMPLATE DEBUG ===');
        
        return mappedTemplate;
    }

    function renderTemplateIntoPreview(template, $container) {
        // Build container styles with background
        var style = '';
        var w = template.canvas && template.canvas.width ? template.canvas.width + 'px' : '100%';
        var h = template.canvas && template.canvas.height ? template.canvas.height + 'px' : '100%';
        style += 'position:relative;width:'+w+';height:'+h+';min-height:'+h+';';
        var bg = template.canvas && template.canvas.background ? template.canvas.background : null;
        
        // Debug: Log template data to see what we're working with
        window.debugTemplateData = function() {
            console.log('=== Template Data Debug ===');
            console.log('Full template:', template);
            console.log('Template canvas:', template.canvas);
            console.log('Background data:', bg);
            console.log('Background type:', typeof bg);
            if (bg && typeof bg === 'object') {
                console.log('Background keys:', Object.keys(bg));
                console.log('Background type property:', bg.type);
                console.log('Background stops/colors:', bg.stops || bg.colors);
            }
            console.log('Template color palette:', template.colorPalette || template.colors || template.colorOptions);
            console.log('=== End Debug ===');
        };
        
        if (bg) {
            if (typeof bg === 'string') {
                // Handle CSS gradient strings directly
                if (bg.includes('gradient')) {
                    style += 'background:'+bg+';';
                } else {
                    style += 'background-color:'+bg+';';
                }
            } else if ((bg.type === 'color' || bg.type === 'solid') && bg.color) {
                style += 'background-color:'+bg.color+';';
            } else if (bg.type === 'image') {
                var url = bg.url || bg.src || bg.image || bg.imageUrl;
                if (url) {
                    var size = bg.size || (bg.cover ? 'cover' : (bg.contain ? 'contain' : 'cover'));
                    var position = bg.position || 'center';
                    var repeat = (typeof bg.repeat === 'string') ? bg.repeat : (bg.repeat ? 'repeat' : 'no-repeat');
                    style += 'background-image:url('+url+');background-size:'+size+';background-position:'+position+';background-repeat:'+repeat+';';
                }
            } else if (bg.type === 'gradient' || bg.type === 'linear-gradient' || bg.type === 'radial-gradient' || bg.type === 'linearGradient' || bg.type === 'radialGradient') {
                console.log('Processing gradient background:', bg);
                var isRad = bg.type === 'radial-gradient' || bg.type === 'radialGradient';
                var angle = (typeof bg.angle === 'number') ? (bg.angle + 'deg') : (bg.angle || undefined);
                var direction = bg.direction || undefined;
                var stops = Array.isArray(bg.stops) ? bg.stops : (Array.isArray(bg.colors) ? bg.colors : []);
                
                console.log('Initial stops:', stops);
                
                // Handle different gradient data formats
                if (stops.length === 0 && bg.gradient) {
                    // Alternative format: bg.gradient contains the gradient data
                    stops = Array.isArray(bg.gradient.stops) ? bg.gradient.stops : (Array.isArray(bg.gradient.colors) ? bg.gradient.colors : []);
                    console.log('Found nested gradient, stops:', stops);
                    
                    // Handle color1/color2 format
                    if (stops.length === 0 && bg.gradient.color1 && bg.gradient.color2) {
                        stops = [
                            { color: bg.gradient.color1, offset: 0 },
                            { color: bg.gradient.color2, offset: 1 }
                        ];
                        console.log('Converted color1/color2 to stops:', stops);
                    }
                    
                    // Update direction from nested gradient
                    if (bg.gradient.direction) {
                        direction = bg.gradient.direction;
                        console.log('Using gradient direction:', direction);
                    }
                }
                
                if (stops.length > 0) {
                    // Enhanced stop processing with better fallbacks
                    var stopStr = stops.map(function(s, idx){ 
                        if (typeof s === 'string') {
                            // Handle simple color strings - add proper offsets
                            if (stops.length === 2) {
                                return s + (idx === 0 ? ' 0%' : ' 100%');
                            } else {
                                return s + (idx === 0 ? ' 0%' : (idx === stops.length - 1 ? ' 100%' : ' ' + Math.round((idx / (stops.length - 1)) * 100) + '%'));
                            }
                        }
                        
                        var color = s.color || s[0] || s;
                        var offset = '';
                        
                        if (typeof s.offset === 'number') {
                            // Convert decimal to percentage
                            offset = s.offset <= 1 ? Math.round(s.offset * 100) + '%' : s.offset + '%';
                        } else if (s.offset) {
                            offset = s.offset;
                        } else if (s[1]) {
                            offset = s[1];
                        } else {
                            // Auto-calculate offset based on position
                            if (stops.length === 2) {
                                offset = idx === 0 ? '0%' : '100%';
                            } else {
                                offset = idx === 0 ? '0%' : (idx === stops.length - 1 ? '100%' : Math.round((idx / (stops.length - 1)) * 100) + '%');
                            }
                        }
                        
                        return color + (offset ? (' ' + offset) : '');
                    }).join(', ');
                    
                    console.log('Processed stop string:', stopStr);
                    
                    if (isRad) {
                        var shape = bg.shape || 'ellipse';
                        var atPos = bg.position ? (' at ' + bg.position) : '';
                        var gradientStr = 'radial-gradient(' + shape + atPos + ', ' + stopStr + ')';
                        console.log('Generated radial gradient:', gradientStr);
                        style += 'background-image:' + gradientStr + ';';
                    } else {
                        var dir = direction || angle || '180deg';
                        
                        // Convert common direction strings to CSS values
                        if (dir === 'to bottom') dir = '180deg';
                        else if (dir === 'to top') dir = '0deg';
                        else if (dir === 'to right') dir = '90deg';
                        else if (dir === 'to left') dir = '270deg';
                        else if (dir === 'to bottom right') dir = '135deg';
                        else if (dir === 'to bottom left') dir = '225deg';
                        else if (dir === 'to top right') dir = '45deg';
                        else if (dir === 'to top left') dir = '315deg';
                        
                        var gradientStr = 'linear-gradient(' + dir + ', ' + stopStr + ')';
                        console.log('Generated linear gradient:', gradientStr);
                        style += 'background-image:' + gradientStr + ';';
                    }
                } else {
                    // Fallback if no stops found
                    console.log('No stops found, using white fallback');
                    style += 'background-color:#ffffff;';
                }
            } else {
                // Unknown background type, use white fallback
                console.log('Unknown background type:', bg.type, 'using white fallback');
                style += 'background-color:#ffffff;';
            }
        } else {
            console.log('No background data found, using white fallback');
            style += 'background-color:#ffffff;';
        }

        var html = '<div class="apd-template-container" style="'+style+'">';
        if (template.logo && template.logo.type === 'image') {
            var lx = (template.logo.position && typeof template.logo.position.x === 'number') ? template.logo.position.x : 0;
            var ly = (template.logo.position && typeof template.logo.position.y === 'number') ? template.logo.position.y : 0;
            var lw = (template.logo.position && typeof template.logo.position.width === 'number') ? template.logo.position.width : 200;
            var lh = (template.logo.position && typeof template.logo.position.height === 'number') ? template.logo.position.height : 100;
            var badge = '<div class="apd-slot-badge" style="position:absolute;top:6px;left:6px;background:rgba(255,255,255,0.85);color:#444;padding:2px 6px;border-radius:6px;font-size:12px;line-height:1;border:1px solid #ddd;">Logo</div>';
            if (template.logo.content) {
                var isSvg = /\.svg(\?|$)/i.test(template.logo.content);
                if (isSvg) {
                    html += '<div class="apd-logo-box" style="position:absolute;left:'+lx+'px;top:'+ly+'px;width:'+lw+'px;height:'+lh+'px;overflow:visible;border:1px dashed rgba(0,0,0,0.25);">'+
                                badge+
                                '<img class="apd-logo-image" src="'+template.logo.content+'" alt="Product Logo" style="width:100%;height:100%;object-fit:contain;display:block;" />'+
                            '</div>';
                } else {
                    html += '<div class="apd-logo-box apd-logo-bg" style="position:absolute;left:'+lx+'px;top:'+ly+'px;width:'+lw+'px;height:'+lh+'px;overflow:visible;border:1px dashed rgba(0,0,0,0.25);'+
                                'background-image:url('+JSON.stringify(template.logo.content)+');background-size:contain;background-position:center;background-repeat:no-repeat;">'+
                                badge+
                            '</div>';
                }
            } else {
                // Empty logo slot placeholder
                html += '<div class="apd-logo-box" style="position:absolute;left:'+lx+'px;top:'+ly+'px;width:'+lw+'px;height:'+lh+'px;overflow:hidden;border:1px dashed rgba(0,0,0,0.25);background:repeating-conic-gradient(#f2f2f2 0% 25%, #e9e9e9 0% 50%) 50% / 16px 16px;">'+
                            badge+
                        '</div>';
            }
        }
        (template.fields || []).forEach(function(field){
            if (field.type === 'text') {
                var prefix = field.prefix || '';
                var suffix = field.suffix || '';
                var v = field.defaultValue || '';
                
                // Combine prefix + value + suffix for display
                var displayText = prefix + v + suffix;
                
                var fs = (field.position.fontSize || 36);
                var ta = (field.textAlign || 'left');
                var anchor = (ta==='center'?'middle':(ta==='right'?'end':'start'));
                var fw = (field.fontWeight||'bold');
                var ff = (field.fontFamily||'Arial, sans-serif');
                var fstyle = (field.fontStyle||'normal');
                var fill = (field.color||'#000');
                var deco = (field.textDecoration||'none');
                var widthPx = (typeof field.position.width === 'number' ? field.position.width : 'auto');
                var initialStroke = (typeof field.textStrokeWidth === 'number' ? field.textStrokeWidth : 24);
                var heightPx = (field.position.height || (fs + initialStroke * 2));
                var leftPx = (field.position.x||20);
                var topPx = (field.position.y||60);
                var containerStyle = 'position:absolute;left:'+leftPx+'px;top:'+topPx+'px;width:'+widthPx+'px;height:'+heightPx+'px;pointer-events:none;user-select:none;overflow:visible;';
                // Render as SVG so we can apply material pattern stroke cleanly
                var viewW = (typeof field.position.width === 'number' ? field.position.width : 1000);
                var xPos = (ta==='left'?0:(ta==='center'?(viewW/2):(viewW)));
                var svg = ''+
                    '<svg class="apd-text-svg" data-field-id="'+field.id+'" data-prefix="'+escapeAttr(prefix)+'" data-suffix="'+escapeAttr(suffix)+'" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 '+viewW+' '+heightPx+'" preserveAspectRatio="xMinYMin meet" style="overflow:visible">'+
                        '<defs></defs>'+
                        '<text x="'+xPos+'" y="'+(fs + initialStroke)+'" font-size="'+fs+'" font-family="'+ff+'" font-style="'+fstyle+'" font-weight="'+fw+'" fill="'+fill+'" text-decoration="'+deco+'" text-anchor="'+anchor+'" paint-order="stroke fill">'+
                            escapeHtml(displayText)+
                        '</text>'+
                    '</svg>';
                html += '<div class="apd-field apd-field-'+field.id+'" style="'+containerStyle+'">'+svg+'</div>';
            } else if (field.type === 'image') {
                var iv = field.defaultValue;
                var s2 = 'position:absolute;left:'+(field.position.x||20)+'px;top:'+(field.position.y||60)+'px;width:'+(field.position.width||100)+'px;height:'+(field.position.height||100)+'px;pointer-events:none;user-select:none;border:1px dashed rgba(0,0,0,0.25);';
                var badge = '<div class="apd-slot-badge" style="position:absolute;top:6px;left:6px;background:rgba(255,255,255,0.85);color:#444;padding:2px 6px;border-radius:6px;font-size:12px;line-height:1;border:1px solid #ddd;">'+(field.label||'Image')+'</div>';
                if (iv) {
                    html += '<div class="apd-field apd-field-'+field.id+'" data-field-id="'+field.id+'" style="'+s2+'">'+
                                badge+
                                '<img src="'+iv+'" alt="'+(field.label||'Field Image')+'" style="width:100%;height:100%;object-fit:cover;" />'+
                            '</div>';
                } else {
                    html += '<div class="apd-field apd-field-'+field.id+'" data-field-id="'+field.id+'" style="'+s2+'background:repeating-conic-gradient(#f2f2f2 0% 25%, #e9e9e9 0% 50%) 50% / 16px 16px;">'+
                                badge+
                            '</div>';
                }
            }
        });
        html += '</div>';
        $container.html(html);

        // If logo is SVG URL, inline it so we can recolor and add material outline
        try {
            var $img = $container.find('.apd-logo-image');
            var src = $img.attr('src') || '';
            if (/\.svg(\?|$)/i.test(src)) {
                $.get(src, function(svg){
                    var $raw = $(svg).find('svg');
                    if ($raw.length) {
                        var $box = $img.parent();
                        var $stack = $('<div class="apd-logo-stack" style="position:absolute;inset:0"></div>');
                        var $outline = $raw.clone();
                        var $fill = $raw.clone();
                        $outline.addClass('apd-logo-inline logo-outline').attr('preserveAspectRatio','xMidYMid meet').css({position:'absolute',inset:0,width:'100%',height:'100%',display:'block',overflow:'visible'});
                        $fill.addClass('apd-logo-inline logo-fill').attr('preserveAspectRatio','xMidYMid meet').css({position:'absolute',inset:0,width:'100%',height:'100%',display:'block',overflow:'visible'});
                        $stack.append($outline).append($fill);
                        $img.replaceWith($stack);
                        // initialize outline: stroke none until user picks material (all shape tags)
                        $outline.find('path,rect,circle,ellipse,line,polyline,polygon').each(function(){ this.setAttribute('fill','none'); this.setAttribute('stroke','none'); });
                    }
                });
            }
            // If the logo area already contains an inline <svg>, convert it to stacked outline/fill
            var $inlineLogo = $container.find('.apd-logo-box > svg:not(.apd-logo-inline)');
            if ($inlineLogo.length) {
                var $stack2 = $('<div class="apd-logo-stack" style="position:absolute;inset:0"></div>');
                var $outline2 = $inlineLogo.clone();
                var $fill2 = $inlineLogo.clone();
                $outline2.addClass('apd-logo-inline logo-outline').attr('preserveAspectRatio','xMidYMid meet').css({position:'absolute',inset:0,width:'100%',height:'100%',display:'block',overflow:'visible'});
                $fill2.addClass('apd-logo-inline logo-fill').attr('preserveAspectRatio','xMidYMid meet').css({position:'absolute',inset:0,width:'100%',height:'100%',display:'block',overflow:'visible'});
                $stack2.append($outline2).append($fill2);
                $inlineLogo.replaceWith($stack2);
                $outline2.find('path,rect,circle,ellipse,line,polyline,polygon').each(function(){ this.setAttribute('stroke','none'); this.setAttribute('fill','none'); });
            }
        } catch(e) { /* noop */ }

        // If any image fields use SVG, inline them into outline/fill stacks as well
        try {
            $container.find('.apd-field img').each(function(){
                var $imgEl = $(this);
                var src = $imgEl.attr('src') || '';
                if (/\.svg(\?|$)/i.test(src)) {
                    $.get(src, function(svg){
                        var $raw = $(svg).find('svg');
                        if ($raw.length) {
                            var $stack = $('<div class="apd-image-stack" style="position:absolute;inset:0;width:100%;height:100%"></div>');
                            var $outline = $raw.clone();
                            var $fill = $raw.clone();
                            $outline.addClass('apd-logo-inline logo-outline').attr('preserveAspectRatio','xMidYMid meet').css({position:'absolute',inset:0,width:'100%',height:'100%',display:'block',overflow:'visible'});
                            $fill.addClass('apd-logo-inline logo-fill').attr('preserveAspectRatio','xMidYMid meet').css({position:'absolute',inset:0,width:'100%',height:'100%',display:'block',overflow:'visible'});
                            $stack.append($outline).append($fill);
                            $imgEl.replaceWith($stack);
                            $outline.find('path,rect,circle,ellipse,line,polyline,polygon').each(function(){ this.setAttribute('stroke','none'); this.setAttribute('fill','none'); });
                        }
                    });
                }
                // Also handle inline SVGs dropped directly in image fields
                var $inlineFieldSvg = $imgEl.closest('.apd-field').find('> svg:not(.apd-logo-inline)');
                if ($inlineFieldSvg.length) {
                    var $stack3 = $('<div class="apd-image-stack" style="position:absolute;inset:0;width:100%;height:100%"></div>');
                    var $outline3 = $inlineFieldSvg.clone();
                    var $fill3 = $inlineFieldSvg.clone();
                    $outline3.addClass('apd-logo-inline logo-outline').attr('preserveAspectRatio','xMidYMid meet').css({position:'absolute',inset:0,width:'100%',height:'100%',display:'block',overflow:'visible'});
                    $fill3.addClass('apd-logo-inline logo-fill').attr('preserveAspectRatio','xMidYMid meet').css({position:'absolute',inset:0,width:'100%',height:'100%',display:'block',overflow:'visible'});
                    $stack3.append($outline3).append($fill3);
                    $inlineFieldSvg.replaceWith($stack3);
                    $outline3.find('path,rect,circle,ellipse,line,polyline,polygon').each(function(){ this.setAttribute('stroke','none'); this.setAttribute('fill','none'); });
                }
            });
        } catch(e) { /* noop */ }
    }

    function renderDynamicInputs(template, $panel) {
        var inputs = '';
        (template.fields || []).forEach(function(field){
            if (field.type === 'text') {
                var prefix = field.prefix || '';
                var suffix = field.suffix || '';
                var defaultValue = field.defaultValue || '';
                var maxLength = field.maxLength || '';
                
                // Remove prefix and suffix from defaultValue if they exist
                if (prefix && defaultValue.startsWith(prefix)) {
                    defaultValue = defaultValue.substring(prefix.length);
                }
                if (suffix && defaultValue.endsWith(suffix)) {
                    defaultValue = defaultValue.substring(0, defaultValue.length - suffix.length);
                }
                
                var note = field.note || '';
                var maxLengthAttr = maxLength ? ' maxlength="'+escapeAttr(maxLength)+'"' : '';
                inputs += '<div class="apd-form-group">'+
                            '<label class="apd-form-label" for="apd-'+field.id+'">'+(field.label||'Text')+
                            (note ? ' <span class="apd-form-note" style="font-size:12px;color:#666;font-style:italic;margin-left:6px;">'+escapeHtml(String(note))+'</span>' : '')+
                            '</label>'+
                            '<div class="apd-input-with-affixes" style="display:flex;align-items:center;border:1px solid #ddd;border-radius:4px;background:white;">'+
                                (prefix ? '<span class="apd-prefix" style="padding:8px 12px;background:#f5f5f5;border-right:1px solid #ddd;color:#666;font-size:14px;white-space:nowrap;">'+escapeHtml(prefix)+'</span>' : '')+
                                '<input type="text" class="apd-form-input apd-text-input" id="apd-'+field.id+'" data-prefix="'+escapeAttr(prefix)+'" data-suffix="'+escapeAttr(suffix)+'" value="'+escapeAttr(defaultValue)+'"'+maxLengthAttr+' style="flex:1;border:none;outline:none;padding:8px 12px;font-size:14px;" />'+
                                (suffix ? '<span class="apd-suffix" style="padding:8px 12px;background:#f5f5f5;border-left:1px solid #ddd;color:#666;font-size:14px;white-space:nowrap;">'+escapeHtml(suffix)+'</span>' : '')+
                            '</div>'+
                          '</div>';
            } else if (field.type === 'image') {
                inputs += '<div class="apd-form-group">'+
                            '<label class="apd-form-label" for="apd-'+field.id+'">'+(field.label||'Image')+'</label>'+
                            '<input type="file" class="apd-form-input" id="apd-'+field.id+'" accept="image/*" />'+
                          '</div>';
            }
        });
        $panel.html(inputs);
    }

    function wireLiveUpdates(template, $root){
        console.log('=== WIRE LIVE UPDATES DEBUG ===');
        console.log('Template passed to wireLiveUpdates:', template);
        console.log('Template keys:', Object.keys(template));
        console.log('Template fields:', template.fields);
        console.log('Template colorPalette:', template.colorPalette);
        console.log('Template colors:', template.colors);
        console.log('Template colorOptions:', template.colorOptions);
        console.log('=== END WIRE LIVE UPDATES DEBUG ===');
        
        // Text updates
        (template.fields || []).forEach(function(field){
            if (field.type === 'text') {
                $root.on('input', '#apd-'+field.id, function(){
                    var val = $(this).val();
                    var prefix = $(this).data('prefix') || '';
                    var suffix = $(this).data('suffix') || '';
                    var displayText = prefix + val + suffix;
                    
                    var $svg = $root.find('.apd-text-svg[data-field-id="'+field.id+'"]');
                    var $t = $svg.find('text');
                    if ($t.length) { $t.text(displayText); }
                });
            } else if (field.type === 'image') {
                $root.on('change', '#apd-'+field.id, function(e){
                    var file = e.target.files && e.target.files[0];
                    if (!file) return;
                    var url = URL.createObjectURL(file);
                    var $slot = $root.find('.apd-field-'+field.id);
                    var $img = $slot.find('img');
                    if ($img.length) {
                        $img.attr('src', url);
                    } else {
                        // remove checkerboard and inject img
                        $slot.css('background', 'none');
                        $img = $('<img>').attr('src', url).attr('alt', field.label || 'Field Image')
                            .css({ width:'100%', height:'100%', objectFit:'cover' });
                        $slot.append($img);
                    }
                });
            }
        });

        // Use template color palette if available, otherwise fallback to default colors
        var templateColors = template.colorPalette || template.colors || template.colorOptions || null;
        var colors = {};
        
        console.log('=== COLOR PALETTE DEBUG ===');
        console.log('Template object:', template);
        console.log('Template colorPalette:', template.colorPalette);
        console.log('Template colors:', template.colors);
        console.log('Template colorOptions:', template.colorOptions);
        console.log('Final templateColors:', templateColors);
        console.log('TemplateColors type:', typeof templateColors);
        console.log('TemplateColors isArray:', Array.isArray(templateColors));
        
        if (templateColors && Array.isArray(templateColors)) {
            // Template has color palette array - don't render default colors
            console.log('Processing template colors as array:', templateColors);
            templateColors.forEach(function(color, idx) {
                var colorName = color.name || color.label || ('Color ' + (idx + 1));
                var colorValue = color.value || color.hex || color.color || color;
                colors[colorName] = colorValue;
                console.log('Color ' + idx + ':', colorName, '=', colorValue);
            });
        } else if (templateColors && typeof templateColors === 'object') {
            // Template has color palette object - don't render default colors
            console.log('Processing template colors as object:', templateColors);
            colors = templateColors;
        } else {
            // Fallback to default color map
            console.log('No template colors found, using default color map');
            colors = getColorMap();
        }
        
        console.log('Final colors object:', colors);
        console.log('=== END COLOR PALETTE DEBUG ===');
        
        var $grid = $root.find('.apd-color-grid');
        var $matGrid = $root.find('.apd-material-grid');
        var productId = $root.closest('.apd-product-display').data('product-id');
        var materialStorageKey = productId ? ('apd_material_url_'+productId) : null;
        var currentMaterialUrl = materialStorageKey ? localStorage.getItem(materialStorageKey) : null;
        
        // Only render colors if we have template colors, otherwise let product-customizer.js handle it
        console.log('=== COLOR RENDERING DEBUG ===');
        console.log('$grid.length:', $grid.length);
        console.log('templateColors exists:', !!templateColors);
        console.log('templateColors isArray:', Array.isArray(templateColors));
        console.log('templateColors isObject:', typeof templateColors === 'object');
        console.log('Should render colors:', $grid.length && (templateColors && (Array.isArray(templateColors) || typeof templateColors === 'object')));
        
        if ($grid.length && (templateColors && (Array.isArray(templateColors) || typeof templateColors === 'object'))) {
            console.log('Rendering template colors into .apd-color-grid');
            $grid.empty();
            var colorKeys = Object.keys(colors);
            var maxColors = Math.min(colorKeys.length, 12); // Limit to 12 colors max
            
            console.log('Color keys:', colorKeys);
            console.log('Max colors:', maxColors);
            
            colorKeys.slice(0, maxColors).forEach(function(name, idx){
                var colorValue = colors[name];
                console.log('Rendering color ' + idx + ':', name, '=', colorValue);
                var item = $('<div class="apd-color-item"></div>')
                    .css({ display:'inline-flex', flexDirection:'column', alignItems:'center', gap:'4px', margin:'4px' });
                var sw = $('<div class="apd-color-option" data-color="'+name+'" title="'+name+'"></div>')
                    .css({ width:'28px', height:'28px', borderRadius:'4px', backgroundColor: colorValue, cursor:'pointer', boxShadow:'inset 0 0 0 1px rgba(0,0,0,0.1)' });
                if (idx===0) sw.addClass('selected').css({ outline:'2px solid #0073aa' });
                var label = $('<div class="apd-color-label"></div>')
                    .text(name)
                    .css({ fontSize:'12px', color:'#555', textAlign:'center', maxWidth:'64px', wordBreak:'break-word', lineHeight:'1.1' });
                item.append(sw).append(label);
                $grid.append(item);
            });
        } else if ($grid.length) {
            // Hide the color grid if no template colors (let product-customizer.js handle it)
            console.log('Hiding .apd-color-grid - no template colors found');
            $grid.closest('.apd-form-group').hide();
        } else {
            console.log('No .apd-color-grid found');
        }
        console.log('=== END COLOR RENDERING DEBUG ===');

        // Color application function (only if we have template colors)
        if ($grid.length && (templateColors && (Array.isArray(templateColors) || typeof templateColors === 'object'))) {
            var applyColor = function(hex){
                // text SVG fill
                $root.find('.apd-text-svg text').each(function(){ 
                    this.setAttribute('fill', hex); 
                });
                
                // logo fill layer - support both stack and inline structures
                var $logoFillPaths = $root.find('.apd-logo-stack .apd-logo-fill svg path, .apd-logo-inline.logo-fill path');
                
                if ($logoFillPaths.length) {
                    $logoFillPaths.each(function(){ 
                        this.setAttribute('fill', hex); 
                        // Also update style property for better compatibility
                        this.style.fill = hex;
                    });
                } else {
                    // Fallback: try to find any logo-related paths
                    var $allLogoPaths = $root.find('.apd-logo-box svg path, .apd-logo-stack svg path');
                    
                    $allLogoPaths.each(function(){
                        // Only apply to fill layer if it exists, otherwise apply to all
                        var $parent = $(this).closest('.apd-logo-outline, .apd-logo-fill');
                        if ($parent.hasClass('apd-logo-fill') || $parent.length === 0) {
                            this.setAttribute('fill', hex);
                            this.style.fill = hex;
                        }
                    });
                }
            };

            $root.on('click', '.apd-color-option', function(){
                $root.find('.apd-color-option').removeClass('selected').css({ outline:'none' });
                $(this).addClass('selected').css({ outline:'2px solid #0073aa' });
                var hex = colors[$(this).data('color')] || '#000000';
                applyColor(hex);
            });

            // initial apply
            var firstHex = colors[Object.keys(colors)[0]]; applyColor(firstHex);
        }

        // Material outline options (reuse feight uploads)
        if ($matGrid.length) {
            var mats = [
                'Florentine_Silver','Engine_turn_gold','Diamond_Plate','gold'
            ];
            var base = (window.apd_material_base || (apd_ajax.plugin_url ? (apd_ajax.plugin_url + 'uploads/material/') : '/wp-content/plugins/Shop/uploads/material/'));
            $matGrid.empty();
            mats.forEach(function(name, index){
                var url = base + name + '.png';
                var sw = $('<div class="apd-material-option" data-url="'+url+'" title="'+name+'"></div>')
                    .css({ width:'32px', height:'32px', borderRadius:'6px', backgroundImage:'url('+url+')', backgroundSize:'cover', backgroundPosition:'center', cursor:'pointer', border:'2px solid #ddd', transition:'all 0.2s ease' });
                
                // Select the last material (gold) by default if no previous selection
                if (!currentMaterialUrl && index === mats.length - 1) {
                    sw.addClass('selected').css({ border:'2px solid #0073aa' });
                    currentMaterialUrl = url;
                }
                $matGrid.append(sw);
            });

            // Proper SVG pattern creator (avoids <img> issue)
            var SVG_NS = 'http://www.w3.org/2000/svg';
            var XLINK_NS = 'http://www.w3.org/1999/xlink';
            function toAbsoluteUrl(u){
                try {
                    if (!u) return u;
                    if (/^https?:\/\//i.test(u)) return u;
                    if (/^\/\//.test(u)) return (window.location.protocol + u);
                    if (/^\//.test(u)) {
                        var origin = (typeof apd_ajax === 'object' && apd_ajax.site_url) ? apd_ajax.site_url : (window.location.origin || '');
                        return origin.replace(/\/$/, '') + u;
                    }
                    return u;
                } catch(e){ return u; }
            }
            function ensureSvgPattern(defsEl, patternId, imageUrl) {
                if (!defsEl) {
                    console.error('ensureSvgPattern: No defs element provided');
                    return null;
                }
                if (!imageUrl) {
                    console.error('ensureSvgPattern: No image URL provided');
                    return null;
                }
                
                var doc = defsEl.ownerDocument;
                var pattern = defsEl.querySelector('#' + patternId);
                if (!pattern) {
                    pattern = doc.createElementNS(SVG_NS, 'pattern');
                    pattern.setAttribute('id', patternId);
                    pattern.setAttribute('patternUnits', 'userSpaceOnUse');
                    pattern.setAttribute('patternContentUnits', 'userSpaceOnUse');
                    // Use larger tile for better texture fidelity
                    pattern.setAttribute('width', '1024');
                    pattern.setAttribute('height', '1024');
                    defsEl.appendChild(pattern);
                    console.log('ensureSvgPattern: Created new pattern', patternId);
                } else {
                    while (pattern.firstChild) pattern.removeChild(pattern.firstChild);
                    console.log('ensureSvgPattern: Updating existing pattern', patternId);
                }
                
                var img = doc.createElementNS(SVG_NS, 'image');
                var abs = toAbsoluteUrl(imageUrl);
                console.log('ensureSvgPattern: Using absolute URL', abs);
                
                try { 
                    img.setAttributeNS(XLINK_NS, 'xlink:href', abs); 
                } catch(e) {
                    console.warn('ensureSvgPattern: Failed to set xlink:href', e);
                }
                img.setAttribute('href', abs);
                img.setAttribute('width', '1024');
                img.setAttribute('height', '1024');
                img.setAttribute('preserveAspectRatio', 'xMidYMid slice');
                
                // Add load event listener to verify image loads
                img.addEventListener('load', function() {
                    console.log('ensureSvgPattern: Image loaded successfully', abs);
                });
                img.addEventListener('error', function() {
                    console.error('ensureSvgPattern: Failed to load image', abs);
                });
                
                pattern.appendChild(img);
                return pattern;
            }

            // Remove paint attributes/styles that block our stroke on the OUTLINE layer
            function sanitizeOutline($svg){
                try {
                    if (!$svg || !$svg.length) return;
                    // Remove presentation attributes on all non-defs elements
                    $svg.find('*:not(defs *)').each(function(){
                        this.removeAttribute('stroke');
                        this.removeAttribute('stroke-width');
                        // Keep fills on images only; others will be set to none later
                        if (this.tagName && this.tagName.toLowerCase() !== 'image') {
                            this.removeAttribute('fill');
                        }
                        var st = this.getAttribute('style');
                        if (st && /fill\s*:|stroke\s*:/i.test(st)) {
                            st = st.replace(/(^|;)\s*(fill|stroke)\s*:[^;]*;?/gi, '$1');
                            this.setAttribute('style', st);
                        }
                    });
                } catch(e) {}
            }

            var currentLogoOutlineWidth = 24;
            var currentTextOutlineWidth = 24;
            var currentOutlineWidth = 24;

            // Unified outline width slider (applies to logo and text)
            $('#apd-outline-width').on('input change', function(){
                currentOutlineWidth = parseInt($(this).val()) || 0;
                $('#apd-outline-width-value').text(currentOutlineWidth);
                currentLogoOutlineWidth = currentOutlineWidth;
                currentTextOutlineWidth = currentOutlineWidth;
                // Update stroke width live (match Shop behavior)
                var $outline = $root.find('.apd-logo-inline.logo-outline');
                if ($outline.length) {
                    $outline.find('path').each(function(){ this.setAttribute('stroke-width', currentOutlineWidth); });
                }
                $root.find('.apd-text-svg text').each(function(){ this.setAttribute('stroke-width', String(currentOutlineWidth)); });
            });
            
            // Function to apply logo outline effect
            function applyLogoOutline(materialUrl, outlineWidth) {
                var $logoStack = $root.find('.apd-logo-stack');
                if ($logoStack.length) {
                    // Ensure we have the dual layer structure
                    ensureLogoLayers($logoStack);
                    
                    if (materialUrl && outlineWidth > 0) {
                        var materialPattern = createMaterialPattern(materialUrl);
                        
                        // Apply outline to bottom layer
                        $logoStack.find('.apd-logo-outline svg path').each(function() {
                            this.style.stroke = 'url(#' + materialPattern + ')';
                            this.style.strokeWidth = Math.max(outlineWidth, 8) + 'px';
                            this.style.strokeLinejoin = 'round';
                            this.style.strokeLinecap = 'round';
                            this.style.fill = 'none'; // No fill for outline layer
                            this.style.paintOrder = 'stroke';
                            this.style.vectorEffect = 'non-scaling-stroke';
                        });
                        
                        // Apply current selected color to fill layer (preserve user's color choice)
                        var currentSelectedColor = $root.find('.apd-color-option.selected').data('color');
                        var fillColor = currentSelectedColor ? getColorMap()[currentSelectedColor] : '#000';
                        $logoStack.find('.apd-logo-fill svg path').each(function() {
                            this.style.fill = fillColor;
                            this.setAttribute('fill', fillColor);
                            this.style.stroke = 'none';
                            this.style.strokeWidth = '0';
                        });
                    } else {
                        // Remove outline when width is 0
                        $logoStack.find('.apd-logo-outline svg path').each(function() {
                            this.style.stroke = 'none';
                            this.style.strokeWidth = '0';
                            this.style.fill = 'none';
                        });
                        
                        // Apply current selected color to fill layer (preserve user's color choice)
                        var currentSelectedColor = $root.find('.apd-color-option.selected').data('color');
                        var fillColor = currentSelectedColor ? getColorMap()[currentSelectedColor] : '#000';
                        $logoStack.find('.apd-logo-fill svg path').each(function() {
                            this.style.fill = fillColor;
                            this.setAttribute('fill', fillColor);
                            this.style.stroke = 'none';
                            this.style.strokeWidth = '0';
                        });
                    }
                }
            }
            
            // Function to apply text outline effect
            function applyTextOutline(materialUrl, outlineWidth) {
                $root.find('.apd-text-svg text').each(function() {
                    if (materialUrl && outlineWidth > 0) {
                        var $svg = $(this).closest('svg');
                        var materialPattern = createTextMaterialPattern($svg.get(0), materialUrl);
                        this.style.stroke = 'url(#' + materialPattern + ')';
                        this.style.strokeWidth = Math.max(outlineWidth, 8) + 'px'; // Minimum 8px for visibility
                        this.style.strokeLinejoin = 'round';
                        this.style.strokeLinecap = 'round';
                        this.style.paintOrder = 'stroke fill'; // Ensure stroke is drawn first
                        this.style.vectorEffect = 'non-scaling-stroke'; // Keep stroke width consistent
                        
                        // Add subtle contrast for better pattern visibility
                        var outlineFilter = 'drop-shadow(0 0 ' + (outlineWidth/8) + 'px rgba(0,0,0,0.5)) contrast(1.2) saturate(1.3)';
                        this.style.filter = outlineFilter;
                    } else {
                        // Remove outline when width is 0
                        this.style.stroke = 'none';
                        this.style.strokeWidth = '0';
                        this.style.filter = '';
                        this.style.paintOrder = '';
                        this.style.vectorEffect = '';
                    }
                });
            }

            // Ensure logo has dual layer structure (outline + fill)
            var ensureLogoLayers = function($logoStack) {
                // Check if we already have the dual layer structure
                if ($logoStack.find('.apd-logo-outline').length && $logoStack.find('.apd-logo-fill').length) {
                    return; // Already has dual layers
                }
                
                // Get the original SVG
                var $originalSvg = $logoStack.find('svg').first();
                if (!$originalSvg.length) return;
                
                var svgHtml = $originalSvg.prop('outerHTML');
                
                // Create dual layer structure
                var dualLayerHtml = 
                    '<div class="apd-logo-outline" style="position:absolute;inset:0;z-index:1;">' + svgHtml + '</div>' +
                    '<div class="apd-logo-fill" style="position:absolute;inset:0;z-index:2;">' + svgHtml + '</div>';
                
                // Replace the original SVG with dual layers
                $logoStack.html(dualLayerHtml);
                
                console.log('Created dual layer logo structure');
            };

            // Create SVG pattern from material PNG for text elements
            var createTextMaterialPattern = function(svg, materialUrl) {
                var patternId = 'text-material-pattern-' + materialUrl.replace(/[^a-zA-Z0-9]/g, '');
                
                // Remove ALL existing material patterns to prevent accumulation
                var defs = svg.querySelector('defs');
                if (defs) {
                    // Remove all patterns that start with 'text-material-pattern-'
                    var existingPatterns = defs.querySelectorAll('pattern[id^="text-material-pattern-"]');
                    existingPatterns.forEach(function(pattern) {
                        pattern.remove();
                    });
                } else {
                    defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
                    svg.insertBefore(defs, svg.firstChild);
                }
                
                var pattern = document.createElementNS('http://www.w3.org/2000/svg', 'pattern');
                pattern.setAttribute('id', patternId);
                pattern.setAttribute('patternUnits', 'userSpaceOnUse');
                pattern.setAttribute('patternContentUnits', 'userSpaceOnUse');
                pattern.setAttribute('width', '150');  // Smaller pattern for better visibility
                pattern.setAttribute('height', '150');
                
                var image = document.createElementNS('http://www.w3.org/2000/svg', 'image');
                image.setAttribute('href', materialUrl);
                image.setAttribute('x', '0');
                image.setAttribute('y', '0');
                image.setAttribute('width', '150');
                image.setAttribute('height', '150');
                image.setAttribute('preserveAspectRatio', 'none'); // Force to fit exactly
                
                pattern.appendChild(image);
                defs.appendChild(pattern);
                
                return patternId;
            };

            // Create SVG pattern from material PNG
            var createMaterialPattern = function(materialUrl) {
                var patternId = 'material-pattern-' + materialUrl.replace(/[^a-zA-Z0-9]/g, '');
                
                // Find all SVGs that might contain logo patterns
                var $allLogoSvgs = $root.find('.apd-logo-outline svg, .apd-logo-stack > svg, .apd-logo-stack svg');
                
                // Remove ALL existing material patterns from all SVGs to prevent accumulation
                $allLogoSvgs.each(function() {
                    var svg = this;
                    var defs = svg.querySelector('defs');
                    if (defs) {
                        // Remove all patterns that start with 'material-pattern-'
                        var existingPatterns = defs.querySelectorAll('pattern[id^="material-pattern-"]');
                        existingPatterns.forEach(function(pattern) {
                            pattern.remove();
                        });
                    }
                });
                
                // Create new pattern in the first available SVG
                var svg = $allLogoSvgs.get(0);
                if (!svg) {
                    return patternId;
                }
                
                var defs = svg.querySelector('defs');
                if (!defs) {
                    defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
                    svg.insertBefore(defs, svg.firstChild);
                }
                
                var pattern = document.createElementNS('http://www.w3.org/2000/svg', 'pattern');
                pattern.setAttribute('id', patternId);
                pattern.setAttribute('patternUnits', 'userSpaceOnUse');
                pattern.setAttribute('patternContentUnits', 'userSpaceOnUse');
                pattern.setAttribute('width', '1500');  // Smaller pattern for better visibility
                pattern.setAttribute('height', '1500');
                
                var image = document.createElementNS('http://www.w3.org/2000/svg', 'image');
                image.setAttribute('href', materialUrl);
                image.setAttribute('x', '0');
                image.setAttribute('y', '0');
                image.setAttribute('width', '8000');
                image.setAttribute('height', '8000');
                image.setAttribute('preserveAspectRatio', 'none'); // Force to fit exactly
                
                pattern.appendChild(image);
                defs.appendChild(pattern);
                
                return patternId;
            };

            // Global function to clean up all material patterns
            var cleanupAllMaterialPatterns = function() {
                // Remove all patterns from all SVGs
                $root.find('svg').each(function() {
                    var svg = this;
                    var defs = svg.querySelector('defs');
                    if (defs) {
                        // Remove all material patterns
                        var materialPatterns = defs.querySelectorAll('pattern[id*="material-pattern"], pattern[id*="text-material-pattern"]');
                        materialPatterns.forEach(function(pattern) {
                            pattern.remove();
                        });
                    }
                });
            };

            var applyMaterialOutline = function(url){
                if (!url) {
                    // Clean up all patterns first
                    cleanupAllMaterialPatterns();
                    
                    // Remove material patterns when no URL
                    $root.find('.apd-logo-stack svg path').each(function(){
                        this.style.filter = '';
                        this.style.stroke = 'none';
                        this.style.strokeWidth = '0';
                    });
                    $root.find('.apd-text-svg text').each(function(){
                        this.style.filter = '';
                        this.style.stroke = 'none';
                        this.style.strokeWidth = '0';
                    });
                    return;
                }
                
                currentMaterialUrl = url;
                if (materialStorageKey) { try { localStorage.setItem(materialStorageKey, url); } catch(e){} }
                
                // Clean up old patterns before applying new material
                cleanupAllMaterialPatterns();
                
                // Apply material to both logo and text with their respective outline widths
                applyLogoOutline(url, currentLogoOutlineWidth);
                applyTextOutline(url, currentTextOutlineWidth);
                
                return; // Skip the complex implementation below
                
                // Ensure logo has been inlined into outline/fill stack
                var $outline = $root.find('.apd-logo-inline.logo-outline');
                var $fill = $root.find('.apd-logo-inline.logo-fill');
                if ($outline.length === 0 || $fill.length === 0) {
                    console.log('applyMaterialOutline: SVG not inlined yet, attempting to inline');
                    var $img = $root.find('.apd-logo-box img.apd-logo-image');
                    var src = $img.attr('src');
                    if ($img.length && /\.svg(\?|$)/i.test(src)) {
                        $.get(src, function(svg){
                            var $raw = $(svg).find('svg');
                            if ($raw.length) {
                                var $stack = $('<div class="apd-logo-stack" style="position:absolute;inset:0"></div>');
                                var $outlineSvg = $raw.clone();
                                var $fillSvg = $raw.clone();
                                $outlineSvg.addClass('apd-logo-inline logo-outline').attr('preserveAspectRatio','xMidYMid meet').css({position:'absolute',inset:0,width:'100%',height:'100%',display:'block',overflow:'visible'});
                                $fillSvg.addClass('apd-logo-inline logo-fill').attr('preserveAspectRatio','xMidYMid meet').css({position:'absolute',inset:0,width:'100%',height:'100%',display:'block',overflow:'visible'});
                                $stack.append($outlineSvg).append($fillSvg);
                                $img.replaceWith($stack);
                                // Initialize clean outline layer
                                $outlineSvg.find('path').each(function(){ this.setAttribute('fill','none'); this.setAttribute('stroke','none'); });
                                // Retry applying after inline is ready
                                setTimeout(function() {
                                    applyMaterialOutline(url);
                                }, 100);
                            }
                        }).fail(function() {
                            console.error('applyMaterialOutline: Failed to load SVG from', src);
                        });
                        return; // wait for async inline then re-apply
                    } else {
                        console.warn('applyMaterialOutline: No SVG image found or not SVG format');
                        return;
                    }
                }
                // Add pattern defs to any inline svg logo
                // Apply to ALL inline outline/fill svg stacks (logo and image areas)
                $root.find('.apd-logo-inline.logo-outline').each(function(i){
                    var $outlineSvg = $(this);
                    var $fillSvg = $outlineSvg.siblings('.apd-logo-inline.logo-fill');
                    // Ensure no inherited fill/stroke suppresses our stroke when using stroke method
                    sanitizeOutline($outlineSvg);
                    var $defs = $outlineSvg.find('defs');
                    if ($defs.length === 0) { $outlineSvg.prepend('<defs></defs>'); $defs = $outlineSvg.find('defs'); }
                    var pid = 'apdLogoPattern' + i;
                    var defsNode = $defs.get(0);
                    // Use the robust creator that sets both href and xlink:href
                    ensureSvgPattern(defsNode, pid, url);
                    // New approach: render outline via filter+mask, pattern-filled rect masked by dilated alpha ring
                    // 1) Ensure a single wrapper group that represents the logo source
                    var $src = $outlineSvg.find('g.apd-logo-src');
                    if ($src.length === 0) {
                        $src = $('<g class="apd-logo-src"></g>');
                        // move all children except defs into the src group
                        $outlineSvg.children().not('defs').appendTo($src);
                        $outlineSvg.append($src);
                    }
                    // 2) Build filter that creates an outline ring from alpha
                    var fid = 'apdLogoOutlineFilter' + i;
                    var mid = 'apdLogoOutlineMask' + i;
                    if ($outlineSvg.find('#'+fid).length === 0) {
                        var filter = document.createElementNS('http://www.w3.org/2000/svg', 'filter');
                        filter.setAttribute('id', fid);
                        filter.setAttribute('filterUnits','userSpaceOnUse');
                        filter.setAttribute('x','0'); filter.setAttribute('y','0');
                        filter.setAttribute('width','100%'); filter.setAttribute('height','100%');
                        var feMorph = document.createElementNS('http://www.w3.org/2000/svg','feMorphology');
                        feMorph.setAttribute('in','SourceAlpha');
                        feMorph.setAttribute('operator','dilate');
                        feMorph.setAttribute('radius','1'); // updated below each apply
                        filter.appendChild(feMorph);
                        var feComp = document.createElementNS('http://www.w3.org/2000/svg','feComposite');
                        feComp.setAttribute('operator','out');
                        feComp.setAttribute('in2','SourceAlpha');
                        filter.appendChild(feComp);
                        $defs.get(0).appendChild(filter);
                    }
                    // Compute effective radius in SVG units
                    var vb = ($outlineSvg.attr('viewBox')||'').split(/\s+/);
                    var vbW = vb.length===4 ? parseFloat(vb[2]) : null;
                    var bboxW = $outlineSvg.get(0).getBoundingClientRect ? $outlineSvg.get(0).getBoundingClientRect().width : null;
                    var scaleX = (vbW && bboxW) ? (bboxW / vbW) : 1;
                    var gFirst = $src; // group we created sits at original transform
                    var gScale = 1; var tf = gFirst.attr('transform')||''; var m = tf.match(/scale\(([-\d\.eE]+)/);
                    if (m) { var sx = parseFloat(m[1]); if (isFinite(sx) && sx!==0) gScale = Math.abs(sx); }
                    var totalScaleX = scaleX * gScale;
                    var radius = totalScaleX ? (currentOutlineWidth / totalScaleX) : currentOutlineWidth;
                    var filt = $outlineSvg.find('#'+fid);
                    filt.find('feMorphology').attr('radius', String(radius));
                    // 3) Create/update mask that applies the ring to a pattern rect
                    var $mask = $outlineSvg.find('#'+mid);
                    if ($mask.length === 0) {
                        $mask = $('<mask id="'+mid+'" maskUnits="userSpaceOnUse"></mask>');
                        var $use = $('<use xlink:href="#" href="#" />');
                        $use.attr('href', '#'+ $src.attr('id') || '');
                        // ensure the src group has an id
                        if (!$src.attr('id')) { $src.attr('id','apdLogoSrc'+i); $use.attr('href','#apdLogoSrc'+i); }
                        $use.attr('filter','url(#'+fid+')');
                        $mask.append($use);
                        $defs.append($mask);
                    } else {
                        var $useUpd = $mask.find('use');
                        if (!$src.attr('id')) { $src.attr('id','apdLogoSrc'+i); }
                        $useUpd.attr('href','#'+$src.attr('id')).attr('filter','url(#'+fid+')');
                    }
                    // 4) Apply pattern fill to logo paths for outline effect
                    console.log('Applying pattern', pid, 'to outline SVG paths');
                    $outlineSvg.find('path').each(function(){
                        console.log('Setting fill pattern for path element');
                        this.setAttribute('fill','url(#'+pid+')');
                        this.setAttribute('stroke','none');
                        // Force browser to re-render
                        var currentDisplay = this.style.display;
                        this.style.display = 'none';
                        this.offsetHeight; // trigger reflow
                        this.style.display = currentDisplay;
                    });
                    // Remove old mask-based rect if exists
                    $outlineSvg.find('rect.apd-pattern-outline').remove();
                    // Ensure fill layer stays solid
                    if ($fillSvg.length) { 
                        $fillSvg.find('path,rect,circle,ellipse,line,polyline,polygon').each(function(){ 
                            this.setAttribute('stroke','none'); 
                        }); 
                    }
                });

                // Apply material stroke to text SVGs (outline effect)
                $root.find('.apd-text-svg').each(function(){
                    var $svg = $(this);
                    var $defs = $svg.find('defs');
                    if ($defs.length === 0) { $svg.prepend('<defs></defs>'); $defs = $svg.find('defs'); }
                    var tpid = 'apdTextPattern';
                    var defsNode = $defs.get(0);
                    ensureSvgPattern(defsNode, tpid, url);
                    var strokeW = parseInt($('#apd-outline-width').val() || '24', 10);
                    $svg.find('text').each(function(){ this.setAttribute('stroke','url(#'+tpid+')'); this.setAttribute('stroke-width', String(strokeW)); this.setAttribute('stroke-linejoin','round'); this.setAttribute('stroke-linecap','round'); this.setAttribute('paint-order','stroke fill'); this.setAttribute('vector-effect','non-scaling-stroke'); });
                });
            };

            $root.on('click', '.apd-material-option', function(){
                var url = $(this).data('url');
                applyMaterialOutline(url);
                $root.find('.apd-material-option').removeClass('selected').css({ border:'2px solid #ddd' });
                $(this).addClass('selected').css({ border:'2px solid #0073aa' });
            });

            // Restore previously selected material, if any, or apply the default
            if (currentMaterialUrl) {
                console.log('Restoring saved material selection:', currentMaterialUrl);
                applyMaterialOutline(currentMaterialUrl);
                $root.find('.apd-material-option').each(function(){ 
                    if ($(this).data('url') === currentMaterialUrl) { 
                        $(this).addClass('selected').css({ border:'2px solid #0073aa' }); 
                    } 
                });
            } else {
                // Apply default material (gold) if no previous selection
                var $defaultMaterial = $root.find('.apd-material-option.selected').first();
                if ($defaultMaterial.length) {
                    var defaultUrl = $defaultMaterial.data('url');
                    console.log('Applying default material:', defaultUrl);
                    setTimeout(function() {
                        applyMaterialOutline(defaultUrl);
                    }, 500); // Delay to ensure SVG is fully loaded
                }
            }
        }
    }

    function escapeHtml(s){ return String(s).replace(/[&<>"']/g, function(c){ return ({'&':'&amp;','<':'&lt;','>':'&gt','"':'&quot;','\'':'&#39;'})[c]; }); }
    function escapeAttr(s){ return String(s).replace(/"/g, '&quot;'); }
    function getColorMap(){
        return {
            'black':'#000000', 'yellow':'#FFFF00', 'dark-red':'#8B0000', 'orange':'#FFA500',
            'light-blue':'#87CEEB','light-green':'#90EE90','purple':'#800080','light-grey':'#D3D3D3',
            'brown':'#A52A2A','bright-yellow':'#FFD700','dark-green':'#006400','light-purple':'#DDA0DD'
        };
    }

    function renderCardLayout(product, showPrice, showDescription) {
        return `
            <div class="apd-product-card" data-product-id="${product.id}">
                <div class="apd-product-image-container">
                    <img src="${product.image || '/wp-content/plugins/Shop/assets/images/placeholder.png'}" 
                         alt="${product.title}" 
                         class="apd-product-image">
                </div>
                <div class="apd-product-content">
                    <h3 class="apd-product-title">${product.title}</h3>
                    ${showPrice ? `<div class="apd-product-price">$${product.price}</div>` : ''}
                    ${showDescription && product.description ? `<div class="apd-product-description">${product.description}</div>` : ''}
                    <div class="apd-product-actions">
                        <button class="apd-btn apd-btn-primary apd-customize-btn" data-product-id="${product.id}">Start Customizing</button>
                        <button class="apd-btn apd-btn-secondary apd-view-details-btn" data-product-id="${product.id}">Customize</button>
                    </div>
                </div>
            </div>
        `;
    }

    function renderListLayout(product, showPrice, showDescription) {
        return `
            <div class="apd-product-list-item" data-product-id="${product.id}">
                <div class="apd-product-image-container">
                    <img src="${product.image || '/wp-content/plugins/Shop/assets/images/placeholder.png'}" 
                         alt="${product.title}" 
                         class="apd-product-image">
                </div>
                <div class="apd-product-content">
                    <h3 class="apd-product-title">${product.title}</h3>
                    ${showPrice ? `<div class="apd-product-price">$${product.price}</div>` : ''}
                    ${showDescription && product.description ? `<div class="apd-product-description">${product.description}</div>` : ''}
                    <div class="apd-product-actions">
                        <button class="apd-btn apd-btn-primary apd-customize-btn" data-product-id="${product.id}">Start Customizing</button>
                        <button class="apd-btn apd-btn-secondary apd-view-details-btn" data-product-id="${product.id}">Customize</button>
                    </div>
                </div>
            </div>
        `;
    }

    function renderGridLayout(product, showPrice, showDescription) {
        return `
            <div class="apd-product-grid-item" data-product-id="${product.id}">
                <div class="apd-product-image-container">
                    <img src="${product.image || '/wp-content/plugins/Shop/assets/images/placeholder.png'}" 
                         alt="${product.title}" 
                         class="apd-product-image">
                </div>
                <div class="apd-product-content">
                    <h3 class="apd-product-title">${product.title}</h3>
                    ${showPrice ? `<div class="apd-product-price">$${product.price}</div>` : ''}
                    ${showDescription && product.description ? `<div class="apd-product-description">${product.description}</div>` : ''}
                    <div class="apd-product-actions">
                        <button class="apd-btn apd-btn-primary apd-customize-btn" data-product-id="${product.id}">Start Customizing</button>
                        <button class="apd-btn apd-btn-secondary apd-view-details-btn" data-product-id="${product.id}">Customize</button>
                    </div>
                </div>
            </div>
        `;
    }

    function addProductInteractions($block, product) {
        // Product click handler - click anywhere on product item (goes to product detail page)
        $block.find('.apd-product-card, .apd-product-list-item, .apd-product-grid-item').on('click', function(e) {
            // Don't trigger if clicking on buttons or links
            if ($(e.target).is('button, a, .apd-btn') || $(e.target).closest('button, a, .apd-btn').length) {
                return;
            }
            
            // Go to product detail page with Add to Cart, Customize, Checkout options
            const productDetailUrl = `${apd_ajax.site_url}/product-detail/?id=${product.id}`;
            window.location.href = productDetailUrl;
        });

        // Start Customizing button click (primary button) - goes to customizer
        $block.find('.apd-customize-btn').on('click', function(e) {
            e.stopPropagation(); // Prevent product click
            const productId = $(this).data('product-id') || product.id;
            // Go directly to customizer URL
            const customizerUrl = `${apd_ajax.site_url}/customizer/${productId}/`;
            window.location.href = customizerUrl;
        });

        // View Details button click (secondary button) - goes to product detail page
        $block.find('.apd-view-details-btn').on('click', function(e) {
            e.stopPropagation(); // Prevent product click
            const productId = $(this).data('product-id') || product.id;
            // Go to product detail page
            const productDetailUrl = `${apd_ajax.site_url}/product-detail/?id=${productId}`;
            window.location.href = productDetailUrl;
        });

        // Image click to go to product detail page
        $block.find('.apd-product-image').on('click', function(e) {
            e.stopPropagation(); // Prevent product click
            // Go to product detail page
            const productDetailUrl = `${apd_ajax.site_url}/product-detail/?id=${product.id}`;
            window.location.href = productDetailUrl;
        });

        // Enhanced hover effects with smooth transitions
        $block.find('.apd-product-image').css({
            'transition': 'transform 0.3s ease, box-shadow 0.3s ease',
            'border-radius': '8px',
            'overflow': 'hidden'
        }).on('mouseenter', function() {
            $(this).css({
                'transform': 'scale(1.05)',
                'box-shadow': '0 8px 25px rgba(0,0,0,0.15)'
            });
        }).on('mouseleave', function() {
            $(this).css({
                'transform': 'scale(1)',
                'box-shadow': 'none'
            });
        });

        // Add cursor pointer and hover effects to clickable areas
        $block.find('.apd-product-card, .apd-product-list-item, .apd-product-grid-item')
            .css({
                'cursor': 'pointer',
                'transition': 'transform 0.2s ease, box-shadow 0.2s ease'
            })
            .on('mouseenter', function() {
                $(this).css({
                    'transform': 'translateY(-2px)',
                    'box-shadow': '0 4px 12px rgba(0,0,0,0.15)'
                });
            })
            .on('mouseleave', function() {
                $(this).css({
                    'transform': 'translateY(0)',
                    'box-shadow': 'none'
                });
            });
    }

    function getProductUrl(productId) {
        // Helper function to generate product URL based on configuration
        let baseUrl = apd_ajax && apd_ajax.site_url ? apd_ajax.site_url : window.location.origin;
        
        // Use product detail page with all action options (Add to Cart, Customize, Checkout)
        return `${baseUrl}/product-detail/?id=${productId}`;
        
        // Alternative approaches:
        // 1. Customizer page directly: return `${baseUrl}/customizer/${productId}/`;
        // 2. Custom permalink: return `${baseUrl}/products/${productSlug}/`;
        // 3. WordPress post type: return `${baseUrl}/?post_type=apd_product&p=${productId}`;
    }

    function openCustomizer(productId) {
        // Open customizer page for direct customization
        const customizerUrl = `${apd_ajax.site_url}/customizer/${productId}/`;
        window.location.href = customizerUrl;
    }

    function showError($block, message) {
        $block.html(`
            <div class="apd-error">
                <strong>Error:</strong> ${message}
            </div>
        `);
    }

    // Handle dynamic content loading (for AJAX-loaded pages)
    $(document).on('DOMNodeInserted', function(e) {
        const $newBlock = $(e.target).find('.apd-product-display');
        if ($newBlock.length > 0) {
            initProductBlocks();
        }
    });

    // Alternative approach using MutationObserver for better performance
    if (typeof MutationObserver !== 'undefined') {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            const $node = $(node);
                            if ($node.hasClass('apd-product-display') || $node.find('.apd-product-display').length > 0) {
                                initProductBlocks();
                            }
                        }
                    });
                }
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    // Temporary debug function for gradient testing
    window.testGradient = function() {
        var $container = $('.apd-template-container');
        if ($container.length) {
            var style = $container.attr('style');
            console.log('Current container style:', style);
            
            var computedStyle = window.getComputedStyle($container[0]);
            console.log('Computed background-image:', computedStyle.backgroundImage);
            console.log('Computed background-color:', computedStyle.backgroundColor);
            
            // Test with a simple gradient
            $container.css('background-image', 'linear-gradient(45deg, #ff0000, #0000ff)');
            console.log('Applied test gradient');
        } else {
            console.log('No template container found');
        }
    };

    // Debug function to test product navigation
    window.testProductNavigation = function(productId) {
        if (!productId) {
            productId = '123'; // Default test ID
        }
        console.log('Testing product navigation with ID:', productId);
        const testUrl = getProductUrl(productId);
        console.log('Generated URL:', testUrl);
        console.log('This will open the product detail page with Add to Cart, Customize, and Checkout options');
        // Uncomment the line below to actually navigate
        // window.location.href = testUrl;
    };

    // Debug function to list all product elements
    window.listProductElements = function() {
        const products = [];
        $('.apd-product-display').each(function() {
            const productId = $(this).data('product-id');
            const productTitle = $(this).find('.apd-product-title').text();
            products.push({
                id: productId,
                title: productTitle,
                element: this
            });
        });
        console.log('Found products:', products);
        return products;
    };

    // Enhanced product detail page interactions
    function initProductDetailInteractions() {
        // Enhanced quantity selector with controls
        $('.apd-quantity-input').on('input change', function() {
            let value = parseInt($(this).val()) || 1;
            if (value < 1) value = 1;
            if (value > 100) value = 100;
            $(this).val(value);
        });

        // Quantity control buttons
        $('.apd-qty-plus').on('click', function() {
            const targetId = $(this).data('target');
            const $input = $('#' + targetId);
            let value = parseInt($input.val()) || 1;
            if (value < 100) {
                value++;
                $input.val(value).trigger('change');
            }
        });

        $('.apd-qty-minus').on('click', function() {
            const targetId = $(this).data('target');
            const $input = $('#' + targetId);
            let value = parseInt($input.val()) || 1;
            if (value > 1) {
                value--;
                $input.val(value).trigger('change');
            }
        });

        // Add to cart with loading state
        $('.apd-btn-add-cart').on('click', function(e) {
            e.preventDefault();
            const $btn = $(this);
            const productId = $btn.data('product-id');
            const quantity = parseInt($('#apd-quantity-' + productId).val()) || 1;
            
            // Show loading state
            $btn.prop('disabled', true).html('🔄 Adding...');
            
            // Simulate add to cart (replace with actual AJAX call)
            setTimeout(() => {
                $btn.prop('disabled', false).html('🛒 Added to Cart!');
                setTimeout(() => {
                    $btn.html('🛒 Add to Cart');
                }, 2000);
            }, 1000);
            
            console.log('Add to cart:', { productId, quantity });
        });

        // Customize button with smooth transition
        $('.apd-btn-customize').on('click', function(e) {
            e.preventDefault();
            const $btn = $(this);
            const productId = $btn.data('product-id') || window.location.search.match(/id=(\d+)/)?.[1];
            
            if (productId) {
                // Add loading effect
                $btn.prop('disabled', true).html('🎨 Opening...');
                
                setTimeout(() => {
                    const customizerUrl = `${apd_ajax.site_url}/customizer/${productId}/`;
                    window.location.href = customizerUrl;
                }, 500);
            }
        });

        // Related product hover effects
        $('.apd-related-item').css({
            'transition': 'all 0.3s ease',
            'cursor': 'pointer'
        }).on('mouseenter', function() {
            $(this).css({
                'transform': 'translateY(-8px)',
                'box-shadow': '0 8px 25px rgba(0,0,0,0.15)'
            });
        }).on('mouseleave', function() {
            $(this).css({
                'transform': 'translateY(0)',
                'box-shadow': '0 2px 4px rgba(0,0,0,0.1)'
            });
        });

        // Image gallery functionality (if multiple images)
        $('.apd-product-image').on('click', function() {
            const src = $(this).attr('src');
            if (src && src !== '/wp-content/plugins/Shop/assets/images/placeholder.png') {
                // Open image in lightbox or new tab
                window.open(src, '_blank');
            }
        });

        // Smooth scroll to sections
        $('a[href^="#"]').on('click', function(e) {
            e.preventDefault();
            const target = $(this.getAttribute('href'));
            if (target.length) {
                $('html, body').animate({
                    scrollTop: target.offset().top - 100
                }, 800);
            }
        });

        // Quick preview functionality
        $('.apd-btn-preview').on('click', function(e) {
            e.preventDefault();
            const productId = $(this).data('product-id');
            const $btn = $(this);
            
            // Show loading state
            $btn.prop('disabled', true).html('🔄 Loading...');
            
            // Create preview modal
            const modalHtml = `
                <div class="apd-preview-modal" style="
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0,0,0,0.8);
                    z-index: 9999;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    animation: fadeIn 0.3s ease;
                ">
                    <div class="apd-preview-content" style="
                        background: white;
                        border-radius: 12px;
                        padding: 20px;
                        max-width: 90%;
                        max-height: 90%;
                        position: relative;
                        animation: slideIn 0.3s ease;
                    ">
                        <button class="apd-close-preview" style="
                            position: absolute;
                            top: 10px;
                            right: 15px;
                            background: none;
                            border: none;
                            font-size: 24px;
                            cursor: pointer;
                            color: #666;
                        ">&times;</button>
                        <div class="apd-preview-loading" style="
                            text-align: center;
                            padding: 40px;
                            color: #666;
                        ">
                            <div style="font-size: 18px; margin-bottom: 10px;">🎨</div>
                            <div>Loading customizer preview...</div>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
            
            // Load customizer preview
            setTimeout(() => {
                $('.apd-preview-loading').html(`
                    <div style="text-align: center; padding: 20px;">
                        <h3>Quick Preview</h3>
                        <p>This would show a live preview of the customizer interface.</p>
                        <a href="${apd_ajax.site_url}/customizer/${productId}/" 
                           class="apd-btn apd-btn-primary" 
                           style="margin-top: 15px;">
                            🎨 Open Full Customizer
                        </a>
                    </div>
                `);
            }, 1000);
            
            // Close modal handlers
            $('.apd-close-preview, .apd-preview-modal').on('click', function(e) {
                if (e.target === this) {
                    $('.apd-preview-modal').remove();
                }
            });
            
            // Reset button
            setTimeout(() => {
                $btn.prop('disabled', false).html('👁️ Quick Preview');
            }, 1000);
        });

        // Add CSS animations
        if (!$('#apd-preview-styles').length) {
            $('head').append(`
                <style id="apd-preview-styles">
                    @keyframes fadeIn {
                        from { opacity: 0; }
                        to { opacity: 1; }
                    }
                    @keyframes slideIn {
                        from { transform: translateY(-30px); opacity: 0; }
                        to { transform: translateY(0); opacity: 1; }
                    }
                </style>
            `);
        }
    }

    // Initialize product detail interactions when DOM is ready
    $(document).ready(function() {
        if ($('.apd-product-detail-wrapper').length) {
            initProductDetailInteractions();
        }
    });


})(jQuery);
