jQuery(document).ready(() => {
    
    //variations
    var fpdWcLoadAjaxProduct = false,
        $productWrapper = jQuery('.post-'+fpdProductId).first(),
        $customizeButton = jQuery('#fpd-start-customizing-button');
                   
    //set url parameters from form if designer is opened on next page
    $customizeButton.click((evt) => {						

        if($customizeButton.hasClass('fpd-next-page')) {

            evt.preventDefault();

            var serializedForm = jQuery('form.variations_form select').serialize();							
            serializedForm = serializedForm.replace(/[^=&]+=(&|$)/g,"").replace(/&$/,""); //remove empty values
            window.open(evt.currentTarget.href+'&'+serializedForm, '_self');

        }

    });

    let $form;
    if(typeof fancyProductDesigner !== 'undefined') {

        fancyProductDesigner.addEventListener('productCreate', () => {

            if($form) {
                $form.removeClass('disabled');
                $form = null;
            }
            
        })

    }
    
    jQuery('[name="variation_id"]:first').parents('form:first')
    .on('show_variation', (evt, variation) => {
        
        $form = jQuery(evt.currentTarget);

        $customizeButton.css('display', 'inline-block');

        if(!fpdWcLoadAjaxProduct && variation.fpd_variation_product_id) {

            var fpdProductID = variation.fpd_variation_product_id;
            if(typeof fpdProductCreated !== 'undefined' && fpdProductCreated) {

                fpdWcLoadAjaxProduct = true;

                $form.addClass('disabled');
                fancyProductDesigner.toggleSpinner(true, fpd_woo_configs.labels.loading_product);

                var data = {
                    action: 'fpd_load_product',
                    product_id: fpdProductID
                };

                jQuery.post(
                    fpd_setup_configs.admin_ajax_url,
                    data,
                    (response) => {

                        if(typeof response === 'object') {

                            if(response.length == 0) {

                                alert('The product does not exists or has no views!');
                                fancyProductDesigner.toggleSpinner(false);
                                return;

                            }
                                                        
                            fancyProductDesigner.loadProduct(
                                response,
                                fpd_woo_configs.options.replace_initial_elements,
                                true
                            );
                        }
                        else {
                            FPDSnackbar(fpd_woo_configs.labels.product_loading_fail);
                        }

                        fpdWcLoadAjaxProduct = false;

                }, 'json');

            }
            else { //customize button activated and product designer will load in next page

                $customizeButton.attr('href', (_, href) => {
                    return href.search('fpd_product') === -1 ? href+'&fpd_product='+fpdProductID : href.replace(/fpd_product=\d+/gi, 'fpd_product='+fpdProductID);
                });

            }

        }

    })
    .on('reset_data', () => {

        if($productWrapper.hasClass('fpd-variation-needed')) {
            $customizeButton.hide();
        }

    });

})