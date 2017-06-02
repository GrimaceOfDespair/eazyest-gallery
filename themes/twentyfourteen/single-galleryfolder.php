<?php
/**
 * The Template for displaying all single posts.
 *
 * @package Eazyest Gallery
 * @subpackage Twenty_Fourteen
 * @since 0.1.3
 */

get_header(); ?>

<div id="primary" class="content-area">
	<div id="content" class="site-content" role="main">
		<style>
		h3.subfolders {
			top: 10px;
			position: relative;
		}
		</style>
			<?php while ( have_posts() ) : the_post(); ?>

				<?php ezg_get_template_part( 'content-single', 'galleryfolder' ); ?>



				<?php comments_template( '', true ); ?>

			<?php endwhile; // end of the loop. ?>

		</div><!-- #content -->
	</div><!-- #primary -->
<?php get_sidebar( 'content' ); ?>
<?php get_sidebar(); ?>
<?php get_footer(); ?>
