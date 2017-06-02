<?php
/**
 * The template for displaying posts in thegalleryfolder Post Format on index and archive pages
 *
 * Learn more: http://codex.wordpress.org/Post_Formats
 *
 * @package Eazyest Gallery
 * @subpackage Default theme
 * @since 0.1.0 (r2)
 */
 ?>

							<<?php ezg_itemtag(); ?> class="gallery-item folder-item">

								<?php do_action( 'eazyest_gallery_before_folder_icon', $post->ID ); ?>

								<<?php ezg_icontag(); ?> class="gallery-icon folder-icon">
									<a href="<?php the_permalink(); ?>" title="<?php echo esc_attr( sprintf( __( 'View folder &#8220;%s&#8221;', 'eazyest-gallery' ), the_title_attribute( 'echo=0' ) ) ); ?>" rel="bookmark">
										<?php ezg_folder_thumbnail( $post->ID ); ?>
									</a>
								</<?php ezg_icontag(); ?>>

								<?php do_action( 'eazyest_gallery_after_folder_icon', $post->ID ); ?>

							</<?php ezg_itemtag(); ?>>
