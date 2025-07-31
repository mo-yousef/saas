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

    // Modal elements
    const $modal = $("#area-selection-modal");
    const $modalCityName = $("#modal-city-name");
    const $modalAreasGrid = $("#modal-areas-grid");
    const $modalSaveBtn = $("#modal-save-btn");
    const $modalCancelBtn = $("#modal-cancel-btn");
    const $modalCloseBtn = $(".mobooking-modal-close-btn");
    const $modalSelectAll = $("#modal-select-all");
    const $modalDeselectAll = $("#modal-deselect-all");
    const $modalFeedback = $("#modal-feedback");

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

        // Modal actions
        $modalCancelBtn.on("click", closeModal);
        $modalCloseBtn.on("click", closeModal);
        $modalSaveBtn.on("click", handleSaveAreas);
        $modalSelectAll.on("click", () => $modalAreasGrid.find("input[type='checkbox']").prop("checked", true));
        $modalDeselectAll.on("click", () => $modalAreasGrid.find("input[type='checkbox']").prop("checked", false));

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
     * Open and prepare the area selection modal
     */
    function openModal() {
        $modalCityName.text(currentCity.name);
        $modalAreasGrid.html(`<div class="mobooking-loading-state"><div class="mobooking-spinner"></div><p>${i18n.loading_areas}</p></div>`);
        $modal.fadeIn(200);

        // Fetch areas for the city
        $.when(
            getAreasForCity(currentCity.code),
            getSavedAreasForCity(currentCity.code)
        ).done(function(areasResponse, savedAreasResponse) {
            const allAreas = areasResponse[0].data.areas;
            const savedAreas = savedAreasResponse[0].data.areas.map(a => a.area_value);
            displayAreasInModal(allAreas, savedAreas);
        }).fail(function() {
            $modalAreasGrid.html(`<div class="mobooking-error-state"><p>${i18n.error}</p></div>`);
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
        });
    }

    /**
     * Close the area selection modal
     */
    function closeModal() {
        $modal.fadeOut(200);
        currentCity = null;
        $modalFeedback.hide();
    }

    /**
     * Display areas in the modal
     */
    function displayAreasInModal(areas, savedAreas) {
        if (!areas || !areas.length) {
            $modalAreasGrid.html(`<div class="mobooking-empty-state"><p>${i18n.no_areas_available}</p></div>`);
            return;
        }

        let html = "";
        areas.forEach(function (area) {
            const isChecked = savedAreas.includes(area.zip_code);
            html += `
                <label class="modal-area-item">
                    <input type="checkbox" value="${escapeHtml(area.zip_code)}" data-area-name="${escapeHtml(area.name)}" ${isChecked ? "checked" : ""}>
                    <span class="area-name">${escapeHtml(area.name)}</span>
                    <span class="area-zip">${escapeHtml(area.zip_code)}</span>
                </label>
            `;
        });
        $modalAreasGrid.html(html);
    }

    /**
     * Handle saving areas from the modal
     */
    function handleSaveAreas() {
        const selectedAreas = [];
        $modalAreasGrid.find("input[type='checkbox']:checked").each(function () {
            selectedAreas.push({
                area_name: $(this).data("area-name"),
                area_zipcode: $(this).val(),
                country_code: mobooking_areas_params.country_code,
                country_name: 'Sweden',
                city_name: currentCity.name,
            });
        });

        const originalBtnText = $modalSaveBtn.html();
        $modalSaveBtn.prop("disabled", true).html(`<div class="mobooking-spinner mobooking-spinner-sm"></div> ${i18n.saving}`);

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
                    showModalFeedback(i18n.areas_saved_success.replace('%s', currentCity.name), "success");
                    setTimeout(() => {
                        closeModal();
                        loadServiceCoverage(); // Refresh coverage list
                    }, 1500);
                } else {
                    showModalFeedback(response.data?.message || i18n.error_saving, "error");
                }
            },
            error: function () {
                showModalFeedback(i18n.error_saving, "error");
            },
            complete: function () {
                $modalSaveBtn.prop("disabled", false).html(originalBtnText);
            }
        });
    }

    /**
     * Show feedback message inside the modal
     */
    function showModalFeedback(message, type = 'success') {
        window.showAlert(message, type);
        $modalFeedback.removeClass('success error').addClass(type).html(message).fadeIn();
        setTimeout(() => $modalFeedback.fadeOut(), 5000);
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
                if (response.success && response.data?.cities) {
                    renderCoverage(response.data.cities);
                } else {
                    $coverageList.html(`<div class="mobooking-empty-state"><p>${i18n.no_coverage || 'No service coverage found.'}</p></div>`);
                }
            },
            error: function () {
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
