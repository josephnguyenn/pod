<?php 
$contactFormUrl = ReadMoreAdminHelper::getPluginActivationUrl('contact-form-master');
$countdownUrl = ReadMoreAdminHelper::getPluginActivationUrl('countdown-builder');
$downloaderURL = ReadMoreAdminHelper::getPluginActivationUrl('ydn-download');
$scrollToTop = ReadMoreAdminHelper::getPluginActivationUrl('scroll-to-top-builder');
$randomNumbers = ReadMoreAdminHelper::getPluginActivationUrl('random-numbers-builder');
?>
<h1>Feature plugins</h1>
<div class="plugin-group" id="yrm-plugins-wrapper">
	<div class="plugin-card">
		<div class="plugin-card-top">
			 <a href="https://wordpress.org/plugins/countdown-builder/" target="_blank" class="plugin-icon"><div class="plugin-icon" id="plugin-icon-countdown"></div></a>
			 <div class="name column-name">
				<h4>
					<a href="https://wordpress.org/plugins/countdown-builder/" target="_blank">Countdown</a>
				 	<div class="action-links">
				 		<span class="plugin-action-buttons">
					 		<a class="install-now button" data-slug="countdown-builder" href="<?php echo esc_url($countdownUrl); ?>">Install Now</a>
					 	</span>
				 	</div>
				</h4>
			 </div>
			<div class="desc column-description">
				<p>Countdown builder – Customizable Countdown Timer</p>
				<p class="column-compatibility"><span class="compatibility-compatible"><strong>Compatible</strong> with your version of WordPress</span></p>
			</div>
		</div>
	</div>
</div>