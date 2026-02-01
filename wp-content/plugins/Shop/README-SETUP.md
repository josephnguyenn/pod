# Setup Guide - Material Outline Support

## 🎯 Objective

Enable material outline display in CorelDRAW PDF exports.

## 🚀 Quick Start

### For Local Development (Mac)

```bash
# 1. Install Inkscape on your Mac
brew install inkscape

# 2. Verify installation
cd /Users/vanh/Documents/GitHub/pod
php check-inkscape.php

# Expected: ✅ INKSCAPE IS INSTALLED AND WORKING!
```

### For Production Server

```bash
# 1. Deploy code to server (git/FTP)
git push origin main

# 2. SSH to server
ssh user@your-server.com

# 3. Run auto-install
cd /path/to/wordpress/wp-content/plugins/pod
chmod +x server-setup.sh
sudo bash server-setup.sh

# 4. Verify
php check-inkscape.php
```

## 📁 Files Overview

### Test & Setup Files
- ✅ `check-inkscape.php` - Check if Inkscape is available
- ✅ `server-setup.sh` - Auto-install Inkscape on server
- ✅ `DEPLOYMENT-INKSCAPE.md` - Deployment guide

### Documentation
- ✅ `INKSCAPE-INSTALLATION-GUIDE.md` - Detailed installation
- ✅ `MATERIAL-OUTLINE-CORELDRAW-ANALYSIS.md` - Technical analysis
- ✅ `README-MATERIAL-OUTLINE-FIX.md` - User guide
- ✅ `MATERIAL-OUTLINE-PDF-COMPATIBILITY.md` - PDF compatibility

### Updated Plugin Files
- ✅ `includes/class-order-admin-handler.php` - Client-side PDF with warnings
- ✅ `includes/class-svg-processor.php` - Server-side PDF with Inkscape

## 🔧 Installation Commands

### macOS (Local Development)
```bash
brew install inkscape
```

### Ubuntu/Debian (Production Server)
```bash
sudo apt-get update
sudo apt-get install -y inkscape
```

### CentOS/RHEL (Production Server)
```bash
sudo yum install -y epel-release
sudo yum install -y inkscape
```

## ✅ Verification

### Test Command
```bash
php check-inkscape.php
```

### Expected Output (Success)
```
✅ ✅ ✅ INKSCAPE IS INSTALLED AND WORKING! ✅ ✅ ✅

Material outline will work in CorelDRAW PDF exports!

Path: /usr/bin/inkscape
Version: Inkscape 1.2.1
```

### Expected Output (Not Installed)
```
❌ ❌ ❌ INKSCAPE IS NOT AVAILABLE ❌ ❌ ❌

Material outline will NOT work in CorelDRAW PDF exports.

INSTALLATION STEPS:
[...installation instructions...]
```

## 🎨 Testing Material Outline

### Step 1: Export PDF
1. Go to WordPress Admin → Orders
2. Find order with material outline
3. Click "Export Vector PDF"
4. Check browser console

### Step 2: Check Logs

**✅ Success (Using Inkscape):**
```
📄 ✅ Server-side PDF generated successfully
📄 Generated PDF using Inkscape
📄 Pattern FILLS (CorelDRAW compatible): 2
```

**❌ Fallback (No Inkscape):**
```
📄 ⚠️ Using CLIENT-SIDE
📄 Pattern FILLS: 0
⚠️ WARNING: Material outlines may not display in CorelDRAW
```

### Step 3: Verify in CorelDRAW
1. Open exported PDF in CorelDRAW
2. Material outline should be **VISIBLE** ✅

## 📊 Compatibility Matrix

| Export Method | Inkscape Required | Material Outline in CorelDRAW |
|---------------|-------------------|-------------------------------|
| Server PDF (with Inkscape) | ✅ Yes | ✅ **Works** |
| Client PDF (no Inkscape) | ❌ No | ❌ Doesn't work |
| Cut-Ready SVG | ❌ No | ✅ Works (text not curves) |

## 🐛 Troubleshooting

### Inkscape Not Detected

**Check:**
```bash
which inkscape                          # Should return path
inkscape --version                      # Should show version
sudo -u www-data inkscape --version     # Test as web server user
php -r "echo ini_get('disable_functions');"  # Check if shell_exec disabled
```

**Fix:**
- Reinstall Inkscape
- Enable `shell_exec` in php.ini
- Check file permissions
- Restart PHP-FPM

### Shared Hosting (Cannot Install)

**Workaround:** Use Cut-Ready SVG export
- Click "Generate Cut-Ready SVG" instead
- Open SVG directly in CorelDRAW
- Material outline will display ✅
- Manually convert text to curves if needed

## 📝 Development Workflow

### 1. Local Development
```bash
# Install Inkscape locally
brew install inkscape

# Test plugin
php check-inkscape.php

# Develop features
# Test material outline exports
```

### 2. Commit & Push
```bash
git add .
git commit -m "Add Inkscape support for material outline"
git push origin main
```

### 3. Deploy to Production
```bash
# SSH to server
ssh user@production-server

# Pull latest code
cd /path/to/wordpress/wp-content/plugins/pod
git pull origin main

# Install Inkscape
sudo bash server-setup.sh

# Verify
php check-inkscape.php
```

### 4. Test Production
- Export PDF with material outline
- Check browser console logs
- Verify in CorelDRAW

## 📚 Documentation Index

| File | Purpose |
|------|---------|
| **README-SETUP.md** | This file - Quick setup guide |
| **check-inkscape.php** | Test if Inkscape is available |
| **server-setup.sh** | Auto-install Inkscape on server |
| **DEPLOYMENT-INKSCAPE.md** | Detailed deployment guide |
| **INKSCAPE-INSTALLATION-GUIDE.md** | Manual installation instructions |
| **MATERIAL-OUTLINE-CORELDRAW-ANALYSIS.md** | Technical analysis |
| **README-MATERIAL-OUTLINE-FIX.md** | User-facing guide |
| **MATERIAL-OUTLINE-PDF-COMPATIBILITY.md** | PDF compatibility details |

## 🎯 Summary

**To enable material outline in CorelDRAW:**

1. ✅ Install Inkscape (local + production)
2. ✅ Test: `php check-inkscape.php`
3. ✅ Export PDF with material outline
4. ✅ Material outline displays in CorelDRAW

**If cannot install Inkscape:**
- Use Cut-Ready SVG export
- Material outline still works, but text not converted to curves

---

**Last Updated:** January 24, 2026  
**Status:** ✅ Ready for deployment  
**Support:** See documentation files for detailed guides
