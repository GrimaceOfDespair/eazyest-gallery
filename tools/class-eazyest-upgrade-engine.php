<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Eazyest_Upgrade_Engine
 * Upgrade functions
 * This class is only needed and loaded when an upgrade is necessary
 *
 * @package Eazyest Gallery
 * @subpackage Upgrader
 * @author Marcel Brinkkemper
 * @copyright 2012 Brimosoft
 * @since 0.1.0 (r2)
 * @version 0.1.0 (r247)
 * @access public
 */
class Eazyest_Upgrade_Engine {

	/**
	 * @staticvar Eazyest_Upgrade_Engine $instance single instance in memory
	 */
	private static $instance;

	/**
	 * Eazyest_Upgrade_Engine::__construct()
	 *
	 * @return void
	 */
	function __construct(){}

	/**
	 * Eazyest_Upgrade_Engine::init()
	 *
	 * @return void
	 */
	function init() {
		$this->actions();
	}

	/**
	 * Eazyest_Upgrade_Engine::instance()
	 *
	 * @return EazyestUpgrade_Engine
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Eazyest_Upgrade_Engine;
			self::$instance->init();
		}
		return self::$instance;
	}

	/**
	 * Eazyest_Upgrade_Engine::actions()
	 * add WordPress actions
	 *
	 * @since 0.1.0 (r2)
	 * @uses add_action()
	 * @return void
	 */
	function actions() {
		add_action( 'admin_action_skip_gallery_update', array( $this, 'skip_gallery_update' ) );
		$this->ajax_actions();
	}

	// AJAX actions --------------------------------------------------------------
	/**
	 * Eazyest_Upgrade_Engine::ajax_actions()
	 * Action called by AJAX admin-ajax.php requests
	 *
	 * @since 0.1.0 (r2)
	 * @uses add_action()
	 * @return void
	 */
	function ajax_actions() {
		$actions = array(
			'get_upgrade_folders',
			'upgrade_folder',
			'convert_page',
			'update_settings',
			'cleanup',
		);
		foreach( $actions as $action ) {
			add_action( "wp_ajax_eazyest_gallery_$action", array( $this, $action ) );
		}
	}

	/**
	 * Eazyest_Upgrade_Engine::get_upgrade_folders()
	 * AJAX called
	 * Collect folder paths to be upgraded and echo number of folders
	 * If transient exists, upgrade has already started
	 *
	 * @since 0.1.0 (r2)
	 * @uses check_ajax_referer()
	 * @uses get_transient()
	 * @uses set_transient()
	 * @return void
	 */
	function get_upgrade_folders() {
		check_ajax_referer( 'eazyest-gallery-update' );
		eazyest_gallery()->gallery_folder = $_POST['gallery_folder'];
		$upgrade_folders = get_transient( 'eazyest-gallery-upgrade-folders' );
		if ( ! $upgrade_folders ){
			$upgrade_folders = eazyest_folderbase()->get_folder_paths();
			// no folder found, abort
			if ( 0 == count( $upgrade_folders ) ) {
				echo 'empty';
				wp_die();
			}
			set_transient(  'eazyest-gallery-upgrade-folders', $upgrade_folders, 0 );
		}
		echo  count( $upgrade_folders );
		wp_die();
	}

	/**
	 * Eazyest_Upgrade_Engine::upgrade_folder()
	 * AJAX called
	 * Upgrade a folder, remove path from array and echo number of remaining folders
	 *
	 * @since 0.1.0 (r2)
	 * @uses 	check_ajax_referer()
	 * @uses get_transient()
	 * @uses set_transient()
	 * @uses delete_transient()
	 * @return void
	 */
	function upgrade_folder() {
		check_ajax_referer( 'eazyest-gallery-update' );
		eazyest_gallery()->gallery_folder = $_POST['gallery_folder'];
		define( 'LAZYEST_GALLERY_UPGRADING', true );
		$upgrade_folders = get_transient( 'eazyest-gallery-upgrade-folders' );
		if ( ! $upgrade_folders )
			echo '0';
		else {
			$folder_id = 0;
			$images_max = intval( $_POST['images_max'] );
			$upgrade_images = get_transient( 'eazyest-gallery-upgrade_images' );
			if ( ! $upgrade_images ) {
				// only upgrade folder if we do not have leftover images
				$folder_id = $this->do_upgrade_folder();
				$upgrade_folders = get_transient( 'eazyest-gallery-upgrade-folders' );
			}
			if ( 0 == $this->do_upgrade_images( $folder_id, $images_max ) ) {
				// all images have been upgraded, remove xml files and remove folder from upgrade array
				if ( isset( $_POST['remove_xml'] ) )
					$this->remove_xml( $folder_id );
				array_shift( $upgrade_folders );
				if ( ! empty( $upgrade_folders ) )
					set_transient( 'eazyest-gallery-upgrade-folders', $upgrade_folders, 0 );
				else
					delete_transient( 'eazyest-gallery-upgrade-folders' );
			}
			echo count( $upgrade_folders );
		}
		wp_die();
	}

