<?php
/**
 * Page template for managing workers and sending invitations.
 * Refactored with ShadCN UI styling and improved organization.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Use the correct capability for this page.
if ( ! current_user_can( \MoBooking\Classes\Auth::CAP_MANAGE_WORKERS ) ) {
    wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'mobooking' ) );
}

$current_user_id = get_current_user_id();

// Define worker roles for the dropdowns
$all_worker_roles = [
    \MoBooking\Classes\Auth::ROLE_WORKER_STAFF   => __( 'Staff', 'mobooking' ),
];

?>

<div>
    <div class="flex items-center justify-between">
        <h3 class="text-3xl font-medium text-gray-700 dark:text-gray-200">Manage Workers</h3>
    </div>

    <div class="mt-8">
        <div class="p-6 bg-white rounded-md shadow-md dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-700 capitalize dark:text-white">Add New Worker</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">You can either invite a worker to create their own account, or manually add them with a defined password.</p>

            <div class="mt-6">
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="flex -mb-px space-x-8" aria-label="Tabs">
                        <a href="#" class="px-1 py-4 text-sm font-medium text-indigo-600 border-b-2 border-indigo-500 whitespace-nowrap" data-tab="invite-section">Invite New Worker via Email</a>
                        <a href="#" class="px-1 py-4 text-sm font-medium text-gray-500 border-b-2 border-transparent whitespace-nowrap hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300" data-tab="direct-add-section">Add Worker Directly</a>
                    </nav>
                </div>

                <div id="invite-section" class="py-6">
                    <form id="mobooking-invite-worker-form">
                        <?php wp_nonce_field('mobooking_send_invitation_nonce', 'mobooking_nonce'); ?>
                        <input type="hidden" name="action" value="mobooking_send_invitation">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label class="text-gray-700 dark:text-gray-200" for="worker_email">Email Address</label>
                                <input id="worker_email" type="email" name="worker_email" required class="block w-full px-4 py-2 mt-2 text-gray-700 bg-white border border-gray-300 rounded-md dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring">
                            </div>
                            <div>
                                <label class="text-gray-700 dark:text-gray-200" for="worker_role">Role</label>
                                <select id="worker_role" name="worker_role" required class="block w-full px-4 py-2 mt-2 text-gray-700 bg-white border border-gray-300 rounded-md dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring">
                                    <?php foreach ($all_worker_roles as $role_key => $role_name) : ?>
                                        <option value="<?php echo esc_attr($role_key); ?>"><?php echo esc_html($role_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="flex justify-end mt-6">
                            <button type="submit" class="px-6 py-2 font-medium tracking-wide text-white capitalize transition-colors duration-200 transform bg-indigo-600 rounded-md hover:bg-indigo-500 focus:outline-none focus:bg-indigo-500">
                                Send Invitation
                            </button>
                        </div>
                    </form>
                </div>

                <div id="direct-add-section" class="hidden py-6">
                    <form id="mobooking-direct-add-worker-form">
                        <?php wp_nonce_field('mobooking_direct_add_staff_nonce', 'mobooking_direct_add_staff_nonce_field'); ?>
                        <input type="hidden" name="action" value="mobooking_direct_add_staff">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label class="text-gray-700 dark:text-gray-200" for="direct_add_staff_email">Email Address</label>
                                <input id="direct_add_staff_email" type="email" name="direct_add_staff_email" required class="block w-full px-4 py-2 mt-2 text-gray-700 bg-white border border-gray-300 rounded-md dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring">
                            </div>
                            <div>
                                <label class="text-gray-700 dark:text-gray-200" for="direct_add_staff_password">Password</label>
                                <input id="direct_add_staff_password" type="password" name="direct_add_staff_password" required class="block w-full px-4 py-2 mt-2 text-gray-700 bg-white border border-gray-300 rounded-md dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring">
                            </div>
                            <div>
                                <label class="text-gray-700 dark:text-gray-200" for="direct_add_staff_first_name">First Name</label>
                                <input id="direct_add_staff_first_name" type="text" name="direct_add_staff_first_name" class="block w-full px-4 py-2 mt-2 text-gray-700 bg-white border border-gray-300 rounded-md dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring">
                            </div>
                            <div>
                                <label class="text-gray-700 dark:text-gray-200" for="direct_add_staff_last_name">Last Name</label>
                                <input id="direct_add_staff_last_name" type="text" name="direct_add_staff_last_name" class="block w-full px-4 py-2 mt-2 text-gray-700 bg-white border border-gray-300 rounded-md dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring">
                            </div>
                        </div>
                        <div class="flex justify-end mt-6">
                            <button type="submit" class="px-6 py-2 font-medium tracking-wide text-white capitalize transition-colors duration-200 transform bg-indigo-600 rounded-md hover:bg-indigo-500 focus:outline-none focus:bg-indigo-500">
                                Create Worker Account
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="mt-8">
            <h2 class="text-lg font-semibold text-gray-700 capitalize dark:text-white">Current Workers</h2>
            <div class="flex flex-col mt-6">
                <div class="-my-2 py-2 overflow-x-auto sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
                    <div class="align-middle inline-block min-w-full shadow overflow-hidden sm:rounded-lg border-b border-gray-200 dark:border-gray-700">
                        <table class="min-w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-xs font-medium leading-4 tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Email</th>
                                    <th class="px-6 py-3 text-xs font-medium leading-4 tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Name</th>
                                    <th class="px-6 py-3 text-xs font-medium leading-4 tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Role</th>
                                    <th class="px-6 py-3 text-xs font-medium leading-4 tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                <?php
                                $workers = get_users(['meta_key' => \MoBooking\Classes\Auth::META_KEY_OWNER_ID, 'meta_value' => $current_user_id]);
                                if (!empty($workers)) :
                                    foreach ($workers as $worker) :
                                        $current_worker_role_name = __('N/A', 'mobooking');
                                        if (in_array(\MoBooking\Classes\Auth::ROLE_WORKER_STAFF, $worker->roles)) {
                                            $current_worker_role_name = $all_worker_roles[\MoBooking\Classes\Auth::ROLE_WORKER_STAFF];
                                        }
                                        ?>
                                        <tr id="worker-row-<?php echo esc_attr($worker->ID); ?>">
                                            <td class="px-6 py-4 whitespace-no-wrap"><?php echo esc_html($worker->user_email); ?></td>
                                            <td class="px-6 py-4 whitespace-no-wrap"><?php echo esc_html(trim($worker->first_name . ' ' . $worker->last_name) ?: __('No name set', 'mobooking')); ?></td>
                                            <td class="px-6 py-4 whitespace-no-wrap"><?php echo esc_html($current_worker_role_name); ?></td>
                                            <td class="px-6 py-4 whitespace-no-wrap text-sm font-medium">
                                                <button class="text-indigo-600 hover:text-indigo-900 dark:hover:text-indigo-400 mobooking-edit-worker-details-btn" data-worker-id="<?php echo esc_attr($worker->ID); ?>">Edit</button>
                                                <button class="ml-2 text-red-600 hover:text-red-900 dark:hover:text-red-400 mobooking-delete-worker-btn" data-id="<?php echo esc_attr($worker->ID); ?>">Revoke Access</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No Workers Yet</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Workers Page - ShadCN UI Styles */
