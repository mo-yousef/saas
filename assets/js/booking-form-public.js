// This file now contains the MoBookingForm object.
// The wp_localize_script in functions.php makes `mobooking_booking_form_params` available,
// which contains the initial config, state, i18n strings, etc.

window.MoBookingForm = {
    // Config and State will be populated by mobooking_booking_form_params
    // which is localized by WordPress. We refer to it as this.params later.

    // Initialize the form
    init: function() {
        // mobooking_booking_form_params should be globally available here
        if (typeof mobooking_booking_form_params === 'undefined') {
            console.error('MoBooking Error: mobooking_booking_form_params not found. Form cannot initialize.');
            return;
        }
        // Assign localized params to a property of MoBookingForm for easier access
        this.params = mobooking_booking_form_params;

        // Merge initial state from params if provided (though current PHP sets it directly in the object)
        this.state = {
            currentStep: 1,
            selectedService: null,
            selectedOptions: {},
            customerDetails: {},
            discountInfo: null,
            totalPrice: 0,
            locationVerified: false,
            ... (this.params.state || {}) // Merge any state passed from PHP
        };
        // Config is directly available via this.params.config (or this.params.settings, this.params.currency etc.)

        this.bindEvents();
        this.initializeFirstStep();
        console.log('MoBooking Form initialized from booking-form-public.js', this.params);
    },

    // Bind all event handlers
    bindEvents: function() {
        // Location form
        jQuery('#mobooking-location-form').on('submit', this.handleLocationSubmit.bind(this));

        // Navigation buttons
        jQuery(document).on('click', '[data-step-back]', this.handleStepBack.bind(this));
        jQuery(document).on('click', '[data-step-next]', this.handleStepNext.bind(this));

        // Service selection
        jQuery(document).on('click', '.mobooking-service-card', this.handleServiceSelection.bind(this));

        // Customer details form
        jQuery('#mobooking-details-form').on('input change', this.handleDetailsChange.bind(this));

        // Final submission
        jQuery('#final-submit-btn').on('click', this.handleFinalSubmit.bind(this));

        // Discount code
        if (this.params.settings.allow_discount_codes) {
            jQuery('#apply-discount-btn').on('click', this.handleDiscountApplication.bind(this));
        }
    },

    // Initialize first step based on configuration
    initializeFirstStep: function() {
        if (!this.params.settings.enable_location_check) {
            // Skip location check, go directly to services
            this.state.locationVerified = true;
            this.showStep(2);
        } else {
            this.showStep(1);
        }
    },

    // Show specific step
    showStep: function(stepNumber) {
        // Hide all steps
        jQuery('.mobooking-step').removeClass('active');

        // Show target step with animation
        const targetStep = jQuery('.mobooking-step[data-step="' + stepNumber + '"]');
        setTimeout(() => {
            targetStep.addClass('active');
        }, 50);

        // Update progress
        this.updateProgress(stepNumber);
        this.state.currentStep = stepNumber;

        // Load step-specific data
        this.loadStepData(stepNumber);
    },

    // Update progress bar
    updateProgress: function(currentStep) {
        const progressPercentage = ((currentStep - 1) / 4) * 100; // Assuming 5 main steps before success (1 to 5)
        jQuery('.mobooking-progress-line-fill').css('width', progressPercentage + '%');

        jQuery('.mobooking-progress-step').each(function() {
            const stepNum = parseInt(jQuery(this).data('step'));
            jQuery(this).removeClass('active completed');

            if (stepNum < currentStep) {
                jQuery(this).addClass('completed');
                jQuery(this).find('.step-number').text(''); // Clear number, show checkmark (CSS handled)
            } else if (stepNum === currentStep) {
                jQuery(this).addClass('active');
                jQuery(this).find('.step-number').text(stepNum);
            } else {
                jQuery(this).find('.step-number').text(stepNum);
            }
        });
    },

    // Load step-specific data
    loadStepData: function(stepNumber) {
        switch(stepNumber) {
            case 2:
                this.loadServices();
                break;
            case 3:
                this.loadServiceOptions();
                break;
            case 4:
                this.updateSummary(); // Ensure summary is up-to-date when entering step 4
                break;
            case 5:
                this.loadReviewData();
                this.updateSummary(); // Ensure final summary is also up-to-date
                break;
        }
    },

    // Handle location form submission
    handleLocationSubmit: function(e) {
        e.preventDefault();

        const zipCode = jQuery('#mobooking-zip').val().trim();
        const countryCode = jQuery('#mobooking-country').val().trim();

        if (!this.validateLocationForm(zipCode, countryCode)) {
            return;
        }

        this.checkServiceArea(zipCode, countryCode);
    },

    // Validate location form
    validateLocationForm: function(zipCode, countryCode) {
        const feedback = jQuery('#mobooking-location-feedback');

        if (!zipCode) {
            this.showFeedback(feedback, 'error', this.params.i18n.zipRequired);
            return false;
        }

        if (!countryCode) {
            this.showFeedback(feedback, 'error', this.params.i18n.countryRequired);
            return false;
        }

        this.showFeedback(feedback, '', '', true); // Clear previous feedback
        return true;
    },

    // Check service area via AJAX
    checkServiceArea: function(zipCode, countryCode) {
        const submitBtn = jQuery('#mobooking-location-form button[type="submit"]');
        const originalText = submitBtn.html();
        const feedback = jQuery('#mobooking-location-feedback');

        submitBtn.prop('disabled', true).html('<div class="mobooking-spinner"></div> ' + this.params.i18n.checking);
        this.showFeedback(feedback, 'info', this.params.i18n.checking);

        jQuery.ajax({
            url: this.params.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mobooking_check_service_area',
                nonce: this.params.nonce,
                zip_code: zipCode,
                country_code: countryCode,
                tenant_id: this.params.tenantId
            },
            success: (response) => {
                if (response.success && response.data.serviced) {
                    this.showFeedback(feedback, 'success', response.data.message);
                    this.state.locationVerified = true;
                    setTimeout(() => this.showStep(2), 1500);
                } else {
                    this.showFeedback(feedback, 'error', response.data.message || 'Service area not available');
                }
            },
            error: () => {
                this.showFeedback(feedback, 'error', this.params.i18n.connectionError);
            },
            complete: () => {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    },

    // Load available services
    loadServices: function() {
        const container = jQuery('#mobooking-services-container');
        container.html('<div class="mobooking-loading"><div class="mobooking-spinner"></div>' + this.params.i18n.loadingServices + '</div>');
        this.showFeedback(jQuery('#mobooking-services-feedback'), '', '', true); // Clear feedback

        jQuery.ajax({
            url: this.params.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mobooking_get_public_services',
                nonce: this.params.nonce,
                tenant_id: this.params.tenantId
            },
            success: (response) => {
                if (response.success && response.data && response.data.length > 0) {
                    this.displayServices(response.data);
                } else {
                    container.html('<p>' + (response.data?.message || this.params.i18n.noServicesAvailable) + '</p>');
                }
            },
            error: () => {
                container.html('<p>' + this.params.i18n.connectionError + '</p>');
                this.showFeedback(jQuery('#mobooking-services-feedback'), 'error', this.params.i18n.connectionError);
            }
        });
    },

    // Display services
    displayServices: function(services) {
        const container = jQuery('#mobooking-services-container');
        let html = '';
        console.log('[MoBooking JS Debug] Displaying services. Received data:', services);

        services.forEach((service, index) => {
            console.log(`[MoBooking JS Debug] Processing service ${index}:`, service);
            if (!service) {
                console.error(`[MoBooking JS Debug] Service object at index ${index} is null or undefined.`);
                html += `<div>Error: Service data is invalid for one item.</div>`;
                return; // Skip this iteration
            }

            const priceDisplay = this.params.settings.show_pricing ?
                `<div class="mobooking-service-price">${this.params.currency.symbol}${this.formatPrice(service.price || 0)}</div>` : '';

            html += `
                <div class="mobooking-service-card" data-service-id="${service.service_id}" data-service-price="${service.price || 0}">
                    <div class="mobooking-service-header">
                        <div class="mobooking-service-icon">
                            <i class="${this.escapeHtml(service.icon || 'fas fa-broom')}"></i>
                        </div>
                        <div class="mobooking-service-info">
                            <div class="mobooking-service-name">${this.escapeHtml(service.name)}</div>
                            ${priceDisplay}
                        </div>
                        <input type="radio" name="selected_service" value="${service.service_id}" class="sr-only" aria-labelledby="service-name-${service.service_id}">
                    </div>
                    ${service.description ? `<div class="mobooking-service-description" id="service-desc-${service.service_id}">${this.escapeHtml(service.description)}</div>` : ''}
                    <div class="mobooking-service-meta">
                        ${service.duration ? `<div><i class="fas fa-clock"></i> ${this.escapeHtml(service.duration.toString())} min</div>` : ''}
                        ${service.category ? `<div><i class="fas fa-tag"></i> ${this.escapeHtml(service.category)}</div>` : ''}
                    </div>
                </div>
            `;
        });

        container.html(html);
    },

    // Handle service selection
    handleServiceSelection: function(e) {
        const card = jQuery(e.currentTarget);
        const serviceId = card.data('service-id');
        const servicePrice = parseFloat(card.data('service-price') || 0);
        const serviceName = card.find('.mobooking-service-name').text();

        jQuery('.mobooking-service-card').removeClass('selected');
        card.addClass('selected');
        card.find('input[type="radio"]').prop('checked', true);

        this.state.selectedService = {
            id: serviceId,
            name: serviceName,
            price: servicePrice
        };
        this.state.selectedOptions = {}; // Reset options when service changes

        jQuery('[data-step-next="3"]').prop('disabled', false);
        this.showFeedback(jQuery('#mobooking-services-feedback'), '', '', true); // Clear feedback
    },

    // Load service options
    loadServiceOptions: function() {
        if (!this.state.selectedService) {
            jQuery('#mobooking-service-options').html(`<p>${this.params.i18n.selectServiceRequired || 'No service selected.'}</p>`);
            return;
        }

        const container = jQuery('#mobooking-service-options');
        container.html('<div class="mobooking-loading"><div class="mobooking-spinner"></div>Loading service options...</div>');
        this.showFeedback(jQuery('#mobooking-options-feedback'), '', '', true);

        jQuery.ajax({
            url: this.params.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mobooking_get_service_options',
                nonce: this.params.nonce,
                service_id: this.state.selectedService.id,
                tenant_id: this.params.tenantId
            },
            success: (response) => {
                if (response.success && response.data) {
                    this.displayServiceOptions(response.data);
                } else {
                    container.html('<p>No additional options for this service.</p>');
                }
                this.updateSummary(); // Update summary after loading options
            },
            error: () => {
                container.html('<p>' + this.params.i18n.connectionError + '</p>');
            }
        });
    },

    // Display service options
    displayServiceOptions: function(options) {
        const container = jQuery('#mobooking-service-options');

        if (!options || options.length === 0) {
            container.html('<p>No additional options for this service.</p>');
            return;
        }

        let html = '';
        options.forEach(option => {
            html += this.generateOptionHTML(option);
        });

        container.html(html);
        container.find('input, select, textarea').on('change input', this.handleOptionChange.bind(this));
    },

    generateOptionHTML: function(option) {
        const required = option.is_required == '1' ? ' required' : ''; // Corrected: check against '1'
        const requiredIndicator = option.is_required == '1' ? '<span class="mobooking-required">*</span>' : '';
        const priceDisplay = this.formatOptionPrice(option);
        const optionIdHtml = `option_${this.escapeHtml(option.option_id.toString())}`;

        let content = '';
        switch(option.option_type) {
            case 'checkbox':
                content = `
                    <label>
                        <input type="checkbox" name="${optionIdHtml}" value="1"
                               data-option-id="${option.option_id}" data-price="${option.price_impact || 0}"
                               data-price-type="${option.price_impact_type || 'fixed'}"${required}>
                        ${this.escapeHtml(option.name)} ${priceDisplay} ${requiredIndicator}
                    </label>`;
                break;
            case 'text':
                content = `
                    <label for="${optionIdHtml}" class="mobooking-label">
                        ${this.escapeHtml(option.name)} ${priceDisplay} ${requiredIndicator}
                    </label>
                    <input type="text" id="${optionIdHtml}" name="${optionIdHtml}"
                           class="mobooking-input" data-option-id="${option.option_id}"
                           data-price="${option.price_impact || 0}" data-price-type="${option.price_impact_type || 'fixed'}"${required}>`;
                break;
            case 'textarea':
                 content = `
                    <label for="${optionIdHtml}" class="mobooking-label">
                        ${this.escapeHtml(option.name)} ${priceDisplay} ${requiredIndicator}
                    </label>
                    <textarea id="${optionIdHtml}" name="${optionIdHtml}"
                              class="mobooking-textarea" data-option-id="${option.option_id}"
                              data-price="${option.price_impact || 0}" data-price-type="${option.price_impact_type || 'fixed'}"${required}></textarea>`;
                break;
            default:
                return `<!-- Unsupported option type: ${this.escapeHtml(option.option_type)} -->`;
        }
        return `
            <div class="mobooking-form-group">
                ${content}
                ${option.description ? `<div class="option-description">${this.escapeHtml(option.description)}</div>` : ''}
            </div>
        `;
    },

    formatOptionPrice: function(option) {
        if (!option.price_impact || parseFloat(option.price_impact) == 0) {
            return '';
        }
        const price = parseFloat(option.price_impact);
        if (option.price_impact_type === 'percentage') {
            return `<span class="option-price">(+${price}%)</span>`;
        } else {
            return `<span class="option-price">(+${this.params.currency.symbol}${this.formatPrice(price)})</span>`;
        }
    },

    handleOptionChange: function(e) {
        const input = jQuery(e.target);
        const optionId = input.data('option-id').toString(); // Ensure string for key
        const price = parseFloat(input.data('price') || 0);
        const priceType = input.data('price-type') || 'fixed';
        const optionName = input.closest('.mobooking-form-group').find('label').first().text().trim().replace(/\s*\*.*/, '');


        if (input.is(':checkbox')) {
            if (input.is(':checked')) {
                this.state.selectedOptions[optionId] = {
                    name: optionName, value: input.val(), price: price, priceType: priceType
                };
            } else {
                delete this.state.selectedOptions[optionId];
            }
        } else {
            const value = input.val().trim();
            if (value) {
                this.state.selectedOptions[optionId] = {
                    name: optionName, value: value, price: price, priceType: priceType
                };
            } else {
                delete this.state.selectedOptions[optionId];
            }
        }
        this.updateSummary();
    },

    updateSummary: function() {
        if (!this.state.selectedService) return;

        let subtotal = parseFloat(this.state.selectedService.price);
        let summaryHtml = `
            <div class="mobooking-summary-item">
                <span>${this.escapeHtml(this.state.selectedService.name)}</span>
                <span>${this.params.currency.symbol}${this.formatPrice(this.state.selectedService.price)}</span>
            </div>`;

        Object.values(this.state.selectedOptions).forEach(option => {
            let optionPrice = 0;
            if (option.priceType === 'percentage') {
                optionPrice = (parseFloat(this.state.selectedService.price) * option.price) / 100;
            } else {
                optionPrice = option.price;
            }
            subtotal += optionPrice;
            summaryHtml += `
                <div class="mobooking-summary-item">
                    <span>+ ${this.escapeHtml(option.name)}</span>
                    <span>${this.params.currency.symbol}${this.formatPrice(optionPrice)}</span>
                </div>`;
        });

        let finalTotal = subtotal;
        let discountDisplay = '';
        if (this.state.discountInfo) {
            const discountAmount = this.calculateDiscount(subtotal, this.state.discountInfo);
            finalTotal = Math.max(0, subtotal - discountAmount);
            discountDisplay = `
                <div class="mobooking-summary-item">
                    <span>Discount (${this.escapeHtml(this.state.discountInfo.code || '')})</span>
                    <span>-${this.params.currency.symbol}${this.formatPrice(discountAmount)}</span>
                </div>`;
             jQuery('#pricing-discount').text(`-${this.params.currency.symbol}${this.formatPrice(discountAmount)}`);
             jQuery('.discount-applied').removeClass('hidden');
        } else {
            jQuery('.discount-applied').addClass('hidden');
        }

        summaryHtml += discountDisplay;
        summaryHtml += `
            <div class="mobooking-summary-total">
                <span>Total:</span>
                <span>${this.params.currency.symbol}${this.formatPrice(finalTotal)}</span>
            </div>`;

        this.state.totalPrice = finalTotal;

        jQuery('#mobooking-summary-content, #mobooking-summary-content-step4, #mobooking-final-summary').html(summaryHtml);
        jQuery('#pricing-subtotal').text(this.params.currency.symbol + this.formatPrice(subtotal));
        jQuery('#pricing-total').text(this.params.currency.symbol + this.formatPrice(finalTotal));
    },

    handleStepBack: function(e) {
        const targetStep = parseInt(jQuery(e.target).closest('[data-step-back]').data('step-back'));
        this.showStep(targetStep);
    },

    handleStepNext: function(e) {
        const button = jQuery(e.target).closest('[data-step-next]');
        const targetStep = parseInt(button.data('step-next'));

        if (this.validateCurrentStep()) {
            this.showStep(targetStep);
        }
    },

    validateCurrentStep: function() {
        let isValid = true;
        const feedbackElementId = `#mobooking-${this.getStepName(this.state.currentStep)}-feedback`;
        const feedback = jQuery(feedbackElementId);

        switch(this.state.currentStep) {
            case 1: // Location (if location check is enabled)
                // Validation happens in handleLocationSubmit
                isValid = this.state.locationVerified;
                break;
            case 2: // Service Selection
                if (!this.state.selectedService) {
                    this.showFeedback(feedback, 'error', this.params.i18n.selectServiceRequired);
                    isValid = false;
                } else {
                   this.showFeedback(feedback, '', '', true); // Clear
                }
                break;
            case 3: // Service Options - check required options
                let allRequiredOptionsFilled = true;
                jQuery('#mobooking-service-options .mobooking-form-group').each((idx, group) => {
                    const input = jQuery(group).find('input[required], textarea[required], select[required]');
                    if (input.length > 0) {
                        if (input.is(':checkbox') && !input.is(':checked')) {
                            allRequiredOptionsFilled = false;
                        } else if (input.val() === null || input.val().trim() === '') {
                            allRequiredOptionsFilled = false;
                        }
                    }
                });
                if (!allRequiredOptionsFilled) {
                    this.showFeedback(feedback, 'error', this.params.i18n.fillRequiredFields);
                    isValid = false;
                } else {
                    this.showFeedback(feedback, '', '', true); // Clear
                }
                break;
            case 4: // Customer Details
                isValid = this.validateCustomerDetails();
                break;
        }
        return isValid;
    },

    getStepName: function(stepNumber) {
        const names = ["", "location", "services", "options", "details", "review", "success"];
        return names[stepNumber] || "unknown";
    },

    handleDetailsChange: function() {
        this.updateCustomerDetailsState();
        // Optionally add real-time validation feedback here
    },

    updateCustomerDetailsState: function() {
        this.state.customerDetails = {
            name: jQuery('#customer-name').val().trim(),
            email: jQuery('#customer-email').val().trim(),
            phone: jQuery('#customer-phone').val().trim(),
            address: jQuery('#service-address').val().trim(),
            date: jQuery('#preferred-date').val(),
            time: jQuery('#preferred-time').val(),
            instructions: jQuery('#special-instructions').val().trim()
        };
    },

    validateCustomerDetails: function() {
        this.updateCustomerDetailsState(); // Make sure state is current
        const feedback = jQuery('#mobooking-details-feedback');
        const details = this.state.customerDetails;

        if (!details.name || !details.email || !details.phone || !details.address) {
            this.showFeedback(feedback, 'error', this.params.i18n.fillRequiredFields);
            return false;
        }
        if (!this.isValidEmail(details.email)) {
            this.showFeedback(feedback, 'error', this.params.i18n.invalidEmail);
            return false;
        }
        this.showFeedback(feedback, '', '', true); // Clear feedback
        return true;
    },

    loadReviewData: function() {
        let reviewHtml = `<div class="mobooking-review-section"><h4>Service Details</h4>`;
        if (this.state.selectedService) {
            reviewHtml += `<div class="review-item"><strong>Service:</strong> <span>${this.escapeHtml(this.state.selectedService.name)}</span></div>`;
            reviewHtml += `<div class="review-item"><strong>Base Price:</strong> <span>${this.params.currency.symbol}${this.formatPrice(this.state.selectedService.price)}</span></div>`;
        }
        if (Object.keys(this.state.selectedOptions).length > 0) {
            reviewHtml += '<h5>Options:</h5><ul>';
            Object.values(this.state.selectedOptions).forEach(opt => {
                reviewHtml += `<li>${this.escapeHtml(opt.name)}: ${this.escapeHtml(opt.value)}`;
                if (opt.price > 0) {
                     let optPriceDisplay = opt.priceType === 'percentage' ? `(+${opt.price}%)` : `(+${this.params.currency.symbol}${this.formatPrice(opt.price)})`;
                     reviewHtml += ` <span class="option-price">${optPriceDisplay}</span>`;
                }
                reviewHtml += `</li>`;
            });
            reviewHtml += '</ul>';
        }
        reviewHtml += `</div>`;

        reviewHtml += `<div class="mobooking-review-section"><h4>Customer Information</h4>`;
        const cd = this.state.customerDetails;
        reviewHtml += `<div class="review-item"><strong>Name:</strong> <span>${this.escapeHtml(cd.name)}</span></div>`;
        reviewHtml += `<div class="review-item"><strong>Email:</strong> <span>${this.escapeHtml(cd.email)}</span></div>`;
        reviewHtml += `<div class="review-item"><strong>Phone:</strong> <span>${this.escapeHtml(cd.phone)}</span></div>`;
        reviewHtml += `<div class="review-item"><strong>Address:</strong> <span>${this.escapeHtml(cd.address)}</span></div>`;
        if (cd.date) reviewHtml += `<div class="review-item"><strong>Preferred Date:</strong> <span>${this.escapeHtml(cd.date)}</span></div>`;
        if (cd.time) reviewHtml += `<div class="review-item"><strong>Preferred Time:</strong> <span>${this.escapeHtml(cd.time)}</span></div>`;
        if (cd.instructions) reviewHtml += `<div class="review-item"><strong>Instructions:</strong> <span>${this.escapeHtml(cd.instructions)}</span></div>`;
        reviewHtml += `</div>`;

        jQuery('#mobooking-review-details').html(reviewHtml);
    },

    handleDiscountApplication: function() {
        const code = jQuery('#discount-code').val().trim();
        const feedback = jQuery('#discount-feedback');

        if (!code) {
            this.showFeedback(feedback, 'error', this.params.i18n.enterDiscountCode);
            return;
        }

        const button = jQuery('#apply-discount-btn');
        const originalText = button.text();
        button.prop('disabled', true).text('Applying...');
        this.showFeedback(feedback, 'info', 'Applying discount...', false); // Don't auto-hide info

        jQuery.ajax({
            url: this.params.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mobooking_validate_discount',
                nonce: this.params.nonce,
                discount_code: code,
                tenant_id: this.params.tenantId,
                subtotal: this.state.totalPrice // This might be an issue if totalPrice already includes discount
            },
            success: (response) => {
                if (response.success && response.data) { // Ensure response.data exists
                    this.state.discountInfo = response.data; // Store full discount info
                    this.showFeedback(feedback, 'success', this.params.i18n.discountApplied);
                    this.updateSummary(); // Recalculate and display with discount
                } else {
                    this.state.discountInfo = null; // Clear previous discount if invalid
                    this.showFeedback(feedback, 'error', response.data?.message || this.params.i18n.invalidDiscount);
                    this.updateSummary(); // Recalculate without discount
                }
            },
            error: () => {
                this.state.discountInfo = null;
                this.showFeedback(feedback, 'error', this.params.i18n.connectionError);
                this.updateSummary();
            },
            complete: () => {
                button.prop('disabled', false).text(originalText);
            }
        });
    },

    handleFinalSubmit: function() {
        const button = jQuery('#final-submit-btn');
        const originalText = button.html();
        const feedback = jQuery('#mobooking-review-feedback');

        button.prop('disabled', true).html('<div class="mobooking-spinner"></div> ' + this.params.i18n.submitting);
        this.showFeedback(feedback, 'info', this.params.i18n.submitting, false);

        const submissionData = {
            action: 'mobooking_create_booking',
            nonce: this.params.nonce,
            tenant_id: this.params.tenantId,
            selected_services: JSON.stringify([{ // Assuming single service selection model for now
                service_id: this.state.selectedService.id,
                name: this.state.selectedService.name, // For easier server-side validation/logging
                price: this.state.selectedService.price,
                configured_options: this.state.selectedOptions // Send selected options
            }]),
            customer_details: JSON.stringify(this.state.customerDetails), // Renamed from booking_details
            discount_info: this.state.discountInfo ? JSON.stringify(this.state.discountInfo) : null,
            zip_code: jQuery('#mobooking-zip').val() || (this.state.customerDetails.zip_code || ''), // Ensure zip is passed
            country_code: jQuery('#mobooking-country').val() || (this.state.customerDetails.country_code || ''), // Ensure country is passed
            pricing: JSON.stringify({ // Send calculated pricing for server-side verification
                subtotal: jQuery('#pricing-subtotal').text().replace(this.params.currency.symbol, ''),
                discount: jQuery('#pricing-discount').text().replace(this.params.currency.symbol, '').replace('-', ''),
                total: this.state.totalPrice
            })
        };

        jQuery.ajax({
            url: this.params.ajaxUrl,
            type: 'POST',
            data: submissionData,
            success: (response) => {
                if (response.success && response.data) {
                    this.handleBookingSuccess(response.data);
                } else {
                    this.showFeedback(feedback, 'error', response.data?.message || 'Booking submission failed');
                }
            },
            error: () => {
                this.showFeedback(feedback, 'error', this.params.i18n.connectionError);
            },
            complete: () => {
                button.prop('disabled', false).html(originalText);
            }
        });
    },

    handleBookingSuccess: function(data) {
        this.showStep(6); // Success step

        const successDetailsHtml = `
            <div class="success-detail">
                <strong>Booking Reference:</strong> <span>${this.escapeHtml(data.booking_reference || 'N/A')}</span>
            </div>
            <div class="success-detail">
                <strong>Service:</strong> <span>${this.escapeHtml(this.state.selectedService.name)}</span>
            </div>
            <div class="success-detail">
                <strong>Customer:</strong> <span>${this.escapeHtml(this.state.customerDetails.name)}</span>
            </div>
            <div class="success-detail">
                <strong>Email:</strong> <span>${this.escapeHtml(this.state.customerDetails.email)}</span>
            </div>
            <div class="success-detail">
                <strong>Total:</strong> <span>${this.params.currency.symbol}${this.formatPrice(data.final_total || this.state.totalPrice)}</span>
            </div>
            <p style="margin-top: 1rem; color: var(--muted-foreground);">
                You will receive a confirmation email shortly at ${this.escapeHtml(this.state.customerDetails.email)}.
            </p>`;

        jQuery('#success-details').html(successDetailsHtml);
    },

    showFeedback: function(element, type, message, autoHide = true) {
        element.removeClass('success error info').hide(); // Clear previous classes and hide
        if (message) {
            element.addClass(type).html(message).show(); // Use html to allow spinner
        }

        if (autoHide && (type === 'success' || type === 'error')) { // Auto-hide success and error
            setTimeout(() => {
                // Check if the message is still the same before hiding,
                // to prevent hiding a new message that appeared quickly.
                if (element.html() === message) {
                    element.fadeOut();
                }
            }, 3000);
        }
    },

    formatPrice: function(price) {
        return parseFloat(price || 0).toFixed(2);
    },

    calculateDiscount: function(subtotal, discountInfo) {
        if (!discountInfo || !discountInfo.value) return 0; // value might be discount_value

        let discountValue = parseFloat(discountInfo.value || discountInfo.discount_value || 0);

        if (discountInfo.type === 'percentage' || discountInfo.discount_type === 'percentage') {
            return (subtotal * discountValue) / 100;
        } else { // Fixed amount
            return Math.min(discountValue, subtotal); // Discount cannot be more than subtotal
        }
    },

    isValidEmail: function(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    },

    escapeHtml: function(text) {
        if (typeof text !== 'string') return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Initialize when DOM is ready and params are available
jQuery(document).ready(function() {
    if (typeof window.MoBookingForm !== 'undefined' && typeof window.MoBookingForm.init === 'function') {
        // The MoBookingForm object is defined above.
        // wp_localize_script makes `mobooking_booking_form_params` available globally.
        // The init method will use `mobooking_booking_form_params`.
        window.MoBookingForm.init();
    } else {
        console.error('MoBookingForm critical error: Main object not defined.');
    }
});
