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

// Get customer ID from URL
$customer_id = isset( $_GET['customer_id'] ) ? absint( $_GET['customer_id'] ) : 0;

// Get customer data
$customers_manager = new \MoBooking\Classes\Customers();
$customer = $customers_manager->get_customer_by_id( $customer_id );

// Get customer bookings
$bookings_manager = new \MoBooking\Classes\Bookings( new \MoBooking\Classes\Discounts(), new \MoBooking\Classes\Notifications(), new \MoBooking\Classes\Services() );
$bookings = $bookings_manager->get_bookings_by_customer_id( $customer_id );
?>

<div class="container mx-auto my-6">
    <h1 class="text-2xl font-semibold text-gray-700 dark:text-gray-200"><?php echo esc_html( $customer->full_name ); ?></h1>

    <div class="grid gap-6 mt-6 md:grid-cols-3">
        <div class="md:col-span-2">
            <div class="p-6 bg-white rounded-md shadow-md dark:bg-gray-800">
                <h2 class="text-lg font-semibold text-gray-700 capitalize dark:text-white">Core Customer Information</h2>
                <div class="grid grid-cols-1 gap-6 mt-4 sm:grid-cols-2">
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-200">Full Name:</span>
                        <span class="ml-2"><?php echo esc_html( $customer->full_name ); ?></span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-200">Email Address:</span>
                        <a href="mailto:<?php echo esc_attr( $customer->email ); ?>" class="ml-2 text-indigo-600 hover:text-indigo-900 dark:hover:text-indigo-400"><?php echo esc_html( $customer->email ); ?></a>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-200">Phone Number:</span>
                        <a href="tel:<?php echo esc_attr( $customer->phone_number ); ?>" class="ml-2 text-indigo-600 hover:text-indigo-900 dark:hover:text-indigo-400"><?php echo esc_html( $customer->phone_number ); ?></a>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-200">Customer ID:</span>
                        <span class="ml-2"><?php echo esc_html( $customer->id ); ?></span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-200">Date of Registration:</span>
                        <span class="ml-2"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $customer->created_at ) ) ); ?></span>
                    </div>
                </div>
            </div>

            <div class="p-6 mt-6 bg-white rounded-md shadow-md dark:bg-gray-800">
                <h2 class="text-lg font-semibold text-gray-700 capitalize dark:text-white">Address Information</h2>
                <div class="grid grid-cols-1 gap-6 mt-4 sm:grid-cols-2">
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-200">Primary Service Address:</span>
                        <span class="ml-2"><?php echo esc_html( $customer->address_line_1 ); ?></span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-200">Secondary Service Address:</span>
                        <span class="ml-2"><?php echo esc_html( $customer->address_line_2 ); ?></span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-200">City:</span>
                        <span class="ml-2"><?php echo esc_html( $customer->city ); ?></span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-200">State:</span>
                        <span class="ml-2"><?php echo esc_html( $customer->state ); ?></span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-200">ZIP Code:</span>
                        <span class="ml-2"><?php echo esc_html( $customer->zip_code ); ?></span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-200">Country:</span>
                        <span class="ml-2"><?php echo esc_html( $customer->country ); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="p-6 bg-white rounded-md shadow-md dark:bg-gray-800">
                <h2 class="text-lg font-semibold text-gray-700 capitalize dark:text-white">Booking Overview</h2>
                <div class="mt-4 space-y-4">
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-200">Total Bookings:</span>
                        <span class="ml-2"><?php echo esc_html( $customer->booking_overview->total_bookings ); ?></span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-200">Completed Bookings:</span>
                        <span class="ml-2"><?php echo esc_html( $customer->booking_overview->completed_bookings ); ?></span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-200">Pending Bookings:</span>
                        <span class="ml-2"><?php echo esc_html( $customer->booking_overview->pending_bookings ); ?></span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-200">Cancelled Bookings:</span>
                        <span class="ml-2"><?php echo esc_html( $customer->booking_overview->cancelled_bookings ); ?></span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-200">Total Spent:</span>
                        <span class="ml-2"><?php echo esc_html( mobooking_format_price( $customer->booking_overview->total_spent ) ); ?></span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-200">Average Booking Value:</span>
                        <span class="ml-2"><?php echo esc_html( mobooking_format_price( $customer->booking_overview->average_booking_value ) ); ?></span>
                    </div>
                </div>
            </div>

            <div class="p-6 mt-6 bg-white rounded-md shadow-md dark:bg-gray-800">
                <h2 class="text-lg font-semibold text-gray-700 capitalize dark:text-white">Booking History</h2>
                <div class="mt-4 -mx-6 overflow-x-auto">
                    <?php if ( ! empty( $bookings['bookings'] ) ) : ?>
                        <table class="min-w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-xs font-medium leading-4 tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Service</th>
                                    <th class="px-6 py-3 text-xs font-medium leading-4 tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Date</th>
                                    <th class="px-6 py-3 text-xs font-medium leading-4 tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                <?php foreach ( $bookings['bookings'] as $booking ) : ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-no-wrap"><?php echo esc_html( $booking['service_name'] ); ?></td>
                                        <td class="px-6 py-4 whitespace-no-wrap"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking['booking_date'] ) ) ); ?></td>
                                        <td class="px-6 py-4 whitespace-no-wrap"><span class="px-2 py-1 text-xs font-semibold leading-tight text-green-700 bg-green-100 rounded-full dark:bg-green-700 dark:text-green-100"><?php echo esc_html( ucfirst( $booking['status'] ) ); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else : ?>
                        <p class="px-6 text-gray-500 dark:text-gray-400">No bookings found for this customer.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
