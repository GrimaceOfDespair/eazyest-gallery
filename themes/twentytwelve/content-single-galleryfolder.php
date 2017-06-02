<?php
/**
 * The default template for displaying single galleryfolder content.
 *
 * @package Eazyest Gallery
 * @subpackage Twenty_Twelve
 * @since 0.1.0 (r2)
 */
?>

	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		<header class="entry-header">
			<h1 class="entry-title"><?php the_title(); ?></h1>
			<?php if ( comments_open() ) : ?>
				<div class="comments-link">
					<?php comments_popup_link( '<span class="leave-reply">' . __( 'Leave a reply', 'eazyest-gallery' ) . '</span>', __( '1 Reply', 'eazyest-gallery' ), __( '% Replies', 'eazyest-gallery' ) ); ?>
				</div><!-- .comments-link -->
			<?php endif; // comments_open() ?>
		</header><!-- .entry-header -->

		<div class="entry-content eazyest-gallery">
			<?php do_action( 'eazyest_gallery_before_folder_content', $post->ID ); // this action uis used for breadcrumb trail and for thumbnail images ?>
			<?php the_content( __( 'Continue reading <span class="meta-nav">&rarr;</span>', 'eazyest-gallery' ) ); // this is the text content like a regular WordPress post ?>
			<?php do_action( 'eazyest_gallery_after_folder_content', $post->ID ); // this action is used for extra fields and for subfolders ?>
			<?php wp_link_pages( array( 'before' => '<div class="page-links">' . __( 'Pages:', 'eazyest-gallery' ), 'after' => '</div>' ) ); ?>
		</div><!-- .entry-content -->

		<footer class="entry-meta">
			<?php twentytwelve_entry_meta(); ?>
			<?php edit_post_link( __( 'Edit', 'eazyest-gallery' ), '<span class="edit-link">', '</span>' ); ?>
			<?php if ( is_singular() && get_the_author_meta( 'description' ) && is_multi_author() ) : // If a user has filled out their description and this is a multi-author blog, show a bio on their entries. ?>
				<div class="author-info">
					<div class="author-avatar">
						<?php echo get_avatar( get_the_author_meta( 'user_email' ), apply_filters( 'twentytwelve_author_bio_avatar_size', 68 ) ); ?>
					</div><!-- .author-avatar -->
					<div class="author-description">
						<h2><?php printf( __( 'About %s', 'eazyest-gallery' ), get_the_author() ); ?></h2>
						<p><?php the_author_meta( 'description' ); ?></p>
						<div class="author-link">
							<a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>" rel="author">
								<?php printf( __( 'View all entries by %s <span class="meta-nav">&rarr;</span>', 'eazyest-gallery' ), get_the_author() ); ?>
							</a>
						</div><!-- .author-link	-->
					</div><!-- .author-description -->
				</div><!-- .author-info -->
			<?php endif; ?>
		</footer><!-- .entry-meta -->
	</article><!-- #post -->
