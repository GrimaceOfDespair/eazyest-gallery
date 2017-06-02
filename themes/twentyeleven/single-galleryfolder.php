<?php
/**
 * The template for displaying single galleryfolders.
 *
 * @package Eazyest Gallery
 * @subpackage Twenty_Eleven
 * @since 0.1.0 (r2)
 */

get_header(); ?>

		<div id="primary">
			<div id="content" role="main">

				<?php while ( have_posts() ) : the_post(); ?>

					<nav id="nav-single">
						<h3 class="assistive-text"><?php _e( 'Folder navigation', 'eazyest-gallery' ); ?></h3>
						<span class="nav-previous"><?php previous_post_link( '%link', __( '<span class="meta-nav">&larr;</span> Previous', 'eazyest-gallery' ) ); ?></span>
						<span class="nav-next"><?php next_post_link( '%link', __( 'Next <span class="meta-nav">&rarr;</span>', 'eazyest-gallery' ) ); ?></span>
					</nav><!-- #nav-single -->

					<?php ezg_get_template_part( 'content-single', 'galleryfolder' ); ?>

					<?php comments_template( '', true ); ?>

				<?php endwhile; // end of the loop. ?>

			</div><!-- #content -->
		</div><!-- #primary -->

<?php get_footer(); ?>
