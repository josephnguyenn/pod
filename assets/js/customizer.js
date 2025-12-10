jQuery(document).ready(function($) {
    
    // Parse URL parameters for product variants
    const urlParams = new URLSearchParams(window.location.search);
    const variantData = {
        size: urlParams.get('variant_size'),
        material: urlParams.get('variant_material'),
        sku: urlParams.get('variant_sku'),
        price: urlParams.get('variant_price')
    };
    
    console.log('🎨 Variant data from URL:', variantData);
    
    // Use apd_ajax (WordPress plugin standard)
    const ajaxObj = apd_ajax;
    
    // Initialize customizer
    const FSC = {
        currentColor: null,
        currentMaterial: null,
        quantity: 1,
        productPrice: null,
        baseProductPrice: null,
        salePrice: null,
        basePriceForCart: null,
        productId: null,
        productName: null,
        currentMaterialUrl: null,
        colorMap: {},
        materialsMap: null,
        materialsLoading: false,
        variantData: variantData, // Store variant info from URL
        _lastMaterialUrl: null,
        _lastColor: null,
        _materialUrlCache: {},
        _updatePreviewScheduled: false,
        _updatePreviewTimeout: null,
        _outlineApplyScheduled: false,
        _lastTextOutline: { url: null, width: null },
        fixedLogoOutlineWidth: 24,
        fixedTextOutlineWidth: 24,
        _base64ConversionStats: { total: 0, converted: 0, failed: 0 },

        // Function to update checkout preview with materials
        updateCheckoutPreview: function() {
            if (FSC.disableCheckoutPreview === true) {
                return;
            }
            console.log('=== UPDATE CHECKOUT PREVIEW DEBUG ===');
            var checkoutPreview = document.getElementById('checkout-preview');
            console.log('Checkout preview element:', checkoutPreview);
            if (!checkoutPreview) {
                console.log('No checkout preview element found');
                return;
            }

            var payload = null;
            try {
                var storedPayload = localStorage.getItem('apd_checkout_payload');
                console.log('Stored payload from localStorage:', storedPayload);
                if (storedPayload) {
                    payload = JSON.parse(storedPayload);
                    console.log('Parsed payload:', payload);
                }
            } catch(e) {
                console.error('Failed to parse checkout payload:', e);
                return;
            }

            if (!payload || !payload.vinyl_material) {
                console.log('No payload or vinyl_material found:', payload);
                return;
            }

            var materialName = payload.vinyl_material.toLowerCase();
            var materialUrl = null;
            console.log('Looking for material:', materialName);
            console.log('Available materials map:', FSC.materialsMap);

            if (FSC.materialsMap) {
                for (var name in FSC.materialsMap) {
                    console.log('Checking material:', name, 'against:', materialName);
                    if (name.toLowerCase() === materialName) {
                        materialUrl = FSC.materialsMap[name];
                        console.log('Found material URL:', materialUrl);
                        break;
                    }
                }
            } else {
                console.log('No materials map available');
            }

            if (materialUrl) {
                console.log('Updating preview info with material:', materialUrl);
                var previewInfo = document.getElementById('preview-info');
                var materialInfo = document.getElementById('preview-material-info');
                console.log('Preview info elements:', {previewInfo: previewInfo, materialInfo: materialInfo});
                if (previewInfo && materialInfo) {
                    previewInfo.style.display = 'block';
                    var formattedMaterial = materialName.replace(/\b\w/g, function(l) { return l.toUpperCase(); });
                    materialInfo.innerHTML = 'Material: <img src="' + materialUrl + '" alt="' + formattedMaterial + '" style="width: 20px; height: 20px; vertical-align: middle; margin-left: 5px; border-radius: 2px;"> ' + formattedMaterial;
                    console.log('Material preview updated successfully');
                } else {
                    console.log('Preview info elements not found');
                }
            } else {
                console.log('No material URL found for:', materialName);
            }
        },

        // Auto-select material from variant data
        autoSelectVariantMaterial: function(materialName) {
            console.log('🎯 Auto-selecting variant material:', materialName);
            
            // Find material option by name (case-insensitive)
            const $materialOption = $('.fsc-material-outline-option, .fsc-material-option').filter(function() {
                const optionMaterial = $(this).data('material') || '';
                return optionMaterial.toLowerCase() === materialName.toLowerCase();
            });
            
            if ($materialOption.length > 0) {
                console.log('✅ Found material option, triggering click');
                $materialOption.trigger('click');
            } else {
                console.warn('⚠️ Material option not found:', materialName);
                console.log('Available materials:', $('.fsc-material-outline-option, .fsc-material-option').map(function() {
                    return $(this).data('material');
                }).get());
            }
        },

        // Function to ensure material group is loaded
        ensureMaterialGroup: function(){
            console.log('=== ENSURE MATERIAL GROUP DEBUG ===');
            var materialsMap = FSC.materialsMap || (window.fscDefaults && window.fscDefaults.materials) || null;
            console.log('Current materialsMap:', materialsMap);
            console.log('window.apd_ajax:', window.apd_ajax);
            console.log('FSC.materialsLoading:', FSC.materialsLoading);

            if (!materialsMap && window.apd_ajax && window.apd_ajax.ajax_url && !FSC.materialsLoading) {
                console.log('Loading materials via AJAX...');
                FSC.materialsLoading = true;
                $.ajax({
                    url: window.apd_ajax.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'apd_get_materials',
                        nonce: (window.apd_ajax.fsc_nonce || window.apd_ajax.nonce || '46aeaf88d9'),
                        security: (window.apd_ajax.fsc_nonce || window.apd_ajax.nonce || '46aeaf88d9'),
                        _wpnonce: (window.apd_ajax.fsc_nonce || window.apd_ajax.nonce || '46aeaf88d9'),
                        apd_nonce: (window.apd_ajax.fsc_nonce || window.apd_ajax.nonce || '46aeaf88d9')
                    },
                    complete: function(){ FSC.materialsLoading = false; },
                    success: function(resp){
                        console.log('Materials AJAX success response:', resp);
                        if (resp && resp.success && resp.data && resp.data.materials) {
                            console.log('Materials loaded successfully:', resp.data.materials);
                            FSC.materialsMap = resp.data.materials;
                            FSC.ensureMaterialGroup();
                            if (document.getElementById('checkout-preview')) {
                                console.log('Updating checkout preview with materials...');
                                FSC.updateCheckoutPreview();
                            }
                        } else {
                            console.error('Invalid materials response:', resp);
                        }
                    },
                    error: function(xhr, status, error){
                        console.error('Failed to load materials:', {xhr: xhr, status: status, error: error});
                        if (!FSC.materialsMap) {
                            setTimeout(function() {
                                if (!FSC.materialsLoading) {
                                    FSC.ensureMaterialGroup();
                                }
                            }, 1000);
                        }
                    }
                });
                return;
            }

            var renderMaterialGroup = function(materialsMap){
                var $panel = $('.fsc-container, .fsc-form');
                var $afterColor = $('.fsc-form-group:has(h4:contains("Print Color"))');
                if (!materialsMap || !$panel.length) return;
                var $existing = $('.fsc-form-group.fsc-material-outline-group');
                if ($existing.length === 0) {
                    var $matGroup = $('<div class="fsc-form-group fsc-material-outline-group" />');
                    $matGroup.append('<h4 style="margin:8px 0 10px;">Material Outline</h4>');
                    var $grid = $('<div class="fsc-material-outline-grid" style="display:grid;grid-template-columns:repeat(6,80px);gap:10px;" />');
                    Object.keys(materialsMap).forEach(function(name){
                        // Handle both old format (string URL) and new format (object with url and price)
                        var materialData = materialsMap[name];
                        var url = typeof materialData === 'string' ? materialData : (materialData.url || '');
                        var price = typeof materialData === 'object' && materialData.price !== undefined ? parseFloat(materialData.price) : 0;
                        
                        var $opt = $('<div class="fsc-material-outline-option" />')
                            .attr('data-material', name)
                            .attr('data-material-url', url)
                            .attr('data-material-price', price)
                            .attr('title', name + (price > 0 ? ' (+$' + price.toFixed(2) + ')' : ''))
                            .css({
                                width:'80px',height:'96px',borderRadius:'6px',cursor:'pointer',
                                border:'2px solid #ddd',display:'flex',flexDirection:'column',alignItems:'center',justifyContent:'flex-start',
                                overflow:'hidden',background:'#fff'
                            })
                            .append(
                                $('<div class="fsc-material-thumb" />').css({
                                    width:'100%',height:'64px',backgroundImage:'url('+url+')',backgroundSize:'cover',backgroundPosition:'center'
                                })
                            )
                            .append(
                                $('<div class="fsc-material-name" />').text(name).css({
                                    width:'100%',padding:'4px 4px',fontSize:'11px',lineHeight:'14px',textAlign:'center',
                                    whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis',color:'#333'
                                })
                            );
                        $grid.append($opt);
                    });
                    $matGroup.append($grid);
                    if ($afterColor.length) { $afterColor.after($matGroup); } else { $panel.prepend($matGroup); }
                }
                $('.fsc-material-outline-option').off('click.apdMatGrid').on('click.apdMatGrid', function(){
                    $('.fsc-material-outline-option').css('border','2px solid #ddd').removeClass('selected');
                    $(this).addClass('selected').css('border','2px solid #0073aa');
                    FSC.currentMaterial = $(this).data('material');
                    FSC.currentMaterialUrl = $(this).data('material-url');
                    FSC.updatePreview();
                    // Update price when material is selected
                    FSC.updateProductPrice();
                });
                
                // Auto-select material from variant URL parameter
                if (variantData.material) {
                    setTimeout(() => {
                        FSC.autoSelectVariantMaterial(variantData.material);
                    }, 500);
                }
                
                return true;
            };
            if (materialsMap) { renderMaterialGroup(materialsMap); }
        },

        resolveSelectedMaterialUrl: function(){
            try {
                var $sel = $('.fsc-material-outline-option.selected, .fsc-material-option.selected').first();
                if ($sel.length === 0) return null;
                
                var materialName = $sel.data('material');
                if (!materialName) return this._getMaterialUrlFromNode($sel);
                
                if (FSC._materialUrlCache[materialName]) {
                    return FSC._materialUrlCache[materialName];
                }
                
                var url = this._getMaterialUrlFromNode($sel);
                if (url) {
                    FSC._materialUrlCache[materialName] = url;
                }
                return url;
            } catch(e){ return null; }
        },

        _getMaterialUrlFromNode: function($el){
            if (!$el || !$el.length) return null;
            try {
                var url = ($el.data('material-url') || $el.data('url') || $el.attr('data-material-url') || $el.attr('data-url') || $el.attr('data-src')) || null;
                if (!url) {
                    var bg = ($el.css('background-image') || '').toString();
                    var m = bg.match(/url\(["']?(.*?)["']?\)/i);
                    if (m && m[1]) url = m[1];
                }
                if (!url) {
                    var img = $el.find('img').attr('src');
                    if (img) url = img;
                }
                return url || null;
            } catch(e){ return null; }
        },

        scheduleUpdatePreview: function() {
            if (FSC._updatePreviewScheduled) return;
            FSC._updatePreviewScheduled = true;
            
            if (FSC._updatePreviewTimeout) {
                clearTimeout(FSC._updatePreviewTimeout);
            }
            
            FSC._updatePreviewTimeout = setTimeout(function() {
                FSC._updatePreviewScheduled = false;
                FSC.updatePreview();
            }, 100);
        },
        
        updatePreview: function() {
            try {
                const $preview = $('.fsc-preview-content, .apd-template-canvas-full');
            $preview.addClass('updating');
            
            // Default: no outline until user selects a material
            const isSolid = (FSC.currentMaterial === 'Solid');
            let materialUrl = null;
            if (!isSolid) {
                    try {
                materialUrl = FSC.resolveSelectedMaterialUrl() || FSC.getMaterialUrl(FSC.currentMaterial);
                    } catch(e) {
                        console.warn('Failed to resolve material URL:', e);
                        materialUrl = null;
                    }
                }
                
                // Performance optimization: skip if material URL hasn't changed
                if (FSC._lastMaterialUrl === materialUrl && FSC._lastColor === FSC.currentColor) {
                    $preview.removeClass('updating');
                    return;
                }
                
                FSC._lastMaterialUrl = materialUrl;
                FSC._lastColor = FSC.currentColor;
            
            // Update preview with current settings
            const $customizerPreview = $('.fsc-preview-content');
            if ($customizerPreview.length) {
                try {
                    let $textContainer = $customizerPreview.find('.fsc-text-container');
                    if ($textContainer.length === 0) {
                        $textContainer = $('<div class="fsc-text-container"></div>');
                        $customizerPreview.append($textContainer);
                    }
                    
                    const textSvg = FSC.buildTextSvg();
                    if (textSvg) {
                        $textContainer.html(textSvg);
                    }
                    
                    FSC.updateTextColors();
                } catch(e) {
                    console.warn('Failed to update customizer preview:', e);
                }
            }
            
            // Update thumbnails
                try {
            this.updateThumbnails();
                } catch(e) {
                    console.warn('Failed to update thumbnails:', e);
                }
            
                setTimeout(function() { $preview.removeClass('updating'); }, 300);
            } catch(e) {
                console.error('Failed to update preview:', e);
                $preview.removeClass('updating');
            }
        },
        
        updateThumbnails: function() {
            const colors = ['yellow', 'dark-red', 'bright-yellow', 'light-blue'];
            
            $('.fsc-thumbnail').each(function(index) {
                const color = colors[index];
                if (color) {
                    $(this).find('.fsc-thumbnail-svg').css('color', FSC.getColorValue(color));
                    $(this).data('color', color);
                    
                    if (color === FSC.currentColor) {
                        $(this).addClass('selected');
                    } else {
                        $(this).removeClass('selected');
                    }
                }
            });
        },
        
        buildTextSvg: function(materialUrl) {
			const items = [];
			
            $('#fsc-template-fields input, #fsc-template-fields textarea').each(function() {
                const $input = $(this);
                const elementId = $input.data('element-id') || $input.attr('id');
				const value = ($input.val() || '').toString();
                const $label = $('#fsc-template-fields label[for="' + elementId + '"]');
				const labelText = $label.length > 0 ? $label.text() : ($input.attr('placeholder') || elementId);
				if (elementId && value.trim()) {
					items.push({ id: elementId, label: labelText, value: value });
				}
			});
			
			let svgContent = '<div class="fsc-text-svg-container">';
			const currentColor = FSC.getColorValue(FSC.currentColor) || '#000000';
			
			items.forEach(function(item, index) {
				const y = 60 + (index * 80);
				svgContent += `
					<div class="fsc-text-item" style="position: absolute; top: ${y}px; left: 50%; transform: translateX(-50%); text-align: center; color: ${currentColor}; font-weight: 700; font-size: 28px; font-family: 'Inter', sans-serif; text-shadow: 2px 2px 4px rgba(0,0,0,0.1);">
						${item.value}
					</div>
				`;
			});
			svgContent += '</div>';
			
			return svgContent;
        },

        initCustomizerPreview: function() {
            console.log('Initializing customizer preview...');

            const $previewContent = $('.fsc-preview-content');
            if ($previewContent.length === 0) {
                console.log('Not on customizer page, skipping preview init');
                return;
            }

            const $templateFields = $('#fsc-template-fields');
            if ($templateFields.length > 0 && $templateFields.children().length === 0) {
                const isDebugMode = window.location.search.includes('debug=1') || window.location.search.includes('test=1');
                if (isDebugMode) {
                    console.log('Creating default template fields for testing...');
                    $templateFields.html(`
                        <div class="fsc-input-group">
                            <label for="fsc-text-1">Company Name</label>
                            <input type="text" id="fsc-text-1" data-element-id="fsc-text-element-1" placeholder="Enter company name" value="Sample Company">
                        </div>
                        <div class="fsc-input-group">
                            <label for="fsc-text-2">Truck Number</label>
                            <input type="text" id="fsc-text-2" data-element-id="fsc-text-element-2" placeholder="Enter truck number" value="TRK-001">
                        </div>
                        <div class="fsc-input-group">
                            <label for="fsc-text-3">VIN Number</label>
                            <input type="text" id="fsc-text-3" data-element-id="fsc-text-element-3" placeholder="Enter VIN number" value="1HGBH41JXMN109186">
                        </div>
                    `);
                } else {
                    console.log('No template fields found. Customizer will work with existing fields only.');
                }
            }

            let $textContainer = $previewContent.find('.fsc-text-container');
            if ($textContainer.length === 0) {
                $textContainer = $('<div class="fsc-text-container"></div>');
                $previewContent.append($textContainer);
            }

            const textSvg = FSC.buildTextSvg();
            if (textSvg) {
                $textContainer.html(textSvg);
                console.log('Initial text content generated');
            }

            if (FSC.currentColor) {
                FSC.updateTextColors();
            }

            // Bind input events for real-time preview updates
            FSC.bindInputEvents();
            FSC.initializeTextLimits();
            
            // Re-apply text limits when template data changes
            $(document).on('templateDataUpdated', function() {
                FSC.initializeTextLimits();
            });
            
            // Apply limits immediately when inputs are added to DOM
            $(document).on('DOMNodeInserted', function(e) {
                const target = e.target;
                if (target && target.nodeType === 1) { // Element node
                    const $target = $(target);
                if ($target.is('input[type="text"], textarea') || $target.find('input[type="text"], textarea').length > 0) {
                        setTimeout(function() {
                            FSC.initializeTextLimits();
                        }, 100);
                    }
                }
            });
            
            // Also apply limits on any input focus
            $(document).on('focus', 'input[type="text"], textarea', function() {
                const $input = $(this);
                if ($input.attr('maxlength')) return;
                // Try to infer limit from cached template data
                try {
                    const elementId = $input.data('element-id') || $input.attr('id');
                    const searchId = (elementId || '').replace(/^fsc-text-/, '');
                    const cached = FSC._cachedTemplateData;
                    if (searchId && cached && Array.isArray(cached.elements)) {
                        const el = cached.elements.find(function(x){ return x && x.id === searchId; });
                        const limit = el && el.properties ? el.properties.maxLength : null;
                        if (limit) {
                            $input.attr('maxlength', limit);
                            $input.prop('maxLength', limit);
                            console.log('🔧 Applied template maxLength to focused input:', searchId, limit);
                            FSC.validateTextLimits($input);
                        }
                    }
                } catch(e) { /* noop */ }
            });

            console.log('Customizer preview initialized');
        },

        initializeTextLimits: function() {
            try {
                console.log('🔧 Initializing text limits...');
                console.log('🔧 Cached template data:', FSC._cachedTemplateData);
                
                $('#fsc-template-fields input, #fsc-template-fields textarea').each(function() {
                    const $input = $(this);
                    const elementId = $input.data('element-id') || $input.attr('id');
                    const searchId = (elementId || '').replace(/^fsc-text-/, '');
                    console.log('🔧 Processing input:', elementId, $input);
                    
                    if (!elementId) {
                        console.log('❌ No element ID for input:', $input);
                        return;
                    }
                    
                    // Find the corresponding template element
                    const cachedTemplateData = FSC._cachedTemplateData;
                    if (!cachedTemplateData || !cachedTemplateData.elements) {
                        console.log('❌ No cached template data or elements');
                        return;
                    }
                    
                    const element = cachedTemplateData.elements.find(el => el.id === searchId);
                    console.log('🔧 Found element:', element);
                    
                    if (!element || element.type !== 'text') {
                        console.log('❌ Element not found or not text type');
                        return;
                    }
                    
                    const properties = element.properties || {};
                    const maxLength = properties.maxLength;
                    console.log('🔧 Max length from properties:', maxLength);
                    
                    if (maxLength) {
                        console.log('🔧 Setting maxlength attribute to', maxLength, 'for element', elementId);
                        $input.attr('maxlength', maxLength);
                        $input.prop('maxLength', maxLength);
                        FSC.validateTextLimits($input);
                    } else {
                        console.log('❌ No max length set for element:', elementId);
                    }
                });
                console.log('✅ Text limits initialized');
            } catch(e) {
                console.error('Error initializing text limits:', e);
            }
        },

        enforceTemplateMax: function() {
            try {
                $('#fsc-template-fields input, #fsc-template-fields textarea').each(function(){
                    const $input = $(this);
                    const tpl = $input.attr('data-template-max');
                    if (tpl && String($input.attr('maxlength')) !== String(tpl)) {
                        $input.attr('maxlength', tpl);
                        $input.prop('maxLength', Number(tpl));
                        console.log('🔒 Enforced template maxLength on', $input.attr('id'), '->', tpl);
                    }
                });
            } catch(e) { console.error('Error enforcing template max', e); }
        },

        ensureMaxlengthObserver: function() {
            try {
                if (FSC._maxlengthObserverInitialized) return;
                const container = document.getElementById('fsc-template-fields');
                if (!container || typeof MutationObserver === 'undefined') return;
                const obs = new MutationObserver(function(muts){
                    muts.forEach(function(m){
                        if (m.type === 'attributes' && m.attributeName === 'maxlength' && m.target) {
                            const el = m.target;
                            const tpl = el.getAttribute('data-template-max');
                            if (tpl && String(el.getAttribute('maxlength')) !== String(tpl)) {
                                el.setAttribute('maxlength', String(tpl));
                                try { el.maxLength = Number(tpl); } catch(_) {}
                                console.log('🔁 Restored maxlength due to external change on', el.id, '->', tpl);
                            }
                        }
                    });
                });
                obs.observe(container, { subtree: true, attributes: true, attributeFilter: ['maxlength'] });
                FSC._maxlengthObserverInitialized = true;
            } catch(e) { console.error('Error setting maxlength observer', e); }
        },

        setCharacterLimit: function(limit) {
            try {
                console.log('🔧 Manually setting character limit to:', limit);
                $('#fsc-template-fields input, #fsc-template-fields textarea').each(function() {
                    const $input = $(this);
                    $input.attr('maxlength', limit);
                    $input.prop('maxLength', limit);
                    console.log('🔧 Set limit for input:', $input.attr('id'), 'to', limit);
                });
            } catch(e) {
                console.error('Error setting character limit:', e);
            }
        },

        setCharacterLimitById: function(inputId, limit) {
            try {
                console.log('🔧 Setting character limit for input ID:', inputId, 'to', limit);
                const $input = $('#' + inputId);
                if ($input.length) {
                    $input.attr('maxlength', limit);
                    $input.prop('maxLength', limit);
                    console.log('✅ Set limit for input:', inputId, 'to', limit);
                } else {
                    console.log('❌ Input not found:', inputId);
                }
            } catch(e) {
                console.error('Error setting character limit by ID:', e);
            }
        },

        setAllTextLimits: function(limit) {
            try {
                console.log('🔧 Setting ALL text inputs to limit (template-aware):', limit);
                const cached = FSC._cachedTemplateData;
                $('input[type="text"], textarea').each(function() {
                    const $input = $(this);
                    // Never overwrite an explicit template max
                    const templateMaxAttr = $input.attr('data-template-max');
                    if (templateMaxAttr) {
                        $input.attr('maxlength', templateMaxAttr);
                        $input.prop('maxLength', Number(templateMaxAttr));
                        return;
                    }

                    // Respect existing maxlength if already set
                    if ($input.attr('maxlength')) {
                        return;
                    }

                    const elementId = $input.data('element-id') || $input.attr('id');
                    const searchId = (elementId || '').replace(/^fsc-text-/, '');
                    if (cached && Array.isArray(cached.elements) && searchId) {
                        const el = cached.elements.find(function(x){ return x && x.id === searchId; });
                        const tplLimit = el && el.properties ? el.properties.maxLength : null;
                        if (tplLimit) {
                            $input.attr('maxlength', tplLimit);
                            $input.prop('maxLength', tplLimit);
                            $input.attr('data-template-max', String(tplLimit));
                            console.log('🔧 Applied template maxLength for', searchId, tplLimit);
                            return;
                        }
                    }
                    // No template value: do not impose fallback; leave unchanged
                });
            } catch(e) {
                console.error('Error setting all text limits:', e);
            }
        },

        validateTextLimits: function($input) {
            try {
                const elementId = $input.data('element-id') || $input.attr('id');
                console.log('🔍 Validating text limits for element:', elementId);
                
                if (!elementId) {
                    console.log('❌ No element ID found');
                    return;
                }
                
                // Find the corresponding template element
                const cachedTemplateData = FSC._cachedTemplateData;
                console.log('🔍 Cached template data:', cachedTemplateData);
                
                if (!cachedTemplateData || !cachedTemplateData.elements) {
                    console.log('❌ No cached template data or elements');
                    return;
                }
                
                const element = cachedTemplateData.elements.find(el => el.id === elementId);
                console.log('🔍 Found element:', element);
                
                if (!element || element.type !== 'text') {
                    console.log('❌ Element not found or not text type');
                    return;
                }
                
                const properties = element.properties || {};
                const maxLength = properties.maxLength;
                const currentValue = $input.val();
                const currentLength = currentValue.length;
                
                console.log('🔍 Max length:', maxLength, 'Current length:', currentLength);
                
                // Remove existing validation classes and messages
                $input.removeClass('fsc-text-limit-exceeded');
                $input.siblings('.fsc-text-limit-message').remove();
                
                // Check max length
                if (maxLength && currentLength > maxLength) {
                    console.log('⚠️ Exceeding max length!');
                    $input.addClass('fsc-text-limit-exceeded');
                    $input.after(`<div class="fsc-text-limit-message" style="color: #dc3545; font-size: 12px; margin-top: 2px;">Maximum ${maxLength} characters allowed (${currentLength}/${maxLength})</div>`);
                    return false;
                }
                
                // Show character count if max length is set
                if (maxLength) {
                    console.log('✅ Showing character count');
                    $input.after(`<div class="fsc-text-limit-message" style="color: #6c757d; font-size: 12px; margin-top: 2px;">${currentLength}/${maxLength} characters</div>`);
                }
                
                return true;
            } catch(e) {
                console.error('Error validating text limits:', e);
                return true;
            }
        },

        bindInputEvents: function() {
            // Bind events to all text inputs for real-time preview updates
            $(document).on('input', '#fsc-template-fields input, #fsc-template-fields textarea', function() {
                FSC.validateTextLimits($(this));
                FSC.scheduleUpdatePreview();
            });
            
            // Direct enforcement of maxlength attribute
            $(document).on('input', '#fsc-template-fields input, #fsc-template-fields textarea', function() {
                const $input = $(this);
                const maxLength = $input.attr('maxlength');
                
                if (maxLength) {
                    const currentValue = $input.val();
                    if (currentValue.length > maxLength) {
                        console.log('🚫 Truncating input to max length:', maxLength);
                        $input.val(currentValue.substring(0, maxLength));
                    }
                }
            });
            
            // Prevent typing beyond max length
            $(document).on('keydown', '#fsc-template-fields input, #fsc-template-fields textarea', function(e) {
                const $input = $(this);
                const elementId = $input.data('element-id') || $input.attr('id');
                
                if (!elementId) return;
                
                // Find the corresponding template element
                const cachedTemplateData = FSC._cachedTemplateData;
                if (!cachedTemplateData || !cachedTemplateData.elements) return;
                
                const element = cachedTemplateData.elements.find(el => el.id === elementId);
                if (!element || element.type !== 'text') return;
                
                const properties = element.properties || {};
                const maxLength = properties.maxLength;
                
                if (maxLength) {
                    const currentValue = $input.val();
                    const currentLength = currentValue.length;
                    
                    // Allow backspace, delete, arrow keys, etc.
                    const allowedKeys = [8, 9, 27, 46, 37, 38, 39, 40, 16, 17, 18, 91, 93]; // backspace, tab, escape, delete, arrows, ctrl, alt, cmd
                    
                    // Check if we're at or beyond the limit
                    if (currentLength >= maxLength && !allowedKeys.includes(e.which)) {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log('🚫 Prevented typing beyond max length:', currentLength, '>=', maxLength);
                        return false;
                    }
                }
            });
            
            // Also prevent paste beyond max length
            $(document).on('paste', '#fsc-template-fields input, #fsc-template-fields textarea', function(e) {
                const $input = $(this);
                const elementId = $input.data('element-id') || $input.attr('id');
                
                if (!elementId) return;
                
                // Find the corresponding template element
                const cachedTemplateData = FSC._cachedTemplateData;
                if (!cachedTemplateData || !cachedTemplateData.elements) return;
                
                const element = cachedTemplateData.elements.find(el => el.id === elementId);
                if (!element || element.type !== 'text') return;
                
                const properties = element.properties || {};
                const maxLength = properties.maxLength;
                
                if (maxLength) {
                    const currentValue = $input.val();
                    const currentLength = currentValue.length;
                    
                    // Get pasted text
                    const pastedText = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
                    const newLength = currentLength + pastedText.length;
                    
                    if (newLength > maxLength) {
                        e.preventDefault();
                        console.log('🚫 Prevented paste beyond max length:', newLength, '>', maxLength);
                        return false;
                    }
                }
            });

            // Bind events to color options
            $(document).on('click', '.fsc-color-option', function() {
                $('.fsc-color-option').removeClass('selected');
                $(this).addClass('selected');
                FSC.currentColor = $(this).data('color');
                try {
                    $('#fsc-selected-color-name').text(String(FSC.currentColor || 'None'));
                } catch(e) {}
                FSC.updateTextColors();
                FSC.scheduleUpdatePreview();
                
                // Force immediate logo color update
                setTimeout(function() {
                    try {
                        const fillColor = FSC.getColorValue ? FSC.getColorValue(FSC.currentColor) : '#000';
                        FSC.setLogoFillColor(fillColor);
                    } catch(e) {
                        console.error('Error applying logo color:', e);
                    }
                }, 100);
            });

            // Initialize selected color label on load (default to first option)
            setTimeout(function(){
                var $first = $('.fsc-color-option').first();
                if ($first.length) {
                    var initialName = $first.data('color');
                    if ($('#fsc-selected-color-name').length) {
                        $('#fsc-selected-color-name').text(String(initialName || 'None'));
                    }
                }
            }, 0);

            // Bind events to material options
            $(document).on('click', '.fsc-material-outline-option', function() {
                $('.fsc-material-outline-option').removeClass('selected');
                $(this).addClass('selected');
                FSC.currentMaterial = $(this).data('material');
                FSC.scheduleUpdatePreview();
            });

            // Bind quantity buttons
            $(document).off('click', '#fsc-quantity-plus').on('click', '#fsc-quantity-plus', function() {
                const $input = $('#fsc-quantity');
                const currentVal = parseInt($input.val()) || 1;
                const maxVal = parseInt($input.attr('max')) || 100;
                if (currentVal < maxVal) {
                    $input.val(currentVal + 1);
                    FSC.quantity = currentVal + 1;
                }
            });

            $(document).off('click', '#fsc-quantity-minus').on('click', '#fsc-quantity-minus', function() {
                const $input = $('#fsc-quantity');
                const currentVal = parseInt($input.val()) || 1;
                const minVal = parseInt($input.attr('min')) || 1;
                if (currentVal > minVal) {
                    $input.val(currentVal - 1);
                    FSC.quantity = currentVal - 1;
                }
            });

            // Bind quantity input
            $(document).off('input', '#fsc-quantity').on('input', '#fsc-quantity', function() {
                FSC.quantity = parseInt($(this).val()) || 1;
            });

        },

        initializeDefaultSelections: function() {
            // Select first color option if none selected
            if (!$('.fsc-color-option.selected').length) {
                const $firstColor = $('.fsc-color-option').first();
                if ($firstColor.length) {
                    $firstColor.addClass('selected');
                    FSC.currentColor = $firstColor.data('color');
                }
            }

            // Initialize material pagination
            FSC.initializeMaterialPagination();

            // Select first material option if none selected
            if (!$('.fsc-material-outline-option.selected').length) {
                const $firstMaterial = $('.fsc-material-outline-option').first();
                if ($firstMaterial.length) {
                    $firstMaterial.addClass('selected');
                    FSC.currentMaterial = $firstMaterial.data('material');
                }
            }

            // Update preview with default selections
            FSC.updatePreview();
        },

        initializeMaterialPagination: function() {
            // Sample materials data
            const materials = [
                { name: 'Diamond Plate', image: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA2NCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjY0IiBoZWlnaHQ9IjQwIiBmaWxsPSIjRjVGNUY1Ii8+CjxwYXRoIGQ9Ik0wIDQwTDY0IDBIMFY0MFoiIGZpbGw9IiNFRUVFRUUiLz4KPC9zdmc+Cg==' },
                { name: 'Brushed Metal', image: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA2NCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjY0IiBoZWlnaHQ9IjQwIiBmaWxsPSIjRjVGNUY1Ii8+CjxyZWN0IHdpZHRoPSI2NCIgaGVpZ2h0PSI0MCIgZmlsbD0idXJsKCNncmFkaWVudCkiLz4KPGRlZnM+CjxsaW5lYXJHcmFkaWVudCBpZD0iZ3JhZGllbnQiIHgxPSIwJSIgeTE9IjAlIiB4Mj0iMTAwJSIgeTI9IjAlIj4KPHN0b3Agb2Zmc2V0PSIwJSIgc3R5bGU9InN0b3AtY29sb3I6I0ZGRkZGRjtzdG9wLW9wYWNpdHk6MSIgLz4KPHN0b3Agb2Zmc2V0PSI1MCUiIHN0eWxlPSJzdG9wLWNvbG9yOiNFNUU1RTU7c3RvcC1vcGFjaXR5OjEiIC8+CjxzdG9wIG9mZnNldD0iMTAwJSIgc3R5bGU9InN0b3AtY29sb3I6I0ZGRkZGRjtzdG9wLW9wYWNpdHk6MSIgLz4KPC9saW5lYXJHcmFkaWVudD4KPC9kZWZzPgo8L3N2Zz4K' },
                { name: 'Carbon Fiber', image: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA2NCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjY0IiBoZWlnaHQ9IjQwIiBmaWxsPSIjMzMzMzMzIi8+CjxyZWN0IHg9IjAiIHk9IjAiIHdpZHRoPSI2NCIgaGVpZ2h0PSI0IiBmaWxsPSIjNDQ0NDQ0Ii8+CjxyZWN0IHg9IjAiIHk9IjgiIHdpZHRoPSI2NCIgaGVpZ2h0PSI0IiBmaWxsPSIjNDQ0NDQ0Ii8+CjxyZWN0IHg9IjAiIHk9IjE2IiB3aWR0aD0iNjQiIGhlaWdodD0iNCIgZmlsbD0iIzQ0NDQ0NCIvPgo8cmVjdCB4PSIwIiB5PSIyNCIgd2lkdGg9IjY0IiBoZWlnaHQ9IjQiIGZpbGw9IiM0NDQ0NDQiLz4KPHJlY3QgeD0iMCIgeT0iMzIiIHdpZHRoPSI2NCIgaGVpZ2h0PSI0IiBmaWxsPSIjNDQ0NDQ0Ii8+Cjwvc3ZnPgo=' },
                { name: 'Smooth', image: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA2NCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjY0IiBoZWlnaHQ9IjQwIiBmaWxsPSIjRkZGRkZGIi8+Cjwvc3ZnPgo=' },
                { name: 'Wood Grain', image: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA2NCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjY0IiBoZWlnaHQ9IjQwIiBmaWxsPSIjOEE2NDM0Ii8+CjxwYXRoIGQ9Ik0wIDIwQzEwIDIwIDIwIDEwIDMwIDIwQzQwIDIwIDUwIDEwIDY0IDIwVjQwSDBWMjBaIiBmaWxsPSIjNzU0QzI4Ii8+Cjwvc3ZnPgo=' },
                { name: 'Leather', image: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA2NCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjY0IiBoZWlnaHQ9IjQwIiBmaWxsPSIjOEE2NDM0Ii8+CjxjaXJjbGUgY3g9IjE2IiBjeT0iMTIiIHI9IjIiIGZpbGw9IiM3NTRDMjgiLz4KPGNpcmNsZSBjeD0iMzIiIGN5PSIxMiIgcj0iMiIgZmlsbD0iIzc1NEMyOCIvPgo8Y2lyY2xlIGN4PSI0OCIgY3k9IjEyIiByPSIyIiBmaWxsPSIjNzU0QzI4Ii8+CjxjaXJjbGUgY3g9IjE2IiBjeT0iMjgiIHI9IjIiIGZpbGw9IiM3NTRDMjgiLz4KPGNpcmNsZSBjeD0iMzIiIGN5PSIyOCIgcj0iMiIgZmlsbD0iIzc1NEMyOCIvPgo8Y2lyY2xlIGN4PSI0OCIgY3k9IjI4IiByPSIyIiBmaWxsPSIjNzU0QzI4Ii8+Cjwvc3ZnPgo=' }
            ];

            FSC.materialsData = materials;
            FSC.currentMaterialPage = 0;
            FSC.materialsPerPage = 4;
            FSC.totalMaterialPages = Math.ceil(materials.length / FSC.materialsPerPage);

            FSC.renderMaterialPage();
        },

        renderMaterialPage: function() {
            const $grid = $('#fsc-material-grid');

            $grid.empty();

            // Show all materials without pagination
            for (let i = 0; i < FSC.materialsData.length; i++) {
                const material = FSC.materialsData[i];
                const $option = $(`
                    <div class="fsc-material-outline-option" data-material="${material.name}">
                        <div class="fsc-material-thumb" style="background-image: url('${material.image}')"></div>
                        <div class="fsc-material-name">${material.name}</div>
                    </div>
                `);
                $grid.append($option);
            }
        },
        
                 loadProduct: function(productId) {
             // Load product data via AJAX
            $.ajax({
                url: ajaxObj?.ajax_url || '',
                 type: 'POST',
                 data: {
                     action: 'load_product',
                    nonce: (ajaxObj?.fsc_nonce || ajaxObj?.nonce || ''),
                     product_id: productId
                 },
                 beforeSend: function() {
                     $('.fsc-container').addClass('fsc-loading');
                 },
                 success: function(response) {
                    if (response && response.success) {
                        const data = response.data || {};
                        FSC.updateProductInfo(data);
                        FSC.showMessage('Product loaded successfully!', 'success');
                    } else {
                        const message = (response && response.data && response.data.message) ? response.data.message : 'Unknown error';
                        FSC.showMessage('Error loading product: ' + message, 'error');
                    }
                 },
                 error: function() {
                    FSC.showMessage('Network error occurred', 'error');
                 },
                 complete: function() {
                     $('.fsc-container').removeClass('fsc-loading');
                 }
             });
         },
         
        updateProductInfo: function(productData) {
             $('.fsc-product-name').text(productData.title);
             
             // Update price with sale_price if available
             if (productData.sale_price) {
                 FSC.salePrice = parseFloat(productData.sale_price);
             }
             if (productData.price) {
                 FSC.baseProductPrice = parseFloat(productData.price);
             }
             
             // Update price display
             const displayPrice = (FSC.salePrice && FSC.salePrice > 0) ? FSC.salePrice : (FSC.baseProductPrice || productData.price || '0.00');
             $('.fsc-product-price').text('$' + displayPrice + (productData.material ? ' - ' + productData.material : ''));
             
             // Update data attributes
             const $priceElement = $('.fsc-product-price');
             if ($priceElement.length > 0) {
                 $priceElement.attr('data-product-price', FSC.baseProductPrice || productData.price || '0.00');
                 if (FSC.salePrice) {
                     $priceElement.attr('data-product-sale-price', FSC.salePrice);
                 }
             }
             
             // Update price calculation
             if (FSC.updateProductPrice) {
                 FSC.updateProductPrice();
             }
             
             if (productData.colors) {
                 FSC.updateColorOptions(productData.colors);
             }
             
            if (productData.templateData) {
                FSC.renderTemplate(productData.templateData, productData);
                FSC.renderControls(productData.templateData);
                return;
            }
            
             if (productData.features && productData.features.length > 0) {
                 FSC.updateProductFeatures(productData.features);
             }
             
             if (productData.logo_content) {
                 var logoHtml = '<div class="fsc-logo-container">' +
                     productData.logo_content +
                     '</div>';
                $('.fsc-preview-content').html(logoHtml);
                 
                 setTimeout(function() {
                     FSC.updatePreview();
                 }, 100);
             } else {
                 var noProductHtml = '<div class="fsc-no-product-selected">' +
                     '<div class="fsc-no-product-message">' +
                     '<h3>No Product Selected</h3>' +
                     '<p>Please select a product from the dropdown to start customizing.</p>' +
                     '</div>' +
                     '</div>';
                $('.fsc-preview-content').html(noProductHtml);
             }
         },
         
         updateColorOptions: function(colors) {
			const $colorGrid = $('.fsc-color-grid');
			$colorGrid.empty();
			FSC.colorMap = colors || {};
			
			Object.keys(colors).forEach(function(colorName, index) {
                const isSelected = index === 0;
				const $colorOption = $(`
					<div class="fsc-color-option ${isSelected ? 'selected' : ''}" 
						 data-color="${colorName}" 
						 style="background-color: ${colors[colorName]};">
					</div>
				`);
				$colorGrid.append($colorOption);
			});
			
			$('.fsc-color-option').off('click').on('click', function() {
				$('.fsc-color-option').removeClass('selected');
				$(this).addClass('selected');
				FSC.currentColor = $(this).data('color');
				FSC.updatePreview();
				
				// Force immediate logo color update
				setTimeout(function() {
					try {
						const fillColor = FSC.getColorValue ? FSC.getColorValue(FSC.currentColor) : '#000';
						FSC.setLogoFillColor(fillColor);
					} catch(e) {
						console.error('Error applying logo color:', e);
					}
				}, 100);
			});
			
			const firstColor = Object.keys(colors)[0];
			if (firstColor) {
				FSC.currentColor = firstColor;
				FSC.updatePreview();
			}
		},
         
         updateProductFeatures: function(features) {
             const $featuresList = $('.fsc-form-group:has(h4:contains("Product Features")) ul');
             $featuresList.empty();
             
             features.forEach(function(feature) {
                 const $featureItem = $(`<li style="padding: 5px 0; color: #666;">✓ ${feature}</li>`);
                 $featuresList.append($featureItem);
             });
        },

        renderTemplate: function(templateData, productData) {
            const $root = $('.fsc-preview-content');
            if ($root.length === 0) return;

            // Cache template data for resize events
            FSC._cachedTemplateData = templateData;
            FSC._cachedProductData = productData;
            
            // Log template data for debugging fillLogoWithColor
            console.log('🎨 Template data cached:', {
                hasFillLogoWithColor: 'fillLogoWithColor' in templateData,
                fillLogoWithColor: templateData.fillLogoWithColor,
                fillLogoWithColorType: typeof templateData.fillLogoWithColor,
                templateDataKeys: Object.keys(templateData).slice(0, 10) // First 10 keys
            });

            const canvas = templateData.canvas || {};
            const originalWidth = Number(canvas.width) || 800;
            const originalHeight = Number(canvas.height) || 600;
            const elements = Array.isArray(templateData.elements) ? templateData.elements : (templateData.fields || []);

            // Calculate available space dynamically
            const $previewArea = $('.fsc-preview-area');
            
            // Get actual container dimensions
            let containerWidth = $previewArea.width();
            if (!containerWidth || containerWidth < 100) {
                // Fallback if container not yet rendered
                const windowWidth = $(window).width();
                if (windowWidth <= 768) {
                    // Mobile: use nearly full width
                    containerWidth = windowWidth - 30; // Account for minimal padding
                } else if (windowWidth <= 992) {
                    // Tablet: use full width minus padding
                    containerWidth = windowWidth - 60;
                } else {
                    containerWidth = windowWidth * 0.15;
                }
            }
            
            // Don't pre-calculate container height - let it size based on content
            // Just use aspect ratio for scaling calculations
            const aspectRatio = originalHeight / originalWidth;
            
            // Use container dimensions directly without artificial padding
            const availableWidth = containerWidth - 40;
            // For height, use a reasonable maximum based on viewport
            const maxPreviewHeight = Math.min($(window).height() * 0.7, 800);
            const availableHeight = maxPreviewHeight - 40;

            // Calculate scale factor to fit while maintaining aspect ratio
            let scaleX = availableWidth / originalWidth;
            let scaleY = availableHeight / originalHeight;
            
            // IMPORTANT: Set a minimum scale factor to prevent tiny rendering
            // If original dimensions are huge (e.g., 8000px), we want reasonable minimum
            const minScale = 0.3; // Never go below 30% size
            const maxScale = 1; // Allow slight upscaling for small originals
            
            let scaleFactor = Math.min(scaleX, scaleY);
            scaleFactor = Math.max(minScale, Math.min(maxScale, scaleFactor));

            // Calculate final dimensions
            const cw = Math.round(originalWidth * scaleFactor);
            const ch = Math.round(originalHeight * scaleFactor);

            // Debug logging
            console.log('🎨 Render Template:', {
                originalSize: `${originalWidth}x${originalHeight}`,
                containerWidth: containerWidth,
                availableSize: `${availableWidth}x${availableHeight}`,
                scaleFactorRaw: `${(Math.min(scaleX, scaleY)).toFixed(3)}`,
                scaleFactorClamped: scaleFactor.toFixed(3),
                finalSize: `${cw}x${ch}`,
                minScale: minScale,
                maxScale: maxScale
            });

            // Store scale factor for element positioning
            FSC._currentScaleFactor = scaleFactor;

            // Create SVG namespace
            const svgNS = 'http://www.w3.org/2000/svg';
            
            // Create wrapper div for positioning
            const $wrapper = $('<div class="apd-template-preview" />')
                .css({ position: 'relative', width: cw + 'px', height: ch + 'px', margin: '0 auto' })
                .attr('data-original-width', originalWidth)
                .attr('data-original-height', originalHeight)
                .attr('data-scale-factor', scaleFactor);

            // Create SVG element instead of div
            const svgElement = document.createElementNS(svgNS, 'svg');
            svgElement.setAttribute('class', 'apd-template-canvas-full');
            svgElement.setAttribute('width', cw);
            svgElement.setAttribute('height', ch);
            svgElement.setAttribute('viewBox', `0 0 ${cw} ${ch}`);
            svgElement.setAttribute('xmlns', svgNS);
            svgElement.style.display = 'block';
            svgElement.style.overflow = 'visible';
            
            const $canvas = $(svgElement);

            // Create defs element for patterns and gradients
            const defsElement = document.createElementNS(svgNS, 'defs');
            svgElement.appendChild(defsElement);

            $wrapper.append($canvas);
            $root.empty().append($wrapper);

            // Handle background using SVG elements
            if (canvas.background) {
                const bg = canvas.background;
                console.log('🎨 Background data:', bg);
                const type = bg.type || (bg.url || bg.src || bg.image ? 'image' : (bg.color || bg.value ? 'color' : (bg.gradient ? 'gradient' : null)));
                console.log('🎨 Background type detected:', type);
                
                if (type === 'color') {
                    const bgRect = document.createElementNS(svgNS, 'rect');
                    bgRect.setAttribute('width', cw);
                    bgRect.setAttribute('height', ch);
                    bgRect.setAttribute('fill', String(bg.color || bg.value || '#ffffff'));
                    svgElement.appendChild(bgRect);
                } else if (type === 'gradient') {
                    const g = bg.gradient || bg;
                    const gradientId = 'bg-gradient-' + Date.now();
                    const gradientEl = document.createElementNS(svgNS, 'linearGradient');
                    gradientEl.setAttribute('id', gradientId);
                    
                    // Parse direction (default to vertical)
                    const direction = g.direction || 'to bottom';
                    if (direction.includes('bottom')) {
                        gradientEl.setAttribute('x1', '0%');
                        gradientEl.setAttribute('y1', '0%');
                        gradientEl.setAttribute('x2', '0%');
                        gradientEl.setAttribute('y2', '100%');
                    } else if (direction.includes('right')) {
                        gradientEl.setAttribute('x1', '0%');
                        gradientEl.setAttribute('y1', '0%');
                        gradientEl.setAttribute('x2', '100%');
                        gradientEl.setAttribute('y2', '0%');
                    }
                    
                    const stop1 = document.createElementNS(svgNS, 'stop');
                    stop1.setAttribute('offset', '0%');
                    stop1.setAttribute('stop-color', g.color1 || g.from || '#ffffff');
                    
                    const stop2 = document.createElementNS(svgNS, 'stop');
                    stop2.setAttribute('offset', '100%');
                    stop2.setAttribute('stop-color', g.color2 || g.to || '#ffffff');
                    
                    gradientEl.appendChild(stop1);
                    gradientEl.appendChild(stop2);
                    defsElement.appendChild(gradientEl);
                    
                    const bgRect = document.createElementNS(svgNS, 'rect');
                    bgRect.setAttribute('width', cw);
                    bgRect.setAttribute('height', ch);
                    bgRect.setAttribute('fill', `url(#${gradientId})`);
                    svgElement.appendChild(bgRect);
                } else if (type === 'image' && (bg.url || bg.src || bg.image)) {
                    const patternId = 'bg-image-' + Date.now();
                    const patternEl = document.createElementNS(svgNS, 'pattern');
                    patternEl.setAttribute('id', patternId);
                    patternEl.setAttribute('patternUnits', 'userSpaceOnUse');
                    patternEl.setAttribute('width', cw);
                    patternEl.setAttribute('height', ch);
                    
                    const imgEl = document.createElementNS(svgNS, 'image');
                    imgEl.setAttribute('href', bg.url || bg.src || bg.image);
                    imgEl.setAttribute('width', cw);
                    imgEl.setAttribute('height', ch);
                    imgEl.setAttribute('preserveAspectRatio', bg.size === 'cover' ? 'xMidYMid slice' : 'xMidYMid meet');
                    
                    patternEl.appendChild(imgEl);
                    defsElement.appendChild(patternEl);
                    
                    const bgRect = document.createElementNS(svgNS, 'rect');
                    bgRect.setAttribute('width', cw);
                    bgRect.setAttribute('height', ch);
                    bgRect.setAttribute('fill', `url(#${patternId})`);
                    svgElement.appendChild(bgRect);
                }
            }

            (elements || []).forEach(function(el) {
                const x = Number(el.x) || 0;
                const y = Number(el.y) || 0;
                const w = Number(el.width) || 0;
                const h = Number(el.height) || 0;
                const minW = 1000;
                const minH = 1000;

                // Scale all dimensions
                const scaledX = Math.round(x * scaleFactor);
                const scaledY = Math.round(y * scaleFactor);
                const scaledW = Math.round(Math.max(w, minW) * scaleFactor);
                const scaledH = Math.round(Math.max(h, minH) * scaleFactor);

                // Create SVG group element for positioning
                const groupEl = document.createElementNS(svgNS, 'g');
                groupEl.setAttribute('class', 'apd-el');
                groupEl.setAttribute('transform', `translate(${scaledX}, ${scaledY})`);
                
                const $el = $(groupEl);

                if (el.type === 'logo' && productData.logo_content) {
                    console.log("logo", { x,y,w,h });
                    const svgHtml = productData.logo_content;
                    
                    // Parse the SVG content to extract the actual SVG element
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = svgHtml;
                    const sourceSvg = tempDiv.querySelector('svg');
                    
                    // Clone the source SVG content into both layers
                    if (sourceSvg) {
                        // Get the viewBox for proper sizing
                        const originalViewBox = sourceSvg.getAttribute('viewBox') || '0 0 100 100';
                        
                        // Create outline layer SVG
                        const outlineSvg = document.createElementNS(svgNS, 'svg');
                        outlineSvg.setAttribute('class', 'logo-layer logo-outline');
                        outlineSvg.setAttribute('width', w);
                        outlineSvg.setAttribute('height', h);
                        outlineSvg.setAttribute('viewBox', originalViewBox);
                        outlineSvg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
                        outlineSvg.setAttribute('style', 'overflow: visible');
                        
                        // Create fill layer SVG
                        const fillSvg = document.createElementNS(svgNS, 'svg');
                        fillSvg.setAttribute('class', 'logo-layer logo-fill');
                        fillSvg.setAttribute('width', w);
                        fillSvg.setAttribute('height', h);
                        fillSvg.setAttribute('viewBox', originalViewBox);
                        fillSvg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
                        
                        // Clone all child elements (paths, groups, etc.) from source
                        Array.from(sourceSvg.children).forEach(child => {
                            // Clone for outline layer
                            const outlineClone = child.cloneNode(true);
                            outlineSvg.appendChild(outlineClone);
                            
                            // Clone for fill layer and mark elements with initial fills
                            const fillClone = child.cloneNode(true);
                            
                            // Mark elements that have initial fills
                            const markInitialFills = (element) => {
                                if (element.nodeType === 1) { // Element node
                                    const tagName = element.tagName.toLowerCase();
                                    if (['path', 'polygon', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'text'].includes(tagName)) {
                                        const fillAttr = element.getAttribute('fill');
                                        const fillStyle = element.style.fill;
                                        // Check if element has a fill (not 'none' and not empty)
                                        if ((fillAttr && fillAttr !== 'none' && fillAttr !== '') || 
                                            (fillStyle && fillStyle !== 'none' && fillStyle !== '')) {
                                            element.classList.add('has-initial-fill');
                                        }
                                    }
                                    // Recursively process children
                                    Array.from(element.children).forEach(markInitialFills);
                                }
                            };
                            markInitialFills(fillClone);
                            
                            fillSvg.appendChild(fillClone);
                        });
                        
                        // Store references for later use
                        const $outlineLayer = $(outlineSvg);
                        const $fillLayer = $(fillSvg);

                    async function ensurePattern(svgEl, imageUrl){
                        if (!svgEl || !imageUrl) return null;

                        // Function to convert URL to dataURL
                        const toDataURL = async (url) => {
                            if (url.startsWith('data:')) {
                                return url;
                            }
                            try {
                                const response = await fetch(url);
                                const blob = await response.blob();
                                return new Promise((resolve, reject) => {
                                    const reader = new FileReader();
                                    reader.onloadend = () => resolve(reader.result);
                                    reader.onerror = reject;
                                    reader.readAsDataURL(blob);
                                });
                            } catch (error) {
                                console.error('Error converting to dataURL:', error);
                                return url; // Fallback to original URL
                            }
                        };

                        const dataUrl = await toDataURL(imageUrl);

                        const defs = svgEl.querySelector('defs') || svgEl.insertBefore(document.createElementNS('http://www.w3.org/2000/svg','defs'), svgEl.firstChild);
                        const pid = 'logoGoldPattern';
                        let pat = defs.querySelector('#'+pid);
                        if (!pat){
                            pat = document.createElementNS('http://www.w3.org/2000/svg','pattern');
                            pat.setAttribute('id', pid);
                            pat.setAttribute('patternUnits','userSpaceOnUse');
                            pat.setAttribute('width','4000');
                            pat.setAttribute('height','4000');
                            defs.appendChild(pat);
                        } else { while (pat.firstChild) pat.removeChild(pat.firstChild); }
                        const img = document.createElementNS('http://www.w3.org/2000/svg','image');
                        img.setAttribute('href', dataUrl);
                        img.setAttribute('width','4000');
                        img.setAttribute('height','4000');
                        pat.appendChild(img);
                        return pid;
                    }

                    async function applyLogoPattern(){
                        const svgEl = outlineSvg;
                        if (!svgEl) return;
                        const selectedDom = FSC.resolveSelectedMaterialUrl ? FSC.resolveSelectedMaterialUrl() : null;
                        const matUrl = selectedDom || (FSC.getMaterialUrl ? FSC.getMaterialUrl(FSC.currentMaterial) : null);
                        const pid = matUrl ? await ensurePattern(svgEl, matUrl) : null;
                        
                        // Get stroke width from template data
                        let originalWidth = Math.max(2, Number(FSC.fixedLogoOutlineWidth || 24));
                        const cachedTemplateData = FSC._cachedTemplateData;
                        if (cachedTemplateData && cachedTemplateData.elements) {
                            const logoElement = cachedTemplateData.elements.find(el => el.type === 'logo');
                            if (logoElement && logoElement.properties && logoElement.properties.logoStrokeWidth !== undefined) {
                                originalWidth = logoElement.properties.logoStrokeWidth;
                            }
                        }
                        const width = originalWidth; // Don't scale stroke width to preserve original value
                        
                        // Apply to outline layer - set fill to none, stroke with pattern
                        $outlineLayer.find('path, polygon, rect, circle, ellipse, line, polyline').each(function(){
                            this.setAttribute('fill', 'none');
                            if (width > 0 && pid){
                                this.style.stroke = 'url(#'+pid+')';
                                this.style.strokeWidth = String(width);
                            } else {
                                this.style.stroke = 'none';
                                this.style.strokeWidth = '0';
                            }
                            this.style.vectorEffect = 'non-scaling-stroke';
                            this.style.strokeLinejoin = 'round';
                            this.style.strokeLinecap = 'round';
                        });
                    }

                    // Apply pattern for outline layer - only stroke, no fill
                    setTimeout(applyLogoPattern, 0);
                    $(document).off('input.apdLogoW').on('input.apdLogoW', '#fsc-logo-outline-width', applyLogoPattern);
                    $(document).off('click.apdMatSel').on('click.apdMatSel', '.fsc-material-outline-option', function(){
                        $('.fsc-material-outline-option').removeClass('selected');
                        $(this).addClass('selected');
                        applyLogoPattern();
                    });

                    // Apply fill layer - color fill only, no stroke
                    $fillLayer.find('path, polygon, rect, circle, ellipse, line, polyline').each(function(){
                        this.style.stroke = 'none';
                        this.setAttribute('stroke-width', '0');
                    });
                    
                    // Append layers in correct order: outline first (bottom), then fill (top)
                    groupEl.appendChild(outlineSvg);
                    groupEl.appendChild(fillSvg);

					// Apply color to fill layer immediately - check for element-specific color first
					setTimeout(function(){ 
						try { 
							let logoColor = FSC.getColorValue ? FSC.getColorValue(FSC.currentColor) : '#000';
							// Check for element-specific color from template
							if (el.properties && el.properties.color) {
								logoColor = el.properties.color;
							}
							FSC.setLogoFillColor(logoColor); 
						} catch(e) {} 
					}, 0);
                    
                    } // Close the if(sourceSvg) block

                } else if (el.type === 'text') {
                    const prefix = (el.properties && el.properties.prefix) ? String(el.properties.prefix) : '';
                    const suffix = (el.properties && el.properties.suffix) ? String(el.properties.suffix) : '';
                    const baseValue = (el.properties && (el.properties.value || el.properties.text || el.properties.note)) || '';
                    const displayText = prefix + baseValue + suffix;
                    const originalSizePx = (el.properties && el.properties.fontSize) ? (el.properties.fontSize) : 18;
                    const sizePx = originalSizePx; // Don't scale font size to preserve original value
                    const family = (el.properties && el.properties.fontFamily) || 'inherit';
                    const weight = (el.properties && el.properties.fontWeight) || 'bold';
                    
                    // Get stroke width from template data
                    let originalOutline = Number($('#fsc-text-outline-width').val()) || (FSC.fixedTextOutlineWidth || 24);
                    if (el.properties && el.properties.textStrokeWidth !== undefined) {
                        originalOutline = el.properties.textStrokeWidth;
                    }
                    const initialOutline = originalOutline; // Don't scale stroke width to preserve original value
                    
                    // Get element-specific color from template data
                    let elementColor = FSC.getColorValue ? FSC.getColorValue(FSC.currentColor) : '#000';
                    if (el.properties && el.properties.color) {
                        elementColor = el.properties.color;
                    }

                    // Create nested SVG for text (will be embedded in main SVG)
                    const textSvgEl = document.createElementNS(svgNS, 'svg');
                    textSvgEl.setAttribute('class', 'apd-text-svg');
                    textSvgEl.setAttribute('width', String(scaledW));
                    textSvgEl.setAttribute('height', String(scaledH));
                    textSvgEl.setAttribute('x', '0');
                    textSvgEl.setAttribute('y', '0');
                    textSvgEl.setAttribute('overflow', 'visible');
                    
                    const $svg = $(textSvgEl);
                    const textDefsEl = document.createElementNS(svgNS, 'defs');
                    const textEl = document.createElementNS(svgNS, 'text');
                    textEl.textContent = displayText;
                    textEl.setAttribute('x', '0');
                    textEl.setAttribute('y', String(sizePx));
                    textEl.setAttribute('dominant-baseline', 'hanging');
                    textEl.setAttribute('text-anchor', 'start');
                    textEl.setAttribute('fill', elementColor);
                    textEl.setAttribute('stroke-width', String(initialOutline));
                    textEl.setAttribute('stroke-linejoin', 'round');
                    textEl.setAttribute('stroke-linecap', 'round');
                    textEl.setAttribute('paint-order', 'stroke fill');
                    textEl.setAttribute('vector-effect', 'non-scaling-stroke');
                    textEl.setAttribute('font-size', String(sizePx));
                    textEl.setAttribute('font-family', family);
                    textEl.setAttribute('font-weight', weight);
                    textSvgEl.appendChild(textDefsEl);
                    textSvgEl.appendChild(textEl);
                    
                    // Set data attributes on group element
                    if (el.id) { 
                        groupEl.setAttribute('data-el-id', el.id);
                    }
                    groupEl.setAttribute('data-prefix', prefix);
                    groupEl.setAttribute('data-suffix', suffix);
                    groupEl.setAttribute('class', 'apd-el apd-text-el');
                    
                    // Append text SVG directly to group
                    groupEl.appendChild(textSvgEl);

                    function ensureTextPattern(svgEl, imageUrl) {
                        if (!svgEl || !imageUrl) return null;
                        const defs = svgEl.querySelector('defs') || svgEl.insertBefore(document.createElementNS('http://www.w3.org/2000/svg', 'defs'), svgEl.firstChild);
                        const pid = 'apdTextPattern';
                        let pat = defs.querySelector('#' + pid);
                        if (!pat) {
                            pat = document.createElementNS('http://www.w3.org/2000/svg', 'pattern');
                            pat.setAttribute('id', pid);
                            pat.setAttribute('patternUnits', 'userSpaceOnUse');
                            pat.setAttribute('width', '80');
                            pat.setAttribute('height', '80');
                            defs.appendChild(pat);
                        }

                        let img = pat.querySelector('image');
                        if (!img || img.getAttribute('href') !== imageUrl) {
                            if (img) pat.removeChild(img);
                            img = document.createElementNS('http://www.w3.org/2000/svg', 'image');

                            if (imageUrl && !imageUrl.startsWith('data:')) {
                                const tempImg = new Image();
                                tempImg.crossOrigin = 'anonymous';
                                const canvas = document.createElement('canvas');
                                const ctx = canvas.getContext('2d');
                                try {
                                    img.setAttribute('href', imageUrl);
                                    tempImg.onload = function() {
                                        canvas.width = tempImg.width;
                                        canvas.height = tempImg.height;
                                        ctx.drawImage(tempImg, 0, 0);
                                        const base64Data = canvas.toDataURL('image/png');
                                        img.setAttribute('href', base64Data);
                                    };
                                    tempImg.onerror = function() {
                                        img.setAttribute('href', imageUrl);
                                    };
                                    tempImg.src = imageUrl;
                                } catch (e) {
                                    img.setAttribute('href', imageUrl);
                                }
                            } else {
                                img.setAttribute('href', imageUrl);
                            }

                            img.setAttribute('width', '80');
                            img.setAttribute('height', '80');
                            img.setAttribute('preserveAspectRatio', 'xMidYMid slice');
                            pat.appendChild(img);
                        }
                        return pid;
                    }

                    function applyTextPattern() {
                        const svgEl = textSvgEl;
                        const matUrl = (function() {
                            const sel = FSC.resolveSelectedMaterialUrl ? FSC.resolveSelectedMaterialUrl() : null;
                            if (sel) return sel;
                            return FSC.getMaterialUrl ? FSC.getMaterialUrl(FSC.currentMaterial) : null;
                        })();
                        const pid = matUrl ? ensureTextPattern(svgEl, matUrl) : null;
                        
                        // Get stroke width from template data
                        let originalWidth = Math.max(2, Number(FSC.fixedTextOutlineWidth || 24));
                        if (el.properties && el.properties.textStrokeWidth !== undefined) {
                            originalWidth = el.properties.textStrokeWidth;
                        }
                        const width = originalWidth; // Don't scale stroke width to preserve original value
                        const t = textEl;
                        if (!t) return;
                        if (width > 0 && pid) {
                            t.setAttribute('stroke', 'url(#' + pid + ')');
                            t.setAttribute('stroke-width', String(width));
                        } else {
                            t.setAttribute('stroke', 'none');
                            t.setAttribute('stroke-width', '0');
                        }
                    }
                    setTimeout(applyTextPattern, 0);
                }
                // Append group to main SVG canvas
                svgElement.appendChild(groupEl);
            });
        },

        renderControls: function(templateData) {
            // Check for color options in multiple possible properties
            let colorsMap = {};
            
            // Check colorPalette array
            if (Array.isArray(templateData.colorPalette) && templateData.colorPalette.length) {
                templateData.colorPalette.forEach(function(c){
                    const name = (c.name || c.color || '').toString();
                    const val = c.color || c.value || '#000000';
                    if (name) colorsMap[name] = val;
                });
            }
            
            // Check colors object
            if (templateData.colors && typeof templateData.colors === 'object') {
                colorsMap = { ...colorsMap, ...templateData.colors };
            }
            
            // Check colorOptions object
            if (templateData.colorOptions && typeof templateData.colorOptions === 'object') {
                colorsMap = { ...colorsMap, ...templateData.colorOptions };
            }
            
            // Update color options if we have any colors
            if (Object.keys(colorsMap).length) {
                FSC.updateColorOptions(colorsMap);
                
                // Set default color from template if specified
                if (templateData.defaultColor && colorsMap[templateData.defaultColor]) {
                    FSC.currentColor = templateData.defaultColor;
                    // Update UI to show selected color
                    $('.fsc-color-option').removeClass('selected');
                    $('.fsc-color-option[data-color="' + templateData.defaultColor + '"]').addClass('selected');
                }
            } else {
                // Fallback to default colors if no template colors found
                const defaultColors = {
                    'Black': '#000000',
                    'White': '#FFFFFF',
                    'Red': '#FF0000',
                    'Blue': '#0000FF',
                    'Green': '#00FF00',
                    'Yellow': '#FFFF00'
                };
                FSC.updateColorOptions(defaultColors);
            }

            try { FSC.ensureMaterialGroup(); } catch(e) { }

            $(document).off('click.apdMatSelText').on('click.apdMatSelText', '.fsc-material-outline-option', function(){
                if (FSC.scheduleApplyTextOutlineAll) {
                FSC.scheduleApplyTextOutlineAll();
                }
            });

            const elements = Array.isArray(templateData.elements) ? templateData.elements : (templateData.fields || []);
            const textEls = (elements || []).filter(function(el){ return el && el.type === 'text'; });
            const $group = $('.fsc-form-group:has(h4:contains("Custom Text"))');
            if ($group.length) {
                const $container = $('<div class="fsc-inputs-dynamic" />');
                textEls.forEach(function(el, idx){
                    const label = el.label || ('Text ' + (idx+1));
                    const initialValue = (el.properties && (el.properties.value || el.properties.text)) || '';
                    const hintText = (el.properties && (el.properties.note || el.properties.hint || el.properties.placeholder)) || '';
                    const id = 'fsc-text-' + (el.id || idx);
                    const $row = $('<div class="fsc-input-group" />');
                    var labelHtml = '<label for="' + id + '">' + label + (hintText ? ' <sup class="fsc-hint-sup" style="font-size:11px;color:#888;vertical-align:super;font-weight:500;">' + hintText + '</sup>' : '') + '</label>';
                    $row.append(labelHtml);
                    const $input = $('<input type="text" class="fsc-form-input" />').attr('id', id).val(initialValue).attr('placeholder','');
                    // Apply maxLength from template data if provided
                    try {
                        const maxLen = (el && el.properties && el.properties.maxLength) ? Number(el.properties.maxLength) : (el.maxLength ? Number(el.maxLength) : null);
                        if (maxLen && !isNaN(maxLen)) {
                            $input.attr('maxlength', maxLen);
                            $input.prop('maxLength', maxLen);
                            $input.attr('data-template-max', String(maxLen));
                        }
                    } catch(e) { /* noop */ }
                    $input.on('input', function(){
                        const v = $(this).val();
                        let $target = el.id ? $('.apd-text-el[data-el-id="' + el.id + '"]') : $('.apd-template-canvas-full .apd-text-el').eq(idx);
                        if ($target.length) {
                            const prefix = $target.attr('data-prefix') || '';
                            const suffix = $target.attr('data-suffix') || '';
                        const $svgText = $target.find('svg text').first();
                            if ($svgText.length) {
                                $svgText.text(prefix + v + suffix);
                            if (!$svgText.attr('fill') || $svgText.attr('fill') === 'transparent') {
                                const fillColor = FSC.getColorValue ? FSC.getColorValue(FSC.currentColor) : '#000';
                                $svgText.attr('fill', fillColor);
                            }
                                const $container = $target.closest('.apd-el');
                                if ($container.length) {
                                    const cw = parseFloat($('.apd-template-canvas-full').css('width')) || 800;
                                    const ch = parseFloat($('.apd-template-canvas-full').css('height')) || 600;
                                    let left = parseFloat($container.css('left')) || 0;
                                    let top = parseFloat($container.css('top')) || 0;
                                    if (left < 0 || left > cw - 40 || top < 0 || top > ch - 30) {
                                        left = 50;
                                        top = 80 + (idx * 50);
                                        $container.css({ left: left + 'px', top: top + 'px' });
                                    }
                                    $container.show();
                                }
                            }
                        }
                    });
                    $row.append($input);
                    $container.append($row);
                });
                $group.find('.fsc-input-group').remove();
                $group.append($container);
                // After rendering inputs, enforce template maxlengths and start observer
                try { FSC.enforceTemplateMax && FSC.enforceTemplateMax(); } catch(e) {}
                try { FSC.ensureMaxlengthObserver && FSC.ensureMaxlengthObserver(); } catch(e) {}
            }

            $('#fsc-logo-outline-width').closest('.fsc-input-group, .fsc-form-group').remove();
            $('#fsc-text-outline-width').closest('.fsc-input-group, .fsc-form-group').remove();
			// Ensure no duplicated dynamic inputs remain outside intended group
			try { if (FSC.cleanupDynamicInputs) { FSC.cleanupDynamicInputs(); } } catch(e) {}
        },

        // Remove any duplicated/stray dynamic inputs rendered below the checkout button
        cleanupDynamicInputs: function(){
            try {
                var $group = $('.fsc-form-group:has(h4:contains("Custom Text"))');
                var $valid = $group.find('.fsc-inputs-dynamic');

                // Remove any .fsc-inputs-dynamic not inside the Custom Text group
                $('.fsc-inputs-dynamic').each(function(){
                    if (!$valid.length || ($group.length && !$.contains($group[0], this))) {
                        $(this).remove();
                    }
                });

                // Remove any .fsc-inputs-dynamic that appear after the checkout button
                var $checkout = $('.fsc-btn-checkout').first();
                if ($checkout.length) {
                    $checkout.nextAll('.fsc-inputs-dynamic').remove();
                }
            } catch(e) {}
        },

        scheduleApplyTextOutlineAll: function(){
            if (FSC._outlineApplyScheduled) return;
            FSC._outlineApplyScheduled = true;
            (window.requestAnimationFrame || setTimeout)(function(){
                FSC._outlineApplyScheduled = false;
                FSC.applyTextOutlineAll();
            }, 0);
        },

        applyTextOutlineAll: function(){
            try {
                                const NS = 'http://www.w3.org/2000/svg';
                const matUrl = FSC.resolveSelectedMaterialUrl ? FSC.resolveSelectedMaterialUrl() : (FSC.getMaterialUrl ? FSC.getMaterialUrl(FSC.currentMaterial) : null);
                
                // Get stroke width from template data
                let width = Math.max(2, Number(FSC.fixedTextOutlineWidth || 24));
                const cachedTemplateData = FSC._cachedTemplateData;
                if (cachedTemplateData && cachedTemplateData.elements) {
                    const textElement = cachedTemplateData.elements.find(el => el.type === 'text');
                    if (textElement && textElement.properties && textElement.properties.textStrokeWidth !== undefined) {
                        width = textElement.properties.textStrokeWidth;
                    }
                }

                if (FSC._lastTextOutline.url === matUrl && FSC._lastTextOutline.width === width) {
                    return;
                }

                const $textSvgs = $('.apd-text-svg');
                if ($textSvgs.length === 0) return;

                $textSvgs.each(function(){
                    const svgEl = this;
                    const t = $(svgEl).find('text').get(0);
                    if (!t) return;

                    // Get individual stroke width for this text element
                    let individualWidth = width;
                    const fieldId = $(svgEl).data('field-id') || t.getAttribute('data-field-id');
                    if (fieldId && cachedTemplateData && cachedTemplateData.elements) {
                        const fieldElement = cachedTemplateData.elements.find(el => el.id === fieldId);
                        if (fieldElement && fieldElement.properties && fieldElement.properties.textStrokeWidth !== undefined) {
                            individualWidth = fieldElement.properties.textStrokeWidth;
                        }
                    }

                    if (matUrl && individualWidth > 0) {
                        const defs = svgEl.querySelector('defs') || svgEl.insertBefore(document.createElementNS(NS,'defs'), svgEl.firstChild);
                        const pid = 'apdTextPattern';
                        let pat = defs.querySelector('#'+pid);

                        if (!pat){
                            pat = document.createElementNS(NS,'pattern');
                            pat.setAttribute('id', pid);
                            pat.setAttribute('patternUnits','userSpaceOnUse');
                            pat.setAttribute('width','80');
                            pat.setAttribute('height','80');
                            defs.appendChild(pat);
                        }

                        let img = pat.querySelector('image');
                        if (!img || img.getAttribute('href') !== matUrl) {
                            if (img) pat.removeChild(img);
                            img = document.createElementNS(NS,'image');
                            
                            // Convert material URL to base64 to avoid href issues during export
                            if (matUrl && !matUrl.startsWith('data:')) {
                                // Create a temporary image to convert to base64 synchronously
                                const tempImg = new Image();
                                tempImg.crossOrigin = 'anonymous';
                                const canvas = document.createElement('canvas');
                                const ctx = canvas.getContext('2d');
                                
                                // Use a synchronous approach with a data URL
                                try {
                                    // For now, set a placeholder and convert asynchronously
                                    img.setAttribute('href', matUrl); // Temporary
                                    
                                    tempImg.onload = function() {
                                        canvas.width = tempImg.width;
                                        canvas.height = tempImg.height;
                                        ctx.drawImage(tempImg, 0, 0);
                                        const base64Data = canvas.toDataURL('image/png');
                                        img.setAttribute('href', base64Data);
                                    };
                                    
                                    tempImg.onerror = function() {
                                        // Keep original URL if conversion fails
                                        img.setAttribute('href', matUrl);
                                    };
                                    
                                    tempImg.src = matUrl;
                                } catch (e) {
                                    img.setAttribute('href', matUrl);
                                }
                            } else {
                                img.setAttribute('href', matUrl);
                            }
                            
                            img.setAttribute('width','80');
                            img.setAttribute('height','80');
                            img.setAttribute('preserveAspectRatio','xMidYMid slice');
                            pat.appendChild(img);
                        }

                        t.setAttribute('stroke', 'url(#'+pid+')');
                        t.setAttribute('stroke-width', String(individualWidth));
                        t.setAttribute('stroke-linejoin','round');
                        t.setAttribute('stroke-linecap','round');
                        t.setAttribute('paint-order','stroke fill');
                        t.setAttribute('vector-effect','non-scaling-stroke');
                        } else {
                        t.setAttribute('stroke', 'none');
                        t.setAttribute('stroke-width', '0');
                        t.removeAttribute('stroke-linejoin');
                        t.removeAttribute('stroke-linecap');
                        t.removeAttribute('paint-order');
                        t.removeAttribute('vector-effect');
                    }
                });

                FSC._lastTextOutline = { url: matUrl, width: width };
            } catch(e) {}
        },

        init: function() {
            console.log('🚀 FSC Initializing...');
            
            // Initialize default values
            this.currentColor = this.currentColor || 'black';
            this.currentMaterial = this.currentMaterial || 'Solid';
            this.quantity = this.quantity || 1;
            this.productPrice = this.productPrice || 29.99;
            this.productName = this.productName || 'Custom Freight Sign';
            this.productId = this.productId || this.getProductIdFromUrl();
            
            console.log('FSC Initialized with:', {
                currentColor: this.currentColor,
                currentMaterial: this.currentMaterial,
                quantity: this.quantity,
                productPrice: this.productPrice,
                productName: this.productName,
                productId: this.productId
            });
            
            this.loadProductPrice();
            this.bindEvents();
            this.updatePreview();
            this.initCustomizerPreview();
            this.loadCartCount();
            this.initializeDefaultSelections();
            // Update price after initialization to account for default material
            setTimeout(function() {
                if (FSC.updateProductPrice) {
                    FSC.updateProductPrice();
                }
            }, 500);

            if (document.getElementById('checkout-preview')) {
                console.log('Checkout page detected, initializing materials...');
                this.ensureMaterialGroup();
                // Hide and clear checkout preview frame per requirement
                try {
                    FSC.disableCheckoutPreview = true;
                    var p = document.getElementById('checkout-preview');
                    if (p) { p.innerHTML = ''; p.style.display = 'none'; }
                    var i = document.getElementById('preview-info');
                    if (i) { i.innerHTML = ''; i.style.display = 'none'; }
                } catch(e) {}
            }
            setTimeout(function(){ FSC.removeOutlineControls(); }, 0);
            this.autoLoadProduct();
            
            // Add window resize handler to recalculate preview dimensions
            let resizeTimer;
            const handleResize = function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    // Re-render template if it exists
                    if (FSC._cachedTemplateData && FSC._cachedProductData) {
                        console.log('Re-rendering template due to resize...');
                        FSC.renderTemplate(FSC._cachedTemplateData, FSC._cachedProductData);
                    }
                }, 150); // Debounce resize events
            };
            
            $(window).on('resize', handleResize);
            $(window).on('orientationchange', handleResize);
            
            // Trigger initial resize after elements are loaded
            setTimeout(function() {
                if (FSC._cachedTemplateData && FSC._cachedProductData) {
                    FSC.renderTemplate(FSC._cachedTemplateData, FSC._cachedProductData);
                }
            }, 500);
        },

        loadProductPrice: function() {
            console.log('💰 Loading product price and details...');
            
            const $priceElement = $('.fsc-product-price, .product-price, [data-product-price]');
            if ($priceElement.length > 0) {
                // Try data attribute first
                const dataPrice = $priceElement.data('product-price');
                if (dataPrice) {
                    FSC.baseProductPrice = parseFloat(dataPrice);
                    console.log('Base price from data attribute:', FSC.baseProductPrice);
                } else {
                    // Try text content
                    const priceText = $priceElement.text();
                    const priceMatch = priceText.match(/[\d,]+\.?\d*/);
                    if (priceMatch) {
                        FSC.baseProductPrice = parseFloat(priceMatch[0].replace(',', ''));
                        console.log('Base price from text:', FSC.baseProductPrice);
                    }
                }
                // Get sale price from data attribute
                const dataSalePrice = $priceElement.data('product-sale-price');
                if (dataSalePrice) {
                    FSC.salePrice = parseFloat(dataSalePrice);
                    console.log('Sale price from data attribute:', FSC.salePrice);
                }
            }
            
            // Store base price if not already set
            if (!FSC.baseProductPrice) {
                FSC.baseProductPrice = FSC.productPrice || 29.99;
            }
            
            // Get sale price from fscDefaults if not already set
            if (!FSC.salePrice && window.fscDefaults && window.fscDefaults.product_sale_price) {
                const salePriceStr = window.fscDefaults.product_sale_price;
                if (salePriceStr && salePriceStr.trim() !== '') {
                    FSC.salePrice = parseFloat(salePriceStr);
                    console.log('Sale price from fscDefaults:', FSC.salePrice);
                }
            }

            const $nameElement = $('.fsc-product-name, .product-name, [data-product-name]');
            if ($nameElement.length > 0) {
                // Try data attribute first
                const dataName = $nameElement.data('product-name');
                if (dataName) {
                    FSC.productName = dataName;
                    console.log('Name from data attribute:', FSC.productName);
                } else {
                    // Try text content
                    FSC.productName = $nameElement.text().trim();
                    console.log('Name from text:', FSC.productName);
                }
            }

            // Try to get product ID from template
            const $productInfo = $('.fsc-product-info[data-product-id]');
            if ($productInfo.length > 0) {
                const templateProductId = $productInfo.data('product-id');
                if (templateProductId) {
                    FSC.productId = templateProductId;
                    console.log('Product ID from template:', FSC.productId);
                }
            }
            
            // Fallback to other methods
            if (!FSC.productId) {
                FSC.productId = ajaxObj?.product_id || this.getProductIdFromUrl();
                console.log('Product ID from fallback:', FSC.productId);
            }

            if (!FSC.baseProductPrice && window.fscDefaults && window.fscDefaults.product_price) {
                FSC.baseProductPrice = parseFloat(window.fscDefaults.product_price);
                console.log('Base price from fscDefaults:', FSC.baseProductPrice);
            }
            
            // Get sale price from fscDefaults if not already set
            if (!FSC.salePrice && window.fscDefaults && window.fscDefaults.product_sale_price) {
                const salePriceStr = window.fscDefaults.product_sale_price;
                if (salePriceStr && salePriceStr.trim() !== '') {
                    FSC.salePrice = parseFloat(salePriceStr);
                    console.log('Sale price from fscDefaults:', FSC.salePrice);
                }
            }
            
            // Store base price if not already set
            if (!FSC.baseProductPrice) {
                FSC.baseProductPrice = 29.99;
            }
            if (!FSC.productName && window.fscDefaults && window.fscDefaults.product_name) {
                FSC.productName = window.fscDefaults.product_name;
                console.log('Name from fscDefaults:', FSC.productName);
            }

            if (!FSC.productPrice) {
                FSC.baseProductPrice = 29.99;
                FSC.productPrice = FSC.baseProductPrice;
                console.log('Using default base price:', FSC.baseProductPrice);
            }
            if (!FSC.productName) {
                FSC.productName = 'Custom Freight Sign';
                console.log('Using default name:', FSC.productName);
            }
            if (!FSC.productId) {
                FSC.productId = '1';
                console.log('Using default product ID:', FSC.productId);
            }
            
            console.log('Final product details:', {
                productId: FSC.productId,
                productName: FSC.productName,
                productPrice: FSC.productPrice
            });
            
            // Ensure all required values are set
            if (!FSC.productId || FSC.productId === '0') {
                console.warn('⚠️ Product ID is missing or invalid:', FSC.productId);
                // Try to get from hidden form
                const $hiddenProductId = $('#fsc-form-product-id, input[name="product_id"]');
                if ($hiddenProductId.length > 0) {
                    FSC.productId = $hiddenProductId.val();
                    console.log('Product ID from hidden form:', FSC.productId);
                }
                
                // If still no product ID, try to get from URL or use default
                if (!FSC.productId || FSC.productId === '0') {
                    FSC.productId = this.getProductIdFromUrl() || '1';
                    console.log('Product ID from URL or default:', FSC.productId);
                }
            }
            
            // Final validation
            if (!FSC.productId || FSC.productId === '0') {
                FSC.productId = '1';
                console.log('Final fallback product ID:', FSC.productId);
            }
            if (!FSC.productName) {
                console.warn('⚠️ Product name is missing');
            }
            if (!FSC.productPrice) {
                console.warn('⚠️ Product price is missing');
            }
        },

        updateProductPrice: function() {
            // Get base price and sale price if not set
            if (!FSC.baseProductPrice) {
                const $priceElement = $('.fsc-product-price, .product-price, [data-product-price]');
                if ($priceElement.length > 0) {
                    const dataPrice = $priceElement.data('product-price');
                    if (dataPrice) {
                        FSC.baseProductPrice = parseFloat(dataPrice);
                    } else {
                        const priceText = $priceElement.text();
                        const priceMatch = priceText.match(/[\d,]+\.?\d*/);
                        if (priceMatch) {
                            FSC.baseProductPrice = parseFloat(priceMatch[0].replace(',', ''));
                        }
                    }
                    // Get sale price
                    const dataSalePrice = $priceElement.data('product-sale-price');
                    if (dataSalePrice) {
                        FSC.salePrice = parseFloat(dataSalePrice);
                    }
                }
                if (!FSC.baseProductPrice) {
                    FSC.baseProductPrice = window.fscDefaults && window.fscDefaults.product_price 
                        ? parseFloat(window.fscDefaults.product_price) 
                        : 29.99;
                }
                // Get sale price from fscDefaults if not already set
                if (!FSC.salePrice && window.fscDefaults && window.fscDefaults.product_sale_price) {
                    const salePriceStr = window.fscDefaults.product_sale_price;
                    if (salePriceStr && salePriceStr.trim() !== '') {
                        FSC.salePrice = parseFloat(salePriceStr);
                    }
                }
            }
            
            // Get selected material price
            let materialPrice = 0;
            if (FSC.currentMaterial) {
                const $selectedMaterial = $('.fsc-material-outline-option[data-material="' + FSC.currentMaterial + '"]');
                if ($selectedMaterial.length > 0) {
                    materialPrice = parseFloat($selectedMaterial.data('material-price')) || 0;
                } else {
                    // Try to get from materials map
                    if (FSC.materialsMap || (window.fscDefaults && window.fscDefaults.materials)) {
                        const materialsMap = FSC.materialsMap || window.fscDefaults.materials;
                        const materialData = materialsMap[FSC.currentMaterial];
                        if (materialData) {
                            if (typeof materialData === 'object' && materialData.price !== undefined) {
                                materialPrice = parseFloat(materialData.price) || 0;
                            }
                        }
                    }
                }
            }
            
            // Calculate total price: use sale_price if available, otherwise base_price, then add material_price
            let basePriceToUse = FSC.baseProductPrice || 29.99;
            if (FSC.salePrice && FSC.salePrice > 0) {
                basePriceToUse = FSC.salePrice;
            }
            FSC.productPrice = basePriceToUse + (materialPrice || 0);
            FSC.basePriceForCart = basePriceToUse; // Store the price to use (sale or base)
            
            // Update displayed price
            const $priceElement = $('.fsc-product-price, .product-price, [data-product-price]');
            if ($priceElement.length > 0) {
                const $priceDisplay = $priceElement;
                const basePriceText = '$' + basePriceToUse.toFixed(2);
                const materialText = materialPrice > 0 ? ' (+$' + materialPrice.toFixed(2) + ')' : '';
                const totalPriceText = '$' + FSC.productPrice.toFixed(2);
                
                // Update the price display
                $priceDisplay.text(totalPriceText + (FSC.currentMaterial ? ' - ' + FSC.currentMaterial : ''));
                $priceDisplay.attr('data-product-price', FSC.productPrice);
            }
            
            console.log('Price updated:', {
                basePrice: FSC.baseProductPrice,
                salePrice: FSC.salePrice,
                basePriceUsed: basePriceToUse,
                materialPrice: materialPrice,
                totalPrice: FSC.productPrice,
                material: FSC.currentMaterial
            });
        },

        getProductIdFromUrl: function() {
            const urlParams = new URLSearchParams(window.location.search);
            let productId = urlParams.get('product_id') || urlParams.get('id') || '';
            
            // Try to get from URL path (e.g., /customizer/123/)
            if (!productId) {
                const pathMatch = window.location.pathname.match(/\/customizer\/(\d+)/);
                if (pathMatch) {
                    productId = pathMatch[1];
                }
            }
            
            // Try to get from ajaxObj
            if (!productId && ajaxObj && ajaxObj.product_id) {
                productId = ajaxObj.product_id;
            }
            
            // Try to get from fscDefaults
            if (!productId && window.fscDefaults && window.fscDefaults.product_id) {
                productId = window.fscDefaults.product_id;
            }
            
            console.log('Product ID from URL:', productId);
            return productId;
        },

        autoLoadProduct: function() {
            const pid = (function() {
                if (ajaxObj && ajaxObj.product_id) return ajaxObj.product_id;
                const urlParams = new URLSearchParams(window.location.search);
                const q = urlParams.get('customizer');
                if (q) return q;
                const match = window.location.pathname.match(/\/customizer\/(\d+)/);
                if (match && match[1]) return match[1];
                return null;
            })();
            if (pid) {
                this.loadProduct(pid);
            }
        },

        getMaterialUrl: function(materialName) {
            if (!materialName || String(materialName).trim().toLowerCase() === 'solid') {
                return null;
            }

            var $domOption = $('.fsc-material-outline-option[data-material="' + materialName + '"]');
            var domUrl = $domOption.data('material-url');
            if (domUrl) {
                return String(domUrl);
            }
            
            // Try to get from materials map (new structure)
            if (FSC.materialsMap || (window.fscDefaults && window.fscDefaults.materials)) {
                const materialsMap = FSC.materialsMap || window.fscDefaults.materials;
                const materialData = materialsMap[materialName];
                if (materialData) {
                    if (typeof materialData === 'string') {
                        return materialData;
                    } else if (typeof materialData === 'object' && materialData.url) {
                        return materialData.url;
                    }
                }
            }

            // Legacy fallback - already handled above, but keeping for backward compatibility
            if (window.fscDefaults && window.fscDefaults.materials) {
                const materialData = window.fscDefaults.materials[materialName];
                if (materialData) {
                    if (typeof materialData === 'string') {
                        return materialData;
                    } else if (typeof materialData === 'object' && materialData.url) {
                        return materialData.url;
                    }
                }
            }

            const nameKey = String(materialName || '').trim().toLowerCase();
            const nameToFile = {
                'diamond plate': 'Diamond_Plate.png',
                'engine turn gold': 'Engine_turn_gold.png',
                'florentine silver': 'Florentine_Silver.png',
                'gold': 'gold.png',
                'brush gold': 'gold.png'
            };
            const fileName = nameToFile[nameKey] || (materialName ? materialName.replace(/\s+/g, '_') + '.png' : '');
            if (!fileName) {
                return null;
            }

            const pluginUrl = ajaxObj?.plugin_url || '';
            const siteUrl = ajaxObj?.site_url || '';
            let finalBaseUrl;
            if (pluginUrl) {
                finalBaseUrl = pluginUrl.replace(/\/?$/, '/');
            } else if (siteUrl) {
                finalBaseUrl = siteUrl.replace(/\/?$/, '/') + 'wp-content/plugins/Shop/';
                        } else {
                finalBaseUrl = '/wp-content/plugins/Shop/';
            }
            const materialUrl = finalBaseUrl + 'uploads/material/' + encodeURIComponent(fileName);

            return materialUrl;
        },

        removeOutlineControls: function(){
            try {
                var $targets = $('#fsc-logo-outline-width, #fsc-text-outline-width');
                $targets.each(function(){
                    $(this).prop('disabled', true).css({display:'none'});
                    var id = $(this).attr('id');
                    $('label[for="'+id+'"]').css({display:'none'});
                    $(this).siblings('span, .value, .range-value').css({display:'none'});
                });
            } catch(e){}
        },

        bindEvents: function() {
            $('.fsc-color-option').on('click', function() {
                $('.fsc-color-option').removeClass('selected');
                $(this).addClass('selected');
                FSC.currentColor = $(this).data('color');
                FSC.updateTextColors();
                FSC.scheduleUpdatePreview();
                
                // Force immediate logo color update
                setTimeout(function() {
                    try {
                        const fillColor = FSC.getColorValue ? FSC.getColorValue(FSC.currentColor) : '#000';
                        FSC.setLogoFillColor(fillColor);
                    } catch(e) {
                        console.error('Error applying logo color:', e);
                    }
                }, 100);
            });

            $('.fsc-material-outline-option').on('click', function() {
                $('.fsc-material-outline-option').removeClass('selected');
                $(this).addClass('selected');
                FSC.currentMaterial = $(this).data('material');

                const materialUrl = $(this).data('material-url') || $(this).data('url') || $(this).css('background-image').match(/url\(['"]?([^'"]+)['"]?\)/)?.[1];
                FSC.currentMaterialUrl = materialUrl;
                
                // Update price when material is selected
                FSC.updateProductPrice();

                FSC.scheduleUpdatePreview();
            });

            $('#fsc-quantity').on('input', function() {
                FSC.quantity = parseInt($(this).val()) || 1;
            });

            $('.fsc-btn-checkout').on('click', function(e) {
                e.preventDefault();
                FSC.saveCustomization('checkout');
            });
            
            // Add to cart button
            $('.fsc-btn-add-cart').on('click', function(e) {
                console.log('🛒 ADD TO CART button clicked!');
                e.preventDefault();
                FSC.saveCustomization('cart');
            });
            
            // Debug: Check if button exists
            console.log('ADD TO CART buttons found:', $('.fsc-btn-add-cart').length);
            
            // Reset button - reloads the page to reset all customizations
            $('.fsc-btn-reset').on('click', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to reset all customizations? This will reload the page.')) {
                    window.location.reload();
                }
            });
        },

        updateTextColors: function() {
            try {
                const fillColor = FSC.getColorValue(FSC.currentColor);

                $('.fsc-text-svg-container .fsc-text-item').each(function() {
                    try {
                        this.style.color = fillColor;
                    } catch(e) {
                        console.warn('Failed to update HTML text color:', e);
                    }
                });

                $('.apd-text-svg text, .fsc-text-svg text').each(function() {
                    try {
                        this.setAttribute('fill', fillColor);
                    } catch(e) {
                        console.warn('Failed to update SVG text color:', e);
                    }
                });

                $('.apd-template-canvas-full .fsc-text-item, .apd-template-canvas-full .apd-text-item').each(function() {
                    try {
                        this.style.color = fillColor;
                    } catch(e) {
                        console.warn('Failed to update canvas text color:', e);
                    }
                });

                console.log('Text colors updated successfully to:', fillColor);
				// Also update logo fill layer to match current color
				try { if (FSC.setLogoFillColor) { FSC.setLogoFillColor(fillColor); } } catch(e) {}
            } catch(e) {
                console.error('Failed to update text colors:', e);
            }
        },

		// Apply fill color to logo fill layer shapes/text
		setLogoFillColor: function(color) {
			try {
				if (!color) return;
				
				// Check if fillLogoWithColor option is enabled in template data
				// Handle both boolean true and string "true" values
				const templateData = FSC._cachedTemplateData;
				let fillLogoWithColor = false;
				
				if (templateData) {
					const rawValue = templateData.fillLogoWithColor;
					// Handle boolean true, string "true", number 1, or any truthy value
					fillLogoWithColor = rawValue === true || rawValue === 'true' || rawValue === 1 || rawValue === '1' || (typeof rawValue === 'string' && rawValue.toLowerCase() === 'true');
					
					console.log('🎨 Fill logo with color check:', {
						rawValue: rawValue,
						typeof: typeof rawValue,
						result: fillLogoWithColor,
						templateDataKeys: Object.keys(templateData)
					});
				} else {
					console.log('🎨 No cached template data available');
				}
				
				if (!fillLogoWithColor) {
					console.log('🎨 Fill logo with color is disabled in template, skipping logo color update');
					return;
				}
				
				console.log('🎨 Setting logo fill color to:', color);
				
				// Comprehensive approach: Find ALL logo-related elements in the preview area
				// Method 1: Target logo fill layers specifically
				$('.apd-logo-box .logo-layer.logo-fill, .fsc-logo-container .logo-layer.logo-fill, .apd-el .logo-layer.logo-fill').each(function(){
					var $layer = $(this);
					var $svg = $layer.is('svg') ? $layer : $layer.find('svg').first();
					if (!$svg.length && !$layer.is('svg')) return;
					var $targetSvg = $svg.length ? $svg : $layer;
					console.log('🎨 Found logo layer:', $layer);
					applyColorToSvgElements($targetSvg, color, 'logo-layer');
				});
				
				// Method 2: Target ALL SVG elements in .apd-el groups (logo containers in template canvas)
				$('.apd-template-canvas-full .apd-el').each(function(){
					var $group = $(this);
					// Find all SVG elements within this group that are logo-related
					$group.find('svg.logo-layer.logo-fill, svg').each(function(){
						var $svg = $(this);
						// Only process if this is a logo fill layer or contains logo paths
						var hasLogoPaths = $svg.find('path[class*="has-initial-fill"], path[fill], polygon[fill]').length > 0;
						if ($svg.hasClass('logo-fill') || hasLogoPaths) {
							console.log('🎨 Found logo SVG in apd-el group:', $svg);
							applyColorToSvgElements($svg, color, 'apd-el-group');
						}
					});
					// Also check for direct path/polygon elements in the group (not in SVG)
					$group.find('path, polygon').each(function(){
						var $el = $(this);
						// Only apply if this element has fill attribute (logo element)
						if ($el.attr('fill') && $el.attr('fill') !== 'none') {
							console.log('🎨 Found direct logo path/polygon in apd-el:', $el);
							applyColorToElement(this, color, 'direct-path');
						}
					});
				});
				
				// Method 3: Target ALL path, polygon, and text elements in the entire template canvas
				// This is the most aggressive approach to catch any logo elements
				// First, find all elements with has-initial-fill class (definitely logo elements)
				$('.apd-template-canvas-full [class*="has-initial-fill"]').each(function(){
					console.log('🎨 Found element with has-initial-fill class:', this.tagName, this.id || '');
					applyColorToElement(this, color, 'has-initial-fill-class');
				});
				
				// Then, find all path/polygon/text with fill attribute (may be logo elements)
				$('.apd-template-canvas-full path[fill], .apd-template-canvas-full polygon[fill], .apd-template-canvas-full text').each(function(){
					var $el = $(this);
					var fillValue = $el.attr('fill');
					
					// Skip if it's part of background/defs
					if ($el.closest('defs, pattern, linearGradient, radialGradient').length > 0) {
						return;
					}
					
					// Skip if fill is 'none' or empty (unless it's text which might not have fill)
					var tagName = this.tagName.toLowerCase();
					if (tagName !== 'text' && (!fillValue || fillValue === 'none')) {
						return;
					}
					
					// Check if this element is in a logo context
					var isInLogo = $el.closest('.apd-el, .logo-layer, .logo-fill, .apd-logo-box, .fsc-logo-container').length > 0;
					// Check if it has has-initial-fill class (definitely a logo element)
					var hasInitialFillClass = $el.hasClass('has-initial-fill');
					// For text, also check if it has fill attribute with a color value
					var isTextWithFill = tagName === 'text' && fillValue && fillValue !== 'none' && !fillValue.startsWith('url(');
					
					if (isInLogo || hasInitialFillClass || isTextWithFill) {
						console.log('🎨 Found logo element in canvas (comprehensive search):', tagName, this.id || '', 'fill:', fillValue, 'isInLogo:', isInLogo, 'hasInitialFill:', hasInitialFillClass);
						applyColorToElement(this, color, 'canvas-search');
					}
				});
				
				// Method 4: Fallback for direct SVG in containers
				$('.apd-logo-box svg, .fsc-logo-container svg').each(function(){
					var $svg = $(this);
					if ($svg.closest('.logo-layer').length === 0 && $svg.closest('.apd-el').length === 0) {
						console.log('🎨 Found logo SVG in container (fallback):', $svg);
						applyColorToSvgElements($svg, color, 'container-fallback');
					}
				});
				
				// Helper function to apply color to all elements in an SVG
				function applyColorToSvgElements($svg, color, context) {
					if (!$svg || !$svg.length) return;
					$svg.find('path, polygon, rect, circle, ellipse, line, polyline, text').each(function(){
						applyColorToElement(this, color, context);
					});
					// Also check the SVG itself if it's a direct element
					if ($svg.is('path, polygon, rect, circle, ellipse, line, polyline, text')) {
						applyColorToElement($svg[0], color, context + '-direct');
					}
				}
				
				// Helper function to apply color to a single element
				function applyColorToElement(element, color, context) {
					try {
						var $el = $(element);
						var elementTag = element.tagName.toLowerCase();
						var hasInitialFill = element.classList.contains('has-initial-fill');
						var oldFill = element.getAttribute('fill') || (element.style ? element.style.fill : '') || 'none';
						
						// Skip background elements (use jQuery to check parent)
						if ($el.closest('defs, pattern, linearGradient, radialGradient').length > 0) {
							return;
						}
						
						// Only process shape and text elements
						if (!['path', 'polygon', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'text'].includes(elementTag)) {
							return;
						}
						
						if (hasInitialFill || (oldFill !== 'none' && oldFill !== '' && oldFill !== 'transparent')) {
							console.log('🎨 [' + context + '] Applying color to element with fill:', elementTag, element.id || '', 'from', oldFill, 'to', color);
						}
						
						// Remove the initial fill class
						element.classList.remove('has-initial-fill');
						
						// Force remove old fill attribute and style to ensure clean application
						element.removeAttribute('fill');
						if (element.style) {
							element.style.removeProperty('fill');
							element.style.removeProperty('color');
						}
						
						// Apply the new color with multiple methods to ensure it sticks
						element.setAttribute('fill', color);
						if (element.style) {
							element.style.fill = color;
							element.style.setProperty('fill', color, 'important');
						}
						
						// For text elements, also set color attribute and handle stroke
						if (elementTag === 'text') {
							if (element.style) {
								element.style.color = color;
								element.style.setProperty('color', color, 'important');
							}
							element.setAttribute('fill', color);
							element.setAttribute('color', color);
							// Note: We keep stroke for text as it may be part of outline effect
							// Only change fill, not stroke
						}
						
						// Verify the change was applied
						var newFill = element.getAttribute('fill');
						var newStyleFill = element.style ? element.style.fill : '';
						console.log('🎨 [' + context + '] Applied color to', elementTag, element.id || '', 'fill attr:', newFill, 'style.fill:', newStyleFill);
					} catch(e) {
						console.error('🎨 [' + context + '] Error applying color to element:', e, element);
					}
				}
				
				console.log('🎨 Logo color application completed');
			} catch(e) {
				console.error('Error in setLogoFillColor:', e);
			}
		},

        getColorValue: function(colorName) {
            if (FSC.colorMap && FSC.colorMap[colorName]) {
                return FSC.colorMap[colorName];
            }
            if (typeof colorName === 'string' && (colorName.startsWith('#') || colorName.startsWith('rgb') || colorName.startsWith('hsl'))) {
                return colorName;
            }
            const colors = {
                'black': '#000000',
                'yellow': '#FFFF00',
                'dark-red': '#8B0000',
                'orange': '#FFA500',
                'light-blue': '#87CEEB',
                'light-green': '#90EE90',
                'purple': '#800080',
                'light-grey': '#D3D3D3',
                'brown': '#A52A2A',
                'bright-yellow': '#FFD700',
                'dark-green': '#006400',
                'light-purple': '#DDA0DD'
            };

            return colors[colorName] || '#000000';
        },

        saveCustomization: async function(action) {
            const textFields = {};
            const templateData = {};

            $('input[type="text"], input[type="number"], textarea, select').each(function() {
                const $input = $(this);
                const elementId = $input.data('element-id') || $input.attr('id') || $input.attr('name');
                const value = $input.val();

                if (elementId && value && value.trim()) {
                    if (elementId === 'fsc-quantity') return;

                    textFields[elementId] = value.trim();
                }
            });

            $('#fsc-template-fields input, #fsc-template-fields textarea').each(function() {
                const $input = $(this);
                const elementId = $input.data('element-id') || $input.attr('id');
                const value = $input.val();

                if (elementId && value && value.trim()) {
                    const $label = $('#fsc-template-fields label[for="' + elementId + '"]');
                    const labelText = $label.length > 0 ? $label.text() :
                                     $input.attr('data-label') ||
                                     $input.attr('placeholder') ||
                                     elementId.replace(/^fsc-/, '').replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());

                    templateData[elementId] = {
                        value: value.trim(),
                        label: labelText
                    };
                }
            });

            $('[data-element-id]').each(function() {
                const $el = $(this);
                const elementId = $el.data('element-id');
                const value = $el.val() || $el.text();

                if (elementId && value && value.trim()) {
                    templateData[elementId] = value.trim();
                }
            });

            $('.apd-template-canvas-full .apd-field, .apd-template-canvas-full [data-field-id]').each(function() {
                const $el = $(this);
                const fieldId = $el.data('field-id') || $el.attr('class')?.match(/apd-field-(\w+)/)?.[1];
                const value = $el.text();

                if (fieldId && value && value.trim()) {
                    templateData[fieldId] = value.trim();
                }
            });

			// Resolve material display info (name + image url)
			var materialDisplay = (function(){
				var name = FSC.currentMaterial || '';
				var img = FSC.currentMaterialUrl || '';
				if (!img && name) { try { img = FSC.getMaterialUrl(name) || ''; } catch(e) {} }
				return { name: name, image: img };
			})();

			// Normalize template fields to array with labels for summary rendering (keep DOM order)
			var templateFieldsArray = [];
			$('#fsc-template-fields input, #fsc-template-fields textarea').each(function(){
				var $input = $(this);
				var id = $input.data('element-id') || $input.attr('id');
				if (!id) return;
				var val = ($input.val() || '').toString();
				var $lbl = $('#fsc-template-fields label[for="' + id + '"]');
				var label = $lbl.length ? $lbl.text() : ($input.attr('data-label') || $input.attr('placeholder') || id);
				var hint = $input.attr('data-note') || $input.attr('data-hint') || $input.attr('placeholder') || '';
				templateFieldsArray.push({ id: id, label: label, value: val, hint: hint });
			});
			// Fallback: some themes render inputs in .fsc-inputs-dynamic; collect labels there if needed
			if (templateFieldsArray.length === 0) {
				$('.fsc-form-group:has(h4:contains("Custom Text")) .fsc-input-group').each(function(){
					var $group = $(this);
					var $label = $group.find('label').first();
					var $input = $group.find('input, textarea').first();
					if ($input.length) {
						var id = $input.data('element-id') || $input.attr('id') || '';
						var baseLabel = $label.clone();
						baseLabel.find('sup').remove();
						var label = (baseLabel.text() || $input.attr('data-label') || $input.attr('placeholder') || id || '').toString().trim();
						var hint = ($label.find('sup.fsc-hint-sup').text() || $input.attr('data-note') || $input.attr('data-hint') || '').toString().trim();
						var val = ($input.val() || '').toString();
						if (label) { templateFieldsArray.push({ id: id || label, label: label, value: val, hint: hint }); }
					}
				});
			}
			// Also build a quick lookup { label: value } for simple rendering on checkout
			var fieldsDisplay = {};
			var fieldsHints = {};
			templateFieldsArray.forEach(function(it){
				fieldsDisplay[it.label] = it.value;
				if (it.hint) { fieldsHints[it.label] = it.hint; }
			});

			// Get material price
			let materialPrice = 0;
			if (FSC.currentMaterial) {
				const $selectedMaterial = $('.fsc-material-outline-option[data-material="' + FSC.currentMaterial + '"]');
				if ($selectedMaterial.length > 0) {
					materialPrice = parseFloat($selectedMaterial.data('material-price')) || 0;
				} else {
					if (FSC.materialsMap || (window.fscDefaults && window.fscDefaults.materials)) {
						const materialsMap = FSC.materialsMap || window.fscDefaults.materials;
						const materialData = materialsMap[FSC.currentMaterial];
						if (materialData && typeof materialData === 'object' && materialData.price !== undefined) {
							materialPrice = parseFloat(materialData.price) || 0;
						}
					}
				}
			}
			
			// Debug: Log material and color values
			console.log('📋 saveCustomization - Material/Color Debug:');
			console.log('  FSC.currentColor:', FSC.currentColor);
			console.log('  FSC.currentMaterial:', FSC.currentMaterial);
			console.log('  FSC.currentMaterialUrl:', FSC.currentMaterialUrl);
			
			const payload = {
                print_color: FSC.currentColor,
                vinyl_material: FSC.currentMaterial,
                quantity: FSC.quantity,
                product_price: FSC.productPrice || 29.99,
                base_price: FSC.baseProductPrice || 0,
                sale_price: FSC.salePrice || null,
                material_price: materialPrice,
                product_id: FSC.productId || '',
                product_name: FSC.productName || 'Custom Freight Sign',
                material_texture_url: FSC.currentMaterialUrl || '',
				material_display: materialDisplay,
				text_fields: textFields,
				template_data: templateData,
				template_fields_array: templateFieldsArray,
				fields_display: fieldsDisplay,
				fields_hints: fieldsHints,
                // Include variant information if available
                variant_info: FSC.variantData && (FSC.variantData.size || FSC.variantData.sku) ? {
                    size: FSC.variantData.size || '',
                    material: FSC.variantData.material || '',
                    sku: FSC.variantData.sku || '',
                    price: FSC.variantData.price || ''
                } : null
            };

            $('.fsc-container').addClass('fsc-loading');

            console.log('💾 Storing payload to localStorage:', payload);
            try {
                localStorage.setItem('apd_checkout_payload', JSON.stringify(payload));
                console.log('✅ localStorage storage SUCCESS!');
            } catch(e) {
                console.error('❌ localStorage storage FAILED:', e);
            }

            $.ajax({
                url: ajaxObj?.ajax_url || '',
                type: 'POST',
                dataType:'json',
                data: Object.assign({
                    action:'save_customization',
                    nonce:(ajaxObj?.fsc_nonce||ajaxObj?.nonce||''),
                    _wpnonce:(ajaxObj?.fsc_nonce||ajaxObj?.nonce||'')
                }, payload),
                complete: function(){ $('.fsc-container').removeClass('fsc-loading'); },
                success: function(response){
                    if (response && response.success) {
                        switch(action){
                            case 'cart': return FSC.addToCart();
                            case 'customize': return FSC.showCustomizationModal();
                            case 'checkout': return FSC.downloadAndCheckout();
                        }
                    } else { FSC.showMessage('Error saving customization', 'error'); }
                },
                error: function(){ FSC.showMessage('Network error occurred', 'error'); }
            });
        },

        addToCart: async function() {
            console.log('🛒 Add to Cart clicked!');
            console.log('FSC State:', {
                currentColor: FSC.currentColor,
                currentMaterial: FSC.currentMaterial,
                quantity: FSC.quantity,
                productPrice: FSC.productPrice,
                productId: FSC.productId,
                productName: FSC.productName
            });
            
            // Show loading state
            const $btn = $('.fsc-btn-add-cart');
            const originalText = $btn.html();
            $btn.prop('disabled', true).html('🔄 Adding...');
            
            try {
                // Capture preview image first
                let previewImage = null;
                const previewArea = document.querySelector('.apd-template-preview .apd-template-canvas-full');
                
                if (previewArea && window.htmlElementToImage) {
                    try {
                        previewImage = await window.htmlElementToImage(previewArea);
                        console.log('✅ Preview image captured for cart');
                    } catch (e) {
                        console.warn('⚠️ Failed to capture preview image:', e);
                    }
                }
                
                // Get material price
                let materialPrice = 0;
                if (FSC.currentMaterial) {
                    const $selectedMaterial = $('.fsc-material-outline-option[data-material="' + FSC.currentMaterial + '"]');
                    if ($selectedMaterial.length > 0) {
                        materialPrice = parseFloat($selectedMaterial.data('material-price')) || 0;
                    } else {
                        // Try to get from materials map
                        if (FSC.materialsMap || (window.fscDefaults && window.fscDefaults.materials)) {
                            const materialsMap = FSC.materialsMap || window.fscDefaults.materials;
                            const materialData = materialsMap[FSC.currentMaterial];
                            if (materialData) {
                                if (typeof materialData === 'object' && materialData.price !== undefined) {
                                    materialPrice = parseFloat(materialData.price) || 0;
                                }
                            }
                        }
                    }
                }
                
                // Debug: Log material and color values before adding to cart
                console.log('🛒 addToCart - Material/Color Debug:');
                console.log('  FSC.currentColor:', FSC.currentColor);
                console.log('  FSC.currentMaterial:', FSC.currentMaterial);
                
                // Collect current customization data
                const customizationData = {
                    print_color: FSC.currentColor,
                    vinyl_material: FSC.currentMaterial,
                    quantity: FSC.quantity,
                    product_price: FSC.productPrice,
                    base_price: FSC.baseProductPrice || 0,
                    sale_price: FSC.salePrice || null,
                    material_price: materialPrice,
                    product_id: FSC.productId,
                    product_name: FSC.productName,
                    material_texture_url: FSC.getMaterialUrl(FSC.currentMaterial),
                    text_fields: FSC.getTextFields(),
                    template_data: FSC.getTemplateData(),
                    preview_image_svg: previewImage,
                    // Include variant information if available
                    variants: FSC.variantData && (FSC.variantData.size || FSC.variantData.sku) ? {
                        size: FSC.variantData.size || '',
                        material: FSC.variantData.material || '',
                        material_id: FSC.variantData.material || '',
                        sku: FSC.variantData.sku || '',
                        price: FSC.variantData.price || ''
                    } : null
                };
                
                console.log('Customization Data:', customizationData);
                
                if (ajaxObj && ajaxObj.ajax_url) {
                    // Add to cart via AJAX
                    $.ajax({
                        url: ajaxObj.ajax_url,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'apd_add_to_cart',
                            nonce: ajaxObj?.nonce || '',
                            product_id: FSC.productId,
                            quantity: FSC.quantity,
                            customization_data: customizationData
                        },
                        success: function(response) {
                            console.log('Add to cart response:', response);
                            if (response && response.success) {
                                $btn.html('✅ Added to Cart!');
                                FSC.showMessage('Product added to cart! Click the cart icon to view.', 'success');
                                
                                // Update cart count in header if exists
                                FSC.updateCartCount(response.data.cart_count);
                                
                                // Trigger cart update event for floating cart icon
                                const event = new CustomEvent('apd_cart_updated');
                                document.dispatchEvent(event);
                                window.dispatchEvent(event);
                                
                                // Reset button after 2 seconds
                                setTimeout(() => {
                                    $btn.html('Add to Cart');
                                }, 2000);
                            } else {
                                // Fallback: save to local storage cart
                                // Get material price
                                let materialPrice = 0;
                                if (FSC.currentMaterial) {
                                    const $selectedMaterial = $('.fsc-material-outline-option[data-material="' + FSC.currentMaterial + '"]');
                                    if ($selectedMaterial.length > 0) {
                                        materialPrice = parseFloat($selectedMaterial.data('material-price')) || 0;
                                    } else {
                                        if (FSC.materialsMap || (window.fscDefaults && window.fscDefaults.materials)) {
                                            const materialsMap = FSC.materialsMap || window.fscDefaults.materials;
                                            const materialData = materialsMap[FSC.currentMaterial];
                                            if (materialData && typeof materialData === 'object' && materialData.price !== undefined) {
                                                materialPrice = parseFloat(materialData.price) || 0;
                                            }
                                        }
                                    }
                                }
                                FSC.addItemToLocalCart({
                                    product_id: FSC.productId,
                                    product_name: FSC.productName,
                                    product_price: FSC.productPrice,
                                    base_price: FSC.baseProductPrice || 0,
                                    sale_price: FSC.salePrice || null,
                                    material_price: materialPrice,
                                    quantity: FSC.quantity,
                                    print_color: FSC.currentColor,
                                    vinyl_material: FSC.currentMaterial,
                                    material_texture_url: FSC.getMaterialUrl(FSC.currentMaterial),
                                    text_fields: FSC.getTextFields(),
                                    template_data: FSC.getTemplateData(),
                                    preview_image_svg: previewImage
                                });
                                $btn.html('✅ Added!');
                                FSC.showMessage('Added to local cart', 'success');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.warn('Add to cart AJAX error, saving to local cart instead:', { xhr, status, error });
                            // Get material price
                            let materialPrice = 0;
                            if (FSC.currentMaterial) {
                                const $selectedMaterial = $('.fsc-material-outline-option[data-material="' + FSC.currentMaterial + '"]');
                                if ($selectedMaterial.length > 0) {
                                    materialPrice = parseFloat($selectedMaterial.data('material-price')) || 0;
                                } else {
                                    if (FSC.materialsMap || (window.fscDefaults && window.fscDefaults.materials)) {
                                        const materialsMap = FSC.materialsMap || window.fscDefaults.materials;
                                        const materialData = materialsMap[FSC.currentMaterial];
                                        if (materialData && typeof materialData === 'object' && materialData.price !== undefined) {
                                            materialPrice = parseFloat(materialData.price) || 0;
                                        }
                                    }
                                }
                            }
                            FSC.addItemToLocalCart({
                                product_id: FSC.productId,
                                product_name: FSC.productName,
                                product_price: FSC.productPrice,
                                base_price: FSC.baseProductPrice || 0,
                                sale_price: FSC.salePrice || null,
                                material_price: materialPrice,
                                quantity: FSC.quantity,
                                print_color: FSC.currentColor,
                                vinyl_material: FSC.currentMaterial,
                                material_texture_url: FSC.getMaterialUrl(FSC.currentMaterial),
                                text_fields: FSC.getTextFields(),
                                template_data: FSC.getTemplateData(),
                                preview_image_svg: previewImage
                            });
                            $btn.html('✅ Added!');
                            FSC.showMessage('Added to local cart', 'success');
                        }
                    });
                } else {
                    // No server available; save to local cart directly
                    // Get material price
                    let materialPrice = 0;
                    if (FSC.currentMaterial) {
                        const $selectedMaterial = $('.fsc-material-outline-option[data-material="' + FSC.currentMaterial + '"]');
                        if ($selectedMaterial.length > 0) {
                            materialPrice = parseFloat($selectedMaterial.data('material-price')) || 0;
                        } else {
                            if (FSC.materialsMap || (window.fscDefaults && window.fscDefaults.materials)) {
                                const materialsMap = FSC.materialsMap || window.fscDefaults.materials;
                                const materialData = materialsMap[FSC.currentMaterial];
                                if (materialData && typeof materialData === 'object' && materialData.price !== undefined) {
                                    materialPrice = parseFloat(materialData.price) || 0;
                                }
                            }
                        }
                    }
                    FSC.addItemToLocalCart({
                        product_id: FSC.productId,
                        product_name: FSC.productName,
                        product_price: FSC.productPrice,
                        base_price: FSC.baseProductPrice || 0,
                        sale_price: FSC.salePrice || null,
                        material_price: materialPrice,
                        quantity: FSC.quantity,
                        print_color: FSC.currentColor,
                        vinyl_material: FSC.currentMaterial,
                        material_texture_url: FSC.getMaterialUrl(FSC.currentMaterial),
                        text_fields: FSC.getTextFields(),
                        template_data: FSC.getTemplateData(),
                        preview_image_svg: previewImage
                    });
                    $btn.html('✅ Added!');
                    FSC.showMessage('Added to local cart', 'success');
                }
            } catch (error) {
                console.error('Add to cart error:', error);
                $btn.prop('disabled', false).html(originalText);
                FSC.showMessage('Error adding to cart: ' + error.message, 'error');
            }
        },
        
        getTextFields: function() {
            const textFields = {};
            $('.fsc-text-input').each(function() {
                const fieldId = $(this).data('field-id') || $(this).attr('id');
                if (fieldId) {
                    textFields[fieldId] = $(this).val();
                }
            });
            return textFields;
        },
        
        getTemplateData: function() {
            const templateData = {};
            $('.fsc-form-group input, .fsc-form-group select, .fsc-form-group textarea').each(function() {
                const fieldId = $(this).attr('id') || $(this).attr('name');
                if (fieldId) {
                    templateData[fieldId] = $(this).val();
                }
            });
            return templateData;
        },
        
        updateCartCount: function(count) {
            // Update cart count in header/navigation if exists
            $('.apd-cart-count, .cart-count').text(count + ' items');
            console.log('🛒 Cart count updated to:', count);
        },

        // LocalStorage cart helpers (client-side fallback)
        getLocalCart: function() {
            try {
                const raw = localStorage.getItem('apd_cart');
                return raw ? JSON.parse(raw) : [];
            } catch (e) {
                console.warn('⚠️ Failed to parse apd_cart from localStorage:', e);
                return [];
            }
        },

        setLocalCart: function(items) {
            try {
                localStorage.setItem('apd_cart', JSON.stringify(items || []));
                return true;
            } catch (e) {
                console.warn('⚠️ Failed to save apd_cart to localStorage:', e);
                return false;
            }
        },

        getLocalCartCount: function() {
            const items = FSC.getLocalCart();
            return items.reduce((sum, it) => sum + (parseInt(it.quantity, 10) || 0), 0);
        },

        addItemToLocalCart: function(item) {
            const items = FSC.getLocalCart();
            // Merge by product_id + options to avoid duplicates
            const key = JSON.stringify({
                product_id: item.product_id,
                print_color: item.print_color,
                vinyl_material: item.vinyl_material,
                text_fields: item.text_fields
            });
            const existing = items.find(it => it._key === key);
            if (existing) {
                existing.quantity = (parseInt(existing.quantity, 10) || 0) + (parseInt(item.quantity, 10) || 1);
                existing.updated_at = Date.now();
            } else {
                item._key = key;
                item.created_at = Date.now();
                item.updated_at = Date.now();
                items.push(item);
            }
            FSC.setLocalCart(items);
            FSC.updateCartCount(FSC.getLocalCartCount());
        },

        syncCartCountFromLocal: function() {
            const count = FSC.getLocalCartCount();
            if (count > 0) {
                FSC.updateCartCount(count);
            }
        },

        loadCartCount: function() {
            // Load current cart count on page load
            if (ajaxObj && ajaxObj.ajax_url) {
                $.ajax({
                    url: ajaxObj.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'apd_get_cart',
                        nonce: ajaxObj.nonce
                    },
                    success: function(response) {
                        if (response && response.success) {
                            FSC.updateCartCount(response.data.count);
                        }
                    },
                    error: function() {
                        console.warn('Failed to load cart count');
                    }
                });
            } else {
                // Fallback to local storage count
                FSC.syncCartCountFromLocal();
            }
        },

        showCustomizationModal: function() {
            FSC.showMessage('Customization options opened', 'info');
        },

        downloadAndCheckout: async function() {
            console.log('🛒 Starting checkout process...');
            
            // Find the preview area
            const previewArea = document.querySelector('.apd-template-preview .apd-template-canvas-full');

            if (!previewArea) {
                console.error('❌ Preview area not found');
                // Get the stored payload and redirect without preview image
                var stored = localStorage.getItem('apd_checkout_payload');
                var payload = stored ? JSON.parse(stored) : {};
                if (!payload.preview_image_svg && !payload.preview_image_png) {
                    console.warn('⚠️ No preview image available, redirecting anyway...');
                }
                
                // Redirect to official checkout page
                var checkoutUrl = (ajaxObj && ajaxObj.checkout_url) ? ajaxObj.checkout_url : '/checkout/';
                // Add instant=true parameter so checkout page knows to use payload
                if (checkoutUrl.indexOf('?') === -1) {
                    checkoutUrl += '?instant=true';
                } else {
                    checkoutUrl += '&instant=true';
                }
                console.log('🔀 Redirecting to checkout (no preview area):', checkoutUrl);
                window.location.href = checkoutUrl;
                return;
            }

            console.log('📥 Preview area found:', previewArea);
            console.log('📥 Preview area dimensions:', previewArea.offsetWidth, 'x', previewArea.offsetHeight);

            try {
                // Capture the preview image
                const svgDataForPayload = await window.htmlElementToImage(previewArea);
                console.log('✅ Image capture successful, data size:', svgDataForPayload ? svgDataForPayload.length : 0);

                // Update the checkout payload with the preview image
                try {
                    var stored = localStorage.getItem('apd_checkout_payload');
                    var payload = stored ? JSON.parse(stored) : {};
                    
                    // Store the preview image
                    if (svgDataForPayload) { 
                        payload.preview_image_svg = svgDataForPayload;
                        console.log('✅ Preview image stored in payload');
                    }
                    
                    payload.preview_captured_at = new Date().toISOString();
                    
                    // Ensure all required fields are present
                    payload.product_id = payload.product_id || (FSC.productId || '');
                    payload.product_name = payload.product_name || (FSC.productName || '');
                    payload.product_price = payload.product_price || (FSC.productPrice || 29.99);
                    payload.quantity = payload.quantity || (FSC.quantity || 1);
                    payload.print_color = payload.print_color || FSC.currentColor;
                    payload.vinyl_material = payload.vinyl_material || FSC.currentMaterial;
                    payload.material_texture_url = payload.material_texture_url || FSC.currentMaterialUrl || '';

                    // Check payload size before saving
                    const payloadString = JSON.stringify(payload);
                    const payloadSize = new Blob([payloadString]).size;
                    console.log('📦 Payload size:', payloadSize, 'bytes');
                    
                    // If payload is too large, remove preview image
                    if (payloadSize > 500000) {
                        console.warn('⚠️ Payload too large, removing preview image');
                        delete payload.preview_image_svg;
                        delete payload.preview_image_png;
                    }
                    
                    // Save to localStorage (use oneclick key for consistency with Buy Now)
                    const finalPayloadString = JSON.stringify(payload);
                    localStorage.setItem('apd_checkout_payload_oneclick', finalPayloadString);
                    // Also save to regular key for backward compatibility
                    localStorage.setItem('apd_checkout_payload', finalPayloadString);
                    console.log('✅ Updated payload saved to localStorage');

                    // Skip server upload entirely - we'll use localStorage only
                    // Server upload was causing 400 errors due to payload size limits
                    // localStorage is sufficient for checkout page to read the payload
                    console.log('ℹ️ Using localStorage only (server upload skipped to avoid size limits)');
                } catch(e) {
                    console.error('❌ Failed to update payload with preview image:', e);
                }

                // Small delay to ensure localStorage is written, then redirect
                setTimeout(function() {
                    var checkoutUrl = (ajaxObj && ajaxObj.checkout_url) ? ajaxObj.checkout_url : '/checkout/';
                    // Add instant=true parameter so checkout page knows to use payload
                    if (checkoutUrl.indexOf('?') === -1) {
                        checkoutUrl += '?instant=true';
                    } else {
                        checkoutUrl += '&instant=true';
                    }
                    console.log('🔀 Redirecting to checkout:', checkoutUrl);
                    window.location.href = checkoutUrl;
                }, 200);

            } catch (error) {
                console.error('❌ Image capture failed:', error);
                // Redirect anyway even if image capture fails
                setTimeout(function() {
                    var checkoutUrl = (ajaxObj && ajaxObj.checkout_url) ? ajaxObj.checkout_url : '/checkout/';
                    // Add instant=true parameter so checkout page knows to use payload
                    if (checkoutUrl.indexOf('?') === -1) {
                        checkoutUrl += '?instant=true';
                    } else {
                        checkoutUrl += '&instant=true';
                    }
                    console.log('🔀 Redirecting to checkout (without preview):', checkoutUrl);
                    window.location.href = checkoutUrl;
                }, 200);
            }
        },

        testMaterialSelection: function(materialName) {
            FSC.currentMaterial = materialName;
            FSC.updatePreview();
            
            $('.fsc-material-option').removeClass('selected');
            $('.fsc-material-outline-option').removeClass('selected');
            
            $('.fsc-material-option[data-material="' + materialName + '"]').addClass('selected');
            $('.fsc-material-outline-option[data-material="' + materialName + '"]').addClass('selected');
        },

        showMessage: function(message, type) {
            const $message = $(`<div class="fsc-message fsc-message-${type}">${message}</div>`);
            $('.fsc-container').append($message);

            setTimeout(function() {
                $message.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        },
        
        // Debug function to check current state
        debugState: function() {
            // Debug function - console logs disabled
        },
        
        // Force convert from filter/mask approach to stroke approach
        forceStrokeApproach: function() {
            const $logoWrap = $('.fsc-logo-container, .apd-logo-box');
            if ($logoWrap.length) {
                const $existingSvg = $logoWrap.find('svg').first();
                if ($existingSvg.length) {
                    const svgHtml = $existingSvg.prop('outerHTML');
                    $logoWrap.css('position', 'relative');
                    $logoWrap.html(
                        '<div class="logo-layer logo-outline" style="position:absolute; inset:0; z-index:1; pointer-events:none;">' + svgHtml + '</div>' +
                        '<div class="logo-layer logo-fill" style="position:absolute; inset:0; z-index:2; pointer-events:none;">' + svgHtml + '</div>'
                    );
                    FSC.updatePreview();
                }
            }
        }
    };

    // Initialize the customizer
    FSC.init();
    
    // Fallback initialization for shortcode usage
    jQuery(document).ready(function($) {
        // Check if FSC is already initialized
        if (!window.FSC || !window.FSC.currentColor) {
            console.log('🔄 Re-initializing FSC for shortcode usage...');
            FSC.init();
        }
        
        // Ensure buttons are bound even if they're added dynamically
        setTimeout(function() {
            if ($('.fsc-btn-add-cart').length > 0 && !$('.fsc-btn-add-cart').data('events')) {
                console.log('🔄 Re-binding ADD TO CART buttons...');
                $('.fsc-btn-add-cart').off('click').on('click', function(e) {
                    console.log('🛒 ADD TO CART button clicked (fallback)!');
                    e.preventDefault();
                    FSC.saveCustomization('cart');
                });
            }
        }, 1000);
    });
    
    // Make FSC available globally for debugging
    window.FSC = FSC;
    window.testMaterial = function(materialName) {
        FSC.testMaterialSelection(materialName);
    };
    window.debugFSC = function() {
        FSC.debugState();
    };
    window.debugMaterialOutline = function() {
        // Debug function - console logs disabled
    };
    window.forceStrokeApproach = function() {
        FSC.forceStrokeApproach();
    };
    window.testResponsive = function() {
        console.log('📱 Testing Responsive Scaling...');
        console.log('Current scale factor:', FSC._currentScaleFactor);
        console.log('Window size:', $(window).width() + 'x' + $(window).height());
        console.log('Preview area size:', $('.fsc-preview-area').width() + 'x' + $('.fsc-preview-area').height());
        
        if (FSC._cachedTemplateData) {
            console.log('Re-rendering template...');
            FSC.renderTemplate(FSC._cachedTemplateData, FSC._cachedProductData);
        } else {
            console.log('⚠️ No cached template data found');
        }
    };
    
    // Add message styles
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .fsc-message {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 6px;
                color: white;
                font-weight: 600;
                z-index: 9999;
                animation: slideIn 0.3s ease;
            }
            .fsc-message-success { background: #28a745; }
            .fsc-message-error { background: #dc3545; }
            .fsc-message-info { background: #17a2b8; }
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `)
        .appendTo('head');
    
    // Global functions for testing character limits
    window.testCharacterLimit = function(limit) {
        console.log('🧪 Testing character limit:', limit);
        if (FSC && FSC.setCharacterLimit) {
            FSC.setCharacterLimit(limit);
        } else {
            console.error('FSC.setCharacterLimit not available');
        }
    };
    
    // Immediate character limit enforcement
    window.enforceCharacterLimits = function() {
        console.log('🚨 ENFORCING character limits immediately...');
        $('input[type="text"], textarea').each(function() {
            const $input = $(this);
            // Only set maxlength if data-template-max exists
            const templateMax = $input.attr('data-template-max');
            if (templateMax) {
                $input.attr('maxlength', templateMax);
                $input.prop('maxLength', Number(templateMax));
                console.log('🔧 FORCED limit on input:', $input.attr('id'), '->', templateMax, '(from template)');
            } else {
                // Remove maxlength if no template max (allow unlimited input)
                $input.removeAttr('maxlength');
                $input.prop('maxLength', -1);
                console.log('🔧 Removed limit on input:', $input.attr('id'), '(unlimited)');
            }
        });
        console.log('✅ Character limits enforced on all inputs');
    };
    
    // Auto-enforce limits when page loads
    $(document).ready(function() {
        setTimeout(function() {
            console.log('🚨 Auto-enforcing character limits on page load...');
            $('input[type="text"], textarea').each(function() {
                const $input = $(this);
                // Only set maxlength if data-template-max exists
                const templateMax = $input.attr('data-template-max');
                if (templateMax) {
                    $input.attr('maxlength', templateMax);
                    $input.prop('maxLength', Number(templateMax));
                    console.log('🔧 Auto-applied template limit to input:', $input.attr('id'), '->', templateMax);
                } else {
                    // Remove maxlength if no template max (allow unlimited input)
                    $input.removeAttr('maxlength');
                    // Don't set maxLength to -1 as it's invalid - just remove the attribute
                    // The input will have no limit by default
                    console.log('🔧 Removed limit on input:', $input.attr('id'), '(unlimited)');
                }
            }); 
        }, 1000);
    });
    
    window.checkCharacterLimits = function() {
        console.log('🔍 Checking current character limits...');
        $('#fsc-template-fields input, #fsc-template-fields textarea').each(function() {
            const $input = $(this);
            const maxLength = $input.attr('maxlength');
            const currentLength = $input.val().length;
            console.log('Input:', $input.attr('id'), 'Max:', maxLength, 'Current:', currentLength);
        });
    };
    
    window.setCharacterLimitById = function(inputId, limit) {
        console.log('🧪 Setting character limit for specific input:', inputId, 'to', limit);
        if (FSC && FSC.setCharacterLimitById) {
            FSC.setCharacterLimitById(inputId, limit);
        } else {
            console.error('FSC.setCharacterLimitById not available');
        }
    };
    
    window.setAllTextLimits = function(limit) {
        console.log('🧪 Setting ALL text inputs to limit:', limit);
        if (FSC && FSC.setAllTextLimits) {
            FSC.setAllTextLimits(limit);
        } else {
            console.error('FSC.setAllTextLimits not available');
        }
    };
});
