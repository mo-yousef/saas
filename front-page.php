<?php
/**
 * The template for displaying the front page.
 *
 * @package Nord Booking
 */

get_header();
?>

<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/assets/css/new-front-page.css">

<header class="wallex-header">
    <div class="container">
        <nav class="wallex-nav">
            <div class="wallex-logo">
                <a href="<?php echo esc_url(home_url('/')); ?>">Nord Booking</a>
            </div>
            <ul class="wallex-nav-links">
                <li><a href="#features">Features</a></li>
                <li><a href="#pricing">Pricing</a></li>
                <li><a href="#testimonials">Testimonials</a></li>
                <li><a href="#faq">FAQ</a></li>
            </ul>
            <div class="wallex-header-actions">
                <a href="<?php echo esc_url(home_url('/login/')); ?>" class="btn btn-secondary">Login</a>
                <a href="<?php echo esc_url(home_url('/register/')); ?>" class="btn btn-primary">Sign Up</a>
            </div>
        </nav>
    </div>
</header>

<main>
    <section class="wallex-hero">
        <div class="container">
            <div class="wallex-hero-content">
                <h1>Streamline Your Cleaning Business with Our Powerful SaaS Platform</h1>
                <p>Nord Booking is the all-in-one software for cleaning service companies. Automate your bookings, manage your customers, and grow your revenue with our intuitive and powerful platform.</p>
                <div class="wallex-hero-actions">
                    <a href="<?php echo esc_url(home_url('/register/')); ?>" class="btn btn-primary">Start Your Free Trial</a>
                    <a href="#features" class="btn btn-secondary">Explore Features</a>
                </div>
            </div>
            <div class="wallex-hero-image">
                <img src="https://fse.jegtheme.com/wallex/wp-content/uploads/sites/80/2025/03/hero-image.webp" alt="Nord Booking Dashboard Preview">
            </div>
        </div>
    </section>

    <section id="features" class="wallex-features">
        <div class="container">
            <div class="wallex-section-header">
                <h2>A Feature-Rich Platform to Grow Your Cleaning Business</h2>
            </div>
            <div class="wallex-features-grid">
                <div class="wallex-feature-card">
                    <h3>Automated Online Booking</h3>
                    <p>Enable your customers to book your cleaning services 24/7 with a beautiful, mobile-friendly booking form. Reduce no-shows with automated reminders.</p>
                </div>
                <div class="wallex-feature-card">
                    <h3>Customer Management (CRM)</h3>
                    <p>Keep all your customer information in one place. Track booking history, preferences, and contact details to provide a personalized service.</p>
                </div>
                <div class="wallex-feature-card">
                    <h3>Team Scheduling</h3>
                    <p>Manage your cleaning staff's schedules with ease. Assign jobs, track availability, and ensure your team is always in the right place at the right time.</p>
                </div>
                <div class="wallex-feature-card">
                    <h3>Invoicing and Payments</h3>
                    <p>Generate professional invoices and accept online payments securely. Integrate with Stripe and PayPal to get paid faster.</p>
                </div>
                <div class="wallex-feature-card">
                    <h3>Reporting and Analytics</h3>
                    <p>Make data-driven decisions with our comprehensive reporting and analytics tools. Track your revenue, bookings, and customer growth.</p>
                </div>
                <div class="wallex-feature-card">
                    <h3>Service Area Management</h3>
                    <p>Define your service areas and set custom pricing for different locations. Ensure you only get bookings from customers in your service area.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="pricing" class="wallex-pricing">
        <div class="container">
            <div class="wallex-section-header">
                <h2>Simple, Transparent Pricing for Your Cleaning Business</h2>
            </div>
            <div class="wallex-pricing-grid">
                <div class="wallex-pricing-card">
                    <h3>Starter</h3>
                    <p class="price">$29<span>/Month</span></p>
                    <ul>
                        <li>Up to 50 bookings per month</li>
                        <li>Online Booking Form</li>
                        <li>Customer Management</li>
                        <li>Email Support</li>
                    </ul>
                    <a href="<?php echo esc_url(home_url('/register/')); ?>" class="btn btn-secondary">Choose Plan</a>
                </div>
                <div class="wallex-pricing-card popular">
                    <h3>Pro</h3>
                    <p class="price">$79<span>/Month</span></p>
                    <ul>
                        <li>Unlimited Bookings</li>
                        <li>All Starter Features</li>
                        <li>Team Scheduling</li>
                        <li>Invoicing and Payments</li>
                        <li>Priority Support</li>
                    </ul>
                    <a href="<?php echo esc_url(home_url('/register/')); ?>" class="btn btn-primary">Choose Plan</a>
                </div>
                <div class="wallex-pricing-card">
                    <h3>Business</h3>
                    <p class="price">$149<span>/Month</span></p>
                    <ul>
                        <li>Unlimited Bookings</li>
                        <li>All Pro Features</li>
                        <li>Reporting and Analytics</li>
                        <li>Service Area Management</li>
                        <li>Phone Support</li>
                    </ul>
                    <a href="<?php echo esc_url(home_url('/register/')); ?>" class="btn btn-secondary">Choose Plan</a>
                </div>
            </div>
        </div>
    </section>

    <section id="testimonials" class="wallex-testimonials">
        <div class="container">
            <div class="wallex-section-header">
                <h2>Trusted by Cleaning Businesses Worldwide</h2>
            </div>
            <div class="wallex-testimonials-grid">
                <div class="wallex-testimonial-card">
                    <p>"Nord Booking has been a game-changer for our cleaning business. We've seen a 40% increase in bookings since we started using it. The automated booking and scheduling features save us so much time."</p>
                    <div class="author">
                        <p><strong>Sarah Johnson</strong>, CEO of Clean Co.</p>
                    </div>
                </div>
                <div class="wallex-testimonial-card">
                    <p>"The customer support is amazing. They helped us get set up in no time. Our customers love how easy it is to book our services online. Highly recommended for any cleaning business."</p>
                    <div class="author">
                        <p><strong>Mike Williams</strong>, Owner of Maid for You</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="faq" class="wallex-faq">
        <div class="container">
            <div class="wallex-section-header">
                <h2>Frequently Asked Questions</h2>
            </div>
            <div class="wallex-faq-grid">
                <div class="wallex-faq-item">
                    <h3>Is there a free trial?</h3>
                    <p>Yes, we offer a 14-day free trial on all our plans. No credit card is required to get started. You can explore all the features and see how Nord Booking can help your business.</p>
                </div>
                <div class="wallex-faq-item">
                    <h3>Can I use my own domain name?</h3>
                    <p>Absolutely! You can use your own domain name with Nord Booking. We also provide you with a free subdomain to get you started right away.</p>
                </div>
                <div class="wallex-faq-item">
                    <h3>What payment gateways do you support?</h3>
                    <p>We support Stripe and PayPal for secure online payments. You can easily connect your existing accounts to start accepting payments from your customers.</p>
                </div>
                <div class="wallex-faq-item">
                    <h3>Can I cancel my subscription at any time?</h3>
                    <p>Yes, you can cancel your subscription at any time. There are no long-term contracts or hidden fees. You have full control over your subscription.</p>
                </div>
            </div>
        </div>
    </section>
</main>

<footer class="wallex-footer">
    <div class="container">
        <div class="wallex-footer-content">
            <p>&copy; <?php echo date('Y'); ?> Nord Booking. All Rights Reserved. | <a href="#">Privacy Policy</a> | <a href="#">Terms of Service</a></p>
        </div>
    </div>
</footer>

<?php
get_footer();
?>
