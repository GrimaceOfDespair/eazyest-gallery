<?php 

/**
 * Widgets for Eazyest Gallery.
 *
 * This file holds the widgets for Eazyest Gallery
 * It includes (replacements for) the widgets in Lazyest Gallery
 * It includes also (replacements for) the widgets from the Lazyest Widgets plugin
 *
 * @package Eazyest Gallery
 * @subpackage Widgets
 * @version 0.1.0 (r242)
 *
 * @link http://codex.wordpress.org/Widgets_API for WordPress Widgets API
 * @link http://core.trac.wordpress.org/browser/tags/3.5/wp-includes/widgets.php for WP_Widget class
 */


// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// Deactivate the lazyest-widgets plugin
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if ( is_plugin_active( 'lazyest-widgets/lazyest-widgets.php' ) )
	deactivate_plugins( 'lazyest-widgets/lazyest-widgets.php' );



/**
 * Eazyest_Widgets
 * Registerd all widgets for Eazyest Gallery.
 *
 * @package Eazyest Gallery
 * @subpackage Widgets
 * @author Marcel Brinkkemper
 * @copyright 2013 Brimosoft
 * @version 0.1.0 (r108)
 * @access public
 */
class Eazyest_Widgets {

	 /**
   * @staticvar Eazyest_Widgets $instance single object in memory
   */
  private static $instance;

  /**
   * Eazyest_Widgets::__construct()
   *
   * @return void
   */
  function __construct() {}

  /**
   * Eazyest_Widgets::init()
   * Initialize.
   * @return void
   */
  private function init() {
  	$this->actions();
  }

  /**
   * Eazyest_Widgets::instance()
   *
   * @return object Eazyest_Widgets
   */
  public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Eazyest_Widgets;
			self::$instance->init();
		}
		return self::$instance;
  }

  /**
   * Eazyest_Widgets::actions()
   * Add Actions.
   *
   * @since 0.1.0 (r2)
   * @uses add_action() for 'widget_init'
   * @return void
   */
  function actions() {
  	add_action( 'widgets_init', array( $this, 'register_widgets' ) );
  }

  /**
   * Eazyest_Widgets::register_widgets()
   * Regietsr the widgets in the WordPress Widgets API.
   *
	 * @since 0.1.0 (r2)
	 * @uses register_widget()
   * @return void
   */
  function register_widgets() {
  	$widgets = array(
  		'List_Folders',
			'Random_Images',
			'Random_Slideshow',
			'Recent_Folders',
			'Recent_Images',
		);
		foreach( $widgets as $widget )
			register_widget( "Eazyest_Widget_$widget" );
  }
} // Eazyest_Widgets

/**
 * Eazyest_Widget_Recent_Images
 * Recent images widget.
 * Replaces the Recent Images widget from the eazyest-widgets plugin
 *
 * @access public
 * @since 0.1.0 (r2)
 */
class Eazyest_Widget_Recent_Images extends WP_Widget {

	/**
	 * Eazyest_Widget_Recent_Images::__construct()
	 *
	 * @return void
	 */
	function __construct() {
		$widget_ops = array(
			'classname'   =>     'widget_eazyest_last_image',
			'description' => __( 'The most recent images in your gallery', 'eazyest-gallery' )
		);

		parent::__construct( 'eazyest_last_image', __( 'EZG Latest Images', 'eazyest-gallery' ), $widget_ops );
	}

	/**
	 * Eazyest_Widget_Recent_Images::widget()
	 *
	 * @param array $args
	 * @param WP_Widget $instance
	 * @return void
	 */
	function widget( $args, $instance ) {
		extract($args);
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? __( 'Recent Images', 'eazyest-gallery') : $instance['title'], $instance, $this->id_base );

		if ( ! $number = absint( $instance['number'] ) )
			// number of recent images to show
 			$number = 4;

		if ( ! $columns = absint( $instance['columns'] ) )
			$columns = 2;

