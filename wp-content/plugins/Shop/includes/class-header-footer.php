<?php
/**
 * Site header and footer output for APD page templates.
 * Outputs same Elementor header/footer (IDs configurable via options).
 * Injects only on homepage and Elementor Canvas pages (no theme header/footer) to avoid duplicate.
 *
 * @package AdvancedProductDesigner
 */

if (!defined('ABSPATH')) {
    exit;
}

class APD_Header_Footer
{
    /** Flag: we are inside an APD template that already outputs header/footer (don't double-inject). */
    const IN_WRAPPER_FLAG = 'apd_header_footer_in_wrapper';

    /** Flag: this request should get injected header/footer (e.g. Elementor Canvas). */
    const INJECT_FLAG = 'apd_inject_header_footer';
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
     * Mark that we are inside an APD template that outputs its own header/footer.
     * Call at the start of page_wrapper_start so global injector skips this request.
     */
    public static function set_in_wrapper()
    {
        if (!defined(self::IN_WRAPPER_FLAG)) {
            define(self::IN_WRAPPER_FLAG, true);
        }
    }

    /**
     * Whether we are inside an APD full template (wrapper already has header/footer).
     *
     * @return bool
     */
    public static function is_in_wrapper()
    {
        return defined(self::IN_WRAPPER_FLAG) && constant(self::IN_WRAPPER_FLAG);
    }

    /**
     * Determine if this request should get header/footer injected.
     * Only inject when theme does not output them: homepage or Elementor Canvas template.
     * This avoids duplicate header/footer on pages that already have theme header/footer.
     * Filter 'apd_inject_header_footer' can override (e.g. force true/false).
     *
     * @return bool
     */
    public static function should_inject_header_footer()
    {
        if (is_admin() || !did_action('wp')) {
            return false;
        }
        if (self::is_in_wrapper()) {
            return false;
        }
        $inject = false;
        if (is_front_page()) {
            $inject = true;
        } else {
            $post_id = is_singular() ? get_queried_object_id() : 0;
            if ($post_id) {
                $template = get_post_meta($post_id, '_wp_page_template', true);
                if ($template === 'elementor_canvas' || $template === 'elementor_canvas.php') {
                    $inject = true;
                }
            }
        }
        return apply_filters('apd_inject_header_footer', $inject);
    }

    /**
     * Register hooks to inject header/footer (only on homepage and Canvas pages to avoid duplicate).
     */
    public static function init_global_injector()
    {
        add_action('wp', array(__CLASS__, 'maybe_set_inject_flag'), 5);
        add_action('wp_body_open', array(__CLASS__, 'inject_site_header'), 1);
        add_action('wp_footer', array(__CLASS__, 'inject_site_footer'), 1);
    }

    /**
     * Set inject flag on wp so we know whether to inject in wp_body_open / wp_footer.
     */
    public static function maybe_set_inject_flag()
    {
        if (self::should_inject_header_footer()) {
            if (!defined(self::INJECT_FLAG)) {
                define(self::INJECT_FLAG, true);
            }
        }
    }

    /**
     * Output site header at wp_body_open when inject flag is set.
     */
    public static function inject_site_header()
    {
        if (defined(self::INJECT_FLAG) && constant(self::INJECT_FLAG)) {
            self::output_site_header();
        }
    }

    /**
     * Output site footer at wp_footer when inject flag is set.
     */
    public static function inject_site_footer()
    {
        if (defined(self::INJECT_FLAG) && constant(self::INJECT_FLAG)) {
            self::output_site_footer();
        }
    }

    /**
     * Output document start: doctype, html, head, body open.
     * Call before page content when template is the full page (no theme wrapper).
     */
    public static function page_wrapper_start()
    {
        self::set_in_wrapper();
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
