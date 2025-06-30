jQuery(document).ready(function($) {
    'use strict';

    // Login Form Logic
    const $loginForm = $('#mobooking-login-form');
    const $loginMessageDiv = $('#mobooking-login-message');

    if ($loginForm.length) {
        $loginForm.on('submit', function(e) {
            e.preventDefault();
            $loginMessageDiv.hide().removeClass('error success').empty();
            const formData = {
                action: 'mobooking_login',
                log: $('#mobooking-user-login').val(),
                pwd: $('#mobooking-user-pass').val(),
                rememberme: $('#mobooking-rememberme').is(':checked') ? 'forever' : '',
                nonce: mobooking_auth_params.login_nonce,
            };
            $.ajax({
                type: 'POST',
                url: mobooking_auth_params.ajax_url,
                data: formData,
                dataType: 'json',
                beforeSend: function() {
                    $loginForm.find('input[type="submit"]').prop('disabled', true).val('Logging in...');
                },
                success: function(response) {
                    if (response.success) {
                        $loginMessageDiv.addClass('success').html(response.data.message).show();
                        if (response.data.redirect_url) {
                            window.location.href = response.data.redirect_url;
                        }
                    } else {
                        $loginMessageDiv.addClass('error').html(response.data.message).show();
                        $loginForm.find('input[type="submit"]').prop('disabled', false).val('Log In');
                    }
                },
                error: function(xhr, status, error) {
                    $loginMessageDiv.addClass('error').html('An unexpected error occurred. Please try again.').show();
                    $loginForm.find('input[type="submit"]').prop('disabled', false).val('Log In');
                    console.error("Login error:", status, error, xhr.responseText);
                }
            });
        });
    }

    // Registration Form Logic
    const $registerForm = $('#mobooking-register-form');
    const $registerMessageDiv = $('#mobooking-register-message');
    let currentStep = 1;
    const totalSteps = 3;
    let registrationData = {}; // Single declaration

    function updateProgressBar() {
        $('.mobooking-progress-step').removeClass('active');
        $('.mobooking-progress-step[data-step="' + currentStep + '"]').addClass('active');
    }

    function showStep(stepNumber) {
        const $currentActiveStep = $('.mobooking-register-step.active');
        const $targetStep = $('#mobooking-register-step-' + stepNumber);

        if ($currentActiveStep.attr('id') === $targetStep.attr('id') && $targetStep.hasClass('active')) {
            return;
        }

        if ($currentActiveStep.length) {
            $currentActiveStep.removeClass('active');
        }

        setTimeout(function() {
            $('.mobooking-register-step').each(function() {
                if ($(this).attr('id') !== $targetStep.attr('id')) {
                    $(this).hide().removeClass('active');
                }
            });

            $targetStep.show();

            requestAnimationFrame(function() {
                $targetStep.addClass('active');
            });

        }, $currentActiveStep.length ? 150 : 0);

        currentStep = stepNumber;
        updateProgressBar();
        $registerMessageDiv.hide().removeClass('error success').empty();

        if (stepNumber === 3) {
            $('#confirm-first-name').text(registrationData.first_name || 'N/A');
            $('#confirm-last-name').text(registrationData.last_name || 'N/A');
            $('#confirm-email').text(registrationData.email || 'N/A');
            if ($('#confirm-company-name').length) {
                 $('#confirm-company-name').text(registrationData.company_name || 'N/A');
            }
        }
    }

    function clearError($field) {
        $field.removeClass('input-error');
        $field.next('.field-error').remove();
    }

    function addError($field, message) {
        clearError($field);
        $field.addClass('input-error');
        $field.after('<div class="field-error">' + message + '</div>');
    }

    function validateFirstNameField() {
        const $field = $('#mobooking-first-name');
        const value = $field.val().trim();
        clearError($field);
        if (!value) {
            addError($field, 'First name is required.');
            return false;
        }
        return true;
    }

    function validateLastNameField() {
        const $field = $('#mobooking-last-name');
        const value = $field.val().trim();
        clearError($field);
        if (!value) {
            addError($field, 'Last name is required.');
            return false;
        }
        return true;
    }

    function validateEmailField() {
        const $field = $('#mobooking-user-email');
        const value = $field.val().trim();
        clearError($field);
        if (!value) {
            addError($field, 'Email is required.');
            return false;
        } else {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(value)) {
                addError($field, 'Invalid email format.');
                return false;
            }
        }
        // Perform AJAX check only if basic format is valid and field is not empty
        if (value && emailPattern.test(value)) {
            // Add a visual indicator for checking
            $field.addClass('checking-email');
            const $emailErrorDiv = $field.next('.field-error-ajax');
            if (!$emailErrorDiv.length) {
                $field.after('<div class="field-error-ajax" style="font-size:0.8em; margin-top:2px; color: hsl(215.4 16.3% 46.9%);">Checking...</div>');
            } else {
                $emailErrorDiv.text('Checking...').removeClass('error success');
            }

            $.ajax({
                type: 'POST',
                url: mobooking_auth_params.ajax_url,
                data: {
                    action: 'mobooking_check_email_exists',
                    email: value,
                    // nonce: mobooking_auth_params.check_email_nonce // Uncomment if nonce check is added in PHP
                },
                dataType: 'json',
                success: function(response) {
                    $field.removeClass('checking-email');
                    const $errorDisplay = $field.next('.field-error-ajax');
                    if (response.success && response.data.exists) {
                        $errorDisplay.text(response.data.message).addClass('error').removeClass('success');
                        // Optionally, call addError to mark field visually, though this AJAX message is more specific
                        // addError($field, response.data.message);
                        // Note: validateStep will still fail if email exists, as server side registration will reject it.
                        // This real-time check is for UX.
                    } else if (response.success && !response.data.exists) {
                        $errorDisplay.text(response.data.message).addClass('success').removeClass('error');
                    } else {
                        // Handle non-success AJAX response (e.g. server error on check)
                        $errorDisplay.text('Could not verify email.').addClass('error').removeClass('success');
                    }
                },
                error: function() {
                    $field.removeClass('checking-email');
                    const $errorDisplay = $field.next('.field-error-ajax');
                    $errorDisplay.text('Error checking email. Please try again.').addClass('error').removeClass('success');
                }
            });
        }
        return true; // Basic format validation still returns true, AJAX provides async feedback
    }

    function validatePasswordField() {
        const $field = $('#mobooking-user-pass');
        const $confirmField = $('#mobooking-user-pass-confirm');
        const value = $field.val();
        clearError($field);
        let isValidPassword = true;
        if (!value) {
            addError($field, 'Password is required.');
            isValidPassword = false;
        } else if (value.length < 8) {
            addError($field, 'Password must be at least 8 characters.');
            isValidPassword = false;
        }
        if ($confirmField.val()) {
            validatePasswordConfirmField();
        }
        return isValidPassword;
    }

    function validatePasswordConfirmField() {
        const $field = $('#mobooking-user-pass-confirm');
        const $passwordField = $('#mobooking-user-pass');
        const value = $field.val();
        const passwordValue = $passwordField.val();
        clearError($field);
        if (!value && $passwordField.val()) {
            addError($field, 'Password confirmation is required.');
            return false;
        } else if (passwordValue && value !== passwordValue) { // Check match only if main password has value
            addError($field, 'Passwords do not match.');
            return false;
        }
        return true;
    }

    function validateCompanyNameField() {
        const $field = $('#mobooking-company-name');
        if ($field.is(':visible') && $field.prop('required')) {
            const value = $field.val().trim();
            clearError($field); // Clear basic "required" error first
            const $ajaxFeedbackDiv = $field.next('.field-error-ajax');
            if ($ajaxFeedbackDiv.length) $ajaxFeedbackDiv.remove(); // Remove old AJAX feedback

            if (!value) {
                addError($field, 'Company name is required.');
                return false; // Basic validation failed
            }

            // AJAX check for slug uniqueness
            $field.addClass('checking-slug');
            $field.after('<div class="field-error-ajax" style="font-size:0.8em; margin-top:2px; color: hsl(215.4 16.3% 46.9%);">Checking availability...</div>');

            $.ajax({
                type: 'POST',
                url: mobooking_auth_params.ajax_url,
                data: {
                    action: 'mobooking_check_company_slug_exists',
                    company_name: value,
                    // nonce: mobooking_auth_params.check_slug_nonce // Uncomment if using nonce in PHP
                },
                dataType: 'json',
                success: function(response) {
                    $field.removeClass('checking-slug');
                    const $errorDisplay = $field.next('.field-error-ajax');
                    if (response.success) {
                        let message = response.data.message;
                        if (response.data.slug_preview) {
                            message += ' (URL preview: .../' + response.data.slug_preview + '/)';
                        }
                        if (response.data.exists) {
                            $errorDisplay.text(message).addClass('warning').removeClass('success error'); // Use a 'warning' class for this message type
                        } else {
                            $errorDisplay.text(message).addClass('success').removeClass('warning error');
                        }
                    } else {
                        $errorDisplay.text(response.data.message || 'Could not verify company name.').addClass('error').removeClass('success warning');
                    }
                },
                error: function() {
                    $field.removeClass('checking-slug');
                    const $errorDisplay = $field.next('.field-error-ajax');
                    $errorDisplay.text('Error checking company name. Please try again.').addClass('error').removeClass('success warning');
                }
            });
             return true; // Basic validation passed, AJAX is for feedback. Final check on server.
        } else {
             clearError($field);
             const $ajaxFeedbackDiv = $field.next('.field-error-ajax');
             if ($ajaxFeedbackDiv.length) $ajaxFeedbackDiv.remove();
        }
        return true;
    }

    function validateStep(stepNumber) {
        $registerMessageDiv.hide().removeClass('error success').empty();
        let overallStepIsValid = true;
        let $currentStepDiv = $('#mobooking-register-step-' + stepNumber);

        $currentStepDiv.find('.field-error').remove();
        $currentStepDiv.find('.input-error').removeClass('input-error');

        if (stepNumber === 1) {
            if (!validateFirstNameField()) overallStepIsValid = false;
            if (!validateLastNameField()) overallStepIsValid = false;
            if (!validateEmailField()) overallStepIsValid = false;
            if (!validatePasswordField()) overallStepIsValid = false;
            if ($('#mobooking-user-pass').val() && !validatePasswordConfirmField()) {
                overallStepIsValid = false;
            }
        } else if (stepNumber === 2) {
            if (!validateCompanyNameField()) overallStepIsValid = false;
        }

        if (!overallStepIsValid) {
            $registerMessageDiv.addClass('error').html('Please correct the errors highlighted above.').show();
        } else {
            $registerMessageDiv.hide().removeClass('error success').empty();
        }
        return overallStepIsValid;
    }

    function collectStepData(stepNumber) {
        if (stepNumber === 1) {
            registrationData.first_name = $('#mobooking-first-name').val();
            registrationData.last_name = $('#mobooking-last-name').val();
            registrationData.email = $('#mobooking-user-email').val();
            registrationData.password = $('#mobooking-user-pass').val();
            registrationData.password_confirm = $('#mobooking-user-pass-confirm').val();
        } else if (stepNumber === 2) {
            registrationData.company_name = $('#mobooking-company-name').val();
        }
    }

    if ($registerForm.length) {
        showStep(currentStep);

        $('#mobooking-first-name').on('blur', function() { validateFirstNameField(); });
        $('#mobooking-last-name').on('blur', function() { validateLastNameField(); });
        $('#mobooking-user-email').on('blur', function() { validateEmailField(); });
        $('#mobooking-user-pass').on('keyup', function() { validatePasswordField(); });
        $('#mobooking-user-pass-confirm').on('keyup', function() { validatePasswordConfirmField(); });
        $('#mobooking-company-name').on('blur', function() { validateCompanyNameField(); });

        $('#mobooking-step-1-next').on('click', function() {
            collectStepData(1);
            if (validateStep(1)) {
                showStep(2);
            }
        });

        $('#mobooking-step-2-prev').on('click', function() {
            collectStepData(2);
            showStep(1);
        });
        $('#mobooking-step-2-next').on('click', function() {
            collectStepData(2);
            if (validateStep(2)) {
                showStep(3);
            }
        });

        $('#mobooking-step-3-prev').on('click', function() {
            showStep(2);
        });

        // Prevent Enter key submission on early steps
        $registerForm.on('keydown', function(e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                if (currentStep === 1 && $('#mobooking-step-1-next').is(':visible')) {
                    e.preventDefault();
                    $('#mobooking-step-1-next').trigger('click');
                } else if (currentStep === 2 && $('#mobooking-step-2-next').is(':visible')) {
                    e.preventDefault();
                    $('#mobooking-step-2-next').trigger('click');
                }
                // If on step 3, Enter key will submit the form as expected.
            }
        });

        $registerForm.on('submit', function(e) {
            e.preventDefault();
            // Final validation of current step (should be step 3)
            // Or rely that previous steps were validated. For safety, can re-validate all.
            // For now, assume step 3 is mostly display and previous steps are fine.
            // If Company Name was optional for workers, it's handled by backend.

            $registerMessageDiv.hide().removeClass('error success').empty();
            const formData = {
                action: 'mobooking_register',
                nonce: mobooking_auth_params.register_nonce,
                ...registrationData
            };

            const inviterId = $('#mobooking-inviter-id').val();
            const assignedRole = $('#mobooking-assigned-role').val();
            const invitationToken = $('#mobooking-invitation-token').val();

            if (inviterId && assignedRole && invitationToken) {
                formData.inviter_id = inviterId;
                formData.role_to_assign = assignedRole;
                formData.invitation_token = invitationToken;
            }

            $.ajax({
                type: 'POST',
                url: mobooking_auth_params.ajax_url,
                data: formData,
                dataType: 'json',
                beforeSend: function() {
                    $registerForm.find('input[type="submit"]').prop('disabled', true).val('Registering...');
                },
                success: function(response) {
                    if (response.success) {
                        $registerMessageDiv.addClass('success').html(response.data.message).show();
                        if (response.data.redirect_url) {
                            $registerForm.hide();
                            $('#mobooking-progress-bar').hide();
                            setTimeout(function() {
                                window.location.href = response.data.redirect_url;
                            }, 1500);
                        }
                    } else {
                        $registerMessageDiv.addClass('error').html(response.data.message).show();
                        $registerForm.find('input[type="submit"]').prop('disabled', false).val('Confirm & Register');
                    }
                },
                error: function() {
                    $registerMessageDiv.addClass('error').html('An unexpected error occurred. Please try again.').show();
                    $registerForm.find('input[type="submit"]').prop('disabled', false).val('Confirm & Register');
                }
            });
        });
    }

    // Forgot Password Form Logic
    const $forgotPasswordForm = $('#mobooking-forgot-password-form');
    const $forgotPasswordMessageDiv = $('#mobooking-forgot-password-message');

    if ($forgotPasswordForm.length) {
        $forgotPasswordForm.on('submit', function(e) {
            e.preventDefault();
            $forgotPasswordMessageDiv.hide().removeClass('error success').empty();
            const email = $('#mobooking-user-email-forgot').val();

            if (!email) {
                $forgotPasswordMessageDiv.addClass('error').html('Please enter your email address.').show();
                return;
            }

            const formData = {
                action: 'mobooking_send_password_reset_link',
                user_email: email,
                nonce: mobooking_auth_params.forgot_password_nonce,
            };

            $.ajax({
                type: 'POST',
                url: mobooking_auth_params.ajax_url,
                data: formData,
                dataType: 'json',
                beforeSend: function() {
                    $forgotPasswordForm.find('input[type="submit"]').prop('disabled', true).val('Sending...');
                },
                success: function(response) {
                    if (response.success) {
                        $forgotPasswordMessageDiv.addClass('success').html(response.data.message).show();
                        $forgotPasswordForm.find('input[type="email"]').val(''); // Clear email field on success
                    } else {
                        $forgotPasswordMessageDiv.addClass('error').html(response.data.message || 'An error occurred.').show();
                    }
                },
                error: function() {
                    $forgotPasswordMessageDiv.addClass('error').html('An unexpected error occurred. Please try again.').show();
                },
                complete: function() {
                    $forgotPasswordForm.find('input[type="submit"]').prop('disabled', false).val('Send Reset Link');
                }
            });
        });
    }
});
