
<?php

$disbale_help_context = fpd_get_option('fpd_disable_help_context');

$current_screen = get_current_screen();
$screen_id = str_replace("fancy-product-designer_page_fpd_", "", $current_screen->id);
$screen_id = $screen_id == 'toplevel_page_fancy_product_designer' ? 'products' : $screen_id;

$current_user = wp_get_current_user();
$user_email = $current_user->user_email;
?>
<div id="fpd-admin-topbar">
    <div>
        <a href="admin.php?page=<?php echo $disbale_help_context ? 'fancy_product_designer' : 'fpd_products' ;?>" aria-current="page" class="<?php echo $screen_id == 'products' ? 'current' : '' ?>">Add New Product</a>
        <a href="admin.php?page=fpd_genius" aria-current="page" class="<?php echo $screen_id == 'genius' ? 'current' : '' ?>">Get Genius</a>
    </div>
    <div>
        <a href="admin.php?page=fpd_status" target="_self" aria-current="page" class="<?php echo $screen_id == 'status' ? 'current' : '' ?>"><?php _e('Status', 'radykal'); ?></a>
        <a href="https://support.fancyproductdesigner.com/" target="_blank"><?php _e('Support Center', 'radykal'); ?></a>
        <a href="https://support.fancyproductdesigner.com/support/discussions/forums/5000283646" target="_blank"><?php _e('Changelog', 'radykal'); ?></a>
        <button data-featurebase-feedback><?php _e('Request a Feature', 'radykal'); ?></button>
    </div>
    <script>!(function(e,t){const a="featurebase-sdk";function n(){if(!t.getElementById(a)){var e=t.createElement("script");(e.id=a),(e.src="https://do.featurebase.app/js/sdk.js"),t.getElementsByTagName("script")[0].parentNode.insertBefore(e,t.getElementsByTagName("script")[0])}}"function"!=typeof e.Featurebase&&(e.Featurebase=function(){(e.Featurebase.q=e.Featurebase.q||[]).push(arguments)}),"complete"===t.readyState||"interactive"===t.readyState?n():t.addEventListener("DOMContentLoaded",n)})(window,document);</script>
    <script>
        Featurebase("initialize_feedback_widget", {
            organization: "fpd", // Replace this with your organization name, copy-paste the subdomain part from your Featurebase workspace url (e.g. https://*yourorg*.featurebase.app)
            theme: "light", // required
            //placement: "right", // optional - remove to hide the floating button
            email: "<?php echo esc_js($user_email); ?>", // optional
            defaultBoard: "Feature Request", // optional - preselect a board
            locale: "en", // Change the language, view all available languages from https://help.featurebase.app/en/articles/8879098-using-featurebase-in-my-language  
            metadata: {
                version: "<?php echo Fancy_Product_Designer::VERSION ?>"
            } // Attach session-specific metadata to feedback. Refer to the advanced section for the details: https://help.featurebase.app/en/articles/3774671-advanced#7k8iriyap66
        });
    </script>
</div>

<?php
require_once(FPD_PLUGIN_ADMIN_DIR . '/views/get-started-banner.php' );
?>
