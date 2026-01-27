<?php
/**
 * Inkscape Installation Checker
 * 
 * Run this file to check if Inkscape is available on the server.
 * 
 * Usage:
 * 1. Upload to WordPress root or plugin folder
 * 2. Access via browser: https://yoursite.com/check-inkscape.php
 * 3. Or run via command line: php check-inkscape.php
 */

// Prevent direct access from web (security)
if (php_sapi_name() !== 'cli' && !defined('WP_DEBUG')) {
    // Only allow in CLI mode or when WP_DEBUG is on
    die('Access denied. Run via command line: php check-inkscape.php');
}

echo "==========================================\n";
echo "   INKSCAPE INSTALLATION CHECKER\n";
echo "==========================================\n\n";

// 1. Check if shell_exec is available
echo "1. Checking PHP shell_exec function...\n";
if (function_exists('shell_exec')) {
    echo "   ✅ shell_exec is ENABLED\n\n";
} else {
    echo "   ❌ shell_exec is DISABLED\n";
    echo "   ⚠️  Cannot check Inkscape - shell_exec is required\n";
    echo "   📝 Edit php.ini and remove 'shell_exec' from disable_functions\n\n";
    exit(1);
}

// 2. Check which inkscape
echo "2. Checking 'which inkscape' command...\n";
$which_result = shell_exec('which inkscape 2>&1');
if (!empty($which_result) && strpos($which_result, 'not found') === false) {
    echo "   ✅ Inkscape found at: " . trim($which_result) . "\n\n";
} else {
    echo "   ❌ Inkscape NOT found in PATH\n";
    echo "   Result: " . ($which_result ?: "(empty)") . "\n\n";
}

// 3. Check inkscape version
echo "3. Checking Inkscape version...\n";
$version_result = shell_exec('inkscape --version 2>&1');
if (!empty($version_result) && strpos($version_result, 'Inkscape') !== false) {
    echo "   ✅ " . trim($version_result) . "\n\n";
} else {
    echo "   ❌ Cannot get Inkscape version\n";
    echo "   Result: " . ($version_result ?: "(empty)") . "\n\n";
}

// 4. Check common installation paths
echo "4. Checking common Inkscape installation paths...\n";
$possible_paths = [
    '/usr/bin/inkscape',
    '/usr/local/bin/inkscape',
    '/opt/homebrew/bin/inkscape',
    '/opt/local/bin/inkscape',
];

$found_path = null;
foreach ($possible_paths as $path) {
    $exists = file_exists($path);
    $executable = $exists ? is_executable($path) : false;
    
    if ($exists && $executable) {
        echo "   ✅ FOUND: $path (executable)\n";
        $found_path = $path;
    } elseif ($exists) {
        echo "   ⚠️  FOUND: $path (but not executable)\n";
    } else {
        echo "   ❌ NOT FOUND: $path\n";
    }
}
echo "\n";

// 5. Test Inkscape execution
if ($found_path) {
    echo "5. Testing Inkscape execution...\n";
    $test_result = shell_exec("$found_path --version 2>&1");
    if (!empty($test_result)) {
        echo "   ✅ Inkscape executes successfully\n";
        echo "   Output: " . trim($test_result) . "\n\n";
    } else {
        echo "   ❌ Inkscape found but cannot execute\n\n";
    }
} else {
    echo "5. Inkscape not found - cannot test execution\n\n";
}

// 6. Check server environment
echo "6. Server environment information...\n";
echo "   OS: " . PHP_OS . "\n";
echo "   PHP Version: " . PHP_VERSION . "\n";
echo "   PHP SAPI: " . php_sapi_name() . "\n";
echo "   Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "   Web Server User: " . get_current_user() . "\n\n";

// 7. Check disable_functions
echo "7. Checking disabled PHP functions...\n";
$disabled = ini_get('disable_functions');
if (empty($disabled)) {
    echo "   ✅ No functions are disabled\n\n";
} else {
    echo "   ⚠️  Disabled functions: $disabled\n";
    if (strpos($disabled, 'shell_exec') !== false) {
        echo "   ❌ shell_exec is DISABLED - Inkscape cannot be used\n";
    }
    echo "\n";
}

// Final verdict
echo "==========================================\n";
echo "   FINAL VERDICT\n";
echo "==========================================\n\n";

if ($found_path && !empty($version_result) && strpos($version_result, 'Inkscape') !== false) {
    echo "✅ ✅ ✅ INKSCAPE IS INSTALLED AND WORKING! ✅ ✅ ✅\n\n";
    echo "Material outline will work in CorelDRAW PDF exports!\n\n";
    echo "Path: $found_path\n";
    echo "Version: " . trim($version_result) . "\n\n";
} else {
    echo "❌ ❌ ❌ INKSCAPE IS NOT AVAILABLE ❌ ❌ ❌\n\n";
    echo "Material outline will NOT work in CorelDRAW PDF exports.\n\n";
    echo "INSTALLATION STEPS:\n";
    echo "-------------------\n";
    echo "1. SSH into your server:\n";
    echo "   ssh user@your-server.com\n\n";
    echo "2. Install Inkscape:\n";
    echo "   # For Ubuntu/Debian:\n";
    echo "   sudo apt-get update\n";
    echo "   sudo apt-get install -y inkscape\n\n";
    echo "   # For CentOS/RHEL:\n";
    echo "   sudo yum install -y epel-release\n";
    echo "   sudo yum install -y inkscape\n\n";
    echo "3. Verify installation:\n";
    echo "   inkscape --version\n\n";
    echo "4. Run this script again to verify\n\n";
}

echo "==========================================\n";
echo "For more information, see:\n";
echo "- MATERIAL-OUTLINE-CORELDRAW-ANALYSIS.md\n";
echo "- README-MATERIAL-OUTLINE-FIX.md\n";
echo "==========================================\n";
?>
