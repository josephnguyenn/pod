<?php
	$ajaxNonce = wp_create_nonce("YrmNonce");

	$pagenum = isset( $_GET['yrm-pagenum'] ) ? absint( $_GET['yrm-pagenum'] ) : 1;
	global $wpdb;

	$orderStatus = true;
	$arrowVisibility = ' yrm-visibility-hidden';
	$typeSortVisibility = ' yrm-visibility-hidden';
	$rotateClass = '';
	$orderSql = 'desc';
	$orderBySql = 'id';

	if (!empty($_GET['order'])) {
		$orderSql = esc_attr($_GET['order']);
		if ($_GET['orderby'] == 'id') {
			$arrowVisibility = '';
		}
		else if ($_GET['orderby'] == 'type') {
			$typeSortVisibility = '';
		}

		if ( $_GET['order'] == 'asc') {
			$orderStatus = false;
			$rotateClass = 'yrm-rotate-180';
		}
	}

	if (!empty($_GET['orderby'])) {
		$orderBySql = esc_attr($_GET['orderby']);
	}

	$limit = YRM_NUMBER_PAGES; // number of rows in page
	$offset = ($pagenum - 1) * $limit;
	$total = $wpdb->get_var("SELECT COUNT(`id`) FROM {$wpdb->prefix}expm_maker");
	$numOfPages = ceil($total/$limit);
	$results = ReadMoreData::getAllDataByLimit($offset, $limit, $orderBySql, $orderSql);
	$headerCheckbox = '<input type="checkbox" class="yrm-select-all yrm-table-manage-checkbox">';
	$allowedTag = ReadMoreAdminHelper::getAllowedTags();
?>
<div class="ycf-bootstrap-wrapper">
	
	<div class="expm-wrapper">
	<!-- <div class="read-more-promo" style="background: linear-gradient(135deg, #6a5acd, #3b5998); padding: 30px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); color: white; text-align: center; margin-bottom: 20px; display: flex; align-items: center; justify-content: center; flex-wrap: wrap;">
  <div style="max-width: 600px;">
    <h3 style="font-size: 26px; font-weight: bold; margin-bottom: 10px;">🔥 Share Your Feature Requests!</h3>
    <p style="font-size: 18px; line-height: 1.5; margin-bottom: 10px;">
		Do you want to get new features related to <strong>Read More</strong>?
    </p>
    <p style="font-size: 16px; margin-bottom: 20px;">We’re working hard and could add them as soon as possible in our next updates.</p>
    <p style="font-size: 16px; margin-bottom: 20px;">💡 Be free and share your new feature suggestions with us — your feedback makes the plugin better!</p>
    <a href="https://wordpress.org/support/plugin/expand-maker/" target="_blank" class="btn btn-primary" style="background-color: #ff6347; color: white; padding: 12px 25px; border-radius: 5px; text-decoration: none; font-size: 18px; transition: all 0.3s ease-in-out;">Send a Feature Request</a>
  </div>
</div> -->

