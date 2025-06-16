<?php
/** Dashboard Page: Service Areas @package MoBooking */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<h1><?php esc_html_e('Manage Service Areas (ZIP Codes)', 'mobooking'); ?></h1>
<p><?php esc_html_e('Define the ZIP codes where you offer your services.', 'mobooking'); ?></p>

<div id="mobooking-add-area-form-wrapper" style="background:#fff; padding:20px; margin-bottom:20px; border:1px solid #ccd0d4; max-width:400px;">
    <h3 style="margin-top:0;"><?php esc_html_e('Add New Service Area', 'mobooking'); ?></h3>
    <form id="mobooking-add-area-form">
        <p>
            <label for="mobooking-area-country-code"><?php esc_html_e('Country Code (e.g., US, CA, GB):', 'mobooking'); ?></label><br>
            <input type="text" id="mobooking-area-country-code" name="country_code" required class="regular-text" style="width:100%;">
        </p>
        <p>
            <label for="mobooking-area-value"><?php esc_html_e('ZIP / Postal Code:', 'mobooking'); ?></label><br>
            <input type="text" id="mobooking-area-value" name="area_value" required class="regular-text" style="width:100%;">
        </p>
        <button type="submit" class="button button-primary"><?php esc_html_e('Add Area', 'mobooking'); ?></button>
        <div id="mobooking-add-area-feedback" style="margin-top:10px; padding:8px; border-radius:3px;"></div>
    </form>
</div>

<h3 style="margin-top:30px;"><?php esc_html_e('Your Defined Service Areas', 'mobooking'); ?></h3>
<div id="mobooking-areas-list-container" style="max-width:500px;">
    <p><?php esc_html_e('Loading areas...', 'mobooking'); ?></p>
</div>

<script type="text/template" id="mobooking-area-item-template">
    <div class="mobooking-area-item" style="border:1px solid #eee; padding:8px 12px; margin-bottom:5px; background:#f9f9f9; display:flex; justify-content:space-between; align-items:center; border-radius:3px;">
        <span><strong><%= country_code %></strong> - <%= area_value %></span>
        <button class="button button-link-delete mobooking-delete-area-btn" data-id="<%= area_id %>"><?php esc_html_e('Delete', 'mobooking'); ?></button>
    </div>
</script>
