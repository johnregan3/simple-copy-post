<?php
/**
 * Plugin Name: Simple Copy Post
 * Plugin URI: http://johnregan3.github.io/simple-copy-post
 * Description: Simple WordPress Plugin that copies/duplicates Posts, Pages, and Custom Post Types with just one click.
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
 * @package Simple Copy Post
 * @author John Regan
 * @version 1.0
 *
 * @todo Add Bulk Action support
 */


/**
 * Register text domain
 *
 * @since 1.0
 */
add_action( 'init', 'scpjr3_textdomain' );

function scpjr3_textdomain() {
	load_plugin_textdomain( 'scpjr3' );
}


/**
 * Enqueue Script
 *
 * @since 1.0
 */
add_action( 'admin_enqueue_scripts', 'scpjr3_enqueue_script' );

function scpjr3_enqueue_script( ) {
	wp_register_script( 'scpjr3-script', plugins_url( 'simple-copy-post.js', __FILE__ ) );
	wp_localize_script( 'scpjr3-script', 'scpjr3Ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
	wp_enqueue_script( 'scpjr3-script' );
}


/**
 * Add "Copy" link to Table Row Actions
 *
 * @since 1.0
 * @param  array  $actions  Default Row Actions
 * @return array  $actions  Modified Row Actions
 */
add_filter( 'post_row_actions', 'scpjr3_row_actions', 10, 2 );

function scpjr3_row_actions( $actions, $post ) {
	// Create Nonce
	$scpjr3_nonce = wp_create_nonce( 'scpjr3_nonce' );
	$link = '<a href="' . admin_url( "admin-ajax.php?action=scpjr3_script&nonce=" . $scpjr3_nonce . "&post_id=" . $post->ID ) . '" >' . __( 'Copy', 'scpjr3' ) . '</a>';
	$actions = array_slice( $actions, 0, 2, true ) +
		array( 'scjr3_copy' => $link ) +
		array_slice( $actions, 2, NULL, true );
	return $actions;
}


/**
 * Register Submit Box Field
 *
 * @since 1.0
 */
add_action( 'post_submitbox_misc_actions', 'scpjr3_button' );

function scpjr3_button() {
	global $post;
	$post_id = $post->ID;
	$post_type = get_post_type( $post_id );

	// Create Nonce
	$scpjr3_nonce = wp_create_nonce( 'scpjr3_nonce' );

	// Render Content of the Meta Box
	echo '</div><div class="misc-pub-section" style="text-align: right;">'; //Yes, this is here on purpose.
	echo '<div id="scpjr3-message" style="display: none; margin-bottom: 5px; padding: 5px 10px; text-align: left;"></div>';
	echo '<a href="#" id="scpjr3-copy-post" class="button-secondary" data-nonce="' . $scpjr3_nonce . '" data-post-id="' . $post_id . '">' . __( 'Copy this ', 'scpjr3' ) . $post_type . '</a>';
}


/**
 * Copy Post Action
 *
 * Verifies nonce, checks for capabilities, then copies content from
 * original post ($old_post) into a new post object.  Then, it inserts the post, and
 * if it's a page, it copies the Page Template.  Finally, if this
 * is an ajax request, a response is returned. If not, the page is refreshed.
 *
 * @since 1.0
 */
add_action( 'wp_ajax_scpjr3_script', 'scpjr3_action' );

function scpjr3_action() {

	// Check if this is a GET or POST request (Table List vs Edit Post view)
	if ( isset( $_GET['nonce'] ) && isset( $_GET['post_id'] ) ) {
		$nonce = $_GET['nonce'];
		$post_id = $_GET['post_id'];
	} else {
		$nonce = $_POST['nonce'];
		$post_id = $_POST['post_id'];
	}

	// Verify Nonce
	if ( ! isset( $nonce ) || ! wp_verify_nonce( $nonce, 'scpjr3_nonce' ) )
		return;

	// Check Capabilities
	if ( ! current_user_can( 'edit_post', $post_id ) || ! current_user_can( 'edit_page', $post_id ) )
		return;

	// Get the Old Post
	$old_post = get_post( $post_id );

	/**
	 * If Old Post is unpublished, exit.
	 *
	 * This only applies to copies made from the "Edit Post" screen.
	 * If copied before published, the Post tile says "Autosave"
	 * This doesn't happen when posts are copied from the Table List ("All Posts") view.
	 */
	if ( ! ( "publish" == $old_post->post_status || "draft" == $old_post->post_status ) ) {
		$response['type'] = 'not-published';
		$response['message'] = __( 'Copies can only be made of Published Posts.', 'scpjr3' );
		echo json_encode( $response );
		die();
	}

	/**
	 * Copy Post Object Values
	 *
	 * Notice times are not copied over to new post to avoid conflicts
	 * Also, the post_status is draft, so users can make adjustments before
	 * publishing.
	 */
	$new_post = array(
		'post_status'    => 'draft',
		'menu_order'     => $old_post->menu_order,
		'post_type'      => $old_post->post_type,
		'comment_status' => $old_post->comment_status,
		'ping_status'    => $old_post->ping_status,
		'pinged'         => $old_post->pinged,
		'post_author'    => $old_post->post_author,
		'post_category'  => $old_post->post_category,
		'post_content'   => $old_post->post_content,
		'post_excerpt'   => $old_post->post_excerpt,
		'post_name'      => $old_post->post_name,
		'post_parent'    => $old_post->post_parent,
		'post_password'  => $old_post->post_password,
		'post_title'     => $old_post->post_title . ' (' . __( 'copy', 'scpjr3' ) . ')',
		'post_type'      => $old_post->post_type,
		'tags_input'     => $old_post->tags_input,
		'to_ping'        => $old_post->to_ping,
		'tax_input'      => $old_post->tax_input,
	);

	// Create new Post
	$new_post_id = wp_insert_post( $new_post );

	// Copy Page Template if applicable
	if ( $new_post_id && ( 'page' == $old_post->post_type ) ) {
		$page_template = get_post_meta( $post_id, '_wp_page_template' );
		update_post_meta( $new_post_id, '_wp_page_template', $page_template );
	}

	// Prepare Response
	if ( $new_post_id ) {
		$response['type'] = 'success';
		$response['message'] = __( 'Post successfully copied.', 'scpjr3' );
	} else {
		$response['type'] = 'error';
		$response['message'] = __( 'There was an error copying this post.', 'scpjr3' );
	}

	// If this is being processed with Ajax, return the response
	if ( ! empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest' ) {
		echo json_encode( $response );
		die();
	// If not, redirect to the same page.
	} else {
		header( "Location: ".$_SERVER["HTTP_REFERER"] );
	}

} ?>
