<?php
/**
 * Template Name: Modern Public Booking Form (Shadcn UI Style)
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Conditionally load header/footer for public vs embed view
if (get_query_var('mobooking_page_type') !== 'embed') {
    get_header();
    echo '<body class="mobooking-form-active">'; // Add class to body
} else {
    echo '<body class="mobooking-form-active mobooking-form-embed-active">';
}
?>

<style>
/* Shadcn UI Inspired Design System */
:root {
    --background: 0 0% 100%;
    --foreground: 222.2 84% 4.9%;
    --card: 0 0% 100%;
    --card-foreground: 222.2 84% 4.9%;
    --popover: 0 0% 100%;
    --popover-foreground: 222.2 84% 4.9%;
    --primary: 222.2 47.4% 11.2%;
    --primary-foreground: 210 40% 98%;
    --secondary: 210 40% 96.1%;
    --secondary-foreground: 222.2 84% 4.9%;
    --muted: 210 40% 96.1%;
    --muted-foreground: 215.4 16.3% 46.9%;
    --accent: 210 40% 96.1%;
    --accent-foreground: 222.2 84% 4.9%;
    --destructive: 0 84.2% 60.2%;
    --destructive-foreground: 210 40% 98%;
    --border: 214.3 31.8% 91.4%;
    --input: 214.3 31.8% 91.4%;
    --ring: 222.2 84% 4.9%;
    --radius: 0.5rem;
}

@media (prefers-color-scheme: dark) {
    :root {
        --background: 222.2 84% 4.9%;
        --foreground: 210 40% 98%;
        --card: 222.2 84% 4.9%;
        --card-foreground: 210 40% 98%;
        --popover: 222.2 84% 4.9%;
        --popover-foreground: 210 40% 98%;
        --primary: 210 40% 98%;
        --primary-foreground: 222.2 47.4% 11.2%;
        --secondary: 217.2 32.6% 17.5%;
        --secondary-foreground: 210 40% 98%;
        --muted: 217.2 32.6% 17.5%;
        --muted-foreground: 215 20.2% 65.1%;
        --accent: 217.2 32.6% 17.5%;
        --accent-foreground: 210 40% 98%;
        --border: 217.2 32.6% 17.5%;
        --input: 217.2 32.6% 17.5%;
        --ring: 212.7 26.8% 83.9%;
    }
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body.mobooking-form-active {
    background-color: hsl(var(--background));
    color: hsl(var(--foreground));
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    font-feature-settings: "rlig" 1, "calt" 1;
    line-height: 1.5;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

.mobooking-bf-wrapper {
    min-height: 100vh;
    background: hsl(var(--background));
    padding: 1rem;
}

.mobooking-bf__layout-container {
    max-width: 1200px;
    margin: 0 auto;
    display: block;
}

/* Main Content */
.mobooking-bf__main-content {
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: calc(var(--radius) + 2px);
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
}

/* Steps with Sidebar Layout */
.mobooking-bf__step-with-sidebar {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 2rem;
    align-items: start;
}

@media (max-width: 1024px) {
    .mobooking-bf__step-with-sidebar {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .mobooking-bf__sidebar {
        order: -1;
    }
}

.mobooking-bf-main-title {
    background: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
    padding: 2rem;
    margin: 0;
    font-size: 1.875rem;
    font-weight: 600;
    text-align: center;
    border-radius: calc(var(--radius) + 2px) calc(var(--radius) + 2px) 0 0;
}

/* Progress Steps */
.mobooking-bf__progress-container {
    padding: 1.5rem 2rem;
    border-bottom: 1px solid hsl(var(--border));
    background: hsl(var(--muted) / 0.5);
}

.mobooking-bf__progress-steps {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
}

.mobooking-bf__progress-steps::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 2px;
    background: hsl(var(--border));
    z-index: 1;
}

.mobooking-bf__progress-steps::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    height: 2px;
    background: hsl(var(--primary));
    z-index: 2;
    transition: width 0.3s ease;
    width: var(--progress-width, 0%);
}

.mobooking-bf__progress-step {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    background: hsl(var(--background));
    border: 2px solid hsl(var(--border));
    color: hsl(var(--muted-foreground));
    font-size: 0.875rem;
    font-weight: 500;
    position: relative;
    z-index: 3;
    transition: all 0.3s ease;
}

.mobooking-bf__progress-step.active {
    background: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
    border-color: hsl(var(--primary));
}

.mobooking-bf__progress-step.completed {
    background: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
    border-color: hsl(var(--primary));
}

.mobooking-bf__progress-step.completed::after {
    content: '✓';
    font-size: 0.75rem;
}

/* Steps */
.mobooking-bf__step {
    padding: 2rem;
    display: none;
    opacity: 0;
    transform: translateY(10px);
    transition: all 0.3s ease;
}

.mobooking-bf__step.active {
    display: block;
    opacity: 1;
    transform: translateY(0);
}

.mobooking-bf__step-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: hsl(var(--foreground));
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Form Elements */
.mobooking-bf__form-group {
    margin-bottom: 1.5rem;
}

.mobooking-bf__label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: hsl(var(--foreground));
    margin-bottom: 0.5rem;
}

.mobooking-bf__required-indicator {
    color: hsl(var(--destructive));
}

.mobooking-bf__input,
.mobooking-bf__textarea,
.mobooking-bf__select {
    display: flex;
    width: 100%;
    border-radius: calc(var(--radius) - 2px);
    border: 1px solid hsl(var(--input));
    background-color: hsl(var(--background));
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    transition: border-color 0.2s;
    color: hsl(var(--foreground));
}

.mobooking-bf__input:focus,
.mobooking-bf__textarea:focus,
.mobooking-bf__select:focus {
    outline: none;
    border-color: hsl(var(--ring));
    box-shadow: 0 0 0 2px hsl(var(--ring) / 0.2);
}

.mobooking-bf__input {
    height: 2.5rem;
}

.mobooking-bf__textarea {
    min-height: 6rem;
    resize: vertical;
}

.mobooking-bf__input::placeholder,
.mobooking-bf__textarea::placeholder {
    color: hsl(var(--muted-foreground));
}

/* Buttons */
.mobooking-bf__button-group {
    display: flex;
    gap: 0.75rem;
    justify-content: space-between;
    margin-top: 2rem;
}

.mobooking-bf__button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    white-space: nowrap;
    border-radius: calc(var(--radius) - 2px);
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s;
    cursor: pointer;
    padding: 0.5rem 1rem;
    height: 2.5rem;
    border: 1px solid transparent;
    text-decoration: none;
    user-select: none;
}

.mobooking-bf__button:focus-visible {
    outline: 2px solid hsl(var(--ring));
    outline-offset: 2px;
}

.mobooking-bf__button:disabled {
    pointer-events: none;
    opacity: 0.5;
}

