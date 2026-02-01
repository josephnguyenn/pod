<?php

$current_screen = get_current_screen();
$screen_id = str_replace("fancy-product-designer_page_fpd_", "", $current_screen->id);

$get_started_banners = array(
    'products' => array(
        'title' => 'Add a New Product',
        'desc' => 'Start from-scratch, use a pre-made template, or upload a demo to add a new product to the builder.',
        'tour_id' => 57166,
        'video' => '<iframe width="100%" height="315" src="https://www.youtube.com/embed/9AcrfUIds9s?si=NO2cbdql-cykEowF" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>'
    ),
    'product_builder' => array(
        'title' => 'Build Your Customizable Product',
        'desc' => 'Use the product builder backend to add images, text, upload zones, and customization rules to your products.',
        'tour_id' => 57167,
        'video' => '<iframe width="100%" height="315" src="https://www.youtube.com/embed/bmd6yuoU7Co?si=OvA67EoizKuInF_I" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>'
    ),
    'ui_layout_composer' => array(
        'title' => 'Customize the User Interface',
        'desc' => 'Edit the appearance of the interface your customers will use to personalize their products before they buy them.',
        'tour_id' => 57168,
        'video' => '<iframe width="100%" height="315" src="https://www.youtube.com/embed/oJLJbBc2xR8?si=hq1Qhv8-Wk2Z1ECJ" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>'
    )
);

//show banner coming from get started screen
$current_banner = isset($_GET['show_get_started']) ? @$get_started_banners[$_GET['show_get_started']] : null;


//show initial banner for first time visitors
if(!$current_banner && isset($get_started_banners[$screen_id])) {

    $banner_dismissed = get_option( 'fpd_notification_'.$screen_id );    

    if(!$banner_dismissed) {
        $current_banner = $get_started_banners[$screen_id];
    }
    
}

?>
<?php if($current_banner): ?>
    <div id="fpd-admin-get-started-banner" class="ui container fpd-dismiss-notification">
        <div class="ui raised segment">
            <div class="ui grid">
                <div class="seven wide column">
                    <h4 class="ui header"><?php echo $current_banner['title']; ?></h4>
                    <p><?php echo $current_banner['desc']; ?></p>
                    <button class="ui primary basic button" data-tour-id="<?php echo $current_banner['tour_id']; ?>">TAKE A TOUR</button>
                </div>
                <div class="seven wide column">
                    <?php echo $current_banner['video']; ?>
                </div>
            </div>
        </div>
        <button id="fpd-close-banner" class="notice-dismiss" value="<?php echo esc_attr( $screen_id ); ?>">
        </button>
        <script>
            jQuery(document).ready(() => {

                jQuery('#fpd-admin-get-started-banner .button').on('click', (evt) => {

                    evt.preventDefault();                
                    window.USETIFUL.tour.start(parseInt(evt.currentTarget.dataset.tourId));

                })

            })
    </script>
    </div>
<?php endif; ?>