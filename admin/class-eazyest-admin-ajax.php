<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Eazyest_Ajax
 *
 * @package Eazyest Gallery
 * @subpackage Admin/Ajax
 * @author Marcel Brinkkemper
 * @copyright 2012 Brimosoft
 * @since 0.1.0 (r2)
 * @version 0.1.0 (r297)
 * @access public
 */
class Eazyest_Admin_Ajax {

	/**
	 * @staticvar Eazyest_Admin_Ajax $instance The single object in memory
	 */
	private static $instance;

	/**
	 * Eazyest_Ajax::__construct()
	 *
	 * @return void
	 */
	function __construct() {
	}

	/**
	 * Eazyest_Ajax::init()
	 *
	 * @return void
	 */
	private function init() {
		$this->actions();
	}

	/**
	 * Eazyest_Ajax::instance()
	 * create Eazyest_Akax instance
	 *
	 * @since 0.1.0 (r2)
	 * @return self object Eazyest_Admin_Ajax
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Eazyest_Admin_Ajax;
			self::$instance->init();
		}
		return self::$instance;
	}

	/**
	 * Eazyest_Ajax::actions()
	 * Add ajax actions used by Eazyest Gallery
	 *
	 * @since 0.1.0 (r2)
	 * @uses add_action()
	 * @return void
	 */
	function actions() {
		// admin actions
		$ajax = array(
			// admin ajax
			'upload',
			'filetree',
			'select_dir',
			'folder_change',
			'collect_folders',
			'create_folder',

			// frontend logged in
			'next_slideshow',
			'more_thumbnails',
			'more_folders',
		);
		$nopriv = array(
			// frontend not logged in
			'next_slideshow',
			'more_thumbnails',
			'more_folders',
		);

		foreach( $ajax as $action ) {
			add_action( "wp_ajax_eazyest_gallery_$action", array( $this, $action ) );
		}
		foreach( $nopriv as $action ) {
			add_action( "wp_ajax_nopriv_eazyest_gallery_$action", array( $this, $action ) );
		}
	}
  // Admin actions ------------------------------------------------------------
	/**
	 * Eazyest_Ajax::upload()
	 * Refresh the attachment list table after image uploads
	 *
	 * @since 0.1.0 (r2)
	 * @uses check_ajax_referer()
	 * @uses get_post()
	 * @return void
	 */
	function upload() {
		$post_id = isset( $_POST['post'] ) ? $_POST['post'] : 0;
		if ( check_ajax_referer( 'update-post_' . $post_id ) ) {
			global $post;
			$post = get_post( $post_id );
	  	require_once( eazyest_gallery()->plugin_dir . 'admin/class-eazyest-media-list-table.php' );
	  	$list_table = new Eazyest_Media_List_Table( array( 'plural' => 'media', 'screen' => eazyest_gallery()->post_type  ) );
			$list_table->prepare_items();
			if ( $list_table->has_items() ) {
				$list_table->views();
				$list_table->display();
			}
		}
		wp_die();
	}

	/**
	 * Eazyest_Ajax::filetree()
	 * Return sub-directory listing on request of jquery.filetree
	 *
	 * @since 0.1.0 (r2)
	 * @uses check_ajax_referer()
	 * @return void
	 */
	function filetree() {
		$content_dir = str_replace( '\\', '/', WP_CONTENT_DIR );
		if ( check_ajax_referer( 'file-tree-nonce' ) ) {
			$dir = urldecode( $_POST['dir'] );
			if( is_dir( $dir ) ) {
				$files = scandir( $dir );
				natcasesort( $files );
				 // the 2 accounts for . and ..
				if( count( $files ) > 2 ) {
					echo "<ul class='jquery-filetree' style='display: none;'>";
					// All dirs
					foreach( $files as $file ) {
						$file = str_replace( '\\', '/', $file );
						if( is_dir( $dir . $file ) ) {
							if ( eazyest_folderbase()->valid_dir( $dir . $file ) ) {
								echo "<li class='directory collapsed'><a href='#' rel='" . htmlentities( $dir . $file ) . "/'>" . htmlentities( $file ) . "</a></li>";
							}
						}
					}
					echo "</ul>";
				}
			}
		}
		wp_die();
	}

