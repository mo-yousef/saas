<?php
/**
 * The Calendar page for the NORDBOOKING Dashboard.
 *
 * @package NORDBOOKING
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="nordbooking-dashboard-page-content">
    <h1 class="nordbooking-dashboard-page-title"><?php esc_html_e( 'Calendar', 'NORDBOOKING' ); ?></h1>
    <p class="nordbooking-dashboard-page-description"><?php esc_html_e( 'View and manage your bookings in a calendar format.', 'NORDBOOKING' ); ?></p>

    <div class="nordbooking-card">
        <div id="booking-calendar"></div>
    </div>
</div>

<style>
    #booking-calendar {
        /* max-width is removed to allow full width */
        margin: 40px 0; /* Adjust margin for full-width layout */
    }
</style>
