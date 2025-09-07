<?php
/**
 * The template for displaying the front page.
 *
 * @package Nord Booking
 */

get_header();
?>

<div class="landing-page">
    <header class="header">
        <div class="container">
            <div class="logo">NordBK</div>
            <nav class="nav">
                <a href="#features">Features</a>
                <a href="#pricing">Pricing</a>
                <a href="#contact">Contact</a>
            </nav>
            <div class="auth-buttons">
                <a href="<?php echo esc_url(home_url('/login/')); ?>" class="btn btn-login">Login</a>
                <a href="<?php echo esc_url(home_url('/register/')); ?>" class="btn btn-signup">Sign Up</a>
            </div>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="container">
                <h1>Powerful, Simple, and Affordable Business Solutions</h1>
                <p>Empower your business with our cutting-edge SaaS platform. Streamline your operations, boost productivity, and drive growth.</p>
                <a href="<?php echo esc_url(home_url('/register/')); ?>" class="btn btn-primary">Get Started for Free</a>
            </div>
        </section>

        <section id="features" class="features">
            <div class="container">
                <h2>Features Designed for Your Success</h2>
                <div class="feature-grid">
                    <div class="feature-item">
                        <h3>Intuitive Dashboard</h3>
                        <p>Manage everything from a single, easy-to-use dashboard.</p>
                    </div>
                    <div class="feature-item">
                        <h3>Advanced Analytics</h3>
                        <p>Gain valuable insights with our powerful analytics tools.</p>
                    </div>
                    <div class="feature-item">
                        <h3>Seamless Integrations</h3>
                        <p>Connect with your favorite tools and services effortlessly.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="pricing" class="pricing">
            <div class="container">
                <h2>Choose Your Plan</h2>
                <div class="pricing-grid">
                    <div class="pricing-plan">
                        <h3>Basic</h3>
                        <p class="price">$29<span>/mo</span></p>
                        <ul>
                            <li>Feature 1</li>
                            <li>Feature 2</li>
                            <li>Feature 3</li>
                        </ul>
                        <a href="#" class="btn btn-secondary">Select Plan</a>
                    </div>
                    <div class="pricing-plan popular">
                        <h3>Pro</h3>
                        <p class="price">$79<span>/mo</span></p>
                        <ul>
                            <li>All Basic Features</li>
                            <li>Feature 4</li>
                            <li>Feature 5</li>
                        </ul>
                        <a href="#" class="btn btn-primary">Select Plan</a>
                    </div>
                    <div class="pricing-plan">
                        <h3>Enterprise</h3>
                        <p class="price">Contact Us</p>
                        <ul>
                            <li>All Pro Features</li>
                            <li>Dedicated Support</li>
                            <li>Custom Integrations</li>
                        </ul>
                        <a href="#" class="btn btn-secondary">Contact Sales</a>
                    </div>
                </div>
            </div>
        </section>

        <section id="contact" class="contact">
            <div class="container">
                <h2>Get in Touch</h2>
                <p>Have questions? We'd love to hear from you.</p>
                <a href="mailto:support@nordbk.com" class="btn btn-primary">Contact Us</a>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-links">
                <a href="<?php echo esc_url(home_url('/privacy-policy/')); ?>">Privacy Policy</a>
                <a href="<?php echo esc_url(home_url('/terms-of-use/')); ?>">Terms of Use</a>
                <a href="<?php echo esc_url(home_url('/cookies-policy/')); ?>">Cookies Policy</a>
                <a href="<?php echo esc_url(home_url('/refund-policy/')); ?>">Refund Policy</a>
            </div>
            <p>&copy; <?php echo date('Y'); ?> NordBK. All Rights Reserved.</p>
        </div>
    </footer>
</div>

<?php
get_footer();
?>
