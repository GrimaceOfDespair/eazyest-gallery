<?php 
 
/*
 * Plugin Name: Eazyest Gallery
 * Plugin URI: http://brimosoft.nl/eazyest/gallery/
 * Description: Easy Gallery management plugin for Wordpress
 * Date: January 2013
 * Author: Brimosoft
 * Author URI: http://brimosoft.nl
 * Version: 0.1.0-alpha-r8
 * License: GNU General Public License, version 3
 */
 
/**
 * Eazyest Gallery is easy gallery management software for WordPress.
 * 
 * @version 0.1.0 (r8)  
 * @package Eazyest Gallery
 * @subpackage Main
 * @link http://brimosoft.nl/eazyest/gallery/
 * @author Marcel Brinkkemper <eazyest@brimosoft.nl>
 * @copyright      2004 Nicholas Bruun Jespersen (lazy-gallery)
 * @copyright 2005-2006 Valerio Chiodino         (lazyest-gallery)
 * @copyright 2008-2013 Marcel Brinkkemper       (lazyest-gallery + eazyest-gallery)
 * @license GNU General Public License, version 3
 * @license http://www.gnu.org/licenses/
 * 
 * 
 * @uses TableDnD plug-in for JQuery,
 * @copyright (c) Denis Howlett
 * 
 * @uses JQuery File Tree, 
 * @copyright (c) 2008, A Beautiful Site, LLC
 * 
 * @uses Camera slideshow v1.3.3,
 * @copyright (c) 2012 by Manuel Masia - www.pixedelic.com 
 * 
 * In this plugin source code, phpDoc @uses refer to WordPress functions for compatibility checks
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit; 
 
/**
 *  EZG_SECURE_VERSION Last version where options or database settings have changed
 */
define('EZG_SECURE_VERSION', '0.1.0'); 

/**
 * Eazyest_Gallery
 * Eazyest Gallery core class.
 * Holds the options and basic functions
 * 
 * @since lazyest-gallery 0.16.0
 * @version 0.1.0 (r2)
 * @access public
 */
class Eazyest_Gallery {
	
	/**
	 * @var array $data overloaded variables
	 */ 
	private $data;

	/**
	 *
	 * @var array $options Holding all the eazyest-gallery options
	 */
	private $options = array();
	
	/**
	 * @staticvar Eazyest_Gallery $instance The single Eazyest Gallery object in memory
	 * @since 0.1.0 (r2)
	 */
	private static $instance;

	/**
	 * Eazyest_Gallery::__construct()
	 * Empty constructor
	 * 
	 * @return void
	 */
	public function __construct() {}	
	
	/**
	 * @since 0.1.0 (r2)
	 */
	public function __clone() { wp_die( __( 'Cheatin&#8217; huh?', 'eazyest-gallery' ) ); }

	/**
	 * 
	 * @since 0.1.0 (r2)
	 */
	public function __wakeup() { wp_die( __( 'Cheatin&#8217; huh?', 'eazyest-gallery' ) ); }

	/**
	 * Magic method for checking the existence of a certain custom field
	 *
	 * @since 0.1.0 (r2)
	 */
	public function __isset( $key ) { 
		return isset( $this->data[$key] ); 
	}

	/**
	 * Magic method for getting Eazyest_Gallery variables
	 *
	 * @since 0.1.0 (r2)
	 */
	public function __get( $key ) { 
		return isset( $this->data[$key] ) ? $this->data[$key] : null; 
	}

	/**
	 * Magic method for setting Eazyest_Gallery variables
	 *
	 * @since 0.1.0 (r2)
	 */
	public function __set( $key, $value ) { 
		$this->data[$key] = $value; 
	}
	
