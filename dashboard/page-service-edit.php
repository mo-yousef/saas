<?php
/**
 * Dashboard Page: Add/Edit Service - Clean Single Column with Tabs
 * @package MoBooking
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Check user permissions
if ( ! current_user_can( 'mobooking_business_owner' ) ) {
    wp_die( __( 'You do not have sufficient permissions to access this page.', 'mobooking' ) );
}


// Page setup
$edit_mode = false;
$service_id = 0;
if ( isset( $_GET['service_id'] ) && ! empty( $_GET['service_id'] ) ) {
    $edit_mode = true;
    $service_id = intval( $_GET['service_id'] );
    $page_title = __( 'Edit Service', 'mobooking' );
} else {
    $page_title = __( 'Add New Service', 'mobooking' );
}

// Initialize variables
$service_name = '';
$service_description = '';
$service_price = '';
$service_duration = '';
$service_icon = '';
$service_image_url = '';
$service_status = 'active';
$service_options_data = [];
$error_message = '';

// Get settings
$breadcrumb_services = admin_url('admin.php?page=mobooking-services');
$user_id = get_current_user_id();
$settings_manager = new \MoBooking\Classes\Settings();
$biz_settings = $settings_manager->get_business_settings($user_id);
$currency_symbol = $biz_settings['biz_currency_symbol'];
$currency_pos = $biz_settings['biz_currency_position'];

// Define option types
$option_types = [
    'checkbox' => ['label' => __('Checkbox', 'mobooking'), 'description' => __('Simple yes/no choice', 'mobooking'), 'icon' => 'check-square'],
    'text' => ['label' => __('Text Input', 'mobooking'), 'description' => __('Short text field', 'mobooking'), 'icon' => 'type'],
    'number' => ['label' => __('Number', 'mobooking'), 'description' => __('Numeric input only', 'mobooking'), 'icon' => 'hash'],
    'select' => ['label' => __('Dropdown', 'mobooking'), 'description' => __('Choose one from list', 'mobooking'), 'icon' => 'chevron-down'],
    'radio' => ['label' => __('Radio Buttons', 'mobooking'), 'description' => __('Single choice selection', 'mobooking'), 'icon' => 'circle'],
    'textarea' => ['label' => __('Text Area', 'mobooking'), 'description' => __('Multi-line text', 'mobooking'), 'icon' => 'file-text'],
    'quantity' => ['label' => __('Quantity', 'mobooking'), 'description' => __('Number with +/- buttons', 'mobooking'), 'icon' => 'plus-minus'],
    'sqm' => ['label' => __('Square Meter', 'mobooking'), 'description' => __('Area calculation', 'mobooking'), 'icon' => 'square']
];

// Define price types
$price_types = [
    '' => ['label' => __('No Price Impact', 'mobooking'), 'description' => __('This option doesn\'t affect the price', 'mobooking'), 'icon' => 'minus'],
    'fixed' => ['label' => __('Fixed Amount', 'mobooking'), 'description' => __('Add/subtract a fixed amount', 'mobooking'), 'icon' => 'dollar-sign'],
    'percentage' => ['label' => __('Percentage', 'mobooking'), 'description' => __('Increase/decrease by percentage', 'mobooking'), 'icon' => 'percent'],
    'multiply' => ['label' => __('Multiply', 'mobooking'), 'description' => __('Multiply price by option value', 'mobooking'), 'icon' => 'x']
];

// Fetch service data if editing
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
?>

<style>
:root {
  --background: 0 0% 100%;
  --foreground: 222.2 84% 4.9%;
  --card: 0 0% 100%;
  --card-foreground: 222.2 84% 4.9%;
  --primary: 222.2 47.4% 11.2%;
  --primary-foreground: 210 40% 98%;
  --secondary: 210 40% 96%;
  --secondary-foreground: 222.2 84% 4.9%;
  --muted: 210 40% 96%;
  --muted-foreground: 215.4 16.3% 46.9%;
  --accent: 210 40% 96%;
  --accent-foreground: 222.2 84% 4.9%;
  --destructive: 0 62.8% 30.6%;
  --destructive-foreground: 210 40% 98%;
  --border: 214.3 31.8% 91.4%;
  --input: 214.3 31.8% 91.4%;
  --ring: 222.2 84% 4.9%;
  --radius: 0.5rem;
}

.mobooking-wrap {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
}

/* Page Header */
.page-header {
  margin-bottom: 2rem;
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
  font-size: 0.875rem;
  color: hsl(var(--muted-foreground));
}

