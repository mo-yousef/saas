<?php
/**
 * Template for displaying the Customer Details page in the MoBooking Dashboard.
 * Completely refactored with proper error handling, tenant-aware queries, and correct URL routing.
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

// Helper function to format price since mobooking_format_price() is missing
if ( ! function_exists( 'mobooking_format_price' ) ) {
    function mobooking_format_price( $price ) {
        if ( is_null( $price ) || $price === '' ) {
            return '$0.00';
        }
        
        // Get currency settings from Settings class if available
        try {
            $settings_manager = new \MoBooking\Classes\Settings();
            $current_user_id = get_current_user_id();
            
            // Handle worker case
            $data_user_id = $current_user_id;
            if ( class_exists( 'MoBooking\Classes\Auth' ) && \MoBooking\Classes\Auth::is_user_worker( $current_user_id ) ) {
                $owner_id = \MoBooking\Classes\Auth::get_business_owner_id_for_worker( $current_user_id );
                if ( $owner_id ) {
                    $data_user_id = $owner_id;
                }
            }
            
            $currency_code = $settings_manager->get_setting( $data_user_id, 'biz_currency_code', 'USD' );
        } catch ( Exception $e ) {
            error_log( 'MoBooking: Error getting currency settings: ' . $e->getMessage() );
            $currency_code = 'USD';
        }
        
        // Simple currency symbol mapping
        $currency_symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'JPY' => '¥',
        ];
        
        $symbol = isset( $currency_symbols[ $currency_code ] ) ? $currency_symbols[ $currency_code ] : $currency_code . ' ';
        
        return $symbol . number_format( floatval( $price ), 2 );
    }
}

// Helper function to generate proper dashboard URLs
function mobooking_get_dashboard_url( $page = '', $params = [] ) {
    $base_url = home_url( '/dashboard/' );
    
    if ( ! empty( $page ) ) {
        $base_url .= trailingslashit( $page );
    }
    
    if ( ! empty( $params ) ) {
        $base_url = add_query_arg( $params, $base_url );
    }
    
    return $base_url;
}

// Get current user and determine tenant context
$current_user_id = get_current_user_id();
$data_user_id = $current_user_id;

// Handle worker case - get the business owner they work for
if ( class_exists( 'MoBooking\Classes\Auth' ) && \MoBooking\Classes\Auth::is_user_worker( $current_user_id ) ) {
    $owner_id = \MoBooking\Classes\Auth::get_business_owner_id_for_worker( $current_user_id );
    if ( $owner_id ) {
        $data_user_id = $owner_id;
        error_log( "MoBooking: Worker {$current_user_id} accessing customer details for tenant {$owner_id}" );
    }
}

// Get customer ID from URL parameters
$customer_id = 0;
if ( isset( $_GET['customer_id'] ) ) {
    $customer_id = absint( $_GET['customer_id'] );
} elseif ( isset( $_GET['id'] ) ) {
    $customer_id = absint( $_GET['id'] );
}

if ( ! $customer_id ) {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Customer Details', 'mobooking' ); ?></h1>
        <div class="notice notice-error">
            <p><?php esc_html_e( 'Invalid customer ID provided.', 'mobooking' ); ?></p>
        </div>
        <p><a href="<?php echo esc_url( mobooking_get_dashboard_url( 'customers' ) ); ?>" class="button"><?php esc_html_e( 'Back to Customers', 'mobooking' ); ?></a></p>
    </div>
    <?php
    return;
}

// Get customer data with tenant context
global $wpdb;
$customers_table = \MoBooking\Classes\Database::get_table_name('customers');

try {
    // Query customer with tenant check to ensure user can only see their own customers
    $customer = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$customers_table} WHERE id = %d AND tenant_id = %d",
            $customer_id,
            $data_user_id
        )
    );
    
    if ( $wpdb->last_error ) {
        error_log( 'MoBooking: Database error getting customer: ' . $wpdb->last_error );
        throw new Exception( 'Database error occurred' );
    }
    
} catch ( Exception $e ) {
    error_log( 'MoBooking: Error getting customer data: ' . $e->getMessage() );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Customer Details', 'mobooking' ); ?></h1>
        <div class="notice notice-error">
            <p><?php esc_html_e( 'Error loading customer data. Please try again later.', 'mobooking' ); ?></p>
        </div>
        <p><a href="<?php echo esc_url( mobooking_get_dashboard_url( 'customers' ) ); ?>" class="button"><?php esc_html_e( 'Back to Customers', 'mobooking' ); ?></a></p>
    </div>
    <?php
    return;
}

if ( ! $customer ) {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Customer Details', 'mobooking' ); ?></h1>
        <div class="notice notice-error">
            <p><?php esc_html_e( 'Customer not found or you do not have permission to view this customer.', 'mobooking' ); ?></p>
        </div>
        <p><a href="<?php echo esc_url( mobooking_get_dashboard_url( 'customers' ) ); ?>" class="button"><?php esc_html_e( 'Back to Customers', 'mobooking' ); ?></a></p>
    </div>
    <?php
    return;
}

// Get booking overview statistics
$bookings_table = \MoBooking\Classes\Database::get_table_name('bookings');
$booking_overview = null;

if ( ! empty( $customer->email ) ) {
    try {
        $booking_overview = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    COUNT(*) as total_bookings,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings,
                    SUM(CASE WHEN status IN ('completed', 'confirmed') THEN total_price ELSE 0 END) as total_spent,
                    AVG(CASE WHEN status IN ('completed', 'confirmed') THEN total_price ELSE NULL END) as average_booking_value
                 FROM {$bookings_table}
                 WHERE customer_email = %s AND user_id = %d",
                $customer->email,
                $data_user_id
            )
        );
        
        if ( $wpdb->last_error ) {
            error_log( 'MoBooking: Database error getting booking overview: ' . $wpdb->last_error );
        }
    } catch ( Exception $e ) {
        error_log( 'MoBooking: Exception getting booking overview: ' . $e->getMessage() );
    }
}

// Default booking overview if query failed
if ( ! $booking_overview ) {
    $booking_overview = (object) [
        'total_bookings' => 0,
        'completed_bookings' => 0,
        'pending_bookings' => 0,
        'cancelled_bookings' => 0,
        'total_spent' => 0,
        'average_booking_value' => 0
    ];
}

// Get customer bookings
$items_table = \MoBooking\Classes\Database::get_table_name('booking_items');
$bookings = [];

if ( ! empty( $customer->email ) ) {
    try {
        $bookings = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT b.*, GROUP_CONCAT(i.service_name SEPARATOR ', ') as service_name
                 FROM {$bookings_table} b
                 LEFT JOIN {$items_table} i ON b.booking_id = i.booking_id
                 WHERE b.customer_email = %s AND b.user_id = %d
                 GROUP BY b.booking_id
                 ORDER BY b.booking_date DESC
                 LIMIT 50",
                $customer->email,
                $data_user_id
            ),
            ARRAY_A
        );
        
        if ( $wpdb->last_error ) {
            error_log( 'MoBooking: Database error getting customer bookings: ' . $wpdb->last_error );
            $bookings = [];
        }
    } catch ( Exception $e ) {
        error_log( 'MoBooking: Exception getting customer bookings: ' . $e->getMessage() );
        $bookings = [];
    }
}

?>

<div class="wrap mobooking-customer-details-page">
    <div class="page-header">
        <div class="page-header-content">
            <h1><?php echo esc_html( $customer->full_name ?? __( 'Customer Details', 'mobooking' ) ); ?></h1>
            <div class="page-header-actions">
                <a href="<?php echo esc_url( mobooking_get_dashboard_url( 'customers' ) ); ?>" class="button">
                    <?php esc_html_e( '← Back to Customers', 'mobooking' ); ?>
                </a>
            </div>
        </div>
    </div>

    <div class="customer-details-grid">
        <div class="customer-details-main">
            <!-- Core Customer Information -->
            <div class="customer-details-section">
                <h2><?php esc_html_e( 'Core Customer Information', 'mobooking' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Full Name', 'mobooking' ); ?></th>
                        <td><?php echo esc_html( $customer->full_name ?? __( 'N/A', 'mobooking' ) ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Email Address', 'mobooking' ); ?></th>
                        <td>
                            <?php if ( ! empty( $customer->email ) ) : ?>
                                <a href="mailto:<?php echo esc_attr( $customer->email ); ?>"><?php echo esc_html( $customer->email ); ?></a>
                            <?php else : ?>
                                <?php esc_html_e( 'N/A', 'mobooking' ); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Phone Number', 'mobooking' ); ?></th>
                        <td>
                            <?php if ( ! empty( $customer->phone_number ) ) : ?>
                                <a href="tel:<?php echo esc_attr( $customer->phone_number ); ?>"><?php echo esc_html( $customer->phone_number ); ?></a>
                            <?php else : ?>
                                <?php esc_html_e( 'N/A', 'mobooking' ); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Customer ID', 'mobooking' ); ?></th>
                        <td><?php echo esc_html( $customer->id ?? __( 'N/A', 'mobooking' ) ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Status', 'mobooking' ); ?></th>
                        <td>
                            <span class="status-badge status-<?php echo esc_attr( $customer->status ?? 'unknown' ); ?>">
                                <?php echo esc_html( ucfirst( $customer->status ?? __( 'Unknown', 'mobooking' ) ) ); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Date of Registration', 'mobooking' ); ?></th>
                        <td>
                            <?php 
                            if ( ! empty( $customer->created_at ) ) {
                                echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $customer->created_at ) ) );
                            } else {
                                esc_html_e( 'N/A', 'mobooking' );
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Address Information -->
            <div class="customer-details-section">
                <h2><?php esc_html_e( 'Address Information', 'mobooking' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Primary Address', 'mobooking' ); ?></th>
                        <td><?php echo esc_html( $customer->address_line_1 ?? __( 'N/A', 'mobooking' ) ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Secondary Address', 'mobooking' ); ?></th>
                        <td><?php echo esc_html( $customer->address_line_2 ?? __( 'N/A', 'mobooking' ) ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'City', 'mobooking' ); ?></th>
                        <td><?php echo esc_html( $customer->city ?? __( 'N/A', 'mobooking' ) ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'State', 'mobooking' ); ?></th>
                        <td><?php echo esc_html( $customer->state ?? __( 'N/A', 'mobooking' ) ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'ZIP Code', 'mobooking' ); ?></th>
                        <td><?php echo esc_html( $customer->zip_code ?? __( 'N/A', 'mobooking' ) ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Country', 'mobooking' ); ?></th>
                        <td><?php echo esc_html( $customer->country ?? __( 'N/A', 'mobooking' ) ); ?></td>
                    </tr>
                </table>
            </div>

            <!-- Booking Overview -->
            <div class="customer-details-section">
                <h2><?php esc_html_e( 'Booking Overview', 'mobooking' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Total Bookings', 'mobooking' ); ?></th>
                        <td><?php echo esc_html( intval( $booking_overview->total_bookings ) ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Completed Bookings', 'mobooking' ); ?></th>
                        <td><?php echo esc_html( intval( $booking_overview->completed_bookings ) ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Pending Bookings', 'mobooking' ); ?></th>
                        <td><?php echo esc_html( intval( $booking_overview->pending_bookings ) ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Cancelled Bookings', 'mobooking' ); ?></th>
                        <td><?php echo esc_html( intval( $booking_overview->cancelled_bookings ) ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Total Spent', 'mobooking' ); ?></th>
                        <td><?php echo esc_html( mobooking_format_price( $booking_overview->total_spent ) ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Average Booking Value', 'mobooking' ); ?></th>
                        <td><?php echo esc_html( mobooking_format_price( $booking_overview->average_booking_value ) ); ?></td>
                    </tr>
                </table>
            </div>

            <!-- Booking History -->
            <div class="customer-details-section">
                <h2><?php esc_html_e( 'Recent Booking History', 'mobooking' ); ?></h2>
                <?php if ( ! empty( $bookings ) ) : ?>
                    <div class="table-responsive">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Reference', 'mobooking' ); ?></th>
                                    <th><?php esc_html_e( 'Services', 'mobooking' ); ?></th>
                                    <th><?php esc_html_e( 'Date & Time', 'mobooking' ); ?></th>
                                    <th><?php esc_html_e( 'Status', 'mobooking' ); ?></th>
                                    <th><?php esc_html_e( 'Total', 'mobooking' ); ?></th>
                                    <th><?php esc_html_e( 'Actions', 'mobooking' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $bookings as $booking ) : ?>
                                    <tr>
                                        <td class="booking-ref">
                                            <strong><?php echo esc_html( $booking['booking_reference'] ?? $booking['booking_id'] ); ?></strong>
                                        </td>
                                        <td class="booking-services">
                                            <?php echo esc_html( $booking['service_name'] ?? __( 'N/A', 'mobooking' ) ); ?>
                                        </td>
                                        <td class="booking-datetime">
                                            <?php 
                                            if ( ! empty( $booking['booking_date'] ) ) {
                                                $date = date_i18n( get_option( 'date_format' ), strtotime( $booking['booking_date'] ) );
                                                $time = ! empty( $booking['booking_time'] ) ? esc_html( $booking['booking_time'] ) : '';
                                                echo $date;
                                                if ( $time ) {
                                                    echo '<br><small>' . $time . '</small>';
                                                }
                                            } else {
                                                esc_html_e( 'N/A', 'mobooking' );
                                            }
                                            ?>
                                        </td>
                                        <td class="booking-status">
                                            <span class="status-badge status-<?php echo esc_attr( $booking['status'] ?? 'unknown' ); ?>">
                                                <?php echo esc_html( ucfirst( $booking['status'] ?? __( 'Unknown', 'mobooking' ) ) ); ?>
                                            </span>
                                        </td>
                                        <td class="booking-total">
                                            <strong><?php echo esc_html( mobooking_format_price( $booking['total_price'] ?? 0 ) ); ?></strong>
                                        </td>
                                        <td class="booking-actions">
                                            <a href="<?php echo esc_url( mobooking_get_dashboard_url( 'bookings', [ 'action' => 'view', 'booking_id' => $booking['booking_id'] ] ) ); ?>" 
                                               class="button button-small">
                                                <?php esc_html_e( 'View', 'mobooking' ); ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ( count( $bookings ) >= 50 ) : ?>
                        <p class="more-bookings-notice">
                            <em><?php esc_html_e( 'Showing the most recent 50 bookings.', 'mobooking' ); ?></em>
                            <a href="<?php echo esc_url( mobooking_get_dashboard_url( 'bookings', [ 'customer_email' => $customer->email ] ) ); ?>">
                                <?php esc_html_e( 'View all bookings for this customer', 'mobooking' ); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                <?php else : ?>
                    <div class="no-bookings-message">
                        <p><?php esc_html_e( 'No bookings found for this customer.', 'mobooking' ); ?></p>
                        <a href="<?php echo esc_url( mobooking_get_dashboard_url( 'bookings', [ 'action' => 'create', 'customer_id' => $customer_id ] ) ); ?>" 
                           class="button button-primary">
                            <?php esc_html_e( 'Create First Booking', 'mobooking' ); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="customer-details-sidebar">
            <!-- Quick Actions -->
            <div class="customer-details-section">
                <h3><?php esc_html_e( 'Quick Actions', 'mobooking' ); ?></h3>
                <div class="action-buttons">
                    <?php if ( ! empty( $customer->email ) ) : ?>
                        <a href="mailto:<?php echo esc_attr( $customer->email ); ?>" class="button button-primary">
                            <span class="dashicons dashicons-email"></span>
                            <?php esc_html_e( 'Send Email', 'mobooking' ); ?>
                        </a>
                    <?php endif; ?>
                    
                    <?php if ( ! empty( $customer->phone_number ) ) : ?>
                        <a href="tel:<?php echo esc_attr( $customer->phone_number ); ?>" class="button">
                            <span class="dashicons dashicons-phone"></span>
                            <?php esc_html_e( 'Call Customer', 'mobooking' ); ?>
                        </a>
                    <?php endif; ?>
                    
                    <a href="<?php echo esc_url( mobooking_get_dashboard_url( 'bookings', [ 'action' => 'create', 'customer_id' => $customer_id ] ) ); ?>" 
                       class="button">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php esc_html_e( 'New Booking', 'mobooking' ); ?>
                    </a>
                    
                    <a href="<?php echo esc_url( mobooking_get_dashboard_url( 'customers', [ 'action' => 'edit', 'customer_id' => $customer_id ] ) ); ?>" 
                       class="button">
                        <span class="dashicons dashicons-edit"></span>
                        <?php esc_html_e( 'Edit Customer', 'mobooking' ); ?>
                    </a>
                </div>
            </div>

            <!-- Customer Summary Stats -->
            <div class="customer-details-section">
                <h3><?php esc_html_e( 'Customer Summary', 'mobooking' ); ?></h3>
                <div class="customer-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo esc_html( intval( $booking_overview->total_bookings ) ); ?></div>
                        <div class="stat-label"><?php esc_html_e( 'Total Bookings', 'mobooking' ); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo esc_html( mobooking_format_price( $booking_overview->total_spent ) ); ?></div>
                        <div class="stat-label"><?php esc_html_e( 'Total Spent', 'mobooking' ); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo esc_html( mobooking_format_price( $booking_overview->average_booking_value ) ); ?></div>
                        <div class="stat-label"><?php esc_html_e( 'Avg. Booking', 'mobooking' ); ?></div>
                    </div>
                </div>
            </div>

            <!-- Customer Notes -->
            <div class="customer-details-section">
                <h3><?php esc_html_e( 'Customer Notes', 'mobooking' ); ?></h3>
                <div class="customer-notes">
                    <?php if ( ! empty( $customer->notes ) ) : ?>
                        <p><?php echo esc_html( $customer->notes ); ?></p>
                    <?php else : ?>
                        <p class="no-notes"><?php esc_html_e( 'No notes available for this customer.', 'mobooking' ); ?></p>
                        <a href="<?php echo esc_url( mobooking_get_dashboard_url( 'customers', [ 'action' => 'edit', 'customer_id' => $customer_id ] ) ); ?>" 
                           class="button button-small">
                            <?php esc_html_e( 'Add Notes', 'mobooking' ); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.mobooking-customer-details-page {
    max-width: 1200px;
}

.page-header {
    background: #fff;
    border: 1px solid #ccd0d4;
    margin-bottom: 20px;
    padding: 20px;
}

.page-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.page-header h1 {
    margin: 0;
    font-size: 1.8em;
}

.customer-details-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
}

.customer-details-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    margin-bottom: 20px;
}

.customer-details-section h2,
.customer-details-section h3 {
    margin-top: 0;
    margin-bottom: 15px;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.form-table th {
    width: 200px;
    font-weight: 600;
    padding: 10px 0;
}

.form-table td {
    padding: 10px 0;
}

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-active {
    background-color: #d4edda;
    color: #155724;
}

.status-inactive {
    background-color: #f8d7da;
    color: #721c24;
}

.status-pending {
    background-color: #fff3cd;
    color: #856404;
}

.status-confirmed {
    background-color: #d4edda;
    color: #155724;
}

.status-completed {
    background-color: #d1ecf1;
    color: #0c5460;
}

.status-cancelled {
    background-color: #f8d7da;
    color: #721c24;
}

.table-responsive {
    overflow-x: auto;
}

.wp-list-table td {
    padding: 12px 8px;
    vertical-align: top;
}

.booking-ref strong {
    color: #0073aa;
}

.booking-datetime small {
    color: #666;
}

.booking-total strong {
    color: #d63638;
}

.action-buttons {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.action-buttons .button {
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.customer-stats {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.stat-item {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 4px;
}

.stat-number {
    font-size: 1.5em;
    font-weight: bold;
    color: #0073aa;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 0.9em;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.no-bookings-message {
    text-align: center;
    padding: 40px 20px;
    background: #f8f9fa;
    border-radius: 4px;
}

.more-bookings-notice {
    margin-top: 15px;
    padding: 10px;
    background: #f0f8ff;
    border-left: 4px solid #0073aa;
}

.no-notes {
    color: #666;
    font-style: italic;
    margin-bottom: 15px;
}

@media (max-width: 1024px) {
    .customer-details-grid {
        grid-template-columns: 1fr;
    }
    
    .page-header-content {
        flex-direction: column;
        align-items: flex-start;
    }
}

@media (max-width: 768px) {
    .customer-stats {
        flex-direction: row;
        flex-wrap: wrap;
    }
    
    .stat-item {
        flex: 1;
        min-width: 120px;
    }
    
    .action-buttons {
        flex-direction: row;
        flex-wrap: wrap;
    }
    
    .action-buttons .button {
        flex: 1;
        min-width: 140px;
    }
}
</style>