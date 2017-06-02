<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Eazyest_Admin
 * This class contains all functions and actions required for Eazyest Gallery to work in the WordPress admin.
 *
 * @package Eazyest Gallery
 * @subpackage Admin
 * @author Marcel Brinkkemper
 * @copyright 2010-2013 Brimosoft
 * @version 0.1.0 (r277)
 * @access public
 * @since lazyest-gallery 0.16.0
 *
 */
class Eazyest_Admin {

	/**
	 * @var array $data overloaded vars
	 * @access private
	 */
	private $data;

	/**
	 * @staticvar Eazyest_Admin $instance single object in memory
	 */
	private static $instance;

  /**
   * Eazyest_Admin::__construct()
   *
   * @return void
   */
  function __construct() {}

	/**
	 * Eazyest_Admin::__isset()
	 *
	 * @param mixed $key
	 * @return bool
	 */
	public function __isset( $key ) {
		return isset( $this->data[$key] );
	}

	/**
	 * Eazyest_Admin::__get()
	 *
	 * @param mixed $key
	 * @return mixed
	 */
	public function __get( $key ) {
		return isset( $this->data[$key] ) ? $this->data[$key] : null;
	}

	/**
	 * Eazyest_Admin::__set()
	 *
	 * @param mixed $key
	 * @param mixed $value
	 * @return void
	 */
	public function __set( $key, $value ) {
		$this->data[$key] = $value;
	}

	/**
	 * Eazyest_Admin::init()
	 * Initialize
	 *
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	private function init() {
		$this->setup_variables();
		$this->includes();
		$this->actions();
		$this->filters();
	}

	/**
	 * Eazyest_Admin::instance()
	 * Eazyest Admin should be loaded once
	 *
	 * @since 0.1.0 (r2)
	 * @return Eazyest_Admin object
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Eazyest_Admin;
			self::$instance->init();
		}
		return self::$instance;
	}

  /**
   * Eazyest_Admin::setup_variables()
   *
	 * @since 0.1.0 (r2)
   * @return void
   */
  function setup_variables() {
  	if ( defined( 'DOING_AJAX' ) ) {
  		$this->ajax();
		}
		$this->folder_editor();
  }

  /**
   * Eazyest_Admin::includes()
   * Include files.
   *
   * @since 0.1.0 (r2)
   * @return void
   */
  function includes() {
  	// tools
  	include( eazyest_gallery()->plugin_dir . 'tools/class-eazyest-upgrader.php' );
  }

  /**
   * Eazyest_Admin::actions()
   * add WordPress actions
   *
   * @since 0.1.0 (r2)
   * @uses add_action()
   * @return void
   */
  function actions() {
  	add_action( 'admin_init',   array( $this, 'after_activation' ) );
  	add_action( 'admin_init',   array( $this, 'register_setting' ) );
  	add_action( 'admin_menu',   array( $this, 'admin_menu'       ) );
  	add_action( 'admin_head',   array( $this, 'admin_head'       ) );

  	// delete attachments in footer to speed up page load
  	if ( $transient = get_transient( 'eazyest_gallery_delete_attachments' ) )
  		add_action( 'admin_footer', array( $this, 'delete_attachments' ) );

 		// add attachments in footer to speed up page load
		add_action( 'admin_footer', array( $this, 'add_attachments' ) );
  }

  /**
   * Eazyest_Admin::filters()
   * add WordPress filters.
   *
   * @since 0.1.0 (r79)
   * @uses add_filter()
   * @return void
   */
  function filters() {
    add_filter( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );
  }

  /**
   * Eazyest_Admin::after_activation()
   * Redirect users to about screen after activation.
   *
   * @since 0.1.0 (r74)
   * @uses delete_transient()
   * @uses wp_redirect()
	 * @uses admin_url()
	 * @uses get_option() to check if about page has to show
   * @return void
   */
  function after_activation() {
  	if ( $activated = get_transient( 'eazyest-gallery-activated' ) ) {
  		delete_transient( 'eazyest-gallery-activated' );
  		if ( ! eazyest_gallery_upgrader()->should_upgrade() ) {

  			if ( ! get_option( 'eazyest-gallery-about' ) )
  				return;

 				wp_redirect( admin_url( 'index.php?page=eazyest-gallery-about' ) );
  			exit;
			}
  	}
  }

