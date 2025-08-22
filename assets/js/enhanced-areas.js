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
    const i18n = mobooking_areas_params.i18n || {};

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
        $citiesGridContainer.html(`<div class="mobooking-loading-state"><div class="mobooking-spinner"></div><p>${i18n.loading_cities}</p></div>`);

        $.ajax({
            url: mobooking_areas_params.ajax_url,
            type: "POST",
            data: {
                action: "mobooking_get_cities_for_country",
                nonce: mobooking_areas_params.nonce,
                country_code: mobooking_areas_params.country_code,
            },
            success: function (response) {
                if (response.success && response.data?.cities) {
                    citiesData = response.data.cities;
                    displayCities(citiesData);
                    populateCityFilter(citiesData);
                } else {
                    $citiesGridContainer.html(`<div class="mobooking-empty-state"><p>${i18n.no_cities_available}</p></div>`);
                }
            },
            error: function () {
                $citiesGridContainer.html(`<div class="mobooking-error-state"><p>${i18n.error}</p></div>`);
            },
        });
    }

    /**
     * Display cities grid
     */
    function displayCities(cities) {
        if (!cities.length) {
            $citiesGridContainer.html(`<div class="mobooking-empty-state"><p>${i18n.no_cities_available}</p></div>`);
            return;
        }

        let html = '<div class="cities-grid">';
        cities.forEach(function (city) {
            html += `
                <div class="city-card" data-city-code="${escapeHtml(city.code)}" data-city-name="${escapeHtml(city.name)}">
                    <span class="city-name">${escapeHtml(city.name)}</span>
                    <span class="city-action-link">${wp.i18n.__('Manage Areas', 'mobooking')} &rarr;</span>
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
        $cityFilter.empty().append($("<option>", { value: "", text: i18n.all_cities || "All Cities" }));
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
            <div class="areas-selection-controls">
                <button type="button" id="dialog-select-all" class="btn btn-link">${i18n.select_all || 'Select All'}</button>
                <button type="button" id="dialog-deselect-all" class="btn btn-link">${i18n.deselect_all || 'Deselect All'}</button>
            </div>
            <div id="dialog-areas-grid" class="modal-areas-grid">
                <div class="mobooking-loading-state"><div class="mobooking-spinner"></div><p>${i18n.loading_areas}</p></div>
            </div>
        `;

        areaDialog = new MoBookingDialog({
            title: `${i18n.select_areas || 'Select Areas for'} ${currentCity.name}`,
            content: dialogContent,
            buttons: [
                {
                    label: i18n.cancel || 'Cancel',
                    class: 'secondary',
                    onClick: (dialog) => dialog.close(),
                },
                {
                    label: i18n.save_areas || 'Save Areas',
                    class: 'primary',
                    onClick: handleSaveAreas,
                }
            ],
            onOpen: () => {
                // Bind select/deselect all buttons
                const dialogEl = areaDialog.getElement();
                $(dialogEl).on('click', '#dialog-select-all', () => $('#dialog-areas-grid input[type="checkbox"]').prop('checked', true));
                $(dialogEl).on('click', '#dialog-deselect-all', () => $('#dialog-areas-grid input[type="checkbox"]').prop('checked', false));

                // Fetch areas
                fetchAndDisplayAreas();
            },
            onClose: () => {
                currentCity = null;
                areaDialog = null;
            }
        });

        areaDialog.show();
    }

    /**
     * Fetch areas and display them in the dialog
     */
    function fetchAndDisplayAreas() {
        const $grid = $('#dialog-areas-grid');
        $.when(
            getAreasForCity(currentCity.code),
            getSavedAreasForCity(currentCity.code)
        ).done(function(areasResponse, savedAreasResponse) {
            const allAreas = areasResponse[0].data.areas;
            const savedAreasData = savedAreasResponse[0].data.areas || [];
            const savedAreas = savedAreasData.map(a => a.area_value);
            displayAreasInModal(allAreas, savedAreas);
        }).fail(function() {
            $grid.html(`<div class="mobooking-error-state"><p>${i18n.error}</p></div>`);
        });
    }

    /**
     * Get all available areas for a city
     */
    function getAreasForCity(cityCode) {
        return $.ajax({
            url: mobooking_areas_params.ajax_url,
            type: "POST",
            data: {
                action: "mobooking_get_areas_for_city",
                nonce: mobooking_areas_params.nonce,
                country_code: mobooking_areas_params.country_code,
                city_code: cityCode,
            },
        });
    }

    /**
     * Get already saved/enabled areas for a city
     */
    function getSavedAreasForCity(cityCode) {
        return $.ajax({
            url: mobooking_areas_params.ajax_url,
            type: "POST",
            data: {
                action: "mobooking_get_service_coverage",
                nonce: mobooking_areas_params.nonce,
                city: cityCode,
                limit: -1, // Get all
            },
            success: function(response) {
                console.log("Successfully fetched saved areas for city:", cityCode, response);
            },
            error: function(xhr) {
                console.error("Error fetching saved areas for city:", cityCode, xhr.responseText);
            }
        });
    }

    /**
     * Display areas in the dialog
     */
    function displayAreasInModal(areas, savedAreas) {
        const $grid = $('#dialog-areas-grid');
        const placeNames = Object.keys(areas);

        if (!placeNames.length) {
            $grid.html(`<div class="mobooking-empty-state"><p>${i18n.no_areas_available}</p></div>`);
            return;
        }

        let html = "";
        placeNames.sort().forEach(function (placeName) {
            const locations = areas[placeName];
            const locationZips = locations.map(loc => loc.zipcode);
            const allZipsSaved = locationZips.every(zip => savedAreas.includes(zip));
            const areaData = escapeHtml(JSON.stringify(locations));
            const zipCodesDisplay = locations.map(l => l.zipcode).join(', ');

            html += `
                <label class="modal-area-item">
                    <input type="checkbox" value="${escapeHtml(placeName)}" data-area-object='${areaData}' ${allZipsSaved ? "checked" : ""}>
                    <span class="area-name">${escapeHtml(placeName)}</span>
                    <span class="area-zip">${escapeHtml(zipCodesDisplay)}</span>
                </label>
            `;
        });
        $grid.html(html);
    }

    /**
     * Handle saving areas from the dialog
     */
    function handleSaveAreas(dialog) {
        const selectedAreas = [];
        const $grid = $(dialog.getElement()).find('#dialog-areas-grid');

        $grid.find("input[type='checkbox']:checked").each(function () {
            const areaDataString = $(this).data('area-object');
            if (areaDataString) {
                try {
                    const locations = JSON.parse(areaDataString);
                    selectedAreas.push(...locations);
                } catch (e) {
                    console.error('Error parsing area data:', e);
                }
            }
        });

        const $saveBtn = $(dialog.findElement('.btn-primary'));
        const originalBtnText = $saveBtn.text();
        $saveBtn.prop("disabled", true).html(`<div class="mobooking-spinner mobooking-spinner-sm"></div> ${i18n.saving}`);

        $.ajax({
            url: mobooking_areas_params.ajax_url,
            type: "POST",
            data: {
                action: "mobooking_save_city_areas",
                nonce: mobooking_areas_params.nonce,
                city_code: currentCity.code,
                areas_data: selectedAreas,
            },
            success: function (response) {
                if (response.success) {
                    window.showAlert(i18n.areas_saved_success.replace('%s', currentCity.name), "success");
                    setTimeout(() => {
                        dialog.close();
                        loadServiceCoverage(); // Refresh coverage list
                    }, 1500);
                } else {
                    window.showAlert(response.data?.message || i18n.error_saving, "error");
                }
            },
            error: function () {
                window.showAlert(i18n.error_saving, "error");
            },
            complete: function () {
                $saveBtn.prop("disabled", false).html(originalBtnText);
            }
        });
    }

    /**
     * Load and display service coverage
     */
    function loadServiceCoverage() {
        $coverageList.html(`<div class="mobooking-loading-state"><div class="mobooking-spinner"></div></div>`);

        const filters = {
            search: $coverageSearch.val().trim(),
            city: $cityFilter.val(),
            status: $statusFilter.val(),
            country_code: mobooking_areas_params.country_code,
            groupby: 'city',
        };

        $.ajax({
            url: mobooking_areas_params.ajax_url,
            type: "POST",
            data: {
                action: "mobooking_get_service_coverage_grouped",
                nonce: mobooking_areas_params.nonce,
                filters: filters,
            },
            success: function (response) {
                console.log("Service coverage response:", response);
                if (response.success && response.data?.cities) {
                    renderCoverage(response.data.cities);
                } else {
                    $coverageList.html(`<div class="mobooking-empty-state"><p>${i18n.no_coverage || 'No service coverage found.'}</p></div>`);
                }
            },
            error: function (xhr) {
                console.error("Error loading service coverage:", xhr.responseText);
                $coverageList.html(`<div class="mobooking-error-state"><p>${i18n.error}</p></div>`);
            },
        });
    }

    /**
     * Render the service coverage section
     */
    function renderCoverage(cities) {
        if (!cities.length) {
            $coverageList.html(`<div class="mobooking-empty-state"><p>${i18n.no_coverage || 'No service coverage found.'}</p></div>`);
            return;
        }

        let html = '<div class="coverage-cities-list">';
        cities.forEach(function (city) {
            html += `
                <div class="coverage-city-item" data-city-code="${escapeHtml(city.city_code)}">
                    <div class="city-info">
                        <span class="city-name">${escapeHtml(city.city_name)}</span>
                        <span class="city-stats">${city.area_count} areas</span>
                    </div>
                    <div class="city-actions">
                        <button type="button" class="toggle-city-btn mobooking-btn mobooking-btn-secondary mobooking-btn-sm" data-status="${city.status}">
                            ${city.status === 'active' ? 'Disable' : 'Enable'}
                        </button>
                        <button type="button" class="remove-city-btn mobooking-btn mobooking-btn-danger mobooking-btn-sm">
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
        // Future implementation
    }

    /**
     * Handle removing all areas for a city
     */
    function handleRemoveCity() {
        const $btn = $(this);
        const cityCode = $btn.closest('.coverage-city-item').data('city-code');
        const cityName = $btn.closest('.coverage-city-item').find('.city-name').text();

        if (!confirm(i18n.confirm_remove_city.replace('%s', cityName))) {
            return;
        }

        $btn.prop('disabled', true).html('<div class="mobooking-spinner mobooking-spinner-sm"></div>');

        $.ajax({
            url: mobooking_areas_params.ajax_url,
            type: "POST",
            data: {
                action: "mobooking_remove_city_coverage",
                nonce: mobooking_areas_params.nonce,
                city_code: cityCode,
            },
            success: function (response) {
                if (response.success) {
                    window.showAlert(response.data.message || 'City removed.', 'success');
                    // Optimistically remove from UI
                    $btn.closest('.coverage-city-item').fadeOut(300, function() { $(this).remove(); });
                } else {
                    window.showAlert(response.data.message || i18n.error_removing_city, 'error');
                    $btn.prop('disabled', false).text('Remove');
                }
            },
            error: function() {
                window.showAlert(i18n.error_removing_city, 'error');
                $btn.prop('disabled', false).text('Remove');
            }
        });
    }

    /**
     * Clear all coverage filters
     */
    function clearCoverageFilters() {
        $coverageSearch.val('');
        $cityFilter.val('');
        $statusFilter.val('');
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
