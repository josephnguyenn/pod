<?php
/**
 * Portable Inkscape Handler
 * 
 * Alternative solutions for stroke-to-path conversion without system Inkscape:
 * 1. Download portable Inkscape binary to pod folder
 * 2. Use JavaScript-based path offsetting (client-side)
 * 3. Use PHP SVG manipulation libraries
 */

if (!defined('ABSPATH')) {
    exit;
}

class APD_Portable_Inkscape
{
    private $plugin;
    private $inkscape_path;
    private $use_portable = false;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
        $this->init();
    }

    /**
     * Initialize portable Inkscape
     */
    public function init()
    {
        // Check if portable Inkscape exists in pod folder
        $upload_dir = wp_upload_dir();
        $portable_dir = $upload_dir['basedir'] . '/apd-portable-inkscape';
        
        // Try to find portable Inkscape
        $this->inkscape_path = $this->find_portable_inkscape($portable_dir);
        
        if ($this->inkscape_path) {
            $this->use_portable = true;
            error_log("APD Portable: Using portable Inkscape at: " . $this->inkscape_path);
        }
    }

    /**
     * Find portable Inkscape binary
     */
    private function find_portable_inkscape($portable_dir)
    {
        // Check common portable locations
        $possible_paths = [
            $portable_dir . '/inkscape',
            $portable_dir . '/bin/inkscape',
            $portable_dir . '/inkscape.exe', // Windows
            __DIR__ . '/../../portable-inkscape/inkscape',
            __DIR__ . '/../../portable-inkscape/bin/inkscape',
        ];

        foreach ($possible_paths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        return false;
    }

    /**
     * Check if portable Inkscape is available
     */
    public function is_available()
    {
        return $this->use_portable && $this->inkscape_path;
    }

    /**
     * Get Inkscape path (system or portable)
     */
    public function get_inkscape_path()
    {
        // First try system Inkscape
        $system_paths = [
            '/usr/bin/inkscape',
            '/usr/local/bin/inkscape',
            '/opt/homebrew/bin/inkscape',
        ];

        foreach ($system_paths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        // Try which command
        $which = shell_exec('which inkscape 2>/dev/null');
        if (!empty($which)) {
            return trim($which);
        }

        // Fallback to portable
        if ($this->use_portable) {
            return $this->inkscape_path;
        }

        return false;
    }

    /**
     * Convert SVG with stroke-to-path using portable Inkscape
     */
    public function convert_stroke_to_path($svg_content, $order_id = 0)
    {
        $inkscape_path = $this->get_inkscape_path();
        
        if (!$inkscape_path) {
            error_log("APD Portable: No Inkscape available (system or portable)");
            return false;
        }

        // Save SVG temporarily
        $upload_dir = wp_upload_dir();
        $temp_svg_input = $upload_dir['path'] . '/temp-stroke-' . $order_id . '-' . time() . '.svg';
        $temp_svg_output = $upload_dir['path'] . '/temp-stroke-converted-' . $order_id . '-' . time() . '.svg';

        file_put_contents($temp_svg_input, $svg_content);

        // Use Inkscape to convert stroke-to-path
        $command = escapeshellarg($inkscape_path) . 
                   ' --actions="select-all:text;object-to-path;select-all;stroke-to-path"' .
                   ' --export-filename=' . escapeshellarg($temp_svg_output) .
                   ' --export-type=svg' .
                   ' ' . escapeshellarg($temp_svg_input) . 
                   ' 2>&1';

        error_log("APD Portable: Running command: " . $command);
        
        $output = shell_exec($command);
        $return_code = 0;
        exec($command . '; echo $?', $output_array, $return_code);

        if (file_exists($temp_svg_output) && filesize($temp_svg_output) > 0) {
            $converted_svg = file_get_contents($temp_svg_output);
            
            // Cleanup
            @unlink($temp_svg_input);
            @unlink($temp_svg_output);
            
            error_log("APD Portable: Successfully converted stroke-to-path");
            return $converted_svg;
        } else {
            error_log("APD Portable: Conversion failed. Return code: $return_code");
            error_log("APD Portable: Output: " . ($output ?: 'No output'));
            
            @unlink($temp_svg_input);
            if (file_exists($temp_svg_output)) @unlink($temp_svg_output);
            
            return false;
        }
    }
}
