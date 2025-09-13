<?php
/**
 * Email template for trial expiration notification.
 *
 * @package NORDBOOKING
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>

<p><?php esc_html_e( 'Hi %%NAME%%,', 'NORDBOOKING' ); ?></p>

<p><?php esc_html_e( 'Your 7-day free trial of NORDBOOKING has ended.', 'NORDBOOKING' ); ?></p>

<p><?php esc_html_e( 'We hope you enjoyed the features and benefits of our platform. To continue using our services without interruption, please upgrade to a paid plan.', 'NORDBOOKING' ); ?></p>

<p><a href="%%UPGRADE_LINK%%"><?php esc_html_e( 'Upgrade Now', 'NORDBOOKING' ); ?></a></p>

<p><?php esc_html_e( 'If you have any questions, feel free to contact our support team.', 'NORDBOOKING' ); ?></p>

<p><?php printf( esc_html__( 'Thanks,', 'NORDBOOKING' ) ); ?><br><?php printf( esc_html__( 'The %s Team', 'NORDBOOKING' ), get_bloginfo( 'name' ) ); ?></p>
