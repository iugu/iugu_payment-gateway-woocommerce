/*jshint devel: true */
(function($) {
    'use strict';
    $(function() {
        const clipboard = new ClipboardJS('#iugu_pix_qrcode_text_button');
        $('#iugu_pix_qrcode_text_button').on('click', function(e) {
            const clipboard = new ClipboardJS('#iugu_pix_qrcode_text_button');
            var x = document.getElementById("snackbar");
            x.className = "show";
            setTimeout(function() { x.className = x.className.replace("show", ""); }, 3000);
        })
    });
}(jQuery));