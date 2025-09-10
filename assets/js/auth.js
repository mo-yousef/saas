// NORDBOOKING Auth Forms: Registration, Login, Forgot Password
jQuery(document).ready(function ($) {
  "use strict";

  // Debug Tree System
  const DEBUG = {
    enabled: true,
    level: 0,
    indent: "  ",
    log: function (message, data = null) {
      if (!this.enabled) return;
      const prefix = "🔍 NORDBOOKING Debug:" + this.indent.repeat(this.level);
      if (data) {
        console.log(prefix + " " + message, data);
      } else {
        console.log(prefix + " " + message);
      }
    },
    group: function (title) {
      if (!this.enabled) return;
      console.group("🔍 NORDBOOKING Debug: " + title);
      this.level++;
    },
    groupEnd: function () {
      if (!this.enabled) return;
      this.level = Math.max(0, this.level - 1);
      console.groupEnd();
    },
    error: function (message, error = null) {
      if (!this.enabled) return;
      const prefix = "❌ NORDBOOKING Error:" + this.indent.repeat(this.level);
      if (error) {
        console.error(prefix + " " + message, error);
      } else {
        console.error(prefix + " " + message);
      }
    },
    success: function (message, data = null) {
      if (!this.enabled) return;
      const prefix = "✅ NORDBOOKING Success:" + this.indent.repeat(this.level);
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

  function showFieldError($field, message) {
    DEBUG.log("Showing field error", {
      field: $field.attr("id"),
      message: message,
    });
    $field.addClass("error");
    $field.siblings(".field-error").remove();
    const errorHtml = `<div class="field-error" style="color: #d63638; font-size: 0.875em; margin-top: 0.25rem;">${message}</div>`;
    $field.after(errorHtml);
  }

  function clearFieldError($field) {
    $field.removeClass("error");
    $field.siblings(".field-error").remove();
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

  // --- Registration Form Logic (Single Step) ---
  const $registerForm = $("#NORDBOOKING-register-form");
  if ($registerForm.length) {
    DEBUG.group("Registration Form Setup");
    const $registerMessageDiv = $("#NORDBOOKING-register-message");

    // Add hidden input for reCAPTCHA token
    $registerForm.append(
      '<input type="hidden" name="recaptcha_token" id="NORDBOOKING-recaptcha-token">'
    );

    async function checkEmailExists(email) {
      return $.ajax({
        type: "POST",
        url: nordbooking_auth_params.ajax_url,
        data: { action: "nordbooking_check_email_exists", email: email },
        dataType: "json",
      });
    }

    async function checkCompanySlugExists(companyName) {
      return $.ajax({
        type: "POST",
        url: nordbooking_auth_params.ajax_url,
        data: {
          action: "nordbooking_check_company_slug_exists",
          company_name: companyName,
        },
        dataType: "json",
      });
    }

    // On-blur validation for email
    $("#NORDBOOKING-user-email").on("blur", async function () {
      const $field = $(this);
      const email = $field.val().trim();
      clearFieldError($field);
      if (!isValidEmail(email)) {
        showFieldError($field, "Please enter a valid email address.");
        return;
      }
      try {
        const response = await checkEmailExists(email);
        if (response.data && response.data.exists) {
          showFieldError($field, "This email is already registered.");
        }
      } catch (error) {
        DEBUG.error("Email existence check failed on blur", error);
      }
    });

    // On-blur validation for company name
    $("#NORDBOOKING-company-name").on("blur", async function () {
      const $field = $(this);
      const companyName = $field.val().trim();
      clearFieldError($field);
      if (companyName) {
        try {
          const response = await checkCompanySlugExists(companyName);
          if (response.data && response.data.exists) {
            showFieldError($field, response.data.message);
          }
        } catch (error) {
          DEBUG.error("Company slug check failed on blur", error);
        }
      }
    });

    async function validateRegistrationForm() {
      DEBUG.group("Form Validation");
      let isValid = true;
      $registerForm.find(".field-error").remove();
      $registerForm.find(".error").removeClass("error");

      // --- Field by field validation ---
      if ($("#NORDBOOKING-first-name").val().trim() === "") {
        showFieldError($("#NORDBOOKING-first-name"), "First name is required.");
        isValid = false;
      }
      if ($("#NORDBOOKING-last-name").val().trim() === "") {
        showFieldError($("#NORDBOOKING-last-name"), "Last name is required.");
        isValid = false;
      }
      const email = $("#NORDBOOKING-user-email").val().trim();
      if (!isValidEmail(email)) {
        showFieldError(
          $("#NORDBOOKING-user-email"),
          "Valid email is required."
        );
        isValid = false;
      }
      const password = $("#NORDBOOKING-user-pass").val();
      if (password.length < 8) {
        showFieldError(
          $("#NORDBOOKING-user-pass"),
          "Password must be at least 8 characters."
        );
        isValid = false;
      }
      if (password !== $("#NORDBOOKING-user-pass-confirm").val()) {
        showFieldError(
          $("#NORDBOOKING-user-pass-confirm"),
          "Passwords do not match."
        );
        isValid = false;
      }
      const isInvitation = !!$("#NORDBOOKING-inviter-id").val();
      if (!isInvitation && $("#NORDBOOKING-company-name").val().trim() === "") {
        showFieldError(
          $("#NORDBOOKING-company-name"),
          "Company name is required."
        );
        isValid = false;
      }

      DEBUG.log("Initial validation result", { isValid: isValid });
      DEBUG.groupEnd();
      return isValid;
    }

    $registerForm.on("submit", function (e) {
      e.preventDefault();
      DEBUG.group("🚀 REGISTRATION FORM SUBMISSION");

      const $submitButton = $(this).find('input[type="submit"]');
      const originalButtonText = $submitButton.val();
      $submitButton.prop("disabled", true).val("Validating...");

      validateRegistrationForm().then((formIsValid) => {
        if (!formIsValid) {
          displayGlobalMessage(
            $registerMessageDiv,
            "Please correct the errors above.",
            false
          );
          $submitButton.prop("disabled", false).val(originalButtonText);
          DEBUG.error("Form validation failed.");
          DEBUG.groupEnd();
          return;
        }

        DEBUG.success("Form validation passed.");
        $submitButton.val("Processing...");

        // --- reCAPTCHA Execution ---
        // Replace 'YOUR_SITE_KEY' with the actual key, likely from wp_localize_script
        const recaptchaSiteKey =
          nordbooking_auth_params.recaptcha_site_key || "YOUR_SITE_KEY";
        if (
          typeof grecaptcha === "undefined" ||
          !recaptchaSiteKey ||
          recaptchaSiteKey === "YOUR_SITE_KEY"
        ) {
          DEBUG.log("reCAPTCHA not configured, submitting form without it.");
          submitRegistrationData(); // Submit form directly if reCAPTCHA is not set up
          return;
        }

        DEBUG.log("Executing reCAPTCHA...");
        grecaptcha.ready(function () {
          grecaptcha
            .execute(recaptchaSiteKey, { action: "register" })
            .then(function (token) {
              DEBUG.success("reCAPTCHA token received.");
              $("#NORDBOOKING-recaptcha-token").val(token);
              submitRegistrationData();
            })
            .catch(function (error) {
              DEBUG.error("reCAPTCHA execution failed", error);
              displayGlobalMessage(
                $registerMessageDiv,
                "Could not verify you are human. Please try again.",
                false
              );
              $submitButton.prop("disabled", false).val(originalButtonText);
            });
        });
      });
    });

    function submitRegistrationData() {
      const formData = $registerForm.serializeArray().reduce((obj, item) => {
        obj[item.name] = item.value;
        return obj;
      }, {});

      formData.action = "nordbooking_register";
      formData.nonce = nordbooking_auth_params.register_nonce;

      const $submitButton = $registerForm.find('input[type="submit"]');
      const originalButtonText = $submitButton.val();
      $submitButton.prop("disabled", true).val("Creating Account...");

      DEBUG.group("🌐 AJAX Registration Request");
      $.ajax({
        type: "POST",
        url: nordbooking_auth_params.ajax_url,
        data: formData,
        dataType: "json",
        timeout: 60000,
        success: (response) => {
          DEBUG.log("AJAX Success Response", response);
          if (response && response.success) {
            DEBUG.success("Registration successful!", response.data);
            const redirectUrl = response.data?.redirect_url || "/dashboard/";
            const successMessage =
              response.data?.message || "Your account has been created.";
            const displayName = response.data?.user_data?.name || "there";
            const messageHtml = `<div style="text-align: center; padding: 30px; background: #f0f9ff; border: 2px solid #0ea5e9; border-radius: 8px; margin: 20px 0;"><div style="color: #0ea5e9; font-size: 48px; margin-bottom: 20px;">✓</div><h3 style="color: #0369a1; margin: 0 0 15px 0; font-size: 24px;">Welcome, ${displayName}!</h3><p style="color: #0369a1; margin: 0 0 20px 0; font-size: 16px;">${successMessage}</p><p style="color: #0369a1; margin: 0 0 20px 0; font-size: 16px;">Redirecting to your dashboard...</p></div>`;
            $registerForm
              .closest(".NORDBOOKING-auth-form-wrapper")
              .html(messageHtml)
              .show();
            setTimeout(() => {
              window.location.href = redirectUrl;
            }, 3000);
          } else {
            DEBUG.error("Registration failed on server", response);
            displayGlobalMessage(
              $registerMessageDiv,
              response.data?.message ||
                "Registration failed. Please try again.",
              false
            );
            $submitButton.prop("disabled", false).val(originalButtonText);
          }
        },
        error: (jqXHR, textStatus, errorThrown) => {
          DEBUG.error("AJAX Error Response", {
            status: jqXHR.status,
            textStatus,
            errorThrown,
          });
          displayGlobalMessage(
            $registerMessageDiv,
            "An unexpected server error occurred. Please try again later.",
            false
          );
          $submitButton.prop("disabled", false).val(originalButtonText);
        },
        complete: () => {
          DEBUG.groupEnd(); // End AJAX group
          DEBUG.groupEnd(); // End Form Submission group
        },
      });
    }
    DEBUG.groupEnd(); // End Registration Form Setup
  }

  // --- Login Form Logic ---
  const $loginForm = $("#NORDBOOKING-login-form");
  if ($loginForm.length) {
    $loginForm.on("submit", function (e) {
      e.preventDefault();
      const email = $("#NORDBOOKING-user-login").val().trim();
      const password = $("#NORDBOOKING-user-pass").val();
      const $submitButton = $(this).find('input[type="submit"]');
      const originalButtonText = $submitButton.val();
      let isValid = true;

      $(".field-error").remove();
      if (!isValidEmail(email)) {
        showFieldError(
          $("#NORDBOOKING-user-login"),
          "Please enter a valid email address."
        );
        isValid = false;
      }
      if (!password) {
        showFieldError($("#NORDBOOKING-user-pass"), "Password is required.");
        isValid = false;
      }
      if (!isValid) return;

      $.ajax({
        type: "POST",
        url: nordbooking_auth_params.ajax_url,
        data: {
          action: "nordbooking_login",
          nonce: nordbooking_auth_params.login_nonce,
          log: email,
          pwd: password,
          rememberme: $("#NORDBOOKING-rememberme").is(":checked")
            ? "forever"
            : "",
        },
        dataType: "json",
        beforeSend: () =>
          $submitButton.prop("disabled", true).val("Logging In..."),
        success: (response) => {
          const $messageDiv = $("#NORDBOOKING-login-message");
          if (response.success) {
            displayGlobalMessage(
              $messageDiv,
              response.data.message || "Login successful! Redirecting...",
              true
            );
            setTimeout(() => {
              window.location.href =
                response.data.redirect_url || "/dashboard/";
            }, 1000);
          } else {
            displayGlobalMessage(
              $messageDiv,
              response.data.message ||
                "Login failed. Please check your credentials.",
              false
            );
          }
        },
        error: () =>
          displayGlobalMessage(
            $("#NORDBOOKING-login-message"),
            "An unexpected error occurred.",
            false
          ),
        complete: () =>
          $submitButton.prop("disabled", false).val(originalButtonText),
      });
    });
  }

  // --- Forgot Password Form Logic ---
  const $forgotPasswordForm = $("#NORDBOOKING-forgot-password-form");
  if ($forgotPasswordForm.length) {
    $forgotPasswordForm.on("submit", function (e) {
      e.preventDefault();
      const email = $("#NORDBOOKING-user-email-forgot").val().trim();
      const $submitButton = $(this).find('input[type="submit"]');
      const originalButtonText = $submitButton.val();

      $(".field-error").remove();
      if (!isValidEmail(email)) {
        showFieldError(
          $("#NORDBOOKING-user-email-forgot"),
          "Please enter a valid email address."
        );
        return;
      }

      $.ajax({
        type: "POST",
        url: nordbooking_auth_params.ajax_url,
        data: {
          action: "nordbooking_send_password_reset_link",
          nonce: nordbooking_auth_params.forgot_password_nonce,
          user_email: email,
        },
        dataType: "json",
        beforeSend: () =>
          $submitButton.prop("disabled", true).val("Sending Link..."),
        success: (response) =>
          displayGlobalMessage(
            $("#NORDBOOKING-forgot-password-message"),
            response.data.message ||
              "If an account with that email exists, a password reset link has been sent.",
            true
          ),
        error: () =>
          displayGlobalMessage(
            $("#NORDBOOKING-forgot-password-message"),
            "An unexpected error occurred.",
            false
          ),
        complete: () =>
          $submitButton.prop("disabled", false).val(originalButtonText),
      });
    });
  }

  DEBUG.success("Auth forms initialization completed");
  DEBUG.groupEnd();
});
