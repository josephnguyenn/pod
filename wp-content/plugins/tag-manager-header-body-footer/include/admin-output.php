<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<?php

$success_message = '';
$error_message = '';

// ====================================================
// Include the file that contains all the info
// ====================================================

include( dirname(__FILE__) . '/settings.php' );

// getting the page url for the settings page
$plugin_page_url = esc_url( menu_page_url( 'yydev-tag-manager', false ) );

// ================================================
// Get all the data and ouput it into the page
// ================================================

$plugin_data_array = yydev_tagmanager_get_plugin_settings($wp_options_name);

// ================================================
// Setting yandex and htaccess info
// ================================================

// getting domain base name website.com
$domain_base_name = preg_replace('/^www\./', '', $_SERVER['SERVER_NAME']);

// set the code message to the page
$add_code_to_htaccess_note = "#yydevelopment tag manager allow yandex metrica to use maps";
$add_code_to_htaccess = "Header always set X-Frame-Options 'ALLOW-FROM ^https?://(\/+.)?(" . $domain_base_name . "|webvisor.com)/'";

// getting current wp-config and adding the new line code
$wp_htaccess_path = ABSPATH . '.htaccess';
$tag_htaccess_edit = file_get_contents($wp_htaccess_path);

// ====================================================
// Update the data if it's changed
// ====================================================

