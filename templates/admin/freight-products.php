<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Freight Products</h1>
    <a href="<?php echo admin_url('post-new.php?post_type=freight_product'); ?>" class="page-title-action">Add New Product</a>
    <hr class="wp-header-end">

    <!-- Quick Stats -->
    <div class="fsc-admin-stats">
        <div class="fsc-stat-card">
            <h3><?php echo count($products); ?></h3>
            <p>Total Products</p>
        </div>
        <div class="fsc-stat-card">
            <h3><?php echo count($materials); ?></h3>
            <p>Available Materials</p>
        </div>
        <div class="fsc-stat-card">
            <h3><?php echo count($colors); ?></h3>
            <p>Color Options</p>
        </div>
    </div>

    <!-- Products Table -->
    <div class="fsc-admin-content">
        <form method="post">
            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <select name="action">
                        <option value="-1">Bulk Actions</option>
                        <option value="bulk-delete">Delete</option>
                    </select>
                    <input type="submit" class="button action" value="Apply">
                </div>
                <br class="clear">
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" id="cb-select-all-1">
                        </td>
                        <th class="manage-column column-title">Product Name</th>
                        <th class="manage-column column-price">Price</th>
                        <th class="manage-column column-material">Material</th>
                        <th class="manage-column column-features">Features</th>
                        <th class="manage-column column-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 20px;">
                            <p>No freight products found. <a href="<?php echo admin_url('post-new.php?post_type=freight_product'); ?>">Create your first product</a>.</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="post[]" value="<?php echo $product->ID; ?>">
                            </th>
                            <td class="column-title">
                                <strong><a href="<?php echo get_edit_post_link($product->ID); ?>"><?php echo esc_html($product->post_title); ?></a></strong>
                                <?php 
                                $logo_url = get_post_meta($product->ID, '_fsc_logo_file', true);
                                if ($logo_url): ?>
                                    <div class="product-logo-preview">
                                        <img src="<?php echo esc_url($logo_url); ?>" alt="Product Logo" style="max-width: 50px; max-height: 30px; margin-top: 5px;">
                                    </div>
                                <?php endif; ?>
                                <div class="row-actions">
                                    <span class="edit"><a href="<?php echo get_edit_post_link($product->ID); ?>">Edit</a> | </span>
                                    <span class="view"><a href="<?php echo get_permalink($product->ID); ?>">View</a> | </span>
                                    <span class="customize"><a href="<?php echo home_url('/customizer/' . $product->post_name); ?>">Customize</a> | </span>
                                    <span class="design"><a href="<?php echo admin_url('admin.php?page=apd-designer&product_id=' . $product->ID); ?>">Design</a></span>
                                </div>
                            </td>
                            <td class="column-price">
                                <?php 
                                $price = get_post_meta($product->ID, '_fsc_price', true);
                                echo $price ? esc_html($price) : '<em>Not set</em>';
                                ?>
                            </td>
                            <td class="column-material">
                                <?php 
                                $material = get_post_meta($product->ID, '_fsc_material', true);
                                echo $material ? esc_html($material) : '<em>Not set</em>';
                                ?>
                            </td>
                            <td class="column-features">
                                <?php 
                                $features = get_post_meta($product->ID, '_fsc_features', true);
                                if (is_array($features) && !empty($features)) {
                                    echo '<ul style="margin: 0; padding-left: 15px;">';
                                    foreach (array_slice($features, 0, 3) as $feature) {
                                        echo '<li>' . esc_html($feature) . '</li>';
                                    }
                                    if (count($features) > 3) {
                                        echo '<li><em>... and ' . (count($features) - 3) . ' more</em></li>';
                                    }
                                    echo '</ul>';
                                } else {
                                    echo '<em>No features set</em>';
                                }
                                ?>
                            </td>
                            <td class="column-actions">
                                <a href="<?php echo get_edit_post_link($product->ID); ?>" class="button button-small">Edit</a>
                                <a href="<?php echo home_url('/customizer/' . $product->post_name); ?>" class="button button-small">Customize</a>
                                <a href="<?php echo admin_url('admin.php?page=apd-designer&product_id=' . $product->ID); ?>" class="button button-small button-primary">Design</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>
    </div>

         <!-- Quick Actions -->
     <div class="fsc-quick-actions">
         <h3>Quick Actions</h3>
         <div class="fsc-action-buttons">
             <a href="<?php echo admin_url('post-new.php?post_type=freight_product'); ?>" class="button button-primary">Create New Product</a>
             <a href="<?php echo home_url('/customizer/freight-solution-llc'); ?>" class="button">View Customizer</a>
             <a href="<?php echo admin_url('upload.php'); ?>" class="button">Manage Materials</a>
         </div>
     </div>

     <!-- SVG Upload Instructions -->
     <div class="fsc-svg-instructions">
         <h3>📁 SVG Logo Upload Instructions</h3>
         <div class="fsc-instruction-content">
             <p><strong>To upload SVG logo files:</strong></p>
             <ol>
                 <li>Go to <strong>Media Library</strong> → <strong>Add New</strong></li>
                 <li>Click <strong>"Select Files"</strong> or drag & drop your SVG file</li>
                 <li>Upload the file (SVG upload is now enabled)</li>
                 <li>Copy the file URL from Media Library</li>
                 <li>Place your SVG file in: <code><?php echo wp_upload_dir()['basedir']; ?>/object/</code></li>
                 <li>Rename it to: <code>Logo-PNG.svg</code></li>
             </ol>
             
             <div class="fsc-alert">
                 <p><strong>⚠️ Important:</strong> Make sure your SVG file is clean and doesn't contain any malicious scripts. The plugin includes security measures, but always verify your SVG files.</p>
             </div>
             
             <div class="fsc-current-logo">
                 <h4>Current Logo Status:</h4>
                 <?php 
                 $upload_dir = wp_upload_dir();
                 $logo_path = $upload_dir['basedir'] . '/object/Logo-PNG.svg';
                 if (file_exists($logo_path)) {
                     echo '<p style="color: green;">✅ Logo file found: <code>' . $upload_dir['baseurl'] . '/object/Logo-PNG.svg</code></p>';
                     
                     // Show logo preview
                     echo '<div style="margin-top: 15px; padding: 15px; background: white; border: 1px solid #ddd; border-radius: 6px;">';
                     echo '<h5 style="margin: 0 0 10px 0;">Logo Preview:</h5>';
                     echo '<div style="max-width: 300px; margin: 0 auto;">';
                     $logo_content = file_get_contents($logo_path);
                     if ($logo_content) {
                         // Clean and display SVG
                         $logo_content = str_replace('<svg', '<svg style="width: 100%; height: auto; max-height: 100px;"', $logo_content);
                         echo $logo_content;
                     }
                     echo '</div>';
                     echo '</div>';
                 } else {
                     echo '<p style="color: red;">❌ Logo file not found. Please upload your SVG file to <code>' . $upload_dir['basedir'] . '/object/Logo-PNG.svg</code></p>';
                 }
                 ?>
             </div>
             
             <!-- Direct Upload Section -->
             <div class="fsc-direct-upload" style="margin-top: 20px; padding: 20px; background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px;">
                 <h4 style="margin: 0 0 15px 0;">🔄 Quick Logo Upload</h4>
                 <form method="post" enctype="multipart/form-data" action="<?php echo admin_url('admin-post.php'); ?>">
                     <input type="hidden" name="action" value="fsc_upload_logo">
                     <?php wp_nonce_field('fsc_upload_logo', 'fsc_logo_nonce'); ?>
                     
                     <div style="margin-bottom: 15px;">
                         <label for="logo_file" style="display: block; margin-bottom: 5px; font-weight: 600;">Select SVG Logo File:</label>
                         <input type="file" id="logo_file" name="logo_file" accept=".svg" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                         <p style="margin: 5px 0 0 0; font-size: 0.9rem; color: #666;">Only SVG files are allowed. Maximum size: 2MB</p>
                     </div>
                     
                     <button type="submit" class="button button-primary" style="margin-right: 10px;">Upload Logo</button>
                     <a href="<?php echo admin_url('upload.php'); ?>" class="button">Go to Media Library</a>
                 </form>
             </div>
         </div>
     </div>

    <!-- Materials Overview -->
    <div class="fsc-materials-overview">
        <h3>Available Materials</h3>
        <div class="fsc-materials-grid">
            <?php foreach ($materials as $name => $url): ?>
            <div class="fsc-material-item">
                <div class="fsc-material-preview" style="background-image: url('<?php echo esc_url($url); ?>');"></div>
                <span class="fsc-material-name"><?php echo esc_html($name); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.fsc-admin-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.fsc-stat-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.fsc-stat-card h3 {
    font-size: 2rem;
    margin: 0 0 10px 0;
    color: #0073aa;
}

