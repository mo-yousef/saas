jQuery(document).ready(function($) {
    'use strict';

    // Nonce and AJAX URL from WordPress localization
    const ajaxUrl = mobooking_availability_params.ajax_url;
    const availabilityNonce = mobooking_availability_params.availability_nonce;

    // --- DOM Elements ---
    const $feedbackDiv = $('#mobooking-availability-feedback');
    // Recurring Slots
    const $recurringSlotsContainer = $('#recurring-slots-container');
    const $addRecurringSlotBtn = $('#mobooking-add-recurring-slot-btn');
    const $recurringSlotModal = $('#mobooking-recurring-slot-modal');
    const $recurringSlotModalBackdrop = $('#mobooking-recurring-slot-modal-backdrop');
    const $recurringSlotForm = $('#mobooking-recurring-slot-form');
    const $recurringSlotModalTitle = $('#recurring-slot-modal-title');
    const $recurringSlotModalError = $('#mobooking-recurring-slot-modal-error'); // Added
    let currentRecurringSlots = []; // Store loaded slots
    // Date Overrides
    const $datepicker = $('#mobooking-availability-datepicker');
    const $overrideDetailsDiv = $('#mobooking-override-details');
    const $overrideFormTitle = $('#override-form-title');
    const $overrideForm = $('#mobooking-date-override-form');
    const $overrideDateInput = $('#override-date-input');
    const $overrideIdInput = $('#override-id-input');
    const $overrideIsUnavailableCheckbox = $('#override-is-unavailable');
    const $overrideTimeSlotsSection = $('#override-time-slots-section');
    const $deleteOverrideBtn = $('#mobooking-delete-override-btn');
    const $clearOverrideFormBtn = $('#mobooking-clear-override-form-btn');

    const i18n = mobooking_availability_params.i18n || {}; // Ensure i18n object exists
    const daysOfWeek = [
        i18n.sunday || 'Sunday',
        i18n.monday || 'Monday',
        i18n.tuesday || 'Tuesday',
        i18n.wednesday || 'Wednesday',
        i18n.thursday || 'Thursday',
        i18n.friday || 'Friday',
        i18n.saturday || 'Saturday'
    ];

    // --- Utility Functions ---
    function showFeedback(message, type = 'info') { // type can be 'info', 'success', 'error'
        $feedbackDiv.removeClass('notice-info notice-success notice-error notice-warning').addClass('notice-' + type).html('<p>' + message + '</p>').fadeIn();
        setTimeout(function() {
            $feedbackDiv.fadeOut();
        }, 5000);
    }

    function formatTimeForDisplay(timeStr) { // HH:MM:SS to HH:MM AM/PM (or locale default)
        if (!timeStr) return '';
        const [hours, minutes] = timeStr.split(':');
        const date = new Date();
        date.setHours(parseInt(hours, 10));
        date.setMinutes(parseInt(minutes, 10));
        return date.toLocaleTimeString(navigator.language, { hour: '2-digit', minute: '2-digit', hour12: true });
    }

    function formatTimeForInput(timeStr) { // HH:MM AM/PM or existing HH:MM:SS to HH:MM (24h for input type=time)
        if (!timeStr) return '';
        // Check if it's already in HH:MM or HH:MM:SS format
        if (/^([01]\d|2[0-3]):([0-5]\d)(?::([0-5]\d))?$/.test(timeStr)) {
            return timeStr.substring(0, 5); // Return HH:MM
        }
        // Attempt to parse other formats (like AM/PM) if necessary, though toLocaleTimeString might not be robustly parsable
        // For now, this function assumes if it's not HH:MM:SS, it might be already suitable or needs manual entry.
        // A more robust solution would use a library like moment.js or date-fns for time parsing/formatting if varied inputs are expected.
        // Given we control the output from DB (HH:MM:SS), this simplified version is okay for now.
        return timeStr; // Fallback
    }


    // --- Recurring Slots ---
    function openRecurringSlotModal(slotData = null) {
        $recurringSlotForm[0].reset();
        $recurringSlotModalError.hide().empty(); // Clear previous errors
        $('#recurring-is-active').prop('checked', true); // Default to active

        if (slotData) {
            $recurringSlotModalTitle.text(i18n.edit_recurring_slot || 'Edit Recurring Slot');
            $('#recurring-slot-id').val(slotData.slot_id);
            $('#recurring-day-of-week').val(slotData.day_of_week);
            $('#recurring-start-time').val(formatTimeForInput(slotData.start_time));
            $('#recurring-end-time').val(formatTimeForInput(slotData.end_time));
            $('#recurring-capacity').val(slotData.capacity);
            $('#recurring-is-active').prop('checked', !!parseInt(slotData.is_active));
        } else {
            $recurringSlotModalTitle.text(i18n.add_recurring_slot || 'Add Recurring Slot');
            $('#recurring-slot-id').val('');
        }
        // CSS now handles display via .active class
        $recurringSlotModal.addClass('active');
        $recurringSlotModalBackdrop.addClass('active');
    }

    function closeRecurringSlotModal() {
        // CSS now handles display via .active class
        $recurringSlotModal.removeClass('active');
        $recurringSlotModalBackdrop.removeClass('active');
    }

    function renderRecurringSlots() { // Now uses currentRecurringSlots global
        $recurringSlotsContainer.empty();

        const slotsByDay = {};
        daysOfWeek.forEach((day, index) => slotsByDay[index] = []);

        currentRecurringSlots.forEach(slot => {
            if (slotsByDay[slot.day_of_week] !== undefined) { // Ensure day_of_week is valid
                slotsByDay[slot.day_of_week].push(slot);
            }
        });

        daysOfWeek.forEach((dayName, dayIndex) => {
            const $dayGroup = $('<div class="recurring-day-group"></div>');
            const daySlots = slotsByDay[dayIndex] || [];
            // A day is considered "off" if ALL its slots are inactive, or if there are no slots.
            // However, our new toggle means "off" is when we explicitly set it, which deactivates slots.
            // So, if all slots for a day are inactive, it's effectively a day off.
            const isEffectivelyDayOff = daySlots.length === 0 || daySlots.every(slot => !parseInt(slot.is_active));

            let dayOffButtonText = isEffectivelyDayOff ? (i18n.set_as_working_day || 'Set as Working Day') : (i18n.mark_day_off || 'Mark as Day Off');
            let dayOffButtonClass = isEffectivelyDayOff ? 'button-primary' : 'mobooking-button-delete';

            $dayGroup.append(`
                <div class="recurring-day-header">
                    <h4>${dayName}</h4>
                    <button type="button" class="button button-small mobooking-toggle-day-off-btn ${dayOffButtonClass}" data-day-index="${dayIndex}" data-is-currently-off="${isEffectivelyDayOff}">
                        ${dayOffButtonText}
                    </button>
                </div>
            `);

            const $ul = $('<ul class="recurring-slots-list"></ul>');

            if (daySlots.length > 0 && !isEffectivelyDayOff) { // Only show slots if not effectively a day off
                daySlots.forEach(slot => {
                    // Only render active slots if the day is not marked as off.
                    // If a slot is individually inactive but the day is a "working day", it should still be shown as inactive.
                    if (parseInt(slot.is_active)) {
                        const activeText = i18n.active || 'Active';
                        const activeClass = 'status-active';
                        const $li = $(`
                            <li>
                                <span>
                                    ${formatTimeForDisplay(slot.start_time)} - ${formatTimeForDisplay(slot.end_time)}
                                    (Capacity: ${slot.capacity})
                                    <span class="booking-status ${activeClass}" style="margin-left: 10px;">${activeText}</span>
                                </span>
                                <span class="slot-actions">
                                    <button type="button" class="button button-small mobooking-edit-recurring-slot-btn" data-slot-id="${slot.slot_id}">${i18n.edit || 'Edit'}</button>
                                    <button type="button" class="button button-small button-link-delete mobooking-delete-recurring-slot-btn" data-slot-id="${slot.slot_id}">${i18n.delete || 'Delete'}</button>
                                </span>
                            </li>
                        `);
                        $ul.append($li);
                    } else {
                        // Optionally show inactive slots differently or hide them if day is working
                        // For now, if a day is working, we only list its *active* slots.
                        // If all slots are inactive, it's covered by isEffectivelyDayOff
                    }
                });
                 // If all slots were inactive but day wasn't explicitly "off", this logic needs refinement.
                 // The definition of isEffectivelyDayOff should be the primary driver.
                 if ($ul.children().length === 0 && !isEffectivelyDayOff) {
                     $ul.append(`<li class="empty-day-slot">${i18n.no_active_slots_for_working_day || 'No active slots. Add some or edit existing ones.'}</li>`);
                 }

            } else if (isEffectivelyDayOff) {
                $ul.append(`<li class="empty-day-slot">${i18n.day_marked_as_off || 'This day is marked as off.'}</li>`);
            } else { // No slots at all for a working day
                 $ul.append(`<li class="empty-day-slot">${i18n.no_slots_add_some || 'No time slots. Add some below.'}</li>`);
            }
            $dayGroup.append($ul);
            $recurringSlotsContainer.append($dayGroup);
        });
    }

    function loadRecurringSlots() {
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'mobooking_get_recurring_slots',
                nonce: availabilityNonce,
            },
            success: function(response) {
                if (response.success) {
                    currentRecurringSlots = response.data; // Store the loaded slots
                    renderRecurringSlots(); // Re-render with the new data
                } else {
                    showFeedback(response.data.message || (i18n.error_loading_recurring_schedule || 'Error loading recurring schedule.'), 'error');
                    $recurringSlotsContainer.html(`<p>${i18n.error_loading_recurring_schedule_retry || 'Could not load schedule. Please try again.'}</p>`);
                }
            },
            error: function() {
                showFeedback(i18n.error_ajax || 'AJAX error.', 'error');
                $recurringSlotsContainer.html(`<p>${i18n.error_loading_recurring_schedule_retry || 'Could not load schedule. Please try again.'}</p>`);
            }
        });
    }

    $addRecurringSlotBtn.on('click', function() {
        openRecurringSlotModal();
    });

    $('.mobooking-modal-close, #mobooking-recurring-slot-modal-backdrop').on('click', function() {
        closeRecurringSlotModal();
    });

    $recurringSlotForm.on('submit', function(e) {
        e.preventDefault();
        const slotData = {
            slot_id: $('#recurring-slot-id').val(),
            day_of_week: $('#recurring-day-of-week').val(),
            start_time: $('#recurring-start-time').val(),
            end_time: $('#recurring-end-time').val(),
            capacity: $('#recurring-capacity').val(),
            is_active: $('#recurring-is-active').is(':checked') ? 1 : 0,
        };

        // Basic client-side validation
        if (!slotData.start_time || !slotData.end_time) {
            $recurringSlotModalError.html(i18n.start_end_time_required || 'Start and End time are required.').show();
            return;
        }
        if (slotData.start_time >= slotData.end_time) {
            $recurringSlotModalError.html(i18n.start_time_before_end || 'Start time must be before end time.').show();
            return;
        }
        if (parseInt(slotData.capacity) < 1) {
            $recurringSlotModalError.html(i18n.capacity_must_be_positive || 'Capacity must be a positive number.').show();
            return;
        }
        $recurringSlotModalError.hide().empty(); // Clear errors if validation passes


        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'mobooking_save_recurring_slot',
                nonce: availabilityNonce,
                slot_data: JSON.stringify(slotData)
            },
            success: function(response) {
                if (response.success) {
                    showFeedback(response.data.message, 'success'); // Global feedback for success
                    loadRecurringSlots(); // Refresh list
                    closeRecurringSlotModal();
                } else {
                    // Show error inside the modal
                    $recurringSlotModalError.html(response.data.message || (i18n.error_saving_slot || 'Error saving slot.')).show();
                }
            },
            error: function() {
                // Show error inside the modal
                $recurringSlotModalError.html(i18n.error_ajax || 'AJAX error. Please try again.').show();
            }
        });
    });

    $recurringSlotsContainer.on('click', '.mobooking-edit-recurring-slot-btn', function() {
        const slotId = $(this).data('slot-id');
        // Find the slot data from the already loaded slots (or make another AJAX call if not stored)
        // For simplicity, assuming we can refetch or have it stored if loadRecurringSlots stores data globally.
        // Better: fetch the specific slot for editing to ensure fresh data.
        // For now, we'll re-fetch all and find. In a more complex app, store slots in a JS variable.
        // Find slot from the stored currentRecurringSlots array
        const slotToEdit = currentRecurringSlots.find(s => s.slot_id == slotId);
        if (slotToEdit) {
            openRecurringSlotModal(slotToEdit);
        } else {
            // Fallback to refetch if not found, though it should be there
            showFeedback(i18n.error_slot_not_found || 'Slot not found for editing. Refreshing list...', 'warning');
            loadRecurringSlots();
        }
    });

    $recurringSlotsContainer.on('click', '.mobooking-delete-recurring-slot-btn', function() {
        if (!confirm(i18n.confirm_delete_slot || 'Are you sure you want to delete this recurring slot?')) {
            return;
        }
        const slotId = $(this).data('slot-id');
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'mobooking_delete_recurring_slot',
                nonce: availabilityNonce,
                slot_id: slotId
            },
            success: function(response) {
                if (response.success) {
                    showFeedback(response.data.message, 'success');
                    loadRecurringSlots(); // Refresh list
                } else {
                    showFeedback(response.data.message || (i18n.error_deleting_slot || 'Error deleting slot.'), 'error');
                }
            },
            error: function() {
                showFeedback(i18n.error_ajax || 'AJAX error.', 'error');
            }
        });
    });

    // --- Date Overrides ---
    let currentOverrides = []; // Store overrides for the current view (e.g., month)

    $datepicker.datepicker({
        dateFormat: "yy-mm-dd",
        onSelect: function(dateText) {
            $overrideDateInput.val(dateText);
            $overrideFormTitle.text((i18n.manage_override_for || "Manage Override for:") + " " + dateText);
            $('.override-selected-date-display').text((i18n.selected_date || "Selected Date:") + " " + dateText);

            // Check if an override exists for this date
            const existingOverride = currentOverrides.find(ov => ov.override_date === dateText);
            if (existingOverride) {
                $overrideIdInput.val(existingOverride.override_id);
                $overrideIsUnavailableCheckbox.prop('checked', !!parseInt(existingOverride.is_unavailable));
                if (!!parseInt(existingOverride.is_unavailable)) {
                    $overrideTimeSlotsSection.hide();
                    $('#override-start-time').val('');
                    $('#override-end-time').val('');
                    $('#override-capacity').val('1');
                } else {
                    $overrideTimeSlotsSection.show();
                    $('#override-start-time').val(formatTimeForInput(existingOverride.start_time));
                    $('#override-end-time').val(formatTimeForInput(existingOverride.end_time));
                    $('#override-capacity').val(existingOverride.capacity || 1);
                }
                $('#override-notes').val(existingOverride.notes || '');
                $deleteOverrideBtn.show();
            } else {
                resetOverrideForm(dateText); // Keep date, clear other fields
            }
            $overrideDetailsDiv.show();
        },
        onChangeMonthYear: function(year, month) {
            // Load overrides for the new month
            const dateFrom = `${year}-${String(month).padStart(2, '0')}-01`;
            const tempDate = new Date(year, month, 0); // Last day of prev month to get days in current month
            const dateTo = `${year}-${String(month).padStart(2, '0')}-${String(tempDate.getDate()).padStart(2, '0')}`;
            loadDateOverrides(dateFrom, dateTo);
        },
        beforeShowDay: function(date) {
            const dateString = $.datepicker.formatDate('yy-mm-dd', date);
            const override = currentOverrides.find(ov => ov.override_date === dateString);
            if (override) {
                if (!!parseInt(override.is_unavailable)) {
                    return [true, "mobooking-date-unavailable", (i18n.day_off || "Day Off")];
                } else {
                    return [true, "mobooking-date-custom-availability", (i18n.custom_availability || "Custom Availability")];
                }
            }
            return [true, ""]; // Default, no special styling
        }
    });

    function resetOverrideForm(dateText = null) {
        $overrideForm[0].reset();
        $overrideIdInput.val('');
        $overrideIsUnavailableCheckbox.prop('checked', false);
        $overrideTimeSlotsSection.show();
        $deleteOverrideBtn.hide();
        if (dateText) {
            $overrideDateInput.val(dateText);
            $overrideFormTitle.text((i18n.manage_override_for || "Manage Override for:") + " " + dateText);
             $('.override-selected-date-display').text((i18n.selected_date || "Selected Date:") + " " + dateText);
        } else {
            $overrideDetailsDiv.hide();
            $overrideFormTitle.text(i18n.select_date_to_manage || 'Select a date to manage overrides');
             $('.override-selected-date-display').text('');
        }
    }

    $clearOverrideFormBtn.on('click', function() {
        const selectedDate = $datepicker.datepicker('getDate');
        resetOverrideForm(selectedDate ? $.datepicker.formatDate('yy-mm-dd', selectedDate) : null);
        if (!selectedDate) { // If no date was selected, hide the form
            $overrideDetailsDiv.hide();
            $overrideFormTitle.text(i18n.select_date_to_manage || 'Select a date to manage overrides');
        }
    });


    $overrideIsUnavailableCheckbox.on('change', function() {
        if ($(this).is(':checked')) {
            $overrideTimeSlotsSection.hide();
        } else {
            $overrideTimeSlotsSection.show();
        }
    });

    function loadDateOverrides(dateFrom, dateTo) {
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'mobooking_get_date_overrides',
                nonce: availabilityNonce,
                date_from: dateFrom,
                date_to: dateTo
            },
            success: function(response) {
                if (response.success) {
                    currentOverrides = response.data;
                    $datepicker.datepicker('refresh'); // Refresh to apply beforeShowDay styles
                } else {
                    showFeedback(response.data.message || (i18n.error_loading_overrides || 'Error loading date overrides.'), 'error');
                }
            },
            error: function() {
                showFeedback(i18n.error_ajax || 'AJAX error.', 'error');
            }
        });
    }

    $overrideForm.on('submit', function(e) {
        e.preventDefault();
        const overrideData = {
            override_id: $overrideIdInput.val(),
            override_date: $overrideDateInput.val(),
            is_unavailable: $overrideIsUnavailableCheckbox.is(':checked') ? 1 : 0,
            notes: $('#override-notes').val()
        };

        if (!overrideData.is_unavailable) {
            overrideData.start_time = $('#override-start-time').val();
            overrideData.end_time = $('#override-end-time').val();
            overrideData.capacity = $('#override-capacity').val();

            if (!overrideData.start_time || !overrideData.end_time) {
                showFeedback(i18n.start_end_time_required_override || 'Start and End time are required for available overrides.', 'error');
                return;
            }
            if (overrideData.start_time >= overrideData.end_time) {
                showFeedback(i18n.start_time_before_end_override || 'Start time must be before end time for overrides.', 'error');
                return;
            }
        }

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'mobooking_save_date_override',
                nonce: availabilityNonce,
                override_data: JSON.stringify(overrideData)
            },
            success: function(response) {
                if (response.success) {
                    showFeedback(response.data.message, 'success');
                    // Refresh overrides for the current view
                    const currentMonthDate = $datepicker.datepicker('getDate') || new Date();
                    const year = currentMonthDate.getFullYear();
                    const month = currentMonthDate.getMonth() + 1; // JS months are 0-indexed
                    const dateFrom = `${year}-${String(month).padStart(2, '0')}-01`;
                    const tempDate = new Date(year, month, 0);
                    const dateTo = `${year}-${String(month).padStart(2, '0')}-${String(tempDate.getDate()).padStart(2, '0')}`;
                    loadDateOverrides(dateFrom, dateTo);

                    // Update form with new ID if it was an insert
                    if(response.data.override && response.data.override.override_id) {
                        $overrideIdInput.val(response.data.override.override_id);
                        $deleteOverrideBtn.show();
                    }

                } else {
                    showFeedback(response.data.message || (i18n.error_saving_override || 'Error saving override.'), 'error');
                }
            },
            error: function() {
                showFeedback(i18n.error_ajax || 'AJAX error.', 'error');
            }
        });
    });

    $deleteOverrideBtn.on('click', function() {
        if (!confirm(i18n.confirm_delete_override || 'Are you sure you want to delete the override for this date?')) {
            return;
        }
        const overrideId = $overrideIdInput.val();
        if (!overrideId) {
            showFeedback(i18n.error_no_override_to_delete || 'No override selected to delete.', 'error');
            return;
        }

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'mobooking_delete_date_override',
                nonce: availabilityNonce,
                override_id: overrideId
            },
            success: function(response) {
                if (response.success) {
                    showFeedback(response.data.message, 'success');
                    resetOverrideForm($overrideDateInput.val()); // Keep date, clear fields
                    // Refresh overrides
                    const currentMonthDate = $datepicker.datepicker('getDate') || new Date();
                    const year = currentMonthDate.getFullYear();
                    const month = currentMonthDate.getMonth() + 1;
                    const dateFrom = `${year}-${String(month).padStart(2, '0')}-01`;
                    const tempDate = new Date(year, month, 0);
                    const dateTo = `${year}-${String(month).padStart(2, '0')}-${String(tempDate.getDate()).padStart(2, '0')}`;
                    loadDateOverrides(dateFrom, dateTo);
                } else {
                    showFeedback(response.data.message || (i18n.error_deleting_override || 'Error deleting override.'), 'error');
                }
            },
            error: function() {
                showFeedback(i18n.error_ajax || 'AJAX error.', 'error');
            }
        });
    });


    // --- Initializations ---

    // Event handler for toggling day off status
    $recurringSlotsContainer.on('click', '.mobooking-toggle-day-off-btn', function() {
        const $button = $(this);
        const dayIndex = $button.data('day-index');
        const isCurrentlyOff = $button.data('is-currently-off');
        const setToDayOff = !isCurrentlyOff; // If it's currently a working day, we want to mark it as off.

        const confirmMessage = setToDayOff ?
                                (i18n.confirm_mark_day_off || 'Are you sure you want to mark this day as off? All existing slots for this day will be deactivated.') :
                                (i18n.confirm_set_working_day || 'Are you sure you want to set this as a working day? You will need to add or activate time slots.');

        if (!confirm(confirmMessage)) {
            return;
        }

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'mobooking_set_recurring_day_status',
                nonce: availabilityNonce,
                day_of_week: dayIndex,
                is_day_off: setToDayOff
            },
            success: function(response) {
                if (response.success) {
                    showFeedback(response.data.message, 'success');
                    loadRecurringSlots(); // Refresh the entire recurring slots display
                } else {
                    showFeedback(response.data.message || (i18n.error_updating_day_status || 'Error updating day status.'), 'error');
                }
            },
            error: function() {
                showFeedback(i18n.error_ajax, 'error');
            }
        });
    });


    loadRecurringSlots();
    // Load overrides for the current month on page load
    const initialDate = $datepicker.datepicker('getDate') || new Date();
    const initialYear = initialDate.getFullYear();
    const initialMonth = initialDate.getMonth() + 1; // JS months are 0-indexed
    const initialDateFrom = `${initialYear}-${String(initialMonth).padStart(2, '0')}-01`;
    const initialTempDate = new Date(initialYear, initialMonth, 0); // Last day of prev month for days in current
    const initialDateTo = `${initialYear}-${String(initialMonth).padStart(2, '0')}-${String(initialTempDate.getDate()).padStart(2, '0')}`;
    loadDateOverrides(initialDateFrom, initialDateTo);

});
