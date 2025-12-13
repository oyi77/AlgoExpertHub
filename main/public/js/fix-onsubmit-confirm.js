/**
 * Fix onsubmit="return confirm(...)" patterns
 * This script runs on page load and converts all inline onsubmit confirm patterns
 * to proper event handlers that check confirmation BEFORE executing actions
 */
(function() {
    'use strict';

    function init() {
        // Find all forms with onsubmit="return confirm(...)"
        const forms = document.querySelectorAll('form[onsubmit*="confirm"]');
        
        forms.forEach(function(form) {
            const onsubmitAttr = form.getAttribute('onsubmit');
            
            // Extract the confirmation message from onsubmit attribute
            // Pattern: onsubmit="return confirm('message');"
            const match = onsubmitAttr.match(/confirm\(['"]([^'"]+)['"]\)/);
            
            if (!match || !match[1]) {
                return; // Skip if we can't parse the message
            }
            
            const confirmMessage = match[1];
            
            // Remove the onsubmit attribute
            form.removeAttribute('onsubmit');
            
            // Add data attribute for consistency
            form.setAttribute('data-confirm-message', confirmMessage);
            
            // Add proper event handler that checks confirmation FIRST
            form.addEventListener('submit', function(e) {
                // CRITICAL: Prevent default FIRST before showing confirmation
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                // Show confirmation dialog
                showConfirmation(confirmMessage, function(confirmed) {
                    if (confirmed) {
                        // User confirmed - create new submission bypassing handlers
                        const newForm = form.cloneNode(true);
                        newForm.removeAttribute('data-confirm-message');
                        
                        // Remove all event listeners by cloning
                        const hiddenForm = document.createElement('form');
                        hiddenForm.method = form.method || 'POST';
                        hiddenForm.action = form.action;
                        hiddenForm.style.display = 'none';
                        
                        // Copy all form data
                        const formData = new FormData(form);
                        for (let pair of formData.entries()) {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = pair[0];
                            input.value = pair[1];
                            hiddenForm.appendChild(input);
                        }
                        
                        // Also copy method override if exists
                        const methodInput = form.querySelector('input[name="_method"]');
                        if (methodInput) {
                            const method = document.createElement('input');
                            method.type = 'hidden';
                            method.name = '_method';
                            method.value = methodInput.value;
                            hiddenForm.appendChild(method);
                        }
                        
                        document.body.appendChild(hiddenForm);
                        hiddenForm.submit();
                    }
                    // If not confirmed, do nothing - form submission already prevented
                });
                
                return false;
            }, true); // Use capture phase to run before other handlers
        });
    }
    
    function showConfirmation(message, callback) {
        // Try SweetAlert first (preferred)
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Confirmation',
                text: message,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes',
                cancelButtonText: 'Cancel'
            }).then(function(result) {
                callback(result.isConfirmed);
            });
        } else {
            // Fallback to native confirm
            const confirmed = confirm(message);
            callback(confirmed);
        }
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
