
                            
                            <?php
/**
 * Dashboard Page: Add/Edit Service - Refactored with Shadcn UI
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// 1. Determine Page Mode (Add vs. Edit) and Set Title
$edit_mode = false;
$service_id = 0;
if ( isset( $_GET['service_id'] ) && ! empty( $_GET['service_id'] ) ) {
    $edit_mode = true;
    $service_id = intval( $_GET['service_id'] );
    $page_title = __( 'Edit Service', 'mobooking' );
} else {
    $page_title = __( 'Add New Service', 'mobooking' );
}

// 2. Initialize Variables
$service_name = '';
$service_description = '';
$service_price = '';
$service_duration = '';
$service_icon = '';
$service_image_url = '';
$service_status = 'active';
$service_options_data = [];
$error_message = '';

// 3. Fetch Service Data in Edit Mode
$user_id = get_current_user_id();
// Fetch business settings for currency display
$settings_manager = new \MoBooking\Classes\Settings();
$biz_settings = $settings_manager->get_business_settings($user_id);
$currency_symbol = $biz_settings['biz_currency_symbol'];
$currency_pos = $biz_settings['biz_currency_position'];

if ( $edit_mode && $service_id > 0 ) {
    if ( class_exists('\MoBooking\Classes\Services') ) {
        $services_manager = new \MoBooking\Classes\Services();
        $service_data = $services_manager->get_service( $service_id, $user_id );

        if ( $service_data && ! is_wp_error( $service_data ) ) {
            $service_name = $service_data['name'];
            $service_description = $service_data['description'];
            $service_price = $service_data['price'];
            $service_duration = $service_data['duration'];
            $service_icon = $service_data['icon'];
            $service_image_url = $service_data['image_url'];
            $service_status = $service_data['status'];
            $service_options_data = isset($service_data['options']) && is_array($service_data['options']) ? $service_data['options'] : [];
        } else {
            $error_message = __( 'Service not found or you do not have permission to edit it.', 'mobooking' );
        }
    } else {
        $error_message = __( 'Error: Services manager class not found.', 'mobooking' );
    }
}

// Nonce for JS operations
wp_nonce_field('mobooking_services_nonce', 'mobooking_services_nonce_field');
?>

<style>
/* Shadcn-inspired utility classes */
.shadow-sm { box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05); }
.shadow-md { box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); }
.rounded-lg { border-radius: 0.5rem; }
.rounded-md { border-radius: 0.375rem; }
.rounded-sm { border-radius: 0.125rem; }
.border { border: 1px solid hsl(214.3 31.8% 91.4%); }
.border-b { border-bottom: 1px solid hsl(214.3 31.8% 91.4%); }
.bg-card { background-color: hsl(0 0% 100%); }
.bg-muted { background-color: hsl(210 40% 96%); }
.bg-primary { background-color: hsl(221.2 83.2% 53.3%); }
.bg-secondary { background-color: hsl(210 40% 96%); }
.text-primary { color: hsl(221.2 83.2% 53.3%); }
.text-muted-foreground { color: hsl(215.4 16.3% 46.9%); }
.text-foreground { color: hsl(222.2 84% 4.9%); }
.text-destructive { color: hsl(0 84.2% 60.2%); }
.text-sm { font-size: 0.875rem; line-height: 1.25rem; }
.text-xs { font-size: 0.75rem; line-height: 1rem; }
.font-medium { font-weight: 500; }
.font-semibold { font-weight: 600; }
.p-4 { padding: 1rem; }
.p-6 { padding: 1.5rem; }
.px-3 { padding-left: 0.75rem; padding-right: 0.75rem; }
.py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
.px-4 { padding-left: 1rem; padding-right: 1rem; }
.py-3 { padding-top: 0.75rem; padding-bottom: 0.75rem; }
.mb-6 { margin-bottom: 1.5rem; }
.mb-4 { margin-bottom: 1rem; }
.mb-2 { margin-bottom: 0.5rem; }
.mr-2 { margin-right: 0.5rem; }
.mt-2 { margin-top: 0.5rem; }
.mt-4 { margin-top: 1rem; }
.mt-6 { margin-top: 1.5rem; }
.space-y-4 > * + * { margin-top: 1rem; }
.space-y-6 > * + * { margin-top: 1.5rem; }
.flex { display: flex; }
.flex-col { flex-direction: column; }
.flex-row { flex-direction: row; }
.items-center { align-items: center; }
.justify-between { justify-content: space-between; }
.gap-2 { gap: 0.5rem; }
.gap-4 { gap: 1rem; }
.w-full { width: 100%; }
.hidden { display: none; }
.block { display: block; }

/* Custom components */
.card {
    background-color: hsl(0 0% 100%);
    border: 1px solid hsl(214.3 31.8% 91.4%);
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
}

.card-header {
    padding: 1.5rem 1.5rem 0;
}

.card-content {
    padding: 1.5rem;
}

.card-title {
    font-size: 1.125rem;
    font-weight: 600;
    line-height: 1;
    letter-spacing: -0.025em;
    color: hsl(222.2 84% 4.9%);
}

.card-description {
    font-size: 0.875rem;
    color: hsl(215.4 16.3% 46.9%);
    margin-top: 0.375rem;
}

.accordion {
    border: 1px solid hsl(214.3 31.8% 91.4%);
    border-radius: 0.5rem;
    overflow: hidden;
}

.accordion-item {
    border-bottom: 1px solid hsl(214.3 31.8% 91.4%);
}

.accordion-item:last-child {
    border-bottom: none;
}

.accordion-trigger {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 1rem 1.5rem;
    font-weight: 500;
    text-align: left;
    background: transparent;
    border: none;
    cursor: pointer;
    transition: all 0.15s ease;
    font-size: 0.875rem;
}

.accordion-trigger:hover {
    background-color: hsl(210 40% 96%);
}

.accordion-trigger[aria-expanded="true"] {
    background-color: hsl(210 40% 96%);
}

.accordion-trigger .chevron {
    transition: transform 0.15s ease;
}

.accordion-trigger[aria-expanded="true"] .chevron {
    transform: rotate(180deg);
}

.accordion-content {
    padding: 0 1.5rem 1rem;
    overflow: hidden;
    transition: all 0.15s ease;
}

.accordion-content[aria-hidden="true"] {
    display: none;
}

.form-group {
    margin-bottom: 1rem;
}

.form-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: hsl(222.2 84% 4.9%);
    margin-bottom: 0.375rem;
}

.form-input {
    display: flex;
    height: 2.5rem;
    width: 100%;
    border-radius: 0.375rem;
    border: 1px solid hsl(214.3 31.8% 91.4%);
    background-color: hsl(0 0% 100%);
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    line-height: 1.25rem;
    transition: all 0.15s ease;
}

.form-input:focus {
    outline: 2px solid hsl(221.2 83.2% 53.3%);
    outline-offset: 2px;
    border-color: hsl(221.2 83.2% 53.3%);
}

.form-textarea {
    min-height: 5rem;
    resize: vertical;
}

.form-select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    padding-right: 2.5rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.15s ease;
    padding: 0.5rem 1rem;
    height: 2.5rem;
    border: 1px solid transparent;
    cursor: pointer;
    white-space: nowrap;
    text-decoration: none;
}

