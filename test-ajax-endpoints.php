<?php
/**
 * Test AJAX Endpoints for Customer Booking Management
 */

if (!defined('ABSPATH')) {
    require_once('../../../wp-config.php');
}

get_header();
?>

<div class="container" style="max-width: 800px; margin: 0 auto; padding: 2rem 1rem;">
    <h1>Test AJAX Endpoints</h1>
    
    <div style="background: white; border-radius: 12px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2>AJAX Handler Test</h2>
        
        <?php
        // Get a sample booking for testing
        global $wpdb;
        $bookings_table = \NORDBOOKING\Classes\Database::get_table_name('bookings');
        $sample_booking = $wpdb->get_row(
            "SELECT * FROM $bookings_table WHERE status IN ('pending', 'confirmed') ORDER BY booking_id DESC LIMIT 1"
        );
        
        if ($sample_booking):
            $test_token = hash('sha256', $sample_booking->booking_id . $sample_booking->customer_email . wp_salt());
        ?>
        
        <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
            <h3>Sample Booking Data</h3>
            <p><strong>Booking ID:</strong> <?php echo $sample_booking->booking_id; ?></p>
            <p><strong>Customer:</strong> <?php echo esc_html($sample_booking->customer_name); ?></p>
            <p><strong>Email:</strong> <?php echo esc_html($sample_booking->customer_email); ?></p>
            <p><strong>Current Date:</strong> <?php echo $sample_booking->booking_date; ?></p>
            <p><strong>Current Time:</strong> <?php echo $sample_booking->booking_time; ?></p>
            <p><strong>Token:</strong> <code><?php echo $test_token; ?></code></p>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div>
                <h3>Test Reschedule</h3>
                <form id="test-reschedule-form">
                    <input type="hidden" name="booking_token" value="<?php echo $test_token; ?>">
                    <input type="hidden" name="booking_id" value="<?php echo $sample_booking->booking_id; ?>">
                    
                    <div style="margin-bottom: 1rem;">
                        <label>New Date:</label>
                        <input type="date" name="new_date" value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required style="width: 100%; padding: 0.5rem;">
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <label>New Time:</label>
                        <input type="time" name="new_time" value="10:00" required style="width: 100%; padding: 0.5rem;">
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <label>Reason:</label>
                        <textarea name="reschedule_reason" style="width: 100%; padding: 0.5rem;">Test reschedule</textarea>
                    </div>
                    
                    <button type="submit" style="background: #007cba; color: white; border: none; padding: 0.75rem 1rem; border-radius: 6px; cursor: pointer;">
                        Test Reschedule
                    </button>
                </form>
            </div>
            
            <div>
                <h3>Test Cancel</h3>
                <form id="test-cancel-form">
                    <input type="hidden" name="booking_token" value="<?php echo $test_token; ?>">
                    <input type="hidden" name="booking_id" value="<?php echo $sample_booking->booking_id; ?>">
                    
                    <div style="margin-bottom: 1rem;">
                        <label>Cancel Reason:</label>
                        <textarea name="cancel_reason" style="width: 100%; padding: 0.5rem;">Test cancellation</textarea>
                    </div>
                    
                    <button type="submit" style="background: #dc3545; color: white; border: none; padding: 0.75rem 1rem; border-radius: 6px; cursor: pointer;">
                        Test Cancel
                    </button>
                </form>
            </div>
        </div>
        
        <?php else: ?>
        <div style="background: #fff3cd; padding: 1rem; border-radius: 8px; color: #856404;">
            <p>No sample bookings found. Create a test booking first.</p>
        </div>
        <?php endif; ?>
        
        <div id="test-results" style="margin-top: 2rem; padding: 1rem; border-radius: 8px; display: none;"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const rescheduleForm = document.getElementById('test-reschedule-form');
    const cancelForm = document.getElementById('test-cancel-form');
    const resultsDiv = document.getElementById('test-results');
    
    function showResult(message, type) {
        resultsDiv.style.display = 'block';
        resultsDiv.style.background = type === 'success' ? '#d4edda' : '#f8d7da';
        resultsDiv.style.color = type === 'success' ? '#155724' : '#721c24';
        resultsDiv.innerHTML = '<h3>Test Result</h3><p>' + message + '</p>';
    }
    
    if (rescheduleForm) {
        rescheduleForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'nordbooking_reschedule_booking');
            formData.append('nonce', '<?php echo wp_create_nonce('nordbooking_customer_booking_management'); ?>');
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showResult('✅ Reschedule test successful: ' + data.data.message, 'success');
                } else {
                    showResult('❌ Reschedule test failed: ' + (data.data.message || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                showResult('❌ Network error: ' + error.message, 'error');
                console.error('Error:', error);
            });
        });
    }
    
    if (cancelForm) {
        cancelForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'nordbooking_cancel_booking');
            formData.append('nonce', '<?php echo wp_create_nonce('nordbooking_customer_booking_management'); ?>');
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showResult('✅ Cancel test successful: ' + data.data.message, 'success');
                } else {
                    showResult('❌ Cancel test failed: ' + (data.data.message || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                showResult('❌ Network error: ' + error.message, 'error');
                console.error('Error:', error);
            });
        });
    }
});
</script>

<?php get_footer(); ?>