.breadcrumb-link {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  color: hsl(var(--foreground));
  text-decoration: none;
  transition: color 0.2s;
}

.breadcrumb-link:hover {
  color: hsl(var(--primary));
}

.breadcrumb-separator {
  color: hsl(var(--muted-foreground));
}

.breadcrumb-current {
  color: hsl(var(--muted-foreground));
}

.page-header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
}

.page-title {
  font-size: 1.875rem;
  font-weight: 600;
  color: hsl(var(--foreground));
  margin: 0 0 0.5rem 0;
}

.page-description {
  color: hsl(var(--muted-foreground));
  margin: 0;
  max-width: 600px;
}

.page-header-actions {
  display: flex;
  gap: 0.5rem;
  flex-shrink: 0;
}

/* Alerts */
.alert {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  border-radius: var(--radius);
  border: 1px solid;
  margin-bottom: 1.5rem;
}

.alert-destructive {
  border-color: hsl(var(--destructive));
  background-color: hsl(var(--destructive) / 0.1);
  color: hsl(var(--destructive));
}

.alert-success {
  border-color: hsl(120 60% 50%);
  background-color: hsl(120 60% 50% / 0.1);
  color: hsl(120 60% 30%);
}

/* Form */
.service-form {
  margin-bottom: 2rem;
}

/* Tabs */
.tabs {
  width: 100%;
}

.tabs-list {
  display: flex;
  background-color: hsl(var(--muted));
  border-radius: var(--radius);
  padding: 0.25rem;
  margin-bottom: 1.5rem;
}

.tabs-trigger {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: calc(var(--radius) - 0.125rem);
  background: transparent;
  border: none;
  color: hsl(var(--muted-foreground));
  cursor: pointer;
  transition: all 0.2s;
  flex: 1;
}

.tabs-trigger:hover {
  background-color: hsl(var(--accent));
  color: hsl(var(--accent-foreground));
}

.tabs-trigger.active {
  background-color: hsl(var(--background));
  color: hsl(var(--foreground));
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.tabs-content {
  display: none;
}

.tabs-content.active {
  display: block;
}

/* Cards */
.card {
  background-color: hsl(var(--card));
  border: 1px solid hsl(var(--border));
  border-radius: var(--radius);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.card-header {
  padding: 1.5rem;
  border-bottom: 1px solid hsl(var(--border));
}

.card-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: hsl(var(--card-foreground));
  margin: 0 0 0.25rem 0;
}

.card-description {
  color: hsl(var(--muted-foreground));
  font-size: 0.875rem;
  margin: 0;
}

.card-content {
  padding: 1.5rem;
}

/* Form Elements */
.form-label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  color: hsl(var(--foreground));
  margin-bottom: 0.5rem;
}

