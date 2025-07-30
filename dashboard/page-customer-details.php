<?php
/**
 * Template for displaying the Customer Details page in the MoBooking Dashboard.
 *
 * @package MoBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

require_once __DIR__ . '/../functions/utilities.php';

// Security check
if ( ! current_user_can( 'mobooking_view_customers' ) ) {
    wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'mobooking' ) );
}

// Get customer ID from URL
$customer_id = isset( $_GET['customer_id'] ) ? absint( $_GET['customer_id'] ) : 0;

// Get customer data
$customers_manager = new \MoBooking\Classes\Customers();
$customer = $customers_manager->get_customer_by_id( $customer_id );

// Get customer bookings
$bookings_manager = new \MoBooking\Classes\Bookings( new \MoBooking\Classes\Discounts(), new \MoBooking\Classes\Notifications(), new \MoBooking\Classes\Services() );
$bookings = $bookings_manager->get_bookings_by_customer_id( $customer_id );
?>

<div class="wrap mobooking-customer-details-page">
    <div class="customer-details-header">
        <h1><?php echo esc_html( $customer->full_name ); ?></h1>
        <div class="customer-details-actions">
            <button id="mobooking-edit-customer-btn" class="button button-secondary"><?php esc_html_e('Edit', 'mobooking'); ?></button>
            <button id="mobooking-save-customer-btn" class="button button-primary" style="display: none;"><?php esc_html_e('Save', 'mobooking'); ?></button>
        </div>
    </div>

    <div class="customer-details-grid">
        <div class="customer-details-main">
            <div class="customer-details-section">
                <h2><?php esc_html_e( 'Core Customer Information', 'mobooking' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Full Name', 'mobooking' ); ?></th>
                        <td><?php echo esc_html( $customer->full_name ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Email Address', 'mobooking' ); ?></th>
                        <td><a href="mailto:<?php echo esc_attr( $customer->email ); ?>"><?php echo esc_html( $customer->email ); ?></a></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Phone Number', 'mobooking' ); ?></th>
                        <td><a href="tel:<?php echo esc_attr( $customer->phone_number ); ?>"><?php echo esc_html( $customer->phone_number ); ?></a></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Customer ID', 'mobooking' ); ?></th>
                        <td><?php echo esc_html( $customer->id ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Date of Registration', 'mobooking' ); ?></th>
                        <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $customer->created_at ) ) ); ?></td>
                    </tr>
                </table>
            </div>

            <div class="customer-details-section">
                <h2><?php esc_html_e( 'Address Information', 'mobooking' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Primary Service Address', 'mobooking' ); ?></th>
                        <td data-field="address_line_1"><?php echo esc_html( $customer->address_line_1 ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Secondary Service Address', 'mobooking' ); ?></th>
                        <td data-field="address_line_2"><?php echo esc_html( $customer->address_line_2 ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'City', 'mobooking' ); ?></th>
                        <td data-field="city"><?php echo esc_html( $customer->city ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'State', 'mobooking' ); ?></th>
                        <td data-field="state"><?php echo esc_html( $customer->state ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'ZIP Code', 'mobooking' ); ?></th>
                        <td data-field="zip_code"><?php echo esc_html( $customer->zip_code ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Country', 'mobooking' ); ?></th>
                        <td data-field="country"><?php echo esc_html( $customer->country ); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="customer-details-sidebar">
            <div class="customer-details-section">
                <h2><?php esc_html_e( 'Booking Overview', 'mobooking' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Total Bookings', 'mobooking' ); ?></th>
                        <td><?php echo esc_html( $customer->booking_overview->total_bookings ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Completed Bookings', 'mobooking' ); ?></th>
                        <td><?php echo esc_html( $customer->booking_overview->completed_bookings ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Pending Bookings', 'mobooking' ); ?></th>
                        <td><?php echo esc_html( $customer->booking_overview->pending_bookings ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Cancelled Bookings', 'mobooking' ); ?></th>
                        <td><?php echo esc_html( $customer->booking_overview->cancelled_bookings ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Total Spent', 'mobooking' ); ?></th>
                        <td><?php echo esc_html( mobooking_format_price( $customer->booking_overview->total_spent ) ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Average Booking Value', 'mobooking' ); ?></th>
                        <td><?php echo esc_html( mobooking_format_price( $customer->booking_overview->average_booking_value ) ); ?></td>
                    </tr>
                </table>
            </div>

            <div class="customer-details-section">
                <h2><?php esc_html_e( 'Booking History', 'mobooking' ); ?></h2>
                <?php if ( ! empty( $bookings['bookings'] ) ) : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Service', 'mobooking' ); ?></th>
                                <th><?php esc_html_e( 'Date', 'mobooking' ); ?></th>
                                <th><?php esc_html_e( 'Status', 'mobooking' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $bookings['bookings'] as $booking ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $booking['service_name'] ); ?></td>
                                    <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking['booking_date'] ) ) ); ?></td>
                                    <td><span class="booking-status status-<?php echo esc_attr( $booking['status'] ); ?>"><?php echo esc_html( ucfirst( $booking['status'] ) ); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p><?php esc_html_e( 'No bookings found for this customer.', 'mobooking' ); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
