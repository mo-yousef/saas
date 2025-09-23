<?php
/**
 * The template for displaying the front page.
 *
 * @package Nord Booking
 */

get_header();

// Text domain for translations
$nbk_text_domain = 'nord-booking';
?>

<style>
    /* ==================================================
     * NBK RESET & FOUNDATION STYLES
     * ================================================== */
    header#masthead,
    footer#colophon {
        display: none;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    /* ==================================================
     * NBK CSS CUSTOM PROPERTIES (DESIGN TOKENS)
     * ================================================== */
    :root {
        --nbk-background: 0 0% 100%;
        --nbk-foreground: 222.2 84% 4.9%;
        --nbk-card: 0 0% 100%;
        --nbk-card-foreground: 222.2 84% 4.9%;
        --nbk-popover: 0 0% 100%;
        --nbk-popover-foreground: 222.2 84% 4.9%;
        --nbk-primary: 221.2 83.2% 53.3%;
        --nbk-primary-foreground: 210 40% 98%;
        --nbk-secondary: 210 40% 96%;
        --nbk-secondary-foreground: 222.2 84% 4.9%;
        --nbk-muted: 210 40% 96%;
        --nbk-muted-foreground: 215.4 16.3% 46.9%;
        --nbk-accent: 210 40% 96%;
        --nbk-accent-foreground: 222.2 84% 4.9%;
        --nbk-destructive: 0 62.8% 30.6%;
        --nbk-destructive-foreground: 210 40% 98%;
        --nbk-border: 214.3 31.8% 91.4%;
        --nbk-input: 214.3 31.8% 91.4%;
        --nbk-ring: 222.2 84% 4.9%;
        --nbk-radius: 0.5rem;
    }

    /* ==================================================
     * NBK BASE STYLES
     * ================================================== */
    body {
        font-family: "Outfit", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif !important;
        line-height: 1.6;
        color: hsl(var(--nbk-foreground));
        background-color: hsl(var(--nbk-background));
        font-feature-settings: "rlig" 1, "calt" 1;
    }

    .nbk-link {
        text-decoration: none !important;
    }

    .nbk-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 2rem;
    }

    /* ==================================================
     * NBK HEADER STYLES
     * ================================================== */
    .nbk-header {
        position: sticky;
        top: 0;
        z-index: 50;
        width: 100%;
        border-bottom: 1px solid hsl(var(--nbk-border));
        background-color: hsl(var(--nbk-background) / 0.95);
        backdrop-filter: blur(8px);
    }

    .nbk-nav {
        display: flex;
        height: 4rem;
        align-items: center;
        justify-content: space-between;
    }

    .nbk-logo {
        font-size: 1.5rem;
        font-weight: 700;
        color: hsl(var(--nbk-foreground));
    }

    .nbk-nav-links {
        display: flex;
        list-style: none;
        gap: 2rem;
        align-items: center;
    }

    .nbk-nav-links a {
        text-decoration: none;
        color: hsl(var(--nbk-muted-foreground));
        font-weight: 500;
        font-size: 0.875rem;
        transition: color 0.2s;
    }

    .nbk-nav-links a:hover {
        color: hsl(var(--nbk-foreground));
    }

    /* ==================================================
     * NBK BUTTON COMPONENT STYLES
     * ================================================== */
    .nbk-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: calc(var(--nbk-radius) - 2px);
        font-size: 0.875rem;
        font-weight: 500;
        transition: all 0.2s;
        border: 1px solid transparent;
        cursor: pointer;
        text-decoration: none;
        gap: 0.5rem;
        font-family: "Outfit", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif !important;
    }

    .nbk-btn svg {
        width: 18px;
        height: 18px;
    }

    .nbk-btn--primary {
        background-color: hsl(var(--nbk-primary));
        color: hsl(var(--nbk-primary-foreground));
    }

    .nbk-btn--primary:hover {
        background-color: hsl(var(--nbk-primary) / 0.9);
    }

    .nbk-btn--secondary {
        border: 1px solid hsl(var(--nbk-border));
        background-color: hsl(var(--nbk-background));
        color: hsl(var(--nbk-foreground));
    }

    .nbk-btn--secondary:hover {
        background-color: hsl(var(--nbk-accent));
    }

    .nbk-btn--outline {
        color: hsl(var(--nbk-foreground));
        border: 1px solid hsl(var(--nbk-border));
        background-color: hsl(var(--nbk-background));
    }

    .nbk-btn--outline:hover {
        background-color: hsl(var(--nbk-accent));
        color: hsl(var(--nbk-accent-foreground));
    }

    .nbk-btn--sm {
        height: 2.25rem;
        padding: 0 0.75rem;
    }

    .nbk-btn--lg {
        height: 2.75rem;
        padding: 0 2rem;
    }

    .nbk-btn--xl {
        height: 3rem;
        padding: 0 1.5rem;
        font-size: 1.1rem;
    }

    /* ==================================================
     * NBK HERO SECTION STYLES
     * ================================================== */
    .nbk-hero {
        margin-top: 150px;
        text-align: center;
        box-shadow: inset 0 -69px 33px 0 rgb(0 0 0 / 3%), 0 1px 2px -1px rgb(0 0 0 / 0.1);
        overflow: hidden;
        background-image: url(../wp-content/themes/saas/assets/images/grid-pattern.png);
        background-position: bottom center;
        background-repeat: no-repeat;
        background-size: 120% auto;
    }

    .nbk-hero__badge {
        display: inline-flex;
        align-items: center;
        border-radius: 9999px;
        border: 1px solid hsl(var(--nbk-border));
        background-color: hsl(var(--nbk-muted));
        padding: 0.25rem 0.75rem;
        font-size: 0.75rem;
        font-weight: 500;
        margin-bottom: 2rem;
        gap: 0.5rem;
        color: hsl(var(--nbk-muted-foreground));
    }
    .nbk-hero__badge svg {
        width: 18px;
    }
    .nbk-hero__title {
        font-size: clamp(2.25rem, 5vw, 4rem);
        font-weight: 800;
        line-height: 1.1;
        letter-spacing: -0.025em;
        margin-bottom: 1.5rem;
        color: hsl(var(--nbk-foreground));
        max-inline-size: 800px;
        margin-inline: auto;
    }

    .nbk-hero__description {
        font-size: 1.25rem;
        line-height: 1.6;
        color: hsl(var(--nbk-muted-foreground));
        max-width: 40rem;
        margin: 0 auto 2.5rem;
    }

    .nbk-hero__actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
        margin-top: 2rem;
        margin-bottom: 3rem;
    }

    .nbk-hero__stats {
        display: flex;
        justify-content: center;
        gap: 2rem;
        flex-wrap: wrap;
        margin-top: 3rem;
    }

    .nbk-hero__mockup {
        margin-bottom: -130px;
        max-width: 1000px;
        margin: auto;
        margin-bottom: -210px;
        margin-top: 3rem;
    }

    .nbk-hero__mockup img {
        max-width: 100%;
        box-shadow: 0 1px 33px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
        overflow: hidden;
        border-radius: 10px;
        outline: 1px solid #e4eaf1;
    }

    .nbk-stat {
        text-align: center;
    }

    .nbk-stat__number {
        font-size: 2rem;
        font-weight: 700;
        color: hsl(var(--nbk-foreground));
    }

    .nbk-stat__label {
        font-size: 0.875rem;
        color: hsl(var(--nbk-muted-foreground));
    }

    /* ==================================================
     * NBK CARD COMPONENT STYLES
     * ================================================== */
    .nbk-card {
        border-radius: var(--nbk-radius);
        border: 1px solid hsl(var(--nbk-border));
        background-color: hsl(var(--nbk-card));
        color: hsl(var(--nbk-card-foreground));
        box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
    }

    .nbk-card__header {
        display: flex;
        flex-direction: column;
        space-y: 1.5;
        padding: 1.5rem;
    }

    .nbk-card__title {
        font-size: 1.5rem;
        font-weight: 600;
        line-height: 1;
        letter-spacing: -0.025em;
    }

    .nbk-card__description {
        font-size: 0.875rem;
        color: hsl(var(--nbk-muted-foreground));
    }

    .nbk-card__content {
        padding: 1.5rem;
    }

    /* ==================================================
     * NBK SECTION STYLES
     * ================================================== */
    .nbk-section {
        padding: 6rem 0;
    }

    .nbk-section__header {
        text-align: center;
        margin-bottom: 4rem;
    }

    .nbk-section__badge {
        display: inline-flex;
        align-items: center;
        border-radius: 9999px;
        border: 1px solid hsl(var(--nbk-border));
        background-color: hsl(var(--nbk-muted));
        padding: 0.25rem 0.75rem;
        font-size: 0.75rem;
        font-weight: 500;
        margin-bottom: 1rem;
        gap: 5px;
        color: hsl(var(--nbk-muted-foreground));
    }

    .nbk-section__title {
        font-size: clamp(2rem, 4vw, 3rem);
        font-weight: 800;
        line-height: 1.1;
        letter-spacing: -0.025em;
        margin-bottom: 1rem;
        color: hsl(var(--nbk-foreground));
    }

    .nbk-section__description {
        font-size: 1.125rem;
        line-height: 1.6;
        color: hsl(var(--nbk-muted-foreground));
        max-width: 48rem;
        margin: 0 auto;
    }

    /* ==================================================
     * NBK FEATURES GRID STYLES
     * ================================================== */


            .nbk-section-header {
            text-align: center;
            margin-bottom: 3rem;
            max-width: 768px;
            margin-left: auto;
            margin-right: auto;
        }
