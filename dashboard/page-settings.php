<?php
/**
 * Redesigned Dashboard Page: Settings
 * @package NORDBOOKING
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings_manager = new \NORDBOOKING\Classes\Settings();
$user_id = get_current_user_id();
$update_message = '';

// Handle form submission for non-AJAX fallback
if ( isset( $_POST['save_business_settings'] ) ) {
    // Verify nonce
    if ( isset( $_POST['nordbooking_dashboard_nonce_field'] ) && wp_verify_nonce( $_POST['nordbooking_dashboard_nonce_field'], 'nordbooking_dashboard_nonce' ) ) {

        // Sanitize and prepare settings data from POST
        $settings_to_save = [];
        // A list of expected setting keys and their sanitization functions
        $allowed_settings = [
            'biz_name' => 'sanitize_text_field',
            'biz_email' => 'sanitize_email',
            'biz_phone' => 'sanitize_text_field',
            'biz_address' => 'sanitize_textarea_field',
            'biz_currency_code' => 'sanitize_text_field',
            'biz_user_language' => 'sanitize_text_field',
            'biz_logo_url' => 'esc_url_raw',
            'bf_theme_color' => 'sanitize_hex_color',
            'bf_secondary_color' => 'sanitize_hex_color',
            'bf_background_color' => 'sanitize_hex_color',
            // Note: Email templates are complex and saved via AJAX in the new design.
            // This fallback handles the main settings.
        ];

        foreach ( $allowed_settings as $key => $sanitizer ) {
            if ( isset( $_POST[$key] ) ) {
                $settings_to_save[$key] = call_user_func( $sanitizer, wp_unslash($_POST[$key]) );
            }
        }

        // Save the settings
        $result = $settings_manager->save_business_settings( $user_id, $settings_to_save );

        if ( $result ) {
            $update_message = '<div class="notice notice-success is-dismissible" style="margin-bottom: 15px;"><p>' . esc_html__( 'Settings saved successfully.', 'NORDBOOKING' ) . '</p></div>';
        } else {
            $update_message = '<div class="notice notice-error is-dismissible" style="margin-bottom: 15px;"><p>' . esc_html__( 'There was an error saving the settings.', 'NORDBOOKING' ) . '</p></div>';
        }

    } else {
        // Nonce verification failed
        $update_message = '<div class="notice notice-error is-dismissible" style="margin-bottom: 15px;"><p>' . esc_html__( 'Security check failed. Please try again.', 'NORDBOOKING' ) . '</p></div>';
    }
}

// Fetch settings for display (re-fetch in case they were just updated)
$biz_settings = $settings_manager->get_business_settings($user_id);
$bf_settings = $settings_manager->get_booking_form_settings($user_id);


// Helper functions
function nordbooking_get_biz_setting_value($settings, $key, $default = '') {
    return isset($settings[$key]) ? esc_attr($settings[$key]) : esc_attr($default);
}
function nordbooking_get_biz_setting_textarea($settings, $key, $default = '') {
    return isset($settings[$key]) ? esc_textarea($settings[$key]) : esc_textarea($default);
}
function nordbooking_select_biz_setting_value($settings, $key, $value, $default_value = '') {
    $current_val = isset($settings[$key]) ? $settings[$key] : $default_value;
    return selected($value, $current_val, false);
}
?>
<div id="NORDBOOKING-business-settings-page" class="wrap NORDBOOKING-settings-page">
    <?php
    // Display any update messages
    if ( ! empty( $update_message ) ) {
        echo $update_message;
    }
    ?>
    <div class="nordbooking-page-header">
        <div class="nordbooking-page-header-heading">
            <span class="nordbooking-page-header-icon">
                <?php echo nordbooking_get_dashboard_menu_icon('settings'); ?>
            </span>
            <?php
            $biz_name = nordbooking_get_biz_setting_value($biz_settings, 'biz_name');
            $page_title = !empty($biz_name) ? sprintf(esc_html__('%s Settings', 'NORDBOOKING'), esc_html($biz_name)) : esc_html__('Business Settings', 'NORDBOOKING');
            ?>
            <h1><?php echo $page_title; ?></h1>
        </div>
        <div class="nordbooking-page-header-actions">
            <button type="submit" form="NORDBOOKING-business-settings-form" name="save_business_settings" id="NORDBOOKING-save-biz-settings-btn" class="btn btn-primary"><?php esc_html_e('Save All Settings', 'NORDBOOKING'); ?></button>
        </div>
    </div>

    <h2 class="nav-tab-wrapper" style="margin-bottom:20px;">
        <a href="#general" class="nav-tab nav-tab-active" data-tab="general"><?php esc_html_e('General', 'NORDBOOKING'); ?></a>
        <a href="#branding" class="nav-tab" data-tab="branding"><?php esc_html_e('Branding', 'NORDBOOKING'); ?></a>
        <a href="#email-notifications" class="nav-tab" data-tab="email-notifications"><?php esc_html_e('Email Notifications', 'NORDBOOKING'); ?></a>
    </h2>

    <form id="NORDBOOKING-business-settings-form" method="post">
        <?php wp_nonce_field('nordbooking_dashboard_nonce', 'nordbooking_dashboard_nonce_field'); ?>
        <div id="NORDBOOKING-settings-feedback" style="margin-bottom:15px; margin-top:10px;"></div>

        <div id="general-tab" class="settings-tab-content">
            <p class="page-description"><?php esc_html_e('Manage your core business information and localization.', 'NORDBOOKING'); ?></p>
            <div class="settings-layout">
                <!-- Left Column -->
                <div class="settings-column">
                    <!-- Business Details Card -->
                    <div class="nordbooking-card">
                        <div class="nordbooking-card-header">
                            <h3 class="nordbooking-card-title"><?php esc_html_e('Business Details', 'NORDBOOKING'); ?></h3>
                        </div>
                        <div class="nordbooking-card-content">
                            <div class="form-group">
                                <label for="biz_name"><?php esc_html_e('Business Name', 'NORDBOOKING'); ?></label>
                                <input name="biz_name" type="text" id="biz_name" value="<?php echo nordbooking_get_biz_setting_value($biz_settings, 'biz_name'); ?>" class="regular-text">
                                <p class="description"><?php esc_html_e('The public name of your business.', 'NORDBOOKING'); ?></p>
                            </div>
                            <div class="form-group">
                                <label for="biz_email"><?php esc_html_e('Public Business Email', 'NORDBOOKING'); ?></label>
                                <input name="biz_email" type="email" id="biz_email" value="<?php echo nordbooking_get_biz_setting_value($biz_settings, 'biz_email'); ?>" class="regular-text">
                                <p class="description"><?php esc_html_e('Email address for customer communication.', 'NORDBOOKING'); ?></p>
                            </div>
                            <div class="form-group">
                                <label for="biz_phone"><?php esc_html_e('Business Phone', 'NORDBOOKING'); ?></label>
                                <input name="biz_phone" type="tel" id="biz_phone" value="<?php echo nordbooking_get_biz_setting_value($biz_settings, 'biz_phone'); ?>" class="regular-text">
                            </div>
                            <div class="form-group">
                                <label for="biz_address"><?php esc_html_e('Business Address', 'NORDBOOKING'); ?></label>
                                <textarea name="biz_address" id="biz_address" class="large-text" rows="4"><?php echo nordbooking_get_biz_setting_textarea($biz_settings, 'biz_address'); ?></textarea>
                                <p class="description"><?php esc_html_e('Your primary business location.', 'NORDBOOKING'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                 <!-- Right Column -->
                <div class="settings-column">
                    <!-- Localization Card -->
                    <div class="nordbooking-card">
                        <div class="nordbooking-card-header">
                            <h3 class="nordbooking-card-title"><?php esc_html_e('Localization', 'NORDBOOKING'); ?></h3>
                        </div>
                        <div class="nordbooking-card-content">
                            <div class="form-group">
                                <label for="biz_currency_code"><?php esc_html_e('Currency', 'NORDBOOKING'); ?></label>
                                <select name="biz_currency_code" id="biz_currency_code" class="regular-text">
                                    <option value="USD" <?php echo nordbooking_select_biz_setting_value($biz_settings, 'biz_currency_code', 'USD', 'USD'); ?>>USD (US Dollar)</option>
                                    <option value="EUR" <?php echo nordbooking_select_biz_setting_value($biz_settings, 'biz_currency_code', 'EUR'); ?>>EUR (Euro)</option>
                                    <option value="GBP" <?php echo nordbooking_select_biz_setting_value($biz_settings, 'biz_currency_code', 'GBP'); ?>>GBP (British Pound)</option>
                                    <option value="CAD" <?php echo nordbooking_select_biz_setting_value($biz_settings, 'biz_currency_code', 'CAD'); ?>>CAD (Canadian Dollar)</option>
                                    <option value="AUD" <?php echo nordbooking_select_biz_setting_value($biz_settings, 'biz_currency_code', 'AUD'); ?>>AUD (Australian Dollar)</option>
                                </select>
                                <p class="description"><?php esc_html_e('Select your currency for pricing display.', 'NORDBOOKING'); ?></p>
                            </div>
                            <div class="form-group">
                                <label for="biz_user_language"><?php esc_html_e('Language', 'NORDBOOKING'); ?></label>
                                <select name="biz_user_language" id="biz_user_language" class="regular-text">
                                    <option value="en_US" <?php echo nordbooking_select_biz_setting_value($biz_settings, 'biz_user_language', 'en_US', 'en_US'); ?>>English (US)</option>
                                    <option value="sv_SE" <?php echo nordbooking_select_biz_setting_value($biz_settings, 'biz_user_language', 'sv_SE'); ?>>Swedish</option>
                                    <option value="nb_NO" <?php echo nordbooking_select_biz_setting_value($biz_settings, 'biz_user_language', 'nb_NO'); ?>>Norwegian</option>
                                    <option value="da_DK" <?php echo nordbooking_select_biz_setting_value($biz_settings, 'biz_user_language', 'da_DK'); ?>>Danish</option>
                                </select>
                                 <p class="description"><?php esc_html_e("Select the language for the dashboard and booking form.", 'NORDBOOKING'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="branding-tab" class="settings-tab-content" style="display:none;">
            <p class="page-description"><?php esc_html_e('Customize the look and feel of your booking form and emails.', 'NORDBOOKING'); ?></p>
            <div class="settings-layout">
                <div class="settings-column">
                    <!-- Logo Card -->
                    <div class="nordbooking-card">
                        <div class="nordbooking-card-header">
                            <h3 class="nordbooking-card-title"><?php esc_html_e('Company Logo', 'NORDBOOKING'); ?></h3>
                        </div>
                        <div class="nordbooking-card-content">
                            <div class="form-group">
                                <div class="logo-uploader-wrapper">
                                    <div class="logo-preview">
                                        <?php
                                        $logo_url = nordbooking_get_biz_setting_value($biz_settings, 'biz_logo_url');
                                        if (!empty($logo_url)) : ?>
                                            <img src="<?php echo esc_url($logo_url); ?>" alt="<?php esc_attr_e('Company Logo', 'NORDBOOKING'); ?>">
                                        <?php else : ?>
                                            <div class="logo-placeholder">
                                                <span><?php esc_html_e('No Logo', 'NORDBOOKING'); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="logo-uploader-actions">
                                        <input type="file" id="NORDBOOKING-logo-file-input" accept="image/png, image/jpeg, image/gif" style="display: none;">
                                        <button type="button" class="btn btn-secondary" id="NORDBOOKING-upload-logo-btn"><?php esc_html_e('Upload Logo', 'NORDBOOKING'); ?></button>
                                        <button type="button" class="btn btn-link" id="NORDBOOKING-remove-logo-btn" style="<?php echo empty($logo_url) ? 'display:none;' : ''; ?>"><?php esc_html_e('Remove', 'NORDBOOKING'); ?></button>
                                    </div>
                                </div>
                                <input name="biz_logo_url" type="hidden" id="biz_logo_url" value="<?php echo esc_url($logo_url); ?>">
                                <div class="progress-bar-wrapper" style="display:none;">
                                    <div class="progress-bar"></div>
                                </div>
                                <p class="description"><?php esc_html_e('Upload a logo for your emails and booking form.', 'NORDBOOKING'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="settings-column">
                    <!-- Theme Colors Card -->
                    <div class="nordbooking-card">
                        <div class="nordbooking-card-header">
                            <h3 class="nordbooking-card-title"><?php esc_html_e('Theme Colors', 'NORDBOOKING'); ?></h3>
                        </div>
                        <div class="nordbooking-card-content">
                            <div class="form-group">
                                <label for="bf_theme_color"><?php esc_html_e('Primary Color', 'NORDBOOKING'); ?></label>
                                <input name="bf_theme_color" type="text" id="bf_theme_color" value="<?php echo nordbooking_get_biz_setting_value($bf_settings, 'bf_theme_color', '#1abc9c'); ?>" class="form-input NORDBOOKING-color-picker">
                            </div>
                            <div class="form-group">
                                <label for="bf_secondary_color"><?php esc_html_e('Secondary Color', 'NORDBOOKING'); ?></label>
                                <input name="bf_secondary_color" type="text" id="bf_secondary_color" value="<?php echo nordbooking_get_biz_setting_value($bf_settings, 'bf_secondary_color', '#34495e'); ?>" class="form-input NORDBOOKING-color-picker">
                            </div>
                            <div class="form-group">
                                <label for="bf_background_color"><?php esc_html_e('Background Color', 'NORDBOOKING'); ?></label>
                                <input name="bf_background_color" type="text" id="bf_background_color" value="<?php echo nordbooking_get_biz_setting_value($bf_settings, 'bf_background_color', '#ffffff'); ?>" class="form-input NORDBOOKING-color-picker">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="email-notifications-tab" class="settings-tab-content" style="display:none;">
            <div class="email-settings-layout">
                <div class="email-editor-column">
                    <div class="nordbooking-card">
                        <div class="nordbooking-card-header">
                            <h3 class="nordbooking-card-title"><?php esc_html_e('Edit Email Template', 'NORDBOOKING'); ?></h3>
                        </div>
                        <div class="nordbooking-card-content">
                            <div class="form-group">
                                <label for="email-template-selector"><?php esc_html_e('Select Email to Edit', 'NORDBOOKING'); ?></label>
                                <select id="email-template-selector" class="regular-text">
                                    <?php
                                    $email_templates = $settings_manager->get_email_templates();
                                    foreach ($email_templates as $key => $template) {
                                        echo '<option value="' . esc_attr($key) . '">' . esc_html($template['name']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div id="email-editor-container">
                                <div class="form-group">
                                    <label for="email-editor-subject"><?php esc_html_e('Subject', 'NORDBOOKING'); ?></label>
                                    <input type="text" id="email-editor-subject" class="regular-text" placeholder="<?php esc_attr_e('Email subject', 'NORDBOOKING'); ?>">
                                </div>
                                <div class="form-group">
                                    <label><?php esc_html_e('Body Components', 'NORDBOOKING'); ?></label>
                                    <div id="email-editor-body" class="email-editor-fields-wrapper">
                                        <!-- JS will render sortable components here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="nordbooking-card">
                        <div class="nordbooking-card-header">
                            <h3 class="nordbooking-card-title"><?php esc_html_e('Available Variables', 'NORDBOOKING'); ?></h3>
                        </div>
                        <div class="nordbooking-card-content">
                            <p class="description"><?php esc_html_e('Click any variable to copy it to your clipboard.', 'NORDBOOKING'); ?></p>
                            <ul id="email-variables-list">
                                <!-- Variables will be loaded by JS -->
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="email-preview-column">
                    <div class="nordbooking-card">
                        <div class="nordbooking-card-header">
                            <h3 class="nordbooking-card-title"><?php esc_html_e('Live Preview', 'NORDBOOKING'); ?></h3>
                        </div>
                        <div class="nordbooking-card-content">
                            <iframe id="email-preview-iframe" src="about:blank"></iframe>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- This button is outside the tabs for global save -->
        <p class="submit" style="margin-top:20px;">
             <button type="submit" form="NORDBOOKING-business-settings-form" name="save_business_settings" id="NORDBOOKING-save-biz-settings-btn-footer" class="btn btn-primary"><?php esc_html_e('Save All Settings', 'NORDBOOKING'); ?></button>
        </p>
    </form>
    
    <!-- Debug Section -->
    <div id="debug-info" style="margin-top: 20px; padding: 15px; background: #f0f0f0; border: 1px solid #ccc;">
        <h3>Debug Information</h3>
        <div id="debug-output"></div>
        <button type="button" id="test-basic-js">Test Basic JavaScript</button>
        <button type="button" id="test-jquery">Test jQuery</button>
        <button type="button" id="test-tabs">Test Tab Switching</button>
        <button type="button" id="test-form-save">Test Form Save</button>
        <button type="button" id="force-load-params">Force Load Params</button>
        <button type="button" id="test-logo-endpoint">Test Logo Endpoint</button>
        <button type="button" id="test-ajax-general">Test AJAX General</button>
        <button type="button" id="test-user-caps">Test User Capabilities</button>
    </div>
    
    <script>
    // Basic JavaScript test (runs immediately)
    console.log('=== INLINE SCRIPT TEST ===');
    console.log('Basic JavaScript is working');
    
    // Test if jQuery is available
    if (typeof jQuery !== 'undefined') {
        console.log('jQuery is available:', jQuery.fn.jquery);
        
        jQuery(document).ready(function($) {
            console.log('jQuery document ready fired');
            
            $('#debug-output').html('<p>✓ jQuery is working (version: ' + $.fn.jquery + ')</p>');
            
            // Test basic button clicks
            $('#test-basic-js').on('click', function() {
                alert('Basic JavaScript is working!');
                $('#debug-output').append('<p>✓ Basic JavaScript click handler works</p>');
            });
            
            $('#test-jquery').on('click', function() {
                $('#debug-output').append('<p>✓ jQuery click handler works</p>');
                console.log('Available nordbooking globals:', Object.keys(window).filter(k => k.includes('nordbooking')));
            });
            
            $('#test-tabs').on('click', function() {
                const tabs = $('.nav-tab-wrapper .nav-tab');
                const contents = $('.settings-tab-content');
                $('#debug-output').append('<p>Found ' + tabs.length + ' tabs and ' + contents.length + ' tab contents</p>');
                
                // Test clicking the second tab
                if (tabs.length > 1) {
                    $(tabs[1]).trigger('click');
                    $('#debug-output').append('<p>✓ Triggered click on second tab</p>');
                }
            });
            
            // Check if our business settings script loaded
            setTimeout(function() {
                if (typeof nordbooking_biz_settings_params !== 'undefined') {
                    $('#debug-output').append('<p>✓ Business settings params are loaded</p>');
                    $('#debug-output').append('<p>AJAX URL: ' + nordbooking_biz_settings_params.ajax_url + '</p>');
                    $('#debug-output').append('<p>Nonce: ' + nordbooking_biz_settings_params.nonce + '</p>');
                } else {
                    $('#debug-output').append('<p>✗ Business settings params are NOT loaded</p>');
                    
                    // Check what scripts are actually loaded
                    var scripts = [];
                    $('script[src*="dashboard"]').each(function() {
                        scripts.push($(this).attr('src'));
                    });
                    $('#debug-output').append('<p>Dashboard scripts found: ' + scripts.length + '</p>');
                    if (scripts.length > 0) {
                        $('#debug-output').append('<ul><li>' + scripts.join('</li><li>') + '</li></ul>');
                    }
                }
                
                if (typeof window.showAlert !== 'undefined') {
                    $('#debug-output').append('<p>✓ showAlert function is available</p>');
                } else {
                    $('#debug-output').append('<p>✗ showAlert function is NOT available</p>');
                }
                
                // Test if the business settings script file is accessible
                $.get('<?php echo NORDBOOKING_THEME_URI; ?>assets/js/dashboard-business-settings.js')
                    .done(function() {
                        $('#debug-output').append('<p>✓ Business settings JS file is accessible</p>');
                    })
                    .fail(function() {
                        $('#debug-output').append('<p>✗ Business settings JS file is NOT accessible</p>');
                    });
            }, 1000);
            
            // Test form save functionality
            $('#test-form-save').on('click', function() {
                if (typeof nordbooking_biz_settings_params !== 'undefined') {
                    $('#debug-output').append('<p>Testing AJAX save...</p>');
                    
                    $.ajax({
                        url: nordbooking_biz_settings_params.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'nordbooking_save_business_settings',
                            nonce: nordbooking_biz_settings_params.nonce,
                            settings: {test: 'value'}
                        },
                        success: function(response) {
                            $('#debug-output').append('<p>✓ AJAX Save Success: ' + JSON.stringify(response) + '</p>');
                        },
                        error: function(xhr, status, error) {
                            $('#debug-output').append('<p>✗ AJAX Save Error: ' + error + '</p>');
                        }
                    });
                } else {
                    $('#debug-output').append('<p>✗ Cannot test save - params not loaded</p>');
                }
            });
            
            // Force load parameters manually
            $('#force-load-params').on('click', function() {
                if (typeof nordbooking_biz_settings_params === 'undefined') {
                    // Create the parameters manually for testing
                    window.nordbooking_biz_settings_params = {
                        ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        nonce: '<?php echo wp_create_nonce('nordbooking_dashboard_nonce'); ?>',
                        i18n: {
                            saving: 'Saving...',
                            save_success: 'Settings saved successfully.',
                            error_saving: 'Error saving settings.',
                            error_ajax: 'An unexpected error occurred.'
                        }
                    };
                    $('#debug-output').append('<p>✓ Parameters manually loaded</p>');
                    
                    // Try to reinitialize tabs
                    const navTabs = $('.nav-tab-wrapper .nav-tab');
                    const tabContents = $('.settings-tab-content');
                    
                    navTabs.off('click').on('click', function(e) {
                        e.preventDefault();
                        const tabId = $(this).data('tab');
                        
                        navTabs.removeClass('nav-tab-active');
                        $(this).addClass('nav-tab-active');
                        
                        tabContents.hide();
                        $('#' + tabId + '-tab').show();
                        
                        $('#debug-output').append('<p>✓ Tab switched to: ' + tabId + '</p>');
                    });
                    
                    $('#debug-output').append('<p>✓ Tab functionality manually initialized</p>');
                } else {
                    $('#debug-output').append('<p>Parameters already loaded</p>');
                }
            });
            
            // Test logo upload endpoint
            $('#test-logo-endpoint').on('click', function() {
                $('#debug-output').append('<p>Testing logo upload endpoint...</p>');
                
                // Test with a simple POST to see if the endpoint exists
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'nordbooking_upload_logo',
                        nonce: '<?php echo wp_create_nonce('nordbooking_dashboard_nonce'); ?>',
                        test: 'true'
                    },
                    success: function(response) {
                        $('#debug-output').append('<p>✓ Logo endpoint responded: ' + JSON.stringify(response) + '</p>');
                    },
                    error: function(xhr, status, error) {
                        $('#debug-output').append('<p>✗ Logo endpoint error: ' + xhr.status + ' - ' + error + '</p>');
                        $('#debug-output').append('<p>Response: ' + xhr.responseText + '</p>');
                    }
                });
            });
            
            // Test general AJAX functionality
            $('#test-ajax-general').on('click', function() {
                $('#debug-output').append('<p>Testing general AJAX...</p>');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'nordbooking_test_ajax',
                        nonce: '<?php echo wp_create_nonce('nordbooking_dashboard_nonce'); ?>'
                    },
                    success: function(response) {
                        $('#debug-output').append('<p>✓ General AJAX works: ' + JSON.stringify(response) + '</p>');
                    },
                    error: function(xhr, status, error) {
                        $('#debug-output').append('<p>✗ General AJAX error: ' + xhr.status + ' - ' + error + '</p>');
                    }
                });
            });
            
            // Test user capabilities
            $('#test-user-caps').on('click', function() {
                $('#debug-output').append('<p>Testing user capabilities...</p>');
                $('#debug-output').append('<p>Current user ID: <?php echo get_current_user_id(); ?></p>');
                $('#debug-output').append('<p>User roles: <?php $user = wp_get_current_user(); echo implode(", ", $user->roles); ?></p>');
                $('#debug-output').append('<p>Can upload_files: <?php echo current_user_can("upload_files") ? "YES" : "NO"; ?></p>');
                $('#debug-output').append('<p>Can edit_posts: <?php echo current_user_can("edit_posts") ? "YES" : "NO"; ?></p>');
                $('#debug-output').append('<p>Can manage_options: <?php echo current_user_can("manage_options") ? "YES" : "NO"; ?></p>');
            });
        });
    } else {
        console.error('jQuery is NOT available');
        document.getElementById('debug-output').innerHTML = '<p style="color: red;">✗ jQuery is NOT available</p>';
    }
    </script>
    
    <!-- Fallback Business Settings Script -->
    <script>
    jQuery(document).ready(function($) {
        console.log('=== FALLBACK BUSINESS SETTINGS SCRIPT ===');
        
        // Create parameters if they don't exist
        if (typeof nordbooking_biz_settings_params === 'undefined') {
            window.nordbooking_biz_settings_params = {
                ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
                nonce: '<?php echo wp_create_nonce('nordbooking_dashboard_nonce'); ?>',
                i18n: {
                    saving: 'Saving...',
                    save_success: 'Settings saved successfully.',
                    error_saving: 'Error saving settings.',
                    error_ajax: 'An unexpected error occurred.'
                }
            };
            console.log('Fallback: Created nordbooking_biz_settings_params');
        }
        
        // Ensure showAlert is available
        if (typeof window.showAlert !== 'function') {
            window.showAlert = function(message, type) {
                console.log('Fallback showAlert:', type, message);
                alert(type.toUpperCase() + ': ' + message);
            };
            console.log('Fallback: Created showAlert function');
        }
        
        // Initialize tab navigation
        const navTabs = $('.nav-tab-wrapper .nav-tab');
        const tabContents = $('.settings-tab-content');
        
        console.log('Fallback: Found', navTabs.length, 'tabs and', tabContents.length, 'tab contents');
        
        // Remove any existing handlers and add new ones
        navTabs.off('click.fallback').on('click.fallback', function(e) {
            e.preventDefault();
            const tabId = $(this).data('tab');
            console.log('Fallback: Tab clicked:', tabId);
            
            navTabs.removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            tabContents.hide();
            const targetTab = $('#' + tabId + '-tab');
            targetTab.show();
            
            // Update URL hash
            if (history.pushState) {
                history.pushState(null, null, '#' + tabId);
            } else {
                location.hash = '#' + tabId;
            }
            
            console.log('Fallback: Switched to tab:', tabId);
        });
        
        // Activate tab based on URL hash
        if (window.location.hash) {
            const hashWithoutHash = window.location.hash.substring(1);
            const activeTab = navTabs.filter('[data-tab="' + hashWithoutHash + '"]');
            if (activeTab.length) {
                activeTab.trigger('click.fallback');
                console.log('Fallback: Activated tab from hash:', hashWithoutHash);
            } else {
                navTabs.first().trigger('click.fallback');
            }
        } else {
            navTabs.first().trigger('click.fallback');
        }
        
        // Initialize form submission
        const form = $('#NORDBOOKING-business-settings-form');
        const saveButtons = $('#NORDBOOKING-save-biz-settings-btn, #NORDBOOKING-save-biz-settings-btn-footer');
        
        form.off('submit.fallback').on('submit.fallback', function(e) {
            e.preventDefault();
            console.log('Fallback: Form submission started');
            
            const originalButtonText = saveButtons.first().text();
            saveButtons.prop('disabled', true).text('Saving...');
            
            // Serialize form data
            let settingsData = $(this).serializeArray().reduce((obj, item) => {
                obj[item.name] = item.value;
                return obj;
            }, {});
            
            console.log('Fallback: Settings data:', settingsData);
            
            $.ajax({
                url: nordbooking_biz_settings_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'nordbooking_save_business_settings',
                    nonce: nordbooking_biz_settings_params.nonce,
                    settings: settingsData
                },
                success: function(response) {
                    console.log('Fallback: AJAX response:', response);
                    if (response.success) {
                        window.showAlert(response.data.message || 'Settings saved successfully.', 'success');
                    } else {
                        window.showAlert(response.data.message || 'Error saving settings.', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Fallback: AJAX error:', xhr, status, error);
                    window.showAlert('An unexpected error occurred. Please try again.', 'error');
                },
                complete: function() {
                    saveButtons.prop('disabled', false).text(originalButtonText);
                }
            });
        });
        
        // Initialize color picker if available
        if (typeof $.fn.wpColorPicker === 'function') {
            $('.NORDBOOKING-color-picker').wpColorPicker();
            console.log('Fallback: Color picker initialized');
        }
        
        // Initialize logo upload functionality
        const logoFileInput = $('#NORDBOOKING-logo-file-input');
        const uploadLogoBtn = $('#NORDBOOKING-upload-logo-btn');
        const removeLogoBtn = $('#NORDBOOKING-remove-logo-btn');
        const logoPreview = $('.logo-preview');
        const progressBarWrapper = $('.progress-bar-wrapper');
        const progressBar = $('.progress-bar');
        
        // Completely remove ALL existing event handlers and data to prevent duplicates
        uploadLogoBtn.off().removeData();
        logoFileInput.off().removeData();
        removeLogoBtn.off().removeData();
        
        // Add our handlers with a delay to ensure other scripts have finished
        setTimeout(function() {
            uploadLogoBtn.on('click.fallback-only', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Fallback: Upload button clicked (single handler)');
                logoFileInput.trigger('click');
            });
        }, 100);
        
        setTimeout(function() {
            logoFileInput.on('change.fallback-only', function(e) {
                e.stopPropagation();
                console.log('Fallback: File selected (single handler):', this.files[0]?.name);
                if (this.files[0]) {
                    uploadLogo(this.files[0]);
                }
            });
        }, 100);
        
        function uploadLogo(file) {
            console.log('Fallback: Starting logo upload:', file.name);
            
            const formData = new FormData();
            formData.append('logo', file);
            formData.append('action', 'nordbooking_upload_logo');
            formData.append('nonce', nordbooking_biz_settings_params.nonce);
            
            $.ajax({
                url: nordbooking_biz_settings_params.ajax_url,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                xhr: function() {
                    const xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(evt) {
                        if (evt.lengthComputable) {
                            const percent = Math.round((evt.loaded / evt.total) * 100);
                            progressBar.width(percent + '%');
                        }
                    }, false);
                    return xhr;
                },
                beforeSend: function() {
                    progressBar.width('0%');
                    progressBarWrapper.show();
                    console.log('Fallback: Upload started');
                },
                success: function(response) {
                    console.log('Fallback: Upload response:', response);
                    if (response.success) {
                        $('#biz_logo_url').val(response.data.url);
                        logoPreview.html('<img src="' + response.data.url + '" alt="Company Logo">');
                        removeLogoBtn.show();
                        window.showAlert('Logo uploaded successfully.', 'success');
                    } else {
                        window.showAlert(response.data.message || 'Error uploading logo.', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Fallback: Upload error:', xhr, status, error);
                    window.showAlert('Upload failed: ' + error, 'error');
                },
                complete: function() {
                    progressBarWrapper.hide();
                    console.log('Fallback: Upload complete');
                }
            });
        }
        
        setTimeout(function() {
            removeLogoBtn.on('click.fallback-only', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $('#biz_logo_url').val('');
                logoPreview.html('<div class="logo-placeholder"><span>No Logo</span></div>');
                $(this).hide();
                console.log('Fallback: Logo removed (single handler)');
            });
        }, 100);
        
        console.log('Fallback: Logo upload functionality initialized');
        console.log('Fallback: Business settings initialization complete');
    });
    </script>
</div>
