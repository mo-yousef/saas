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
        if ( isset( $_POST['mobooking_update_role_submit'] ) && isset( $_POST['mobooking_target_user_id'] ) && check_admin_referer( 'mobooking_manage_user_roles_nonce', '_mobooking_nonce' ) ) {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'Permission denied.', 'mobooking' ) );
            }

            $target_user_id = intval( $_POST['mobooking_target_user_id'] );
            $new_role_key = isset( $_POST['mobooking_role_change_generic'] ) ? sanitize_text_field( $_POST['mobooking_role_change_generic'] ) : '';

            if ( $target_user_id > 0 && ! empty( $new_role_key ) ) {
                $user = get_userdata( $target_user_id ); // Get WP_User object for the target user.
                if ( $user ) {
                    // Define all MoBooking role slugs to ensure only these are processed.
                    $all_mobooking_role_slugs_for_processing = [
                        $auth_class::ROLE_BUSINESS_OWNER,
                        $auth_class::ROLE_WORKER_STAFF,
                    ];
                    // Add legacy roles to ensure they are cleaned up if present
                    $legacy_roles_to_remove = ['mobooking_worker_manager', 'mobooking_worker_viewer'];
                    $roles_to_iterate_for_removal = array_unique(array_merge($all_mobooking_role_slugs_for_processing, $legacy_roles_to_remove));


                    // Remove all existing MoBooking roles from the user before adding the new one.
                    foreach ( $roles_to_iterate_for_removal as $role_slug_to_remove ) {
                        if ( in_array( $role_slug_to_remove, $user->roles, true ) ) {
                            $user->remove_role( $role_slug_to_remove );
                        }
                    }

                    if ( $new_role_key === 'remove_mobooking_roles' ) {
                        // If "Remove MoBooking Roles" was selected.
                        delete_user_meta( $target_user_id, $auth_class::META_KEY_OWNER_ID ); // Also remove worker owner assignment.
                        if ( empty( $user->roles ) ) { $user->set_role( 'subscriber' ); } // If no roles left, set to default WordPress subscriber.
                        add_action( 'admin_notices', function() { echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'MoBooking roles removed successfully.', 'mobooking' ) . '</p></div>'; });
                    } elseif ( in_array( $new_role_key, $all_mobooking_role_slugs_for_processing, true ) ) { // Ensure new role is one of the current valid MoBooking roles
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
        if ( isset( $_POST['mobooking_update_owner_submit'] ) && isset( $_POST['mobooking_target_user_id'] ) && check_admin_referer( 'mobooking_manage_user_roles_nonce', '_mobooking_nonce' ) ) {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'Permission denied.', 'mobooking' ) );
            }

            $target_worker_user_id = intval( $_POST['mobooking_target_user_id'] );
            $new_owner_id_input = isset( $_POST['mobooking_assign_owner_generic'] ) ? sanitize_text_field( $_POST['mobooking_assign_owner_generic'] ) : '';

            if ( $target_worker_user_id > 0 ) {
                $worker_user = get_userdata( $target_worker_user_id );
                if ( $worker_user ) {
                    $is_actually_worker = false;
                    // Only ROLE_WORKER_STAFF is a valid worker role now
                    $worker_role_slugs = [$auth_class::ROLE_WORKER_STAFF];
                    foreach ( $worker_role_slugs as $w_slug ) {
                        if ( in_array( $w_slug, $worker_user->roles ) ) {
                            $is_actually_worker = true;
                            break;
                        }
                    }

                    if ( ! $is_actually_worker && $new_owner_id_input !== '0' && $new_owner_id_input !== '' ) {
                        add_action( 'admin_notices', function () use ( $worker_user ) {
                            echo '<div class="notice notice-error is-dismissible"><p>' . sprintf( esc_html__( 'User %s must have the MoBooking Worker Staff role to be assigned a Business Owner. Please assign the Worker Staff role first.', 'mobooking' ), esc_html( $worker_user->user_email ) ) . '</p></div>';
                        } );
                    } else {
                        if ( $new_owner_id_input === '0' || $new_owner_id_input === '' ) {
                            delete_user_meta( $target_worker_user_id, $auth_class::META_KEY_OWNER_ID );
                            add_action( 'admin_notices', function () {
                                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Business Owner assignment removed.', 'mobooking' ) . '</p></div>';
                            } );
                        } else {
                            $new_owner_id = intval( $new_owner_id_input );
                            $owner_user = get_userdata( $new_owner_id );
                            if ( $owner_user && in_array( $auth_class::ROLE_BUSINESS_OWNER, $owner_user->roles, true ) ) {
                                update_user_meta( $target_worker_user_id, $auth_class::META_KEY_OWNER_ID, $new_owner_id );
                                add_action( 'admin_notices', function () {
                                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Business Owner assigned successfully.', 'mobooking' ) . '</p></div>';
                                } );
                            } else {
                                add_action( 'admin_notices', function () {
                                    echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Invalid Business Owner selected. The selected user is not a Business Owner.', 'mobooking' ) . '</p></div>';
                                } );
                            }
                        }
                    }
                } else {
                    add_action( 'admin_notices', function () {
                        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Target worker user not found for owner assignment.', 'mobooking' ) . '</p></div>';
                    } );
                }
            } else {
                add_action( 'admin_notices', function () {
                    echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Invalid action or user ID for owner assignment.', 'mobooking' ) . '</p></div>';
                } );
            }
        }
        // --- End: Process "Save Owner" Form Submission ---

        // --- Section: Process "Create New Worker Staff" Form Submission ---
        if ( isset( $_POST['mobooking_create_worker_staff_submit'] ) && check_admin_referer( 'mobooking_create_worker_staff_nonce', '_mobooking_create_staff_nonce' ) ) {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'Permission denied.', 'mobooking' ) );
            }

            $new_staff_email = isset( $_POST['mobooking_new_staff_email'] ) ? sanitize_email( $_POST['mobooking_new_staff_email'] ) : '';
            $new_staff_password = isset( $_POST['mobooking_new_staff_password'] ) ? $_POST['mobooking_new_staff_password'] : ''; // Password will be used by wp_insert_user, which handles its own hashing.
            $new_staff_first_name = isset( $_POST['mobooking_new_staff_first_name'] ) ? sanitize_text_field( $_POST['mobooking_new_staff_first_name'] ) : '';
            $new_staff_last_name = isset( $_POST['mobooking_new_staff_last_name'] ) ? sanitize_text_field( $_POST['mobooking_new_staff_last_name'] ) : '';
            $selected_owner_id = isset( $_POST['mobooking_new_staff_owner_id'] ) ? intval( $_POST['mobooking_new_staff_owner_id'] ) : 0;

            $errors = new \WP_Error();

            if ( empty( $new_staff_email ) ) {
                $errors->add( 'empty_email', __( 'Email address is required.', 'mobooking' ) );
            } elseif ( ! is_email( $new_staff_email ) ) {
                $errors->add( 'invalid_email', __( 'Invalid email address.', 'mobooking' ) );
            }
            if ( email_exists( $new_staff_email ) ) {
                $errors->add( 'email_exists', __( 'This email address is already registered.', 'mobooking' ) );
            }
            if ( username_exists( $new_staff_email ) ) { // Assuming username is the email
                $errors->add( 'username_exists', __( 'A user with this email as username already exists.', 'mobooking' ) );
            }
            if ( empty( $new_staff_password ) ) {
                $errors->add( 'empty_password', __( 'Password is required.', 'mobooking' ) );
            }
            // Basic password length check (WordPress default is 7 characters, but wp_insert_user doesn't enforce this directly)
            if ( !empty( $new_staff_password ) && strlen( $new_staff_password ) < 7 ) {
                $errors->add( 'password_length', __( 'Password must be at least 7 characters long.', 'mobooking' ) );
            }
            if ( empty( $selected_owner_id ) ) {
                $errors->add( 'empty_owner', __( 'Assigning a Business Owner is required.', 'mobooking' ) );
            } else {
                $owner_user_data = get_userdata( $selected_owner_id );
                if ( ! $owner_user_data || ! in_array( $auth_class::ROLE_BUSINESS_OWNER, $owner_user_data->roles, true ) ) {
                    $errors->add( 'invalid_owner', __( 'The selected Business Owner is not valid.', 'mobooking' ) );
                }
            }

            if ( $errors->has_errors() ) {
                foreach ( $errors->get_error_messages() as $message ) {
                    add_action( 'admin_notices', function() use ( $message ) {
                        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
                    });
                }
            } else {
                // All checks passed, create the user
                $user_data = array(
                    'user_login' => $new_staff_email,
                    'user_email' => $new_staff_email,
                    'user_pass'  => $new_staff_password,
                    'first_name' => $new_staff_first_name,
                    'last_name'  => $new_staff_last_name,
                    'role'       => $auth_class::ROLE_WORKER_STAFF,
                );
                $new_user_id = wp_insert_user( $user_data );

                if ( is_wp_Error( $new_user_id ) ) {
                    add_action( 'admin_notices', function() use ( $new_user_id ) {
                        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $new_user_id->get_error_message() ) . '</p></div>';
                    });
                } else {
                    // User created successfully, assign the owner meta
                    update_user_meta( $new_user_id, $auth_class::META_KEY_OWNER_ID, $selected_owner_id );
                    add_action( 'admin_notices', function() {
                        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Worker Staff user created successfully.', 'mobooking' ) . '</p></div>';
                    });
                    // Clear POST data to prevent re-submission or to clear form fields - typically handled by redirect, but for now, just a notice.
                    // Consider adding: $_POST = array();
                }
            }
        }
        // --- End: Process "Create New Worker Staff" Form Submission ---

        ?>
        <div class="wrap">
            <h1><?php _e( 'MoBooking User Management', 'mobooking' ); ?></h1>
            <?php do_action('admin_notices'); ?>

            <style>
                .mobooking-user-tree ul { list-style-type: none; padding-left: 20px; }
                .mobooking-user-tree li { margin-bottom: 5px; padding: 5px; border-left: 1px solid #ccc; }
                .mobooking-user-tree .owner-item > .user-info { font-weight: bold; }
                .mobooking-user-tree .worker-list { margin-top: 5px; border-left: 1px dashed #eee; padding-left: 15px; }
                .mobooking-user-tree .toggle-workers { cursor: pointer; margin-right: 5px; font-size: 0.8em; }
                .mobooking-user-tree .user-actions a { margin-left: 10px; }
                #mobooking-user-management-actions { margin-top: 20px; padding: 15px; border: 1px solid #ddd; background: #f5f5f5; display: none; }
                #mobooking-user-management-actions h3 { margin-top: 0; }
            </style>

            <h2><?php _e( 'User Hierarchy', 'mobooking' ); ?></h2>
            <?php
            // Define roles for display and logic, now simplified
            $all_mobooking_roles_display = [
                $auth_class::ROLE_BUSINESS_OWNER => __( 'Business Owner', 'mobooking' ),
                $auth_class::ROLE_WORKER_STAFF   => __( 'Worker Staff', 'mobooking' ),
            ];
            // This variable might be used by JS or other parts if they specifically need to know what constitutes a "worker"
            $worker_role_slugs_only = [
                $auth_class::ROLE_WORKER_STAFF,
            ];

            $business_owners_args = ['role__in' => [$auth_class::ROLE_BUSINESS_OWNER], 'orderby' => 'ID', 'order' => 'ASC'];
            $business_owners_list = get_users($business_owners_args);
            ?>
            <div class="mobooking-user-tree">
                <ul>
                    <?php if ( ! empty( $business_owners_list ) ) : ?>
                        <?php foreach ( $business_owners_list as $owner ) : ?>
                            <li class="owner-item">
                                <span class="toggle-workers">▶</span>
                                <span class="user-info">
                                    <?php echo esc_html( $owner->display_name ?: $owner->user_login ); ?> (<?php echo esc_html( $owner->user_email ); ?>) - <?php echo esc_html( $all_mobooking_roles_display[$auth_class::ROLE_BUSINESS_OWNER] ); ?>
                                </span>
                                <span class="user-actions">
                                    <a href="<?php echo esc_url( get_edit_user_link( $owner->ID ) ); ?>" target="_blank"><?php _e('View Profile', 'mobooking'); ?></a>
                                    <a href="#" class="manage-user-link"
                                       data-user-id="<?php echo esc_attr($owner->ID); ?>"
                                       data-user-name="<?php echo esc_attr($owner->display_name ?: $owner->user_email); ?>"
                                       data-current-role="<?php echo esc_attr($auth_class::ROLE_BUSINESS_OWNER); ?>"
                                       data-current-owner-id="">
                                        <?php _e('Manage', 'mobooking'); ?>
                                    </a>
                                </span>
                                <?php
                                $workers_args = [
                                    'meta_key' => $auth_class::META_KEY_OWNER_ID,
                                    'meta_value' => $owner->ID,
                                    'orderby' => 'ID',
                                    'order' => 'ASC'
                                ];
                                $workers = get_users( $workers_args );
                                ?>
                                <ul class="worker-list" style="display: none;">
                                    <?php if ( ! empty( $workers ) ) : ?>
                                        <?php foreach ( $workers as $worker ) : ?>
                                            <?php
                                            $worker_role_name = __('N/A', 'mobooking');
                                            $current_worker_primary_role = '';
                                            foreach ($worker->roles as $role_slug) {
                                                if (isset($all_mobooking_roles_display[$role_slug])) {
                                                    $worker_role_name = $all_mobooking_roles_display[$role_slug];
                                                    $current_worker_primary_role = $role_slug;
                                                    break;
                                                }
                                            }
                                            ?>
                                            <li>
                                                <span class="user-info">
                                                    <?php echo esc_html( $worker->display_name ?: $worker->user_login ); ?> (<?php echo esc_html( $worker->user_email ); ?>) - <?php echo esc_html( $worker_role_name ); ?>
                                                </span>
                                                <span class="user-actions">
                                                    <a href="<?php echo esc_url( get_edit_user_link( $worker->ID ) ); ?>" target="_blank"><?php _e('View Profile', 'mobooking'); ?></a>
                                                    <a href="#" class="manage-user-link"
                                                       data-user-id="<?php echo esc_attr($worker->ID); ?>"
                                                       data-user-name="<?php echo esc_attr($worker->display_name ?: $worker->user_email); ?>"
                                                       data-current-role="<?php echo esc_attr($current_worker_primary_role); ?>"
                                                       data-current-owner-id="<?php echo esc_attr($owner->ID); ?>">
                                                        <?php _e('Manage', 'mobooking'); ?>
                                                    </a>
                                                </span>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <li><?php _e( 'No workers found for this owner.', 'mobooking' ); ?></li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <li><?php _e( 'No Business Owners found.', 'mobooking' ); ?></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Hidden Management Section -->
            <div id="mobooking-user-management-actions" style="display:none; margin-top: 30px; padding: 20px; border: 1px solid #ccd0d4; background-color: #f6f7f7;">
                <h3><?php _e('Manage User:', 'mobooking'); ?> <span id="managing-user-name"></span></h3>
                <form method="post" id="mobooking-generic-role-form">
                    <?php wp_nonce_field( 'mobooking_manage_user_roles_nonce', '_mobooking_nonce' ); ?>
                    <input type="hidden" name="mobooking_target_user_id" id="mobooking_role_target_user_id" value="">
                    <h4><?php _e('Change Role', 'mobooking'); ?></h4>
                    <select name="mobooking_role_change_generic" id="mobooking_role_change_generic_select">
                        <option value=""><?php _e( '-- Select Role --', 'mobooking' ); ?></option>
                        <?php foreach ($all_mobooking_roles_display as $slug => $name) : ?>
                            <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($name); ?></option>
                        <?php endforeach; ?>
                        <option value="remove_mobooking_roles"><?php _e( 'Remove MoBooking Roles', 'mobooking' ); ?></option>
                    </select>
                    <button type="submit" name="mobooking_update_role_submit" id="mobooking_update_role_submit_button" value="" class="button button-primary">
                        <?php _e( 'Save Role', 'mobooking' ); ?>
                    </button>
                </form>
                <hr>
                <form method="post" id="mobooking-generic-owner-form" style="margin-top:15px;">
                     <?php wp_nonce_field( 'mobooking_manage_user_roles_nonce', '_mobooking_nonce' ); // Re-use nonce if appropriate, or create specific one ?>
                    <input type="hidden" name="mobooking_target_user_id" id="mobooking_owner_target_user_id" value="">
                    <h4><?php _e('Assign/Change Business Owner (for workers)', 'mobooking'); ?></h4>
                    <p><small><?php _e('This only applies if the user is a worker. Assigning an owner to a Business Owner will have no effect or may be cleared if their role is Business Owner.', 'mobooking');?></small></p>
                    <select name="mobooking_assign_owner_generic" id="mobooking_assign_owner_generic_select">
                        <option value="0"><?php _e( '-- Remove/No Assignment --', 'mobooking' ); ?></option>
                        <?php
                        // Ensure $business_owners_list is available or re-fetch if needed for this scope
                        // For now, assuming $business_owners_list fetched for the tree is still in scope.
                        // If not, it might be better to pass this list via JS data attributes or fetch via AJAX.
                        if ( ! empty( $business_owners_list ) ) {
                            foreach ($business_owners_list as $owner_option) {
                                echo '<option value="' . esc_attr($owner_option->ID) . '">' . esc_html( $owner_option->user_email ?: $owner_option->user_login ) . '</option>';
                            }
                        }
                        ?>
                    </select>
                    <button type="submit" name="mobooking_update_owner_submit" id="mobooking_update_owner_submit_button" value="" class="button button-primary">
                        <?php _e( 'Save Owner Assignment', 'mobooking' ); ?>
                    </button>
                </form>
                 <button id="close-management-section" class="button" style="margin-top:15px;"><?php _e('Close Management Panel', 'mobooking'); ?></button>
            </div>
            <!-- End Hidden Management Section -->

            <!-- Start: Create New Worker Staff Form -->
            <h2><?php _e( 'Create New Worker Staff', 'mobooking' ); ?></h2>
            <form method="post">
                <?php wp_nonce_field( 'mobooking_create_worker_staff_nonce', '_mobooking_create_staff_nonce' ); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <label for="mobooking_new_staff_email"><?php _e( 'User Email', 'mobooking' ); ?></label>
                        </th>
                        <td>
                            <input type="email" id="mobooking_new_staff_email" name="mobooking_new_staff_email" class="regular-text" required />
                            <p class="description"><?php _e( 'Required. This will also be their username.', 'mobooking' ); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="mobooking_new_staff_password"><?php _e( 'Password', 'mobooking' ); ?></label>
                        </th>
                        <td>
                            <input type="password" id="mobooking_new_staff_password" name="mobooking_new_staff_password" class="regular-text" required />
                             <p class="description"><?php _e( 'Required. Minimum 7 characters.', 'mobooking' ); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="mobooking_new_staff_first_name"><?php _e( 'First Name', 'mobooking' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="mobooking_new_staff_first_name" name="mobooking_new_staff_first_name" class="regular-text" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="mobooking_new_staff_last_name"><?php _e( 'Last Name', 'mobooking' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="mobooking_new_staff_last_name" name="mobooking_new_staff_last_name" class="regular-text" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="mobooking_new_staff_owner_id"><?php _e( 'Assign to Business Owner', 'mobooking' ); ?></label>
                        </th>
                        <td>
                            <select id="mobooking_new_staff_owner_id" name="mobooking_new_staff_owner_id" required>
                                <option value=""><?php _e( '-- Select Business Owner --', 'mobooking' ); ?></option>
                                <?php if ( ! empty( $business_owners_list ) ) : ?>
                                    <?php foreach ( $business_owners_list as $owner ) : ?>
                                        <option value="<?php echo esc_attr( $owner->ID ); ?>">
                                            <?php echo esc_html( $owner->user_email ?: $owner->user_login ); ?> (ID: <?php echo esc_html($owner->ID); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <option value="" disabled><?php _e( 'No Business Owners found.', 'mobooking' ); ?></option>
                                <?php endif; ?>
                            </select>
                            <p class="description"><?php _e( 'Required. Select the Business Owner this staff member will be associated with.', 'mobooking' ); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button( __( 'Create Worker Staff', 'mobooking' ), 'primary', 'mobooking_create_worker_staff_submit' ); ?>
            </form>
            <!-- End: Create New Worker Staff Form -->

            <h2><?php _e( 'Manage Business Owners and Their Workers', 'mobooking' ); ?></h2>
            <p><em><?php _e( '(This section can be used for additional summary or actions related to owners and workers in the future.)', 'mobooking' ); ?></em></p>
        </div>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Tree view toggle
                $('.mobooking-user-tree .toggle-workers').on('click', function() {
                    var $this = $(this);
                    $this.nextAll('.worker-list').slideToggle('fast');
                    if ($this.text() === '▶') {
                        $this.text('▼');
                    } else {
                        $this.text('▶');
                    }
                });

                // Manage user link
                $('.manage-user-link').on('click', function(e) {
                    e.preventDefault();
                    var userId = $(this).data('user-id');
                    var userName = $(this).data('user-name');
                    var currentRole = $(this).data('current-role');
                    var currentOwnerId = $(this).data('current-owner-id');

                    $('#managing-user-name').text(userName + ' (ID: ' + userId + ')');

                    // Populate Role Form
                    $('#mobooking_role_target_user_id').val(userId);
                    $('#mobooking_update_role_submit_button').val(userId); // Keep this for existing PHP handler compatibility
                    $('#mobooking_role_change_generic_select').val(currentRole);

                    // Populate Owner Assignment Form
                    $('#mobooking_owner_target_user_id').val(userId);
                    $('#mobooking_update_owner_submit_button').val(userId); // Keep this for existing PHP handler compatibility
                    $('#mobooking_assign_owner_generic_select').val(currentOwnerId || '0');


                    // Show the management section
                    var managementSection = $('#mobooking-user-management-actions');
                    managementSection.slideDown('fast');
                    $('html, body').animate({
                        scrollTop: managementSection.offset().top - 50 // 50px offset for admin bar or other fixed headers
                    }, 500);
                });

                $('#close-management-section').on('click', function() {
                    $('#mobooking-user-management-actions').slideUp('fast');
                });

                // Existing JS for owner assignment forms might need removal or adaptation if those specific forms are gone.
                // For now, the generic forms will use the main page submit, handled by PHP.
                // The old .mobooking-change-owner-link, .mobooking-assign-owner-link JS can be removed if those links are no longer used.
                // Let's remove them to avoid conflicts.
                // $('.mobooking-change-owner-link, .mobooking-assign-owner-link').off('click');
                // $('.mobooking-cancel-owner-link').off('click');
                // It's better to remove the old HTML elements that these were attached to.
            });
        </script>
        <?php
    }

    public static function render_customer_details_page() {
        if ( ! current_user_can( 'mobooking_view_customers' ) ) {
            wp_die( esc_html__( 'You do not have permission to view this page.', 'mobooking' ) );
        }

        $customer_id = isset( $_GET['customer_id'] ) ? intval( $_GET['customer_id'] ) : 0;
        if ( ! $customer_id ) {
            wp_die( esc_html__( 'Invalid customer ID.', 'mobooking' ) );
        }

        // Include the customer details template
        include_once MOBOOKING_THEME_DIR . 'dashboard/page-customer-details.php';
    }

    // Methods for handling form submissions or AJAX requests specific to this page will be added here.
}
?>
