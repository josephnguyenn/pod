<?php
class RadMoreAjax {
	
	public function init() {
		add_action('wp_ajax_delete_rm', array($this, 'deleteRm'));
		add_action('wp_ajax_yrm_delete_readmores', array($this, 'deleteReadMores'));
		add_action('wp_ajax_yrm_type_delete', array($this, 'typeDelete'));
		add_action('wp_ajax_yrm_switch_status', array($this, 'switchStatus'));
		add_action('wp_ajax_yrm_far_status', array($this, 'farStatus'));
		add_action('wp_ajax_yrm_export', array($this, 'exportData'));
		add_action('wp_ajax_yrm_import_data', array($this, 'importData'));

		// review panel
		add_action('wp_ajax_yrm_dont_show_review_notice', array($this, 'dontShowReview'));
		add_action('wp_ajax_yrm_change_review_show_period', array($this, 'changeReviewPeriod'));

		add_action('wp_ajax_yrm_support', array($this, 'support'));
		add_action('wp_ajax_expander_storeSurveyResult', array($this, 'surveyResult'));

		add_action('wp_ajax_yrm_add_accordion', array($this, 'addAccordion'));
	}

	public function surveyResult() {
		check_ajax_referer('readMoreAjaxNonce', 'token');

		echo 1;
		die;
	}

	public function support() {
		check_ajax_referer('YrmNonce', 'ajaxNonce');

		echo true;
		die();
	}

	public function changeReviewPeriod() {
		check_ajax_referer('YrmNonce', 'ajaxNonce');
		$messageType = sanitize_text_field($_POST['messageType']);

		$timeDate = new DateTime('now');
		$timeDate->modify('+'.YRM_SHOW_REVIEW_PERIOD.' day');

		$timeNow = strtotime($timeDate->format('Y-m-d H:i:s'));
		update_option('YrmShowNextTime', $timeNow);
		$usageDays = get_option('YrmUsageDays');
		$usageDays += YRM_SHOW_REVIEW_PERIOD;
		update_option('YrmUsageDays', $usageDays);

		echo YCD_AJAX_SUCCESS;
		wp_die();
	}

	public function dontShowReview() {
		check_ajax_referer('yrmReviewNotice', 'ajaxNonce');
		update_option('YrmDontShowReviewNotice', 1);

		echo 1;
		wp_die();
	}

	public function importData()
	{
		check_ajax_referer('YrmNonce', 'ajaxNonce');

		if (!isset($_POST['attachmentUrl']) || !is_string($_POST['attachmentUrl'])) {
			wp_send_json_error('Invalid attachment URL.');
		}

		$url = esc_url_raw($_POST['attachmentUrl']);

		if (!filter_var($url, FILTER_VALIDATE_URL)) {
			wp_send_json_error('Invalid URL format.');
		}

		$response = wp_remote_get($url);

		if (is_wp_error($response)) {
			wp_send_json_error('Failed to fetch the file.');
		}

		$body = wp_remote_retrieve_body($response);
		$contents = json_decode($body, true);

		if (json_last_error() !== JSON_ERROR_NONE || !is_array($contents)) {
			wp_send_json_error('Invalid data format.');
		}

		global $wpdb;
		$wpdb->show_errors(false);

		foreach ($contents as $tableName => $tableData) {
			if (!is_array($tableData)) {
				continue;
			}

			foreach ($tableData as $rowData) {
				if (!is_array($rowData)) {
					continue;
				}

				$insertResult = $wpdb->insert($wpdb->prefix . $tableName, $rowData);

				if ($insertResult === false) {
					error_log('Failed to insert data into table: ' . $wpdb->prefix . $tableName);
				}
			}
		}

		wp_send_json_success('Data import successful.');
	}



	public function exportData() {
		check_ajax_referer('YrmNonce', 'ajaxNonce');
		global $wpdb;
		$data = array();
		
		$tables = array('expm_maker', 'expm_maker_pages');
		
		foreach ($tables as $table) {
			$dataSql = 'SELECT * FROM '.$wpdb->prefix.$table;
			$getAllData = $wpdb->get_results($dataSql, ARRAY_A);
			$currentTable = array();
	
			foreach ($getAllData as $currentData) {
				$currentTable[] = $currentData;
			}
	
			$data[$table] = $currentTable;
		}
	
		// Set correct content headers if downloading
		header('Content-Type: application/json');
		echo json_encode($data, JSON_PRETTY_PRINT);
		wp_die();
	}	
	
	public function deleteRm() {

		check_ajax_referer('YrmNonce', 'ajaxNonce');
		$id  = (int)$_POST['readMoreId'];
		
		$this->deleteById($id);
		
		echo '';
		die();
	}
	
	public function deleteById($id) {
		$dataObj = new ReadMoreData();
		$dataObj->setId($id);
		$dataObj->delete();
		
		do_action('YrmDeleteReadMore', $id);
	}
	
	public function deleteReadMores() {
		check_ajax_referer('YrmNonce', 'ajaxNonce');
		$list = $_POST['idsList'];
		
		if (is_array($list) && !empty($list)) {
			foreach ($list as $currentId) {
				$this->deleteById((int)$currentId);
			}
		}
		
		echo '1';
		wp_die();
	}

	public function typeDelete() {

		check_ajax_referer('YrmNonce', 'ajaxNonce');
		$id  = (int)$_POST['id'];

		$typeObj = ReadMore::createObjByType('far');
		$typeObj->setSavedId($id);
		$typeObj->delete();

		do_action('YrmTypeDeleteReadMore', $id);

		echo '';
		die();
	}

	public function switchStatus() {
		check_ajax_referer('YrmNonce', 'ajaxNonce');
		$postId = (int)$_POST['readMoreId'];
		$status = -1;

		if ($_POST['isChecked'] == 'true') {
			$status = true;
		}
		update_option('yrm-read-more-'.esc_attr($postId), $status);
		wp_die();
	}

	public function farStatus() {
		check_ajax_referer('YrmNonce', 'ajaxNonce');
		$postId = (int)$_POST['id'];
		$status = 0;

		if ($_POST['isChecked'] == 'true') {
			$status = 1;
		}
		global $wpdb;
		$prepare = $wpdb->prepare('UPDATE '.esc_attr($wpdb->prefix).YRM_FIND_TABLE.' SET enable = %s WHERE id=%d', $status, $postId);
		$wpdb->query($prepare);
		echo 1;
		wp_die();
	}

	public function addAccordion() {
		check_ajax_referer('YrmNonce', 'ajaxNonce');
		ob_start();
		$key = (int)$_GET['nextIndex'];
		$tab = array('label' => 'Tab '.($key+1), 'content' => 'Content');
		include(YRM_VIEWS.'/accordion/ItemTemplateWrapper.php');
		$content = ob_get_contents();
		ob_end_clean();

		echo wp_kses($content, ReadMoreAdminHelper::getAllowedTags());
		wp_die();
	}
}