jQuery(document).ready(function($) {
    'use strict';

    // Login Form Logic (remains unchanged)
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
                beforeSend: function() { $loginForm.find('input[type="submit"]').prop('disabled', true).val('Logging in...'); },
                success: function(response) {
                    if (response.success) {
                        $loginMessageDiv.addClass('success').html(response.data.message).show();
                        if (response.data.redirect_url) window.location.href = response.data.redirect_url;
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
    let registrationData = {};

    function debounce(func, delay) {
        let timeout;
        return function(...args) {
            const context = this;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), delay);
        };
    }

    function updateProgressBar() {
        $('.mobooking-progress-step').removeClass('active');
        $('.mobooking-progress-step[data-step="' + currentStep + '"]').addClass('active');
    }

    function getStepButton(stepNumber) {
        if (stepNumber === 1) return $('#mobooking-step-1-next');
        if (stepNumber === 2) return $('#mobooking-step-2-next');
        if (stepNumber === 3) return $('#mobooking-wp-submit-register');
        return null;
    }

    async function updateNextButtonState(stepNumber) {
        const $button = getStepButton(stepNumber);
        if (!$button) return;

        let isStepValid = true;
        $button.prop('disabled', true); // Disable by default while checking

        // Check synchronous fields first
        if (stepNumber === 1) {
            isStepValid = validateFirstNameField(true) &&
                          validateLastNameField(true) &&
                          validatePasswordField(true) &&
                          validatePasswordConfirmField(true);
            // For email, check its current async status if already determined, or wait
            const emailStatus = $('#mobooking-user-email').data('async-status');
            if (emailStatus === 'pending') isStepValid = false; // Still checking
            else if (emailStatus === 'invalid' || emailStatus === 'error') isStepValid = false;
            // If email is valid synchronously but async check not done, validateStep will handle it
        } else if (stepNumber === 2) {
            isStepValid = validateCompanyNameField(true); // Check sync part
            const companyStatus = $('#mobooking-company-name').data('async-status');
            if (companyStatus === 'pending') isStepValid = false;
            else if (companyStatus === 'error') isStepValid = false; // 'warning' for company name is not blocking
        } else if (stepNumber === 3) { // Confirmation step, always enabled if reached
            isStepValid = true;
        }

        // If all synchronous checks pass, then we might enable or wait for async.
        // The main validateStep will handle the full async logic.
        // This function primarily ensures button is disabled if any known error or pending async.
        if(isStepValid) {
             // Re-check if any field in current step is marked as 'pending' for async validation
            $('#mobooking-register-step-' + stepNumber + ' input').each(function() {
                if ($(this).data('async-status') === 'pending') {
                    isStepValid = false;
                    return false; // break loop
                }
                 if ($(this).data('async-status') === 'invalid' || $(this).data('async-status') === 'error') {
                    isStepValid = false;
                    return false; // break loop
                }
            });
        }
        $button.prop('disabled', !isStepValid);
    }


    function showStep(stepNumber) {
        const $currentActiveStep = $('.mobooking-register-step.active');
        const $targetStep = $('#mobooking-register-step-' + stepNumber);

        if ($currentActiveStep.attr('id') === $targetStep.attr('id') && $targetStep.hasClass('active')) return;

        if ($currentActiveStep.length) $currentActiveStep.removeClass('active');

        setTimeout(function() {
            $('.mobooking-register-step').each(function() {
                if ($(this).attr('id') !== $targetStep.attr('id')) $(this).hide().removeClass('active');
            });
            $targetStep.show();
            requestAnimationFrame(function() { $targetStep.addClass('active'); });
        }, $currentActiveStep.length ? 150 : 0);

        currentStep = stepNumber;
        updateProgressBar();
        updateNextButtonState(currentStep); // Update button state when step changes
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
        $field.next('.field-error, .field-error-ajax').remove(); // Clear both sync and async errors
    }

    function addError($field, message, isAsync = false) {
        clearError($field);
        $field.addClass('input-error');
        const errorClass = isAsync ? 'field-error-ajax error' : 'field-error';
        $field.after('<div class="' + errorClass + '">' + message + '</div>');
    }

    function addAjaxFeedback($field, message, type) { // type can be 'success', 'warning', 'error', 'checking'
        clearError($field); // Clear previous sync/async errors
        const className = 'field-error-ajax ' + type;
        let text = message;
        if (type === 'checking') text = 'Checking...';
        $field.after('<div class="' + className + '">' + text + '</div>');
    }

    function validateFirstNameField(isSilent = false) {
        const $field = $('#mobooking-first-name');
        const value = $field.val().trim();
        if (!isSilent) clearError($field);
        if (!value) {
            if (!isSilent) addError($field, 'First name is required.');
            return false;
        }
        return true;
    }

    function validateLastNameField(isSilent = false) {
        const $field = $('#mobooking-last-name');
        const value = $field.val().trim();
        if (!isSilent) clearError($field);
        if (!value) {
            if (!isSilent) addError($field, 'Last name is required.');
            return false;
        }
        return true;
    }

    function validateEmailField(isSilent = false) {
        const $field = $('#mobooking-user-email');
        const value = $field.val().trim();
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!isSilent) clearError($field);
        let isValidSync = true;

        if (!value) {
            if (!isSilent) addError($field, 'Email is required.');
            isValidSync = false;
        } else if (!emailPattern.test(value)) {
            if (!isSilent) addError($field, 'Invalid email format.');
            isValidSync = false;
        }

        $field.data('sync-valid', isValidSync); // Store sync validity

        return new Promise(function(resolve) {
            if (isValidSync) {
                $field.data('async-status', 'pending');
                if (!isSilent) addAjaxFeedback($field, 'Checking...', 'checking');
                updateNextButtonState(currentStep);

                $.ajax({
                    type: 'POST', url: mobooking_auth_params.ajax_url,
                    data: { action: 'mobooking_check_email_exists', email: value, nonce: mobooking_auth_params.check_email_nonce },
                    dataType: 'json',
                    success: function(response) {
                        if (!isSilent) clearError($field); // Clear "Checking..."
                        if (response.success && response.data.exists) {
                            if (!isSilent) addAjaxFeedback($field, response.data.message, 'error');
                            $field.data('async-status', 'invalid'); resolve(false);
                        } else if (response.success && !response.data.exists) {
                            if (!isSilent) addAjaxFeedback($field, response.data.message, 'success');
                            $field.data('async-status', 'valid'); resolve(true);
                        } else {
                            if (!isSilent) addAjaxFeedback($field, response.data.message || 'Could not verify email.', 'error');
                            $field.data('async-status', 'error'); resolve(false);
                        }
                    },
                    error: function() {
                        if (!isSilent) clearError($field);
                        if (!isSilent) addAjaxFeedback($field, 'Error checking email.', 'error');
                        $field.data('async-status', 'error'); resolve(false);
                    },
                    complete: function() { updateNextButtonState(currentStep); }
                });
            } else {
                $field.data('async-status', 'not-applicable'); // Sync validation failed
                updateNextButtonState(currentStep);
                resolve(false); // Sync validation failed
            }
        });
    }

    function validatePasswordField(isSilent = false) {
        const $field = $('#mobooking-user-pass');
        const value = $field.val();
        if (!isSilent) clearError($field);
        let isValid = true;
        if (!value) {
            if (!isSilent) addError($field, 'Password is required.');
            isValid = false;
        } else if (value.length < 8) {
            if (!isSilent) addError($field, 'Password must be at least 8 characters.');
            isValid = false;
        }
        if ($('#mobooking-user-pass-confirm').val() || !isValid) {
             validatePasswordConfirmField(isSilent); // Re-validate confirm if password changes or if password invalid
        }
        return isValid;
    }

    function validatePasswordConfirmField(isSilent = false) {
        const $field = $('#mobooking-user-pass-confirm');
        const $passwordField = $('#mobooking-user-pass');
        const value = $field.val();
        const passwordValue = $passwordField.val();
        if (!isSilent) clearError($field);
        if (!value && passwordValue) {
            if (!isSilent) addError($field, 'Password confirmation is required.');
            return false;
        } else if (passwordValue && value !== passwordValue) {
            if (!isSilent) addError($field, 'Passwords do not match.');
            return false;
        }
        return true;
    }

    function validateCompanyNameField(isSilent = false) {
        const $field = $('#mobooking-company-name');
        let isValidSync = true;
        if (!isSilent) clearError($field);

        if ($field.is(':visible') && $field.prop('required')) {
            const value = $field.val().trim();
            if (!value) {
                if (!isSilent) addError($field, 'Company name is required.');
                isValidSync = false;
            }
            $field.data('sync-valid', isValidSync);

            return new Promise(function(resolve) {
                if (isValidSync) {
                    $field.data('async-status', 'pending');
                    if (!isSilent) addAjaxFeedback($field, 'Checking availability...', 'checking');
                    updateNextButtonState(currentStep);
                    $.ajax({
                        type: 'POST', url: mobooking_auth_params.ajax_url,
                        data: { action: 'mobooking_check_company_slug_exists', company_name: value, nonce: mobooking_auth_params.check_slug_nonce },
                        dataType: 'json',
                        success: function(response) {
                            if (!isSilent) clearError($field);
                            if (response.success) {
                                let message = response.data.message;
                                if (response.data.slug_preview) message += ' (URL: .../' + response.data.slug_preview + '/)';

                                if (response.data.exists) { // "Exists" means it's a warning, not a hard invalidation for UX
                                    if (!isSilent) addAjaxFeedback($field, message, 'warning');
                                    $field.data('async-status', 'warning'); resolve(true);
                                } else {
                                    if (!isSilent) addAjaxFeedback($field, message, 'success');
                                    $field.data('async-status', 'valid'); resolve(true);
                                }
                            } else {
                                if (!isSilent) addAjaxFeedback($field, response.data.message || 'Could not verify.', 'error');
                                $field.data('async-status', 'error'); resolve(false);
                            }
                        },
                        error: function() {
                            if (!isSilent) clearError($field);
                            if (!isSilent) addAjaxFeedback($field, 'Error checking name.', 'error');
                            $field.data('async-status', 'error'); resolve(false);
                        },
                        complete: function() { updateNextButtonState(currentStep); }
                    });
                } else {
                    $field.data('async-status', 'not-applicable');
                    updateNextButtonState(currentStep);
                    resolve(false); // Sync validation failed
                }
            });
        } else {
             if (!isSilent) clearError($field);
             $field.data('sync-valid', true); // Not required or not visible
             $field.data('async-status', 'not-applicable');
             updateNextButtonState(currentStep);
             return Promise.resolve(true); // Valid in this context
        }
    }

    async function validateStep(stepNumber) {
        $registerMessageDiv.hide().removeClass('error success').empty();
        let overallStepIsValid = true;

        if (stepNumber === 1) {
            // Run sync validations first
            if (!validateFirstNameField()) overallStepIsValid = false;
            if (!validateLastNameField()) overallStepIsValid = false;
            if (!validatePasswordField()) overallStepIsValid = false;
            if ($('#mobooking-user-pass').val() && !validatePasswordConfirmField()) overallStepIsValid = false;

            // Await async email validation
            const emailIsValid = await validateEmailField();
            if (!emailIsValid) overallStepIsValid = false;

        } else if (stepNumber === 2) {
            const companyNameIsValid = await validateCompanyNameField();
            if (!companyNameIsValid) overallStepIsValid = false;
        }

        if (!overallStepIsValid) {
            $registerMessageDiv.addClass('error').html('Please correct the errors highlighted above.').show();
        } else {
            $registerMessageDiv.hide().removeClass('error success').empty();
        }
        updateNextButtonState(stepNumber); // Final update after all checks
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
        showStep(currentStep); // Initial call to set button state

        const debouncedValidateFirstName = debounce(function() { validateFirstNameField().finally(() => updateNextButtonState(1)); }, 500);
        const debouncedValidateLastName = debounce(function() { validateLastNameField().finally(() => updateNextButtonState(1)); }, 500);
        const debouncedValidateEmail = debounce(function() { validateEmailField().finally(() => updateNextButtonState(1)); }, 750);
        const debouncedValidateCompanyName = debounce(function() { validateCompanyNameField().finally(() => updateNextButtonState(2)); }, 750);

        $('#mobooking-first-name').on('blur', function() { validateFirstNameField().finally(() => updateNextButtonState(1)); }).on('keyup', debouncedValidateFirstName);
        $('#mobooking-last-name').on('blur', function() { validateLastNameField().finally(() => updateNextButtonState(1)); }).on('keyup', debouncedValidateLastName);
        $('#mobooking-user-email').on('blur', function() { validateEmailField().finally(() => updateNextButtonState(1)); }).on('keyup', debouncedValidateEmail);

        $('#mobooking-user-pass').on('keyup', function() { validatePasswordField(); updateNextButtonState(1); });
        $('#mobooking-user-pass-confirm').on('keyup', function() { validatePasswordConfirmField(); updateNextButtonState(1); });

        $('#mobooking-company-name').on('blur', function() { validateCompanyNameField().finally(() => updateNextButtonState(2)); }).on('keyup', debouncedValidateCompanyName);

        $('#mobooking-step-1-next').on('click', async function() {
            collectStepData(1);
            const isValid = await validateStep(1);
            if (isValid) {
                showStep(2);
            }
        });

        $('#mobooking-step-2-prev').on('click', function() {
            collectStepData(2);
            showStep(1);
        });
        $('#mobooking-step-2-next').on('click', async function() {
            collectStepData(2);
            const isValid = await validateStep(2);
            if (isValid) {
                showStep(3);
            }
        });

        $('#mobooking-step-3-prev').on('click', function() {
            showStep(2);
        });

        $registerForm.on('keydown', function(e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                if (currentStep === 1 && $('#mobooking-step-1-next').is(':visible') && !$('#mobooking-step-1-next').is(':disabled')) {
                    e.preventDefault(); $('#mobooking-step-1-next').trigger('click');
                } else if (currentStep === 2 && $('#mobooking-step-2-next').is(':visible') && !$('#mobooking-step-2-next').is(':disabled')) {
                    e.preventDefault(); $('#mobooking-step-2-next').trigger('click');
                }
            }
        });

        $registerForm.on('submit', async function(e) {
            e.preventDefault();
            const isStep3Valid = await validateStep(3); // Step 3 has no validation, effectively true
            if (!isStep3Valid) return; // Should not happen for step 3

            // Final check of all data before submission (optional, as steps should be valid)
            // For robustness, re-validate all previous steps silently
            const isStep1DataValid = await validateStep(1); // This will run async checks again
            if (!isStep1DataValid) {
                showStep(1); // Go back to step with error
                return;
            }
            const isStep2DataValid = await validateStep(2); // This will run async checks again
            if (!isStep2DataValid) {
                showStep(2); // Go back to step with error
                return;
            }


            $registerMessageDiv.hide().removeClass('error success').empty();
            const formData = { action: 'mobooking_register', nonce: mobooking_auth_params.register_nonce, ...registrationData };
            const inviterId = $('#mobooking-inviter-id').val();
            const assignedRole = $('#mobooking-assigned-role').val();
            const invitationToken = $('#mobooking-invitation-token').val();
            if (inviterId && assignedRole && invitationToken) {
                formData.inviter_id = inviterId; formData.role_to_assign = assignedRole; formData.invitation_token = invitationToken;
            }

            $.ajax({
                type: 'POST', url: mobooking_auth_params.ajax_url, data: formData, dataType: 'json',
                beforeSend: function() {
                    console.log('MoBooking Register: AJAX beforeSend');
                    $registerForm.find('input[type="submit"]').prop('disabled', true).val('Registering...');
                },
                success: function(response) {
                    console.log('MoBooking Register: AJAX success response:', response);
                    if (response && response.success) {
                        $registerMessageDiv.addClass('success').html(response.data.message).show();
                        if (response.data.redirect_url) {
                            $registerForm.hide(); $('#mobooking-progress-bar').hide();
                            console.log('MoBooking Register: Redirecting to ' + response.data.redirect_url);
                            setTimeout(function() { window.location.href = response.data.redirect_url; }, 1500);
                        }
                    } else {
                        console.error('MoBooking Register: AJAX success but response.success is false or response is malformed.', response);
                        $registerMessageDiv.addClass('error').html(response && response.data && response.data.message ? response.data.message : 'An unknown error occurred.').show();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('MoBooking Register: AJAX error. Status:', textStatus, 'Error:', errorThrown, 'Response Text:', jqXHR.responseText, 'jqXHR:', jqXHR);
                    let errorMessage = 'An unexpected AJAX error occurred. Please check the console and try again.';
                    if (jqXHR.responseText) {
                        try {
                            const errorResponse = JSON.parse(jqXHR.responseText);
                            if (errorResponse && errorResponse.data && errorResponse.data.message) errorMessage = errorResponse.data.message;
                            else if (jqXHR.responseText.length < 200) errorMessage = jqXHR.responseText;
                        } catch (e) {
                             if (jqXHR.responseText && jqXHR.responseText.length < 200) errorMessage = "Error: " + jqXHR.responseText;
                             else errorMessage = "An unexpected error occurred. Server response not in expected format.";
                        }
                    }
                    $registerMessageDiv.addClass('error').html(errorMessage).show();
                },
                complete: function(jqXHR, textStatus) {
                    console.log('MoBooking Register: AJAX complete. Status:', textStatus);
                    if (!$registerMessageDiv.hasClass('success') || ($registerMessageDiv.hasClass('success') && !$registerForm.is(':hidden'))) {
                         $registerForm.find('input[type="submit"]').prop('disabled', false).val('Confirm & Register');
                    }
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
                $forgotPasswordMessageDiv.addClass('error').html('Please enter your email address.').show(); return;
            }
            const formData = { action: 'mobooking_send_password_reset_link', user_email: email, nonce: mobooking_auth_params.forgot_password_nonce };
            $.ajax({
                type: 'POST', url: mobooking_auth_params.ajax_url, data: formData, dataType: 'json',
                beforeSend: function() { $forgotPasswordForm.find('input[type="submit"]').prop('disabled', true).val('Sending...'); },
                success: function(response) {
                    if (response.success) {
                        $forgotPasswordMessageDiv.addClass('success').html(response.data.message).show();
                        $forgotPasswordForm.find('input[type="email"]').val('');
                    } else {
                        $forgotPasswordMessageDiv.addClass('error').html(response.data.message || 'An error occurred.').show();
                    }
                },
                error: function() { $forgotPasswordMessageDiv.addClass('error').html('An unexpected error occurred. Please try again.').show(); },
                complete: function() { $forgotPasswordForm.find('input[type="submit"]').prop('disabled', false).val('Send Reset Link'); }
            });
        });
    }
});
