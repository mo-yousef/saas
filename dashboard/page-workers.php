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
    \MoBooking\Classes\Auth::ROLE_WORKER_MANAGER => __( 'Manager', 'mobooking' ),
    \MoBooking\Classes\Auth::ROLE_WORKER_STAFF   => __( 'Staff', 'mobooking' ),
    \MoBooking\Classes\Auth::ROLE_WORKER_VIEWER  => __( 'Viewer', 'mobooking' ),
];

?>

<div class="wrap mobooking-dashboard-page mobooking-workers-page">
    <h1><?php esc_html_e( 'Manage Workers', 'mobooking' ); ?></h1>

    <div id="mobooking-feedback-area" class="notice" style="display:none;">
        <p></p>
    </div>

    <h2><?php esc_html_e( 'Invite New Worker', 'mobooking' ); ?></h2>
    <p><?php esc_html_e( 'Invite a new worker by providing their email address and assigning a role. They will receive an email with a registration link.', 'mobooking' ); ?></p>

    <form id="mobooking-invite-worker-form" method="POST">
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
                                // Ensure wp_roles() is available or use predefined display name
                                $staff_role_display_name = __( 'Staff', 'mobooking' ); // Default
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
        <?php submit_button( __( 'Send Invitation', 'mobooking' ) ); ?>
    </form>

    <hr style="margin-top: 2em; margin-bottom: 2em;">

    <h2><?php esc_html_e( 'Manually Add New Worker Staff', 'mobooking' ); ?></h2>
    <p><?php esc_html_e( 'Directly create a new Worker Staff member by setting their email and password. They will be automatically assigned to your business.', 'mobooking' ); ?></p>
    <form id="mobooking-direct-add-staff-form" method="POST">
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
        <?php submit_button( __( 'Create and Add Worker Staff', 'mobooking' ) ); ?>
    </form>

    <hr>

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
                    $worker_mobooking_roles_display = [];
                    $current_worker_role_key = '';
                    foreach($all_worker_roles as $role_key => $role_name) {
                        if (in_array($role_key, $worker->roles)) {
                            $worker_mobooking_roles_display[] = $role_name;
                            if (empty($current_worker_role_key)) { // Capture the first MoBooking role as current
                                $current_worker_role_key = $role_key;
                            }
                        }
                    }
                    ?>
                    <tr id="worker-row-<?php echo esc_attr( $worker->ID ); ?>">
                        <td class="worker-email-display"><?php echo esc_html( $worker->user_email ); ?></td>
                        <td class="worker-first-name-display"><?php echo esc_html( $worker->first_name ); ?></td>
                        <td class="worker-last-name-display"><?php echo esc_html( $worker->last_name ); ?></td>
                        <td class="worker-role-display">
                            <?php echo esc_html( !empty($worker_mobooking_roles_display) ? implode(', ', $worker_mobooking_roles_display) : 'N/A' ); ?>
                        </td>
                        <td>
                            <button type="button" class="button button-small mobooking-edit-worker-details-btn" data-worker-id="<?php echo esc_attr( $worker->ID ); ?>">
                                <?php esc_html_e( 'Edit Info', 'mobooking' ); ?>
                            </button>

                            <form class="mobooking-change-role-form" style="display: inline-block; margin-left: 5px;">
                                <?php wp_nonce_field( 'mobooking_change_worker_role_nonce_' . $worker->ID, 'mobooking_change_role_nonce' ); ?>
                                <input type="hidden" name="action" value="mobooking_change_worker_role">
                                <input type="hidden" name="worker_user_id" value="<?php echo esc_attr( $worker->ID ); ?>">
                                <select name="new_role" class="mobooking-role-select">
                                    <?php foreach ( $all_worker_roles as $role_key_option => $role_name_option ) : ?>
                                        <option value="<?php echo esc_attr( $role_key_option ); ?>" <?php selected( $current_worker_role_key, $role_key_option ); ?>>
                                            <?php echo esc_html( $role_name_option ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="button button-secondary button-small mobooking-change-role-submit-btn">
                                    <?php esc_html_e( 'Change Role', 'mobooking' ); ?>
                                </button>
                            </form>

                            <form class="mobooking-revoke-access-form" style="display: inline-block; margin-left: 5px;">
                                <?php wp_nonce_field( 'mobooking_revoke_worker_access_nonce_' . $worker->ID, 'mobooking_revoke_access_nonce' ); ?>
                                <input type="hidden" name="action" value="mobooking_revoke_worker_access">
                                <input type="hidden" name="worker_user_id" value="<?php echo esc_attr( $worker->ID ); ?>">
                                <button type="submit" class="button button-link-delete mobooking-revoke-access-btn">
                                    <?php esc_html_e( 'Revoke Access', 'mobooking' ); ?>
                                </button>
                            </form>

                            <div id="edit-worker-form-<?php echo esc_attr( $worker->ID ); ?>" class="mobooking-edit-worker-inline-form" style="display:none; margin-top:10px; padding:10px; border:1px solid #ccc; background-color:#f9f9f9;">
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
                                    <button type="submit" class="button button-primary mobooking-save-details-btn"><?php esc_html_e( 'Save Details', 'mobooking' ); ?></button>
                                    <button type="button" class="button mobooking-cancel-edit-details-btn" data-worker-id="<?php echo esc_attr( $worker->ID ); ?>"><?php esc_html_e( 'Cancel', 'mobooking' ); ?></button>
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

<?php // Enqueue dashboard-workers.js separately in your plugin's asset enqueuing logic. ?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    const feedbackArea = $('#mobooking-feedback-area');
    const feedbackP = feedbackArea.find('p');

    function showFeedback(message, isSuccess) {
        feedbackP.html(message);
        if (isSuccess) {
            feedbackArea.removeClass('notice-error').addClass('notice-success is-dismissible');
        } else {
            feedbackArea.removeClass('notice-success').addClass('notice-error is-dismissible');
        }
        feedbackArea.show().delay(5000).fadeOut();
         // Scroll to feedback
        $('html, body').animate({ scrollTop: feedbackArea.offset().top - 50 }, 500);
    }

    // Invitation form
    $('#mobooking-invite-worker-form').on('submit', function(e) {
        e.preventDefault();
        feedbackArea.hide();
        var formData = $(this).serialize();

        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                showFeedback(response.data.message, true);
                $('#worker_email').val(''); // Clear email field
                // Potentially refresh worker list or add new worker row dynamically here
                // For now, suggest a page reload to see the new pending worker (if applicable) or updated list.
                // location.reload(); // Or a more sophisticated list update
            } else {
                showFeedback(response.data.message || '<?php echo esc_js( __( "An error occurred.", "mobooking" ) ); ?>', false);
            }
        }).fail(function() {
            showFeedback('<?php echo esc_js( __( "An unexpected error occurred. Please try again.", "mobooking" ) ); ?>', false);
        });
    });

    // Revoke Access
    $('.mobooking-revoke-access-form').on('submit', function(e) {
        e.preventDefault();
        if (!confirm('<?php echo esc_js( __( "Are you sure you want to revoke this worker\'s access? This cannot be undone.", "mobooking" ) ); ?>')) {
            return;
        }
        feedbackArea.hide();
        var $form = $(this);
        var workerId = $form.find('input[name="worker_user_id"]').val();
        var nonce = $form.find('input[name="mobooking_revoke_access_nonce"]').val();
        var $button = $form.find('.mobooking-revoke-access-btn');
        $button.prop('disabled', true).text('<?php echo esc_js( __("Revoking...", "mobooking") ); ?>');

        $.post(ajaxurl, {
            action: 'mobooking_revoke_worker_access',
            worker_user_id: workerId,
            mobooking_revoke_access_nonce: nonce
        }, function(response) {
            if (response.success) {
                showFeedback(response.data.message, true);
                $('#worker-row-' + workerId).fadeOut(500, function() { $(this).remove(); });
            } else {
                showFeedback(response.data.message || '<?php echo esc_js( __( "An error occurred while revoking access.", "mobooking" ) ); ?>', false);
                $button.prop('disabled', false).text('<?php echo esc_js( __("Revoke Access", "mobooking") ); ?>');
            }
        }).fail(function() {
            showFeedback('<?php echo esc_js( __( "An unexpected error occurred. Please try again.", "mobooking" ) ); ?>', false);
            $button.prop('disabled', false).text('<?php echo esc_js( __("Revoke Access", "mobooking") ); ?>');
        });
    });

    // Direct Add Staff form
    $('#mobooking-direct-add-staff-form').on('submit', function(e) {
        e.preventDefault();
        feedbackArea.hide();
        var formData = $(this).serialize();
        var $form = $(this);
        var $submitButton = $form.find('input[type="submit"]');
        $submitButton.prop('disabled', true);

        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                showFeedback(response.data.message, true);
                $form[0].reset(); // Clear form fields
                // Consider refreshing the worker list or adding the new worker dynamically.
                // For now, a page reload might be the simplest way if immediate update is needed.
                // location.reload();
            } else {
                showFeedback(response.data.message || '<?php echo esc_js( __( "An error occurred.", "mobooking" ) ); ?>', false);
            }
        }).fail(function() {
            showFeedback('<?php echo esc_js( __( "An unexpected error occurred. Please try again.", "mobooking" ) ); ?>', false);
        }).always(function() {
            $submitButton.prop('disabled', false);
        });
    });

    // Change Role form
    $('.mobooking-workers-table').on('submit', '.mobooking-change-role-form', function(e) {
        e.preventDefault();
        feedbackArea.hide();
        var $form = $(this);
        var formData = $form.serialize();
        var workerId = $form.find('input[name="worker_user_id"]').val();
        var $submitButton = $form.find('.mobooking-change-role-submit-btn');
        var originalButtonText = $submitButton.text();
        $submitButton.prop('disabled', true).text('<?php echo esc_js( __("Changing...", "mobooking") ); ?>');

        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                showFeedback(response.data.message, true);
                // Update role display in the table
                $('#worker-row-' + workerId + ' .worker-role-display').text(response.data.new_role_display_name);
                // Update the selected option in the dropdown for this specific form
                $form.find('.mobooking-role-select option').removeAttr('selected');
                $form.find('.mobooking-role-select option[value="' + response.data.new_role_key + '"]').attr('selected', 'selected');

            } else {
                showFeedback(response.data.message || '<?php echo esc_js( __( "An error occurred.", "mobooking" ) ); ?>', false);
            }
        }).fail(function() {
            showFeedback('<?php echo esc_js( __( "An unexpected server error occurred. Please try again.", "mobooking" ) ); ?>', false);
        }).always(function() {
            $submitButton.prop('disabled', false).text(originalButtonText);
        });
    });

    // Edit Worker Details - Show/Hide form
    $('.mobooking-workers-table').on('click', '.mobooking-edit-worker-details-btn', function() {
        var workerId = $(this).data('worker-id');
        $('#edit-worker-form-' + workerId).slideToggle('fast');
    });
    $('.mobooking-workers-table').on('click', '.mobooking-cancel-edit-details-btn', function() {
        var workerId = $(this).data('worker-id');
        $('#edit-worker-form-' + workerId).slideUp('fast');
    });

    // Edit Worker Details - AJAX Submit
    $('.mobooking-workers-table').on('submit', '.mobooking-edit-details-actual-form', function(e) {
        e.preventDefault();
        feedbackArea.hide();
        var $form = $(this);
        var formData = $form.serialize();
        var workerId = $form.find('input[name="worker_user_id"]').val();
        var $submitButton = $form.find('.mobooking-save-details-btn');
        var originalButtonText = $submitButton.text();
        $submitButton.prop('disabled', true).text('<?php echo esc_js( __("Saving...", "mobooking") ); ?>');

        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                showFeedback(response.data.message, true);
                // Update displayed names in the table
                var newFirstName = $form.find('input[name="edit_first_name"]').val();
                var newLastName = $form.find('input[name="edit_last_name"]').val();
                $('#worker-row-' + workerId + ' .worker-first-name-display').text(newFirstName);
                $('#worker-row-' + workerId + ' .worker-last-name-display').text(newLastName);
                $('#edit-worker-form-' + workerId).slideUp('fast'); // Hide form on success
            } else {
                showFeedback(response.data.message || '<?php echo esc_js( __( "An error occurred.", "mobooking" ) ); ?>', false);
            }
        }).fail(function() {
            showFeedback('<?php echo esc_js( __( "An unexpected server error occurred. Please try again.", "mobooking" ) ); ?>', false);
        }).always(function() {
            $submitButton.prop('disabled', false).text(originalButtonText);
        });
    });
});
</script>
