jQuery(document).ready(function($) {
    'use strict';

    // --- Modal Handling ---
    function openModal($modal) {
        $modal.show();
    }

    function closeModal($modal) {
        $modal.hide();
    }

    $('.mobooking-modal-close').on('click', function() {
        closeModal($(this).closest('.mobooking-modal'));
    });

    $(window).on('click', function(event) {
        if ($(event.target).is('.mobooking-modal')) {
            closeModal($(event.target));
        }
    });

    // --- Notes Modal ---
    const $notesModal = $('#mobooking-notes-modal');
    const $notesForm = $('#mobooking-notes-form');
    const $notesContent = $('#customer-notes-content');

    $('#mobooking-add-note-btn').on('click', function() {
        openModal($notesModal);
    });

    $notesForm.on('submit', function(e) {
        e.preventDefault();
        const $submitBtn = $(this).find('button[type="submit"]');
        $submitBtn.prop('disabled', true).text('Saving...');

        const data = {
            action: 'mobooking_update_customer_note',
            nonce: mobooking_customers_params.nonce, // Assuming this is available
            customer_id: $(this).find('input[name="customer_id"]').val(),
            customer_notes: $(this).find('textarea[name="customer_notes"]').val()
        };

        $.post(mobooking_customers_params.ajax_url, data, function(response) {
            if (response.success) {
                const newNotes = data.customer_notes ? data.customer_notes.replace(/\n/g, '<br>') : '<p class="text-muted">No notes for this customer yet.</p>';
                $notesContent.html(data.customer_notes ? `<p>${newNotes}</p>` : newNotes);
                closeModal($notesModal);
                // Maybe show a toast message here if available
            } else {
                alert('Error: ' + response.data.message);
            }
        }).fail(function() {
            alert('An unknown error occurred.');
        }).always(function() {
            $submitBtn.prop('disabled', false).text('Save Notes');
        });
    });

    // --- Edit Customer Modal ---
    const $editCustomerModal = $('#mobooking-edit-customer-modal');
    const $editCustomerForm = $('#mobooking-edit-customer-form');

    $('#mobooking-edit-customer-btn').on('click', function(e) {
        e.preventDefault();
        openModal($editCustomerModal);
    });

    $editCustomerForm.on('submit', function(e) {
        e.preventDefault();
        const $submitBtn = $(this).find('button[type="submit"]');
        $submitBtn.prop('disabled', true).text('Saving...');

        let formData = $(this).serializeArray();
        let data = {
            action: 'mobooking_update_customer_details',
            nonce: mobooking_customers_params.nonce
        };

        // Convert form data to a key-value object
        formData.forEach(function(item) {
            data[item.name] = item.value;
        });

        $.post(mobooking_customers_params.ajax_url, data, function(response) {
            if (response.success) {
                // Easiest way to show all changes is to reload the page
                location.reload();
            } else {
                alert('Error: ' . response.data.message);
                $submitBtn.prop('disabled', false).text('Save Changes');
            }
        }).fail(function() {
            alert('An unknown error occurred.');
            $submitBtn.prop('disabled', false).text('Save Changes');
        });
    });

});
