<?php
/**
 * The header for NordBK theme
 *
 * @package Nord_Booking
 * @version 1.0
 */
?>
    <!-- Header -->
    <header>
        <div class="container">
            <nav>
                <div class="logo">Nord Booking</div>
                <ul class="nav-links">
                    <li><a href="<?php echo esc_url(home_url('/features/')); ?>">Features</a></li>
                    <li><a href="#how-it-works">How It Works</a></li>
                    <li><a href="#pricing">Pricing</a></li>
                    <li><a href="#testimonials">Reviews</a></li>
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

<body <?php body_class(); ?>>    
    <?php wp_body_open(); ?>

