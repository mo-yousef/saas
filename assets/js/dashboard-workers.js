jQuery(document).ready(function($) {
    // Check if mobooking_workers_params are defined. If not, log an error and exit.
    if (typeof mobooking_workers_params === 'undefined' || typeof mobooking_workers_params.ajax_url === 'undefined' || typeof mobooking_workers_params.i18n === 'undefined') {
        console.error('MoBooking Error: mobooking_workers_params not defined or incomplete. AJAX calls and internationalization may fail.');
        // Optionally, you could disable form submissions here or show a user-facing error.
        // For now, we'll let it proceed and potentially fail at the AJAX call, which will be caught by .fail()
    }

    const feedbackArea = $('#mobooking-feedback-area');
    const feedbackP = feedbackArea.find('p');

    function showFeedback(message, isSuccess, customClass = '') {
        feedbackP.html(message);
        // Reset classes first
        feedbackArea.removeClass('notice-success notice-error mobooking-worker-created-success is-dismissible');

        if (isSuccess) {
            feedbackArea.addClass('notice-success is-dismissible');
            if (customClass) {
                feedbackArea.addClass(customClass);
            }
        } else {
            feedbackArea.addClass('notice-error is-dismissible');
        }
        feedbackArea.show().delay(5000).fadeOut();
        $('html, body').animate({ scrollTop: feedbackArea.offset().top - 50 }, 500);
    }

    // Invitation form
    $('#mobooking-invite-worker-form').on('submit', function(e) {
        e.preventDefault();
        feedbackArea.hide();
        var formData = $(this).serialize();

        $.post(mobooking_workers_params.ajax_url, formData, function(response) {
            if (response.success) {
                showFeedback(response.data.message, true);
                $('#worker_email').val(''); // Clear email field
            } else {
                showFeedback(response.data.message || mobooking_workers_params.i18n.error_occurred, false);
            }
        }).fail(function() {
            showFeedback(mobooking_workers_params.i18n.error_unexpected, false);
        });
    });

    // Revoke Access
    $('.mobooking-revoke-access-form').on('submit', function(e) {
        e.preventDefault();
        if (!confirm(mobooking_workers_params.i18n.confirm_revoke_access)) {
            return;
        }
        feedbackArea.hide();
        var $form = $(this);
        var workerId = $form.find('input[name="worker_user_id"]').val();
        var nonce = $form.find('input[name="mobooking_revoke_access_nonce"]').val(); // This nonce is form-specific, still good
        var $button = $form.find('.mobooking-revoke-access-btn');
        $button.prop('disabled', true).text(mobooking_workers_params.i18n.revoking);

        $.post(mobooking_workers_params.ajax_url, {
            action: 'mobooking_revoke_worker_access', // This action name is fine
            worker_user_id: workerId,
            mobooking_revoke_access_nonce: nonce // Pass the specific nonce
        }, function(response) {
            if (response.success) {
                showFeedback(response.data.message, true);
                $('#worker-row-' + workerId).fadeOut(500, function() { $(this).remove(); });
            } else {
                showFeedback(response.data.message || mobooking_workers_params.i18n.error_revoking_access, false);
                $button.prop('disabled', false).text(mobooking_workers_params.i18n.revoke_access);
            }
        }).fail(function() {
            showFeedback(mobooking_workers_params.i18n.error_unexpected, false);
            $button.prop('disabled', false).text(mobooking_workers_params.i18n.revoke_access);
        });
    });

    // Direct Add Staff form
    $('#mobooking-direct-add-staff-form').on('submit', function(e) {
        e.preventDefault();
        feedbackArea.hide();
        var formData = $(this).serialize(); // This includes the nonce from the form
        var $form = $(this);
        var $submitButton = $form.find('input[type="submit"]');
        $submitButton.prop('disabled', true);

        $.post(mobooking_workers_params.ajax_url, formData, function(response) {
            if (response.success) {
                showFeedback(response.data.message, true, 'mobooking-worker-created-success'); // Add custom class here
                $form[0].reset();
                // Reload the page to show the new worker in the list and clear state.
                // The feedback message will be visible for a short duration due to showFeedback's delay().
                setTimeout(function() {
                    location.reload();
                }, 1000); // Add a slight delay to ensure feedback is seen, adjust as needed.
            } else {
                showFeedback(response.data.message || mobooking_workers_params.i18n.error_occurred, false);
            }
        }).fail(function() {
            showFeedback(mobooking_workers_params.i18n.error_unexpected, false);
        }).always(function() {
            $submitButton.prop('disabled', false);
        });
    });

    // Change Role form
    $('.mobooking-workers-table').on('submit', '.mobooking-change-role-form', function(e) {
        e.preventDefault();
        feedbackArea.hide();
        var $form = $(this);
        var formData = $form.serialize(); // Includes form-specific nonce
        var workerId = $form.find('input[name="worker_user_id"]').val();
        var $submitButton = $form.find('.mobooking-change-role-submit-btn');
        var originalButtonText = $submitButton.text();
        $submitButton.prop('disabled', true).text(mobooking_workers_params.i18n.changing_role);

        $.post(mobooking_workers_params.ajax_url, formData, function(response) {
            if (response.success) {
                showFeedback(response.data.message, true);
                $('#worker-row-' + workerId + ' .worker-role-display').text(response.data.new_role_display_name);
                $form.find('.mobooking-role-select option').removeAttr('selected');
                $form.find('.mobooking-role-select option[value="' + response.data.new_role_key + '"]').attr('selected', 'selected');
            } else {
                showFeedback(response.data.message || mobooking_workers_params.i18n.error_occurred, false);
            }
        }).fail(function() {
            showFeedback(mobooking_workers_params.i18n.error_server, false);
        }).always(function() {
            $submitButton.prop('disabled', false).text(originalButtonText);
        });
    });

    // Edit Worker Details - Show/Hide form
    $('.mobooking-workers-table').on('click', '.mobooking-edit-worker-details-btn', function() {
        var workerId = $(this).data('worker-id');
        $('#edit-worker-form-' + workerId).slideToggle('fast');
    });
    $('.mobooking-workers-table').on('click', '.mobooking-cancel-edit-details-btn', function() {
        var workerId = $(this).data('worker-id');
        $('#edit-worker-form-' + workerId).slideUp('fast');
    });

    // Edit Worker Details - AJAX Submit
    $('.mobooking-workers-table').on('submit', '.mobooking-edit-details-actual-form', function(e) {
        e.preventDefault();
        feedbackArea.hide();
        var $form = $(this);
        var formData = $form.serialize(); // Includes form-specific nonce
        var workerId = $form.find('input[name="worker_user_id"]').val();
        var $submitButton = $form.find('.mobooking-save-details-btn');
        var originalButtonText = $submitButton.text();
        $submitButton.prop('disabled', true).text(mobooking_workers_params.i18n.saving);

        $.post(mobooking_workers_params.ajax_url, formData, function(response) {
            if (response.success) {
                showFeedback(response.data.message, true);
                var newFirstName = $form.find('input[name="edit_first_name"]').val();
                var newLastName = $form.find('input[name="edit_last_name"]').val();
                $('#worker-row-' + workerId + ' .worker-first-name-display').text(newFirstName);
                $('#worker-row-' + workerId + ' .worker-last-name-display').text(newLastName);
                $('#edit-worker-form-' + workerId).slideUp('fast');
            } else {
                showFeedback(response.data.message || mobooking_workers_params.i18n.error_occurred, false);
            }
        }).fail(function() {
            showFeedback(mobooking_workers_params.i18n.error_server, false);
        }).always(function() {
            $submitButton.prop('disabled', false).text(originalButtonText);
        });
    });
});
