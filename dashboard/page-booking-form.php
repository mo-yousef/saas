<?php
/**
 * Dashboard Page: Booking Form Settings
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Fetch settings
$settings_manager = new \MoBooking\Classes\Settings();
$user_id = get_current_user_id();
$bf_settings = $settings_manager->get_booking_form_settings($user_id);
$biz_settings = $settings_manager->get_business_settings($user_id); // For biz_name

// Helper to get value, escape it, and provide default
function mobooking_get_setting_value($settings, $key, $default = '') {
    return isset($settings[$key]) ? esc_attr($settings[$key]) : esc_attr($default);
}
function mobooking_get_setting_textarea($settings, $key, $default = '') {
    return isset($settings[$key]) ? esc_textarea($settings[$key]) : esc_textarea($default);
}
function mobooking_is_setting_checked($settings, $key, $default_is_checked = false) {
    $val = isset($settings[$key]) ? $settings[$key] : ($default_is_checked ? '1' : '0');
    return checked('1', $val, false);
}

// Get current public booking form URL
$current_slug = mobooking_get_setting_value($bf_settings, 'bf_business_slug', '');
if (empty($current_slug) && !empty($biz_settings['biz_name'])) {
    $current_slug = sanitize_title($biz_settings['biz_name']);
}

$public_booking_url = '';
if (!empty($current_slug)) {
    // Use the new standardized URL structure
    $public_booking_url = trailingslashit(site_url()) . 'booking/' . esc_attr($current_slug) . '/';
}

?>
<div>
    <h3 class="text-3xl font-medium text-gray-700 dark:text-gray-200">Booking Form Settings</h3>
    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Customize the appearance and behavior of your public booking form.</p>

    <form id="mobooking-booking-form-settings-form" method="post" class="mt-6">
        <?php wp_nonce_field('mobooking_dashboard_nonce', 'mobooking_dashboard_nonce_field'); ?>
        <div id="mobooking-settings-feedback" class="hidden p-4 mb-4 text-sm rounded-lg"></div>

        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="flex -mb-px space-x-8" aria-label="Tabs">
                <a href="#mobooking-general-settings-tab" class="px-1 py-4 text-sm font-medium text-indigo-600 border-b-2 border-indigo-500 whitespace-nowrap" data-tab="general">General Settings</a>
                <a href="#mobooking-form-control-settings-tab" class="px-1 py-4 text-sm font-medium text-gray-500 border-b-2 border-transparent whitespace-nowrap hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300" data-tab="form-control">Form Control</a>
                <a href="#mobooking-design-settings-tab" class="px-1 py-4 text-sm font-medium text-gray-500 border-b-2 border-transparent whitespace-nowrap hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300" data-tab="design">Design & Styling</a>
                <a href="#mobooking-advanced-settings-tab" class="px-1 py-4 text-sm font-medium text-gray-500 border-b-2 border-transparent whitespace-nowrap hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300" data-tab="advanced">Advanced</a>
                <a href="#mobooking-share-embed-settings-tab" class="px-1 py-4 text-sm font-medium text-gray-500 border-b-2 border-transparent whitespace-nowrap hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300" data-tab="share-embed">Share & Embed</a>
            </nav>
        </div>

        <div id="mobooking-general-settings-tab" class="mt-6">
            <div class="p-6 bg-white rounded-md shadow-md dark:bg-gray-800">
                <h2 class="text-lg font-semibold text-gray-700 capitalize dark:text-white">General Settings</h2>
                <div class="grid grid-cols-1 gap-6 mt-4 sm:grid-cols-2">
                    <div>
                        <label class="text-gray-700 dark:text-gray-200" for="bf_business_slug">Business Slug</label>
                        <input id="bf_business_slug" type="text" name="bf_business_slug" value="<?php echo esc_attr($current_slug); ?>"
                               class="block w-full px-4 py-2 mt-2 text-gray-700 bg-white border border-gray-300 rounded-md dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring">
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Unique slug for your public booking page URL.</p>
                    </div>
                    <div>
                        <label class="text-gray-700 dark:text-gray-200" for="bf_header_text">Form Header Text</label>
                        <input id="bf_header_text" type="text" name="bf_header_text" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_header_text', 'Book Our Services Online'); ?>"
                               class="block w-full px-4 py-2 mt-2 text-gray-700 bg-white border border-gray-300 rounded-md dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring">
                    </div>
                    <div>
                        <label class="text-gray-700 dark:text-gray-200" for="bf_terms_conditions_url">Terms & Conditions URL</label>
                        <input id="bf_terms_conditions_url" type="url" name="bf_terms_conditions_url" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_terms_conditions_url'); ?>"
                               class="block w-full px-4 py-2 mt-2 text-gray-700 bg-white border border-gray-300 rounded-md dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring">
                    </div>
                    <div class="flex items-center">
                        <label class="flex items-center text-gray-700 dark:text-gray-200">
                            <input type="checkbox" name="bf_show_progress_bar" value="1" <?php echo mobooking_is_setting_checked($bf_settings, 'bf_show_progress_bar', true); ?>
                                   class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="ml-2">Show Progress Bar</span>
                        </label>
                    </div>
                    <div class="col-span-2">
                        <label class="text-gray-700 dark:text-gray-200" for="bf_success_message">Success Message</label>
                        <textarea id="bf_success_message" name="bf_success_message"
                                  class="block w-full px-4 py-2 mt-2 text-gray-700 bg-white border border-gray-300 rounded-md dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring"><?php echo mobooking_get_setting_textarea($bf_settings, 'bf_success_message', 'Thank you for your booking! We will contact you soon to confirm the details. A confirmation email has been sent to you.'); ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end mt-4">
            <button type="submit" name="save_booking_form_settings" id="mobooking-save-bf-settings-btn"
                    class="px-4 py-2 font-medium tracking-wide text-white capitalize transition-colors duration-200 transform bg-indigo-600 rounded-md hover:bg-indigo-500 focus:outline-none focus:bg-indigo-500">
                Save Settings
            </button>
        </div>
    </form>
</div>

<style>

.form-table th {
    width: 200px;
    font-weight: 600;
}

.form-table td {
    padding: 15px 10px;
}

.form-table input[type="text"],
.form-table input[type="url"],
.form-table input[type="number"],
.form-table select,
.form-table textarea {
    font-size: 14px;
}

.form-table .description {
    margin-top: 5px;
    font-style: italic;
    color: #666;
}

fieldset label {
    display: inline-block;
    margin-right: 20px;
    font-weight: normal;
}

#mobooking-settings-feedback {
    max-width: 100%;
}

#mobooking-settings-feedback.notice {
    padding: 10px 15px;
    margin: 5px 0 15px 0;
    border-left: 4px solid;
    background: #fff;
}

#mobooking-settings-feedback.notice-success {
    border-left-color: #46b450;
    background: #f7fcf0;
}

#mobooking-settings-feedback.notice-error {
    border-left-color: #dc3232;
    background: #fef7f1;
}

.button.button-secondary:disabled {
    color: #a0a5aa !important;
    border-color: #ddd !important;
    background: #f6f7f7 !important;
    cursor: not-allowed;
}

/* QR Code styling */
#qr-code-container img {
    border-radius: 8px;
}

