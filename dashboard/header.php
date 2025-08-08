<?php
/**
 * The header for the MoBooking Dashboard.
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<header class="flex items-center justify-between px-6 py-4 bg-white border-b-2 border-gray-200 dark:bg-gray-800 dark:border-gray-700">
    <div class="flex items-center">
        <button id="mobooking-mobile-nav-toggle" class="text-gray-500 focus:outline-none lg:hidden">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 6H20M4 12H20M4 18H11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
        </button>
        <div class="relative mx-4 lg:mx-0">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="w-5 h-5 text-gray-500" viewBox="0 0 24 24" fill="none">
                    <path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </span>
            <input class="w-32 pl-10 pr-4 text-indigo-600 border-gray-200 rounded-md sm:w-64 focus:border-indigo-600 focus:ring focus:ring-opacity-40 focus:ring-indigo-500" type="text" placeholder="Search">
        </div>
    </div>
    <div class="flex items-center">
        <div x-data="{ dropdownOpen: false }" class="relative">
            <button @click="dropdownOpen = !dropdownOpen" class="relative block w-8 h-8 overflow-hidden rounded-full shadow focus:outline-none">
                <?php $user = wp_get_current_user(); ?>
                <div class="flex items-center justify-center w-full h-full text-xl font-bold text-white bg-indigo-500">
                    <?php echo esc_html( strtoupper( substr( $user->display_name, 0, 1 ) ) ); ?>
                </div>
            </button>
            <div x-show="dropdownOpen" @click="dropdownOpen = false" class="fixed inset-0 z-10 w-full h-full" style="display: none;"></div>
            <div x-show="dropdownOpen" class="absolute right-0 z-10 w-48 mt-2 overflow-hidden bg-white rounded-md shadow-xl" style="display: none;">
                <a href="<?php echo esc_url(home_url('/dashboard/settings/')); ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-600 hover:text-white"><?php esc_html_e('Settings', 'mobooking'); ?></a>
                <a href="<?php echo wp_logout_url( home_url() ); ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-600 hover:text-white"><?php esc_html_e('Logout', 'mobooking'); ?></a>
            </div>
        </div>
    </div>
</header>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('dropdown', () => ({
            open: false,
            toggle() {
                this.open = ! this.open
            },
        }))
    })
</script>