.fsc-stat-card p {
    margin: 0;
    color: #666;
    font-weight: 500;
}

.fsc-admin-content {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin: 20px 0;
    overflow: hidden;
}

.fsc-quick-actions {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.fsc-action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.fsc-materials-overview {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.fsc-materials-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.fsc-material-item {
    text-align: center;
}

.fsc-material-preview {
    width: 100px;
    height: 100px;
    border: 2px solid #ddd;
    border-radius: 8px;
    margin: 0 auto 10px;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
}

.fsc-material-name {
    font-size: 0.9rem;
    color: #666;
    word-break: break-word;
}

.column-features ul {
    margin: 0;
    padding-left: 15px;
}

 .column-features li {
     margin-bottom: 2px;
 }

 .fsc-svg-instructions {
     background: white;
     border: 1px solid #ddd;
     border-radius: 8px;
     padding: 20px;
     margin: 20px 0;
 }

 .fsc-instruction-content ol {
     margin: 15px 0;
     padding-left: 20px;
 }

 .fsc-instruction-content li {
     margin-bottom: 8px;
     line-height: 1.5;
 }

 .fsc-alert {
     background: #fff3cd;
     border: 1px solid #ffeaa7;
     border-radius: 6px;
     padding: 15px;
     margin: 15px 0;
 }

 .fsc-alert p {
     margin: 0;
     color: #856404;
 }

 .fsc-current-logo {
     background: #f8f9fa;
     border: 1px solid #e9ecef;
     border-radius: 6px;
     padding: 15px;
     margin-top: 15px;
 }

 .fsc-current-logo h4 {
     margin: 0 0 10px 0;
     color: #333;
 }

 .fsc-current-logo p {
     margin: 5px 0;
     font-family: monospace;
     font-size: 0.9rem;
 }

 .fsc-current-logo code {
     background: #e9ecef;
     padding: 2px 6px;
     border-radius: 3px;
     font-size: 0.85rem;
 }
 </style>
