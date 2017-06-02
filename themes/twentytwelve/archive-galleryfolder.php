<?php
/**
 * The template for displaying galleryfolder Archive pages.
 *
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 *
 * @package Eazyest Gallery
 * @subpackage Twenty_Twelve
 * @since Eazyest Gallery 0.1.0 (r2)
 */

get_header(); ?>

	<section id="primary" class="site-content">
		<div id="content" role="main">

		<?php if ( have_posts() ) : ?>
			<header class="archive-header">
				<h1 class="archive-title"><?php ezg_gallery_title() ?></h1>
			</header><!-- .archive-header -->

			<?php ezg_gallery_style() ?>
			<div id="<?php ezg_selector( false ) ?>" class="<?php ezg_gallery_class( 'archive' ); ?> entry-content">
				<?php $ezg_i = 0; ?>
				<?php
				/* Start the Loop */
				while ( have_posts() ) : the_post();

					/* Include the post format-specific template for the content. If you want to overload
					 * this in a child theme then include a file called called content-galleryfolder.php
					 */
					ezg_get_template_part( 'content', 'galleryfolder' );

				ezg_folders_break( ++$ezg_i );
				endwhile; ?>
				<br style="clear: both;"/>
				<?php	do_action( 'eazyest_gallery_end_of_gallery' ); ?>
			</div><!-- #eazyest-gallery-0 -->
			<?php eazyestgallery_content_nav( 'nav-below' ); ?>

		<?php else : ?>
			<?php get_template_part( 'content', 'none' ); ?>
		<?php endif; ?>

		</div><!-- #content -->
	</section><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
