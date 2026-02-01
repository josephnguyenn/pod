<?php
use yrm\Tickbox;

class ReadMoreFilters
{
	private $isLoadedMediaData = false;

	public function isLoadedMediaData() {
		return $this->isLoadedMediaData;
	}

	public function setIsLoadedMediaData($isLoadedMediaData) {
		$this->isLoadedMediaData = $isLoadedMediaData;
	}

	public function __construct()
	{
		$this->init();
	}

	public function yrmMediaButton()
	{
		$isLoadedMediaData = $this->isLoadedMediaData();
		new Tickbox(true, $isLoadedMediaData);
	}

	public function init()
	{
		$this->shortcodeButtons();
	}

	private function shortcodeButtons()
	{
		if (get_option('yrm-hide-media-buttons')) {
			return false;
		}
		add_filter('mce_external_plugins', array($this, 'editorButton'));
		add_action('media_buttons', array($this, 'yrmMediaButton'));
		add_filter('upload_mimes', array($this, 'yrm_allow_json_uploads'));
		add_filter('wp_check_filetype_and_ext', array($this, 'wp_check_filetype_and_ext'), 10, 4);

		return true;
	}

	public function wp_check_filetype_and_ext($data, $file, $filename, $mimes) {
		$filetype = wp_check_filetype($filename, $mimes);
		return [
			'ext'             => $filetype['ext'],
			'type'            => $filetype['type'],
			'proper_filename' => $data['proper_filename'],
		];
	}

	public function yrm_allow_json_uploads($mimes) {
	    $mimes['json'] = 'application/json';
	    return $mimes;
	}

	public function editorButton($buttons)
	{
		$buttons['readMoreButton'] = YRM_ADMIN_JAVASCRIPT.'yrm-tinymce-plugin.js';
		$this->yrmMediaButton();

		return $buttons;
	}
}

new ReadMoreFilters();