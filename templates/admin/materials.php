<?php
/**
 * Materials Management Page Template
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>Materials Management</h1>
    
    <div class="apd-materials-container">
        <!-- Upload New Material Section -->
        <div class="apd-materials-section">
            <h2>Upload New Material</h2>
            <form method="post" enctype="multipart/form-data" class="apd-upload-form">
                <?php wp_nonce_field('upload_material', 'material_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="material_name">Material Name</label>
                        </th>
                        <td>
                            <input type="text" id="material_name" name="material_name" class="regular-text" required>
                            <p class="description">Enter a descriptive name for this material (e.g., "Gold Texture", "Silver Finish")</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="material_file">Material File</label>
                        </th>
                        <td>
                            <div style="margin-bottom: 15px;">
                                <button type="button" id="apd_select_material_media" class="button">
                                    Select from Media Library
                                </button>
                                <span id="apd_selected_media_name" style="margin-left: 10px; color: #666;"></span>
                            </div>
                            <div id="apd_selected_media_preview" style="margin-bottom: 10px;"></div>
                            <input type="hidden" id="apd_material_media_id" name="material_media_id" value="">
                            <p style="margin: 10px 0;"><strong>OR</strong></p>
                            <input type="file" id="material_file" name="material_file" accept=".png,.jpg,.jpeg">
                            <p class="description">Select an image from the media library or upload a new PNG or JPG file. Recommended size: 512x512px or larger.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="material_price">Material Price ($)</label>
                        </th>
                        <td>
                            <input type="number" id="material_price" name="material_price" class="regular-text" step="0.01" min="0" value="0" required>
                            <p class="description">Set the price for this material. This will be added to the base product price when selected.</p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="upload_material" class="button-primary" value="Upload Material">
                </p>
            </form>
        </div>
        
        
        <!-- Current Materials List -->
        <div class="apd-materials-section">
            <h2>Current Materials</h2>
            <?php if (!empty($materials)): ?>
                <div class="apd-materials-grid">
                    <?php foreach ($materials as $index => $material): ?>
                        <div class="apd-material-item">
                            <div class="material-preview">
                                <img src="<?php echo esc_url($material['url']); ?>" alt="<?php echo esc_attr($material['name']); ?>" loading="lazy">
                            </div>
                            <div class="material-info">
                                <h4><?php echo esc_html($material['name']); ?></h4>
                                <p class="material-type">
                                    <span class="type-badge type-<?php echo esc_attr($material['type']); ?>">
                                        <?php echo esc_html(ucfirst($material['type'])); ?>
                                    </span>
                                </p>
                                <p class="material-price" style="margin: 8px 0; font-size: 16px; font-weight: bold; color: #2271b1;">
                                    Price: $<?php echo esc_html(number_format(floatval($material['price'] ?? 0), 2)); ?>
                                </p>
                                <p class="material-date">Added: <?php echo esc_html($material['date']); ?></p>
                            </div>
                            <div class="material-actions">
                                <form method="post" class="material-price-form" style="margin-bottom: 8px;">
                                    <?php wp_nonce_field('update_material_price', 'material_price_nonce'); ?>
                                    <input type="hidden" name="material_index" value="<?php echo $index; ?>">
                                    <label style="display: block; margin-bottom: 4px; font-size: 12px;">Update Price:</label>
                                    <div style="display: flex; gap: 4px;">
                                        <input type="number" name="material_price" class="material-price-input" step="0.01" min="0" value="<?php echo esc_attr($material['price'] ?? 0); ?>" style="width: 80px; padding: 4px;" required>
                                        <input type="submit" name="update_material_price" class="button button-small" value="Update">
                                    </div>
                                </form>
                                <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this material?');">
                                    <?php wp_nonce_field('delete_material', 'material_nonce'); ?>
                                    <input type="hidden" name="material_index" value="<?php echo $index; ?>">
                                    <input type="submit" name="delete_material" class="button button-small button-link-delete" value="Delete">
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No materials found. Upload some materials to get started.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.apd-materials-container {
    max-width: 1200px;
}

.apd-materials-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.apd-materials-section h2 {
    margin-top: 0;
    color: #23282d;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.apd-upload-form .form-table th {
    width: 200px;
}

.apd-media-selector {
    margin-bottom: 15px;
}

.selected-media-preview {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 15px;
}

.selected-media-item {
    position: relative;
    width: 100px;
    height: 100px;
    border: 2px solid #0073aa;
    border-radius: 4px;
    overflow: hidden;
}

.selected-media-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.selected-media-item .remove-btn {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #dc3232;
    color: white;
    border: none;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    cursor: pointer;
    font-size: 12px;
}

.apd-materials-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.apd-material-item {
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    background: #fff;
    transition: box-shadow 0.2s ease;
}

.apd-material-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.material-preview {
    height: 150px;
    overflow: hidden;
    background: #f9f9f9;
    display: flex;
    align-items: center;
    justify-content: center;
}

.material-preview img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.material-info {
    padding: 15px;
}

.material-info h4 {
    margin: 0 0 10px 0;
    color: #23282d;
}

.material-type {
    margin: 5px 0;
}

.type-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
}

.type-uploaded {
    background: #e3f2fd;
    color: #1976d2;
}

.type-media {
    background: #f3e5f5;
    color: #7b1fa2;
}

.type-legacy {
    background: #fff3e0;
    color: #f57c00;
}

.material-date {
    font-size: 12px;
    color: #666;
    margin: 5px 0 0 0;
}

.material-actions {
    padding: 0 15px 15px 15px;
    border-top: 1px solid #eee;
    margin-top: 10px;
    padding-top: 10px;
}

@media (max-width: 768px) {
    .apd-materials-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    let mediaFrame;
    let selectedMedia = [];
    
    // Material media selector
    $('#apd_select_material_media').on('click', function(e) {
        e.preventDefault();
        
        if (mediaFrame) {
            mediaFrame.open();
            return;
        }
        
        mediaFrame = wp.media({
            title: 'Select Material Image',
            button: {
                text: 'Use this image'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        mediaFrame.on('select', function() {
            const attachment = mediaFrame.state().get('selection').first().toJSON();
            
            // Set hidden field value
            $('#apd_material_media_id').val(attachment.id);
            
            // Show preview
            const previewHtml = `
                <div style="position: relative; display: inline-block;">
                    <img src="${attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url}" 
                         style="max-width: 150px; max-height: 150px; border: 2px solid #0073aa; border-radius: 4px;">
                    <button type="button" id="apd_remove_material_media" 
                            style="position: absolute; top: -8px; right: -8px; background: #dc3232; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; font-size: 16px; line-height: 1;">×</button>
                </div>
            `;
            $('#apd_selected_media_preview').html(previewHtml);
            $('#apd_selected_media_name').text(attachment.filename);
            
            // Clear file input
            $('#material_file').val('');
        });
        
        mediaFrame.open();
    });
    
    // Remove selected media
    $(document).on('click', '#apd_remove_material_media', function() {
        $('#apd_material_media_id').val('');
        $('#apd_selected_media_preview').empty();
        $('#apd_selected_media_name').text('');
    });
    
    // Old media library button (for backward compatibility)
    $('#select-media-btn').on('click', function(e) {
        e.preventDefault();
        
        if (mediaFrame) {
            mediaFrame.open();
            return;
        }
        
        mediaFrame = wp.media({
            title: 'Select Materials',
            button: {
                text: 'Add Selected Materials'
            },
            multiple: true,
            library: {
                type: 'image'
            }
        });
        
        mediaFrame.on('select', function() {
            const selection = mediaFrame.state().get('selection');
            selectedMedia = [];
            
            selection.map(function(attachment) {
                selectedMedia.push(attachment.toJSON());
            });
            
            updateMediaPreview();
            updateSubmitButton();
        });
        
        mediaFrame.open();
    });
    
    function updateMediaPreview() {
        const preview = $('#selected-media-preview');
        preview.empty();
        
        selectedMedia.forEach(function(media, index) {
            const item = $(`
                <div class="selected-media-item">
                    <img src="${media.sizes.thumbnail ? media.sizes.thumbnail.url : media.url}" alt="${media.title}">
                    <button type="button" class="remove-btn" data-index="${index}">×</button>
                </div>
            `);
            preview.append(item);
        });
    }
    
    function updateSubmitButton() {
        const submitBtn = $('input[name="select_material"]');
        const hiddenInput = $('#selected_materials');
        
        if (selectedMedia.length > 0) {
            submitBtn.prop('disabled', false);
            hiddenInput.val(selectedMedia.map(m => m.id).join(','));
        } else {
            submitBtn.prop('disabled', true);
            hiddenInput.val('');
        }
    }
    
    // Remove selected media
    $(document).on('click', '.remove-btn', function() {
        const index = parseInt($(this).data('index'));
        selectedMedia.splice(index, 1);
        updateMediaPreview();
        updateSubmitButton();
    });
});
</script>
