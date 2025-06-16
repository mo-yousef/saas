jQuery(document).ready(function($) {
    'use strict';

    const form = $('#mobooking-booking-form-settings-form');
    const feedbackDiv = $('#mobooking-settings-feedback');
    const saveButton = $('#mobooking-save-bf-settings-btn');

    // Tab navigation
    const navTabs = $('.nav-tab-wrapper .nav-tab');
    const tabContents = $('.mobooking-settings-tab-content');

    navTabs.on('click', function(e) {
        e.preventDefault();
        navTabs.removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        tabContents.hide();
        $('#' + $(this).data('tab') + '-settings-tab').show();
    });

    // Initialize Color Picker
    if (typeof $.fn.wpColorPicker === 'function') {
        $('.mobooking-color-picker').wpColorPicker();
    } else {
        console.warn('WP Color Picker not available.');
        $('.mobooking-color-picker').each(function(){ $(this).after('<p><small><em>Color picker script not loaded. Please enter hex color manually.</em></small></p>')});
    }


    function loadSettings() {
        feedbackDiv.empty().removeClass('success error notice notice-success notice-error').hide();
        form.find(':input').prop('disabled', true); // Disable form while loading

        $.ajax({
            url: mobooking_bf_settings_params.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_get_booking_form_settings',
                nonce: mobooking_bf_settings_params.nonce
            },
            success: function(response) {
                if (response.success && response.data.settings) {
                    populateForm(response.data.settings);
                } else {
                    feedbackDiv.text(response.data.message || mobooking_bf_settings_params.i18n.error_loading || 'Error loading settings.').addClass('notice notice-error').show();
                }
            },
            error: function() {
                feedbackDiv.text(mobooking_bf_settings_params.i18n.error_ajax || 'AJAX error.').addClass('notice notice-error').show();
            },
            complete: function() {
                form.find(':input').prop('disabled', false);
            }
        });
    }

    function populateForm(settings) {
        for (const key in settings) {
            if (settings.hasOwnProperty(key)) {
                const field = form.find(`[name="${key}"]`);
                if (field.length) {
                    if (field.is(':checkbox')) {
                        field.prop('checked', settings[key] === '1' || settings[key] === true);
                    } else if (field.hasClass('mobooking-color-picker')) {
                        field.wpColorPicker('color', settings[key]);
                    } else {
                        field.val(settings[key]);
                    }
                }
            }
        }
    }

    form.on('submit', function(e) {
        e.preventDefault();
        feedbackDiv.empty().removeClass('success error notice notice-success notice-error').hide();
        const originalButtonText = saveButton.text();
        saveButton.prop('disabled', true).text(mobooking_bf_settings_params.i18n.saving || 'Saving...');

        let settingsData = {};
        form.find(':input:not([type="submit"])').each(function() {
            const $field = $(this);
            const name = $field.attr('name');
            if (!name) return; // Skip if no name attribute

            if ($field.is(':checkbox')) {
                settingsData[name] = $field.is(':checked') ? '1' : '0';
            } else if ($field.is(':radio')) {
                if ($field.is(':checked')) {
                    settingsData[name] = $field.val();
                }
            } else {
                settingsData[name] = $field.val();
            }
        });

        $.ajax({
            url: mobooking_bf_settings_params.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_save_booking_form_settings',
                nonce: mobooking_bf_settings_params.nonce,
                settings: settingsData
            },
            success: function(response) {
                if (response.success) {
                    feedbackDiv.text(response.data.message || mobooking_bf_settings_params.i18n.save_success || 'Settings saved.').addClass('notice notice-success').show();
                } else {
                    feedbackDiv.text(response.data.message || mobooking_bf_settings_params.i18n.error_saving || 'Error saving.').addClass('notice notice-error').show();
                }
            },
            error: function() {
                feedbackDiv.text(mobooking_bf_settings_params.i18n.error_ajax || 'AJAX error.').addClass('notice notice-error').show();
            },
            complete: function() {
                saveButton.prop('disabled', false).text(originalButtonText);
                setTimeout(function() { feedbackDiv.fadeOut(); }, 5000);
            }
        });
    });

    // Initial load
    loadSettings();
});
