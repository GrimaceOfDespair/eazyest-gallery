(function($) {

	function eazyest_gallery_collect_finished(response) {
		if ( 0 == response ) {
			$('#eazyest-collect-folders').removeClass('collect-folders').html(eazyestGalleryCollect.notfound).wrap( '<div class="updated"/>' ).attr( 'title', '' );
		} else {
			var totalNew = 0;
			var totalDel = 0;
			$.each( response, function(key, folder){
				tdImages = 'tr#post-' + folder.id + ' td.galleryfolder_images';
				var count = parseInt( $(tdImages).html(), 10 ) + folder.images['add'] - folder.images['delete'];
				$(tdImages).html(count);
				totalNew +=  folder.images['add'];
				totalDel +=  folder.images['delete'];
			});
			$('#eazyest-collect-folders').removeClass('collect-folders').html('').wrap( '<div class="updated"/>' ).attr( 'title', '' );
			if ( 0 < totalNew )
				$('#eazyest-collect-folders').append( eazyestGalleryCollect.foundimages.replace('%d', totalNew ) );
			if ( 0 < totalDel )	{
				if ( 0 < totalNew )
					$('#eazyest-collect-folders').append( '<br />' );
				$('#eazyest-collect-folders').append( eazyestGalleryCollect.missedimages.replace('%d', totalDel ) );
			}
			if ( $('table.wp-list-table.media').length ) {
				if ( 0 < totalDel || 0 < totalNew )
					$('#eazyest-collect-folders').append( '<br />' );
				$('#eazyest-collect-folders').append( eazyestGalleryCollect.refresh );
			}
		}
	}

	function eazyest_gallery_collect_error( response ) {
		var errorString = eazyestGalleryCollect.error1 + '<br />' + eazyestGalleryCollect.error2 + ' <strong>' + response + '</strong>';
		$('#eazyest-collect-folders').removeClass('collect-folders').html(errorString).wrap( '<div class="error"/>' );
	}

	function eazyest_gallery_collect_next(nextFolder) {
	 	dataFolder = {
	 		action     : 'eazyest_gallery_collect_folders',
	 		subaction  : nextFolder,
	 		_wpnonce   : eazyestGalleryCollect._wpnonce
	 	}
	 	$.ajax(
			{
				type : 'POST',
		 	 	url  : ajaxurl,
				data :	dataFolder,
				success: function(response) {
			 		if ( 'next' == response )
			 			eazyest_gallery_collect_next(response);
			 		else if ( typeof response === 'object' || 0 == response ) {
			 			eazyest_gallery_collect_finished(response);
				 	}	else 	{
				 		var trimmed = $.trim( response );
				 		if ( 'next' == trimmed ) {
				 			eazyest_gallery_collect_next(trimmed);
						} else {
							eazyest_gallery_collect_error( response );
						}
					}
				},
				error : function ( xhr, textStatus, errorThrown){
					if ( 500 == xhr.status ) {
						eazyest_gallery_collect_error( eazyestGalleryCollect.error500 + ' - ' + errorThrown );
					}
				}
			});
	}

	$(document).ready(function(){
		$( eazyestGalleryCollect.collecting ).insertBefore('#posts-filter table.wp-list-table');
		$('#eazyest-collect-folders').show('fast',function(){
	 		eazyest_gallery_collect_next('start');
	 	}).click(function(){
			$(this).css('color','red');
			eazyest_gallery_collect_next('stop');
		});
	}); // $(document).load
})(jQuery)
