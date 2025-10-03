<?php
/**
 * Enhanced Subscription Management Page
 * @package NORDBOOKING
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$user_id = get_current_user_id();

// Get subscription data
$subscription = null;
$status = 'unsubscribed';
$days_left = 0;

if (class_exists('\NORDBOOKING\Classes\Subscription')) {
    $subscription = \NORDBOOKING\Classes\Subscription::get_subscription($user_id);
    $status = \NORDBOOKING\Classes\Subscription::get_subscription_status($user_id);
    $days_left = \NORDBOOKING\Classes\Subscription::get_days_until_next_payment($user_id);
}

// Check if Stripe is configured
$stripe_configured = \NORDBOOKING\Classes\StripeConfig::is_configured();

// Get user info
$user = get_userdata($user_id);
$user_name = $user ? $user->display_name : '';

// Get pricing information
$pricing_info = null;
if ($stripe_configured) {
    $pricing_info = \NORDBOOKING\Classes\Subscription::get_pricing_info();
}

// Handle success/error messages from Stripe redirects
$message = '';
$message_type = '';

if (isset($_GET['success']) && $_GET['success'] == '1') {
    // Sync subscription status after successful payment
    if (class_exists('NORDBOOKING\Classes\Subscription')) {
        \NORDBOOKING\Classes\Subscription::sync_subscription_status($user_id);
        // Refresh status after sync
        $status = \NORDBOOKING\Classes\Subscription::get_subscription_status($user_id);
        
        // Handle subscription activation and welcome email
        if ($status === 'active') {
            nordbooking_handle_subscription_activation($user_id);
        }
    }
    
    if ($status === 'active') {
        $message = __('Congratulations! Your plan subscription is active. A welcome email has been sent to you.', 'NORDBOOKING');
    } else {
        $message = __('Payment processed successfully! Your subscription is being activated.', 'NORDBOOKING');
    }
    $message_type = 'success';
} elseif (isset($_GET['cancelled']) && $_GET['cancelled'] == '1') {
    $message = __('Subscription setup was cancelled. You can try again anytime.', 'NORDBOOKING');
    $message_type = 'info';
} elseif (isset($_GET['expired']) && $_GET['expired'] == '1') {
    $message = __('Your 7-day free trial has expired. Subscribe to the Pro plan to continue using Nord Booking.', 'NORDBOOKING');
    $message_type = 'warning';
}

// Handle cancellation success message
if (isset($_GET['cancelled_subscription']) && $_GET['cancelled_subscription'] == '1') {
    $message = __('Your subscription has been cancelled successfully. You will continue to have access until the end of your billing period.', 'NORDBOOKING');
    $message_type = 'info';
}

// Determine status display
$status_display = '';
$status_class = '';
$time_remaining = '';

switch($status) {
    case 'trial':
        $status_display = __('Free Trial', 'NORDBOOKING');
        $status_class = 'status-trial';
        $time_remaining = sprintf(_n('%d day remaining', '%d days remaining', $days_left, 'NORDBOOKING'), $days_left);
        break;
    case 'active':
        $status_display = __('Pro Plan', 'NORDBOOKING');
        $status_class = 'status-active';
        $time_remaining = sprintf(_n('%d day until renewal', '%d days until renewal', $days_left, 'NORDBOOKING'), $days_left);
        break;
    case 'expired_trial':
        $status_display = __('Trial Expired', 'NORDBOOKING');
        $status_class = 'status-expired';
        $time_remaining = __('Subscribe to continue', 'NORDBOOKING');
        break;
    case 'cancelled':
        $status_display = __('Cancelled', 'NORDBOOKING');
        $status_class = 'status-cancelled';
        $time_remaining = $days_left > 0 ? sprintf(_n('%d day remaining', '%d days remaining', $days_left, 'NORDBOOKING'), $days_left) : __('Access ended', 'NORDBOOKING');
        break;
    case 'expired':
        $status_display = __('Expired', 'NORDBOOKING');
        $status_class = 'status-expired';
        $time_remaining = __('Renew to continue', 'NORDBOOKING');
        break;
    default:
        $status_display = __('No Subscription', 'NORDBOOKING');
        $status_class = 'status-none';
        $time_remaining = __('Start your free trial', 'NORDBOOKING');
        break;
}

// Determine which buttons to show
$show_subscribe = in_array($status, ['unsubscribed', 'expired_trial', 'expired', 'trial']);
$show_cancel = in_array($status, ['active', 'trial']); // Allow cancel for both active and trial users
$show_manage_billing = in_array($status, ['active', 'cancelled']);
$show_invoices = in_array($status, ['active']); // Only show invoices for active Pro subscribers
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
            <h1><?php esc_html_e('Subscription', 'NORDBOOKING'); ?></h1>
            <p class="dashboard-subtitle"><?php esc_html_e('Manage your subscription and billing', 'NORDBOOKING'); ?></p>
        </div>
    </div>
</div>

<?php if ($message): ?>
<div class="nordbooking-alert nordbooking-alert-<?php echo esc_attr($message_type); ?>">
    <div class="alert-icon">
        <?php
        $icons = [
            'success' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
            'error' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
            'warning' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
            'info' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>'
        ];
        echo $icons[$message_type] ?? $icons['info'];
        ?>
    </div>
    <div class="alert-content">
        <div class="alert-title">
            <?php
            $titles = [
                'success' => __('Success!', 'NORDBOOKING'),
                'error' => __('Error', 'NORDBOOKING'),
                'warning' => __('Warning', 'NORDBOOKING'),
                'info' => __('Information', 'NORDBOOKING')
            ];
            echo esc_html($titles[$message_type] ?? $titles['info']);
            ?>
        </div>
        <div class="alert-description">
            <?php echo esc_html($message); ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php 
// Show special message for trial users who came from Pro plan selection
if ($status === 'trial' && isset($_GET['plan']) && $_GET['plan'] === 'pro'): ?>
<div class="nordbooking-alert nordbooking-alert-info">
    <div class="alert-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
        </svg>
    </div>
    <div class="alert-content">
        <div class="alert-title"><?php esc_html_e('Free Trial Active', 'NORDBOOKING'); ?></div>
        <div class="alert-description">
            <?php esc_html_e('You have a 7-day free trial with full Pro access. You can upgrade to the Pro plan now to continue after your trial ends.', 'NORDBOOKING'); ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!$stripe_configured): ?>
<div class="nordbooking-alert nordbooking-alert-error">
    <div class="alert-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="15" y1="9" x2="9" y2="15"></line>
            <line x1="9" y1="9" x2="15" y2="15"></line>
        </svg>
    </div>
    <div class="alert-content">
        <div class="alert-title"><?php esc_html_e('Configuration Error', 'NORDBOOKING'); ?></div>
        <div class="alert-description">
            <?php esc_html_e('Subscription system is not configured. Please contact support.', 'NORDBOOKING'); ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="subscription-page-layout">
    <!-- Main Content Area -->
    <div class="subscription-main-content">
        <!-- Current Status Card -->
        <div class="nordbooking-card status-card">
            <div class="nordbooking-card-header">
                <h2 class="nordbooking-card-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem;">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M9 12l2 2 4-4"/>
                    </svg>
                    <?php esc_html_e('Current Plan Status', 'NORDBOOKING'); ?>
                </h2>
            </div>
            <div class="nordbooking-card-content">
                <div class="subscription-status-display">
                    <div class="status-main">
                        <div class="status-badge <?php echo esc_attr($status_class); ?>">
                            <?php echo esc_html($status_display); ?>
                        </div>
                        <div class="status-time">
                            <?php echo esc_html($time_remaining); ?>
                        </div>
                        <?php if ($status === 'cancelled'): ?>
                            <div class="status-note">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="12" y1="8" x2="12" y2="12"/>
                                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                                </svg>
                                <?php esc_html_e('Your subscription has been cancelled but remains active until the end of your billing period.', 'NORDBOOKING'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($pricing_info && in_array($status, ['active', 'trial', 'cancelled'])): ?>
                    <div class="status-price">
                        <span class="price-amount">$<?php echo esc_html($pricing_info['amount'] / 100); ?></span>
                        <span class="price-period">/month</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    <!-- Trial Benefits Section (Only for trial users) -->
    <?php if ($status === 'trial'): ?>
    <div class="nordbooking-card trial-benefits-card">
        <div class="nordbooking-card-header">
            <h2 class="nordbooking-card-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem; color: #3b82f6;">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
                <?php esc_html_e('Continue with Pro Plan', 'NORDBOOKING'); ?>
            </h2>
        </div>
        <div class="nordbooking-card-content">
            <p style="margin-bottom: 1rem; color: #6b7280;">
                <?php esc_html_e('You\'re currently enjoying all Pro features during your free trial. Upgrade now to continue seamless access after your trial ends.', 'NORDBOOKING'); ?>
            </p>
            
            <div class="trial-pricing-display">
                <div class="trial-price">
                    <span class="price-currency">$</span>
                    <span class="price-amount">89</span>
                    <span class="price-period">/month</span>
                </div>
                <div class="trial-features">
                    <div class="feature-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                        <?php esc_html_e('Unlimited bookings', 'NORDBOOKING'); ?>
                    </div>
                    <div class="feature-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                        <?php esc_html_e('Advanced calendar management', 'NORDBOOKING'); ?>
                    </div>
                    <div class="feature-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                        <?php esc_html_e('Payment processing', 'NORDBOOKING'); ?>
                    </div>
                    <div class="feature-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                        <?php esc_html_e('Priority support', 'NORDBOOKING'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Expired Trial Section -->
    <?php if ($status === 'expired_trial'): ?>
    <div class="nordbooking-card expired-trial-card">
        <div class="nordbooking-card-header">
            <h2 class="nordbooking-card-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem; color: #ef4444;">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="15" y1="9" x2="9" y2="15"/>
                    <line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
                <?php esc_html_e('Trial Expired - Upgrade Required', 'NORDBOOKING'); ?>
            </h2>
        </div>
        <div class="nordbooking-card-content">
            <div class="expired-trial-notice">
                <p><strong><?php esc_html_e('Your free trial has expired.', 'NORDBOOKING'); ?></strong></p>
                <p><?php esc_html_e('You cannot access the Dashboard or registration form until you upgrade to the Pro Plan.', 'NORDBOOKING'); ?></p>
                <p><?php esc_html_e('All your data is safe and will be restored immediately when you subscribe.', 'NORDBOOKING'); ?></p>
            </div>
            
            <div class="expired-trial-pricing">
                <div class="trial-price">
                    <span class="price-currency">$</span>
                    <span class="price-amount">89</span>
                    <span class="price-period">/month</span>
                </div>
                <div class="expired-benefits">
                    <h4><?php esc_html_e('Restore Full Access:', 'NORDBOOKING'); ?></h4>
                    <ul>
                        <li>✓ <?php esc_html_e('Unlimited bookings', 'NORDBOOKING'); ?></li>
                        <li>✓ <?php esc_html_e('Advanced calendar management', 'NORDBOOKING'); ?></li>
                        <li>✓ <?php esc_html_e('Payment processing', 'NORDBOOKING'); ?></li>
                        <li>✓ <?php esc_html_e('Customer portal', 'NORDBOOKING'); ?></li>
                        <li>✓ <?php esc_html_e('Priority support', 'NORDBOOKING'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>



        <!-- Billing History Section (Always visible for Pro subscribers) -->
        <?php if ($show_invoices): ?>
        <div id="invoices-section" class="nordbooking-card invoices-card">
            <div class="nordbooking-card-header">
                <h2 class="nordbooking-card-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem;">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14,2 14,8 20,8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                    <?php esc_html_e('Billing History', 'NORDBOOKING'); ?>
                </h2>
            </div>
            <div class="nordbooking-card-content">
                <div id="invoices-list">
                    <div class="loading-spinner">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 6v6l4 2"/>
                        </svg>
                        <span><?php esc_html_e('Loading invoices...', 'NORDBOOKING'); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Actions Sidebar -->
    <div class="subscription-sidebar">
        <div class="sidebar-card">
            <div class="sidebar-header">
                <h3><?php esc_html_e('Quick Actions', 'NORDBOOKING'); ?></h3>
                <p><?php esc_html_e('Manage your subscription and billing', 'NORDBOOKING'); ?></p>
            </div>
            
            <div class="sidebar-actions">
                <?php if ($show_subscribe && $stripe_configured): ?>
                    <button id="subscribe-btn" class="sidebar-btn primary<?php echo ($status === 'trial') ? ' trial-upgrade' : ''; ?>">
                        <div class="btn-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 12l2 2 4-4"/>
                                <circle cx="12" cy="12" r="10"/>
                            </svg>
                        </div>
                        <div class="btn-content">
                            <span class="btn-title">
                                <?php 
                                if ($status === 'trial') {
                                    esc_html_e('Upgrade to Pro', 'NORDBOOKING');
                                } else {
                                    esc_html_e('Subscribe to Pro', 'NORDBOOKING');
                                }
                                ?>
                            </span>
                            <span class="btn-subtitle">
                                <?php 
                                if ($status === 'trial') {
                                    esc_html_e('Continue after trial ends', 'NORDBOOKING');
                                } else {
                                    esc_html_e('Start your Pro subscription', 'NORDBOOKING');
                                }
                                ?>
                            </span>
                        </div>
                    </button>
                <?php endif; ?>

                <?php if ($show_cancel): ?>
                    <button id="cancel-subscription-btn" class="sidebar-btn secondary">
                        <div class="btn-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="15" y1="9" x2="9" y2="15"/>
                                <line x1="9" y1="9" x2="15" y2="15"/>
                            </svg>
                        </div>
                        <div class="btn-content">
                            <span class="btn-title">
                                <?php 
                                if ($status === 'trial') {
                                    esc_html_e('Cancel Trial', 'NORDBOOKING');
                                } else {
                                    esc_html_e('Cancel Subscription', 'NORDBOOKING');
                                }
                                ?>
                            </span>
                            <span class="btn-subtitle">
                                <?php 
                                if ($status === 'trial') {
                                    esc_html_e('End trial immediately', 'NORDBOOKING');
                                } else {
                                    esc_html_e('Cancel at period end', 'NORDBOOKING');
                                }
                                ?>
                            </span>
                        </div>
                    </button>
                <?php endif; ?>

                <?php if ($show_manage_billing && $stripe_configured): ?>
                    <button id="manage-billing-btn" class="sidebar-btn secondary">
                        <div class="btn-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2v20m9-9H3"/>
                            </svg>
                        </div>
                        <div class="btn-content">
                            <span class="btn-title"><?php esc_html_e('Manage Billing', 'NORDBOOKING'); ?></span>
                            <span class="btn-subtitle"><?php esc_html_e('Update payment methods', 'NORDBOOKING'); ?></span>
                        </div>
                    </button>
                <?php endif; ?>


            </div>

            <div class="pricing-info">
                <div class="price-display">
                    <span class="price">$89</span>
                    <span class="period">/month</span>
                </div>
                <p class="price-note"><?php esc_html_e('Simple, transparent pricing with no hidden fees', 'NORDBOOKING'); ?></p>
            </div>
        </div>
    </div>
</div>

<style>
.subscription-page-layout {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 1.5rem;
    max-width: 1100px;
    margin: 0 auto;
}

.subscription-main-content {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.subscription-sidebar {
    position: sticky;
    top: 2rem;
    height: fit-content;
}

.sidebar-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1.25rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.sidebar-header {
    margin-bottom: 1.25rem;
    text-align: center;
}

.sidebar-header h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #111827;
    margin: 0 0 0.5rem 0;
}

.sidebar-header p {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0;
}

.sidebar-actions {
    display: flex;
    flex-direction: column;
    gap: 0.625rem;
    margin-bottom: 1.25rem;
}

.sidebar-btn {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 30px 15px;
    border: 2px solid transparent;
    border-radius: 0.5rem;
    background: white;
    text-align: left;
    cursor: pointer;
    transition: all 0.2s ease;
    width: 100%;
    font-size: 0.875rem;
}

.sidebar-btn.primary {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    border-color: #3b82f6;
}

/* .sidebar-btn.primary.trial-upgrade {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border-color: #10b981;
    animation: pulse-glow 2s infinite;
} */