if( isset($_POST['yydev_tagmanager_nonce']) ) {

    if( wp_verify_nonce($_POST['yydev_tagmanager_nonce'], 'yydev_tagmanager_action') ) {

        // If there is no error insert the info to the database

        // ------------------------------------------------
        // dealing with the head tags data
        // ------------------------------------------------

        $form_shortcode_head_post = $_POST['form_shortcode_head'];
        $count = count($form_shortcode_head_post);
        $form_shortcode_head = '';

        // converting the array into data value with ###^^^ to separate the code
        for ($i = 0; $i <= $count-1; $i++) {

            $this_tag_info = yydev_tagmanager_mysql_prep($form_shortcode_head_post[$i]);
            
            if( !empty($this_tag_info) ) {
                $form_shortcode_head .= $this_tag_info;
                if($count > $i+1) {$form_shortcode_head .= "###^^^";}
            } // if( !empty($this_tag_info) ) {

        } // for ($i = 0; $i <= $count; $i++) {

        // clean the code before output
        $form_shortcode_head = yydev_tagmanager_mysql_prep($form_shortcode_head);

        // ------------------------------------------------
        // dealing with the body tags data
        // ------------------------------------------------

        $form_shortcode_body_post = $_POST['form_shortcode_body'];
        $count = count($form_shortcode_body_post);
        $form_shortcode_body = '';

        // converting the array into data value with ###^^^ to separate the code
        for ($i = 0; $i <= $count-1; $i++) {

            $this_tag_info = yydev_tagmanager_mysql_prep($form_shortcode_body_post[$i]);
            
            if( !empty($this_tag_info) ) {
                $form_shortcode_body .= $this_tag_info;
                if($count > $i+1) {$form_shortcode_body .= "###^^^";}
            } // if( !empty($this_tag_info) ) {

        } // for ($i = 0; $i <= $count; $i++) {

        // clean the code before output
        $form_shortcode_body = yydev_tagmanager_mysql_prep($form_shortcode_body);

        // ------------------------------------------------
        // dealing with the footer tags data
        // ------------------------------------------------

        $form_shortcode_footer_post = $_POST['form_shortcode_footer'];
        $count = count($form_shortcode_footer_post);
        $form_shortcode_footer = '';

        // converting the array into data value with ###^^^ to separate the code
        for ($i = 0; $i <= $count-1; $i++) {

            $this_tag_info = yydev_tagmanager_mysql_prep($form_shortcode_footer_post[$i]);
            
            if( !empty($this_tag_info) ) {
                $form_shortcode_footer .= $this_tag_info;
                if($count > $i+1) {$form_shortcode_footer .= "###^^^";}
            } // if( !empty($this_tag_info) ) {

        } // for ($i = 0; $i <= $count; $i++) {

        // clean the code before output
        $form_shortcode_footer = yydev_tagmanager_mysql_prep($form_shortcode_footer);

        // ------------------------------------------------
        // dealing with the lazyload script code
        // ------------------------------------------------

        $custom_lazy_load_js_post = $_POST['custom_lazy_load_js'];
        $count = count($custom_lazy_load_js_post);
        $custom_lazy_load_js = '';

        // converting the array into data value with ###^^^ to separate the code
        for ($i = 0; $i <= $count-1; $i++) {

            $this_tag_info = yydev_tagmanager_mysql_prep($custom_lazy_load_js_post[$i]);
            
            if( !empty($this_tag_info) ) {
                $custom_lazy_load_js .= $this_tag_info;
                if($count > $i+1) {$custom_lazy_load_js .= "###^^^";}
            } // if( !empty($this_tag_info) ) {

        } // for ($i = 0; $i <= $count; $i++) {

        // clean the code before output
        $custom_lazy_load_js = yydev_tagmanager_mysql_prep($custom_lazy_load_js);


        // ----------------------------------------------
        // getting all the values and clear data
        // ----------------------------------------------        

        $exclude_users = sanitize_text_field( $_POST['exclude_users'] );
        $exclude_option = sanitize_text_field( $_POST['exclude_option'] );
        $exclude_ids = sanitize_text_field( $_POST['exclude_ids'] );

        $lazy_load_time = sanitize_text_field( $_POST['lazy_load_time'] );

        $google_analytics_id = sanitize_text_field( $_POST['google_analytics_id'] );
        $yandex_metrika_id = sanitize_text_field( $_POST['yandex_metrika_id'] );
        $facebook_pixel_id = sanitize_text_field( $_POST['facebook_pixel_id'] );
        $google_tag_manager_id = sanitize_text_field( $_POST['google_tag_manager_id'] );

        $remove_custom_lazy_load_js_on_elementor = yydev_tagmanager_checkbox_isset('remove_custom_lazy_load_js_on_elementor');

        $no_lazy_load_wait_pages = sanitize_text_field( $_POST['no_lazy_load_wait_pages'] );

        $wp_body_open = yydev_tagmanager_checkbox_isset('wp_body_open');
        $add_plugin_to_settings = yydev_tagmanager_checkbox_isset('add_plugin_to_settings');

        $save_notes = yydev_tagmanager_mysql_prep( $_POST['save_notes'] );

        // ----------------------------------------------
        // Dealing with with adding yndax-x-frame code to htaccess or remove it
        // ----------------------------------------------    

        $yandex_x_frame_class_allow = yydev_tagmanager_checkbox_isset('yandex_x_frame_class_allow');

        if( isset($yandex_x_frame_class_allow) ) {

            // making sure not to make change to localhost so it won't break code
            if( !strstr($domain_base_name, 'localhost') ) {

                	// -------------------------------------------
                	// incase we want to add the code into htaccess
                	// -------------------------------------------

                    if( $yandex_x_frame_class_allow == 1 ) {

                    	// creating code to insert the wp-config.php
                    	$new_added_code_line = "";
                    	$new_added_code_line .= "\n\n";
                    	$new_added_code_line .= $add_code_to_htaccess_note;
                    	$new_added_code_line .= "\n";
                    	$new_added_code_line .= $add_code_to_htaccess;

                    	// making sure the line not exists already in the file
                    	if( !strstr($tag_htaccess_edit, $add_code_to_htaccess_note) ) {

                    		 // inserting the new data to the file
                    		$new_wp_config_content = $tag_htaccess_edit . $new_added_code_line;
                    		file_put_contents($wp_htaccess_path, $new_wp_config_content);

                    	} // if( !strstr($tag_htaccess_edit, $add_code_to_htaccess_note) ) {

                    } // if( $yandex_x_frame_class_allow == 1 ) {

                	// -------------------------------------------
                	// incase we want to remove the code from htaccess
                	// -------------------------------------------

                    if( $yandex_x_frame_class_allow == 0 ) {

                    	if( strstr($tag_htaccess_edit, $add_code_to_htaccess_note) ) {

                    		 // inserting the new data to the file
                            $tag_htaccess_edit = str_replace("\n" . $add_code_to_htaccess, '', $tag_htaccess_edit);
                            $tag_htaccess_edit = str_replace("\n\n" . $add_code_to_htaccess_note, '', $tag_htaccess_edit);
                            $tag_htaccess_edit = str_replace("\n" . $add_code_to_htaccess_note, '', $tag_htaccess_edit);


                    		file_put_contents($wp_htaccess_path, $tag_htaccess_edit);

                    	} // if( strstr($tag_htaccess_edit, $add_code_to_htaccess_note) ) {

                    } // if( $yandex_x_frame_class_allow == 0 ) {

            } // if( !strstr($domain_base_name, 'localhost') ) {

        } // $yandex_x_frame_class_allow

        // ----------------------------------------------
        // insert the data into an array
        // ----------------------------------------------  

        $plugin_data_array = array(

            'exclude_users' => $exclude_users,
            'exclude_option' => $exclude_option,
            'exclude_ids' => $exclude_ids,

            'lazy_load_time' => $lazy_load_time,

            'google_analytics_id' => $google_analytics_id,
            'yandex_metrika_id' => $yandex_metrika_id,
            'facebook_pixel_id' => $facebook_pixel_id,
            'google_tag_manager_id' => $google_tag_manager_id,

            'custom_lazy_load_js' => $custom_lazy_load_js,
            'remove_custom_lazy_load_js_on_elementor' => $remove_custom_lazy_load_js_on_elementor,

            'no_lazy_load_wait_pages' => $no_lazy_load_wait_pages,

            'wp_body_open' => $wp_body_open,
            'add_plugin_to_settings' => $add_plugin_to_settings,

            'save_notes' => $save_notes,

        ); // $creating_data_array = array(

        // ----------------------------------------------
        // creating a value with all the array data
        // ----------------------------------------------  

        $array_key_name = '';
        $array_item_value = '';
        
	    foreach($plugin_data_array as $key=>$item) {
	        $array_key_name .= "####" . $key;
			$array_item_value .= "####" . $item;
	    } // foreach($medical_form_array as $key=>$item) {

        // ----------------------------------------------
        // inserting all the data to datbase
        // ----------------------------------------------  

        $plugin_data = $array_key_name . "***" . $array_item_value;
        $plugin_data = $plugin_data;

        // update optuon on the database into wp_options
        update_option($wp_options_name, $plugin_data);

        // ------------------------------------------------
        // updating the tags info into the database
        // ------------------------------------------------

        $current_page_id = "0";
        $check_if_data_exists = $wpdb->get_row("SELECT id FROM " . $yydev_tags_table_name . " WHERE page_id = 0");

        if( $wpdb->num_rows == 0) {

            // if the data was not submited into the database
            $wpdb->insert( $yydev_tags_table_name, array('page_id'=>$current_page_id, 'tag_type'=>'head', 'tag_code'=>$form_shortcode_head), array('%d', '%s', '%s') );
            $wpdb->insert( $yydev_tags_table_name, array('page_id'=>$current_page_id, 'tag_type'=>'body', 'tag_code'=>$form_shortcode_body), array('%d', '%s', '%s') );
            $wpdb->insert( $yydev_tags_table_name, array('page_id'=>$current_page_id, 'tag_type'=>'footer', 'tag_code'=>$form_shortcode_footer), array('%d', '%s', '%s') );

            // Creating page link and redirect the user the current page with the new data
            $success_message = __('The data was inserted successfully', 'tag-manager-header-body-footer');
            $new_page_link = $plugin_page_url . "&message=" . urlencode($success_message);

        } else { // if( $wpdb->num_rows == 0) {

            // if the data already exist one the database
            $wpdb->update( $yydev_tags_table_name, array('tag_code'=>$form_shortcode_head), array('page_id'=>$current_page_id, 'tag_type'=>'head'), array('%s') );
            $wpdb->update( $yydev_tags_table_name, array('tag_code'=>$form_shortcode_body), array('page_id'=>$current_page_id, 'tag_type'=>'body'), array('%s') );
            $wpdb->update( $yydev_tags_table_name, array('tag_code'=>$form_shortcode_footer), array('page_id'=>$current_page_id, 'tag_type'=>'footer'), array('%s') );

            // Creating page link and redirect the user the current page with the new data
            $success_message = __('The data was updated successfully', 'tag-manager-header-body-footer');
            $new_page_link = $plugin_page_url . "&&plugin_redirect=1&message=" . urlencode($success_message);

        } // } else { // if( $wpdb->num_rows == 0) { 
                  
        // getting the page url for the settings page

    } else { // if( wp_verify_nonce($_POST['yydev_tagmanager_nonce'], 'yydev_tagmanager_action') ) {
        $error_message = __('Error: form nonce problem', 'tag-manager-header-body-footer');
    } // } else { // if( wp_verify_nonce($_POST['yydev_tagmanager_nonce'], 'yydev_tagmanager_action') ) {

} // if( isset($_POST['yydev_tagmanager_nonce']) ) {

