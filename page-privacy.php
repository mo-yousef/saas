<?php
/**
 * Template Name: Privacy Policy
 * Description: Privacy Policy page for Nord Booking
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
        .privacy-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .privacy-content {
        }

        /* Typography */
        .privacy-title {
            color: hsl(var(--primary));
            font-size: 2.5rem;
            font-weight: 700;
            border-bottom: 3px solid hsl(var(--primary));
            padding-bottom: 0.5rem;
            margin-bottom: 2rem;
        }

        .privacy-subtitle {
            font-size: 1.5rem;
            font-weight: 600;
            margin-top: 2rem;
            margin-bottom: 1rem;
        }

        .privacy-subsubtitle {
            color: hsl(var(--foreground));
            font-size: 1.25rem;
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
        }

        .privacy-paragraph {
            margin-bottom: 1rem;
            color: hsl(var(--nbk-muted-foreground));
        }

        .privacy-list {
            margin: 1rem 0 1rem 1.25rem;
            color: hsl(var(--nbk-muted-foreground));
        }

        .privacy-list li {
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

        .gdpr-box {
            background-color: hsl(200 100% 96%);
            padding: 1rem;
            border-radius: var(--radius);
            border-left: 4px solid hsl(200 100% 50%);
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
                    <h1><?php _e('Privacy Policy', $nbk_text_domain); ?></h1>
                </div>
            </div>
        </div>
        <div class="nbk-container" data-spacing="top-bottom" data-layout="narrow">
            <div class="privacy-content">
                <p class="last-updated">
                    <strong><?php _e('Last Updated:', $nbk_text_domain); ?></strong> 
                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime('2025-09-25'))); ?>
                </p>

                <div class="highlight-box">
                    <strong><?php _e('Simple Summary:', $nbk_text_domain); ?></strong> 
                    <?php _e('We protect your privacy and follow GDPR laws. We only collect data needed to provide our booking platform service, never sell your information, and give you full control over your personal data. You can access, correct, or delete your data anytime.', $nbk_text_domain); ?>
                </div>

                <h2 class="privacy-subtitle"><?php _e('1. Introduction', $nbk_text_domain); ?></h2>
                <p class="privacy-paragraph">
                    <?php _e('Welcome to Nord Booking ("we", "our", "us"). We are committed to protecting your privacy and handling your personal data transparently. This privacy policy explains how we collect, use, process, and protect your information when you use our website and booking management services.', $nbk_text_domain); ?>
                </p>
                <p class="privacy-paragraph">
                    <?php _e('This policy applies to all users of our services, including business owners who subscribe to our platform, customers making bookings, and website visitors.', $nbk_text_domain); ?>
                </p>

                <h2 class="privacy-subtitle"><?php _e('2. Data Controller Information', $nbk_text_domain); ?></h2>
                <p class="privacy-paragraph">
                    <strong><?php _e('Company Name:', $nbk_text_domain); ?></strong> Nord Booking<br>
                    <strong><?php _e('Email:', $nbk_text_domain); ?></strong> <a href="mailto:info@nordbk.com">info@nordbk.com</a><br>
                    <strong><?php _e('Data Protection Officer:', $nbk_text_domain); ?></strong> <a href="mailto:info@nordbk.com">info@nordbk.com</a>
                </p>

                <h2 class="privacy-subtitle"><?php _e('3. What Information We Collect', $nbk_text_domain); ?></h2>
                
                <h3 class="privacy-subsubtitle"><?php _e('3.1 Information You Provide Directly', $nbk_text_domain); ?></h3>
                <p class="privacy-paragraph"><?php _e('We collect information you provide when using our services:', $nbk_text_domain); ?></p>
                
                <h4><strong><?php _e('For Business Owners:', $nbk_text_domain); ?></strong></h4>
                <ul class="privacy-list">
                    <li><strong><?php _e('Account Information:', $nbk_text_domain); ?></strong> <?php _e('Name, email address, phone number, business name', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Business Details:', $nbk_text_domain); ?></strong> <?php _e('Company address, service descriptions, operating hours', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Subscription Data:', $nbk_text_domain); ?></strong> <?php _e('Billing information processed securely through Stripe', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Service Management:', $nbk_text_domain); ?></strong> <?php _e('Customer booking data you receive through your booking forms', $nbk_text_domain); ?></li>
                </ul>
                
                <h4><strong><?php _e('For Customers Making Bookings:', $nbk_text_domain); ?></strong></h4>
                <ul class="privacy-list">
                    <li><strong><?php _e('Contact Details:', $nbk_text_domain); ?></strong> <?php _e('Name, email address, phone number when making bookings', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Booking Information:', $nbk_text_domain); ?></strong> <?php _e('Appointment details, service preferences, special requests', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Communication:', $nbk_text_domain); ?></strong> <?php _e('Messages sent through our platform', $nbk_text_domain); ?></li>
                </ul>

                <h3 class="privacy-subsubtitle"><?php _e('3.2 Information Collected Automatically', $nbk_text_domain); ?></h3>
                <p class="privacy-paragraph"><?php _e('When you use our services, we automatically collect:', $nbk_text_domain); ?></p>
                <ul class="privacy-list">
                    <li><strong><?php _e('Usage Data:', $nbk_text_domain); ?></strong> <?php _e('Pages viewed, features used, time spent on platform, interaction patterns', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Device Information:', $nbk_text_domain); ?></strong> <?php _e('IP address, browser type, operating system, device identifiers', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Log Data:', $nbk_text_domain); ?></strong> <?php _e('Server logs, error reports, access times and dates', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Cookies and Tracking:', $nbk_text_domain); ?></strong> <?php _e('Information collected through cookies and similar technologies', $nbk_text_domain); ?></li>
                </ul>

                <div class="gdpr-box">
                    <h3><?php _e('GDPR Notice for EU Residents', $nbk_text_domain); ?></h3>
                    <p><?php _e('If you are located in the European Union (EU), European Economic Area (EEA), or Switzerland, you have additional rights under the General Data Protection Regulation (GDPR). Please see Section 8 for details about your rights and how to exercise them.', $nbk_text_domain); ?></p>
                </div>

                <h2 class="privacy-subtitle"><?php _e('4. Legal Basis for Processing (GDPR)', $nbk_text_domain); ?></h2>
                <p class="privacy-paragraph"><?php _e('For users in the EU/EEA, we process your personal data based on the following legal grounds:', $nbk_text_domain); ?></p>
                <ul class="privacy-list">
                    <li><strong><?php _e('Consent:', $nbk_text_domain); ?></strong> <?php _e('When you have given clear consent for specific processing activities', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Contract Performance:', $nbk_text_domain); ?></strong> <?php _e('To provide services you\'ve requested or fulfill contractual obligations', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Legitimate Interest:', $nbk_text_domain); ?></strong> <?php _e('For business operations, security, fraud prevention, and service improvement', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Legal Obligation:', $nbk_text_domain); ?></strong> <?php _e('To comply with applicable laws and regulations', $nbk_text_domain); ?></li>
                </ul>

                <h2 class="privacy-subtitle"><?php _e('5. How We Use Your Information', $nbk_text_domain); ?></h2>
                <p class="privacy-paragraph"><?php _e('We use the collected information for the following purposes:', $nbk_text_domain); ?></p>
                <ul class="privacy-list">
                    <li><?php _e('Provide, maintain, and improve our booking management platform', $nbk_text_domain); ?></li>
                    <li><?php _e('Process subscription payments through Stripe', $nbk_text_domain); ?></li>
                    <li><?php _e('Send booking confirmations and notifications', $nbk_text_domain); ?></li>
                    <li><?php _e('Provide customer support and respond to inquiries', $nbk_text_domain); ?></li>
                    <li><?php _e('Send important updates about our service (with your consent for marketing)', $nbk_text_domain); ?></li>
                    <li><?php _e('Monitor platform usage and improve user experience', $nbk_text_domain); ?></li>
                    <li><?php _e('Prevent fraud, abuse, and security incidents', $nbk_text_domain); ?></li>
                    <li><?php _e('Comply with legal obligations and enforce our terms', $nbk_text_domain); ?></li>
                </ul>

                <h2 class="privacy-subtitle"><?php _e('6. Information Sharing and Disclosure', $nbk_text_domain); ?></h2>
                <p class="privacy-paragraph"><?php _e('We may share your information in limited circumstances:', $nbk_text_domain); ?></p>
                <ul class="privacy-list">
                    <li><strong><?php _e('Service Providers:', $nbk_text_domain); ?></strong> <?php _e('With trusted partners like Stripe for payment processing and hosting providers', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Business Transfers:', $nbk_text_domain); ?></strong> <?php _e('In connection with mergers, acquisitions, or asset sales (with notice)', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Legal Requirements:', $nbk_text_domain); ?></strong> <?php _e('When required by law or to protect rights, property, or safety', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Your Consent:', $nbk_text_domain); ?></strong> <?php _e('With your explicit consent for specific sharing purposes', $nbk_text_domain); ?></li>
                </ul>

                <div class="important-box">
                    <strong><?php _e('Important:', $nbk_text_domain); ?></strong> 
                    <?php _e('We never sell, rent, or trade your personal information to third parties for their marketing purposes. Customer booking data belongs to the business owner and is shared only as necessary to facilitate the booking service.', $nbk_text_domain); ?>
                </div>

                <h2 class="privacy-subtitle"><?php _e('7. Data Security and Protection', $nbk_text_domain); ?></h2>
                <p class="privacy-paragraph"><?php _e('We implement strong security measures to protect your personal data:', $nbk_text_domain); ?></p>
                <ul class="privacy-list">
                    <li><?php _e('256-bit SSL encryption for all data transmission', $nbk_text_domain); ?></li>
                    <li><?php _e('Secure data storage with access controls and authentication', $nbk_text_domain); ?></li>
                    <li><?php _e('Regular security audits and vulnerability assessments', $nbk_text_domain); ?></li>
                    <li><?php _e('Staff training on data protection best practices', $nbk_text_domain); ?></li>
                    <li><?php _e('Incident response procedures for potential data breaches', $nbk_text_domain); ?></li>
                    <li><?php _e('Secure payment processing through Stripe (PCI DSS compliant)', $nbk_text_domain); ?></li>
                </ul>

                <h2 class="privacy-subtitle"><?php _e('8. Your Rights Under GDPR', $nbk_text_domain); ?></h2>
                <p class="privacy-paragraph"><?php _e('If you are a resident of the EU/EEA, you have the following rights:', $nbk_text_domain); ?></p>
                <ul class="privacy-list">
                    <li><strong><?php _e('Right of Access:', $nbk_text_domain); ?></strong> <?php _e('Request information about the personal data we hold about you', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Right to Rectification:', $nbk_text_domain); ?></strong> <?php _e('Request correction of inaccurate or incomplete data', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Right to Erasure:', $nbk_text_domain); ?></strong> <?php _e('Request deletion of your personal data (under certain conditions)', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Right to Restrict Processing:', $nbk_text_domain); ?></strong> <?php _e('Request limitation of processing in certain circumstances', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Right to Data Portability:', $nbk_text_domain); ?></strong> <?php _e('Receive your data in a portable, machine-readable format', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Right to Object:', $nbk_text_domain); ?></strong> <?php _e('Object to processing based on legitimate interests or direct marketing', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Right to Withdraw Consent:', $nbk_text_domain); ?></strong> <?php _e('Withdraw consent for consent-based processing at any time', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Right to Complain:', $nbk_text_domain); ?></strong> <?php _e('File a complaint with your local data protection authority', $nbk_text_domain); ?></li>
                </ul>
                <p class="privacy-paragraph">
                    <?php _e('To exercise any of these rights, please contact us at info@nordbk.com. We will respond to your request within 30 days.', $nbk_text_domain); ?>
                </p>

                <h2 class="privacy-subtitle"><?php _e('9. Data Retention', $nbk_text_domain); ?></h2>
                <p class="privacy-paragraph"><?php _e('We retain personal data only as long as necessary for the purposes outlined in this policy:', $nbk_text_domain); ?></p>
                <ul class="privacy-list">
                    <li><strong><?php _e('Business Accounts:', $nbk_text_domain); ?></strong> <?php _e('Until account deletion or 3 years after subscription ends', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Customer Booking Data:', $nbk_text_domain); ?></strong> <?php _e('2 years after booking completion or as required by business owner', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Transaction Records:', $nbk_text_domain); ?></strong> <?php _e('7 years for tax and accounting purposes', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Support Communications:', $nbk_text_domain); ?></strong> <?php _e('3 years after issue resolution', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Marketing Data:', $nbk_text_domain); ?></strong> <?php _e('Until consent is withdrawn or 2 years of inactivity', $nbk_text_domain); ?></li>
                </ul>

                <h2 class="privacy-subtitle"><?php _e('10. International Data Transfers', $nbk_text_domain); ?></h2>
                <p class="privacy-paragraph">
                    <?php _e('Your personal data may be transferred to and processed in countries outside your residence. For EU/EEA residents, we ensure adequate protection through:', $nbk_text_domain); ?>
                </p>
                <ul class="privacy-list">
                    <li><?php _e('EU Commission adequacy decisions where available', $nbk_text_domain); ?></li>
                    <li><?php _e('Standard Contractual Clauses (SCCs) approved by the EU Commission', $nbk_text_domain); ?></li>
                    <li><?php _e('Other appropriate safeguards as required by GDPR', $nbk_text_domain); ?></li>
                    <li><?php _e('Binding corporate rules for internal transfers', $nbk_text_domain); ?></li>
                </ul>

                <h2 class="privacy-subtitle"><?php _e('11. Cookies and Tracking Technologies', $nbk_text_domain); ?></h2>
                <p class="privacy-paragraph">
                    <?php _e('We use cookies and similar technologies to enhance user experience, analyze usage, and provide our services. You can manage cookie preferences through your browser settings. For detailed information about our cookie usage, please see our Cookie Policy.', $nbk_text_domain); ?>
                </p>

                <h2 class="privacy-subtitle"><?php _e('12. Children\'s Privacy', $nbk_text_domain); ?></h2>
                <p class="privacy-paragraph">
                    <?php _e('Our services are not directed to children under 16 years of age. We do not knowingly collect personal information from children under 16. If we become aware that we have collected such information, we will take steps to delete it promptly and notify parents/guardians as required.', $nbk_text_domain); ?>
                </p>

                <h2 class="privacy-subtitle"><?php _e('13. Changes to This Privacy Policy', $nbk_text_domain); ?></h2>
                <p class="privacy-paragraph"><?php _e('We may update this privacy policy from time to time. If we make significant changes, we will notify you by:', $nbk_text_domain); ?></p>
                <ul class="privacy-list">
                    <li><?php _e('Posting the updated policy on our website with a new "Last Updated" date', $nbk_text_domain); ?></li>
                    <li><?php _e('Sending an email notification for material changes affecting your rights', $nbk_text_domain); ?></li>
                    <li><?php _e('Providing prominent notice on our platform for significant policy updates', $nbk_text_domain); ?></li>
                </ul>
                <p class="privacy-paragraph">
                    <?php _e('Continued use of our services after changes constitute acceptance of the updated policy.', $nbk_text_domain); ?>
                </p>

                <h2 class="privacy-subtitle"><?php _e('14. Third-Party Services', $nbk_text_domain); ?></h2>
                <p class="privacy-paragraph"><?php _e('Our platform integrates with third-party services that have their own privacy policies:', $nbk_text_domain); ?></p>
                <ul class="privacy-list">
                    <li><strong><?php _e('Stripe:', $nbk_text_domain); ?></strong> <?php _e('Payment processing (view their privacy policy at stripe.com/privacy)', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Analytics Services:', $nbk_text_domain); ?></strong> <?php _e('Website usage analytics to improve our service', $nbk_text_domain); ?></li>
                    <li><strong><?php _e('Email Services:', $nbk_text_domain); ?></strong> <?php _e('Automated booking confirmations and notifications', $nbk_text_domain); ?></li>
                </ul>

                <div class="contact-box">
                    <h2 class="privacy-subtitle"><?php _e('15. Contact Information', $nbk_text_domain); ?></h2>
                    <p class="privacy-paragraph">
                        <?php _e('Questions about this privacy policy or our data practices? We\'re here to help:', $nbk_text_domain); ?>
                    </p>
                    <p class="privacy-paragraph">
                        <strong><?php _e('General Privacy Questions:', $nbk_text_domain); ?></strong> 
                        <a href="mailto:info@nordbk.com">info@nordbk.com</a>
                    </p>
                    <p class="privacy-paragraph">
                        <strong><?php _e('Data Protection Officer:', $nbk_text_domain); ?></strong> 
                        <a href="mailto:info@nordbk.com">info@nordbk.com</a>
                    </p>
                    <p class="privacy-paragraph">
                        <strong><?php _e('Support Team:', $nbk_text_domain); ?></strong> 
                        <a href="mailto:support@nordbk.com">support@nordbk.com</a>
                    </p>
                    
                    <h3 class="privacy-subsubtitle"><?php _e('GDPR Data Requests', $nbk_text_domain); ?></h3>
                    <p class="privacy-paragraph">
                        <?php _e('For GDPR-related requests (access, correction, deletion, etc.), please email our Data Protection Officer directly with "GDPR Request" in the subject line. We will respond within 30 days.', $nbk_text_domain); ?>
                    </p>
                    
                    <h3 class="privacy-subsubtitle"><?php _e('Supervisory Authority', $nbk_text_domain); ?></h3>
                    <p class="privacy-paragraph">
                        <?php _e('EU/EEA residents have the right to complain to their local data protection authority if you believe we have not addressed your privacy concerns adequately.', $nbk_text_domain); ?>
                    </p>
                </div>

                <p class="privacy-paragraph" style="margin-top: 2rem; font-size: 0.875rem; color: hsl(var(--muted-foreground));">
                    <?php _e('This privacy policy is designed to be clear, comprehensive, and compliant with GDPR, CCPA, and other applicable privacy regulations. We are committed to transparency and protecting your privacy rights.', $nbk_text_domain); ?>
                </p>
            </div>
        </div>
    </main>

    <?php get_footer(); ?>