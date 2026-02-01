<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<style>

    .yydevelopment-tag-manager {
        display: block;
        padding: 0px 10px 0px 10px;
    }

    .yydevelopment-tag-manager span#footer-thankyou-code {
        margin: 0px 0px -30px 0px !important;
        padding: 0px;
        display: block;
        font-size: 14px;
        line-height: 1.55;
        font-style: italic;
        font-family: Arial,sans-serif !important;
        color: #555d66;
    }
     
    .yydevelopment-tag-manager .yydev_tag_warp_textarea {
        display: block;
        max-width: 800px;
        margin: 0px 20px 40px 20px;
        position: relative;
    }

    .yydevelopment-tag-manager .yydev_tag_warp_textarea {
        display: block;
        max-width: 800px;
        margin: 0px 20px 40px 20px;
        position: relative;
    }

    .yydevelopment-tag-manager .yydev_tag_warp_textarea .tag-area-container {
        position: relative;
    }

    .yydevelopment-tag-manager .yydev_tag_warp_textarea p {
        display: inline-block;
        direction: ltr;
    }

    .yydevelopment-tag-manager .yydev_tag_warp_textarea p b {
        background: #ffff00;
        font-size: 17px;
    }

    .yydevelopment-tag-manager .remove-tag-text {
        position: absolute;
        right: -26px;
        top: 50%;
        width: 20px;
    }
    .yydevelopment-tag-manager .add-another-tag {
        color: #fff;
        border: 1px solid #d5d5d5;
        margin: 8px auto 15px;
        display: inline-block;
        width: 95%;
        clear: both;
        text-align: center;
        padding: 11px 20px 11px 20px;
        text-decoration: none;
        font-size: 15px;
        font-weight: bold;
        direction: ltr;
        background: #00aafb;
    }

    .yydevelopment-tag-manager textarea.form_shortcode_content {
        width: 100%;
        height: 250px;
        text-align: left;
        direction: ltr;
        display: block;
        padding: 10px 20px 10px 20px;
    }

    .yydevelopment-tag-manager textarea.custom_lazy_load_js {
        width: 100%;
        height: 250px;
        text-align: left;
        direction: ltr;
        display: block;
        padding: 10px 20px 10px 20px;
    }

    .yydevelopment-tag-manager #remove_custom_lazy_load_js_on_elementor {
        position: relative;
        top: -5px;
    }

    .yydevelopment-tag-manager .yydev-tags-submit {
        color: #fff;
        background: #707070;
        font-size: 20px;
        font-weight: bold;
        padding: 10px 30px 10px 30px;
        cursor: pointer;
        margin: 20px 5px 0px 5px;
    }

    .yydevelopment-tag-manager .yydev-tags-submit:hover {
        background: #5c5c5c;
    }

    .yydevelopment-tag-manager .align-center {
        text-align: center;
    }

    .yydevelopment-tag-manager .output-messsage {
        display: block;
        color: #fff;
        background: #0054ff;
        line-height: 30px;
        padding: 10px 10px 10px 10px;
        font-size: 20px;
        margin: 8px 0px 15px 0px;
    }

    .yydevelopment-tag-manager .error-messsage {
        display: block;
        color: #fff;
        background: #ff0000;
        line-height: 30px;
        padding: 10px 10px 10px 10px;
        font-size: 20px;
        margin: 8px 0px 15px 0px;
    }

    .yydevelopment-tag-manager .tag-manager-line {
        margin: 20px 0px 20px 0px;
    }


    .yydevelopment-tag-manager .tags-right-side {
        display: inline-block;
    }

    .yydevelopment-tag-manager .insert-new {
        display: inline-block;
    }

    .yydevelopment-tag-manager .yy-lazy-load-warp {
        width: 30%;
        display: inline-block;
        vertical-align: top;
        margin: 0px 40px 0px 40px;
        padding: 30px;
        background: #fcfcfc;
    }

    .yydevelopment-tag-manager .yy-lazy-load-warp .tag-manager-line {
        padding-bottom: 10px;
        padding-top: 20px;
        border-top: 1px solid #d3d3d3;
    }

    .yydevelopment-tag-manager .yy-lazy-load-warp .tag-manager-line label {
        padding-bottom: 15px;
    }

    .yydevelopment-tag-manager .yy-lazy-load-warp .tag-manager-line label.yandex_x_frame_class_allow {
        padding-bottom: 0px;
    }

    .yydevelopment-tag-manager .yy-lazy-load-warp .tag-manager-line:last-child {
        padding-bottom: 0px;
    }

    .yydevelopment-tag-manager input::placeholder { /* Chrome, Firefox, Opera, Safari 10.1+ */
      color: #c0c0c0;
    }

    .yydevelopment-tag-manager input:-ms-input-placeholder { /* Internet Explorer 10-11 */
      color: #c0c0c0;
    }

    .yydevelopment-tag-manager input::-ms-input-placeholder { /* Microsoft Edge */
      color: #c0c0c0;
    }

    .mark-this-line {
        padding: 20px 10px 20px 10px;
        background: #e3e3e3;
        display: inline-block;
        border: 2px solid #606060;
    }

    .yydevelopment-tag-manager .save-notes {
        display: block;
        border: 2px solid #828282;
        max-width: 600px;
        padding: 50px;
        background: #f5f5f5;
        border-radius: 20px;
    }

    .yydevelopment-tag-manager .save-notes textarea {
        height: 150px;
    }

    /*================================
    =============== Mobile
    ==================================*/

    @media only screen and (max-width: 1500px) {

        .yydevelopment-tag-manager .yy-lazy-load-warp {
            width: 80%;
            margin: 0 auto;
        }

    }

</style>