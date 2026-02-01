jQuery(document).ready(function($) {
    'use strict';

    var thumbnailFrame;
    var logoFrame;

    // ========== THUMBNAIL UPLOADER ==========
    $('.fsc-upload-thumbnail-btn').on('click', function(e) {
        e.preventDefault();

        // If the media frame already exists, reopen it
        if (thumbnailFrame) {
            thumbnailFrame.open();
            return;
        }

        // Create the media frame
        thumbnailFrame = wp.media({
            title: 'Select Product Thumbnail',
            button: {
                text: 'Use this image'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });

        // When an image is selected, run a callback
        thumbnailFrame.on('select', function() {
            var attachment = thumbnailFrame.state().get('selection').first().toJSON();
            
            // Update the hidden field
            $('#fsc_thumbnail_id').val(attachment.id);
            
            // Update the preview image
            var previewImg = $('.fsc-thumbnail-preview img');
            if (attachment.sizes && attachment.sizes.medium) {
                previewImg.attr('src', attachment.sizes.medium.url);
            } else {
                previewImg.attr('src', attachment.url);
            }
            previewImg.show();
            
            // Update button text and show remove button
            $('.fsc-upload-thumbnail-btn').text('Change Thumbnail');
            if ($('.fsc-remove-thumbnail-btn').length === 0) {
                $('.fsc-upload-thumbnail-btn').after('<button type="button" class="button fsc-remove-thumbnail-btn" style="margin-left: 5px;">Remove Thumbnail</button>');
            }
        });

        // Open the media frame
        thumbnailFrame.open();
    });

    // Remove thumbnail
    $(document).on('click', '.fsc-remove-thumbnail-btn', function(e) {
        e.preventDefault();
        
        // Clear the hidden field
        $('#fsc_thumbnail_id').val('');
        
        // Reset preview to placeholder
        var previewImg = $('.fsc-thumbnail-preview img');
        var placeholderUrl = previewImg.attr('src').replace(/\/wp-content\/uploads\/.*/, '/wp-content/plugins/freight-signs-customizer/assets/images/placeholder.png');
        previewImg.attr('src', placeholderUrl);
        
        // Update button and hide remove button
        $('.fsc-upload-thumbnail-btn').text('Upload Thumbnail');
        $(this).remove();
    });

    // ========== LOGO UPLOADER (SVG ONLY) ==========
    $('.fsc-upload-logo-btn').on('click', function(e) {
        e.preventDefault();

        // If the media frame already exists, reopen it
        if (logoFrame) {
            logoFrame.open();
            return;
        }

        // Create the media frame - restrict to SVG
        logoFrame = wp.media({
            title: 'Select Product Logo (SVG only)',
            button: {
                text: 'Use this SVG'
            },
            multiple: false,
            library: {
                type: 'image/svg+xml'
            }
        });

        // When an image is selected, run a callback
        logoFrame.on('select', function() {
            var attachment = logoFrame.state().get('selection').first().toJSON();
            
            // Validate it's an SVG
            if (attachment.mime !== 'image/svg+xml' && attachment.subtype !== 'svg+xml') {
                alert('Please select an SVG file only.');
                return;
            }
            
            // Update the hidden field
            $('#fsc_logo_id').val(attachment.id);
            
            // Update the preview text/link
            var currentLogoDiv = $('.fsc-logo-wrapper > div');
            if (currentLogoDiv.length === 0) {
                $('.fsc-logo-wrapper').prepend('<div style="margin-bottom: 10px; padding: 10px; background: #f0f0f0; border-radius: 4px;"><strong>Current logo:</strong> <a href="" target="_blank"></a></div>');
                currentLogoDiv = $('.fsc-logo-wrapper > div');
            }
            
            currentLogoDiv.find('a').attr('href', attachment.url).text(attachment.filename);
            
            // Update button text and show remove button
            $('.fsc-upload-logo-btn').text('Change Logo');
            if ($('.fsc-remove-logo-btn').length === 0) {
                $('.fsc-upload-logo-btn').after('<button type="button" class="button fsc-remove-logo-btn" style="margin-left: 5px;">Remove Logo</button>');
            }
        });

        // Open the media frame
        logoFrame.open();
    });

    // Remove logo
    $(document).on('click', '.fsc-remove-logo-btn', function(e) {
        e.preventDefault();
        
        // Clear the hidden field
        $('#fsc_logo_id').val('');
        
        // Remove the current logo display
        $('.fsc-logo-wrapper > div').remove();
        
        // Update button and hide remove button
        $('.fsc-upload-logo-btn').text('Upload Logo');
        $(this).remove();
    });
});