?>

<div class="wrap yydevelopment-tag-manager">

    <h2 class="display-inline"><?php _e('Tag Manager - Add Header, Body and Footer Codes', 'tag-manager-header-body-footer'); ?></h2>
    <p class="main-plugin-description"><?php esc_html_e('Below you will be able to add header code, below the <body> code and footer code for tracking platforms like google analytics, facebook pixel and google tag manager.', 'tag-manager-header-body-footer'); ?></p>

    <?php yydev_tagmanager_echo_message_if_exists(); ?>
    <?php yydev_tagmanager_echo_success_message_if_exists($success_message); ?>
    <?php yydev_tagmanager_echo_error_message_if_exists($error_message); ?>

    <div class="insert-new">
        
<?php

    $tagmanager_content = $wpdb->get_results("SELECT * FROM " . $yydev_tags_table_name . " WHERE page_id = 0");
    $head_tag = ''; $body_tag = ''; $footer_tag = '';

    if($tagmanager_content > 0) {  

        foreach($tagmanager_content as $tagmanager_content_data) {

            $tag_type = $tagmanager_content_data->tag_type;
            $tag_code = $tagmanager_content_data->tag_code;
            
            if( $tag_type === "head" ) {
                $head_tag = $tagmanager_content_data->tag_code;
            } elseif( $tag_type === "body" ) {
                $body_tag = $tagmanager_content_data->tag_code;
            } elseif( $tag_type === "footer" ) {
                $footer_tag = $tagmanager_content_data->tag_code;
            } // if( $tag_type === "head" ) {

        } // foreach($tagmanager_content as $tagmanager_content_data) {

    } // if($tagmanager_content > 0) {  

