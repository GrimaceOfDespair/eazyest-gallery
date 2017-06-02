<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Eazyest_Gallery_Upgrader
 * Checks if Eazyest Gallery needs a major update and adds upgrader page to tools menu
 *
 * @package Eazyest Gallery
 * @subpackage Tools/Upgrader
 * @author Marcel Brinkkemper
 * @copyright 2012-2013 Brimosoft
 * @since 0.1.0 (r2)
 * @version 0.1.0 (r286)
 * @access public
 */
class Eazyest_Gallery_Upgrader {
	/**
	 * @var string tab name in tools page
	 */
	private $tab = 'upgrade';

	/**
	 * @var Eazyest_Gallery_Upgrader single object in memory
	 */
	private static $instance;

	/**
	 * @var Eazyest_Upgrade_Engine
	 */
	private $upgrade_engine;

	/**
	 * Eazyest_Gallery_Upgrader::__construct()
	 *
	 * @return void
	 */
	function __construct(){}

	/**
	 * Eazyest_Gallery_Upgrader::init()
	 *
	 * @return void
	 */
	function init() {
		$this->setup_variables();
		$this->actions();
		$this->filters();
	}

	/**
	 * Eazyest_Gallery_Upgrader::instance()
	 *
	 * @return Eazyest_Gallery_Upgrader object
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Eazyest_Gallery_Upgrader;
			self::$instance->init();
		}
		return self::$instance;
	}

	/**
	 * Eazyest_Gallery_Upgrader::should_upgrade()
	 * Check if Eazyest Gallery needs a major upgrade
	 *
	 * @since 0.1.0 (r2)
	 * @return bool
	 */
	function should_upgrade() {
		if ( $option = get_option( 'lazyest-gallery' ) )
			return true;
		else
			return false;
	}

	/**
	 * Eazyest_Gallery_Upgrader::setup_variables()
	 *
	 * @return void
	 */
	function setup_variables() {
		if ( $this->should_upgrade() ) {
			include_once( eazyest_gallery()->plugin_dir . 'tools/class-eazyest-upgrade-engine.php' );
			$this->upgrade_engine = Eazyest_Upgrade_Engine::instance();
		}
	}


	// set actions and filters ---------------------------------------------------
	/**
	 * Eazyest_Gallery_Upgrader::actions()
	 * add WordPress actions
	 *
	 * @since 0.1.0 (r2)
	 * @uses add_action()
	 * @return void
	 */
	function actions() {
		// WordPress admin actions
		add_action( 'admin_init',    array( $this, 'enqueue_scripts' )      );
		add_action( 'admin_head',    array( $this, 'hide_folders'    )      );
		add_action( 'admin_head',    array( $this, 'admin_style'     )      );
		add_action( 'admin_notices', array( $this, 'admin_notices'   )      );
		add_action( 'admin_menu',    array( $this, 'remove_menus'    ), 999 );

		// interact with eazyest-gallery
		add_action( 'eazyest_gallery_tools_tab', array( $this, 'display_tab' ) );
	}

	/**
	 * Eazyest_Gallery_Upgrader::filters()
	 * add WordPress filters
	 *
	 * @since 0.1.0 (r2)
	 * @uses add_filter()
	 * @return void
	 */
	function filters() {
		add_filter( 'eazyest_gallery_tools_tabs', array( $this, 'add_tab' ) );
	}

