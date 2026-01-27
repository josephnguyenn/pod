<?php
/**
 * Meta Boxes Handler
 * 
 * Handles all product and template meta boxes
 *
 * @package AdvancedProductDesigner
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class APD_Meta_Boxes
{
    /**
     * Main plugin instance reference
     * @var AdvancedProductDesigner
     */
    private $plugin;

    /**
     * Constructor
     * 
     * @param AdvancedProductDesigner $plugin Main plugin instance
     */
    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Register hooks
     */
    public function init()
    {
        add_action('add_meta_boxes', array($this, 'add_template_meta_boxes'));
        add_action('add_meta_boxes', array($this, 'add_product_meta_boxes'));
        add_action('save_post', array($this, 'save_product_meta'));
        add_action('save_post', array($this, 'save_template_meta'));
        
        // Ensure core pages exist (cart, checkout, thank-you, my-orders)
        add_action('admin_init', array($this, 'ensure_core_pages'));
        add_action('init', array($this, 'ensure_core_pages'));
    }

    public function add_template_meta_boxes()
    {
        add_meta_box(
            'apd_template_details',
            'Template Settings',
            array($this, 'template_details_meta_box'),
            'apd_template',
            'normal',
            'high'
        );
    }

    public function add_product_meta_boxes()
    {
        add_meta_box(
            'apd_product_details',
            'Product Details',
            array($this, 'product_details_meta_box'),
            'apd_product',
            'normal',
            'high'
        );

        add_meta_box(
            'apd_product_features',
            'Product Features',
            array($this, 'product_features_meta_box'),
            'apd_product',
            'side',
            'default'
        );

        add_meta_box(
            'apd_canvas_settings',
            'Canvas Settings',
            array($this, 'canvas_settings_meta_box'),
            'apd_product',
            'side',
            'default'
        );

        add_meta_box(
            'apd_product_variants',
            'Product Variants (Material & Size)',
            array($this, 'product_variants_meta_box'),
            'apd_product',
            'normal',
            'default'
        );

        add_meta_box(
            'apd_pricing_tiers',
            'Volume Pricing Tiers',
            array($this, 'pricing_tiers_meta_box'),
            'apd_product',
            'normal',
            'default'
        );
    }

    public function product_details_meta_box($post)
    {
        wp_nonce_field('fsc_save_product_meta', 'fsc_product_meta_nonce');

        $price = get_post_meta($post->ID, '_fsc_price', true);
        $sale_price = get_post_meta($post->ID, '_fsc_sale_price', true);
        $category = get_post_meta($post->ID, '_fsc_category', true);
        $template_id = get_post_meta($post->ID, '_fsc_template', true);
        $material = get_post_meta($post->ID, '_fsc_material', true);
        $size = get_post_meta($post->ID, '_fsc_size', true);
        $color_options = get_post_meta($post->ID, '_fsc_color_options', true);
        $logo_file = get_post_meta($post->ID, '_fsc_logo_file', true);
        $is_customizable = get_post_meta($post->ID, '_fsc_customizable', true);
        if ($is_customizable === '') {
            $is_customizable = '1'; // Default to customizable
        }

        // Get available templates
        $templates = get_posts(array(
            'post_type' => 'apd_template',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ));

        ?>
        <table class="form-table">
            <tr>
                <th><label for="fsc_template">Template</label></th>
                <td>
                    <select id="fsc_template" name="fsc_template">
                        <option value="">Select Template</option>
                        <?php foreach ($templates as $template): ?>
                            <option value="<?php echo $template->ID; ?>" <?php selected($template_id, $template->ID); ?>>
                                <?php echo esc_html($template->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">Choose a template for this product (optional if customization is disabled). <a href="<?php echo admin_url('admin.php?page=apd-templates'); ?>" target="_blank">Manage Templates</a></p>
                </td>
            </tr>
            <tr>
                <th><label for="fsc_price">Regular Price</label></th>
                <td>
                    <input type="text" id="fsc_price" name="fsc_price" value="<?php echo esc_attr($price); ?>" class="regular-text" placeholder="$125.00">
                    <p class="description">Enter the regular product price</p>
                </td>
            </tr>
            <tr>
                <th><label for="fsc_sale_price">Sale Price</label></th>
                <td>
                    <input type="text" id="fsc_sale_price" name="fsc_sale_price" value="<?php echo esc_attr($sale_price); ?>" class="regular-text" placeholder="$99.00">
                    <p class="description">Enter the sale price (optional). Leave empty if no sale.</p>
                </td>
            </tr>
            <tr>
                <th><label for="fsc_category">Category</label></th>
                <td>
                    <input type="text" id="fsc_category" name="fsc_category" value="<?php echo esc_attr($category); ?>" class="regular-text" placeholder="Freight Signs, Safety Signs, etc.">
                    <p class="description">Enter the product category</p>
                </td>
            </tr>
            <tr>
                <th><label for="fsc_thumbnail">Product Thumbnail</label></th>
                <td>
                    <?php
                    $thumbnail_id = get_post_meta($post->ID, '_fsc_thumbnail_id', true);
                    $thumbnail_url = '';
                    if ($thumbnail_id) {
                        $thumbnail_url = wp_get_attachment_image_url($thumbnail_id, 'medium');
                    }
                    ?>
                    <div class="fsc-thumbnail-wrapper">
                        <div class="fsc-thumbnail-preview" style="margin-bottom: 10px;">
                            <?php if ($thumbnail_url): ?>
                                <img src="<?php echo esc_url($thumbnail_url); ?>" style="max-width: 200px; height: auto; border: 1px solid #ddd; border-radius: 4px; display: block;">
                            <?php else: ?>
                                <img src="<?php echo esc_url(APD_PLUGIN_URL . 'assets/images/placeholder.png'); ?>" style="max-width: 200px; height: auto; border: 1px solid #ddd; border-radius: 4px; display: block;">
                            <?php endif; ?>
                        </div>
                        <input type="hidden" id="fsc_thumbnail_id" name="fsc_thumbnail_id" value="<?php echo esc_attr($thumbnail_id); ?>">
                        <button type="button" class="button fsc-upload-thumbnail-btn">
                            <?php echo $thumbnail_id ? 'Change Thumbnail' : 'Upload Thumbnail'; ?>
                        </button>
                        <?php if ($thumbnail_id): ?>
                            <button type="button" class="button fsc-remove-thumbnail-btn" style="margin-left: 5px;">Remove Thumbnail</button>
                        <?php endif; ?>
                    </div>
                    <p class="description">Upload a thumbnail image for this product (JPG, PNG, GIF)</p>
                </td>
            </tr>
            <tr style="display: none;">
                <th><label for="fsc_material">Material</label></th>
                <td>
                    <input type="text" id="fsc_material" name="fsc_material" value="<?php echo esc_attr($material); ?>" class="regular-text" placeholder="Heavy Metal Chrome with Color">
                    <p class="description">Enter the material description</p>
                </td>
            </tr>
            <tr style="display: none;">
                <th><label for="fsc_size">Size</label></th>
                <td>
                    <input type="text" id="fsc_size" name="fsc_size" value="<?php echo esc_attr($size); ?>" class="regular-text" placeholder="DOT Approved Size">
                    <p class="description">Enter the product size</p>
                </td>
            </tr>
            <tr>
                <th><label for="fsc_customizable">Customizable</label></th>
                <td>
                    <label>
                        <input type="checkbox" id="fsc_customizable" name="fsc_customizable" value="1" <?php checked($is_customizable, '1'); ?>>
                        Allow customers to customize this product
                    </label>
                    <p class="description">If unchecked, customers can only add to cart without customization</p>
                </td>
            </tr>
            <tr id="fsc_customization_options_row" style="<?php echo $is_customizable != '1' ? 'display:none;' : ''; ?>">
                <th><label>Customization Options</label></th>
                <td>
                    <?php
                    $enable_color_selection = get_post_meta($post->ID, '_fsc_enable_color_selection', true);
                    $enable_outline_selection = get_post_meta($post->ID, '_fsc_enable_outline_selection', true);
                    // Default both to enabled if not set
                    if ($enable_color_selection === '') $enable_color_selection = '1';
                    if ($enable_outline_selection === '') $enable_outline_selection = '1';
                    ?>
                    <label style="display: block; margin-bottom: 8px;">
                        <input type="checkbox" name="fsc_enable_color_selection" value="1" <?php checked($enable_color_selection, '1'); ?>>
                        Enable color selection in customizer
                    </label>
                    <label style="display: block;">
                        <input type="checkbox" name="fsc_enable_outline_selection" value="1" <?php checked($enable_outline_selection, '1'); ?>>
                        Enable outline color selection in customizer
                    </label>
                    <p class="description">Choose which customization options are available for this product. At least one should be enabled.</p>
                </td>
            </tr>
            <tr>
                <th><label for="fsc_logo_file">Product Logo (SVG)</label></th>
                <td>
                    <?php
                    $logo_id = get_post_meta($post->ID, '_fsc_logo_id', true);
                    $logo_url = '';
                    $logo_filename = '';
                    if ($logo_id) {
                        $logo_url = wp_get_attachment_url($logo_id);
                        $logo_filename = basename($logo_url);
                    } elseif ($logo_file) {
                        // Backward compatibility with old file-based system
                        $logo_url = $logo_file;
                        $logo_filename = basename($logo_file);
                    }
                    ?>
                    <div class="fsc-logo-wrapper">
                        <?php if ($logo_url): ?>
                            <div style="margin-bottom: 10px; padding: 10px; background: #f0f0f0; border-radius: 4px;">
                                <strong>Current logo:</strong> 
                                <a href="<?php echo esc_url($logo_url); ?>" target="_blank"><?php echo esc_html($logo_filename); ?></a>
                            </div>
                        <?php endif; ?>
                        <input type="hidden" id="fsc_logo_id" name="fsc_logo_id" value="<?php echo esc_attr($logo_id); ?>">
                        <button type="button" class="button fsc-upload-logo-btn">
                            <?php echo $logo_url ? 'Change Logo' : 'Upload Logo'; ?>
                        </button>
                        <?php if ($logo_url): ?>
                            <button type="button" class="button fsc-remove-logo-btn" style="margin-left: 5px;">Remove Logo</button>
                        <?php endif; ?>
                    </div>
                    <p class="description">Upload SVG logo file for this product<?php echo $logo_url ? ' (click to replace)' : ' (required)'; ?></p>
                </td>
            </tr>
            <tr style="display: none;">
                <th><label for="fsc_color_options">Color Options</label></th>
                <td>
                    <textarea id="fsc_color_options" name="fsc_color_options" rows="4" class="large-text" placeholder="black, yellow, dark-red, orange, light-blue, light-green, purple, light-grey, brown, bright-yellow, dark-green, light-purple"><?php echo esc_textarea($color_options); ?></textarea>
                    <p class="description">Enter available colors separated by commas</p>
                </td>
            </tr>
        </table>
        <?php
    }

    public function product_features_meta_box($post)
    {
        $features = get_post_meta($post->ID, '_fsc_features', true);
        if (!is_array($features)) {
            $features = array(
                'DOT Approved Size',
                '5 years outdoor life',
                'Air release for bubble free installing',
                'Professional quality materials'
            );
        }
        ?>
        <div id="fsc-features-container">
            <?php foreach ($features as $index => $feature): ?>
            <div class="fsc-feature-row">
                <input type="text" name="fsc_features[]" value="<?php echo esc_attr($feature); ?>" class="regular-text" placeholder="Enter feature">
                <button type="button" class="button fsc-remove-feature">Remove</button>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button fsc-add-feature">Add Feature</button>
        
        <script>
        jQuery(document).ready(function($) {
            // Toggle customization options visibility
            $('#fsc_customizable').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#fsc_customization_options_row').show();
                } else {
                    $('#fsc_customization_options_row').hide();
                }
            });
            
            $('.fsc-add-feature').on('click', function() {
                var newRow = '<div class="fsc-feature-row"><input type="text" name="fsc_features[]" value="" class="regular-text" placeholder="Enter feature"><button type="button" class="button fsc-remove-feature">Remove</button></div>';
                $('#fsc-features-container').append(newRow);
            });
            
            $(document).on('click', '.fsc-remove-feature', function() {
                $(this).parent().remove();
            });
        });
        </script>
        
        <style>
        .fsc-feature-row {
            margin-bottom: 10px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .fsc-feature-row input {
            flex: 1;
        }
        </style>
        <?php
    }

    public function canvas_settings_meta_box($post)
    {
        wp_nonce_field('apd_save_product_meta', 'apd_product_meta_nonce');

        $canvas_width = get_post_meta($post->ID, '_apd_canvas_width', true);
        $canvas_height = get_post_meta($post->ID, '_apd_canvas_height', true);
        $background_color = get_post_meta($post->ID, '_apd_background_color', true);

        ?>
        <table class="form-table">
            <tr>
                <th><label for="apd_canvas_width">Canvas Width (px)</label></th>
                <td>
                    <input type="number" id="apd_canvas_width" name="apd_canvas_width" value="<?php echo esc_attr($canvas_width ?: '800'); ?>" class="regular-text" min="100" max="2000">
                    <p class="description">Default canvas width in pixels</p>
                </td>
            </tr>
            <tr>
                <th><label for="apd_canvas_height">Canvas Height (px)</label></th>
                <td>
                    <input type="number" id="apd_canvas_height" name="apd_canvas_height" value="<?php echo esc_attr($canvas_height ?: '600'); ?>" class="regular-text" min="100" max="2000">
                    <p class="description">Default canvas height in pixels</p>
                </td>
            </tr>
            <tr>
                <th><label for="apd_background_color">Background Color</label></th>
                <td>
                    <input type="color" id="apd_background_color" name="apd_background_color" value="<?php echo esc_attr($background_color ?: '#ffffff'); ?>" class="regular-text">
                    <p class="description">Default canvas background color</p>
                </td>
            </tr>
        </table>
        <?php
    }

    public function product_variants_meta_box($post)
    {
        $variants = get_post_meta($post->ID, '_apd_variants', true);
        
        // Initialize default structure
        if (!is_array($variants)) {
            $variants = array(
                'enabled' => false,
                'size_options' => array(
                    array('value' => '12x6', 'label' => '12" × 6"'),
                    array('value' => '18x12', 'label' => '18" × 12"')
                ),
                'material_options' => array(),
                'combinations' => array()
            );
        }
        
        // Get all available materials
        $all_materials = get_option('apd_materials', array());
        
        ?>
        <div class="apd-variants-wrapper">
            <p>
                <label>
                    <input type="checkbox" id="apd_variants_enabled" name="apd_variants_enabled" value="1" <?php checked($variants['enabled'], true); ?>>
                    <strong>Enable SKU-based variants for this product</strong>
                </label>
            </p>
            <p class="description">When enabled, customers select material and size on the product detail page, then enter customizer with those selections.</p>
            
            <div id="apd-variants-content" style="<?php echo !$variants['enabled'] ? 'display:none;' : ''; ?>">
                
                <div style="padding: 15px; background: #e7f3ff; border-left: 4px solid #2271b1; margin-bottom: 20px;">
                    <h4 style="margin-top: 0; color: #2271b1;">💡 Price Inheritance</h4>
                    <p style="margin-bottom: 0; color: #2c3338;">
                        Variants automatically inherit the <strong>Price</strong> and <strong>Sale Price</strong> from the "Product Details" section above.<br>
                        Only fill in variant-specific prices when they differ from the base product price. Leave empty to use the default prices.
                    </p>
                </div>
                
                <hr>
                
                <!-- Size Options -->
                <h3>Size Options</h3>
                <p class="description">Define available sizes (e.g., 12×6, 18×12). Value is used in SKU, Label is shown to customer.</p>
                
                <table class="widefat" id="apd-size-options-table">
                    <thead>
                        <tr>
                            <th style="width: 200px;">Value (for SKU)</th>
                            <th>Label (display name)</th>
                            <th style="width: 80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="apd-size-options-body">
                        <?php foreach ($variants['size_options'] as $idx => $size): ?>
                        <tr>
                            <td><input type="text" name="apd_size_value[]" value="<?php echo esc_attr($size['value']); ?>" class="regular-text" placeholder="12x6"></td>
                            <td><input type="text" name="apd_size_label[]" value="<?php echo esc_attr($size['label']); ?>" class="regular-text" placeholder='12" × 6"'></td>
                            <td><button type="button" class="button apd-remove-size-btn">Remove</button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="button" class="button apd-add-size-btn" style="margin-top: 10px;">Add Size</button>
                
                <hr style="margin: 20px 0;">
                
                <!-- Material Options -->
                <h3>Material Options</h3>
                <p class="description">Define available materials (e.g., Sticker, Steel, Metal). Value is used in SKU, Label is shown to customer.</p>
                
                <table class="widefat" id="apd-material-options-table">
                    <thead>
                        <tr>
                            <th style="width: 200px;">Value (for SKU)</th>
                            <th>Label (display name)</th>
                            <th style="width: 80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="apd-material-options-body">
                        <?php foreach ($variants['material_options'] as $idx => $material): ?>
                        <tr>
                            <td><input type="text" name="apd_material_value[]" value="<?php echo esc_attr($material['value']); ?>" class="regular-text" placeholder="sticker"></td>
                            <td><input type="text" name="apd_material_label[]" value="<?php echo esc_attr($material['label']); ?>" class="regular-text" placeholder="Sticker"></td>
                            <td><button type="button" class="button apd-remove-material-btn">Remove</button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="button" class="button apd-add-material-btn" style="margin-top: 10px;">Add Material</button>
                
                <hr style="margin: 20px 0;">
                
                <!-- Generate Combinations -->
                <button type="button" class="button button-primary" id="apd-generate-combinations">Generate Combinations Table</button>
                <p class="description">Click to automatically create all size × material combinations below.</p>
                
                <hr style="margin: 20px 0;">
                
                <!-- Combinations Table -->
                <h3>Variant Combinations</h3>
                <p class="description">Each combination gets unique SKU, price, sale price, stock status, and optional quantity discounts.</p>
                
                <div class="apd-variant-combinations">
                    <?php 
                    $pricing_service = new APD_Pricing_Service();
                    $variant_tiers_all = get_post_meta($post->ID, '_apd_variant_price_tiers', true);
                    if (!is_array($variant_tiers_all)) {
                        $variant_tiers_all = array();
                    }
                    
                    foreach ($variants['combinations'] as $idx => $combo): 
                        $sku = isset($combo['sku']) ? $combo['sku'] : '';
                        $variant_tiers = isset($variant_tiers_all[$sku]) ? $variant_tiers_all[$sku] : array();
                    ?>
                    <div class="apd-variant-item" style="margin-bottom: 20px; border: 1px solid #ddd; padding: 15px; background: #fff;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <h4 style="margin: 0;">
                                <?php echo esc_html($combo['size']); ?> 
                                <?php if (!empty($combo['material'])): ?>
                                    × <?php echo esc_html($combo['material']); ?>
                                <?php endif; ?>
                            </h4>
                            <button type="button" class="button apd-toggle-variant-tiers" data-variant-idx="<?php echo esc_attr($idx); ?>" data-sku="<?php echo esc_attr($sku); ?>">
                                <?php echo !empty($variant_tiers) ? 'Edit' : 'Add'; ?> Quantity Discounts
                            </button>
                        </div>
                        
                        <input type="hidden" name="apd_comb_size[]" value="<?php echo esc_attr($combo['size']); ?>">
                        <input type="hidden" name="apd_comb_material[]" value="<?php echo esc_attr($combo['material']); ?>">
                        
                        <table class="form-table" style="margin: 0;">
                            <tr>
                                <th style="padding-left: 0; width: 120px;">SKU</th>
                                <td><input type="text" name="apd_comb_sku[]" value="<?php echo esc_attr($combo['sku']); ?>" class="regular-text" placeholder="VAR-12X6-GOLD"></td>
                            </tr>
                            <tr>
                                <th style="padding-left: 0;">Price ($)</th>
                                <td>
                                    <input type="number" name="apd_comb_price[]" value="<?php echo esc_attr($combo['price']); ?>" step="0.01" min="0" class="regular-text" placeholder="<?php echo esc_attr(get_post_meta($post->ID, '_fsc_price', true)); ?>">
                                    <p class="description">Leave empty to use product base price ($<?php echo esc_html(get_post_meta($post->ID, '_fsc_price', true) ?: '0.00'); ?>)</p>
                                </td>
                            </tr>
                            <tr>
                                <th style="padding-left: 0;">Sale Price ($)</th>
                                <td>
                                    <input type="number" name="apd_comb_sale_price[]" value="<?php echo esc_attr($combo['sale_price']); ?>" step="0.01" min="0" class="regular-text" placeholder="<?php echo esc_attr(get_post_meta($post->ID, '_fsc_sale_price', true)); ?>">
                                    <p class="description">Leave empty to use product sale price ($<?php echo esc_html(get_post_meta($post->ID, '_fsc_sale_price', true) ?: 'none'); ?>)</p>
                                </td>
                            </tr>
                            <tr>
                                <th style="padding-left: 0;">Stock</th>
                                <td>
                                    <select name="apd_comb_stock[]">
                                        <option value="instock" <?php selected($combo['stock'], 'instock'); ?>>In Stock</option>
                                        <option value="outofstock" <?php selected($combo['stock'], 'outofstock'); ?>>Out of Stock</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        
                        <!-- Variant-specific tier pricing -->
                        <div class="apd-variant-tiers-section" data-variant-idx="<?php echo esc_attr($idx); ?>" data-sku="<?php echo esc_attr($sku); ?>" style="display: none; margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                            <h4 style="margin-top: 0;">Quantity Discounts for this Variant</h4>
                            <!-- Hidden field to map variant index to SKU -->
                            <input type="hidden" name="apd_var_tier_sku_map[<?php echo esc_attr($idx); ?>]" value="<?php echo esc_attr($sku); ?>">
                            <table class="widefat apd-variant-tiers-table">
                                <thead>
                                    <tr>
                                        <th style="width: 100px;">Min Qty</th>
                                        <th style="width: 100px;">Discount %</th>
                                        <th style="width: 150px;">Tier Name</th>
                                        <th style="width: 80px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="apd-variant-tiers-body">
                                    <?php if (!empty($variant_tiers)): ?>
                                        <?php foreach ($variant_tiers as $tier): ?>
                                        <tr>
                                            <td><input type="number" name="apd_var_tier_minqty[<?php echo esc_attr($idx); ?>][]" value="<?php echo esc_attr($tier['min_qty']); ?>" min="1" class="small-text"></td>
                                            <td><input type="number" name="apd_var_tier_discount[<?php echo esc_attr($idx); ?>][]" value="<?php echo esc_attr($tier['discount_percent']); ?>" min="0" max="100" step="0.01" class="small-text"></td>
                                            <td><input type="text" name="apd_var_tier_name[<?php echo esc_attr($idx); ?>][]" value="<?php echo esc_attr($tier['name']); ?>" class="regular-text" placeholder="Bulk"></td>
                                            <td><button type="button" class="button apd-remove-variant-tier">Remove</button></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <button type="button" class="button apd-add-variant-tier" data-variant-idx="<?php echo esc_attr($idx); ?>" style="margin-top: 10px;">Add Tier</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Toggle variants content
            $('#apd_variants_enabled').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#apd-variants-content').slideDown();
                } else {
                    $('#apd-variants-content').slideUp();
                }
            });
            
            // Add size option
            $('.apd-add-size-btn').on('click', function() {
                var newRow = '<tr>' +
                    '<td><input type="text" name="apd_size_value[]" value="" class="regular-text" placeholder="24x12"></td>' +
                    '<td><input type="text" name="apd_size_label[]" value="" class="regular-text" placeholder="24\\" × 12\\""></td>' +
                    '<td><button type="button" class="button apd-remove-size-btn">Remove</button></td>' +
                    '</tr>';
                $('#apd-size-options-body').append(newRow);
            });
            
            // Remove size option
            $(document).on('click', '.apd-remove-size-btn', function() {
                $(this).closest('tr').remove();
            });
            
            // Add material option
            $('.apd-add-material-btn').on('click', function() {
                var newRow = '<tr>' +
                    '<td><input type="text" name="apd_material_value[]" value="" class="regular-text" placeholder="steel"></td>' +
                    '<td><input type="text" name="apd_material_label[]" value="" class="regular-text" placeholder="Steel"></td>' +
                    '<td><button type="button" class="button apd-remove-material-btn">Remove</button></td>' +
                    '</tr>';
                $('#apd-material-options-body').append(newRow);
            });
            
            // Remove material option
            $(document).on('click', '.apd-remove-material-btn', function() {
                $(this).closest('tr').remove();
            });
            
            // Toggle variant tiers section
            $(document).on('click', '.apd-toggle-variant-tiers', function(e) {
                e.preventDefault();
                var variantIdx = $(this).data('variant-idx');
                var $section = $('.apd-variant-tiers-section[data-variant-idx="' + variantIdx + '"]');
                $section.slideToggle();
            });
            
            // Add variant tier
            $(document).on('click', '.apd-add-variant-tier', function(e) {
                e.preventDefault();
                var variantIdx = $(this).data('variant-idx');
                var $tbody = $('.apd-variant-tiers-section[data-variant-idx="' + variantIdx + '"] .apd-variant-tiers-body');
                var newRow = '<tr>' +
                    '<td><input type="number" name="apd_var_tier_minqty[' + variantIdx + '][]" value="" min="1" class="small-text"></td>' +
                    '<td><input type="number" name="apd_var_tier_discount[' + variantIdx + '][]" value="" min="0" max="100" step="0.01" class="small-text"></td>' +
                    '<td><input type="text" name="apd_var_tier_name[' + variantIdx + '][]" value="" class="regular-text" placeholder="Bulk"></td>' +
                    '<td><button type="button" class="button apd-remove-variant-tier">Remove</button></td>' +
                    '</tr>';
                $tbody.append(newRow);
            });
            
            // Remove variant tier
            $(document).on('click', '.apd-remove-variant-tier', function(e) {
                e.preventDefault();
                $(this).closest('tr').remove();
            });
            
            // Helper function to generate variant card HTML
            function generateVariantCard(idx, size, sizeLabel, material, materialLabel, sku) {
                var title = sizeLabel;
                if (materialLabel && materialLabel !== 'N/A') {
                    title += ' × ' + materialLabel;
                }
                
                var card = '<div class="apd-variant-item" style="margin-bottom: 20px; border: 1px solid #ddd; padding: 15px; background: #fff;">' +
                    '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">' +
                        '<h4 style="margin: 0;">' + title + '</h4>' +
                        '<button type="button" class="button apd-toggle-variant-tiers" data-variant-idx="' + idx + '" data-sku="' + sku + '">Add Quantity Discounts</button>' +
                    '</div>' +
                    '<input type="hidden" name="apd_comb_size[]" value="' + size + '">' +
                    '<input type="hidden" name="apd_comb_material[]" value="' + material + '">' +
                    '<table class="form-table" style="margin: 0;">' +
                        '<tr>' +
                            '<th style="padding-left: 0; width: 120px;">SKU</th>' +
                            '<td><input type="text" name="apd_comb_sku[]" value="' + sku + '" class="regular-text" placeholder="VAR-12X6-GOLD"></td>' +
                        '</tr>' +
                        '<tr>' +
                            '<th style="padding-left: 0;">Price ($)</th>' +
                            '<td>' +
                                '<input type="number" name="apd_comb_price[]" value="" step="0.01" min="0" class="regular-text">' +
                                '<p class="description">Leave empty to use product base price</p>' +
                            '</td>' +
                        '</tr>' +
                        '<tr>' +
                            '<th style="padding-left: 0;">Sale Price ($)</th>' +
                            '<td>' +
                                '<input type="number" name="apd_comb_sale_price[]" value="" step="0.01" min="0" class="regular-text">' +
                                '<p class="description">Leave empty to use product sale price</p>' +
                            '</td>' +
                        '</tr>' +
                        '<tr>' +
                            '<th style="padding-left: 0;">Stock</th>' +
                            '<td>' +
                                '<select name="apd_comb_stock[]">' +
                                    '<option value="instock">In Stock</option>' +
                                    '<option value="outofstock">Out of Stock</option>' +
                                '</select>' +
                            '</td>' +
                        '</tr>' +
                    '</table>' +
                    '<div class="apd-variant-tiers-section" data-variant-idx="' + idx + '" data-sku="' + sku + '" style="display: none; margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">' +
                        '<h4 style="margin-top: 0;">Quantity Discounts for this Variant</h4>' +
                        '<input type="hidden" name="apd_var_tier_sku_map[' + idx + ']" value="' + sku + '">' +
                        '<table class="widefat apd-variant-tiers-table">' +
                            '<thead>' +
                                '<tr>' +
                                    '<th style="width: 100px;">Min Qty</th>' +
                                    '<th style="width: 100px;">Discount %</th>' +
                                    '<th style="width: 150px;">Tier Name</th>' +
                                    '<th style="width: 80px;">Actions</th>' +
                                '</tr>' +
                            '</thead>' +
                            '<tbody class="apd-variant-tiers-body"></tbody>' +
                        '</table>' +
                        '<button type="button" class="button apd-add-variant-tier" data-variant-idx="' + idx + '" style="margin-top: 10px;">Add Tier</button>' +
                    '</div>' +
                '</div>';
                
                return card;
            }
            
            // Generate combinations
            $('#apd-generate-combinations').on('click', function() {
                var sizes = [];
                $('input[name="apd_size_value[]"]').each(function(i) {
                    var value = $(this).val();
                    var label = $('input[name="apd_size_label[]"]').eq(i).val();
                    if (value && label) {
                        sizes.push({value: value, label: label});
                    }
                });
                
                var materials = [];
                $('input[name="apd_material_value[]"]').each(function(i) {
                    var value = $(this).val();
                    var label = $('input[name="apd_material_label[]"]').eq(i).val();
                    if (value && label) {
                        materials.push({value: value, label: label});
                    }
                });
                
                // Allow either sizes only, materials only, or both
                if (sizes.length === 0 && materials.length === 0) {
                    alert('Please add at least one size or at least one material.');
                    return;
                }
                
                // Clear existing combinations
                $('.apd-variant-combinations').empty();
                
                var idx = 0;
                
                // Generate combinations based on what's available
                if (sizes.length > 0 && materials.length > 0) {
                    // Both sizes and materials - create full combinations
                    sizes.forEach(function(size) {
                        materials.forEach(function(material) {
                            var sku = 'VAR-' + size.value.toUpperCase() + '-' + material.value.toUpperCase().replace(/\s+/g, '-');
                            var card = generateVariantCard(idx, size.value, size.label, material.value, material.label, sku);
                            $('.apd-variant-combinations').append(card);
                            idx++;
                        });
                    });
                } else if (sizes.length > 0) {
                    // Only sizes - create variants with empty material
                    sizes.forEach(function(size) {
                        var sku = 'VAR-' + size.value.toUpperCase();
                        var card = generateVariantCard(idx, size.value, size.label, '', 'N/A', sku);
                        $('.apd-variant-combinations').append(card);
                        idx++;
                    });
                } else {
                    // Only materials - create variants with empty size
                    materials.forEach(function(material) {
                        var sku = 'VAR-' + material.value.toUpperCase().replace(/\s+/g, '-');
                        var card = generateVariantCard(idx, '', 'N/A', material.value, material.label, sku);
                        $('.apd-variant-combinations').append(card);
                        idx++;
                    });
                }
                
                alert('Combinations generated! Please fill in prices for each variant.');
            });
        });
        </script>
        
        <style>
        .apd-variants-wrapper table.widefat {
            margin-top: 10px;
        }
        .apd-variants-wrapper table.widefat td input[type="text"],
        .apd-variants-wrapper table.widefat td input[type="number"] {
            width: 100%;
        }
        </style>
        <?php
    }

    public function save_product_meta($post_id)
    {
        // Check nonce
        if (!isset($_POST['fsc_product_meta_nonce']) || !wp_verify_nonce($_POST['fsc_product_meta_nonce'], 'fsc_save_product_meta')) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save product details
        if (isset($_POST['fsc_template'])) {
            update_post_meta($post_id, '_fsc_template', intval($_POST['fsc_template']));
        }

        if (isset($_POST['fsc_price'])) {
            update_post_meta($post_id, '_fsc_price', sanitize_text_field($_POST['fsc_price']));
        }

        if (isset($_POST['fsc_sale_price'])) {
            update_post_meta($post_id, '_fsc_sale_price', sanitize_text_field($_POST['fsc_sale_price']));
        }

        if (isset($_POST['fsc_category'])) {
            update_post_meta($post_id, '_fsc_category', sanitize_text_field($_POST['fsc_category']));
        }

        if (isset($_POST['fsc_material'])) {
            update_post_meta($post_id, '_fsc_material', sanitize_text_field($_POST['fsc_material']));
        }

        if (isset($_POST['fsc_size'])) {
            update_post_meta($post_id, '_fsc_size', sanitize_text_field($_POST['fsc_size']));
        }

        if (isset($_POST['fsc_color_options'])) {
            update_post_meta($post_id, '_fsc_color_options', sanitize_textarea_field($_POST['fsc_color_options']));
        }

        // Save customizable checkbox
        if (isset($_POST['fsc_customizable'])) {
            update_post_meta($post_id, '_fsc_customizable', '1');
        } else {
            update_post_meta($post_id, '_fsc_customizable', '0');
        }
        
        // Save customization options
        if (isset($_POST['fsc_enable_color_selection'])) {
            update_post_meta($post_id, '_fsc_enable_color_selection', '1');
        } else {
            update_post_meta($post_id, '_fsc_enable_color_selection', '0');
        }
        
        if (isset($_POST['fsc_enable_outline_selection'])) {
            update_post_meta($post_id, '_fsc_enable_outline_selection', '1');
        } else {
            update_post_meta($post_id, '_fsc_enable_outline_selection', '0');
        }

        // Save features
        if (isset($_POST['fsc_features']) && is_array($_POST['fsc_features'])) {
            $features = array_filter(array_map('sanitize_text_field', $_POST['fsc_features']));
            update_post_meta($post_id, '_fsc_features', $features);
        }

        // Save canvas settings
        if (isset($_POST['apd_canvas_width'])) {
            update_post_meta($post_id, '_apd_canvas_width', intval($_POST['apd_canvas_width']));
        }
        
        if (isset($_POST['apd_canvas_height'])) {
            update_post_meta($post_id, '_apd_canvas_height', intval($_POST['apd_canvas_height']));
        }
        
        if (isset($_POST['apd_background_color'])) {
            update_post_meta($post_id, '_apd_background_color', sanitize_hex_color($_POST['apd_background_color']));
        }

        // Save thumbnail ID from media selector
        if (isset($_POST['fsc_thumbnail_id'])) {
            update_post_meta($post_id, '_fsc_thumbnail_id', sanitize_text_field($_POST['fsc_thumbnail_id']));
        }

        // Save logo ID from media selector
        if (isset($_POST['fsc_logo_id'])) {
            $logo_id = sanitize_text_field($_POST['fsc_logo_id']);
            update_post_meta($post_id, '_fsc_logo_id', $logo_id);
            
            // Also update the logo file URL for backward compatibility
            if ($logo_id) {
                $logo_url = wp_get_attachment_url($logo_id);
                if ($logo_url) {
                    update_post_meta($post_id, '_fsc_logo_file', $logo_url);
                }
            } else {
                // Clear logo file if no logo ID
                delete_post_meta($post_id, '_fsc_logo_file');
            }
        }

        // Save product variants
        if (isset($_POST['apd_variants_enabled'])) {
            // Build variants data structure
            $variants_data = array(
                'enabled' => true,
                'size_options' => array(),
                'material_options' => array(),
                'combinations' => array()
            );
            
            // Save size options
            if (isset($_POST['apd_size_value']) && isset($_POST['apd_size_label'])) {
                $size_values = $_POST['apd_size_value'];
                $size_labels = $_POST['apd_size_label'];
                
                foreach ($size_values as $idx => $value) {
                    if (!empty($value) && !empty($size_labels[$idx])) {
                        $variants_data['size_options'][] = array(
                            'value' => sanitize_text_field($value),
                            'label' => sanitize_text_field($size_labels[$idx])
                        );
                    }
                }
            }
            
            // Save material options
            if (isset($_POST['apd_material_value']) && isset($_POST['apd_material_label'])) {
                $material_values = $_POST['apd_material_value'];
                $material_labels = $_POST['apd_material_label'];
                
                foreach ($material_values as $idx => $value) {
                    if (!empty($value) && !empty($material_labels[$idx])) {
                        $variants_data['material_options'][] = array(
                            'value' => sanitize_text_field($value),
                            'label' => sanitize_text_field($material_labels[$idx])
                        );
                    }
                }
            }
            
            // Save combinations
            if (isset($_POST['apd_comb_size']) && is_array($_POST['apd_comb_size'])) {
                $comb_sizes = $_POST['apd_comb_size'];
                $comb_materials = isset($_POST['apd_comb_material']) ? $_POST['apd_comb_material'] : array();
                $comb_skus = isset($_POST['apd_comb_sku']) ? $_POST['apd_comb_sku'] : array();
                $comb_prices = isset($_POST['apd_comb_price']) ? $_POST['apd_comb_price'] : array();
                $comb_sale_prices = isset($_POST['apd_comb_sale_price']) ? $_POST['apd_comb_sale_price'] : array();
                $comb_stocks = isset($_POST['apd_comb_stock']) ? $_POST['apd_comb_stock'] : array();
                
                foreach ($comb_sizes as $idx => $size) {
                    $variants_data['combinations'][] = array(
                        'size' => sanitize_text_field($size),
                        'material' => sanitize_text_field($comb_materials[$idx]),
                        'sku' => sanitize_text_field($comb_skus[$idx]),
                        'price' => sanitize_text_field($comb_prices[$idx]),
                        'sale_price' => sanitize_text_field($comb_sale_prices[$idx]),
                        'stock' => sanitize_text_field($comb_stocks[$idx])
                    );
                }
            }
            
            update_post_meta($post_id, '_apd_variants', $variants_data);
            
            // Save variant-specific pricing tiers using index-based approach
            $pricing_service = new APD_Pricing_Service();
            $all_variant_tiers = array();
            
            // Get the SKU mapping from the form
            $sku_map = isset($_POST['apd_var_tier_sku_map']) ? $_POST['apd_var_tier_sku_map'] : array();
            
            foreach ($sku_map as $variant_idx => $sku) {
                if (empty($sku)) {
                    continue;
                }
                
                // Check for tier data for this variant index
                if (isset($_POST['apd_var_tier_minqty'][$variant_idx]) && is_array($_POST['apd_var_tier_minqty'][$variant_idx])) {
                    $variant_tiers = array();
                    $min_qtys = $_POST['apd_var_tier_minqty'][$variant_idx];
                    $discounts = isset($_POST['apd_var_tier_discount'][$variant_idx]) ? $_POST['apd_var_tier_discount'][$variant_idx] : array();
                    $names = isset($_POST['apd_var_tier_name'][$variant_idx]) ? $_POST['apd_var_tier_name'][$variant_idx] : array();
                    
                    foreach ($min_qtys as $tier_idx => $min_qty) {
                        if (!empty($min_qty) && isset($discounts[$tier_idx]) && $discounts[$tier_idx] !== '') {
                            $variant_tiers[] = array(
                                'min_qty' => intval($min_qty),
                                'discount_percent' => floatval($discounts[$tier_idx]),
                                'name' => isset($names[$tier_idx]) ? sanitize_text_field($names[$tier_idx]) : ''
                            );
                        }
                    }
                    
                    if (!empty($variant_tiers)) {
                        // Sort tiers by min_qty ascending
                        usort($variant_tiers, function($a, $b) {
                            return $a['min_qty'] - $b['min_qty'];
                        });
                        $all_variant_tiers[$sku] = $variant_tiers;
                    }
                }
            }
            
            // Save all variant tiers at once
            if (!empty($all_variant_tiers)) {
                update_post_meta($post_id, '_apd_variant_price_tiers', $all_variant_tiers);
            } else {
                delete_post_meta($post_id, '_apd_variant_price_tiers');
            }
        } else {
            // Variants disabled
            $variants_data = array(
                'enabled' => false,
                'size_options' => array(),
                'material_options' => array(),
                'combinations' => array()
            );
            update_post_meta($post_id, '_apd_variants', $variants_data);
        }

        // Save pricing tiers
        if (isset($_POST['apd_pricing_tiers_nonce']) && wp_verify_nonce($_POST['apd_pricing_tiers_nonce'], 'apd_pricing_tiers_meta')) {
            $pricing_service = new APD_Pricing_Service();
            
            $tiers = array();
            if (isset($_POST['apd_tier_min_qty']) && is_array($_POST['apd_tier_min_qty'])) {
                $min_qtys = $_POST['apd_tier_min_qty'];
                $discounts = isset($_POST['apd_tier_discount']) ? $_POST['apd_tier_discount'] : array();
                $names = isset($_POST['apd_tier_name']) ? $_POST['apd_tier_name'] : array();
                
                foreach ($min_qtys as $index => $min_qty) {
                    if (!empty($min_qty) && isset($discounts[$index]) && $discounts[$index] !== '') {
                        $tiers[] = array(
                            'min_qty' => intval($min_qty),
                            'discount_percent' => floatval($discounts[$index]),
                            'name' => isset($names[$index]) ? sanitize_text_field($names[$index]) : ''
                        );
                    }
                }
            }
            
            if (!empty($tiers)) {
                $pricing_service->save_price_tiers($post_id, $tiers);
            } else {
                $pricing_service->delete_price_tiers($post_id);
            }
        }
    }

    public function enqueue_scripts()
    {
        // Only enqueue customizer scripts on customizer pages
        if (get_query_var('customizer')) {
            wp_enqueue_style('apd-styles', APD_PLUGIN_URL . 'assets/css/customizer.css', array(), APD_VERSION);
            wp_enqueue_script('html2canvas', 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js', array('jquery'), '1.4.1', true);
            // Use consistent handle 'apd-customizer' instead of 'apd-script'
            wp_enqueue_script('apd-customizer', APD_PLUGIN_URL . 'assets/js/customizer.js', array('jquery', 'html2canvas'), APD_VERSION, true);

            // Get product ID for customizer
            $product_id = get_query_var('customizer');
            
            // Get template ID and customization options if product is loaded
            $template_id = 0;
            $enable_color_selection = 1;  // Default enabled (use integers for JavaScript)
            $enable_outline_selection = 1;  // Default enabled (use integers for JavaScript)
            
            if ($product_id) {
                $template_id = get_post_meta($product_id, '_fsc_template', true);
                
                // Get customization options
                $color_opt = get_post_meta($product_id, '_fsc_enable_color_selection', true);
                $outline_opt = get_post_meta($product_id, '_fsc_enable_outline_selection', true);
                
                // Default to enabled if not set, convert to integer (1 or 0) for JavaScript
                $enable_color_selection = ($color_opt === '' || $color_opt === '1') ? 1 : 0;
                $enable_outline_selection = ($outline_opt === '' || $outline_opt === '1') ? 1 : 0;
            }

            wp_localize_script('apd-customizer', 'apd_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                // Nonces used by various endpoints
                'nonce' => wp_create_nonce('apd_ajax_nonce'),
                'fsc_nonce' => wp_create_nonce('fsc_nonce'),
                'plugin_url' => APD_PLUGIN_URL,
                'site_url' => home_url(),
                'product_id' => $product_id,
                'template_id' => $template_id,
                'enable_color_selection' => $enable_color_selection,
                'enable_outline_selection' => $enable_outline_selection
            ));

            // Pass uploaded fonts to the customizer for font rendering
            $uploaded_fonts = get_option('apd_uploaded_fonts', array());
            wp_localize_script('apd-customizer', 'apdUploadedFonts', $uploaded_fonts);

            // Provide materials directly to the page to avoid extra AJAX calls
            $materials_map = $this->get_materials($template_id);
            // Convert to format expected by frontend: name => {url, price}
            wp_localize_script('apd-customizer', 'fscDefaults', array(
                'materials' => $materials_map
            ));

            // Debug: Log script enqueue
            error_log('APD Scripts: Enqueued for customizer with product_id: ' . $product_id);
        }
    }

    public function admin_enqueue_scripts()
    {
        wp_enqueue_style('apd-admin-styles', APD_PLUGIN_URL . 'assets/css/admin.css', array(), APD_VERSION);
        wp_enqueue_style('apd-admin-fixes', APD_PLUGIN_URL . 'assets/css/admin-fixes.css', array(), APD_VERSION);

        // Fix for WordPress dismissible notices
        add_action('admin_footer', function() {
            ?>
            <script>
            // Fix for dismissible notices - ensure elements exist before adding listeners
            (function() {
                if (typeof jQuery !== 'undefined') {
                    jQuery(document).ready(function($) {
                        $('.notice-dismiss').off('click.wp-dismiss-notice').on('click.wp-dismiss-notice', function(e) {
                            e.preventDefault();
                            $(this).closest('.notice').fadeOut();
                        });
                    });
                }
            })();
            </script>
            <?php
        });

        // Enqueue media uploader on product edit page
        $screen = get_current_screen();
        if ($screen && $screen->post_type === 'apd_product') {
            wp_enqueue_media();
            wp_enqueue_script('apd-product-admin', APD_PLUGIN_URL . 'assets/js/product-admin.js', array('jquery'), APD_VERSION, true);
        }

        // Enqueue designer scripts only on designer page
        if ($screen && strpos($screen->id, 'apd-designer') !== false) {
            wp_enqueue_script('apd-designer', APD_PLUGIN_URL . 'assets/js/designer.js', array('jquery'), APD_VERSION, true);

            wp_localize_script('apd-designer', 'apd_designer', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('apd_nonce'),
                'plugin_url' => APD_PLUGIN_URL
            ));

            // Add meta tags for nonce and AJAX URL
            add_action('admin_head', function () {
                echo '<meta name="apd-nonce" content="' . wp_create_nonce('apd_nonce') . '">';
                echo '<meta name="apd-ajax-url" content="' . admin_url('admin-ajax.php') . '">';
            });
        }
    }

    public function enqueue_block_editor_assets()
    {
        // This hook is specifically for block editor assets

        // Enqueue block editor scripts with proper dependencies
        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'apd-product-store',
            APD_PLUGIN_URL . 'assets/js/product-store.js',
            array('jquery'),
            APD_VERSION,
            true
        );

        wp_enqueue_script(
            'apd-product-block',
            APD_PLUGIN_URL . 'assets/js/product-block.js',
            array('apd-product-store', 'jquery'),
            APD_VERSION,
            true
        );

        wp_enqueue_style('apd-product-block', APD_PLUGIN_URL . 'assets/css/product-block.css', array(), APD_VERSION);

        wp_localize_script('apd-product-block', 'apd_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('apd_ajax_nonce'),
            'site_url' => home_url()
        ));
    }

    public function enqueue_frontend_scripts()
    {
        // Always ensure jQuery is enqueued first (WordPress should handle this, but be explicit)
        // Use priority to ensure jQuery loads early
        wp_enqueue_script('jquery');
        
        // Check if product list shortcode is used on the page
        global $post;
        $has_product_list = false;
        if ($post && isset($post->post_content)) {
            $has_product_list = has_shortcode($post->post_content, 'apd_product_list');
        }
        
        // Enqueue frontend scripts for product blocks
        wp_enqueue_script('apd-product-block-frontend', APD_PLUGIN_URL . 'assets/js/product-block-frontend.js', array('jquery'), APD_VERSION, true);
        wp_enqueue_style('apd-product-block', APD_PLUGIN_URL . 'assets/css/product-block.css', array(), APD_VERSION);

        // Inject uploaded fonts as inline CSS for frontend rendering
        $uploaded_fonts = get_option('apd_uploaded_fonts', array());
        if (!empty($uploaded_fonts)) {
            $font_css = '';
            foreach ($uploaded_fonts as $font) {
                if (!empty($font['family']) && !empty($font['url'])) {
                    $family_css = esc_attr($font['family']);
                    $url_css = esc_url($font['url']);
                    $weight_css = isset($font['weight']) ? esc_attr($font['weight']) : '400';
                    // Determine font format based on URL extension
                    $format = 'truetype';
                    if (strpos($url_css, '.woff2') !== false) {
                        $format = 'woff2';
                    } elseif (strpos($url_css, '.woff') !== false) {
                        $format = 'woff';
                    } elseif (strpos($url_css, '.otf') !== false) {
                        $format = 'opentype';
                    }
                    $font_css .= "@font-face{font-family:'{$family_css}';src:url('{$url_css}') format('{$format}');font-weight:{$weight_css};font-display:swap;}";
                }
            }
            if ($font_css) {
                wp_add_inline_style('apd-product-block', $font_css);
            }
        }

        // Enqueue customizer scripts and styles
        wp_enqueue_script('apd-product-customizer', APD_PLUGIN_URL . 'assets/js/product-customizer.js', array('jquery'), APD_VERSION, true);
        wp_enqueue_style('apd-product-customizer', APD_PLUGIN_URL . 'assets/css/product-customizer.css', array(), APD_VERSION);

        // NOTE: customizer.js is loaded by enqueue_scripts() on customizer pages only
        // Do NOT load it here to avoid duplicate loading and conflicts

        // Enqueue cart scripts and styles on cart page
        if (is_page() && (has_shortcode(get_post()->post_content, 'apd_cart') || is_page(get_option('apd_cart')))) {
            wp_enqueue_script('apd-cart', APD_PLUGIN_URL . 'assets/js/cart.js', array('jquery'), APD_VERSION, true);
            wp_enqueue_style('apd-cart', APD_PLUGIN_URL . 'assets/css/cart.css', array(), APD_VERSION);
        }

        // Enqueue orders scripts and styles on orders page
        if (is_page() && (has_shortcode(get_post()->post_content, 'apd_orders') || is_page(get_option('apd_orders')))) {
            wp_enqueue_script('apd-orders', APD_PLUGIN_URL . 'assets/js/orders.js', array('jquery'), APD_VERSION, true);
            wp_enqueue_style('apd-orders', APD_PLUGIN_URL . 'assets/css/orders.css', array(), APD_VERSION);
        }

        // Enqueue product variants script on product detail pages
        wp_enqueue_script('apd-product-variants', APD_PLUGIN_URL . 'assets/js/product-variants.js', array('jquery'), APD_VERSION, true);
        wp_localize_script('apd-product-variants', 'apdVariantsConfig', array(
            'homeUrl' => home_url(),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('apd_ajax_nonce')
        ));

        // Prepare apd_ajax data
        $apd_ajax_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('apd_ajax_nonce'),
            'site_url' => home_url(),
            'cart_url' => home_url(get_option('apd_cart_url', '/cart/')),
            'checkout_url' => home_url(get_option('apd_checkout_url', '/checkout/')),
            'products_url' => home_url(get_option('apd_products_url', '/products/')),
            'orders_url' => home_url(get_option('apd_orders_url', '/my-orders/')),
            'customizer_url' => home_url(get_option('apd_customizer_url', '/customizer/')),
            'thank_you_url' => home_url(get_option('apd_thank_you_url', '/thank-you/'))
        );
        
        // Add customization options if on customizer page
        if (get_query_var('customizer')) {
            $product_id = get_query_var('customizer');
            $template_id = 0;
            $enable_color_selection = 1;
            $enable_outline_selection = 1;
            
            if ($product_id) {
                $template_id = get_post_meta($product_id, '_fsc_template', true);
                $color_opt = get_post_meta($product_id, '_fsc_enable_color_selection', true);
                $outline_opt = get_post_meta($product_id, '_fsc_enable_outline_selection', true);
                
                $enable_color_selection = ($color_opt === '' || $color_opt === '1') ? 1 : 0;
                $enable_outline_selection = ($outline_opt === '' || $outline_opt === '1') ? 1 : 0;
            }
            
            $apd_ajax_data['product_id'] = $product_id;
            $apd_ajax_data['template_id'] = $template_id;
            $apd_ajax_data['enable_color_selection'] = $enable_color_selection;
            $apd_ajax_data['enable_outline_selection'] = $enable_outline_selection;
        }

        wp_localize_script('apd-product-block-frontend', 'apd_ajax', $apd_ajax_data);

        wp_localize_script('apd-product-customizer', 'apd_ajax', $apd_ajax_data);

        // Also localize for cart script
        if (is_page() && (has_shortcode(get_post()->post_content, 'apd_cart') || is_page(get_option('apd_cart')))) {
            wp_localize_script('apd-cart', 'apd_ajax', $apd_ajax_data);
            wp_localize_script('apd-orders', 'apd_ajax', $apd_ajax_data);
        }
    }

    public function render_customizer($product_id = 0)
    {
        // Debug: Log render_customizer call
        error_log('APD Render Customizer: Called with product_id: ' . $product_id);

        // Get product data if product_id is provided
        $product_data = null;
        if ($product_id > 0) {
            $product_data = get_post($product_id);
            error_log('APD Render Customizer: Product data retrieved: ' . ($product_data ? 'Found' : 'Not found'));
            if ($product_data) {
                error_log('APD Render Customizer: Product title: ' . $product_data->post_title . ', Type: ' . $product_data->post_type . ', Status: ' . $product_data->post_status);
            }
        }

        // Get all products for dropdown
        $all_products = get_posts(array(
            'post_type' => 'apd_product',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'post_status' => 'publish'
        ));

        // Get template ID from product if available
        $template_id = 0;
        if ($product_data) {
            $template_id = get_post_meta($product_data->ID, '_fsc_template', true);
        }

        // Get materials from uploads folder (filtered by template's allowed categories)
        $materials = $this->get_materials($template_id);

        // Debug: Log materials for troubleshooting
        error_log('FSC Materials loaded: ' . print_r($materials, true));

        // Get colors
        $colors = array(
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

        // Get product meta data
        $product_price = '';
        $product_sale_price = '';
        $product_material = '';
        $product_features = array();
        $product_logo_content = '';
        $enable_color_selection = 1; // Default enabled (integer)
        $enable_outline_selection = 1; // Default enabled (integer)

        if ($product_data) {
            $product_price = get_post_meta($product_data->ID, '_fsc_price', true);
            $product_sale_price = get_post_meta($product_data->ID, '_fsc_sale_price', true);
            $product_material = get_post_meta($product_data->ID, '_fsc_material', true);
            $product_features = get_post_meta($product_data->ID, '_fsc_features', true);
            
            // Get customization options
            $color_opt = get_post_meta($product_data->ID, '_fsc_enable_color_selection', true);
            $outline_opt = get_post_meta($product_data->ID, '_fsc_enable_outline_selection', true);
            // Default to enabled if not set, convert to integer for JavaScript
            $enable_color_selection = ($color_opt === '' || $color_opt === '1') ? 1 : 0;
            $enable_outline_selection = ($outline_opt === '' || $outline_opt === '1') ? 1 : 0;
            
            // Get logo URL - prefer attachment ID over meta field
            $logo_id = get_post_meta($product_data->ID, '_fsc_logo_id', true);
            $product_logo_url = '';
            
            if ($logo_id) {
                $product_logo_url = wp_get_attachment_url($logo_id);
                error_log('APD: Got logo from attachment ID ' . $logo_id . ': ' . ($product_logo_url ?: 'FAILED'));
            }
            
            if (!$product_logo_url) {
                $product_logo_url = get_post_meta($product_data->ID, '_fsc_logo_file', true);
                if ($product_logo_url) {
                    error_log('APD: Using logo_file meta as fallback: ' . $product_logo_url);
                }
            }

            // Get processed SVG content for product-specific logo
            if ($product_logo_url) {
                // Convert URL to file path using WordPress uploads directory
                $upload_dir = wp_upload_dir();
                $logo_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $product_logo_url);
                
                // Also try replacing the site URL for cases where full URL is stored
                if (!file_exists($logo_path)) {
                    $logo_path = str_replace(site_url(), ABSPATH, $product_logo_url);
                }
                
                error_log('APD: Attempting to load SVG from: ' . $logo_path);
                
                if (file_exists($logo_path)) {
                    $product_logo_content = $this->_get_processed_svg_content($logo_path);
                    
                    if ($product_logo_content) {
                        error_log('APD: Logo content loaded successfully, size: ' . strlen($product_logo_content) . ' bytes');
                    } else {
                        error_log('APD: Failed to get processed SVG content');
                    }
                } else {
                    error_log('APD: Logo file does not exist at path: ' . $logo_path);
                }
            }

            // Override colors if product has custom color options
            $custom_colors = get_post_meta($product_data->ID, '_fsc_color_options', true);
            if ($custom_colors) {
                $color_array = array_map('trim', explode(',', $custom_colors));
                $colors = array();
                foreach ($color_array as $color) {
                    if (isset($this->get_default_colors()[$color])) {
                        $colors[$color] = $this->get_default_colors()[$color];
                    }
                }
            }
        }

        // Do not inject mock defaults; leave empty if not available so frontend can handle gracefully

        include APD_PLUGIN_PATH . 'templates/customizer.php';
    }

    public function render_product_list($atts)
    {
        // Debug: Log shortcode call
        error_log('APD Product List: Shortcode called with atts: ' . print_r($atts, true));

        // Get all products
        $products = get_posts(array(
            'post_type' => 'apd_product',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ));

        // Debug: Log products found
        error_log('APD Product List: Found ' . count($products) . ' products');

        // If no products, show a message
        if (empty($products)) {
            return '<div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 10px 0; border-radius: 4px;">
                <strong>No Products Found:</strong> Please create some products in the admin panel first. 
                <a href="' . admin_url('post-new.php?post_type=apd_product') . '" target="_blank">Create Product</a>
            </div>';
        }

        // Group products by category
        $categories = array();
        foreach ($products as $product) {
            $category = get_post_meta($product->ID, '_fsc_category', true);
            if (empty($category)) {
                $category = 'Uncategorized';
            }

            if (!isset($categories[$category])) {
                $categories[$category] = array();
            }

            $price = get_post_meta($product->ID, '_fsc_price', true);
            $sale_price = get_post_meta($product->ID, '_fsc_sale_price', true);
            $features = get_post_meta($product->ID, '_fsc_features', true);
            $template_id = get_post_meta($product->ID, '_fsc_template', true);

            // Get custom thumbnail or fallback to featured image
            $thumbnail_id = get_post_meta($product->ID, '_fsc_thumbnail_id', true);
            $thumbnail_url = '';
            if ($thumbnail_id) {
                $thumbnail_url = wp_get_attachment_image_url($thumbnail_id, 'medium');
            }
            if (!$thumbnail_url) {
                $thumbnail_url = get_the_post_thumbnail_url($product->ID, 'medium');
            }

            $categories[$category][] = array(
                'id' => $product->ID,
                'title' => $product->post_title,
                'description' => wp_trim_words($product->post_content, 20),
                'content' => $product->post_content,
                'price' => $price ?: '0.00',
                'sale_price' => $sale_price,
                'features' => is_array($features) ? $features : array(),
                'template_id' => $template_id,
                'permalink' => get_permalink($product->ID),
                'thumbnail' => $thumbnail_url
            );
        }

        // Pass data to template
        // Debug: Log categories created
        error_log('APD Product List: Created ' . count($categories) . ' categories: ' . implode(', ', array_keys($categories)));

        $template_data = array(
            'categories' => $categories,
            'atts' => $atts,
            'show_title' => $atts['show_title'] === 'true',
            'show_description' => $atts['show_description'] === 'true',
            'show_price' => $atts['show_price'] === 'true',
            'show_sale' => $atts['show_sale'] === 'true',
            'columns' => intval($atts['columns']),
            'items_per_page' => intval($atts['items_per_page'])
        );

        // Debug: Log template data
        error_log('APD Product List: Template data prepared, including ' . count($template_data['categories']) . ' categories');

        // Include the product list template
        include APD_PLUGIN_PATH . 'templates/product-list.php';
    }

    public function render_products_by_company($atts)
    {
        // Get the company slug or name from attributes
        $company_slug = sanitize_text_field($atts['company']);
        
        if (empty($company_slug)) {
            return '<div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 10px 0; border-radius: 4px;">
                <strong>No Company Specified:</strong> Please specify a company using the "company" attribute. Example: [apd_products_by_company company="company-slug"]
            </div>';
        }

        // Get all products for the specified company
        $args = array(
            'post_type' => 'apd_product',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
            'tax_query' => array(
                array(
                    'taxonomy' => 'apd_company',
                    'field' => 'slug',
                    'terms' => $company_slug,
                ),
            ),
        );

        $products = get_posts($args);

        // Get company term for display
        $company_term = get_term_by('slug', $company_slug, 'apd_company');
        $company_name = $company_term ? $company_term->name : ucwords(str_replace('-', ' ', $company_slug));

        // If no products, show a message
        if (empty($products)) {
            return '<div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 10px 0; border-radius: 4px;">
                <strong>No Products Found:</strong> No products are assigned to the company "' . esc_html($company_name) . '". 
                <a href="' . admin_url('edit.php?post_type=apd_product') . '" target="_blank">Manage Products</a>
            </div>';
        }

        // Group products by category (same as render_product_list)
        $categories = array();
        foreach ($products as $product) {
            $category = get_post_meta($product->ID, '_fsc_category', true);
            if (empty($category)) {
                $category = 'Uncategorized';
            }

            if (!isset($categories[$category])) {
                $categories[$category] = array();
            }

            $price = get_post_meta($product->ID, '_fsc_price', true);
            $sale_price = get_post_meta($product->ID, '_fsc_sale_price', true);
            $features = get_post_meta($product->ID, '_fsc_features', true);
            $template_id = get_post_meta($product->ID, '_fsc_template', true);

            // Get custom thumbnail or fallback to featured image
            $thumbnail_id = get_post_meta($product->ID, '_fsc_thumbnail_id', true);
            $thumbnail_url = '';
            if ($thumbnail_id) {
                $thumbnail_url = wp_get_attachment_image_url($thumbnail_id, 'medium');
            }
            if (!$thumbnail_url) {
                $thumbnail_url = get_the_post_thumbnail_url($product->ID, 'medium');
            }

            $categories[$category][] = array(
                'id' => $product->ID,
                'title' => $product->post_title,
                'description' => wp_trim_words($product->post_content, 20),
                'content' => $product->post_content,
                'price' => $price ?: '0.00',
                'sale_price' => $sale_price,
                'features' => is_array($features) ? $features : array(),
                'template_id' => $template_id,
                'permalink' => get_permalink($product->ID),
                'thumbnail' => $thumbnail_url
            );
        }

        // Pass data to template
        $template_data = array(
            'categories' => $categories,
            'atts' => $atts,
            'show_title' => $atts['show_title'] === 'true',
            'show_description' => $atts['show_description'] === 'true',
            'show_price' => $atts['show_price'] === 'true',
            'show_sale' => $atts['show_sale'] === 'true',
            'columns' => intval($atts['columns']),
            'items_per_page' => intval($atts['items_per_page']),
            'company_name' => $company_name,
            'hide_header' => $atts['hide_header'] === 'true'
        );

        // Include the product list template
        include APD_PLUGIN_PATH . 'templates/product-list.php';
    }

    public function test_shortcode($atts)
    {
        return '<div style="background: #f0f0f0; padding: 20px; border: 2px solid #333; margin: 10px 0;">TEST SHORTCODE WORKS! Current time: ' . date('Y-m-d H:i:s') . '</div>';
    }

    public function debug_shortcode($atts)
    {
        return '<div style="background: #e1f5fe; padding: 20px; border: 2px solid #2196f3; margin: 10px 0;">
            <h3>Debug Info:</h3>
            <p><strong>Shortcode called:</strong> ' . date('Y-m-d H:i:s') . '</p>
            <p><strong>Products found:</strong> ' . count(get_posts(array('post_type' => 'apd_product', 'posts_per_page' => -1, 'post_status' => 'publish'))) . '</p>
            <p><strong>Plugin active:</strong> ' . (is_plugin_active('freight-signs-customizer/freight-signs-customizer.php') ? 'Yes' : 'No') . '</p>
            <p><strong>Customizer Query Var:</strong> ' . get_query_var('customizer') . '</p>
            <p><strong>Test Customizer URL:</strong> <a href="' . home_url('/customizer/1/') . '">/customizer/1/</a></p>
        </div>';
    }

    public function add_form_enctype()
    {
        global $post_type;
        if ($post_type === 'apd_product') {
            echo ' enctype="multipart/form-data"';
        }
    }

    public function get_default_colors()
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

    public function get_materials($template_id = 0)
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
                'Engine turn_gold' => array(
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
     */
    private function _get_processed_svg_content($logo_path)
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
        
        // Strip HTML/XML comments that appear before the SVG tag
        $svg_content = preg_replace('/<!--[\s\S]*?-->/i', '', $svg_content);
        
        // Trim whitespace
        $svg_content = trim($svg_content);

        // Extract only the <svg>...</svg> fragment (more robust than checking if it starts with <svg)
        if (preg_match('/<svg[\s\S]*?<\/svg>/i', $svg_content, $m)) {
            $svg_content = $m[0];
            error_log('APD: Extracted SVG tag successfully, size: ' . strlen($svg_content) . ' bytes');
        } else {
            error_log('APD: Could not find complete <svg>...</svg> tags in content. First 200 chars: ' . substr($svg_content, 0, 200));
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
            $filter_def = '<filter id="fsc-outline"><feMorphology operator="dilate" radius="2"/><feComposite operator="out" in="SourceGraphic"/></filter>';
            
            // Check if <defs> exists
            if (stripos($svg_content, '<defs>') !== false) {
                // Add filter inside existing <defs>
                $svg_content = preg_replace('/<defs>/i', '<defs>' . $filter_def, $svg_content, 1);
            } else {
                // Create new <defs> with filter after opening <svg> tag
                $svg_content = preg_replace('/(<svg[^>]*>)/i', '$1<defs>' . $filter_def . '</defs>', $svg_content, 1);
            }
        }
        
        error_log('APD: Processed SVG successfully, final size: ' . strlen($svg_content) . ' bytes');

        return $svg_content ?: false;
    }

    public function get_logo_svg()
    {
        $plugin_dir = APD_PLUGIN_PATH;
        $logo_path = $plugin_dir . 'uploads/object/Logo-PNG.svg';
        return $this->_get_processed_svg_content($logo_path);
    }

    // ========================================
    // AJAX methods moved to includes/class-ajax-handlers.php
    // ========================================

    private function get_cart()
    {
        // Delegate to cart service via plugin instance public getter
        $cart_service = $this->plugin->get_cart_service();
        if ($cart_service) {
            return $cart_service->get_cart();
        }
        return array();
    }

    private function save_cart($cart)
    {
        // Delegate to cart service via plugin instance public getter
        $cart_service = $this->plugin->get_cart_service();
        if ($cart_service) {
            return $cart_service->save_cart($cart);
        }
        return false;
        
        error_log('APD save_cart(): Verification - SESSION apd_cart now has ' . count($_SESSION['apd_cart']) . ' items');
    }

    // Handle PNG data URL upload and return a media URL
    public function apd_save_customization_image()
    {
        // Accept multiple nonce field names for compatibility
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : (isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : (isset($_POST['security']) ? $_POST['security'] : (isset($_POST['apd_nonce']) ? $_POST['apd_nonce'] : '')));
        if ($nonce && !(wp_verify_nonce($nonce, 'fsc_nonce') || wp_verify_nonce($nonce, 'apd_ajax_nonce'))) {
            // Soft fail: allow if logged-in; otherwise continue but note we didn't verify
        }
        if (!isset($_POST['image'])) {
            wp_send_json_error(array('message' => 'No image provided'), 400);
        }
        $data_url = $_POST['image'];
        if (strpos($data_url, 'data:image/png;base64,') !== 0) {
            wp_send_json_error(array('message' => 'Invalid image format'), 400);
        }
        $raw = base64_decode(substr($data_url, strlen('data:image/png;base64,')));
        if ($raw === false) {
            wp_send_json_error(array('message' => 'Decode failed'), 400);
        }
        // Optional size cap 8MB
        if (strlen($raw) > 8 * 1024 * 1024) {
            wp_send_json_error(array('message' => 'Image too large'), 400);
        }
        // Store file in uploads
        $upload = wp_upload_bits('customization-' . time() . '-' . wp_generate_password(6, false, false) . '.png', null, $raw);
        if (!empty($upload['error'])) {
            wp_send_json_error(array('message' => $upload['error']), 500);
        }
        // Optionally, register as attachment
        $file_url = $upload['url'];
        wp_send_json_success(array('url' => esc_url($file_url)));
    }

    public function load_product()
    {
        // Accept either FSC nonce or APD ajax nonce for compatibility
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        $valid = false;
        if ($nonce && wp_verify_nonce($nonce, 'fsc_nonce')) {
            $valid = true;
        }
        if (!$valid && $nonce && wp_verify_nonce($nonce, 'apd_ajax_nonce')) {
            $valid = true;
        }
        if (!$valid) {
            wp_send_json_error(array('message' => 'Security check failed (invalid nonce)'));
        }

        $product_id = intval($_POST['product_id']);

        if ($product_id <= 0) {
            wp_send_json_error(array('message' => 'Invalid product ID'));
        }

        $product = get_post($product_id);

        if (!$product || $product->post_type !== 'apd_product') {
            wp_send_json_error(array('message' => 'Product not found'));
        }

        // Get product meta data
        $price = get_post_meta($product_id, '_fsc_price', true);
        $material = get_post_meta($product_id, '_fsc_material', true);
        $features = get_post_meta($product_id, '_fsc_features', true);
        $color_options = get_post_meta($product_id, '_fsc_color_options', true);
        $template_id = get_post_meta($product_id, '_fsc_template', true);
        
        // Get logo URL - prefer attachment ID over meta field
        $logo_id = get_post_meta($product_id, '_fsc_logo_id', true);
        $product_logo_url = '';
        
        if ($logo_id) {
            $product_logo_url = wp_get_attachment_url($logo_id);
        }
        
        if (!$product_logo_url) {
            $product_logo_url = get_post_meta($product_id, '_fsc_logo_file', true);
        }

        // Get processed SVG content for product-specific logo
        $product_logo_content = '';
        if ($product_logo_url) {
            // Convert URL to file path using WordPress uploads directory
            $upload_dir = wp_upload_dir();
            $logo_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $product_logo_url);
            
            // Also try replacing the site URL for cases where full URL is stored
            if (!file_exists($logo_path)) {
                $logo_path = str_replace(site_url(), ABSPATH, $product_logo_url);
            }
            
            if (file_exists($logo_path)) {
                $product_logo_content = $this->_get_processed_svg_content($logo_path);
            }
        }

        // Do not inject mock defaults; leave empty values so UI reflects real data
        // $price, $material, $features, $product_logo_content may be empty if not set

        // Process color options: only include colors explicitly configured
        $colors = array();
        if ($color_options) {
            $default_colors = $this->get_default_colors();
            $color_array = array_map('trim', explode(',', $color_options));
            foreach ($color_array as $color) {
                if (isset($default_colors[$color])) {
                    $colors[$color] = $default_colors[$color];
                }
            }
        }

        // Resolve template data (if linked)
        $template_data = null;
        if ($template_id) {
            $template_post = get_post($template_id);
            if ($template_post && $template_post->post_type === 'apd_template') {
                error_log('APD load_product: template_id=' . $template_id . ' title=' . $template_post->post_title);
                $template_data_raw = get_post_meta($template_id, '_apd_template_data', true);
                if ($template_data_raw) {
                    $decoded = json_decode($template_data_raw, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $template_data = $decoded;
                        error_log('APD load_product: template_data decoded OK (array)');
                    } else {
                        // Try if stored as already-decoded array/string
                        $template_data = $template_data_raw;
                        error_log('APD load_product: template_data not JSON, using raw value');
                    }
                } else {
                    // Fallbacks: sometimes stored in post_content
                    $content = trim($template_post->post_content);
                    if ($content) {
                        $decoded = json_decode($content, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $template_data = $decoded;
                            error_log('APD load_product: template_data loaded from post_content JSON');
                        } else {
                            error_log('APD load_product: no _apd_template_data and post_content not JSON');
                        }
                    } else {
                        error_log('APD load_product: no _apd_template_data and empty post_content');
                    }
                }
            }
        }

        $response_data = array(
            'id' => $product_id,
            'title' => $product->post_title,
            'price' => $price,
            'material' => $material,
            'features' => is_array($features) ? $features : array(),
            'colors' => $colors,
            'logo_content' => $product_logo_content,
            'url' => get_permalink($product_id),
            'template_id' => $template_id ? intval($template_id) : 0,
            'template_data' => $template_data,
            'templateData' => $template_data  // Keep both for backwards compatibility
        );

        if ($template_id && $template_data) {
            error_log('APD load_product: returning templateData for product ' . $product_id);
        } else if ($template_id && !$template_data) {
            error_log('APD load_product: template linked but no templateData for product ' . $product_id);
        } else {
            error_log('APD load_product: no template linked for product ' . $product_id);
        }

        wp_send_json_success($response_data);
    }

    // Activation methods moved to includes/class-activation.php

    // Register lightweight order type and statuses
    public function register_order_cpt_and_statuses()
    {
        // Custom post type for orders
        register_post_type('apd_order', array(
            'labels' => array(
                'name' => 'Orders',
                'singular_name' => 'Order'
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'apd-dashboard',
            'supports' => array('title', 'custom-fields'),
            'capability_type' => 'post'
        ));
        // Statuses - only the 3 we actually use
        $statuses = array(
            'apd_pending' => 'Pending',
            'apd_confirmed' => 'Confirmed',
            'apd_completed' => 'Completed'
        );
        foreach ($statuses as $key => $label) {
            if (!post_type_exists('apd_order'))
                break;
            register_post_status($key, array(
                'label' => $label,
                'public' => false,
                'internal' => false,
                'label_count' => _n_noop($label . ' <span class="count">(%s)</span>', $label . ' <span class="count">(%s)</span>')
            ));
        }
    }

    // AJAX: place order from checkout
    public function apd_place_order()
    {
        // Turn off error reporting for clean JSON response
        $error_reporting = error_reporting(0);
        $display_errors = ini_get('display_errors');
        ini_set('display_errors', 0);
        
        // Verify nonce
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : (isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : '');
        if ($nonce && !(wp_verify_nonce($nonce, APD_Config::NONCE_ACTION_AJAX) || wp_verify_nonce($nonce, APD_Config::NONCE_ACTION_FSC))) {
            error_reporting($error_reporting);
            ini_set('display_errors', $display_errors);
            wp_send_json_error('Security check failed');
            return;
        }

        // Prepare customer data
        $customer_data = array(
            'customer_name' => sanitize_text_field(isset($_POST['customer_name']) ? $_POST['customer_name'] : ''),
            'customer_email' => sanitize_email(isset($_POST['customer_email']) ? $_POST['customer_email'] : ''),
            'customer_phone' => sanitize_text_field(isset($_POST['customer_phone']) ? $_POST['customer_phone'] : ''),
            'customer_address' => sanitize_textarea_field(isset($_POST['customer_address']) ? $_POST['customer_address'] : '')
        );

        // Prepare payment data
        $payment_method = sanitize_text_field(isset($_POST['payment_method']) ? $_POST['payment_method'] : APD_Config::PAYMENT_METHOD_PAYPAL);
        $payment_data = array(
            'payment_method' => $payment_method,
            'paypal_order_id' => sanitize_text_field(isset($_POST['paypal_order_id']) ? $_POST['paypal_order_id'] : ''),
            'paypal_transaction_id' => sanitize_text_field(isset($_POST['paypal_transaction_id']) ? $_POST['paypal_transaction_id'] : ''),
            'paypal_payer_id' => sanitize_text_field(isset($_POST['paypal_payer_id']) ? $_POST['paypal_payer_id'] : ''),
            'payment_status' => ($payment_method === APD_Config::PAYMENT_METHOD_MOCK_PAYPAL) ? 'completed' : sanitize_text_field(isset($_POST['payment_status']) ? $_POST['payment_status'] : 'completed')
        );

        // Get cart items (prefer POST, then service cart)
        $cart_items = null;
        if (isset($_POST['cart'])) {
            $posted_cart = json_decode(stripslashes($_POST['cart']), true);
            if (is_array($posted_cart)) {
                $cart_items = $posted_cart;
            }
        }

        // Create order using OrderService via plugin instance public getter
        $order_service = $this->plugin->get_order_service();
        if (!$order_service) {
            wp_send_json_error('Order service not available');
            return;
        }
        $order_id = $order_service->create_order($customer_data, $payment_data, $cart_items);

        // Restore error reporting
        error_reporting($error_reporting);
        ini_set('display_errors', $display_errors);

        // Handle errors
        if (is_wp_error($order_id)) {
            wp_send_json_error(array('message' => $order_id->get_error_message()));
            return;
        }

        // Get thank you page URL via plugin instance public getter
        $order_service = $this->plugin->get_order_service();
        if (!$order_service) {
            wp_send_json_error('Order service not available');
            return;
        }
        $thankyou_url = $order_service->get_thank_you_url();

        wp_send_json_success(array(
            'order_id' => $order_id,
            'redirect' => esc_url($thankyou_url)
        ));
    }

    public function apd_get_order_details()
    {
        // Verify nonce for security
        if (!wp_verify_nonce(isset($_POST['nonce']) ? $_POST['nonce'] : '', 'apd_order_details')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        $order_id = intval(isset($_POST['order_id']) ? $_POST['order_id'] : 0);
        if (!$order_id) {
            wp_send_json_error('Invalid order ID');
            return;
        }

        // Get order using OrderService via plugin instance public getter
        $order_service = $this->plugin->get_order_service();
        if (!$order_service) {
            wp_send_json_error('Order service not available');
            return;
        }
        $order_data = $order_service->get_order($order_id);

        if (is_wp_error($order_data)) {
            wp_send_json_error($order_data->get_error_message());
            return;
        }

    }

    // --- Admin: Register/ensure statuses visible in All ---
    // Enhanced cart shortcode with live preview
    private function maybe_create_core_pages()
    {
        // Define pages with their shortcodes and page templates
        $pages = array(
            'apd_cart' => array(
                'title' => 'Cart',
                'slug' => 'cart',
                'content' => '[apd_cart]',
                'template' => '' // Cart doesn't use page template, only shortcode
            ),
            'apd_checkout' => array(
                'title' => 'Checkout',
                'slug' => 'checkout',
                'content' => '[apd_checkout]',
                'template' => 'templates/page-checkout.php'
            ),
            'apd_thankyou' => array(
                'title' => 'Thank You',
                'slug' => 'thank-you',
                'content' => '[apd_thank_you]',
                'template' => 'templates/page-thankyou.php'
            ),
            'apd_orders' => array(
                'title' => 'My Orders',
                'slug' => 'my-orders',
                'content' => '[apd_orders]',
                'template' => 'templates/page-orders.php'
            ),
            'apd_product_list' => array(
                'title' => 'Product',
                'slug' => 'product',
                'content' => '[apd_product_list]',
                'template' => 'templates/page-product-list.php'
            ),
            'apd_product_detail' => array(
                'title' => 'Product Detail',
                'slug' => 'product-detail',
                'content' => '[apd_product_detail]',
                'template' => 'templates/page-product-detail.php'
            ),
        );
        
        foreach ($pages as $opt_key => $def) {
            // Check if page already exists by slug
            $existing = get_page_by_path($def['slug']);
            
            if ($existing && $existing->ID) {
                // Page exists, update option with its ID
                update_option($opt_key, intval($existing->ID));
                
                // Assign page template if specified and not already set
                if (!empty($def['template'])) {
                    $current_template = get_post_meta($existing->ID, '_wp_page_template', true);
                    if ($current_template !== $def['template']) {
                        update_post_meta($existing->ID, '_wp_page_template', $def['template']);
                        error_log('APD: Assigned template "' . $def['template'] . '" to existing page "' . $def['title'] . '"');
                    }
                }
                error_log('APD: Found existing page "' . $def['title'] . '" with ID ' . $existing->ID);
                continue;
            }

            // Check if option already has a valid page ID
            $existing_id = get_option($opt_key);
            if ($existing_id && get_post($existing_id)) {
                // Assign page template if specified and not already set
                if (!empty($def['template'])) {
                    $current_template = get_post_meta($existing_id, '_wp_page_template', true);
                    if ($current_template !== $def['template']) {
                        update_post_meta($existing_id, '_wp_page_template', $def['template']);
                        error_log('APD: Assigned template "' . $def['template'] . '" to existing page "' . $def['title'] . '"');
                    }
                }
                continue;
            }

            // Create the page
            $page_data = array(
                'post_title' => $def['title'],
                'post_name' => $def['slug'],
                'post_content' => $def['content'],
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_author' => get_current_user_id() ? get_current_user_id() : 1
            );
            
            $id = wp_insert_post($page_data);
            
            if (!is_wp_error($id) && $id) {
                update_option($opt_key, intval($id));
                
                // Assign page template if specified
                if (!empty($def['template'])) {
                    update_post_meta($id, '_wp_page_template', $def['template']);
                    error_log('APD: Created page "' . $def['title'] . '" with ID ' . $id . ' and assigned template "' . $def['template'] . '"');
                } else {
                    error_log('APD: Created page "' . $def['title'] . '" with ID ' . $id);
                }
            } else {
                error_log('APD: Failed to create page "' . $def['title'] . '"');
            }
        }
    }

    // Run on admin/init to backfill pages if missing
    public function ensure_core_pages()
    {
        // Only run for admins to avoid overhead
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $needed = array(
            get_option('apd_cart') => 'cart',
            get_option('apd_checkout') => 'checkout',
            get_option('apd_thankyou') => 'thank-you',
            get_option('apd_orders') => 'my-orders',
            get_option('apd_product_list') => 'product',
            get_option('apd_product_detail') => 'product-detail',
        );
        
        $missing = false;
        foreach ($needed as $optId => $slug) {
            if (!$optId || !get_post($optId)) {
                $missing = true;
                error_log('APD: Missing page for option ' . $slug . ' (ID: ' . $optId . ')');
                break;
            }
        }
        
        if ($missing) {
            error_log('APD: Ensuring core pages exist...');
            $this->maybe_create_core_pages();
            flush_rewrite_rules(false);
        }
    }

    public function add_query_vars($vars)
    {
        $vars[] = 'customizer';
        // Removed product_detail - using page instead
        // $vars[] = 'product_detail';
        return $vars;
    }

    public function template_redirect()
    {
        $customizer_id = get_query_var('customizer');
        if ($customizer_id) {
            // Debug: Log customizer redirect
            error_log('APD Template Redirect: Customizer detected with ID: ' . $customizer_id);

            // Get header and footer
            get_header();

            // Render the original customizer with product ID
            $this->render_customizer($customizer_id);

            get_footer();
            exit;
        }

        // Removed product_detail redirect - using page instead
        // if (get_query_var('product_detail')) {
        //     include APD_PLUGIN_PATH . 'templates/product-detail-page.php';
        //     exit;
        // }
    }

    public function load_single_product_template($template)
    {
        global $post;

        if ($post && $post->post_type === 'apd_product') {
            $custom_template = APD_PLUGIN_PATH . 'templates/single-apd_product.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }

        return $template;
    }

    public function load_company_taxonomy_template($template)
    {
        // Check if we're viewing a company taxonomy archive
        if (is_tax('apd_company')) {
            // Set Elementor canvas template (full width)
            add_filter('template_include', function($template) {
                // Force Elementor canvas template for full width
                if (function_exists('elementor_theme_do_location')) {
                    add_filter('elementor/theme/get_location_templates/template_id', function() {
                        return 'elementor_canvas';
                    });
                }
                return $template;
            }, 1);
            
            $custom_template = APD_PLUGIN_PATH . 'templates/taxonomy-apd_company.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }

        return $template;
    }

    public function set_company_archive_elementor_template()
    {
        if (is_tax('apd_company')) {
            // Force Elementor Canvas template (full width, no header/footer from theme)
            add_filter('template_include', function($template) {
                // Set page template meta for Elementor
                global $wp_query;
                if (isset($wp_query->queried_object_id)) {
                    // Elementor Canvas = full width
                    add_filter('elementor/page/get_template', function() {
                        return 'elementor_canvas';
                    });
                }
                return $template;
            }, 999);
        }
    }

    public function product_detail_shortcode($atts)
    {
        // Parse shortcode attributes
        $atts = shortcode_atts(array(
            'id' => 0,
            'show_price' => 'true',
            'show_description' => 'true',
            'show_features' => 'true',
            'show_specs' => 'true',
            'show_related' => 'true'
        ), $atts);

        $product_id = intval($atts['id']);

        if ($product_id <= 0) {
            return '<div class="apd-error">Invalid product ID provided.</div>';
        }

        // Get product data
        $product = get_post($product_id);

        if (!$product || $product->post_type !== 'apd_product') {
            return '<div class="apd-error">Product not found.</div>';
        }

        // Get product meta data
        $price = get_post_meta($product_id, '_fsc_price', true);
        $material = get_post_meta($product_id, '_fsc_material', true);
        $features = get_post_meta($product_id, '_fsc_features', true);
        $category = get_post_meta($product_id, '_fsc_category', true);
        $logo_url = get_post_meta($product_id, '_fsc_logo_file', true);

        // Build HTML output
        ob_start();
        ?>
        <div class="apd-product-detail-wrapper">
            <article class="apd-single-product">
                
                <!-- Product Header -->
                <div class="apd-product-header">
                    <h1 class="apd-product-title"><?php echo esc_html($product->post_title); ?></h1>
                    <?php if ($atts['show_price'] === 'true' && $price): ?>
                        <div class="apd-product-price">$<?php echo esc_html($price); ?></div>
                    <?php endif; ?>
                </div>

                <!-- Product Content -->
                <div class="apd-product-content">
                    
                    <!-- Product Image/Gallery -->
                    <div class="apd-product-gallery">
                        <?php if (has_post_thumbnail($product_id)): ?>
                            <div class="apd-product-image">
                                <?php echo get_the_post_thumbnail($product_id, 'large'); ?>
                            </div>
                        <?php elseif ($logo_url): ?>
                            <div class="apd-product-image">
                                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($product->post_title); ?>" />
                            </div>
                        <?php else: ?>
                            <div class="apd-product-image">
                                <img src="<?php echo APD_PLUGIN_URL; ?>assets/images/placeholder.png" alt="<?php echo esc_attr($product->post_title); ?>" />
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Product Details -->
                    <div class="apd-product-details">
                        
                        <!-- Product Description -->
                        <?php if ($atts['show_description'] === 'true' && $product->post_content): ?>
                            <div class="apd-product-description">
                                <h3>Description</h3>
                                <?php echo wpautop($product->post_content); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Product Features -->
                        <?php if ($atts['show_features'] === 'true' && $features && is_array($features)): ?>
                            <div class="apd-product-features">
                                <h3>Features & Benefits</h3>
                                <ul class="apd-feature-list">
                                    <?php foreach ($features as $feature): ?>
                                        <li><?php echo esc_html($feature); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <!-- Product Specifications -->
                        <?php if ($atts['show_specs'] === 'true' && $material): ?>
                            <div class="apd-product-specs">
                                <h3>Specifications</h3>
                                <div class="apd-spec-item">
                                    <strong>Material:</strong> <?php echo esc_html($material); ?>
                                </div>
                                <?php if ($category): ?>
                                    <div class="apd-spec-item">
                                        <strong>Category:</strong> <?php echo esc_html($category); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Customizer Actions -->
                        <div class="apd-product-actions">
                            <div class="apd-action-buttons">
                                <a href="<?php echo home_url('/customizer/' . $product_id . '/'); ?>" 
                                   class="apd-btn apd-btn-primary apd-btn-customize"
                                   data-product-id="<?php echo $product_id; ?>">
                                    <span class="btn-icon">🎨</span>
                                    <span class="btn-text">Customize This Product</span>
                                </a>
                                
                                <button class="apd-btn apd-btn-secondary apd-btn-add-cart" 
                                        data-product-id="<?php echo $product_id; ?>">
                                    <span class="btn-icon">🛒</span>
                                    <span class="btn-text">Add to Cart</span>
                                </button>
                            </div>
                            
                            <!-- Quantity Selector -->
                            <div class="apd-quantity-selector">
                                <label for="apd-quantity-<?php echo $product_id; ?>">Quantity:</label>
                                <div class="apd-quantity-controls">
                                    <button type="button" class="apd-qty-btn apd-qty-minus" data-target="apd-quantity-<?php echo $product_id; ?>">−</button>
                                    <input type="number" id="apd-quantity-<?php echo $product_id; ?>" value="1" min="1" max="100" class="apd-quantity-input">
                                    <button type="button" class="apd-qty-btn apd-qty-plus" data-target="apd-quantity-<?php echo $product_id; ?>">+</button>
                                </div>
                            </div>

                            <!-- Quick Preview -->
                            <div class="apd-quick-preview">
                                <button class="apd-btn apd-btn-outline apd-btn-preview" data-product-id="<?php echo $product_id; ?>">
                                    👁️ Quick Preview
                                </button>
                            </div>
                        </div>

                        <!-- Related Products -->
                        <?php if ($atts['show_related'] === 'true'): ?>
                            <?php
                            $related_products = get_posts(array(
                                'post_type' => 'apd_product',
                                'posts_per_page' => 4,
                                'post__not_in' => array($product_id),
                                'meta_key' => '_fsc_category',
                                'meta_value' => $category
                            ));

                            if ($related_products):
                                ?>
                                <div class="apd-related-products">
                                    <h3>Related Products</h3>
                                    <div class="apd-related-grid">
                                        <?php foreach ($related_products as $related): ?>
                                            <div class="apd-related-item">
                                                <a href="<?php echo home_url('/product-detail/?id=' . $related->ID); ?>">
                                                    <?php if (has_post_thumbnail($related->ID)): ?>
                                                        <?php echo get_the_post_thumbnail($related->ID, 'medium'); ?>
                                                    <?php else: ?>
                                                        <img src="<?php echo APD_PLUGIN_URL; ?>assets/images/placeholder.png" alt="<?php echo esc_attr($related->post_title); ?>" />
                                                    <?php endif; ?>
                                                    <h4><?php echo esc_html($related->post_title); ?></h4>
                                                    <?php if (get_post_meta($related->ID, '_fsc_price', true)): ?>
                                                        <div class="apd-related-price">$<?php echo esc_html(get_post_meta($related->ID, '_fsc_price', true)); ?></div>
                                                    <?php endif; ?>
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                    </div>
                </div>
                
            </article>
        </div>

        <style>
        .apd-product-detail-wrapper {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .apd-single-product {
            width: 100%;
        }

        .apd-product-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }

        .apd-product-title {
            font-size: 2.5rem;
            margin: 0 0 10px 0;
            font-weight: bold;
        }

        .apd-product-price {
            font-size: 1.5rem;
            font-weight: 600;
            opacity: 0.9;
        }

        .apd-product-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            padding: 40px;
        }

        .apd-product-gallery {
            position: sticky;
            top: 20px;
        }

        .apd-product-image img {
            width: 100%;
            height: auto;
            border-radius: 8px;
        }

        .apd-product-details h3 {
            color: #333;
            font-size: 1.5rem;
            margin: 30px 0 15px 0;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }

        .apd-product-description {
            margin-bottom: 30px;
        }

        .apd-feature-list {
            list-style: none;
            padding: 0;
        }

        .apd-feature-list li {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            position: relative;
            padding-left: 25px;
        }

        .apd-feature-list li:before {
            content: "✓";
            color: #28a745;
            font-weight: bold;
            position: absolute;
            left: 0;
        }

        .apd-spec-item {
            padding: 8px 0;
            color: #666;
        }

        .apd-product-actions {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 8px;
            margin: 30px 0;
        }

        .apd-action-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .apd-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .apd-btn:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .apd-btn:hover:before {
            left: 100%;
        }

        .apd-btn-primary {
            background: #667eea;
            color: white;
        }

        .apd-btn-primary:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }

        .apd-btn-secondary {
            background: #6c757d;
            color: white;
        }

        .apd-btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .apd-quantity-selector {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .apd-quantity-controls {
            display: flex;
            align-items: center;
            border: 1px solid #ddd;
            border-radius: 6px;
            overflow: hidden;
        }

        .apd-qty-btn {
            background: #f8f9fa;
            border: none;
            padding: 8px 12px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.2s ease;
            user-select: none;
        }

        .apd-qty-btn:hover {
            background: #e9ecef;
        }

        .apd-qty-btn:active {
            background: #dee2e6;
        }

        .apd-quantity-input {
            width: 60px;
            padding: 8px;
            border: none;
            text-align: center;
            font-weight: 600;
            outline: none;
        }

        .apd-btn-outline {
            background: transparent;
            border: 2px solid #667eea;
            color: #667eea;
        }

        .apd-btn-outline:hover {
            background: #667eea;
            color: white;
        }

        .apd-quick-preview {
            margin-top: 15px;
        }

        .btn-icon {
            margin-right: 8px;
        }

        .btn-text {
            transition: all 0.3s ease;
        }

        .apd-related-products {
            margin-top: 50px;
        }

        .apd-related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .apd-related-item {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .apd-related-item:hover {
            transform: translateY(-5px);
        }

        .apd-related-item a {
            text-decoration: none;
            color: inherit;
        }

        .apd-related-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }

        .apd-related-item h4 {
            padding: 15px;
            margin: 0;
            font-size: 1rem;
        }

        .apd-related-price {
            padding: 0 15px 15px;
            font-weight: 600;
            color: #667eea;
        }

        .apd-error {
            background: #ffebee;
            border: 1px solid #f44336;
            color: #c62828;
            padding: 20px;
            border-radius: 4px;
            text-align: center;
        }

        @media (max-width: 768px) {
            .apd-product-content {
                grid-template-columns: 1fr;
                gap: 20px;
                padding: 20px;
            }
            
            .apd-product-header {
                padding: 20px;
            }
            
            .apd-product-title {
                font-size: 2rem;
            }
            
            .apd-action-buttons {
                flex-direction: column;
            }
        }
        </style>
        <?php

        return ob_get_clean();
    }

    public function debug_admin_menu()
    {
        // Ensure admin menu is registered even if there were previous errors
        if (!current_user_can('manage_options')) {
            return;
        }

        // Always try to register menu as fallback
        add_menu_page(
            'Product Designer',
            'Product Designer',
            'manage_options',
            'apd-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-art',
            6
        );

        error_log('APD Debug: Fallback menu registration attempted');
    }

    public function admin_dashboard_notice()
    {
        // Only show to admins
        if (!current_user_can('manage_options')) {
            return;
        }

        // Always show for debugging
        echo '<div class="notice notice-info is-dismissible" data-notice="apd-dashboard">';
        echo '<p><strong>Product Designer Plugin Debug:</strong> ';
        echo '<a href="' . admin_url('admin.php?page=apd-dashboard') . '" class="button button-primary">Access Dashboard</a>';
        echo ' | <a href="' . admin_url('admin.php?page=apd-orders') . '" class="button">Orders</a>';
        echo ' | <a href="' . admin_url('admin.php?page=apd-templates') . '" class="button">Templates</a>';
        echo ' | <a href="' . admin_url('admin.php?page=apd-settings') . '" class="button">Settings</a>';
        echo '<br><small>Plugin loaded: ' . (class_exists('AdvancedProductDesigner') ? 'YES' : 'NO') . '</small></p>';
        echo '</div>';

        // Add JavaScript to handle notice dismissal
        echo '<script>
        jQuery(document).ready(function($) {
            $(document).on("click", ".notice[data-notice=\'apd-dashboard\'] .notice-dismiss", function() {
                $.post(ajaxurl, {
                    action: "apd_dismiss_dashboard_notice",
                    nonce: "' . wp_create_nonce('apd_dismiss_notice') . '"
                });
            });
        });
        </script>';
    }

    /**
     * Pricing Tiers Meta Box
     */
    public function pricing_tiers_meta_box($post)
    {
        // Check if variants are enabled
        $variants = get_post_meta($post->ID, '_apd_variants', true);
        $variants_enabled = is_array($variants) && isset($variants['enabled']) && $variants['enabled'];
        
        if ($variants_enabled) {
            ?>
            <div class="apd-pricing-tiers-wrapper">
                <div style="padding: 20px; background: #fff3cd; border-left: 4px solid #ffc107; margin: 10px 0;">
                    <h4 style="margin-top: 0; color: #856404;">⚠️ Variants Enabled</h4>
                    <p style="margin-bottom: 0; color: #856404;">
                        This product has variants enabled. Product-level volume pricing tiers are disabled.<br>
                        To set quantity discounts, use the <strong>"Add Quantity Discounts"</strong> button next to each variant in the <strong>"Product Variants"</strong> section above.
                    </p>
                </div>
            </div>
            <?php
            return;
        }
        
        $pricing_service = new APD_Pricing_Service();
        $tiers = $pricing_service->get_price_tiers($post->ID);
        
        $base_price = floatval(get_post_meta($post->ID, '_fsc_price', true));
        if (!$base_price) {
            $base_price = 29.99;
        }
        
        ?>
        <div class="apd-pricing-tiers-wrapper">
            <p class="description">Set up quantity-based discounts. When customers order in bulk, they automatically receive the discount.</p>
            
            <table class="widefat" id="apd-pricing-tiers-table">
                <thead>
                    <tr>
                        <th style="width: 150px;">Minimum Quantity</th>
                        <th style="width: 150px;">Discount (%)</th>
                        <th style="width: 200px;">Tier Name (optional)</th>
                        <th style="width: 150px;">Price per Unit</th>
                        <th style="width: 80px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="apd-pricing-tiers-body">
                    <?php if (!empty($tiers)): ?>
                        <?php foreach ($tiers as $index => $tier): ?>
                            <?php 
                            $discounted_price = $base_price - (($base_price * floatval($tier['discount_percent'])) / 100);
                            ?>
                            <tr>
                                <td><input type="number" name="apd_tier_min_qty[]" value="<?php echo esc_attr($tier['min_qty']); ?>" min="1" class="small-text" required></td>
                                <td><input type="number" name="apd_tier_discount[]" value="<?php echo esc_attr($tier['discount_percent']); ?>" min="0" max="100" step="0.01" class="small-text apd-tier-discount" required></td>
                                <td><input type="text" name="apd_tier_name[]" value="<?php echo esc_attr($tier['name']); ?>" class="regular-text" placeholder="e.g., Bulk Order"></td>
                                <td><span class="apd-calculated-price">$<?php echo number_format($discounted_price, 2); ?></span></td>
                                <td><button type="button" class="button apd-remove-tier-btn">Remove</button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="apd-no-tiers-row">
                            <td colspan="5" style="text-align: center; padding: 20px;">
                                <em>No pricing tiers configured. Click "Add Tier" to create volume discounts.</em>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <button type="button" class="button apd-add-tier-btn" style="margin-top: 10px;">Add Tier</button>
            
            <div style="margin-top: 20px; padding: 15px; background: #f0f0f1; border-left: 4px solid #2271b1;">
                <h4 style="margin-top: 0;">Preview</h4>
                <p class="description">How your volume pricing will appear to customers:</p>
                <div id="apd-pricing-preview">
                    <?php if (!empty($tiers)): ?>
                        <table class="widefat" style="max-width: 500px;">
                            <thead>
                                <tr>
                                    <th>Quantity</th>
                                    <th>Discount</th>
                                    <th>Price/Unit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tiers as $tier): ?>
                                    <?php $discounted_price = $base_price - (($base_price * floatval($tier['discount_percent'])) / 100); ?>
                                    <tr>
                                        <td><?php echo esc_html($tier['min_qty']); ?>+</td>
                                        <td><?php echo esc_html($tier['discount_percent']); ?>%</td>
                                        <td>$<?php echo number_format($discounted_price, 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p><em>No tiers to preview</em></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var basePrice = <?php echo $base_price; ?>;
            
            // Add new tier
            $('.apd-add-tier-btn').on('click', function() {
                $('.apd-no-tiers-row').remove();
                var newRow = '<tr>' +
                    '<td><input type="number" name="apd_tier_min_qty[]" value="" min="1" class="small-text" required></td>' +
                    '<td><input type="number" name="apd_tier_discount[]" value="" min="0" max="100" step="0.01" class="small-text apd-tier-discount" required></td>' +
                    '<td><input type="text" name="apd_tier_name[]" value="" class="regular-text" placeholder="e.g., Bulk Order"></td>' +
                    '<td><span class="apd-calculated-price">$0.00</span></td>' +
                    '<td><button type="button" class="button apd-remove-tier-btn">Remove</button></td>' +
                    '</tr>';
                $('#apd-pricing-tiers-body').append(newRow);
            });
            
            // Remove tier
            $(document).on('click', '.apd-remove-tier-btn', function() {
                var $tbody = $('#apd-pricing-tiers-body');
                $(this).closest('tr').remove();
                
                // If no tiers left, show placeholder
                if ($tbody.find('tr').length === 0) {
                    $tbody.html('<tr class="apd-no-tiers-row"><td colspan="5" style="text-align: center; padding: 20px;"><em>No pricing tiers configured. Click "Add Tier" to create volume discounts.</em></td></tr>');
                }
            });
            
            // Update calculated price on discount change
            $(document).on('input', '.apd-tier-discount', function() {
                var discount = parseFloat($(this).val()) || 0;
                var discountedPrice = basePrice - ((basePrice * discount) / 100);
                $(this).closest('tr').find('.apd-calculated-price').text('$' + discountedPrice.toFixed(2));
            });
        });
        </script>
        
        <style>
        .apd-pricing-tiers-wrapper table input[type="number"],
        .apd-pricing-tiers-wrapper table input[type="text"] {
            width: 100%;
        }
        .apd-calculated-price {
            font-weight: bold;
            color: #2271b1;
        }
        </style>
        <?php
    }

    /**
     * Template Details Meta Box
     */
    public function template_details_meta_box($post)
    {
        wp_nonce_field('apd_template_meta', 'apd_template_meta_nonce');

        $width = get_post_meta($post->ID, '_apd_template_width', true) ?: 800;
        $height = get_post_meta($post->ID, '_apd_template_height', true) ?: 600;
        $background_type = get_post_meta($post->ID, '_apd_template_bg_type', true) ?: 'color';
        $background_color = get_post_meta($post->ID, '_apd_template_bg_color', true) ?: '#ffffff';
        $background_image = get_post_meta($post->ID, '_apd_template_bg_image', true);
        $template_data = get_post_meta($post->ID, '_apd_template_data', true) ?: '{}';
        $allowed_categories = get_post_meta($post->ID, '_apd_allowed_material_categories', true);
        if (!is_array($allowed_categories)) {
            $allowed_categories = array();
        }
        
        // Get all material categories via plugin's material_manager
        $all_categories = array();
        if (isset($this->plugin->material_manager)) {
            $all_categories = $this->plugin->material_manager->get_material_categories();
        }

        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="apd_template_width">Canvas Width (px)</label>
                </th>
                <td>
                    <input type="number" id="apd_template_width" name="apd_template_width" value="<?php echo esc_attr($width); ?>" min="100" max="2000" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="apd_template_height">Canvas Height (px)</label>
                </th>
                <td>
                    <input type="number" id="apd_template_height" name="apd_template_height" value="<?php echo esc_attr($height); ?>" min="100" max="2000" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="apd_template_bg_type">Background Type</label>
                </th>
                <td>
                    <select id="apd_template_bg_type" name="apd_template_bg_type">
                        <option value="color" <?php selected($background_type, 'color'); ?>>Solid Color</option>
                        <option value="image" <?php selected($background_type, 'image'); ?>>Image</option>
                        <option value="gradient" <?php selected($background_type, 'gradient'); ?>>Gradient</option>
                    </select>
                </td>
            </tr>
            <tr id="bg-color-row" style="<?php echo $background_type === 'color' ? '' : 'display:none;'; ?>">
                <th scope="row">
                    <label for="apd_template_bg_color">Background Color</label>
                </th>
                <td>
                    <input type="color" id="apd_template_bg_color" name="apd_template_bg_color" value="<?php echo esc_attr($background_color); ?>">
                </td>
            </tr>
            <tr id="bg-image-row" style="<?php echo $background_type === 'image' ? '' : 'display:none;'; ?>">
                <th scope="row">
                    <label for="apd_template_bg_image">Background Image</label>
                </th>
                <td>
                    <input type="url" id="apd_template_bg_image" name="apd_template_bg_image" value="<?php echo esc_attr($background_image); ?>" class="regular-text">
                    <button type="button" class="button" id="select-bg-image">Select Image</button>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="apd_allowed_material_categories">Allowed Material Categories</label>
                </th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span>Allowed Material Categories</span></legend>
                        <label style="display: block; margin-bottom: 8px;">
                            <input type="checkbox" name="apd_allowed_material_categories[]" value="all" <?php checked(empty($allowed_categories) || in_array('all', $allowed_categories)); ?>> 
                            <strong>All Categories (No Restriction)</strong>
                        </label>
                        <?php foreach ($all_categories as $category): ?>
                            <label style="display: block; margin-bottom: 6px;">
                                <input type="checkbox" name="apd_allowed_material_categories[]" value="<?php echo esc_attr($category); ?>" <?php checked(in_array($category, $allowed_categories)); ?>> 
                                <?php echo esc_html($category); ?>
                            </label>
                        <?php endforeach; ?>
                        <label style="display: block; margin-bottom: 6px;">
                            <input type="checkbox" name="apd_allowed_material_categories[]" value="Uncategorized" <?php checked(in_array('Uncategorized', $allowed_categories)); ?>> 
                            Uncategorized
                        </label>
                    </fieldset>
                    <p class="description">Select which material categories are allowed for this template. If "All Categories" is checked, all materials will be available. Leave all unchecked to show all materials.</p>
                </td>
            </tr>
        </table>
        
        <div class="apd-template-designer-link">
            <p><strong>Template Designer:</strong> <a href="<?php echo admin_url('admin.php?page=apd-designer&template_id=' . $post->ID); ?>" class="button button-primary">Open Template Designer</a></p>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#apd_template_bg_type').on('change', function() {
                var type = $(this).val();
                $('#bg-color-row, #bg-image-row').hide();
                if (type === 'color') {
                    $('#bg-color-row').show();
                } else if (type === 'image') {
                    $('#bg-image-row').show();
                }
            });
            
            $('#select-bg-image').on('click', function(e) {
                e.preventDefault();
                
                var frame = wp.media({
                    title: 'Select Background Image',
                    button: {
                        text: 'Use this image'
                    },
                    multiple: false,
                    library: {
                        type: 'image'
                    }
                });
                
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#apd_template_bg_image').val(attachment.url);
                });
                
                frame.open();
            });
        });
        </script>
        <?php
    }

    /**
     * Save Template Meta
     */
    public function save_template_meta($post_id)
    {
        if (!isset($_POST['apd_template_meta_nonce']) || !wp_verify_nonce($_POST['apd_template_meta_nonce'], 'apd_template_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (get_post_type($post_id) !== 'apd_template') {
            return;
        }

        $fields = array(
            '_apd_template_width',
            '_apd_template_height',
            '_apd_template_bg_type',
            '_apd_template_bg_color',
            '_apd_template_bg_image'
        );

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        // Save allowed material categories
        if (isset($_POST['apd_allowed_material_categories'])) {
            $allowed_categories = array_map('sanitize_text_field', $_POST['apd_allowed_material_categories']);
            update_post_meta($post_id, '_apd_allowed_material_categories', $allowed_categories);
        } else {
            // If no categories selected, delete the meta (which means all materials allowed)
            delete_post_meta($post_id, '_apd_allowed_material_categories');
        }
    }
}
