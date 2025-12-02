/**
 * Global Dialog Wrapper
 * Replaces native browser dialogs (alert, confirm, prompt) with custom modals
 * Uses SweetAlert when available, falls back to Bootstrap modals
 */

(function(window) {
    'use strict';

    // Check if SweetAlert is available
    const hasSwal = typeof Swal !== 'undefined';
    
    // Check if jQuery and Bootstrap are available
    const hasJQuery = typeof jQuery !== 'undefined';
    const hasBootstrap = hasJQuery && typeof jQuery.fn.modal !== 'undefined';

    /**
     * Replace native alert() with SweetAlert or Bootstrap modal
     */
    const customAlert = function(message, title = 'Alert') {
        if (hasSwal) {
            return Swal.fire({
                icon: 'info',
                title: title,
                text: message,
                confirmButtonText: 'OK'
            });
        } else if (hasBootstrap) {
            // Fallback to Bootstrap modal
            const modalId = 'dialog-alert-' + Date.now();
            const modalHtml = `
                <div class="modal fade" id="${modalId}" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">${title}</h5>
                                <button type="button" class="close" data-dismiss="modal">
                                    <span>&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p>${message.replace(/\n/g, '<br>')}</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" data-dismiss="modal">OK</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            jQuery('body').append(modalHtml);
            jQuery('#' + modalId).modal('show');
            jQuery('#' + modalId).on('hidden.bs.modal', function() {
                jQuery(this).remove();
            });
            return Promise.resolve();
        } else {
            // Final fallback to native
            return Promise.resolve(window.nativeAlert(message));
        }
    };

    /**
     * Replace native confirm() with SweetAlert or Bootstrap modal
     */
    const customConfirm = function(message, title = 'Confirmation') {
        if (hasSwal) {
            return Swal.fire({
                title: title,
                text: message,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes',
                cancelButtonText: 'No'
            }).then((result) => {
                return Promise.resolve(result.isConfirmed);
            });
        } else if (hasBootstrap) {
            // Fallback to Bootstrap modal with Promise
            return new Promise((resolve) => {
                const modalId = 'dialog-confirm-' + Date.now();
                const modalHtml = `
                    <div class="modal fade" id="${modalId}" tabindex="-1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">${title}</h5>
                                    <button type="button" class="close" data-dismiss="modal">
                                        <span>&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <p>${message.replace(/\n/g, '<br>')}</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">No</button>
                                    <button type="button" class="btn btn-primary dialog-confirm-yes" data-dismiss="modal">Yes</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                jQuery('body').append(modalHtml);
                const $modal = jQuery('#' + modalId);
                
                $modal.find('.dialog-confirm-yes').on('click', function() {
                    resolve(true);
                });
                
                $modal.on('hidden.bs.modal', function() {
                    resolve(false);
                    jQuery(this).remove();
                });
                
                $modal.modal('show');
            });
        } else {
            // Final fallback to native
            const result = window.nativeConfirm(message);
            return Promise.resolve(result);
        }
    };

    /**
     * Replace native prompt() with SweetAlert or Bootstrap modal
     */
    const customPrompt = function(message, defaultText = '', title = 'Input') {
        if (hasSwal) {
            return Swal.fire({
                title: title,
                text: message,
                input: 'textarea',
                inputValue: defaultText,
                inputPlaceholder: 'Enter your response...',
                showCancelButton: true,
                confirmButtonText: 'OK',
                cancelButtonText: 'Cancel',
                inputValidator: (value) => {
                    if (!value || !value.trim()) {
                        return 'Please enter a value';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    return Promise.resolve(result.value);
                }
                return Promise.resolve(null);
            });
        } else if (hasBootstrap) {
            // Fallback to Bootstrap modal with Promise
            return new Promise((resolve) => {
                const modalId = 'dialog-prompt-' + Date.now();
                const modalHtml = `
                    <div class="modal fade" id="${modalId}" tabindex="-1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">${title}</h5>
                                    <button type="button" class="close" data-dismiss="modal">
                                        <span>&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <p>${message.replace(/\n/g, '<br>')}</p>
                                    <textarea class="form-control dialog-prompt-input" rows="5">${defaultText}</textarea>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-primary dialog-prompt-ok" data-dismiss="modal">OK</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                jQuery('body').append(modalHtml);
                const $modal = jQuery('#' + modalId);
                const $input = $modal.find('.dialog-prompt-input');
                
                $modal.find('.dialog-prompt-ok').on('click', function() {
                    const value = $input.val();
                    if (value && value.trim()) {
                        resolve(value);
                    } else {
                        resolve(null);
                    }
                });
                
                $modal.on('hidden.bs.modal', function() {
                    resolve(null);
                    jQuery(this).remove();
                });
                
                $modal.modal('show');
                // Focus input
                setTimeout(() => $input.focus(), 500);
            });
        } else {
            // Final fallback to native
            const result = window.nativePrompt(message, defaultText);
            return Promise.resolve(result);
        }
    };

    // Store native functions before overriding
    window.nativeAlert = window.alert;
    window.nativeConfirm = window.confirm;
    window.nativePrompt = window.prompt;

    // Override native functions
    window.alert = function(message, title) {
        return customAlert(message, title);
    };

    window.confirm = function(message, title) {
        return customConfirm(message, title);
    };

    // Note: prompt() override is trickier because native prompt is blocking
    // We'll provide a custom function instead
    window.prompt = function(message, defaultText, title) {
        // For backward compatibility, we'll use a synchronous-like approach
        // But this will only work properly with SweetAlert/Bootstrap
        let result = null;
        let resolved = false;
        
        customPrompt(message, defaultText || '', title || 'Input').then((value) => {
            result = value;
            resolved = true;
        });
        
        // Wait for result (this is not ideal but maintains compatibility)
        // Better approach: Use async/await or promises in calling code
        const startTime = Date.now();
        while (!resolved && (Date.now() - startTime) < 30000) {
            // Wait up to 30 seconds
        }
        
        return result;
    };

    // Export custom functions for explicit use (recommended)
    window.customAlert = customAlert;
    window.customConfirm = customConfirm;
    window.customPrompt = customPrompt;

    // Also provide a simpler synchronous wrapper for legacy code
    window.syncAlert = function(message) {
        customAlert(message, 'Alert');
    };

    window.syncConfirm = function(message, callback) {
        customConfirm(message, 'Confirmation').then((confirmed) => {
            if (callback) callback(confirmed);
        });
    };

})(window);