  /**
   * Eazyest_Admin::register_setting()
   * Register eazyest-gallery setting for options pagee
   *
   * @since 0.1.0 (r2)
   * @uses register_setting()
   * @return void
   */
  function register_setting() {
  	register_setting( 'eazyest-gallery', 'eazyest-gallery', array( $this, 'sanitize_settings' ) );
  }

  /**
   * Eazyest_Admin::sanitize_settings()
   * Sanitize options after before saving to wpdb
   *
   * @since 0.1.0 (r2)
   * @param array $options
   * @return array sanitized $options
   */
  function sanitize_settings( $options ) {
  	$defaults = eazyest_gallery()->defaults();
  	// eazyest gallery cannot work if gallery folder does not exist
  	$options['gallery_folder'] = str_replace( '\\', '/', $options['gallery_folder'] );

  	if ( isset( $options['new_install'] ) && $options['new_install'] ) {
  		$old_options = get_option( 'eazyest-gallery' );
  		$old_options['gallery_folder'] = $options['gallery_folder'];
  		$old_options['gallery_title']  = $options['gallery_title'];
			$old_options['show_credits']   = isset( $options['show_credits'] ) ? $options['show_credits'] : false;
			unset( $old_options['new_install'] );
			$options = $old_options;
  	}

		$gallery_folder = eazyest_gallery()->get_absolute_path(  ABSPATH . $options['gallery_folder'] );
  	if ( eazyest_folderbase()->is_dangerous( $gallery_folder ) ) {
			$options['new_install']    = true;
  		$options['gallery_folder'] = $defaults['gallery_folder'];
			add_settings_error( __( 'eazyest-gallery', 'eazyest-gallery' ), 'gallery_folder', __( 'The folder you have selected cannot be used for a gallery', 'eazyest-gallery'), 'error' );
		}

		// if gallery folder does not exist, user should visit settings page again
		if ( ! is_dir( eazyest_gallery()->get_absolute_path( ABSPATH . $options['gallery_folder'] ) ) ) {
			$options['new_install']    = true;
  		$options['gallery_folder'] = $defaults['gallery_folder'];
			add_settings_error( __( 'eazyest-gallery', 'eazyest-gallery' ), 'gallery_folder', __( 'The folder you have selected does not exist', 'eazyest-gallery'), 'error' );
		}

		// other fields to sanitize
		foreach ( $defaults as $setting => $value ) {
			switch( $setting ) {
				case 'folders_page' :
				case 'folders_columns' :
				case 'thumbs_page' :
				case 'thumbs_columns' :
					$options[$setting] = absint( $options[$setting] );
					break;
				case 'listed_as' :
					$options[$setting]	= esc_html( $options[$setting] );
					break;
				case 'gallery_slug' :
					$options[$setting]	= sanitize_title( $options[$setting] );
					eazyest_gallery()->galleryfolder = $options['gallery_folder'];
					if ( is_dir( eazyest_gallery()->home_dir() . $options[$setting] ) ) {
						$options[$setting] = eazyest_gallery()->gallery_slug;
						add_settings_error( __( 'eazyest-gallery', 'eazyest-gallery' ), 'gallery_slug', __( 'The slug you selected cannot be used', 'eazyest-galery' ), 'error' );
					}

					if ( $options[$setting] != eazyest_gallery()->gallery_slug ) {
						set_transient( 'eazyest-gallery-flush-rewrite-rules', true, 0 );
					}
					break;
				case 'new_install' :
				case 'show_credits' :
				case 'random_subfolder' :
				case 'thumb_caption' :
				case 'thumb_description' :
				case 'enable_exif' :
					$options[$setting] = ! empty( $options[$setting] ) ? true : false;
					break;
				default :
					$options[$setting] = ! empty( $options[$setting] ) ? $options[$setting] : $value;
			}
		}
		if ( ( ! $options['thumb_caption']) )
			$options['thumb_description'] = false;
  	return $options;
  }