.sidebar-btn.secondary {
    background: #f9fafb;
    border-color: #e5e7eb;
    color: #374151;
}

/* .sidebar-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
} */

.sidebar-btn.primary:hover {
    background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
}

.sidebar-btn.secondary:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
}

.btn-icon {
    flex-shrink: 0;
}

.btn-content {
    flex: 1;
}

.btn-title {
    display: block;
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 0.1rem;
}

.btn-subtitle {
    display: block;
    font-size: 0.75rem;
    opacity: 0.8;
}

.pricing-info {
    text-align: center;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 0.5rem;
    border: 1px solid #e2e8f0;
}

.price-display {
    margin-bottom: 0.5rem;
}

.price {
    font-size: 2rem;
    font-weight: 800;
    color: #111827;
}

.period {
    font-size: 1rem;
    color: #6b7280;
    margin-left: 0.25rem;
}

.price-note {
    font-size: 0.75rem;
    color: #6b7280;
    margin: 0;
}

.status-note {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.75rem;
    padding: 0.75rem;
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.2);
    border-radius: 0.375rem;
    font-size: 0.875rem;
    color: #1e40af;
}

.invoices-card {
    margin-top: 1rem;
}

.invoices-table {
    overflow-x: auto;
}

.invoices-table table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.invoices-table th {
    background: #f9fafb;
    font-weight: 600;
    color: #374151;
    text-align: left;
    padding: 0.75rem;
    border-bottom: 2px solid #e5e7eb;
}

