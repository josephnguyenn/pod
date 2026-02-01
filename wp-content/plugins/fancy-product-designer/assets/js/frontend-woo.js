jQuery(document).ready(() => {
	function fpdReady() {
		let wcPrice = fpd_woo_configs.options.wcPrice,
			modalDesignerOnceOpened = false;

		//check when variation has been selected
		jQuery(document).on("found_variation", ".variations_form", (evt, variation) => {
			let variationPrice;
			if (variation.display_price !== undefined) {
				wcPrice = variation.display_price;
			}

			_setTotalPrice();
		});

		//calculate initial price
		fancyProductDesigner.addEventListener("productCreate", () => {
			_setTotalPrice();

			if (fpd_setup_configs.initial_product) {
				setTimeout(_setProductImage, 5);
			}
		});

		//listen when price changes
		fancyProductDesigner.addEventListener("priceChange", () => {
			_setTotalPrice();
		});

		$cartForm.on("fpdProductSubmit", () => {
			if (!fpd_setup_configs.misc.ajax_add_to_cart_mode) fancyProductDesigner.toggleSpinner(true);

			$cartForm.submit();
		});

		//fill custom form with values and then submit
		$cartForm.on("click", ":submit", (evt) => {
			evt.preventDefault();

			//validate min quantity input
			$quantityInput = $cartForm.find(".quantity input");
			if ($quantityInput.length > 0 && parseInt($quantityInput.val()) < parseInt($quantityInput.attr("min"))) {
				return;
			}

			//check if product is created and all variations are selected
			if (!fpdProductCreated || jQuery(this).is(".wc-variation-selection-needed")) {
				return false;
			}

			let order = fancyProductDesigner.getOrder({
				customizationRequired: fpd_setup_configs.misc.customization_required !== "none",
			});
			const addToCartDisabled = jQuery(".single_add_to_cart_button").hasClass("disabled");

			if (order.product != false && order.bulkVariations !== false && !addToCartDisabled) {
				FPDSnackbar(fpd_woo_configs.labels.add_to_cart);

				let priceSet = _setTotalPrice();
				const $addToCartBtn = jQuery(".single_add_to_cart_button").addClass("fpd-disabled");
				if (fpd_setup_configs.misc.ajax_add_to_cart_mode) {
					setTimeout(() => {
						$addToCartBtn.removeClass("fpd-disabled");
					}, 3000);
				}

				let tempDevicePixelRation = fabric.devicePixelRatio,
					viewOpts = fancyProductDesigner.viewInstances[0].options,
					multiplier = FPDFabricUtils.getScaleByDimesions(
						viewOpts.stageWidth,
						viewOpts.stageHeight,
						fpd_woo_configs.options.cart_thumbnail_width,
						fpd_woo_configs.options.cart_thumbnail_height
					);

				fabric.devicePixelRatio = 1;
				fancyProductDesigner.viewInstances[0].toDataURL(
					(dataURL) => {
						if (fancyProductDesigner.mainOptions.modalMode) {
							modalDesignerOnceOpened &&
								$cartForm.find('input[name="fpd_product_thumbnail"]').val(dataURL);
						} else {
							$cartForm.find('input[name="fpd_product_thumbnail"]').val(dataURL);
						}

						$cartForm.find('input[name="fpd_product"]').val(JSON.stringify(order));
						$cartForm
							.find('input[name="fpd_print_order"]')
							.val(
								JSON.stringify(
									fancyProductDesigner.getPrintOrderData(
										fpd_setup_configs.misc.export_method == "svg2pdf"
									)
								)
							);

						if (priceSet) {
							$cartForm.trigger("fpdProductSubmit");
						}

						fabric.devicePixelRatio = tempDevicePixelRation;
					},
					{ format: "png", multiplier: multiplier }
				);
			}
		});

		fancyProductDesigner.addEventListener("modalDesignerDone", () => {
			if ($selector.parents(".woocommerce").length > 0) {
				_setProductImage();
			}

			if (fpd_woo_configs.options.lightbox_add_to_cart) {
				$cartForm.find(":submit:not(.reset_variations)").click();
			}
		});

		fancyProductDesigner.addEventListener("modalDesignerOpen", () => {
			modalDesignerOnceOpened = true;
		});

		jQuery("#fpd-extern-download-pdf").click((evt) => {
			var $this = jQuery(this);

			evt.preventDefault();
			if (fpdProductCreated) {
				if (fpd_setup_configs.misc.pro_export_enabled) {
					if (window.fpdDownloadPrintFile) {
						$this.addClass("fpd-disabled");
						window.fpdDownloadPrintFile(fancyProductDesigner, () => {
							$this.removeClass("fpd-disabled");
						});
					}
				} else {
					fancyProductDesigner.actions.downloadFile("pdf");
				}
			}
		});

		jQuery("#fpd-save-order").click(function (evt) {
			evt.preventDefault();

			const urlParams = new URLSearchParams(window.location.search);

			if (fpdProductCreated && urlParams.get("item_id")) {
				fancyProductDesigner.toggleSpinner(true);

				const data = {
					action: "fpd_save_order",
					item_id: urlParams.get("item_id"),
					fpd_order: JSON.stringify(fancyProductDesigner.getOrder()),
					print_order: fpd_setup_configs.misc.pro_export_enabled
						? JSON.stringify(
								fancyProductDesigner.getPrintOrderData(
									fpd_setup_configs.misc.export_method == "svg2pdf"
								)
						  )
						: "",
				};

				jQuery.post(
					fpd_setup_configs.admin_ajax_url,
					data,
					(response) => {
						fancyProductDesigner.toggleSpinner(false);
						FPDSnackbar(
							typeof response === "object"
								? fpd_woo_configs.labels.order_saved
								: fpd_woo_configs.labels.order_saving_failed
						);
					},
					"json"
				);
			}
		});

		//set total price depending from wc and fpd price
		function _setTotalPrice() {
			//do not set price when wcbv is enabled, wcbv is doing price display
			if ($body.hasClass("wcbv-product")) {
				return false;
			}

			$cartForm.find('input[name="fpd_quantity"]').val(fancyProductDesigner.orderQuantity);

			if (fpd_woo_configs.options.disable_price_calculation) {
				return true;
			}

			let totalPrice =
					parseFloat(wcPrice) * fancyProductDesigner.orderQuantity +
					parseFloat(fancyProductDesigner.calculatePrice(true, false)),
				htmlPrice;

			totalPrice = totalPrice.toFixed(fpd_woo_configs.options.number_of_decimals);

			if (!$priceElem || $priceElem.length == 0) {
				htmlPrice = fancyProductDesigner.formatPrice(totalPrice);

				//check if variations are used
				var $priceElem,
					selectorPriceAmount = fpd_woo_configs.options.price_selector;
				if ($productWrapper.find(".variations_form").length > 0) {
					//check if amount contains 2 prices or sale prices. If yes different prices are used
					if (
						$productWrapper.find(".price:first > .amount").length >= 2 ||
						$productWrapper.find(".price:first ins > .amount").length >= 2
					) {
						//different prices
						$priceElem =
							$cartForm.find(".woocommerce-Price-amount:first").length > 0
								? $cartForm.find(selectorPriceAmount)
								: $productWrapper.find(".single_variation .price .amount:last"); //fallback older WC version
					} else {
						//same price
						$priceElem =
							$productWrapper.find(".woocommerce-Price-amount:first").length > 0
								? $productWrapper.find(selectorPriceAmount)
								: $productWrapper.find(".price:first .amount:last"); //fallback older WC version
					}
				}
				//no variations are used
				else {
					$priceElem =
						$productWrapper.find(".woocommerce-Price-amount").length > 0
							? $productWrapper.find(selectorPriceAmount)
							: $productWrapper.find(".price:first .amount:last"); //fallback older WC version
				}
			}

			if ($priceElem && $priceElem.length > 0) {
				$priceElem.html(htmlPrice);
			} else {
				console.info("No price element could be found in the document!");
			}

			setTimeout(() => {
				jQuery(".fpd-modal-product-designer fpd-actions-bar .fpd-total-price").html(htmlPrice);
			}, 1);

			if ($cartForm.find('input[name="fpd_product_price"]').length > 0) {
				//set price without quantity
				$cartForm
					.find('input[name="fpd_product_price"]')
					.val(parseFloat(wcPrice) + fancyProductDesigner.calculatePrice(false, false));
				return true;
			} else {
				return false;
			}
		}

		let fpdImage;
		function _updateProductImage(imageSrc) {
			var $firstProductImage = $productWrapper.find(".images"),
				//wc standard, flatsome theme, owl
				firstImageSelector =
					".woocommerce-product-gallery__image:first img, .slide:first img, .owl-stage .img-thumbnail img";

			firstImageSelector += fpd_woo_configs.options.product_image_css_selector
				? "," + fpd_woo_configs.options.product_image_css_selector
				: "";

			var image = new Image();
			image.onload = function () {
				$firstProductImage
					.find(firstImageSelector)
					.attr("data-large_image_width", this.width)
					.attr("data-large_image_height", this.height);
			};
			image.src = imageSrc;

			$firstProductImage
				.find(firstImageSelector)
				.attr("src", imageSrc)
				.attr("srcset", imageSrc) //all images (display and zoom)
				.parent("a")
				.attr("href", imageSrc) //photoswipe image
				.children("img")
				.attr("data-large_image", imageSrc); //photoswipe large image

			$firstProductImage.find(".flex-control-thumbs li:first img").attr("src", imageSrc); //thumb gallery
		}

		function _setProductImage() {
			const $modalDesigner = jQuery(".fpd-modal-product-designer");

			if ($modalDesigner.length && fpd_woo_configs.options.lightbox_update_product_image) {
				//show designer otherwise data url is empty
				let tempShow = false;
				if (!$modalDesigner.hasClass("fpd-show")) {
					tempShow = true;
					$modalDesigner.addClass("fpd-show");
				}

				fancyProductDesigner.selectView(0);
				fancyProductDesigner.currentViewInstance.fabricCanvas.resetSize();

				const dataURL = fancyProductDesigner.currentViewInstance.fabricCanvas.toDataURL();

				if (tempShow) {
					$modalDesigner.removeClass("fpd-show");
				}

				_updateProductImage(dataURL);
				fpdImage = dataURL;
			}
		}

		//fix: do not change to variation image when using lightbox
		$productWrapper.find(".images").on("woocommerce_gallery_init_zoom", () => {
			if (fpdImage) {
				_updateProductImage(fpdImage);
			}

			//timeout fix: zoom image is not updating
			setTimeout(() => {
				if (fpdImage) {
					_updateProductImage(fpdImage);
				}
			}, 500);
		});
	}

	if (typeof fancyProductDesigner !== "undefined") {
		return fpdReady();
	}
	jQuery(".fpd-container").on("ready", fpdReady);
});
