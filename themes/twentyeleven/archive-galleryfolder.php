<?php
/**
 * The template for displaying Eazyest Gallery Folder Archives.
 *
 * Used to display galleryfolder pages
 *
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 *
 * @package Eazyest Gallery
 * @subpackage Twenty_Eleven
 * @since 0.1.0 (r2)
 */
get_header(); ?>

		<section id="primary">
			<div id="content" role="main">

			<?php if ( have_posts() ) : ?>

				<header class="page-header">
					<h1 class="page-title entry-title">
							<?php ezg_gallery_title() ?>
					</h1>
				</header>

				<?php eazyestgallery_content_nav( 'nav-above' ); ?>
				<?php ezg_gallery_style() ?>
				<div id="<?php ezg_selector( false ) ?>" class="<?php ezg_gallery_class( 'archive' ); ?>">
					<?php $ezg_i = 0; ?>
					<?php /* Start the Loop */ ?>
					<?php while ( have_posts() ) : the_post(); ?>

						<?php
							/* Include the Post-Format-specific template for the content.
							 * If you want to overload this in a child theme then include a file
							 * called loop-galleryfolder.php and that will be used instead.
							 */
							ezg_get_template_part( 'content', 'galleryfolder' );
						?>
					<?php ezg_folders_break( ++$ezg_i ); ?>
					<?php endwhile; ?>
					<br style="clear: both;"/>
					<?php do_action( 'eazyest_gallery_end_of_gallery' ); ?>
				</div>
				<?php eazyestgallery_content_nav( 'nav-below' ); ?>

			<?php else : ?>

				<article id="post-0" class="post no-results not-found">
					<header class="entry-header">
						<h1 class="entry-title"><?php _e( 'Nothing Found', 'eazyest-gallery' ); ?></h1>
					</header><!-- .entry-header -->

					<div class="entry-content">
						<p><?php _e( 'Apologies, but no results were found for the requested archive. Perhaps searching will help find a related post.', 'eazyest-gallery' ); ?></p>
						<?php get_search_form(); ?>
					</div><!-- .entry-content -->
				</article><!-- #post-0 -->

			<?php endif; ?>

			</div><!-- #content -->
		</section><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
