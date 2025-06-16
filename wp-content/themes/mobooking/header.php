<?php
/**
 * The header for our theme
 *
 * @package MoBooking
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<div id="page" class="site">
    <a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Skip to content', 'mobooking' ); ?></a>

    <header id="masthead" class="site-header">
        <div class="site-branding">
            <p class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></p>
            <?php
            $mobooking_description = get_bloginfo( 'description', 'display' );
            if ( $mobooking_description || is_customize_preview() ) :
                ?>
                <p class="site-description"><?php echo $mobooking_description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
            <?php endif; ?>
        </div><!-- .site-branding -->

        <nav id="site-navigation" class="main-navigation">
            <?php
            // Placeholder for potential main navigation
            // wp_nav_menu( array( 'theme_location' => 'menu-1', 'menu_id' => 'primary-menu' ) );
            ?>
        </nav><!-- #site-navigation -->
    </header><!-- #masthead -->

    <div id="content" class="site-content">
