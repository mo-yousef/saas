<?php
/**
 * Dashboard Page: Discounts
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Instantiate Discounts class and fetch initial data
$discounts_manager = new \MoBooking\Classes\Discounts();
$user_id = get_current_user_id();

$default_args = [
    'limit' => 20, // Items per page
    'paged' => 1,  // Start from the first page
    'status' => null, // Get all statuses by default
    'orderby' => 'created_at',
    'order' => 'DESC',
];
$discounts_result = $discounts_manager->get_discount_codes_by_user($user_id, $default_args);

$discounts_list = $discounts_result['discounts'];
$total_discounts = $discounts_result['total_count'];
$per_page = $discounts_result['per_page'];
$current_page = $discounts_result['current_page'];
$total_pages = ceil($total_discounts / $per_page);

// Nonce for JS operations
wp_nonce_field('mobooking_dashboard_nonce', 'mobooking_dashboard_nonce_field');

?>
<div style="display: flex; justify-content: space-between; align-items: center;">
    <h1><?php esc_html_e('Manage Discount Codes', 'mobooking'); ?></h1>
    <button id="mobooking-add-new-discount-btn" class="button button-primary"><?php esc_html_e('Add New Discount Code', 'mobooking'); ?></button>
</div>

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
            <?php if ( ! empty( $discounts_list ) ) : ?>
                <?php foreach ( $discounts_list as $discount ) : ?>
                    <?php
                    $type_display = $discount['type'] === 'percentage' ? __('Percentage', 'mobooking') : __('Fixed Amount', 'mobooking');
                    $value_display = $discount['type'] === 'percentage' ? $discount['value'] . '%' : \MoBooking\Classes\Utils::format_currency($discount['value']);
                    $expiry_date_display = !empty($discount['expiry_date']) ? date_i18n(get_option('date_format'), strtotime($discount['expiry_date'])) : __('Never', 'mobooking');
                    $usage_limit_display = !empty($discount['usage_limit']) ? $discount['usage_limit'] : __('Unlimited', 'mobooking');
                    $usage_display = esc_html($discount['times_used']) . ' / ' . esc_html($usage_limit_display);
                    $status_display = $discount['status'] === 'active' ? __('Active', 'mobooking') : __('Inactive', 'mobooking');
                    ?>
                    <tr class="mobooking-discount-item <?php echo $discount['status'] !== 'active' ? 'inactive-row' : ''; ?>" data-id="<?php echo esc_attr($discount['discount_id']); ?>">
                        <td data-label="<?php esc_attr_e('Code', 'mobooking'); ?>"><strong><?php echo esc_html($discount['code']); ?></strong></td>
                        <td data-label="<?php esc_attr_e('Type', 'mobooking'); ?>"><?php echo esc_html($type_display); ?></td>
                        <td data-label="<?php esc_attr_e('Value', 'mobooking'); ?>"><?php echo wp_kses_post($value_display); // Currency might have HTML ?></td>
                        <td data-label="<?php esc_attr_e('Expiry', 'mobooking'); ?>"><?php echo esc_html($expiry_date_display); ?></td>
                        <td data-label="<?php esc_attr_e('Usage (Used/Limit)', 'mobooking'); ?>"><?php echo esc_html($usage_display); ?></td>
                        <td data-label="<?php esc_attr_e('Status', 'mobooking'); ?>"><span class="status-<?php echo esc_attr($discount['status']); ?>" style="padding: 3px 6px; border-radius: 3px; background-color: #eee; font-weight:bold;"><?php echo esc_html($status_display); ?></span></td>
                        <td data-label="<?php esc_attr_e('Actions', 'mobooking'); ?>">
                            <button class="button button-small mobooking-edit-discount-btn" data-id="<?php echo esc_attr($discount['discount_id']); ?>"><?php esc_html_e('Edit', 'mobooking'); ?></button>
                            <button class="button button-small button-link-delete mobooking-delete-discount-btn" data-id="<?php echo esc_attr($discount['discount_id']); ?>" style="margin-left:5px;"><?php esc_html_e('Delete', 'mobooking'); ?></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="7"><p><?php esc_html_e('No discount codes found.', 'mobooking'); ?></p></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<div id="mobooking-discounts-pagination-container" style="margin-top:20px; text-align: center;">
    <?php
    if ($total_pages > 1) {
        echo paginate_links(array(
            'base' => '#%#%',
            'format' => '?paged=%#%',
            'current' => $current_page,
            'total' => $total_pages,
            'prev_text' => __('&laquo; Prev'),
            'next_text' => __('Next &raquo;'),
            'add_fragment' => '',
            'type' => 'list'
        ));
    }
    ?>
</div>

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
