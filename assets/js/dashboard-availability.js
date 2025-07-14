jQuery(document).ready(function ($) {
  "use strict";

  const ajaxUrl = mobooking_availability_params.ajax_url;
  const availabilityNonce = mobooking_availability_params.availability_nonce;
  const i18n = mobooking_availability_params.i18n || {};
  const daysOfWeek = [
    i18n.sunday || "Sunday",
    i18n.monday || "Monday",
    i18n.tuesday || "Tuesday",
    i18n.wednesday || "Wednesday",
    i18n.thursday || "Thursday",
    i18n.friday || "Friday",
    i18n.saturday || "Saturday",
  ];

  // --- DOM Elements ---
  const $feedbackDiv = $("#mobooking-availability-feedback");
  const $scheduleContainer = $("#recurring-schedule-container");
  const $saveScheduleBtn = $("#mobooking-save-recurring-schedule-btn");

  let scheduleData = []; // This will hold the state of the schedule

  // --- Utility Functions ---
  function showFeedback(message, type = "info") {
    $feedbackDiv
      .removeClass("notice-info notice-success notice-error")
      .addClass("notice-" + type)
      .html("<p>" + message + "</p>")
      .fadeIn();
    setTimeout(() => $feedbackDiv.fadeOut(), 5000);
  }

  // --- Schedule Rendering ---
  function renderScheduleEditor() {
    $scheduleContainer.empty();
    const $scheduleList = $('<ul class="mobooking-schedule-editor"></ul>');

    daysOfWeek.forEach((dayName, dayIndex) => {
      const dayData = scheduleData.find(d => d.day_of_week == dayIndex) || { day_of_week: dayIndex, is_enabled: false, slots: [] };

      const $dayItem = $(`
        <li class="day-schedule ${dayData.is_enabled ? 'day-enabled' : 'day-disabled'}">
          <div class="day-header">
            <div class="day-name-toggle">
              <label class="mobooking-toggle-switch">
                <input type="checkbox" class="day-toggle-switch" data-day-index="${dayIndex}" ${dayData.is_enabled ? 'checked' : ''}>
                <span class="slider"></span>
              </label>
              <strong>${dayName}</strong>
            </div>
            <div class="day-actions">
              <button type="button" class="button button-small add-slot-btn" data-day-index="${dayIndex}">
                <span class="dashicons dashicons-plus"></span>
              </button>
              <button type="button" class="button button-small copy-schedule-btn" data-day-index="${dayIndex}">
                <span class="dashicons dashicons-admin-page"></span>
              </button>
            </div>
          </div>
          <div class="day-slots">
            <!-- Slots will be rendered here -->
          </div>
        </li>
      `);

      const $slotsContainer = $dayItem.find('.day-slots');
      if (dayData.is_enabled) {
        if (dayData.slots.length > 0) {
          dayData.slots.forEach((slot, slotIndex) => {
            $slotsContainer.append(renderSlotInput(dayIndex, slotIndex, slot));
          });
        } else {
          $slotsContainer.html(`<p class="no-slots-text">${i18n.no_slots_text || 'No time slots for this day.'}</p>`);
        }
      } else {
        $slotsContainer.html(`<p class="day-off-text">${i18n.day_off_text || 'Unavailable'}</p>`);
      }

      $scheduleList.append($dayItem);
    });

    $scheduleContainer.append($scheduleList);
  }

  function renderSlotInput(dayIndex, slotIndex, slot) {
    return `
      <div class="time-slot" data-slot-index="${slotIndex}">
        <input type="time" class="start-time" value="${slot.start_time}">
        <span>-</span>
        <input type="time" class="end-time" value="${slot.end_time}">
        <button type="button" class="button button-link-delete delete-slot-btn" data-day-index="${dayIndex}" data-slot-index="${slotIndex}">
          <span class="dashicons dashicons-trash"></span>
        </button>
      </div>
    `;
  }

  // --- Data & State Management ---
  function loadSchedule() {
    $.ajax({
      url: ajaxUrl,
      type: "POST",
      data: {
        action: "mobooking_get_recurring_schedule",
        nonce: availabilityNonce,
      },
      success: function (response) {
        if (response.success) {
          scheduleData = response.data;
          renderScheduleEditor();
        } else {
          showFeedback(i18n.error_loading_schedule || "Error loading schedule.", "error");
        }
      },
      error: function () {
        showFeedback(i18n.error_ajax || "AJAX error.", "error");
      },
    });
  }

  function saveSchedule() {
    // Before saving, update scheduleData from the DOM
    updateScheduleDataFromDOM();

    $.ajax({
      url: ajaxUrl,
      type: "POST",
      data: {
        action: "mobooking_save_recurring_schedule",
        nonce: availabilityNonce,
        schedule_data: JSON.stringify(scheduleData),
      },
      success: function (response) {
        if (response.success) {
          showFeedback(response.data.message, "success");
          loadSchedule(); // Reload to ensure data is fresh
        } else {
          showFeedback(response.data.message || "Error saving schedule.", "error");
        }
      },
      error: function () {
        showFeedback(i18n.error_ajax || "AJAX error.", "error");
      },
    });
  }

  function updateScheduleDataFromDOM() {
    let updatedSchedule = [];
    $('.mobooking-schedule-editor .day-schedule').each(function (dayIndex) {
      const $dayItem = $(this);
      const isEnabled = $dayItem.find('.day-toggle-switch').is(':checked');
      let dayData = {
        day_of_week: dayIndex,
        is_enabled: isEnabled,
        slots: []
      };

      if (isEnabled) {
        $dayItem.find('.time-slot').each(function () {
          const $slot = $(this);
          const startTime = $slot.find('.start-time').val();
          const endTime = $slot.find('.end-time').val();
          if (startTime && endTime) {
            dayData.slots.push({ start_time: startTime, end_time: endTime });
          }
        });
      }
      updatedSchedule.push(dayData);
    });
    scheduleData = updatedSchedule;
  }


  // --- Event Handlers ---
  $scheduleContainer.on('change', '.day-toggle-switch', function () {
    const $toggle = $(this);
    const dayIndex = $toggle.data('day-index');
    const isEnabled = $toggle.is(':checked');
    const $dayItem = $toggle.closest('.day-schedule');
    const $slotsContainer = $dayItem.find('.day-slots');

    $dayItem.toggleClass('day-enabled', isEnabled).toggleClass('day-disabled', !isEnabled);

    if (isEnabled) {
        $slotsContainer.html('');
        // Add a default slot
        const newSlot = { start_time: '09:00', end_time: '17:00' };
        scheduleData.find(d => d.day_of_week == dayIndex).slots.push(newSlot);
        $slotsContainer.append(renderSlotInput(dayIndex, 0, newSlot));
    } else {
        $slotsContainer.html(`<p class="day-off-text">${i18n.day_off_text || 'Unavailable'}</p>`);
    }
  });

  $scheduleContainer.on('click', '.add-slot-btn', function () {
    const dayIndex = $(this).data('day-index');
    const dayData = scheduleData.find(d => d.day_of_week == dayIndex);
    if (!dayData.is_enabled) return;

    const newSlot = { start_time: '09:00', end_time: '17:00' };
    dayData.slots.push(newSlot);
    const newSlotIndex = dayData.slots.length - 1;
    const $slotsContainer = $(this).closest('.day-schedule').find('.day-slots');
    $slotsContainer.find('.no-slots-text').remove();
    $slotsContainer.append(renderSlotInput(dayIndex, newSlotIndex, newSlot));
  });

  $scheduleContainer.on('click', '.delete-slot-btn', function () {
    const dayIndex = $(this).data('day-index');
    const slotIndex = $(this).data('slot-index');
    const dayData = scheduleData.find(d => d.day_of_week == dayIndex);

    dayData.slots.splice(slotIndex, 1);

    // Re-render the slots for the day
    const $dayItem = $(this).closest('.day-schedule');
    const $slotsContainer = $dayItem.find('.day-slots');
    $slotsContainer.empty();
    if (dayData.slots.length > 0) {
      dayData.slots.forEach((slot, newSlotIndex) => {
        $slotsContainer.append(renderSlotInput(dayIndex, newSlotIndex, slot));
      });
    } else {
      $slotsContainer.html(`<p class="no-slots-text">${i18n.no_slots_text || 'No time slots for this day.'}</p>`);
    }
  });

  $scheduleContainer.on('click', '.copy-schedule-btn', function () {
    const sourceDayIndex = $(this).data('day-index');
    updateScheduleDataFromDOM(); // Ensure we have the latest data
    const sourceDayData = scheduleData.find(d => d.day_of_week == sourceDayIndex);

    // Create and show a modal for copying
    const $modal = $(`
        <div class="mobooking-modal active">
            <div class="mobooking-modal-content">
                <h3>${i18n.copy_schedule || 'Copy Schedule'}</h3>
                <p>${i18n.copy_from || 'Copy from'} <strong>${daysOfWeek[sourceDayIndex]}</strong> ${i18n.to || 'to'}:</p>
                <div class="copy-days-selection">
                    ${daysOfWeek.map((day, index) => {
                        if (index === sourceDayIndex) return '';
                        return `
                            <label>
                                <input type="checkbox" name="copy-day" value="${index}">
                                ${day}
                            </label>
                        `;
                    }).join('')}
                </div>
                <div class="form-actions">
                    <button type="button" class="button button-primary" id="confirm-copy-btn">${i18n.copy || 'Copy'}</button>
                    <button type="button" class="button mobooking-modal-close">${i18n.cancel || 'Cancel'}</button>
                </div>
            </div>
        </div>
        <div class="mobooking-modal-backdrop active"></div>
    `);

    $('body').append($modal);

    $modal.on('click', '.mobooking-modal-close, .mobooking-modal-backdrop', function () {
        $modal.remove();
    });

    $modal.on('click', '#confirm-copy-btn', function () {
        const targetDays = [];
        $modal.find('input[name="copy-day"]:checked').each(function () {
            targetDays.push(parseInt($(this).val()));
        });

        if (targetDays.length > 0) {
            targetDays.forEach(targetDayIndex => {
                const targetDayData = scheduleData.find(d => d.day_of_week == targetDayIndex);
                targetDayData.is_enabled = sourceDayData.is_enabled;
                targetDayData.slots = JSON.parse(JSON.stringify(sourceDayData.slots)); // Deep copy
            });
            renderScheduleEditor();
        }
        $modal.remove();
    });
  });

  $saveScheduleBtn.on('click', saveSchedule);

  // --- Initialization ---
  loadSchedule();
});