  // Wordpress household actions and filters functions -------------------------
	/**
	 * Eazyest_Gallery_Upgrader::enqueue_scripts()
	 * Enqueue scripts for this page
	 *
	 * @since 0.1.0 (r2)
	 * @uses wp_enqueue_style()
	 * @uses wp_enqueue_script()
	 * @uses wp_localize_script()
	 * @return void
	 */
	function enqueue_scripts() {
		$j = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? 'js' : 'min.js';
		if ( $this->should_upgrade() ) {
			wp_enqueue_script( 'eazyest-gallery-upgrader',  eazyest_gallery()->plugin_url . "tools/js/eazyest-gallery-upgrader.$j", array( 'jquery' ), '0.1.0-r157', true );
			wp_localize_script( 'eazyest-gallery-upgrader', 'eazyestUpgraderSettings', $this->script_settings() );
		} else {
			$dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
			if ( ! in_array( 'eazyest_gallery_upgrader', $dismissed  ) ) {
				wp_enqueue_style( 'wp-pointer' );
				wp_enqueue_script( 'eazyest-gallery-pointer',  eazyest_gallery()->plugin_url . "tools/js/eazyest-gallery-menu-pointer.$j", array( 'jquery', 'wp-pointer' ), '0.1.0-r2', true );
				wp_localize_script( 'eazyest-gallery-pointer', 'eazyestUpgraderPointer', $this->pointer_settings() );
			}
		}
	}

	/**
	 * Eazyest_Gallery_Upgrader::script_settings()
	 * Javascript translatable strings
	 *
	 * @since 0.1.0 (r2)
	 * @return array
	 */
	function script_settings() {
		return array(
			'stop'             => __( 'Stop',                                       'eazyest-gallery' ),
			'restart'          => __( 'Restart',                                    'eazyest-gallery' ),
			'ready'            => __( 'All folders converted to custom post types', 'eazyest-gallery' ),
			'finished'         => __( 'Upgrade Finished',                           'eazyest-gallery' ),
			'errorMessage'     => __( 'You cannot use %s for a gallery folder.',    'eazyest-gallery' ),
			'notExistsMessage' => __( 'This folder does not exist yet.',            'eazyest-gallery' ),
		);
	}

	/**
	 * Eazyest_Gallery_Upgrader::pointer_settings()
	 * Javascript translatable strings for wp_pointer
	 *
	 * @since 0.1.0 (r2)
	 * @return array
	 */
	function pointer_settings() {
		return array(
			'content'  => sprintf('<h3>%s</h3><p>%s</p>',
											__( 'Eazyest Gallery 0.1',                   'eazyest-gallery' ),
											__( 'Here is your new Eazyest Gallery Menu', 'eazyest-gallery' )
										)
		);
	}

