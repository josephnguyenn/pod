<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<?php

// ==================================================================
// output the values into the the page or input in the correct way
// allowing to have double and single quotes inside input
// ==================================================================

function yydev_tagmanager_html_output($output_code) {

    $output_code = stripslashes_deep($output_code);
    $output_code = esc_html($output_code);
    return $output_code;

} // function yydev_redirect_html_output($output_code) {

// ==================================================================
// This function create a content in mysql that will fit the database and will ignore marks like ("", \, =)
// ==================================================================

function yydev_tagmanager_mysql_prep( $value ) {

        if( !empty($value) && !is_array($value) ) {
            $value = trim($value);            
        } else { // if( !empty($value) && !is_array($value) ) {
            $value = "";
        } // if( !empty($value) && !is_array($value) ) {

        return $value;
} // function yydev_tagmanager_mysql_prep( $value ) {

// ==================================================================
// This function will display error message if there was something wrong
// $error_message will be the name of the string we define and if it's exists
// it will echo the message to the page
// if $display_inline is set to 1 it will have style of display: inline
// ==================================================================

function yydev_tagmanager_show_error_message($error_message, $display_inline = "") {
    
    if($display_inline == 1) {
        $display_inline_echo = "display-inline";
    } // if($display_inline == 1) {
    
    if( isset($error_message) ) {
        ?>
        
        <div class="output-data-error-message <?php echo $display_inline_echo; ?>">
            <?php echo $error_message; ?>
        </div>
        
        <?php
    } // if( isset($error) ) {
    
} // function yydev_tagmanager_show_error_message($error) {


// ================================================
// Echoing Message if it's exists 
// ================================================

function yydev_tagmanager_echo_message_if_exists() {
    
    if(isset($_GET['message'])) {
        echo "<div class='output-messsage'> " . esc_html($_GET['message']) . " </div>";
    } // if(isset($_GET['message'])) {
    
    if(isset($_GET['error-message'])) {
        echo "<div class='error-messsage'><b>Error:</b> " .  esc_html($_GET['error-message']) . " </div>";
    } // if(isset($_GET['error-message'])) {

} // function yydev_tagmanager_echo_message_if_exists() {


function yydev_tagmanager_echo_success_message_if_exists($success) {

    if(isset($success) && !empty($success) ) {
        echo "<div class='output-messsage'> " . esc_html($success) . " </div>";
    } // if(isset($success) && !empty($success) ) {

} // function yydev_tagmanager_echo_success_message_if_exists($success) {

function yydev_tagmanager_echo_error_message_if_exists($error) {

    if(isset($error) && !empty($error) ) {
        echo "<div class='error-messsage'><b>Error:</b> " .  $error . " </div>";
    } // if(isset($_GET['error-message'])) {

} // function yydev_tagmanager_echo_error_message_if_exists() {

// ==================================================================
// redirect the page using the path you provided
// ==================================================================

function yydev_tagmanager_redirections_page($link) {
	header("Location: {$link}");
	exit;
} // function yydev_tagmanager_redirections_page($path) {

// ==================================================================
// redirect the page using the path you provided
// ==================================================================

function yydev_tagmanager_get_plugin_settings($wp_options_name) {

    $plugin_data_array = [];
    $getting_plugin_data = get_option($wp_options_name);

    if( !empty($getting_plugin_data) ) {

        // ----------------------------------------------
        // breaking the string into to 2 variables. the array namd and vakue  
        // ----------------------------------------------  

        $break_array = explode("***", $getting_plugin_data);

        $item_name = explode("####", $break_array[0]);
        $key_name = explode("####", $break_array[1]);

        $array_count = count($key_name);

        // ----------------------------------------------
        // creating an organized array with all values
        // ----------------------------------------------      

        for($count_number = 0; $count_number < $array_count; $count_number++) {
        	$plugin_data_array[ $item_name[$count_number] ] = $key_name[$count_number];
        } // for($count_number = 0; $count_number < $array_count; $count_number++) {

    } // if( !empty($getting_plugin_data) ) {

    return $plugin_data_array;

} // function yydev_tagmanager_get_plugin_settings($wp_options_name) {

// ==================================================================
// Cehcking if the checkbox is already set
// ==================================================================

function yydev_tagmanager_checkbox_isset($post_value) {
    
    $checkbox_value = '';

    if( isset( $_POST[$post_value] ) ) {
        $checkbox_value = intval($_POST[$post_value]);
    } // if( isset( $_POST[$post_value] ) ) {

    return $checkbox_value;
    
} // function yydev_tagmanager_checkbox_isset($error) {

// ================================================
// Echoing Message if it's exists 
// ================================================

