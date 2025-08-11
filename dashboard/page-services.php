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

// Nonce for AJAX operations
wp_nonce_field('mobooking_services_nonce', 'mobooking_services_nonce_field');

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

<style>
/* Enhanced Services Page Styles */
:root {
    --primary: 222.2 84% 4.9%;
    --primary-foreground: 210 40% 98%;
    --secondary: 210 40% 96%;
    --secondary-foreground: 222.2 84% 4.9%;
    --muted: 210 40% 96%;
    --muted-foreground: 215.4 16.3% 46.9%;
    --accent: 210 40% 96%;
    --destructive: 0 84.2% 60.2%;
    --border: 214.3 31.8% 91.4%;
    --input: 214.3 31.8% 91.4%;
    --ring: 222.2 84% 4.9%;
    --background: 0 0% 100%;
    --foreground: 222.2 84% 4.9%;
    --card: 0 0% 100%;
    --radius: 0.5rem;
}

.services-dashboard {
    min-height: 100vh;
    background: linear-gradient(135deg, hsl(210, 40%, 99%) 0%, hsl(210, 40%, 96%) 100%);
    padding: 2rem;
}

.services-container {
    max-width: 1400px;
    margin: 0 auto;
}

/* Header Section */
.services-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    box-shadow: 0 2px 8px hsl(var(--primary) / 0.05);
}

.header-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.header-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 3rem;
    height: 3rem;
    background: linear-gradient(135deg, hsl(var(--primary)) 0%, hsl(var(--primary) / 0.8) 100%);
    color: hsl(var(--primary-foreground));
    border-radius: var(--radius);
    box-shadow: 0 4px 12px hsl(var(--primary) / 0.2);
}

.services-title {
    font-size: 2rem;
    font-weight: 700;
    background: linear-gradient(135deg, hsl(var(--primary)) 0%, hsl(var(--primary) / 0.7) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin: 0;
}

.add-service-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: linear-gradient(135deg, hsl(var(--primary)) 0%, hsl(var(--primary) / 0.9) 100%);
    color: hsl(var(--primary-foreground));
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: var(--radius);
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px hsl(var(--primary) / 0.2);
}

.add-service-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px hsl(var(--primary) / 0.3);
    color: hsl(var(--primary-foreground));
    text-decoration: none;
}

/* Controls Section */
.services-controls {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 1rem;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    box-shadow: 0 2px 8px hsl(var(--primary) / 0.05);
}

.search-container {
    position: relative;
}

.search-input {
    width: 100%;
    padding: 0.75rem 0.75rem 0.75rem 2.5rem;
    border: 1px solid hsl(var(--input));
    border-radius: var(--radius);
    background: hsl(var(--background));
    font-size: 0.95rem;
    transition: all 0.2s ease;
}

.search-input:focus {
    outline: none;
    border-color: hsl(var(--ring));
    box-shadow: 0 0 0 2px hsl(var(--ring) / 0.2);
}

.search-icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: hsl(var(--muted-foreground));
    pointer-events: none;
}

.filter-select {
    padding: 0.75rem;
    border: 1px solid hsl(var(--input));
    border-radius: var(--radius);
    background: hsl(var(--background));
    font-size: 0.95rem;
    transition: all 0.2s ease;
}

.filter-select:focus {
    outline: none;
    border-color: hsl(var(--ring));
    box-shadow: 0 0 0 2px hsl(var(--ring) / 0.2);
}

/* Content Section */
.services-content {
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    padding: 2rem;
    box-shadow: 0 2px 8px hsl(var(--primary) / 0.05);
}

/* Loading State */
.loading-state {
    display: none;
    text-align: center;
    padding: 2rem;
    color: hsl(var(--muted-foreground));
}

.loading-spinner {
    display: inline-block;
    width: 2rem;
    height: 2rem;
    border: 2px solid hsl(var(--border));
    border-top: 2px solid hsl(var(--primary));
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Services Grid */
.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

/* Service Cards */
.service-card {
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px hsl(var(--primary) / 0.05);
    position: relative;
}

.service-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px hsl(var(--primary) / 0.15);
    border-color: hsl(var(--primary) / 0.3);
}

.service-card-image {
    position: relative;
    height: 180px;
    background: linear-gradient(135deg, hsl(var(--muted)) 0%, hsl(var(--muted) / 0.7) 100%);
    overflow: hidden;
}

