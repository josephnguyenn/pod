<?php
/**
 * Template Service Class
 * 
 * Handles template loading and rendering with data injection
 * Provides clean separation between logic and presentation
 *
 * @package AdvancedProductDesigner
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class APD_Template_Service
{
    /**
     * Template directory path
     */
    private $template_dir;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->template_dir = APD_PLUGIN_PATH . 'templates/';
    }

    /**
     * Load and render a template file
     *
     * @param string $template_name Template name (without .php extension)
     * @param array $data Data to pass to template
     * @param bool $return Whether to return output instead of echoing
     * @return string|void Template output if $return is true
     */
    public function render($template_name, $data = array(), $return = false)
    {
        $template_file = $this->locate_template($template_name);

        if (!$template_file) {
            error_log('APD Template Service: Template not found - ' . $template_name);
            if ($return) {
                return '';
            }
            return;
        }

        // Extract data variables
        extract($data);

        if ($return) {
            ob_start();
            include $template_file;
            return ob_get_clean();
        } else {
            include $template_file;
        }
    }

    /**
     * Locate template file
     *
     * Checks for template in theme first, then plugin directory
     *
     * @param string $template_name Template name
     * @return string|false Template file path or false if not found
     */
    private function locate_template($template_name)
    {
        // Add .php extension if not present
        if (substr($template_name, -4) !== '.php') {
            $template_name .= '.php';
        }

        // Check in theme directory first (allows theme overrides)
        $theme_template = get_stylesheet_directory() . '/apd-templates/' . $template_name;
        if (file_exists($theme_template)) {
            return $theme_template;
        }

        // Check in parent theme directory
        $parent_template = get_template_directory() . '/apd-templates/' . $template_name;
        if (file_exists($parent_template)) {
            return $parent_template;
        }

        // Check in plugin templates directory
        $plugin_template = $this->template_dir . $template_name;
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }

        return false;
    }

    /**
     * Render admin template
     *
     * @param string $template_name Template name (without .php)
     * @param array $data Data to pass to template
     * @param bool $return Whether to return output
     * @return string|void Template output if $return is true
     */
    public function render_admin($template_name, $data = array(), $return = false)
    {
        return $this->render('admin/' . $template_name, $data, $return);
    }

    /**
     * Get template part (for partial templates)
     *
     * @param string $slug Template slug
     * @param string $name Template name (optional)
     * @param array $data Data to pass to template
     */
    public function get_template_part($slug, $name = null, $data = array())
    {
        $templates = array();

        if ($name) {
            $templates[] = "{$slug}-{$name}";
        }
        $templates[] = $slug;

        foreach ($templates as $template) {
            $located = $this->locate_template($template);
            if ($located) {
                extract($data);
                include $located;
                return;
            }
        }
    }

    /**
     * Check if template exists
     *
     * @param string $template_name Template name
     * @return bool True if template exists
     */
    public function template_exists($template_name)
    {
        return $this->locate_template($template_name) !== false;
    }

    /**
     * Get template path
     *
     * @param string $template_name Template name
     * @return string|false Template path or false
     */
    public function get_template_path($template_name)
    {
        return $this->locate_template($template_name);
    }

    /**
     * Render with caching (for heavy templates)
     *
     * @param string $template_name Template name
     * @param array $data Data to pass to template
     * @param int $cache_time Cache time in seconds
     * @return string Template output
     */
    public function render_cached($template_name, $data = array(), $cache_time = 3600)
    {
        $cache_key = 'apd_template_' . md5($template_name . serialize($data));
        $cached = get_transient($cache_key);

        if (false !== $cached) {
            return $cached;
        }

        $output = $this->render($template_name, $data, true);
        set_transient($cache_key, $output, $cache_time);

        return $output;
    }

    /**
     * Clear template cache
     *
     * @param string $template_name Optional template name to clear specific cache
     */
    public function clear_cache($template_name = null)
    {
        global $wpdb;

        if ($template_name) {
            // Clear specific template cache
            $cache_key = 'apd_template_' . md5($template_name);
            delete_transient($cache_key);
        } else {
            // Clear all template caches
            $wpdb->query(
                "DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE '_transient_apd_template_%' 
                OR option_name LIKE '_transient_timeout_apd_template_%'"
            );
        }
    }

    /**
     * Helper method to escape and sanitize data for templates
     *
     * @param mixed $value Value to sanitize
     * @param string $context Context (html, attr, url, js, textarea)
     * @return mixed Sanitized value
     */
    public static function esc($value, $context = 'html')
    {
        if (is_array($value)) {
            return array_map(function($v) use ($context) {
                return self::esc($v, $context);
            }, $value);
        }

        switch ($context) {
            case 'html':
                return esc_html($value);
            case 'attr':
                return esc_attr($value);
            case 'url':
                return esc_url($value);
            case 'js':
                return esc_js($value);
            case 'textarea':
                return esc_textarea($value);
            default:
                return esc_html($value);
        }
    }

    /**
     * Helper to format price for display
     *
     * @param float $price Price
     * @param string $currency Currency symbol
     * @return string Formatted price
     */
    public static function format_price($price, $currency = '$')
    {
        return $currency . number_format((float)$price, 2);
    }

    /**
     * Helper to format date
     *
     * @param string $date Date string
     * @param string $format Date format
     * @return string Formatted date
     */
    public static function format_date($date, $format = null)
    {
        if (!$format) {
            $format = get_option('date_format', 'Y-m-d');
        }
        return date_i18n($format, strtotime($date));
    }
}