	/**
	 * Eazyest_Ajax::select_dir()
	 * Reurn relative directory when user clicks directory in filetree dropdown
	 *
	 * @since 0.1.0 (r2)
	 * @uses check_ajax_referer()
	 * @uses wp_die()
	 * @return void
	 */
	function select_dir() {
		if ( check_ajax_referer( 'file-tree-nonce' ) ) {
			$dir = urldecode( $_POST['dir'] );
			if ( is_dir( $dir ) ) {
				$abspath = str_replace( '\\', '/', ABSPATH );
				$rel_dir = eazyest_gallery()->get_relative_path( $abspath, $dir );
				eazyest_gallery()->gallery_folder = $rel_dir;
				if ( ! eazyest_folderbase()->is_dangerous( eazyest_gallery()->root() ) )
					echo $rel_dir;
				else
					echo "!";
			}
		}
		wp_die();
	}

	/**
	 * Eazyest_Ajax::gallery_folder_change()
	 * Check if gallery folder path exists or is on a dangerous path.
	 *
	 * @since 0.1.0 (r2)
	 * @uses check_ajax_referer()
	 * @uses trailingslashit()
	 * @uses wp_send_json() to send array of results
	 * @return void
	 */
	function folder_change() {
		check_ajax_referer( 'gallery-folder-nonce' );
		$root = str_replace( '\\', '/', trailingslashit( eazyest_gallery()->get_absolute_path( ABSPATH . $_POST['gallery_folder'] ) ) );
		$response = array( 'result' => 0, 'folder' => $_POST['gallery_folder'] );
		if ( eazyest_folderbase()->is_dangerous( $root ) || ! file_exists( $root ) ) {
			if ( eazyest_folderbase()->is_dangerous( $root ) ){
				$response['result'] = 1;
				$response['folder'] = eazyest_gallery()->gallery_folder;
			} else {
				$response['result'] = 2;
			}
		}
		wp_send_json( $response );
	}

	/**
	 * Eazyest_Admin_Ajax::create_folder()
	 * Create new gallery folder on Ajax request
	 *
	 * @since 0.1.0 (r21)
	 * @uses check_ajax_referer()
	 * @uses trailingslashit()
	 * @uses wp_send_json() to send array of results
	 * @return void
	 */
	function create_folder() {
		check_ajax_referer( 'gallery-folder-nonce' );
		$root = str_replace( '\\', '/', trailingslashit( eazyest_gallery()->get_absolute_path( ABSPATH . $_POST['gallery_folder'] ) ) );
		$response = array( 'result' => 0, 'folder' => $_POST['gallery_folder'] );
		if ( ! eazyest_folderbase()->is_dangerous( $root ) ) {
			if ( ! is_dir( $root ) )
				wp_mkdir_p( $root );
			if ( ! is_dir( $root ) ) {
				$response['result'] = 1;
				$response['folder'] = eazyest_gallery()->gallery_folder;
			}
		}
		wp_send_json( $response );
	}

	/**
	 * Eazyest_Admin_Ajax::collect_folders()
	 * Checks for new or deleted images per folder on AJAX call.
	 *
	 * @since 0.1.0 (r20)
	 * @uses check_ajax_referer()
	 * @use set_transient() to store intemediate results
	 * @uses get_transient() to retrieve intermediate results
	 * @uses wp_send_json() to send array of updated image counts
	 * @return void
	 */
	function collect_folders() {
		check_ajax_referer( 'collect-folders' );
		$subaction = isset( $_POST['subaction'] ) ? $_POST['subaction'] : 'start';
		$results = array( 'updated' => array(), 'folders' => array() );
		if ( 'start' == $subaction ) {
			global $wpdb;
			// get folders newest first because they are most likely to have new images
			$results['folders']  = $wpdb->get_results( $wpdb->prepare(  "SELECT ID FROM $wpdb->posts WHERE post_type = %s AND post_status = 'publish' ORDER BY post_date DESC", eazyest_gallery()->post_type  ), ARRAY_A );
		} else if ( 'next' == $subaction || 'stop' == $subaction ) {
			$results = get_transient( 'eazyest-gallery-ajax-collect' );
		} else {
			echo __( 'Cheating huh?', 'eazyest-gallery' );
			wp_die();
		}
		// user clicked the update meaagse, stop and send progress until now
		if ( 'stop' == $subaction )
			wp_send_json( $results['updated'] );

		// process next step
		if ( count( $results['folders'] ) ) {
			$folder = reset( $results['folders'] );
			$new_images = eazyest_folderbase()->get_new_images( $folder['ID'] );
			if ( $new_images['add'] || $new_images['delete'] ) {
				$results['updated'][] = array( 'id' => $folder['ID'], 'images' => $new_images );
			}
			$shift = true;
			if ( $transient = get_transient( 'eazyest_gallery_add_attachments' ) ) {
				$shift = ! in_array( $folder['ID'], $transient );
			}
			if ( $shift )
				array_shift( $results['folders'] );
		}
		set_transient( 'eazyest-gallery-ajax-collect', $results );
		if ( count( $results['folders'] ) ) {
			echo 'next';
		} else {
			if ( empty( $results['updated'] ) ) {
				echo 0;
			} else {
				wp_send_json( $results['updated'] );
			}
		}
		wp_die();
	}

