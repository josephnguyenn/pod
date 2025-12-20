<?php
/**
 * Email Service Class
 * 
 * Handles all email operations including order confirmations, notifications, and status updates
 * Provides centralized email template management and SMTP configuration
 *
 * @package AdvancedProductDesigner
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class APD_Email_Service
{
    /**
     * Template service instance
     * @var APD_Template_Service
     */
    private $template_service;

    /**
     * Constructor
     *
     * @param APD_Template_Service $template_service Template service instance
     */
    public function __construct($template_service = null)
    {
        $this->template_service = $template_service ?? new APD_Template_Service();
    }

    /**
     * Send order confirmation email to customer
     *
     * @param int $order_id Order ID
     * @param array $order_data Order data
     * @return bool Success status
     */
    public function send_order_confirmation($order_id, $order_data)
    {
        try {
            // Check if email notifications are enabled
            if (!$this->is_email_enabled()) {
                return false;
            }

            $customer_email = $order_data['customer_email'] ?? '';
            if (empty($customer_email) || !is_email($customer_email)) {
                error_log('APD Email Service - Invalid customer email for order #' . $order_id);
                return false;
            }

            // Configure SMTP if enabled
            $this->configure_smtp();

            $from_name = get_option('apd_email_from_name', get_bloginfo('name'));
            $from_email = get_option('apd_email_from_address', get_option('admin_email'));
            $subject = sprintf(__('Order Confirmation - #%d', 'advanced-product-designer'), $order_id);

            // Build email HTML
            $message = $this->build_order_confirmation_email($order_id, $order_data);

            // Set headers
            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
                sprintf('From: %s <%s>', $from_name, $from_email)
            );

            $sent = wp_mail($customer_email, $subject, $message, $headers);

            if ($sent) {
                error_log('APD Email Service - Order confirmation sent to: ' . $customer_email);
            } else {
                error_log('APD Email Service - Failed to send confirmation to: ' . $customer_email);
            }

            return $sent;

        } catch (Exception $e) {
            error_log('APD Email Service - Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send admin notification email for new order
     *
     * @param int $order_id Order ID
     * @param array $order_data Order data
     * @return bool Success status
     */
    public function send_admin_notification($order_id, $order_data)
    {
        try {
            // Check if admin notifications are enabled
            if (!get_option('apd_admin_email_notifications', '1')) {
                return false;
            }

            $admin_email = get_option('apd_admin_email_address', get_option('admin_email'));
            if (empty($admin_email) || !is_email($admin_email)) {
                error_log('APD Email Service - Invalid admin email');
                return false;
            }

            // Configure SMTP if enabled
            $this->configure_smtp();

            $from_name = get_option('apd_email_from_name', get_bloginfo('name'));
            $from_email = get_option('apd_email_from_address', get_option('admin_email'));
            $subject = sprintf(__('New Order #%d - %s', 'advanced-product-designer'), $order_id, get_bloginfo('name'));

            // Build admin email HTML
            $message = $this->build_admin_notification_email($order_id, $order_data);

            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
                sprintf('From: %s <%s>', $from_name, $from_email)
            );

            $sent = wp_mail($admin_email, $subject, $message, $headers);

            if ($sent) {
                error_log('APD Email Service - Admin notification sent for order #' . $order_id);
            } else {
                error_log('APD Email Service - Failed to send admin notification for order #' . $order_id);
            }

            return $sent;

        } catch (Exception $e) {
            error_log('APD Email Service - Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send order status update email
     *
     * @param int $order_id Order ID
     * @param array $order_data Order data
     * @param string $status_type Status type (confirmed, completed, etc.)
     * @return bool Success status
     */
    public function send_status_update($order_id, $order_data, $status_type = 'confirmed')
    {
        try {
            if (!$this->is_email_enabled()) {
                return false;
            }

            $customer_email = $order_data['customer_email'] ?? '';
            if (empty($customer_email) || !is_email($customer_email)) {
                return false;
            }

            $this->configure_smtp();

            $from_name = get_option('apd_email_from_name', get_bloginfo('name'));
            $from_email = get_option('apd_email_from_address', get_option('admin_email'));
            
            $status_labels = array(
                'confirmed' => __('Order Confirmed', 'advanced-product-designer'),
                'completed' => __('Order Completed', 'advanced-product-designer'),
                'processing' => __('Order Processing', 'advanced-product-designer'),
                'shipped' => __('Order Shipped', 'advanced-product-designer'),
            );

            $subject = sprintf('%s - #%d', $status_labels[$status_type] ?? __('Order Update', 'advanced-product-designer'), $order_id);
            $message = $this->build_status_update_email($order_id, $order_data, $status_type);

            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
                sprintf('From: %s <%s>', $from_name, $from_email)
            );

            return wp_mail($customer_email, $subject, $message, $headers);

        } catch (Exception $e) {
            error_log('APD Email Service - Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Build order confirmation email HTML
     *
     * @param int $order_id Order ID
     * @param array $order_data Order data
     * @return string Email HTML
     */
    private function build_order_confirmation_email($order_id, $order_data)
    {
        $html = $this->get_email_header();

        $html .= '<h1 style="color: #333; font-size: 24px; margin-bottom: 20px;">' . __('Thank You for Your Order!', 'advanced-product-designer') . '</h1>';
        $html .= '<p style="font-size: 16px; line-height: 1.5; color: #666;">' . sprintf(__('Hi %s,', 'advanced-product-designer'), esc_html($order_data['customer_name'])) . '</p>';
        $html .= '<p style="font-size: 16px; line-height: 1.5; color: #666;">' . __('We have received your order and will begin processing it soon.', 'advanced-product-designer') . '</p>';

        $html .= '<div style="background: #f8f8f8; padding: 20px; margin: 20px 0; border-radius: 5px;">';
        $html .= '<h2 style="color: #333; font-size: 20px; margin-top: 0;">' . sprintf(__('Order #%d', 'advanced-product-designer'), $order_id) . '</h2>';
        $html .= '<p><strong>' . __('Order Date:', 'advanced-product-designer') . '</strong> ' . esc_html($order_data['order_date']) . '</p>';
        $html .= '<p><strong>' . __('Total Amount:', 'advanced-product-designer') . '</strong> ' . APD_Template_Service::format_price($order_data['total_amount']) . '</p>';
        $html .= '<p><strong>' . __('Payment Method:', 'advanced-product-designer') . '</strong> ' . esc_html(ucfirst($order_data['payment_method'])) . '</p>';
        $html .= '</div>';

        // Order items
        $cart_items = json_decode($order_data['cart_items'], true);
        if (!empty($cart_items) && is_array($cart_items)) {
            $html .= '<h3 style="color: #333; font-size: 18px; margin-top: 30px;">' . __('Order Items:', 'advanced-product-designer') . '</h3>';
            $html .= '<table style="width: 100%; border-collapse: collapse; margin: 10px 0;">';
            $html .= '<tr style="background: #f0f0f0;">';
            $html .= '<th style="padding: 10px; text-align: left; border: 1px solid #ddd;">' . __('Product', 'advanced-product-designer') . '</th>';
            $html .= '<th style="padding: 10px; text-align: center; border: 1px solid #ddd;">' . __('Quantity', 'advanced-product-designer') . '</th>';
            $html .= '<th style="padding: 10px; text-align: right; border: 1px solid #ddd;">' . __('Price', 'advanced-product-designer') . '</th>';
            $html .= '</tr>';

            foreach ($cart_items as $item) {
                $html .= '<tr>';
                $html .= '<td style="padding: 10px; border: 1px solid #ddd;">' . esc_html($item['product_name'] ?? 'Product') . '</td>';
                $html .= '<td style="padding: 10px; text-align: center; border: 1px solid #ddd;">' . esc_html($item['quantity'] ?? 1) . '</td>';
                $html .= '<td style="padding: 10px; text-align: right; border: 1px solid #ddd;">' . APD_Template_Service::format_price($item['total'] ?? 0) . '</td>';
                $html .= '</tr>';
            }

            $html .= '</table>';
        }

        $html .= '<p style="font-size: 16px; line-height: 1.5; color: #666; margin-top: 30px;">' . __('If you have any questions, please contact us.', 'advanced-product-designer') . '</p>';

        $html .= $this->get_email_footer();

        return $html;
    }

    /**
     * Build admin notification email HTML
     *
     * @param int $order_id Order ID
     * @param array $order_data Order data
     * @return string Email HTML
     */
    private function build_admin_notification_email($order_id, $order_data)
    {
        $html = $this->get_email_header();

        $html .= '<h1 style="color: #333; font-size: 24px; margin-bottom: 20px;">' . sprintf(__('New Order #%d', 'advanced-product-designer'), $order_id) . '</h1>';

        $html .= '<div style="background: #f8f8f8; padding: 20px; margin: 20px 0; border-radius: 5px;">';
        $html .= '<h2 style="color: #333; font-size: 20px; margin-top: 0;">' . __('Customer Information', 'advanced-product-designer') . '</h2>';
        $html .= '<p><strong>' . __('Name:', 'advanced-product-designer') . '</strong> ' . esc_html($order_data['customer_name']) . '</p>';
        $html .= '<p><strong>' . __('Email:', 'advanced-product-designer') . '</strong> ' . esc_html($order_data['customer_email']) . '</p>';
        $html .= '<p><strong>' . __('Phone:', 'advanced-product-designer') . '</strong> ' . esc_html($order_data['customer_phone']) . '</p>';
        if (!empty($order_data['customer_address'])) {
            $html .= '<p><strong>' . __('Address:', 'advanced-product-designer') . '</strong> ' . nl2br(esc_html($order_data['customer_address'])) . '</p>';
        }
        $html .= '</div>';

        $html .= '<div style="background: #e8f4f8; padding: 20px; margin: 20px 0; border-radius: 5px;">';
        $html .= '<h2 style="color: #333; font-size: 20px; margin-top: 0;">' . __('Order Details', 'advanced-product-designer') . '</h2>';
        $html .= '<p><strong>' . __('Order Date:', 'advanced-product-designer') . '</strong> ' . esc_html($order_data['order_date']) . '</p>';
        $html .= '<p><strong>' . __('Total Amount:', 'advanced-product-designer') . '</strong> ' . APD_Template_Service::format_price($order_data['total_amount']) . '</p>';
        $html .= '<p><strong>' . __('Payment Method:', 'advanced-product-designer') . '</strong> ' . esc_html(ucfirst($order_data['payment_method'])) . '</p>';
        $html .= '<p><strong>' . __('Payment Status:', 'advanced-product-designer') . '</strong> ' . esc_html(ucfirst($order_data['payment_status'])) . '</p>';
        $html .= '</div>';

        // Admin link to view order
        $admin_url = admin_url('admin.php?page=apd-orders&action=view&order_id=' . $order_id);
        $html .= '<p style="text-align: center; margin: 30px 0;">';
        $html .= '<a href="' . esc_url($admin_url) . '" style="background: #0073aa; color: #fff; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">' . __('View Order in Admin', 'advanced-product-designer') . '</a>';
        $html .= '</p>';

        $html .= $this->get_email_footer();

        return $html;
    }

    /**
     * Build status update email HTML
     *
     * @param int $order_id Order ID
     * @param array $order_data Order data
     * @param string $status_type Status type
     * @return string Email HTML
     */
    private function build_status_update_email($order_id, $order_data, $status_type)
    {
        $html = $this->get_email_header();

        $status_messages = array(
            'confirmed' => __('Your order has been confirmed and is being prepared.', 'advanced-product-designer'),
            'processing' => __('Your order is now being processed.', 'advanced-product-designer'),
            'completed' => __('Your order has been completed!', 'advanced-product-designer'),
            'shipped' => __('Your order has been shipped!', 'advanced-product-designer'),
        );

        $html .= '<h1 style="color: #333; font-size: 24px; margin-bottom: 20px;">' . sprintf(__('Order #%d Update', 'advanced-product-designer'), $order_id) . '</h1>';
        $html .= '<p style="font-size: 16px; line-height: 1.5; color: #666;">' . sprintf(__('Hi %s,', 'advanced-product-designer'), esc_html($order_data['customer_name'])) . '</p>';
        $html .= '<p style="font-size: 16px; line-height: 1.5; color: #666;">' . ($status_messages[$status_type] ?? __('Your order status has been updated.', 'advanced-product-designer')) . '</p>';

        $html .= '<div style="background: #f8f8f8; padding: 20px; margin: 20px 0; border-radius: 5px;">';
        $html .= '<h2 style="color: #333; font-size: 20px; margin-top: 0;">' . sprintf(__('Order #%d', 'advanced-product-designer'), $order_id) . '</h2>';
        $html .= '<p><strong>' . __('Status:', 'advanced-product-designer') . '</strong> ' . esc_html(ucfirst($status_type)) . '</p>';
        $html .= '<p><strong>' . __('Total Amount:', 'advanced-product-designer') . '</strong> ' . APD_Template_Service::format_price($order_data['total_amount']) . '</p>';
        $html .= '</div>';

        $html .= $this->get_email_footer();

        return $html;
    }

    /**
     * Get email header HTML
     *
     * @return string Header HTML
     */
    private function get_email_header()
    {
        $html = '<!DOCTYPE html>';
        $html .= '<html lang="en">';
        $html .= '<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>';
        $html .= '<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">';
        $html .= '<div style="border: 1px solid #ddd; padding: 30px; background: #fff;">';

        // Logo
        $logo_url = get_option('apd_email_logo_url');
        if ($logo_url) {
            $html .= '<div style="text-align: center; margin-bottom: 30px;">';
            $html .= '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr(get_bloginfo('name')) . '" style="max-width: 200px; height: auto;">';
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Get email footer HTML
     *
     * @return string Footer HTML
     */
    private function get_email_footer()
    {
        $html = '<div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #999; font-size: 12px;">';
        $html .= '<p>&copy; ' . date('Y') . ' ' . esc_html(get_bloginfo('name')) . '. ' . __('All rights reserved.', 'advanced-product-designer') . '</p>';
        $html .= '<p>' . sprintf(__('Powered by %s', 'advanced-product-designer'), '<strong>Advanced Product Designer</strong>') . '</p>';
        $html .= '</div>';
        $html .= '</div></body></html>';

        return $html;
    }

    /**
     * Check if email notifications are enabled
     *
     * @return bool True if enabled
     */
    private function is_email_enabled()
    {
        return get_option('apd_email_enabled', '1') === '1';
    }

    /**
     * Configure SMTP if enabled
     */
    private function configure_smtp()
    {
        if (get_option('apd_smtp_enabled', '0') !== '1') {
            return;
        }

        add_action('phpmailer_init', function($phpmailer) {
            $phpmailer->isSMTP();
            $phpmailer->Host = get_option('apd_smtp_host', 'smtp.gmail.com');
            $phpmailer->Port = intval(get_option('apd_smtp_port', 587));
            $phpmailer->SMTPAuth = true;
            $phpmailer->Username = get_option('apd_smtp_username', '');
            $phpmailer->Password = get_option('apd_smtp_password', '');
            $phpmailer->SMTPSecure = get_option('apd_smtp_encryption', 'tls');
        });
    }
}
