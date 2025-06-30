// MoBooking Auth Forms: Registration, Login, Forgot Password
jQuery(document).ready(function ($) {
  "use strict";

  // --- Helper Functions ---
  function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  function showFieldError($field, message, errorContainerSelector = null) {
    $field.addClass("error");
    $field.siblings(".field-error").remove(); // Remove previous error for this field
    const errorHtml = `<div class="field-error" style="color: #d63638; font-size: 0.875em; margin-top: 0.25rem;">${message}</div>`;
    if (errorContainerSelector) {
        $(errorContainerSelector).html(errorHtml).show();
    } else {
        $field.after(errorHtml);
    }
    $field.one("input change", function () {
      $(this).removeClass("error");
      $(this).siblings(".field-error").remove();
      if (errorContainerSelector) { $(errorContainerSelector).hide().empty(); }
    });
  }

  function displayGlobalMessage($messageDiv, message, isSuccess) {
    const messageClass = isSuccess ? "success" : "error";
    const style = isSuccess
      ? "color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb;"
      : "color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb;";
    $messageDiv
      .removeClass("success error")
      .addClass(messageClass)
      .html(`<div style="padding: 0.75rem 1.25rem; margin-bottom: 1rem; border-radius: 0.25rem; ${style}">${message}</div>`)
      .show();
  }


  // --- Registration Form Logic ---
  const $registerForm = $("#mobooking-register-form");
  const $registerMessageDiv = $("#mobooking-register-message");

  if ($registerForm.length) {
    let registrationData = {};
    let currentStep = 1;
    const totalSteps = $("#mobooking-progress-bar .mobooking-progress-step").length || 3; // Dynamically count steps or default

    function updateProgressBar() {
        $("#mobooking-progress-bar .mobooking-progress-step").removeClass("active completed");
        for (let i = 1; i <= totalSteps; i++) {
            if (i < currentStep) {
                $("#mobooking-progress-bar .mobooking-progress-step[data-step='" + i + "']").addClass("completed");
            } else if (i === currentStep) {
                $("#mobooking-progress-bar .mobooking-progress-step[data-step='" + i + "']").addClass("active");
            }
        }
    }

    function showStep(step) {
      $(".mobooking-register-step").removeClass('active').hide();
      $("#mobooking-register-step-" + step).show().addClass('active');
      currentStep = step;
      updateProgressBar();
    }

    showStep(1); // Initialize first step

    $("#mobooking-step-1-next").on("click", async function () {
      if (await validateRegisterStep(1)) showStep(2);
    });
    $("#mobooking-step-2-prev").on("click", function () { showStep(1); });
    $("#mobooking-step-2-next").on("click", async function () {
      if (await validateRegisterStep(2)) {
        updateConfirmationStep();
        showStep(3);
      }
    });
    $("#mobooking-step-3-prev").on("click", function () { showStep(2); });

    async function checkEmailExists(email) {
      return new Promise((resolve, reject) => {
        $.ajax({
          type: "POST",
          url: mobooking_auth_params.ajax_url,
          data: {
            action: "mobooking_check_email_exists",
            email: email,
            // If you have a specific nonce for email checking and it's localized in mobooking_auth_params:
            // nonce: mobooking_auth_params.check_email_nonce
          },
          dataType: "json",
          success: (response) => resolve(response.data && response.data.exists),
          error: () => reject(new Error("Failed to check email")),
        });
      });
    }

    async function validateRegisterStep(step) {
      $(".field-error").remove();
      let isValid = true;
      // Consolidate data gathering
      registrationData.first_name = $("#mobooking-first-name").val().trim();
      registrationData.last_name = $("#mobooking-last-name").val().trim();
      registrationData.email = $("#mobooking-user-email").val().trim();
      registrationData.password = $("#mobooking-user-pass").val();
      registrationData.password_confirm = $("#mobooking-user-pass-confirm").val();
      registrationData.company_name = $("#mobooking-company-name").val() ? $("#mobooking-company-name").val().trim() : "";


      if (step === 1) {
        if (!registrationData.first_name) { showFieldError($("#mobooking-first-name"), "First name is required."); isValid = false; }
        if (!registrationData.last_name) { showFieldError($("#mobooking-last-name"), "Last name is required."); isValid = false; }
        if (!registrationData.email || !isValidEmail(registrationData.email)) {
            showFieldError($("#mobooking-user-email"), "Valid email is required."); isValid = false;
        } else if (!$("#mobooking-user-email").is('[readonly]')) { // Only check email if not pre-filled (invitation) and email format is valid
            try {
                if (await checkEmailExists(registrationData.email)) {
                    showFieldError($("#mobooking-user-email"), "This email is already registered.");
                    isValid = false;
                }
            } catch (error) { console.error("Email validation error:", error); /* Allow submission if check fails, backend will catch it */ }
        }
        if (!registrationData.password || registrationData.password.length < 8) { showFieldError($("#mobooking-user-pass"), "Password must be at least 8 characters."); isValid = false; }
        if (registrationData.password !== registrationData.password_confirm) { showFieldError($("#mobooking-user-pass-confirm"), "Passwords do not match."); isValid = false; }
      }

      if (step === 2) {
        const isInvitation = $("#mobooking-inviter-id").length > 0 && $("#mobooking-inviter-id").val() !== "";
        if (!isInvitation && !registrationData.company_name) {
          // Company name is only required if it's not an invitation flow
          // Check if company name field exists and is visible before requiring
          if ($("#mobooking-company-name").is(":visible")) {
            showFieldError($("#mobooking-company-name"), "Company name is required.");
            isValid = false;
          }
        }
      }
      // Step 3 is confirmation, no new validation, but ensure previous steps were valid.
      return isValid;
    }

    function updateConfirmationStep() {
      $("#confirm-first-name").text(registrationData.first_name);
      $("#confirm-last-name").text(registrationData.last_name);
      $("#confirm-email").text(registrationData.email);
      const isInvitation = $("#mobooking-inviter-id").length > 0 && $("#mobooking-inviter-id").val() !== "";
      if (!isInvitation && registrationData.company_name) {
        $("#confirm-company-name").text(registrationData.company_name);
        $("#confirm-company-name-p").show();
      } else {
        $("#confirm-company-name-p").hide();
      }
    }

    $registerForm.on("submit", async function (e) {
      e.preventDefault();
      // Ensure all steps are validated before final submission
      if (!(await validateRegisterStep(1)) ) {
        showStep(1); // Go back to step 1 if invalid
        displayGlobalMessage($registerMessageDiv, "Please correct the errors in Step 1.", false);
        return;
      }
      if (!(await validateRegisterStep(2))) {
        showStep(2); // Go back to step 2 if invalid
        displayGlobalMessage($registerMessageDiv, "Please correct the errors in Step 2.", false);
        return;
      }


      $registerMessageDiv.hide().empty();
      const $submitButton = $registerForm.find('input[type="submit"]');
      const originalButtonText = $submitButton.val();

      const formData = {
        action: "mobooking_register",
        nonce: mobooking_auth_params.register_nonce,
        first_name: registrationData.first_name,
        last_name: registrationData.last_name,
        email: registrationData.email,
        password: registrationData.password,
        password_confirm: registrationData.password_confirm,
        company_name: registrationData.company_name,
      };

      const inviterId = $("#mobooking-inviter-id").val();
      const assignedRole = $("#mobooking-assigned-role").val();
      const invitationToken = $("#mobooking-invitation-token").val();

      if (inviterId && assignedRole && invitationToken) {
        formData.inviter_id = inviterId;
        formData.role_to_assign = assignedRole;
        formData.invitation_token = invitationToken;
        formData.company_name = ''; // Ensure company name is empty for invitation flow
      }

      $.ajax({
        type: "POST",
        url: mobooking_auth_params.ajax_url,
        data: formData,
        dataType: "json",
        beforeSend: () => $submitButton.prop("disabled", true).val("Creating Account..."),
        success: (response) => {
          if (response.success) {
            $registerForm.hide();
            $("#mobooking-progress-bar").hide();
            showSuccessMessageWithCountdown(registrationData.first_name || "there", response.data.redirect_url || "/dashboard/");
          } else {
            displayGlobalMessage($registerMessageDiv, response.data.message || "Registration failed. Please try again.", false);
          }
        },
        error: (jqXHR) => {
            let errorMsg = "An unexpected error occurred. Please try again.";
            if (jqXHR.responseJSON && jqXHR.responseJSON.data && jqXHR.responseJSON.data.message) {
                errorMsg = jqXHR.responseJSON.data.message;
            } else if (jqXHR.responseText) {
                 try { // Try to parse if it's a stringified JSON error
                    const errorResponse = JSON.parse(jqXHR.responseText);
                    if (errorResponse && errorResponse.data && errorResponse.data.message) {
                        errorMsg = errorResponse.data.message;
                    }
                } catch (e) { /* Use default error or jqXHR.responseText if short */
                    if (jqXHR.responseText.length < 200) errorMsg = jqXHR.responseText;
                }
            }
          displayGlobalMessage($registerMessageDiv, errorMsg, false);
        },
        complete: () => {
            // Only re-enable if not successful (form hidden on success)
            if (!$registerForm.is(':hidden')) {
                 $submitButton.prop("disabled", false).val(originalButtonText);
            }
        }
      });
    });

    function showSuccessMessageWithCountdown(userName, redirectUrl) {
      let countdown = 3;
      // Assuming $registerMessageDiv is inside the main form container which we want to replace.
      const $formContainer = $registerForm.closest('.mobooking-auth-form-wrapper');

      function updateMessage() {
        const message = `
            <div style="text-align: center; padding: 30px; background: #f0f9ff; border: 2px solid #0ea5e9; border-radius: 8px; margin: 20px 0;">
                <div style="color: #0ea5e9; font-size: 48px; margin-bottom: 20px;">✓</div>
                <h3 style="color: #0369a1; margin: 0 0 15px 0; font-size: 24px;">Welcome, ${userName}!</h3>
                <p style="color: #0369a1; margin: 0 0 20px 0; font-size: 16px;">
                    Your account is ready. Redirecting in ${countdown} second${countdown !== 1 ? "s" : ""}...
                </p>
                <p style="margin-top: 15px; font-size: 14px; color: #64748b;">
                    <a href="${redirectUrl}" style="color: #0ea5e9; text-decoration: none;">Go to Dashboard Now</a>
                </p>
            </div>`;
        if ($formContainer.length) {
            $formContainer.html(message).show();
        } else { // Fallback if the wrapper isn't found
            $registerMessageDiv.html(message).addClass('success').show();
        }
      }
      updateMessage();
      const interval = setInterval(() => {
        countdown--;
        if (countdown >= 0) updateMessage();
        else {
          clearInterval(interval);
          window.location.href = redirectUrl;
        }
      }, 1000);
    }
  }


  // --- Login Form Logic ---
  const $loginForm = $("#mobooking-login-form");
  const $loginMessageDiv = $("#mobooking-login-message");

  if ($loginForm.length) {
    $loginForm.on("submit", function (e) {
      e.preventDefault();
      $loginMessageDiv.hide().empty().removeClass("error success");
      $(".field-error").remove(); // Clear previous field errors

      const email = $("#mobooking-user-login").val().trim();
      const password = $("#mobooking-user-pass").val();
      const rememberMe = $("#mobooking-rememberme").is(":checked");
      const $submitButton = $loginForm.find('input[type="submit"]');
      const originalButtonText = $submitButton.val();

      let valid = true;
      if (!email || !isValidEmail(email)) {
        showFieldError($("#mobooking-user-login"), "Please enter a valid email address.");
        valid = false;
      }
      if (!password) {
        showFieldError($("#mobooking-user-pass"), "Password is required.");
        valid = false;
      }
      if (!valid) return;

      $.ajax({
        type: "POST",
        url: mobooking_auth_params.ajax_url,
        data: {
          action: "mobooking_login",
          nonce: mobooking_auth_params.login_nonce,
          log: email,
          pwd: password,
          rememberme: rememberMe ? "forever" : "",
        },
        dataType: "json",
        beforeSend: () => $submitButton.prop("disabled", true).val("Logging In..."),
        success: (response) => {
          if (response.success) {
            displayGlobalMessage($loginMessageDiv, response.data.message || "Login successful! Redirecting...", true);
            window.location.href = response.data.redirect_url || "/dashboard/";
          } else {
            displayGlobalMessage($loginMessageDiv, response.data.message || "Login failed. Please check your credentials.", false);
          }
        },
        error: (jqXHR) => {
            let errorMsg = "An unexpected error occurred. Please try again.";
            if (jqXHR.responseJSON && jqXHR.responseJSON.data && jqXHR.responseJSON.data.message) {
                errorMsg = jqXHR.responseJSON.data.message;
            } else if (jqXHR.responseText) {
                 try {
                    const errorResponse = JSON.parse(jqXHR.responseText);
                    if (errorResponse && errorResponse.data && errorResponse.data.message) {
                        errorMsg = errorResponse.data.message;
                    }
                } catch (e) {
                    if (jqXHR.responseText.length < 200) errorMsg = jqXHR.responseText;
                }
            }
          displayGlobalMessage($loginMessageDiv, errorMsg, false);
        },
        complete: () => $submitButton.prop("disabled", false).val(originalButtonText),
      });
    });
  }


  // --- Forgot Password Form Logic ---
  const $forgotPasswordForm = $("#mobooking-forgot-password-form");
  const $forgotPasswordMessageDiv = $("#mobooking-forgot-password-message");

  if ($forgotPasswordForm.length) {
    $forgotPasswordForm.on("submit", function (e) {
      e.preventDefault();
      $forgotPasswordMessageDiv.hide().empty().removeClass("error success");
      $(".field-error").remove(); // Clear previous field errors

      const email = $("#mobooking-user-email-forgot").val().trim();
      const $submitButton = $forgotPasswordForm.find('input[type="submit"]');
      const originalButtonText = $submitButton.val();

      if (!email || !isValidEmail(email)) {
        showFieldError($("#mobooking-user-email-forgot"), "Please enter a valid email address.");
        return;
      }

      $.ajax({
        type: "POST",
        url: mobooking_auth_params.ajax_url,
        data: {
          action: "mobooking_send_password_reset_link",
          nonce: mobooking_auth_params.forgot_password_nonce,
          user_email: email,
        },
        dataType: "json",
        beforeSend: () => $submitButton.prop("disabled", true).val("Sending Link..."),
        success: (response) => {
          // Backend sends success true even if email not found, for security.
          displayGlobalMessage($forgotPasswordMessageDiv, response.data.message || "If an account with that email exists, a password reset link has been sent.", true);
          if (response.success) { // This will usually be true from backend
             $forgotPasswordForm.find('input[type="email"]').val(''); // Clear email field
          }
        },
        error: (jqXHR) => {
            // This error block might be hit for network errors or if server sends non-JSON/non-200 response
            // For security, still show a generic success-like message unless it's a clear client/network fault
            let errorMsg = "An issue occurred. If an account with that email exists, a link has been sent. Please check your connection or contact support if issues persist.";
            // If server explicitly sent an error with success:false (e.g. bad nonce, which usually dies)
            if (jqXHR.responseJSON && jqXHR.responseJSON.data && jqXHR.responseJSON.data.message && jqXHR.responseJSON.success === false) {
                 errorMsg = jqXHR.responseJSON.data.message;
                 displayGlobalMessage($forgotPasswordMessageDiv, errorMsg, false);
            } else {
                 displayGlobalMessage($forgotPasswordMessageDiv, errorMsg, true); // Treat as success-like for enumeration protection
            }
        },
        complete: () => $submitButton.prop("disabled", false).val(originalButtonText),
      });
    });
  }
});