	/**
	 * Eazyest_Upgrade_Engine::convert_page()
	 * AJAX called
	 * Convert page into gallery slug
	 *
	 * @since 0.1.0 (r2)
	 * @uses 	check_ajax_referer()
	 * @uses get_page()
	 * @uses set_transient()
	 * @uses wp_delete_post()
	 * @return void
	 */
	function convert_page() {
		check_ajax_referer( 'eazyest-gallery-update' );
		$page = get_page( $_POST['gallery_id'] );
		if ( ! empty( $page ) ) {
			$gallery_slug = $page->post_name;
			set_transient( 'eazyest-gallery-slug', $gallery_slug );
			wp_delete_post( $page->ID, true );
		}
		echo '1';
		wp_die();
	}

	/**
	 * Eazyest_Upgrade_Engine::update_settings()
	 * AJAX called
	 * Update settings to format
	 *
	 * @since 0.1.0 (r2)
	 * @uses 	check_ajax_referer()
	 * @uses get_option()
	 * @uses trailingslashit()
	 * @uses set_transient()
	 * @return void
	 */
	function update_settings() {
		check_ajax_referer( 'eazyest-gallery-update' );
		$options = get_option( 'lazyest-gallery' );
		if ( $options['gallery_folder'] != $_POST['gallery_folder'] ) {
			eazyest_gallery()->gallery_folder = $_POST['gallery_folder'];
			$temproot = str_replace('\\', '/', trailingslashit( eazyest_gallery()->get_absolute_path( ABSPATH . $gallery_folder ) ) );
			if ( $temproot == eazyest_gallery()->root() )
				set_transient( 'eazyest-gallery-folder', $_POST['gallery_folder'] );
		}
		$this->update_options();
		echo '1';
		wp_die();
	}

	/**
	 * Eazyest_Upgrade_Engine::cleanup()
	 * AJAX called
	 * Cleanup after upgrade
	 *
	 * @since 0.1.0 (r2)
	 * @uses 	check_ajax_referer()
	 * @return void
	 */
	function cleanup() {
		check_ajax_referer( 'eazyest-gallery-update' );
		$this->drop_table();
		$this->remove_roles();
		$this->remove_commentmeta();
		$this->remove_lazyest_gallery();
		delete_option( 'lazyest-gallery' );
		echo '1';
		wp_die();
	}

	// Upgrade functions ---------------------------------------------------------
	/**
	 * Eazyest_Upgrade_Engine::update_sort()
	 * convert sorting settings
	 *
	 * @since 0.1.0 (r2)
	 * @param string $setting
	 * @return string
	 */
	function update_sort( $setting ) {
		switch( $setting ) {
			case 'MANUAL' :
				return 'menu_order-ASC';
			case 'TRUE' :
				return 'post_name-ASC';
			case 'DTRUE' :
				return 'post_name_DESC';
			case 'CAPTION' :
				return 'post_title-ASC';
			case 'DCAPTION' :
				return 'post_title-DESC';
			case 'FALSE' :
				return 'post_date-ASC';
			case 'DFALSE' :
			default :
				return 'post_date_DESC';
		}
	}

