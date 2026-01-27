<?php
/**
 * Test Inkscape execution from PHP
 * Upload to server and run: php test-inkscape-exec.php
 */

echo "Testing Inkscape execution from PHP...\n\n";

// Test 1: shell_exec available?
if (!function_exists('shell_exec')) {
    echo "❌ FAILED: shell_exec is disabled\n";
    echo "   Edit php.ini and remove shell_exec from disable_functions\n";
    exit(1);
}
echo "✅ OK: shell_exec is available\n";

// Test 2: Find Inkscape
$paths = ['/usr/bin/inkscape', '/usr/local/bin/inkscape', '/opt/homebrew/bin/inkscape'];
$found = null;
foreach ($paths as $p) {
    if (file_exists($p)) {
        echo "✅ OK: Found Inkscape at: $p\n";
        $found = $p;
        break;
    }
}

if (!$found) {
    echo "❌ FAILED: Inkscape not found in common paths\n";
    echo "   Try: which inkscape\n";
    exit(1);
}

// Test 3: Execute Inkscape
$version = shell_exec(escapeshellarg($found) . ' --version 2>&1');
if ($version) {
    echo "✅ OK: Inkscape version: " . trim($version) . "\n\n";
} else {
    echo "❌ FAILED: Cannot execute Inkscape\n";
    exit(1);
}

// Test 4: Test text-to-path conversion
echo "Testing text-to-path conversion...\n";
$test_svg = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><text x="10" y="20" fill="#000">Test</text></svg>';
$temp_in = sys_get_temp_dir() . '/test-in-' . time() . '.svg';
$temp_out = sys_get_temp_dir() . '/test-out-' . time() . '.svg';
file_put_contents($temp_in, $test_svg);

$cmd = escapeshellarg($found) . 
       ' --actions="select-all:text;object-to-path"' .
       ' --export-filename=' . escapeshellarg($temp_out) .
       ' --export-type=svg ' . escapeshellarg($temp_in) . ' 2>&1';

echo "Running command: $cmd\n";
$result = shell_exec($cmd);

if (file_exists($temp_out) && filesize($temp_out) > 0) {
    $converted = file_get_contents($temp_out);
    $has_path = strpos($converted, '<path') !== false;
    $has_text = strpos($converted, '<text') !== false;
    
    echo "✅ OK: Conversion test successful\n";
    echo "   - Paths created: " . ($has_path ? "YES" : "NO") . "\n";
    echo "   - Text remaining: " . ($has_text ? "YES (not fully converted)" : "NO (fully converted)") . "\n";
    
    @unlink($temp_in);
    @unlink($temp_out);
    
    if ($has_path) {
        echo "\n✅ ✅ ✅ SUCCESS: Inkscape is working correctly! ✅ ✅ ✅\n";
        echo "\nPDF export will use server-side Inkscape processing.\n";
        echo "Material outlines will be preserved for CorelDRAW.\n";
    } else {
        echo "\n⚠️  WARNING: Text converted but no paths created\n";
        echo "   This may indicate an issue with Inkscape command\n";
    }
} else {
    echo "❌ FAILED: Inkscape conversion test failed\n";
    if ($result) {
        echo "   Output: " . substr($result, 0, 500) . "\n";
    }
    echo "\n   This means PDF export will use client-side fallback.\n";
    echo "   Material outlines may not display in CorelDRAW.\n";
}
?>
