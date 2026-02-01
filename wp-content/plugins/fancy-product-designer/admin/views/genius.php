<?php

$error = null;
$license_info = null;
$license_key = get_option('fpd_genius_license_key', $GLOBALS['FPD_GENIUS_LICENSE_KEY']);


if (isset($_POST['_fpdnonce']) && wp_verify_nonce($_POST['_fpdnonce'], 'register_license')) {

	if (isset($_POST['fpd_register_license'])) {

		$license_key = preg_replace( '/[^A-Z0-9-]/', '', trim($_POST['fpd_genius_license']) );

		if (!empty($license_key)) {

			$res = fpd_genius_request(
				'client/register',
				$license_key,
				'PATCH',
				array(
					'domain' => fpd_get_domain_from_url( get_site_url() )
				)
			);
						
			if (!empty($res)) {
				$license_info = $res['message'];
			}

			update_option('fpd_genius_license_key', $license_key);

		} else {

			$error = __('Please enter a valid license key!', 'radykal');

		}

	} else if (isset($_POST['fpd_deregister_license'])) {

		$res = fpd_genius_request(
			'client/unregister',
			$license_key,
			'PATCH',
			array(
				'domain' => fpd_get_domain_from_url( get_site_url() )
			)
		);
		

		if (!empty($res)) {
			$license_info = $res['message'];
		}

		update_option('fpd_genius_license_key', '');
		$license_key = '';

	}
	else if (isset($_POST['fpd_license_status'])) {

		$res = fpd_genius_request(
			'client',
			$license_key,
		);
		
		if (!empty($res)) {
			$license_info = $res['message'];
		}

	}

}

