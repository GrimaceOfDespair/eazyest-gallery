<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Eazyest_Shortcodes
 * Shortcodes for Eazyest Gallery
 *
 * @package Eazyest Gallery
 * @subpackage Frontend/Shortcodes
 * @author Marcel Brinkkemper
 * @copyright 2013 Brimosoft
 * @since 0.1.0 (r2)
 * @version 0.1.0 (r278)
 * @access public
 */
class Eazyest_Shortcodes {

	/**
	 * @staticvar Eazyest_Shortcodes $instance single instance in memory
	 */
	private static $instance;

	/**
	 * Eazyest_Shortcodes::__construct()
	 *
	 * @return void
	 */
	function __construct() {}

	/**
	 * Eazyest_Shortcodes::init()
	 *
	 * @return void
	 */
	function init() {
		$this->shortcodes();
	}

	/**
	 * Eazyest_Shortcodes::instance()
	 *
	 * @return object Eazyest_Shortcodes
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Eazyest_Shortcodes;
			self::$instance->init();
		}
		return self::$instance;
	}

	/**
	 * Eazyest_Shortcodes::shortcodes()
	 * Hook shortcode tags
	 * Eazyest Gallery supports the following shortcodes:
	 *
	 * [eazyest_gallery]
	 * [eazyest_folder]
	 * [eazyest_slideshow]
	 *
	 * for compatibilty sake, the following shortcodes are supported:
	 * [lg_gallery]
	 * [lg_folder]
	 * [lg_image]
	 * [lg_slideshow]
	 *
	 * @since 0.1.0 (r2)
	 * @uses add_shrtcode
	 * @return void
	 */
	function shortcodes() {
		add_shortcode( 'eazyest_gallery',   array( $this, 'gallery_shortcode'   ) );

		add_shortcode( 'lg_gallery',        array( $this, 'lg_gallery_shortcode'   ) );

		add_shortcode( 'eazyest_folder',    array( $this, 'folder_shortcode'    ) );
		add_shortcode( 'lg_folder',         array( $this, 'lg_folder_shortcode'    ) );

		// lg_image is supported for back-compat reasons only. Use WordPress Media to insert images.
		add_shortcode( 'lg_image',          array( $this, 'lg_image_shortcode'     ) );

		add_shortcode( 'eazyest_slideshow', array( $this, 'slideshow_shortcode' ) );
		add_shortcode( 'lg_slideshow',      array( $this, 'lg_slideshow_shortcode' ) );
	}


