jQuery(document).ready(function($) {
    'use strict';

    const areasListContainer = $('#mobooking-areas-list-container');
    const addAreaForm = $('#mobooking-add-area-form');
    const feedbackDiv = $('#mobooking-add-area-feedback').hide(); // Hide initially

    // Basic XSS protection for display
    function sanitizeHTML(str) {
        if (typeof str !== 'string') return '';
        var temp = document.createElement('div');
        temp.textContent = str;
        return temp.innerHTML;
    }

    function renderTemplate(templateId, data) {
        let template = $(templateId).html();
        if (!template) return '';
        for (const key in data) {
            const value = (typeof data[key] === 'string' || typeof data[key] === 'number') ? data[key] : '';
            template = template.replace(new RegExp('<%=\\s*' + key + '\\s*%>', 'g'), sanitizeHTML(String(value)));
        }
        return template;
    }

    function loadAreas() {
        areasListContainer.html('<p>' + mobooking_areas_params.i18n.loading + '</p>');
        $.ajax({
            url: mobooking_areas_params.ajax_url,
            type: 'POST',
            data: { action: 'mobooking_get_areas', nonce: mobooking_areas_params.nonce },
            success: function(response) {
                areasListContainer.empty();
                if (response.success && response.data.length) {
                    response.data.forEach(function(area) {
                        areasListContainer.append(renderTemplate('#mobooking-area-item-template', area));
                    });
                } else if (response.success) {
                    areasListContainer.html('<p>' + mobooking_areas_params.i18n.no_areas + '</p>');
                } else {
                    areasListContainer.html('<p>' + sanitizeHTML(response.data.message || mobooking_areas_params.i18n.error_loading) + '</p>');
                }
            },
            error: function() {
                areasListContainer.html('<p>' + mobooking_areas_params.i18n.error_loading + '</p>');
            }
        });
    }

    addAreaForm.on('submit', function(e) {
        e.preventDefault();
        feedbackDiv.empty().removeClass('success error').hide();
        const countryCode = $('#mobooking-area-country-code').val().trim();
        const areaValue = $('#mobooking-area-value').val().trim();

        if (!countryCode || !areaValue) {
            feedbackDiv.text(mobooking_areas_params.i18n.fields_required).addClass('error').show();
            return;
        }

        const submitButton = $(this).find('button[type="submit"]');
        const originalButtonText = submitButton.text();
        submitButton.prop('disabled', true).text(mobooking_areas_params.i18n.adding);

        $.ajax({
            url: mobooking_areas_params.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_add_area',
                nonce: mobooking_areas_params.nonce,
                country_code: countryCode,
                area_value: areaValue
            },
            success: function(response) {
                if (response.success) {
                    feedbackDiv.text(response.data.message).removeClass('error').addClass('success').show();
                    addAreaForm[0].reset();
                    loadAreas();
                } else {
                    feedbackDiv.text(response.data.message || mobooking_areas_params.i18n.error_adding).removeClass('success').addClass('error').show();
                }
                 setTimeout(function() { feedbackDiv.fadeOut().empty(); }, 4000);
            },
            error: function() {
                feedbackDiv.text(mobooking_areas_params.i18n.error_adding).removeClass('success').addClass('error').show();
                 setTimeout(function() { feedbackDiv.fadeOut().empty(); }, 4000);
            },
            complete: function() {
                submitButton.prop('disabled', false).text(originalButtonText);
            }
        });
    });

    areasListContainer.on('click', '.mobooking-delete-area-btn', function() {
        const areaId = $(this).data('id');
        if (confirm(mobooking_areas_params.i18n.confirm_delete)) {
            $.ajax({
                url: mobooking_areas_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'mobooking_delete_area',
                    nonce: mobooking_areas_params.nonce,
                    area_id: areaId
                },
                success: function(response) {
                    if (response.success) {
                        loadAreas();
                        // Optionally show a global success message not tied to the form
                        // Example:
                        // $('<div class="mobooking-global-feedback success">' + sanitizeHTML(response.data.message) + '</div>')
                        //     .insertAfter('#mobooking-add-area-form-wrapper')
                        //     .delay(3000).fadeOut(500, function() { $(this).remove(); });
                    } else {
                        alert(sanitizeHTML(response.data.message || mobooking_areas_params.i18n.error_deleting));
                    }
                },
                error: function() {
                    alert(mobooking_areas_params.i18n.error_deleting);
                }
            });
        }
    });

    if (areasListContainer.length) {
        loadAreas();
    }
});
