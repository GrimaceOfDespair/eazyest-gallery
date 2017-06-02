<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( extension_loaded( 'imagick' ) && is_callable( 'Imagick', 'queryFormats' ) )
	include_once( eazyest_gallery()->plugin_dir  . 'includes/class-_eazyest-image-editor-imagick.php' );
else
	if ( extension_loaded('gd') && function_exists('gd_info') )
		include_once( eazyest_gallery()->plugin_dir  . 'includes/class-_eazyest-image-editor-gd.php' );

/**
 * Eazyest_Image_Editor
 *
 * @since 0.1.0 (r36)
 * @version 0.2.0 (r322)
 * @package Eazyest Gallery
 * @subpackage Image Editor
 * @see WP_Image_Editor
 */
class Eazyest_Image_Editor extends _Eazyest_Image_Editor {


	/**
	 * Eazyest_Image_Editor::load()
	 * Apply exif orientation after image loaded in memory.
	 *
	 * @since 0.2.0 (r322)
	 * @access public
	 * @return bool
	 */
	public function load() {
		if ( $this->image )
		 return true;
		if( $load = parent::load() ) {
			if ( ( false === strpos( $this->file, eazyest_gallery()->address() ) ) && ( false === strpos( $this->file, eazyest_gallery()->root() ) ) )
				return true;
			$this->exif_orientation();
			return true;
		}
		return $load;
	}

	/**
	 * Builds an output filename based on current file
	 * If file is in eazyest gallery, resized files will be stored in subdirectories.
	 *
	 * @since 0.1.0 (r36)
	 * @version 0.1.0 (r36)
	 * @uses trailingslashit to build save path
	 * @uses wp_mkdir_p to create output directory
	 * @param string $suffix not used in Eazyest Gallery
	 * @param string $dest_path
	 * @param string $extension
	 * @return string filename
	 */
	public function generate_filename( $suffix = null, $dest_path = null, $extension = null )  {
		$filename = parent::generate_filename( $suffix, $dest_path, $extension );

		if ( ( false === strpos( $this->file, eazyest_gallery()->address() ) ) && ( false === strpos( $this->file, eazyest_gallery()->root() ) ) )
			return $filename;

		$dir    = dirname( $this->file );
		$name   = basename( $filename );

		$dest_path = $dir . '/_cache';
		if ( ! is_dir( $dest_path ) )
			wp_mkdir_p( $dest_path );

		return trailingslashit( $dest_path ) . $name;
	}

	/**
	 * Eazyest_Image_Editor::exif_orientation()
	 * Re-orient resized images based on exif orientation in source file.
	 *
	 * @since 0.2.0 (r322)
	 * @access protected
	 * @return void
	 */
	protected function exif_orientation() {
		if ( apply_filters( 'eazyest_gallery_skip_exif_orientation', false ) )
			return;

		if ( ! function_exists( 'exif_read_data' ) )
			return;

		if ( $exif = exif_read_data( $this->file ) ) {
			$orientation = isset ( $exif['Orientation'] ) ? $exif['Orientation'] : false;
			if ( $orientation ) {
				switch( $orientation ) {
					case 3: // 180 rotate left
						$this->rotate( 180 );
						break;
					case 6: // 90 rotate right
						$this->rotate( -90 );
						break;
					case 8: // 90 rotate left
						$this->rotate( 90 );
						break;
				}
			}
		}
	}

		/**
	 * Saves current in-memory image to file.
	 *
	 * @since 0.1.0 (r61)
	 * @access public
	 *
	 * @param string $destfilename
	 * @param string $mime_type
	 * @return array|WP_Error {'path'=>string, 'file'=>string, 'width'=>int, 'height'=>int, 'mime-type'=>string}
	 */
	public function save( $filename = null, $mime_type = null ) {
		if ( ! isset( $filename ) )
			return parent::save( $filename, $mime_type );

		$filename = str_replace( '\\', '/', $filename );

		if ( ( false === strpos( $this->file, eazyest_gallery()->address() ) ) && ( false === strpos( $this->file, eazyest_gallery()->root() ) ) )
			return parent::save( $filename, $mime_type );

		if ( basename( $this->file ) != basename( $filename ) && false === strpos( $filename, '_cache' ) ) {
			$dirname = dirname( $this->file );

			if ( strpos( $dirname, eazyest_gallery()->address() ) )
				$dirname = str_replace( eazyest_gallery()->address(), eazyest_gallery()->root(), $dirname );

			if ( false === strpos( $filename, 'midsize-' ) ){
				$dirname .=	'/_cache';
				set_transient( 'eazyest_gallery_created_cache', $dirname . '/' . basename( $filename ) );
				if ( false !== strpos( $filename, 'midsize-' ) )
					set_transient( 'eazyest_gallery_midsize', $this->file );
			}

			$filename = $dirname . '/' . basename( $filename );
		}
		return parent::save( $filename, $mime_type );
	}
} // Eazyest_Image_Editor
