jQuery(document).ready(function ($) {
    'use strict';

    const countrySelector = $('#mobooking-country-selector');
    const citySelector = $('#mobooking-city-selector');
    const areaContainer = $('#mobooking-area-zip-selector-container');
    const addBtn = $('#mobooking-add-selected-areas-btn');
    const feedbackDiv = $('#mobooking-selection-feedback');

    function showFeedback(message, type = 'success') {
        feedbackDiv.removeClass('success error').addClass(type).html(message).fadeIn();
        setTimeout(() => feedbackDiv.fadeOut(), 5000);
    }

    function populateDropdown(selector, data, defaultOption) {
        selector.empty().append($('<option>', { value: '', text: defaultOption }));
        $.each(data, function (i, item) {
            selector.append($('<option>', { value: item.code || item.city_name, text: item.name || item.city_name }));
        });
    }

    // Load countries
    $.ajax({
        url: mobooking_areas_params.ajax_url,
        type: 'POST',
        data: { action: 'mobooking_get_countries', nonce: mobooking_areas_params.nonce },
        success: function (response) {
            if (response.success) {
                populateDropdown(countrySelector, response.data.countries, mobooking_areas_params.i18n.select_country);
            }
        }
    });

    // Handle country change
    countrySelector.on('change', function () {
        const countryCode = $(this).val();
        citySelector.prop('disabled', true).empty();
        areaContainer.empty();
        addBtn.prop('disabled', true);

        if (countryCode) {
            $.ajax({
                url: mobooking_areas_params.ajax_url,
                type: 'POST',
                data: { action: 'mobooking_get_cities_for_country', nonce: mobooking_areas_params.nonce, country_code: countryCode },
                success: function (response) {
                    if (response.success) {
                        populateDropdown(citySelector, response.data.cities, mobooking_areas_params.i18n.select_city);
                        citySelector.prop('disabled', false);
                    }
                }
            });
        }
    });

    // Handle city change
    citySelector.on('change', function () {
        const countryCode = countrySelector.val();
        const cityName = $(this).val();
        areaContainer.empty();
        addBtn.prop('disabled', true);

        if (cityName) {
            $.ajax({
                url: mobooking_areas_params.ajax_url,
                type: 'POST',
                data: { action: 'mobooking_get_areas_for_city', nonce: mobooking_areas_params.nonce, country_code: countryCode, city_name: cityName },
                success: function (response) {
                    if (response.success && response.data.areas.length > 0) {
                        let html = '';
                        response.data.areas.forEach(function (area) {
                            html += `<label><input type="checkbox" class="mobooking-area-checkbox" value="${area.zip}" data-area-name="${area.name}" data-country-code="${countryCode}" data-country-name="${countrySelector.find('option:selected').text()}"> ${area.name} (${area.zip})</label><br>`;
                        });
                        areaContainer.html(html);
                        addBtn.prop('disabled', false);
                    }
                }
            });
        }
    });

    // Handle add button click
    addBtn.on('click', function () {
        const selectedAreas = [];
        $('.mobooking-area-checkbox:checked').each(function () {
            const checkbox = $(this);
            selectedAreas.push({
                area_name: checkbox.data('area-name'),
                area_zipcode: checkbox.val(),
                country_name: checkbox.data('country-name'),
                country_code: checkbox.data('country-code')
            });
        });

        if (selectedAreas.length === 0) {
            showFeedback(mobooking_areas_params.i18n.no_areas_selected, 'error');
            return;
        }

        $.ajax({
            url: mobooking_areas_params.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_add_bulk_areas',
                nonce: mobooking_areas_params.nonce,
                areas: JSON.stringify(selectedAreas)
            },
            success: function (response) {
                if (response.success) {
                    showFeedback(response.data.message);
                } else {
                    showFeedback(response.data.message, 'error');
                }
            }
        });
    });
});
