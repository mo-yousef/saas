<?php
/**
 * MoBooking Dashboard Page: Availability Management
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Capability check for managing availability
if ( ! current_user_can( \MoBooking\Classes\Auth::CAP_MANAGE_AVAILABILITY ) ) {
    wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'mobooking' ) );
}
?>
<div>
    <h3 class="text-3xl font-medium text-gray-700 dark:text-gray-200">Manage Availability</h3>
    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Define your regular working hours and set specific dates for overrides or days off.</p>

    <div id="mobooking-floating-alert" class="fixed top-5 right-5 z-50"></div>

    <div class="mt-8">
        <div class="p-6 bg-white rounded-md shadow-md dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-700 capitalize dark:text-white">Recurring Weekly Schedule</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Set your standard availability for each day of the week. These are your default hours unless overridden by a specific date setting below.</p>

            <div id="recurring-schedule-container" class="mt-6">
                <!-- JS will populate this with the schedule editor -->
                <p class="text-gray-500 dark:text-gray-400">Loading schedule editor...</p>
            </div>

            <div class="flex justify-end mt-6">
                <button type="button" id="mobooking-save-recurring-schedule-btn" class="px-4 py-2 font-medium tracking-wide text-white capitalize transition-colors duration-200 transform bg-indigo-600 rounded-md hover:bg-indigo-500 focus:outline-none focus:bg-indigo-500">
                    <?php esc_html_e('Save Weekly Schedule', 'mobooking'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal for Adding/Editing Recurring Slot -->
    <div id="mobooking-recurring-slot-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black bg-opacity-50">
        <div class="w-full max-w-lg p-6 bg-white rounded-md shadow-lg dark:bg-gray-800">
            <h3 id="recurring-slot-modal-title" class="text-lg font-semibold text-gray-700 capitalize dark:text-white">Add Recurring Slot</h3>
            <div id="mobooking-recurring-slot-modal-error" class="hidden p-4 mt-4 text-sm text-red-700 bg-red-100 rounded-lg dark:bg-red-200 dark:text-red-800"></div>
            <form id="mobooking-recurring-slot-form" class="mt-4 space-y-6">
                <input type="hidden" id="recurring-slot-id" name="slot_id">
                <div>
                    <label for="recurring-day-of-week" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Day of Week <span class="text-red-500">*</span></label>
                    <select id="recurring-day-of-week" name="day_of_week" required class="block w-full px-3 py-2 mt-1 text-gray-900 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                        <option value="1">Monday</option>
                        <option value="2">Tuesday</option>
                        <option value="3">Wednesday</option>
                        <option value="4">Thursday</option>
                        <option value="5">Friday</option>
                        <option value="6">Saturday</option>
                        <option value="0">Sunday</option>
                    </select>
                </div>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="recurring-start-time" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Start Time <span class="text-red-500">*</span></label>
                        <input type="time" id="recurring-start-time" name="start_time" required class="block w-full px-3 py-2 mt-1 text-gray-900 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                    </div>
                    <div>
                        <label for="recurring-end-time" class="block text-sm font-medium text-gray-700 dark:text-gray-200">End Time <span class="text-red-500">*</span></label>
                        <input type="time" id="recurring-end-time" name="end_time" required class="block w-full px-3 py-2 mt-1 text-gray-900 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                    </div>
                </div>
                <div>
                    <label for="recurring-capacity" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Capacity <span class="text-red-500">*</span></label>
                    <input type="number" id="recurring-capacity" name="capacity" min="1" value="1" required class="block w-full px-3 py-2 mt-1 text-gray-900 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Number of concurrent bookings for this slot.</p>
                </div>
                <div class="flex items-center">
                    <input id="recurring-is-active" name="is_active" type="checkbox" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                    <label for="recurring-is-active" class="block ml-2 text-sm text-gray-900 dark:text-gray-300">Active</label>
                </div>
                <div class="flex justify-end mt-6 space-x-4">
                    <button type="button" class="px-4 py-2 font-medium tracking-wide text-gray-700 capitalize transition-colors duration-200 transform bg-gray-200 rounded-md mobooking-modal-close hover:bg-gray-300 focus:outline-none focus:bg-gray-300 dark:text-gray-200 dark:bg-gray-600 dark:hover:bg-gray-700">Cancel</button>
                    <button type="submit" class="px-4 py-2 font-medium tracking-wide text-white capitalize transition-colors duration-200 transform bg-indigo-600 rounded-md hover:bg-indigo-500 focus:outline-none focus:bg-indigo-500">Save Slot</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Generic Confirmation/Alert Modal -->
    <div id="mobooking-generic-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black bg-opacity-50">
        <div class="w-full max-w-md p-6 bg-white rounded-md shadow-lg dark:bg-gray-800">
            <h3 id="mobooking-generic-modal-title" class="text-lg font-semibold text-gray-700 capitalize dark:text-white"></h3>
            <p id="mobooking-generic-modal-message" class="mt-2 text-sm text-gray-600 dark:text-gray-400"></p>
            <div id="mobooking-generic-modal-actions" class="flex justify-end mt-6 space-x-4">
                <button type="button" id="mobooking-generic-modal-cancel-btn" class="px-4 py-2 font-medium tracking-wide text-gray-700 capitalize transition-colors duration-200 transform bg-gray-200 rounded-md mobooking-modal-close hover:bg-gray-300 focus:outline-none focus:bg-gray-300 dark:text-gray-200 dark:bg-gray-600 dark:hover:bg-gray-700"></button>
                <button type="button" id="mobooking-generic-modal-confirm-btn" class="px-4 py-2 font-medium tracking-wide text-white capitalize transition-colors duration-200 transform bg-indigo-600 rounded-md hover:bg-indigo-500 focus:outline-none focus:bg-indigo-500"></button>
            </div>
        </div>
    </div>
</div>
