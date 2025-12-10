/**
 * Product Variants JavaScript
 * Handles material swatch selection, size dropdown, price updates, and customizer redirection
 */

jQuery(document).ready(function($) {
    let selectedMaterial = null;
    let selectedSize = null;
    let productId = null;
    
    // Wait a bit for page to fully render
    setTimeout(function() {
        // Check if we're on a product detail page with variants
        if (typeof apdCombinations === 'undefined' || !apdCombinations || apdCombinations.length === 0) {
            console.log('[APD Variants] No variant combinations found');
            return;
        }
        
        console.log('[APD Variants] Initializing with', apdCombinations.length, 'combinations');
        console.log('[APD Variants] Combinations data:', apdCombinations);
        
        // Get product ID from page
        productId = $('.apd-product-gallery').data('product-id') || 
                    $('.apd-product-info').data('product-id') ||
                    window.apdProductId;
        
        initializeVariantHandlers();
    }, 300);
    
    function initializeVariantHandlers() {
    
    // Material button click
    $('.apd-material-btn').on('click', function() {
        $('.apd-material-btn').removeClass('selected');
        $(this).addClass('selected');
        selectedMaterial = String($(this).data('material-id'));
        
        console.log('[APD Variants] Material selected:', selectedMaterial);
        updateVariantInfo();
    });
    
    // Size dropdown change
    $('#apd-size-select').on('change', function() {
        selectedSize = $(this).val();
        
        console.log('[APD Variants] Size selected:', selectedSize);
        updateVariantInfo();
    });
    
    // Initialize with first selections
    function initializeDefaults() {
        // Get first material (if material options exist)
        const $firstBtn = $('.apd-material-btn').first();
        if ($firstBtn.length) {
            $firstBtn.addClass('selected');
            selectedMaterial = String($firstBtn.data('material-id'));
            console.log('[APD Variants] First material:', selectedMaterial);
        } else {
            // No material options - set to empty string for size-only variants
            selectedMaterial = '';
            console.log('[APD Variants] No material options (size-only product)');
        }
        
        // Get first size (if size options exist)
        const $sizeSelect = $('#apd-size-select');
        if ($sizeSelect.length && $sizeSelect.find('option').length > 0) {
            const $firstOption = $sizeSelect.find('option:first');
            selectedSize = $firstOption.val();
            $sizeSelect.val(selectedSize); // Explicitly set the select value
            console.log('[APD Variants] First size:', selectedSize);
        } else {
            // No size options - set to empty string for material-only variants
            selectedSize = '';
            console.log('[APD Variants] No size options (material-only product)');
        }
        
        console.log('[APD Variants] Initialized with:', {material: selectedMaterial, size: selectedSize});
        console.log('[APD Variants] Available combinations:', apdCombinations);
        
        // Always try to update
        updateVariantInfo();
    }
    
    // Update price and button based on selection
    function updateVariantInfo() {
        // Check if we have at least one dimension selected (size OR material)
        const hasMaterialOptions = $('.apd-material-btn').length > 0;
        const hasSizeOptions = $('#apd-size-select').length > 0;
        
        // Validate: must have selection for each available dimension
        if ((hasMaterialOptions && (selectedMaterial === null || selectedMaterial === undefined)) ||
            (hasSizeOptions && !selectedSize)) {
            console.log('[APD Variants] Incomplete selection:', {material: selectedMaterial, size: selectedSize});
            // Show first combination as fallback
            if (apdCombinations && apdCombinations.length > 0) {
                const firstCombo = apdCombinations[0];
                const displayPrice = firstCombo.sale_price && parseFloat(firstCombo.sale_price) > 0 
                    ? parseFloat(firstCombo.sale_price)
                    : parseFloat(firstCombo.price);
                $('#apd-price-display').text(displayPrice.toFixed(2));
            }
            return;
        }
        
        // Debug: log all combinations to see structure
        console.log('[APD Variants] All combinations:', apdCombinations);
        console.log('[APD Variants] Looking for combo:', {size: selectedSize, material: selectedMaterial});
        
        // Find matching combination - handle empty strings for size-only or material-only
        const combo = apdCombinations.find(c => {
            const sizeMatch = String(c.size || '') === String(selectedSize || '');
            const materialMatch = String(c.material || '') === String(selectedMaterial || '');
            console.log('[APD Variants] Checking combo:', c, 'size match:', sizeMatch, 'material match:', materialMatch);
            return sizeMatch && materialMatch;
        });
        
        console.log('[APD Variants] Found combo:', combo);
        
        if (combo) {
            // Update price display
            const displayPrice = combo.sale_price && parseFloat(combo.sale_price) > 0 
                ? parseFloat(combo.sale_price)
                : parseFloat(combo.price);
            
            $('#apd-price-display').text(displayPrice.toFixed(2));
            
            // Show/hide sale price styling
            if (combo.sale_price && parseFloat(combo.sale_price) > 0) {
                $('.apd-variant-price').addClass('has-sale');
                $('.apd-variant-regular-price').text('$' + parseFloat(combo.price).toFixed(2));
            } else {
                $('.apd-variant-price').removeClass('has-sale');
            }
            
            // Enable/disable button based on stock
            const $startBtn = $('#apd-start-customizing');
            if (combo.stock === 'outofstock') {
                $startBtn.prop('disabled', true)
                         .text('Out of Stock')
                         .addClass('disabled');
                $('#apd-stock-status').text('Out of Stock').addClass('out-of-stock').removeClass('in-stock');
            } else {
                $startBtn.prop('disabled', false)
                         .text('Start Customizing')
                         .removeClass('disabled');
                $('#apd-stock-status').text('In Stock').addClass('in-stock').removeClass('out-of-stock');
            }
            
            console.log('[APD Variants] Updated UI with price:', displayPrice, 'stock:', combo.stock);
        } else {
            console.warn('[APD Variants] No matching combination found');
            $('#apd-price-display').text('--');
            $('#apd-start-customizing').prop('disabled', true);
        }
    }
    
    // Start Customizing button
    $('#apd-start-customizing').on('click', function(e) {
        e.preventDefault();
        
        // Check if required selections are made
        const hasMaterialOptions = $('.apd-material-btn').length > 0;
        const hasSizeOptions = $('#apd-size-select').length > 0;
        
        if ((hasMaterialOptions && (selectedMaterial === null || selectedMaterial === undefined)) ||
            (hasSizeOptions && !selectedSize)) {
            const missingFields = [];
            if (hasMaterialOptions && !selectedMaterial) missingFields.push('material');
            if (hasSizeOptions && !selectedSize) missingFields.push('size');
            alert('Please select ' + missingFields.join(' and '));
            return;
        }
        
        const combo = apdCombinations.find(c => 
            String(c.size || '') === String(selectedSize || '') &&
            String(c.material || '') === String(selectedMaterial || '')
        );
        
        if (!combo) {
            alert('Invalid combination selected');
            return;
        }
        
        if (combo.stock === 'outofstock') {
            alert('This variant is currently out of stock');
            return;
        }
        
        // Build customizer URL with variant parameters
        const variantPrice = combo.sale_price && parseFloat(combo.sale_price) > 0 
            ? combo.sale_price 
            : combo.price;
            
        const url = apdVariantsConfig.homeUrl +
                    '/customizer/' + productId + '/' +
                    '?variant_size=' + encodeURIComponent(selectedSize) +
                    '&variant_material=' + encodeURIComponent(selectedMaterial) +
                    '&variant_sku=' + encodeURIComponent(combo.sku) +
                    '&variant_price=' + encodeURIComponent(variantPrice);
        
        console.log('[APD Variants] Redirecting to:', url);
        window.location.href = url;
    });
    
    // Initialize on page load
    initializeDefaults();
    }
});
