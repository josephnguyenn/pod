<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="apd-dashboard">
    <style>
    .apd-dashboard {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    .apd-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px;
        border-radius: 12px;
        margin-bottom: 30px;
    }
    .apd-header-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .apd-header h1 {
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 10px;
        margin: 0 0 10px 0;
    }
    .apd-header p {
        color: rgba(255,255,255,0.9);
        font-size: 1.1rem;
        margin: 0;
    }
    .apd-stats {
        text-align: right;
    }
    .apd-stats .number {
        font-size: 2rem;
        font-weight: bold;
        margin: 0;
    }
    .apd-stats .label {
        color: rgba(255,255,255,0.8);
        margin: 0;
    }
    .apd-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .apd-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 24px;
        transition: box-shadow 0.3s ease;
    }
    .apd-card:hover {
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }
    .apd-card-header {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
    }
    .apd-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 16px;
    }
    .apd-icon.green { background: #dcfce7; color: #16a34a; }
    .apd-icon.blue { background: #dbeafe; color: #2563eb; }
    .apd-icon.purple { background: #e9d5ff; color: #7c3aed; }
    .apd-card h3 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1f2937;
        margin: 0 0 5px 0;
    }
    .apd-card p {
        color: #6b7280;
        font-size: 0.9rem;
        margin: 0;
    }
    .apd-btn {
        background: #667eea;
        color: white;
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        display: inline-block;
        font-weight: 500;
        transition: background-color 0.3s ease;
        border: none;
        cursor: pointer;
    }
    .apd-btn:hover {
        background: #5a6fd8;
        color: white;
        text-decoration: none;
    }
    .apd-btn.green { background: #16a34a; }
    .apd-btn.green:hover { background: #15803d; }
    .apd-btn.blue { background: #2563eb; }
    .apd-btn.blue:hover { background: #1d4ed8; }
    .apd-btn.purple { background: #7c3aed; }
    .apd-btn.purple:hover { background: #6d28d9; }
    </style>

    <!-- Header -->
    <div class="apd-header">
        <div class="apd-header-content">
            <div>
                <h1>Advanced Product Designer</h1>
                <p>Create stunning custom products with drag-and-drop design tools</p>
            </div>
            <div class="apd-stats">
                <div class="number"><?php echo count($products); ?></div>
                <div class="label">Total Products</div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="apd-grid">
        <div class="apd-card">
            <div class="apd-card-header">
                <div class="apd-icon green">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </div>
                <div>
                    <h3>Create Product</h3>
                    <p>Add a new designer product</p>
                </div>
            </div>
            <a href="<?php echo admin_url('post-new.php?post_type=apd_product'); ?>" class="apd-btn green">
                Create Now
            </a>
        </div>

        <div class="apd-card">
            <div class="apd-card-header">
                <div class="apd-icon blue">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z"></path>
                    </svg>
                </div>
                <div>
                    <h3>Open Designer</h3>
                    <p>Launch the design interface</p>
                </div>
            </div>
            <a href="<?php echo admin_url('admin.php?page=apd-designer'); ?>" class="apd-btn blue">
                Open Designer
            </a>
        </div>

        <div class="apd-card">
            <div class="apd-card-header">
                <div class="apd-icon purple">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
                <div>
                    <h3>Settings</h3>
                    <p>Configure plugin options</p>
                </div>
            </div>
            <a href="<?php echo admin_url('admin.php?page=apd-settings'); ?>" class="apd-btn purple">
                Configure
            </a>
        </div>
    </div>

    <!-- Recent Products -->
    <div class="apd-card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
            <h2 style="font-size: 1.5rem; font-weight: bold; color: #1f2937; margin: 0;">Recent Products</h2>
            <a href="<?php echo admin_url('admin.php?page=apd-products'); ?>" style="color: #2563eb; text-decoration: none; font-weight: 500;">
                View All →
            </a>
        </div>

        <?php if (empty($products)): ?>
            <div style="text-align: center; padding: 48px 0;">
                <div style="background: #f3f4f6; border-radius: 50%; width: 96px; height: 96px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                    <svg width="48" height="48" fill="none" stroke="#9ca3af" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
                <h3 style="font-size: 1.1rem; font-weight: 600; color: #1f2937; margin: 0 0 8px 0;">No products yet</h3>
                <p style="color: #6b7280; margin: 0 0 16px 0;">Create your first designer product to get started</p>
                <a href="<?php echo admin_url('post-new.php?post_type=apd_product'); ?>" class="apd-btn blue">
                    Create First Product
                </a>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <?php foreach (array_slice($products, 0, 6) as $product): ?>
                    <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; transition: box-shadow 0.3s ease;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                            <h3 style="font-weight: 600; color: #1f2937; margin: 0;"><?php echo esc_html($product->post_title); ?></h3>
                            <span style="font-size: 0.8rem; color: #6b7280;"><?php echo get_the_date('M j, Y', $product->ID); ?></span>
                        </div>
                        <p style="color: #6b7280; font-size: 0.9rem; margin: 0 0 16px 0;"><?php echo wp_trim_words($product->post_content, 15); ?></p>
                        <div style="display: flex; gap: 8px;">
                            <a href="<?php echo get_edit_post_link($product->ID); ?>" style="flex: 1; background: #f3f4f6; color: #374151; padding: 8px 12px; border-radius: 6px; font-size: 0.9rem; text-align: center; text-decoration: none; transition: background-color 0.3s ease;">
                                Edit
                            </a>
                            <a href="<?php echo admin_url('admin.php?page=apd-designer&product_id=' . $product->ID); ?>" style="flex: 1; background: #2563eb; color: white; padding: 8px 12px; border-radius: 6px; font-size: 0.9rem; text-align: center; text-decoration: none; transition: background-color 0.3s ease;">
                                Design
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Features Overview -->
    <div style="margin-top: 30px; background: linear-gradient(135deg, #f9fafb 0%, #dbeafe 100%); border-radius: 12px; padding: 24px;">
        <h2 style="font-size: 1.5rem; font-weight: bold; color: #1f2937; margin: 0 0 16px 0;">Plugin Features</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
            <div style="text-align: center;">
                <div style="background: white; border-radius: 50%; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px;">
                    <svg width="24" height="24" color="#2563eb" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h3 style="font-weight: 600; color: #1f2937; margin: 0 0 4px 0;">SVG Support</h3>
                <p style="color: #6b7280; font-size: 0.9rem; margin: 0;">Upload and use SVG graphics</p>
            </div>
            <div style="text-align: center;">
                <div style="background: white; border-radius: 50%; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px;">
                    <svg width="24" height="24" color="#16a34a" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z"></path>
                    </svg>
                </div>
                <h3 style="font-weight: 600; color: #1f2937; margin: 0 0 4px 0;">Drag & Drop</h3>
                <p style="color: #6b7280; font-size: 0.9rem; margin: 0;">Intuitive design interface</p>
            </div>
            <div style="text-align: center;">
                <div style="background: white; border-radius: 50%; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px;">
                    <svg width="24" height="24" color="#7c3aed" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                </div>
                <h3 style="font-weight: 600; color: #1f2937; margin: 0 0 4px 0;">Text Editor</h3>
                <p style="color: #6b7280; font-size: 0.9rem; margin: 0;">Advanced typography controls</p>
            </div>
            <div style="text-align: center;">
                <div style="background: white; border-radius: 50%; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px;">
                    <svg width="24" height="24" color="#ea580c" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </svg>
                </div>
                <h3 style="font-weight: 600; color: #1f2937; margin: 0 0 4px 0;">Custom Canvas</h3>
                <p style="color: #6b7280; font-size: 0.9rem; margin: 0;">Flexible canvas dimensions</p>
            </div>
        </div>
    </div>
</div>
