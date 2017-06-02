<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Eazyest_Gallery_Exif
 * Plugin to display Exif data for an attachment
 *
 * @package Eazyest Gallery
 * @subpackage Plugins/Exif
 * @author Marcel Brinkkemper
 * @copyright 2013 Brimosoft
 * @version 0.1.0 (r324)
 * @since 01.10 (r2)
 * @access public
 */
class Eazyest_Gallery_Exif {

	/**
	 * @staticvar object $instance
	 */
	private static $instance;

	/**
	 * Eazyest_Gallery_Exif::__construct()
	 *
	 * @return void
	 */
	function __construct(){}

	/**
	 * Eazyest_Gallery_Exif::init()
	 *
	 * @access private
	 * @return void
	 */
	private function init() {
		$this->actions();
		$this->filters();
	}

	/**
	 * Eazyest_Gallery_Exif::instance()
	 *
	 * @return object Eazyest_Gallery_Exif
	 */
	public static function instance() {
		// enable only when exif extension is loaded
		if ( ! is_callable( 'exif_read_data' ) )
			return null;

		if ( ! isset( self::$instance ) ) {
			self::$instance = new Eazyest_Gallery_Exif;
			self::$instance->init();
		}
		return self::$instance;
	}

	/**
	 * Eazyest_Gallery_Exif::actions()
	 *
	 * @since 0.1.0 (r70)
	 * @uses add_action()
	 * @return void
	 */
	function actions() {
		// only add frontend actions when user has checked option
		if ( eazyest_gallery()->enable_exif ) {
			add_action( 'eazyest_gallery_after_attachment', array( $this, 'show_exif' ) );
		}
		add_action( 'eazyest_gallery_before_rotation', 'ezg_get_exif' );
	}

	/**
	 * Eazyest_Gallery_Exif::filters()
	 *
	 * @since 0.1.0 (r2)
	 * @uses add_filter()
	 * @return void
	 */
	function filters() {
		add_filter( 'eazyest_gallery_image_settings',  array( $this, 'exif_option' ) );
	}

	/**
	 * Eazyest_Gallery_Exif::exif_option()
	 * Add exif option to Eazyest Gallery settings screen.
	 * @see Eazyest_Settings_Page::fields()
	 *
	 * @since 0.1.0 (r2)
	 * @param array $options
	 * @return array
	 */
	function exif_option( $options ) {
		$options['enable_exif'] = array(
			'title' => __( 'Exif', 'eazyest-gallery' ),
			'callback' => array( $this, 'enable_exif' )
		);
		return $options;
	}

	/**
	 * Eazyest_Gallery_Exif::enable_exif()
	 * Output settings screen row for anable exif option.
	 *
	 * @since 0.1.0 (r2)
	 * @uses checked() to set checkbox input
	 * @return void
	 */
	function enable_exif() {
		$enable_exif =  eazyest_gallery()->enable_exif;
		?>
		<input type="checkbox" id="enable_exif" name="eazyest-gallery[enable_exif]" value="1" <?php checked( $enable_exif ) ?> />
		<label for="enable_exif"><?php _e( 'Show Exif information on the attachment page', 'eazyest-gallery' ) ?> </label>
		<?php
	}

	// exif administration functions ---------------------------------------------

	/**
	 * Eazyest_Gallery_Exif::get_exif_data()
	 * Read Exif data from image file.
	 * For Eazyest Gallery images, store Exif data as postmeta record to preserve data when original file gets changed.
	 *
	 * @since 0.2.0 (r324)
	 * @access public
	 * @uses get_post_meta() to read exif from wpdb
	 * @uses get_attached_file() to get filename
	 * @uses update_post_meta() to store exif in wpdb
	 * @param int $attachment_id
	 * @return array exif data extracted from image
	 */
	public function get_exif_data( $attachment_id ) {

		$exif = get_post_meta( $attachment_id, '_eazyest_exif_data', true );

		if ( ! empty( $exif ) )
			return $exif;

		$exif = array();
		$attached_file = get_attached_file( $attachment_id );
		if ( $exif = @read_exif_data( $attached_file ) ) {

			// extra field of compiled GPS data for use with Google Maps
			$exif['gps']       = $this->get_geodata( $attachment_id, $exif );

			// store postmeta if image is in Eazyest Gallery
			if ( eazyest_folderbase()->is_gallery_image( $attachment_id ) )
				update_post_meta( $attachment_id,  '_eazyest_exif_data', $exif );
		}
		return $exif;
	}

