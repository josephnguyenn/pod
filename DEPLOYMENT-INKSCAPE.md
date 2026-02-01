# Deployment Guide - Inkscape Setup for Material Outline

## Overview

Để material outline hoạt động trong CorelDRAW, cần cài Inkscape trên server.

**Files trong repo:**
- ✅ `check-inkscape.php` - Test script (check if Inkscape is available)
- ✅ `server-setup.sh` - Auto-install script for production server
- ✅ `INKSCAPE-INSTALLATION-GUIDE.md` - Detailed manual

## Quick Deployment Steps

### Step 1: Local Development (Optional)

Nếu develop local, cài Inkscape trên máy:

```bash
# macOS
brew install inkscape

# Ubuntu/Linux
sudo apt-get install inkscape

# Windows
# Download from: https://inkscape.org/release/
```

Test local:
```bash
cd /Users/vanh/Documents/GitHub/pod
php check-inkscape.php
```

### Step 2: Deploy Code to Server

```bash
# Commit changes
git add .
git commit -m "Add Inkscape support for material outline in CorelDRAW"
git push origin main

# Or upload via FTP/SFTP
# Upload entire /pod folder to server
```

### Step 3: Install Inkscape on Production Server

#### Method A: Auto Install (Recommended)

```bash
# SSH to production server
ssh user@your-production-server.com

# Navigate to uploaded files
cd /path/to/wordpress/wp-content/plugins/pod

# Make script executable
chmod +x server-setup.sh

# Run installation script
sudo bash server-setup.sh
```

**Expected output:**
```
==========================================
  ✅ INSTALLATION COMPLETE!
==========================================

✅ Inkscape found at: /usr/bin/inkscape
✅ Version: Inkscape 1.2.1
✅ www-data can execute Inkscape
✅ shell_exec is enabled
```

#### Method B: Manual Install

```bash
# SSH to server
ssh user@your-production-server.com

# Ubuntu/Debian
sudo apt-get update
sudo apt-get install -y inkscape

# CentOS/RHEL
sudo yum install -y epel-release
sudo yum install -y inkscape

# Verify
inkscape --version
```

### Step 4: Verify Installation

#### From Server (SSH):

```bash
cd /path/to/wordpress/wp-content/plugins/pod
php check-inkscape.php
```

**Expected:**
```
✅ ✅ ✅ INKSCAPE IS INSTALLED AND WORKING! ✅ ✅ ✅
Material outline will work in CorelDRAW PDF exports!
```

#### From WordPress Admin:

1. Go to **Orders** page
2. Find order with material outline
3. Click **Export Vector PDF**
4. Open browser console (F12)
5. Look for logs:

**✅ Success (Inkscape working):**
```
📄 ✅ Server-side PDF generated successfully
📄 Generated PDF using Inkscape
📄 Pattern FILLS (CorelDRAW compatible): 2
```

**❌ Inkscape not available:**
```
📄 ⚠️ Using CLIENT-SIDE
📄 Pattern FILLS: 0
⚠️ WARNING: Material outlines may not display in CorelDRAW
```

### Step 5: Test in CorelDRAW

1. Export PDF with material outline
2. Open PDF in CorelDRAW
3. **Material outline should be visible** ✅

## Troubleshooting

### Issue 1: Shared Hosting (No SSH Access)

**Solution:** Contact hosting support

```
Subject: Inkscape Installation Request

Hi,

Please install Inkscape on my hosting account:

Command: sudo apt-get install -y inkscape

This is needed for SVG/PDF processing in my WordPress plugin.

Thank you!
```

**Alternative:** Switch to VPS hosting (DigitalOcean, Linode, AWS)

### Issue 2: shell_exec Disabled

```bash
# Check disabled functions
php -r "echo ini_get('disable_functions');"

# If shell_exec is disabled, edit php.ini:
sudo nano /etc/php/7.4/fpm/php.ini

# Find and modify:
disable_functions = exec,passthru,system
# (remove shell_exec)

# Restart PHP
sudo systemctl restart php-fpm
```

### Issue 3: Permission Issues

```bash
# Make Inkscape executable
sudo chmod +x /usr/bin/inkscape

# Test as web server user
sudo -u www-data inkscape --version
```

### Issue 4: Inkscape Installed but Plugin Doesn't Detect

Check WordPress error logs:

```bash
# Enable WordPress debug mode
# Edit wp-config.php:
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

# Check logs
tail -f /path/to/wordpress/wp-content/debug.log

# Look for:
# "APD: Found Inkscape at: /usr/bin/inkscape"
# or
# "APD: Inkscape NOT FOUND"
```

## Deployment Checklist

### Pre-Deployment (Local)
- [ ] Test material outline in local WordPress
- [ ] Run `php check-inkscape.php` locally
- [ ] Commit code changes
- [ ] Push to repository

### Deployment (Production)
- [ ] Deploy code to production server
- [ ] SSH to production server
- [ ] Run `sudo bash server-setup.sh`
- [ ] Verify: `php check-inkscape.php`

### Post-Deployment Testing
- [ ] Export PDF with material outline
- [ ] Check browser console logs
- [ ] Verify "Generated PDF using Inkscape" message
- [ ] Open PDF in CorelDRAW
- [ ] Confirm material outline is visible

### If Inkscape Cannot Be Installed
- [ ] Use Cut-Ready SVG export instead
- [ ] Document workaround for users
- [ ] Update plugin documentation

## Files Included in Deployment

```
/pod/
├── check-inkscape.php              ← Test script
├── server-setup.sh                 ← Auto-install script
├── DEPLOYMENT-INKSCAPE.md          ← This file
├── INKSCAPE-INSTALLATION-GUIDE.md  ← Detailed guide
├── MATERIAL-OUTLINE-*.md           ← Technical docs
└── includes/
    ├── class-order-admin-handler.php  ← Updated with warnings
    └── class-svg-processor.php        ← Inkscape integration
```

## Environment Support Matrix

| Environment | Can Install Inkscape? | Auto-Install Script | Manual Install |
|-------------|----------------------|---------------------|----------------|
| VPS (Ubuntu/Debian) | ✅ Yes | ✅ Yes | ✅ Yes |
| VPS (CentOS/RHEL) | ✅ Yes | ✅ Yes | ✅ Yes |
| Shared Hosting | ❌ No | ❌ No | ⚠️ Contact support |
| Managed WordPress | ⚠️ Maybe | ❌ No | ⚠️ Contact support |
| Local Development | ✅ Yes | ⚠️ Manual | ✅ Yes |

## Support

### Documentation Files:
- `INKSCAPE-INSTALLATION-GUIDE.md` - Installation instructions
- `MATERIAL-OUTLINE-CORELDRAW-ANALYSIS.md` - Technical analysis
- `README-MATERIAL-OUTLINE-FIX.md` - User guide

### Test Commands:
```bash
# Quick test
php check-inkscape.php

# Detailed test
inkscape --version
which inkscape
sudo -u www-data inkscape --version
```

### WordPress Debug:
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Check logs
tail -f wp-content/debug.log
```

---

**Date:** January 24, 2026  
**Status:** Ready for deployment  
**Required:** SSH access to production server for Inkscape installation
