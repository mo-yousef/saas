<?php
/**
 * Dashboard Page: Services
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Instantiate the Services class
$services_manager = new \MoBooking\Classes\Services();
$user_id = get_current_user_id();

$default_args = [
    'number' => 20, // Items per page
    'offset' => 0,  // Start from the first page
    'status' => null, // Get all statuses by default
    'orderby' => 'name',
    'order' => 'ASC',
    // category_filter and search_query will be empty for initial load
];
$services_result = $services_manager->get_services_by_user($user_id, $default_args);

$services_list = $services_result['services'];
$total_services = $services_result['total_count'];
$per_page = $services_result['per_page'];
$current_page = $services_result['current_page'];
$total_pages = ceil($total_services / $per_page);

// Nonce for JS operations (services and options)
wp_nonce_field('mobooking_services_nonce', 'mobooking_services_nonce_field');
?>
<div style="display: flex; justify-content: space-between; align-items: center;">
    <h1><?php esc_html_e('Manage Your Services', 'mobooking'); ?></h1>
    <a href="<?php echo esc_url(site_url('/dashboard/service-edit/')); ?>" id="mobooking-add-new-service-btn" class="button button-primary"><?php esc_html_e('Add New Service', 'mobooking'); ?></a>
</div>

<h2 style="margin-top:30px;"><?php esc_html_e('Your Services', 'mobooking'); ?></h2>
<!-- Add filter controls here later if needed -->
<div id="mobooking-services-list-container">
    <?php if ( ! empty( $services_list ) ) : ?>
        <?php foreach ( $services_list as $service ) : ?>
            <?php if (is_array($service)) : // Ensure $service is an array ?>
            <div class="mobooking-service-item" data-service-id="<?php echo esc_attr( $service['service_id'] ); ?>" style="border:1px solid #ccd0d4; padding:15px; margin-bottom:10px; background:#fff; border-radius:4px;">
                <h3 style="margin-top:0;"><?php echo esc_html( $service['name'] ); ?></h3>
                <p><strong><?php esc_html_e('Price:', 'mobooking'); ?></strong> <span class="service-price"><?php echo esc_html( \MoBooking\Classes\Utils::format_currency( $service['price'] ) ); ?></span></p>
                <p><strong><?php esc_html_e('Duration:', 'mobooking'); ?></strong> <span class="service-duration"><?php echo esc_html( $service['duration'] ); ?></span> <?php esc_html_e('min', 'mobooking'); ?></p>
                <p><strong><?php esc_html_e('Status:', 'mobooking'); ?></strong> <span class="service-status"><?php echo esc_html( ucfirst( $service['status'] ) ); ?></span></p>
                <p style="margin-top:15px;">
                    <a href="<?php echo esc_url(site_url('/dashboard/service-edit/?service_id=' . $service['service_id'])); ?>" class="button mobooking-edit-service-btn" data-id="<?php echo esc_attr( $service['service_id'] ); ?>"><?php esc_html_e('Edit', 'mobooking'); ?></a>
                    <button class="button button-link-delete mobooking-delete-service-btn" data-id="<?php echo esc_attr( $service['service_id'] ); ?>"><?php esc_html_e('Delete', 'mobooking'); ?></button>
                </p>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php else : ?>
        <p><?php esc_html_e('No services found. Click "Add New Service" to create your first service.', 'mobooking'); ?></p>
    <?php endif; ?>
</div>

<div id="mobooking-services-pagination-container" style="margin-top: 20px; text-align: center;">
    <?php
    if ($total_pages > 1) {
        echo paginate_links(array(
            'base' => '#%#%', // Base for the links, not a real URL as JS will handle it.
            'format' => '?paged=%#%',
            'current' => $current_page,
            'total' => $total_pages,
            'prev_text' => __('&laquo; Prev'),
            'next_text' => __('Next &raquo;'),
            'add_fragment' => '', // Avoids adding # HASH to the URL
            'type' => 'list' // Outputs an unordered list.
        ));
    }
    ?>
</div>

<script type="text/template" id="mobooking-service-item-template">
    <div class="mobooking-service-item" data-service-id="<%= service_id %>" style="border:1px solid #ccd0d4; padding:15px; margin-bottom:10px; background:#fff; border-radius:4px;">
        <h3 style="margin-top:0;"><%= name %></h3>
        <p><strong><?php esc_html_e('Price:', 'mobooking'); ?></strong> <span class="service-price"><%= formatted_price %></span></p>
        <p><strong><?php esc_html_e('Duration:', 'mobooking'); ?></strong> <span class="service-duration"><%= duration %></span> <?php esc_html_e('min', 'mobooking'); ?></p>
        <p><strong><?php esc_html_e('Status:', 'mobooking'); ?></strong> <span class="service-status"><%= display_status %></span></p>
        <p style="margin-top:15px;">
            <a href="<?php echo esc_url(site_url('/dashboard/service-edit/')); ?>?service_id=<%= service_id %>" class="button mobooking-edit-service-btn" data-id="<%= service_id %>"><?php esc_html_e('Edit', 'mobooking'); ?></a>
            <button class="button button-link-delete mobooking-delete-service-btn" data-id="<%= service_id %>"><?php esc_html_e('Delete', 'mobooking'); ?></button>
        </p>
    </div>
</script>

<script type="text/javascript">
    // Pass initial services data (including options) to JavaScript for caching
    // This ensures the edit form can be populated without an immediate AJAX call for initially loaded items.
    var mobooking_initial_services_list_for_cache = <?php echo wp_json_encode($services_list); ?>;
</script>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // NOTE: Much of the JavaScript related to the modal form,
    // service option templates, and choice item templates has been removed
    // as that functionality is now on page-service-edit.php.
    // This script might still be used for deleting services or pagination,
    // so parts of it might be kept or refactored.

    // Example: If there's still JS here for deleting services from the list view:
    // $(document).on('click', '.mobooking-delete-service-btn', function() { ... });

    // Pagination JS would also remain if it's handled via AJAX.
    // $('#mobooking-services-pagination-container').on('click', '.page-numbers a', function(e) { ... });
});
</script>