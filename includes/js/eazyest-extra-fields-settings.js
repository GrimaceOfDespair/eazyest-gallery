(function($){

	$(document).ready( function(){

		$('a.delete-cross').click( function(){
			$(this).closest('tr').remove();
			return false;
		});

	});

})(jQuery)
