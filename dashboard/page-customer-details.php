<?php
/**
 * Template for displaying the Customer Details page in the MoBooking Dashboard.
 *
 * @package MoBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Security check
if ( ! current_user_can( 'mobooking_view_customers' ) ) {
    wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'mobooking' ) );
}

// Helper function to format price
if ( ! function_exists( 'mobooking_format_price' ) ) {
    function mobooking_format_price( $price, $currency_symbol = '$' ) {
        return $currency_symbol . number_format_i18n( floatval( $price ), 2 );
    }
}

// Get customer ID from URL
$customer_id = isset( $_GET['customer_id'] ) ? absint( $_GET['customer_id'] ) : 0;

if ( ! $customer_id ) {
    // Render error message if no ID
    return;
}

// Instantiate managers and get data
$customers_manager = new \MoBooking\Classes\Customers();
$tenant_id = \MoBooking\Classes\Auth::get_effective_tenant_id_for_user( get_current_user_id() );
$customer = $customers_manager->get_customer_by_id( $customer_id, $tenant_id );
$settings_manager = new \MoBooking\Classes\Settings();
$currency_code = $settings_manager->get_setting($tenant_id, 'biz_currency_code', 'USD');
$currency_symbol = \MoBooking\Classes\Utils::get_currency_symbol($currency_code);

// Handle customer not found
if ( ! $customer ) {
    // Render error message if customer not found
    return;
}

// Get booking history
$bookings = $customers_manager->get_customer_bookings($customer_id, $tenant_id);

// Find upcoming appointment
$upcoming_appointment = null;
if ( ! empty( $bookings ) ) {
    foreach ( array_reverse($bookings) as $booking ) { // Iterate from oldest to newest
        if ( in_array($booking->status, ['confirmed', 'pending']) && strtotime($booking->booking_date . ' ' . $booking->booking_time) > current_time('timestamp') ) {
            $upcoming_appointment = $booking;
            break;
        }
    }
}

?>
<div class="wrap mobooking-dashboard-wrap mobooking-customer-details-page">
    <div class="mobooking-page-header">
        <div class="mobooking-page-header-heading">
            <a href="<?php echo esc_url( home_url('/dashboard/customers/') ); ?>" class="btn btn-outline btn-sm back-btn">
                <?php echo mobooking_get_feather_icon('arrow-left'); ?>
            </a>
            <div class="header-title-container">
                <h1 class="wp-heading-inline"><?php echo esc_html( $customer->full_name ); ?></h1>
                <span class="status-badge status-<?php echo esc_attr( $customer->status ); ?>">
                    <?php echo mobooking_get_status_badge_icon_svg($customer->status); ?>
                    <span class="status-text"><?php echo esc_html( ucfirst( $customer->status ) ); ?></span>
                </span>
            </div>
        </div>
        <div class="mobooking-page-header-actions">
            <a href="#" id="mobooking-edit-customer-btn" class="btn btn-secondary"><?php esc_html_e('Edit', 'mobooking'); ?></a>
            <a href="#" class="btn btn-primary"><?php esc_html_e('New Booking', 'mobooking'); ?></a>
        </div>
    </div>

    <div class="customer-details-grid">
        <div class="customer-details-main">
            <!-- Key Information Card -->
            <div class="mobooking-card">
                <div class="mobooking-card-header">
                    <h3 class="mobooking-card-title"><?php esc_html_e('Key Information', 'mobooking'); ?></h3>
                </div>
                <div class="mobooking-card-content">
                    <div class="key-info-grid">
                        <div class="key-info-item">
                            <span class="key-info-label"><?php esc_html_e('Lifetime Value', 'mobooking'); ?></span>
                            <span class="key-info-value"><?php echo mobooking_format_price( $customer->booking_overview->total_spent ?? 0, $currency_symbol ); ?></span>
                        </div>
                        <div class="key-info-item">
                            <span class="key-info-label"><?php esc_html_e('Customer Since', 'mobooking'); ?></span>
                            <span class="key-info-value"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $customer->created_at ) ) ); ?></span>
                        </div>
                        <div class="key-info-item">
                            <span class="key-info-label"><?php esc_html_e('Total Bookings', 'mobooking'); ?></span>
                            <span class="key-info-value"><?php echo esc_html( $customer->booking_overview->total_bookings ?? 0 ); ?></span>
                        </div>
                    </div>
                    <div class="customer-info-section">
                        <h4><?php esc_html_e('Contact Information', 'mobooking'); ?></h4>
                        <ul class="customer-info-list">
                            <li>
                                <?php echo mobooking_get_feather_icon('mail'); ?>
                                <a href="mailto:<?php echo esc_attr( $customer->email ); ?>"><?php echo esc_html( $customer->email ); ?></a>
                            </li>
                            <li>
                                <?php echo mobooking_get_feather_icon('phone'); ?>
                                <span><?php echo esc_html( $customer->phone_number ?: __('N/A', 'mobooking') ); ?></span>
                            </li>
                        </ul>
                    </div>
                    <div class="customer-info-section">
                        <h4><?php esc_html_e('Address', 'mobooking'); ?></h4>
                        <ul class="customer-info-list">
                            <li>
                                <?php echo mobooking_get_feather_icon('map-pin'); ?>
                                <address>
                                    <?php
                                    echo esc_html( $customer->address_line_1 ?: '' ) . '<br>';
                                    if ( ! empty($customer->address_line_2) ) {
                                        echo esc_html( $customer->address_line_2 ) . '<br>';
                                    }
                                    echo esc_html( "{$customer->city}, {$customer->state} {$customer->zip_code}" );
                                    ?>
                                </address>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- Booking History Card -->
            <div class="mobooking-card">
                <div class="mobooking-card-header">
                    <h3 class="mobooking-card-title"><?php esc_html_e('Booking History', 'mobooking'); ?></h3>
                </div>
                <div class="mobooking-card-content">
                    <?php if ( ! empty( $bookings ) && ! is_wp_error( $bookings ) ) : ?>
                        <div class="mobooking-table-responsive-wrapper">
                            <table class="mobooking-table">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Reference', 'mobooking'); ?></th>
                                        <th><?php esc_html_e('Date', 'mobooking'); ?></th>
                                        <th><?php esc_html_e('Status', 'mobooking'); ?></th>
                                        <th class="text-right"><?php esc_html_e('Amount', 'mobooking'); ?></th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ( $bookings as $booking ) : ?>
                                        <tr>
                                            <td><?php echo esc_html( $booking->booking_reference ); ?></td>
                                            <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking->booking_date ) ) ); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo esc_attr( $booking->status ); ?>">
                                                    <?php echo mobooking_get_status_badge_icon_svg($booking->status); ?>
                                                    <span class="status-text"><?php echo esc_html( ucfirst( $booking->status ) ); ?></span>
                                                </span>
                                            </td>
                                            <td class="text-right"><?php echo mobooking_format_price( $booking->total_price, $currency_symbol ); ?></td>
                                            <td class="text-right">
                                                <a href="<?php echo esc_url( home_url('/dashboard/bookings/?action=view_booking&booking_id=' . $booking->booking_id) ); ?>" class="btn btn-outline btn-sm">
                                                    <?php esc_html_e('View', 'mobooking'); ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <div class="mobooking-no-results-message">
                            <p><?php esc_html_e('No bookings found for this customer.', 'mobooking'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="customer-details-sidebar">
            <!-- Quick Actions Card -->
            <div class="mobooking-card">
                <div class="mobooking-card-header">
                    <h3 class="mobooking-card-title"><?php esc_html_e('Quick Actions', 'mobooking'); ?></h3>
                </div>
                <div class="mobooking-card-content">
                    <div class="quick-actions-buttons">
                        <a href="mailto:<?php echo esc_attr( $customer->email ); ?>" class="btn btn-secondary">
                            <?php echo mobooking_get_feather_icon('mail'); ?>
                            <?php esc_html_e('Send Email', 'mobooking'); ?>
                        </a>
                        <a href="tel:<?php echo esc_attr( $customer->phone_number ); ?>" class="btn btn-secondary">
                            <?php echo mobooking_get_feather_icon('phone'); ?>
                            <?php esc_html_e('Call Customer', 'mobooking'); ?>
                        </a>
                    </div>
                </div>
            </div>
            <!-- Upcoming Appointment -->
            <div class="mobooking-card">
                <div class="mobooking-card-header">
                    <h3 class="mobooking-card-title"><?php esc_html_e('Upcoming Appointment', 'mobooking'); ?></h3>
                </div>
                <div class="mobooking-card-content">
                    <?php if ( $upcoming_appointment ) : ?>
                        <div class="upcoming-appointment-details">
                            <span class="upcoming-date"><?php echo date_i18n( 'F j, Y', strtotime($upcoming_appointment->booking_date) ); ?></span>
                            <span class="upcoming-time"><?php echo date_i18n( get_option( 'time_format' ), strtotime($upcoming_appointment->booking_time) ); ?></span>
                            <a href="<?php echo esc_url( home_url('/dashboard/bookings/?action=view_booking&booking_id=' . $upcoming_appointment->booking_id) ); ?>" class="btn btn-secondary btn-sm">
                                <?php esc_html_e('View Booking', 'mobooking'); ?>
                            </a>
                        </div>
                    <?php else : ?>
                        <p class="text-muted"><?php esc_html_e('No upcoming appointments.', 'mobooking'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Customer Notes Card -->
            <div class="mobooking-card">
                <div class="mobooking-card-header">
                    <h3 class="mobooking-card-title"><?php esc_html_e('Notes', 'mobooking'); ?></h3>
                    <button id="mobooking-add-note-btn" class="btn btn-outline btn-sm"><?php esc_html_e('Add Note', 'mobooking'); ?></button>
                </div>
                <div class="mobooking-card-content">
                    <div id="customer-notes-content">
                        <?php if ( ! empty( $customer->notes ) ) : ?>
                            <p><?php echo nl2br(esc_html( $customer->notes )); ?></p>
                        <?php else : ?>
                            <p class="text-muted"><?php esc_html_e('No notes for this customer yet.', 'mobooking'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Customer Modal -->
<div id="mobooking-edit-customer-modal" class="mobooking-modal" style="display:none;">
    <div class="mobooking-modal-content">
        <div class="mobooking-modal-header">
            <h3 class="mobooking-modal-title"><?php esc_html_e('Edit Customer', 'mobooking'); ?></h3>
            <button class="mobooking-modal-close">&times;</button>
        </div>
        <div class="mobooking-modal-body">
            <form id="mobooking-edit-customer-form">
                <input type="hidden" name="customer_id" value="<?php echo esc_attr($customer_id); ?>">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit-full-name"><?php esc_html_e('Full Name', 'mobooking'); ?></label>
                        <input type="text" id="edit-full-name" name="full_name" value="<?php echo esc_attr($customer->full_name); ?>">
                    </div>
                    <div class="form-group">
                        <label for="edit-email"><?php esc_html_e('Email Address', 'mobooking'); ?></label>
                        <input type="email" id="edit-email" name="email" value="<?php echo esc_attr($customer->email); ?>">
                    </div>
                    <div class="form-group">
                        <label for="edit-phone-number"><?php esc_html_e('Phone Number', 'mobooking'); ?></label>
                        <input type="tel" id="edit-phone-number" name="phone_number" value="<?php echo esc_attr($customer->phone_number); ?>">
                    </div>
                    <div class="form-group">
                        <label for="edit-status"><?php esc_html_e('Status', 'mobooking'); ?></label>
                        <select id="edit-status" name="status">
                            <option value="active" <?php selected($customer->status, 'active'); ?>><?php esc_html_e('Active', 'mobooking'); ?></option>
                            <option value="inactive" <?php selected($customer->status, 'inactive'); ?>><?php esc_html_e('Inactive', 'mobooking'); ?></option>
                            <option value="lead" <?php selected($customer->status, 'lead'); ?>><?php esc_html_e('Lead', 'mobooking'); ?></option>
                        </select>
                    </div>
                    <div class="form-group form-group-full">
                        <label for="edit-address-1"><?php esc_html_e('Address Line 1', 'mobooking'); ?></label>
                        <input type="text" id="edit-address-1" name="address_line_1" value="<?php echo esc_attr($customer->address_line_1); ?>">
                    </div>
                    <div class="form-group form-group-full">
                        <label for="edit-address-2"><?php esc_html_e('Address Line 2', 'mobooking'); ?></label>
                        <input type="text" id="edit-address-2" name="address_line_2" value="<?php echo esc_attr($customer->address_line_2); ?>">
                    </div>
                    <div class="form-group">
                        <label for="edit-city"><?php esc_html_e('City', 'mobooking'); ?></label>
                        <input type="text" id="edit-city" name="city" value="<?php echo esc_attr($customer->city); ?>">
                    </div>
                    <div class="form-group">
                        <label for="edit-state"><?php esc_html_e('State / Province', 'mobooking'); ?></label>
                        <input type="text" id="edit-state" name="state" value="<?php echo esc_attr($customer->state); ?>">
                    </div>
                    <div class="form-group">
                        <label for="edit-zip-code"><?php esc_html_e('ZIP / Postal Code', 'mobooking'); ?></label>
                        <input type="text" id="edit-zip-code" name="zip_code" value="<?php echo esc_attr($customer->zip_code); ?>">
                    </div>
                    <div class="form-group">
                        <label for="edit-country"><?php esc_html_e('Country', 'mobooking'); ?></label>
                        <input type="text" id="edit-country" name="country" value="<?php echo esc_attr($customer->country); ?>">
                    </div>
                </div>
            </form>
        </div>
        <div class="mobooking-modal-footer">
            <button type="button" class="btn btn-secondary mobooking-modal-close"><?php esc_html_e('Cancel', 'mobooking'); ?></button>
            <button type="submit" form="mobooking-edit-customer-form" class="btn btn-primary"><?php esc_html_e('Save Changes', 'mobooking'); ?></button>
        </div>
    </div>
</div>

<!-- Notes Modal -->
<div id="mobooking-notes-modal" class="mobooking-modal" style="display:none;">
    <div class="mobooking-modal-content">
        <div class="mobooking-modal-header">
            <h3 class="mobooking-modal-title"><?php esc_html_e('Customer Notes', 'mobooking'); ?></h3>
            <button class="mobooking-modal-close">&times;</button>
        </div>
        <div class="mobooking-modal-body">
            <form id="mobooking-notes-form">
                <input type="hidden" name="customer_id" value="<?php echo esc_attr($customer_id); ?>">
                <textarea name="customer_notes" rows="8" style="width:100%;"><?php echo esc_textarea( $customer->notes ); ?></textarea>
            </form>
        </div>
        <div class="mobooking-modal-footer">
            <button type="button" class="btn btn-secondary mobooking-modal-close"><?php esc_html_e('Cancel', 'mobooking'); ?></button>
            <button type="submit" form="mobooking-notes-form" class="btn btn-primary"><?php esc_html_e('Save Notes', 'mobooking'); ?></button>
        </div>
    </div>
</div>