(function($) {
    'use strict';

    // Initialize product customizer
    $(document).ready(function() {
        initProductCustomizer();
    });

    function initProductCustomizer() {
        $('.apd-product-customizer').each(function() {
            const $customizer = $(this);
            const productId = $customizer.data('product-id');
            
            if (productId) {
                loadProductCustomizer(productId, $customizer);
            }
        });
    }

    function loadProductCustomizer(productId, $customizer) {
        console.log('APD Customizer: Loading product ID:', productId);
        console.log('APD Customizer: Product ID type:', typeof productId);
        console.log('APD Customizer: Product ID value:', productId);
        
        // Show loading state with debug info
        $customizer.html(`
            <div class="apd-customizer-loading">
                <div class="apd-spinner"></div>
                <p>Loading customizer...</p>
                <p style="font-size: 12px; color: #666;">Debug: Product ID = ${productId} (${typeof productId})</p>
            </div>
        `);

        // Fetch product and template data
        $.ajax({
            url: apd_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'apd_get_customizer_data',
                product_id: productId,
                nonce: apd_ajax.nonce
            },
            success: function(response) {
                console.log('APD Customizer: AJAX response:', response);
                if (response.success && response.data) {
                    renderCustomizer(response.data, $customizer);
                } else {
                    showError($customizer, 'Failed to load customizer data: ' + (response.data || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.error('APD Customizer: AJAX error:', xhr, status, error);
                showError($customizer, 'Error loading customizer: ' + error);
            }
        });
    }

    function renderCustomizer(data, $customizer) {
        const { product, template, templateData } = data;
        
        const html = `
            <div class="apd-customizer-container">
                <div class="apd-customizer-header">
                    <h2>Customize: ${product.title}</h2>
                    <div class="apd-customizer-actions">
                        <button class="apd-btn apd-btn-secondary apd-reset-btn">Reset</button>
                        <button class="apd-btn apd-btn-primary apd-save-btn">Save Design</button>
                    </div>
                </div>
                
                <div class="apd-customizer-content">
                    <div class="apd-customizer-preview">
                        <div class="apd-preview-header">
                            <h3>Preview</h3>
                            <div class="apd-preview-controls">
                                <button class="apd-btn apd-btn-small apd-zoom-out">-</button>
                                <span class="apd-zoom-level">100%</span>
                                <button class="apd-btn apd-btn-small apd-zoom-in">+</button>
                            </div>
                        </div>
                        <div class="apd-preview-container">
                            <div class="apd-canvas-preview" id="apd-canvas-preview-${product.id}">
                                <!-- Canvas will be rendered here -->
                            </div>
                        </div>
                    </div>
                    
                    <div class="apd-customizer-panel">
                        <div class="apd-panel-header">
                            <h3>Customization Options</h3>
                        </div>
                        
                        <div class="apd-panel-content">
                            <div class="apd-text-fields-section">
                                <h4>Text Fields</h4>
                                <div class="apd-text-fields" id="apd-text-fields-${product.id}">
                                    <!-- Text input fields will be generated here -->
                                </div>
                            </div>
                            
                            <div class="apd-color-options-section">
                                <h4>Color Options</h4>
                                <div class="apd-color-options" id="apd-color-options-${product.id}">
                                    <!-- Color controls will be generated here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $customizer.html(html);
        
        // Initialize customizer
        initializeCustomizer(product, template, templateData, $customizer);
    }

    function initializeCustomizer(product, template, templateData, $customizer) {
        const canvasId = `apd-canvas-preview-${product.id}`;
        const textFieldsId = `apd-text-fields-${product.id}`;
        const colorOptionsId = `apd-color-options-${product.id}`;
        
        // Render canvas
        renderCanvas(canvasId, templateData);
        
        // Generate text input fields
        generateTextFields(textFieldsId, templateData);
        
        // Generate color controls
        generateColorControls(colorOptionsId, templateData);
        
        // Initialize event handlers
        initializeEventHandlers(product, template, templateData, $customizer);
    }

    function renderCanvas(canvasId, templateData) {
        const $canvas = $(`#${canvasId}`);
        
        if (!templateData || !templateData.canvas) {
            $canvas.html('<div class="apd-error">No template data available</div>');
            return;
        }
        
        const canvas = templateData.canvas;
        const elements = templateData.elements || [];
        
        // Set canvas size
        $canvas.css({
            width: canvas.width + 'px',
            height: canvas.height + 'px',
            position: 'relative',
            border: '1px solid #ddd',
            backgroundColor: canvas.background ? canvas.background.color || '#ffffff' : '#ffffff'
        });
        
        // Set background
        if (canvas.background) {
            if (canvas.background.type === 'image' && canvas.background.image) {
                $canvas.css('background-image', `url(${canvas.background.image})`);
                $canvas.css('background-size', 'cover');
                $canvas.css('background-position', 'center');
                $canvas.css('background-repeat', 'no-repeat');
            } else if (canvas.background.type === 'gradient' && canvas.background.gradient) {
                const gradient = canvas.background.gradient;
                $canvas.css('background', `linear-gradient(${gradient.direction}, ${gradient.color1}, ${gradient.color2})`);
            }
        }
        
        // Render elements
        elements.forEach(function(element) {
            renderElement($canvas, element);
        });
    }

    function renderElement($canvas, element) {
        const $element = $(`<div class="apd-preview-element" data-element-id="${element.id}" data-element-type="${element.type}">`);
        
        $element.css({
            position: 'absolute',
            left: element.x + 'px',
            top: element.y + 'px',
            width: element.width + 'px',
            height: element.height + 'px',
            border: '2px dashed transparent',
            cursor: 'pointer'
        });
        
        // Add element content based on type
        if (element.type === 'text') {
            $element.html(`<span class="apd-text-content">${element.label}</span>`);
            
            // Apply text properties
            if (element.properties) {
                if (element.properties.textColor) {
                    $element.css('color', element.properties.textColor);
                }
                if (element.properties.fontSize) {
                    $element.css('font-size', element.properties.fontSize + 'px');
                }
                if (element.properties.fontWeight) {
                    $element.css('font-weight', element.properties.fontWeight);
                }
            }
        } else if (element.type === 'logo') {
            $element.html(`<div class="apd-logo-content">${element.label}</div>`);
            
            // Apply logo properties
            if (element.properties) {
                if (element.properties.logoColor) {
                    $element.css('color', element.properties.logoColor);
                }
            }
        } else if (element.type === 'image') {
            $element.html(`<div class="apd-image-content">${element.label}</div>`);
        }
        
        $canvas.append($element);
    }

    function generateTextFields(containerId, templateData) {
        const $container = $(`#${containerId}`);
        const textElements = (templateData.elements || []).filter(el => el.type === 'text');
        
        if (textElements.length === 0) {
            $container.html('<p class="apd-no-fields">No text fields available</p>');
            return;
        }
        
        textElements.forEach(function(element, index) {
            const maxLength = element.properties?.maxLength || element.maxLength || '';
            const maxLengthAttr = maxLength ? ` maxlength="${maxLength}"` : '';
            
            const fieldHtml = `
                <div class="apd-text-field" data-element-id="${element.id}">
                    <label for="text-${element.id}">${element.label}</label>
                    <input type="text" 
                           id="text-${element.id}" 
                           class="apd-text-input" 
                           value="${element.label}" 
                           data-element-id="${element.id}"
                           placeholder="Enter text..."${maxLengthAttr}>
                    <div class="apd-text-options">
                        <div class="apd-color-picker">
                            <label>Text Color:</label>
                            <input type="color" 
                                   class="apd-color-input" 
                                   data-property="textColor"
                                   data-element-id="${element.id}"
                                   value="${element.properties?.textColor || '#000000'}">
                        </div>
                        <div class="apd-outline-options">
                            <label>Outline:</label>
                            <select class="apd-outline-select" data-element-id="${element.id}">
                                <option value="none">None</option>
                                <option value="1px solid #000">Black</option>
                                <option value="1px solid #fff">White</option>
                                <option value="2px solid #000">Thick Black</option>
                                <option value="2px solid #fff">Thick White</option>
                            </select>
                        </div>
                    </div>
                </div>
            `;
            $container.append(fieldHtml);
        });
    }

    function generateColorControls(containerId, templateData) {
        const $container = $(`#${containerId}`);
        
        const colorPalette = templateData.colorPalette || [];
        
        if (colorPalette.length === 0) {
            $container.html('<p class="apd-no-colors">No color options available</p>');
            return;
        }
        
        const colorHtml = `
            <div class="apd-color-palette">
                <h5>Available Colors</h5>
                <div class="apd-color-grid">
                    ${colorPalette.map(color => `
                        <div class="apd-color-item" 
                             style="background-color: ${color.color}" 
                             data-color="${color.color}"
                             title="${color.name}">
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
        
        $container.html(colorHtml);
    }

    function initializeEventHandlers(product, template, templateData, $customizer) {
        // Text input changes
        $customizer.on('input', '.apd-text-input', function() {
            const elementId = $(this).data('element-id');
            const newText = $(this).val();
            updateElementText(elementId, newText);
        });
        
        // Color changes
        $customizer.on('change', '.apd-color-input', function() {
            const elementId = $(this).data('element-id');
            const property = $(this).data('property');
            const newColor = $(this).val();
            updateElementProperty(elementId, property, newColor);
        });
        
        // Outline changes
        $customizer.on('change', '.apd-outline-select', function() {
            const elementId = $(this).data('element-id');
            const outline = $(this).val();
            updateElementOutline(elementId, outline);
        });
        
        // Color palette clicks
        $customizer.on('click', '.apd-color-item', function() {
            const color = $(this).data('color');
            // Apply color to selected element or show color picker
            showColorPicker(color);
        });
        
        // Zoom controls
        $customizer.on('click', '.apd-zoom-in', function() {
            zoomCanvas(1.1);
        });
        
        $customizer.on('click', '.apd-zoom-out', function() {
            zoomCanvas(0.9);
        });
        
        // Save button
        $customizer.on('click', '.apd-save-btn', function() {
            saveCustomization(product.id, templateData);
        });
        
        // Reset button
        $customizer.on('click', '.apd-reset-btn', function() {
            resetCustomization(product, template, templateData, $customizer);
        });
    }

    function updateElementText(elementId, newText) {
        const $element = $(`.apd-preview-element[data-element-id="${elementId}"]`);
        $element.find('.apd-text-content').text(newText);
    }

    function updateElementProperty(elementId, property, value) {
        const $element = $(`.apd-preview-element[data-element-id="${elementId}"]`);
        $element.css(property, value);
    }

    function updateElementOutline(elementId, outline) {
        const $element = $(`.apd-preview-element[data-element-id="${elementId}"]`);
        if (outline === 'none') {
            $element.css('text-shadow', 'none');
        } else {
            $element.css('text-shadow', `-1px -1px 0 ${outline.split(' ')[2]}, 1px -1px 0 ${outline.split(' ')[2]}, -1px 1px 0 ${outline.split(' ')[2]}, 1px 1px 0 ${outline.split(' ')[2]}`);
        }
    }

    function zoomCanvas(factor) {
        const $canvas = $('.apd-canvas-preview');
        const currentZoom = parseFloat($canvas.css('transform').match(/scale\(([^)]+)\)/) || [1, 1])[1];
        const newZoom = currentZoom * factor;
        $canvas.css('transform', `scale(${newZoom})`);
        $('.apd-zoom-level').text(Math.round(newZoom * 100) + '%');
    }

    function saveCustomization(productId, templateData) {
        // Collect current customization data
        const customizations = {};
        
        $('.apd-text-input').each(function() {
            const elementId = $(this).data('element-id');
            customizations[elementId] = {
                text: $(this).val(),
                color: $(`input[data-element-id="${elementId}"][data-property="textColor"]`).val(),
                outline: $(`select[data-element-id="${elementId}"]`).val()
            };
        });
        
        // Save via AJAX
        $.ajax({
            url: apd_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'apd_save_customization',
                product_id: productId,
                customizations: customizations,
                nonce: apd_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showSuccess('Customization saved successfully!');
                } else {
                    showError(null, 'Failed to save customization');
                }
            },
            error: function() {
                showError(null, 'Error saving customization');
            }
        });
    }

    function resetCustomization(product, template, templateData, $customizer) {
        if (confirm('Are you sure you want to reset all changes?')) {
            // Reload the customizer
            loadProductCustomizer(product.id, $customizer);
        }
    }

    function showColorPicker(color) {
        // Simple color picker implementation
        const newColor = prompt('Enter color (hex, rgb, or name):', color);
        if (newColor) {
            // Apply to selected elements or show color options
            $('.apd-color-item[data-color="' + color + '"]').css('background-color', newColor);
        }
    }

    function showSuccess(message) {
        // Show success message
        const $message = $(`<div class="apd-success-message">${message}</div>`);
        $('body').append($message);
        setTimeout(() => $message.fadeOut(), 3000);
    }

    function showError($container, message) {
        if ($container) {
            $container.html(`<div class="apd-error">${message}</div>`);
        } else {
            const $message = $(`<div class="apd-error-message">${message}</div>`);
            $('body').append($message);
            setTimeout(() => $message.fadeOut(), 5000);
        }
    }

    // Handle dynamic content loading
    $(document).on('DOMNodeInserted', function(e) {
        const $newCustomizer = $(e.target).find('.apd-product-customizer');
        if ($newCustomizer.length > 0) {
            initProductCustomizer();
        }
    });

})(jQuery);
