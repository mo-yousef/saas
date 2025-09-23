<?php
/**
 * The template for displaying the footer.
 *
 * @package Nord Booking
 */

// Text domain for translations
$nbk_text_domain = 'nord-booking';
?>

<style>
    /* ==================================================
     * NBK FOOTER STYLES WITH DARK BACKGROUND
     * ================================================== */
    .nbk-footer {
        background-color: #1a1a1a;
        color: #ffffff;
        border-top: 1px solid #333333;
        padding: 4rem 0 2rem;
    }
    .nbk-footer__section h3 {
        font-weight: 700;
        margin-bottom: 1.5rem;
        color: #ffffff;
        font-size: 1.25rem;
        letter-spacing: -0.025em;
    }

    .nbk-footer__section p {
        color: #a0a0a0;
        line-height: 1.6;
        margin-bottom: 1.5rem;
        font-size: 0.95rem;
    }

    .nbk-footer__section ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .nbk-footer__section ul li {
        margin-bottom: 0.75rem;
    }

    .nbk-footer__section ul li a {
        color: #a0a0a0;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 400;
        transition: all 0.2s ease;
        display: inline-block;
        position: relative;
    }

    .nbk-footer__section ul li a:hover {
        color: #ffffff;
        transform: translateX(4px);
    }

    .nbk-footer__section ul li a:hover::before {
        content: '→';
        position: absolute;
        left: -20px;
        color: hsl(var(--nbk-primary));
        font-weight: 600;
    }

    .nbk-footer__social-links {
        display: flex;
        gap: 1.5rem;
        margin-top: 1.5rem;
    }

    .nbk-footer__social-links a {
        color: #a0a0a0;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        padding: 0.5rem 1rem;
        border: 1px solid #333333;
        border-radius: 6px;
        transition: all 0.3s ease;
        background-color: transparent;
    }

    .nbk-footer__social-links a:hover {
        color: #ffffff;
        border-color: hsl(var(--nbk-primary));
        background-color: hsl(var(--nbk-primary) / 0.1);
        transform: translateY(-2px);
    }

    .nbk-footer__brand-description {
        color: #a0a0a0;
        margin-bottom: 1.5rem;
        line-height: 1.6;
        font-size: 0.95rem;
    }

    .nbk-footer__bottom {
        border-top: 1px solid #333333;
        padding-top: 2rem;
        text-align: center;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .nbk-footer__bottom p {
        color: #a0a0a0;
        font-size: 0.875rem;
        margin: 0;
    }

    .nbk-footer__bottom-links {
        display: flex;
        gap: 2rem;
        flex-wrap: wrap;
    }

    .nbk-footer__bottom-links a {
        color: #a0a0a0;
        text-decoration: none;
        font-size: 0.875rem;
        transition: color 0.2s ease;
    }

    .nbk-footer__bottom-links a:hover {
        color: #ffffff;
    }

    /* NBK Brand Logo Styling */
    .nbk-footer__brand-title {
        font-size: 1.5rem;
        font-weight: 800;
        margin-bottom: 1rem;
        color: #ffffff;
        letter-spacing: -0.025em;
    }

    /* NBK Responsive Footer */
    @media (max-width: 768px) {
        .nbk-footer {
            padding: 3rem 0 1.5rem;
        }

        .nbk-footer__grid {
            grid-template-columns: 1fr;
            gap: 2.5rem;
            margin-bottom: 2.5rem;
        }

        .nbk-footer__social-links {
            justify-content: flex-start;
            flex-wrap: wrap;
        }

        .nbk-footer__bottom {
            flex-direction: column;
            text-align: center;
            gap: 1.5rem;
        }

        .nbk-footer__bottom-links {
            justify-content: center;
        }
    }

    @media (max-width: 480px) {
        .nbk-footer__social-links {
            gap: 1rem;
        }

        .nbk-footer__social-links a {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
        }

        .nbk-footer__bottom-links {
            gap: 1.5rem;
        }
    }

    /* ==================================================
     * NBK ANIMATION OBSERVER STYLES
     * ================================================== */
    .nbk-animation-paused {
        animation-play-state: paused;
    }

    .nbk-animation-running {
        animation-play-state: running;
    }
</style>

<!-- NBK Footer -->
<footer class="nbk-footer">
    <div class="nbk-container">
        <div class="nbk-footer__grid">
            <div class="nbk-footer__section">
                <h3 class="nbk-footer__brand-title"><?php _e('Nord Booking', $nbk_text_domain); ?></h3>
                <p class="nbk-footer__brand-description">
                    <?php _e('The ultimate SaaS platform for cleaning service companies. Streamline your bookings, manage customers, and grow your business.', $nbk_text_domain); ?>
                </p>
                <div class="nbk-footer__social-links">
                    <a href="<?php echo esc_url('#'); ?>" class="nbk-link" aria-label="<?php esc_attr_e('Twitter', $nbk_text_domain); ?>">
                        <?php _e('Twitter', $nbk_text_domain); ?>
                    </a>
                    <a href="<?php echo esc_url('#'); ?>" class="nbk-link" aria-label="<?php esc_attr_e('LinkedIn', $nbk_text_domain); ?>">
                        <?php _e('LinkedIn', $nbk_text_domain); ?>
                    </a>
                    <a href="<?php echo esc_url('#'); ?>" class="nbk-link" aria-label="<?php esc_attr_e('Facebook', $nbk_text_domain); ?>">
                        <?php _e('Facebook', $nbk_text_domain); ?>
                    </a>
                </div>
            </div>

            <div class="nbk-footer__section">
                <h3><?php _e('Product', $nbk_text_domain); ?></h3>
                <ul>
                    <li><a href="<?php echo esc_url(home_url('/#features')); ?>" class="nbk-link"><?php _e('Features', $nbk_text_domain); ?></a></li>
                    <li><a href="<?php echo esc_url(home_url('/#pricing')); ?>" class="nbk-link"><?php _e('Pricing', $nbk_text_domain); ?></a></li>
                    <li><a href="<?php echo esc_url(home_url('/api/')); ?>" class="nbk-link"><?php _e('API', $nbk_text_domain); ?></a></li>
                    <li><a href="<?php echo esc_url(home_url('/integrations/')); ?>" class="nbk-link"><?php _e('Integrations', $nbk_text_domain); ?></a></li>
                    <li><a href="<?php echo esc_url(home_url('/changelog/')); ?>" class="nbk-link"><?php _e('Changelog', $nbk_text_domain); ?></a></li>
                </ul>
            </div>

            <div class="nbk-footer__section">
                <h3><?php _e('Company', $nbk_text_domain); ?></h3>
                <ul>
                    <li><a href="<?php echo esc_url(home_url('/about/')); ?>" class="nbk-link"><?php _e('About', $nbk_text_domain); ?></a></li>
                    <li><a href="<?php echo esc_url(home_url('/blog/')); ?>" class="nbk-link"><?php _e('Blog', $nbk_text_domain); ?></a></li>
                    <li><a href="<?php echo esc_url(home_url('/careers/')); ?>" class="nbk-link"><?php _e('Careers', $nbk_text_domain); ?></a></li>
                    <li><a href="<?php echo esc_url(home_url('/press/')); ?>" class="nbk-link"><?php _e('Press', $nbk_text_domain); ?></a></li>
                    <li><a href="<?php echo esc_url(home_url('/partners/')); ?>" class="nbk-link"><?php _e('Partners', $nbk_text_domain); ?></a></li>
                </ul>
            </div>

            <div class="nbk-footer__section">
                <h3><?php _e('Support', $nbk_text_domain); ?></h3>
                <ul>
                    <li><a href="<?php echo esc_url(home_url('/help/')); ?>" class="nbk-link"><?php _e('Help Center', $nbk_text_domain); ?></a></li>
                    <li><a href="<?php echo esc_url(home_url('/contact/')); ?>" class="nbk-link"><?php _e('Contact Us', $nbk_text_domain); ?></a></li>
                    <li><a href="<?php echo esc_url(home_url('/status/')); ?>" class="nbk-link"><?php _e('Status', $nbk_text_domain); ?></a></li>
                    <li><a href="<?php echo esc_url(home_url('/privacy/')); ?>" class="nbk-link"><?php _e('Privacy Policy', $nbk_text_domain); ?></a></li>
                    <li><a href="<?php echo esc_url(home_url('/terms/')); ?>" class="nbk-link"><?php _e('Terms of Service', $nbk_text_domain); ?></a></li>
                </ul>
            </div>
        </div>

        <div class="nbk-footer__bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php _e('Nord Booking. All rights reserved.', $nbk_text_domain); ?></p>
            <div class="nbk-footer__bottom-links">
                <a href="<?php echo esc_url(home_url('/privacy/')); ?>" class="nbk-link"><?php _e('Privacy', $nbk_text_domain); ?></a>
                <a href="<?php echo esc_url(home_url('/terms/')); ?>" class="nbk-link"><?php _e('Terms', $nbk_text_domain); ?></a>
                <a href="<?php echo esc_url(home_url('/cookies/')); ?>" class="nbk-link"><?php _e('Cookies', $nbk_text_domain); ?></a>
            </div>
        </div>
    </div>
</footer>

<script>
    // NBK Animation Observer for smooth animations
    document.addEventListener('DOMContentLoaded', function() {
        const nbkObserverOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const nbkAnimationObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.remove('nbk-animation-paused');
                    entry.target.classList.add('nbk-animation-running');
                    entry.target.style.animationPlayState = 'running';
                }
            });
        }, nbkObserverOptions);

        // Observe all NBK animated elements
        document.querySelectorAll('.nbk-fade-in, .nbk-slide-up').forEach(el => {
            el.classList.add('nbk-animation-paused');
            el.style.animationPlayState = 'paused';
            nbkAnimationObserver.observe(el);
        });

        // NBK Mobile menu toggle (if mobile menu exists)
        const mobileMenuToggle = document.querySelector('.nbk-mobile-menu-toggle');
        if (mobileMenuToggle) {
            mobileMenuToggle.addEventListener('click', function() {
                const navLinks = document.querySelector('.nbk-nav-links');
                if (navLinks) {
                    const isVisible = navLinks.style.display === 'flex';
                    navLinks.style.display = isVisible ? 'none' : 'flex';
                    
                    // Update aria-expanded for accessibility
                    this.setAttribute('aria-expanded', !isVisible);
                }
            });
        }

        // NBK Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // NBK Footer link hover effects enhancement
        document.querySelectorAll('.nbk-footer__section ul li a').forEach(link => {
            link.addEventListener('mouseenter', function() {
                this.style.paddingLeft = '20px';
            });
            
            link.addEventListener('mouseleave', function() {
                this.style.paddingLeft = '0px';
            });
        });
    });
</script>

<?php wp_footer(); ?>
</body>
</html>