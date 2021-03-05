jQuery( document ).ready( function( $ ) {

	// Delete slide
	$( '#sortable'  ).on( 'click', '.trash', function( e ) {
		e.preventDefault();

		var imgId = $( this ).parent().parent().index();

		var data = {
			action: 'hybrid_delete_action',
			id: imgId,
			nonce: ajax_object.delete_nonce
		};

		jQuery.post( ajax_object.ajax_url, data, function( response ) {
			if ( response == 1 ) {
				$( '#sortable li' ).eq( imgId ).remove();
			}
		});
	} );

	// Update slide link
	$( '#sortable' ).on( 'click', '.url-btn', function( e ) {
		e.preventDefault();

		var imgId = $( this ).parent().parent().index();
		var url = $( this ).parent().children( '.url' ).val();

		var data = {
			action: 'hybrid_url_action',
			id: imgId,
			nonce: ajax_object.url_nonce, 
			url: url
		};

		jQuery.post( ajax_object.ajax_url, data, function( response ) {
			//console.log(response);
		});
	} );

} );