  /**
   * Eazyest_Admin::admin_menu()
   * The Eazyest Gallery menu pages
   *
   * @since 0.1.0 (r2)
   * @uses add_options_page()
   * @uses add_management_page()
   * @uses add_dashboard_page()
   * @return void
   */
  function admin_menu() {
  	$settings = add_options_page(
  		__( 'Eazyest Gallery Settings', 'eazyest-gallery' ),
  		eazyest_gallery()->gallery_name(),
  		'manage_options',
  		'eazyest-gallery',
  		array( $this->settings_page(), 'display' )
		);
		add_action( "load-$settings", array( $this->settings_page(), 'add_help_tabs' ) );

		add_management_page(
  		__( 'Eazyest Gallery Tools', 'eazyest-gallery' ),
  		eazyest_gallery()->gallery_name(),
  		'manage_options',
  		'eazyest-gallery-tools',
  		array( $this->tools_page(), 'display' )
		);

		add_dashboard_page(
			__( 'About Eazyest Gallery', 'eazyest-gallery' ),
  		eazyest_gallery()->gallery_name(),
			'read',
			'eazyest-gallery-about',
			array( $this->about_page(), 'about' )
		);


		add_dashboard_page(
			__( 'About Eazyest Gallery', 'eazyest-gallery' ),
  		eazyest_gallery()->gallery_name(),
			'read',
			'eazyest-gallery-credits',
			array( $this->about_page(), 'credits' )
		);
  }

  /**
   * Eazyest_Admin::admin_head()
   * Remove about pages from the menu and add menu styles for galleryfolder post type.
   *
   * @since 0.1.0 (r103)
   * @uses remove_submenu_page()
   * @return void
   */
  function admin_head() {
		remove_submenu_page( 'index.php', 'eazyest-gallery-about' );
		remove_submenu_page( 'index.php', 'eazyest-gallery-credits' );
		$this->admin_style();
  }

