<?php
/**
 * Redesigned Booking Details Page
 * Modern, insightful, and organized layout
 */

if (!defined('ABSPATH')) {
    exit;
}

// Ensure variables are set (they should be by page-bookings.php)
if (!isset($single_booking_id) || !is_numeric($single_booking_id) ||
    !isset($bookings_manager) || !isset($currency_symbol) || !isset($current_user_id)) {
    echo '<div class="notice notice-error"><p>' . esc_html__('Required data not available to display booking.', 'NORDBOOKING') . '</p></div>';
    return;
}

$booking_id_to_fetch = $single_booking_id;
$user_id_for_permission_check = $current_user_id;

$actual_booking_owner_id = $bookings_manager->get_booking_owner_id($booking_id_to_fetch);
$booking_owner_id_for_fetch = null;

if ($actual_booking_owner_id === null) {
    echo '<div class="notice notice-error"><p>' . esc_html__('Booking not found or owner could not be determined.', 'NORDBOOKING') . '</p></div>';
    return;
}

$can_view = false;
if (NORDBOOKING\Classes\Auth::is_user_business_owner($user_id_for_permission_check)) {
    if ($user_id_for_permission_check === $actual_booking_owner_id) {
        $can_view = true;
        $booking_owner_id_for_fetch = $user_id_for_permission_check;
    }
} elseif (NORDBOOKING\Classes\Auth::is_user_worker($user_id_for_permission_check)) {
    $worker_owner_id = NORDBOOKING\Classes\Auth::get_business_owner_id_for_worker($user_id_for_permission_check);
    $booking_to_check = $bookings_manager->get_booking($booking_id_to_fetch, $actual_booking_owner_id);
    if ($worker_owner_id && $worker_owner_id === $actual_booking_owner_id && $booking_to_check && (int)$booking_to_check['assigned_staff_id'] === $user_id_for_permission_check) {
        $can_view = true;
        $booking_owner_id_for_fetch = $worker_owner_id;
    }
}

if (!$can_view) {
    echo '<div class="notice notice-error"><p>' . esc_html__('You do not have permission to view this booking.', 'NORDBOOKING') . '</p></div>';
    return;
}

$booking = $bookings_manager->get_booking($booking_id_to_fetch, $booking_owner_id_for_fetch);

if (!$booking) {
    echo '<div class="notice notice-error"><p>' . esc_html__('Booking details could not be retrieved or access denied.', 'NORDBOOKING') . '</p></div>';
    return;
}

// Calculate booking metrics
$booking_age_days = floor((time() - strtotime($booking['created_at'])) / (60 * 60 * 24));
$is_upcoming = strtotime($booking['booking_date'] . ' ' . $booking['booking_time']) > time();
$days_until_service = $is_upcoming ? floor((strtotime($booking['booking_date'] . ' ' . $booking['booking_time']) - time()) / (60 * 60 * 24)) : 0;

// Status configurations
$status_config = [
    'pending' => ['color' => '#f59e0b', 'bg' => '#fef3c7', 'icon' => 'clock', 'label' => 'Pending'],
    'confirmed' => ['color' => '#3b82f6', 'bg' => '#dbeafe', 'icon' => 'check-circle', 'label' => 'Confirmed'],
    'processing' => ['color' => '#8b5cf6', 'bg' => '#e9d5ff', 'icon' => 'play-circle', 'label' => 'In Progress'],
    'completed' => ['color' => '#10b981', 'bg' => '#d1fae5', 'icon' => 'check-circle-2', 'label' => 'Completed'],
    'cancelled' => ['color' => '#ef4444', 'bg' => '#fee2e2', 'icon' => 'x-circle', 'label' => 'Cancelled'],
    'on-hold' => ['color' => '#f97316', 'bg' => '#fed7aa', 'icon' => 'pause-circle', 'label' => 'On Hold']
];

$current_status = $status_config[$booking['status']] ?? $status_config['pending'];

// Prepare formatted data
$booking_date_formatted = date_i18n(get_option('date_format'), strtotime($booking['booking_date']));
$booking_time_formatted = date_i18n(get_option('time_format'), strtotime($booking['booking_time']));
$created_at_formatted = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($booking['created_at']));
$updated_at_formatted = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($booking['updated_at']));

$main_bookings_page_url = home_url('/dashboard/bookings/');
?>

