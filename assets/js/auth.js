// Enhanced Registration JavaScript for MoBooking
// This replaces the registration portion of assets/js/auth.js

jQuery(document).ready(function ($) {
  "use strict";

  // Multi-step Registration Form Logic
  const $registerForm = $("#mobooking-register-form");
  const $registerMessageDiv = $("#mobooking-register-message");

  if ($registerForm.length) {
    let registrationData = {};
    let currentStep = 1;
    const totalSteps = 3;

    // Utility function to show specific step
    function showStep(step) {
      $(".mobooking-register-step").hide();
      $("#mobooking-register-step-" + step).show();
      currentStep = step;
      updateProgressBar();
    }

    // Update progress bar based on current step
    function updateProgressBar() {
      const progress = (currentStep / totalSteps) * 100;
      $("#mobooking-progress-bar .progress-fill").css("width", progress + "%");
      $("#mobooking-step-indicator").text(
        `Step ${currentStep} of ${totalSteps}`
      );
    }

    // Initialize first step
    showStep(1);

    // Step navigation handlers
    $("#mobooking-step-1-next").on("click", async function () {
      const isValid = await validateStep(1);
      if (isValid) {
        showStep(2);
      }
    });

    $("#mobooking-step-2-next").on("click", async function () {
      const isValid = await validateStep(2);
      if (isValid) {
        updateConfirmationStep();
        showStep(3);
      }
    });

    $("#mobooking-step-2-prev").on("click", function () {
      showStep(1);
    });

    $("#mobooking-step-3-prev").on("click", function () {
      showStep(2);
    });

    // Enhanced step validation with improved error handling
    async function validateStep(step) {
      $(".field-error").remove(); // Clear previous errors
      let isValid = true;

      if (step === 1) {
        // Personal Information Validation
        const firstName = $("#mobooking-first-name").val().trim();
        const lastName = $("#mobooking-last-name").val().trim();
        const email = $("#mobooking-user-email").val().trim();
        const password = $("#mobooking-user-pass").val();
        const passwordConfirm = $("#mobooking-user-pass-confirm").val();

        // Basic validation
        if (!firstName) {
          showFieldError("#mobooking-first-name", "First name is required.");
          isValid = false;
        }
        if (!lastName) {
          showFieldError("#mobooking-last-name", "Last name is required.");
          isValid = false;
        }
        if (!email || !isValidEmail(email)) {
          showFieldError(
            "#mobooking-user-email",
            "Please enter a valid email address."
          );
          isValid = false;
        }
        if (!password || password.length < 8) {
          showFieldError(
            "#mobooking-user-pass",
            "Password must be at least 8 characters long."
          );
          isValid = false;
        }
        if (password !== passwordConfirm) {
          showFieldError(
            "#mobooking-user-pass-confirm",
            "Passwords do not match."
          );
          isValid = false;
        }

        // AJAX validation for email existence (only if email is valid)
        if (isValid && email) {
          try {
            const emailExists = await checkEmailExists(email);
            if (emailExists) {
              showFieldError(
                "#mobooking-user-email",
                "This email is already registered. Please use a different email or try logging in."
              );
              isValid = false;
            }
          } catch (error) {
            console.error("Email validation error:", error);
            // Don't block registration on validation check failure
          }
        }

        // Store data if valid
        if (isValid) {
          registrationData.first_name = firstName;
          registrationData.last_name = lastName;
          registrationData.email = email;
          registrationData.password = password;
          registrationData.password_confirm = passwordConfirm;
        }
      }

      if (step === 2) {
        // Business Information Validation
        const isInvitation =
          $("#mobooking-inviter-id").length && $("#mobooking-inviter-id").val();

        if (!isInvitation) {
          const companyName = $("#mobooking-company-name").val().trim();
          if (!companyName) {
            showFieldError(
              "#mobooking-company-name",
              "Company name is required."
            );
            isValid = false;
          } else {
            registrationData.company_name = companyName;
          }
        } else {
          registrationData.company_name = ""; // Empty for invited workers
        }
      }

      return isValid;
    }

    // Enhanced field error display
    function showFieldError(fieldSelector, message) {
      const $field = $(fieldSelector);
      $field.addClass("error");

      // Remove existing error message for this field
      $field.siblings(".field-error").remove();

      // Add new error message
      $field.after(
        `<div class="field-error" style="color: #d63638; font-size: 14px; margin-top: 5px;">${message}</div>`
      );

      // Clear error on input
      $field.one("input", function () {
        $(this).removeClass("error");
        $(this).siblings(".field-error").remove();
      });
    }

    // Email validation helper
    function isValidEmail(email) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return emailRegex.test(email);
    }

    // AJAX helper to check if email exists
    function checkEmailExists(email) {
      return new Promise((resolve, reject) => {
        $.ajax({
          type: "POST",
          url: mobooking_auth_params.ajax_url,
          data: {
            action: "mobooking_check_email_exists",
            email: email,
          },
          dataType: "json",
          success: function (response) {
            resolve(response.data && response.data.exists);
          },
          error: function () {
            reject(new Error("Failed to check email"));
          },
        });
      });
    }

    // Update confirmation step with user data
    function updateConfirmationStep() {
      $("#confirm-first-name").text(registrationData.first_name || "");
      $("#confirm-last-name").text(registrationData.last_name || "");
      $("#confirm-email").text(registrationData.email || "");
      if (registrationData.company_name) {
        $("#confirm-company-name").text(registrationData.company_name);
      }
    }

    // Enhanced keyboard navigation
    $registerForm.on("keydown", function (e) {
      if (e.key === "Enter") {
        if (
          currentStep === 1 &&
          $("#mobooking-step-1-next").is(":visible") &&
          !$("#mobooking-step-1-next").is(":disabled")
        ) {
          e.preventDefault();
          $("#mobooking-step-1-next").trigger("click");
        } else if (
          currentStep === 2 &&
          $("#mobooking-step-2-next").is(":visible") &&
          !$("#mobooking-step-2-next").is(":disabled")
        ) {
          e.preventDefault();
          $("#mobooking-step-2-next").trigger("click");
        }
      }
    });

    // Enhanced form submission with 3-second confirmation and redirect
    $registerForm.on("submit", async function (e) {
      e.preventDefault();

      // Final validation
      const isStep3Valid = await validateStep(3);
      if (!isStep3Valid) return;

      // Re-validate all previous steps for security
      const isStep1DataValid = await validateStep(1);
      if (!isStep1DataValid) {
        showStep(1);
        return;
      }

      const isStep2DataValid = await validateStep(2);
      if (!isStep2DataValid) {
        showStep(2);
        return;
      }

      // Clear any previous messages
      $registerMessageDiv.hide().removeClass("error success").empty();

      // Prepare form data
      const formData = {
        action: "mobooking_register",
        nonce: mobooking_auth_params.register_nonce,
        ...registrationData,
      };

      // Add invitation data if present
      const inviterId = $("#mobooking-inviter-id").val();
      const assignedRole = $("#mobooking-assigned-role").val();
      const invitationToken = $("#mobooking-invitation-token").val();

      if (inviterId && assignedRole && invitationToken) {
        formData.inviter_id = inviterId;
        formData.role_to_assign = assignedRole;
        formData.invitation_token = invitationToken;
      }

      // Submit the registration
      $.ajax({
        type: "POST",
        url: mobooking_auth_params.ajax_url,
        data: formData,
        dataType: "json",
        beforeSend: function () {
          console.log("MoBooking Register: Starting registration process");
          $registerForm
            .find('input[type="submit"]')
            .prop("disabled", true)
            .val("Creating Account...");
        },
        success: function (response) {
          console.log("MoBooking Register: AJAX success response:", response);

          if (response && response.success) {
            // Hide the form and show success message
            $registerForm.hide();
            $("#mobooking-progress-bar").hide();

            // Create enhanced success message with countdown
            const redirectUrl = response.data.redirect_url || "/dashboard/";
            const userName = registrationData.first_name || "there";

            showSuccessMessageWithCountdown(userName, redirectUrl);
          } else {
            console.error("MoBooking Register: Registration failed:", response);
            const errorMessage =
              response && response.data && response.data.message
                ? response.data.message
                : "Registration failed. Please try again.";

            $registerMessageDiv
              .addClass("error")
              .html(
                `<div style="color: #d63638; padding: 15px; background: #fcf2f2; border: 1px solid #d63638; border-radius: 4px; margin: 15px 0;">
                                <strong>Registration Failed</strong><br>
                                ${errorMessage}
                            </div>`
              )
              .show();
          }
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.error(
            "MoBooking Register: AJAX error:",
            textStatus,
            errorThrown,
            jqXHR.responseText
          );

          let errorMessage = "An unexpected error occurred. Please try again.";

          if (jqXHR.responseText) {
            try {
              const errorResponse = JSON.parse(jqXHR.responseText);
              if (
                errorResponse &&
                errorResponse.data &&
                errorResponse.data.message
              ) {
                errorMessage = errorResponse.data.message;
              }
            } catch (e) {
              if (jqXHR.responseText.length < 200) {
                errorMessage = jqXHR.responseText;
              }
            }
          }

          $registerMessageDiv
            .addClass("error")
            .html(
              `<div style="color: #d63638; padding: 15px; background: #fcf2f2; border: 1px solid #d63638; border-radius: 4px; margin: 15px 0;">
                            <strong>Network Error</strong><br>
                            ${errorMessage}
                        </div>`
            )
            .show();
        },
        complete: function (jqXHR, textStatus) {
          console.log("MoBooking Register: AJAX complete. Status:", textStatus);

          // Only re-enable the form if we're showing an error (not success)
          if (
            !$registerMessageDiv.hasClass("success") ||
            $registerForm.is(":visible")
          ) {
            $registerForm
              .find('input[type="submit"]')
              .prop("disabled", false)
              .val("Confirm & Register");
          }
        },
      });
    });

    // Enhanced success message with countdown and automatic redirect
    function showSuccessMessageWithCountdown(userName, redirectUrl) {
      let countdown = 3;

      function updateMessage() {
        const message = `
                    <div style="text-align: center; padding: 30px; background: #f0f9ff; border: 2px solid #0ea5e9; border-radius: 8px; margin: 20px 0;">
                        <div style="color: #0ea5e9; font-size: 48px; margin-bottom: 20px;">
                            ✓
                        </div>
                        <h3 style="color: #0369a1; margin: 0 0 15px 0; font-size: 24px;">
                            Welcome to MoBooking, ${userName}!
                        </h3>
                        <p style="color: #0369a1; margin: 0 0 20px 0; font-size: 16px;">
                            Your account has been successfully created. 
                            ${
                              countdown > 0
                                ? `Redirecting to your dashboard in ${countdown} second${
                                    countdown !== 1 ? "s" : ""
                                  }...`
                                : "Redirecting now..."
                            }
                        </p>
                        <div style="margin-top: 20px;">
                            <div style="width: 100%; height: 4px; background: #e0f2fe; border-radius: 2px; overflow: hidden;">
                                <div style="height: 100%; background: #0ea5e9; border-radius: 2px; transition: width 1s linear; width: ${
                                  ((3 - countdown) / 3) * 100
                                }%;"></div>
                            </div>
                        </div>
                        <p style="margin-top: 15px; font-size: 14px; color: #64748b;">
                            <a href="${redirectUrl}" style="color: #0ea5e9; text-decoration: none;">Click here if you're not redirected automatically</a>
                        </p>
                    </div>
                `;

        $registerMessageDiv.addClass("success").html(message).show();
      }

      // Initial message
      updateMessage();

      // Start countdown
      const countdownInterval = setInterval(function () {
        countdown--;

        if (countdown >= 0) {
          updateMessage();
        } else {
          clearInterval(countdownInterval);

          // Perform redirect
          console.log("MoBooking Register: Redirecting to", redirectUrl);
          window.location.href = redirectUrl;
        }
      }, 1000);

      // Fallback redirect in case something goes wrong
      setTimeout(function () {
        if (
          window.location.pathname !==
          new URL(redirectUrl, window.location.origin).pathname
        ) {
          console.log("MoBooking Register: Fallback redirect triggered");
          window.location.href = redirectUrl;
        }
      }, 5000); // 5 second fallback
    }
  }
});
