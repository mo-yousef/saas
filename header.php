<?php
/**
 * Dynamic Header Template
 * Responsive header with user authentication state management and SVG icons
 * 
 * @package NORDBOOKING
 */

// Get current user and check authentication status
$current_user = wp_get_current_user();
$is_logged_in = is_user_logged_in();
$has_dashboard_access = false;

if ($is_logged_in) {
    $has_dashboard_access = user_can($current_user, \NORDBOOKING\Classes\Auth::ACCESS_NORDBOOKING_DASHBOARD);
}

// Get current page info for navigation highlighting
$current_page = get_queried_object();
$current_url = home_url($_SERVER['REQUEST_URI']);
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    
    <!-- Preload critical fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <?php wp_head(); ?>
    
    <style>
        /* Critical CSS for header - loaded inline for performance */
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            --white: #ffffff;
            --success-color: #10b981;
            --border-radius: 8px;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --transition-fast: all 0.15s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        .nordbk-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--gray-200);
            transition: var(--transition-fast);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        .nordbk-header.scrolled {
            background-color: rgba(255, 255, 255, 0.98);
            box-shadow: var(--shadow-sm);
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .header-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 0;
        }
        
        .header-brand {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--gray-900);
            text-decoration: none;
        }
        
        .brand-icon {
            width: 32px;
            height: 32px;
            color: var(--primary-color);
        }
        
        .nav-menu {
            display: flex;
            align-items: center;
            gap: 2rem;
            list-style: none;
            margin: 0;
        }
        
        .nav-link {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--gray-600);
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            transition: var(--transition-fast);
            text-decoration: none;
        }
        
        .nav-link:hover, .nav-link.active {
            color: var(--gray-900);
            background-color: var(--gray-50);
        }
        
        .nav-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .trust-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            background-color: var(--gray-50);
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--gray-600);
        }
        
        .trust-icon {
            width: 16px;
            height: 16px;
            color: var(--success-color);
        }
        
        .auth-buttons {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: var(--border-radius);
            border: 1px solid transparent;
            cursor: pointer;
            transition: var(--transition-fast);
            text-decoration: none;
            min-height: 40px;
            padding: 0.5rem 1rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            color: var(--white);
        }
        
        .btn-outline {
            background-color: transparent;
            color: var(--gray-700);
            border-color: var(--gray-300);
        }
        
        .btn-outline:hover {
            background-color: var(--gray-50);
            color: var(--gray-900);
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.8125rem;
        }
        
        .btn-icon {
            width: 16px;
            height: 16px;
        }
        
        .user-menu {
            position: relative;
        }
        
        .user-menu-trigger {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            background: none;
            border: none;
            border-radius: var(--border-radius);
            color: var(--gray-600);
            cursor: pointer;
            transition: var(--transition-fast);
        }
        
        .user-menu-trigger:hover {
            background-color: var(--gray-50);
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: var(--gray-100);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--gray-600);
        }
        
        .chevron-down {
            width: 16px;
            height: 16px;
        }
        
        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 0.5rem;
            min-width: 200px;
            background-color: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            box-shadow: var(--shadow-xl);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: var(--transition-fast);
        }
        
        .user-menu:hover .user-dropdown,
        .user-menu.open .user-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            color: var(--gray-700);
            text-decoration: none;
            transition: var(--transition-fast);
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
        }
        
        .dropdown-item:hover {
            background-color: var(--gray-50);
        }
        
        .dropdown-icon {
            width: 16px;
            height: 16px;
        }
        
        .dropdown-separator {
            height: 1px;
            background-color: var(--gray-200);
            margin: 0.25rem 0;
        }
        
        .mobile-menu-toggle {
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            width: 40px;
            height: 40px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            border-radius: var(--border-radius);
            transition: var(--transition-fast);
        }
        
        .mobile-menu-toggle:hover {
            background-color: var(--gray-50);
        }
        
        .hamburger-line {
            width: 20px;
            height: 2px;
            background-color: var(--gray-600);
            margin: 2px 0;
            transition: var(--transition-fast);
            border-radius: 1px;
        }
        
        .mobile-menu-toggle.open .hamburger-line:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }
        
        .mobile-menu-toggle.open .hamburger-line:nth-child(2) {
            opacity: 0;
        }
        
        .mobile-menu-toggle.open .hamburger-line:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -6px);
        }
        
        .mobile-menu {
            position: fixed;
            top: 0;
            right: -100%;
            width: 100%;
            max-width: 400px;
            height: 100vh;
            background-color: var(--white);
            box-shadow: var(--shadow-xl);
            transition: all 0.3s ease;
            z-index: 150;
        }
        
        .mobile-menu.open {
            right: 0;
        }
        
        .mobile-menu-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 140;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .mobile-menu-overlay.open {
            opacity: 1;
            visibility: visible;
        }
        
        .mobile-menu-content {
            padding: 2rem 1.5rem;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .mobile-menu-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .mobile-close-btn {
            width: 32px;
            height: 32px;
            border: none;
            background: none;
            cursor: pointer;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .mobile-nav-links {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }
        
        .mobile-nav-link {
            padding: 1rem;
            font-size: 1.125rem;
            font-weight: 500;
            color: var(--gray-700);
            border-radius: var(--border-radius);
            transition: var(--transition-fast);
            text-decoration: none;
        }
        
        .mobile-nav-link:hover {
            background-color: var(--gray-50);
        }
        
        .mobile-auth-buttons {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: auto;
        }
        
        .btn-mobile {
            justify-content: center;
            padding: 1rem;
        }
        
        /* Responsive breakpoints */
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }
            
            .mobile-menu-toggle {
                display: flex;
            }
            
            .trust-badge {
                display: none;
            }
            
            .auth-buttons {
                display: none;
            }
        }
        
        @media (max-width: 480px) {
            .header-nav {
                padding: 0.75rem 0;
            }
            
            .header-brand {
                font-size: 1.125rem;
            }
            
            .brand-icon {
                width: 28px;
                height: 28px;
            }
            
            body {
                padding-top: 70px;
            }
        }
        
        /* Focus styles for accessibility */
        .btn:focus,
        .nav-link:focus,
        .user-menu-trigger:focus,
        .mobile-menu-toggle:focus,
        .mobile-close-btn:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }
        
        /* Reduced motion support */
        @media (prefers-reduced-motion: reduce) {
            .nordbk-header,
            .nav-link,
            .btn,
            .user-dropdown,
            .mobile-menu,
            .hamburger-line,
            .mobile-menu-overlay {
                transition: none;
            }
        }
    </style>