	/**
	 * Eazyest_Gallery_Exif::get_geodata()
	 * Get compiled GPS data from Exif for use in Google Maps
	 *
	 * @since 0.2.0 (r324)
	 * @access public
	 * @param int $attachment_id
	 * @param array $exif Exif data read from file
	 * @return string gps data like "51.64683,5.2665"
	 */
	public function get_geodata( $attachment_id, $exif=null ) {
		// read exif data if none given
		if ( ! isset( $exif ) ) {
			$exif = $this->get_exif_data( $attachment_id );
			if ( ! $exif )
				return '';
		}
		if ( isset( $exif['gps'] ) )
			return $exif['gps'];

		$geodata = '';
		$gps= array();
		if ( !empty( $exif['GPSLatitude'] ) ) {
			$gps['latitude_hour']    = $exif['GPSLatitude'][0];
			$gps['latitude_minute']  = $exif['GPSLatitude'][1];
			$gps['latitude_second']  = $exif['GPSLatitude'][2];
			$gps['longitude_hour']   = $exif['GPSLongitude'][0];
			$gps['longitude_minute'] = $exif['GPSLongitude'][1];
			$gps['longitude_second'] = $exif['GPSLongitude'][2];

			foreach( $gps as $key => $value ) {
				$pos = strpos( $value, '/' );
				if( $pos !== false ) {
					$temp = explode( '/',$value );
					$gps[$key] = $temp[0] / $temp[1];
				}
			}

			$latitude = round( $gps['latitude_hour'] + ( $gps['latitude_minute'] / 60 ) + ( $gps['latitude_second'] / 3600 ), 5 );
			$latitude = $exif['GPSLatitudeRef'] == "S" ? -1 * $latitude : $latitude;

			$longitude = round( $gps['longitude_hour'] + ( $gps['longitude_minute'] / 60 ) + ( $gps['longitude_second'] / 3600 ), 5 );
			$longitude = ( $exif['GPSLongitudeRef'] == "W" ) ? -1 * $longitude : $longitude;

			$geodata = $latitude . ',' . $longitude;
		}
		return $geodata;
	}

	// exif output functions -----------------------------------------------------

	/**
	 * Eazyest_Gallery_Exif::imgtype()
	 * Returns array of exif image types strings.
	 *
	 * @since 0.1.0 (r71)
	 * @access protected
	 * @return array
	 */
	protected function imgtype() {
		return array(
			'',
			_x( 'GIF',                       'exif imgtype', 'eazyest-gallery' ),
			_x( 'JPG',                       'exif imgtype', 'eazyest-gallery' ),
			_x( 'PNG',                       'exif imgtype', 'eazyest-gallery' ),
			_x( 'SWF',                       'exif imgtype', 'eazyest-gallery' ),
			_x( 'PSD',                       'exif imgtype', 'eazyest-gallery' ),
			_x( 'BMP',                       'exif imgtype', 'eazyest-gallery' ),
			_x( 'TIFF(intel byte order)',    'exif imgtype', 'eazyest-gallery' ),
			_x( 'TIFF(motorola byte order)', 'exif imgtype', 'eazyest-gallery' ),
			_x( 'JPC',                       'exif imgtype', 'eazyest-gallery' ),
			_x( 'JP2',                       'exif imgtype', 'eazyest-gallery' ),
			_x( 'JPX',                       'exif imgtype', 'eazyest-gallery' ),
			_x( 'JB2',                       'exif imgtype', 'eazyest-gallery' ),
			_x( 'SWC',                       'exif imgtype', 'eazyest-gallery' ),
			_x( 'IFF',                       'exif imgtype', 'eazyest-gallery' ),
			_x( 'WBMP',                      'exif imgtype', 'eazyest-gallery' ),
			_x( 'XBM',                       'exif imgtype', 'eazyest-gallery' ),
		);
	}

	/**
	 * Eazyest_Gallery_Exif::orientation()
	 * Returns array of exif orientation strings.
	 *
	 * @since 0.1.0 (r71)
	 * @access protected
	 * @return array
	 */
	protected function orientation() {
		return array(
			'',
			_x( 'top left side',     'exif orientation', 'eazyest-gallery' ),
			_x( 'top right side',    'exif orientation', 'eazyest-gallery' ),
			_x( 'bottom right side', 'exif orientation', 'eazyest-gallery' ),
			_x( 'bottom left side',  'exif orientation', 'eazyest-gallery' ),
			_x( 'left side top',     'exif orientation', 'eazyest-gallery' ),
			_x( 'right side top',    'exif orientation', 'eazyest-gallery' ),
			_x( 'right side bottom', 'exif orientation', 'eazyest-gallery' ),
			_x( 'left side bottom',  'exif orientation', 'eazyest-gallery' ),
		);
	}

	/**
	 * Eazyest_Gallery_Exif::resolution_unit()
	 * Returns array of exif resolution unit strings.
	 *
	 * @since 0.1.0 (r71)
	 * @access protected
	 * @return array
	 */
	protected function resolution_unit() {
		return array(
			'',
			'',
			_x( 'inches',      'exif resolution unit', 'eazyest-gallery' ),
			_x( 'centimeters', 'exif resolution unit', 'eazyest-gallery' ),
		);
	}

