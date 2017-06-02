<?php
/**
 * Eazyest_Folder_Editor
 * All functions to manage the Folder Edit screen
 *
 * @package Eazyest Gallery
 * @subpackage Admin/Folder Editor
 * @author Marcel Brinkkemper
 * @copyright 2012-2013 Brimosoft
 * @version 0.1.0 (r315)
 * @since 0.1.0 (r2)
 * @access public
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class Eazyest_Folder_Editor {

	/**
	 * @staticvar Eazyest_Folder_Editor $instance single object in memory
	 * @access private
	 */
	private static $instance;

	/**
	 * Eazyest_Folder_Editor::__construct()
	 *
	 * @return void
	 */
	function __construct() {}

	/**
	 * Eazyest_Folder_Editor::init()
	 *
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	private function init() {
		$this->actions();
		$this->filters();
	}

	/**
	 * Eazyest_Folder_Editor::instance()
	 * Return single object in memory
	 *
	 * @since 0.1.0 (r2)
	 * @return Eazyest_Folder_Editor object
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Eazyest_Folder_Editor;
			self::$instance->init();
		}
		return self::$instance;
	}

	/**
	 * Eazyest_Folder_Editor::actions()
	 * Add WordPress actions
	 * Apply filter 'eazyest_gallery_before_list_items_action' on action before the images list is built
	 * can be either
	 *  'collect_images' : collect new (ftp)  uploaded images when user opens the folder edit screen ( default )
	 *  'no_action'      : take no action
	 *
	 * @since 0.1.0 (r2)
	 * @uses apply_filters()
	 * @uses add_action()
	 * @return void
	 */
	function actions() {
		$type = eazyest_gallery()->post_type;
		$before_list_items_action = apply_filters( 'eazyest_gallery_before_list_items_action', 'collect_images' );

		$manage_action = "manage_{$type}_posts_custom_column";
  	add_action( 'admin_init',                        array( $this, 'collect_folders_action'  )        );
  	add_action( 'admin_enqueue_scripts',             array( $this, 'register_scripts'        ), 10    );
  	add_action( 'admin_enqueue_scripts',             array( $this, 'enqueue_scripts'         ), 20    );
  	add_action( 'admin_head',                        array( $this, 'fix_content_messages'    )        );
  	add_action( 'admin_head',                        array( $this, 'admin_style'             )        );
  	add_action( 'admin_head',                        array( $this, 'collect_style'           )        );

  	add_action( 'admin_action_save_gallery',         array( $this, 'save_gallery'            )        );
  	add_action( 'admin_action_move_folder',          array( $this, 'move_folder'             )        );
  	add_action( 'admin_action_delete',               array( $this, 'delete_action'           )        );
  	add_action( 'admin_action_editpost',             array( $this, 'do_bulk_actions'         )        );
  	add_action( 'admin_action_folder_action',        array( $this, 'do_bulk_actions'         )        );
  	add_action( 'admin_action_attachment_action',    array( $this, 'do_bulk_actions'         )        );
  	add_action( 'admin_action_untrash_folders',      array( $this, 'untrash_folders'         )        );

  	add_action( 'admin_notices',                     array( $this, 'admin_notices'           )        );

  	add_action( 'edit_form_after_title',             array( $this, 'media_buttons'           )        );
  	add_action( 'edit_form_after_editor',            array( $this, 'list_table_attachments'  ),  1, 1 );
  	add_action( 'edit_form_after_editor',            array( $this, 'list_table_folders'      ),  2, 1 );
  	add_action( 'add_meta_boxes',                    array( $this, 'submit_meta_box'         )        );
  	add_action( 'post_submitbox_misc_actions',       array( $this, 'folder_information'      ),  8    );
  	add_action( 'post_submitbox_misc_actions',                     'ezg_donate',                 9    );
  	add_action( $manage_action,                      array( $this, 'custom_column'           ), 10, 2 );

  	add_action( 'eazyest_gallery_before_list_items', array( $this, $before_list_items_action ), 10, 1 );
  	add_action( 'eazyest_gallery_collect_folders',   array( $this, 'collect_folders'         )        );
	}

	/**
	 * Eazyest_Folder_Editor::no_action()
	 * Do nothing
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	function no_action(){}

	/**
	 * Eazyest_Folder_Editor::filters()
	 * Add WordPress filters
	 *
	 * @since 0.1.0 (r2)
	 * @uses add_filter()
	 * @return void
	 */
	function filters() {
		$manage_columns = 'manage_edit-' . eazyest_gallery()->post_type . '_columns';
		// folder edit screen filters
		add_filter( 'post_updated_messages',               array( $this, 'post_updated_messages' )        );
		add_filter( $manage_columns,                       array( $this, 'folder_columns'        )        );
		add_filter( 'page_row_actions',                    array( $this, 'page_row_actions'      ), 10, 2 );
		add_filter( 'upload_dir',                          array( $this, 'upload_dir'            )        );
		add_filter( 'views_edit-galleryfolder',            array( $this, 'save_columns_button'   )        );
		add_filter( 'page_attributes_dropdown_pages_args', array( $this, 'dropdown_pages_args'   ), 10, 2 );
		// filters to adapt media upload
		add_filter( 'media_view_strings',                  array( $this, 'media_view_strings'    ), 10, 2 );
		add_filter( 'media_send_to_editor',                array( $this, 'media_send_to_editor'  ), 10, 3 );
	}

	/**
	 * Eazyest_Folder_Editor::register_scripts()
	 * Register scripts for later use.
	 *
	 * @since 0.1.0 (r2)
	 * @uses wp_register_script()
	 * @return void
	 */
	function register_scripts() {
		$j = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? 'js' : 'min.js';
		wp_register_script( 'jquery-tablednd',         eazyest_gallery()->plugin_url . "admin/js/jquery.tablednd.$j",         array( 'jquery' ),          '0.7',        true );
		wp_register_script( 'eazyest-gallery-admin',   eazyest_gallery()->plugin_url . "admin/js/eazyest-gallery-admin.$j",   array( 'jquery-tablednd' ), '0.1.0-r315', true );
		wp_register_script( 'eazyest-gallery-collect', eazyest_gallery()->plugin_url . "admin/js/eazyest-gallery-collect.$j", array( 'jquery' ),          '0.1.0-r273', true );

		wp_localize_script( 'eazyest-gallery-admin',   'galleryfolderL10n',     $this->localize_folder_script()  );
		wp_localize_script( 'eazyest-gallery-collect', 'eazyestGalleryCollect', $this->localize_collect_script() );
	}

	/**
	 * Eazyest_Folder_Editor::localize_folder_script()
	 * Localize script to set visibility strings.
	 *
	 * @since 0.1.0 (r96)
	 * @return array
	 */
	function localize_folder_script() {
		return array(
			'hidden' =>        __( 'Hidden',           'eazyest-gallery' ),
			'hiddenpublish' => __( 'Hidden Published', 'eazyest-gallery' ),
		);
	}

	/**
	 * Eazyest_Folder_Editor::enqueue_scripts()
	 * Enqueue scripts to collect new ftp-uploaded folders.
	 *
	 * Applies filter <code>'eazyest_gallery_ajax_collect'</code> bool if this should run or not
	 *
	 * @since 0.1.0 (r20)
	 * @uses apply_filters()
	 * @uses wp_enqueue_srcipt()
	 * @return void
	 */
	function enqueue_scripts() {
    if ( in_array( get_current_screen()->id, array( 'upload', 'edit-' . eazyest_gallery()->post_type ) ) ) {
			if ( apply_filters( 'eazyest_gallery_ajax_collect', true ) && ! isset( $_REQUEST['collect-refresh'] ) ) {
				wp_enqueue_script( 'eazyest-gallery-collect' );
			}
		}
	}

	/**
	 * Eazyest_Folder_Editor::localize_collect_script()
	 * Localize script for collecting images.
	 *
	 * @since 0.1.0 (r20)
	 * @uses wp_create_nonce() for ajax nonce
	 * @return array
	 */
	function localize_collect_script() {
		return array(
			'collecting'  => '<p title="' .
			          esc_attr__( 'Click to stop search',                                   'eazyest-gallery' ) . '" id="eazyest-collect-folders" class="collect-folders">' .
							          __( 'Searching for new images in Eazyest Gallery',            'eazyest-gallery' ) . '</p>',
			'notfound'     => __( 'No new images found in your gallery',                    'eazyest-gallery' ),
			'foundimages'  => __( 'Found %d new images in your gallery',                    'eazyest-gallery' ),
			'missedimages' => __( 'Found %d missing images in your gallery',                'eazyest-gallery' ),
			'refresh'      => '<a href="upload.php">' .
			                  __( 'Refresh this screen',                                    'eazyest-gallery' ) . '</a>',
			'error1'       => __( 'An error occurred while indexing your gallery.',         'eazyest-gallery' ),
			'error2'       => __( 'Please check your server settings to solve this error:', 'eazyest-gallery' ),
			'error500'     => __( '500 (Internal Server Error)',                            'eazyest-gallery' ),
			'_wpnonce'     => wp_create_nonce( 'collect-folders' ),
		);
	}

	/**
	 * Eazyest_Folder_Editor::bail()
	 * Do we have the right screen and action, or should we stop executing?
	 *
	 * @since 0.1.0 (r2)
	 * @uses get_current_screen()
	 * @return bool
	 */
	private function bail() {
		if ( isset( $_GET['post_type'] ) && ( $_GET['post_type'] == eazyest_gallery()->post_type ) )
			return false;

		if( isset( $_GET['post'] ) && eazyest_gallery()->post_type == get_post_type( absint( $_GET['post'] ) ) )
			return false;

		$screen = get_current_screen();
		if ( ( isset( $screen->post_type ) ) && ( eazyest_gallery()->post_type == $screen->post_type ) )
			return false;

		return true;
	}


  /**
   * Eazyest_Folder_Editor::fix_content_messages()
   * Prevent plugins to issue messages depending on files in wp_upload_dir.
   *
	 * Eazyest Gallery changes wp_upload_dir for post_type = galleryfolder screens.
	 * Some plugins issue messages when a file is not found in wp_upload_dir.
	 * This function tries to catch and remove them.
	 * Don't worry, they will work on opther screens.
   *
   * @since 0.1.0 (r125)
   * @uses remove_action()
   * @return void
   */
  function fix_content_messages() {

  	$is_image = isset( $_POST['post'] ) &&  ezg_is_gallery_image( $_POST['post'] );

		if ( $this->bail() && ! $is_image )
			return;

  	// remove action for  Shadowbox JS missing source files message.
  	if ( get_option ( 'shadowbox-js-missing-src' ) )
  		delete_option( 'shadowbox-js-missing-src' );
  	global $ShadowboxAdmin;
  	if ( isset( $ShadowboxAdmin ) ) {
  		remove_action( 'admin_notices', array( $ShadowboxAdmin, 'missing_src_notice' ) );
		}
  }

	/**
	 * Eazyest_Folder_Editor::post_updated_messages()
	 * Filter post updated messages for galleryfolder post type.
	 * @see http://codex.wordpress.org/Function_Reference/register_post_type
	 *
	 * @since 0.1.0 (r96)
	 * @uses esc_url()
	 * @uses  get_permalink()
	 * @uses add_query_arg()
	 * @uses wp_post_revision_title()
	 * @uses date_i18n()
	 * @param array $messages
	 * @return array
	 */
	function post_updated_messages( $messages ) {
		if ( $this->bail() )
			return $messages;

		if ( ! isset( $_GET['post'] ) )
			return $messages;

		global $post, $post_ID;
		$messages[eazyest_gallery()->post_type] = array(
			 0 => '', // Unused. Messages start at index 1.
			 1 => sprintf( __('Folder updated. <a href="%s">View post</a>', 'eazyest-gallery' ), esc_url( get_permalink($post_ID) ) ),
			 2 => __('Custom field updated.', 'eazyest-gallery' ),
			 3 => __('Custom field deleted.', 'eazyest-gallery' ),
			 4 => __('Folder updated.', 'eazyest-gallery' ),
			/* translators: %s: date and time of the revision */
			 5 => isset($_GET['revision']) ? sprintf( __('Folder restored to revision from %s', 'eazyest-gallery' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			 6 => sprintf( __('Folder published. <a href="%s">View folder</a>', 'eazyest-gallery' ), esc_url( get_permalink($post_ID) ) ),
			 7 => __('Folder saved.', 'eazyest-gallery' ),
			 8 => sprintf( __('Folder submitted. <a target="_blank" href="%s">Preview folder</a>', 'eazyest-gallery' ), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			 9 => sprintf( __('Folder scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview folder</a>', 'eazyest-gallery' ),
				// translators: Publish box date format, see http://php.net/date
				date_i18n( __( 'M j, Y @ G:i', 'eazyest-gallery' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
			10 => sprintf( __('Folder draft updated. <a target="_blank" href="%s">Preview folder</a>', 'eazyest-gallery' ), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		);
		if ( 'hidden' == $post->post_status ) {
			$messages['post'][1]  = __('Folder updated.',  'eazyest-gallery' );
			$messages['post'][6]  = __('Folder published.', 'eazyest-gallery' );
			$messages['post'][10] = __('Folder draft updated', 'eazyest-gallery' );
			$messages['post'][9]  = sprintf( __('Folder scheduled for: <strong>%1$s</strong>.', 'eazyest-gallery' ),
				// translators: Publish box date format, see http://php.net/date
				date_i18n( __( 'M j, Y @ G:i', 'eazyest-gallery' ), strtotime( $post->post_date ) ) );
		}
		return $messages;
	}

	/**
	 * Eazyest_Folder_Editor::admin_notices()
	 * Show notices after custom actions
	 *
	 * @since 0.1.0 (r2)
	 * @uses get_current_screen()
	 * @uses remove_query_arg()
	 * @uses  add_query_arg()
	 * @uses wp_nonce_url()
	 * @uses number_format_i18n()
	 * @uses absint()
	 * @return void
	 */
	function admin_notices() {
		// only process notices for the eazyest gallery
		if ( $this->bail() )
			return;
		$screen = get_current_screen();
		$message = '';

		if ( 'post' ==  $screen->base && eazyest_gallery()->post_type == $screen->id ) {
			if ( isset( $_GET['post'] ) && ! empty( $_GET['deleted'] ) && $deleted = absint( $_GET['deleted'] ) ) {
				$message = sprintf( _n( 'Image attachment permanently deleted.', '%d image attachments permanently deleted.', $deleted, 'eazyest-gallery' ), number_format_i18n( $_GET['deleted'] ) );
				$_SERVER['REQUEST_URI'] = remove_query_arg(array( 'deleted'), $_SERVER['REQUEST_URI']);
			}
			if ( isset( $_REQUEST['trashed'] ) && $trashed = absint( $_REQUEST['trashed'] ) ) {
				$post_type = eazyest_gallery()->post_type;
				$message = sprintf( _n( 'Folder moved to the Trash.', '%s folders moved to the Trash.', $trashed, 'eazyest-gallery' ), number_format_i18n( $trashed ) );
				$ids = isset($_REQUEST['ids']) ? $_REQUEST['ids'] : 0;
				$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'trashed' ), $_SERVER['REQUEST_URI'] );
				$undo_url = wp_nonce_url( add_query_arg( array( 'ids' => $ids, 'action' => 'untrash_folders' ), $_SERVER['REQUEST_URI'] ), 'bulk-posts' );
				$message .= ' <a href="' . $undo_url . '">' . __( 'Undo', 'eazyest-gallery' ) . '</a>';
			}

			if ( isset( $_REQUEST['untrashed'] ) && $untrashed = absint( $_REQUEST['untrashed'] ) ) {
				$message = sprintf( _n( 'Folder restored from the Trash.', '%s folders restored from the Trash.', $untrashed, 'eazyest-gallery' ), number_format_i18n( $untrashed ) );
				$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'untrashed' ), $_SERVER['REQUEST_URI'] );
			}
			if ( empty( $message ) )
				return;
			?>
			<div class="updated"><p><?php echo $message ?></p></div>
			<?php
			return;
		}
		if ( isset( $_REQUEST['parent-of'] ) ) {
			$message = '<p>' . __( 'You cannot delete a parent folder', 'eazyest-gallery' ) . '</p>';
			$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'parent-of' ), $_SERVER['REQUEST_URI'] );
		}

		if ( $errors = get_transient( 'eazyest_gallery_rename_errors' ) ) {
			$message .= '<p><strong>' . __( 'Eazyest Gallery found one or more new folders, but could not include them.', 'eazyest-gallery' ) . '<strong>';
			foreach( $errors as $error ) {
				$message .= '<br />' . $error->get_error_message();
			}
			$message .= '<p>';
			delete_transient( 'eazyest_gallery_rename_errors' );
		}

		if ( empty( $message ) )
			return;
		?>
		<div class="error"><?php echo $message ?></div>
		<?php
	}

	/**
	 * Eazyest_Folder_Editor::delete_action()
	 * Prevent users to permanently delete parent folders
	 * Return to edit page with message
	 *
	 * @since 0.1.0 (r2)
	 * @uses get_post()
	 * @uses get_children()
	 * @uses wp_redirect()
	 * @uses add_query_arg()
	 * @uses wp_get_referer()
	 * @return void
	 */
	function delete_action() {
		global $post_type, $post_ids;
		$post_ids = !empty( $_REQUEST['post'] ) ? array_map( 'intval', (array) $_REQUEST['post'] ) : null;
		$post_type = isset( $_REQUEST['post_type'] ) ? $_REQUEST['post_type'] : null;
		if ( ! isset( $post_type ) || $post_type != eazyest_gallery()->post_type )
			return;
		if ( isset( $post_ids ) ) {
			foreach( (array) $post_ids as $post_id ) {
				$has_subfolders = eazyest_folderbase()->get_subfolders( $post_id );
				if ( ! empty( $has_subfolders ) ) {
					wp_redirect( add_query_arg( array( 'parent-of' => count( $has_subfolders ) ), wp_get_referer() ) );
					exit;
				}
			}
		}
	}

	/**
	 * Eazyest_Folder_Editor::do_bulk_actions()
	 * Perform bulk actions
	 *
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	function do_bulk_actions() {
		$buttons = array( 'attachment_action', 'attachment_action2', 'folder_action', 'folder_action2' );
		$return = true;
		foreach( $buttons as $button )
			if ( isset( $_REQUEST[$button] ) && -1 != $_REQUEST[$button] ) {
				$action = false === strpos( $button, 'attachment' ) ? 'bulk_trash_folders' : 'bulk_delete_attachments';
				$return = false;
			}
		if ( $return ) return;

		$this->$action();
	}

	/**
	 * Eazyest_Folder_Editor::bulk_trash_folders()
	 * Trash one or more subfolders
	 *
	 * @since 0.1.0 (r2)
	 * @uses check_admin_referer()
	 * @uses wp_get_referer()
	 * @uses remove_query_arg()
	 * @uses get_post_type_object()
	 * @uses current_user_can()
	 * @uses wp_die()
	 * @uses wp_trash_post()
	 * @uses add_query_arg()
	 * @uses wp_redirect()
	 * @return void
	 */
	function bulk_trash_folders() {
		check_admin_referer( 'bulk-folders', 'bulk-folders' );

		$sendback = wp_get_referer();
		$sendback = remove_query_arg( array( 'trashed', 'untrashed', 'deleted', 'ids', 'message' ), $sendback );
		if ( isset( $_REQUEST['folders'] ) && 0 < count( $_REQUEST['folders'] ) ) {
			$trashed = 0;
			$post_type_object = get_post_type_object( eazyest_gallery()->post_type );
			foreach( (array) $_REQUEST['folders'] as $folder ) {
				if ( ! current_user_can( $post_type_object->cap->delete_post, intval( $folder ) ) )
					wp_die( __( 'You are not allowed to move this folder to the Trash.', 'eazyest-gallery' ) );

				if ( false !== wp_trash_post( intval( $folder ) ) )
					$trashed++;
			}
			$sendback = add_query_arg( array( 'trashed' => $trashed, 'ids' => join( ',', ( array ) $_REQUEST['folders'] ) ), $sendback );
		}
		wp_redirect( $sendback );
		exit();
	}

	/**
	 * Eazyest_Folder_Editor::untrash_folders()
	 * Undo trashing of one or more folders
	 *
	 * @since 0.1.0 (r2)
	 * @uses admin_url()
	 * @uses check_admin_referer()
	 * @uses get_post_type_object()
	 * @uses current_user_can()
	 * @uses get_post()
	 * @uses wp_untrash_post()
	 * @uses wp_die()
	 * @uses add_query_arg()
	 * @uses wp_redirect()
	 * @return void
	 */
	function untrash_folders() {
		check_admin_referer( 'bulk-posts');

		$sendback = add_query_arg( array( 'action' => 'edit' ), admin_url( 'post.php' ) );

		$post_ids = array();
		$post_type_object = get_post_type_object( eazyest_gallery()->post_type );
		if ( isset( $_REQUEST['ids'] ) ) {
			$post_ids = explode( ',', $_REQUEST['ids'] );
			$untrashed = 0;
			foreach( (array) $post_ids as $post_id ) {
				if ( ! current_user_can( $post_type_object->cap->delete_post, intval( $post_id ) ) )
					wp_die( __( 'You are not allowed to restore this folder from the Trash.', 'eazyest-gallery' ) );

				$parent_id = get_post( $post_id )->post_parent;
				if ( ! wp_untrash_post( $post_id ) )
					wp_die( __( 'Error in restoring from Trash.', 'eazyest-gallery' ) );

				$untrashed++;
			}
			$sendback = add_query_arg( array( 'untrashed' => $untrashed, 'post' => $parent_id ), $sendback );
			wp_redirect( $sendback );
			exit();
		}
	}

	/**
	 * Eazyest_Folder_Editor::bulk_delete_attachments()
	 * Permanently delete one or more image attachments
	 *
	 * @since 0.1.0 (r2)
	 * @uses check_admin_referer()
	 * @uses wp_get_referer()
	 * @uses get_post_type_object()
	 * @uses current_user_can()
	 * @uses wp_delete_attachment()
	 * @uses wp_die()
	 * @uses add_query_arg()
	 * @uses wp_redirect()
	 * @return void
	 */
	function bulk_delete_attachments() {
		check_admin_referer( 'bulk-media', 'bulk-media' );

		$sendback = wp_get_referer();
		$sendback = remove_query_arg( array( 'trashed', 'untrashed', 'deleted', 'ids', 'message' ), $sendback );

		if ( isset( $_REQUEST['media'] ) && 0 < count( $_REQUEST['media'] ) ) {
			$deleted = 0;
			$post_type_object = get_post_type_object( 'attachment' );
			foreach( (array) $_REQUEST['media'] as $media ) {
				if ( !current_user_can( $post_type_object->cap->delete_post, intval( $media ) ) )
					wp_die( __( 'You are not allowed to delete this image.', 'eazyest-gallery' ) );

				if ( false !== wp_delete_attachment( intval( $media ), true ) )
					$deleted++;
			}
			$sendback = add_query_arg( array( 'deleted' => $deleted ), $sendback );
		}
		wp_redirect( $sendback );
		exit();
	}

	/**
	 * Eazyest_Folder_Editor::save_gallery()
	 * Save Gallery menu order triggered by Save Changes button on manually sorted Folders list
	 * Redirect back and show number of updated posts
	 *
	 * @since 0.1.0 (r2)
	 * @uses check_admin_referer()
	 * @uses wp_redirect()
	 * @uses admin_url()
	 * @return void
	 */
	function save_gallery() {
		$updated = 0;
		if ( ! empty( $_POST ) && check_admin_referer( 'save_gallery-pages','gallery_nonce-pages') ) {
			if ( isset( $_POST['gallery-order-pages'] ) ) {
				$gallery_order = explode( ' ', $_POST['gallery-order-pages'] );
				$updated = eazyest_folderbase()->save_gallery_order( $gallery_order );
			}
		}
		wp_redirect( admin_url( "edit.php?post_type=galleryfolder&updated=$updated" ) );
		exit;
	}

	/**
	 * Eazyest_Folder_Editor::move_folder()
	 * Move folder to top or to bottom of the list
	 *
	 * @since 0.1.0 (r2)
	 * @uses wp_redirect()
	 * @uses admin_url()
	 * @return void
	 */
	function move_folder() {
		$updated = 0;
		if ( ! empty( $_GET ) && check_admin_referer( 'move_folder' ) ) {
			if ( isset( $_GET['move'] ) && isset( $_GET['folder'] ) ) {
   			$updated = eazyest_folderbase()->move_folder( $_GET['folder'], $_GET['move'] );
			}
  	}
		wp_redirect( admin_url( "edit.php?post_type=galleryfolder&updated=$updated" ) );
		exit;
	}

	/**
	 * Eazyest_Folder_Editor::collect_folders()
	 * Call Eazyest_FolderBase::collect_folders to get all (ftp) uploaded folders
	 *
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	function collect_folders() {
		if ( $this->bail() )
			return;

		if ( isset( $_GET['bulk-edit'] ) )
			return;

		$post_id = isset( get_current_screen()->post_ID ) ? get_current_screen()->post_ID : 0;
		if ( ! $post_id && isset( $_GET['post'] ) )
			$post_id = absint( $_GET['post'] );

		eazyest_folderbase()->collect_folders( $post_id );
	}

	/**
	 * Eazyest_Folder_Editor::collect_folders_action()
	 * Run the action 'eazyest_gallery_collect_folders'
	 *
	 * @since 0.1.0 (r2)
	 * @uses do_action()
	 * @return void
	 */
	function collect_folders_action() {
		if ( defined( 'DOING_AJAX' ) )
			return;
		do_action( 'eazyest_gallery_collect_folders' );
	}

	/**
	 * Eazyest_Folder_Editor::collect_images()
	 * Call Eazyest_FolderBase::collect_images to get all (ftp) uploaded images
	 *
	 * @since 0.1.0 (r2)
	 * @param mixed $folder_id
	 * @return void
	 */
	function collect_images( $folder_id ) {
		eazyest_folderbase()->collect_images( $folder_id );
	}

	/**
	 * Eazyest_Folder_Editor::admin_style()
	 * Add style rules for the Folder Edit screen to the Admin <head> element
	 *
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	function admin_style() {
		// styling for post_type galleryfolder pages
		if ( $this->bail() )
			return;
		?>
		<style type="text/css">
			.fixed .column-galleryfolder_images {
				width: 8em;
			}
			.fixed .column-galleryfolder_new {
				vertical-align: middle;
				width: 16px;
			}
			.fixed .column-galleryfolder_drag, .fixed .column-media_drag {
				display: none;
				padding:0;
				text-align: center;
				vertical-align: middle;
				width:32px;
			}
			.fixed td.column-galleryfolder_drag, .fixed td.column-media_drag {
				background-color: #f3f3f3;
				border-right:  1px dotted #aaa;
				border-left:  1px dotted #aaa;
				cursor: -moz-grab;
				cursor: -webkit-grab;
				cursor: grab;
			}
			.fixed tr.dragging td.column-galleryfolder_drag, .fixed tr.dragging td.column-media_drag {
				cursor: -moz-grabbing;
				cursor: -webkit-grabbing;
				cursor: grabbing;
			}
			.fixed tr.dragging {
				background-color:  #fff;
				text-shadow: 4px 4px #C6C6C6;
			}
			input[type="text"].attachment-title {
				width:90%;
			}
			div.after-editor {
				margin-top: 20px;
			}
			div.attachment-content {
				margin-top: 8px;
				margin-bottom: 10px;
				width: 90%;
			}
			.attachment-excerpt {
				width:90%;
			}
			.tablenav .tablenav-pages a.add-subfolder {
				font-family: sans-serif;
				font-weight:  normal;
				margin-left: 4px;
				padding: 3px 8px;
				position: relative;
				top: -3px;
				text-decoration: none;
				font-size: 12px;
				border: 0 none;
			}
			.subsubsub a.save-gallery:hover {
				color:#fff;
			}
			.button.button-large.media-button-gallery {
				height: 0;
				padding: 0;
			}
			.media-menu-item:empty {
				display: none;
			}
		</style>
		<?php
	}

	/**
	 * Eazyest_Folder_Editor::collect_style()
	 * Adds style rules for the image collector.
	 *
	 * @since 0.1.0 (r261)
	 * @return void
	 */
	function collect_style() {
    if ( in_array( get_current_screen()->id, array( 'upload', 'edit-' . eazyest_gallery()->post_type ) ) ) :
		?>
		<style type="text/css">
			.collect-folders {
				background: url(images/wpspin_light.gif) no-repeat;
				background-size: 16px 16px;
				padding-left: 18px;
			}
			#eazyest-collect-folders {
				cursor:pointer;
			}
		</style>
		<?php
		endif;
	}

	/**
	 * Eazyest_Folder_Editor::folder_columns()
	 * Add custom columns to the posts list
	 *
	 * @since 0.1.0 (r2)
	 * @param array $columns
	 * @return array
	 */
	function folder_columns( $columns ) {
		$type = eazyest_gallery()->post_type;
		$drag_url = eazyest_gallery()->plugin_url . 'admin/images/sort.png';
		$post_status = isset( $_REQUEST['post_status'] ) ? $_REQUEST['post_status'] : '';
		$title = isset( $_GET['post'] ) ? 'folder_title' : 'title';
		$comments = isset( $_GET['post'] ) ? 'folder_comments' : 'comments';
		$date = isset( $_GET['post'] ) ? 'folder_date' : 'date';
		$columns = array();
		if ( ! isset( $_GET['post'] ) )
			$columns['cb'] = '<input type="checkbox" />';
		if (  'menu_order-ASC' == eazyest_gallery()->sort_by() && $post_status != 'trash' )
			$columns["{$type}_drag"] = '<img src="' . $drag_url . '" alt="' . __( 'Draggable Column', 'eazyest-gallery' ) .  '" style="width:16px; height=16px"/>';
		$columns["{$type}_path"]   = _x( 'Name',    'column name', 'eazyest-gallery' );
		$columns[$title]           = _x( 'Caption', 'column name', 'eazyest-gallery' );
		$columns["{$type}_images"] = _x( 'Images',  'column name', 'eazyest-gallery' );
		$columns['tags']           = _x( 'Tags',    'column name', 'eazyest-gallery' );
		$columns[$comments]        = '<span><span class="vers"><div title="' . esc_attr__( 'Comments', 'eazyest-gallery' ) . '" class="comment-grey-bubble"></div></span></span>';
		$columns["{$type}_new"]    = '';
		$columns[$date]            = _x( 'Date' ,   'column name', 'eazyest-gallery' );
		return $columns;
	}

	/**
	 * Eazyest_Folder_Editor::page_row_actions()
	 * Add 'to Top' and 'to Bottom' links to row actions below caption
	 *
	 * @since 0.1.0 (r2)
	 * @param array $actions
	 * @param stdClass $post
	 * @uses admin_url()
	 * @uses add_query_arg()
	 * @uses get_current_screen()
	 * @return array
	 */
	function page_row_actions( $actions, $post ) {
		$post_type = eazyest_gallery()->post_type;
		$move_url = wp_nonce_url( add_query_arg( array(
				'post_type' => $post_type,
				'folder'    => $post->ID,
				'action'    =>	'move_folder'
			), admin_url( 'edit.php' ) ), 'move_folder' );
		// 'to top' and 'to bottom' shortcuts for manually sorted folders
		if ( $post->post_type == $post_type && $post->post_status != 'trash' && eazyest_gallery()->sort_folders ==  'menu_order-ASC' ) {
			$to_top_url    = add_query_arg( array( 'move' => 'to_top'    ), $move_url );
			$to_bottom_url = add_query_arg( array( 'move' => 'to_bottom' ), $move_url );
			$actions["to-top to-top-{$post->ID}"]       = "<a href='$to_top_url'>"    . __( 'to Top&nbsp;&#8593;', 'eazyest-gallery'   ) . "</a>";
			$actions["to-bottom to-bottom-{$post->ID}"] = "<a href='$to_bottom_url'>" . __( 'to Bottom&nbsp;&#8595;', 'eazyest-gallery') . "</a>";
		}
		if ( 'trash' == $post->post_status ) {
			if ( eazyest_folderbase()->has_subfolders( $post->ID ) )
				unset( $actions['delete'] );
		}
		$screen = get_current_screen();
		if ( 'post' ==  $screen->base && $post_type == $screen->id ) {
			$actions['trash'] = '<a class="submitdelete" title="' . __( 'Move this folder to the Trash', 'eazyest-gallery' ) . '" href="' . add_query_arg( array( 'post' => $post->post_parent, 'action' => 'folder_action', 'folder_action' => 'trash', 'folders' => $post->ID, 'bulk-folders' => wp_create_nonce( 'bulk-folders') ), admin_url( 'post.php') ) . '">' . __( 'Trash', 'eazyest-gallery' ) . '</a>';
		}
		return $actions;
	}

	/**
	 * Eazyest_Folder_Editor::custom_column()
	 * Output custom colmn for the post list
	 *
	 * @since 0.1.0 (r2)
	 * @param string $column
	 * @param int $post_id
	 * @return void
	 */
	function custom_column( $column, $post_id ) {
		$type = eazyest_gallery()->post_type;
		switch( $column ) {
			case "{$type}_drag" :
				echo $this->drag_handle( $post_id );
				break;
			case "{$type}_images" :
				echo $this->get_image_count( $post_id );
				break;
			case "{$type}_path" :
				echo $this->get_folder_path_display( $post_id );
				break;
			case "{$type}_new" :
				echo $this->get_new_folder_display( $post_id );
		}
	}

	/**
	 * Eazyest_Folder_Editor::get_new_folder_display()
	 * Display a little star in column prior to Date to show recently added Folder
	 * Use filter 'eazyest_gallery_folder_star_days' to change number of days to show the star (default 1)
	 *
	 * @since 0.1.0 (r2)
	 * @uses apply_filters()
	 * @uses get_post_time()
	 * @param mixed $post_id
	 * @return string <img> element
	 */
	function get_new_folder_display( $post_id ) {
		$days = apply_filters( 'eazyest_gallery_folder_star_days', 1 );
		$star_src = eazyest_gallery()->plugin_url . 'admin/images/new-item.png';
		$star_alt = __( 'Posted less than one day ago', 'eazyest-gallery' );
		$new_item = "<img src='$star_src' alt='$star_alt' title='$star_alt' style='width:16px; height=16px' />";
		$post_time = get_post_time( 'U', true, $post_id );
		return ( $days * 86401 > ( time() - $post_time ) ) ? $new_item : '';
	}

	/**
	 * Eazyest_Folder_Editor::get_image_count()
	 * Count attachments for this Folder
	 *
	 * @since 0.1.0 (r2)
	 * @uses get_children()
	 * @param int $post_id
	 * @return int
	 */
	function get_image_count( $post_id ) {
		$attachments = get_children( array(
			'post_parent' => $post_id,
	    'post_type'   => 'attachment',
	    'numberposts' => -1,
	    'post_status' => 'any'
		) );
		return count( $attachments );
	}

	/**
	 * Eazyest_Folder_Editor::drag_handle()
	 * html for inside drag-handle <td>
	 *
	 * @since 0.1.0 (r2)
	 * @param integer $post_id
	 * @return string
	 */
	function drag_handle( $post_id ) {
		$drag_handle  = "<span class='hide-if-no-js' title='" . __( 'Click an hold to sort', 'eazyest-gallery' ) . "'>&#8230;</span>";
		$drag_handle .= "<input class='drag-id' type='hidden' name='post_id-{$post_id}' value='{$post_id}' />";
		return $drag_handle;
	}

	/**
	 * Eazyest_Folder_Editor::display_subfolders()
	 * Display a list of sub-directories in admin list table
	 *
	 * @since 0.1.0 (r2)
	 * @uses current_user_can()
	 * @uses get_edit_post_link()
	 * @uses esc_attr()
	 * @param integer $post_id
	 * @return html markup of unordered list of subfolders
	 */
	function display_subfolders( $post_id ) {

		$subfolders = get_pages( array(
			'child_of'    => $post_id,
			'sort_column' => 'menu_order',
			'post_type'   => eazyest_gallery()->post_type,
		) );
		$display = '';
		if ( ! empty( $subfolders ) ){

			$display .= "\n<ul class='sub-directories'>";

			foreach( $subfolders as $folder ) {
				$display .= "\n\t<li>";
				$edit_name = $folder->post_name;
				$edit_name  = str_repeat( '&#8212; ', $this->folder_level( $folder->ID, $post_id ) + 1 ) . $edit_name;
				if ( current_user_can( 'edit_post', $folder->ID ) ) {
					$edit_link  = get_edit_post_link( $folder->ID, true );
					$edit_title = esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;', 'eazyest-gallery' ), $edit_name ) );
					$display   .= "<a href='$edit_link' title='$edit_title'>$edit_name</a>";
				} else {
					$display .= "$edit_name";
				}
				$display .= "</li>";
			}
			$display .= "\n</ul>";
		}
		return $display;
	}

	/**
	 * Eazyest_Folder_Editor::folder_level()
	 * Returns hierarchy level for a folder.
	 *
	 * @since 0.1.0 (r320)
	 * @param integer $post_id
	 * @param integer $parent_id
	 * @return integer
	 */
	function folder_level( $post_id, $parent_id = 0 ) {
		$level = 0;
		$folder = get_post( $post_id );
		if ( (int) $folder->post_parent > 0 ) {
			//sent level 0 by accident, by default, or because we don't know the actual level
			$find_main_page = (int) $folder->post_parent;
			while ( $find_main_page > 0 && $find_main_page != $parent_id ) {
				$parent = get_post( $find_main_page );

				if ( is_null( $parent ) )
					break;

				$level++;
				$find_main_page = (int) $parent->post_parent;
			}
		}
		return $level;
	}

	/**
	 * Eazyest_Folder_Editor::get_folder_path_display()
	 * Get display text for folder path
	 *
	 * @since 0.1.0 (r2)
	 * @param int $post_id
	 * @return string
	 */
	function get_folder_path_display( $post_id, $for = 'table' ) {
		global $post;
		$display = __( 'Not Saved', 'eazyest-gallery' );
		$gallery_path = ezg_get_gallery_path( $post_id );
		$parent_id = ( isset( $_GET['post'] ) ) ?	absint( $_GET['post'] ) : 0;
		if ( ! empty( $gallery_path ) ) {
			$edit_name = get_post( $post_id )->post_name;
			if ( 'table' == $for )
				$edit_name  = str_repeat( '&#8212; ', $this->folder_level( $post_id, $parent_id ) ) . $edit_name;
			if ( ( $post->ID == $post_id && 'table' != $for ) || ! current_user_can( 'edit_post', $post_id ) ) {
				$display    = " <strong>$edit_name</strong> ";
			} else {
				$edit_link  = get_edit_post_link( $post_id, true );
				$edit_title = esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;', 'eazyest-gallery' ), $edit_name ) );
				$display    = " <strong><a href='$edit_link' title='$edit_title'>$edit_name</a></strong> ";
			}

			// show sub-directories in manually sorted lists
			if ( 'table' == $for && eazyest_gallery()->sort_by() == 'menu_order-ASC' )
				$display .= $this->display_subfolders( $post_id );
		}
		return $display;
	}

	/**
	 * Eazyest_Folder_Editor::hidden_order_field()
	 * Add a hidden input field with post IDs in sorted order
	 *
	 * @param mixed $items
	 * @param string $type
	 * @return
	 */
	function hidden_order_field( $items, $type = 'pages' ) {
		$changed = $type == 'pages' ? isset( $_GET['folder_orderby'] ) : isset( $_GET['orderby'] );
		$changed = intval( $changed );
		$hidden_order_field = wp_nonce_field( "save_gallery-{$type}", "gallery_nonce-{$type}", false, false );
		$hidden_order_field .= "\n<input type='hidden' id='gallery-order-{$type}' name='gallery-order-{$type}' value ='";
			if ( ! empty( $items ) ) {
				foreach( $items as $item ) {
					$hidden_order_field .= "{$item->ID} ";
				}
			}
			$hidden_order_field = rtrim( $hidden_order_field );
			$hidden_order_field .=  "' />";
			$hidden_order_field .= "\n<input type='hidden' id='gallery-changed-{$type}' name='gallery-changed-{$type}' value='$changed'/>\n";
		return $hidden_order_field;
	}

	/**
	 * Eazyest_Folder_Editor::save_columns_button()
	 * Add a <form> and submit <input> to the Gallery list screen
	 *
	 * @uses wp_enqueue_script()
	 * @since 0.1.0 (r2)
	 * @param array $views
	 * @return array
	 */
	function save_columns_button( $views ) {

		if ( ( isset( $_REQUEST['post_status'] ) && 'trash' == $_REQUEST['post_status']  ) || ( eazyest_gallery()->sort_by() != 'menu_order-ASC' ) )
			return $views;

		global $wp_query;
    $folders = $wp_query->posts;
    $save_button = "<form id='posts-saver' action='edit.php?action=save_gallery' method='post'>";
    $save_button .= $this->hidden_order_field( $folders );
		$post_type = eazyest_gallery()->post_type;
		$disabled = isset( $_GET['orderby'] ) ? '' : "disabled='disabled' ";
		$button_text = __( 'Save Changes', 'eazyest-gallery' );
		$save_button .= "<input type='submit' name='save_gallery' value='$button_text' id='save-order' class='button button-primary button-small save-gallery' $disabled/>";
		$save_button .= "</form>";
		$views['save-sort'] = $save_button;
		wp_enqueue_script( 'eazyest-gallery-admin' );

		return $views;
	}

  /**
   * Eazyest_Folder_Editor::media_buttons()
   * Remove Media Buttins from edit screen when Folder has not been saved yet
   *
   * @since 0.1.0 (r2)
   * @uses remove_all_actions()
   * @return void
   */
  function media_buttons() {
  	global $post;
  	if ( $post->post_type == eazyest_gallery()->post_type ) {
  		$gallery_path = ezg_get_gallery_path( $post->ID );
  		if ( '' == $gallery_path )
				remove_all_actions( 'media_buttons' );
  	}
  }

  /**
   * Eazyest_Folder_Editor::media_view_strings()
   * Remove and change strings in the Media uploader
   *
   * @since 0.1.0 (r2)
   * @param array $strings
   * @param WP_post $post
   * @return array
   */
  function media_view_strings( $strings, $post ) {
		if ( isset( $post ) && eazyest_gallery()->post_type == $post->post_type ) {
	 		// disable some views that have no purpose in Eazyest Gallery
	 		$disabled = array( 'selectFiles', 'createNewGallery', 'insertFromUrlTitle', 'createGalleryTitle' );
	 		foreach( $disabled as $string )
	 			unset( $strings[$string] );
	 		$strings['allMediaItems']      = __( 'Select a view', 'eazyest-gallery'           );
	 		$strings['uploadedToThisPost'] = __( 'Uploaded to this folder', 'eazyest-gallery' );
			$strings['insertIntoPost']     = __( 'Done uploading', 'eazyest-gallery'          );
		}
		return $strings;
  }

  /**
   * Eazyest_Folder_Editor::media_send_to_editor()
   * filter for 'media_send_to_editor'
   * Don't send images to editor when we are on the edit folder screen
   *
   * @since 0.1.0 (r2)
   * @param string $html
   * @param integer $id
   * @param WP_Post $attachment
   * @return string
   */
  function media_send_to_editor( $html, $id, $attachment ) {

  	if ( eazyest_folderbase()->refered_by_folder() )
  		$html = null;

  	return $html;
  }

  /**
   * Eazyest_Folder_Editor::submit_meta_box()
   * Replace WordPress submitdiv with Eazyest Gallery submitdiv.
   *
   * @since 0.1.0 (r96)
   * @uses remove_meta_box()
   * @uses add_meta_box()
   * @return void
   */
  function submit_meta_box(){
  	remove_meta_box( 'submitdiv', eazyest_gallery()->post_type, 'side' );
  	// re-add submitdiv with priority high because WordPress will ignore removed core meta boxes
  	add_meta_box( 'submitdiv', __( 'Publish', 'eazyest-gallery' ), array( $this, 'folder_submit_meta_box' ), eazyest_gallery()->post_type, 'side', 'high' );
  }

	/**
	 * Eazyest_Folder_Editor::folder_submit_meta_box()
	 * Replacement submit box which includes the 'hidden' visibility
	 * @see http://core.trac.wordpress.org/browser/tags/3.5.1/wp-admin/includes/meta-boxes.php
	 *
	 * @since 0.1.0 (r96)
	 * @param WP_Post $post
	 * @return void
	 */
	function folder_submit_meta_box( $post ) {global $action;

		$post_type = $post->post_type;
		$post_type_object = get_post_type_object($post_type);
		$can_publish = current_user_can($post_type_object->cap->publish_posts);
		?>
		<div class="submitbox" id="submitpost">

		<div id="minor-publishing">

		<?php // Hidden submit button early on so that the browser chooses the right button when form is submitted with Return key ?>
		<div style="display:none;">
		<?php submit_button( __( 'Save', 'eazyest-gallery' ), 'button', 'save' ); ?>
		</div>

		<div id="minor-publishing-actions">
		<div id="save-action">
		<?php if ( 'publish' != $post->post_status && 'future' != $post->post_status && 'pending' != $post->post_status ) { ?>
		<input <?php if ( 'private' == $post->post_status ) { ?>style="display:none"<?php } ?> type="submit" name="save" id="save-post" value="<?php esc_attr_e( 'Save Draft', 'eazyest-gallery' ); ?>" class="button" />
		<?php } elseif ( 'pending' == $post->post_status && $can_publish ) { ?>
		<input type="submit" name="save" id="save-post" value="<?php esc_attr_e( 'Save as Pending', 'eazyest-gallery' ); ?>" class="button" />
		<?php } ?>
		<span class="spinner"></span>
		</div>
		<?php if ( $post_type_object->public && 'hidden' != $post->post_status ) : ?>
		<div id="preview-action">
		<?php
		if ( 'publish' == $post->post_status ) {
			$preview_link = esc_url( get_permalink( $post->ID ) );
			$preview_button = __( 'Preview Changes', 'eazyest-gallery' );
		} else {
			$preview_link = set_url_scheme( get_permalink( $post->ID ) );
			$preview_link = esc_url( apply_filters( 'preview_post_link', add_query_arg( 'preview', 'true', $preview_link ) ) );
			$preview_button = __( 'Preview', 'eazyest-gallery' );
		}
		?>
		<a class="preview button" href="<?php echo $preview_link; ?>" target="wp-preview" id="post-preview"><?php echo $preview_button; ?></a>
		<input type="hidden" name="wp-preview" id="wp-preview" value="" />
		</div>
		<?php endif; // public post type ?>
		<div class="clear"></div>
		</div><!-- #minor-publishing-actions -->

		<div id="misc-publishing-actions">

		<div class="misc-pub-section"><label for="post_status"><?php _e( 'Status:', 'eazyest-gallery' ) ?></label>
		<span id="post-status-display">
		<?php
		switch ( $post->post_status ) {
			case 'private':
				_e( 'Privately Published', 'eazyest-gallery' );
				break;
			case 'hidden':
				_e( 'Hidden Published', 'eazyest-gallery' );
				break;
			case 'publish':
				_e( 'Published', 'eazyest-gallery' );
				break;
			case 'future':
				_e( 'Scheduled', 'eazyest-gallery' );
				break;
			case 'pending':
				_e( 'Pending Review', 'eazyest-gallery' );
				break;
			case 'draft':
			case 'auto-draft':
				_e( 'Draft', 'eazyest-gallery' );
				break;
		}
		?>
		</span>
		<?php if ( 'publish' == $post->post_status || 'private' == $post->post_status || 'hidden' == $post->post_status || $can_publish ) { ?>
		<a href="#post_status" <?php if ( 'private' == $post->post_status ) { ?>style="display:none;" <?php } ?>class="edit-post-status hide-if-no-js"><?php _e( 'Edit', 'eazyest-gallery' ) ?></a>

		<div id="post-status-select" class="hide-if-js">
		<input type="hidden" name="hidden_post_status" id="hidden_post_status" value="<?php echo esc_attr( ( 'auto-draft' == $post->post_status ) ? 'draft' : $post->post_status); ?>" />
		<select name='post_status' id='post_status'>
		<?php if ( 'publish' == $post->post_status ) : ?>
		<option<?php selected( $post->post_status, 'publish' ); ?> value='publish'><?php _e( 'Published', 'eazyest-gallery' ) ?></option>
		<?php elseif ( 'private' == $post->post_status ) : ?>
		<option<?php selected( $post->post_status, 'private' ); ?> value='publish'><?php _e( 'Privately Published', 'eazyest-gallery' ) ?></option>
		<?php elseif ( 'hidden' == $post->post_status ) : ?>
		<option<?php selected( $post->post_status, 'hidden' ); ?> value='publish'><?php _e( 'Hidden Published', 'eazyest-gallery' ) ?></option>
		<?php elseif ( 'future' == $post->post_status ) : ?>
		<option<?php selected( $post->post_status, 'future' ); ?> value='future'><?php _e( 'Scheduled', 'eazyest-gallery' ) ?></option>
		<?php endif; ?>
		<option<?php selected( $post->post_status, 'pending' ); ?> value='pending'><?php _e( 'Pending Review', 'eazyest-gallery' ) ?></option>
		<?php if ( 'auto-draft' == $post->post_status ) : ?>
		<option<?php selected( $post->post_status, 'auto-draft' ); ?> value='draft'><?php _e( 'Draft', 'eazyest-gallery' ) ?></option>
		<?php else : ?>
		<option<?php selected( $post->post_status, 'draft' ); ?> value='draft'><?php _e( 'Draft', 'eazyest-gallery' ) ?></option>
		<?php endif; ?>
		</select>
		 <a href="#post_status" class="save-post-status hide-if-no-js button"><?php _e( 'OK', 'eazyest-gallery' ); ?></a>
		 <a href="#post_status" class="cancel-post-status hide-if-no-js"><?php _e( 'Cancel', 'eazyest-gallery' ); ?></a>
		</div>

		<?php } ?>
		</div><!-- .misc-pub-section -->

		<div class="misc-pub-section" id="visibility">
		<?php _e( 'Visibility:', 'eazyest-gallery' ); ?> <span id="post-visibility-display"><?php

		if ( 'private' == $post->post_status ) {
			$post->post_password = '';
			$visibility = 'private';
			$visibility_trans = __( 'Private', 'eazyest-gallery' );
		} elseif( 'hidden' == $post->post_status ) {
			$visibility = 'hidden';
			$visibility_trans = __( 'Hidden', 'eazyest-gallery' );
		} elseif ( !empty( $post->post_password ) ) {
			$visibility = 'password';
			$visibility_trans = __( 'Password protected', 'eazyest-gallery' );
		} elseif ( $post_type == 'post' && is_sticky( $post->ID ) ) {
			$visibility = 'public';
			$visibility_trans = __( 'Public, Sticky', 'eazyest-gallery' );
		} else {
			$visibility = 'public';
			$visibility_trans = __( 'Public', 'eazyest-gallery' );
		}

		echo esc_html( $visibility_trans ); ?></span>
		<?php if ( $can_publish ) { ?>
		<a href="#visibility" class="edit-visibility hide-if-no-js"><?php _e( 'Edit', 'eazyest-gallery' ); ?></a>

		<div id="post-visibility-select" class="hide-if-js">
		<input type="hidden" name="hidden_post_password" id="hidden-post-password" value="<?php echo esc_attr($post->post_password); ?>" />
		<?php if ($post_type == 'post'): ?>
		<input type="checkbox" style="display:none" name="hidden_post_sticky" id="hidden-post-sticky" value="sticky" <?php checked(is_sticky($post->ID)); ?> />
		<?php endif; ?>
		<input type="hidden" name="hidden_post_visibility" id="hidden-post-visibility" value="<?php echo esc_attr( $visibility ); ?>" />
		<input type="radio" name="visibility" id="visibility-radio-public" value="public" <?php checked( $visibility, 'public' ); ?> /> <label for="visibility-radio-public" class="selectit"><?php _e( 'Public', 'eazyest-gallery' ); ?></label><br />
		<?php if ( $post_type == 'post' && current_user_can( 'edit_others_posts' ) ) : ?>
		<span id="sticky-span"><input id="sticky" name="sticky" type="checkbox" value="sticky" <?php checked( is_sticky( $post->ID ) ); ?> /> <label for="sticky" class="selectit"><?php _e( 'Stick this post to the front page', 'eazyest-gallery' ); ?></label><br /></span>
		<?php endif; ?>
		<input type="radio" name="visibility" id="visibility-radio-password" value="password" <?php checked( $visibility, 'password' ); ?> /> <label for="visibility-radio-password" class="selectit"><?php _e( 'Password protected', 'eazyest-gallery' ); ?></label><br />
		<span id="password-span"><label for="post_password"><?php _e( 'Password:', 'eazyest-gallery' ); ?></label> <input type="text" name="post_password" id="post_password" value="<?php echo esc_attr($post->post_password); ?>" /><br /></span>
		<input type="radio" name="visibility" id="visibility-radio-private" value="private" <?php checked( $visibility, 'private' ); ?> /> <label for="visibility-radio-private" class="selectit"><?php _e( 'Private', 'eazyest-gallery' ); ?></label><br />
		<input type="radio" name="visibility" id="visibility-radio-hidden" value="hidden" <?php checked( $visibility, 'hidden' ); ?> /> <label for="visibility-radio-hidden" class="selectit"><?php _e( 'Hidden', 'eazyest-gallery' ); ?></label><br />
		<p>
		 <a href="#visibility" class="save-post-visibility hide-if-no-js button"><?php _e( 'OK', 'eazyest-gallery' ); ?></a>
		 <a href="#visibility" class="cancel-post-visibility hide-if-no-js"><?php _e( 'Cancel', 'eazyest-gallery' ); ?></a>
		</p>
		</div>
		<?php } ?>

		</div><!-- .misc-pub-section -->

		<?php
		// translators: Publish box date format, see http://php.net/date
		$datef = __( 'M j, Y @ G:i', 'eazyest-gallery' );
		if ( 0 != $post->ID ) {
			if ( 'future' == $post->post_status ) { // scheduled for publishing at a future date
				$stamp = __( 'Scheduled for: <b>%1$s</b>', 'eazyest-gallery' );
			} else if ( 'publish' == $post->post_status || 'private' == $post->post_status ) { // already published
				$stamp = __( 'Published on: <b>%1$s</b>', 'eazyest-gallery' );
			} else if ( '0000-00-00 00:00:00' == $post->post_date_gmt ) { // draft, 1 or more saves, no date specified
				$stamp = __( 'Publish <b>immediately</b>', 'eazyest-gallery' );
			} else if ( time() < strtotime( $post->post_date_gmt . ' +0000' ) ) { // draft, 1 or more saves, future date specified
				$stamp = __( 'Schedule for: <b>%1$s</b>', 'eazyest-gallery' );
			} else { // draft, 1 or more saves, date specified
				$stamp = __( 'Publish on: <b>%1$s</b>', 'eazyest-gallery' );
			}
			$date = date_i18n( $datef, strtotime( $post->post_date ) );
		} else { // draft (no saves, and thus no date specified)
			$stamp = __( 'Publish <b>immediately</b>', 'eazyest-gallery' );
			$date = date_i18n( $datef, strtotime( current_time( 'mysql') ) );
		}

		if ( $can_publish ) : // Contributors don't get to choose the date of publish ?>
		<div class="misc-pub-section curtime">
			<span id="timestamp">
			<?php printf($stamp, $date); ?></span>
			<a href="#edit_timestamp" class="edit-timestamp hide-if-no-js"><?php _e( 'Edit', 'eazyest-gallery' ) ?></a>
			<div id="timestampdiv" class="hide-if-js"><?php touch_time(($action == 'edit'), 1); ?></div>
		</div><?php // /misc-pub-section ?>
		<?php endif; ?>

		<?php do_action( 'post_submitbox_misc_actions'); ?>
		</div>
		<div class="clear"></div>
		</div>

		<div id="major-publishing-actions">
		<?php do_action( 'post_submitbox_start'); ?>
		<div id="delete-action">
		<?php
		if ( current_user_can( "delete_post", $post->ID ) ) {
			if ( !EMPTY_TRASH_DAYS )
				$delete_text = __( 'Delete Permanently', 'eazyest-gallery' );
			else
				$delete_text = __( 'Move to Trash', 'eazyest-gallery' );
			?>
		<a class="submitdelete deletion" href="<?php echo get_delete_post_link($post->ID); ?>"><?php echo $delete_text; ?></a><?php
		} ?>
		</div>

		<div id="publishing-action">
		<span class="spinner"></span>
		<?php
		if ( !in_array( $post->post_status, array( 'publish', 'future', 'private') ) || 0 == $post->ID ) {
			if ( $can_publish ) :
				if ( !empty($post->post_date_gmt) && time() < strtotime( $post->post_date_gmt . ' +0000' ) ) : ?>
				<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Schedule', 'eazyest-gallery' ) ?>" />
				<?php submit_button( __( 'Schedule', 'eazyest-gallery' ), 'primary button-large', 'publish', false, array( 'accesskey' => 'p' ) ); ?>
		<?php	else : ?>
				<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Publish', 'eazyest-gallery' ) ?>" />
				<?php submit_button( __( 'Publish', 'eazyest-gallery' ), 'primary button-large', 'publish', false, array( 'accesskey' => 'p' ) ); ?>
		<?php	endif;
			else : ?>
				<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Submit for Review', 'eazyest-gallery' ) ?>" />
				<?php submit_button( __( 'Submit for Review', 'eazyest-gallery' ), 'primary button-large', 'publish', false, array( 'accesskey' => 'p' ) ); ?>
		<?php
			endif;
		} else { ?>
				<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Update', 'eazyest-gallery' ) ?>" />
				<input name="save" type="submit" class="button button-primary button-large" id="publish" accesskey="p" value="<?php esc_attr_e( 'Update', 'eazyest-gallery' ) ?>" />
		<?php
		} ?>
		</div>
		<div class="clear"></div>
		</div>
		</div>

		<?php
	}

  /**
   * Eazyest_Folder_Editor::folder_information()
   * Output folder information in a metabox
   *
   * @since 0.1.0 (r2)
   * @uses get_post()
	 * @return void
   */
  function folder_information() {
  	if ( $this->bail() )
			return;

  	global $post;
  	$folder = $post;
  	$gallery_path = ezg_get_gallery_path( $post->ID );
  	$path[] = $this->get_folder_path_display( $folder->ID, 'meta' );
  	if ( empty( $gallery_path ) && isset( $_GET['post_parent'] ) ) {
  		$folder->post_parent = absint( $_GET['post_parent'] );
  	}
  	while ( 0 < $folder->post_parent ) {
  		$folder = get_post( $folder->post_parent );
  		$path[] = $this->get_folder_path_display( $folder->ID, 'metabox' );
  	}
  	$path = array_reverse( $path );
  	$gallery_display = implode( '/', $path );
  	?>
  	<div class="misc-pub-section">
	  	<p><?php printf( __( 'Path: %s', 'eazyest-gallery' ), $gallery_display ); ?></p>
	  	<input type="hidden" id="gallery_path" name="gallery_path" value="<?php echo $gallery_path ?>" />
  	</div>
  	<?php
  }

  /**
   * Eazyest_Folder_Editor::donate()
   * Add a donate button to the gallleryfolder edit screen
   *
   * @since 0.1.0 (r2)
   * @return void
   */
  function donate() {
  	if ( $this->bail() )
			return;
  	?>
  	<div class="misc-pub-section">
	  	<p>
				<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=22A3Y8ZUGR6PE" title="<?php esc_attr_e( 'Support the development of Eazyest Galery', 'eazyest-gallery' ); ?>">
					<img src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" alt="PayPal - The safer, easier way to pay online!" /><br />
					<?php _e(  'Support the development of Eazyest Galery', 'eazyest-gallery' ); ?>
				</a>
			</p>
  	</div>
  	<?php
  }

  /**
   * Eazyest_Folder_Editor::dropdown_pages_args()
   * Change parent folder dropdown if user wants to add sub-folder
   *
   * @since 0.1.0 (r2)
   * @uses get_current_screen()
   * @param array $dropdown_args
   * @param WP_Post $post
   * @return array
   */
  function dropdown_pages_args(  $dropdown_args, $post ) {
  	if ( $this->bail() )
  		return $dropdown_args;
  	$screen = get_current_screen();
		if ( 'add' == $screen->action && isset( $_GET['post_parent'] ) )
			$dropdown_args['selected'] = $_GET['post_parent'];
		return  $dropdown_args;
  }

  /**
   * Eazyest_Folder_Editor::list_table_attachments()
   * Return list table with images to edit
   *
   * @uses wp_enqueue_sscript()
   * @return string
   */
  function list_table_attachments() {
  	global $post;
  	if ( $this->bail() )
  		return;
 		do_action( 'eazyest_gallery_before_list_items', $post->ID );
  	require_once( eazyest_gallery()->plugin_dir . 'admin/class-eazyest-media-list-table.php' );
  	$list_table = new Eazyest_Media_List_Table( array( 'plural' => 'media'  ) );
		$list_table->prepare_items();
		?>
		<div id="attached-images-<?php echo $post->ID ?>" class="attached-images after-editor">
		<?php
			$list_table->views();
			$list_table->display();
		?>
		</div>
		<?php
		wp_enqueue_script( 'eazyest-gallery-admin' );
  }

  /**
   * Eazyest_Folder_Editor::list_table_folders()
   * Return list table with sub-folders to edit
   *
   * @since 0.1.0 (r2)
   * @uses wp_enqueue_script()
   * @return string
   */
  function list_table_folders() {
  	global $post;
  	if ( $this->bail() )
  		return;
  	require_once( eazyest_gallery()->plugin_dir . 'admin/class-eazyest-folder-list-table.php' );
  	$list_table = new Eazyest_Folder_List_Table( array( 'plural' => 'pages'  ) );
		$list_table->prepare_items();
		?>
		<div id="sub-folders-<?php echo $post->ID ?>" class="sub-folders after-editor">
		<?php
			if ( $list_table->has_items() && 'menu_order-ASC' == eazyest_gallery()->sort_by() )
				wp_enqueue_script( 'eazyest-gallery-admin' );
			$list_table->views();
			$list_table->display();
		?>
		</div>
		<?php
  }

  /**
   * Eazyest_Folder_Editor::upload_dir()
   * Filter the upload dir for Eazyest Gallery Folders
   *
   * @param array $upload
   * @return array
   */
  function upload_dir( $upload ) {
		$post_type = isset( $_REQUEST['post_type'] ) ? $_REQUEST['post_type'] : '';
		$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
		if ( 0 == $post_id )
			$post_id = isset( $_REQUEST['post'] ) ? intval( $_REQUEST['post'] ) : 0;
		if ( 0 != $post_id )
				$post_type = get_post_type( $post_id );
		if ( 'attachment' == $post_type )
		  $post_type = get_post_type( get_post( $post_id )->post_parent );
		// if it is galleryfolder, change upload_dir
  	if ( eazyest_gallery()->post_type == $post_type ) {
			$gallery_path      = ezg_get_gallery_path( $post_id );
			$upload['path']    = untrailingslashit( eazyest_gallery()->root()  . $gallery_path );
			$upload['url']     = untrailingslashit( eazyest_gallery()->address() . $gallery_path );
			$upload['subdir']  = untrailingslashit( $gallery_path );
			$upload['basedir'] = untrailingslashit( eazyest_gallery()->root() );
			$upload['baseurl'] = untrailingslashit( eazyest_gallery()->address() );
		}
		return $upload;
	}
} // Eazyest_Folder_Editor

/**
 * ezg_donate()
 * Adds a donate button to the Publish box
 *
 * @since 0.1.0 (r220)
 * @return void
 */
function ezg_donate() {
	eazyest_admin()->folder_editor()->donate();
}
