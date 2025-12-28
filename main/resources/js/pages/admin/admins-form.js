/**
 * Admin Create/Edit Form Scripts
 * Handles select2 initialization and image upload preview
 */

(function($) {
    'use strict';

    $(function() {
        // Initialize Select2 for role selection
        $(".js-example-tokenizer").select2({
            placeholder: "Select Role"
        });

        // Initialize image upload preview
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
    });
})(jQuery);

