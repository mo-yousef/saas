jQuery(document).ready(function($) {
    'use strict';

    const $loginForm = $('#mobooking-login-form');
    const $messageDiv = $('#mobooking-login-message');

    if ($loginForm.length) {
        $loginForm.on('submit', function(e) {
            e.preventDefault();
            $messageDiv.hide().removeClass('error success').empty();

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
                        $messageDiv.addClass('success').html(response.data.message).show();
                        if (response.data.redirect_url) {
                            window.location.href = response.data.redirect_url;
                        }
                    } else {
                        $messageDiv.addClass('error').html(response.data.message).show();
                        $loginForm.find('input[type="submit"]').prop('disabled', false).val('Log In');
                    }
                },
                error: function(xhr, status, error) {
                    $messageDiv.addClass('error').html('An unexpected error occurred. Please try again.').show();
                    $loginForm.find('input[type="submit"]').prop('disabled', false).val('Log In');
                    console.error("Login error:", status, error, xhr.responseText);
                }
            });
        });
    }

    // Registration form handler will be added here later
    const $registerForm = $('#mobooking-register-form');
    const $registerMessageDiv = $('#mobooking-register-message');

    if ($registerForm.length) {
        $registerForm.on('submit', function(e) {
            e.preventDefault();
            $registerMessageDiv.hide().removeClass('error success').empty();

            const email = $('#mobooking-user-email').val();
            const password = $('#mobooking-user-pass').val();
            const passwordConfirm = $('#mobooking-user-pass-confirm').val();

            if (!email || !password || !passwordConfirm) {
                $registerMessageDiv.addClass('error').html('All fields are required.').show();
                return;
            }
            if (password !== passwordConfirm) {
                $registerMessageDiv.addClass('error').html('Passwords do not match.').show();
                return;
            }
            if (password.length < 8) {
                $registerMessageDiv.addClass('error').html('Password must be at least 8 characters long.').show();
                return;
            }


            const formData = {
                action: 'mobooking_register',
                email: email,
                password: password,
                password_confirm: passwordConfirm,
                nonce: mobooking_auth_params.register_nonce
            };

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
                            window.location.href = response.data.redirect_url;
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