 		$subfolders = $instance['subfolders'] == 1 ? true : false;
 		if ( ! $post_id = $instance['post_id'] )
			$subfolders = true;
		?>
		<?php echo $before_widget; ?>
		<?php if ( $title ) echo $before_title . $title . $after_title; ?>
		<div class="eazyest-recent-images">
			<?php ezg_recent_images( array( 'id' => $post_id, 'number' => $number, 'title' => '', 'subfolders' => $subfolders, 'columns' => $columns ) ); ?>
		</div>
		<?php echo $after_widget; ?>
		<?php
	}

	/**
	 * Eazyest_Widget_Recent_Images::update()
	 *
	 * @param WP_Widget $new_instance
	 * @param WP_Widget $old_instance
	 * @return WP_Widget $instance
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title']      = strip_tags( $new_instance['title'] );
		$instance['number']     = absint( $new_instance['number'] );
		$instance['post_id']    = absint( $new_instance['post_id'] );
		$instance['columns']    = absint( $new_instance['columns'] );
		$instance['subfolders'] = $new_instance['subfolders'];
		return $instance;
	}

	/**
	 * Eazyest_Widget_Recent_Images::form()
	 *
	 * @param mixed $instance
	 * @return void
	 */
	function form( $instance ) {
		$title   = isset( $instance['title']   )       ? esc_attr( $instance['title']  ) : '';
		$number  = isset( $instance['number']  )       ? absint(  $instance['number']  ) : 4;
		$post_id = isset( $instance['post_id'] )       ? absint(  $instance['post_id'] ) : 0;
		$columns = isset( $instance['columns'] )       ? absint(  $instance['columns'] ) : 2;
		$subfolders = isset( $instance['subfolders'] ) ? $instance['subfolders']         : 0;

		if ( $post_id == 0 )
			$subfolders = 1;

		$options = "<option value='0'";
			if ( 0 == $post_id )
				$options .= "selected='selected'";
		$options .= ">" . __( 'All folders', 'eazyest-gallery' ) . "</option>\n";
		$folders = get_posts( array( 'post_type' => eazyest_gallery()->post_type ) );
		if ( ! empty( $folders ) ) {
			foreach( $folders as $folder ) {
				$gallery_path = ezg_get_gallery_path( $folder->ID );
				$post_title = esc_html( $folder->post_title );
				$options .= "<option value'$folder->ID'";
				if ( $post_id == $folder->ID )
					$options .= "selected='selected'";
				$options .= ">$post_title</option>\n";
			}
		}
?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'eazyest-gallery' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" class="widefat" value="<?php echo $title; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of images to show:', 'eazyest-gallery' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="number" min="1" max="16" class="small-text" value="<?php echo $number; ?>" size="3" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'columns' ); ?>"><?php _e( 'Display in columns:', 'eazyest-gallery' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'columns' ); ?>" name="<?php echo $this->get_field_name( 'columns' ); ?>" type="number" min="1" max="16" class="small-text" value="<?php echo $columns; ?>" size="3" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'post_id' ); ?>"><?php _e( 'Select images from folder:', 'eazyest-gallery' ); ?></label>
			<select id="<?php echo $this->get_field_id( 'post_id' ); ?>"  name="<?php echo $this->get_field_name( 'post_id' ); ?>"><?php echo $options ?></select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'subfolders' ); ?>"><?php _e( 'Include subfolders', 'eazyest-gallery' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'subfolders' ); ?>" name="<?php echo $this->get_field_name( 'subfolders' ); ?>" type="checkbox" value="1" <?php checked( $subfolders ) ?> />
		</p>
<?php
	}

} // Eazyest_Widget_Recent_Images



/**
 * Eazyest_Widget_Recent_Folders
 * A Widget showing a gallery of the latest added folder icons
 *
 * @since 0.1.0 (r2)
 * @access public
 */
class Eazyest_Widget_Recent_Folders extends WP_Widget {

	/**
	 * Eazyest_Widget_Recent_Folders::__construct()
	 *
	 * @return void
	 */
	function __construct() {
		$widget_ops = array(
			'classname'   =>     'widget_eazyest_last_folder',
			'description' => __( 'The most recent folders in your gallery', 'eazyest-gallery' )
		);
		parent::__construct( 'eazyest_last_folder', __( 'EZG Recent Folders', 'eazyest-gallery' ), $widget_ops );
	}

	/**
	 * Eazyest_Widget_Recent_Folders::widget()
	 *
	 * @uses EazyestGallery::get_option
	 * @param mixed $args
	 * @param mixed $instance
	 * @return void
	 */
	function widget( $args, $instance ) {
		extract($args);
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? __( 'Recent Folders', 'eazyest-gallery') : $instance['title'], $instance, $this->id_base );

		if ( ! $number = absint( $instance['number'] ) )
			// number of recent folders to show
 			$number = 4;

