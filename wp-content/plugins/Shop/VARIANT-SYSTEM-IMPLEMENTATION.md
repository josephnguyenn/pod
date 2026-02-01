# SKU-Based Product Variant System - Implementation Summary

## Overview
Complete implementation of a SKU-based product variant system allowing size × material combinations with individual SKUs, prices, and stock management.

## Data Structure

### Storage
- **Location**: `_apd_variants` post meta field (JSON)
- **Structure**:
```json
{
  "enabled": true/false,
  "size_options": ["12x18", "18x24", "24x36"],
  "material_options": ["Reflective White", "Fluorescent Orange"],
  "combinations": [
    {
      "size": "12x18",
      "material": "Reflective White",
      "sku": "FREIGHT-12x18-REF-WHT",
      "price": "29.99",
      "sale_price": "",
      "stock": "in_stock"
    }
  ]
}
```

## Admin Features

### Meta Box (freight-signs-customizer.php)
Located in `product_variants_meta_box()` function:

1. **Enable/Disable Toggle**: Checkbox to enable variants for product
2. **Size Options**: Text input for comma-separated sizes (e.g., "12x18, 18x24, 24x36")
3. **Material Selection**: Checkboxes for each material from `apd_materials` option
4. **Generate Combinations Button**: Creates all size × material combinations
5. **Combinations Table**: 
   - Columns: Size, Material, SKU, Price, Sale Price, Stock Status
   - Each row is a unique variant combination
   - Stock dropdown: In Stock, Out of Stock, Pre-order

### Save Handler
Function `save_product_meta()` processes:
- `apd_variants_enabled` checkbox
- `apd_size_options` text field
- `apd_material_options[]` checkbox array
- `apd_combinations[]` multidimensional array with all variant data

## Frontend Display (product-detail-page.php)

### Variant Product Layout
When variants are enabled, displays:

1. **Circular Material Swatches**:
   ```html
   <div class="apd-swatch" 
        data-material-id="X" 
        style="background-image: url(...)">
   </div>
   ```
   - 60px circular thumbnails
   - Hover scale effect
   - Selected state with primary border color

2. **Size Dropdown**:
   ```html
   <select id="apd-size-select">
     <option value="">Select size</option>
     <option value="12x18">12x18</option>
   </select>
   ```

3. **Dynamic Pricing**:
   - Updates based on selected size + material
   - Shows sale price strikethrough if applicable
   - Displays stock status (In Stock / Out of Stock)

4. **Action Button**:
   - Variant products: "Start Customizing" button
   - Redirects to: `/customize?product_id=X&variant_size=12x18&variant_material=Reflective White&variant_sku=FREIGHT-12x18-REF-WHT&variant_price=29.99`
   - Regular products: Standard "Customize Now" + "Add to Cart" buttons

## JavaScript (product-variants.js)

### Initialization
- Loads on product detail page only
- Reads `apdCombinations` global variable
- Listens to swatch clicks and size changes

### Key Functions

1. **Material Swatch Selection**:
   ```javascript
   $('.apd-swatch').on('click', function() {
     const materialId = $(this).data('material-id');
     $('.apd-swatch').removeClass('selected');
     $(this).addClass('selected');
     apdState.selectedMaterial = materialId;
     updateVariantInfo();
   });
   ```

2. **Size Selection**:
   ```javascript
   $('#apd-size-select').on('change', function() {
     apdState.selectedSize = $(this).val();
     updateVariantInfo();
   });
   ```

3. **Update Variant Info**:
   - Finds matching combination from `apdCombinations`
   - Updates price display
   - Updates stock status
   - Enables/disables "Start Customizing" button

4. **Start Customizing Handler**:
   - Validates selections
   - Constructs URL with variant parameters
   - Redirects to customizer

## Customizer Integration (customizer.js)

### URL Parameter Parsing
```javascript
const urlParams = new URLSearchParams(window.location.search);
const variantData = {
  size: urlParams.get('variant_size'),
  material: urlParams.get('variant_material'),
  sku: urlParams.get('variant_sku'),
  price: urlParams.get('variant_price')
};
```

### Auto-Material Selection
Function `autoSelectVariantMaterial(materialName)`:
1. Waits for material options to load
2. Finds matching material option (case-insensitive)
3. Triggers click to select it
4. Updates preview and pricing

