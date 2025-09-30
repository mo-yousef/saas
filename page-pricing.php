<?php
/**
 * Template Name: Pricing Page
 * 
 * Conversion-optimized pricing page with FAQ section and proper backend integration
 * 
 * @package NORDBOOKING
 */

// Check if user is logged in for proper redirection
$current_user = wp_get_current_user();
$is_logged_in = is_user_logged_in();

get_header();
?>

<style>
/* Pricing Page Specific Styles */
.pricing-hero {
background: radial-gradient(hsl(var(--nbk-primary)), #0e172a);    color: white;
    padding: 4rem 0;
    text-align: center;
}

.pricing-hero h1 {
    font-size: clamp(2.5rem, 5vw, 3rem);
    font-weight: 800;
    margin-bottom: 1rem;
    color: white;
}

.pricing-hero p {
    font-size: 1.25rem;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto 2rem;
    color: white;
}

.pricing-badge {
    display: inline-flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 50px;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 2rem;
    backdrop-filter: blur(10px);
}

.pricing-section {
    padding: 4rem 0;
}

.pricing-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
    margin-top: 3rem;
}

.pricing-card {
    background: white;
    border: 2px solid hsl(var(--nbk-border));
    border-radius: 1rem;
    padding: 2rem;
    position: relative;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.pricing-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.pricing-card.featured {
    border-color: hsl(var(--nbk-primary));
    transform: scale(1.05);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15);
}

.pricing-card.featured::before {
    content: "Most Popular";
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: hsl(var(--nbk-primary));
    color: white;
    padding: 0.5rem 1.5rem;
    border-radius: 50px;
    font-size: 0.875rem;
    font-weight: 600;
}

.plan-name {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: hsl(var(--nbk-foreground));
}

.plan-description {
    color: hsl(var(--nbk-muted-foreground));
    margin-bottom: 2rem;
    line-height: 1.6;
}

.plan-price {
    display: flex;
    align-items: baseline;
    margin-bottom: 2rem;
}

.price-amount {
    font-size: 3rem;
    font-weight: 800;
    color: hsl(var(--nbk-foreground));
}

.price-currency {
    font-size: 1.5rem;
    font-weight: 600;
    margin-right: 0.25rem;
    color: hsl(var(--nbk-foreground));
}

.price-period {
    font-size: 1rem;
    color: hsl(var(--nbk-muted-foreground));
    margin-left: 0.5rem;
}

.plan-features {
    list-style: none;
    padding: 0;
    margin: 0 0 2rem 0;
}

.plan-features li {
    display: flex;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid hsl(var(--nbk-border) / 0.5);
}

.plan-features li:last-child {
    border-bottom: none;
}

.feature-icon {
    width: 20px;
    height: 20px;
    margin-right: 0.75rem;
    color: hsl(var(--nbk-primary));
    flex-shrink: 0;
}

.feature-text {
    color: hsl(var(--nbk-foreground));
    font-weight: 500;
}

.plan-cta {
    width: 100%;
    padding: 1rem 2rem;
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: 0.5rem;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.plan-cta.primary {
    background: hsl(var(--nbk-primary));
    color: white;
    border: 2px solid hsl(var(--nbk-primary));
}

.plan-cta.primary:hover {
    background: hsl(var(--nbk-primary) / 0.9);
    transform: translateY(-1px);
}

.plan-cta.secondary {
    background: white;
    color: hsl(var(--nbk-foreground));
    border: 2px solid hsl(var(--nbk-border));
}

.plan-cta.secondary:hover {
    background: hsl(var(--nbk-muted));
}

.trial-notice {
    background: hsl(var(--nbk-muted));
    border: 1px solid hsl(var(--nbk-border));
    border-radius: 0.5rem;
    padding: 1rem;
    margin-top: 1rem;
    text-align: center;
    font-size: 0.875rem;
    color: hsl(var(--nbk-muted-foreground));
}

.faq-section {
    background: hsl(var(--nbk-muted) / 0.3);
    padding: 4rem 0;
}

.faq-header {
    text-align: center;
    margin-bottom: 3rem;
}

.faq-header h2 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: hsl(var(--nbk-foreground));
}

