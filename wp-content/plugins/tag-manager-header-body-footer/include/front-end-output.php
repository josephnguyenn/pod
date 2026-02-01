<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<?php

// ====================================================
// creating actions that will allow us to to load the code directly
// ====================================================

function yydev_tag_manager_head() {
    do_action( 'yydev_tag_manager_head' );
} // function yydev_above_footer() {

function yydev_tag_manager_below_body() {
    do_action( 'yydev_tag_manager_below_body' );
} // function yydev_tag_manager_below_body() {

function yydev_tag_manager_before_closing_body() {
    do_action( 'yydev_tag_manager_before_closing_body' );
} // function yydev_tag_manager_before_end_body() {

// ====================================================
// Output all the tags into the site
// ====================================================

// getting the data from the databse
$tagmanager_content_output = $wpdb->get_results("SELECT * FROM " . $yydev_tags_table_name . " WHERE page_id = 0");
$yydev_tag_manager_head = ''; $yydev_tag_body_tag = ''; $yydev_tag_footer_tag = '';

if($tagmanager_content_output > 0) {

    // organize the tags data
    foreach($tagmanager_content_output as $tagmanager_content_output_data) {

        $tag_type = $tagmanager_content_output_data->tag_type;
        $tag_code = $tagmanager_content_output_data->tag_code;

        if( $tag_type === "head" ) {

            // converting the array to data
            $yydev_tag_manager_head = explode("###^^^", $tagmanager_content_output_data->tag_code);
            $yydev_tag_manager_head = implode("\n", $yydev_tag_manager_head);
            $yydev_tag_manager_head = stripslashes_deep($yydev_tag_manager_head);

        } elseif( $tag_type === "body" ) {

            $yydev_tag_body_tag = stripslashes_deep($tagmanager_content_output_data->tag_code);

            $yydev_tag_body_tag = explode("###^^^", $tagmanager_content_output_data->tag_code);
            $yydev_tag_body_tag = implode("\n", $yydev_tag_body_tag);
            $yydev_tag_body_tag = stripslashes_deep($yydev_tag_body_tag);

        } elseif( $tag_type === "footer" ) {

            $yydev_tag_footer_tag = explode("###^^^", $tagmanager_content_output_data->tag_code);
            $yydev_tag_footer_tag = implode("\n", $yydev_tag_footer_tag);
            $yydev_tag_footer_tag = stripslashes_deep($yydev_tag_footer_tag);

        } // if( $tag_type === "head" ) {

    } // foreach($tagmanager_content_output as $tagmanager_content_output_data) {

} // if($tagmanager_content_output > 0) {