	/**
	 * Eazyest_Gallery::init()
	 * Initialize everything
	 * 
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	private function init() {
		$this->load_text_domain();
		$this->load_options();		
		$this->setup_variables();
		$this->includes();	
		$this->set_gallery_folder();	
		$this->actions();    
		$this->filters();
	}

	/**
	 * Eazyest_Gallery::instance()
	 * Eazyest Gallery should be loaded only once
	 * 
	 * @since 0.1.0 (r2)
	 * @uses load_plugin_textdomain()
	 * @uses plugin_basename()
	 * @uses do_action()
	 * @return object Eazyest_Gallery
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Eazyest_Gallery;
			self::$instance->init();
		}
		return self::$instance;
	}
	
	/**
	 * Eazyest_Gallery::setup_variables()
	 * Setup Class variables
	 * 
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	function setup_variables() {		
		$this->plugin_url      = plugin_dir_url( __FILE__ );
		$this->plugin_dir      = plugin_dir_path( __FILE__ );
		$this->plugin_file     = __FILE__;
		$this->plugin_basename = plugin_basename( __FILE__ );		
		$this->post_type       = apply_filters( 'eazyest_gallery_post_type', 'galleryfolder' );
	}
	
	/**
	 * Eazyest_Gallery::load_text_domain()
	 * 
	 * @uses load_plugin_textdomain
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	function load_text_domain() {
		load_plugin_textdomain( 'eazyest-gallery', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );		
	}
	
	/**
	 * Eazyest_Gallery::load_options()
	 * Load the options array
	 * Assign defaults if not found
	 * 
	 * @since 0.1.0 (r2)
	 * @uses get_option()
	 * @uses add_option()
	 * @return void
	 */
	private function load_options() {
		// Eazyest Gallery is basically an upgrade for Lazyest Gallery
		$options = get_option( 'lazyest-gallery' );
		if ( false === $options ) 
			$options = get_option( 'eazyest-gallery' );
		
		if ( false === $options ) { 
			// options not in the wpdb, probably new install
			$options = $this->defaults();
			
			//set options to default
			add_option( 'eazyest-gallery', $options ); 
			$this->options = get_option( 'eazyest-gallery' );		
		} else {
			$this->options = $options;	
		}	
	}
	
	/**
	 * Eazyest_Gallery::includes()
	 * Load files that should always be included
	 * 
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	private function includes() {		
		include( $this->plugin_dir . 'includes/class-eazyest-folderbase.php'   );
		include( $this->plugin_dir . 'includes/class-eazyest-extra-fields.php' );	
		include( $this->plugin_dir . 'includes/widgets.php'                    );		
	} 
	
	/**
	 * Eazyest_Gallery::actions()
	 * hook WordPress actions
	 * 
	 * @since 0.1.0 (r2)
	 * @uses add_action()
	 * @return void
	 */
	function actions() {
		// WordPress actions
		add_action( 'init'                         , array( $this, 'initialized'  ), 10 );
		add_action( 'activate_'   . $this->basename, array( $this, 'activation'   )     );
		add_action( 'deactivate_' . $this->basename, array( $this, 'deactivation' )     );
		
		// Eazyest Gallery initialization actions
		
		add_action(   'eazyest_gallery_init', 'eazyest_folderbase',   8 );
		add_action(   'eazyest_gallery_init', 'eazyest_extra_fields', 9 );
				
		if ( is_admin() )	{
			include( $this->plugin_dir . 'admin/class-eazyest-admin.php' );
			add_action( 'eazyest_gallery_init', 'eazyest_admin', 10 );
		}
		else {
			include( $this->plugin_dir . 'frontend/class-eazyest-frontend.php' );
		  add_action( 'eazyest_gallery_init', 'eazyest_frontend',  10 );
		}
		add_action( 'eazyest_gallery_init', 'eazyest_widgets',                        11 ); 
		
		add_action( 'eazyest_gallery_init', array( $this, 'plugins'               ),  50 );											
		add_action( 'eazyest_gallery_init', array( $this, 'eazyest_gallery_ready' ), 999 );
	}
	
	function plugins() {
		if ( $this->get_option( 'enable_exif' ) ) {
			include( $this->plugin_dir . '/plugins/class-eazyest-gallery-exif.php' );
			eazyest_gallery_exif();
		}
	}
	
	/**
	 * Eazyest_Gallery::initialized()
	 * Do Eazyest Gallery init action
	 * 
	 * @since 0.1.0 (r2)
	 * @uses do_action()
	 * @return void
	 */
	function initialized() {
		do_action( 'eazyest_gallery_init' );
	}
	