### Payload Integration
Adds to cart payload:
```javascript
variant_info: {
  size: "12x18",
  material: "Reflective White",
  sku: "FREIGHT-12x18-REF-WHT",
  price: "29.99"
}
```

## Cart Display (cart.js)

### renderCustomizationDetails()
Shows variant info first:
```html
<li><strong>Size:</strong> 12x18</li>
<li><strong>SKU:</strong> FREIGHT-12x18-REF-WHT</li>
<li><strong>Variant Material:</strong> Reflective White</li>
```

Maintains backward compatibility with legacy price-modifier variants.

## Order History (templates/orders.php)

Displays in order item specs:
```php
if (!empty($cd['variant_info'])) {
  if (!empty($cd['variant_info']['size'])) 
    $specs[] = 'Size: ' . esc_html($cd['variant_info']['size']);
  if (!empty($cd['variant_info']['sku'])) 
    $specs[] = 'SKU: ' . esc_html($cd['variant_info']['sku']);
}
```

## CSS Styling

### Material Swatches
```css
.apd-swatch {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  border: 3px solid transparent;
  background-size: cover;
  background-position: center;
  cursor: pointer;
  transition: all 0.3s ease;
}

.apd-swatch:hover {
  transform: scale(1.1);
}

.apd-swatch.selected {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.3);
}
```

### Size Dropdown
```css
.apd-size-dropdown {
  width: 100%;
  padding: 10px 12px;
  border: 2px solid var(--color-border);
  border-radius: 6px;
  font-size: 1rem;
}

.apd-size-dropdown:focus {
  outline: none;
  border-color: var(--color-primary);
}
```

### Pricing Display
```css
.apd-variant-price {
  font-size: 2rem;
  font-weight: 900;
}

.apd-variant-regular-price {
  font-size: 1.5rem;
  text-decoration: line-through;
  color: var(--color-secondary);
}
```

### Stock Status
```css
.apd-stock-status.in-stock {
  background: #d4edda;
  color: #155724;
}

.apd-stock-status.out-of-stock {
  background: #f8d7da;
  color: #721c24;
}
```

## User Flow

1. **Product Page**:
   - Customer sees circular material swatches
   - Clicks on desired material (border highlights)
   - Selects size from dropdown
   - Price updates dynamically
   - Stock status shows availability
   - "Start Customizing" button enables

2. **Customizer**:
   - URL contains variant parameters
   - Material auto-selected (500ms delay for loading)
   - Customer customizes text/graphics
   - Variant info stored in cart payload

3. **Cart**:
   - Shows selected size and SKU
   - Displays variant material separately from customization material
   - Preview image with customizations

4. **Order History**:
   - Size and SKU appear in item specs
   - All customization details preserved

## Testing Checklist

- [ ] Enable variants on product
- [ ] Add size options and select materials
- [ ] Generate combinations table
- [ ] Verify all combinations appear with default SKUs
- [ ] Edit SKUs, prices, and stock status
- [ ] Save product
- [ ] View product page - see swatches and size dropdown
- [ ] Select material - verify swatch highlights
- [ ] Change size - verify price updates
- [ ] Check stock status display
- [ ] Click "Start Customizing"
- [ ] Verify material auto-selected in customizer
- [ ] Complete customization and add to cart
- [ ] Check cart shows size and SKU
- [ ] Complete checkout
- [ ] View order history - verify variant info displays

## File Changes

### Modified Files:
1. `freight-signs-customizer.php` - Meta box and save handler
2. `assets/js/customizer.js` - URL parsing and auto-selection
3. `assets/js/cart.js` - Variant display in cart
4. `templates/product-detail-page.php` - Circular swatches UI and CSS
5. `templates/orders.php` - Variant display in order history

### New Files:
1. `assets/js/product-variants.js` - Frontend variant interactions

## Backward Compatibility

- Non-variant products display normally
- Legacy price-modifier variants still supported in cart/orders
- Existing products unaffected until variants enabled
- Mixed cart (variant + non-variant products) fully supported

## Future Enhancements

- Bulk SKU generation with patterns
- Inventory tracking per variant
- Variant images per combination
- Export/import combinations CSV
- Low stock warnings
- Backorder management
- Quantity limits per variant