?>

<br />

<form class="edit-form-data" method="POST" action="">

<div class='tags-right-side'>

        <!-- dealing with the header code on the page -->
        <div class="yydev_tag_warp_textarea">
            <p><b><?php esc_html_e('Insert <head> tags code', 'tag-manager-header-body-footer', 'tag-manager-header-body-footer'); ?></b> - 
            <span><?php esc_html_e('The code will be displayed between the opening <head> and the closing </head> tags', 'tag-manager-header-body-footer'); ?></span></p>

<?php
            $head_tag_array = explode("###^^^", $head_tag);
            foreach( $head_tag_array as $head_tag_array_data ) {
?>
                <div class="tag-area-container">
                    <textarea class='form_shortcode_content' name='form_shortcode_head[]' ><?php echo yydev_tagmanager_html_output($head_tag_array_data); ?></textarea>
                    <a class='remove-tag-text' href='#'><img  src='<?php echo plugins_url() . '/tag-manager-header-body-footer/images/remove.png'; ?>' alt='' title='<?php _e('Remove Code', 'tag-manager-header-body-footer'); ?>' /></a>
                </div><!--tag-area-container-->
<?php

            } //  foreach( $head_tag_array as $head_tag_array_data ) {
?>
            <a href="#" class="direction-ltr add-another-tag"><?php _e('+ Add Another Head Tag', 'tag-manager-header-body-footer'); ?></a>
        </div><!--yydev_tag_warp_textarea-->


        <!-- dealing with the body code on the page -->
        <div class="yydev_tag_warp_textarea">
            <p><b><?php esc_html_e('Insert tags after <body> opening tag', 'tag-manager-header-body-footer'); ?></b> - 
            <?php esc_html_e('The code will be displayed below the opening <body> tag', 'tag-manager-header-body-footer'); ?></p>

