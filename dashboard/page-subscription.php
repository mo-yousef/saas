<?php
/**
 * Enhanced Subscription Management Page with Real-time Sync
 * @package NORDBOOKING
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$user_id = get_current_user_id();

// Use enhanced subscription manager for real-time data
$subscription_manager = \NORDBOOKING\Classes\SubscriptionManager::getInstance();
$subscription = $subscription_manager->get_subscription_with_sync($user_id);
$status = $subscription_manager->get_status_with_validation($user_id);
$days_left = \NORDBOOKING\Classes\Subscription::get_days_until_next_payment($user_id);

// Check if Stripe is configured
$stripe_configured = \NORDBOOKING\Classes\StripeConfig::is_configured();
$config_status = \NORDBOOKING\Classes\StripeConfig::get_configuration_status();

// Get user info for display
$user = get_userdata($user_id);
$user_email = $user ? $user->user_email : '';
$user_name = $user ? $user->display_name : '';

// Get pricing information
$pricing_info = null;
if ($stripe_configured) {
    $pricing_info = \NORDBOOKING\Classes\Subscription::get_pricing_info();
}
?>

<?php
// Handle success/error messages from Stripe redirects
$message = '';
$message_type = '';

if (isset($_GET['success']) && $_GET['success'] == '1') {
    // Sync subscription status after successful payment
    if (class_exists('NORDBOOKING\Classes\Subscription')) {
        \NORDBOOKING\Classes\Subscription::sync_subscription_status($user_id);
        // Refresh status after sync
        $status = \NORDBOOKING\Classes\Subscription::get_subscription_status($user_id);
    }
    
    if ($status === 'active') {
        $message = __('Subscription activated successfully! Welcome to NORDBOOKING Pro.', 'NORDBOOKING');
    } else {
        $message = __('Payment processed successfully! Your subscription is being activated.', 'NORDBOOKING');
    }
    $message_type = 'success';
} elseif (isset($_GET['cancelled']) && $_GET['cancelled'] == '1') {
    $message = __('Subscription setup was cancelled. You can try again anytime.', 'NORDBOOKING');
    $message_type = 'info';
}
?>

<div class="nordbooking-page-header">
    <div class="nordbooking-page-header-heading">
        <div class="nordbooking-page-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="20" height="14" x="2" y="5" rx="2"/>
                <line x1="2" x2="22" y1="10" y2="10"/>
            </svg>
        </div>
        <div class="heading-wrapper">
            <h1><?php esc_html_e('Subscription Management', 'NORDBOOKING'); ?></h1>
            <p class="dashboard-subtitle"><?php esc_html_e('Manage your subscription and billing information', 'NORDBOOKING'); ?></p>
        </div>
    </div>
</div>

<?php if ($message): ?>
<div class="subscription-message subscription-message-<?php echo esc_attr($message_type); ?>">
    <p><?php echo esc_html($message); ?></p>
</div>
<?php endif; ?>

<?php if (!$stripe_configured): ?>
<div class="subscription-message subscription-message-error">
    <?php if (\NORDBOOKING\Classes\StripeConfig::needs_price_id()): ?>
        <p><?php esc_html_e('Subscription system is almost ready! The administrator needs to configure the pricing in Stripe Dashboard.', 'NORDBOOKING'); ?></p>
    <?php elseif (\NORDBOOKING\Classes\StripeConfig::has_api_keys()): ?>
        <p><?php esc_html_e('Subscription system is being configured. Please contact the administrator to complete the Stripe setup.', 'NORDBOOKING'); ?></p>
    <?php else: ?>
        <p><?php esc_html_e('Subscription system is not configured. Please contact the administrator to set up Stripe integration.', 'NORDBOOKING'); ?></p>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="subscription-management-wrapper">
    <!-- Real-time Status Indicator -->
    <div id="sync-status" class="sync-status-indicator" style="display: none;">
        <div class="sync-message">
            <span class="sync-icon">🔄</span>
            <span class="sync-text">Syncing subscription status...</span>
        </div>
    </div>

    <!-- Current Subscription Status -->
    <div class="nordbooking-card card-bs" id="subscription-status-card">
        <div class="nordbooking-card-header">
            <div class="nordbooking-card-title-group">
                <div class="nordbooking-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 6v6l4 2"/>
                    </svg>
                </div>
                <h2 class="nordbooking-card-title"><?php esc_html_e('Current Subscription', 'NORDBOOKING'); ?></h2>
                <div class="card-actions">
                    <button id="auto-refresh-toggle" class="button button-small" title="Toggle auto-refresh">
                        <span class="auto-refresh-icon">🔄</span>
                        <span class="auto-refresh-text">Auto-sync: OFF</span>
                    </button>
                    <span id="last-updated" class="last-updated">
                        Last updated: <?php echo current_time('H:i:s'); ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="nordbooking-card-content">
            <div class="subscription-status-grid">
                <div class="status-item">
                    <label><?php esc_html_e('Status:', 'NORDBOOKING'); ?></label>
                    <div class="status-value">
                        <?php
                        $status_class = '';
                        $status_text = ucfirst($status);
                        
                        switch($status) {
                            case 'active':
                                $status_class = 'status-badge status-confirmed';
                                $status_text = __('Active', 'NORDBOOKING');
                                break;
                            case 'trial':
                                $status_class = 'status-badge status-processing';
                                $status_text = __('Trial', 'NORDBOOKING');
                                break;
                            case 'expired_trial':
                                $status_class = 'status-badge status-cancelled';
                                $status_text = __('Trial Expired', 'NORDBOOKING');
                                break;
                            case 'expired':
                                $status_class = 'status-badge status-cancelled';
                                $status_text = __('Expired', 'NORDBOOKING');
                                break;
                            case 'unsubscribed':
                            default:
                                $status_class = 'status-badge status-pending';
                                $status_text = __('Unsubscribed', 'NORDBOOKING');
                                break;
                        }
                        ?>
                        <span class="<?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_text); ?></span>
                    </div>
                </div>

                <?php if ($status === 'trial' || $status === 'active'): ?>
                <div class="status-item">
                    <label><?php esc_html_e('Days Remaining:', 'NORDBOOKING'); ?></label>
                    <div class="status-value">
                        <strong><?php echo esc_html($days_left); ?> <?php esc_html_e('days', 'NORDBOOKING'); ?></strong>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($subscription): ?>
                <div class="status-item">
                    <label><?php esc_html_e('Started:', 'NORDBOOKING'); ?></label>
                    <div class="status-value">
                        <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($subscription['created_at']))); ?>
                    </div>
                </div>

                <?php if (!empty($subscription['trial_ends_at']) && $status === 'trial'): ?>
                <div class="status-item">
                    <label><?php esc_html_e('Trial Ends:', 'NORDBOOKING'); ?></label>
                    <div class="status-value">
                        <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($subscription['trial_ends_at']))); ?>
                        <div class="countdown-timer" data-end-date="<?php echo esc_attr($subscription['trial_ends_at']); ?>"></div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($subscription['ends_at']) && $status === 'active'): ?>
                <div class="status-item">
                    <label><?php esc_html_e('Next Payment:', 'NORDBOOKING'); ?></label>
                    <div class="status-value">
                        <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($subscription['ends_at']))); ?>
                        <div class="countdown-timer" data-end-date="<?php echo esc_attr($subscription['ends_at']); ?>"></div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($pricing_info): ?>
                <div class="status-item">
                    <label><?php esc_html_e('Plan Price:', 'NORDBOOKING'); ?></label>
                    <div class="status-value">
                        <?php 
                        $amount = $pricing_info['amount'] / 100;
                        $currency = strtoupper($pricing_info['currency']);
                        $interval = $pricing_info['interval'];
                        echo esc_html("$currency $amount per $interval");
                        ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($subscription['stripe_subscription_id'])): ?>
                <div class="status-item">
                    <label><?php esc_html_e('Subscription ID:', 'NORDBOOKING'); ?></label>
                    <div class="status-value">
                        <code><?php echo esc_html(substr($subscription['stripe_subscription_id'], 0, 20) . '...'); ?></code>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Subscription Actions -->
    <div class="nordbooking-card card-bs">
        <div class="nordbooking-card-header">
            <div class="nordbooking-card-title-group">
                <div class="nordbooking-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2v20m9-9H3"/>
                    </svg>
                </div>
                <h2 class="nordbooking-card-title"><?php esc_html_e('Subscription Actions', 'NORDBOOKING'); ?></h2>
            </div>
        </div>
        <div class="nordbooking-card-content">
            <div class="subscription-actions">
                <?php if ($stripe_configured): ?>
                    <?php if ($status === 'unsubscribed' || $status === 'expired_trial' || $status === 'expired'): ?>
                        <div class="action-item">
                            <div class="action-info">
                                <h3><?php esc_html_e('Start Subscription', 'NORDBOOKING'); ?></h3>
                                <p><?php esc_html_e('Subscribe to unlock all features and continue using NORDBOOKING.', 'NORDBOOKING'); ?></p>
                            </div>
                            <button id="subscribe-now-btn" class="button button-primary">
                                <?php esc_html_e('Subscribe Now', 'NORDBOOKING'); ?>
                            </button>
                        </div>
                    <?php elseif ($status === 'trial'): ?>
                        <div class="action-item">
                            <div class="action-info">
                                <h3><?php esc_html_e('Upgrade to Full Subscription', 'NORDBOOKING'); ?></h3>
                                <p><?php esc_html_e('Your trial expires in ' . $days_left . ' days. Upgrade now to continue without interruption.', 'NORDBOOKING'); ?></p>
                            </div>
                            <button id="upgrade-subscription-btn" class="button button-primary">
                                <?php esc_html_e('Upgrade Now', 'NORDBOOKING'); ?>
                            </button>
                        </div>
                    <?php elseif ($status === 'active'): ?>
                        <div class="action-item">
                            <div class="action-info">
                                <h3><?php esc_html_e('Manage Billing', 'NORDBOOKING'); ?></h3>
                                <p><?php esc_html_e('Update payment method, view invoices, or manage your billing information.', 'NORDBOOKING'); ?></p>
                            </div>
                            <button id="manage-billing-btn" class="button button-secondary">
                                <?php esc_html_e('Manage Billing', 'NORDBOOKING'); ?>
                            </button>
                        </div>
                        
                        <div class="action-item">
                            <div class="action-info">
                                <h3><?php esc_html_e('Cancel Subscription', 'NORDBOOKING'); ?></h3>
                                <p><?php esc_html_e('Cancel your subscription. You\'ll continue to have access until the end of your billing period.', 'NORDBOOKING'); ?></p>
                            </div>
                            <button id="cancel-subscription-btn" class="button button-destructive">
                                <?php esc_html_e('Cancel Subscription', 'NORDBOOKING'); ?>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Enhanced Sync Options (always available if Stripe is configured) -->
                    <?php if ($stripe_configured): ?>
                        <div class="action-item">
                            <div class="action-info">
                                <h3><?php esc_html_e('Sync Options', 'NORDBOOKING'); ?></h3>
                                <p><?php esc_html_e('Keep your subscription status up-to-date with real-time synchronization.', 'NORDBOOKING'); ?></p>
                                <?php if ($subscription && empty($subscription['stripe_subscription_id'])): ?>
                                    <p style="color: orange;"><small><?php esc_html_e('Note: This will search for any unlinked subscriptions in Stripe.', 'NORDBOOKING'); ?></small></p>
                                <?php endif; ?>
                            </div>
                            <div class="sync-buttons">
                                <button id="refresh-status-btn" class="button button-secondary">
                                    <?php esc_html_e('Quick Sync', 'NORDBOOKING'); ?>
                                </button>
                                <button id="real-time-sync-btn" class="button button-primary">
                                    <?php esc_html_e('Deep Sync', 'NORDBOOKING'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="action-item">
                        <div class="action-info">
                            <h3><?php esc_html_e('Subscription Unavailable', 'NORDBOOKING'); ?></h3>
                            <p><?php esc_html_e('The subscription system is currently being configured. Please check back later or contact support.', 'NORDBOOKING'); ?></p>
                        </div>
                        <button class="button button-secondary" disabled>
                            <?php esc_html_e('Configuration Required', 'NORDBOOKING'); ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Debug Information (only show if there are issues) -->
    <?php if (current_user_can('manage_options') || (isset($_GET['debug']) && $_GET['debug'] == '1')): ?>
    <div class="nordbooking-card card-bs">
        <div class="nordbooking-card-header">
            <div class="nordbooking-card-title-group">
                <div class="nordbooking-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" x2="12" y1="8" y2="12"/>
                        <line x1="12" x2="12.01" y1="16" y2="16"/>
                    </svg>
                </div>
                <h2 class="nordbooking-card-title"><?php esc_html_e('Debug Information', 'NORDBOOKING'); ?></h2>
            </div>
        </div>
        <div class="nordbooking-card-content">
            <div class="debug-info">
                <p><strong>User ID:</strong> <?php echo $user_id; ?></p>
                <p><strong>Subscription Status:</strong> <?php echo $status; ?></p>
                <p><strong>Has Local Subscription:</strong> <?php echo $subscription ? 'Yes' : 'No'; ?></p>
                <?php if ($subscription): ?>
                    <p><strong>Stripe Customer ID:</strong> <?php echo $subscription['stripe_customer_id'] ?: 'None'; ?></p>
                    <p><strong>Stripe Subscription ID:</strong> <?php echo $subscription['stripe_subscription_id'] ?: 'None'; ?></p>
                    <p><strong>Local Status:</strong> <?php echo $subscription['status']; ?></p>
                <?php endif; ?>
                <p><strong>Stripe Configured:</strong> <?php echo $stripe_configured ? 'Yes' : 'No'; ?></p>
                
                <?php if (!$subscription || empty($subscription['stripe_subscription_id'])): ?>
                    <p style="color: orange;"><strong>Issue:</strong> No Stripe subscription linked. This might happen if:</p>
                    <ul style="margin-left: 20px;">
                        <li>Payment was not completed</li>
                        <li>Webhook was not received</li>
                        <li>Subscription exists in Stripe but not linked locally</li>
                    </ul>
                    <p><strong>Solution:</strong> Click "Refresh Status" to search for and link any existing Stripe subscriptions.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Billing Information -->
    <?php if ($status === 'active' || $status === 'trial'): ?>
    <div class="nordbooking-card card-bs">
        <div class="nordbooking-card-header">
            <div class="nordbooking-card-title-group">
                <div class="nordbooking-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                        <rect width="8" height="4" x="8" y="2" rx="1" ry="1"/>
                    </svg>
                </div>
                <h2 class="nordbooking-card-title"><?php esc_html_e('Account Information', 'NORDBOOKING'); ?></h2>
            </div>
        </div>
        <div class="nordbooking-card-content">
            <div class="account-info-grid">
                <div class="info-item">
                    <label><?php esc_html_e('Account Holder:', 'NORDBOOKING'); ?></label>
                    <div class="info-value"><?php echo esc_html($user_name); ?></div>
                </div>
                <div class="info-item">
                    <label><?php esc_html_e('Email:', 'NORDBOOKING'); ?></label>
                    <div class="info-value"><?php echo esc_html($user_email); ?></div>
                </div>
                <div class="info-item">
                    <label><?php esc_html_e('Plan:', 'NORDBOOKING'); ?></label>
                    <div class="info-value"><?php esc_html_e('NORDBOOKING Pro', 'NORDBOOKING'); ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Invoice History -->
    <?php if ($status === 'active' || $status === 'trial'): ?>
    <div class="nordbooking-card card-bs">
        <div class="nordbooking-card-header">
            <div class="nordbooking-card-title-group">
                <div class="nordbooking-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14,2 14,8 20,8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10,9 9,9 8,9"/>
                    </svg>
                </div>
                <h2 class="nordbooking-card-title"><?php esc_html_e('Invoice History', 'NORDBOOKING'); ?></h2>
                <div class="card-actions">
                    <button id="refresh-invoices-btn" class="button button-small" title="Refresh invoices">
                        <span class="refresh-icon">🔄</span>
                        <span class="refresh-text">Refresh</span>
                    </button>
                </div>
            </div>
        </div>
        <div class="nordbooking-card-content">
            <div id="invoice-list-container">
                <div id="invoice-loading" class="invoice-loading">
                    <p><?php esc_html_e('Loading invoices...', 'NORDBOOKING'); ?></p>
                </div>
                <div id="invoice-list" style="display: none;">
                    <!-- Invoices will be loaded here via AJAX -->
                </div>
                <div id="invoice-empty" style="display: none;">
                    <p><?php esc_html_e('No invoices found. Invoices will appear here after your first payment.', 'NORDBOOKING'); ?></p>
                </div>
                <div id="invoice-error" style="display: none;">
                    <p><?php esc_html_e('Unable to load invoices. Please try again later.', 'NORDBOOKING'); ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Subscription Features -->
    <div class="nordbooking-card card-bs">
        <div class="nordbooking-card-header">
            <div class="nordbooking-card-title-group">
                <div class="nordbooking-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 12l2 2 4-4"/>
                        <path d="M21 12c.552 0 1-.448 1-1V5c0-.552-.448-1-1-1H3c-.552 0-1 .448-1 1v6c0 .552.448 1 1 1h18z"/>
                        <path d="M3 12v6c0 .552.448 1 1 1h16c.552 0 1-.448 1-1v-6"/>
                    </svg>
                </div>
                <h2 class="nordbooking-card-title"><?php esc_html_e('Subscription Features', 'NORDBOOKING'); ?></h2>
            </div>
        </div>
        <div class="nordbooking-card-content">
            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 12l2 2 4-4"/>
                            <circle cx="12" cy="12" r="10"/>
                        </svg>
                    </div>
                    <div class="feature-text">
                        <h4><?php esc_html_e('Unlimited Bookings', 'NORDBOOKING'); ?></h4>
                        <p><?php esc_html_e('Accept unlimited bookings from your customers', 'NORDBOOKING'); ?></p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 12l2 2 4-4"/>
                            <circle cx="12" cy="12" r="10"/>
                        </svg>
                    </div>
                    <div class="feature-text">
                        <h4><?php esc_html_e('Advanced Calendar', 'NORDBOOKING'); ?></h4>
                        <p><?php esc_html_e('Full calendar management with availability settings', 'NORDBOOKING'); ?></p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 12l2 2 4-4"/>
                            <circle cx="12" cy="12" r="10"/>
                        </svg>
                    </div>
                    <div class="feature-text">
                        <h4><?php esc_html_e('Payment Processing', 'NORDBOOKING'); ?></h4>
                        <p><?php esc_html_e('Secure payment processing with Stripe integration', 'NORDBOOKING'); ?></p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 12l2 2 4-4"/>
                            <circle cx="12" cy="12" r="10"/>
                        </svg>
                    </div>
                    <div class="feature-text">
                        <h4><?php esc_html_e('Customer Management', 'NORDBOOKING'); ?></h4>
                        <p><?php esc_html_e('Comprehensive customer database and communication', 'NORDBOOKING'); ?></p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 12l2 2 4-4"/>
                            <circle cx="12" cy="12" r="10"/>
                        </svg>
                    </div>
                    <div class="feature-text">
                        <h4><?php esc_html_e('Email Support', 'NORDBOOKING'); ?></h4>
                        <p><?php esc_html_e('Priority email support for all subscribers', 'NORDBOOKING'); ?></p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 12l2 2 4-4"/>
                            <circle cx="12" cy="12" r="10"/>
                        </svg>
                    </div>
                    <div class="feature-text">
                        <h4><?php esc_html_e('Regular Updates', 'NORDBOOKING'); ?></h4>
                        <p><?php esc_html_e('Access to all new features and improvements', 'NORDBOOKING'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.sync-status-indicator {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #0073aa;
    color: white;
    padding: 10px 15px;
    border-radius: 5px;
    z-index: 9999;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.sync-message {
    display: flex;
    align-items: center;
    gap: 8px;
}

.sync-icon {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.card-actions {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-left: auto;
}

.last-updated {
    font-size: 12px;
    color: #666;
}

.countdown-timer {
    font-size: 12px;
    color: #0073aa;
    margin-top: 2px;
}

.auto-refresh-active .auto-refresh-icon {
    color: #46b450;
    animation: spin 2s linear infinite;
}

.subscription-health-indicator {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 5px;
}

.health-healthy { background-color: #46b450; }
.health-warning { background-color: #ffb900; }
.health-critical { background-color: #dc3232; }

.status-badge {
    position: relative;
}

.status-badge::after {
    content: '';
    position: absolute;
    top: -2px;
    right: -2px;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background-color: #46b450;
}

.status-cancelled::after { background-color: #dc3232; }
.status-pending::after { background-color: #ffb900; }

/* Invoice List Styles */
.invoice-loading {
    text-align: center;
    padding: 20px;
    color: #666;
}