<div class="nordbooking-booking-details-redesigned">
    <!-- Header Section -->
    <div class="booking-header">
        <div class="booking-header-content">
            <div class="booking-title-section">
                <div class="booking-breadcrumb">
                    <a href="<?php echo esc_url($main_bookings_page_url); ?>" class="breadcrumb-link">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                        <?php _e('All Bookings', 'NORDBOOKING'); ?>
                    </a>
                    <span class="breadcrumb-separator">/</span>
                    <span class="breadcrumb-current"><?php echo esc_html($booking['booking_reference']); ?></span>
                </div>
                <h1 class="booking-title">
                    <?php _e('Booking Details', 'NORDBOOKING'); ?>
                    <span class="booking-reference">#<?php echo esc_html($booking['booking_reference']); ?></span>
                </h1>
                <div class="booking-subtitle">
                    <?php printf(__('Created %s ago', 'NORDBOOKING'), $booking_age_days . ' ' . _n('day', 'days', $booking_age_days, 'NORDBOOKING')); ?>
                </div>
            </div>
            
            <div class="booking-status-section">
                <div class="current-status" style="background-color: <?php echo $current_status['bg']; ?>; color: <?php echo $current_status['color']; ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <?php if ($current_status['icon'] === 'clock'): ?>
                            <circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/>
                        <?php elseif ($current_status['icon'] === 'check-circle'): ?>
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/>
                        <?php elseif ($current_status['icon'] === 'play-circle'): ?>
                            <circle cx="12" cy="12" r="10"/><polygon points="10,8 16,12 10,16 10,8"/>
                        <?php elseif ($current_status['icon'] === 'x-circle'): ?>
                            <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                        <?php elseif ($current_status['icon'] === 'pause-circle'): ?>
                            <circle cx="12" cy="12" r="10"/><line x1="10" y1="15" x2="10" y2="9"/><line x1="14" y1="15" x2="14" y2="9"/>
                        <?php else: ?>
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                        <?php endif; ?>
                    </svg>
                    <span class="status-label"><?php echo esc_html($current_status['label']); ?></span>
                </div>
                
                <?php if ($is_upcoming && $days_until_service >= 0): ?>
                <div class="countdown-badge">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/>
                    </svg>
                    <?php if ($days_until_service === 0): ?>
                        <?php _e('Today', 'NORDBOOKING'); ?>
                    <?php elseif ($days_until_service === 1): ?>
                        <?php _e('Tomorrow', 'NORDBOOKING'); ?>
                    <?php else: ?>
                        <?php printf(__('In %d days', 'NORDBOOKING'), $days_until_service); ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="booking-content-grid">
        <!-- Left Column -->
        <div class="booking-main-content">
            <!-- Customer Information Card -->
            <div class="info-card customer-card">
                <div class="card-header">
                    <div class="card-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <?php _e('Customer Information', 'NORDBOOKING'); ?>
                    </div>
                </div>
                <div class="card-content">
                    <div class="customer-details">
                        <div class="customer-avatar">
                            <div class="avatar-circle">
                                <?php echo strtoupper(substr($booking['customer_name'], 0, 2)); ?>
                            </div>
                        </div>
                        <div class="customer-info">
                            <h3 class="customer-name"><?php echo esc_html($booking['customer_name']); ?></h3>
                            <div class="contact-info">
                                <div class="contact-item">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                        <polyline points="22,6 12,13 2,6"/>
                                    </svg>
                                    <a href="mailto:<?php echo esc_attr($booking['customer_email']); ?>" class="contact-link">
                                        <?php echo esc_html($booking['customer_email']); ?>
                                    </a>
                                </div>
                                <div class="contact-item">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                    </svg>
                                    <a href="tel:<?php echo esc_attr($booking['customer_phone']); ?>" class="contact-link">
                                        <?php echo esc_html($booking['customer_phone'] ?: 'N/A'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Service Details Card -->
            <div class="info-card service-card">
                <div class="card-header">
                    <div class="card-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                        </svg>
                        <?php _e('Service Details', 'NORDBOOKING'); ?>
                    </div>
                </div>
                <div class="card-content">
                    <div class="service-info">
                        <div class="service-details-grid">
                            <div class="detail-item">
                                <span class="detail-label"><?php _e('Frequency', 'NORDBOOKING'); ?></span>
                                <span class="detail-value"><?php echo esc_html(ucfirst($booking['service_frequency'] ?? 'one-time')); ?></span>
                            </div>
                            
                            <?php if ($booking['has_pets'] ?? false): ?>
                            <div class="detail-item">
                                <span class="detail-label"><?php _e('Pets', 'NORDBOOKING'); ?></span>
                                <span class="detail-value pets-yes">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="11.5" cy="8.5" r="2.5"/><path d="M11.5 15.5c-4 0-7 2-7 4.5V22h14v-2c0-2.5-3-4.5-7-4.5z"/>
                                    </svg>
                                    <?php _e('Yes', 'NORDBOOKING'); ?>
                                </span>
                            </div>
                            <?php if ($booking['pet_details']): ?>
                            <div class="detail-item full-width">
                                <span class="detail-label"><?php _e('Pet Details', 'NORDBOOKING'); ?></span>
                                <span class="detail-value"><?php echo esc_html($booking['pet_details']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>
                            
                            <div class="detail-item">
                                <span class="detail-label"><?php _e('Access Method', 'NORDBOOKING'); ?></span>
                                <span class="detail-value"><?php echo esc_html(ucfirst($booking['property_access_method'] ?? 'N/A')); ?></span>
                            </div>
                            
                            <?php if ($booking['property_access_details']): ?>
                            <div class="detail-item full-width">
                                <span class="detail-label"><?php _e('Access Details', 'NORDBOOKING'); ?></span>
                                <span class="detail-value"><?php echo esc_html($booking['property_access_details']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($booking['special_instructions']): ?>
                        <div class="special-instructions">
                            <h4><?php _e('Special Instructions', 'NORDBOOKING'); ?></h4>
                            <p><?php echo esc_html($booking['special_instructions']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Location Card -->
            <div class="info-card location-card">
                <div class="card-header">
                    <div class="card-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <?php _e('Service Location', 'NORDBOOKING'); ?>
                    </div>
                </div>
                <div class="card-content">
                    <div class="location-info">
                        <p class="address"><?php echo esc_html($booking['service_address']); ?></p>
                        <div class="location-actions">
                            <a href="https://maps.google.com/?q=<?php echo urlencode($booking['service_address']); ?>" target="_blank" class="location-link">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                    <polyline points="15,3 21,3 21,9"/>
                                    <line x1="10" y1="14" x2="21" y2="3"/>
                                </svg>
                                <?php _e('View on Maps', 'NORDBOOKING'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="booking-sidebar">
            <!-- Schedule Card -->
            <div class="info-card schedule-card">
                <div class="card-header">
                    <div class="card-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        <?php _e('Schedule', 'NORDBOOKING'); ?>
                    </div>
                </div>
                <div class="card-content">
                    <div class="schedule-info">
                        <div class="schedule-date">
                            <div class="date-display">
                                <span class="day"><?php echo date('d', strtotime($booking['booking_date'])); ?></span>
                                <div class="month-year">
                                    <span class="month"><?php echo date('M', strtotime($booking['booking_date'])); ?></span>
                                    <span class="year"><?php echo date('Y', strtotime($booking['booking_date'])); ?></span>
                                </div>
                            </div>
                            <div class="date-info">
                                <h4><?php echo $booking_date_formatted; ?></h4>
                                <p class="day-name"><?php echo date('l', strtotime($booking['booking_date'])); ?></p>
                            </div>
                        </div>
                        
                        <div class="schedule-time">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12,6 12,12 16,14"/>
                            </svg>
                            <span class="time"><?php echo $booking_time_formatted; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pricing Card -->
            <div class="info-card pricing-card">
                <div class="card-header">
                    <div class="card-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                        <?php _e('Pricing', 'NORDBOOKING'); ?>
                    </div>
                </div>
                <div class="card-content">
                    <div class="pricing-breakdown">
                        <?php if (floatval($booking['discount_amount']) > 0): ?>
                            <div class="pricing-row subtotal">
                                <span><?php _e('Subtotal', 'NORDBOOKING'); ?></span>
                                <span><?php echo esc_html($currency_symbol . number_format_i18n(floatval($booking['total_price']) + floatval($booking['discount_amount']), 2)); ?></span>
                            </div>
                            <div class="pricing-row discount">
                                <span>
                                    <?php _e('Discount', 'NORDBOOKING'); ?>
                                    <?php if ($booking['discount_code']): ?>
                                        <span class="discount-code">(<?php echo esc_html($booking['discount_code']); ?>)</span>
                                    <?php endif; ?>
                                </span>
                                <span class="discount-amount">-<?php echo esc_html($currency_symbol . number_format_i18n(floatval($booking['discount_amount']), 2)); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="pricing-row total">
                            <span><?php _e('Total', 'NORDBOOKING'); ?></span>
                            <span class="total-amount"><?php echo esc_html($currency_symbol . number_format_i18n(floatval($booking['total_price']), 2)); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Management Card -->
            <div class="info-card status-card">
                <div class="card-header">
                    <div class="card-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                        <?php _e('Status Management', 'NORDBOOKING'); ?>
                    </div>
                </div>
                <div class="card-content">
                    <div class="status-management">
                        <div class="status-selector">
                            <label for="booking-status-select"><?php _e('Change Status', 'NORDBOOKING'); ?></label>
                            <select id="booking-status-select" class="status-select" data-booking-id="<?php echo esc_attr($booking['booking_id']); ?>">
                                <?php foreach ($status_config as $status_key => $status_info): ?>
                                    <option value="<?php echo esc_attr($status_key); ?>" <?php selected($booking['status'], $status_key); ?>>
                                        <?php echo esc_html($status_info['label']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button id="save-status-btn" class="save-status-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                                <polyline points="17,21 17,13 7,13 7,21"/>
                                <polyline points="7,3 7,8 15,8"/>
                            </svg>
                            <?php _e('Save Changes', 'NORDBOOKING'); ?>
                        </button>
                        
                        <div id="status-feedback" class="status-feedback"></div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Card -->
            <div class="info-card actions-card">
                <div class="card-header">
                    <div class="card-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                        </svg>
                        <?php _e('Quick Actions', 'NORDBOOKING'); ?>
                    </div>
                </div>
                <div class="card-content">
                    <div class="quick-actions">
                        <a href="mailto:<?php echo esc_attr($booking['customer_email']); ?>?subject=<?php echo urlencode('Regarding your booking #' . $booking['booking_reference']); ?>" class="action-btn email-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                            <?php _e('Email Customer', 'NORDBOOKING'); ?>
                        </a>
                        
                        <a href="tel:<?php echo esc_attr($booking['customer_phone']); ?>" class="action-btn phone-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                            </svg>
                            <?php _e('Call Customer', 'NORDBOOKING'); ?>
                        </a>
                        
                        <button class="action-btn duplicate-btn" onclick="duplicateBooking(<?php echo $booking['booking_id']; ?>)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                            </svg>
                            <?php _e('Duplicate', 'NORDBOOKING'); ?>
                        </button>
                        
                        <button class="action-btn print-btn" onclick="window.print()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6,9 6,2 18,2 18,9"/>
                                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                                <rect x="6" y="14" width="12" height="8"/>
                            </svg>
                            <?php _e('Print', 'NORDBOOKING'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Timeline Card -->
            <div class="info-card timeline-card">
                <div class="card-header">
                    <div class="card-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                        <?php _e('Activity Timeline', 'NORDBOOKING'); ?>
                    </div>
                </div>
                <div class="card-content">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker created"></div>
                            <div class="timeline-content">
                                <h4><?php _e('Booking Created', 'NORDBOOKING'); ?></h4>
                                <p class="timeline-date"><?php echo $created_at_formatted; ?></p>
                            </div>
                        </div>
                        
                        <?php if ($booking['updated_at'] !== $booking['created_at']): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker updated"></div>
                            <div class="timeline-content">
                                <h4><?php _e('Last Updated', 'NORDBOOKING'); ?></h4>
                                <p class="timeline-date"><?php echo $updated_at_formatted; ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($is_upcoming): ?>
                        <div class="timeline-item upcoming">
                            <div class="timeline-marker upcoming"></div>
                            <div class="timeline-content">
                                <h4><?php _e('Scheduled Service', 'NORDBOOKING'); ?></h4>
                                <p class="timeline-date"><?php echo $booking_date_formatted . ' ' . $booking_time_formatted; ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Status management
    $('#save-status-btn').on('click', function() {
        const $button = $(this);
        const bookingId = $('#booking-status-select').data('booking-id');
        const newStatus = $('#booking-status-select').val();
        const $feedback = $('#status-feedback');
        
        $button.prop('disabled', true).text('Saving...');
        $feedback.hide();
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'nordbooking_update_booking_status',
                booking_id: bookingId,
                status: newStatus,
                nonce: '<?php echo wp_create_nonce('nordbooking_booking_status_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $feedback.removeClass('error').addClass('success').text('Status updated successfully!').show();
                    // Update the header status display
                    location.reload();
                } else {
                    $feedback.removeClass('success').addClass('error').text(response.data.message || 'Failed to update status').show();
                }
            },
            error: function() {
                $feedback.removeClass('success').addClass('error').text('Network error occurred').show();
            },
            complete: function() {
                $button.prop('disabled', false).html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17,21 17,13 7,13 7,21"/><polyline points="7,3 7,8 15,8"/></svg><?php _e('Save Changes', 'NORDBOOKING'); ?>');
            }
        });
    });
});

// Duplicate booking function
function duplicateBooking(bookingId) {
    if (confirm('<?php _e('Create a new booking with the same details?', 'NORDBOOKING'); ?>')) {
        // Redirect to booking form with pre-filled data
        window.location.href = '<?php echo get_permalink(get_page_by_path('booking')); ?>?duplicate=' + bookingId;
    }
}
</script>