	/**
	 * Eazyest_Gallery::activation()
	 * Do Eazyest Gallery Activation action
	 * 
	 * @since 0.1.0 (r2)
	 * @uses do_action()
	 * @return void
	 */
	function activation() {
		do_action( 'eazyest_gallery_activation' );
		flush_rewrite_rules();
	}
	
	/**
	 * Eazyest_Gallery::deactivation()
	 * Do Eazyest Gallery Activation action
	 * 
	 * @since 0.1.0 (r2)
	 * @uses do_action()
	 * @return void
	 */
	function deactivation() {
		do_action( 'eazyest_gallery_deactivation' );
	}

	/**
	 * Eazyest_Gallery::eazyest_gallery_ready()
	 * Creates a hook for plugin builders to do something after Eazyest Gallery has loaded
	 * 
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	function eazyest_gallery_ready() {
		do_action( 'eazyest_gallery_ready' ); 
	}
	
	/**
	 * Eazyest_Gallery::filters()
	 * hook WordPress filters
	 * 
	 * @since lazyest-gallery 1.2.0
	 * @uses register_activation_hook()
	 * @uses add_action()
	 * @uses add_filter()
	 * @return void
	 */
	function filters() {
	}
	
	/**
	 * Eazyest_Gallery::gallery_slug()
	 * 
	 * @return string
	 */
	function gallery_slug() {
		return $this->get_option( 'gallery_slug' );
	}
	
	/**
	 * Eazyest_Gallery::gallery_name()
	 * Filter:
	 * <code>'eazyest_gallery_menu_name'</code>
	 * 
	 * @return string
	 */
	function gallery_name() {		
		return apply_filters( 'eazyest_gallery_menu_name', __( 'Eazyest Gallery', 'layzest-gallery' ) );
	}
	
	/**
	 * Eazyest_Gallery::gallery_title()
	 * Returns the Gallery Title 
	 * Filter:
	 * <code>'eazyest_gallery_title'</code>
	 * 
	 * @since 0.1.0 (r2)
	 * @return string
	 */
	function gallery_title() {
		$title = $this->get_option( 'gallery_title' );
		return empty( $title ) ? apply_filters( 'eazyest_gallery_title', __( 'Gallery', 'eazyest-gallery' ) ) : $title;
	}	
	
	/**
	 * Eazyest_Gallery::set_gallery_folder()
	 * Set the root directory for the gallery
	 * 
	 * @since 0.1.0 (r2)
	 * @uses trailingslashit()
	 * @uses get_option()
	 * @return void
	 */
	private function set_gallery_folder() {
		$gallery_folder = $this->get_option( 'gallery_folder' );
  	$this->root = str_replace( '\\', '/', trailingslashit( $this->get_absolute_path( ABSPATH . $gallery_folder ) ) );
  	$this->address = trailingslashit( $this->_resolve_href( trailingslashit( get_option( 'siteurl' ) ), $gallery_folder ) ) ;
	}
	
	/**
	 * Eazyest_Gallery::root()
	 * 
	 * @since lazyest-gallery 1.1.0
	 * @return path to gallery root with all forward slashes and trailing slash
	 */
	public function root() {
		$this->set_gallery_folder();
		return $this->root;
	}
	
	public function address() {
		return $this->address;
	}
	
	function right_path() {
		return file_exists( $this->root );
	}

