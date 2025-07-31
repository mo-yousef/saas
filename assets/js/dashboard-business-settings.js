jQuery(document).ready(function($) {
    'use strict';

    const form = $('#mobooking-business-settings-form');
    const feedbackDiv = $('#mobooking-settings-feedback');
    const saveButton = $('#mobooking-save-biz-settings-btn');

    // Tab navigation
    const navTabs = $('.nav-tab-wrapper .nav-tab');
    const tabContents = $('.mobooking-settings-tab-content');

    navTabs.on('click', function(e) {
        e.preventDefault();
        const tabId = $(this).data('tab');

        navTabs.removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        tabContents.hide();
        $('#mobooking-' + tabId + '-tab').show();

        // Update URL hash without jumping
        if (history.pushState) {
            history.pushState(null, null, '#' + tabId + '-tab');
        } else {
            location.hash = '#' + tabId + '-tab';
        }
    });

    // Check for hash on page load to activate correct tab
    if (window.location.hash) {
        const activeTab = navTabs.filter('[href="' + window.location.hash + '"]');
        if (activeTab.length) {
            activeTab.trigger('click');
        } else {
            // Default to first tab if hash is invalid or doesn't match
             navTabs.first().trigger('click');
        }
    } else {
        // Default to first tab if no hash
        navTabs.first().trigger('click');
    }

    // Initial settings are now loaded by PHP.
    // The loadSettings() and populateForm() functions are no longer needed for initial load.

    form.on('submit', function(e) {
        e.preventDefault();
        feedbackDiv.empty().removeClass('notice-success notice-error').hide();
        const originalButtonText = saveButton.text();
        saveButton.prop('disabled', true).text(mobooking_biz_settings_params.i18n.saving || 'Saving...');

        let settingsData = {};
        form.find(':input:not([type="submit"])').each(function() {
            const $field = $(this);
            const name = $field.attr('name');
            if (!name) return;

            if ($field.is(':checkbox')) {
                settingsData[name] = $field.is(':checked') ? '1' : '0';
            } else {
                settingsData[name] = $field.val();
            }
        });

        // Validate JSON for biz_hours_json before sending (optional, server validates too)
        if(settingsData.biz_hours_json && settingsData.biz_hours_json.trim() !== ""){
            try {
                JSON.parse(settingsData.biz_hours_json);
            } catch(e) {
                feedbackDiv.text(mobooking_biz_settings_params.i18n.invalid_json || 'Business Hours JSON is not valid.').addClass('notice notice-error').show();
                saveButton.prop('disabled', false).text(originalButtonText);
                return;
            }
        }


        $.ajax({
            url: mobooking_biz_settings_params.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_save_business_settings',
                nonce: mobooking_biz_settings_params.nonce,
                settings: settingsData
            },
            success: function(response) {
                if (response.success) {
                    window.showAlert(response.data.message || mobooking_biz_settings_params.i18n.save_success || 'Settings saved.', 'success');
                    // Force page reload on successful save to apply new settings (like language), after a short delay
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    window.showAlert(response.data.message || mobooking_biz_settings_params.i18n.error_saving || 'Error saving.', 'error');
                }
            },
            error: function() {
                window.showAlert(mobooking_biz_settings_params.i18n.error_ajax || 'AJAX error.', 'error');
            },
            complete: function() {
                saveButton.prop('disabled', false).text(originalButtonText);
                setTimeout(function() { feedbackDiv.fadeOut(function(){ $(this).empty().removeClass('notice-success notice-error'); }); }, 5000);
            }
        });
    });

    // Initial load by JavaScript is removed. PHP handles populating the form.
    // Ensure mobooking_biz_settings_params (especially nonce, ajax_url, and i18n strings)
    // are correctly localized in your plugin's PHP enqueue script.
    if (typeof mobooking_biz_settings_params === 'undefined') {
        console.error('mobooking_biz_settings_params is not defined. Please ensure it is localized.');
        // Provide a fallback to prevent JS errors if params are missing.
        window.mobooking_biz_settings_params = { nonce: '', ajax_url: '', i18n: {} };
    }
});
