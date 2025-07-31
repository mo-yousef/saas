/**
 * Enhanced Service Areas Management JavaScript
 * Country-based selection with persistent visual management
 */

(function ($) {
  "use strict";

  // Global state
  let currentCountry = null;
  let selectedCities = new Map();
  let selectedAreas = new Map();
  let coverageFilters = {
    search: "",
    country: "",
    status: "",
  };

  // DOM elements
  const $countrySelector = $("#mobooking-country-selector");
  const $selectCountryBtn = $("#select-country-btn");
  const $countrySelectionCard = $("#country-selection-card");
  const $citiesAreasCard = $("#cities-areas-selection-card");
  const $cancelSelectionBtn = $("#cancel-selection-btn");
  const $selectedCountryName = $("#selected-country-name");
  const $citiesGrid = $("#cities-grid");
  const $areasSelectionSection = $("#areas-selection-section");
  const $selectedCityName = $("#selected-city-name");
  const $areasGrid = $("#areas-grid");
  const $selectionActions = $("#selection-actions");
  const $saveSelectionsBtn = $("#save-selections-btn");
  const $backToCitiesBtn = $("#back-to-cities-btn");
  const $selectionFeedback = $("#selection-feedback");

  // Coverage management elements
  const $coverageSearch = $("#coverage-search");
  const $countryFilter = $("#country-filter");
  const $statusFilter = $("#status-filter");
  const $clearCoverageFiltersBtn = $("#clear-coverage-filters-btn");
  const $coverageLoading = $("#coverage-loading");
  const $serviceCoverageList = $("#service-coverage-list");
  const $noCoverageState = $("#no-coverage-state");
  const $coveragePagination = $("#coverage-pagination");

  // i18n shorthand
  const i18n = window.mobooking_areas_i18n || {};

  /**
   * Initialize the application
   */
  function init() {
    if (typeof mobooking_areas_params === "undefined") {
      console.error("MoBooking Areas: Parameters not loaded");
      return;
    }

    loadCountries();
    loadServiceCoverage();
    bindEvents();
  }

  /**
   * Bind all event handlers
   */
  function bindEvents() {
    // Country selection
    $countrySelector.on("change", handleCountrySelectChange);
    $selectCountryBtn.on("click", handleSelectCountry);
    $cancelSelectionBtn.on("click", handleCancelSelection);

    // Cities and areas selection
    $(document).on("click", ".city-item", handleCitySelection);
    $(document).on("change", ".area-checkbox", handleAreaSelection);
    $saveSelectionsBtn.on("click", handleSaveSelections);
    $backToCitiesBtn.on("click", handleBackToCities);

    // Coverage management
    let searchTimeout;
    $coverageSearch.on("input", function () {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        coverageFilters.search = $(this).val().trim();
        loadServiceCoverage(1);
      }, 500);
    });

    $countryFilter.on("change", function () {
      coverageFilters.country = $(this).val();
      loadServiceCoverage(1);
    });

    $statusFilter.on("change", function () {
      coverageFilters.status = $(this).val();
      loadServiceCoverage(1);
    });

    $clearCoverageFiltersBtn.on("click", clearCoverageFilters);

    // Coverage actions (delegated)
    $(document).on("click", ".toggle-area-btn", handleToggleArea);
    $(document).on("click", ".remove-country-btn", handleRemoveCountry);
    $(document).on("click", ".page-numbers", handlePaginationClick);
  }

  /**
   * Load available countries
   */
  function loadCountries() {
    $.ajax({
      url: mobooking_areas_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_get_countries",
        nonce: mobooking_areas_params.nonce,
      },
      success: function (response) {
        if (response.success && response.data?.countries) {
          populateCountryDropdown(response.data.countries);
        } else {
          showFeedback(
            $selectionFeedback,
            i18n.error || "Error loading countries",
            "error"
          );
        }
      },
      error: function () {
        showFeedback(
          $selectionFeedback,
          i18n.error || "Error loading countries",
          "error"
        );
      },
    });
  }

  /**
   * Populate country dropdown
   */
  function populateCountryDropdown(countries) {
    $countrySelector.empty().append(
      $("<option>", {
        value: "",
        text: i18n.choose_country || "Choose a country to add...",
      })
    );

    countries.forEach(function (country) {
      $countrySelector.append(
        $("<option>", { value: country.code, text: country.name })
      );
    });

    // Also populate filter dropdown
    $countryFilter.empty().append(
      $("<option>", {
        value: "",
        text: i18n.all_countries || "All Countries",
      })
    );

    countries.forEach(function (country) {
      $countryFilter.append(
        $("<option>", { value: country.name, text: country.name })
      );
    });
  }

  /**
   * Handle country selector change
   */
  function handleCountrySelectChange() {
    const selected = $(this).val();
    $selectCountryBtn.prop("disabled", !selected);
  }

  /**
   * Handle select country button click
   */
  function handleSelectCountry() {
    const countryCode = $countrySelector.val();
    const countryName = $countrySelector.find("option:selected").text();

    if (!countryCode) return;

    currentCountry = { code: countryCode, name: countryName };
    selectedCities.clear();
    selectedAreas.clear();

    // Update UI
    $selectedCountryName.text(countryName);
    $countrySelectionCard.hide();
    $citiesAreasCard.show();
    $areasSelectionSection.hide();
    $selectionActions.hide();

    // Load cities for selected country
    loadCitiesForCountry(countryCode);
  }

  /**
   * Handle cancel selection
   */
  function handleCancelSelection() {
    currentCountry = null;
    selectedCities.clear();
    selectedAreas.clear();

    $countrySelectionCard.show();
    $citiesAreasCard.hide();
    $countrySelector.val("");
    $selectCountryBtn.prop("disabled", true);
  }

  /**
   * Load cities for selected country
   */
  function loadCitiesForCountry(countryCode) {
    $citiesGrid.html(
      '<div class="loading-state"><div class="mobooking-spinner"></div><p>' +
        (i18n.loading || "Loading...") +
        "</p></div>"
    );

    $.ajax({
      url: mobooking_areas_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_get_cities_for_country",
        nonce: mobooking_areas_params.nonce,
        country_code: countryCode,
      },
      success: function (response) {
        if (response.success && response.data?.cities) {
          displayCities(response.data.cities);
        } else {
          $citiesGrid.html(
            '<div class="empty-state"><p>' +
              (i18n.no_cities_available || "No cities available") +
              "</p></div>"
          );
        }
      },
      error: function () {
        $citiesGrid.html(
          '<div class="error-state"><p>' +
            (i18n.error || "Error loading cities") +
            "</p></div>"
        );
      },
    });
  }

  /**
   * Display cities grid
   */
  function displayCities(cities) {
    if (!cities.length) {
      $citiesGrid.html(
        '<div class="empty-state"><p>' +
          (i18n.no_cities_available || "No cities available") +
          "</p></div>"
      );
      return;
    }

    let html = '<div class="cities-grid-content">';
    cities.forEach(function (city) {
      const isSelected = selectedCities.has(city.code);
      html += `
                <div class="city-item ${
                  isSelected ? "selected" : ""
                }" data-city-code="${escapeHtml(
        city.code
      )}" data-city-name="${escapeHtml(city.name)}">
                    <div class="city-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div class="city-name">${escapeHtml(city.name)}</div>
                    <div class="city-status">
                        ${
                          isSelected
                            ? '<span class="status-badge selected">Selected</span>'
                            : '<span class="status-badge">Click to select</span>'
                        }
                    </div>
                </div>
            `;
    });
    html += "</div>";

    $citiesGrid.html(html);
    updateSelectionActions();
  }

  /**
   * Handle city selection
   */
  function handleCitySelection() {
    const $city = $(this);
    const cityCode = $city.data("city-code");
    const cityName = $city.data("city-name");

    if ($city.hasClass("selected")) {
      // Deselect city
      selectedCities.delete(cityCode);
      $city.removeClass("selected");
      $city
        .find(".status-badge")
        .removeClass("selected")
        .text("Click to select");
    } else {
      // Select city and load its areas
      selectedCities.set(cityCode, { name: cityName, areas: new Map() });
      $city.addClass("selected");
      $city.find(".status-badge").addClass("selected").text("Selected");

      // Load areas for this city
      loadAreasForCity(currentCountry.code, cityCode, cityName);
    }

    updateSelectionActions();
  }

  /**
   * Load areas for selected city
   */
  function loadAreasForCity(countryCode, cityCode, cityName) {
    $selectedCityName.text(cityName);
    $areasSelectionSection.show();
    $backToCitiesBtn.show();

    $areasGrid.html(
      '<div class="loading-state"><div class="mobooking-spinner"></div><p>' +
        (i18n.loading || "Loading...") +
        "</p></div>"
    );

    $.ajax({
      url: mobooking_areas_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_get_areas_for_city",
        nonce: mobooking_areas_params.nonce,
        country_code: countryCode,
        city_code: cityCode,
      },
      success: function (response) {
        if (response.success && response.data?.areas) {
          displayAreas(response.data.areas, cityCode);
        } else {
          $areasGrid.html(
            '<div class="empty-state"><p>' +
              (i18n.no_areas_available || "No areas available") +
              "</p></div>"
          );
        }
      },
      error: function () {
        $areasGrid.html(
          '<div class="error-state"><p>' +
            (i18n.error || "Error loading areas") +
            "</p></div>"
        );
      },
    });
  }

  /**
   * Display areas grid
   */
  function displayAreas(areas, cityCode) {
    if (!areas.length) {
      $areasGrid.html(
        '<div class="empty-state"><p>' +
          (i18n.no_areas_available || "No areas available") +
          "</p></div>"
      );
      return;
    }

    let html = `
            <div class="areas-header">
                <div class="selection-controls">
                    <button type="button" class="select-all-areas-btn mobooking-btn-link" data-city="${cityCode}">Select All</button>
                    <button type="button" class="deselect-all-areas-btn mobooking-btn-link" data-city="${cityCode}" style="display: none;">Deselect All</button>
                </div>
            </div>
            <div class="areas-grid-content">
        `;

    areas.forEach(function (area) {
      const areaId = `${cityCode}-${area.code}`;
      const isSelected = selectedAreas.has(areaId);

      html += `
                <label class="area-item ${isSelected ? "selected" : ""}">
                    <input type="checkbox" 
                           class="area-checkbox" 
                           value="${areaId}"
                           data-city-code="${cityCode}"
                           data-area-name="${escapeHtml(area.name)}"
                           data-zip-code="${escapeHtml(area.zip_code)}"
                           ${isSelected ? "checked" : ""}>
                    <div class="area-content">
                        <div class="area-name">${escapeHtml(area.name)}</div>
                        <div class="area-zip">${escapeHtml(area.zip_code)}</div>
                    </div>
                </label>
            `;
    });

    html += "</div>";
    $areasGrid.html(html);

    // Bind area selection controls
    $(document).on("click", ".select-all-areas-btn", function () {
      const cityCode = $(this).data("city");
      $(`.area-checkbox[data-city-code="${cityCode}"]:not(:checked)`)
        .prop("checked", true)
        .trigger("change");
    });

    $(document).on("click", ".deselect-all-areas-btn", function () {
      const cityCode = $(this).data("city");
      $(`.area-checkbox[data-city-code="${cityCode}"]:checked`)
        .prop("checked", false)
        .trigger("change");
    });

    updateAreaSelectionControls(cityCode);
  }

  /**
   * Handle area selection
   */
  function handleAreaSelection() {
    const $checkbox = $(this);
    const areaId = $checkbox.val();
    const cityCode = $checkbox.data("city-code");
    const areaName = $checkbox.data("area-name");
    const zipCode = $checkbox.data("zip-code");

    if ($checkbox.is(":checked")) {
      selectedAreas.set(areaId, {
        city_code: cityCode,
        area_name: areaName,
        zip_code: zipCode,
        country_code: currentCountry.code,
        country_name: currentCountry.name,
      });
      $checkbox.closest(".area-item").addClass("selected");
    } else {
      selectedAreas.delete(areaId);
      $checkbox.closest(".area-item").removeClass("selected");
    }

    updateAreaSelectionControls(cityCode);
    updateSelectionActions();
  }

  /**
   * Update area selection controls
   */
  function updateAreaSelectionControls(cityCode) {
    const $selectAll = $(`.select-all-areas-btn[data-city="${cityCode}"]`);
    const $deselectAll = $(`.deselect-all-areas-btn[data-city="${cityCode}"]`);
    const totalAreas = $(`.area-checkbox[data-city-code="${cityCode}"]`).length;
    const selectedAreasCount = $(
      `.area-checkbox[data-city-code="${cityCode}"]:checked`
    ).length;

    if (selectedAreasCount === 0) {
      $selectAll.show();
      $deselectAll.hide();
    } else if (selectedAreasCount === totalAreas) {
      $selectAll.hide();
      $deselectAll.show();
    } else {
      $selectAll.show();
      $deselectAll.show();
    }
  }

  /**
   * Handle back to cities
   */
  function handleBackToCities() {
    $areasSelectionSection.hide();
    $backToCitiesBtn.hide();
    updateSelectionActions();
  }

  /**
   * Update selection actions visibility and content
   */
  function updateSelectionActions() {
    const hasSelections = selectedCities.size > 0 || selectedAreas.size > 0;

    if (hasSelections) {
      $selectionActions.show();

      let buttonText = i18n.save_selections || "Save Selected Areas";
      if (selectedAreas.size > 0) {
        buttonText += ` (${selectedAreas.size})`;
      } else if (selectedCities.size > 0) {
        buttonText =
          (i18n.save_selections || "Save Selected Areas") +
          ` (${selectedCities.size} cities)`;
      }

      $saveSelectionsBtn.find("svg").siblings().remove();
      $saveSelectionsBtn.append(` ${buttonText}`);
    } else {
      $selectionActions.hide();
    }
  }

  /**
   * Handle save selections
   */
  function handleSaveSelections() {
    if (selectedAreas.size === 0) {
      showFeedback(
        $selectionFeedback,
        "Please select at least one area to save.",
        "error"
      );
      return;
    }

    const areasData = Array.from(selectedAreas.values());
    const originalText = $saveSelectionsBtn.html();

    $saveSelectionsBtn.prop("disabled", true).html(`
            <div class="mobooking-spinner mobooking-spinner-sm"></div>
            ${i18n.saving || "Saving..."}
        `);

    $.ajax({
      url: mobooking_areas_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_add_bulk_areas",
        nonce: mobooking_areas_params.nonce,
        areas_data: areasData,
      },
      success: function (response) {
        if (response.success) {
          const message = (
            i18n.country_added_success ||
            "Service areas added successfully for {{country}}!"
          ).replace("{{country}}", currentCountry.name);
          showFeedback($selectionFeedback, message, "success");

          // Reset the selection
          setTimeout(() => {
            handleCancelSelection();
            loadServiceCoverage(1); // Refresh coverage display
          }, 2000);
        } else {
          showFeedback(
            $selectionFeedback,
            response.data?.message || "Error saving selections.",
            "error"
          );
        }
      },
      error: function () {
        showFeedback($selectionFeedback, "Error saving selections.", "error");
      },
      complete: function () {
        $saveSelectionsBtn.prop("disabled", false).html(originalText);
      },
    });
  }

  /**
   * Load service coverage
   */
  function loadServiceCoverage(page = 1) {
    showCoverageLoading(true);

    $.ajax({
      url: mobooking_areas_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_get_service_coverage",
        nonce: mobooking_areas_params.nonce,
        paged: page,
        limit: 20,
        search: coverageFilters.search,
        country: coverageFilters.country,
        status: coverageFilters.status,
      },
      success: function (response) {
        if (response.success && response.data) {
          renderServiceCoverage(response.data);
          renderCoveragePagination(response.data);
        } else {
          showNoCoverageState();
        }
      },
      error: function () {
        showCoverageError();
      },
      complete: function () {
        showCoverageLoading(false);
      },
    });
  }

  /**
   * Render service coverage
   */
  function renderServiceCoverage(data) {
    if (!data.coverage || data.coverage.length === 0) {
      showNoCoverageState();
      return;
    }

    $noCoverageState.hide();

    // Group coverage by country
    const coverageByCountry = {};
    data.coverage.forEach(function (area) {
      const countryName =
        getCountryNameFromCode(area.country_code) || area.country_code;
      if (!coverageByCountry[countryName]) {
        coverageByCountry[countryName] = {
          country_code: area.country_code,
          areas: [],
        };
      }
      coverageByCountry[countryName].areas.push(area);
    });

    let html = "";
    Object.keys(coverageByCountry)
      .sort()
      .forEach(function (countryName) {
        const countryData = coverageByCountry[countryName];
        const activeAreas = countryData.areas.filter(
          (a) => a.status === "active" || !a.status
        );
        const inactiveAreas = countryData.areas.filter(
          (a) => a.status === "inactive"
        );

        html += `
                <div class="coverage-country" data-country="${escapeHtml(
                  countryData.country_code
                )}">
                    <div class="coverage-country-header">
                        <div class="country-info">
                            <div class="country-flag">${escapeHtml(
                              countryData.country_code
                            )}</div>
                            <div class="country-details">
                                <h4 class="country-name">${escapeHtml(
                                  countryName
                                )}</h4>
                                <div class="country-stats">
                                    <span class="stat active">${
                                      activeAreas.length
                                    } active</span>
                                    ${
                                      inactiveAreas.length > 0
                                        ? `<span class="stat inactive">${inactiveAreas.length} inactive</span>`
                                        : ""
                                    }
                                </div>
                            </div>
                        </div>
                        <div class="country-actions">
                            <button type="button" class="toggle-country-btn mobooking-btn-link" data-country="${escapeHtml(
                              countryData.country_code
                            )}">
                                <svg class="expand-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                                View Areas
                            </button>
                            <button type="button" class="remove-country-btn mobooking-btn-link mobooking-btn-danger" 
                                    data-country-code="${escapeHtml(
                                      countryData.country_code
                                    )}" 
                                    data-country-name="${escapeHtml(
                                      countryName
                                    )}">
                                Remove Country
                            </button>
                        </div>
                    </div>
                    <div class="coverage-areas" style="display: none;">
                        <div class="areas-list">
            `;

        // Group areas by city
        const areasByCity = {};
        countryData.areas.forEach(function (area) {
          if (!areasByCity[area.area_name]) {
            areasByCity[area.area_name] = [];
          }
          areasByCity[area.area_name].push(area);
        });

        Object.keys(areasByCity)
          .sort()
          .forEach(function (cityName) {
            const cityAreas = areasByCity[cityName];
            html += `
                    <div class="city-group">
                        <div class="city-header">
                            <h5 class="city-name">${escapeHtml(cityName)}</h5>
                            <span class="city-count">${
                              cityAreas.length
                            } areas</span>
                        </div>
                        <div class="city-areas">
                `;

            cityAreas.forEach(function (area) {
              const isActive = area.status !== "inactive";
              html += `
                        <div class="area-row ${
                          isActive ? "active" : "inactive"
                        }" data-area-id="${area.area_id}">
                            <div class="area-info">
                                <div class="area-name">${escapeHtml(
                                  area.area_name
                                )}</div>
                                <div class="area-zip">${escapeHtml(
                                  area.area_value
                                )}</div>
                            </div>
                            <div class="area-status">
                                <span class="status-indicator ${
                                  isActive ? "active" : "inactive"
                                }">
                                    ${isActive ? "Active" : "Inactive"}
                                </span>
                            </div>
                            <div class="area-actions">
                                <button type="button" class="toggle-area-btn mobooking-btn-link" 
                                        data-area-id="${area.area_id}" 
                                        data-current-status="${
                                          isActive ? "active" : "inactive"
                                        }">
                                    ${isActive ? "Disable" : "Enable"}
                                </button>
                            </div>
                        </div>
                    `;
            });

            html += `
                        </div>
                    </div>
                `;
          });

        html += `
                        </div>
                    </div>
                </div>
            `;
      });

    $serviceCoverageList.html(html);

    // Bind toggle country visibility
    $(document).on("click", ".toggle-country-btn", function () {
      const $btn = $(this);
      const $areas = $btn.closest(".coverage-country").find(".coverage-areas");
      const $icon = $btn.find(".expand-icon");

      if ($areas.is(":visible")) {
        $areas.slideUp();
        $icon.css("transform", "rotate(0deg)");
        $btn.find("svg").siblings().text("View Areas");
      } else {
        $areas.slideDown();
        $icon.css("transform", "rotate(180deg)");
        $btn.find("svg").siblings().text("Hide Areas");
      }
    });
  }

  /**
   * Handle toggle area status
   */
  function handleToggleArea() {
    const $btn = $(this);
    const areaId = $btn.data("area-id");
    const currentStatus = $btn.data("current-status");
    const newStatus = currentStatus === "active" ? "inactive" : "active";
    const $row = $btn.closest(".area-row");

    $btn
      .prop("disabled", true)
      .html('<div class="mobooking-spinner mobooking-spinner-sm"></div>');

    $.ajax({
      url: mobooking_areas_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_toggle_area_status",
        nonce: mobooking_areas_params.nonce,
        area_id: areaId,
        status: newStatus,
      },
      success: function (response) {
        if (response.success) {
          // Update UI
          $row.removeClass("active inactive").addClass(newStatus);
          $row
            .find(".status-indicator")
            .removeClass("active inactive")
            .addClass(newStatus)
            .text(newStatus === "active" ? "Active" : "Inactive");
          $btn
            .data("current-status", newStatus)
            .text(newStatus === "active" ? "Disable" : "Enable");

          // Update country stats
          updateCountryStats($row.closest(".coverage-country"));
        } else {
          showFeedback(
            $selectionFeedback,
            response.data?.message || "Error updating area status.",
            "error"
          );
        }
      },
      error: function () {
        showFeedback(
          $selectionFeedback,
          "Error updating area status.",
          "error"
        );
      },
      complete: function () {
        $btn.prop("disabled", false);
      },
    });
  }

  /**
   * Handle remove country
   */
  function handleRemoveCountry() {
    const $btn = $(this);
    const countryCode = $btn.data("country-code");
    const countryName = $btn.data("country-name");

    const confirmMessage = (
      i18n.confirm_remove_country ||
      "Are you sure you want to remove all service areas for {{country}}?"
    ).replace("{{country}}", countryName);

    if (!confirm(confirmMessage)) {
      return;
    }

    $btn
      .prop("disabled", true)
      .html('<div class="mobooking-spinner mobooking-spinner-sm"></div>');

    $.ajax({
      url: mobooking_areas_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_remove_country_coverage",
        nonce: mobooking_areas_params.nonce,
        country_code: countryCode,
      },
      success: function (response) {
        if (response.success) {
          $btn.closest(".coverage-country").fadeOut(300, function () {
            $(this).remove();
            // Check if any coverage remains
            if ($serviceCoverageList.children().length === 0) {
              showNoCoverageState();
            }
          });
        } else {
          showFeedback(
            $selectionFeedback,
            response.data?.message || "Error removing country coverage.",
            "error"
          );
          $btn.prop("disabled", false).text("Remove Country");
        }
      },
      error: function () {
        showFeedback(
          $selectionFeedback,
          "Error removing country coverage.",
          "error"
        );
        $btn.prop("disabled", false).text("Remove Country");
      },
    });
  }

  /**
   * Update country statistics
   */
  function updateCountryStats($countryElement) {
    const activeAreas = $countryElement.find(".area-row.active").length;
    const inactiveAreas = $countryElement.find(".area-row.inactive").length;

    let statsHtml = `<span class="stat active">${activeAreas} active</span>`;
    if (inactiveAreas > 0) {
      statsHtml += `<span class="stat inactive">${inactiveAreas} inactive</span>`;
    }

    $countryElement.find(".country-stats").html(statsHtml);
  }

  /**
   * Render coverage pagination
   */
  function renderCoveragePagination(data) {
    if (data.total_count <= data.per_page) {
      $coveragePagination.empty();
      return;
    }

    const totalPages = Math.ceil(data.total_count / data.per_page);
    const currentPage = data.current_page;
    let html = '<div class="mobooking-pagination"><ul class="page-numbers">';

    // Previous button
    if (currentPage > 1) {
      html += `<li><a href="#" class="page-numbers prev" data-page="${
        currentPage - 1
      }">← ${i18n.previous || "Previous"}</a></li>`;
    }

    // Page numbers
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);

    if (startPage > 1) {
      html += '<li><a href="#" class="page-numbers" data-page="1">1</a></li>';
      if (startPage > 2) {
        html += '<li><span class="page-numbers dots">…</span></li>';
      }
    }

    for (let i = startPage; i <= endPage; i++) {
      html += `<li><a href="#" class="page-numbers ${
        i === currentPage ? "current" : ""
      }" data-page="${i}">${i}</a></li>`;
    }

    if (endPage < totalPages) {
      if (endPage < totalPages - 1) {
        html += '<li><span class="page-numbers dots">…</span></li>';
      }
      html += `<li><a href="#" class="page-numbers" data-page="${totalPages}">${totalPages}</a></li>`;
    }

    // Next button
    if (currentPage < totalPages) {
      html += `<li><a href="#" class="page-numbers next" data-page="${
        currentPage + 1
      }">${i18n.next || "Next"} →</a></li>`;
    }

    html += "</ul></div>";
    $coveragePagination.html(html);
  }

  /**
   * Handle pagination clicks
   */
  function handlePaginationClick(e) {
    e.preventDefault();
    if ($(this).hasClass("current") || $(this).hasClass("dots")) return;

    const page = parseInt($(this).data("page"));
    if (page) {
      loadServiceCoverage(page);
    }
  }

  /**
   * Clear coverage filters
   */
  function clearCoverageFilters() {
    $coverageSearch.val("");
    $countryFilter.val("");
    $statusFilter.val("");
    coverageFilters.search = "";
    coverageFilters.country = "";
    coverageFilters.status = "";
    loadServiceCoverage(1);
  }

  /**
   * Show/hide coverage loading state
   */
  function showCoverageLoading(show) {
    if (show) {
      $coverageLoading.show();
      $serviceCoverageList.hide();
      $noCoverageState.hide();
    } else {
      $coverageLoading.hide();
      $serviceCoverageList.show();
    }
  }

  /**
   * Show no coverage state
   */
  function showNoCoverageState() {
    $serviceCoverageList.hide();
    $coveragePagination.empty();
    $noCoverageState.show();
  }

  /**
   * Show coverage error
   */
  function showCoverageError() {
    $serviceCoverageList.html(
      '<div class="error-state"><p>Error loading service coverage. Please try again.</p></div>'
    );
    $noCoverageState.hide();
  }

  /**
   * Utility functions
   */
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

  function escapeHtml(text) {
    const map = {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#039;",
    };
    return text.replace(/[&<>"']/g, function (m) {
      return map[m];
    });
  }

  function getCountryNameFromCode(countryCode) {
    const $option = $countryFilter
      .find(`option`)
      .filter(function () {
        return $(this).text().toLowerCase().includes(countryCode.toLowerCase());
      })
      .first();
    return $option.length ? $option.text() : countryCode;
  }

  // Initialize when document is ready
  $(document).ready(init);
})(jQuery);

