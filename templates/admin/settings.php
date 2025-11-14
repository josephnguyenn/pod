<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="apd-settings">
    <style>
    .apd-settings {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    .apd-settings-header {
        margin-bottom: 30px;
    }
    .apd-settings-header h1 {
        font-size: 2rem;
        font-weight: bold;
        color: #1f2937;
        margin: 0 0 8px 0;
    }
    .apd-settings-header p {
        color: #6b7280;
        margin: 0;
    }
    .apd-settings-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
    }
    .apd-settings-main {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }
    .apd-settings-sidebar {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }
    .apd-settings-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 24px;
    }
    .apd-settings-card h2 {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1f2937;
        margin: 0 0 16px 0;
    }
    .apd-settings-card p {
        color: #6b7280;
        margin: 0 0 16px 0;
    }
    .apd-form-group {
        margin-bottom: 20px;
    }
    .apd-form-label {
        display: block;
        font-size: 0.9rem;
        font-weight: 500;
        color: #374151;
        margin-bottom: 8px;
    }
    .apd-form-input {
        width: 100%;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        padding: 12px;
        font-size: 0.9rem;
        transition: border-color 0.3s ease;
    }
    .apd-form-input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    .apd-form-help {
        font-size: 0.8rem;
        color: #6b7280;
        margin-top: 4px;
    }
    .apd-btn {
        background: #667eea;
        color: white;
        padding: 12px 24px;
        border-radius: 6px;
        text-decoration: none;
        display: inline-block;
        font-weight: 500;
        transition: background-color 0.3s ease;
        border: none;
        cursor: pointer;
        font-size: 0.9rem;
    }
    .apd-btn:hover {
        background: #5a6fd8;
        color: white;
        text-decoration: none;
    }
    .apd-btn.w-full { width: 100%; }
    .apd-grid-3 {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 16px;
        align-items: end;
    }
    .apd-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    .apd-divide {
        border-top: 1px solid #e5e7eb;
        margin: 16px 0;
    }
    .apd-font-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid #f3f4f6;
    }
    .apd-font-item:last-child {
        border-bottom: none;
    }
    .apd-font-info h4 {
        font-size: 0.9rem;
        font-weight: 600;
        color: #1f2937;
        margin: 0 0 4px 0;
    }
    .apd-font-info p {
        font-size: 0.8rem;
        color: #6b7280;
        margin: 0;
    }
    .apd-font-actions {
        display: flex;
        gap: 8px;
    }
    .apd-btn-small {
        padding: 6px 12px;
        font-size: 0.8rem;
    }
    .apd-btn-danger {
        background: #dc2626;
    }
    .apd-btn-danger:hover {
        background: #b91c1c;
    }
    @media (max-width: 1024px) {
        .apd-settings-grid {
            grid-template-columns: 1fr;
        }
        .apd-grid-3 {
            grid-template-columns: 1fr;
        }
        .apd-grid-2 {
            grid-template-columns: 1fr;
        }
    }
    </style>

    <!-- Header -->
    <div class="apd-settings-header">
        <h1>Settings</h1>
        <p>Configure your Advanced Product Designer plugin</p>
    </div>

    <div class="apd-settings-grid">
        <!-- Main Settings -->
        <div class="apd-settings-main">
            <!-- Fonts (Upload & Manage) -->
            <div class="apd-settings-card">
                <h2>Fonts</h2>
                <p>Upload custom fonts (TTF/OTF/WOFF/WOFF2) to use in templates.</p>

                <div class="apd-grid-3">
                    <div>
                        <label class="apd-form-label">Font file</label>
                        <input id="apd-font-file" type="file" accept=".ttf,.otf,.woff,.woff2" class="apd-form-input" />
                        <p class="apd-form-help">Max size 5MB. Only TTF, OTF, WOFF, WOFF2.</p>
                    </div>
                    <div>
                        <button id="apd-upload-font" class="apd-btn w-full">Upload</button>
                    </div>
                </div>

                <div style="margin-top: 24px;">
                    <h3 style="font-size: 1.1rem; font-weight: 600; color: #1f2937; margin: 0 0 12px 0;">Uploaded Fonts</h3>
                    <div id="apd-fonts-list">
                        <?php
                        $uploaded_fonts = get_option('apd_uploaded_fonts', array());
                        if (!empty($uploaded_fonts)) {
                            echo '<style id="apd-uploaded-fonts">';
                            foreach ($uploaded_fonts as $font) {
                                if (!empty($font['family']) && !empty($font['url'])) {
                                    $family_css = esc_attr($font['family']);
                                    $url_css = esc_url($font['url']);
                                    echo "@font-face{font-family:'{$family_css}';src:url('{$url_css}') format('truetype');font-display:swap;}\n";
                                }
                            }
                            echo '</style>';
                        }
                        if (empty($uploaded_fonts)) {
                            echo '<p style="font-size: 0.9rem; color: #6b7280;">No fonts uploaded yet.</p>';
                        } else {
                            foreach ($uploaded_fonts as $idx => $font) {
                                $family = isset($font['family']) ? $font['family'] : '';
                                $name = isset($font['name']) ? $font['name'] : $family;
                                $url = isset($font['url']) ? $font['url'] : '';
                                echo '<div class="apd-font-item">';
                                echo '<div class="apd-font-info">';
                                echo '<h4>'.esc_html($name).'</h4>';
                                echo '<p style="font-family:\''.esc_attr($family).'\';">Aa Bb Cc</p>';
                                echo '</div>';
                                echo '<div class="apd-font-actions">';
                                echo '<button class="apd-btn apd-btn-small apd-btn-danger apd-delete-font" data-index="'.$idx.'">Delete</button>';
                                echo '</div>';
                                echo '</div>';
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
            <!-- Page URL Settings -->
            <div class="apd-settings-card">
                <h2>Page URL Settings</h2>
                <p>Configure the URLs for different pages in your store</p>
                
                <div>
                    <div class="apd-form-group">
                        <label class="apd-form-label">Cart Page URL</label>
                        <input type="text" id="apd_cart_url" name="apd_cart_url" value="<?php echo esc_attr(get_option('apd_cart_url', '/cart/')); ?>" class="apd-form-input" placeholder="/cart/">
                        <p class="apd-form-help">URL where customers view their cart (e.g., /cart/, /shopping-cart/)</p>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label">Checkout Page URL</label>
                        <input type="text" id="apd_checkout_url" name="apd_checkout_url" value="<?php echo esc_attr(get_option('apd_checkout_url', '/checkout/')); ?>" class="apd-form-input" placeholder="/checkout/">
                        <p class="apd-form-help">URL where customers complete their purchase (e.g., /checkout/, /order/)</p>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label">Products Page URL</label>
                        <input type="text" id="apd_products_url" name="apd_products_url" value="<?php echo esc_attr(get_option('apd_products_url', '/products/')); ?>" class="apd-form-input" placeholder="/products/">
                        <p class="apd-form-help">URL where customers browse all products (e.g., /products/, /shop/)</p>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label">Orders Page URL</label>
                        <input type="text" id="apd_orders_url" name="apd_orders_url" value="<?php echo esc_attr(get_option('apd_orders_url', '/my-orders/')); ?>" class="apd-form-input" placeholder="/my-orders/">
                        <p class="apd-form-help">URL where customers view their order history (e.g., /my-orders/, /account/orders/)</p>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label">Customizer Page URL</label>
                        <input type="text" id="apd_customizer_url" name="apd_customizer_url" value="<?php echo esc_attr(get_option('apd_customizer_url', '/customizer/')); ?>" class="apd-form-input" placeholder="/customizer/">
                        <p class="apd-form-help">Base URL for product customizer (e.g., /customizer/, /design/)</p>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label">Thank You Page URL</label>
                        <input type="text" id="apd_thank_you_url" name="apd_thank_you_url" value="<?php echo esc_attr(get_option('apd_thank_you_url', '/thank-you/')); ?>" class="apd-form-input" placeholder="/thank-you/">
                        <p class="apd-form-help">URL shown after successful order completion (e.g., /thank-you/, /order-confirmation/)</p>
                    </div>
                </div>
            </div>

            <!-- General Settings -->
            <div class="apd-settings-card">
                <h2>General Settings</h2>
                
                <div>
                    <div class="apd-form-group">
                        <label class="apd-form-label">Default Canvas Width (px)</label>
                        <input type="number" value="800" min="100" max="2000" class="apd-form-input">
                        <p class="apd-form-help">Default width for new canvas areas</p>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label">Default Canvas Height (px)</label>
                        <input type="number" value="600" min="100" max="2000" class="apd-form-input">
                        <p class="apd-form-help">Default height for new canvas areas</p>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label">Default Background Color</label>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <input type="color" value="#ffffff" style="width: 48px; height: 48px; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer;">
                            <input type="text" value="#ffffff" class="apd-form-input" style="flex: 1;">
                        </div>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label">Maximum File Upload Size (MB)</label>
                        <input type="number" value="2" min="1" max="10" class="apd-form-input">
                        <p class="apd-form-help">Maximum size for SVG file uploads</p>
                    </div>
                </div>
            </div>

            <!-- Payment Settings -->
            <div class="apd-settings-card">
                <h2>Payment Settings</h2>
                
                <?php 
                $paypal_client_id = get_option('apd_paypal_client_id', '');
                $paypal_test_mode = get_option('apd_paypal_test_mode', '1');
                $paypal_status = ($paypal_test_mode === '1') ? 'test_mode' : ((!empty($paypal_client_id) && $paypal_client_id !== 'YOUR_PAYPAL_CLIENT_ID') ? 'configured' : 'not_configured');
                ?>
                
                <div class="apd-paypal-status" style="margin-bottom: 20px; padding: 15px; border-radius: 6px; <?php 
                    if ($paypal_status === 'configured') {
                        echo 'background: #d4edda; border: 1px solid #c3e6cb; color: #155724;';
                    } elseif ($paypal_status === 'test_mode') {
                        echo 'background: #fff3cd; border: 1px solid #ffeaa7; color: #856404;';
                    } else {
                        echo 'background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24;';
                    }
                ?>">
                    <strong>PayPal Status:</strong> 
                    <?php if ($paypal_status === 'configured'): ?>
                        ✅ Configured and Ready (Production)
                    <?php elseif ($paypal_status === 'test_mode'): ?>
                        🧪 Test Mode - Mock Payments Enabled
                    <?php else: ?>
                        ❌ Not Configured - PayPal payments will not work
                    <?php endif; ?>
                </div>
                
                <div>
                    <div class="apd-form-group">
                        <label class="apd-form-label">PayPal Client ID</label>
                        <input type="text" id="apd_paypal_client_id" name="apd_paypal_client_id" value="<?php echo esc_attr(get_option('apd_paypal_client_id', '')); ?>" class="apd-form-input" placeholder="Enter your PayPal Client ID">
                        <p class="apd-form-help">Get your PayPal Client ID from <a href="https://developer.paypal.com/developer/applications/" target="_blank">PayPal Developer Console</a></p>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label">PayPal Environment</label>
                        <select id="apd_paypal_environment" name="apd_paypal_environment" class="apd-form-input">
                            <option value="sandbox" <?php selected(get_option('apd_paypal_environment', 'sandbox'), 'sandbox'); ?>>Sandbox (Testing)</option>
                            <option value="production" <?php selected(get_option('apd_paypal_environment', 'sandbox'), 'production'); ?>>Production (Live)</option>
                        </select>
                        <p class="apd-form-help">Use Sandbox for testing, Production for live payments</p>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label">Currency</label>
                        <select id="apd_currency" name="apd_currency" class="apd-form-input">
                            <option value="USD" <?php selected(get_option('apd_currency', 'USD'), 'USD'); ?>>USD - US Dollar</option>
                            <option value="EUR" <?php selected(get_option('apd_currency', 'USD'), 'EUR'); ?>>EUR - Euro</option>
                            <option value="GBP" <?php selected(get_option('apd_currency', 'USD'), 'GBP'); ?>>GBP - British Pound</option>
                        </select>
                        <p class="apd-form-help">Currency for PayPal payments</p>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label">
                            <input type="checkbox" id="apd_paypal_test_mode" name="apd_paypal_test_mode" value="1" <?php checked(get_option('apd_paypal_test_mode', '1'), '1'); ?> style="margin-right: 8px;">
                            Enable Test Mode (Mock Payment)
                        </label>
                        <p class="apd-form-help">When enabled, simulates successful payments without real PayPal. Perfect for development and demo. Disable for production.</p>
                    </div>
                </div>
            </div>

            <!-- Email Settings -->
            <div class="apd-settings-card">
                <h2>Email Settings</h2>
                <p>Configure automatic email notifications for order confirmations and thank you messages.</p>
                
                <div>
                    <div class="apd-form-group">
                        <label class="apd-form-label">
                            <input type="checkbox" id="apd_email_enabled" name="apd_email_enabled" value="1" <?php checked(get_option('apd_email_enabled', '1'), '1'); ?> style="margin-right: 8px;">
                            Enable Email Notifications
                        </label>
                        <p class="apd-form-help">Send automatic emails when orders are completed</p>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label" for="apd_email_from_name">From Name</label>
                        <input type="text" id="apd_email_from_name" name="apd_email_from_name" class="apd-form-input" 
                               value="<?php echo esc_attr(get_option('apd_email_from_name', 'Freight Signs Customizer')); ?>">
                        <p class="apd-form-help">The name that appears in the "From" field of emails</p>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label" for="apd_email_from_address">From Email</label>
                        <input type="email" id="apd_email_from_address" name="apd_email_from_address" class="apd-form-input" 
                               value="<?php echo esc_attr(get_option('apd_email_from_address', get_option('admin_email'))); ?>">
                        <p class="apd-form-help">The email address that sends the notifications</p>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label" for="apd_email_subject">Order Confirmation Subject</label>
                        <input type="text" id="apd_email_subject" name="apd_email_subject" class="apd-form-input" 
                               value="<?php echo esc_attr(get_option('apd_email_subject', 'Order Confirmation - #{order_id}')); ?>">
                        <p class="apd-form-help">Use {order_id}, {customer_name}, {site_name} as placeholders</p>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label" for="apd_email_template">Email Template</label>
                        <textarea id="apd_email_template" name="apd_email_template" class="apd-form-input" rows="10" style="resize: vertical;"><?php echo esc_textarea(get_option('apd_email_template', 'Dear {customer_name},

Thank you for your order! We have received your order #{order_id} and will process it shortly.

Order Details:
- Product: {product_name}
- Quantity: {quantity}
- Total: {total_price}
- Order Date: {order_date}

We will send you another email once your order is ready for shipping.

Best regards,
{site_name}')); ?></textarea>
                        <p class="apd-form-help">Available placeholders: {customer_name}, {order_id}, {product_name}, {quantity}, {total_price}, {order_date}, {site_name}</p>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label">
                            <input type="checkbox" id="apd_admin_email_notifications" name="apd_admin_email_notifications" value="1" <?php checked(get_option('apd_admin_email_notifications', '1'), '1'); ?> style="margin-right: 8px;">
                            Send Admin Notifications
                        </label>
                        <p class="apd-form-help">Send email notifications to admin when new orders are placed</p>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label" for="apd_admin_email_address">Admin Email</label>
                        <input type="email" id="apd_admin_email_address" name="apd_admin_email_address" class="apd-form-input" 
                               value="<?php echo esc_attr(get_option('apd_admin_email_address', get_option('admin_email'))); ?>">
                        <p class="apd-form-help">Email address to receive admin notifications</p>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label" for="test_email_address">Test Email</label>
                        <div class="apd-grid-3">
                            <div>
                                <input type="email" id="test_email_address" class="apd-form-input" placeholder="your-email@example.com">
                                <p class="apd-form-help">Send a test email to verify your settings</p>
                            </div>
                            <div>
                                <button type="button" id="send_test_email" class="apd-btn w-full">Send Test Email</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SMTP Configuration -->
            <div class="apd-settings-card">
                <h2>SMTP Configuration</h2>
                <p>Configure SMTP settings for reliable email delivery. This is recommended for production use.</p>
                
                <div>
                    <div class="apd-form-group">
                        <label class="apd-form-label">
                            <input type="checkbox" id="apd_smtp_enabled" name="apd_smtp_enabled" value="1" <?php checked(get_option('apd_smtp_enabled', '0'), '1'); ?> style="margin-right: 8px;">
                            Enable SMTP
                        </label>
                        <p class="apd-form-help">Use SMTP instead of PHP mail() function for better delivery</p>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label" for="apd_smtp_host">SMTP Host</label>
                        <input type="text" id="apd_smtp_host" name="apd_smtp_host" class="apd-form-input" 
                               value="<?php echo esc_attr(get_option('apd_smtp_host', 'smtp.gmail.com')); ?>" placeholder="smtp.gmail.com">
                        <p class="apd-form-help">SMTP server hostname (e.g., smtp.gmail.com, smtp.mailgun.org)</p>
                    </div>
                    
                    <div class="apd-grid-2">
                        <div class="apd-form-group">
                            <label class="apd-form-label" for="apd_smtp_port">SMTP Port</label>
                            <input type="number" id="apd_smtp_port" name="apd_smtp_port" class="apd-form-input" 
                                   value="<?php echo esc_attr(get_option('apd_smtp_port', '587')); ?>" placeholder="587">
                            <p class="apd-form-help">Common ports: 587 (TLS), 465 (SSL), 25 (unencrypted)</p>
                        </div>
                        
                        <div class="apd-form-group">
                            <label class="apd-form-label" for="apd_smtp_encryption">Encryption</label>
                            <select id="apd_smtp_encryption" name="apd_smtp_encryption" class="apd-form-input">
                                <option value="none" <?php selected(get_option('apd_smtp_encryption', 'tls'), 'none'); ?>>None</option>
                                <option value="tls" <?php selected(get_option('apd_smtp_encryption', 'tls'), 'tls'); ?>>TLS</option>
                                <option value="ssl" <?php selected(get_option('apd_smtp_encryption', 'tls'), 'ssl'); ?>>SSL</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label" for="apd_smtp_username">SMTP Username</label>
                        <input type="text" id="apd_smtp_username" name="apd_smtp_username" class="apd-form-input" 
                               value="<?php echo esc_attr(get_option('apd_smtp_username', '')); ?>" placeholder="your-email@gmail.com">
                        <p class="apd-form-help">Your SMTP authentication username</p>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label" for="apd_smtp_password">SMTP Password</label>
                        <input type="password" id="apd_smtp_password" name="apd_smtp_password" class="apd-form-input" 
                               value="<?php echo esc_attr(get_option('apd_smtp_password', '')); ?>" placeholder="Your SMTP password">
                        <p class="apd-form-help">Your SMTP authentication password (use app password for Gmail)</p>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label" for="apd_smtp_from_email">From Email (SMTP)</label>
                        <input type="email" id="apd_smtp_from_email" name="apd_smtp_from_email" class="apd-form-input" 
                               value="<?php echo esc_attr(get_option('apd_smtp_from_email', get_option('admin_email'))); ?>" placeholder="noreply@yourdomain.com">
                        <p class="apd-form-help">Email address to send from (must be verified with your SMTP provider)</p>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label" for="apd_smtp_from_name">From Name (SMTP)</label>
                        <input type="text" id="apd_smtp_from_name" name="apd_smtp_from_name" class="apd-form-input" 
                               value="<?php echo esc_attr(get_option('apd_smtp_from_name', 'Freight Signs Customizer')); ?>" placeholder="Your Store Name">
                        <p class="apd-form-help">Name that appears in the "From" field</p>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label">
                            <input type="checkbox" id="apd_smtp_debug" name="apd_smtp_debug" value="1" <?php checked(get_option('apd_smtp_debug', '0'), '1'); ?> style="margin-right: 8px;">
                            Enable SMTP Debug
                        </label>
                        <p class="apd-form-help">Log SMTP connection details for troubleshooting</p>
                    </div>
                </div>
            </div>

            <!-- Email Templates -->
            <div class="apd-settings-card">
                <h2>Email Templates</h2>
                <p>Customize email templates for different scenarios. Use placeholders to personalize messages.</p>
                
                <div>
                    <div class="apd-form-group">
                        <label class="apd-form-label" for="apd_email_template_type">Template Type</label>
                        <select id="apd_email_template_type" name="apd_email_template_type" class="apd-form-input">
                            <option value="order_confirmation">Order Confirmation</option>
                            <option value="order_shipped">Order Shipped</option>
                            <option value="order_delivered">Order Delivered</option>
                            <option value="welcome_customer">Welcome Customer</option>
                            <option value="admin_notification">Admin Notification</option>
                        </select>
                        <p class="apd-form-help">Select which template to customize</p>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label" for="apd_email_template_subject">Email Subject</label>
                        <input type="text" id="apd_email_template_subject" name="apd_email_template_subject" class="apd-form-input" 
                               value="<?php echo esc_attr(get_option('apd_email_template_subject', 'Order Confirmation - #{order_id}')); ?>">
                        <p class="apd-form-help">Available placeholders: {order_id}, {customer_name}, {site_name}, {order_date}</p>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label" for="apd_email_template_body">Email Body</label>
                        <textarea id="apd_email_template_body" name="apd_email_template_body" class="apd-form-input" rows="15" style="resize: vertical;"><?php echo esc_textarea(get_option('apd_email_template_body', 'Dear {customer_name},

Thank you for your order! We have received your order #{order_id} and will process it shortly.

Order Details:
- Product: {product_name}
- Quantity: {quantity}
- Total: {total_price}
- Order Date: {order_date}

We will send you another email once your order is ready for shipping.

Best regards,
{site_name}')); ?></textarea>
                        <p class="apd-form-help">Available placeholders: {customer_name}, {order_id}, {product_name}, {quantity}, {total_price}, {order_date}, {site_name}</p>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label">
                            <input type="checkbox" id="apd_email_html_enabled" name="apd_email_html_enabled" value="1" <?php checked(get_option('apd_email_html_enabled', '1'), '1'); ?> style="margin-right: 8px;">
                            Enable HTML Emails
                        </label>
                        <p class="apd-form-help">Send emails in HTML format for better formatting</p>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label" for="apd_email_footer">Email Footer</label>
                        <textarea id="apd_email_footer" name="apd_email_footer" class="apd-form-input" rows="4" style="resize: vertical;"><?php echo esc_textarea(get_option('apd_email_footer', 'This email was sent from {site_name}. If you have any questions, please contact us.')); ?></textarea>
                        <p class="apd-form-help">Footer text that appears in all emails</p>
                    </div>
                </div>
            </div>

            <!-- Advanced Email Settings -->
            <div class="apd-settings-card">
                <h2>Advanced Email Settings</h2>
                <p>Configure advanced email delivery options and headers.</p>
                
                <div>
                    <div class="apd-form-group">
                        <label class="apd-form-label" for="apd_email_headers">Custom Headers</label>
                        <textarea id="apd_email_headers" name="apd_email_headers" class="apd-form-input" rows="4" style="resize: vertical;"><?php echo esc_textarea(get_option('apd_email_headers', 'X-Mailer: Freight Signs Customizer
X-Priority: 3')); ?></textarea>
                        <p class="apd-form-help">Custom email headers (one per line, format: Header: Value)</p>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label" for="apd_email_attachments">Default Attachments</label>
                        <input type="text" id="apd_email_attachments" name="apd_email_attachments" class="apd-form-input" 
                               value="<?php echo esc_attr(get_option('apd_email_attachments', '')); ?>" placeholder="/path/to/file1.pdf,/path/to/file2.jpg">
                        <p class="apd-form-help">Comma-separated list of file paths to attach to all emails</p>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label" for="apd_email_reply_to">Reply-To Address</label>
                        <input type="email" id="apd_email_reply_to" name="apd_email_reply_to" class="apd-form-input" 
                               value="<?php echo esc_attr(get_option('apd_email_reply_to', get_option('admin_email'))); ?>" placeholder="support@yourdomain.com">
                        <p class="apd-form-help">Email address for customer replies</p>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label" for="apd_email_cc">CC Addresses</label>
                        <input type="text" id="apd_email_cc" name="apd_email_cc" class="apd-form-input" 
                               value="<?php echo esc_attr(get_option('apd_email_cc', '')); ?>" placeholder="manager@yourdomain.com,admin@yourdomain.com">
                        <p class="apd-form-help">Comma-separated list of email addresses to CC on all emails</p>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label" for="apd_email_bcc">BCC Addresses</label>
                        <input type="text" id="apd_email_bcc" name="apd_email_bcc" class="apd-form-input" 
                               value="<?php echo esc_attr(get_option('apd_email_bcc', '')); ?>" placeholder="backup@yourdomain.com">
                        <p class="apd-form-help">Comma-separated list of email addresses to BCC on all emails</p>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label" for="apd_email_delay">Email Delay (seconds)</label>
                        <input type="number" id="apd_email_delay" name="apd_email_delay" class="apd-form-input" 
                               value="<?php echo esc_attr(get_option('apd_email_delay', '0')); ?>" min="0" max="3600">
                        <p class="apd-form-help">Delay before sending emails (0 = send immediately)</p>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label">
                            <input type="checkbox" id="apd_email_retry_failed" name="apd_email_retry_failed" value="1" <?php checked(get_option('apd_email_retry_failed', '1'), '1'); ?> style="margin-right: 8px;">
                            Retry Failed Emails
                        </label>
                        <p class="apd-form-help">Automatically retry sending failed emails</p>
                    </div>
                    
                    <div class="apd-form-group">
                        <label class="apd-form-label" for="apd_email_max_retries">Max Retry Attempts</label>
                        <input type="number" id="apd_email_max_retries" name="apd_email_max_retries" class="apd-form-input" 
                               value="<?php echo esc_attr(get_option('apd_email_max_retries', '3')); ?>" min="1" max="10">
                        <p class="apd-form-help">Maximum number of retry attempts for failed emails</p>
                    </div>
                </div>
            </div>


            <!-- Text Settings -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Text Settings</h2>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Default Font Family</label>
                        <select class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="Arial">Arial</option>
                            <option value="Helvetica">Helvetica</option>
                            <option value="Times New Roman">Times New Roman</option>
                            <option value="Georgia">Georgia</option>
                            <option value="Verdana">Verdana</option>
                            <option value="Impact">Impact</option>
                            <option value="Comic Sans MS">Comic Sans MS</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Default Font Size (px)</label>
                        <input type="number" value="24" min="8" max="100" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Default Text Color</label>
                        <div class="flex items-center space-x-3">
                            <input type="color" value="#000000" class="w-12 h-12 border border-gray-300 rounded cursor-pointer">
                            <input type="text" value="#000000" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" id="enable-outline" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <label for="enable-outline" class="ml-2 text-sm text-gray-700">Enable text outline by default</label>
                    </div>
                </div>
            </div>

            <!-- Upload Settings -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Upload Settings</h2>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Allowed File Types</label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">SVG (Scalable Vector Graphics)</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">PNG (Portable Network Graphics)</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">JPG/JPEG (Joint Photographic Experts Group)</span>
                            </label>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Upload Directory</label>
                        <input type="text" value="/wp-content/uploads/apd-svg/" readonly class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50 text-gray-600">
                        <p class="text-sm text-gray-500 mt-1">Directory where uploaded files are stored</p>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" id="auto-optimize" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <label for="auto-optimize" class="ml-2 text-sm text-gray-700">Automatically optimize uploaded images</label>
                    </div>
                </div>
            </div>

            <!-- Integration Settings -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Integration Settings</h2>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">WooCommerce Integration</label>
                        <div class="flex items-center">
                            <input type="checkbox" id="woocommerce-integration" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label for="woocommerce-integration" class="ml-2 text-sm text-gray-700">Enable WooCommerce product integration</label>
                        </div>
                        <p class="text-sm text-gray-500 mt-1">Allow customers to customize products before adding to cart</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Custom Post Type Slug</label>
                        <input type="text" value="apd_product" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-sm text-gray-500 mt-1">URL slug for designer products</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Shortcode</label>
                        <div class="flex items-center space-x-2">
                            <input type="text" value="[advanced_product_designer]" readonly class="flex-1 border border-gray-300 rounded-lg px-3 py-2 bg-gray-50 text-gray-600">
                            <button class="bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-700 transition-colors" onclick="copyToClipboard('[advanced_product_designer]')">
                                Copy
                            </button>
                        </div>
                        <p class="text-sm text-gray-500 mt-1">Use this shortcode to display the designer on any page</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="apd-settings-sidebar">
            <!-- Save Settings -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Save Settings</h3>
                <button class="w-full bg-green-600 text-white py-3 px-4 rounded-lg hover:bg-green-700 transition-colors font-medium">
                    Save All Settings
                </button>
                <button class="w-full bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-700 transition-colors mt-2">
                    Reset to Defaults
                </button>
            </div>

            <!-- System Status -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">System Status</h3>
                
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">PHP Version</span>
                        <span class="text-sm font-medium text-green-600"><?php echo PHP_VERSION; ?></span>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">WordPress Version</span>
                        <span class="text-sm font-medium text-green-600"><?php echo get_bloginfo('version'); ?></span>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Upload Directory</span>
                        <?php
                        $upload_dir = wp_upload_dir();
                        $svg_dir = $upload_dir['basedir'] . '/apd-svg/';
                        $is_writable = is_writable($upload_dir['basedir']);
                        ?>
                        <span class="text-sm font-medium <?php echo $is_writable ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo $is_writable ? 'Writable' : 'Not Writable'; ?>
                        </span>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Memory Limit</span>
                        <span class="text-sm font-medium text-green-600"><?php echo ini_get('memory_limit'); ?></span>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Max Upload Size</span>
                        <span class="text-sm font-medium text-green-600"><?php echo ini_get('upload_max_filesize'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>
                
                <div class="space-y-3">
                    <a href="<?php echo admin_url('admin.php?page=apd-designer'); ?>" class="block w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors text-center">
                        Open Designer
                    </a>
                    
                    <a href="<?php echo admin_url('post-new.php?post_type=apd_product'); ?>" class="block w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors text-center">
                        Create Product
                    </a>
                    
                    <a href="<?php echo admin_url('admin.php?page=apd-products'); ?>" class="block w-full bg-purple-600 text-white py-2 px-4 rounded-lg hover:bg-purple-700 transition-colors text-center">
                        View Products
                    </a>
                    
                    <button class="block w-full bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-700 transition-colors" onclick="clearCache()">
                        Clear Cache
                    </button>
                </div>
            </div>

            <!-- Help & Support -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Help & Support</h3>
                
                <div class="space-y-3">
                    <a href="#" class="block text-blue-600 hover:text-blue-800 text-sm">Documentation</a>
                    <a href="#" class="block text-blue-600 hover:text-blue-800 text-sm">Video Tutorials</a>
                    <a href="#" class="block text-blue-600 hover:text-blue-800 text-sm">Support Forum</a>
                    <a href="#" class="block text-blue-600 hover:text-blue-800 text-sm">Contact Support</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Show success message
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Copied!';
        button.classList.add('bg-green-600');
        button.classList.remove('bg-blue-600');
        
        setTimeout(() => {
            button.textContent = originalText;
            button.classList.remove('bg-green-600');
            button.classList.add('bg-blue-600');
        }, 2000);
    });
}

function clearCache() {
    if (confirm('Are you sure you want to clear the plugin cache? This will remove all temporary files and may affect performance temporarily.')) {
        // AJAX call to clear cache
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=apd_clear_cache&nonce=<?php echo wp_create_nonce('apd_nonce'); ?>'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Cache cleared successfully!');
            } else {
                alert('Failed to clear cache: ' + data.data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while clearing cache.');
        });
    }
}

// Auto-save settings on change
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('input, select, textarea');
    let saveTimeout;
    
    inputs.forEach(input => {
        input.addEventListener('change', function() {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(saveSettings, 1000);
        });
    });
    
    function saveSettings() {
        // Collect all form data
        const formData = new FormData();
        formData.append('action', 'apd_save_settings');
        formData.append('nonce', '<?php echo wp_create_nonce('apd_nonce'); ?>');
        
        // Add all input values
        inputs.forEach(input => {
            if (input.type === 'checkbox') {
                formData.append(input.name || input.id, input.checked ? '1' : '0');
            } else {
                formData.append(input.name || input.id, input.value);
            }
        });
        
        // Save settings
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Settings saved automatically');
            }
        })
        .catch(error => {
            console.error('Auto-save error:', error);
        });
    }
});
</script>