/* Embed customization */
.widefat td {
    padding: 10px;
    border-bottom: 1px solid #f0f0f0;
}

.widefat td:first-child {
    font-weight: 600;
    width: 100px;
}

/* Color picker alignment */
.wp-picker-container {
    display: inline-block;
}

/* Responsive adjustments */
@media screen and (max-width: 782px) {
    .form-table th,
    .form-table td {
        display: block;
        width: auto;
        padding: 10px 0;
    }
    
    .form-table th {
        padding-bottom: 5px;
    }
    
    .nav-tab-wrapper {
        border-bottom: 1px solid #ccd0d4;
    }
    
    .nav-tab {
        display: block;
        margin: 0;
        border-bottom: 1px solid #ccd0d4;
    }
    
    .nav-tab:last-child {
        border-bottom: none;
    }
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    'use strict';

    // Use the existing localized parameters from the external JS file
    // The external file already handles initialization and fallbacks for mobooking_bf_settings_params
    
    const form = $('#mobooking-booking-form-settings-form');
    const feedbackDiv = $('#mobooking-settings-feedback');
    const saveButton = $('#mobooking-save-bf-settings-btn');

    // Fix tab navigation to work with our new tab structure
    const navTabs = $('.nav-tab-wrapper .nav-tab');
    const tabContents = $('.mobooking-settings-tab-content');

    navTabs.on('click', function(e) {
        e.preventDefault();
        navTabs.removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        tabContents.hide();
        $('#mobooking-' + $(this).data('tab') + '-settings-tab').show();
    });

    // Initialize Color Picker (WordPress handles this)
    if (typeof $.fn.wpColorPicker === 'function') {
        $('.mobooking-color-picker').wpColorPicker();
    }

    // Dynamic update for public link and embed code
    const businessSlugInput = $('#bf_business_slug');
    const publicLinkInput = $('#mobooking-public-link');
    const embedCodeTextarea = $('#mobooking-embed-code');
    const copyLinkBtn = $('#mobooking-copy-public-link-btn');
    const copyEmbedBtn = $('#mobooking-copy-embed-code-btn');
    const qrCodeImage = $('#qr-code-image');
    const downloadQrBtn = $('#download-qr-btn');

    // Use the site URL from the existing parameters
    let baseSiteUrl = (typeof mobooking_bf_settings_params !== 'undefined' && mobooking_bf_settings_params.site_url) 
        ? mobooking_bf_settings_params.site_url 
        : window.location.origin + '/';
        
    if (baseSiteUrl.slice(-1) !== '/') {
        baseSiteUrl += '/';
    }

    function updateShareableLinks(slug) {
        const sanitizedSlug = slug.trim().toLowerCase().replace(/[^a-z0-9-]+/g, '-').replace(/^-+|-+$/g, '');
        if (sanitizedSlug) {
            const publicLink = baseSiteUrl + 'booking/' + sanitizedSlug + '/';
            const embedLink = baseSiteUrl + 'embed-booking/' + sanitizedSlug + '/'; // New embed link structure
            const embedCode = `<iframe src="${embedLink}" title="Booking Form" style="width:100%; height:800px; border:1px solid #ccc;"></iframe>`;
            
            publicLinkInput.val(publicLink);
            embedCodeTextarea.val(embedCode);
            copyLinkBtn.prop('disabled', false);
            copyEmbedBtn.prop('disabled', false);
            
            // Update QR code
            if (qrCodeImage.length) {
                qrCodeImage.attr('src', `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(publicLink)}`);
            }
            if (downloadQrBtn.length) {
                downloadQrBtn.prop('disabled', false);
            }
            
            // Update preview link if it exists
            // Ensure we target the correct preview link associated with the public URL input field
            const previewLink = publicLinkInput.closest('td').find('a[target="_blank"].button-secondary');
            if (previewLink.length) {
                previewLink.attr('href', publicLink);
            }
        } else {
            publicLinkInput.val('').attr('placeholder', 'Link will appear here once slug is saved.');
            embedCodeTextarea.val('').attr('placeholder', 'Embed code will appear here once slug is saved.');
            copyLinkBtn.prop('disabled', true);
            copyEmbedBtn.prop('disabled', true);
            if (downloadQrBtn.length) {
                downloadQrBtn.prop('disabled', true);
            }
        }
    }

    if (businessSlugInput.length) {
        businessSlugInput.on('input', function() {
            updateShareableLinks($(this).val());
        });
        updateShareableLinks(businessSlugInput.val()); // Initial update
    }

    // Copy to clipboard functionality
    function copyToClipboard(text, button) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(function() {
                showCopySuccess(button);
            }).catch(function() {
                fallbackCopyTextToClipboard(text, button);
            });
        } else {
            fallbackCopyTextToClipboard(text, button);
        }
    }

    function fallbackCopyTextToClipboard(text, button) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            showCopySuccess(button);
        } catch (err) {
            console.error('Fallback: Oops, unable to copy', err);
            showCopyError(button);
        }
        
        document.body.removeChild(textArea);
    }

    function showCopySuccess(button) {
        const originalText = button.text();
        button.text('Copied!').addClass('button-primary').removeClass('button-secondary');
        setTimeout(function() {
            button.text(originalText).removeClass('button-primary').addClass('button-secondary');
        }, 2000);
    }

    function showCopyError(button) {
        const originalText = button.text();
        button.text('Copy failed').addClass('button-secondary');
        setTimeout(function() {
            button.text(originalText);
        }, 2000);
    }

    copyLinkBtn.on('click', function() {
        copyToClipboard(publicLinkInput.val(), $(this));
    });

    copyEmbedBtn.on('click', function() {
        copyToClipboard(embedCodeTextarea.val(), $(this));
    });

    // Embed customization
    $('#mobooking-customize-embed-btn').on('click', function() {
        $('#embed-customization-row').toggle();
    });

    $('#update-embed-code-btn').on('click', function() {
        const width = $('#embed-width').val() || '100%';
        const height = $('#embed-height').val() || '800px';
        const border = $('#embed-border').val() || '1px solid #ccc';
        const url = publicLinkInput.val();
        
        if (url) {
            const customEmbedCode = `<iframe src="${url}" title="Booking Form" style="width:${width}; height:${height}; border:${border};"></iframe>`;
            embedCodeTextarea.val(customEmbedCode);
        }
    });

    // QR Code download
    downloadQrBtn.on('click', function() {
        const qrUrl = qrCodeImage.attr('src');
        if (qrUrl) {
            const link = document.createElement('a');
            link.href = qrUrl;
            link.download = 'booking-form-qr-code.png';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    });

    // Form state management for enabling/disabling form
    $('#bf_form_enabled').on('change', function() {
        const isEnabled = $(this).is(':checked');
        const maintenanceRow = $('input[name="bf_maintenance_message"]').closest('tr');
        
        if (isEnabled) {
            maintenanceRow.hide();
        } else {
            maintenanceRow.show();
        }
    }).trigger('change'); // Initialize on page load

    // Note: Form submission is handled by the external dashboard-booking-form-settings.js file
    // which already has the proper AJAX handling and localized parameters
    
    console.log('MoBooking Enhanced Booking Form Settings initialized successfully.');
});
</script>





