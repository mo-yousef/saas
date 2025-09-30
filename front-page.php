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

<main>
    <!-- ============================================
         HERO SECTION
    ============================================ -->
    <section class="nbk-hero">
        <div class="nbk-container">
            <div class="nbk-hero__badge nbk-fade-in">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M10.6144 17.7956C10.277 18.5682 9.20776 18.5682 8.8704 17.7956L7.99275 15.7854C7.21171 13.9966 5.80589 12.5726 4.0523 11.7942L1.63658 10.7219C.868536 10.381.868537 9.26368 1.63658 8.92276L3.97685 7.88394C5.77553 7.08552 7.20657 5.60881 7.97427 3.75892L8.8633 1.61673C9.19319.821767 10.2916.821765 10.6215 1.61673L11.5105 3.75894C12.2782 5.60881 13.7092 7.08552 15.5079 7.88394L17.8482 8.92276C18.6162 9.26368 18.6162 10.381 17.8482 10.7219L15.4325 11.7942C13.6789 12.5726 12.2731 13.9966 11.492 15.7854L10.6144 17.7956ZM4.53956 9.82234C6.8254 10.837 8.68402 12.5048 9.74238 14.7996 10.8008 12.5048 12.6594 10.837 14.9452 9.82234 12.6321 8.79557 10.7676 7.04647 9.74239 4.71088 8.71719 7.04648 6.85267 8.79557 4.53956 9.82234ZM19.4014 22.6899 19.6482 22.1242C20.0882 21.1156 20.8807 20.3125 21.8695 19.8732L22.6299 19.5353C23.0412 19.3526 23.0412 18.7549 22.6299 18.5722L21.9121 18.2532C20.8978 17.8026 20.0911 16.9698 19.6586 15.9269L19.4052 15.3156C19.2285 14.8896 18.6395 14.8896 18.4628 15.3156L18.2094 15.9269C17.777 16.9698 16.9703 17.8026 15.956 18.2532L15.2381 18.5722C14.8269 18.7549 14.8269 19.3526 15.2381 19.5353L15.9985 19.8732C16.9874 20.3125 17.7798 21.1156 18.2198 22.1242L18.4667 22.6899C18.6473 23.104 19.2207 23.104 19.4014 22.6899ZM18.3745 19.0469 18.937 18.4883 19.4878 19.0469 18.937 19.5898 18.3745 19.0469Z"></path>
                </svg>
                <?php _e('Trusted by cleaning companies globally', $nbk_text_domain); ?>
            </div>
            
            <h1 class="nbk-hero__title nbk-slide-up">
                <?php _e('Manage and Grow Your Cleaning Business', $nbk_text_domain); ?>
            </h1>
            
            <p class="nbk-hero__description nbk-slide-up nbk-delay-100">
                <?php _e('A complete solution for cleaning companies to handle bookings, customers, and growth all in one place.', $nbk_text_domain); ?>
            </p>

            <div class="nbk-hero__actions nbk-slide-up nbk-delay-200">
                <a href="<?php echo esc_url(home_url('/register/')); ?>" class="nbk-btn nbk-btn--outline nbk-btn--xl">
                    <?php _e('Start Free Trial', $nbk_text_domain); ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </a>
            </div>

            <div class="nbk-hero__mockup">
                <img class="nbk-fade-in" src="<?php echo get_template_directory_uri(); ?>/assets/images/hero-mockup.png" alt="<?php esc_attr_e('Hero Mockup', $nbk_text_domain); ?>">
            </div>
        </div>


        <figure class="figure1">
            <picture><source srcset="<?php echo get_template_directory_uri(); ?>/assets/images/hero/hero-man-cleaning.jpg, <?php echo get_template_directory_uri(); ?>/assets/images/hero/hero-man-cleaning.jpg" type="image/webp"><img src="<?php echo get_template_directory_uri(); ?>/assets/images/hero/hero-man-cleaning.jpg" width="227" height="315" alt="Massage"></picture>
            <span class="freeform"></span>
        </figure>
        
        <figure class="figure3">
            <span class="freeform"></span>
            <picture><source srcset="<?php echo get_template_directory_uri(); ?>/assets/images/hero/hero-woman-cleaning.jpg, <?php echo get_template_directory_uri(); ?>/assets/images/hero/hero-woman-cleaning.jpg" type="image/webp"><img src="<?php echo get_template_directory_uri(); ?>/assets/images/hero/hero-woman-cleaning.jpg" width="227" height="315" alt="Pottery class"></picture>
        </figure>
    </section>

    <div class="masthead-bottom-rounded"><div></div></div>

    <!-- ============================================
         FEATURES SECTION
    ============================================ -->
    <section id="nbk-features-section" class="nbk-section">
        <div class="nbk-container">
            <div class="nbk-section__header">
                <div class="nbk-section__badge nbk-slide-up nbk-delay-100">                    
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"></polygon>
                    </svg>
                    <?php _e('Powerful Features', $nbk_text_domain); ?>
                </div>
                <h2 class="nbk-section__title nbk-slide-up nbk-delay-200">
                    <?php _e('Complete Business Management Platform', $nbk_text_domain); ?>
                </h2>
                <p class="nbk-section__description nbk-slide-up nbk-delay-300">
                    <?php _e('Everything you need to run your service business efficiently, from booking management to customer communications and team coordination.', $nbk_text_domain); ?>
                </p>
            </div>

            <div class="nbk-features-grid">
                <!-- Custom Service Creation -->
                <div class="nbk-feature-card nbk-slide-up nbk-delay-100">
                    <div class="feature-content">
                        <div class="nbk-feature-header">
                            <div class="nbk-feature-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                                </svg>
                            </div>
                            <h3 class="nbk-feature-title"><?php _e('Custom Service Creation', $nbk_text_domain); ?></h3>
                        </div>
                        <p class="nbk-feature-description">
                            <?php _e('Create detailed services for cleaning, moving, and more with fully customizable options, pricing tiers, and service configurations.', $nbk_text_domain); ?>
                        </p>
                    </div>
                    <div class="feature-img">
                        <img class="nbk-fade-in" src="<?php echo get_template_directory_uri(); ?>/assets/images/features/service.png" alt="<?php esc_attr_e('Service', $nbk_text_domain); ?>">
                    </div>
                </div>

                <!-- Customer Management Hub -->
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
                            <h3 class="nbk-feature-title"><?php _e('Customer Management Hub', $nbk_text_domain); ?></h3>
                        </div>
                        <p class="nbk-feature-description">
                            <?php _e('Comprehensive customer database with booking history, preferences, and self-service capabilities for rescheduling and cancellations.', $nbk_text_domain); ?>
                        </p>
                    </div>
                    <div class="feature-img">
                        <img class="nbk-fade-in" src="<?php echo get_template_directory_uri(); ?>/assets/images/features/service.png" alt="<?php esc_attr_e('Service', $nbk_text_domain); ?>">
                    </div>
                </div>

                <!-- Smart Service Areas -->
                <div class="nbk-feature-card nbk-slide-up nbk-delay-100">
                    <div class="feature-content">
                        <div class="nbk-feature-header">
                            <div class="nbk-feature-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                            </div>
                            <h3 class="nbk-feature-title"><?php _e('Smart Service Areas', $nbk_text_domain); ?></h3>
                        </div>
                        <p class="nbk-feature-description">
                            <?php _e('Define precise service coverage by selecting countries, cities, and specific ZIP codes with real-time availability checking.', $nbk_text_domain); ?>
                        </p>
                    </div>
                    <div class="feature-img">
                        <img class="nbk-fade-in" src="<?php echo get_template_directory_uri(); ?>/assets/images/features/location.png" alt="<?php esc_attr_e('Location', $nbk_text_domain); ?>">
                    </div>
                </div>

                <!-- Intelligent Coupon System -->
                <div class="nbk-feature-card nbk-slide-up nbk-delay-100">
                    <div class="feature-content">
                        <div class="nbk-feature-header">
                            <div class="nbk-feature-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"></path>
                                    <path d="M3 5v14a2 2 0 0 0 2 2h16v-5"></path>
                                    <path d="M18 12a2 2 0 0 0 0 4h4v-4Z"></path>
                                </svg>
                            </div>
                            <h3 class="nbk-feature-title"><?php _e('Intelligent Coupon System', $nbk_text_domain); ?></h3>
                        </div>
                        <p class="nbk-feature-description">
                            <?php _e('Create and manage discount codes with advanced rules, usage limits, and detailed tracking for marketing campaigns.', $nbk_text_domain); ?>
                        </p>
                    </div>
                    <div class="feature-img">
                        <img class="nbk-fade-in" src="<?php echo get_template_directory_uri(); ?>/assets/images/features/coupon.png" alt="<?php esc_attr_e('Coupon', $nbk_text_domain); ?>">
                    </div>
                </div>

                <!-- Team & Worker Management -->
                <div class="nbk-feature-card nbk-slide-up nbk-delay-100">
                    <div class="feature-content">
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
                            <h3 class="nbk-feature-title"><?php _e('Team & Worker Management', $nbk_text_domain); ?></h3>
                        </div>
                        <p class="nbk-feature-description">
                            <?php _e('Add team members, assign bookings, manage schedules, and track performance with role-based access controls.', $nbk_text_domain); ?>
                        </p>
                    </div>
                    <div class="feature-img">
                        <img class="nbk-fade-in" src="<?php echo get_template_directory_uri(); ?>/assets/images/features/worker.png" alt="<?php esc_attr_e('Worker', $nbk_text_domain); ?>">
                    </div>
                </div>

                <!-- Smart Email Notifications -->
                <div class="nbk-feature-card nbk-slide-up nbk-delay-100">
                    <div class="feature-content">
                        <div class="nbk-feature-header">
                            <div class="nbk-feature-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                    <polyline points="22,6 12,13 2,6"></polyline>
                                </svg>
                            </div>
                            <h3 class="nbk-feature-title"><?php _e('Smart Email Notifications', $nbk_text_domain); ?></h3>
                        </div>
                        <p class="nbk-feature-description">
                            <?php _e('Automated email system with customizable templates, triggers, and personalized messaging for customers and staff.', $nbk_text_domain); ?>
                        </p>
                    </div>
                    <div class="feature-img">
                        <img class="nbk-fade-in" src="<?php echo get_template_directory_uri(); ?>/assets/images/features/email.png" alt="<?php esc_attr_e('Email', $nbk_text_domain); ?>">
                    </div>
                </div>

                <!-- Dynamic Invoice Generation -->
                <div class="nbk-feature-card nbk-slide-up nbk-delay-100">
                    <div class="feature-content">
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
                            <h3 class="nbk-feature-title"><?php _e('Dynamic Invoice Generation', $nbk_text_domain); ?></h3>
                        </div>
                        <p class="nbk-feature-description">
                            <?php _e('Automatically generate professional invoices for each booking with customizable templates and integrated payment processing.', $nbk_text_domain); ?>
                        </p>
                    </div>
                    <div class="feature-img">
                        <img class="nbk-fade-in" src="<?php echo get_template_directory_uri(); ?>/assets/images/features/invoice.png" alt="<?php esc_attr_e('Invoice', $nbk_text_domain); ?>">
                    </div>
                </div>

                <!-- Flexible Availability System -->
                <div class="nbk-feature-card nbk-slide-up nbk-delay-100">
                    <div class="feature-content">
                        <div class="nbk-feature-header">
                            <div class="nbk-feature-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12,6 12,12 16,14"></polyline>
                                </svg>
                            </div>
                            <h3 class="nbk-feature-title"><?php _e('Flexible Availability System', $nbk_text_domain); ?></h3>
                        </div>
                        <p class="nbk-feature-description">
                            <?php _e('Set custom availability schedules, time slots, and booking windows with support for multiple time zones and seasonal adjustments.', $nbk_text_domain); ?>
                        </p>
                    </div>
                    <div class="feature-img">
                        <img class="nbk-fade-in" src="<?php echo get_template_directory_uri(); ?>/assets/images/features/availability.png" alt="<?php esc_attr_e('Availability', $nbk_text_domain); ?>">
                    </div>
                </div>

                <!-- Public Booking Forms -->
                <div class="nbk-feature-card nbk-slide-up nbk-delay-100">
                    <div class="feature-content">
                        <div class="nbk-feature-header">
                            <div class="nbk-feature-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="2" y1="12" x2="22" y2="12"></line>
                                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                                </svg>
                            </div>
                            <h3 class="nbk-feature-title"><?php _e('Public Booking Forms', $nbk_text_domain); ?></h3>
                        </div>
                        <p class="nbk-feature-description">
                            <?php _e('Get your own custom booking form that customers can access publicly or embed directly on your website for seamless integration.', $nbk_text_domain); ?>
                        </p>
                    </div>
                    <div class="feature-img">
                        <img class="nbk-fade-in" src="<?php echo get_template_directory_uri(); ?>/assets/images/features/booking-form.png" alt="<?php esc_attr_e('Booking Form', $nbk_text_domain); ?>">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================
         KEY FEATURES SECTION
    ============================================ -->
    <section class="nbk-key-features">
        <div class="nbk-container">
            <div class="nbk-key-features__header">
                <div class="nbk-section__badge"><?php _e('Why Choose Us', $nbk_text_domain); ?></div>
                <h2 class="nbk-key-features__title"><?php _e('Built for Modern Service Businesses', $nbk_text_domain); ?></h2>
                <p class="nbk-key-features__subtitle"><?php _e('Nord Booking is packed with powerful features designed specifically for cleaning and service companies.', $nbk_text_domain); ?></p>
            </div>

            <div class="nbk-key-features__grid">
                <!-- Multi-Service Support -->
                <div class="nbk-key-features__card">
                    <div class="nbk-key-features__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                        </svg>
                    </div>
                    <h3 class="nbk-key-features__card-title"><?php _e('Multi-Service Support', $nbk_text_domain); ?></h3>
                    <p class="nbk-key-features__card-description"><?php _e('Create unlimited services with custom options, pricing tiers, and configurations to match your business offerings perfectly.', $nbk_text_domain); ?></p>
                </div>

                <!-- Advanced Booking Management -->
                <div class="nbk-key-features__card">
                    <div class="nbk-key-features__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <h3 class="nbk-key-features__card-title"><?php _e('Advanced Booking Management', $nbk_text_domain); ?></h3>
                    <p class="nbk-key-features__card-description"><?php _e('Manage all bookings from a central dashboard with status tracking, worker assignment, and customer communication tools.', $nbk_text_domain); ?></p>
                </div>

                <!-- Customer Self-Service -->
                <div class="nbk-key-features__card">
                    <div class="nbk-key-features__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <h3 class="nbk-key-features__card-title"><?php _e('Customer Self-Service', $nbk_text_domain); ?></h3>
                    <p class="nbk-key-features__card-description"><?php _e('Empower customers to manage their own bookings, reschedule appointments, and cancel services without staff intervention.', $nbk_text_domain); ?></p>
                </div>

                <!-- Promotional Tools -->
                <div class="nbk-key-features__card">
                    <div class="nbk-key-features__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="8" r="7"></circle>
                            <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline>
                        </svg>
                    </div>
                    <h3 class="nbk-key-features__card-title"><?php _e('Promotional Tools', $nbk_text_domain); ?></h3>
                    <p class="nbk-key-features__card-description"><?php _e('Create coupon codes with percentage or fixed discounts, usage limits, and expiration dates to drive marketing campaigns.', $nbk_text_domain); ?></p>
                </div>

                <!-- Team Coordination -->
                <div class="nbk-key-features__card">
                    <div class="nbk-key-features__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <h3 class="nbk-key-features__card-title"><?php _e('Team Coordination', $nbk_text_domain); ?></h3>
                    <p class="nbk-key-features__card-description"><?php _e('Add unlimited workers, assign them to bookings, and manage their schedules with role-based permissions and access control.', $nbk_text_domain); ?></p>
                </div>

                <!-- Automated Communications -->
                <div class="nbk-key-features__card">
                    <div class="nbk-key-features__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                    </div>
                    <h3 class="nbk-key-features__card-title"><?php _e('Automated Communications', $nbk_text_domain); ?></h3>
                    <p class="nbk-key-features__card-description"><?php _e('Send booking confirmations, reminders, and follow-ups automatically with customizable email templates and triggers.', $nbk_text_domain); ?></p>
                </div>

                <!-- Professional Invoicing -->
                <div class="nbk-key-features__card">
                    <div class="nbk-key-features__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14,2 14,8 20,8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                        </svg>
                    </div>
                    <h3 class="nbk-key-features__card-title"><?php _e('Professional Invoicing', $nbk_text_domain); ?></h3>
                    <p class="nbk-key-features__card-description"><?php _e('Generate branded invoices automatically for each booking with detailed breakdowns, payment tracking, and PDF exports.', $nbk_text_domain); ?></p>
                </div>

                <!-- Flexible Scheduling -->
                <div class="nbk-key-features__card">
                    <div class="nbk-key-features__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12,6 12,12 16,14"></polyline>
                        </svg>
                    </div>
                    <h3 class="nbk-key-features__card-title"><?php _e('Flexible Scheduling', $nbk_text_domain); ?></h3>
                    <p class="nbk-key-features__card-description"><?php _e('Configure custom availability windows, time slots, buffer times, and booking deadlines to match your operational needs.', $nbk_text_domain); ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================
         TESTIMONIALS SECTION
    ============================================ -->
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

    <!-- ============================================
         FINAL CTA SECTION
    ============================================ -->
    <section class="nbk-cta-section">
        <div class="nbk-container">
            <div class="nbk-section__badge nbk-slide-up">
                🚀 <?php _e('Join 100+ successful cleaning businesses', $nbk_text_domain); ?>
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
                <?php _e('No credit card required • 7-days free trial • Cancel anytime', $nbk_text_domain); ?>
            </p>
        </div>
    </section>
</main>

<?php get_footer(); ?>