<style>
.apd-settings {
    max-width: 1200px;
    margin: 20px auto;
    padding: 20px;
}

@media (max-width: 768px) {
    .apd-settings {
        margin: 10px;
        padding: 15px;
    }
    
    .apd-settings .grid {
        grid-template-columns: 1fr;
    }
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadBtn = document.getElementById('apd-upload-font');
    const fileInput = document.getElementById('apd-font-file');
    const listEl = document.getElementById('apd-fonts-list');

    if (uploadBtn) {
        uploadBtn.addEventListener('click', function() {
            const file = fileInput && fileInput.files && fileInput.files[0];
            if (!file) { alert('Please select a font file.'); return; }
            const ext = file.name.toLowerCase().split('.').pop();
            if (!['ttf','otf','woff','woff2'].includes(ext)) { alert('Invalid font type.'); return; }
            if (file.size > 5 * 1024 * 1024) { alert('Font too large (max 5MB).'); return; }

            const fd = new FormData();
            fd.append('action', 'upload_font');
            fd.append('font_file', file);

            fetch(ajaxurl, { method: 'POST', body: fd })
                .then(r => r.json().catch(() => ({ success: false, data: 'Invalid JSON' })))
                .then(data => {
                    if (data && data.success) {
                        const font = data.data;
                        // Inject @font-face so preview uses the uploaded font immediately
                        (function ensureFontFace(){
                            const id = 'apd-font-' + font.family.replace(/\s+/g, '-');
                            if (!document.getElementById(id)) {
                                const style = document.createElement('style');
                                style.id = id;
                                style.textContent = `@font-face{font-family:'${font.family}';src:url('${font.url}') format('truetype');font-display:swap;}`;
                                document.head.appendChild(style);
                            }
                        })();
                        const row = document.createElement('div');
                        row.className = 'flex items-center justify-between py-3';
                        row.innerHTML = `
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-medium">${font.name}</span>
                                <span class="text-sm text-gray-500" style="font-family: '${font.family}';">Aa Bb Cc</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button class="apd-delete-font bg-red-600 text-white text-sm px-3 py-1 rounded" data-file="${font.file}">Delete</button>
                            </div>`;
                        listEl.appendChild(row);
                        alert('Font uploaded successfully.');
                        fileInput.value = '';
                    } else {
                        alert('Upload failed: ' + (data && data.data ? data.data : 'Unknown error'));
                    }
                })
                .catch(() => alert('Upload error.'));
        });
    }

    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('apd-delete-font')) {
            const index = e.target.getAttribute('data-index');
            const file = e.target.getAttribute('data-file');
            if (!confirm('Delete this font?')) return;
            const fd = new FormData();
            fd.append('action', 'apd_delete_font');
            if (index !== null) fd.append('index', index);
            if (file) fd.append('file', file);
            fetch(ajaxurl, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data && data.success) {
                        e.target.closest('.flex.items-center.justify-between.py-3')?.remove();
                    } else {
                        alert('Delete failed: ' + (data && data.data ? data.data : 'Unknown error'));
                    }
                })
                .catch(() => alert('Delete error.'));
        }
    });

    // Test email functionality
    const sendTestEmailBtn = document.getElementById('send_test_email');
    const testEmailInput = document.getElementById('test_email_address');
    
    if (sendTestEmailBtn && testEmailInput) {
        sendTestEmailBtn.addEventListener('click', function() {
            const testEmail = testEmailInput.value.trim();
            if (!testEmail) {
                alert('Please enter an email address to test');
                return;
            }
            
            if (!testEmail.includes('@')) {
                alert('Please enter a valid email address');
                return;
            }
            
            // Disable button and show loading
            sendTestEmailBtn.disabled = true;
            sendTestEmailBtn.textContent = 'Sending...';
            
            const formData = new FormData();
            formData.append('action', 'apd_send_test_email');
            formData.append('test_email', testEmail);
            formData.append('nonce', '<?php echo wp_create_nonce('apd_ajax_nonce'); ?>');
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('JSON parse error:', text);
                        throw new Error('Invalid JSON response: ' + text.substring(0, 100));
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    alert('✅ Test email sent successfully! Check your inbox.');
                } else {
                    alert('❌ Failed to send test email: ' + (data.data || 'Unknown error'));
                }
            })
            .catch(error => {
                alert('❌ Error sending test email: ' + error.message);
            })
            .finally(() => {
                // Re-enable button
                sendTestEmailBtn.disabled = false;
                sendTestEmailBtn.textContent = 'Send Test Email';
            });
        });
    }


});
</script>