	/**
	 * Eazyest_Gallery_Exif::ycbcr_positioning()
	 * Returns array of exif ycbcr positioning strings.
	 *
	 * @since 0.1.0 (r71)
	 * @access protected
	 * @return array
	 */
	protected function ycbcr_positioning() {
		return array(
			'',
			_x( 'the center of pixel array', 'exif ycbr positioning', 'eazyest-gallery' ),
			_x( 'the datum point',           'exif ycbr positioning', 'eazyest-gallery' ),
		);
	}

	/**
	 * Eazyest_Gallery_Exif::exposure_program()
	 * Returns array of exif exposure program strings.
	 *
	 * @since 0.1.0 (r71)
	 * @access protected
	 * @return array
	 */
	protected function exposure_program() {
		return array(
      _x( 'Not defined',                                                         'exif exposure program', 'eazyest-gallery' ),
      _x( 'Manual',                                                              'exif exposure program', 'eazyest-gallery' ),
      _x( 'Normal program',                                                      'exif exposure program', 'eazyest-gallery' ),
      _x( 'Aperture priority',                                                   'exif exposure program', 'eazyest-gallery' ),
      _x( 'Shutter priority',                                                    'exif exposure program', 'eazyest-gallery' ),
      _x( 'Creative program (biased toward depth of field)',                     'exif exposure program', 'eazyest-gallery' ),
      _x( 'Action program (biased toward fast shutter speed)',                   'exif exposure program', 'eazyest-gallery' ),
      _x( 'Portrait mode (for closeup photos with the background out of focus)', 'exif exposure program', 'eazyest-gallery' ),
      _x( 'Landscape mode (for landscape photos with the background in focus)',  'exif exposure program', 'eazyest-gallery' ),
    );
	}

	/**
	 * Eazyest_Gallery_Exif::metering_mode()
	 * Returns array of exif metering mode strings.
	 *
	 * @since 0.1.0 (r71)
	 * @access protected
	 * @return array
	 */
	protected function metering_mode() {
		return array(
        '0' => _x( 'Unknown',                 'exif metering mode', 'eazyest-gallery' ),
        '1' => _x( 'Average',                 'exif metering mode', 'eazyest-gallery' ),
        '2' => _x( 'Center Weighted Average', 'exif metering mode', 'eazyest-gallery' ),
        '3' => _x( 'Spot',                    'exif metering mode', 'eazyest-gallery' ),
        '4' => _x( 'MultiSpot',               'exif metering mode', 'eazyest-gallery' ),
        '5' => _x( 'Pattern',                 'exif metering mode', 'eazyest-gallery' ),
        '6' => _x( 'Partial',                 'exif metering mode', 'eazyest-gallery' ),
      '255' => _x( 'Other Metering Mode',     'exif metering mode', 'eazyest-gallery' ),
    );
	}

	/**
	 * Eazyest_Gallery_Exif::light_source()
	 * Returns array of exif light source strings.
	 *
	 * @since 0.1.0 (r71)
	 * @access protected
	 * @return array
	 */
	protected function light_source() {
		return array(
        '0' => _x( 'unknown',                                 'exif light source', 'eazyest-gallery' ),
        '1' => _x( 'Daylight',                                'exif light source', 'eazyest-gallery' ),
        '2' => _x( 'Fluorescent',                             'exif light source', 'eazyest-gallery' ),
        '3' => _x( 'Tungsten (incandescent light)',           'exif light source', 'eazyest-gallery' ),
        '4' => _x( 'Flash',                                   'exif light source', 'eazyest-gallery' ),
        '9' => _x( 'Fine weather',                            'exif light source', 'eazyest-gallery' ),
       '10' => _x( 'Cloudy weather',                          'exif light source', 'eazyest-gallery' ),
       '12' => _x( 'Daylight fluorescent (D 5700 – 7100K)',   'exif light source', 'eazyest-gallery' ),
       '13' => _x( 'Day white fluorescent (N 4600 – 5400K)',  'exif light source', 'eazyest-gallery' ),
       '14' => _x( 'Cool white fluorescent (W 3900 – 4500K)', 'exif light source', 'eazyest-gallery' ),
       '15' => _x( 'White fluorescent (WW 3200 – 3700K)',     'exif light source', 'eazyest-gallery' ),
       '17' => _x( 'Standard light A',                        'exif light source', 'eazyest-gallery' ),
       '18' => _x( 'Standard light B',                        'exif light source', 'eazyest-gallery' ),
       '19' => _x( 'Standard light C',                        'exif light source', 'eazyest-gallery' ),
       '20' => _x( 'D55',                                     'exif light source', 'eazyest-gallery' ),
       '21' => _x( 'D65',                                     'exif light source', 'eazyest-gallery' ),
       '22' => _x( 'D75',                                     'exif light source', 'eazyest-gallery' ),
       '23' => _x( 'D50',                                     'exif light source', 'eazyest-gallery' ),
       '24' => _x( 'ISO studio tungsten',                     'exif light source', 'eazyest-gallery' ),
      '255' => _x( 'other light source',                      'exif light source', 'eazyest-gallery' ),
    );
	}

