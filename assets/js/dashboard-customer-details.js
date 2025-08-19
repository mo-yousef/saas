jQuery(document).ready(function($) {
    'use strict';

    // --- Notes Modal Logic ---
    $('#mobooking-add-note-btn').on('click', function() {
        const $button = $(this);
        const customerId = $button.data('customer-id');
        const currentNotes = $('#customer-notes-content').text().trim();

        const notesDialog = new MoBookingDialog({
            title: 'Customer Notes',
            content: `
                <form id="mobooking-dialog-notes-form">
                    <textarea name="customer_notes" rows="8" style="width:100%;" class="mobooking-input">${currentNotes}</textarea>
                </form>
            `,
            buttons: [
                {
                    label: 'Cancel',
                    class: 'secondary',
                    onClick: (dialog) => dialog.close()
                },
                {
                    label: 'Save Notes',
                    class: 'primary',
                    onClick: (dialog) => {
                        const newNotes = $(dialog.findElement('textarea[name="customer_notes"]')).val();
                        const data = {
                            action: 'mobooking_update_customer_note',
                            nonce: mobooking_customers_params.nonce,
                            customer_id: customerId,
                            customer_notes: newNotes
                        };

                        // You can add a loading state to the button here
                        const saveBtn = dialog.findElement('.btn-primary');
                        saveBtn.textContent = 'Saving...';
                        saveBtn.disabled = true;

                        $.post(mobooking_customers_params.ajax_url, data)
                            .done(function(response) {
                                if (response.success) {
                                    const notesContent = newNotes ? newNotes.replace(/\n/g, '<br>') : '<p class="text-muted">No notes for this customer yet.</p>';
                                    $('#customer-notes-content').html(notesContent);
                                    dialog.close();
                                } else {
                                    alert('Error: ' + (response.data.message || 'Could not save notes.'));
                                }
                            })
                            .fail(function() {
                                alert('An unknown error occurred.');
                            })
                            .always(function() {
                                saveBtn.textContent = 'Save Notes';
                                saveBtn.disabled = false;
                            });
                    }
                }
            ]
        });
        notesDialog.show();
    });

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
