<?php  
/** 
 * Eazyest Gallery is easy gallery management software for WordPress.
 * 
 * Plugin Name: Eazyest Gallery
 * Plugin URI: http://brimosoft.nl/eazyest/gallery/
 * Description: Easiest Gallery management plugin for Wordpress
 * Date: April 2013
 * Author: Brimosoft
 * Author URI: http://brimosoft.nl
 * Version: 0.2.0-alpha-327
 * Text Domain: eazyest-gallery
 * Domain Path: /languages/
 * License: GNU General Public License, version 3
 *
 * @version 0.2.0 (r327)  
 * @package Eazyest Gallery
 * @subpackage Main
 * @link http://brimosoft.nl/eazyest/gallery/
 * @author Marcel Brinkkemper <eazyest@brimosoft.nl>, Andredewaard
 * @copyright 2013 Marcel Brinkkemper
 * @license GNU General Public License, version 3
 * @license http://www.gnu.org/licenses/
 * 
 * @uses TableDnD plug-in for JQuery,
 * @copyright (c) Denis Howlett396
 * 
 * @uses JQuery File Tree, 
 * @copyright (c) 2008, A Beautiful Site, LLC
 * 
 * @uses Camera slideshow v1.3.3,
 * @copyright (c) 2012 by Manuel Masia - www.pixedelic.com
 * 
 * @uses Jigsoar icons, Handcrafted by Benjamin Humphrey for Jigsoar
 * @copyright www.jigsoaricons.com
 * 
 * In this plugin source code, phpDoc @uses refers to WordPress functions for compatibility checks
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
 * @version 0.2.0 (r327)
 * @access public
 */
class Eazyest_Gallery {
	
	/**
	 * @var array $data overloaded variables
	 */ 
	private $data;
	
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
		eazyest_widgets();
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
	 * @access private
	 * @uses get_option()
	 * @uses add_option()
	 * @return void
	 */
	private function load_options() {
		
		$options = get_option( 'eazyest-gallery' );
		
		if ( false === $options ) { 
			// options not in the wpdb, probably new install
			$options = $this->defaults();
			
			//set options to default
			add_option( 'eazyest-gallery', $options ); 		
		}
		$this->data = $options;
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
		$basename = $this->plugin_basename;	
		add_action( 'init',                 array( $this, 'initialized'        ) );
		add_action( "activate_$basename",   array( $this, 'activation'         ) );
		add_action( "deactivate_$basename", array( $this, 'deactivation'       ) );
		add_action( 'activated_plugin',     array( $this, 'deactivate_lazyest' ) );
		
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
		
		add_action( 'eazyest_gallery_init', array( $this, 'plugins'               ),  50 );											
		add_action( 'eazyest_gallery_init', array( $this, 'eazyest_gallery_ready' ), 999 );
	}
	
	/**
	 * Eazyest_Gallery::plugins()
	 * Include files with additional functionality.
	 * 
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	function plugins() {
		include( $this->plugin_dir . '/plugins/class-eazyest-gallery-exif.php' );
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
	 * @uses do_action( 'eazyest_gallery_activation' )
	 * @uses set_transient() to save activated state
	 * @uses flush_rewrite_rules()
	 * @return void
	 */
	function activation() {
		do_action( 'eazyest_gallery_activation' );
		eazyest_folderbase()->register_post_types();		
		flush_rewrite_rules();
		set_transient( 'eazyest-gallery-activated', true );
	}
	
