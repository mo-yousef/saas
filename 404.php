<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @package NORDBOOKING
 */

get_header();
?>

<main id="main" class="site-main">
    <center>
    <section class="error-404 not-found">
        <header class="page-header">
            <h1 class="page-title"><?php esc_html_e( 'Oops! That page can&rsquo;t be found.', 'NORDBOOKING' ); ?></h1>
        </header><!-- .page-header -->

        <div class="page-content">
            <p><?php esc_html_e( 'It looks like nothing was found at this location. Maybe try one of the links below or a search?', 'NORDBOOKING' ); ?></p>
            <?php get_search_form(); ?>
        </div><!-- .page-content -->
    </section><!-- .error-404 -->
    </center>
</main><!-- #main -->

<?php
get_footer();
