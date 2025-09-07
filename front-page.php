<?php
/**
 * The template for displaying the front page.
 *
 * @package Nord Booking
 */

get_header();
?>
</head>
<body>


    <main>
        <!-- Hero Section -->
        <section class="hero">
            <div class="container">
                <div class="hero-badge fade-in">
                    ✨ Trusted by 1000+ cleaning businesses
                </div>
                
                <h1 class="hero-title slide-up">
                    Manage Your Cleaning Business Online
                </h1>
                
                <p class="hero-description slide-up delay-100">
                    The ultimate SaaS platform for cleaning service companies. Streamline bookings, manage customers, and grow your business with our all-in-one solution.
                </p>

                <div class="hero-actions slide-up delay-200">
                    <a href="<?php echo esc_url(home_url('/register/')); ?>" class="btn btn-primary btn-xl">Start Free Trial</a>
                    <a href="/dashboard" class="btn btn-outline btn-xl">Book Demo</a> <!-- Assuming /dashboard or a contact page for demo -->
                </div>

                <div class="hero-stats fade-in delay-300">
                    <div class="stat">
                        <div class="stat-number">10,000+</div>
                        <div class="stat-label">Bookings Processed</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number">500+</div>
                        <div class="stat-label">Active Businesses</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number">99.9%</div>
                        <div class="stat-label">Uptime</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="section">
            <div class="container">
                <div class="section-header">
                    <div class="section-badge">Features</div>
                    <h2 class="section-title">Everything you need to succeed</h2>
                    <p class="section-description">
                        Powerful features designed specifically for cleaning businesses to streamline operations and boost growth.
                    </p>
                </div>

                <div class="features-grid">
                    <div class="card feature-card slide-up">
                        <div class="feature-icon">🏢</div>
                        <h3 class="feature-title">Multi-Tenant System</h3>
                        <p class="feature-description">
                            Manage multiple cleaning businesses from one dashboard. Perfect for franchises or multi-location operations with centralized control.
                        </p>
                    </div>

                    <div class="card feature-card slide-up delay-100">
                        <div class="feature-icon">🛠️</div>
                        <h3 class="feature-title">Service Management</h3>
                        <p class="feature-description">
                            Easily create, edit, and manage all your cleaning services with custom pricing, duration settings, and detailed descriptions.
                        </p>
                    </div>

                    <div class="card feature-card slide-up delay-200">
                        <div class="feature-icon">📅</div>
                        <h3 class="feature-title">Online Booking Form</h3>
                        <p class="feature-description">
                            Let customers book services 24/7 with beautiful, mobile-responsive booking forms that convert visitors into customers.
                        </p>
                    </div>

                    <div class="card feature-card slide-up delay-300">
                        <div class="feature-icon">📊</div>
                        <h3 class="feature-title">Business Dashboard</h3>
                        <p class="feature-description">
                            Track bookings, revenue, customer data, and business performance with comprehensive analytics and reporting tools.
                        </p>
                    </div>

                    <div class="card feature-card slide-up delay-400">
                        <div class="feature-icon">🗺️</div>
                        <h3 class="feature-title">Area Management</h3>
                        <p class="feature-description">
                            Define service areas, set different pricing zones, and optimize your service coverage for maximum efficiency.
                        </p>
                    </div>

                    <div class="card feature-card slide-up delay-500">
                        <div class="feature-icon">🎫</div>
                        <h3 class="feature-title">Discount Codes</h3>
                        <p class="feature-description">
                            Create promotional campaigns with flexible discount codes to attract new customers and retain existing ones.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works -->
        <section id="how-it-works" class="section">
            <div class="container">
                <div class="section-header">
                    <div class="section-badge">How It Works</div>
                    <h2 class="section-title">Get started in minutes</h2>
                    <p class="section-description">
                        Simple setup process that gets your cleaning business online and accepting bookings quickly.
                    </p>
                </div>

                <div class="steps-grid">
                    <div class="step slide-up">
                        <div class="step-number">1</div>
                        <h3 class="step-title">Sign Up & Setup</h3>
                        <p class="step-description">
                            Create your account, add your business details, and configure your services in just a few clicks. No technical knowledge required.
                        </p>
                    </div>

                    <div class="step slide-up delay-100">
                        <div class="step-number">2</div>
                        <h3 class="step-title">Customize & Launch</h3>
                        <p class="step-description">
                            Personalize your booking form, set your service areas, pricing, and embed it on your website or share the direct link.
                        </p>
                    </div>

                    <div class="step slide-up delay-200">
                        <div class="step-number">3</div>
                        <h3 class="step-title">Manage & Grow</h3>
                        <p class="step-description">
                            Start receiving bookings instantly, manage your schedule through the dashboard, and watch your business grow.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing -->
        <section id="pricing" class="section">
            <div class="container">
                <div class="section-header">
                    <div class="section-badge">Pricing</div>
                    <h2 class="section-title">Simple, transparent pricing</h2>
                    <p class="section-description">
                        Choose the plan that fits your business size and needs. All plans include our core features with no hidden fees.
                    </p>
                </div>

                <div class="pricing-grid">
                    <div class="card pricing-card slide-up">
                        <div class="card-header">
                            <h3 class="plan-name">Starter</h3>
                            <div class="plan-price">$29</div>
                            <div class="plan-period">per month</div>
                        </div>
                        <div class="card-content">
                            <ul class="plan-features">
                                <li>Up to 100 bookings/month</li>
                                <li>Basic dashboard & reporting</li>
                                <li>Email support</li>
                                <li>Mobile-responsive booking forms</li>
                                <li>Customer management</li>
                                <li>Basic integrations</li>
                            </ul>
                            <a href="<?php echo esc_url(home_url('/register/')); ?>" class="btn btn-outline btn-lg width-100">Choose Starter</a>
                        </div>
                    </div>

                    <div class="card pricing-card slide-up delay-100">
                        <div class="pricing-badge">Most Popular</div>
                        <div class="card-header">
                            <h3 class="plan-name">Professional</h3>
                            <div class="plan-price">$79</div>
                            <div class="plan-period">per month</div>
                        </div>
                        <div class="card-content">
                            <ul class="plan-features">
                                <li>Unlimited bookings</li>
                                <li>Advanced dashboard & analytics</li>
                                <li>Priority support</li>
                                <li>Custom branding</li>
                                <li>Area management</li>
                                <li>Discount codes & promotions</li>
                                <li>WooCommerce & Stripe integration</li>
                            </ul>
                            <a href="<?php echo esc_url(home_url('/register/')); ?>" class="btn btn-primary btn-lg width-100">Choose Professional</a>
                        </div>
                    </div>

                    <div class="card pricing-card slide-up delay-200">
                        <div class="card-header">
                            <h3 class="plan-name">Enterprise</h3>
                            <div class="plan-price">$199</div>
                            <div class="plan-period">per month</div>
                        </div>
                        <div class="card-content">
                            <ul class="plan-features">
                                <li>Everything in Professional</li>
                                <li>Multi-tenant system</li>
                                <li>White-label solution</li>
                                <li>API access</li>
                                <li>Dedicated account manager</li>
                                <li>Custom integrations</li>
                                <li>Advanced security features</li>
                            </ul>
                            <a href="/dashboard" class="btn btn-outline btn-lg width-100">Contact Sales</a> <!-- Assuming /dashboard or contact page -->
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimonials -->
        <section id="testimonials" class="section">
            <div class="container">
                <div class="section-header">
                    <div class="section-badge">Testimonials</div>
                    <h2 class="section-title">What our customers say</h2>
                    <p class="section-description">
                        Join thousands of cleaning businesses that have transformed their operations with Nord Booking.
                    </p>
                </div>

                <div class="testimonials-grid">
                    <div class="card testimonial slide-up delay-100">
                        <div class="card-content">
                            <p class="testimonial-content">
                                "The multi-tenant feature is perfect for our franchise operations. We can manage all locations from one place while giving each franchise owner their own dashboard. Game changer for our business!"
                            </p>
                            <div class="testimonial-author">
                                <div class="author-avatar">MJ</div>
                                <div>
                                    <div class="author-name">Michael Johnson</div>
                                    <div class="author-title">CEO, CleanPro Franchises</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card testimonial slide-up delay-200">
                        <div class="card-content">
                            <p class="testimonial-content">
                                "Outstanding customer support and the platform is so easy to use. We've seen a 150% increase in revenue since switching to Nord Booking. Our customers love how simple it is to book our services online."
                            </p>
                            <div class="testimonial-author">
                                <div class="author-avatar">LR</div>
                                <div>
                                    <div class="author-name">Lisa Rodriguez</div>
                                    <div class="author-title">Manager, Elite Cleaning Co.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Integration Section -->
        <section class="section">
            <div class="container">
                <div class="section-header">
                    <div class="section-badge">Integrations</div>
                    <h2 class="section-title">Seamlessly connects with your tools</h2>
                    <p class="section-description">
                        Nord Booking integrates with the platforms you already use to streamline your workflow.
                    </p>
                </div>

                <div class="features-grid">
                    <div class="card feature-card slide-up">
                        <div class="feature-icon">💳</div>
                        <h3 class="feature-title">WooCommerce Integration</h3>
                        <p class="feature-description">
                            Seamlessly integrate with your existing WooCommerce store to manage bookings alongside your products.
                        </p>
                    </div>

                    <div class="card feature-card slide-up delay-100">
                        <div class="feature-icon">💰</div>
                        <h3 class="feature-title">Stripe Payments</h3>
                        <p class="feature-description">
                            Accept secure payments online with Stripe integration. Support for credit cards, digital wallets, and more.
                        </p>
                    </div>

                    <div class="card feature-card slide-up delay-200">
                        <div class="feature-icon">📧</div>
                        <h3 class="feature-title">Email Automation</h3>
                        <p class="feature-description">
                            Automated booking confirmations, reminders, and follow-up emails to keep customers engaged.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="section">
            <div class="container">
                <div class="section-header">
                    <div class="section-badge">FAQ</div>
                    <h2 class="section-title">Frequently asked questions</h2>
                    <p class="section-description">
                        Everything you need to know about Nord Booking and how it can help your cleaning business.
                    </p>
                </div>

                <div class="max-width-48 margin-auto">
                    <div class="card slide-up margin-bottom-1">
                        <div class="card-content">
                            <h3 class="font-weight-600 margin-bottom-half">How quickly can I get started?</h3>
                            <p class="color-muted margin-0">You can set up your account and start accepting bookings within 10 minutes. Our onboarding process is designed to be simple and straightforward.</p>
                        </div>
                    </div>

                    <div class="card slide-up delay-100 margin-bottom-1">
                        <div class="card-content">
                            <h3 class="font-weight-600 margin-bottom-half">Do I need technical skills to use Nord Booking?</h3>
                            <p class="color-muted margin-0">Not at all! Nord Booking is designed for business owners, not developers. Everything is point-and-click with no coding required.</p>
                        </div>
                    </div>

                    <div class="card slide-up delay-200 margin-bottom-1">
                        <div class="card-content">
                            <h3 class="font-weight-600 margin-bottom-half">Can I customize the booking form to match my brand?</h3>
                            <p class="color-muted margin-0">Yes! Professional and Enterprise plans include custom branding options to match your business colors, logo, and style.</p>
                        </div>
                    </div>

                    <div class="card slide-up delay-300 margin-bottom-1">
                        <div class="card-content">
                            <h3 class="font-weight-600 margin-bottom-half">What payment methods are supported?</h3>
                            <p class="color-muted margin-0">Through our Stripe integration, we support all major credit cards, digital wallets like Apple Pay and Google Pay, and bank transfers.</p>
                        </div>
                    </div>

                    <div class="card slide-up delay-400">
                        <div class="card-content">
                            <h3 class="font-weight-600 margin-bottom-half">Is there a free trial?</h3>
                            <p class="color-muted margin-0">Yes! We offer a 14-day free trial with no credit card required. You can explore all features and see how Nord Booking works for your business.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Final CTA -->
        <section class="cta-section">
            <div class="container">
                <div class="hero-badge fade-in">
                    🚀 Join 500+ successful cleaning businesses
                </div>
                
                <h2 class="section-title slide-up">
                    Ready to transform your cleaning business?
                </h2>
                
                <p class="section-description slide-up delay-100">
                    Start your free trial today and see how Nord Booking can help you streamline operations and grow your revenue.
                </p>

                <div class="hero-actions slide-up delay-200">
                    <a href="<?php echo esc_url(home_url('/register/')); ?>" class="btn btn-primary btn-xl">Start Free Trial</a>
                    <a href="/dashboard" class="btn btn-outline btn-xl">Schedule Demo</a> <!-- Assuming /dashboard or contact page -->
                </div>

                <p class="margin-top-1 font-size-sm color-muted">
                    No credit card required • 14-day free trial • Cancel anytime
                </p>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-section">
                    <h3>Nord Booking</h3>
                    <p class="color-muted margin-bottom-1">The ultimate SaaS platform for cleaning service companies. Streamline your bookings, manage customers, and grow your business.</p>
                    <div class="flex gap-1">
                        <a href="/dashboard" class="color-muted text-decoration-none">Twitter</a>
                        <a href="/dashboard" class="color-muted text-decoration-none">LinkedIn</a>
                        <a href="/dashboard" class="color-muted text-decoration-none">Facebook</a>
                    </div>
                </div>

                <div class="footer-section">
                    <h3>Product</h3>
                    <ul>
                        <li><a href="/dashboard">Features</a></li>
                        <li><a href="/dashboard">Pricing</a></li>
                        <li><a href="/dashboard">API</a></li>
                        <li><a href="/dashboard">Integrations</a></li>
                        <li><a href="/dashboard">Changelog</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h3>Company</h3>
                    <ul>
                        <li><a href="/dashboard">About</a></li>
                        <li><a href="/dashboard">Blog</a></li>
                        <li><a href="/dashboard">Careers</a></li>
                        <li><a href="/dashboard">Press</a></li>
                        <li><a href="/dashboard">Partners</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h3>Support</h3>
                    <ul>
                        <li><a href="/dashboard">Help Center</a></li>
                        <li><a href="/dashboard">Contact Us</a></li>
                        <li><a href="/dashboard">Status</a></li>
                        <li><a href="/dashboard">Privacy Policy</a></li>
                        <li><a href="/dashboard">Terms of Service</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; 2024 Nord Booking. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Simple intersection observer for animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animationPlayState = 'running';
                }
            });
        }, observerOptions);

        // Observe all animated elements
        document.querySelectorAll('.fade-in, .slide-up').forEach(el => {
            el.style.animationPlayState = 'paused';
            observer.observe(el);
        });

        // Mobile menu toggle (basic implementation)
        document.querySelector('.mobile-menu-toggle').addEventListener('click', function() {
            const navLinks = document.querySelector('.nav-links');
            navLinks.style.display = navLinks.style.display === 'flex' ? 'none' : 'flex';
        });
    </script>
<?php
get_footer();
