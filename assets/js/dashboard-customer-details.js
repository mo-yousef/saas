jQuery(document).ready(function($) {
    'use strict';

    const editBtn = $('#mobooking-edit-customer-btn');
    const saveBtn = $('#mobooking-save-customer-btn');
    const editableFields = $('.form-table td[data-field]');

    editBtn.on('click', function() {
        editableFields.each(function() {
            const field = $(this);
            const value = field.text();
            const input = $('<input type="text" class="regular-text">').val(value);
            field.html(input);
        });
        editBtn.hide();
        saveBtn.show();
    });

    saveBtn.on('click', function() {
        const updatedData = {
            customer_id: new URLSearchParams(window.location.search).get('customer_id')
        };

        editableFields.each(function() {
            const field = $(this);
            const fieldName = field.data('field');
            const value = field.find('input').val();
            updatedData[fieldName] = value;
        });

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mobooking_update_customer_details',
                nonce: mobooking_customer_details_params.nonce,
                customer_data: updatedData
            },
            success: function(response) {
                if (response.success) {
                    editableFields.each(function() {
                        const field = $(this);
                        const value = field.find('input').val();
                        field.text(value);
                    });
                    editBtn.show();
                    saveBtn.hide();
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function() {
                alert('An error occurred while saving the data.');
            }
        });
    });
});
