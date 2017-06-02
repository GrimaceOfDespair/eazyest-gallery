(function($){
	function repeatSlides() {
		setInterval( function(){
			$('.eazyest-ajax-slideshow').each( function() {
				var ID = $(this).attr('id');
				var nonce_ID = ID.replace( 'eazyest-ajax-slideshow-', '#eazyest-ajax-nonce-' );
				var data = {
					action   : 'eazyest_gallery_next_slideshow',
					_wpnonce : $(nonce_ID).val(),
					show_id  : ID
				};
				$.post( eazyestSlideshowSettings.ajaxurl, data, function(response){
					if ( 0 != response) {
						$( '#' + ID + ' .gallery-item.bottom dt.gallery-icon').html( response );
						$( '#' + ID + ' .gallery-item.bottom').animate({opacity: 1.0},500,function(){
							$(this).addClass('top').removeClass('bottom')
						});
						$( '#' + ID + ' .gallery-item.top').animate({opacity: 0.0},500,function(){
							$(this).addClass('bottom').removeClass('top')
						});
					}
				});
			});
		}, eazyestSlideshowSettings.timeOut );
	}

	$(document).ready( function(){
		$('.eazyest-ajax-slideshow').each(function(){
			$(this).children('.top').css({opacity: 1.0});
			$(this).children('.bottom').css({opacity: 0.0});
		});
		repeatSlides();
	});
})(jQuery)
