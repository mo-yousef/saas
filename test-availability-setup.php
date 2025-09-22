<?php
/**
 * Test Availability Setup and Retrieval
 */

if (!defined('ABSPATH')) {
    require_once('../../../wp-config.php');
}

get_header();
?>

<div class="container" style="max-width: 1000px; margin: 0 auto; padding: 2rem 1rem;">
    <h1>Availability Setup Test</h1>
    
    <div style="background: white; border-radius: 12px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2>System Status</h2>
        
        <?php
        // Check if availability tables exist
        global $wpdb;
        $availability_table = \NORDBOOKING\Classes\Database::get_table_name('availability_rules');
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$availability_table'") == $availability_table;
        
        echo "<h3>Database Tables</h3>";
        if ($table_exists) {
            echo "<p style='color: green;'>✅ Availability rules table exists: $availability_table</p>";
            
            // Check if there are any availability rules
            $rules_count = $wpdb->get_var("SELECT COUNT(*) FROM $availability_table");
            echo "<p>📊 Total availability rules: $rules_count</p>";
            
            if ($rules_count > 0) {
                // Show sample rules
                $sample_rules = $wpdb->get_results("SELECT user_id, day_of_week, start_time, end_time FROM $availability_table LIMIT 10", ARRAY_A);
                echo "<h4>Sample Availability Rules:</h4>";
                echo "<table style='width: 100%; border-collapse: collapse; margin: 1rem 0;'>";
                echo "<thead><tr style='background: #f8f9fa;'><th style='padding: 0.5rem; border: 1px solid #ddd;'>User ID</th><th style='padding: 0.5rem; border: 1px solid #ddd;'>Day</th><th style='padding: 0.5rem; border: 1px solid #ddd;'>Start Time</th><th style='padding: 0.5rem; border: 1px solid #ddd;'>End Time</th></tr></thead>";
                echo "<tbody>";
                
                $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                
                foreach ($sample_rules as $rule) {
                    $day_name = $days[$rule['day_of_week']] ?? 'Unknown';
                    echo "<tr>";
                    echo "<td style='padding: 0.5rem; border: 1px solid #ddd;'>{$rule['user_id']}</td>";
                    echo "<td style='padding: 0.5rem; border: 1px solid #ddd;'>{$day_name}</td>";
                    echo "<td style='padding: 0.5rem; border: 1px solid #ddd;'>{$rule['start_time']}</td>";
                    echo "<td style='padding: 0.5rem; border: 1px solid #ddd;'>{$rule['end_time']}</td>";
                    echo "</tr>";
                }
                echo "</tbody></table>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ Availability rules table does not exist: $availability_table</p>";
            echo "<p>The table will be created when a business owner first sets their availability.</p>";
        }
        
        // Get business owners
        echo "<h3>Business Owners</h3>";
        $business_owners = get_users(['role' => 'nordbooking_business_owner', 'number' => 10]);
        
        if (!empty($business_owners)) {
            echo "<p style='color: green;'>✅ Found " . count($business_owners) . " business owners</p>";
            
            foreach ($business_owners as $owner) {
                echo "<div style='background: #f8f9fa; padding: 1rem; margin: 0.5rem 0; border-radius: 6px;'>";
                echo "<strong>{$owner->display_name}</strong> (ID: {$owner->ID})<br>";
                
                if ($table_exists) {
                    // Check availability rules for this owner
                    $owner_rules = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT day_of_week, start_time, end_time FROM $availability_table WHERE user_id = %d AND is_active = 1",
                            $owner->ID
                        ),
                        ARRAY_A
                    );
                    
                    if (!empty($owner_rules)) {
                        echo "<span style='color: green;'>✅ Has " . count($owner_rules) . " availability rules</span><br>";
                        echo "<small>Days with availability: ";
                        $days_with_availability = [];
                        foreach ($owner_rules as $rule) {
                            $day_name = $days[$rule['day_of_week']] ?? 'Unknown';
                            $days_with_availability[] = "$day_name ({$rule['start_time']}-{$rule['end_time']})";
                        }
                        echo implode(', ', $days_with_availability);
                        echo "</small>";
                    } else {
                        echo "<span style='color: orange;'>⚠️ No availability rules set</span>";
                    }
                } else {
                    echo "<span style='color: gray;'>❓ Cannot check availability (table missing)</span>";
                }
                
                echo "</div>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ No business owners found</p>";
        }
        ?>
    </div>
    
    <?php if (!empty($business_owners)): ?>
    <div style="background: white; border-radius: 12px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2>Create Sample Availability</h2>
        <p>If no availability rules exist, you can create sample ones for testing:</p>
        
        <form id="create-sample-availability" style="margin-bottom: 1rem;">
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label for="sample-user-id" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Business Owner:</label>
                    <select id="sample-user-id" required style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px;">
                        <option value="">Select owner...</option>
                        <?php foreach ($business_owners as $owner): ?>
                            <option value="<?php echo $owner->ID; ?>"><?php echo esc_html($owner->display_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="sample-start-time" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Start Time:</label>
                    <input type="time" id="sample-start-time" value="09:00" required style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px;">
                </div>
                
                <div>
                    <label for="sample-end-time" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">End Time:</label>
                    <input type="time" id="sample-end-time" value="17:00" required style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px;">
                </div>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Days of Week:</label>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <label><input type="checkbox" name="days[]" value="1" checked> Monday</label>
                    <label><input type="checkbox" name="days[]" value="2" checked> Tuesday</label>
                    <label><input type="checkbox" name="days[]" value="3" checked> Wednesday</label>
                    <label><input type="checkbox" name="days[]" value="4" checked> Thursday</label>
                    <label><input type="checkbox" name="days[]" value="5" checked> Friday</label>
                    <label><input type="checkbox" name="days[]" value="6"> Saturday</label>
                    <label><input type="checkbox" name="days[]" value="0"> Sunday</label>
                </div>
            </div>
            
            <button type="submit" style="background: #007cba; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; font-weight: 600; cursor: pointer;">
                Create Sample Availability
            </button>
        </form>
        
        <div id="create-result" style="display: none; margin-top: 1rem; padding: 1rem; border-radius: 6px;"></div>
    </div>
    
    <div style="background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2>Test Real Availability</h2>
        <p>Test the availability system with real data:</p>
        
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
                <input type="date" id="test-date" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px;">
            </div>
        </div>
        
        <button id="test-availability-btn" style="background: #28a745; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; font-weight: 600; cursor: pointer;">
            Test Availability
        </button>
        
        <div id="availability-results" style="margin-top: 2rem; display: none;">
            <h3>Available Time Slots</h3>
            <div id="availability-slots" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 0.5rem; margin-top: 1rem;"></div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set default date to tomorrow
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    document.getElementById('test-date').value = tomorrow.toISOString().split('T')[0];
    
    // Handle sample availability creation
    document.getElementById('create-sample-availability').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const userId = document.getElementById('sample-user-id').value;
        const startTime = document.getElementById('sample-start-time').value;
        const endTime = document.getElementById('sample-end-time').value;
        const selectedDays = Array.from(document.querySelectorAll('input[name="days[]"]:checked')).map(cb => cb.value);
        
        if (!userId || !startTime || !endTime || selectedDays.length === 0) {
            alert('Please fill in all fields and select at least one day.');
            return;
        }
        
        const resultDiv = document.getElementById('create-result');
        resultDiv.style.display = 'block';
        resultDiv.style.background = '#fff3cd';
        resultDiv.style.color = '#856404';
        resultDiv.textContent = 'Creating sample availability...';
        
        // This would normally make an AJAX call to create the availability rules
        // For now, just show a message
        setTimeout(() => {
            resultDiv.style.background = '#d4edda';
            resultDiv.style.color = '#155724';
            resultDiv.innerHTML = '✅ Sample availability would be created for user ' + userId + ' from ' + startTime + ' to ' + endTime + ' on days: ' + selectedDays.join(', ') + '<br><small>Note: This is a demo. In a real implementation, this would create database records.</small>';
        }, 1000);
    });
    
    // Handle availability testing
    document.getElementById('test-availability-btn').addEventListener('click', function() {
        const ownerId = document.getElementById('test-owner').value;
        const date = document.getElementById('test-date').value;
        
        if (!ownerId || !date) {
            alert('Please select both a business owner and date.');
            return;
        }
        
        const btn = this;
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Testing...';
        
        const resultsDiv = document.getElementById('availability-results');
        const slotsDiv = document.getElementById('availability-slots');
        
        resultsDiv.style.display = 'block';
        slotsDiv.innerHTML = '<p>Loading...</p>';
        
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
                displayAvailabilitySlots(data.data.time_slots, data.data.source);
            } else {
                slotsDiv.innerHTML = '<p style="color: #666;">No available times found for this date.</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            slotsDiv.innerHTML = '<p style="color: #dc3545;">Error loading time slots: ' + error.message + '</p>';
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = originalText;
        });
    });
    
    function displayAvailabilitySlots(timeSlots, source) {
        const slotsDiv = document.getElementById('availability-slots');
        
        if (timeSlots.length === 0) {
            slotsDiv.innerHTML = '<p style="color: #666;">No available time slots for this date.</p>';
            return;
        }
        
        let sourceInfo = '';
        if (source === 'database') {
            sourceInfo = '<p style="color: #28a745; margin-bottom: 1rem;">✅ Using real availability data from business owner settings</p>';
        } else {
            sourceInfo = '<p style="color: #ffc107; margin-bottom: 1rem;">⚠️ Using fallback data (business owner has not set availability)</p>';
        }
        
        slotsDiv.innerHTML = sourceInfo;
        
        const slotsGrid = document.createElement('div');
        slotsGrid.style.cssText = 'display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 0.5rem;';
        
        timeSlots.forEach(slot => {
            const button = document.createElement('div');
            button.style.cssText = 'padding: 0.75rem; border: 2px solid #28a745; background: #d4edda; color: #155724; border-radius: 8px; text-align: center; font-weight: 500;';
            button.textContent = slot.display;
            slotsGrid.appendChild(button);
        });
        
        slotsDiv.appendChild(slotsGrid);
    }
});
</script>

<?php get_footer(); ?>