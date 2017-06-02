<?php
/**
 * The loop that displays a galleryfolder.
 *
 * The loop displays the posts and the post content. See
 * http://codex.wordpress.org/The_Loop to understand it and
 * http://codex.wordpress.org/Template_Tags to understand
 * the tags used in it.
 *
 * This can be overridden in child themes with loop-page.php.
 *
 * @package Eazyest Gallery
 * @subpackage Twenty_Ten
 * @since 0.1.0 (r2)
 */
?>

<?php /* Display navigation to next/previous pages when applicable */ ?>
<?php if ( $wp_query->max_num_pages > 1 ) : ?>
	<div id="nav-above" class="navigation">
		<div class="nav-previous"><?php next_posts_link( __( '<span class="meta-nav">&larr;</span> Next folders page',    'eazyest-gallery' ) ); ?></div>
		<div class="nav-next"><?php previous_posts_link( __( 'Prevous folders page <span class="meta-nav">&rarr;</span>', 'eazyest-gallery' ) ); ?></div>
	</div><!-- #nav-above -->
<?php endif; ?>

<?php /* If there are no posts to display, such as an empty archive page */ ?>
<?php if ( ! have_posts() ) : ?>
	<div id="post-0" class="post error404 not-found">
		<h1 class="entry-title"><?php _e( 'Not Found', 'eazyest-gallery' ); ?></h1>
		<div class="entry-content">
			<p><?php _e( 'Apologies, but no results were found for the requested archive. Perhaps searching will help find a related post.', 'eazyest-gallery' ); ?></p>
			<?php get_search_form(); ?>
		</div><!-- .entry-content -->
	</div><!-- #post-0 -->
<?php endif; ?>

<?php ezg_gallery_style() ?>
<div id="<?php ezg_selector( false ) ?>" class="<?php ezg_gallery_class( 'archive' ); ?>">
	<?php $ezg_i = 0; ?>
	<?php /* Start the Loop. */ ?>
	<?php while ( have_posts() ) : the_post(); ?>

		<<?php ezg_itemtag(); ?> class="gallery-item folder-item">

			<?php do_action( 'eazyest_gallery_before_folder_icon', $post->ID ); ?>

			<<?php ezg_icontag(); ?> class="gallery-icon folder-icon">
				<a href="<?php the_permalink(); ?>" title="<?php echo esc_attr( sprintf( __( 'View folder &#8220;%s&#8221;', 'eazyest-gallery' ), the_title_attribute( 'echo=0' ) ) ); ?>" rel="bookmark">
					<?php ezg_folder_thumbnail(); ?>
				</a>
			</<?php ezg_icontag(); ?>>

			<?php do_action( 'eazyest_gallery_after_folder_icon', $post->ID ); ?>

		</<?php ezg_itemtag(); ?>>
	<?php ezg_folders_break( ++$ezg_i ); ?>
	<?php endwhile; // End the loop ?>
	<br style="clear: both;"/>
	<?php do_action( 'eazyest_gallery_end_of_gallery' ); ?>
</div>

<?php /* Display navigation to next/previous pages when applicable */ ?>
<?php if (  $wp_query->max_num_pages > 1 ) : ?>
				<div id="nav-below" class="navigation">
					<div class="nav-previous"><?php next_posts_link( __( '<span class="meta-nav">&larr;</span> Next folders page', 'eazyest-gallery' ) ); ?></div>
					<div class="nav-next"><?php previous_posts_link( __( 'Previous folders <span class="meta-nav">&rarr;</span>',  'eazyest-gallery' ) ); ?></div>
				</div><!-- #nav-below -->
<?php endif; ?>
