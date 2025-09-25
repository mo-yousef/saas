<?php
/**
 * The template for displaying all single pages
 *
 * @package NORDBOOKING
 */

get_header();
?>

<main id="main" class="site-main">
        <div class="nbk-container">

    <?php
    while ( have_posts() ) :
        the_post();

        get_template_part( 'templates/content', 'page' ); // We'll create this content part next or use a simpler loop

        // If comments are open or we have at least one comment, load up the comment template.
        if ( comments_open() || get_comments_number() ) :
            comments_template();
        endif;

    endwhile; // End of the loop. 
    ?>
    </div>
</main><!-- #main -->

<?php get_footer(); ?>