// ===========================================================================================
// Getting lazy loading tags and add them to the head
// ===========================================================================================

    $plugin_data_array = yydev_tagmanager_get_plugin_settings($wp_options_name);

    $yy_google_analytics_id = sanitize_text_field($plugin_data_array['google_analytics_id']);
    $yandex_metrika_id = sanitize_text_field($plugin_data_array['yandex_metrika_id']);
    $facebook_pixel_id = sanitize_text_field($plugin_data_array['facebook_pixel_id']);
    $google_tag_manager_id = sanitize_text_field($plugin_data_array['google_tag_manager_id']);

    $custom_lazy_load_js = $plugin_data_array['custom_lazy_load_js'];
    $remove_custom_lazy_load_js_on_elementor = intval($plugin_data_array['remove_custom_lazy_load_js_on_elementor']);

    $wp_body_open = intval($plugin_data_array['wp_body_open']);


    // -------------------------------------
    // Starting with google analytics
    // -------------------------------------

        $header_lazyload_code = "";
        $body_lazyload_code = "";

        if( !empty($yy_google_analytics_id) ||  !empty($yandex_metrika_id) ) {

            $header_lazyload_code .= "<script>";

            	$header_lazyload_code .= "function yydev_tagmanager_js_lazy_load() {";

                    // -------------------------------------
                    // getting the analytics code output
                    // -------------------------------------

                    if( !empty($yy_google_analytics_id) ) {
 
                        $header_lazyload_code .= "var YY_analytics_TAG = document.createElement('script');";
                        $header_lazyload_code .= "YY_analytics_TAG.src = 'https://www.googletagmanager.com/gtag/js?id=" . $yy_google_analytics_id . "';";
                        $header_lazyload_code .= "var first_analytics_ScriptTag = document.getElementsByTagName('script')[0];";
                        $header_lazyload_code .= "first_analytics_ScriptTag.parentNode.insertBefore(YY_analytics_TAG, first_analytics_ScriptTag);";

                        $header_lazyload_code .= "window.dataLayer = window.dataLayer || [];";
                        $header_lazyload_code .= "function gtag(){dataLayer.push(arguments);}";
                        $header_lazyload_code .= "gtag('js', new Date());";
                        $header_lazyload_code .= "gtag('config', '" . $yy_google_analytics_id . "');";
                        
                    } // if( !empty($yy_google_analytics_id) ) {

                    // -------------------------------------
                    // getting the facebook pixel output to the page
                    // -------------------------------------

                    if( !empty($facebook_pixel_id) ) {

                        $header_lazyload_code .= "!function(f,b,e,v,n,t,s)";
                        $header_lazyload_code .= "{if(f.fbq)return;n=f.fbq=function(){n.callMethod?";
                        $header_lazyload_code .= "n.callMethod.apply(n,arguments):n.queue.push(arguments)};";
                        $header_lazyload_code .= "if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';";
                        $header_lazyload_code .= "n.queue=[];t=b.createElement(e);t.async=!0;";
                        $header_lazyload_code .= "t.src=v;s=b.getElementsByTagName(e)[0];";
                        $header_lazyload_code .= "s.parentNode.insertBefore(t,s)}(window, document,'script',";
                        $header_lazyload_code .= "'https://connect.facebook.net/en_US/fbevents.js');";

                        $header_lazyload_code .= "fbq('init', '" . $facebook_pixel_id . "');";
                        $header_lazyload_code .= "fbq('track', 'PageView');";

                    } // if( !empty($facebook_pixel_id) ) {

                    // -------------------------------------
                    // getting the google tag manager output to the page
                    // -------------------------------------

                    if( !empty($google_tag_manager_id) ) {

                        $header_lazyload_code .= "var gtmScript = document.createElement('script');";
                        $header_lazyload_code .= "gtmScript.type = 'text/javascript';";
                        $header_lazyload_code .= "gtmScript.async = true;";
                        $header_lazyload_code .= "gtmScript.src = 'https://www.googletagmanager.com/gtm.js?id=" . $google_tag_manager_id . "';";
                        $header_lazyload_code .= "var firstScript = document.getElementsByTagName('script')[0];";
                        $header_lazyload_code .= "firstScript.parentNode.insertBefore(gtmScript, firstScript);";

                    } // if( !empty($google_tag_manager_id) ) {

                    // -------------------------------------
                    // getting the yandex metrika code output
                    // -------------------------------------

                    if( !empty($yandex_metrika_id) ) {
                    
                		$header_lazyload_code .= "(function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};";
                        $header_lazyload_code .= "m[i].l=1*new Date();k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})";
                        $header_lazyload_code .= "(window, document, 'script', 'https://mc.yandex.ru/metrika/tag.js', 'ym');";

                        $header_lazyload_code .= "ym(" . $yandex_metrika_id . ", 'init', {";
                            $header_lazyload_code .= "clickmap:true,";
                            $header_lazyload_code .= "trackLinks:true,";
                            $header_lazyload_code .= "accurateTrackBounce:true,";
                            $header_lazyload_code .= "webvisor:true";
                        $header_lazyload_code .= "});";
                        
                    } // if( !empty($yandex_metrika_id) ) {

                    // -------------------------------------
                    // loaindg custom lazy load javascript code
                    // -------------------------------------

                    if( !empty($custom_lazy_load_js) ) {

                            $output_custom_lazy_js = 1;

                            // making sure this is not elementor editor
                            if( $remove_custom_lazy_load_js_on_elementor == 1 ) {

                                // return website.com/page/?elementor-preview=4386
                                $request_url =  $_SERVER['REQUEST_URI'];
                                if( strstr($request_url, 'elementor-preview') ) {
                                    $output_custom_lazy_js = 0;
                                }

                            } // 

                            // output custom lazy load js to page
                            if( $output_custom_lazy_js == 1 ) {

                                $yydev_custom_lazy_load_data = explode("###^^^", $custom_lazy_load_js);
                                $yydev_custom_lazy_load_data = implode("\n", $yydev_custom_lazy_load_data);
                                $yydev_custom_lazy_load_data = stripslashes_deep($yydev_custom_lazy_load_data);

                                $header_lazyload_code .= $yydev_custom_lazy_load_data;

                            } // if( $output_custom_lazy_js == 1 ) {

                    } // if( !empty($custom_lazy_load_js) ) {

                    // -------------------------------------
                    // stop the function from running again
                    // -------------------------------------

                    $header_lazyload_code .= "yydev_tagmanager_stop = 1;";

            	$header_lazyload_code .= "}"; // // function youtube_replace_function() {

                // -------------------------------------
                // making sure to run the script once
                // -------------------------------------

        		$header_lazyload_code .= "var yydev_tagmanager_stop = 0;";

                // -------------------------------------
                // run the function after 5 sec from page loading
                // -------------------------------------
                
                // run the function after 5 sec
            	$header_lazyload_code .= "document.addEventListener('DOMContentLoaded', function(event) {";
                		$header_lazyload_code .= "setTimeout(run_yydev_tagmanager_lazy_load, {lazy_load_time_replace});";
            	$header_lazyload_code .= "});"; // document.addEventListener('DOMContentLoaded', function(event) {

                // make sure the function didn't run on scroll and if it didn't run it now
            	$header_lazyload_code .= "function run_yydev_tagmanager_lazy_load() {";

                    $header_lazyload_code .= "if(yydev_tagmanager_stop == 0) {";
                        $header_lazyload_code .= "yydev_tagmanager_js_lazy_load();";
                    $header_lazyload_code .= "}"; // if(yydev_tagmanager_stop == 0) {

            	$header_lazyload_code .= "}"; // $header_lazyload_code .= "function run_yydev_tagmanager_lazy_load() {";

                // -------------------------------------
                // run the function as well when the user scroll in the first time
                // -------------------------------------

                $header_lazyload_code .= "window.addEventListener('scroll', function(e) {";

            		$header_lazyload_code .= "if( this.scrollY > 10 && yydev_tagmanager_stop == 0) {";
            		    	$header_lazyload_code .= "yydev_tagmanager_js_lazy_load();";
            		$header_lazyload_code .= "}"; // if( this.scrollY > 10 && yydev_tagmanager_stop == 0) {

                $header_lazyload_code .= "});"; // window.addEventListener('scroll', function(e) {

                // -------------------------------------
                // run the function as well when the user scroll in the first time
                // -------------------------------------

                $header_lazyload_code .= "document.addEventListener('DOMContentLoaded', function() {";
                    $header_lazyload_code .= "document.body.addEventListener('mouseup', yydev_run_event_lazyload);";
                    $header_lazyload_code .= "document.body.addEventListener('mousedown', yydev_run_event_lazyload);";
                    $header_lazyload_code .= "document.body.addEventListener('click', yydev_run_event_lazyload);";
                    $header_lazyload_code .= "document.body.addEventListener('mousemove', yydev_run_event_lazyload);";
                    $header_lazyload_code .= "document.body.addEventListener('keypress', yydev_run_event_lazyload);";
                $header_lazyload_code .= "});";

                $header_lazyload_code .= "function yydev_run_event_lazyload() {";
                    $header_lazyload_code .= "if (typeof yydev_tagmanager_stop !== 'undefined' && yydev_tagmanager_stop === 0) {";
                        $header_lazyload_code .= "yydev_tagmanager_js_lazy_load();";
                    $header_lazyload_code .= "}";
                $header_lazyload_code .= "}";

            $header_lazyload_code .= "</script>";

            // -------------------------------------
            // adding noscript code of yandex
            // -------------------------------------

            if( !empty($yandex_metrika_id) ) {
                $header_lazyload_code .= "<noscript><div><img src='https://mc.yandex.ru/watch/" . $yandex_metrika_id . "' style='position:absolute; left:-9999px;' alt='' /></div></noscript>";
            } // if( !empty($yandex_metrika_id) ) {

            // -------------------------------------
            // adding noscript code of facebook
            // -------------------------------------

            if( !empty($facebook_pixel_id) ) {
                $header_lazyload_code .= "<noscript><img height='1' width='1' style='display:none' src='https://www.facebook.com/tr?id=" . $facebook_pixel_id . "&ev=PageView&noscript=1' /></noscript>";
            } // if( !empty($facebook_pixel_id) ) {

        } // if( !empty($yy_google_analytics_id) ||  !empty($yandex_metrika_id) ) {

    // -------------------------------------
    // Add the code into the header code
    // -------------------------------------

    $yydev_tag_manager_head = $header_lazyload_code . $yydev_tag_manager_head;

