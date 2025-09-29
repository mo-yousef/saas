<?php
/**
 * Debug Discount System
 * Step-by-step debugging of discount functionality
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
    <title>Debug Discount System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; background: #f9f9f9; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        .debug-code { background: #f0f0f0; padding: 10px; margin: 10px 0; font-family: monospace; }
        button { padding: 10px 15px; margin: 5px; }
    </style>
</head>
<body>
    <h1>NORDBOOKING Discount System Debug</h1>
    
    <div class="debug-section">
        <h2>Step 1: Test Discount Creation</h2>
        <?php
        $discounts_manager = $GLOBALS['nordbooking_discounts_manager'] ?? null;
        $user_id = get_current_user_id();
        
        if ($discounts_manager) {
            echo '<p class="success">✅ Discounts manager available</p>';
            
            // Create a test discount
            $test_discount = [
                'code' => 'DEBUG20',
                'type' => 'percentage',
                'value' => 20,
                'status' => 'active'
            ];
            
            $existing = $discounts_manager->get_discount_by_code('DEBUG20', $user_id);
            if (!$existing) {
                $result = $discounts_manager->add_discount($user_id, $test_discount);
                if (!is_wp_error($result)) {
                    echo '<p class="success">✅ Created test discount: DEBUG20</p>';
                    $discount_id = $result;
                } else {
                    echo '<p class="error">❌ Failed to create discount: ' . $result->get_error_message() . '</p>';
                }
            } else {
                echo '<p class="info">ℹ️ Test discount DEBUG20 already exists</p>';
                $discount_id = $existing['discount_id'];
            }
        } else {
            echo '<p class="error">❌ Discounts manager not available</p>';
        }
        ?>
    </div>
    
    <div class="debug-section">
        <h2>Step 2: Test Discount Validation</h2>
        <?php
        if ($discounts_manager && isset($discount_id)) {
            $validation = $discounts_manager->validate_discount('DEBUG20', $user_id);
            if (!is_wp_error($validation)) {
                echo '<p class="success">✅ Discount validation successful</p>';
                echo '<div class="debug-code">';
                echo 'Discount Data:<br>';
                echo 'ID: ' . $validation['discount_id'] . '<br>';
                echo 'Code: ' . $validation['code'] . '<br>';
                echo 'Type: ' . $validation['type'] . '<br>';
                echo 'Value: ' . $validation['value'] . '<br>';
                echo 'Times Used: ' . $validation['times_used'] . '<br>';
                echo '</div>';
            } else {
                echo '<p class="error">❌ Discount validation failed: ' . $validation->get_error_message() . '</p>';
            }
        }
        ?>
    </div>
    
    <div class="debug-section">
        <h2>Step 3: Test Usage Increment</h2>
        <?php
        if ($discounts_manager && isset($discount_id)) {
            $before_usage = $discounts_manager->get_discount($discount_id, $user_id);
            echo '<p class="info">Usage before increment: ' . $before_usage['times_used'] . '</p>';
            
            $increment_result = $discounts_manager->increment_discount_usage($discount_id);
            if ($increment_result) {
                echo '<p class="success">✅ Usage increment successful</p>';
                
                $after_usage = $discounts_manager->get_discount($discount_id, $user_id);
                echo '<p class="info">Usage after increment: ' . $after_usage['times_used'] . '</p>';
            } else {
                echo '<p class="error">❌ Usage increment failed</p>';
            }
        }
        ?>
    </div>
    
    <div class="debug-section">
        <h2>Step 4: Test Discount Calculation</h2>
        <div class="debug-code">
            <strong>Test Scenario:</strong><br>
            Service Price: $100<br>
            Options Price: $20<br>
            Subtotal: $120<br>
            Discount: DEBUG20 (20%)<br>
            Expected Discount Amount: $24<br>
            Expected Final Total: $96<br>
        </div>
        
        <?php
        if (isset($validation)) {
            $subtotal = 120;
            $discount_amount = 0;
            
            if ($validation['type'] === 'percentage') {
                $discount_amount = ($subtotal * floatval($validation['value'])) / 100;
            } elseif ($validation['type'] === 'fixed_amount') {
                $discount_amount = min(floatval($validation['value']), $subtotal);
            }
            
            $final_total = max(0, $subtotal - $discount_amount);
            
            echo '<div class="debug-code">';
            echo '<strong>Calculated Results:</strong><br>';
            echo 'Discount Amount: $' . number_format($discount_amount, 2) . '<br>';
            echo 'Final Total: $' . number_format($final_total, 2) . '<br>';
            echo '</div>';
            
            if ($discount_amount == 24 && $final_total == 96) {
                echo '<p class="success">✅ Discount calculation is correct</p>';
            } else {
                echo '<p class="error">❌ Discount calculation is incorrect</p>';
            }
        }
        ?>
    </div>
    
    <div class="debug-section">
        <h2>Step 5: Test AJAX Endpoint</h2>
        <button onclick="testAjaxEndpoint()">Test AJAX Validation</button>
        <div id="ajax-results"></div>
    </div>
    
    <div class="debug-section">
        <h2>Step 6: Test Database Schema</h2>
        <?php
        global $wpdb;
        
        // Check bookings table for discount columns
        $bookings_table = $wpdb->prefix . 'nordbooking_bookings';
        $columns = $wpdb->get_results("DESCRIBE $bookings_table");
        $column_names = array_column($columns, 'Field');
        
        echo '<p class="info">Checking bookings table columns:</p>';
        echo '<ul>';
        if (in_array('discount_id', $column_names)) {
            echo '<li class="success">✅ discount_id column exists</li>';
        } else {
            echo '<li class="error">❌ discount_id column missing</li>';
        }
        
        if (in_array('discount_amount', $column_names)) {
            echo '<li class="success">✅ discount_amount column exists</li>';
        } else {
            echo '<li class="error">❌ discount_amount column missing</li>';
        }
        echo '</ul>';
        
        // Check services table for disable_discount_code column
        $services_table = $wpdb->prefix . 'nordbooking_services';
        $service_columns = $wpdb->get_results("DESCRIBE $services_table");
        $service_column_names = array_column($service_columns, 'Field');
        
        echo '<p class="info">Checking services table columns:</p>';
        echo '<ul>';
        if (in_array('disable_discount_code', $service_column_names)) {
            echo '<li class="success">✅ disable_discount_code column exists</li>';
        } else {
            echo '<li class="error">❌ disable_discount_code column missing</li>';
        }
        echo '</ul>';
        ?>
    </div>
    
    <script>
    function testAjaxEndpoint() {
        const resultsDiv = document.getElementById('ajax-results');
        resultsDiv.innerHTML = '<p>Testing AJAX endpoint...</p>';
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'nordbooking_validate_discount_public',
                discount_code: 'DEBUG20',
                tenant_id: <?php echo get_current_user_id(); ?>,
                nonce: '<?php echo wp_create_nonce('nordbooking_booking_form_nonce'); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('AJAX Response:', data);
            if (data.success && data.data.valid) {
                resultsDiv.innerHTML = '<p class="success">✅ AJAX endpoint working correctly!</p><pre>' + JSON.stringify(data, null, 2) + '</pre>';
            } else {
                resultsDiv.innerHTML = '<p class="error">❌ AJAX endpoint failed: ' + (data.data ? data.data.message : 'Unknown error') + '</p><pre>' + JSON.stringify(data, null, 2) + '</pre>';
            }
        })
        .catch(error => {
            resultsDiv.innerHTML = '<p class="error">❌ Network error: ' + error.message + '</p>';
        });
    }
    </script>
</body>
</html>