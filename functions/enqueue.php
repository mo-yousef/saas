<?php
/**
 * Enqueue scripts and styles.
 *
 * @package MoBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Enqueue scripts and styles.
 */
function mobooking_enqueue_scripts() {
    // Register Coloris
    wp_register_style( 'coloris', 'https://cdn.jsdelivr.net/npm/@melloware/coloris@0.25.0/dist/coloris.min.css', [], '0.25.0' );
    wp_register_script( 'coloris', 'https://cdn.jsdelivr.net/npm/@melloware/coloris@0.25.0/dist/coloris.min.js', [], '0.25.0', true );

    // Enqueue Coloris on the booking form settings page
    if ( get_query_var( 'mobooking_dashboard_page' ) === 'booking-form' ) {
        wp_enqueue_style( 'coloris' );
        wp_enqueue_script( 'coloris' );
    }
}
add_action( 'wp_enqueue_scripts', 'mobooking_enqueue_scripts' );
