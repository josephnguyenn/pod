<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $wpdb;

if( !defined('FPD_PLUGIN_DIR') )
    define( 'FPD_PLUGIN_DIR', dirname(__FILE__) );

if( !defined('FPD_PLUGIN_ROOT_PHP') )
    define( 'FPD_PLUGIN_ROOT_PHP', dirname(__FILE__).'/'.basename(__FILE__)  );

if( !defined('FPD_PLUGIN_ADMIN_DIR') )
    define( 'FPD_PLUGIN_ADMIN_DIR', dirname(__FILE__) . '/admin' );

if( !defined('FPD_WP_CONTENT_DIR') )
	define( 'FPD_WP_CONTENT_DIR', str_replace('\\', '/', WP_CONTENT_DIR) );

if( !defined('FPD_ORDER_DIR') )
    define( 'FPD_ORDER_DIR', FPD_WP_CONTENT_DIR . '/fancy_products_orders/' );

if( !defined('FPD_TEMP_DIR') )
    define( 'FPD_TEMP_DIR', WP_CONTENT_DIR.'/_fpd_temp/' );

if( !defined('FPD_FONTS_DIR') )
    define( 'FPD_FONTS_DIR', FPD_WP_CONTENT_DIR.'/uploads/fpd_fonts/' );

if( !defined('FPD_CATEGORIES_TABLE') )
    define( 'FPD_CATEGORIES_TABLE', $wpdb->prefix . 'fpd_categories' );

if( !defined('FPD_PRODUCTS_TABLE') )
    define( 'FPD_PRODUCTS_TABLE', $wpdb->prefix . 'fpd_products' );

if( !defined('FPD_CATEGORY_PRODUCTS_REL_TABLE') )
    define( 'FPD_CATEGORY_PRODUCTS_REL_TABLE', $wpdb->prefix . 'fpd_category_products_rel' );

if( !defined('FPD_VIEWS_TABLE') )
    define( 'FPD_VIEWS_TABLE', $wpdb->prefix . 'fpd_views' );

if( !defined('FPD_TEMPLATES_TABLE') )
    define( 'FPD_TEMPLATES_TABLE', $wpdb->prefix . 'fpd_templates' );

if( !defined('FPD_ORDERS_TABLE') )
    define( 'FPD_ORDERS_TABLE', $wpdb->prefix . 'fpd_orders' );

if( !defined('FPD_DESIGNS_TABLE') )
    define( 'FPD_DESIGNS_TABLE', $wpdb->prefix . 'fpd_designs' );

if( !defined('FPD_PRINT_JOBS_TABLE') )
    define( 'FPD_PRINT_JOBS_TABLE', $wpdb->prefix . 'fpd_print_jobs' );

$GLOBALS['FPD_GENIUS_URL'] = 'https://fpd-service-router-dcbb17b083ce.herokuapp.com/api/';
$GLOBALS['FPD_GENIUS_DISABLE_MANAGE_LICENSE'] = false;
$GLOBALS['FPD_GENIUS_LICENSE_KEY'] = '';

?>