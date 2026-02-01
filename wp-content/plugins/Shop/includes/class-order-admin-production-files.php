<?php
/**
 * Order Admin – Production Files block (Original SVG, Cut-Ready SVG, Vector PDF, Test Inkscape).
 *
 * @package AdvancedProductDesigner
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class APD_Order_Admin_Production_Files
{
    /**
     * Render the Production Files block for an order (admin).
     *
     * @param int    $order_id         Order post ID.
     * @param string $svg_download_url URL or data URL of the design SVG for download.
     */
    public static function render($order_id, $svg_download_url)
    {
        if (!current_user_can('manage_options') || empty($svg_download_url)) {
            return;
        }
        $order_id = (int) $order_id;
        include APD_PLUGIN_PATH . 'templates/admin/partials/order-production-files.php';
    }
}