.form-input,
.form-textarea {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid hsl(var(--border));
  border-radius: var(--radius);
  background-color: hsl(var(--background));
  color: hsl(var(--foreground));
  font-size: 0.875rem;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.form-input:focus,
.form-textarea:focus {
  outline: none;
  border-color: hsl(var(--ring));
  box-shadow: 0 0 0 2px hsl(var(--ring) / 0.2);
}

.form-description {
  font-size: 0.75rem;
  color: hsl(var(--muted-foreground));
  margin-top: 0.25rem;
  margin-bottom: 0;
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: var(--radius);
  border: 1px solid;
  cursor: pointer;
  transition: all 0.2s;
  text-decoration: none;
}

.btn-sm {
  padding: 0.375rem 0.75rem;
  font-size: 0.8125rem;
}

.btn-primary {
  background-color: hsl(var(--primary));
  border-color: hsl(var(--primary));
  color: hsl(var(--primary-foreground));
}

.btn-primary:hover {
  background-color: hsl(var(--primary) / 0.9);
}

.btn-outline {
  background-color: transparent;
  border-color: hsl(var(--border));
  color: hsl(var(--foreground));
}

.btn-outline:hover {
  background-color: hsl(var(--accent));
  color: hsl(var(--accent-foreground));
}

.btn-destructive {
  background-color: hsl(var(--destructive));
  border-color: hsl(var(--destructive));
  color: hsl(var(--destructive-foreground));
}

.btn-destructive:hover {
  background-color: hsl(var(--destructive) / 0.9);
}

.btn-ghost {
  background-color: transparent;
  border-color: transparent;
  color: hsl(var(--foreground));
}

.btn-ghost:hover {
  background-color: hsl(var(--accent));
  color: hsl(var(--accent-foreground));
}

/* Switch */
.switch {
  position: relative;
  display: inline-flex;
  height: 1.5rem;
  width: 2.75rem;
  cursor: pointer;
  border-radius: 9999px;
  border: none;
  background-color: hsl(var(--input));
  transition: background-color 0.2s;
}

.switch.switch-checked {
  background-color: hsl(var(--primary));
}

.switch-thumb {
  display: block;
  height: 1.25rem;
  width: 1.25rem;
  border-radius: 50%;
  background-color: hsl(var(--background));
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s;
  transform: translateX(0.125rem);
}

.switch.switch-checked .switch-thumb {
  transform: translateX(1.375rem);
}

/* Badges */
.badge {
  display: inline-flex;
  align-items: center;
  padding: 0.125rem 0.5rem;
  font-size: 0.75rem;
  font-weight: 500;
  border-radius: calc(var(--radius) - 0.125rem);
}

.badge-outline {
  border: 1px solid hsl(var(--border));
  background-color: transparent;
  color: hsl(var(--foreground));
}

.badge-secondary {
  background-color: hsl(var(--secondary));
  color: hsl(var(--secondary-foreground));
}

.badge-accent {
  background-color: hsl(var(--accent));
  color: hsl(var(--accent-foreground));
}

/* Utilities */
.space-y-4 > * + * { margin-top: 1rem; }
.space-y-6 > * + * { margin-top: 1.5rem; }
.space-y-2 > * + * { margin-top: 0.5rem; }
.grid { display: grid; }
.grid-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
.gap-2 { gap: 0.5rem; }
.gap-4 { gap: 1rem; }
.gap-6 { gap: 1.5rem; }
.flex { display: flex; }
.items-center { align-items: center; }
.justify-between { justify-content: space-between; }
.flex-1 { flex: 1 1 0%; }
.w-24 { width: 6rem; }
.mt-2 { margin-top: 0.5rem; }
.mt-3 { margin-top: 0.75rem; }
.mt-4 { margin-top: 1rem; }
.relative { position: relative; }
.text-destructive { color: hsl(var(--destructive)); }
.text-sm { font-size: 0.875rem; }
.text-xs { font-size: 0.75rem; }
.text-muted-foreground { color: hsl(var(--muted-foreground)); }
.font-medium { font-weight: 500; }

@media (min-width: 768px) {
  .md\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .md\:grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
  .md\:col-span-2 { grid-column: span 2 / span 2; }
}

/* Input Prefixes and Suffixes */
.input-prefix,
.input-suffix {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 0.75rem;
  background-color: hsl(var(--muted));
  border: 1px solid hsl(var(--border));
  font-size: 0.875rem;
  color: hsl(var(--muted-foreground));
}

.input-prefix {
  left: 0;
  border-right: none;
  border-radius: var(--radius) 0 0 var(--radius);
}

.input-suffix {
  right: 0;
  border-left: none;
  border-radius: 0 var(--radius) var(--radius) 0;
}

.pl-10 { padding-left: 2.5rem; }
.pr-10 { padding-right: 2.5rem; }

/* Icon and Image Upload */
.icon-selector { text-align: center; }
.icon-preview { margin-bottom: 1rem; }
.icon-display {
  width: 4rem;
  height: 4rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid hsl(var(--border));
  border-radius: var(--radius);
  background: hsl(var(--muted));
  color: hsl(var(--muted-foreground));
  margin: 0 auto;
}

.image-upload { width: 100%; }
.image-preview {
  width: 100%;
  height: 10rem;
  border: 2px dashed hsl(var(--border));
  border-radius: var(--radius);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
  overflow: hidden;
}

.image-preview:hover {
  border-color: hsl(var(--ring));
  background: hsl(var(--accent));
}

.image-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-preview.empty .upload-placeholder {
  text-align: center;
  color: hsl(var(--muted-foreground));
}

.upload-placeholder p {
  margin: 0.5rem 0 0 0;
  font-size: 0.875rem;
}

.remove-image-btn {
  position: absolute;
  top: 0.5rem;
  right: 0.5rem;
  background: hsl(var(--background));
  border: 1px solid hsl(var(--border));
  border-radius: var(--radius);
  padding: 0.25rem;
  color: hsl(var(--destructive));
  cursor: pointer;
  transition: all 0.2s;
}

.remove-image-btn:hover {
  background: hsl(var(--destructive));
  color: hsl(var(--destructive-foreground));
}

/* Options */
.options-container {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.empty-state {
  text-align: center;
  padding: 3rem 2rem;
  color: hsl(var(--muted-foreground));
}

.empty-state-icon { margin-bottom: 1rem; opacity: 0.5; }
.empty-state-title {
  margin: 0 0 0.5rem 0;
  font-size: 1.125rem;
  font-weight: 600;
  color: hsl(var(--foreground));
}

.empty-state-description {
  margin: 0 0 1.5rem 0;
  max-width: 400px;
  margin-left: auto;
  margin-right: auto;
}

.option-item {
  border: 1px solid hsl(var(--border));
  border-radius: var(--radius);
  background: hsl(var(--card));
  transition: all 0.2s;
}

.option-item:hover { border-color: hsl(var(--ring)); }
.option-item.expanded {
  border-color: hsl(var(--primary));
  box-shadow: 0 0 0 2px hsl(var(--ring) / 0.2);
}

.option-header {
  display: flex;
  align-items: center;
  padding: 1rem;
  cursor: pointer;
  user-select: none;
}

.drag-handle {
  margin-right: 0.75rem;
  color: hsl(var(--muted-foreground));
  cursor: grab;
}

.drag-handle:active { cursor: grabbing; }
.option-summary { flex: 1; }
.option-name {
  margin: 0 0 0.25rem 0;
  font-size: 1rem;
  font-weight: 600;
  color: hsl(var(--card-foreground));
}

.option-badges {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.option-actions { display: flex; gap: 0.25rem; }
.option-content {
  padding: 0 1rem 1rem 1rem;
  border-top: 1px solid hsl(var(--border));
  display: none;
}

.option-item.expanded .option-content { display: block; }

.btn-icon {
  padding: 0.5rem;
  background: none;
  border: none;
  color: hsl(var(--muted-foreground));
  cursor: pointer;
  border-radius: var(--radius);
  transition: all 0.2s;
}

.btn-icon:hover {
  background-color: hsl(var(--accent));
  color: hsl(var(--accent-foreground));
}

/* Option Types Grid */
.option-types-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 0.75rem;
}

.option-type-card {
  border: 1px solid hsl(var(--border));
  border-radius: var(--radius);
  padding: 0.75rem;
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
}

.option-type-card:hover {
  border-color: hsl(var(--ring));
  background: hsl(var(--accent));
}

.option-type-card.selected {
  border-color: hsl(var(--primary));
  background: hsl(var(--primary) / 0.1);
  box-shadow: 0 0 0 2px hsl(var(--ring) / 0.2);
}

.option-type-label {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
}

.option-type-icon { color: hsl(var(--muted-foreground)); }
.option-type-content { display: flex; flex-direction: column; }
.option-type-title {
  font-weight: 500;
  color: hsl(var(--foreground));
  font-size: 0.875rem;
}

.option-type-description {
  color: hsl(var(--muted-foreground));
  font-size: 0.75rem;
}

/* Price Types Grid */
.price-types-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 0.5rem;
}

.price-type-card {
  border: 1px solid hsl(var(--border));
  border-radius: var(--radius);
  padding: 0.75rem;
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
}

.price-type-card:hover {
  border-color: hsl(var(--ring));
  background: hsl(var(--accent));
}

.price-type-card.selected {
  border-color: hsl(var(--primary));
  background: hsl(var(--primary) / 0.1);
  box-shadow: 0 0 0 2px hsl(var(--ring) / 0.2);
}

.price-type-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}

