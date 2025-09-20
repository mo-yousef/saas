<?php
/**
 * Subscription Management Page
 * @package NORDBOOKING
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$user_id = get_current_user_id();
$subscription = \NORDBOOKING\Classes\Subscription::get_subscription($user_id);
$status = \NORDBOOKING\Classes\Subscription::get_subscription_status($user_id);
$days_left = \NORDBOOKING\Classes\Subscription::get_days_until_next_payment($user_id);

// Get user info for display
$user = get_userdata($user_id);
$user_email = $user ? $user->user_email : '';
$user_name = $user ? $user->display_name : '';
?>

<?php
// Handle success/error messages from Stripe redirects
$message = '';
$message_type = '';

if (isset($_GET['success']) && $_GET['success'] == '1') {
    $message = __('Subscription activated successfully! Welcome to NORDBOOKING Pro.', 'NORDBOOKING');
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

<div class="subscription-management-wrapper">
    <!-- Current Subscription Status -->
    <div class="nordbooking-card card-bs">
        <div class="nordbooking-card-header">
            <div class="nordbooking-card-title-group">
                <div class="nordbooking-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 6v6l4 2"/>
                    </svg>
                </div>
                <h2 class="nordbooking-card-title"><?php esc_html_e('Current Subscription', 'NORDBOOKING'); ?></h2>
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
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($subscription['ends_at']) && $status === 'active'): ?>
                <div class="status-item">
                    <label><?php esc_html_e('Next Payment:', 'NORDBOOKING'); ?></label>
                    <div class="status-value">
                        <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($subscription['ends_at']))); ?>
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
            </div>
        </div>
    </div>

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

<script>
jQuery(document).ready(function($) {
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
                        location.reload();
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
});
</script>