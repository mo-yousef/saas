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


    function loadSettings() {
        feedbackDiv.empty().removeClass('notice-success notice-error').hide();
        form.find(':input:not([type="submit"])').prop('disabled', true);

        $.ajax({
            url: mobooking_biz_settings_params.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_get_business_settings',
                nonce: mobooking_biz_settings_params.nonce
            },
            success: function(response) {
                if (response.success && response.data.settings) {
                    populateForm(response.data.settings);
                } else {
                    feedbackDiv.text(response.data.message || mobooking_biz_settings_params.i18n.error_loading || 'Error loading settings.').addClass('notice notice-error').show();
                }
            },
            error: function() {
                feedbackDiv.text(mobooking_biz_settings_params.i18n.error_ajax || 'AJAX error.').addClass('notice notice-error').show();
            },
            complete: function() {
                form.find(':input:not([type="submit"])').prop('disabled', false);
            }
        });
    }

    function populateForm(settings) {
        for (const key in settings) {
            if (settings.hasOwnProperty(key)) {
                const field = form.find(`[name="${key}"]`);
                if (field.length) {
                    if (field.is(':checkbox')) { // Though no checkboxes currently in biz settings
                        field.prop('checked', settings[key] === '1' || settings[key] === true);
                    } else {
                        field.val(settings[key]);
                    }
                }
            }
        }
    }

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
                    feedbackDiv.text(response.data.message || mobooking_biz_settings_params.i18n.save_success || 'Settings saved.').addClass('notice notice-success').show();
                } else {
                    feedbackDiv.text(response.data.message || mobooking_biz_settings_params.i18n.error_saving || 'Error saving.').addClass('notice notice-error').show();
                }
            },
            error: function() {
                feedbackDiv.text(mobooking_biz_settings_params.i18n.error_ajax || 'AJAX error.').addClass('notice notice-error').show();
            },
            complete: function() {
                saveButton.prop('disabled', false).text(originalButtonText);
                setTimeout(function() { feedbackDiv.fadeOut(function(){ $(this).empty().removeClass('notice-success notice-error'); }); }, 5000);
            }
        });
    });

    // Initial load
    if (form.length) { // Only if the form exists on the page
        loadSettings();
    }
});
