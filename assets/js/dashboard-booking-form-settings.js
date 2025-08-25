jQuery(document).ready(function ($) {
    "use strict";

    // --- Toast Notification Function (assuming it exists) ---
    // Make sure a global function `window.showAlert(message, type)` is available.
    // Example: window.showAlert = (message, type = 'info') => { console.log(`${type}: ${message}`); };
    if (typeof window.showAlert !== 'function') {
        window.showAlert = (message, type = 'info') => {
            console.warn("No toast handler found. Implement `window.showAlert`.", { message, type });
            alert(`${type.toUpperCase()}: ${message}`);
        };
    }

    // --- Tab Navigation ---
    const tabs = $('.mobooking-tab-item');
    const panes = $('.mobooking-settings-tab-pane');

    function activateTab(tabId) {
        tabs.removeClass('active');
        panes.removeClass('active');

        const activeTab = tabs.filter(`[data-tab="${tabId}"]`);
        const activePane = $(`#${tabId}`);

        activeTab.addClass('active');
        activePane.addClass('active');

        if (history.replaceState) {
            history.replaceState(null, null, `#${tabId}`);
        }
    }

    tabs.on('click', function (e) {
        e.preventDefault();
        const tabId = $(this).data('tab');
        activateTab(tabId);
    });

    // Activate tab from URL hash on page load
    const urlHash = window.location.hash.substring(1);
    if (urlHash && panes.filter(`#${urlHash}`).length > 0) {
        activateTab(urlHash);
    } else {
        // Fallback to the first tab if hash is invalid or not present
        activateTab(tabs.first().data('tab'));
    }

    // --- Form Submission ---
    $("#mobooking-booking-form-settings-form").on("submit", function (e) {
        e.preventDefault();
        const form = $(this);
        const saveButton = $("#mobooking-save-bf-settings-btn");
        const originalButtonText = saveButton.text();
        const spinner = `<span class="spinner"></span>`;

        saveButton.prop("disabled", true).html(spinner + ' Saving...');

        const settingsData = {};
        form.find("input, textarea, select").each(function () {
            const field = $(this);
            const name = field.attr("name");
            if (!name || name.startsWith("save_")) return;

            if (field.is(":checkbox")) {
                settingsData[name] = field.is(":checked") ? "1" : "0";
            } else {
                settingsData[name] = field.val();
            }
        });

        // Use localized params if available, otherwise fallback
        const ajaxUrl = (window.mobooking_params?.ajax_url) || ajaxurl;
        const nonce = (window.mobooking_params?.nonce) || form.find('[name="mobooking_dashboard_nonce_field"]').val();

        $.ajax({
            url: ajaxUrl,
            type: "POST",
            dataType: "json",
            data: {
                action: "mobooking_save_booking_form_settings",
                nonce: nonce,
                settings: settingsData,
            },
            success: function (response) {
                if (response.success) {
                    showAlert(response.data?.message || "Settings saved successfully.", 'success');
                    // Potentially update UI elements like the public link if slug changes
                    if (settingsData.bf_business_slug) {
                        const newUrl = `${(window.mobooking_params?.site_url || '')}booking/${settingsData.bf_business_slug}/`;
                        $('#bf-public-link').attr('href', newUrl).text(newUrl);
                        $('#mobooking-public-link-input').val(newUrl);
                    }
                } else {
                    showAlert(response.data?.message || "Failed to save settings.", 'error');
                }
            },
            error: function (xhr) {
                const errorMsg = xhr.responseJSON?.data?.message || "A network error occurred.";
                showAlert(errorMsg, 'error');
            },
            complete: function () {
                saveButton.prop("disabled", false).text(originalButtonText);
            }
        });
    });

    // --- Real-time Interactions ---

    // Slug sanitization
    $("input[name='bf_business_slug']").on("input", function () {
        const input = $(this);
        const sanitized = input.val()
            .toLowerCase()
            .replace(/\s+/g, '-') // Replace spaces with -
            .replace(/[^a-z0-9-]/g, '') // Remove all non-alphanumeric chars except -
            .replace(/-+/g, '-'); // Replace multiple - with single -
        if (input.val() !== sanitized) {
            input.val(sanitized);
        }
    });

    // Toggle maintenance message field based on form enabled status
    $('#bf_form_enabled').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('#maintenance-message-group').toggle(!isChecked);
    }).trigger('change'); // Trigger on load

    // --- Share & Embed Section ---

    // Copy to clipboard
    function copyToClipboard(text, successMessage) {
        if (!navigator.clipboard) {
            showAlert("Clipboard API not available.", "error");
            return;
        }
        navigator.clipboard.writeText(text).then(() => {
            showAlert(successMessage, 'success');
        }).catch(err => {
            showAlert("Failed to copy.", "error");
            console.error('Could not copy text: ', err);
        });
    }

    $('#mobooking-copy-public-link-btn, #mobooking-copy-public-link-btn-2').on('click', function() {
        copyToClipboard($('#mobooking-public-link-input').val(), 'Public link copied!');
    });

    $('#mobooking-copy-embed-code-btn').on('click', function() {
        copyToClipboard($('#mobooking-embed-code').val(), 'Embed code copied!');
    });

    // Embed customization
    $('#mobooking-customize-embed-btn').on('click', function() {
        $('#embed-customization-row').slideToggle();
    });

    $('#update-embed-code-btn').on('click', function() {
        const width = $('#embed-width').val() || '100%';
        const height = $('#embed-height').val() || '800px';
        const border = $('#embed-border').val() || '1px solid #ccc';
        const baseUrl = $('#mobooking-public-link-input').val();

        if (baseUrl) {
            const iframe = `<iframe src="${baseUrl}" title="Booking Form" style="width:${width}; height:${height}; border:${border};"></iframe>`;
            $('#mobooking-embed-code').val(iframe);
            showAlert('Embed code updated.', 'success');
        }
    });

    // QR Code download
    $('#download-qr-btn').on('click', function() {
        const qrUrl = $('#qr-code-image').attr('src');
        if (qrUrl) {
            const link = document.createElement('a');
            link.href = qrUrl;
            link.download = 'booking-form-qr-code.png';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    });

    // Flush rewrite rules
    $('#mobooking-flush-rewrite-rules-btn').on('click', function() {
        const button = $(this);
        button.prop('disabled', true).text('Flushing...');

        $.post(ajaxurl, { action: 'mobooking_flush_rewrite_rules', nonce: window.mobooking_params.nonce })
            .done(response => {
                if (response.success) {
                    showAlert('Rewrite rules flushed successfully.', 'success');
                } else {
                    showAlert(response.data?.message || 'Failed to flush rewrite rules.', 'error');
                }
            })
            .fail(() => showAlert('An error occurred while flushing rules.', 'error'))
            .always(() => button.prop('disabled', false).text('Flush Rewrite Rules'));
    });

    // Initialize color pickers if WP color picker is available
    if (typeof $.fn.wpColorPicker === 'function') {
        $('.mobooking-color-picker').each(function() {
            // Check if it's a native color picker, if so, don't initialize wpColorPicker
            if (this.type !== 'color') {
                $(this).wpColorPicker();
            }
        });
    }
});