<?php
            $body_tag_array = explode("###^^^", $body_tag);
            foreach( $body_tag_array as $body_tag_array_data ) {
?>
                <div class="tag-area-container">
                    <textarea class='form_shortcode_content' name='form_shortcode_body[]' ><?php echo yydev_tagmanager_html_output($body_tag_array_data); ?></textarea>
                    <a class='remove-tag-text' href='#'><img  src='<?php echo plugins_url() . '/tag-manager-header-body-footer/images/remove.png';; ?>' alt='' title='<?php _e('Remove Code', 'tag-manager-header-body-footer'); ?>' /></a>
                </div><!--tag-area-container-->
<?php
            } //  foreach( $head_tag_array as $head_tag_array_data ) {
?>

            <a href="#" class="direction-ltr add-another-tag"><?php _e('+ Add Another After Body Tag'); ?></a>
        </div><!--yydev_tag_warp_textarea-->



        <div class="yydev_tag_warp_textarea">
            <p><b><?php esc_html_e('Insert footer tags code', 'tag-manager-header-body-footer'); ?></b> - 
            <?php esc_html_e('The code will be displayed right above the end </body> tag', 'tag-manager-header-body-footer'); ?></p>

<?php
            $footer_tag_array = explode("###^^^", $footer_tag);
            foreach( $footer_tag_array as $footer_tag_array_data ) {
?>
                <div class="tag-area-container">
                <textarea class='form_shortcode_content' name='form_shortcode_footer[]' ><?php echo yydev_tagmanager_html_output($footer_tag_array_data); ?></textarea>
                <a class='remove-tag-text' href='#'><img  src='<?php echo plugins_url() . '/tag-manager-header-body-footer/images/remove.png';; ?>' alt='' title='<?php _e('Remove Code', 'tag-manager-header-body-footer'); ?>' /></a>
                </div><!--tag-area-container-->
<?php
            } //  foreach( $head_tag_array as $head_tag_array_data ) {
?>

            <a href="#" class="direction-ltr add-another-tag"><?php _e('+ Add Another Footer Tag', 'tag-manager-header-body-footer'); ?></a>
        </div><!--yydev_tag_warp_textarea-->

        <div class="yydev_tag_warp_textarea save-notes">
        
            <p><b>
                <?php esc_html_e("Storage Notes", 'tag-manager-header-body-footer'); ?> 
                <small><?php esc_html_e("(won't effect the site)", 'tag-manager-header-body-footer'); ?></small>
            </b>
            <?php esc_html_e("You can insert here tags you don't need anymore and save it for later use", 'tag-manager-header-body-footer'); ?></p>

<?php

    $storage_notes = '';
    if( isset($plugin_data_array['save_notes']) && !empty($plugin_data_array['save_notes']) ) {
        $storage_notes = yydev_tagmanager_html_output($plugin_data_array['save_notes']);
    }

?>
            <div class="tag-area-container">
                <textarea class='form_shortcode_content' name='save_notes' ><?php echo $storage_notes; ?></textarea>
            </div><!--tag-area-container-->

        </div><!--yydev_tag_warp_textarea-->



</div><!--tags-right-side-->


        <div class='yy-lazy-load-warp'>

            <h2> <?php _e('Lazy Loading For Analytics:', 'tag-manager-header-body-footer'); ?></h2>  


             <p> <?php _e("Add lazy load to most analytics tools by inserting the user id:", 'tag-manager-header-body-footer'); ?>
             <br />
             <small><strong><mark><?php _e("The lazy load code will load the script only after user will start scroll the page or after 5 seconds (this might affect your tracking)", 'tag-manager-header-body-footer'); ?></mark></strong></small>
             </p>


            <div class="tag-manager-line">

<?php

                $load_after_time = '5000';
                if( !empty($plugin_data_array['lazy_load_time']) ) {
                    $load_after_time = intval($plugin_data_array['lazy_load_time']);
                } // if( !empty($plugin_data_array['lazy_load_time']) ) {