.mobooking-workers-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

/* Page Header */
.mobooking-page-header {
    margin-bottom: 2rem;
}

.mobooking-page-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 2rem;
    font-weight: 700;
    color: hsl(222.2 84% 4.9%);
    margin: 0 0 0.5rem 0;
}

.mobooking-page-icon {
    color: hsl(221.2 83.2% 53.3%);
}

.mobooking-page-description {
    color: hsl(215.4 16.3% 46.9%);
    font-size: 1.125rem;
    margin: 0;
}

/* Alert Components */
.mobooking-alert {
    border-radius: 0.5rem;
    border: 1px solid;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.mobooking-alert.mobooking-alert-success {
    border-color: hsl(142 76% 36% / 0.3);
    background-color: hsl(142 76% 36% / 0.1);
    color: hsl(142 76% 36%);
}

.mobooking-alert.mobooking-alert-error {
    border-color: hsl(0 84.2% 60.2% / 0.3);
    background-color: hsl(0 84.2% 60.2% / 0.1);
    color: hsl(0 84.2% 60.2%);
}

.mobooking-alert-content {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.mobooking-alert-icon-success,
.mobooking-alert-icon-error {
    flex-shrink: 0;
}

.mobooking-alert.mobooking-alert-success .mobooking-alert-icon-error,
.mobooking-alert.mobooking-alert-error .mobooking-alert-icon-success {
    display: none;
}

.mobooking-inline-alert {
    border-radius: 0.375rem;
    border: 1px solid;
    padding: 0.75rem;
    margin-bottom: 1rem;
    font-size: 0.875rem;
}

.mobooking-inline-alert.mobooking-inline-alert-success {
    border-color: hsl(142 76% 36% / 0.3);
    background-color: hsl(142 76% 36% / 0.05);
    color: hsl(142 76% 36%);
}

.mobooking-inline-alert.mobooking-inline-alert-error {
    border-color: hsl(0 84.2% 60.2% / 0.3);
    background-color: hsl(0 84.2% 60.2% / 0.05);
    color: hsl(0 84.2% 60.2%);
}

.mobooking-inline-alert-content {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.mobooking-inline-alert.mobooking-inline-alert-success .mobooking-inline-alert-icon-error,
.mobooking-inline-alert.mobooking-inline-alert-error .mobooking-inline-alert-icon-success {
    display: none;
}

.mobooking-inline-alert-message {
    margin: 0;
}

/* Card Components */
.mobooking-card {
    background-color: hsl(0 0% 100%);
    border: 1px solid hsl(214.3 31.8% 91.4%);
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
    margin-bottom: 2rem;
}

.mobooking-card-header {
    padding: 1.5rem 1.5rem 0 1.5rem;
}

.mobooking-card-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.25rem;
    font-weight: 600;
    color: hsl(222.2 84% 4.9%);
    margin: 0 0 0.5rem 0;
}

.mobooking-card-description {
    color: hsl(215.4 16.3% 46.9%);
    margin: 0;
}

.mobooking-card-content {
    padding: 1.5rem;
}

/* Accordion Components */
.mobooking-accordion {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.mobooking-accordion-item {
    border: 1px solid hsl(214.3 31.8% 91.4%);
    border-radius: 0.375rem;
    overflow: hidden;
}

.mobooking-accordion-trigger {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    width: 100%;
    padding: 1rem;
    background-color: hsl(210 40% 98%);
    cursor: pointer;
    transition: background-color 0.15s ease;
    border: none;
    text-align: left;
    font-family: inherit;
    font-size: inherit;
    outline: none;
    position: relative;
    user-select: none;
}

.mobooking-accordion-trigger:hover {
    background-color: hsl(210 40% 96%);
}

.mobooking-accordion-trigger:focus {
    background-color: hsl(210 40% 96%);
    box-shadow: inset 0 0 0 2px hsl(221.2 83.2% 53.3%);
}

.mobooking-accordion-trigger:active {
    background-color: hsl(210 40% 94%);
}

.mobooking-accordion-icon {
    transition: transform 0.2s ease;
    color: hsl(215.4 16.3% 46.9%);
    flex-shrink: 0;
    pointer-events: none; /* Prevent clicks on the icon itself */
}

.mobooking-accordion-item.mobooking-accordion-open .mobooking-accordion-icon {
    transform: rotate(90deg);
}

.mobooking-accordion-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1rem;
    font-weight: 600;
    color: hsl(222.2 84% 4.9%);
    margin: 0;
    flex: 1;
    pointer-events: none; /* Prevent clicks on child elements */
}

.mobooking-accordion-title svg {
    pointer-events: none;
}

.mobooking-accordion-content {
    display: none;
    border-top: 1px solid hsl(214.3 31.8% 91.4%);
    background-color: hsl(0 0% 100%);
}

.mobooking-accordion-item.mobooking-accordion-open .mobooking-accordion-content {
    display: block;
}

.mobooking-accordion-content-inner {
    padding: 1.5rem;
}

/* Debug styling */
.mobooking-accordion-trigger {
    border: 2px solid transparent !important;
}

.mobooking-accordion-trigger:hover {
    border-color: hsl(221.2 83.2% 53.3%) !important;
}

/* Form Components */
.mobooking-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.mobooking-form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.mobooking-form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.mobooking-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: hsl(222.2 84% 4.9%);
}

.mobooking-required {
    color: hsl(0 84.2% 60.2%);
}

.mobooking-input,
.mobooking-select {
    height: 2.5rem;
    padding: 0.5rem 0.75rem;
    border: 1px solid hsl(214.3 31.8% 91.4%);
    border-radius: 0.375rem;
    background-color: hsl(0 0% 100%);
    font-size: 0.875rem;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.mobooking-input:focus,
.mobooking-select:focus {
    outline: none;
    border-color: hsl(221.2 83.2% 53.3%);
    box-shadow: 0 0 0 3px hsl(221.2 83.2% 53.3% / 0.1);
}

.mobooking-input-group {
    position: relative;
    display: flex;
}

.mobooking-input-group .mobooking-input {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
    border-right: 0;
}



.mobooking-password-toggle .mobooking-eye-closed {
    display: none;
}

.mobooking-password-toggle.mobooking-password-visible .mobooking-eye-open {
    display: none;
}

.mobooking-password-toggle.mobooking-password-visible .mobooking-eye-closed {
    display: block;
}

.mobooking-helper-text {
    font-size: 0.875rem;
    color: hsl(215.4 16.3% 46.9%);
    margin: 0 0 1rem 0;
}

.mobooking-form-actions {
    display: flex;
    gap: 0.75rem;
    justify-content: flex-start;
    padding-top: 0.5rem;
    border-top: 1px solid hsl(214.3 31.8% 91.4%);
    margin-top: 0.5rem;
}

/* Button Components */
.mobooking-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    height: 2.5rem;
    padding: 0 1rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 500;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all 0.15s ease;
    text-decoration: none;
}

.mobooking-button-sm {
    height: 2rem;
    padding: 0 0.75rem;
    font-size: 0.8125rem;
}

.mobooking-button-primary {
    background-color: hsl(221.2 83.2% 53.3%);
    color: hsl(210 40% 98%);
    border-color: hsl(221.2 83.2% 53.3%);
}

.mobooking-button-primary:hover {
    background-color: hsl(221.2 83.2% 50%);
    border-color: hsl(221.2 83.2% 50%);
}

.mobooking-button-secondary {
    background-color: hsl(210 40% 96.1%);
    color: hsl(222.2 84% 4.9%);
    border-color: hsl(214.3 31.8% 91.4%);
}

.mobooking-button-secondary:hover {
    background-color: hsl(210 40% 94%);
}

.mobooking-button-outline {
    background-color: transparent;
    color: hsl(222.2 84% 4.9%);
    border-color: hsl(214.3 31.8% 91.4%);
}

.mobooking-button-outline:hover {
    background-color: hsl(210 40% 98%);
}

.mobooking-button-destructive {
    background-color: hsl(0 84.2% 60.2%);
    color: hsl(210 40% 98%);
    border-color: hsl(0 84.2% 60.2%);
}

.mobooking-button-destructive:hover {
    background-color: hsl(0 84.2% 55%);
    border-color: hsl(0 84.2% 55%);
}

.mobooking-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Table Components */
.mobooking-table-container {
    border: 1px solid hsl(214.3 31.8% 91.4%);
    border-radius: 0.5rem;
    overflow: hidden;
}

.mobooking-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.mobooking-table-header {
    background-color: hsl(210 40% 98%);
    border-bottom: 1px solid hsl(214.3 31.8% 91.4%);
    padding: 0.75rem;
    text-align: left;
    font-weight: 600;
    color: hsl(222.2 84% 4.9%);
}

.mobooking-table-header-content {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.mobooking-table-header-actions {
    width: 200px;
}

.mobooking-table-row {
    border-bottom: 1px solid hsl(214.3 31.8% 91.4%);
}

.mobooking-table-row:last-child {
    border-bottom: none;
}

.mobooking-table-cell {
    padding: 1rem 0.75rem;
    vertical-align: top;
}

.mobooking-table-cell-content {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.mobooking-table-primary-text {
    font-weight: 500;
    color: hsl(222.2 84% 4.9%);
}

.mobooking-table-secondary-text {
    font-size: 0.8125rem;
    color: hsl(215.4 16.3% 46.9%);
}

.mobooking-avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    background-color: hsl(210 40% 96.1%);
    color: hsl(215.4 16.3% 46.9%);
    flex-shrink: 0;
}

.mobooking-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.5rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.mobooking-badge-secondary {
    background-color: hsl(210 40% 96.1%);
    color: hsl(222.2 84% 4.9%);
}

.mobooking-table-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    align-items: flex-start;
}

.mobooking-inline-form-group {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    width: 100%;
}

.mobooking-inline-form {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    flex: 1;
}

.mobooking-select-sm {
    height: 2rem;
    font-size: 0.8125rem;
    min-width: 80px;
}

/* Edit Form */
.mobooking-edit-worker-form {
    margin-top: 1rem;
}

.mobooking-edit-form-container {
    border: 1px solid hsl(214.3 31.8% 91.4%);
    border-radius: 0.375rem;
    background-color: hsl(210 40% 98%);
    overflow: hidden;
}

.mobooking-edit-form-header {
    padding: 0.75rem 1rem;
    background-color: hsl(210 40% 96%);
    border-bottom: 1px solid hsl(214.3 31.8% 91.4%);
}

.mobooking-edit-form-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: hsl(222.2 84% 4.9%);
    margin: 0;
}

