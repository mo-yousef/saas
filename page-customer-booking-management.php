<?php
/**
 * Template Name: Customer Booking Management - Enhanced
 * 
 * Enhanced booking management page with infographic-style timeline
 * and improved user experience for customers to manage their bookings.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header('booking');

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

<div class="customer-booking-management" style="min-height: 100vh; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
    <!-- Enhanced Business Header -->
    <div class="business-header" style="background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%); padding: 2rem 0; border-bottom: 3px solid #007cba; margin-bottom: 2rem; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
        <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 1rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1.5rem;">
                <div style="display: flex; align-items: center; gap: 1.5rem;">
                    <?php if (!empty($business_logo)): ?>
                        <div style="background: white; padding: 0.75rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                            <img src="<?php echo esc_url($business_logo); ?>" alt="<?php echo esc_attr($business_name); ?>" style="height: 60px; width: auto;">
                        </div>
                    <?php endif; ?>
                    <div>
                        <h1 style="margin: 0 0 0.25rem 0; font-size: 2rem; color: #333; font-weight: 700;"><?php echo esc_html($business_name); ?></h1>
                        <p style="margin: 0; color: #666; font-size: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #007cba;">
                                <path d="M12 20h9"></path>
                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                            </svg>
                            <?php _e('Booking Management Portal', 'NORDBOOKING'); ?>
                        </p>
                    </div>
                </div>
                <div class="booking-status-indicator" style="background: white; padding: 1rem 1.5rem; border-radius: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <span class="status-badge status-<?php echo esc_attr($booking->status); ?>" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.875rem; font-weight: 600; 
                        <?php if($booking->status === 'confirmed'): ?>
                            background: #d4edda; color: #155724; border: 2px solid #c3e6cb;
                        <?php elseif($booking->status === 'pending'): ?>
                            background: #fff3cd; color: #856404; border: 2px solid #ffeaa7;
                        <?php else: ?>
                            background: #f8d7da; color: #721c24; border: 2px solid #f5c6cb;
                        <?php endif; ?>">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                            <circle cx="12" cy="12" r="10"></circle>
                        </svg>
                        <?php echo esc_html(ucfirst($booking->status)); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 1rem;">
        <div style="display: grid; grid-template-columns: 1fr 350px; gap: 2rem; align-items: start;">
            
            <!-- Main Content Area -->
            <div class="main-content">
                <!-- Booking Timeline Infographic -->
                <div class="booking-timeline-card" style="background: white; border-radius: 16px; padding: 0; margin-bottom: 2rem; box-shadow: 0 8px 32px rgba(0,0,0,0.12); overflow: hidden; border: 1px solid rgba(0,124,186,0.1);">
                    <!-- Timeline Header -->
                    <div class="timeline-header" style="background: linear-gradient(135deg, #007cba 0%, #005a8b 100%); color: white; padding: 2rem; position: relative; overflow: hidden;">
                        <div style="position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: rgba(255,255,255,0.1); border-radius: 50%; opacity: 0.3;"></div>
                        <div style="position: absolute; bottom: -30px; left: -30px; width: 100px; height: 100px; background: rgba(255,255,255,0.05); border-radius: 50%;"></div>
                        
                        <div style="position: relative; z-index: 2;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;">
                                <div>
                                    <h2 style="margin: 0 0 0.5rem 0; color: white; font-size: 1.75rem; font-weight: 700; display: flex; align-items: center; gap: 0.75rem;">
                                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M9 11H5a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2h-4"></path>
                                            <rect x="9" y="7" width="6" height="4"></rect>
                                        </svg>
                                        <?php _e('Your Booking Journey', 'NORDBOOKING'); ?>
                                    </h2>
                                    <p style="margin: 0; color: rgba(255,255,255,0.9); font-size: 1rem;">
                                        <?php _e('Reference:', 'NORDBOOKING'); ?> <strong style="background: rgba(255,255,255,0.2); padding: 0.25rem 0.75rem; border-radius: 15px; font-size: 0.9rem;"><?php echo esc_html($booking->booking_reference); ?></strong>
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Timeline Steps -->
                            <div class="timeline-steps" style="display: flex; justify-content: space-between; align-items: center; margin-top: 2rem; position: relative;">
                                <!-- Timeline Line -->
                                <div style="position: absolute; top: 50%; left: 0; right: 0; height: 3px; background: rgba(255,255,255,0.3); border-radius: 2px; z-index: 1;"></div>
                                <div style="position: absolute; top: 50%; left: 0; width: 66%; height: 3px; background: rgba(255,255,255,0.8); border-radius: 2px; z-index: 2;"></div>
                                
                                <!-- Step 1: Booked -->
                                <div class="timeline-step completed" style="position: relative; z-index: 3; text-align: center;">
                                    <div style="width: 50px; height: 50px; background: rgba(255,255,255,0.9); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#007cba" stroke-width="3">
                                            <polyline points="20,6 9,17 4,12"></polyline>
                                        </svg>
                                    </div>
                                    <div style="color: white; font-size: 0.875rem; font-weight: 600;"><?php _e('Booked', 'NORDBOOKING'); ?></div>
                                </div>
                                
                                <!-- Step 2: Confirmed -->
                                <div class="timeline-step <?php echo $booking->status === 'confirmed' ? 'completed' : 'pending'; ?>" style="position: relative; z-index: 3; text-align: center;">
                                    <div style="width: 50px; height: 50px; background: <?php echo $booking->status === 'confirmed' ? 'rgba(255,255,255,0.9)' : 'rgba(255,255,255,0.3)'; ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
                                        <?php if($booking->status === 'confirmed'): ?>
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#007cba" stroke-width="3">
                                                <polyline points="20,6 9,17 4,12"></polyline>
                                            </svg>
                                        <?php else: ?>
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <path d="M12 6v6l4 2"></path>
                                            </svg>
                                        <?php endif; ?>
                                    </div>
                                    <div style="color: white; font-size: 0.875rem; font-weight: 600;"><?php _e('Confirmed', 'NORDBOOKING'); ?></div>
                                </div>
                                
                                <!-- Step 3: Service Day -->
                                <div class="timeline-step pending" style="position: relative; z-index: 3; text-align: center;">
                                    <div style="width: 50px; height: 50px; background: rgba(255,255,255,0.3); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                            <path d="M8 2v4"></path>
                                            <path d="M16 2v4"></path>
                                            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                            <path d="M3 10h18"></path>
                                        </svg>
                                    </div>
                                    <div style="color: white; font-size: 0.875rem; font-weight: 600;"><?php _e('Service Day', 'NORDBOOKING'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Key Information Cards -->
                    <div class="key-info-section" style="padding: 2rem;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                            <!-- Date & Time Card -->
                            <div class="info-card" style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border: 2px solid #2196f3; border-radius: 16px; padding: 1.5rem; position: relative; overflow: hidden;">
                                <div style="position: absolute; top: -20px; right: -20px; width: 80px; height: 80px; background: rgba(33,150,243,0.1); border-radius: 50%;"></div>
                                <div style="position: relative; z-index: 2;">
                                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                        <div style="background: #2196f3; color: white; padding: 0.75rem; border-radius: 12px;">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                                <line x1="3" y1="10" x2="21" y2="10"></line>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 style="margin: 0; color: #1976d2; font-size: 1.1rem; font-weight: 700;"><?php _e('Scheduled For', 'NORDBOOKING'); ?></h3>
                                        </div>
                                    </div>
                                    <div style="color: #0d47a1; font-size: 1.25rem; font-weight: 700; line-height: 1.3;">
                                        <div id="current-date"><?php echo esc_html(date('l, F j, Y', strtotime($booking->booking_date))); ?></div>
                                        <div id="current-time" style="font-size: 1.1rem; margin-top: 0.25rem; color: #1976d2;"><?php echo esc_html(date('g:i A', strtotime($booking->booking_time))); ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Location Card -->
                            <div class="info-card" style="background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%); border: 2px solid #4caf50; border-radius: 16px; padding: 1.5rem; position: relative; overflow: hidden;">
                                <div style="position: absolute; top: -20px; right: -20px; width: 80px; height: 80px; background: rgba(76,175,80,0.1); border-radius: 50%;"></div>
                                <div style="position: relative; z-index: 2;">
                                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                        <div style="background: #4caf50; color: white; padding: 0.75rem; border-radius: 12px;">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                                <circle cx="12" cy="10" r="3"></circle>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 style="margin: 0; color: #388e3c; font-size: 1.1rem; font-weight: 700;"><?php _e('Service Location', 'NORDBOOKING'); ?></h3>
                                        </div>
                                    </div>
                                    <div style="color: #1b5e20; font-size: 1rem; font-weight: 500; line-height: 1.4;">
                                        <?php echo nl2br(esc_html($booking->service_address)); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Total Amount Card -->
                            <div class="info-card" style="background: linear-gradient(135deg, #fff3e0 0%, #ffcc02 100%); border: 2px solid #ff9800; border-radius: 16px; padding: 1.5rem; position: relative; overflow: hidden;">
                                <div style="position: absolute; top: -20px; right: -20px; width: 80px; height: 80px; background: rgba(255,152,0,0.1); border-radius: 50%;"></div>
                                <div style="position: relative; z-index: 2;">
                                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                        <div style="background: #ff9800; color: white; padding: 0.75rem; border-radius: 12px;">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 style="margin: 0; color: #f57c00; font-size: 1.1rem; font-weight: 700;"><?php _e('Total Investment', 'NORDBOOKING'); ?></h3>
                                        </div>
                                    </div>
                                    <div style="color: #e65100; font-size: 2rem; font-weight: 800;">
                                        $<?php echo number_format($booking->total_price, 2); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Enhanced Services Section -->
                <div class="services-section-card" style="background: white; border-radius: 16px; padding: 0; margin-bottom: 2rem; box-shadow: 0 8px 32px rgba(0,0,0,0.12); overflow: hidden; border: 1px solid rgba(0,124,186,0.1);">
                    <div class="services-header" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 2rem; border-bottom: 2px solid #007cba;">
                        <h3 style="margin: 0; color: #333; font-size: 1.5rem; font-weight: 700; display: flex; align-items: center; gap: 1rem;">
                            <div style="background: #007cba; color: white; padding: 0.75rem; border-radius: 12px;">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 11H5a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2h-4"></path>
                                    <rect x="9" y="7" width="6" height="4"></rect>
                                </svg>
                            </div>
                            <?php _e('Your Service Package', 'NORDBOOKING'); ?>
                        </h3>
                        <p style="margin: 0.5rem 0 0 0; color: #666; font-size: 1rem;"><?php _e('Everything included in your booking', 'NORDBOOKING'); ?></p>
                    </div>
                    
                    <div class="services-content" style="padding: 2rem;">
                        <?php if (!empty($booking_items)): ?>
                            <div class="services-list" style="display: flex; flex-direction: column; gap: 1.5rem;">
                                <?php foreach ($booking_items as $index => $item): ?>
                                    <div class="service-item-enhanced" style="background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%); border: 2px solid #e9ecef; border-radius: 16px; padding: 2rem; transition: all 0.3s ease; position: relative; overflow: hidden;">
                                        <!-- Service Number Badge -->
                                        <div style="position: absolute; top: -10px; left: 2rem; background: #007cba; color: white; padding: 0.5rem 1rem; border-radius: 0 0 12px 12px; font-weight: 700; font-size: 0.875rem;">
                                            <?php _e('Service', 'NORDBOOKING'); ?> <?php echo ($index + 1); ?>
                                        </div>
                                        
                                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 2rem; margin-top: 1rem;">
                                            <div style="flex: 1;">
                                                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                                    <div style="background: linear-gradient(135deg, #007cba 0%, #005a8b 100%); color: white; padding: 0.75rem; border-radius: 12px;">
                                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                                                        </svg>
                                                    </div>
                                                    <h4 style="margin: 0; color: #333; font-size: 1.25rem; font-weight: 700;">
                                                        <?php echo esc_html($item->service_name); ?>
                                                    </h4>
                                                </div>
                                                
                                                <?php if (!empty($item->service_description)): ?>
                                                    <p style="margin: 0 0 1.5rem 0; color: #666; font-size: 1rem; line-height: 1.6; padding-left: 3.5rem;">
                                                        <?php echo esc_html($item->service_description); ?>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <!-- Enhanced Service Options -->
                                                <?php 
                                                $selected_options = [];
                                                if (!empty($item->selected_options)) {
                                                    $options_data = is_string($item->selected_options) ? json_decode($item->selected_options, true) : $item->selected_options;
                                                    if (is_array($options_data)) {
                                                        $selected_options = $options_data;
                                                    }
                                                }
                                                ?>
                                                
                                                <?php if (!empty($selected_options)): ?>
                                                    <div class="service-options-enhanced" style="margin-top: 1.5rem; padding-left: 3.5rem;">
                                                        <h5 style="margin: 0 0 1rem 0; color: #333; font-size: 1rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#007cba" stroke-width="2">
                                                                <polyline points="20,6 9,17 4,12"></polyline>
                                                            </svg>
                                                            <?php _e('Customizations:', 'NORDBOOKING'); ?>
                                                        </h5>
                                                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem;">
                                                            <?php foreach ($selected_options as $option_key => $option_data): ?>
                                                                <?php
                                                                $option_name = '';
                                                                $option_value = '';
                                                                $option_price = 0;
                                                                
                                                                if (is_array($option_data) && isset($option_data['name'])) {
                                                                    $option_name = $option_data['name'];
                                                                    $value_from_db = $option_data['value'] ?? '';
                                                                    
                                                                    if (is_string($value_from_db)) {
                                                                        $decoded_value = json_decode($value_from_db, true);
                                                                        if (is_array($decoded_value) && isset($decoded_value['name']) && isset($decoded_value['value'])) {
                                                                            $option_name = $decoded_value['name'];
                                                                            $option_value = $decoded_value['value'];
                                                                            $option_price = isset($decoded_value['price']) ? floatval($decoded_value['price']) : 0;
                                                                        } else {
                                                                            $option_value = $value_from_db;
                                                                            $option_price = isset($option_data['price']) ? floatval($option_data['price']) : 0;
                                                                        }
                                                                    }
                                                                }
                                                                
                                                                if (!empty($option_name)):
                                                                ?>
                                                                    <div class="option-card" style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border: 1px solid #2196f3; padding: 0.75rem 1rem; border-radius: 12px; display: flex; justify-content: space-between; align-items: center;">
                                                                        <div>
                                                                            <div style="font-weight: 600; color: #1976d2; font-size: 0.875rem;"><?php echo esc_html($option_name); ?></div>
                                                                            <div style="color: #0d47a1; font-size: 0.8rem;"><?php echo esc_html($option_value); ?></div>
                                                                        </div>
                                                                        <?php if ($option_price != 0): ?>
                                                                            <div style="background: #1976d2; color: white; padding: 0.25rem 0.5rem; border-radius: 8px; font-size: 0.75rem; font-weight: 600;">
                                                                                <?php echo ($option_price > 0 ? '+' : '') . '$' . number_format(abs($option_price), 2); ?>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div style="text-align: right; flex-shrink: 0;">
                                                <div style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 1rem 1.5rem; border-radius: 16px; font-weight: 700; font-size: 1.25rem; box-shadow: 0 4px 12px rgba(40,167,69,0.3);">
                                                    $<?php echo number_format($item->item_total_price, 2); ?>
                                                </div>
                                                <div style="margin-top: 0.5rem; font-size: 0.8rem; color: #666; text-align: center;"><?php _e('Service Total', 'NORDBOOKING'); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; padding: 3rem; color: #666; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 16px; border: 2px dashed #dee2e6;">
                                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="margin-bottom: 1.5rem; opacity: 0.5;">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="M12 6v6l4 2"></path>
                                </svg>
                                <h4 style="margin: 0 0 0.5rem 0; color: #495057; font-size: 1.25rem;"><?php _e('No Service Details', 'NORDBOOKING'); ?></h4>
                                <p style="margin: 0; font-size: 1rem;"><?php _e('Service information is not available for this booking.', 'NORDBOOKING'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Enhanced Sidebar -->
            <div class="booking-sidebar">
                <!-- Quick Actions Card -->
                <div class="quick-actions-card" style="background: white; border-radius: 16px; padding: 0; margin-bottom: 2rem; box-shadow: 0 8px 32px rgba(0,0,0,0.12); overflow: hidden; border: 1px solid rgba(0,124,186,0.1); position: sticky; top: 2rem;">
                    <div class="sidebar-header" style="background: linear-gradient(135deg, #007cba 0%, #005a8b 100%); color: white; padding: 1.5rem; text-align: center;">
                        <h3 style="margin: 0 0 0.5rem 0; font-size: 1.25rem; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 0.75rem;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                            </svg>
                            <?php _e('Quick Actions', 'NORDBOOKING'); ?>
                        </h3>
                        <p style="margin: 0; font-size: 0.9rem; opacity: 0.9;"><?php _e('Manage your booking easily', 'NORDBOOKING'); ?></p>
                    </div>
                    
                    <div class="sidebar-content" style="padding: 2rem;">
                        <!-- Status Change Section -->
                        <div class="action-section" style="margin-bottom: 2rem;">
                            <h4 style="margin: 0 0 1rem 0; color: #333; font-size: 1rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#007cba" stroke-width="2">
                                    <path d="M12 20h9"></path>
                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                                </svg>
                                <?php _e('Booking Changes', 'NORDBOOKING'); ?>
                            </h4>
                            
                            <div class="booking-actions-enhanced" style="display: flex; flex-direction: column; gap: 1rem;">
                                <button id="reschedule-btn" class="action-btn-enhanced reschedule-btn" style="display: flex; align-items: center; justify-content: center; gap: 0.75rem; background: linear-gradient(135deg, #007cba 0%, #005a8b 100%); color: white; border: none; padding: 1rem 1.5rem; border-radius: 12px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(0,124,186,0.3);">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                                        <path d="M3 3v5h5"></path>
                                        <path d="M12 7v5l4 2"></path>
                                    </svg>
                                    <?php _e('Reschedule Booking', 'NORDBOOKING'); ?>
                                </button>
                                
                                <button id="cancel-btn" class="action-btn-enhanced cancel-btn" style="display: flex; align-items: center; justify-content: center; gap: 0.75rem; background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; border: none; padding: 1rem 1.5rem; border-radius: 12px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(220,53,69,0.3);">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <path d="M15 9l-6 6"></path>
                                        <path d="M9 9l6 6"></path>
                                    </svg>
                                    <?php _e('Cancel Booking', 'NORDBOOKING'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Invoice & Documents Section -->
                        <div class="action-section" style="margin-bottom: 2rem;">
                            <h4 style="margin: 0 0 1rem 0; color: #333; font-size: 1rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#007cba" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14,2 14,8 20,8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                </svg>
                                <?php _e('Documents & Invoice', 'NORDBOOKING'); ?>
                            </h4>
                            
                            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                <button onclick="downloadInvoicePDF()" class="invoice-btn-enhanced" style="display: flex; align-items: center; justify-content: center; gap: 0.75rem; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 0.875rem 1.25rem; border-radius: 10px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; border: none; cursor: pointer; font-size: 0.9rem; box-shadow: 0 3px 8px rgba(40,167,69,0.3);">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="7,10 12,15 17,10"></polyline>
                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                    </svg>
                                    <?php _e('Download PDF', 'NORDBOOKING'); ?>
                                </button>
                                
                                <button onclick="printInvoice()" class="invoice-btn-enhanced" style="display: flex; align-items: center; justify-content: center; gap: 0.75rem; background: linear-gradient(135deg, #6c757d 0%, #495057 100%); color: white; padding: 0.875rem 1.25rem; border-radius: 10px; font-weight: 600; transition: all 0.3s ease; border: none; cursor: pointer; font-size: 0.9rem; box-shadow: 0 3px 8px rgba(108,117,125,0.3);">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="6,9 6,2 18,2 18,9"></polyline>
                                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                        <rect x="6" y="14" width="12" height="8"></rect>
                                    </svg>
                                    <?php _e('Print Invoice', 'NORDBOOKING'); ?>
                                </button>
                                
                                <button onclick="emailInvoice()" class="invoice-btn-enhanced" style="display: flex; align-items: center; justify-content: center; gap: 0.75rem; background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); color: white; padding: 0.875rem 1.25rem; border-radius: 10px; font-weight: 600; transition: all 0.3s ease; border: none; cursor: pointer; font-size: 0.9rem; box-shadow: 0 3px 8px rgba(23,162,184,0.3);">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                        <polyline points="22,6 12,13 2,6"></polyline>
                                    </svg>
                                    <?php _e('Email Invoice', 'NORDBOOKING'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Support Section -->
                        <div class="action-section">
                            <h4 style="margin: 0 0 1rem 0; color: #333; font-size: 1rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#007cba" stroke-width="2">
                                    <path d="M9 12l2 2 4-4"></path>
                                    <path d="M21 12c.552 0 1-.448 1-1V8a2 2 0 0 0-2-2h-1l-1-2h-3l-1 2H9l-1-2H5L4 8H3a2 2 0 0 0-2 2v3c0 .552.448 1 1 1"></path>
                                </svg>
                                <?php _e('Need Help?', 'NORDBOOKING'); ?>
                            </h4>
                            
                            <div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border: 2px solid #dee2e6; border-radius: 12px; padding: 1.5rem; text-align: center;">
                                <p style="margin: 0 0 1rem 0; color: #666; font-size: 0.9rem; line-height: 1.4;">
                                    <?php _e('Have questions about your booking? Our support team is here to help!', 'NORDBOOKING'); ?>
                                </p>
                                <a href="mailto:<?php echo esc_attr(get_option('admin_email')); ?>" style="display: inline-flex; align-items: center; gap: 0.5rem; background: #007cba; color: white; padding: 0.75rem 1rem; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.875rem; transition: all 0.3s ease;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                        <polyline points="22,6 12,13 2,6"></polyline>
                                    </svg>
                                    <?php _e('Contact Support', 'NORDBOOKING'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>



        <!-- Enhanced Reschedule Form (Hidden by default) -->
        <div id="reschedule-form" class="reschedule-form-enhanced" style="display: none; background: white; border-radius: 16px; padding: 0; margin-bottom: 2rem; box-shadow: 0 12px 40px rgba(0,0,0,0.15); overflow: hidden; border: 1px solid rgba(0,124,186,0.2);">
            <div style="background: linear-gradient(135deg, #007cba 0%, #005a8b 100%); color: white; padding: 2rem; text-align: center;">
                <h3 style="margin: 0 0 0.5rem 0; color: white; font-size: 1.5rem; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 0.75rem;">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                        <path d="M3 3v5h5"></path>
                        <path d="M12 7v5l4 2"></path>
                    </svg>
                    <?php _e('Reschedule Your Booking', 'NORDBOOKING'); ?>
                </h3>
                <p style="margin: 0; color: rgba(255,255,255,0.9); font-size: 1rem;"><?php _e('Choose a new date and time that works better for you', 'NORDBOOKING'); ?></p>
            </div>
            
            <div style="padding: 2rem;")
            
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

        <!-- Enhanced Cancel Confirmation (Hidden by default) -->
        <div id="cancel-confirmation" class="cancel-confirmation-enhanced" style="display: none; background: white; border-radius: 16px; padding: 0; margin-bottom: 2rem; box-shadow: 0 12px 40px rgba(220,53,69,0.2); overflow: hidden; border: 1px solid rgba(220,53,69,0.3);">
            <div style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 2rem; text-align: center;">
                <h3 style="margin: 0 0 0.5rem 0; color: white; font-size: 1.5rem; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 0.75rem;">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M15 9l-6 6"></path>
                        <path d="M9 9l6 6"></path>
                    </svg>
                    <?php _e('Cancel Booking', 'NORDBOOKING'); ?>
                </h3>
                <p style="margin: 0; color: rgba(255,255,255,0.9); font-size: 1rem;"><?php _e('This action cannot be undone. Please confirm your decision.', 'NORDBOOKING'); ?></p>
            </div>
            
            <div style="padding: 2rem;">
                <div style="background: #fff5f5; border: 2px solid #fed7d7; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; text-align: center;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#e53e3e" stroke-width="2" style="margin-bottom: 1rem;">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    <p style="margin: 0; color: #742a2a; font-size: 1rem; font-weight: 600;"><?php _e('Are you sure you want to cancel this booking?', 'NORDBOOKING'); ?></p>
                </div>
            
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
/* Enhanced Booking Management Styles */
.customer-booking-management {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
}

