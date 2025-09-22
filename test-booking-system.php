<?php
/**
 * Test Booking System
 * Simple test to verify booking functionality is working after database fixes
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
    <title>Booking System Test</title>
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
    <h1>NORDBOOKING Booking System Test</h1>
    
    <div class="test-section">
        <h2>1. Database Table Structure Test</h2>
        <?php
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'nordbooking_bookings';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$bookings_table'") == $bookings_table;
        
        if ($table_exists) {
            echo '<p class="success">✅ Bookings table exists</p>';
            
            // Get table structure
            $columns = $wpdb->get_results("DESCRIBE $bookings_table");
            
            echo '<h3>Current Table Structure:</h3>';
            echo '<table>';
            echo '<thead><tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr></thead>';
            echo '<tbody>';
            foreach ($columns as $column) {
                echo '<tr>';
                echo '<td><code>' . esc_html($column->Field) . '</code></td>';
                echo '<td>' . esc_html($column->Type) . '</td>';
                echo '<td>' . esc_html($column->Null) . '</td>';
                echo '<td>' . esc_html($column->Key) . '</td>';
                echo '<td>' . esc_html($column->Default) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
            
            // Check for critical columns
            $column_names = array_column($columns, 'Field');
            $critical_columns = ['booking_id', 'user_id', 'total_price', 'service_address', 'has_pets', 'pet_details', 'property_access_method'];
            
            echo '<h3>Critical Columns Check:</h3>';
            echo '<ul>';
            foreach ($critical_columns as $col) {
                if (in_array($col, $column_names)) {
                    echo '<li class="success">✅ <code>' . $col . '</code></li>';
                } else {
                    echo '<li class="error">❌ <code>' . $col . '</code> - MISSING</li>';
                }
            }
            echo '</ul>';
            
        } else {
            echo '<p class="error">❌ Bookings table does not exist</p>';
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>2. AJAX Endpoints Test</h2>
        <?php
        $ajax_endpoints = [
            'nordbooking_submit_booking' => 'Submit booking',
            'nordbooking_enhanced_submit_booking' => 'Enhanced submit booking',
            'nordbooking_submit_booking_fixed' => 'Fixed submit booking',
            'nordbooking_submit_booking_corrected' => 'Corrected submit booking'
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
        <h2>3. Recent Bookings</h2>
        <?php
        if ($table_exists) {
            $recent_bookings = $wpdb->get_results("SELECT * FROM $bookings_table ORDER BY created_at DESC LIMIT 5");
            
            if ($recent_bookings) {
                echo '<p class="success">✅ Found ' . count($recent_bookings) . ' recent bookings</p>';
                echo '<table>';
                echo '<thead><tr><th>ID</th><th>Reference</th><th>Customer</th><th>Date</th><th>Total</th><th>Status</th></tr></thead>';
                echo '<tbody>';
                foreach ($recent_bookings as $booking) {
                    echo '<tr>';
                    echo '<td>' . $booking->booking_id . '</td>';
                    echo '<td>' . $booking->booking_reference . '</td>';
                    echo '<td>' . $booking->customer_name . '</td>';
                    echo '<td>' . $booking->booking_date . '</td>';
                    echo '<td>$' . number_format($booking->total_price, 2) . '</td>';
                    echo '<td>' . $booking->status . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            } else {
                echo '<p class="info">ℹ️ No bookings found (this is normal for new installations)</p>';
            }
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>4. Database Error Check</h2>
        <?php
        // Check for recent database errors
        $error_log = ini_get('error_log');
        if ($error_log && file_exists($error_log)) {
            $recent_errors = shell_exec("tail -20 $error_log | grep -i 'nordbooking\|database error' | tail -5");
            if ($recent_errors) {
                echo '<p class="error">❌ Recent database errors found:</p>';
                echo '<pre style="background: #f8f8f8; padding: 10px; overflow: auto;">' . esc_html($recent_errors) . '</pre>';
            } else {
                echo '<p class="success">✅ No recent database errors found</p>';
            }
        } else {
            echo '<p class="info">ℹ️ Error log not accessible</p>';
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>5. Test Summary</h2>
        <p class="info">
            <strong>Database Column Fixes Applied:</strong><br>
            ✅ Changed 'total_amount' to 'total_price'<br>
            ✅ Changed 'customer_address' to 'service_address'<br>
            ✅ Removed 'selected_services' (non-existent column)<br>
            ✅ Split 'pet_information' into 'has_pets' and 'pet_details'<br>
            ✅ Split 'property_access' into 'property_access_method' and 'property_access_details'<br>
        </p>
        
        <p class="info">
            <strong>Next Steps:</strong><br>
            1. Try creating a new booking through the frontend<br>
            2. Check if the booking appears in the database<br>
            3. Verify no more database errors occur<br>
            4. Test the invoice system with active subscriptions
        </p>
    </div>
    
    <script>
    console.log('Booking System Test Page Loaded');
    
    // Test if jQuery is available
    if (typeof jQuery !== 'undefined') {
        console.log('✅ jQuery is available');
    } else {
        console.log('❌ jQuery is not available');
    }
    </script>
</body>
</html>