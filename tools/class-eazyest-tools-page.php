<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Eazyest_Tools_Page
 * Tools page in WordPress tools menu
 * Can be expanded by adding tabs
 *
 * @package Eazyest Gallery
 * @subpackage Tools
 * @author Marcel Brinkkemper
 * @copyright 2012 Brimosoft
 * @since 0.1.0 (r2)
 * @version 0.1.0 (r2)
 * @access public
 */
class Eazyest_Tools_Page {
	/**
	 * @var single instance in memory
	 */
	private static $instance;

	/**
	 * @var array of tabs
	 * array( string => array( id => string, url => string ) )
	 */
	private $tabs;

	/**
	 * Eazyest_Tools_Page::__construct()
	 *
	 * @return void
	 */
	function __construct(){}

	/**
	 * Eazyest_Tools_Page::init()
	 *
	 * @return void
	 */
	private function init() {
		$this->tabs = array();
		do_action( 'eazyest_gallery_tools_page_init' );
	}

	/**
	 * Eazyest_Tools_Page::instance()
	 *
	 * @return
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Eazyest_Tools_Page;
			self::$instance->init();
		}
		return self::$instance;
	}

	/**
	 * Eazyest_Tools_Page::add_tab()
	 * Add a tab to the tools page
	 *
	 * @since 0.1.0 (r2)
	 * @uses add_query_arg()
	 * @param mixed $tools_tab
	 * @param bool $overwrite
	 * @return void
	 */
	function add_tab( $tools_tab = array(), $overwrite = true ) {
		if ( ! isset( $this->tabs[$tools_tab['id']] ) || $overwrite ) {
			if ( ! isset( $tools_tab['url'] ) )
				$tools_tab['url'] = add_query_arg( array( 'page' => 'eazyest-gallery', 'tab' => $tools_tab['id'] ), admin_url( 'tools.php' ) );
			$this->tabs[$tools_tab['id']] = $tools_tab;
		}
	}

	/**
	 * Eazyest_Tools_Page::display()
	 * Display the tools page
	 *
	 * @since 0.1.0 (r2)
	 * @uses apply_filters()
	 * @return void
	 */
	function display() {

		// filter tabs for plugins to add tabs
		$this->tabs = apply_filters( 'eazyest_gallery_tools_tabs', $this->tabs );
		$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : '';
		if ( empty( $current_tab ) && ! empty( $this->tabs ) ) {
			reset( $this->tabs );
			$current_tab = key( $this->tabs );
		}
		?>

		<div class="wrap">

			<?php screen_icon(); ?>

			<?php if ( empty( $this->tabs ) ) : ?>
			<h2><?php _e( 'Eazyest Gallery Tools', 'eazyest-gallery' ) ?></h2>
			<?php elseif ( 1 == count( $this->tabs ) ) : ?>
			<h2><?php esc_html_e( $this->tabs[$current_tab]['title'] ); ?></h2>
			<?php else : ?>
			<h2 class="nav-tab-wrapper">
				<?php foreach( $this->tabs as $id => $tab ) : ?>
				<?php $class = $id == $current_tab ? 'nav-tab nav-tab-active' : 'nav-tab'; ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'eazyest-gallery-tools', 'tab' => $id ), admin_url( 'tools.php' ) ) ); ?>" class="<?php echo $class ?>"><?php esc_html( $tab['title'] ); ?></a>
				<?php endforeach; ?>
			</h2>
			<?php endif; ?>

			<?php do_action( 'eazyest_gallery_tools_tab', $current_tab ); ?>
		</div>

		<?php
	}
} // Eazyest_Gallery_Tools_Page
