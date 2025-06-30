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
    const totalSteps = 3;

    // Store form data across steps
    let registrationData = {};

    function updateProgressBar() {
        $('.mobooking-progress-step').removeClass('active');
        $('.mobooking-progress-step[data-step="' + currentStep + '"]').addClass('active');
    }

    function showStep(stepNumber) {
        const $currentActiveStep = $('.mobooking-register-step.active');
        const $targetStep = $('#mobooking-register-step-' + stepNumber);

        if ($currentActiveStep.attr('id') === $targetStep.attr('id')) {
            // Already on the target step, do nothing
            return;
        }

        // Remove 'active' from current step to trigger fade-out
        if ($currentActiveStep.length) {
            $currentActiveStep.removeClass('active');
        }

        // After a short delay (for fade-out to start), hide old steps and show the new one
        setTimeout(function() {
            // Ensure all non-target steps are truly hidden
            $('.mobooking-register-step').each(function() {
                if ($(this).attr('id') !== $targetStep.attr('id')) {
                    $(this).hide().removeClass('active'); // Also remove active just in case
                }
            });

            $targetStep.show(); // Make the target step part of the layout flow

            // Add 'active' class to trigger fade-in. Needs a tiny delay for 'display' to take effect.
             // requestAnimationFrame is better than a fixed timeout for this if available
            requestAnimationFrame(function() {
                $targetStep.addClass('active');
            });

        }, 150); // This should be less than or equal to CSS transition duration (0.3s = 300ms)
                 // We use 150ms to allow the fade-out to begin.

        currentStep = stepNumber;
        updateProgressBar();
        $registerMessageDiv.hide().removeClass('error success').empty(); // Clear messages on step change
    }

    function validateStep(stepNumber) {
        $registerMessageDiv.hide().removeClass('error success').empty();
        let isValid = true;
        let $currentStepDiv = $('#mobooking-register-step-' + stepNumber);

        // Clear previous errors
        $currentStepDiv.find('.field-error').remove();
        $currentStepDiv.find('.input-error').removeClass('input-error');

        function addError($field, message) {
            $field.addClass('input-error');
            $field.after('<div class="field-error" style="color:red; font-size:0.8em; margin-top:2px;">' + message + '</div>');
            isValid = false;
        }

        if (stepNumber === 1) {
            const email = $('#mobooking-user-email').val();
            const password = $('#mobooking-user-pass').val();
            const passwordConfirm = $('#mobooking-user-pass-confirm').val();

            if (!email) addError($('#mobooking-user-email'), 'Email is required.');
            else {
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test(email)) addError($('#mobooking-user-email'), 'Invalid email format.');
            }
            if (!password) addError($('#mobooking-user-pass'), 'Password is required.');
            else if (password.length < 8) addError($('#mobooking-user-pass'), 'Password must be at least 8 characters.');
            if (!passwordConfirm) addError($('#mobooking-user-pass-confirm'), 'Password confirmation is required.');
            else if (password !== passwordConfirm) addError($('#mobooking-user-pass-confirm'), 'Passwords do not match.');
        } else if (stepNumber === 2) {
            const firstName = $('#mobooking-first-name').val();
            const lastName = $('#mobooking-last-name').val();
            if (!firstName) addError($('#mobooking-first-name'), 'First name is required.');
            if (!lastName) addError($('#mobooking-last-name'), 'Last name is required.');
        } else if (stepNumber === 3) {
            const companyName = $('#mobooking-company-name').val();
            if (!companyName) addError($('#mobooking-company-name'), 'Company name is required.');
        }

        if (!isValid) {
            $registerMessageDiv.addClass('error').html('Please correct the errors above.').show();
        }
        return isValid;
    }

    function collectStepData(stepNumber) {
        if (stepNumber === 1) {
            registrationData.email = $('#mobooking-user-email').val();
            registrationData.password = $('#mobooking-user-pass').val();
            registrationData.password_confirm = $('#mobooking-user-pass-confirm').val();
        } else if (stepNumber === 2) {
            registrationData.first_name = $('#mobooking-first-name').val();
            registrationData.last_name = $('#mobooking-last-name').val();
        } else if (stepNumber === 3) {
            registrationData.company_name = $('#mobooking-company-name').val();
        }
    }

    if ($registerForm.length) {
        // Initial setup
        showStep(currentStep);

        // Navigation
        $('#mobooking-step-1-next').on('click', function() {
            if (validateStep(1)) {
                collectStepData(1);
                showStep(2);
            }
        });

        $('#mobooking-step-2-prev').on('click', function() {
            collectStepData(2); // Save data before going back
            showStep(1);
        });
        $('#mobooking-step-2-next').on('click', function() {
            if (validateStep(2)) {
                collectStepData(2);
                showStep(3);
            }
        });

        $('#mobooking-step-3-prev').on('click', function() {
            collectStepData(3); // Save data before going back
            showStep(2);
        });

        $registerForm.on('submit', function(e) {
            e.preventDefault();
            if (!validateStep(3)) {
                return;
            }
            collectStepData(3);
            $registerMessageDiv.hide().removeClass('error success').empty();

            const formData = {
                action: 'mobooking_register',
                nonce: mobooking_auth_params.register_nonce,
                ...registrationData // Spread collected data
            };

            // Check for invitation fields (if any)
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
