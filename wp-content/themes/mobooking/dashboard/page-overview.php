<?php
/**
 * Dashboard Page: Overview
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$user = wp_get_current_user();
?>
<h1><?php printf(esc_html__('Welcome to Your Dashboard, %s!', 'mobooking'), esc_html($user->display_name)); ?></h1>
<p><?php esc_html_e('Here you can get a quick glance at your business activity and manage your services, bookings, and settings.', 'mobooking'); ?></p>

<div id="mobooking-overview-content" style="margin-top:20px;">

    <div id="mobooking-kpi-boxes" style="display:flex; flex-wrap:wrap; gap:20px; margin-bottom:30px;">
        <div class="kpi-box" style="background:#fff; padding:20px; border:1px solid #ddd; flex:1; min-width:200px; border-radius:4px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h4 style="margin-top:0; color:#555; font-size:15px;"><?php esc_html_e('Bookings This Month', 'mobooking'); ?></h4>
            <p id="kpi-bookings-month" style="font-size:24px; font-weight:bold; color:#333; margin-bottom:0;">...</p>
        </div>
        <div class="kpi-box" style="background:#fff; padding:20px; border:1px solid #ddd; flex:1; min-width:200px; border-radius:4px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h4 style="margin-top:0; color:#555; font-size:15px;"><?php esc_html_e('Est. Revenue This Month', 'mobooking'); ?></h4>
            <p id="kpi-revenue-month" style="font-size:24px; font-weight:bold; color:#333; margin-bottom:0;">...</p>
        </div>
        <div class="kpi-box" style="background:#fff; padding:20px; border:1px solid #ddd; flex:1; min-width:200px; border-radius:4px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h4 style="margin-top:0; color:#555; font-size:15px;"><?php esc_html_e('Upcoming Confirmed Bookings', 'mobooking'); ?></h4>
            <p id="kpi-upcoming-count" style="font-size:24px; font-weight:bold; color:#333; margin-bottom:0;">...</p>
        </div>
    </div>

    <div id="mobooking-overview-columns" style="display:flex; flex-wrap:wrap; gap:30px;">
        <div id="mobooking-overview-recent-bookings-section" style="flex:2; min-width:300px; background:#fff; padding:20px; border:1px solid #ddd; border-radius:4px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h3 style="border-bottom:1px solid #eee; padding-bottom:10px; margin-top:0;"><?php esc_html_e('Recent & Upcoming Bookings', 'mobooking'); ?></h3>
            <div id="mobooking-overview-recent-bookings">
                <p><?php esc_html_e('Loading recent bookings...', 'mobooking'); ?></p>
            </div>
        </div>
        <div id="mobooking-overview-quick-actions-section" style="flex:1; min-width:200px; background:#fff; padding:20px; border:1px solid #ddd; border-radius:4px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h3 style="border-bottom:1px solid #eee; padding-bottom:10px; margin-top:0;"><?php esc_html_e('Quick Actions', 'mobooking'); ?></h3>
            <ul style="list-style:none; padding-left:0;">
                <li style="margin-bottom:10px;"><a href="<?php echo esc_url(home_url('/dashboard/bookings/')); ?>" class="button button-secondary button-large" style="width:100%; text-align:center;"><?php esc_html_e('View All Bookings', 'mobooking'); ?></a></li>
                <li style="margin-bottom:10px;"><a href="<?php echo esc_url(home_url('/dashboard/services/')); ?>" class="button button-secondary button-large" style="width:100%; text-align:center;"><?php esc_html_e('Manage Services', 'mobooking'); ?></a></li>
                <li style="margin-bottom:10px;"><a href="<?php echo esc_url(home_url('/dashboard/discounts/')); ?>" class="button button-secondary button-large" style="width:100%; text-align:center;"><?php esc_html_e('Manage Discounts', 'mobooking'); ?></a></li>
                 <li style="margin-bottom:10px;"><a href="<?php echo esc_url(home_url('/dashboard/areas/')); ?>" class="button button-secondary button-large" style="width:100%; text-align:center;"><?php esc_html_e('Service Areas', 'mobooking'); ?></a></li>
                <hr>
                <li style="margin-bottom:10px; margin-top:15px;"><a href="<?php echo esc_url(home_url('/dashboard/booking-form/')); ?>" class="button button-secondary button-large" style="width:100%; text-align:center;"><?php esc_html_e('Booking Form Settings', 'mobooking'); ?></a></li>
                <li style="margin-bottom:10px;"><a href="<?php echo esc_url(home_url('/dashboard/settings/')); ?>" class="button button-secondary button-large" style="width:100%; text-align:center;"><?php esc_html_e('Business Settings', 'mobooking'); ?></a></li>
            </ul>
        </div>
    </div>
</div>

<script type="text/template" id="mobooking-overview-booking-item-template">
    <div class="mobooking-overview-booking-item" style="border-bottom:1px solid #f0f0f0; padding:10px 0; margin-bottom:10px;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <strong style="font-size:1.1em;">Ref: <%= booking_reference %></strong> - <%= customer_name %><br>
                <span style="font-size:0.9em; color:#555;">Date: <%= booking_date %> <%= booking_time %></span>
            </div>
            <div style="text-align:right;">
                <span class="status-<%= status %>" style="font-weight:bold; padding: 3px 6px; border-radius: 3px; background-color: #eee; display:inline-block; margin-bottom:5px;"><%= status_display %></span><br>
                <span style="font-size:0.9em; color:#555;">Total: <%= total_price_formatted %></span>
            </div>
        </div>
         <a href="<?php echo esc_url(home_url('/dashboard/bookings/')); ?>?booking_id=<%= booking_id %>#viewdetails" class="button button-small" style="margin-top:8px;"><?php esc_html_e('View Details', 'mobooking'); ?></a>
    </div>
</script>