<script type="text/javascript">
jQuery(document).ready(function($) {
    // Simple form submission handler
    $('#mobooking-booking-form-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $button = $('#mobooking-save-bf-settings-btn');
        const $feedback = $('#mobooking-settings-feedback');
        
        // Show loading
        $button.prop('disabled', true).text('Saving...');
        $feedback.hide().removeClass('notice-success notice-error');
        
        // Collect form data
        const formData = new FormData(this);
        const settings = {};
        
        // Convert FormData to object
        $form.find('input, textarea, select').each(function() {
            const $field = $(this);
            const name = $field.attr('name');
            if (name && name !== 'save_booking_form_settings') {
                if ($field.is(':checkbox')) {
                    settings[name] = $field.is(':checked') ? '1' : '0';
                } else {
                    settings[name] = $field.val();
                }
            }
        });
        
        console.log('Sending data:', settings);
        
        // Make AJAX request
        $.ajax({
            url: ajaxurl || '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: {
                action: 'mobooking_save_booking_form_settings',
                nonce: $('[name="mobooking_dashboard_nonce_field"]').val(),
                settings: settings
            },
            success: function(response) {
                console.log('Response:', response);
                if (response.success) {
                    $feedback.text(response.data.message || 'Settings saved successfully!')
                           .addClass('notice notice-success')
                           .show();
                } else {
                    $feedback.text(response.data.message || 'Error saving settings')
                           .addClass('notice notice-error')
                           .show();
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr, status, error);
                $feedback.text('Network error: ' + error)
                       .addClass('notice notice-error')
                       .show();
            },
            complete: function() {
                $button.prop('disabled', false).text('Save Booking Form Settings');
            }
        });
    });
    
    // Test if scripts are loaded
    console.log('Booking form settings script loaded');
    if (typeof ajaxurl !== 'undefined') {
        console.log('AJAX URL:', ajaxurl);
    } else {
        console.log('ajaxurl not defined, using fallback');
        window.ajaxurl = '/wp-admin/admin-ajax.php';
    }
});
</script>