// ====================================================
// Adding the <head> tags
// ====================================================

if(!empty($yydev_tag_manager_head)) {

    // ------------------------------------------------
    // loading the code above footer with wp_head()
    // ------------------------------------------------

    add_action('wp_head', function() use($yydev_tag_manager_head, $yydev_tagmanager_settings) {

        yydev_tagmanager_exclude_pages_check($yydev_tagmanager_settings);

        if( yydev_tagmanager_exclude_pages_check($yydev_tagmanager_settings) ) {

            $lazy_load_time = yydev_tagmanager_get_lazy_load_time($yydev_tagmanager_settings);
            $yydev_tag_manager_head = str_replace('{lazy_load_time_replace}', $lazy_load_time, $yydev_tag_manager_head);
            echo "\n" . $yydev_tag_manager_head . "\n";

        } // if( yydev_tagmanager_exclude_pages_check($yydev_tagmanager_settings) ) {

     }, 9999); // add_action('wp_head', function() use($yydev_tag_manager_head, $yydev_tagmanager_settings) {

    // ------------------------------------------------
    // loading with direct plugin tag
    // ------------------------------------------------
    
    add_action('yydev_tag_manager_head', function() use($yydev_tag_manager_head, $yydev_tagmanager_settings) {

        if( yydev_tagmanager_exclude_pages_check($yydev_tagmanager_settings) ) {

            $lazy_load_time = yydev_tagmanager_get_lazy_load_time($yydev_tagmanager_settings);
            $yydev_tag_manager_head = str_replace('{lazy_load_time_replace}', $lazy_load_time, $yydev_tag_manager_head);
            echo "\n" . $yydev_tag_manager_head . "\n";

        } // if( yydev_tagmanager_exclude_pages_check($yydev_tagmanager_settings) ) {

     }, 9999); // add_action('wp_head', function() use($yydev_tag_manager_head, $yydev_tagmanager_settings) {

} // if(!empty($yydev_tag_manager_head)) {



