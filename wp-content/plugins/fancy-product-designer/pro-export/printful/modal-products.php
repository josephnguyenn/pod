<?php

	$max_variations_limit = defined( 'WC_MAX_LINKED_VARIATIONS' ) ? WC_MAX_LINKED_VARIATIONS : 50;

?>

<div class="ui dimmer modals page">
	<div class="ui modal active" id="fpd-modal-printful-products">

		<div class="ui clearing basic segment header">
			<div class="ui left floated basic segment">
				<h3 class="ui header">
					<?php _e('Printful Products', 'radykal'); ?>
					<div class="sub header"><?php _e('Select a Printful product from the Printful catalog. After the Printful product is imported, your WooCommerce product will have the variations from the Printful product variants.', 'radykal'); ?></div>
				</h3>
			</div>
		</div>

		<div class="content" id="fpd-printful-content-products">

			<div class="ui grid">

				<div class="four wide column">

					<div class="ui relaxed divided list"></div>
					<hr />
					<div class="ui input">
						<input type="text" placeholder="<?php _e( 'Search Products...', 'radykal'); ?>">
					</div>

				</div>

				<div class="twelve wide column">

					<div class="ui two cards"></div>

				</div>

			</div>

		</div><!-- #fpd-printful-content-products -->

		<div class="scrolling content" id="fpd-printful-content-product-details">

			<div class="ui grid">

				<div class="row">

					<div class="fourteen wide column">

						<h4 class="ui header" data-placeholder="title"></h4>

					</div>
					<div class="two wide right aligned column">

						<span class="ui icon tiny button" id="fpd-printful-product-details-close">
							<i class="close icon"></i>
						</span>

					</div>

				</div>

				<div class="row">

					<div class="eight wide column">

						<div id="fpd-printful-gallery" data-placeholder="gallery">

							<div class="image-nav">
								<i class="angle left icon" data-slide="left"></i>
								<i class="angle right icon" data-slide="right"></i>
							</div>

						</div>

					</div>
					<div class="eight wide column">

						<h5 class="ui header"><?php _e('Description', 'radykal'); ?></h5>
						<p data-placeholder="desc"></p>

						<div class="ui divider"></div>

						<div id="fpd-printful-variant-attibutes">

							<h4 class="ui header">
								<?php _e('Total Variants:', 'radykal'); ?>
								<span data-placeholder="variants-count"></span>
							</h4>

							<div id="fpd-printful-details-colors">
								<h5 class="ui header"><?php _e('Colors', 'radykal'); ?></h5>
								<select data-id="colors" data-placeholder="<?php _e('Select Colors', 'radykal'); ?>" multiple>
								</select>
							</div>

							<div id="fpd-printful-details-sizes">
								<h5 class="ui header"><?php _e('Sizes', 'radykal'); ?></h5>
								<select data-id="sizes" data-placeholder="<?php _e('Select Sizes', 'radykal'); ?>" multiple>
								</select>
							</div>


						</div>

						<div class="ui negative message" id="fpd-printful-variants-msg-limit">
							<p><?php printf( __( 'Please decrease the amount of selected variants. Maximum %s variants can be imported.', 'radykal' ), $max_variations_limit); ?></p>
						</div>

						<div class="ui negative message" id="fpd-printful-variants-msg-empty">
							<p><?php _e('No variants are available for the selected region.', 'radykal'); ?></p>
						</div>

					</div>

				</div>


			</div>

		</div><!-- #fpd-printful-content-product-details -->

		<div class="actions">
			<span class="ui primary small button" id="fpd-printful-import-product">
				<?php _e('Import Product', 'radykal'); ?>
			</span>
		</div>

		<div class="ui active inverted dimmer">
			<div class="ui text loader"><?php _e('Loading...', 'radykal'); ?></div>
  		</div>

	</div>
</div>

