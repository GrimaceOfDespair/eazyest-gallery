<?php
/**
 * eazyestgallery_theme_mods()
 * Update header image which is stored in _cache directory.
 *
 * @param array $option theme_mods_twentyten
 * @return array
 */
function eazyestgallery_theme_mods( $option ) {
	if ( isset( $option['header_image'] ) ) {
		if ( isset( $option['header_image_data'] ) ) {
			if ( is_object( $option['header_image_data'] ) )
				$option['header_image_data'] = get_object_vars( $option['header_image_data'] );
			if ( ! ezg_is_gallery_image( $option['header_image_data']['attachment_id'] ) )
				return( $option );
			$url = wp_get_attachment_url( $option['header_image_data']['attachment_id'] );
			$attachment = get_post( $option['header_image_data']['attachment_id'], ARRAY_A );
			if ( $url != $attachment['guid'] ) {
				$attachment['guid']         = $url;
				$attachment['post_content'] = $url;
				wp_update_post( $attachment );
			}
			if ( $url != $option['header_image'] )
				$option['header_image'] = $url;
			if ( $url != $option['header_image_data']['url'] )
				$option['header_image_data']['url'] = $url;
			if ( $url != $option['header_image_data']['thumbnail_url'] );
				$option['header_image_data']['thumbnail_url'] = $url;
		}
	}
	return $option;
}
add_filter( 'pre_update_option_theme_mods_twentyten', 'eazyestgallery_theme_mods' );
