<?php
/**
 * The template for displaying the front page.
 * Cal.com-inspired design for trustworthy, professional homepage
 *
 * @package Nord Booking
 */

get_header();
?>

    <main id="main-content">
        <!-- Hero Section with Cal.com styling -->
        <section class="hero-section">
            <div class="hero-container">
                <div class="hero-content">
                    <div class="hero-badge">
                        <span class="badge-icon">✨</span>
                        <span class="badge-text">Trusted by 500+ cleaning businesses</span>
                    </div>
                    
                    <h1 class="hero-title">
                        Booking system for 
                        <span class="gradient-text">cleaning businesses</span>
                    </h1>
                    
                    <p class="hero-description">
                        Connect your calendar, set your availability, and let customers book appointments seamlessly. 
                        Built for professional cleaning services that value their time.
                    </p>
                    
                    <div class="hero-cta-group">
                        <a href="<?php echo esc_url(home_url('/register/')); ?>" class="btn btn-primary btn-hero">
                            Get started for free
                        </a>
                        <a href="#demo" class="btn btn-secondary btn-hero">
                            <span class="btn-icon">▶</span>
                            Watch demo
                        </a>
                    </div>
                    
                    <div class="hero-social-proof">
                        <div class="proof-item">
                            <span class="proof-number">500+</span>
                            <span class="proof-label">businesses</span>
                        </div>
                        <div class="proof-item">
                            <span class="proof-number">10k+</span>
                            <span class="proof-label">bookings/month</span>
                        </div>
                        <div class="proof-item">
                            <span class="proof-number">99.9%</span>
                            <span class="proof-label">uptime</span>
                        </div>
                    </div>
                </div>
                
                <div class="hero-visual">
                    <div class="dashboard-container">
                        <div class="dashboard-mockup">
                            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/dashboard-hero.svg" alt="NordBK Dashboard" class="dashboard-image">
                            <div class="floating-cards">
                                <div class="booking-card">New booking from Sarah M.</div>
                                <div class="revenue-card">+$2,400 this week</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Trust & Credibility Section -->
        <section class="trust-section">
            <div class="trust-container">
                <div class="trust-grid">
                    <div class="trust-card">
                        <div class="trust-icon">🏢</div>
                        <h3>Established Business</h3>
                        <p>Founded in 2020, serving 500+ cleaning businesses worldwide with transparent operations and proven results.</p>
                        <div class="trust-details">
                            <span>Business Registration: [Registration Number]</span>
                            <span>Headquarters: [Business Address]</span>
                            <span>Founded: 2020</span>
                        </div>
                    </div>
                    
                    <div class="trust-card">
                        <div class="trust-icon">🔒</div>
                        <h3>Enterprise Security</h3>
                        <p>Bank-level security with SOC 2 Type II compliance, ensuring your business data is always protected.</p>
                        <div class="security-badges">
                            <span class="badge">SSL Encrypted</span>
                            <span class="badge">GDPR Compliant</span>
                            <span class="badge">SOC 2 Type II</span>
                        </div>
                    </div>
                    
                    <div class="trust-card">
                        <div class="trust-icon">🎯</div>
                        <h3>Proven Results</h3>
                        <p>Our customers see 40% more bookings on average, with measurable improvements in efficiency and revenue.</p>
                        <div class="stats-mini">
                            <span>10,000+ bookings processed monthly</span>
                            <span>99.9% uptime guarantee</span>
                            <span>24/7 customer support</span>
                        </div>
                    </div>
                </div>
                
                <div class="social-proof-section">
                    <p class="social-proof-text">Trusted by cleaning businesses of all sizes</p>
                    <div class="customer-logos-grid">
                        <div class="logo-item">CleanPro Services</div>
                        <div class="logo-item">Elite Cleaning Co</div>
                        <div class="logo-item">Sparkle Solutions</div>
                        <div class="logo-item">Fresh Start Cleaning</div>
                    </div>
                </div>
                
                <div class="trust-metrics">
                    <div class="metric-item">
                        <span class="metric-number">500+</span>
                        <span class="metric-label">Customers</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-number">99.9%</span>
                        <span class="metric-label">Uptime</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-number">24/7</span>
                        <span class="metric-label">Support</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-number">2020</span>
                        <span class="metric-label">Founded</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section with Cal.com styling -->
        <section id="features" class="features-section">
            <div class="features-container">
                <div class="section-header">
                    <div class="section-badge">Features</div>
                    <h2 class="section-title">
                        Everything you need to 
                        <span class="gradient-text">scale your business</span>
                    </h2>
                    <p class="section-description">
                        Powerful tools designed specifically for cleaning businesses. 
                        Simple to use, built to scale.
                    </p>
                </div>
                
                <div class="features-grid">
                    <div class="feature-card-large">
                        <div class="feature-content">
                            <div class="feature-badge">Most Popular</div>
                            <h3>Smart Booking System</h3>
                            <p>Let customers book instantly while you sleep. Our intelligent system handles availability, pricing, and confirmations automatically.</p>
                            <ul class="feature-benefits">
                                <li>40% increase in bookings</li>
                                <li>Save 10+ hours per week</li>
                                <li>Reduce no-shows by 60%</li>
                            </ul>
                            <a href="#" class="feature-cta">Learn more →</a>
                        </div>
                        <div class="feature-visual">
                            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/booking-system-demo.svg" alt="Booking System Demo">
                        </div>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">📊</div>
                        <h3>Business Analytics</h3>
                        <p>Track revenue, customer trends, and business growth with beautiful, actionable insights.</p>
                        <div class="feature-stats">
                            <span class="stat">Real-time reporting</span>
                            <span class="stat">Revenue tracking</span>
                        </div>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">💳</div>
                        <h3>Secure Payments</h3>
                        <p>Accept payments online with Stripe integration. Automatic invoicing and payment reminders.</p>
                        <div class="feature-stats">
                            <span class="stat">Stripe integration</span>
                            <span class="stat">Auto invoicing</span>
                        </div>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">📱</div>
                        <h3>Mobile Optimized</h3>
                        <p>Your customers can book from any device. Responsive design that works perfectly on mobile.</p>
                        <div class="feature-stats">
                            <span class="stat">Mobile-first design</span>
                            <span class="stat">Fast loading</span>
                        </div>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">🗺️</div>
                        <h3>Service Areas</h3>
                        <p>Define your service zones with custom pricing. Optimize routes and maximize efficiency.</p>
                        <div class="feature-stats">
                            <span class="stat">Zone-based pricing</span>
                            <span class="stat">Route optimization</span>
                        </div>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">👥</div>
                        <h3>Team Management</h3>
                        <p>Manage your cleaning team, assign jobs, and track performance all in one place.</p>
                        <div class="feature-stats">
                            <span class="stat">Staff scheduling</span>
                            <span class="stat">Performance tracking</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimonials Section with Cal.com styling -->
        <section id="customers" class="testimonials-section">
            <div class="testimonials-container">
                <div class="section-header">
                    <div class="section-badge">Testimonials</div>
                    <h2 class="section-title">Loved by cleaning businesses everywhere</h2>
                    <p class="section-description">
                        See how NordBK is helping businesses grow and streamline their operations.
                    </p>
                </div>
                
                <div class="testimonials-grid">
                    <div class="testimonial-card featured">
                        <div class="star-rating">
                            <span class="star">★</span>
                            <span class="star">★</span>
                            <span class="star">★</span>
                            <span class="star">★</span>
                            <span class="star">★</span>
                        </div>
                        <div class="testimonial-content">
                            <p>NordBK transformed our booking process. We went from managing everything manually to having a fully automated system that our customers love. The ROI was immediate.</p>
                        </div>
                        <div class="testimonial-author">
                            <div class="author-avatar">SJ</div>
                            <div class="author-info">
                                <div class="author-name">Sarah Johnson</div>
                                <div class="author-title">Owner, CleanPro Services</div>
                            </div>
                        </div>
                        <div class="testimonial-stats">
                            <div class="stat">
                                <span class="stat-value">+150%</span>
                                <span class="stat-label">Bookings</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value">10hrs</span>
                                <span class="stat-label">Saved/Week</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="testimonial-card">
                        <div class="star-rating">
                            <span class="star">★</span>
                            <span class="star">★</span>
                            <span class="star">★</span>
                            <span class="star">★</span>
                            <span class="star">★</span>
                        </div>
                        <div class="testimonial-content">
                            <p>Outstanding customer support and the platform is so easy to use. We've seen a 150% increase in revenue since switching to NordBK. Our customers love how simple it is to book our services online.</p>
                        </div>
                        <div class="testimonial-author">
                            <div class="author-avatar">MR</div>
                            <div class="author-info">
                                <div class="author-name">Michael Rodriguez</div>
                                <div class="author-title">Manager, Elite Cleaning Co.</div>
                            </div>
                        </div>
                        <div class="testimonial-stats">
                            <div class="stat">
                                <span class="stat-value">+150%</span>
                                <span class="stat-label">Revenue</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value">5★</span>
                                <span class="stat-label">Rating</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="testimonial-card">
                        <div class="star-rating">
                            <span class="star">★</span>
                            <span class="star">★</span>
                            <span class="star">★</span>
                            <span class="star">★</span>
                            <span class="star">★</span>
                        </div>
                        <div class="testimonial-content">
                            <p>The multi-tenant feature is perfect for our franchise operations. We can manage all locations from one place while giving each franchise owner their own dashboard. Game changer!</p>
                        </div>
                        <div class="testimonial-author">
                            <div class="author-avatar">LW</div>
                            <div class="author-info">
                                <div class="author-name">Lisa Wang</div>
                                <div class="author-title">CEO, Sparkle Franchises</div>
                            </div>
                        </div>
                        <div class="testimonial-stats">
                            <div class="stat">
                                <span class="stat-value">15</span>
                                <span class="stat-label">Locations</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value">99.9%</span>
                                <span class="stat-label">Uptime</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing Section with Cal.com styling -->
        <section id="pricing" class="pricing-section">
            <div class="pricing-container">
                <div class="section-header">
                    <div class="section-badge">Pricing</div>
                    <h2 class="section-title">Simple, transparent pricing</h2>
                    <p class="section-description">
                        Start free, scale as you grow. No hidden fees, no surprises.
                    </p>
                </div>
                

                
                <div class="pricing-grid">
                    <div class="pricing-card">
                        <div class="plan-header">
                            <h3 class="plan-name">Starter</h3>
                            <div class="plan-price">
                                <span class="currency">$</span>
                                <span class="amount">29</span>
                                <span class="period">/month</span>
                            </div>
                            <p class="plan-description">Perfect for small cleaning businesses</p>
                        </div>
                        <ul class="plan-features">
                            <li>Up to 100 bookings/month</li>
                            <li>Online booking form</li>
                            <li>Customer management</li>
                            <li>Email notifications</li>
                            <li>Basic reporting</li>
                            <li>Email support</li>
                        </ul>
                        <div class="plan-cta">
                            <a href="<?php echo esc_url(home_url('/register/')); ?>" class="btn btn-outline btn-full">Get started</a>
                        </div>
                    </div>
                    
                    <div class="pricing-card featured">
                        <div class="popular-badge">Most Popular</div>
                        <div class="plan-header">
                            <h3 class="plan-name">Professional</h3>
                            <div class="plan-price">
                                <span class="currency">$</span>
                                <span class="amount">79</span>
                                <span class="period">/month</span>
                            </div>
                            <p class="plan-description">For growing cleaning businesses</p>
                        </div>
                        <ul class="plan-features">
                            <li>Unlimited bookings</li>
                            <li>Advanced booking system</li>
                            <li>Team management</li>
                            <li>Service area mapping</li>
                            <li>Advanced analytics</li>
                            <li>Stripe integration</li>
                            <li>Priority support</li>
                            <li>Custom branding</li>
                        </ul>
                        <div class="plan-cta">
                            <a href="<?php echo esc_url(home_url('/register/')); ?>" class="btn btn-primary btn-full">Get started</a>
                        </div>
                    </div>
                    
                    <div class="pricing-card">
                        <div class="plan-header">
                            <h3 class="plan-name">Enterprise</h3>
                            <div class="plan-price">
                                <span class="currency">$</span>
                                <span class="amount">199</span>
                                <span class="period">/month</span>
                            </div>
                            <p class="plan-description">For large cleaning operations</p>
                        </div>
                        <ul class="plan-features">
                            <li>Everything in Professional</li>
                            <li>Multi-location support</li>
                            <li>White-label options</li>
                            <li>API access</li>
                            <li>Custom integrations</li>
                            <li>Dedicated support</li>
                            <li>Advanced security features</li>
                            <li>SLA guarantee</li>
                        </ul>
                        <div class="plan-cta">
                            <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="btn btn-outline btn-full">Contact sales</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section with Cal.com styling -->
        <section id="support" class="section bg-gray-50">
            <div class="container">
                <div class="section-header">
                    <div class="section-badge">FAQ</div>
                    <h2 class="section-title">Frequently asked questions</h2>
                    <p class="section-description">
                        Everything you need to know about NordBK and how it can help your cleaning business.
                    </p>
                </div>

                <div class="max-width-48 margin-auto">
                    <div class="faq-item">
                        <button class="faq-question" aria-expanded="false">
                            How quickly can I get started?
                        </button>
                        <div class="faq-answer">
                            <p>You can set up your account and start accepting bookings within 10 minutes. Our onboarding process is designed to be simple and straightforward, with step-by-step guidance.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question" aria-expanded="false">
                            Do I need technical skills to use NordBK?
                        </button>
                        <div class="faq-answer">
                            <p>Not at all! NordBK is designed for business owners, not developers. Everything is point-and-click with no coding required. Our intuitive interface makes it easy for anyone to use.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question" aria-expanded="false">
                            Can I customize the booking form to match my brand?
                        </button>
                        <div class="faq-answer">
                            <p>Yes! Professional and Enterprise plans include custom branding options to match your business colors, logo, and style. You can make the booking form look like part of your website.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question" aria-expanded="false">
                            What payment methods are supported?
                        </button>
                        <div class="faq-answer">
                            <p>Through our Stripe integration, we support all major credit cards, digital wallets like Apple Pay and Google Pay, and bank transfers. Payments are processed securely with bank-level encryption.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question" aria-expanded="false">
                            Is there a free trial?
                        </button>
                        <div class="faq-answer">
                            <p>Yes! We offer a 14-day free trial with no credit card required. You can explore all features and see how NordBK works for your business before making any commitment.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question" aria-expanded="false">
                            How secure is my data?
                        </button>
                        <div class="faq-answer">
                            <p>Your data security is our top priority. We use bank-level encryption, are SOC 2 Type II compliant, and follow GDPR regulations. All data is backed up regularly and stored in secure data centers.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Final CTA Section -->
        <section class="section" style="background: var(--color-primary); color: white;">
            <div class="container text-center">
                <div class="hero-badge mb-4">
                    🚀 Join 500+ successful cleaning businesses
                </div>
                
                <h2 class="section-title text-white mb-4">
                    Ready to transform your cleaning business?
                </h2>
                
                <p class="section-description text-white-75 mb-8">
                    Start your free trial today and see how NordBK can help you streamline operations and grow your revenue.
                </p>

                <div class="hero-cta-group">
                    <a href="<?php echo esc_url(home_url('/register/')); ?>" class="btn btn-light btn-hero">Start Free Trial</a>
                    <a href="#demo" class="btn btn-outline-light btn-hero">Schedule Demo</a>
                </div>

                <p class="mt-6 text-sm text-white-50">
                    No credit card required • 14-day free trial • Cancel anytime
                </p>
            </div>
        </section>

    </main>
<?php
get_footer();
