jQuery( document ).ready( function( $ ) {

	var mediaUploader;

  	$( '#upload_image_button' ).click( function( e ) {
		
		// Accepts an optional object hash to override default values.
		var frame = new wp.media.view.MediaFrame.Select({
			// Modal title
			title: 'Add Image',

			// Enable/disable multiple select
			multiple: false,

			// Library WordPress query arguments.
			library: {
				order: 'ASC',

				// [ 'name', 'author', 'date', 'title', 'modified', 'uploadedTo',
				// 'id', 'post__in', 'menuOrder' ]
				orderby: 'title',

				// mime type. e.g. 'image', 'image/jpeg'
				type: 'image',

				// Searches the attachment title.
				search: null,

				// Attached to a specific post (ID).
				uploadedTo: null
			},

			button: {
				text: 'Add Image'
			}
		});

		// Fires after the frame markup has been built, but not appended to the DOM.
		// @see wp.media.view.Modal.attach()
		frame.on( 'ready', function() {} );

		// Fires when the frame's $el is appended to its DOM container.
		// @see media.view.Modal.attach()
		frame.on( 'attach', function() {} );

		// Fires when the modal opens (becomes visible).
		// @see media.view.Modal.open()
		frame.on( 'open', function() {} );

		// Fires when the modal closes via the escape key.
		// @see media.view.Modal.close()
		frame.on( 'escape', function() {} );

		// Fires when the modal closes.
		// @see media.view.Modal.close()
		frame.on( 'close', function() {} );

		// Fires when a user has selected attachment(s) and clicked the select button.
		// @see media.view.MediaFrame.Post.mainInsertToolbar()
		frame.on( 'select', function() {
			var selectionCollection = frame.state().get( 'selection') ;
			var attachment = frame.state().get('selection').first().toJSON();
			//console.log( attachment );
			//alert( attachment.id );

			var data = {
				'action': 'hybrid_add_image',
				'image': attachment.id
			};

			// We can also pass the url value separately from ajaxurl for front end AJAX implementations
			$.post(ajax_object.ajax_url, data, function(response) {

				var imgData = JSON.parse(response);

				var icon = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="svg-icon svg-move"><title>move</title><path d="M11 4.414v6.586h-6.586l1.293-1.293c0.391-0.391 0.391-1.024 0-1.414s-1.024-0.391-1.414 0l-3 3c-0.181 0.181-0.293 0.431-0.293 0.707 0 0.136 0.027 0.265 0.076 0.383s0.121 0.228 0.217 0.324l3 3c0.391 0.391 1.024 0.391 1.414 0s0.391-1.024 0-1.414l-1.293-1.293h6.586v6.586l-1.293-1.293c-0.391-0.391-1.024-0.391-1.414 0s-0.391 1.024 0 1.414l3 3c0.096 0.096 0.206 0.168 0.324 0.217s0.247 0.076 0.383 0.076c0.13 0 0.261-0.025 0.383-0.076 0.118-0.049 0.228-0.121 0.324-0.217l3-3c0.391-0.391 0.391-1.024 0-1.414s-1.024-0.391-1.414 0l-1.293 1.293v-6.586h6.586l-1.293 1.293c-0.391 0.391-0.391 1.024 0 1.414s1.024 0.391 1.414 0l3-3c0.096-0.096 0.168-0.206 0.217-0.324 0.15-0.362 0.078-0.795-0.217-1.090l-3-3c-0.391-0.391-1.024-0.391-1.414 0s-0.391 1.024 0 1.414l1.293 1.293h-6.586v-6.586l1.293 1.293c0.391 0.391 1.024 0.391 1.414 0s0.391-1.024 0-1.414l-3-3c-0.092-0.092-0.202-0.166-0.324-0.217-0.245-0.101-0.521-0.101-0.766 0-0.118 0.049-0.228 0.121-0.324 0.217l-3 3c-0.391 0.391-0.391 1.024 0 1.414s1.024 0.391 1.414 0z"></path></svg>';

				var trashIcon = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="22" height="28" viewBox="0 0 22 28" class="svg-icon svg-trash"><title>trash</title><path d="M8 11.5v9c0 0.281-0.219 0.5-0.5 0.5h-1c-0.281 0-0.5-0.219-0.5-0.5v-9c0-0.281 0.219-0.5 0.5-0.5h1c0.281 0 0.5 0.219 0.5 0.5zM12 11.5v9c0 0.281-0.219 0.5-0.5 0.5h-1c-0.281 0-0.5-0.219-0.5-0.5v-9c0-0.281 0.219-0.5 0.5-0.5h1c0.281 0 0.5 0.219 0.5 0.5zM16 11.5v9c0 0.281-0.219 0.5-0.5 0.5h-1c-0.281 0-0.5-0.219-0.5-0.5v-9c0-0.281 0.219-0.5 0.5-0.5h1c0.281 0 0.5 0.219 0.5 0.5zM18 22.813v-14.812h-14v14.812c0 0.75 0.422 1.188 0.5 1.188h13c0.078 0 0.5-0.438 0.5-1.188zM7.5 6h7l-0.75-1.828c-0.047-0.063-0.187-0.156-0.266-0.172h-4.953c-0.094 0.016-0.219 0.109-0.266 0.172zM22 6.5v1c0 0.281-0.219 0.5-0.5 0.5h-1.5v14.812c0 1.719-1.125 3.187-2.5 3.187h-13c-1.375 0-2.5-1.406-2.5-3.125v-14.875h-1.5c-0.281 0-0.5-0.219-0.5-0.5v-1c0-0.281 0.219-0.5 0.5-0.5h4.828l1.094-2.609c0.313-0.766 1.25-1.391 2.078-1.391h5c0.828 0 1.766 0.625 2.078 1.391l1.094 2.609h4.828c0.281 0 0.5 0.219 0.5 0.5z"></path></svg>';

				var count = $( '#sortable li' ).not( '.slideshow-holder' ).length;

				var deleteNonce = '<input type="hidden" id="_wpnonce" name="_wpnonce" value="' + ajax_object.delete_nonce + '">';

				var deleteForm = '<form action="" method="post" class="delete"><input type="hidden" name="submitted" value="true" />' + deleteNonce + '<input type="hidden" name="delete" value="' + count + '" class="hidden delete-img" /><button class="trash">' + trashIcon + '</button></form>';

				var urlNonce = '<input type="hidden" id="_wpnonce" name="_wpnonce" value="' + ajax_object.url_nonce + '">';
				
				var urlForm = '<form action="" method="post" class="url"><input type="text" name="url" value="" class="url" /><input type="hidden" name="submitted" value="true" />' + urlNonce + '<input type="hidden" name="add_url" value="' + count + '" class="add-url" /></form>';

				$( '#sortable' ).append( '<li id="listItem_' + imgData.id + '">' + imgData.img + '<span class="handle ui-sortable-handle">' + icon + '</span>' + urlForm + deleteForm + '</li>' );

				$( '#sortable .slideshow-holder' ).remove();

				$( '#sortable' ).sortable( 'refresh' );
			});

		} );

		// Fires when a state activates.
		frame.on( 'activate', function() {} );

		// Fires when a mode is deactivated on a region.
		frame.on( '{region}:deactivate', function() {} );
		// and a more specific event including the mode.
		frame.on( '{region}:deactivate:{mode}', function() {} );

		// Fires when a region is ready for its view to be created.
		frame.on( '{region}:create', function() {} );
		// and a more specific event including the mode.
		frame.on( '{region}:create:{mode}', function() {} );

		// Fires when a region is ready for its view to be rendered.
		frame.on( '{region}:render', function() {} );
		// and a more specific event including the mode.
		frame.on( '{region}:render:{mode}', function() {} );

		// Fires when a new mode is activated (after it has been rendered) on a region.
		frame.on( '{region}:activate', function() {} );
		// and a more specific event including the mode.
		frame.on( '{region}:activate:{mode}', function() {} );

		// Get an object representing the current state.
		frame.state();

		// Get an object representing the previous state.
		frame.lastState();

		// Open the modal.
		frame.open();
  	});
});