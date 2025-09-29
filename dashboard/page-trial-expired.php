<?php
/**
 * Trial Expired Page - Shown when user's trial has expired
 * @package NORDBOOKING
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$user_id = get_current_user_id();
$user = get_userdata($user_id);
$user_name = $user ? $user->display_name : '';

// Get subscription info
$subscription = null;
if (class_exists('\NORDBOOKING\Classes\Subscription')) {
    $subscription = \NORDBOOKING\Classes\Subscription::get_subscription($user_id);
}

$trial_ended_date = '';
if ($subscription && !empty($subscription['trial_ends_at'])) {
    $trial_ended_date = date('F j, Y', strtotime($subscription['trial_ends_at']));
}
?>

<style>
.trial-expired-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    padding: 2rem;
}

.trial-expired-card {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    max-width: 600px;
    width: 100%;
    overflow: hidden;
}

.trial-expired-header {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    padding: 2rem;
    text-align: center;
}

.trial-expired-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.trial-expired-header h1 {
    font-size: 2rem;
    font-weight: 700;
    margin: 0 0 0.5rem 0;
    color: white;
}

.trial-expired-header p {
    font-size: 1.1rem;
    opacity: 0.9;
    margin: 0;
    color: white;
}

.trial-expired-content {
    padding: 2rem;
}

.trial-expired-message {
    text-align: center;
    margin-bottom: 2rem;
}

.trial-expired-message h2 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #1f2937;
}

.trial-expired-message p {
    font-size: 1rem;
    color: #6b7280;
    line-height: 1.6;
    margin-bottom: 1rem;
}

.features-reminder {
    background: #f0f9ff;
    border: 1px solid #0ea5e9;
    border-radius: 0.5rem;
    padding: 1.5rem;
    margin: 2rem 0;
}

.features-reminder h3 {
    color: #0369a1;
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.features-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 0.75rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

.features-list li {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #0369a1;
    font-size: 0.875rem;
}

.feature-icon {
    width: 16px;
    height: 16px;
    color: #10b981;
}

.pricing-info {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 1.5rem;
    text-align: center;
    margin: 2rem 0;
}

.pricing-info .price {
    font-size: 2.5rem;
    font-weight: 800;
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.pricing-info .price-period {
    color: #6b7280;
    font-size: 1rem;
}

.pricing-info .price-description {
    color: #6b7280;
    font-size: 0.875rem;
    margin-top: 0.5rem;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.btn {
    padding: 0.75rem 2rem;
    border-radius: 0.5rem;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s ease;
    font-size: 1rem;
}

.btn-primary {
    background: #2563eb;
    color: white;
    border: 2px solid #2563eb;
}

.btn-primary:hover {
    background: #1d4ed8;
    border-color: #1d4ed8;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(37, 99, 235, 0.3);
}

.btn-secondary {
    background: white;
    color: #374151;
    border: 2px solid #d1d5db;
}

.btn-secondary:hover {
    background: #f9fafb;
    border-color: #9ca3af;
}

.support-info {
    text-align: center;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #e5e7eb;
    color: #6b7280;
    font-size: 0.875rem;
}

@media (max-width: 640px) {
    .trial-expired-container {
        padding: 1rem;
    }
    
    .trial-expired-header,
    .trial-expired-content {
        padding: 1.5rem;
    }
    
    .trial-expired-header h1 {
        font-size: 1.5rem;
    }
    
    .features-list {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="trial-expired-container">
    <div class="trial-expired-card">
        <div class="trial-expired-header">
            <div class="trial-expired-icon">⏰</div>
            <h1>Your Free Trial Has Ended</h1>
            <p>Your 7-day free trial expired <?php echo $trial_ended_date ? 'on ' . esc_html($trial_ended_date) : 'recently'; ?></p>
        </div>
        
        <div class="trial-expired-content">
            <div class="trial-expired-message">
                <h2>Hi <?php echo esc_html($user_name); ?>! 👋</h2>
                <p><strong>Your free trial has expired.</strong> Upgrade to the Pro Plan to continue using Nord Booking.</p>
                <p>Your registration form is now locked, and you cannot access the Dashboard until you subscribe.</p>
                <p><strong>Don't worry - all your data is safe and will be restored immediately when you subscribe!</strong></p>
            </div>
            
            <div class="features-reminder">
                <h3>
                    <svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 12l2 2 4-4"/>
                        <circle cx="12" cy="12" r="10"/>
                    </svg>
                    What You'll Get Back:
                </h3>
                <ul class="features-list">
                    <li>
                        <svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                        Unlimited bookings
                    </li>
                    <li>
                        <svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                        Advanced calendar management
                    </li>
                    <li>
                        <svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                        Payment processing
                    </li>
                    <li>
                        <svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                        Customer portal
                    </li>
                    <li>
                        <svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                        Team management
                    </li>
                    <li>
                        <svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                        Analytics & reporting
                    </li>
                </ul>
            </div>
            
            <div class="pricing-info">
                <div class="price">$29<span class="price-period">/month</span></div>
                <div class="price-description">Simple, transparent pricing with no hidden fees</div>
            </div>
            
            <div class="action-buttons">
                <a href="<?php echo esc_url(home_url('/dashboard/subscription/')); ?>" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 12l2 2 4-4"/>
                        <circle cx="12" cy="12" r="10"/>
                    </svg>
                    Subscribe to Pro Plan
                </a>
                <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="btn btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    Contact Support
                </a>
            </div>
            
            <div class="support-info">
                <p>Need help or have questions? Our support team is here to assist you.</p>
                <p><strong>Email:</strong> support@nordbooking.com</p>
            </div>
        </div>
    </div>
</div>

<script>
// Add some interactive elements
document.addEventListener('DOMContentLoaded', function() {
    // Add click tracking for analytics (if needed)
    const subscribeBtn = document.querySelector('.btn-primary');
    if (subscribeBtn) {
        subscribeBtn.addEventListener('click', function() {
            // Track subscription button click
            console.log('Trial expired - Subscribe button clicked');
        });
    }
    
    // Add smooth animations
    const card = document.querySelector('.trial-expired-card');
    if (card) {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100);
    }
});
</script>