// ===========================================================================================
// Getting lazy loading tags and add them to the after body tag
// ===========================================================================================

    // -------------------------------------
    // getting the google tag manager to the body tag
    // -------------------------------------

    if( !empty($google_tag_manager_id) ) {
        $body_lazyload_code .= "<!-- Google Tag Manager (noscript) -->";
        $body_lazyload_code .= "<noscript><iframe src='https://www.googletagmanager.com/ns.html?id=" . $google_tag_manager_id . "'";
        $body_lazyload_code .= "height='0' width='0' style='display:none;visibility:hidden'></iframe></noscript>";
        $body_lazyload_code .= "<!-- End Google Tag Manager (noscript) -->";
    } // if( !empty($google_tag_manager_id) ) {

// ====================================================
// Adding the after <body> tags
// ====================================================

// -------------------------------------
// Add the code into the header code
// -------------------------------------

$yydev_tag_body_tag = $body_lazyload_code . $yydev_tag_body_tag;

// ----------------------------------------------------
// Adding the code if the theme not support after body tags
// ----------------------------------------------------

if(!empty($yydev_tag_body_tag)) {

    // ------------------------------------------------
    // loading the code above below <body> using ob_start
    // ------------------------------------------------

    // checking which way we should load the code with
    if( $wp_body_open == 1 ) {

        // ------------------------------------------------
        // loading the below body code using wp_body_open()
        // ------------------------------------------------

        add_action('wp_body_open', function() use($yydev_tag_body_tag, $yydev_tagmanager_settings) {

            if( yydev_tagmanager_exclude_pages_check($yydev_tagmanager_settings) ) {
                echo "\n" . $yydev_tag_body_tag . "\n";
            } // if( yydev_tagmanager_exclude_pages_check($yydev_tagmanager_settings) ) {

         }, 1); // add_action('wp_head', function() use($yydev_tag_body_tag, $yydev_tagmanager_settings) {
         
    } else { // if( $wp_body_open == 1 ) {

        // ------------------------------------------------
        // loading the code above below <body> using ob_start
        // ------------------------------------------------

        add_action('wp_head', function() {
            ob_start();
         }); // add_action('wp_head', function() {

        add_action( 'wp_footer', function() use($yydev_tag_body_tag, $yydev_tagmanager_settings) {

            if( yydev_tagmanager_exclude_pages_check($yydev_tagmanager_settings) ) {

                $yydev_buffer  = ob_get_clean();
                $yydev_str_replace ='/<[bB][oO][dD][yY]\s[A-Za-z]{2,5}[A-Za-z0-9 "_=\-\.]+>|<body>/';
                ob_start();

                if( preg_match($yydev_str_replace, $yydev_buffer, $yydev_buffer_return) ) {

                    $yydev_new_code = $yydev_buffer_return[0] . $yydev_tag_body_tag;
                    $yydev_new_code = preg_replace($yydev_str_replace, $yydev_new_code, $yydev_buffer);
                    
                    echo $yydev_new_code;

                } // if( preg_match($yydev_str_replace, $yydev_buffer, $yydev_buffer_return) ) {

                ob_flush();

            } // if( yydev_tagmanager_exclude_pages_check($yydev_tagmanager_settings) ) {

        }); // add_action( 'wp_footer', function() use($yydev_tag_body_ta, $yydev_tagmanager_settingsg) {

    } // } else { // if( intval(get_option('yydev_tag_mangage_wp_body_open')) == 1 ) {

    // ------------------------------------------------
    // loading with direct plugin tag
    // ------------------------------------------------

    add_action('yydev_tag_manager_below_body', function() use($yydev_tag_body_tag, $yydev_tagmanager_settings) {

        if( yydev_tagmanager_exclude_pages_check($yydev_tagmanager_settings) ) {
            echo "\n" . $yydev_tag_body_tag . "\n";
        } // if( yydev_tagmanager_exclude_pages_check($yydev_tagmanager_settings) ) {

     }, 9999); // add_action('wp_head', function() use($yydev_tag_body_tag, $yydev_tagmanager_settings) {

} // if(!empty($yydev_tag_body_tag)) {

