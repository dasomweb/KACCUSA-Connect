<?php
/**
 * Register Portfolio Custom Post Type
 *
 * @package KACCUSA-Connect
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function () {
	register_post_type( 'dw_portfolio', [
		'labels'       => [
			'name'               => 'Portfolio',
			'singular_name'      => 'Portfolio',
			'add_new'            => 'Add New',
			'add_new_item'       => 'Add New Portfolio',
			'edit_item'          => 'Edit Portfolio',
			'view_item'          => 'View Portfolio',
			'search_items'       => 'Search Portfolio',
			'not_found'          => 'No portfolio items found',
			'not_found_in_trash' => 'No portfolio items found in Trash',
		],
		'public'       => true,
		'has_archive'  => true,
		'rewrite'      => [ 'slug' => 'portfolio' ],
		'supports'     => [ 'title', 'editor', 'thumbnail', 'custom-fields', 'excerpt' ],
		'show_in_rest' => true,
		'menu_icon'    => 'dashicons-portfolio',
	] );
} );

add_action( 'add_meta_boxes', function () {
	add_meta_box(
		'dw_portfolio_details',
		'Project Details',
		'dw_portfolio_meta_box_callback',
		'dw_portfolio'
	);
} );

function dw_portfolio_meta_box_callback( $post ) {
	wp_nonce_field( 'dw_portfolio_save', 'dw_portfolio_nonce' );

	$client     = get_post_meta( $post->ID, '_dw_client', true );
	$url        = get_post_meta( $post->ID, '_dw_project_url', true );
	$category   = get_post_meta( $post->ID, '_dw_project_category', true );
	$completed  = get_post_meta( $post->ID, '_dw_completed_date', true );

	?>
	<table class="form-table">
		<tr>
			<th><label for="dw_client">Client Name</label></th>
			<td><input type="text" id="dw_client" name="dw_client" value="<?php echo esc_attr( $client ); ?>" class="regular-text"></td>
		</tr>
		<tr>
			<th><label for="dw_project_url">Project URL</label></th>
			<td><input type="url" id="dw_project_url" name="dw_project_url" value="<?php echo esc_attr( $url ); ?>" class="regular-text"></td>
		</tr>
		<tr>
			<th><label for="dw_project_category">Category</label></th>
			<td><input type="text" id="dw_project_category" name="dw_project_category" value="<?php echo esc_attr( $category ); ?>" class="regular-text"></td>
		</tr>
		<tr>
			<th><label for="dw_completed_date">Completed Date</label></th>
			<td><input type="date" id="dw_completed_date" name="dw_completed_date" value="<?php echo esc_attr( $completed ); ?>"></td>
		</tr>
	</table>
	<?php
}

add_action( 'save_post_dw_portfolio', function ( $post_id ) {
	if ( ! isset( $_POST['dw_portfolio_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( $_POST['dw_portfolio_nonce'], 'dw_portfolio_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	update_post_meta( $post_id, '_dw_client', sanitize_text_field( $_POST['dw_client'] ?? '' ) );
	update_post_meta( $post_id, '_dw_project_url', esc_url_raw( $_POST['dw_project_url'] ?? '' ) );
	update_post_meta( $post_id, '_dw_project_category', sanitize_text_field( $_POST['dw_project_category'] ?? '' ) );
	update_post_meta( $post_id, '_dw_completed_date', sanitize_text_field( $_POST['dw_completed_date'] ?? '' ) );
} );
