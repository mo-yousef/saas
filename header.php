<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Professional booking management SaaS platform for cleaning services. Streamline operations, increase bookings, and grow your business with NordBK.">
    <meta name="keywords" content="cleaning business software, booking management, SaaS platform, cleaning services, appointment scheduling">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo home_url(); ?>">
    <meta property="og:title" content="NordBK - Professional Booking Management for Cleaning Services">
    <meta property="og:description" content="The scheduling infrastructure for cleaning businesses. Trusted by 500+ professional cleaning services.">
    <meta property="og:image" content="<?php echo get_template_directory_uri(); ?>/assets/images/og-image.jpg">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo home_url(); ?>">
    <meta property="twitter:title" content="NordBK - Professional Booking Management for Cleaning Services">
    <meta property="twitter:description" content="The scheduling infrastructure for cleaning businesses. Trusted by 500+ professional cleaning services.">
    <meta property="twitter:image" content="<?php echo get_template_directory_uri(); ?>/assets/images/twitter-image.jpg">
    
    <!-- Schema.org markup -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "SoftwareApplication",
      "name": "NordBK Professional Booking Management",
      "applicationCategory": "BusinessApplication",
      "operatingSystem": "Web Browser",
      "description": "Professional booking management SaaS platform for cleaning services",
      "url": "<?php echo home_url(); ?>",
      "offers": {
        "@type": "Offer",
        "price": "29",
        "priceCurrency": "USD",
        "priceValidUntil": "2025-12-31"
      },
      "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "4.8",
        "reviewCount": "127"
      },
      "provider": {
        "@type": "Organization",
        "name": "NordBK",
        "url": "<?php echo home_url(); ?>",
        "address": {
          "@type": "PostalAddress",
          "addressCountry": "NO"
        }
      }
    }
    </script>
    
    <!-- Cal.com-inspired Design System -->
    <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/assets/css/cal-design-system.css">
    <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/assets/css/header-component.css">
    <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/assets/css/hero-section.css">
    <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/assets/css/trust-section.css">
    <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/assets/css/features-section.css">
    <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/assets/css/testimonials-section.css">
    <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/assets/css/pricing-section.css">
    <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/assets/css/footer-section.css">
    <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/assets/css/responsive-fixes.css">
    <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/assets/css/accessibility-enhancements.css">
    
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

    <!-- Skip to content link for accessibility -->
    <a href="#main-content" class="skip-to-content sr-only">Skip to main content</a>
    
    <!-- Header with Cal.com styling -->
    <header class="site-header">
        <nav class="main-navigation">
            <div class="nav-brand">
                <div class="logo-container">
                    <svg class="logo-icon" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2L2 7v10c0 5.55 3.84 9.739 9 11 5.16-1.261 9-5.45 9-11V7l-10-5z"/>
                        <path d="M9 12l2 2 4-4" stroke="white" stroke-width="2" fill="none"/>
                    </svg>
                    <span class="brand-name">NordBK</span>
                </div>
            </div>
            <ul class="nav-menu">
                <li><a href="#features" class="nav-link">Features</a></li>
                <li><a href="#pricing" class="nav-link">Pricing</a></li>
                <li><a href="#customers" class="nav-link">Customers</a></li>
                <li><a href="#support" class="nav-link">Support</a></li>
            </ul>
            <div class="nav-actions">
                <div class="trust-indicators-subtle">
                    <span class="trust-badge">Enterprise Security</span>
                </div>
                <a href="<?php echo esc_url(home_url('/login/')); ?>" class="btn btn-ghost">Sign in</a>
                <a href="<?php echo esc_url(home_url('/register/')); ?>" class="btn btn-primary">Get started</a>
            </div>
            <button class="mobile-menu-toggle" aria-label="Toggle mobile menu">
                <div class="hamburger">
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </div>
            </button>
        </nav>
    </header>

    <!-- Mobile Menu -->
    <div class="mobile-menu-overlay"></div>
    <div class="mobile-menu">
        <div class="mobile-menu-header">
            <div class="logo-container">
                <svg class="logo-icon" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2L2 7v10c0 5.55 3.84 9.739 9 11 5.16-1.261 9-5.45 9-11V7l-10-5z"/>
                    <path d="M9 12l2 2 4-4" stroke="white" stroke-width="2" fill="none"/>
                </svg>
                <span class="brand-name">NordBK</span>
            </div>
            <button class="mobile-menu-close" aria-label="Close mobile menu">×</button>
        </div>
        <nav class="mobile-menu-nav">
            <ul class="mobile-nav-menu">
                <li><a href="#features" class="mobile-nav-link">Features</a></li>
                <li><a href="#pricing" class="mobile-nav-link">Pricing</a></li>
                <li><a href="#customers" class="mobile-nav-link">Customers</a></li>
                <li><a href="#support" class="mobile-nav-link">Support</a></li>
            </ul>
        </nav>
        <div class="mobile-menu-actions">
            <div class="mobile-trust-badge">🔒 Enterprise Security</div>
            <a href="<?php echo esc_url(home_url('/login/')); ?>" class="btn btn-ghost btn-full">Sign in</a>
            <a href="<?php echo esc_url(home_url('/register/')); ?>" class="btn btn-primary btn-full">Get started</a>
        </div>
    </div>