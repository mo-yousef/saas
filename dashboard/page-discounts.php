<?php
/**
 * Dashboard Page: Discounts
 * @package NORDBOOKING
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Instantiate Discounts class and fetch initial data
$discounts_manager = new \NORDBOOKING\Classes\Discounts();
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
wp_nonce_field('nordbooking_dashboard_nonce', 'nordbooking_dashboard_nonce_field');

?>
<div class="NORDBOOKING-discounts-page">
    <div class="NORDBOOKING-page-header">
        <div class="NORDBOOKING-page-header-heading">
            <span class="NORDBOOKING-page-header-icon">
                <?php echo nordbooking_get_dashboard_menu_icon('discounts'); ?>
            </span>
            <h1 class="card-content-value text-2xl font-bold"><?php esc_html_e('Manage Discount Codes', 'NORDBOOKING'); ?></h1>
        </div>
        <button id="NORDBOOKING-add-new-discount-btn" class="btn btn-primary btn-sm"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class=""><path d="M5 12h14"/><path d="M12 5v14"/></svg><?php esc_html_e('Add New Discount Code', 'NORDBOOKING'); ?></button>
    </div>

    <div id="NORDBOOKING-discounts-list-container" class="NORDBOOKING-table-container">
    <table class="wp-list-table widefat striped">
        <thead>
            <tr>
                <th scope="col"><?php esc_html_e('Code', 'NORDBOOKING'); ?></th>
                <th scope="col"><?php esc_html_e('Type', 'NORDBOOKING'); ?></th>
                <th scope="col"><?php esc_html_e('Value', 'NORDBOOKING'); ?></th>
                <th scope="col"><?php esc_html_e('Expiry', 'NORDBOOKING'); ?></th>
                <th scope="col"><?php esc_html_e('Usage (Used/Limit)', 'NORDBOOKING'); ?></th>
                <th scope="col"><?php esc_html_e('Status', 'NORDBOOKING'); ?></th>
                <th scope="col"><?php esc_html_e('Actions', 'NORDBOOKING'); ?></th>
            </tr>
        </thead>
        <tbody id="NORDBOOKING-discounts-list">
            <?php if ( ! empty( $discounts_list ) ) : ?>
                <?php foreach ( $discounts_list as $discount ) : ?>
                    <?php
                    $type_display = $discount['type'] === 'percentage' ? __('Percentage', 'NORDBOOKING') : __('Fixed Amount', 'NORDBOOKING');
                    $value_display = $discount['type'] === 'percentage' ? $discount['value'] . '%' : \NORDBOOKING\Classes\Utils::format_currency($discount['value']);
                    $expiry_date_display = !empty($discount['expiry_date']) ? date_i18n(get_option('date_format'), strtotime($discount['expiry_date'])) : __('Never', 'NORDBOOKING');
                    $usage_limit_display = !empty($discount['usage_limit']) ? $discount['usage_limit'] : __('Unlimited', 'NORDBOOKING');
                    $usage_display = esc_html($discount['times_used']) . ' / ' . esc_html($usage_limit_display);
                    $status_display = $discount['status'] === 'active' ? __('Active', 'NORDBOOKING') : __('Inactive', 'NORDBOOKING');
                    ?>
                    <tr class="NORDBOOKING-discount-item" data-id="<?php echo esc_attr($discount['discount_id']); ?>">
                        <td data-label="<?php esc_attr_e('Code', 'NORDBOOKING'); ?>"><strong><?php echo esc_html($discount['code']); ?></strong></td>
                        <td data-label="<?php esc_attr_e('Type', 'NORDBOOKING'); ?>"><?php echo esc_html($type_display); ?></td>
                        <td data-label="<?php esc_attr_e('Value', 'NORDBOOKING'); ?>"><?php echo wp_kses_post($value_display); // Currency might have HTML ?></td>
                        <td data-label="<?php esc_attr_e('Expiry', 'NORDBOOKING'); ?>"><?php echo esc_html($expiry_date_display); ?></td>
                        <td data-label="<?php esc_attr_e('Usage (Used/Limit)', 'NORDBOOKING'); ?>"><?php echo esc_html($usage_display); ?></td>
                        <td data-label="<?php esc_attr_e('Status', 'NORDBOOKING'); ?>"><span class="status-<?php echo esc_attr($discount['status']); ?>"><?php echo esc_html($status_display); ?></span></td>
                        <td data-label="<?php esc_attr_e('Actions', 'NORDBOOKING'); ?>">
                            <button class="btn btn-outline btn-sm NORDBOOKING-edit-discount-btn" data-id="<?php echo esc_attr($discount['discount_id']); ?>"><?php esc_html_e('Edit', 'NORDBOOKING'); ?></button>
                            <button class="btn btn-destructive btn-sm NORDBOOKING-delete-discount-btn" data-id="<?php echo esc_attr($discount['discount_id']); ?>"><?php esc_html_e('Delete', 'NORDBOOKING'); ?></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="7"><p><?php esc_html_e('No discount codes found.', 'NORDBOOKING'); ?></p></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<div id="NORDBOOKING-discounts-pagination-container" style="margin-top:20px; text-align: center;">
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

<script type="text/template" id="NORDBOOKING-discount-item-template">
    <tr class="NORDBOOKING-discount-item" data-id="<%= discount_id %>">
        <td data-label="<?php esc_attr_e('Code', 'NORDBOOKING'); ?>"><strong><%= code %></strong></td>
        <td data-label="<?php esc_attr_e('Type', 'NORDBOOKING'); ?>"><%= type_display %></td>
        <td data-label="<?php esc_attr_e('Value', 'NORDBOOKING'); ?>"><%= value_display %></td>
        <td data-label="<?php esc_attr_e('Expiry', 'NORDBOOKING'); ?>"><%= expiry_date_display %></td>
        <td data-label="<?php esc_attr_e('Usage (Used/Limit)', 'NORDBOOKING'); ?>"><%= usage_display %></td>
        <td data-label="<?php esc_attr_e('Status', 'NORDBOOKING'); ?>"><span class="status-<%= status %>"><%= status_display %></span></td>
        <td data-label="<?php esc_attr_e('Actions', 'NORDBOOKING'); ?>">
            <button class="btn btn-outline btn-sm NORDBOOKING-edit-discount-btn" data-id="<%= discount_id %>"><?php esc_html_e('Edit', 'NORDBOOKING'); ?></button>
            <button class="btn btn-destructive btn-sm NORDBOOKING-delete-discount-btn" data-id="<%= discount_id %>"><?php esc_html_e('Delete', 'NORDBOOKING'); ?></button>
        </td>
    </tr>
</script>

<!-- Discount form modal is now handled by assets/js/dialog.js -->
<script type="text/template" id="NORDBOOKING-discount-form-template">
    <form id="NORDBOOKING-discount-form">
        <input type="hidden" id="NORDBOOKING-discount-id" name="discount_id" value="">
        <div class="form-grid">
            <div class="form-group full-width">
                <label for="NORDBOOKING-discount-code"><?php esc_html_e('Discount Code', 'NORDBOOKING'); ?></label>
                <div style="display: flex; gap: 0.5rem;">
                    <input type="text" id="NORDBOOKING-discount-code" name="code" required style="flex: 1;">
                    <button type="button" id="NORDBOOKING-generate-code-btn" class="button btn btn-secondary btn-sm"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"></path><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"></circle></svg><?php esc_html_e('Generate', 'NORDBOOKING'); ?></button>
                </div>
            </div>
            <div class="form-group full-width">
                <label><?php esc_html_e('Type', 'NORDBOOKING'); ?></label>
                <div class="radio-pills">
                    <label>
                        <input type="radio" name="type" value="percentage" checked>
                        <span><?php esc_html_e('Percentage', 'NORDBOOKING'); ?></span>
                    </label>
                    <label>
                        <input type="radio" name="type" value="fixed_amount">
                        <span><?php esc_html_e('Fixed Amount', 'NORDBOOKING'); ?></span>
                    </label>
                </div>
            </div>
            <div class="form-group">
                <label for="NORDBOOKING-discount-value"><?php esc_html_e('Value', 'NORDBOOKING'); ?></label>
                <input type="number" id="NORDBOOKING-discount-value" name="value" step="0.01" min="0.01" required>
            </div>
            <div class="form-group">
                <label for="NORDBOOKING-discount-expiry"><?php esc_html_e('Expiry Date', 'NORDBOOKING'); ?></label>
                <input type="text" id="NORDBOOKING-discount-expiry" name="expiry_date" class="NORDBOOKING-datepicker" placeholder="YYYY-MM-DD" autocomplete="off">
            </div>
            <div class="form-group">
                <label for="NORDBOOKING-discount-limit"><?php esc_html_e('Usage Limit', 'NORDBOOKING'); ?></label>
                <input type="number" id="NORDBOOKING-discount-limit" name="usage_limit" step="1" min="0" placeholder="<?php esc_attr_e('e.g., 100', 'NORDBOOKING'); ?>">
            </div>
            <div class="form-group">
                <label for="NORDBOOKING-discount-status"><?php esc_html_e('Status', 'NORDBOOKING'); ?></label>
                <label class="NORDBOOKING-toggle-switch">
                    <input type="checkbox" id="NORDBOOKING-discount-status" name="status" value="active">
                    <span class="slider"></span>
                </label>
            </div>
        </div>
        <!-- Actions are now part of the dialog footer -->
    </form>
</script>
</div>
