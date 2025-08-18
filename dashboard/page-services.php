<?php
/**
 * Dashboard Page: Services (Enhanced & Refactored)
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Instantiate the Services class
$services_manager = new \MoBooking\Classes\Services();
$user_id = get_current_user_id();

// Fetch business settings for currency formatting
$settings_manager = new \MoBooking\Classes\Settings();
$biz_settings = $settings_manager->get_business_settings($user_id);
$currency_symbol = $biz_settings['biz_currency_symbol'] ?? '$';
$currency_pos = $biz_settings['biz_currency_position'] ?? 'before';
$currency_decimals = 2;

// Initial load parameters
$default_args = [
    'number' => 20,
    'offset' => 0,
    'status' => null,
    'orderby' => 'name',
    'order' => 'ASC',
];

$services_result = $services_manager->get_services_by_user($user_id, $default_args);
$services_list = $services_result['services'] ?? [];
$total_services = $services_result['total_count'] ?? 0;
$per_page = $services_result['per_page'] ?? 20;
$current_page = $services_result['current_page'] ?? 1;
$total_pages = ceil($total_services / $per_page);


// Function to format currency
function format_currency($amount, $symbol, $position) {
    $formatted_amount = number_format_i18n($amount, 2);
    return $position === 'before' ? $symbol . $formatted_amount : $formatted_amount . $symbol;
}

// Function to get default service icon
function get_default_service_icon() {
    return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
    </svg>';
}
?>


<div class="wrap mobooking-dashboard-wrap mobooking-services-page-wrapper">
    <div class="mobooking-page-header">
        <div class="mobooking-page-header-heading">
            <span class="mobooking-page-header-icon">
                <?php echo mobooking_get_dashboard_menu_icon('services'); ?>
            </span>
            <h1 class="wp-heading-inline"><?php esc_html_e('Manage Your Services', 'mobooking'); ?></h1>
        </div>
        <a href="<?php echo esc_url(site_url('/dashboard/service-edit/')); ?>" class="btn btn-primary btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class=""><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            <?php esc_html_e('Add New Service', 'mobooking'); ?>
        </a>
    </div>

    <!-- Controls Section -->
    <div class="mobooking-card mobooking-filters-wrapper">
        <div class="mobooking-card-content">
            <div class="mobooking-filter-row">
                <div class="mobooking-filter-item mobooking-filter-item-search">
                    <label for="services-search"><?php esc_html_e('Search:', 'mobooking'); ?></label>
                    <input type="text" id="services-search" class="regular-text" placeholder="<?php esc_attr_e('Search services...', 'mobooking'); ?>" value="">
                </div>
            </div>
        </div>
    </div>
        
    <!-- Content Section -->
    <div class="mobooking-services-list-wrapper">
        <div id="services-list-container">
            <div id="loading-state" class="loading-state" style="display: none;">
                <div class="loading-spinner"></div>
                <p><?php esc_html_e('Loading services...', 'mobooking'); ?></p>
            </div>
            <?php if (empty($services_list)): ?>
                <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-muted-foreground" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg>
                        <h3 class="mt-4 text-lg font-semibold"><?php esc_html_e('No services yet', 'mobooking'); ?></h3>
                        <p class="mt-2 text-sm text-muted-foreground"><?php esc_html_e('Create your first service to start accepting bookings.', 'mobooking'); ?></p>
                        <a href="<?php echo esc_url(site_url('/dashboard/service-edit/')); ?>" class="btn btn-primary mt-6">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 mr-2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                            <?php esc_html_e('Create First Service', 'mobooking'); ?>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="services-grid">
                        <?php foreach ($services_list as $service): 
                            $price_formatted = format_currency($service['price'], $currency_symbol, $currency_pos);
                            $service_icon = !empty($service['icon']) 
                                ? $services_manager->get_service_icon_html($service['icon'])
                                : get_default_service_icon();
                            $options_count = !empty($service['options']) ? count($service['options']) : 0;
                        ?>
                            <div class="card" data-service-id="<?php echo esc_attr($service['service_id']); ?>">
                                <div class="card-header p-0 relative">
                                    <?php if (!empty($service['image_url'])): ?>
                                        <img src="<?php echo esc_url($service['image_url']); ?>" alt="<?php echo esc_attr($service['name']); ?>" class="w-full h-48 object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-48 bg-muted flex items-center justify-center">
                                            <svg class="w-12 h-12 text-muted-foreground" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                        </div>
                                    <?php endif; ?>
                                    <div class="badge badge-<?php echo esc_attr($service['status']); ?> absolute top-2 right-2"><?php echo esc_html(ucfirst($service['status'])); ?></div>
                                </div>
                                <div class="card-content p-4">
                                    <div class="flex items-start gap-4 mb-4">
                                        <div class="text-primary"><?php echo $service_icon; ?></div>
                                        <div>
                                            <h3 class="font-semibold"><?php echo esc_html($service['name']); ?></h3>
                                            <p class="text-primary font-bold"><?php echo esc_html($price_formatted); ?></p>
                                        </div>
                                    </div>
                                    <?php if (!empty($service['description'])): ?>
                                        <p class="text-sm text-muted-foreground mb-4 line-clamp-3"><?php echo esc_html($service['description']); ?></p>
                                    <?php endif; ?>
                                    <div class="text-xs text-muted-foreground space-y-2">
                                        <div class="flex items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                            <span><?php echo esc_html($service['duration']); ?> <?php esc_html_e('min', 'mobooking'); ?></span>
                                        </div>
                                        <?php if ($options_count > 0): ?>
                                        <div class="flex items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M9 12l2 2 4-4"/><path d="M21 12c.552 0 1-.448 1-1V5c0-.552-.448-1-1-1H3c-.552 0-1 .448-1 1v6c0 .552.448 1 1 1h18z"/></svg>
                                            <span><?php echo esc_html($options_count); ?> <?php esc_html_e('Options', 'mobooking'); ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-footer p-4 flex gap-2">
                                    <a href="<?php echo esc_url(site_url('/dashboard/service-edit/?service_id=' . $service['service_id'])); ?>" class="btn btn-primary w-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 mr-2"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                                        <?php esc_html_e('View', 'mobooking'); ?>
                                    </a>
                                    <form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-service-name="<?php echo esc_attr($service['name']); ?>">
                                        <input type="hidden" name="action" value="mobooking_delete_service">
                                        <input type="hidden" name="service_id" value="<?php echo esc_attr($service['service_id']); ?>">
                                        <?php wp_nonce_field('mobooking_delete_service_nonce'); ?>
                                        <button type="submit" class="btn btn-destructive">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($total_pages > 1): ?>
                        <div class="flex justify-center mt-6">
                            <?php
                            echo paginate_links([
                                'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                                'total' => $total_pages,
                                'current' => $current_page,
                                'format' => '?paged=%#%',
                                'prev_text' => __('&laquo; Prev'),
                                'next_text' => __('Next &raquo;'),
                            ]);
                            ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="delete-confirmation-modal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><?php esc_html_e('Confirm Deletion', 'mobooking'); ?></h3>
            <button type="button" class="modal-close" id="modal-close-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6L6 18"/>
                    <path d="M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <p id="delete-confirmation-text"></p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="cancel-delete-btn"><?php esc_html_e('Cancel', 'mobooking'); ?></button>
            <button type="button" class="btn btn-destructive" id="confirm-delete-btn"><?php esc_html_e('Delete Service', 'mobooking'); ?></button>
        </div>
    </div>
</div>

<style>
/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    backdrop-filter: blur(4px);
}

.modal-content {
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid hsl(var(--border));
}

.modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
}

.modal-close {
    background: none;
    border: none;
    color: hsl(var(--muted-foreground));
    cursor: pointer;
    padding: 0.25rem;
    border-radius: var(--radius);
    transition: all 0.2s ease;
}

.modal-close:hover {
    background: hsl(var(--muted));
    color: hsl(var(--foreground));
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1.5rem;
    border-top: 1px solid hsl(var(--border));
}
</style>

<script>
jQuery(document).ready(function($) {
    'use strict';

    const $searchInput = $('#services-search');
    const $servicesListContainer = $('#services-list-container');
    const $loadingState = $('#loading-state');
    const $modal = $('#delete-confirmation-modal');
    let currentRequest = null;

    // Debounce function for search
    function debounce(func, delay) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    }

    // AJAX search function
    const fetchSearchResults = debounce(function() {
        const searchQuery = $searchInput.val().trim();

        $loadingState.show();
        $servicesListContainer.hide();

        if (currentRequest) {
            currentRequest.abort();
        }

        currentRequest = $.ajax({
            url: "<?php echo esc_url(admin_url('admin-ajax.php')); ?>",
            type: 'POST',
            data: {
                action: 'mobooking_search_services',
                search_query: searchQuery,
                nonce: "<?php echo wp_create_nonce('mobooking_search_services_nonce'); ?>"
            },
            success: function(response) {
                $loadingState.hide();
                $servicesListContainer.html(response).show();
            },
            error: function(jqXHR, textStatus) {
                if (textStatus !== 'abort') {
                    $loadingState.hide();
                    $servicesListContainer.html('<p>An error occurred.</p>').show();
                }
            }
        });
    }, 300);

    $searchInput.on('input', fetchSearchResults);

    // --- Delete Confirmation Modal ---
    let formToSubmit;

    // Open modal
    $(document).on('click', '.btn-destructive', function(e) {
        e.preventDefault();
        formToSubmit = $(this).closest('form');
        const serviceName = formToSubmit.data('service-name') || 'this service';
        $('#delete-confirmation-text').text(`Are you sure you want to delete "${serviceName}"? This action cannot be undone.`);
        $modal.show();
    });

    // Confirm deletion
    $('#confirm-delete-btn').on('click', function() {
        if (formToSubmit) {
            formToSubmit.submit();
        }
    });

    // Cancel deletion / Close modal
    function closeModal() {
        $modal.hide();
        formToSubmit = null;
    }

    $('#cancel-delete-btn, #modal-close-btn, .modal-overlay').on('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $modal.is(':visible')) {
            closeModal();
        }
    });
});
</script>