/* Enhanced Action Buttons */
.action-btn-enhanced:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}

.reschedule-btn:hover {
    background: linear-gradient(135deg, #0056b3 0%, #004085 100%) !important;
}

.cancel-btn:hover {
    background: linear-gradient(135deg, #c82333 0%, #a71e2a 100%) !important;
}

/* Enhanced Invoice Buttons */
.invoice-btn-enhanced:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.2) !important;
}

/* Timeline Animations */
.timeline-step.completed {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

/* Info Card Hover Effects */
.info-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.15);
    transition: all 0.3s ease;
}

/* Service Item Hover Effects */
.service-item-enhanced:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    border-color: #007cba;
}

/* Option Card Animations */
.option-card {
    transition: all 0.2s ease;
}

.option-card:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 12px rgba(33,150,243,0.3);
}

/* Status Badge Styles */
.status-pending {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%) !important;
    color: #856404 !important;
    border: 2px solid #ffc107 !important;
}

.status-confirmed {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%) !important;
    color: #155724 !important;
    border: 2px solid #28a745 !important;
}

.status-cancelled {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%) !important;
    color: #721c24 !important;
    border: 2px solid #dc3545 !important;
}

/* Form Enhancements */
.NORDBOOKING-datetime-grid {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1.5rem;
    border: 2px solid #e9ecef;
}

