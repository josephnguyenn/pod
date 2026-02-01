<?php
/**
 * Production Files block for order detail (admin).
 * Expects $order_id and $svg_download_url in scope.
 *
 * @package AdvancedProductDesigner
 */

if (!defined('ABSPATH') || !current_user_can('manage_options') || empty($svg_download_url)) {
    return;
}
?>
<div class="order-svg-download-section" style="margin: 20px 0; padding: 15px; background: #f0f6fc; border: 1px solid #0073aa; border-radius: 4px;">
    <h3 style="margin: 0 0 10px 0; font-size: 14px; color: #0073aa;">Production Files</h3>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <button type="button" class="button button-primary" onclick="downloadOrderSVG(<?php echo (int) $order_id; ?>)">
            <span class="dashicons dashicons-download" style="margin-top: 3px;"></span> Download Original SVG
        </button>
        <button type="button" class="button button-secondary" onclick="processCutReadySVG(<?php echo (int) $order_id; ?>)" style="background: #2271b1; color: white; border-color: #2271b1;">
            <span class="dashicons dashicons-media-code" style="margin-top: 3px;"></span> Export Cut-Ready SVG
        </button>
        <button type="button" class="button button-secondary" onclick="exportVectorPDF(<?php echo (int) $order_id; ?>)" style="background: #d63638; color: white; border-color: #d63638;">
            <span class="dashicons dashicons-media-document" style="margin-top: 3px;"></span> Export Vector PDF
        </button>
        <button type="button" class="button" onclick="testInkscape()" style="margin-left: 5px;">
            <span class="dashicons dashicons-yes-alt" style="margin-top: 3px;"></span> Test Inkscape
        </button>
    </div>
    <p class="description" style="margin: 10px 0 0 0;">Original SVG includes textures and effects. Cut-Ready SVG is optimized for CorelDRAW/cutting machines (removes textures, flattens layers). Vector PDF preserves all styles and material patterns — open in CorelDRAW to convert to editable vectors.</p>
    <p class="description" style="margin: 6px 0 0 0; color: #50575e;"><strong>CorelDRAW:</strong> Open SVG or PDF in CorelDRAW and use <em>Save As</em> .cdr for native editing.</p>
</div>
