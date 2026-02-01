<?php
require_once(FPD_PLUGIN_ADMIN_DIR.'/views/modal-shortcodes.php');
?>
<br>

<div class="wrap" id="fpd-manage-status">

	<h1><?php esc_html_e( 'Utilities', 'radykal'); ?></h1>

	<h3><?php esc_html_e( 'Tools', 'radykal'); ?></h3>
	<table class="fpd-status-table ui striped table">
		<tbody>
			<tr>
				<td class="four wide">
					<em><?php esc_html_e( 'Shortcodes', 'radykal'); ?></em>
					<span data-variation="tiny" data-tooltip="<?php esc_attr_e('All shortcodes that comes with Fancy Product Designer.', 'radykal'); ?>"><i class="mdi mdi-information-outline icon"></i></span>
				</td>
				<td colspan="2">
					<span class="ui secondary tiny button" id="fpd-open-shortcode-builder">
						<?php _e('Open Shortcode Builder', 'radykal'); ?>
					</span>
				</td>
			</tr>
			<tr>
				<td>
					<em><?php _e('Migrate Images', 'radykal'); ?></em>
					<span data-variation="tiny" data-tooltip="<?php esc_attr_e('Use this tool when you move your site to another domain or the protocol has been updated.', 'radykal'); ?>">
						<i class="mdi mdi-information-outline icon"></i>
					</span>
				</td>
				<td class="ui fluid input">
					<input type="text" id="fpd-old-domain" class="widefat" placeholder="<?php esc_attr_e('Enter the old domain incl. protocol (http or https), e.g. https://domain.com', 'radykal'); ?>" />
				</td>
				<td  class="two wide">
					<button class="ui secondary tiny button" id="fpd-reset-image-sources">
						<?php _e('Start Migration', 'radykal'); ?>
					</button>
				</td>
			</tr>
			<?php do_action( 'fpd_status_tools_table_end' ); ?>
		</tbody>
	</table>
	<div id="fpd-updated-infos" class="fpd-hidden">
		<h4><?php _e('Updated Entries in Database Tables'); ?></h4>
		<ul>
			<li><?php _e('Products'); ?>: <span id="fpd-updated-products"></span></li>
			<li><?php _e('Views'); ?>: <span id="fpd-updated-views"></span></li>
			<li><?php _e('Designs'); ?>: <span id="fpd-updated-designs"></span></li>
			<li><?php _e('Shortcode Orders'); ?>: <span id="fpd-updated-sc-orders"></span></li>
			<li><?php _e('WooCommerce Orders'); ?>: <span id="fpd-updated-wc-orders"></span></li>
		</ul>
	</div>
	<?php 

		$pj_limit = 5;
		$print_jobs_total = FPD_Print_Job::get_total();

		$pj_offset = 0;
		if( isset($_GET['print_job_offset'])	)
			$pj_offset = intval($_GET['print_job_offset']);

		$pj_offset = intval( $pj_offset );

		$print_jobs = FPD_Print_Job::get_print_jobs(
			array(
				'order_by' => 'ID',
				'limit' => $pj_limit,
				'offset' => $pj_offset * $pj_limit
			)
		);

	?>
	<h3><?php esc_html_e( 'Print Jobs', 'radykal'); ?> (Total: <?php echo $print_jobs_total; ?>)</h3>
	<p><?php esc_html_e( 'A list of all print jobs using the PRO export.', 'radykal'); ?></p>
	<table class="fpd-print-jobs-table ui striped table">
		<thead>
			<tr>
				<th class="one wide">
					<?php esc_html_e( 'ID', 'radykal'); ?>
				</th>
				<th class="two wide">
					<?php esc_html_e( 'GUID', 'radykal'); ?>
				</th>
				<th class="four wide">
					<?php esc_html_e( 'Details', 'radykal'); ?>
				</th>
				<th class="four wide">
					<?php esc_html_e( 'Data', 'radykal'); ?>
				</th>
				<th class="two wide">
					<?php esc_html_e( 'Status', 'radykal'); ?>
				</th>
				<th class="right aligned">
					<?php esc_html_e( 'Created', 'radykal'); ?>
				</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach($print_jobs as $print_job): ?>
				<tr>
					 <td><?php echo $print_job->ID; ?></td>
					 <td><?php echo $print_job->guid; ?></td>
					 <td><?php echo $print_job->details; ?></td>
					 <td class="four wide" style="word-break: break-all;"><?php echo $print_job->data; ?></td>
					 <td><?php echo $print_job->status; ?></td>
					 <td class="right aligned"><?php echo $print_job->created_at; ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
		<tfoot>
    		<tr>
				<th colspan="6">
					<div class="ui right floated pagination mini menu">
						<a 
							class="icon item <?php echo $pj_offset == 0 ? 'disabled': ''; ?>" 
							href="?page=fpd_utilities&print_job_offset=<?php echo $pj_offset-1; ?>"
						>
								<i class="left chevron icon"></i>
						</a>
						<a 
							class="icon item <?php echo ($pj_offset * $pj_limit + $pj_limit) > $print_jobs_total ? 'disabled': ''; ?>" 
							href="?page=fpd_utilities&print_job_offset=<?php echo $pj_offset+1; ?>"
						>
								<i class="right chevron icon"></i>
						</a>
					</div>
				</th>
			</tr>
		</tfoot>
	</table>

</div>