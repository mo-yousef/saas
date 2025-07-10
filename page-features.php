<?php
/**
 * Template Name: Features Page
 *
 * @package MoBooking
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
</head>
<body>
    <!-- Header (copied from front-page.php and adapted) -->
    <header class="front-page-header">
        <div class="container">
            <nav class="front-page-nav">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="logo">MoBooking</a>
                <ul class="nav-links">
                    <li><a href="<?php echo esc_url(home_url('/features/')); ?>">Features</a></li>
                    <li><a href="<?php echo esc_url(home_url('/#how-it-works')); ?>">How It Works</a></li>
                    <li><a href="<?php echo esc_url(home_url('/#pricing')); ?>">Pricing</a></li>
                    <li><a href="<?php echo esc_url(home_url('/#testimonials')); ?>">Reviews</a></li>
                </ul>
                <div style="display: flex; gap: 0.75rem; align-items: center;">
                    <?php if (is_user_logged_in()) : ?>
                        <a href="<?php echo esc_url(home_url('/dashboard/')); ?>" class="btn btn-primary btn-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-user" style="margin-right: 0.5rem;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            My Account
                        </a>
                    <?php else : ?>
                        <a href="<?php echo esc_url(home_url('/login/')); ?>" class="btn btn-outline btn-sm">Login</a>
                        <a href="<?php echo esc_url(home_url('/register/')); ?>" class="btn btn-primary btn-sm">Sign Up</a>
                    <?php endif; ?>
                </div>
                <button class="mobile-menu-toggle">☰</button>
            </nav>
        </div>
    </header>

    <main id="content" class="site-content">
        <section id="page-features" class="section">
            <div class="container">
                <div class="section-header">
                    <h1 class="section-title">Our Amazing Features</h1>
                    <p class="section-description">
                        Discover the powerful tools and functionalities MoBooking offers to streamline your business.
                    </p>
                </div>

                <div class="features-grid">
                    <!-- Placeholder Feature 1 -->
                    <div class="card feature-card">
                        <div class="feature-icon">💡</div>
                        <h3 class="feature-title">Placeholder Feature One</h3>
                        <p class="feature-description">
                            This is a description for the first amazing feature. Replace this with actual content.
                        </p>
                    </div>

                    <!-- Placeholder Feature 2 -->
                    <div class="card feature-card">
                        <div class="feature-icon">🚀</div>
                        <h3 class="feature-title">Placeholder Feature Two</h3>
                        <p class="feature-description">
                            Explain the benefits and details of the second key feature here. Make it compelling.
                        </p>
                    </div>

                    <!-- Placeholder Feature 3 -->
                    <div class="card feature-card">
                        <div class="feature-icon">⚙️</div>
                        <h3 class="feature-title">Placeholder Feature Three</h3>
                        <p class="feature-description">
                            Describe the third important feature. Focus on how it helps the user.
                        </p>
                    </div>
                     <!-- Placeholder Feature 4 (Optional) -->
                    <div class="card feature-card">
                        <div class="feature-icon">🌟</div>
                        <h3 class="feature-title">Placeholder Feature Four</h3>
                        <p class="feature-description">
                            Add another feature if needed. Keep descriptions concise and informative.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer (copied from front-page.php and adapted) -->
    <footer class="front-page-footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> MoBooking. All rights reserved. | <a href="<?php echo esc_url(home_url('/privacy-policy/')); ?>">Privacy Policy</a> | <a href="<?php echo esc_url(home_url('/terms-of-service/')); ?>">Terms of Service</a></p>
            </div>
        </div>
    </footer>
    <script>
        // Basic mobile menu toggle for the copied header
        // Ensure this doesn't conflict if you have a global script
        const mobileMenuButton = document.querySelector('.front-page-header .mobile-menu-toggle');
        const navLinks = document.querySelector('.front-page-header .nav-links');

        if (mobileMenuButton && navLinks) {
            mobileMenuButton.addEventListener('click', function() {
                const isDisplayed = navLinks.style.display === 'flex' || getComputedStyle(navLinks).display === 'flex';
                if (isDisplayed && window.innerWidth <= 768) { // Check if it's actually in mobile view
                     navLinks.style.display = 'none';
                } else {
                    navLinks.style.display = 'flex';
                     // For mobile: make it block, stack vertically
                    if(window.innerWidth <= 768) {
                        navLinks.style.flexDirection = 'column';
                        navLinks.style.position = 'absolute';
                        navLinks.style.top = '4rem'; // Below header
                        navLinks.style.left = '0';
                        navLinks.style.right = '0';
                        navLinks.style.backgroundColor = 'hsl(var(--background))';
                        navLinks.style.padding = '1rem';
                        navLinks.style.borderBottom = '1px solid hsl(var(--border))';
                        navLinks.style.zIndex = '40'; // Ensure it's above content but below sticky header
                    }
                }
            });
        }
    </script>
<?php
// get_footer(); // Using custom footer from front-page.php style
wp_footer(); // Standard WordPress hook, good practice
?>
</body>
</html>
