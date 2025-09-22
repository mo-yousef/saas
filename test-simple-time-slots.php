<?php
/**
 * Simple Test for Time Slots AJAX
 */

if (!defined('ABSPATH')) {
    require_once('../../../wp-config.php');
}

get_header();
?>

<div class="container" style="max-width: 800px; margin: 0 auto; padding: 2rem 1rem;">
    <h1>Simple Time Slots Test</h1>
    
    <div style="background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2>Test AJAX Time Slots</h2>
        
        <div style="margin-bottom: 1rem;">
            <label for="test-tenant-id" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Tenant ID:</label>
            <input type="number" id="test-tenant-id" value="1" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px;">
        </div>
        
        <div style="margin-bottom: 1rem;">
            <label for="test-date" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Date:</label>
            <input type="date" id="test-date" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px;">
        </div>
        
        <button id="test-btn" style="background: #007cba; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; font-weight: 600; cursor: pointer;">
            Test Time Slots
        </button>
        
        <div id="results" style="margin-top: 2rem; display: none;">
            <h3>Results</h3>
            <div id="results-content" style="background: #f8f9fa; padding: 1rem; border-radius: 6px; white-space: pre-wrap; font-family: monospace;"></div>
        </div>
        
        <div id="time-slots-display" style="margin-top: 2rem; display: none;">
            <h3>Time Slots</h3>
            <div id="time-slots-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 0.5rem;"></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const testBtn = document.getElementById('test-btn');
    const tenantIdInput = document.getElementById('test-tenant-id');
    const dateInput = document.getElementById('test-date');
    const resultsDiv = document.getElementById('results');
    const resultsContent = document.getElementById('results-content');
    const timeSlotsDisplay = document.getElementById('time-slots-display');
    const timeSlotsGrid = document.getElementById('time-slots-grid');
    
    // Set default date to tomorrow
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    dateInput.value = tomorrow.toISOString().split('T')[0];
    
    testBtn.addEventListener('click', function() {
        const tenantId = tenantIdInput.value;
        const date = dateInput.value;
        
        if (!tenantId || !date) {
            alert('Please fill in both tenant ID and date.');
            return;
        }
        
        testBtn.disabled = true;
        testBtn.textContent = 'Testing...';
        resultsDiv.style.display = 'block';
        timeSlotsDisplay.style.display = 'none';
        resultsContent.textContent = 'Loading...';
        
        const formData = new FormData();
        formData.append('action', 'nordbooking_get_available_time_slots');
        formData.append('tenant_id', tenantId);
        formData.append('date', date);
        
        console.log('Sending request with:', {
            action: 'nordbooking_get_available_time_slots',
            tenant_id: tenantId,
            date: date
        });
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            return response.text();
        })
        .then(text => {
            console.log('Raw response:', text);
            
            try {
                const data = JSON.parse(text);
                console.log('Parsed JSON:', data);
                
                resultsContent.textContent = JSON.stringify(data, null, 2);
                
                if (data.success && data.data.time_slots) {
                    displayTimeSlots(data.data.time_slots);
                }
            } catch (e) {
                console.error('JSON parse error:', e);
                resultsContent.textContent = 'JSON Parse Error: ' + e.message + '\n\nRaw response:\n' + text;
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            resultsContent.textContent = 'Fetch Error: ' + error.message;
        })
        .finally(() => {
            testBtn.disabled = false;
            testBtn.textContent = 'Test Time Slots';
        });
    });
    
    function displayTimeSlots(timeSlots) {
        timeSlotsDisplay.style.display = 'block';
        timeSlotsGrid.innerHTML = '';
        
        timeSlots.forEach(slot => {
            const button = document.createElement('div');
            button.style.cssText = 'padding: 0.75rem; border: 2px solid #28a745; background: #d4edda; color: #155724; border-radius: 8px; text-align: center; font-weight: 500;';
            button.textContent = slot.display;
            timeSlotsGrid.appendChild(button);
        });
    }
});
</script>

<?php get_footer(); ?>