.service-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.service-card:hover .service-card-image img {
    transform: scale(1.05);
}

.service-image-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: hsl(var(--muted-foreground));
    flex-direction: column;
    gap: 0.5rem;
}

.service-status-badge {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    backdrop-filter: blur(8px);
}

.status-active {
    background: hsl(142 76% 36% / 0.9);
    color: hsl(var(--background));
    border: 1px solid hsl(142 76% 36%);
}

.status-inactive {
    background: hsl(var(--muted) / 0.9);
    color: hsl(var(--foreground));
    border: 1px solid hsl(var(--border));
}

.service-card-content {
    padding: 1.5rem;
}

.service-card-header {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
}

.service-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    background: hsl(var(--accent));
    border-radius: var(--radius);
    color: hsl(var(--primary));
    flex-shrink: 0;
}

.service-icon svg {
    width: 1.25rem;
    height: 1.25rem;
}

.service-details h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0 0 0.25rem 0;
    color: hsl(var(--foreground));
}

.service-price {
    font-size: 1.125rem;
    font-weight: 700;
    color: hsl(var(--primary));
}

.service-description {
    color: hsl(var(--muted-foreground));
    line-height: 1.6;
    margin-bottom: 1rem;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.service-meta {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
}

.service-meta-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.service-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: auto;
}

.btn {
    padding: 0.5rem 1rem;
    border-radius: var(--radius);
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s ease;
    border: 1px solid transparent;
    cursor: pointer;
}

.btn-primary {
    background: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
    border-color: hsl(var(--primary));
}

.btn-primary:hover {
    background: hsl(var(--primary) / 0.9);
    color: hsl(var(--primary-foreground));
    text-decoration: none;
}

.btn-secondary {
    background: hsl(var(--secondary));
    color: hsl(var(--secondary-foreground));
    border-color: hsl(var(--border));
}

.btn-secondary:hover {
    background: hsl(var(--secondary) / 0.8);
    color: hsl(var(--secondary-foreground));
    text-decoration: none;
}

.btn-destructive {
    background: hsl(var(--destructive));
    color: hsl(var(--primary-foreground));
    border-color: hsl(var(--destructive));
}

.btn-destructive:hover {
    background: hsl(var(--destructive) / 0.9);
    color: hsl(var(--primary-foreground));
    text-decoration: none;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: hsl(var(--muted-foreground));
}

.empty-state-icon {
    display: flex;
    justify-content: center;
    margin-bottom: 1rem;
    color: hsl(var(--muted-foreground));
}

.empty-state-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: hsl(var(--foreground));
}

.empty-state-description {
    margin-bottom: 1.5rem;
    line-height: 1.6;
}

/* Pagination */
.services-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    margin-top: 2rem;
}

.pagination-link {
    padding: 0.5rem 0.75rem;
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    color: hsl(var(--foreground));
    text-decoration: none;
    transition: all 0.2s ease;
}

.pagination-link:hover {
    background: hsl(var(--accent));
    border-color: hsl(var(--primary));
    color: hsl(var(--foreground));
    text-decoration: none;
}

.pagination-link.active {
    background: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
    border-color: hsl(var(--primary));
}

.pagination-link.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}

.pagination-ellipsis {
    padding: 0.5rem 0.25rem;
    color: hsl(var(--muted-foreground));
}