.faq-header p {
    font-size: 1.125rem;
    color: hsl(var(--nbk-muted-foreground));
    max-width: 600px;
    margin: 0 auto;
}

.faq-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
    gap: 2rem;
    max-width: 1000px;
    margin: 0 auto;
}

.faq-item {
    background: white;
    border: 1px solid hsl(var(--nbk-border));
    border-radius: 0.75rem;
    overflow: hidden;
    transition: all 0.2s ease;
}

.faq-item:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.faq-question {
    padding: 1.5rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: white;
    border: none;
    width: 100%;
    text-align: left;
    font-size: 1.1rem;
    font-weight: 600;
    color: hsl(var(--nbk-foreground));
    transition: background-color 0.2s ease;
}

.faq-question:hover {
    background: hsl(var(--nbk-muted) / 0.5);
}

.faq-question.active {
    background: hsl(var(--nbk-primary) / 0.1);
    color: hsl(var(--nbk-primary));
}

.faq-icon {
    width: 20px;
    height: 20px;
    transition: transform 0.2s ease;
}

.faq-question.active .faq-icon {
    transform: rotate(180deg);
}

.faq-answer {
    padding: 0 1.5rem 1.5rem;
    color: hsl(var(--nbk-muted-foreground));
    line-height: 1.6;
    display: none;
}

.faq-answer.active {
    display: block;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.trust-indicators {
    padding: 3rem 0;
    text-align: center;
    background: white;
}

.trust-indicators h3 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 2rem;
    color: hsl(var(--nbk-foreground));
}

.trust-badges {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 3rem;
    flex-wrap: wrap;
}

.trust-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: hsl(var(--nbk-muted-foreground));
    font-weight: 500;
}

.trust-badge svg {
    width: 24px;
    height: 24px;
    color: hsl(var(--nbk-primary));
}

