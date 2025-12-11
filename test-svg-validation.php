<?php
/**
 * Test script for SVG validation
 * Run this from command line: php test-svg-validation.php
 */

// Load WordPress if available, otherwise define minimal requirements
if (file_exists(__DIR__ . '/wp-load.php')) {
    require_once __DIR__ . '/wp-load.php';
} else {
    // Minimal setup for testing outside WordPress
    echo "Testing without WordPress environment...\n\n";
}

// Copy the validation function for standalone testing
function validate_svg_content($svg_content)
{
    if (empty($svg_content)) {
        return 'SVG file is empty';
    }
    
    // Normalize encoding to UTF-8 if file appears to be UTF-16
    if (strpos($svg_content, "\x00") !== false || preg_match('/encoding=["\']utf-16["\']i/', $svg_content)) {
        if (function_exists('mb_convert_encoding')) {
            $converted = @mb_convert_encoding($svg_content, 'UTF-8', 'UTF-16,UTF-16LE,UTF-16BE,UTF-8');
            if ($converted !== false) {
                $svg_content = $converted;
            }
        }
    }
    
    // Check for malicious content
    $dangerous_tags = array('script', 'embed', 'object', 'iframe', 'link');
    foreach ($dangerous_tags as $tag) {
        if (preg_match('/<' . $tag . '\b/i', $svg_content)) {
            return 'SVG contains potentially dangerous content: ' . $tag . ' tags are not allowed';
        }
    }
    
    // Check for external references that might not load
    if (preg_match('/xlink:href=["\']http/i', $svg_content)) {
        return 'SVG contains external links. Please embed all resources within the SVG file';
    }
    
    // Check if SVG uses <text> elements (which may have font issues)
    if (preg_match('/<text[\s>]/i', $svg_content)) {
        // Count text elements
        preg_match_all('/<text[\s>]/i', $svg_content, $text_matches);
        $text_count = count($text_matches[0]);
        
        // Check if there are also path elements (converted text)
        preg_match_all('/<path[\s>]/i', $svg_content, $path_matches);
        $path_count = count($path_matches[0]);
        
        // If there are ANY text elements, reject it (fonts may not be available)
        if ($text_count > 0) {
            return 'SVG contains ' . $text_count . ' text element(s) that may not display correctly. Please convert all text to paths in your SVG editor (Object to Path in Inkscape, or Create Outlines in Illustrator)';
        }
    }
    
    // Check for required SVG structure
    if (!preg_match('/<svg[\s>]/i', $svg_content)) {
        return 'Invalid SVG: Missing <svg> root element';
    }
    
    if (!preg_match('/<\/svg>/i', $svg_content)) {
        return 'Invalid SVG: Unclosed <svg> tag';
    }
    
    // Check viewBox or width/height attributes
    if (!preg_match('/viewBox=["\'][^"\']+["\']/i', $svg_content) && 
        !preg_match('/width=["\'][^"\']+["\']/i', $svg_content)) {
        return 'SVG missing viewBox or width/height attributes. Please ensure your SVG has proper dimensions defined';
    }
    
    return true;
}

echo "=================================================\n";
echo "SVG VALIDATION TEST\n";
echo "=================================================\n\n";

// Auto-scan for all SVG files in current directory
$svg_files = glob(__DIR__ . '/*.svg');
$test_files = [];

if (empty($svg_files)) {
    echo "No SVG files found in " . __DIR__ . "\n\n";
    echo "Usage: Drop your SVG files here and run:\n";
    echo "  php test-svg-validation.php\n\n";
    exit(0);
}

// Build test file list from found SVGs
foreach ($svg_files as $filepath) {
    $filename = basename($filepath);
    $test_files[$filename] = 'Testing file';
}

echo "Found " . count($test_files) . " SVG file(s) to test\n\n";

foreach ($test_files as $filename => $description) {
    echo "Testing: $filename [$description]\n";
    echo str_repeat('-', 50) . "\n";
    
    $filepath = __DIR__ . '/' . $filename;
    
    if (!file_exists($filepath)) {
        echo "❌ File not found: $filepath\n\n";
        continue;
    }
    
    $svg_content = file_get_contents($filepath);
    $result = validate_svg_content($svg_content);
    
    if ($result === true) {
        echo "✅ PASSED - File is valid\n";
        
        // Show some stats
        preg_match_all('/<text[\s>]/i', $svg_content, $text_matches);
        preg_match_all('/<path[\s>]/i', $svg_content, $path_matches);
        echo "   Stats: " . count($text_matches[0]) . " text elements, " . count($path_matches[0]) . " path elements\n";
    } else {
        echo "❌ FAILED - $result\n";
    }
    
    echo "\n";
}

echo "=================================================\n";
echo "Test complete!\n";
echo "=================================================\n\n";

echo "Expected Results:\n";
echo "- test-svg-bad.svg: Should FAIL (contains text elements)\n";
echo "- test-svg-good.svg: Should PASS (only path elements)\n";
echo "- Client file: Should PASS (text converted to paths)\n";
?>
