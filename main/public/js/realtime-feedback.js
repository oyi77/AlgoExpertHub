/**
 * Real-time Feedback System
 * Handles loading states, notifications, and form validation feedback
 */
class RealTimeFeedback {
    constructor(config = {}) {
        this.config = {
            notifications: {
                position: 'top-right',
                maxVisible: 5,
                autoDismiss: true,
                dismissAfter: 5000,
                soundEnabled: false,
                ...config.notifications
            },
            loadingStates: {
                showProgress: true,
                showEstimatedTime: true,
                allowCancellation: true,
                minDisplayTime: 500,
                ...config.loadingStates
            },
            formValidation: {
                realTime: true,
                debounceDelay: 300,
                highlightErrors: true,
                showSuccessIndicators: true,
                ...config.formValidation
            },
            animations: {
                enabled: true,
                duration: 300,
                easing: 'ease-in-out',
                ...config.animations
            }
        };

        this.loadingStates = new Map();
        this.notifications = [];
        this.validationTimers = new Map();

        this.init();
    }

    init() {
        this.createNotificationContainer();
        this.setupFormValidation();
        this.setupLoadingStates();
        this.setupEventListeners();
    }

    /**
     * Create notification container
     */
    createNotificationContainer() {
        if (document.getElementById('notification-container')) return;

        const container = document.createElement('div');
        container.id = 'notification-container';
        container.className = `notification-container position-${this.config.notifications.position}`;
        container.setAttribute('aria-live', 'polite');
        container.setAttribute('aria-label', 'Notifications');
        
        document.body.appendChild(container);
    }

    /**
     * Show notification
     */
    showNotification(message, type = 'info', options = {}) {
        const notification = {
            id: 'notification_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
            message,
            type,
            title: options.title,
            persistent: options.persistent || false,
            autoDismiss: options.autoDismiss !== false,
            dismissAfter: options.dismissAfter || this.config.notifications.dismissAfter,
            actions: options.actions || [],
            createdAt: new Date().toISOString()
        };

        this.notifications.push(notification);
        this.renderNotification(notification);

        // Auto-dismiss if configured
        if (notification.autoDismiss && !notification.persistent) {
            setTimeout(() => {
                this.dismissNotification(notification.id);
            }, notification.dismissAfter);
        }

        // Limit visible notifications
        if (this.notifications.length > this.config.notifications.maxVisible) {
            const oldestId = this.notifications[0].id;
            this.dismissNotification(oldestId);
        }

        return notification.id;
    }

