<?php
/**
 * SVG Utils
 *
 * Shared utilities for SVG processing: Inkscape detection, validation.
 *
 * @package AdvancedProductDesigner
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class APD_SVG_Utils
{
    /**
     * Find Inkscape binary path (server-side PDF/SVG processing).
     *
     * @return string|false Resolved path or false if not found
     */
    public static function find_inkscape()
    {
        $paths_checked = array();
        $custom_path = function_exists('get_option') ? trim((string) get_option('apd_inkscape_path', '')) : '';
        $possible_paths = array();
        if (!empty($custom_path)) {
            $possible_paths[] = $custom_path;
        }
        $possible_paths = array_merge(
            $possible_paths,
            array(
                '/usr/bin/inkscape',
                '/usr/local/bin/inkscape',
                '/opt/homebrew/bin/inkscape',
                'inkscape',
            )
        );

        foreach ($possible_paths as $path) {
            $paths_checked[] = $path;
            if (strpos($path, '/') === 0 && is_executable($path)) {
                error_log("APD Inkscape detection: using executable path: {$path}");
                return $path;
            }
            if (function_exists('shell_exec')) {
                $resolved = trim((string) @shell_exec('command -v ' . escapeshellarg($path) . ' 2>/dev/null'));
                if (empty($resolved)) {
                    $resolved = trim((string) @shell_exec('which ' . escapeshellarg($path) . ' 2>/dev/null'));
                }
                if (!empty($resolved) && is_executable($resolved)) {
                    error_log("APD Inkscape detection: resolved {$path} to {$resolved} via shell lookup");
                    return $resolved;
                }
            }
        }
        error_log('APD Inkscape detection: Inkscape not found. Paths checked: ' . implode(', ', $paths_checked));
        return false;
    }

    /**
     * Validate SVG content for security and basic requirements.
     *
     * @param string $svg_content Raw SVG string
     * @return array 'valid' => bool, 'error' => string (if invalid)
     */
    public static function validate_svg_content($svg_content)
    {
        $dangerous = array(
            '/<script/i',
            '/on\\w+\\s*=/i',
            '/javascript:/i',
            '/<iframe/i',
            '/<object/i',
            '/<embed/i',
        );
        foreach ($dangerous as $pattern) {
            if (preg_match($pattern, $svg_content)) {
                return array('valid' => false, 'error' => 'SVG contains potentially dangerous content');
            }
        }
        if (strlen($svg_content) < 50) {
            return array('valid' => false, 'error' => 'SVG content too small');
        }
        if (strlen($svg_content) > 10 * 1024 * 1024) {
            return array('valid' => false, 'error' => 'SVG content exceeds maximum size (10MB)');
        }
        return array('valid' => true);
    }
}
