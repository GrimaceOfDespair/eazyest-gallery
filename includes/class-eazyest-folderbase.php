<?php
/**
 * Eazyest_FolderBase
 * Handels all functions related to post_type galleryfolder and their attachments
 *
 * @package Eazyest Gallery
 * @subpackage Folderbase
 * @author Marcel Brinkkemper
 * @copyright 2012-2013 Brimosoft
 * @since @since 0.1.0 (r2)
 * @version 0.2.0 (r324)
 * @access public
 */


// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class Eazyest_FolderBase {
	/**
	 * @var private array of posted folder paths
	 */
	private $posted_paths;

	/**
	 * @var private array of folder paths
	 */
	private $folder_paths;

	/**
	 * @var private array of posted images
	 */
	private $posted_images;

	/**
	* @var private array of images in folder
	*/
  private $folder_images;

  /**
   * @var number of new (+) or deleted (-) images found the last time collect_images() run
	 */
  private $images_collected;

  /**
   * @var number of new (+) or deleted (-) folders found the last time collect_folders() run
	 */
  private $folders_collected;

  /**
   * @var $max_process_items maxium number of items to process to prevent out of execution time errors   *
   */
  public $max_process_items;

  /**
   * @staticvar Eazyest_FolderBase $instance single object in memory
   */
  private static $instance;

  /**
   * Eazyest_FolderBase::__construct()
   *
   * @return void
   */
  function __construct() {}

  /**
   * Eazyest_FolderBase::init()
   *
   * @since 0.1.0 (r2)
   * @return void
   */
  private function init() {
  	$this->includes();
  	$this->actions();
		$this->filters();
		$this->register_post_types();
		$this->register_post_status();
		$this->endpoints();
  }

  /**
   * Eazyest_FolderBase::instance()
   *
   * @since 0.1.0 (r2)
   * @return Eazyest_FolderBase object
   */
  public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Eazyest_FolderBase;
			self::$instance->init();
		}
		return self::$instance;
  }

  function includes() {
  	if ( $theme = ezg_theme_compatible() ) {
  		include( eazyest_gallery()->plugin_dir . 'themes/' . $theme . '/functions.php' );
  	}
  }

	/**
	 * Eazyest_FolderBase::actions()
	 * Add WordPress actions
	 * @uses apply_filter() for 'eazyest_gallery_insert_folder_action' to set action before the images list is built
	 * filtered value can be either:
	 *  'collect_images' : collect new (ftp) uploaded images when new uploaded folder has been found and inserted in the WP database
	 *                     this could potentially create many transactions when you open admin
	 *  '__return_false' : take no action ( default )
	 *
	 * @since 0.1.0 (r2)
	 * @uses add_action() for 'save_post', 'before_delete_post' and 'eazyest_gallery_insert_folder'
	 * @return void
	 */
	function actions() {
		add_action( 'init',                          array( $this, 'filtered_vars'       ), 1000    );
		add_action( 'save_post',                     array( $this, 'save_post'           ),    1    );
		add_action( 'save_post',                     array( $this, 'save_attachment'     ),    2    );
		add_action( 'before_delete_post',            array( $this, 'before_delete_post'  ),    1    );
		add_action( 'eazyest_gallery_insert_folder', '__return_false',                        10, 1 );
	}

	/**
	 * Eazyest_FolderBase::filters()
	 * Add WordPress filters
	 *
	 * @since 0.1.0 (r2)
	 * @uses add_filter
	 * @return void
	 */
	function filters() {
		// filters related to folders
		add_filter( 'pre_get_posts',                   array( $this, 'pre_get_posts'                ),  10    );
		// filters related to metadata
		add_filter( 'get_attached_file',               array( $this, 'get_attached_file'            ),  20, 2 );
		add_filter( 'wp_get_attachment_url',           array( $this, 'get_attachment_url'           ),  20, 2 );
		add_filter( 'update_post_metadata',            array( $this, 'update_attachment_metadata'   ),  20, 5 );
		add_filter( 'wp_get_attachment_metadata',      array( $this, 'get_attachment_metadata'      ),  10, 2 );
		add_filter( 'wp_generate_attachment_metadata', array( $this, 'generate_attachment_metadata' ),   1, 2 );
		add_filter( 'image_downsize',                  array( $this, 'image_downsize'               ),  10, 3 );
		// filters related to image editing
		add_filter( 'wp_image_editors',                array( $this, 'image_editors'                ), 999    );
		add_filter( 'wp_save_image_editor_file',       array( $this, 'save_image_editor_file'       ),  20, 5 );
		// other filters
		add_filter( 'wp_create_file_in_uploads',       array( $this, 'create_file_in_uploads'       ),  10, 2 );
	}

	/**
	 * Eazyest_FolderBase::filtered_vars()
	 * Initialize variables with filter
	 *
	 * @since 0.1.0 (r159)
	 * @uses add_filter()
	 * @return void
	 */
	function filtered_vars() {
		$this->max_process_items = apply_filters( 'eazyest_gallery_max_process_items', 20 );
	}

	// Functions related to folders ----------------------------------------------

	/**
	 * Eazyest_FolderBase::register_post_types()
	 * Register post types used by Eazyest Gallery
	 *
	 * @since 0.1.0 (r2)
	 * @uses register_post_type()
	 * @return void
	 */
	function register_post_types() {

		$post_type = array();

		$post_type['labels'] = array(
			'name'               => eazyest_gallery()->gallery_title(),
			'menu_name'          => eazyest_gallery()->gallery_name(),
			'singular_name'      => __( 'Folder',                    'eazyest-gallery' ),
			'all_items'          => __( 'All Folders',               'eazyest-gallery' ),
			'add_new'            => __( 'Add New' ,                  'eazyest-gallery' ),
			'add_new_item'       => __( 'Create New Folder',         'eazyest-gallery' ),
			'edit'               => __( 'Edit',                      'eazyest-gallery' ),
			'edit_item'          => __( 'Edit Folder',               'eazyest-gallery' ),
			'new_item'           => __( 'New Folder',                'eazyest-gallery' ),
			'view'               => __( 'View Folder',               'eazyest-gallery' ),
			'view_item'          => __( 'View Folder',               'eazyest-gallery' ),
			'search_items'       => __( 'Search Folders',            'eazyest-gallery' ),
			'not_found'          => __( 'No folders found',          'eazyest-gallery' ),
			'not_found_in_trash' => __( 'No folders found in Trash', 'eazyest-gallery' ),
			'parent_item_colon'  => __( 'Parent Folder:',            'eazyest-gallery' ),
		);

		$post_type['rewrite'] = array(
			'slug'       => eazyest_gallery()->gallery_slug(),
			'with_front' => true,
			'feeds'      => true,
		);

		$post_type['supports'] = array(
			'title',
			'editor',
			'author',
			'custom-fields',
			'categories',
			'thumbnail',
			'comments',
			'page-attributes',
		);

		register_post_type(
			eazyest_gallery()->post_type, array(
				'labels'              => $post_type['labels'],
				'rewrite'             => $post_type['rewrite'],
				'supports'            => $post_type['supports'],
				'description'         => __( 'Eazyest Gallery Folder', 'eazyest-gallery' ),
				'menu_position'       => 10,
				'exclude_from_search' => false,
				'show_in_nav_menus'   => true,
				'public'              => true,
				'show_ui'             => true,
				'can_export'          => true,
				'hierarchical'        => true,
				'query_var'           => eazyest_gallery()->post_type,
				'taxonomies'          => array('post_tag'),
				'has_archive'         => true,
			)
		);

		// if a new gallery_slug has been assigned
		if ( $flush = get_transient( 'eazyest-gallery-flush-rewrite-rules' ) ) {
			flush_rewrite_rules();
			delete_transient( 'eazyest-gallery-flush-rewrite-rules' );
		}
	}

	/**
	 * Eazyest_FolderBase::register_post_status()
	 * Register a post status for hidden folders.
	 * Hidden folders do exist, but they do not show in folder listings and cannot be accessed in frontend.
	 *
	 * @since 0.1.0 (r96)
	 * @return void
	 */
	function register_post_status() {
		$post_status = 'hidden';
		$args = array(
			'label'                     => _x( 'Hidden', 'post status', 'eazyest-gallery' ),
			'public'                    => false,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Hidden <span class="count">(%s)</span>', 'Hidden <span class="count">(%s)</span>' ),
		);
		register_post_status( $post_status, $args );
	}

	/**
	 * Eazyest_FolderBase::endpoints()
	 * Add rewrite endpoints for slideshow.
	 * @see http://codex.wordpress.org/Rewrite_API/add_rewrite_endpoint
	 *
	 * @since 0.1.0 (r65)
	 * @uses add_rewrite_endpoint()
	 * @return void
	 */
	function endpoints() {
		// rewrite endpoint for slideshows /slideshow/large (2nd argument is size)
		add_rewrite_endpoint( 'slideshow', EP_PERMALINK );
		// rewrite endpoint for thumbnail pages /thumbnails/2 (2nd argument is page)
		add_rewrite_endpoint( 'thumbnails', EP_PERMALINK );
		// rewrite endpoint for folder pages /folders/2 (2nd argument is page)
		add_rewrite_endpoint( 'folders', EP_PERMALINK );
	}

	/**
	 * Eazyest_FolderBase::refered_by_folder()
	 * Check if the current action is refered by the edit folder screen
	 *
	 * @since 0.1.0 (r2)
	 * @uses wp_get_referer()
	 * @uses get_post_type()
	 * @return integer ID of Folder being edited
	 */
	function refered_by_folder() {
		$request = explode( '&', parse_url( wp_get_referer(), PHP_URL_QUERY ) );
		$post_id = 0;
		if ( ! empty( $request ) ) {
			foreach( $request as $part )
				if ( false !== strpos( $part, 'post' ) ) {
					$post_id = intval( substr( $part, 5 ) );
					$post_id = get_post_type( $post_id ) == eazyest_gallery()->post_type ? $post_id : 0;
				}
		}
		return $post_id;
	}

	/**
	 * Eazyest_FolderBase::thumbnails_orderby()
	 * Changes the orderby partameter because WP_Query does not allow to order by post_excerpt
	 *
	 * @since 0.1.0 (r298) moved from Eazyest_Frontend @since 0.2.0 (323)
	 * @uses wpdb
	 * @param string $orderby
	 * @return string
	 */
	function thumbnails_orderby( $orderby ) {
		global $wpdb;
		return $wpdb->posts . '.' . str_replace( '-', ' ', eazyest_gallery()->sort_by('thumbnails') );
	}

	/**
	 * Eazyest_FolderBase::pre_get_posts()
	 * Add gallery post_type to tag query
	 *
	 * @since 0.1.0 (r2)
	 * @uses WP_Query::get()
	 * @uses WP_Query::set()
	 * @param WP_Query $query
	 * @return WP_Query object
	 */
	function pre_get_posts( $query ) {
		// order by from eazyest-gallery options
		if ( eazyest_gallery()->post_type == $query->get( 'post_type' ) ) {
			if ( ! isset( $_REQUEST['orderby'] ) ) {
				// set sort order from options
				$option = explode( '-', eazyest_gallery()->sort_by() );
				$order_by = $option[0] == 'menu_order' ? 'menu_order' :  substr( $option[0], 5 );
				$query->set( 'orderby', $order_by );
				$query->set( 'order',   $option[1] );
			}

			// if manually sorted, subfolders show inline
			if ( is_admin() && 'menu_order-ASC' == eazyest_gallery()->sort_by() && empty( $query->query_vars['post_parent'] ) )
				$query->set( 'post_parent', 0 );
		}

		// show only images attached to folder if query-attachments
		if ( isset( $_REQUEST['action'] ) && 'query-attachments' == $_REQUEST['action'] ) {
			$post_id = $this->refered_by_folder();
			if ( $post_id )
				$query->set( 'post_parent', $post_id );
		}

		if ( is_tag() ) {
			$post_types = $query->get( 'post_type' );
			if ( ! $post_types || 'post' == $post_types )
				$post_types = array( 'post', eazyest_gallery()->post_type );
			if ( is_array( $post_types ) )
				$post_types[] = eazyest_gallery()->post_type;
			 $query->set( 'post_type', $post_types );
		}
		return $query;
	}

	/**
	 * Eazyest_FolderBase::save_post()
	 * Store meta data and attachment changes when a galleryfolder is saved
	 *
	 * @since 0.1.0 (r2)
	 * @param int $post_id
	 * @return void
	 */
	function save_post( $post_id ) {
		// don't run on autosave
		if ( defined( 'DOING_AUTOSAVE' ) || defined( 'LAZYEST_GALLERY_UPGRADING' ) )
			return;
		// don't  run if not initiated from edit post
		if ( ! isset( $_POST['action'] ) )
			return;

		// do not handle bulk edits here
		if ( isset( $POST['bulk-edit'] ) )
			return;
		$action = $_POST['action'];
		unset( $_POST['action'] );
		if ( isset( $_POST['post_type'] ) && $_POST['post_type'] == eazyest_gallery()->post_type ) {
			// only do this for post type galleryfolder
			$this->save_gallery_path( $post_id );
			$this->save_attachments( $post_id );
			$this->save_subfolders( $post_id );
			$this->save_post_status( $post_id );
		}
		$_POST['action'] = $action;
	}

	/**
	 * Eazyest_FolderBase::copy_timestamp()
	 * Eazyest Gallery uses created timestamp for post_date.
	 *
	 * @since 0.1.0 (r66)
	 * @uses get_post_type() to check if it is an attachment
	 * @uses apply_filters() for 'eazyest_gallery_copy_timestamp' (true)
	 * @uses get_post()
	 * @uses get_gmt_from_date() to calculate post_date_gmt
	 * @uses wp_update_post() to save changed dates
	 * @param int $post_id
	 * @return void
	 */
	function copy_timestamp( $post_id ) {
		if ( 'attachment' != get_post_type( $post_id ) )
			return;

		if ( ! apply_filters( 'eazyest_gallery_copy_timestamp', true ) )
			return;

		$metadata = wp_get_attachment_metadata( $post_id );
		if ( ! empty( $metadata['image_meta']['created_timestamp'] ) ) {
			$attachment = get_post( $post_id, ARRAY_A );
			$datetime = date( 'Y-m-d H:i:s', $metadata['image_meta']['created_timestamp'] );
			$changed = false;
			if ( $datetime != $attachment['post_date'] ) {
				$attachment['post_date'] = $datetime;
				$changed = true;
			}
			$datetime_gmt = get_gmt_from_date( $datetime );
			if ( $datetime_gmt != $attachment['post_date_gmt'] ) {
				$attachment['post_date_gmt'] = $datetime_gmt;
				$changed = true;
			}
			if ( $changed )
				wp_update_post( $attachment );
		}
	}

	/**
	 * Eazyest_FolderBase::save_attachment()
	 * When a single attachment is saved, copy fields values
	 *
	 * @since 0.1.0 (r2)
	 * @uses get_post()
	 * @uses get_post_type()
	 * @param mixed $post_id
	 * @return
	 */
	function save_attachment( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) || defined( 'LAZYEST_GALLERY_UPGRADING' ) )
			return;
		// don't run if not initiated from edit post
		if ( ! isset( $_POST['action'] ) )
			return;
		if ( isset( $_POST['post_type'] ) && $_POST['post_type'] == 'attachment' ) {
			$attachment = get_post( $post_id );
			if ( eazyest_gallery()->post_type == get_post_type( $attachment->post_parent ) ) {
				if ( empty( $attachment->post_excerpt ) ) {
					$attachment->post_excerpt = $attachment->post_title;
				}
				wp_update_post( $attachment );
			}
			$this->copy_timestamp( $post_id );
		}
	}

	/**
	 * Eazyest_FolderBase::save_post_status()
	 * Save post_status 'hidden' for hidden folders
	 *
	 * @since 0.1.0 (r96)
	 * @uses get_post() to retrieve post fields
	 * @uses wp_update_post() to save post
	 * @param int $post_id just saved post
	 * @return void
	 */
	function save_post_status( $post_id ) {
		if(  isset( $_POST['visibility'] ) && 'hidden' == $_POST['visibility'] ) {
			$post = get_post( $post_id, ARRAY_A );
			$post['post_status'] = 'hidden';
			unset( $_POST['action'] );
			wp_update_post( $post );
		}
	}

	/**
	 * Eazyest_FolderBase::save_gallery_path()
	 * Save and/or create the file system path for this folder
	 *
	 * @since 0.1.0 (r2)
	 * @uses wp_unique_post_slug()
	 * @uses sanitize_title()
	 * @uses wp_mkdir_p()
	 * @uses trailingslashit()
	 * @uses update_post_meta()
	 * @param int $post_id
	 * @return void
	 */
	function save_gallery_path( $post_id ) {

		// don't process if gallery_path is already set'
		$current_path = ezg_get_gallery_path( $post_id );
		if ( ! empty( $current_path ) )
			return;

		$gallery_path = isset( $_POST['gallery_path'] ) ? $_POST['gallery_path'] : '';

		// when gallery path is not set, construct one
		if ( '' == $gallery_path ) {
			// use post_name for folder name or sanitize title if not set
			$gallery_path = isset( $_POST['post_name'] ) ? $_POST['post_name'] : '';

			if ( ( '' == $gallery_path ) ) {
	  		// post name has not been set yet, but we need a slug to make a directory to store images
				$gallery_path = wp_unique_post_slug(
					sanitize_title( $_POST['post_title'] ),
					$post_id,
					'fake',
					eazyest_gallery()->post_type,
					$_POST['post_type']
				);
			}

			// possibly append to post parent
			if ( isset( $_POST['post_parent'] ) ) {
				$parent_path = ezg_get_gallery_path( $_POST['post_parent'] );
				$gallery_path = '' == $parent_path ? $gallery_path : trailingslashit( $parent_path ) . $gallery_path;
			}

			// check directory on file system
			$new_directory = eazyest_gallery()->root() . $gallery_path;
			if ( ! is_dir( $new_directory ) )
				wp_mkdir_p( $new_directory );
			if ( is_dir( $new_directory ) ) {
				// only save when gallery path exists
				ezg_update_gallery_path( $post_id, $gallery_path );
			}
		}
	}

	/**
	 * Eazyest_FolderBase::save_attachments()
	 * Save attachments fields edited in the attachment list table	 *
	 *
	 * @since 0.1.0 (r2)
	 * @uses get_post()
	 * @uses wp_update_post()
	 * @param int $post_id ID for attachment parent folder
	 * @return void
	 */
	function save_attachments( $post_id ) {
		if ( isset( $_POST['attachment'] ) && is_array( $_POST['attachment'] ) ) {
			$reordered = isset( $_POST['gallery-changed-media'] ) && $_POST['gallery-changed-media'];
			foreach( $_POST['attachment'] as $item_id => $fields ) {
				$attachment = get_post( $item_id );
				foreach( $fields as $field => $value ) {
					$attachment->$field = stripslashes( sanitize_text_field( $value ) );
					if ( $reordered )
						$attachment->menu_order = array_search( $attachment->ID, explode( ' ', $_POST['gallery-order-media'] ) );
				}
				if ( empty( $attachment->post_title ) ) {
					$pathinfo = pathinfo( $attachment->guid );
					if ( $this->replace_dashes() )
						$attachment->post_title = str_replace( array( '-', '_' ), ' ', $pathinfo['filename'] );
					else
						$attachment->post_title = $pathinfo['filename'];
				}
				if ( empty( $attachment->post_excerpt ) )
					$attachment->post_excerpt = $attachment->post_title;
				wp_update_post( $attachment );
			}
		}
	}

	/**
	 * Eazyest_FolderBase::save_subfolders()
	 * Save subfolders menu order for his folder
	 *
	 * @param integer $post_id
	 * @return void
	 */
	function save_subfolders( $post_id ) {
		if ( isset( $_POST['gallery-changed-pages'] ) && $_POST['gallery-changed-pages'] ) {
			$gallery_order = explode( ' ', $_POST['gallery-order-pages'] );
			foreach( $gallery_order as $menu_order => $item_id ) {
				$subfolder = get_post( $item_id );
				if ( $menu_order != $subfolder->menu_order ) {
					$subfolder->menu_order = $menu_order;
					wp_update_post( $subfolder );
				}
			}
		}
	}

	/**
	 * Eazyest_FolderBase::goto_parent()
	 * Move file system path if parent-directory gets deleted and post_parent has other directory
	 *
	 * @since 0.1.0 (r2)
	 * @uses get_post_meta()
	 * @uses update_post_meta()
	 * @uses get_children()
	 * @uses wp_update_post(
	 * @uses wp_die()
	 * @param mixed $sub_id
	 * @return void
	 */
	function goto_parent( $sub_id ) {
		$sub = get_post( $sub_id );

		$sub_path    = ezg_get_gallery_path( $sub->ID );
		$parent_path = ezg_get_gallery_path( $sub->post_parent );
		$new_path = $parent_path . '/' . basename( $sub_path );

		// check if this uploaded folder is writable
		if ( ! is_writable( eazyest_gallery()->root() . $sub_path ) )
			wp_die( __( 'Eazyest Gallery cannot access one or more folders on your server', 'eazyest-gallery' ) );

		// rename path in filesystem
		if ( rename( eazyest_gallery()->root() . $sub_path, eazyest_gallery()->root() . $new_path ) ) {
			// change metadata
			ezg_update_gallery_path( $sub->ID, $new_path, $sub_path );
			update_post_meta( $sub->ID, '_gallery_path', $new_path, $sub_path );
			// check attachments
			$attachments = get_children(  array( 'post_parent' => $sub_id, 'post_type' => 'attachment' )  );
			if ( ! empty( $attachments ) ) {
				foreach( $attachments as $attachment ) {
					// update each attachment
					$old_attached = get_post_meta( $attachment->ID, '_wp_attached_file', true );
					update_post_meta( $attachment->ID, '_wp_attached_file', $new_path . '/' . basename( $old_attached ), $old_attached );
					$attachment->guid = eazyest_gallery()->address() . $new_path . '/' . basename( $old_attached );
					wp_update_post( $attachment );
				}
			}
		} else {
			wp_die( __( 'Eazyest Gallery could not rename folder in file system', 'eazyest-gallery' ) );
		}
	}

	/**
	 * Eazyest_FolderBase::clear_dir()
	 * Remove directory and all files and sub-directories
	 *
	 * @since 0.1.0 (r2)
	 * @uses trailingslashit
	 * @param mixed $directory
	 * @return void
	 */
	function clear_dir( $directory ) {
		if ( empty( $directory ) )
			return;

		$directory = trailingslashit( $directory );

		// never delete when not in gallery
		if ( false === strpos( $directory, eazyest_gallery()->root() ) )
			return;

		// never delete when directory is gallery root
		if ( $directory == eazyest_gallery()->root() )
			return;

		if ( is_dir( $directory ) ) {
			if ( $handle = opendir( $directory ) ) {
				while( $file = readdir( $handle ) ) {
					if (	!	in_array(	$file, array(	'.', '..'	)	)	) {
						if ( is_file( $directory . $file ) )
							@unlink( $directory . $file );
						else if ( is_dir( $directory . $file ) )
							$this->clear_dir ( $directory . $file );
					}
				}
				@rmdir( $directory );
			}
			if ( is_resource( $handle ) )
				closedir( $handle );
		}
	}

	/**
	 * Eazyest_FolderBase::before_delete_post()
	 * Die if user attempts to delete a parent folder
	 * Delete the gallery directory for the just deleted folder
	 * Sub-directories may exist if user has selected another parent in the WordPress admin
	 * Move sub-directories to parent folder and change attachment metadata
	 *
	 * @since 0.1.0 (r2)
	 * @uses get_post_type()
	 * @uses get_children()
	 * @uses wp_die()
	 * @param mixed $postid
	 * @return void
	 */
	public function before_delete_post( $post_id ) {
		// check delete post for post_type galleryfolder
		if ( eazyest_gallery()->post_type == get_post_type( $post_id ) ) {
			// do not delete folder if it has sibbling WP_Posts
			if ( $this->has_subfolders( $post_id ) ) {
				wp_die( __( 'You cannot delete a parent folder', 'eazyest-gallery' ) );
			}
			$gallery_path = ezg_get_gallery_path( $post_id );
			// if gallery_path is not set do not delete anything
			if ( empty( $gallery_path )  || ! is_dir( eazyest_gallery()->root() . $gallery_path ) )
				return;

			// if it has subdirectories, but folder has changed parent, relocate directory and attachments
			// links in posts will be broken, but images still exist
			$subdirectories = $this->get_subdirectories( $post_id );
			if ( ! empty( $subdirectories ) ) {
				foreach( $subdirectories as $subdirectory ) {
					$path = $gallery_path . '/' . $directory;
					$sub_id = $this->get_folder_by_path( $path );
					$this->goto_parent( $sub_id );
				}
			}
			/** remove all attachments and files in this subfolder/directory
			 * @todo we need a cron job for this to prevent out of execution time errors
			 */
			$args = array(
				'post_type'      => 'attachment',
				'post_parent'    => $post_id,
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'fields'         => 'ids',
			);
			$query = new WP_Query( $args );
			if ( $query->have_posts() ) {

				$attachment_transient = $query->posts;
				if ( $transient = get_transient( 'eazyest_gallery_delete_attachments' ) ) {
					$attachment_transient = array_merge( $transient, $attachment_transient );
				}
				set_transient(  'eazyest_gallery_delete_attachments', $attachment_transient );
			}
			$delete_dir = eazyest_gallery()->root() . $gallery_path;
			// now remove the folder and all files from the server
			$this->clear_dir( $delete_dir );
		}
	}

	/**
	 * Eazyest_FolderBase::get_subdirectories()
	 * Get an array of subdirectory paths
	 *
	 * @since 0.1.0 (r2)
	 * @uses get_post_meta()
	 * @param integer $post_id
	 * @return array
	 */
	public function get_subdirectories( $post_id ) {
		$directory = ezg_get_gallery_path( $post_id );
		if ( ! is_dir( eazyest_gallery()->root() . $directory ) )
			return null;
		$paths = $this->get_folder_paths( $post_id );
		foreach( $paths as $key => $path ) {
			if ( false === strpos( $path, $directory ) || strlen( $path ) == strlen( $directory ) )
				unset( $paths[ $key] );
		}
		return $paths;
	}

	/**
	 * Eazyest_FolderBase::get_subfolders()
	 *
	 * @since 0.1.0 (r2)
	 * @uses get_children()
	 * @param int $post_id
	 * @return array of WP_Post subfolders
	 */
	public function get_subfolders( $post_id ) {
		return get_children( array( 'post_parent' => $post_id, 'post_type' => eazyest_gallery()->post_type ) );
	}

	/**
	 * Eazyest_FolderBase::has_subfolders()
	 * Check if folder has subfolders
	 *
	 * @since 0.1.0 (r2)
	 * @param mixed $post_id
	 * @return
	 */
	public function has_subfolders( $post_id ) {
		$has_subfolders = $this->get_subfolders( $post_id );
		return ! empty( $has_subfolders );
	}

	/**
	 * Eazyest_FolderBase::get_posted_paths()
	 * Selects all posted gallery paths from postmeta
	 * If $post_id is set, search for folders child of $post_id
	 *
	 * @since 0.1.0 (r2)
	 * @uses wpdb::get_col()
	 * @param string $reset
	 * @return array
	 */
	private function get_posted_paths( $post_id = '', $cached = 'cached' ) {

		global $wpdb;
		$query = "SELECT {$wpdb->postmeta}.meta_value FROM {$wpdb->postmeta}, {$wpdb->posts}
							WHERE $wpdb->posts.ID=$wpdb->postmeta.post_id";
		if ( '' != $post_id )
			$query .= "
							AND $wpdb->posts.post_parent={$post_id}";
		$query .= "
							AND {$wpdb->postmeta}.meta_key='_gallery_path'";
		$this->posted_paths = array( 'post_id' => $post_id, 'folders' => $wpdb->get_col( $query ) );
		return $this->posted_paths['folders'];
	}

	/**
	 * Eazyest_FolderBase::excluded_folders()
	 * Folder names that have a special purpose in file system or in Eazyest Gallery
	 * These folders should not be indexed
	 * thumbnails and other sizes are stored in subfolder '_cache'
	 *
	 * @since 0.1.0 (r2)
	 * @uses apply_filters()
	 * @return array
	 */
	function excluded_folders() {
		$excluded_folders = apply_filters( 'eazyest_gallery_excluded_folders',
			array(
				'cgi-bin',
				'thumbs',
				'slides',
				)
			);
		return apply_filters( 'eazyest_excluded_folders', $excluded_folders );
	}

	/**
	 * Eazyest_FolderBase::valid_dir()
	 * Checks if the directory is valid to open as EazyestFolder
	 * Exclude excluded_folders() and any folders starting with . or _
	 *
	 * @since lazyest-gallery 1.0.0
	 * @param string $adir
	 * @return bool
	 */
	function valid_dir( $adir ) {
		if ( ! is_dir( $adir ) )
			return false;
		$valid = ! in_array( basename( $adir ), $this->excluded_folders() ) &&
			'_' != substr( basename( $adir ), 0, 1 )  &&
			'.' != substr( basename( $adir ), 0, 1 );
		return $valid;
	}

	/**
	 * Eazyest_FolderBase::_dangerous()
	 *
	 * @since lazyest-gallery 1.1.0
	 * @uses apply_filters()
	 * @return array containg directories in which the gallery should not be.
	 */
	private function _dangerous() {
		// potentially dangerous subdirs in wp-content
		$content_dirs = array( 'themes', 'plugins', 'languages', 'upgrade', 'cache', 'wptouch-data' );
		// WordPress core dirs
		$dangerous = array(	'wp-admin',	'wp-includes'	);
		foreach( $content_dirs as $dir )
			$dangerous[] = 'wp-content/' . $dir;
		return apply_filters( 'eazyest_dangerous_paths', $dangerous );
	}

	/**
	 * Eazyest_FolderBase::is_dangerous()
	 * Check if the directory selected for the gallery could break wordpress
	 *
	 * @since lazyest-gallery 1.1.0
	 * @uses trailingslashit()
	 * @uses untrailingslashit()
	 * @param string $directory
	 * @return bool
	 */
	function is_dangerous( $directory ) {
		$directory = trailingslashit( str_replace( '\\', '/', $directory ) );
		// check if gallery folder is not on server root
		if ( $directory == '/' )
	 		return true;
		// see if gallery is not in a WordPress core directory
		$dangerous = $this->_dangerous();
		foreach ( $dangerous as $path ) {
			$notok = strpos( $directory, $path );
			if ( false !== $notok )
				return true;
		}

		if ( defined(  'WP_CONTENT_DIR' ) ) {
			$content_dir = trailingslashit( str_replace( '\\', '/', WP_CONTENT_DIR ) );
			if ( $directory == $content_dir )
				return true;
		}

		// check if gallery is not WordPress wp-content even if it is not WP_CONTENT_DIR
		if ( strlen( $directory ) > 11 ) {
			if ( 'wp-content' == basename( untrailingslashit( $directory ) ) )
				return true;
		}

		$upload_dir = wp_upload_dir();
		$basedir = trailingslashit( str_replace( '\\', '/', $upload_dir['basedir'] ) );
		if ( $directory == $basedir )
			return true;

		// check if WordPress is not in a gallery subirectory
		$subdirs = $this->get_folder_paths( $directory );
		if ( ! empty( $subdirs ) ) {
			foreach( $subdirs as $dir ) {
				if ( false !== strpos( $dir, 'wp-includes' ) )
					return true;
			}
		}

		return false;
	}

	/**
	 * Eazyest_FolderBase::sort_paths()
	 * usort callback to sort paths smallest length first
	 *
	 * @since 0.1.0 (r2)
	 * @param mixed $a
	 * @param mixed $b
	 * @return int
	 */
	private function sort_paths( $a, $b ) {
		return strlen( $a ) - strlen( $b );
	}

	/**
	 * Eazyest_FolderBase::_get_folder_paths()
	 * Recursively collect all folder paths from the gallery file system
	 *
	 * @since 0.1.0 (r2)
	 * @param string $root
	 * @return array
	 */
	private function _get_folder_paths( $root = '' ) {
		$folder_paths = array();
		$root = ( empty( $root ) ) ? untrailingslashit( eazyest_gallery()->root() ) : $root;
		if ( $dir_handler = @opendir( $root ) ) {
			while ( false !== ( $folder_path = readdir( $dir_handler ) ) ) {
				$folder_path = trailingslashit( $root ) . $folder_path;
				if ( $this->valid_dir( $folder_path ) ) {
					$folder_paths[] = utf8_encode( substr( str_replace( '\\', '/', $folder_path), strlen( eazyest_gallery()->root() ) ) );
					$sub_paths = $this->_get_folder_paths( $folder_path );
					if ( is_array( $sub_paths ) && ! empty( $sub_paths ) )
						$folder_paths = array_merge( $folder_paths, $sub_paths );
				} else {
					continue;
				}
			}

			@closedir( $dir_handler );
			usort( $folder_paths, array( $this, 'sort_paths' ) );
			return $folder_paths ;
		}
	}

	/**
	 * Eazyest_FolderBase::get_folder_paths()
	 * Get all folder paths relative to gallery root
	 * If $post_id != 0, it will return all folder_path children
	 *
	 * @since 0.1.0 (r2)
	 * @param integer $post_id
	 * @param string $cache
	 * @return array
	 */
	function get_folder_paths( $post_id = 0, $cached = 'cached' ) {
		if ( 'cached' == $cached && ! empty( $this->folder_paths ) ) {
			if ( $this->folder_paths['post_id'] == $post_id )
				return ( $this->folder_paths['folders'] );
		}
		$gallery_path = ezg_get_gallery_path( $post_id );
		$root = ( empty( $gallery_path ) ) ? '' : eazyest_gallery()->root() . $gallery_path;
		unset( $this->folder_paths );
		$this->folder_paths = array( 'post_id' => $post_id, 'folders' => $this->_get_folder_paths( $root ) );
		return $this->folder_paths['folders'];
	}

	/**
	 * Eazyest_FolderBase::get_folder_by_path()
	 *
	 * @since 0.1.0 (r2)
	 * @uses wpdb::get_col()
	 * @uses wpdb::prepare()
	 * @param mixed $folder_path
	 * @return
	 */
	function get_folder_by_path( $folder_path ) {
		$folder = array();
		global $wpdb;
		$folder = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key=%s AND meta_value=%s", '_gallery_path', $folder_path ) );
		return count( $folder ) ? $folder[0] : 0;
	}

	/**
	 * Eazyest_FolderBase::get_folder_by_string()
	 * Returns ID for galleryfolder identified by directory name
	 * Also searches to find sanitized directory names
	 *
	 * @example <code>$this->get_folder_by_string( 'Foldername/Old Name/' );</code>
	 * @since 0.1.0 (r2)
	 * @uses get_page_by_path()
	 * @param string $folder_string
	 * @return int post ID, 0 if not found
	 */
	function get_folder_by_string( $folder_string = '' ) {
		if ( ! empty( $folder_string ) ) {
			$post = get_page_by_path( $folder_string, OBJECT, eazyest_gallery()->post_type );
			if ( ! empty( $post ) )
				return $post->ID;
			else if ( $id = $this->get_folder_by_path( untrailingslashit( $folder_string ) ) )
				return $id;
			else {
				global $wpdb;
				$results = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = %s", basename( $folder_string ), eazyest_gallery()->post_type ), ARRAY_A );
				if ( count( $results ) )
					return $results[0]['ID'];
			}
		}
		return 0;
	}

	/**
	 * Eazyest_FolderBase::get_folder_children()
	 * Returns an array of post IDs for all generations children of a folder
	 *
	 * @since 0.1.0 (r2)
	 * @uses wpdb
	 * @param int $folder_id
	 * @return array
	 */
	function get_folder_children( $folder_id ) {
		global $wpdb;
		$folders = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_parent FROM $wpdb->posts WHERE post_type = %s AND post_status IN ('inherit', 'publish')", eazyest_gallery()->post_type ), ARRAY_A );
		$children = array();
		foreach( $folders as $folder ) {
			if ( $folder['post_parent'] == $folder_id ) {
				$children[] = $folder['ID'];
				if ( $grandchildren = $this->get_folder_children( $folder['ID'] ) )
					$children = array_merge( $children, $grandchildren );
			}
		}
		return $children;
	}

	/**
	 * Eazyest_FolderBase::get_parent()
	 * Get the post id based on a folder path
	 *
	 * @since 0.1.0 (r2)
	 * @uses untrailingslashit()
	 * @param string $folder_path
	 * @return int post_id for parent folder
	 */
	function get_parent( $folder_path ) {
		$parent_path = untrailingslashit( substr( $folder_path, 0, - strlen( basename( $folder_path ) ) -1 ) );
		return $this->get_folder_by_path( $parent_path );
	}

	/**
	 * Eazyest_FolderBase::sanitize_dirname()
	 * Sanitize a folder directory name
	 *
	 * @since 0.1.0 (r2)
	 * @param string $folder_path
	 * @return string santized folder path
	 */
	function sanitize_dirname( $folder_path ) {
		$folder_path = rtrim( $folder_path, '/' );
		$parts = explode( '/', $folder_path );
		// sanitize full path
		foreach( $parts as $key => $part )
			$parts[$key] = sanitize_title( $part );
		$new_path = implode( '/', $parts );
		return $new_path;
	}

	/**
	 * Eazyest_FolderBase::sanitize_folder()
	 * Sanitize a folder directory name in file system to prevent illegible image urls
	 *
	 * @since 0.1.0 (r2)
	 * @uses sanitize_title
	 * @param string $folder_path raw path ( utf8_encoded )
	 * @return string sanitized path
	 */
	function sanitize_folder( $folder_path = '' ) {
		if ( '' != $folder_path ) {
			$root = eazyest_gallery()->root();

			if ( empty( $root ) || '/' == $root )
				wp_die( __( 'Something went terribly wrong while trying to rename a gallery folder.', 'eazyest-gallery' ) );

			$folder_path = rtrim( $folder_path, '/' );
			$parts = explode( '/', $folder_path );
			// sanitize full path
			foreach( $parts as $key => $part )
				$parts[$key] = sanitize_title( $part );
			$new_path = implode( DIRECTORY_SEPARATOR, $parts ) . DIRECTORY_SEPARATOR;
			// set only basename to raw path because parent folders have already been converted
			$parts[count( $parts )-1] = utf8_decode( basename( $folder_path ) );
			$old_path = implode( DIRECTORY_SEPARATOR, $parts ) . DIRECTORY_SEPARATOR;
			$old_dir = $root . $old_path;
			$new_dir = $root . $new_path;
			if ( @rename( $old_dir, $new_dir ) )
				return str_replace( DIRECTORY_SEPARATOR, '/', $new_path );
			else {
				return new WP_Error( 'error', sprintf( __( 'Could not rename folder from %1$s to %2$s', 'eazyest-gallery' ), $old_path, $new_path ) );
			}
		}
		return $folder_path;
	}

	/**
	 * Eazyest_FolderBase::name_is_posted()
	 * Check if post_name already exists in wpdb
	 *
	 * @since 0.1.0 (r2)
	 * @uses wpdb->get_col()
	 * @param string $post_name
	 * @return bool true if $post_name is found
	 */
	private function name_is_posted( $post_name ) {
		global $wpdb;
		$result = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_name = '$post_name'" );
		return ( ! empty( $result ) );
	}

	/**
	 * Eazyest_FolderBase::replace_dashes()
	 * Should dashes and underscores be replaced by spaces
	 *
	 * @since 0.1.0 (r2)
	 * @return bool
	 */
	function replace_dashes() {
		return apply_filters( 'eazyest_gallery_replace_dashes', true );
	}

	/**
	 * Eazyest_FolderBase::insert_folder()
	 * Used when a new folder is found in the gallery file system, but no galleryfolder is connected
	 * Insert a new galleryfolder post in the WordPress database
	 * Folders will be saved with status 'publish' because other new folders could be child folders
	 *
	 * @since 0.1.0 (r2)
	 * @uses sanitize_title()
	 * @uses is_wp_error()
	 * @uses get_error_message()
	 * @uses apply_filters()
	 * @uses wp_insert_post()
	 * @uses update_post_meta()
	 * @param string $folder_path
	 * @param integer $post_id
	 * @return int
	 */
	function insert_folder( $folder_path, $post_id = 0 ) {
		$post_parent = $post_id;

		$title = basename( $folder_path );
		if ( sanitize_title( $title ) != $title ) {
			// rename folder in file system to prevent awkward image urls
			$folder_path = $this->sanitize_folder( $folder_path );
		}
		if ( is_wp_error( $folder_path ) ) {
			return $folder_path;
		}

		if ( $post_id = $this->get_folder_by_path( $folder_path ) )
			return $post_id;

		if ( ! $post_parent ) {
			if ( strlen( basename( $folder_path ) ) < strlen( $folder_path )  ) {
				$post_parent = $this->get_parent( $folder_path );
			}
		}
		$add = 2;
		$post_name = sanitize_title( $title );
		while ( $this->name_is_posted( $post_name ) ) {
			$post_name .= '-' . $add++;
		}
		if ( $this->replace_dashes() )
			$title = str_replace( array( '-', '_' ), ' ', $title );
		$folder = array(
			'post_type'   => eazyest_gallery()->post_type,
			'post_title'  => $title,
			'post_name'   => $post_name,
			'post_status' => apply_filters( 'eazyest_gallery_folder_status', 'publish' ),
			'post_parent' => $post_parent
		);

		$post_id = wp_insert_post( $folder );
		if ( $post_id ) {
			ezg_update_gallery_path( $post_id,untrailingslashit( $folder_path ) );
			do_action( 'eazyest_gallery_insert_folder', $post_id );
		}
		return $post_id;
	}

	/**
	 * Eazyest_FolderBase::add_folders()
	 * Add folders to wpdb when user has (ftp) uploaded new folders
	 *
	 * @since 0.1.0 (r2)
	 * @param integer $post_id
	 * @return integer number of folders added
	 */
	function add_folders( $post_id ) {
		$this->get_posted_paths();
		$this->get_folder_paths( $post_id );
		$added = 0;
		$errors = array();
		if ( ! empty( $this->folder_paths['folders'] ) ) {
			foreach( $this->folder_paths['folders'] as $folder_name ) {
				$child_name = $this->sanitize_dirname( $folder_name );
				if ( ! in_array( $child_name, $this->posted_paths['folders'] ) ) {
					$result = $this->insert_folder( $folder_name, $post_id );
					if ( is_wp_error( $result ) ) {
						$errors[] = $result;
						continue;
					}
					$this->posted_paths['folders'][] = $child_name;
					$added++;
				}
			}
		}
		if ( ! empty( $errors ) )
			set_transient( 'eazyest_gallery_rename_errors', $errors, DAY_IN_SECONDS );

		return $added;
	}

	/**
	 * Eazyest_FolderBase::delete_folders()
	 * Trashes folders when user has (ftp) deleted folders
	 *
	 * @since 0.1.0 (r2)
	 * @uses wp_trash_post
	 * @param integer $post_id
	 * @return integer number of folders deleted
	 */
	function delete_folders( $post_id ) {
		$this->get_posted_paths( $post_id );
		$this->get_folder_paths();
		$deleted = 0;
		if ( ! empty( $this->posted_paths['folders'] ) ) {
			// reverse array, we want to start to delete sibblings first
			$posted_paths = array_reverse( $this->posted_paths['folders'] );
			foreach( $posted_paths as $key => $path_name ) {
				if ( is_dir( eazyest_gallery()->root() . $path_name ) )
					continue;
				if ( ! in_array( $path_name, $this->folder_paths['folders'] ) ) {
					$folder_id = $this->get_folder_by_path( $path_name );
					if ( $folder_id )
						// trash folder
						wp_trash_post( $folder_id, false );
					$deleted++;
				}
			}
		}
		return $deleted;
	}

	/**
	 * Eazyest_FolderBase::get_new_folders()
	 * Check if folders have been added or deleted
	 *
	 * @since 0.1.0 (r2)
	 * @param integer $post_id
	 * @return array ( 'add' => int, 'delete' => int ) folders to be added and/or deleted
	 */
	function get_new_folders( $post_id = 0 ) {
		$this->get_posted_paths( $post_id, 'new' );
		$this->get_folder_paths( $post_id, 'new' );
		$changes = array(
			'add'    => 0,
			'delete' => 0,
		);
		$changes['delete'] = $this->delete_folders( $post_id );
		$changes['add']    = $this->add_folders( $post_id );
		return $changes;
	}

	/**
	 * Eazyest_FolderBase::folders_collected()
	 *
	 * @since 0.1.0 (r2)
	 * @return integer
	 */
	public function folders_collected() {
		return $this->folders_collected;
	}

	/**
	 * Eazyest_FolderBase::collect_folders()
	 * Check all folders in file system if they exist as posted galleryfolder
	 *
	 * @since 0.1.0 (r2)
	 * @return array
	 */
	public function collect_folders( $post_id = 0 ) {
		$new_folders = $this->get_new_folders( $post_id );

		$this->folders_collected = $new_folders['add'] - $new_folders['delete'];
		return $this->folders_collected;
	}

	/**
	 * Eazyest_FolderBase::save_gallery_order()
	 *
	 * @since 0.1.0 (r2)
	 * @uses wp_update_post()
	 * @param mixed $gallery_order
	 * @return integer number of posts changed
	 */
	public function save_gallery_order( $gallery_order = array() ) {
		$updated    = 0;
		if ( ! empty( $gallery_order ) ) {
			foreach( $gallery_order as $menu_order => $post_id ) {
				$post = get_post( $post_id );
					if ( $post->menu_order != $menu_order ) {
						$post->menu_order = $menu_order;
						wp_update_post( $post );
						$updated++;
					}
			}
		}
		return $updated;
	}

	/**
	 * Eazyest_FolderBase::_sort_menu_order()
	 * usort array of posts by menu_order
	 *
	 * @since 0.1.0 (r2)
	 * @param array $a
	 * @param array $b
	 * @return integer
	 */
	private function _sort_menu_order( $a, $b ) {
		return intval( $a['menu_order'] ) - intval( $b['menu_order'] );
	}

	/**
	 * Eazyest_FolderBase::move_folder()
	 * Move a folder to top or to bottom of the list
	 * This function should hardly ever be called.
	 * Manually sorting should be handeled by AJAX
	 *
	 * @since 0.1.0 (r2)
	 * @uses wp_update_post()
	 * @param integer $post_id
	 * @param string $direction
	 * @return void
	 */
	public function move_folder( $post_id, $direction = 'to_top' ) {
		$mover       = get_post( $post_id );
		$post_parent = $mover->post_parent;
		$post_type   = eazyest_gallery()->post_type;

		global $wpdb;
		$updated = 0;
		$folder_ids = $wpdb->get_results( "SELECT ID,menu_order FROM $wpdb->posts WHERE post_type='$post_type' AND post_parent=$post_parent ORDER BY menu_order ASC", ARRAY_A );
		$key = array_search( array( 'ID' => $mover->ID, 'menu_order' => $mover->menu_order ), $folder_ids );
		if ( false !== $key ) {
			$folder_ids[$key]['menu_order'] = 'to_top' == $direction ? -1 : count( $folder_ids );
			usort( $folder_ids, array( $this, '_sort_menu_order' ) );
			$menu_order = 0;
			foreach( $folder_ids as $item ) {
				if ( $item['menu_order'] != $menu_order ) {
					$folder = array(
						'ID' => $item['ID'],
						'menu_order' => $menu_order
					);
					wp_update_post( $folder );
					$updated++;
				}
				$menu_order++;
			}
		}
		return $updated;
	}

	// Functions related to images/attachments -----------------------------------

	public function get_attachment_by_filename( $filename ) {
		global $wpdb;
		$guid = eazyest_gallery()->address . $filename;
		$ids = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid=%s", $guid ), ARRAY_A );
		if ( empty( $ids ) )
			return false;
		return $ids[0]['ID'];

	}

	/**
	 * Eazyest_FolderBase::get_attached_file()
	 * Filter for get_attached_file()
	 * Returns filename in gallery
	 *
	 * @since 0.1.0 (r2)
	 * @uses get_post_meta()
	 * @param string $file
	 * @param int $post_id
	 * @return string file
	 */
	public function get_attached_file( $file, $post_id ) {

		if ( $this->is_gallery_image( $post_id ) ) {
			$attached_file = get_post_meta( $post_id, '_wp_attached_file', true );
			$file = eazyest_gallery()->root() . $attached_file;
		}

		return $file;
	}

	/**
	 * Eazyest_FolderBase::get_attachment_url()
	 * Filter for get_attachment_url()
	 * Returns url for image in gallery
	 *
	 * @since 0.1.0 (r2)
	 * @uses get_post_meta()
	 * @param string $url
	 * @param int $post_id
	 * @return string
	 */
	public function get_attachment_url( $url, $post_id ) {

		if ( $this->is_gallery_image( $post_id ) ) {
			$attached_file = get_post_meta( $post_id, '_wp_attached_file', true );
			$url = eazyest_gallery()->address() . $attached_file;
		}
		return $url;
	}

	/**
	 * Eazyest_FolderBase::create_file_in_uploads()
	 * When a file is created in cache, return the cache path.
	 *
	 * @since 0.1.0 (r61)
	 * @uses get_transient())
	 * @param string $file
	 * @param int $post_id
	 * @return string
	 */
	function create_file_in_uploads( $file, $post_id ) {
		if ( $this->is_gallery_image( $post_id ) ) {
			if ( $saved = get_transient( 'eazyest_gallery_saved_in_cache' ) ) {
				if ( basename( $file) == basename( $saved ) ){
					$file = $saved;
				}
			}
		}
		return $file;
	}

	/**
	 * Eazyest_FolderBase::get_attachment_image_src()
	 * Like wp_get_attachment_images_src(), returns array of url and width, height.
	 *
	 * @since 0.1.0 (r48)
	 * @uses wp_get_attachment_image_src() for non-gallery images
	 * @param int $attachment_id
	 * @param string $size
	 * @return array (url, width, height)
	 */
	public function get_attachment_image_src( $attachment_id, $size='thumbnail' ) {
		if ( $this->is_gallery_image( $attachment_id ) ) {
			return $this->image_downsize( null, $attachment_id, $size );
		} else {
			return wp_get_attachment_image_src($attachment_id, $size );
		}
	}

	/**
	 * Eazyest_FolderBase::get_folder_images()
	 * Get all filenames of images currently in the folder
	 *
	 * @since 0.1.0 (r2)
	 * @uses trailingsalshit
	 * @param int $post_id
	 * @return array
	 */
	private function get_folder_images( $post_id, $cached = 'cached' ) {

		if ( 'cached' == $cached && ! empty( $this->folder_images ) ) {
			if ( $this->folder_images['post_id'] == $post_id )
				return ( $this->folder_images['images'] );
		}
		$gallery_path = ezg_get_gallery_path( $post_id );
		if ( empty( $gallery_path ) )
			return array();

		unset( $this->folder_images );
		$this->folder_images = array( 'post_id' => $post_id, 'images' => array() );

		$folder_path = eazyest_gallery()->root() . $gallery_path;
		if ( $dir_content = @opendir( $folder_path ) ) {
			while ( false !== ( $dir_file = readdir( $dir_content ) ) ) {
        if ( ! is_dir( $dir_file ) && ( 0 < preg_match( "/^.*\.(jpg|gif|png|jpeg)$/i", $dir_file ) ) ) {
          $this->folder_images['images'][] = $gallery_path . '/' . utf8_encode( basename( $dir_file ) );
        }
			}
      @closedir( $dir_content );
		} else {
	    return false;
		}
    return $this->folder_images['images'];
	}

	/**
	 * Eazyest_FolderBase::sanitize_filename()
	 * sanitize a filename for a new found image, but check if an image with sanitized name already exists
	 *
	 * @since 0.1.0 (r2)
	 * @uses sanitize_file_name()
	 * @uses trailingslashit
	 * @param string $filename
	 * @param integer $post_id
	 * @return string the sanitized filename
	 */
	function sanitize_filename( $filename, $post_id = 0 ) {
		$sanitized = sanitize_file_name( $filename ) ;
		if ( $post_id ) {
			$gallery_path = ezg_get_gallery_path( $post_id );
			$folder_path = eazyest_gallery()->root() . $gallery_path;
			if ( $sanitized != $filename ) {
				// filename changed after sanitizing
				$pathinfo = pathinfo( $sanitized );
				$i = 0;
				while ( file_exists( $folder_path . '/' . $sanitized ) ) {
					// renamed file exists
					$sanitized = $pathinfo['filename'] . '-' . ++$i . $pathinfo['extension'];
				}
				@rename( $folder_path . '/' . $filename, $folder_path . '/' . $sanitized );
			}
		}
		return $sanitized;
	}

	/**
	 * Eazyest_FolderBase::get_posted_images()
	 * Get all image attachments for a particular galleryfolder
	 *
	 * @since 0.1.0 (r2)
	 * @uses get_children()
	 * @param int $post_id
	 * @return array
	 */
	private function get_posted_images( $post_id, $cached = 'cached' ) {

		if ( 'cached' == $cached && ! empty( $this->posted_images ) ) {
			if ( $this->posted_images['post_id'] == $post_id )
				return ( $this->posted_images['images'] );
		}

		unset( $this->posted_images );
		$this->posted_images = array( 'post_id' => $post_id, 'images' => array() );
		$attachments = get_children( array(
			'post_parent'    => $post_id,
			'post_type'      => 'attachment',
			'post_mime_type' => 'image'
		) );
		if ( ! empty( $attachments ) ) {
			foreach( $attachments as $attachment ) {
				$this->posted_images['images'][] = substr( $attachment->guid, strlen( eazyest_gallery()->address() ) );
			}
		}
		return $this->posted_images['images'];
	}

	/**
	 * Eazyest_FolderBase::insert_image()
	 * Insert a new image found in a folder into the WP database
	 *
	 * @since 0.1.0 (r2)
	 * @uses wp_check_filetype()
	 * @uses trailingslashit()
	 * @uses wp_insert_attachment() to store attachment in database
	 * @uses wp_read_image_metadata() to get exif and iptc data
	 * @uses wp_update_attachment_metadata() to store exif and iptc data
	 * @param int $post_id
	 * @param string $filename
	 * @param string $title
	 * @return void
	 */
	function insert_image( $post_id, $filename, $title ) {
		$gallery_path = ezg_get_gallery_path( $post_id );
		$wp_filetype = wp_check_filetype( basename( $filename ), null );
		$title = preg_replace( '/\.[^.]+$/', '', basename( $title ) );
		if ( $this->replace_dashes() )
			$title = str_replace( array( '-', '_' ), ' ', $title );
		// set $_POST values as if we are uploading an image in Edit Folder screen
		$_POST['post_type'] = eazyest_gallery()->post_type;
		$_POST['post_id']   = $post_id;
  	$attachment = array(
     'guid' => eazyest_gallery()->address()  . $gallery_path . '/' . basename( $filename ),
     'post_mime_type' => $wp_filetype['type'],
     'post_title' => $title,
     'post_excerpt' => $title,
     'post_content' => '',
     'post_status' => 'inherit'
  	);
  	$attach_id = wp_insert_attachment( $attachment, $filename, $post_id );
  	if ( !is_wp_error( $attach_id ) ) {
			wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $filename ) );
			$this->copy_timestamp( $attach_id );
			return $attach_id;
		} else {
			return false;
		}
	}

	/**
	 * Eazyest_FolderBase::new_images()
	 * Count the number of (ftp) uploaded images in this folder
	 *
	 * @since 0.1.0 (r2)
	 * @param mixed $post_id
	 * @return array ( 'add' => int, 'delete' => int ) folders to be added and/or deleted
	 */
	function get_new_images( $post_id ) {
		$this->get_posted_images( $post_id, 'new' );
		$this->get_folder_images( $post_id, 'new' );
		$changes = array(
			'add'    => 0,
			'delete' => 0,
		);
		$changes['delete'] = $this->delete_attachments( $post_id );
		$changes['add']    = $this->add_images( $post_id );
		return $changes;
	}

	/**
	 * Eazyest_FolderBase::add_images()
	 * Add new images as attachments
	 *
	 * Add new names to post_images names because sanitized filenames could be equal
	 *
	 * @since 0.1.0 (r2)
	 * @uses trailingslashit()
	 * @param integer $post_id
	 * @return void
	 */
	function add_images( $post_id ) {
		$gallery_path = ezg_get_gallery_path( $post_id );
		$this->get_posted_images( $post_id );
		$this->get_folder_images( $post_id );
		$added = 0;
		$add_later = array();
		if ( ! empty( $this->folder_images['images'] ) ) {
			foreach( $this->folder_images['images'] as $image_name ) {
				$attach_name = $gallery_path . '/' . $this->sanitize_filename( basename( $image_name ), $post_id );
				$attach_file = eazyest_gallery()->root() . $attach_name;
				if ( ! in_array( $attach_name, $this->posted_images['images'] ) ) {
					if ( $added < $this->max_process_items )
						$this->insert_image( $post_id, $attach_file, $image_name  );
					$this->posted_images['images'][] = $attach_name;
					$added++;
				}
			}
		}
		if ( $added > $this->max_process_items ) {
			if ( $transient = get_transient( 'eazyest_gallery_add_attachments' ) ) {
				$transient[] = $post_id;
			}	else {
				$transient = array( $post_id );
			}
			set_transient(  'eazyest_gallery_add_attachments', $transient );
		}	else {
			if ( $transient = get_transient( 'eazyest_gallery_add_attachments' ) ){
				$transient = array_diff( $transient, array( $post_id ) );
				if ( count( $transient ) ) {
					set_transient( 'eazyest_gallery_add_attachments', $transient );
				}	else {
					delete_transient( 'eazyest_gallery_add_attachments' );
				}
			}
		}
		return $added;
	}

	/**
	 * Eazyest_FolderBase::delete_attachments()
	 * Delete attachments from WordPress database when original image has been (ftp) erased
	 *
	 * @since 0.1.0 (r2)
	 * @uses wp_delete_attachment()
	 * @param integer $post_id
	 * @return void
	 */
	function delete_attachments( $post_id ) {
		$this->get_posted_images( $post_id );
		$this->get_folder_images( $post_id );
		$deleted = 0;
		$delete_later = array();
		if ( ! empty( $this->posted_images['images'] ) ) {
			foreach( $this->posted_images['images'] as $key => $image_name  ) {
				if ( ! in_array( $image_name, $this->folder_images['images'] ) ) {
					$attachment_id = $this->get_attachment_by_filename( $image_name );
					if ( $deleted < $this->max_process_items )
						wp_delete_attachment( $attachment_id, true );
					else
						$delete_later[] = $attachment_id;
					unset( $this->posted_images['images'][$key] );
					$deleted++;
				}
			}
		}
		if ( ! empty( $delete_later ) ) {
			if ( $transient = get_transient( 'eazyest_gallery_delete_attachments' ) ) {
				$delete_later = array_merge( $delete_later, $transient );
			}
			set_transient(  'eazyest_gallery_delete_attachments', $delete_later );
		}
		return $deleted;
	}

	/**
	 * Eazyest_FolderBase::images_collected()
	 * Number of new (+) or deleted (-) images found the last time collect_images() run
	 *
	 * @since 0.1.0 (r2)
	 * @return integer
	 */
	public function images_collected() {
		return $this->images_collected;
	}

	/**
	 * Eazyest_FolderBase::collect_images()
	 * Get all images for a particular galleryfolder
	 * Check for new or deleted (ftp) uploaded images
	 *
	 * @since 0.1.0 (r2)
	 * @param int $post_id
	 * @return array
	 */
	public function collect_images( $post_id ) {
		// this loads the image name caches
		$new_images = $this->get_new_images( $post_id );
		$this->images_collected = $new_images['add'] - $new_images['delete'];
		return $this->images_collected ;
	}

	// Image resizing functions --------------------------------------------------

	/**
	 * Eazyest_FolderBase::image_downsize()
	 * This function filters the image_downsize($id, $size = 'medium')
	 * Returns the image url based on the gallery path instead of wp_upload path
	 *
	 * @since 0.1.0 (r2)
	 * @see wordpress/wp-includes/media.php
	 * @uses wp_get_attachment_metadata()
	 * @uses get_option() to get image dimensions
	 * @param string|array $resize
	 * @param int $post_id
	 * @param string $size
	 * @return array
	 */
	public function image_downsize( $resize, $post_id, $size ) {
		if ( ! $this->is_gallery_image( $post_id ) )
			return false;

		$defaults = array( 'thumbnail', 'medium', 'large' );

		$metadata = wp_get_attachment_metadata( $post_id );
		$attached = get_post_meta( $post_id, '_wp_attached_file', true );

		if ( ! file_exists( eazyest_gallery()->root() . $attached ) )
			return false;


		$pathinfo = pathinfo( $attached );
		$dir    = $pathinfo['dirname'];
		$name   = $pathinfo['basename'];

		if ( isset( $metadata['width'] ) && isset( $metadata['height'] ) ) {
			$width  = $metadata['width'];
			$height = $metadata['height'];
		} else {
			list( $width, $height ) = getimagesize( eazyest_gallery()->root() . $attached );
		}

		// $size is array, find corresponding size string
		if ( is_array( $size ) ) {
			// check if file exists for this size array
			$size_name = $pathinfo['filename'] . "-{$size[0]}x{$size[1]}" . $pathinfo['extension'];
			$size_file = eazyest_gallery()->root() . $dir . '/_cache/' . $size_name;
			if ( file_exists( $size_file ) ) {
				$dir  = $dir . '/_cache';
				$name = $size_name;
			} else {
				// find default size that fits best
				foreach( $defaults as $default ) {
					if ( $size[0] <= intval( get_option("{$default}_size_w" ) ) && $size[1] <= intval( get_option("{$default}_size_h" ) ) ) {
						$size = $default;
						break;
					}
				}
				if ( is_array( $size ) )
					$size = 'full';
			}
		}
		$is_intermediate = false;
		// get image name from metadata
		if ( isset( $metadata['sizes'] ) ) {
			// check again we could have changed $size
			if ( ! is_array( $size ) ) {
				if ( 'full' != $size && isset( $metadata['sizes'][$size] ) && isset( $metadata['sizes'][$size]['file'] ) ) {
					$name   = basename( $metadata['sizes'][$size]['file'] );
					$width  = $metadata['sizes'][$size]['width'];
					$height = $metadata['sizes'][$size]['height'];
					$dir = $dir . '/' . dirname( $metadata['sizes'][$size]['file'] );
					$is_intermediate = true;
				}
			}
		}
		$img_url = eazyest_gallery()->address . $dir .'/' . $name;
		list( $width, $height ) = image_constrain_size_for_editor( $width, $height, $size );

		return array( $img_url, $width, $height, $is_intermediate );
	}

	/**
	 * Eazyest_FolderBase::image_editors()
	 * Add 'Eazyest_Image_Editor' as first in the image editors array
	 *
	 * @since 0.1.0 (r36)
	 * @param array $editor_classes
	 * @return array
	 */
	public function image_editors( $editor_classes ) {
		require_once( eazyest_gallery()->plugin_dir . 'includes/class-eazyest-image-editor.php' );
		array_unshift( $editor_classes, 'Eazyest_Image_Editor' );
		return $editor_classes;
	}

	/**
	 * Eazyest_FolderBase::get_attachment_metadata()
	 * Filter for attachment metadata.
	 * Creates new metadata if metadata got lost e.g. when saved in another path than WP expected
	 *
	 * @since 0.1.0 (r61)
	 * @uses wp_generate_attachment_metadata()
	 * @uses get_attached_file()
	 * @uses update_post_meta()
	 * @param array $metadata
	 * @param int $post_id
	 * @return array
	 */
	function get_attachment_metadata( $metadata, $post_id ) {
		if ( ! $this->is_gallery_image( $post_id ) )
			return $metadata;

		if ( empty( $metadata ) && is_admin() ) {
			$metadata = wp_generate_attachment_metadata( $post_id, get_attached_file( $post_id ) );
			update_post_meta( $post_id, '_wp_attachment_metadata', $metadata );
		}
		return $metadata;
	}

	/**
	 * Eazyest_FolderBase::sizes_metadata()
	 * Update metadata for image sizes.
	 * All resized images are stored in subdirectory _cache
	 *
	 * @since 0.1.0 (r36)
	 * @uses get_post_meta() to get attached file
	 * @param array $metadata
	 * @param int $attachment_id
	 * @return array updated metadata
	 */
	function sizes_metadata( $metadata, $attachment_id ) {
		if ( ! $this->is_gallery_image( $attachment_id ) )
			return $metadata;

		$file = get_post_meta( $attachment_id, '_wp_attached_file', true );
		if ( false !== strpos( $file, eazyest_gallery()->root() ) )
			$file = substr( $file, strlen( eazyest_gallery()->root() ) );

		if ( ! isset( $metadata['file'] ) || $file != $metadata['file'] )
			$metadata['file'] = $file;

  	$orientation = 0;
		if ( function_exists( 'exif_read_data' ) ) {
			// run this when image has not been checked on rotation based on exif info
			if ( ! isset( $metadata['eazyest_exif'] ) ) {
				$filepath = eazyest_gallery()->root() . $file;
				if ( file_exists( $filepath ) && $exif = exif_read_data( $filepath ) ) {
					$orientation = isset ( $exif['Orientation'] ) ? $exif['Orientation'] : 0;
					// rotate original because many browser don't display the image correctly on the attachment page
					if ( in_array( $orientation, array( 3, 6, 8 ) ) && apply_filters( 'eazyest_gallery_rotate_original', true ) ) {
  					// action for other plugins to get image metadata before rotation clears it
						do_action( 'eazyest_gallery_before_rotation', $attachment_id );
						$image = wp_get_image_editor( eazyest_gallery()->root() . $file );
						$image->save( eazyest_gallery()->root() . $file );
					}
				}
			}
		}

		if ( in_array( $orientation, array( 6, 8) ) ) {
			$width = $metadata['width'];
			$metadata['width']  = $metadata['height'];
			$metadata['height'] = $width;
		}

  	if ( isset( $metadata['sizes'] ) ) {
  		foreach( $metadata['sizes'] as $key => $size ) {
  			$metadata['sizes'][$key]['file'] = '_cache/' . basename( $metadata['sizes'][$key]['file'] );
  			if ( in_array( $orientation, array( 6, 8) ) ) {
					$width = $metadata['sizes'][$key]['width'];
					$metadata['sizes'][$key]['width']  = $metadata['sizes'][$key]['height'];
					$metadata['sizes'][$key]['height'] = $width;
				}
  		}
  	}
  	$metadata['eazyest_exif'] = 1;
		return $metadata;
	}

	/**
	 * Eazyest_FolderBase::file_metadata()
	 * Update attachment file metadata for resized images in _temp folder.
	 *
	 * @since 0.1.0 (r36)
	 * @uses get_post() to get attachment
	 * @param array $metadata
	 * @param int $attachment_id
	 * @return array updated metadata
	 */
	function file_metadata( $metadata, $attachment_id ) {

		if ( ! $this->is_gallery_image( $attachment_id ) )
			return $metadata;

		$attachment = get_post( $attachment_id );
		$guid = $attachment->guid;

		// clear some files used in creating header-images
		if( $cache = get_transient( 'eazyest_gallery_created_cache' ) ) {
			if ( basename( $cache ) == basename( $guid ) && file_exists( $cache ) ) {
				$metadata = $cache;
				$attachment->guid = str_replace( eazyest_gallery()->root(), eazyest_gallery()->address(), $metadata );
				wp_update_post( $attachment );
				delete_transient( 'eazyest_gallery_created_cache' );
				if ( false !== strpos( $metatada, 'cropped-' ) ) {
					if ( $midsize = get_transient( 'eazyest_gallery_midsize' ) ){
						if ( file_exists( $midsize ) )
							@unlink( $midsize );
						delete_transient( 'eazyest_gallery_midsize' );
					}
				}
			} else if ( basename( $cache ) != basename( $guid ) && file_exists( $cache ) ) {
				$metadata = $cache;
				delete_transient( 'eazyest_gallery_created_cache' );
			}
		}
		$gallery_path = ezg_get_gallery_path( $attachment->post_parent );
		$pathinfo = pathinfo( $guid );
		if ( false === strpos( $metadata, $pathinfo['filename'] ) )
			$metadata = $gallery_path . '/' . $pathinfo['basename'];

		if ( false !== strpos( $metadata, eazyest_gallery()->address() ) )
			$metadata = substr( $metadata, strlen( eazyest_gallery()->address() ) );

		if ( false !== strpos( $metadata, eazyest_gallery()->root() ) )
			$metadata = substr( $metadata, strlen( eazyest_gallery()->root() ) );
		return $metadata;
	}

	/**
	 * Eazyest_FolderBase::update_attachment_metadata()
	 * Update metadata for gallery images.
	 * Filters WordPress 'update_attachment_metadata'
	 *
	 * @since 0.1.0 (r36)
	 * @uses wpdb;
	 * @uses  get_metadata() to check if value has changed
	 * @global $wpdb
	 * @param mixed $result
	 * @param int $object_id
	 * @param string $meta_key meta key value for wpdb->postmeta
	 * @param mixed $meta_value
	 * @param mixed $prev_value
	 * @return mixed null|mixed if nothing changed return null
	 */
	function  update_attachment_metadata( $result, $object_id, $meta_key, $meta_value, $prev_value ) {
		// only filter attachment metadata we need
		if ( ! in_array( $meta_key, array( '_wp_attachment_metadata', '_wp_attachment_backup_sizes', '_wp_attached_file' ) ) )
			return $result;

		// if nothing has changed, return
		if ( empty($prev_value) ) {
			$old_value = get_metadata( 'post', $object_id, $meta_key );
			if ( count( $old_value) == 1 ) {
				if ( $old_value[0] === $meta_value )
					return false;
			}
		}

		// only filter metadata for gallery images
		if ( ! $this->is_gallery_image( $object_id ) )
			return $result;

		if ( $meta_key == '_wp_attachment_metadata' )
			$meta_value = $this->sizes_metadata( $meta_value, $object_id );
		if ( $meta_key == '_wp_attached_file' )
			$meta_value = $this->file_metadata( $meta_value, $object_id );

		// add or change metavalue;
		global $wpdb;
		if ( ! $meta_id = $wpdb->get_var( $wpdb->prepare( "SELECT meta_id FROM $wpdb->postmeta WHERE meta_key = %s AND post_id = %d", $meta_key, $object_id ) ) )
			return add_metadata('post', $object_id, $meta_key, $meta_value);

		// process the metadata
		$_meta_value = $meta_value;
		$meta_value = maybe_serialize( $meta_value );

		$data  = compact( 'meta_value' );
		$where = array( 'post_id' => $object_id, 'meta_key' => $meta_key );

		if ( !empty( $prev_value ) ) {
			$prev_value = maybe_serialize($prev_value);
			$where['meta_value'] = $prev_value;
		}
		// now update the metadata
		$wpdb->update( $wpdb->postmeta, $data, $where );
		return true;
	}

	/**
	 * Eazyest_FolderBase::generate_attachment_metadata()
	 * Changes the resized image filenames because they are stored in the _cache directory.
	 *
	 * @since 0.1.0 (r178)
	 * @param array $metadata
	 * @param int $post_id
	 * @return array
	 */
	function generate_attachment_metadata( $metadata, $post_id ) {
		if ( $this->is_gallery_image( $post_id ) ) {
			if ( ! empty( $metadata ) ) {
				if ( isset( $metadata['sizes'] ) && count( $metadata['sizes'] ) ) {
					foreach( $metadata['sizes'] as $size => $data ) {
						if ( isset( $data['file'] ) )
							if ( false == strpos( $data['file'], '_cache' ) )
								$metadata['sizes'][$size]['file'] = '_cache/' . $metadata['sizes'][$size]['file'];
					}
				}
			}
		}
		return $metadata;
	}

	/**
	 * Eazyest_FolderBase::save_image_editor_file()
	 * Save edited files in a seprate folder to prevent them from being indexed as new.
	 *
	 * @since 0.1.0 (r36)
	 * @uses get_post() to get folder->ID
	 * @uses get_post_meta() to get gallery_path
	 * @uses wp_mkdir_p() to create temporaray folder
	 * @uses trailingslashit to build filename
	 * @param mixed $result
	 * @param string $filename
	 * @param object $image WP_Image_Editor
	 * @param string $mime_type
	 * @param int $post_id
	 * @return mixed WP_Image_Editor::save()
	 */
	function save_image_editor_file( $result, $filename, $image, $mime_type, $post_id ) {
		if ( ! $this->is_gallery_image( $post_id ) )
			return $result;

		$gallery_path = ezg_get_gallery_path( get_post( $post_id )->post_parent );
		$dirname = eazyest_gallery()->root() . $gallery_path . '/_cache';
		if ( ! is_dir( $dirname ) )
			wp_mkdir_p( $dirname );

		$filename = trailingslashit( $dirname ) . basename( $filename );
		$result = $image->save( $filename, $mime_type );
		if ( ! is_wp_error( $result ) )
			set_transient( 'eazyest_gallery_created_cache', $filename );
		return $result;
	}

	// image select functions ----------------------------------------------------
	/**
	 * Eazyest_FolderBase::is_gallery_image()
	 * Test if attachment resides in eazyest gallery.
	 *
	 * @since 0.1.0 (r12)
	 * @uses absint() to check attachment_id
	 * @uses get_post()
	 * @param int $attachment_id
	 * @return bool
	 */
	function is_gallery_image( $attachment_id ) {
		if ( ! $attachment_id  = absint( $attachment_id ) )
			return false;

		$attachment = get_post( $attachment_id );

		if ( empty( $attachment ) )
			return false;
		return false !== strpos( $attachment->guid, eazyest_gallery()->address() );
	}

	/**
	 * Eazyest_FolderBase::featured_image()
	 * Returns the ID for the featured image
	 * If no featured image is selected in edit > galleryfolder, the first image in the folder is selected.
	 *
	 * @since 0.1.0 (r2)
	 * @uses wpdb
	 * @param int $post_id
	 * @return int
	 */
	function featured_image( $post_id ) {
		global $wpdb;
		$query = $wpdb->prepare(
			"SELECT meta_value
			FROM $wpdb->postmeta
			WHERE meta_key = '_thumbnail_id'
			AND post_id = %s",
			$post_id
		);
		$results = $wpdb->get_results( $query, ARRAY_A );
		if ( ! empty( $results ) )
 		return $results[0]['meta_value'];
		else
			return $this->first_image( $post_id );
	}

	/**
	 * Eazyest_FolderBase::first_image()
	 * Returns the ID for the first image in the folder after sorting according to Eazyest Gallery folder Settings
	 *
	 * @since 0.1.0 (r2)
	 * @uses wpdb
	 * @param int $post_id
	 * @return int
	 */
	function first_image( $post_id ) {
		global $wpdb;
		list( $order_by, $ascdesc ) = explode( '-', eazyest_gallery()->sort_by( 'thumbnails' ) );
		$results = $wpdb->get_results( "
			SELECT ID FROM $wpdb->posts
			WHERE post_parent = $post_id
			AND post_type = 'attachment'
			AND post_status IN ('inherit', 'publish')
			ORDER BY $order_by $ascdesc
			LIMIT 1;",
			ARRAY_A
		);
		$id = ! empty( $results ) ? $results[0]['ID'] : 0;
		return $id;
	}

	/**
	 * Eazyest_FolderBase::random_images()
	 * Returns an array with a number of randomly selected image ID from a folder.
	 *
	 * @example To select one random image from all folders do:
	 * @example <?php $post_id = Eazyest_FolderBase->random_images( 0, 1, true ); ?>
	 *
	 * @since 0.1.0 (r2)
	 * @uses wpdb
	 * @param integer $post_id the gallery folder id. if 0 and subfolders, all root folders will be included
	 * @param integer $number 1 or higher
	 * @param bool $subfolders include subfolders in selection
	 * @return array of integers
	 */
	function random_images( $post_id = 0, $number = 1, $subfolders = false ) {
		$number = max( 1, $number );
		$post_ids = "= $post_id";
		if ( $subfolders ) {
			$children = $this->get_folder_children( $post_id );
			if ( ! empty( $children ) ) {
				if ( $post_id )
					$children[] = $post_id;
				$childlist = implode( ',', $children );
				$post_ids = "IN ($childlist)";
			}
		}
		global $wpdb;
		$results = $wpdb->get_results( "
			SELECT ID FROM $wpdb->posts
			WHERE post_parent $post_ids
			AND post_type = 'attachment'
			AND post_status IN ('inherit','publish')
			ORDER BY RAND()
			LIMIT $number;",
			ARRAY_A
		);
		if ( empty( $results ) )
			return array( 0 );

		$random = array();
		foreach( $results as $result ) {
			$random[] = $result['ID'];
		}
		return $random;
	}

	/**
	 * Eazyest_FolderBase::recent_images()
	 * Returns an array with a number of latest included images
	 *
	 * @example To select the latest image from all folders do:
	 * @example <?php $post_id = Eazyest_FolderBase->recent_images( 0, 1, true ); ?>
	 *
	 * @since 0.1.0 (r2)
	 * @use wpdb
	 * @param integer $post_id the gallery folder id. if 0 and subfolders, all root folders will be included
	 * @param integer $number
	 * @param bool $subfolders
	 * @return array of integers
	 */
	function recent_images( $post_id = 0, $number = 1, $subfolders = false ) {
		$number = max( 1, $number );
		$post_ids = "= $post_id";
		if ( $subfolders ) {
			$children = $this->get_folder_children( $post_id );
			if ( ! empty( $children ) ) {
				if ( $post_id )
					$children[] = $post_id;
				$childlist = implode( ',', $children );
				$post_ids = "IN ($childlist)";
			}
		}
		global $wpdb;
		$results = $wpdb->get_results( "
			SELECT ID FROM $wpdb->posts
			WHERE post_parent $post_ids
			AND post_type = 'attachment'
			AND post_status IN ('inherit', 'publish')
			ORDER BY post_date DESC
			LIMIT $number;",
			ARRAY_A
		);
		if ( empty( $results ) )
			return array( 0 );

		$recent = array();
		foreach( $results as $result ) {
			$recent[] = $result['ID'];
		}
		return $recent;
	}

	/**
	 * Eazyest_FolderBase::children_images()
	 * Get all attachemnt ID for galleryfolder and its subfolders
	 *
	 * @since 0.1.0 (r2)
	 * @param integer $post_id
	 * @param integer $number of images to retrieve
	 * @param bool $subfolders
	 * @return array of int attachment ID
	 */
	function children_images( $post_id = 0, $number = 0 ) {
		global $wpdb;
		list( $order_by, $ascdesc ) = explode( '-', eazyest_gallery()->sort_by( 'thumbnails' ) );
		$limit = 0 < $number ? "LIMIT $number" : '';
		$post_ids = "= $post_id";
		$children = $this->get_folder_children( $post_id );
		if ( ! empty( $children ) ) {
			if ( $post_id )
				$children[] = $post_id;
			$childlist = implode( ',', $children );
			$post_ids = "IN ($childlist)";
		}
		$results = $wpdb->get_results( "
			SELECT ID FROM $wpdb->posts
			WHERE post_parent $post_ids
			AND post_type = 'attachment'
			AND post_status IN ('inherit', 'publish')
			ORDER BY $order_by $ascdesc
			$limit",
			ARRAY_A
		);
		if ( empty( $results ) )
			return array( 0 );

		$images = array();
		foreach( $results as $result ) {
			$images[] = $result['ID'];
		}
		return $images;
	}

} // Eazyest_FolderBase


