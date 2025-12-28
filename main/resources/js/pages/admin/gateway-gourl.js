/**
 * Gateway GoUrl Scripts
 * Handles custom image preview and currency management
 */

(function($) {
    'use strict';

    $(function() {
        // Custom image preview handler
        $(document).on('change', '.imageUploader', function() {
            showImagePreview(this, "#image-preview-" + $(this).data('id'));
        });

        function showImagePreview(input, id) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $(id).css('background-image', "url(" + e.target.result + ")");
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Currency management
        let currencyAdded = window.currencyAdded || [];

        $('#addNew').on('click', function() {
            let currency = $('#currency option:selected').val();

            if (currencyAdded.includes(currency)) {
                if (typeof iziToast !== 'undefined') {
                    iziToast.error({
                        message: "Already Added This Currency",
                        position: 'topRight'
                    });
                }
                return;
            }

            let html = window.currencyFieldTemplate || '';
            if (html) {
                html = html.replace(/\$\{currency\}/g, currency);
                currencyAdded.push(currency);
                $('#appear').after(html);
            }
        });

        $(document).on('click', '.remove', function(e) {
            e.preventDefault();
            let currency = $(this).data('currncy') || $(this).data('currency');
            if (currency) {
                currencyAdded.splice(currencyAdded.indexOf(currency), 1);
            }
            $(this).parents().find('.removeEl').remove();
        });

        $(document).on('keyup', '.site-currency', function() {
            $('.append_currency').text($(this).val());
        });
    });
})(jQuery);

