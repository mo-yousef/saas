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

<div class="mobooking-dashboard-wrap mobooking-workers-page">
    <!-- Page Header -->
    <div class="mobooking-page-header">
        <div class="mobooking-header-content">
            <div class="mobooking-header-text">
                <h1 class="mobooking-page-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mobooking-page-icon">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <?php esc_html_e( 'Manage Workers', 'mobooking' ); ?>
                </h1>
                <p class="mobooking-page-description">
                    <?php esc_html_e( 'Invite team members and manage staff access to your cleaning business.', 'mobooking' ); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Global Feedback Area -->
    <div id="mobooking-feedback-area" class="mobooking-alert" style="display:none;">
        <div class="mobooking-alert-content">
            <div class="mobooking-alert-icon">
                <svg class="mobooking-alert-icon-success" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <svg class="mobooking-alert-icon-error" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
            </div>
            <div class="mobooking-alert-message">
                <p></p>
            </div>
        </div>
    </div>

    <!-- Add New Worker Section -->
    <div class="mobooking-card">
        <div class="mobooking-card-header">
            <h2 class="mobooking-card-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="16"></line>
                    <line x1="8" y1="12" x2="16" y2="12"></line>
                </svg>
                <?php esc_html_e( 'Add New Worker', 'mobooking' ); ?>
            </h2>
            <p class="mobooking-card-description">
                <?php esc_html_e( 'You can either invite a worker to create their own account, or manually add them with a defined password.', 'mobooking' ); ?>
            </p>
        </div>

        <div class="mobooking-card-content">
            <!-- Invite Worker Section -->
            <div class="mobooking-accordion">
                <div class="mobooking-accordion-item">
                    <div class="mobooking-accordion-trigger" data-target="invite-section">
                        <div class="mobooking-accordion-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9,18 15,12 9,6"></polyline>
                            </svg>
                        </div>
                        <h3 class="mobooking-accordion-title">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                            <?php esc_html_e( 'Invite New Worker via Email', 'mobooking' ); ?>
                        </h3>
                    </div>
                    <div class="mobooking-accordion-content" id="invite-section">
                        <div class="mobooking-accordion-content-inner">
                            <div id="invite-worker-feedback" class="mobooking-inline-alert" style="display:none;">
                                <div class="mobooking-inline-alert-content">
                                    <div class="mobooking-inline-alert-icon">
                                        <svg class="mobooking-inline-alert-icon-success" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20,6 9,17 4,12"></polyline>
                                        </svg>
                                        <svg class="mobooking-inline-alert-icon-error" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="15" y1="9" x2="9" y2="15"></line>
                                            <line x1="9" y1="9" x2="15" y2="15"></line>
                                        </svg>
                                    </div>
                                    <p class="mobooking-inline-alert-message"></p>
                                </div>
                            </div>

                            <p class="mobooking-helper-text">
                                <?php esc_html_e( 'Invite a new worker by providing their email address and assigning a role. They will receive an email with a registration link.', 'mobooking' ); ?>
                            </p>

                            <form id="mobooking-invite-worker-form" class="mobooking-form">
                                <?php wp_nonce_field( 'mobooking_send_invitation_nonce', 'mobooking_nonce' ); ?>
                                <input type="hidden" name="action" value="mobooking_send_invitation">

                                <div class="mobooking-form-grid">
                                    <div class="mobooking-form-group">
                                        <label for="worker_email" class="mobooking-label">
                                            <?php esc_html_e( 'Email Address', 'mobooking' ); ?>
                                            <span class="mobooking-required">*</span>
                                        </label>
                                        <input type="email" id="worker_email" name="worker_email" class="mobooking-input" placeholder="worker@example.com" required>
                                    </div>

                                    <div class="mobooking-form-group">
                                        <label for="worker_role" class="mobooking-label">
                                            <?php esc_html_e( 'Role', 'mobooking' ); ?>
                                            <span class="mobooking-required">*</span>
                                        </label>
                                        <select id="worker_role" name="worker_role" class="mobooking-select" required>
                                            <?php foreach ( $all_worker_roles as $role_key => $role_name ) : ?>
                                                <option value="<?php echo esc_attr( $role_key ); ?>"><?php echo esc_html( $role_name ); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="mobooking-form-actions">
                                    <button type="submit" class="mobooking-button mobooking-button-primary">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                            <polyline points="22,6 12,13 2,6"></polyline>
                                        </svg>
                                        <?php esc_html_e( 'Send Invitation', 'mobooking' ); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Direct Add Worker Section -->
                <div class="mobooking-accordion-item">
                    <div class="mobooking-accordion-trigger" data-target="direct-add-section">
                        <div class="mobooking-accordion-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9,18 15,12 9,6"></polyline>
                            </svg>
                        </div>
                        <h3 class="mobooking-accordion-title">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <?php esc_html_e( 'Add Worker Directly', 'mobooking' ); ?>
                        </h3>
                    </div>
                    <div class="mobooking-accordion-content" id="direct-add-section">
                        <div class="mobooking-accordion-content-inner">
                            <div id="direct-add-worker-feedback" class="mobooking-inline-alert" style="display:none;">
                                <div class="mobooking-inline-alert-content">
                                    <div class="mobooking-inline-alert-icon">
                                        <svg class="mobooking-inline-alert-icon-success" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20,6 9,17 4,12"></polyline>
                                        </svg>
                                        <svg class="mobooking-inline-alert-icon-error" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="15" y1="9" x2="9" y2="15"></line>
                                            <line x1="9" y1="9" x2="15" y2="15"></line>
                                        </svg>
                                    </div>
                                    <p class="mobooking-inline-alert-message"></p>
                                </div>
                            </div>

                            <p class="mobooking-helper-text">
                                <?php esc_html_e( 'Create a worker account directly with a username and password. The worker can log in immediately.', 'mobooking' ); ?>
                            </p>

                            <form id="mobooking-direct-add-worker-form" class="mobooking-form">
                                <?php wp_nonce_field( 'mobooking_direct_add_staff_nonce', 'mobooking_direct_add_staff_nonce_field' ); ?>
                                <input type="hidden" name="action" value="mobooking_direct_add_staff">

                                <div class="mobooking-form-grid">
                                    <div class="mobooking-form-group">
                                        <label for="direct_add_staff_email" class="mobooking-label">
                                            <?php esc_html_e( 'Email Address', 'mobooking' ); ?>
                                            <span class="mobooking-required">*</span>
                                        </label>
                                        <input type="email" id="direct_add_staff_email" name="direct_add_staff_email" class="mobooking-input" placeholder="worker@example.com" required>
                                    </div>

                                    <div class="mobooking-form-group">
                                        <label for="direct_add_staff_password" class="mobooking-label">
                                            <?php esc_html_e( 'Password', 'mobooking' ); ?>
                                            <span class="mobooking-required">*</span>
                                        </label>
                                        <div class="mobooking-input-group">
                                            <input type="password" id="direct_add_staff_password" name="direct_add_staff_password" class="mobooking-input" placeholder="Enter password" required>
                                            <button type="button" class="mobooking-input-addon mobooking-password-toggle" data-target="direct_add_staff_password">
                                                <svg class="mobooking-eye-open" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                                <svg class="mobooking-eye-closed" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mobooking-form-group">
                                        <label for="direct_add_staff_first_name" class="mobooking-label">
                                            <?php esc_html_e( 'First Name', 'mobooking' ); ?>
                                        </label>
                                        <input type="text" id="direct_add_staff_first_name" name="direct_add_staff_first_name" class="mobooking-input" placeholder="First name">
                                    </div>

                                    <div class="mobooking-form-group">
                                        <label for="direct_add_staff_last_name" class="mobooking-label">
                                            <?php esc_html_e( 'Last Name', 'mobooking' ); ?>
                                        </label>
                                        <input type="text" id="direct_add_staff_last_name" name="direct_add_staff_last_name" class="mobooking-input" placeholder="Last name">
                                    </div>
                                </div>

                                <div class="mobooking-form-actions">
                                    <button type="submit" class="mobooking-button mobooking-button-primary">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                        <?php esc_html_e( 'Create Worker Account', 'mobooking' ); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Workers Section -->
    <div class="mobooking-card">
        <div class="mobooking-card-header">
            <h2 class="mobooking-card-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <?php esc_html_e( 'Current Workers', 'mobooking' ); ?>
            </h2>
        </div>

        <div class="mobooking-card-content">
            <div id="current-workers-feedback" class="mobooking-inline-alert" style="display:none;">
                <div class="mobooking-inline-alert-content">
                    <div class="mobooking-inline-alert-icon">
                        <svg class="mobooking-inline-alert-icon-success" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20,6 9,17 4,12"></polyline>
                        </svg>
                        <svg class="mobooking-inline-alert-icon-error" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="15" y1="9" x2="9" y2="15"></line>
                            <line x1="9" y1="9" x2="15" y2="15"></line>
                        </svg>
                    </div>
                    <p class="mobooking-inline-alert-message"></p>
                </div>
            </div>

            <?php
            $workers = get_users( [
                'meta_key'   => \MoBooking\Classes\Auth::META_KEY_OWNER_ID,
                'meta_value' => $current_user_id,
            ] );

            if ( ! empty( $workers ) ) :
            ?>
                <div class="mobooking-table">
                    <div class="mobooking-table-header">
                        <div class="mobooking-table-cell"><?php esc_html_e( 'Email', 'mobooking' ); ?></div>
                        <div class="mobooking-table-cell"><?php esc_html_e( 'Name', 'mobooking' ); ?></div>
                        <div class="mobooking-table-cell"><?php esc_html_e( 'Role', 'mobooking' ); ?></div>
                        <div class="mobooking-table-cell"><?php esc_html_e( 'Actions', 'mobooking' ); ?></div>
                    </div>
                    <div class="mobooking-table-body">
                        <?php foreach ( $workers as $worker ) : ?>
                            <?php
                            $current_worker_role_name = __('N/A', 'mobooking');
                            $current_worker_role_key = '';

                            if (in_array(\MoBooking\Classes\Auth::ROLE_WORKER_STAFF, $worker->roles)) {
                                $current_worker_role_name = $all_worker_roles[\MoBooking\Classes\Auth::ROLE_WORKER_STAFF];
                                $current_worker_role_key = \MoBooking\Classes\Auth::ROLE_WORKER_STAFF;
                            }
                            ?>
                            <div id="worker-row-<?php echo esc_attr( $worker->ID ); ?>" class="mobooking-table-row">
                                <div class="mobooking-table-cell" data-label="<?php esc_attr_e( 'Email', 'mobooking' ); ?>">
                                    <?php echo esc_html( $worker->user_email ); ?>
                                </div>
                                <div class="mobooking-table-cell" data-label="<?php esc_attr_e( 'Name', 'mobooking' ); ?>">
                                    <?php
                                    $full_name = trim($worker->first_name . ' ' . $worker->last_name);
                                    echo esc_html($full_name ?: __('No name set', 'mobooking'));
                                    ?>
                                </div>
                                <div class="mobooking-table-cell" data-label="<?php esc_attr_e( 'Role', 'mobooking' ); ?>">
                                    <div class="mobooking-badge mobooking-badge-secondary worker-role-display">
                                        <?php echo esc_html( $current_worker_role_name ); ?>
                                    </div>
                                </div>
                                <div class="mobooking-table-cell" data-label="<?php esc_attr_e( 'Actions', 'mobooking' ); ?>">
                                    <div class="mobooking-table-actions">
                                        <button type="button" class="mobooking-button mobooking-button-sm mobooking-button-outline mobooking-edit-worker-details-btn" data-worker-id="<?php echo esc_attr( $worker->ID ); ?>"><?php esc_html_e( 'Edit', 'mobooking' ); ?></button>
                                        <form class="mobooking-delete-worker-form mobooking-inline-form">
                                            <?php wp_nonce_field( 'mobooking_revoke_worker_access_nonce_' . $worker->ID, 'mobooking_revoke_access_nonce' ); ?>
                                            <input type="hidden" name="action" value="mobooking_revoke_worker_access">
                                            <input type="hidden" name="worker_user_id" value="<?php echo esc_attr( $worker->ID ); ?>">
                                            <button type="submit" class="mobooking-button mobooking-button-sm mobooking-button-destructive mobooking-delete-worker-btn"><?php esc_html_e( 'Revoke Access', 'mobooking' ); ?></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else : ?>
                <div class="mobooking-empty-state">
                    <div class="mobooking-empty-state-content">
                        <div class="mobooking-empty-state-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <h3 class="mobooking-empty-state-title">
                            <?php esc_html_e( 'No Workers Yet', 'mobooking' ); ?>
                        </h3>
                        <p class="mobooking-empty-state-description">
                            <?php esc_html_e( 'You haven\'t invited any workers yet, or no workers have accepted an invitation. Use the forms above to get started.', 'mobooking' ); ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>
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