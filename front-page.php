<?php
/**
 * The template for displaying the front page.
 *
 * @package MoBooking
 */

get_header();
?>
    <style>
        header#masthead,footer#colophon {
    display: none;
}


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
            --primary: 222.2 47.4% 11.2%;
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

        /* Header */
        header {
            position: sticky;
            top: 0;
            z-index: 50;
            width: 100%;
            border-bottom: 1px solid hsl(var(--border));
            background-color: hsl(var(--background) / 0.95);
            backdrop-filter: blur(8px);
        }

        nav {
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

        .btn-secondary {
            border: 1px solid hsl(var(--border));
            background-color: hsl(var(--background));
            color: hsl(var(--foreground));
        }

        .btn-secondary:hover {
            background-color: hsl(var(--accent));
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

        /* Hero Section */
        .hero {
            padding: 6rem 0 4rem;
            text-align: center;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 9999px;
            border: 1px solid hsl(var(--border));
            background-color: hsl(var(--muted));
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 500;
            margin-bottom: 2rem;
            color: hsl(var(--muted-foreground));
        }

        .hero-title {
                font-size: clamp(2.25rem, 5vw, 4rem);
    font-weight: 800;
    line-height: 1.1;
    letter-spacing: -0.025em;
    margin-bottom: 1.5rem;
    color: hsl(var(--foreground));
    max-inline-size: 800px;
    margin-inline: auto;
        }

        .hero-description {
            font-size: 1.25rem;
            line-height: 1.6;
            color: hsl(var(--muted-foreground));
            max-width: 42rem;
            margin: 0 auto 2.5rem;
        }

        .hero-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 2rem;
            margin-bottom: 3rem;
        }

        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 2rem;
            flex-wrap: wrap;
            margin-top: 3rem;
        }

        .stat {
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: hsl(var(--foreground));
        }

        .stat-label {
            font-size: 0.875rem;
            color: hsl(var(--muted-foreground));
        }

        /* Card Component */
        .card {
            border-radius: var(--radius);
            border: 1px solid hsl(var(--border));
            background-color: hsl(var(--card));
            color: hsl(var(--card-foreground));
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
        }

        .card-header {
            display: flex;
            flex-direction: column;
            space-y: 1.5;
            padding: 1.5rem;
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 600;
            line-height: 1;
            letter-spacing: -0.025em;
        }

        .card-description {
            font-size: 0.875rem;
            color: hsl(var(--muted-foreground));
        }

        .card-content {
            padding: 1.5rem;
        }

        /* Section Styles */
        .section {
            padding: 6rem 0;
        }

        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 9999px;
            border: 1px solid hsl(var(--border));
            background-color: hsl(var(--muted));
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 500;
            margin-bottom: 1rem;
            color: hsl(var(--muted-foreground));
        }

        .section-title {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 800;
            line-height: 1.1;
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

        /* Features Grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 4rem;
        }

        .feature-card {
            padding: 2rem;
            transition: all 0.2s;
        }

        .feature-card:hover {
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }

        .feature-icon {
            width: 3rem;
            height: 3rem;
            background-color: hsl(var(--primary));
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.5rem;
            color: hsl(var(--primary-foreground));
        }

        .feature-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: hsl(var(--foreground));
        }

        .feature-description {
            color: hsl(var(--muted-foreground));
            line-height: 1.6;
        }

        /* Steps */
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 3rem;
            margin-top: 4rem;
        }

        .step {
            text-align: center;
        }

        .step-number {
            width: 3rem;
            height: 3rem;
            background-color: hsl(var(--primary));
            color: hsl(var(--primary-foreground));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .step-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: hsl(var(--foreground));
        }

        .step-description {
            color: hsl(var(--muted-foreground));
            line-height: 1.6;
        }

        /* Pricing */
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 4rem;
        }

        .pricing-card {
            position: relative;
            padding: 2rem;
            transition: all 0.2s;
        }

        .pricing-card:hover {
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }

        .pricing-badge {
            position: absolute;
            top: -0.5rem;
            left: 50%;
            transform: translateX(-50%);
            background-color: hsl(var(--primary));
            color: hsl(var(--primary-foreground));
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .plan-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: hsl(var(--foreground));
        }

        .plan-price {
            font-size: 3rem;
            font-weight: 800;
            color: hsl(var(--foreground));
            margin-bottom: 0.25rem;
        }

        .plan-period {
            color: hsl(var(--muted-foreground));
            margin-bottom: 0;
        }

        .plan-features {
            list-style: none;
            margin-bottom: 2rem;
            space-y: 0.75rem;
        }

        .plan-features li {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: hsl(var(--muted-foreground));
            margin-bottom: 0.75rem;
        }

        .plan-features li::before {
            content: '✓';
            color: hsl(var(--primary));
            font-weight: 600;
            width: 1rem;
            text-align: center;
        }

        /* Testimonials */
        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 4rem;
        }

        .testimonial {
            padding: 2rem;
            border-left: 4px solid hsl(var(--border));
        }

        .testimonial-content {
            font-style: italic;
            margin-bottom: 1.5rem;
            color: hsl(var(--muted-foreground));
            line-height: 1.6;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .author-avatar {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            background-color: hsl(var(--muted));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: hsl(var(--foreground));
        }

        .author-name {
            font-weight: 500;
            color: hsl(var(--foreground));
        }

        .author-title {
            font-size: 0.875rem;
            color: hsl(var(--muted-foreground));
        }

        /* CTA Section */
        .cta-section {
            padding: 6rem 0;
            text-align: center;
            background-color: hsl(var(--muted) / 0.5);
        }

        /* Footer */
        footer {
            border-top: 1px solid hsl(var(--border));
            padding: 4rem 0 2rem;
            background-color: hsl(var(--muted) / 0.3);
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .footer-section h3 {
            font-weight: 600;
            margin-bottom: 1rem;
            color: hsl(var(--foreground));
        }

        .footer-section ul {
            list-style: none;
            space-y: 0.5rem;
        }

        .footer-section ul li {
            margin-bottom: 0.5rem;
        }

        .footer-section ul li a {
            color: hsl(var(--muted-foreground));
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.2s;
        }

        .footer-section ul li a:hover {
            color: hsl(var(--foreground));
        }

        .footer-bottom {
            border-top: 1px solid hsl(var(--border));
            padding-top: 2rem;
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
                display: none;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .hero-actions {
                flex-direction: column;
                align-items: center;
            }

            .hero-stats {
                flex-direction: column;
                gap: 1rem;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .steps-grid {
                grid-template-columns: 1fr;
            }

            .pricing-grid {
                grid-template-columns: 1fr;
            }

            .testimonials-grid {
                grid-template-columns: 1fr;
            }

            .footer-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Animations */
        .fade-in {
            opacity: 0;
            animation: fadeIn 0.6s ease forwards;
        }

        @keyframes fadeIn {
            to {
                opacity: 1;
            }
        }

        .slide-up {
            opacity: 0;
            transform: translateY(20px);
            animation: slideUp 0.6s ease forwards;
        }

        @keyframes slideUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .delay-100 { animation-delay: 0.1s; }
        .delay-200 { animation-delay: 0.2s; }
        .delay-300 { animation-delay: 0.3s; }
        .delay-400 { animation-delay: 0.4s; }
        .delay-500 { animation-delay: 0.5s; }
        .delay-600 { animation-delay: 0.6s; }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <nav>
                <div class="logo">MoBooking</div>
                <ul class="nav-links">
                    <li><a href="#features">Features</a></li>
                    <li><a href="#how-it-works">How It Works</a></li>
                    <li><a href="#pricing">Pricing</a></li>
                    <li><a href="#testimonials">Reviews</a></li>
                </ul>
                <div style="display: flex; gap: 0.75rem;">
                    <a href="<?php echo esc_url(home_url('/login/')); ?>" class="btn btn-outline btn-sm">Login</a>
                    <a href="<?php echo esc_url(home_url('/register/')); ?>" class="btn btn-primary btn-sm">Sign Up</a>
                </div>
                <button class="mobile-menu-toggle">☰</button>
            </nav>
        </div>
    </header>

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
                            <a href="<?php echo esc_url(home_url('/register/')); ?>" class="btn btn-outline btn-lg" style="width: 100%;">Choose Starter</a>
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
                            <a href="<?php echo esc_url(home_url('/register/')); ?>" class="btn btn-primary btn-lg" style="width: 100%;">Choose Professional</a>
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
                            <a href="/dashboard" class="btn btn-outline btn-lg" style="width: 100%;">Contact Sales</a> <!-- Assuming /dashboard or contact page -->
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
                        Join thousands of cleaning businesses that have transformed their operations with MoBooking.
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
                                "Outstanding customer support and the platform is so easy to use. We've seen a 150% increase in revenue since switching to MoBooking. Our customers love how simple it is to book our services online."
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
                        MoBooking integrates with the platforms you already use to streamline your workflow.
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
                        Everything you need to know about MoBooking and how it can help your cleaning business.
                    </p>
                </div>

                <div style="max-width: 48rem; margin: 0 auto;">
                    <div class="card slide-up" style="margin-bottom: 1rem;">
                        <div class="card-content">
                            <h3 style="font-weight: 600; margin-bottom: 0.5rem;">How quickly can I get started?</h3>
                            <p style="color: hsl(var(--muted-foreground)); margin: 0;">You can set up your account and start accepting bookings within 10 minutes. Our onboarding process is designed to be simple and straightforward.</p>
                        </div>
                    </div>

                    <div class="card slide-up delay-100" style="margin-bottom: 1rem;">
                        <div class="card-content">
                            <h3 style="font-weight: 600; margin-bottom: 0.5rem;">Do I need technical skills to use MoBooking?</h3>
                            <p style="color: hsl(var(--muted-foreground)); margin: 0;">Not at all! MoBooking is designed for business owners, not developers. Everything is point-and-click with no coding required.</p>
                        </div>
                    </div>

                    <div class="card slide-up delay-200" style="margin-bottom: 1rem;">
                        <div class="card-content">
                            <h3 style="font-weight: 600; margin-bottom: 0.5rem;">Can I customize the booking form to match my brand?</h3>
                            <p style="color: hsl(var(--muted-foreground)); margin: 0;">Yes! Professional and Enterprise plans include custom branding options to match your business colors, logo, and style.</p>
                        </div>
                    </div>

                    <div class="card slide-up delay-300" style="margin-bottom: 1rem;">
                        <div class="card-content">
                            <h3 style="font-weight: 600; margin-bottom: 0.5rem;">What payment methods are supported?</h3>
                            <p style="color: hsl(var(--muted-foreground)); margin: 0;">Through our Stripe integration, we support all major credit cards, digital wallets like Apple Pay and Google Pay, and bank transfers.</p>
                        </div>
                    </div>

                    <div class="card slide-up delay-400">
                        <div class="card-content">
                            <h3 style="font-weight: 600; margin-bottom: 0.5rem;">Is there a free trial?</h3>
                            <p style="color: hsl(var(--muted-foreground)); margin: 0;">Yes! We offer a 14-day free trial with no credit card required. You can explore all features and see how MoBooking works for your business.</p>
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
                    Start your free trial today and see how MoBooking can help you streamline operations and grow your revenue.
                </p>

                <div class="hero-actions slide-up delay-200">
                    <a href="<?php echo esc_url(home_url('/register/')); ?>" class="btn btn-primary btn-xl">Start Free Trial</a>
                    <a href="/dashboard" class="btn btn-outline btn-xl">Schedule Demo</a> <!-- Assuming /dashboard or contact page -->
                </div>

                <p style="margin-top: 1rem; font-size: 0.875rem; color: hsl(var(--muted-foreground));">
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
                    <h3>MoBooking</h3>
                    <p style="color: hsl(var(--muted-foreground)); margin-bottom: 1rem;">The ultimate SaaS platform for cleaning service companies. Streamline your bookings, manage customers, and grow your business.</p>
                    <div style="display: flex; gap: 1rem;">
                        <a href="/dashboard" style="color: hsl(var(--muted-foreground)); text-decoration: none;">Twitter</a>
                        <a href="/dashboard" style="color: hsl(var(--muted-foreground)); text-decoration: none;">LinkedIn</a>
                        <a href="/dashboard" style="color: hsl(var(--muted-foreground)); text-decoration: none;">Facebook</a>
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
                <p>&copy; 2024 MoBooking. All rights reserved.</p>
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
