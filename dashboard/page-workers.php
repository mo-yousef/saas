<?php
/**
 * Page template for managing workers and sending invitations.
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

<div class="wrap mobooking-dashboard-page mobooking-workers-page">
    <h1><?php esc_html_e( 'Manage Workers', 'mobooking' ); ?></h1>

    <div id="mobooking-feedback-area" class="notice" style="display:none;">
        <p></p>
    </div>

    <div class="mobooking-section-add-worker">
        <h2><?php esc_html_e( 'Add New Worker', 'mobooking' ); ?></h2>
        <p><?php esc_html_e('You can either invite a worker to create their own account, or manually add them with a defined password.', 'mobooking'); ?></p>

        <details class="mobooking-collapsible-section">
            <summary class="mobooking-collapsible-summary">
                <h3><?php esc_html_e( 'Invite New Worker via Email', 'mobooking' ); ?></h3>
            </summary>
            <div class="mobooking-collapsible-content">
                <p><?php esc_html_e( 'Invite a new worker by providing their email address and assigning a role. They will receive an email with a registration link.', 'mobooking' ); ?></p>
                <form id="mobooking-invite-worker-form" method="POST" class="mobooking-form-spaced">
                    <input type="hidden" name="action" value="mobooking_send_invitation">
                    <?php wp_nonce_field( 'mobooking_send_invitation_nonce', 'mobooking_invitation_nonce' ); ?>
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="worker_email"><?php esc_html_e( 'Worker Email', 'mobooking' ); ?></label>
                                </th>
                                <td>
                                    <input type="email" id="worker_email" name="worker_email" class="regular-text" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="worker_role_invite"><?php esc_html_e( 'Assign Role', 'mobooking' ); ?></label>
                                </th>
                                <td>
                                    <select id="worker_role_invite" name="worker_role" required>
                                        <option value="<?php echo esc_attr(\MoBooking\Classes\Auth::ROLE_WORKER_STAFF); ?>">
                                            <?php
                                            $staff_role_display_name = __( 'Staff', 'mobooking' );
                                            if (function_exists('wp_roles')) {
                                                $roles = wp_roles();
                                                if (isset($roles->role_names[\MoBooking\Classes\Auth::ROLE_WORKER_STAFF])) {
                                                    $staff_role_display_name = $roles->role_names[\MoBooking\Classes\Auth::ROLE_WORKER_STAFF];
                                                }
                                            }
                                            echo esc_html($staff_role_display_name);
                                            ?>
                                        </option>
                                    </select>
                                    <p class="description"><?php esc_html_e( 'New workers will be assigned the "Staff" role.', 'mobooking' ); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="submit">
                        <input type="submit" name="submit_invite" id="submit_invite" class="button button-primary" value="<?php echo esc_attr__( 'Send Invitation', 'mobooking' ); ?>">
                    </p>
                </form>
            </div>
        </details>

        <details class="mobooking-collapsible-section">
            <summary class="mobooking-collapsible-summary">
                <h3><?php esc_html_e( 'Manually Create New Worker Staff', 'mobooking' ); ?></h3>
            </summary>
            <div class="mobooking-collapsible-content">
                <p><?php esc_html_e( 'Directly create a new Worker Staff member by setting their email and password. They will be automatically assigned to your business.', 'mobooking' ); ?></p>
                <form id="mobooking-direct-add-staff-form" method="POST" class="mobooking-form-spaced">
                    <?php wp_nonce_field( 'mobooking_direct_add_staff_nonce', 'mobooking_direct_add_staff_nonce_field' ); ?>
                    <input type="hidden" name="action" value="mobooking_direct_add_staff">
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="direct_add_staff_email"><?php esc_html_e( 'Worker Email', 'mobooking' ); ?></label>
                                </th>
                                <td>
                                    <input type="email" id="direct_add_staff_email" name="direct_add_staff_email" class="regular-text" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="direct_add_staff_password"><?php esc_html_e( 'Password', 'mobooking' ); ?></label>
                                </th>
                                <td>
                                    <input type="password" id="direct_add_staff_password" name="direct_add_staff_password" class="regular-text" required>
                                    <p class="description"><?php esc_html_e( 'Minimum 8 characters.', 'mobooking' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="direct_add_staff_first_name"><?php esc_html_e( 'First Name (Optional)', 'mobooking' ); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="direct_add_staff_first_name" name="direct_add_staff_first_name" class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="direct_add_staff_last_name"><?php esc_html_e( 'Last Name (Optional)', 'mobooking' ); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="direct_add_staff_last_name" name="direct_add_staff_last_name" class="regular-text">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="submit">
                        <input type="submit" name="submit_direct_add" id="submit_direct_add" class="button button-primary" value="<?php echo esc_attr__( 'Create and Add Worker Staff', 'mobooking' ); ?>">
                    </p>
                </form>
            </div>
        </details>
    </div>

    <hr style="margin-top: 2em; margin-bottom: 2em;">

    <div class="mobooking-section-current-workers">
        <h2><?php esc_html_e( 'Current Workers', 'mobooking' ); ?></h2>
    <?php
    $workers = get_users( [
        'meta_key'   => \MoBooking\Classes\Auth::META_KEY_OWNER_ID,
        'meta_value' => $current_user_id,
    ] );

    if ( ! empty( $workers ) ) :
    ?>
        <table class="wp-list-table widefat fixed striped mobooking-workers-table">
            <thead>
                <tr>
                    <th scope="col"><?php esc_html_e( 'Email', 'mobooking' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'First Name', 'mobooking' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Last Name', 'mobooking' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Role', 'mobooking' ); ?></th>
                    <th scope="col" style="width: 350px;"><?php esc_html_e( 'Actions', 'mobooking' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $workers as $worker ) : ?>
                    <?php
                    // $worker_mobooking_roles_display = []; // Keep for potential future if multiple roles come back
                    $current_worker_role_name = __('N/A', 'mobooking');
                    $current_worker_role_key = ''; // Keep this to ensure 'Staff' is selected in dropdown if they have it.

                    // Since we only have one worker role (Staff), this logic simplifies.
                    // We primarily want to display 'Staff' if they have that role.
                    if (in_array(\MoBooking\Classes\Auth::ROLE_WORKER_STAFF, $worker->roles)) {
                        $current_worker_role_name = $all_worker_roles[\MoBooking\Classes\Auth::ROLE_WORKER_STAFF];
                        $current_worker_role_key = \MoBooking\Classes\Auth::ROLE_WORKER_STAFF;
                    } else {
                        // If they have other WP roles but not staff, or no roles.
                        // This part of the display might need more thought if users can have non-MoBooking roles simultaneously.
                        // For now, if not explicitly staff, mark as N/A for MoBooking role.
                    }
                    ?>
                    <tr id="worker-row-<?php echo esc_attr( $worker->ID ); ?>">
                        <td class="worker-email-display"><?php echo esc_html( $worker->user_email ); ?></td>
                        <td class="worker-first-name-display"><?php echo esc_html( $worker->first_name ); ?></td>
                        <td class="worker-last-name-display"><?php echo esc_html( $worker->last_name ); ?></td>
                        <td class="worker-role-display">
                            <?php echo esc_html( $current_worker_role_name ); ?>
                        </td>
                        <td class="actions-cell">
                            <div class="mobooking-worker-actions">
                                <button type="button" class="button button-secondary button-small mobooking-edit-worker-details-btn" data-worker-id="<?php echo esc_attr( $worker->ID ); ?>">
                                    <?php esc_html_e( 'Edit Info', 'mobooking' ); ?>
                                </button>

                                <?php // The Change Role form is less useful if there's only one target role (Staff). Kept for structure. ?>
                                <form class="mobooking-change-role-form mobooking-action-form">
                                    <?php wp_nonce_field( 'mobooking_change_worker_role_nonce_' . $worker->ID, 'mobooking_change_role_nonce' ); ?>
                                    <input type="hidden" name="action" value="mobooking_change_worker_role">
                                    <input type="hidden" name="worker_user_id" value="<?php echo esc_attr( $worker->ID ); ?>">
                                    <select name="new_role" class="mobooking-role-select" title="<?php esc_attr_e('Change worker role', 'mobooking'); ?>">
                                        <?php foreach ( $all_worker_roles as $role_key_option => $role_name_option ) : ?>
                                            <option value="<?php echo esc_attr( $role_key_option ); ?>" <?php selected( $current_worker_role_key, $role_key_option ); ?>>
                                                <?php echo esc_html( $role_name_option ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="button button-secondary button-small mobooking-change-role-submit-btn">
                                        <?php esc_html_e( 'Set Role', 'mobooking' ); ?>
                                    </button>
                                </form>

                                <form class="mobooking-revoke-access-form mobooking-action-form">
                                    <?php wp_nonce_field( 'mobooking_revoke_worker_access_nonce_' . $worker->ID, 'mobooking_revoke_access_nonce' ); ?>
                                    <input type="hidden" name="action" value="mobooking_revoke_worker_access">
                                    <input type="hidden" name="worker_user_id" value="<?php echo esc_attr( $worker->ID ); ?>">
                                    <button type="submit" class="button button-link-delete button-small mobooking-revoke-access-btn">
                                        <?php esc_html_e( 'Revoke Access', 'mobooking' ); ?>
                                    </button>
                                </form>
                            </div>

                            <div id="edit-worker-form-<?php echo esc_attr( $worker->ID ); ?>" class="mobooking-edit-worker-inline-form" style="display:none;">
                                <h4><?php esc_html_e( 'Edit Worker Details', 'mobooking' ); ?>: <?php echo esc_html($worker->user_email); ?></h4>
                                <form class="mobooking-edit-details-actual-form">
                                    <?php wp_nonce_field( 'mobooking_edit_worker_details_nonce_' . $worker->ID, 'mobooking_edit_details_nonce_field' ); ?>
                                    <input type="hidden" name="action" value="mobooking_edit_worker_details">
                                    <input type="hidden" name="worker_user_id" value="<?php echo esc_attr( $worker->ID ); ?>">
                                    <p>
                                        <label for="edit_first_name_<?php echo esc_attr( $worker->ID ); ?>"><?php esc_html_e( 'First Name:', 'mobooking' ); ?></label><br>
                                        <input type="text" id="edit_first_name_<?php echo esc_attr( $worker->ID ); ?>" name="edit_first_name" value="<?php echo esc_attr( $worker->first_name ); ?>" class="regular-text">
                                    </p>
                                    <p>
                                        <label for="edit_last_name_<?php echo esc_attr( $worker->ID ); ?>"><?php esc_html_e( 'Last Name:', 'mobooking' ); ?></label><br>
                                        <input type="text" id="edit_last_name_<?php echo esc_attr( $worker->ID ); ?>" name="edit_last_name" value="<?php echo esc_attr( $worker->last_name ); ?>" class="regular-text">
                                    </p>
                                    <p class="submit">
                                        <button type="submit" class="button button-primary mobooking-save-details-btn"><?php esc_html_e( 'Save Details', 'mobooking' ); ?></button>
                                        <button type="button" class="button button-secondary mobooking-cancel-edit-details-btn" data-worker-id="<?php echo esc_attr( $worker->ID ); ?>"><?php esc_html_e( 'Cancel', 'mobooking' ); ?></button>
                                    </p>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p><?php esc_html_e( 'You have not invited any workers yet, or no workers have accepted an invitation.', 'mobooking' ); ?></p>
    <?php endif; ?>
</div>

<?php // JavaScript for this page is now enqueued via mobooking_enqueue_dashboard_scripts in functions.php ?>
