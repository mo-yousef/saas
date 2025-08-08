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

<div>
    <h3 class="text-3xl font-medium text-gray-700 dark:text-gray-200">Customers</h3>
    <div class="mt-4">
        <div class="flex flex-wrap -mx-6">
            <div class="w-full px-6 sm:w-1/2 xl:w-1/3">
                <div class="flex items-center px-5 py-6 bg-white rounded-md shadow-sm dark:bg-gray-800">
                    <div class="p-3 bg-indigo-600 bg-opacity-75 rounded-full">
                        <svg class="w-8 h-8 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.653-.122-1.28-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.653.122-1.28.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div class="mx-5">
                        <h4 class="text-2xl font-semibold text-gray-700 dark:text-gray-200"><?php echo esc_html($kpi_data['total_customers']); ?></h4>
                        <div class="text-gray-500 dark:text-gray-400">Total Customers</div>
                    </div>
                </div>
            </div>
            <div class="w-full px-6 mt-6 sm:w-1/2 xl:w-1/3 sm:mt-0">
                <div class="flex items-center px-5 py-6 bg-white rounded-md shadow-sm dark:bg-gray-800">
                    <div class="p-3 bg-orange-600 bg-opacity-75 rounded-full">
                        <svg class="w-8 h-8 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v.01" />
                        </svg>
                    </div>
                    <div class="mx-5">
                        <h4 class="text-2xl font-semibold text-gray-700 dark:text-gray-200"><?php echo esc_html($kpi_data['new_customers_month']); ?></h4>
                        <div class="text-gray-500 dark:text-gray-400">New This Month</div>
                    </div>
                </div>
            </div>
            <div class="w-full px-6 mt-6 sm:w-1/2 xl:w-1/3 xl:mt-0">
                <div class="flex items-center px-5 py-6 bg-white rounded-md shadow-sm dark:bg-gray-800">
                    <div class="p-3 bg-pink-600 bg-opacity-75 rounded-full">
                        <svg class="w-8 h-8 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="mx-5">
                        <h4 class="text-2xl font-semibold text-gray-700 dark:text-gray-200"><?php echo esc_html($kpi_data['active_customers']); ?></h4>
                        <div class="text-gray-500 dark:text-gray-400">Active Customers</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="flex flex-col mt-8">
        <div class="-my-2 py-2 overflow-x-auto sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
            <div class="align-middle inline-block min-w-full shadow overflow-hidden sm:rounded-lg border-b border-gray-200 dark:border-gray-700">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 dark:bg-gray-700 dark:border-gray-700 text-left text-xs leading-4 font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Full Name</th>
                            <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 dark:bg-gray-700 dark:border-gray-700 text-left text-xs leading-4 font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 dark:bg-gray-700 dark:border-gray-700 text-left text-xs leading-4 font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Phone Number</th>
                            <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 dark:bg-gray-700 dark:border-gray-700 text-left text-xs leading-4 font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Bookings</th>
                            <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 dark:bg-gray-700 dark:border-gray-700 text-left text-xs leading-4 font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Last Booking Date</th>
                            <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 dark:bg-gray-700 dark:border-gray-700 text-left text-xs leading-4 font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 dark:bg-gray-700 dark:border-gray-700"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800">
                        <?php if ( ! empty( $customers ) ) : ?>
                            <?php foreach ( $customers as $customer ) : ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 dark:border-gray-700">
                                        <div class="text-sm leading-5 text-gray-900 dark:text-gray-200"><?php echo esc_html( $customer->full_name ); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 dark:border-gray-700">
                                        <div class="text-sm leading-5 text-gray-900 dark:text-gray-200"><?php echo esc_html( $customer->email ); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 dark:border-gray-700">
                                        <div class="text-sm leading-5 text-gray-900 dark:text-gray-200"><?php echo esc_html( $customer->phone_number ); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 dark:border-gray-700">
                                        <div class="text-sm leading-5 text-gray-900 dark:text-gray-200"><?php echo esc_html( $customer->total_bookings ); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 dark:border-gray-700">
                                        <div class="text-sm leading-5 text-gray-900 dark:text-gray-200"><?php echo esc_html( $customer->last_booking_date ? date_i18n( get_option( 'date_format' ), strtotime( $customer->last_booking_date ) ) : __( 'N/A', 'mobooking' ) ); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 dark:border-gray-700">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $customer->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>"><?php echo esc_html( ucfirst( $customer->status ) ); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap text-right border-b border-gray-200 dark:border-gray-700 text-sm leading-5 font-medium">
                                        <a href="<?php echo esc_url( home_url( '/dashboard/customer-details/?customer_id=' . $customer->id ) ); ?>" class="text-indigo-600 hover:text-indigo-900 dark:hover:text-indigo-400">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-gray-500 dark:text-gray-400">No customers found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
