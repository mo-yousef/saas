jQuery(document).ready(function($) {
    'use strict';

    // --- Edit Customer Modal Logic ---
    $('#mobooking-edit-customer-btn').on('click', function(e) {
        e.preventDefault();

        // This is a bit verbose, but necessary to get the form fields into the dialog
        const formHtml = $('#mobooking-edit-customer-form').html();

        const editDialog = new MoBookingDialog({
            title: 'Edit Customer',
            content: `<form id="mobooking-dialog-edit-form">${formHtml}</form>`,
            buttons: [
                {
                    label: 'Cancel',
                    class: 'secondary',
                    onClick: (dialog) => dialog.close()
                },
                {
                    label: 'Save Changes',
                    class: 'primary',
                    onClick: (dialog) => {
                        const form = dialog.findElement('#mobooking-dialog-edit-form');
                        const formData = $(form).serializeArray();
                        let data = {
                            action: 'mobooking_update_customer_details',
                            nonce: mobooking_customers_params.nonce
                        };

                        formData.forEach(item => {
                            data[item.name] = item.value;
                        });

                        const saveBtn = dialog.findElement('.btn-primary');
                        saveBtn.textContent = 'Saving...';
                        saveBtn.disabled = true;

                        $.post(mobooking_customers_params.ajax_url, data)
                            .done(function(response) {
                                if (response.success) {
                                    location.reload();
                                } else {
                                    alert('Error: ' + (response.data.message || 'Could not update customer.'));
                                    saveBtn.textContent = 'Save Changes';
                                    saveBtn.disabled = false;
                                }
                            })
                            .fail(function() {
                                alert('An unknown error occurred.');
                                saveBtn.textContent = 'Save Changes';
                                saveBtn.disabled = false;
                            });
                    }
                }
            ]
        });
        editDialog.show();
    });

});
