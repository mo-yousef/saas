<?php
/**
 * Template for displaying the Customers List page in the MoBooking Dashboard.
 *
 * @package MoBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
if (!function_exists('mobooking_get_feather_icon')) { // Check if function exists to avoid re-declaration if included elsewhere
    function mobooking_get_feather_icon($icon_name, $attrs = 'width="18" height="18"') {
        $svg = '';
        switch ($icon_name) {
            case 'user-check': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><polyline points="17 11 19 13 23 9"></polyline></svg>'; break;
            case 'user-minus': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="23" y1="11" x2="17" y2="11"></line></svg>'; break;
            case 'user-x': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="18" y1="8" x2="23" y2="13"></line><line x1="23" y1="8" x2="18" y2="13"></line></svg>'; break;
            default: $svg = '<!-- icon not found: '.esc_attr($icon_name).' -->'; break;
        }
        return $svg;
    }
}

if (!function_exists('mobooking_get_customer_status_badge_icon_svg')) { // Check if function exists
    function mobooking_get_customer_status_badge_icon_svg($status) {
        $attrs = 'class="feather"'; // CSS will handle size and margin
        $icon_name = '';
        switch ($status) {
            case 'active': $icon_name = 'user-check'; break;
            case 'inactive': $icon_name = 'user-minus'; break;
            case 'blacklisted': $icon_name = 'user-x'; break;
            default: return '';
        }
        return mobooking_get_feather_icon($icon_name, $attrs);
    }
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
$kpi_data = $customers_manager->get_kpi_data( $tenant_id );
?>

<div class="wrap mobooking-dashboard-wrap mobooking-customers-page-wrapper">
    <div class="mobooking-page-header">
        <h1 class="wp-heading-inline"><?php esc_html_e('Manage Customers', 'mobooking'); ?></h1>
        <button id="mobooking-add-customer-btn" class="page-title-action">
            <?php esc_html_e('Add New Customer', 'mobooking'); ?>
        </button>
    </div>

    <div id="mobooking-customers-feedback" class="notice" style="display:none;">
        <p></p>
    </div>

    <div class="dashboard-kpi-grid mobooking-overview-kpis">
        <div class="dashboard-kpi-card">
            <div class="kpi-header">
                <span class="kpi-title"><?php esc_html_e('Total Customers', 'mobooking'); ?></span>
                <div class="kpi-icon customers">👥</div>
            </div>
            <div class="kpi-value"><?php echo esc_html($kpi_data['total_customers']); ?></div>
        </div>

        <div class="dashboard-kpi-card">
            <div class="kpi-header">
                <span class="kpi-title"><?php esc_html_e('New This Month', 'mobooking'); ?></span>
                <div class="kpi-icon new">✨</div>
            </div>
            <div class="kpi-value"><?php echo esc_html($kpi_data['new_customers_month']); ?></div>
        </div>

        <div class="dashboard-kpi-card">
            <div class="kpi-header">
                <span class="kpi-title"><?php esc_html_e('Active Customers', 'mobooking'); ?></span>
                 <div class="kpi-icon active">✔️</div>
            </div>
            <div class="kpi-value"><?php echo esc_html($kpi_data['active_customers']); ?></div>
        </div>
    </div>

    <div class="mobooking-card mobooking-filters-wrapper">
        <div class="mobooking-card-header">
            <h3><?php esc_html_e('Filter Customers', 'mobooking'); ?></h3>
        </div>
        <div class="mobooking-card-content">
            <form id="mobooking-customers-filter-form" class="mobooking-filters-form" method="get">
                <input type="hidden" name="page" value="mobooking-customers">
                <div class="mobooking-filter-row">
                    <div class="mobooking-filter-item mobooking-filter-item-search">
                        <label for="mobooking-search-query"><?php esc_html_e('Search:', 'mobooking'); ?></label>
                        <input type="search" id="mobooking-search-query" name="s" class="regular-text" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e('Name, email, or phone', 'mobooking'); ?>">
                    </div>
                    <div class="mobooking-filter-item">
                        <label for="mobooking-customer-status-filter"><?php esc_html_e('Status:', 'mobooking'); ?></label>
                        <select id="mobooking-customer-status-filter" name="status" class="mobooking-filter-select">
                            <option value=""><?php esc_html_e('All Statuses', 'mobooking'); ?></option>
                            <option value="active" <?php selected($status_filter, 'active'); ?>><?php esc_html_e('Active', 'mobooking'); ?></option>
                            <option value="inactive" <?php selected($status_filter, 'inactive'); ?>><?php esc_html_e('Inactive', 'mobooking'); ?></option>
                            <option value="blacklisted" <?php selected($status_filter, 'blacklisted'); ?>><?php esc_html_e('Blacklisted', 'mobooking'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="mobooking-filter-actions">
                    <button type="submit" class="button button-secondary"><?php esc_html_e('Filter', 'mobooking'); ?></button>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=mobooking-customers' ) ); ?>" id="mobooking-clear-filters-btn" class="button"><?php esc_html_e('Clear Filters', 'mobooking'); ?></a>
                </div>
            </form>
        </div>
    </div>
    <div class="mobooking-table">
        <div class="mobooking-table-header">
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
                if ( in_array( $key, [ 'full_name', 'email', 'total_bookings', 'last_booking_date', 'status' ] ) ) {
                    $order = ( $sort_by === $key && $sort_order === 'ASC' ) ? 'DESC' : 'ASC';
                    $url = add_query_arg( [ 'orderby' => $key, 'order' => $order ] );
                    echo "<div class='mobooking-table-cell sortable " . ( $sort_by === $key ? strtolower( $sort_order ) : '' ) . "'>
                            <a href='" . esc_url( $url ) . "'>
                                <span>{$title}</span>
                                <span class='sorting-indicator'></span>
                            </a>
                          </div>";
                } else {
                    echo "<div class='mobooking-table-cell'>{$title}</div>";
                }
            }
            ?>
        </div>
        <div class="mobooking-table-body">
            <?php if ( ! empty( $customers ) ) : ?>
                <?php foreach ( $customers as $customer ) : ?>
                    <div class="mobooking-table-row" id="customer-<?php echo $customer->id; ?>">
                        <div class="mobooking-table-cell" data-label="<?php esc_attr_e( 'Full Name', 'mobooking' ); ?>"><?php echo esc_html( $customer->full_name ); ?></div>
                        <div class="mobooking-table-cell" data-label="<?php esc_attr_e( 'Email', 'mobooking' ); ?>"><?php echo esc_html( $customer->email ); ?></div>
                        <div class="mobooking-table-cell" data-label="<?php esc_attr_e( 'Phone Number', 'mobooking' ); ?>"><?php echo esc_html( $customer->phone_number ); ?></div>
                        <div class="mobooking-table-cell" data-label="<?php esc_attr_e( 'Total Bookings', 'mobooking' ); ?>"><?php echo esc_html( $customer->total_bookings ); ?></div>
                        <div class="mobooking-table-cell" data-label="<?php esc_attr_e( 'Last Booking Date', 'mobooking' ); ?>"><?php echo esc_html( $customer->last_booking_date ? date_i18n( get_option( 'date_format' ), strtotime( $customer->last_booking_date ) ) : __( 'N/A', 'mobooking' ) ); ?></div>
                        <div class="mobooking-table-cell" data-label="<?php esc_attr_e( 'Status', 'mobooking' ); ?>">
                            <span class="status-badge status-<?php echo esc_attr( $customer->status ); ?>">
                                <?php echo mobooking_get_customer_status_badge_icon_svg( $customer->status ); ?>
                                <span class="status-text"><?php echo esc_html( ucfirst( $customer->status ) ); ?></span>
                            </span>
                        </div>
                        <div class="mobooking-table-cell" data-label="<?php esc_attr_e( 'Actions', 'mobooking' ); ?>">
                            <a href="<?php echo esc_url( home_url( '/dashboard/customer-details/?customer_id=' . $customer->id ) ); ?>" class="button">
                                <?php esc_html_e( 'View Details', 'mobooking' ); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="mobooking-table-row no-items">
                    <div class="mobooking-table-cell" colspan="7"><?php esc_html_e( 'No customers found.', 'mobooking' ); ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div id="mobooking-customers-pagination-container" class="tablenav bottom">
        <div class="tablenav-pages">
            <span class="pagination-links">
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
            </span>
        </div>
    </div>

</div>
<style>
    .mobooking-table-responsive-wrapper {
        overflow-x: auto;
    }
    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25em 0.6em;
        font-size: 0.85em;
        font-weight: 500;
        border-radius: var(--radius, 0.5rem);
        border: 1px solid transparent;
        line-height: 1.2;
    }
    .status-badge .feather {
        width: 1em;
        height: 1em;
        margin-right: 0.4em;
        stroke-width: 2.5;
    }
    .status-badge.status-active {
        background-color: hsl(145, 63%, 95%);
        color: hsl(145, 63%, 22%);
        border-color: hsl(145, 63%, 72%);
    }
    .status-badge.status-active .feather { color: hsl(145, 63%, 22%); }

    .status-badge.status-inactive {
        background-color: hsl(var(--muted));
        color: hsl(var(--muted-foreground));
        border-color: hsl(var(--border));
    }
    .status-badge.status-inactive .feather { color: hsl(var(--muted-foreground)); }

    .status-badge.status-blacklisted {
        background-color: hsl(var(--destructive) / 0.1);
        color: hsl(var(--destructive));
        border-color: hsl(var(--destructive) / 0.3);
    }
    .status-badge.status-blacklisted .feather { color: hsl(var(--destructive)); }

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
