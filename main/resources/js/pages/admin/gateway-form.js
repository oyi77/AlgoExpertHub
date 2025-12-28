/**
 * Gateway Form Scripts (Shared)
 * Handles image upload preview and currency display
 */

(function($) {
    'use strict';

    $(function() {
        // Initialize image upload preview if uploadPreview plugin is available
        if (typeof $.uploadPreview !== 'undefined') {
            $.uploadPreview({
                input_field: "#image-upload",
                preview_box: "#image-preview",
                label_field: "#image-label",
                label_default: window.chooseFileLabel || "Choose File",
                label_selected: window.updateImageLabel || "Update Image",
                no_label: false,
                success_callback: null
            });
        }

        // Handle currency display update
        $('.site-currency').on('keyup', function() {
            $('.append_currency').text($(this).val());
        });

        // Set initial currency display
        $('.append_currency').text($('.site-currency').val());
    });
})(jQuery);

