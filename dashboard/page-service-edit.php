<?php
/**
 * Dashboard Page: Add/Edit Service
 *
 * @package MoBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check user permissions.
if ( ! current_user_can( 'mobooking_business_owner' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'mobooking' ) );
}

// Page setup.
$edit_mode  = false;
$service_id = 0;
if ( isset( $_GET['service_id'] ) && ! empty( $_GET['service_id'] ) ) {
	$edit_mode  = true;
	$service_id = intval( $_GET['service_id'] );
	$page_title = __( 'Edit Service', 'mobooking' );
} else {
	$page_title = __( 'Add New Service', 'mobooking' );
}

// Initialize variables.
$service_name         = '';
$service_description  = '';
$service_price        = '';
$service_duration     = '';
$service_icon         = '';
$service_image_url    = '';
$service_status       = 'active';
$service_options_data = array();
$error_message        = '';

// Get settings.
$breadcrumb_services = admin_url( 'admin.php?page=mobooking-services' );
$user_id             = get_current_user_id();
$settings_manager    = new \MoBooking\Classes\Settings();
$biz_settings        = $settings_manager->get_business_settings( $user_id );
$currency_symbol     = $biz_settings['biz_currency_symbol'];
$currency_pos        = $biz_settings['biz_currency_position'];

// Define option types.
$option_types = array(
	'checkbox'   => array(
		'label'       => __( 'Option Toggle / Yes or No', 'mobooking' ),
		'description' => __( 'Simple yes/no choice', 'mobooking' ),
		'icon'        => 'check-square',
	),
	'text'       => array(
		'label'       => __( 'Short Answer', 'mobooking' ),
		'description' => __( 'Short text field', 'mobooking' ),
		'icon'        => 'type',
	),
	'number'     => array(
		'label'       => __( 'Number Field', 'mobooking' ),
		'description' => __( 'Numeric input only', 'mobooking' ),
		'icon'        => 'hash',
	),
	'select'     => array(
		'label'       => __( 'Select from List', 'mobooking' ),
		'description' => __( 'Choose one from list', 'mobooking' ),
		'icon'        => 'chevron-down',
	),
	'radio'      => array(
		'label'       => __( 'Single Choice', 'mobooking' ),
		'description' => __( 'Single choice selection', 'mobooking' ),
		'icon'        => 'circle',
	),
	'textarea'   => array(
		'label'       => __( 'Long Answer / Additional Notes', 'mobooking' ),
		'description' => __( 'Multi-line text', 'mobooking' ),
		'icon'        => 'file-text',
	),
	'quantity'   => array(
		'label'       => __( 'Item Quantity / Number of Items', 'mobooking' ),
		'description' => __( 'Number with +/- buttons', 'mobooking' ),
		'icon'        => 'plus-minus',
	),
	'sqm'        => array(
		'label'       => __( 'Area (m²)', 'mobooking' ),
		'description' => __( 'Area calculation', 'mobooking' ),
		'icon'        => 'square',
	),
	'kilometers' => array(
		'label'       => __( 'Distance (km)', 'mobooking' ),
		'description' => __( 'Distance calculation', 'mobooking' ),
		'icon'        => 'truck',
	),
);

// Define price types.
$price_types = array(
	''           => array(
		'label'       => __( 'No Price', 'mobooking' ),
		'description' => __( "This option doesn't affect the price.", 'mobooking' ),
		'icon'        => 'minus-circle',
	),
	'fixed'      => array(
		'label'       => __( 'Fixed', 'mobooking' ),
		'description' => __( 'Add a fixed amount to the total.', 'mobooking' ),
		'icon'        => 'dollar-sign',
	),
	'percentage' => array(
		'label'       => __( 'Percentage', 'mobooking' ),
		'description' => __( 'Increase total by a percentage.', 'mobooking' ),
		'icon'        => 'percent',
	),
	'multiply'   => array(
		'label'       => __( 'Multiply', 'mobooking' ),
		'description' => __( 'Multiply price by a value.', 'mobooking' ),
		'icon'        => 'x-circle',
	),
);

// Define price impact types for the entire option.
$price_impact_types = array(
	''           => array(
		'label'       => __( 'No Price', 'mobooking' ),
		'description' => __( "This option doesn't affect the price.", 'mobooking' ),
		'icon'        => 'minus-circle',
	),
	'fixed'      => array(
		'label'       => __( 'Fixed Amount', 'mobooking' ),
		'description' => __( 'Add/subtract a fixed amount', 'mobooking' ),
		'icon'        => 'dollar-sign',
	),
	'percentage' => array(
		'label'       => __( 'Percentage', 'mobooking' ),
		'description' => __( 'Increase/decrease by percentage', 'mobooking' ),
		'icon'        => 'percent',
	),
	'multiply'   => array(
		'label'       => __( 'Multiply', 'mobooking' ),
		'description' => __( 'Multiply price by option value', 'mobooking' ),
		'icon'        => 'x',
	),
);

// Fetch service data if editing.
if ( $edit_mode && $service_id > 0 ) {
	if ( class_exists( '\MoBooking\Classes\Services' ) ) {
		$services_manager = new \MoBooking\Classes\Services();
		$service_data     = $services_manager->get_service( $service_id, $user_id );

		if ( $service_data && ! is_wp_error( $service_data ) ) {
			$service_name         = $service_data['name'];
			$service_description  = $service_data['description'];
			$service_price        = $service_data['price'];
			$service_duration     = $service_data['duration'];
			$service_icon         = $service_data['icon'];
			$service_image_url    = $service_data['image_url'];
			$service_status       = $service_data['status'];
			$service_options_data = isset( $service_data['options'] ) && is_array( $service_data['options'] ) ? $service_data['options'] : array();
		} else {
			$error_message = __( 'Service not found or you do not have permission to edit it.', 'mobooking' );
		}
	} else {
		$error_message = __( 'Error: Services manager class not found.', 'mobooking' );
	}
}
?>

<div class="mobooking-service-edit-page">
	<!-- Page Header -->
	<div class="mobooking-page-header">
		<div class="mobooking-page-header-heading">
			<span class="mobooking-page-header-icon">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-briefcase"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
			</span>
			<div class="heading-wrapper">
				<h1 class="dashboard-title"><?php echo esc_html( $page_title ); ?></h1>
				<p class="dashboard-subtitle">
					<?php
					echo $edit_mode
						? esc_html__( 'Modify service details and customize options to fit your business needs.', 'mobooking' )
						: esc_html__( 'Create a new service with pricing and customizable options for your customers.', 'mobooking' );
					?>
				</p>
			</div>
		</div>
		<div class="mobooking-page-header-actions">
			<a href="<?php echo esc_url( $breadcrumb_services ); ?>" class="btn btn-secondary btn-sm">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
				<?php esc_html_e( 'Back', 'mobooking' ); ?>
			</a>
		</div>
	</div>

	<!-- Error Message -->
	<?php if ( ! empty( $error_message ) ) : ?>
		<div class="alert alert-destructive">
			<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
			<span><?php echo esc_html( $error_message ); ?></span>
		</div>
	<?php endif; ?>

	<!-- Alert Container -->
	<div id="alert-container"></div>

	<!-- Main Form -->
	<form id="mobooking-service-form" class="service-form">
		<?php wp_nonce_field( 'mobooking_services_nonce', 'nonce' ); ?>

		<?php if ( $edit_mode ) : ?>
			<input type="hidden" name="service_id" value="<?php echo esc_attr( $service_id ); ?>">
		<?php endif; ?>

		<div class="mobooking-edit-layout-grid">
			<div class="mobooking-main-content">
				<!-- Basic Information Card -->
				<div class="mobooking-card">
					<div class="mobooking-card-header">
						<h3 class="mobooking-card-title">Basic Information</h3>
					</div>
					<div class="mobooking-card-content space-y-4">
						<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
							<div class="md:col-span-3">
								<label class="mobooking-filter-item label" for="service-name">
									<?php esc_html_e( 'Service Name', 'mobooking' ); ?> <span class="text-destructive">*</span>
								</label>
								<input
									type="text"
									id="service-name"
									name="name"
									class="regular-text"
									placeholder="<?php esc_attr_e( 'e.g., Deep House Cleaning', 'mobooking' ); ?>"
									value="<?php echo esc_attr( $service_name ); ?>"
									required
								>
							</div>
						</div>

						<div>
							<label class="mobooking-filter-item label" for="service-description"><?php esc_html_e( 'Description', 'mobooking' ); ?></label>
							<textarea
								id="service-description"
								name="description"
								class="regular-text"
								rows="2"
								placeholder="<?php esc_attr_e( 'Describe your service in detail. What does it include? What makes it special?', 'mobooking' ); ?>"
							><?php echo esc_textarea( $service_description ); ?></textarea>
						</div>

						<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
							<div>
								<label class="mobooking-filter-item label" for="service-price">
									<?php esc_html_e( 'Base Price', 'mobooking' ); ?> <span class="text-destructive">*</span>
								</label>
								<div class="relative">
									<?php if ( 'before' === $currency_pos ) : ?>
										<span class="input-prefix"><?php echo esc_html( $currency_symbol ); ?></span>
									<?php endif; ?>
									<input
										type="number"
										id="service-price"
										name="price"
										class="regular-text <?php echo 'before' === $currency_pos ? 'pl-10' : ( 'after' === $currency_pos ? 'pr-10' : '' ); ?>"
										placeholder="0.00"
										value="<?php echo esc_attr( $service_price ); ?>"
										step="0.01"
										min="0"
										required
									>
									<?php if ( 'after' === $currency_pos ) : ?>
										<span class="input-suffix"><?php echo esc_html( $currency_symbol ); ?></span>
									<?php endif; ?>
								</div>
							</div>

							<div>
								<label class="mobooking-filter-item label" for="service-duration">
									<?php esc_html_e( 'Duration (minutes)', 'mobooking' ); ?> <span class="text-destructive">*</span>
								</label>
								<input
									type="number"
									id="service-duration"
									name="duration"
									class="regular-text"
									placeholder="<?php esc_attr_e( 'e.g., 120', 'mobooking' ); ?>"
									value="<?php echo esc_attr( $service_duration ); ?>"
									min="30"
									step="15"
									required
								>
							</div>
						</div>
					</div>
				</div>

				<!-- Service Options Card -->
				<div class="mobooking-card">
					<div class="mobooking-card-header">
						<h3 class="mobooking-card-title"><?php esc_html_e( 'Service Options', 'mobooking' ); ?></h3>
						<button type="button" id="add-option-btn" class="btn btn-secondary btn-sm">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
							<?php esc_html_e( 'Add Option', 'mobooking' ); ?>
						</button>
					</div>
					<div class="mobooking-card-content">
						<div id="options-container" class="options-container">
							<?php if ( empty( $service_options_data ) ) : ?>
								<div class="empty-state">
									<div class="empty-state-icon">
										<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
									</div>
									<h3 class="empty-state-title"><?php esc_html_e( 'No options added yet', 'mobooking' ); ?></h3>
									<p class="empty-state-description">
										<?php esc_html_e( 'Add customization options like room size, add-ons, or special requirements to make your service more flexible.', 'mobooking' ); ?>
									</p>
									<button type="button" class="btn btn-primary add-first-option">
										<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
										<?php esc_html_e( 'Add Your First Option', 'mobooking' ); ?>
									</button>
								</div>
							<?php else : ?>
								<?php foreach ( $service_options_data as $index => $option ) : ?>
									<?php
									set_query_var( 'option', $option );
									set_query_var( 'option_index', $index );
									set_query_var( 'option_types', $option_types );
									set_query_var( 'price_types', $price_types );
									set_query_var( 'price_impact_types', $price_impact_types );
									get_template_part( 'templates/service-option-item' );
									?>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>

			<div class="mobooking-sidebar">
				<!-- Actions Card -->
				<div class="mobooking-card">
					<div class="mobooking-card-header">
						<h3 class="mobooking-card-title"><?php esc_html_e( 'Actions', 'mobooking' ); ?></h3>
					</div>
					<div class="mobooking-card-content">
						<button type="submit" class="btn btn-primary w-full" id="save-service-btn">
							<?php if ( $edit_mode ) : ?>
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2"><path d="M20 6L9 17l-5-5"/></svg>
								<?php esc_html_e( 'Update Service', 'mobooking' ); ?>
							<?php else : ?>
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
								<?php esc_html_e( 'Create Service', 'mobooking' ); ?>
							<?php endif; ?>
						</button>
						<div class="flex items-center justify-between mt-4">
							<label class="mobooking-filter-item label"><?php esc_html_e( 'Status', 'mobooking' ); ?></label>
							<div class="flex items-center space-x-2">
								<button type="button" class="switch <?php echo 'active' === $service_status ? 'switch-checked' : ''; ?>" data-switch="status">
									<span class="switch-thumb"></span>
								</button>
								<span class="text-sm font-medium">
									<?php echo 'active' === $service_status ? esc_html__( 'Active', 'mobooking' ) : esc_html__( 'Inactive', 'mobooking' ); ?>
								</span>
								<input type="hidden" name="status" value="<?php echo esc_attr( $service_status ); ?>">
							</div>
						</div>
						<?php if ( $edit_mode ) : ?>
						<hr class="my-4">
						<button type="button" id="delete-service-btn" class="btn btn-destructive btn-sm w-full">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><path d="m19 6-1 14H6L5 6"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
							<?php esc_html_e( 'Delete Service', 'mobooking' ); ?>
						</button>
						<?php endif; ?>
					</div>
				</div>
				<!-- Visual Settings Card -->
				<div class="mobooking-card">
					<div class="mobooking-card-header">
						<h3 class="mobooking-card-title"><?php esc_html_e( 'Visuals', 'mobooking' ); ?></h3>
					</div>
					<div class="mobooking-card-content space-y-6">
						<!-- Service Image -->
						<div>
							<label class="mobooking-filter-item label"><?php esc_html_e( 'Service Image', 'mobooking' ); ?></label>
							<div class="mobooking-image-upload">
								<div id="image-preview" class="mobooking-image-preview <?php echo empty( $service_image_url ) ? 'empty' : ''; ?>">
									<?php if ( ! empty( $service_image_url ) ) : ?>
										<img src="<?php echo esc_url( $service_image_url ); ?>" alt="Service Image">
										<button type="button" class="remove-image-btn">
											<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><path d="m19 6-1 14H6L5 6"/></svg>
										</button>
									<?php else : ?>
										<div class="mobooking-image-placeholder">
											<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
											<p><?php esc_html_e( 'Click to upload image', 'mobooking' ); ?></p>
											<p class="text-xs text-muted-foreground"><?php esc_html_e( 'PNG, JPG up to 5MB', 'mobooking' ); ?></p>
										</div>
									<?php endif; ?>
								</div>
								<input type="file" id="service-image-upload" accept="image/*" style="display: none;">
								<input type="hidden" id="service-image-url" name="image_url" value="<?php echo esc_attr( $service_image_url ); ?>">
							</div>
						</div>
						<!-- Service Icon -->
						<div class="service-icon-section">
							<label class="mobooking-filter-item label"><?php esc_html_e( 'Service Icon', 'mobooking' ); ?></label>
							<div class="mobooking-icon-selector">
								<div class="mobooking-icon-preview">
									<div id="current-icon" class="mobooking-icon-display">
										<?php if ( ! empty( $service_icon ) ) : ?>
											<?php echo wp_kses_post( $service_icon ); ?>
										<?php else : ?>
											<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27,6.96 12,12.01 20.73,6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
										<?php endif; ?>
									</div>
								</div>
								<button type="button" id="select-icon-btn" class="btn btn-outline btn-sm mt-2">
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
									<?php esc_html_e( 'Choose Icon', 'mobooking' ); ?>
								</button>
								<input type="hidden" id="service-icon" name="icon" value="<?php echo esc_attr( $service_icon ); ?>">
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

	</form>
</div>

<template id="mobooking-option-template">
	<?php
	set_query_var( 'option', array() );
	set_query_var( 'option_index', '__INDEX__' );
	set_query_var( 'option_types', $option_types );
	set_query_var( 'price_types', $price_types );
	set_query_var( 'price_impact_types', $price_impact_types );
	get_template_part( 'templates/service-option-item' );
	?>
</template>

<?php
// Initialize existing options count for JavaScript
if ( $edit_mode && ! empty( $service_options_data ) ) {
	// This script is no longer needed as the tabbed interface has been removed.
}
?>