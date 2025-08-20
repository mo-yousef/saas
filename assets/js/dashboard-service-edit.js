jQuery(function ($) {
  // Service Edit functionality
  const ServiceEdit = {
    optionIndex: mobooking_service_edit_params.option_count,

    init: function () {
      this.bindEvents();
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
          const choiceTypes = ["select", "radio", "checkbox"];
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

      // Delete
      $("#delete-service-btn").on("click", () => this.deleteService());

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
        const $optionElement = $(this).closest(".mobooking-option-item");
        $optionElement.toggleClass("expanded");
        $optionElement.find(".mobooking-option-content").slideToggle(200);
      });

      // Delete option
      $container.on("click", ".delete-option", function () {
        if (
          confirm(
            mobooking_service_edit_params.i18n.confirm_delete_option ||
              "Are you sure you want to delete this option?"
          )
        ) {
          $(this).closest(".mobooking-option-item").remove();

          if ($(".mobooking-option-item").length === 0) {
            self.showEmptyState();
          }
        }
      });

      // Update option name in header
      $container.on("input", ".option-name-input", function () {
        const $input = $(this);
        const nameDisplay = $input.closest(".mobooking-option-item").find(".mobooking-option-name");
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
        const choiceTypes = ["select", "radio", "checkbox"];

        // Always clear choices when the type changes, to avoid mismatched inputs.
        $choicesList.empty();

        if (choiceTypes.includes(type)) {
          $choicesContainer.slideDown(200);
        } else {
          $choicesContainer.slideUp(200);
        }

        // Handle SQM/Kilometers specific UI
        const $priceImpactContainer = $optionItem.find(
          ".price-impact-value-container"
        );
        const $priceTypesGrid = $optionItem.find(".price-types-grid");
        const $priceImpactDescription = $optionItem.find(
          ".price-impact-description"
        );
        const $priceImpactLabel = $optionItem.find(".price-impact-label");
        const $priceImpactValueLabel = $priceImpactContainer.find("label");

        if (type === "sqm" || type === "kilometers") {
          $priceImpactContainer.slideDown(200);
          $priceTypesGrid.slideUp(200);
          $priceImpactDescription.hide();
          const labelText =
            type === "sqm"
              ? mobooking_service_edit_params.i18n.price_per_sqm ||
                "Price per Square Meter"
              : mobooking_service_edit_params.i18n.price_per_km ||
                "Price per Kilometer";
          $priceImpactLabel.text(labelText);
          $priceImpactValueLabel.text(labelText);
          // Ensure price impact type is set to 'fixed'
          $optionItem.find(".price-impact-type-input").val("fixed");
        } else {
          // Restore default view for other types
          $priceTypesGrid.slideDown(200);
          $priceImpactDescription.show();
          $priceImpactLabel.text(
            mobooking_service_edit_params.i18n.price_impact || "Price Impact"
          );
          $priceImpactValueLabel.text(
            mobooking_service_edit_params.i18n.price_value || "Price Value"
          );
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

        newChoiceHtml = `
                        <div class="choice-item flex items-center gap-2">
                            <input type="text" name="options[${optionIndex}][choices][${choiceIndex}][label]" class="form-input flex-1" placeholder="Choice Label">
                            <input type="number" name="options[${optionIndex}][choices][${choiceIndex}][price]" class="form-input w-24" placeholder="Price" step="0.01">
                            <button type="button" class="btn-icon remove-choice-btn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><path d="m19 6-1 14H6L5 6"/></svg>
                            </button>
                        </div>
                    `;

        $list.append(newChoiceHtml);
      });

      // Remove choice
      $container.on("click", ".remove-choice-btn", function () {
        $(this).closest(".choice-item").remove();
      });

      // Update price impact value visibility
      $container.on("change", ".price-impact-type-radio", function () {
        const $radio = $(this);
        const impactType = $radio.val();
        const $optionItem = $radio.closest(".option-item");
        const $valueContainer = $optionItem.find(
          ".price-impact-value-container"
        );

        if (impactType) {
          $valueContainer.slideDown(200);
        } else {
          $valueContainer.slideUp(200);
        }

        // Update card selection visually
        $optionItem.find(".price-type-card").removeClass("selected");
        $radio.closest(".price-type-card").addClass("selected");
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
        const choiceTypes = ["select", "radio", "checkbox"];
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
        const self = this;
        const icons = {
            copy: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-copy"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>',
            plus: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus"><path d="M5 12h14"/><path d="M12 5v14"/></svg>',
            trash: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash-2"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>',
            star: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24px" height="24px"><path d="M0 0h24v24H0z" fill="none"/><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>',
            tools: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24px" height="24px"><path d="M0 0h24v24H0z" fill="none"/><path d="M21.69 18.56l-1.41-1.41c-.54-.54-1.29-.8-2.09-.69l-1.44.21c-.33.05-.6.31-.6.64v1.5c0 .28.22.5.5.5h.5c2.21 0 4-1.79 4-4v-.5c0-.33-.27-.59-.6-.54l-1.44.21c-.8.11-1.55.38-2.09.92L16.56 17H7.44l-1.41-1.41c-.54-.54-1.29-.8-2.09-.69l-1.44.21c-.33.05-.6.31-.6.64v1.5c0 .28.22.5.5.5h.5c2.21 0 4-1.79 4-4v-.5c0-.33-.27-.59-.6-.54l-1.44.21c-.8.11-1.55.38-2.09.92L1.94 17H1v-2.44l1.41-1.41c.54-.54.8-.1.69-2.09l-.21-1.44c-.05-.33.21-.6.54-.6h1.5c.28 0 .5.22.5.5v.5c0 2.21 1.79 4 4 4h.5c.33 0 .59-.27.54-.6l-.21-1.44c-.11-.8.15-1.55.92-2.09L12 7.44V1H9.56L8.14 2.41c-.54.54-.8 1.29-.69 2.09l.21 1.44c.05.33.31.6.64.6h1.5c.28 0 .5-.22.5-.5v-.5c0-2.21-1.79-4-4-4H1.5c-.33 0-.59.27-.54.6l.21 1.44c.11.8-.15 1.55-.92 2.09L-.44 7H-3v2.44l1.41 1.41c.54.54.8 1.29.69 2.09l-.21 1.44c-.05.33.21-.6.54-.6h1.5c.28 0 .5.22.5.5v.5c0 2.21 1.79 4 4 4h8c2.21 0 4-1.79 4-4v-.5c0-.28-.22-.5-.5-.5h-1.5c-.33 0-.6-.27-.6-.6l.21-1.44c.11-.8-.15-1.55-.92-2.09L14.56 10H12V7.44l1.41-1.41c.54-.54 1.29-.8 2.09-.69l1.44.21c.33.05.6.31.6.64v1.5c0 .28-.22.5-.5.5h-.5c-2.21 0-4 1.79-4 4v.5c0 .33.27.59.6.54l1.44-.21c.8-.11 1.55-.38 2.09-.92l1.41-1.41H21.69zM12 14c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/></svg>',
        };

        const iconGrid = document.createElement('div');
        iconGrid.className = 'mobooking-icon-grid';

        for (const [name, svg] of Object.entries(icons)) {
            const iconWrapper = document.createElement('div');
            iconWrapper.className = 'mobooking-icon-wrapper';
            iconWrapper.dataset.iconName = name;
            iconWrapper.innerHTML = svg;
            iconGrid.appendChild(iconWrapper);
        }

        const dialog = new MoBookingDialog({
            title: 'Choose an Icon',
            content: iconGrid,
            buttons: [
                {
                    label: 'Close',
                    class: 'secondary',
                    onClick: (dialog) => dialog.close(),
                },
            ],
        });

        dialog.show();

        dialog.findElement('.mobooking-icon-grid').addEventListener('click', function (e) {
            const wrapper = e.target.closest('.mobooking-icon-wrapper');
            if (wrapper) {
                const iconName = wrapper.dataset.iconName;
                const iconSvg = icons[iconName];
                $('#current-icon').html(iconSvg);
                $('#service-icon').val(iconSvg);
                dialog.close();
            }
        });
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
