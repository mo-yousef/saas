<?php
/**
 * Worker-specific Single Booking View
 * This file is specifically for workers to view their assigned bookings
 * without the complex permission logic of the main booking single page.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// This file expects these variables to be set:
// $booking_to_view, $current_staff_id, $business_owner_id, $currency_symbol

if (!isset($booking_to_view) || !$booking_to_view) {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'Booking not found.', 'NORDBOOKING' ) . '</p></div>';
    return;
}

// Additional security check - ensure this booking is assigned to the current worker
if ((int)$booking_to_view['assigned_staff_id'] !== $current_staff_id) {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'You do not have permission to view this booking.', 'NORDBOOKING' ) . '</p></div>';
    return;
}

$booking = $booking_to_view;
$booking_id = $booking['booking_id'];

// Process booking items for display (same logic as business owner view)
$booking_items = $booking['items'] ?? [];
$subtotal_calc = 0;

// Format dates
$booking_date_formatted = !empty($booking['booking_date']) ? date_i18n(get_option('date_format'), strtotime($booking['booking_date'])) : __('Not set', 'NORDBOOKING');
$booking_time_formatted = !empty($booking['booking_time']) ? date_i18n(get_option('time_format'), strtotime($booking['booking_time'])) : __('Not set', 'NORDBOOKING');

// Status badge class
$status_class = 'status-' . strtolower($booking['status'] ?? 'unknown');
?>

<div class="wrap nordbooking-dashboard-wrap">
    <!-- Header -->
    <div class="nordbooking-page-header">
        <div class="nordbooking-page-header-heading">
            <span class="nordbooking-page-header-icon">
                <?php echo nordbooking_get_dashboard_menu_icon('bookings'); ?>
            </span>
            <h1 class="wp-heading-inline">
                <?php printf(__('Booking #%s', 'NORDBOOKING'), esc_html($booking['booking_reference'] ?? $booking_id)); ?>
            </h1>
        </div>
        <div class="nordbooking-page-header-actions">
            <a href="<?php echo esc_url(home_url('/dashboard/my-assigned-bookings/')); ?>" class="btn btn-secondary">
                ← <?php esc_html_e('Back to My Bookings', 'NORDBOOKING'); ?>
            </a>
        </div>
    </div>

    <!-- Booking Status -->
    <div class="nordbooking-card">
        <div class="nordbooking-card-content">
            <div class="booking-status-header">
                <h2><?php esc_html_e('Booking Status', 'NORDBOOKING'); ?></h2>
                <span class="booking-status-badge <?php echo esc_attr($status_class); ?>">
                    <?php echo esc_html(ucfirst($booking['status'] ?? 'Unknown')); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="content-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-top: 1.5rem;">
        
        <!-- Left Column - Booking Details -->
        <div>
            <!-- Customer Information -->
            <div class="nordbooking-card">
                <div class="nordbooking-card-header">
                    <h3 class="nordbooking-card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <?php esc_html_e('Customer Information', 'NORDBOOKING'); ?>
                    </h3>
                </div>
                <div class="nordbooking-card-content">
                    <div class="info-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                        <div class="info-item">
                            <label><?php esc_html_e('Name', 'NORDBOOKING'); ?></label>
                            <p><?php echo esc_html($booking['customer_name'] ?? __('Not provided', 'NORDBOOKING')); ?></p>
                        </div>
                        <div class="info-item">
                            <label><?php esc_html_e('Email', 'NORDBOOKING'); ?></label>
                            <p><?php echo esc_html($booking['customer_email'] ?? __('Not provided', 'NORDBOOKING')); ?></p>
                        </div>
                        <div class="info-item">
                            <label><?php esc_html_e('Phone', 'NORDBOOKING'); ?></label>
                            <p><?php echo esc_html($booking['customer_phone'] ?? __('Not provided', 'NORDBOOKING')); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Service Details -->
            <div class="nordbooking-card">
                <div class="nordbooking-card-header">
                    <h3 class="nordbooking-card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                            <path d="M2 17l10 5 10-5"></path>
                            <path d="M2 12l10 5 10-5"></path>
                        </svg>
                        <?php esc_html_e('Pricing Details', 'NORDBOOKING'); ?>
                    </h3>
                </div>
                <div class="nordbooking-card-content">
                    <?php if (isset($booking_items) && is_array($booking_items) && !empty($booking_items)): ?>
                        <table class="NORDBOOKING-services-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Service / Option', 'NORDBOOKING'); ?></th>
                                    <th><?php esc_html_e('Details', 'NORDBOOKING'); ?></th>
                                    <th class="price-cell"><?php esc_html_e('Price', 'NORDBOOKING'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($booking_items as $item): $subtotal_calc += floatval($item['item_total_price']); ?>
                                    <tr>
                                        <td data-label="<?php esc_attr_e('Service', 'NORDBOOKING'); ?>" class="service-name-cell">
                                            <?php echo esc_html($item['service_name']); ?>
                                        </td>
                                        <td data-label="<?php esc_attr_e('Base Price', 'NORDBOOKING'); ?>" class="price-cell">
                                            <?php echo esc_html($currency_symbol . number_format_i18n(floatval($item['service_price']), 2)); ?>
                                        </td>
                                        <td data-label="<?php esc_attr_e('Item Total', 'NORDBOOKING'); ?>" class="price-cell">
                                            <?php echo esc_html($currency_symbol . number_format_i18n(floatval($item['item_total_price']), 2)); ?>
                                        </td>
                                    </tr>
                                    <?php
                                        // Ensure selected options are available as an array (decode JSON string if needed)
                                        $selected_options_raw = $item['selected_options'] ?? [];
                                        if (is_string($selected_options_raw)) {
                                            $decoded = json_decode($selected_options_raw, true);
                                            if (json_last_error() === JSON_ERROR_NONE) {
                                                $selected_options = $decoded;
                                            } else {
                                                $selected_options = [];
                                            }
                                        } else {
                                            $selected_options = is_array($selected_options_raw) ? $selected_options_raw : [];
                                        }
                                    ?>
                                    <?php if (!empty($selected_options) && is_array($selected_options)): ?>
                                        <?php
                                            foreach ($selected_options as $option_key => $option_data):
                                                $option_field_label = '';
                                                $option_selected_value_display = '';
                                                $option_price_text = '';
                                                if (is_array($option_data) && isset($option_data['name'])) {
                                                    $option_field_label = $option_data['name'];
                                                    $value_from_db = $option_data['value'] ?? '';
                                                    if (is_string($value_from_db)) {
                                                        $decoded_value = json_decode($value_from_db, true);
                                                        if (is_array($decoded_value) && isset($decoded_value['name']) && isset($decoded_value['value'])) {
                                                            $option_field_label = $decoded_value['name'];
                                                            $option_selected_value_display = esc_html($decoded_value['value']);
                                                            $current_option_price = isset($decoded_value['price']) ? floatval($decoded_value['price']) : 0;
                                                            $option_price_text = ($current_option_price >= 0 ? '+' : '') . esc_html($currency_symbol . number_format_i18n($current_option_price, 2));
                                                        } elseif (is_array($decoded_value)) {
                                                            $option_selected_value_display = esc_html(wp_json_encode($decoded_value));
                                                            $current_option_price = isset($option_data['price']) ? floatval($option_data['price']) : (isset($decoded_value['price']) ? floatval($decoded_value['price']) : 0);
                                                            $option_price_text = ($current_option_price >= 0 ? '+' : '') . esc_html($currency_symbol . number_format_i18n($current_option_price, 2));
                                                        } else {
                                                            $option_selected_value_display = esc_html($value_from_db);
                                                            $current_option_price = isset($option_data['price']) ? floatval($option_data['price']) : 0;
                                                            if ($current_option_price != 0) {
                                                                $option_price_text = ($current_option_price >= 0 ? '+' : '') . esc_html($currency_symbol . number_format_i18n($current_option_price, 2));
                                                            }
                                                        }
                                                    } elseif (is_array($value_from_db)) {
                                                        $option_selected_value_display = esc_html(wp_json_encode($value_from_db));
                                                        $current_option_price = isset($option_data['price']) ? floatval($option_data['price']) : 0;
                                                        if ($current_option_price != 0) {
                                                            $option_price_text = ($current_option_price >= 0 ? '+' : '') . esc_html($currency_symbol . number_format_i18n($current_option_price, 2));
                                                        }
                                                    } else {
                                                        $option_selected_value_display = esc_html($value_from_db);
                                                        $current_option_price = isset($option_data['price']) ? floatval($option_data['price']) : 0;
                                                        if ($current_option_price != 0) {
                                                            $option_price_text = ($current_option_price >= 0 ? '+' : '') . esc_html($currency_symbol . number_format_i18n($current_option_price, 2));
                                                        }
                                                    }
                                                } else {
                                                    $option_field_label = is_string($option_key) ? esc_html($option_key) : 'Additional Option';
                                                    $option_selected_value_display = esc_html(wp_json_encode($option_data));
                                                }
                                            ?>
                                            <tr class="option-row">
                                                <td data-label="<?php echo esc_attr($option_field_label); ?>" class="option-name">
                                                    └ <?php echo esc_html($option_field_label); ?>
                                                </td>
                                                <td data-label="<?php esc_attr_e('Selected', 'NORDBOOKING'); ?>">
                                                    <?php echo $option_selected_value_display; ?>
                                                </td>
                                                <td data-label="<?php esc_attr_e('Price', 'NORDBOOKING'); ?>" class="price-cell">
                                                    <?php echo $option_price_text; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <hr style="margin: 1.5rem 0;">
                        <div class="NORDBOOKING-pricing-summary">
                            <p><span><?php esc_html_e('Subtotal:', 'NORDBOOKING'); ?></span> <span><?php echo esc_html($currency_symbol . number_format_i18n($subtotal_calc, 2)); ?></span></p>
                            <?php 
                            $discount_amount = floatval($booking['discount_amount'] ?? 0);
                            $discount_amount_formatted = $discount_amount > 0 ? '-' . $currency_symbol . number_format_i18n($discount_amount, 2) : $currency_symbol . '0.00';
                            $total_price_formatted = $currency_symbol . number_format_i18n(floatval($booking['total_price'] ?? 0), 2);
                            ?>
                            <p><span><?php esc_html_e('Discount Applied:', 'NORDBOOKING'); ?></span> <span><?php echo esc_html($discount_amount_formatted); ?></span></p>
                            <p><strong><?php esc_html_e('Final Total:', 'NORDBOOKING'); ?></strong> <strong class="final-total"><?php echo $total_price_formatted; ?></strong></p>
                        </div>
                    <?php else: ?>
                        <p><?php esc_html_e('No service items found for this booking.', 'NORDBOOKING'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Special Instructions -->
            <?php if (!empty($booking['special_instructions'])) : ?>
            <div class="nordbooking-card">
                <div class="nordbooking-card-header">
                    <h3 class="nordbooking-card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14,2 14,8 20,8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10,9 9,9 8,9"></polyline>
                        </svg>
                        <?php esc_html_e('Special Instructions', 'NORDBOOKING'); ?>
                    </h3>
                </div>
                <div class="nordbooking-card-content">
                    <p><?php echo nl2br(esc_html($booking['special_instructions'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right Column - Booking Summary -->
        <div>
            <!-- Booking Summary -->
            <div class="nordbooking-card">
                <div class="nordbooking-card-header">
                    <h3 class="nordbooking-card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <?php esc_html_e('Booking Summary', 'NORDBOOKING'); ?>
                    </h3>
                </div>
                <div class="nordbooking-card-content">
                    <div class="summary-items">
                        <div class="summary-item" style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid #e2e8f0;">
                            <span><?php esc_html_e('Booking ID', 'NORDBOOKING'); ?></span>
                            <span style="font-weight: 600;">#<?php echo esc_html($booking['booking_reference'] ?? $booking_id); ?></span>
                        </div>
                        <div class="summary-item" style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid #e2e8f0;">
                            <span><?php esc_html_e('Date', 'NORDBOOKING'); ?></span>
                            <span style="font-weight: 600;"><?php echo esc_html($booking_date_formatted); ?></span>
                        </div>
                        <div class="summary-item" style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid #e2e8f0;">
                            <span><?php esc_html_e('Time', 'NORDBOOKING'); ?></span>
                            <span style="font-weight: 600;"><?php echo esc_html($booking_time_formatted); ?></span>
                        </div>
                        <?php if (!empty($booking['service_address'])) : ?>
                        <div class="summary-item" style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid #e2e8f0;">
                            <span><?php esc_html_e('Address', 'NORDBOOKING'); ?></span>
                            <span style="font-weight: 600; text-align: right; max-width: 60%;">
                                <?php echo nl2br(esc_html($booking['service_address'])); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        <div class="summary-item" style="display: flex; justify-content: space-between; padding: 0.75rem 0; font-size: 1.125rem; font-weight: 700;">
                            <span><?php esc_html_e('Total', 'NORDBOOKING'); ?></span>
                            <span><?php echo esc_html($currency_symbol . number_format($booking['total_price'] ?? 0, 2)); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Update -->
            <div class="nordbooking-card">
                <div class="nordbooking-card-header">
                    <h3 class="nordbooking-card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9,11 12,14 22,4"></polyline>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                        </svg>
                        <?php esc_html_e('Update Status', 'NORDBOOKING'); ?>
                    </h3>
                </div>
                <div class="nordbooking-card-content">
                    <form id="worker-status-update-form" style="display: flex; flex-direction: column; gap: 1rem;">
                        <?php wp_nonce_field('nordbooking_worker_update_status', 'worker_status_nonce'); ?>
                        <input type="hidden" name="action" value="nordbooking_worker_update_booking_status">
                        <input type="hidden" name="booking_id" value="<?php echo esc_attr($booking_id); ?>">
                        
                        <div>
                            <label for="new_status" style="display: block; font-weight: 600; margin-bottom: 0.5rem;">
                                <?php esc_html_e('New Status', 'NORDBOOKING'); ?>
                            </label>
                            <select id="new_status" name="new_status" class="nordbooking-select" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                                <option value="confirmed" <?php selected($booking['status'], 'confirmed'); ?>><?php esc_html_e('Confirmed', 'NORDBOOKING'); ?></option>
                                <option value="in_progress" <?php selected($booking['status'], 'in_progress'); ?>><?php esc_html_e('In Progress', 'NORDBOOKING'); ?></option>
                                <option value="completed" <?php selected($booking['status'], 'completed'); ?>><?php esc_html_e('Completed', 'NORDBOOKING'); ?></option>
                                <option value="cancelled" <?php selected($booking['status'], 'cancelled'); ?>><?php esc_html_e('Cancelled', 'NORDBOOKING'); ?></option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="status_notes" style="display: block; font-weight: 600; margin-bottom: 0.5rem;">
                                <?php esc_html_e('Notes (Optional)', 'NORDBOOKING'); ?>
                            </label>
                            <textarea id="status_notes" name="status_notes" rows="3" class="nordbooking-textarea" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; resize: vertical;" placeholder="<?php esc_attr_e('Add any notes about this status change...', 'NORDBOOKING'); ?>"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20,6 9,17 4,12"></polyline>
                            </svg>
                            <?php esc_html_e('Update Status', 'NORDBOOKING'); ?>
                        </button>
                    </form>
                    
                    <div id="status-update-feedback" style="display: none; margin-top: 1rem; padding: 0.75rem; border-radius: 0.375rem;"></div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="nordbooking-card">
                <div class="nordbooking-card-header">
                    <h3 class="nordbooking-card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                        <?php esc_html_e('Contact Customer', 'NORDBOOKING'); ?>
                    </h3>
                </div>
                <div class="nordbooking-card-content">
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <?php if (!empty($booking['customer_phone'])) : ?>
                        <a href="tel:<?php echo esc_attr($booking['customer_phone']); ?>" class="btn btn-outline" style="text-decoration: none;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                            <?php esc_html_e('Call Customer', 'NORDBOOKING'); ?>
                        </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($booking['customer_email'])) : ?>
                        <a href="mailto:<?php echo esc_attr($booking['customer_email']); ?>" class="btn btn-outline" style="text-decoration: none;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                            <?php esc_html_e('Email Customer', 'NORDBOOKING'); ?>
                        </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($booking['service_address'])) : ?>
                        <a href="https://maps.google.com/?q=<?php echo urlencode($booking['service_address']); ?>" target="_blank" class="btn btn-outline" style="text-decoration: none;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <?php esc_html_e('Get Directions', 'NORDBOOKING'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.booking-status-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.booking-status-badge {
    padding: 0.5rem 1rem;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.status-confirmed {
    background-color: #dcfce7;
    color: #166534;
}

.status-pending {
    background-color: #fef3c7;
    color: #92400e;
}

.status-completed {
    background-color: #dbeafe;
    color: #1e40af;
}

.status-cancelled {
    background-color: #fee2e2;
    color: #991b1b;
}

.status-in_progress {
    background-color: #fef3c7;
    color: #92400e;
}

.info-item label {
    font-weight: 600;
    color: #64748b;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
    display: block;
}

.info-item p {
    margin: 0;
    font-weight: 500;
}

@media (max-width: 768px) {
    .content-grid {
        grid-template-columns: 1fr !important;
    }
    
    .info-grid {
        grid-template-columns: 1fr !important;
    }
}

.status-update-success {
    background-color: #dcfce7;
    border: 1px solid #16a34a;
    color: #166534;
}

.status-update-error {
    background-color: #fee2e2;
    border: 1px solid #ef4444;
    color: #991b1b;
}

/* Service Table Styling - Matching Business Owner View */
.NORDBOOKING-services-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1rem;
    font-size: 0.875rem;
}

