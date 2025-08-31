// MoBooking Auth Forms: Registration, Login, Forgot Password
// Complete Enhanced Version with Debug Tree and Fixed Redirect
jQuery(document).ready(function ($) {
  "use strict";

  // Debug Tree System
  const DEBUG = {
    enabled: true,
    level: 0,
    indent: "  ",

    log: function (message, data = null) {
      if (!this.enabled) return;
      const prefix = "🔍 MoBooking Debug:" + this.indent.repeat(this.level);
      if (data) {
        console.log(prefix + " " + message, data);
      } else {
        console.log(prefix + " " + message);
      }
    },

    group: function (title) {
      if (!this.enabled) return;
      console.group("🔍 MoBooking Debug: " + title);
      this.level++;
    },

    groupEnd: function () {
      if (!this.enabled) return;
      this.level = Math.max(0, this.level - 1);
      console.groupEnd();
    },

    error: function (message, error = null) {
      if (!this.enabled) return;
      const prefix = "❌ MoBooking Error:" + this.indent.repeat(this.level);
      if (error) {
        console.error(prefix + " " + message, error);
      } else {
        console.error(prefix + " " + message);
      }
    },

    success: function (message, data = null) {
      if (!this.enabled) return;
      const prefix = "✅ MoBooking Success:" + this.indent.repeat(this.level);
      if (data) {
        console.log(prefix + " " + message, data);
      } else {
        console.log(prefix + " " + message);
      }
    },
  };

  DEBUG.group("Auth Forms Initialization");
  DEBUG.log("jQuery ready, starting auth forms setup");

  // --- Helper Functions ---
  function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  function showFieldError($field, message, errorContainerSelector = null) {
    DEBUG.log("Showing field error", {
      field: $field.attr("id"),
      message: message,
    });
    $field.addClass("error");
    $field.siblings(".field-error").remove();
    const errorHtml = `<div class="field-error" style="color: #d63638; font-size: 0.875em; margin-top: 0.25rem;">${message}</div>`;
    if (errorContainerSelector) {
      $(errorContainerSelector).html(errorHtml).show();
    } else {
      $field.after(errorHtml);
    }
    $field.one("input change", function () {
      $(this).removeClass("error");
      $(this).siblings(".field-error").remove();
      if (errorContainerSelector) {
        $(errorContainerSelector).hide().empty();
      }
    });
  }

  function displayGlobalMessage($messageDiv, message, isSuccess) {
    DEBUG.log("Displaying global message", {
      message: message,
      isSuccess: isSuccess,
    });
    const messageClass = isSuccess ? "success" : "error";
    const style = isSuccess
      ? "color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb;"
      : "color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb;";
    $messageDiv
      .removeClass("success error")
      .addClass(messageClass)
      .html(
        `<div style="padding: 0.75rem 1.25rem; margin-bottom: 1rem; border-radius: 0.25rem; ${style}">${message}</div>`
      )
      .show();
  }

  // --- Registration Form Logic ---
  const $registerForm = $("#mobooking-register-form");
  const $registerMessageDiv = $("#mobooking-register-message");

  DEBUG.log("Registration form found", { exists: $registerForm.length > 0 });

  if ($registerForm.length) {
    DEBUG.group("Registration Form Setup");

    let registrationData = {};
    let currentStep = 1;
    const totalSteps =
      $("#mobooking-progress-bar .mobooking-progress-step").length || 3;

    DEBUG.log("Registration form initialized", { totalSteps: totalSteps });

    function updateProgressBar() {
      DEBUG.log("Updating progress bar", {
        currentStep: currentStep,
        totalSteps: totalSteps,
      });
      $("#mobooking-progress-bar .mobooking-progress-step").removeClass(
        "active completed"
      );
      for (let i = 1; i <= totalSteps; i++) {
        if (i < currentStep) {
          $(
            "#mobooking-progress-bar .mobooking-progress-step[data-step='" +
              i +
              "']"
          ).addClass("completed");
        } else if (i === currentStep) {
          $(
            "#mobooking-progress-bar .mobooking-progress-step[data-step='" +
              i +
              "']"
          ).addClass("active");
        }
      }
    }

    function showStep(step) {
      DEBUG.log("Showing step", { step: step });
      $(".mobooking-register-step").removeClass("active").hide();
      $("#mobooking-register-step-" + step)
        .show()
        .addClass("active");
      currentStep = step;
      updateProgressBar();
    }

    showStep(1);

    // Step navigation
    $("#mobooking-step-1-next").on("click", async function () {
      DEBUG.group("Step 1 Navigation");
      if (await validateRegisterStep(1)) {
        DEBUG.success("Step 1 validation passed, moving to step 2");
        showStep(2);
      } else {
        DEBUG.error("Step 1 validation failed");
      }
      DEBUG.groupEnd();
    });

    $("#mobooking-step-2-prev").on("click", function () {
      DEBUG.log("Going back to step 1");
      showStep(1);
    });

    $("#mobooking-step-2-next").on("click", async function () {
      DEBUG.group("Step 2 Navigation");
      if (await validateRegisterStep(2)) {
        DEBUG.success(
          "Step 2 validation passed, updating confirmation and moving to step 3"
        );
        updateConfirmationStep();
        showStep(3);
      } else {
        DEBUG.error("Step 2 validation failed");
      }
      DEBUG.groupEnd();
    });

    $("#mobooking-step-3-prev").on("click", function () {
      DEBUG.log("Going back to step 2");
      showStep(2);
    });

    async function checkEmailExists(email) {
      DEBUG.group("Email Existence Check");
      DEBUG.log("Checking email", { email: email });

      return new Promise((resolve, reject) => {
        $.ajax({
          type: "POST",
          url: mobooking_auth_params.ajax_url,
          data: {
            action: "mobooking_check_email_exists",
            email: email,
          },
          dataType: "json",
          timeout: 10000,
          success: (response) => {
            DEBUG.log("Email check response", response);
            const exists = response.data && response.data.exists;
            DEBUG.success("Email check completed", { exists: exists });
            DEBUG.groupEnd();
            resolve(exists);
          },
          error: (jqXHR, textStatus, errorThrown) => {
            DEBUG.error("Email check failed", {
              status: jqXHR.status,
              textStatus: textStatus,
              errorThrown: errorThrown,
            });
            DEBUG.groupEnd();
            reject(new Error("Failed to check email"));
          },
        });
      });
    }

    async function checkCompanySlugExists(companyName) {
      DEBUG.group("Company Slug Existence Check");
      DEBUG.log("Checking company name", { companyName: companyName });

      return new Promise((resolve) => {
        $.ajax({
          type: "POST",
          url: mobooking_auth_params.ajax_url,
          data: {
            action: "mobooking_check_company_slug_exists",
            company_name: companyName,
          },
          dataType: "json",
          timeout: 10000,
          success: (response) => {
            DEBUG.log("Company slug check response", response);
            const exists = response.data && response.data.exists;
            DEBUG.success("Company slug check completed", { exists: exists });
            DEBUG.groupEnd();
            resolve({ exists: exists, message: response.data?.message || '' });
          },
          error: (jqXHR, textStatus, errorThrown) => {
            DEBUG.error("Company slug check failed", {
              status: jqXHR.status,
              textStatus: textStatus,
              errorThrown: errorThrown,
            });
            DEBUG.groupEnd();
            resolve({ exists: false, message: 'Could not verify company name.' });
          },
        });
      });
    }

    async function validateRegisterStep(step) {
      DEBUG.group(`Step ${step} Validation`);

      $(".field-error").remove();
      let isValid = true;

      // Gather form data
      registrationData.first_name = $("#mobooking-first-name").val().trim();
      registrationData.last_name = $("#mobooking-last-name").val().trim();
      registrationData.email = $("#mobooking-user-email").val().trim();
      registrationData.password = $("#mobooking-user-pass").val();
      registrationData.password_confirm = $(
        "#mobooking-user-pass-confirm"
      ).val();
      registrationData.company_name = $("#mobooking-company-name").val()
        ? $("#mobooking-company-name").val().trim()
        : "";

      DEBUG.log("Gathered form data", registrationData);

      if (step === 1) {
        DEBUG.group("Step 1 Field Validation");

        if (!registrationData.first_name) {
          showFieldError($("#mobooking-first-name"), "First name is required.");
          isValid = false;
          DEBUG.error("First name validation failed");
        }

        if (!registrationData.last_name) {
          showFieldError($("#mobooking-last-name"), "Last name is required.");
          isValid = false;
          DEBUG.error("Last name validation failed");
        }

        if (!registrationData.email || !isValidEmail(registrationData.email)) {
          showFieldError(
            $("#mobooking-user-email"),
            "Valid email is required."
          );
          isValid = false;
          DEBUG.error("Email validation failed");
        } else {
          try {
            DEBUG.log("Starting email existence check");
            if (await checkEmailExists(registrationData.email)) {
              showFieldError(
                $("#mobooking-user-email"),
                "This email is already registered."
              );
              isValid = false;
              DEBUG.error("Email already exists");
            } else {
              DEBUG.success("Email is available");
            }
          } catch (error) {
            DEBUG.error("Email check failed, continuing...", error);
            // Continue without email check if it fails
          }
        }

        if (
          !registrationData.password ||
          registrationData.password.length < 8
        ) {
          showFieldError(
            $("#mobooking-user-pass"),
            "Password must be at least 8 characters."
          );
          isValid = false;
          DEBUG.error("Password validation failed");
        }

        if (registrationData.password !== registrationData.password_confirm) {
          showFieldError(
            $("#mobooking-user-pass-confirm"),
            "Passwords do not match."
          );
          isValid = false;
          DEBUG.error("Password confirmation failed");
        }

        DEBUG.groupEnd();
      }

      if (step === 2) {
        DEBUG.group("Step 2 Field Validation");

        const inviterId = $("#mobooking-inviter-id").val();
        if (!inviterId && !registrationData.company_name) {
          showFieldError(
            $("#mobooking-company-name"),
            "Company name is required."
          );
          isValid = false;
          DEBUG.error("Company name validation failed");
        } else if (!inviterId && registrationData.company_name) {
            // Only check if it's not an invitation and company name is provided
            try {
                DEBUG.log("Starting company slug existence check");
                const slugCheck = await checkCompanySlugExists(registrationData.company_name);
                if (slugCheck.exists) {
                    // It's just a warning, not a validation failure, so we don't set isValid = false
                    showFieldError($("#mobooking-company-name"), slugCheck.message);
                    DEBUG.log("Company name slug might be taken", { message: slugCheck.message });
                } else {
                    DEBUG.success("Company name appears to be available");
                }
            } catch (error) {
                DEBUG.error("Company slug check failed, continuing...", error);
            }
        } else {
          DEBUG.success("Company name validation passed (invitation flow or empty)");
        }

        DEBUG.groupEnd();
      }

      DEBUG.success(`Step ${step} validation result`, { isValid: isValid });
      DEBUG.groupEnd();
      return isValid;
    }

    function updateConfirmationStep() {
      DEBUG.log("Updating confirmation step with data", registrationData);
      $("#confirm-first-name").text(registrationData.first_name);
      $("#confirm-last-name").text(registrationData.last_name);
      $("#confirm-email").text(registrationData.email);
      $("#confirm-company-name").text(registrationData.company_name || "N/A");
    }

    // Enhanced form submission with comprehensive debugging
    $registerForm.on("submit", async function (e) {
      e.preventDefault();

      DEBUG.group("🚀 REGISTRATION FORM SUBMISSION");
      DEBUG.log("Form submission started");

      // Final validation
      DEBUG.group("Final Validation");
      if (!(await validateRegisterStep(1))) {
        showStep(1);
        displayGlobalMessage(
          $registerMessageDiv,
          "Please correct the errors in Step 1.",
          false
        );
        DEBUG.error("Final step 1 validation failed");
        DEBUG.groupEnd();
        DEBUG.groupEnd();
        return;
      }
      if (!(await validateRegisterStep(2))) {
        showStep(2);
        displayGlobalMessage(
          $registerMessageDiv,
          "Please correct the errors in Step 2.",
          false
        );
        DEBUG.error("Final step 2 validation failed");
        DEBUG.groupEnd();
        DEBUG.groupEnd();
        return;
      }
      DEBUG.success("Final validation passed");
      DEBUG.groupEnd();

      $registerMessageDiv.hide().empty();
      const $submitButton = $registerForm.find('input[type="submit"]');
      const originalButtonText = $submitButton.val();

      // Prepare form data
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

      // Handle invitation flow
      const inviterId = $("#mobooking-inviter-id").val();
      const assignedRole = $("#mobooking-assigned-role").val();
      const invitationToken = $("#mobooking-invitation-token").val();

      if (inviterId && assignedRole && invitationToken) {
        formData.inviter_id = inviterId;
        formData.role_to_assign = assignedRole;
        formData.invitation_token = invitationToken;
        formData.company_name = "";
        DEBUG.log("Invitation flow detected", {
          inviterId,
          assignedRole,
          invitationToken,
        });
      }

      DEBUG.log("Prepared form data", formData);

      // AJAX Registration Request
      DEBUG.group("🌐 AJAX Registration Request");

      $.ajax({
        type: "POST",
        url: mobooking_auth_params.ajax_url,
        data: formData,
        dataType: "json",
        timeout: 60000, // 60 second timeout
        beforeSend: () => {
          $submitButton.prop("disabled", true).val("Creating Account...");
          DEBUG.log("AJAX request initiated", {
            url: mobooking_auth_params.ajax_url,
            action: formData.action,
            email: formData.email,
          });
        },
        success: (response) => {
            DEBUG.group("✅ AJAX Success Response");
            DEBUG.log("Raw response received", response);

            if (response && response.success) {
                DEBUG.success("Registration successful!", response.data);

                $registerForm.hide();
                $("#mobooking-progress-bar").hide();

                const $formContainer = $registerForm.closest(".mobooking-auth-form-wrapper");
                const successMessage = response.data?.message || "Your account has been created.";
                const displayName = response.data?.user_data?.name || "there";

                const messageHtml = `
        <div style="text-align: center; padding: 30px; background: #f0f9ff; border: 2px solid #0ea5e9; border-radius: 8px; margin: 20px 0;">
        <div style="color: #0ea5e9; font-size: 48px; margin-bottom: 20px;">✓</div>
        <h3 style="color: #0369a1; margin: 0 0 15px 0; font-size: 24px;">Welcome, ${displayName}!</h3>
        <p style="color: #0369a1; margin: 0 0 20px 0; font-size: 16px;">
          ${successMessage}
        </p>
        <p style="color: #0369a1; margin: 0 0 20px 0; font-size: 16px;">
          Redirecting to your dashboard...
        </p>
        </div>`;

                if ($formContainer.length) {
                    $formContainer.html(messageHtml).show();
                } else {
                    $registerMessageDiv.html(messageHtml).addClass("success").show();
                }

                const redirectUrl = response.data?.redirect_url || "/dashboard/";
                DEBUG.log("Redirecting to", redirectUrl);
                setTimeout(() => {
                    window.location.href = redirectUrl;
                }, 3000); // 3 second delay
            } else {
                DEBUG.error("Registration failed", response);
                const errorMessage =
                    response?.data?.message ||
                    "Registration failed. Please try again.";
                displayGlobalMessage($registerMessageDiv, errorMessage, false);
                $submitButton.prop("disabled", false).val(originalButtonText);
            }
            DEBUG.groupEnd();
        },
        error: (jqXHR, textStatus, errorThrown) => {
          DEBUG.group("❌ AJAX Error Response");
          DEBUG.error("AJAX request failed", {
            status: jqXHR.status,
            statusText: jqXHR.statusText,
            textStatus: textStatus,
            errorThrown: errorThrown,
            responseText: jqXHR.responseText ? jqXHR.responseText.substring(0, 500) : "[No responseText]",
            readyState: jqXHR.readyState,
          });

          let errorMsg = "An unexpected error occurred. Please try again.";

          if (textStatus === "timeout") {
            errorMsg = "Registration timed out. Please check your connection and try again.";
            DEBUG.error("Request timed out");
          } else if (jqXHR.status === 0) {
            // This often means the request was blocked (CORS), aborted, or there's no network.
            // jqXHR.responseText will likely be undefined or empty.
            errorMsg = "Connection or network error. Please check your internet connection and try again. If the issue persists, contact support.";
            DEBUG.error("Connection/Network error (status 0 or request aborted)");
          } else if (jqXHR.status >= 500) {
            errorMsg = "Server error occurred. Please try again or contact support. Check server logs for details.";
            DEBUG.error("Server error", { status: jqXHR.status });
          } else if (jqXHR.status === 403) {
            errorMsg = "Access denied (403). Please refresh the page and try again.";
            DEBUG.error("Access denied (403)");
          } else if (jqXHR.status === 404) {
            errorMsg = "Registration endpoint not found (404). Please contact support.";
            DEBUG.error("Endpoint not found (404)");
          } else if (jqXHR.responseJSON?.data?.message) { // If server sent a JSON error
            errorMsg = jqXHR.responseJSON.data.message;
            DEBUG.error("Server returned JSON error message", errorMsg);
          } else if (jqXHR.responseText) { // If there's some other text response
             // Avoid showing long HTML error pages directly to the user if possible
            errorMsg = "Received an unexpected response from the server. Please try again.";
            DEBUG.error("Non-JSON error response text (first 200 chars)", jqXHR.responseText.substring(0,200));
          } else {
            // Fallback for other errors where responseText might be empty or undefined but status is not 0
            errorMsg = `An error occurred (Status: ${jqXHR.status || 'N/A'}, Type: ${textStatus || 'N/A'}). Please try again.`;
             DEBUG.error("Unhandled AJAX error type");
          }

          displayGlobalMessage($registerMessageDiv, errorMsg, false);
          $submitButton.prop("disabled", false).val(originalButtonText);

          DEBUG.groupEnd();
        },
        complete: () => {
          DEBUG.log("AJAX request completed");
          DEBUG.groupEnd(); // Close AJAX group
        },
      });

      DEBUG.groupEnd(); // Close form submission group
    });

    // Enhanced success message function with guaranteed redirect
    function showSuccessMessageWithCountdown(userName, redirectUrl) {
      DEBUG.group("🎉 Success Message Display");
      DEBUG.log("Showing success message", { userName, redirectUrl });

      let countdown = 3;
      const $formContainer = $registerForm.closest(
        ".mobooking-auth-form-wrapper"
      );

      function updateMessage() {
        const message = `
          <div style="text-align: center; padding: 30px; background: #f0f9ff; border: 2px solid #0ea5e9; border-radius: 8px; margin: 20px 0;">
            <div style="color: #0ea5e9; font-size: 48px; margin-bottom: 20px;">✓</div>
            <h3 style="color: #0369a1; margin: 0 0 15px 0; font-size: 24px;">Welcome, ${userName}!</h3>
            <p style="color: #0369a1; margin: 0 0 20px 0; font-size: 16px;">
              Your account is ready. Redirecting in ${countdown} second${
          countdown !== 1 ? "s" : ""
        }...
            </p>
            <p style="margin-top: 15px; font-size: 14px; color: #64748b;">
              <a href="${redirectUrl}" style="color: #0ea5e9; text-decoration: none;">Go to Dashboard Now</a>
            </p>
          </div>`;

        if ($formContainer.length) {
          $formContainer.html(message).show();
          DEBUG.log("Message displayed in form container");
        } else {
          $registerMessageDiv.html(message).addClass("success").show();
          DEBUG.log("Message displayed in message div (fallback)");
        }
      }

      updateMessage();

      // Countdown timer
      const interval = setInterval(() => {
        countdown--;
        DEBUG.log("Countdown tick", { countdown });

        if (countdown >= 0) {
          updateMessage();
        } else {
          DEBUG.log("Countdown finished, initiating redirect");
          clearInterval(interval);
          performRedirect(redirectUrl);
        }
      }, 1000);

      // Multiple fallback redirects to ensure it works
      setTimeout(() => {
        DEBUG.log("5-second fallback redirect triggered");
        performRedirect(redirectUrl);
      }, 5000);

      setTimeout(() => {
        DEBUG.log("10-second emergency redirect triggered");
        performRedirect(redirectUrl);
      }, 10000);

      // Function to perform redirect with multiple methods
      function performRedirect(url) {
        DEBUG.group("🔄 Redirect Attempt");
        DEBUG.log("Starting redirect to", url);

        try {
          // Method 1: Standard redirect
          DEBUG.log("Attempting window.location.href redirect");
          window.location.href = url;

          // Method 2: Backup redirect (executes if above fails)
          setTimeout(() => {
            DEBUG.log("Attempting window.location.replace redirect (backup)");
            window.location.replace(url);
          }, 1000);

          // Method 3: Emergency redirect
          setTimeout(() => {
            DEBUG.log("Attempting emergency redirect");
            try {
              window.location.assign(url);
            } catch (e) {
              DEBUG.error("All redirect methods failed", e);
              // Last resort: show manual link
              $registerMessageDiv
                .html(
                  `
                <div style="text-align: center; padding: 20px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px;">
                  <h4>Registration Successful!</h4>
                  <p>Automatic redirect failed. Please click the link below:</p>
                  <a href="${url}" style="display: inline-block; padding: 10px 20px; background: #007cba; color: white; text-decoration: none; border-radius: 3px;">Go to Dashboard</a>
                </div>
              `
                )
                .show();
            }
          }, 2000);
        } catch (error) {
          DEBUG.error("Redirect failed", error);
        }

        DEBUG.groupEnd();
      }

      DEBUG.groupEnd();
    }

    DEBUG.groupEnd(); // Close Registration Form Setup
  }

  // --- Login Form Logic ---
  const $loginForm = $("#mobooking-login-form");
  const $loginMessageDiv = $("#mobooking-login-message");

  DEBUG.log("Login form found", { exists: $loginForm.length > 0 });

  if ($loginForm.length) {
    DEBUG.group("Login Form Setup");

    $loginForm.on("submit", function (e) {
      e.preventDefault();

      DEBUG.group("Login Form Submission");
      DEBUG.log("Login form submitted");

      $loginMessageDiv.hide().empty().removeClass("error success");
      $(".field-error").remove();

      const email = $("#mobooking-user-login").val().trim();
      const password = $("#mobooking-user-pass").val();
      const rememberMe = $("#mobooking-rememberme").is(":checked");
      const $submitButton = $loginForm.find('input[type="submit"]');
      const originalButtonText = $submitButton.val();

      DEBUG.log("Login data gathered", { email, rememberMe });

      let valid = true;
      if (!email || !isValidEmail(email)) {
        showFieldError(
          $("#mobooking-user-login"),
          "Please enter a valid email address."
        );
        valid = false;
        DEBUG.error("Email validation failed");
      }
      if (!password) {
        showFieldError($("#mobooking-user-pass"), "Password is required.");
        valid = false;
        DEBUG.error("Password validation failed");
      }

      if (!valid) {
        DEBUG.error("Login validation failed");
        DEBUG.groupEnd();
        return;
      }

      DEBUG.log("Starting login AJAX request");

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
        beforeSend: () => {
          $submitButton.prop("disabled", true).val("Logging In...");
          DEBUG.log("Login request sent");
        },
        success: (response) => {
          DEBUG.log("Login response received", response);
          if (response.success) {
            DEBUG.success("Login successful");
            displayGlobalMessage(
              $loginMessageDiv,
              response.data.message || "Login successful! Redirecting...",
              true
            );
            const redirectUrl = response.data.redirect_url || "/dashboard/";
            DEBUG.log("Redirecting to", redirectUrl);
            setTimeout(() => {
              window.location.href = redirectUrl;
            }, 1000);
          } else {
            DEBUG.error("Login failed", response);
            displayGlobalMessage(
              $loginMessageDiv,
              response.data.message ||
                "Login failed. Please check your credentials.",
              false
            );
          }
        },
        error: (jqXHR) => {
          DEBUG.error("Login AJAX error", {
            status: jqXHR.status,
            responseText: jqXHR.responseText,
          });
          let errorMsg = "An unexpected error occurred. Please try again.";
          if (jqXHR.responseJSON?.data?.message) {
            errorMsg = jqXHR.responseJSON.data.message;
          }
          displayGlobalMessage($loginMessageDiv, errorMsg, false);
        },
        complete: () => {
          $submitButton.prop("disabled", false).val(originalButtonText);
          DEBUG.log("Login request completed");
          DEBUG.groupEnd();
        },
      });
    });

    DEBUG.groupEnd();
  }

  // --- Forgot Password Form Logic ---
  const $forgotPasswordForm = $("#mobooking-forgot-password-form");
  const $forgotPasswordMessageDiv = $("#mobooking-forgot-password-message");

  DEBUG.log("Forgot password form found", {
    exists: $forgotPasswordForm.length > 0,
  });

  if ($forgotPasswordForm.length) {
    DEBUG.group("Forgot Password Form Setup");

    $forgotPasswordForm.on("submit", function (e) {
      e.preventDefault();

      DEBUG.group("Forgot Password Form Submission");
      DEBUG.log("Forgot password form submitted");

      $forgotPasswordMessageDiv.hide().empty().removeClass("error success");
      $(".field-error").remove();

      const email = $("#mobooking-user-email-forgot").val().trim();
      const $submitButton = $forgotPasswordForm.find('input[type="submit"]');
      const originalButtonText = $submitButton.val();

      DEBUG.log("Email for password reset", { email });

      if (!email || !isValidEmail(email)) {
        showFieldError(
          $("#mobooking-user-email-forgot"),
          "Please enter a valid email address."
        );
        DEBUG.error("Email validation failed");
        DEBUG.groupEnd();
        return;
      }

      DEBUG.log("Starting password reset request");

      $.ajax({
        type: "POST",
        url: mobooking_auth_params.ajax_url,
        data: {
          action: "mobooking_send_password_reset_link",
          nonce: mobooking_auth_params.forgot_password_nonce,
          user_email: email,
        },
        dataType: "json",
        beforeSend: () => {
          $submitButton.prop("disabled", true).val("Sending Link...");
          DEBUG.log("Password reset request sent");
        },
        success: (response) => {
          DEBUG.log("Password reset response", response);
          displayGlobalMessage(
            $forgotPasswordMessageDiv,
            response.data.message ||
              "If an account with that email exists, a password reset link has been sent.",
            true
          );
        },
        error: (jqXHR) => {
          DEBUG.error("Password reset error", {
            status: jqXHR.status,
            responseText: jqXHR.responseText,
          });
          let errorMsg = "An unexpected error occurred. Please try again.";
          if (jqXHR.responseJSON?.data?.message) {
            errorMsg = jqXHR.responseJSON.data.message;
          }
          displayGlobalMessage($forgotPasswordMessageDiv, errorMsg, false);
        },
        complete: () => {
          $submitButton.prop("disabled", false).val(originalButtonText);
          DEBUG.log("Password reset request completed");
          DEBUG.groupEnd();
        },
      });
    });

    DEBUG.groupEnd();
  }

  DEBUG.success("Auth forms initialization completed");
  DEBUG.groupEnd();

  // Add global error handler
  window.addEventListener("error", function (event) {
    if (
      event.error &&
      event.error.stack &&
      event.error.stack.includes("mobooking")
    ) {
      DEBUG.error("Global JavaScript error in MoBooking", {
        message: event.error.message,
        filename: event.filename,
        lineno: event.lineno,
        stack: event.error.stack,
      });
    }
  });
});
