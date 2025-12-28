/**
 * Admin Index Page Scripts
 * Handles status toggling, role management modals, and select2 initialization
 */

(function($) {
    'use strict';

    $(function() {
        // Initialize Select2 for role tokenizer
        $(".js-example-tokenizer").select2({
            placeholder: "Give Permission",
            tags: true,
            tokenSeparators: [',', ' ']
        });

        // Handle status toggle
        $('.status').on('change', function() {
            let id = $(this).data('id');
            let route = $(this).data('route');

            $.ajax({
                url: route,
                method: "POST",
                data: {
                    _token: window.csrf_token || $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.type === 'success') {
                        toastr.success(response.message || 'Successfully changed status');
                    } else {
                        toastr.error(response.message || 'Failed to change status');
                    }
                },
                error: function(xhr) {
                    toastr.error('An error occurred while changing status');
                }
            });
        });

        // Handle add role modal
        $('.add').on('click', function() {
            const modal = $('#role');
            modal.modal('show');
        });

        // Handle edit role modal
        $('.edit').on('click', function() {
            const modal = $('#role_edit');
            modal.find('input[name=role]').val($(this).data('name'));
            modal.find('form').attr('action', $(this).data('href'));
            modal.find('.js-example-tokenizer').val($(this).data('permission')).trigger('change');
            modal.modal('show');
        });
    });
})(jQuery);

