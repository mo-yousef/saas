<?php
/** Dashboard Page: Service Areas @package MoBooking */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Instantiate Areas class and fetch initial data
$areas_manager = new \MoBooking\Classes\Areas();
$user_id = get_current_user_id();

$default_args = [
    'limit' => 20, // Items per page, can be adjusted
    'paged' => 1,  // Start from the first page
];
// Assuming 'zip_code' is the default type for this page
$areas_result = $areas_manager->get_areas_by_user($user_id, 'zip_code', $default_args);

$areas_list = $areas_result['areas'];
$total_areas = $areas_result['total_count'];
$per_page = $areas_result['per_page'];
$current_page = $areas_result['current_page'];
$total_pages = ceil($total_areas / $per_page);

// Nonce for JS operations
wp_nonce_field('mobooking_dashboard_nonce', 'mobooking_dashboard_nonce_field');
?>
<h1><?php esc_html_e('Manage Service Areas', 'mobooking'); ?></h1>
<p><?php esc_html_e('Define the areas where you offer your services by selecting from a list or adding ZIP codes manually.', 'mobooking'); ?></p>

<div id="mobooking-area-selection-wrapper" style="background:#f0f0f0; padding:20px; margin-bottom:20px; border:1px solid #ccd0d4; max-width:500px;">
    <h3 style="margin-top:0;"><?php esc_html_e('Add Areas by Selection', 'mobooking'); ?></h3>
    <p>
        <label for="mobooking-country-selector"><?php esc_html_e('Select Country:', 'mobooking'); ?></label><br>
        <select id="mobooking-country-selector" name="mobooking_country_selector" class="regular-text" style="width:100%;">
            <option value=""><?php esc_html_e('-- Select a Country --', 'mobooking'); ?></option>
        </select>
    </p>
    <p>
        <label for="mobooking-city-selector"><?php esc_html_e('Select City:', 'mobooking'); ?></label><br>
        <select id="mobooking-city-selector" name="mobooking_city_selector" class="regular-text" style="width:100%;" disabled>
            <option value=""><?php esc_html_e('-- Select a City --', 'mobooking'); ?></option>
        </select>
    </p>
    <p>
        <label for="mobooking-area-zip-selector"><?php esc_html_e('Available Service Areas/ZIPs:', 'mobooking'); ?></label><br>
        <div id="mobooking-area-zip-selector-container" style="border:1px solid #ddd; background-color:white; padding:10px; min-height:100px; max-height:200px; overflow-y:auto;">
            <small><?php esc_html_e('Select a country and city to see available areas.', 'mobooking'); ?></small>
        </div>
    </p>
    <button type="button" id="mobooking-add-selected-areas-btn" class="button button-primary" disabled><?php esc_html_e('Add Selected Areas from City', 'mobooking'); ?></button>
    <div id="mobooking-selection-feedback" style="margin-top:10px; padding:8px; border-radius:3px;"></div>
</div>

<hr style="margin: 30px 0;">

<div id="mobooking-area-form-wrapper" style="background:#fff; padding:20px; margin-bottom:20px; border:1px solid #ccd0d4; max-width:400px;">
    <h3 id="mobooking-area-form-title" style="margin-top:0;"><?php esc_html_e('Add or Edit Service Area Manually', 'mobooking'); ?></h3>
    <form id="mobooking-area-form">
        <input type="hidden" id="mobooking-area-id" name="area_id" value="">
        <p>
            <label for="mobooking-area-country-code"><?php esc_html_e('Country Code (e.g., US, CA, GB):', 'mobooking'); ?></label><br>
            <input type="text" id="mobooking-area-country-code" name="country_code" required class="regular-text" style="width:100%;">
        </p>
        <p>
            <label for="mobooking-area-value"><?php esc_html_e('ZIP / Postal Code:', 'mobooking'); ?></label><br>
            <input type="text" id="mobooking-area-value" name="area_value" required class="regular-text" style="width:100%;">
        </p>
        <button type="submit" id="mobooking-save-area-btn" class="button button-primary"><?php esc_html_e('Add Area', 'mobooking'); ?></button>
        <button type="button" id="mobooking-cancel-edit-area-btn" class="button" style="display:none; margin-left:5px;"><?php esc_html_e('Cancel Edit', 'mobooking'); ?></button>
        <div id="mobooking-area-form-feedback" style="margin-top:10px; padding:8px; border-radius:3px;"></div>
    </form>
</div>

<h3 style="margin-top:30px;"><?php esc_html_e('Your Defined Service Areas', 'mobooking'); ?></h3>
<div id="mobooking-areas-list-container" style="max-width:500px;">
    <?php if ( ! empty( $areas_list ) ) : ?>
        <?php foreach ( $areas_list as $area ) : ?>
            <div class="mobooking-area-item" data-area-id="<?php echo esc_attr($area['area_id']); ?>" style="border:1px solid #eee; padding:8px 12px; margin-bottom:5px; background:#f9f9f9; display:flex; justify-content:space-between; align-items:center; border-radius:3px;">
                <span><strong><?php echo esc_html($area['country_code']); ?></strong> - <?php echo esc_html($area['area_value']); ?></span>
                <div>
                    <button class="button button-small mobooking-edit-area-btn" data-id="<?php echo esc_attr($area['area_id']); ?>"><?php esc_html_e('Edit', 'mobooking'); ?></button>
                    <button class="button button-link-delete mobooking-delete-area-btn" data-id="<?php echo esc_attr($area['area_id']); ?>" style="margin-left:5px;"><?php esc_html_e('Delete', 'mobooking'); ?></button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else : ?>
        <p><?php esc_html_e('No service areas defined yet.', 'mobooking'); ?></p>
    <?php endif; ?>
</div>

<div id="mobooking-areas-pagination-container" style="margin-top: 20px;">
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

<script type="text/template" id="mobooking-area-item-template">
    <div class="mobooking-area-item" data-area-id="<%= area_id %>" style="border:1px solid #eee; padding:8px 12px; margin-bottom:5px; background:#f9f9f9; display:flex; justify-content:space-between; align-items:center; border-radius:3px;">
        <span><strong><%= country_code %></strong> - <%= area_value %></span>
        <div>
            <button class="button button-small mobooking-edit-area-btn" data-id="<%= area_id %>"><?php esc_html_e('Edit', 'mobooking'); ?></button>
            <button class="button button-link-delete mobooking-delete-area-btn" data-id="<%= area_id %>" style="margin-left:5px;"><?php esc_html_e('Delete', 'mobooking'); ?></button>
        </div>
    </div>
</script>