.mobooking-edit-form-container .mobooking-form {
    padding: 1rem;
}

/* Empty State */
.mobooking-empty-state {
    text-align: center;
    padding: 3rem 1rem;
}

.mobooking-empty-state-content {
    max-width: 400px;
    margin: 0 auto;
}

.mobooking-empty-state-icon {
    margin-bottom: 1rem;
    color: hsl(215.4 16.3% 46.9%);
}

.mobooking-empty-state-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: hsl(222.2 84% 4.9%);
    margin: 0 0 0.5rem 0;
}

.mobooking-empty-state-description {
    color: hsl(215.4 16.3% 46.9%);
    margin: 0;
}

/* Responsive Design */
@media (max-width: 768px) {
    .mobooking-workers-page {
        padding: 1rem 0.5rem;
    }
    
    .mobooking-page-title {
        font-size: 1.5rem;
    }
    
    .mobooking-form-grid {
        grid-template-columns: 1fr;
    }
    
    .mobooking-table-container {
        overflow-x: auto;
    }
    
    .mobooking-table {
        min-width: 600px;
    }
    
    .mobooking-table-actions {
        gap: 0.25rem;
    }
    
    .mobooking-inline-form-group {
        flex-direction: column;
        align-items: stretch;
    }
    
    .mobooking-form-actions {
        flex-direction: column;
    }
    
    .mobooking-accordion-content-inner {
        padding: 1rem;
    }
}

