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
                    <th scope="col"><?php esc_html_e( 'Role', 'mobooking' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Actions', 'mobooking' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $workers as $worker ) : ?>
                    <?php
                    $worker_mobooking_roles = [];
                    foreach($all_worker_roles as $role_key => $role_name) {
                        if (in_array($role_key, $worker->roles)) {
                            $worker_mobooking_roles[] = $role_name;
                        }
                    }
                    $current_worker_role_key = '';
                    foreach($all_worker_roles as $role_key => $role_name) {
                        if (in_array($role_key, $worker->roles)) {
                            $current_worker_role_key = $role_key;
                            break;
                        }
                    }
                    ?>
                    <tr id="worker-row-<?php echo esc_attr( $worker->ID ); ?>">
                        <td><?php echo esc_html( $worker->user_email ); ?></td>
                        <td class="worker-role-display">
                            <?php echo esc_html( !empty($worker_mobooking_roles) ? implode(', ', $worker_mobooking_roles) : 'N/A' ); ?>
                        </td>
                        <td>
                            <form class="mobooking-revoke-access-form" style="display: inline-block;">
                                <?php wp_nonce_field( 'mobooking_revoke_worker_access_nonce_' . $worker->ID, 'mobooking_revoke_access_nonce' ); ?>
                                <input type="hidden" name="action" value="mobooking_revoke_worker_access">
                                <input type="hidden" name="worker_user_id" value="<?php echo esc_attr( $worker->ID ); ?>">
                                <button type="submit" class="button button-link-delete mobooking-revoke-access-btn">
                                    <?php esc_html_e( 'Revoke Access', 'mobooking' ); ?>
                                </button>
                            </form>
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
});
</script>
