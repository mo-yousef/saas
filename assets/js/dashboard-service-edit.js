jQuery(function ($) {
  // Service Edit functionality
  const ServiceEdit = {
    optionIndex: mobooking_service_edit_params.option_count,
    iconDialog: null,
    selectedIconIdentifier: null,
    selectedIconHtml: null,

    init: function () {
      this.bindEvents();
      this.initSwitches();
      this.initExistingOptions();
      this.initSortable(); // Initialize sortable functionality
      this.fixOptionIndices(); // Fix indices on load
    },

    // Initialize existing options to show proper state
    initExistingOptions: function () {
      $(".option-item").each(function () {
        const $option = $(this);
        const selectedType = $option.find(".option-type-radio:checked").val();
        const selectedPriceType = $option
          .find(".price-impact-type-radio:checked")
          .val();

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

        // Show price impact value container if price type is selected or for special types
        const $valueContainer = $option.find(".price-impact-value-container");
        if (
          selectedPriceType ||
          selectedType === "sqm" ||
          selectedType === "kilometers"
        ) {
          $valueContainer.show();
        }
      });
    },

    // Initialize sortable functionality for options
    initSortable: function () {
      const self = this;
      const $container = $("#options-container");

      // Check if jQuery UI Sortable is available
      if (typeof $.fn.sortable === "function") {
        $container.sortable({
          items: ".option-item",
          handle: ".mobooking-option-drag-handle",
          placeholder: "sortable-placeholder",
          cursor: "grabbing",
          tolerance: "pointer",
          start: function (e, ui) {
            ui.placeholder.height(ui.item.height());
            ui.placeholder.css({
              "background-color": "hsl(var(--muted))",
              border: "2px dashed hsl(var(--border))",
              "border-radius": "var(--radius)",
              "margin-bottom": "1rem",
            });
          },
          stop: function (e, ui) {
            // Fix indices after sorting
            self.fixOptionIndices();

            // Update sort order values
            $container.find(".option-item").each(function (index) {
              $(this)
                .find('input[name*="[sort_order]"]')
                .val(index + 1);
            });
          },
        });
      } else {
        console.warn(
          "jQuery UI Sortable not available. Option sorting disabled."
        );
      }
    },
    fixOptionIndices: function () {
      $("#options-container .option-item").each(function (index) {
        const $option = $(this);
        const newIndex = index;

        // Update the data attribute
        $option.attr("data-option-index", newIndex);

        // Update all input names within this option
        $option.find("input, select, textarea").each(function () {
          const $input = $(this);
          const name = $input.attr("name");
          if (name && name.startsWith("options[")) {
            const newName = name.replace(
              /options\[\d+\]/,
              `options[${newIndex}]`
            );
            $input.attr("name", newName);
          }
        });

        // Update choice indices
        $option.find(".choice-item").each(function (choiceIndex) {
          $(this)
            .find("input")
            .each(function () {
              const $input = $(this);
              const name = $input.attr("name");
              if (name && name.includes("[choices][")) {
                const newName = name.replace(
                  /\[choices\]\[\d+\]/,
                  `[choices][${choiceIndex}]`
                );
                $input.attr("name", newName);
              }
            });
        });
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
      $("#select-icon-btn").on("click", (e) => {
        e.preventDefault();
        this.openIconSelector();
      });
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
          $(this).closest(".option-item").remove();
          self.updateOptionsBadge();
          self.fixOptionIndices(); // Fix indices after deletion
          self.showEmptyStateIfNeeded();
        }
      });

      // Option name change
      $container.on("blur", ".option-name-input", function () {
        const $input = $(this);
        const newName = $input.val().trim() || "Unnamed Option";
        $input.closest(".option-item").find(".option-name").text(newName);
      });

      // Option type change
      $container.on("change", ".option-type-radio", function () {
        const $radio = $(this);
        const $optionItem = $radio.closest(".option-item");
        const selectedType = $radio.val();
        const choiceTypes = ["select", "radio", "checkbox"];
        const $choicesContainer = $optionItem.find(".choices-container");
        const $priceTypesGrid = $optionItem.find(".price-types-grid");
        const $priceImpactDescription = $optionItem.find(
          ".price-impact-description"
        );
        const $priceImpactLabel = $optionItem.find(".price-impact-label");
        const $priceImpactValueLabel = $optionItem.find(
          ".price-impact-value-label"
        );

        // Show/hide choices container
        if (choiceTypes.includes(selectedType)) {
          $choicesContainer.slideDown(200);
        } else {
          $choicesContainer.slideUp(200);
        }

        // Handle special types (sqm, kilometers)
        if (selectedType === "sqm" || selectedType === "kilometers") {
          $priceTypesGrid.slideUp(200);
          $priceImpactDescription.hide();
          const labelText =
            selectedType === "sqm"
              ? mobooking_service_edit_params.i18n.price_per_sqm ||
                "Price per Square Meter"
              : mobooking_service_edit_params.i18n.price_per_km ||
                "Price per Kilometer";
          $priceImpactLabel.text(labelText);
          $priceImpactValueLabel.text(labelText);
          $optionItem.find(".price-impact-type-input").val("fixed");
        } else {
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

        let newChoiceHtml = `
          <div class="choice-item flex items-center gap-2">
              <input type="text" 
                     name="options[${optionIndex}][choices][${choiceIndex}][label]" 
                     class="form-input flex-1" 
                     placeholder="Choice Label"
                     required>
              <input type="number" 
                     name="options[${optionIndex}][choices][${choiceIndex}][price]" 
                     class="form-input w-24" 
                     placeholder="Price" 
                     step="0.01"
                     value="0">
              <button type="button" class="btn-icon remove-choice-btn">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M3 6h18"/>
                      <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                      <path d="m19 6-1 14H6L5 6"/>
                  </svg>
              </button>
          </div>
        `;

        $list.append(newChoiceHtml);

        // Focus on the new choice label input
        $list.find('.choice-item:last-child input[type="text"]').focus();
      });

      // Remove choice
      $container.on("click", ".remove-choice-btn", function () {
        const $choiceItem = $(this).closest(".choice-item");
        const $optionItem = $choiceItem.closest(".option-item");

        $choiceItem.remove();

        // Re-index remaining choices
        $optionItem.find(".choice-item").each(function (index) {
          const optionIndex = $optionItem.data("option-index");
          $(this)
            .find("input")
            .each(function () {
              const $input = $(this);
              const name = $input.attr("name");
              if (name && name.includes("[choices][")) {
                const newName = name.replace(
                  /\[choices\]\[\d+\]/,
                  `[choices][${index}]`
                );
                $input.attr("name", newName);
              }
            });
        });
      });

      // Price impact type change
      $container.on("change", ".price-impact-type-radio", function () {
        const $radio = $(this);
        const $optionItem = $radio.closest(".option-item");
        const impactType = $radio.val();
        const $valueContainer = $optionItem.find(
          ".price-impact-value-container"
        );

        // Always show the price input when a price impact type is selected
        if (impactType) {
          $valueContainer.slideDown(200);
        } else {
          $valueContainer.slideUp(200);
        }

        // Update card selection visually
        $optionItem.find(".price-type-card").removeClass("selected");
        $radio.closest(".price-type-card").addClass("selected");

        // Update hidden input
        $optionItem.find(".price-impact-type-input").val($radio.val());
      });

      // Switch toggles
      $container.on("click", ".switch", function () {
        const $switch = $(this);
        const isChecked = $switch.hasClass("switch-checked");
        const newValue = isChecked ? "0" : "1";

        $switch.toggleClass("switch-checked");
        $switch.siblings("input[type=hidden]").val(newValue);

        if ($switch.data("switch") === "required") {
          const $label = $switch.parent().find(".text-sm");
          if ($label.length) {
            $label.text(newValue === "1" ? "Required option" : "Optional");
          }
        }

        if ($switch.data("switch") === "status") {
          const $label = $switch.parent().find(".text-sm");
          if ($label.length) {
            $label.text(newValue === "1" ? "Active" : "Inactive");
          }
        }
      });
    },

    initSwitches: function () {
      $(".switch").each(function () {
        const $switchEl = $(this);
        const $hiddenInput = $switchEl.siblings("input[type=hidden]");
        const currentValue = $hiddenInput.val();
        const isChecked = currentValue === "1";

        if (isChecked) {
          $switchEl.addClass("switch-checked");
        } else {
          $switchEl.removeClass("switch-checked");
        }

        if ($switchEl.data("switch") === "required") {
          const $label = $switchEl.parent().find(".text-sm");
          if ($label.length) {
            $label.text(isChecked ? "Required option" : "Optional");
          }
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
      this.fixOptionIndices(); // Fix indices after adding
      this.refreshSortable(); // Refresh sortable after adding new option
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
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                ${i18n.add_first_option || "Add Your First Option"}
            </button>
        </div>
      `;
      $("#options-container").html(emptyStateHtml);
    },

    showEmptyStateIfNeeded: function () {
      const $container = $("#options-container");
      if ($container.find(".option-item").length === 0) {
        this.showEmptyState();
      }
    },

    updateOptionsBadge: function () {
      const count = $("#options-container .option-item").length;
      const $badge = $("#options-count-badge");
      if (count > 0) {
        $badge.text(count).removeClass("hidden");
      } else {
        $badge.addClass("hidden");
      }
    },

    saveService: function () {
      const self = this;
      const $form = $("#mobooking-service-form");
      const $submitBtn = $form.find('button[type="submit"]');
      const originalText = $submitBtn.text();
      const isUpdating = $("input[name='service_id']").length > 0 && $("input[name='service_id']").val() > 0;

      // Fix indices before submission
      this.fixOptionIndices();

      // Validate that all choice labels are filled
      let hasEmptyChoices = false;
      let hasEmptyOptionNames = false;

      // Check for empty choice labels (only visible ones)
      $form.find('.choice-item:visible input[type="text"]').each(function () {
        if ($(this).val().trim() === "") {
          hasEmptyChoices = true;
          $(this).addClass("error").css("border-color", "#ef4444");
        } else {
          $(this).removeClass("error").css("border-color", "");
        }
      });

      // Check for empty option names
      $form.find(".option-name-input").each(function () {
        if ($(this).val().trim() === "") {
          hasEmptyOptionNames = true;
          $(this).addClass("error").css("border-color", "#ef4444");
        } else {
          $(this).removeClass("error").css("border-color", "");
        }
      });

      // Remove required attribute from hidden price inputs to prevent validation errors
      $form.find('input[name*="price_impact_value"]').each(function () {
        const $input = $(this);
        const $container = $input.closest(".price-impact-value-container");
        if ($container.is(":hidden")) {
          $input.removeAttr("required");
        }
      });

      if (hasEmptyChoices) {
        alert("Please fill in all choice labels or remove empty choices.");
        return;
      }

      if (hasEmptyOptionNames) {
        alert("Please fill in all option names.");
        return;
      }

      // Show loading state
      $submitBtn
        .prop("disabled", true)
        .text(mobooking_service_edit_params.i18n.saving || "Saving...");

      // Collect form data
      const formData = new FormData($form[0]);
      formData.append("action", "mobooking_save_service");
      formData.append("nonce", mobooking_service_edit_params.nonce);

      // Debug: Log form data
      console.log("Form data being submitted:");
      for (let [key, value] of formData.entries()) {
        console.log(key, value);
      }

      $.ajax({
        url: mobooking_service_edit_params.ajax_url,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          if (response && response.success) {
              const message =
                  response.data?.message ||
                  (mobooking_service_edit_params.i18n?.service_saved) ||
                  "Service saved successfully.";

              if (typeof window.showToast === "function") {
                  window.showToast({ type: "success", title: "Success", message });
              }

              if (isUpdating) {
                  // It's an update, so we reload the page after a short delay to allow the toast to be seen.
                  setTimeout(() => location.reload(), 500);
              } else {
                  // It's a new service, so we redirect to the new edit page.
                  if (response.data && response.data.service_id) {
                      const newServiceId = response.data.service_id;
                      // Build the redirect URL. We assume the admin URL structure.
                      const redirectUrl = `admin.php?page=mobooking-service-edit&service_id=${newServiceId}`;
                      window.location.href = redirectUrl;
                  } else {
                      // Fallback in case the service_id is not returned, just reload.
                      setTimeout(() => location.reload(), 500);
                  }
              }
          } else {
            const message =
              (response && response.data && response.data.message) ||
              (mobooking_service_edit_params.i18n &&
                mobooking_service_edit_params.i18n.error_saving_service) ||
              "Error saving service. Please check your input and try again.";
            if (typeof window.showToast === "function") {
              window.showToast({ type: "error", title: "Error", message });
            }
          }
        },
        error: function (xhr, status, error) {
          const message =
            (mobooking_service_edit_params.i18n &&
              mobooking_service_edit_params.i18n.error_ajax) ||
            "An AJAX error occurred. Please try again.";
          if (typeof window.showToast === "function") {
            window.showToast({ type: "error", title: "Error", message });
          }
        },
        complete: function () {
          // Reset button state only if not redirecting/reloading
          if (!isUpdating) {
             $submitBtn.prop("disabled", false).text(originalText);
          }
        },
      });
    },

    deleteService: function () {
      const serviceId = $("#service_id").val();
      if (
        !serviceId ||
        !confirm(
          mobooking_service_edit_params.i18n.confirm_delete ||
            "Are you sure you want to delete this service? This action cannot be undone."
        )
      ) {
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
            alert("Service deleted successfully.");
            window.location.href = mobooking_service_edit_params.redirect_url;
          } else {
            alert(response.data?.message || "Error deleting service.");
          }
        },
        error: function () {
          alert("An error occurred while deleting the service.");
        },
      });
    },

    // --- Icon Selector Logic ---
    openIconSelector: function () {
        const self = this;
        this.selectedIconIdentifier = null;
        this.selectedIconHtml = null;

        const dialogContent = `
            <div class="icon-selector-content">
                <div class="preset-icons-section">
                    <h4 class="section-title">Preset Icons</h4>
                    <div id="dialog-preset-icons-grid" class="mobooking-icon-grid">
                        <div class="icon-grid-loading"><p>Loading icons...</p></div>
                    </div>
                </div>
                <hr class="my-4">
                <div class="custom-icon-section">
                    <h4 class="section-title">Upload Custom Icon</h4>
                    <p class="section-description">Upload your own SVG icon. For best results, use a simple, single-color SVG.</p>
                    <input type="file" id="dialog-custom-icon-upload" accept=".svg" class="mt-2">
                    <div id="dialog-custom-icon-upload-feedback" class="text-sm mt-2"></div>
                </div>
            </div>
        `;

        this.iconDialog = new MoBookingDialog({
            title: 'Choose an Icon',
            content: dialogContent,
            buttons: [
                {
                    label: 'Remove Icon',
                    class: 'destructive',
                    onClick: (dialog) => {
                        this.removeIcon();
                        dialog.close();
                    }
                },
                {
                    label: 'Cancel',
                    class: 'secondary',
                    onClick: (dialog) => dialog.close()
                },
                {
                    label: 'Set Icon',
                    class: 'primary',
                    onClick: (dialog) => {
                        this.setIcon();
                        dialog.close();
                    }
                }
            ],
            onOpen: (dialog) => {
                this.fetchPresetIcons(dialog);
                const setButton = dialog.findElement('.btn-primary');
                if (setButton) {
                    setButton.disabled = true;
                }

                const uploadInput = dialog.findElement('#dialog-custom-icon-upload');
                if (uploadInput) {
                    uploadInput.addEventListener('change', (e) => {
                        if (e.target.files[0]) {
                            this.handleCustomIconUpload(e.target.files[0], dialog);
                        }
                    });
                }
            }
        });

        this.iconDialog.show();
    },

    fetchPresetIcons: function(dialog) {
        const self = this;
        const grid = dialog.findElement('#dialog-preset-icons-grid');
        if (!grid) return;

        $.ajax({
            url: mobooking_service_edit_params.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_get_preset_icons',
                nonce: mobooking_service_edit_params.nonce
            },
            success: function(response) {
                if (response.success && response.data.icons) {
                    grid.innerHTML = ''; // Clear loading
                    for (const name in response.data.icons) {
                        const item = document.createElement('div');
                        item.className = 'mobooking-icon-grid-item';
                        item.dataset.iconId = 'preset:' + name;
                        item.innerHTML = response.data.icons[name];
                        item.addEventListener('click', () => self.handleIconSelection(item, dialog));
                        grid.appendChild(item);
                    }
                } else {
                    grid.innerHTML = '<p>Could not load icons.</p>';
                }
            },
            error: function() {
                grid.innerHTML = '<p>Error loading icons.</p>';
            }
        });
    },

    handleIconSelection: function(item, dialog) {
        const allItems = dialog.findElement('.mobooking-icon-grid').children;
        for (let i = 0; i < allItems.length; i++) {
            allItems[i].classList.remove('selected');
        }
        item.classList.add('selected');

        this.selectedIconIdentifier = item.dataset.iconId;
        this.selectedIconHtml = item.innerHTML;

        const setButton = dialog.findElement('.btn-primary');
        if (setButton) {
            setButton.disabled = false;
        }
    },

    handleCustomIconUpload: function (file, dialog) {
      const self = this;
      const feedback = dialog.findElement("#dialog-custom-icon-upload-feedback");
      if (!file || !file.type === "image/svg+xml") {
        feedback.textContent = "Please select a valid SVG file.";
        feedback.className = "text-sm mt-2 error";
        return;
      }

      feedback.textContent = "Uploading...";
      feedback.className = "text-sm mt-2";

      const formData = new FormData();
      formData.append("action", "mobooking_upload_service_icon");
      formData.append("nonce", mobooking_service_edit_params.nonce);
      formData.append("service_icon_svg", file);
      const serviceId = $("input[name='service_id']").val();
      if (serviceId) {
        formData.append("service_id", serviceId);
      }

      $.ajax({
        url: mobooking_service_edit_params.ajax_url,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          if (response.success && response.data.icon_url) {
            feedback.textContent = "Upload successful! Click 'Set Icon' to use it.";
            feedback.className = "text-sm mt-2 success";
            self.selectedIconIdentifier = response.data.icon_url;
            self.selectedIconHtml = `<img src="${response.data.icon_url}" alt="Custom Icon" class="mobooking-custom-icon"/>`;
            const setButton = dialog.findElement('.btn-primary');
            if (setButton) {
                setButton.disabled = false;
            }
          } else {
            feedback.textContent = response.data?.message || "Upload failed.";
            feedback.className = "text-sm mt-2 error";
          }
        },
        error: function () {
            feedback.textContent = "An AJAX error occurred.";
            feedback.className = "text-sm mt-2 error";
        },
      });
    },

    setIcon: function () {
      if (this.selectedIconIdentifier && this.selectedIconHtml) {
        $("#service-icon").val(this.selectedIconIdentifier);
        $("#current-icon").html(this.selectedIconHtml);
      }
    },

    removeIcon: function () {
      const currentIcon = $("#service-icon").val();
      const $currentIconDisplay = $("#current-icon");

      // If the icon is a custom uploaded one (a URL), we should delete it from the server.
      if (currentIcon && currentIcon.startsWith("http")) {
        $.ajax({
          url: mobooking_service_edit_params.ajax_url,
          type: "POST",
          data: {
            action: "mobooking_delete_service_icon",
            nonce: mobooking_service_edit_params.nonce,
            icon_url: currentIcon,
          },
          success: function (response) {
            if (!response.success) {
              // Non-critical error, we still clear it from the UI.
              console.error("Could not delete custom icon from server:", response.data?.message);
            }
          },
        });
      }

      // Clear the icon from the form and UI
      $("#service-icon").val("");
      $currentIconDisplay.html(
        '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27,6.96 12,12.01 20.73,6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>'
      );
    },
    // --- End Icon Selector Logic ---

    handleImageUpload: function (file) {
      const maxSizeBytes = 5 * 1024 * 1024; // 5MB
      if (!file || !file.type.startsWith("image/")) {
        alert("Please select a valid image file.");
        return;
      }
      if (file.size > maxSizeBytes) {
        alert("Image is too large. Maximum size is 5MB.");
        return;
      }

      const $preview = $("#image-preview");
      const originalHtml = $preview.html();
      $preview.addClass("uploading");

      const formData = new FormData();
      formData.append("action", "mobooking_upload_service_image");
      formData.append("nonce", mobooking_service_edit_params.nonce);
      formData.append("service_image", file);

      $.ajax({
        url: mobooking_service_edit_params.ajax_url,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          if (
            response &&
            response.success &&
            response.data &&
            response.data.image_url
          ) {
            const imageUrl = response.data.image_url;
            $("#service-image-url").val(imageUrl);

            const removeBtnSvg =
              '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><path d="m19 6-1 14H6L5 6"/></svg>';

            $preview
              .removeClass("empty")
              .html(
                '<img src="' +
                  imageUrl +
                  '" alt="Service Image">' +
                  '<button type="button" class="remove-image-btn">' +
                  removeBtnSvg +
                  "</button>"
              );
          } else {
            const msg =
              (response && response.data && response.data.message) ||
              (mobooking_service_edit_params.i18n &&
                mobooking_service_edit_params.i18n.error_uploading_image) ||
              "Failed to upload image.";
            alert(msg);
            $preview.html(originalHtml);
          }
        },
        error: function () {
          alert(
            (mobooking_service_edit_params.i18n &&
              mobooking_service_edit_params.i18n.error_uploading_image) ||
              "Failed to upload image"
          );
          $preview.html(originalHtml);
        },
        complete: function () {
          $preview.removeClass("uploading");
        },
      });
    },

    removeImage: function () {
      const imageUrl = $("#service-image-url").val();
      const $preview = $("#image-preview");
      const serviceId = $("input[name='service_id']").val(); // Get service ID

      if (!imageUrl) {
        // Nothing to delete on server; just reset UI
        $preview
          .addClass("empty")
          .html(
            '<div class="mobooking-image-placeholder">' +
              '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>' +
              "<p>Click to upload image</p>" +
              '<p class="text-xs text-muted-foreground">PNG, JPG up to 5MB</p>' +
              "</div>"
          );
        return;
      }

      // If there's no serviceId, it means the service hasn't been saved yet.
      // In this case, we only need to remove the image from the UI, not the server.
      if (!serviceId) {
          $("#service-image-url").val("");
           $preview
              .addClass("empty")
              .html(
                '<div class="mobooking-image-placeholder">' +
                  '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>' +
                  "<p>Click to upload image</p>" +
                  '<p class="text-xs text-muted-foreground">PNG, JPG up to 5MB</p>' +
                  "</div>"
              );
          return;
      }

      $.ajax({
        url: mobooking_service_edit_params.ajax_url,
        type: "POST",
        data: {
          action: "mobooking_delete_service_image",
          nonce: mobooking_service_edit_params.nonce,
          image_url_to_delete: imageUrl,
          service_id: serviceId, // Pass the service ID to the backend
        },
        success: function (response) {
          if (response && response.success) {
            $("#service-image-url").val("");
            $preview
              .addClass("empty")
              .html(
                '<div class="mobooking-image-placeholder">' +
                  '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>' +
                  "<p>Click to upload image</p>" +
                  '<p class="text-xs text-muted-foreground">PNG, JPG up to 5MB</p>' +
                  "</div>"
              );
          } else {
            alert(
              (response && response.data && response.data.message) ||
                "Could not delete image."
            );
          }
        },
        error: function () {
          alert("An error occurred while deleting the image.");
        },
      });
    },
  };

  // Initialize on document ready
  ServiceEdit.init();
});
