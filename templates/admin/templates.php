<?php
/**
 * Templates Management Page Template
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Templates</h1>
    <a href="<?php echo admin_url('post-new.php?post_type=apd_template'); ?>" class="page-title-action">Add New Template</a>
    <hr class="wp-header-end">
    
    <?php if (isset($_GET['duplicated']) && $_GET['duplicated'] == '1'): ?>
        <div class="notice notice-success is-dismissible">
            <p>Template duplicated successfully!</p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['deleted']) && $_GET['deleted'] == '1'): ?>
        <div class="notice notice-success is-dismissible">
            <p>Template deleted successfully!</p>
        </div>
    <?php endif; ?>
    
    <?php if (empty($templates)): ?>
        <div class="apd-empty-state">
            <div class="apd-empty-icon">
                <span class="dashicons dashicons-layout" style="font-size: 64px; color: #ddd;"></span>
            </div>
            <h2>No templates found</h2>
            <p>Create your first template to get started with the product designer.</p>
            <a href="<?php echo admin_url('post-new.php?post_type=apd_template'); ?>" class="button button-primary button-large">Create Your First Template</a>
        </div>
    <?php else: ?>
        <div class="apd-templates-grid">
            <?php foreach ($templates as $template): ?>
                <?php
                $width = get_post_meta($template->ID, '_apd_template_width', true) ?: 800;
                $height = get_post_meta($template->ID, '_apd_template_height', true) ?: 600;
                $bg_type = get_post_meta($template->ID, '_apd_template_bg_type', true) ?: 'color';
                $bg_color = get_post_meta($template->ID, '_apd_template_bg_color', true) ?: '#ffffff';
                $bg_image = get_post_meta($template->ID, '_apd_template_bg_image', true);
                $template_data = get_post_meta($template->ID, '_apd_template_data', true);
                $has_design = !empty($template_data) && $template_data !== '{}';
                ?>
                <div class="apd-template-card">
                    <div class="template-preview" style="width: 200px; height: <?php echo (200 * $height / $width); ?>px; background: <?php echo $bg_type === 'color' ? $bg_color : '#f0f0f0'; ?>; position: relative; border: 1px solid #ddd;">
                        <?php if ($bg_type === 'image' && $bg_image): ?>
                            <img src="<?php echo esc_url($bg_image); ?>" style="width: 100%; height: 100%; object-fit: cover;" alt="Background">
                        <?php endif; ?>
                        
                        <?php if ($has_design): ?>
                            <div class="template-has-design">
                                <span class="dashicons dashicons-yes-alt" style="color: #46b450; font-size: 24px;"></span>
                            </div>
                        <?php else: ?>
                            <div class="template-no-design">
                                <span class="dashicons dashicons-edit" style="color: #999; font-size: 24px;"></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="template-info">
                        <h3 class="template-title">
                            <a href="<?php echo get_edit_post_link($template->ID); ?>"><?php echo esc_html($template->post_title); ?></a>
                        </h3>
                        
                        <div class="template-meta">
                            <span class="template-size"><?php echo $width; ?> × <?php echo $height; ?>px</span>
                            <span class="template-status status-<?php echo $template->post_status; ?>"><?php echo ucfirst($template->post_status); ?></span>
                            <?php if ($has_design): ?>
                                <span class="template-designed">✓ Designed</span>
                            <?php else: ?>
                                <span class="template-empty">Empty</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="template-actions">
                            <a href="<?php echo admin_url('admin.php?page=apd-designer&template_id=' . $template->ID); ?>" class="button button-primary button-small">
                                <?php echo $has_design ? 'Edit Design' : 'Start Designing'; ?>
                            </a>
                            
                            <a href="<?php echo get_edit_post_link($template->ID); ?>" class="button button-small">Settings</a>
                            
                            <div class="template-dropdown">
                                <button class="button button-small dropdown-toggle" type="button">
                                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                                </button>
                                <div class="dropdown-content">
                                    <form method="post" style="display: inline;">
                                        <?php wp_nonce_field('duplicate_template_' . $template->ID); ?>
                                        <input type="hidden" name="action" value="duplicate">
                                        <input type="hidden" name="template_id" value="<?php echo $template->ID; ?>">
                                        <button type="submit" class="button-link">Duplicate</button>
                                    </form>
                                    
                                    <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this template?');">
                                        <?php wp_nonce_field('delete_template_' . $template->ID); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="template_id" value="<?php echo $template->ID; ?>">
                                        <button type="submit" class="button-link" style="color: #a00;">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.apd-empty-state {
    text-align: center;
    padding: 60px 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin-top: 20px;
}

.apd-empty-icon {
    margin-bottom: 20px;
}

.apd-empty-state h2 {
    color: #23282d;
    margin-bottom: 10px;
}

.apd-empty-state p {
    color: #666;
    margin-bottom: 30px;
    font-size: 16px;
}

.apd-templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.apd-template-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    overflow: hidden;
    transition: box-shadow 0.2s ease;
}

.apd-template-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.template-preview {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f9f9f9;
}

.template-has-design,
.template-no-design {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(255,255,255,0.9);
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.template-info {
    padding: 20px;
}

.template-title {
    margin: 0 0 10px 0;
    font-size: 16px;
}

.template-title a {
    text-decoration: none;
    color: #23282d;
}

.template-title a:hover {
    color: #0073aa;
}

.template-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 15px;
    font-size: 12px;
}

.template-size {
    color: #666;
}

.template-status {
    padding: 2px 8px;
    border-radius: 12px;
    font-weight: 500;
    text-transform: uppercase;
}

.status-publish {
    background: #d4edda;
    color: #155724;
}

.status-draft {
    background: #fff3cd;
    color: #856404;
}

.template-designed {
    color: #46b450;
    font-weight: 500;
}

.template-empty {
    color: #999;
}

.template-actions {
    display: flex;
    gap: 8px;
    align-items: center;
}

.template-dropdown {
    position: relative;
    display: inline-block;
}

.dropdown-toggle {
    padding: 4px 8px !important;
    min-height: auto !important;
}

.dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    background-color: #fff;
    min-width: 120px;
    box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
    z-index: 1;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.dropdown-content button {
    width: 100%;
    padding: 8px 12px;
    text-align: left;
    border: none;
    background: none;
    cursor: pointer;
    color: #23282d;
}

.dropdown-content button:hover {
    background: #f0f0f1;
}

.template-dropdown:hover .dropdown-content {
    display: block;
}

@media (max-width: 768px) {
    .apd-templates-grid {
        grid-template-columns: 1fr;
    }
    
    .template-actions {
        flex-wrap: wrap;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle dropdown toggle
    $('.dropdown-toggle').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Close other dropdowns
        $('.dropdown-content').not($(this).siblings('.dropdown-content')).hide();
        
        // Toggle current dropdown
        $(this).siblings('.dropdown-content').toggle();
    });
    
    // Close dropdown when clicking outside
    $(document).on('click', function() {
        $('.dropdown-content').hide();
    });
});
</script>