	/**
	 * Eazyest_Shortcodes::gallery_shortcode()
	 * The Eazyest Gallery shortcode
	 * use <code>[eazyest_gallery]</code>
	 * @see http://codex.wordpress.org/Gallery_Shortcode for attributes
	 * Extra attribute: <code>'root="afolder/"'</code> to set nother root folder
	 *
	 * Plugins may override the eazyest_gallery shortcode
	 * <code>'eazyest_gallery'</code>
	 *
	 * WordPress filters used:
	 * <code>'gallery_style'</code>
	 *
	 * Action to <strong>echo</strong> content:
	 * <code>'eazyest_gallery_end_of_gallery'</code>
	 *
	 * @since 0.1.0 (r2)
	 * @uses apply_filters()
	 * @uses sanitize_sql_orderby()
	 * @uses shortcode_atts()
	 * @uses intval()
	 * @uses get_permalink()
	 * @uses WP_Query
	 * @uses esc_attr()
	 * @uses the_title_attribute()
	 * @uses wp_reset_query()
	 * @uses wp_reset_postdata()
	 * @uses get_pagenum_link()
	 * @param array $attr
	 * @return string html markup for eazyest-gallery
	 */
	function gallery_shortcode( $attr ) {

		$instance = ezg_instance();

		if ( ! empty( $attr['ids'] ) ) {
			// 'ids' is explicitly ordered, unless you specify otherwise.
			if ( empty( $attr['orderby'] ) )
				$attr['orderby'] = 'post__in';
			$attr['include'] = $attr['ids'];
		}
		global $ezg_doing_shortcode;
		$ezg_doing_shortcode = true;

		// Allow plugins/themes to override the default gallery template.
		$output = apply_filters( 'eazyest_gallery', '', $attr );
		if ( $output != '' )
			return $output;

		// We're trusting author input, so let's at least make sure it looks like a valid orderby statement
		if ( isset( $attr['orderby'] ) ) {
			$attr['orderby'] = sanitize_sql_orderby( $attr['orderby'] );
			if ( !$attr['orderby'] )
				unset( $attr['orderby'] );
		}

		list( $default_orderby, $default_ascdesc ) = explode( '-', eazyest_gallery()->sort_by() );
		$order_by = eazyest_gallery()->sort_by() == 'menu_order-ASC' ? 'menu_order' :  substr( $default_orderby[0], 5 );

		extract( shortcode_atts( array(
			'order'      => $default_ascdesc,
			'orderby'    => $default_orderby,
			'id'         => 0,
			'itemtag'    => 'dl',
			'icontag'    => 'dt',
			'captiontag' => 'dd',
			'columns'    => eazyest_gallery()->folders_columns,
			'size'       => 'thumbnail',
			'include'    => '',
			'exclude'    => '',
			'root'       => ''
		), $attr ) );

		$id = intval($id);
		if ( 'RAND' == $order )
			$orderby = 'none';

		//user has set a root folder (text)
		if ( ! empty( $root ) ) {
			$id = eazyest_folderbase()->get_folder_by_string( $root );
		}

		eazyest_gallery()->folders_columns = $columns;
		$folders_per_page = eazyest_gallery()->folders_page ? eazyest_gallery()->folders_page : -1;

		$args = array (
			'post_type'      => eazyest_gallery()->post_type,
			'post_parent'    => $id,
			'order'          => $order,
			'orderby'        => $orderby,
			'posts_per_page' => $folders_per_page,
		);

		if ( ! empty( $include ) ) {
			$include = str_replace( ' ', '', $include );
			$args['post__in'] = explode( ',', $include );
			// do not set post_parent if particular ids have been given
			unset( $args['post_parent'] );
		}
		if ( ! empty( $exclude ) ) {
			$exclude = str_replace( ' ', '', $exclude );
			$args['post__not_in'] = explode( ',', $exclude );
		}

		global $paged;
		if ( ! $paged )
			$paged = 1;
		if ( 1 < $paged )
			$args['paged'] = $paged;

		eazyest_frontend()->itemtag    = $itemtag;
		eazyest_frontend()->icontag    = $icontag;
		eazyest_frontend()->captiontag = $captiontag;

		$selector = "eazyest-gallery-{$instance}";

		$gallery_style = $gallery_div = '';
		$gallery_style = eazyest_frontend()->gallery_style( $selector, eazyest_gallery()->folders_columns );
		$classes = eazyest_frontend()->gallery_class( 'archive' );
		$gallery_div = "<div id='$selector' class='$classes'>";

		if ( apply_filters( 'eazyest_gallery_do_shortcode_title', false ) ) {
			$output =  apply_filters( 'eazyest_gallery_shortcode_title', '<h3>' . eazyest_gallery()->gallery_title() . '</h3>' );
		}
		$output .= apply_filters( 'gallery_style', $gallery_style . "\n\t\t" . $gallery_div );

		global $ezg_doing_folders;
		$ezg_doing_folders = true;
		$i = 0;

		$global_post = $GLOBALS['post'];
		$query = new WP_Query( $args );

		global $post;
		while( $query->have_posts() ) {
			$query->the_post();
			$thumbnail = eazyest_frontend()->folder_thumbnail_html();
			$link = '<a href="' . get_permalink() . '" title="' . esc_attr( sprintf( __( 'View folder &#8220;%s&#8221;', 'eazyest-gallery' ), the_title_attribute( 'echo=0' ) ) ) . '" rel="bookmark">' . $thumbnail . '</a>';
			$output .= "<{$itemtag} class='gallery-item folder-item'>";
			ob_start();
			do_action( 'eazyest_gallery_before_folder_icon', $post->ID );
			$output .= ob_get_contents();
			ob_end_clean();
			$output .= "
				<{$icontag} class='gallery-icon folder-icon'>
					$link
				</{$icontag}>";
			ob_start();
			do_action( 'eazyest_gallery_after_folder_icon', $post->ID );
			$output .= ob_get_contents();
			ob_end_clean();
			$output .= "</{$itemtag}>";
			if ( $columns > 0 && ++$i % $columns == 0 )
				$output .= '<br style="clear: both" />';
		}

		$output .= "
				<br style='clear: both;' />";
		ob_start();
		do_action( 'eazyest_gallery_end_of_gallery' );
		$output .= ob_get_contents();
		ob_end_clean();
		$output .=	"
			</div>\n";

		$nextpage = $prevpage = 0;
		if ( 1 < $query->max_num_pages ) {
			$nextpage = $paged + 1;
			$prevpage = $paged - 1;
			$nextpage = ( $nextpage <= $query->max_num_pages ) ? $nextpage : 0;
		}

		wp_reset_query();
		wp_reset_postdata();
		$GLOBALS['post'] = $global_post;

		// get page links if more folders than folders per page
		$nextpage_link = $prevpage_link = '';
		if ( $prevpage ) {
			$prevpage_link = "<span class='meta-nav'>&larr;</span> <a href='" . get_pagenum_link( $prevpage ) . "'>" . __( 'Previous folders page', 'eazyest-gallery' ) . "</a>";
		}
		if ( $nextpage ) {
			$nextpage_link = "<a href='" . get_pagenum_link( $nextpage ) . "'>" . __( 'Next folders page', 'eazyest-gallery' ) . "</a> <span class='meta-nav'>&rarr;</span>";
		}
		if ( ! empty( $nextpage_link ) || ! empty( $prevpage_link ) )
			$output .= "
				<style type='text/css'>
					.assistive-text {
						position: absolute !important;
						clip: rect(1px, 1px, 1px, 1px);
					}
				</style>
				<nav id='folder-nav-{$post->ID}' class='navigation' role='navigation'>
					<h3 class='assistive-text'>" . __( 'Folder navigation', 'eazyest-gallery' ) . "</h3>
					<div class='nav-previous alignleft'>$prevpage_link</div>
					<div class='nav-next alignright'>$nextpage_link</div>
				</nav>
			";
		wp_enqueue_script( 'eazyest_frontend' );

		$ezg_doing_folders    = false;
		$ezg_doing_shortcode  = false;

		// restore settings
		$options = get_option( 'eazyest-gallery' );
		eazyest_gallery()->folders_columns = $options['folders_columns'];
		return $output;
	}

