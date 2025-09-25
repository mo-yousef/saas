<?php
/**
 * Template Name: Cookies Policy
 * Description: Cookies Policy page for Nord Booking
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
        .cookies-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .cookies-content {
        }

        /* Typography */
        .cookies-title {
            color: hsl(var(--primary));
            font-size: 2.5rem;
            font-weight: 700;
            border-bottom: 3px solid hsl(var(--primary));
            padding-bottom: 0.5rem;
            margin-bottom: 2rem;
        }

        .cookies-subtitle {
            font-size: 1.5rem;
            font-weight: 600;
            margin-top: 2rem;
            margin-bottom: 1rem;
        }

        .cookies-subsubtitle {
            color: hsl(var(--foreground));
            font-size: 1.25rem;
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
        }

        .cookies-paragraph {
            margin-bottom: 1rem;
            color: hsl(var(--nbk-muted-foreground));
        }

        .cookies-list {
            margin: 1rem 0 1rem 1.25rem;
            color: hsl(var(--nbk-muted-foreground));
        }

        .cookies-list li {
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

        .cookies-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
            border: 1px solid hsl(var(--border));
            border-radius: var(--radius);
            overflow: hidden;
        }

        .cookies-table th,
        .cookies-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid hsl(var(--border));
        }

        .cookies-table th {
            background-color: hsl(var(--muted));
            font-weight: 600;
            color: hsl(var(--foreground));
        }

        .cookies-table tr:hover {
            background-color: hsl(var(--muted) / 0.3);
        }

        .cookie-type {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .cookie-type.essential {
            background-color: hsl(142 70% 96%);
            color: hsl(142 70% 45%);
        }

        .cookie-type.analytics {
            background-color: hsl(200 70% 96%);
            color: hsl(200 70% 45%);
        }

        .cookie-type.functional {
            background-color: hsl(43 70% 96%);
            color: hsl(43 70% 45%);
        }

        .cookie-type.marketing {
            background-color: hsl(335 70% 96%);
            color: hsl(335 70% 45%);
        }

        .contact-box {
            background-color: hsl(var(--nbk-muted) / 0.5);
            padding: 1.5rem;
            border-radius: var(--radius);
            margin-top: 2rem;
        }

        .consent-box {
            background-color: hsl(200 100% 96%);
            padding: 1.5rem;
            border-radius: var(--radius);
            border-left: 4px solid hsl(200 100% 50%);
            margin: 1.5rem 0;
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

        @media (max-width: 768px) {
            .cookies-table {
                font-size: 0.875rem;
            }
            
            .cookies-table th,
            .cookies-table td {
                padding: 0.5rem;
            }
        }

    </style>

    <!-- Main Content -->
    <main>
        <div class="nbk-page-header">
            <div class="nbk-container">
                <div class="nbk-page-header-content">
                    <h1><?php _e('Cookies Policy', $nbk_text_domain); ?></h1>
                </div>
            </div>
        </div>
        <div class="nbk-container" data-spacing="top-bottom" data-layout="narrow">
            <div class="cookies-content">
                <p class="last-updated">
                    <strong><?php _e('Last Updated:', $nbk_text_domain); ?></strong> 
                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime('2025-09-25'))); ?>
                </p>

                <div class="highlight-box">
                    <strong><?php _e('Simple Summary:', $nbk_text_domain); ?></strong> 
                    <?php _e('We use cookies to make our booking platform work properly and improve your experience. Essential cookies are necessary for the service to function. You can control optional cookies through your browser settings or our cookie preferences.', $nbk_text_domain); ?>
                </div>

                <h2 class="cookies-subtitle"><?php _e('1. What Are Cookies?', $nbk_text_domain); ?></h2>
                <p class="cookies-paragraph">
                    <?php _e('Cookies are small text files that are stored on your computer or mobile device when you visit our website. They help us make Nord Booking work properly and provide you with a better experience.', $nbk_text_domain); ?>
                </p>
                <p class="cookies-paragraph">
                    <?php _e('Cookies contain information that is transferred to your device\'s hard drive. They help us recognize your browser and, if you have a registered account, associate it with your account.', $nbk_text_domain); ?>
                </p>

                <h2 class="cookies-subtitle"><?php _e('2. How We Use Cookies', $nbk_text_domain); ?></h2>
                <p class="cookies-paragraph"><?php _e('We use cookies for several important reasons:', $nbk_text_domain); ?></p>
                <ul class="cookies-list">
                    <li><?php _e('To keep you logged in to your Nord Booking account', $nbk_text_domain); ?></li>
                    <li><?php _e('To remember your preferences and settings', $nbk_text_domain); ?></li>
                    <li><?php _e('To ensure our booking forms work properly', $nbk_text_domain); ?></li>
                    <li><?php _e('To improve our website\'s performance and security', $nbk_text_domain); ?></li>
                    <li><?php _e('To understand how you use our service (with your consent)', $nbk_text_domain); ?></li>
                </ul>

                <div class="consent-box">
                    <h3><?php _e('Your Cookie Consent', $nbk_text_domain); ?></h3>
                    <p><?php _e('Under GDPR and other privacy laws, we need your consent for non-essential cookies. Essential cookies that make our service work don\'t require consent, but we\'ll always be transparent about what we use.', $nbk_text_domain); ?></p>
                </div>

                <h2 class="cookies-subtitle"><?php _e('3. Types of Cookies We Use', $nbk_text_domain); ?></h2>
                
                <h3 class="cookies-subsubtitle"><?php _e('3.1 Essential Cookies (Always Active)', $nbk_text_domain); ?></h3>
                <p class="cookies-paragraph">
                    <?php _e('These cookies are necessary for Nord Booking to function properly. They cannot be disabled as they are essential for providing our booking management service.', $nbk_text_domain); ?>
                </p>
                <ul class="cookies-list">
                    <li><?php _e('Session management cookies that keep you logged in', $nbk_text_domain); ?></li>
                    <li><?php _e('Security cookies that protect against attacks', $nbk_text_domain); ?></li>
                    <li><?php _e('Preference cookies that remember your language and settings', $nbk_text_domain); ?></li>
                    <li><?php _e('Booking process cookies that temporarily store form data', $nbk_text_domain); ?></li>
                </ul>

                <h3 class="cookies-subsubtitle"><?php _e('3.2 Functional Cookies (Can Be Disabled)', $nbk_text_domain); ?></h3>
                <p class="cookies-paragraph">
                    <?php _e('These cookies enhance your experience but are not essential for the basic functionality of Nord Booking.', $nbk_text_domain); ?>
                </p>
                <ul class="cookies-list">
                    <li><?php _e('Recent bookings history for quick access to frequently used services', $nbk_text_domain); ?></li>
                    <li><?php _e('Dashboard layout preferences and customizations', $nbk_text_domain); ?></li>
                    <li><?php _e('Notification preferences and display settings', $nbk_text_domain); ?></li>
                    <li><?php _e('User interface customizations and saved views', $nbk_text_domain); ?></li>
                </ul>

                <h3 class="cookies-subsubtitle"><?php _e('3.3 Analytics Cookies (Optional)', $nbk_text_domain); ?></h3>
                <p class="cookies-paragraph">
                    <?php _e('These cookies help us understand how our service is used so we can improve it. We only use analytics cookies with your explicit consent.', $nbk_text_domain); ?>
                </p>
                <ul class="cookies-list">
                    <li><strong><?php _e('Google Analytics:', $nbk_text_domain); ?></strong> <?php _e('Tracks website usage and user interactions to help us improve our service', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Usage Analytics:', $nbk_text_domain); ?></strong> <?php _e('Internal analytics to understand feature usage and improve user experience', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Performance Monitoring:', $nbk_text_domain); ?></strong> <?php _e('Tracks website performance and identifies areas for improvement', $nbk_text_domain); ?></li>
                </ul>

                <h2 class="cookies-subtitle"><?php _e('4. Third-Party Cookies', $nbk_text_domain); ?></h2>
                <p class="cookies-paragraph">
                    <?php _e('Some cookies are set by trusted third-party services that we use to provide our service:', $nbk_text_domain); ?>
                </p>
                <ul class="cookies-list">
                    <li><strong><?php _e('Stripe:', $nbk_text_domain); ?></strong> <?php _e('Payment processing cookies for subscription management (essential for billing)', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Email Service Provider:', $nbk_text_domain); ?></strong> <?php _e('Tracking for booking confirmation emails (functional)', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Content Delivery Network (CDN):', $nbk_text_domain); ?></strong> <?php _e('Performance cookies to deliver content faster (functional)', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Support Chat:', $nbk_text_domain); ?></strong> <?php _e('Customer support chat functionality (functional)', $nbk_text_domain); ?></li>
                </ul>
                <p class="cookies-paragraph">
                    <?php _e('These third-party services have their own privacy policies and cookie practices. We recommend reviewing their policies for more information.', $nbk_text_domain); ?>
                </p>

                <h2 class="cookies-subtitle"><?php _e('5. Managing Your Cookie Preferences', $nbk_text_domain); ?></h2>
                
                <h3 class="cookies-subsubtitle"><?php _e('5.1 Browser Settings', $nbk_text_domain); ?></h3>
                <p class="cookies-paragraph">
                    <?php _e('You can control cookies through your browser settings. Here\'s how to manage cookies in popular browsers:', $nbk_text_domain); ?>
                </p>
                <ul class="cookies-list">
                    <li><strong><?php _e('Chrome:', $nbk_text_domain); ?></strong> <?php _e('Settings > Privacy and Security > Cookies and other site data', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Firefox:', $nbk_text_domain); ?></strong> <?php _e('Settings > Privacy & Security > Cookies and Site Data', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Safari:', $nbk_text_domain); ?></strong> <?php _e('Preferences > Privacy > Manage Website Data', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Edge:', $nbk_text_domain); ?></strong> <?php _e('Settings > Cookies and site permissions > Cookies and site data', $nbk_text_domain); ?></li>
                </ul>

                <div class="important-box">
                    <strong><?php _e('Important:', $nbk_text_domain); ?></strong> 
                    <?php _e('Disabling essential cookies will prevent Nord Booking from working properly. You may not be able to log in, save preferences, or use booking features if essential cookies are blocked.', $nbk_text_domain); ?>
                </div>

                <h3 class="cookies-subsubtitle"><?php _e('5.2 Cookie Preferences Center', $nbk_text_domain); ?></h3>
                <p class="cookies-paragraph">
                    <?php _e('You can also manage your cookie preferences directly through our Cookie Preferences Center, which allows you to:', $nbk_text_domain); ?>
                </p>
                <ul class="cookies-list">
                    <li><?php _e('View all cookies we use and their purposes', $nbk_text_domain); ?></li>
                    <li><?php _e('Enable or disable non-essential cookie categories', $nbk_text_domain); ?></li>
                    <li><?php _e('Update your preferences at any time', $nbk_text_domain); ?></li>
                    <li><?php _e('See which third-party services use cookies', $nbk_text_domain); ?></li>
                </ul>

                <h2 class="cookies-subtitle"><?php _e('6. GDPR and Cookie Compliance', $nbk_text_domain); ?></h2>
                <p class="cookies-paragraph">
                    <?php _e('Under the General Data Protection Regulation (GDPR) and other privacy laws, we:', $nbk_text_domain); ?>
                </p>
                <ul class="cookies-list">
                    <li><?php _e('Only use essential cookies without consent (they\'re necessary for the service)', $nbk_text_domain); ?></li>
                    <li><?php _e('Ask for your explicit consent before using analytics or marketing cookies', $nbk_text_domain); ?></li>
                    <li><?php _e('Provide clear information about all cookies we use', $nbk_text_domain); ?></li>
                    <li><?php _e('Allow you to withdraw consent at any time', $nbk_text_domain); ?></li>
                    <li><?php _e('Respect your choices about cookie usage', $nbk_text_domain); ?></li>
                </ul>

                <h2 class="cookies-subtitle"><?php _e('7. Data Protection and Security', $nbk_text_domain); ?></h2>
                <p class="cookies-paragraph">
                    <?php _e('All cookies we use are secured and protected:', $nbk_text_domain); ?>
                </p>
                <ul class="cookies-list">
                    <li><?php _e('Cookies are encrypted and transmitted over HTTPS', $nbk_text_domain); ?></li>
                    <li><?php _e('We use secure, httpOnly flags where appropriate', $nbk_text_domain); ?></li>
                    <li><?php _e('Cookie data is never shared with unauthorized third parties', $nbk_text_domain); ?></li>
                    <li><?php _e('We regularly review and update our cookie usage', $nbk_text_domain); ?></li>
                </ul>

                <h2 class="cookies-subtitle"><?php _e('8. Updates to This Cookie Policy', $nbk_text_domain); ?></h2>
                <p class="cookies-paragraph">
                    <?php _e('We may update this cookie policy from time to time to reflect changes in our practices or legal requirements. When we make significant changes, we will:', $nbk_text_domain); ?>
                </p>
                <ul class="cookies-list">
                    <li><?php _e('Update the "Last Modified" date at the top of this policy', $nbk_text_domain); ?></li>
                    <li><?php _e('Notify you through our website or email for major changes', $nbk_text_domain); ?></li>
                    <li><?php _e('Request new consent if we introduce new types of cookies', $nbk_text_domain); ?></li>
                </ul>

                <div class="contact-box">
                    <h2 class="cookies-subtitle"><?php _e('9. Contact Us About Cookies', $nbk_text_domain); ?></h2>
                    <p class="cookies-paragraph">
                        <?php _e('If you have questions about our cookie policy or want to exercise your rights:', $nbk_text_domain); ?>
                    </p>
                    <p class="cookies-paragraph">
                        <strong><?php _e('General Questions:', $nbk_text_domain); ?></strong> 
                        <a href="mailto:info@nordbk.com">info@nordbk.com</a>
                    </p>
                    <p class="cookies-paragraph">
                        <strong><?php _e('Privacy & Data Protection:', $nbk_text_domain); ?></strong> 
                        <a href="mailto:info@nordbk.com">info@nordbk.com</a>
                    </p>
                    <p class="cookies-paragraph">
                        <strong><?php _e('Technical Support:', $nbk_text_domain); ?></strong> 
                        <a href="mailto:support@nordbk.com">support@nordbk.com</a>
                    </p>
                    
                    <h3 class="cookies-subsubtitle"><?php _e('Quick Actions', $nbk_text_domain); ?></h3>
                    <ul class="cookies-list">
                        <li><a href="<?php echo esc_url(home_url('/privacy/')); ?>"><?php _e('View Privacy Policy', $nbk_text_domain); ?></a></li>
                        <li><a href="<?php echo esc_url(home_url('/terms/')); ?>"><?php _e('View Terms of Service', $nbk_text_domain); ?></a></li>
                    </ul>
                </div>

                <p class="cookies-paragraph" style="margin-top: 2rem; font-size: 0.875rem; color: hsl(var(--muted-foreground));">
                    <?php _e('This cookie policy complies with GDPR, ePrivacy Directive, CCPA, and other applicable privacy regulations. We are committed to transparency and giving you control over your data.', $nbk_text_domain); ?>
                </p>
            </div>
        </div>
    </main>

    <?php get_footer(); ?>