<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Eazyest_About_Page
 * Shows the About Eazyest Gallery page
 * 
 * @package Eazyest Gallery
 * @subpackage Admin/About
 * @author Marcel Brinkkemper
 * @copyright 2013 Brimosoft
 * @since 0.1.0 (r103)
 * @version 0.1.0 (r309)
 * @access public
 */
class Eazyest_About_Page {
	/**
	 * @staticvar $instance
	 * @access private
	 */
	private static $instance;

	/**
	 * Eazyest_About_Page::__construct()
	 *
	 * @return void
	 */
	function __construct() {}

	/**
	 * Eazyest_About_Page::instance()
	 *
	 * @return Eazyest_About_Page object
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Eazyest_About_Page;
			self::$instance->init();
		}
		return self::$instance;
	}

	/**
	 * Eazyest_About_Page::init()
	 *
	 * @return void
	 */
	private function init() {}

	/**
	 * Eazyest_About_Page::about_header()
	 * Header for About page.
	 *
	 * @since 0.1.0 (r103)
	 * @return void
	 */
	private function about_header() {
		list( $display_version ) = explode( '-', eazyest_gallery()->version() );
		?>
		<style>
		.eazyest-badge {
			padding-top: 162px;
			height: 50px;
			width: 173px;
			color: #fff;
			font-weight: bold;
			font-size: 14px;
			text-align: center;
			margin: 0 -5px;
			background: url('<?php echo eazyest_gallery()->plugin_url ?>admin/images/badge.png?ver=0.1.0') no-repeat;
		}
		.about-wrap .eazyest-badge {
			position: absolute;
			top: 0;
			right: 0;
		}
		body.rtl .about-wrap .eazyest-badge {
			right: auto;
			left: 0;
		}
		</style>
		<div class="wrap about-wrap">
			<h1><?php printf( __( 'Welcome to Eazyest Gallery %s', 'eazyest-gallery' ), $display_version ); ?></h1>
			<div class="about-text"><?php printf( __( 'Thank you for installing Eazyest Galery %s! This plugin offers you the easiest way to manage your galleries in WordPress.', 'eazyest-gallery'  ), $display_version ); ?></div>
			<div class="eazyest-badge" title="<?php echo esc_attr( eazyest_gallery()->version() ) ?>"><?php printf( __( 'Version %s', 'eazyest-gallery' ), $display_version ); ?></div>
		<?php
	}

