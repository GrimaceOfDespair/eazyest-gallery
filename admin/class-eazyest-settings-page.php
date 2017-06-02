<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Eazyest_Settings_Page
 * Admin Settings Page for Lazyest Gallery
 *
 * @package Eazyest Gallery
 * @subpackage Admin/Settings
 * @author Marcel Brinkkemper
 * @copyright 2013 Brimosoft
 * @version 0.2.0 (r318)
 * @since 0.1.0 (r2)
 * @access public
 */
class Eazyest_Settings_Page {

	/**
	 * single object in memory
	 */
	private static $instance;

	/**
	 * Eazyest_Settings_Page::__construct()
	 *
	 * @return void
	 */
	function __construct(){}

	/**
	 * Eazyest_Settings_Page::init()
	 * Initialize
	 *
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	private function init() {
		$this->actions();
	}

	/**
	 * Eazyest_Settings_Page::instance()
	 *
	 * @since 0.1.0 (r2)
	 * @return Eazyest_Settings_Page object
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Eazyest_Settings_Page;
			self::$instance->init();
		}
		return self::$instance;
	}

	/**
	 * Eazyest_Settings_Page::actions()
	 * add WordPress actions
	 *
	 * @since 0.1.0 (r2)
	 * @uses add_action()
	 * @return void
	 */
	function actions() {
		add_action( 'eazyest_gallery_main_settings', array( $this, 'main_settings' ), 1 );

		add_action( 'eazyest_gallery_settings_section', array( $this, 'folder_settings'   ), 10 );
		add_action( 'eazyest_gallery_settings_section', array( $this, 'image_settings'    ), 11 );
		add_action( 'eazyest_gallery_settings_section', array( $this, 'custom_text' 	  ), 12 );
		add_action( 'eazyest_gallery_settings_section', array( $this, 'advanced_settings' ), 13 );


		add_action( 'admin_head',            array( $this, 'admin_style'           ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_notices',         array( $this, 'admin_notices'         ) );
	}

	/**
	 * Eazyest_Settings_Page::admin_style()
	 * Inline stylesheet for the settings page
	 *
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	function admin_style() {
		if ( get_current_screen()->base != 'settings_page_eazyest-gallery' )
			return;

		$images_dir = eazyest_gallery()->plugin_url . 'admin/images/';
		$directory_png   = $images_dir . 'directory.png';
		$folder_open_png = $images_dir . 'folder_open.png';
		$file_png        = $images_dir . 'file.png';
		$spinner_gif     = $images_dir . 'spinner.gif';
		?>
		<style type="text/css" media="screen">
		#file-tree {
			background: white;
			border: 1px solid #DFDFDF;
			-webkit-border-radius: 3px;
			border-radius: 3px;
			position:absolute;
			top: 25px;
			left: 0;
			width: 25em;
			z-index: 1000;
			display:none;
		}

		ul.jquery-filetree {
			font-family: Verdana, sans-serif;
			font-size: 11px;
			line-height: 18px;
			padding: 0px;
			margin: 0px;
		}

		ul.jquery-filetree li {
			list-style: none;
			padding: 0px;
			padding-left: 20px;
			margin: 0px;
			white-space: nowrap;
		}

		ul.jquery-filetree a {
			color: #333;
			text-decoration: none;
			display: block;
			padding: 0px 2px;
		}

		ul.jquery-filetree a:hover {
			background: #BDF;
		}
		.jquery-filetree li.directory {
			background: url(<?php echo $directory_png; ?>) left top no-repeat;
		}
		.jquery-filetree li.expanded {
			background: url(<?php echo $folder_open_png; ?>) left top no-repeat;
		}
		.jquery-filetree li.file {
			background: url(<?php echo $file_png ?>) left top no-repeat;
		}
		.jquery-filetree li.wait {
			background: url(<?php echo $spinner_gif ?>) left top no-repeat;
		}
		#eazyest-ajax-response {
			color: #cc0000;
		}
		#eazyest-ajax-response code {
			color:  #ff0000;
			font-size:12px;
		}
		.form-table td p.gallery-folder {
			border-color: #dfdfdf;	-webkit-border-radius: 3px;
			border-radius: 3px;
			border-width: 1px;
			border-style: solid;
			margin-top: 1px;
			padding-left: 2px;
			width: 25em;
		}
		</style>
		<?php
	}

	/**
	 * Eazyest_Settings_Page::admin_enqueue_scripts()
	 * Register scripts for the settings page
	 *
	 * @since 0.1.0 (r2)
	 * @uses wp_register_script()
	 * @uses wp_localize_script()
	 * @return void
	 */
	function admin_enqueue_scripts() {
		$j = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? 'js' : 'min.js';
		wp_register_script( 'jquery-filetree',          eazyest_gallery()->plugin_url . "admin/js/jquery.filetree.$j",          array( 'jquery' ),          '1.01-r134',  true );
		wp_register_script( 'eazyest-gallery-settings', eazyest_gallery()->plugin_url . "admin/js/eazyest-gallery-settings.$j", array( 'jquery-filetree' ), '0.1.0-r173', true );

		wp_localize_script( 'eazyest-gallery-settings', 'fileTreeSettings', $this->filetree_args() );
	}

	/**
	 * Eazyest_Settings_Page::admin_notices()
	 * Show admin notice after redirecting on activate.
	 *
	 * @since 0.1.0 (r75)
	 * @return void
	 */
	function admin_notices() {
		$messages = $errors = array();
		if ( isset( $_GET['eazyest-activate'] ) ) {
			$messages[] = __( 'plugin <strong>activated</strong>. Please check your settings', 'eazyest-gallery' );
		}
		if ( ! empty( $messages ) ) {
			$message = implode( '<br />', $messages );
			echo "<div class='updated'><p>$message</p></div>";
		}
	}

	/**
	 * Eazyest_Settings_Page::filetree_args()
	 * Array to set javascript argument to initialize jquery.filetree
	 *
	 * @since 0.1.0 (r2)
	 * @uses admin_url()
	 * @return array
	 */
	function filetree_args() {
		return array(
			'root'             => eazyest_gallery()->home_dir(),
			'script'           => admin_url( 'admin-ajax.php' ),
			'loadMessage'      => __( 'Loading..',                               'eazyest-gallery' ),
			'errorMessage'     => __( 'You cannot use %s for a gallery folder.', 'eazyest-gallery' ),
			'notExistsMessage' => __( 'This folder does not exist yet.',         'eazyest-gallery' ),
			'notCreateMessage' => __( 'Could not create folder %s.',             'eazyest-gallery' ),
		);
	}

	// Sections definitions ------------------------------------------------------

	/**
	 * Eazyest_Settings_Page::section()
	 * Returns array( 'title', 'description' ) per settings page section
	 * Add or change sections by adding filters:
	 * apply_filters( 'eazyest_gallery_settings_sections', $sections );
	 *
	 * Added custom-text settings to change button text
	 * @since 0.1.4
	 *
	 * @since 0.1.0 (r2)
	 * @uses apply_filters()
	 * @param string $section
	 * @return array
	 */
	function section( $section = '' ) {
		$sections = array(
			'main-settings' => array(
				'title'       => __( 'Main Settings', 'eazyest-gallery' ),
				'description' => __( 'Main gallery settings to select your gallery', 'eazyest-gallery' ),
			),
			'folder-settings' => array(
				'title'       => __( 'Folder Options', 'eazyest-gallery' ),
				'description' => __( 'How your folders will be displayed', 'eazyest-gallery' ),
			),
			'image-settings' => array(
				'title'       => __( 'Image Options', 'eazyest-gallery' ),
				'description' => __( 'How your images will be displayed', 'eazyest-gallery' ),
			),
			'advanced-settings' => array(
				'title'       => __( 'Advanced Options', 'eazyest-gallery' ),
				'description' => __( 'Options for advanced users', 'eazyest-gallery' ),
			),
			'custom-text' => array(
				'title'		  => __('Custom Text', 'eazyest-gallery' ),
				'description' => __('Custom text for the buttons', 'eazyest-gallery'),
			)
		);
		if ( eazyest_gallery()->new_instrall || ! eazyest_gallery()->right_path() )
			$sections['main-settings']['description'] .= '<br />' . __( 'You have to select a folder on your server before you can use Eazyest Gallery', 'eazyest-gallery' );
		$sections = apply_filters( 'eazyest_gallery_settings_sections', $sections );
		return isset( $sections[$section] ) ? $sections[$section] : array();
	}

	// Fields definitions --------------------------------------------------------

	/**
	 * Eazyest_Settings_Page::fields()
	 * Returns option fields array( 'title', 'callback'(optional) ) per settings page section
	 * Add more fields per section by adding filters:
	 * apply_filters( 'eazyest_gallery_folder_settings'   $fields );
	 * apply_filters( 'eazyest_gallery_image_settings',   $fields );
	 * apply_filters( 'eazyest_gallery_advanced_settings' $fields );
	 *
	 * @since 0.1.0 (r2)
	 * @uses apply_filters()
	 * @param string $section
	 * @return array
	 */
	function fields( $section ) {
		$sections = array();
		$sections['main-settings'] = array(
				'gallery_folder' => array(
					'title' => __( 'Your gallery folder', 'eazyest-gallery' )
				),
				'gallery_title' => array(
					'title' => __( 'Gallery title', 'eazyest-gallery' )
				),
				'donate' => array(
					'title' => __( 'Support Eazyest Gallery', 'eazyest-gallery' )
				)
			);
		$sections['folder-settings'] = apply_filters( 'eazyest_gallery_folder_settings', array(
			'folders_columns' => array(
				'title' => __( 'Icon columns', 'eazyest-gallery' )
			),
			'folders_page' => array(
				'title' => __( 'Icons per page', 'eazyest-gallery' )
			),
			'sort_folders' => array(
				'title' => __( 'Sort folders by', 'eazyest-gallery' )
			),
			'count_subfolders' => array(
				'title' => __( 'Count images', 'eazyest-gallery' )
			),
			'folder_image' => array(
				'title' => __( 'Folder icons', 'eazyest-gallery' )
			)
		) );
		$sections['image-settings'] = apply_filters( 'eazyest_gallery_image_settings', array(
			'thumbs_columns' => array(
				'title' => ( 'Thumbnail columns' )
			),
			'thumbs_page' => array(
				'title' => __( 'Thumbnails per page', 'eazyest-gallery' )
			),
			// do not set this until native WordPress gallery offers filter for attachment excerpt
			/*
			  'thumb_caption' => array(
			 	'title' => __( 'Captions', 'eazyest-gallery' )
			), */
			'sort_thumbnails' => array(
				'title' => __( 'Sort images by', 'eazyest-gallery' )
			),
			'on_thumb_click' => array(
				'title' => __( 'Thumbnail click', 'eazyest-gallery' )
			),
			'on_slide_click' => array(
				'title' => __( 'Attachment click', 'eazyest-gallery' )
			),
			'listed_as' => array(
				'title' => __( 'List name', 'eazyest-gallery' )
			)
		) );
		$sections['custom-text'] = apply_filters( 'eazyest_gallery_custom_text', array (
			'more_thumbnail_button_text' => array(
				'title' => __('More thumbnails button text', 'eazyest-gallery' )
			),
			'slideshow_button_text' => array(
				'title' => __('Slideshow button text', 'eazyest-gallery' )
			),
		) );
		$sections['advanced-settings'] = apply_filters( 'eazyest_gallery_advanced_settings', array(
			'gallery_slug' => array(
				'title' => __( 'Gallery slug', 'eazyest-gallery' )
			),
			'page_slug' => array(
				'title' => __('Page slug', 'eazyest-gallery' )
			),
			'viewer_level' => array(
				'title' => __( 'Minimum viewer role', 'eazyest-gallery' )
			)
		) );
		$sections = apply_filters( 'eazyest-gallery-settings-section-fields', $sections );
		return isset( $sections[$section] ) ? $sections[$section] : array();
	}

	// Display section -----------------------------------------------------------

	/**
	 * Eazyest_Settings_Page::display_section()
	 * Output a section on the Settings screen
	 *
	 * @since 0.1.0 (r2)
	 * @param string $section
	 * @return void
	 */
	function display_section( $section = '' ) {
		if ( empty( $section ) )
			return;

		$section_parts = $this->section( $section );
		$fields = $this->fields( $section );
		?>
		<h3><?php echo $section_parts['title']  ?></h3>
		<p><?php  echo $section_parts['description'] ?></p>
		<?php if ( ! empty( $fields ) ) : ?>
		<table class="form-table <?php echo $section_parts['title'] ; ?> ">
			<tbody>
				<?php foreach( $fields as $field => $parts ) : ?>
				<tr>
					<th scope="row"><?php echo $parts['title']; ?></th>
					<td>
						<?php if ( ! isset( $parts['callback'] ) ) : ?>
						<?php $this->$field(); ?>
						<?php else : ?>
						<?php call_user_func( $parts['callback'] ); ?>
						<?php endif; ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
		<?php
	}

	// Section Functions ---------------------------------------------------------

	/**
	 * Eazyest_Settings_Page::main_settings()
	 *
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	function main_settings() {
		$this->display_section( 'main-settings' );
	}

	/**
	 * Eazyest_Settings_Page::folder_settings()
	 *
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	function folder_settings() {
		$this->display_section( 'folder-settings' );
	}

	/**
	 * Eazyest_Settings_Page::image_settings()
	 *
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	function image_settings() {
		$this->display_section( 'image-settings' );
	}

	/**
	 * Eazyest_Settings_Page::advanced_settings()
	 *
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	function advanced_settings() {
		$this->display_section( 'advanced-settings' );
	}

	/**
	 * Eazyest_Settings_Page::custom_text()
	 *
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	function custom_text() {
		$this->display_section( 'custom-text' );
	}

	// Field Functions -----------------------------------------------------------

	// Main Settings ---------------------
	/**
	 * Eazyest_Settings_Page::gallery_folder()
	 * Output settings field markup
	 *
	 * @since 0.1.0 (r2)
	 * @uses wp_nonce_field()
	 * @uses wp_enqueue_script()
	 * @return void
	 */
	function gallery_folder() {
		$gallery_folder = eazyest_gallery()->gallery_folder;
		$gallery_secure = eazyest_gallery()->gallery_secure;
		$new_install    = eazyest_gallery()->new_install;
		$has_folders    = get_posts( array( 'post_type' => eazyest_gallery()->post_type ) );
		$enabled        = empty( $has_folders ) || ! eazyest_gallery()->right_path();
		$buttonclass    = 'button';
		if ( eazyest_gallery()->right_path() )
			$buttonclass .= ' hidden';
		?><div style="position:relative">
			<?php wp_nonce_field( 'file-tree-nonce',      'file-tree-nonce',       false ) ?>
			<?php wp_nonce_field( 'gallery-folder-nonce', 'gallery-folder-nonce',  false ); ?>
			<input type="hidden" name="eazyest-gallery[gallery_secure]" value="<?php echo $gallery_secure; ?>" />
			<input type="hidden" name="eazyest-gallery[new_install]" value="<?php echo $new_install; ?>" />
			<?php if ( $enabled ) : ?>
				<input type="text" name="eazyest-gallery[gallery_folder]" id="gallery_folder" size="60" class="regular-text code" value="<?php echo $gallery_folder ?>" />
				<a id="folder-select" class="button button-small open" href="#"><strong>&#8744;</strong></a><div id="file-tree"></div>
			<?php else : ?>
				<input type="hidden" name="eazyest-gallery[gallery_folder]" id="gallery_folder" value="<?php echo $gallery_folder ?>" />
				<p class="gallery-folder code"><?php echo $gallery_folder ?></p>
			<?php endif; ?>
			<div id="eazyest-ajax-response" class="hidden"></div>
			<p><a id="create-folder" class="<?php echo $buttonclass ?>" href="#"><?php _e( 'Create folder', 'eazyest-gallery' ); ?></a></p>
			<p class="description">
				<?php _e( 'Relative to your WordPress installation.', 'eazyest-gallery' ); ?><br />
				<?php printf( __( 'Your current setting maps to %s', 'eazyest-gallery' ), '<code>' . eazyest_gallery()->root() . '</code>' ) ?>
			</p>
		</div>
		<?php
		wp_enqueue_script( 'eazyest-gallery-settings' );
	}

	function gallery_title() {
		$gallery_title = eazyest_gallery()->gallery_title();
		?>
		<input type="text" id="gallery_title" name="eazyest-gallery[gallery_title]" size="32" class="regular-text" value="<?php echo $gallery_title ?>" />
		<?php
	}

	/**
	 * Eazyest_Settings_Page::donate()
	 * Output settings field markup
	 *
	 * @since 0.1.0 (r2)
	 * @uses checked()
	 * @return void
	 */
	function donate() {
		$show_credits = eazyest_gallery()->show_credits;
		$vife_stars   = eazyest_gallery()->plugin_url . 'admin/images/5-stars.png';
		?>
		<p>
			<input type="checkbox" id="show_credits" name="eazyest-gallery[show_credits]" <?php checked( $show_credits ) ?> />
			<label for="show_credits"><?php _e( 'Show the "Powered by Eazyest Gallery" banner with your gallery', 'eazyest-gallery' ) ?> </label>
		</p>
		<p>
			<a href="http://wordpress.org/support/view/plugin-reviews/eazyest-gallery" target="_blank"><?php _e( 'Review and rate Eazyest Gallery', 'eazyest-gallery' ) ?><img height="17" width="92" src="<?php echo $vife_stars; ?>" alt="five stars" /></a>
		</p>
		<p>
			<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=22A3Y8ZUGR6PE" title="<?php _e( 'Support the development of Eazyest Galery', 'eazyest-gallery' ); ?>">
				<img src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" alt="PayPal - The safer, easier way to pay online!" />
			</a>
		</p>
		<?php
	}

	// Folder options ------------------------------------------------------------
	/**
	 * Eazyest_Settings_Page::folders_page()
	 * Output settings field markup
	 *
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	function folders_page() {
		$folders_page = eazyest_gallery()->folders_page;
		$step = eazyest_gallery()->folders_columns ? eazyest_gallery()->folders_columns : 1;
		?>
		<input id="folders_page" name="eazyest-gallery[folders_page]" type="number" min="0" step="<?php echo $step; ?>" class="small-text" value="<?php echo $folders_page; ?>" />
		<label for="folders_page"><?php _e( 'per page', 'eazyest-gallery' ) ?></label>
		<?php
	}

	/**
	 * Eazyest_Settings_Page::folders_columns()
	 * Output settings field markup
	 *
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	function folders_columns() {
		$folders_columns = eazyest_gallery()->folders_columns;
		?>
		<input id="folders_columns" name="eazyest-gallery[folders_columns]" type="number" min="0" step="1" class="small-text" value="<?php echo $folders_columns; ?>" />
		<label for="folders_columns"><?php _e( 'columns', 'eazyest-gallery' ) ?></label>
		<?php
	}

	/**
	 * Eazyest_Settings_Page::sort_options()
	 * Return array of options to sort folders and images
	 *
	 * @since 0.1.0 (r2)
	 * @return array
	 */
	private function folder_sort_options() {
		return array(
			'post_name-ASC'   => __( 'Name, ascending (A-Z)',     'eazyest-gallery' ),
			'post_name-DESC'  => __( 'Name, descending (Z-A)',    'eazyest-gallery' ),
			'post_title-ASC'  => __( 'Caption, ascending (A-Z)',  'eazyest-gallery' ),
			'post_title-DESC' => __( 'Caption, descending (Z-A)', 'eazyest-gallery' ),
			'post_date-ASC'   => __( 'Date, oldest first',        'eazyest-gallery' ),
			'post_date-DESC'  => __( 'Date, newest first',        'eazyest-gallery' ),
			'menu_order-ASC'  => __( 'Manually',                  'eazyest-gallery' )
		);
	}


	/**
	 * Eazyest_Settings_Page::thumbnail_sort_options()
	 *
	 * @since 0.1.0 (r298)
	 * @return array
	 */
	private function thumbnail_sort_options() {
		return array(
			'post_name-ASC'   => __( 'Name, ascending (A-Z)',     'eazyest-gallery' ),
			'post_name-DESC'  => __( 'Name, descending (Z-A)',    'eazyest-gallery' ),
			'post_excerpt-ASC'  => __( 'Caption, ascending (A-Z)',  'eazyest-gallery' ),
			'post_excerpt-DESC' => __( 'Caption, descending (Z-A)', 'eazyest-gallery' ),
			'post_date-ASC'   => __( 'Date, oldest first',        'eazyest-gallery' ),
			'post_date-DESC'  => __( 'Date, newest first',        'eazyest-gallery' ),
			'menu_order-ASC'  => __( 'Manually',                  'eazyest-gallery' )
		);
	}

	/**
	 * Eazyest_Settings_Page::sort_folders()
	 * Output settings field markup
	 *
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	function sort_folders() {
		$sort_folders = eazyest_gallery()->sort_folders;
		$options = $this->folder_sort_options();
		?>
		<select id="sort_folders" name="eazyest-gallery[sort_folders]">
			<?php foreach( $options as $value => $option) : ?>
			<option value="<?php echo $value; ?>" <?php selected( $value, $sort_folders ); ?>><?php echo $option; ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Eazyest_Settings_Page::count_subfolders()
	 * Output settings field markup
	 *
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	function count_subfolders() {
		$count_subfolders = eazyest_gallery()->count_subfolders;
		$options = array(
			'none'     => __( 'Show number of images in folder only',                      'eazyest-gallery' ),
			'include'  => __( 'Show number of images in folder including subfolders',      'eazyest-gallery' ),
			'separate' => __( 'Show number of images in folder and subfolders separately', 'eazyest-gallery' ),
			'nothing'  => __( 'Do not show number of images in folder',                    'eazyest-gallery' )
		);
		?>
		<select id="count_subfolders" name="eazyest-gallery[count_subfolders]">
			<?php foreach( $options as $value => $option ) : ?>
			<option value="<?php echo $value; ?>" <?php selected( $value, $count_subfolders ); ?>><?php echo $option; ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Eazyest_Settings_Page::folder_image()
	 * Output settings field markup
	 *
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	function folder_image() {
		$folder_image     = eazyest_gallery()->folder_image;
		$random_subfolder = eazyest_gallery()->random_subfolder;
		$options = array(
			'featured_image' => __( 'Featured image',        'eazyest-gallery' ),
			'first_image'    => __( 'First image in folder', 'eazyest-gallery' ),
			'random_image'   => __( 'Random Image',          'eazyest-gallery' ),
			'icon'           => __( 'Folder Icon',           'eazyest-gallery' ),
			'none'           => __( 'Title only',            'eazyest-gallery' )
		);
		?>
		<select id="folder_image" name="eazyest-gallery[folder_image]">
			<?php foreach( $options as $value => $option ) : ?>
			<option value="<?php echo $value; ?>" <?php selected( $value, $folder_image ); ?>><?php echo $option; ?></option>
			<?php endforeach; ?>
		</select>
		<p id="random-subfolder" style="visibility:hidden">
			<input id="random_subfolder" name="eazyest-gallery[random_subfolder]" type="checkbox" value="1" <?php checked( '1', $random_subfolder ) ?> />
			<label for="random_subfolder"><?php _e( 'Include images from subfolders', 'eazyest-gallery' ); ?></label>
   	</p>
		<?php
	}

	// Image options ---------------------
	/**
	 * Eazyest_Settings_Page::thumbs_page()
	 * Output settings field markup
	 *
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	function thumbs_page() {
		$thumbs_page = eazyest_gallery()->thumbs_page;
		$step = eazyest_gallery()->thumbs_columns ? eazyest_gallery()->thumbs_columns : 1;
		?>
		<input id="thumbs_page" name="eazyest-gallery[thumbs_page]" type="number" min="0" step="<?php echo $step; ?>" class="small-text" value="<?php echo $thumbs_page; ?>" />
		<label for="thumbs_page"><?php _e( 'per page', 'eazyest-gallery' ) ?></label>
		<?php
	}

	/**
	 * Eazyest_Settings_Page::thumbs_columns()
	 * Output settings field markup
	 *
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	function thumbs_columns() {
		$thumbs_columns = eazyest_gallery()->thumbs_columns;
		?>
		<input id="thumbs_columns" name="eazyest-gallery[thumbs_columns]" type="number" min="0" step="1" class="small-text" value="<?php echo $thumbs_columns; ?>" />
		<label for="thumbs_columns"><?php _e( 'columns', 'eazyest-gallery' ) ?></label>
		<?php
	}

	/**
	 * Eazyest_Settings_Page::thumb_caption()
	 * Output settings field markup
	 *
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	function thumb_caption() {
		$thumb_caption = eazyest_gallery()->thumb_caption;
		?>
		<input type="checkbox" id="thumb_caption" name="eazyest-gallery[thumb_caption]" <?php checked( $thumb_caption ) ?> />
		<label for="thumb_caption"><?php _e( 'Show captions in thumbnail view', 'eazyest-gallery' ) ?> </label>
		<?php
	}

	/**
	 * Eazyest_Settings_Page::sort_thumbnails()
	 * Output settings field markup
	 *
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	function sort_thumbnails() {
		$sort_thumbnails = eazyest_gallery()->sort_thumbnails;
		$options = $this->thumbnail_sort_options();
		?>
		<select id="sort_thumbnails" name="eazyest-gallery[sort_thumbnails]">
			<?php foreach( $options as $value => $option) : ?>
			<option value="<?php echo $value; ?>" <?php selected( $value, $sort_thumbnails ); ?>><?php echo $option; ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	private function popup_options() {
		return array(
			'none'      => __( 'Just display the image', 'eazyest-gallery' ),
			'lightbox'  => __( 'Add lightbox markup',    'eazyest-gallery' ),
			'thickbox'  => __( 'Add thickbox markup',    'eazyest-gallery' ),
			'fancybox'  => __( 'Add fancybox markup',    'eazyest-gallery' ),
			'shadowbox' => __( 'Add shadowbox markup',   'eazyest-gallery' ),
		);
	}

	/**
	 * Eazyest_Settings_Page::on_thumb_click()
	 * Output settings field markup
	 * Apply filters to add alternative popup options
	 *
	 * @since 0.1.0 (r2)
	 * @uses apply_filters()
	 * @return void
	 */
	function on_thumb_click() {
		$on_thumb_click = eazyest_gallery()->on_thumb_click;
		$thumb_popup    = eazyest_gallery()->thumb_popup;
		$options = array(
			'nothing'    => __( 'Nothing',                'eazyest-gallery' ),
			'attachment' => __( 'Show attachment page',   'eazyest-gallery' ),
			'medium'     => __( 'Show medium size image', 'eazyest-gallery' ),
			'large'      => __( 'Show large size image',  'eazyest-gallery' ),
			'full'       => __( 'Show full size image',   'eazyest-gallery' ),
		);
		$popups = $this->popup_options();
		$popups = apply_filters( 'eazyest_gallery_thumbnail_popup', $popups );
		?>
		<select id="on_thumb_click" name="eazyest-gallery[on_thumb_click]">
			<?php foreach( $options as $value => $option ) : ?>
			<option value="<?php echo $value; ?>" <?php selected( $value, $on_thumb_click ); ?>><?php echo $option; ?></option>
			<?php endforeach; ?>
		</select>
		<p id="thumb-popup" style="visibility:hidden">
			<select id="thump_popup" name="eazyest-gallery[thumb_popup]">
				<?php foreach( $popups as $value => $option ) : ?>
				<option value="<?php echo $value; ?>" <?php selected( $value, $thumb_popup ); ?>><?php echo $option; ?></option>
				<?php endforeach; ?>
			</select>
   	</p>
		<?php
	}

	/**
	 * Eazyest_Settings_Page::on_slide_click()
	 * Output settings field markup
	 * Apply filters to add alternative popup options
	 *
	 * @since 0.1.0 (r2)
	 * @uses apply_filters()
	 * @return void
	 */
	function on_slide_click() {
		$on_slide_click = eazyest_gallery()->on_slide_click;
		$slide_popup    = eazyest_gallery()->slide_popup;
		$options = array(
			'default' => __( 'Theme default',        'eazyest-gallery' ),
			'next'    => __( 'Go to next image',     'eazyest-gallery' ),
			'full'    => __( 'Show full size image', 'eazyest-gallery' ),
			'nothing' => __( 'Nothing',              'eazyest-gallery' ),
		);
		$popups = $this->popup_options();
		$popups = apply_filters( 'eazyest_gallery_attachment_popup', $popups );
		?>
		<select id="on_slide_click" name="eazyest-gallery[on_slide_click]">
			<?php foreach( $options as $value => $option ) : ?>
			<option value="<?php echo $value; ?>" <?php selected( $value, $on_slide_click ); ?>><?php echo $option; ?></option>
			<?php endforeach; ?>
		</select>
		<p id="slide-popup" style="visibility:hidden">
			<select id="slide_popup" name="eazyest-gallery[slide_popup]">
				<?php foreach( $popups as $value => $option ) : ?>
				<option value="<?php echo $value; ?>" <?php selected( $value, $slide_popup ); ?>><?php echo $option; ?></option>
				<?php endforeach; ?>
			</select>
   	</p>
		<?php
	}

	/**
	 * Eazyest_Settings_Page::listed_as()
	 * Output settings field markup
	 *
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	function listed_as() {
		$listed_as = eazyest_gallery()->listed_as;
		?>
		<input type="text" id="listed_as" name="eazyest-gallery[listed_as]" value="<?php echo $listed_as; ?>" />
		<?php
	}

	// Advanced options ------------------
	/**
	 * Eazyest_Settings_Page::gallery_slug()
	 * Output settings field markup
	 *
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	function gallery_slug() {
		$gallery_slug = eazyest_gallery()->gallery_slug;?>
		<input type="text" id="gallery_slug" class="regular-text code" name="eazyest-gallery[gallery_slug]" value="<?php echo $gallery_slug; ?>" />
		<?php
	}


	// Custom text ------------------
	/**
	 * Eazyest_Settings_Page::more_thumbnail_button_text()
	 * Output settings field markup
	 *
	 * @since 0.1.4
	 * @return void
	 */
	function more_thumbnail_button_text() {
		$more_thumbnail_button_text = eazyest_gallery()->more_thumbnail_button_text;?>
			<input type="text" id="more_thumbnail_button_text" class="regular-text code" name="eazyest-gallery[more_thumbnail_button_text]" value="<?php echo $more_thumbnail_button_text; ?>" />
		<p class="description">
			<?php _e( 'Custom text for the button "more thumbnails".', 'eazyest-gallery' ); ?><br />
			<?php _e( 'Leave blank to use default.', 'eazyest-gallery' ); ?>
		</p>
		<?php
	}


	/**
	 * Eazyest_Settings_Page::slideshow_button_text()
	 * Output settings field markup
	 *
	 * @since 0.1.4
	 * @return void
	 */
	function slideshow_button_text() {
		$slideshow_button_text = eazyest_gallery()->slideshow_button_text;?>
			<input type="text" id="slideshow_button_text" class="regular-text code" name="eazyest-gallery[slideshow_button_text]" value="<?php echo $slideshow_button_text; ?>" />
			<p class="description">
				<?php _e( 'Custom text for the button "slideshow".', 'eazyest-gallery' ); ?><br />
				<?php _e( 'Leave blank to use default.', 'eazyest-gallery' ); ?>
			</p>
			<?php
	}

	// Advanced options ------------------
	/**
	 * Eazyest_Settings_Page::page_slug()
	 * Output settings field markup
	 *
	 * @since 0.1.3
	 * @return void
	 */
	function page_slug() {
		$page_slug = eazyest_gallery()->page_slug;
		$site_url = site_url();
		$page_slug_url = $site_url . '/' . $page_slug;
		?>
		<input type="text" id="page_slug" class="regular-text code" name="eazyest-gallery[page_slug]" value="<?php echo $page_slug; ?>" />
		<p class="description">
				<?php _e( 'Relative to your WordPress installation. This is for the breadcrumbs to work properly', 'eazyest-gallery' ); ?><br />
				<?php printf( __( 'Your current setting links to %s', 'eazyest-gallery' ), '<code>' . $page_slug_url  . '</code>' ) ?>
			</p>
		<?php
	}

	/**
	 * Eazyest_Settings_Page::everyone()
	 * Returns dummy role name 'Everyone'
	 *
	 * @since 0.1.0 (r2)
	 * @return string
	 */
	function everyone() {
		return __( 'Everyone', 'eazyest-gallery' );
	}

	/**
	 * Eazyest_Settings_Page::viewer_level()
	 * Output settings field markup
	 *
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	function viewer_level() {
		$viewer_level = eazyest_gallery()->viewer_level;
		$options = array(
			'everyone' => $this->everyone()
		);
		global $wp_roles;
		foreach( $wp_roles->role_names as $role => $name ) {
			$add = false;
			foreach( $wp_roles->roles[$role]['capabilities'] as $cap ) {
				if ( $cap )
					$add = true;
			}
			if ( $add )
				$options[$role] = $name;
		}
		?>
		<select id="viewer_level" name="eazyest-gallery[viewer_level]">
			<?php foreach( $options as $value => $option ) : ?>
			<option value="<?php echo $value; ?>" <?php selected( $value, $viewer_level ); ?>><?php echo $option; ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	// Help tabs -----------------------------------------------------------------

	/**
	 * Eazyest_Settings_Page::help_sections()
	 * Return array of help tabs for sections
	 *
	 * @since 0.1.0 (r2)
	 * @see http://codex.wordpress.org/Function_Reference/add_help_tab
	 * @uses admin_url()
	 * @return array()
	 */
	function help_sections() {
		$options_media = admin_url( 'options-media.php' );
		return array(
			'overview' => array(
				'id'      => 'overview',
				'title'   => __( 'Overview', 'eazyest-gallery' ),
				'content' => "\n<p>" .          __( 'This screen provides access to all of the Eazyest Gallery settings.',                 'eazyest-gallery' ) . "</p>" .
				             "\n<p>" .          __( 'Please see the additional help tabs for more information on each indiviual section.', 'eazyest-gallery' ) . "</p>" .
										 /* translators %s <a href=> %s </a> */
    		             "\n<p>" . sprintf( __( 'For Image sizes, please refer to %sWordPress Media Settings%s.',                      'eazyest-gallery' ), "<a href='$options_media'>", "</a>" ) . "</p>"
			),
			'main' => array(
				'id'      => 'main-settings',
				'title'   => __( 'Main Settings', 'eazyest-gallery' ),
				'content' => "\n<p>" .         __( 'In the Main Settings you set the server directory for your gallery.', 'eazyest-gallery' ) . "<br />\n" .
				                               __( 'Eazyest Gallery will not work if this directory does not exist.',     'eazyest-gallery' ) . "<br />\n" .
				                               __( 'You cannot change this directory after you add folders to it.',       'eazyest-gallery' ) . "</p>\n" .
				             "\n<p>" .         __( 'Set a title for your gallery archive page',                           'eazyest-gallery' ) . "</p>\n" .
				             "\n<p><strong>" . __( 'Please donate to support the development of Eazyest Gallery',         'eazyest-gallery' ) . "</strong></p>\n"
			),
			'folder' => array(
				'id'      => 'folder-settings',
				'title'   => __( 'Folder Options', 'eazyest-gallery' ),
				'content' => "\n<p>" . __( 'In the Folder Options you have a number of options to control how your folders will be displayed.', 'eazyest-gallery' ) . "</p>" .
				             "\n\t<ul>" .
				             "\n\t\t<li>" .     __( 'Select the number of folder icons to initially display in the &#8220;Icons per page&#8221; option.',                           'eazyest-gallery' ) . "</li>"  .
				             "\n\t\t<li>" .     __( 'The option &#8220;Icon columns&#8221; sets the number of icons to display per row.',                                           'eazyest-gallery' ) . "<br />" .
				                                __( 'Set this option to 0 if you want to display as many icons that fit your page.',                                                'eazyest-gallery' ) . "<br />" .
				                                __( 'If you set any other value, Eazyest Gallery will fit your icons when necessary.',                                              'eazyest-gallery' ) . "</li>"  .
				             "\n\t\t<li>" .     __( 'Select how you want your want to sort your folders in the &#8220;Sort folders by&#8221; option.',                              'eazyest-gallery' ) . "</li>"  .
										 "\n\t\t<li>" .     __( 'If you want to display the number of images in your folders in the icons view, select the &#8220;Count images&#8221; option.', 'eazyest-gallery' ) . "</li>"  .
										 "\n\t\t<li>" .     __( 'In the &#8220;Folder icons&#8221; options, you set what to display in the folder icons view.',                                 'eazyest-gallery' ) .
										 "\n\t\t\t<ul>" .
										 "\n\t\t\t\t<li>" . __( '&#8220;Featured image&#8221; will display the folder featured image.',                                                         'eazyest-gallery' ) . "<br />" .
										                    __( 'If you haven&#8217;t selected a featured image yet, Eazyest Gallery will show the first image in your folder.',                'eazyest-gallery' ) . "</li>"  .
  									 "\n\t\t\t\t<li>" . __( '&#8220;First image in folder&#8221; will display the first image, after sorting the images.',                                  'eazyest-gallery' ) . "</li>"  .
  									 "\n\t\t\t\t<li>" . __( '&#8220;Random image&#8221; will select a random image each time you open the folder icons page.',                              'eazyest-gallery' ) . "<br />" .
  									                    __( 'If you want to include random images from subfolders, please select &#8220;Include images from subfolders&#8221;.',            'eazyest-gallery' ) . "</li>"  .
  									 "\n\t\t\t\t<li>" . __( '&#8220;Folder icon&#8221; will display a standard folder icon.',                                                               'eazyest-gallery' ) . "</li>"  .
										 "\n\t\t\t\t<li>" . __( 'If you don&#8217;t want to display an image, please select &#8220;Title only&#8221;.',                                         'eazyest-gallery' ) . "</li>"  .
  									 "\n\t\t\t</ul>" .
										 "\n\t\t</li>" .
				             "\n\t</ul>" .
										 /* translators %s <a href=> %s </a> */
    		             "\n<p>" . sprintf( __( 'For Image sizes, please refer to %sWordPress Media Settings%s.', 'eazyest-gallery' ),
										 						"<a href='$options_media'>",
										 						"</a>" ) .
										 "</p>"
			),
			'image' => array(
				'id'      => 'image-settings',
				'title'   => __( 'Image Options', 'eazyest-gallery' ),
				'content' => "\n<p>" .          __( 'In the Folder Options you have a number of options to control how your images will be displayed.',             'eazyest-gallery' ) . "</p>" .
				             "\n\t<ul>" .

				             "\n\t\t<li>" .     __( 'Select the number of thumbnails to initially display in the &#8220;Thumbnails per page&#8221; option.',        'eazyest-gallery' ) . "</li>"  .
				             "\n\t\t<li>" .     __( 'The option &#8220;Thumbnail columns&#8221; sets the number of icons to display per row.',                      'eazyest-gallery' ) . "<br />" .
				                                __( 'Set this option to 0 if you want to display as many thumbnails that fit your page.',                           'eazyest-gallery' ) . "<br />" .
				                                __( 'If you set any other value, Eazyest Gallery will fit your thumbnails when necessary.',                         'eazyest-gallery' ) . "</li>"  .
				             "\n\t\t<li>" .     __( 'Keep your descriptions as short as possible when you select the &#8220;Descriptions&#8221; option.',           'eazyest-gallery' ) . "</li>"  .
				             "\n\t\t<li>" .     __( 'Select how you want your want to sort your images in the &#8220;Sort images by&#8221; option.',                'eazyest-gallery' ) . "</li>"  .
                     "\n\t\t<li>" .     __( 'Select what will happen if you click on a thumbnail image in the &#8220;Thumbnail click&#8221; option.',       'eazyest-gallery' ) .
										 "\n\t\t\t<ul>" .
                     "\n\t\t\t\t<li>" . __( 'If you select &#8220;Nothing&#8221;, you will stay on the thumnails page',                                     'eazyest-gallery' ) . "</li>"  .
                     "\n\t\t\t\t<li>" . __( 'If you select &#8220;Show attachment page&#8221;, you will be redirected to the attachment page.',             'eazyest-gallery' ) . "</li>"  .
                     "\n\t\t\t\t<li>" . __( 'If you select &#8220;Show medium size image&#8221;, you will see the medium sized image in your browser.(*)',  'eazyest-gallery' ) . "</li>"  .
                     "\n\t\t\t\t<li>" . __( 'If you select &#8220;Show large size image&#8221;, you will see the large sized image in your browser.(*)',    'eazyest-gallery' ) . "</li>"  .
                     "\n\t\t\t\t<li>" . __( 'If you select &#8220;Show full size image&#8221;, you will see the original image* in your browser.(*)',       'eazyest-gallery' ) . "</li>"  .
										 "\n\t\t\t</ul>" .  __( '(*) You may want to add markup for popular popup plugins like Lightbox or Thickbox.',                          'eazyest-gallery' ) . "</li>"  .
										 "\n\t\t<li>" .     __( '&#8220;List name&#8221; displays what type of images you have in your gallery.',                               'eazyest-gallery' ) . "</li>"  .
										 "\n\t</ul>" .
    		             "\n<p>" . sprintf( __( 'For Image sizes, please refer to %sWordPress Media Settings%s.',                                               'eazyest-gallery' ),
										 						"<a href='$options_media'>",
										 						"</a>" ) .
										 "</p>"
			),
			'advanced' => array(
				'id'      => 'advanced-settings',
				'title'   => __( 'Advanced Options', 'eazyest-gallery' ),
				'content' => "\n<p>" . __( 'In Advaced Options you have a number of options for advanced use of Eazyest Gallery',                              'eazyest-gallery' ) . "</p>"   .
				             "\n\t<ul>" .
				             "\n\t\t<li>" . __('&#8220;Gallery slug&#8221; is a custom root slug to prefix your gallery with.',                                'eazyest-gallery' ) . "</li>"  .
				             "\n\t\t<li>" . __('&#8220;Minimum viewer role&#8221; selects the minimum role/capability a user must have to view your gallery.', 'eazyest-gallery' ) . "<br />" .
						               sprintf( __('If you select &#8220;%s&#8221;, a viewer does not have to be logged in',                                       'eazyest-gallery' ), $this->everyone() ). "</li>"
			)
		);
	}

	/**
	 * Eazyest_Settings_Page::add_help_tabs()
	 * Add all help tabs
	 *
	 * @since 0.1.0 (r2)
	 * @uses get_current_screen()
	 * @uses WP_Screen::add_help_tab()
	 * @return void
	 */
	function add_help_tabs() {
		foreach( $this->help_sections() as $args )
			get_current_screen()->add_help_tab( $args );
	}

	// Display -------------------------------------------------------------------

	/**
	 * Eazyest_Settings_Page::display()
	 * Display the Settings screen
	 *
	 * @since 0.1.0 (r2)
	 * @uses settings_fields()
	 * @uses do_action()
	 * @uses submit_button()
	 * @return void
	 */
	function display() {
		?>

		<div class="wrap">

			<?php screen_icon(); ?>

			<h2><?php _e( 'Eazyest Gallery Settings', 'eazyest-gallery' ) ?></h2>
			<form action="options.php" method="post">

				<?php settings_fields( 'eazyest-gallery' ); ?>

				<?php do_action( 'eazyest_gallery_main_settings' ); ?>

				<?php if ( eazyest_gallery()->right_path() ) : ?>
				<?php do_action( 'eazyest_gallery_settings_section' ); ?>
				<?php endif; ?>

				<?php submit_button(); ?>
			</form>
		</div>

		<?php
	}
} // Eazyest_Settings_Page
