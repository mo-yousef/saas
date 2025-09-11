<?php
/**
 * Dashboard Page: Add/Edit Service
 *
 * @package NORDBOOKING
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check user permissions.
if ( ! current_user_can( 'nordbooking_business_owner' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'NORDBOOKING' ) );
}

// Page setup.
$edit_mode  = false;
$service_id = 0;
if ( isset( $_GET['service_id'] ) && ! empty( $_GET['service_id'] ) ) {
	$edit_mode  = true;
	$service_id = intval( $_GET['service_id'] );
	$page_title = __( 'Edit Service', 'NORDBOOKING' );
} else {
	$page_title = __( 'Add New Service', 'NORDBOOKING' );
}

// Initialize variables.
$service_name         = '';
$service_description  = '';
$service_price        = '';
$service_duration     = '';
$service_icon         = '';
$service_image_url    = '';
$service_status       = 'active';
$disable_pet_question = 0;
$disable_frequency_option = 0;
$service_options_data = array();
$error_message        = '';

// Get settings.
$breadcrumb_services = admin_url( 'admin.php?page=NORDBOOKING-services' );
$user_id             = get_current_user_id();
$settings_manager    = new \NORDBOOKING\Classes\Settings();
$biz_settings        = $settings_manager->get_business_settings( $user_id );
$currency_symbol     = $biz_settings['biz_currency_symbol'];
$currency_pos        = $biz_settings['biz_currency_position'];

// Define option types.
$option_types = array(
	'toggle'   => array(
		'label'       => __( 'Option Toggle / Yes or No', 'NORDBOOKING' ),
		'description' => __( 'A single yes/no toggle switch.', 'NORDBOOKING' ),
		'icon'        => 'toggle-right',
	),
	'checkbox'   => array(
		'label'       => __( 'Multiple Choice', 'NORDBOOKING' ),
		'description' => __( 'A list of checkboxes where multiple can be selected.', 'NORDBOOKING' ),
		'icon'        => 'check-square',
	),
	'text'       => array(
		'label'       => __( 'Short Answer', 'NORDBOOKING' ),
		'description' => __( 'A single-line text input field.', 'NORDBOOKING' ),
		'icon'        => 'type',
	),
	'number'     => array(
		'label'       => __( 'Number Field', 'NORDBOOKING' ),
		'description' => __( 'A field for entering a number.', 'NORDBOOKING' ),
		'icon'        => 'hash',
	),
	'select'     => array(
		'label'       => __( 'Select from List', 'NORDBOOKING' ),
		'description' => __( 'A dropdown list to select a single option.', 'NORDBOOKING' ),
		'icon'        => 'chevron-down',
	),
	'radio'      => array(
		'label'       => __( 'Single Choice', 'NORDBOOKING' ),
		'description' => __( 'A list of radio buttons to select a single option.', 'NORDBOOKING' ),
		'icon'        => 'circle',
	),
	'textarea'   => array(
		'label'       => __( 'Long Answer / Additional Notes', 'NORDBOOKING' ),
		'description' => __( 'A multi-line text input field for longer answers.', 'NORDBOOKING' ),
		'icon'        => 'file-text',
	),
	'quantity'   => array(
		'label'       => __( 'Item Quantity / Number of Items', 'NORDBOOKING' ),
		'description' => __( 'A field for entering a quantity, often with steppers.', 'NORDBOOKING' ),
		'icon'        => 'plus-minus',
	),
	'sqm'        => array(
		'label'       => __( 'Area (m²)', 'NORDBOOKING' ),
		'description' => __( 'A field for entering an area in square meters (m²).', 'NORDBOOKING' ),
		'icon'        => 'square',
	),
	'kilometers' => array(
		'label'       => __( 'Distance (km)', 'NORDBOOKING' ),
		'description' => __( 'A field for entering a distance in kilometers (km).', 'NORDBOOKING' ),
		'icon'        => 'truck',
	),
);

// Define price types.
$price_types = array(
	''           => array(
		'label'       => __( 'No Price', 'NORDBOOKING' ),
		'description' => __( "This option doesn't affect the price.", 'NORDBOOKING' ),
		'icon'        => 'minus-circle',
	),
	'fixed'      => array(
		'label'       => __( 'Fixed', 'NORDBOOKING' ),
		'description' => __( 'Add a fixed amount to the total.', 'NORDBOOKING' ),
		'icon'        => 'dollar-sign',
	),
	'percentage' => array(
		'label'       => __( 'Percentage', 'NORDBOOKING' ),
		'description' => __( 'Increase total by a percentage.', 'NORDBOOKING' ),
		'icon'        => 'percent',
	),
	'multiply'   => array(
		'label'       => __( 'Multiply', 'NORDBOOKING' ),
		'description' => __( 'Multiply price by a value.', 'NORDBOOKING' ),
		'icon'        => 'x-circle',
	),
);

// Define price impact types for the entire option.
$price_impact_types = array(
	''           => array(
		'label'       => __( 'No Price', 'NORDBOOKING' ),
		'description' => __( "This option doesn't affect the price.", 'NORDBOOKING' ),
		'icon'        => 'minus-circle',
	),
	'fixed'      => array(
		'label'       => __( 'Fixed Amount', 'NORDBOOKING' ),
		'description' => __( 'Add/subtract a fixed amount', 'NORDBOOKING' ),
		'icon'        => 'dollar-sign',
	),
	'percentage' => array(
		'label'       => __( 'Percentage', 'NORDBOOKING' ),
		'description' => __( 'Increase/decrease by percentage', 'NORDBOOKING' ),
		'icon'        => 'percent',
	),
	'multiply'   => array(
		'label'       => __( 'Multiply', 'NORDBOOKING' ),
		'description' => __( 'Multiply price by option value', 'NORDBOOKING' ),
		'icon'        => 'x',
	),
);

// Fetch service data if editing.
$services_manager = new \NORDBOOKING\Classes\Services();
if ( $edit_mode && $service_id > 0 ) {
	if ( class_exists( '\NORDBOOKING\Classes\Services' ) ) {
		$service_data     = $services_manager->get_service( $service_id, $user_id );

		if ( $service_data && ! is_wp_error( $service_data ) ) {
			$service_name         = $service_data['name'];
			$service_description  = $service_data['description'];
			$service_price        = $service_data['price'];
			$service_duration     = $service_data['duration'];
			$service_icon         = $service_data['icon'];
			$service_image_url    = $service_data['image_url'];
			$service_status       = $service_data['status'];
			$disable_pet_question = $service_data['disable_pet_question'] ?? 0;
			$disable_frequency_option = $service_data['disable_frequency_option'] ?? 0;
			$service_options_data = isset( $service_data['options'] ) && is_array( $service_data['options'] ) ? $service_data['options'] : array();
		} else {
			$error_message = __( 'Service not found or you do not have permission to edit it.', 'NORDBOOKING' );
		}
	} else {
		$error_message = __( 'Error: Services manager class not found.', 'NORDBOOKING' );
	}
}
?>

<div class="NORDBOOKING-service-edit-page">
	<!-- Page Header -->
	<div class="nordbooking-page-header">
		<div class="nordbooking-page-header-heading">
			<span class="nordbooking-page-header-icon">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-briefcase"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
			</span>
			<div class="heading-wrapper">
				<h1 class="dashboard-title"><?php echo esc_html( $page_title ); ?></h1>
				<p class="dashboard-subtitle">
					<?php
					echo $edit_mode
						? esc_html__( 'Modify service details and customize options to fit your business needs.', 'NORDBOOKING' )
						: esc_html__( 'Create a new service with pricing and customizable options for your customers.', 'NORDBOOKING' );
					?>
				</p>
			</div>
		</div>
		<div class="nordbooking-page-header-actions">
			<a href="<?php echo esc_url( $breadcrumb_services ); ?>" class="btn btn-secondary btn-sm">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
				<?php esc_html_e( 'Back', 'NORDBOOKING' ); ?>
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
	<form id="NORDBOOKING-service-form" class="service-form" novalidate>
		<?php wp_nonce_field( 'nordbooking_services_nonce', 'nonce' ); ?>

		<?php if ( $edit_mode ) : ?>
			<input type="hidden" name="service_id" value="<?php echo esc_attr( $service_id ); ?>">
		<?php endif; ?>

		<div class="NORDBOOKING-edit-layout-grid">
			<div class="NORDBOOKING-main-content">
				<!-- Basic Information Card -->
				<div class="nordbooking-card">
					<div class="nordbooking-card-header">
						<h3 class="nordbooking-card-title">Basic Information</h3>
					</div>
					<div class="nordbooking-card-content space-y-4">
						<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
							<div class="md:col-span-3">
								<label class="nordbooking-filter-item label" for="service-name">
									<?php esc_html_e( 'Service Name', 'NORDBOOKING' ); ?> <span class="text-destructive">*</span>
								</label>
								<input
									type="text"
									id="service-name"
									name="name"
									class="regular-text"
									placeholder="<?php esc_attr_e( 'e.g., Deep House Cleaning', 'NORDBOOKING' ); ?>"
									value="<?php echo esc_attr( $service_name ); ?>"
									required
								>
							</div>
						</div>

						<div>
							<label class="nordbooking-filter-item label" for="service-description"><?php esc_html_e( 'Description', 'NORDBOOKING' ); ?></label>
							<textarea
								id="service-description"
								name="description"
								class="regular-text"
								rows="2"
								placeholder="<?php esc_attr_e( 'Describe your service in detail. What does it include? What makes it special?', 'NORDBOOKING' ); ?>"
							><?php echo esc_textarea( $service_description ); ?></textarea>
						</div>

						<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
							<div>
								<label class="nordbooking-filter-item label" for="service-price">
									<?php esc_html_e( 'Base Price', 'NORDBOOKING' ); ?> <span class="text-destructive">*</span>
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
								<label class="nordbooking-filter-item label" for="service-duration">
									<?php esc_html_e( 'Duration (minutes)', 'NORDBOOKING' ); ?> <span class="text-destructive">*</span>
								</label>
								<input
									type="number"
									id="service-duration"
									name="duration"
									class="regular-text"
									placeholder="<?php esc_attr_e( 'e.g., 120', 'NORDBOOKING' ); ?>"
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
				<div class="nordbooking-card">
					<div class="nordbooking-card-header">
						<h3 class="nordbooking-card-title"><?php esc_html_e( 'Service Options', 'NORDBOOKING' ); ?></h3>
						<button type="button" id="add-option-btn" class="btn btn-secondary btn-sm">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
							<?php esc_html_e( 'Add Option', 'NORDBOOKING' ); ?>
						</button>
					</div>
					<div class="nordbooking-card-content">
						<div id="options-container" class="options-container">
							<?php if ( empty( $service_options_data ) ) : ?>
								<div class="empty-state">
									<div class="empty-state-icon">
										<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
									</div>
									<h3 class="empty-state-title"><?php esc_html_e( 'No options added yet', 'NORDBOOKING' ); ?></h3>
									<p class="empty-state-description">
										<?php esc_html_e( 'Add customization options like room size, add-ons, or special requirements to make your service more flexible.', 'NORDBOOKING' ); ?>
									</p>
									<button type="button" class="btn btn-primary add-first-option">
										<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
										<?php esc_html_e( 'Add Your First Option', 'NORDBOOKING' ); ?>
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

			<div class="NORDBOOKING-sidebar">
				<!-- Actions Card -->
				<div class="nordbooking-card">
					<div class="nordbooking-card-header">
						<h3 class="nordbooking-card-title"><?php esc_html_e( 'Actions', 'NORDBOOKING' ); ?></h3>
					</div>
					<div class="nordbooking-card-content">
						<button type="submit" class="btn btn-primary w-full" id="save-service-btn">
							<?php if ( $edit_mode ) : ?>
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2"><path d="M20 6L9 17l-5-5"/></svg>
								<?php esc_html_e( 'Update Service', 'NORDBOOKING' ); ?>
							<?php else : ?>
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
								<?php esc_html_e( 'Create Service', 'NORDBOOKING' ); ?>
							<?php endif; ?>
						</button>


						<?php if ( $edit_mode ) : ?>
						<button type="button" id="delete-service-btn" class="btn btn-destructive btn-sm w-full">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><path d="m19 6-1 14H6L5 6"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
							<?php esc_html_e( 'Delete Service', 'NORDBOOKING' ); ?>
						</button>
						<hr class="my-4">
						<?php endif; ?>

						<?php if ( $edit_mode ) : ?>
						<div class="flex items-center justify-between">
						<?php else : ?>
						<div class="flex items-center justify-between mt-4">
						<?php endif; ?>
							<label class="nordbooking-filter-item label"><?php esc_html_e( 'Status', 'NORDBOOKING' ); ?></label>
							<div class="flex items-center space-x-2">
								<button type="button" class="switch <?php echo 'active' === $service_status ? 'switch-checked' : ''; ?>" data-switch="status">
									<span class="switch-thumb"></span>
								</button>
								<span class="text-sm font-medium">
									<?php echo 'active' === $service_status ? esc_html__( 'Active', 'NORDBOOKING' ) : esc_html__( 'Inactive', 'NORDBOOKING' ); ?>
								</span>
								<input type="hidden" name="status" value="<?php echo esc_attr( $service_status ); ?>">
							</div>
						</div>
						<hr class="my-4">
						<div class="flex items-center justify-between">
							<label class="nordbooking-filter-item label" for="disable_pet_question"><?php esc_html_e( 'Disable Pet Question', 'NORDBOOKING' ); ?></label>
							<div class="flex items-center space-x-2">
								<button type="button" class="switch <?php echo $disable_pet_question ? 'switch-checked' : ''; ?>" data-switch="disable_pet_question">
									<span class="switch-thumb"></span>
								</button>
								<input type="hidden" name="disable_pet_question" value="<?php echo esc_attr( $disable_pet_question ); ?>">
							</div>
						</div>
						<div class="flex items-center justify-between mt-4">
							<label class="nordbooking-filter-item label" for="disable_frequency_option"><?php esc_html_e( 'Disable Frequency Option', 'NORDBOOKING' ); ?></label>
							<div class="flex items-center space-x-2">
								<button type="button" class="switch <?php echo $disable_frequency_option ? 'switch-checked' : ''; ?>" data-switch="disable_frequency_option">
									<span class="switch-thumb"></span>
								</button>
								<input type="hidden" name="disable_frequency_option" value="<?php echo esc_attr( $disable_frequency_option ); ?>">
							</div>
						</div>
					</div>
				</div>
				<!-- Visual Settings Card -->
				<div class="nordbooking-card">
					<div class="nordbooking-card-header">
						<h3 class="nordbooking-card-title"><?php esc_html_e( 'Visuals', 'NORDBOOKING' ); ?></h3>
					</div>
					<div class="nordbooking-card-content space-y-6">
						<!-- Service Image -->
						<div>
							<label class="nordbooking-filter-item label"><?php esc_html_e( 'Service Image', 'NORDBOOKING' ); ?></label>
							<div class="NORDBOOKING-image-upload">
								<div id="image-preview" class="NORDBOOKING-image-preview <?php echo empty( $service_image_url ) ? 'empty' : ''; ?>">
									<?php if ( ! empty( $service_image_url ) ) : ?>
										<img src="<?php echo esc_url( $service_image_url ); ?>" alt="Service Image">
										<button type="button" class="remove-image-btn">
											<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><path d="m19 6-1 14H6L5 6"/></svg>
										</button>
									<?php else : ?>
										<div class="NORDBOOKING-image-placeholder">
											<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
											<p><?php esc_html_e( 'Click to upload image', 'NORDBOOKING' ); ?></p>
											<p class="text-xs text-muted-foreground"><?php esc_html_e( 'PNG, JPG up to 5MB', 'NORDBOOKING' ); ?></p>
										</div>
									<?php endif; ?>
								</div>
								<input type="file" id="service-image-upload" accept="image/*" style="display: none;">
								<input type="hidden" id="service-image-url" name="image_url" value="<?php echo esc_attr( $service_image_url ); ?>">
							</div>
						</div>
						<!-- Service Icon -->
						<div class="service-icon-section">
							<label class="nordbooking-filter-item label"><?php esc_html_e( 'Service Icon', 'NORDBOOKING' ); ?></label>
							<div class="NORDBOOKING-icon-selector">
								<div class="NORDBOOKING-icon-preview">
									<div id="current-icon" class="NORDBOOKING-icon-display">
										<?php if ( ! empty( $service_icon ) ) : ?>
											<?php echo $services_manager->get_service_icon_html( $service_icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										<?php else : ?>
											<svg xmlns="http://www.w3.org/2000/svg" viewBox="1 -1 50 50"><path d="M 11.09375 2 C 6.39375 2.8 2.8 6.39375 2 11.09375 L 6 11.6875 C 6.4 8.6875 8.6875 6.4 11.6875 6 L 11.09375 2 z M 17 2 L 17 6 L 23 6 L 23 2 L 17 2 z M 28 2 L 28 6 L 34 6 L 34 2 L 28 2 z M 38.90625 2 L 38.3125 6 C 41.2125 6.4 43.5 8.6875 44 11.6875 L 48 11.09375 C 47.2 6.39375 43.60625 2.7 38.90625 2 z M 2 16 L 2 22 L 6 22 L 6 16 L 2 16 z M 44 17 L 44 23 L 48 23 L 48 17 L 44 17 z M 2 27 L 2 33 L 6 33 L 6 27 L 2 27 z M 44 28 L 44 34 L 48 34 L 48 28 L 44 28 z M 6 38.3125 L 2 38.90625 C 2.8 43.60625 6.39375 47.3 11.09375 48 L 11.6875 44 C 8.7875 43.6 6.5 41.3125 6 38.3125 z M 44 38.3125 C 43.6 41.2125 41.3125 43.5 38.3125 44 L 38.90625 48 C 43.60625 47.2 47.2 43.60625 48 38.90625 L 44 38.3125 z M 16 44 L 16 48 L 22 48 L 22 44 L 16 44 z M 27 44 L 27 48 L 33 48 L 33 44 L 27 44 z" fill="currentColor" /></svg>

										<?php endif; ?>
									</div>
								</div>
								<button type="button" id="select-icon-btn" class="btn btn-outline btn-sm mt-2">
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
									<?php esc_html_e( 'Choose Icon', 'NORDBOOKING' ); ?>
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

<template id="NORDBOOKING-option-template">
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