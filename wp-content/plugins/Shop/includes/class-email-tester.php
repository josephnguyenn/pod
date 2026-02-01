<?php
/**
 * Email Tester
 * 
 * Handles email testing functionality and SMTP configuration testing
 *
 * @package AdvancedProductDesigner
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class APD_Email_Tester
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
        add_action('wp_ajax_apd_send_test_email', array($this, 'send_test_email'));
        add_action('wp_ajax_apd_send_advanced_test_email', array($this, 'send_advanced_test_email'));
        add_action('wp_ajax_apd_test_smtp_connection', array($this, 'test_smtp_connection'));
        add_action('wp_ajax_apd_get_email_logs', array($this, 'get_email_logs'));
    }

    public function send_test_email() {
        try {
            if (!current_user_can('manage_options')) {
                wp_send_json_error('Unauthorized');
            }

            $test_email = sanitize_email(isset($_POST['test_email']) ? $_POST['test_email'] : '');
            if (empty($test_email)) {
                wp_send_json_error('Email address required');
            }

            $from_name = get_option('apd_email_from_name', 'Freight Signs Customizer');
            $from_email = get_option('apd_email_from_address', get_option('admin_email'));
            
            $subject = 'Test Email - ' . $from_name;
            $message = 'This is a test email from your Freight Signs Customizer plugin. If you receive this, your email settings are working correctly!';
            
            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $from_name . ' <' . $from_email . '>'
            );

            $sent = wp_mail($test_email, $subject, $message, $headers);

            if ($sent) {
                wp_send_json_success('Test email sent successfully!');
            } else {
                wp_send_json_error('Failed to send test email. Check your email settings.');
            }

        } catch (Exception $e) {
            wp_send_json_error('Error: ' . $e->getMessage());
        }
    }

    public function send_advanced_test_email() {
        try {
            if (!current_user_can('manage_options')) {
                wp_send_json_error('Unauthorized');
            }

            $test_email = sanitize_email(isset($_POST['test_email']) ? $_POST['test_email'] : '');
            $email_method = sanitize_text_field(isset($_POST['email_method']) ? $_POST['email_method'] : 'php');
            $email_type = sanitize_text_field(isset($_POST['email_type']) ? $_POST['email_type'] : 'order_confirmation');
            $include_attachments = (bool)(isset($_POST['include_attachments']) ? $_POST['include_attachments'] : false);

            if (empty($test_email)) {
                wp_send_json_error('Email address required');
            }

            // Configure SMTP if enabled
            if ($email_method === 'smtp' && get_option('apd_smtp_enabled', '0') === '1') {
                $this->configure_smtp();
            }

            $from_name = get_option('apd_email_from_name', 'Freight Signs Customizer');
            $from_email = get_option('apd_email_from_address', get_option('admin_email'));
            
            $subject = 'Test Email - ' . $from_name . ' (' . strtoupper($email_method) . ')';
            $message = $this->get_test_email_template($email_type);
            
            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $from_name . ' <' . $from_email . '>'
            );

            // Add attachments if requested
            $attachments = array();
            if ($include_attachments) {
                $attachments = $this->get_test_attachments();
            }

            $sent = wp_mail($test_email, $subject, $message, $headers, $attachments);

            if ($sent) {
                wp_send_json_success('Test email sent successfully via ' . strtoupper($email_method) . '!');
            } else {
                wp_send_json_error('Failed to send test email via ' . strtoupper($email_method) . '. Check your email settings.');
            }

        } catch (Exception $e) {
            wp_send_json_error('Error: ' . $e->getMessage());
        }
    }

    public function test_smtp_connection() {
        try {
            if (!current_user_can('manage_options')) {
                wp_send_json_error('Unauthorized');
            }

            $smtp_enabled = get_option('apd_smtp_enabled', '0');
            if ($smtp_enabled !== '1') {
                wp_send_json_error('SMTP is not enabled');
            }

            $host = get_option('apd_smtp_host', 'smtp.gmail.com');
            $port = get_option('apd_smtp_port', '587');
            $encryption = get_option('apd_smtp_encryption', 'tls');
            $username = get_option('apd_smtp_username', '');
            $password = get_option('apd_smtp_password', '');

            if (empty($username) || empty($password)) {
                wp_send_json_error('SMTP credentials not configured');
            }

            $start_time = microtime(true);
            
            // Test SMTP connection
            $connection = $this->test_smtp_connection_direct($host, $port, $username, $password, $encryption);
            
            $end_time = microtime(true);
            $response_time = round(($end_time - $start_time) * 1000);

            if ($connection) {
                wp_send_json_success(array(
                    'host' => $host,
                    'port' => $port,
                    'encryption' => $encryption,
                    'response_time' => $response_time
                ));
            } else {
                wp_send_json_error('SMTP connection failed');
            }

        } catch (Exception $e) {
            wp_send_json_error('SMTP test error: ' . $e->getMessage());
        }
    }

    public function get_email_logs() {
        try {
            if (!current_user_can('manage_options')) {
                wp_send_json_error('Unauthorized');
            }

            $logs = get_option('apd_email_logs', array());
            
            // Return last 10 logs
            $recent_logs = array_slice($logs, -10);
            
            wp_send_json_success($recent_logs);

        } catch (Exception $e) {
            wp_send_json_error('Error retrieving email logs: ' . $e->getMessage());
        }
    }

    private function configure_smtp($disable_debug = false) {
        if (get_option('apd_smtp_enabled', '0') !== '1') {
            return;
        }

        $host = get_option('apd_smtp_host', 'smtp.gmail.com');
        $port = get_option('apd_smtp_port', '587');
        $encryption = get_option('apd_smtp_encryption', 'tls');
        $username = get_option('apd_smtp_username', '');
        $password = get_option('apd_smtp_password', '');

        if (empty($username) || empty($password)) {
            return;
        }

        // Configure PHPMailer for SMTP - only add the action once
        static $smtp_configured = false;
        if (!$smtp_configured) {
            add_action('phpmailer_init', function($phpmailer) use ($host, $port, $encryption, $username, $password, $disable_debug) {
                $phpmailer->isSMTP();
                $phpmailer->Host = $host;
                $phpmailer->SMTPAuth = true;
                $phpmailer->Username = $username;
                $phpmailer->Password = $password;
                $phpmailer->SMTPSecure = $encryption;
                $phpmailer->Port = $port;
                // Disable debug during AJAX calls to prevent corrupting JSON responses
                if ($disable_debug || wp_doing_ajax()) {
                    $phpmailer->SMTPDebug = 0;
                } else {
                    $phpmailer->SMTPDebug = get_option('apd_smtp_debug', '0') === '1' ? 2 : 0;
                }
            });
            $smtp_configured = true;
        }
    }

    private function test_smtp_connection_direct($host, $port, $username, $password, $encryption) {
        try {
            $socket = fsockopen($host, $port, $errno, $errstr, 10);
            if (!$socket) {
                return false;
            }
            fclose($socket);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    private function get_test_email_template($email_type) {
        $site_name = get_bloginfo('name');
        $site_url = home_url();
        $current_year = date('Y');
        $logo_url = get_option('apd_email_logo_url', '');
        $support_email = 'gotospectrum@gmail.com';

        ob_start();
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Email</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                },
            },
        };
    </script>
    <style>
        body {
            font-family: 'Inter', 'sans-serif';
            margin: 0;
            padding: 0;
            width: 100%;
            background-color: #f4f4f7;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        .header {
            padding: 2rem;
            background-color: #f9fafb;
            text-align: center;
        }
        .content {
            padding: 2rem;
        }
        .footer {
            padding: 2rem;
            background-color: #f9fafb;
            text-align: center;
            font-size: 0.875rem;
            color: #6b7280;
        }
        .button {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background-color: #111827;
            color: #ffffff;
            text-decoration: none;
            border-radius: 0.375rem;
            font-weight: 600;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto my-8 shadow-lg">
        <div class="header">
            <?php if (!empty($logo_url)): ?>
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($site_name); ?>" style="max-height: 48px; margin: 0 auto 24px; display: block;">
            <?php else: ?>
                <div class="h-12 w-48 mx-auto bg-gray-200 rounded-lg flex items-center justify-center text-gray-500 mb-6">
                    <?php echo esc_html($site_name); ?>
                </div>
            <?php endif; ?>
            <h1 class="text-3xl font-bold text-gray-900">Test Email</h1>
            <p class="text-gray-600 mt-2">This is a test email from your system</p>
        </div>

        <div class="content">
            <p class="text-lg text-gray-800 mb-6">
                Dear Test User,
            </p>
            <p class="text-gray-700 mb-6">
                This is a test email to verify that your email configuration is working correctly. If you're reading this, it means your email settings are properly configured!
            </p>
            
            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Email Type</h3>
                <p class="text-gray-700"><?php echo esc_html(ucwords(str_replace('_', ' ', $email_type))); ?></p>
            </div>
            
            <div class="text-center mt-8">
                <a href="<?php echo esc_url($site_url); ?>" class="button">
                    Visit Website
                </a>
            </div>
        </div>

        <div class="footer">
            <p class="mb-2">
                Questions about your configuration?
            </p>
            <p class="mb-4">
                Contact support at <a href="mailto:<?php echo esc_attr($support_email); ?>" class="text-blue-600 underline"><?php echo esc_html($support_email); ?></a>.
            </p>
            <p class="text-sm text-gray-500">
                © <?php echo esc_html($current_year); ?> <?php echo esc_html($site_name); ?>. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
        <?php
        return ob_get_clean();
    }

    private function get_test_attachments() {
        // Return empty array for now - can be extended to include actual test files
        return array();
    }

}
