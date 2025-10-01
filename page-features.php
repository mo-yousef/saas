<?php
/**
 * Template Name: Features Page
 *
 * @package Nord Booking
 */

get_header(); // Even if front-page doesn't use it, page templates generally should.
?>

    <main id="content" class="site-content">
        <!-- Page Header -->
        <section class="section page-header-section">
            <div class="container">
                <h1 class="section-title">Everything You Need to Run Your Cleaning Business</h1>
                <p class="section-description">Powerful features designed specifically for cleaning service companies. From simple bookings to advanced business management - we've got you covered.</p>
            </div>
        </section>

        <!-- In-page Quick Navigation -->
        <nav class="feature-toc" aria-label="Features page navigation">
            <div class="container">
                <a class="toc-link" href="#at-a-glance">At a Glance</a>
                <a class="toc-link" href="#core-features">Core</a>
                <a class="toc-link" href="#advanced-features">Advanced</a>
                <a class="toc-link" href="#technical-excellence">Technical</a>
                <a class="toc-link" href="#pricing-integration">Pricing</a>
                <a class="toc-link" href="#social-proof">Reviews</a>
                <a class="toc-link" href="#faq">FAQ</a>
                <a class="toc-link" href="<?php echo esc_url( home_url('/register/') ); ?>">Start Free</a>
            </div>
        </nav>

        <!-- At a Glance Section -->
        <section id="at-a-glance" class="section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">At a Glance</h2>
                    <p class="section-description">A quick overview of the key capabilities that help run and grow your cleaning business.</p>
                </div>
                <div class="glance-grid" role="list">
                    <div class="glance-item" role="listitem">
                        <span class="icon" aria-hidden="true">📅</span>
                        <div class="text">Effortless online bookings with real-time availability and confirmations.</div>
                    </div>
                    <div class="glance-item" role="listitem">
                        <span class="icon" aria-hidden="true">🏢</span>
                        <div class="text">Scalable multi-tenant setup for single locations to franchises.</div>
                    </div>
                    <div class="glance-item" role="listitem">
                        <span class="icon" aria-hidden="true">🧰</span>
                        <div class="text">Robust service management with options, add‑ons, and dynamic pricing.</div>
                    </div>
                    <div class="glance-item" role="listitem">
                        <span class="icon" aria-hidden="true">👥</span>
                        <div class="text">Customer CRM: profiles, history, and automated follow‑ups.</div>
                    </div>
                    <div class="glance-item" role="listitem">
                        <span class="icon" aria-hidden="true">💳</span>
                        <div class="text">Stripe payments, invoices, and support for modern wallets.</div>
                    </div>
                    <div class="glance-item" role="listitem">
                        <span class="icon" aria-hidden="true">📈</span>
                        <div class="text">Actionable analytics dashboard to track revenue and performance.</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Hero Section -->
        <section class="hero">
            <div class="container">
                <div class="hero-badge fade-in">
                    ✨ Trusted by 1000+ cleaning businesses
                </div>

                <h2 class="hero-title slide-up">
                    Transform Your Cleaning Business with Professional Tools
                </h2>

                <p class="hero-description slide-up delay-100">
                    Stop juggling spreadsheets and missed calls. Nord Booking gives you everything you need to streamline operations, delight customers, and grow your revenue.
                </p>
                <!-- Removed hero-actions and hero-stats from front-page.php as they are not in the new content spec for this hero -->
            </div>
        </section>

        <!-- Core Features Section -->
        <section id="core-features" class="section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Core Features</h2>
                    <!-- Optional: Add a sub-description for core features if desired -->
                </div>

                <div class="features-grid">
                    <!-- Feature 1: Smart Booking System -->
                    <div class="card feature-card">
                        <div class="feature-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-calendar"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        </div>
                        <h3 class="feature-title">Effortless Online Booking</h3>
                        <p class="feature-description">
                            Let customers book your services 24/7 with our intelligent booking system. Multi-step forms guide customers through service selection, scheduling, and payments - all while you sleep.
                            <!-- Placeholder for image: <div style="border:1px dashed hsl(var(--border)); padding: 2rem; text-align:center; margin-top:1rem; font-size:0.875rem; color:hsl(var(--muted-foreground));">Screenshot of Booking Form Here</div> -->
                        </p>
                        <ul class="feature-benefits-list">
                            <li>6-step booking process with smart validation</li>
                            <li>Real-time availability checking</li>
                            <li>Automatic ZIP code verification for service areas</li>
                            <li>Mobile-optimized for on-the-go bookings</li>
                            <li>Instant confirmation emails</li>
                        </ul>
                    </div>

                    <!-- Feature 2: Multi-Tenant Architecture -->
                    <div class="card feature-card">
                        <div class="feature-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-server"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>
                        </div>
                        <h3 class="feature-title">Built for Growth & Scalability</h3>
                        <p class="feature-description">
                            Perfect for individual businesses or franchise operations. Each business owner gets their own isolated dashboard and data while you maintain central control.
                        </p>
                        <ul class="feature-benefits-list">
                            <li>Separate dashboards for each business owner</li>
                            <li>Isolated customer data and bookings</li>
                            <li>White-label customization options</li>
                            <li>Perfect for franchises & multi-location</li>
                            <li>Scalable infrastructure</li>
                        </ul>
                    </div>

                    <!-- Feature 3: Complete Service Management -->
                    <div class="card feature-card">
                        <div class="feature-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-settings"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                        </div>
                        <h3 class="feature-title">Manage Services Like a Pro</h3>
                        <p class="feature-description">
                            Create detailed service offerings with custom options, pricing tiers, and add-ons. Give customers exactly what they want while maximizing your revenue.
                        </p>
                        <ul class="feature-benefits-list">
                            <li>Unlimited service categories</li>
                            <li>Custom service options (checkbox, text, etc.)</li>
                            <li>Dynamic pricing with add-ons</li>
                            <li>Service duration tracking</li>
                            <li>Rich media support (icons, images)</li>
                        </ul>
                    </div>

                    <!-- Feature 4: Advanced Customer Management -->
                    <div class="card feature-card">
                        <div class="feature-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-users"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        </div>
                        <h3 class="feature-title">Know Your Customers Better</h3>
                        <p class="feature-description">
                            Build lasting relationships with comprehensive customer profiles, booking history, and personalized service recommendations.
                        </p>
                        <ul class="feature-benefits-list">
                            <li>Detailed customer profiles & history</li>
                            <li>Service preferences & special instructions</li>
                            <li>Automated follow-up systems</li>
                            <li>Customer loyalty insights</li>
                            <li>Personalized recommendations</li>
                        </ul>
                    </div>

                    <!-- Feature 5: Geographic Area Management -->
                    <div class="card feature-card">
                        <div class="feature-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-map-pin"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        </div>
                        <h3 class="feature-title">Smart Service Area Coverage</h3>
                        <p class="feature-description">
                            Define your service areas with precision. Customers can instantly check if you serve their location, reducing wasted inquiries and improving conversions.
                        </p>
                        <ul class="feature-benefits-list">
                            <li>ZIP code and city-based areas</li>
                            <li>Global location API integration</li>
                            <li>Automatic area validation</li>
                            <li>Visual coverage maps</li>
                            <li>Nordic region specialty support</li>
                        </ul>
                    </div>

                    <!-- Feature 6: Discount & Promotion Engine -->
                    <div class="card feature-card">
                        <div class="feature-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-percent"><line x1="19" y1="5" x2="5" y2="19"></line><circle cx="6.5" cy="6.5" r="2.5"></circle><circle cx="17.5" cy="17.5" r="2.5"></circle></svg>
                        </div>
                        <h3 class="feature-title">Boost Sales with Smart Discounts</h3>
                        <p class="feature-description">
                            Create compelling offers that drive bookings. Percentage discounts, fixed amounts, limited-time offers - all managed from your dashboard.
                        </p>
                        <ul class="feature-benefits-list">
                            <li>Percentage or fixed amount discounts</li>
                            <li>Expiry dates and usage limits</li>
                            <li>Promo code generation & tracking</li>
                            <li>Seasonal campaign management</li>
                            <li>ROI tracking for promotions</li>
                        </ul>
                    </div>

                    <!-- Feature 7: Professional Dashboard -->
                    <div class="card feature-card">
                        <div class="feature-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-bar-chart-2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                        </div>
                        <h3 class="feature-title">Business Intelligence Dashboard</h3>
                        <p class="feature-description">
                            Make data-driven decisions with comprehensive analytics, booking trends, and performance metrics that help you grow smarter.
                             <!-- Placeholder for image: <div style="border:1px dashed hsl(var(--border)); padding: 2rem; text-align:center; margin-top:1rem; font-size:0.875rem; color:hsl(var(--muted-foreground));">Screenshot of Dashboard Analytics Here</div> -->
                        </p>
                        <ul class="feature-benefits-list">
                            <li>Real-time booking statistics</li>
                            <li>Revenue tracking & forecasting</li>
                            <li>Customer behavior insights</li>
                            <li>Service performance analytics</li>
                            <li>Customizable KPI tracking</li>
                        </ul>
                    </div>

                    <!-- Feature 8: Seamless Payments -->
                    <div class="card feature-card">
                        <div class="feature-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-credit-card"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                        </div>
                        <h3 class="feature-title">Get Paid Instantly</h3>
                        <p class="feature-description">
                            Integrated payment processing through WooCommerce and Stripe. Accept all major credit cards, digital wallets, and bank transfers.
                        </p>
                        <ul class="feature-benefits-list">
                            <li>Stripe integration for secure payments</li>
                            <li>Supports all major credit cards</li>
                            <li>Apple Pay & Google Pay compatible</li>
                            <li>Automated invoice generation</li>
                            <li>PCI-compliant payment processing</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Advanced Features Section -->
        <section id="advanced-features" class="section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Advanced Features</h2>
                </div>

                <div class="features-grid">
                    <!-- Feature 9: Custom Branding & White-Label -->
                    <div class="card feature-card">
                        <div class="feature-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-palette"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                        </div>
                        <h3 class="feature-title">Make It Yours</h3>
                        <p class="feature-description">
                            Complete branding control with custom colors, logos, and styling. Create a professional experience that matches your brand identity.
                        </p>
                        <ul class="feature-benefits-list">
                            <li>Custom color schemes and typography</li>
                            <li>Logo integration and brand consistency</li>
                            <li>Embeddable booking forms</li>
                            <li>White-label options for agencies</li>
                            <li>Mobile-responsive design</li>
                        </ul>
                    </div>

                    <!-- Feature 10: Automated Notifications -->
                    <div class="card feature-card">
                        <div class="feature-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-bell"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                        </div>
                        <h3 class="feature-title">Stay Connected Automatically</h3>
                        <p class="feature-description">
                            Keep customers informed and engaged with automated email notifications for confirmations, reminders, and follow-ups.
                        </p>
                        <ul class="feature-benefits-list">
                            <li>Booking confirmation emails</li>
                            <li>Appointment reminders</li>
                            <li>Service completion follow-ups</li>
                            <li>Customizable email templates</li>
                            <li>SMS integration capabilities</li>
                        </ul>
                    </div>

                    <!-- Feature 11: API & Integrations -->
                    <div class="card feature-card">
                        <div class="feature-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-link"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                        </div>
                        <h3 class="feature-title">Connect Your Business Tools</h3>
                        <p class="feature-description">
                            Integrate with your existing business tools and workflows. Our API allows custom integrations and third-party connections.
                        </p>
                        <ul class="feature-benefits-list">
                            <li>RESTful API for custom integrations</li>
                            <li>WooCommerce native integration</li>
                            <li>Third-party calendar synchronization</li>
                            <li>CRM and accounting software connections</li>
                            <li>Webhook support for real-time updates</li>
                        </ul>
                    </div>

                    <!-- Feature 12: Mobile-First Design -->
                    <div class="card feature-card">
                        <div class="feature-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-smartphone"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
                        </div>
                        <h3 class="feature-title">Works Perfectly on Any Device</h3>
                        <p class="feature-description">
                            Your customers book from their phones, and you manage from yours. Full mobile optimization ensures a great experience everywhere.
                        </p>
                        <ul class="feature-benefits-list">
                            <li>Responsive design for all screen sizes</li>
                            <li>Touch-optimized interface</li>
                            <li>Fast loading on mobile networks</li>
                            <li>Offline capability for basic functions</li>
                            <li>Progressive web app features</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Technical Excellence Section -->
        <section id="technical-excellence" class="section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Technical Excellence</h2>
                </div>

                <div class="features-grid">
                    <!-- Feature: Enterprise-Grade Security -->
                    <div class="card feature-card">
                        <div class="feature-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-shield"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        </div>
                        <h3 class="feature-title">Your Data is Safe</h3>
                        <p class="feature-description">
                            Built with security best practices. SSL encryption, regular backups, and compliance with data protection regulations.
                        </p>
                        <ul class="feature-benefits-list">
                            <li>SSL/TLS encryption for all data</li>
                            <li>Regular automated backups</li>
                            <li>GDPR compliance features</li>
                            <li>Role-based access control</li>
                            <li>Audit logs and activity tracking</li>
                        </ul>
                    </div>

                    <!-- Feature: Scalable Architecture -->
                    <div class="card feature-card">
                        <div class="feature-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-zap"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                        </div>
                        <h3 class="feature-title">Built to Scale</h3>
                        <p class="feature-description">
                            WordPress-based architecture with custom database optimization. Handles everything from single businesses to large franchise operations.
                        </p>
                        <ul class="feature-benefits-list">
                            <li>Object-oriented PHP architecture</li>
                            <li>Custom database tables for performance</li>
                            <li>Auto-scaling infrastructure</li>
                            <li>CDN integration for global speed</li>
                            <li>99.9% uptime guarantee</li>
                        </ul>
                    </div>

                    <!-- Feature: Developer-Friendly -->
                    <div class="card feature-card">
                        <div class="feature-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-code"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                        </div>
                        <h3 class="feature-title">Extensible & Customizable</h3>
                        <p class="feature-description">
                            Built with developers in mind. Clean code, comprehensive documentation, and extensible architecture for custom requirements.
                        </p>
                        <ul class="feature-benefits-list">
                            <li>Well-documented codebase</li>
                            <li>Custom hooks and filters</li>
                            <li>Modular component architecture</li>
                            <li>Theme and plugin compatibility</li>
                            <li>Version control and deployment tools</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing Integration Section -->
        <section id="pricing-integration" class="section">
            <div class="container">
                <div class="section-header">
                    <div class="feature-icon">
                         <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="hsl(var(--primary))" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-gem"><polygon points="6 3 18 3 22 9 12 22 2 9 6 3"></polygon><polygon points="12 22 12 9"></polygon><polygon points="2.42 9 21.58 9"></polygon><polygon points="10.18 3 12 9 13.82 3"></polygon><polygon points="18.46 3.82 12 9 5.54 3.82"></polygon></svg>
                    </div>
                    <h2 class="section-title">Plans That Grow With You</h2>
                    <p class="section-description">Start free, scale as you grow. From solo cleaners to large franchises, we have a plan that fits your business.</p>
                </div>

                <div class="pricing-grid">
                    <!-- Starter Plan -->
                    <div class="card pricing-card">
                        <div>
                            <div class="card-header">
                                <h3 class="plan-name">Starter</h3>
                                <div class="plan-price">$0<span>/month</span></div>
                            </div>
                            <div class="card-content">
                                <p class="feature-description">Perfect for new businesses getting started.</p>
                                <!-- Link to full pricing page or signup could go here -->
                            </div>
                        </div>
                        <div>
                             <a href="<?php echo esc_url(home_url('/register/')); ?>" class="btn btn-outline btn-lg">Get Started</a>
                        </div>
                    </div>

                    <!-- Professional Plan -->
                    <div class="card pricing-card professional-plan">
                         <div class="most-popular-badge">Most Popular</div>
                        <div>
                            <div class="card-header">
                                <h3 class="plan-name">Professional</h3>
                                <div class="plan-price">$79<span>/month</span></div>
                            </div>
                            <div class="card-content">
                                <p class="feature-description">Advanced features for growing businesses.</p>
                                <!-- Link to full pricing page or signup could go here -->
                            </div>
                        </div>
                        <div>
                             <a href="<?php echo esc_url(home_url('/register/')); ?>" class="btn btn-primary btn-lg">Choose Professional</a>
                        </div>
                    </div>

                    <!-- Enterprise Plan -->
                    <div class="card pricing-card">
                        <div>
                            <div class="card-header">
                                <h3 class="plan-name">Enterprise</h3>
                                <div class="plan-price">$199<span>/month</span></div>
                            </div>
                            <div class="card-content">
                                <p class="feature-description">Full platform access with white-label options.</p>
                                <!-- Link to full pricing page or contact could go here -->
                            </div>
                        </div>
                        <div>
                             <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="btn btn-outline btn-lg">Contact Sales</a>
                        </div>
                    </div>
                </div>
                <p>For more details, visit our <a href="<?php echo esc_url(home_url('/#pricing')); ?>">full pricing page</a>.</p>
            </div>
        </section>

        <!-- Social Proof Section -->
        <section id="social-proof" class="section">
            <div class="container">
                <div class="section-header">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="hsl(var(--primary))" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-star"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                    </div>
                    <h2 class="section-title">Join 1000+ Successful Cleaning Businesses</h2>
                    <p class="section-description">See why cleaning businesses across the globe choose Nord Booking to grow their operations.</p>
                </div>

                <div class="testimonials-grid">
                    <!-- Testimonial 1 -->
                    <div class="card testimonial">
                        <div class="card-content">
                            <p class="testimonial-content">
                                "150% revenue increase since switching to Nord Booking. Their platform is intuitive and the support is top-notch!"
                            </p>
                            <div class="testimonial-author">
                                <!-- <div class="author-avatar">LR</div> -->
                                <div>
                                    <div class="author-name">Lisa Rodriguez</div>
                                    <div class="author-title">Elite Cleaning Co.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Testimonial 2 -->
                    <div class="card testimonial">
                        <div class="card-content">
                            <p class="testimonial-content">
                                "Perfect for our franchise operations. The multi-tenant features give us central control while empowering individual owners."
                            </p>
                            <div class="testimonial-author">
                                <!-- <div class="author-avatar">MJ</div> -->
                                <div>
                                    <div class="author-name">Michael Johnson</div>
                                    <div class="author-title">CleanPro Franchises</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Testimonial 3 -->
                    <div class="card testimonial">
                        <div class="card-content">
                            <p class="testimonial-content">
                                "Outstanding customer support and the platform is so easy to use. We've streamlined everything from booking to payments."
                            </p>
                            <div class="testimonial-author">
                                <!-- <div class="author-avatar">SW</div> -->
                                <div>
                                    <div class="author-name">Sarah Williams</div>
                                    <div class="author-title">Sparkle Services</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Primary CTA Section -->
        <section id="primary-cta" class="section cta-section">
            <div class="container">
                <h2 class="section-title">Ready to Transform Your Cleaning Business?</h2>
                <p class="section-description">Join thousands of cleaning businesses that have streamlined their operations with Nord Booking.</p>
                <div class="hero-actions">
                    <a href="<?php echo esc_url(home_url('/register/')); ?>" class="btn btn-xl" style="background-color: hsl(var(--background)); color: hsl(var(--primary));">Start Free Trial</a>
                    <a href="<?php echo esc_url(home_url('/contact/#schedule-demo')); ?>" class="btn btn-outline btn-xl" style="border-color: hsl(var(--primary-foreground)/0.5); color: hsl(var(--primary-foreground)); background-color: transparent;">Schedule Demo</a>
                </div>
                <p>
                    No credit card required • 7-days free trial • Cancel anytime
                </p>
            </div>
        </section>

        <!-- Secondary CTA Section -->
        <section id="secondary-cta" class="section">
            <div class="container">
                <h2 class="section-title">See Nord Booking in Action</h2>
                <p class="section-description">Book a personalized demo and see how Nord Booking can work for your specific cleaning business needs.</p>
                <a href="<?php echo esc_url(home_url('/contact/#schedule-demo')); ?>" class="btn btn-primary btn-lg">Schedule Demo</a>
                <ul>
                    <li>✓ 30-minute personalized demo</li>
                    <li>✓ Custom setup consultation</li>
                    <li>✓ No sales pressure</li>
                </ul>
            </div>
        </section>

        <!-- FAQ Section -->
        <section id="faq" class="section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Frequently Asked Questions</h2>
                </div>

                <div class="faq-grid">
                    <!-- FAQ 1 -->
                    <div class="card faq-item">
                        <div class="card-content">
                            <h3 class="faq-question">Do I need technical skills to use Nord Booking?</h3>
                            <p class="faq-answer">Not at all! Nord Booking is designed for business owners, not developers. Everything is point-and-click with no coding required.</p>
                        </div>
                    </div>
                    <!-- FAQ 2 -->
                    <div class="card faq-item">
                        <div class="card-content">
                            <h3 class="faq-question">Can I customize the booking form to match my brand?</h3>
                            <p class="faq-answer">Yes! Professional and Enterprise plans include custom branding options to match your business colors, logo, and style.</p>
                        </div>
                    </div>
                    <!-- FAQ 3 -->
                    <div class="card faq-item">
                        <div class="card-content">
                            <h3 class="faq-question">What payment methods are supported?</h3>
                            <p class="faq-answer">Through our Stripe integration, we support all major credit cards, digital wallets like Apple Pay and Google Pay, and bank transfers.</p>
                        </div>
                    </div>
                    <!-- FAQ 4 -->
                    <div class="card faq-item">
                        <div class="card-content">
                            <h3 class="faq-question">Is there a free trial?</h3>
                            <p class="faq-answer">Yes! We offer a 7-days free trial with no credit card required. You can explore all features and see how Nord Booking works for your business.</p>
                        </div>
                    </div>
                    <!-- FAQ 5 -->
                    <div class="card faq-item">
                        <div class="card-content">
                            <h3 class="faq-question">Can I manage multiple locations or franchises?</h3>
                            <p class="faq-answer">Absolutely! Our multi-tenant architecture is perfect for franchise operations, allowing you to manage multiple locations while giving each franchise owner their own dashboard.</p>
                        </div>
                    </div>
                    <!-- FAQ 6 -->
                    <div class="card faq-item">
                        <div class="card-content">
                            <h3 class="faq-question">How quickly can I get started?</h3>
                            <p class="faq-answer">Most businesses are up and running within 30 minutes. Our onboarding process is designed to be simple and straightforward.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- End of new content for page-features.php, main content is complete. -->
    </main>

<?php get_footer(); ?>