.feature-img {
    position: relative;
    bottom: 0;
    right: 0;
    margin-left: 1.5rem;
}

.feature-img img {
    max-width: 100%;
    width: 100%;
}
        .nbk-section-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.375rem 0.75rem;
            background-color: var(--gray-100);
            color: var(--gray-700);
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1rem;
            border: 1px solid var(--gray-200);
        }

        .nbk-section-title {
            font-size: 2.25rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 1rem;
            letter-spacing: -0.025em;
            line-height: 1.2;
        }

        .nbk-section-subtitle {
            font-size: 1.125rem;
            color: var(--gray-600);
            line-height: 1.6;
        }

        .nbk-hero-feature {
            background-color: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 3rem;
            position: relative;
        }

        .nbk-hero-badge {
            position: absolute;
            top: -0.5rem;
            left: 1.5rem;
            background-color: var(--primary-color);
            color: var(--white);
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .nbk-hero-content {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 2rem;
            align-items: center;
        }

        .nbk-hero-text h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 0.75rem;
        }

        .nbk-hero-text p {
            color: var(--gray-600);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .nbk-hero-features {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        .nbk-hero-feature-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--gray-700);
        }

        .nbk-hero-feature-item::before {
            content: "✓";
            color: var(--success-color);
            font-weight: 600;
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(16, 185, 129, 0.1);
            border-radius: 50%;
            font-size: 0.75rem;
            flex-shrink: 0;
        }

        .nbk-hero-visual {
            background-color: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            text-align: center;
            color: var(--gray-600);
            font-size: 0.875rem;
        }

        .nbk-features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .nbk-feature-card {
            background-color: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: var(--border-radius);
            padding: 0rem;
            position: relative;
            min-height: 350px;
            transition: var(--transition-fast);
        }

        .nbk-feature-card:hover {
            border-color: var(--gray-300);
            box-shadow: var(--shadow-sm);
        }

        .nbk-feature-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .nbk-feature-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--gray-100);
            border: 1px solid var(--gray-200);
        }

        .nbk-feature-icon svg {
            width: 1.25rem;
            height: 1.25rem;
            stroke: var(--gray-600);
        }

        .nbk-feature-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--gray-900);
        }

        .nbk-feature-description {
            color: var(--gray-600);
            line-height: 1.5;
            margin-bottom: 0px;
            font-size: 0.875rem;
        }

        .nbk-feature-list {
            list-style: none;
        }

        .nbk-feature-list li {
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            color: var(--gray-700);
            line-height: 1.4;
        }

        .nbk-feature-list li:last-child {
            margin-bottom: 0;
        }

        .nbk-feature-status {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: var(--border-radius);
            font-size: 0.75rem;
            font-weight: 500;
            margin-top: 1rem;
        }

        .nbk-status-available {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .nbk-status-planned {
            background-color: var(--gray-100);
            color: var(--gray-600);
        }

        .nbk-status-development {
            background-color: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
        }

        @media (max-width: 768px) {
            .nbk-hero-content {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .nbk-hero-features {
                grid-template-columns: 1fr;
            }

            .nbk-features-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .nbk-section-title {
                font-size: 1.875rem;
            }

            .nbk-features-section {
                padding: 2rem 1rem;
            }

            .nbk-hero-feature {
                padding: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .nbk-section-title {
                font-size: 1.5rem;
            }
            
            .nbk-section-subtitle {
                font-size: 1rem;
            }

            .nbk-hero-feature {
                padding: 1rem;
            }

            .nbk-feature-card {
                padding: 1rem;
            }
        }

.feature-content {
    padding: 1.5rem;
}
    .nbk-feature-card {
        padding: 0rem;
        transition: all 0.2s;
    }

    .nbk-feature-card:hover {
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    }

    .nbk-feature-card__icon {
        width: 3rem;
        height: 3rem;
        background-color: hsl(var(--nbk-primary));
        border-radius: var(--nbk-radius);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
        font-size: 1.5rem;
        color: hsl(var(--nbk-primary-foreground));
    }

    .nbk-feature-card__title {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 0.75rem;
        color: hsl(var(--nbk-foreground));
    }

    .nbk-feature-card__description {
        color: hsl(var(--nbk-muted-foreground));
        line-height: 1.6;
    }

    /* ==================================================
     * NBK STEPS STYLES
     * ================================================== */
    .nbk-steps-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 3rem;
        margin-top: 4rem;
    }

    .nbk-step {
        text-align: center;
    }

    .nbk-step__number {
        width: 3rem;
        height: 3rem;
        background-color: hsl(var(--nbk-primary));
        color: hsl(var(--nbk-primary-foreground));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
        font-size: 1.25rem;
        font-weight: 600;
    }

    .nbk-step__title {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 0.75rem;
        color: hsl(var(--nbk-foreground));
    }

    .nbk-step__description {
        color: hsl(var(--nbk-muted-foreground));
        line-height: 1.6;
    }

    /* ==================================================
     * NBK PRICING STYLES
     * ================================================== */
    .nbk-pricing-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        margin-top: 4rem;
    }

    .nbk-pricing-card {
        position: relative;
        padding: 2rem;
        transition: all 0.2s;
    }

    .nbk-pricing-card:hover {
        box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
    }

    .nbk-pricing-card__badge {
        position: absolute;
        top: -0.5rem;
        left: 50%;
        transform: translateX(-50%);
        background-color: hsl(var(--nbk-primary));
        color: hsl(var(--nbk-primary-foreground));
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .nbk-plan__name {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: hsl(var(--nbk-foreground));
    }

    .nbk-plan__price {
        font-size: 3rem;
        font-weight: 800;
        color: hsl(var(--nbk-foreground));
        margin-bottom: 0.25rem;
    }

    .nbk-plan__period {
        color: hsl(var(--nbk-muted-foreground));
        margin-bottom: 0;
    }

    .nbk-plan__features {
        list-style: none;
        margin-bottom: 2rem;
        space-y: 0.75rem;
    }

    .nbk-plan__features li {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: hsl(var(--nbk-muted-foreground));
        margin-bottom: 0.75rem;
    }

    .nbk-plan__features li::before {
        content: '✓';
        color: hsl(var(--nbk-primary));
        font-weight: 600;
        width: 1rem;
        text-align: center;
    }

    /* ==================================================
     * NBK TESTIMONIALS STYLES
     * ================================================== */
    .nbk-testimonials-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 2rem;
        margin-top: 4rem;
    }

    .nbk-testimonial {
        padding: 2rem;
        border-left: 4px solid hsl(var(--nbk-border));
    }

    .nbk-testimonial__content {
        font-style: italic;
        margin-bottom: 1.5rem;
        color: hsl(var(--nbk-muted-foreground));
        line-height: 1.6;
    }

    .nbk-testimonial__author {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .nbk-author__avatar {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 50%;
        background-color: hsl(var(--nbk-muted));
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        color: hsl(var(--nbk-foreground));
    }

    .nbk-author__name {
        font-weight: 500;
        color: hsl(var(--nbk-foreground));
    }

    .nbk-author__title {
        font-size: 0.875rem;
        color: hsl(var(--nbk-muted-foreground));
    }

    /* ==================================================
     * NBK CTA SECTION STYLES
     * ================================================== */
    .nbk-cta-section {
        padding: 6rem 0;
        text-align: center;
        background-color: hsl(var(--nbk-muted) / 0.5);
    }

    /* ==================================================
     * NBK FOOTER STYLES
     * ================================================== */
    .nbk-footer {
        border-top: 1px solid hsl(var(--nbk-border));
        padding: 4rem 0 2rem;
        background-color: hsl(var(--nbk-muted) / 0.3);
    }

    .nbk-footer__grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
        margin-bottom: 3rem;
    }

    .nbk-footer__section h3 {
        font-weight: 600;
        margin-bottom: 1rem;
        color: hsl(var(--nbk-foreground));
    }

    .nbk-footer__section ul {
        list-style: none;
        space-y: 0.5rem;
    }

    .nbk-footer__section ul li {
        margin-bottom: 0.5rem;
    }

    .nbk-footer__section ul li a {
        color: hsl(var(--nbk-muted-foreground));
        text-decoration: none;
        font-size: 0.875rem;
        transition: color 0.2s;
    }

    .nbk-footer__section ul li a:hover {
        color: hsl(var(--nbk-foreground));
    }

    .nbk-footer__bottom {
        border-top: 1px solid hsl(var(--nbk-border));
        padding-top: 2rem;
        text-align: center;
        color: hsl(var(--nbk-muted-foreground));
        font-size: 0.875rem;
    }

    /* ==================================================
     * NBK MOBILE MENU STYLES
     * ================================================== */
    .nbk-mobile-menu-toggle {
        display: none;
        background: none;
        border: none;
        font-size: 1.25rem;
        cursor: pointer;
        color: hsl(var(--nbk-foreground));
    }

    /* ==================================================
     * NBK RESPONSIVE DESIGN
     * ================================================== */
    @media (max-width: 768px) {
        .nbk-nav-links {
            display: none;
        }

        .nbk-mobile-menu-toggle {
            display: block;
        }

        .nbk-hero__actions {
            flex-direction: column;
            align-items: center;
        }

        .nbk-hero__stats {
            flex-direction: column;
            gap: 1rem;
        }

        .nbk-features-grid {
            grid-template-columns: 1fr;
        }

        .nbk-steps-grid {
            grid-template-columns: 1fr;
        }

        .nbk-pricing-grid {
            grid-template-columns: 1fr;
        }

        .nbk-testimonials-grid {
            grid-template-columns: 1fr;
        }

        .nbk-footer__grid {
            grid-template-columns: 1fr;
        }
    }

    /* ==================================================
     * NBK ANIMATION UTILITIES
     * ================================================== */
    .nbk-fade-in {
        opacity: 0;
        animation: nbk-fadeIn 0.6s ease forwards;
    }

    @keyframes nbk-fadeIn {
        to {
            opacity: 1;
        }
    }

    .nbk-slide-up {
        opacity: 0;
        transform: translateY(20px);
        animation: nbk-slideUp 0.6s ease forwards;
    }

    @keyframes nbk-slideUp {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .nbk-delay-100 { animation-delay: 0.1s; }
    .nbk-delay-200 { animation-delay: 0.2s; }
    .nbk-delay-300 { animation-delay: 0.3s; }
    .nbk-delay-400 { animation-delay: 0.4s; }
    .nbk-delay-500 { animation-delay: 0.5s; }
    .nbk-delay-600 { animation-delay: 0.6s; }
</style>

<main>
    <!-- NBK Hero Section -->
    <section class="nbk-hero">
        <div class="nbk-container">
            <div class="nbk-hero__badge nbk-fade-in">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M10.6144 17.7956C10.277 18.5682 9.20776 18.5682 8.8704 17.7956L7.99275 15.7854C7.21171 13.9966 5.80589 12.5726 4.0523 11.7942L1.63658 10.7219C.868536 10.381.868537 9.26368 1.63658 8.92276L3.97685 7.88394C5.77553 7.08552 7.20657 5.60881 7.97427 3.75892L8.8633 1.61673C9.19319.821767 10.2916.821765 10.6215 1.61673L11.5105 3.75894C12.2782 5.60881 13.7092 7.08552 15.5079 7.88394L17.8482 8.92276C18.6162 9.26368 18.6162 10.381 17.8482 10.7219L15.4325 11.7942C13.6789 12.5726 12.2731 13.9966 11.492 15.7854L10.6144 17.7956ZM4.53956 9.82234C6.8254 10.837 8.68402 12.5048 9.74238 14.7996 10.8008 12.5048 12.6594 10.837 14.9452 9.82234 12.6321 8.79557 10.7676 7.04647 9.74239 4.71088 8.71719 7.04648 6.85267 8.79557 4.53956 9.82234ZM19.4014 22.6899 19.6482 22.1242C20.0882 21.1156 20.8807 20.3125 21.8695 19.8732L22.6299 19.5353C23.0412 19.3526 23.0412 18.7549 22.6299 18.5722L21.9121 18.2532C20.8978 17.8026 20.0911 16.9698 19.6586 15.9269L19.4052 15.3156C19.2285 14.8896 18.6395 14.8896 18.4628 15.3156L18.2094 15.9269C17.777 16.9698 16.9703 17.8026 15.956 18.2532L15.2381 18.5722C14.8269 18.7549 14.8269 19.3526 15.2381 19.5353L15.9985 19.8732C16.9874 20.3125 17.7798 21.1156 18.2198 22.1242L18.4667 22.6899C18.6473 23.104 19.2207 23.104 19.4014 22.6899ZM18.3745 19.0469 18.937 18.4883 19.4878 19.0469 18.937 19.5898 18.3745 19.0469Z"></path></svg>
                <?php _e('Trusted by cleaning companies globally', $nbk_text_domain); ?>
            </div>
            
            <h1 class="nbk-hero__title nbk-slide-up">
                <?php _e('Manage and Grow Your Cleaning Business', $nbk_text_domain); ?>
            </h1>
            
            <p class="nbk-hero__description nbk-slide-up nbk-delay-100">
                <?php _e('A complete solution for cleaning companies to handle bookings, customers, and growth all in one place.', $nbk_text_domain); ?>
            </p>

            <div class="nbk-hero__actions nbk-slide-up nbk-delay-200">
                <a href="<?php echo esc_url(home_url('/register/')); ?>" class="nbk-btn nbk-btn--primary nbk-btn--xl">
                    <?php _e('Start Free Trial', $nbk_text_domain); ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </a>
            </div>

            <div class="nbk-hero__mockup">
                <img class="nbk-fade-in" src="<?php echo get_template_directory_uri(); ?>/assets/images/hero-mockup.png" alt="<?php esc_attr_e('Hero Mockup', $nbk_text_domain); ?>">
            </div>
        </div>
    </section>







   <section id="nbk-features-section" class="nbk-section">
        <div class="nbk-container">

            <div class="nbk-section__header">
                <div class="nbk-section__badge nbk-slide-up nbk-delay-100">                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"></polygon>
                    </svg>
<?php _e('Powerful Features', $nbk_text_domain); ?></div>
                <h2 class="nbk-section__title nbk-slide-up nbk-delay-200"><?php _e('Complete Business Management Platform', $nbk_text_domain); ?></h2>
                <p class="nbk-section__description nbk-slide-up nbk-delay-300">
                    <?php _e('Everything you need to run your service business efficiently, from booking management 
                    to customer communications and team coordination.', $nbk_text_domain); ?>
                </p>
            </div>


            <!-- Hero Feature: Comprehensive Booking System -->
            <!-- <div class="nbk-hero-feature">
                <div class="nbk-hero-badge nbk-slide-up nbk-delay-100">Featured</div>
                <div class="nbk-hero-content">
                    <div class="nbk-hero-text nbk-slide-up nbk-delay-100">
                        <h3>Complete Booking Management System</h3>
                        <p>
                            A comprehensive solution for managing all aspects of your service business, 
                            from customer bookings to worker management and payment processing.
                        </p>
                        <div class="nbk-hero-features">
                            <div class="nbk-hero-feature-item">Customer management & profiles</div>
                            <div class="nbk-hero-feature-item">Custom service creation</div>
                            <div class="nbk-hero-feature-item">ZIP code & service areas</div>
                            <div class="nbk-hero-feature-item">Coupon code system</div>
                            <div class="nbk-hero-feature-item">Worker scheduling & assignments</div>
                            <div class="nbk-hero-feature-item">Smart email notifications</div>
                            <div class="nbk-hero-feature-item">Dynamic invoice generation</div>
                            <div class="nbk-hero-feature-item">Customer self-service portal</div>
                        </div>
                    </div>
                    <div class="nbk-hero-visual nbk-slide-up nbk-delay-100">
                        <div style="margin-bottom: 1rem;">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="3" y1="9" x2="21" y2="9"></line>
                                <path d="m9 21 3-3 3 3"></path>
                                <path d="m9 3 3 3 3-3"></path>
                            </svg>
                        </div>
                        <div>Booking Dashboard Preview</div>
                    </div>
                </div>
            </div> -->

            <div class="nbk-features-grid">
                <!-- Service Management -->
                <div class="nbk-feature-card nbk-slide-up nbk-delay-100">
                    <div class="feature-content">
                        <div class="nbk-feature-header">
                            <div class="nbk-feature-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                                </svg>
                            </div>
                            <h3 class="nbk-feature-title">Custom Service Creation</h3>
                        </div>
                        <p class="nbk-feature-description">
                            Create detailed services for cleaning, moving, and more with fully customizable options, 
                            pricing tiers, and service configurations.
                        </p>
                    </div>
                    <div class="feature-img">
                        <img class="nbk-fade-in" src="<?php echo get_template_directory_uri(); ?>/assets/images/features/service.png" alt="<?php esc_attr_e('Service', $nbk_text_domain); ?>">
                    </div>
                </div>
                

                <!-- Customer Management -->
                <div class="nbk-feature-card nbk-slide-up nbk-delay-100">
                    <div class="feature-content">
                        <div class="nbk-feature-header">
                            <div class="nbk-feature-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                            </div>
                            <h3 class="nbk-feature-title">Customer Management Hub</h3>
                        </div>
                        <p class="nbk-feature-description">
                            Comprehensive customer database with booking history, preferences, and 
                            self-service capabilities for rescheduling and cancellations.
                        </p>
                    </div>
                    <div class="feature-img">
                        <img class="nbk-fade-in" src="<?php echo get_template_directory_uri(); ?>/assets/images/features/service.png" alt="<?php esc_attr_e('Service', $nbk_text_domain); ?>">
                    </div>
                </div>

                <!-- Service Areas & Location -->
                <div class="nbk-feature-card nbk-slide-up nbk-delay-100">
                    <div class="nbk-feature-header">
                        <div class="nbk-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                        </div>
                        <h3 class="nbk-feature-title">Smart Service Areas</h3>
                    </div>
                    <p class="nbk-feature-description">
                        Define precise service coverage by selecting countries, cities, and specific ZIP codes 
                        with real-time availability checking.
                    </p>
                </div>

                <!-- Coupon System -->
                <div class="nbk-feature-card nbk-slide-up nbk-delay-100">
                    <div class="nbk-feature-header">
                        <div class="nbk-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"></path>
                                <path d="M3 5v14a2 2 0 0 0 2 2h16v-5"></path>
                                <path d="M18 12a2 2 0 0 0 0 4h4v-4Z"></path>
                            </svg>
                        </div>
                        <h3 class="nbk-feature-title">Intelligent Coupon System</h3>
                    </div>
                    <p class="nbk-feature-description">
                        Create and manage discount codes with advanced rules, usage limits, 
                        and detailed tracking for marketing campaigns.
                    </p>
                </div>

                <!-- Worker Management -->
                <div class="nbk-feature-card nbk-slide-up nbk-delay-100">
                    <div class="nbk-feature-header">
                        <div class="nbk-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                <circle cx="22" cy="11" r="1"></circle>
                                <path d="m22 13-1.5-1.5L22 10"></path>
                            </svg>
                        </div>
                        <h3 class="nbk-feature-title">Team & Worker Management</h3>
                    </div>
                    <p class="nbk-feature-description">
                        Add team members, assign bookings, manage schedules, and track performance 
                        with role-based access controls.
                    </p>
                </div>

                <!-- Email Notifications -->
                <div class="nbk-feature-card nbk-slide-up nbk-delay-100">
                    <div class="nbk-feature-header">
                        <div class="nbk-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                        </div>
                        <h3 class="nbk-feature-title">Smart Email Notifications</h3>
                    </div>
                    <p class="nbk-feature-description">
                        Automated email system with customizable templates, triggers, and 
                        personalized messaging for customers and staff.
                    </p>
                </div>

                <!-- Invoicing System -->
                <div class="nbk-feature-card nbk-slide-up nbk-delay-100">
                    <div class="nbk-feature-header">
                        <div class="nbk-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14,2 14,8 20,8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10,9 9,9 8,9"></polyline>
                            </svg>
                        </div>
                        <h3 class="nbk-feature-title">Dynamic Invoice Generation</h3>
                    </div>
                    <p class="nbk-feature-description">
                        Automatically generate professional invoices for each booking with 
                        customizable templates and integrated payment processing.
                    </p>
                </div>

                <!-- Availability Management -->
                <div class="nbk-feature-card nbk-slide-up nbk-delay-100">
                    <div class="nbk-feature-header">
                        <div class="nbk-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12,6 12,12 16,14"></polyline>
                            </svg>
                        </div>
                        <h3 class="nbk-feature-title">Flexible Availability System</h3>
                    </div>
                    <p class="nbk-feature-description">
                        Set custom availability schedules, time slots, and booking windows 
                        with support for multiple time zones and seasonal adjustments.
                    </p>
                </div>

                <!-- Public Booking Forms -->
                <div class="nbk-feature-card nbk-slide-up nbk-delay-100">
                    <div class="nbk-feature-header">
                        <div class="nbk-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="2" y1="12" x2="22" y2="12"></line>
                                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                            </svg>
                        </div>
                        <h3 class="nbk-feature-title">Public Booking Forms</h3>
                    </div>
                    <p class="nbk-feature-description">
                        Get your own custom booking form that customers can access publicly 
                        or embed directly on your website for seamless integration.
                    </p>
                </div>
            </div>
        </div>
    </section>




    <!-- NBK How It Works Section -->
    <section id="how-it-works" class="nbk-section">
        <div class="nbk-container">
            <div class="nbk-section__header">
                <div class="nbk-section__badge"><?php _e('How It Works', $nbk_text_domain); ?></div>
                <h2 class="nbk-section__title"><?php _e('Get started in minutes', $nbk_text_domain); ?></h2>
                <p class="nbk-section__description">
                    <?php _e('Simple setup process that gets your cleaning business online and accepting bookings quickly.', $nbk_text_domain); ?>
                </p>
            </div>

            <div class="nbk-steps-grid">
                <div class="nbk-step nbk-slide-up">
                    <div class="nbk-step__number">1</div>
                    <h3 class="nbk-step__title"><?php _e('Sign Up & Setup', $nbk_text_domain); ?></h3>
                    <p class="nbk-step__description">
                        <?php _e('Create your account, add your business details, and configure your services in just a few clicks. No technical knowledge required.', $nbk_text_domain); ?>
                    </p>
                </div>

                <div class="nbk-step nbk-slide-up nbk-delay-100">
                    <div class="nbk-step__number">2</div>
                    <h3 class="nbk-step__title"><?php _e('Customize & Launch', $nbk_text_domain); ?></h3>
                    <p class="nbk-step__description">
                        <?php _e('Personalize your booking form, set your service areas, pricing, and embed it on your website or share the direct link.', $nbk_text_domain); ?>
                    </p>
                </div>

                <div class="nbk-step nbk-slide-up nbk-delay-200">
                    <div class="nbk-step__number">3</div>
                    <h3 class="nbk-step__title"><?php _e('Manage & Grow', $nbk_text_domain); ?></h3>
                    <p class="nbk-step__description">
                        <?php _e('Start receiving bookings instantly, manage your schedule through the dashboard, and watch your business grow.', $nbk_text_domain); ?>
                    </p>
                </div>
            </div>
        </div>
    </section>


    <!-- NBK Features Section -->
    <section id="features" class="nbk-section">
        <div class="nbk-container">
            <div class="nbk-section__header">
                <div class="nbk-section__badge"><?php _e('Features', $nbk_text_domain); ?></div>
                <h2 class="nbk-section__title"><?php _e('Everything you need to succeed', $nbk_text_domain); ?></h2>
                <p class="nbk-section__description">
                    <?php _e('Powerful features designed specifically for cleaning businesses to streamline operations and boost growth.', $nbk_text_domain); ?>
                </p>
            </div>

            <div class="nbk-features-grid">
            <div class="nbk-pricing-grid">
                <div class="nbk-card nbk-pricing-card nbk-slide-up">
                    <div class="nbk-card__header">
                        <h3 class="nbk-plan__name"><?php _e('Starter', $nbk_text_domain); ?></h3>
                        <div class="nbk-plan__price">$29</div>
                        <div class="nbk-plan__period"><?php _e('per month', $nbk_text_domain); ?></div>
                    </div>
                    <div class="nbk-card__content">
                        <ul class="nbk-plan__features">
                            <li><?php _e('Up to 100 bookings/month', $nbk_text_domain); ?></li>
                            <li><?php _e('Basic dashboard & reporting', $nbk_text_domain); ?></li>
                            <li><?php _e('Email support', $nbk_text_domain); ?></li>
                            <li><?php _e('Mobile-responsive booking forms', $nbk_text_domain); ?></li>
                            <li><?php _e('Customer management', $nbk_text_domain); ?></li>
                            <li><?php _e('Basic integrations', $nbk_text_domain); ?></li>
                        </ul>
                        <a href="<?php echo esc_url(home_url('/register/')); ?>" class="nbk-btn nbk-btn--outline nbk-btn--lg" style="width: 100%;">
                            <?php _e('Choose Starter', $nbk_text_domain); ?>
                        </a>
                    </div>
                </div>

                <div class="nbk-card nbk-pricing-card nbk-slide-up nbk-delay-100">
                    <div class="nbk-pricing-card__badge"><?php _e('Most Popular', $nbk_text_domain); ?></div>
                    <div class="nbk-card__header">
                        <h3 class="nbk-plan__name"><?php _e('Professional', $nbk_text_domain); ?></h3>
                        <div class="nbk-plan__price">$79</div>
                        <div class="nbk-plan__period"><?php _e('per month', $nbk_text_domain); ?></div>
                    </div>
                    <div class="nbk-card__content">
                        <ul class="nbk-plan__features">
                            <li><?php _e('Unlimited bookings', $nbk_text_domain); ?></li>
                            <li><?php _e('Advanced dashboard & analytics', $nbk_text_domain); ?></li>
                            <li><?php _e('Priority support', $nbk_text_domain); ?></li>
                            <li><?php _e('Custom branding', $nbk_text_domain); ?></li>
                            <li><?php _e('Area management', $nbk_text_domain); ?></li>
                            <li><?php _e('Discount codes & promotions', $nbk_text_domain); ?></li>
                            <li><?php _e('WooCommerce & Stripe integration', $nbk_text_domain); ?></li>
                        </ul>
                        <a href="<?php echo esc_url(home_url('/register/')); ?>" class="nbk-btn nbk-btn--primary nbk-btn--lg" style="width: 100%;">
                            <?php _e('Choose Professional', $nbk_text_domain); ?>
                        </a>
                    </div>
                </div>

                <div class="nbk-card nbk-pricing-card nbk-slide-up nbk-delay-200">
                    <div class="nbk-card__header">
                        <h3 class="nbk-plan__name"><?php _e('Enterprise', $nbk_text_domain); ?></h3>
                        <div class="nbk-plan__price">$199</div>
                        <div class="nbk-plan__period"><?php _e('per month', $nbk_text_domain); ?></div>
                    </div>
                    <div class="nbk-card__content">
                        <ul class="nbk-plan__features">
                            <li><?php _e('Everything in Professional', $nbk_text_domain); ?></li>
                            <li><?php _e('Multi-tenant system', $nbk_text_domain); ?></li>
                            <li><?php _e('White-label solution', $nbk_text_domain); ?></li>
                            <li><?php _e('API access', $nbk_text_domain); ?></li>
                            <li><?php _e('Dedicated account manager', $nbk_text_domain); ?></li>
                            <li><?php _e('Custom integrations', $nbk_text_domain); ?></li>
                            <li><?php _e('Advanced security features', $nbk_text_domain); ?></li>
                        </ul>
                        <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="nbk-btn nbk-btn--outline nbk-btn--lg" style="width: 100%;">
                            <?php _e('Contact Sales', $nbk_text_domain); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- NBK Testimonials Section -->
    <section id="testimonials" class="nbk-section">
        <div class="nbk-container">
            <div class="nbk-section__header">
                <div class="nbk-section__badge"><?php _e('Testimonials', $nbk_text_domain); ?></div>
                <h2 class="nbk-section__title"><?php _e('What our customers say', $nbk_text_domain); ?></h2>
                <p class="nbk-section__description">
                    <?php _e('Join thousands of cleaning businesses that have transformed their operations with Nord Booking.', $nbk_text_domain); ?>
                </p>
            </div>

            <div class="nbk-testimonials-grid">
                <div class="nbk-card nbk-testimonial nbk-slide-up nbk-delay-100">
                    <div class="nbk-card__content">
                        <p class="nbk-testimonial__content">
                            <?php _e('"The multi-tenant feature is perfect for our franchise operations. We can manage all locations from one place while giving each franchise owner their own dashboard. Game changer for our business!"', $nbk_text_domain); ?>
                        </p>
                        <div class="nbk-testimonial__author">
                            <div class="nbk-author__avatar">MJ</div>
                            <div>
                                <div class="nbk-author__name"><?php _e('Michael Johnson', $nbk_text_domain); ?></div>
                                <div class="nbk-author__title"><?php _e('CEO, CleanPro Franchises', $nbk_text_domain); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="nbk-card nbk-testimonial nbk-slide-up nbk-delay-200">
                    <div class="nbk-card__content">
                        <p class="nbk-testimonial__content">
                            <?php _e('"Outstanding customer support and the platform is so easy to use. We\'ve seen a 150% increase in revenue since switching to Nord Booking. Our customers love how simple it is to book our services online."', $nbk_text_domain); ?>
                        </p>
                        <div class="nbk-testimonial__author">
                            <div class="nbk-author__avatar">LR</div>
                            <div>
                                <div class="nbk-author__name"><?php _e('Lisa Rodriguez', $nbk_text_domain); ?></div>
                                <div class="nbk-author__title"><?php _e('Manager, Elite Cleaning Co.', $nbk_text_domain); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- NBK Integration Section -->
    <section class="nbk-section">
        <div class="nbk-container">
            <div class="nbk-section__header">
                <div class="nbk-section__badge"><?php _e('Integrations', $nbk_text_domain); ?></div>
                <h2 class="nbk-section__title"><?php _e('Seamlessly connects with your tools', $nbk_text_domain); ?></h2>
                <p class="nbk-section__description">
                    <?php _e('Nord Booking integrates with the platforms you already use to streamline your workflow.', $nbk_text_domain); ?>
                </p>
            </div>

            <div class="nbk-features-grid">
                <div class="nbk-card nbk-feature-card nbk-slide-up">
                    <div class="nbk-feature-card__icon">💳</div>
                    <h3 class="nbk-feature-card__title"><?php _e('WooCommerce Integration', $nbk_text_domain); ?></h3>
                    <p class="nbk-feature-card__description">
                        <?php _e('Seamlessly integrate with your existing WooCommerce store to manage bookings alongside your products.', $nbk_text_domain); ?>
                    </p>
                </div>

                <div class="nbk-card nbk-feature-card nbk-slide-up nbk-delay-100">
                    <div class="nbk-feature-card__icon">💰</div>
                    <h3 class="nbk-feature-card__title"><?php _e('Stripe Payments', $nbk_text_domain); ?></h3>
                    <p class="nbk-feature-card__description">
                        <?php _e('Accept secure payments online with Stripe integration. Support for credit cards, digital wallets, and more.', $nbk_text_domain); ?>
                    </p>
                </div>

                <div class="nbk-card nbk-feature-card nbk-slide-up nbk-delay-200">
                    <div class="nbk-feature-card__icon">📧</div>
                    <h3 class="nbk-feature-card__title"><?php _e('Email Automation', $nbk_text_domain); ?></h3>
                    <p class="nbk-feature-card__description">
                        <?php _e('Automated booking confirmations, reminders, and follow-up emails to keep customers engaged.', $nbk_text_domain); ?>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- NBK FAQ Section -->
    <section class="nbk-section">
        <div class="nbk-container">
            <div class="nbk-section__header">
                <div class="nbk-section__badge"><?php _e('FAQ', $nbk_text_domain); ?></div>
                <h2 class="nbk-section__title"><?php _e('Frequently asked questions', $nbk_text_domain); ?></h2>
                <p class="nbk-section__description">
                    <?php _e('Everything you need to know about Nord Booking and how it can help your cleaning business.', $nbk_text_domain); ?>
                </p>
            </div>

            <div style="max-width: 48rem; margin: 0 auto;">
                <div class="nbk-card nbk-slide-up" style="margin-bottom: 1rem;">
                    <div class="nbk-card__content">
                        <h3 style="font-weight: 600; margin-bottom: 0.5rem;"><?php _e('How quickly can I get started?', $nbk_text_domain); ?></h3>
                        <p style="color: hsl(var(--nbk-muted-foreground)); margin: 0;"><?php _e('You can set up your account and start accepting bookings within 10 minutes. Our onboarding process is designed to be simple and straightforward.', $nbk_text_domain); ?></p>
                    </div>
                </div>

                <div class="nbk-card nbk-slide-up nbk-delay-100" style="margin-bottom: 1rem;">
                    <div class="nbk-card__content">
                        <h3 style="font-weight: 600; margin-bottom: 0.5rem;"><?php _e('Do I need technical skills to use Nord Booking?', $nbk_text_domain); ?></h3>
                        <p style="color: hsl(var(--nbk-muted-foreground)); margin: 0;"><?php _e('Not at all! Nord Booking is designed for business owners, not developers. Everything is point-and-click with no coding required.', $nbk_text_domain); ?></p>
                    </div>
                </div>

                <div class="nbk-card nbk-slide-up nbk-delay-200" style="margin-bottom: 1rem;">
                    <div class="nbk-card__content">
                        <h3 style="font-weight: 600; margin-bottom: 0.5rem;"><?php _e('Can I customize the booking form to match my brand?', $nbk_text_domain); ?></h3>
                        <p style="color: hsl(var(--nbk-muted-foreground)); margin: 0;"><?php _e('Yes! Professional and Enterprise plans include custom branding options to match your business colors, logo, and style.', $nbk_text_domain); ?></p>
                    </div>
                </div>

                <div class="nbk-card nbk-slide-up nbk-delay-300" style="margin-bottom: 1rem;">
                    <div class="nbk-card__content">
                        <h3 style="font-weight: 600; margin-bottom: 0.5rem;"><?php _e('What payment methods are supported?', $nbk_text_domain); ?></h3>
                        <p style="color: hsl(var(--nbk-muted-foreground)); margin: 0;"><?php _e('Through our Stripe integration, we support all major credit cards, digital wallets like Apple Pay and Google Pay, and bank transfers.', $nbk_text_domain); ?></p>
                    </div>
                </div>

                <div class="nbk-card nbk-slide-up nbk-delay-400">
                    <div class="nbk-card__content">
                        <h3 style="font-weight: 600; margin-bottom: 0.5rem;"><?php _e('Is there a free trial?', $nbk_text_domain); ?></h3>
                        <p style="color: hsl(var(--nbk-muted-foreground)); margin: 0;"><?php _e('Yes! We offer a 14-day free trial with no credit card required. You can explore all features and see how Nord Booking works for your business.', $nbk_text_domain); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- NBK Final CTA Section -->
    <section class="nbk-cta-section">
        <div class="nbk-container">
            <div class="nbk-hero__badge nbk-fade-in">
                🚀 <?php _e('Join 500+ successful cleaning businesses', $nbk_text_domain); ?>
            </div>
            
            <h2 class="nbk-section__title nbk-slide-up">
                <?php _e('Ready to transform your cleaning business?', $nbk_text_domain); ?>
            </h2>
            
            <p class="nbk-section__description nbk-slide-up nbk-delay-100">
                <?php _e('Start your free trial today and see how Nord Booking can help you streamline operations and grow your revenue.', $nbk_text_domain); ?>
            </p>

            <div class="nbk-hero__actions nbk-slide-up nbk-delay-200">
                <a href="<?php echo esc_url(home_url('/register/')); ?>" class="nbk-btn nbk-btn--primary nbk-btn--xl">
                    <?php _e('Start Free Trial', $nbk_text_domain); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="nbk-btn nbk-btn--outline nbk-btn--xl">
                    <?php _e('Schedule Demo', $nbk_text_domain); ?>
                </a>
            </div>

            <p style="margin-top: 1rem; font-size: 0.875rem; color: hsl(var(--nbk-muted-foreground));">
                <?php _e('No credit card required • 14-day free trial • Cancel anytime', $nbk_text_domain); ?>
            </p>
        </div>
    </section>
</main>

<?php get_footer(); ?>
   