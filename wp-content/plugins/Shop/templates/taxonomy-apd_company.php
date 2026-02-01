<?php
/**
 * Template for displaying Company (apd_company) taxonomy archives
 * With Elementor Header and Footer (uses APD_Header_Footer helper)
 */

// Get the current company term
$current_term = get_queried_object();
$company_name = $current_term->name;
$company_description = $current_term->description;

APD_Header_Footer::page_wrapper_start();
APD_Header_Footer::output_site_header();
?>

<div class="apd-company-archive-wrapper">
    <div class="apd-company-products">
        <?php
        echo do_shortcode('[apd_products_by_company company="' . esc_attr($current_term->slug) . '" hide_header="false"]');
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
        color: white !important;
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
APD_Header_Footer::output_site_footer();
APD_Header_Footer::page_wrapper_end();
