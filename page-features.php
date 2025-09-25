<?php
/**
 * Template Name: Features Page
 *
 * @package Nord Booking
 */

get_header(); // Even if front-page doesn't use it, page templates generally should.
?>
    <style>
        /* Copied from front-page.php for consistency */
        /*
        Removed: header#masthead,footer#colophon { display: none; }
        This rule is very specific to front-page.php's intention to hide default theme header/footer.
        page-features.php uses a custom header but shouldn't universally hide theme elements
        that get_header() or get_footer() might output if they were used more traditionally.
        */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --background: 0 0% 100%;
            --foreground: 222.2 84% 4.9%;
            --card: 0 0% 100%;
            --card-foreground: 222.2 84% 4.9%;
            --popover: 0 0% 100%;
            --popover-foreground: 222.2 84% 4.9%;
            --primary:  221.2 83.2% 53.3%;
            --primary-foreground: 210 40% 98%;
            --secondary: 210 40% 96%;
            --secondary-foreground: 222.2 84% 4.9%;
            --muted: 210 40% 96%;
            --muted-foreground: 215.4 16.3% 46.9%;
            --accent: 210 40% 96%;
            --accent-foreground: 222.2 84% 4.9%;
            --destructive: 0 62.8% 30.6%;
            --destructive-foreground: 210 40% 98%;
            --border: 214.3 31.8% 91.4%;
            --input: 214.3 31.8% 91.4%;
            --ring: 222.2 84% 4.9%;
            --radius: 0.5rem;
        }

        body {
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
            line-height: 1.6;
            color: hsl(var(--foreground));
            background-color: hsl(var(--background));
            font-feature-settings: "rlig" 1, "calt" 1;
        }
        a {
            text-decoration: none !important;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        /* Header from front-page.php */
        header.front-page-header { /* Renamed to avoid conflict if site-header is used by get_header() */
            position: sticky;
            top: 0;
            z-index: 50;
            width: 100%;
            border-bottom: 1px solid hsl(var(--border));
            background-color: hsl(var(--background) / 0.95);
            backdrop-filter: blur(8px);
        }

        nav.front-page-nav { /* Renamed */
            display: flex;
            height: 4rem;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: hsl(var(--foreground));
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: hsl(var(--muted-foreground));
            font-weight: 500;
            font-size: 0.875rem;
            transition: color 0.2s;
        }

        .nav-links a:hover {
            color: hsl(var(--foreground));
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: calc(var(--radius) - 2px);
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s;
            border: 1px solid transparent;
            cursor: pointer;
            text-decoration: none;
            gap: 0.5rem;
        }

        .btn-primary {
            background-color: hsl(var(--primary));
            color: hsl(var(--primary-foreground));
        }

        .btn-primary:hover {
            background-color: hsl(var(--primary) / 0.9);
        }

        .btn-outline {
            color: hsl(var(--foreground));
            border: 1px solid hsl(var(--border));
            background-color: hsl(var(--background));
        }

        .btn-outline:hover {
            background-color: hsl(var(--accent));
            color: hsl(var(--accent-foreground));
        }

        .btn-sm {
            height: 2.25rem;
            padding: 0 0.75rem;
        }

        .btn-lg {
            height: 2.75rem;
            padding: 0 2rem;
        }

        .btn-xl {
            height: 3rem;
            padding: 0 2.5rem;
            font-size: 1rem;
        }

        /* Section Styles from front-page.php */
        .section {
            padding: 4rem 0; /* Adjusted padding for a standard page */
        }

        .section-header {
            text-align: center;
            margin-bottom: 3rem; /* Adjusted margin */
        }

        .section-title {
            font-size: clamp(2rem, 4vw, 2.5rem); /* Adjusted size */
            font-weight: 700; /* Adjusted from 800 */
            line-height: 1.2;
            letter-spacing: -0.025em;
            margin-bottom: 1rem;
            color: hsl(var(--foreground));
        }

        .section-description {
            font-size: 1.125rem;
            line-height: 1.6;
            color: hsl(var(--muted-foreground));
            max-width: 48rem;
            margin: 0 auto;
        }

        /* Features Grid from front-page.php (can be reused) */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); /* Slightly smaller minmax */
            gap: 2rem;
            margin-top: 3rem; /* Adjusted margin */
        }

        .card { /* Generic card style from front-page.php */
            border-radius: var(--radius);
            border: 1px solid hsl(var(--border));
            background-color: hsl(var(--card));
            color: hsl(var(--card-foreground));
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            transition: all 0.2s;
        }
        .card:hover {
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }

        .feature-card { /* Specific feature card style from front-page.php */
            padding: 1.5rem; /* Adjusted padding */
        }

        .feature-icon {
            width: 2.5rem; /* Adjusted size */
            height: 2.5rem; /* Adjusted size */
            background-color: hsl(var(--primary));
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.25rem; /* Adjusted size */
            color: hsl(var(--primary-foreground));
        }

        .feature-title {
            font-size: 1.125rem; /* Adjusted size */
            font-weight: 600;
            margin-bottom: 0.5rem; /* Adjusted margin */
            color: hsl(var(--foreground));
        }

        .feature-description {
            color: hsl(var(--muted-foreground));
            line-height: 1.6;
            font-size: 0.875rem; /* Adjusted size */
        }

        .feature-benefits-list {
            list-style: none; /* Remove default bullets */
            padding-left: 0;
            margin-top: 1rem;
        }

        .feature-benefits-list li {
            display: flex;
            align-items: flex-start; /* Align icon with top of text */
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            color: hsl(var(--muted-foreground));
        }

        .feature-benefits-list li::before {
            content: '✓'; /* Checkmark icon */
            color: hsl(var(--primary));
            font-weight: 600;
            margin-right: 0.5rem;
            flex-shrink: 0; /* Prevent checkmark from shrinking */
            line-height: 1.4; /* Adjust line height for better alignment */
        }

        .feature-card .feature-description,
        .technical-card .feature-description { /* Assuming technical cards might use same class */
            margin-bottom: 1rem; /* Consolidate from inline */
        }

        .faq-item .faq-question {
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 1.125rem;
        }
        .faq-item .faq-answer {
            color: hsl(var(--muted-foreground));
            margin: 0;
            line-height: 1.6;
        }

        /* Footer from front-page.php */
        footer.front-page-footer { /* Renamed */
            border-top: 1px solid hsl(var(--border));
            padding: 3rem 0 1.5rem; /* Adjusted padding */
            background-color: hsl(var(--muted) / 0.3);
            margin-top: 4rem; /* Add some space before footer */
        }

        .footer-bottom {
            border-top: 1px solid hsl(var(--border));
            padding-top: 1.5rem; /* Adjusted padding */
            text-align: center;
            color: hsl(var(--muted-foreground));
            font-size: 0.875rem;
        }
        /* Mobile Menu */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            color: hsl(var(--foreground));
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links {
                display: none; /* This will hide main nav links, ensure mobile menu works or is different for page template */
            }

            .mobile-menu-toggle {
                display: block;
            }
            .features-grid {
                grid-template-columns: 1fr;
            }
        }

    </style>


    <main id="content" class="site-content">
        <!-- Page Header -->
        <section class="section page-header-section" style="padding-top: 4rem; padding-bottom: 2rem; text-align: center;">
            <div class="container">
                <h1 class="section-title" style="font-size: clamp(2.25rem, 5vw, 3rem); margin-bottom: 0.75rem;">Everything You Need to Run Your Cleaning Business</h1>
                <p class="section-description" style="font-size: 1.25rem; max-width: 48rem; margin: 0 auto;">Powerful features designed specifically for cleaning service companies. From simple bookings to advanced business management - we've got you covered.</p>
            </div>
        </section>

        <!-- Hero Section -->
        <section class="hero" style="padding-top: 3rem; padding-bottom: 4rem; background-color: hsl(var(--muted)/0.3);">
            <div class="container">
                <div class="hero-badge fade-in" style="margin-bottom: 1.5rem;">
                    ✨ Trusted by 1000+ cleaning businesses
                </div>

                <h2 class="hero-title slide-up" style="font-size: clamp(2rem, 4.5vw, 3.5rem); margin-bottom: 1rem;">
                    Transform Your Cleaning Business with Professional Tools
                </h2>

                <p class="hero-description slide-up delay-100" style="font-size: 1.125rem; max-width: 42rem; margin-bottom:0;">
                    Stop juggling spreadsheets and missed calls. Nord Booking gives you everything you need to streamline operations, delight customers, and grow your revenue.
                </p>
                <!-- Removed hero-actions and hero-stats from front-page.php as they are not in the new content spec for this hero -->
            </div>
        </section>

        <!-- Core Features Section -->
        <section id="core-features" class="section">
            <div class="container">
                <div class="section-header" style="margin-bottom: 3rem;">
                    <h2 class="section-title">Core Features</h2>
                    <!-- Optional: Add a sub-description for core features if desired -->
                </div>

                <div class="features-grid" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem;">
                    <!-- Feature 1: Smart Booking System -->
                    <div class="card feature-card">
                        <div class="feature-icon" style="width: 2.5rem; height: 2.5rem; font-size: 1.5rem;">
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
                        <div class="feature-icon" style="width: 2.5rem; height: 2.5rem; font-size: 1.5rem;">
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
                        <div class="feature-icon" style="width: 2.5rem; height: 2.5rem; font-size: 1.5rem;">
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
                        <div class="feature-icon" style="width: 2.5rem; height: 2.5rem; font-size: 1.5rem;">
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
                        <div class="feature-icon" style="width: 2.5rem; height: 2.5rem; font-size: 1.5rem;">
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
                        <div class="feature-icon" style="width: 2.5rem; height: 2.5rem; font-size: 1.5rem;">
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
                        <div class="feature-icon" style="width: 2.5rem; height: 2.5rem; font-size: 1.5rem;">
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
                        <div class="feature-icon" style="width: 2.5rem; height: 2.5rem; font-size: 1.5rem;">
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
        <section id="advanced-features" class="section" style="background-color: hsl(var(--muted)/0.3);">
            <div class="container">
                <div class="section-header" style="margin-bottom: 3rem;">
                    <h2 class="section-title">Advanced Features</h2>
                </div>

                <div class="features-grid" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem;">
                    <!-- Feature 9: Custom Branding & White-Label -->
                    <div class="card feature-card">
                        <div class="feature-icon" style="width: 2.5rem; height: 2.5rem; font-size: 1.5rem;">
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
                        <div class="feature-icon" style="width: 2.5rem; height: 2.5rem; font-size: 1.5rem;">
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
                        <div class="feature-icon" style="width: 2.5rem; height: 2.5rem; font-size: 1.5rem;">
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
                        <div class="feature-icon" style="width: 2.5rem; height: 2.5rem; font-size: 1.5rem;">
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
                <div class="section-header" style="margin-bottom: 3rem;">
                    <h2 class="section-title">Technical Excellence</h2>
                </div>

                <div class="features-grid" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem;">
                    <!-- Feature: Enterprise-Grade Security -->
                    <div class="card feature-card">
                        <div class="feature-icon" style="width: 2.5rem; height: 2.5rem; font-size: 1.5rem;">
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
                        <div class="feature-icon" style="width: 2.5rem; height: 2.5rem; font-size: 1.5rem;">
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
                        <div class="feature-icon" style="width: 2.5rem; height: 2.5rem; font-size: 1.5rem;">
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
        <section id="pricing-integration" class="section" style="background-color: hsl(var(--muted)/0.3);">
            <div class="container">
                <div class="section-header" style="margin-bottom: 3rem;">
                    <div class="feature-icon" style="margin: 0 auto 1rem auto; background-color: transparent;">
                         <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="hsl(var(--primary))" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-gem"><polygon points="6 3 18 3 22 9 12 22 2 9 6 3"></polygon><polygon points="12 22 12 9"></polygon><polygon points="2.42 9 21.58 9"></polygon><polygon points="10.18 3 12 9 13.82 3"></polygon><polygon points="18.46 3.82 12 9 5.54 3.82"></polygon></svg>
                    </div>
                    <h2 class="section-title">Plans That Grow With You</h2>
                    <p class="section-description" style="max-width: 42rem;">Start free, scale as you grow. From solo cleaners to large franchises, we have a plan that fits your business.</p>
                </div>

                <div class="pricing-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem; align-items: stretch;">
                    <!-- Starter Plan -->
                    <div class="card pricing-card" style="display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div class="card-header" style="padding-bottom: 1rem;">
                                <h3 class="plan-name" style="font-size: 1.5rem; font-weight: 600;">Starter</h3>
                                <div class="plan-price" style="font-size: 2.5rem; font-weight: 700; color: hsl(var(--primary));">$0<span style="font-size: 1rem; font-weight: 500; color: hsl(var(--muted-foreground));">/month</span></div>
                            </div>
                            <div class="card-content" style="padding-top: 0.5rem;">
                                <p class="feature-description" style="min-height: 40px;">Perfect for new businesses getting started.</p>
                                <!-- Link to full pricing page or signup could go here -->
                            </div>
                        </div>
                        <div style="padding: 1.5rem;">
                             <a href="<?php echo esc_url(home_url('/register/')); ?>" class="btn btn-outline btn-lg" style="width: 100%;">Get Started</a>
                        </div>
                    </div>

                    <!-- Professional Plan -->
                    <div class="card pricing-card" style="display: flex; flex-direction: column; justify-content: space-between; border-color: hsl(var(--primary)); border-width: 2px;">
                         <div>
                            <div style="text-align: center; background-color: hsl(var(--primary)); color: hsl(var(--primary-foreground)); padding: 0.25rem 0.75rem; font-size: 0.75rem; font-weight: 500; margin: -2px -2px 0; border-top-left-radius: var(--radius); border-top-right-radius: var(--radius);">Most Popular</div>
                            <div class="card-header" style="padding-bottom: 1rem; padding-top: 1.5rem;">
                                <h3 class="plan-name" style="font-size: 1.5rem; font-weight: 600;">Professional</h3>
                                <div class="plan-price" style="font-size: 2.5rem; font-weight: 700; color: hsl(var(--primary));">$79<span style="font-size: 1rem; font-weight: 500; color: hsl(var(--muted-foreground));">/month</span></div>
                            </div>
                            <div class="card-content" style="padding-top: 0.5rem;">
                                <p class="feature-description" style="min-height: 40px;">Advanced features for growing businesses.</p>
                                <!-- Link to full pricing page or signup could go here -->
                            </div>
                        </div>
                        <div style="padding: 1.5rem;">
                             <a href="<?php echo esc_url(home_url('/register/')); ?>" class="btn btn-primary btn-lg" style="width: 100%;">Choose Professional</a>
                        </div>
                    </div>

                    <!-- Enterprise Plan -->
                    <div class="card pricing-card" style="display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div class="card-header" style="padding-bottom: 1rem;">
                                <h3 class="plan-name" style="font-size: 1.5rem; font-weight: 600;">Enterprise</h3>
                                <div class="plan-price" style="font-size: 2.5rem; font-weight: 700; color: hsl(var(--primary));">$199<span style="font-size: 1rem; font-weight: 500; color: hsl(var(--muted-foreground));">/month</span></div>
                            </div>
                            <div class="card-content" style="padding-top: 0.5rem;">
                                <p class="feature-description" style="min-height: 40px;">Full platform access with white-label options.</p>
                                <!-- Link to full pricing page or contact could go here -->
                            </div>
                        </div>
                        <div style="padding: 1.5rem;">
                             <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="btn btn-outline btn-lg" style="width: 100%;">Contact Sales</a>
                        </div>
                    </div>
                </div>
                <p style="text-align: center; margin-top: 2rem; font-size: 0.875rem;">For more details, visit our <a href="<?php echo esc_url(home_url('/#pricing')); ?>">full pricing page</a>.</p>
            </div>
        </section>

        <!-- Social Proof Section -->
        <section id="social-proof" class="section">
            <div class="container">
                <div class="section-header" style="margin-bottom: 3rem;">
                    <div class="feature-icon" style="margin: 0 auto 1rem auto; background-color: transparent;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="hsl(var(--primary))" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-star"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                    </div>
                    <h2 class="section-title">Join 1000+ Successful Cleaning Businesses</h2>
                    <p class="section-description" style="max-width: 42rem;">See why cleaning businesses across the globe choose Nord Booking to grow their operations.</p>
                </div>

                <div class="testimonials-grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                    <!-- Testimonial 1 -->
                    <div class="card testimonial" style="padding: 1.5rem; border-left: 4px solid hsl(var(--primary));">
                        <div class="card-content" style="padding:0;">
                            <p class="testimonial-content" style="font-style: italic; margin-bottom: 1.5rem; color: hsl(var(--muted-foreground)); line-height: 1.6;">
                                "150% revenue increase since switching to Nord Booking. Their platform is intuitive and the support is top-notch!"
                            </p>
                            <div class="testimonial-author" style="display: flex; align-items: center; gap: 1rem;">
                                <!-- <div class="author-avatar" style="width: 2.5rem; height: 2.5rem; border-radius: 50%; background-color: hsl(var(--muted)); display: flex; align-items: center; justify-content: center; font-weight: 600; color: hsl(var(--foreground));">LR</div> -->
                                <div>
                                    <div class="author-name" style="font-weight: 600; color: hsl(var(--foreground));">Lisa Rodriguez</div>
                                    <div class="author-title" style="font-size: 0.875rem; color: hsl(var(--muted-foreground));">Elite Cleaning Co.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Testimonial 2 -->
                    <div class="card testimonial" style="padding: 1.5rem; border-left: 4px solid hsl(var(--primary));">
                        <div class="card-content" style="padding:0;">
                            <p class="testimonial-content" style="font-style: italic; margin-bottom: 1.5rem; color: hsl(var(--muted-foreground)); line-height: 1.6;">
                                "Perfect for our franchise operations. The multi-tenant features give us central control while empowering individual owners."
                            </p>
                            <div class="testimonial-author" style="display: flex; align-items: center; gap: 1rem;">
                                <!-- <div class="author-avatar" style="width: 2.5rem; height: 2.5rem; border-radius: 50%; background-color: hsl(var(--muted)); display: flex; align-items: center; justify-content: center; font-weight: 600; color: hsl(var(--foreground));">MJ</div> -->
                                <div>
                                    <div class="author-name" style="font-weight: 600; color: hsl(var(--foreground));">Michael Johnson</div>
                                    <div class="author-title" style="font-size: 0.875rem; color: hsl(var(--muted-foreground));">CleanPro Franchises</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Testimonial 3 -->
                    <div class="card testimonial" style="padding: 1.5rem; border-left: 4px solid hsl(var(--primary));">
                        <div class="card-content" style="padding:0;">
                            <p class="testimonial-content" style="font-style: italic; margin-bottom: 1.5rem; color: hsl(var(--muted-foreground)); line-height: 1.6;">
                                "Outstanding customer support and the platform is so easy to use. We've streamlined everything from booking to payments."
                            </p>
                            <div class="testimonial-author" style="display: flex; align-items: center; gap: 1rem;">
                                <!-- <div class="author-avatar" style="width: 2.5rem; height: 2.5rem; border-radius: 50%; background-color: hsl(var(--muted)); display: flex; align-items: center; justify-content: center; font-weight: 600; color: hsl(var(--foreground));">SW</div> -->
                                <div>
                                    <div class="author-name" style="font-weight: 600; color: hsl(var(--foreground));">Sarah Williams</div>
                                    <div class="author-title" style="font-size: 0.875rem; color: hsl(var(--muted-foreground));">Sparkle Services</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Primary CTA Section -->
        <section id="primary-cta" class="section cta-section" style="padding: 4rem 0; text-align: center; background-color: hsl(var(--primary)); color: hsl(var(--primary-foreground));">
            <div class="container">
                <h2 class="section-title" style="color: hsl(var(--primary-foreground)); font-size: clamp(1.8rem, 4vw, 2.5rem);">Ready to Transform Your Cleaning Business?</h2>
                <p class="section-description" style="color: hsl(var(--primary-foreground)/0.85); margin-bottom: 2.5rem; font-size: 1.125rem;">Join thousands of cleaning businesses that have streamlined their operations with Nord Booking.</p>
                <div class="hero-actions" style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="<?php echo esc_url(home_url('/register/')); ?>" class="btn btn-xl" style="background-color: hsl(var(--background)); color: hsl(var(--primary));">Start Free Trial</a>
                    <a href="<?php echo esc_url(home_url('/contact/#schedule-demo')); ?>" class="btn btn-outline btn-xl" style="border-color: hsl(var(--primary-foreground)/0.5); color: hsl(var(--primary-foreground)); background-color: transparent;">Schedule Demo</a>
                </div>
                <p style="margin-top: 1.5rem; font-size: 0.875rem; color: hsl(var(--primary-foreground)/0.7);">
                    No credit card required • 14-day free trial • Cancel anytime
                </p>
            </div>
        </section>

        <!-- Secondary CTA Section -->
        <section id="secondary-cta" class="section" style="padding: 4rem 0; background-color: hsl(var(--muted)/0.3);">
            <div class="container" style="text-align: center;">
                <h2 class="section-title" style="font-size: clamp(1.5rem, 3vw, 2rem);">See Nord Booking in Action</h2>
                <p class="section-description" style="margin-bottom: 2rem; font-size: 1rem;">Book a personalized demo and see how Nord Booking can work for your specific cleaning business needs.</p>
                <a href="<?php echo esc_url(home_url('/contact/#schedule-demo')); ?>" class="btn btn-primary btn-lg" style="margin-bottom: 1.5rem;">Schedule Demo</a>
                <ul style="list-style: none; padding: 0; display: flex; flex-wrap: wrap; justify-content: center; gap: 0.5rem 1.5rem; color: hsl(var(--muted-foreground)); font-size: 0.875rem;">
                    <li style="display: inline-flex; align-items: center;">✓ 30-minute personalized demo</li>
                    <li style="display: inline-flex; align-items: center;">✓ Custom setup consultation</li>
                    <li style="display: inline-flex; align-items: center;">✓ No sales pressure</li>
                </ul>
            </div>
        </section>

        <!-- FAQ Section -->
        <section id="faq" class="section">
            <div class="container">
                <div class="section-header" style="margin-bottom: 3rem;">
                    <h2 class="section-title">Frequently Asked Questions</h2>
                </div>

                <div class="faq-grid" style="max-width: 48rem; margin: 0 auto; display: grid; gap: 1rem;">
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
                            <p class="faq-answer">Yes! We offer a 14-day free trial with no credit card required. You can explore all features and see how Nord Booking works for your business.</p>
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