.mobooking-bf__button--primary {
    background: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
}

.mobooking-bf__button--primary:hover {
    background: hsl(var(--primary) / 0.9);
}

.mobooking-bf__button--secondary {
    background: hsl(var(--secondary));
    color: hsl(var(--secondary-foreground));
}

.mobooking-bf__button--secondary:hover {
    background: hsl(var(--secondary) / 0.8);
}

.mobooking-bf__button--outline {
    border: 1px solid hsl(var(--input));
    background: hsl(var(--background));
    color: hsl(var(--foreground));
}

.mobooking-bf__button--outline:hover {
    background: hsl(var(--accent));
    color: hsl(var(--accent-foreground));
}

/* Service Cards */
.mobooking-bf__service-card {
    border: 1px solid hsl(var(--border));
    border-radius: calc(var(--radius) - 2px);
    padding: 1.5rem;
    margin-bottom: 1rem;
    background: hsl(var(--card));
    transition: all 0.2s;
    cursor: pointer;
    position: relative;
}

.mobooking-bf__service-card:hover {
    border-color: hsl(var(--ring));
    box-shadow: 0 2px 8px -2px rgb(0 0 0 / 0.1);
}

.mobooking-bf__service-card.selected {
    border-color: hsl(var(--primary));
    background: hsl(var(--primary) / 0.05);
}

.mobooking-bf__service-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.mobooking-bf__service-icon {
    width: 3rem;
    height: 3rem;
    border-radius: calc(var(--radius) - 2px);
    background: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

.mobooking-bf__service-info {
    flex: 1;
}

.mobooking-bf__service-name {
    font-size: 1.125rem;
    font-weight: 600;
    color: hsl(var(--foreground));
    margin-bottom: 0.25rem;
}

.mobooking-bf__service-price {
    font-size: 1.25rem;
    font-weight: 700;
    color: hsl(var(--primary));
    margin: 0;
}

.mobooking-bf__service-description {
    color: hsl(var(--muted-foreground));
    font-size: 0.875rem;
    margin-bottom: 1rem;
    line-height: 1.4;
}

.mobooking-bf__service-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
}

.mobooking-bf__service-duration,
.mobooking-bf__service-category {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

/* Radio Button Styling */
.mobooking-bf__radio-wrapper {
}

.mobooking-bf__radio-input {
    appearance: none;
    width: 1.25rem;
    height: 1.25rem;
    border: 1px solid hsl(var(--input));
    border-radius: 50%;
    background: hsl(var(--background));
    position: relative;
    cursor: pointer;
    transition: all 0.2s;
}

.mobooking-bf__radio-input:checked {
    border-color: hsl(var(--primary));
    background: hsl(var(--primary));
}

.mobooking-bf__radio-input:checked::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 0.375rem;
    height: 0.375rem;
    border-radius: 50%;
    background: hsl(var(--primary-foreground));
}

/* Sidebar */
.mobooking-bf__sidebar {
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: calc(var(--radius) + 2px);
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    padding: 1.5rem;
    height: fit-content;
    position: sticky;
    top: 1rem;
}

.mobooking-bf__sidebar-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: hsl(var(--foreground));
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.mobooking-bf__summary-empty {
    text-align: center;
    color: hsl(var(--muted-foreground));
    padding: 2rem 0;
}

.mobooking-bf__summary-empty i {
    font-size: 2rem;
    margin-bottom: 1rem;
    display: block;
    opacity: 0.5;
}

.mobooking-bf__summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid hsl(var(--border));
}

.mobooking-bf__summary-item:last-child {
    border-bottom: none;
}

.mobooking-bf__summary-total {
    font-weight: 600;
    color: hsl(var(--primary));
    font-size: 1.125rem;
    margin-top: 0.5rem;
    padding-top: 0.75rem;
    border-top: 1px solid hsl(var(--border));
}

/* Loading State */
.mobooking-bf__loading {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: hsl(var(--muted-foreground));
}

.mobooking-bf__loading::before {
    content: '';
    width: 1rem;
    height: 1rem;
    border: 2px solid hsl(var(--border));
    border-top: 2px solid hsl(var(--primary));
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Feedback Messages */
.mobooking-bf__feedback {
    padding: 0.75rem;
    border-radius: calc(var(--radius) - 2px);
    margin-bottom: 1rem;
    font-size: 0.875rem;
    display: none;
}

.mobooking-bf__feedback.success {
    background: hsl(142.1 70.6% 45.3% / 0.1);
    color: hsl(142.1 70.6% 45.3%);
    border: 1px solid hsl(142.1 70.6% 45.3% / 0.2);
}

.mobooking-bf__feedback.error {
    background: hsl(var(--destructive) / 0.1);
    color: hsl(var(--destructive));
    border: 1px solid hsl(var(--destructive) / 0.2);
}

.mobooking-bf__feedback.info {
    background: hsl(var(--primary) / 0.1);
    color: hsl(var(--primary));
    border: 1px solid hsl(var(--primary) / 0.2);
}

/* Confirmation */
.mobooking-bf__confirmation {
    text-align: center;
    padding: 2rem;
}

.mobooking-bf__confirmation-icon {
    font-size: 4rem;
    color: hsl(142.1 70.6% 45.3%);
    margin-bottom: 1rem;
    animation: bounceIn 0.6s ease-out;
}

@keyframes bounceIn {
    0% { transform: scale(0.3); opacity: 0; }
    50% { transform: scale(1.05); }
    70% { transform: scale(0.9); }
    100% { transform: scale(1); opacity: 1; }
}

.mobooking-bf__confirmation h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: hsl(var(--foreground));
    margin-bottom: 1rem;
}

.mobooking-bf__confirmation p {
    color: hsl(var(--muted-foreground));
    margin-bottom: 0.5rem;
}

/* Service Options */
.mobooking-bf__option-group {
    border: 1px solid hsl(var(--border));
    border-radius: calc(var(--radius) - 2px);
    padding: 1rem;
    margin-bottom: 1rem;
    background: hsl(var(--card));
}

.mobooking-bf__option-description {
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
    margin-top: 0.25rem;
}

.mobooking-bf__option-price {
    color: hsl(var(--primary));
    font-weight: 500;
    margin-left: 0.5rem;
}

/* Checkbox Styling */
.mobooking-bf__checkbox-wrapper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.mobooking-bf__checkbox {
    appearance: none;
    width: 1rem;
    height: 1rem;
    border: 1px solid hsl(var(--input));
    border-radius: 2px;
    background: hsl(var(--background));
    position: relative;
    cursor: pointer;
    transition: all 0.2s;
}

.mobooking-bf__checkbox:checked {
    background: hsl(var(--primary));
    border-color: hsl(var(--primary));
}

.mobooking-bf__checkbox:checked::after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: hsl(var(--primary-foreground));
    font-size: 0.75rem;
    font-weight: 700;
}

