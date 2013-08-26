jQuery( document ).ready( function() {

	jQuery( "#scpjr3-copy-post" ).click( function( event ) {
		event.preventDefault;
		var post_id = jQuery( this ).attr( "data-post-id" );
		var nonce   = jQuery( this ).attr( "data-nonce" );
		var msg     = jQuery( "#scpjr3-message" )

		jQuery.ajax({
			type : "post",
			dataType : "json",
			url : scpjr3Ajax.ajaxurl,
			data : { action : "scpjr3_script", post_id : post_id, nonce : nonce },
			success: function( response ) {
				if ( response.type == "success" ) {
					msg.append( response.message );
					msg.css( { 'backgroundColor' : '#ffffe0', 'border' : '1px solid #e6db55' } );
				} else if ( response.type == "not-published" ) {
					msg.append( response.message );
					msg.css( { 'backgroundColor' : '#ffebe8', 'border' : '1px solid #c00' } );
				} else {
					msg.html( "Error copying Post.  Please save the Post and try again." );
					msg.css( { 'backgroundColor' : '#ffebe8', 'border' : '1px solid #c00' } );
				}
				msg.fadeIn().delay('5000').fadeOut();;
			}
		});
	});

});