	/**
	 * Eazyest_Gallery::_resolve_href()
	 * Resolves a relative url
	 * 
	 * @since lazyest-gallery 1.1.0
	 * @param string $base
	 * @param string $href
	 * @return string resolved url
	 */
	private function _resolve_href( $base, $href ) {
		if ( ! $href ) {
			return $base;
		}			
    $href = str_replace( '\\', '/', $href );
		$rel_parsed = parse_url( $href );
		if ( array_key_exists( 'scheme', $rel_parsed ) ) {
			return $href;
		}
		$base_parsed = parse_url( "$base " );
		if ( ! array_key_exists( 'path', $base_parsed ) ) {
			$base_parsed = parse_url( "$base/ " );
		}
		if ( $href{0} === "/" ) {
			$path = $href;
		} else {
			$path = str_replace( '\\', '/', dirname($base_parsed['path']) ) . "/$href";
		}
		$path = preg_replace( '~/\./~', '/', $path );
		$parts = array();
		foreach ( explode( '/', preg_replace( '~/+~', '/', $path ) ) as $part )
			if ( $part === ".." ) {
				array_pop( $parts );
			} elseif ( $part != "" ) {
				$parts[] = $part;
			} 
		$port = isset( $base_parsed['port'] ) ? ':' . $base_parsed['port'] : ''; 

		return ( ( array_key_exists( 'scheme', $base_parsed ) ) ? $base_parsed['scheme'] . '://' . $base_parsed['host']:"" ) . "/" . implode( "/", $parts );
	}
	
	/**
	 * Eazyest_Gallery::common_root()
	 * Get lowest comon root for upload dir and ABSPATH
	 * 
	 * @since 0.1.0 (r2)
	 * @uses wp_upload_dir()
	 * @return string path
	 */
	function common_root() {
		$root = str_replace( array( '/', '\\'), '/', ABSPATH );
		$upload = wp_upload_dir();
		$abspath_dirs = explode( '/', $root );
		$uploads_dirs = explode( '/', str_replace( array( '/', '\\'), '/', $upload['basedir'] ) );
		$dir = 0;
		$root_dirs = array();
		while( $uploads_dirs[$dir] == $abspath_dirs[$dir] ) {
			$root_dirs[] = $uploads_dirs[$dir];
			$dir++;
		}	
		$root = ! empty( $root_dirs ) ? implode( '/', $root_dirs ) . '/' : $root;
		return $root;	 
	}

	/**
	 * Eazyest_Gallery::valid()
	 * Check if the gallery root directory is set, andd if it exists
	 * 
	 * @return bool
	 */
	function valid() {
		return isset( $this->root ) && file_exists( $this->root );
	}
	
	/**
	 * Eazyest_Gallery::version()
	 * The version is only defined in the plugin header
	 * 
	 * @since 0.1.0 (r2)
	 * @return string Current version
	 */
	function version() {
	  require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	  $plugin_data = get_plugin_data( $this->plugin_file );
	  return $plugin_data['Version'];
	}

	/**
	 * Eazyest_Gallery::get_option()
	 * retrieves an option from the options array
	 * 
	 * @since lazyest-gallery 0.16.0
	 * @param string $option
	 * @return mixed option value or false on fail
	 * 
	 */
	function get_option( $option ) {
		return isset( $this->options[$option] ) ? $this->options[$option] : false;
	}

	/**
	 * Eazyest_Gallery::change_option()
	 * Changes an option but does not save it
	 * 
	 * @since lazyest-gallery 0.16.0
	 * @param mixed $option
	 * @param mixed $value
	 * @return void
	 */
	function change_option( $option, $value ) {
		$this->options[$option] = $value;
	}

	/**
	 * Eazyest_Gallery::update_option()
	 * Changes and saves an option
	 * 
	 * @since lazyest-gallery 0.16.0
	 * @uses update_option()
	 * @param mixed $option
	 * @param mixed $value
	 * @return void
	 */
	function update_option( $option, $value ) {
		$this->change_option( $option, $value );
		update_option( 'eazyest-gallery', $this->options );
	}

	/**
	 * Eazyest_Gallery::store_options()
	 * Saves the options to the WP DB
	 * 
	 * @since lazyest-gallery 0.16.0
	 * @uses update_option()
	 * @return void
	 */
	function store_options() {
		update_option( 'eazyest-gallery', $this->options );
	}

