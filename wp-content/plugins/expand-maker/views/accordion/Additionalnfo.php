<div class="panel panel-default">
    <div class="panel-heading"><?php _e('Info', YRM_LANG);?></div>
    <div class="panel-body yrm-upgrade-pro-wrapper">
        <div style="margin-bottom: 20px;">
            <p class="yrm-upgrade-pro">
               <br><b>Do you have a question</b>?<br>
            </p>
            <?php echo wp_kses(ReadMoreAdminHelper::newIdeasButton('Contact us'), ReadMoreAdminHelper::getAllowedTags())?>
        </div>
        <div class="row form-group">
            <div class="col-md-12">
                <label><?php _e('Join our Telegram Group')?></label>
            </div>
            <div class="col-md-12">
                <div class="yrm-tooltip" style="display: block !important;">
                    <span class="yrm-tooltiptext" id="yrm-tooltip"><?php _e('Copy to clipboard', YRM_LANG)?></span>
                    <input type="text" id="expm-shortcode-info-div" class="widefat" readonly="readonly" value='<?php echo esc_attr("@wpReadMore"); ?>'>
                </div>
            </div>
        </div>
		<div class="yrm-telegram-image"></div>

    </div>
</div>