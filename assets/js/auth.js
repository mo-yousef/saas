jQuery(document).ready(function($) {
    'use strict';

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

    const $registerForm = $('#mobooking-register-form');
    const $registerMessageDiv = $('#mobooking-register-message');
    let currentStep = 1;
    const totalSteps = 3; // Step 1: Personal, Step 2: Business, Step 3: Confirm

    // Store form data across steps
    let registrationData = {};

    function updateProgressBar() {
        $('.mobooking-progress-step').removeClass('active');
        $('.mobooking-progress-step[data-step="' + currentStep + '"]').addClass('active');
    }

    function showStep(stepNumber) {
        const $currentActiveStep = $('.mobooking-register-step.active');
        const $targetStep = $('#mobooking-register-step-' + stepNumber);

        if ($currentActiveStep.attr('id') === $targetStep.attr('id') && $targetStep.hasClass('active')) {
            return; // Already on the target step and it's active
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

        }, $currentActiveStep.length ? 150 : 0); // No delay if it's the initial show

        currentStep = stepNumber;
        updateProgressBar();
        $registerMessageDiv.hide().removeClass('error success').empty();

        // Populate confirmation step if showing step 3
        if (stepNumber === 3) {
            $('#confirm-first-name').text(registrationData.first_name || 'N/A');
            $('#confirm-last-name').text(registrationData.last_name || 'N/A');
            $('#confirm-email').text(registrationData.email || 'N/A');
            if ($('#confirm-company-name').length) { // Only if element exists (not for worker invite)
                 $('#confirm-company-name').text(registrationData.company_name || 'N/A');
            }
        }
    }

    // --- Main Step Validation Logic ---
    function validateStep(stepNumber) {
        $registerMessageDiv.hide().removeClass('error success').empty();
        let isValid = true;
        let $currentStepDiv = $('#mobooking-register-step-' + stepNumber);

    let registrationData = {};

    // --- Validation Helper Functions (moved to outer scope) ---
    function clearError($field) {
        $field.removeClass('input-error');
        $field.next('.field-error').remove();
    }

    function addErrorToStep($field, message, $stepDiv) {
        clearError($field);
        $field.addClass('input-error');
        $field.after('<div class="field-error">' + message + '</div>');
        // This function will now be used by validateStep,
        // so overallStepIsValid will be managed there.
    }

    // --- Individual Field Validation Functions (now in correct scope) ---
    function validateFirstNameField() {
        const $field = $('#mobooking-first-name'); // Assuming ID is mobooking-first-name
        const value = $field.val().trim();
        clearError($field);
        if (!value) {
            addErrorToStep($field, 'First name is required.', $field.closest('.mobooking-register-step'));
            return false;
        }
        return true;
    }

    function validateLastNameField() {
        const $field = $('#mobooking-last-name'); // Assuming ID is mobooking-last-name
        const value = $field.val().trim();
        clearError($field);
        if (!value) {
            addErrorToStep($field, 'Last name is required.', $field.closest('.mobooking-register-step'));
            return false;
        }
        return true;
    }

    function validateEmailField() {
        const $field = $('#mobooking-user-email');
        const value = $field.val().trim();
        clearError($field);
        if (!value) {
            addErrorToStep($field, 'Email is required.', $field.closest('.mobooking-register-step'));
            return false;
        } else {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(value)) {
                addErrorToStep($field, 'Invalid email format.', $field.closest('.mobooking-register-step'));
                return false;
            }
        }
        return true;
    }

    function validatePasswordField() {
        const $field = $('#mobooking-user-pass');
        const $confirmField = $('#mobooking-user-pass-confirm');
        const value = $field.val();
        clearError($field);
        let isValidPassword = true;
        if (!value) {
            addErrorToStep($field, 'Password is required.', $field.closest('.mobooking-register-step'));
            isValidPassword = false;
        } else if (value.length < 8) {
            addErrorToStep($field, 'Password must be at least 8 characters.', $field.closest('.mobooking-register-step'));
            isValidPassword = false;
        }
        if ($confirmField.val()) { // Re-validate confirm field if password changes
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
        if (!value && $passwordField.val()) { // Only required if password is also entered
            addErrorToStep($field, 'Password confirmation is required.', $field.closest('.mobooking-register-step'));
            return false;
        } else if (value !== passwordValue) {
            addErrorToStep($field, 'Passwords do not match.', $field.closest('.mobooking-register-step'));
            return false;
        }
        return true;
    }

    function validateCompanyNameField() {
        const $field = $('#mobooking-company-name');
        if ($field.is(':visible') && $field.prop('required')) {
            const value = $field.val().trim();
            clearError($field);
            if (!value) {
                addErrorToStep($field, 'Company name is required.', $field.closest('.mobooking-register-step'));
                return false;
            }
        } else {
             clearError($field);
        }
        return true;
    }

    // --- Main Step Validation Logic ---
    function validateStep(stepNumber) {
        $registerMessageDiv.hide().removeClass('error success').empty();
        let overallStepIsValid = true;
        let $currentStepDiv = $('#mobooking-register-step-' + stepNumber);

        // Clear previous errors for the current step before re-validating
        $currentStepDiv.find('.field-error').remove();
        $currentStepDiv.find('.input-error').removeClass('input-error');

        if (stepNumber === 1) {
            // Order of validation matters for user experience, so check one by one
            if (!validateFirstNameField()) overallStepIsValid = false;
            if (!validateLastNameField()) overallStepIsValid = false; // Validate last name after first
            if (!validateEmailField()) overallStepIsValid = false;
            if (!validatePasswordField()) overallStepIsValid = false;
            // Only validate confirm_password if password field itself is valid and has content
            if (overallStepIsValid && $('#mobooking-user-pass').val() && !validatePasswordConfirmField()) {
                overallStepIsValid = false;
            }
        } else if (stepNumber === 2) {
            if (!validateCompanyNameField()) overallStepIsValid = false;
        }

        if (!overallStepIsValid) {
            let $firstErrorField = $currentStepDiv.find('.input-error').first();
            if ($firstErrorField.length) {
                // $firstErrorField.focus(); // Focusing can be disruptive, especially if field is hidden by other UI
            }
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

        // Real-time validation event listeners
        $('#mobooking-first-name').on('blur', function() { validateFirstNameField(); });
        $('#mobooking-last-name').on('blur', function() { validateLastNameField(); });
        $('#mobooking-user-email').on('blur', function() { validateEmailField(); });
        $('#mobooking-user-pass').on('keyup', function() { validatePasswordField(); });
        $('#mobooking-user-pass-confirm').on('keyup', function() { validatePasswordConfirmField(); });
        $('#mobooking-company-name').on('blur', function() { validateCompanyNameField(); });


        // Navigation
        $('#mobooking-step-1-next').on('click', function() {
            collectStepData(1); // Collect data first
            if (validateStep(1)) { // Then validate
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
            // No data to collect from step 3 itself
            showStep(2);
        });

        $registerForm.on('submit', function(e) {
            e.preventDefault();
            // Final validation happens before this via button clicks usually,
            // but good to have a final check or rely on server.
            // For now, assume data is collected and validated up to this point.
            $registerMessageDiv.hide().removeClass('error success').empty();

            const formData = {
                action: 'mobooking_register',
                nonce: mobooking_auth_params.register_nonce,
                ...registrationData
            };

            // Check for invitation fields
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
                        // Hide form or redirect
                        if (response.data.redirect_url) {
                            $registerForm.hide();
                            $('#mobooking-progress-bar').hide();
                            setTimeout(function() {
                                window.location.href = response.data.redirect_url;
                            }, 1500);
                        }
                    } else {
                        $registerMessageDiv.addClass('error').html(response.data.message).show();
                        $registerForm.find('input[type="submit"]').prop('disabled', false).val('Register');
                    }
                },
                error: function() {
                    $registerMessageDiv.addClass('error').html('An unexpected error occurred. Please try again.').show();
                    $registerForm.find('input[type="submit"]').prop('disabled', false).val('Register');
                }
            });
        });
    }
});
