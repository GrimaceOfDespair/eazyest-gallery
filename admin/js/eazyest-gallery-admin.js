(function($) {

	$(document).ready(function(){

		// table drag and drop handlers
		function doDragTable( dTable ) {
			$( 'td.galleryfolder_drag' ).addClass( 'drag-handle' );
			$( 'td.media_drag' ).addClass( 'drag-handle' );

			$( '.column-galleryfolder_drag, .column-media_drag' ).show();

			$( dTable ).tableDnD({
				onDragClass : 'dragging',
				dragHandle  : '.drag-handle',
				onDrop      : function( table, row ) {
					var dropStr = '';
					var i = 0;
					$( table ).find( 'input.drag-id' ).each( function() {
						if ( i % 2 )
							$( this ).closest( 'tr' ).addClass( 'alternate' );
						else
							$( this ).closest( 'tr' ).removeClass( 'alternate' );
						dropStr += $( this ).val() + ' ';
						i++;
					});
					$( '#save-order' ).removeAttr( 'disabled' );
					dropStr = dropStr.replace(/(^\s*)|(\s*$)/g, '');
					if ( $( table ).hasClass( 'pages' ) ) {
						$( '#gallery-order-pages' ).val( dropStr );
						$( '#gallery-changed-pages').val( '1' );
					}
					else {
						$( '#gallery-order-media' ).val( dropStr );
						$( '#gallery-changed-media').val( '1' );
					}
				}
			});	// $( dTable ).tableDnD
		}

		if ( $( '.column-galleryfolder_drag' ).length ) {
			doDragTable( '.wp-list-table.pages' );
		}

		if ( $( '.column-media_drag' ).length ) {
			doDragTable( '.wp-list-table.media' );
		}

		if ( $('.wp-editor-area').length ) {
			var media = wp.media.editor.add('content');
			// refresh media table when media editor closes
			media.on( 'close', function() {
				var data = {
					action : 'eazyest_gallery_upload',
					_wpnonce : $( '#_wpnonce' ).val(),
					post     : $( '#post_ID' ).val(),
				};
				$.post( ajaxurl, data, function( response ){
					$( '.attached-images' ).html( response );
					doDragTable( '.wp-list-table.media' );
				});
				return false;
			});
		}

		// handle changes in post status including 'hidden'
		$('.save-post-visibility', '#post-visibility-select').click(function () { // crazyhorse - multiple ok cancels
			var pvSelect = $('#post-visibility-select');
			pvSelect.slideUp('fast');
			$('.edit-visibility', '#visibility').show();
			if ( $('input:radio:checked', '#post-visibility-select').val() == 'hidden' ) {
				$('#post-visibility-display').html(galleryfolderL10n.hidden );
				$('#post-status-display').html(galleryfolderL10n.hiddenpublish );
			}
			return false;
		});

	}); // $(document).ready


})(jQuery)