</head>

<body <?php body_class(); ?>>

<!-- Mobile Menu Overlay -->
<div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>

<!-- Mobile Menu -->
<div class="mobile-menu" id="mobileMenu">
    <div class="mobile-menu-content">
        <div class="mobile-menu-header">
            <div class="header-brand">
<svg width="24" height="24" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
<g clip-path="url(#clip0_1113_5151)">
<path d="M3.35168e-06 8.37315e-07C-0.00685864 26.518 10.5231 51.9526 29.2735 70.7091C48.0239 89.4656 73.459 100.008 99.9838 100.016V8.37315e-07H3.35168e-06ZM99.9838 100.016H200V8.37315e-07C186.863 -0.00169886 173.854 2.58433 161.717 7.61033C149.581 12.6363 138.553 20.0038 129.265 29.2918C119.977 38.5799 112.61 49.6065 107.586 61.7416C102.562 73.8767 99.9787 86.8826 99.9838 100.016ZM99.9838 100.016V200H200C200.001 186.869 197.414 173.867 192.388 161.735C187.362 149.604 179.995 138.581 170.707 129.297C161.42 120.012 150.394 112.647 138.259 107.623C126.124 102.599 113.118 100.014 99.9838 100.016ZM99.9838 100.016H3.35168e-06V200C26.5203 199.995 51.9525 189.458 70.7027 170.708C89.453 151.958 99.9855 126.53 99.9838 100.016Z" fill="#2563eb"/>
</g>
<defs>
<clipPath id="clip0_1113_5151">
<rect width="200" height="200" fill="white" transform="translate(200) rotate(90)"/>
</clipPath>
</defs>
</svg>
                Nord Booking
            </div>
            <button class="mobile-close-btn" id="mobileCloseBtn" aria-label="Close menu">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        
        <nav class="mobile-nav-links">
            <a href="<?php echo esc_url(home_url('/features/')); ?>" class="mobile-nav-link">Features</a>
            <a href="#how-it-works" class="mobile-nav-link">How It Works</a>
            <a href="#pricing" class="mobile-nav-link">Pricing</a>
            <a href="#testimonials" class="mobile-nav-link">Reviews</a>
        </nav>
        
        <div class="mobile-auth-buttons">
            <?php if ($is_logged_in) : ?>
                <a href="<?php echo esc_url(home_url('/dashboard/')); ?>" class="btn btn-primary btn-mobile">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="btn-icon">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    My Account
                </a>
                <form method="post" action="<?php echo esc_url(wp_logout_url(home_url())); ?>">
                    <button type="submit" class="btn btn-outline btn-mobile">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="btn-icon">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16,17 21,12 16,7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        Logout
                    </button>
                </form>
            <?php else : ?>
                <a href="<?php echo esc_url(home_url('/login/')); ?>" class="btn btn-outline btn-mobile">Login</a>
                <a href="<?php echo esc_url(home_url('/register/')); ?>" class="btn btn-primary btn-mobile">Sign Up</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Header -->
