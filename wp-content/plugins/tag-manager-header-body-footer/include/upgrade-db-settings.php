<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<?php

// ----------------------------------------------
// Include the settings.php file
// ---------------------------------------------

include( dirname(__FILE__) . '/settings.php' ); // Load the files to get the databse info

// ============================================================================================
// getting all the theme settings data from the database
// ============================================================================================    

// $getting_theme_data loaded from function.php
$getting_theme_data_upgrade = get_option($wp_options_name);

if( !empty($getting_theme_data_upgrade) ) {
    $detabase_tag_array = yydev_tagmanager_get_plugin_settings($wp_options_name);
} // if( !empty($getting_theme_data_upgrade) ) {

// ============================================================================================
// getting the array data from the install.php file so we can compare the 2 different arrays
// ============================================================================================    

if( !empty($plugin_data_array) ) {
    $install_plugin_array = $plugin_data_array;
} // if( !empty($if( !empty($plugin_data_array) ) {

// ============================================================================================
// compare the 2 different arrays
// ============================================================================================

if( isset($install_plugin_array) && isset($detabase_tag_array) ) {

    // ---------------------------------------------------
    // getting the info from the install array and make sure it exists on the database array
    // ---------------------------------------------------

    foreach($install_plugin_array as $key => $value) {

        $key_search = "#" . $key . "#";
        $key_search2 = "#" . $key . "*";

        // checking if the key exists on the database value
        if( !strstr($getting_theme_data_upgrade, $key_search) && !strstr($getting_theme_data_upgrade, $key_search2) ) {
            $detabase_tag_array[$key] =  $value;
        } // if( !strstr($getting_theme_data_upgrade, $key_search) && !strstr($getting_theme_data_upgrade, $key_search2) ) {{

    } // if( !strstr($getting_theme_data_upgrade, $key_search) || !strstr($getting_theme_data_upgrade, $key_search2) ) {

    // ----------------------------------------------
    // creating a value with all the array data
    // ----------------------------------------------  

    $array_key_name = "";
    $array_item_value = "";

    foreach($detabase_tag_array as $key=>$item) {
        $array_key_name .= "####" . $key;
    	$array_item_value .= "####" . $item;
    } // foreach($detabase_tag_array as $key=>$item) {

    $new_child_database_data = $array_key_name . "***" . $array_item_value;

    // ----------------------------------------------
    // update the data with the changes
    // ---------------------------------------------- 

    update_option($wp_options_name, $new_child_database_data);

} // if( isset($install_child_data_array) && isset($detabase_tag_array) ) {