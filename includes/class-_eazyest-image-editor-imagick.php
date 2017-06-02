<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * _Eazyest_Image_Editor
 * Pseudo class to have the Eazyest_Image_editor class extend WP_Image_Editor_Imagick.
 *
 * @package Eazyest Gallery
 * @subpackage Image Editor
 * @author Marcel Brinkkemper
 * @copyright 2012
 * @since 0.1.0 (r36)
 * @version 0.1.0 (r36)
 * @access public
 */
function lazyest_parent_class() {
	return 'WP_Image_Editor_Imagick';
}
class _Eazyest_Image_Editor extends WP_Image_Editor_Imagick {}