  function admin_style() {
  	?>
  	<style>
			#menu-posts-<?php echo eazyest_gallery()->post_type; ?> .wp-menu-image {
				background: url('<?php echo  eazyest_gallery()->plugin_url ?>admin/images/icon-adminmenu16-sprite.png') no-repeat 6px 6px !important;
    	}
			#menu-posts-<?php echo eazyest_gallery()->post_type; ?>:hover .wp-menu-image, #menu-posts-<?php echo eazyest_gallery()->post_type; ?>.wp-has-current-submenu .wp-menu-image {
				background-position: 6px -26px !important;
			}
			.icon32-posts-<?php echo eazyest_gallery()->post_type; ?> {
				background: url('<?php echo  eazyest_gallery()->plugin_url ?>admin/images/icon-adminpage32.png') no-repeat left top !important;
			}
			@media
			only screen and (-webkit-min-device-pixel-ratio: 1.5),
			only screen and (   min--moz-device-pixel-ratio: 1.5),
			only screen and (     -o-min-device-pixel-ratio: 3/2),
			only screen and (        min-device-pixel-ratio: 1.5),
			only screen and (        		 min-resolution: 1.5dppx) {

				#menu-posts-<?php echo eazyest_gallery()->post_type; ?> .wp-menu-image {
				background-image: url('<?php echo eazyest_gallery()->plugin_url ?>admin/images/icon-adminmenu16-sprite_2x.png') !important;
				-webkit-background-size: 16px 48px;
				-moz-background-size: 16px 48px;
				background-size: 16px 48px;
			}
			.icon32-posts-<?php echo eazyest_gallery()->post_type; ?> {
				background-image: url('<?php echo  eazyest_gallery()->plugin_url ?>admin/images/icon-adminpage32_2x.png') !important;
				-webkit-background-size: 32px 32px;
				-moz-background-size: 32px 32px;
				background-size: 32px 32px;
			}
		}
		</style>
  	<?php
  }

  /**
   * Eazyest_Admin::delete_attachments()
   * Remove leftover attachments that were not deleted because of out of execution time error prevention.
   *
   * @since 0.1.0 (r159)
   * @uses get_transient() to get IDs of attachments to be deleted
   * @uses wp_delete_attachment()
   * @uses set_transient() if not all attachments have been deleted yet
   * @uses delete_transient() if all attachments have been removed
   * @return void
   */
  function delete_attachments() {
  	/** remove attachments where images have been removed from server max_process_items per page load to prevent maximun execution time error */
  	if ( $transient = get_transient( 'eazyest_gallery_delete_attachments' ) ) {
  		$count = 0;
  		while ( ! empty( $transient ) && $count < eazyest_folderbase()->max_process_items ) {
  			wp_delete_attachment( $transient[0] );
  			array_shift( $transient );
  			$count++;
  		}
  		if ( ! empty( $transient) )
  			set_transient( 'eazyest_gallery_delete_attachments', $transient );
  		else
				delete_transient(  'eazyest_gallery_delete_attachments' );
  	}
  }


  /**
   * Eazyest_Admin::add_attachments()
   * Add attachment that could not be inserted because of out of execution time error prevention.
   *
   * @since 0.1.0 (r159)
   * @uses get_transient() to get folder IDs whith new images
   * @uses delete_transient()
   * @return void
   */
  function add_attachments() {
  	/** add attachments weher new images have been found in a folder max_process_items per page load to prevent maximun execution time error */
  	if ( $post_ids = get_transient(  'eazyest_gallery_add_attachments' ) ) {
	  	delete_transient(  'eazyest_gallery_add_attachments' );
	  	$added = 0;
	  	if ( ! empty($post_ids ) ) {
		  	foreach( $post_ids as $post_id ) {
		  		$added += eazyest_folderbase()->add_images( $post_id );
		  		if( $added > eazyest_folderbase()->max_process_items )
		  			break;
				}
			}
		}
  }

  /**
   * Eazyest_Admin::plugin_action_links()
   * Add links to the plugin action menu.
   *
   * @since 0.1.0 (r79)
   * @uses admin_url()
   * @param mixed $links
   * @param mixed $file
   * @return
   */
  function plugin_action_links( $links, $file ) {
  	if ( $file == eazyest_gallery()->plugin_basename ) {
  		$url = '<a href="' . admin_url( 'options-general.php?page=eazyest-gallery' ) . '">' . __( 'Settings', 'eazyest-gallery' ) . '</a>';
  		array_unshift( $links, $url );
  		$links[] = '<a href="' . admin_url( 'index.php?page=eazyest-gallery-about' ) . '">' . __( 'About', 'eazyest-gallery' ) . '</a>';
  	}
  	return $links;
  }

  /**
   * Eazyest_Admin::settings_page()
   * Initiate the settings page
   *
   * @since 0.1.0 (r2)
   * @return Eazyest_Settings_Page object
   */
  function settings_page() {
		require_once( eazyest_gallery()->plugin_dir . 'admin/class-eazyest-settings-page.php' );
		return Eazyest_Settings_Page::instance();
  }

  /**
   * Eazyest_Admin::tools_page()
   * Initiate the tools page.
   *
   * @since 0.1.0 (r2)
   * @return Eazyest_Tools_Page object
   */
  function tools_page() {
  	require_once( eazyest_gallery()->plugin_dir . 'tools/class-eazyest-tools-page.php' );
  	return Eazyest_Tools_Page::instance();
  }

  function about_page() {
  	include_once( eazyest_gallery()->plugin_dir . 'admin/class-eazyest-about-page.php' );
  	return Eazyest_About_Page::instance();
  }

  /**
   * Eazyest_Admin::folder_editor()
   * Initiate the folder editor
   *
   * @since 0.1.0 (r2)
   * @return Eazyest_Folder_Editor object
   */
  function folder_editor() {
  	if ( eazyest_gallery()->right_path() ) {
			require_once( eazyest_gallery()->plugin_dir . 'admin/class-eazyest-folder-editor.php' );
	  	return Eazyest_Folder_Editor::instance();
		}
		return null;
  }

  /**
   * Eazyest_Admin::ajax()
   * Initiate AJAX functionality
   *
   * @since 0.1.0 (r2)
   * @return Eazyest_Ajax object
   */
  function ajax() {
		require_once( eazyest_gallery()->plugin_dir . 'admin/class-eazyest-admin-ajax.php' );
		return Eazyest_Admin_Ajax::instance();
  }


} // Eazyest_Admin

/**
 * eazyest_admin()
 *
 * @since 0.1.0 (r2)
 * @return object Eazyest_Admin
 */
function eazyest_admin() {
	return Eazyest_Admin::instance();
}
?>
