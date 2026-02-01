jQuery(document).ready(() => {

    function fpdReady() {

        var $gfForm = jQuery('.gfield.fpd-order').parents('form:first');
        
        if($gfForm.length > 0) {

            $gfForm.on('click', '.gform_button:submit', function(evt) {

                evt.preventDefault();

                if(!fpdProductCreated) { return false; }

                var order = fancyProductDesigner.getOrder({
                        customizationRequired: fpd_setup_configs.misc.customization_required !== 'none'
                    });                

                if(order.product != false) {

                    order.print_order = fancyProductDesigner.getPrintOrderData();
                    
                    $gfForm.find('.fpd-order input').val(JSON.stringify(order));

                    let reqFieldsSet = true;
                    $gfForm.find('.gfield_contains_required').each((i, reqField) => {

                        const inputElem = reqField.querySelector('input');
                        if(inputElem && inputElem.value === '') {

                            reqFieldsSet = false;
                            reqField.classList.add('gfield_error');
                            inputElem.setAttribute('aria-invalid', true);

                        }

                    })
                    
                    if(reqFieldsSet)
                        $gfForm.submit();

                }

            });

        }

        fancyProductDesigner.addEventListener('priceChange', function() {

            var currency = new Currency(gf_global.gf_currency_config),
                totalPrice = fancyProductDesigner.currentPrice,
                $priceInput = $gfForm.find('.fpd-price input');

            $priceInput.val(currency.toMoney(totalPrice, true));

            if($priceInput.prev('.fpd-gf-price').length > 0) {
                $priceInput.prev('.fpd-gf-price').html(currency.toMoney(totalPrice, true));
            }
            else {
                $priceInput.before('<p class="fpd-gf-price">'+currency.toMoney(totalPrice, true)+'</p>');
            }

        });

    }

    if(typeof fancyProductDesigner !== 'undefined') {
        return fpdReady();
    }
    jQuery('.fpd-container').on('ready', fpdReady);

})
