<?php
/**
 * Template Name: Terms and Conditions
 * Description: Terms and Conditions page for Nord Booking
 *
 * @package NORDBOOKING
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
get_header(); 
// Get theme text domain
$nbk_text_domain = 'NORDBOOKING';
?>

    <style>
        /* Use theme's CSS variables and design system */
        :root {
            --background: 0 0% 100%;
            --foreground: 222.2 84% 4.9%;
            --card: 0 0% 100%;
            --card-foreground: 222.2 84% 4.9%;
            --popover: 0 0% 100%;
            --popover-foreground: 222.2 84% 4.9%;
            --primary: 221.2 83.2% 53.3%;
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
            --ring: 221.2 83.2% 53.3%;
            --radius: 0.5rem;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Outfit", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: hsl(var(--foreground));
            background-color: hsl(var(--background));
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Header styling matching theme */
        .nordbk-header {
            position: sticky;
            top: 0;
            z-index: 50;
            width: 100%;
            border-bottom: 1px solid hsl(var(--border));
            background-color: hsl(var(--background) / 0.95);
            backdrop-filter: blur(8px);
        }

        .nordbk-header .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .nordbk-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 4rem;
        }

        .nordbk-logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: hsl(var(--primary));
            text-decoration: none;
        }

        .nordbk-nav-links {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .nordbk-nav-link {
            font-size: 0.875rem;
            font-weight: 500;
            color: hsl(var(--foreground));
            text-decoration: none;
            transition: color 0.2s;
        }

        .nordbk-nav-link:hover {
            color: hsl(var(--primary));
        }

        /* Main content container */
        .terms-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .terms-content {
        }

        /* Typography */
        .terms-title {
            color: hsl(var(--primary));
            font-size: 2.5rem;
            font-weight: 700;
            border-bottom: 3px solid hsl(var(--primary));
            padding-bottom: 0.5rem;
            margin-bottom: 2rem;
        }

        .terms-subtitle {
            font-size: 1.5rem;
            font-weight: 600;
            margin-top: 2rem;
            margin-bottom: 1rem;
        }

        .terms-subsubtitle {
            color: hsl(var(--foreground));
            font-size: 1.25rem;
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
        }

        .terms-paragraph {
            margin-bottom: 1rem;
    color: hsl(var(--nbk-muted-foreground));
        }

        .terms-list {
            margin: 1rem 0 1rem 1.25rem;
    color: hsl(var(--nbk-muted-foreground));
        }

        .terms-list li {
            margin-bottom: 0.5rem;
        }

        .last-updated {
            font-style: italic;
            color: hsl(var(--muted-foreground));
            margin-bottom: 2rem;
        }

        /* Highlight boxes */
        .highlight-box {
            background-color: hsl(var(--accent));
            padding: 1rem;
            border-left: 4px solid hsl(var(--primary));
            margin: 1.5rem 0;
            border-radius: var(--radius);
        }

        .important-box {
            background-color: hsl(43 100% 96%);
            padding: 1rem;
            border-radius: var(--radius);
            border-left: 4px solid hsl(43 100% 50%);
            margin: 1rem 0;
        }

        .contact-box {
    background-color: hsl(var(--nbk-muted) / 0.5);
            padding: 1.5rem;
            border-radius: var(--radius);
            margin-top: 2rem;
        }

        /* Footer */
        .nordbk-footer {
    background-color: hsl(var(--nbk-muted) / 0.5);
            border-top: 1px solid hsl(var(--border));
            padding: 2rem 0;
            margin-top: 3rem;
        }

        .nordbk-footer .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
            text-align: center;
        }

        .nordbk-footer-text {
            color: hsl(var(--muted-foreground));
            font-size: 0.875rem;
        }

        .nordbk-footer-link {
            color: hsl(var(--primary));
            text-decoration: none;
        }

        .nordbk-footer-link:hover {
            text-decoration: underline;
        }

    </style>

    <!-- Main Content -->
    <main>
        <div class="nbk-page-header">
            <div class="nbk-container">
                <div class="nbk-page-header-content">
                    <h1>Terms and Conditions</h1>
                </div>
            </div>
        </div>
        <div class="nbk-container" data-spacing="top-bottom" data-layout="narrow">
            <div class="terms-content">
                <p class="last-updated">
                    <strong><?php _e('Last Updated:', $nbk_text_domain); ?></strong> 
                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime('2025-09-25'))); ?>
                </p>

                <div class="highlight-box">
                    <strong><?php _e('Simple Summary:', $nbk_text_domain); ?></strong> 
                    <?php _e('By using Nord Booking, you agree to follow our rules, respect other users, and understand that we\'ll protect your data according to GDPR laws. We provide a booking management platform for businesses, with subscriptions processed through Stripe. Businesses handle their own customer payments directly.', $nbk_text_domain); ?>
                </div>

                <h2 class="terms-subtitle"><?php _e('1. What These Terms Cover', $nbk_text_domain); ?></h2>
                <p class="terms-paragraph">
                    <?php _e('These terms apply when you use Nord Booking\'s website and services. By creating an account or making a booking, you agree to follow these rules.', $nbk_text_domain); ?>
                </p>

                <h2 class="terms-subtitle"><?php _e('2. Our Service', $nbk_text_domain); ?></h2>
                <p class="terms-paragraph"><?php _e('Nord Booking is a subscription-based booking management platform that helps businesses:', $nbk_text_domain); ?></p>
                <ul class="terms-list">
                    <li><?php _e('Create and manage their own booking system', $nbk_text_domain); ?></li>
                    <li><?php _e('Accept appointments from customers through custom booking forms', $nbk_text_domain); ?></li>
                    <li><?php _e('Manage customers, services, and schedules', $nbk_text_domain); ?></li>
                    <li><?php _e('Send automated booking confirmations and notifications', $nbk_text_domain); ?></li>
                    <li><?php _e('Process customer bookings without handling payments', $nbk_text_domain); ?></li>
                </ul>
                <p class="terms-paragraph">
                    <strong><?php _e('Note:', $nbk_text_domain); ?></strong> 
                    <?php _e('Nord Booking is a platform for businesses to manage bookings. We don\'t handle payments between businesses and their customers - that\'s handled directly between them.', $nbk_text_domain); ?>
                </p>

                <h2 class="terms-subtitle"><?php _e('3. Your Responsibilities', $nbk_text_domain); ?></h2>
                
                <h3 class="terms-subsubtitle"><?php _e('If You\'re a Business Owner:', $nbk_text_domain); ?></h3>
                <ul class="terms-list">
                    <li><?php _e('Pay your monthly subscription fee through Stripe', $nbk_text_domain); ?></li>
                    <li><?php _e('Provide accurate service information to customers', $nbk_text_domain); ?></li>
                    <li><?php _e('Honor bookings made through your booking form', $nbk_text_domain); ?></li>
                    <li><?php _e('Handle your own payment processing with customers', $nbk_text_domain); ?></li>
                    <li><?php _e('Manage your own booking policies and cancellations', $nbk_text_domain); ?></li>
                    <li><?php _e('Keep your account and business information up to date', $nbk_text_domain); ?></li>
                </ul>
                
                <h3 class="terms-subsubtitle"><?php _e('If You\'re a Customer Making Bookings:', $nbk_text_domain); ?></h3>
                <ul class="terms-list">
                    <li><?php _e('Provide accurate contact information', $nbk_text_domain); ?></li>
                    <li><?php _e('Show up for confirmed appointments', $nbk_text_domain); ?></li>
                    <li><?php _e('Follow the business\'s payment and cancellation policies', $nbk_text_domain); ?></li>
                    <li><?php _e('Communicate directly with the business for service-related issues', $nbk_text_domain); ?></li>
                </ul>
                
                <h3 class="terms-subsubtitle"><?php _e('Everyone Must:', $nbk_text_domain); ?></h3>
                <ul class="terms-list">
                    <li><?php _e('Keep account information secure', $nbk_text_domain); ?></li>
                    <li><?php _e('Not misuse our platform', $nbk_text_domain); ?></li>
                    <li><?php _e('Treat other users respectfully', $nbk_text_domain); ?></li>
                </ul>

                <h2 class="terms-subtitle"><?php _e('4. Subscriptions and Payments', $nbk_text_domain); ?></h2>
                
                <h3 class="terms-subsubtitle"><?php _e('Business Owner Subscriptions:', $nbk_text_domain); ?></h3>
                <p class="terms-paragraph">
                    <strong><?php _e('Monthly Subscription:', $nbk_text_domain); ?></strong> 
                    <?php _e('Business owners pay a monthly subscription fee to access Nord Booking\'s platform. This payment is processed securely through Stripe.', $nbk_text_domain); ?>
                </p>
                <p class="terms-paragraph">
                    <strong><?php _e('Free Trial:', $nbk_text_domain); ?></strong> 
                    <?php _e('New users may receive a free trial period. After the trial ends, you\'ll need to subscribe to continue using the service.', $nbk_text_domain); ?>
                </p>
                <p class="terms-paragraph">
                    <strong><?php _e('Billing:', $nbk_text_domain); ?></strong> 
                    <?php _e('Subscription fees are automatically charged monthly to your chosen payment method through Stripe.', $nbk_text_domain); ?>
                </p>
                <p class="terms-paragraph">
                    <strong><?php _e('Cancellation:', $nbk_text_domain); ?></strong> 
                    <?php _e('You can cancel your subscription anytime through your account dashboard or Stripe\'s customer portal. You\'ll retain access until your current billing period ends.', $nbk_text_domain); ?>
                </p>
                
                <h3 class="terms-subsubtitle"><?php _e('Customer Bookings:', $nbk_text_domain); ?></h3>
                <p class="terms-paragraph">
                    <strong><?php _e('No Payment Processing:', $nbk_text_domain); ?></strong> 
                    <?php _e('Nord Booking doesn\'t handle payments between businesses and their customers. All service payments are handled directly between you and the business.', $nbk_text_domain); ?>
                </p>
                <p class="terms-paragraph">
                    <strong><?php _e('Booking Changes:', $nbk_text_domain); ?></strong> 
                    <?php _e('You can reschedule or cancel bookings through the management link sent to your email, subject to the business\'s policies.', $nbk_text_domain); ?>
                </p>

                <div class="important-box">
                    <strong><?php _e('Important:', $nbk_text_domain); ?></strong> 
                    <?php _e('We provide the booking platform, but each business sets their own prices, payment methods, and policies. Any payment disputes should be resolved directly with the business.', $nbk_text_domain); ?>
                </div>

                <h2 class="terms-subtitle"><?php _e('5. Your Data and Privacy (GDPR)', $nbk_text_domain); ?></h2>
                <p class="terms-paragraph">
                    <?php _e('We respect your privacy and follow EU data protection laws (GDPR). Here\'s what you need to know:', $nbk_text_domain); ?>
                </p>
                
                <h3 class="terms-subsubtitle"><?php _e('What Data We Collect:', $nbk_text_domain); ?></h3>
                
                <h4><strong><?php _e('For Business Owners:', $nbk_text_domain); ?></strong></h4>
                <ul class="terms-list">
                    <li><?php _e('Account details (name, email, business information)', $nbk_text_domain); ?></li>
                    <li><?php _e('Subscription and billing information (processed by Stripe)', $nbk_text_domain); ?></li>
                    <li><?php _e('Service and booking management data', $nbk_text_domain); ?></li>
                    <li><?php _e('Customer data you collect through your booking forms', $nbk_text_domain); ?></li>
                </ul>
                
                <h4><strong><?php _e('For Customers Making Bookings:', $nbk_text_domain); ?></strong></h4>
                <ul class="terms-list">
                    <li><?php _e('Contact details (name, email, phone) when making bookings', $nbk_text_domain); ?></li>
                    <li><?php _e('Booking details and preferences', $nbk_text_domain); ?></li>
                    <li><?php _e('Communication with businesses through our platform', $nbk_text_domain); ?></li>
                </ul>
                
                <h4><strong><?php _e('Automatically Collected:', $nbk_text_domain); ?></strong></h4>
                <ul class="terms-list">
                    <li><?php _e('Website usage data and analytics', $nbk_text_domain); ?></li>
                    <li><?php _e('Device and browser information', $nbk_text_domain); ?></li>
                    <li><?php _e('IP addresses and access logs', $nbk_text_domain); ?></li>
                </ul>

                <h3 class="terms-subsubtitle"><?php _e('Your Rights Under GDPR:', $nbk_text_domain); ?></h3>
                <ul class="terms-list">
                    <li><strong><?php _e('Access:', $nbk_text_domain); ?></strong> <?php _e('See what data we have about you', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Correction:', $nbk_text_domain); ?></strong> <?php _e('Fix incorrect information', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Deletion:', $nbk_text_domain); ?></strong> <?php _e('Ask us to delete your data', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Portability:', $nbk_text_domain); ?></strong> <?php _e('Get your data in a standard format', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Object:', $nbk_text_domain); ?></strong> <?php _e('Stop certain data processing', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Withdraw consent:', $nbk_text_domain); ?></strong> <?php _e('Change your mind about data use', $nbk_text_domain); ?></li>
                </ul>

                <p class="terms-paragraph">
                    <?php _e('We only use your data to provide our booking management service, process subscriptions through Stripe, with your consent, or when legally required. Your data is stored securely and never sold to third parties.', $nbk_text_domain); ?>
                </p>

                <h2 class="terms-subtitle"><?php _e('6. What We Don\'t Allow', $nbk_text_domain); ?></h2>
                <p class="terms-paragraph"><?php _e('Please don\'t:', $nbk_text_domain); ?></p>
                <ul class="terms-list">
                    <li><?php _e('Create fake accounts or bookings', $nbk_text_domain); ?></li>
                    <li><?php _e('Harass other users or service providers', $nbk_text_domain); ?></li>
                    <li><?php _e('Try to hack or break our system', $nbk_text_domain); ?></li>
                    <li><?php _e('Copy our content without permission', $nbk_text_domain); ?></li>
                    <li><?php _e('Use our service for illegal activities', $nbk_text_domain); ?></li>
                </ul>

                <h2 class="terms-subtitle"><?php _e('7. Service Availability', $nbk_text_domain); ?></h2>
                <p class="terms-paragraph">
                    <?php _e('We try to keep Nord Booking running smoothly, but sometimes we need to do maintenance or updates. We\'re not responsible if you can\'t access the service temporarily.', $nbk_text_domain); ?>
                </p>

                <h2 class="terms-subtitle"><?php _e('8. Limitation of Liability', $nbk_text_domain); ?></h2>
                <p class="terms-paragraph">
                    <?php _e('Nord Booking is a platform that helps businesses manage bookings. We don\'t provide the actual services or handle payments between businesses and customers. We\'re not liable for:', $nbk_text_domain); ?>
                </p>
                <ul class="terms-list">
                    <li><?php _e('Service quality issues between businesses and customers', $nbk_text_domain); ?></li>
                    <li><?php _e('Payment disputes (these are handled directly between parties)', $nbk_text_domain); ?></li>
                    <li><?php _e('Missed appointments due to business or customer issues', $nbk_text_domain); ?></li>
                    <li><?php _e('Technical issues beyond our reasonable control', $nbk_text_domain); ?></li>
                    <li><?php _e('Business policy changes or closures', $nbk_text_domain); ?></li>
                    <li><?php _e('Data loss due to user error or account termination', $nbk_text_domain); ?></li>
                </ul>
                
                <p class="terms-paragraph">
                    <?php _e('Our maximum liability is limited to the amount you\'ve paid us in subscription fees in the 12 months prior to any incident.', $nbk_text_domain); ?>
                </p>

                <h2 class="terms-subtitle"><?php _e('9. Changes to These Terms', $nbk_text_domain); ?></h2>
                <p class="terms-paragraph">
                    <?php _e('We may update these terms occasionally. If we make significant changes, we\'ll notify you by email or through our website. Continued use means you accept the new terms.', $nbk_text_domain); ?>
                </p>

                <h2 class="terms-subtitle"><?php _e('10. Ending Your Account', $nbk_text_domain); ?></h2>
                <p class="terms-paragraph">
                    <?php _e('You can close your account anytime through your settings. We may close accounts that violate these terms. When an account is closed, some data may be kept for legal or business reasons.', $nbk_text_domain); ?>
                </p>

                <h2 class="terms-subtitle"><?php _e('11. Governing Law', $nbk_text_domain); ?></h2>
                <p class="terms-paragraph">
                    <?php _e('These terms are governed by the laws of the European Union and the jurisdiction where Nord Booking is headquartered. Any disputes will be handled in accordance with applicable consumer protection laws.', $nbk_text_domain); ?>
                </p>

                <div class="contact-box">
                    <h2 class="terms-subtitle"><?php _e('12. Contact Us', $nbk_text_domain); ?></h2>
                    <p class="terms-paragraph">
                        <?php _e('Questions about these terms? We\'re here to help:', $nbk_text_domain); ?>
                    </p>
                    <p class="terms-paragraph">
                        <strong><?php _e('Email:', $nbk_text_domain); ?></strong> 
                        <a href="mailto:support@nordbk.com">support@nordbk.com</a>
                    </p>
                    <p class="terms-paragraph">
                        <strong><?php _e('Data Protection Officer:', $nbk_text_domain); ?></strong> 
                        <a href="mailto:info@nordbk.com">info@nordbk.com</a>
                    </p>
                    
                    <p class="terms-paragraph">
                        <?php _e('For GDPR-related requests or concerns about your data, please contact our Data Protection Officer directly.', $nbk_text_domain); ?>
                    </p>
                </div>

                <p class="terms-paragraph" style="margin-top: 2rem; font-size: 0.875rem; color: hsl(var(--muted-foreground));">
                    <?php _e('This document is designed to be clear and understandable while ensuring full legal compliance with GDPR and consumer protection laws.', $nbk_text_domain); ?>
                </p>
            </div>
        </div>
    </main>

    <?php get_footer(); ?>
