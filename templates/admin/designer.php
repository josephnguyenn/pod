<?php
/**
 * Template Designer Page
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get template_id from global variables (set by designer_page()) or from URL
$template_id = isset($GLOBALS['apd_template_id']) ? $GLOBALS['apd_template_id'] : (isset($_GET['template_id']) ? intval($_GET['template_id']) : 0);
$product_id = isset($GLOBALS['apd_product_id']) ? $GLOBALS['apd_product_id'] : (isset($_GET['product_id']) ? intval($_GET['product_id']) : 0);
$template = isset($GLOBALS['apd_template']) ? $GLOBALS['apd_template'] : null;


// Template creation is now handled in designer_page() function to avoid headers already sent error

// Only get template if not already set from global variables
if (!$template && $template_id > 0) {
    $template = get_post($template_id);
}
$width = get_post_meta($template_id, '_apd_template_width', true) ?: 800;
$height = get_post_meta($template_id, '_apd_template_height', true) ?: 600;
$bg_type = get_post_meta($template_id, '_apd_template_bg_type', true) ?: 'color';
$bg_color = get_post_meta($template_id, '_apd_template_bg_color', true) ?: '#ffffff';
$bg_image = get_post_meta($template_id, '_apd_template_bg_image', true);
$template_data = get_post_meta($template_id, '_apd_template_data', true) ?: '{}';

// Get materials from variable passed by designer_page() function
if (!isset($materials)) {
    $materials = array();
}
?>

<div class="wrap apd-designer-wrap">
    <div class="apd-designer-header">
        <div class="apd-designer-title">
            <h1>Template Designer</h1>
            <?php if ($product_id): ?>
                <?php $product = get_post($product_id); ?>
                <p class="apd-product-info">Designing template for: <strong><?php echo esc_html($product->post_title); ?></strong></p>
            <?php endif; ?>
        </div>
        <div class="apd-designer-actions">
            <button id="undo-btn" class="button" title="Undo (Ctrl+Z)">↶ Undo</button>
            <button id="save-template" class="button button-primary">Save Template</button>
            <?php if ($product_id): ?>
                <a href="<?php echo admin_url('admin.php?page=apd-products'); ?>" class="button">Back to Products</a>
            <?php else: ?>
                <a href="<?php echo admin_url('admin.php?page=apd-templates'); ?>" class="button">Back to Templates</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="apd-designer-container">
        <div class="apd-designer-sidebar">
            <div class="apd-sidebar-section">
                <h3>Canvas Settings</h3>
                <div class="apd-form-group">
                    <label>Width: <input type="number" id="canvas-width" value="<?php echo $width; ?>" min="100" max="2000"></label>
                    <label>Height: <input type="number" id="canvas-height" value="<?php echo $height; ?>" min="100" max="2000"></label>
                </div>

                <div class="apd-form-group">
                    <label>Background Type:</label>
                    <select id="background-type">
                        <option value="color" <?php selected($bg_type, 'color'); ?>>Solid Color</option>
                        <option value="image" <?php selected($bg_type, 'image'); ?>>Image</option>
                        <option value="gradient" <?php selected($bg_type, 'gradient'); ?>>Gradient</option>
                            </select>
                        </div>

                <div id="bg-color-control" class="apd-form-group" style="<?php echo $bg_type === 'color' ? '' : 'display:none;'; ?>">
                    <label>Background Color:</label>
                    <input type="color" id="background-color" value="<?php echo $bg_color; ?>" style="width: 100%; height: 40px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">
                        </div>

                <div id="bg-image-control" class="apd-form-group" style="<?php echo $bg_type === 'image' ? '' : 'display:none;'; ?>">
                    <label>Background Image:</label>
                    <input type="file" id="background-image-upload" accept="image/*" style="margin-bottom: 10px;">
                    <div id="bg-image-preview" style="margin-top: 10px;">
                        <?php if ($bg_image): ?>
                            <img src="<?php echo esc_url($bg_image); ?>" style="max-width: 200px; max-height: 100px; border: 1px solid #ddd;">
                        <?php endif; ?>
                                </div>
                            </div>
                        </div>

            <div class="apd-sidebar-section">
                <h3>Add Elements</h3>
                <div id="element-counter" style="margin-bottom: 15px; padding: 10px; background: #f0f0f0; border-radius: 4px; font-size: 12px; color: #666;">
                    <strong>Elements on canvas:</strong> <span id="element-count">0</span>
                </div>
                <div class="apd-element-buttons">
                    <button class="apd-add-element" data-type="logo">
                        <span class="dashicons dashicons-format-image"></span>
                        Logo Area
                                </button>
                    <button class="apd-add-element" data-type="text">
                        <span class="dashicons dashicons-text"></span>
                        Text Field
                                </button>
                    <button class="apd-add-element" data-type="image">
                        <span class="dashicons dashicons-format-gallery"></span>
                        Image Field
                            </button>
                        </div>
                    </div>

            <?php
            // Load uploaded fonts @font-face rules (hidden, just for CSS)
            $uploaded_fonts = get_option('apd_uploaded_fonts', array());
            if (!empty($uploaded_fonts)) {
                echo '<style id="apd-designer-fonts">';
                foreach ($uploaded_fonts as $font) {
                    if (!empty($font['family']) && !empty($font['url'])) {
                        $family_css = esc_attr($font['family']);
                        $url_css = esc_url($font['url']);
                        echo "@font-face{font-family:'{$family_css}';src:url('{$url_css}') format('truetype');font-display:swap;}\n";
                    }
                }
                echo '</style>';
            }
            ?>

            <div class="apd-sidebar-section">
                <h3>Materials</h3>
                <div class="apd-materials-list">
                    <?php foreach ($materials as $name => $url): ?>
                        <div class="apd-material-item" data-name="<?php echo esc_attr($name); ?>" data-url="<?php echo esc_url($url); ?>">
                            <img src="<?php echo esc_url($url); ?>" alt="<?php echo esc_attr($name); ?>" title="<?php echo esc_attr($name); ?>">
                            </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="apd-sidebar-section">
                <h3>Color Palette</h3>
                <div class="apd-color-palette" id="color-palette">
                    <!-- Colors will be added dynamically -->
                </div>
                <div class="apd-add-color-section">
                    <button id="add-color-btn" class="apd-add-color-btn" title="Add Color">
                        <span class="dashicons dashicons-plus-alt"></span>
                        Add Color
                    </button>
                </div>
            </div>

            <div class="apd-sidebar-section">
                <h3>Customizer Options</h3>
                <div class="apd-form-group">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" id="fill-logo-with-color" style="width: auto; margin: 0;">
                        <span>Fill logo with selected print color</span>
                    </label>
                    <p style="margin: 8px 0 0 0; font-size: 11px; color: #666; line-height: 1.4;">
                        When enabled, the logo will be filled with the selected print color along with text. When disabled, only text will be filled with the color.
                    </p>
                </div>
            </div>
        </div>

        <div class="apd-designer-canvas-container">
            <div class="apd-canvas-wrapper">
                <div id="apd-canvas" class="apd-canvas" style="width: <?php echo $width; ?>px; height: <?php echo $height; ?>px; background: <?php echo $bg_type === 'color' ? $bg_color : '#f0f0f0'; ?>;">
                    <?php if ($bg_type === 'image' && $bg_image): ?>
                        <div class="apd-canvas-bg-image" style="background-image: url('<?php echo esc_url($bg_image); ?>');"></div>
                    <?php endif; ?>
                    </div>
                </div>
            </div>

        <div class="apd-designer-properties">
            <div class="apd-properties-header">
                <h3>Element Properties</h3>
                <span id="selected-element-name">No element selected</span>
        </div>

            <div id="element-properties" class="apd-properties-content">
                <p class="apd-no-selection">Select an element to edit its properties</p>
            </div>
        </div>
    </div>
</div>

<!-- Color Picker Modal -->
<div id="color-picker-modal" class="apd-modal" style="display: none;">
    <div class="apd-modal-content">
        <div class="apd-modal-header">
            <h3>Choose Color</h3>
            <button class="apd-modal-close">&times;</button>
        </div>
        <div class="apd-modal-body">
            <div class="apd-color-picker-section">
                <label>Select Color:</label>
                <input type="color" id="color-picker-input" value="#FFFFFF" style="width: 100%; height: 50px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">
            </div>
            <div class="apd-color-name-section">
                <label>Color Name:</label>
                <input type="text" id="color-name-input" value="" placeholder="Enter color name" style="width: 100%; height: 34px; border: 1px solid #ddd; border-radius: 4px; padding: 6px 8px;">
            </div>
            <div class="apd-color-preview">
                <label>Preview:</label>
                <div id="color-preview" style="width: 100%; height: 40px; border: 1px solid #ddd; border-radius: 4px; background-color: #FFFFFF;"></div>
            </div>
        </div>
        <div class="apd-modal-footer">
            <button id="delete-color-btn" class="button" style="display:none;background:#dc3232;color:#fff;border:none;">Delete</button>
            <button id="save-color-btn" class="button button-primary">Save Color</button>
            <button id="cancel-color-btn" class="button">Cancel</button>
        </div>
    </div>
</div>

<input type="hidden" id="template-id" value="<?php echo $template_id; ?>">
<input type="hidden" id="template-data" value="<?php echo esc_attr($template_data); ?>">
<script>
// Pass uploaded fonts to JavaScript
window.apdUploadedFonts = <?php 
$uploaded_fonts = get_option('apd_uploaded_fonts', array());
echo json_encode($uploaded_fonts);
?>;
</script>


<style>
.apd-designer-wrap { margin: -20px -20px 0 -20px; background: #f1f1f1; min-height: calc(100vh - 32px); }
.apd-font-item { display: flex; justify-content: space-between; align-items: center; padding: 8px; margin: 4px 0; border: 1px solid #ddd; border-radius: 4px; cursor: pointer; background: #fff; }
.apd-font-item:hover { background: #f0f0f0; }
.apd-font-item.selected { background: #0073aa; color: #fff; }
.font-name { font-weight: 500; }
.font-preview { font-size: 18px; font-weight: bold; }
.apd-designer-header { background: #fff; padding: 20px; border-bottom: 1px solid #ccd0d4; display: flex; justify-content: space-between; align-items: center; }
.apd-designer-title h1 { margin: 0 0 5px 0; }
.apd-product-info { margin: 0; color: #666; font-size: 14px; }
.apd-designer-actions { display: flex; gap: 10px; }
.apd-designer-container { display: flex; height: calc(100vh - 120px); }
.apd-designer-sidebar { min-width: 300px; max-width: 300px; background: #fff; border-right: 1px solid #ccd0d4; overflow-y: auto; padding: 20px; }
.apd-sidebar-section { margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
.apd-sidebar-section:last-child { border-bottom: none; }
.apd-sidebar-section h3 { margin: 0 0 15px 0; color: #23282d; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
.apd-form-group { margin-bottom: 15px; }
.apd-form-group label { display: block; margin-bottom: 5px; font-weight: 500; color: #23282d; }
.apd-form-group input, .apd-form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
.apd-element-buttons { display: flex; flex-direction: column; gap: 10px; }
.apd-add-element { display: flex; align-items: center; gap: 10px; padding: 12px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; cursor: pointer; transition: all 0.2s ease; }
.apd-add-element:hover { background: #e3f2fd; border-color: #0073aa; }
.apd-add-element .dashicons { font-size: 18px; color: #666; }
.apd-materials-list { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
.apd-material-item { aspect-ratio: 1; border: 2px solid #ddd; border-radius: 4px; overflow: hidden; cursor: pointer; transition: border-color 0.2s ease; }
.apd-material-item:hover { border-color: #0073aa; }
.apd-material-item.selected { border-color: #0073aa; box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.2); }
.apd-material-item img { width: 100%; height: 100%; object-fit: cover; }
.apd-color-palette { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
.apd-color-item { display: flex; flex-direction: column; align-items: center; padding: 8px; border: 2px solid #ddd; border-radius: 4px; cursor: pointer; transition: all 0.2s ease; }
.apd-color-item:hover { border-color: #0073aa; transform: scale(1.05); }
.apd-color-item.selected { border-color: #0073aa; box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.2); }
.color-swatch { width: 30px; height: 30px; border-radius: 50%; margin-bottom: 4px; }
.color-name { font-size: 10px; color: #666; text-align: center; }
.apd-add-color-section { margin-top: 15px; text-align: center; }
.apd-add-color-btn { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 10px; background: #f0f0f0; border: 2px dashed #ccc; border-radius: 4px; cursor: pointer; transition: all 0.2s ease; }
.apd-add-color-btn:hover { background: #e0e0e0; border-color: #0073aa; }
.apd-add-color-btn .dashicons { font-size: 16px; color: #666; }
.apd-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center; }
.apd-modal-content { background: #fff; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); max-width: 400px; width: 90%; }
.apd-modal-header { padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
.apd-modal-header h3 { margin: 0; color: #23282d; }
.apd-modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #666; }
.apd-modal-close:hover { color: #000; }
.apd-modal-body { padding: 20px; }
.apd-color-picker-section, .apd-color-preview { margin-bottom: 15px; }
.apd-color-picker-section label, .apd-color-preview label { display: block; margin-bottom: 8px; font-weight: 500; color: #23282d; }
.apd-modal-footer { padding: 20px; border-top: 1px solid #eee; display: flex; gap: 10px; justify-content: flex-end; }
.apd-designer-canvas-container { flex: 1; display: flex; align-items: center; justify-content: center; background: #f1f1f1; padding: 20px; }
.apd-canvas-wrapper { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
.apd-canvas { position: relative; border: 1px solid #ddd; background: #fff; overflow: hidden; }
.apd-canvas-bg-image { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-size: cover; background-position: center; background-repeat: no-repeat; }
.apd-canvas-element { position: absolute; border: 2px dashed #0073aa; background: rgba(0, 115, 170, 0.1); cursor: move; min-width: 50px; min-height: 30px; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #0073aa; user-select: none; }
.apd-canvas-element.selected { border-color: #ff6b6b; background: rgba(255, 107, 107, 0.1); color: #ff6b6b; }
.apd-canvas-element .element-label { background: rgba(255,255,255,0.9); padding: 2px 6px; border-radius: 3px; font-weight: 500; }
.apd-canvas-element .element-resize-handles { position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; }
.apd-canvas-element .resize-handle { position: absolute; width: 8px; height: 8px; background: #0073aa; border: 1px solid #fff; pointer-events: all; }
.apd-canvas-element .resize-handle.nw { top: -4px; left: -4px; cursor: nw-resize; }
.apd-canvas-element .resize-handle.ne { top: -4px; right: -4px; cursor: ne-resize; }
.apd-canvas-element .resize-handle.sw { bottom: -4px; left: -4px; cursor: sw-resize; }
.apd-canvas-element .resize-handle.se { bottom: -4px; right: -4px; cursor: se-resize; }
.button.disabled { opacity: 0.5; cursor: not-allowed; }
.apd-designer-properties { width: 300px; background: #fff; border-left: 1px solid #ccd0d4; overflow-y: auto; }
.apd-properties-header { padding: 20px; border-bottom: 1px solid #eee; background: #f9f9f9; }
.apd-properties-header h3 { margin: 0 0 5px 0; color: #23282d; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
#selected-element-name { font-size: 12px; color: #666; }
.apd-properties-content { padding: 20px; }
.apd-no-selection { color: #666; font-style: italic; text-align: center; margin: 40px 0; }
.apd-property-group { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
.apd-property-group:last-child { border-bottom: none; }
.apd-property-group h4 { margin: 0 0 10px 0; color: #23282d; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
.apd-property { margin-bottom: 10px; }
.apd-property label { display: block; margin-bottom: 5px; font-size: 12px; color: #666; font-weight: 500; }
.apd-property input, .apd-property select { width: 100%; padding: 6px 8px; border: 1px solid #ddd; border-radius: 3px; font-size: 12px; }
.apd-property input[type="color"] { height: 32px; padding: 2px; }
.apd-property input[type="range"] { width: 100%; }
.apd-property .range-value { display: inline-block; margin-left: 10px; font-size: 11px; color: #666; min-width: 30px; }
.apd-delete-element { background: #dc3232; color: white; border: none; padding: 8px 16px; border-radius: 3px; cursor: pointer; font-size: 12px; width: 100%; }
.apd-delete-element:hover { background: #a00; }
</style>

<script>
var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
var apd_ajax = {
    nonce: '<?php echo wp_create_nonce('apd_ajax_nonce'); ?>'
};
</script>
<script src="<?php echo APD_PLUGIN_URL; ?>assets/js/designer.js"></script>