    /**
     * Render notification
     */
    renderNotification(notification) {
        const container = document.getElementById('notification-container');
        if (!container) return;

        const element = document.createElement('div');
        element.id = notification.id;
        element.className = `notification notification-${notification.type}`;
        element.setAttribute('role', 'alert');
        element.setAttribute('aria-live', 'assertive');

        let actionsHtml = '';
        if (notification.actions.length > 0) {
            actionsHtml = '<div class="notification-actions">';
            notification.actions.forEach(action => {
                actionsHtml += `<button class="notification-action" data-action="${action.action}">${action.label}</button>`;
            });
            actionsHtml += '</div>';
        }

        element.innerHTML = `
            <div class="notification-content">
                ${notification.title ? `<div class="notification-title">${notification.title}</div>` : ''}
                <div class="notification-message">${notification.message}</div>
                ${actionsHtml}
            </div>
            ${!notification.persistent ? '<button class="notification-close" aria-label="Close notification">&times;</button>' : ''}
        `;

        // Add event listeners
        const closeBtn = element.querySelector('.notification-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                this.dismissNotification(notification.id);
            });
        }

        // Handle action buttons
        element.querySelectorAll('.notification-action').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const action = e.target.dataset.action;
                this.handleNotificationAction(notification.id, action);
            });
        });

        // Animate in
        if (this.config.animations.enabled) {
            element.style.opacity = '0';
            element.style.transform = 'translateX(100%)';
            container.appendChild(element);
            
            requestAnimationFrame(() => {
                element.style.transition = `all ${this.config.animations.duration}ms ${this.config.animations.easing}`;
                element.style.opacity = '1';
                element.style.transform = 'translateX(0)';
            });
        } else {
            container.appendChild(element);
        }
    }

    /**
     * Dismiss notification
     */
    dismissNotification(notificationId) {
        const element = document.getElementById(notificationId);
        if (!element) return;

        if (this.config.animations.enabled) {
            element.style.transition = `all ${this.config.animations.duration}ms ${this.config.animations.easing}`;
            element.style.opacity = '0';
            element.style.transform = 'translateX(100%)';
            
            setTimeout(() => {
                element.remove();
            }, this.config.animations.duration);
        } else {
            element.remove();
        }

        // Remove from notifications array
        this.notifications = this.notifications.filter(n => n.id !== notificationId);
    }

    /**
     * Handle notification action
     */
    handleNotificationAction(notificationId, action) {
        const notification = this.notifications.find(n => n.id === notificationId);
        if (!notification) return;

        // Emit custom event
        const event = new CustomEvent('notificationAction', {
            detail: { notificationId, action, notification }
        });
        document.dispatchEvent(event);

        // Auto-dismiss after action
        this.dismissNotification(notificationId);
    }

    /**
     * Show loading state
     */
    showLoading(actionId, message = 'Loading...', options = {}) {
        const loadingState = {
            id: actionId,
            message,
            progress: options.progress || null,
            type: options.type || 'default',
            cancelable: options.cancelable || false,
            startedAt: new Date().toISOString(),
            estimatedDuration: options.estimatedDuration || null
        };

        this.loadingStates.set(actionId, loadingState);
        this.renderLoadingState(loadingState);

        return actionId;
    }

    /**
     * Update loading progress
     */
    updateLoadingProgress(actionId, progress, message = null) {
        const loadingState = this.loadingStates.get(actionId);
        if (!loadingState) return;

        loadingState.progress = Math.max(0, Math.min(100, progress));
        if (message) loadingState.message = message;
        loadingState.updatedAt = new Date().toISOString();

        this.updateLoadingStateDisplay(loadingState);
    }

    /**
     * Complete loading state
     */
    completeLoading(actionId, message = 'Completed', type = 'success') {
        const loadingState = this.loadingStates.get(actionId);
        if (!loadingState) return;

        const duration = new Date() - new Date(loadingState.startedAt);
        
        // Show completion notification
        this.showNotification(message, type, {
            dismissAfter: 3000
        });

        // Remove loading state
        this.hideLoading(actionId);

        return { duration, message, type };
    }

    /**
     * Hide loading state
     */
    hideLoading(actionId) {
        const element = document.getElementById(`loading-${actionId}`);
        if (element) {
            if (this.config.animations.enabled) {
                element.style.transition = `opacity ${this.config.animations.duration}ms ${this.config.animations.easing}`;
                element.style.opacity = '0';
                setTimeout(() => element.remove(), this.config.animations.duration);
            } else {
                element.remove();
            }
        }

        this.loadingStates.delete(actionId);
    }

    /**
     * Render loading state
     */
    renderLoadingState(loadingState) {
        // This would typically be implemented based on your UI framework
        // For now, we'll create a simple overlay
        const overlay = document.createElement('div');
        overlay.id = `loading-${loadingState.id}`;
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div class="loading-content">
                <div class="loading-spinner"></div>
                <div class="loading-message">${loadingState.message}</div>
                ${loadingState.progress !== null ? `
                    <div class="loading-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${loadingState.progress}%"></div>
                        </div>
                        <div class="progress-text">${loadingState.progress}%</div>
                    </div>
                ` : ''}
                ${loadingState.cancelable ? '<button class="loading-cancel">Cancel</button>' : ''}
            </div>
        `;

        document.body.appendChild(overlay);

        // Handle cancel button
        const cancelBtn = overlay.querySelector('.loading-cancel');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                this.cancelLoading(loadingState.id);
            });
        }
    }

    /**
     * Update loading state display
     */
    updateLoadingStateDisplay(loadingState) {
        const element = document.getElementById(`loading-${loadingState.id}`);
        if (!element) return;

        const messageEl = element.querySelector('.loading-message');
        if (messageEl) messageEl.textContent = loadingState.message;

        const progressFill = element.querySelector('.progress-fill');
        const progressText = element.querySelector('.progress-text');
        
        if (progressFill && loadingState.progress !== null) {
            progressFill.style.width = `${loadingState.progress}%`;
        }
        
        if (progressText && loadingState.progress !== null) {
            progressText.textContent = `${loadingState.progress}%`;
        }
    }

    /**
     * Cancel loading state
     */
    cancelLoading(actionId) {
        const event = new CustomEvent('loadingCancelled', {
            detail: { actionId }
        });
        document.dispatchEvent(event);

        this.hideLoading(actionId);
    }

    /**
     * Setup form validation
     */
    setupFormValidation() {
        if (!this.config.formValidation.realTime) return;

        document.addEventListener('input', (e) => {
            if (e.target.matches('input, textarea, select')) {
                this.handleFieldValidation(e.target);
            }
        });

        document.addEventListener('blur', (e) => {
            if (e.target.matches('input, textarea, select')) {
                this.handleFieldValidation(e.target, true);
            }
        });
    }

    /**
     * Handle field validation
     */
    handleFieldValidation(field, immediate = false) {
        const fieldName = field.name || field.id;
        if (!fieldName) return;

        // Clear existing timer
        if (this.validationTimers.has(fieldName)) {
            clearTimeout(this.validationTimers.get(fieldName));
        }

        const validate = () => {
            this.validateField(field);
        };

        if (immediate) {
            validate();
        } else {
            const timer = setTimeout(validate, this.config.formValidation.debounceDelay);
            this.validationTimers.set(fieldName, timer);
        }
    }

    /**
     * Validate field
     */
    validateField(field) {
        // This would integrate with your validation system
        // For now, we'll do basic HTML5 validation
        const isValid = field.checkValidity();
        const fieldName = field.name || field.id;

        this.updateFieldValidationState(field, isValid, field.validationMessage);
    }

    /**
     * Update field validation state
     */
    updateFieldValidationState(field, isValid, message = '') {
        const fieldContainer = field.closest('.form-group, .field-container') || field.parentElement;
        
        // Remove existing validation classes
        fieldContainer.classList.remove('field-valid', 'field-invalid');
        field.classList.remove('is-valid', 'is-invalid');

        // Remove existing validation message
        const existingMessage = fieldContainer.querySelector('.validation-message');
        if (existingMessage) existingMessage.remove();

        if (isValid) {
            if (this.config.formValidation.showSuccessIndicators) {
                fieldContainer.classList.add('field-valid');
                field.classList.add('is-valid');
            }
        } else {
            if (this.config.formValidation.highlightErrors) {
                fieldContainer.classList.add('field-invalid');
                field.classList.add('is-invalid');
            }

            if (message) {
                const messageEl = document.createElement('div');
                messageEl.className = 'validation-message text-danger';
                messageEl.textContent = message;
                fieldContainer.appendChild(messageEl);
            }
        }
    }

    /**
     * Setup loading states for forms and buttons
     */
    setupLoadingStates() {
        document.addEventListener('submit', (e) => {
            if (e.target.matches('form')) {
                this.handleFormSubmit(e.target);
            }
        });

        document.addEventListener('click', (e) => {
            if (e.target.matches('button[data-loading], .btn[data-loading]')) {
                this.handleButtonLoading(e.target);
            }
        });
    }

    /**
     * Handle form submit loading
     */
    handleFormSubmit(form) {
        const actionId = `form-${form.id || 'submit'}`;
        const message = form.dataset.loadingMessage || 'Submitting...';
        
        this.showLoading(actionId, message, {
            type: 'form-submit',
            cancelable: false
        });

        // Auto-complete on response (you'd integrate this with your AJAX handling)
        setTimeout(() => {
            this.completeLoading(actionId, 'Form submitted successfully');
        }, 2000);
    }

    /**
     * Handle button loading
     */
    handleButtonLoading(button) {
        const actionId = `button-${button.id || Date.now()}`;
        const originalText = button.textContent;
        const loadingText = button.dataset.loadingText || 'Loading...';

        button.textContent = loadingText;
        button.disabled = true;
        button.classList.add('loading');

        // Store original state
        button.dataset.originalText = originalText;
        button.dataset.loadingActionId = actionId;
    }

    /**
     * Complete button loading
     */
    completeButtonLoading(button, success = true) {
        const originalText = button.dataset.originalText;
        const actionId = button.dataset.loadingActionId;

        if (originalText) {
            button.textContent = originalText;
        }
        
        button.disabled = false;
        button.classList.remove('loading');
        
        if (success) {
            button.classList.add('success');
            setTimeout(() => button.classList.remove('success'), 2000);
        } else {
            button.classList.add('error');
            setTimeout(() => button.classList.remove('error'), 2000);
        }

        delete button.dataset.originalText;
        delete button.dataset.loadingActionId;
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Listen for custom events
        document.addEventListener('showNotification', (e) => {
            const { message, type, options } = e.detail;
            this.showNotification(message, type, options);
        });

        document.addEventListener('showLoading', (e) => {
            const { actionId, message, options } = e.detail;
            this.showLoading(actionId, message, options);
        });

        document.addEventListener('completeLoading', (e) => {
            const { actionId, message, type } = e.detail;
            this.completeLoading(actionId, message, type);
        });
    }
}

// Auto-initialize if config is available
document.addEventListener('DOMContentLoaded', () => {
    if (window.feedbackConfig) {
        window.realTimeFeedback = new RealTimeFeedback(window.feedbackConfig);
    }
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RealTimeFeedback;
}