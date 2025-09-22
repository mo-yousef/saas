<?php
/**
 * Template Name: Customer Booking Management
 * 
 * This page allows customers to update or cancel their bookings
 * through a unique secure link.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header();

// Get the booking token from URL
$booking_token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';

if (empty($booking_token)) {
    ?>
    <div class="container" style="padding: 2rem 0;">
        <div class="error-message" style="text-align: center; padding: 2rem; background: #f8d7da; color: #721c24; border-radius: 8px;">
            <h2><?php _e('Invalid Access', 'NORDBOOKING'); ?></h2>
            <p><?php _e('This page requires a valid booking link. Please check your email for the correct link.', 'NORDBOOKING'); ?></p>
        </div>
    </div>
    <?php
    get_footer();
    exit;
}

// Verify the token and get booking details
global $wpdb;
$bookings_table = \NORDBOOKING\Classes\Database::get_table_name('bookings');

// For security, we'll use a hash-based token system
// The token should be generated as: hash('sha256', $booking_id . $customer_email . wp_salt())
$booking = null;
$bookings = $wpdb->get_results(
    "SELECT * FROM $bookings_table WHERE status IN ('pending', 'confirmed') ORDER BY booking_id DESC"
);

foreach ($bookings as $potential_booking) {
    $expected_token = hash('sha256', $potential_booking->booking_id . $potential_booking->customer_email . wp_salt());
    if (hash_equals($expected_token, $booking_token)) {
        $booking = $potential_booking;
        break;
    }
}

if (!$booking) {
    ?>
    <div class="container" style="padding: 2rem 0;">
        <div class="error-message" style="text-align: center; padding: 2rem; background: #f8d7da; color: #721c24; border-radius: 8px;">
            <h2><?php _e('Booking Not Found', 'NORDBOOKING'); ?></h2>
            <p><?php _e('This booking link is invalid or the booking has already been processed.', 'NORDBOOKING'); ?></p>
        </div>
    </div>
    <?php
    get_footer();
    exit;
}

// Get business information
$business_owner = get_userdata($booking->user_id);
$business_name = $business_owner ? $business_owner->display_name : get_bloginfo('name');

// Get business logo/settings if available
$business_logo = '';
if (class_exists('NORDBOOKING\Classes\Settings')) {
    $settings = new \NORDBOOKING\Classes\Settings();
    $business_settings = $settings->get_business_settings($booking->user_id);
    $business_logo = $business_settings['biz_logo'] ?? '';
    if (!empty($business_settings['biz_name'])) {
        $business_name = $business_settings['biz_name'];
    }
}

// Get booking items
$booking_items_table = \NORDBOOKING\Classes\Database::get_table_name('booking_items');
$booking_items = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $booking_items_table WHERE booking_id = %d",
    $booking->booking_id
));
?>

<div class="customer-booking-management" style="min-height: 100vh; background: #f8f9fa;">
    <!-- Business Header -->
    <div class="business-header" style="background: white; padding: 1.5rem 0; border-bottom: 1px solid #e9ecef; margin-bottom: 2rem;">
        <div class="container" style="max-width: 800px; margin: 0 auto; padding: 0 1rem;">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <?php if (!empty($business_logo)): ?>
                    <img src="<?php echo esc_url($business_logo); ?>" alt="<?php echo esc_attr($business_name); ?>" style="height: 60px; width: auto;">
                <?php endif; ?>
                <div>
                    <h1 style="margin: 0; font-size: 1.5rem; color: #333;"><?php echo esc_html($business_name); ?></h1>
                    <p style="margin: 0; color: #666; font-size: 0.9rem;"><?php _e('Booking Management', 'NORDBOOKING'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="container" style="max-width: 800px; margin: 0 auto; padding: 0 1rem;">
        <!-- Booking Details Card -->
        <div class="booking-details-card" style="background: white; border-radius: 12px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h2 style="margin: 0 0 1.5rem 0; color: #333; font-size: 1.25rem;">
                <?php _e('Your Booking Details', 'NORDBOOKING'); ?>
            </h2>
            
            <div class="booking-info" style="display: grid; gap: 1rem; margin-bottom: 2rem;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <strong><?php _e('Booking Reference:', 'NORDBOOKING'); ?></strong><br>
                        <span style="color: #007cba; font-weight: 600;"><?php echo esc_html($booking->booking_reference); ?></span>
                    </div>
                    <div>
                        <strong><?php _e('Status:', 'NORDBOOKING'); ?></strong><br>
                        <span class="status-badge status-<?php echo esc_attr($booking->status); ?>" style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.875rem; font-weight: 500; background: #e3f2fd; color: #1976d2;">
                            <?php echo esc_html(ucfirst($booking->status)); ?>
                        </span>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <strong><?php _e('Date:', 'NORDBOOKING'); ?></strong><br>
                        <span id="current-date"><?php echo esc_html(date('F j, Y', strtotime($booking->booking_date))); ?></span>
                    </div>
                    <div>
                        <strong><?php _e('Time:', 'NORDBOOKING'); ?></strong><br>
                        <span id="current-time"><?php echo esc_html(date('g:i A', strtotime($booking->booking_time))); ?></span>
                    </div>
                </div>
                
                <div>
                    <strong><?php _e('Address:', 'NORDBOOKING'); ?></strong><br>
                    <?php echo esc_html($booking->service_address); ?>
                </div>
                
                <?php if (!empty($booking_items)): ?>
                <div>
                    <strong><?php _e('Services:', 'NORDBOOKING'); ?></strong><br>
                    <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
                        <?php foreach ($booking_items as $item): ?>
                            <li><?php echo esc_html($item->service_name); ?> - $<?php echo number_format($item->item_total_price, 2); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <div style="border-top: 1px solid #e9ecef; padding-top: 1rem; margin-top: 1rem;">
                    <strong style="font-size: 1.1rem;"><?php _e('Total:', 'NORDBOOKING'); ?> $<?php echo number_format($booking->total_price, 2); ?></strong>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="booking-actions" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 2rem;">
            <button id="reschedule-btn" class="action-btn reschedule-btn" style="background: #007cba; color: white; border: none; padding: 1rem 2rem; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.3s;">
                <?php _e('Reschedule Booking', 'NORDBOOKING'); ?>
            </button>
            
            <button id="cancel-btn" class="action-btn cancel-btn" style="background: #dc3545; color: white; border: none; padding: 1rem 2rem; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.3s;">
                <?php _e('Cancel Booking', 'NORDBOOKING'); ?>
            </button>
        </div>

        <!-- Reschedule Form (Hidden by default) -->
        <div id="reschedule-form" class="reschedule-form" style="display: none; background: white; border-radius: 12px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 1.5rem 0; color: #333;"><?php _e('Select New Date & Time', 'NORDBOOKING'); ?></h3>
            
            <form id="reschedule-booking-form">
                <div class="NORDBOOKING-datetime-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 1.5rem;">
                    <div class="NORDBOOKING-datetime-col">
                        <label class="NORDBOOKING-label" style="display: block; margin-bottom: 0.75rem; font-weight: 600; color: #333;"><?php _e('Select New Date', 'NORDBOOKING'); ?> *</label>
                        <div id="reschedule-service-date" style="border: 1px solid #ddd; border-radius: 8px; overflow: hidden;"></div>
                        <input type="hidden" id="selected-reschedule-date" name="new_date" required>
                    </div>
                    
                    <div class="NORDBOOKING-datetime-col">
                        <label class="NORDBOOKING-label" style="display: block; margin-bottom: 0.75rem; font-weight: 600; color: #333;"><?php _e('Available Time Slots', 'NORDBOOKING'); ?> *</label>
                        <div id="reschedule-time-slots-container" style="height: 300px; border: 1px solid #ddd; border-radius: 8px; padding: 1rem; overflow-y: auto; background: #f8f9fa;">
                            <div id="reschedule-time-slots" class="NORDBOOKING-time-slots" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 0.5rem;">
                                <p class="NORDBOOKING-time-placeholder" style="grid-column: 1 / -1; text-align: center; color: #666; margin: 2rem 0;"><?php _e('Select a date to see available times.', 'NORDBOOKING'); ?></p>
                            </div>
                        </div>
                        <input type="hidden" id="selected-reschedule-time" name="new_time" required>
                    </div>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label for="reschedule-reason" style="display: block; margin-bottom: 0.5rem; font-weight: 600;"><?php _e('Reason for rescheduling (optional):', 'NORDBOOKING'); ?></label>
                    <textarea id="reschedule-reason" name="reschedule_reason" rows="3" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; resize: vertical;" placeholder="<?php _e('Please let us know why you need to reschedule...', 'NORDBOOKING'); ?>"></textarea>
                </div>
                
                <div id="reschedule-feedback" class="NORDBOOKING-feedback" style="display: none; margin-bottom: 1rem; padding: 0.75rem; border-radius: 6px;"></div>
                
                <div style="display: flex; gap: 1rem;">
                    <button type="submit" id="confirm-reschedule-btn" style="background: #28a745; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; font-weight: 600; cursor: pointer; flex: 1;" disabled>
                        <?php _e('Confirm Reschedule', 'NORDBOOKING'); ?>
                    </button>
                    <button type="button" id="cancel-reschedule" style="background: #6c757d; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; font-weight: 600; cursor: pointer; flex: 1;">
                        <?php _e('Cancel', 'NORDBOOKING'); ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Cancel Confirmation (Hidden by default) -->
        <div id="cancel-confirmation" class="cancel-confirmation" style="display: none; background: white; border-radius: 12px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-left: 4px solid #dc3545;">
            <h3 style="margin: 0 0 1rem 0; color: #dc3545;"><?php _e('Cancel Booking', 'NORDBOOKING'); ?></h3>
            <p style="margin-bottom: 1.5rem; color: #666;"><?php _e('Are you sure you want to cancel this booking? This action cannot be undone.', 'NORDBOOKING'); ?></p>
            
            <form id="cancel-booking-form">
                <div style="margin-bottom: 1.5rem;">
                    <label for="cancel-reason" style="display: block; margin-bottom: 0.5rem; font-weight: 600;"><?php _e('Reason for cancellation (optional):', 'NORDBOOKING'); ?></label>
                    <textarea id="cancel-reason" name="cancel_reason" rows="3" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; resize: vertical;" placeholder="<?php _e('Please let us know why you need to cancel...', 'NORDBOOKING'); ?>"></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem;">
                    <button type="submit" style="background: #dc3545; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; font-weight: 600; cursor: pointer; flex: 1;">
                        <?php _e('Confirm Cancellation', 'NORDBOOKING'); ?>
                    </button>
                    <button type="button" id="cancel-cancel" style="background: #6c757d; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; font-weight: 600; cursor: pointer; flex: 1;">
                        <?php _e('Keep Booking', 'NORDBOOKING'); ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Success/Error Messages -->
        <div id="message-container" style="display: none; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;"></div>
    </div>
</div>

<style>
.action-btn:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

.reschedule-btn:hover {
    background: #0056b3 !important;
}

.cancel-btn:hover {
    background: #c82333 !important;
}

.status-pending {
    background: #fff3cd !important;
    color: #856404 !important;
}

.status-confirmed {
    background: #d4edda !important;
    color: #155724 !important;
}

/* Flatpickr Calendar Styles */
.flatpickr-calendar {
    border: none !important;
    box-shadow: none !important;
    width: 100% !important;
}

