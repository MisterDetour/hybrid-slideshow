jQuery( document ).ready( function( $ ) {

	// Initiate sortable slide table
	jQuery( "#sortable" ).sortable( {
		handle : '.handle', 
		update: function( event, ui ) {
			// Update slideshow order in DB
			var order = $( '#sortable' ).sortable( 'toArray' );

			var urls = $( '#sortable li input.url' ).map( function () {
				return this.value; 
			} ).get();

			// Strip strings from list item ids
			for ( var i = 0; i < order.length; i++ ) {
				order[ i ] = [ order[ i ].replace( 'listItem_', '' ), urls[ i ]  ];
			}

			var data = {
				action: 'hybrid_special_action',
				order: order
			};

			jQuery.post( ajaxurl, data );
		}
	} );

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

	// Update slide link on change
	$( '#sortable' ).on( 'input', 'input.url', function( e ) {
		e.preventDefault();

		var imgId = $( this ).parent().parent().index();
		var url = $( this ).val();

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