	/**
	 * Eazyest_Gallery_Exif::flash()
	 * Returns array of exif flash strings.
	 *
	 * @since 0.1.0 (r71)
	 * @access protected
	 * @return array
	 */
	protected function flash() {
		return array(
       '0' => _x( 'Flash did not fire.',                                                                   'exif flash', 'eazyest-gallery' ),
       '1' => _x( 'Flash fired.',                                                                          'exif flash', 'eazyest-gallery' ),
       '5' => _x( 'Strobe return light not detected.',                                                     'exif flash', 'eazyest-gallery' ),
       '7' => _x( 'Strobe return light detected.',                                                         'exif flash', 'eazyest-gallery' ),
       '9' => _x( 'Flash fired, compulsory flash mode',                                                    'exif flash', 'eazyest-gallery' ),
      '13' => _x( 'Flash fired, compulsory flash mode, return light not detected',                         'exif flash', 'eazyest-gallery' ),
      '15' => _x( 'Flash fired, compulsory flash mode, return light detected',                             'exif flash', 'eazyest-gallery' ),
      '16' => _x( 'Flash did not fire, compulsory flash mode',                                             'exif flash', 'eazyest-gallery' ),
      '24' => _x( 'Flash did not fire, auto mode',                                                         'exif flash', 'eazyest-gallery' ),
      '25' => _x( 'Flash fired, auto mode',                                                                'exif flash', 'eazyest-gallery' ),
      '29' => _x( 'Flash fired, auto mode, return light not detected',                                     'exif flash', 'eazyest-gallery' ),
      '31' => _x( 'Flash fired, auto mode, return light detected',                                         'exif flash', 'eazyest-gallery' ),
      '32' => _x( 'No flash function',                                                                     'exif flash', 'eazyest-gallery' ),
      '65' => _x( 'Flash fired, red-eye reduction mode',                                                   'exif flash', 'eazyest-gallery' ),
      '69' => _x( 'Flash fired, red-eye reduction mode, return light not detected',                        'exif flash', 'eazyest-gallery' ),
      '71' => _x( 'Flash fired, red-eye reduction mode, return light detected',                            'exif flash', 'eazyest-gallery' ),
      '73' => _x( 'Flash fired, compulsory flash mode, red-eye reduction mode',                            'exif flash', 'eazyest-gallery' ),
      '77' => _x( 'Flash fired, compulsory flash mode, red-eye reduction mode, return light not detected', 'exif flash', 'eazyest-gallery' ),
      '79' => _x( 'Flash fired, compulsory flash mode, red-eye reduction mode, return light detected',     'exif flash', 'eazyest-gallery' ),
      '89' => _x( 'Flash fired, auto mode, red-eye reduction mode',                                        'exif flash', 'eazyest-gallery' ),
      '93' => _x( 'Flash fired, auto mode, return light not detected, red-eye reduction mode',             'exif flash', 'eazyest-gallery' ),
      '95' => _x( 'Flash fired, auto mode, return light detected, red-eye reduction mode',                 'exif flash', 'eazyest-gallery' ),
    );
	}

	/**
	 * Eazyest_Gallery_Exif::_photo_getval()
	 * Get exif string value from array of strings.
	 *
	 * @since 0.1.0 (r71)
	 * @access private
	 * @param string $image_info
	 * @param array $val_array
	 * @return string
	 */
	private function _photo_getval( $image_info, $val_array ) {
    $info_val = _x( 'Unknown', 'exif info', 'eazyest-gallery' );
    foreach( $val_array as $name => $val ) {
      if ( $name == $image_info ) {
        $info_val = &$val;
        break;
      }
    }
    return $info_val;
  }

