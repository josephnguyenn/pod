# Hướng Dẫn Install Inkscape - Material Outline Fix

## ⚠️ Quan Trọng

**KHÔNG thể install Inkscape trong thư mục `/pod`!**

Inkscape là **system software** phải được install ở server level.

```
✅ Đúng: /usr/bin/inkscape          (system binary)
❌ Sai:  /pod/inkscape               (không thể install ở đây)
```

## Kiểm Tra Trạng Thái

### Cách 1: Dùng Test Script

```bash
# Trong thư mục /pod
php check-inkscape.php
```

**Expected output nếu Inkscape CHƯA có:**
```
❌ ❌ ❌ INKSCAPE IS NOT AVAILABLE ❌ ❌ ❌
Material outline will NOT work in CorelDRAW PDF exports.
```

**Expected output nếu Inkscape ĐÃ CÓ:**
```
✅ ✅ ✅ INKSCAPE IS INSTALLED AND WORKING! ✅ ✅ ✅
Material outline will work in CorelDRAW PDF exports!
```

### Cách 2: Check Trực Tiếp (SSH)

```bash
which inkscape
inkscape --version
```

## Cách Install Inkscape

### Loại Hosting Của Bạn?

#### 📌 VPS / Cloud Server (DigitalOcean, AWS, Linode, etc.)

✅ **CÓ thể tự install**

```bash
# 1. SSH vào server
ssh user@your-server-ip

# 2. Install Inkscape
# Ubuntu/Debian:
sudo apt-get update
sudo apt-get install -y inkscape

# CentOS/RHEL:
sudo yum install -y epel-release
sudo yum install -y inkscape

# 3. Verify
inkscape --version
```

#### 📌 Shared Hosting (cPanel, Plesk)

❌ **KHÔNG thể tự install**

→ Liên hệ hosting support:

```
Subject: Request Inkscape Installation

Hi,

I need Inkscape installed on my hosting account for SVG/PDF 
processing in my WordPress plugin.

Installation command:
sudo apt-get install -y inkscape

Or if you prefer:
sudo yum install -y inkscape

This is a standard open-source graphics tool available in 
most Linux distributions.

Thank you!
```

#### 📌 Managed WordPress Hosting (WP Engine, Kinsta, etc.)

❌ **Thường KHÔNG cho phép**

→ Options:
1. Liên hệ support (có thể họ từ chối)
2. Chuyển sang VPS hosting
3. Dùng workaround (Cut-Ready SVG export)

### Kiểm Tra Loại Hosting

```bash
# Check if you have sudo access
sudo -v

# If you get this:
# sudo: a password is required
→ VPS/Cloud (you can install)

# If you get this:
# sudo: command not found
→ Shared hosting (contact support)
```

## Installation Steps - Chi Tiết

### Ubuntu/Debian Server

```bash
# 1. Update package list
sudo apt-get update

# 2. Install Inkscape
sudo apt-get install -y inkscape

# 3. Verify installation
which inkscape
# Should output: /usr/bin/inkscape

inkscape --version
# Should output: Inkscape 1.x.x ...

# 4. Test as web server user
sudo -u www-data inkscape --version
# Should work without errors
```

### CentOS/RHEL Server

```bash
# 1. Enable EPEL repository
sudo yum install -y epel-release

# 2. Install Inkscape
sudo yum install -y inkscape

# 3. Verify
which inkscape
inkscape --version

# 4. Test as web server user
sudo -u apache inkscape --version
```

### macOS (Local Development)

```bash
# Using Homebrew
brew install inkscape

# Verify
which inkscape
inkscape --version
```

## Troubleshooting

### Issue 1: Permission Denied

```bash
# Make Inkscape executable
sudo chmod +x /usr/bin/inkscape

# Test as web server user
sudo -u www-data inkscape --version
```

### Issue 2: Dependencies Missing

```bash
# Ubuntu/Debian
sudo apt-get install -y \
    libgtk-3-0 \
    libglib2.0-0 \
    libpango-1.0-0 \
    libcairo2 \
    libpoppler-glib8
```

### Issue 3: shell_exec Disabled

