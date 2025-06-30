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
            $('#confirm-name').text(registrationData.name || 'N/A');
            $('#confirm-email').text(registrationData.email || 'N/A');
            // Only show company name if it's not an invitation (invitation check done in PHP, JS just checks if element exists)
            if ($('#confirm-company-name').length) {
                 $('#confirm-company-name').text(registrationData.company_name || 'N/A');
            }
        }
    }

    function validateStep(stepNumber) {
        $registerMessageDiv.hide().removeClass('error success').empty();
        let isValid = true;
        let $currentStepDiv = $('#mobooking-register-step-' + stepNumber);

        // $currentStepDiv.find('.field-error').remove(); // Errors are cleared per field now
        // $currentStepDiv.find('.input-error').removeClass('input-error'); // Errors are cleared per field now
        let overallStepIsValid = true; // Tracks validity of the whole step

        function clearError($field) {
            $field.removeClass('input-error');
            $field.next('.field-error').remove();
        }

        function addError($field, message) {
            clearError($field); // Clear previous error for this field first
            $field.addClass('input-error');
            $field.after('<div class="field-error">' + message + '</div>');
            overallStepIsValid = false; // If any field has an error, the step is invalid
        }

        // --- Define validation functions for individual fields ---
        function validateNameField() {
            const $field = $('#mobooking-user-name');
            const value = $field.val().trim();
            clearError($field);
            if (!value) {
                addError($field, 'Full name is required.');
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
            // Advanced: AJAX check for email uniqueness could be added here for real-time feedback
            return true;
        }

        function validatePasswordField() {
            const $field = $('#mobooking-user-pass');
            const $confirmField = $('#mobooking-user-pass-confirm');
            const value = $field.val(); // No trim for password
            clearError($field);
            let isValidPassword = true;
            if (!value) {
                addError($field, 'Password is required.');
                isValidPassword = false;
            } else if (value.length < 8) {
                addError($field, 'Password must be at least 8 characters.');
                isValidPassword = false;
            }
            // Trigger confirmation validation if password changes
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
            if (!value) {
                addError($field, 'Password confirmation is required.');
                return false;
            } else if (value !== passwordValue) {
                addError($field, 'Passwords do not match.');
                return false;
            }
            return true;
        }

        function validateCompanyNameField() {
            const $field = $('#mobooking-company-name');
            // Only validate if visible and required (not an invitation)
            if ($field.is(':visible') && $field.prop('required')) {
                const value = $field.val().trim();
                clearError($field);
                if (!value) {
                    addError($field, 'Company name is required.');
                    return false;
                }
            } else {
                 clearError($field); // Clear any previous error if field is no longer relevant
            }
            return true;
        }

        // --- Perform validation based on current step ---
        if (stepNumber === 1) {
            // Validate all fields in step 1, and update overallStepIsValid
            if (!validateNameField()) overallStepIsValid = false;
            if (!validateEmailField()) overallStepIsValid = false;
            if (!validatePasswordField()) overallStepIsValid = false;
            if (!validatePasswordConfirmField()) overallStepIsValid = false; // Also validate confirm here
        } else if (stepNumber === 2) {
            if (!validateCompanyNameField()) overallStepIsValid = false;
        }
        // No validation needed for step 3 (confirmation) as it's display only before submit

        if (!overallStepIsValid) {
            // Find the first field with an error in the current step and focus it
            let $firstErrorField = $currentStepDiv.find('.input-error').first();
            if ($firstErrorField.length) {
                $firstErrorField.focus();
            }
            $registerMessageDiv.addClass('error').html('Please correct the errors highlighted above.').show();
        } else {
            $registerMessageDiv.hide().removeClass('error success').empty();
        }
        return overallStepIsValid;
    }

    function collectStepData(stepNumber) {
        if (stepNumber === 1) {
            registrationData.name = $('#mobooking-user-name').val();
            registrationData.email = $('#mobooking-user-email').val();
            registrationData.password = $('#mobooking-user-pass').val();
            registrationData.password_confirm = $('#mobooking-user-pass-confirm').val();
        } else if (stepNumber === 2) {
            registrationData.company_name = $('#mobooking-company-name').val();
        }
        // No data collection for step 3, it's for display
    }

    if ($registerForm.length) {
        showStep(currentStep);

        // Real-time validation event listeners
        $('#mobooking-user-name').on('blur', function() { validateNameField(); });
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