/**
 * Debug Script for Enhanced Areas
 * Add this to your browser console to check for errors
 */

console.log("🔍 Starting Enhanced Areas Debug...");

// Check if all required elements exist
const requiredElements = [
  "#mobooking-country-selector",
  "#select-country-btn",
  "#country-selection-card",
  "#cities-areas-selection-card",
  "#cities-grid",
  "#areas-grid",
  "#service-coverage-list",
];

console.log("📋 Checking DOM elements...");
requiredElements.forEach((selector) => {
  const element = document.querySelector(selector);
  console.log(
    `${element ? "✅" : "❌"} ${selector}: ${element ? "Found" : "NOT FOUND"}`
  );
});

// Check if parameters are available
console.log("🔍 Checking parameters...");
if (typeof mobooking_areas_params !== "undefined") {
  console.log("✅ mobooking_areas_params available:", mobooking_areas_params);
} else {
  console.error("❌ mobooking_areas_params not available!");
}

if (typeof mobooking_areas_i18n !== "undefined") {
  console.log("✅ mobooking_areas_i18n available");
} else {
  console.error("❌ mobooking_areas_i18n not available!");
}

// Test basic AJAX
console.log("🔍 Testing basic AJAX connection...");
if (
  typeof jQuery !== "undefined" &&
  typeof mobooking_areas_params !== "undefined"
) {
  jQuery.ajax({
    url: mobooking_areas_params.ajax_url,
    type: "POST",
    data: {
      action: "mobooking_get_countries",
      nonce: mobooking_areas_params.nonce,
    },
    success: function (response) {
      console.log("✅ Countries AJAX test successful:", response);
    },
    error: function (xhr, status, error) {
      console.error("❌ Countries AJAX test failed:");
      console.error("Status:", status);
      console.error("Error:", error);
      console.error("Response Text:", xhr.responseText);
    },
  });

  // Test service coverage endpoint
  jQuery.ajax({
    url: mobooking_areas_params.ajax_url,
    type: "POST",
    data: {
      action: "mobooking_get_service_coverage",
      nonce: mobooking_areas_params.nonce,
    },
    success: function (response) {
      console.log("✅ Service Coverage AJAX test successful:", response);
    },
    error: function (xhr, status, error) {
      console.error("❌ Service Coverage AJAX test failed:");
      console.error("Status:", status);
      console.error("Error:", error);
      console.error("Response Text:", xhr.responseText);
    },
  });
} else {
  console.error("❌ Cannot test AJAX - jQuery or parameters missing");
}

// Check for JavaScript errors
window.addEventListener("error", function (e) {
  console.error("🚨 JavaScript Error Detected:");
  console.error("Message:", e.message);
  console.error("Source:", e.filename);
  console.error("Line:", e.lineno);
  console.error("Column:", e.colno);
  console.error("Error Object:", e.error);
});

console.log("🔍 Debug script loaded. Check above for any issues.");
