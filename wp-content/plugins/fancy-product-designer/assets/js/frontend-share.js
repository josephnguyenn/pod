jQuery(document).ready(function() {

    jQuery('#fpd-share-button').click(function(evt) {

        if(!fancyProductDesigner) return;

        evt.preventDefault();

        jQuery(".fpd-share-widget, .fpd-share-url").addClass('fpd-hidden');
        jQuery('.fpd-share-process').removeClass('fpd-hidden');

        const multiplier = $selector.width() > 800 ? Number(800 / $selector.width()).toFixed(2) : 1;
        
        fancyProductDesigner.currentViewInstance.toDataURL((dataURL) => {							

            let data = {
                action: 'fpd_createshareurl',
                image: dataURL,
                product: JSON.stringify(fancyProductDesigner.getProduct()),
            };

            jQuery.post(fpd_setup_configs.admin_ajax_url, data, (response) => {

                if(response.share_id !== undefined) {

                    var pattern = new RegExp('(share_id=).*?(&|$)'),
                        shareUrl = window.location.href;

                    if(shareUrl.search(pattern) >= 0) {
                        //replace share id
                        shareUrl = shareUrl.replace(pattern,'$1' + response.share_id + '$2');
                    }
                    else{
                        shareUrl = shareUrl + (shareUrl.indexOf('?')>0 ? '&' : '?') + 'share_id=' + response.share_id;
                    }

                    //append selected attributes of variable product
                    var variationsSer = $productWrapper.find('.variations_form .variations select')
                        .filter((index, element) => jQuery(element).val() != "")
                        .serialize();
                    
                    if(variationsSer && variationsSer.length > 0) {
                        shareUrl += ('&' + variationsSer);
                    }

                    jsSocials.setDefaults('facebook', {
                        logo: ' fpd-icon-share-facebook'
                    });

                    jsSocials.setDefaults('twitter', {
                        logo: ' fpd-icon-share-twitter'
                    });

                    jsSocials.setDefaults('linkedin', {
                        logo: ' fpd-icon-share-linkedin'
                    });

                    jsSocials.setDefaults('pinterest', {
                        logo: ' fpd-icon-share-pinterest'
                    });

                    jsSocials.setDefaults('email', {
                        logo: ' fpd-icon-share-mail'
                    });
                    
                    jQuery(".fpd-share-widget").empty().jsSocials({
                        url: shareUrl,
                        shares: fpd_setup_configs.misc.social_shares,
                        showLabel: false,
                        text: fpd_setup_configs.labels.share_default_text
                    }).removeClass('fpd-hidden');
                }

                jQuery('.fpd-share-process').addClass('fpd-hidden');
                jQuery('.fpd-share-url').attr('href', shareUrl).text(shareUrl).removeClass('fpd-hidden');

            }, 'json');

        }, {multiplier: multiplier, format: 'png'});

    });

});