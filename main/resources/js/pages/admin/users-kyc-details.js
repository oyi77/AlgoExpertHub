/**
 * User KYC Details Page Scripts
 * Handles approve and reject modals
 */

(function($) {
    'use strict';

    $(function() {
        // Handle approve button
        $('.approve').on('click', function() {
            const modal = $('#approve');
            modal.find('form').attr('action', $(this).data('url'));
            modal.modal('show');
        });

        // Handle reject button
        $('.reject').on('click', function() {
            const modal = $('#reject');
            modal.find('form').attr('action', $(this).data('url'));
            modal.modal('show');
        });
    });
})(jQuery);