?>

                <label for="lazy_load_time"><strong><?php _e("When there is no movement from user Load files after:", 'tag-manager-header-body-footer'); ?> </strong>
                <br />
                <small><?php _e("The value is in milliseconds; 5000 = 5 seconds.", 'tag-manager-header-body-footer'); ?> </small>
                </label><br />
                <input type="text" id="lazy_load_time" class="input-short" name="lazy_load_time" value="<?php echo $load_after_time; ?>" />

            </div><!--tag-manager-line-->


            <div class="tag-manager-line">

                <label for="google_analytics_id"><strong><?php _e("Google Analytics ID:", 'tag-manager-header-body-footer'); ?> </strong></label><br />
                <input type="text" id="google_analytics_id" class="input-short" name="google_analytics_id" placeholder="UA-100000000-2" value="<?php echo sanitize_text_field($plugin_data_array['google_analytics_id']); ?>" />

            </div><!--tag-manager-line-->

            <div class="tag-manager-line">

                <label for="facebook_pixel_id"><strong><?php _e("Facebook Pixel ID:", 'tag-manager-header-body-footer'); ?> </strong></label><br />
                <input type="text" id="facebook_pixel_id" class="input-short" name="facebook_pixel_id" placeholder="1158434421232421" value="<?php echo sanitize_text_field($plugin_data_array['facebook_pixel_id']); ?>" />

            </div><!--tag-manager-line-->

            <div class="tag-manager-line">

                <label for="google_tag_manager_id"><strong><?php _e("Google Tag Manager ID:", 'tag-manager-header-body-footer'); ?> </strong></label><br />
                <input type="text" id="google_tag_manager_id" class="input-short" name="google_tag_manager_id" placeholder="GTM-SSSSSSS" value="<?php echo sanitize_text_field($plugin_data_array['google_tag_manager_id']); ?>" />

            </div><!--tag-manager-line-->

            <div class="tag-manager-line">

                <label for="yandex_metrika_id"><strong><?php _e("Yandex Metrika ID:", 'tag-manager-header-body-footer'); ?> </strong></label><br />
                <input type="text" id="yandex_metrika_id" class="input-short" name="yandex_metrika_id" placeholder="00000000" value="<?php echo sanitize_text_field($plugin_data_array['yandex_metrika_id']); ?>" />
                <br /><br />
            
                <small>
                <?php _e("Yandex metrika is messy, at the moment this code loads clickmap, trackLinks, accurateTrackBounce & webvisor and set them as true.", 'tag-manager-header-body-footer'); ?>
                <?php _e("If you have different settings on your code it might not work (the code you paste on the site when you change the settings on Yandex.)", 'tag-manager-header-body-footer'); ?>
                </small>

                <br /><br />

                <?php
                    // check the htaccess content again to make sure it hasn't changed
                    $tag_htaccess_new = file_get_contents($wp_htaccess_path);
                ?>
                <input type="checkbox" name="yandex_x_frame_class_allow" id="yandex_x_frame_class_allow" value='1' <?php if( strstr($tag_htaccess_new, $add_code_to_htaccess_note) ) { echo 'checked'; } ?>/> 
                <label for="yandex_x_frame_class_allow" class="yandex_x_frame_class_allow"><?php _e('Add allow x-frame option on htaccess to allow maps to work on yandex', 'tag-manager-header-body-footer'); ?>
                <span style='background:#ff0000;color:#fff;'><?php _e("Warning: don't use it if you don't know what it mean, it might break your site.", 'tag-manager-header-body-footer'); ?></span>
                </label>

            </div><!--tag-manager-line-->









            <div class="tag-manager-line yydev_tag_warp_textarea">

                <label for="custom_lazy_load_js"><strong><?php _e("Custom Lazy Load JS (Don't insert script tags here):", 'tag-manager-header-body-footer'); ?> </strong></label>
                <br />
                <span style='background:#ff0000;color:#fff;font-weight:bold;padding:5px;'><?php _e("Warning: Don't insert script open and close tags in here", 'tag-manager-header-body-footer'); ?></span>
                <br /><br />

