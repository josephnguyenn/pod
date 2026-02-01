<?php
$params = ReadMoreData::params();
$timeZones = ReadMoreData::getTimeZones();
?>
<div class="panel panel-default">
	<div class="panel-heading"><?php _e('Advanced showing settings', YRM_LANG);?></div>
	<div class="panel-body yrm-pro-options-wrapper">
		<?php echo wp_kses(ReadMoreAdminHelper::upgradeContent(), ReadMoreAdminHelper::getAllowedTags()); ?>
        <div class="row row-static-margin-bottom">
			<div class="col-xs-5">
				<label class="control-label-checkbox" for="yrm-accordion-show-only-devices"><?php _e('Show On Selected Devices', YRM_LANG);?>:</label>
			</div>
			<div class="col-xs-4">
                <div class="yrm-switch-wrapper">
                    <label class="yrm-switch">
                        <input type="checkbox" name="yrm-accordion-show-only-devices" id="yrm-accordion-show-only-devices" class="yrm-accordion-checkbox" <?php echo esc_attr($this->getOptionValue('yrm-accordion-show-only-devices', true)); ?>>
                        <span class="yrm-slider yrm-round"></span>
                    </label>
                </div>
			</div>
		</div>
        <div class="yrm-accordion-content yrm-hide-content">
			<div class="row row-static-margin-bottom">
				<div class="col-xs-5">
					<label class="control-label-checkbox" for="hover-effect"><?php _e('Select device(s)', YRM_LANG);?>:</label>
				</div>
				<div class="col-xs-4">
					<?php echo wp_kses($functions::yrmSelectBox($params['devices'], $this->getOptionValue('yrm-accordion-selected-devices'), array('name'=>"yrm-accordion-selected-devices[]", 'multiple'=>'multiple', 'class'=>'yrm-js-select2')), $allowedTag);?>
				</div>
			</div>
		</div>
		<div class="row row-static-margin-bottom">
			<div class="col-xs-5">
				<label class="control-label-checkbox" for="yrm-accordion-show-date-range"><?php _e('Show On Date Range', YRM_LANG);?>:</label>
			</div>
			<div class="col-xs-4">
                <div class="yrm-switch-wrapper">
                    <label class="yrm-switch">
                        <input type="checkbox" name="yrm-accordion-show-date-range" id="yrm-accordion-show-date-range" class="yrm-accordion-checkbox" <?php echo esc_attr($this->getOptionValue('yrm-accordion-show-date-range', true)); ?>>
                        <span class="yrm-slider yrm-round"></span>
                    </label>
                </div>
			</div>
		</div>
		<div class="yrm-accordion-content yrm-hide-content">
			<div class="row row-static-margin-bottom">
				<div class="col-xs-5">
					<label class="control-label-checkbox" for="yrm-accordion-rm-time-zone"><?php _e('Select timezone', YRM_LANG);?>:</label>
				</div>
				<div class="col-xs-4">
					<?php echo wp_kses($functions::yrmSelectBox($timeZones, $this->getOptionValue('yrm-accordion-rm-time-zone'), array('name'=>"yrm-accordion-rm-time-zone",  'class'=>'yrm-js-select2')), $allowedTag);?>
				</div>
			</div>
			<div class="row row-static-margin-bottom">
				<div class="col-xs-5">
					<label class="control-label-checkbox" for="yrm-accordion-rm-start-date"><?php _e('Start date', YRM_LANG);?>:</label>
				</div>
				<div class="col-xs-4">		
					<input type="text" id="yrm-accordion-rm-start-date" class="form-control yrm-date-time-picker" name="yrm-accordion-rm-start-date" placeholder="Start date" value="<?php echo esc_attr($this->getOptionValue('yrm-accordion-rm-start-date')); ?>">
				</div>
			</div>
			<div class="row row-static-margin-bottom">
				<div class="col-xs-5">
					<label class="control-label-checkbox" for="yrm-accordion-rm-end-date"><?php _e('End date', YRM_LANG);?>:</label>
				</div>
				<div class="col-xs-4">		
					<input type="text" id="yrm-accordion-rm-end-date" class="form-control yrm-date-time-picker" name="yrm-accordion-rm-end-date" placeholder="End Date" value="<?php echo esc_attr($this->getOptionValue('yrm-accordion-rm-end-date')); ?>">
				</div>
			</div>
		</div>
	</div>
</div>