	/**
	 * Eazyest_Upgrade_Engine::update_options()
	 * convert Lazyest Gallery settings and save option
	 *
	 * @since 0.1.0 (r2)
	 * @uses get_option()
	 * @uses update_option()
	 * @uses get_transient()
	 * @uses delete_transient()
	 * @return void
	 */
	function update_options() {
		// deactivate lazyest-gallery
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if ( is_plugin_active( 'lazyest-gallery/lazyest-gallery.php') )
			deactivate_plugins( 'lazyest-gallery/lazyest-gallery.php' );

		// get the old options
		$old_options  = get_option( 'lazyest-gallery' );
  	$options = eazyest_gallery()->defaults();

		if ( $gallery_folder = get_transient( 'eazyest-gallery-folder' ) ) {
			$old_options['gallery_folder'] = $gallery_folder;
			delete_transient( 'eazyest-gallery-folder' );
		}

  	// convert only settings used since eazyest-gallery
		foreach( $options as $setting => $value ) {
			switch( $setting ) {
				case 'show_credits' :
				case 'random_subfolder' :
				case 'enable_exif' :
					$options[$setting] = isset( $old_options[$setting] ) ? ( $old_options[$setting] == 'TRUE' ) : false;
					break;
				case 'gallery_folder' :
					$options[$setting] = str_replace( '\\', '/', $old_options[$setting] );
					break;
				case 'sort_folders' :
					$options[$setting] = $this->update_sort( $old_options['sort_folders'] );
					break;
				case 'sort_thumbnails' :
					$options[$setting] = $this->update_sort( $old_options['sort_alphabetically'] );
					break;
				case 'on_thumb_click' :
					switch( $old_options[$setting] ) {
						case 'fullimg' :
							$value = 'full';
							break;
						case 'slide' :
							$value = 'attachment';
							break;
						case 'lightslide' :
							$value = 'large';
							$options['thumb_popup'] = 'lightbox';
							break;
						case 'thickslide' :
							$value = 'large';
							$options['thumb_popup'] = 'thickbox';
							break;
						case 'lightbox' :
							$value = 'full';
							$options['thumb_popup'] = 'lightbox';
							break;
						case 'thickbox' :
							$value = 'full';
							$options['thumb_popup'] = 'thickbox';
							break;
					}
					$options[$setting] = $value;
					break;
				case 'on_slide_click' :
					switch( $old_options[$setting] ) {
						case 'nothing' :
							$value = 'none';
							break;
						case 'fullimg' :
							$value = 'full';
							$options['slide_popup'] = 'none';
							break;
						case 'lightbox' :
							$value = 'full';
							$options['slide_popup'] = 'lightbox';
							break;
						case 'thickbox' :
							$value = 'full';
							$options['slide_popup'] = 'thickbox';
							break;
					}
					$options[$setting] = $value;
					break;
				default :
					$options[$setting] = isset( $old_options[$setting] ) ? $old_options[$setting] : $options[$setting];
			}
		}
		if ( $gallery_slug = get_transient( 'eazyest-gallery-slug' ) ) {
			$options['gallery_slug'] = $gallery_slug;
			delete_transient( 'eazyest-gallery-slug' );
		}
		$options['gallery_secure'] = EZG_SECURE_VERSION;
		$options['new_install']    = false;
		update_option( 'eazyest-gallery', $options );

		if ( $fields_options = get_option( 'eazyest-fields' ) )
			eazyest_extra_fields()->enable();
	}

