# Hướng Dẫn Check Logs - APD Plugin

## Cách 1: WordPress Debug Log (Khuyến nghị)

### Bước 1: Enable WordPress Debug Mode

Mở file `wp-config.php` (trong thư mục WordPress root) và thêm:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false); // Không hiển thị errors trên frontend
```

### Bước 2: Check Log File

Logs sẽ được ghi vào: `wp-content/debug.log`

**Trên server (SSH):**
```bash
# Xem toàn bộ log
cat wp-content/debug.log

# Xem log real-time (theo dõi khi export PDF)
tail -f wp-content/debug.log

# Xem log gần đây (100 dòng cuối)
tail -n 100 wp-content/debug.log

# Tìm log về PDF export
grep "APD PDF" wp-content/debug.log

# Tìm log về material outline
grep "Material Outline" wp-content/debug.log

# Tìm log về custom text
grep "apdTextPattern" wp-content/debug.log
```

**Trên local (Mac/Windows):**
```bash
# Mở file trong editor
open wp-content/debug.log  # Mac
notepad wp-content/debug.log  # Windows

# Hoặc dùng terminal
tail -f wp-content/debug.log
```

### Bước 3: Filter Logs cho PDF Export

Để xem chỉ logs liên quan đến PDF export:

```bash
# Tất cả logs về PDF export
grep "APD PDF" wp-content/debug.log

# Logs về conversion
grep "Convert All to Curves" wp-content/debug.log

# Logs về material outline
grep "Material Outline" wp-content/debug.log

# Logs về pattern images
grep "Pattern Images" wp-content/debug.log

# Logs về custom text
grep "Custom Text Material" wp-content/debug.log
```

## Cách 2: Server Error Log

Nếu WordPress debug log không có, check server error log:

**Apache:**
```bash
# Ubuntu/Debian
tail -f /var/log/apache2/error.log

# CentOS/RHEL
tail -f /var/log/httpd/error_log

# Mac (MAMP/XAMPP)
tail -f /Applications/MAMP/logs/php_error.log
```

**Nginx + PHP-FPM:**
```bash
tail -f /var/log/php-fpm/error.log
# hoặc
tail -f /var/log/nginx/error.log
```

## Cách 3: Browser Console (Client-side)

Khi export PDF từ browser, mở Developer Tools (F12) và check Console tab:

1. Mở browser console (F12 hoặc Cmd+Option+I trên Mac)
2. Chọn tab "Console"
3. Export PDF
4. Xem logs với prefix `📄` hoặc `APD`

## Các Log Messages Quan Trọng

### PDF Export Success:
```
APD PDF Compatible NEW - Order #XXX: ✅ SVG prepared for PDF export
APD PDF NEW - Order #XXX: ✅ PDF generated successfully
```

### Material Outline:
```
APD Apply Material Outline NEW - Order #XXX: ✅ SUCCESS - Material outline applied as fills
APD Clip Pattern Images - Order #XXX: Created X clipPaths, X clip-path references
```

### Custom Text Patterns:
```
APD Preserve Custom Text Material - Order #XXX: Backed up X custom text pattern IDs
APD Restore Custom Text Material - Order #XXX: ✅ Restored X lost patterns
```

### Conversion:
```
APD Convert All to Curves NEW - Order #XXX: ✅ SUCCESS - Text converted to curves
```

### Warnings:
```
APD PDF Compatible NEW - Order #XXX: ⚠️ WARNING - ...
APD Apply Material Outline NEW - Order #XXX: ⚠️ Inkscape not available
```

## Quick Commands

```bash
# Xem log real-time cho PDF export
tail -f wp-content/debug.log | grep "APD PDF"

# Xem log cho order cụ thể
grep "Order #9332" wp-content/debug.log

# Đếm số lần conversion thành công
grep "SUCCESS - Text converted to curves" wp-content/debug.log | wc -l

# Xem tất cả warnings
grep "⚠️" wp-content/debug.log

# Xem tất cả errors
grep "ERROR" wp-content/debug.log
```

## Troubleshooting

### Log file quá lớn:
```bash
# Clear log (backup trước)
cp wp-content/debug.log wp-content/debug.log.backup
> wp-content/debug.log  # Clear file
```

### Không thấy logs:
1. Check `wp-config.php` có `WP_DEBUG_LOG` enabled không
2. Check permissions: `chmod 666 wp-content/debug.log`
3. Check disk space: `df -h`

### Logs không hiển thị đúng:
- Đảm bảo `error_log()` function không bị disable trong `php.ini`
- Check `display_errors` setting