	/**
	 * Eazyest_Shortcodes::folder_shortcode()
	 * The Eazyest Folder shortcode
	 * use [eazyest_folder folder="folder/folder/"] or [eazyest_folder id="15"]
	 * where id= refers to post->ID
	 * @see http://codex.wordpress.org/Gallery_Shortcode for attributes
	 *
	 * Uses filter for plugins to override the shortcode:
	 * <code>'eazyest_folder'</code>
	 * Filter for h3 title element:
	 * <code>'eazyest_folder_shortcode_title'</code>
	 *
	 * Actions to <strong>echo</strong> extra content:
	 * <code>'eazyest_gallery_before_folder_content'</code>
	 * <code>'eazyest_gallery_after_folder_content'</code>
	 *
	 * @since 0.1.0 (r2)
	 * @uses esc_html()
	 * @uses apply_filters()
	 * @uses sanitize_sql_orderby(
	 * @uses do+_action()
	 * @param mixed $attr
	 * @return string html markup for folder gallery
	 */
	function folder_shortcode( $attr ) {
			if( ! isset( $attr[0] ) )
				$attr[0] = 'eazyest_folder';

		if ( isset( $attr['folder'] ) && ! isset( $attr['id'] ) ) {
			$attr['id'] = eazyest_folderbase()->get_folder_by_string( $attr['folder'] );
			if ( ! isset( $attr['id'] ) || ! $attr['id']) {
				if ( isset( $attr['folder'] ) ) {
					return '<p class="error">' . sprintf( __( 'Apologies, but no results were found for folder &#8220;%s&#8221; in shortcode <code>[%s folder="%s"]</code>.', 'eazyest-gallery' ),
						esc_html( $attr['folder'] ),
						$attr[0],
						esc_html( $attr['folder'] )
						) . '</p>';
				} else	{
					return '<p class="error">' . sprintf( __( 'Apologies, but no folder could be matched in shortcode <code>[%s]</code>', 'eazyest-gallery' ), $attr[0] ) . '</p>';
				}
			}
		}
		global $ezg_doing_shortcode;
		$ezg_doing_shortcode = true;

		$output = apply_filters( 'eazyest_folder', '', $attr );
		if ( $output != '' )
			return $output;

		// We're trusting author input, so let's at least make sure it looks like a valid orderby statement
		if ( isset( $attr['orderby'] ) ) {
			$attr['orderby'] = sanitize_sql_orderby( $attr['orderby'] );
			if ( !$attr['orderby'] )
				unset( $attr['orderby'] );
		}

		list( $default_orderby, $default_ascdesc ) = explode( '-', eazyest_gallery()->sort_by( 'thumbnails' ) );
		$order_by = eazyest_gallery()->sort_by( 'thumbnails' ) == 'menu_order-ASC' ? 'menu_order' :  substr( $default_orderby[0], 5 );

		extract( shortcode_atts( array(
			'order'      => $default_ascdesc,
			'orderby'    => $default_orderby,
			'id'         => 0,
			'itemtag'    => 'dl',
			'icontag'    => 'dt',
			'captiontag' => 'dd',
			'columns'    => eazyest_gallery()->thumbs_columns,
			'size'       => 'thumbnail',
			'count'      => eazyest_gallery()->thumbs_page,
		), $attr ) );

		$id = intval( $id );
		if ( 'RAND' == $order )
			$orderby = 'none';

		if ( $orderby != $default_orderby ) {
			if ( ! in_array( $orderby, array( 'none', 'menu_order' ) ) )
				$orderby = 'post_' . $orderby;
		}
		eazyest_gallery()->sort_thumbnails = "$orderby-$order";
		eazyest_gallery()->thumbs_columns  =  $columns;
		eazyest_gallery()->thumbs_page     =  $count;

		eazyest_frontend()->itemtag    = $itemtag;
		eazyest_frontend()->icontag    = $icontag;
		eazyest_frontend()->captiontag = $captiontag;

		$global_post = $GLOBALS['post'];

		$output = '';
		global $post;
		if ( $folder = get_post( $id ) ) {
			$post = $folder;

			$folder_title = apply_filters( 'eazyest_folder_shortcode_title', '<h3>' . get_the_title() . '</h3>' );
			ob_start(); // buffer to use actions that echo content
			?>
			<?php echo $folder_title ?>
			<div class="eazyest-gallery">
				<?php do_action( 'eazyest_gallery_before_folder_content' ); // this action is used for breadcrumb trail and for thumbnail images ?>
				<?php	// we don't echo content in a folder shortcode ?>
				<?php do_action( 'eazyest_gallery_after_folder_content'  ); // this action is used for extra fields and for subfolders ?>
			</div>

			<?php
			$output = ob_get_contents();
			ob_end_clean();
		}
		$GLOBALS['post'] = $global_post;

		$ezg_doing_shortcode = false;

		// restore settings
		$options = get_option( 'eazyest-gallery' );
		eazyest_gallery()->sort_thumbnails = $options['sort_thumbnails'];
		eazyest_gallery()->thumbs_columns  = $options['thumbs_columns'];
		eazyest_gallery()->thumbs_page     = $options['thumbs_page'];

		return $output;
	}

