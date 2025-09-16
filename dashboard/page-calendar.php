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

    <div class="calendar-layout-container">
        <div class="nordbooking-card calendar-main-content">
            <div id="booking-calendar"></div>
        </div>
        <aside id="calendar-sidebar" class="calendar-sidebar">
            <div class="sidebar-header">
                <h3 id="sidebar-title" class="sidebar-title"></h3>
                <button id="sidebar-close-btn" class="sidebar-close-btn">&times;</button>
            </div>
            <div id="sidebar-content" class="sidebar-content">
                <!-- Dynamic content will be injected here -->
            </div>
        </aside>
    </div>
</div>

<style>
    .calendar-layout-container {
        display: flex;
        gap: 1.5rem;
        align-items: flex-start;
    }
    .calendar-main-content {
        flex: 1;
        min-width: 0; /* Prevents flex item from overflowing */
    }
    #booking-calendar {
        padding: 1.5rem;
    }
    .calendar-sidebar {
        width: 350px; /* Sidebar width */
        flex-shrink: 0;
        transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out;
        /* Initially hidden */
        transform: translateX(20px);
        opacity: 0;
        pointer-events: none;
    }
    .calendar-sidebar.is-visible {
        transform: translateX(0);
        opacity: 1;
        pointer-events: auto;
    }
</style>
