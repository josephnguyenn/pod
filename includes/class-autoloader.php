<?php
/**
 * Class Autoloader
 * 
 * Automatically loads class files when needed
 * PSR-4 style autoloading for plugin classes
 *
 * @package AdvancedProductDesigner
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class APD_Autoloader
{
    /**
     * Register autoloader
     */
    public static function register()
    {
        spl_autoload_register(array(__CLASS__, 'autoload'));
    }

    /**
     * Autoload class files
     *
     * @param string $class Class name
     */
    public static function autoload($class)
    {
        // Only autoload APD classes
        if (strpos($class, 'APD_') !== 0) {
            return;
        }

        // Convert class name to file name
        // APD_Cart_Service -> class-cart-service.php
        $class_name = str_replace('APD_', '', $class);
        $file_name = 'class-' . strtolower(str_replace('_', '-', $class_name)) . '.php';

        // Check in includes directory
        $file_path = APD_PLUGIN_PATH . 'includes/' . $file_name;

        if (file_exists($file_path)) {
            require_once $file_path;
            return;
        }

        // Check in root directory (for backward compatibility)
        $root_path = APD_PLUGIN_PATH . $file_name;
        if (file_exists($root_path)) {
            require_once $root_path;
        }
    }

    /**
     * Load all service classes
     */
    public static function load_services()
    {
        $services = array(
            'config',
            'cart-service',
            'order-service',
            'email-service',
            'template-service',
            'apd-debug-logger',
            'apd-health-check'
        );

        foreach ($services as $service) {
            $file = APD_PLUGIN_PATH . 'includes/class-' . $service . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }
    }
}

// Register autoloader
APD_Autoloader::register();
