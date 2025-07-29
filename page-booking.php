<?php
/**
 * The template for displaying the booking page.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package MoBooking
 */

get_header();
?>

	<main id="primary" class="site-main">

		<?php
		while ( have_posts() ) :
			the_post();

			get_template_part( 'templates/parts/booking-form' );

		endwhile; // End of the loop.
		?>

	</main><!-- #main -->

<?php
get_footer();