	/**
	 * Eazyest_Gallery::get_absolute_path()
	 * 
	 * @since lazyest-gallery 1.1.0
	 * @param mixed $path containg ../ or ./
	 * @return absolute path
	 */
	function get_absolute_path( $path ) {
		$path = str_replace( '\\', '/', $path );
		$parts = array_filter( explode( '/', $path ), 'strlen' );
		$absolutes = array();
		foreach ( $parts as $part ) {
			if ( '.' == $part )
				continue;
			if ( '..' == $part ) {
				array_pop( $absolutes );
			} else {
				$absolutes[] = $part;
			}
		}
		$absolute_path = implode( '/', $absolutes );
		if ( $path[0] == '/' ) // implode does not restore leading slash

			$absolute_path = '/' . $absolute_path;
		if ( $path[1] == '/' ) // double slash when using UNC path

			$absolute_path = '/' . $absolute_path;
		return $absolute_path;
	}

	/**
	 * Eazyest_Gallery::get_relative_path()
	 * 
	 * @since lazyest-gallery 1.1.0 
	 * @param mixed $from
	 * @param mixed $to
	 * @return string relative path
	 */
	function get_relative_path( $from, $to ) {
		$from = explode( '/', str_replace( '\\', '/', $from ) );
		$to = explode( '/', str_replace( '\\', '/', $to ) );
		$rel_path = $to;
		foreach ( $from as $depth => $dir ) {
			if ( $dir === $to[$depth] ) {
				array_shift( $rel_path );
			} else {
				$remaining = count( $from ) - $depth;
				if ( 1 < $remaining ) {
					$pad_length = ( count( $rel_path ) + $remaining - 1 ) * -1;
					$rel_path = array_pad( $rel_path, $pad_length, '..' );
					break;
				}
			}
		}
		return implode( '/', $rel_path );
	}

	/**
	 * Eazyest_Gallery::_default_dir()
	 * Sets the default gallery directory relative to <code>ABSPATH</code>
	 * Filter:
	 * <code>'eazyest_gallery_directory'</code>
	 * 
	 * @since lazyest-gallery 1.1.0
	 * @uses apply_filters()
	 * @uses wp_upload_dir()
	 * @return string;
	 */
	private function _default_dir() {
		$basedir = str_replace( '\\', '/', WP_CONTENT_DIR );
		$abspath = str_replace( '\\', '/', ABSPATH );
		$relative = $this->get_relative_path( $abspath, $basedir );
		return apply_filters( 'eazyest_gallery_directory', $relative . '/gallery/' );
	}
	
	/**
	 * Eazyest_Gallery::_default_address()
	 * Sets the default gallery address for images src url
	 * 
	 * since 1.1.9
	 * @return string;
	 */
	private function _default_address() {		
		return $this->_resolve_href( trailingslashit( get_option( 'siteurl') ), $this->_default_dir() );
	}	
  
  /**
   * Eazyest_Gallery::default_editor_capability()
   * The default capability for users to be assigned the eazyest editor role
   * 
   * @since lazyest-gallery 1.1.9
   * @uses  apply_filters()
   * @return string
   */
  private function default_editor_capability() {
  	return apply_filters( 'eazyest_editor_capability', 'edit_posts' );
  }
  
  /**
   * Eazyest_Gallery::slug_exists()
   * Check in WordPress database if slug already exists
   * Check if slug is a directory
   * 
   * @since 0.1.0 (r2)
   * @param string $slug
   * @uses wpdb
   * @return bool
   */
  private function slug_exists( $slug ) {
  	global $wpdb;
  	$results = $wpdb->get_results( $wpdb->prepare( "SELECT post_name FROM $wpdb->posts WHERE post_name = %s", $slug ) );
  	return ( ! empty( $results ) ) || is_dir( ABSPATH . $slug );
  }
  
  /**
   * Eazyest_Gallery::default_slug()
   * Set default slug and check if default slug is not already used
   * Filtered <code>apply_filters( 'eazyest_gallery_slug', 'gallery' )</code>
   * 
   * @since 0.1.0 (r2)
   * @uses apply_filters()
   * @return string
   */
  private function default_slug() {
  	$default_slug = apply_filters( 'eazyest_gallery_slug', 'gallery' );
  	$append = -1;
  	while( $this->slug_exists( $default_slug ) ) {
  		$default_slug = $default_slug . $append;
  		$append--;
  	}
  	return $default_slug;
  }