.price-type-icon { color: hsl(var(--muted-foreground)); }
.price-type-content { display: flex; flex-direction: column; }
.price-type-title {
  font-weight: 500;
  color: hsl(var(--foreground));
  font-size: 0.875rem;
}

.price-type-description {
  color: hsl(var(--muted-foreground));
  font-size: 0.75rem;
}

/* Action Bar */
.action-bar {
  position: sticky;
  bottom: 0;
  background: hsl(var(--background));
  border-top: 1px solid hsl(var(--border));
  padding: 1rem 0;
  margin-top: 2rem;
}

.action-bar-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

/* Toggle Animation */
.toggle-option svg {
  transition: transform 0.2s ease;
}

.option-item.expanded .toggle-option svg {
  transform: rotate(180deg);
}

/* Responsive Design */
@media (max-width: 768px) {
  .page-header-content {
    flex-direction: column;
    align-items: stretch;
  }
  
  .page-header-actions {
    justify-content: flex-start;
  }
  
  .option-types-grid,
  .price-types-grid {
    grid-template-columns: 1fr;
  }
  
  .tabs-trigger {
    font-size: 0.8125rem;
    padding: 0.5rem 0.75rem;
  }
}
</style>

<div class="wrap mobooking-wrap">
    <!-- Page Header -->
    <div class="page-header">
        <div class="breadcrumb">
            <a href="<?php echo esc_url($breadcrumb_services); ?>" class="breadcrumb-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                    <rect width="20" height="14" x="2" y="6" rx="2"></rect>
                </svg>
                <?php esc_html_e('Services', 'mobooking'); ?>
            </a>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="breadcrumb-separator">
                <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
            <span class="breadcrumb-current"><?php echo esc_html($page_title); ?></span>
        </div>
        
        <div class="page-header-content">
            <div class="page-header-text">
                <h1 class="page-title"><?php echo esc_html($page_title); ?></h1>
                <p class="page-description">
                    <?php echo $edit_mode 
                        ? esc_html__('Modify service details and customize options to fit your business needs.', 'mobooking')
                        : esc_html__('Create a new service with pricing and customizable options for your customers.', 'mobooking'); ?>
                </p>
            </div>
            
            <?php if ($edit_mode): ?>
                <div class="page-header-actions">
                    <button type="button" id="duplicate-service-btn" class="btn btn-outline btn-sm">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect width="14" height="14" x="8" y="8" rx="2" ry="2"/>
                            <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>
                        </svg>
                        <?php esc_html_e('Duplicate', 'mobooking'); ?>
                    </button>
                    <button type="button" id="delete-service-btn" class="btn btn-destructive btn-sm">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 6h18"/>
                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                            <path d="m19 6-1 14H6L5 6"/>
                            <line x1="10" y1="11" x2="10" y2="17"/>
                            <line x1="14" y1="11" x2="14" y2="17"/>
                        </svg>
                        <?php esc_html_e('Delete', 'mobooking'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Error Message -->
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-destructive">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <path d="m15 9-6 6"/>
                <path d="m9 9 6 6"/>
            </svg>
            <span><?php echo esc_html($error_message); ?></span>
        </div>
    <?php endif; ?>

    <!-- Alert Container -->
    <div id="alert-container"></div>

    <!-- Main Form -->
    <form id="mobooking-service-form" class="service-form">
        <?php wp_nonce_field('mobooking_services_nonce', 'mobooking_services_nonce'); ?>
        
        <?php if ($edit_mode): ?>
            <input type="hidden" name="service_id" value="<?php echo esc_attr($service_id); ?>">
        <?php endif; ?>

        <!-- Tabs Navigation -->
        <div class="tabs">
            <div class="tabs-list" role="tablist">
                <button type="button" class="tabs-trigger active" data-tab="service-info" role="tab" aria-selected="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/>
                        <polyline points="14,2 14,8 20,8"/>
                    </svg>
                    <?php esc_html_e('Service Information', 'mobooking'); ?>
                </button>
                <button type="button" class="tabs-trigger" data-tab="service-options" role="tab" aria-selected="false">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                    <?php esc_html_e('Service Options', 'mobooking'); ?>
                    <?php if (!empty($service_options_data)): ?>
                        <span class="badge badge-secondary"><?php echo count($service_options_data); ?></span>
                    <?php endif; ?>
                </button>
            </div>

            <!-- Tab Content: Service Information -->
            <div class="tabs-content active" id="service-info">
                <div class="space-y-6">
                    <!-- Basic Information Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Basic Information</h3>
                            <p class="card-description">Essential details about your service</p>
                        </div>
                        <div class="card-content space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="md:col-span-2">
                                    <label class="form-label" for="service-name">
                                        Service Name <span class="text-destructive">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        id="service-name"
                                        name="name"
                                        class="form-input"
                                        placeholder="e.g., Deep House Cleaning"
                                        value="<?php echo esc_attr($service_name); ?>"
                                        required
                                    >
                                    <p class="form-description">This is what customers will see when booking</p>
                                </div>
                                <div>
                                    <label class="form-label">Status</label>
                                    <div class="flex items-center space-x-2 mt-2">
                                        <button type="button" class="switch <?php echo $service_status === 'active' ? 'switch-checked' : ''; ?>" data-switch="status">
                                            <span class="switch-thumb"></span>
                                        </button>
                                        <span class="text-sm font-medium">
                                            <?php echo $service_status === 'active' ? esc_html__('Active', 'mobooking') : esc_html__('Inactive', 'mobooking'); ?>
                                        </span>
                                        <input type="hidden" name="status" value="<?php echo esc_attr($service_status); ?>">
                                    </div>
                                    <p class="form-description">Only active services are bookable</p>
                                </div>
                            </div>

                            <div>
                                <label class="form-label" for="service-description">Description</label>
                                <textarea 
                                    id="service-description" 
                                    name="description" 
                                    class="form-textarea" 
                                    rows="4"
                                    placeholder="Describe your service in detail. What does it include? What makes it special?"
                                ><?php echo esc_textarea($service_description); ?></textarea>
                                <p class="form-description">Detailed description helps customers understand your service better</p>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing & Duration Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Pricing & Duration</h3>
                            <p class="card-description">Set the base price and estimated time</p>
                        </div>
                        <div class="card-content">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="form-label" for="service-price">
                                        Base Price <span class="text-destructive">*</span>
                                    </label>
                                    <div class="relative">
                                        <?php if ($currency_pos === 'before'): ?>
                                            <span class="input-prefix"><?php echo esc_html($currency_symbol); ?></span>
                                        <?php endif; ?>
                                        <input
                                            type="number"
                                            id="service-price"
                                            name="price"
                                            class="form-input <?php echo $currency_pos === 'before' ? 'pl-10' : ($currency_pos === 'after' ? 'pr-10' : ''); ?>"
                                            placeholder="0.00"
                                            value="<?php echo esc_attr($service_price); ?>"
                                            step="0.01"
                                            min="0"
                                            required
                                        >
                                        <?php if ($currency_pos === 'after'): ?>
                                            <span class="input-suffix"><?php echo esc_html($currency_symbol); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="form-description">Starting price for this service</p>
                                </div>

                                <div>
                                    <label class="form-label" for="service-duration">
                                        Duration (minutes) <span class="text-destructive">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        id="service-duration"
                                        name="duration"
                                        class="form-input"
                                        placeholder="e.g., 120"
                                        value="<?php echo esc_attr($service_duration); ?>"
                                        min="15"
                                        step="15"
                                        required
                                    >
                                    <p class="form-description">Estimated time to complete</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Visual Settings Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Visual Settings</h3>
                            <p class="card-description">Icon and image to represent your service</p>
                        </div>
                        <div class="card-content">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Service Icon -->
                                <div>
                                    <label class="form-label">Service Icon</label>
                                    <div class="icon-selector">
                                        <div class="icon-preview">
                                            <div id="current-icon" class="icon-display">
                                                <?php if (!empty($service_icon)): ?>
                                                    <?php echo wp_kses_post($service_icon); ?>
                                                <?php else: ?>
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                                                        <polyline points="3.27,6.96 12,12.01 20.73,6.96"/>
                                                        <line x1="12" y1="22.08" x2="12" y2="12"/>
                                                    </svg>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <button type="button" id="select-icon-btn" class="btn btn-outline btn-sm mt-2">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="3"/>
                                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                                            </svg>
                                            Choose Icon
                                        </button>
                                        <input type="hidden" id="service-icon" name="icon" value="<?php echo esc_attr($service_icon); ?>">
                                    </div>
                                </div>

                                <!-- Service Image -->
                                <div>
                                    <label class="form-label">Service Image</label>
                                    <div class="image-upload">
                                        <div id="image-preview" class="image-preview <?php echo empty($service_image_url) ? 'empty' : ''; ?>">
                                            <?php if (!empty($service_image_url)): ?>
                                                <img src="<?php echo esc_url($service_image_url); ?>" alt="Service Image">
                                                <button type="button" class="remove-image-btn">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M3 6h18"/>
                                                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                                        <path d="m19 6-1 14H6L5 6"/>
                                                    </svg>
                                                </button>
                                            <?php else: ?>
                                                <div class="upload-placeholder">
                                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                        <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                                                        <circle cx="9" cy="9" r="2"/>
                                                        <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                                                    </svg>
                                                    <p>Click to upload image</p>
                                                    <p class="text-xs text-muted-foreground">PNG, JPG up to 5MB</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <input type="file" id="service-image-upload" accept="image/*" style="display: none;">
                                        <input type="hidden" id="service-image-url" name="image_url" value="<?php echo esc_attr($service_image_url); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Content: Service Options -->
            <div class="tabs-content" id="service-options">
                <div class="card">
                    <div class="card-header">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="card-title">Service Options</h3>
                                <p class="card-description">Add customization options for your service</p>
                            </div>
                            <button type="button" id="add-option-btn" class="btn btn-primary">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M5 12h14"/>
                                    <path d="M12 5v14"/>
                                </svg>
                                Add Option
                            </button>
                        </div>
                    </div>
                    <div class="card-content">
                        <div id="options-container" class="options-container">
                            <?php if (empty($service_options_data)): ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <circle cx="12" cy="12" r="10"/>
                                            <path d="M8 14s1.5 2 4 2 4-2 4-2"/>
                                            <line x1="9" y1="9" x2="9.01" y2="9"/>
                                            <line x1="15" y1="9" x2="15.01" y2="9"/>
                                        </svg>
                                    </div>
                                    <h3 class="empty-state-title">No options added yet</h3>
                                    <p class="empty-state-description">
                                        Add customization options like room size, add-ons, or special requirements to make your service more flexible.
                                    </p>
                                    <button type="button" class="btn btn-primary add-first-option">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M5 12h14"/>
                                            <path d="M12 5v14"/>
                                        </svg>
                                        Add Your First Option
                                    </button>
                                </div>
                            <?php else: ?>
                                <?php foreach ($service_options_data as $index => $option): ?>
                                    <?php
                                    // Pass variables to the template
                                    set_query_var('option', $option);
                                    set_query_var('option_index', $index);
                                    set_query_var('option_types', $option_types);
                                    set_query_var('price_types', $price_types);
                                    get_template_part('templates/service-option-item');
                                    ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Bar -->
        <div class="action-bar">
            <div class="action-bar-content">
                <div class="flex items-center justify-between">
                    <a href="<?php echo esc_url($breadcrumb_services); ?>" class="btn btn-ghost">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m12 19-7-7 7-7"/>
                            <path d="M19 12H5"/>
                        </svg>
                        Cancel
                    </a>
                    
                    <div class="flex gap-2">
                        <button type="button" class="btn btn-outline" id="save-draft-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14,2 14,8 20,8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                                <polyline points="10,9 9,9 8,9"/>
                            </svg>
                            Save as Draft
                        </button>
                        <button type="submit" class="btn btn-primary" id="save-service-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m9 12 2 2 4-4"/>
                                <path d="M21 12c.552 0 1-.448 1-1V5a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v6c0 .552.448 1 1 1h18z"/>
                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7"/>
                            </svg>
                            <?php echo $edit_mode ? esc_html__('Update Service', 'mobooking') : esc_html__('Create Service', 'mobooking'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<template id="mobooking-option-template">
    <?php
    set_query_var('option', []);
    set_query_var('option_index', '__INDEX__');
    set_query_var('option_types', $option_types);
    set_query_var('price_types', $price_types);
    get_template_part('templates/service-option-item');
    ?>
</template>

<?php
// Initialize existing options count for JavaScript
if ($edit_mode && !empty($service_options_data)) {
    echo '<script>';
    echo 'document.addEventListener("DOMContentLoaded", function() {';
    echo 'document.querySelector(".tabs-trigger[data-tab=\'service-options\']").innerHTML += \'<span class="badge badge-secondary">' . count($service_options_data) . '</span>\';';
    echo '});';
    echo '</script>';
}
?>