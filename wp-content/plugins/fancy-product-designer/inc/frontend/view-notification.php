<div class="fpd-frontend-notification">
    <div>
        <div>
            <h4>Administrator Notification</h4>
            The Fancy Product Designer feature has been activated for this page/product. 
            However, due to missing action hooks, the designer could not be integrated successfully. 
            This issue frequently occurs with page builder plugins such as Elementor. 
            To enable the product designer app smoothly, simply insert the shortcode <code>[fpd]</code> into the content.
            <br><br>
            <a href="https://support.fancyproductdesigner.com/support/solutions/articles/13000063665-using-fpd-with-page-builder-plugins-like-elementor" target="_blank">For additional details and solutions regarding this matter, please refer to the following information.</a>
        </div>
        <span id="fpd-frontend-notification-close">Close</span>
    </div>
</div>
<script type="text/javascript">
    
    document.getElementById('fpd-frontend-notification-close').addEventListener('click', () => {        
        document.querySelector('.fpd-frontend-notification').remove();
    })

</script>