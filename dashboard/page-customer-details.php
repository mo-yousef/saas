<?php
/**
 * Template for displaying the Customer Details page in the NORDBOOKING Dashboard.
 *
 * @package NORDBOOKING
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Security check
if ( ! current_user_can( 'nordbooking_view_customers' ) ) {
    wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'NORDBOOKING' ) );
}

// Helper function to format price
if ( ! function_exists( 'nordbooking_format_price' ) ) {
    function nordbooking_format_price( $price, $currency_symbol = '$' ) {
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
$customers_manager = new \NORDBOOKING\Classes\Customers();
$tenant_id = \NORDBOOKING\Classes\Auth::get_effective_tenant_id_for_user( get_current_user_id() );
$customer = $customers_manager->get_customer_by_id( $customer_id, $tenant_id );
$settings_manager = new \NORDBOOKING\Classes\Settings();
$currency_code = $settings_manager->get_setting($tenant_id, 'biz_currency_code', 'USD');
$currency_symbol = \NORDBOOKING\Classes\Utils::get_currency_symbol($currency_code);

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
<div class="wrap nordbooking-dashboard-wrap NORDBOOKING-customer-details-page">
    <div class="nordbooking-page-header">
        <div class="nordbooking-page-header-heading">
            <a href="<?php echo esc_url( home_url('/dashboard/customers/') ); ?>" class="btn btn-outline btn-sm back-btn">
                <?php echo nordbooking_get_feather_icon('arrow-left'); ?>
            </a>
            <div class="header-title-container">
                <h1 class="wp-heading-inline"><?php echo esc_html( $customer->full_name ); ?></h1>
                <span class="status-badge status-<?php echo esc_attr( $customer->status ); ?>">
                    <?php echo nordbooking_get_status_badge_icon_svg($customer->status); ?>
                    <span class="status-text"><?php echo esc_html( ucfirst( $customer->status ) ); ?></span>
                </span>
            </div>
        </div>
        <div class="nordbooking-page-header-actions">
            <a href="#" id="NORDBOOKING-edit-customer-btn" class="btn btn-secondary"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg><?php esc_html_e('Edit', 'NORDBOOKING'); ?></a>
        </div>
    </div>

    <div class="customer-details-grid">
        <div class="customer-details-main">
            <!-- Key Information Card -->
            <div class="nordbooking-card card-bs">
                <div class="nordbooking-card-header">
                    <h3 class="nordbooking-card-title"><?php esc_html_e('Key Information', 'NORDBOOKING'); ?></h3>
                </div>
                <div class="nordbooking-card-content">
                    <div class="key-info-grid">
                        <div class="key-info-item">
                            <?php echo nordbooking_get_feather_icon('dollar-sign'); ?>
                            <div class="key-info-content">
                                <span class="key-info-label"><?php esc_html_e('Lifetime Value', 'NORDBOOKING'); ?></span>
                                <span class="key-info-value"><?php echo nordbooking_format_price( $customer->booking_overview->total_spent ?? 0, $currency_symbol ); ?></span>
                            </div>
                        </div>
                        <div class="key-info-item">
                            <?php echo nordbooking_get_feather_icon('calendar'); ?>
                            <div class="key-info-content">
                                <span class="key-info-label"><?php esc_html_e('Customer Since', 'NORDBOOKING'); ?></span>
                                <span class="key-info-value"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $customer->created_at ) ) ); ?></span>
                            </div>
                        </div>
                        <div class="key-info-item">
                            <?php echo nordbooking_get_feather_icon('hash'); ?>
                            <div class="key-info-content">
                                <span class="key-info-label"><?php esc_html_e('Total Bookings', 'NORDBOOKING'); ?></span>
                                <span class="key-info-value"><?php echo esc_html( $customer->booking_overview->total_bookings ?? 0 ); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="customer-info-section">
                        <h4><?php esc_html_e('Contact Information', 'NORDBOOKING'); ?></h4>
                        <ul class="customer-info-list">
                            <li>
                                <?php echo nordbooking_get_feather_icon('mail'); ?>
                                <a href="mailto:<?php echo esc_attr( $customer->email ); ?>"><?php echo esc_html( $customer->email ); ?></a>
                            </li>
                            <li>
                                <?php echo nordbooking_get_feather_icon('phone'); ?>
                                <span><?php echo esc_html( $customer->phone_number ?: __('N/A', 'NORDBOOKING') ); ?></span>
                            </li>
                        </ul>
                    </div>
                    <div class="customer-info-section">
                        <h4><?php esc_html_e('Address', 'NORDBOOKING'); ?></h4>
                        <ul class="customer-info-list">
                            <li>
                                <?php echo nordbooking_get_feather_icon('map-pin'); ?>
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
            <div class="nordbooking-card">
                <div class="nordbooking-card-header">
                    <h3 class="nordbooking-card-title"><?php esc_html_e('Booking History', 'NORDBOOKING'); ?></h3>
                </div>
                <div class="nordbooking-card-content">
                    <?php if ( ! empty( $bookings ) && ! is_wp_error( $bookings ) ) : ?>
                        <div class="nordbooking-table-responsive-wrapper">
                            <table class="nordbooking-table">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Reference', 'NORDBOOKING'); ?></th>
                                        <th><?php esc_html_e('Date', 'NORDBOOKING'); ?></th>
                                        <th><?php esc_html_e('Status', 'NORDBOOKING'); ?></th>
                                        <th class="text-right"><?php esc_html_e('Amount', 'NORDBOOKING'); ?></th>
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
                                                    <?php echo nordbooking_get_status_badge_icon_svg($booking->status); ?>
                                                    <span class="status-text"><?php echo esc_html( ucfirst( $booking->status ) ); ?></span>
                                                </span>
                                            </td>
                                            <td class="text-right"><?php echo nordbooking_format_price( $booking->total_price, $currency_symbol ); ?></td>
                                            <td class="text-right">
                                                <a href="<?php echo esc_url( home_url('/dashboard/bookings/?action=view_booking&booking_id=' . $booking->booking_id) ); ?>" class="btn btn-outline btn-sm">
                                                    <?php esc_html_e('View', 'NORDBOOKING'); ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <div class="NORDBOOKING-no-results-message">
                            <p><?php esc_html_e('No bookings found for this customer.', 'NORDBOOKING'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="customer-details-sidebar">
            <!-- Quick Actions Card -->
            <div class="nordbooking-card">
                <div class="nordbooking-card-header">
                    <h3 class="nordbooking-card-title"><?php esc_html_e('Quick Actions', 'NORDBOOKING'); ?></h3>
                </div>
                <div class="nordbooking-card-content">
                    <div class="quick-actions-buttons">
                        <a href="mailto:<?php echo esc_attr( $customer->email ); ?>" class="btn btn-secondary">
                            <?php echo nordbooking_get_feather_icon('mail'); ?>
                            <?php esc_html_e('Send Email', 'NORDBOOKING'); ?>
                        </a>
                        <a href="tel:<?php echo esc_attr( $customer->phone_number ); ?>" class="btn btn-secondary">
                            <?php echo nordbooking_get_feather_icon('phone'); ?>
                            <?php esc_html_e('Call Customer', 'NORDBOOKING'); ?>
                        </a>
                    </div>
                </div>
            </div>
            <!-- Upcoming Appointment -->
            <div class="nordbooking-card">
                <div class="nordbooking-card-header">
                    <h3 class="nordbooking-card-title"><?php esc_html_e('Upcoming Appointment', 'NORDBOOKING'); ?></h3>
                </div>
                <div class="nordbooking-card-content">
                    <?php if ( $upcoming_appointment ) : ?>
                        <div class="upcoming-appointment-details">
                            <span class="upcoming-date"><?php echo date_i18n( 'F j, Y', strtotime($upcoming_appointment->booking_date) ); ?></span>
                            <span class="upcoming-time"><?php echo date_i18n( get_option( 'time_format' ), strtotime($upcoming_appointment->booking_time) ); ?></span>
                            <a href="<?php echo esc_url( home_url('/dashboard/bookings/?action=view_booking&booking_id=' . $upcoming_appointment->booking_id) ); ?>" class="btn btn-secondary btn-sm">
                                <?php esc_html_e('View Booking', 'NORDBOOKING'); ?>
                            </a>
                        </div>
                    <?php else : ?>
                        <p class="text-muted"><?php esc_html_e('No upcoming appointments.', 'NORDBOOKING'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden form for editing, to be used by the dialog -->
<div id="NORDBOOKING-edit-customer-form-template" style="display:none;">
    <form id="NORDBOOKING-edit-customer-form">
        <input type="hidden" name="customer_id" value="<?php echo esc_attr($customer_id); ?>">
        <div class="form-grid">
            <div class="form-group">
                <label for="edit-full-name"><?php esc_html_e('Full Name', 'NORDBOOKING'); ?></label>
                <input type="text" id="edit-full-name" name="full_name" value="<?php echo esc_attr($customer->full_name); ?>">
            </div>
            <div class="form-group">
                <label for="edit-email"><?php esc_html_e('Email Address', 'NORDBOOKING'); ?></label>
                <input type="email" id="edit-email" name="email" value="<?php echo esc_attr($customer->email); ?>">
            </div>
            <div class="form-group">
                <label for="edit-phone-number"><?php esc_html_e('Phone Number', 'NORDBOOKING'); ?></label>
                <input type="tel" id="edit-phone-number" name="phone_number" value="<?php echo esc_attr($customer->phone_number); ?>">
            </div>
            <div class="form-group">
                <label for="edit-status"><?php esc_html_e('Status', 'NORDBOOKING'); ?></label>
                <select id="edit-status" name="status">
                    <option value="active" <?php selected($customer->status, 'active'); ?>><?php esc_html_e('Active', 'NORDBOOKING'); ?></option>
                    <option value="inactive" <?php selected($customer->status, 'inactive'); ?>><?php esc_html_e('Inactive', 'NORDBOOKING'); ?></option>
                    <option value="lead" <?php selected($customer->status, 'lead'); ?>><?php esc_html_e('Lead', 'NORDBOOKING'); ?></option>
                </select>
            </div>
            <div class="form-group form-group-full">
                <label for="edit-address-1"><?php esc_html_e('Address Line 1', 'NORDBOOKING'); ?></label>
                <input type="text" id="edit-address-1" name="address_line_1" value="<?php echo esc_attr($customer->address_line_1); ?>">
            </div>
            <div class="form-group form-group-full">
                <label for="edit-address-2"><?php esc_html_e('Address Line 2', 'NORDBOOKING'); ?></label>
                <input type="text" id="edit-address-2" name="address_line_2" value="<?php echo esc_attr($customer->address_line_2); ?>">
            </div>
            <div class="form-group">
                <label for="edit-city"><?php esc_html_e('City', 'NORDBOOKING'); ?></label>
                <input type="text" id="edit-city" name="city" value="<?php echo esc_attr($customer->city); ?>">
            </div>
            <div class="form-group">
                <label for="edit-state"><?php esc_html_e('State / Province', 'NORDBOOKING'); ?></label>
                <input type="text" id="edit-state" name="state" value="<?php echo esc_attr($customer->state); ?>">
            </div>
            <div class="form-group">
                <label for="edit-zip-code"><?php esc_html_e('ZIP / Postal Code', 'NORDBOOKING'); ?></label>
                <input type="text" id="edit-zip-code" name="zip_code" value="<?php echo esc_attr($customer->zip_code); ?>">
            </div>
            <div class="form-group">
                <label for="edit-country"><?php esc_html_e('Country', 'NORDBOOKING'); ?></label>
                <input type="text" id="edit-country" name="country" value="<?php echo esc_attr($customer->country); ?>">
            </div>
        </div>
    </form>
</div>
