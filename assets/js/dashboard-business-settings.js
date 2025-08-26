jQuery(document).ready(function($) {
    'use strict';

    const form = $('#mobooking-business-settings-form');
    const feedbackDiv = $('#mobooking-settings-feedback');
    const saveButton = $('#mobooking-save-biz-settings-btn');
    let mediaUploader;

    // Logo Uploader Logic
    $('#mobooking-upload-logo-btn').on('click', function(e) {
        e.preventDefault();
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: 'Choose Logo',
            button: {
                text: 'Choose Logo'
            },
            multiple: false
        });
        mediaUploader.on('select', function() {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#biz_logo_url').val(attachment.url);
            $('.logo-preview').html('<img src="' + attachment.url + '" alt="Company Logo">');
            $('#mobooking-remove-logo-btn').show();
        });
        mediaUploader.open();
    });

    $('#mobooking-remove-logo-btn').on('click', function(e) {
        e.preventDefault();
        $('#biz_logo_url').val('');
        $('.logo-preview').html('<div class="logo-placeholder"><span>No Logo</span></div>');
        $(this).hide();
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