.btn-primary {
    background-color: hsl(221.2 83.2% 53.3%);
    color: hsl(210 40% 98%);
}

.btn-primary:hover {
    background-color: hsl(221.2 83.2% 48%);
}

.btn-secondary {
    background-color: hsl(210 40% 96%);
    color: hsl(222.2 84% 4.9%);
    border: 1px solid hsl(214.3 31.8% 91.4%);
}

.btn-secondary:hover {
    background-color: hsl(210 40% 91%);
}

.btn-outline {
    border: 1px solid hsl(214.3 31.8% 91.4%);
    background-color: hsl(0 0% 100%);
    color: hsl(222.2 84% 4.9%);
}

.btn-outline:hover {
    background-color: hsl(210 40% 96%);
}

.btn-sm {
    height: 2rem;
    padding: 0.25rem 0.75rem;
    font-size: 0.75rem;
}

.option-row {
    background-color: hsl(0 0% 100%);
    border: 1px solid hsl(214.3 31.8% 91.4%);
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 0.75rem;
    transition: all 0.15s ease;
}

.option-row:hover {
    box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
}

.option-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid hsl(214.3 31.8% 91.4%);
}

.drag-handle {
    cursor: move;
    color: hsl(215.4 16.3% 46.9%);
    padding: 0.25rem;
    border-radius: 0.25rem;
    transition: all 0.15s ease;
}

.drag-handle:hover {
    background-color: hsl(210 40% 96%);
    color: hsl(222.2 84% 4.9%);
}

.switch {
    position: relative;
    display: inline-flex;
    height: 1.5rem;
    width: 2.75rem;
    cursor: pointer;
    align-items: center;
    border-radius: 9999px;
    border: 2px solid transparent;
    transition: all 0.15s ease;
    background-color: hsl(214.3 31.8% 91.4%);
}

.switch.active {
    background-color: hsl(221.2 83.2% 53.3%);
}

.switch-thumb {
    pointer-events: none;
    display: block;
    height: 1.25rem;
    width: 1.25rem;
    border-radius: 50%;
    background-color: hsl(0 0% 100%);
    box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
    transition: transform 0.15s ease;
    transform: translateX(0);
}

.switch.active .switch-thumb {
    transform: translateX(1.25rem);
}

.choice-item {
    background-color: hsl(210 40% 96%);
    border: 1px solid hsl(214.3 31.8% 91.4%);
    border-radius: 0.375rem;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
}

.choice-item:last-child {
    margin-bottom: 0;
}

.remove-btn {
    color: hsl(0 84.2% 60.2%);
    background: transparent;
    border: none;
    font-size: 1.25rem;
    line-height: 1;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 0.25rem;
    transition: all 0.15s ease;
}

.remove-btn:hover {
    background-color: hsl(0 84.2% 95%);
}

/* Option Type Radio Cards */
.option-type-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.option-type-card {
    position: relative;
    display: flex;
    cursor: pointer;
    border-radius: 0.5rem;
    border: 2px solid hsl(214.3 31.8% 91.4%);
    background-color: hsl(0 0% 100%);
    padding: 0.75rem;
    transition: all 0.15s ease;
}

.option-type-card:hover {
    border-color: hsl(221.2 83.2% 53.3%);
    background-color: hsl(221.2 83.2% 97%);
}

.option-type-card:has(.option-type-radio:checked) {
    border-color: hsl(221.2 83.2% 53.3%);
    background-color: hsl(221.2 83.2% 97%);
    box-shadow: 0 0 0 2px hsl(221.2 83.2% 53.3% / 0.2);
}

.option-type-radio {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.option-type-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 0.5rem;
    width: 100%;
}

.option-type-icon {
    color: hsl(215.4 16.3% 46.9%);
    transition: color 0.15s ease;
}

.option-type-card:has(.option-type-radio:checked) .option-type-icon {
    color: hsl(221.2 83.2% 53.3%);
}

.option-type-label {
    font-size: 0.75rem;
    font-weight: 500;
    color: hsl(222.2 84% 4.9%);
    line-height: 1.2;
}

/* Pricing Type Radio Cards */
.pricing-type-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 0.75rem;
    margin-top: 0.5rem;
}

.pricing-type-card {
    position: relative;
    display: flex;
    cursor: pointer;
    border-radius: 0.5rem;
    border: 2px solid hsl(214.3 31.8% 91.4%);
    background-color: hsl(0 0% 100%);
    padding: 1rem;
    transition: all 0.15s ease;
}

.pricing-type-card:hover {
    border-color: hsl(221.2 83.2% 53.3%);
    background-color: hsl(221.2 83.2% 97%);
}

.pricing-type-card:has(.pricing-type-radio:checked) {
    border-color: hsl(221.2 83.2% 53.3%);
    background-color: hsl(221.2 83.2% 97%);
    box-shadow: 0 0 0 2px hsl(221.2 83.2% 53.3% / 0.2);
}