	/**
	 * Eazyest_Shortcodes::slideshow_shortcode()
	 * Slideshow shortcode
	 * use <code>[eazyest_slideshow folder="folder/folder/"]</code>
	 * or <code>[eazyest_slideshow id="10"]</code>
	 * where id= $post->ID
	 *
	 * @since 0.1.0 (r2)
	 * @uses is_single()
	 * @uses esc_html()
	 * @param mixed $attr
	 * supported attributes:
	 * folder= (directory path or page-path)
	 * id= post ID
	 * orderby= (post_date,post_name,menu_order,...) default: see Eazyest Gallery settings
	 * order= (ASC,DESC)
	 * size= (thumbnail,medium,large,full) default: "large"
	 * skin= (amber,ash,azure,beige,black,blue,brown,burgundy,charcoal,chocolate,
	 *        coffee,cyan,fuchsia,gold,green,grey,indigo,khaki,lime,magenta,
	 *        maroon,orange,olive,pink,pistachio,red,tangerine,turquoise,
	 *        violet,white,yellow) default: "ash"
	 *
	 * @return string html markup for camera slideshow
	 */
	function slideshow_shortcode( $attr ) {
		if ( ! is_single() && ! is_page() )
			return '';

		if ( isset( $attr['folder'] ) && ! isset( $attr['id'] ) ) {
			$attr['id'] = eazyest_folderbase()->get_folder_by_string( $attr['folder'] );
			if ( ! isset( $attr['id'] ) || ! $attr['id']) {
				if ( isset( $attr['folder'] ) ) {
					return '<p class="error">' . sprintf( __( 'Apologies, but no results were found for folder &#8220;%s&#8221; in shortcode <code>[%s folder="%s"]</code>.', 'eazyest-gallery' ),
						esc_html( $attr['folder'] ),
						$attr[0],
						esc_html( $attr['folder'] )
						) . '</p>';
				} else	{
					if( ! isset( $attr[0] ) )
						$attr[0] = 'eazyest_folder';
					return '<p class="error">' . sprintf( __( 'Apologies, but no folder could be matched in shortcode <code>[%s]</code>', 'eazyest-gallery' ), $attr[0] ) . '</p>';
				}
			}
		}

		global $ezg_doing_shortcode;
		$ezg_doing_shortcode = true;

		ob_start(); // buffer to use actions that echo content
		eazyest_slideshow()->slideshow( $attr );
		$output = ob_get_contents();
		ob_end_clean();

		$ezg_doing_shortcode = false;
		return $output;
	}

