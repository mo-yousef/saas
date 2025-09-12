<?php
/**
 * Class CustomerMonitoringPage
 *
 * Handles the display of the customer monitoring dashboard for site administrators.
 *
 * @package NORDBOOKING\Classes\Admin
 */

namespace NORDBOOKING\Classes\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CustomerMonitoringPage {

    /**
     * Registers the admin menu page.
     */
    public static function register_page() {
        add_menu_page(
            __( 'Client Monitoring', 'nordbooking' ),
            __( 'Client Monitoring', 'nordbooking' ),
            'manage_options',
            'nordbooking-client-monitoring',
            [ __CLASS__, 'render_page' ],
            'dashicons-visibility',
            30
        );
    }

    /**
     * Renders the content for the customer monitoring page.
     */
    public static function render_page() {
        $customers = self::get_all_customers();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e( 'Client Monitoring', 'nordbooking' ); ?></h1>
            <hr class="wp-header-end">
            <table class="wp-list-table widefat fixed striped table-view-list">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column column-primary"><?php _e( 'Name', 'nordbooking' ); ?></th>
                        <th scope="col" class="manage-column"><?php _e( 'Email', 'nordbooking' ); ?></th>
                        <th scope="col" class="manage-column"><?php _e( 'Phone', 'nordbooking' ); ?></th>
                        <th scope="col" class="manage-column"><?php _e( 'Business Owner', 'nordbooking' ); ?></th>
                        <th scope="col" class="manage-column"><?php _e( 'Subscription Status', 'nordbooking' ); ?></th>
                        <th scope="col" class="manage-column"><?php _e( 'Last Activity', 'nordbooking' ); ?></th>
                        <th scope="col" class="manage-column"><?php _e( 'Actions', 'nordbooking' ); ?></th>
                    </tr>
                </thead>
                <tbody id="the-list">
                    <?php if ( ! empty( $customers ) ) : ?>
                        <?php foreach ( $customers as $customer ) : ?>
                            <?php
                            $details_page_url = home_url('/dashboard/customer-details/?customer_id=' . $customer->id);
                            ?>
                            <tr>
                                <td class="column-primary">
                                    <strong><?php echo esc_html( $customer->full_name ); ?></strong>
                                </td>
                                <td><?php echo esc_html( $customer->email ); ?></td>
                                <td><?php echo esc_html( $customer->phone_number ); ?></td>
                                <td><?php echo esc_html( $customer->business_owner ); ?></td>
                                <td><?php echo esc_html( $customer->subscription_status ? ucfirst($customer->subscription_status) : 'None' ); ?></td>
                                <td><?php echo esc_html( $customer->last_activity_at ); ?></td>
                                <td>
                                    <a href="<?php echo esc_url( $details_page_url ); ?>" class="button"><?php _e( 'View Dashboard', 'nordbooking' ); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr class="no-items">
                            <td class="colspanchange" colspan="7"><?php _e( 'No customers found.', 'nordbooking' ); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Retrieves all customers from the database.
     *
     * @return array
     */
    private static function get_all_customers() {
        global $wpdb;
        $customers_table = \NORDBOOKING\Classes\Database::get_table_name('customers');
        $users_table = $wpdb->prefix . 'users';
        $subscriptions_table = \NORDBOOKING\Classes\Database::get_table_name('subscriptions');

        $sql = "
            SELECT
                c.id,
                c.full_name,
                c.email,
                c.phone_number,
                c.last_activity_at,
                u.display_name AS business_owner,
                s.status AS subscription_status
            FROM
                {$customers_table} c
            LEFT JOIN
                {$users_table} u ON c.tenant_id = u.ID
            LEFT JOIN
                {$subscriptions_table} s ON c.id = s.customer_id
            ORDER BY
                c.last_activity_at DESC
        ";

        return $wpdb->get_results( $sql );
    }

    /**
     * Enqueues scripts and styles for the admin page.
     *
     * @param string $hook The current admin page hook.
     */
    public static function enqueue_assets( $hook ) {
        // Our page hook is 'toplevel_page_nordbooking-client-monitoring'
        if ( 'toplevel_page_nordbooking-client-monitoring' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'nordbooking-customer-monitoring',
            NORDBOOKING_THEME_URI . 'assets/css/admin-customer-monitoring.css',
            [],
            NORDBOOKING_VERSION
        );
    }
}
