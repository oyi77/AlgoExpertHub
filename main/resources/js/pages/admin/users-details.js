/**
 * User Details Page Scripts
 * Handles balance add/subtract forms and mail modal
 */

(function($) {
    'use strict';

    $(function() {
        let addBalance = $("#addBalance");
        let subBalance = $("#subBalance");

        // Hide both forms initially
        addBalance.addClass('d-none');
        subBalance.addClass('d-none');

        // Toggle add balance form
        $("#addBtn").on('click', function(){
            addBalance.toggleClass('d-none');
            if(subBalance.hasClass('d-none')) {
                return true;
            } else {
                subBalance.addClass('d-none');
            }
        });

        // Toggle subtract balance form
        $("#subBtn").on('click', function(){
            subBalance.toggleClass('d-none');
            if(addBalance.hasClass('d-none')) {
                return true;
            } else {
                addBalance.addClass('d-none');
            }
        });
        
        // Handle add balance form submission
        $('#addBalance').on('submit', function(e) {
            e.preventDefault();

            let formData = $(this).serializeArray();
            const action = $(this).attr('action');
            const type = formData[2].value;
            const balance = formData[3].value;
            const userId = $(this).data('user-id') || formData.find(f => f.name === 'user_id')?.value;

            Swal.fire({
                title: window.confirmationTitle || 'Confirmation',
                text: window.confirmationText || 'Are you sure to perform this action?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#6c757d',
                confirmButtonText: window.updateButtonText || 'Update',
                cancelButtonText: window.closeButtonText || 'Close'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = $('<form>', {
                        'method': 'POST',
                        'action': action
                    });
                    form.append($('<input>', {
                        'type': 'hidden',
                        'name': '_token',
                        'value': window.csrf_token || $('meta[name="csrf-token"]').attr('content')
                    }));
                    if (userId) {
                        form.append($('<input>', {
                            'type': 'hidden',
                            'name': 'user_id',
                            'value': userId
                        }));
                    }
                    form.append($('<input>', {
                        'type': 'hidden',
                        'name': 'type',
                        'value': type
                    }));
                    form.append($('<input>', {
                        'type': 'hidden',
                        'name': 'balance',
                        'value': balance
                    }));
                    $('body').append(form);
                    form.submit();
                }
            });
        });

        // Handle subtract balance form submission
        $('#subBalance').on('submit', function(e) {
            e.preventDefault();

            let formData = $(this).serializeArray();
            const action = $(this).attr('action');
            const type = formData[2].value;
            const balance = formData[3].value;
            const userId = $(this).data('user-id') || formData.find(f => f.name === 'user_id')?.value;

            Swal.fire({
                title: window.confirmationTitle || 'Confirmation',
                text: window.confirmationText || 'Are you sure to perform this action?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#6c757d',
                confirmButtonText: window.updateButtonText || 'Update',
                cancelButtonText: window.closeButtonText || 'Close'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = $('<form>', {
                        'method': 'POST',
                        'action': action
                    });
                    form.append($('<input>', {
                        'type': 'hidden',
                        'name': '_token',
                        'value': window.csrf_token || $('meta[name="csrf-token"]').attr('content')
                    }));
                    if (userId) {
                        form.append($('<input>', {
                            'type': 'hidden',
                            'name': 'user_id',
                            'value': userId
                        }));
                    }
                    form.append($('<input>', {
                        'type': 'hidden',
                        'name': 'type',
                        'value': type
                    }));
                    form.append($('<input>', {
                        'type': 'hidden',
                        'name': 'balance',
                        'value': balance
                    }));
                    $('body').append(form);
                    form.submit();
                }
            });
        });

        // Handle send mail modal
        $('.sendMail').on('click', function(e) {
            e.preventDefault();
            const modal = $('#mail');
            modal.modal('show');
        });
    });
})(jQuery);