.NORDBOOKING-services-table th {
    text-align: left;
    padding: 0.75rem 0.5rem;
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
    border-bottom: 2px solid #e2e8f0;
    background-color: #f9fafb;
}

.NORDBOOKING-services-table th.price-cell,
.NORDBOOKING-services-table th:last-child {
    text-align: right;
}

.NORDBOOKING-services-table td {
    padding: 0.75rem 0.5rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: top;
}

.NORDBOOKING-services-table .price-cell {
    text-align: right;
    font-weight: 500;
}

.NORDBOOKING-services-table .service-name-cell {
    font-weight: 600;
    color: #111827;
}

.NORDBOOKING-services-table .option-row td {
    padding: 0.5rem 0.5rem;
    font-size: 0.875rem;
    color: #6b7280;
    border-bottom: 1px solid #f9fafb;
}

.NORDBOOKING-services-table .option-row .option-name {
    padding-left: 1.5rem;
    font-weight: 500;
}

.NORDBOOKING-pricing-summary {
    margin-top: 1rem;
}

.NORDBOOKING-pricing-summary p {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 0.5rem 0;
    padding: 0.25rem 0;
}

.NORDBOOKING-pricing-summary p:last-child {
    border-top: 1px solid #e2e8f0;
    padding-top: 0.75rem;
    margin-top: 0.75rem;
    font-size: 1.125rem;
}

