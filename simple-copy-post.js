jQuery( document ).ready( function() {

	jQuery( "#scpjr3-copy-post" ).click( function( event ) {
		event.preventDefault;
		post_id = jQuery( this ).attr( "data-post-id" );
		nonce   = jQuery( this ).attr( "data-nonce" );

		jQuery.ajax({
			type : "post",
			dataType : "json",
			url : scpjr3Ajax.ajaxurl,
			data : { action : "scpjr3_script", post_id : post_id, nonce : nonce },
			success: function( response ) {
				if ( response.type == "success" ) {
					jQuery( "#scpjr3-message" ).append( response.message );
					jQuery( "#scpjr3-message" ).css( { 'backgroundColor' : '#ffffe0', 'border' : '1px solid #e6db55' } );
				} else if ( response.type == "not-published" ) {
					jQuery( "#scpjr3-message" ).append( response.message );
					jQuery( "#scpjr3-message" ).css( { 'backgroundColor' : '#ffebe8', 'border' : '1px solid #c00' } );
				} else {
					jQuery( "#scpjr3-message" ).html( "Error copying Post.  Please save the Post and try again." );
					jQuery( "#scpjr3-message" ).css( { 'backgroundColor' : '#ffebe8', 'border' : '1px solid #c00' } );
				}
				jQuery( "#scpjr3-message" ).fadeIn().delay('5000').fadeOut();;
			},
			error: function( jqXHR, textStatus, errorThrown ) {
				console.log( jqXHR);
				console.log( textStatus );
				console.log( errorThrown );
			}
		});
	});

});