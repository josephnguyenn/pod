#!/bin/bash
#
# Server Setup Script for Material Outline Support
# 
# This script should be run on the production server (NOT in /pod folder)
# Usage: ssh to server, then run: bash server-setup.sh
#

set -e  # Exit on error

echo "=========================================="
echo "  Material Outline - Server Setup"
echo "=========================================="
echo ""

# Check if running as root or with sudo
if [ "$EUID" -ne 0 ]; then
    echo "⚠️  This script needs sudo privileges"
    echo "Please run: sudo bash server-setup.sh"
    exit 1
fi

# Detect OS
echo "1. Detecting operating system..."
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS=$ID
    VERSION=$VERSION_ID
    echo "   Detected: $NAME $VERSION"
else
    OS=$(uname -s)
    echo "   Detected: $OS"
fi
echo ""

# Install Inkscape based on OS
echo "2. Installing Inkscape..."

if [[ "$OS" == "ubuntu" ]] || [[ "$OS" == "debian" ]]; then
    echo "   Using apt-get (Ubuntu/Debian)..."
    apt-get update -qq
    apt-get install -y inkscape
    
elif [[ "$OS" == "centos" ]] || [[ "$OS" == "rhel" ]] || [[ "$OS" == "fedora" ]]; then
    echo "   Using yum (CentOS/RHEL)..."
    yum install -y epel-release
    yum install -y inkscape
    
else
    echo "   ❌ Unknown OS: $OS"
    echo "   Please install manually:"
    echo "      Ubuntu/Debian: apt-get install inkscape"
    echo "      CentOS/RHEL:   yum install inkscape"
    exit 1
fi
echo ""

# Verify installation
echo "3. Verifying Inkscape installation..."
if command -v inkscape &> /dev/null; then
    INKSCAPE_PATH=$(which inkscape)
    INKSCAPE_VERSION=$(inkscape --version 2>&1 | head -n1)
    echo "   ✅ Inkscape found at: $INKSCAPE_PATH"
    echo "   ✅ Version: $INKSCAPE_VERSION"
else
    echo "   ❌ Inkscape installation failed"
    exit 1
fi
echo ""

# Check web server user
echo "4. Checking web server user..."
WEB_USER=""
if id -u www-data &>/dev/null; then
    WEB_USER="www-data"
elif id -u apache &>/dev/null; then
    WEB_USER="apache"
elif id -u nginx &>/dev/null; then
    WEB_USER="nginx"
fi

if [ -n "$WEB_USER" ]; then
    echo "   Web server user: $WEB_USER"
    echo "   Testing Inkscape access for $WEB_USER..."
    if sudo -u $WEB_USER inkscape --version &>/dev/null; then
        echo "   ✅ $WEB_USER can execute Inkscape"
    else
        echo "   ⚠️  $WEB_USER cannot execute Inkscape (may need permissions fix)"
    fi
else
    echo "   ⚠️  Could not detect web server user"
    echo "   Common users: www-data, apache, nginx"
fi
echo ""

# Check PHP shell_exec
echo "5. Checking PHP configuration..."
DISABLED_FUNCTIONS=$(php -r "echo ini_get('disable_functions');")
if [[ -z "$DISABLED_FUNCTIONS" ]]; then
    echo "   ✅ No PHP functions are disabled"
elif [[ "$DISABLED_FUNCTIONS" == *"shell_exec"* ]]; then
    echo "   ❌ shell_exec is disabled in PHP"
    echo "   ⚠️  Inkscape cannot be used by PHP"
    echo ""
    echo "   FIX: Edit php.ini and remove 'shell_exec' from disable_functions"
    echo "   Then restart PHP-FPM: systemctl restart php-fpm"
else
    echo "   ✅ shell_exec is enabled"
fi
echo ""

# Installation complete
echo "=========================================="
echo "  ✅ INSTALLATION COMPLETE!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Test WordPress plugin:"
echo "   - Go to Orders page"
echo "   - Export PDF with material outline"
echo "   - Check console for: 'Generated PDF using Inkscape'"
echo ""
echo "2. Verify in CorelDRAW:"
echo "   - Open exported PDF in CorelDRAW"
echo "   - Material outline should be visible ✅"
echo ""
echo "If issues persist, check WordPress error logs:"
echo "   tail -f /path/to/wordpress/wp-content/debug.log"
echo ""
