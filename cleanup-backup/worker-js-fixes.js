/**
 * Worker Management JavaScript Fixes
 * 
 * This file contains JavaScript fixes for the worker management system
 * to address issues with password toggle and form validation.
 */

(function($) {
    'use strict';

    // Enhanced password toggle functionality
    function initPasswordToggle() {
        $(document).off('click.passwordToggle').on('click.passwordToggle', '.btn[data-target]', function(e) {
            e.preventDefault();
            
            const $toggle = $(this);
            const targetId = $toggle.data('target');
            const $input = $('#' + targetId);
            
            if (!$input.length) {
                console.warn('Password toggle target not found:', targetId);
                return;
            }
            
            const isPassword = $input.attr('type') === 'password';
            
            // Toggle input type
            $input.attr('type', isPassword ? 'text' : 'password');
            
            // Toggle button state
            $toggle.toggleClass('NORDBOOKING-password-visible', isPassword);
            
            // Update aria-label for accessibility
            const newLabel = isPassword ? 'Hide password' : 'Show password';
            $toggle.attr('aria-label', newLabel);
            
            // Focus back to input
            $input.focus();
        });
    }

    // Enhanced form validation with better error display
    function showFieldError($field, message) {
        // Remove existing error
        $field.removeClass('NORDBOOKING-input-error');
        $field.siblings('.NORDBOOKING-field-error').remove();
        
        if (message) {
            // Add error state
            $field.addClass('NORDBOOKING-input-error');
            
            // Add error message
            const $error = $('<div class="NORDBOOKING-field-error">' + message + '</div>');
            $field.after($error);
        }
    }

    function hideFieldError($field) {
        $field.removeClass('NORDBOOKING-input-error');
        $field.siblings('.NORDBOOKING-field-error').remove();
    }

    // Enhanced inline alert display
    function showInlineAlert($alertArea, message, isSuccess) {
        if (!$alertArea.length) return;
        
        const alertClass = isSuccess ? 'NORDBOOKING-inline-alert-success' : 'NORDBOOKING-inline-alert-error';
        
        $alertArea
            .removeClass('NORDBOOKING-inline-alert-success NORDBOOKING-inline-alert-error NORDBOOKING-inline-alert-info NORDBOOKING-inline-alert-warning')
            .addClass(alertClass)
            .find('.NORDBOOKING-inline-alert-message')
            .text(message);
        
        $alertArea.slideDown(300);
        
        // Auto-hide success messages after 5 seconds
        if (isSuccess) {
            setTimeout(function() {
                $alertArea.slideUp(300);
            }, 5000);
        }
    }

    function hideInlineAlert($alertArea) {
        if ($alertArea.length) {
            $alertArea.slideUp(200);
        }
    }

    // Enhanced form validation
    function validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function validatePassword(password) {
        return password && password.length >= 8;
    }

    // Real-time validation
    function initRealTimeValidation() {
        // Email validation
        $(document).on('blur', 'input[type="email"]', function() {
            const $field = $(this);
            const email = $field.val().trim();
            
            if (email && !validateEmail(email)) {
                showFieldError($field, 'Please enter a valid email address.');
            } else {
                hideFieldError($field);
            }
        });

        // Password validation
        $(document).on('input', 'input[type="password"]', function() {
            const $field = $(this);
            const password = $field.val();
            
            if (password && !validatePassword(password)) {
                showFieldError($field, 'Password must be at least 8 characters long.');
            } else {
                hideFieldError($field);
            }
        });

        // Clear errors on input
        $(document).on('input', '.NORDBOOKING-input-error', function() {
            hideFieldError($(this));
        });
    }

    // Enhanced button loading states
    function setButtonLoading($button, loadingText) {
        if (!$button.data('original-html')) {
            $button.data('original-html', $button.html());
        }

        const spinnerIcon = '<span class="NORDBOOKING-spinner"></span>';
        
        $button
            .prop('disabled', true)
            .html(spinnerIcon + (loadingText || 'Loading...'))
            .addClass('NORDBOOKING-loading');
    }

    function resetButtonLoading($button) {
        const originalHtml = $button.data('original-html');
        if (originalHtml) {
            $button
                .prop('disabled', false)
                .html(originalHtml)
                .removeClass('NORDBOOKING-loading');
        }
    }

    // Enhanced AJAX error handling
    function handleAjaxError(xhr, status, error) {
        console.error('AJAX Error:', {xhr, status, error});
        
        let message = 'An error occurred. Please try again.';
        
        try {
            if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                message = xhr.responseJSON.data.message;
            } else if (xhr.responseText) {
                const response = JSON.parse(xhr.responseText);
                if (response.data && response.data.message) {
                    message = response.data.message;
                }
            }
        } catch (e) {
            // Use default message
        }
        
        return message;
    }

    // Initialize all enhancements
    function initWorkerEnhancements() {
        initPasswordToggle();
        initRealTimeValidation();
        
        // Add global error handler for AJAX requests
        $(document).ajaxError(function(event, xhr, settings, thrownError) {
            if (settings.url && settings.url.includes('admin-ajax.php')) {
                const errorMessage = handleAjaxError(xhr, 'error', thrownError);
                console.error('Global AJAX Error:', errorMessage);
            }
        });
        
        // Enhance existing form submissions
        $(document).on('submit', '#NORDBOOKING-invite-worker-form, #NORDBOOKING-direct-add-worker-form', function(e) {
            const $form = $(this);
            const $submitButton = $form.find('button[type="submit"]');
            
            // Clear previous errors
            $form.find('.NORDBOOKING-input-error').each(function() {
                hideFieldError($(this));
            });
            
            // Basic validation
            let hasErrors = false;
            
            $form.find('input[required]').each(function() {
                const $field = $(this);
                const value = $field.val().trim();
                
                if (!value) {
                    showFieldError($field, 'This field is required.');
                    hasErrors = true;
                } else if ($field.attr('type') === 'email' && !validateEmail(value)) {
                    showFieldError($field, 'Please enter a valid email address.');
                    hasErrors = true;
                } else if ($field.attr('type') === 'password' && !validatePassword(value)) {
                    showFieldError($field, 'Password must be at least 8 characters long.');
                    hasErrors = true;
                }
            });
            
            if (hasErrors) {
                e.preventDefault();
                return false;
            }
            
            // Set loading state
            setButtonLoading($submitButton);
        });
    }

    // Initialize when document is ready
    $(document).ready(function() {
        initWorkerEnhancements();
        
        // Re-initialize after any dynamic content changes
        $(document).on('DOMNodeInserted', function() {
            // Debounce to avoid excessive calls
            clearTimeout(window.workerEnhancementTimeout);
            window.workerEnhancementTimeout = setTimeout(initWorkerEnhancements, 100);
        });
    });

    // Export functions for external use
    window.WorkerEnhancements = {
        showInlineAlert: showInlineAlert,
        hideInlineAlert: hideInlineAlert,
        showFieldError: showFieldError,
        hideFieldError: hideFieldError,
        setButtonLoading: setButtonLoading,
        resetButtonLoading: resetButtonLoading,
        handleAjaxError: handleAjaxError,
        validateEmail: validateEmail,
        validatePassword: validatePassword
    };

})(jQuery);