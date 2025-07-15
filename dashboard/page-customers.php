<?php
/**
 * Template for displaying the Customers List page in the MoBooking Dashboard.
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

// Get current user and tenant ID
$current_user_id = get_current_user_id();
$tenant_id = \MoBooking\Classes\Auth::get_effective_tenant_id_for_user( $current_user_id );

// Prepare arguments for fetching customers
$page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
$per_page = 20;
$search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
$status_filter = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
$sort_by = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'full_name';
$sort_order = isset( $_GET['order'] ) ? strtoupper( sanitize_key( $_GET['order'] ) ) : 'ASC';

$args = [
    'page' => $page,
    'per_page' => $per_page,
    'search' => $search,
    'status' => $status_filter,
    'orderby' => $sort_by,
    'order' => $sort_order,
];

// Fetch customers data
$customers_manager = new \MoBooking\Classes\Customers();
$customers = $customers_manager->get_customers_by_tenant_id( $tenant_id, $args );
$total_customers = $customers_manager->get_customer_count_by_tenant_id( $tenant_id, $args );

?>

<div class="wrap mobooking-customers-page">
    <h1><?php esc_html_e( 'Customers', 'mobooking' ); ?></h1>

    <div id="mobooking-customers-feedback" class="notice" style="display:none;">
        <p></p>
    </div>

    <form method="get">
        <input type="hidden" name="page" value="mobooking-customers">
        <div class="mobooking-filters-bar">
            <div class="mobooking-filter-group">
                <label for="mobooking-customer-search"><?php esc_html_e( 'Search Customers:', 'mobooking' ); ?></label>
                <input type="text" id="mobooking-customer-search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Name, email, or phone', 'mobooking' ); ?>">
            </div>
            <div class="mobooking-filter-group">
                <label for="mobooking-customer-status-filter"><?php esc_html_e( 'Status:', 'mobooking' ); ?></label>
                <select id="mobooking-customer-status-filter" name="status">
                    <option value=""><?php esc_html_e( 'All Statuses', 'mobooking' ); ?></option>
                    <option value="active" <?php selected( $status_filter, 'active' ); ?>><?php esc_html_e( 'Active', 'mobooking' ); ?></option>
                    <option value="inactive" <?php selected( $status_filter, 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'mobooking' ); ?></option>
                    <option value="blacklisted" <?php selected( $status_filter, 'blacklisted' ); ?>><?php esc_html_e( 'Blacklisted', 'mobooking' ); ?></option>
                </select>
            </div>
            <div class="mobooking-filter-group">
                <button type="submit" class="button button-secondary">
                    <?php esc_html_e( 'Apply Filters', 'mobooking' ); ?>
                </button>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=mobooking-customers' ) ); ?>" class="button">
                    <?php esc_html_e( 'Reset', 'mobooking' ); ?>
                </a>
            </div>
        </div>
    </form>

    <table class="wp-list-table widefat fixed striped mobooking-customers-table">
        <thead>
            <tr>
                <?php
                $columns = [
                    'full_name' => __( 'Full Name', 'mobooking' ),
                    'email' => __( 'Email', 'mobooking' ),
                    'phone_number' => __( 'Phone Number', 'mobooking' ),
                    'total_bookings' => __( 'Total Bookings', 'mobooking' ),
                    'last_booking_date' => __( 'Last Booking Date', 'mobooking' ),
                    'status' => __( 'Status', 'mobooking' ),
                    'actions' => __( 'Actions', 'mobooking' ),
                ];

                foreach ( $columns as $key => $title ) {
                    $class = "manage-column column-{$key}";
                    if ( in_array( $key, [ 'full_name', 'email', 'total_bookings', 'last_booking_date', 'status' ] ) ) {
                        $order = ( $sort_by === $key && $sort_order === 'ASC' ) ? 'DESC' : 'ASC';
                        $url = add_query_arg( [ 'orderby' => $key, 'order' => $order ] );
                        echo "<th scope='col' class='{$class} sortable " . ( $sort_by === $key ? strtolower( $sort_order ) : '' ) . "'>
                                <a href='" . esc_url( $url ) . "'>
                                    <span>{$title}</span>
                                    <span class='sorting-indicator'></span>
                                </a>
                              </th>";
                    } else {
                        echo "<th scope='col' class='{$class}'>{$title}</th>";
                    }
                }
                ?>
            </tr>
        </thead>
        <tbody id="the-list">
            <?php if ( ! empty( $customers ) ) : ?>
                <?php foreach ( $customers as $customer ) : ?>
                    <tr id="customer-<?php echo $customer->id; ?>">
                        <td data-label="<?php esc_attr_e( 'Full Name', 'mobooking' ); ?>"><?php echo esc_html( $customer->full_name ); ?></td>
                        <td data-label="<?php esc_attr_e( 'Email', 'mobooking' ); ?>"><?php echo esc_html( $customer->email ); ?></td>
                        <td data-label="<?php esc_attr_e( 'Phone Number', 'mobooking' ); ?>"><?php echo esc_html( $customer->phone_number ); ?></td>
                        <td data-label="<?php esc_attr_e( 'Total Bookings', 'mobooking' ); ?>"><?php echo esc_html( $customer->total_bookings ); ?></td>
                        <td data-label="<?php esc_attr_e( 'Last Booking Date', 'mobooking' ); ?>"><?php echo esc_html( $customer->last_booking_date ? date_i18n( get_option( 'date_format' ), strtotime( $customer->last_booking_date ) ) : __( 'N/A', 'mobooking' ) ); ?></td>
                        <td data-label="<?php esc_attr_e( 'Status', 'mobooking' ); ?>">
                            <span class="booking-status status-<?php echo esc_attr( $customer->status ); ?>">
                                <?php echo esc_html( ucfirst( $customer->status ) ); ?>
                            </span>
                        </td>
                        <td data-label="<?php esc_attr_e( 'Actions', 'mobooking' ); ?>">
                            <a href="#" class="view-customer-details" data-customer-id="<?php echo $customer->id; ?>"><?php esc_html_e( 'View Details', 'mobooking' ); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr class="no-items">
                    <td class="colspanchange" colspan="7"><?php esc_html_e( 'No customers found.', 'mobooking' ); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="mobooking-pagination-container">
        <?php
        $total_pages = ceil( $total_customers / $per_page );
        if ( $total_pages > 1 ) {
            echo paginate_links( [
                'base' => add_query_arg( 'paged', '%#%' ),
                'format' => '',
                'current' => $page,
                'total' => $total_pages,
                'prev_text' => '&laquo; ' . __( 'Previous', 'mobooking' ),
                'next_text' => __( 'Next', 'mobooking' ) . ' &raquo;',
            ] );
        }
        ?>
    </div>

</div>
<style>
    .mobooking-filters-bar {
        display: flex;
        gap: 1rem;
        align-items: center;
        margin-bottom: 1.5rem;
        padding: 1rem;
        background-color: #f6f7f7;
        border: 1px solid #ccd0d4;
        border-radius: 4px;
    }
    .mobooking-filter-group {
        display: flex;
        flex-direction: column; /* Stack label and input */
    }
    .mobooking-filter-group label {
        margin-bottom: .25rem;
        font-weight: bold;
    }
    .mobooking-filter-group input[type="text"],
    .mobooking-filter-group select {
        min-width: 180px; /* Give inputs some base width */
    }
    .mobooking-pagination-container {
        margin-top: 1.5rem;
        display: flex;
        justify-content: center;
    }

    /* Styles for sortable columns - copied from WordPress core list tables */
    .wp-list-table th.sortable a span {
        float: right;
        display: block;
        width: 0;
        height: 0;
        border-left: 5px solid transparent;
        border-right: 5px solid transparent;
    }
    .wp-list-table th.sortable.asc a span.sorting-indicator {
        border-bottom: 5px solid #333;
    }
    .wp-list-table th.sortable.desc a span.sorting-indicator {
        border-top: 5px solid #333;
    }
    .wp-list-table th.sorted .sorting-indicator {
        /* Keep visible for sorted column */
    }
    .wp-list-table th.sortable a:hover span.sorting-indicator {
        /* Optional: change color on hover */
    }
</style>
