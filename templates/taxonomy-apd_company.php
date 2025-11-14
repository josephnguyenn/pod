<?php
/**
 * Template for displaying Company (apd_company) taxonomy archives
 * With Elementor Header and Footer
 */

// Get the current company term
$current_term = get_queried_object();
$company_name = $current_term->name;
$company_description = $current_term->description;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php
// Load Elementor Header (ID: 2849)
if (did_action('elementor/loaded')) {
    echo \Elementor\Plugin::$instance->frontend->get_builder_content_for_display(2849);
} elseif (function_exists('elementor_theme_do_location')) {
    elementor_theme_do_location('header');
} else {
    // Fallback to regular header
    get_header();
}
?>

<div class="apd-company-archive-wrapper">
    <div class="apd-company-header">
        <h1 class="apd-company-title"><?php echo esc_html($company_name); ?> Products</h1>
        <?php if ($company_description): ?>
            <div class="apd-company-description">
                <?php echo wp_kses_post($company_description); ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="apd-company-products">
        <?php
        // Use the shortcode to display products
        echo do_shortcode('[apd_products_by_company company="' . esc_attr($current_term->slug) . '"]');
        ?>
    </div>
</div>

<style>
    body {
        margin: 0;
        padding: 0;
    }
    
    .apd-company-archive-wrapper {
        max-width: 100%;
        margin: 0 auto;
        padding: 40px 20px;
    }
    
    .apd-company-header {
        max-width: 1200px;
        margin: 0 auto 40px;
        text-align: center;
    }
    
    .apd-company-title {
        font-size: 2.5em;
        margin-bottom: 20px;
        color: #333;
    }
    
    .apd-company-description {
        font-size: 1.1em;
        color: #666;
        max-width: 800px;
        margin: 0 auto;
        line-height: 1.6;
    }
    
    .apd-company-products {
        margin-top: 30px;
    }
</style>

<?php
// Load Elementor Footer (ID: 4674)
if (did_action('elementor/loaded')) {
    echo \Elementor\Plugin::$instance->frontend->get_builder_content_for_display(4674);
} elseif (function_exists('elementor_theme_do_location')) {
    elementor_theme_do_location('footer');
} else {
    // Fallback to regular footer
    get_footer();
}
?>

<?php wp_footer(); ?>
</body>
</html>
