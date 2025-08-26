jQuery(document).ready(function($) {
    'use strict';

    // Tab navigation
    const navTabs = $('.nav-tab-wrapper .nav-tab');
    const tabContents = $('.settings-tab-content');

    navTabs.on('click', function(e) {
        e.preventDefault();
        const tabId = $(this).data('tab');

        navTabs.removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        tabContents.hide();
        $('#' + tabId + '-settings-tab').show();

        // Update URL hash without jumping
        if (history.pushState) {
            history.pushState(null, null, '#' + tabId);
        } else {
            location.hash = '#' + tabId;
        }
    });

    // Check for hash on page load to activate correct tab
    if (window.location.hash) {
        const activeTab = navTabs.filter('[href="' + window.location.hash + '"]');
        if (activeTab.length) {
            activeTab.trigger('click');
        }
    }

    const form = $('#mobooking-business-settings-form');
    const feedbackDiv = $('#mobooking-settings-feedback');
    const saveButton = $('#mobooking-save-biz-settings-btn');
    const logoFileInput = $('#mobooking-logo-file-input');
    const uploadLogoBtn = $('#mobooking-upload-logo-btn');
    const removeLogoBtn = $('#mobooking-remove-logo-btn');
    const logoPreview = $('.logo-preview');
    const progressBarWrapper = $('.progress-bar-wrapper');
    const progressBar = $('.progress-bar');

    // Trigger file input click
    uploadLogoBtn.on('click', function(e) {
        e.preventDefault();
        logoFileInput.click();
    });

    // Handle file selection
    logoFileInput.on('change', function() {
        const file = this.files[0];
        if (file) {
            uploadLogo(file);
        }
    });

    // Handle logo upload
    function uploadLogo(file) {
        const formData = new FormData();
        formData.append('logo', file);
        formData.append('action', 'mobooking_upload_logo');
        formData.append('nonce', mobooking_biz_settings_params.nonce);

        $.ajax({
            url: mobooking_biz_settings_params.ajax_url,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(evt) {
                    if (evt.lengthComputable) {
                        const percentComplete = evt.loaded / evt.total;
                        const percent = Math.round(percentComplete * 100);
                        progressBar.width(percent + '%');
                    }
                }, false);
                return xhr;
            },
            beforeSend: function() {
                progressBar.width('0%');
                progressBarWrapper.show();
            },
            success: function(response) {
                if (response.success) {
                    $('#biz_logo_url').val(response.data.url);
                    logoPreview.html('<img src="' + response.data.url + '" alt="Company Logo">');
                    removeLogoBtn.show();
                    window.showAlert('Logo uploaded successfully.', 'success');
                } else {
                    window.showAlert(response.data.message || 'Error uploading logo.', 'error');
                }
            },
            error: function() {
                window.showAlert('AJAX error.', 'error');
            },
            complete: function() {
                progressBarWrapper.hide();
            }
        });
    }

    // Handle logo removal
    removeLogoBtn.on('click', function(e) {
        e.preventDefault();
        $('#biz_logo_url').val('');
        logoPreview.html('<div class="logo-placeholder"><span>No Logo</span></div>');
        $(this).hide();
    });

    // Handle send test email
    $('#mobooking-send-test-email-btn').on('click', function() {
        const $btn = $(this);
        const originalText = $btn.text();
        $btn.prop('disabled', true).text('Sending...');

        $.ajax({
            url: mobooking_biz_settings_params.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_send_test_email',
                nonce: mobooking_biz_settings_params.nonce
            },
            success: function(response) {
                if (response.success) {
                    window.showAlert(response.data.message, 'success');
                } else {
                    window.showAlert(response.data.message, 'error');
                }
            },
            error: function() {
                window.showAlert('AJAX error.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });

    // Form submission
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
                } else {
                    window.showAlert(response.data.message || mobooking_biz_settings_params.i18n.error_saving || 'Error saving.', 'error');
                }
            },
            error: function() {
                window.showAlert(mobooking_biz_settings_params.i18n.error_ajax || 'AJAX error.', 'error');
            },
            complete: function() {
                saveButton.prop('disabled', false).text(originalButtonText);
            }
        });
    });

    if (typeof mobooking_biz_settings_params === 'undefined') {
        console.error('mobooking_biz_settings_params is not defined. Please ensure it is localized.');
        window.mobooking_biz_settings_params = { nonce: '', ajax_url: '', i18n: {} };
    }
});