		if ( ! $columns = absint( $instance['columns'] ) )
			$columns = 2;
		?>
		<?php echo $before_widget; ?>
		<?php if ( $title ) echo $before_title . $title . $after_title; ?>
		<div class="eazyest-recent-folders">
			<?php ezg_recent_folders( array( 'number' => $number, 'title' => '', 'columns' => $columns ) ); ?>
		</div>
		<?php echo $after_widget; ?>
		<?php
	}

	/**
	 * Eazyest_Widget_Recent_Folders::update()
	 *
	 * @param mixed $new_instance
	 * @param mixed $old_instance
	 * @return
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title']   = strip_tags( $new_instance['title'] );
		$instance['number']  = (int) $new_instance['number'];
		$instance['columns'] = absint( $new_instance['columns'] );
		return $instance;
	}

	/**
	 * Eazyest_Widget_Recent_Folders::form()
	 *
	 * @param mixed $instance
	 * @return void
	 */
	function form( $instance ) {
		$title = isset( $instance['title'] )     ? esc_attr( $instance['title'] ) : '';
		$number = isset( $instance['number'] )   ? absint( $instance['number'] ) : 4;
		$columns = isset( $instance['columns'] ) ? absint(  $instance['columns'] ) : 2;
?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'eazyest-gallery' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>
		<p>
			<label for="<?php echo $this->get_field_id( 'columns' ); ?>"><?php _e( 'Display in columns:', 'eazyest-gallery' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'columns' ); ?>" name="<?php echo $this->get_field_name( 'columns' ); ?>" type="number" min="1" max="16" class="small-text" value="<?php echo $columns; ?>" size="3" />
		</p>
		<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of folder icons to show:', 'eazyest-gallery' ); ?></label>
		<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>
<?php
	}

} // Eazyest_Widget_Recent_Folders

/**
 * Eazyest_Widget_Random_Images
 * Widget for random images.
 * Replaces the LG Random image widget from pre-2.0 versions
 * Replaces the Really Random Images widget from the eazyest-widgets plugin
 *
 * @since 0.1.0 (r2)
 * @access public
 */
class Eazyest_Widget_Random_Images extends WP_Widget {

	function __construct() {
		$widget_ops = array(
		 'classname'   =>     'widget_eazyest_random_image',
		 'description' => __( 'Random images from your gallery', 'eazyest-gallery' )
		 );
		parent::__construct( 'eazyest_random_image', __('EZG Random Images', 'eazyest-gallery' ), $widget_ops );
	}

	/**
	 * Eazyest_Widget_Random_Images::widget()
	 *
	 * @param mixed $args
	 * @param mixed $instance
	 * @return void
	 */
	function widget($args, $instance) {
		global $lg_gallery;
		extract($args);
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? __( 'Random Images', 'eazyest-gallery' ) : $instance['title'], $instance, $this->id_base );

		if ( ! $number = absint( $instance['number'] ) )
			// number of random images to show
 			$number = 4;

		if ( ! $columns = absint( $instance['columns'] ) )
			$columns = 2;

		$subfolders = $instance['subfolders'] == 1 ? true : false;
 		if ( ! $post_id = $instance['post_id'] )
			$subfolders = true;

