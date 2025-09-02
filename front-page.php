<?php
/**
 * The template for displaying the front page.
 *
 * @package Nord Booking
 */

get_header();
?>
<div class="wp-site-blocks">
    <header class="guten-Usgshc">
        <div class="guten-hhRaWT">
            <div class="guten-mJnMRV">
                <div class="guten-tTeyPR">
                    <a href="#">Support</a>
                    <a href="#">Legal</a>
                </div>
                <div class="guten-mCPnWH">
                    <h6>App Available on</h6>
                    <a href="#"><i class="fab fa-apple"></i></a>
                    <a href="#"><i class="fab fa-google-play"></i></a>
                </div>
            </div>
            <div class="guten-EjW9We">
                <div class="guten-IyketA">
                    <div class="guten-MZ9Klr">
                        <img src="https://fse.jegtheme.com/wallex/wp-content/uploads/sites/80/2025/03/wallex-logo-dark.webp" alt="Wallex Logo">
                        <span>Est.2016</span>
                    </div>
                    <div class="guten-c5Eymv">
                        <nav class="guten-nav-menu">
                            <ul>
                                <li><a href="#">Home</a></li>
                                <li><a href="#">About</a></li>
                                <li><a href="#">Services</a></li>
                                <li><a href="#">Pages</a></li>
                                <li><a href="#">Blog</a></li>
                            </ul>
                        </nav>
                    </div>
                    <div class="guten-oxoC28">
                        <a href="#" class="guten-button">Contact</a>
                        <a href="#" class="guten-button">14-Day Free Trial</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main>
        <section class="guten-wGKEqx">
            <div class="guten-vLcHN2">
                <div class="guten-9fN8fN">
                    <div class="guten-m20bIT">
                        <div class="guten-lWAR9g">
                            <div class="guten-3erqR3">
                                <div class="guten-qHxwUo">
                                    <h1><?php the_field('hero_title'); ?></h1>
                                    <p><?php the_field('hero_subtitle'); ?></p>
                                </div>
                                <a href="<?php the_field('hero_button_url'); ?>" class="guten-button"><?php the_field('hero_button_text'); ?></a>
                                <div class="guten-OBmD4P">
                                    <span><i class="gtn gtn-check-circle-solid"></i> No credit card required</span>
                                    <span><i class="gtn gtn-check-circle-solid"></i> Trusted 100+ Countries</span>
                                    <span><i class="gtn gtn-check-circle-solid"></i> 24/7 Online Support</span>
                                </div>
                            </div>
                        </div>
                        <div class="guten-CrsJKp">
                            <div class="guten-I3HafU">
                                <img src="<?php the_field('hero_image'); ?>" alt="Hero Image">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="guten-pVUmeF">
            <div class="container">
                <div class="guten-MOuZHv">
                    <h5 class="guten-FLdXbM"><?php the_field('companies_title'); ?></h5>
                    <div class="client-logos">
                        <?php
                        $images = get_field('company_logos');
                        if( $images ): ?>
                            <?php foreach( $images as $image ): ?>
                                <img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>" />
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="guten-qGa564">
            <div class="container">
                <div class="guten-ozGKJf">
                    <h2 class="guten-D607dL"><?php the_field('solutions_title'); ?></h2>
                    <div class="guten-Vfb9oQ">
                        <div class="guten-F7ZwUF">
                            <div class="guten-1b0GcT">
                                <h4>For Personal</h4>
                                <a href="#">Learn More <i class="fa-lg gtn gtn-arrow-up-right-line"></i></a>
                            </div>
                        </div>
                        <div class="guten-lAhBhi">
                            <div class="guten-GfKBSZ">
                                <h4>For Business</h4>
                                <a href="#">Learn More <i class="fa-lg gtn gtn-arrow-up-right-line"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="guten-uDZWuF">
            <div class="container">
                <h2 class="guten-m0v64b"><?php the_field('pricing_title'); ?></h2>
                <div class="pricing-grid">
                    <?php if( have_rows('pricing_plans') ): ?>
                        <?php while( have_rows('pricing_plans') ): the_row(); ?>
                            <div class="pricing-card">
                                <h5><?php the_sub_field('plan_name'); ?></h5>
                                <p class="price"><?php the_sub_field('price'); ?></p>
                                <a href="#" class="guten-button">Select Plan</a>
                                <?php if( have_rows('features') ): ?>
                                    <ul>
                                        <?php while( have_rows('features') ): the_row(); ?>
                                            <li><i class="gtn gtn-check-light"></i> <?php the_sub_field('feature'); ?></li>
                                        <?php endwhile; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <footer class="guten-JEqFLo">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Nord Booking. All Rights Reserved.</p>
        </div>
    </footer>
</div>
<?php
get_footer();
?>