.invoices-table td {
    padding: 0.75rem;
    border-bottom: 1px solid #f3f4f6;
    color: #374151;
}

.invoices-table tr:hover {
    background: #f9fafb;
}

.status-badge.status-paid {
    background: rgba(34, 197, 94, 0.1);
    color: #16a34a;
}

.invoice-download-link {
    color: #3b82f6;
    text-decoration: none;
    font-weight: 500;
}

.invoice-download-link:hover {
    color: #1d4ed8;
    text-decoration: underline;
}

@media (max-width: 1024px) {
    .subscription-page-layout {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .subscription-sidebar {
        position: static;
        order: -1;
    }
    
    .sidebar-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 0.75rem;
    }
}

@media (max-width: 640px) {
    .features-grid {
        grid-template-columns: 1fr !important;
    }
    
    .sidebar-actions {
        grid-template-columns: 1fr !important;
    }
    
    .subscription-status-display {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .status-price {
        text-align: left;
    }
}

.subscription-status-display {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
}

.status-main {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.status-badge.status-none {
    background-color: hsl(215.4 16.3% 46.9% / 0.1);
    color: hsl(215.4 16.3% 46.9%);
}

.status-time {
    font-size: 0.875rem;
    color: hsl(215.4 16.3% 46.9%);
    font-weight: 500;
}

.status-price {
    text-align: right;
}

.price-amount {
    font-size: 2rem;
    font-weight: 700;
    color: hsl(222.2 84% 4.9%);
}

.price-period {
    font-size: 1rem;
    color: hsl(215.4 16.3% 46.9%);
}

.subscription-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.button-large {
    padding: 0.75rem 2rem;
    font-size: 1rem;
}

.button svg {
    margin-right: 0.5rem;
}

.loading-spinner {
    text-align: center;
    padding: 2rem;
    color: hsl(215.4 16.3% 46.9%);
}

/* Modern Alert System */
.nordbooking-alert {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 1rem;
    border-radius: 0.5rem;
    border: 1px solid;
    margin-bottom: 1.5rem;
    font-size: 0.875rem;
    line-height: 1.5;
}

.nordbooking-alert .alert-icon {
    flex-shrink: 0;
    margin-top: 0.125rem;
}

.nordbooking-alert .alert-content {
    flex: 1;
    min-width: 0;
}

.nordbooking-alert .alert-title {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.nordbooking-alert .alert-description {
    opacity: 0.9;
}

.nordbooking-alert-success {
    background-color: hsl(142 76% 36% / 0.1);
    border-color: hsl(142 76% 36% / 0.3);
    color: hsl(142 76% 36%);
}

.nordbooking-alert-error {
    background-color: hsl(0 84.2% 60.2% / 0.1);
    border-color: hsl(0 84.2% 60.2% / 0.3);
    color: hsl(0 84.2% 60.2%);
}

.nordbooking-alert-warning {
    background-color: hsl(45 93% 47% / 0.1);
    border-color: hsl(45 93% 47% / 0.3);
    color: hsl(45 93% 47%);
}

.nordbooking-alert-info {
    background-color: hsl(221.2 83.2% 53.3% / 0.1);
    border-color: hsl(221.2 83.2% 53.3% / 0.3);
    color: hsl(221.2 83.2% 53.3%);
}

.trial-upgrade-btn {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%) !important;
    border: 2px solid #3b82f6 !important;
    box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3) !important;
    animation: pulse-glow 2s infinite;
    position: relative;
    overflow: hidden;
}

.trial-upgrade-btn:hover {
    background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%) !important;
    transform: translateY(-2px);
    box-shadow: 0 8px 12px rgba(59, 130, 246, 0.4) !important;
}