<?php

                $custom_lazy_load_js = $plugin_data_array['custom_lazy_load_js'];

                $custom_lazy_load_js_array = explode("###^^^", $custom_lazy_load_js);
                foreach( $custom_lazy_load_js_array as $custom_lazy_load_data ) {
?>
                    <div class="tag-area-container">
                        <textarea class='form_shortcode_content' name='custom_lazy_load_js[]' ><?php echo yydev_tagmanager_html_output($custom_lazy_load_data); ?></textarea>
                        <a class='remove-tag-text' href='#'><img  src='<?php echo plugins_url() . '/tag-manager-header-body-footer/images/remove.png'; ?>' alt='' title='<?php _e('Remove Code', 'tag-manager-header-body-footer'); ?>' /></a>
                    </div><!--tag-area-container-->
<?php

                } //  foreach( $custom_lazy_load_js_array as $custom_lazy_load_data ) {
?>
                <a href="#" class="direction-ltr add-another-tag"><?php _e('+ Add Another Lazyload Script', 'tag-manager-header-body-footer'); ?></a>


                <input type="checkbox" name="remove_custom_lazy_load_js_on_elementor" id="remove_custom_lazy_load_js_on_elementor" value='1' <?php if( intval($plugin_data_array['remove_custom_lazy_load_js_on_elementor']) == 1) { echo 'checked'; } ?>/> 
                <label for="remove_custom_lazy_load_js_on_elementor"><?php _e("Don't load custom lazy code in elementor editor (found it to cause problem with some scripts).", 'tag-manager-header-body-footer'); ?></label>

            </div><!--yydev_tag_warp_textarea-->


            <div class="tag-manager-line">

                <h2> <?php _e("Include/Exclude Lazy Load Pages By ID:", 'tag-manager-header-body-footer'); ?> </h2>    

                <p> <?php _e("Insert the pages ID and separate them by comma. You can use", 'tag-manager-header-body-footer'); ?> <a target="_blank" href="https://wordpress.org/plugins/show-posts-and-pages-id/">Show Pages IDs</a> <?php _e("plugin for help", 'tag-manager-header-body-footer'); ?>.
                <br /><br />
                <?php _e("Example", 'tag-manager-header-body-footer'); ?> <small><?php _e("(one page)", 'tag-manager-header-body-footer'); ?></small>: 14 
                <br /> <?php _e("Example", 'tag-manager-header-body-footer'); ?> <small><?php _e("(multiple pages)", 'tag-manager-header-body-footer'); ?></small>: 14, 16, 23 </p>

                <br />


                <strong><label for="no_lazy_load_wait_pages"><?php _e("Don't wait to load the files (stop lazy load):", 'tag-manager-header-body-footer'); ?></label></strong><br />
                <small><?php _e("Will load the tracking code when page load without waiting (can be used for pages with conversion tracking):", 'tag-manager-header-body-footer'); ?></small>
                <br />
                <input type="text" id="no_lazy_load_wait_pages" class="input-short" name="no_lazy_load_wait_pages"  value="<?php echo sanitize_text_field($plugin_data_array['no_lazy_load_wait_pages']); ?>" />

            </div><!--tag-manager-line-->

        </div><!--yy-lazy-load-warp-->


       <br /><br />

        <h2> <?php _e('Exclude Code From Pages By User Role:', 'tag-manager-header-body-footer'); ?> </h2>  

         <p> <?php _e("Choose the user roles you want to exclude the tag manager codes from:", 'tag-manager-header-body-footer'); ?> </p>

        <div class="tag-manager-line">

            <label for="exclude_users"><?php _e("Exclude Code For Users:", 'tag-manager-header-body-footer'); ?> </label>

            <select name="exclude_users" id='exclude_users'>
                <option value="none" <?php if($plugin_data_array['exclude_users'] == "none") {echo "selected";} ?> ><?php _e("Not Active (Default)", 'tag-manager-header-body-footer'); ?></option>
                <option value="exclude_admin" <?php if($plugin_data_array['exclude_users'] == "exclude_admin") {echo "selected";} ?> ><?php _e("Exclude Admin", 'tag-manager-header-body-footer'); ?></option>
                <option value="exclude_all_users" <?php if ($plugin_data_array['exclude_users'] == "exclude_all_users") {echo "selected";} ?> ><?php _e("Exclude All Logged In Users", 'tag-manager-header-body-footer'); ?></option>
            </select>

        </div><!--tag-manager-line-->

        <br /><br />

        <h2> <?php _e("Include/Exclude Pages By ID:", 'tag-manager-header-body-footer'); ?> </h2>    

         <p> <?php _e("Insert the pages ID and separate them by comma. You can use", 'tag-manager-header-body-footer'); ?> <a target="_blank" href="https://wordpress.org/plugins/show-posts-and-pages-id/">Show Pages IDs</a> <?php _e("plugin for help", 'tag-manager-header-body-footer'); ?>.
         <br /><br />
         <?php _e("Example", 'tag-manager-header-body-footer'); ?> <small><?php _e("(one page)", 'tag-manager-header-body-footer'); ?></small>: 14 
         <br /> <?php _e("Example", 'tag-manager-header-body-footer'); ?> <small><?php _e("(multiple pages)", 'tag-manager-header-body-footer'); ?></small>: 14, 16, 23 </p>

        <div class="tag-manager-line">

            <label for="exclude_option"><?php _e("Include/Exclude Option:", 'tag-manager-header-body-footer'); ?></label>

            <select name="exclude_option" id='exclude_option'>
                <option value="none" <?php if($plugin_data_array['exclude_option'] == "none") {echo "selected";} ?> ><?php _e("Not Active (Default)", 'tag-manager-header-body-footer'); ?></option>
                <option value="exclude" <?php if($plugin_data_array['exclude_option'] == "exclude") {echo "selected";} ?> ><?php _e("Exclude Pages By ID", 'tag-manager-header-body-footer'); ?></option>
                <option value="include" <?php if ($plugin_data_array['exclude_option'] == "include") {echo "selected";} ?> ><?php _e("Include Only On Pages", 'tag-manager-header-body-footer'); ?></option>
            </select>

            <input type="text" id="exclude_ids" class="input-short" name="exclude_ids" value="<?php echo yydev_tagmanager_html_output($plugin_data_array['exclude_ids']); ?>" />

        </div><!--tag-manager-line-->

        <br /><br />

        <input type="checkbox" name="add_plugin_to_settings" id="add_plugin_to_settings" value='1' <?php if( intval($plugin_data_array['add_plugin_to_settings']) == 1) { echo 'checked'; } ?>/> 
        <label for="add_plugin_to_settings"><?php _e('Check this box if you want the plugin to show up under the settings menu instead of the main menu (require reopening the plugin page to see changes)', 'tag-manager-header-body-footer'); ?></label>

        <br /><br />

        <div class='mark-this-line'>
            <input type="checkbox" name="wp_body_open" id="wp_body_open" value='1' <?php if( intval($plugin_data_array['wp_body_open']) == 1) { echo 'checked'; } ?>/> 
            <label for="wp_body_open"><?php _e('Check this if your theme supports <b>wp_body_open</b> action (<b>RECOMMENDED TO BE ACTIVE</b>)', 'tag-manager-header-body-footer'); ?></label>
        </div>

        <br />

        <?php
            // creating nonce to make sure the form was submitted correctly from the right page
            wp_nonce_field( 'yydev_tagmanager_action', 'yydev_tagmanager_nonce' ); 
        ?>

        <input type="submit" class="edit-form-data yydev-tags-submit" name="insert_tag_manager" value="<?php _e('Insert/Update Tags', 'tag-manager-header-body-footer'); ?>" />

</form>

<br /><br /><br />
<span id="footer-thankyou-code"><?php _e('This plugin was created by', 'tag-manager-header-body-footer'); ?> <a target="_blank" href="https://www.yydevelopment.com">YYDevelopment</a>. 
<?php _e('If you liked it please give it a', 'tag-manager-header-body-footer'); ?> <a target="_blank" href="https://wordpress.org/plugins/tag-manager-header-body-footer/#reviews"><?php _e('5 stars review', 'tag-manager-header-body-footer'); ?></a>. 
If you want to help support this FREE plugin <a target="_blank" href="https://www.yydevelopment.com/coffee-break/?plugin=tag-manager-header-body-footer">buy us a coffee</a>.</span>
</span>
</div><!--wrap-->