jQuery(document).ready(function($) {
    // Prevent multiple initialization
    if (window.apdDesignerInitialized) {
        return;
    }
    window.apdDesignerInitialized = true;
    
    let selectedElement = null;
    let elementCounter = 0;
    let templateData = JSON.parse($('#template-data').val() || '{}');
    
    // Undo system
    let undoHistory = [];
    let undoIndex = -1;
    const MAX_UNDO_HISTORY = 50;
    
    // Color palette system
    let colorPalette = [];
    let currentEditingColor = null;
    
    
    // Initialize undo system
    saveState();
    
    // Initialize color palette
    initColorPalette();
    
    // Initialize canvas
    initCanvas();
    
    
    // Add element buttons
    $('.apd-add-element').on('click', function() {
        const type = $(this).data('type');
        addElement(type);
    });
    
    // Save template
    $('#save-template').on('click', function() {
        saveTemplate();
    });
    
    // Undo button
    $('#undo-btn').on('click', function() {
        undo();
    });
    
    // Canvas controls
    $('#canvas-width, #canvas-height').on('change', function() {
        updateCanvasSize();
    });
    
    // Add input validation for canvas size inputs
    $('#canvas-width, #canvas-height').on('input', function() {
        const value = parseInt($(this).val());
        if (isNaN(value) || value < 100 || value > 2000) {
            $(this).addClass('error');
        } else {
            $(this).removeClass('error');
        }
    });
    
    $('#background-type').on('change', function() {
        updateBackgroundType();
    });
    
    $('#background-color').on('change', function() {
        updateBackgroundColor();
    });
    
    // Background image upload
    $('#background-image-upload').on('change', function() {
        handleBackgroundImageUpload(this.files[0]);
    });
    
    // Gradient controls
    $(document).on('change', '#gradient-color-1, #gradient-color-2, #gradient-direction', function() {
        updateGradientBackground();
    });
    
    // Add color button
    $('#add-color-btn').on('click', function() {
        openColorPicker();
    });
    
    // Color picker modal events
    $('#color-picker-input').on('change', function() {
        const color = $(this).val();
        $('#color-preview').css('background-color', color);
    });
    
    $('#save-color-btn').on('click', function() {
        saveColorFromPicker();
    });
    
    $('#cancel-color-btn, .apd-modal-close').on('click', function() {
        closeColorPicker();
    });

    // Delete color in modal (only for edit mode)
    $('#delete-color-btn').on('click', function() {
        if (currentEditingColor === null) return;
        if (!confirm('Are you sure you want to delete this color?')) return;
        // Remove color
        colorPalette.splice(currentEditingColor, 1);
        templateData.colorPalette = colorPalette;
        renderColorPalette();
        saveState();
        closeColorPicker();
    });

    // Fill logo with color option
    $('#fill-logo-with-color').on('change', function() {
        templateData.fillLogoWithColor = $(this).is(':checked');
        saveState();
        console.log('Fill logo with color option changed to:', templateData.fillLogoWithColor);
    });
    
    // Click outside modal to close
    $('#color-picker-modal').on('click', function(e) {
        if (e.target === this) {
            closeColorPicker();
        }
    });
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl+Z for undo
        if (e.ctrlKey && e.key === 'z' && !e.shiftKey) {
            e.preventDefault();
            undo();
        }
        // Ctrl+Y or Ctrl+Shift+Z for redo (future feature)
        else if ((e.ctrlKey && e.key === 'y') || (e.ctrlKey && e.shiftKey && e.key === 'Z')) {
            e.preventDefault();
            // redo(); // Future feature
        }
    });
    
    function initCanvas() {
        // Initialize canvas with default size if not set
        if (!templateData.canvas) {
            templateData.canvas = {
                width: 800,
                height: 600
            };
        }
        
        // Ensure canvas size is within limits
        const maxWidth = 2000;
        const maxHeight = 2000;
        const minWidth = 100;
        const minHeight = 100;
        
        templateData.canvas.width = Math.max(minWidth, Math.min(templateData.canvas.width, maxWidth));
        templateData.canvas.height = Math.max(minHeight, Math.min(templateData.canvas.height, maxHeight));
        
        // Set initial canvas size
        $('#apd-canvas').css({
            width: templateData.canvas.width + 'px',
            height: templateData.canvas.height + 'px'
        });
        
        
        // Update input fields
        $('#canvas-width').val(templateData.canvas.width);
        $('#canvas-height').val(templateData.canvas.height);
        
        // Load background settings
        if (templateData.canvas.background) {
            loadBackgroundSettings(templateData.canvas.background);
        }
        
        // Load existing elements
        if (templateData.elements) {
            // Clear existing elements first to avoid duplicates
            $('.apd-canvas-element').remove();
            
            templateData.elements.forEach(function(element) {
                createElement(element);
            });
        }
        
        // Load fill logo with color option
        if (templateData.fillLogoWithColor !== undefined) {
            $('#fill-logo-with-color').prop('checked', templateData.fillLogoWithColor);
        } else {
            // Default to false
            $('#fill-logo-with-color').prop('checked', false);
            templateData.fillLogoWithColor = false;
        }
    }
    
    function loadBackgroundSettings(backgroundData) {
        
        if (!backgroundData.type) {
            return;
        }
        
        // Clear existing background
        $('#apd-canvas').css({
            'background-color': '',
            'background-image': '',
            'background-size': '',
            'background-position': ''
        });
        
        // Set background type in dropdown
        $('#background-type').val(backgroundData.type);
        
        // Show relevant controls
        $('#bg-color-control, #bg-image-control, #bg-gradient-control').hide();
        
        if (backgroundData.type === 'color') {
            $('#bg-color-control').show();
            if (backgroundData.color) {
                $('#background-color').val(backgroundData.color);
                $('#apd-canvas').css('background-color', backgroundData.color);
            }
        } else if (backgroundData.type === 'image') {
            $('#bg-image-control').show();
            if (backgroundData.image) {
                // Apply background image
                $('#apd-canvas').css('background-image', 'url(' + backgroundData.image + ')');
                $('#apd-canvas').css('background-size', 'cover');
                $('#apd-canvas').css('background-position', 'center');
                
                // Show preview
                $('#bg-image-preview').html('<img src="' + backgroundData.image + '" style="max-width: 200px; max-height: 100px; border: 1px solid #ddd;">');
                
            }
        } else if (backgroundData.type === 'gradient') {
            // Add gradient controls if not exists
            if ($('#bg-gradient-control').length === 0) {
                $('#bg-image-control').after(`
                    <div id="bg-gradient-control" class="apd-form-group">
                        <label>Gradient Colors:</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="color" id="gradient-color-1" value="#ff0000">
                            <input type="color" id="gradient-color-2" value="#0000ff">
                        </div>
                        <label>Direction:</label>
                        <select id="gradient-direction">
                            <option value="to right">Left to Right</option>
                            <option value="to bottom">Top to Bottom</option>
                            <option value="45deg">Diagonal</option>
                        </select>
                    </div>
                `);
            }
            $('#bg-gradient-control').show();
            if (backgroundData.gradient) {
                const gradient = backgroundData.gradient;
                $('#gradient-color-1').val(gradient.color1);
                $('#gradient-color-2').val(gradient.color2);
                $('#gradient-direction').val(gradient.direction);
                
                const gradientCss = `linear-gradient(${gradient.direction}, ${gradient.color1}, ${gradient.color2})`;
                $('#apd-canvas').css('background-image', gradientCss);
            }
        }
        
        // Update template data to ensure consistency
        if (!templateData.canvas) {
            templateData.canvas = {};
        }
        if (!templateData.canvas.background) {
            templateData.canvas.background = {};
        }
        templateData.canvas.background = backgroundData;
    }
    
    // Undo system functions
    function saveState() {
        // Create a deep copy of current state
        const state = JSON.parse(JSON.stringify(templateData));
        
        // Remove states after current index (when user makes new changes after undo)
        if (undoIndex < undoHistory.length - 1) {
            undoHistory = undoHistory.slice(0, undoIndex + 1);
        }
        
        // Add new state
        undoHistory.push(state);
        undoIndex++;
        
        // Limit history size
        if (undoHistory.length > MAX_UNDO_HISTORY) {
            undoHistory.shift();
            undoIndex--;
        }
        
        // Update undo button state
        updateUndoButtonState();
        
        console.log('State saved, history length:', undoHistory.length, 'index:', undoIndex);
    }
    
    function undo() {
        if (undoIndex > 0) {
            undoIndex--;
            const previousState = undoHistory[undoIndex];
            
            // Restore state
            templateData = JSON.parse(JSON.stringify(previousState));
            
            // Rebuild canvas
            rebuildCanvas();
            
            // Update undo button state
            updateUndoButtonState();
            
            console.log('Undo performed, current index:', undoIndex);
        } else {
            console.log('No more undo history available');
        }
    }
    
    function updateUndoButtonState() {
        const undoBtn = $('#undo-btn');
        if (undoIndex > 0) {
            undoBtn.prop('disabled', false).removeClass('disabled');
        } else {
            undoBtn.prop('disabled', true).addClass('disabled');
        }
    }
    
    function rebuildCanvas() {
        // Clear canvas
        $('.apd-canvas-element').remove();
        
        // Restore canvas size
        if (templateData.canvas) {
            $('#apd-canvas').css({
                width: templateData.canvas.width + 'px',
                height: templateData.canvas.height + 'px'
            });
            
            $('#canvas-width').val(templateData.canvas.width);
            $('#canvas-height').val(templateData.canvas.height);
            
            // Restore background
            if (templateData.canvas.background) {
                loadBackgroundSettings(templateData.canvas.background);
            }
        }
        
        // Restore elements and update element counter
        if (templateData.elements) {
            // Update element counter to avoid ID conflicts
            templateData.elements.forEach(function(element) {
                createElement(element);
                // Extract counter from element ID
                const idMatch = element.id.match(/element_(\d+)/);
                if (idMatch) {
                    const elementNum = parseInt(idMatch[1]);
                    if (elementNum > elementCounter) {
                        elementCounter = elementNum;
                    }
                }
            });
        }
        
        // Restore fill logo with color option
        if (templateData.fillLogoWithColor !== undefined) {
            $('#fill-logo-with-color').prop('checked', templateData.fillLogoWithColor);
        } else {
            $('#fill-logo-with-color').prop('checked', false);
        }
        
        // Sync DOM with data to ensure consistency
        syncDOMWithData();
        
        // Update element counter
        updateElementCounter();
        
        // Clear selection
        selectedElement = null;
        $('.apd-canvas-element').removeClass('selected');
    }
    
    function syncDOMWithData() {
        // Remove DOM elements that don't exist in data
        $('.apd-canvas-element').each(function() {
            const elementId = $(this).data('id');
            const existsInData = templateData.elements.some(el => el.id === elementId);
            if (!existsInData) {
                $(this).remove();
            }
        });
        
        // Add data elements that don't exist in DOM
        if (templateData.elements) {
            templateData.elements.forEach(function(elementData) {
                const existsInDOM = $('.apd-canvas-element[data-id="' + elementData.id + '"]').length > 0;
                if (!existsInDOM) {
                    createElement(elementData);
                }
            });
        }
        
        console.log('DOM synced with data. Elements in data:', templateData.elements.length, 'Elements in DOM:', $('.apd-canvas-element').length);
    }
    
    function updateElementCounter() {
        const totalElements = templateData.elements ? templateData.elements.length : 0;
        $('#element-count').text(totalElements);
        
        // Also show breakdown by type
        if (templateData.elements && templateData.elements.length > 0) {
            const typeCounts = {};
            templateData.elements.forEach(function(element) {
                typeCounts[element.type] = (typeCounts[element.type] || 0) + 1;
            });
            
            let breakdown = '';
            Object.keys(typeCounts).forEach(function(type) {
                breakdown += type + ': ' + typeCounts[type] + ' ';
            });
            
            $('#element-counter').attr('title', breakdown.trim());
        }
    }
    
    function applyColorToElement(color, colorName) {
        if (!selectedElement) {
            console.log('No element selected for color application');
            return;
        }
        
        const element = selectedElement.element;
        const elementData = selectedElement.data;
        
        // Apply color based on element type
        if (elementData.type === 'text') {
            // For text elements, change the text color
            element.css('color', color);
            element.find('.element-label').css('color', color);
            
            // Update element data
            if (!elementData.properties) {
                elementData.properties = {};
            }
            elementData.properties.textColor = color;
            elementData.properties.textColorName = colorName;
            
            console.log('Applied text color:', color, 'to element:', elementData.label);
        } else if (elementData.type === 'logo') {
            // For logo elements, we'll store the color for later use
            if (!elementData.properties) {
                elementData.properties = {};
            }
            elementData.properties.logoColor = color;
            elementData.properties.logoColorName = colorName;
            
            // Visual feedback - change border color
            element.css('border-color', color);
            
            console.log('Applied logo color:', color, 'to element:', elementData.label);
        } else if (elementData.type === 'image') {
            // For image elements, we'll store the color for overlay effects
            if (!elementData.properties) {
                elementData.properties = {};
            }
            elementData.properties.imageColor = color;
            elementData.properties.imageColorName = colorName;
            
            // Visual feedback - change border color
            element.css('border-color', color);
            
            console.log('Applied image color:', color, 'to element:', elementData.label);
        }
        
        // Save state for undo
        saveState();
        
        // Update element counter
        updateElementCounter();
    }
    
    // Color palette functions
    function initColorPalette() {
        // Load colors from template data or use default
        if (templateData.colorPalette && templateData.colorPalette.length > 0) {
            colorPalette = templateData.colorPalette;
        } else {
            // Default colors
            colorPalette = [
                { color: '#000000', name: 'Black' },
                { color: '#FFFFFF', name: 'White' },
                { color: '#FF0000', name: 'Red' },
                { color: '#00FF00', name: 'Green' },
                { color: '#0000FF', name: 'Blue' },
                { color: '#FFFF00', name: 'Yellow' }
            ];
        }
        
        renderColorPalette();
    }
    
    function renderColorPalette() {
        const palette = $('#color-palette');
        palette.empty();
        
        colorPalette.forEach(function(colorData, index) {
            const colorItem = $(`
                <div class="apd-color-item" data-color="${colorData.color}" data-index="${index}" style="position:relative;">
                    <button type="button" class="apd-color-delete-btn" title="Delete" style="position:absolute;top:4px;right:4px;border:none;background:#dc3232;color:#fff;width:18px;height:18px;line-height:18px;border-radius:50%;font-size:12px;display:flex;align-items:center;justify-content:center;">×</button>
                    <div class="color-swatch" style="background-color: ${colorData.color}; ${colorData.color === '#FFFFFF' ? 'border: 1px solid #ddd;' : ''}"></div>
                    <span class="color-name">${colorData.name}</span>
                </div>
            `);
            
            // Click to select color for element
            colorItem.on('click', function() {
                selectColorForElement(colorData.color, colorData.name);
            });
            
            // Double click to edit color
            colorItem.on('dblclick', function() {
                editColor(index);
            });

            // Delete button click
            colorItem.find('.apd-color-delete-btn').on('click', function(e){
                e.stopPropagation();
                if (!confirm('Delete this color?')) return;
                colorPalette.splice(index, 1);
                templateData.colorPalette = colorPalette;
                renderColorPalette();
                saveState();
            });
            
            palette.append(colorItem);
        });
    }
    
    function selectColorForElement(color, colorName) {
        // Remove previous selection
        $('.apd-color-item').removeClass('selected');
        
        // Add selection to clicked color
        $(`.apd-color-item[data-color="${color}"]`).addClass('selected');
        
        // Apply color to selected element
        if (selectedElement) {
            applyColorToElement(color, colorName);
        }
        
        console.log('Color selected:', color, colorName);
    }
    
    function openColorPicker(colorIndex = null) {
        currentEditingColor = colorIndex;
        
        if (colorIndex !== null) {
            // Editing existing color
            const colorData = colorPalette[colorIndex];
            $('#color-picker-input').val(colorData.color);
            $('#color-preview').css('background-color', colorData.color);
            $('#color-name-input').val(colorData.name || '');
            $('.apd-modal-header h3').text('Edit Color');
            $('#delete-color-btn').show();
        } else {
            // Adding new color
            $('#color-picker-input').val('#FFFFFF');
            $('#color-preview').css('background-color', '#FFFFFF');
            $('#color-name-input').val('');
            $('.apd-modal-header h3').text('Add Color');
            $('#delete-color-btn').hide();
        }
        
        $('#color-picker-modal').show();
    }
    
    function saveColorFromPicker() {
        const color = $('#color-picker-input').val();
        let colorName = ($('#color-name-input').val() || '').trim();
        if (!colorName) {
            colorName = getColorName(color);
        }
        
        if (currentEditingColor !== null) {
            // Update existing color
            colorPalette[currentEditingColor] = { color: color, name: colorName };
        } else {
            // Add new color
            colorPalette.push({ color: color, name: colorName });
        }
        
        // Save to template data
        if (!templateData.colorPalette) {
            templateData.colorPalette = [];
        }
        templateData.colorPalette = colorPalette;
        
        // Re-render palette
        renderColorPalette();
        
        // Save state for undo
        saveState();
        
        // Close modal
        closeColorPicker();
        
        console.log('Color saved:', color, colorName);
    }
    
    function closeColorPicker() {
        $('#color-picker-modal').hide();
        currentEditingColor = null;
    }
    
    function editColor(index) {
        openColorPicker(index);
    }
    
    function getColorName(color) {
        // Simple color name mapping
        const colorNames = {
            '#000000': 'Black',
            '#FFFFFF': 'White',
            '#FF0000': 'Red',
            '#00FF00': 'Green',
            '#0000FF': 'Blue',
            '#FFFF00': 'Yellow',
            '#FF00FF': 'Magenta',
            '#00FFFF': 'Cyan',
            '#FFA500': 'Orange',
            '#800080': 'Purple',
            '#808080': 'Gray',
            '#8B4513': 'Brown'
        };
        
        return colorNames[color.toUpperCase()] || color.toUpperCase();
    }
    
    function addElement(type) {
        // Check if element already exists to prevent duplicates
        if (!templateData.elements) {
            templateData.elements = [];
        }
        
        // Allow multiple elements of the same type
        // No need to check for duplicates anymore
        
        // Generate unique ID
        const existingIds = templateData.elements.map(el => el.id);
        let newId;
        let counter = elementCounter;
        
        do {
            counter++;
            newId = 'element_' + counter;
        } while (existingIds.includes(newId));
        
        elementCounter = counter;
        
        // Count existing elements of the same type for naming
        const sameTypeElements = templateData.elements.filter(el => el.type === type);
        const elementNumber = sameTypeElements.length + 1;
        
        // Calculate position to avoid overlap
        const baseX = 50;
        const baseY = 50;
        const offsetX = 30;
        const offsetY = 30;
        const elementsPerRow = 3; // Maximum elements per row
        
        const row = Math.floor(templateData.elements.length / elementsPerRow);
        const col = templateData.elements.length % elementsPerRow;
        
        const element = {
            id: newId,
            type: type,
            x: baseX + (col * offsetX),
            y: baseY + (row * offsetY),
            width: type === 'text' ? 200 : 100,
            height: type === 'text' ? 40 : 100,
            label: type.charAt(0).toUpperCase() + type.slice(1) + ' ' + elementNumber,
            properties: {}
        };
        
        createElement(element);
        templateData.elements.push(element);
        
        // Save state for undo
        saveState();
        
        // Update element counter display
        updateElementCounter();
        
    }
    
    function createElement(elementData) {
        const element = $('<div class="apd-canvas-element" data-id="' + elementData.id + '" data-type="' + elementData.type + '">')
            .css({
                left: elementData.x + 'px',
                top: elementData.y + 'px',
                width: elementData.width + 'px',
                height: elementData.height + 'px'
            })
            .html(`
                <div class="element-label" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:flex-start;width:100%;height:100%;background:transparent;padding:0;">${elementData.label}</div>
                <div class="element-resize-handles">
                    <div class="resize-handle nw"></div>
                    <div class="resize-handle ne"></div>
                    <div class="resize-handle sw"></div>
                    <div class="resize-handle se"></div>
                </div>
            `);
        
        $('#apd-canvas').append(element);
        
        // Apply saved styles if exist
        if (elementData.properties) {
            const label = element.find('.element-label');
            if (elementData.type === 'text') {
                if (elementData.properties.textColor) {
                    label.css('color', elementData.properties.textColor);
                }
                if (elementData.properties.textAlign) {
                    const jc = elementData.properties.textAlign === 'left' ? 'flex-start' :
                               elementData.properties.textAlign === 'center' ? 'center' :
                               elementData.properties.textAlign === 'right' ? 'flex-end' : 'space-between';
                    label.css('justify-content', jc);
                }
                if (elementData.properties.fontFamily) {
                    label.css('font-family', elementData.properties.fontFamily);
                }
                if (elementData.properties.fontSize != null) {
                    label.css('font-size', elementData.properties.fontSize + 'px');
                }
                if (elementData.properties.fontStyle) {
                    label.css('font-style', elementData.properties.fontStyle);
                }
                if (elementData.properties.fontWeight) {
                    label.css('font-weight', elementData.properties.fontWeight);
                }
                if (elementData.properties.textDecoration) {
                    label.css('text-decoration', elementData.properties.textDecoration);
                }
                if (elementData.properties.textStrokeWidth != null) {
                    const strokeWidth = elementData.properties.textStrokeWidth;
                    if (strokeWidth > 0) {
                        label.css('-webkit-text-stroke-width', strokeWidth + 'px');
                        label.css('text-stroke-width', strokeWidth + 'px');
                    } else {
                        label.css('-webkit-text-stroke-width', '0px');
                        label.css('text-stroke-width', '0px');
                    }
                }
                if (elementData.properties.textStrokeColor) {
                    label.css('-webkit-text-stroke-color', elementData.properties.textStrokeColor);
                    label.css('text-stroke-color', elementData.properties.textStrokeColor);
                }
            } else if (elementData.type === 'logo') {
                if (elementData.properties.logoColor) {
                    element.css('border-color', elementData.properties.logoColor);
                }
                if (elementData.properties.logoStrokeWidth != null) {
                    const strokeWidth = elementData.properties.logoStrokeWidth;
                    if (strokeWidth > 0) {
                        element.css('border-width', strokeWidth + 'px');
                        element.css('border-style', 'solid');
                    } else {
                        element.css('border-width', '2px');
                        element.css('border-style', 'dashed');
                    }
                }
                if (elementData.properties.logoStrokeColor) {
                    element.css('border-color', elementData.properties.logoStrokeColor);
                }
            } else if (elementData.type === 'image' && elementData.properties.imageColor) {
                element.css('border-color', elementData.properties.imageColor);
            }
        }
        
        // Make draggable and resizable
        makeElementDraggable(element, elementData);
        makeElementResizable(element, elementData);
        
        // Click to select
        element.on('click', function(e) {
            e.stopPropagation();
            selectElement($(this), elementData);
        });
    }
    
    function makeElementDraggable(element, elementData) {
        let isDragging = false;
        let startX, startY;
        
        element.on('mousedown', function(e) {
            if (e.target.classList.contains('element-resize')) {
                return;
            }
            isDragging = true;
            startX = e.clientX - element.position().left;
            startY = e.clientY - element.position().top;
            e.preventDefault();
        });
        
        $(document).on('mousemove', function(e) {
            if (isDragging) {
                const newX = e.clientX - startX;
                const newY = e.clientY - startY;
                element.css({
                    left: Math.max(0, Math.min(newX, $('#apd-canvas').width() - element.width())) + 'px',
                    top: Math.max(0, Math.min(newY, $('#apd-canvas').height() - element.height())) + 'px'
                });
            }
        });
        
        $(document).on('mouseup', function() {
            if (isDragging) {
                updateElementData(element, elementData);
                // Save state for undo after drag
                saveState();
            }
            isDragging = false;
        });
    }
    
    function selectElement(element, elementData) {
        // Remove all selections first
        $('.apd-canvas-element').removeClass('selected');
        
        // Add selection to clicked element
        element.addClass('selected');
        selectedElement = { element: element, data: elementData };
        
        $('#selected-element-name').text(elementData.label);
        showElementProperties(elementData);
        
        // Highlight current color in palette
        highlightCurrentColor(elementData);
    }
    
    function highlightCurrentColor(elementData) {
        // Clear all color selections
        $('.apd-color-item').removeClass('selected');
        
        // Find and highlight current color
        if (elementData.properties) {
            const currentColor = elementData.properties.textColor || 
                                elementData.properties.logoColor || 
                                elementData.properties.imageColor;
            
            if (currentColor) {
                const colorItem = $(`.apd-color-item[data-color="${currentColor}"]`);
                if (colorItem.length > 0) {
                    colorItem.addClass('selected');
                }
            }
        }
    }
    
    function showElementProperties(elementData) {
        let html = '<div class="apd-property-group">';
        html += '<h4>Element Settings</h4>';
        html += '<div class="apd-property"><label>Label</label><input type="text" id="prop-label" value="' + elementData.label + '"></div>';
        // Note field (admin hint rendered next to label on client)
        const currentNote = (elementData.note || (elementData.properties && elementData.properties.note) || '');
        html += '<div class="apd-property"><label>Note</label><input type="text" id="prop-note" value="' + currentNote + '" placeholder="Helper text shown next to label"></div>';
        
        // Add prefix and suffix fields for text elements
        if (elementData.type === 'text') {
            const p = elementData.properties || {};
            const currentPrefix = p.prefix || '';
            const currentSuffix = p.suffix || '';
            const currentMaxLength = p.maxLength || '';
            
            html += '<div class="apd-property"><label>Prefix</label><input type="text" id="prop-prefix" value="' + currentPrefix + '" placeholder="Text before user input"></div>';
            html += '<div class="apd-property"><label>Suffix</label><input type="text" id="prop-suffix" value="' + currentSuffix + '" placeholder="Text after user input"></div>';
            html += '<div class="apd-property"><label>Max Length</label><input type="number" id="prop-max-length" value="' + currentMaxLength + '" placeholder="Maximum characters (optional)" min="1" max="1000"></div>';
        }
        
        html += '</div>';
        
        html += '<div class="apd-property-group">';
        html += '<h4>Position & Size</h4>';
        html += '<div class="apd-property"><label>X Position</label><input type="number" id="prop-x" value="' + elementData.x + '"></div>';
        html += '<div class="apd-property"><label>Y Position</label><input type="number" id="prop-y" value="' + elementData.y + '"></div>';
        html += '<div class="apd-property"><label>Width</label><input type="number" id="prop-width" value="' + elementData.width + '"></div>';
        html += '<div class="apd-property"><label>Height</label><input type="number" id="prop-height" value="' + elementData.height + '"></div>';
        html += '</div>';
        
        // Typography controls for text elements
        if (elementData.type === 'text') {
            const p = elementData.properties || {};
            const currentAlign = p.textAlign || 'left';
            const currentFont = p.fontFamily || 'Arial, sans-serif';
            const currentSize = (p.fontSize != null ? p.fontSize : 36);
            const isItalic = p.fontStyle === 'italic';
            const currentWeight = p.fontWeight || '700';
            const isUnderline = (p.textDecoration === 'underline');
            const strokeWidth = (p.textStrokeWidth != null ? p.textStrokeWidth : 0);
            const strokeColor = p.textStrokeColor || '#000000';
            const currentPrefix = p.prefix || '';
            const currentSuffix = p.suffix || '';

            html += '<div class="apd-property-group">';
            html += '<h4>Typography</h4>';
            // Alignment with icons
            html += '<div class="apd-property">'
                 +  '<label>Align</label>'
                 +  '<div id="prop-align" style="display:flex;gap:6px">'
                 +    '<button type="button" class="button apd-align" data-align="left"><span class="dashicons dashicons-editor-alignleft"></span></button>'
                 +    '<button type="button" class="button apd-align" data-align="center"><span class="dashicons dashicons-editor-aligncenter"></span></button>'
                 +    '<button type="button" class="button apd-align" data-align="right"><span class="dashicons dashicons-editor-alignright"></span></button>'
                 +    '<button type="button" class="button apd-align" data-align="justify"><span class="dashicons dashicons-editor-justify"></span></button>'
                 +  '</div>'
                 + '</div>';
            // Font family - populate from available fonts
            html += '<div class="apd-property"><label>Font Family</label>'
                 +  '<select id="prop-font-family">';
            
            // Add default fonts
            const defaultFonts = [
                { value: 'Arial, sans-serif', name: 'Arial' },
                { value: 'Helvetica, Arial, sans-serif', name: 'Helvetica' },
                { value: 'Roboto, Arial, sans-serif', name: 'Roboto' },
                { value: 'Open Sans, Arial, sans-serif', name: 'Open Sans' },
                { value: 'Montserrat, Arial, sans-serif', name: 'Montserrat' },
                { value: 'Times New Roman, serif', name: 'Times New Roman' },
                { value: 'Georgia, serif', name: 'Georgia' },
                { value: 'Courier New, monospace', name: 'Courier New' }
            ];
            
            // Add default fonts to dropdown
            defaultFonts.forEach(font => {
                const selected = (currentFont === font.value) ? ' selected' : '';
                html += `<option value="${font.value}"${selected}>${font.name}</option>`;
            });
            
            // Add uploaded fonts from Settings (stored in database)
            if (window.apdUploadedFonts && Array.isArray(window.apdUploadedFonts)) {
                window.apdUploadedFonts.forEach(font => {
                    if (font.family && font.name) {
                        const selected = (currentFont === font.family) ? ' selected' : '';
                        html += `<option value="${font.family}"${selected}>${font.name}</option>`;
                    }
                });
            }
            
            html += '</select></div>';
            // Font size
            html += '<div class="apd-property"><label>Font Size (px)</label><input type="number" id="prop-font-size" value="' + currentSize + '"></div>';
            // Decorations
            html += '<div class="apd-property"><label>Decoration</label>'
                 +  '<div style="display:flex;gap:6px;align-items:center">'
                 +    '<button type="button" class="button apd-deco" data-deco="italic" title="Italic"><span class="dashicons dashicons-editor-italic"></span></button>'
                 +    '<button type="button" class="button apd-deco" data-deco="bold" title="Bold"><span class="dashicons dashicons-editor-bold"></span></button>'
                 +    '<button type="button" class="button apd-deco" data-deco="underline" title="Underline"><span class="dashicons dashicons-editor-underline"></span></button>'
                 +  '</div>'
                 + '</div>';
            // Font Weight
            html += '<div class="apd-property"><label>Font Weight</label>'
                 +  '<select id="prop-font-weight">'
                 +    '<option value="400">400</option>'
                 +    '<option value="500">500</option>'
                 +    '<option value="600">600</option>'
                 +    '<option value="700" selected>700</option>'
                 +    '<option value="800">800</option>'
                 +    '<option value="900">900</option>'
                 +  '</select>'
                 + '</div>';
            
            // Stroke Width Controls
            html += '<div class="apd-property-group">';
            html += '<h4>Text Stroke</h4>';
            html += '<div class="apd-property"><label>Stroke Width (px)</label><input type="number" id="prop-stroke-width" value="' + strokeWidth + '" min="0" max="20" step="0.5"></div>';
            html += '<div class="apd-property"><label>Stroke Color</label><input type="color" id="prop-stroke-color" value="' + strokeColor + '"></div>';
            html += '</div>';
            
            html += '</div>';

            // Initialize selections
            setTimeout(function(){
                $('#prop-font-family').val(currentFont);
                $('#prop-font-weight').val(String(currentWeight));
                $('#prop-align .apd-align').removeClass('button-primary');
                $('#prop-align .apd-align[data-align="'+currentAlign+'"]').addClass('button-primary');
                if (isItalic) $('.apd-deco[data-deco="italic"]').addClass('button-primary');
                if (parseInt(currentWeight) >= 700) $('.apd-deco[data-deco="bold"]').addClass('button-primary');
                if (isUnderline) $('.apd-deco[data-deco="underline"]').addClass('button-primary');
                // Ensure styles reflect initial state after render
                var $label = selectedElement && selectedElement.element ? selectedElement.element.find('.element-label') : null;
                if ($label && $label.length) {
                    if (isItalic) { $label.css('font-style', 'italic'); }
                }
            },0);
        }

        // Add color properties based on element type
        if (elementData.type === 'text' || elementData.type === 'logo' || elementData.type === 'image') {
            html += '<div class="apd-property-group">';
            html += '<h4>Color Settings</h4>';
            
            const currentColor = elementData.properties ? 
                (elementData.properties.textColor || elementData.properties.logoColor || elementData.properties.imageColor) : 
                '#000000';
            const currentColorName = elementData.properties ? 
                (elementData.properties.textColorName || elementData.properties.logoColorName || elementData.properties.imageColorName) : 
                'Black';
            
            html += '<div class="apd-property">';
            html += '<label>Current Color</label>';
            html += '<div style="display: flex; align-items: center; gap: 10px;">';
            html += '<div style="width: 30px; height: 30px; background-color: ' + currentColor + '; border: 1px solid #ddd; border-radius: 4px;"></div>';
            html += '<span>' + currentColorName + '</span>';
            html += '</div>';
            html += '<p style="font-size: 11px; color: #666; margin: 5px 0 0 0;">Select a color from the palette above</p>';
            html += '</div>';
            html += '</div>';
        }
        
        // Add stroke width controls for logo elements
        if (elementData.type === 'logo') {
            const p = elementData.properties || {};
            const logoStrokeWidth = (p.logoStrokeWidth != null ? p.logoStrokeWidth : 0);
            const logoStrokeColor = p.logoStrokeColor || '#000000';
            
            html += '<div class="apd-property-group">';
            html += '<h4>Logo Stroke</h4>';
            html += '<div class="apd-property"><label>Stroke Width (px)</label><input type="number" id="prop-logo-stroke-width" value="' + logoStrokeWidth + '" min="0" max="20" step="0.5"></div>';
            html += '<div class="apd-property"><label>Stroke Color</label><input type="color" id="prop-logo-stroke-color" value="' + logoStrokeColor + '"></div>';
            html += '</div>';
        }
        
        html += '<div class="apd-property-group">';
        html += '<button type="button" class="apd-delete-element">Delete Element</button>';
        html += '</div>';
        
        $('#element-properties').html(html);
        
        // Bind property change events
        bindPropertyEvents(elementData);
    }
    
    function bindPropertyEvents(elementData) {
        // Label change
        $('#prop-label').on('change blur', function() {
            const newLabel = $(this).val();
            elementData.label = newLabel;
            selectedElement.element.find('.element-label').text(newLabel);
            $('#selected-element-name').text(newLabel);
        });
        // Note change (store both top-level and in properties for compatibility)
        $('#prop-note').on('change blur', function(){
            const newNote = $(this).val();
            elementData.note = newNote;
            if (!elementData.properties) elementData.properties = {};
            elementData.properties.note = newNote;
            saveState();
        });
        
        // Prefix and suffix changes for text elements
        if (elementData.type === 'text') {
            $('#prop-prefix').on('change blur', function() {
                const newPrefix = $(this).val();
                if (!elementData.properties) elementData.properties = {};
                elementData.properties.prefix = newPrefix;
                saveState();
            });
            
            $('#prop-suffix').on('change blur', function() {
                const newSuffix = $(this).val();
                if (!elementData.properties) elementData.properties = {};
                elementData.properties.suffix = newSuffix;
                saveState();
            });
            
            // Character limit changes
            $('#prop-max-length').on('change blur', function() {
                const newMaxLength = $(this).val();
                if (!elementData.properties) elementData.properties = {};
                elementData.properties.maxLength = newMaxLength ? parseInt(newMaxLength) : null;
                saveState();
            });
        }
        
        // Position and size changes
        $('#prop-x, #prop-y, #prop-width, #prop-height').on('change blur', function() {
            const x = parseInt($('#prop-x').val()) || 0;
            const y = parseInt($('#prop-y').val()) || 0;
            const width = parseInt($('#prop-width').val()) || 50;
            const height = parseInt($('#prop-height').val()) || 30;
            
            selectedElement.element.css({
                left: x + 'px',
                top: y + 'px',
                width: width + 'px',
                height: height + 'px'
            });
            
            elementData.x = x;
            elementData.y = y;
            elementData.width = width;
            elementData.height = height;
        });

        // Typography bindings for text elements
        if (elementData.type === 'text') {
            if (!elementData.properties) elementData.properties = {};
            
            // Remove any existing handlers to prevent duplicates
            $('#element-properties').off('click.apd-align click.apd-deco');
            $('#prop-font-family, #prop-font-size, #prop-font-weight').off('change.apd blur.apd input.apd');
            
            // Align
            $('#element-properties').on('click.apd-align', '.apd-align', function(){
                const align = $(this).data('align');
                $('#prop-align .apd-align').removeClass('button-primary');
                $(this).addClass('button-primary');
                const label = selectedElement.element.find('.element-label');
                const jc = align === 'left' ? 'flex-start' : align === 'center' ? 'center' : align === 'right' ? 'flex-end' : 'space-between';
                label.css('justify-content', jc);
                elementData.properties.textAlign = align;
                saveState();
            });
            // Font family
            $('#prop-font-family').on('change.apd blur.apd', function(){
                const ff = $(this).val();
                const label = selectedElement.element.find('.element-label');
                label.css('font-family', ff);
                elementData.properties.fontFamily = ff;
                saveState();
            });
            // Font size
            $('#prop-font-size').on('change.apd input.apd blur.apd', function(){
                const fs = parseInt($(this).val()) || 12;
                const label = selectedElement.element.find('.element-label');
                label.css('font-size', fs + 'px');
                elementData.properties.fontSize = fs;
                saveState();
            });
            // Decorations
            $('#element-properties').on('click.apd-deco', '.apd-deco', function(){
                const deco = $(this).data('deco');
                $(this).toggleClass('button-primary');
                if (deco === 'italic') {
                    const on = $(this).hasClass('button-primary');
                    const label = selectedElement.element.find('.element-label');
                    label.css('font-style', on ? 'italic' : 'normal');
                    elementData.properties.fontStyle = on ? 'italic' : 'normal';
                } else if (deco === 'bold') {
                    const on = $(this).hasClass('button-primary');
                    const label = selectedElement.element.find('.element-label');
                    const currentWeight = on ? '700' : '400';
                    label.css('font-weight', currentWeight);
                    elementData.properties.fontWeight = currentWeight;
                    $('#prop-font-weight').val(currentWeight);
                } else if (deco === 'underline') {
                    const on = $(this).hasClass('button-primary');
                    const label = selectedElement.element.find('.element-label');
                    label.css('text-decoration', on ? 'underline' : 'none');
                    elementData.properties.textDecoration = on ? 'underline' : 'none';
                }
                saveState();
            });
            // Weight
            $('#prop-font-weight').on('change.apd blur.apd', function(){
                const fw = $(this).val();
                const label = selectedElement.element.find('.element-label');
                label.css('font-weight', fw);
                elementData.properties.fontWeight = fw;
                saveState();
            });
            
            // Stroke Width
            $('#prop-stroke-width').on('change.apd input.apd blur.apd', function(){
                const strokeWidth = parseFloat($(this).val()) || 0;
                const label = selectedElement.element.find('.element-label');
                if (strokeWidth > 0) {
                    label.css('-webkit-text-stroke-width', strokeWidth + 'px');
                    label.css('text-stroke-width', strokeWidth + 'px');
                } else {
                    label.css('-webkit-text-stroke-width', '0px');
                    label.css('text-stroke-width', '0px');
                }
                elementData.properties.textStrokeWidth = strokeWidth;
                saveState();
            });
            
            // Stroke Color
            $('#prop-stroke-color').on('change.apd blur.apd', function(){
                const strokeColor = $(this).val();
                const label = selectedElement.element.find('.element-label');
                label.css('-webkit-text-stroke-color', strokeColor);
                label.css('text-stroke-color', strokeColor);
                elementData.properties.textStrokeColor = strokeColor;
                saveState();
            });
        }
        
        // Logo stroke width controls
        if (elementData.type === 'logo') {
            // Logo Stroke Width
            $('#prop-logo-stroke-width').on('change.apd input.apd blur.apd', function(){
                const strokeWidth = parseFloat($(this).val()) || 0;
                const element = selectedElement.element;
                if (strokeWidth > 0) {
                    element.css('border-width', strokeWidth + 'px');
                    element.css('border-style', 'solid');
                } else {
                    element.css('border-width', '2px');
                    element.css('border-style', 'dashed');
                }
                elementData.properties.logoStrokeWidth = strokeWidth;
                saveState();
            });
            
            // Logo Stroke Color
            $('#prop-logo-stroke-color').on('change.apd blur.apd', function(){
                const strokeColor = $(this).val();
                const element = selectedElement.element;
                element.css('border-color', strokeColor);
                elementData.properties.logoStrokeColor = strokeColor;
                saveState();
            });
        }
        
        $('.apd-delete-element').on('click', function() {
            if (confirm('Are you sure you want to delete this element?')) {
                selectedElement.element.remove();
                templateData.elements = templateData.elements.filter(function(el) {
                    return el.id !== elementData.id;
                });
                $('#element-properties').html('<p class="apd-no-selection">Select an element to edit its properties</p>');
                $('#selected-element-name').text('No element selected');
                selectedElement = null;
                
                // Save state for undo after delete
                saveState();
                
                // Update element counter
                updateElementCounter();
            }
        });
    }
    
    function makeElementResizable(element, elementData) {
        element.find('.resize-handle').on('mousedown', function(e) {
                e.preventDefault();
            e.stopPropagation();
            
            const handle = $(this);
            const isResizing = true;
            const startX = e.clientX;
            const startY = e.clientY;
            const startWidth = element.width();
            const startHeight = element.height();
            const startLeft = element.position().left;
            const startTop = element.position().top;
            
            $(document).on('mousemove.resizing', function(e) {
                const deltaX = e.clientX - startX;
                const deltaY = e.clientY - startY;
                
                if (handle.hasClass('se')) {
                    // Southeast handle
                    element.css({
                        width: Math.max(50, startWidth + deltaX) + 'px',
                        height: Math.max(30, startHeight + deltaY) + 'px'
                    });
                } else if (handle.hasClass('sw')) {
                    // Southwest handle
                    element.css({
                        left: (startLeft + deltaX) + 'px',
                        width: Math.max(50, startWidth - deltaX) + 'px',
                        height: Math.max(30, startHeight + deltaY) + 'px'
                    });
                } else if (handle.hasClass('ne')) {
                    // Northeast handle
                    element.css({
                        top: (startTop + deltaY) + 'px',
                        width: Math.max(50, startWidth + deltaX) + 'px',
                        height: Math.max(30, startHeight - deltaY) + 'px'
                    });
                } else if (handle.hasClass('nw')) {
                    // Northwest handle
                    element.css({
                        left: (startLeft + deltaX) + 'px',
                        top: (startTop + deltaY) + 'px',
                        width: Math.max(50, startWidth - deltaX) + 'px',
                        height: Math.max(30, startHeight - deltaY) + 'px'
                    });
                }
            });
            
            $(document).on('mouseup.resizing', function() {
                $(document).off('mousemove.resizing mouseup.resizing');
                updateElementData(element, elementData);
                // Save state for undo after resize
                saveState();
            });
        });
    }
    
    function updateElementData(element, elementData) {
        const position = element.position();
        elementData.x = position.left;
        elementData.y = position.top;
        elementData.width = element.width();
        elementData.height = element.height();
    }
    
    function saveTemplate() {
        // Prevent duplicate save
        if (window.apdSaving) {
            console.log('Save already in progress, ignoring duplicate call');
            return;
        }
        window.apdSaving = true;
        
        const templateId = $('#template-id').val();
        
        // Validate template data before sending
        try {
            const jsonData = JSON.stringify(templateData);
            console.log('Saving template data:', {
                templateId: templateId,
                dataSize: jsonData.length,
                hasBackground: !!templateData.canvas?.background,
                backgroundType: templateData.canvas?.background?.type
            });
            
            // Check if data is too large (limit to 1MB)
            if (jsonData.length > 1024 * 1024) {
                alert('Template data is too large. Please reduce the size of background images or other elements.');
                return;
            }
        } catch (error) {
            console.error('Error serializing template data:', error);
            alert('Error preparing template data: ' + error.message);
            return;
        }
        
        $.ajax({
            url: apd_designer.ajax_url,
            type: 'POST',
            data: {
                action: 'save_template_design',
                template_id: templateId,
                template_data: JSON.stringify(templateData),
                nonce: apd_designer.nonce
            },
            success: function(response) {
                window.apdSaving = false; // Reset flag
                if (response.success) {
                    alert('Template saved successfully!');
                } else {
                    console.error('Save template error:', response);
                    alert('Error saving template: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                window.apdSaving = false; // Reset flag
                console.error('AJAX error:', {xhr, status, error});
                alert('Error saving template. Please try again.');
            }
        });
    }
    
    // Click on canvas to deselect
    $('#apd-canvas').on('click', function(e) {
        if (e.target === this) {
            $('.apd-canvas-element').removeClass('selected');
            $('#element-properties').html('<p class="apd-no-selection">Select an element to edit its properties</p>');
            $('#selected-element-name').text('No element selected');
            selectedElement = null;
        }
    });
    
    // Canvas control functions
    function updateCanvasSize() {
        let width = parseInt($('#canvas-width').val()) || 800;
        let height = parseInt($('#canvas-height').val()) || 600;
        
        // Validate and limit canvas size
        const maxWidth = 2000;
        const maxHeight = 2000;
        const minWidth = 100;
        const minHeight = 100;
        
        // Clamp values to reasonable limits
        width = Math.max(minWidth, Math.min(width, maxWidth));
        height = Math.max(minHeight, Math.min(height, maxHeight));
        
        // Update input fields with clamped values
        $('#canvas-width').val(width);
        $('#canvas-height').val(height);
        
        $('#apd-canvas').css({
            width: width + 'px',
            height: height + 'px'
        });
        
        // Update template data
        if (!templateData.canvas) {
            templateData.canvas = {};
        }
        templateData.canvas.width = width;
        templateData.canvas.height = height;
        
        // Save state for undo
        saveState();
        
        // Show warning if values were clamped
        if (parseInt($('#canvas-width').val()) !== width || parseInt($('#canvas-height').val()) !== height) {
            alert('Canvas size has been limited to reasonable values (100-2000px)');
        }
    }
    
    function updateBackgroundType() {
        const type = $('#background-type').val();
        
        // Clear all background styles first
        $('#apd-canvas').css({
            'background-color': '',
            'background-image': '',
            'background-size': '',
            'background-position': ''
        });
        
        // Clear background preview
        $('#bg-image-preview').empty();
        
        // Hide all background controls
        $('#bg-color-control, #bg-image-control, #bg-gradient-control').hide();
        
        // Show relevant control and apply default values
        if (type === 'color') {
            $('#bg-color-control').show();
            // Set default color if not already set
            const currentColor = $('#background-color').val() || '#ffffff';
            $('#apd-canvas').css('background-color', currentColor);
        } else if (type === 'image') {
            $('#bg-image-control').show();
            // Don't apply any background image by default
        } else if (type === 'gradient') {
            // Add gradient controls if not exists
            if ($('#bg-gradient-control').length === 0) {
                $('#bg-image-control').after(`
                    <div id="bg-gradient-control" class="apd-form-group">
                        <label>Gradient Colors:</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="color" id="gradient-color-1" value="#ff0000">
                            <input type="color" id="gradient-color-2" value="#0000ff">
                        </div>
                        <label>Direction:</label>
                        <select id="gradient-direction">
                            <option value="to right">Left to Right</option>
                            <option value="to bottom">Top to Bottom</option>
                            <option value="45deg">Diagonal</option>
                        </select>
                    </div>
                `);
            }
            $('#bg-gradient-control').show();
            // Apply default gradient
            const color1 = $('#gradient-color-1').val() || '#ff0000';
            const color2 = $('#gradient-color-2').val() || '#0000ff';
            const direction = $('#gradient-direction').val() || 'to right';
            const gradientCss = `linear-gradient(${direction}, ${color1}, ${color2})`;
            $('#apd-canvas').css('background-image', gradientCss);
        }
        
        // Update template data
        if (!templateData.canvas) {
            templateData.canvas = {};
        }
        if (!templateData.canvas.background) {
            templateData.canvas.background = {};
        }
        
        // Clear previous background data and set new type
        templateData.canvas.background = { type: type };
        
        // Save state for undo
        saveState();
        
        console.log('Background type changed to:', type);
    }
    
    function updateBackgroundColor() {
        const color = $('#background-color').val();
        
        // Clear other background types first
        $('#apd-canvas').css({
            'background-image': '',
            'background-size': '',
            'background-position': ''
        });
        
        // Apply color
        $('#apd-canvas').css('background-color', color);
        
        // Update template data
        if (!templateData.canvas) {
            templateData.canvas = {};
        }
        if (!templateData.canvas.background) {
            templateData.canvas.background = {};
        }
        templateData.canvas.background.type = 'color';
        templateData.canvas.background.color = color;
        
        // Save state for undo
        saveState();
    }
    
    function handleBackgroundImageUpload(file) {
        if (!file) return;
        
        // Check file type
        if (!file.type.startsWith('image/')) {
            alert('Please select a valid image file');
            return;
        }
        
        // Check file size (limit to 2MB)
        if (file.size > 2 * 1024 * 1024) {
            alert('Image file is too large. Please select an image smaller than 2MB.');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const imageUrl = e.target.result;
            
            // Clear other background types first
            $('#apd-canvas').css({
                'background-color': '',
                'background-image': '',
                'background-size': '',
                'background-position': ''
            });
            
            // Update canvas background
            $('#apd-canvas').css('background-image', 'url(' + imageUrl + ')');
            $('#apd-canvas').css('background-size', 'cover');
            $('#apd-canvas').css('background-position', 'center');
            
            // Show preview
            $('#bg-image-preview').html('<img src="' + imageUrl + '" style="max-width: 200px; max-height: 100px; border: 1px solid #ddd;">');
            
            // Update template data
            if (!templateData.canvas) {
                templateData.canvas = {};
            }
            if (!templateData.canvas.background) {
                templateData.canvas.background = {};
            }
            templateData.canvas.background.type = 'image';
            templateData.canvas.background.image = imageUrl;
            
            // Save state for undo
            saveState();
            
            console.log('Background image updated:', {
                type: 'image',
                size: file.size,
                dataLength: imageUrl.length
            });
        };
        reader.readAsDataURL(file);
    }
    
    
    function updateElementProperty(property, value) {
        if (!selectedElement || !selectedElement.element) return;
        
        const elementData = selectedElement.element.data('element-data');
        if (!elementData) return;
        
        if (!elementData.properties) {
            elementData.properties = {};
        }
        
        elementData.properties[property] = value;
        
        // Apply the change to the visual element
        const label = selectedElement.element.find('.element-label');
        if (label.length) {
            if (property === 'fontFamily') {
                label.css('font-family', value);
            } else if (property === 'fontSize') {
                label.css('font-size', value + 'px');
            } else if (property === 'textColor') {
                label.css('color', value);
            } else if (property === 'fontWeight') {
                label.css('font-weight', value);
            } else if (property === 'fontStyle') {
                label.css('font-style', value);
            } else if (property === 'textAlign') {
                label.css('text-align', value);
            }
        }
        
        // Update the properties panel if it's open
        if (property === 'fontFamily' && $('#prop-font-family').length) {
            $('#prop-font-family').val(value);
        }
        
        // Save state for undo
        saveState();
    }
    
    
    function updateGradientBackground() {
        const color1 = $('#gradient-color-1').val();
        const color2 = $('#gradient-color-2').val();
        const direction = $('#gradient-direction').val();
        
        // Clear other background types first
        $('#apd-canvas').css({
            'background-color': '',
            'background-image': '',
            'background-size': '',
            'background-position': ''
        });
        
        // Apply gradient
        const gradient = `linear-gradient(${direction}, ${color1}, ${color2})`;
        $('#apd-canvas').css('background-image', gradient);
        
        // Update template data
        if (!templateData.canvas) {
            templateData.canvas = {};
        }
        if (!templateData.canvas.background) {
            templateData.canvas.background = {};
        }
        templateData.canvas.background.type = 'gradient';
        templateData.canvas.background.gradient = {
            color1: color1,
            color2: color2,
            direction: direction
        };
        
        // Save state for undo
        saveState();
    }
});