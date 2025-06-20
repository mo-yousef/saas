<?php
/**
 * Class UserManagementPage
 * Handles the registration and rendering of the MoBooking User Management admin page
 * within the WordPress dashboard. Allows administrators to view users with MoBooking roles,
 * change their MoBooking roles, and manage worker assignments to Business Owners.
 *
 * @package MoBooking\Classes\Admin
 */
namespace MoBooking\Classes\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class UserManagementPage {

    /**
     * Registers the admin menu and submenu pages for MoBooking User Management.
     * This method is typically hooked into 'admin_menu'.
     */
    public static function register_page() {
        // Add the main top-level menu page for MoBooking Admin.
        add_menu_page(
            __( 'MoBooking Admin', 'mobooking' ),        // Page title (visible in browser tab)
            __( 'MoBooking Admin', 'mobooking' ),        // Menu title (visible in sidebar)
            'manage_options',                            // Capability required to see this menu
            'mobooking-admin',                           // Menu slug (unique identifier)
            [ __CLASS__, 'render_main_page_content' ],   // Callback function to render the page content
            'dashicons-groups',                          // Icon for the menu item
            25                                           // Position in the menu order
        );

        // Add the User Management submenu page under the main MoBooking Admin menu.
        add_submenu_page(
            'mobooking-admin',                           // Parent slug (links to the main menu page)
            __( 'MoBooking User Management', 'mobooking' ), // Page title
            __( 'User Management', 'mobooking' ),        // Menu title
            'manage_options',                            // Capability
            'mobooking-user-management',                 // Menu slug
            [ __CLASS__, 'render_user_management_page_content' ] // Callback function
        );
    }

    /**
     * Renders the content for the main MoBooking Admin page.
     * This page serves as a placeholder or overview page for the MoBooking admin section.
     */
    public static function render_main_page_content() {
        ?>
        <div class="wrap">
            <h1><?php _e( 'MoBooking Admin', 'mobooking' ); ?></h1>
            <p><?php _e( 'Welcome to the MoBooking Admin area. Use the submenus to manage specific features.', 'mobooking' ); ?></p>
        </div>
        <?php
    }

