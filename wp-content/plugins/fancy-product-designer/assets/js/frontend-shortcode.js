jQuery(document).ready(() => {

    function fpdReady() {

        var priceFormat = jQuery('.fpd-shortcode-price').data('priceformat');
        if(priceFormat)
            fancyProductDesigner.mainOptions.priceFormat.currency = priceFormat;

        var $shortcodePrice = $cartForm.find('.fpd-shortcode-price');					

        //calculate initial price
        fancyProductDesigner.addEventListener('productCreate', function() {
            
            $cartForm.find(':submit').removeClass('fpd-disabled');
            _setTotalPrice();

        });

        //listen when price changes
        fancyProductDesigner.addEventListener('priceChange', _setTotalPrice);

        jQuery('[name="fpd_shortcode_form"]').on('click', ':submit', function(evt) {

            evt.preventDefault();

            if(!fpdProductCreated) { return false; }

            var order = fancyProductDesigner.getOrder({
                    customizationRequired: fpd_setup_configs.misc.customization_required !== 'none'
                });

            var $submitBtn = jQuery(this),
                data = {
                    action: 'fpd_newshortcodeorder'
                };

            if(order.product != false && order.bulkVariations !== false) {

                var $nameInput = $cartForm.find('[name="fpd_shortcode_form_name"]').removeClass('fpd-error'),
                    $emailInput = $cartForm.find('[name="fpd_shortcode_form_email"]').removeClass('fpd-error'),
                    emailRegex = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;


                if( $nameInput.val() === '' ) {
                    $nameInput.focus().addClass('fpd-error');
                    return false;
                }
                else {
                    data.name = $nameInput.val();
                }

                if( !emailRegex.test($emailInput.val()) ) {
                    $emailInput.focus().addClass('fpd-error');
                    return false;
                }
                else {
                    data.email = $emailInput.val();
                }

                data.print_order = JSON.stringify(fancyProductDesigner.getPrintOrderData( fpd_setup_configs.misc.export_method == 'svg2pdf' ));
                data.order = JSON.stringify(order);
                $submitBtn.addClass('fpd-disabled');
                $selector.find('.fpd-full-loader').show();

                jQuery.post(fpd_setup_configs.admin_ajax_url, data, function(response) {

                    FPDSnackbar(response.id ? response.message : response.error);
                    $submitBtn.removeClass('fpd-disabled');
                    $selector.find('.fpd-full-loader').hide();
                    fancyProductDesigner.toggleSpinner(false);

                }, 'json');

                $nameInput.val('');
                $emailInput.val('');
                fancyProductDesigner.toggleSpinner(true);

            }

        });

        //set total price depending from wc and fpd price
        function _setTotalPrice() {
            
            if(priceFormat && $shortcodePrice) {
                
                const htmlPrice = fancyProductDesigner.formatPrice(fancyProductDesigner.currentPrice);

                $shortcodePrice.html(htmlPrice)
                .parent().addClass('fpd-show-up');

            }

        };

    }

    if(typeof fancyProductDesigner !== 'undefined') {
        return fpdReady();
    }
    jQuery('.fpd-container').on('ready', fpdReady);

})