				?>
		<?php echo $before_widget; ?>
		<?php if ( $title ) echo $before_title . $title . $after_title; ?>
		<div class="eazyest-random-images">
			<?php ezg_random_images( array( 'id' => $post_id, 'number' => $number, 'title' => '', 'subfolders' => $subfolders, 'columns' => $columns ) ); ?>
		</div>
		<?php echo $after_widget; ?>
		<?php
	}

	/**
	 * Eazyest_Widget_Random_Images::update()
	 *
	 * @param mixed $new_instance
	 * @param mixed $old_instance
	 * @return
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title']      = strip_tags( $new_instance['title'] );
		$instance['number']     = absint( $new_instance['number'] );
		$instance['post_id']    = absint( $new_instance['post_id'] );
		$instance['columns']    = absint( $new_instance['columns'] );
		$instance['subfolders'] = $new_instance['subfolders'];
		return $instance;
	}

	/**
	 * Eazyest_Widget_Random_Images::form()
	 *
	 * @param mixed $instance
	 * @return void
	 */
	function form( $instance ) {
		$title   = isset( $instance['title']   )       ? esc_attr( $instance['title']  ) : '';
		$number  = isset( $instance['number']  )       ? absint(  $instance['number']  ) : 4;
		$post_id = isset( $instance['post_id'] )       ? absint(  $instance['post_id'] ) : 0;
		$columns = isset( $instance['columns'] )       ? absint(  $instance['columns'] ) : 2;
		$subfolders = isset( $instance['subfolders'] ) ? $instance['subfolders']         : 0;

		if ( $post_id == 0 )
			$subfolders = 1;

		$options = "<option value='0'";
			if ( 0 == $post_id )
				$options .= "selected='selected'";
		$options .= ">" . __( 'All folders', 'eazyest-gallery' ) . "</option>\n";
		$folders = get_posts( array( 'post_type' => eazyest_gallery()->post_type ) );
		if ( ! empty( $folders ) ) {
			foreach( $folders as $folder ) {
				$gallery_path = ezg_get_gallery_path( $folder->ID );
				$post_title = esc_html( $folder->post_title );
				$post_title = str_repeat( '&#8212; ', substr_count( $gallery_path, '/' ) ) . $post_title;
				$options .= "<option value'$folder->ID'";
				if ( $post_id == $folder->ID )
					$options .= "selected='selected'";
				$options .= ">$post_title</option>\n";
			}
		}
?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'eazyest-gallery' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" class="widefat" value="<?php echo $title; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of images to show:', 'eazyest-gallery' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="number" min="1" max="16" class="small-text" value="<?php echo $number; ?>" size="3" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'columns' ); ?>"><?php _e( 'Display in columns:', 'eazyest-gallery' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'columns' ); ?>" name="<?php echo $this->get_field_name( 'columns' ); ?>" type="number" min="1" max="16" class="small-text" value="<?php echo $columns; ?>" size="3" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'post_id' ); ?>"><?php _e( 'Select images from folder:', 'eazyest-gallery' ); ?></label>
			<select id="<?php echo $this->get_field_id( 'post_id' ); ?>"  name="<?php echo $this->get_field_name( 'post_id' ); ?>"><?php echo $options ?></select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'subfolders' ); ?>"><?php _e( 'Include subfolders', 'eazyest-gallery' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'subfolders' ); ?>" name="<?php echo $this->get_field_name( 'subfolders' ); ?>" type="checkbox" value="1" <?php checked( $subfolders ) ?> />
		</p>
<?php
	}

} // Eazyest_Widget_Random_Images

/**
 * Eazyest_Widget_Random_Slideshow
 * Displays an Ajax driven slideshow for ranomly selected images
 *
 * @since 0.1.0 (r2)
 * @access public
 */
class Eazyest_Widget_Random_Slideshow extends WP_Widget {

	/**
	 * Eazyest_Widget_Random_Slideshow::__construct()
	 *
	 * @return void
	 */
	function __construct() {
		$widget_ops = array(
			'classname'   =>     'widget_eazyest_random_slideshow',
			'description' => __( 'A random thumbnail slideshow for your gallery', 'eazyest-gallery' ) );
		parent::__construct( 'eazyest_random_slideshow', __('EZG Random Images Slideshow', 'eazyest-gallery' ), $widget_ops );
	}

	/**
	 * Eazyest_Widget_Random_Slideshow::widget()
	 * @see Eazyest_Slideshow::slideshow()
	 *
	 * @since 0.1.0 (r2)
	 * @uses apply_filters() for 'widget_title'
	 * @param array $args
	 * @param array $instance
	 * @return void
	 */
	function widget( $args, $instance ) {
		extract($args);
		$title = apply_filters( 'widget_title', empty($instance['title']) ? __( 'Random Image Slideshow', 'eazyest-gallery' ) : $instance['title'], $instance, $this->id_base );

 		$subfolders = $instance['subfolders'] == 1 ? true : false;
 		if ( ! $post_id = $instance['post_id'] )
			$subfolders = true;
		?>

		<?php echo $before_widget; ?>
		<?php if ( $title ) echo $before_title . $title . $after_title; ?>
		<div class="eazyest-random-slidehow">
			<?php eazyest_slideshow()->slideshow( array( 'id' => $post_id, 'title' => '', 'subfolders' => $subfolders, 'ajax' => true, 'orderby' => 'rand', 'size' => 'thumbnail', 'show' => $this->number ) ); ?>
		</div>
		<?php
		echo $after_widget;
	}

