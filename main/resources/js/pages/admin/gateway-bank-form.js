/**
 * Gateway Bank Form Scripts
 * Handles dynamic bank field addition and QR code toggle
 */

(function($) {
    'use strict';

    $(function() {
        var i = window.initialFieldCount || 0;

        // Bank fields template
        let bankFields = window.bankFieldsTemplate || `
            <div class="form-group col-md-6 delete">
                <label for="">{{ __('Bank Account Number') }}</label>
                <input type="text" name="account_number" class="form-control">
            </div>
            <div class="form-group col-md-6 delete">
                <label for="">{{ __('Bank Routing Number') }}</label>
                <input type="text" name="routing_number" class="form-control">
            </div>
            <div class="form-group col-md-6 delete">
                <label for="">{{ __('Bank Branch Name') }}</label>
                <input type="text" name="branch_name" class="form-control">
            </div>
        `;

        // Hide QR code initially if bank type
        if (window.gatewayType === 'bank') {
            $('.qr').addClass('d-none');
            $('#append').after(bankFields);
        }

        // Handle type change
        $('#type').on('change', function() {
            if ($(this).val() === 'bank') {
                $('#append').after(bankFields);
                $('.qr').removeClass('d-block').addClass('d-none');
                $('.qr').find('input[name=qr_code]').val('');
            } else {
                $('.qr').removeClass('d-none').addClass('d-block');
                $('.delete').remove();
            }
        });

        // Handle payment proof field addition (if exists)
        if ($('.payment').length) {
            $('.payment').on('click', function() {
                var html = window.paymentFieldTemplate || '';
                if (html) {
                    html = html.replace(/\$\{i\}/g, i);
                    $('.payment-instruction').append(html);
                    i++;
                }
            });

            $(document).on('click', '.remove', function() {
                $(this).closest('.user-data').remove();
            });
        }
    });
})(jQuery);

