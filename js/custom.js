jQuery( document ).ready( function( $ ) {

	$( '.trash' ).on( 'click', function( e ) {
		e.preventDefault();

		var imgId = $( this ).parent().parent().index();

		var data = {
			action: 'hybrid_delete_action',
			id: imgId,
			nonce: ajax_object.delete_nonce
		};

		jQuery.post( ajax_object.ajax_url, data, function( response ) {
			// TODO: need to check nonce! https://wordpress.stackexchange.com/questions/231797/what-is-nonce-and-how-to-use-it-with-ajax-in-wordpress
			$( '#sortable li' ).eq( imgId ).remove();
		});
	} );

} );