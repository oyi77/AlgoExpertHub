/**
 * Gateway Create/Edit Form Scripts
 * Handles dynamic user proof parameter field addition
 */

(function($) {
    'use strict';

    $(function() {
        var i = window.initialFieldCount || 0;

        // Handle add payment proof field
        $('.payment').on('click', function() {
            var html = window.paymentFieldTemplate || '';
            if (html) {
                html = html.replace(/\$\{i\}/g, i);
                $('.payment-instruction').append(html);
                i++;
            }
        });

        // Handle remove field
        $(document).on('click', '.remove', function() {
            $(this).closest('.user-data').remove();
        });

        // Initialize image upload previews
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

            // Second image preview if exists
            if ($('#image-upload-1').length) {
                $.uploadPreview({
                    input_field: "#image-upload-1",
                    preview_box: "#image-preview-1",
                    label_field: "#image-label-1",
                    label_default: window.chooseFileLabel || "Choose File",
                    label_selected: window.updateImageLabel || "Update Image",
                    no_label: false,
                    success_callback: null
                });
            }
        }

        // Handle currency display
        $('.site-currency').on('keyup', function() {
            $('.append_currency').text($(this).val());
        });

        $('.append_currency').text($('.site-currency').val());
    });
})(jQuery);

