/**
 * Enhanced Workers Page JavaScript - ShadCN UI Compatible
 * This file provides improved functionality for the refactored workers page
 *
 * To integrate: Replace contents of assets/js/dashboard-workers.js
 */

(function ($) {
  "use strict";
  console.log("NORDBOOKING Workers: Enhanced JavaScript Loaded");
  // Wait for DOM and localized parameters
  $(document).ready(function () {
    if (typeof nordbooking_workers_params === "undefined") {
      console.error("NORDBOOKING Workers: Required parameters not found");
      return;
    }

    new MoBookingWorkersManager();
  });

  /**
   * Workers Page Manager Class
   */
  function MoBookingWorkersManager() {
    this.init();
  }

  MoBookingWorkersManager.prototype = {
    // Initialize the manager
    init: function () {
      this.bindEvents();
      this.initializeComponents();
      this.loadExistingData();
    },

    // Bind all event handlers
    bindEvents: function () {
      this.bindAccordionEvents();
      this.bindFormEvents();
      this.bindTableEvents();
      this.bindPasswordToggleEvents();
      this.bindKeyboardEvents();
    },

    // Initialize UI components
    initializeComponents: function () {
      this.setupFeedbackAreas();
      this.setupFormValidation();
      this.setupAccessibilityFeatures();
    },

    // Load any existing data
    loadExistingData: function () {
      // Could be used to refresh worker data periodically
      // or load additional information as needed
    },

    /**
     * Accordion functionality
     */
    bindAccordionEvents: function () {
      var self = this;

      $(document).on("click", ".NORDBOOKING-accordion-trigger", function (e) {
        e.preventDefault();
        self.handleAccordionToggle($(this));
      });

      // Keyboard navigation for accordion
      $(document).on("keydown", ".NORDBOOKING-accordion-trigger", function (e) {
        if (e.key === "Enter" || e.key === " ") {
          e.preventDefault();
          self.handleAccordionToggle($(this));
        }
      });
    },

    handleAccordionToggle: function ($trigger) {
      var $item = $trigger.closest(".NORDBOOKING-accordion-item");
      var target = $trigger.data("target");
      var $content = $("#" + target);

      if ($item.hasClass("NORDBOOKING-accordion-open")) {
        this.closeAccordionItem($item, $content);
      } else {
        // Close other items first
        this.closeAllAccordionItems();
        this.openAccordionItem($item, $content);
      }
    },

    openAccordionItem: function ($item, $content) {
      $item.addClass("NORDBOOKING-accordion-open");
      $content.slideDown(200, function () {
        // Focus first input in opened section
        $content.find("input, select, textarea").first().focus();
      });
    },

    closeAccordionItem: function ($item, $content) {
      $item.removeClass("NORDBOOKING-accordion-open");
      $content.slideUp(200);
    },

    closeAllAccordionItems: function () {
      $(".NORDBOOKING-accordion-item").removeClass(
        "NORDBOOKING-accordion-open"
      );
      $(".NORDBOOKING-accordion-content").slideUp(200);
    },

    /**
     * Form event handlers
     */
    bindFormEvents: function () {
      var self = this;

      // Invite worker form
      $(document).on("submit", "#NORDBOOKING-invite-worker-form", function (e) {
        e.preventDefault();
        self.handleInviteWorkerSubmit($(this));
      });

      // Direct add worker form
      $(document).on(
        "submit",
        "#NORDBOOKING-direct-add-worker-form",
        function (e) {
          e.preventDefault();
          self.handleDirectAddWorkerSubmit($(this));
        }
      );

      // Change role forms
      $(document).on("submit", ".NORDBOOKING-change-role-form", function (e) {
        e.preventDefault();
        self.handleChangeRoleSubmit($(this));
      });

      // Delete worker forms
      $(document).on("submit", ".NORDBOOKING-delete-worker-form", function (e) {
        e.preventDefault();
        self.handleDeleteWorkerSubmit($(this));
      });

      // Edit worker details forms
      $(document).on(
        "submit",
        ".NORDBOOKING-edit-details-actual-form",
        function (e) {
          e.preventDefault();
          self.handleEditWorkerDetailsSubmit($(this));
        }
      );
    },

    /**
     * Table event handlers
     */
    bindTableEvents: function () {
      var self = this;

      // Edit worker details toggle
      $(document).on(
        "click",
        ".NORDBOOKING-edit-worker-details-btn",
        function (e) {
          e.preventDefault();
          self.handleEditWorkerToggle($(this));
        }
      );

      // Cancel edit
      $(document).on(
        "click",
        ".NORDBOOKING-cancel-edit-details-btn",
        function (e) {
          e.preventDefault();
          self.handleCancelEdit($(this));
        }
      );
    },

    /**
     * Password toggle functionality
     */
    bindPasswordToggleEvents: function () {
      $(document).on("click", ".NORDBOOKING-password-toggle", function (e) {
        e.preventDefault();
        var $toggle = $(this);
        var targetId = $toggle.data("target");
        var $input = $("#" + targetId);

        if ($input.attr("type") === "password") {
          $input.attr("type", "text");
          $toggle.addClass("NORDBOOKING-password-visible");
          $toggle.attr("aria-label", "Hide password");
        } else {
          $input.attr("type", "password");
          $toggle.removeClass("NORDBOOKING-password-visible");
          $toggle.attr("aria-label", "Show password");
        }
      });
    },

    /**
     * Keyboard navigation
     */
    bindKeyboardEvents: function () {
      var self = this;

      // Escape key to close edit forms and feedback
      $(document).on("keydown", function (e) {
        if (e.key === "Escape") {
          // Close any open edit forms
          $(".NORDBOOKING-edit-worker-form:visible").slideUp(200);

          // Hide inline feedback messages
          $(".NORDBOOKING-inline-alert:visible").slideUp(200);
        }
      });

      // Enter key on table rows to edit
      $(document).on("keydown", ".nordbooking-table-row", function (e) {
        if (e.key === "Enter") {
          var $editBtn = $(this).find(".NORDBOOKING-edit-worker-details-btn");
          if ($editBtn.length) {
            $editBtn.click();
          }
        }
      });
    },

    /**
     * Form submission handlers
     */
    handleInviteWorkerSubmit: function ($form) {
      var self = this;
      var feedbackArea = $("#invite-worker-feedback");

      this.hideInlineAlert(feedbackArea);

      if (!this.validateInviteForm($form)) {
        return;
      }

      var formData = $form.serialize();
      var $submitButton = $form.find('button[type="submit"]');

      this.setButtonLoading($submitButton, "Sending...");

      $.post(nordbooking_workers_params.ajax_url, formData)
        .done(function (response) {
          self.handleFormResponse(response, feedbackArea, $form, function () {
            self.refreshWorkersTable();
          });
        })
        .fail(function () {
          self.showInlineAlert(
            feedbackArea,
            self.getI18nString("error_ajax"),
            false
          );
        })
        .always(function () {
          self.resetButtonLoading($submitButton);
        });
    },

    handleDirectAddWorkerSubmit: function ($form) {
      var self = this;
      var feedbackArea = $("#direct-add-worker-feedback");

      this.hideInlineAlert(feedbackArea);

      if (!this.validateDirectAddForm($form)) {
        return;
      }

      var formData = $form.serialize();
      var $submitButton = $form.find('button[type="submit"]');

      this.setButtonLoading($submitButton, "Creating...");

      $.post(nordbooking_workers_params.ajax_url, formData)
        .done(function (response) {
          self.handleFormResponse(response, feedbackArea, $form, function () {
            self.refreshWorkersTable();
          });
        })
        .fail(function () {
          self.showInlineAlert(
            feedbackArea,
            self.getI18nString("error_ajax"),
            false
          );
        })
        .always(function () {
          self.resetButtonLoading($submitButton);
        });
    },

    handleChangeRoleSubmit: function ($form) {
      var self = this;
      var feedbackArea = $("#current-workers-feedback");
      var workerId = $form.find('input[name="worker_user_id"]').val();

      this.hideInlineAlert(feedbackArea);

      var formData = $form.serialize();
      var $submitButton = $form.find(".NORDBOOKING-change-role-submit-btn");

      this.setButtonLoading($submitButton, "Updating...");

      $.post(nordbooking_workers_params.ajax_url, formData)
        .done(function (response) {
          if (response.success) {
            self.showInlineAlert(feedbackArea, response.data.message, true);
            self.updateWorkerRoleDisplay(
              workerId,
              response.data.new_role_display_name,
              response.data.new_role_key
            );
          } else {
            self.showInlineAlert(
              feedbackArea,
              response.data.message || self.getI18nString("error_occurred"),
              false
            );
          }
        })
        .fail(function () {
          self.showInlineAlert(
            feedbackArea,
            self.getI18nString("error_ajax"),
            false
          );
        })
        .always(function () {
          self.resetButtonLoading($submitButton);
        });
    },

    handleDeleteWorkerSubmit: function ($form) {
      var self = this;
      var feedbackArea = $("#current-workers-feedback");
      var workerId = $form.find('input[name="worker_user_id"]').val();

      if (!confirm(this.getI18nString("confirm_delete"))) {
        return;
      }

      this.hideInlineAlert(feedbackArea);

      var formData = $form.serialize();
      var $submitButton = $form.find(".NORDBOOKING-delete-worker-btn");

      this.setButtonLoading($submitButton, "Deleting...");

      $.post(nordbooking_workers_params.ajax_url, formData)
        .done(function (response) {
          if (response.success) {
            self.showInlineAlert(feedbackArea, response.data.message, true);
            self.removeWorkerRow(workerId);
          } else {
            self.showInlineAlert(
              feedbackArea,
              response.data.message ||
                self.getI18nString("error_deleting_worker"),
              false
            );
          }
        })
        .fail(function () {
          self.showInlineAlert(
            feedbackArea,
            self.getI18nString("error_ajax"),
            false
          );
        })
        .always(function () {
          self.resetButtonLoading($submitButton);
        });
    },

    handleEditWorkerDetailsSubmit: function ($form) {
      var self = this;
      var feedbackArea = $("#current-workers-feedback");
      var workerId = $form.find('input[name="worker_user_id"]').val();

      this.hideInlineAlert(feedbackArea);

      var formData = $form.serialize();
      var $submitButton = $form.find(".NORDBOOKING-save-details-btn");

      this.setButtonLoading($submitButton, "Saving...");

      $.post(nordbooking_workers_params.ajax_url, formData)
        .done(function (response) {
          if (response.success) {
            self.showInlineAlert(feedbackArea, response.data.message, true);
            self.updateWorkerNameDisplay(workerId, $form);
            self.hideEditForm(workerId);
          } else {
            self.showInlineAlert(
              feedbackArea,
              response.data.message ||
                self.getI18nString("error_saving_worker"),
              false
            );
          }
        })
        .fail(function () {
          self.showInlineAlert(
            feedbackArea,
            self.getI18nString("error_ajax"),
            false
          );
        })
        .always(function () {
          self.resetButtonLoading($submitButton);
        });
    },

    /**
     * UI interaction handlers
     */
    handleEditWorkerToggle: function ($button) {
      var workerId = $button.data("worker-id");
      var $form = $("#edit-worker-form-" + workerId);

      if ($form.is(":visible")) {
        this.hideEditForm(workerId);
      } else {
        // Hide other edit forms first
        $(".NORDBOOKING-edit-worker-form:visible").slideUp(200);
        this.showEditForm(workerId);
      }
    },

    handleCancelEdit: function ($button) {
      var workerId = $button.data("worker-id");
      this.hideEditForm(workerId);
    },

    showEditForm: function (workerId) {
      var $form = $("#edit-worker-form-" + workerId);
      $form.slideDown(200, function () {
        // Focus first input
        $form.find("input").first().focus();
      });
    },

    hideEditForm: function (workerId) {
      $("#edit-worker-form-" + workerId).slideUp(200);
    },

    /**
     * Form validation
     */
    validateInviteForm: function ($form) {
      var emailValue = $form.find("#invite_email").val();
      var email = emailValue ? emailValue.trim() : "";
      var role = $form.find("#invite_role").val();

      if (!email) {
        this.showInlineAlert(
          $("#invite-worker-feedback"),
          "Email address is required.",
          false
        );
        $form.find("#invite_email").focus();
        return false;
      }

      if (!this.isValidEmail(email)) {
        this.showInlineAlert(
          $("#invite-worker-feedback"),
          "Please enter a valid email address.",
          false
        );
        $form.find("#invite_email").focus();
        return false;
      }

      if (!role) {
        this.showInlineAlert(
          $("#invite-worker-feedback"),
          "Please select a role.",
          false
        );
        $form.find("#invite_role").focus();
        return false;
      }

      return true;
    },

    validateDirectAddForm: function ($form) {
      var email = $form.find("#direct_add_staff_email").val().trim();
      var password = $form.find("#direct_add_staff_password").val();

      if (!email) {
        this.showInlineAlert(
          $("#direct-add-worker-feedback"),
          "Email address is required.",
          false
        );
        $form.find("#direct_add_staff_email").focus();
        return false;
      }

      if (!this.isValidEmail(email)) {
        this.showInlineAlert(
          $("#direct-add-worker-feedback"),
          "Please enter a valid email address.",
          false
        );
        $form.find("#direct_add_staff_email").focus();
        return false;
      }

      if (!password || password.length < 6) {
        this.showInlineAlert(
          $("#direct-add-worker-feedback"),
          "Password must be at least 6 characters long.",
          false
        );
        $form.find("#direct_add_staff_password").focus();
        return false;
      }

      return true;
    },

    isValidEmail: function (email) {
      var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return emailRegex.test(email);
    },

    /**
     * Feedback and alert management
     */
    setupFeedbackAreas: function () {
      // Initialize all feedback areas as hidden
      $(".NORDBOOKING-alert, .NORDBOOKING-inline-alert").hide();
    },

    showGlobalAlert: function (message, isSuccess) {
      const type = isSuccess ? "success" : "error";
      window.showAlert(message, type);
    },

    showInlineAlert: function ($alertArea, message, isSuccess) {
      var alertClass = isSuccess
        ? "NORDBOOKING-inline-alert-success"
        : "NORDBOOKING-inline-alert-error";

      $alertArea
        .removeClass(
          "NORDBOOKING-inline-alert-success NORDBOOKING-inline-alert-error"
        )
        .addClass(alertClass)
        .find(".NORDBOOKING-inline-alert-message")
        .text(message);

      $alertArea.slideDown(300);

      if (isSuccess) {
        setTimeout(function () {
          $alertArea.slideUp(300);
        }, 5000);
      }
    },

    hideInlineAlert: function ($alertArea) {
      $alertArea.slideUp(200);
    },

    hideAllAlerts: function () {
      $(".NORDBOOKING-alert, .NORDBOOKING-inline-alert").slideUp(200);
    },

    /**
     * Button state management
     */
    setButtonLoading: function ($button, loadingText) {
      if (!$button.data("original-html")) {
        $button.data("original-html", $button.html());
      }

      var spinnerIcon =
        '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="NORDBOOKING-spinner"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg>';

      $button
        .prop("disabled", true)
        .html(spinnerIcon + loadingText)
        .addClass("NORDBOOKING-loading");
    },

    resetButtonLoading: function ($button) {
      var originalHtml = $button.data("original-html");
      if (originalHtml) {
        $button
          .prop("disabled", false)
          .html(originalHtml)
          .removeClass("NORDBOOKING-loading");
      }
    },

    /**
     * Form response handling
     */
    handleFormResponse: function (
      response,
      feedbackArea,
      $form,
      successCallback
    ) {
      if (response.success) {
        this.showInlineAlert(feedbackArea, response.data.message, true);
        $form[0].reset();

        if (typeof successCallback === "function") {
          setTimeout(successCallback, 1500);
        }
      } else {
        this.showInlineAlert(
          feedbackArea,
          response.data.message || this.getI18nString("error_occurred"),
          false
        );
      }
    },

    /**
     * Table management
     */
    updateWorkerRoleDisplay: function (workerId, roleName, roleKey) {
      var $roleDisplay = $("#worker-row-" + workerId + " .worker-role-display");
      var $roleSelect = $(
        "#worker-row-" + workerId + " .NORDBOOKING-role-select"
      );

      $roleDisplay.text(roleName);
      $roleSelect.val(roleKey);
    },

    updateWorkerNameDisplay: function (workerId, $form) {
      var firstName = $form.find('input[name="edit_first_name"]').val().trim();
      var lastName = $form.find('input[name="edit_last_name"]').val().trim();
      var fullName =
        $.trim(firstName + " " + lastName) ||
        this.getI18nString("no_name_set") ||
        "No name set";

      var $row = $("#worker-row-" + workerId);
      $row.find(".worker-full-name-display").text(fullName);
      $row.find(".worker-first-name-display").text(firstName);
      $row.find(".worker-last-name-display").text(lastName);
    },

    removeWorkerRow: function (workerId) {
      var self = this;
      var $row = $("#worker-row-" + workerId);

      $row.addClass("NORDBOOKING-animate-out");

      setTimeout(function () {
        $row.fadeOut(300, function () {
          $(this).remove();

          // Check if table is now empty
          if ($(".nordbooking-table-row").length === 0) {
            setTimeout(function () {
              location.reload();
            }, 1000);
          }
        });
      }, 200);
    },

    refreshWorkersTable: function () {
      // Reload the page to show updated workers list
      setTimeout(function () {
        location.reload();
      }, 2000);
    },

    /**
     * Accessibility enhancements
     */
    setupAccessibilityFeatures: function () {
      // Add ARIA labels and roles
      $(".NORDBOOKING-accordion-trigger").attr("role", "button");
      $(".nordbooking-table").attr("role", "table");
      $(".NORDBOOKING-password-toggle").attr(
        "aria-label",
        "Toggle password visibility"
      );

      // Add keyboard navigation hints
      this.addKeyboardHints();

      // Setup focus management
      this.setupFocusManagement();
    },

    addKeyboardHints: function () {
      // Add tooltips for keyboard shortcuts
      $(".NORDBOOKING-accordion-trigger").attr(
        "title",
        "Press Enter or Space to toggle"
      );
      $(".nordbooking-table-row").attr("title", "Press Enter to edit");
    },

    setupFocusManagement: function () {
      var self = this;

      // Trap focus in modal-like edit forms
      $(document).on(
        "keydown",
        ".NORDBOOKING-edit-worker-form:visible",
        function (e) {
          if (e.key === "Tab") {
            self.manageFocusInEditForm($(this), e);
          }
        }
      );
    },

    manageFocusInEditForm: function ($form, e) {
      var $focusableElements = $form.find("input, select, textarea, button");
      var $firstElement = $focusableElements.first();
      var $lastElement = $focusableElements.last();

      if (e.shiftKey) {
        // Shift + Tab
        if ($(document.activeElement).is($firstElement)) {
          e.preventDefault();
          $lastElement.focus();
        }
      } else {
        // Tab
        if ($(document.activeElement).is($lastElement)) {
          e.preventDefault();
          $firstElement.focus();
        }
      }
    },

    /**
     * Form validation setup
     */
    setupFormValidation: function () {
      var self = this;

      // Real-time validation for email fields
      $(document).on("blur", 'input[type="email"]', function () {
        self.validateEmailField($(this));
      });

      // Real-time validation for password fields
      $(document).on("input", 'input[type="password"]', function () {
        self.validatePasswordField($(this));
      });

      // Clear validation errors on input
      $(document).on("input", "input.NORDBOOKING-input-error", function () {
        $(this).removeClass("NORDBOOKING-input-error");
      });
    },

    validateEmailField: function ($field) {
      var email = $field.val().trim();

      if (email && !this.isValidEmail(email)) {
        $field.addClass("NORDBOOKING-input-error");
        this.showFieldError($field, "Please enter a valid email address.");
      } else {
        $field.removeClass("NORDBOOKING-input-error");
        this.hideFieldError($field);
      }
    },

    validatePasswordField: function ($field) {
      var password = $field.val();
      var minLength = 6;

      if (password && password.length < minLength) {
        $field.addClass("NORDBOOKING-input-error");
        this.showFieldError(
          $field,
          `Password must be at least ${minLength} characters long.`
        );
      } else {
        $field.removeClass("NORDBOOKING-input-error");
        this.hideFieldError($field);
      }
    },

    showFieldError: function ($field, message) {
      var $errorElement = $field.siblings(".NORDBOOKING-field-error");

      if ($errorElement.length === 0) {
        $errorElement = $(
          '<div class="NORDBOOKING-field-error" style="color: var(--mobk-destructive); font-size: 0.8125rem; margin-top: 0.25rem;"></div>'
        );
        $field.after($errorElement);
      }

      $errorElement.text(message).show();
    },

    hideFieldError: function ($field) {
      $field.siblings(".NORDBOOKING-field-error").hide();
    },

    /**
     * Utility functions
     */
    getI18nString: function (key) {
      return nordbooking_workers_params.i18n &&
        nordbooking_workers_params.i18n[key]
        ? nordbooking_workers_params.i18n[key]
        : key;
    },

    /**
     * Performance optimizations
     */
    debounce: function (func, wait) {
      var timeout;
      return function executedFunction() {
        var context = this;
        var args = arguments;
        var later = function () {
          timeout = null;
          func.apply(context, args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
      };
    },

    /**
     * Error handling and logging
     */
    logError: function (error, context) {
      if (console && console.error) {
        console.error("NORDBOOKING Workers Error [" + context + "]:", error);
      }
    },

    /**
     * Cleanup and destroy
     */
    destroy: function () {
      // Remove event listeners
      $(document).off(".NORDBOOKING-workers");

      // Clear any timeouts
      if (this.refreshTimeout) {
        clearTimeout(this.refreshTimeout);
      }

      // Reset button states
      $(".NORDBOOKING-button[data-original-html]").each(function () {
        var $btn = $(this);
        $btn.html($btn.data("original-html")).prop("disabled", false);
      });
    },
  };

  // Add CSS for enhanced styles if not already included
  if (!$("#NORDBOOKING-workers-enhanced-styles").length) {
    var enhancedStyles = `
            <style id="NORDBOOKING-workers-enhanced-styles">
                .NORDBOOKING-input-error {
                    border-color: var(--mobk-destructive) !important;
                    box-shadow: 0 0 0 3px var(--mobk-destructive) / 0.1 !important;
                }
                
                .NORDBOOKING-spinner {
                    animation: spin 1s linear infinite;
                }
                
                @keyframes spin {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
                
                .NORDBOOKING-animate-out {
                    animation: fadeOutScale 0.3s ease-in forwards;
                }
                
                @keyframes fadeOutScale {
                    from {
                        opacity: 1;
                        transform: scale(1);
                    }
                    to {
                        opacity: 0;
                        transform: scale(0.95);
                    }
                }
                
                .NORDBOOKING-loading {
                    cursor: not-allowed;
                    opacity: 0.7;
                }
                
                .NORDBOOKING-field-error {
                    animation: slideInUp 0.2s ease-out;
                }
                
                @keyframes slideInUp {
                    from {
                        opacity: 0;
                        transform: translateY(5px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
            </style>
        `;

    $("head").append(enhancedStyles);
  }

  // Export for potential external use
  window.MoBookingWorkersManager = MoBookingWorkersManager;
})(jQuery);
