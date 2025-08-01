jQuery(document).ready(function($) {
    'use strict';

    const listContainer = $('#mobooking-discounts-list');
    const paginationContainer = $('#mobooking-discounts-pagination-container');
    const modal = $('#mobooking-discount-modal');
    const form = $('#mobooking-discount-form');
    const formTitle = $('#mobooking-discount-form-title');
    const discountIdField = $('#mobooking-discount-id');
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
                // Correctly access discounts and pagination data from the structured response
                if (response.success && response.data && response.data.discounts && response.data.discounts.length) {
                    const discounts = response.data.discounts;
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
                } else if (response.success && response.data && response.data.discounts && response.data.discounts.length === 0) { // Explicitly check for empty discounts array
                    listContainer.html('<tr><td colspan="7"><p>' + (mobooking_discounts_params.i18n.no_discounts || 'No discounts found.') + '</p></td></tr>');
                } else {
                    // Handle cases where response.data might be missing or message is not in response.data.message
                    const message = (response.data && response.data.message) ? sanitizeHTML(response.data.message) : (mobooking_discounts_params.i18n.error_loading || 'Error.');
                    listContainer.html('<tr><td colspan="7"><p>' + message + '</p></td></tr>');
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
        modal.fadeIn();
    });

    // Cancel Form
    modal.on('click', '.mobooking-modal-close, .mobooking-modal-backdrop', function(e) {
        if ($(e.target).is('.mobooking-modal-close') || $(e.target).is('.mobooking-modal-backdrop')) {
            modal.fadeOut();
        }
    });

    // Edit Discount
    listContainer.on('click', '.mobooking-edit-discount-btn', function() {
        const discountId = $(this).closest('tr').data('id');
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
                    modal.fadeIn();
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
                    window.showAlert(response.data.message, 'success');
                    modal.fadeOut();
                    loadDiscounts(discountIdField.val() ? currentFilters.paged : 1); // Refresh current page on edit, or go to page 1 on add
                } else {
                    window.showAlert(response.data.message || 'Error saving.', 'error');
                }
            },
            error: function() {
                window.showAlert('AJAX error saving.', 'error');
            },
            complete: function() {
                submitButton.prop('disabled', false).text(originalButtonText);
            }
        });
    });

    // Initialize Datepicker
    if (typeof $.fn.datepicker === 'function') {
        $('.mobooking-datepicker').datepicker({ dateFormat: 'yy-mm-dd', changeMonth: true, changeYear: true });
    } else {
        $('.mobooking-datepicker').attr('type', 'date');
    }

    // loadDiscounts(); // Initial load is now handled by PHP.
    // Ensure pagination links from PHP work with the existing loadDiscounts logic.
    // The PHP pagination uses hrefs like #?paged=X, JS should preventDefault and use the page number.
    // The current paginationContainer.on('click', 'a', ...) should handle this if WP's paginate_links outputs simple hrefs.
    // If it's a full URL, the selector might need adjustment or the link format in PHP.
    // For now, assuming current JS pagination handler is sufficient for links rendered by PHP.
});
