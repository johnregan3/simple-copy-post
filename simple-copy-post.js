jQuery(document).ready( function() {

	jQuery("#scpjr3-copy-post").click( function(event) {
		event.preventDefault;
		post_id = jQuery(this).attr("data-post-id");
		nonce = jQuery(this).attr("data-nonce");

		jQuery.ajax({
			type : "post",
			dataType : "json",
			url : cpjr3Ajax.ajaxurl,
			data : {action: "scpjr3_script", post_id : post_id, nonce: nonce},
			success: function(response) {
				if(response.type == "success") {
					jQuery("#scpjr3-success-message").html(response.message)
				} else if(response.type == "not-published") {
					jQuery("#scpjr3-error-message").html(response.message);
				} else {
					jQuery("#scpjr3-error-message").html("Error copying Post.  Please save the Post and try again.");
				}
			}
		});

	});

});