<div class="read-more-promo" 
     style="
        background: linear-gradient(135deg, #ff0066, #ff8c00);
        padding: 35px;
        border-radius: 12px;
        box-shadow: 0 4px 14px rgba(0,0,0,0.15);
        color: white;
        text-align: center;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
     "
>
  <div style="max-width: 650px;">
    
    <h3 style="font-size: 30px; font-weight: 800; margin-bottom: 10px;">
      🎉 Black Friday Mega Sale — 50% OFF!
    </h3>

    <p style="font-size: 18px; margin-bottom: 10px; line-height: 1.5;">
      Upgrade your <strong>Read More</strong> plugin and unlock all PRO features for half the price!
    </p>

    <p style="font-size: 16px; margin-bottom: 20px;">
      💰 Offer ends <strong>December 3</strong> — Don’t miss out!
    </p>

    <a href="https://edmonsoft.com/" 
       target="_blank"
       style="
          background-color: #000;
          color: #fff;
          padding: 14px 30px;
          border-radius: 6px;
          text-decoration: none;
          font-size: 18px;
          font-weight: bold;
          transition: 0.3s ease;
          display: inline-block;
       "
       onmouseover="this.style.opacity='0.85'" 
       onmouseout="this.style.opacity='1'"
    >
      Get 50% OFF Now
    </a>

  </div>
</div>


<style>
  .read-more-promo a:hover {
    background-color: #ff4500;
    transform: scale(1.05);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
  }
</style>
		<div class="wrap">
			<h2 class="add-new-buttons"><?php _e('Read More', YRM_LANG); ?><a href="<?php echo YRM_TYPES_PAGE_URL;?>" class="add-new-h2"><?php echo _e('Add New', YRM_LANG); ?></a></h2>
		</div>
		<div class="yrm-export-import-wrapper">
			<img src="<?php echo YRM_IMG_URL.'ajax.gif'; ?>" class="yrm-spinner yrm-hide-content">
			<?php if ($total != 0 ): ?>
				<input type="button" class="yrm-exprot-button button" value="<?php echo  _e('Export', YRM_LANG)?>">
			<?php endif;?>
			<input type="button" class="yrm-import-button button" data-ajaxNonce="<?php echo esc_attr($ajaxNonce); ?>" value="<?php echo  _e('Import', YRM_LANG)?>">
		</div>
		<?php if(YRM_PKG == YRM_FREE_PKG): ?>
			<div class="main-view-upgrade main-upgreade-wrapper">
				<?php echo ReadMoreAdminHelper::upgradeButton(); ?>
			</div>
		<?php endif;?>
		<?php if ($total != 0 ): ?>
			<button class="btn btn-danger yrm-delete-read-mores" disabled="disabled">
				<span class="glyphicon glyphicon-trash"></span>
				<?php _e('Delete')?>
			</button>
		<?php endif;?>
		<table class="table table-bordered expm-table">
			<tr class="yrm-manage-col">
				<td class="manage-column column-id sortable">
					<?php echo wp_kses($headerCheckbox, $allowedTag); ?>
					<span class="yrm-manage-id-text">
						Id
						<span class="yrm-sorting-indicator <?php echo esc_attr($rotateClass).esc_attr($arrowVisibility); ?>" data-orderby="id" data-order="<?php echo esc_attr($orderStatus); ?>"></span>
					</span>
				</td>
				<td><?php _e('Title', YRM_LANG)?></td>
				<td><?php _e('Enabled', YRM_LANG)?></td>
				<td class="manage-column column-type sortable">
					<?php _e('Type', YRM_LANG)?>
					<span class="yrm-sorting-indicator <?php echo esc_attr($rotateClass).esc_attr($typeSortVisibility); ?>" data-orderby="type" data-order="<?php echo esc_attr($orderStatus); ?>"></span>
				</td>
				<td><?php _e('Options', YRM_LANG)?></td>
			</tr>

			<?php if(empty($results)) { ?>
				<tr>
					<td colspan="4"><?php _e('No Data', YRM_LANG)?></td>
				</tr>
			<?php }
			else {
				foreach ($results as $result) { ?>
					<?php
						$id = (int)$result['id'];
						$title = esc_attr($result['expm-title']);
						if (empty($title)) {
							$title = __('(no title)');
						}
						$type = esc_attr($result['type']);

					?>
					<tr>
						<td>
							<input id="yrm-select-<?php echo esc_attr($id); ?>" type="checkbox" class="yrm-readmore-id-checkbox yrm-table-manage-checkbox" value="<?php echo esc_attr($id); ?>">
							<?php echo esc_attr($id); ?>
						</td>
						<td><a href="<?php echo admin_url()."admin.php?page=button&yrm_type=".esc_attr($type)."&readMoreId=".esc_attr($id).""?>"><?php echo esc_attr($title); ?></a></td>
						<td>
							<?php $isChecked = (ReadMore::isActiveReadMore($id) ? 'checked': ''); ?>
							<div class="yrm-switch-wrapper">
								<label class="yrm-switch">
									<input type="checkbox" name="yrm-status-switch" data-id="<?= esc_attr($id); ?>" class="yrm-accordion-checkbox yrm-status-switch" <?php echo esc_attr($isChecked);?>>
									<span class="yrm-slider yrm-round"></span>
								</label>
							</div>
						</td>
						<td><?php echo ReadMoreAdminHelper::getTitleFromType($type); ?></td>
						<td>
							<a class="yrm-crud yrm-edit glyphicon glyphicon-edit" href="<?php echo admin_url()."admin.php?page=button&yrm_type=".esc_attr($type)."&readMoreId=".esc_attr($id).""?>"></a>
							<a class="yrm-crud yrm-delete-link glyphicon glyphicon-remove" data-id="<?php echo esc_attr($id);?>" href="<?php echo  wp_nonce_url(admin_url("admin.php?page=readMore&action=expmDeleteData&id=" . $id), 'delete_read_more')?>"></a>
							<a class="yrm-crud yrm-clone-link glyphicon glyphicon-duplicate" href="<?php echo admin_url();?>admin-post.php?action=read_more_clone&id=<?php echo esc_attr($id); ?>" ></a>
						</td>
					</tr>
			<?php } ?>

			<?php } ?>
			<tr class="yrm-manage-col">
				<td>
					<?php echo wp_kses($headerCheckbox, $allowedTag); ?>
					<span class="yrm-manage-id-text">
						<?php _e('Id', YRM_LANG)?>
					</span>
				</td>
				<td><?php _e('Title', YRM_LANG)?></td>
				<td><?php _e('Enabled', YRM_LANG)?></td>
				<td><?php _e('Type', YRM_LANG)?></td>
				<td><?php _e('Options', YRM_LANG)?></td>
			</tr>
		</table>
		<?php
			$page_links = paginate_links( array(
				'base' => add_query_arg( 'yrm-pagenum', '%#%' ),
				'format' => '',
				'prev_text' => __( '&laquo;', 'text-domain' ),
				'next_text' => __( '&raquo;', 'text-domain' ),
				'total' => $numOfPages,
				'current' => $pagenum
			) );

			if ( $page_links ) {
				echo '<div class="yrm-tablenav"><div class="yrm-tablenav-pages">' . wp_kses($page_links, ReadMoreAdminHelper::getAllowedTags()) . '</div></div>';
			}
		?>
	</div>
</div>