@media (max-width: 480px) {
    .mobooking-card-content,
    .mobooking-card-header {
        padding: 1rem;
    }
    
    .mobooking-table-cell {
        padding: 0.75rem 0.5rem;
    }
    
    .mobooking-button {
        font-size: 0.8125rem;
    }
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    'use strict';

    // Initialize variables
    var feedbackArea = $('#mobooking-feedback-area');
    var currentWorkersSection = $('#current-workers-feedback');
    var inviteWorkerSection = $('#invite-worker-feedback');
    var directAddWorkerSection = $('#direct-add-worker-feedback');

    // Check if mobooking_workers_params is available, if not create fallback
    if (typeof mobooking_workers_params === 'undefined') {
        window.mobooking_workers_params = {
            ajax_url: ajaxurl || '/wp-admin/admin-ajax.php',
            i18n: {
                error_occurred: 'An error occurred.',
                error_ajax: 'An AJAX error occurred.',
                confirm_delete: 'Are you sure you want to delete this worker?',
                error_deleting_worker: 'Error deleting worker.',
                error_saving_worker: 'Error saving worker.',
                no_name_set: 'No name set'
            }
        };
    }

    // Utility function to show feedback messages
    function showFeedback(message, isSuccess, targetArea) {
        if (!targetArea) {
            targetArea = feedbackArea;
        }
        
        var alertClass = isSuccess ? 'mobooking-alert-success' : 'mobooking-alert-error';
        var inlineAlertClass = isSuccess ? 'mobooking-inline-alert-success' : 'mobooking-inline-alert-error';
        
        // Check if it's the main feedback area or inline
        if (targetArea.hasClass('mobooking-alert')) {
            targetArea.removeClass('mobooking-alert-success mobooking-alert-error').addClass(alertClass);
            targetArea.find('p').text(message);
        } else {
            targetArea.removeClass('mobooking-inline-alert-success mobooking-inline-alert-error').addClass(inlineAlertClass);
            targetArea.find('.mobooking-inline-alert-message').text(message);
        }
        
        targetArea.slideDown('fast');
        
        // Auto-hide after 5 seconds for success messages
        if (isSuccess) {
            setTimeout(function() {
                targetArea.slideUp('fast');
            }, 5000);
        }
    }

    // Accordion functionality - multiple approaches for better compatibility
    function toggleAccordion($trigger) {
        var $item = $trigger.closest('.mobooking-accordion-item');
        var target = $trigger.data('target');
        var $content = $('#' + target);
        
        console.log('Toggle accordion. Target:', target, 'Content found:', $content.length > 0);
        
        if ($content.length === 0) {
            console.error('No content element found for target:', target);
            return;
        }
        
        // Toggle the current item
        if ($item.hasClass('mobooking-accordion-open')) {
            console.log('Closing accordion item');
            $item.removeClass('mobooking-accordion-open');
            $content.slideUp(200);
        } else {
            console.log('Opening accordion item, closing others first');
            // Close other accordion items
            $('.mobooking-accordion-item').removeClass('mobooking-accordion-open');
            $('.mobooking-accordion-content').slideUp(200);
            
            // Open the clicked item
            $item.addClass('mobooking-accordion-open');
            $content.slideDown(200);
        }
    }

    // Primary click handler
    $(document).on('click', '.mobooking-accordion-trigger', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('Accordion trigger clicked');
        toggleAccordion($(this));
    });

    // Backup: Handle clicks on child elements
    $(document).on('click', '.mobooking-accordion-trigger *', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('Child element clicked, triggering accordion');
        toggleAccordion($(this).closest('.mobooking-accordion-trigger'));
    });

    // Password toggle functionality
    $('.mobooking-password-toggle').on('click', function() {
        var $toggle = $(this);
        var targetId = $toggle.data('target');
        var $input = $('#' + targetId);
        
        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $toggle.addClass('mobooking-password-visible');
        } else {
            $input.attr('type', 'password');
            $toggle.removeClass('mobooking-password-visible');
        }
    });

    // Invite Worker form
    $('#mobooking-invite-worker-form').on('submit', function(e) {
        e.preventDefault();
        inviteWorkerSection.hide();
        
        var $form = $(this);
        var formData = $form.serialize();
        var $submitButton = $form.find('button[type="submit"]');
        var originalButtonText = $submitButton.html();
        
        console.log('Invite worker form data:', formData);
        
        $submitButton.prop('disabled', true).html('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="animate-spin"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg>Sending...');
        
        $.ajax({
            url: mobooking_workers_params.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log('Invite response:', response);
                if (response.success) {
                    showFeedback(response.data.message, true, inviteWorkerSection);
                    $form[0].reset();
                    
                    // Refresh the workers list after a short delay
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showFeedback(response.data.message || 'Error sending invitation', false, inviteWorkerSection);
                }
            },
            error: function(xhr, status, error) {
                console.error('Invite AJAX Error:', xhr, status, error);
                showFeedback('AJAX Error: ' + error, false, inviteWorkerSection);
            },
            complete: function() {
                $submitButton.prop('disabled', false).html(originalButtonText);
            }
        });
    });

    // Direct Add Worker form
    $('#mobooking-direct-add-worker-form').on('submit', function(e) {
        e.preventDefault();
        directAddWorkerSection.hide();
        
        var $form = $(this);
        var formData = $form.serialize();
        var $submitButton = $form.find('button[type="submit"]');
        var originalButtonText = $submitButton.html();
        
        console.log('Direct add worker form data:', formData);
        
        $submitButton.prop('disabled', true).html('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="animate-spin"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg>Creating...');
        
        $.ajax({
            url: mobooking_workers_params.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log('Direct add response:', response);
                if (response.success) {
                    showFeedback(response.data.message, true, directAddWorkerSection);
                    $form[0].reset();
                    
                    // Refresh the workers list after a short delay
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showFeedback(response.data.message || 'Error creating worker', false, directAddWorkerSection);
                }
            },
            error: function(xhr, status, error) {
                console.error('Direct add AJAX Error:', xhr, status, error);
                showFeedback('AJAX Error: ' + error, false, directAddWorkerSection);
            },
            complete: function() {
                $submitButton.prop('disabled', false).html(originalButtonText);
            }
        });
    });

    // Change Role form
    $('.mobooking-workers-page').on('submit', '.mobooking-change-role-form', function(e) {
        e.preventDefault();
        currentWorkersSection.hide();
        
        var $form = $(this);
        var formData = $form.serialize();
        var workerId = $form.find('input[name="worker_user_id"]').val();
        var $submitButton = $form.find('.mobooking-change-role-submit-btn');
        var originalButtonText = $submitButton.html();
        
        $submitButton.prop('disabled', true).html('<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="animate-spin"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg>Updating...');
        
        $.post(mobooking_workers_params.ajax_url, formData, function(response) {
            if (response.success) {
                showFeedback(response.data.message, true, currentWorkersSection);
                $('#worker-row-' + workerId + ' .worker-role-display').text(response.data.new_role_display_name);
                $form.find('.mobooking-role-select option').removeAttr('selected');
                $form.find('.mobooking-role-select option[value="' + response.data.new_role_key + '"]').attr('selected', 'selected');
            } else {
                showFeedback(response.data.message || mobooking_workers_params.i18n.error_occurred, false, currentWorkersSection);
            }
        }).fail(function() {
            showFeedback(mobooking_workers_params.i18n.error_ajax, false, currentWorkersSection);
        }).always(function() {
            $submitButton.prop('disabled', false).html(originalButtonText);
        });
    });

    // Delete Worker form
    $('.mobooking-workers-page').on('submit', '.mobooking-delete-worker-form', function(e) {
        e.preventDefault();
        
        if (!confirm(mobooking_workers_params.i18n.confirm_delete)) {
            return;
        }
        
        currentWorkersSection.hide();
        
        var $form = $(this);
        var workerId = $form.find('input[name="worker_user_id"]').val();
        
        // Debug: Check if worker ID is found
        if (!workerId) {
            console.error('Worker ID not found in form');
            showFeedback('Error: Worker ID not found', false, currentWorkersSection);
            return;
        }
        
        console.log('Deleting worker with ID:', workerId);
        
        var formData = $form.serialize();
        console.log('Form data:', formData);
        
        var $submitButton = $form.find('.mobooking-delete-worker-btn');
        var originalButtonText = $submitButton.html();
        
        $submitButton.prop('disabled', true).html('<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="animate-spin"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg>Deleting...');
        
        $.ajax({
            url: mobooking_workers_params.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log('Delete response:', response);
                if (response.success) {
                    showFeedback(response.data.message, true, currentWorkersSection);
                    $('#worker-row-' + workerId).fadeOut('fast', function() {
                        $(this).remove();
                        
                        // Check if there are any workers left
                        if ($('.mobooking-table-row').length === 0) {
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        }
                    });
                } else {
                    showFeedback(response.data.message || mobooking_workers_params.i18n.error_deleting_worker, false, currentWorkersSection);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr, status, error);
                console.error('Response text:', xhr.responseText);
                showFeedback('AJAX Error: ' + error, false, currentWorkersSection);
            },
            complete: function() {
                $submitButton.prop('disabled', false).html(originalButtonText);
            }
        });
    });

    // Edit Worker Details - Show/Hide form
    $('.mobooking-workers-page').on('click', '.mobooking-edit-worker-details-btn', function() {
        var workerId = $(this).data('worker-id');
        var $form = $('#edit-worker-form-' + workerId);
        
        if ($form.is(':visible')) {
            $form.slideUp('fast');
        } else {
            // Hide other edit forms
            $('.mobooking-edit-worker-form').slideUp('fast');
            $form.slideDown('fast');
        }
    });

    $('.mobooking-workers-page').on('click', '.mobooking-cancel-edit-details-btn', function() {
        var workerId = $(this).data('worker-id');
        $('#edit-worker-form-' + workerId).slideUp('fast');
    });

    // Edit Worker Details - AJAX Submit
    $('.mobooking-workers-page').on('submit', '.mobooking-edit-details-actual-form', function(e) {
        e.preventDefault();
        currentWorkersSection.hide();
        
        var $form = $(this);
        var formData = $form.serialize();
        var workerId = $form.find('input[name="worker_user_id"]').val();
        var $submitButton = $form.find('.mobooking-save-details-btn');
        var originalButtonText = $submitButton.html();
        
        $submitButton.prop('disabled', true).html('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="animate-spin"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg>Saving...');
        
        $.post(mobooking_workers_params.ajax_url, formData, function(response) {
            if (response.success) {
                showFeedback(response.data.message, true, currentWorkersSection);
                
                // Update the displayed name in the table
                var firstName = $form.find('input[name="edit_first_name"]').val();
                var lastName = $form.find('input[name="edit_last_name"]').val();
                var fullName = $.trim(firstName + ' ' + lastName) || mobooking_workers_params.i18n.no_name_set || 'No name set';
                
                $('#worker-row-' + workerId + ' .worker-full-name-display').text(fullName);
                $('#worker-row-' + workerId + ' .worker-first-name-display').text(firstName);
                $('#worker-row-' + workerId + ' .worker-last-name-display').text(lastName);
                
                // Hide the form
                $('#edit-worker-form-' + workerId).slideUp('fast');
            } else {
                showFeedback(response.data.message || mobooking_workers_params.i18n.error_saving_worker, false, currentWorkersSection);
            }
        }).fail(function() {
            showFeedback(mobooking_workers_params.i18n.error_ajax, false, currentWorkersSection);
        }).always(function() {
            $submitButton.prop('disabled', false).html(originalButtonText);
        });
    });

    // Add spinning animation for loading states
    var spinningIconCSS = `
        .animate-spin {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }
    `;
    
    $('<style>').prop('type', 'text/css').html(spinningIconCSS).appendTo('head');
});
</script>

<?php
// JavaScript for this page is now handled inline above
// The main dashboard-workers.js file can be updated to match this new structure
?>