/* Responsive Design */
@media (max-width: 768px) {
    .services-dashboard {
        padding: 1rem;
    }
    
    .services-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .services-controls {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }
    
    .services-grid {
        grid-template-columns: 1fr;
    }
    
    .service-card-header {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .service-actions {
        flex-direction: column;
    }
}

/* Feedback Messages */
.feedback-message {
    padding: 1rem;
    border-radius: var(--radius);
    margin-bottom: 1rem;
    border: 1px solid;
}

.feedback-success {
    background: hsl(142 76% 36% / 0.1);
    color: hsl(142 76% 36%);
    border-color: hsl(142 76% 36% / 0.3);
}

.feedback-error {
    background: hsl(var(--destructive) / 0.1);
    color: hsl(var(--destructive));
    border-color: hsl(var(--destructive) / 0.3);
}
</style>

<div class="services-dashboard">
    <div class="services-container">
        <!-- Header Section -->
        <div class="services-header">
            <div class="header-content">
                <div class="header-icon">
                    <?php echo mobooking_get_dashboard_menu_icon('services'); ?>
                </div>
                <h1 class="services-title"><?php esc_html_e('Manage Your Services', 'mobooking'); ?></h1>
            </div>
            <a href="<?php echo esc_url(site_url('/dashboard/service-edit/')); ?>" class="add-service-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14" />
                    <path d="M12 5v14" />
                </svg>
                <?php esc_html_e('Add New Service', 'mobooking'); ?>
            </a>
        </div>

        <!-- Controls Section -->
        <div class="services-controls">
            <div class="search-container">
                <input
                    type="text"
                    id="services-search"
                    class="search-input"
                    placeholder="<?php esc_attr_e('Search services...', 'mobooking'); ?>"
                    value=""
                >
                <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m21 21-4.3-4.3" />
                    <circle cx="11" cy="11" r="8" />
                </svg>
            </div>

            <select id="status-filter" class="filter-select">
                <option value=""><?php esc_html_e('All Statuses', 'mobooking'); ?></option>
                <option value="active"><?php esc_html_e('Active', 'mobooking'); ?></option>
                <option value="inactive"><?php esc_html_e('Inactive', 'mobooking'); ?></option>
            </select>

            <select id="sort-filter" class="filter-select">
                <option value="name-asc"><?php esc_html_e('Name A-Z', 'mobooking'); ?></option>
                <option value="name-desc"><?php esc_html_e('Name Z-A', 'mobooking'); ?></option>
                <option value="price-asc"><?php esc_html_e('Price Low-High', 'mobooking'); ?></option>
                <option value="price-desc"><?php esc_html_e('Price High-Low', 'mobooking'); ?></option>
                <option value="date-asc"><?php esc_html_e('Oldest First', 'mobooking'); ?></option>
                <option value="date-desc"><?php esc_html_e('Newest First', 'mobooking'); ?></option>
            </select>
        </div>
        
        <!-- Content Section -->
        <div class="services-content">
            <!-- Feedback Messages Container -->
            <div id="services-feedback-container"></div>
            
            <!-- Loading State -->
            <div id="loading-state" class="loading-state">
                <div class="loading-spinner"></div>
                <p><?php esc_html_e('Loading services...', 'mobooking'); ?></p>
            </div>
            
            <!-- Services List Container -->
            <div id="services-list-container">
                <?php if (empty($services_list)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z" />
                                <polyline points="14 2 14 8 20 8" />
                                <line x1="16" y1="13" x2="8" y2="13" />
                                <line x1="16" y1="17" x2="8" y2="17" />
                                <line x1="10" y1="9" x2="8" y2="9" />
                            </svg>
                        </div>
                        <h3 class="empty-state-title"><?php esc_html_e('No services yet', 'mobooking'); ?></h3>
                        <p class="empty-state-description">
                            <?php esc_html_e('Create your first service to start accepting bookings from customers.', 'mobooking'); ?>
                        </p>
                        <a href="<?php echo esc_url(site_url('/dashboard/service-edit/')); ?>" class="add-service-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 12h14" />
                                <path d="M12 5v14" />
                            </svg>
                            <?php esc_html_e('Create First Service', 'mobooking'); ?>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="services-grid" id="services-grid">
                        <?php foreach ($services_list as $service): 
                            $price_formatted = format_currency($service['price'], $currency_symbol, $currency_pos);
                            $service_icon = !empty($service['icon']) 
                                ? $services_manager->get_service_icon_html($service['icon'])
                                : get_default_service_icon();
                            $options_count = !empty($service['options']) ? count($service['options']) : 0;
                        ?>
                            <div class="service-card" data-service-id="<?php echo esc_attr($service['service_id']); ?>">
                                <div class="service-card-image">
                                    <?php if (!empty($service['image_url'])): ?>
                                        <img src="<?php echo esc_url($service['image_url']); ?>" alt="<?php echo esc_attr($service['name']); ?>">
                                    <?php else: ?>
                                        <div class="service-image-placeholder">
                                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                                                <circle cx="9" cy="9" r="2"/>
                                                <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                                            </svg>
                                            <span><?php esc_html_e('No Image', 'mobooking'); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="service-status-badge status-<?php echo esc_attr($service['status']); ?>">
                                        <?php echo esc_html(ucfirst($service['status'])); ?>
                                    </div>
                                </div>
                                
                                <div class="service-card-content">
                                    <div class="service-card-header">
                                        <div class="service-icon">
                                            <?php echo $service_icon; // Already escaped in get_service_icon_html ?>
                                        </div>
                                        <div class="service-details">
                                            <h3><?php echo esc_html($service['name']); ?></h3>
                                            <div class="service-price"><?php echo esc_html($price_formatted); ?></div>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($service['description'])): ?>
                                        <p class="service-description"><?php echo esc_html($service['description']); ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="service-meta">
                                        <div class="service-meta-item">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="12" cy="12" r="10"/>
                                                <polyline points="12 6 12 12 16 14"/>
                                            </svg>
                                            <?php echo esc_html($service['duration']); ?> <?php esc_html_e('min', 'mobooking'); ?>
                                        </div>
                                        
                                        <?php if ($options_count > 0): ?>
                                            <div class="service-meta-item">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M9 12l2 2 4-4"/>
                                                    <path d="M21 12c.552 0 1-.448 1-1V5c0-.552-.448-1-1-1H3c-.552 0-1 .448-1 1v6c0 .552.448 1 1 1h18z"/>
                                                </svg>
                                                <?php echo esc_html($options_count); ?> <?php esc_html_e('Options', 'mobooking'); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="service-meta-item">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M8 2v4"/>
                                                <path d="M16 2v4"/>
                                                <rect width="18" height="18" x="3" y="4" rx="2"/>
                                                <path d="M3 10h18"/>
                                            </svg>
                                            <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($service['created_at']))); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="service-actions">
                                        <a href="<?php echo esc_url(site_url('/dashboard/service-edit/' . $service['service_id'])); ?>" class="btn btn-primary">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                            </svg>
                                            <?php esc_html_e('Edit', 'mobooking'); ?>
                                        </a>
                                        
                                        <button type="button" class="btn btn-secondary service-duplicate-btn" data-service-id="<?php echo esc_attr($service['service_id']); ?>">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect width="14" height="14" x="8" y="8" rx="2" ry="2"/>
                                                <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>
                                            </svg>
                                            <?php esc_html_e('Duplicate', 'mobooking'); ?>
                                        </button>
                                        
                                        <button type="button" class="btn btn-destructive service-delete-btn" data-service-id="<?php echo esc_attr($service['service_id']); ?>" data-service-name="<?php echo esc_attr($service['name']); ?>">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M3 6h18"/>
                                                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                            </svg>
                                            <?php esc_html_e('Delete', 'mobooking'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="services-pagination" id="services-pagination-container">
                            <a href="#" class="pagination-link prev <?php echo $current_page === 1 ? 'disabled' : ''; ?>" data-page="<?php echo $current_page - 1; ?>">&laquo; <?php esc_html_e('Prev', 'mobooking'); ?></a>
                            
                            <?php
                            $maxPagesToShow = 5;
                            $startPage = max(1, $current_page - floor($maxPagesToShow / 2));
                            $endPage = min($total_pages, $startPage + $maxPagesToShow - 1);
                            
                            if ($endPage - $startPage + 1 < $maxPagesToShow) {
                                $startPage = max(1, $endPage - $maxPagesToShow + 1);
                            }

                            if ($startPage > 1) {
                                echo '<a href="#" class="pagination-link" data-page="1">1</a>';
                                if ($startPage > 2) {
                                    echo '<span class="pagination-ellipsis">&hellip;</span>';
                                }
                            }

                            for ($i = $startPage; $i <= $endPage; $i++) {
                                echo '<a href="#" class="pagination-link ' . ($i === $current_page ? 'active' : '') . '" data-page="' . $i . '">' . $i . '</a>';
                            }

                            if ($endPage < $total_pages) {
                                if ($endPage < $total_pages - 1) {
                                    echo '<span class="pagination-ellipsis">&hellip;</span>';
                                }
                                echo '<a href="#" class="pagination-link" data-page="' . $total_pages . '">' . $total_pages . '</a>';
                            }
                            ?>
                            
                            <a href="#" class="pagination-link next <?php echo $current_page === $total_pages ? 'disabled' : ''; ?>" data-page="<?php echo $current_page + 1; ?>"><?php esc_html_e('Next', 'mobooking'); ?> &raquo;</a>
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

    // Check if required parameters exist
    if (typeof mobooking_services_params === 'undefined') {
        console.error('MoBooking: mobooking_services_params is not defined.');
        return;
    }

    // Cache DOM elements
    const $searchInput = $('#services-search');
    const $statusFilter = $('#status-filter');
    const $sortFilter = $('#sort-filter');
    const $servicesGrid = $('#services-grid');
    const $paginationContainer = $('#services-pagination-container');
    const $feedbackContainer = $('#services-feedback-container');
    const $loadingState = $('#loading-state');
    const $servicesListContainer = $('#services-list-container');

    // Current state
    let currentPage = 1;
    let currentRequest = null;
    let isLoading = false;

    // Debounce function
    function debounce(func, delay) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    }

    // Show feedback message
    function showFeedback(message, type = 'success') {
        const feedbackHtml = `
            <div class="feedback-message feedback-${type}">
                ${message}
            </div>
        `;
        $feedbackContainer.html(feedbackHtml);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            $feedbackContainer.find('.feedback-message').fadeOut(500, function() {
                $(this).remove();
            });
        }, 5000);
    }

    // Format currency
    function formatCurrency(amount) {
        const symbol = mobooking_services_params.currency_symbol || '
                ;
        const position = mobooking_services_params.currency_position || 'before';
        const formattedAmount = parseFloat(amount).toFixed(2);
        
        return position === 'before' ? symbol + formattedAmount : formattedAmount + symbol;
    }

    // Get default service icon
    function getDefaultServiceIcon() {
        return `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
        </svg>`;
    }

    // Render service card
    function renderServiceCard(service) {
        const priceFormatted = formatCurrency(service.price);
        const serviceIcon = service.icon_html || getDefaultServiceIcon();
        const optionsCount = service.options ? service.options.length : 0;
        const createdDate = new Date(service.created_at).toLocaleDateString();

        const imageHtml = service.image_url 
            ? `<img src="${service.image_url}" alt="${service.name}">`
            : `<div class="service-image-placeholder">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                    <circle cx="9" cy="9" r="2"/>
                    <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                </svg>
                <span><?php esc_html_e('No Image', 'mobooking'); ?></span>
            </div>`;

        return `
            <div class="service-card" data-service-id="${service.service_id}">
                <div class="service-card-image">
                    ${imageHtml}
                    <div class="service-status-badge status-${service.status}">
                        ${service.status.charAt(0).toUpperCase() + service.status.slice(1)}
                    </div>
                </div>
                
                <div class="service-card-content">
                    <div class="service-card-header">
                        <div class="service-icon">
                            ${serviceIcon}
                        </div>
                        <div class="service-details">
                            <h3>${service.name}</h3>
                            <div class="service-price">${priceFormatted}</div>
                        </div>
                    </div>
                    
                    ${service.description ? `<p class="service-description">${service.description}</p>` : ''}
                    
                    <div class="service-meta">
                        <div class="service-meta-item">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                            ${service.duration} <?php esc_html_e('min', 'mobooking'); ?>
                        </div>
                        
                        ${optionsCount > 0 ? `
                            <div class="service-meta-item">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9 12l2 2 4-4"/>
                                    <path d="M21 12c.552 0 1-.448 1-1V5c0-.552-.448-1-1-1H3c-.552 0-1 .448-1 1v6c0 .552.448 1 1 1h18z"/>
                                </svg>
                                ${optionsCount} <?php esc_html_e('Options', 'mobooking'); ?>
                            </div>
                        ` : ''}
                        
                        <div class="service-meta-item">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M8 2v4"/>
                                <path d="M16 2v4"/>
                                <rect width="18" height="18" x="3" y="4" rx="2"/>
                                <path d="M3 10h18"/>
                            </svg>
                            ${createdDate}
                        </div>
                    </div>
                    
                    <div class="service-actions">
                        <a href="<?php echo esc_url(site_url('/dashboard/service-edit/')); ?>${service.service_id}" class="btn btn-primary">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                            </svg>
                            <?php esc_html_e('Edit', 'mobooking'); ?>
                        </a>
                        
                        <button type="button" class="btn btn-secondary service-duplicate-btn" data-service-id="${service.service_id}">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="14" height="14" x="8" y="8" rx="2" ry="2"/>
                                <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>
                            </svg>
                            <?php esc_html_e('Duplicate', 'mobooking'); ?>
                        </button>
                        
                        <button type="button" class="btn btn-destructive service-delete-btn" data-service-id="${service.service_id}" data-service-name="${service.name}">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 6h18"/>
                                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                            </svg>
                            <?php esc_html_e('Delete', 'mobooking'); ?>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    // Render pagination
    function renderPagination(totalPages, currentPage) {
        if (totalPages <= 1) {
            $paginationContainer.hide();
            return;
        }

        const maxPagesToShow = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
        let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);

        if (endPage - startPage + 1 < maxPagesToShow) {
            startPage = Math.max(1, endPage - maxPagesToShow + 1);
        }

        let paginationHTML = `
            <a href="#" class="pagination-link prev ${currentPage === 1 ? 'disabled' : ''}" data-page="${currentPage - 1}">
                &laquo; <?php esc_html_e('Prev', 'mobooking'); ?>
            </a>
        `;

        if (startPage > 1) {
            paginationHTML += `<a href="#" class="pagination-link" data-page="1">1</a>`;
            if (startPage > 2) {
                paginationHTML += `<span class="pagination-ellipsis">&hellip;</span>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            paginationHTML += `<a href="#" class="pagination-link ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</a>`;
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationHTML += `<span class="pagination-ellipsis">&hellip;</span>`;
            }
            paginationHTML += `<a href="#" class="pagination-link" data-page="${totalPages}">${totalPages}</a>`;
        }

        paginationHTML += `
            <a href="#" class="pagination-link next ${currentPage === totalPages ? 'disabled' : ''}" data-page="${currentPage + 1}">
                <?php esc_html_e('Next', 'mobooking'); ?> &raquo;
            </a>
        `;

        $paginationContainer.html(paginationHTML).show();
    }

    // Render empty state
    function renderEmptyState(isFiltered = false) {
        const emptyStateHTML = isFiltered ? `
            <div class="empty-state">
                <div class="empty-state-icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        <line x1="13" y1="9" x2="9" y2="13"></line>
                        <line x1="9" y1="9" x2="13" y2="13"></line>
                    </svg>
                </div>
                <h3 class="empty-state-title"><?php esc_html_e('No matching services found', 'mobooking'); ?></h3>
                <p class="empty-state-description">
                    <?php esc_html_e('Try adjusting your search or filter criteria to find what you\'re looking for.', 'mobooking'); ?>
                </p>
            </div>
        ` : `
            <div class="empty-state">
                <div class="empty-state-icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z" />
                        <polyline points="14 2 14 8 20 8" />
                        <line x1="16" y1="13" x2="8" y2="13" />
                        <line x1="16" y1="17" x2="8" y2="17" />
                        <line x1="10" y1="9" x2="8" y2="9" />
                    </svg>
                </div>
                <h3 class="empty-state-title"><?php esc_html_e('No services yet', 'mobooking'); ?></h3>
                <p class="empty-state-description">
                    <?php esc_html_e('Create your first service to start accepting bookings from customers.', 'mobooking'); ?>
                </p>
                <a href="<?php echo esc_url(site_url('/dashboard/service-edit/')); ?>" class="add-service-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14" />
                        <path d="M12 5v14" />
                    </svg>
                    <?php esc_html_e('Create First Service', 'mobooking'); ?>
                </a>
            </div>
        `;

        $servicesListContainer.html(emptyStateHTML);
        $paginationContainer.hide();
    }

    // Fetch services via AJAX
    function fetchServices(page = 1) {
        if (isLoading) return;

        isLoading = true;
        currentPage = page;

        const searchQuery = $searchInput.val().trim();
        const status = $statusFilter.val();
        const sort = $sortFilter.val().split('-');
        const [orderby, order] = sort;

        // Show loading state
        $loadingState.show();
        $servicesListContainer.hide();

        // Abort previous request
        if (currentRequest) {
            currentRequest.abort();
        }

        const requestData = {
            action: 'mobooking_get_services',
            nonce: mobooking_services_params.services_nonce,
            search_query: searchQuery,
            status_filter: status,
            orderby: orderby,
            order: order.toUpperCase(),
            paged: currentPage,
            per_page: 20,
        };

        currentRequest = $.ajax({
            url: mobooking_services_params.ajax_url,
            type: 'POST',
            data: requestData,
            dataType: 'json',
            success: function(response) {
                isLoading = false;
                $loadingState.hide();
                $servicesListContainer.show();

                if (response.success && response.data) {
                    const { services, total_count, per_page, current_page } = response.data;
                    const totalPages = Math.ceil(total_count / per_page);

                    if (services && services.length > 0) {
                        // Render services grid
                        const servicesHTML = services.map(service => renderServiceCard(service)).join('');
                        $servicesListContainer.html(`<div class="services-grid" id="services-grid">${servicesHTML}</div>`);
                        
                        // Update pagination
                        renderPagination(totalPages, current_page);
                    } else {
                        // Show empty state
                        const isFiltered = searchQuery || status;
                        renderEmptyState(isFiltered);
                    }
                } else {
                    showFeedback(response.data?.message || '<?php esc_html_e('Failed to load services. Please try again.', 'mobooking'); ?>', 'error');
                    renderEmptyState();
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                isLoading = false;
                $loadingState.hide();
                $servicesListContainer.show();

                if (textStatus !== 'abort') {
                    showFeedback('<?php esc_html_e('Network error. Please check your connection and try again.', 'mobooking'); ?>', 'error');
                    renderEmptyState();
                }
            }
        });
    }

    // Event handlers
    const debouncedFetch = debounce(() => fetchServices(1), 300);

    $searchInput.on('input', debouncedFetch);
    $statusFilter.on('change', () => fetchServices(1));
    $sortFilter.on('change', () => fetchServices(1));

    // Pagination event handler
    $(document).on('click', '.pagination-link:not(.disabled)', function(e) {
        e.preventDefault();
        const page = parseInt($(this).data('page'));
        if (page && page !== currentPage) {
            fetchServices(page);
        }
    });

    // Service action handlers
    $(document).on('click', '.service-delete-btn', function(e) {
        e.preventDefault();
        const serviceId = $(this).data('service-id');
        const serviceName = $(this).data('service-name');
        
        $('#delete-confirmation-text').text(`<?php esc_html_e('Are you sure you want to delete the service', 'mobooking'); ?> "${serviceName}"? <?php esc_html_e('This action cannot be undone.', 'mobooking'); ?>`);
        $('#delete-confirmation-modal').show();
        $('#confirm-delete-btn').data('service-id', serviceId);
    });

    $(document).on('click', '.service-duplicate-btn', function(e) {
        e.preventDefault();
        const serviceId = $(this).data('service-id');
        
        // Show loading state on button
        const $btn = $(this);
        const originalHtml = $btn.html();
        $btn.html('<div class="loading-spinner" style="width: 14px; height: 14px;"></div> <?php esc_html_e('Duplicating...', 'mobooking'); ?>').prop('disabled', true);

        $.ajax({
            url: mobooking_services_params.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_duplicate_service',
                nonce: mobooking_services_params.services_nonce,
                service_id: serviceId
            },
            dataType: 'json',
            success: function(response) {
                $btn.html(originalHtml).prop('disabled', false);
                $('#delete-confirmation-modal').hide();
                
                if (response.success) {
                    showFeedback(response.data.message || '<?php esc_html_e('Service deleted successfully.', 'mobooking'); ?>');
                    fetchServices(currentPage); // Refresh current page
                } else {
                    showFeedback(response.data?.message || '<?php esc_html_e('Failed to delete service.', 'mobooking'); ?>', 'error');
                }
            },
            error: function() {
                $btn.html(originalHtml).prop('disabled', false);
                $('#delete-confirmation-modal').hide();
                showFeedback('<?php esc_html_e('Network error. Please try again.', 'mobooking'); ?>', 'error');
            }
        });
    });

    // Close modal when clicking outside
    $(document).on('click', '.modal-overlay', function(e) {
        if (e.target === this) {
            $('#delete-confirmation-modal').hide();
        }
    });

    // Keyboard navigation for modal
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#delete-confirmation-modal').is(':visible')) {
            $('#delete-confirmation-modal').hide();
        }
    });

    // Auto-refresh services every 30 seconds (optional)
    // setInterval(() => {
    //     if (!isLoading) {
    //         fetchServices(currentPage);
    //     }
    // }, 30000);

    console.log('MoBooking Services: Enhanced page initialized');
});
</script>

