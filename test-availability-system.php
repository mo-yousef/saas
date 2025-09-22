<?php
/**
 * Test Availability System
 */

if (!defined('ABSPATH')) {
    require_once('../../../wp-config.php');
}

get_header();
?>

<div class="container" style="max-width: 1000px; margin: 0 auto; padding: 2rem 1rem;">
    <h1>Availability System Test</h1>
    
    <div style="background: white; border-radius: 12px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2>System Status</h2>
        
        <?php
        // Test 1: Check if Availability class exists
        echo "<h3>1. Class Availability</h3>";
        if (class_exists('NORDBOOKING\Classes\Availability')) {
            echo "<p style='color: green;'>✅ Availability class exists</p>";
            
            $availability = new \NORDBOOKING\Classes\Availability();
            echo "<p style='color: green;'>✅ Availability class can be instantiated</p>";
        } else {
            echo "<p style='color: red;'>❌ Availability class not found</p>";
        }
        
        // Test 2: Check database tables
        echo "<h3>2. Database Tables</h3>";
        global $wpdb;
        $tables_to_check = ['availability_rules', 'availability_exceptions'];
        
        foreach ($tables_to_check as $table_suffix) {
            $table_name = \NORDBOOKING\Classes\Database::get_table_name($table_suffix);
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
            
            if ($table_exists) {
                echo "<p style='color: green;'>✅ $table_name exists</p>";
            } else {
                echo "<p style='color: orange;'>⚠️ $table_name missing (will be created when needed)</p>";
            }
        }
        
        // Test 3: Get sample business owners
        echo "<h3>3. Sample Business Owners</h3>";
        $business_owners = get_users(['role' => 'nordbooking_business_owner', 'number' => 5]);
        
        if (!empty($business_owners)) {
            echo "<p style='color: green;'>✅ Found " . count($business_owners) . " business owners</p>";
            
            foreach ($business_owners as $owner) {
                echo "<div style='background: #f8f9fa; padding: 1rem; margin: 0.5rem 0; border-radius: 6px;'>";
                echo "<strong>{$owner->display_name}</strong> (ID: {$owner->ID})<br>";
                echo "Email: {$owner->user_email}<br>";
                
                // Check if they have availability rules
                if (class_exists('NORDBOOKING\Classes\Availability')) {
                    $availability = new \NORDBOOKING\Classes\Availability();
                    $schedule = $availability->get_recurring_schedule($owner->ID);
                    
                    $has_schedule = false;
                    foreach ($schedule as $day) {
                        if ($day['is_enabled'] && !empty($day['slots'])) {
                            $has_schedule = true;
                            break;
                        }
                    }
                    
                    if ($has_schedule) {
                        echo "<span style='color: green;'>✅ Has availability schedule</span>";
                    } else {
                        echo "<span style='color: orange;'>⚠️ No availability schedule set</span>";
                    }
                }
                
                echo "</div>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ No business owners found. Create a business owner account first.</p>";
        }
        ?>
    </div>
    
    <?php if (!empty($business_owners)): ?>
    <div style="background: white; border-radius: 12px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2>Test Time Slots</h2>
        <p>Select a business owner and date to test the availability system:</p>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
            <div>
                <label for="test-owner" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Business Owner:</label>
                <select id="test-owner" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px;">
                    <option value="">Select owner...</option>
                    <?php foreach ($business_owners as $owner): ?>
                        <option value="<?php echo $owner->ID; ?>"><?php echo esc_html($owner->display_name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="test-date" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Date:</label>
                <input type="date" id="test-date" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px;" min="<?php echo date('Y-m-d'); ?>">
            </div>
        </div>
        
        <button id="test-availability-btn" style="background: #007cba; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; font-weight: 600; cursor: pointer;">
            Test Availability
        </button>
        
        <div id="test-results" style="margin-top: 2rem; display: none;">
            <h3>Available Time Slots</h3>
            <div id="test-time-slots" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 0.5rem; margin-top: 1rem;"></div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const testBtn = document.getElementById('test-availability-btn');
    const ownerSelect = document.getElementById('test-owner');
    const dateInput = document.getElementById('test-date');
    const resultsDiv = document.getElementById('test-results');
    const timeSlotsDiv = document.getElementById('test-time-slots');
    
    // Set default date to tomorrow
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    dateInput.value = tomorrow.toISOString().split('T')[0];
    
    testBtn.addEventListener('click', function() {
        const ownerId = ownerSelect.value;
        const date = dateInput.value;
        
        if (!ownerId || !date) {
            alert('Please select both a business owner and date.');
            return;
        }
        
        testBtn.disabled = true;
        testBtn.textContent = 'Testing...';
        timeSlotsDiv.innerHTML = '<p>Loading...</p>';
        resultsDiv.style.display = 'block';
        
        const formData = new FormData();
        formData.append('action', 'nordbooking_get_available_time_slots');
        formData.append('tenant_id', ownerId);
        formData.append('date', date);
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.time_slots) {
                displayTestTimeSlots(data.data.time_slots);
            } else {
                timeSlotsDiv.innerHTML = '<p style="color: #666;">No available times found for this date.</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            timeSlotsDiv.innerHTML = '<p style="color: #dc3545;">Error loading time slots: ' + error.message + '</p>';
        })
        .finally(() => {
            testBtn.disabled = false;
            testBtn.textContent = 'Test Availability';
        });
    });
    
    function displayTestTimeSlots(timeSlots) {
        if (timeSlots.length === 0) {
            timeSlotsDiv.innerHTML = '<p style="color: #666;">No available time slots for this date.</p>';
            return;
        }
        
        timeSlotsDiv.innerHTML = '';
        
        timeSlots.forEach(slot => {
            const button = document.createElement('div');
            button.style.cssText = 'padding: 0.75rem; border: 2px solid #e9ecef; background: white; color: #333; border-radius: 8px; text-align: center; font-weight: 500;';
            
            if (slot.available) {
                button.style.borderColor = '#28a745';
                button.style.background = '#d4edda';
                button.style.color = '#155724';
                button.innerHTML = '✅ ' + slot.display;
            } else {
                button.style.borderColor = '#dc3545';
                button.style.background = '#f8d7da';
                button.style.color = '#721c24';
                button.innerHTML = '❌ ' + slot.display;
            }
            
            timeSlotsDiv.appendChild(button);
        });
    }
});
</script>

<?php get_footer(); ?>