	/**
	 * Eazyest_Upgrade_Engine::drop_table()
	 * Remove the eazyestfiles table
	 *
	 * @since 0.1.0 (r2)
	 * @uses wpdp
	 * @return bool
	 */
	function drop_table() {
		global $wpdb;
		$table = $wpdb->prefix . 'lazyestfiles';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'") == $table ) {
			$wpdb->query( "DROP TABLE $table" );
		}
		return true;
	}

	/**
	 * Eazyest_Upgrade_Engine::remove_roles()
	 * Remove roles used in previous versions
	 *
	 * @since 0.1.0 (r2)
	 * @uses remove_role()
	 * @return void
	 */
	function remove_roles() {
		$lazyest_roles = array(
			 'lazyest_manager',
			 'lazyest_editor',
			 'lazyest_author',
		);
		foreach( $lazyest_roles as $lazyest_role ) {
			$args = array(
				'role'   => $lazyest_role,
			);
			$users = get_users( $args );
			if ( ! empty( $users ) ) {
				foreach( $users as $user ) {
					$user->remove_role( $lazyest_role );
				}
			}
			remove_role( $lazyest_role );
		}
	}

	/**
	 * Eazyest_Upgrade_Engine::remove_commentmeta()
	 * Remove coment meta values used before eazyest-gallery
	 *
	 * @since 0.1.0 (r2)
	 * @uses wpdb
	 * @return bool
	 */
	function remove_commentmeta() {
		global $wpdb;
		$wpdb->query( "DELETE FROM $wpdb->commentmeta WHERE meta_key = 'lazyest'" );
	}

	/**
	 * Eazyest_Upgrade_Engine::remove_lazyest_gallery()
	 * Remove the LAzyest Gallery plugin to prevent conflicts.
	 *
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	function remove_lazyest_gallery() {
		$delete_directory = dirname( eazyest_gallery()->plugin_dir ) . '/lazyest-gallery';
		if ( is_dir( $delete_directory ) )
			eazyest_folderbase()->clear_dir( $delete_directory );
	}

	/**
	 * Eazyest_Upgrade_Engine::skip_gallery_update()
	 * Skip update but do remove unused table, commentmeta, and roles
	 *
	 * @since 0.1.0 (r2)
	 * @uses check_admin_referer()
	 * @uses add_query_arg()
	 * @uses wp_redirect()
	 * @return void
	 */
	function skip_gallery_update() {
		check_admin_referer( 'eazyest-gallery-update' );
		$this->update_options();
		$this->remove_roles();
		$this->drop_table();
		$this->remove_commentmeta();
		$this->remove_lazyest_gallery();

		delete_option( 'lazyest-gallery' );

		$redirect = add_query_arg( array( 'page' => 'eazyest-gallery'), admin_url( 'options-general.php' ) );

		wp_redirect( $redirect );
		exit;
	}

	/**
	 * Eazyest_Upgrade_Engine::read_folder_data()
	 * Read folder attributes from the captions.xml file
	 *
	 * @since 0.1.0 (r2)
	 * @param string $xml_file path
	 * @return array()
	 */
	private function read_folder_data( $xml_file ) {
		$folder_data = array(
			'caption'      => '',
			'description'  => '',
			'id'           => 0,
			'menu_order'   => 0,
			'editor'       => 0,
			'visibility'   => 'visible',
			'extra_fields' => array()
		);
		$folder_data['folderdate'] =  @filemtime( dirname( $xml_file ) );
  	if ( false === $folder_data['folderdate'] ) {
  		$folder_data['folderdate'] = time();
  	}
  	if ( ! file_exists( $xml_file ) )
  		return $folder_data;
		// read folder from xml file
		$xml_reader = new Eazyest_XML_Parser;
		$xml_array  = $xml_reader->parse( $xml_file );
		if (  ! empty( $xml_array ) ) {
			foreach ( $xml_array[0]['children'] as $child ) {
			  switch ( $child['name'] ) {
					case 'FOLDER' :
			      if ( isset( $child['tagdata'] ) )  {
						  $folder_data['caption'] = stripslashes( html_entity_decode( utf8_decode( $child['tagdata'] ) ) );
	          }
						break;
					case 'FDESCRIPTION' :
			      if ( isset( $child['tagdata'] ) )  {
						  $folder_data['description'] = stripslashes( html_entity_decode( utf8_decode( $child['tagdata'] ) ) );
	          }
						break;
					case 'ORDER' :
			      if ( isset( $child['tagdata'] ) )  {
						  $folder_data['menu_order'] = intval( $child['tagdata'] );
	          }
						break;
					case 'VISIBILITY' :
			      if ( isset( $child['tagdata'] ) )  {
						  $folder_data['visibility'] = $child['tagdata'];
	          }
						break;
				  case 'ID' :
			      if ( isset( $child['tagdata'] ) )  {
				  	  $folder_data['id'] = intval( $child['tagdata'] );
	          }
						break;
	        case 'FOLDERDATE' :
			      if ( isset( $child['tagdata'] ) )  {
				  	 $folder_data['folderdate'] = intval($child['tagdata']);
	          }
						break;
	        case 'EDITOR' :
			      if ( isset( $child['tagdata'] ) )  {
				  	  $folder_data['editor'] = intval( $child['tagdata'] );
	          }
	          break;
					case 'LEVEL' :
					case 'VIEWER_LEVEL' :
						break;
					default :
						$key = strtolower( $child['name'] );
						if ( isset( $child['tagdata'] ) ) {
							$folder_data['extra_fields'][$key] = stripslashes( html_entity_decode( utf8_decode( $child['tagdata'] ) ) );
						}
						break;
				}
	    }
		}
		return $folder_data;
	}

	/**
	 * Eazyest_Upgrade_Engine::move_comments()
	 * Move comments from gallery page/folder/image to custom post type galleryfolder or to attachment.
	 *
	 * @param integer $xml_id id used in captions.xml file
	 * @param integer $wpdb_id post_id for post/attachment
	 * @return integer number of comments moved
	 */
	private function move_comments( $xml_id, $wpdb_id ) {
		$comment_count = 0;
		global $wpdb;
		$comments_meta = $wpdb->get_results( $wpdb->prepare("SELECT * FROM $wpdb->commentmeta WHERE meta_key = 'lazyest' AND meta_value = %d", $xml_id ), ARRAY_A  );
		if ( ! empty( $comments_meta ) ) {
			foreach( $comments_meta as $comment_meta ) {
				$comment = get_comment( $comment_meta['comment_id'], ARRAY_A );
				if ( $_POST['allow_comments'] ) {
					$gallery_id = $comment['comment_post_ID'];
					$comment['comment_post_ID'] = $wpdb_id;
					if ( false !== $wpdb->update(  $wpdb->comments, $comment, array( 'comment_ID' => $comment['comment_ID'] ) ) ) {
						$comment_count++;
					}
				}	else {
					wp_delete_comment( $comment_meta['comment_id'], true );
				}
			}
			if ( $comment_count )
				wp_update_comment_count( $gallery_id );
		}
		return $comment_count;
	}

	/**
	 * Eazyest_Upgrade_Engine::do_upgrade_folder()
	 * Create custom post type galleryfolder and parse xml values to custom post type fields.
	 *
	 * @since 0.1.0 (r2)
	 * @uses get_transient()
	 * @uses aplly_filters()) for ('eazyest_gallery_delete_cache',false) because by default chache is not deleted
	 * @uses get_post()
	 * @uses wp_update_post()
	 * @return integer post_id for new galleryfolder
	 */
	function do_upgrade_folder() {
		$upgrade_folders = get_transient( 'eazyest-gallery-upgrade-folders' );
		if ( $upgrade_folders ) {
			// apply filter to delete cache (false)
			if ( isset( $_POST['remove_cache'] ) )
				$this->delete_cache();

			// get folder path to upgrade
			$raw_path = $upgrade_folders[0];
			if (  $folder_id = eazyest_folderbase()->get_folder_by_path( $raw_path ) ) {
				// folder is already in database
				return $folder_id;
			}
			$folder_title = basename( $raw_path );

			// convert dashes and hyphens to spaces
			if ( eazyest_folderbase()->replace_dashes() )
				$folder_title = str_replace( array( '-', '_'), ' ', $folder_title );

			// insert folder in wpdb and retrieve stored/sanitized galery_path
			$folder_id = eazyest_folderbase()->insert_folder( $raw_path );
			if ( $folder_id ) {
				$gallery_path = ezg_get_gallery_path( $folder_id );
				// change folders array to support renamed folder
				foreach( $upgrade_folders as $key => $upgrade_folder ) {
					$strpos = strpos( $upgrade_folder, $raw_path );
					if ( false !== $strpos ) {
						if ( 0 == $strpos ) {
							$upgrade_folder = str_replace( $raw_path, $gallery_path, $upgrade_folder );
							$upgrade_folders[$key] = $upgrade_folder;
						}
					}
				}
				// store folders array for next AJAX call
				set_transient( 'eazyest-gallery-upgrade-folders', $upgrade_folders, 0 );

				$post_parent = 0;
				if ( strpos( $gallery_path, '/') ) {
					$parent_dir = dirname( $gallery_path );
					global $wpdb;
					$parent_ids = $wpdb->get_col( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_gallery_path' AND meta_value = '$parent_dir'" );
					$post_parent = isset( $parent_ids[0] ) ? $parent_ids[0] : 0;
				}
				// read existing edits
				$xml_file = eazyest_gallery()->root() . $gallery_path . '/captions.xml';
				$folder_data = $this->read_folder_data( $xml_file );

				// update folder with read data
				$datetime = date( 'Y-m-d H:i:s', $folder_data['folderdate'] );
				$folder = get_post( $folder_id, ARRAY_A );
				$folder['post_title']    = ! empty(  $folder_data['caption']     )                              ? $folder_data['caption']     : $folder_title;
				$folder['post_excerpt']  = ! empty(  $folder_data['caption']     )                              ? $folder_data['caption']     : $folder_title;
				$folder['post_content']  = ! empty(  $folder_data['description'] )                              ? $folder_data['description'] : '';
				$folder['menu_order']    = 0 <       $folder_data['menu_order']                                 ? $folder_data['menu_order']  : 0;
				$folder['post_author']   = 0 <       $folder_data['editor']                                     ? $folder_data['editor']      : $folder['post_author'];
				$folder['post_status']   = in_array( $folder_data['visibility'], array( 'hidden', 'private' ) ) ? $folder_data['visibility']  : $folder['post_status'];
				$folder['post_date']     = $datetime;
				$folder['post_date_gmt'] = get_gmt_from_date( $datetime );
				$folder['post_parent']   = $post_parent;
				// move comments to this folder
				wp_update_post( $folder );
				$options = get_option( 'lazyest-gallery'  );
				if (  isset($options['allow_comments']) && 'TRUE' == $options['allow_comments'] ) {
					if ( $this->move_comments( $folder_data['id'], $folder_id ) )
						wp_update_comment_count( $folder_id );
				}
				if ( ! empty( $folder_data['extra_fields']) ) {
					foreach( $folder_data['extra_fields'] as $field => $value )
						eazyest_extra_fields()->update_post_field( $folder_id, $field, $value );
				}
			}
		}
		return $folder_id;
	}

	/**
	 * Eazyest_Upgrade_Engine::get_upgrade_images()
	 * Get array of image names that should be updated for a particular gallery folder.
	 *
	 * @since 0.1.0 (r2)
	 * @param string $gallery_path
	 * @return array
	 */
	private function get_upgrade_images( $gallery_path ) {
		$images = array();
    $dir = eazyest_gallery()->root() . $gallery_path;
		if ( $dir_content = @opendir( $dir ) ) {
			while ( false !== ( $dir_file = readdir( $dir_content ) ) ) {
        if ( ! is_dir( $dir_file ) && ( 0 < preg_match( "/^.*\.(jpg|gif|png|jpeg)$/i", $dir_file ) ) ) {
          $images[] = utf8_encode( basename( $dir_file ) );
        }
			}
      @closedir( $dir_content );
		}
		return $images;
	}

	/**
	 * Eazyest_Upgrade_Engine::read_images_data()
	 * Read image attributes from captions.xml file and return array of iamges data.
	 *
	 * @since 0.1.0 (r2)
	 * @param mixed $xml_file
	 * @return array
	 */
	private function read_images_data( $xml_file ) {
		$images_data = array();
		if ( ! file_exists( $xml_file ) )
			return $images_data;

		// read images from xml file
		$xml_reader = new Eazyest_XML_Parser;
		$xml_array  = $xml_reader->parse( $xml_file );
		if ( ! empty( $xml_array ) ) {
			foreach ( $xml_array[0]['children'] as $child ) {
				if ( 'PHOTO' == $child['name'] ) {
				  $image = array(
				  	'id'           => 0,
				  	'menu_order'   => 0,
				  	'caption'      => '',
				  	'description'  => '',
				  	'imagedate'    => @filemtime( dirname( $xml_file ) . '/' . $image['image'] ),
						'extra_fields' => array()
					);

					if ( false === $image['imagedate'] )
				 		$image['imagedate'] = time();

					foreach( $child['children'] as $grandchild ) {
						switch ( $grandchild['name'] ) {
							case 'FILENAME' :
                if ( isset( $grandchild['tagdata'] ) ) {
								  $image['image'] = stripslashes( html_entity_decode( utf8_decode(  $grandchild['tagdata'] ) ) );
                }
								break;
							case 'CAPTION' :
                if ( isset( $grandchild['tagdata'] ) ) {
								  $image['caption'] = stripslashes( html_entity_decode( utf8_decode( $grandchild['tagdata'] ) ) );
                }
								break;
							case 'DESCRIPTION' :
                if ( isset( $grandchild['tagdata'] ) ) {
								  $image['description'] = stripslashes( html_entity_decode( utf8_decode( $grandchild['tagdata'] ) ) );
                }
								break;
							case 'IMAGE' :
                if ( isset( $grandchild['tagdata'] ) ) {
								  $image['id'] = $grandchild['tagdata'];
                }
								break;
							case 'INDEX' :
                if ( isset( $grandchild['tagdata'] ) ) {
									$image['menu_order'] = absint( $grandchild['tagdata'] );
                }
                break;
              case 'IMAGEDATE' :
                if ( isset( $grandchild['tagdata'] ) ) {
                  $image['imagedate'] = intval( $grandchild['tagdata'] );
                }
                break;
							default :
								$key = strtolower( $grandchild['name'] );
								if ( isset( $grandchild['tagdata'] ) ) {
									$image['extra_fields'][$key] = stripslashes( html_entity_decode( utf8_decode( $grandchild['tagdata'] ) ) );
								}
							break;
						}
          }
					$images_data[] = $image;
			  }
			}
		}
		return $images_data;
	}

	/**
	 * Eazyest_Upgrade_Engine::delete_cache()
	 * Remove cached thumbs and slides for a folder.
	 *
	 * @since 0.1.0 (r2)
	 * @uses get_transient()
	 * @return void
	 */
	private function delete_cache() {
		$upgrade_folders = get_transient( 'eazyest-gallery-upgrade-folders' );
		$gallery_path = $upgrade_folders[0];
		$options = get_option( 'lazyest-gallery' );
		eazyest_gallery()->gallery_folder = $_POST['gallery_folder'];
		eazyest_folderbase()->clear_dir( eazyest_gallery()->root() . $gallery_path . '/' . $options['thumb_folder'] );
		eazyest_folderbase()->clear_dir( eazyest_gallery()->root() . $gallery_path . '/' . $options['slide_folder'] );
	}

	/**
	 * Eazyest_Upgrade_Engine::remove_xml()
	 * Remove captions.xml file for this folder.
	 *
	 * @since 0.1.0 (r2)
	 * @param integer $folder_id
	 * @return void
	 */
	private function remove_xml( $folder_id ) {
		$gallery_path = ezg_get_gallery_path( $folder_id );
		$captions_xml = eazyest_gallery()->root() . $gallery_path . '/captions.xml';
		if ( file_exists( $captions_xml ) )
			unlink( $captions_xml );
	}

	/**
	 * Eazyest_Upgrade_Engine::do_upgrade_images()
	 * Upgrade xml values to attachment post fields and metadata.
	 *
	 * @since 0.1.0 (r2)
	 * @uses get_transient()
	 * @uses delete_transient()
	 * @uses get_post()
	 * @uses wp_update_post()
	 * @param integer $folder_id post_id
	 * @param integer $images_max maximum number of images to update in one run
	 * @return integer number of images still to upgrade
	 */
	function do_upgrade_images( $folder_id = 0, $images_max = 0 ) {
		if  ( ! $images_max )
			$images_max = eazyest_folderbase()->max_process_items;
		if ( 0 == $folder_id ) {
			$upgrade_folders = get_transient( 'eazyest-gallery-upgrade-folders' );
			$gallery_path = $upgrade_folders[0];
			$folder_id = eazyest_folderbase()->get_folder_by_path( $gallery_path );
		} else {
			$gallery_path = ezg_get_gallery_path( $folder_id );
		}
		// get image names in folder
		$upgrade_images = get_transient( 'eazyest-gallery-upgrade-images' );
		if ( ! $upgrade_images )
			$upgrade_images = $this->get_upgrade_images( $gallery_path );

		// read existing edits
		$xml_file = eazyest_gallery()->root() . $gallery_path . '/captions.xml';
		$images_data = $this->read_images_data( $xml_file );

		$upgraded = 0;
		$count =  count( $upgrade_images );
		$_POST['post_type'] = eazyest_gallery()->post_type;
		if ( ! empty( $images_data ) ) {
			while( $upgraded < $images_max && $upgraded < $count && count( $images_data ) > 0  ) {
				$image = $images_data[0];
				$datetime = date( 'Y-m-d H:i:s', $image['imagedate'] );
				$key = array_search( $image['image'], $upgrade_images );
				if ( $key !== false ) {
					$attach_name = eazyest_folderbase()->sanitize_filename( $image['image'], $folder_id );
					$attach_file = eazyest_gallery()->root() . $gallery_path . '/' . $attach_name;
					$attachment_id = eazyest_folderbase()->insert_image( $folder_id, $attach_file, $image['image'] );
					unset( $upgrade_images[$key] );
					$attachment = get_post( $attachment_id, ARRAY_A );
					$attachment['post_title']    = ! empty( $image['caption'] )     ? $image['caption']     : $attachment['post_title'];
					$attachment['post_excerpt']  = ! empty( $image['caption'] )     ? $image['caption']     : $attachment['post_excerpt'];
					$attachment['post_content']	 = ! empty( $image['description'] ) ? $image['description'] : $attachment['post_content'];
					$attachment['menu_order']    = 0 < $image['menu_order']         ? $image['menu_order']  : 0;
					$attachment['post_date']     = $datetime;
					$attachment['post_date_gmt'] = get_gmt_from_date( $datetime );
					wp_update_post( $attachment  );
					$options = get_option( 'lazyest-gallery' );
					if (  isset($options['allow_comments']) && 'TRUE' == $options['allow_comments'] ) {
						if ( $this->move_comments( $image['id'], $attachment_id ) )
							wp_update_comment_count($attachment_id );
					}
					if ( ! empty( $image['extra_fields'] ) ) {
						foreach( $image['extra_fields'] as $field => $value )
							eazyest_extra_fields()->update_post_field( $attachment_id, $field, $value );
					}
					$upgraded++;
					unset( $upgrade_images[$key] );
				}
				array_shift( $images_data );
			}
		}
		if ( empty( $images_data ) ) {
			delete_transient( 'eazyest-gallery-upgrade-images' );
			return 0;
		}	else {
			set_transient( 'eazyest-gallery-upgrade-images', $upgrade_images, 0 );
			return( count( $upgrade_images ) );
		}
	}
} // Eazyest_Upgrade_Engine

