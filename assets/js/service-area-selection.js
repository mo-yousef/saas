/**
 * Service Area Selection JavaScript
 * For service edit page - multi-country support
 */

(function ($) {
  "use strict";

  // Global state
  let currentServiceCountry = null;
  let selectedCities = [];
  let countriesData = [];
  let citiesData = [];

  // DOM elements
  const $countrySelect = $("#service-country-select");
  const $citiesContainer = $("#service-cities-container");
  const $citiesGrid = $("#service-cities-grid");
  const $areaSummary = $("#service-area-summary");
  const $selectedAreasList = $("#selected-areas-list");

  /**
   * Initialize the service area selection
   */
  function init() {
    loadCountries();
    bindEvents();
    loadExistingServiceAreas();
    loadSelectedCountry(); // Load previously selected country
  }

  /**
   * Bind event handlers
   */
  function bindEvents() {
    $countrySelect.on("change", handleCountryChange);
    $citiesGrid.on("change", ".service-city-item input[type='checkbox']", handleCitySelection);
    $selectedAreasList.on("click", ".remove-area", handleRemoveArea);
  }

  /**
   * Load previously selected country
   */
  function loadSelectedCountry() {
    $.ajax({
      url: nordbooking_service_params.ajax_url,
      type: "POST",
      data: {
        action: "nordbooking_get_selected_country",
        nonce: nordbooking_service_params.nonce,
      },
      success: function(response) {
        if (response.success && response.data?.country_code) {
          const countryCode = response.data.country_code;
          $countrySelect.val(countryCode);
          if (countryCode) {
            currentServiceCountry = countryCode;
            loadCitiesForCountry(countryCode);
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
      url: nordbooking_service_params.ajax_url,
      type: "POST",
      data: {
        action: "nordbooking_save_selected_country",
        nonce: nordbooking_service_params.nonce,
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
    $.ajax({
      url: nordbooking_service_params.ajax_url,
      type: "POST",
      data: {
        action: "nordbooking_get_countries",
        nonce: nordbooking_service_params.nonce,
      },
      success: function(response) {
        if (response.success && response.data?.countries) {
          countriesData = response.data.countries;
          populateCountrySelect(countriesData);
          // Load selected country after countries are loaded
          setTimeout(loadSelectedCountry, 100);
        }
      },
      error: function() {
        console.error("Failed to load countries");
      }
    });
  }

  /**
   * Get country flag emoji for select dropdown
   */
  function getCountryFlag(countryCode) {
    return window.CountryFlags ? window.CountryFlags.getEmoji(countryCode) : countryCode;
  }

  /**
   * Populate country select dropdown
   */
  function populateCountrySelect(countries) {
    $countrySelect.empty().append(
      $("<option>", { value: "", text: "Select a country..." })
    );
    
    countries.forEach(function(country) {
      const flag = getCountryFlag(country.code);
      $countrySelect.append(
        $("<option>", { 
          value: country.code, 
          text: `${flag} ${country.name}` 
        })
      );
    });
  }

  /**
   * Handle country selection change
   */
  function handleCountryChange() {
    const selectedCountryCode = $countrySelect.val();
    
    if (!selectedCountryCode) {
      $citiesContainer.hide();
      $areaSummary.hide();
      return;
    }

    // Check if changing country and warn user
    if (currentServiceCountry && currentServiceCountry !== selectedCountryCode && selectedCities.length > 0) {
      const currentCountryName = countriesData.find(c => c.code === currentServiceCountry)?.name || currentServiceCountry;
      const newCountryName = countriesData.find(c => c.code === selectedCountryCode)?.name || selectedCountryCode;
      
      if (window.showCountryChangeDialog) {
        window.showCountryChangeDialog(
          currentCountryName,
          newCountryName,
          function() {
            // User confirmed - proceed with country change
            selectedCities = [];
            updateAreaSummary();
            currentServiceCountry = selectedCountryCode;
            saveSelectedCountry(selectedCountryCode);
            loadCitiesForCountry(selectedCountryCode);
          },
          function() {
            // User cancelled - revert selection
            $countrySelect.val(currentServiceCountry);
          }
        );
      } else {
        // Fallback to browser confirm if dialog not available
        if (!confirm("Changing country will remove all previously selected cities. Are you sure you want to continue?")) {
          $countrySelect.val(currentServiceCountry);
          return;
        }
        selectedCities = [];
        updateAreaSummary();
      }
      return;
    }

    currentServiceCountry = selectedCountryCode;
    saveSelectedCountry(selectedCountryCode);
    loadCitiesForCountry(selectedCountryCode);
  }

  /**
   * Load cities for selected country
   */
  function loadCitiesForCountry(countryCode) {
    $citiesGrid.html('<div class="loading-state">Loading cities...</div>');
    
    $.ajax({
      url: nordbooking_service_params.ajax_url,
      type: "POST",
      data: {
        action: "nordbooking_get_cities_for_country",
        nonce: nordbooking_service_params.nonce,
        country_code: countryCode,
      },
      success: function(response) {
        if (response.success && response.data?.cities) {
          citiesData = response.data.cities;
          displayCities(citiesData);
          $citiesContainer.show();
        } else {
          $citiesGrid.html('<div class="error-state">No cities available for this country.</div>');
        }
      },
      error: function() {
        $citiesGrid.html('<div class="error-state">Failed to load cities.</div>');
      }
    });
  }

  /**
   * Display cities in grid
   */
  function displayCities(cities) {
    if (!cities.length) {
      $citiesGrid.html('<div class="empty-state">No cities available.</div>');
      return;
    }

    let html = '';
    cities.forEach(function(city) {
      const isSelected = selectedCities.includes(city.code);
      html += `
        <div class="service-city-item ${isSelected ? 'selected' : ''}" data-city-code="${escapeHtml(city.code)}">
          <input type="checkbox" value="${escapeHtml(city.code)}" ${isSelected ? 'checked' : ''}>
          <span class="city-name">${escapeHtml(city.name)}</span>
        </div>
      `;
    });
    
    $citiesGrid.html(html);
  }

  /**
   * Handle city selection
   */
  function handleCitySelection() {
    const $checkbox = $(this);
    const $cityItem = $checkbox.closest('.service-city-item');
    const cityCode = $checkbox.val();
    const isChecked = $checkbox.is(':checked');

    if (isChecked) {
      if (!selectedCities.includes(cityCode)) {
        selectedCities.push(cityCode);
      }
      $cityItem.addClass('selected');
    } else {
      selectedCities = selectedCities.filter(code => code !== cityCode);
      $cityItem.removeClass('selected');
    }

    updateAreaSummary();
  }

  /**
   * Handle removing an area from summary
   */
  function handleRemoveArea() {
    const cityCode = $(this).data('city-code');
    selectedCities = selectedCities.filter(code => code !== cityCode);
    
    // Update checkbox in grid
    $citiesGrid.find(`input[value="${cityCode}"]`).prop('checked', false);
    $citiesGrid.find(`[data-city-code="${cityCode}"]`).removeClass('selected');
    
    updateAreaSummary();
  }

  /**
   * Update area summary display
   */
  function updateAreaSummary() {
    if (selectedCities.length === 0) {
      $areaSummary.hide();
      return;
    }

    let html = '';
    selectedCities.forEach(function(cityCode) {
      const city = citiesData.find(c => c.code === cityCode);
      if (city) {
        html += `
          <div class="selected-area-tag">
            ${escapeHtml(city.name)}
            <span class="remove-area" data-city-code="${escapeHtml(cityCode)}">&times;</span>
          </div>
        `;
      }
    });

    $selectedAreasList.html(html);
    $areaSummary.show();
  }

  /**
   * Load existing service areas for edit mode
   */
  function loadExistingServiceAreas() {
    // This would be called in edit mode to load existing service areas
    // Implementation depends on how service areas are stored
    // For now, this is a placeholder
  }

  /**
   * Get selected service area data for form submission
   */
  function getServiceAreaData() {
    return {
      country: currentServiceCountry,
      cities: selectedCities
    };
  }

  /**
   * Utility function to escape HTML
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

  // Make getServiceAreaData available globally for form submission
  window.getServiceAreaData = getServiceAreaData;

  // Initialize when document is ready
  $(document).ready(init);

})(jQuery);