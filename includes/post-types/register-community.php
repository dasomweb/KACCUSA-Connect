<?php
/**
 * Register Community Custom Post Type
 *
 * @package KACCUSA-Connect
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function () {
	register_post_type( 'dw_community', [
		'labels'       => [
			'name'               => 'Community',
			'singular_name'      => 'Community',
			'add_new'            => 'Add New',
			'add_new_item'       => 'Add New Community Post',
			'edit_item'          => 'Edit Community Post',
			'view_item'          => 'View Community Post',
			'search_items'       => 'Search Community',
			'not_found'          => 'No community posts found',
			'not_found_in_trash' => 'No community posts found in Trash',
		],
		'public'       => true,
		'has_archive'  => true,
		'rewrite'      => [ 'slug' => 'community' ],
		'supports'     => [ 'title', 'editor', 'thumbnail', 'custom-fields', 'excerpt', 'comments' ],
		'show_in_rest' => true,
		'menu_icon'    => 'dashicons-groups',
	] );
} );

add_action( 'add_meta_boxes', function () {
	add_meta_box(
		'dw_community_details',
		'Community Details',
		'dw_community_meta_box_callback',
		'dw_community'
	);
} );

function dw_community_meta_box_callback( $post ) {
	wp_nonce_field( 'dw_community_save', 'dw_community_nonce' );

	$region     = get_post_meta( $post->ID, '_dw_region', true );
	$event_date = get_post_meta( $post->ID, '_dw_event_date', true );
	$location   = get_post_meta( $post->ID, '_dw_location', true );
	$contact    = get_post_meta( $post->ID, '_dw_contact_info', true );

	?>
	<table class="form-table">
		<tr>
			<th><label for="dw_region">Region</label></th>
			<td><input type="text" id="dw_region" name="dw_region" value="<?php echo esc_attr( $region ); ?>" class="regular-text"></td>
		</tr>
		<tr>
			<th><label for="dw_event_date">Event Date</label></th>
			<td><input type="date" id="dw_event_date" name="dw_event_date" value="<?php echo esc_attr( $event_date ); ?>"></td>
		</tr>
		<tr>
			<th><label for="dw_location">Location</label></th>
			<td><input type="text" id="dw_location" name="dw_location" value="<?php echo esc_attr( $location ); ?>" class="regular-text"></td>
		</tr>
		<tr>
			<th><label for="dw_contact_info">Contact Info</label></th>
			<td><input type="text" id="dw_contact_info" name="dw_contact_info" value="<?php echo esc_attr( $contact ); ?>" class="regular-text"></td>
		</tr>
	</table>
	<?php
}

add_action( 'save_post_dw_community', function ( $post_id ) {
	if ( ! isset( $_POST['dw_community_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( $_POST['dw_community_nonce'], 'dw_community_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	update_post_meta( $post_id, '_dw_region', sanitize_text_field( $_POST['dw_region'] ?? '' ) );
	update_post_meta( $post_id, '_dw_event_date', sanitize_text_field( $_POST['dw_event_date'] ?? '' ) );
	update_post_meta( $post_id, '_dw_location', sanitize_text_field( $_POST['dw_location'] ?? '' ) );
	update_post_meta( $post_id, '_dw_contact_info', sanitize_text_field( $_POST['dw_contact_info'] ?? '' ) );
} );
