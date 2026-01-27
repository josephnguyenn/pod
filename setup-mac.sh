#!/bin/bash
#
# Quick Setup Script for Mac (Local Development)
# This installs Inkscape on YOUR Mac, not in the /pod folder
#

set -e

echo "=========================================="
echo "  Material Outline - Mac Setup"
echo "=========================================="
echo ""

# Check if running on macOS
if [[ "$OSTYPE" != "darwin"* ]]; then
    echo "❌ This script is for macOS only!"
    echo "For Linux servers, use: server-setup.sh"
    exit 1
fi

echo "This will install Inkscape on your Mac using Homebrew."
echo ""

# Check if Homebrew is installed
if ! command -v brew &> /dev/null; then
    echo "📦 Homebrew not found. Installing Homebrew first..."
    /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
    
    # Add Homebrew to PATH (for Apple Silicon Macs)
    if [[ -f "/opt/homebrew/bin/brew" ]]; then
        eval "$(/opt/homebrew/bin/brew shellenv)"
    fi
else
    echo "✅ Homebrew is already installed"
fi

echo ""
echo "📦 Installing Inkscape via Homebrew..."
brew install inkscape

echo ""
echo "=========================================="
echo "  ✅ Installation Complete!"
echo "=========================================="
echo ""

# Verify installation
if command -v inkscape &> /dev/null; then
    INKSCAPE_PATH=$(which inkscape)
    INKSCAPE_VERSION=$(inkscape --version 2>&1 | head -n1)
    echo "✅ Inkscape installed successfully!"
    echo "   Path: $INKSCAPE_PATH"
    echo "   Version: $INKSCAPE_VERSION"
    echo ""
    
    # Test with check script
    if [[ -f "check-inkscape.php" ]]; then
        echo "Running verification test..."
        echo ""
        php check-inkscape.php
    fi
else
    echo "❌ Installation failed. Please install manually:"
    echo "   brew install inkscape"
fi

echo ""
echo "=========================================="
echo "Next steps:"
echo "1. Test material outline in WordPress"
echo "2. Export PDF and check console logs"
echo "3. Deploy to production server"
echo "=========================================="
