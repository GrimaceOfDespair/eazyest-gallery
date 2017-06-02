<?php
/**
 * The template for displaying galleryfolder Archive pages.
 *
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 *
 * @package Eazyest Gallery
 * @subpackage Weaver II
 * @since Eazyest Gallery 0.1.0 (r206)
 */

weaverii_get_header('archive');
if ( weaverii_getopt('wii_infobar_location') == 'top' ) get_template_part('infobar');
weaverii_inject_area('premain');
echo("\t<div id=\"main\">\n");
weaverii_trace_template(__FILE__);
weaverii_get_sidebar_left('archive');
?>
		<div id="container_wrap"<?php weaverii_get_page_class('archive', 'container-archive'); ?>>
<?php		if (weaverii_getopt('wii_infobar_location') == 'content') get_template_part('infobar');
		weaverii_inject_area('precontent'); ?>
		<section id="container">
<?php		weaverii_get_sidebar_top('archive'); ?>
		    <div id="content" role="main">

<?php 		    if ( have_posts() ) { ?>

			<header class="page-header">
			    <h1 class="page-title archive-title"><span class="archive-title-label">
						<?php ezg_gallery_title() ?>
					</span>
			    </h1>
			</header>
			<?php ezg_gallery_style() ?>
			<div id="<?php ezg_selector( false ) ?>" class="<?php ezg_gallery_class( 'archive' ); ?> entry-content">
				<?php $ezg_i = 0; ?>
				<?php eazyest_gallery_content_nav( 'nav-above' );
				/* Start the Loop */
				weaverii_post_count_clear();
				while ( have_posts() ) {
				    the_post();
				    weaverii_post_count_bump();

				    /* Include the Post-Format-specific template for the content.
				     * If you want to overload this in a child theme then include a file
				     * called content-___.php (where ___ is the Post Format name) and that will be used instead.
				     */
				    ezg_get_template_part( 'content', 'galleryfolder' );
						ezg_folders_break( ++$ezg_i );
				}
			eazyest_gallery_content_nav( 'nav-below' );

		    } else {
			weaver_not_found_search(__FILE__);
		    } ?>

		</div><!-- #content -->
<?php		weaverii_get_sidebar_bottom('archive'); ?>
		</section><!-- #container -->
	    </div><!-- #container_wrap -->

<?php 	weaverii_get_sidebar_right('archive');
	weaverii_get_footer('archive');
?>
