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
        // Optionally, provide a fallback or simpler input if color picker is essential.
        // For now, this console warning and the note in PHP are sufficient.
    }

    // Initial settings are now loaded by PHP.
    // The loadSettings() and populateForm() functions are no longer needed for initial load.

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

    // Initial load by JavaScript is removed. PHP handles populating the form.

    // Copy to clipboard functionality
    function copyToClipboard(element, feedbackElement, buttonElement) {
        if (!element || !element.value) return;

        navigator.clipboard.writeText(element.value)
            .then(function() {
                const originalButtonText = buttonElement.text();
                feedbackElement.text(mobooking_bf_settings_params.i18n.copied || 'Copied!').show();
                buttonElement.text(mobooking_bf_settings_params.i18n.copied || 'Copied!');
                setTimeout(function() {
                    feedbackElement.fadeOut().empty();
                    buttonElement.text(originalButtonText);
                }, 2000);
            })
            .catch(function(err) {
                console.error('Failed to copy text: ', err);
                feedbackElement.text(mobooking_bf_settings_params.i18n.copy_failed || 'Copy failed.').addClass('error').show();
                 setTimeout(function() {
                    feedbackElement.fadeOut().empty().removeClass('error');
                }, 2000);
            });
    }

    $('#mobooking-copy-public-link-btn').on('click', function() {
        copyToClipboard(
            document.getElementById('mobooking-public-link'),
            $('#mobooking-copy-link-feedback'),
            $(this)
        );
    });

    $('#mobooking-copy-embed-code-btn').on('click', function() {
        copyToClipboard(
            document.getElementById('mobooking-embed-code'),
            $('#mobooking-copy-embed-feedback'),
            $(this)
        );
    });


    // Ensure mobooking_bf_settings_params (especially nonce, ajax_url, and i18n strings)
    // are correctly localized in your plugin's PHP enqueue script.
    if (typeof mobooking_bf_settings_params === 'undefined') {
        console.error('mobooking_bf_settings_params is not defined. Please ensure it is localized.');
        // Provide a fallback to prevent JS errors if params are missing, though functionality will be broken.
        window.mobooking_bf_settings_params = { nonce: '', ajax_url: '', i18n: { copied: "Copied!", copy_failed: "Copy failed."} };
    } else if (typeof mobooking_bf_settings_params.i18n === 'undefined') {
        mobooking_bf_settings_params.i18n = { copied: "Copied!", copy_failed: "Copy failed."};
    } else {
        mobooking_bf_settings_params.i18n.copied = mobooking_bf_settings_params.i18n.copied || "Copied!";
        mobooking_bf_settings_params.i18n.copy_failed = mobooking_bf_settings_params.i18n.copy_failed || "Copy failed.";
    }
});
