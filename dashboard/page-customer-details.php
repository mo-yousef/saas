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
            <a href="#" class="btn btn-secondary"><?php esc_html_e('Edit', 'mobooking'); ?></a>
            <a href="#" class="btn btn-primary"><?php esc_html_e('New Booking', 'mobooking'); ?></a>
        </div>
    </div>

    <div class="customer-details-grid">
        <div class="customer-details-main">
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
            <!-- Customer Information Card -->
            <div class="mobooking-card">
                <div class="mobooking-card-header">
                    <h3 class="mobooking-card-title"><?php esc_html_e('Customer Information', 'mobooking'); ?></h3>
                </div>
                <div class="mobooking-card-content">
                    <ul class="customer-info-list">
                        <li>
                            <?php echo mobooking_get_feather_icon('mail'); ?>
                            <a href="mailto:<?php echo esc_attr( $customer->email ); ?>"><?php echo esc_html( $customer->email ); ?></a>
                        </li>
                        <li>
                            <?php echo mobooking_get_feather_icon('phone'); ?>
                            <span><?php echo esc_html( $customer->phone_number ?: __('N/A', 'mobooking') ); ?></span>
                        </li>
                        <li>
                            <?php echo mobooking_get_feather_icon('map-pin'); ?>
                            <address>
                                <?php
                                echo esc_html( $customer->address_line_1 ?: '' ) . '<br>';
                                if ( $customer->address_line_2 ) {
                                    echo esc_html( $customer->address_line_2 ) . '<br>';
                                }
                                echo esc_html( "{$customer->city}, {$customer->state} {$customer->zip_code}" );
                                ?>
                            </address>
                        </li>
                    </ul>
                </div>
            </div>
            <!-- Customer Notes Card -->
            <div class="mobooking-card">
                <div class="mobooking-card-header">
                    <h3 class="mobooking-card-title"><?php esc_html_e('Notes', 'mobooking'); ?></h3>
                    <button class="btn btn-outline btn-sm"><?php esc_html_e('Add Note', 'mobooking'); ?></button>
                </div>
                <div class="mobooking-card-content">
                    <?php if ( ! empty( $customer->notes ) ) : ?>
                        <p><?php echo esc_html( $customer->notes ); ?></p>
                    <?php else : ?>
                        <p class="text-muted"><?php esc_html_e('No notes for this customer yet.', 'mobooking'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>