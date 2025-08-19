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
$discounts_result = $discounts_manager->get_discounts_by_user($user_id, $default_args);

$discounts_list = $discounts_result['discounts'];
$total_discounts = $discounts_result['total_count'];
$per_page = $discounts_result['per_page'];
$current_page = $discounts_result['current_page'];
$total_pages = ceil($total_discounts / $per_page);

// Nonce for JS operations
wp_nonce_field('mobooking_dashboard_nonce', 'mobooking_dashboard_nonce_field');

?>
<div class="mobooking-discounts-page">
    <div class="mobooking-page-header">
        <div class="mobooking-page-header-heading">
            <span class="mobooking-page-header-icon">
                <?php echo mobooking_get_dashboard_menu_icon('discounts'); ?>
            </span>
            <h1 class="card-content-value text-2xl font-bold"><?php esc_html_e('Manage Discount Codes', 'mobooking'); ?></h1>
        </div>
        <button id="mobooking-add-new-discount-btn" class="btn btn-primary btn-sm"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class=""><path d="M5 12h14"/><path d="M12 5v14"/></svg><?php esc_html_e('Add New Discount Code', 'mobooking'); ?></button>
    </div>

    <div id="mobooking-discounts-list-container" class="mobooking-table-container">
    <table class="wp-list-table widefat striped">
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
                    <tr class="mobooking-discount-item" data-id="<?php echo esc_attr($discount['discount_id']); ?>">
                        <td data-label="<?php esc_attr_e('Code', 'mobooking'); ?>"><strong><?php echo esc_html($discount['code']); ?></strong></td>
                        <td data-label="<?php esc_attr_e('Type', 'mobooking'); ?>"><?php echo esc_html($type_display); ?></td>
                        <td data-label="<?php esc_attr_e('Value', 'mobooking'); ?>"><?php echo wp_kses_post($value_display); // Currency might have HTML ?></td>
                        <td data-label="<?php esc_attr_e('Expiry', 'mobooking'); ?>"><?php echo esc_html($expiry_date_display); ?></td>
                        <td data-label="<?php esc_attr_e('Usage (Used/Limit)', 'mobooking'); ?>"><?php echo esc_html($usage_display); ?></td>
                        <td data-label="<?php esc_attr_e('Status', 'mobooking'); ?>"><span class="status-<?php echo esc_attr($discount['status']); ?>"><?php echo esc_html($status_display); ?></span></td>
                        <td data-label="<?php esc_attr_e('Actions', 'mobooking'); ?>">
                            <button class="btn btn-outline btn-sm mobooking-edit-discount-btn" data-id="<?php echo esc_attr($discount['discount_id']); ?>"><?php esc_html_e('Edit', 'mobooking'); ?></button>
                            <button class="btn btn-destructive btn-sm mobooking-delete-discount-btn" data-id="<?php echo esc_attr($discount['discount_id']); ?>"><?php esc_html_e('Delete', 'mobooking'); ?></button>
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
    <tr class="mobooking-discount-item" data-id="<%= discount_id %>">
        <td data-label="<?php esc_attr_e('Code', 'mobooking'); ?>"><strong><%= code %></strong></td>
        <td data-label="<?php esc_attr_e('Type', 'mobooking'); ?>"><%= type_display %></td>
        <td data-label="<?php esc_attr_e('Value', 'mobooking'); ?>"><%= value_display %></td>
        <td data-label="<?php esc_attr_e('Expiry', 'mobooking'); ?>"><%= expiry_date_display %></td>
        <td data-label="<?php esc_attr_e('Usage (Used/Limit)', 'mobooking'); ?>"><%= usage_display %></td>
        <td data-label="<?php esc_attr_e('Status', 'mobooking'); ?>"><span class="status-<%= status %>"><%= status_display %></span></td>
        <td data-label="<?php esc_attr_e('Actions', 'mobooking'); ?>">
            <button class="btn btn-outline btn-sm mobooking-edit-discount-btn" data-id="<%= discount_id %>"><?php esc_html_e('Edit', 'mobooking'); ?></button>
            <button class="btn btn-destructive btn-sm mobooking-delete-discount-btn" data-id="<%= discount_id %>"><?php esc_html_e('Delete', 'mobooking'); ?></button>
        </td>
    </tr>
</script>

<!-- Discount form modal is now handled by assets/js/dialog.js -->
<script type="text/template" id="mobooking-discount-form-template">
    <form id="mobooking-discount-form">
        <input type="hidden" id="mobooking-discount-id" name="discount_id" value="">
        <div class="form-grid">
            <div class="form-group full-width">
                <label for="mobooking-discount-code"><?php esc_html_e('Discount Code', 'mobooking'); ?></label>
                <div style="display: flex; gap: 0.5rem;">
                    <input type="text" id="mobooking-discount-code" name="code" required style="flex: 1;">
                    <button type="button" id="mobooking-generate-code-btn" class="button btn btn-secondary btn-sm"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"></path><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"></circle></svg><?php esc_html_e('Generate', 'mobooking'); ?></button>
                </div>
            </div>
            <div class="form-group full-width">
                <label><?php esc_html_e('Type', 'mobooking'); ?></label>
                <div class="radio-pills">
                    <label>
                        <input type="radio" name="type" value="percentage" checked>
                        <span><?php esc_html_e('Percentage', 'mobooking'); ?></span>
                    </label>
                    <label>
                        <input type="radio" name="type" value="fixed_amount">
                        <span><?php esc_html_e('Fixed Amount', 'mobooking'); ?></span>
                    </label>
                </div>
            </div>
            <div class="form-group">
                <label for="mobooking-discount-value"><?php esc_html_e('Value', 'mobooking'); ?></label>
                <input type="number" id="mobooking-discount-value" name="value" step="0.01" min="0.01" required>
            </div>
            <div class="form-group">
                <label for="mobooking-discount-expiry"><?php esc_html_e('Expiry Date', 'mobooking'); ?></label>
                <input type="text" id="mobooking-discount-expiry" name="expiry_date" class="mobooking-datepicker" placeholder="YYYY-MM-DD" autocomplete="off">
            </div>
            <div class="form-group">
                <label for="mobooking-discount-limit"><?php esc_html_e('Usage Limit', 'mobooking'); ?></label>
                <input type="number" id="mobooking-discount-limit" name="usage_limit" step="1" min="0" placeholder="<?php esc_attr_e('e.g., 100', 'mobooking'); ?>">
            </div>
            <div class="form-group">
                <label for="mobooking-discount-status"><?php esc_html_e('Status', 'mobooking'); ?></label>
                <label class="mobooking-toggle-switch">
                    <input type="checkbox" id="mobooking-discount-status" name="status" value="active">
                    <span class="slider"></span>
                </label>
            </div>
        </div>
        <!-- Actions are now part of the dialog footer -->
    </form>
</script>
</div>