```bash
# Check disabled functions
php -r "echo ini_get('disable_functions');"

# If shell_exec is listed, edit php.ini:
sudo nano /etc/php/7.4/fpm/php.ini

# Find this line:
disable_functions = exec,passthru,shell_exec,system

# Remove shell_exec:
disable_functions = exec,passthru,system

# Restart PHP
sudo systemctl restart php-fpm
# or
sudo systemctl restart apache2
```

### Issue 4: Inkscape Not in PATH

```bash
# Create symlink
sudo ln -s /usr/local/bin/inkscape /usr/bin/inkscape

# Or add to PATH in php.ini
putenv("PATH=/usr/local/bin:/usr/bin:/bin");
```

## Verification After Installation

### Test 1: Command Line

```bash
inkscape --version
# Expected: Inkscape 1.x.x (hash, date)
```

### Test 2: PHP Script

```bash
cd /path/to/pod
php check-inkscape.php
# Expected: ✅ INKSCAPE IS INSTALLED AND WORKING!
```

### Test 3: WordPress Plugin

1. Go to Orders page
2. Export PDF with material outline
3. Check browser console
4. Should see:

```
✅ Server-side PDF generated successfully
Generated PDF using Inkscape
```

**NOT:**
```
⚠️ Using CLIENT-SIDE
```

### Test 4: CorelDRAW Import

1. Export PDF with material outline
2. Open in CorelDRAW
3. **Material outline should be VISIBLE** ✅

## What If I Can't Install Inkscape?

### Workaround: Use Cut-Ready SVG Export

**Pros:**
- ✅ No Inkscape needed
- ✅ Material outline works in CorelDRAW
- ✅ Direct SVG → CorelDRAW

**Cons:**
- ❌ Text NOT converted to curves
- ❌ Need manual conversion in CorelDRAW

**Steps:**
1. Click "Generate Cut-Ready SVG" instead of "Export PDF"
2. Open SVG directly in CorelDRAW
3. Select text and convert to curves: `Arrange → Convert to Curves`

## Installation Script

Tạo file `install-inkscape.sh`:

```bash
#!/bin/bash
echo "=== Inkscape Installation Script ==="
echo ""

# Detect OS
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS=$NAME
else
    OS=$(uname -s)
fi

echo "Detected OS: $OS"
echo ""

# Install based on OS
if [[ "$OS" == *"Ubuntu"* ]] || [[ "$OS" == *"Debian"* ]]; then
    echo "Installing for Ubuntu/Debian..."
    sudo apt-get update
    sudo apt-get install -y inkscape
    
elif [[ "$OS" == *"CentOS"* ]] || [[ "$OS" == *"Red Hat"* ]]; then
    echo "Installing for CentOS/RHEL..."
    sudo yum install -y epel-release
    sudo yum install -y inkscape
    
else
    echo "Unknown OS. Please install manually:"
    echo "  Ubuntu/Debian: sudo apt-get install inkscape"
    echo "  CentOS/RHEL: sudo yum install inkscape"
    exit 1
fi

# Verify
echo ""
echo "=== Verification ==="
which inkscape
inkscape --version

# Test as web server user
echo ""
echo "=== Testing as web server user ==="
if id -u www-data >/dev/null 2>&1; then
    sudo -u www-data inkscape --version
elif id -u apache >/dev/null 2>&1; then
    sudo -u apache inkscape --version
else
    echo "Web server user not found (www-data or apache)"
fi

echo ""
echo "=== Installation Complete! ==="
echo "Please test PDF export in WordPress."
```

Upload to server và chạy:

```bash
chmod +x install-inkscape.sh
./install-inkscape.sh
```

## Summary

| Step | Can Do in /pod? | Where to Do It |
|------|----------------|----------------|
| Check if Inkscape exists | ✅ Yes | `php check-inkscape.php` |
| Install Inkscape | ❌ No | SSH to server + `sudo apt-get install` |
| Test installation | ✅ Yes | `php check-inkscape.php` |
| Use Inkscape | ✅ Yes (automatic) | Plugin auto-detects when installed |

**Key Point:** Install phải làm trên SERVER (system-wide), KHÔNG phải trong `/pod` folder!

---

**Date:** January 24, 2026  
**Status:** Installation guide complete
