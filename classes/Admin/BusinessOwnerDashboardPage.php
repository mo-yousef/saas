<?php
/**
 * Class BusinessOwnerDashboardPage
 *
 * Handles the display of the business owner monitoring dashboard for site administrators.
 *
 * @package NORDBOOKING\Classes\Admin
 */

namespace NORDBOOKING\Classes\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BusinessOwnerDashboardPage {

    /**
     * Registers the admin menu page.
     */
    public static function register_page() {
        add_menu_page(
            __( 'Business Owners', 'nordbooking' ),
            __( 'Business Owners', 'nordbooking' ),
            'manage_options',
            'nordbooking-business-owners',
            [ __CLASS__, 'render_page' ],
            'dashicons-businessperson',
            30
        );
    }

    /**
     * Renders the content for the business owner monitoring page.
     */
    public static function render_page() {
        $business_owners = self::get_business_owners();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e( 'Business Owners', 'nordbooking' ); ?></h1>
            <hr class="wp-header-end">
            <table class="wp-list-table widefat fixed striped table-view-list">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column column-primary"><?php _e( 'Display Name', 'nordbooking' ); ?></th>
                        <th scope="col" class="manage-column"><?php _e( 'Email', 'nordbooking' ); ?></th>
                        <th scope="col" class="manage-column"><?php _e( 'Registered', 'nordbooking' ); ?></th>
                        <th scope="col" class="manage-column"><?php _e( 'Actions', 'nordbooking' ); ?></th>
                    </tr>
                </thead>
                <tbody id="the-list">
                    <?php if ( ! empty( $business_owners ) ) : ?>
                        <?php foreach ( $business_owners as $user ) : ?>
                            <tr>
                                <td class="column-primary">
                                    <strong><?php echo esc_html( $user->display_name ); ?></strong>
                                </td>
                                <td><?php echo esc_html( $user->user_email ); ?></td>
                                <td><?php echo esc_html( date( get_option( 'date_format' ), strtotime( $user->user_registered ) ) ); ?></td>
                                <td>
                                    <?php
                                    $switch_url = wp_nonce_url(
                                        add_query_arg(
                                            [
                                                'action' => 'switch_to_user',
                                                'user_id' => $user->ID,
                                            ],
                                            admin_url( 'admin.php?page=nordbooking-business-owners' )
                                        ),
                                        'switch_to_user_' . $user->ID
                                    );
                                    ?>
                                    <a href="<?php echo esc_url( $switch_url ); ?>" class="button"><?php _e( 'Login as User', 'nordbooking' ); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr class="no-items">
                            <td class="colspanchange" colspan="4"><?php _e( 'No business owners found.', 'nordbooking' ); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Retrieves all business owners from the database.
     *
     * @return array
     */
    private static function get_business_owners() {
        $args = [
            'role__in' => ['nordbooking_business_owner'],
            'orderby' => 'user_registered',
            'order' => 'DESC',
        ];
        return get_users($args);
    }

    /**
     * Handles user switching.
     */
    public static function handle_user_switching() {
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'switch_to_user' && isset( $_GET['user_id'] ) ) {
            $user_id = (int) $_GET['user_id'];
            check_admin_referer( 'switch_to_user_' . $user_id );

            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( __( 'You do not have permission to do this.', 'nordbooking' ) );
            }

            $user = get_user_by( 'id', $user_id );
            if ( $user ) {
                $admin_id = get_current_user_id();
                set_transient( 'nordbooking_admin_id_' . $user_id, $admin_id, 3600 ); // Store admin ID for 1 hour

                wp_set_current_user( $user_id, $user->user_login );
                wp_set_auth_cookie( $user_id );
                do_action( 'wp_login', $user->user_login, $user );

                wp_redirect( home_url( '/dashboard/' ) );
                exit;
            }
        }
    }

    /**
     * Adds a "Switch Back" link to the admin bar when an admin is impersonating a user.
     */
    public static function add_switch_back_link( $wp_admin_bar ) {
        if ( ! is_user_logged_in() ) {
            return;
        }

        $user_id = get_current_user_id();
        $admin_id = get_transient( 'nordbooking_admin_id_' . $user_id );

        if ( $admin_id ) {
            $switch_back_url = wp_nonce_url(
                add_query_arg(
                    [
                        'action' => 'switch_back',
                    ],
                    admin_url( 'admin.php?page=nordbooking-business-owners' )
                ),
                'switch_back'
            );
            $wp_admin_bar->add_node(
                [
                    'id'    => 'switch_back',
                    'title' => __( 'Switch Back to Admin', 'nordbooking' ),
                    'href'  => $switch_back_url,
                    'meta'  => ['class' => 'switch-back-link'],
                ]
            );
        }
    }

    /**
     * Handles switching back to the admin user.
     */
    public static function handle_switch_back() {
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'switch_back' ) {
            check_admin_referer( 'switch_back' );

            $user_id = get_current_user_id();
            $admin_id = get_transient( 'nordbooking_admin_id_' . $user_id );

            if ( $admin_id ) {
                $admin = get_user_by( 'id', $admin_id );
                if ( $admin ) {
                    delete_transient( 'nordbooking_admin_id_' . $user_id );
                    wp_set_current_user( $admin_id, $admin->user_login );
                    wp_set_auth_cookie( $admin_id );
                    do_action( 'wp_login', $admin->user_login, $admin );
                }
            }

            wp_redirect( admin_url( 'admin.php?page=nordbooking-business-owners' ) );
            exit;
        }
    }
}
