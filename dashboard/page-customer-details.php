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
if ( ! current_user_can( \MoBooking\Classes\Auth::CAP_VIEW_CUSTOMERS ) && ! current_user_can( \MoBooking\Classes\Auth::CAP_MANAGE_CUSTOMERS ) ) {
    wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'mobooking' ) );
}

// Get customer ID from URL
$customer_id = isset( $_GET['customer_id'] ) ? absint( $_GET['customer_id'] ) : 0;

// Get customer data
$customer_manager = new \MoBooking\Classes\Customers();
$customer = $customer_manager->get_customer_by_id( $customer_id );

// Get customer bookings
$bookings_manager = new \MoBooking\Classes\Bookings( new \MoBooking\Classes\Discounts(), new \MoBooking\Classes\Notifications(), new \MoBooking\Classes\Services() );
$bookings = $bookings_manager->get_bookings_by_customer_id( $customer_id );

?>

<div class="wrap mobooking-customer-details-page">
    <h1><?php esc_html_e( 'Customer Details', 'mobooking' ); ?></h1>

    <div class="customer-details-container">
        <div class="customer-details-main">
            <h2><?php echo esc_html( $customer->full_name ); ?></h2>
            <p><strong><?php esc_html_e( 'Email:', 'mobooking' ); ?></strong> <?php echo esc_html( $customer->email ); ?></p>
            <p><strong><?php esc_html_e( 'Phone:', 'mobooking' ); ?></strong> <?php echo esc_html( $customer->phone_number ); ?></p>
            <p><strong><?php esc_html_e( 'Status:', 'mobooking' ); ?></strong> <span class="booking-status status-<?php echo esc_attr( $customer->status ); ?>"><?php echo esc_html( ucfirst( $customer->status ) ); ?></span></p>
        </div>
        <div class="customer-details-sidebar">
            <h3><?php esc_html_e( 'Bookings', 'mobooking' ); ?></h3>
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