	/**
	 * Eazyest_Gallery_Upgrader::admin_style()
	 * Echo style element for this page
	 *
	 * @since 0.1.0 (r2)
	 * @uses get_current_screen()
	 * @return void
	 */
	function admin_style() {
   if ( get_current_screen()->base != 'tools_page_eazyest-gallery-tools' )
   	return;
		?>
		<style type="text/css" media="screen">
			input[disabled] { color: #7F7F7F }
			.hidden-upgrader { display:none }
		</style>
		<?php
	}

	/**
	 * Eazyest_Gallery_Upgrader::hide_folders()
	 * Hide the custom post_type menu if Eazyest Gallery should be upgraded or gallery folder does not exist
	 *
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	function hide_folders() {
		if ( $this->should_upgrade() || ! eazyest_gallery()->right_path() || eazyest_gallery()->new_install ) {
			$post_type = eazyest_gallery()->post_type;
		echo "
		<style type='text/css' media='screen'>
			#menu-posts-$post_type { display:none; }
		</style>
		";
		}
	}

	/**
	 * Eazyest_Gallery_Upgrader::admin_notices()
	 * Admin notices to
	 * - urge users to upgrade Eazyest Gallery
	 * - Results of upgrade
	 *
	 * @since 0.1.0 (r2)
	 * @uses add_query_arg()
	 * @return void
	 */
	function admin_notices() {
		if ( $this->should_upgrade() ) {
				if ( 'tools_page_eazyest-gallery-tools' != get_current_screen()->id ){
				$message  = __( 'Eazyest Gallery found Lazyest Gallery settings in your database', 'eazyest-gallery'  );
				$linktext = __( 'Please upgrade to Eazyest Gallery', 'eazyest-gallery'  );
				$url      = add_query_arg( array( 'page' => 'eazyest-gallery-tools', 'tab' => 'upgrade' ), admin_url( 'tools.php' ) );
				?>
				<div id="eazyest-gallery-upgrade-notice" class="error"><p><?php echo $message ?> <a href="<?php echo $url; ?>" title="<?php echo esc_attr( $linktext ); ?>"><?php echo $linktext ?></a></p></div>
				<?php
			}
		} else {
			if ( isset( $_GET['gallery-upgraded'] ) ) {
				$check = '';
				if ( $_GET['gallery-upgraded'] == 1 )
					$message = __( 'Succesfully upgraded your gallery', 'eazyest-gallery' );
				else {
					$message = __( 'Upgraded your gallery, but some values remain in the database', 'eazyest-gallery' );
					$check = '<p>' . __( 'Please check your settings', 'eazyest-gallery' ) . '</p>';
				}
				$_SERVER['REQUEST_URI'] = remove_query_arg( 'gallery-upgraded', $_SERVER['REQUEST_URI'] );
				?>
				<div class="updated"><p><strong><?php echo $message; ?></strong></p><?php echo $check; ?></div>
				<?php
			}
		}
	}

	/**
	 * Eazyest_Gallery_Upgrader::remove_menus()
	 * Remove the Settings - Eazyest Gallery menu if not upgraded
	 *
	 * @since 0.1.0 (r2)
	 * @uses 	remove_submenu_page()
	 * @return void
	 */
	function remove_menus() {
		if ( $this->should_upgrade() )
			remove_submenu_page( 'options-general.php', 'eazyest-gallery' );
	}

	/**
	 * Eazyest_Gallery_Upgrader::add_tab()
	 * Add a tab to the Eazyest Gallery tools page
	 *
	 * @since 0.1.0 (r2)
	 * @param array $tabs
	 * @return array
	 */
	function add_tab( $tabs) {
		$tabs[$this->tab] = array(
			'title' => __( 'Upgrade Gallery', 'eazyest-gallery' )
		);
		return $tabs;
	}

	// Tools page display functions ----------------------------------------------
	/**
	 * Eazyest_Gallery_Upgrader::no_upgrade()
	 * Check if upgrade is required and display when no upgrade is required
	 *
	 * @since 0.1.0 (r2)
	 * @return bool
	 */
	function no_upgrade() {
		if ( ! $this->should_upgrade() ) {
			?>
			<h3><?php echo __( 'No upgrade required', 'eazyest-gallery' ) ?></h3>
			<p><?php printf( __( 'You don&#8217;t need to update this version of Eazyest Gallery (%s), good job!', 'eazyest-gallery' ), '<strong>' . eazyest_gallery()->version() . '</strong>' ); ?></p>
			<?php
			return true;
		}
		return false;
	}

	/**
	 * Eazyest_Gallery_Upgrader::sections()
	 * Sections information for this tool page tab
	 *
	 * @since 0.1.0 (r2)
	 * @return array
	 */
	function sections() {
		return array(
			'gallery-settings' => array(
				'title'       => __( 'Your current settings', 'eazyest-gallery' ),
				'description' => __( 'These settings are necessary to convert your content.', 'eazyest-gallery' ),
			),
			'upgrade-options' => array(
				'title'       => __( 'Options',                                                       'eazyest-gallery' ),
				'description' => __( 'Some optional parameters to help tune the conversion process.', 'eazyest-gallery' ),
			)
		);
	}

	/**
	 * Eazyest_Gallery_Upgrader::fields()
	 * Return fields to dispay in section $section
	 *
	 * @since 0.1.0 (r2)
	 * @param string $section
	 * @return array
	 */
	function fields( $section ) {
		$sections = array(
			'gallery-settings' => array(
				'gallery_folder' => array(
					'title' => __( 'Your gallery folder', 'eazyest-gallery' ),
				),
				'gallery_id' => array(
					'title' => __( 'Your gallery page', 'eazyest-gallery' ),
				),
				'convert_page' => array(
					'title' => __( 'Convert page', 'eazyest-gallery' ),
				),
				'allow_comments' => array(
					'title' => __( 'Allow comments', 'eazyest-gallery' ),
				)
			),
			'upgrade-options' => array(
				'import_image_max' => array(
					'title' => __( 'Images limit', 'eazyest-gallery' ),
				),
				'remove_cache' => array(
					'title' => __( 'Slides and Thumbs', 'eazyest-gallery' ),
				),
				'remove_xml' => array(
					'title' => __( 'Caption files', 'eazyest-gallery' ),
				),
			)
		);
		return isset( $sections[$section] ) ? $sections[$section] : array();
	}

	// all fields to display
	/**
	 * Eazyest_Gallery_Upgrader::gallery_folder()
	 * Field to display
	 *
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	function gallery_folder() {
		$options = get_option( 'lazyest-gallery' );
		eazyest_gallery()->gallery_folder = str_replace( '\\', '/', $options['gallery_folder'] );
		$exist_class     = eazyest_gallery()->right_path() ? ' hide-if-js' : '' ;
		$dangerous_class = eazyest_folderbase()->is_dangerous( eazyest_gallery()->root() ) ? '' : ' hide-if-js';
		?>
		<?php wp_nonce_field( 'gallery-folder-nonce', 'gallery-folder-nonce',  false ); ?>
		<input type="text" id="gallery_folder" name="gallery_folder" size="60" class="regular-text code" value="<?php echo eazyest_gallery()->gallery_folder ?>" />
		<div id="eazyest-ajax-response" class="hidden"></div>
		<?php
	}

	/**
	 * Eazyest_Gallery_Upgrader::gallery_id()
	 * Field to display
	 *
	 * @since 0.1.0 (r2)
	 * @uses get_pages()
	 * @uses selected(
	 * @uses esc_html()
	 * @return void
	 */
	function gallery_id() {
		$options = get_option( 'lazyest-gallery' );
		$gallery_id = $options['gallery_id'];
		$pages = get_pages( array( 'sort_column' => 'post_date', 'sort_order' => 'DESC', 'post_status' => 'publish,private' ) );
		?>
		<select id="gallery_id">
		<?php foreach( $pages as $page ) : ?>
			<option value="<?php echo $page->ID ?>" <?php selected( $page->ID == $gallery_id ) ?>><?php echo esc_html( $page->post_title ); ?></option>
		<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Eazyest_Gallery_Upgrader::convert_page()
	 * Field to diplay
	 *
	 * @since 0.1.0 (r2)
	 * @uses _e()
	 * @return void
	 */
	function convert_page() {
		$options = get_option( 'lazyest-gallery' );
		?>
		<p>
			<input type="radio" name="convert_page" id="convert_page-true" value="1" />
			<label for="convert_page-true"><?php _e( 'Delete my gallery page and use the slug for my new gallery', 'eazyest-gallery' ) ?></label>
			<?php if ( isset($options['allow_comments']) && 'TRUE' == $options['allow_comments'] ) : ?>
			<p class="description"><?php _e( 'If you select to delete your page, comments on the gallery root cannot be re-linked and will be discarded', 'eazyest-gallery' ); ?></p>
			<?php endif; ?>
		</p>
		<p>
			<input type="radio" name="convert_page" id="convert_page-false" value="0" checked="checked" />
			<label for="convert_page-true"><?php _e( 'Keep my gallery page as an extra gallery', 'eazyest-gallery' ) ?></label>
		</p>
		<?php
	}

	/**
	 * Eazyest_Gallery_Upgrader::allow_comments()
	 * Field to display
	 *
	 * @since 0.1.0 (r2)
	 * @uses _e()
	 * @return void
	 */
	function allow_comments() {
		$options = get_option( 'lazyest-gallery' );
		$allow_comments =   isset($options['allow_comments']) && 'TRUE' == $options['allow_comments'];
		?>
		<input type="checkbox" name="allow_comments" id="allow_comments" value="1" <?php checked( 'TRUE' == $allow_comments ) ?> />
		<label><?php _e( 'Comments will be re-linked to custom post types', 'eazyest-gallery' ); ?></label>
		<?php
	}

	/**
	 * Eazyest_Gallery_Upgrader::import_image_max()
	 * Field to display
	 *
	 * @since 0.1.0 (r2)
	 * @uses _e()
	 * @return void
	 */
	function import_image_max() {
		?>
		<input type="text" name="import_image_max" id="import_image_max" value="<?php echo eazyest_folderbase()->max_process_items ?>" class="small-text" />
		<label for="import_image_max"><?php _e( 'images to process at a time', 'eazyest-gallery') ?></label>
		<p class="description"><?php _e( 'Keep this low if you experience out-of-memory issues.', 'eazyest-gallery' ) ?></p>
		<?php
	}

	/**
	 * Eazyest_Gallery_Upgrader::remove_cache()
	 *
	 * @since 0.1.0 (r159)
	 * @uses _e()
	 * @return void
	 */
	function remove_cache() {
		?>
		<input type="checkbox" id="remove_cache" name="remove_cache" value="1" checked="checked"  />
		<label><?php _e( 'Remove slides and thumbs after upgrade', 'eazyest-gallery' ); ?></label>
		<?php
	}

	/**
	 * Eazyest_Gallery_Upgrader::remove_xml()
	 * Field to display
	 *
	 * @since 0.1.0 (r2)
	 * @uses _e()
	 * @return void
	 */
	function remove_xml() {
		?>
		<input type="checkbox" id="remove_xml" name="remove_xml" value="1" checked="checked"  />
		<label><?php _e( 'Remove captions.xml files after upgrade', 'eazyest-gallery' ); ?></label>
		<?php
	}

	/**
	 * Eazyest_Gallery_Upgrader::display_section()
	 * Display a tools page section like a settings page section
	 *
	 * @since 0.1.0 (r2)
	 * @param string $section
	 * @return void
	 */
	function display_section( $section ) {
		if ( empty( $section ) )
			return;
		$sections = $this->sections();
		$section_parts = $sections[$section];
		$fields = $this->fields( $section );
		?>
		<h3><?php echo $section_parts['title']  ?></h3>
		<p><?php  echo $section_parts['description'] ?></p>
		<?php if ( ! empty( $fields ) ) : ?>
		<table class="form-table">
			<tbody>
				<?php foreach( $fields as $field => $parts ) : ?>
				<tr>
					<th scope="row"><?php echo $parts['title']; ?></th>
					<td>
						<?php $this->$field(); ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
		<?php
	}

	/**
	 * Eazyest_Gallery_Upgrader::display_tab()
	 * Display this Eazyest Gallery tools page tab
	 *
	 * @since 0.1.0 (r2)
	 * @uses  wp_nonce_field()
	 * @uses _e()
	 * @uses esc_attr_e()
	 * @param string $tab
	 * @return void
	 */
	function display_tab( $tab ) {
		if ( $tab != $this->tab )
			return;
		if ( $this->no_upgrade() )
			return;
		$about_page = admin_url( add_query_arg( array( 'page' => 'eazyest-gallery-about', 'lazyest' => 'true' ), 'index.php' ) );
		?>
			<form id="upgrade-form" action="admin.php" method="post">
				<input type="hidden" name="action" id="" value="skip_gallery_update"  />
				<?php wp_nonce_field( 'eazyest-gallery-update' ); ?>
				<h3><?php         _e( 'Upgrade from Lazyest Gallery',                                                                                         'eazyest-gallery' ); ?></h3>
				<p><?php          _e( 'Eazyest Gallery uses custom post types and stores all information in the WordPress database.',                         'eazyest-gallery' ); ?></p>
				<p><?php          _e( 'You have to convert your gallery to custom post types.',                                                               'eazyest-gallery' ); ?></p>
				<p><strong><?php  _e( 'Please backup your database and your gallery before you start upgrading.',                                             'eazyest-gallery' ); ?></strong></p>
				<p><strong><?php  _e( 'Eazyest Gallery will remove the Lazyest Gallery plugin and its data.',                                                 'eazyest-gallery' ); ?></strong></p>
				<p><em><?php      _e( 'Be aware that importing and renaming folders and images and importing and linking comments is a time consuming task.', 'eazyest-gallery' ); ?></em></p>
				<p><em><?php      _e( 'Importing large galleries containing hundreds of folders could well take hours to complete.',                          'eazyest-gallery' ); ?></em></p>
				<?php foreach( $this->sections() as $section => $parts ) : ?>
				<?php $this->display_section( $section ); ?>
				<?php endforeach; ?>
				<p class="submit">
					<a id="start-upgrade" href="#start-upgrade" class="button button-primary"><?php echo __( 'Start', 'eazyest-gallery' ); ?></a>
				</p>
				<div id="upgrade-process" class="hidden-upgrader">
					<h3 id="upgrade-process-title"><?php _e( 'Upgrade progress', 'eazyest-gallery' ); ?></h3>
					<p id="upgrade-error" class="hidden-upgrader"><?php _e( 'Something went terribly wrong in the upgrade process. Please check your settings above.', 'eazyest-gallery' ); ?></p>
					<p id="folder-counter" class="hidden-upgrader"><span class="spinner" style="float:left;"></span><?php printf( __( 'Converting folder %s of %s', 'eazyest-gallery' ), '<span id="current-folder"></span>', '<span id="all-folders"></span>' ); ?></p>
					<p id="image-counter" class="hidden-upgrader"><?php printf( __( 'Busy converting a large folder. Imported more than %s images in this folder.', 'eazyest-gallery' ), '<span id="image-batch"></span>'  ) ?></p>
					<p id="upgrade_page" class="hidden-upgrader"><?php _e( 'Converting your Gallery Page', 'eazyest-gallery' ); ?></p>
					<p id="upgrade-settings" class="hidden-upgrader"><?php _e( 'Updating your settings', 'eazyest-gallery' ); ?></p>
					<p id="upgrade-cleanup" class="hidden-upgrader"><?php _e( 'Cleanup and Remove Lazyest Gallery plugin', 'eazyest-gallery' ); ?></p>
					<p id="upgrade-success"  class="hidden-upgrader"><?php _e( 'Successfully upgraded Eazyest Gallery', 'eazyest-gallery' ); ?> <a href="<?php echo $about_page ?>"><?php _e( 'Check out what&#8217;s new', 'eazyest-gallery' ) ?></a></p>
				</div>
				<div class="submit" id="skip">
					<a href="#skip_upgrade" class="button button-secondary" id="skip_upgrade"><?php echo esc_attr_e( 'Skip', 'eazyest-gallery'); ?></a>
					<label for="skip_upgrade"><?php _e( 'Don&#8217;t import', 'eazyest-gallery' ); ?></label>
					<p class="description"><?php _e( 'Don&#8217;t save any content, just let Eazyest Gallery automatically import my folders and images.', 'eazyest-gallery' ) ?></p>
				</div>
				<div class="submit" id="abort">
					<a href="<?php echo admin_url( 'plugins.php' ) ?>" class="button button-primary" id="abort_upgrade"><?php echo esc_attr_e( 'Abort', 'eazyest-gallery'); ?></a>
					<label for="skip_upgrade"><?php _e( 'Don&#8217;t use Eazyest Gallery', 'eazyest-gallery' ); ?></label>
					<p class="description"><?php _e( 'Bring me back to the plugin page, I want to keep Lazyest Gallery.', 'eazyest-gallery' ) ?></p>
				</div>
			</form>
		<?php
	}
} // Eazyest_Gallery_Upgrader

/**
 * eazyest_gallery_upgrader()
 * Function to get the object of class Eazyest_Gallery_Upgrader
 *
 * @since 0.1.0 (r2)
 * @return Eazyest_Gallery_Upgrader object
 */
function eazyest_gallery_upgrader() {
	return Eazyest_Gallery_Upgrader::instance();
}

// autostart this tool page tab
eazyest_gallery_upgrader();