/**
 * Eazyest_XML_Parser
 * Parser for captions.xml file
 *
 * @package Eazyest Gallery
 * @author Marcel Brinkkemper
 * @copyright 2012 Brimosoft
 * @version 2.2.0 (r241)
 * @access public
 */
class Eazyest_XML_Parser {

	var $arr_output = array();
	var $res_parser;
	var $str_xml_data;

	/**
	 * Eazyest_XML_Parser::parse()
	 *
	 * @since 0.1.0 (r2)
	 * @param string $str_input_xml
	 * @return array
	 */
	function parse( $str_input_xml ) {
		$this->res_parser = xml_parser_create ();
		xml_parser_set_option( $this->res_parser, XML_OPTION_TARGET_ENCODING, 'UTF-8' );
		xml_set_object( $this->res_parser, $this );
		xml_set_element_handler( $this->res_parser, "tag_open", "tag_closed" );

		xml_set_character_data_handler( $this->res_parser, "tag_data" );

		$f = fopen( $str_input_xml, 'r' );
		$readok = true;

		while( ( $data = fread( $f, 4096 ) ) && $readok ) {
			$this->str_xml_data = xml_parse( $this->res_parser,$data );
			if( ! $this->str_xml_data ) {
				printf( "XML error: %s at line %d in file %s <br />" ,
					xml_error_string(xml_get_error_code( $this->res_parser ) ),
					xml_get_current_line_number( $this->res_parser ),
					$str_input_xml );
				$readok = false;
			}
		}
		xml_parser_free( $this->res_parser );

		if ( $readok ) {
			return $this->arr_output;
		} else {
			return null;
		}
	}