.flatpickr-months {
    background: #007cba !important;
    color: white !important;
}

.flatpickr-current-month .flatpickr-monthDropdown-months,
.flatpickr-current-month input.cur-year {
    color: white !important;
}

.flatpickr-weekday {
    background: #f8f9fa !important;
    color: #333 !important;
    font-weight: 600 !important;
}

.flatpickr-day {
    border-radius: 6px !important;
    margin: 2px !important;
}

.flatpickr-day:hover {
    background: #e3f2fd !important;
    color: #1976d2 !important;
}

.flatpickr-day.selected {
    background: #007cba !important;
    border-color: #007cba !important;
}

.flatpickr-day.today {
    border-color: #007cba !important;
    color: #007cba !important;
}

/* Time Slots Styles */
.NORDBOOKING-time-slots button {
    padding: 0.75rem 1rem;
    border: 2px solid #e9ecef;
    background: white;
    color: #333;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: center;
}

.NORDBOOKING-time-slots button:hover {
    border-color: #007cba;
    background: #e3f2fd;
    color: #1976d2;
    transform: translateY(-1px);
}

.NORDBOOKING-time-slots button.selected {
    background: #007cba;
    border-color: #007cba;
    color: white;
}

.NORDBOOKING-time-slots button:disabled {
    background: #f8f9fa;
    color: #6c757d;
    border-color: #e9ecef;
    cursor: not-allowed;
    opacity: 0.6;
}