	/**
	 * Eazyest_Gallery_Exif::show_exif()
	 * Show exif data div below image.
	 * div is hidden on display, shows on user click.
	 *
	 * @since 0.1.0 (r71)
	 * @uses get_post() to get attachment properties
	 * @param int $post_id for currently showing attachment
	 * @return void
	 */
	function show_exif( $post_id ) {

		$imgtype           = $this->imgtype();
    $orientation       = $this->orientation();
    $resolution_unit   = $this->resolution_unit();
    $ycbcr_positioning = $this->ycbcr_positioning();
    $exposure_program  = $this->exposure_program();
    $metering_mode     = $this->metering_mode();
    $light_source      = $this->light_source();
    $flash             = $this->flash();

    $exif = ezg_get_exif( $post_id );

		if ( $exif ) {
	    $img_info = array ();
	    if ( isset( $exif['FILE']['FileName'] ) )
	      $img_info[_x( 'File Name', 'exif info', 'eazyest-gallery' )] = $exif['FILE']['FileName'];

	    if ( isset( $exif['FILE']['FileType'] ) )
	      $img_info[_x( 'File Type', 'exif info', 'eazyest-gallery' )] =  $imgtype[$exif['FILE']['FileType']];

	    if ( isset( $exif['FILE']['MimeType'] ) )
	      $img_info[_x( 'Mime Type', 'exif info', 'eazyest-gallery' )] =  $exif['FILE']['MimeType'];

	    if ( isset( $exif['FILE']['FileSize'] ) )
	      $img_info[_x( 'File Size', 'exif info', 'eazyest-gallery' )] = ( floor( $exif['FILE']['FileSize'] / 1024 * 10 ) /10 ) . 'KB';

	    if ( isset( $exif['FILE']['FileDateTime'] ) )
	      $img_info[_x( 'File Date/Time', 'exif info', 'eazyest-gallery' )] = date( 'Y-m-d  H:i:s', $exif['FILE']['FileDateTime'] );

	    if ( isset( $exif['IFD0']['Artist'] ) )
	      $img_info[_x( 'Artist', 'exif info', 'eazyest-gallery' )] = $exif['IFD0']['Artist'];

	    if ( isset( $exif['IFD0']['Make'] ) )
	      $img_info[_x( 'Make', 'exif info', 'eazyest-gallery' )] = $exif['IFD0']['Make'];

	    if ( isset( $exif['IFD0']['Model'] ) )
	      $img_info[_x( 'Model', 'exif info', 'eazyest-gallery' )] = $exif['IFD0']['Model'];

	    if ( isset( $exif['IFD0']['DateTime'] ) )
	      $img_info[_x( 'Date/Time', 'exif info', 'eazyest-gallery' )] = $exif['IFD0']['DateTime'];

	    if ( isset( $exif['EXIF']['ExifVersion'] ) )
	      $img_info[_x( 'Exif Version', 'exif info', 'eazyest-gallery' )] = $exif['EXIF']['ExifVersion'];

	    if ( isset( $exif['EXIF']['DateTimeOriginal'] ) )
	      $img_info[_x( 'Date/Time Original', 'exif info', 'eazyest-gallery' )] = $exif['EXIF']['DateTimeOriginal'];

	    if ( isset( $exif['EXIF']['DateTimeDigitized'] ) )
	      $img_info[_x( 'Date/Time Digitized', 'exif info', 'eazyest-gallery' )] = $exif['EXIF']['DateTimeDigitized'];

	    if ( isset( $exif['COMPUTED']['Height'] ) )
	      $img_info[_x( 'Height', 'exif info', 'eazyest-gallery' )] = $exif['COMPUTED']['Height'] . 'px';

	    if ( isset( $exif['COMPUTED']['Width'] ) )
	      $img_info[_x( 'Width', 'exif info', 'eazyest-gallery' )] = $exif['COMPUTED']['Width'] . 'px';

	    if ( isset( $exif['EXIF']['CompressedBitsPerPixel'] ) )
	      $img_info[_x( 'Compressed Bits Per Pixel', 'exif info', 'eazyest-gallery' )] = sprintf( _x( '%s Bits/Pixel', 'exif info', 'eazyest-gallery' ), $exif['EXIF']['CompressedBitsPerPixel'] );

	    $img_info[_x( 'Focus Distance', 'exif info', 'eazyest-gallery' )] = isset( $exif['COMPUTED']['FocusDistance'] ) ? sprintf(  _x( '%s m', 'length meter', 'eazyest-gallery' ), $exif['COMPUTED']['FocusDistance'] ) : NULL;

	    $img_info[_x( 'Focal Length', 'exif info', 'eazyest-gallery' )] = isset( $exif['EXIF']['FocalLength'] ) ? sprintf( _x( '%s mm', 'length milimeter', 'eazyest-gallery' ), $exif['EXIF']['FocalLength'] ) : NULL;

	    $img_info[_x( 'FocalLength In 35mm Film', 'exif info', 'eazyest-gallery' )] = isset( $exif['EXIF']['FocalLengthIn35mmFilm'] ) ? sprintf( _x( '%s mm', 'length milimeter', 'eazyest-gallery' ), $exif['EXIF']['FocalLengthIn35mmFilm'] ) : NULL;

	    if ( isset( $exif['EXIF']['ColorSpace'] ) )
	      $img_info[_x( 'Color Space', 'exif info', 'eazyest-gallery' )] = $exif['EXIF']['ColorSpace'] == 1 ? _x( 'sRGB', 'exif info', 'eazyest-gallery' ) :  _x( 'Uncalibrated', 'exif info', 'eazyest-gallery' );

	    if ( isset( $exif['IFD0']['ImageDescription'] ) )
	      $img_info[_x( 'Image Description', 'exif info', 'eazyest-gallery' )] = $exif['IFD0']['ImageDescription'];

	    if ( isset( $exif['IFD0']['Orientation'] ) )
	      $img_info[_x( 'Orientation', 'exif info', 'eazyest-gallery' )] = $orientation[$exif['IFD0']['Orientation']];

	    if ( isset( $exif['IFD0']['XResolution'] ) )
	    	                                                                // translators: resolution, unit
	      $img_info[_x( 'X Resolution', 'exif info', 'eazyest-gallery' )] = sprintf( __( '%s%s', 'eazyest-gallery' ), $exif['IFD0']['XResolution'], $resolution_unit[$exif['IFD0']['ResolutionUnit']] );

	    if ( isset( $exif['IFD0']['YResolution'] ) )
	    	                                                                // translators: resolution, unit
	      $img_info[_x( 'Y Resolution', 'exif info', 'eazyest-gallery' )] = sprintf( __( '%s%s', 'eazyest-gallery' ), $exif['IFD0']['YResolution'], $resolution_unit[$exif['IFD0']['ResolutionUnit']] );

	    if ( isset( $exif['IFD0']['Software'] ) )
	      $img_info[_x( 'Software', 'exif info', 'eazyest-gallery' )] = utf8_encode( $exif['IFD0']['Software'] );

	    if ( isset( $exif['IFD0']['YCbCrPositioning'] ) )
	      $img_info[_x( 'YCbCr Positioning', 'exif info', 'eazyest-gallery' )] = $ycbcr_positioning[$exif['IFD0']['YCbCrPositioning']];

	    if ( isset( $exif['IFD0']['Copyright'] ) )
	      $img_info[_x( 'Copyright', 'exif info', 'eazyest-gallery' )] = $exif['IFD0']['Copyright'];

	    if ( isset( $exif['COMPUTED']['Copyright.Photographer'] ) )
	      $img_info[_x( 'Photographer', 'exif info', 'eazyest-gallery' )] = $exif['COMPUTED']['Copyright.Photographer'];

	    if ( isset( $exif['COMPUTED']['Copyright.Editor'] ) )
	      $img_info[_x( 'Editor', 'exif info', 'eazyest-gallery' )] = $exif['COMPUTED']['Copyright.Editor'];

	    if ( isset( $exif['EXIF']['ExifVersion'] ) )
	      $img_info[_x( 'Exif Version', 'exif info', 'eazyest-gallery' )] = $exif['EXIF']['ExifVersion'];

	    if ( isset( $exif['EXIF']['FlashPixVersion'] ) )
				                                                                    // translators: Flashpix version
	      $img_info[_x( 'Flashpix Version', 'exif info', 'eazyest-gallery' )] = sprintf( _x( 'Version %s', 'exif info', 'eazyest-gallery' ), number_format( $exif['EXIF']['FlashPixVersion']/100, 2 ) );

	    if ( isset( $exif['EXIF']['ApertureValue'] ) )
	      $img_info[_x( 'Aperture Value', 'exif info', 'eazyest-gallery' )] = $exif['EXIF']['ApertureValue'];

	    if ( isset( $exif['EXIF']['ShutterSpeedValue'] ) )
	      $img_info[_x( 'Shutter Speed Value', 'exif info', 'eazyest-gallery' )] = $exif['EXIF']['ShutterSpeedValue'];

	    if ( isset( $exif['COMPUTED']['ApertureFNumber'] ) )
	      $img_info[_x( 'Aperture F-Number', 'exif info', 'eazyest-gallery' )] = $exif['COMPUTED']['ApertureFNumber'];

	    if ( isset( $exif['EXIF']['MaxApertureValue'] ) )
	      $img_info[_x( 'Max Aperture Value', 'exif info', 'eazyest-gallery' )] = 'F' . $exif['EXIF']['MaxApertureValue'];

	    if ( isset( $exif['EXIF']['ExposureTime'] ) )
	      $img_info[_x( 'Exposure Time', 'exif info', 'eazyest-gallery' )] = $exif['EXIF']['ExposureTime'];

	    if ( isset( $exif['EXIF']['FNumber'] ) )
	      $img_info[_x( 'F-Number', 'exif info', 'eazyest-gallery' )] = $exif['EXIF']['FNumber'];

	    if ( isset( $exif['EXIF']['MeteringMode'] ) )
	      $img_info[_x( 'Metering Mode', 'exif info', 'eazyest-gallery' )] = $this->_photo_getval( $exif['EXIF']['MeteringMode'], $metering_mode );

	    if ( isset( $exif['EXIF']['LightSource'] ) )
	      $img_info[_x( 'Light Source', 'exif info', 'eazyest-gallery' )] = $this->_photo_getval( $exif['EXIF']['LightSource'], $light_source );

	    if ( isset( $exif['EXIF']['Flash'] ) )
	      $img_info[_x( 'Flash', 'exif info', 'eazyest-gallery' )] = $this->_photo_getval( $exif['EXIF']['Flash'], $flash );

	    if ( isset( $exif['EXIF']['ExposureMode'] ) )
	      $img_info[_x( 'Exposure Mode', 'exif info', 'eazyest-gallery' )] = $exif['EXIF']['ExposureMode'] == 1 ? _x( 'Manual exposure', 'exif info', 'eazyest-gallery' ) : _x( 'Auto exposure', 'exif info', 'eazyest-gallery' );

	    if ( isset( $exif['EXIF']['WhiteBalance'] ) )
	      $img_info[_x( 'White Balance', 'exif info', 'eazyest-gallery' )] = $exif['EXIF']['WhiteBalance'] == 1 ?  _x( 'Manual white balance', 'exif info', 'eazyest-gallery'  ) :  _x( 'Auto white balance', 'exif info', 'eazyest-gallery'  );

	    if ( isset( $exif['EXIF']['ExposureProgram'] ) )
	      $img_info[_x( 'Exposure Program', 'exif info', 'eazyest-gallery' )] = $exposure_program[$exif['EXIF']['ExposureProgram']];

	    if ( isset( $exif['EXIF']['ExposureBiasValue'] ) )
	    	                                                                       // translators: exposure bias value
	      $img_info[_x( 'Exposure Bias Value', 'exif info', 'eazyest-gallery' )] = sprintf( _x( ' %sEV', 'exif info', 'eazyest-gallery' ), $exif['EXIF']['ExposureBiasValue'] );

	    if ( isset( $exif['EXIF']['ISOSpeedRatings'] ) )
	      $img_info[_x( 'ISO Speed Ratings', 'exif info', 'eazyest-gallery' )] = $exif['EXIF']['ISOSpeedRatings'];

	    if ( isset( $exif['EXIF']['ComponentsConfiguration'] ) )
	      $img_info[_x( 'Components Configuration', 'exif info', 'eazyest-gallery' )] = bin2hex( $exif['EXIF']['ComponentsConfiguration'] ) == '01020300' ? _x( 'YCbCr', 'exif info', 'eazyest-gallery' ) : _x( 'RGB', 'exif info', 'eazyest-gallery' );

	    if ( isset( $exif['COMPUTED']['UserCommentEncoding'] ) )
	      $img_info[_x( 'User Comment Encoding', 'exif info', 'eazyest-gallery' )] = $exif['COMPUTED']['UserCommentEncoding'];

	    if ( isset( $exif['COMPUTED']['UserComment'] ) )
	      $img_info[_x( 'User Comment', 'exif info', 'eazyest-gallery' )] = $exif['COMPUTED']['UserComment'];

	    if ( isset( $exif['EXIF']['ExifImageLength'] ) )
	      $img_info[_x( 'Exif Image Length', 'exif info', 'eazyest-gallery' )] = $exif['EXIF']['ExifImageLength'];

	    if ( isset( $exif['EXIF']['ExifImageWidth'] ) )
	      $img_info[_x( 'Exif Image Width', 'exif info', 'eazyest-gallery' )] = $exif['EXIF']['ExifImageWidth'];

	    if ( isset( $exif['EXIF']['FileSource'] ) )
	      $img_info[_x( 'File Source', 'exif info', 'eazyest-gallery' )] = bin2hex( $exif['EXIF']['FileSource'] ) == 0x03 ? _x( 'DSC', 'exif info', 'eazyest-gallery'  ) : _x( 'unknown', 'exif info', 'eazyest-gallery'  );

	    if ( isset( $exif['EXIF']['SceneType'] ) )
	      $img_info[_x( 'Scene Type', 'exif info', 'eazyest-gallery' )] = bin2hex( $exif['EXIF']['SceneType'] ) == 0x01 ? _x( 'A directly photographed image', 'exif info', 'eazyest-gallery'  ) :  _x( 'unknown', 'exif info', 'eazyest-gallery'  );

	    if ( isset( $exif['COMPUTED']['Thumbnail.FileType'] ) )
	      $img_info[_x( 'Thumbnail FileType', 'exif info', 'eazyest-gallery' )] = $exif['COMPUTED']['Thumbnail.FileType'];

	    if ( isset( $exif['COMPUTED']['Thumbnail.MimeType'] ) )
	      $img_info[_x( 'Thumbnail MimeType', 'exif info', 'eazyest-gallery' )] = $exif['COMPUTED']['Thumbnail.MimeType'];
		}
    ?>
      <script type="text/javascript">
      	var showingExif = false;
      	var showExifString = '<?php _e( 'Show Exif data', 'eazyest-gallery' ); ?>';
      	var hideExifString = '<?php _e( 'Hide Exif data', 'eazyest-gallery' ); ?>';
				function showExif() {
					if ( showingExif ){
						jQuery('#exif-imagedata').hide();
						jQuery('#show-exif').html(showExifString);
						showingExif = false;
					} else {
						jQuery('#exif-imagedata').show();
						jQuery('#show-exif').html(hideExifString);
						showingExif = true;
					}
				}
			</script>
      <style type="text/css">
      	#exif-imagedata { display:none }
      	table.imagedata-table { margin-top:1em }
      	table.imagedata-table tbody th { padding-right: 10px; text-align:right; width:20em; }
			</style>
			<div id="exif-imagedata">
    		<?php if ( $exif ) : ?>
	      <table class="imagedata-table">
	      	<thead>
	      		<tr>
	      			<th></th>
	      			<th><?php _e( 'Image data', 'eazyest-gallery' ); ?></th>
						</tr>
					</thead>
	        <tbody>
	          <tr>
	            <th scope="row"><?php _e( 'Date', 'eazyest-gallery' ); ?></th>
	            <td><?php echo $img_info[__( 'File Date/Time', 'eazyest-gallery' )]; ?></td>
	          </tr>
	          <tr>
	            <th scope="row"><?php _e( 'Height', 'eazyest-gallery' ); ?></th>
	            <td><?php echo $img_info[__( 'Height', 'eazyest-gallery' )]; ?></td>
	          </tr>
	          <tr>
	            <th scope="row"><?php _e( 'Width', 'eazyest-gallery' ); ?></th>
	            <td><?php echo $img_info[__( 'Width', 'eazyest-gallery' )]; ?></td>
	          </tr>
	          <?php if ( isset( $img_info[__( 'Make', 'eazyest-gallery' )] ) && isset( $img_info[__( 'Model', 'eazyest-gallery' )]) ) : ?>
	          <tr>
	            <th scope="row"><?php _e( 'Camera', 'eazyest-gallery' ); ?></th>
	            <td><?php echo $img_info[__( 'Make', 'eazyest-gallery' )] . ' - ' . $img_info[__( 'Model', 'eazyest-gallery' )]; ?></td>
	          </tr>
	          <?php endif; ?>
	        </tbody>
	        <tbody id="all-exif">
		        <?php foreach( $img_info as $name => $val ) : if ( $val ) : ?>
		          <tr>
		            <th scope="row"><?php echo $name; ?></th>
		            <td><?php echo $val; ?></td>
		          </tr>
		        <?php endif; endforeach; ?>
	        </tbody>
	      </table>
	    	<?php else : ?>
	    	<?php  list($width, $height, $type, $attr) = getimagesize( $original ); ?>
	      <table class="imagedata-table">
	      	<thead>
	      		<tr>
	      			<th></th>
	      			<th><?php _e( 'Image data', 'eazyest-gallery' ); ?></th>
						</tr>
					</thead>
	        <tbody>
	          <tr>
	            <th scope="row"><?php _e( 'Date', 'eazyest-gallery' ); ?></th>
	            <td><?php echo date( get_option('date_format' ), filemtime( $original ) ); ?></td>
	          </tr>
						<tr>
	            <th scope="row"><?php _e( 'Height', 'eazyest-gallery' ); ?></th>
	            <td><?php printf( '%dpx', $height ); ?></td>
	          </tr>
						<tr>
	            <th scope="row"><?php _e( 'Width', 'eazyest-gallery'  ); ?></th>
	            <td><?php printf( '%dpx', $width ); ?></td>
	          </tr>
	        </tbody>
	      </table>
				<?php endif; ?>
  		</div>
      <p><a id="show-exif" href="javascript:showExif();"><?php _e( 'Show Exif data', 'eazyest-gallery' ); ?></a></p>
  	<?php
		wp_enqueue_script( 'jquery' );
	}

} // Eazyest_Gallery_Exif

/**
 * ezg_get_exif()
 * Get exif data for a particular attachment.
 *
 * @since 0.2.0 (r324)
 * @param int $attachment_id
 * @return array|bool exif data|false if no exif data.
 */
function ezg_get_exif( $attachment_id ) {

	$exif = eazyest_gallery_exif()->get_exif_data( $attachment_id );

	if ( empty( $exif ) )
		return false;

	return $exif;
}

/**
 * eazyest_gallery_exif()
 *
 * @since 0.1.0 (r2)
 * @return object Eazyest_Gallery_Exif
 */
function eazyest_gallery_exif() {
	return Eazyest_Gallery_Exif::instance();
}
// autostart this plugin
eazyest_gallery_exif();
