jQuery(document).ready(function($) {
    'use strict';

    const listContainer = $('#mobooking-discounts-list');
    const paginationContainer = $('#mobooking-discounts-pagination-container');
    const formContainer = $('#mobooking-discount-form-container');
    const form = $('#mobooking-discount-form');
    const formTitle = $('#mobooking-discount-form-title');
    const discountIdField = $('#mobooking-discount-id');
    const feedbackDiv = $('#mobooking-discount-form-feedback');
    const itemTemplate = $('#mobooking-discount-item-template').html();

    let currentFilters = { paged: 1, limit: 20 }; // Basic pagination, filters can be added later

    function sanitizeHTML(str) {
        if (typeof str !== 'string') return '';
        var temp = document.createElement('div');
        temp.textContent = str;
        return temp.innerHTML;
    }

    function renderTemplate(templateHtml, data) {
        let template = templateHtml;
        for (const key in data) {
            const value = (data[key] === null || typeof data[key] === 'undefined') ? '' : data[key];
            template = template.replace(new RegExp('<%=\\s*' + key + '\\s*%>', 'g'), sanitizeHTML(String(value)));
        }
        return template;
    }

    function loadDiscounts(page = 1) {
        currentFilters.paged = page;
        listContainer.html('<tr><td colspan="7"><p>' + (mobooking_discounts_params.i18n.loading || 'Loading...') + '</p></td></tr>');
        paginationContainer.empty();

        $.ajax({
            url: mobooking_discounts_params.ajax_url,
            type: 'POST',
            data: { action: 'mobooking_get_discounts', nonce: mobooking_discounts_params.nonce, ...currentFilters },
            success: function(response) {
                listContainer.empty();
                if (response.success && response.data.bookings && response.data.bookings.length) { // PHP sends 'bookings' key from get_bookings_by_tenant, should be 'discounts'
                    // Assuming PHP sends response.data.discounts and response.data.total_count etc.
                    const discounts = response.data.discounts || response.data.bookings; // Temporary fix for key name
                    const total_count = response.data.total_count;
                    const per_page = response.data.per_page;
                    const current_page = response.data.current_page;

                    discounts.forEach(function(discount) {
                        let displayData = {...discount};
                        displayData.type_display = mobooking_discounts_params.types[discount.type] || discount.type;
                        displayData.value_display = discount.type === 'percentage' ? parseFloat(discount.value).toFixed(2) + '%' : parseFloat(discount.value).toFixed(2); // Add currency symbol later
                        displayData.expiry_date_display = discount.expiry_date || mobooking_discounts_params.i18n.never || 'Never';
                        displayData.usage_display = `${discount.times_used} / ${parseInt(discount.usage_limit,10) > 0 ? discount.usage_limit : mobooking_discounts_params.i18n.unlimited || '∞'}`;
                        displayData.status_display = mobooking_discounts_params.statuses[discount.status] || discount.status;
                        listContainer.append(renderTemplate(itemTemplate, displayData));
                    });
                    renderPagination(total_count, per_page, current_page);
                } else if (response.success) {
                    listContainer.html('<tr><td colspan="7"><p>' + (mobooking_discounts_params.i18n.no_discounts || 'No discounts found.') + '</p></td></tr>');
                } else {
                    listContainer.html('<tr><td colspan="7"><p>' + (response.data.message || mobooking_discounts_params.i18n.error_loading || 'Error.') + '</p></td></tr>');
                }
            },
            error: function() {
                listContainer.html('<tr><td colspan="7"><p>' + (mobooking_discounts_params.i18n.error_loading || 'Error.') + '</p></td></tr>');
            }
        });
    }

    function renderPagination(totalItems, itemsPerPage, currentPage) {
        paginationContainer.empty();
        const totalPages = Math.ceil(totalItems / itemsPerPage);
        if (totalPages <= 1) return;
        let paginationHtml = '<ul class="mobooking-pagination">';
        for (let i = 1; i <= totalPages; i++) {
            paginationHtml += `<li class="${i == currentPage ? 'active' : ''}"><a href="#" data-page="${i}">${i}</a></li>`;
        }
        paginationHtml += '</ul>';
        paginationContainer.html(paginationHtml);
    }

    paginationContainer.on('click', 'a', function(e) {
        e.preventDefault();
        loadDiscounts(parseInt($(this).data('page'), 10));
    });

    // Show Add New Form
    $('#mobooking-add-new-discount-btn').on('click', function() {
        form[0].reset();
        discountIdField.val('');
        formTitle.text(mobooking_discounts_params.i18n.add_new_title || 'Add New Discount Code');
        feedbackDiv.empty().removeClass('success error').hide();
        formContainer.slideDown();
    });

    // Cancel Form
    $('#mobooking-cancel-discount-form').on('click', function() {
        formContainer.slideUp();
    });

    // Edit Discount
    listContainer.on('click', '.mobooking-edit-discount-btn', function() {
        const discountId = $(this).closest('tr').data('id');
        feedbackDiv.empty().removeClass('success error').hide();
        form[0].reset(); // Clear form before populating

        $.ajax({
            url: mobooking_discounts_params.ajax_url,
            type: 'POST',
            data: { action: 'mobooking_get_discount_details', nonce: mobooking_discounts_params.nonce, discount_id: discountId },
            success: function(response) {
                if (response.success && response.data.discount) {
                    const d = response.data.discount;
                    formTitle.text(mobooking_discounts_params.i18n.edit_title || 'Edit Discount Code');
                    discountIdField.val(d.discount_id);
                    $('#mobooking-discount-code').val(d.code);
                    $('#mobooking-discount-type').val(d.type);
                    $('#mobooking-discount-value').val(d.value);
                    $('#mobooking-discount-expiry').val(d.expiry_date || '');
                    $('#mobooking-discount-limit').val(d.usage_limit || '');
                    $('#mobooking-discount-status').val(d.status);
                    formContainer.slideDown();
                } else {
                    alert(response.data.message || 'Error fetching details.');
                }
            },
            error: function() { alert('AJAX error fetching details.'); }
        });
    });

    // Delete Discount
    listContainer.on('click', '.mobooking-delete-discount-btn', function() {
        if (!confirm(mobooking_discounts_params.i18n.confirm_delete || 'Are you sure?')) return;
        const discountId = $(this).closest('tr').data('id');
        $.ajax({
            url: mobooking_discounts_params.ajax_url,
            type: 'POST',
            data: { action: 'mobooking_delete_discount', nonce: mobooking_discounts_params.nonce, discount_id: discountId },
            success: function(response) {
                if (response.success) {
                    loadDiscounts(currentFilters.paged);
                } else {
                    alert(response.data.message || 'Error deleting.');
                }
            },
            error: function() { alert('AJAX error deleting.'); }
        });
    });

    // Form Submission (Add/Edit)
    form.on('submit', function(e) {
        e.preventDefault();
        feedbackDiv.empty().removeClass('success error').hide();
        const submitButton = $('#mobooking-save-discount-btn');
        const originalButtonText = submitButton.text();
        submitButton.prop('disabled', true).text(mobooking_discounts_params.i18n.saving || 'Saving...');

        let formData = $(this).serializeArray();
        let dataToSend = { action: 'mobooking_save_discount', nonce: mobooking_discounts_params.nonce };
        formData.forEach(item => dataToSend[item.name] = item.value);

        // Ensure usage_limit is sent as empty if 0, to be stored as NULL by PHP logic
        if (dataToSend.usage_limit === '0') dataToSend.usage_limit = '';


        $.ajax({
            url: mobooking_discounts_params.ajax_url,
            type: 'POST',
            data: dataToSend,
            success: function(response) {
                if (response.success) {
                    feedbackDiv.text(response.data.message).addClass('success').show();
                    formContainer.slideUp();
                    loadDiscounts(discountIdField.val() ? currentFilters.paged : 1); // Refresh current page on edit, or go to page 1 on add
                } else {
                    feedbackDiv.text(response.data.message || 'Error saving.').addClass('error').show();
                }
            },
            error: function() {
                feedbackDiv.text('AJAX error saving.').addClass('error').show();
            },
            complete: function() {
                submitButton.prop('disabled', false).text(originalButtonText);
                 setTimeout(function() { feedbackDiv.fadeOut().empty(); }, 4000);
            }
        });
    });

    // Initialize Datepicker
    if (typeof $.fn.datepicker === 'function') {
        $('.mobooking-datepicker').datepicker({ dateFormat: 'yy-mm-dd', changeMonth: true, changeYear: true });
    } else {
        $('.mobooking-datepicker').attr('type', 'date');
    }

    loadDiscounts(); // Initial load
});