.pricing-type-radio {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.pricing-type-content {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    width: 100%;
}

.pricing-type-icon {
    color: hsl(215.4 16.3% 46.9%);
    transition: color 0.15s ease;
    flex-shrink: 0;
}

.pricing-type-card:has(.pricing-type-radio:checked) .pricing-type-icon {
    color: hsl(221.2 83.2% 53.3%);
}

.pricing-type-text {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.pricing-type-title {
    font-size: 0.875rem;
    font-weight: 500;
    color: hsl(222.2 84% 4.9%);
    line-height: 1.2;
}

.pricing-type-desc {
    font-size: 0.75rem;
    color: hsl(215.4 16.3% 46.9%);
    line-height: 1.2;
}

.pricing-requirements-container {
    background-color: hsl(210 40% 98%);
    border: 1px solid hsl(214.3 31.8% 91.4%);
    border-radius: 0.5rem;
    padding: 1rem;
}

@media (max-width: 640px) {
    .option-type-grid {
        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
        gap: 0.375rem;
    }
    
    .option-type-card {
        padding: 0.5rem;
    }
    
    .option-type-content {
        gap: 0.25rem;
    }
    
    .option-type-label {
        font-size: 0.625rem;
    }
    
    .pricing-type-grid {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
    
    .pricing-type-content {
        gap: 0.5rem;
    }
}
</style>

<div class="mobooking-service-edit-page">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-foreground"><?php echo esc_html( $page_title ); ?></h1>
        <p class="text-muted-foreground mt-2"><?php echo $edit_mode ? esc_html__('Update your service details and options.', 'mobooking') : esc_html__('Create a new service for your customers.', 'mobooking'); ?></p>
    </div>

    <?php if ( ! empty( $error_message ) ) : ?>
        <div class="card mb-6" style="border-color: hsl(0 84.2% 60.2%); background-color: hsl(0 84.2% 95%);">
            <div class="card-content">
                <p class="text-destructive font-medium"><?php echo esc_html( $error_message ); ?></p>
            </div>
        </div>
        <?php
        if ( $edit_mode && ! $service_data ) {
            echo '</div>';
            return;
        }
        ?>
    <?php endif; ?>

    <div class="space-y-6">
        <form id="mobooking-service-form" class="space-y-6">
            <input type="hidden" id="mobooking-service-id" name="service_id" value="<?php echo esc_attr( $edit_mode ? $service_id : '' ); ?>">
            
            <!-- Basic Information Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?php esc_html_e('Basic Information', 'mobooking'); ?></h3>
                    <p class="card-description"><?php esc_html_e('Enter the core details for your service.', 'mobooking'); ?></p>
                </div>
                <div class="card-content space-y-4">
                    <div class="form-group">
                        <label for="mobooking-service-name" class="form-label"><?php esc_html_e('Service Name', 'mobooking'); ?> <span class="text-destructive">*</span></label>
                        <input type="text" id="mobooking-service-name" name="name" value="<?php echo esc_attr( $service_name ); ?>" required class="form-input w-full" placeholder="<?php esc_attr_e('e.g., Deep House Cleaning', 'mobooking'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="mobooking-service-description" class="form-label"><?php esc_html_e('Description', 'mobooking'); ?></label>
                        <textarea id="mobooking-service-description" name="description" class="form-input form-textarea w-full" rows="3" placeholder="<?php esc_attr_e('Describe what this service includes...', 'mobooking'); ?>"><?php echo esc_textarea( $service_description ); ?></textarea>
                    </div>

                    <div class="flex flex-col gap-4" style="flex-direction: row;">
                        <div class="form-group" style="flex: 1;">
                            <label for="mobooking-service-price" class="form-label"><?php esc_html_e('Price', 'mobooking'); ?> <span class="text-destructive">*</span></label>
                            <div class="flex items-center">
                                <?php if ($currency_pos === 'before') : ?>
                                    <span class="px-3 py-2 bg-muted border border-r-0 rounded-l-md text-sm font-medium"><?php echo esc_html($currency_symbol); ?></span>
                                    <input type="number" id="mobooking-service-price" name="price" value="<?php echo esc_attr( $service_price ); ?>" required class="form-input" style="border-radius: 0 0.375rem 0.375rem 0; border-left: 0;" step="0.01" min="0" placeholder="0.00">
                                <?php else : ?>
                                    <input type="number" id="mobooking-service-price" name="price" value="<?php echo esc_attr( $service_price ); ?>" required class="form-input" style="border-radius: 0.375rem 0 0 0.375rem; border-right: 0;" step="0.01" min="0" placeholder="0.00">
                                    <span class="px-3 py-2 bg-muted border border-l-0 rounded-r-md text-sm font-medium"><?php echo esc_html($currency_symbol); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group" style="flex: 1;">
                            <label for="mobooking-service-duration" class="form-label"><?php esc_html_e('Duration (minutes)', 'mobooking'); ?> <span class="text-destructive">*</span></label>
                            <input type="number" id="mobooking-service-duration" name="duration" value="<?php echo esc_attr( $service_duration ); ?>" required class="form-input w-full" min="1" placeholder="120">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status & Display Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?php esc_html_e('Status & Display', 'mobooking'); ?></h3>
                    <p class="card-description"><?php esc_html_e('Control how your service appears to customers.', 'mobooking'); ?></p>
                </div>
                <div class="card-content space-y-4">
                    <div class="form-group">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="form-label"><?php esc_html_e('Service Status', 'mobooking'); ?></label>
                                <p class="text-xs text-muted-foreground"><?php esc_html_e('Only active services are visible to customers.', 'mobooking'); ?></p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-muted-foreground"><?php esc_html_e('Inactive', 'mobooking'); ?></span>
                                <button type="button" id="mobooking-service-status-toggle" class="switch <?php echo ($service_status === 'active') ? 'active' : ''; ?>" aria-pressed="<?php echo ($service_status === 'active') ? 'true' : 'false'; ?>">
                                    <span class="switch-thumb"></span>
                                </button>
                                <span class="text-sm text-muted-foreground"><?php esc_html_e('Active', 'mobooking'); ?></span>
                                <input type="hidden" id="mobooking-service-status" name="status" value="<?php echo esc_attr($service_status); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Icon Section -->
                    <div class="form-group">
                        <label class="form-label"><?php esc_html_e('Service Icon', 'mobooking'); ?></label>
                        <div class="flex items-start gap-4">
                            <div id="mobooking-service-icon-preview" class="w-16 h-16 border-2 border-dashed rounded-md flex items-center justify-center bg-muted">
                                <?php if ( $service_icon ) : ?>
                                    <span class="dashicons <?php echo esc_attr($service_icon); ?>" style="font-size: 24px; color: #666;"></span>
                                <?php else : ?>
                                    <span class="mobooking-no-icon-text text-xs text-muted-foreground"><?php esc_html_e('No Icon', 'mobooking'); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1">
                                <input type="hidden" id="mobooking-service-icon-value" name="icon" value="<?php echo esc_attr($service_icon); ?>">
                                <div class="flex gap-2 mb-2">
                                    <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('mobooking-service-icon-upload').click();">
                                        <?php esc_html_e('Upload Custom', 'mobooking'); ?>
                                    </button>
                                    <button type="button" id="mobooking-remove-service-icon-btn" class="btn btn-outline btn-sm">
                                        <?php esc_html_e('Remove', 'mobooking'); ?>
                                    </button>
                                </div>
                                <input type="file" id="mobooking-service-icon-upload" accept="image/*" style="display: none;">
                                <p class="text-xs text-muted-foreground"><?php esc_html_e('Choose from presets below or upload a custom icon.', 'mobooking'); ?></p>
                            </div>
                        </div>
                        
                        <!-- Preset Icons Grid -->
                        <div id="mobooking-preset-icons-wrapper" class="mt-4">
                            <div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(48px, 1fr)); gap: 0.5rem; max-width: 400px;">
                                <?php
                                $preset_icons = [
                                    'dashicons-admin-home', 'dashicons-building', 'dashicons-admin-tools',
                                    'dashicons-hammer', 'dashicons-admin-appearance', 'dashicons-camera',
                                    'dashicons-chart-line', 'dashicons-money', 'dashicons-calendar-alt',
                                    'dashicons-clock', 'dashicons-star-filled', 'dashicons-awards'
                                ];
                                foreach ( $preset_icons as $icon ) :
                                ?>
                                    <button type="button" class="mobooking-preset-icon-item w-12 h-12 border rounded-md flex items-center justify-center hover:border-primary transition-colors" data-icon="<?php echo esc_attr($icon); ?>">
                                        <span class="dashicons <?php echo esc_attr($icon); ?>" style="font-size: 20px; color: #666;"></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Image Section -->
                    <div class="form-group">
                        <label class="form-label"><?php esc_html_e('Service Image', 'mobooking'); ?></label>
                        <div class="flex items-start gap-4">
                            <img id="mobooking-service-image-preview" src="<?php echo esc_url($service_image_url ?: 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22150%22%20height%3D%22150%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20150%20150%22%3E%3Crect%20width%3D%22150%22%20height%3D%22150%22%20fill%3D%22%23EEEEEE%22%3E%3C%2Frect%3E%3Ctext%20x%3D%2275%22%20y%3D%2280%22%20text-anchor%3D%22middle%22%20font-size%3D%2212%22%20fill%3D%22%23AAAAAA%22%3E150x150%3C%2Ftext%3E%3C%2Fsvg%3E'); ?>" alt="Service preview" class="w-24 h-24 object-cover border rounded-md">
                            <div class="flex-1">
                                <input type="hidden" id="mobooking-service-image-url-value" name="image_url" value="<?php echo esc_attr($service_image_url); ?>">
                                <div class="flex gap-2 mb-2">
                                    <button type="button" id="mobooking-trigger-service-image-upload-btn" class="btn btn-outline btn-sm">
                                        <?php esc_html_e('Upload Image', 'mobooking'); ?>
                                    </button>
                                    <button type="button" id="mobooking-remove-service-image-btn" class="btn btn-outline btn-sm">
                                        <?php esc_html_e('Remove', 'mobooking'); ?>
                                    </button>
                                </div>
                                <input type="file" id="mobooking-service-image-upload" accept="image/*" style="display: none;">
                                <p class="text-xs text-muted-foreground"><?php esc_html_e('Recommended size: 300x300px. Max file size: 2MB.', 'mobooking'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Service Options Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?php esc_html_e('Service Options', 'mobooking'); ?></h3>
                    <p class="card-description"><?php esc_html_e('Add customizable options that customers can select when booking this service.', 'mobooking'); ?></p>
                </div>
                <div class="card-content">
                    <div id="mobooking-service-options-list" class="space-y-4">
                        <?php if ( $edit_mode && ! empty( $service_options_data ) ) : ?>
                            <?php foreach ( $service_options_data as $option_idx => $option ) : ?>
                                <div class="option-row mobooking-service-option-row">
                                    <div class="option-header">
                                        <div class="flex items-center gap-3">
                                            <span class="drag-handle mobooking-option-drag-handle">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="9" cy="12" r="1"/>
                                                    <circle cx="9" cy="5" r="1"/>
                                                    <circle cx="9" cy="19" r="1"/>
                                                    <circle cx="15" cy="12" r="1"/>
                                                    <circle cx="15" cy="5" r="1"/>
                                                    <circle cx="15" cy="19" r="1"/>
                                                </svg>
                                            </span>
                                            <h4 class="font-semibold text-foreground mobooking-option-title"><?php echo esc_html( $option['name'] ?: __('Untitled Option', 'mobooking') ); ?></h4>
                                        </div>
                                        <button type="button" class="remove-btn mobooking-remove-option-btn" title="<?php esc_attr_e('Remove option', 'mobooking'); ?>">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="m18 6-12 12"/>
                                                <path d="m6 6 12 12"/>
                                            </svg>
                                        </button>
                                    </div>

                                    <div class="mobooking-service-option-row-content space-y-4">
                                        <input type="hidden" name="options[<?php echo $option_idx; ?>][option_id]" value="<?php echo esc_attr( $option['option_id'] ); ?>">
                                        <input type="hidden" name="options[<?php echo $option_idx; ?>][is_required]" value="<?php echo esc_attr( (isset($option['is_required']) && $option['is_required'] === '1') ? '1' : '0' ); ?>">
                                        
                                        <div class="grid gap-4" style="grid-template-columns: 1fr 1fr;">
                                            <div class="form-group">
                                                <label class="form-label"><?php esc_html_e('Option Name', 'mobooking'); ?> <span class="text-destructive">*</span></label>
                                                <input type="text" name="options[<?php echo $option_idx; ?>][name]" value="<?php echo esc_attr( $option['name'] ); ?>" class="form-input w-full" required placeholder="<?php esc_attr_e('e.g., Room Size', 'mobooking'); ?>">
                                            </div>

                                            <div class="form-group">
                                                <label class="form-label"><?php esc_html_e('Type', 'mobooking'); ?></label>
                                                <div class="option-type-grid">
                                                    <label class="option-type-card">
                                                        <input type="radio" name="options[<?php echo $option_idx; ?>][type]" value="checkbox" <?php checked( $option['type'], 'checkbox' ); ?> class="option-type-radio mobooking-option-type">
                                                        <div class="option-type-content">
                                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="option-type-icon">
                                                                <rect width="18" height="18" x="3" y="3" rx="2"/>
                                                                <path d="m9 12 2 2 4-4"/>
                                                            </svg>
                                                            <span class="option-type-label"><?php esc_html_e('Checkbox', 'mobooking'); ?></span>
                                                        </div>
                                                    </label>
                                                    <label class="option-type-card">
                                                        <input type="radio" name="options[<?php echo $option_idx; ?>][type]" value="text" <?php checked( $option['type'], 'text' ); ?> class="option-type-radio mobooking-option-type">
                                                        <div class="option-type-content">
                                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="option-type-icon">
                                                                <path d="M4 7h16"/>
                                                                <path d="M9 20V4"/>
                                                                <path d="M15 20V4"/>
                                                            </svg>
                                                            <span class="option-type-label"><?php esc_html_e('Text', 'mobooking'); ?></span>
                                                        </div>
                                                    </label>
                                                    <label class="option-type-card">
                                                        <input type="radio" name="options[<?php echo $option_idx; ?>][type]" value="number" <?php checked( $option['type'], 'number' ); ?> class="option-type-radio mobooking-option-type">
                                                        <div class="option-type-content">
                                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="option-type-icon">
                                                                <circle cx="12" cy="12" r="10"/>
                                                                <path d="m15 9-6 6"/>
                                                                <path d="M9 9h.01"/>
                                                                <path d="M15 15h.01"/>
                                                            </svg>
                                                            <span class="option-type-label"><?php esc_html_e('Number', 'mobooking'); ?></span>
                                                        </div>
                                                    </label>
                                                    <label class="option-type-card">
                                                        <input type="radio" name="options[<?php echo $option_idx; ?>][type]" value="select" <?php checked( $option['type'], 'select' ); ?> class="option-type-radio mobooking-option-type">
                                                        <div class="option-type-content">
                                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="option-type-icon">
                                                                <path d="m7 15 5 5 5-5"/>
                                                                <path d="m7 9 5-5 5 5"/>
                                                            </svg>
                                                            <span class="option-type-label"><?php esc_html_e('Dropdown', 'mobooking'); ?></span>
                                                        </div>
                                                    </label>
                                                    <label class="option-type-card">
                                                        <input type="radio" name="options[<?php echo $option_idx; ?>][type]" value="radio" <?php checked( $option['type'], 'radio' ); ?> class="option-type-radio mobooking-option-type">
                                                        <div class="option-type-content">
                                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="option-type-icon">
                                                                <circle cx="12" cy="12" r="10"/>
                                                                <circle cx="12" cy="12" r="3"/>
                                                            </svg>
                                                            <span class="option-type-label"><?php esc_html_e('Radio', 'mobooking'); ?></span>
                                                        </div>
                                                    </label>
                                                    <label class="option-type-card">
                                                        <input type="radio" name="options[<?php echo $option_idx; ?>][type]" value="textarea" <?php checked( $option['type'], 'textarea' ); ?> class="option-type-radio mobooking-option-type">
                                                        <div class="option-type-content">
                                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="option-type-icon">
                                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                                <polyline points="14,2 14,8 20,8"/>
                                                                <line x1="16" y1="13" x2="8" y2="13"/>
                                                                <line x1="16" y1="17" x2="8" y2="17"/>
                                                            </svg>
                                                            <span class="option-type-label"><?php esc_html_e('Text Area', 'mobooking'); ?></span>
                                                        </div>
                                                    </label>
                                                    <label class="option-type-card">
                                                        <input type="radio" name="options[<?php echo $option_idx; ?>][type]" value="quantity" <?php checked( $option['type'], 'quantity' ); ?> class="option-type-radio mobooking-option-type">
                                                        <div class="option-type-content">
                                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="option-type-icon">
                                                                <line x1="4" y1="12" x2="20" y2="12"/>
                                                                <line x1="12" y1="4" x2="12" y2="20"/>
                                                            </svg>
                                                            <span class="option-type-label"><?php esc_html_e('Quantity', 'mobooking'); ?></span>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label"><?php esc_html_e('Description', 'mobooking'); ?></label>
                                            <textarea name="options[<?php echo $option_idx; ?>][description]" class="form-input form-textarea w-full" rows="2" placeholder="<?php esc_attr_e('Helpful description for customers...', 'mobooking'); ?>"><?php echo esc_textarea( $option['description'] ); ?></textarea>
                                        </div>

                                        <!-- Accordion for Option Choices -->
                                        <div class="accordion mobooking-accordion-choices">
                                            <div class="accordion-item">
                                                <button type="button" class="accordion-trigger" aria-expanded="false">
                                                    <span><?php esc_html_e('Option Choices', 'mobooking'); ?></span>
                                                    <svg class="chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                                </button>
                                                <div class="accordion-content mobooking-choices-accordion-content" aria-hidden="true">
                                                    <?php // Content of original .mobooking-option-values-field starts here, style is handled by JS now ?>
                                                    <div class="mobooking-option-values-field" style="<?php echo ( in_array( $option['type'], ['select', 'radio', 'checkbox'] ) ? '' : 'display:none;' ); ?>">
                                                        <div class="form-group">
                                                            <?php // The label that was here is now part of the accordion trigger ?>
                                                            <div class="mobooking-choices-ui-container">
                                                                <div class="mobooking-choices-list space-y-2">
                                                                    <!-- Choices will be rendered here by JavaScript -->
                                                                </div>
                                                                <button type="button" class="btn btn-outline btn-sm mobooking-add-choice-btn mt-3">
                                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                                                                    <?php esc_html_e('Add Choice', 'mobooking'); ?>
                                                                </button>
                                                            </div>
                                                            <textarea name="options[<?php echo $option_idx; ?>][option_values]" class="form-input form-textarea w-full text-xs mt-3" rows="3" placeholder='[{"value":"opt1","label":"Choice 1"}]' readonly><?php
                                                                $ov_json = '';
                                                                if (isset($option['option_values'])) {
                                                                    $ov_json = is_array($option['option_values']) ? wp_json_encode($option['option_values']) : $option['option_values'];
                                                                }
                                                                echo esc_textarea( $ov_json );
                                                            ?></textarea>
                                                            <p class="text-xs text-muted-foreground mt-1"><?php esc_html_e('This data is auto-generated. Use the interface above to manage choices.', 'mobooking'); ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Accordion for Pricing & Requirements -->
                                        <div class="accordion mobooking-accordion-pricing">
                                            <div class="accordion-item">
                                                <button type="button" class="accordion-trigger" aria-expanded="false">
                                                    <span><?php esc_html_e('Pricing & Requirements', 'mobooking'); ?></span>
                                                    <svg class="chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                                </button>
                                                <div class="accordion-content" aria-hidden="true">
                                                    <?php // Original content of .pricing-requirements-container starts here ?>
                                                    <div class="pricing-requirements-container">
                                                        <div class="flex items-center gap-4 mb-4">
                                                            <label class="flex items-center gap-2 cursor-pointer">
                                                        <input type="checkbox" name="options[<?php echo $option_idx; ?>][is_required_cb]" value="1" <?php checked( $option['is_required'], '1' ); ?> class="w-4 h-4 text-primary border border-gray-300 rounded focus:ring-primary">
                                                        <span class="text-sm font-medium"><?php esc_html_e('Required field', 'mobooking'); ?></span>
                                                    </label>
                                                </div>

                                                <div class="pricing-type-section mb-4">
                                                    <label class="form-label mb-3"><?php esc_html_e('Price Impact Type', 'mobooking'); ?></label>
                                                    <div class="pricing-type-grid">
                                                        <label class="pricing-type-card">
                                                            <input type="radio" name="options[<?php echo $option_idx; ?>][price_impact_type]" value="none" <?php checked( $option['price_impact_type'], 'none' ); ?> class="pricing-type-radio">
                                                            <div class="pricing-type-content">
                                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="pricing-type-icon">
                                                                    <circle cx="12" cy="12" r="10"/>
                                                                    <path d="m15 9-6 6"/>
                                                                    <path d="m9 9 6 6"/>
                                                                </svg>
                                                                <div class="pricing-type-text">
                                                                    <span class="pricing-type-title"><?php esc_html_e('No Change', 'mobooking'); ?></span>
                                                                    <span class="pricing-type-desc"><?php esc_html_e('Free option', 'mobooking'); ?></span>
                                                                </div>
                                                            </div>
                                                        </label>
                                                        <label class="pricing-type-card">
                                                            <input type="radio" name="options[<?php echo $option_idx; ?>][price_impact_type]" value="fixed" <?php checked( $option['price_impact_type'], 'fixed' ); ?> class="pricing-type-radio">
                                                            <div class="pricing-type-content">
                                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="pricing-type-icon">
                                                                    <line x1="12" y1="1" x2="12" y2="23"/>
                                                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                                                </svg>
                                                                <div class="pricing-type-text">
                                                                    <span class="pricing-type-title"><?php esc_html_e('Fixed Amount', 'mobooking'); ?></span>
                                                                    <span class="pricing-type-desc"><?php esc_html_e('Add/subtract fixed price', 'mobooking'); ?></span>
                                                                </div>
                                                            </div>
                                                        </label>
                                                        <label class="pricing-type-card">
                                                            <input type="radio" name="options[<?php echo $option_idx; ?>][price_impact_type]" value="percentage" <?php checked( $option['price_impact_type'], 'percentage' ); ?> class="pricing-type-radio">
                                                            <div class="pricing-type-content">
                                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="pricing-type-icon">
                                                                    <circle cx="12" cy="12" r="10"/>
                                                                    <path d="m15 9-6 6"/>
                                                                    <path d="m9 9 h.01"/>
                                                                    <path d="m15 15 h.01"/>
                                                                </svg>
                                                                <div class="pricing-type-text">
                                                                    <span class="pricing-type-title"><?php esc_html_e('Percentage', 'mobooking'); ?></span>
                                                                    <span class="pricing-type-desc"><?php esc_html_e('% of base price', 'mobooking'); ?></span>
                                                                </div>
                                                            </div>
                                                        </label>
                                                        <label class="pricing-type-card">
                                                            <input type="radio" name="options[<?php echo $option_idx; ?>][price_impact_type]" value="multiply_value" <?php checked( $option['price_impact_type'], 'multiply_value' ); ?> class="pricing-type-radio">
                                                            <div class="pricing-type-content">
                                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="pricing-type-icon">
                                                                    <path d="m5 9 3-3 3 3"/>
                                                                    <path d="m13 15 3 3 3-3"/>
                                                                    <path d="M8 6v12"/>
                                                                    <path d="M16 18V6"/>
                                                                </svg>
                                                                <div class="pricing-type-text">
                                                                    <span class="pricing-type-title"><?php esc_html_e('Multiply Value', 'mobooking'); ?></span>
                                                                    <span class="pricing-type-desc"><?php esc_html_e('Price × quantity', 'mobooking'); ?></span>
                                                                </div>
                                                            </div>
                                                        </label>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="form-label"><?php esc_html_e('Price Impact Value', 'mobooking'); ?></label>
                                                    <input type="number" name="options[<?php echo $option_idx; ?>][price_impact_value]" value="<?php echo esc_attr( $option['price_impact_value'] ); ?>" class="form-input w-full" step="0.01" min="0" placeholder="0.00">
                                                    <p class="text-xs text-muted-foreground mt-1"><?php esc_html_e('Enter the value used for price calculations based on the selected impact type.', 'mobooking'); ?></p>
                                                </div>
                                            </div>
                                            <?php // Original content of .pricing-requirements-container ends here ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <div class="text-center py-8 text-muted-foreground">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-4 opacity-50">
                                    <rect width="18" height="18" x="3" y="3" rx="2"/>
                                    <path d="M9 9h6v6H9z"/>
                                </svg>
                                <p class="font-medium"><?php esc_html_e('No options created yet', 'mobooking'); ?></p>
                                <p class="text-sm"><?php esc_html_e('Click "Add Option" to create customizable choices for your service.', 'mobooking'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mt-6 pt-4 border-t">
                        <button type="button" id="mobooking-add-service-option-btn" class="btn btn-outline">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                                <path d="M5 12h14"/>
                                <path d="M12 5v14"/>
                            </svg>
                            <?php esc_html_e('Add Option', 'mobooking'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="card">
                <div class="card-content">
                    <div class="flex items-center justify-between gap-4">
                        <div class="hidden" id="mobooking-service-form-feedback">
                            <!-- Feedback messages will be shown here -->
                        </div>
                        <div class="flex items-center gap-3 ml-auto">
                            <button type="button" id="mobooking-cancel-service-edit-btn" class="btn btn-secondary">
                                <?php esc_html_e('Cancel', 'mobooking'); ?>
                            </button>
                            <button type="submit" id="mobooking-save-service-btn" class="btn btn-primary">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                                    <polyline points="17,21 17,13 7,13 7,21"/>
                                    <polyline points="7,3 7,8 15,8"/>
                                </svg>
                                <?php echo $edit_mode ? esc_html__('Update Service', 'mobooking') : esc_html__('Create Service', 'mobooking'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Script Templates for Dynamic Content -->
<script type="text/template" id="mobooking-service-option-template">
    <div class="option-row mobooking-service-option-row">
        <div class="option-header">
            <div class="flex items-center gap-3">
                <span class="drag-handle mobooking-option-drag-handle">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="12" r="1"/>
                        <circle cx="9" cy="5" r="1"/>
                        <circle cx="9" cy="19" r="1"/>
                        <circle cx="15" cy="12" r="1"/>
                        <circle cx="15" cy="5" r="1"/>
                        <circle cx="15" cy="19" r="1"/>
                    </svg>
                </span>
                <h4 class="font-semibold text-foreground mobooking-option-title"><?php esc_html_e('New Option', 'mobooking'); ?></h4>
            </div>
            <button type="button" class="remove-btn mobooking-remove-option-btn" title="<?php esc_attr_e('Remove option', 'mobooking'); ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m18 6-12 12"/>
                    <path d="m6 6 12 12"/>
                </svg>
            </button>
        </div>

        <div class="mobooking-service-option-row-content space-y-4">
            <input type="hidden" name="options[][option_id]" value="">
            <input type="hidden" name="options[][is_required]" value="0"> <!-- Default to 0 (not required) -->
            
            <div class="grid gap-4" style="grid-template-columns: 1fr 1fr;">
                <div class="form-group">
                    <label class="form-label"><?php esc_html_e('Option Name', 'mobooking'); ?> <span class="text-destructive">*</span></label>
                    <input type="text" name="options[][name]" class="form-input w-full" required placeholder="<?php esc_attr_e('e.g., Room Size', 'mobooking'); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label"><?php esc_html_e('Type', 'mobooking'); ?></label>
                    <div class="option-type-grid">
                        <label class="option-type-card">
                            <input type="radio" name="options[][type]" value="checkbox" class="option-type-radio mobooking-option-type" checked> <!-- Default to checkbox -->
                            <div class="option-type-content">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="option-type-icon"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="m9 12 2 2 4-4"/></svg>
                                <span class="option-type-label"><?php esc_html_e('Checkbox', 'mobooking'); ?></span>
                            </div>
                        </label>
                        <label class="option-type-card">
                            <input type="radio" name="options[][type]" value="text" class="option-type-radio mobooking-option-type">
                            <div class="option-type-content">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="option-type-icon"><path d="M4 7h16"/><path d="M9 20V4"/><path d="M15 20V4"/></svg>
                                <span class="option-type-label"><?php esc_html_e('Text', 'mobooking'); ?></span>
                            </div>
                        </label>
                        <label class="option-type-card">
                            <input type="radio" name="options[][type]" value="number" class="option-type-radio mobooking-option-type">
                            <div class="option-type-content">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="option-type-icon"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="M9 9h.01"/><path d="M15 15h.01"/></svg>
                                <span class="option-type-label"><?php esc_html_e('Number', 'mobooking'); ?></span>
                            </div>
                        </label>
                        <label class="option-type-card">
                            <input type="radio" name="options[][type]" value="select" class="option-type-radio mobooking-option-type">
                            <div class="option-type-content">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="option-type-icon"><path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/></svg>
                                <span class="option-type-label"><?php esc_html_e('Dropdown', 'mobooking'); ?></span>
                            </div>
                        </label>
                        <label class="option-type-card">
                            <input type="radio" name="options[][type]" value="radio" class="option-type-radio mobooking-option-type">
                            <div class="option-type-content">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="option-type-icon"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg>
                                <span class="option-type-label"><?php esc_html_e('Radio', 'mobooking'); ?></span>
                            </div>
                        </label>
                        <label class="option-type-card">
                            <input type="radio" name="options[][type]" value="textarea" class="option-type-radio mobooking-option-type">
                            <div class="option-type-content">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="option-type-icon"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                                <span class="option-type-label"><?php esc_html_e('Text Area', 'mobooking'); ?></span>
                            </div>
                        </label>
                        <label class="option-type-card">
                            <input type="radio" name="options[][type]" value="quantity" class="option-type-radio mobooking-option-type">
                            <div class="option-type-content">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="option-type-icon"><line x1="4" y1="12" x2="20" y2="12"/><line x1="12" y1="4" x2="12" y2="20"/></svg>
                                <span class="option-type-label"><?php esc_html_e('Quantity', 'mobooking'); ?></span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label"><?php esc_html_e('Description', 'mobooking'); ?></label>
                <textarea name="options[][description]" class="form-input form-textarea w-full" rows="2" placeholder="<?php esc_attr_e('Helpful description for customers...', 'mobooking'); ?>"></textarea>
            </div>

            <!-- Collapsible Choices Section -->
            <div class="mobooking-option-values-field" style="display:none;">
                <div class="accordion">
                    <div class="accordion-item">
                        <button type="button" class="accordion-trigger" aria-expanded="false">
                            <span><?php esc_html_e('Option Choices', 'mobooking'); ?></span>
                            <svg class="chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m6 9 6 6 6-6"/>
                            </svg>
                        </button>
                        <div class="accordion-content mobooking-choices-accordion-content" aria-hidden="true">
                            <div class="space-y-3">
                                <div class="mobooking-choices-ui-container">
                                    <div class="mobooking-choices-list space-y-2">
                                        <!-- Choices will be rendered here -->
                                    </div>
                                    <button type="button" class="btn btn-outline btn-sm mobooking-add-choice-btn mt-3">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                                            <path d="M5 12h14"/>
                                            <path d="M12 5v14"/>
                                        </svg>
                                        <?php esc_html_e('Add Choice', 'mobooking'); ?>
                                    </button>
                                </div>
                                <textarea name="options[][option_values]" class="form-input form-textarea w-full text-xs" rows="3" placeholder='[{"value":"opt1","label":"Choice 1"}]' readonly></textarea>
                                <p class="text-xs text-muted-foreground"><?php esc_html_e('This data is auto-generated. Use the interface above to manage choices.', 'mobooking'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Collapsible Pricing Section -->
            <div class="accordion">
                <div class="accordion-item">
                    <button type="button" class="accordion-trigger" aria-expanded="false">
                        <span><?php esc_html_e('Pricing & Requirements', 'mobooking'); ?></span>
                        <svg class="chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                    </button>
                    <div class="accordion-content" aria-hidden="true">
                        <div class="space-y-4">
                            <div class="flex items-center gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="options[][is_required_cb]" value="1" class="w-4 h-4 text-primary border border-gray-300 rounded focus:ring-primary">
                                    <span class="text-sm font-medium"><?php esc_html_e('Required field', 'mobooking'); ?></span>
                                </label>
                            </div>

                            <div class="grid gap-4" style="grid-template-columns: 1fr 1fr;">
                                <div class="form-group">
                                    <label class="form-label mb-3"><?php esc_html_e('Price Impact Type', 'mobooking'); ?></label>
                                    <div class="pricing-type-grid">
                                        <label class="pricing-type-card">
                                            <input type="radio" name="options[][price_impact_type]" value="none" checked class="pricing-type-radio">
                                            <div class="pricing-type-content">
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="pricing-type-icon"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
                                                <div class="pricing-type-text">
                                                    <span class="pricing-type-title"><?php esc_html_e('No Change', 'mobooking'); ?></span>
                                                    <span class="pricing-type-desc"><?php esc_html_e('Free option', 'mobooking'); ?></span>
                                                </div>
                                            </div>
                                        </label>
                                        <label class="pricing-type-card">
                                            <input type="radio" name="options[][price_impact_type]" value="fixed" class="pricing-type-radio">
                                            <div class="pricing-type-content">
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="pricing-type-icon"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                                <div class="pricing-type-text">
                                                    <span class="pricing-type-title"><?php esc_html_e('Fixed Amount', 'mobooking'); ?></span>
                                                    <span class="pricing-type-desc"><?php esc_html_e('Add/subtract fixed price', 'mobooking'); ?></span>
                                                </div>
                                            </div>
                                        </label>
                                        <label class="pricing-type-card">
                                            <input type="radio" name="options[][price_impact_type]" value="percentage" class="pricing-type-radio">
                                            <div class="pricing-type-content">
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="pricing-type-icon"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 h.01"/><path d="m15 15 h.01"/></svg>
                                                <div class="pricing-type-text">
                                                    <span class="pricing-type-title"><?php esc_html_e('Percentage', 'mobooking'); ?></span>
                                                    <span class="pricing-type-desc"><?php esc_html_e('% of base price', 'mobooking'); ?></span>
                                                </div>
                                            </div>
                                        </label>
                                        <label class="pricing-type-card">
                                            <input type="radio" name="options[][price_impact_type]" value="multiply_value" class="pricing-type-radio">
                                            <div class="pricing-type-content">
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="pricing-type-icon"><path d="m5 9 3-3 3 3"/><path d="m13 15 3 3 3-3"/><path d="M8 6v12"/><path d="M16 18V6"/></svg>
                                                <div class="pricing-type-text">
                                                    <span class="pricing-type-title"><?php esc_html_e('Multiply Value', 'mobooking'); ?></span>
                                                    <span class="pricing-type-desc"><?php esc_html_e('Price × quantity', 'mobooking'); ?></span>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label"><?php esc_html_e('Price Impact Value', 'mobooking'); ?></label>
                                    <input type="number" name="options[][price_impact_value]" class="form-input w-full" step="0.01" min="0" placeholder="0.00">
                                </div>
                            </div>

                            <p class="text-xs text-muted-foreground"><?php esc_html_e('Configure how this option affects the total service price.', 'mobooking'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>

<script type="text/template" id="mobooking-choice-item-template">
    <div class="choice-item mobooking-choice-item">
        <div class="flex items-center gap-3">
            <span class="drag-handle mobooking-choice-drag-handle">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="12" r="1"/>
                    <circle cx="9" cy="5" r="1"/>
                    <circle cx="9" cy="19" r="1"/>
                    <circle cx="15" cy="12" r="1"/>
                    <circle cx="15" cy="5" r="1"/>
                    <circle cx="15" cy="19" r="1"/>
                </svg>
            </span>
            <input type="checkbox" class="mobooking-choice-item-checked-state" style="margin-right: 5px; display: none;">
            <div class="grid gap-2 flex-1" style="grid-template-columns: 1fr 1fr auto auto;">
                <input type="text" class="form-input form-input-sm mobooking-choice-label" placeholder="<?php esc_attr_e('Label', 'mobooking'); ?>">
                <input type="text" class="form-input form-input-sm mobooking-choice-value" placeholder="<?php esc_attr_e('Value', 'mobooking'); ?>">
                <input type="number" class="form-input form-input-sm mobooking-choice-price-adjust" placeholder="0.00" step="0.01" style="max-width: 80px;" title="<?php esc_attr_e('Price adjustment', 'mobooking'); ?>">
                <button type="button" class="remove-btn mobooking-remove-choice-btn" title="<?php esc_attr_e('Remove choice', 'mobooking'); ?>">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m18 6-12 12"/>
                        <path d="m6 6 12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</script>

<script>
// Enhanced JavaScript for Shadcn UI interactions
document.addEventListener('DOMContentLoaded', function() {
    // Status toggle functionality
    const statusToggle = document.getElementById('mobooking-service-status-toggle');
    const statusInput = document.getElementById('mobooking-service-status');
    
    if (statusToggle && statusInput) {
        statusToggle.addEventListener('click', function() {
            const isActive = this.classList.contains('active');
            if (isActive) {
                this.classList.remove('active');
                this.setAttribute('aria-pressed', 'false');
                statusInput.value = 'inactive';
            } else {
                this.classList.add('active');
                this.setAttribute('aria-pressed', 'true');
                statusInput.value = 'active';
            }
        });
    }

    // Accordion functionality
    function initAccordions() {
        document.querySelectorAll('.accordion-trigger').forEach(trigger => {
            trigger.addEventListener('click', function() {
                const isExpanded = this.getAttribute('aria-expanded') === 'true';
                const content = this.nextElementSibling;
                
                if (isExpanded) {
                    this.setAttribute('aria-expanded', 'false');
                    content.setAttribute('aria-hidden', 'true');
                } else {
                    this.setAttribute('aria-expanded', 'true');
                    content.setAttribute('aria-hidden', 'false');
                }
            });
        });
    }

    // Icon selection functionality
    document.querySelectorAll('.mobooking-preset-icon-item').forEach(item => {
        item.addEventListener('click', function() {
            const icon = this.dataset.icon;
            const preview = document.getElementById('mobooking-service-icon-preview');
            const input = document.getElementById('mobooking-service-icon-value');
            
            // Remove selected class from all items
            document.querySelectorAll('.mobooking-preset-icon-item').forEach(i => i.classList.remove('selected'));
            
            // Add selected class to clicked item
            this.classList.add('selected');
            
            // Update preview and input
            if (preview && input) {
                preview.innerHTML = `<span class="dashicons ${icon}" style="font-size: 24px; color: #666;"></span>`;
                input.value = icon;
            }
        });
    });

    // Remove icon functionality
    const removeIconBtn = document.getElementById('mobooking-remove-service-icon-btn');
    if (removeIconBtn) {
        removeIconBtn.addEventListener('click', function() {
            const preview = document.getElementById('mobooking-service-icon-preview');
            const input = document.getElementById('mobooking-service-icon-value');
            
            if (preview && input) {
                preview.innerHTML = '<span class="mobooking-no-icon-text text-xs text-muted-foreground"><?php esc_html_e('No Icon', 'mobooking'); ?></span>';
                input.value = '';
                
                // Remove selected class from all preset icons
                document.querySelectorAll('.mobooking-preset-icon-item').forEach(i => i.classList.remove('selected'));
            }
        });
    }

    // Initialize accordions
    initAccordions();

    // Re-initialize accordions when new options are added
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                initAccordions();
            }
        });
    });

    const optionsList = document.getElementById('mobooking-service-options-list');
    if (optionsList) {
        observer.observe(optionsList, { childList: true, subtree: true });
    }

    // Form validation enhancement
    const form = document.getElementById('mobooking-service-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Basic client-side validation
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('border-destructive');
                    isValid = false;
                } else {
                    field.classList.remove('border-destructive');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showFeedback('Please fill in all required fields.', 'error');
            }
        });
    }

    // Feedback display function
    function showFeedback(message, type = 'info') {
        const feedback = document.getElementById('mobooking-service-form-feedback');
        if (feedback) {
            feedback.className = `p-3 rounded-md text-sm font-medium ${
                type === 'error' ? 'bg-red-50 text-red-800 border border-red-200' :
                type === 'success' ? 'bg-green-50 text-green-800 border border-green-200' :
                'bg-blue-50 text-blue-800 border border-blue-200'
            }`;
            feedback.textContent = message;
            feedback.classList.remove('hidden');
            
            // Auto-hide after 5 seconds for success messages
            if (type === 'success') {
                setTimeout(() => {
                    feedback.classList.add('hidden');
                }, 5000);
            }
        }
    }

    // Radio button visual feedback for option types
    document.addEventListener('change', function(e) {
        if (e.target.matches('.option-type-radio')) {
            // Remove visual selection from all option type cards in the same group
            const optionRow = e.target.closest('.mobooking-service-option-row');
            if (optionRow) {
                const allCards = optionRow.querySelectorAll('.option-type-card');
                allCards.forEach(card => {
                    card.classList.remove('selected');
                });
                
                // Add visual selection to the selected card
                const selectedCard = e.target.closest('.option-type-card');
                if (selectedCard) {
                    selectedCard.classList.add('selected');
                }
            }
        }
    });

    // Radio button visual feedback for pricing types
    document.addEventListener('change', function(e) {
        if (e.target.matches('.pricing-type-radio')) {
            // Remove visual selection from all pricing type cards in the same group
            const optionRow = e.target.closest('.mobooking-service-option-row');
            if (optionRow) {
                const allCards = optionRow.querySelectorAll('.pricing-type-card');
                allCards.forEach(card => {
                    card.classList.remove('selected');
                });
                
                // Add visual selection to the selected card
                const selectedCard = e.target.closest('.pricing-type-card');
                if (selectedCard) {
                    selectedCard.classList.add('selected');
                }
            }
        }
    });

    // Image upload handling
    const imageUploadBtn = document.getElementById('mobooking-trigger-service-image-upload-btn');
    const imageUploadInput = document.getElementById('mobooking-service-image-upload');
    const imagePreview = document.getElementById('mobooking-service-image-preview');
    const imageUrlInput = document.getElementById('mobooking-service-image-url-value');
    
    if (imageUploadBtn && imageUploadInput) {
        imageUploadBtn.addEventListener('click', function() {
            imageUploadInput.click();
        });
        
        imageUploadInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (imagePreview) {
                        imagePreview.src = e.target.result;
                    }
                    if (imageUrlInput) {
                        imageUrlInput.value = e.target.result;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Remove image functionality
    const removeImageBtn = document.getElementById('mobooking-remove-service-image-btn');
    if (removeImageBtn) {
        removeImageBtn.addEventListener('click', function() {
            if (imagePreview && imageUrlInput) {
                imagePreview.src = 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22150%22%20height%3D%22150%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20150%20150%22%3E%3Crect%20width%3D%22150%22%20height%3D%22150%22%20fill%3D%22%23EEEEEE%22%3E%3C%2Frect%3E%3Ctext%20x%3D%2275%22%20y%3D%2280%22%20text-anchor%3D%22middle%22%20font-size%3D%2212%22%20fill%3D%22%23AAAAAA%22%3ENo%20Image%3C%2Ftext%3E%3C%2Fsvg%3E';
                imageUrlInput.value = '';
            }
        });
    }

    // Cancel button functionality
    const cancelBtn = document.getElementById('mobooking-cancel-service-edit-btn');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            if (confirm('<?php esc_html_e('Are you sure you want to cancel? Any unsaved changes will be lost.', 'mobooking'); ?>')) {
                window.location.href = '<?php echo esc_url(home_url('/dashboard/services/')); ?>';
            }
        });
    }

    // Auto-resize textareas
    document.addEventListener('input', function(e) {
        if (e.target.matches('textarea.form-textarea')) {
            e.target.style.height = 'auto';
            e.target.style.height = e.target.scrollHeight + 'px';
        }
    });

    // Initialize existing textareas
    document.querySelectorAll('textarea.form-textarea').forEach(textarea => {
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
    });
});

// CSS for form input small variant
const style = document.createElement('style');
style.textContent = `
    .form-input-sm {
        height: 2rem;
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    .border-destructive {
        border-color: hsl(0 84.2% 60.2%) !important;
    }
    .choice-item .grid {
        align-items: center;
    }
    .option-row {
        position: relative;
    }
    .option-row:hover {
        transform: translateY(-1px);
    }
    @media (max-width: 640px) {
        .choice-item .grid {
            grid-template-columns: 1fr;
            gap: 0.5rem;
        }
        .choice-item .grid > * {
            width: 100%;
        }
    }
`;
document.head.appendChild(style);
</script>

<?php
// The existing JavaScript file will still be loaded and will handle the core AJAX functionality
// This inline script enhances the UI interactions specific to the Shadcn design
?>