<script type="text/javascript">

	jQuery(document).ready(function($) {

		var $modal = $('#fpd-modal-printful-products'),
			$cards = $modal.find('.ui.cards'),
			$productDetails = $modal.find('#fpd-printful-content-product-details'),
			currentProdutDetails = null,
			pfProductsLoaded = false,
			maxWooVariationLimit = <?php echo $max_variations_limit; ?>,
			selectedColors = [],
			selectedSizes = [];

		$modal.find('select').select2({
			width: '100%'
		})
		.on('change', function (evt) {

			var selected = $(this).children(':selected').map(function() { return this.value; }).get();

			if(this.dataset.id == 'colors') {
				selectedColors = selected;
			}
			else {
				selectedSizes = selected;
			}

			updateTotalVariants();

		});

		$('#fpd-printful-open-modal').click(function(evt) {

			evt.preventDefault();
			$modal.parent().addClass('active').css('display', 'flex !important');

			if(!pfProductsLoaded) {
				getPfProducts();
				pfProductsLoaded = true;
			}

		})
		//.click();

		$modal.parent().click(function(evt) {

			if($(evt.target).hasClass('modals')) {
				$modal.parent().removeClass('active').css('display', 'none !important');
			}

		})

		//select category
		$modal.on('click', '.four .list .item', function(evt) {

			evt.preventDefault();

			var $this = $(this),
				targetFilter = this.dataset.filter;

			if(targetFilter == 'all') {

				$cards.children('.card').show();

			}
			else {

				$cards.children('.card').hide()
				.filter('[data-filter="'+targetFilter+'"]').show();

			}

			$this.addClass('active')
			.siblings().removeClass('active');

		});

		//search products
		$modal.on('keyup', '.four input[type="text"]', function(evt) {

			evt.preventDefault();

			var searchQuery = this.value;

			if(this.value == '') {
				$cards.children('.card').show();
			}
			else {

				 $cards.children('.card').hide().filter(function(){
			     	return $(this).find('.fpd-printful-product-title').text().toLowerCase().includes(searchQuery.toLowerCase());

			    }).show();

			}

		});
		
		//load printful product
		$modal.on('click', '.grid .extra .button', function(evt) {

			evt.preventDefault();

			var $this = $(this);

			jQuery.ajax({
				url: "<?php echo admin_url('admin-ajax.php'); ?>",
				data: {
					action: 'fpd_get_printful_product',
					_ajax_nonce: "<?php echo FPD_Admin::$ajax_nonce; ?>",
					product_id: $this.parents('.card:first').data('id')
				},
				type: 'post',
				dataType: 'json',
				success: function(data) {					
                    
					if(data && data.product) {

						currentProdutDetails = data;

						$productDetails.find('[data-placeholder="title"]').html(data.product.model);

						$productDetails.find('[data-placeholder="gallery"]')
						.css('background-image', 'url('+data.product.image+')');
                        
                        if(data.product.description) {
                            
                            $productDetails.find('[data-placeholder="desc"]').html(
                                data.product.description.replace(/(?:\r\n|\r|\n)/g, '<br>')
                            );
                        }
                        $productDetails.find('[data-placeholder="desc"]').prev('.header').toggle(data.product.description);
						
						if(data.variants.length) {

							$modal.removeClass('fpd-pf-variants-empty');

							$productDetails.find('[data-placeholder="variants-count"]').text(_.size(data.variants));

							$productDetails.find('[data-placeholder="gallery"]')
							.children('.image-nav').toggle(data.variants[0].templates.length > 1);

							//colors
							var colors = [];
							_.each(data.variants, function(variant) {

								var cExist = _.find(colors, function(c) { return c.name == variant.color; });
								if(!cExist && variant.color_code) {
									colors.push({
										name: variant.color,
										code: variant.color_code
									})
								}

							})

							$productDetails.find('#fpd-printful-details-colors > select').empty();
							_.each(colors, function(color) {

								if(color) {
									$productDetails.find('#fpd-printful-details-colors > select')
									.append('<option value="'+color.code+'" selected>'+color.name.toUpperCase() +'</option>');
								}

							});

							$productDetails.find('#fpd-printful-details-colors')
							.toggle($productDetails.find('#fpd-printful-details-colors > select').children().length > 0)
							.children('select').trigger('change');


							//sizes
							var sizes = arrayColumn(data.variants, 'size').filter(arrayUnique);

							$productDetails.find('#fpd-printful-details-sizes > select').empty();
							_.each(sizes, function(size) {
								if(size) {
									$productDetails.find('#fpd-printful-details-sizes > select')
									.append('<option value="'+size+'" selected>'+size +'</option>');
								}
							});

							$productDetails.find('#fpd-printful-details-sizes')
							.toggle($productDetails.find('#fpd-printful-details-sizes > select').children().length > 0)
							.children('select').trigger('change');

						}
						else {
							$modal.addClass('fpd-pf-variants-empty');
						}

						$modal.addClass('fpd-product-details-enabled');

					}
					else if(data?.error) {
						alert(data.error)
						
					}

					$modal.children('.dimmer').removeClass('active');

				}

			});
			$modal.children('.dimmer').addClass('active');

		});

		$modal.on('click', '#fpd-printful-product-details-close', function(evt) {

			evt.preventDefault();
			$modal.removeClass('fpd-product-details-enabled');

		})

		var currentGalleryIndex = 0;
		$modal.on('click', '#fpd-printful-content-product-details .image-nav > i', function(evt) {

			evt.preventDefault();

			var slideDir = this.dataset.slide,
				$gallery = $productDetails.find('#fpd-printful-gallery'),
				templates = currentProdutDetails.variants[_.keys(currentProdutDetails.variants)[0]].templates;

			currentGalleryIndex = slideDir == 'left' ? currentGalleryIndex-1 : currentGalleryIndex+1;

			if(currentGalleryIndex < 0) {
				currentGalleryIndex = templates.length-1;
			}
			else if(currentGalleryIndex >= templates.length) {
				currentGalleryIndex = 0;
			}

			$gallery.css('background-image', 'url('+templates[currentGalleryIndex].image_url+')');

		});

		$('#fpd-printful-import-product').click(function(evt) {

			evt.preventDefault();

			$modal.children('.dimmer').addClass('active');
			importPfProduct(currentProdutDetails.product.id);

		});

		function setupProducts(products) {

			$modal.children('.dimmer').removeClass('active');			

			if(jQuery.isArray(products)) {

				var pf_cats = {all: {
					'name': 'All',
					'count': 0
				}};

				products.forEach(function(product) {

					//skip custom or EMBROIDERY products
					if( product.files.length !== 0 && product.type !== 'EMBROIDERY' ) {

						var key = product.type;

						//remove preview, label_outside and label_inside otherwise listed as view
						var views = _.filter(product.files, function(f) {
							return !['preview', 'label_outside', 'label_inside'].includes(f.id) && !f.id.includes('embroidery_');
						});

						if(views.length) {

							//setup categories
							pf_cats.all.count += 1;

							if( _.has(pf_cats, key) ) {
								pf_cats[key].count = pf_cats[key].count + 1;
							}
							else {
								pf_cats[key] = {
									name: product.type,
									count: 1
								};
							}

							$cards.append('<div class="ui card" data-id="'+product.id+'" data-filter="'+key+'"><div class="content"><div class="meta">#'+product.id+'</div><div class="fpd-printful-product-title">'+product.model+'</div></div><div class="image"><img src="'+product.image+'" /></div><div class="extra content"><div class="left floated">Variants: '+product.variant_count+'</div><div class="right floated">Views: '+String(views.length)+'</div></div><div class="extra content"><span class="ui primary tiny button">Load</span></div></div>');

						}

					}

				})

				_.each(pf_cats, function(cat, key) {
					$modal.find('.four .list').append('<a href="#" data-filter="'+key+'" class="item">'+cat.name+' ('+cat.count+')</a>');

				});

			}
			else if(products.error) {
				alert(products.error);
			}

		}

		function getPfProducts() {

			jQuery.ajax({
				url: fpd_admin_opts.adminAjaxUrl,
				data: {
					action: 'fpd_get_printful_products',
					_ajax_nonce: fpd_admin_opts.ajaxNonce,
				},
				type: 'post',
				dataType: 'json',
				success: function(data) {

					if(data) {
						setupProducts(data);
					}
					else {
						alert('Something went wrong. Please reload page and try again!');
					}

				}
			});

		}

		function importPfProduct(productId) {

			jQuery.ajax({
				url: fpd_admin_opts.adminAjaxUrl,
				data: {
					action: 'fpd_import_printful_product',
					_ajax_nonce: fpd_admin_opts.ajaxNonce,
					post_id: <?php echo $post->ID; ?>,
					printful_product_id: productId,
					include_colors: selectedColors,
					include_sizes: selectedSizes
				},
				type: 'post',
				dataType: 'json',
				success: function(data) {

					if(data && data.post_url) {
						window.open(data.post_url, '_self');
					}
					else {
						alert('Something went wrong. Please reload page and try again!');
					}

				}
			});

		};

		function updateTotalVariants() {

			if(currentProdutDetails && _.size(currentProdutDetails.variants)) {

				var totalVariants = 0;
				_.each(currentProdutDetails.variants, function(variant) {

					if($productDetails.find('#fpd-printful-details-colors').is(':visible')) {
						if(selectedColors.includes(variant.color_code) && selectedSizes.includes(variant.size)) {
							totalVariants++;
						}
					}
					else {
						if(selectedSizes.includes(variant.size)) {
							totalVariants++;
						}
					}

				});

				$productDetails.find('[data-placeholder="variants-count"]').text(totalVariants);

				$modal
				.toggleClass('fpd-pf-variants-limit', totalVariants > maxWooVariationLimit)
				.toggleClass('fpd-pf-hide-import-product', totalVariants == 0 || totalVariants > maxWooVariationLimit);

			}

		};

		function arrayColumn(array, columnName) {
		    return _.map(array, function(value,index) {
			    return columnName === undefined ? value : value[columnName];
		    })
		};

		function arrayUnique(value, index, self) {
			return self.indexOf(value) === index;
		};

	});