function yydev_tagmanager_find_page_id() {

    global $wpdb;
    $post_id_num = 0;

    // in case of page or blog post
    if( is_single() || is_page() ) {		
    	$post_id_num = get_the_ID();
    } // if( is_single() || is_page() ) {

    // in case of static home page
    if( is_home() ) {
    	$blog_page_id = get_option( 'page_for_posts' );

    	// incase the blog is on the home page
    	if( !empty($blog_page_id) ) {
    		$post_id_num = $blog_page_id;
    	} // if( $blog_page_id ) {

    } // if( !empty($blog_page_id) ) {

    // if the page id is still empty
    if( !empty($post_id_num) ) {
        global $post;
        $post_id_num = $post->ID;
    }


    return intval($post_id_num);

} // function yydev_tagmanager_find_page_id() {

// ================================================
// Getting all the user settings and checking
// if we should output the code to the page or not
// $yydev_tagmanager_settings = the plugin settings
// ================================================

function yydev_tagmanager_exclude_pages_check($yydev_tagmanager_settings) {

    $exclude_code = 1;
    $users_settings = $yydev_tagmanager_settings;
    global $wpdb;

    // --------------------------------------------------------
    // checking if we should not load plugin via user role
    // --------------------------------------------------------

    $exclude_users = $users_settings['exclude_users'];

    // incase we exclude code when admin loged in
    if( function_exists('current_user_can') ) {

        if( current_user_can('administrator') && ($exclude_users === 'exclude_admin') ) {
            $exclude_code = 0;
        } // if( current_user_can('administrator') && ($exclude_users === 'exclude_admin') ) {
        
    } // if( function_exists('current_user_can') ) {

    // incase we exclude all users
    if( is_user_logged_in() && ($exclude_users === 'exclude_all_users') ) {
        $exclude_code = 0;
    } // if( is_user_logged_in() && ($exclude_users === 'exclude_all_users') ) {

    // --------------------------------------------------------
    // checking if we should not load plugin via page id
    // --------------------------------------------------------

    $exclude_pages_option = $users_settings['exclude_option'];
    $exclude_pages_ids = $users_settings['exclude_ids'];
    $page_id = yydev_tagmanager_find_page_id();

    // --------------------------------------
    // creating an array with all the ids
    // --------------------------------------
    $exclude_ids_array = [];
    $exclude_ids_explode = explode( ',', $exclude_pages_ids);

    foreach($exclude_ids_explode as $exclude_id) {

        $exclude_id = intval( trim($exclude_id) );

        if( !empty($exclude_id) ) {
            $exclude_ids_array[] = $exclude_id;
        } // if( !empty($exclude_id) ) {

    } // foreach($exclude_ids_explode as $exclude_id) {

    // --------------------------------------
    // incase we exclude pages
    // --------------------------------------

    if( $exclude_pages_option === 'exclude' ) {

        // incase we choose to exclude an id
        if( in_array( $page_id, $exclude_ids_array) ) {
            $exclude_code = 0;
        } // if( in_array( $page_id, $exclude_ids_array) ) {

    } // if( $exclude_pages_option === 'exclude' ) {

    // --------------------------------------
    // incase we exclude pages
    // --------------------------------------
    
    if( $exclude_pages_option === 'include' ) {

        // incase we choose to include only on some pages
        if( !in_array( $page_id, $exclude_ids_array) ) {
            $exclude_code = 0;
        } // if( !in_array( $page_id, $exclude_ids_array) ) {

    } // if( $exclude_pages_option === 'exclude' ) {

    return $exclude_code;

} // function yydev_tagmanager_exclude_pages_check($yydev_tagmanager_settings) {

// ================================================
// Check what the lazyload time we need to have
// and exclude it based on the top lazy load option
// ================================================

function yydev_tagmanager_get_lazy_load_time($yydev_tagmanager_settings) {

    $exclude_code = 1;
    $users_settings = $yydev_tagmanager_settings;
    global $wpdb;

    // --------------------------------------------------------
    // checking if we should not load plugin via page id
    // --------------------------------------------------------

    $lazy_load_time = intval($users_settings['lazy_load_time']);
    $no_lazy_load_wait_pages = $users_settings['no_lazy_load_wait_pages'];
    $page_id = yydev_tagmanager_find_page_id();

    // --------------------------------------
    // creating an array with all the ids
    // --------------------------------------
    $exclude_ids_array = [];
    $no_lazy_load_wait_pages_array = explode( ',', $no_lazy_load_wait_pages);

    foreach($no_lazy_load_wait_pages_array as $exclude_id) {

        $exclude_id = intval( trim($exclude_id) );

        if( !empty($exclude_id) ) {
            $exclude_ids_array[] = $exclude_id;
        } // if( !empty($exclude_id) ) {

    } // foreach($exclude_ids_explode as $exclude_id) {

    // --------------------------------------
    // incase we exclude pages
    // --------------------------------------

    // incase we choose to exclude an id
    if( in_array( $page_id, $exclude_ids_array) ) {
        $lazy_load_time = 0;
    } // if( in_array( $page_id, $exclude_ids_array) ) {

    // --------------------------------------
    // return the lazy load time
    // --------------------------------------

    return $lazy_load_time;

} // function yydev_tagmanager_exclude_pages_check($yydev_tagmanager_settings) {