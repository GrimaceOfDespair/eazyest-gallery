(function($) {
	$(document).ready(function() {

		$('#menu-posts-galleryfolder').pointer({
	  	content: eazyestUpgraderPointer.content,
			 position: {'edge':'top'},
			 close: function() {
			 	$.post( ajaxurl, {
					pointer: 'eazyest_gallery_upgrader',
					action: 'dismiss-wp-pointer'
				});
			 }
		}).pointer('open');

	});  // $(document).ready();
})(jQuery)
