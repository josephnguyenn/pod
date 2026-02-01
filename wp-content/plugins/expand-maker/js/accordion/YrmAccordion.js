function YrmAccordion() {
	this.options = {};
}

YrmAccordion.start = function () {
	jQuery('.yrm-accordion-wrapper').each(function () {
		var options = jQuery(this).data('options');
		var obj = new YrmAccordion()
		obj.options = options;
		obj.init();
	})
}

YrmAccordion.prototype.init = function () {
    var event = this.options['yrm-accordion-activate-event'] || 'click';
    var easings = this.options['yrm-accordion-animate-easings'];
    var duration = parseInt(this.options['yrm-accordion-animate-duration'], 10);
    var keepOpen = this.options['yrm-accordion-keep-extended'] === true;
    var enableClickSound = this.options['yrm-accordion-toggle-sound'];
    var scrollToActive = this.options['yrm-accordion-scroll-to-active-item'];

    var audio;
    if (enableClickSound && this.options['sound-url']) {
        audio = new Audio(this.options['sound-url']);
    }

    // Cache commonly used DOM elements
    var $accordionItems = jQuery('.yrm-accordion-item');
    var $accordionItemHeaders = jQuery('.yrm-accordion-item-header');

    // Scroll to active item if the option is enabled
    if (scrollToActive) {
        var $firstExpandedItem = $accordionItems.filter("[data-expanded='1']").first();
        if ($firstExpandedItem.length) {
            jQuery([document.documentElement, document.body]).animate({
                scrollTop: $firstExpandedItem.offset().top
            }, 1000);
        }
    }

    // Retrieve icons only once, not on every click
    var $accordionWrapper = jQuery(".yrm-accordion-wrapper");
    var icons = $accordionWrapper.data('options')['yrm-accordion-icons'].split('_');
    var openClass = icons[0];
    var closeClass = icons[1];

    // Helper function to toggle accordion items
    function toggleAccordion($parentItem, isOpen) {
        var $accordionContent = $parentItem.find('.yrm-accordion-item-content');
        if (!isOpen) {
            // Close other items if keepOpen is false
            if (!keepOpen) {
                $accordionItems.removeClass('yrm-opened').data('expanded', 0);
                $accordionItems.find('.accordion-header-icon').removeClass(closeClass).addClass(openClass);
                $accordionItems.find('.yrm-accordion-item-content').slideUp(duration, easings);
            }

            // Open the clicked/hovered accordion item
            $parentItem.addClass('yrm-opened').data('expanded', 1);
            $parentItem.find('.accordion-header-icon').removeClass(openClass).addClass(closeClass);
            $accordionContent.slideDown(duration, easings);
            setTimeout(() => {jQuery('.yrm-accordion-item-content iframe').css({width: jQuery('.yrm-accordion-item-content').width()+'px'})}, 100)
        } else {
            // Close the clicked/hovered accordion item
            $parentItem.removeClass('yrm-opened').data('expanded', 0);
            $parentItem.find('.accordion-header-icon').removeClass(closeClass).addClass(openClass);
            $accordionContent.slideUp(duration, easings);
        }
    }

    // Debounce function to prevent multiple quick triggers
    function debounce(func, wait) {
        let timeout;
        return function () {
            const context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    }

    // Unbind any previous events to avoid double binding
    $accordionItemHeaders.unbind(event).bind(event, function (e) {
        e.preventDefault();
        var $parentItem = jQuery(this).closest('.yrm-accordion-item');
        var isExpanded = $parentItem.data('expanded') === 1;

        // Play sound if enabled
        if (enableClickSound && audio) {
            audio.play();
        }

        toggleAccordion($parentItem, isExpanded);
    });

    // If 'mouseover' is the event, handle it separately and debounce it
    if (event === 'mouseover') {
        // Apply mouseover and mouseleave to the entire accordion item (header + content)
        $accordionItems.unbind('mouseover mouseleave').on('mouseover', debounce(function () {
            var $parentItem = jQuery(this);
            var isExpanded = $parentItem.data('expanded') === 1;
            if (!isExpanded) {
                toggleAccordion($parentItem, isExpanded);
            }
        }, 200)).on('mouseleave', debounce(function () {
            var $parentItem = jQuery(this);
            if ($parentItem.data('expanded') === 1) {
                toggleAccordion($parentItem, true);
            }
        }, 200));
    }
}

jQuery(document).ready(function () {
	YrmAccordion.start();
});