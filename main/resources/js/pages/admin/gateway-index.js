/**
 * Gateway Index Page Scripts
 * Handles gateway status toggle
 */

(function($) {
    'use strict';

    $(function() {
        $('.check').on('change', function() {
            $.ajax({
                url: $(this).data('url'),
                method: "POST",
                data: {
                    "_token": window.csrf_token || $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        if (window.alertType === 'izi' && typeof iziToast !== 'undefined') {
                            iziToast.success({
                                position: 'topRight',
                                message: "Gateway Status changed Successfully",
                            });
                        } else if (window.alertType === 'toast' && typeof toastr !== 'undefined') {
                            toastr.success("Gateway Status changed Successfully", {
                                positionClass: "toast-top-right"
                            });
                        } else if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: "Gateway Status changed Successfully"
                            });
                        }
                        return;
                    }

                    // Error handling
                    if (window.alertType === 'izi' && typeof iziToast !== 'undefined') {
                        iziToast.error({
                            position: 'topRight',
                            message: "Something went wrong",
                        });
                    } else if (window.alertType === 'toast' && typeof toastr !== 'undefined') {
                        toastr.error("Something went wrong", {
                            positionClass: "toast-top-right"
                        });
                    } else if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: "Something went wrong"
                        });
                    }
                },
                error: function() {
                    if (window.alertType === 'izi' && typeof iziToast !== 'undefined') {
                        iziToast.error({
                            position: 'topRight',
                            message: "Something went wrong",
                        });
                    } else if (window.alertType === 'toast' && typeof toastr !== 'undefined') {
                        toastr.error("Something went wrong", {
                            positionClass: "toast-top-right"
                        });
                    } else if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: "Something went wrong"
                        });
                    }
                }
            });
        });
    });
})(jQuery);