.NORDBOOKING-pricing-summary .final-total {
    color: #059669;
    font-size: 1.25rem;
}

/* Responsive table */
@media (max-width: 640px) {
    .NORDBOOKING-services-table {
        font-size: 0.875rem;
    }
    
    .NORDBOOKING-services-table th,
    .NORDBOOKING-services-table td {
        padding: 0.5rem 0.25rem;
    }
    
    .NORDBOOKING-services-table .option-row td:first-child {
        padding-left: 1rem;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle status update form submission
    $('#worker-status-update-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitButton = $form.find('button[type="submit"]');
        const $feedback = $('#status-update-feedback');
        const originalButtonText = $submitButton.html();
        
        // Show loading state
        $submitButton.prop('disabled', true).html(
            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation: spin 1s linear infinite;"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg> <?php esc_html_e("Updating...", "NORDBOOKING"); ?>'
        );
        
        // Hide previous feedback
        $feedback.hide();
        
        // Submit via AJAX
        $.ajax({
            url: '<?php echo admin_url("admin-ajax.php"); ?>',
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $feedback.removeClass('status-update-error')
                            .addClass('status-update-success')
                            .html('<strong><?php esc_html_e("Success!", "NORDBOOKING"); ?></strong> ' + response.data.message)
                            .show();
                    
                    // Update the status badge on the page
                    if (response.data.new_status) {
                        const newStatusClass = 'status-' + response.data.new_status.toLowerCase();
                        const newStatusText = response.data.new_status_display || response.data.new_status;
                        
                        $('.booking-status-badge')
                            .removeClass('status-confirmed status-pending status-completed status-cancelled status-in_progress')
                            .addClass(newStatusClass)
                            .text(newStatusText);
                    }
                    
                    // Clear the notes field
                    $('#status_notes').val('');
                    
                    // Auto-hide success message after 5 seconds
                    setTimeout(function() {
                        $feedback.fadeOut();
                    }, 5000);
                    
                } else {
                    // Show error message
                    $feedback.removeClass('status-update-success')
                            .addClass('status-update-error')
                            .html('<strong><?php esc_html_e("Error!", "NORDBOOKING"); ?></strong> ' + (response.data.message || '<?php esc_html_e("An error occurred while updating the status.", "NORDBOOKING"); ?>'))
                            .show();
                }
            },
            error: function(xhr, status, error) {
                // Show error message
                $feedback.removeClass('status-update-success')
                        .addClass('status-update-error')
                        .html('<strong><?php esc_html_e("Error!", "NORDBOOKING"); ?></strong> <?php esc_html_e("Failed to update status. Please try again.", "NORDBOOKING"); ?>')
                        .show();
            },
            complete: function() {
                // Reset button state
                $submitButton.prop('disabled', false).html(originalButtonText);
            }
        });
    });
});
</script>