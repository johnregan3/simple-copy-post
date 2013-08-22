<?php
/**
 * Plugin Name: Simple Copy Post
 * Plugin URI: http://johnregan3.github.io/simple-copy-post
 * Description: Simple, lightweight WordPress Plugin that copies/duplicates Pages, Posts and Custom Post Type Posts.
 * Author: John Regan
 * Author URI: http://johnregan3.me
 * Version: 1.0
 * Text Domain: scpjr3
 *
 * Copyright 2013  John Regan  (email : johnregan3@outlook.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @package Simple Copy Posts
 * @author John Regan
 * @version 1.0
 */

/**
 * Register text domain
 *
 * @since 1.0
 */
function scpjr3_textdomain() {
	load_plugin_textdomain('scpjr3');
}

add_action('init', 'scpjr3_textdomain');


/**
 * Enqueue Script
 *
 * @since 1.0
 */
add_action( 'admin_enqueue_scripts', 'scpjr3_scripts' );

function scpjr3_scripts( ) {
	wp_register_script( 'scpjr3-script', plugins_url( 'simple-copy-post.js', __FILE__) );
	wp_localize_script( 'scpjr3-script', 'scpjr3Ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
	wp_enqueue_script( 'scpjr3-script' );
}


/**
 * Register Meta Box
 *
 * @since 1.0
 */

add_action( 'post_submitbox_misc_actions', 'scpjr3_content' );

/**
 * Field Content
 *
 * @since 1.0
 */
function scpjr3_content() {
	global $post;

	// Create Nonce
	$scpjr3_nonce = wp_create_nonce( 'scpjr3_nonce' );

	// Render Content of the Meta Box
	echo '</div><div class="misc-pub-section">'; //Yes, this is here on purpose.
	echo '<div id="#scpjr3-success-message"></div>';
	echo '<div id="#scpjr3-error-message"></div>';
	echo '<p class="description">' . __( 'Create a duplicate of this Post or Page', 'scpjr3' ) . '</p>';
	echo '<button id="scpjr3-copy-post" name="scpjr3_copy_post" value="scpjr3-copy-post" class="button-secondary" data-nonce="' . $scpjr3_nonce . '" data-post-id="' . $post->ID . '">' . __( 'Copy', 'scpjr3' ) . '</button>';
}


add_action( 'wp_ajax_mcpjr3_script', 'scpjr3_action' );

function scpjr3_action() {

	$post_id = $_POST['post_id'];

	if ( ! $post_id ) {
		$response['type'] = 'not-published';
		$response['message'] = 'Post not copied.  Source Post must be Published to be duplicated.';
		echo json_encode( $response );
		exit();
	}


	// Check Capabilities
	if ( 'page' == $_REQUEST['post_type'] ) {
		if ( ! current_user_can( 'edit_page', $post_id ) )
			return;
	} else {
		if ( ! current_user_can( 'edit_post', $post_id ) )
			return;
	}

	// Nonce Check
	if ( ! isset( $_POST['scpjr3_nonce'] ) || ! wp_verify_nonce( $_POST['scpjr3_nonce'], plugin_basename( __FILE__ ) ) )
		return;

	// Get the Old Post
	$old_post = get_post( $post_id );

	// Copy Post Object Values
	$new_post = array(
		'post_status'    => 'draft',
		'menu_order'     => $old_post['menu_order'],
		'post_type'      => $old_post['post_type'],
		'comment_status' => $old_post['comment_status'],
		'ping_status'    => $old_post['ping_status'],
		'pinged'         => $old_post['pinged'],
		'post_author'    => $old_post['post_author'],
		'post_category'  => $old_post['post_category'],
		'post_content'   => $old_post['post_content'],
		'post_excerpt'   => $old_post['post_excerpt'],
		'post_name'      => $old_post['post_name'],
		'post_parent'    => $old_post['post_parent'],
		'post_password'  => $old_post['post_password'],
		'post_title'     => $old_post['post_title'],
		'post_type'      => $old_post['post_type'],
		'tags_input'     => $old_post['tags_input'],
		'to_ping'        => $old_post['to_ping'],
		'tax_input'      => $old_post['tax_input'],
	);

	// Create new Post
	$new_post_id = wp_insert_post( $new_post );

	// Copy Page Template if necessary
	if ( $new_post_id && ('page' == $old_post['post_type'] ) ) {
		$page_template = get_post_meta( $post_id, '_wp_page_template' );
		update_post_meta( $new_post_id, '_wp_page_template', $page_template );
	}

	// Prepare Response
	if( $new_post_id ) {
		$response['type'] = 'success';
		$response['message'] = 'Post successfully copied.';
	}

	// Send Response
	if ( $response ) {
		echo json_encode( $response );
	}

	exit();

}
?>
