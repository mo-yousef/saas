jQuery(function ($) {
  // Service Edit functionality
  const ServiceEdit = {
    optionIndex: mobooking_service_edit_params.option_count,

    init: function () {
      this.bindEvents();
      this.initTabs();
      this.initSwitches();
      this.initExistingOptions(); // NEW: Initialize existing options properly
    },

    // NEW: Initialize existing options to show proper state
    initExistingOptions: function () {
      $(".option-item").each(function () {
        const $option = $(this);
        const selectedType = $option.find(".option-type-radio:checked").val();

        if (selectedType) {
          // Update the choices container visibility based on existing type
          const choiceTypes = [
            "select",
            "radio",
            "checkbox",
            "sqm",
            "kilometers",
          ];
          const $choicesContainer = $option.find(".choices-container");

          if (choiceTypes.includes(selectedType)) {
            $choicesContainer.show();
          } else {
            $choicesContainer.hide();
          }
        }
      });
    },

    bindEvents: function () {
      const self = this;
      const $container = $("#options-container");

      // Add option button
      $(document).on(
        "click",
        "#add-option-btn, .add-first-option",
        function () {
          self.addOption();
        }
      );

      // Form submission
      $("#mobooking-service-form").on("submit", function (e) {
        e.preventDefault();
        self.saveService();
      });

      // Save as draft
      $("#save-draft-btn").on("click", () => this.saveService(true));

      // Delete and duplicate
      $("#delete-service-btn").on("click", () => this.deleteService());
      $("#duplicate-service-btn").on("click", () => this.duplicateService());

      // Icon and image handling
      $("#select-icon-btn").on("click", () => this.openIconSelector());
      $("#image-preview").on("click", function () {
        if (!$(this).find("img").length) {
          $("#service-image-upload").click();
        }
      });
      $("#service-image-upload").on("change", function (e) {
        if (e.target.files[0]) {
          self.handleImageUpload(e.target.files[0]);
        }
      });
      $(document).on("click", ".remove-image-btn", function (e) {
        e.stopPropagation();
        self.removeImage();
      });

      // --- Delegated Option Events ---

      // Toggle option
      $container.on("click", ".toggle-option", function () {
        const $optionElement = $(this).closest(".option-item");
        $optionElement.toggleClass("expanded");
        $optionElement.find(".option-content").slideToggle(200);
      });

      // Delete option
      $container.on("click", ".delete-option", function () {
        if (
          confirm(
            mobooking_service_edit_params.i18n.confirm_delete_option ||
              "Are you sure you want to delete this option?"
          )
        ) {
          $(this).closest(".option-item").remove();
          self.updateOptionsBadge();

          if ($(".option-item").length === 0) {
            self.showEmptyState();
          }
        }
      });

      // Update option name in header
      $container.on("input", ".option-name-input", function () {
        const $input = $(this);
        const nameDisplay = $input.closest(".option-item").find(".option-name");
        nameDisplay.text($input.val() || "New Option");
      });

      // Update option type badge and show/hide choices
      $container.on("change", ".option-type-radio", function () {
        const $radio = $(this);
        const type = $radio.val();
        const $optionItem = $radio.closest(".option-item");

        // Update badge
        const badge = $optionItem.find(".option-badges .badge-outline");
        const typeLabel = $radio
          .closest(".option-type-card")
          .find(".option-type-title")
          .text();
        if (badge.length) {
          badge.text(typeLabel);
        }

        // Show/hide choices container and clear existing choices on type change
        const $choicesContainer = $optionItem.find(".choices-container");
        const $choicesList = $optionItem.find(".choices-list");
        const choiceTypes = [
          "select",
          "radio",
          "checkbox",
          "sqm",
          "kilometers",
        ];

        // Always clear choices when the type changes, to avoid mismatched inputs.
        $choicesList.empty();

        if (choiceTypes.includes(type)) {
          $choicesContainer.slideDown(200);
        } else {
          $choicesContainer.slideUp(200);
        }

        // Update card selection visually
        $optionItem.find(".option-type-card").removeClass("selected");
        $radio.closest(".option-type-card").addClass("selected");
      });

      // Add choice
      $container.on("click", ".add-choice-btn", function () {
        const $btn = $(this);
        const $list = $btn.siblings(".choices-list");
        const $optionItem = $btn.closest(".option-item");
        const optionIndex = $optionItem.data("option-index");
        const choiceIndex = $list.children().length;
        const optionType = $optionItem.find(".option-type-radio:checked").val();

        let newChoiceHtml = "";

        if (optionType === "sqm") {
          newChoiceHtml = `
                        <div class="choice-item flex items-center gap-2">
                            <input type="number" name="options[${optionIndex}][choices][${choiceIndex}][from_sqm]" class="form-input w-24" placeholder="From" step="0.01" min="0">
                            <span class="text-muted-foreground">-</span>
                            <input type="number" name="options[${optionIndex}][choices][${choiceIndex}][to_sqm]" class="form-input w-24" placeholder="To (∞ for unlimited)" step="0.01" min="0">
                            <input type="number" name="options[${optionIndex}][choices][${choiceIndex}][price_per_sqm]" class="form-input flex-1" placeholder="Price per SQM" step="0.01" min="0">
                            <button type="button" class="btn-icon remove-choice-btn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><path d="m19 6-1 14H6L5 6"/></svg>
                            </button>
                        </div>
                    `;
        } else if (optionType === "kilometers") {
          newChoiceHtml = `
                        <div class="choice-item flex items-center gap-2">
                            <input type="number" name="options[${optionIndex}][choices][${choiceIndex}][from_km]" class="form-input w-24" placeholder="From" step="0.1" min="0">
                            <span class="text-muted-foreground">-</span>
                            <input type="number" name="options[${optionIndex}][choices][${choiceIndex}][to_km]" class="form-input w-24" placeholder="To (∞ for unlimited)" step="0.1" min="0">
                            <input type="number" name="options[${optionIndex}][choices][${choiceIndex}][price_per_km]" class="form-input flex-1" placeholder="Price per KM" step="0.01" min="0">
                            <button type="button" class="btn-icon remove-choice-btn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><path d="m19 6-1 14H6L5 6"/></svg>
                            </button>
                        </div>
                    `;
        } else {
          newChoiceHtml = `
                        <div class="choice-item flex items-center gap-2">
                            <input type="text" name="options[${optionIndex}][choices][${choiceIndex}][label]" class="form-input flex-1" placeholder="Choice Label">
                            <input type="number" name="options[${optionIndex}][choices][${choiceIndex}][price]" class="form-input w-24" placeholder="Price" step="0.01">
                            <button type="button" class="btn-icon remove-choice-btn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><path d="m19 6-1 14H6L5 6"/></svg>
                            </button>
                        </div>
                    `;
        }

        $list.append(newChoiceHtml);
      });

      // Remove choice
      $container.on("click", ".remove-choice-btn", function () {
        $(this).closest(".choice-item").remove();
      });
    },

    initTabs: function () {
      $(".tabs-trigger").on("click", function () {
        const tabId = $(this).data("tab");

        $(".tabs-trigger").removeClass("active").attr("aria-selected", "false");
        $(this).addClass("active").attr("aria-selected", "true");

        $(".tabs-content").removeClass("active");
        $("#" + tabId).addClass("active");
      });
    },

    initSwitches: function () {
      $(document).on("click", ".switch", function () {
        const $switchEl = $(this);
        const $hiddenInput = $switchEl.parent().find('input[type="hidden"]');

        $switchEl.toggleClass("switch-checked");
        const isChecked = $switchEl.hasClass("switch-checked");

        if ($hiddenInput.length) {
          $hiddenInput.val(
            $switchEl.data("switch") === "status"
              ? isChecked
                ? "active"
                : "inactive"
              : isChecked
              ? "1"
              : "0"
          );
        }

        if ($switchEl.data("switch") === "status") {
          const $label = $switchEl.parent().find(".text-sm");
          if ($label.length) {
            $label.text(isChecked ? "Active" : "Inactive");
          }
        }
      });
    },

    addOption: function () {
      const $container = $("#options-container");
      $container.find(".empty-state").remove();

      const template = $("#mobooking-option-template").html();
      if (!template) {
        console.error("Option template not found!");
        return;
      }

      const optionHtml = template.replace(/__INDEX__/g, this.optionIndex);
      $container.append(optionHtml);

      const $newOption = $container.find(".option-item").last();
      if ($newOption.length) {
        $newOption.addClass("expanded");
        $newOption.find(".option-content").show();
        $newOption.find(".option-name-input").focus();

        // Initialize the new option's choices container visibility
        const selectedType =
          $newOption.find(".option-type-radio:checked").val() || "checkbox";
        const choiceTypes = [
          "select",
          "radio",
          "checkbox",
          "sqm",
          "kilometers",
        ];
        const $choicesContainer = $newOption.find(".choices-container");

        if (choiceTypes.includes(selectedType)) {
          $choicesContainer.show();
        } else {
          $choicesContainer.hide();
        }
      }

      this.updateOptionsBadge();
      this.optionIndex++;
    },

    showEmptyState: function () {
      const i18n = mobooking_service_edit_params.i18n;
      const emptyStateHtml = `
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M8 14s1.5 2 4 2 4-2 4-2"/>
                            <line x1="9" y1="9" x2="9.01" y2="9"/>
                            <line x1="15" y1="9" x2="15.01" y2="9"/>
                        </svg>
                    </div>
                    <h3 class="empty-state-title">${
                      i18n.no_options_yet || "No options added yet"
                    }</h3>
                    <p class="empty-state-description">
                        ${
                          i18n.add_options_prompt ||
                          "Add customization options like room size, add-ons, or special requirements to make your service more flexible."
                        }
                    </p>
                    <button type="button" class="btn btn-primary add-first-option">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14"/>
                            <path d="M12 5v14"/>
                        </svg>
                        Add Your First Option
                    </button>
                </div>
            `;
      $("#options-container").html(emptyStateHtml);
    },

    updateOptionsBadge: function () {
      const count = $(".option-item").length;
      const $badge = $('.tabs-trigger[data-tab="service-options"] .badge');

      if ($badge.length) {
        $badge.text(count);
      } else if (count > 0) {
        $('.tabs-trigger[data-tab="service-options"]').append(
          `<span class="badge badge-secondary">${count}</span>`
        );
      }
    },

    displaySaveError: function(errorMessage) {
        // This regex now looks for the option name and is more flexible about the prefix.
        const optionMatch = errorMessage.match(/'([^']+)':\s*(Range \d+:.*)/);
        let errorHandled = false;

        if (optionMatch && optionMatch[1] && optionMatch[2]) {
            const optionName = optionMatch[1];
            const cleanMessage = optionMatch[2]; // The part of the message after the name.

            $('.option-name-input').each(function() {
                if ($(this).val() === optionName) {
                    $(this).closest('.option-item').find('.option-feedback').text(cleanMessage);
                    errorHandled = true;
                    return false; // break loop
                }
            });
        }

        // Fallback to a general alert if we couldn't place the error message
        if (!errorHandled) {
            // A more generic fallback that doesn't rely on the regex
            const generalErrorContainer = $('#alert-container');
            if (generalErrorContainer.length) {
                const alertHtml = `<div class="alert alert-destructive"><span>${errorMessage}</span></div>`;
                generalErrorContainer.html(alertHtml);
            } else {
                alert(errorMessage);
            }
        }
    },

    saveService: function (isDraft = false) {
        const self = this;
        const $form = $("#mobooking-service-form");
        const $submitBtn = $form.find('button[type="submit"]');
        const originalText = $submitBtn.text();

        // Show loading state
        $submitBtn
            .prop("disabled", true)
            .text(mobooking_service_edit_params.i18n.saving || "Saving...");

        // Clear all previous option-level and global feedback messages
        $('.option-feedback').empty();
        $('#alert-container').empty();

        // Add draft status if saving as draft
        if (isDraft) {
            $("<input>")
                .attr({ type: "hidden", name: "status", value: "inactive" })
                .appendTo($form);
        }

        // Serialize form data
        const formData = new FormData($form[0]);
        formData.append("action", "mobooking_save_service");
        formData.append("nonce", mobooking_service_edit_params.nonce);

        $.ajax({
            url: mobooking_service_edit_params.ajax_url,
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    // Success logic remains the same
                    setTimeout(() => {
                        window.location.href = mobooking_service_edit_params.redirect_url;
                    }, 1000);
                } else {
                    // Handle non-400 errors that have success:false
                    self.displaySaveError(response.data.message || mobooking_service_edit_params.i18n.error_saving_service);
                }
            },
            error: function (xhr, status, error) {
                let errorMessage = mobooking_service_edit_params.i18n.error_ajax || "An AJAX error occurred.";
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.data && response.data.message) {
                        errorMessage = response.data.message;
                    }
                } catch (e) {
                    errorMessage = xhr.responseText || errorMessage;
                }
                self.displaySaveError(errorMessage);
            },
            complete: function () {
                // Restore button state
                $submitBtn.prop("disabled", false).text(originalText);
                if (isDraft) {
                    $form.find('input[name="status"][value="inactive"]').remove();
                }
            },
        });
    },

    deleteService: function () {
      if (
        !confirm(
          mobooking_service_edit_params.i18n.confirm_delete ||
            "Are you sure you want to delete this service? This action cannot be undone."
        )
      ) {
        return;
      }

      const serviceId = $('input[name="service_id"]').val();
      if (!serviceId) {
        alert("Service ID not found.");
        return;
      }

      $.ajax({
        url: mobooking_service_edit_params.ajax_url,
        type: "POST",
        data: {
          action: "mobooking_delete_service",
          service_id: serviceId,
          nonce: mobooking_service_edit_params.nonce,
        },
        success: function (response) {
          if (response.success) {
            window.location.href = mobooking_service_edit_params.redirect_url;
          } else {
            alert(response.data.message || "Error deleting service.");
          }
        },
        error: function () {
          alert("AJAX error occurred while deleting service.");
        },
      });
    },

    duplicateService: function () {
      // Implementation for duplicating service would go here
      console.log("Duplicate service functionality not yet implemented");
    },

    openIconSelector: function () {
      // Implementation for icon selector would go here
      console.log("Icon selector functionality not yet implemented");
    },

    handleImageUpload: function (file) {
      // Implementation for image upload would go here
      console.log("Image upload functionality not yet implemented");
    },

    removeImage: function () {
      $("#image-preview").find("img").remove();
      $("#service-image-upload").val("");
      console.log("Image removed");
    },
  };

  // Initialize the service edit functionality
  ServiceEdit.init();
});