.trial-upgrade-btn::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    transform: rotate(45deg);
    animation: shine 3s infinite;
}

@keyframes pulse-glow {
    0%, 100% {
        box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3);
    }
    50% {
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.5);
    }
}

@keyframes shine {
    0% {
        transform: translateX(-100%) translateY(-100%) rotate(45deg);
    }
    50% {
        transform: translateX(100%) translateY(100%) rotate(45deg);
    }
    100% {
        transform: translateX(100%) translateY(100%) rotate(45deg);
    }
}


.trial-pricing-display {
    display: flex;
    align-items: center;
    gap: 2rem;
    padding: 1rem;
    background: white;
    border-radius: 0.5rem;
    border: 1px solid #e5e7eb;
}

.trial-price {
    display: flex;
    align-items: baseline;
    gap: 0.25rem;
}

.trial-price .price-currency {
    font-size: 1.25rem;
    font-weight: 600;
    color: #374151;
}

.trial-price .price-amount {
    font-size: 2.5rem;
    font-weight: 800;
    color: #111827;
}

.trial-price .price-period {
    font-size: 1rem;
    color: #6b7280;
}

.trial-features {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.5rem;
    flex: 1;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #374151;
}

.feature-item svg {
    color: #10b981;
    flex-shrink: 0;
}

