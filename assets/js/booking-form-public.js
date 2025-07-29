jQuery(document).ready(function ($) {
    "use strict";

    const MOB_PARAMS = window.mobooking_booking_form_params || {};
    const TENANT_ID = MOB_PARAMS.tenant_id || null;
    const FORM_NONCE = MOB_PARAMS.nonce || null;
    const AJAX_URL = MOB_PARAMS.ajax_url || "/wp-admin/admin-ajax.php";

    let services = [];
    let selectedService = null;

    function loadServices() {
        $.ajax({
            url: AJAX_URL,
            type: 'POST',
            data: {
                action: 'mobooking_get_services',
                nonce: FORM_NONCE,
                tenant_id: TENANT_ID,
            },
            success: function(response) {
                if (response.success) {
                    services = response.data;
                    renderServices();
                }
            }
        });
    }

    function renderServices() {
        const container = $('#mobooking-services-container');
        container.empty();
        services.forEach(service => {
            const card = $(`
                <div class="mobooking-service-card" data-service-id="${service.service_id}">
                    <h3>${service.name}</h3>
                    <p>${service.description}</p>
                    <p>Price: ${service.price}</p>
                </div>
            `);
            card.on('click', () => {
                $('.mobooking-service-card').removeClass('selected');
                card.addClass('selected');
                selectedService = service;
                renderServiceOptions();
            });
            container.append(card);
        });
    }

    function renderServiceOptions() {
        const container = $('#mobooking-service-options-container');
        container.empty();
        if (selectedService && selectedService.options) {
            selectedService.options.forEach(option => {
                const optionEl = $(`
                    <div>
                        <label>
                            <input type="checkbox" name="service_options[]" value="${option.option_id}">
                            ${option.name} (+${option.price_impact_value})
                        </label>
                    </div>
                `);
                container.append(optionEl);
            });
        }
    }

    if (TENANT_ID && FORM_NONCE) {
        loadServices();
    }
});
