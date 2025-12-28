/**
 * Users Index Page Scripts
 * Handles bulk mail modal and password change modal
 */

(function($) {
    'use strict';

    $(function() {
        // Handle bulk mail modal
        $('.sendMail').on('click', function(e) {
            e.preventDefault();
            const modal = $('#mail');
            modal.modal('show');
        });

        // Handle change password modal
        $('.changePassword').on('click', function(e) {
            e.preventDefault();
            const modal = $('#changePassword');
            modal.find('form').attr('action', $(this).data('url'));
            modal.modal('show');
        });
    });
})(jQuery);

