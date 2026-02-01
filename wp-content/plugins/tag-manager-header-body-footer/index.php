<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<?php
/*
Plugin Name: YYDevelopment - Tag Manager - Header, Body And Footer
Plugin URI:  https://www.yydevelopment.com/yydevelopment-wordpress-plugins/
Description: Simple plugin that allow you add head, body and footer codes for google tag manager, analytics & facebook pixel codes.
Version:     3.6.2
Author:      YYDevelopment
Author URI:  https://www.yydevelopment.com/
Text Domain: tag-manager-header-body-footer
Domain Path: /languages
*/

include_once( dirname(__FILE__) . '/include/settings.php' );
require_once( dirname(__FILE__) . '/include/functions.php' );

// ================================================
// Adding lanagues support to the plugin
// ================================================

function tag_manager_header_body_footer_languages() {
    load_plugin_textdomain( 'tag-manager-header-body-footer', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}

add_action( 'plugins_loaded', 'tag_manager_header_body_footer_languages' );

// ================================================
// update the database on plugin update
// ================================================

$yydev_tag_manager_data_plugin_version = '3.5.0'; // plugin version
$yydev_tag_manager_data_slug_name = 'yydev_tag_manager_version'; // the name we save on the wp_options database
$db_plugin_version = get_option($yydev_tag_manager_data_slug_name);

// checking if the plugin version exists on the dabase
// and checking if the database version equal to the plugin version $yydev_tag_manager_data_plugin_version
// if( empty($db_plugin_version) || ($yydev_tag_manager_data_plugin_version != $db_plugin_version) ) {

    // update the plugin database if it's required
    $yydev_tag_manager_database_update = 1;
    require_once( dirname(__FILE__) . '/include/install.php' );

    // update the plugin version in the database
    update_option($yydev_tag_manager_data_slug_name, $yydev_tag_manager_data_plugin_version);

// } // if( empty($db_plugin_version) || ($yydev_tag_manager_data_plugin_version != $db_plugin_version) ) {

// ================================================
// Get all the data and ouput it into the page
// ================================================

$yydev_tagmanager_settings = yydev_tagmanager_get_plugin_settings($wp_options_name);

// ================================================
// Creating Database when the plugin is activated
// ================================================

function yydev_tagmanager_create_database() {
    
    require_once( dirname(__FILE__) . '/include/install.php' );
        
} // function yydev_tagmanager_create_database() {

register_activation_hook(__FILE__, 'yydev_tagmanager_create_database');

// ================================================
// display the plugin we have create on the wordpress post blog and pages
// ================================================

// function that will output the code to the page
function output_yydev_tagmanager() {

    include( dirname(__FILE__) . '/include/style.php' );
    include( dirname(__FILE__) . '/include/script.php' );
    include( dirname(__FILE__) . '/include/admin-output.php' );

} // function output_yydev_tagmanager() {

// ================================================
// choosing if the load from the main page or under the settings
// ================================================

if( intval($yydev_tagmanager_settings['add_plugin_to_settings']) == 1 ) {

    // in case of settings menu loading
    function register_yydev_tagmanager_page() {
        add_options_page( __('Tag Manager', 'tag-manager-header-body-footer'), __('Tag Manager', 'tag-manager-header-body-footer'), 'manage_options', 'yydev-tag-manager', 'output_yydev_tagmanager');
    } // function register_yydev_tagmanager_page() {

    add_action('admin_menu', 'register_yydev_tagmanager_page');

} else { // if( intval($yydev_tagmanager_settings['add_plugin_to_settings']) == 1 ) {

    // in case of main menu loading
    function register_yydev_tagmanager_page() {
        $wordpress_icon_path = plugins_url( 'images/favicon.png', __FILE__ );
        add_menu_page( __('Tag Manager', 'tag-manager-header-body-footer'), __('Tag Manager', 'tag-manager-header-body-footer'), 'manage_options', 'yydev-tag-manager', 'output_yydev_tagmanager',  $wordpress_icon_path, 500);
    } // function register_yydev_tagmanager_page() {

    add_action('admin_menu', 'register_yydev_tagmanager_page');

} // if( intval($yydev_tagmanager_settings['add_plugin_to_settings']) == 1 ) {

// ================================================
// Add settings page to the plugin menu info
// ================================================

function yydev_tagmanager_add_settings_link( $actions, $plugin_file ) {

	static $plugin;

    if (!isset($plugin)) { $plugin = plugin_basename(__FILE__); }
    if ($plugin == $plugin_file) {
        $admin_page_url = esc_url( menu_page_url( 'yydev-tag-manager', false ) );
        $settings = array('settings' => '<a href="' . $admin_page_url . '">Settings</a>');
        $donate = array('donate' => '<a target="_blank" href="https://www.yydevelopment.com/coffee-break/?plugin=tag-manager-header-body-footer">Donate</a>');
        $actions = array_merge($settings, $donate, $actions);
    } // if ($plugin == $plugin_file) {

    return $actions;

} //function yydev_tagmanager_add_settings_link( $actions, $plugin_file ) {

add_filter( 'plugin_action_links', 'yydev_tagmanager_add_settings_link', 10, 5 );

// ================================================
// output the data into the page front end
// we are loading the page after init to get user role
// ================================================

if( !is_admin() ) {
    include( dirname(__FILE__) . '/include/front-end-output.php' );
} // if( !is_admin() ) {

// ================================================
// including admin notices flie
// ================================================

if( is_admin() ) {
	include_once( dirname(__FILE__) . '/notices.php' );
} // if( is_admin() ) {