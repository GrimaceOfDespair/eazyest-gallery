<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Eazyest_Slideshow
 *
 * @package Eazyest Gallery
 * @subpackage Frontend/Slideshow
 * @author Marcel Brinkkemper
 * @copyright 2013 Brimosoft
 * @since 0.1.0 (r2)
 * @version 0.1.0 (r296)
 * @access public
 */
class Eazyest_Slideshow {

	/**
	 * @var array $cameras id for camera slideshow elements
	 * @access private
	 */
	private $cameras = array();

	/**
	 * @staticvar object Eazyest_Slideshow single instance in memory
	 */
	private static $instance;

	function __construct(){}

	/**
	 * Eazyest_Slideshow::init()
	 *
	 * @return void
	 */
	private function init(){
		$this->actions();
	}

	/**
	 * Eazyest_Slideshow::instance()
	 *
	 * @return
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Eazyest_Slideshow;
			self::$instance->init();
		}
		return self::$instance;
	}

	/**
	 * Eazyest_Slideshow::actions()
	 *
	 * @uses add_action()
	 * @return void
	 */
	function actions() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts'       ),  50 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_camera_style'   )      );
		add_action( 'wp_footer',          array( $this, 'camera_scripts'         ), 100 );
		// ajax actions
	}

	/**
	 * Eazyest_Slideshow::register_scripts()
	 *
	 * @since 0.1.0 (r2)
	 * @uses wp_script_is()
	 * @uses wp_register_script()
	 * @return void
	 */
	function register_scripts() {
		$j = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? 'js' : 'min.js';

		// check if the camera scripts are already registered because other plugins use the same scripts
		if ( ! wp_script_is( 'jquery-easing', 'registered' ) )
			wp_register_script( 'jquery-easing', eazyest_gallery()->plugin_url . "frontend/js/jquery.easing.1.3.$j", array( 'jquery' ), '1.3', true );
		if ( ! wp_script_is( 'camera-slide', 'registered' ) )
			wp_register_script( 'camera-slide', eazyest_gallery()->plugin_url . "frontend/js/camera.$j", array( 'jquery', 'jquery-easing' ), '1.3.3', true );

		wp_register_script( 'eazyest-slideshow',  eazyest_gallery()->plugin_url . "frontend/js/eazyest-slideshow.$j", array( 'jquery' ), '0.1.0-r2', true );
		wp_localize_script( 'eazyest-slideshow', 'eazyestSlideshowSettings', $this->slideshow_settings() );
	}

	function slideshow_settings() {
		return array(
			'timeOut' => apply_filters( 'eazyest_gallery_ajax_timeout', 5000 ),
			'ajaxurl' => esc_js( admin_url( 'admin-ajax.php' ) ),
		);
	}

	/**
	 * Eazyest_Slideshow::enqueue_camera_scripts()
	 * Enqueue scripts for camera slideshow
	 *
	 * @since 0.1.0 (r2)
	 * @uses wp_enqueue_script()
	 * @return void
	 */
	function enqueue_camera_scripts() {
		wp_enqueue_script( 'jquery-easing' );
		wp_enqueue_script( 'camera-slide' );
	}

	/**
	 * Eazyest_Slideshow::enqueue_camera_style()
	 * Enqueue stylesheet for camera slideshow script
	 * The slideshow will not run on home page or archive pages
	 *
	 * @since 0.1.0 (r2)
	 * @uses is_single()
	 * @uses wp_enqueue_style()
	 * @return void
	 */
	function enqueue_camera_style() {
		// slideshow runs only for single posts or pages
		if ( ! is_single() && ! is_page() )
			return;
		wp_enqueue_style( 'eazyest-slideshow', eazyest_gallery()->plugin_url . 'frontend/css/camera.css', '0.1.0-r2' );
	}

	/**
	 * Eazyest_Slideshow::camera_scripts()
	 * Adds javascript in wp_footer to start the slideshow(s)
	 *
	 * @since 0.1.0 (r2)
	 * @uses apply_filters(  'eazyest_gallery_camera_slideshow_options' ) array of options for camera slideshow
	 * @see http://www.pixedelic.com/plugins/camera/#opts_anchor
	 * @return void
	 */
	function camera_scripts() {
		if ( empty( $this->cameras ) )
			return;

		$camera_options = apply_filters( 'eazyest_gallery_camera_slideshow_options', array(
			'thumbnails' => false,
			'pagination' => false,
			'portrait'   => false,
			'height'		 => '75%',
			'time'       => 5000,
			'fx'         => 'simpleFade',
		) );
		foreach( $this->cameras as $id ) {
			?>
		<script type="text/javascript">
			(function($) {
				$('#camera_wrap_<?php echo $id ?>').camera(<?php echo json_encode($camera_options); ?>);
			})(jQuery)
		</script>
			<?php
		}
	}

	/**
	 * Eazyest_Slideshow::data_image()
	 * Return src attribute for images in the slideshow
	 *
	 * @since 0.1.0 (r2)
	 * @uses WP_Post
	 * @param string $size
	 * @return string
	 */
	private function data_image( $size = 'thumbnail' ) {
		global $post;
		$wp_src = eazyest_folderbase()->get_attachment_image_src( $post->ID, $size );
		return $wp_src[0];
	}

	/**
	 * Eazyest_Slideshow::camera_slideshow()
	 * Display the camera slideshow
	 * @uses Camera slideshow v1.3.3 - a jQuery slideshow with many effects, transitions, easy to customize, using canvas and mobile ready, based on jQuery 1.4+
	 * @copyright 2012 by Manuel Masia - www.pixedelic.com
	 * @see http://www.pixedelic.com/plugins/camera/
	 *
	 * @since 0.1.0 (r2)
	 * @uses WP_Query
	 * @uses WP_Post
	 * @uses wp_get_attachment_link()
	 * @uses wp_reset_query()
	 * @uses wp_reset_postdata()
	 * @param array $query_args for WP_Query
	 * @param integer $id  = galleryfolder ID
	 * @param string $size = ('thumbnail','medium','large','full')
	 * @param string $skin = (amber,ash,azure,beige,black,blue,brown,burgundy,charcoal,chocolate,
	 *                        coffee,cyan,fuchsia,gold,green,grey,indigo,khaki,lime,magenta,
	 *                        maroon,orange,olive,pink,pistachio,red,tangerine,turquoise,
	 *                        violet,white,yellow) default: "ash"
	 * @return void
	 */
	function camera_slideshow( $query_args = array(), $id = 0, $size = 'large', $skin = 'ash' ) {
		if ( empty( $query_args ) )
			return;

		$query = new WP_Query( $query_args );

		if ( ! $query->post_count ){
			echo "\n<p>" . __( 'Eazyest Gallery found no attachments to build a slideshow', 'eazyest-gallery' ) . "</p>\n";
			return;
		}

		$global_post = $GLOBALS['post'];
		global $post;

		$caption_div = '';
		?>
		<div class="fluid_container">
			<div class="camera_wrap camera_<?php echo $skin ?>_skin" id="camera_wrap_<?php echo $id ?>">
			<?php while( $query->have_posts() ) :	$query->the_post(); ?>
			<?php
				if ( apply_filters( 'eazyest_gallery_slideshow_captions', true ) ) {
				$caption_div = "
						<div class='camera_caption fadeFromBottom'>
	             " . wp_get_attachment_link( $post->ID, 'none', true, false, wptexturize( $post->post_excerpt ) ) . "
	          </div>
				";
			}
			?>
				<div data-thumb="<?php echo $this->data_image( 'thumbnail' ) ?>" data-src="<?php echo $this->data_image( $size ) ?>">
	         <?php echo $caption_div; ?>
	      </div>
			<?php endwhile; ?>
			</div>
		</div> <!-- fluid_container -->
		<br style="clear:both;"/>
		<?php

		wp_reset_query();
		wp_reset_postdata();
		$GLOBALS['post'] = $global_post;

		$this->cameras[] = $id;
		$this->enqueue_camera_scripts();
	}

	/**
	 * Eazyest_Slideshow::ajax_slideshow()
	 * Display the Ajax driven slideshow
	 *
	 * @since 0.1.0 (r2)
	 * @uses get_transient()
	 * @uses set_transient()
	 * @uses WP_Query
	 * @uses wp_nonce_field()
	 * @uses wp_get_attachment_link()
	 * @param array $query_args for WP_Query
	 * @param integer $id
	 * @param string $size
	 * @return void
	 */
	function ajax_slideshow( $query_args, $show = 1, $size = 'thumbnail' ) {
		if ( empty( $query_args ) )
			return;

		$global_post = $GLOBALS['post'];
		global $post;

		if ( $query_args != get_transient( "eazyest-ajax-slideshow-$show" ) )
			set_transient( "eazyest-ajax-slideshow-$show", $query_args, DAY_IN_SECONDS );

		if ( isset( $query_args['size'] ) ) {
			$size = $query_args['size'];
			unset( $query_args['size'] );
		}
		$query_args['posts_per_page'] = 1;
		$height = absint( get_option( "{$size}_size_h" ) ) + 20;
		$width  = absint( get_option( "{$size}_size_w" ) ) + 20;
		$query = new WP_Query( $query_args );
		if ( $query->have_posts() ) : $query->the_post();
		?>
		<style type="text/css">
			#eazyest-ajax-slideshow-<?php echo $show ?> {
				position: relative;
				height: <?php echo $height; ?>px;
				width: !important<?php echo $width; ?>px;
			}
			#eazyest-ajax-slideshow-<?php echo $show ?> .gallery-item {
				position: absolute;
				top: 0;
				left: 0;
			}
			#eazyest-ajax-slideshow-<?php echo $show ?> .gallery-item.top {
				z-index: 1000;
			}
		</style>
		<form id="eazyest-slideshow-form-<?php echo $show ?>">
			<?php wp_nonce_field( 'eazyest-ajax-nonce-' . $show, 'eazyest-ajax-nonce-' . $show  ); ?>
			<div id="eazyest-ajax-slideshow-<?php echo $show ?>" class="gallery eazyest-gallery eazyest-ajax-slideshow">
				<<?php ezg_itemtag(); ?> class="gallery-item top">
					<<?php ezg_icontag();?> class="gallery-icon">
						<?php echo wp_get_attachment_link( $post->ID, $size, true ); ?>
					</<?php ezg_icontag();?>>
				</<?php ezg_itemtag(); ?>>
				<<?php ezg_itemtag(); ?> class="gallery-item bottom">
					<<?php ezg_icontag();?> class="gallery-icon">
						<?php echo wp_get_attachment_link( $post->ID, $size, true ); ?>
					</<?php ezg_icontag();?>>
				</<?php ezg_itemtag(); ?>>
			</div>
		</form>
		<?php endif; ?>
		<?php
		wp_enqueue_script( 'eazyest-slideshow' );
	}

	/**
	 * Eazyest_Slideshow::slideshow()
	 *
	 * Uses filter for plugins to override the shortcode:
	 * <code>'eazyest_slideshow'</code>
	 *
	 * supported attributes:
	 * id      = post ID
	 * orderby = (post_date,post_name,menu_order,...) default: see Eazyest Gallery settings
	 * order   = (ASC,DESC)
	 * size    = (thumbnail,medium,large,full) default: "large"
	 * skin    = (amber,ash,azure,beige,black,blue,brown,burgundy,charcoal,chocolate,
	 *            coffee,cyan,fuchsia,gold,green,grey,indigo,khaki,lime,magenta,
	 *            maroon,orange,olive,pink,pistachio,red,tangerine,turquoise,
	 *            violet,white,yellow) default: "ash"
	 * skin is not applied for ajax thumbnail slideshows
	 *
	 * @since 0.1.0 (r2)
	 * @uses sanitize_sql_orderby()
	 * @uses apply_filters() for 'eazyest_gallery_slideshow_attr' array filter
	 * @uses shortcode_atts to parse $attr array
	 * @param mixed $attr
	 * @return void;
	 */
	function slideshow( $attr ) {
		$output = apply_filters( 'eazyest_slideshow', '', $attr );
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

		$attr = apply_filters( 'eazyest_gallery_slideshow_attr', $attr );

		extract( shortcode_atts( array(
			'order'      => $default_ascdesc,
			'orderby'    => $default_orderby,
			'id'         => 0,
			'subfolders' => 0,
			'number'     => -1,
			'size'       => 'large',
			'skin'       => apply_filters( 'eazyest_gallery_camera_slideshow_skin', 'ash'),
			'ajax'       => 0,
			'show'       => 1,
		), $attr ) );
		$id = intval($id);
		if ( ! $id )
			$id = $GLOBALS['post']->ID;

		if ( 'RAND' == $order )
			$orderby = 'none';

		if ( $subfolders ) {
			$children = eazyest_folderbase()->children_images( $id );
			if ( ! empty( $children ) )	{
				$post__in = $children;
			}
		}
		$args =	array(
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'post_status'    => array( 'publish', 'inherit' ),
			'posts_per_page'  => $number,
			'orderby'        => $orderby,
			'order'          => $order,
		);
		if ( isset( $post__in ) ) {
			$args['post__in'] = $post__in;
		} else {
			$args['post_parent'] = $id;
		}
		if ( $ajax ) {
			$arg['size'] = $size;
			$this->ajax_slideshow( $args, $show );
		}
		else {
			$this->camera_slideshow( $args, $id, $size, $skin );
		}
	}

} // Eazyest_Slideshow

/**
 * eazyest_slideshow()
 * @since 0.1.0 (r2)
 * @return object Eazyest_Slideshow
 */
function eazyest_slideshow() {
	return Eazyest_Slideshow::instance();
}
