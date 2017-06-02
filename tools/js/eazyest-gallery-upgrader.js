(function($) {

	var eazyestUpgraderRunning = false;
	var eazyestImageCount = 0;

	function eazyestUpgradeFolder( folderCount ) {
		if ( eazyestUpgraderRunning ) {
			var data = {
				action         : 'eazyest_gallery_upgrade_folder',
				gallery_folder : $('#gallery_folder').val(),
				images_max     : $('#import_image_max' ).val(),
				allow_comments : $('input[name=allow_comments]:checked').val(),
				remove_cache   : $('input[name=remove_cache]:checked').val(),
				remove_xml     : $('input[name=remove_xml]:checked').val(),
				_ajax_nonce    : $('#_wpnonce').val()
			}
			$.post( ajaxurl, data, function(response){
				if ( response != '0' ) {
					var newCount = parseInt( response, 10 );
					if( newCount != folderCount ) {
						// start upgrading new folder
						var current = parseInt( $('#current-folder').html() );
						current++;
						$('#current-folder').html(current);
						$('#image-counter').hide();
						eazyestImageCount = 0;
					} else {
						$('#image-counter').show();
						eazyestImageCount = eazyestImageCount + parseInt( data.images_max, 10 );
						$('#image-batch').html(eazyestImageCount);
					}
					eazyestUpgradeFolder( newCount );
				} else {
					$('#folder-counter').html( eazyestUpgraderSettings.ready );
					if ( '1' == $('input[name=convert_page]:checked').val() ){
						eazyestUpgradePage();
					} else {
						eazyestUpdateSettings();
					}
				}
			});
		}
	}

	function eazyestUpgradePage() {
		if ( '1' == $('input[name=convert_page]:checked').val() && eazyestUpgraderRunning ) {
			$('#upgrade_page').show();
			var data = {
				action         : 'eazyest_gallery_convert_page',
				gallery_folder : $('#gallery_folder').val(),
				gallery_id     : $('#gallery_id').val(),
				_ajax_nonce    : $('#_wpnonce').val()
			};
			$.post( ajaxurl, data, function(response){
				eazyestUpdateSettings();
			});
		}
	}

	function eazyestUpdateSettings() {
		if ( eazyestUpgraderRunning ) {
			$('#upgrade-settings').show();
			var data = {
				action         : 'eazyest_gallery_update_settings',
				gallery_folder : $('#gallery_folder').val(),
				_ajax_nonce    : $('#_wpnonce').val()
			};
			$.post( ajaxurl, data, function(response){
				$('#upgrade-cleanup').show();
				eazyestCleanup();
			});
		}
	}

	function eazyestCleanup() {
		if ( eazyestUpgraderRunning ) {
			var data = {
				action         : 'eazyest_gallery_cleanup',
				gallery_folder : $('#gallery_folder').val(),
				_ajax_nonce    : $('#_wpnonce').val()
			};
			$.post( ajaxurl, data, function(response ){
				$('#start-upgrade').hide();
				$('#upgrade-process-title').html( eazyestUpgraderSettings.finished );
				$('#upgrade-success').show();
			});
		}
	}

	function eazyestUpgrader() {
		if ( ! eazyestUpgraderRunning ) {
			$('#skip').hide();
			$('#abort').hide();
			$('#upgrade-process').show();
			$('#upgrade-error').hide();
			$('#start-upgrade').html( eazyestUpgraderSettings.stop );
			$('#start-upgrade').removeClass( 'button-primary' );
			$('#upgrade-form :input').attr( 'disabled', true );
			eazyestUpgraderRunning = true;
		} else {
			$('#skip').show();
			$('#upgrade-process').hide();
			$('#current-folder').html( '0' );
			$('#start-upgrade').html( eazyestUpgraderSettings.restart );
			$('#start-upgrade').addClass( 'button-primary' );
			eazyestUpgraderRunning = false;
		}
		if ( eazyestUpgraderRunning ) {
			var data = {
				action         : 'eazyest_gallery_get_upgrade_folders',
				gallery_folder : $('#gallery_folder').val(),
				_ajax_nonce    : $('#_wpnonce').val()
			}
			$.post( ajaxurl, data, function(response){
				if ( 'empty' ==  response ) {
					$('#upgrade-error').show();
					$('#start-upgrade').html( eazyestUpgraderSettings.restart );
					eazyestUpgraderRunning = false;
				} else {
					var folderCount = parseInt( response, 10 );
					if ( folderCount > 0 ) {
						$('#current-folder').html( '1' );
						$('#all-folders').html(folderCount);
						$('#folder-counter').show();
						$('span.spinner').show();
						eazyestUpgradeFolder( folderCount );
					}
				}
			});
		}
	}

	$(document).ready(function() {

		$('#start-upgrade').click(function(){
			eazyestUpgrader();
			return false;
		});

		$('#skip_upgrade').click(function(){
			$(this).parents('form').submit();
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
						$('#eazyest-ajax-response').html(eazyestUpgraderSettings.errorMessage.replace('%s', '<code>'+$('#gallery_folder').val()+'</code>')).show('fast', function(){
							$('#gallery_folder').val(response.folder);
						});
					} else {
						// file does not exist
						$('#eazyest-ajax-response').html(eazyestUpgraderSettings.notExistsMessage).show('fast', function(){
							$('#gallery_folder').val(response.folder);
						});
					}
				}
			});
			return false;
		});

	}); // $(document).ready();

})(jQuery)
