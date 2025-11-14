<?php
/**
 * APD Debug Logger
 * Centralized error logging and debugging system
 * 
 * @package AdvancedProductDesigner
 */

if (!defined('ABSPATH')) {
    exit;
}

class APD_Debug_Logger {
    
    private static $instance = null;
    private $log_file;
    private $enabled;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->log_file = APD_PLUGIN_PATH . 'debug.log';
        $this->enabled = get_option('apd_debug_mode', false);
        
        add_action('admin_menu', array($this, 'add_debug_page'));
        add_action('wp_ajax_apd_toggle_debug', array($this, 'ajax_toggle_debug'));
        add_action('wp_ajax_apd_clear_log', array($this, 'ajax_clear_log'));
        add_action('wp_ajax_apd_download_log', array($this, 'ajax_download_log'));
    }
    
    public function add_debug_page() {
        add_submenu_page(
            'apd-dashboard',
            'Debug Log',
            '🐛 Debug Log',
            'manage_options',
            'apd-debug-log',
            array($this, 'render_debug_page')
        );
    }
    
    public function render_debug_page() {
        $debug_enabled = get_option('apd_debug_mode', false);
        $log_exists = file_exists($this->log_file);
        $log_size = $log_exists ? size_format(filesize($this->log_file)) : '0 KB';
        
        ?>
        <div class="wrap">
            <h1>🐛 Debug Log</h1>
            
            <div class="apd-debug-controls" style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <h2>Debug Mode: 
                    <span id="debug-status" style="color: <?php echo $debug_enabled ? '#46b450' : '#dc3232'; ?>">
                        <?php echo $debug_enabled ? 'ENABLED' : 'DISABLED'; ?>
                    </span>
                </h2>
                
                <p>Log file size: <strong><?php echo $log_size; ?></strong></p>
                
                <button type="button" class="button button-primary" id="toggle-debug">
                    <?php echo $debug_enabled ? 'Disable Debug Mode' : 'Enable Debug Mode'; ?>
                </button>
                
                <button type="button" class="button" id="refresh-log">
                    <span class="dashicons dashicons-update"></span> Refresh
                </button>
                
                <button type="button" class="button" id="download-log" <?php echo !$log_exists ? 'disabled' : ''; ?>>
                    <span class="dashicons dashicons-download"></span> Download Log
                </button>
                
                <button type="button" class="button button-link-delete" id="clear-log" <?php echo !$log_exists ? 'disabled' : ''; ?>>
                    <span class="dashicons dashicons-trash"></span> Clear Log
                </button>
            </div>
            
            <div class="apd-log-viewer" style="background: #1e1e1e; color: #d4d4d4; padding: 20px; border-radius: 8px; font-family: 'Courier New', monospace; font-size: 13px; max-height: 600px; overflow-y: auto;">
                <pre id="log-content" style="margin: 0; white-space: pre-wrap; word-wrap: break-word;"><?php
                    if ($log_exists) {
                        echo esc_html(file_get_contents($this->log_file));
                    } else {
                        echo 'No log entries yet. Enable debug mode to start logging.';
                    }
                ?></pre>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#toggle-debug').on('click', function() {
                var $btn = $(this);
                $btn.prop('disabled', true);
                
                $.post(ajaxurl, {
                    action: 'apd_toggle_debug',
                    nonce: '<?php echo wp_create_nonce('apd_debug'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    }
                }).always(function() {
                    $btn.prop('disabled', false);
                });
            });
            
            $('#clear-log').on('click', function() {
                if (!confirm('Are you sure you want to clear the debug log?')) {
                    return;
                }
                
                var $btn = $(this);
                $btn.prop('disabled', true);
                
                $.post(ajaxurl, {
                    action: 'apd_clear_log',
                    nonce: '<?php echo wp_create_nonce('apd_debug'); ?>'
                }, function(response) {
                    if (response.success) {
                        $('#log-content').text('Log cleared.');
                        $btn.prop('disabled', true);
                        $('#download-log').prop('disabled', true);
                    }
                }).always(function() {
                    $btn.prop('disabled', false);
                });
            });
            
            $('#refresh-log').on('click', function() {
                location.reload();
            });
            
            $('#download-log').on('click', function() {
                window.location.href = ajaxurl + '?action=apd_download_log&nonce=<?php echo wp_create_nonce('apd_debug'); ?>';
            });
            
            // Auto-scroll to bottom
            var logViewer = $('.apd-log-viewer');
            logViewer.scrollTop(logViewer[0].scrollHeight);
        });
        </script>
        <?php
    }
    
    public function ajax_toggle_debug() {
        check_ajax_referer('apd_debug', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $current = get_option('apd_debug_mode', false);
        update_option('apd_debug_mode', !$current);
        
        wp_send_json_success();
    }
    
    public function ajax_clear_log() {
        check_ajax_referer('apd_debug', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        if (file_exists($this->log_file)) {
            unlink($this->log_file);
        }
        
        wp_send_json_success();
    }
    
    public function ajax_download_log() {
        check_ajax_referer('apd_debug', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Permission denied');
        }
        
        if (!file_exists($this->log_file)) {
            wp_die('Log file not found');
        }
        
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="apd-debug-' . date('Y-m-d-His') . '.log"');
        header('Content-Length: ' . filesize($this->log_file));
        readfile($this->log_file);
        exit;
    }
    
    public function log($message, $level = 'INFO', $context = array()) {
        if (!$this->enabled) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $level = strtoupper($level);
        
        $formatted_message = "[{$timestamp}] [{$level}] {$message}";
        
        if (!empty($context)) {
            $formatted_message .= "\n" . print_r($context, true);
        }
        
        $formatted_message .= "\n";
        
        file_put_contents($this->log_file, $formatted_message, FILE_APPEND);
    }
    
    public function info($message, $context = array()) {
        $this->log($message, 'INFO', $context);
    }
    
    public function warning($message, $context = array()) {
        $this->log($message, 'WARNING', $context);
    }
    
    public function error($message, $context = array()) {
        $this->log($message, 'ERROR', $context);
    }
    
    public function debug($message, $context = array()) {
        $this->log($message, 'DEBUG', $context);
    }
}

// Global helper functions
function apd_log($message, $level = 'INFO', $context = array()) {
    APD_Debug_Logger::get_instance()->log($message, $level, $context);
}

function apd_log_error($message, $context = array()) {
    APD_Debug_Logger::get_instance()->error($message, $context);
}

function apd_log_warning($message, $context = array()) {
    APD_Debug_Logger::get_instance()->warning($message, $context);
}

function apd_log_info($message, $context = array()) {
    APD_Debug_Logger::get_instance()->info($message, $context);
}

// Initialize
APD_Debug_Logger::get_instance();