</script>
<style type="text/css">

	#fpd-modal-printful-products {
		position: relative;
	}

	#fpd-modal-printful-products .four.column .list a {
		text-decoration: none !important;
		box-shadow: none !important;
	}

	#fpd-modal-printful-products .four.column .list a.active {
		text-decoration: underline !important;
	}

	#fpd-modal-printful-products .four.column hr {
		margin: 15px 0;
	}

	#fpd-modal-printful-products .twelve.column {
		height: 65vh;
		overflow: auto;
	}

	.fpd-product-details-enabled #fpd-printful-content-product-details {
		display: block;
	}

	.fpd-product-details-enabled #fpd-printful-import-product {
		display: inline-block;
	}

	#fpd-printful-gallery {
		height: 300px;
		background-position: 50%;
		background-repeat: no-repeat;
		background-size: contain;
		position: relative;
		border: 1px solid rgba(0,0,0,.05);
	}

	#fpd-printful-gallery .image-nav {
		position: absolute;
		width: 100%;
		left: 0;
		top: 50%;
		font-size: 30px;
		transform: translateY(-50%);
	}

	#fpd-printful-gallery .image-nav > i {
		cursor: pointer;
		position: absolute;
		left: 10px;
		font-size: 20px;
	}

	#fpd-printful-gallery .image-nav > i:last-child {
		right: 10px;
		left: auto;
	}

	#fpd-printful-content-product-details .ui.label {
		margin-bottom: 5px;
	}

	#fpd-printful-content-product-details,
	#fpd-printful-import-product,
	.fpd-product-details-enabled #fpd-printful-content-products,
	#fpd-printful-content-product-details #fpd-printful-variants-msg-empty,
	#fpd-printful-content-product-details #fpd-printful-variants-msg-limit,
	.fpd-pf-variants-empty #fpd-printful-variant-attibutes,
	.fpd-pf-variants-empty #fpd-printful-import-product,
	.fpd-pf-hide-import-product #fpd-printful-import-product {
		display: none;
	}

	.fpd-pf-variants-empty #fpd-printful-content-product-details #fpd-printful-variants-msg-empty,
	.fpd-pf-variants-limit #fpd-printful-content-product-details #fpd-printful-variants-msg-limit {
		display: block;
	}

	#fpd-printful-variant-attibutes > h4 {
		margin-bottom: 20px;
		margin-top: 10px;
		display: block;
	}

	#fpd-printful-variant-attibutes h5 {
		margin: 20px 0 5px 0;
	}

	#fpd-printful-content-product-details .select2-selection__rendered {
		max-height: 100px;
		overflow: auto;
	}

</style>