	/**
	 * Eazyest_About_Page::about_footer()
	 * Footer for the About page
	 *
	 * @since 0.1.0 (r103)
	 * @uses esc_url()
	 * @uses admin_url()
	 * @uses add_query_arg()
	 * @uses update_option()
	 * @return void
	 */
	function about_footer() {
		?>
			<div class="return-to-dashboard">
				<a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'eazyest-gallery' ), 'options-general.php' ) ) ); ?>"><?php _e( 'Go to Eazyest Gallery Settings', 'eazyest-gallery' ); ?></a>
			</div>

		</div>
		<?php
		// set option to not show about page anymore
		update_option( 'eazyest-gallery-about', true );
	}

	/**
	 * Eazyest_About_Page::about()
	 * The About Eazyest Gallery text.
	 *
	 * @since 0.1.0 (r103)
	 * @uses esc_url()
	 * @uses admin_url()
	 * @uses add_query_arg()
	 * @uses _e()
	 * @return void
	 */
	public function about() {
		$should_upgrade = eazyest_gallery_upgrader()->should_upgrade();
		$lazyest = isset( $_GET['lazyest'] ) || $should_upgrade;
		$h2 = $lazyest ? __(  'What&#8217;s New', 'eazyest-gallery' ) : __( 'What&#8217;s Eazyest', 'eazyest-gallery' );
		$this->about_header();
		?>
			<h2 class="nav-tab-wrapper">
				<a class="nav-tab nav-tab-active" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'eazyest-gallery-about' ), 'index.php' ) ) ); ?>">
					<?php echo $h2; ?>
				</a><a class="nav-tab" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'eazyest-gallery-credits' ), 'index.php' ) ) ); ?>">
					<?php _e( 'Credits', 'eazyest-gallery' ); ?>
				</a>
			</h2>

			<div class="changelog">
				<?php if ( $lazyest ) : ?>
				<h3><?php _e( 'This is what Lazyest Gallery was supposed to be', 'eazyest-gallery' ); ?></h3>
				<div class="feature-section images-stagger-right">
					<img alt="<?php _e( 'Integrated Uploader', 'eazyest-gallery' ) ?>" src="<?php echo eazyest_gallery()->plugin_url ?>admin/images/uploader.jpg" class="image-50" />
					<h4><?php _e( 'Fully Integrated in WordPress', 'eazyest-gallery' ); ?></h4>
					<p><?php _e( 'Folders are implemented as custom post types.', 'eazyest-gallery' ); ?><br />
					   <?php _e( 'Store your images as attachments, and access them in the Media Library.', 'eazyest-gallery' ); ?></p>
		   		<p><?php _e( 'Use the WordPress Media Manager to upload images.', 'eazyest-gallery' ); ?></p>
				 	<p><?php _e( 'Eazyest Gallery is fully compatible with WP Super Cache, Jetpack, WordPress-SEO, Comments-Reloaded and other popular plugins','eazyest-gallery' ); ?></p>
				 	<?php if( $should_upgrade ) : ?>
				 	<p class="attention"><strong><?php // translators: %s are placeholders for link anchors
					          printf( __( 'Please convert your Lazyest Gallery Folders, Images, Captions, Descriptions and Comments with the %sUpgrade Tool%s.', 'eazyest-gallery' ),
					            '<a href="' . admin_url( 'tools.php?page=eazyest-gallery-tools' ) . '">', '</a>'
					          ); ?></strong></p>
				 	<?php endif; ?>
				</div>
			</div>

			<div class="changelog">
				<?php endif; ?>
				<h3><?php _e( 'Manage Folders and Subfolders', 'eazyest-gallery' ); ?></h3>
				<div class="feature-section images-stagger-right">
					<img alt="<?php _e( 'Drag &amp; Drop sorting', 'eazyest-gallery' ) ?>" src="<?php echo eazyest_gallery()->plugin_url ?>admin/images/drag-drop.jpg" class="image-50" />
					<h4><?php _e( 'Easily Arrange your Folders', 'eazyest-gallery' ); ?></h4>
					<p><?php _e( 'Easily add folders just like pages.', 'eazyest-gallery' ); ?><br />
					   <?php _e( 'You can have as many folders you want.', 'eazyest-gallery' ); ?><br />
					   <?php _e( 'Add titles, content, images, tags, and subfolders in the Edit Folder screen.', 'eazyest-gallery' ); ?><br />
					   <?php _e( 'Sort your folders and images by date, by name, by title, or enable drag-and-drop manually sorting.', 'eazyest-gallery' )?></p>
		   		<?php if ( ! eazyest_gallery()->right_path() || eazyest_gallery()->new_install ) : ?>
		   		<p class="attention"><strong><?php
					 					// translators: %s are placeholders for link anchors
		   		          printf( __( 'Please confirm your server gallery folder in %sEazyest Gallery Settings%s.', 'eazyest-gallery' ),
		   		          	'<a href="' . admin_url( 'options-general.php?page=eazyest-gallery' ) . '">', '</a>'
										); ?></strong></p>
		   		<?php endif; ?>
				</div>
			</div>

			<div class="changelog">
				<h3><?php _e( 'Manage your Images', 'eazyest-gallery' ) ?></h3>
				<div class="feature-section images-stagger-right">
					<?php if ( ! $lazyest ) : ?>
					<img alt="<?php _e( 'Integrated Uploader', 'eazyest-gallery' ) ?>" src="<?php echo eazyest_gallery()->plugin_url ?>admin/images/uploader.jpg" class="image-50" />
					<?php endif; ?>
					<h4><?php _e( 'Easily Upload and Manage your Images', 'eazyest-gallery' ) ?></h4>
					<p><?php _e( 'You can easily upload your images in the new Media Manager.', 'eazyest-gallery' ); ?></p>
					<p><?php _e( 'If you are tired of uploading photos through the Media Manager, this plugin will make it a breeze with auto-indexing integration. Just add your images on your server and Eazyest Gallery will find and use them.', 'eazyest-gallery' ); ?></p>
					<p><?php _e( 'Add captions and descriptions to all your images in one screen', 'eazyest-gallery' ); ?></p>
				</div>
			</div>

			<div class="changelog">
				<h3><?php _e( 'Display your Gallery', 'eazyest-gallery' ) ?></h3>
				<div class="feature-section images-stagger-right">
					<h4><?php _e( 'Display WordPress Compatible Galleries', 'eazyest-gallery' ) ?></h4>
					<img alt="<?php _e( 'Camera slideshow', 'eazyest-gallery' ) ?>" src="<?php echo eazyest_gallery()->plugin_url ?>admin/images/slideshow.jpg" class="image-50" />
					<p><?php _e( 'Eazyest Gallery produces WordPress gallery markup. Your gallery is ready to go without changes to your theme. If you have plugins or themes to display galleries differently, they will work for Eazyest Gallery too.', 'eazyest-gallery' )?></p>
					<h4><?php _e( 'Show Beautiful Slideshows', 'eazyest-gallery' ) ?></h4>
					<p><?php _e( 'Eazyest Gallery includes the Camera Slideshow by Manuel Masia. You can include slideshow widgets in your sidebar', 'eazyest-gallery' ) ?></p>
					<h4><?php _e( 'Theme compatibility', 'eazyest-gallery' ) ?></h4>
					<p><?php _e( 'Eazyest Gallery includes templates for <strong>Twenty Ten</strong>, for <strong>Twenty Eleven</strong>, and for <strong>Twenty Twelve</strong>. Most features will work in other themes. You can find example templates for Gallery, Folder and Attachment pages in the plugin package.', 'eazyest-gallery' ) ?></p>
					<p><?php _e( 'Eazyest Gallery comes with a variety of widgets, shortcodes, and template tags to let you create the gallery you want.', 'eazyest-gallery' ) ?></p>
				</div>
			</div>

			<div class="changelog">
				<h3><?php _e( 'Under the Hood', 'eazyest-gallery' ); ?></h3>
				<div class="feature-section col three-col">

					<div>
						<h4><?php _e( 'Template Tags', 'eazyest-gallery' ); ?></h4>
						<p><?php _e(  'Use Eazyest Gallery tags to build your theme template. Show a list of all your folders with the <code>ezg_list_folders()</code> tag. Display random images with the <code>ezg_random_images()</code> tag', 'eazyest-gallery' ); ?></p>
						<?php if ( $lazyest ) : ?>
						<p><?php _e( 'All your Lazyest Gallery tags will work in Eazyest Gallery', 'eazyest-gallery'); ?></p>
						<?php endif; ?>
					</div>

					<div>
						<h4><?php _e( 'Integrate and Expand', 'eazyest-gallery' ) ?></h4>
						<p><?php _e( 'Eazyest Gallery offers a myriad of action hooks and filters to change the behavior and html output.', 'eazyest-gallery' ) ?></p>
					</div>

					<div class="last-feature">
						<h4><?php _e( 'External Libraries', 'eazyest-gallery' ) ?></h4>
						<p><?php             // translators %s are placeholders for link anchors
						         printf( __( 'Eazyest Gallery includes %sTableDnD plug-in%s for JQuery by Denis Howlett to enable manually sorting, %sJQuery File Tree%s by A Beautiful Site in the Settings screen, and the %sCamera Slideshow%s by Manuel Masia.', 'eazyest-gallery' ),
										 	 '<a href="http://isocra.com/2008/02/table-drag-and-drop-jquery-plugin/">', '</a>',
										 	 '<a href="http://www.abeautifulsite.net/blog/2008/03/jquery-file-tree/">', '</a>',
										 	 '<a href="http://www.pixedelic.com/plugins/camera/">', '</a>'
						         ); ?></p>
					</div>

				</div>
			</div>
		<?php
		$this->about_footer();
	}

	/**
	 * Eazyest_About_Page::credits()
	 * Eazyest Gallery Credits.
	 *
	 * @since 0.1.0 (r103)
	 * @uses esc_url()
	 * @uses admin_url()
	 * @uses add_query_arg()
	 * @return void
	 */
	function credits() {
		$should_upgrade = eazyest_gallery_upgrader()->should_upgrade();
		$lazyest = isset( $_GET['lazyest'] ) || $should_upgrade;
		$h2 = $lazyest ? __(  'What&#8217;s New', 'eazyest-gallery' ) : __( 'What&#8217;s Eazyest', 'eazyest-gallery' );
		$this->about_header();
		?>
			<h2 class="nav-tab-wrapper">
				<a class="nav-tab" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'eazyest-gallery-about' ), 'index.php' ) ) ); ?>">
					<?php echo $h2; ?>
				</a><a class="nav-tab nav-tab-active" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'eazyest-gallery-credits' ), 'index.php' ) ) ); ?>">
					<?php _e( 'Credits', 'eazyest-gallery' ); ?>
				</a>
			</h2>

			<p class="about-description"><?php _e( 'Eazyest Gallery is created during evening hours by a lone developer supported by his family.', 'eazyest-gallery' ); ?></p>

			<h4 class="wp-people-group"><?php _e( 'Project Leader', 'eazyest-gallery' ); ?></h4>
			<ul class="wp-people-group " id="wp-people-group-project-leaders">
				<li class="wp-person" id="wp-person-macbrink">
					<a href="http://profiles.wordpress.org/macbrink"><img src="http://gravatar.com/avatar/07be247e700a7c7e5630214c40ae4098?s=60" class="gravatar" alt="Marcel Brinkkemper" /></a>
					<a class="web" href="http://profiles.wordpress.org/macbrink">Marcel Brinkkemper</a>
					<span class="title"><?php _e( 'Lone Developer', 'eazyest-gallery' ); ?></span>
				</li>
			</ul>

			<h4 class="wp-people-group"><?php _e( 'Contributors, Testers and Bug reporters', 'eazyest-gallery' ); ?></h4>
			<ul class="wp-people-group " id="wp-people-group-project-testers">
				<li class="wp-person" id="wp-person-audrey">
					<img src="http://2.gravatar.com/avatar/0bb825e2d9dc951bca98b3afe7ff2b79?s=60" class="gravatar" alt="Audrey" />
					<a class="web" href="http://wordpress.org/support/profile/crackedjar">Audrey</a>
					<span class="title"><?php _e( 'Advanced user and Tester', 'eazyest-gallery' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-barfux">
					<img src="http://www.gravatar.com/avatar/a7aee800031b1cdc25ef3eb5e1d04ade?s=60&d=monsterid&r=g" class="gravatar" alt="barfux" />
					<a href="http://wordpress.org/support/profile/barfux" class="web">barfux</a>
					<span class="title"><?php _e( 'Tester and Bug reporter', 'eazyest-gallery' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-joyously">
					<img src="http://www.gravatar.com/avatar/aa8e5a3c12d13b8a152e6fb1862cb0b4?s=60&d=monsterid&r=g" class="gravatar" alt="joyously" />
					<a href="http://wordpress.org/support/profile/joyously" class="web">joy</a>
					<span class="title"><?php _e( 'Thought support', 'eazyest-gallery' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-blogbata">
					<img src="http://2.gravatar.com/avatar/f504f7cecb236d61336f643c2bd3e2da?s=60" class="gravatar" alt="Blogbata" />
					<a class="web">Blogbata</a>
					<span class="title"><?php _e( 'Bug reporter', 'eazyest-gallery' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-santiagodemierr">
					<img src="http://www.gravatar.com/avatar/9457df3ffa8c8c08777b658a0671f35f?s=60&d=monsterid&r=g" class="gravatar" alt="santiago.demierre" />
					<a href="http://wordpress.org/support/profile/santiagodemierre" class="web">santiago.demierre</a>
					<span class="title"><?php _e( 'Bug reporter', 'eazyest-gallery' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-tgv604">
					<img src="http://www.gravatar.com/avatar/d583b42b4707813c345d63c376367a88?s=60&d=monsterid&r=g" class="gravatar" alt="tgv604" />
					<a href="http://wordpress.org/support/profile/tgv604" class="web">tgv604</a>
					<span class="title"><?php _e( 'Bug reporter', 'eazyest-gallery' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-nino-b">
					<img src="http://www.gravatar.com/avatar/4a7d696eb3c30f87d83e1dc63ac04048?s=60&d=monsterid&r=g" class="gravatar" alt="nino-b" />
					<a href="http://wordpress.org/support/profile/nino-b" class="web">nino-b</a>
					<span class="title"><?php _e( 'Bug reporter', 'eazyest-gallery' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-patdtn">
					<img src="http://www.gravatar.com/avatar/feec71ac24545388823abae5ae4dce53?s=60&d=monsterid&r=g" class="gravatar" alt="patdtn" />
					<a href="http://wordpress.org/support/profile/patdtn" class="web">patdtn</a>
					<span class="title"><?php _e( 'Reviewer and Bug reporter', 'eazyest-gallery' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-emburr">
					<img src="http://www.gravatar.com/avatar/b9a7a79bb10c44ab01260ad8ec51a33e?s=60&d=monsterid&r=g" class="gravatar" alt="emburr" />
					<a href="http://wordpress.org/support/profile/emburr" class="web">eMBurr</a>
					<span class="title"><?php _e( 'Bug reporter and patch supplier', 'eazyest-gallery' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-klihelp">
					<img src="http://www.gravatar.com/avatar/ec2466269881b11803d5c7e847e8ecee?s=60&d=monsterid&r=g" class="gravatar" alt="klihelp" />
					<a href="http://wordpress.org/support/profile/klihelp" class="web">klihelp</a>
					<span class="title"><?php _e( 'Champion Tester, Bug reporter', 'eazyest-gallery' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-rs58">
					<img src="http://www.gravatar.com/avatar/5f62d7fb65289ae46d84cb6564914054?s=60&d=monsterid&r=g" class="gravatar" alt="rs58" />
					<a href="http://wordpress.org/support/profile/rs58" class="web">RS58</a>
					<span class="title"><?php _e( 'Bug reporter', 'eazyest-gallery' ); ?></span>
				</li>
			</ul>
			<h4 class="wp-people-group"><?php _e( 'External Libraries', 'eazyest-gallery' ); ?></h4>
			<p class="wp-credits-list">
				<a href="http://isocra.com/2008/02/table-drag-and-drop-jquery-plugin/">TableDnD plug-in for JQuery</a>,
				<a href="http://www.abeautifulsite.net/blog/2008/03/jquery-file-tree/">JQuery File Tree</a>,
				<a href="http://www.pixedelic.com/plugins/camera/">Camera Slideshow</a>
			</p>

			<p class="clear"><?php _e( 'Hi, You&#8217;ve read all to this spot. Don&#8217;t you think the list of contributors, testers and bug reporters is way too short? Please join in and drop a note in the support forums.', 'eazyest-gallery' ); ?></p>
		</div>
		<?php
	}

} // Eazyest_About_Page
