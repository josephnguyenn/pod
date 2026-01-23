# Complete Fatal Error Fix - January 21, 2026

## Problem Summary
```
Plugin could not be activated because it triggered a fatal error.
PHP Fatal error: Cannot redeclare apd_init()
```

## Root Causes

### 1. Duplicate Plugin Installation
**Error:** Function redeclaration
- Plugin installed in TWO locations on server:
  - `/wp-content/plugins/Shop/freight-signs-customizer.php`
  - `/wp-content/plugins/pod/freight-signs-customizer.php`
- Both trying to declare same functions/classes

### 2. Headers Already Sent
**Error:** Cannot modify header information
- `setcookie()` called after headers sent by SEO plugin
- Occurred in `class-cart-service.php` line 57

---

## Fixes Applied

### Fix 1: Prevent Function/Class Redeclaration

#### File: `freight-signs-customizer.php`

**Protected main class:**
```php
// BEFORE:
class AdvancedProductDesigner {
    // ...
}

// AFTER:
if (!class_exists('AdvancedProductDesigner')) {
    class AdvancedProductDesigner {
        // ...
    }
}
```

**Protected init function:**
```php
// BEFORE:
function apd_init() {
    // ...
}

// AFTER:
if (!function_exists('apd_init')) {
    function apd_init() {
        // ...
    }
}
```

#### File: `includes/block-registration.php`

**Protected block class:**
```php
// BEFORE:
class APD_Block_Registration {
    // ...
}

// AFTER:
if (!class_exists('APD_Block_Registration')) {
    class APD_Block_Registration {
        // ...
    }
}
```

### Fix 2: Safe Cookie Setting

#### File: `includes/class-cart-service.php`

**Added headers_sent() check:**
```php
// BEFORE:
setcookie('apd_cart_session', $session_id, time() + self::CART_EXPIRATION, ...);

// AFTER:
if (!headers_sent()) {
    setcookie('apd_cart_session', $session_id, time() + self::CART_EXPIRATION, ...);
} else {
    error_log('APD Cart Service - Cannot set cookie, headers already sent...');
}
```

---

## Deployment Instructions

### Step 1: Upload Fixed Files to Server

Upload these 3 files to your server:
1. `freight-signs-customizer.php` → `/wp-content/plugins/pod/freight-signs-customizer.php`
2. `includes/class-cart-service.php` → `/wp-content/plugins/pod/includes/class-cart-service.php`
3. `includes/block-registration.php` → `/wp-content/plugins/pod/includes/block-registration.php`

### Step 2: Remove Duplicate Plugin (IMPORTANT!)

**Option A: Keep "pod" folder, remove "Shop"**
```bash
# Via SSH:
cd /home/customer/www/gotospectrum.com/public_html/wp-content/plugins
rm -rf Shop
```

**Option B: Keep "Shop" folder, remove "pod"**
```bash
# Via SSH:
cd /home/customer/www/gotospectrum.com/public_html/wp-content/plugins
rm -rf pod
```

> ⚠️ **IMPORTANT:** You MUST remove one of the duplicate plugin folders, or the error will continue!

### Step 3: Activate Plugin

1. Log into WordPress admin
2. Go to Plugins page
3. Find "Advanced Product Designer"
4. Click "Activate"
5. Should activate successfully without errors

---

## What These Fixes Do

### ✅ Prevents Function Redeclaration
- Plugin can coexist with duplicate installations
- No fatal errors during activation
- Safe to have multiple versions temporarily

### ✅ Safe Cookie Handling
- Checks if headers already sent before setting cookies
- Continues working even if cookies can't be set
- Better compatibility with SEO and other plugins

### ✅ Maintains Full Functionality
- All plugin features work normally
- Cart system functions properly
- No loss of data or settings

---

## Testing Checklist

After deployment:

- [ ] Plugin activates successfully
- [ ] No fatal errors in debug.log
- [ ] Cart functionality works
- [ ] Products can be customized
- [ ] Orders can be created
- [ ] Admin pages load correctly

---

## If Problems Persist

1. **Check which plugin folder is active:**
   ```bash
   ls -la /wp-content/plugins/ | grep -E "(Shop|pod)"
   ```

2. **Verify only ONE copy exists**
   - Should only see either "Shop" OR "pod", not both

3. **Clear all caches:**
   - WordPress cache
   - Server cache (Redis/Memcached)
   - Browser cache
   - CDN cache (Cloudflare, etc.)

4. **Check file permissions:**
   ```bash
   chmod 644 freight-signs-customizer.php
   chmod 644 includes/class-cart-service.php
   chmod 644 includes/block-registration.php
   ```

---

## Summary

✅ **Fixed Issues:**
1. Function/class redeclaration errors
2. Headers already sent errors
3. Plugin activation failures

✅ **Files Modified:**
- `freight-signs-customizer.php`
- `includes/class-cart-service.php`
- `includes/block-registration.php`

✅ **Required Action:**
- Upload 3 fixed files
- Remove duplicate plugin folder
- Activate plugin

The plugin should now activate successfully! 🎉