<header class="nordbk-header" id="header">
    <div class="header-container">
        <nav class="header-nav">
            <!-- Brand Logo -->
            <a href="<?php echo esc_url(home_url('/')); ?>" class="header-brand">
<svg width="24" height="24" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
<g clip-path="url(#clip0_1113_5151)">
<path d="M3.35168e-06 8.37315e-07C-0.00685864 26.518 10.5231 51.9526 29.2735 70.7091C48.0239 89.4656 73.459 100.008 99.9838 100.016V8.37315e-07H3.35168e-06ZM99.9838 100.016H200V8.37315e-07C186.863 -0.00169886 173.854 2.58433 161.717 7.61033C149.581 12.6363 138.553 20.0038 129.265 29.2918C119.977 38.5799 112.61 49.6065 107.586 61.7416C102.562 73.8767 99.9787 86.8826 99.9838 100.016ZM99.9838 100.016V200H200C200.001 186.869 197.414 173.867 192.388 161.735C187.362 149.604 179.995 138.581 170.707 129.297C161.42 120.012 150.394 112.647 138.259 107.623C126.124 102.599 113.118 100.014 99.9838 100.016ZM99.9838 100.016H3.35168e-06V200C26.5203 199.995 51.9525 189.458 70.7027 170.708C89.453 151.958 99.9855 126.53 99.9838 100.016Z" fill="#2563eb"/>
</g>
<defs>
<clipPath id="clip0_1113_5151">
<rect width="200" height="200" fill="white" transform="translate(200) rotate(90)"/>
</clipPath>
</defs>
</svg>
                Nord Booking
            </a>
            
            <!-- Desktop Navigation -->
            <ul class="nav-menu">
                <li><a href="<?php echo esc_url(home_url('/features/')); ?>" class="nav-link">Features</a></li>
                <li><a href="#how-it-works" class="nav-link">How It Works</a></li>
                <li><a href="#pricing" class="nav-link">Pricing</a></li>
                <li><a href="#testimonials" class="nav-link">Reviews</a></li>
            </ul>
            
            <!-- Desktop Actions -->
            <div class="nav-actions">
                
                <!-- Authentication Buttons -->
                <div class="auth-buttons">
                    <?php if ($is_logged_in) : ?>
                        <!-- User Menu -->
                        <div class="user-menu">
                            <button class="user-menu-trigger" id="userMenuTrigger" aria-haspopup="true" aria-expanded="false">
                                <div class="user-avatar">
                                    <?php echo esc_html(strtoupper(substr($current_user->display_name, 0, 1))); ?>
                                </div>
                                <svg class="chevron-down" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6,9 12,15 18,9"></polyline>
                                </svg>
                            </button>
                            <div class="user-dropdown" id="userDropdown" role="menu">
                                <?php if ($has_dashboard_access) : ?>
                                    <a href="<?php echo esc_url(home_url('/dashboard/')); ?>" class="dropdown-item" role="menuitem">
                                        <svg class="dropdown-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                            <line x1="9" y1="15" x2="15" y2="9"></line>
                                        </svg>
                                        Dashboard
                                    </a>
                                <?php endif; ?>
                                <a href="<?php echo esc_url(home_url('/profile/')); ?>" class="dropdown-item" role="menuitem">
                                    <svg class="dropdown-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                    Profile
                                </a>
                                <a href="<?php echo esc_url(home_url('/settings/')); ?>" class="dropdown-item" role="menuitem">
                                    <svg class="dropdown-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="3"></circle>
                                        <path d="M12 1v6m0 6v6m11-7h-6m-6 0H1"></path>
                                    </svg>
                                    Settings
                                </a>
                                <div class="dropdown-separator"></div>
                                <form method="post" action="<?php echo esc_url(wp_logout_url(home_url())); ?>" style="margin: 0;">
                                    <button type="submit" class="dropdown-item" role="menuitem">
                                        <svg class="dropdown-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                            <polyline points="16,17 21,12 16,7"></polyline>
                                            <line x1="21" y1="12" x2="9" y2="12"></line>
                                        </svg>
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php else : ?>
                        <!-- Guest Authentication Buttons -->
                        <a href="<?php echo esc_url(home_url('/login/')); ?>" class="btn btn-outline btn-sm">Login</a>
                        <a href="<?php echo esc_url(home_url('/register/')); ?>" class="btn btn-primary btn-sm">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Open menu" aria-expanded="false">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>
        </nav>
    </div>