	// functions for pre-2.0 shortcodes ------------------------------------------
	/**
	 * Eazyest_Shortcodes::lg_gallery_shortcode()
	 * Compatibility for <code>[lg_gallery]</code> shortcodes
	 * @see Eazyest_Shortcodes::gallery_shortcode()
	 *
	 * @deprecated 0.1.0
	 * @since lazyest-gallery 0.11.0
	 * @param array $attr
	 * @return string
	 */
	function lg_gallery_shortcode( $attr ) {
		$attr[0] = 'lg_gallery';
		return $this->gallery_shortcode( $attr );
	}

	/**
	 * Eazyest_Shortcodes::lg_folder_shortcode()
	 * Support for the <code>[lg_folder]</code> shortcode
	 *
	 * @see Eazyest_Shortcodes::folder_shortcode()
	 * @deprecated 0.1.0
	 * @deprecated use the [eazyest_folder] shortcode
	 *
	 * @since lazyest-gallery 0.11.0
	 * @param mixed $attr
	 * @return string html markup for folder gallery
	 */
	function lg_folder_shortcode( $attr ) {
		$attr[0] = 'lg_folder';
		return $this->folder_shortcode( $attr );
	}

	/**
	 * Eazyest_Shortcodes::lg_image_shortcode()
	 * Support for the <code>[lg_image]</code> shortcode
	 *
	 * @deprecated 0.1.0
	 * @deprecated Use the WordPress Media Manager to insert images in your posts
	 *
	 * @since lazyest-gallery 0.11.0
	 * @param array $attr
	 * attributes supported:
	 * folder= folder directory path
	 * image= image filename like "myimage.jpg"
	 * align= (left,center,right,none)
	 * display= (thumb,slide,image) parsed to: (thumbnail,large,full)
	 * @uses shortcode_atts()
	 * @uses get_post()
	 * @uses get_posts()
	 * @uses sanitize_file_name()
	 * @uses wp_get_attachment_image_src()
	 * @uses esc_html()
	 * @return string html markup for image
	 */
	function lg_image_shortcode( $attr ) {
		extract( shortcode_atts( array(
			'folder' => '',
			'image'  => '',
			'align'  => 'none',
			'display' => 'slide'
		), $attr ) );

		if ( ! in_array( $align, array( 'left', 'right', 'center', 'none' ) ) )
			$align = 'none';

		switch( $display ) {
			case 'thumb' :
				$display = 'thumbnail';
				break;
			case 'slide' :
				$display = 'large';
				break;
			case 'image' :
				$display = 'full';
				break;
			default :
				$display = 'medium';
		}

		$post_id = 0;
		if ( ! empty( $folder ) ) {
			// try to find the folder
			$post_id = eazyest_folderbase()->get_folder_by_string( $folder );
			if ( ! $post_id )
				return '<p class="error">' . sprintf( __( 'Apologies, but no folder could be matched in shortcode <code>[lg_image folder="%s" image="%s"]</code>', 'eazyest-gallery' ), esc_html( $folder ), esc_html( $image ) ) . '</p>';
		}
		if ( ! empty( $image ) && $post_id ) {
			// try to find the image
			$attachments = get_posts( array( 'numberposts' => -1, 'post_type' => 'attachment', 'post_mime_type' => 'image', 'post_parent' => $post_id, 'post_status' => null  ) );
			$attachment_id = 0;
			if ( ! empty( $attachments ) ) {
				foreach( $attachments as $attachment ) {
					$wp_src = eazyest_folderbase()->get_attachment_image_src( $attachment->ID, 'full' );
					if( $image == basename( $wp_src[0] ) ) {
						$attachment_id = $attachment->ID;
						break;
					}	else if ( sanitize_file_name( $image ) == basename( $wp_src[0] ) ) {
						$attachment_id = $attachment->ID;
						break;
					}	else {
						$pathinfo = pathinfo( $image );
						if ( $pathinfo['filename'] == $attachment->post_title ) {
							$attachment_id = $attachment->ID;
							break;
						} else if ( $pathinfo['filename'] == $attachment->post_excerpt ) {
							$attachment_id = $attachment->ID;
							break;
						}
					}
				}
			}
			if ( ! $attachment_id )
				return '<p class="error">' .  sprintf( __( 'Apologies, but no image could be matched in shortcode <code>[lg_image folder="%s" image="%s"]</code>', 'eazyest-gallery' ), esc_html( $folder ), esc_html( $image ) ) . '</p>';

			$attachment = get_post( $attachment_id );
			if ( empty( $caption ) )
				$caption = $attachment->post_excerpt;
			if ( empty( $caption ) )
				$caption = $attachment->post_title;
			$caption = esc_html( $caption );

			global $ezg_doing_shortcode;
			$ezg_doing_shortcode = true;

			$wp_sr = eazyest_folderbase()->get_attachment_image_src( $attachment->ID, $display );
			$width = 10 + $wp_src[1];
			$link   = eazyest_frontend()->add_attr_to_link( wp_get_attachment_link( $attachment->ID, $display ), $attachment->ID );
			$output = "
			<div id='attachment_{$attachment->ID}' class='wp-caption align{$align}' style='width:{$width}px'>
				$link
				<p class='wp-caption-text'>$caption</p>
			</div>
			";

			$ezg_doing_shortcode = false;
			return $output;
		}
		return '<p class="error">' . __( 'Apologies, but no folder nor an image could be matched in shortcode <code>[lg_image]</code>', 'eazyest-gallery' ) . '</p>';
	}

	/**
	 * Eazyest_Shortcodes::lg_slideshow_shortcode()
	 * Support for the <code>[lg_slideshow]</code> shortcode
	 * @deprecated 0.1.0
	 * @deprecated use Eazyest_Shortcodes::slideshow_shortcode()
	 *
	 * @since lazyest-gallery 0.12.0
	 * @param array $attr
	 * @return string html markup for slideshow
	 */
	function lg_slideshow_shortcode( $attr ) {
		$attr[0] = 'lg_slideshow';
		if ( isset( $attr['display'] ) )
			switch( $attr['display'] ) {
				case 'thumb' :
					$attr['display'] = 'thumbnail';
					break;
				case 'slide' :
					$attr['display'] = 'large';
					break;
				case 'image' :
					$display = 'full';
					break;
				default :
					$attr['display'] = 'medium';
			}
		return $this->slideshow_shortcode( $attr );
	}

} // Lazyest_Shortcodes
