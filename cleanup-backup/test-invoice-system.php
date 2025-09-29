<?php
/**
 * Test Invoice System
 * Simple test to verify invoice functionality is working
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-config.php');
}

// Only allow admin access
if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Invoice System Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        button { padding: 10px 15px; margin: 5px; }
    </style>
</head>
<body>
    <h1>NORDBOOKING Invoice System Test</h1>
    
    <div class="test-section">
        <h2>1. Class Loading Test</h2>
        <?php
        if (class_exists('NORDBOOKING\Classes\InvoiceManager')) {
            echo '<p class="success">✅ InvoiceManager class loaded successfully</p>';
            
            $invoice_manager = \NORDBOOKING\Classes\InvoiceManager::getInstance();
            if ($invoice_manager) {
                echo '<p class="success">✅ InvoiceManager instance created successfully</p>';
            } else {
                echo '<p class="error">❌ Failed to create InvoiceManager instance</p>';
            }
        } else {
            echo '<p class="error">❌ InvoiceManager class not found</p>';
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>2. Stripe Configuration Test</h2>
        <?php
        if (class_exists('NORDBOOKING\Classes\StripeConfig')) {
            $is_configured = \NORDBOOKING\Classes\StripeConfig::is_configured();
            if ($is_configured) {
                echo '<p class="success">✅ Stripe is configured</p>';
                
                $is_test_mode = \NORDBOOKING\Classes\StripeConfig::is_test_mode();
                echo '<p class="info">ℹ️ Mode: ' . ($is_test_mode ? 'Test' : 'Live') . '</p>';
            } else {
                echo '<p class="error">❌ Stripe is not configured</p>';
                echo '<p class="info">ℹ️ Configure Stripe in the admin panel to test invoice functionality</p>';
            }
        } else {
            echo '<p class="error">❌ StripeConfig class not found</p>';
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>3. Invoice Statistics Test</h2>
        <?php
        if (class_exists('NORDBOOKING\Classes\InvoiceManager')) {
            $invoice_manager = \NORDBOOKING\Classes\InvoiceManager::getInstance();
            $stats = $invoice_manager->get_invoice_statistics();
            
            echo '<p class="info">📊 Invoice Statistics:</p>';
            echo '<ul>';
            echo '<li>Total Invoices: ' . $stats['total_invoices'] . '</li>';
            echo '<li>Total Revenue: ' . $stats['currency'] . ' ' . number_format($stats['total_revenue'], 2) . '</li>';
            echo '<li>This Month Revenue: ' . $stats['currency'] . ' ' . number_format($stats['this_month_revenue'], 2) . '</li>';
            echo '</ul>';
            
            if ($stats['total_invoices'] > 0) {
                echo '<p class="success">✅ Invoice statistics retrieved successfully</p>';
            } else {
                echo '<p class="info">ℹ️ No invoices found (this is normal for new installations)</p>';
            }
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>4. AJAX Endpoints Test</h2>
        <?php
        $ajax_endpoints = [
            'nordbooking_get_invoices' => 'Get user invoices',
            'nordbooking_get_invoice_pdf' => 'Get invoice PDF',
            'nordbooking_admin_get_customer_invoices' => 'Admin get customer invoices'
        ];
        
        echo '<p class="info">📡 AJAX endpoints registered:</p>';
        echo '<ul>';
        foreach ($ajax_endpoints as $action => $description) {
            $hook_exists = has_action("wp_ajax_$action");
            if ($hook_exists) {
                echo '<li class="success">✅ ' . $action . ' - ' . $description . '</li>';
            } else {
                echo '<li class="error">❌ ' . $action . ' - ' . $description . '</li>';
            }
        }
        echo '</ul>';
        ?>
    </div>
    
    <div class="test-section">
        <h2>5. Frontend Integration Test</h2>
        <?php
        $subscription_page = get_page_by_path('dashboard/subscription');
        if ($subscription_page) {
            echo '<p class="success">✅ Subscription page exists</p>';
            echo '<p class="info">📄 <a href="' . get_permalink($subscription_page->ID) . '" target="_blank">View Subscription Page</a></p>';
        } else {
            echo '<p class="error">❌ Subscription page not found</p>';
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>6. Admin Integration Test</h2>
        <?php
        if (class_exists('NORDBOOKING\Classes\Admin\ConsolidatedAdminPage')) {
            echo '<p class="success">✅ ConsolidatedAdminPage class loaded</p>';
            echo '<p class="info">📄 <a href="' . admin_url('admin.php?page=nordbooking-consolidated-admin') . '" target="_blank">View Admin Page</a></p>';
        } else {
            echo '<p class="error">❌ ConsolidatedAdminPage class not found</p>';
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>Test Summary</h2>
        <p class="info">
            <strong>Next Steps:</strong><br>
            1. Ensure Stripe is configured in the admin panel<br>
            2. Create a test subscription to generate invoices<br>
            3. Test the invoice list on the subscription page<br>
            4. Test the admin invoice viewing functionality
        </p>
    </div>
    
    <div class="test-section">
        <h2>7. JavaScript Test</h2>
        <div id="js-test-results">
            <p>Running JavaScript tests...</p>
        </div>
        <button onclick="testAjaxEndpoints()" class="button">Test AJAX Endpoints</button>
    </div>
    
    <script>
    // Simple JavaScript test for AJAX functionality
    console.log('Invoice System Test Page Loaded');
    
    jQuery(document).ready(function($) {
        var testResults = $('#js-test-results');
        var results = [];
        
        // Test if jQuery is available
        if (typeof jQuery !== 'undefined') {
            results.push('✅ jQuery is available');
        } else {
            results.push('❌ jQuery is not available');
        }
        
        // Test if admin AJAX URL is available
        if (typeof ajaxurl !== 'undefined') {
            results.push('✅ AJAX URL is available: ' + ajaxurl);
        } else {
            results.push('❌ AJAX URL is not available');
        }
        
        // Test if nordbooking_admin object is available
        if (typeof nordbooking_admin !== 'undefined') {
            results.push('✅ nordbooking_admin object is available');
        } else {
            results.push('❌ nordbooking_admin object is not available');
        }
        
        testResults.html('<ul><li>' + results.join('</li><li>') + '</li></ul>');
    });
    
    function testAjaxEndpoints() {
        jQuery(function($) {
            $('#js-test-results').append('<p>Testing AJAX endpoints...</p>');
            
            // Test the invoice statistics endpoint
            $.post(ajaxurl, {
                action: 'nordbooking_admin_get_customer_invoices',
                user_id: 1, // Test with user ID 1
                _ajax_nonce: '<?php echo wp_create_nonce('nordbooking_admin_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    $('#js-test-results').append('<p class="success">✅ AJAX endpoint test successful</p>');
                } else {
                    $('#js-test-results').append('<p class="info">ℹ️ AJAX endpoint responded (no invoices found is normal): ' + response.data.message + '</p>');
                }
            }).fail(function(xhr, status, error) {
                $('#js-test-results').append('<p class="error">❌ AJAX endpoint test failed: ' + error + '</p>');
            });
        });
    }
    </script>
</body>
</html>