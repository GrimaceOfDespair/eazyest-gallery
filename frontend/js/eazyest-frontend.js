(function($){

	$(window).load( function(){
		// make all gallery items same height for responsive gallery
		if ( $('.gallery-columns-0').length ) {
			var maxHeight = 0;
			$('.gallery-columns-0').children('dl').each( function(){
				if ( $(this).outerHeight() > maxHeight )
					maxHeight = $(this).outerHeight();
			});

			$('.gallery-columns-0').children('dl').each( function(){
				$(this).css('height',maxHeight);
			});
		}

	}); // window.load

	function eazyestMoreButton() {
		if ( $('nav.thumbnail-navigation').length ) {
			$('nav.thumbnail-navigation .nav-previous').remove();
			$('nav.thumbnail-navigation .nav-next').removeClass('nav-next alignright').addClass('nav-more alignleft');
			var attribs = $('nav.thumbnail-navigation .nav-more a').attr('class').split('-');
			$('nav.thumbnail-navigation .nav-more a').addClass('button').html( eazyestFrontend.moreButton );
			$('nav.thumbnail-navigation .nav-more').on( 'click', 'a', function() {
				$(this).html( eazyestFrontend.moreButton + '&hellip;' );
				thumbsPage = $(this).attr('id').substr(15);
				var data = {
					action  : 'eazyest_gallery_more_thumbnails',
					page    : thumbsPage,
					columns : attribs[1],
					posts   : attribs[2],
					folder  : $(this).closest('nav.thumbnail-navigation').attr('id').substr(14)
				};
				$.post( eazyestFrontend.ajaxurl, data, function(response){
					$('nav.thumbnail-navigation').replaceWith(response);
					eazyestMoreButton();
				})
				return false;
			});
		}
	}

	function eazyestFolderButton() {
		if ( $('nav.folder-navigation').length ) {
			$('nav.folder-navigation .nav-previous').remove();
			$('nav.folder-navigation .nav-next').removeClass('nav-next alignright').addClass('nav-more alignleft');
			$('nav.folder-navigation .nav-more a').addClass('button').html( eazyestFrontend.moreFolders );
			$('nav.folder-navigation .nav-more').on( 'click', 'a', function() {
				$(this).html( eazyestFrontend.moreFolders + '&hellip;' );
				foldersPage = $(this).attr('id').substr(12);
				var data = {
					action : 'eazyest_gallery_more_folders',
					page   : foldersPage,
					folder : $(this).closest('nav.folder-navigation').attr('id').substr(11)
				};
				$.post( eazyestFrontend.ajaxurl, data, function(response){
					$('nav.folder-navigation').replaceWith(response);
					eazyestFolderButton();
				})
				return false;
			});
		}
	}

	$(document).ready(function() {
		eazyestMoreButton();
		eazyestFolderButton()
	});

})(jQuery)