@media (max-width: 768px) {
    .trial-pricing-display {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .trial-features {
        grid-template-columns: 1fr;
        width: 100%;
    }
}


.expired-trial-notice {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.expired-trial-notice p {
    margin: 0.5rem 0;
    color: #dc2626;
}

.expired-trial-pricing {
    display: flex;
    align-items: center;
    gap: 2rem;
    padding: 1rem;
    background: white;
    border-radius: 0.5rem;
    border: 1px solid #e5e7eb;
}

.expired-benefits h4 {
    margin: 0 0 0.5rem 0;
    color: #374151;
    font-size: 1rem;
}

.expired-benefits ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.expired-benefits li {
    padding: 0.25rem 0;
    color: #374151;
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .expired-trial-pricing {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
}

@media (max-width: 640px) {
    .subscription-status-display {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .status-price {
        text-align: left;
    }
    
    .subscription-actions {
        flex-direction: column;
    }
    
    .subscription-actions .button {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
// Localize nonces and AJAX URL
window.nordbookingSubscription = {
    ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
    nonce: '<?php echo wp_create_nonce('nordbooking_dashboard_nonce'); ?>',
    userId: <?php echo $user_id; ?>
};

// Debug: Log the nonce for verification
console.log('NORDBOOKING Debug: Nonce generated:', window.nordbookingSubscription.nonce);
console.log('NORDBOOKING Debug: Current subscription status:', '<?php echo esc_js($status); ?>');
console.log('NORDBOOKING Debug: Show subscribe button:', <?php echo $show_subscribe ? 'true' : 'false'; ?>);
console.log('NORDBOOKING Debug: Show cancel button:', <?php echo $show_cancel ? 'true' : 'false'; ?>);

document.addEventListener('DOMContentLoaded', function() {
    const subscribeBtn = document.getElementById('subscribe-btn');
    const cancelBtn = document.getElementById('cancel-subscription-btn');
    const manageBillingBtn = document.getElementById('manage-billing-btn');
    const invoicesSection = document.getElementById('invoices-section');

    // Subscribe button
    if (subscribeBtn) {
        subscribeBtn.addEventListener('click', function() {
            this.disabled = true;
            this.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg> Processing...';
            
            // Create Stripe checkout session
            fetch(window.nordbookingSubscription.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'nordbooking_create_checkout_session',
                    nonce: window.nordbookingSubscription.nonce,
                    user_id: window.nordbookingSubscription.userId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.checkout_url) {
                    window.location.href = data.data.checkout_url;
                } else {
                    console.error('Checkout session error:', data);
                    new NordbookingDialog({
                        title: 'Error',
                        content: 'Error creating checkout session: ' + (data.data?.message || 'Unknown error'),
                        icon: 'error',
                        buttons: [{
                            label: 'OK',
                            class: 'primary',
                            onClick: (dialog) => dialog.close()
                        }]
                    }).show();
                    this.disabled = false;
                    const buttonText = this.classList.contains('trial-upgrade-btn') ? 'Upgrade to Pro Plan' : 'Subscribe to Pro Plan';
                    this.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg> ' + buttonText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                new NordbookingDialog({
                    title: 'Network Error',
                    content: 'Network error occurred. Please try again.',
                    icon: 'error',
                    buttons: [{
                        label: 'OK',
                        class: 'primary',
                        onClick: (dialog) => dialog.close()
                    }]
                }).show();
                this.disabled = false;
                const buttonText = this.classList.contains('trial-upgrade-btn') ? 'Upgrade to Pro Plan' : 'Subscribe to Pro Plan';
                this.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg> ' + buttonText;
            });
        });
    }

    // Cancel subscription button
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            const currentStatus = '<?php echo esc_js($status); ?>';
            const isTrial = currentStatus === 'trial';
            
            const confirmTitle = isTrial ? 'Cancel Free Trial' : 'Cancel Subscription';
            const confirmMessage = isTrial 
                ? 'Are you sure you want to cancel your free trial? You will lose access immediately and can reactivate by subscribing to the Pro plan.'
                : 'Are you sure you want to cancel your subscription? You will continue to have access until the end of your current billing period.';
            
            new NordbookingDialog({
                title: confirmTitle,
                content: confirmMessage,
                icon: 'warning',
                buttons: [
                    {
                        label: 'Cancel',
                        class: 'secondary',
                        onClick: (dialog) => dialog.close()
                    },
                    {
                        label: 'Confirm',
                        class: 'primary',
                        onClick: (dialog) => {
                            dialog.close();
                            // Execute the cancellation logic
                            this.disabled = true;
                            this.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg> Cancelling...';
                            
                            const action = isTrial ? 'nordbooking_cancel_trial' : 'nordbooking_cancel_subscription';
                            
                            fetch(window.nordbookingSubscription.ajaxUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: new URLSearchParams({
                                    action: action,
                                    nonce: window.nordbookingSubscription.nonce,
                                    user_id: window.nordbookingSubscription.userId
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Show success dialog
                                    const successMessage = data.data.message || 'Cancelled successfully.';
                                    new NordbookingDialog({
                                        title: 'Success',
                                        content: successMessage,
                                        icon: 'success',
                                        buttons: [{
                                            label: 'OK',
                                            class: 'primary',
                                            onClick: (dialog) => {
                                                dialog.close();
                                                if (data.data.redirect_url) {
                                                    window.location.href = data.data.redirect_url;
                                                } else {
                                                    // Add cancelled parameter to URL for proper messaging
                                                    const url = new URL(window.location);
                                                    url.searchParams.set('cancelled_subscription', '1');
                                                    window.location.href = url.toString();
                                                }
                                            }
                                        }]
                                    }).show();
                                } else {
                                    console.error('Cancel error:', data);
                                    new NordbookingDialog({
                                        title: 'Error',
                                        content: 'Error cancelling: ' + (data.data?.message || 'Unknown error'),
                                        icon: 'error',
                                        buttons: [{
                                            label: 'OK',
                                            class: 'primary',
                                            onClick: (dialog) => dialog.close()
                                        }]
                                    }).show();
                                    this.disabled = false;
                                    const buttonText = isTrial ? 'Cancel Trial' : 'Cancel Subscription';
                                    this.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg> ' + buttonText;
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                new NordbookingDialog({
                                    title: 'Network Error',
                                    content: 'Network error occurred. Please try again.',
                                    icon: 'error',
                                    buttons: [{
                                        label: 'OK',
                                        class: 'primary',
                                        onClick: (dialog) => dialog.close()
                                    }]
                                }).show();
                                this.disabled = false;
                                const buttonText = isTrial ? 'Cancel Trial' : 'Cancel Subscription';
                                this.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg> ' + buttonText;
                            });
                        }
                    }
                ]
            }).show();
        });
    }

    // Manage billing button
    if (manageBillingBtn) {
        manageBillingBtn.addEventListener('click', function() {
            this.disabled = true;
            this.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg> Loading...';
            
            fetch(window.nordbookingSubscription.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'nordbooking_create_billing_portal_session',
                    nonce: window.nordbookingSubscription.nonce,
                    user_id: window.nordbookingSubscription.userId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.portal_url) {
                    window.open(data.data.portal_url, '_blank');
                } else {
                    console.error('Billing portal error:', data);
                    const errorMsg = data.data?.message || 'Unknown error';
                    if (errorMsg.includes('configuration')) {
                        new NordbookingDialog({
                            title: 'Billing Portal Not Configured',
                            content: `
                                <p>The billing portal is not configured in your Stripe dashboard.</p>
                                <p><strong>To fix this:</strong></p>
                                <ol style="text-align: left; margin: 1rem 0;">
                                    <li>Go to your <a href="https://dashboard.stripe.com/settings/billing/portal" target="_blank" style="color: #3b82f6;">Stripe Dashboard</a></li>
                                    <li>Navigate to Settings → Billing → Customer Portal</li>
                                    <li>Click "Activate" or configure your portal settings</li>
                                    <li>Save the configuration</li>
                                </ol>
                                <p>Contact support if you need assistance.</p>
                            `,
                            icon: 'warning',
                            buttons: [{
                                label: 'OK',
                                class: 'primary',
                                onClick: (dialog) => dialog.close()
                            }]
                        }).show();
                    } else {
                        new NordbookingDialog({
                            title: 'Billing Portal Error',
                            content: 'Error accessing billing portal: ' + errorMsg,
                            icon: 'error',
                            buttons: [{
                                label: 'OK',
                                class: 'primary',
                                onClick: (dialog) => dialog.close()
                            }]
                        }).show();
                    }
                }
                this.disabled = false;
                this.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20m9-9H3"/></svg> Manage Billing';
            })
            .catch(error => {
                console.error('Error:', error);
                new NordbookingDialog({
                    title: 'Network Error',
                    content: 'Network error occurred. Please try again.',
                    icon: 'error',
                    buttons: [{
                        label: 'OK',
                        class: 'primary',
                        onClick: (dialog) => dialog.close()
                    }]
                }).show();
                this.disabled = false;
                this.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20m9-9H3"/></svg> Manage Billing';
            });
        });
    }

    // Auto-load invoices when page loads for Pro users
    if (invoicesSection) {
        loadInvoices();
    }

    function loadInvoices() {
        const invoicesList = document.getElementById('invoices-list');
        
        fetch(window.nordbookingSubscription.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'nordbooking_get_invoices',
                nonce: window.nordbookingSubscription.nonce,
                user_id: window.nordbookingSubscription.userId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.invoices) {
                const invoices = data.data.invoices;
                if (invoices.length === 0) {
                    invoicesList.innerHTML = '<p style="text-align: center; color: #6b7280; padding: 2rem;">No invoices found.</p>';
                } else {
                    let invoicesHtml = '<div class="invoices-table"><table style="width: 100%; border-collapse: collapse;"><thead><tr style="border-bottom: 1px solid #e5e7eb;"><th style="text-align: left; padding: 0.75rem;">Date</th><th style="text-align: left; padding: 0.75rem;">Amount</th><th style="text-align: left; padding: 0.75rem;">Status</th><th style="text-align: left; padding: 0.75rem;">Invoice</th></tr></thead><tbody>';
                    
                    invoices.forEach(invoice => {
                        const date = new Date(invoice.created * 1000).toLocaleDateString();
                        
                        // Safely calculate amount with fallbacks
                        let amountValue = 0;
                        if (invoice.amount_paid && !isNaN(invoice.amount_paid)) {
                            amountValue = parseFloat(invoice.amount_paid) / 100;
                        } else if (invoice.total && !isNaN(invoice.total)) {
                            amountValue = parseFloat(invoice.total) / 100;
                        } else if (invoice.amount && !isNaN(invoice.amount)) {
                            amountValue = parseFloat(invoice.amount) / 100;
                        }
                        const amount = '$' + amountValue.toFixed(2);
                        
                        const status = invoice.status.charAt(0).toUpperCase() + invoice.status.slice(1);
                        const downloadUrl = invoice.invoice_pdf || '#';
                        
                        invoicesHtml += `<tr><td style="padding: 0.75rem;">${date}</td><td style="padding: 0.75rem;"><strong>${amount}</strong></td><td style="padding: 0.75rem;"><span class="status-badge status-${invoice.status || 'unknown'}">${status}</span></td><td style="padding: 0.75rem;">${downloadUrl !== '#' ? `<a href="${downloadUrl}" target="_blank" class="invoice-download-link">Download</a>` : '<span style="color: #9ca3af;">Not available</span>'}</td></tr>`;
                    });
                    
                    invoicesHtml += '</tbody></table></div>';
                    invoicesList.innerHTML = invoicesHtml;
                }
            } else {
                invoicesList.innerHTML = '<p style="text-align: center; color: #ef4444; padding: 2rem;">Error loading invoices: ' + (data.data.message || 'Unknown error') + '</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            invoicesList.innerHTML = '<p style="text-align: center; color: #ef4444; padding: 2rem;">Network error occurred while loading invoices.</p>';
        });
    }
});

// Using NordbookingDialog system - no custom dialog code needed
</script>