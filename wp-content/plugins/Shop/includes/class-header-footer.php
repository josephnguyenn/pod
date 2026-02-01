<?php
/**
 * Site header and footer output for APD page templates.
 * Outputs same Elementor header/footer as company taxonomy (IDs configurable via options).
 *
 * @package AdvancedProductDesigner
 */

if (!defined('ABSPATH')) {
    exit;
}

class APD_Header_Footer
{
    /** Default Elementor header template ID (same as company page). */
    const DEFAULT_HEADER_ID = 2849;

    /** Default Elementor footer template ID (same as company page). */
    const DEFAULT_FOOTER_ID = 4674;

    /**
     * Get Elementor header template ID (configurable via option).
     *
     * @return int
     */
    public static function get_header_id()
    {
        return (int) get_option('apd_elementor_header_id', self::DEFAULT_HEADER_ID);
    }

    /**
     * Get Elementor footer template ID (configurable via option).
     *
     * @return int
     */
    public static function get_footer_id()
    {
        return (int) get_option('apd_elementor_footer_id', self::DEFAULT_FOOTER_ID);
    }

    /**
     * Output site header (Elementor template or theme fallback).
     * Same logic as taxonomy-apd_company.php.
     */
    public static function output_site_header()
    {
        if (did_action('elementor/loaded')) {
            echo \Elementor\Plugin::$instance->frontend->get_builder_content_for_display(self::get_header_id());
        } elseif (function_exists('elementor_theme_do_location')) {
            elementor_theme_do_location('header');
        } else {
            get_header();
        }
    }

    /**
     * Output site footer (Elementor template or theme fallback).
     * Same logic as taxonomy-apd_company.php.
     */
    public static function output_site_footer()
    {
        if (did_action('elementor/loaded')) {
            echo \Elementor\Plugin::$instance->frontend->get_builder_content_for_display(self::get_footer_id());
        } elseif (function_exists('elementor_theme_do_location')) {
            elementor_theme_do_location('footer');
        } else {
            get_footer();
        }
    }

    /**
     * Output document start: doctype, html, head, body open.
     * Call before page content when template is the full page (no theme wrapper).
     */
    public static function page_wrapper_start()
    {
        ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open();
    }

    /**
     * Output document end: wp_footer, close body and html.
     * Call after page content when template is the full page.
     */
    public static function page_wrapper_end()
    {
        wp_footer();
        ?>
</body>
</html>
        <?php
    }
}
