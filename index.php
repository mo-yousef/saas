<?php
/**
 * The main template file
 *
 * @package NORDBOOKING
 */

get_header();

if ( have_posts() ) :
    while ( have_posts() ) :
        the_post();
        the_title('<h2>', '</h2>');
        the_content();
    endwhile;
else :
    echo '<p>No content found.</p>';
endif;

get_footer();