.NORDBOOKING-time-slots .time-slot-btn {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 0.75rem;
    font-weight: 600;
    transition: all 0.2s ease;
    cursor: pointer;
}

.NORDBOOKING-time-slots .time-slot-btn:hover:not(:disabled) {
    background: #e3f2fd;
    border-color: #2196f3;
    color: #1976d2;
    transform: translateY(-1px);
}

.NORDBOOKING-time-slots .time-slot-btn.selected {
    background: linear-gradient(135deg, #007cba 0%, #005a8b 100%);
    color: white;
    border-color: #007cba;
    box-shadow: 0 4px 12px rgba(0,124,186,0.3);
}

.NORDBOOKING-time-slots .time-slot-btn:disabled {
    background: #f8f9fa;
    color: #6c757d;
    cursor: not-allowed;
    opacity: 0.6;
}

/* Enhanced Flatpickr Calendar Styles */
.NORDBOOKING-flatpickr .flatpickr-calendar {
    border: none !important;
    box-shadow: 0 8px 32px rgba(0,0,0,0.12) !important;
    width: 100% !important;
    border-radius: 12px !important;
    overflow: hidden !important;
}

.NORDBOOKING-flatpickr .flatpickr-months {
    background: linear-gradient(135deg, #007cba 0%, #005a8b 100%) !important;
    color: white !important;
    padding: 1rem !important;
}

.NORDBOOKING-flatpickr .flatpickr-current-month .flatpickr-monthDropdown-months,
.NORDBOOKING-flatpickr .flatpickr-current-month input.cur-year {
    color: white !important;
    font-weight: 600 !important;
}

.NORDBOOKING-flatpickr .flatpickr-weekday {
    background: #f8f9fa !important;
    color: #333 !important;
    font-weight: 600 !important;
    padding: 0.75rem !important;
}

.NORDBOOKING-flatpickr .flatpickr-day {
    border-radius: 8px !important;
    margin: 2px !important;
    font-weight: 500 !important;
    transition: all 0.2s ease !important;
}

.NORDBOOKING-flatpickr .flatpickr-day:hover {
    background: #e3f2fd !important;
    color: #1976d2 !important;
    transform: scale(1.1) !important;
}

.NORDBOOKING-flatpickr .flatpickr-day.selected {
    background: #007cba !important;
    border-color: #007cba !important;
    box-shadow: 0 4px 12px rgba(0,124,186,0.3) !important;
}

/* Message Styles */
.NORDBOOKING-feedback.success {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    color: #155724;
    border: 2px solid #28a745;
    border-radius: 12px;
}

.NORDBOOKING-feedback.error {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    color: #721c24;
    border: 2px solid #dc3545;
    border-radius: 12px;
}

/* Loading Animation */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
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

/* Enhanced Styles */
.invoice-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.service-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.info-item {
    transition: all 0.2s ease;
}

.info-item:hover {
    transform: translateY(-1px);
}

/* Animations */
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.booking-details-card,
.invoice-actions-card,
.booking-management-card {
    animation: fadeIn 0.5s ease-out;
}

/* Status Badge Enhancements */
.status-confirmed {
    background: rgba(34, 197, 94, 0.2) !important;
    color: rgb(34, 197, 94) !important;
    border: 2px solid rgba(34, 197, 94, 0.3) !important;
}

.status-pending {
    background: rgba(245, 158, 11, 0.2) !important;
    color: rgb(245, 158, 11) !important;
    border: 2px solid rgba(245, 158, 11, 0.3) !important;
}

.status-cancelled {
    background: rgba(239, 68, 68, 0.2) !important;
    color: rgb(239, 68, 68) !important;
    border: 2px solid rgba(239, 68, 68, 0.3) !important;
}

/* Enhanced Responsive Design */
@media (max-width: 1024px) {
    .container > div {
        grid-template-columns: 1fr !important;
        gap: 1.5rem !important;
    }
    
    .booking-sidebar {
        order: -1;
    }
    
    .quick-actions-card {
        position: static !important;
    }
}

@media (max-width: 768px) {
    .business-header {
        padding: 1.5rem 0 !important;
    }
    
    .business-header .container > div {
        flex-direction: column !important;
        text-align: center !important;
        gap: 1rem !important;
    }
    
    .timeline-steps {
        flex-direction: column !important;
        gap: 1rem !important;
    }
    
    .timeline-steps > div:not(:first-child) {
        display: none !important;
    }
    
    .key-info-section > div {
        grid-template-columns: 1fr !important;
        gap: 1rem !important;
    }
    
    .NORDBOOKING-datetime-grid {
        grid-template-columns: 1fr !important;
        gap: 1.5rem !important;
    }
    
    #reschedule-time-slots-container {
        height: 200px !important;
    }
    
    .timeline-header,
    .services-header,
    .sidebar-header {
        padding: 1.5rem !important;
    }
    
    .services-content,
    .sidebar-content {
        padding: 1.5rem !important;
    }
    
    .service-item-enhanced {
        padding: 1.5rem !important;
    }
    
    .service-item-enhanced > div {
        flex-direction: column !important;
        gap: 1rem !important;
    }
    
    .service-item-enhanced > div > div:last-child {
        text-align: left !important;
    }
    
    .booking-actions-enhanced {
        gap: 0.75rem !important;
    }
    
    .sidebar-content > div {
        flex-direction: column !important;
        gap: 0.75rem !important;
    }
}

@media (max-width: 480px) {
    .container {
        padding: 0 0.75rem !important;
    }
    
    .business-header h1 {
        font-size: 1.5rem !important;
    }
    
    .timeline-header h2 {
        font-size: 1.25rem !important;
    }
    
    .info-card {
        padding: 1rem !important;
    }
    
    .service-item-enhanced {
        padding: 1rem !important;
    }
    
    .sidebar-content {
        padding: 1rem !important;
    }
    
    .action-btn-enhanced,
    .invoice-btn-enhanced {
        padding: 0.75rem 1rem !important;
        font-size: 0.875rem !important;
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

// Invoice Functions
function printInvoice() {
    const printWindow = window.open(
        '<?php echo esc_url(home_url('/invoice-standalone.php?booking_id=' . $booking->booking_id . '&download_invoice=true')); ?>',
        '_blank',
        'width=800,height=600'
    );
    
    if (printWindow) {
        printWindow.onload = function() {
            printWindow.print();
        };
    }
}

function downloadInvoicePDF() {
    // Open invoice in new window optimized for PDF generation
    const pdfWindow = window.open(
        '<?php echo esc_url(home_url('/invoice-standalone.php?booking_id=' . $booking->booking_id . '&download_as_pdf=true')); ?>',
        '_blank',
        'width=800,height=600,scrollbars=yes,resizable=yes'
    );
    
    if (pdfWindow) {
        // Focus the window
        pdfWindow.focus();
        
        // Show instructions to user
        showMessage('<?php _e('Invoice opened in new window. Use your browser\'s print function and select "Save as PDF" to download.', 'NORDBOOKING'); ?>', 'success');
    } else {
        showMessage('<?php _e('Please allow popups for this site to download the PDF invoice.', 'NORDBOOKING'); ?>', 'error');
    }
}

function emailInvoice() {
    // Show loading state
    const emailBtn = event.target;
    const originalText = emailBtn.innerHTML;
    emailBtn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;"><circle cx="12" cy="12" r="3"></circle></svg> <?php _e('Sending...', 'NORDBOOKING'); ?>';
    emailBtn.disabled = true;
    
    // Send email request
    const formData = new FormData();
    formData.append('action', 'nordbooking_email_invoice');
    formData.append('booking_id', <?php echo intval($booking->booking_id); ?>);
    formData.append('booking_token', '<?php echo esc_js($booking_token); ?>');
    formData.append('nonce', '<?php echo wp_create_nonce('nordbooking_customer_booking_management'); ?>');
    
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage('<?php _e('Invoice has been sent to your email address.', 'NORDBOOKING'); ?>', 'success');
        } else {
            showMessage(data.data.message || '<?php _e('Failed to send invoice. Please try again.', 'NORDBOOKING'); ?>', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('<?php _e('An error occurred while sending the invoice.', 'NORDBOOKING'); ?>', 'error');
    })
    .finally(() => {
        emailBtn.innerHTML = originalText;
        emailBtn.disabled = false;
    });
}
</script>

<?php get_footer(); ?>