function ezg_theme_compatible() {
	$theme = basename( TEMPLATEPATH );
	$compatible_themes = array( 'twentyten', 'twentyeleven', 'twentytwelve', 'weaver-ii', 'weaver-ii-pro' );
	if ( in_array( $theme, $compatible_themes ) ) {
		if ( 'weaver-ii-pro' == $theme )
			$theme = 'weaver-ii';
		return $theme;
	}
	return false;
}

/**
 * ezg_is_gallery_image()
 * Wrapper for eazyest_folderbase()->is_gallery_image().
 *
 * @since 0.1.0 (r61)
 * @param int $post_id
 * @return bool true if attachment image resides in eazyest gallery
 */
function ezg_is_gallery_image( $post_id ) {
	return eazyest_folderbase()->is_gallery_image( $post_id );
}

/**
 * ezg_get_gallery_path()
 * Get value for metadata '_gallery_path'.
 *
 * @since 0.1.0 (r108)
 * @uses get_post_meta()
 * @param integer $post_id
 * @return string
 */
function ezg_get_gallery_path( $post_id = 0 ) {
	return get_post_meta( $post_id, '_gallery_path', true );
}

/**
 * ezg_update_gallery_path()
 * update post metadata '_gallery_path'.
 *
 * @since 0.1.0 (r108)
 * @uses update_post_meta()
 * @param integer $post_id
 * @param string $new_value
 * @param string $old_value
 * @return bool
 */
function ezg_update_gallery_path( $post_id = 0, $new_value, $old_value = null ) {
	if ( ! $post_id || empty( $new_value ) )
		return false;
	return update_post_meta( $post_id, '_gallery_path', $new_value, $old_value );
}

/**
 * eazyest_folderbase()
 *
 * @since 0.1.0 (r2)
 * @return object Eazyest_FolderBase
 */
function eazyest_folderbase() {
	return Eazyest_Folderbase::instance();
}