	/**
	 * Eazyest_Gallery::defaults()
	 * Default options
	 *
	 * Options used: 
	 * 'new_install'       : only used at first install, to reset settings page
	 * 'gallery_folder'    : the gallery folder, relative to ABSPATH
	 * 'gallery_title'     : Text for title element and h1 element
	 * 'show_credits'      : show "powered by Eazyest Gallery"
	 * 'folders_page'      : folders per page
	 * 'folders_columns'   : folders per row
	 * 'sort_folders'      : see 'sort_thumbnails'
	 * 'count_subfolders'  : none, include, separate, nothing
	 * 'folder_image'      : what to show per folder: 
	 *                       featured_image, first_image, random_image, icon, none
	 * 'random_subfolder'  : random folder image from subfolder
	 * 'thumbs_page'       : thumbnails per page
	 * 'thumbs_columns'    : thumbnails per row
	 * 'thumb_caption'     : show caption in thumbnail view
	 * 'sort_thumbnails'   : thumbnail sort options: 
	 *                       post_name-ASC = name ascending, post_name-DESC = name descending, 
	 *                       post_title-ASC = caption ascending, post_title-DESC = caption descending, 
	 *                       post_date-ASC = date ascending, post_date-DESC = date descending, 
	 *                       menu_order-ASC = manually
	 * 'on_thumb_click'    : nothing, attachment, medium, large, full
	 * 'thumb_popup'       : add markup for popup 'none', 'lightbox', or 'thickbox' (filtered)
	 * 'on_slide_click'    : nothing, full
	 * 'slide_popup'       : see thumb_popup
	 * 'listed_as'         : display name in thumbs view 'photos' e.g. "10 photos"
	 * 'enable_exif'       : show exif information in attachment page
	 * 'gallery_secure'    : version of last update for options or database
	 * 'viewer_level'      : minimum level to view the gallery
	 * 'gallery_slug'      : page slug for the gallery
	 * 
	 * @since lazyest-gallery 0.16.0
	 * @return array
	 */
	public function defaults() {
		return array(
			'new_install'       => true,
			// main settings
			'gallery_folder'    => $this->_default_dir(),
			'gallery_title'     => '',
			'show_credits'      => false, 
			// folder options
			'folders_page'      => 0,
			'folders_columns'   => 0,			
			'sort_folders'      => 'post_date-DESC',
			'count_subfolders'  => 'none',
			'folder_image'      => 'first_image',
			'random_subfolder'  => false, 
			// image options
			'thumbs_page'       => 0,
			'thumbs_columns'    => 0,
			'thumb_caption'     => true,
			'sort_thumbnails'   => 'post_date-DESC',
			'on_thumb_click'    => 'large',
			'thumb_popup'       => 'none', 
			'on_slide_click'    => 'full',
			'slide_popup'       => 'none',
			'listed_as'         => 'photos',
			'enable_exif'       => false, 
			// advanced options
			'gallery_secure'    => '0.0.0',
			'viewer_level'      => 'everyone',
			'gallery_slug'      => $this->default_slug()
		);		
	}
	
	/**
	 * Eazyest_Gallery::sort_by()
	 * Return the option to sort $item ( 'folders' or 'thumbnails' )
	 * 
	 * @since 0.1.0 (r2)
	 * @param string $item
	 * @return string
	 */
	function sort_by( $item = 'folders' ) {
		$sort_by = $this->get_option( "sort_{$item}" ); 
		return false != $sort_by ? $sort_by : 'post_date-DESC';
	}
} // Eazyest_Gallery class

/**
 * eazyest_gallery()
 * 
 * @return The Eazyest_Gallery instance currently in memory
 */
function eazyest_gallery() {
	return Eazyest_Gallery::instance();
}

// and now let's get the ball rolling
list( $current_wp ) = explode( '-', $GLOBALS['wp_version'] );
if ( version_compare( $current_wp, '3.5', '<') ) {
 require_once ABSPATH.'/wp-admin/includes/plugin.php';
		deactivate_plugins( __FILE__ );
  	wp_die( __( 'Eazyest Gallery requires WordPress 3.5 or higher. The plugin has now disabled itself.', 'eazyest-gallery' ) );
} else {
 eazyest_gallery();
}