.NORDBOOKING-feedback {
    padding: 0.75rem 1rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
}

.NORDBOOKING-feedback.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.NORDBOOKING-feedback.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

@media (max-width: 768px) {
    .booking-actions {
        grid-template-columns: 1fr !important;
    }
    
    .booking-info > div {
        grid-template-columns: 1fr !important;
    }
    
    .NORDBOOKING-datetime-grid {
        grid-template-columns: 1fr !important;
        gap: 1.5rem !important;
    }
    
    #reschedule-time-slots-container {
        height: 200px !important;
    }
}
</style>

<!-- Include Flatpickr for date picker -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const rescheduleBtn = document.getElementById('reschedule-btn');
    const cancelBtn = document.getElementById('cancel-btn');
    const rescheduleForm = document.getElementById('reschedule-form');
    const cancelConfirmation = document.getElementById('cancel-confirmation');
    const cancelReschedule = document.getElementById('cancel-reschedule');
    const cancelCancel = document.getElementById('cancel-cancel');
    const messageContainer = document.getElementById('message-container');
    
    const bookingToken = '<?php echo esc_js($booking_token); ?>';
    const bookingId = <?php echo intval($booking->booking_id); ?>;
    const tenantId = <?php echo intval($booking->user_id); ?>;
    
    let selectedDate = null;
    let selectedTime = null;
    let flatpickrInstance = null;
    
    // Initialize date picker
    function initRescheduleDatePicker() {
        const dateContainer = document.getElementById('reschedule-service-date');
        if (!dateContainer) return;
        
        // Get tomorrow's date as minimum
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        
        flatpickrInstance = flatpickr(dateContainer, {
            inline: true,
            minDate: tomorrow,
            maxDate: new Date().fp_incr(90), // 90 days from now
            dateFormat: 'Y-m-d',
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length > 0) {
                    selectedDate = dateStr;
                    document.getElementById('selected-reschedule-date').value = dateStr;
                    loadAvailableTimeSlots(dateStr);
                    validateRescheduleForm();
                }
            },
            onReady: function(selectedDates, dateStr, instance) {
                instance.calendarContainer.classList.add('NORDBOOKING-flatpickr');
            }
        });
    }
    
    // Load available time slots for selected date from the business owner's availability
    function loadAvailableTimeSlots(date) {
        const timeSlotsContainer = document.getElementById('reschedule-time-slots');
        timeSlotsContainer.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: #666; margin: 2rem 0;">Loading available times...</p>';
        
        // Fetch real availability data from the server
        const formData = new FormData();
        formData.append('action', 'nordbooking_get_available_time_slots');
        formData.append('tenant_id', tenantId);
        formData.append('date', date);
        formData.append('nonce', '<?php echo wp_create_nonce('nordbooking_customer_booking_management'); ?>');
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.time_slots) {
                displayTimeSlots(data.data.time_slots);
            } else {
                timeSlotsContainer.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: #666; margin: 2rem 0;">No available times for this date.</p>';
            }
        })
        .catch(error => {
            console.error('Error loading time slots:', error);
            timeSlotsContainer.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: #dc3545; margin: 2rem 0;">Error loading available times. Please try again.</p>';
        });
    }
    
    // Display time slots from server response
    function displayTimeSlots(timeSlots) {
        const timeSlotsContainer = document.getElementById('reschedule-time-slots');
        timeSlotsContainer.innerHTML = '';
        
        if (timeSlots.length === 0) {
            timeSlotsContainer.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: #666; margin: 2rem 0;">No available times for this date.</p>';
            return;
        }
        
        timeSlots.forEach(slot => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'time-slot-btn';
            button.textContent = slot.display;
            button.dataset.time = slot.time;
            button.disabled = !slot.available;
            
            if (slot.available) {
                button.addEventListener('click', function() {
                    // Remove selected class from all buttons
                    timeSlotsContainer.querySelectorAll('.time-slot-btn').forEach(btn => {
                        btn.classList.remove('selected');
                    });
                    
                    // Add selected class to clicked button
                    this.classList.add('selected');
                    selectedTime = this.dataset.time;
                    document.getElementById('selected-reschedule-time').value = selectedTime;
                    validateRescheduleForm();
                });
            }
            
            timeSlotsContainer.appendChild(button);
        });
    }
    
    // Format time to 12-hour format
    function formatTime12Hour(time24) {
        const [hours, minutes] = time24.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const hour12 = hour % 12 || 12;
        return `${hour12}:${minutes} ${ampm}`;
    }
    
    // Validate reschedule form
    function validateRescheduleForm() {
        const confirmBtn = document.getElementById('confirm-reschedule-btn');
        const isValid = selectedDate && selectedTime;
        
        confirmBtn.disabled = !isValid;
        confirmBtn.style.opacity = isValid ? '1' : '0.6';
        confirmBtn.style.cursor = isValid ? 'pointer' : 'not-allowed';
    }
    
    // Show reschedule form
    rescheduleBtn.addEventListener('click', function() {
        rescheduleForm.style.display = 'block';
        cancelConfirmation.style.display = 'none';
        
        // Initialize date picker if not already done
        if (!flatpickrInstance) {
            initRescheduleDatePicker();
        }
        
        rescheduleForm.scrollIntoView({ behavior: 'smooth' });
    });
    
    // Show cancel confirmation
    cancelBtn.addEventListener('click', function() {
        cancelConfirmation.style.display = 'block';
        rescheduleForm.style.display = 'none';
        cancelConfirmation.scrollIntoView({ behavior: 'smooth' });
    });
    
    // Show reschedule feedback
    function showRescheduleFeedback(message, type) {
        const feedbackDiv = document.getElementById('reschedule-feedback');
        feedbackDiv.style.display = 'block';
        feedbackDiv.className = 'NORDBOOKING-feedback ' + type;
        feedbackDiv.textContent = message;
        
        // Auto-hide success messages after 3 seconds
        if (type === 'success') {
            setTimeout(() => {
                feedbackDiv.style.display = 'none';
            }, 3000);
        }
    }
    
    // Hide reschedule form
    cancelReschedule.addEventListener('click', function() {
        rescheduleForm.style.display = 'none';
        
        // Reset form state
        selectedDate = null;
        selectedTime = null;
        document.getElementById('selected-reschedule-date').value = '';
        document.getElementById('selected-reschedule-time').value = '';
        document.getElementById('reschedule-reason').value = '';
        document.getElementById('reschedule-feedback').style.display = 'none';
        
        // Reset time slots
        const timeSlotsContainer = document.getElementById('reschedule-time-slots');
        timeSlotsContainer.innerHTML = '<p class="NORDBOOKING-time-placeholder" style="grid-column: 1 / -1; text-align: center; color: #666; margin: 2rem 0;"><?php _e('Select a date to see available times.', 'NORDBOOKING'); ?></p>';
        
        // Reset date picker
        if (flatpickrInstance) {
            flatpickrInstance.clear();
        }
        
        validateRescheduleForm();
    });
    
    // Hide cancel confirmation
    cancelCancel.addEventListener('click', function() {
        cancelConfirmation.style.display = 'none';
    });
    
    // Handle reschedule form submission
    document.getElementById('reschedule-booking-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate form before submission
        if (!selectedDate || !selectedTime) {
            showRescheduleFeedback('Please select both a date and time slot.', 'error');
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'nordbooking_reschedule_booking');
        formData.append('booking_token', bookingToken);
        formData.append('booking_id', bookingId);
        formData.append('new_date', selectedDate);
        formData.append('new_time', selectedTime);
        formData.append('reschedule_reason', document.getElementById('reschedule-reason').value);
        formData.append('nonce', '<?php echo wp_create_nonce('nordbooking_customer_booking_management'); ?>');
        
        // Disable submit button
        const submitBtn = e.target.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = '<?php _e('Processing...', 'NORDBOOKING'); ?>';
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showRescheduleFeedback(data.data.message, 'success');
                
                // Update the displayed date and time
                if (data.data.new_date && data.data.new_time) {
                    document.getElementById('current-date').textContent = data.data.new_date_formatted;
                    document.getElementById('current-time').textContent = data.data.new_time_formatted;
                }
                
                // Hide form after successful update
                setTimeout(() => {
                    rescheduleForm.style.display = 'none';
                    showMessage('Your booking has been successfully rescheduled!', 'success');
                }, 2000);
            } else {
                showRescheduleFeedback(data.data.message || '<?php _e('An error occurred. Please try again.', 'NORDBOOKING'); ?>', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('<?php _e('An error occurred. Please try again.', 'NORDBOOKING'); ?>', 'error');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    });
    
    // Handle cancel form submission
    document.getElementById('cancel-booking-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('action', 'nordbooking_cancel_booking');
        formData.append('booking_token', bookingToken);
        formData.append('booking_id', bookingId);
        formData.append('cancel_reason', document.getElementById('cancel-reason').value);
        formData.append('nonce', '<?php echo wp_create_nonce('nordbooking_customer_booking_management'); ?>');
        
        // Disable submit button
        const submitBtn = e.target.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = '<?php _e('Processing...', 'NORDBOOKING'); ?>';
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(data.data.message, 'success');
                cancelConfirmation.style.display = 'none';
                
                // Update status and disable action buttons
                const statusBadge = document.querySelector('.status-badge');
                statusBadge.textContent = '<?php _e('Cancelled', 'NORDBOOKING'); ?>';
                statusBadge.className = 'status-badge status-cancelled';
                statusBadge.style.background = '#f8d7da';
                statusBadge.style.color = '#721c24';
                
                rescheduleBtn.disabled = true;
                cancelBtn.disabled = true;
                rescheduleBtn.style.opacity = '0.5';
                cancelBtn.style.opacity = '0.5';
            } else {
                showMessage(data.data.message || '<?php _e('An error occurred. Please try again.', 'NORDBOOKING'); ?>', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('<?php _e('An error occurred. Please try again.', 'NORDBOOKING'); ?>', 'error');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    });
    
    function showMessage(message, type) {
        messageContainer.style.display = 'block';
        messageContainer.textContent = message;
        
        if (type === 'success') {
            messageContainer.style.background = '#d4edda';
            messageContainer.style.color = '#155724';
            messageContainer.style.border = '1px solid #c3e6cb';
        } else {
            messageContainer.style.background = '#f8d7da';
            messageContainer.style.color = '#721c24';
            messageContainer.style.border = '1px solid #f5c6cb';
        }
        
        messageContainer.scrollIntoView({ behavior: 'smooth' });
        
        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(() => {
                messageContainer.style.display = 'none';
            }, 5000);
        }
    }
});
</script>

<?php get_footer(); ?>