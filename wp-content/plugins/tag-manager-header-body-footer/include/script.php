<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<script>


jQuery(document).ready(function($){

    // --------------------------------
    // dealing with creating another notes box
    // --------------------------------

    $(document).on('click', '.yydev_tag_warp_textarea .add-another-tag', function () {

        var lastYYnotes = $(this).parent().find('.tag-area-container').last();
        lastYYnotes.clone().insertAfter(lastYYnotes);
        $(this).parent().find('.tag-area-container .form_shortcode_content').last().val('');

        return false;

    }); // $(document).on('click', '.yydev_tag_warp_textarea .add-another-tag', function () {

    // --------------------------------
    // delating with removing notees
    // --------------------------------

    $(document).on('click', '.yydev_tag_warp_textarea a.remove-tag-text', function () {

            if ( confirm("<?php _e('Are you sure you want to permanently remove this tag?', 'tag-manager-header-body-footer') ?>") ) {

                if( $(this).parent().parent().find('.tag-area-container').length > 1 ) {

                    // if there is more than one note remove it
                    $(this).parent().remove();

                } else { // if( $('.yydev-textarea-note').length > 1 ) {

                    // if there is only one note hide it
                    $(this).parent().find('.form_shortcode_content').val('');
  
                } // if( $('.yydev-textarea-note').length > 1 ) {         
            
            } // if ( confirm("Are you sure you want to remove the page note?") ) {

            return false;

    }); // $(document).on('click', '.yydev_tag_warp_textarea a.remove-tag-text', function () {


}); // jQuery(document).ready(function($){

</script>