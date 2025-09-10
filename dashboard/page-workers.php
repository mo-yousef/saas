<?php
/**
 * Page template for managing workers and sending invitations.
 * Refactored with ShadCN UI styling and improved organization.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Use the correct capability for this page.
if ( ! current_user_can( \NORDBOOKING\Classes\Auth::CAP_MANAGE_WORKERS ) ) {
    wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'NORDBOOKING' ) );
}

$current_user_id = get_current_user_id();

// Define worker roles for the dropdowns
$all_worker_roles = [
    \NORDBOOKING\Classes\Auth::ROLE_WORKER_STAFF   => __( 'Staff', 'NORDBOOKING' ),
];

?>

<div class="NORDBOOKING-dashboard-wrap NORDBOOKING-workers-page">
    <!-- Page Header -->
    <div class="NORDBOOKING-page-header">
        <div class="NORDBOOKING-page-header-heading">
            <span class="NORDBOOKING-page-header-icon">
                <?php echo nordbooking_get_dashboard_menu_icon('workers'); ?>
            </span>
            <h1 class="NORDBOOKING-page-title"><?php esc_html_e( 'Manage Workers', 'NORDBOOKING' ); ?></h1>
        </div>
    </div>

    <!-- Global Feedback Area -->
    <div id="NORDBOOKING-feedback-area" class="NORDBOOKING-alert" style="display:none;">
        <div class="NORDBOOKING-alert-content">
            <div class="NORDBOOKING-alert-icon">
                <svg class="NORDBOOKING-alert-icon-success" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                <svg class="NORDBOOKING-alert-icon-error" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
            </div>
            <div class="NORDBOOKING-alert-message">
                <p></p>
            </div>
        </div>
    </div>

    <!-- Add New Worker Section -->
    <div class="NORDBOOKING-card card-bs">
        <div class="NORDBOOKING-card-header">
            <h2 class="NORDBOOKING-card-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="16"></line>
                    <line x1="8" y1="12" x2="16" y2="12"></line>
                </svg>
                <?php esc_html_e( 'Add New Worker', 'NORDBOOKING' ); ?>
            </h2>
        </div>

        <div class="NORDBOOKING-card-content">
            <!-- Invite Worker Section -->
            <div class="NORDBOOKING-accordion">
                <div class="NORDBOOKING-accordion-item">
                    <div class="NORDBOOKING-accordion-trigger" data-target="invite-section">
                        <div class="NORDBOOKING-accordion-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9,18 15,12 9,6"></polyline>
                            </svg>
                        </div>
                        <h3 class="NORDBOOKING-accordion-title">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                            <?php esc_html_e( 'Invite New Worker via Email', 'NORDBOOKING' ); ?>
                        </h3>
                    </div>
                    <div class="NORDBOOKING-accordion-content" id="invite-section">
                        <div class="NORDBOOKING-accordion-content-inner">
                            <div id="invite-worker-feedback" class="NORDBOOKING-inline-alert" style="display:none;">
                                <div class="NORDBOOKING-inline-alert-content">
                                    <div class="NORDBOOKING-inline-alert-icon">
                                        <svg class="NORDBOOKING-inline-alert-icon-success" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20,6 9,17 4,12"></polyline>
                                        </svg>
                                        <svg class="NORDBOOKING-inline-alert-icon-error" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="15" y1="9" x2="9" y2="15"></line>
                                            <line x1="9" y1="9" x2="15" y2="15"></line>
                                        </svg>
                                    </div>
                                    <p class="NORDBOOKING-inline-alert-message"></p>
                                </div>
                            </div>

                            <p class="NORDBOOKING-helper-text">
                                <?php esc_html_e( 'Invite a new worker by providing their email address and assigning a role. They will receive an email with a registration link.', 'NORDBOOKING' ); ?>
                            </p>

                            <form id="NORDBOOKING-invite-worker-form" class="NORDBOOKING-form">
                                <?php wp_nonce_field( 'nordbooking_send_invitation_nonce', 'nordbooking_nonce' ); ?>
                                <input type="hidden" name="action" value="nordbooking_send_invitation">

                                <div class="NORDBOOKING-form-grid">
                                    <div class="NORDBOOKING-form-group">
                                        <label for="worker_email" class="NORDBOOKING-label">
                                            <?php esc_html_e( 'Email Address', 'NORDBOOKING' ); ?>
                                            <span class="NORDBOOKING-required">*</span>
                                        </label>
                                        <input type="email" id="worker_email" name="worker_email" class="NORDBOOKING-input" placeholder="worker@example.com" required>
                                    </div>

                                    <div class="NORDBOOKING-form-group">
                                        <label for="worker_role" class="NORDBOOKING-label">
                                            <?php esc_html_e( 'Role', 'NORDBOOKING' ); ?>
                                            <span class="NORDBOOKING-required">*</span>
                                        </label>
                                        <select id="worker_role" name="worker_role" class="NORDBOOKING-select" required>
                                            <?php foreach ( $all_worker_roles as $role_key => $role_name ) : ?>
                                                <option value="<?php echo esc_attr( $role_key ); ?>"><?php echo esc_html( $role_name ); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="NORDBOOKING-form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                            <polyline points="22,6 12,13 2,6"></polyline>
                                        </svg>
                                        <?php esc_html_e( 'Send Invitation', 'NORDBOOKING' ); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Direct Add Worker Section -->
                <div class="NORDBOOKING-accordion-item">
                    <div class="NORDBOOKING-accordion-trigger" data-target="direct-add-section">
                        <div class="NORDBOOKING-accordion-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9,18 15,12 9,6"></polyline>
                            </svg>
                        </div>
                        <h3 class="NORDBOOKING-accordion-title">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <?php esc_html_e( 'Add Worker Directly', 'NORDBOOKING' ); ?>
                        </h3>
                    </div>
                    <div class="NORDBOOKING-accordion-content" id="direct-add-section">
                        <div class="NORDBOOKING-accordion-content-inner">
                            <div id="direct-add-worker-feedback" class="NORDBOOKING-inline-alert" style="display:none;">
                                <div class="NORDBOOKING-inline-alert-content">
                                    <div class="NORDBOOKING-inline-alert-icon">
                                        <svg class="NORDBOOKING-inline-alert-icon-success" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20,6 9,17 4,12"></polyline>
                                        </svg>
                                        <svg class="NORDBOOKING-inline-alert-icon-error" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="15" y1="9" x2="9" y2="15"></line>
                                            <line x1="9" y1="9" x2="15" y2="15"></line>
                                        </svg>
                                    </div>
                                    <p class="NORDBOOKING-inline-alert-message"></p>
                                </div>
                            </div>

                            <p class="NORDBOOKING-helper-text">
                                <?php esc_html_e( 'Create a worker account directly with a username and password. The worker can log in immediately.', 'NORDBOOKING' ); ?>
                            </p>

                            <form id="NORDBOOKING-direct-add-worker-form" class="NORDBOOKING-form">
                                <?php wp_nonce_field( 'nordbooking_direct_add_staff_nonce', 'nordbooking_direct_add_staff_nonce_field' ); ?>
                                <input type="hidden" name="action" value="nordbooking_direct_add_staff">

                                <div class="NORDBOOKING-form-grid">
                                    <div class="NORDBOOKING-form-group">
                                        <label for="direct_add_staff_email" class="NORDBOOKING-label">
                                            <?php esc_html_e( 'Email Address', 'NORDBOOKING' ); ?>
                                            <span class="NORDBOOKING-required">*</span>
                                        </label>
                                        <input type="email" id="direct_add_staff_email" name="direct_add_staff_email" class="NORDBOOKING-input" placeholder="worker@example.com" required>
                                    </div>

                                    <div class="NORDBOOKING-form-group">
                                        <label for="direct_add_staff_password" class="NORDBOOKING-label">
                                            <?php esc_html_e( 'Password', 'NORDBOOKING' ); ?>
                                            <span class="NORDBOOKING-required">*</span>
                                        </label>
                                        <div class="NORDBOOKING-input-group">
                                            <input type="password" id="direct_add_staff_password" name="direct_add_staff_password" class="NORDBOOKING-input" placeholder="Enter password" required>
                                            <button type="button" class="btn btn-icon btn-secondary" data-target="direct_add_staff_password">
                                                <svg class="NORDBOOKING-eye-open" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                                <svg class="NORDBOOKING-eye-closed" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="NORDBOOKING-form-group">
                                        <label for="direct_add_staff_first_name" class="NORDBOOKING-label">
                                            <?php esc_html_e( 'First Name', 'NORDBOOKING' ); ?>
                                        </label>
                                        <input type="text" id="direct_add_staff_first_name" name="direct_add_staff_first_name" class="NORDBOOKING-input" placeholder="First name">
                                    </div>

                                    <div class="NORDBOOKING-form-group">
                                        <label for="direct_add_staff_last_name" class="NORDBOOKING-label">
                                            <?php esc_html_e( 'Last Name', 'NORDBOOKING' ); ?>
                                        </label>
                                        <input type="text" id="direct_add_staff_last_name" name="direct_add_staff_last_name" class="NORDBOOKING-input" placeholder="Last name">
                                    </div>
                                </div>

                                <div class="NORDBOOKING-form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                        <?php esc_html_e( 'Create Worker Account', 'NORDBOOKING' ); ?>
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
    <div class="NORDBOOKING-card">
        <div class="NORDBOOKING-card-header">
            <h2 class="NORDBOOKING-card-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <?php esc_html_e( 'Current Workers', 'NORDBOOKING' ); ?>
            </h2>
        </div>

        <div class="NORDBOOKING-card-content">
            <div id="current-workers-feedback" class="NORDBOOKING-inline-alert" style="display:none;">
                <div class="NORDBOOKING-inline-alert-content">
                    <div class="NORDBOOKING-inline-alert-icon">
                        <svg class="NORDBOOKING-inline-alert-icon-success" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20,6 9,17 4,12"></polyline>
                        </svg>
                        <svg class="NORDBOOKING-inline-alert-icon-error" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="15" y1="9" x2="9" y2="15"></line>
                            <line x1="9" y1="9" x2="15" y2="15"></line>
                        </svg>
                    </div>
                    <p class="NORDBOOKING-inline-alert-message"></p>
                </div>
            </div>

            <?php
            $workers = get_users( [
                'meta_key'   => \NORDBOOKING\Classes\Auth::META_KEY_OWNER_ID,
                'meta_value' => $current_user_id,
            ] );

            if ( ! empty( $workers ) ) :
            ?>
                <div class="NORDBOOKING-workers-grid">
                    <?php foreach ( $workers as $worker ) : ?>
                        <?php
                        $current_worker_role_name = __('N/A', 'NORDBOOKING');
                        $current_worker_role_key = '';

                        if (in_array(\NORDBOOKING\Classes\Auth::ROLE_WORKER_STAFF, $worker->roles)) {
                            $current_worker_role_name = $all_worker_roles[\NORDBOOKING\Classes\Auth::ROLE_WORKER_STAFF];
                            $current_worker_role_key = \NORDBOOKING\Classes\Auth::ROLE_WORKER_STAFF;
                        }
                        ?>
                        <div id="worker-card-<?php echo esc_attr( $worker->ID ); ?>" class="NORDBOOKING-worker-card">
                            <div class="NORDBOOKING-card-inner-content">
                                <div class="NORDBOOKING-worker-info">
                                    <div class="NORDBOOKING-avatar">
                                        <?php echo get_avatar($worker->ID, 40, '', '', ['class' => 'NORDBOOKING-avatar-img']); ?>
                                    </div>
                                    <div class="NORDBOOKING-worker-details">
                                        <h3 class="NORDBOOKING-worker-name">
                                            <?php
                                            $full_name = trim($worker->first_name . ' ' . $worker->last_name);
                                            echo esc_html($full_name ?: __('No name set', 'NORDBOOKING'));
                                            ?>
                                        </h3>
                                        <p class="NORDBOOKING-worker-email"><?php echo esc_html( $worker->user_email ); ?></p>
                                        <div class="NORDBOOKING-badge NORDBOOKING-badge-secondary worker-role-display">
                                            <?php echo esc_html( $current_worker_role_name ); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="NORDBOOKING-worker-actions">
                                    <button type="button" class="btn btn-outline btn-sm NORDBOOKING-edit-worker-details-btn" data-worker-id="<?php echo esc_attr( $worker->ID ); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                        <?php esc_html_e( 'Edit', 'NORDBOOKING' ); ?>
                                    </button>
                                    <form class="NORDBOOKING-delete-worker-form NORDBOOKING-inline-form">
                                        <?php wp_nonce_field( 'nordbooking_revoke_worker_access_nonce_' . $worker->ID, 'nordbooking_revoke_access_nonce' ); ?>
                                        <input type="hidden" name="action" value="nordbooking_revoke_worker_access">
                                        <input type="hidden" name="worker_user_id" value="<?php echo esc_attr( $worker->ID ); ?>">
                                        <button type="submit" class="btn btn-destructive btn-sm NORDBOOKING-delete-worker-btn">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3,6 5,6 21,6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            </svg>
                                            <?php esc_html_e( 'Revoke', 'NORDBOOKING' ); ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div id="edit-worker-form-<?php echo esc_attr( $worker->ID ); ?>" class="NORDBOOKING-edit-worker-form" style="display: none;">
                                <div class="NORDBOOKING-edit-form-container">
                                    <form class="NORDBOOKING-edit-details-actual-form NORDBOOKING-form">
                                        <?php wp_nonce_field( 'nordbooking_edit_details_nonce_' . $worker->ID, 'nordbooking_edit_details_nonce_field' ); ?>
                                        <input type="hidden" name="action" value="nordbooking_edit_worker_details">
                                        <input type="hidden" name="worker_user_id" value="<?php echo esc_attr( $worker->ID ); ?>">
                                        <div class="NORDBOOKING-form-grid">
                                            <div class="NORDBOOKING-form-group">
                                                <label for="edit_first_name_<?php echo esc_attr( $worker->ID ); ?>" class="NORDBOOKING-label"><?php esc_html_e( 'First Name', 'NORDBOOKING' ); ?></label>
                                                <input type="text" id="edit_first_name_<?php echo esc_attr( $worker->ID ); ?>" name="edit_first_name" value="<?php echo esc_attr( $worker->first_name ); ?>" class="NORDBOOKING-input">
                                            </div>
                                            <div class="NORDBOOKING-form-group">
                                                <label for="edit_last_name_<?php echo esc_attr( $worker->ID ); ?>" class="NORDBOOKING-label"><?php esc_html_e( 'Last Name', 'NORDBOOKING' ); ?></label>
                                                <input type="text" id="edit_last_name_<?php echo esc_attr( $worker->ID ); ?>" name="edit_last_name" value="<?php echo esc_attr( $worker->last_name ); ?>" class="NORDBOOKING-input">
                                            </div>
                                        </div>
                                        <div class="NORDBOOKING-form-actions">
                                            <button type="submit" class="btn btn-primary NORDBOOKING-save-details-btn"><?php esc_html_e( 'Save', 'NORDBOOKING' ); ?></button>
                                            <button type="button" class="btn btn-secondary NORDBOOKING-cancel-edit-details-btn" data-worker-id="<?php echo esc_attr( $worker->ID ); ?>"><?php esc_html_e( 'Cancel', 'NORDBOOKING' ); ?></button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="NORDBOOKING-empty-state">
                    <div class="NORDBOOKING-empty-state-content">
                        <div class="NORDBOOKING-empty-state-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <h3 class="NORDBOOKING-empty-state-title">
                            <?php esc_html_e( 'No Workers Yet', 'NORDBOOKING' ); ?>
                        </h3>
                        <p class="NORDBOOKING-empty-state-description">
                            <?php esc_html_e( 'You haven\'t invited any workers yet, or no workers have accepted an invitation. Use the forms above to get started.', 'NORDBOOKING' ); ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// JavaScript for this page is now handled inline above
// The main dashboard-workers.js file can be updated to match this new structure
?>