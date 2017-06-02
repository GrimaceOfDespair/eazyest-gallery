(function($) {

	function thumbs_columnsChange() {
		var columnsVal = parseInt( $('#thumbs_columns').val(), 10 );
		var pageVal = parseInt( $('#thumbs_page').val(), 10 );

		if ( pageVal == 0 || columnsVal == 0 )
			return;

		$('#thumbs_page').attr( 'step', columnsVal );
		if ( pageVal > 0 ) {
			lowVal  = pageVal - pageVal%columnsVal;
			highVal = lowVal + columnsVal;
			if ( ( pageVal - lowVal ) > ( highVal - pageVal ) )
				pageVal = highVal;
			else
				pageVal = lowVal;
			$('#thumbs_page').val( pageVal );
		}
	}

	function folder_columnsChange() {
		var columnsVal = parseInt( $('#folders_columns').val(), 10 );
		var pageVal = parseInt( $('#folders_page').val(), 10 );

		if ( pageVal == 0 || columnsVal == 0 )
			return;

		$('#folders_page').attr( 'step', columnsVal );
		if ( pageVal > 0 ) {
			lowVal  = pageVal - pageVal%columnsVal;
			highVal = lowVal + columnsVal;
			if ( ( pageVal - lowVal ) > ( highVal - pageVal ) )
				pageVal = highVal;
			else
				pageVal = lowVal;
			$('#folders_page').val( pageVal );
		}
	}

	$(document).ready(function(){

		// filetree dropdown ---------------------------------------------------------
		$('#folder-select').click(function(){
				if ($(this).hasClass('open') ) {
					if ( ! $(this).hasClass('loaded') ) {
						$('#file-tree').fileTree({
							root        : fileTreeSettings.root,
							script      : fileTreeSettings.script,
							loadMessage : fileTreeSettings.loadMessage
						}, function(directory){
							var data = {
								action : 'eazyest_gallery_select_dir',
								_wpnonce : $('#file-tree-nonce').val(),
								dir    : directory
							}
							$.post( fileTreeSettings.script, data, function(response){
								if ( '!' != response )
								$('#gallery_folder').val(response);
							});
						})
						$('#file-tree').show();
						$(this).removeClass('open');
						$(this).addClass('loaded');
					} else {
						$('#file-tree').show();
						$(this).removeClass('open');
					}
			} else {
				$('#file-tree').hide();
				$(this).addClass('open');
			}
			return false;
		});

		// close filetree dropdown on click
		$('#wpcontent').click(function(){
			$('#file-tree').hide();
			$('#folder-select').addClass('open');
		});

		// gallery_folder changed, check if gallery folder is ok
		$('#gallery_folder').change( function(){
			var data = {
				action         : 'eazyest_gallery_folder_change',
				_wpnonce       : $('#gallery-folder-nonce').val(),
				gallery_folder : $('#gallery_folder').val()
			};
			$.post( ajaxurl, data, function(response){
				if ( 0 == response.result ) {
					$('#eazyest-ajax-response').hide();
					$('#create-folder').hide();
				} else {
					if ( 1 == response.result ) {
						// folder on a dangerous path, restore value from settings
						$('#eazyest-ajax-response').html(fileTreeSettings.errorMessage.replace('%s', '<code>'+$('#gallery_folder').val()+'</code>')).show('fast', function(){
							$('#gallery_folder').val(response.folder);
						});
					} else {
						// file does not exist
						$('#eazyest-ajax-response').html(fileTreeSettings.notExistsMessage).show('fast', function(){
							$('#create-folder').show();
						});
					}
				}
			});
			return false;
		});

		// create-folder button clicked
		$('#create-folder').click(function(){
			var data = {
				action : 'eazyest_gallery_create_folder',
				_wpnonce : $('#gallery-folder-nonce').val(),
				gallery_folder : $('#gallery_folder').val()
			};
			$.post(ajaxurl, data, function(response){
				if ( 0 == response.result ) {
					$('#eazyest-ajax-response').hide();
					$('#create-folder').hide();
				} else {
					// could not create folder, restore value from settings
					$('#eazyest-ajax-response').html(fileTreeSettings.notCreateMessage.replace('%s', '<code>'+$('#gallery_folder').val()+'</code>')).show('fast', function(){
						$('#gallery_folder').val(response.folder);
					});
				}
			});
			return false;
		});

		// folder image select -------------------------------------------------------
		function showRandomSubFolder() {
			if ( $('#folder_image').val() == 'random_image' )
				$('#random-subfolder').css('visibility','visible');
			else
				$('#random-subfolder').css('visibility','hidden');
		}

		showRandomSubFolder();

		$('#folder_image').change( function(){
			showRandomSubFolder();
		});

		// thumbnail popup select ----------------------------------------------------
		function showThumbnailPopup( aField, aPopup ) {
			var options = new Array('medium', 'large', 'full');
			var value = $(aField).val();
			if ( 0 <= options.indexOf(value) )
				$(aPopup).css('visibility','visible');
			else
				$(aPopup).css('visibility','hidden');
		}

		showThumbnailPopup( '#on_thumb_click', '#thumb-popup' );
		showThumbnailPopup( '#on_slide_click', '#slide-popup' );

		$('#on_thumb_click').change( function(){
			showThumbnailPopup( '#on_thumb_click', '#thumb-popup' );
		});
		$('#on_slide_click').change( function(){
			showThumbnailPopup( '#on_slide_click', '#slide-popup' );
		});

		// folders_columns change
		$('#folders_columns').change(function(){
			folder_columnsChange()
		});
		$('#folders_page').change(function(){
			folder_columnsChange()
		});

		// thumbs_columns change
		$('#thumbs_columns').change(function(){
			thumbs_columnsChange()
		});
		$('#thumbs_page').change(function(){
			thumbs_columnsChange()
		});

  }); // (document).ready

})(jQuery)
