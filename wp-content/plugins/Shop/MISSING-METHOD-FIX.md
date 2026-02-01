# Missing Method Fix - ensure_core_pages

## Problem
```
PHP Fatal error: Uncaught TypeError: call_user_func_array(): 
Argument #1 ($callback) must be a valid callback, 
class AdvancedProductDesigner does not have a method "ensure_core_pages"
```

## Root Cause

The main plugin class (`AdvancedProductDesigner`) was trying to call a method that doesn't exist in that class:

```php
// In freight-signs-customizer.php (WRONG CLASS):
add_action('admin_init', array($this, 'ensure_core_pages'));
add_action('init', array($this, 'ensure_core_pages'));
```

But the method actually exists in the `APD_Meta_Boxes` class:

```php
// In class-meta-boxes.php (CORRECT CLASS):
public function ensure_core_pages() {
    // Creates cart, checkout, thank-you, and my-orders pages if missing
}
```

---

## Fix Applied

### 1. Removed incorrect hooks from `freight-signs-customizer.php`

**BEFORE (Lines 145-146):**
```php
add_action('admin_init', array($this, 'ensure_core_pages'));
add_action('init', array($this, 'ensure_core_pages'));
```

**AFTER:**
```php
// Note: ensure_core_pages is handled by APD_Meta_Boxes class during its init()
// (hooks removed from here)
```

### 2. Added correct hooks to `includes/class-meta-boxes.php`

**BEFORE:**
```php
public function init()
{
    add_action('add_meta_boxes', array($this, 'add_template_meta_boxes'));
    add_action('add_meta_boxes', array($this, 'add_product_meta_boxes'));
    add_action('save_post', array($this, 'save_product_meta'));
    add_action('save_post', array($this, 'save_template_meta'));
}
```

**AFTER:**
```php
public function init()
{
    add_action('add_meta_boxes', array($this, 'add_template_meta_boxes'));
    add_action('add_meta_boxes', array($this, 'add_product_meta_boxes'));
    add_action('save_post', array($this, 'save_product_meta'));
    add_action('save_post', array($this, 'save_template_meta'));
    
    // Ensure core pages exist (cart, checkout, thank-you, my-orders)
    add_action('admin_init', array($this, 'ensure_core_pages'));
    add_action('init', array($this, 'ensure_core_pages'));
}
```

---

## What This Method Does

The `ensure_core_pages()` method:
1. ✅ Checks if required pages exist (cart, checkout, thank-you, my-orders)
2. ✅ Creates missing pages automatically
3. ✅ Flushes rewrite rules if pages were created
4. ✅ Only runs for admin users to avoid overhead

This ensures the plugin always has the pages it needs to function.

---

## Files Modified

1. ✅ `freight-signs-customizer.php` - Removed incorrect hook registrations
2. ✅ `includes/class-meta-boxes.php` - Added correct hook registrations

---

## Deployment

Upload these 2 files to your server:

**Local:**
- `/Users/vanh/Documents/GitHub/pod/freight-signs-customizer.php`
- `/Users/vanh/Documents/GitHub/pod/includes/class-meta-boxes.php`

**Server:**
- `/wp-content/plugins/pod/freight-signs-customizer.php`
- `/wp-content/plugins/pod/includes/class-meta-boxes.php`

---

## Testing

After deployment:
- ✅ Plugin should activate without errors
- ✅ Core pages (cart, checkout, etc.) will be created automatically if missing
- ✅ No more "method does not exist" fatal errors

---

## Summary

**Problem:** Method called from wrong class
**Solution:** Moved hook registration to the correct class that contains the method
**Impact:** Plugin can now activate and will automatically create required pages