// ====================================================
// Adding the footer tags
// ====================================================

if(!empty($yydev_tag_footer_tag)) {

    // ------------------------------------------------
    // loading the code above footer with wp_footer()
    // ------------------------------------------------
    
    add_action('wp_footer', function() use($yydev_tag_footer_tag, $yydev_tagmanager_settings) {

        if( yydev_tagmanager_exclude_pages_check($yydev_tagmanager_settings) ) {
            echo "\n" . $yydev_tag_footer_tag . "\n";
        } // if( yydev_tagmanager_exclude_pages_check($yydev_tagmanager_settings) ) {

     }, 9999); // add_action('wp_footer', function() use($yydev_tag_footer_tag, $yydev_tagmanager_settings) {

    // ------------------------------------------------
    // loading with direct plugin tag
    // ------------------------------------------------
    
    add_action('yydev_tag_manager_before_closing_body', function() use($yydev_tag_footer_tag, $yydev_tagmanager_settings) {
        
        if( yydev_tagmanager_exclude_pages_check($yydev_tagmanager_settings) ) {
            echo "\n" . $yydev_tag_footer_tag . "\n";
        } // if( yydev_tagmanager_exclude_pages_check($yydev_tagmanager_settings) ) {

     }, 9999); // add_action('wp_head', function() use($yydev_tag_footer_tag, $yydev_tagmanager_settings) {

} // if(!empty($yydev_tag_footer_tag)) {
