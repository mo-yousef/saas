<?php
/**
 * Test Settings JavaScript Loading
 * Add this as a temporary page to test if scripts are loading
 */

// Add this to test the JavaScript loading independently
add_action('wp_ajax_test_settings_js', 'test_settings_js_callback');
add_action('wp_ajax_nopriv_test_settings_js', 'test_settings_js_callback');

function test_settings_js_callback() {
    wp_send_json_success(['message' => 'AJAX is working!', 'time' => current_time('mysql')]);
}

// Create a test page
add_action('init', function() {
    if (isset($_GET['test_settings_js']) && current_user_can('manage_options')) {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Test Settings JavaScript</title>
            <?php wp_head(); ?>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
                .success { color: green; }
                .error { color: red; }
                .nav-tab-wrapper { margin: 20px 0; }
                .nav-tab { 
                    display: inline-block; 
                    padding: 8px 12px; 
                    margin-right: 5px; 
                    background: #f1f1f1; 
                    border: 1px solid #ccc; 
                    text-decoration: none; 
                    cursor: pointer;
                }
                .nav-tab-active { background: #fff; border-bottom: 1px solid #fff; }
                .settings-tab-content { 
                    display: none; 
                    padding: 20px; 
                    border: 1px solid #ccc; 
                    margin-top: -1px;
                }
                .settings-tab-content:first-of-type { display: block; }
            </style>
        </head>
        <body>
            <h1>Settings JavaScript Test Page</h1>
            
            <div class="test-section">
                <h2>Script Loading Test</h2>
                <div id="script-test-results"></div>
            </div>

            <div class="test-section">
                <h2>Tab Navigation Test</h2>
                <h2 class="nav-tab-wrapper">
                    <a href="#general" class="nav-tab nav-tab-active" data-tab="general">General</a>
                    <a href="#branding" class="nav-tab" data-tab="branding">Branding</a>
                    <a href="#email-notifications" class="nav-tab" data-tab="email-notifications">Email</a>
                </h2>
                
                <div id="general-tab" class="settings-tab-content">
                    <h3>General Tab Content</h3>
                    <p>This is the general tab content.</p>
                </div>
                
                <div id="branding-tab" class="settings-tab-content">
                    <h3>Branding Tab Content</h3>
                    <p>This is the branding tab content.</p>
                </div>
                
                <div id="email-notifications-tab" class="settings-tab-content">
                    <h3>Email Tab Content</h3>
                    <p>This is the email tab content.</p>
                </div>
            </div>

            <div class="test-section">
                <h2>AJAX Test</h2>
                <button id="test-ajax-btn">Test AJAX</button>
                <div id="ajax-results"></div>
            </div>

            <div class="test-section">
                <h2>Form Test</h2>
                <form id="test-form">
                    <input type="text" name="test_field" value="Test Value" />
                    <button type="submit">Test Form Submit</button>
                </form>
                <div id="form-results"></div>
            </div>

            <script>
            jQuery(document).ready(function($) {
                console.log('=== Settings JavaScript Test ===');
                
                // Test 1: Check if jQuery is working
                $('#script-test-results').append('<p class="success">✓ jQuery is working</p>');
                
                // Test 2: Check if required globals are available
                if (typeof window.showAlert !== 'undefined') {
                    $('#script-test-results').append('<p class="success">✓ showAlert is available</p>');
                } else {
                    $('#script-test-results').append('<p class="error">✗ showAlert is NOT available</p>');
                    // Create fallback
                    window.showAlert = function(message, type) {
                        alert(type.toUpperCase() + ': ' + message);
                    };
                }
                
                // Test 3: Check WordPress Color Picker
                if (typeof $.fn.wpColorPicker !== 'undefined') {
                    $('#script-test-results').append('<p class="success">✓ WordPress Color Picker is available</p>');
                } else {
                    $('#script-test-results').append('<p class="error">✗ WordPress Color Picker is NOT available</p>');
                }
                
                // Test 4: Tab Navigation
                const navTabs = $('.nav-tab-wrapper .nav-tab');
                const tabContents = $('.settings-tab-content');
                
                $('#script-test-results').append('<p>Found ' + navTabs.length + ' tabs and ' + tabContents.length + ' tab contents</p>');
                
                navTabs.on('click', function(e) {
                    e.preventDefault();
                    const tabId = $(this).data('tab');
                    console.log('Tab clicked:', tabId);
                    
                    navTabs.removeClass('nav-tab-active');
                    $(this).addClass('nav-tab-active');
                    
                    tabContents.hide();
                    $('#' + tabId + '-tab').show();
                    
                    window.showAlert('Switched to ' + tabId + ' tab', 'success');
                });
                
                // Test 5: AJAX
                $('#test-ajax-btn').on('click', function() {
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'test_settings_js',
                            nonce: '<?php echo wp_create_nonce('test_nonce'); ?>'
                        },
                        success: function(response) {
                            $('#ajax-results').html('<p class="success">✓ AJAX Success: ' + JSON.stringify(response) + '</p>');
                        },
                        error: function(xhr, status, error) {
                            $('#ajax-results').html('<p class="error">✗ AJAX Error: ' + error + '</p>');
                        }
                    });
                });
                
                // Test 6: Form handling
                $('#test-form').on('submit', function(e) {
                    e.preventDefault();
                    const formData = $(this).serializeArray();
                    $('#form-results').html('<p class="success">✓ Form submit prevented. Data: ' + JSON.stringify(formData) + '</p>');
                    window.showAlert('Form test completed', 'success');
                });
                
                console.log('Test page initialization complete');
            });
            </script>
            
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
        exit;
    }
});
?>