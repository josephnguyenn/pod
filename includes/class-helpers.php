<?php

/**
 * Class APD_Helpers
 * 
 * Shared helper functions for the plugin
 *
 * @package AdvancedProductDesigner
 */

if (!defined('ABSPATH')) {
    exit;
}

class APD_Helpers
{
    /**
     * Get default colors
     * 
     * @return array
     */
    public static function get_default_colors()
    {
        return array(
            'black' => '#000000',
            'yellow' => '#FFFF00',
            'dark-red' => '#8B0000',
            'orange' => '#FFA500',
            'light-blue' => '#87CEEB',
            'light-green' => '#90EE90',
            'purple' => '#800080',
            'light-grey' => '#D3D3D3',
            'brown' => '#A52A2A',
            'bright-yellow' => '#FFD700',
            'dark-green' => '#006400',
            'light-purple' => '#DDA0DD'
        );
    }

    /**
     * Get materials
     * 
     * @param int $template_id Optional template ID to filter by category
     * @return array
     */
    public static function get_materials($template_id = 0)
    {
        $materials = array();
        $allowed_categories = array();
        
        // Get allowed categories for this template if template_id is provided
        if ($template_id > 0) {
            $allowed_categories = get_post_meta($template_id, '_apd_allowed_material_categories', true);
            if (!is_array($allowed_categories)) {
                $allowed_categories = array();
            }
        }
        
        // Check if we should filter by categories
        $filter_by_category = !empty($allowed_categories) && !in_array('all', $allowed_categories);

        // Get materials from database first
        $db_materials = get_option('apd_materials', array());

        if (!empty($db_materials)) {
            foreach ($db_materials as $material) {
                // Check if material should be included based on category filtering
                if ($filter_by_category) {
                    $material_category = isset($material['category']) ? $material['category'] : 'Uncategorized';
                    if (!in_array($material_category, $allowed_categories)) {
                        continue; // Skip this material
                    }
                }
                
                // Backward compatibility: ensure price exists
                $price = isset($material['price']) ? floatval($material['price']) : 0;
                $materials[$material['name']] = array(
                    'url' => $material['url'],
                    'price' => $price,
                    'category' => isset($material['category']) ? $material['category'] : 'Uncategorized'
                );
            }
        } else {
            // Fallback: Use plugin directory if no database materials
            $plugin_dir = APD_PLUGIN_PATH;
            $material_path = $plugin_dir . 'uploads/material/';

            if (is_dir($material_path)) {
                $files = glob($material_path . '*.{png,jpg,jpeg}', GLOB_BRACE);
                foreach ($files as $file) {
                    $name = pathinfo($file, PATHINFO_FILENAME);
                    $materials[$name] = array(
                        'url' => APD_PLUGIN_URL . 'uploads/material/' . basename($file),
                        'price' => 0
                    );
                }
            }
        }

        // Final fallback materials if none found
        if (empty($materials)) {
            $materials = array(
                'Brush_gold' => array(
                    'url' => APD_PLUGIN_URL . 'uploads/material/Brush_gold.png',
                    'price' => 0
                ),
                'Diamond_Plate' => array(
                    'url' => APD_PLUGIN_URL . 'uploads/material/Diamond_Plate.png',
                    'price' => 0
                ),
                'Engine_turn_gold' => array(
                    'url' => APD_PLUGIN_URL . 'uploads/material/Engine_turn_gold.png',
                    'price' => 0
                ),
                'Florentine_Silver' => array(
                    'url' => APD_PLUGIN_URL . 'uploads/material/Florentine_Silver.png',
                    'price' => 0
                ),
                'gold' => array(
                    'url' => APD_PLUGIN_URL . 'uploads/material/gold.png',
                    'price' => 0
                )
            );
        }

        return $materials;
    }

    /**
     * Helper function to process SVG content for dynamic coloring
     * 
     * @param string $logo_path Absolute path to the SVG file
     * @return string|false
     */
    public static function get_processed_svg_content($logo_path)
    {
        error_log('APD: Checking SVG path: ' . $logo_path);
        
        if (!file_exists($logo_path)) {
            error_log('APD: SVG file does not exist at: ' . $logo_path);
            return false;
        }

        $svg_content = file_get_contents($logo_path);
        if ($svg_content === false || $svg_content === '') {
            error_log('APD: Failed to read SVG content or file is empty');
            return false;
        }
        
        error_log('APD: SVG file loaded, size: ' . strlen($svg_content) . ' bytes');
        error_log('APD: First 100 chars: ' . substr($svg_content, 0, 100));

        // Normalize encoding to UTF-8 if file appears to be UTF-16
        if (strpos($svg_content, "\x00") !== false || preg_match('/encoding=["\']utf-16["\']/i', $svg_content)) {
            if (function_exists('mb_convert_encoding')) {
                $converted = @mb_convert_encoding($svg_content, 'UTF-8', 'UTF-16,UTF-16LE,UTF-16BE,UTF-8');
                if ($converted !== false) {
                    $svg_content = $converted;
                }
            }
        }

        // Strip UTF-8 BOM and XML prolog/DOCTYPE which can break innerHTML parsing
        $svg_content = preg_replace('/^\xEF\xBB\xBF/', '', $svg_content); // UTF-8 BOM
        $svg_content = preg_replace('/<\?xml[^>]*\?>/i', '', $svg_content);
        $svg_content = preg_replace('/<!DOCTYPE[^>]*>/i', '', $svg_content);
        
        // Trim whitespace
        $svg_content = trim($svg_content);

        // Validate it starts with <svg
        if (!preg_match('/^<svg[\s>]/i', $svg_content)) {
            error_log('APD: Invalid SVG - does not start with <svg tag. First 200 chars: ' . substr($svg_content, 0, 200));
            return false;
        }

        // Keep only the <svg>...</svg> fragment
        if (preg_match('/<svg[\s\S]*<\/svg>/i', $svg_content, $m)) {
            $svg_content = $m[0];
        } else {
            error_log('APD: Could not find complete <svg>...</svg> tags');
            return false;
        }

        // Ensure xmlns exists for robust DOM parsing
        if (stripos($svg_content, 'xmlns=') === false) {
            $svg_content = preg_replace('/<svg\b/i', '<svg xmlns="http://www.w3.org/2000/svg"', $svg_content, 1);
        }

        // Remove any existing class attributes from SVG tag
        $svg_content = preg_replace('/<svg([^>]*?)class=\"[^\"]*\"([^>]*?)>/', '<svg$1$2>', $svg_content);

        // Add our custom class
        $svg_content = str_replace('<svg', '<svg class="fsc-logo-svg"', $svg_content);

        // Add outline filter if not present
        if (strpos($svg_content, 'id="fsc-outline"') === false) {
            $svg_content = str_replace('<defs>', '<defs><filter id="fsc-outline"><feMorphology operator="dilate" radius="2"/><feComposite operator="out" in="SourceGraphic"/></filter></defs>', $svg_content);
        }
        
        error_log('APD: Processed SVG successfully, final size: ' . strlen($svg_content) . ' bytes');

        return $svg_content ?: false;
    }

    /**
     * Get logo SVG
     * 
     * @return string|false
     */
    public static function get_logo_svg()
    {
        $plugin_dir = APD_PLUGIN_PATH;
        $logo_path = $plugin_dir . 'uploads/object/Logo-PNG.svg';
        return self::get_processed_svg_content($logo_path);
    }
}
