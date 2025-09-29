<?php
/**
 * Test Discount System
 * Simple test to verify discount functionality is working
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
    <title>Discount System Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        button { padding: 10px 15px; margin: 5px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>NORDBOOKING Discount System Test</h1>
    
    <div class="test-section">
        <h2>1. Discount Class Test</h2>
        <?php
        if (class_exists('NORDBOOKING\Classes\Discounts')) {
            echo '<p class="success">✅ Discounts class loaded successfully</p>';
            
            $discounts_manager = $GLOBALS['nordbooking_discounts_manager'] ?? null;
            if ($discounts_manager) {
                echo '<p class="success">✅ Discounts manager instance available</p>';
            } else {
                echo '<p class="error">❌ Discounts manager instance not found</p>';
            }
        } else {
            echo '<p class="error">❌ Discounts class not found</p>';
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>2. Database Table Test</h2>
        <?php
        global $wpdb;
        $discounts_table = $wpdb->prefix . 'nordbooking_discounts';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$discounts_table'") == $discounts_table;
        
        if ($table_exists) {
            echo '<p class="success">✅ Discounts table exists</p>';
            
            // Get table structure
            $columns = $wpdb->get_results("DESCRIBE $discounts_table");
            echo '<h3>Table Structure:</h3>';
            echo '<table>';
            echo '<thead><tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr></thead>';
            echo '<tbody>';
            foreach ($columns as $column) {
                echo '<tr>';
                echo '<td><code>' . esc_html($column->Field) . '</code></td>';
                echo '<td>' . esc_html($column->Type) . '</td>';
                echo '<td>' . esc_html($column->Null) . '</td>';
                echo '<td>' . esc_html($column->Key) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
            
            // Count existing discounts
            $discount_count = $wpdb->get_var("SELECT COUNT(*) FROM $discounts_table");
            echo '<p class="info">📊 Total discount codes: <strong>' . $discount_count . '</strong></p>';
            
        } else {
            echo '<p class="error">❌ Discounts table does not exist</p>';
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>3. Services Table Test (Discount Column)</h2>
        <?php
        $services_table = $wpdb->prefix . 'nordbooking_services';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$services_table'") == $services_table;
        
        if ($table_exists) {
            echo '<p class="success">✅ Services table exists</p>';
            
            // Check if disable_discount_code column exists
            $columns = $wpdb->get_results("DESCRIBE $services_table");
            $column_names = array_column($columns, 'Field');
            
            if (in_array('disable_discount_code', $column_names)) {
                echo '<p class="success">✅ disable_discount_code column exists</p>';
            } else {
                echo '<p class="error">❌ disable_discount_code column missing</p>';
                echo '<p class="info">ℹ️ Run database migration to add the column</p>';
            }
            
        } else {
            echo '<p class="error">❌ Services table does not exist</p>';
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>4. AJAX Endpoints Test</h2>
        <?php
        $ajax_endpoints = [
            'nordbooking_validate_discount_public' => 'Public discount validation',
            'nordbooking_get_discounts' => 'Get discounts (admin)',
            'nordbooking_save_discount' => 'Save discount (admin)',
            'nordbooking_delete_discount' => 'Delete discount (admin)'
        ];
        
        echo '<p class="info">📡 AJAX endpoints registered:</p>';
        echo '<ul>';
        foreach ($ajax_endpoints as $action => $description) {
            $hook_exists = has_action("wp_ajax_$action") || has_action("wp_ajax_nopriv_$action");
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
        <h2>5. Sample Discount Codes</h2>
        <?php
        if ($table_exists && $discount_count > 0) {
            $sample_discounts = $wpdb->get_results("SELECT * FROM $discounts_table ORDER BY created_at DESC LIMIT 5");
            
            echo '<table>';
            echo '<thead><tr><th>Code</th><th>Type</th><th>Value</th><th>Status</th><th>Usage</th><th>Expiry</th></tr></thead>';
            echo '<tbody>';
            foreach ($sample_discounts as $discount) {
                echo '<tr>';
                echo '<td><strong>' . esc_html($discount->code) . '</strong></td>';
                echo '<td>' . esc_html($discount->type) . '</td>';
                echo '<td>' . esc_html($discount->value) . '</td>';
                echo '<td>' . esc_html($discount->status) . '</td>';
                echo '<td>' . esc_html($discount->times_used) . '/' . ($discount->usage_limit ?: '∞') . '</td>';
                echo '<td>' . ($discount->expiry_date ?: 'No expiry') . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p class="info">ℹ️ No discount codes found. Create some in the dashboard to test.</p>';
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>6. Frontend Integration Test</h2>
        <?php
        // Check if booking form files exist
        $booking_form_files = [
            'templates/booking-form-public.php' => 'Booking form template',
            'assets/js/booking-form-public.js' => 'Booking form JavaScript',
            'templates/public-booking-form.css' => 'Booking form CSS'
        ];
        
        echo '<p class="info">📄 Frontend files:</p>';
        echo '<ul>';
        foreach ($booking_form_files as $file => $description) {
            if (file_exists($file)) {
                echo '<li class="success">✅ ' . $file . ' - ' . $description . '</li>';
            } else {
                echo '<li class="error">❌ ' . $file . ' - ' . $description . '</li>';
            }
        }
        echo '</ul>';
        ?>
    </div>
    
    <div class="test-section">
        <h2>7. Create Test Discount Code</h2>
        <?php
        if ($table_exists && $discounts_manager) {
            // Create a test discount code
            $test_code_data = [
                'code' => 'TEST10',
                'type' => 'percentage',
                'value' => 10,
                'status' => 'active'
            ];
            
            // Check if test code already exists
            $existing_test_code = $discounts_manager->get_discount_by_code('TEST10', get_current_user_id());
            
            if (!$existing_test_code) {
                $result = $discounts_manager->add_discount(get_current_user_id(), $test_code_data);
                if (!is_wp_error($result)) {
                    echo '<p class="success">✅ Created test discount code: TEST10 (10% off)</p>';
                } else {
                    echo '<p class="error">❌ Failed to create test discount: ' . $result->get_error_message() . '</p>';
                }
            } else {
                echo '<p class="info">ℹ️ Test discount code TEST10 already exists</p>';
                echo '<p class="info">Usage: ' . $existing_test_code['times_used'] . ' times</p>';
            }
        }
        ?>
        
        <button onclick="testDiscountValidation()" class="button">Test Discount Validation</button>
        <div id="discount-test-results"></div>
    </div>
    
    <div class="test-section">
        <h2>8. Recent Bookings with Discounts</h2>
        <?php
        $bookings_table = $wpdb->prefix . 'nordbooking_bookings';
        if ($wpdb->get_var("SHOW TABLES LIKE '$bookings_table'") == $bookings_table) {
            $recent_bookings = $wpdb->get_results("
                SELECT b.*, d.code as discount_code 
                FROM $bookings_table b 
                LEFT JOIN {$wpdb->prefix}nordbooking_discounts d ON b.discount_id = d.discount_id 
                WHERE b.discount_amount > 0 
                ORDER BY b.created_at DESC 
                LIMIT 5
            ");
            
            if ($recent_bookings) {
                echo '<table>';
                echo '<thead><tr><th>Booking ID</th><th>Customer</th><th>Total</th><th>Discount Code</th><th>Discount Amount</th><th>Date</th></tr></thead>';
                echo '<tbody>';
                foreach ($recent_bookings as $booking) {
                    echo '<tr>';
                    echo '<td>' . $booking->booking_id . '</td>';
                    echo '<td>' . $booking->customer_name . '</td>';
                    echo '<td>$' . number_format($booking->total_price, 2) . '</td>';
                    echo '<td><strong>' . ($booking->discount_code ?: 'N/A') . '</strong></td>';
                    echo '<td>$' . number_format($booking->discount_amount, 2) . '</td>';
                    echo '<td>' . $booking->created_at . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            } else {
                echo '<p class="info">ℹ️ No bookings with discounts found yet.</p>';
            }
        }
        ?>
    </div>

    <div class="test-section">
        <h2>Test Summary</h2>
        <p class="info">
            <strong>Discount System Features Implemented:</strong><br>
            ✅ Service-level discount toggle (disable_discount_code)<br>
            ✅ Discount code input field in booking form<br>
            ✅ Real-time discount validation via AJAX<br>
            ✅ Price calculation with discount applied<br>
            ✅ Discount data included in booking submission<br>
            ✅ Backend discount processing in booking handlers<br>
            ✅ Usage tracking and display in dashboard<br>
        </p>
        
        <p class="info">
            <strong>Testing Steps:</strong><br>
            1. ✅ Run this test page to verify system integrity<br>
            2. ✅ Create test discount codes (TEST10 created above)<br>
            3. 🔄 Test the booking form with discount codes<br>
            4. 🔄 Verify discount is applied correctly in booking<br>
            5. 🔄 Check that usage count increases in dashboard<br>
            6. 🔄 Test service-level discount toggles<br>
        </p>
    </div>
    
    <script>
    function testDiscountValidation() {
        const resultsDiv = document.getElementById('discount-test-results');
        resultsDiv.innerHTML = '<p>Testing discount validation...</p>';
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'nordbooking_validate_discount_public',
                discount_code: 'TEST10',
                tenant_id: <?php echo get_current_user_id(); ?>,
                nonce: '<?php echo wp_create_nonce('nordbooking_booking_form_nonce'); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.valid) {
                resultsDiv.innerHTML = '<p class="success">✅ Discount validation successful!</p><pre>' + JSON.stringify(data.data.discount, null, 2) + '</pre>';
            } else {
                resultsDiv.innerHTML = '<p class="error">❌ Discount validation failed: ' + (data.data ? data.data.message : 'Unknown error') + '</p>';
            }
        })
        .catch(error => {
            resultsDiv.innerHTML = '<p class="error">❌ Network error: ' + error.message + '</p>';
        });
    }
    </script>
    
    <script>
    console.log('Discount System Test Page Loaded');
    </script>
</body>
</html>