# PHP 7.4+ Nested Ternary Operator Fix

## Problem
```
PHP Fatal error: Unparenthesized `a ? b : c ? d : e` is not supported. 
Use either `(a ? b : c) ? d : e` or `a ? b : (c ? d : e)`
```

## What Changed in PHP 7.4
PHP 7.4+ requires explicit parentheses in nested ternary operators to avoid ambiguity.

**OLD (PHP 7.3 and earlier - ALLOWED):**
```php
$value = $a ? $b : $c ? $d : $e;
```

**NEW (PHP 7.4+ - REQUIRED):**
```php
$value = $a ? $b : ($c ? $d : $e);
```

---

## Files Fixed

### 1. `includes/class-order-service.php`

#### Line 144
```php
// BEFORE (BROKEN):
$price = isset($item['price']) ? floatval($item['price']) : floatval(isset($item['product_price']) ? $item['product_price'] : APD_Config::DEFAULT_PRODUCT_PRICE);

// AFTER (FIXED):
$price = isset($item['price']) ? floatval($item['price']) : (floatval(isset($item['product_price']) ? $item['product_price'] : APD_Config::DEFAULT_PRODUCT_PRICE));
```

#### Line 460
```php
// BEFORE (BROKEN):
$price = (float) (isset($item['price']) ? $item['price'] : isset($item['product_price']) ? $item['product_price'] : 0);

// AFTER (FIXED):
$price = (float) (isset($item['price']) ? $item['price'] : (isset($item['product_price']) ? $item['product_price'] : 0));
```

### 2. `includes/class-order-admin-handler.php`

#### Line 215 - Image fallback chain
```php
// BEFORE (BROKEN):
$image_to_display = isset($first['preview_image_svg']) ? $first['preview_image_svg'] : isset($first['preview_image_png']) ? $first['preview_image_png'] : isset($first['preview_image_url']) ? $first['preview_image_url'] : isset($first['customization_image_url']) ? $first['customization_image_url'] : isset($first['image_url']) ? $first['image_url'] : '';

// AFTER (FIXED):
$image_to_display = isset($first['preview_image_svg']) ? $first['preview_image_svg'] : (isset($first['preview_image_png']) ? $first['preview_image_png'] : (isset($first['preview_image_url']) ? $first['preview_image_url'] : (isset($first['customization_image_url']) ? $first['customization_image_url'] : (isset($first['image_url']) ? $first['image_url'] : ''))));
```

#### Lines 364, 366 - Cart item image fallback
```php
// BEFORE (BROKEN):
$imgUrl = isset($item['preview_image_svg']) ? $item['preview_image_svg'] : isset($item['preview_image_png']) ? $item['preview_image_png'] : isset($item['preview_image_url']) ? $item['preview_image_url'] : isset($item['customization_image_url']) ? $item['customization_image_url'] : isset($item['image_url']) ? $item['image_url'] : '';

// AFTER (FIXED):
$imgUrl = isset($item['preview_image_svg']) ? $item['preview_image_svg'] : (isset($item['preview_image_png']) ? $item['preview_image_png'] : (isset($item['preview_image_url']) ? $item['preview_image_url'] : (isset($item['customization_image_url']) ? $item['customization_image_url'] : (isset($item['image_url']) ? $item['image_url'] : ''))));
```

#### Line 370 - Price calculation
```php
// BEFORE (BROKEN):
$price = isset($item['total']) ? (float) $item['total'] : ((float) (isset($item['price']) ? $item['price'] : isset($item['product_price']) ? $item['product_price'] : 0) * $qty);

// AFTER (FIXED):
$price = isset($item['total']) ? (float) $item['total'] : ((float) (isset($item['price']) ? $item['price'] : (isset($item['product_price']) ? $item['product_price'] : 0)) * $qty);
```

#### Line 383 - Material fallback
```php
// BEFORE (BROKEN):
$material = isset($item['vinyl_material']) ? $item['vinyl_material'] : isset($cd['vinyl_material']) ? $cd['vinyl_material'] : isset($cd['material']) ? $cd['material'] : '';

// AFTER (FIXED):
$material = isset($item['vinyl_material']) ? $item['vinyl_material'] : (isset($cd['vinyl_material']) ? $cd['vinyl_material'] : (isset($cd['material']) ? $cd['material'] : ''));
```

#### Line 396 - Color fallback
```php
// BEFORE (BROKEN):
$color = isset($item['print_color']) ? $item['print_color'] : isset($cd['print_color']) ? $cd['print_color'] : isset($cd['color']) ? $cd['color'] : '';

// AFTER (FIXED):
$color = isset($item['print_color']) ? $item['print_color'] : (isset($cd['print_color']) ? $cd['print_color'] : (isset($cd['color']) ? $cd['color'] : ''));
```

---

## Summary of Changes

**Files Modified:** 2
- `includes/class-order-service.php` (2 fixes)
- `includes/class-order-admin-handler.php` (6 fixes)

**Total Fixes:** 8 nested ternary operators

---

## How to Deploy

### Upload these 2 files to your server:
1. `includes/class-order-service.php`
2. `includes/class-order-admin-handler.php`

### Server Paths:
```
/wp-content/plugins/pod/includes/class-order-service.php
/wp-content/plugins/pod/includes/class-order-admin-handler.php
```

---

## Testing

After uploading, the plugin should:
- ✅ Activate without fatal errors
- ✅ Work on PHP 7.4, 8.0, 8.1, 8.2, 8.3+
- ✅ Order display pages work correctly
- ✅ Cart calculations work correctly
- ✅ Image fallbacks work correctly

---

## PHP Version Compatibility

| PHP Version | Before Fix | After Fix |
|-------------|------------|-----------|
| PHP 7.3 | ✅ Works | ✅ Works |
| PHP 7.4 | ❌ Fatal Error | ✅ Works |
| PHP 8.0 | ❌ Fatal Error | ✅ Works |
| PHP 8.1 | ❌ Fatal Error | ✅ Works |
| PHP 8.2 | ❌ Fatal Error | ✅ Works |
| PHP 8.3+ | ❌ Fatal Error | ✅ Works |

---

## Why This Happened

Nested ternary operators without parentheses were deprecated in PHP 7.4 because they can be ambiguous and lead to unexpected behavior. PHP 7.4+ requires explicit parentheses to make the order of operations clear.

This is a **breaking change** in PHP 7.4+ and affects many older WordPress plugins.