	/**
	 * Eazyest_Widget_Random_Slideshow::update()
	 *
	 * @since 0.1.0 (r242)
	 * @param mixed $new_instance
	 * @param mixed $old_instance
	 * @return
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title']      = strip_tags( $new_instance['title'] );
		$instance['post_id']    = absint( $new_instance['post_id'] );
		$instance['subfolders'] = $new_instance['subfolders'];
		return $instance;
	}

	/**
	 * Eazyest_Widget_Random_Slideshow::form()
	 *
	 * @param mixed $instance
	 * @return void
	 */
	function form( $instance ) {
		$title   = isset( $instance['title']   )       ? esc_attr( $instance['title']  ) : '';
		$post_id = isset( $instance['post_id'] )       ? absint(  $instance['post_id'] ) : 0;
		$subfolders = isset( $instance['subfolders'] ) ? $instance['subfolders']         : 0;

		if ( $post_id == 0 )
			$subfolders = 1;

		$options = "<option value='0'";
			if ( 0 == $post_id )
				$options .= "selected='selected'";
		$options .= ">" . __( 'All folders', 'eazyest-gallery' ) . "</option>\n";
		$folders = get_pages( array( 'post_type' => eazyest_gallery()->post_type, 'posts_per_page' => -1,  ) );
		if ( ! empty( $folders ) ) {
			foreach( $folders as $folder ) {
				$gallery_path = ezg_get_gallery_path( $folder->ID );
				$post_title = esc_html( $folder->post_title );
				$post_title = str_repeat( '&#8212; ', substr_count( $gallery_path, '/' ) ) . $post_title;
				$options .= "<option value='$folder->ID'";
				if ( $post_id == $folder->ID )
					$options .= "selected='selected'";
				$options .= ">$post_title</option>\n";
			}
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'eazyest-gallery' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" class="widefat" value="<?php echo $title; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'post_id' ); ?>"><?php _e( 'Select images from folder:', 'eazyest-gallery' ); ?></label>
			<select id="<?php echo $this->get_field_id( 'post_id' ); ?>"  name="<?php echo $this->get_field_name( 'post_id' ); ?>"><?php echo $options ?></select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'subfolders' ); ?>"><?php _e( 'Include subfolders', 'eazyest-gallery' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'subfolders' ); ?>" name="<?php echo $this->get_field_name( 'subfolders' ); ?>" type="checkbox" value="1" <?php checked( $subfolders ) ?> />
		</p>
		<?php
	}
} //Eazyest_Widget_Random_Slideshow

/**
 * Eazyest_Widget_List_Folders
 *
 * @since 0.1.0 (r15)
 * @access public
 */
class Eazyest_Widget_List_Folders extends WP_Widget {

	/**
	 * Eazyest_Widget_List_Folders::__construct()
	 *
	 * @since 0.1.0 (r15)
	 * @return void
	 */
	function __construct() {
		$widget_ops = array(
			'classname'   =>     'widget_eazyest_list_folders',
			'description' => __( 'Show a list of all your Eazyest Gallery folders', 'eazyest-gallery' )
		);

		parent::__construct( 'eazyest_list_folders', __( 'EZG List Folders', 'eazyest-gallery' ), $widget_ops );
	}

	/**
	 * Eazyest_Widget_List_Folders::widget()
	 *
	 * @since 0.1.0 (r15)
	 * @param array $args
	 * @param array $instance
	 * @return void
	 */
	function widget( $args, $instance ) {
		global $lg_gallery;
		extract( $args );
		$title = apply_filters( 'widget_title', empty($instance['title']) ? __( 'Gallery List', 'eazyest-gallery' ) : $instance['title'], $instance, $this->id_base );
		?>
		<?php echo $before_widget; ?>
		<?php if ( $title ) echo $before_title . $title . $after_title; ?>
		<div class="eazyest-list-folders">
			<?php ezg_list_folders() ?>
		</div>
		<?php
		echo $after_widget;
	}

	/**
	 * Eazyest_Widget_List_Folders::form()
	 *
	 * @since 0.1.0 (r15)
	 * @param array $instance
	 * @return void
	 */
	function form( $instance ) {
		$title   = isset( $instance['title']   )       ? esc_attr( $instance['title']  ) : '';
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'eazyest-gallery' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" class="widefat" value="<?php echo $title; ?>" />
		</p>
		<?php
	}

	/**
	 * Eazyest_Widget_List_Folders::update()
	 *
	 * @since 0.1.0 (r15)
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title']  = strip_tags( $new_instance['title'] );
		return $instance;
	}

} // Eazyest_Widget_List_Folders

/**
 * eazyest_widgets()
 *
 * @return object Eazyest_Widgets
 */
function eazyest_widgets() {
	return Eazyest_Widgets::instance();
}
