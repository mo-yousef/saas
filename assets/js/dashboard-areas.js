jQuery(document).ready(function ($) {
  "use strict";

  // Cache DOM elements with debugging
  console.log("Caching DOM elements...");
  const $areasListContainer = $("#mobooking-areas-list-container");
  const $areaForm = $("#mobooking-area-form");
  const $areaFormTitle = $("#mobooking-area-form-title");
  const $areaIdField = $("#mobooking-area-id");
  const $areaCountryField = $("#mobooking-area-country");
  const $areaNameField = $("#mobooking-area-name");
  const $areaZipcodeField = $("#mobooking-area-zipcode");
  const $saveAreaBtn = $("#mobooking-save-area-btn");
  const $cancelEditBtn = $("#mobooking-cancel-edit-area-btn");
  const $feedbackDiv = $("#mobooking-area-form-feedback").hide();
  const $paginationContainer = $("#mobooking-areas-pagination-container");
  const itemTemplate = $("#mobooking-area-item-template").html();

  // Area selection elements
  const $countrySelector = $("#mobooking-country-selector");
  const $citySelector = $("#mobooking-city-selector");
  const $areaZipSelectorContainer = $("#mobooking-area-zip-selector-container");
  const $addSelectedAreasBtn = $("#mobooking-add-selected-areas-btn");
  const $selectionFeedbackDiv = $("#mobooking-selection-feedback").hide();

  // Filter elements
  const $areasSearch = $("#mobooking-areas-search");
  const $countryFilter = $("#mobooking-country-filter");
  const $clearFiltersBtn = $("#mobooking-clear-filters");

  // Debug element existence
  console.log("DOM Elements Check:", {
    countrySelector: $countrySelector.length,
    areaCountryField: $areaCountryField.length,
    countryFilter: $countryFilter.length,
    citySelector: $citySelector.length,
    areasListContainer: $areasListContainer.length,
  });

  // Check if key elements exist
  if ($countrySelector.length === 0) {
    console.error(
      "Country selector not found! Looking for #mobooking-country-selector"
    );
  }
  if ($areaCountryField.length === 0) {
    console.error(
      "Area country field not found! Looking for #mobooking-area-country"
    );
  }

  let currentFilters = {
    paged: 1,
    limit: 20,
    search: "",
    country: "",
  };

  let countriesInitialized = false; // Prevent multiple initializations

  // Ensure parameters exist
  window.mobooking_areas_params = window.mobooking_areas_params || {};
  window.mobooking_areas_params.i18n = window.mobooking_areas_params.i18n || {};
  const i18n = window.mobooking_areas_params.i18n;

  // Utility functions
  function sanitizeHTML(str) {
    if (typeof str !== "string") return "";
    const temp = document.createElement("div");
    temp.textContent = str;
    return temp.innerHTML;
  }

  function renderTemplate(templateHtml, data) {
    if (!templateHtml) return "";
    let html = templateHtml;
    for (const key in data) {
      const value =
        data[key] === null || typeof data[key] === "undefined" ? "" : data[key];
      const placeholder = new RegExp(`{{${key}}}`, "g");
      html = html.replace(placeholder, sanitizeHTML(String(value)));
    }
    return html;
  }

  function showFeedback($element, message, type = "success", duration = 5000) {
    $element
      .removeClass("success error info")
      .addClass(type)
      .html(message)
      .fadeIn();

    if (duration > 0) {
      setTimeout(() => $element.fadeOut(), duration);
    }
  }

  function resetForm() {
    $areaForm[0].reset();
    $areaIdField.val("");
    $areaFormTitle.find("span").remove();
    $areaFormTitle.append(
      "<span> " + (i18n.manual_area_entry || "Manual Area Entry") + "</span>"
    );
    $saveAreaBtn.html(`
            <svg class="mobooking-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            ${i18n.add_area || "Add Area"}
        `);
    $cancelEditBtn.hide();
    $feedbackDiv.empty().removeClass("success error info").hide();
  }

  // Initialize countries for both dropdowns
  function initializeCountries() {
    if (countriesInitialized) {
      console.log("Countries already initialized, skipping...");
      return;
    }

    console.log("Initializing countries...");

    if (!mobooking_areas_params || !mobooking_areas_params.ajax_url) {
      console.error("mobooking_areas_params not available");
      return;
    }

    countriesInitialized = true; // Mark as initialized

    $.ajax({
      url: mobooking_areas_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_get_countries",
        nonce: mobooking_areas_params.nonce,
      },
      beforeSend: function () {
        console.log("Loading countries...");
      },
      success: function (response) {
        console.log("Countries response received:", response);

        if (response.success && response.data && response.data.countries) {
          console.log("Processing countries:", response.data.countries);

          // Clear and populate quick selector dropdown
          $countrySelector.empty().append(
            $("<option>", {
              value: "",
              text: i18n.choose_country || "Choose a country...",
            })
          );

          // Clear and populate manual entry dropdown
          $areaCountryField.empty().append(
            $("<option>", {
              value: "",
              text: i18n.select_country || "Select a country...",
            })
          );

          // Clear and populate filter dropdown
          $countryFilter.empty().append(
            $("<option>", {
              value: "",
              text: i18n.all_countries || "All Countries",
            })
          );

          // Add each country to all dropdowns
          response.data.countries.forEach(function (country) {
            console.log("Adding country:", country);

            const optionText = country.name;

            // Add to quick selector (uses country code as value)
            $countrySelector.append(
              $("<option>", {
                value: country.code,
                text: optionText,
              })
            );

            // Add to manual entry (uses country name as value)
            $areaCountryField.append(
              $("<option>", {
                value: country.name,
                "data-code": country.code,
                text: optionText,
              })
            );

            // Add to filter
            $countryFilter.append(
              $("<option>", {
                value: country.name,
                text: optionText,
              })
            );
          });

          console.log("Countries populated successfully");

          // Verify population immediately after
          setTimeout(function () {
            console.log("=== POST-POPULATION VERIFICATION ===");
            console.log(
              "Country Selector options count:",
              $countrySelector.find("option").length
            );
            console.log(
              "Area Country Field options count:",
              $areaCountryField.find("option").length
            );
            console.log(
              "Country Filter options count:",
              $countryFilter.find("option").length
            );

            // List all options
            console.log("Quick selector options:");
            $countrySelector.find("option").each(function (i, opt) {
              console.log(`  ${i}: "${opt.value}" - "${opt.text}"`);
            });
          }, 100);
        } else {
          console.error("Invalid response structure:", response);
          countriesInitialized = false; // Reset if failed
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX error loading countries:", {
          status: status,
          error: error,
          responseText: xhr.responseText,
        });
        countriesInitialized = false; // Reset if failed
      },
    });
  }

  // Handle country selection in quick selector
  $countrySelector.on("change", function () {
    const selectedCountry = $(this).val();
    $citySelector.prop("disabled", true).empty();
    $areaZipSelectorContainer.html(`
            <div class="mobooking-empty-state">
                <svg class="mobooking-empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                </svg>
                <p class="mobooking-empty-state-text">
                    ${
                      i18n.select_city ||
                      "Select a city to view available areas"
                    }
                </p>
            </div>
        `);
    $addSelectedAreasBtn.prop("disabled", true);

    if (!selectedCountry) {
      $citySelector.append(
        $("<option>", {
          value: "",
          text: i18n.select_country || "First select a country...",
        })
      );
      return;
    }

    // Load cities for selected country
    $.ajax({
      url: mobooking_areas_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_get_cities_for_country",
        nonce: mobooking_areas_params.nonce,
        country_code: selectedCountry,
      },
      beforeSend: function () {
        $citySelector.append(
          $("<option>", {
            value: "",
            text: i18n.loading || "Loading cities...",
          })
        );
      },
      success: function (response) {
        $citySelector.empty();
        if (response.success && response.data.cities) {
          $citySelector.append(
            $("<option>", {
              value: "",
              text: i18n.select_city || "Select a city...",
            })
          );

          response.data.cities.forEach(function (city) {
            $citySelector.append(
              $("<option>", {
                value: city.code || city.name,
                text: city.name,
              })
            );
          });
          $citySelector.prop("disabled", false);
        } else {
          $citySelector.append(
            $("<option>", {
              value: "",
              text: i18n.no_cities_found || "No cities found",
            })
          );
        }
      },
      error: function () {
        $citySelector.empty().append(
          $("<option>", {
            value: "",
            text: i18n.error_loading || "Error loading cities",
          })
        );
      },
    });
  });

  // Handle city selection
  $citySelector.on("change", function () {
    const selectedCity = $(this).val();
    const selectedCountry = $countrySelector.val();

    $addSelectedAreasBtn.prop("disabled", true);

    if (!selectedCity || !selectedCountry) {
      $areaZipSelectorContainer.html(`
                <div class="mobooking-empty-state">
                    <svg class="mobooking-empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    </svg>
                    <p class="mobooking-empty-state-text">
                        ${
                          i18n.select_city ||
                          "Select a city to view available areas"
                        }
                    </p>
                </div>
            `);
      return;
    }

    // Load areas for selected city
    $.ajax({
      url: mobooking_areas_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_get_areas_for_city",
        nonce: mobooking_areas_params.nonce,
        country_code: selectedCountry,
        city_code: selectedCity,
      },
      beforeSend: function () {
        $areaZipSelectorContainer.html(`
                    <div class="mobooking-loading-state">
                        <div class="mobooking-spinner"></div>
                        <p>${i18n.loading || "Loading areas..."}</p>
                    </div>
                `);
      },
      success: function (response) {
        if (
          response.success &&
          response.data.areas &&
          response.data.areas.length > 0
        ) {
          let areasHtml = '<div class="mobooking-areas-grid">';

          response.data.areas.forEach(function (area) {
            areasHtml += `
                            <label class="mobooking-area-checkbox-item">
                                <input type="checkbox" class="mobooking-area-checkbox" 
                                       value="${sanitizeHTML(
                                         area.zip_code || area.code
                                       )}" 
                                       data-area-name="${sanitizeHTML(
                                         area.name
                                       )}"
                                       data-country-code="${sanitizeHTML(
                                         selectedCountry
                                       )}"
                                       data-country-name="${sanitizeHTML(
                                         $countrySelector
                                           .find("option:selected")
                                           .text()
                                       )}">
                                <div class="mobooking-area-checkbox-content">
                                    <div class="mobooking-area-checkbox-name">${sanitizeHTML(
                                      area.name
                                    )}</div>
                                    <div class="mobooking-area-checkbox-zip">${sanitizeHTML(
                                      area.zip_code || area.code
                                    )}</div>
                                </div>
                            </label>
                        `;
          });

          areasHtml += "</div>";
          $areaZipSelectorContainer.html(areasHtml);

          // Enable bulk select button when areas are available
          updateAddSelectedAreasButton();
        } else {
          $areaZipSelectorContainer.html(`
                        <div class="mobooking-empty-state">
                            <svg class="mobooking-empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 12l-2-2m0 4l2-2m2 2v8a2 2 0 01-2 2H6a2 2 0 01-2-2v-8m14 0V9a2 2 0 00-2-2H5a2 2 0 00-2 2v3"/>
                            </svg>
                            <p class="mobooking-empty-state-text">
                                ${
                                  i18n.no_areas_found ||
                                  "No areas found for this city"
                                }
                            </p>
                        </div>
                    `);
        }
      },
      error: function () {
        $areaZipSelectorContainer.html(`
                    <div class="mobooking-error-state">
                        <p>${i18n.error_loading || "Error loading areas"}</p>
                    </div>
                `);
      },
    });
  });

  // Handle area selection checkboxes
  $(document).on("change", ".mobooking-area-checkbox", function () {
    updateAddSelectedAreasButton();
  });

  function updateAddSelectedAreasButton() {
    const $selectedCheckboxes = $(".mobooking-area-checkbox:checked");
    const selectedCount = $selectedCheckboxes.length;

    if (selectedCount > 0) {
      $addSelectedAreasBtn.prop("disabled", false).html(`
                    <svg class="mobooking-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    ${
                      i18n.add_selected_areas || "Add Selected Areas"
                    } (${selectedCount})
                `);
    } else {
      $addSelectedAreasBtn.prop("disabled", true).html(`
                    <svg class="mobooking-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    ${i18n.add_selected_areas || "Add Selected Areas"}
                `);
    }
  }

  // Handle bulk area addition
  $addSelectedAreasBtn.on("click", function () {
    const $selectedCheckboxes = $(".mobooking-area-checkbox:checked");

    if ($selectedCheckboxes.length === 0) {
      showFeedback(
        $selectionFeedbackDiv,
        i18n.no_areas_selected || "Please select at least one area to add.",
        "error"
      );
      return;
    }

    const originalButtonText = $(this).html();
    $(this).prop("disabled", true).html(`
            <div class="mobooking-spinner mobooking-spinner-sm"></div>
            ${i18n.adding || "Adding..."}
        `);

    const areasToAdd = [];
    $selectedCheckboxes.each(function () {
      const $checkbox = $(this);
      areasToAdd.push({
        area_name: $checkbox.data("area-name"),
        area_zipcode: $checkbox.val(),
        country_name: $checkbox.data("country-name"),
        country_code: $checkbox.data("country-code"),
      });
    });

    $.ajax({
      url: mobooking_areas_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_add_bulk_areas",
        nonce: mobooking_areas_params.nonce,
        areas: JSON.stringify(areasToAdd),
      },
      success: function (response) {
        if (response.success) {
          const addedCount = response.data.added_count || areasToAdd.length;
          const message =
            response.data.message ||
            (
              i18n.areas_added ||
              "Selected areas have been added to your service coverage!"
            ).replace("%d", addedCount);

          showFeedback($selectionFeedbackDiv, message, "success");

          // Refresh the areas list
          fetchAndRenderAreas(1);

          // Clear selections
          $selectedCheckboxes.prop("checked", false);
          updateAddSelectedAreasButton();
        } else {
          showFeedback(
            $selectionFeedbackDiv,
            response.data?.message ||
              i18n.error_generic ||
              "An error occurred. Please try again.",
            "error"
          );
        }
      },
      error: function () {
        showFeedback(
          $selectionFeedbackDiv,
          i18n.error_generic || "An error occurred. Please try again.",
          "error"
        );
      },
      complete: function () {
        $addSelectedAreasBtn.prop("disabled", false).html(originalButtonText);
      },
    });
  });

  // Fetch and render areas list
  function fetchAndRenderAreas(page = 1, showLoading = true) {
    currentFilters.paged = page;

    if (showLoading) {
      $areasListContainer.html(`
                <div class="mobooking-loading-state">
                    <div class="mobooking-spinner"></div>
                    <p>${i18n.loading || "Loading your service areas..."}</p>
                </div>
            `);
    }

    $.ajax({
      url: mobooking_areas_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_get_areas",
        nonce: mobooking_areas_params.nonce,
        ...currentFilters,
      },
      success: function (response) {
        if (response.success && response.data) {
          renderAreasList(response.data);
        } else {
          $areasListContainer.html(`
                        <div class="mobooking-empty-state">
                            <svg class="mobooking-empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            </svg>
                            <p class="mobooking-empty-state-text">
                                ${
                                  response.data?.message ||
                                  i18n.no_areas_found ||
                                  "No service areas found. Add your first area above."
                                }
                            </p>
                        </div>
                    `);
        }
      },
      error: function () {
        $areasListContainer.html(`
                    <div class="mobooking-error-state">
                        <p>${
                          i18n.error_loading ||
                          "Error loading areas. Please try again."
                        }</p>
                    </div>
                `);
      },
    });
  }

  function renderAreasList(data) {
    if (!data.areas || data.areas.length === 0) {
      $areasListContainer.html(`
                <div class="mobooking-empty-state">
                    <svg class="mobooking-empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    </svg>
                    <p class="mobooking-empty-state-text">
                        ${
                          i18n.no_areas_found ||
                          "No service areas found. Add your first area above."
                        }
                    </p>
                </div>
            `);
      $paginationContainer.empty();
      return;
    }

    let areasHtml = '<div class="mobooking-areas-list-grid">';

    data.areas.forEach(function (area) {
      // Format the created date
      const createdDate = area.created_at
        ? new Date(area.created_at).toLocaleDateString()
        : "";

      const areaData = {
        area_id: area.area_id,
        area_name: area.area_name || area.area_value, // Fallback for existing data
        area_zipcode: area.area_zipcode || area.area_value,
        country_name: area.country_name || area.country_code,
        created_at_formatted: createdDate,
      };

      areasHtml += renderTemplate(itemTemplate, areaData);
    });

    areasHtml += "</div>";
    $areasListContainer.html(areasHtml);

    // Render pagination
    renderPagination(data.total_count, data.per_page, data.current_page);
  }

  function renderPagination(totalItems, itemsPerPage, currentPage) {
    $paginationContainer.empty();

    if (!totalItems || totalItems <= itemsPerPage) return;

    const totalPages = Math.ceil(totalItems / itemsPerPage);
    let paginationHtml =
      '<div class="mobooking-pagination"><ul class="pagination-links">';

    // Previous button
    if (currentPage > 1) {
      paginationHtml += `
                <li><a href="#" class="page-numbers prev" data-page="${
                  currentPage - 1
                }">
                    ← ${i18n.previous || "Previous"}
                </a></li>
            `;
    }

    // Page numbers
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);

    if (startPage > 1) {
      paginationHtml +=
        '<li><a href="#" class="page-numbers" data-page="1">1</a></li>';
      if (startPage > 2) {
        paginationHtml += '<li><span class="page-numbers dots">…</span></li>';
      }
    }

    for (let i = startPage; i <= endPage; i++) {
      paginationHtml += `
                <li><a href="#" class="page-numbers ${
                  i === currentPage ? "current" : ""
                }" data-page="${i}">${i}</a></li>
            `;
    }

    if (endPage < totalPages) {
      if (endPage < totalPages - 1) {
        paginationHtml += '<li><span class="page-numbers dots">…</span></li>';
      }
      paginationHtml += `<li><a href="#" class="page-numbers" data-page="${totalPages}">${totalPages}</a></li>`;
    }

    // Next button
    if (currentPage < totalPages) {
      paginationHtml += `
                <li><a href="#" class="page-numbers next" data-page="${
                  currentPage + 1
                }">
                    ${i18n.next || "Next"} →
                </a></li>
            `;
    }

    paginationHtml += "</ul></div>";
    $paginationContainer.html(paginationHtml);
  }

  // Handle pagination clicks
  $(document).on("click", ".page-numbers", function (e) {
    e.preventDefault();
    if ($(this).hasClass("current") || $(this).hasClass("dots")) return;

    const page = parseInt($(this).data("page"));
    if (page) {
      fetchAndRenderAreas(page);
    }
  });

  // Handle search and filters
  let searchTimeout;
  $areasSearch.on("input", function () {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      currentFilters.search = $(this).val().trim();
      fetchAndRenderAreas(1);
    }, 500);
  });

  $countryFilter.on("change", function () {
    currentFilters.country = $(this).val();
    fetchAndRenderAreas(1);
  });

  $clearFiltersBtn.on("click", function () {
    $areasSearch.val("");
    $countryFilter.val("");
    currentFilters.search = "";
    currentFilters.country = "";
    fetchAndRenderAreas(1);
  });

  // Handle manual area form submission
  $areaForm.on("submit", function (e) {
    e.preventDefault();
    $feedbackDiv.empty().removeClass("success error info").hide();

    const areaId = $areaIdField.val();
    const countryName = $areaCountryField.val();
    const areaName = $areaNameField.val().trim();
    const areaZipcode = $areaZipcodeField.val().trim();

    if (!countryName || !areaName || !areaZipcode) {
      showFeedback(
        $feedbackDiv,
        i18n.fields_required || "All fields are required.",
        "error"
      );
      return;
    }

    const originalButtonText = $saveAreaBtn.html();
    $saveAreaBtn.prop("disabled", true).html(`
            <div class="mobooking-spinner mobooking-spinner-sm"></div>
            ${i18n.saving || "Saving..."}
        `);

    const actionName = areaId ? "mobooking_update_area" : "mobooking_add_area";
    const requestData = {
      action: actionName,
      nonce: mobooking_areas_params.nonce,
      country_name: countryName,
      area_name: areaName,
      area_zipcode: areaZipcode,
    };

    if (areaId) {
      requestData.area_id = areaId;
    }

    $.ajax({
      url: mobooking_areas_params.ajax_url,
      type: "POST",
      data: requestData,
      success: function (response) {
        if (response.success) {
          const message = areaId
            ? i18n.success_updated || "Service area updated successfully!"
            : i18n.success_added || "Service area added successfully!";

          showFeedback($feedbackDiv, message, "success");

          if (!areaId) {
            resetForm();
          }

          fetchAndRenderAreas(currentFilters.paged);
        } else {
          showFeedback(
            $feedbackDiv,
            response.data?.message ||
              i18n.error_generic ||
              "An error occurred. Please try again.",
            "error"
          );
        }
      },
      error: function () {
        showFeedback(
          $feedbackDiv,
          i18n.error_generic || "An error occurred. Please try again.",
          "error"
        );
      },
      complete: function () {
        $saveAreaBtn.prop("disabled", false).html(originalButtonText);
      },
    });
  });

  // Handle edit area
  $(document).on("click", ".mobooking-edit-area-btn", function () {
    const areaId = $(this).data("area-id");
    const $areaItem = $(this).closest(".mobooking-area-item");

    const areaData = {
      area_id: areaId,
      area_name: $areaItem.find(".mobooking-area-name").text(),
      area_zipcode: $areaItem.find(".mobooking-area-zipcode").text(),
      country_name: $areaItem.find(".mobooking-area-country").text(),
    };

    // Populate form
    $areaIdField.val(areaData.area_id);
    $areaNameField.val(areaData.area_name);
    $areaZipcodeField.val(areaData.area_zipcode);
    $areaCountryField.val(areaData.country_name);

    // Update form title and button
    $areaFormTitle.find("span").remove();
    $areaFormTitle.append(
      "<span> " + (i18n.edit_area || "Edit Area") + "</span>"
    );

    $saveAreaBtn.html(`
            <svg class="mobooking-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            ${i18n.update_area || "Update Area"}
        `);

    $cancelEditBtn.show();

    // Scroll to form
    $areaForm[0].scrollIntoView({ behavior: "smooth" });
  });

  // Handle cancel edit
  $cancelEditBtn.on("click", function () {
    resetForm();
  });

  // Handle delete area
  $(document).on("click", ".mobooking-delete-area-btn", function () {
    const areaId = $(this).data("area-id");
    const $areaItem = $(this).closest(".mobooking-area-item");
    const areaName = $areaItem.find(".mobooking-area-name").text();

    const confirmMessage =
      (i18n.confirm_delete ||
        "Are you sure you want to delete this service area?") +
      `\n\n"${areaName}"`;

    if (!confirm(confirmMessage)) return;

    const $deleteBtn = $(this);
    const originalButtonText = $deleteBtn.html();

    $deleteBtn.prop("disabled", true).html(`
            <div class="mobooking-spinner mobooking-spinner-sm"></div>
        `);

    $.ajax({
      url: mobooking_areas_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_delete_area",
        nonce: mobooking_areas_params.nonce,
        area_id: areaId,
      },
      success: function (response) {
        if (response.success) {
          $areaItem.fadeOut(300, function () {
            $(this).remove();
            // Check if we need to reload the page if this was the last item
            if ($(".mobooking-area-item").length === 0) {
              fetchAndRenderAreas(Math.max(1, currentFilters.paged - 1));
            }
          });
        } else {
          alert(
            response.data?.message ||
              i18n.error_generic ||
              "An error occurred. Please try again."
          );
          $deleteBtn.prop("disabled", false).html(originalButtonText);
        }
      },
      error: function () {
        alert(i18n.error_generic || "An error occurred. Please try again.");
        $deleteBtn.prop("disabled", false).html(originalButtonText);
      },
    });
  });

  // Initialize on page load
  initializeCountries();
  fetchAndRenderAreas(1);
});