/* Responsive */
@media (max-width: 768px) {
    .mobooking-bf-wrapper {
        padding: 0.5rem;
    }
    
    .mobooking-bf__step {
        padding: 1rem;
    }
    
    .mobooking-bf-main-title {
        padding: 1rem;
        font-size: 1.5rem;
    }
    
    .mobooking-bf__progress-container {
        padding: 1rem;
    }
    
    .mobooking-bf__button-group {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .mobooking-bf__sidebar {
        order: -1;
    }
}

/* Hidden utility */
.mobooking-bf__hidden {
    display: none !important;
}
</style>

<div id="mobooking-public-booking-form-wrapper" class="mobooking-bf-wrapper <?php if (get_query_var('mobooking_page_type') === 'embed') { echo 'mobooking-bf-wrapper--embed'; } ?>">
    <div class="mobooking-bf__layout-container">
        <!-- Main content area for steps -->
        <div class="mobooking-bf__main-content">
            <?php if (get_query_var('mobooking_page_type') !== 'embed'): ?>
            <h1 class="mobooking-bf-main-title">
                <i class="fas fa-calendar-check"></i>
                <?php esc_html_e('Book Our Services', 'mobooking'); ?>
            </h1>
            <?php endif; ?>

            <!-- Progress Steps -->
            <div class="mobooking-bf__progress-container">
                <div class="mobooking-bf__progress-steps" id="mobooking-bf-progress-steps">
                    <div class="mobooking-bf__progress-step active" data-step="1">1</div>
                    <div class="mobooking-bf__progress-step" data-step="2">2</div>
                    <div class="mobooking-bf__progress-step" data-step="3">3</div>
                    <div class="mobooking-bf__progress-step" data-step="4">4</div>
                    <div class="mobooking-bf__progress-step" data-step="5">5</div>
                </div>
            </div>

            <!-- Step 1: Location -->
            <div id="mobooking-bf-step-1-location" class="mobooking-bf__step active">
                <h2 class="mobooking-bf__step-title">
                    <i class="fas fa-map-marker-alt"></i>
                    <?php esc_html_e('Check Service Availability', 'mobooking'); ?>
                </h2>
                <form id="mobooking-bf-location-form">
                    <div class="mobooking-bf__form-group">
                        <label for="mobooking-bf-country-code" class="mobooking-bf__label">
                            <?php esc_html_e('Country Code', 'mobooking'); ?>
                        </label>
                        <input type="text" id="mobooking-bf-country-code" name="country_code" value="US" required class="mobooking-bf__input" placeholder="e.g., US, CA, GB">
                    </div>
                    <div class="mobooking-bf__form-group">
                        <label for="mobooking-bf-zip-code" class="mobooking-bf__label">
                            <?php esc_html_e('ZIP / Postal Code', 'mobooking'); ?>
                            <span class="mobooking-bf__required-indicator">*</span>
                        </label>
                        <input type="text" id="mobooking-bf-zip-code" name="zip_code" required class="mobooking-bf__input" placeholder="Enter your ZIP code">
                    </div>
                    <input type="hidden" id="mobooking-bf-tenant-id" name="tenant_id" value="">
                    <div id="mobooking-bf-feedback" class="mobooking-bf__feedback"></div>
                    <div class="mobooking-bf__button-group">
                        <div></div>
                        <button type="submit" id="mobooking-bf-location-submit-btn" class="mobooking-bf__button mobooking-bf__button--primary">
                            <i class="fas fa-search"></i>
                            <?php esc_html_e('Check Availability', 'mobooking'); ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Step 2: Services -->
            <div id="mobooking-bf-step-2-services" class="mobooking-bf__step">
                <h2 class="mobooking-bf__step-title">
                    <i class="fas fa-list-ul"></i>
                    <?php esc_html_e('Select Services', 'mobooking'); ?>
                </h2>
                <div id="mobooking-bf-services-list" class="mobooking-bf-services-list">
                    <div class="mobooking-bf__loading">
                        <?php esc_html_e('Loading available services...', 'mobooking'); ?>
                    </div>
                </div>
                <div id="mobooking-bf-step-2-feedback" class="mobooking-bf__feedback"></div>
                <div class="mobooking-bf__button-group">
                    <button type="button" id="mobooking-bf-services-back-btn" class="mobooking-bf__button mobooking-bf__button--outline">
                        <i class="fas fa-arrow-left"></i>
                        <?php esc_html_e('Back to Location', 'mobooking'); ?>
                    </button>
                    <button type="button" id="mobooking-bf-services-next-btn" class="mobooking-bf__button mobooking-bf__button--primary" disabled style="opacity: 0.5;">
                        <?php esc_html_e('Continue', 'mobooking'); ?>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- Step 3: Service Options -->
            <div id="mobooking-bf-step-3-options" class="mobooking-bf__step">
                <div class="mobooking-bf__step-with-sidebar">
                    <div class="mobooking-bf__step-content">
                        <h2 id="mobooking-bf-step-3-title" class="mobooking-bf__step-title">
                            <i class="fas fa-cogs"></i>
                            <?php esc_html_e('Configure Service Options', 'mobooking'); ?>
                        </h2>
                        <div id="mobooking-bf-service-options-display" class="mobooking-bf-service-options-display">
                            <div class="mobooking-bf__loading">
                                <?php esc_html_e('Loading service options...', 'mobooking'); ?>
                            </div>
                        </div>
                        <div id="mobooking-bf-step-3-feedback" class="mobooking-bf__feedback"></div>
                        <div class="mobooking-bf__button-group">
                            <button type="button" id="mobooking-bf-options-back-btn" class="mobooking-bf__button mobooking-bf__button--outline">
                                <i class="fas fa-arrow-left"></i>
                                <?php esc_html_e('Back to Services', 'mobooking'); ?>
                            </button>
                            <button type="button" id="mobooking-bf-options-next-btn" class="mobooking-bf__button mobooking-bf__button--primary">
                                <?php esc_html_e('Continue', 'mobooking'); ?>
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Sidebar for Step 3 -->
                    <div class="mobooking-bf__sidebar">
                        <h3 class="mobooking-bf__sidebar-title">
                            <i class="fas fa-receipt"></i>
                            <?php esc_html_e('Booking Summary', 'mobooking'); ?>
                        </h3>
                        <div id="mobooking-bf-sidebar-content-step3" class="mobooking-bf__sidebar-content">
                            <div class="mobooking-bf__summary-empty">
                                <i class="fas fa-info-circle"></i>
                                <p><?php esc_html_e('Configure options to see pricing', 'mobooking'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 4: Customer Details & Scheduling -->
            <div id="mobooking-bf-step-4-details" class="mobooking-bf__step">
                <div class="mobooking-bf__step-with-sidebar">
                    <div class="mobooking-bf__step-content">
                        <h2 id="mobooking-bf-step-4-title" class="mobooking-bf__step-title">
                            <i class="fas fa-user-edit"></i>
                            <?php esc_html_e('Your Details & Preferred Time', 'mobooking'); ?>
                        </h2>
                        <form id="mobooking-bf-details-form">
                            <div class="mobooking-bf__form-group">
                                <label for="mobooking-bf-customer-name" class="mobooking-bf__label">
                                    <?php esc_html_e('Full Name', 'mobooking'); ?>
                                    <span class="mobooking-bf__required-indicator">*</span>
                                </label>
                                <input type="text" id="mobooking-bf-customer-name" name="customer_name" required class="mobooking-bf__input" placeholder="Enter your full name">
                            </div>
                            <div class="mobooking-bf__form-group">
                                <label for="mobooking-bf-customer-email" class="mobooking-bf__label">
                                    <?php esc_html_e('Email Address', 'mobooking'); ?>
                                    <span class="mobooking-bf__required-indicator">*</span>
                                </label>
                                <input type="email" id="mobooking-bf-customer-email" name="customer_email" required class="mobooking-bf__input" placeholder="your@email.com">
                            </div>
                            <div class="mobooking-bf__form-group">
                                <label for="mobooking-bf-customer-phone" class="mobooking-bf__label">
                                    <?php esc_html_e('Phone Number', 'mobooking'); ?>
                                    <span class="mobooking-bf__required-indicator">*</span>
                                </label>
                                <input type="tel" id="mobooking-bf-customer-phone" name="customer_phone" required class="mobooking-bf__input" placeholder="(555) 123-4567">
                            </div>
                            <div class="mobooking-bf__form-group">
                                <label for="mobooking-bf-service-address" class="mobooking-bf__label">
                                    <?php esc_html_e('Service Address', 'mobooking'); ?>
                                    <span class="mobooking-bf__required-indicator">*</span>
                                </label>
                                <textarea id="mobooking-bf-service-address" name="service_address" rows="3" required class="mobooking-bf__textarea" placeholder="Enter the full address where service is needed"></textarea>
                            </div>
                            <div class="mobooking-bf__form-group">
                                <label for="mobooking-bf-booking-date" class="mobooking-bf__label">
                                    <?php esc_html_e('Preferred Date', 'mobooking'); ?>
                                    <span class="mobooking-bf__required-indicator">*</span>
                                </label>
                                <input type="text" id="mobooking-bf-booking-date" name="booking_date" required autocomplete="off" class="mobooking-bf__input" placeholder="Select date">
                            </div>
                            <div class="mobooking-bf__form-group">
                                <label for="mobooking-bf-booking-time" class="mobooking-bf__label">
                                    <?php esc_html_e('Preferred Time', 'mobooking'); ?>
                                    <span class="mobooking-bf__required-indicator">*</span>
                                </label>
                                <input type="text" id="mobooking-bf-booking-time" name="booking_time" required placeholder="e.g., 10:00 AM or 14:30" class="mobooking-bf__input">
                            </div>
                            <div class="mobooking-bf__form-group">
                                <label for="mobooking-bf-special-instructions" class="mobooking-bf__label">
                                    <?php esc_html_e('Special Instructions', 'mobooking'); ?>
                                    <span style="color: hsl(var(--muted-foreground)); font-weight: 400;">(<?php esc_html_e('optional', 'mobooking'); ?>)</span>
                                </label>
                                <textarea id="mobooking-bf-special-instructions" name="special_instructions" rows="3" class="mobooking-bf__textarea" placeholder="Any special requirements or notes..."></textarea>
                            </div>
                        </form>
                        <div id="mobooking-bf-step-4-feedback" class="mobooking-bf__feedback"></div>
                        <div class="mobooking-bf__button-group">
                            <button type="button" id="mobooking-bf-details-back-btn" class="mobooking-bf__button mobooking-bf__button--outline">
                                <i class="fas fa-arrow-left"></i>
                                <?php esc_html_e('Back to Options', 'mobooking'); ?>
                            </button>
                            <button type="button" id="mobooking-bf-details-next-btn" class="mobooking-bf__button mobooking-bf__button--primary">
                                <?php esc_html_e('Continue', 'mobooking'); ?>
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Sidebar for Step 4 -->
                    <div class="mobooking-bf__sidebar">
                        <h3 class="mobooking-bf__sidebar-title">
                            <i class="fas fa-receipt"></i>
                            <?php esc_html_e('Booking Summary', 'mobooking'); ?>
                        </h3>
                        <div id="mobooking-bf-sidebar-content-step4" class="mobooking-bf__sidebar-content">
                            <div class="mobooking-bf__summary-empty">
                                <i class="fas fa-info-circle"></i>
                                <p><?php esc_html_e('Your booking details will appear here', 'mobooking'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 5: Review & Confirm -->
            <div id="mobooking-bf-step-5-review" class="mobooking-bf__step">
                <div class="mobooking-bf__step-with-sidebar">
                    <div class="mobooking-bf__step-content">
                        <h2 id="mobooking-bf-step-5-title" class="mobooking-bf__step-title">
                            <i class="fas fa-clipboard-check"></i>
                            <?php esc_html_e('Review & Confirm Booking', 'mobooking'); ?>
                        </h2>
                        <div id="mobooking-bf-review-summary" class="mobooking-bf__review-summary">
                            <div class="mobooking-bf__loading">
                                <?php esc_html_e('Loading booking summary...', 'mobooking'); ?>
                            </div>
                        </div>
                        
                        <!-- Discount Code Section -->
                        <div class="mobooking-bf__form-group" style="border: 1px solid hsl(var(--border)); border-radius: calc(var(--radius) - 2px); padding: 1rem; background: hsl(var(--muted) / 0.3);">
                            <label for="mobooking-bf-discount-code" class="mobooking-bf__label">
                                <i class="fas fa-tag"></i>
                                <?php esc_html_e('Discount Code', 'mobooking'); ?>
                                <span style="color: hsl(var(--muted-foreground)); font-weight: 400;">(<?php esc_html_e('optional', 'mobooking'); ?>)</span>
                            </label>
                            <div style="display: flex; gap: 0.5rem;">
                                <input type="text" id="mobooking-bf-discount-code" name="discount_code" class="mobooking-bf__input" placeholder="Enter discount code" style="flex: 1;">
                                <button type="button" id="mobooking-bf-apply-discount-btn" class="mobooking-bf__button mobooking-bf__button--secondary">
                                    <i class="fas fa-check"></i>
                                    <?php esc_html_e('Apply', 'mobooking'); ?>
                                </button>
                            </div>
                            <div id="mobooking-bf-discount-feedback" class="mobooking-bf__feedback"></div>
                        </div>

                        <div id="mobooking-bf-step-5-feedback" class="mobooking-bf__feedback"></div>
                        <div class="mobooking-bf__button-group">
                            <button type="button" id="mobooking-bf-review-back-btn" class="mobooking-bf__button mobooking-bf__button--outline">
                                <i class="fas fa-arrow-left"></i>
                                <?php esc_html_e('Back to Details', 'mobooking'); ?>
                            </button>
                            <button type="button" id="mobooking-bf-confirm-booking-btn" class="mobooking-bf__button mobooking-bf__button--primary">
                                <i class="fas fa-check-circle"></i>
                                <?php esc_html_e('Confirm Booking', 'mobooking'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Sidebar for Step 5 -->
                    <div class="mobooking-bf__sidebar">
                        <h3 class="mobooking-bf__sidebar-title">
                            <i class="fas fa-receipt"></i>
                            <?php esc_html_e('Final Summary', 'mobooking'); ?>
                        </h3>
                        <div id="mobooking-bf-sidebar-content-step5" class="mobooking-bf__sidebar-content">
                            <!-- Pricing Summary -->
                            <div style="border: 1px solid hsl(var(--border)); border-radius: calc(var(--radius) - 2px); padding: 1rem; background: hsl(var(--card));">
                                <div class="mobooking-bf__summary-item">
                                    <span><?php esc_html_e('Subtotal', 'mobooking'); ?>:</span>
                                    <span id="mobooking-bf-subtotal">$0.00</span>
                                </div>
                                <div class="mobooking-bf__summary-item" id="mobooking-bf-discount-applied-row" style="display: none; color: hsl(142.1 70.6% 45.3%);">
                                    <span><?php esc_html_e('Discount', 'mobooking'); ?>:</span>
                                    <span id="mobooking-bf-discount-applied">-$0.00</span>
                                </div>
                                <div class="mobooking-bf__summary-item mobooking-bf__summary-total">
                                    <span><?php esc_html_e('Total', 'mobooking'); ?>:</span>
                                    <span id="mobooking-bf-final-total">$0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 6: Confirmation -->
            <div id="mobooking-bf-step-6-confirmation" class="mobooking-bf__step">
                <div class="mobooking-bf__confirmation">
                    <div class="mobooking-bf__confirmation-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2><?php esc_html_e('Booking Confirmed!', 'mobooking'); ?></h2>
                    <div id="mobooking-bf-confirmation-message">
                        <p><?php esc_html_e('Your booking has been successfully submitted.', 'mobooking'); ?></p>
                        <p><?php esc_html_e('You will receive a confirmation email shortly.', 'mobooking'); ?></p>
                    </div>
                    <div class="mobooking-bf__button-group" style="justify-content: center;">
                        <button type="button" id="mobooking-bf-new-booking-btn" class="mobooking-bf__button mobooking-bf__button--primary">
                            <i class="fas fa-plus"></i>
                            <?php esc_html_e('Make Another Booking', 'mobooking'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Service Card Template - Enhanced and Fixed -->
<script type="text/template" id="mobooking-bf-service-item-template">
    <div class="mobooking-bf__service-card" data-service-id="<%= service_id %>">
        <div class="mobooking-bf__radio-wrapper">
            <input type="radio" name="selected_service" value="<%= service_id %>" id="service-<%= service_id %>" class="mobooking-bf__radio-input">
            <label for="service-<%= service_id %>" class="mobooking-bf__radio-label">
                <div class="mobooking-bf__service-content">
                    <div class="mobooking-bf__service-header">
                        <div class="mobooking-bf__service-icon">
                            <!-- icon_placeholder -->
                        </div>
                        <div class="mobooking-bf__service-info">
                            <h4 class="mobooking-bf__service-name"><%= name %></h4>
                            <% if (typeof price !== 'undefined' && price > 0) { %>
                                <p class="mobooking-bf__service-price">$<%= price %></p>
                            <% } %>
                        </div>
                    </div>
                    <!-- image_placeholder -->
                    <% if (typeof description !== 'undefined' && description) { %>
                        <p class="mobooking-bf__service-description"><%= description %></p>
                    <% } %>
                    <div class="mobooking-bf__service-meta">
                        <span class="mobooking-bf__service-duration">
                            <i class="fas fa-clock"></i>
                            <%= duration %> min
                        </span>
                        <% if (typeof category !== 'undefined' && category) { %>
                            <span class="mobooking-bf__service-category">
                                <i class="fas fa-tag"></i>
                                <%= category %>
                            </span>
                        <% } %>
                    </div>
                </div>
            </label>
        </div>
    </div>
</script>

<!-- Alternative Service Card Template for backward compatibility -->
<script type="text/template" id="mobooking-bf-service-card-template">
    <div class="mobooking-bf__service-card" data-service-id="<%= service_id %>">
        <div class="mobooking-bf__radio-wrapper">
            <input type="radio" name="selected_service" value="<%= service_id %>" id="service-alt-<%= service_id %>" class="mobooking-bf__radio-input">
            <label for="service-alt-<%= service_id %>" class="mobooking-bf__radio-label">
                <div class="mobooking-bf__service-header">
                    <div class="mobooking-bf__service-icon">
                        <!-- icon_placeholder -->
                    </div>
                    <div class="mobooking-bf__service-info">
                        <h4 class="mobooking-bf__service-name"><%= name %></h4>
                        <p class="mobooking-bf__service-price">$<%= price %></p>
                    </div>
                </div>
                <!-- image_placeholder -->
                <div class="mobooking-bf__service-details">
                    <% if (typeof description !== 'undefined' && description) { %>
                        <p class="mobooking-bf__service-description"><%= description %></p>
                    <% } %>
                    <div class="mobooking-bf__service-meta">
                        <span class="mobooking-bf__service-duration">
                            <i class="fas fa-clock"></i>
                            <%= duration %> min
                        </span>
                        <% if (typeof category !== 'undefined' && category) { %>
                            <span class="mobooking-bf__service-category">
                                <i class="fas fa-tag"></i>
                                <%= category %>
                            </span>
                        <% } %>
                    </div>
                </div>
            </label>
        </div>
    </div>
</script>


<!-- Service Option Templates - Compatible with existing MoBooking JS -->
<script type="text/template" id="mobooking-bf-option-checkbox-template">
    <div class="mobooking-bf__option-group mobooking-bf__option-item" data-service-id="<%= service_id %>" data-option-id="<%= option_id %>" data-option-type="checkbox">
        <div class="mobooking-bf__checkbox-wrapper">
            <input type="checkbox" name="service_option[<%= service_id %>][<%= option_id %>]" value="1" class="mobooking-bf__checkbox" id="option-<%= service_id %>-<%= option_id %>">
            <label for="option-<%= service_id %>-<%= option_id %>" class="mobooking-bf__label" style="margin-bottom: 0; cursor: pointer;">
                <%= name %>
                <!-- price_impact_placeholder -->
            </label>
        </div>
        <!-- description_placeholder -->
    </div>
</script>

<script type="text/template" id="mobooking-bf-option-text-template">
    <div class="mobooking-bf__option-group mobooking-bf__option-item" data-service-id="<%= service_id %>" data-option-id="<%= option_id %>" data-option-type="text">
        <label for="option-<%= service_id %>-<%= option_id %>" class="mobooking-bf__label">
            <%= name %>
            <!-- price_impact_placeholder -->
            <!-- required_indicator_placeholder -->
        </label>
        <input type="text" id="option-<%= service_id %>-<%= option_id %>" name="service_option[<%= service_id %>][<%= option_id %>]" class="mobooking-bf__input">
        <!-- description_placeholder -->
    </div>
</script>

<script type="text/template" id="mobooking-bf-option-textarea-template">
    <div class="mobooking-bf__option-group mobooking-bf__option-item" data-service-id="<%= service_id %>" data-option-id="<%= option_id %>" data-option-type="textarea">
        <label for="option-<%= service_id %>-<%= option_id %>" class="mobooking-bf__label">
            <%= name %>
            <!-- price_impact_placeholder -->
            <!-- required_indicator_placeholder -->
        </label>
        <textarea id="option-<%= service_id %>-<%= option_id %>" name="service_option[<%= service_id %>][<%= option_id %>]" class="mobooking-bf__textarea" rows="3"></textarea>
        <!-- description_placeholder -->
    </div>
</script>

<script type="text/template" id="mobooking-bf-option-number-template">
    <div class="mobooking-bf__option-group mobooking-bf__option-item" data-service-id="<%= service_id %>" data-option-id="<%= option_id %>" data-option-type="number">
        <label for="option-<%= service_id %>-<%= option_id %>" class="mobooking-bf__label">
            <%= name %>
            <!-- price_impact_placeholder -->
            <!-- required_indicator_placeholder -->
        </label>
        <div class="mobooking-bf__number-input-wrapper" style="display: flex; align-items: center; gap: 0.5rem; max-width: 150px;">
            <button type="button" class="mobooking-bf__button mobooking-bf__button--outline mobooking-bf__number-btn mobooking-bf__number-btn--minus" style="padding: 0; width: 2rem; height: 2rem; min-width: auto;" data-action="decrease">
                <i class="fas fa-minus"></i>
            </button>
            <input type="number" id="option-<%= service_id %>-<%= option_id %>" name="service_option[<%= service_id %>][<%= option_id %>]" value="0" min="0" class="mobooking-bf__input mobooking-bf__input--number" style="text-align: center; flex: 1;">
            <button type="button" class="mobooking-bf__button mobooking-bf__button--outline mobooking-bf__number-btn mobooking-bf__number-btn--plus" style="padding: 0; width: 2rem; height: 2rem; min-width: auto;" data-action="increase">
                <i class="fas fa-plus"></i>
            </button>
        </div>
        <!-- description_placeholder -->
    </div>
</script>

<script type="text/template" id="mobooking-bf-option-quantity-template">
    <div class="mobooking-bf__option-group mobooking-bf__option-item" data-service-id="<%= service_id %>" data-option-id="<%= option_id %>" data-option-type="quantity">
        <label for="option-<%= service_id %>-<%= option_id %>-qty" class="mobooking-bf__label">
            <%= name %>
            <!-- price_impact_placeholder -->
            <!-- required_indicator_placeholder -->
        </label>
        <div class="mobooking-bf__number-input-wrapper" style="display: flex; align-items: center; gap: 0.5rem; max-width: 150px;">
            <button type="button" class="mobooking-bf__button mobooking-bf__button--outline mobooking-bf__number-btn mobooking-bf__number-btn--minus" style="padding: 0; width: 2rem; height: 2rem; min-width: auto;" data-action="decrease">
                <i class="fas fa-minus"></i>
            </button>
            <input type="number" id="option-<%= service_id %>-<%= option_id %>-qty" name="service_option[<%= service_id %>][<%= option_id %>][quantity]" value="<%= quantity_default_value %>" min="0" class="mobooking-bf__input mobooking-bf__input--number mobooking-bf-option-quantity-input" style="text-align: center; flex: 1;">
            <button type="button" class="mobooking-bf__button mobooking-bf__button--outline mobooking-bf__number-btn mobooking-bf__number-btn--plus" style="padding: 0; width: 2rem; height: 2rem; min-width: auto;" data-action="increase">
                <i class="fas fa-plus"></i>
            </button>
        </div>
        <!-- description_placeholder -->
    </div>
</script>

<script type="text/template" id="mobooking-bf-option-select-template">
    <div class="mobooking-bf__option-group mobooking-bf__option-item" data-service-id="<%= service_id %>" data-option-id="<%= option_id %>" data-option-type="select">
        <label for="option-<%= service_id %>-<%= option_id %>" class="mobooking-bf__label">
            <%= name %>
            <!-- price_impact_placeholder -->
            <!-- required_indicator_placeholder -->
        </label>
        <select id="option-<%= service_id %>-<%= option_id %>" name="service_option[<%= service_id %>][<%= option_id %>]" class="mobooking-bf__select">
            <option value="">Select an option</option>
            <!-- options_loop_placeholder -->
        </select>
        <!-- description_placeholder -->
    </div>
</script>

<script type="text/template" id="mobooking-bf-option-radio-template">
    <div class="mobooking-bf__option-group mobooking-bf__option-item mobooking-bf__label--radio-group" data-service-id="<%= service_id %>" data-option-id="<%= option_id %>" data-option-type="radio">
        <p class="mobooking-bf__label">
            <%= name %>
            <!-- price_impact_placeholder -->
            <!-- required_indicator_placeholder -->
        </p>
        <!-- options_loop_placeholder -->
        <!-- description_placeholder -->
    </div>
</script>

<script>
// Enhanced JavaScript for Shadcn UI style interactions
document.addEventListener('DOMContentLoaded', function() {
    // Initialize progress bar
    updateProgressBar(1);
    
    // Service card interactions - Fixed to integrate with existing MoBooking JS
    document.addEventListener('click', function(event) {
        // Handle service card clicks
        if (event.target.closest('.mobooking-bf__service-card')) {
            const card = event.target.closest('.mobooking-bf__service-card');
            const radio = card.querySelector('.mobooking-bf__radio-input');
            
            if (radio && !radio.checked) {
                radio.checked = true;
                
                // Trigger change event for existing MoBooking JS
                const changeEvent = new Event('change', { bubbles: true });
                radio.dispatchEvent(changeEvent);
                
                // Also trigger click event for compatibility
                const clickEvent = new Event('click', { bubbles: true });
                radio.dispatchEvent(clickEvent);
            }
        }
    });
    
    // Handle radio button changes - integrate with existing MoBooking logic
    document.addEventListener('change', function(event) {
        if (event.target.name === 'selected_service') {
            const allCards = document.querySelectorAll('.mobooking-bf__service-card');
            const selectedCard = event.target.closest('.mobooking-bf__service-card');
            
            allCards.forEach(card => {
                card.classList.remove('selected');
            });
            
            if (selectedCard) {
                selectedCard.classList.add('selected');
                
                // Update sidebar with selected service
                updateSidebarWithService(selectedCard.dataset.serviceId);
                
                // Trigger existing MoBooking service selection logic
                if (typeof window.displayStep2_HandleServiceSelection === 'function') {
                    window.displayStep2_HandleServiceSelection();
                }
                
                // Enable next button
                const nextButton = document.getElementById('mobooking-bf-services-next-btn');
                if (nextButton) {
                    nextButton.disabled = false;
                    nextButton.style.opacity = '1';
                }
            }
        }
    });
    
    // Handle service selection for existing MoBooking integration
    document.addEventListener('change', function(event) {
        if (event.target.type === 'radio' && event.target.name === 'selected_service') {
            // Store selected service data in the format expected by existing JS
            const serviceCard = event.target.closest('.mobooking-bf__service-card');
            if (serviceCard) {
                const serviceData = {
                    service_id: serviceCard.dataset.serviceId,
                    name: serviceCard.querySelector('.mobooking-bf__service-name')?.textContent || '',
                    price: serviceCard.querySelector('.mobooking-bf__service-price')?.textContent.replace(', '') || '0',
                    duration: serviceCard.querySelector('.mobooking-bf__service-duration')?.textContent || '',
                    description: serviceCard.querySelector('.mobooking-bf__service-description')?.textContent || ''
                };
                
                // Store in sessionStorage for existing MoBooking JS
                sessionStorage.setItem('mobooking_selected_service', JSON.stringify(serviceData));
                
                // Store in global variable if it exists
                if (window.currentSelectionForSummary) {
                    window.currentSelectionForSummary.service = serviceData;
                }
                
                // Trigger custom event for existing MoBooking JS
                const customEvent = new CustomEvent('mobooking_service_selected', {
                    detail: serviceData
                });
                document.dispatchEvent(customEvent);
            }
        }
    });
    
    // Number input controls
    document.addEventListener('click', function(event) {
        if (event.target.closest('[data-action]')) {
            const button = event.target.closest('[data-action]');
            const group = button.closest('.mobooking-bf__option-group');
            const input = group.querySelector('input[type="number"]');
            const action = button.dataset.action;
            let currentValue = parseInt(input.value) || 0;
            
            if (action === 'increase') {
                currentValue += 1;
            } else if (action === 'decrease' && currentValue > 0) {
                currentValue -= 1;
            }
            
            input.value = currentValue;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            
            // Visual feedback
            button.style.transform = 'scale(0.95)';
            setTimeout(() => {
                button.style.transform = 'scale(1)';
            }, 100);
        }
    });
    
    // Enhanced step navigation with existing MoBooking integration
    function navigateToStep(stepNumber) {
        const currentStep = document.querySelector('.mobooking-bf__step.active');
        const nextStep = document.getElementById(`mobooking-bf-step-${stepNumber}-${getStepName(stepNumber)}`);
        
        if (currentStep) {
            currentStep.classList.remove('active');
            setTimeout(() => {
                currentStep.style.display = 'none';
                
                if (nextStep) {
                    nextStep.style.display = 'block';
                    setTimeout(() => {
                        nextStep.classList.add('active');
                    }, 50);
                    updateProgressBar(stepNumber);
                    
                    // Trigger existing MoBooking step logic
                    if (window.displayStep) {
                        window.displayStep(stepNumber);
                    }
                }
            }, 150);
        }
    }
    
    // Button click handlers for integration with existing MoBooking JS
    document.addEventListener('click', function(event) {
        // Services Next Button
        if (event.target.id === 'mobooking-bf-services-next-btn') {
            const selectedService = document.querySelector('input[name="selected_service"]:checked');
            if (selectedService) {
                // Call existing MoBooking function if it exists
                if (typeof window.displayStep2_HandleNext === 'function') {
                    window.displayStep2_HandleNext();
                } else {
                    // Fallback navigation
                    navigateToStep(3);
                }
            } else {
                // Show error message
                const feedback = document.getElementById('mobooking-bf-step-2-feedback');
                if (feedback) {
                    showFeedback(feedback, 'Please select a service to continue.', 'error');
                }
            }
        }
        
        // Options Next Button
        if (event.target.id === 'mobooking-bf-options-next-btn') {
            if (typeof window.displayStep3_HandleNext === 'function') {
                window.displayStep3_HandleNext();
            } else {
                navigateToStep(4);
            }
        }
        
        // Details Next Button
        if (event.target.id === 'mobooking-bf-details-next-btn') {
            if (typeof window.displayStep4_HandleNext === 'function') {
                window.displayStep4_HandleNext();
            } else {
                navigateToStep(5);
            }
        }
        
        // Back buttons
        if (event.target.id === 'mobooking-bf-services-back-btn') {
            navigateToStep(1);
        }
        if (event.target.id === 'mobooking-bf-options-back-btn') {
            navigateToStep(2);
        }
        if (event.target.id === 'mobooking-bf-details-back-btn') {
            navigateToStep(3);
        }
        if (event.target.id === 'mobooking-bf-review-back-btn') {
            navigateToStep(4);
        }
    });
    
    function getStepName(stepNumber) {
        const stepNames = {
            1: 'location',
            2: 'services', 
            3: 'options',
            4: 'details',
            5: 'review',
            6: 'confirmation'
        };
        return stepNames[stepNumber] || 'location';
    }
    
    function updateProgressBar(currentStep) {
        const progressSteps = document.querySelectorAll('.mobooking-bf__progress-step');
        const progressContainer = document.querySelector('.mobooking-bf__progress-steps');
        
        progressSteps.forEach((step, index) => {
            const stepNumber = index + 1;
            step.classList.remove('active', 'completed');
            
            if (stepNumber < currentStep) {
                step.classList.add('completed');
            } else if (stepNumber === currentStep) {
                step.classList.add('active');
            }
        });
        
        // Update progress bar width
        const progressWidth = ((currentStep - 1) / (progressSteps.length - 1)) * 100;
        progressContainer.style.setProperty('--progress-width', `${progressWidth}%`);
    }
    
    // Loading state management
    function showLoading(element, message = 'Loading...') {
        const loadingHTML = `<div class="mobooking-bf__loading">${message}</div>`;
        element.innerHTML = loadingHTML;
        if (element.tagName === 'BUTTON') {
            element.disabled = true;
        }
    }
    
    function hideLoading(element, originalContent) {
        element.innerHTML = originalContent;
        if (element.tagName === 'BUTTON') {
            element.disabled = false;
        }
    }
    
    // Enhanced feedback messages
    function showFeedback(element, message, type = 'info') {
        element.className = `mobooking-bf__feedback ${type}`;
        element.innerHTML = message;
        element.style.display = 'block';
        
        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(() => {
                element.style.display = 'none';
            }, 5000);
        }
    }
    
    // Mock service loading simulation (remove when real AJAX is connected)
    function simulateServiceLoading() {
        const servicesList = document.getElementById('mobooking-bf-services-list');
        if (servicesList && servicesList.querySelector('.mobooking-bf__loading')) {
            setTimeout(() => {
                servicesList.innerHTML = `
                    <div class="mobooking-bf__service-card" data-service-id="1">
                        <div class="mobooking-bf__radio-wrapper">
                            <input type="radio" name="selected_service" value="1" id="service-1" class="mobooking-bf__radio-input">
                        </div>
                        <div class="mobooking-bf__service-header">
                            <div class="mobooking-bf__service-icon">
                                <i class="fas fa-home"></i>
                            </div>
                            <div class="mobooking-bf__service-info">
                                <h4 class="mobooking-bf__service-name">House Cleaning (Simulated)</h4>
                                <p class="mobooking-bf__service-price">$120.00</p>
                            </div>
                        </div>
                        <p class="mobooking-bf__service-description">Simulated description.</p>
                        <div class="mobooking-bf__service-meta">
                            <span class="mobooking-bf__service-duration">
                                <i class="fas fa-clock"></i>
                                180 min
                            </span>
                            <span class="mobooking-bf__service-category">
                                <i class="fas fa-tag"></i>
                                Residential
                            </span>
                        </div>
                    </div>
                    <div class="mobooking-bf__service-card" data-service-id="2">
                        <div class="mobooking-bf__radio-wrapper">
                            <input type="radio" name="selected_service" value="2" id="service-2" class="mobooking-bf__radio-input">
                        </div>
                        <div class="mobooking-bf__service-header">
                            <div class="mobooking-bf__service-icon">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="mobooking-bf__service-info">
                                <h4 class="mobooking-bf__service-name">Office Cleaning</h4>
                                <p class="mobooking-bf__service-price">$85.00</p>
                            </div>
                        </div>
                        <p class="mobooking-bf__service-description">Professional office cleaning including desks, common areas, and restrooms.</p>
                        <div class="mobooking-bf__service-meta">
                            <span class="mobooking-bf__service-duration">
                                <i class="fas fa-clock"></i>
                                120 min
                            </span>
                            <span class="mobooking-bf__service-category">
                                <i class="fas fa-tag"></i>
                                Commercial
                            </span>
                        </div>
                    </div>
                `;
            }, 1500);
        }
    }
    
    // Update sidebar with selected service info
    function updateSidebarWithService(serviceId) {
        const serviceCard = document.querySelector(`[data-service-id="${serviceId}"]`);
        if (!serviceCard) return;
        
        const serviceName = serviceCard.querySelector('.mobooking-bf__service-name').textContent;
        const servicePrice = serviceCard.querySelector('.mobooking-bf__service-price').textContent;
        const serviceDuration = serviceCard.querySelector('.mobooking-bf__service-duration').textContent;
        
        // Update all sidebar instances
        const sidebarContents = [
            document.getElementById('mobooking-bf-sidebar-content-step3'),
            document.getElementById('mobooking-bf-sidebar-content-step4'),
            document.getElementById('mobooking-bf-sidebar-content-step5')
        ];
        
        sidebarContents.forEach(sidebar => {
            if (sidebar) {
                sidebar.innerHTML = `
                    <div class="mobooking-bf__summary-item">
                        <strong>Selected Service:</strong>
                    </div>
                    <div class="mobooking-bf__summary-item">
                        <span>${serviceName}</span>
                        <span>${servicePrice}</span>
                    </div>
                    <div class="mobooking-bf__summary-item">
                        <span>Duration:</span>
                        <span>${serviceDuration}</span>
                    </div>
                    <div class="mobooking-bf__summary-item mobooking-bf__summary-total">
                        <span>Subtotal:</span>
                        <span>${servicePrice}</span>
                    </div>
                `;
            }
        });
        
        // Update main pricing displays
        const subtotalElement = document.getElementById('mobooking-bf-subtotal');
        const finalTotalElement = document.getElementById('mobooking-bf-final-total');
        
        if (subtotalElement) subtotalElement.textContent = servicePrice;
        if (finalTotalElement) finalTotalElement.textContent = servicePrice;
    }
    
    // Trigger service loading simulation
    simulateServiceLoading();
    
    // Initialize and integrate with existing MoBooking JS
    function initializeMoBookingIntegration() {
        // Wait for existing MoBooking JS to load
        if (typeof window.displayStep === 'function') {
            console.log('MoBooking JS detected, integrating...');
            
            // Override or extend existing functions if needed
            if (window.displayStep2_LoadServices) {
                const originalLoadServices = window.displayStep2_LoadServices;
                window.displayStep2_LoadServices = function() {
                    originalLoadServices.call(this);
                    // Add our enhanced styling after services load
                    setTimeout(() => {
                        enhanceServiceCards();
                    }, 100);
                };
            }
        } else {
            // Retry after a short delay
            setTimeout(initializeMoBookingIntegration, 100);
        }
    }
    
    // Enhance service cards with modern styling after they're loaded by existing JS
    function enhanceServiceCards() {
        const serviceCards = document.querySelectorAll('.mobooking-bf__service-card');
        serviceCards.forEach(card => {
            // Ensure radio button is properly positioned
            let radioWrapper = card.querySelector('.mobooking-bf__radio-wrapper');
            if (!radioWrapper) {
                radioWrapper = document.createElement('div');
                radioWrapper.className = 'mobooking-bf__radio-wrapper';
                const radio = card.querySelector('input[type="radio"]');
                if (radio) {
                    radio.className = 'mobooking-bf__radio-input';
                    radioWrapper.appendChild(radio);
                    card.appendChild(radioWrapper);
                }
            }
            
            // Add click handler for the entire card
            card.addEventListener('click', function(e) {
                if (e.target.type !== 'radio') {
                    const radio = this.querySelector('input[type="radio"]');
                    if (radio) {
                        radio.checked = true;
                        radio.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
            });
        });
    }
    
    /*
    // Make functions available globally for existing MoBooking JS
    window.MoBookingUI = {
        navigateToStep,
        updateProgressBar,
        showLoading,
        hideLoading,
        showFeedback,
        enhanceServiceCards,
        updateSidebarWithService
    };
    
    // Initialize integration
    initializeMoBookingIntegration();
    */
});
</script>

<?php
// Conditionally load footer for public vs embed view
if (get_query_var('mobooking_page_type') !== 'embed') {
    get_footer();
}
?>