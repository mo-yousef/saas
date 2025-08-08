<?php
/**
 * The sidebar for the MoBooking Dashboard.
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$current_page = isset($GLOBALS['mobooking_current_dashboard_view']) ? $GLOBALS['mobooking_current_dashboard_view'] : 'overview';
$dashboard_base_url = home_url('/dashboard/');

$user_mobooking_role_display_name = '';
$current_user_id = get_current_user_id();

// Determine the display brand name
$display_brand_name = esc_html__('MoBooking', 'mobooking'); // Default brand name
if ($current_user_id > 0 && isset($GLOBALS['mobooking_settings_manager']) && class_exists('MoBooking\Classes\Settings')) {
    // Ensure get_setting method exists before calling
    if (method_exists($GLOBALS['mobooking_settings_manager'], 'get_setting')) {
        // Use the correct string key 'biz_name' as defined in Settings.php
        $business_name_setting = $GLOBALS['mobooking_settings_manager']->get_setting($current_user_id, 'biz_name', '');
        if (!empty(trim($business_name_setting))) {
            $display_brand_name = esc_html(trim($business_name_setting));
        }
    }
}


if ( $current_user_id > 0 ) {
    $user = get_userdata( $current_user_id );
    if ( $user ) {
        $mobooking_role_keys = [
            \MoBooking\Classes\Auth::ROLE_BUSINESS_OWNER,
            \MoBooking\Classes\Auth::ROLE_WORKER_STAFF,
        ];

        $all_roles = wp_roles(); // Get all role objects, which include names

        foreach ( $user->roles as $user_role_key ) {
            if ( in_array( $user_role_key, $mobooking_role_keys ) ) {
                if ( isset( $all_roles->role_names[$user_role_key] ) ) {
                    $user_mobooking_role_display_name = $all_roles->role_names[$user_role_key];
                    break; // Found the primary MoBooking role
                }
            }
        }
    }
}
?>
<aside :class="sidebarOpen ? 'translate-x-0 ease-out' : '-translate-x-full ease-in'" class="fixed inset-y-0 left-0 z-30 w-64 overflow-y-auto transition duration-300 transform bg-gray-900 lg:translate-x-0 lg:static lg:inset-0">
    <div class="flex items-center justify-center mt-8">
        <div class="flex items-center">
            <a href="<?php echo esc_url($dashboard_base_url); ?>" class="text-2xl font-semibold text-white"><?php echo $display_brand_name; ?></a>
        </div>
    </div>
    <nav class="mt-10">
        <div class="mt-10">
            <h3 class="mx-6 text-xs font-semibold text-gray-400 uppercase"><?php esc_html_e('Main', 'mobooking'); ?></h3>
            <ul>
                <?php if (current_user_can(\MoBooking\Classes\Auth::ACCESS_MOBOOKING_DASHBOARD)) : ?>
                <li><a class="<?php echo ($current_page === 'overview') ? 'bg-gray-800 ' : ''; ?>flex items-center px-6 py-2 mt-4 text-gray-100 hover:bg-gray-700" href="<?php echo esc_url($dashboard_base_url); ?>"><span class="mr-3"><?php echo mobooking_get_dashboard_menu_icon('overview'); ?></span><?php esc_html_e('Overview', 'mobooking'); ?></a></li>
                <?php endif; ?>

                <?php
                if ( current_user_can( \MoBooking\Classes\Auth::ROLE_WORKER_STAFF ) ) :
                ?>
                <li><a class="<?php echo ($current_page === 'my-assigned-bookings') ? 'bg-gray-800 ' : ''; ?>flex items-center px-6 py-2 mt-4 text-gray-100 hover:bg-gray-700" href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'my-assigned-bookings/'); ?>"><span class="mr-3"><?php echo mobooking_get_dashboard_menu_icon('bookings'); ?></span><?php esc_html_e('My Assigned Bookings', 'mobooking'); ?></a></li>
                <?php
                elseif (current_user_can(\MoBooking\Classes\Auth::CAP_VIEW_BOOKINGS) || current_user_can(\MoBooking\Classes\Auth::CAP_MANAGE_BOOKINGS)) :
                ?>
                <li><a class="<?php echo ($current_page === 'bookings') ? 'bg-gray-800 ' : ''; ?>flex items-center px-6 py-2 mt-4 text-gray-100 hover:bg-gray-700" href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'bookings/'); ?>"><span class="mr-3"><?php echo mobooking_get_dashboard_menu_icon('bookings'); ?></span><?php esc_html_e('Bookings', 'mobooking'); ?></a></li>
                <?php endif; ?>

                <?php if (current_user_can(\MoBooking\Classes\Auth::CAP_VIEW_CUSTOMERS) || current_user_can(\MoBooking\Classes\Auth::CAP_MANAGE_CUSTOMERS)) : ?>
                <li><a class="<?php echo ($current_page === 'customers') ? 'bg-gray-800 ' : ''; ?>flex items-center px-6 py-2 mt-4 text-gray-100 hover:bg-gray-700" href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'customers/'); ?>"><span class="mr-3"><?php echo mobooking_get_dashboard_menu_icon('clients'); ?></span><?php esc_html_e('Customers', 'mobooking'); ?></a></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="mt-10">
            <h3 class="mx-6 text-xs font-semibold text-gray-400 uppercase"><?php esc_html_e('Business', 'mobooking'); ?></h3>
            <ul>
                <?php if (current_user_can(\MoBooking\Classes\Auth::CAP_VIEW_SERVICES) || current_user_can(\MoBooking\Classes\Auth::CAP_MANAGE_SERVICES)) : ?>
                <li><a class="<?php echo ($current_page === 'services') ? 'bg-gray-800 ' : ''; ?>flex items-center px-6 py-2 mt-4 text-gray-100 hover:bg-gray-700" href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'services/'); ?>"><span class="mr-3"><?php echo mobooking_get_dashboard_menu_icon('services'); ?></span><?php esc_html_e('Services', 'mobooking'); ?></a></li>
                <?php endif; ?>

                <?php if (current_user_can(\MoBooking\Classes\Auth::CAP_MANAGE_AVAILABILITY)) : ?>
                <li><a class="<?php echo ($current_page === 'availability') ? 'bg-gray-800 ' : ''; ?>flex items-center px-6 py-2 mt-4 text-gray-100 hover:bg-gray-700" href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'availability/'); ?>"><span class="mr-3"><?php echo mobooking_get_dashboard_menu_icon('availability'); ?></span><?php esc_html_e('Availability', 'mobooking'); ?></a></li>
                <?php endif; ?>

                <?php if (current_user_can(\MoBooking\Classes\Auth::CAP_VIEW_DISCOUNTS) || current_user_can(\MoBooking\Classes\Auth::CAP_MANAGE_DISCOUNTS)) : ?>
                <li><a class="<?php echo ($current_page === 'discounts') ? 'bg-gray-800 ' : ''; ?>flex items-center px-6 py-2 mt-4 text-gray-100 hover:bg-gray-700" href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'discounts/'); ?>"><span class="mr-3"><?php echo mobooking_get_dashboard_menu_icon('discounts'); ?></span><?php esc_html_e('Discounts', 'mobooking'); ?></a></li>
                <?php endif; ?>

                <?php if (current_user_can(\MoBooking\Classes\Auth::CAP_VIEW_AREAS) || current_user_can(\MoBooking\Classes\Auth::CAP_MANAGE_AREAS)) : ?>
                <li><a class="<?php echo ($current_page === 'areas') ? 'bg-gray-800 ' : ''; ?>flex items-center px-6 py-2 mt-4 text-gray-100 hover:bg-gray-700" href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'areas/'); ?>"><span class="mr-3"><?php echo mobooking_get_dashboard_menu_icon('areas'); ?></span><?php esc_html_e('Service Areas', 'mobooking'); ?></a></li>
                <?php endif; ?>

                <?php if (current_user_can(\MoBooking\Classes\Auth::CAP_MANAGE_WORKERS)) : ?>
                <li><a class="<?php echo ($current_page === 'workers') ? 'bg-gray-800 ' : ''; ?>flex items-center px-6 py-2 mt-4 text-gray-100 hover:bg-gray-700" href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'workers/'); ?>"><span class="mr-3"><?php echo mobooking_get_dashboard_menu_icon('workers'); ?></span><?php esc_html_e('Workers', 'mobooking'); ?></a></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="mt-10">
            <h3 class="mx-6 text-xs font-semibold text-gray-400 uppercase"><?php esc_html_e('Settings', 'mobooking'); ?></h3>
            <ul>
                <?php if (current_user_can(\MoBooking\Classes\Auth::CAP_MANAGE_BOOKING_FORM)) : ?>
                <li><a class="<?php echo ($current_page === 'booking-form') ? 'bg-gray-800 ' : ''; ?>flex items-center px-6 py-2 mt-4 text-gray-100 hover:bg-gray-700" href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'booking-form/'); ?>"><span class="mr-3"><?php echo mobooking_get_dashboard_menu_icon('booking_form'); ?></span><?php esc_html_e('Booking Form', 'mobooking'); ?></a></li>
                <?php endif; ?>

                <?php if (current_user_can(\MoBooking\Classes\Auth::CAP_MANAGE_BUSINESS_SETTINGS)) : ?>
                <li><a class="<?php echo ($current_page === 'settings') ? 'bg-gray-800 ' : ''; ?>flex items-center px-6 py-2 mt-4 text-gray-100 hover:bg-gray-700" href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'settings/'); ?>"><span class="mr-3"><?php echo mobooking_get_dashboard_menu_icon('settings'); ?></span><?php esc_html_e('Settings', 'mobooking'); ?></a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
</aside>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('dashboard', () => ({
            sidebarOpen: false,
            openSidebar() {
                this.sidebarOpen = true
            },
            closeSidebar() {
                this.sidebarOpen = false
            },
        }))
    })
</script>