    /**
     * Renders the content for the MoBooking User Management submenu page.
     * This method handles both the display of the user table and processing of form submissions
     * for role changes and owner assignments.
     */
    public static function render_user_management_page_content() {

        $auth_class = '\MoBooking\Classes\Auth'; // Shorthand for Auth class constants

        // --- Section: Process "Save Role" Form Submission ---
        // Check if the "Save Role" button was clicked and the nonce is valid.
        if ( isset( $_POST['mobooking_update_role_submit'] ) && check_admin_referer( 'mobooking_manage_user_roles_nonce', '_mobooking_nonce' ) ) {
            // Verify current user has 'manage_options' capability before proceeding.
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'Permission denied.', 'mobooking' ) );
            }

            // Get and sanitize the target user ID from the submit button's value.
            $target_user_id = intval( $_POST['mobooking_update_role_submit'] );
            // Get and sanitize the selected new role key from the corresponding dropdown.
            $new_role_key = isset($_POST['mobooking_role_change']['user_id_' . $target_user_id])
                            ? sanitize_text_field( $_POST['mobooking_role_change']['user_id_' . $target_user_id] )
                            : '';

            // Proceed only if a valid user ID and new role key are present.
            if ( $target_user_id > 0 && !empty($new_role_key) ) {
                $user = get_userdata( $target_user_id ); // Get WP_User object for the target user.
                if ( $user ) {
                    // Define all MoBooking role slugs to ensure only these are processed.
                    $all_mobooking_role_slugs_for_processing = [
                        $auth_class::ROLE_BUSINESS_OWNER, $auth_class::ROLE_WORKER_MANAGER,
                        $auth_class::ROLE_WORKER_STAFF, $auth_class::ROLE_WORKER_VIEWER,
                    ];

                    // Remove all existing MoBooking roles from the user before adding the new one.
                    foreach ( $all_mobooking_role_slugs_for_processing as $role_slug_to_remove ) {
                        if ( in_array( $role_slug_to_remove, $user->roles, true ) ) {
                            $user->remove_role( $role_slug_to_remove );
                        }
                    }

                    if ( $new_role_key === 'remove_mobooking_roles' ) {
                        // If "Remove MoBooking Roles" was selected.
                        delete_user_meta( $target_user_id, $auth_class::META_KEY_OWNER_ID ); // Also remove worker owner assignment.
                        if ( empty( $user->roles ) ) { $user->set_role( 'subscriber' ); } // If no roles left, set to default WordPress subscriber.
                        add_action( 'admin_notices', function() { echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'MoBooking roles removed successfully.', 'mobooking' ) . '</p></div>'; });
                    } elseif ( in_array( $new_role_key, $all_mobooking_role_slugs_for_processing, true ) ) {
                        // If a specific MoBooking role was selected, add it.
                        $user->add_role( $new_role_key );
                        // If the new role is Business Owner, ensure they are not marked as a worker for anyone.
                        if ( $new_role_key === $auth_class::ROLE_BUSINESS_OWNER ) {
                            delete_user_meta( $target_user_id, $auth_class::META_KEY_OWNER_ID );
                        }
                         add_action( 'admin_notices', function() { echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'User role updated successfully.', 'mobooking' ) . '</p></div>'; });
                    } else {
                        // Should not happen if dropdown is the only source of $new_role_key.
                        add_action( 'admin_notices', function() { echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Invalid role selected.', 'mobooking' ) . '</p></div>'; });
                    }
                } else { // User object not found for $target_user_id.
                     add_action( 'admin_notices', function() { echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Target user not found for role update.', 'mobooking' ) . '</p></div>'; });
                }
            } else { // Invalid $target_user_id or $new_role_key.
                 add_action( 'admin_notices', function() { echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Invalid action or user ID for role update.', 'mobooking' ) . '</p></div>'; });
            }
        }
        // --- End: Process "Save Role" Form Submission ---

        // --- Section: Process "Save Owner" Form Submission ---
        // Check if the "Save Owner" button was clicked and the nonce is valid.
        if ( isset( $_POST['mobooking_update_owner_submit'] ) && check_admin_referer( 'mobooking_manage_user_roles_nonce', '_mobooking_nonce' ) ) {
            // Verify current user has 'manage_options' capability.
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'Permission denied.', 'mobooking' ) );
            }

            // Get and sanitize the target worker user ID and the selected new owner ID.
            $worker_user_id = intval( $_POST['mobooking_update_owner_submit'] );
            $new_owner_id_input = isset($_POST['mobooking_assign_owner']['user_id_' . $worker_user_id])
                            ? sanitize_text_field( $_POST['mobooking_assign_owner']['user_id_' . $worker_user_id] )
                            : '';

            if ( $worker_user_id > 0 ) {
                $worker_user = get_userdata( $worker_user_id );
                if ( $worker_user ) {
                    // Check if the target user actually has a MoBooking worker role.
                    $is_actually_worker = false;
                    $worker_role_slugs = [$auth_class::ROLE_WORKER_MANAGER, $auth_class::ROLE_WORKER_STAFF, $auth_class::ROLE_WORKER_VIEWER];
                    foreach($worker_role_slugs as $w_slug) {
                        if(in_array($w_slug, $worker_user->roles)) {
                            $is_actually_worker = true;
                            break;
                        }
                    }

                    // An owner can only be assigned if the user has a worker role, unless the action is to remove an existing assignment.
                    if ( !$is_actually_worker && $new_owner_id_input !== '0' && $new_owner_id_input !== '') {
                         add_action( 'admin_notices', function() use ($worker_user) { echo '<div class="notice notice-error is-dismissible"><p>' . sprintf(esc_html__( 'User %s must have a MoBooking worker role (Manager, Staff, or Viewer) to be assigned a Business Owner. Please assign a worker role first.', 'mobooking' ), esc_html($worker_user->user_email)) . '</p></div>'; });
                    } else {
                        if ( $new_owner_id_input === '0' || $new_owner_id_input === '' ) {
                            // If "Remove Assignment" or empty value was selected.
                            delete_user_meta( $worker_user_id, $auth_class::META_KEY_OWNER_ID );
                            add_action( 'admin_notices', function() { echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Business Owner assignment removed.', 'mobooking' ) . '</p></div>'; });
                        } else {
                            // A specific owner ID was selected.
                            $new_owner_id = intval($new_owner_id_input);
                            $owner_user = get_userdata( $new_owner_id );
                            // Validate that the selected new owner is indeed a Business Owner.
                            if ( $owner_user && in_array( $auth_class::ROLE_BUSINESS_OWNER, $owner_user->roles, true ) ) {
                                update_user_meta( $worker_user_id, $auth_class::META_KEY_OWNER_ID, $new_owner_id );
                                add_action( 'admin_notices', function() { echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Business Owner assigned successfully.', 'mobooking' ) . '</p></div>'; });
                            } else { // Selected new owner is not a valid Business Owner.
                                add_action( 'admin_notices', function() { echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Invalid Business Owner selected. The selected user is not a Business Owner.', 'mobooking' ) . '</p></div>'; });
                            }
                        }
                    }
                } else { // Worker user object not found.
                    add_action( 'admin_notices', function() { echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Target worker user not found for owner assignment.', 'mobooking' ) . '</p></div>'; });
                }
            } else { // Invalid $worker_user_id.
                add_action( 'admin_notices', function() { echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Invalid action or user ID for owner assignment.', 'mobooking' ) . '</p></div>'; });
            }
        }
        // --- End: Process "Save Owner" Form Submission ---

        ?>
        <div class="wrap">
            <h1><?php _e( 'MoBooking User Management', 'mobooking' ); ?></h1>
            <?php do_action('admin_notices'); // Display any admin notices generated by form handling. ?>

            <h2><?php _e( 'All Users with MoBooking Roles', 'mobooking' ); ?></h2>
            <?php
            // --- Section: Data Fetching for User Table ---
            // Define MoBooking role slugs for querying users.
            $mobooking_role_slugs = [
                $auth_class::ROLE_BUSINESS_OWNER, $auth_class::ROLE_WORKER_MANAGER,
                $auth_class::ROLE_WORKER_STAFF, $auth_class::ROLE_WORKER_VIEWER,
            ];
            // Define worker-specific roles for easier checks later.
            $worker_role_slugs_only = [
                $auth_class::ROLE_WORKER_MANAGER, $auth_class::ROLE_WORKER_STAFF, $auth_class::ROLE_WORKER_VIEWER,
            ];

            // Get all users who have one of the MoBooking roles.
            $args = ['role__in' => $mobooking_role_slugs, 'orderby' => 'ID', 'order' => 'ASC'];
            $users_with_mobooking_roles = get_users($args);

            // Get all users who are Business Owners (for the owner assignment dropdown).
            $business_owners_args = ['role__in' => [$auth_class::ROLE_BUSINESS_OWNER]];
            $business_owners_list = get_users($business_owners_args);

            // Prepare an array of MoBooking role display names for use in the table and dropdowns.
            $wp_roles_instance = wp_roles();
            $all_mobooking_role_display_names = [];
            foreach ($mobooking_role_slugs as $slug) {
                if (isset($wp_roles_instance->role_names[$slug])) {
                    $all_mobooking_role_display_names[$slug] = $wp_roles_instance->role_names[$slug];
                } else { $all_mobooking_role_display_names[$slug] = $slug; } // Fallback to slug if name not found.
            }
            // --- End: Data Fetching for User Table ---
            ?>

            <!-- Start: Main form for role and owner updates -->
            <form method="post">
                <?php wp_nonce_field( 'mobooking_manage_user_roles_nonce', '_mobooking_nonce' ); // Nonce for the entire form. ?>

                <!-- Start: All MoBooking Users Table -->
                <table class="wp-list-table widefat striped users">
                    <thead>
                        <tr>
                            <th><?php _e( 'User', 'mobooking' ); ?></th>
                            <th><?php _e( 'MoBooking Role(s)', 'mobooking' ); ?></th>
                            <th><?php _e( 'Is Worker For (Owner)', 'mobooking' ); ?></th>
                            <th><?php _e( 'Actions', 'mobooking' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $users_with_mobooking_roles ) ) : ?>
                            <?php foreach ( $users_with_mobooking_roles as $user ) : ?>
                                <?php
                                // Determine if the current user in the loop is a worker type and their primary MoBooking role.
                                $user_is_worker = false;
                                $current_user_primary_mobooking_role = '';
                                $user_mobooking_roles_display_list = [];
                                foreach ( $user->roles as $role_slug ) {
                                    if ( in_array( $role_slug, $mobooking_role_slugs ) ) {
                                        $user_mobooking_roles_display_list[] = esc_html( $all_mobooking_role_display_names[$role_slug] );
                                        if (empty($current_user_primary_mobooking_role)) { $current_user_primary_mobooking_role = $role_slug; }
                                        if (in_array($role_slug, $worker_role_slugs_only)) { $user_is_worker = true; }
                                    }
                                }
                                ?>
                                <tr>
                                    <!-- User Column -->
                                    <td>
                                        <a href="<?php echo esc_url( get_edit_user_link( $user->ID ) ); ?>">
                                            <?php echo esc_html( $user->user_email ?: $user->user_login ); ?>
                                        </a>
                                    </td>
                                    <!-- MoBooking Role(s) Column -->
                                    <td><?php echo implode( ', ', $user_mobooking_roles_display_list ); ?></td>
                                    <!-- Is Worker For (Owner) Column -->
                                    <td>
                                        <?php
                                        $current_owner_id = get_user_meta( $user->ID, $auth_class::META_KEY_OWNER_ID, true );
                                        if ( $current_owner_id ) {
                                            $owner_data = get_userdata( $current_owner_id );
                                            echo esc_html( $owner_data ? ($owner_data->user_email ?: $owner_data->user_login) : __('Owner not found', 'mobooking') );
                                        } else {
                                            _e( 'N/A', 'mobooking' );
                                        }
                                        ?>
                                    </td>
                                    <!-- Actions Column -->
                                    <td>
                                        <!-- Role Change UI -->
                                        <div style="margin-bottom: 5px;">
                                            <select name="mobooking_role_change[user_id_<?php echo esc_attr($user->ID); ?>]" style="min-width: 150px;">
                                                <option value=""><?php _e( '-- Select Role --', 'mobooking' ); ?></option>
                                                <?php foreach ($all_mobooking_role_display_names as $slug => $name) : ?>
                                                    <option value="<?php echo esc_attr($slug); ?>" <?php selected($current_user_primary_mobooking_role, $slug); ?>>
                                                        <?php echo esc_html($name); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                                <option value="remove_mobooking_roles"><?php _e( 'Remove MoBooking Roles', 'mobooking' ); ?></option>
                                            </select>
                                            <button type="submit" name="mobooking_update_role_submit" value="<?php echo esc_attr($user->ID); ?>" class="button button-secondary button-small">
                                                <?php _e( 'Save Role', 'mobooking' ); ?>
                                            </button>
                                        </div>

                                        <!-- Owner Assignment UI (conditionally shown) -->
                                        <?php
                                        // Display owner assignment UI if the user is a worker type,
                                        // or if they are not a Business Owner (as they could be changed to a worker role).
                                        $can_have_owner = $user_is_worker || ($current_user_primary_mobooking_role !== $auth_class::ROLE_BUSINESS_OWNER);
                                        if ($can_have_owner) :
                                        ?>
                                            <div class="owner-assignment-links">
                                                <?php if ( $current_owner_id ) : ?>
                                                    <a href="#" class="mobooking-change-owner-link" data-user-id="<?php echo esc_attr($user->ID); ?>">(<?php _e('Change Owner', 'mobooking'); ?>)</a>
                                                <?php elseif ($user_is_worker) : // Only show "Assign Owner" if they are already a worker type and have no owner. ?>
                                                    <a href="#" class="mobooking-assign-owner-link" data-user-id="<?php echo esc_attr($user->ID); ?>">(<?php _e('Assign Owner', 'mobooking'); ?>)</a>
                                                <?php endif; ?>
                                            </div>
                                            <!-- Start: Owner Assignment Inline Form for user <?php echo esc_attr($user->ID); ?> -->
                                            <div id="owner-assignment-form-<?php echo esc_attr($user->ID); ?>" class="owner-assignment-form" style="display:none; margin-top:5px; padding: 5px; border: 1px solid #ccc; background: #f9f9f9;">
                                                <select name="mobooking_assign_owner[user_id_<?php echo esc_attr($user->ID); ?>]" style="min-width: 150px;">
                                                    <option value="0"><?php _e( '-- Remove Assignment --', 'mobooking' ); ?></option>
                                                    <?php foreach ($business_owners_list as $owner_option) : ?>
                                                        <option value="<?php echo esc_attr($owner_option->ID); ?>" <?php selected($current_owner_id, $owner_option->ID); ?>>
                                                            <?php echo esc_html( $owner_option->user_email ?: $owner_option->user_login ); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" name="mobooking_update_owner_submit" value="<?php echo esc_attr($user->ID); ?>" class="button button-secondary button-small">
                                                    <?php _e( 'Save Owner', 'mobooking' ); ?>
                                                </button>
                                                <a href="#" class="mobooking-cancel-owner-link" data-user-id="<?php echo esc_attr($user->ID); ?>" style="margin-left:5px;"><?php _e('Cancel', 'mobooking'); ?></a>
                                            </div>
                                            <!-- End: Owner Assignment Inline Form for user <?php echo esc_attr($user->ID); ?> -->
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="4"><?php _e( 'No users found with MoBooking roles.', 'mobooking' ); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <!-- End: All MoBooking Users Table -->
            </form>
            <!-- End: Main form -->

            <h2><?php _e( 'Manage Business Owners and Their Workers', 'mobooking' ); ?></h2>
            <p><em><?php _e( '(Business Owner list and worker management will be displayed here in a future update.)', 'mobooking' ); ?></em></p>
        </div>

        <?php // JavaScript for toggling the visibility of owner assignment forms. ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Show/hide the owner assignment form when "Change Owner" or "Assign Owner" is clicked.
                $('.mobooking-change-owner-link, .mobooking-assign-owner-link').on('click', function(e) {
                    e.preventDefault();
                    var userId = $(this).data('user-id');
                    $('#owner-assignment-form-' + userId).slideToggle('fast');
                    $(this).hide(); // Hide the link that was clicked.
                });

                // Hide the owner assignment form and show the "Change/Assign" link when "Cancel" is clicked.
                $('.mobooking-cancel-owner-link').on('click', function(e) {
                    e.preventDefault();
                    var userId = $(this).data('user-id');
                    $('#owner-assignment-form-' + userId).slideUp('fast');
                    // Make sure to show the correct link again (either "Change" or "Assign")
                    $('.owner-assignment-links[data-user-id="' + userId + '"] a').show(); // This might need refinement if both links exist structurally
                    // Simpler: just show all links in that container, CSS/PHP logic should ensure only one is visible initially.
                    $('.owner-assignment-links a[data-user-id="' + userId + '"]').show();

                });
            });
        </script>
        <?php
    }

    // Methods for handling form submissions or AJAX requests specific to this page will be added here.
}
?>
