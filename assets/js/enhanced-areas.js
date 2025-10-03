/**
 * Enhanced Service Areas Management JavaScript
 * Multi-country support with modal-based city and area management
 */

(function ($) {
  "use strict";

  // Global state
  let currentCountry = null;
  let currentCity = null;
  let countriesData = [];
  let citiesData = [];
  let citiesWithCoverage = [];

  // DOM elements
  const $countriesGridContainer = $("#countries-grid-container");
  const $citiesSelectionCard = $("#cities-selection-card");
  const $citiesGridContainer = $("#cities-grid-container");
  const $coverageList = $("#service-coverage-list");
  const $countryFilter = $("#country-filter");
  const $cityFilter = $("#city-filter");
  const $statusFilter = $("#status-filter");
  const $coverageSearch = $("#coverage-search");
  const $clearFiltersBtn = $("#clear-coverage-filters-btn");
  const $changeCountryBtn = $("#change-country-btn");
  
  // New bulk action elements
  const $bulkActionsBar = $("#bulk-actions-bar");
  const $selectedCount = $("#selected-count");
  const $selectAllCoverage = $("#select-all-coverage");
  const $bulkEnableBtn = $("#bulk-enable-btn");
  const $bulkDisableBtn = $("#bulk-disable-btn");
  const $bulkRemoveBtn = $("#bulk-remove-btn");
  const $bulkCancelBtn = $("#bulk-cancel-btn");
  const $coverageTableHeader = $("#coverage-table-header");
  const $totalCities = $("#total-cities");
  const $activeCities = $("#active-cities");

  // Dialog instance
  let areaDialog = null;

  // i18n shorthand
  const i18n = nordbooking_areas_params.i18n || {};

  /**
   * Initialize the application
   */
  function init() {
    loadCountries();
    loadServiceCoverage();
    bindEvents();
    loadSelectedCountry(); // Load previously selected country
  }

  /**
   * Bind all event handlers
   */
  function bindEvents() {
    // Country selection
    $countriesGridContainer.on("click", ".country-card", handleCountryClick);
    $changeCountryBtn.on("click", handleChangeCountry);

    // City selection
    $citiesGridContainer.on("click", ".city-card", handleCityClick);

    // Coverage management
    $coverageSearch.on("input", debounce(loadServiceCoverage, 500));
    $countryFilter.on("change", loadServiceCoverage);
    $cityFilter.on("change", loadServiceCoverage);
    $statusFilter.on("change", loadServiceCoverage);
    $clearFiltersBtn.on("click", clearCoverageFilters);
    $coverageList.on("click", ".toggle-city-btn", handleToggleCity);
    $coverageList.on("click", ".remove-city-btn", handleRemoveCity);
    
    // Bulk selection
    $selectAllCoverage.on("change", handleSelectAll);
    $coverageList.on("change", ".coverage-checkbox", handleCoverageSelection);
    
    // Bulk actions
    $bulkEnableBtn.on("click", () => handleBulkAction("enable"));
    $bulkDisableBtn.on("click", () => handleBulkAction("disable"));
    $bulkRemoveBtn.on("click", () => handleBulkAction("remove"));
    $bulkCancelBtn.on("click", clearBulkSelection);
  }

  /**
   * Debounce function for search input
   */
  function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }

  /**
   * Load previously selected country
   */
  function loadSelectedCountry() {
    $.ajax({
      url: nordbooking_areas_params.ajax_url,
      type: "POST",
      data: {
        action: "nordbooking_get_selected_country",
        nonce: nordbooking_areas_params.nonce,
      },
      success: function(response) {
        if (response.success && response.data?.country_code) {
          const countryCode = response.data.country_code;
          const country = countriesData.find(c => c.code === countryCode);
          if (country) {
            selectCountry(countryCode, country.name, false); // Don't save again
          }
        }
      },
      error: function() {
        console.log("No previously selected country found");
      }
    });
  }

  /**
   * Save selected country preference
   */
  function saveSelectedCountry(countryCode) {
    $.ajax({
      url: nordbooking_areas_params.ajax_url,
      type: "POST",
      data: {
        action: "nordbooking_save_selected_country",
        nonce: nordbooking_areas_params.nonce,
        country_code: countryCode,
      },
      success: function(response) {
        console.log("Country preference saved");
      },
      error: function() {
        console.error("Failed to save country preference");
      }
    });
  }

  /**
   * Load available countries
   */
  function loadCountries() {
    $countriesGridContainer.html(
      `<div class="NORDBOOKING-loading-state"><div class="NORDBOOKING-spinner"></div><p>${i18n.loading_countries}</p></div>`
    );

    $.ajax({
      url: nordbooking_areas_params.ajax_url,
      type: "POST",
      data: {
        action: "nordbooking_get_countries",
        nonce: nordbooking_areas_params.nonce,
      },
      success: function(response) {
        if (response.success && response.data?.countries) {
          countriesData = response.data.countries;
          displayCountries(countriesData);
          populateCountryFilter(countriesData);
          // Load selected country after countries are loaded
          setTimeout(loadSelectedCountry, 100);
        } else {
          $countriesGridContainer.html(
            `<div class="NORDBOOKING-empty-state"><p>${i18n.no_countries_available}</p></div>`
          );
        }
      },
      error: function() {
        $countriesGridContainer.html(
          `<div class="NORDBOOKING-error-state"><p>${i18n.error}</p></div>`
        );
      }
    });
  }

  /**
   * Display countries grid (compact design)
   */
  function displayCountries(countries) {
    if (!countries.length) {
      $countriesGridContainer.html(
        `<div class="NORDBOOKING-empty-state"><p>${i18n.no_countries_available}</p></div>`
      );
      return;
    }

    let html = '<div class="countries-grid compact">';
    countries.forEach(function (country) {
      const countryFlag = getCountryFlag(country.code);
      html += `
        <div class="country-card compact" data-country-code="${escapeHtml(country.code)}" data-country-name="${escapeHtml(country.name)}">
          <span class="country-flag">${countryFlag}</span>
          <span class="country-name">${escapeHtml(country.name)}</span>
        </div>
      `;
    });
    html += "</div>";
    $countriesGridContainer.html(html);
  }

  /**
   * Populate the country filter dropdown
   */
  function populateCountryFilter(countries) {
    $countryFilter
      .empty()
      .append(
        $("<option>", { value: "", text: i18n.all_countries || "All Countries" })
      );
    countries.forEach(function (country) {
      $countryFilter.append($("<option>", { value: country.code, text: country.name }));
    });
  }

  /**
   * Handle country selection
   */
  function handleCountryClick() {
    const $countryCard = $(this);
    const countryCode = $countryCard.data("country-code");
    const countryName = $countryCard.data("country-name");
    
    // Check if user has existing coverage and warn about country change
    if (currentCountry && currentCountry.code !== countryCode) {
      if (!confirm(i18n.country_change_warning)) {
        return;
      }
    }
    
    selectCountry(countryCode, countryName);
  }

  /**
   * Handle change country button click
   */
  function handleChangeCountry() {
    if (currentCountry && !confirm(i18n.country_change_warning)) {
      return;
    }
    
    // Remove all areas for the current country before changing
    if (currentCountry) {
      removeAllAreasForCountry(currentCountry.code);
    }
    
    $citiesSelectionCard.hide();
    $countriesGridContainer.parent().show();
    currentCountry = null;
    nordbooking_areas_params.country_code = '';
    
    // Clear saved country preference
    saveSelectedCountry('');
  }

  /**
   * Select a country and load its cities
   */
  function selectCountry(countryCode, countryName, shouldSave = true) {
    currentCountry = { code: countryCode, name: countryName };
    nordbooking_areas_params.country_code = countryCode;
    
    // Save country preference
    if (shouldSave) {
      saveSelectedCountry(countryCode);
    }
    
    // Update UI
    $("#cities-card-title").text(i18n.select_cities_in.replace('%s', countryName));
    $("#cities-card-description").text(`Click on a city to manage its service areas in ${countryName}. Areas can be enabled or disabled individually.`);
    
    // Hide countries, show cities
    $countriesGridContainer.parent().hide();
    $citiesSelectionCard.show();
    
    // Load cities for selected country
    loadCities();
  }

  /**
   * Load available cities for selected country and their coverage status
   */
  function loadCities() {
    if (!currentCountry) return;
    
    $citiesGridContainer.html(
      `<div class="NORDBOOKING-loading-state"><div class="NORDBOOKING-spinner"></div><p>${i18n.loading_cities}</p></div>`
    );

    // Load cities and coverage data simultaneously
    $.when(
      getCitiesForCountry(),
      getCitiesCoverage()
    ).done(function(citiesResponse, coverageResponse) {
      if (citiesResponse[0].success && citiesResponse[0].data?.cities) {
        citiesData = citiesResponse[0].data.cities;
        citiesWithCoverage = coverageResponse[0].success ? (coverageResponse[0].data?.cities || []) : [];
        displayCities(citiesData);
        populateCityFilter(citiesData);
      } else {
        $citiesGridContainer.html(
          `<div class="NORDBOOKING-empty-state"><p>${i18n.no_cities_available}</p></div>`
        );
      }
    }).fail(function() {
      $citiesGridContainer.html(
        `<div class="NORDBOOKING-error-state"><p>${i18n.error}</p></div>`
      );
    });
  }

  /**
   * Get cities for country
   */
  function getCitiesForCountry() {
    return $.ajax({
      url: nordbooking_areas_params.ajax_url,
      type: "POST",
      data: {
        action: "nordbooking_get_cities_for_country",
        nonce: nordbooking_areas_params.nonce,
        country_code: nordbooking_areas_params.country_code,
      },
    });
  }

  /**
   * Get cities with coverage data
   */
  function getCitiesCoverage() {
    return $.ajax({
      url: nordbooking_areas_params.ajax_url,
      type: "POST",
      data: {
        action: "nordbooking_get_service_coverage_grouped",
        nonce: nordbooking_areas_params.nonce,
        filters: {
          country_code: nordbooking_areas_params.country_code,
          groupby: "city",
        },
      },
    });
  }

  /**
   * Display cities grid with coverage status
   */
  function displayCities(cities) {
    if (!cities.length) {
      $citiesGridContainer.html(
        `<div class="NORDBOOKING-empty-state"><p>${i18n.no_cities_available}</p></div>`
      );
      return;
    }

    let html = '<div class="cities-grid">';
    cities.forEach(function (city) {
      // Check if city has coverage
      const cityWithCoverage = citiesWithCoverage.find(c => c.city_code === city.code);
      const hasAreas = cityWithCoverage && cityWithCoverage.area_count > 0;
      const isActive = cityWithCoverage && cityWithCoverage.status === 'active';
      
      const cityCardClass = hasAreas ? (isActive ? 'city-card has-areas active' : 'city-card has-areas inactive') : 'city-card';
      const actionText = hasAreas ? 
        (isActive ? `View Areas (${cityWithCoverage.area_count})` : `Manage Areas (${cityWithCoverage.area_count} inactive)`) : 
        "Add Areas";
      
      html += `
                <div class="${cityCardClass}" data-city-code="${escapeHtml(
                  city.code
                )}" data-city-name="${escapeHtml(city.name)}" data-has-areas="${hasAreas}" data-area-count="${cityWithCoverage ? cityWithCoverage.area_count : 0}">
                    <span class="city-name">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-map-pin">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        ${escapeHtml(city.name)}
                        ${hasAreas ? `<span class="city-status-badge ${isActive ? 'active' : 'inactive'}">${isActive ? 'Active' : 'Inactive'}</span>` : ''}
                    </span>
                    <span class="city-action-link">${actionText} &rarr;</span>
                </div>
            `;
    });
    html += "</div>";
    $citiesGridContainer.html(html);
  }

  /**
   * Populate the city filter dropdown
   */
  function populateCityFilter(cities) {
    $cityFilter
      .empty()
      .append(
        $("<option>", { value: "", text: i18n.all_cities || "All Cities" })
      );
    cities.forEach(function (city) {
      $cityFilter.append($("<option>", { value: city.name, text: city.name }));
    });
  }

  /**
   * Handle city card click
   */
  function handleCityClick() {
    const $cityCard = $(this);
    currentCity = {
      code: $cityCard.data("city-code"),
      name: $cityCard.data("city-name"),
    };
    openModal();
  }

  /**
   * Open and prepare the area selection dialog
   */
  function openModal() {
    // Check if city has existing areas
    const cityWithCoverage = citiesWithCoverage.find(c => c.city_code === currentCity.code);
    const hasAreas = cityWithCoverage && cityWithCoverage.area_count > 0;
    const isActive = cityWithCoverage && cityWithCoverage.status === 'active';
    
    let dialogTitle = `${i18n.select_areas || "Select Areas for"} ${currentCity.name}`;
    if (hasAreas) {
      dialogTitle = `Manage Areas for ${currentCity.name} (${cityWithCoverage.area_count} areas - ${isActive ? 'Active' : 'Inactive'})`;
    }

    const dialogContent = `
            <div class="dialog-search-wrapper">
                <input type="search" id="dialog-area-search" placeholder="Search areas..." class="nordbooking-dialog-search-input">
            </div>
            <div class="areas-selection-controls">
                <button type="button" id="dialog-select-all" class="btn btn-secondary btn-sm"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M4 12C4 7.58172 7.58172 4 12 4C16.4183 4 20 7.58172 20 12C20 16.4183 16.4183 20 12 20C7.58172 20 4 16.4183 4 12ZM12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2ZM17.4571 9.45711L16.0429 8.04289L11 13.0858L8.20711 10.2929L6.79289 11.7071L11 15.9142L17.4571 9.45711Z"></path></svg>${
                  i18n.select_all || "Select All"
                }</button>
                <button type="button" id="dialog-deselect-all" class="btn btn-secondary btn-sm"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12C22 17.5228 17.5228 22 12 22ZM12 20C16.4183 20 20 16.4183 20 12C20 7.58172 16.4183 4 12 4C7.58172 4 4 7.58172 4 12C4 16.4183 7.58172 20 12 20ZM12 10.5858L14.8284 7.75736L16.2426 9.17157L13.4142 12L16.2426 14.8284L14.8284 16.2426L12 13.4142L9.17157 16.2426L7.75736 14.8284L10.5858 12L7.75736 9.17157L9.17157 7.75736L12 10.5858Z"></path></svg>${
                  i18n.deselect_all || "Deselect All"
                }</button>
            </div>
            ${hasAreas ? `<div class="dialog-info-banner ${isActive ? 'active' : 'inactive'}">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
                This city currently has ${cityWithCoverage.area_count} service areas and is ${isActive ? 'active' : 'inactive'}.
            </div>` : ''}
            <div id="dialog-areas-grid" class="modal-areas-grid">
                <div class="NORDBOOKING-loading-state"><div class="NORDBOOKING-spinner"></div><p>${
                  i18n.loading_areas
                }</p></div>
            </div>
        `;

    areaDialog = new MoBookingDialog({
      title: dialogTitle,
      content: dialogContent,
      buttons: [
        {
          label: i18n.cancel || "Cancel",
          class: "secondary",
          onClick: (dialog) => dialog.close(),
        },
        {
          label: i18n.save_areas || "Save Areas",
          class: "primary",
          onClick: handleSaveAreas,
        },
      ],
      onOpen: () => {
        const dialogEl = areaDialog.getElement();

        // Bind select/deselect all buttons
        $(dialogEl).on("click", "#dialog-select-all", () =>
          $('#dialog-areas-grid input[type="checkbox"]').prop("checked", true)
        );
        $(dialogEl).on("click", "#dialog-deselect-all", () =>
          $('#dialog-areas-grid input[type="checkbox"]').prop("checked", false)
        );

        // Bind expand/collapse toggle
        $(dialogEl).on("click", ".area-zip-toggle", function () {
          $(this).closest(".modal-area-item").toggleClass("is-expanded");
        });

        // Bind search input
        $(dialogEl).on("input", "#dialog-area-search", function () {
          const searchTerm = $(this).val().toLowerCase();
          const $items = $(dialogEl).find(".modal-area-item");

          $items.each(function () {
            const areaName = $(this).find(".area-name").text().toLowerCase();
            if (areaName.includes(searchTerm)) {
              $(this).show();
            } else {
              $(this).hide();
            }
          });
        });

        // Fetch areas
        fetchAndDisplayAreas();
      },
      onClose: () => {
        currentCity = null;
        areaDialog = null;
      },
    });

    areaDialog.show();
  }

  /**
   * Fetch areas and display them in the dialog
   */
  function fetchAndDisplayAreas() {
    const $grid = $("#dialog-areas-grid");
    $.when(
      getAreasForCity(currentCity.code),
      getSavedAreasForCity(currentCity.code)
    )
      .done(function (areasResponse, savedAreasResponse) {
        const allAreas = areasResponse[0].data.areas;
        const savedAreasData = savedAreasResponse[0].data.areas || [];
        const savedAreas = savedAreasData.map((a) => a.area_value);
        displayAreasInModal(allAreas, savedAreas);
      })
      .fail(function () {
        $grid.html(
          `<div class="NORDBOOKING-error-state"><p>${i18n.error}</p></div>`
        );
      });
  }

  /**
   * Get all available areas for a city
   */
  function getAreasForCity(cityCode) {
    return $.ajax({
      url: nordbooking_areas_params.ajax_url,
      type: "POST",
      data: {
        action: "nordbooking_get_areas_for_city",
        nonce: nordbooking_areas_params.nonce,
        country_code: nordbooking_areas_params.country_code,
        city_code: cityCode,
      },
    });
  }

  /**
   * Get already saved/enabled areas for a city
   */
  function getSavedAreasForCity(cityCode) {
    return $.ajax({
      url: nordbooking_areas_params.ajax_url,
      type: "POST",
      data: {
        action: "nordbooking_get_service_coverage",
        nonce: nordbooking_areas_params.nonce,
        city: cityCode,
        limit: -1, // Get all
      },
      success: function (response) {
        console.log(
          "Successfully fetched saved areas for city:",
          cityCode,
          response
        );
      },
      error: function (xhr) {
        console.error(
          "Error fetching saved areas for city:",
          cityCode,
          xhr.responseText
        );
      },
    });
  }

  /**
   * Display areas in the dialog
   */
  function displayAreasInModal(areas, savedAreas) {
    const $grid = $("#dialog-areas-grid");
    const placeNames = Object.keys(areas);

    if (!placeNames.length) {
      $grid.html(
        `<div class="NORDBOOKING-empty-state"><p>${i18n.no_areas_available}</p></div>`
      );
      return;
    }

    const savedPlaceNames = [];
    const unsavedPlaceNames = [];

    placeNames.forEach(function (placeName) {
      const locations = areas[placeName];
      const locationZips = locations.map((loc) => loc.zipcode);
      const allZipsSaved = locationZips.every((zip) =>
        savedAreas.includes(zip)
      );
      if (allZipsSaved) {
        savedPlaceNames.push(placeName);
      } else {
        unsavedPlaceNames.push(placeName);
      }
    });

    savedPlaceNames.sort();
    unsavedPlaceNames.sort();

    const sortedPlaceNames = [...savedPlaceNames, ...unsavedPlaceNames];

    let html = "";
    sortedPlaceNames.forEach(function (placeName) {
      const locations = areas[placeName];
      const allZipsSaved = savedPlaceNames.includes(placeName);
      const areaData = escapeHtml(JSON.stringify(locations));

      html += `
                <div class="modal-area-item ${allZipsSaved ? 'is-selected' : ''}">
                    <label class="modal-area-item-main">
                        <input type="checkbox" value="${escapeHtml(
                          placeName
                        )}" data-area-object='${areaData}' ${
        allZipsSaved ? "checked" : ""
      }>
                        <span class="area-name">${escapeHtml(placeName)}</span>
                        ${allZipsSaved ? '<span class="area-selected-indicator">✓ Selected</span>' : ''}
                        <button type="button" class="area-zip-toggle">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-down"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                    </label>
                    <div class="area-zip-list">
                        ${locations
                          .map(
                            (l) =>
                              `<span class="area-zip">${escapeHtml(
                                l.zipcode
                              )}</span>`
                          )
                          .join("")}
                    </div>
                </div>
            `;
    });
    $grid.html(html);
  }

  /**
   * Handle saving areas from the dialog
   */
  function handleSaveAreas(dialog) {
    const selectedAreas = [];
    const $grid = $(dialog.getElement()).find("#dialog-areas-grid");

    $grid.find("input[type='checkbox']:checked").each(function () {
      const locations = $(this).data("area-object");
      if (locations && Array.isArray(locations)) {
        selectedAreas.push(...locations);
      }
    });

    const $saveBtn = $(dialog.findElement(".btn-primary"));
    const originalBtnText = $saveBtn.text();
    $saveBtn
      .prop("disabled", true)
      .html(
        `<div class="NORDBOOKING-spinner NORDBOOKING-spinner-sm"></div> ${i18n.saving}`
      );

    $.ajax({
      url: nordbooking_areas_params.ajax_url,
      type: "POST",
      data: {
        action: "nordbooking_save_city_areas",
        nonce: nordbooking_areas_params.nonce,
        city_code: currentCity.code,
        areas_data: selectedAreas,
      },
      success: function (response) {
        if (response.success) {
          window.showAlert(
            i18n.areas_saved_success.replace("%s", currentCity.name),
            "success"
          );
          setTimeout(() => {
            dialog.close();
            loadServiceCoverage(); // Refresh coverage list
            loadCities(); // Refresh cities to show updated status
          }, 1500);
        } else {
          window.showAlert(
            response.data?.message || i18n.error_saving,
            "error"
          );
        }
      },
      error: function () {
        window.showAlert(i18n.error_saving, "error");
      },
      complete: function () {
        $saveBtn.prop("disabled", false).html(originalBtnText);
      },
    });
  }

  /**
   * Load and display service coverage
   */
  function loadServiceCoverage() {
    $coverageList.html(
      `<div class="NORDBOOKING-loading-state"><div class="NORDBOOKING-spinner"></div></div>`
    );

    const filters = {
      search: $coverageSearch.val().trim(),
      country: $countryFilter.val(),
      city: $cityFilter.val(),
      status: $statusFilter.val(),
      groupby: "city",
    };

    $.ajax({
      url: nordbooking_areas_params.ajax_url,
      type: "POST",
      data: {
        action: "nordbooking_get_service_coverage_grouped",
        nonce: nordbooking_areas_params.nonce,
        filters: filters,
      },
      success: function (response) {
        console.log("Service coverage response:", response);
        if (response.success && response.data?.cities) {
          renderCoverage(response.data.cities);
        } else {
          $coverageList.html(
            `<div class="NORDBOOKING-empty-state"><p>${
              i18n.no_coverage || "No service coverage found."
            }</p></div>`
          );
        }
      },
      error: function (xhr) {
        console.error("Error loading service coverage:", xhr.responseText);
        $coverageList.html(
          `<div class="NORDBOOKING-error-state"><p>${i18n.error}</p></div>`
        );
      },
    });
  }

  /**
   * Render the service coverage section with new table design
   */
  function renderCoverage(cities) {
    if (!cities.length) {
      $coverageTableHeader.hide();
      $coverageList.html(
        `<div class="NORDBOOKING-empty-state"><p>${
          i18n.no_coverage || "No service coverage found."
        }</p></div>`
      );
      updateCoverageStats(0, 0);
      return;
    }

    $coverageTableHeader.show();
    
    let html = '';
    let totalCities = cities.length;
    let activeCities = 0;
    
    cities.forEach(function (city) {
      if (city.status === 'active') activeCities++;
      
      const countryFlag = getCountryFlag(city.country_code);
      html += `
        <div class="coverage-row" data-city-code="${escapeHtml(city.city_code)}" data-country-code="${escapeHtml(city.country_code)}">
          <div class="select-cell">
            <input type="checkbox" class="coverage-checkbox" value="${escapeHtml(city.city_code)}">
          </div>
          <div class="country-cell">
            <span class="country-flag">${countryFlag}</span>
            ${escapeHtml(city.country_code)}
          </div>
          <div class="city-cell">
            <span class="city-name">${escapeHtml(city.city_name)}</span>
          </div>
          <div class="areas-cell">
            <span class="area-count">${city.area_count}</span>
          </div>
          <div class="status-cell">
            <span class="status-badge ${city.status}">${city.status === 'active' ? 'Active' : 'Inactive'}</span>
          </div>
          <div class="actions-cell">
            <div class="coverage-actions">
              <button type="button" class="toggle-city-btn btn btn-outline btn-sm" data-city-code="${escapeHtml(city.city_code)}" data-status="${escapeHtml(city.status)}">
                ${city.status === "active" ? "Disable" : "Enable"}
              </button>
              <button type="button" class="remove-city-btn btn btn-destructive btn-sm" data-city-code="${escapeHtml(city.city_code)}">
                Remove
              </button>
            </div>
          </div>
        </div>
      `;
    });
    
    $coverageList.html(html);
    updateCoverageStats(totalCities, activeCities);
  }

  /**
   * Get country flag SVG
   */
  function getCountryFlag(countryCode) {
    return window.CountryFlags ? window.CountryFlags.getSVG(countryCode, 20) : `<span>${countryCode}</span>`;
  }

  /**
   * Update coverage statistics
   */
  function updateCoverageStats(total, active) {
    $totalCities.text(total);
    $activeCities.text(active);
  }

  /**
   * Handle select all checkbox
   */
  function handleSelectAll() {
    const isChecked = $selectAllCoverage.is(':checked');
    $coverageList.find('.coverage-checkbox').prop('checked', isChecked);
    updateBulkActionsBar();
  }

  /**
   * Handle individual coverage selection
   */
  function handleCoverageSelection() {
    updateBulkActionsBar();
    updateSelectAllState();
  }

  /**
   * Update bulk actions bar visibility and count
   */
  function updateBulkActionsBar() {
    const selectedCheckboxes = $coverageList.find('.coverage-checkbox:checked');
    const selectedCount = selectedCheckboxes.length;
    
    if (selectedCount > 0) {
      $selectedCount.text(selectedCount);
      $bulkActionsBar.show();
      
      // Add selected class to rows
      $coverageList.find('.coverage-row').removeClass('selected');
      selectedCheckboxes.each(function() {
        $(this).closest('.coverage-row').addClass('selected');
      });
    } else {
      $bulkActionsBar.hide();
      $coverageList.find('.coverage-row').removeClass('selected');
    }
  }

  /**
   * Update select all checkbox state
   */
  function updateSelectAllState() {
    const totalCheckboxes = $coverageList.find('.coverage-checkbox').length;
    const checkedCheckboxes = $coverageList.find('.coverage-checkbox:checked').length;
    
    if (checkedCheckboxes === 0) {
      $selectAllCoverage.prop('indeterminate', false).prop('checked', false);
    } else if (checkedCheckboxes === totalCheckboxes) {
      $selectAllCoverage.prop('indeterminate', false).prop('checked', true);
    } else {
      $selectAllCoverage.prop('indeterminate', true).prop('checked', false);
    }
  }

  /**
   * Clear bulk selection
   */
  function clearBulkSelection() {
    $coverageList.find('.coverage-checkbox').prop('checked', false);
    $selectAllCoverage.prop('checked', false).prop('indeterminate', false);
    $bulkActionsBar.hide();
    $coverageList.find('.coverage-row').removeClass('selected');
  }

  /**
   * Handle bulk actions
   */
  function handleBulkAction(action) {
    const selectedCities = [];
    $coverageList.find('.coverage-checkbox:checked').each(function() {
      const cityCode = $(this).val();
      const countryCode = $(this).closest('.coverage-row').data('country-code');
      selectedCities.push({ city: cityCode, country: countryCode });
    });

    if (selectedCities.length === 0) {
      return;
    }

    let confirmMessage = '';
    let actionText = '';
    
    switch (action) {
      case 'enable':
        confirmMessage = `Enable ${selectedCities.length} selected cities?`;
        actionText = 'Enabling';
        break;
      case 'disable':
        confirmMessage = `Disable ${selectedCities.length} selected cities?`;
        actionText = 'Disabling';
        break;
      case 'remove':
        confirmMessage = `Remove ${selectedCities.length} selected cities? This action cannot be undone.`;
        actionText = 'Removing';
        break;
    }

    if (!confirm(confirmMessage)) {
      return;
    }

    // Show loading state
    $bulkActionsBar.find('.bulk-actions-buttons').html(
      `<div class="NORDBOOKING-spinner NORDBOOKING-spinner-sm"></div> ${actionText}...`
    );

    $.ajax({
      url: nordbooking_areas_params.ajax_url,
      type: "POST",
      data: {
        action: "nordbooking_bulk_city_action",
        nonce: nordbooking_areas_params.nonce,
        bulk_action: action,
        cities: selectedCities,
      },
      success: function(response) {
        if (response.success) {
          window.showAlert(response.data.message || `Bulk ${action} completed successfully.`, "success");
          clearBulkSelection();
          loadServiceCoverage(); // Refresh the list
        } else {
          window.showAlert(response.data.message || `Failed to ${action} cities.`, "error");
        }
      },
      error: function() {
        window.showAlert(`An error occurred during bulk ${action}.`, "error");
      },
      complete: function() {
        // Restore bulk actions buttons
        $bulkActionsBar.find('.bulk-actions-buttons').html(`
          <button type="button" id="bulk-enable-btn" class="btn btn-success btn-sm">Enable</button>
          <button type="button" id="bulk-disable-btn" class="btn btn-warning btn-sm">Disable</button>
          <button type="button" id="bulk-remove-btn" class="btn btn-destructive btn-sm">Remove</button>
          <button type="button" id="bulk-cancel-btn" class="btn btn-outline btn-sm">Cancel</button>
        `);
        
        // Re-bind events
        $bulkActionsBar.find('#bulk-enable-btn').on("click", () => handleBulkAction("enable"));
        $bulkActionsBar.find('#bulk-disable-btn').on("click", () => handleBulkAction("disable"));
        $bulkActionsBar.find('#bulk-remove-btn').on("click", () => handleBulkAction("remove"));
        $bulkActionsBar.find('#bulk-cancel-btn').on("click", clearBulkSelection);
      }
    });
  }

  /**
   * Remove all areas for a specific country
   */
  function removeAllAreasForCountry(countryCode) {
    $.ajax({
      url: nordbooking_areas_params.ajax_url,
      type: "POST",
      data: {
        action: "nordbooking_remove_country_areas",
        nonce: nordbooking_areas_params.nonce,
        country_code: countryCode,
      },
      success: function(response) {
        if (response.success) {
          console.log(`All areas for country ${countryCode} removed successfully.`);
          loadServiceCoverage(); // Refresh the coverage list
        } else {
          console.error(`Failed to remove areas for country ${countryCode}:`, response.data.message);
        }
      },
      error: function() {
        console.error(`Error removing areas for country ${countryCode}`);
      }
    });
  }

  /**
   * Handle toggling a city's service status
   */
  function handleToggleCity() {
    const $btn = $(this);
    const cityCode = $btn.data("city-code");
    const currentStatus = $btn.data("status");
    const newStatus = currentStatus === "active" ? "inactive" : "active";

    $btn
      .prop("disabled", true)
      .html('<div class="NORDBOOKING-spinner NORDBOOKING-spinner-sm"></div>');

    $.ajax({
      url: nordbooking_areas_params.ajax_url,
      type: "POST",
      data: {
        action: "nordbooking_update_city_status",
        nonce: nordbooking_areas_params.nonce,
        city_code: cityCode,
        status: newStatus,
      },
      success: function (response) {
        if (response.success) {
          window.showAlert(
            response.data.message || "Status updated.",
            "success"
          );
          loadServiceCoverage(); // Reload the list to show changes
          loadCities(); // Refresh cities to show updated status
        } else {
          window.showAlert(
            response.data.message || "Error updating status.",
            "error"
          );
          $btn
            .prop("disabled", false)
            .text(currentStatus === "active" ? "Disable" : "Enable");
        }
      },
      error: function () {
        window.showAlert("An unknown error occurred.", "error");
        $btn
          .prop("disabled", false)
          .text(currentStatus === "active" ? "Disable" : "Enable");
      },
    });
  }

  /**
   * Handle removing all areas for a city
   */
  function handleRemoveCity() {
    const $btn = $(this);
    const cityCode = $btn.closest(".coverage-row").data("city-code");
    const cityName = $btn
      .closest(".coverage-row")
      .find(".city-name")
      .text();

    console.log('Remove city clicked:', { cityCode, cityName }); // Debug log

    if (!cityCode) {
      window.showAlert("City code not found. Please refresh the page and try again.", "error");
      return;
    }

    if (!confirm(i18n.confirm_remove_city.replace("%s", cityName))) {
      return;
    }

    $btn
      .prop("disabled", true)
      .html('<div class="NORDBOOKING-spinner NORDBOOKING-spinner-sm"></div>');

    console.log('Sending AJAX request to remove city:', cityCode); // Debug log

    $.ajax({
      url: nordbooking_areas_params.ajax_url,
      type: "POST",
      data: {
        action: "nordbooking_remove_city_coverage",
        nonce: nordbooking_areas_params.nonce,
        city_code: cityCode,
      },
      success: function (response) {
        if (response.success) {
          window.showAlert(response.data.message || "City removed.", "success");
          // Optimistically remove from UI
          $btn.closest(".coverage-row").fadeOut(300, function () {
            $(this).remove();
          });
          loadCities(); // Refresh cities to show updated status
        } else {
          window.showAlert(
            response.data.message || i18n.error_removing_city,
            "error"
          );
          $btn.prop("disabled", false).text("Remove");
        }
      },
      error: function () {
        window.showAlert(i18n.error_removing_city, "error");
        $btn.prop("disabled", false).text("Remove");
      },
    });
  }

  /**
   * Clear all coverage filters
   */
  function clearCoverageFilters() {
    $coverageSearch.val("");
    $countryFilter.val("");
    $cityFilter.val("");
    $statusFilter.val("");
    loadServiceCoverage();
  }

  /**
   * Utility to escape HTML
   */
  function escapeHtml(text) {
    const map = {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#039;",
    };
    return text ? text.replace(/[&<>"']/g, (m) => map[m]) : "";
  }

  // Initialize the page
  $(document).ready(init);
})(jQuery);