.invoice-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.invoice-table th,
.invoice-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.invoice-table th {
    background-color: #f8f9fa;
    font-weight: 600;
    color: #495057;
}

.invoice-table tr:hover {
    background-color: #f8f9fa;
}

.invoice-number {
    font-weight: 600;
    color: #0073aa;
}

.invoice-amount {
    font-weight: 600;
    color: #28a745;
}

.invoice-date {
    color: #666;
    font-size: 14px;
}

.invoice-period {
    color: #666;
    font-size: 12px;
    font-style: italic;
}

.invoice-actions {
    white-space: nowrap;
}

.invoice-download-btn {
    background: #0073aa;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 3px;
    cursor: pointer;
    text-decoration: none;
    font-size: 12px;
    margin-right: 5px;
}

.invoice-download-btn:hover {
    background: #005a87;
    color: white;
}

.invoice-view-btn {
    background: #6c757d;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 3px;
    cursor: pointer;
    text-decoration: none;
    font-size: 12px;
}

.invoice-view-btn:hover {
    background: #545b62;
    color: white;
}

.refresh-icon {
    display: inline-block;
    transition: transform 0.3s ease;
}

.refreshing .refresh-icon {
    animation: spin 1s linear infinite;
}
</style>

<script>
jQuery(document).ready(function($) {
    let autoRefreshInterval = null;
    let isAutoRefreshEnabled = false;
    
    // Initialize countdown timers
    initCountdownTimers();
    
    // Load invoices if user has active subscription
    <?php if ($status === 'active' || $status === 'trial'): ?>
    loadInvoices();
    <?php endif; ?>
    
    // Auto-refresh toggle
    $('#auto-refresh-toggle').on('click', function() {
        isAutoRefreshEnabled = !isAutoRefreshEnabled;
        
        if (isAutoRefreshEnabled) {
            $(this).addClass('auto-refresh-active');
            $('.auto-refresh-text').text('Auto-sync: ON');
            startAutoRefresh();
        } else {
            $(this).removeClass('auto-refresh-active');
            $('.auto-refresh-text').text('Auto-sync: OFF');
            stopAutoRefresh();
        }
    });
    
    // Start auto-refresh
    function startAutoRefresh() {
        autoRefreshInterval = setInterval(function() {
            performStatusCheck(false); // Silent check
        }, 30000); // Every 30 seconds
    }
    
    // Stop auto-refresh
    function stopAutoRefresh() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
        }
    }
    
    // Refresh invoices button
    $('#refresh-invoices-btn').on('click', function() {
        loadInvoices();
    });
    
    // Load invoices function
    function loadInvoices() {
        const button = $('#refresh-invoices-btn');
        button.addClass('refreshing');
        
        $('#invoice-loading').show();
        $('#invoice-list, #invoice-empty, #invoice-error').hide();
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'nordbooking_get_invoices',
                nonce: '<?php echo wp_create_nonce('nordbooking_dashboard_nonce'); ?>'
            },
            success: function(response) {
                if (response.success && response.data.invoices) {
                    displayInvoices(response.data.invoices);
                } else {
                    $('#invoice-loading').hide();
                    $('#invoice-empty').show();
                }
            },
            error: function() {
                $('#invoice-loading').hide();
                $('#invoice-error').show();
            },
            complete: function() {
                button.removeClass('refreshing');
            }
        });
    }
    
    // Display invoices in table
    function displayInvoices(invoices) {
        if (invoices.length === 0) {
            $('#invoice-loading').hide();
            $('#invoice-empty').show();
            return;
        }
        
        let html = '<table class="invoice-table">';
        html += '<thead><tr>';
        html += '<th><?php esc_html_e('Invoice', 'NORDBOOKING'); ?></th>';
        html += '<th><?php esc_html_e('Amount', 'NORDBOOKING'); ?></th>';
        html += '<th><?php esc_html_e('Date', 'NORDBOOKING'); ?></th>';
        html += '<th><?php esc_html_e('Period', 'NORDBOOKING'); ?></th>';
        html += '<th><?php esc_html_e('Actions', 'NORDBOOKING'); ?></th>';
        html += '</tr></thead><tbody>';
        
        invoices.forEach(function(invoice) {
            html += '<tr>';
            html += '<td><span class="invoice-number">' + (invoice.number || invoice.id) + '</span></td>';
            html += '<td><span class="invoice-amount">' + invoice.currency + ' ' + (invoice.amount / 100).toFixed(2) + '</span></td>';
            html += '<td><span class="invoice-date">' + invoice.created_formatted + '</span></td>';
            html += '<td><span class="invoice-period">' + invoice.period_start_formatted + ' - ' + invoice.period_end_formatted + '</span></td>';
            html += '<td class="invoice-actions">';
            html += '<a href="' + invoice.invoice_pdf + '" target="_blank" class="invoice-download-btn" title="<?php esc_attr_e('Download PDF', 'NORDBOOKING'); ?>">';
            html += '📄 <?php esc_html_e('PDF', 'NORDBOOKING'); ?></a>';
            html += '<a href="' + invoice.hosted_invoice_url + '" target="_blank" class="invoice-view-btn" title="<?php esc_attr_e('View Online', 'NORDBOOKING'); ?>">';
            html += '👁 <?php esc_html_e('View', 'NORDBOOKING'); ?></a>';
            html += '</td>';
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        
        $('#invoice-list').html(html);
        $('#invoice-loading').hide();
        $('#invoice-list').show();
    }
    
    // Perform status check
    function performStatusCheck(showIndicator = true) {
        if (showIndicator) {
            showSyncIndicator();
        }
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'nordbooking_subscription_status_check',
                nonce: '<?php echo wp_create_nonce('nordbooking_dashboard_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    updateSubscriptionDisplay(response.data);
                    updateLastUpdatedTime();
                }
                if (showIndicator) {
                    hideSyncIndicator();
                }
            },
            error: function() {
                if (showIndicator) {
                    hideSyncIndicator();
                }
            }
        });
    }
    
    // Show sync indicator
    function showSyncIndicator() {
        $('#sync-status').fadeIn();
    }
    
    // Hide sync indicator
    function hideSyncIndicator() {
        $('#sync-status').fadeOut();
    }
    
    // Update subscription display
    function updateSubscriptionDisplay(data) {
        // Update status badge
        const statusBadge = $('.status-badge');
        statusBadge.removeClass('status-confirmed status-processing status-cancelled status-pending');
        
        switch(data.status) {
            case 'active':
                statusBadge.addClass('status-confirmed').text('<?php esc_html_e('Active', 'NORDBOOKING'); ?>');
                break;
            case 'trial':
                statusBadge.addClass('status-processing').text('<?php esc_html_e('Trial', 'NORDBOOKING'); ?>');
                break;
            case 'expired_trial':
                statusBadge.addClass('status-cancelled').text('<?php esc_html_e('Trial Expired', 'NORDBOOKING'); ?>');
                break;
            case 'expired':
                statusBadge.addClass('status-cancelled').text('<?php esc_html_e('Expired', 'NORDBOOKING'); ?>');
                break;
            default:
                statusBadge.addClass('status-pending').text('<?php esc_html_e('Unsubscribed', 'NORDBOOKING'); ?>');
        }
        
        // Update days left
        if (data.days_left !== undefined) {
            $('.status-value strong').text(data.days_left + ' <?php esc_html_e('days', 'NORDBOOKING'); ?>');
        }
        
        // Update countdown timers
        updateCountdownTimers();
    }
    
    // Update last updated time
    function updateLastUpdatedTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString();
        $('#last-updated').text('Last updated: ' + timeString);
    }
    
    // Initialize countdown timers
    function initCountdownTimers() {
        $('.countdown-timer').each(function() {
            const endDate = $(this).data('end-date');
            if (endDate) {
                updateCountdown($(this), endDate);
            }
        });
    }
    
    // Update countdown timers
    function updateCountdownTimers() {
        $('.countdown-timer').each(function() {
            const endDate = $(this).data('end-date');
            if (endDate) {
                updateCountdown($(this), endDate);
            }
        });
    }
    
    // Update individual countdown
    function updateCountdown($element, endDate) {
        const end = new Date(endDate);
        const now = new Date();
        const diff = end - now;
        
        if (diff > 0) {
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            
            $element.text(`${days}d ${hours}h ${minutes}m remaining`);
        } else {
            $element.text('Expired');
        }
    }
    
    // Update countdown timers every minute
    setInterval(updateCountdownTimers, 60000);
    
    // Subscribe Now Button
    $('#subscribe-now-btn, #upgrade-subscription-btn').on('click', function() {
        var $btn = $(this);
        var originalText = $btn.text();
        $btn.prop('disabled', true).text('<?php esc_html_e('Processing...', 'NORDBOOKING'); ?>');

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'nordbooking_create_checkout_session',
                nonce: '<?php echo wp_create_nonce('nordbooking_dashboard_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.checkout_url;
                } else {
                    alert(response.data.message || '<?php esc_html_e('An error occurred. Please try again.', 'NORDBOOKING'); ?>');
                    $btn.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                alert('<?php esc_html_e('An error occurred. Please try again.', 'NORDBOOKING'); ?>');
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });

    // Manage Billing Button
    $('#manage-billing-btn').on('click', function() {
        var $btn = $(this);
        var originalText = $btn.text();
        $btn.prop('disabled', true).text('<?php esc_html_e('Loading...', 'NORDBOOKING'); ?>');

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'nordbooking_create_customer_portal_session',
                nonce: '<?php echo wp_create_nonce('nordbooking_dashboard_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.portal_url;
                } else {
                    alert(response.data.message || '<?php esc_html_e('An error occurred. Please try again.', 'NORDBOOKING'); ?>');
                    $btn.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                alert('<?php esc_html_e('An error occurred. Please try again.', 'NORDBOOKING'); ?>');
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });

    // Cancel Subscription Button
    $('#cancel-subscription-btn').on('click', function() {
        if (confirm('<?php esc_html_e('Are you sure you want to cancel your subscription? You will continue to have access until the end of your billing period.', 'NORDBOOKING'); ?>')) {
            var $btn = $(this);
            var originalText = $btn.text();
            $btn.prop('disabled', true).text('<?php esc_html_e('Processing...', 'NORDBOOKING'); ?>');

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'nordbooking_cancel_subscription',
                    nonce: '<?php echo wp_create_nonce('nordbooking_dashboard_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        performStatusCheck(true); // Refresh status instead of full reload
                        alert(response.data.message);
                    } else {
                        alert(response.data.message || '<?php esc_html_e('An error occurred. Please try again.', 'NORDBOOKING'); ?>');
                        $btn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    alert('<?php esc_html_e('An error occurred. Please try again.', 'NORDBOOKING'); ?>');
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        }
    });

    // Refresh Status Button
    $('#refresh-status-btn').on('click', function() {
        performStatusCheck(true);
    });
    
    // Real-time sync button
    $('#real-time-sync-btn').on('click', function() {
        var $btn = $(this);
        var originalText = $btn.text();
        $btn.prop('disabled', true).text('<?php esc_html_e('Syncing...', 'NORDBOOKING'); ?>');
        
        showSyncIndicator();

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'nordbooking_real_time_sync',
                nonce: '<?php echo wp_create_nonce('nordbooking_dashboard_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    updateSubscriptionDisplay(response.data);
                    updateLastUpdatedTime();
                    alert('<?php esc_html_e('Subscription synced successfully!', 'NORDBOOKING'); ?>');
                } else {
                    alert(response.data.message || '<?php esc_html_e('Sync failed. Please try again.', 'NORDBOOKING'); ?>');
                }
                $btn.prop('disabled', false).text(originalText);
                hideSyncIndicator();
            },
            error: function() {
                alert('<?php esc_html_e('An error occurred. Please try again.', 'NORDBOOKING'); ?>');
                $btn.prop('disabled', false).text(originalText);
                hideSyncIndicator();
            }
        });
    });
    
    // Check for updates when page becomes visible
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden && isAutoRefreshEnabled) {
            performStatusCheck(false);
        }
    });
});
</script>