@media (max-width: 768px) {
    .pricing-cards {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .pricing-card.featured {
        transform: none;
    }
    
    .faq-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .trust-badges {
        gap: 1.5rem;
    }
    
    .pricing-hero {
        padding: 3rem 0;
    }
    
    .pricing-section {
        padding: 3rem 0;
    }
}
</style>

<!-- Pricing Hero Section -->
<section class="pricing-hero">
    <div class="nbk-container">
        <div class="pricing-badge">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
            </svg>
            7-Day Free Trial • No Credit Card Required
        </div>
        <h1>Simple, Transparent Pricing</h1>
        <p>Start your cleaning business transformation today with our 7-day free trial, then continue with our affordable Pro plan.</p>
    </div>
</section>

<!-- Pricing Cards Section -->
<section class="pricing-section">
    <div class="nbk-container">
        <div class="pricing-cards">
            <!-- Free Trial Card -->
            <div class="pricing-card">
                <div class="plan-name">Free Trial</div>
                <div class="plan-description">Experience all Pro features for 7 days - completely free</div>
                <div class="plan-price">
                    <span class="price-currency">$</span>
                    <span class="price-amount">0</span>
                    <span class="price-period">for 7 days</span>
                </div>
                <ul class="plan-features">
                    <li>
                        <svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                        <span class="feature-text">Unlimited bookings</span>
                    </li>
                    <li>
                        <svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                        <span class="feature-text">Advanced calendar & scheduling</span>
                    </li>
                    <li>
                        <svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                        <span class="feature-text">Payment processing (Stripe)</span>
                    </li>
                    <li>
                        <svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                        <span class="feature-text">Customer portal & management</span>
                    </li>
                    <li>
                        <svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                        <span class="feature-text">Team member management</span>
                    </li>
                    <li>
                        <svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                        <span class="feature-text">Analytics & reporting</span>
                    </li>
                    <li>
                        <svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                        <span class="feature-text">Priority email support</span>
                    </li>
                </ul>
                <button class="plan-cta secondary" onclick="handlePricingClick('free')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22,4 12,14.01 9,11.01"/>
                    </svg>
                    Start Free Trial
                </button>
                <div class="trial-notice">
                    No credit card required • Full Pro access for 7 days
                </div>
            </div>

            <!-- Pro Plan Card (Featured) -->
            <div class="pricing-card featured">
                <div class="plan-name">Pro Plan</div>
                <div class="plan-description">Continue with unlimited access to all features</div>
                <div class="plan-price">
                    <span class="price-currency">$</span>
                    <span class="price-amount">29</span>
                    <span class="price-period">per month</span>
                </div>
                <ul class="plan-features">
                    <li>
                        <svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                        <span class="feature-text">Unlimited bookings</span>
                    </li>
                    <li>
                        <svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                        <span class="feature-text">Advanced calendar & scheduling</span>
                    </li>
                    <li>
                        <svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                        <span class="feature-text">Payment processing (Stripe)</span>
                    </li>
                    <li>
                        <svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                        <span class="feature-text">Customer portal & management</span>
                    </li>
                    <li>
                        <svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                        <span class="feature-text">Automated email notifications</span>
                    </li>
                    <li>
                        <svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                        <span class="feature-text">Team member management</span>
                    </li>
                    <li>
                        <svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                        <span class="feature-text">Analytics & reporting</span>
                    </li>
                    <li>
                        <svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                        <span class="feature-text">Priority email support</span>
                    </li>
                </ul>
                <button class="plan-cta primary" onclick="handlePricingClick('pro')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 12l2 2 4-4"/>
                        <circle cx="12" cy="12" r="10"/>
                    </svg>
                    Subscribe Now
                </button>
                <div class="trial-notice">
                    Start with 7-day free trial • Cancel anytime
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Trust Indicators -->
<section class="trust-indicators">
    <div class="nbk-container">
        <h3>Trusted by cleaning businesses worldwide</h3>
        <div class="trust-badges">
            <div class="trust-badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 12l2 2 4-4"/>
                    <path d="M21 12c.552 0 1-.448 1-1V5c0-.552-.448-1-1-1H3c-.552 0-1 .448-1 1v6c0 .552.448 1 1 1h18z"/>
                </svg>
                SSL Encrypted
            </div>
            <div class="trust-badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="1" y="3" width="15" height="13"/>
                    <path d="m16 8 2 2-2 2"/>
                    <path d="m21 12H9"/>
                </svg>
                GDPR Compliant
            </div>
            <div class="trust-badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                99.9% Uptime
            </div>
            <div class="trust-badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/>
                    <polyline points="14,2 14,8 20,8"/>
                </svg>
                PCI DSS Level 1
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="faq-section">
    <div class="nbk-container">
        <div class="faq-header">
            <h2>Frequently Asked Questions</h2>
            <p>Everything you need to know about our pricing and features</p>
        </div>
        <div class="faq-grid">
            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    <span>How does the free trial work?</span>
                    <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6,9 12,15 18,9"/>
                    </svg>
                </button>
                <div class="faq-answer">
                    <p>Our 7-day free trial gives you complete access to all Pro features without requiring a credit card. You get unlimited bookings, full calendar management, payment processing, and all premium features. After 7 days, you'll need to subscribe to the Pro plan to continue using the service.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    <span>Can I cancel my subscription anytime?</span>
                    <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6,9 12,15 18,9"/>
                    </svg>
                </button>
                <div class="faq-answer">
                    <p>Yes, you can cancel your subscription at any time from your dashboard. There are no cancellation fees or long-term contracts. You'll continue to have access to your account until the end of your current billing period.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    <span>What payment methods do you accept?</span>
                    <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6,9 12,15 18,9"/>
                    </svg>
                </button>
                <div class="faq-answer">
                    <p>We accept all major credit cards (Visa, MasterCard, American Express) and debit cards through our secure Stripe payment processing. All payments are encrypted and PCI DSS compliant for your security.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    <span>Is there a setup fee?</span>
                    <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6,9 12,15 18,9"/>
                    </svg>
                </button>
                <div class="faq-answer">
                    <p>No, there are no setup fees, hidden costs, or additional charges. The price you see is exactly what you pay. We believe in transparent pricing with no surprises.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    <span>Can I upgrade or downgrade my plan?</span>
                    <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6,9 12,15 18,9"/>
                    </svg>
                </button>
                <div class="faq-answer">
                    <p>Yes, you can change your plan at any time from your account dashboard. Upgrades take effect immediately, and downgrades will take effect at the end of your current billing cycle. We'll prorate any charges accordingly.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    <span>What happens after my free trial ends?</span>
                    <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6,9 12,15 18,9"/>
                    </svg>
                </button>
                <div class="faq-answer">
                    <p>After your 7-day free trial ends, your dashboard will be locked and you'll be prompted to subscribe to the Pro plan to continue using Nord Booking. We'll send you a reminder email on day 6 so you can upgrade before losing access. All your data remains safe and will be restored when you subscribe.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    <span>Is my data secure?</span>
                    <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6,9 12,15 18,9"/>
                    </svg>
                </button>
                <div class="faq-answer">
                    <p>Absolutely. We use bank-level security with SSL encryption, regular security audits, and comply with GDPR and other privacy regulations. Your data is backed up daily and stored securely. We never share your information with third parties.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    <span>What kind of support do you provide?</span>
                    <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6,9 12,15 18,9"/>
                    </svg>
                </button>
                <div class="faq-answer">
                    <p>All plans include email support with responses within 24 hours. Pro plan subscribers get priority support, and Enterprise customers receive dedicated account management with phone support and faster response times.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// FAQ Toggle Functionality
function toggleFaq(button) {
    const faqItem = button.parentElement;
    const answer = faqItem.querySelector('.faq-answer');
    const isActive = button.classList.contains('active');
    
    // Close all other FAQ items
    document.querySelectorAll('.faq-question.active').forEach(activeButton => {
        if (activeButton !== button) {
            activeButton.classList.remove('active');
            activeButton.parentElement.querySelector('.faq-answer').classList.remove('active');
        }
    });
    
    // Toggle current FAQ item
    if (isActive) {
        button.classList.remove('active');
        answer.classList.remove('active');
    } else {
        button.classList.add('active');
        answer.classList.add('active');
    }
}

// Pricing Click Handler
function handlePricingClick(plan) {
    <?php if ($is_logged_in): ?>
        // User is logged in, redirect to subscription page
        if (plan === 'free') {
            // For free trial, redirect to dashboard (trial will be activated automatically)
            window.location.href = '<?php echo esc_url(home_url('/dashboard/')); ?>';
        } else {
            // For pro, redirect to subscription page
            window.location.href = '<?php echo esc_url(home_url('/dashboard/subscription/')); ?>?plan=' + plan;
        }
    <?php else: ?>
        // User is not logged in, redirect to registration page
        window.location.href = '<?php echo esc_url(home_url('/register/')); ?>?plan=' + plan;
    <?php endif; ?>
}

// Smooth scrolling for anchor links
document.addEventListener('DOMContentLoaded', function() {
    // Add smooth scrolling to FAQ section if linked from elsewhere
    if (window.location.hash === '#faq') {
        document.querySelector('.faq-section').scrollIntoView({
            behavior: 'smooth'
        });
    }
    
    // Add loading states to CTA buttons
    document.querySelectorAll('.plan-cta').forEach(button => {
        button.addEventListener('click', function() {
            const originalText = this.innerHTML;
            this.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg> Loading...';
            this.disabled = true;
            
            // Re-enable button after a short delay if navigation doesn't happen
            setTimeout(() => {
                this.innerHTML = originalText;
                this.disabled = false;
            }, 3000);
        });
    });
});

// Add CSS for spinning animation
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);
</script>

<?php get_footer(); ?>