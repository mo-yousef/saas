<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @package NORDBOOKING
 */

get_header(); ?>

<main id="main" class="site-main">
    <section class="error-404 not-found">
        <div class="nordbooking-404-container">
            <!-- 404 Visual Element -->
            <div class="nordbooking-404-visual">
                <div class="nordbooking-404-number">404</div>
            </div>

            <!-- Content Section -->
            <div class="nordbooking-404-content">
                <h1 class="nordbooking-404-title">
                    <?php esc_html_e('Page Found', 'NORDBOOKING'); ?>
                </h1>
                <p class="nordbooking-404-description">
                    <?php esc_html_e('It looks like nothing was found at this location. Let\'s get you back to where you need to be.', 'NORDBOOKING'); ?>
                </p>
                
                <!-- Back to Homepage Link -->
                <div class="nordbooking-404-actions">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="nordbooking-404-home-btn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9,22 9,12 15,12 15,22"></polyline>
                        </svg>
                        <?php esc_html_e('Go Back to Homepage', 'NORDBOOKING'); ?>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Custom Styles -->
    <style>
    .nordbooking-404-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 4rem 2rem;
        text-align: center;
        font-family: var(--font-family-primary, 'Inter', sans-serif);
        min-height: 60vh;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .nordbooking-404-visual {
        margin-bottom: 3rem;
        position: relative;
    }

    .nordbooking-404-number {
        font-size: clamp(6rem, 15vw, 12rem);
        font-weight: var(--font-weight-black, 900);
        color: var(--color-primary, #2563eb);
        line-height: 0.8;
        margin-bottom: 1rem;
        background: linear-gradient(135deg, var(--color-primary, #2563eb), var(--color-primary-light, #93c5fd));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .nordbooking-404-content {
        max-width: 600px;
        margin: 0 auto;
    }

    .nordbooking-404-title {
        font-size: var(--font-size-4xl, 2.25rem);
        font-weight: var(--font-weight-bold, 700);
        color: var(--color-gray-900, #0f172a);
        margin: 0 0 1rem 0;
        line-height: var(--line-height-tight, 1.25);
    }

    .nordbooking-404-description {
        font-size: var(--font-size-lg, 1.125rem);
        color: var(--color-gray-600, #475569);
        margin: 0 0 2.5rem 0;
        line-height: var(--line-height-relaxed, 1.625);
    }

    .nordbooking-404-actions {
        margin-top: 2rem;
    }

    .nordbooking-404-home-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.875rem 2rem;
        background: var(--color-primary, #2563eb);
        color: white;
        text-decoration: none;
        border-radius: var(--border-radius-lg, 0.5rem);
        font-weight: var(--font-weight-semibold, 600);
        font-size: var(--font-size-base, 1rem);
        transition: all 0.2s ease;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    }

    .nordbooking-404-home-btn:hover {
        background: var(--color-primary-dark, #1d4ed8);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px 0 rgba(37, 99, 235, 0.25);
        color: white;
        text-decoration: none;
    }

    .nordbooking-404-home-btn:focus {
        outline: 2px solid var(--color-primary-light, #93c5fd);
        outline-offset: 2px;
    }

    .nordbooking-404-home-btn svg {
        width: 20px;
        height: 20px;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .nordbooking-404-container {
            padding: 2rem 1rem;
        }
        
        .nordbooking-404-home-btn {
            padding: 0.75rem 1.5rem;
            font-size: var(--font-size-sm, 0.875rem);
        }
    }

    /* Animation */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .nordbooking-404-container > * {
        animation: fadeInUp 0.6s ease-out forwards;
    }

    .nordbooking-404-visual {
        animation-delay: 0.1s;
    }

    .nordbooking-404-content {
        animation-delay: 0.2s;
    }

    .nordbooking-404-actions {
        animation-delay: 0.3s;
    }
    </style>
</main>

<?php get_footer(); ?>