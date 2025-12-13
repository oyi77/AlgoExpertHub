/**
 * Installer AJAX Handler
 */

(function($) {
    'use strict';

    const Installer = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Database form submission
            $('#database-form').on('submit', this.handleDatabaseSubmit.bind(this));
            
            // Test connection button
            $('#test-connection').on('click', this.testConnection.bind(this));
            
            // Database type change
            $('#db_type').on('change', this.handleDbTypeChange.bind(this));
        },

        handleDbTypeChange: function(e) {
            const dbType = $(e.target).val();
            const $portField = $('#db_port');
            const defaultPorts = {
                'mysql': 3306,
                'mariadb': 3306,
                'postgresql': 5432,
                'auto': ''
            };
            
            if (defaultPorts[dbType] !== undefined) {
                $portField.val(defaultPorts[dbType]);
            }
        },

        testConnection: function(e) {
            e.preventDefault();
            
            const $btn = $(e.target);
            const $form = $('#database-form');
            const originalText = $btn.text();
            
            // Get form data
            const formData = {
                db_type: $('#db_type').val(),
                db_host: $('#db_host').val(),
                db_port: $('#db_port').val(),
                db_name: $('#db_name').val(),
                db_username: $('#db_username').val(),
                db_pass: $('#db_pass').val(),
                test_connection: true
            };
            
            // Validate
            if (!formData.db_host || !formData.db_username) {
                this.showError('Please fill in database host and username');
                return;
            }
            
            $btn.prop('disabled', true).text('Testing...');
            this.hideMessages();
            
            $.ajax({
                url: 'database.php',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: (response) => {
                    if (response.success) {
                        this.showSuccess('Connection successful!');
                    } else {
                        this.showError(response.error || 'Connection failed');
                    }
                },
                error: (xhr) => {
                    let errorMsg = 'Connection test failed';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.error) {
                            errorMsg = response.error;
                        }
                    } catch(e) {
                        errorMsg = 'Unable to test connection';
                    }
                    this.showError(errorMsg);
                },
                complete: () => {
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        },

        handleDatabaseSubmit: function(e) {
            e.preventDefault();
            
            const $form = $(e.target);
            const $btn = $form.find('button[type="submit"]');
            const originalText = $btn.text();
            
            // Validate form
            if (!this.validateForm($form)) {
                return;
            }
            
            $btn.prop('disabled', true).text('Installing...');
            this.hideMessages();
            this.showProgress('Starting installation...');
            
            $.ajax({
                url: 'database.php',
                method: 'POST',
                data: $form.serialize(),
                dataType: 'json',
                success: (response) => {
                    if (response.success) {
                        this.showProgress('Installation completed! Redirecting...');
                        setTimeout(() => {
                            window.location.href = response.redirect || 'finish.php';
                        }, 1500);
                    } else {
                        this.showError(response.error || 'Installation failed');
                        $btn.prop('disabled', false).text(originalText);
                    }
                },
                error: (xhr) => {
                    let errorMsg = 'Installation failed';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.error) {
                            errorMsg = response.error;
                        }
                    } catch(e) {
                        // Fallback to form submission
                        $form.off('submit').submit();
                        return;
                    }
                    this.showError(errorMsg);
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        },

        validateForm: function($form) {
            let isValid = true;
            const requiredFields = ['purchase_code', 'url', 'db_name', 'db_host', 'db_username', 'username', 'password', 'email'];
            
            requiredFields.forEach(field => {
                const $field = $form.find(`[name="${field}"]`);
                if (!$field.val() || $field.val().trim() === '') {
                    $field.addClass('error');
                    isValid = false;
                } else {
                    $field.removeClass('error');
                }
            });
            
            if (!isValid) {
                this.showError('Please fill in all required fields');
            }
            
            return isValid;
        },

        showError: function(message) {
            this.hideMessages();
            const $error = $('#error-message');
            $error.text(message).show();
            $('html, body').animate({ scrollTop: $error.offset().top - 100 }, 300);
        },

        showSuccess: function(message) {
            this.hideMessages();
            const $success = $('#success-message');
            $success.text(message).show();
            setTimeout(() => {
                $success.fadeOut();
            }, 3000);
        },

        showProgress: function(message) {
            const $progress = $('#progress-message');
            $progress.text(message).show();
        },

        hideMessages: function() {
            $('#error-message, #success-message, #progress-message').hide();
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        Installer.init();
    });

})(jQuery);

