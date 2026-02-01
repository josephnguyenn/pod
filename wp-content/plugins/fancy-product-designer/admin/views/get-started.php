<?php

    $help_items = array(
        array(
            'id' => 'add-new-product',
            'title' => 'Step #1: Add a New Product',
            'desc' => 'Start from-scratch, use a pre-made template, or upload a demo to add a new product to the builder.',
            'url' => 'admin.php?page=fpd_products&show_get_started=products',
            'target' => '_self',
            'img' => plugins_url('/admin/img/get-started/add_new_product.svg', FPD_PLUGIN_ADMIN_DIR)
        ),
        array(
            'id' => 'build-custom-product',
            'title' => 'Step #2: Build Your Customizable Product',
            'desc' => 'Use the product builder backend to add images, text, upload zones, and customization rules to your products.',
            'url' => 'admin.php?page=fpd_product_builder&show_get_started=product_builder',
            'target' => '_self',
            'img' => plugins_url('/admin/img/get-started/product_builder.svg', FPD_PLUGIN_ADMIN_DIR)
        ),
        array(
            'id' => 'customize-interface',
            'title' => 'Step #3: Customize the User Interface',
            'desc' => 'Edit the appearance of the interface your customers will use to personalize their products before they buy them.',
            'url' => 'admin.php?page=fpd_ui_layout_composer&show_get_started=ui_layout_composer',
            'target' => '_self',
            'img' => plugins_url('/admin/img/get-started/user_interface.svg', FPD_PLUGIN_ADMIN_DIR)
        ),
        array(
            'id' => 'connect-store',
            'title' => 'Step #4: Connect Your Store',
            'desc' => 'Create a new WooCommerce product, enter the details, set the price, and connect it to your FPD customizable product.',
            'tour_id' => 49210,
            'target' => '_blank',
            'img' => plugins_url('/admin/img/get-started/connect_woo.svg', FPD_PLUGIN_ADMIN_DIR)
        ),
        array(
            'id' => 'do-more',
            'title' => 'Do More with Fancy Product Designer',
            'desc' => 'Learn how to configure settings, set pricing rules, and use Fancy Product Designer like a pro with our Knowledge Base and Forum.',
            'url' => 'https://support.fancyproductdesigner.com/',
            'target' => '_blank',
            'img' => plugins_url('/admin/img/get-started/learn_more.svg', FPD_PLUGIN_ADMIN_DIR)
        )
    )

?>

<div class="wrap" id="fpd-get-started">
    <h1 class="ui header">Welcome to Fancy Product Designer</h1>
    <div class="ui grid">
        
        <div class="row">
            <div id="fpd-help-items" class="ui doubling five cards">
                <?php foreach($help_items as $help_item): ?>
                    <div class="card">
                        <div class="image">
                            <img src="<?php echo $help_item['img'] ?>" />
                        </div>
                        <div class="content">
                            <div class="header">
                                <?php echo $help_item['title'] ?>
                            </div>
                            <div class="description">
                                <?php echo $help_item['desc'] ?>
                            </div>
                        </div>
                        <div class="extra content">
                            <a class="ui fluid primary button" 
                                data-tour-id="<?php isset($help_item['tour_id']) ?  esc_attr_e( $help_item['tour_id'] ) : ''; ?>"
                                href="<?php isset($help_item['url']) ?  esc_attr_e( $help_item['url'] ) : ''; ?>"
                                target="<?php isset($help_item['target']) ?  esc_attr_e( $help_item['target'] ) : '_self'; ?>"
                            >
                                <?php echo $help_item['target'] == '_self' || isset($help_item['tour_id']) ?  'Start' : 'Learn More'; ?>
                            </a>
                        </div>
                        
                    </div>            
                <?php endforeach; ?>
            </div>
        </div>

        <div class="row">
            <div class="ui icon message" id="fpd-genius-banner">
                
                <div class="content">
                    <div class="header">
                        Upgrade to Genius
                    </div>
                    <p>Genius integrates a suite of professional services designed to enhance the functionality of Fancy Product Designer, including our enhanced PRO Export feature.
                    Furthermore, we offer specialized services powered by artificial intelligence (AI), including "Remove Background", "Image Upscaling", and "Text to Image" features.</p>
                    <a href="admin.php?page=fpd_genius" class="ui primary medium button">Learn More</a>
                </div>
                <i class="arrow alternate circle up icon"></i>
            </div>
        </div>

    </div>
    
</div>
<script>
    jQuery(document).ready(() => {

        jQuery('#fpd-help-items [data-tour-id]').on('click', (evt) => {

            if(evt.currentTarget.dataset.tourId) {
                evt.preventDefault();
                window.USETIFUL.tour.start(parseInt(evt.currentTarget.dataset.tourId));
            }
            
        })
        
    })
</script>