?>
<br>
<div class="wrap" id="fpd-manage-status">

	<?php if ($error): ?>
		<div class="ui error message">
			<?php echo $error; ?>
		</div>
	<?php endif; ?>

	<div class="ui segment">
		<h3>
			<?php _e('What is Genius', 'radykal') ?>
		</h3>
		<p style="font-size: 18px;">
			<?php _e('Genius integrates a suite of professional services designed to enhance the functionality of Fancy Product Designer, including our enhanced <b>PRO Export</b> feature. 
					<br>Furthermore, we offer specialized services powered by artificial intelligence (AI), including "<b>Remove Background</b>", "<b>Image Upscaling</b>", and "<b>Text to Image</b>" features.</p>
		', 'radykal'); ?>
		</p>
		<p>
			<iframe width="800" height="450" src="https://www.youtube.com/embed/fygt_ut_DiQ?si=DZ20Xpp1zykhSTHu" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
		</p>
	</div>

	<div class="ui segment">
		<h3>
			<?php _e('Get a license for Genius', 'radykal') ?>
		</h3>
		<div class="ui equal width grid">
			<div class="column">
				<div class="ui raised segments">
					<div class="ui center aligned inverted segment">
						<h3 class="ui header">Pro</h3>
					</div>
					<div class="ui center aligned secondary segment">
						<div class="ui statistic">
							<div class="value">
								19€*
							</div>
							<div class="label">
								per month
							</div>
						</div>
					</div>
					<div class="ui segment">
						<h4>Pro Export</h4>
						<ul class="ui tiny list">
							<li><b>Print-ready files</b>: PDF, PNG, JPEG</li>
							<li>Define <b>DPI</b></li>
							<li><b>Exclude Layers</b> From Export</li>
							<li><b>Automated Export</b>: Send print file via Mail</li>
							<li><b>Cloud Storage</b>: Store print file in Dropbox or AWS S3</li>
							<li><b>Printful Integration</b>: Design & sell Printful products</li>
						</ul>
					</div>
				</div>
				<a 
					class="ui primary fluid button" 
					href="https://elopage.com/s/radykal/genius-dec00fd9/payment?plan_id=384533&locale=en" 
					target="_blank"
				>
					Get License**
				</a>
			</div>
			<div class="column">
				<div class="ui raised segments">
					<div class="ui center aligned inverted segment">
						<h3 class="ui header">Premium</h3>
					</div>
					<div class="ui center aligned secondary segment">
						<div class="ui statistic">
							<div class="value">
								39€*
							</div>
							<div class="label">
								per month
							</div>
						</div>
					</div>
					<div class="ui segment">
						<h4>All Features of Pro plan</h4>
					</div>
					<div class="ui segment">
						<h4>AI-Powered Features (3000 requests/month)</h4>
						<ul class="ui tiny list">
							<li><b>Remove Background</b>: Your customer can remove the background of bitmap images.</li>
							<li><b>Upscale Image</b>: Your customer can upscale an uploaded image for better print quality.</li>
							<li><b>Text to Images</b>: Your customer can create images by description.</li>
						</ul>
					</div>
					<div class="ui segment">
						<h4>Full Access to our Assets Library</h4>
						<ul class="ui tiny list">
							<li><b><a href="https://fancyproductdesigner.com/features/templates-library/" target="_blank">Premium Templates</a></b>: Create ready-to-use products from our pre-made templates.</li>
							<li><b><a href="https://fancyproductdesigner.com/features/3d-preview/" target="_blank">3D Models</a></b>: Visualize the custom design in a 3D model.</li>
						</ul>
					</div>
				</div>
				<a 
					class="ui primary fluid button" 
					href="https://elopage.com/s/radykal/genius-dec00fd9/payment?plan_id=384534&locale=en" 
					target="_blank"
				>
					Get License**
				</a>
			</div>
			<div class="column">
				<div class="ui raised segments">
					<div class="ui center aligned inverted segment">
						<h3 class="ui header">Pro Lifetime</h3>
					</div>
					<div class="ui center aligned secondary segment">
						<div class="ui statistic">
							<div class="value">
								249€*
							</div>
							<div class="label">
								Lifetime
							</div>
						</div>
					</div>
					<div class="ui segment">
						Get all the features of our monthly Pro plan forever with a one-time purchase of our lifetime license.
					</div>
				</div>
				<a 
					class="ui primary fluid button" 
					href="https://elopage.com/s/radykal/genius-dec00fd9/payment?plan_id=395473&locale=en" 
					target="_blank"
				>
					Get License**
				</a>
			</div>
		</div>
		
		<div class="ui message">
			Please remember to cancel your current subscription before switching to a different plan. Manage your subscription <a href="http://elopage.de/" target="_blank">here</a>.
		</div>
		<p>* Net price</p>
		<p>** Pay easily using our trusted partner, Elopage.</p>
	</div>
	
	<?php if( !$GLOBALS['FPD_GENIUS_DISABLE_MANAGE_LICENSE'] ): ?>
	<div class="ui segment">
		<h3>
			<?php _e('Manage Genius License', 'radykal') ?>
		</h3>
		<p>Attention: Enter only the license code for our Genius service, not the Envato Purchase Code!</p>
		<form method="post" class="ui form">
			<div class="field">
				<?php echo apply_filters( 
					'fpd_backend_genius_license_input', 
					'<input type="text" name="fpd_genius_license"
						placeholder="' . __('Enter your License Key', 'radykal') .'"
						value="'. esc_attr( $license_key ) .'" 
						'. wp_readonly( empty($license_key), false, false ) .'
					/>',
					$license_key 
				); ?>
			</div>
			<div class="field">
				<?php if (empty($license_key)): ?>
					<button type="submit" name="fpd_register_license" class="ui small primary button">
						<?php  _e('Register License', 'radykal'); ?>
					</button>
				<?php endif; ?>
				<?php if (!empty($license_key)): ?>
					<button type="submit" name="fpd_license_status" class="ui small primary button">
						<?php _e('Get License Status', 'radykal'); ?>
					</button>
					<button type="submit" name="fpd_deregister_license" class="ui small secondary button"
						data-tooltip="Deregister this license to use for another domain.">
						<?php _e('Deregister License', 'radykal'); ?>
					</button>
				<?php endif; ?>
			</div>
			<?php wp_nonce_field('register_license', '_fpdnonce'); ?>
		</form>
		<?php if (!empty($license_info)): ?>
			<p class="ui tiny message">
				<?php echo $license_info; ?>
			</p>
		<?php endif; ?>
	</div>
	<?php endif; ?>
	<?php do_action( 'fpd_backend_genius_html_end' ); ?>
</div>
<?php 

