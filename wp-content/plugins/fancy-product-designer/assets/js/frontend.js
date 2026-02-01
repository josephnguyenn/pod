var fancyProductDesigner,
    $body,
    $selector,
    $productWrapper,
    $cartForm,
    $modalPrice = null,
    fpdProductCreated = false;
                        
document.addEventListener('DOMContentLoaded', () => {

    if(typeof fpd_setup_configs === 'undefined') return;
    
    fabric.textureSize = Number(fpd_setup_configs.misc.fabric_js_texture_size);

    $body = jQuery('body');
    $selector = jQuery('#'+fpd_setup_configs.selector);
    $productWrapper = jQuery('.post-'+fpd_setup_configs.post_id).first();
    $cartForm = jQuery('[name="fpd_product"]:first').parents('form:first');

    fancyProductDesigner = new FancyProductDesigner($selector.get(0), fpd_setup_configs.app_options);			

    fancyProductDesigner.addEventListener('ready', () => {

        //shortcode: modules
        jQuery('.fpd-sc-module-wrapper').each((i, moduleWrapper) => {								
            
            switch(moduleWrapper.dataset.type) {

                case 'products':
                    new FPDProductsModule(fancyProductDesigner, moduleWrapper);
                    break;
                case 'text':
                    new FPDTextsModule(fancyProductDesigner, moduleWrapper);
                    break;
                case 'designs':
                    new FPDDesignsModule(fancyProductDesigner, moduleWrapper);
                    break;
                case 'images':
                    new FPDImagesModule(fancyProductDesigner, moduleWrapper);
                    break;
                case 'layouts':
                    new FPDLayoutsModule(fancyProductDesigner, moduleWrapper);
                    break;
                case 'manage-layers':
                    new FPDLayersModule(fancyProductDesigner, moduleWrapper);
                    break;
                case 'save-load':
                    new FPDSaveLoadModule(fancyProductDesigner, moduleWrapper);            
                    break;
                case 'text-layers':
                    new FPDTextLayersModule(fancyProductDesigner, moduleWrapper);            
                    break;
                case 'names-numbers':
                    new FPDNamesNumbersModule(fancyProductDesigner, moduleWrapper);
                    break;

            }

            fancyProductDesigner.translator.translateArea(moduleWrapper);

        });

        //shortcode: actions
        jQuery('.fpd-sc-action-placeholder').each((i, item) => {
            
            item.classList.add('fpd-container');
            fancyProductDesigner.actionsBar.addActionBtn(item, item.dataset.action);


        });

        //load product from url query
        if(fpd_setup_configs.initial_product && fpd_setup_configs.initial_product.length > 1) {
            
            var fpdData = typeof fpd_setup_configs.initial_product == 'string' ? JSON.parse(fpd_setup_configs.initial_product) : fpd_setup_configs.initial_product;
            product = fpdData.product ? fpdData.product : fpdData;		
                                    
            fancyProductDesigner.toggleSpinner(true);
            fancyProductDesigner.loadProduct(product);

            if(fpdData.bulkVariations) {
                fancyProductDesigner.bulkVariations.setup(fpdData.bulkVariations);
            }

        }

        //login required option
        if (fpd_setup_configs.misc.login_required) {
            jQuery('fpd-module-uploads .fpd-upload-image')
            .html('<div class="fpd-login-info">'+fpd_setup_configs.labels.login_required+'</div>');
        }

        //convert download action to print ready download        
        if(fpd_setup_configs.misc.pro_export_enabled && fpd_setup_configs.misc.print_download_designer) {

            jQuery('.fpd-btn[data-action="download"]').each((i, printActionElem) => {

                if(printActionElem.abortController) {
    
                    printActionElem.abortController.abort();
    
                    printActionElem.addEventListener('click', () => {
    
                        if(window.fpdDownloadPrintFile) {

                            printActionElem.classList.add('fpd-disabled');
                            window.fpdDownloadPrintFile(fancyProductDesigner, () => {
                                                                
                                printActionElem.classList.remove('fpd-disabled');

                            })

                        }
    
                    })
    
                }
    
            })

        }

    })

    fancyProductDesigner.addEventListener('productCreate', () => {

        fpdProductCreated = true;
    
        if (['all', 'any'].includes(fancyProductDesigner.mainOptions.customizationRequiredRule)) {
            $body.addClass('fpd-customization-required');
        }

    })

    //remove customization-required class when one or all views are updated
    fancyProductDesigner
    .addEventListener('historyAction', () => {        
        
        let customizationChecker = false,
            jsMethod = fancyProductDesigner.mainOptions.customizationRequiredRule == 'all' ? 'every' : 'some';
            
        customizationChecker = fancyProductDesigner.viewInstances[jsMethod]((viewInst) => {	            							
            return viewInst.fabricCanvas.isCustomized;
        })        

        if(customizationChecker) {
            $body.removeClass('fpd-customization-required');
        }

    })

    if(fpd_setup_configs.misc.store_designs_account) {

        fancyProductDesigner
        .addEventListener('actionSave', (evt) => {

            const { product, thumbnail, title } = evt.detail;

            if (fpd_setup_configs.current_user_id == 0) {
                FPDSnackbar(fpd_setup_configs.labels.account_storage_login_required);
                return;
            }

            if (product) {

                fancyProductDesigner.toggleSpinner(true);

                let data = {
                    action: 'fpd_saveuserproduct',
                    title: title,
                    thumbnail: thumbnail,
                    product: JSON.stringify(product),
                    post_id: fpd_setup_configs.post_id
                };

                jQuery.post(fpd_setup_configs.admin_ajax_url, data, (response) => {

                    FPDSnackbar(response.error ? response.error : response.message);
                    fancyProductDesigner.toggleSpinner(false);

                }, 'json');

            }

        })

        fancyProductDesigner
        .addEventListener('ready', () => {

            const gridElem = fancyProductDesigner.container.querySelector('fpd-module-save-load .fpd-grid');

            if (gridElem && fancyProductDesigner['moduleInstance_save-load']) {

                let data = {
                    action: 'fpd_loaduserproducts',
                    post_id: fpd_setup_configs.post_id
                };

                jQuery.post(fpd_setup_configs.admin_ajax_url, data, function (response) {

                    if (response.data) {

                        Object.keys(response.data).forEach((metaKey) => {

                            const fpdData = response.data[metaKey].fpd_data;												
                            const item = fancyProductDesigner['moduleInstance_save-load'].addSavedProduct(fpdData);

                            item.dataset.key = metaKey;

                        });

                    }

                }, 'json');

            }

        })

        fancyProductDesigner
        .addEventListener('actionLoad:Remove', (evt) => {

            const { item, index } = evt.detail;

            let data = {
                action: 'fpd_removeuserproducts',
                key: item.dataset.key,
            };

            jQuery.post(fpd_setup_configs.admin_ajax_url, data, (response) => { }, 'json');

        });

    }
})