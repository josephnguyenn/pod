<?php

class ReadMoreAccordionView {
	private $uniqid;
	private $typeObj;

	public function __construct($typeObj) {
		$this->uniqid = uniqid();
		$this->typeObj = $typeObj;
	}

	private function accordionItem($index, $value, $opened = false) {
		ob_start();
		if (empty($value['contentType'])) {
			$value['contentType'] = 'content';
		}
		$arrowClass = '';
		$showClass = '';
		$typeObj = $this->typeObj;
		$icons = $typeObj->getOptionValue('yrm-accordion-icons');
		list ($openClass, $closeClass) = explode('_', $icons);
		if ($opened) {
			list ($closeClass, $openClass) = explode('_', $icons);
			$arrowClass = '';
			$showClass = 'yrm-show';
		}
		$contentType = $value['contentType'];
		$content = '';
		if ($contentType == 'post' && !yrm_is_free()) {
			$content = ReadMoreAdminHelperPro::getPostContentById($value['post']);
		}
		else if($contentType == 'youtube') {
			$height = 'auto';
			if (!empty($value['iframeHeight'])) {
				$height = $value['iframeHeight'];
			}
			$content .= "<iframe style='width: 100%;height: ".esc_attr($height)."' src='".esc_attr($this->convert_to_embed_url($value['url']))."'></iframe>";
		}
		else {
			$content = do_shortcode($value['content']);
		}
	
		$iconPosition = $typeObj->getOptionValue('yrm-accordion-icons-position');
		$style = "style='float: left;'";
		if ($iconPosition === 'right') {
			$style = "style='float: right'";
		}
		else if ($iconPosition === 'hide') {
			$style = "display: none";
		}
		$icon = '<i class="fa '.esc_attr($arrowClass).' dashicons '.esc_attr($openClass).' accordion-header-icon " '.wp_kses($style, array('style')).'></i>';
		$allowedTag = ReadMoreAdminHelper::getAllowedTags();

		?>
		<div class="yrm-accordion-item yrm-accordion-item-<?php echo esc_attr($index); ?>" data-expanded="<?php echo esc_attr($opened);?>">
			<div class="yrm-accordion-item-header">
				<?php if ($iconPosition === 'left'): ?>
					<?php echo wp_kses($icon, $allowedTag); ?>
				<?php endif; ?>
				<span><?php echo esc_attr($value['label']); ?></span>
				<?php if ($iconPosition === 'right'): ?>
					<?php echo wp_kses($icon, $allowedTag); ?>
				<?php endif; ?>
			</div>
			<div class="yrm-accordion-item-content <?php echo esc_attr($showClass); ?>" >
				<?php echo wp_kses($content, $allowedTag); ?>
			</div>
		</div>
		<?php
		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}

	function convert_to_embed_url($url) {
		$parsed_url = parse_url($url);
		$host = $parsed_url['host'];
	
		// Check if the host is YouTube or other video platforms
		if (strpos($host, 'youtube.com') !== false || strpos($host, 'youtu.be') !== false) {
			parse_str($parsed_url['query'], $query_params);
			$video_id = $query_params['v'];
			$embed_url = 'https://www.youtube.com/embed/' . $video_id;
		} elseif (strpos($host, 'vimeo.com') !== false) {
			$path_parts = explode('/', trim($parsed_url['path'], '/'));
			$video_id = end($path_parts);
			$embed_url = 'https://player.vimeo.com/video/' . $video_id;
		} else {
			// Handle other platforms or default
			$embed_url = $url;
		}
	
		return $embed_url;
	}

	private function getRenderOptions() {
		$typeObj = $this->typeObj;
		$names = array(
			'yrm-accordion-activate-event',
			'yrm-accordion-keep-extended',
			'yrm-accordion-toggle-sound',
			'yrm-accordion-animate-easings',
			'yrm-accordion-animate-duration',
			'yrm-accordion-icons',
			'yrm-accordion-scroll-to-active-item'
		);

		$options = array();
		foreach ($names as $name) {
			$options[$name] = $typeObj->getOptionValue($name);
		}
        $options['sound-url'] = $typeObj->getOptionValue('yrm-accordion-click-sound');
		return $options;
	}

	private function enqueScripts() {

		wp_register_style('yrmaccordion.css', YRM_CSS_URL.'accordion/accordion.css');
		wp_enqueue_style('yrmaccordion.css');

		$accordionOptions = $this->getRenderOptions();

		wp_register_script('YrmAccordion.js', YRM_JAVASCRIPT.'accordion/YrmAccordion.js');
		wp_localize_script('YrmAccordion.js', 'YRM_ACCORDION_OPTIONS', $accordionOptions);
		wp_enqueue_script('YrmAccordion.js');
	}

	private function getStyles() {
		$typeObj = $this->typeObj;

		$id = $typeObj->getId();
		$enableMaxHeight = $typeObj->getOptionValue('yrm-accordion-enable-max-height');
		$maxHeight = $typeObj->getOptionValue('yrm-accordion-max-height');
		$cursor = $typeObj->getOptionValue('yrm-accordion-cursor');
		$content = '';

		if (!empty($enableMaxHeight)) {
			$content .= "<style>
				.yrm-accordion-wrapper-" . esc_attr($id) . " .yrm-accordion-item-content {max-height: " . esc_attr(ReadMoreAdminHelper::getCSSSafeSize($maxHeight)) . "}
			</style>";
		}
		if ($cursor) {
			$content .= "<style>
				.yrm-accordion-wrapper-" . esc_attr($id) . " .yrm-accordion-item-header {cursor: " . esc_attr($cursor) . "}
			</style>";
		}

		return $content;
	}

	public function __toString() {
		$typeObj = $this->typeObj;
		$mode = $typeObj->getOptionValue('yrm-accordion-mode');

		$id = $typeObj->getId();
		$this->enqueScripts();
		$accordionOptions = $this->getRenderOptions();
		$allowedTag = ReadMoreAdminHelper::getAllowedTags();
		$items = 0;
		ob_start();
		?>
		<div class="yrm-accordion-wrapper yrm-accordion-wrapper-<?php echo esc_attr($id) ?>" id="<?php echo esc_attr($this->uniqid); ?>" data-options='<?php echo wp_kses(json_encode($accordionOptions,  JSON_FORCE_OBJECT, JSON_HEX_QUOT), ReadMoreAdminHelper::getAllowedTags())?>'>
			<?php foreach ($typeObj->getOptionValue('yrm-accordion') as $index => $value): ?>
				<?php
					$opened = false;
					if ($mode == 'firstOpen' && $items == 0) {
						$opened = true;
					}
					else if ($mode == 'allOpen') {
						$opened = true;
					}
					$items += 1;
				?>
				<?php echo wp_kses($this->accordionItem($index, $value, $opened), $allowedTag); ?>
			<?php endforeach;?>
		</div>
		<?php
		$content = ob_get_contents();
		ob_end_clean();
		
		$content .= "<style>".$this->typeObj->getOptionValue('yrm-accordion-custom-css')."</style>";
		$content .= "<script>".$this->typeObj->getOptionValue('yrm-accordion-custom-js')."</script>";

		$obj = $this->typeObj;
		$content .= apply_filters("yrm_accordion_advanced_styles", "", $obj);
		$content .= "<style>.yrm-accordion-wrapper-".esc_attr($id)." .accordion-header-icon {font-size: ".esc_attr($typeObj->getOptionValue('yrm-accordion-icons-size'))." !important;}</style>";
		$content .= $this->getStyles();

		return $content;
	}
}