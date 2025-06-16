<?php
/**
 * Dashboard Page: Discounts
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<h1><?php esc_html_e('Manage Discount Codes', 'mobooking'); ?></h1>
<button id="mobooking-add-new-discount-btn" class="button button-primary"><?php esc_html_e('Add New Discount Code', 'mobooking'); ?></button>

<div id="mobooking-discount-form-container" style="display:none; margin-top:20px; padding:20px; background:#fff; border:1px solid #ccd0d4; max-width:600px; border-radius:4px;">
    <h2 id="mobooking-discount-form-title" style="margin-top:0;"><?php esc_html_e('Add New Discount Code', 'mobooking'); ?></h2>
    <form id="mobooking-discount-form">
        <input type="hidden" id="mobooking-discount-id" name="discount_id" value="">
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="mobooking-discount-code"><?php esc_html_e('Discount Code:', 'mobooking'); ?> <span class="required" style="color:red;">*</span></label></th>
                <td><input type="text" id="mobooking-discount-code" name="code" class="regular-text" required>
                    <p class="description"><?php esc_html_e('E.g., SUMMER20, SAVE10. Customers will use this code.', 'mobooking'); ?></p></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="mobooking-discount-type"><?php esc_html_e('Type:', 'mobooking'); ?> <span class="required" style="color:red;">*</span></label></th>
                <td>
                    <select id="mobooking-discount-type" name="type">
                        <option value="percentage"><?php esc_html_e('Percentage', 'mobooking'); ?></option>
                        <option value="fixed_amount"><?php esc_html_e('Fixed Amount', 'mobooking'); ?></option>
                    </select>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="mobooking-discount-value"><?php esc_html_e('Value:', 'mobooking'); ?> <span class="required" style="color:red;">*</span></label></th>
                <td><input type="number" id="mobooking-discount-value" name="value" step="0.01" min="0.01" class="small-text" required>
                    <p class="description"><?php esc_html_e('E.g., 10 for 10% or 10 for $10.00 (or other currency).', 'mobooking'); ?></p></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="mobooking-discount-expiry"><?php esc_html_e('Expiry Date (optional):', 'mobooking'); ?></label></th>
                <td><input type="text" id="mobooking-discount-expiry" name="expiry_date" class="mobooking-datepicker regular-text" placeholder="YYYY-MM-DD" autocomplete="off"></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="mobooking-discount-limit"><?php esc_html_e('Usage Limit (optional):', 'mobooking'); ?></label></th>
                <td><input type="number" id="mobooking-discount-limit" name="usage_limit" step="1" min="0" class="small-text" placeholder="<?php esc_attr_e('e.g., 100', 'mobooking'); ?>">
                    <p class="description"><?php esc_html_e('Max number of times this code can be used. Leave blank or 0 for unlimited.', 'mobooking'); ?></p></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="mobooking-discount-status"><?php esc_html_e('Status:', 'mobooking'); ?></label></th>
                <td>
                    <select id="mobooking-discount-status" name="status">
                        <option value="active"><?php esc_html_e('Active', 'mobooking'); ?></option>
                        <option value="inactive"><?php esc_html_e('Inactive', 'mobooking'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <p class="submit">
            <button type="submit" id="mobooking-save-discount-btn" class="button button-primary"><?php esc_html_e('Save Discount Code', 'mobooking'); ?></button>
            <button type="button" id="mobooking-cancel-discount-form" class="button" style="margin-left:10px;"><?php esc_html_e('Cancel', 'mobooking'); ?></button>
        </p>
        <div id="mobooking-discount-form-feedback" style="margin-top:10px; padding:8px; border-radius:3px;"></div>
    </form>
</div>

<h2 style="margin-top:30px;"><?php esc_html_e('Your Discount Codes', 'mobooking'); ?></h2>
<div id="mobooking-discounts-list-container">
    <table class="wp-list-table widefat striped fixed">
        <thead>
            <tr>
                <th scope="col"><?php esc_html_e('Code', 'mobooking'); ?></th>
                <th scope="col"><?php esc_html_e('Type', 'mobooking'); ?></th>
                <th scope="col"><?php esc_html_e('Value', 'mobooking'); ?></th>
                <th scope="col"><?php esc_html_e('Expiry', 'mobooking'); ?></th>
                <th scope="col"><?php esc_html_e('Usage (Used/Limit)', 'mobooking'); ?></th>
                <th scope="col"><?php esc_html_e('Status', 'mobooking'); ?></th>
                <th scope="col"><?php esc_html_e('Actions', 'mobooking'); ?></th>
            </tr>
        </thead>
        <tbody id="mobooking-discounts-list">
            <tr><td colspan="7"><p><?php esc_html_e('Loading discount codes...', 'mobooking'); ?></p></td></tr>
        </tbody>
    </table>
</div>
<div id="mobooking-discounts-pagination-container" style="margin-top:20px; text-align: center;"></div>

<script type="text/template" id="mobooking-discount-item-template">
    <tr class="mobooking-discount-item <% if (status !== 'active') { %>inactive-row<% } %>" data-id="<%= discount_id %>">
        <td data-label="<?php esc_attr_e('Code', 'mobooking'); ?>"><strong><%= code %></strong></td>
        <td data-label="<?php esc_attr_e('Type', 'mobooking'); ?>"><%= type_display %></td>
        <td data-label="<?php esc_attr_e('Value', 'mobooking'); ?>"><%= value_display %></td>
        <td data-label="<?php esc_attr_e('Expiry', 'mobooking'); ?>"><%= expiry_date_display %></td>
        <td data-label="<?php esc_attr_e('Usage (Used/Limit)', 'mobooking'); ?>"><%= usage_display %></td>
        <td data-label="<?php esc_attr_e('Status', 'mobooking'); ?>"><span class="status-<%= status %>" style="padding: 3px 6px; border-radius: 3px; background-color: #eee; font-weight:bold;"><%= status_display %></span></td>
        <td data-label="<?php esc_attr_e('Actions', 'mobooking'); ?>">
            <button class="button button-small mobooking-edit-discount-btn" data-id="<%= discount_id %>"><?php esc_html_e('Edit', 'mobooking'); ?></button>
            <button class="button button-small button-link-delete mobooking-delete-discount-btn" data-id="<%= discount_id %>" style="margin-left:5px;"><?php esc_html_e('Delete', 'mobooking'); ?></button>
        </td>
    </tr>
</script>
