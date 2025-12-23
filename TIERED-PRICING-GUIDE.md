# Tiered Pricing System - Implementation Guide

## Overview
Implemented a comprehensive quantity-based tiered pricing system that automatically applies volume discounts when customers order in bulk.

## Features

### 1. **PricingService Class** (`includes/class-pricing-service.php`)
   - Calculates tiered prices based on quantity
   - Stores pricing tiers in product post meta (`_apd_price_tiers`)
   - Percentage-based discounts (e.g., 10%, 15%, 20%)
   - Automatic tier sorting by minimum quantity
   - Methods for saving, retrieving, and deleting tiers
   - Price breakdown calculations for display

### 2. **Admin Interface**
   - New meta box on product edit pages: "Volume Pricing Tiers"
   - Configure unlimited pricing tiers per product
   - Fields for each tier:
     - **Minimum Quantity**: Starting quantity for this tier
     - **Discount %**: Percentage discount (0-100%)
     - **Tier Name**: Optional label (e.g., "Bulk Order", "Wholesale")
   - Real-time price preview showing discounted prices
   - Visual preview of how tiers will appear to customers
   - Automatic validation and sanitization

### 3. **Frontend Display**
   - Attractive gradient-styled pricing table on product detail pages
   - Shows all available tiers with:
     - Quantity thresholds
     - Discount percentages
     - Calculated price per unit
   - Dynamic pricing messages:
     - **Active discount**: Shows current savings when tier applies
     - **Next tier incentive**: Encourages ordering more to reach next discount
   - Updates in real-time as quantity changes
   - Mobile-responsive design

### 4. **Cart Integration**
   - CartService automatically applies tiered pricing
   - Recalculates prices when quantity is updated
   - Works with existing material/variant pricing
   - Discounts shown in cart totals

## How It Works

### For Administrators

1. **Edit a Product** in WordPress admin
2. Scroll to **"Volume Pricing Tiers"** meta box
3. Click **"Add Tier"** to create a pricing tier
4. Enter:
   - Minimum quantity (e.g., 10, 25, 50)
   - Discount percentage (e.g., 10% for 10+ items)
   - Optional tier name
5. Add multiple tiers for different quantity ranges
6. **Save/Update** the product
7. View preview to confirm pricing structure

### For Customers

1. **Browse products** on the frontend
2. Products with tiered pricing display a **"Buy More, Save More!"** section
3. See all available tiers and savings in an easy-to-read table
4. **Adjust quantity** using +/- buttons
5. Watch pricing message update showing:
   - Current discount if tier is active
   - How many more items needed for next discount
6. **Add to cart** - discounted price automatically applied
7. Checkout with volume discount savings

## Example Configuration

### Product: Custom Sign
- Base Price: $29.99

**Tier 1:**
- Min Quantity: 10
- Discount: 10%
- Price per Unit: $26.99

**Tier 2:**
- Min Quantity: 25
- Discount: 15%
- Price per Unit: $25.49

**Tier 3:**
- Min Quantity: 50
- Discount: 20%
- Price per Unit: $23.99

### Customer Scenarios:

- **Order 5 items**: $29.99 each = $149.95 total
  - Message: "Order 5 more to save 10%!"

- **Order 15 items**: $26.99 each = $404.85 total (10% off)
  - Message: "🎉 Volume Discount Applied! You save 10% ($44.99 total savings)"

- **Order 60 items**: $23.99 each = $1,439.40 total (20% off)
  - Message: "🎉 Volume Discount Applied! You save 20% ($359.88 total savings)"

## Technical Details

### Database Storage
- **Meta Key**: `_apd_price_tiers`
- **Data Structure**:
```php
array(
    array(
        'min_qty' => 10,
        'discount_percent' => 10.00,
        'name' => 'Small Bulk Order'
    ),
    array(
        'min_qty' => 25,
        'discount_percent' => 15.00,
        'name' => 'Medium Bulk Order'
    )
)
```

### Calculation Logic
1. Get product base price
2. Retrieve all pricing tiers for product
3. Sort tiers by minimum quantity (ascending)
4. Find highest tier where `quantity >= min_qty`
5. Calculate: `discounted_price = base_price - (base_price × discount_percent / 100)`
6. Return pricing data with savings information

### Integration Points

**Autoloader** (`includes/class-autoloader.php`)
- Added `pricing-service` to services array

**CartService** (`includes/class-cart-service.php`)
- Constructor instantiates PricingService
- `calculate_item_price()` now accepts quantity parameter
- `add_to_cart()` passes quantity to pricing calculation
- `update_cart_item()` recalculates price when quantity changes

