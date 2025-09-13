<?php
/**
 * Email template for subscription renewal reminder.
 *
 * @package NORDBOOKING
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>

<p><?php esc_html_e( 'Hi %%NAME%%,', 'NORDBOOKING' ); ?></p>

<p><?php esc_html_e( 'Your subscription to NORDBOOKING is expiring in 2 days.', 'NORDBOOKING' ); ?></p>

<p><?php esc_html_e( 'To ensure uninterrupted access to our services, please renew your subscription.', 'NORDBOOKING' ); ?></p>

<p><a href="%%RENEWAL_LINK%%"><?php esc_html_e( 'Renew Now', 'NORDBOOKING' ); ?></a></p>

<p><?php esc_html_e( 'If you have any questions, feel free to contact our support team.', 'NORDBOOKING' ); ?></p>

<p><?php printf( esc_html__( 'Thanks,', 'NORDBOOKING' ) ); ?><br><?php printf( esc_html__( 'The %s Team', 'NORDBOOKING' ), get_bloginfo( 'name' ) ); ?></p>
