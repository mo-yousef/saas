/**
 * Enhanced Service Areas Management JavaScript
 * Swedish-focused, modal-based city and area management
 */

(function ($) {
  "use strict";

  // Global state
  let currentCity = null;
  let citiesData = [];

  // DOM elements
  const $citiesGridContainer = $("#cities-grid-container");
  const $coverageList = $("#service-coverage-list");
  const $cityFilter = $("#city-filter");
  const $statusFilter = $("#status-filter");
  const $coverageSearch = $("#coverage-search");
  const $clearFiltersBtn = $("#clear-coverage-filters-btn");

  // Dialog instance
  let areaDialog = null;

  // i18n shorthand
  const i18n = nordbooking_areas_params.i18n || {};

  /**
   * Initialize the application
   */
  function init() {
    loadCities();
    loadServiceCoverage();
    bindEvents();
  }

  /**
   * Bind all event handlers
   */
  function bindEvents() {
    // City selection
    $citiesGridContainer.on("click", ".city-card", handleCityClick);

    // Coverage management
    $coverageSearch.on("input", debounce(loadServiceCoverage, 500));
    $cityFilter.on("change", loadServiceCoverage);
    $statusFilter.on("change", loadServiceCoverage);
    $clearFiltersBtn.on("click", clearCoverageFilters);
    $coverageList.on("click", ".toggle-city-btn", handleToggleCity);
    $coverageList.on("click", ".remove-city-btn", handleRemoveCity);
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
   * Load available Swedish cities
   */
  function loadCities() {
    $citiesGridContainer.html(
      `<div class="NORDBOOKING-loading-state"><div class="NORDBOOKING-spinner"></div><p>${i18n.loading_cities}</p></div>`
    );

    $.ajax({
      url: nordbooking_areas_params.ajax_url,
      type: "POST",
      data: {
        action: "nordbooking_get_cities_for_country",
        nonce: nordbooking_areas_params.nonce,
        country_code: nordbooking_areas_params.country_code,
      },
      success: function (response) {
        if (response.success && response.data?.cities) {
          citiesData = response.data.cities;
          displayCities(citiesData);
          populateCityFilter(citiesData);
        } else {
          $citiesGridContainer.html(
            `<div class="NORDBOOKING-empty-state"><p>${i18n.no_cities_available}</p></div>`
          );
        }
      },
      error: function () {
        $citiesGridContainer.html(
          `<div class="NORDBOOKING-error-state"><p>${i18n.error}</p></div>`
        );
      },
    });
  }

  /**
   * Display cities grid
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
      html += `
                <div class="city-card" data-city-code="${escapeHtml(
                  city.code
                )}" data-city-name="${escapeHtml(city.name)}">
                    <span class="city-name"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-map-pin"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>${escapeHtml(
                      city.name
                    )}</span>
                    <span class="city-action-link">${wp.i18n.__(
                      "Manage Areas",
                      "NORDBOOKING"
                    )} &rarr;</span>
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
            <div id="dialog-areas-grid" class="modal-areas-grid">
                <div class="NORDBOOKING-loading-state"><div class="NORDBOOKING-spinner"></div><p>${
                  i18n.loading_areas
                }</p></div>
            </div>
        `;

    areaDialog = new MoBookingDialog({
      title: `${i18n.select_areas || "Select Areas for"} ${currentCity.name}`,
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
      const allZipsSaved = savedPlaceNames.includes(placeName); // We already know this, just reuse for the checkbox
      const areaData = escapeHtml(JSON.stringify(locations));

      html += `
                <div class="modal-area-item">
                    <label class="modal-area-item-main">
                        <input type="checkbox" value="${escapeHtml(
                          placeName
                        )}" data-area-object='${areaData}' ${
        allZipsSaved ? "checked" : ""
      }>
                        <span class="area-name">${escapeHtml(placeName)}</span>
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
      city: $cityFilter.val(),
      status: $statusFilter.val(),
      country_code: nordbooking_areas_params.country_code,
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
   * Render the service coverage section
   */
  function renderCoverage(cities) {
    if (!cities.length) {
      $coverageList.html(
        `<div class="NORDBOOKING-empty-state"><p>${
          i18n.no_coverage || "No service coverage found."
        }</p></div>`
      );
      return;
    }

    let html = '<div class="coverage-cities-list">';
    cities.forEach(function (city) {
      html += `
                <div class="coverage-city-item" data-city-code="${escapeHtml(
                  city.city_code
                )}">
                    <div class="city-info">
                        <span class="city-name">${escapeHtml(
                          city.city_name
                        )}</span>
                        <span class="city-stats">${city.area_count} areas</span>
                    </div>
                    <div class="city-actions">
                        <button type="button" class="toggle-city-btn NORDBOOKING-btn NORDBOOKING-btn-secondary NORDBOOKING-btn-sm" data-city-code="${escapeHtml(
                          city.city_code
                        )}" data-status="${escapeHtml(city.status)}">
                            ${city.status === "active" ? "Disable" : "Enable"}
                        </button>
                        <button type="button" class="remove-city-btn NORDBOOKING-btn NORDBOOKING-btn-danger NORDBOOKING-btn-sm" data-city-code="${escapeHtml(
                          city.city_code
                        )}">
                            Remove
                        </button>
                    </div>
                </div>
            `;
    });
    html += "</div>";
    $coverageList.html(html);
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
    const cityCode = $btn.closest(".coverage-city-item").data("city-code");
    const cityName = $btn
      .closest(".coverage-city-item")
      .find(".city-name")
      .text();

    if (!confirm(i18n.confirm_remove_city.replace("%s", cityName))) {
      return;
    }

    $btn
      .prop("disabled", true)
      .html('<div class="NORDBOOKING-spinner NORDBOOKING-spinner-sm"></div>');

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
          $btn.closest(".coverage-city-item").fadeOut(300, function () {
            $(this).remove();
          });
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