	/**
	 * Eazyest_XML_Parser::tag_open()
	 *
	 * @since 0.1.0 (r2)
	 * @param xml_parser $parser
	 * @param string $name
	 * @param string $attrs
	 * @return void
	 */
	function tag_open( $parser, $name, $attrs ) {
		$tag=array( "name" => $name, "attrs" => $attrs );
		array_push($this->arr_output, $tag);
	}

	/**
	 * Eazyest_XML_Parser::tag_data()
	 *
	 * @since 0.1.0 (r2)
	 * @param xml_parser $parser
	 * @param string $tag_data
	 * @return void
	 */
	function tag_data( $parser, $tag_data ) {
		if( trim( $tag_data ) ) {
			if( isset( $this->arr_output[count( $this->arr_output )-1]['tagdata'] ) ) {
				$this->arr_output[count( $this->arr_output )-1]['tagdata'] .= $tag_data;
			}
			else {
				$this->arr_output[count( $this->arr_output )-1]['tagdata'] = $tag_data;
			}
		}
	}

	/**
	 * Eazyest_XML_Parser::tag_closed()
	 *
	 * @since 0.1.0 (r2)
	 * @param xml_parser $parser
	 * @param string $name
	 * @return
	 */
	function tag_closed( $parser, $name ) {
		$this->arr_output[count( $this->arr_output )-2]['children'][] = $this->arr_output[count( $this->arr_output )-1];
		array_pop( $this->arr_output );
	}
} // Eazyest_XML_Parser
