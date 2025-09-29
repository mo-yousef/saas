<?php
/**
 * Test Complete Discount Flow
 * Tests the entire discount flow from validation to booking creation
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
    <title>Test Discount Flow</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        button { padding: 10px 15px; margin: 5px; }
        .test-form { background: #f9f9f9; padding: 20px; margin: 10px 0; }
        input, select { margin: 5px; padding: 5px; }
    </style>
</head>
<body>
    <h1>Test Complete Discount Flow</h1>
    
    <div class="test-section">
        <h2>1. Setup Test Data</h2>
        <?php
        global $wpdb;
        $user_id = get_current_user_id();
        $discounts_manager = $GLOBALS['nordbooking_discounts_manager'] ?? null;
        
        // Create test discount if it doesn't exist
        if ($discounts_manager) {
            $test_discount = $discounts_manager->get_discount_by_code('TESTFLOW', $user_id);
            if (!$test_discount) {
                $result = $discounts_manager->add_discount($user_id, [
                    'code' => 'TESTFLOW',
                    'type' => 'percentage',
                    'value' => 15,
                    'status' => 'active'
                ]);
                if (!is_wp_error($result)) {
                    echo '<p class="success">✅ Created test discount: TESTFLOW (15% off)</p>';
                } else {
                    echo '<p class="error">❌ Failed to create test discount</p>';
                }
            } else {
                echo '<p class="info">ℹ️ Test discount TESTFLOW exists (Usage: ' . $test_discount['times_used'] . ')</p>';
            }
        }
        
        // Get a test service
        $services_table = $wpdb->prefix . 'nordbooking_services';
        $test_service = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $services_table WHERE user_id = %d AND status = 'active' LIMIT 1",
            $user_id
        ), ARRAY_A);
        
        if ($test_service) {
            echo '<p class="success">✅ Found test service: ' . $test_service['name'] . ' ($' . $test_service['price'] . ')</p>';
        } else {
            echo '<p class="error">❌ No test service found - create a service first</p>';
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>2. Test Discount Validation</h2>
        <button onclick="testDiscountValidation()">Test Validation</button>
        <div id="validation-results"></div>
    </div>
    
    <div class="test-section">
        <h2>3. Test Booking Creation with Discount</h2>
        <?php if ($test_service): ?>
        <div class="test-form">
            <h3>Simulate Booking Form Submission</h3>
            <p><strong>Service:</strong> <?php echo $test_service['name']; ?> ($<?php echo $test_service['price']; ?>)</p>
            <p><strong>Discount:</strong> TESTFLOW (15% off)</p>
            <p><strong>Expected Total:</strong> $<?php echo number_format($test_service['price'] * 0.85, 2); ?></p>
            <button onclick="testBookingCreation()">Create Test Booking</button>
        </div>
        <div id="booking-results"></div>
        <?php endif; ?>
    </div>
    
    <div class="test-section">
        <h2>4. Check Recent Bookings</h2>
        <button onclick="checkRecentBookings()">Check Recent Bookings</button>
        <div id="recent-bookings"></div>
    </div>
    
    <script>
    function testDiscountValidation() {
        const resultsDiv = document.getElementById('validation-results');
        resultsDiv.innerHTML = '<p>Testing discount validation...</p>';
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'nordbooking_validate_discount_public',
                discount_code: 'TESTFLOW',
                tenant_id: <?php echo $user_id; ?>,
                nonce: '<?php echo wp_create_nonce('nordbooking_booking_form_nonce'); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Validation Response:', data);
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
    
    function testBookingCreation() {
        const resultsDiv = document.getElementById('booking-results');
        resultsDiv.innerHTML = '<p>Creating test booking...</p>';
        
        const bookingData = {
            action: 'nordbooking_create_booking',
            tenant_id: <?php echo $user_id; ?>,
            nonce: '<?php echo wp_create_nonce('nordbooking_booking_form_nonce'); ?>',
            selected_services: JSON.stringify([{
                service_id: <?php echo $test_service['service_id'] ?? 0; ?>,
                configured_options: {}
            }]),
            service_options: JSON.stringify({}),
            customer_details: JSON.stringify({
                name: 'Test Customer',
                email: 'test@example.com',
                phone: '123-456-7890',
                address: '123 Test Street',
                date: '2024-12-25',
                time: '10:00',
                instructions: 'Test booking with discount'
            }),
            service_frequency: 'one-time',
            pet_information: JSON.stringify({has_pets: false, details: ''}),
            property_access: JSON.stringify({method: 'home', details: ''}),
            pricing: JSON.stringify({
                base: <?php echo $test_service['price'] ?? 0; ?>,
                options: 0,
                discount: <?php echo ($test_service['price'] ?? 0) * 0.15; ?>,
                total: <?php echo ($test_service['price'] ?? 0) * 0.85; ?>
            }),
            discount_code: 'TESTFLOW',
            discount_data: JSON.stringify({
                discount_id: 1,
                code: 'TESTFLOW',
                type: 'percentage',
                value: 15
            })
        };
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(bookingData)
        })
        .then(response => response.json())
        .then(data => {
            console.log('Booking Response:', data);
            if (data.success) {
                resultsDiv.innerHTML = '<p class="success">✅ Booking created successfully!</p><p>Booking ID: ' + data.booking_id + '</p>';
                checkRecentBookings();
            } else {
                resultsDiv.innerHTML = '<p class="error">❌ Booking creation failed: ' + (data.data ? data.data.message : data.message || 'Unknown error') + '</p><pre>' + JSON.stringify(data, null, 2) + '</pre>';
            }
        })
        .catch(error => {
            resultsDiv.innerHTML = '<p class="error">❌ Network error: ' + error.message + '</p>';
        });
    }
    
    function checkRecentBookings() {
        const resultsDiv = document.getElementById('recent-bookings');
        resultsDiv.innerHTML = '<p>Checking recent bookings...</p>';
        
        // This would need a custom AJAX endpoint to fetch recent bookings
        // For now, just show a message
        resultsDiv.innerHTML = '<p class="info">ℹ️ Check the WordPress admin for recent bookings with discounts applied.</p>';
    }
    </script>
</body>
</html>