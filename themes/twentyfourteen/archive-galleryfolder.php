<?php


get_header(); ?>

<div id="main-content" class="main-content">
	<div id="primary" class="content-area">
	<div id="content" class="site-content" role="main">
		<article id="post-2" class="post-2 page type-page status-publish hentry">
		<?php if ( have_posts() ) : ?>

			<header class="entry-header">
				<h1 class="entry-title"><?php ezg_gallery_title() ?></h1>
		</header><!-- .page-header -->
		<?php ezg_gallery_style() ?>
		<div class="entry-content">
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
			</div>

		<?php else : ?>
		<?php get_template_part( 'content', 'none' ); ?>
		<?php endif; ?>
			</article>
		</div>
	</div><!-- #content -->
</div><!-- #primary -->

<?php
get_sidebar( 'content' );
get_sidebar();
get_footer();
