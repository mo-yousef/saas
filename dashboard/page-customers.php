<?php
/**
 * Template for displaying the Customers List page in the MoBooking Dashboard.
 *
 * @package MoBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Security check - ensure user has capability to view this page.
if ( ! current_user_can( \MoBooking\Classes\Auth::CAP_VIEW_CUSTOMERS ) && ! current_user_can( \MoBooking\Classes\Auth::CAP_MANAGE_CUSTOMERS ) ) {
    wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'mobooking' ) );
}
?>

<div class="wrap mobooking-customers-page">
    <h1><?php esc_html_e( 'Customers', 'mobooking' ); ?></h1>

    <div id="mobooking-customers-feedback" class="notice" style="display:none;">
        <p></p>
    </div>

    <div class="mobooking-filters-bar">
        <div class="mobooking-filter-group">
            <label for="mobooking-customer-search"><?php esc_html_e( 'Search Customers:', 'mobooking' ); ?></label>
            <input type="text" id="mobooking-customer-search" name="s" placeholder="<?php esc_attr_e( 'Name, email, or phone', 'mobooking' ); ?>">
        </div>
        <div class="mobooking-filter-group">
            <label for="mobooking-customer-status-filter"><?php esc_html_e( 'Status:', 'mobooking' ); ?></label>
            <select id="mobooking-customer-status-filter" name="status">
                <option value=""><?php esc_html_e( 'All Statuses', 'mobooking' ); ?></option>
                <option value="active"><?php esc_html_e( 'Active', 'mobooking' ); ?></option>
                <option value="inactive"><?php esc_html_e( 'Inactive', 'mobooking' ); ?></option>
                <option value="blacklisted"><?php esc_html_e( 'Blacklisted', 'mobooking' ); ?></option>
                <?php // Add other statuses as needed ?>
            </select>
        </div>
        <div class="mobooking-filter-group">
             <button type="button" id="mobooking-apply-customer-filters" class="button button-secondary">
                <?php esc_html_e( 'Apply Filters', 'mobooking' ); ?>
            </button>
            <button type="button" id="mobooking-reset-customer-filters" class="button">
                <?php esc_html_e( 'Reset', 'mobooking' ); ?>
            </button>
        </div>
    </div>

    <table class="wp-list-table widefat fixed striped mobooking-customers-table">
        <thead>
            <tr>
                <th scope="col" id="full_name" class="manage-column column-full_name sortable asc">
                    <a href="#" data-sort="full_name">
                        <span><?php esc_html_e( 'Full Name', 'mobooking' ); ?></span>
                        <span class="sorting-indicator"></span>
                    </a>
                </th>
                <th scope="col" id="email" class="manage-column column-email sortable asc">
                     <a href="#" data-sort="email">
                        <span><?php esc_html_e( 'Email', 'mobooking' ); ?></span>
                        <span class="sorting-indicator"></span>
                    </a>
                </th>
                <th scope="col" id="phone_number" class="manage-column column-phone_number">
                    <?php esc_html_e( 'Phone Number', 'mobooking' ); ?>
                </th>
                <th scope="col" id="total_bookings" class="manage-column column-total_bookings sortable asc">
                    <a href="#" data-sort="total_bookings">
                        <span><?php esc_html_e( 'Total Bookings', 'mobooking' ); ?></span>
                        <span class="sorting-indicator"></span>
                    </a>
                </th>
                <th scope="col" id="last_booking_date" class="manage-column column-last_booking_date sortable asc">
                     <a href="#" data-sort="last_booking_date">
                        <span><?php esc_html_e( 'Last Booking Date', 'mobooking' ); ?></span>
                        <span class="sorting-indicator"></span>
                    </a>
                </th>
                <th scope="col" id="status" class="manage-column column-status sortable asc">
                     <a href="#" data-sort="status">
                        <span><?php esc_html_e( 'Status', 'mobooking' ); ?></span>
                        <span class="sorting-indicator"></span>
                    </a>
                </th>
                <th scope="col" id="actions" class="manage-column column-actions"><?php esc_html_e( 'Actions', 'mobooking' ); ?></th>
            </tr>
        </thead>
        <tbody id="the-list">
            <tr class="no-items">
                <td class="colspanchange" colspan="7"><?php esc_html_e( 'Loading customers...', 'mobooking' ); ?></td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <th scope="col" class="manage-column column-full_name"><?php esc_html_e( 'Full Name', 'mobooking' ); ?></th>
                <th scope="col" class="manage-column column-email"><?php esc_html_e( 'Email', 'mobooking' ); ?></th>
                <th scope="col" class="manage-column column-phone_number"><?php esc_html_e( 'Phone Number', 'mobooking' ); ?></th>
                <th scope="col" class="manage-column column-total_bookings"><?php esc_html_e( 'Total Bookings', 'mobooking' ); ?></th>
                <th scope="col" class="manage-column column-last_booking_date"><?php esc_html_e( 'Last Booking Date', 'mobooking' ); ?></th>
                <th scope="col" class="manage-column column-status"><?php esc_html_e( 'Status', 'mobooking' ); ?></th>
                <th scope="col" class="manage-column column-actions"><?php esc_html_e( 'Actions', 'mobooking' ); ?></th>
            </tr>
        </tfoot>
    </table>

    <div class="mobooking-pagination-container">
        <ul class="mobooking-pagination">
            <?php // Pagination links will be rendered here by JavaScript ?>
        </ul>
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
<?php
// The corresponding JavaScript file (dashboard-customers.js) will handle:
// 1. Fetching data via AJAX.
// 2. Populating the table rows.
// 3. Handling search, filter, sort, and pagination interactions.
// 4. Displaying feedback messages.
?>
