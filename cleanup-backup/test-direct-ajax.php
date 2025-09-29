<?php
/**
 * Test Direct AJAX Functionality
 */

if (!defined('ABSPATH')) {
    require_once('../../../wp-config.php');
}

get_header();

// Get a sample booking
global $wpdb;
$bookings_table = \NORDBOOKING\Classes\Database::get_table_name('bookings');
$sample_booking = $wpdb->get_row(
    "SELECT * FROM $bookings_table WHERE status IN ('pending', 'confirmed') ORDER BY booking_id DESC LIMIT 1"
);
?>

<div class="container" style="max-width: 800px; margin: 0 auto; padding: 2rem 1rem;">
    <h1>Direct AJAX Test</h1>
    
    <div style="background: white; border-radius: 12px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2>Test Direct AJAX Call</h2>
        
        <?php if ($sample_booking): ?>
        <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
            <h3>Sample Booking</h3>
            <p><strong>ID:</strong> <?php echo $sample_booking->booking_id; ?></p>
            <p><strong>Customer:</strong> <?php echo esc_html($sample_booking->customer_name); ?></p>
            <p><strong>Email:</strong> <?php echo esc_html($sample_booking->customer_email); ?></p>
        </div>
        
        <button id="test-direct-ajax" style="background: #007cba; color: white; border: none; padding: 1rem 2rem; border-radius: 6px; cursor: pointer; margin-right: 1rem;">
            Test Direct AJAX
        </button>
        
        <button id="test-wp-ajax" style="background: #28a745; color: white; border: none; padding: 1rem 2rem; border-radius: 6px; cursor: pointer;">
            Test WordPress AJAX
        </button>
        
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
    const directBtn = document.getElementById('test-direct-ajax');
    const wpBtn = document.getElementById('test-wp-ajax');
    const resultsDiv = document.getElementById('test-results');
    
    function showResult(title, message, type, rawResponse = null) {
        resultsDiv.style.display = 'block';
        resultsDiv.style.background = type === 'success' ? '#d4edda' : '#f8d7da';
        resultsDiv.style.color = type === 'success' ? '#155724' : '#721c24';
        
        let html = '<h3>' + title + '</h3><p>' + message + '</p>';
        if (rawResponse) {
            html += '<details><summary>Raw Response</summary><pre>' + JSON.stringify(rawResponse, null, 2) + '</pre></details>';
        }
        resultsDiv.innerHTML = html;
    }
    
    if (directBtn) {
        directBtn.addEventListener('click', function() {
            const formData = new FormData();
            formData.append('action', 'test_reschedule');
            formData.append('booking_id', '<?php echo $sample_booking ? $sample_booking->booking_id : 0; ?>');
            
            fetch('<?php echo home_url('/direct-ajax-test.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Direct AJAX Response Status:', response.status);
                return response.text();
            })
            .then(text => {
                console.log('Direct AJAX Raw Response:', text);
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        showResult('✅ Direct AJAX Test', 'Success: ' + data.message, 'success', data);
                    } else {
                        showResult('❌ Direct AJAX Test', 'Failed: ' + data.message, 'error', data);
                    }
                } catch (e) {
                    showResult('❌ Direct AJAX Test', 'JSON Parse Error: ' + e.message + '<br>Raw response: ' + text.substring(0, 500), 'error');
                }
            })
            .catch(error => {
                showResult('❌ Direct AJAX Test', 'Network Error: ' + error.message, 'error');
                console.error('Direct AJAX Error:', error);
            });
        });
    }
    
    if (wpBtn) {
        wpBtn.addEventListener('click', function() {
            const formData = new FormData();
            formData.append('action', 'nordbooking_reschedule_booking');
            formData.append('booking_token', 'test_token');
            formData.append('booking_id', '<?php echo $sample_booking ? $sample_booking->booking_id : 0; ?>');
            formData.append('new_date', '<?php echo date('Y-m-d', strtotime('+1 day')); ?>');
            formData.append('new_time', '10:00');
            formData.append('nonce', '<?php echo wp_create_nonce('nordbooking_customer_booking_management'); ?>');
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('WP AJAX Response Status:', response.status);
                return response.text();
            })
            .then(text => {
                console.log('WP AJAX Raw Response:', text);
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        showResult('✅ WordPress AJAX Test', 'Success: ' + data.data.message, 'success', data);
                    } else {
                        showResult('❌ WordPress AJAX Test', 'Failed: ' + (data.data ? data.data.message : data.message), 'error', data);
                    }
                } catch (e) {
                    showResult('❌ WordPress AJAX Test', 'JSON Parse Error: ' + e.message + '<br>Raw response: ' + text.substring(0, 500), 'error');
                }
            })
            .catch(error => {
                showResult('❌ WordPress AJAX Test', 'Network Error: ' + error.message, 'error');
                console.error('WP AJAX Error:', error);
            });
        });
    }
});
</script>

<?php get_footer(); ?>