	/**
	 * Eazyest_Gallery::deactivate_lazyest()
	 * Deactivate lazyest plugins except lazyest-stylesheet because they are not compatible with eazyest-gallery and will break the blog.
	 * 
	 * @since 0.1.0 (r37)
	 * @uses get_option()
	 * @uses deactivate_plugins()
	 * @param string $plugin plugin basename
	 * @return void
	 */
	function deactivate_lazyest( $plugin ) {
		if ( $plugin != $this->plugin_basename )
			return;
			
		$deactivates = array();
		if ( $active_plugins = get_option( 'active_plugins' ) ) {
			foreach( (array) $active_plugins as $plugin ) {
				if ( ( false !== strpos( $plugin, 'lazyest' ) ) && ( false === strpos( $plugin, 'stylesheet' ) ) )
					$deactivates[] = $plugin;
			}		
			if ( ! empty( $deactivates ) )
				deactivate_plugins( $deactivates );
		}	
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
	 * @return void
	 */
	function filters() {}
	
	/**
	 * Eazyest_Gallery::gallery_slug()
	 * 
	 * @return string
	 */
	function gallery_slug() {
		return $this->gallery_slug;
	}
	
	/**
	 * Eazyest_Gallery::gallery_name()
	 * Filter:
	 * <code>'eazyest_gallery_menu_name'</code>
	 * 
	 * @return string
	 */
	function gallery_name() {		
		return apply_filters( 'eazyest_gallery_menu_name', __( 'Eazyest Gallery', 'eazyest-gallery' ) );
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
		$title = $this->gallery_title;
		return empty( $title ) ? apply_filters( 'eazyest_gallery_title', __( 'Gallery', 'eazyest-gallery' ) ) : $title;
	}	
	
	/**
	 * Eazyest_Gallery::set_gallery_folder()
	 * Set the root directory for the gallery.
	 * Replace backward slashes in directories by forward slashes.
	 * 
	 * @since 0.1.0 (r2)
	 * @uses trailingslashit()
	 * @uses get_option()
	 * @return void
	 */
	private function set_gallery_folder() {
		$gallery_folder = $this->gallery_folder;
		$this->root = str_replace( '\\', '/', trailingslashit( $this->get_absolute_path( ABSPATH . $gallery_folder ) ) );

		$uploads = wp_upload_dir();
		$basedir = $uploads['basedir'];
		$this->relative_root = $this->get_relative_path( $basedir, $this->root );

	 	$http = empty( $_SERVER['HTTPS'] ) || 'off' == $_SERVER['HTTPS'] ? 'http://' : 'https://';
		$port = ( $_SERVER["SERVER_PORT"] == '80' ) ? '' : ( ':' . $_SERVER['SERVER_PORT'] );

		$this->address = trailingslashit( $this->_resolve_href( trailingslashit( $http . $_SERVER['HTTP_HOST'] . $port ), substr( $this->root, strlen( $this->home_dir() ) ) ) );
	}

	/**
	 * Eazyest_Gallery::root()
	 * path to gallery root.
	 *
	 * @since lazyest-gallery 1.1.0
	 * @param bool $relative
	 * @return string
	 */
	public function root( $relative = false ) {
		if ( ! isset( $this->relative_root ) )
			$this->set_gallery_folder();
		return $relative ? $this->relative_root : $this->root;
	}

	
	/**
	 * Eazyest_Gallery::address()
	 * eazyest gallery base url.
	 * 
	 * @since 0.1.0 (r2)
	 * @return string
	 */
	public function address() {
		if ( ! isset( $this->address ) )
			$this->set_gallery_folder();
		return $this->address;
	}
	
	/**
	 * Eazyest_Gallery::right_path()
	 * Check if gallery root exists.
	 * 
	 * @since 0.1.0 (r2)
	 * @return bool
	 */
	function right_path() {
		return ! empty( $this->root ) && '/' != $this->root && is_dir( $this->root );
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
	 * Eazyest_Gallery::home_dir()
	 * Try to find the home directory for the website.
	 * 
	 * @since 0.1.0 (r231)
	 * @uses home_url()
	 * @uses site_url()
	 * @return string file system path for home directory
	 */
	function home_dir() {		
		if ( isset( $_SERVER['DOCUMENT_ROOT'] ) )
			return trailingslashit( str_replace( array( '/', '\\'), '/', realpath( $_SERVER['DOCUMENT_ROOT'] ) ) );
		
		// document root is not set, try to find root from settings
		$root = str_replace( array( '/', '\\'), '/', ABSPATH );
		$url_parts = parse_url( home_url() );
		$home_parts = array();
		if ( isset( $url_parts['path'] ) )
			$home_parts = array_reverse( explode( '/', ( ltrim( $url_parts['path'], '/' ) ) ) );
		
		$site_parts = array();	
		$url_parts = parse_url( site_url() );
		if ( isset( $url_parts['path'] ) )	
			$site_parts = array_reverse( explode( '/', ( ltrim( $url_parts['path'], '/' ) ) ) );
			
		$root_parts = array_reverse( explode( '/', rtrim( $root, '/' ) ) );
		while( isset( $root_parts[0] ) && isset( $site_parts[0] ) && $root_parts[0] == $site_parts[0] && $site_parts[0] != $home_parts[0] ) {
			array_shift( $root_parts );
			array_shift( $site_parts );	
		}
		$home = implode( '/', array_reverse( $root_parts ) ) . '/';
		return $home;
	}
	
	/**
	 * Eazyest_Gallery::valid()
	 * Check if the gallery root directory is set, andd if it exists
	 * 
	 * @return bool
	 */
	function valid() {
		return isset( $this->root ) && is_dir( $this->root );
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
		$uploads = wp_upload_dir();
		$basedir = $uploads['basedir'];
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
	 * 'sort_folders'      : dfolder sort options:  
	 *                       post_name-ASC = name ascending, post_name-DESC = name descending, 
	 *                       post_title-ASC = caption ascending, post_title-DESC = caption descending, 
	 *                       post_date-ASC = date ascending, post_date-DESC = date descending, 
	 *                       menu_order-ASC = manually
	 * 'count_subfolders'  : none, include, separate, nothing
	 * 'folder_image'      : what to show per folder: 
	 *                       featured_image, first_image, random_image, icon, none
	 * 'random_subfolder'  : random folder image from subfolder
	 * 'thumbs_page'       : thumbnails per page
	 * 'thumbs_columns'    : thumbnails per row
	 * 'thumb_caption'     : show caption in thumbnail view
	 * 'sort_thumbnails'   : thumbnail sort options: 
	 *                       post_name-ASC = name ascending, post_name-DESC = name descending, 
	 *                       post_excerpt-ASC = caption ascending, post_excerpt-DESC = caption descending, 
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
			'folders_page'      => 30,
			'folders_columns'   => 6,			
			'sort_folders'      => 'post_date-DESC',
			'count_subfolders'  => 'none',
			'folder_image'      => 'first_image',
			'random_subfolder'  => false, 
			// image options
			'thumbs_page'       => 40,
			'thumbs_columns'    => 4,
			'thumb_caption'     => true,
			'sort_thumbnails'   => 'post_date-DESC',
			'on_thumb_click'    => 'attachment',
			'thumb_popup'       => 'none', 
			'on_slide_click'    => 'default',
			'slide_popup'       => 'none',
			'listed_as'         => 'photos',
			'enable_exif'       => false, 
			// advanced options
			'gallery_secure'    => EZG_SECURE_VERSION,
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
		$sorter = "sort_{$item}";
		$sort_by = $this->{$sorter}; 
		return false != $sort_by ? $sort_by : 'post_date-DESC';
	}
} // Eazyest_Gallery class

/**
 * uninstall_eazyest_gallery()
 * Removes the eazyest-gallery option from the options table and flushes the rewrite rules.
 * 
 * @since 0.1.0 (r275)
 * @uses delete_option()
 * @uses flush_rewrite_rules()
 * @return void
 */
function uninstall_eazyest_gallery() {
	
	$post_type = apply_filters( 'eazyest_gallery_post_type', 'galleryfolder' );
	global $wpdb;	
	$folders = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = %s", $post_type ), ARRAY_A );
	// folders exist, delete them from wpdb and handLe attachments
	if ( ! empty( $folders ) ) {
		// save ids to handle attachments
		$ids = array();
		foreach( $folders as $folder )
			$ids[] = $folder['ID'];
		$folder_ids = implode( ',', $ids );
		// remove folders
		$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->posts WHERE post_type = %s", $post_type ) );
		// remove folders metadata
		$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE post_id IN ($folder_ids)" );
		// get folders attachments	
		$attachments = $wpdb->get_results( "SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND post_parent IN ($folder_ids)", ARRAY_A );
		if ( ! empty( $attachments ) ) {
			$ids = array();
			foreach( $attachments as $attachment )
				$ids[] = $attachment['ID'];
			$attachment_ids = implode( ',', array_values( $ids ) );			
			// remove attachment metadata		 		
			$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE post_id IN ($attachment_ids)" );			
			// remove attachments			
			$wpdb->query( "DELETE FROM $wpdb->posts WHERE post_type = 'attachment' AND post_parent IN ($folder_ids)" );
		}
	}	
	delete_option( 'eazyest-gallery'             );
	delete_option( 'eazyest-fields'              );
	delete_option( 'eazyest-enable-extra-fields' );
	
	flush_rewrite_rules();	
}
register_uninstall_hook( __FILE__, 'uninstall_eazyest_gallery' );

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

/*--------------------------
 * Apply tags to attachments.
 * Added by andredewaard props to klihelp.
 * @since 0.1.4
 */

function add_tags_to_attachments() {
	register_taxonomy_for_object_type( 'post_tag', 'attachment' );
}
add_action( 'init' , 'add_tags_to_attachments' );