**Main Plugin** (`freight-signs-customizer.php`)
- Added `pricing_tiers_meta_box()` callback
- Added save handler in `save_product_meta()`
- Meta box displays in product admin

**Product Detail Template** (`templates/product-detail-page.php`)
- Displays pricing tier table
- JavaScript handles dynamic message updates
- CSS styling for attractive presentation

## API Methods

### PricingService Public Methods

```php
// Calculate tiered price
$pricing = $pricing_service->calculate_tiered_price($product_id, $quantity, $base_price);
// Returns: ['price' => 26.99, 'discount' => 10, 'tier' => [...], 'total' => 269.90, 'savings' => 30.00]

// Get all tiers for a product
$tiers = $pricing_service->get_price_tiers($product_id);

// Save tiers
$pricing_service->save_price_tiers($product_id, $tiers_array);

// Delete all tiers
$pricing_service->delete_price_tiers($product_id);

// Check if product has tiered pricing
$has_tiers = $pricing_service->has_tiered_pricing($product_id);

// Get HTML table for display
$html = $pricing_service->get_tier_table_html($product_id, $base_price);

// Get pricing message
$message = $pricing_service->get_pricing_message($product_id, $quantity, $base_price);

// Get formatted breakdown
$breakdown = $pricing_service->get_pricing_breakdown($product_id, $quantity, $base_price);
```

## Testing Checklist

### Admin Testing
- [ ] Create new product with pricing tiers
- [ ] Edit existing product to add tiers
- [ ] Remove tiers from product
- [ ] Verify tiers save correctly
- [ ] Check tier validation (negative values, invalid ranges)
- [ ] Confirm preview displays correct prices
- [ ] Test with multiple products

### Frontend Testing
- [ ] View product with no tiers (should not show pricing section)
- [ ] View product with tiers (should show volume pricing table)
- [ ] Change quantity - verify message updates
- [ ] Order quantity below first tier (no discount)
- [ ] Order quantity in tier 1 range (tier 1 discount applies)
- [ ] Order quantity in tier 2+ range (higher tier applies)
- [ ] Add to cart - verify discounted price in cart
- [ ] Update cart quantity - verify price recalculates
- [ ] Mobile responsiveness

### Edge Cases
- [ ] Product with sale price + tiers (should discount sale price)
- [ ] Product with material pricing + tiers
- [ ] Product with variants + tiers
- [ ] Very large quantities (100+)
- [ ] Quantity of 1 (no tier should apply)
- [ ] Empty tier configuration
- [ ] Overlapping tier ranges

## Styling Customization

The pricing table uses a gradient background and modern design. To customize:

**Colors** - Edit in `templates/product-detail-page.php`:
```css
.apd-volume-pricing {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); /* Change gradient */
}

.apd-discount-badge {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); /* Change badge colors */
}
```

**Layout** - Adjust padding, borders, shadows in the CSS section.

## Future Enhancements

Potential features to add:
1. **Tier Templates**: Save tier configurations as templates for reuse
2. **Bulk Tier Assignment**: Apply tiers to multiple products at once
3. **Date-Based Tiers**: Seasonal or promotional tier pricing
4. **User Role Tiers**: Different tiers for wholesale vs. retail customers
5. **Quantity Breaks**: Not just discounts, but different prices per range
6. **Tier Analytics**: Track which tiers are most popular
7. **CSV Import/Export**: Manage tiers in spreadsheet
8. **Global Tiers**: Default tiers for all products in a category

## Troubleshooting

### Tiers Not Showing on Frontend
- Check if `has_tiered_pricing()` returns true
- Verify tiers saved in post meta
- Clear browser cache
- Check PHP errors in console

### Discounts Not Applying
- Verify CartService is using PricingService
- Check quantity parameter being passed
- Review cart item price calculation
- Test with error logging enabled

### Admin UI Issues
- Check JavaScript console for errors
- Verify nonce validation
- Ensure WordPress version compatibility
- Review user permissions

## Support

For issues or questions:
1. Check error logs: `wp-content/debug.log`
2. Enable WP_DEBUG in wp-config.php
3. Review REFACTORING-PROGRESS.md for architecture details
4. Check git history for recent changes

## Commit History

- **785de81**: Add tiered pricing system with volume discounts
  - PricingService implementation
  - Admin meta box and save handler
  - Frontend display and dynamic messaging
  - CartService integration

---

**Status**: ✅ Implemented and Ready for Testing
**Version**: 1.0.0
**Date**: December 23, 2025
