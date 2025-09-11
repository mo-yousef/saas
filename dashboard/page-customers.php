<?php
/**
 * Dashboard Page: Customers
 * - Refactored to use AJAX for filtering and pagination, similar to the Bookings page.
 *
 * @package NORDBOOKING
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Permissions check
if ( ! current_user_can( \NORDBOOKING\Classes\Auth::CAP_MANAGE_CUSTOMERS ) ) {
    wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'NORDBOOKING' ) );
}

// Instantiate managers and get initial data
$customers_manager = new \NORDBOOKING\Classes\Customers();
$current_user_id = get_current_user_id();
$tenant_id = \NORDBOOKING\Classes\Auth::get_effective_tenant_id_for_user($current_user_id);

// Initial data for KPI cards
$kpi_data = $customers_manager->get_kpi_data($tenant_id);
$settings_manager = new \NORDBOOKING\Classes\Settings();
$currency_code = $settings_manager->get_setting($tenant_id, 'biz_currency_code', 'USD');
$currency_symbol = \NORDBOOKING\Classes\Utils::get_currency_symbol($currency_code);

// Initial load of customers
$initial_customers_args = [
    'page' => 1,
    'per_page' => 20,
];
$initial_customers_result = $customers_manager->get_customers_by_tenant_id($tenant_id, $initial_customers_args);
$initial_total_count = $customers_manager->get_customer_count_by_tenant_id($tenant_id, $initial_customers_args);


// Customer statuses for the filter dropdown
$customer_statuses = [
    '' => __('All Statuses', 'NORDBOOKING'),
    'active' => __('Active', 'NORDBOOKING'),
    'inactive' => __('Inactive', 'NORDBOOKING'),
    'lead' => __('Lead', 'NORDBOOKING'),
];

?>
<div class="wrap nordbooking-dashboard-wrap NORDBOOKING-customers-page-wrapper">

    <div class="nordbooking-page-header">
        <div class="nordbooking-page-header-heading">
            <span class="nordbooking-page-header-icon">
                <?php echo nordbooking_get_dashboard_menu_icon('clients'); ?>
            </span>
            <h1 class="wp-heading-inline"><?php esc_html_e('Manage Customers', 'NORDBOOKING'); ?></h1>
        </div>
        <button id="nordbooking-add-customer-btn" class="btn btn-primary" style="display: none;">
            <?php esc_html_e('Add New Customer', 'NORDBOOKING'); ?>
        </button>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid">
        <div class="nordbooking-card">
            <div class="nordbooking-card-header">
                <div class="nordbooking-card-title-group">
                    <span class="nordbooking-card-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg></span>
                    <h3 class="nordbooking-card-title"><?php esc_html_e('Total Customers', 'NORDBOOKING'); ?></h3>
                </div>
            </div>
            <div class="nordbooking-card-content">
                <div class="card-content-value text-2xl font-bold"><?php echo esc_html($kpi_data['total_customers']); ?></div>
            </div>
        </div>
        <div class="nordbooking-card">
            <div class="nordbooking-card-header">
                <div class="nordbooking-card-title-group">
                    <span class="nordbooking-card-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L14.39 8.36L21 9.27L16 14.14L17.21 21.02L12 17.77L6.79 21.02L8 14.14L3 9.27L9.61 8.36L12 2z"></path></svg></span>
                    <h3 class="nordbooking-card-title"><?php esc_html_e('New This Month', 'NORDBOOKING'); ?></h3>
                </div>
            </div>
            <div class="nordbooking-card-content">
                <div class="card-content-value text-2xl font-bold"><?php echo esc_html($kpi_data['new_customers_month']); ?></div>
            </div>
        </div>
        <div class="nordbooking-card">
            <div class="nordbooking-card-header">
                <div class="nordbooking-card-title-group">
                    <span class="nordbooking-card-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span>
                    <h3 class="nordbooking-card-title"><?php esc_html_e('Active Customers', 'NORDBOOKING'); ?></h3>
                </div>
            </div>
            <div class="nordbooking-card-content">
                <div class="card-content-value text-2xl font-bold"><?php echo esc_html($kpi_data['active_customers']); ?></div>
            </div>
        </div>
        <div class="nordbooking-card">
            <div class="nordbooking-card-header">
                <div class="nordbooking-card-title-group">
                    <span class="nordbooking-card-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg></span>
                    <h3 class="nordbooking-card-title"><?php esc_html_e('Avg. Order Value', 'NORDBOOKING'); ?></h3>
                </div>
            </div>
            <div class="nordbooking-card-content">
                <div class="card-content-value text-2xl font-bold"><?php echo esc_html($currency_symbol . number_format($kpi_data['avg_order_value'] ?? 0, 2)); ?></div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="nordbooking-card nordbooking-filters-wrapper">
        <div class="nordbooking-card-content">
            <form id="NORDBOOKING-customers-filter-form" class="nordbooking-filters-form">
                <div class="nordbooking-filters-main">
                    <div class="nordbooking-filter-item nordbooking-filter-item-search">
                        <label for="NORDBOOKING-search-query"><?php esc_html_e('Search', 'NORDBOOKING'); ?></label>
                        <input type="search" id="NORDBOOKING-search-query" name="search_query" class="regular-text" placeholder="<?php esc_attr_e('Name, Email, Phone', 'NORDBOOKING'); ?>">
                    </div>
                    <div class="nordbooking-filter-item">
                        <label for="NORDBOOKING-status-filter"><?php esc_html_e('Status', 'NORDBOOKING'); ?></label>
                        <select id="NORDBOOKING-status-filter" name="status_filter" class="nordbooking-filter-select">
                            <?php foreach ($customer_statuses as $value => $label) : ?>
                                <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="nordbooking-filter-actions">
                        <button type="button" id="NORDBOOKING-clear-filters-btn" class="btn btn-outline"><?php echo nordbooking_get_feather_icon('x'); ?> <span class="btn-text"><?php esc_html_e('Clear', 'NORDBOOKING'); ?></span></button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Customers List Container -->
    <div id="NORDBOOKING-customers-list-container" class="nordbooking-list-table-wrapper">
        <!-- Initial content is loaded via PHP and then updated via AJAX -->
        <?php
        if (!empty($initial_customers_result) && !is_wp_error($initial_customers_result)) {
            // This part mimics the structure our JS will create
            echo '<div class="nordbooking-table-responsive-wrapper">';
            echo '<table class="nordbooking-table">';
            echo '<thead><tr>';
            echo '<th>' . esc_html__('Customer', 'NORDBOOKING') . '</th>';
            echo '<th>' . esc_html__('Contact', 'NORDBOOKING') . '</th>';
            echo '<th>' . esc_html__('Bookings', 'NORDBOOKING') . '</th>';
            echo '<th>' . esc_html__('Last Booking', 'NORDBOOKING') . '</th>';
            echo '<th>' . esc_html__('Status', 'NORDBOOKING') . '</th>';
            echo '<th>' . esc_html__('Actions', 'NORDBOOKING') . '</th>';
            echo '</tr></thead>';
            echo '<tbody>';

            foreach ($initial_customers_result as $customer) {
                $status_val = $customer->status ?? 'active';
                $status_display = ucfirst($status_val);
                $status_icon_html = nordbooking_get_status_badge_icon_svg($status_val);
                $details_page_url = home_url('/dashboard/customer-details/?customer_id=' . $customer->id);
                $last_booking_date = !empty($customer->last_booking_date) ? date_i18n(get_option('date_format'), strtotime($customer->last_booking_date)) : __('N/A', 'NORDBOOKING');

                echo '<tr data-customer-id="' . esc_attr($customer->id) . '">';
                echo '<td data-label="' . esc_attr__('Customer', 'NORDBOOKING') . '"><strong>' . esc_html($customer->full_name) . '</strong></td>';
                echo '<td data-label="' . esc_attr__('Contact', 'NORDBOOKING') . '">' . esc_html($customer->email) . '<br><small>' . esc_html($customer->phone_number ?? '') . '</small></td>';
                echo '<td data-label="' . esc_attr__('Bookings', 'NORDBOOKING') . '">' . esc_html($customer->total_bookings ?? 0) . '</td>';
                echo '<td data-label="' . esc_attr__('Last Booking', 'NORDBOOKING') . '">' . esc_html($last_booking_date) . '</td>';
                echo '<td data-label="' . esc_attr__('Status', 'NORDBOOKING') . '"><span class="status-badge status-' . esc_attr($status_val) . '">' . $status_icon_html . '<span class="status-text">' . esc_html($status_display) . '</span></span></td>';
                echo '<td data-label="' . esc_attr__('Actions', 'NORDBOOKING') . '" class="nordbooking-table-actions">';
                echo '<a href="' . esc_url($details_page_url) . '" class="btn btn-outline btn-sm">' . __('View Details', 'NORDBOOKING') . '</a>';
                echo '</td></tr>';
            }

            echo '</tbody></table>';
            echo '</div>';
        } else {
            echo '<p>' . __('No customers found.', 'NORDBOOKING') . '</p>';
        }
        ?>
    </div>

    <!-- Pagination Container -->
    <div id="NORDBOOKING-customers-pagination-container" class="tablenav bottom">
        <div class="tablenav-pages">
            <span class="pagination-links">
                <?php
                if ($initial_total_count > 0) {
                    $total_pages = ceil($initial_total_count / $initial_customers_args['per_page']);
                    if ($total_pages > 1) {
                        for ($i = 1; $i <= $total_pages; $i++) {
                            $active_class = ($i == 1) ? 'current' : '';
                            echo '<a href="#" class="page-numbers ' . $active_class . '" data-page="' . $i . '">' . $i . '</a> ';
                        }
                    }
                }
                ?>
            </span>
        </div>
    </div>

</div>

<!-- Underscore.js Template for a Customer Row -->
<script type="text/template" id="NORDBOOKING-customer-item-template">
    <tr data-customer-id="<%= id %>">
        <td data-label="<?php esc_attr_e('Customer', 'NORDBOOKING'); ?>">
            <strong><%= full_name %></strong>
        </td>
        <td data-label="<?php esc_attr_e('Contact', 'NORDBOOKING'); ?>">
            <%= email %><br>
            <small><%= phone_number %></small>
        </td>
        <td data-label="<?php esc_attr_e('Bookings', 'NORDBOOKING'); ?>">
            <%= total_bookings %>
        </td>
        <td data-label="<?php esc_attr_e('Last Booking', 'NORDBOOKING'); ?>">
            <%= last_booking_date_formatted %>
        </td>
        <td data-label="<?php esc_attr_e('Status', 'NORDBOOKING'); ?>">
            <span class="status-badge status-<%= status %>">
                <%= status_icon_html %>
                <span class="status-text"><%= status_display %></span>
            </span>
        </td>
        <td data-label="<?php esc_attr_e('Actions', 'NORDBOOKING'); ?>" class="nordbooking-table-actions">
            <a href="<%= details_page_url %>" class="btn btn-outline btn-sm"><?php esc_html_e('View Details', 'NORDBOOKING'); ?></a>
        </td>
    </tr>
</script>