</header>

<script>
    // Header scroll effect
    window.addEventListener('scroll', function() {
        const header = document.getElementById('header');
        if (window.scrollY > 10) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });

    // Mobile menu functionality
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
    const mobileCloseBtn = document.getElementById('mobileCloseBtn');

    function openMobileMenu() {
        mobileMenu.classList.add('open');
        mobileMenuOverlay.classList.add('open');
        mobileMenuToggle.classList.add('open');
        mobileMenuToggle.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    }

    function closeMobileMenu() {
        mobileMenu.classList.remove('open');
        mobileMenuOverlay.classList.remove('open');
        mobileMenuToggle.classList.remove('open');
        mobileMenuToggle.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    }

    mobileMenuToggle.addEventListener('click', function() {
        if (mobileMenu.classList.contains('open')) {
            closeMobileMenu();
        } else {
            openMobileMenu();
        }
    });

    mobileCloseBtn.addEventListener('click', closeMobileMenu);
    mobileMenuOverlay.addEventListener('click', closeMobileMenu);

    // User menu functionality (if logged in)
    const userMenuTrigger = document.getElementById('userMenuTrigger');
    const userDropdown = document.getElementById('userDropdown');
    
    if (userMenuTrigger && userDropdown) {
        userMenuTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            const isOpen = userMenuTrigger.getAttribute('aria-expanded') === 'true';
            
            if (isOpen) {
                userMenuTrigger.setAttribute('aria-expanded', 'false');
                userDropdown.parentElement.classList.remove('open');
            } else {
                userMenuTrigger.setAttribute('aria-expanded', 'true');
                userDropdown.parentElement.classList.add('open');
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            userMenuTrigger.setAttribute('aria-expanded', 'false');
            userDropdown.parentElement.classList.remove('open');
        });
    }

    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (mobileMenu.classList.contains('open')) {
                closeMobileMenu();
            }
            if (userMenuTrigger && userMenuTrigger.getAttribute('aria-expanded') === 'true') {
                userMenuTrigger.setAttribute('aria-expanded', 'false');
                userDropdown.parentElement.classList.remove('open');
            }
        }
    });
</script>

