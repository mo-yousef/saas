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

<!-- ======== main-content start ======== -->
<section class="p-4 md:p-6 2xl:p-10">
    <!-- Breadcrumb Start -->
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-title-md2 font-semibold text-black dark:text-white">
            Customers
        </h2>
        <nav>
            <ol class="flex items-center gap-2">
                <li><a href="<?php echo esc_url(home_url('/dashboard/')); ?>">Dashboard /</a></li>
                <li class="text-primary">Customers</li>
            </ol>
        </nav>
    </div>
    <!-- Breadcrumb End -->

    <!-- ====== Stats Grid Start -->
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 md:gap-6 xl:grid-cols-3 2xl:gap-7.5">
        <!-- Card Item -->
        <div class="rounded-sm border border-stroke bg-white py-6 px-7.5 shadow-default dark:border-strokedark dark:bg-boxdark">
            <div class="flex h-11.5 w-11.5 items-center justify-center rounded-full bg-meta-2 dark:bg-meta-4">
                <svg class="w-8 h-8 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.653-.122-1.28-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.653.122-1.28.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <div class="mt-4 flex items-end justify-between">
                <div>
                    <h4 class="text-title-md font-bold text-black dark:text-white"><?php echo esc_html($kpi_data['total_customers']); ?></h4>
                    <span class="text-sm font-medium">Total Customers</span>
                </div>
            </div>
        </div>

        <!-- Card Item -->
        <div class="rounded-sm border border-stroke bg-white py-6 px-7.5 shadow-default dark:border-strokedark dark:bg-boxdark">
            <div class="flex h-11.5 w-11.5 items-center justify-center rounded-full bg-meta-2 dark:bg-meta-4">
                <svg class="w-8 h-8 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v.01" />
                </svg>
            </div>
            <div class="mt-4 flex items-end justify-between">
                <div>
                    <h4 class="text-title-md font-bold text-black dark:text-white"><?php echo esc_html($kpi_data['new_customers_month']); ?></h4>
                    <span class="text-sm font-medium">New This Month</span>
                </div>
            </div>
        </div>

        <!-- Card Item -->
        <div class="rounded-sm border border-stroke bg-white py-6 px-7.5 shadow-default dark:border-strokedark dark:bg-boxdark">
            <div class="flex h-11.5 w-11.5 items-center justify-center rounded-full bg-meta-2 dark:bg-meta-4">
                 <svg class="w-8 h-8 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="mt-4 flex items-end justify-between">
                <div>
                    <h4 class="text-title-md font-bold text-black dark:text-white"><?php echo esc_html($kpi_data['active_customers']); ?></h4>
                    <span class="text-sm font-medium">Active Customers</span>
                </div>
            </div>
        </div>
    </div>
    <!-- ====== Stats Grid End -->

    <!-- ====== Table Section Start -->
    <div class="mt-8 flex flex-col">
        <div class="rounded-sm border border-stroke bg-white px-5 pt-6 pb-2.5 shadow-default dark:border-strokedark dark:bg-boxdark sm:px-7.5 xl:pb-1">
            <div class="max-w-full overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-gray-2 text-left dark:bg-meta-4">
                            <th class="min-w-[220px] py-4 px-4 font-medium text-black dark:text-white">Full Name</th>
                            <th class="min-w-[150px] py-4 px-4 font-medium text-black dark:text-white">Email</th>
                            <th class="min-w-[150px] py-4 px-4 font-medium text-black dark:text-white">Phone Number</th>
                            <th class="min-w-[120px] py-4 px-4 font-medium text-black dark:text-white">Total Bookings</th>
                            <th class="min-w-[150px] py-4 px-4 font-medium text-black dark:text-white">Last Booking</th>
                            <th class="py-4 px-4 font-medium text-black dark:text-white">Status</th>
                            <th class="py-4 px-4 font-medium text-black dark:text-white">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $customers ) ) : ?>
                            <?php foreach ( $customers as $customer ) : ?>
                                <?php
                                $status_val = $customer->status;
                                $status_classes = '';
                                switch ($status_val) {
                                    case 'active': $status_classes = 'bg-success text-white'; break;
                                    case 'inactive': $status_classes = 'bg-warning text-white'; break;
                                    case 'blacklisted': $status_classes = 'bg-danger text-white'; break;
                                    default: $status_classes = 'bg-gray-400 text-white'; break;
                                }
                                ?>
                                <tr>
                                    <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark">
                                        <p class="font-medium text-black dark:text-white"><?php echo esc_html( $customer->full_name ); ?></p>
                                    </td>
                                    <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark">
                                        <p class="text-black dark:text-white"><?php echo esc_html( $customer->email ); ?></p>
                                    </td>
                                    <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark">
                                        <p class="text-black dark:text-white"><?php echo esc_html( $customer->phone_number ); ?></p>
                                    </td>
                                    <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark">
                                        <p class="text-black dark:text-white"><?php echo esc_html( $customer->total_bookings ); ?></p>
                                    </td>
                                    <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark">
                                        <p class="text-black dark:text-white"><?php echo esc_html( $customer->last_booking_date ? date_i18n( get_option( 'date_format' ), strtotime( $customer->last_booking_date ) ) : __( 'N/A', 'mobooking' ) ); ?></p>
                                    </td>
                                    <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark">
                                        <p class="inline-flex rounded-full bg-opacity-10 py-1 px-3 text-sm font-medium <?php echo $status_classes; ?>">
                                            <?php echo esc_html( ucfirst( $customer->status ) ); ?>
                                        </p>
                                    </td>
                                    <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark">
                                        <div class="flex items-center space-x-3.5">
                                            <a href="<?php echo esc_url( home_url( '/dashboard/customer-details/?customer_id=' . $customer->id ) ); ?>" class="hover:text-primary">
                                                <svg class="fill-current" width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8.99981 14.8219C3.43106 14.8219 0.674805 9.50624 0.562305 9.28124C0.47793 9.11249 0.47793 8.88749 0.562305 8.71874C0.674805 8.49374 3.43106 3.17812 8.99981 3.17812C14.5686 3.17812 17.3248 8.49374 17.4373 8.71874C17.5217 8.88749 17.5217 9.11249 17.4373 9.28124C17.3248 9.50624 14.5686 14.8219 8.99981 14.8219ZM1.85605 8.99999C2.4748 10.0406 4.89356 13.5 8.99981 13.5C13.1061 13.5 15.5248 10.0406 16.1436 8.99999C15.5248 7.95936 13.1061 4.5 8.99981 4.5C4.89356 4.5 2.4748 7.95936 1.85605 8.99999Z" fill=""></path><path d="M9 11.25C7.75736 11.25 6.75 10.2426 6.75 9C6.75 7.75736 7.75736 6.75 9 6.75C10.2426 6.75 11.25 7.75736 11.25 9C11.25 10.2426 10.2426 11.25 9 11.25ZM9 7.875C8.30659 7.875 7.875 8.30659 7.875 9C7.875 9.69341 8.30659 10.125 9 10.125C9.69341 10.125 10.125 9.69341 10.125 9C10.125 8.30659 9.69341 7.875 9 7.875Z" fill=""></path></svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="7" class="text-center py-10">
                                    <p class="text-lg text-gray-400">No customers found.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
<!-- ======== main-content end ======== -->