	// Frontend actions

	/**
	 * Eazyest_Admin_Ajax::next_slideshow()
	 * Echo the next slide by AJAX call for slideshow
	 *
	 * @since 0.1.0 (r63)
	 * @uses check_ajax_referer()
	 * @uses get_transient() to get query args for slide
	 * @uses WP_Query
	 * @uses wp_get_attachment_link() to get link and image for next slide
	 * @return void
	 */
	function next_slideshow() {
		$show = isset( $_POST['show_id'] ) ? substr( $_POST['show_id'], 23 ) : 0;
		check_ajax_referer( 'eazyest-ajax-nonce-' . $show );
		if ( $query_args = get_transient( "eazyest-ajax-slideshow-$show" ) ) {
			if ( isset( $query_args['size'] ) ) {
				$size = $query_args['size'];
				unset( $query_args['size'] );
			} else {
				$size = 'thumbnail';
			}
			$query_args['posts_per_page'] = 1;
			$query = new WP_Query( $query_args );
			global $post;
			if ( $query->have_posts() ) {
				$query->the_post();
				echo wp_get_attachment_link( $post->ID, $size, true );
			}
			wp_die();
		}
		echo 0;
		wp_die();
	}

	/**
	 * Eazyest_Admin_Ajax::more_thumbnails()
	 * Display the next page of thumbnails for a particular folder on AJAX request.
	 *
	 * @since 0.1.0 (r131)
	 * @uses site_url() to check referer
	 * @uses wp_get_referer()
	 * @uses get_post() to set GLOBALS['post'] to run frontend functions
	 * @return void
	 */
	function more_thumbnails() {
		$site_url = site_url();
		$this_site = substr( $site_url, strpos( $site_url, '://' ) + 3 );
		if ( strpos( wp_get_referer(), $this_site ) ) {
			$post = get_post( $_POST['folder'] );
			if ( $post ) {
				$GLOBALS['post'] = $post;
				eazyest_gallery()->thumbs_page    = absint( $_POST['posts']   );
				eazyest_gallery()->thumbs_columns = absint( $_POST['columns'] );

				include_once( eazyest_gallery()->plugin_dir . 'frontend/class-eazyest-frontend.php' );
				eazyest_frontend()->setup_tags();
				eazyest_frontend()->thumbnails( $_POST['folder'], $_POST['page'] );
			}
		} else {
			echo 0;
		}
		wp_die();
	}

	function more_folders() {
		$site_url = site_url();
		$this_site = substr( $site_url, strpos( $site_url, '://' ) + 3 );
		if ( strpos( wp_get_referer(), $this_site ) ) {
			$post = get_post( $_POST['folder'] );
			if ( $post ) {
				$GLOBALS['post'] = $post;
				include_once( eazyest_gallery()->plugin_dir . 'frontend/class-eazyest-frontend.php' );
				eazyest_frontend()->setup_tags();
				eazyest_frontend()->subfolders( $_POST['folder'], $_POST['page'] );
			}
		} else {
			echo 0;
		}
		wp_die();
	}


} // Eazyest_Ajax
