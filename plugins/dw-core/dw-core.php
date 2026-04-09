<?php
/**
 * Plugin Name: DW Core
 * Plugin URI: https://dasomweb.com
 * Description: Core functionality plugin for KACCUSA-Connect. Manages CPT registration, custom fields, GPS auth, community features, and block template rendering.
 * Version: 1.0.0
 * Author: DASOMWEB / DW Studio
 * Author URI: https://dasomweb.com
 * Text Domain: dw-core
 *
 * @package DW_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DW_CORE_VERSION', '1.0.0' );
define( 'DW_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'DW_CORE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load CPT registrations
 */
$post_types_dir = dirname( DW_CORE_PATH ) . '/../includes/post-types/';
if ( is_dir( $post_types_dir ) ) {
	foreach ( glob( $post_types_dir . 'register-*.php' ) as $file ) {
		require_once $file;
	}
}

/**
 * Load field definitions
 */
$fields_file = dirname( DW_CORE_PATH ) . '/../includes/fields/field-definitions.php';
if ( file_exists( $fields_file ) ) {
	require_once $fields_file;
}

/**
 * Load GPS Auth module (preserved from existing codebase)
 */
if ( file_exists( DW_CORE_PATH . 'gps-auth/gps-auth.php' ) ) {
	require_once DW_CORE_PATH . 'gps-auth/gps-auth.php';
}

/**
 * Load Community module (preserved from existing codebase)
 */
if ( file_exists( DW_CORE_PATH . 'community/community.php' ) ) {
	require_once DW_CORE_PATH . 'community/community.php';
}

/**
 * Render a block template by page name.
 *
 * Loads the page layout JSON and renders sections in order.
 *
 * @param string $page_name The page layout name (e.g., 'home', 'portfolio').
 */
function dw_render_block_template( $page_name ) {
	$layout = get_option( "dw_page_layout_{$page_name}" );
	if ( ! $layout || empty( $layout['sections'] ) ) {
		return;
	}

	echo '<div class="dw-page-layout dw-page-' . esc_attr( $page_name ) . '">';
	foreach ( $layout['sections'] as $section ) {
		if ( isset( $section['ref'] ) ) {
			$template_name = basename( $section['ref'], '.json' );
			$block = get_posts( [
				'post_type'   => 'wp_block',
				'title'       => $template_name . '-v1',
				'numberposts' => 1,
			] );
			if ( $block ) {
				echo do_blocks( $block[0]->post_content );
			}
		} elseif ( isset( $section['type'] ) && 'query-loop' === $section['type'] ) {
			// Query loop sections are rendered by WordPress block editor
			// The card design is defined in the referenced card_ref JSON
			echo '<!-- Query Loop: ' . esc_html( $section['post_type'] ?? '' ) . ' -->';
		}
	}
	echo '</div>';
}

/**
 * Register REST API health check endpoints
 */
add_action( 'rest_api_init', function () {
	register_rest_route( 'dw/v1', '/health', [
		'methods'             => 'GET',
		'callback'            => function () {
			return rest_ensure_response( [
				'status'  => 'ok',
				'version' => DW_CORE_VERSION,
				'time'    => current_time( 'mysql' ),
			] );
		},
		'permission_callback' => '__return_true',
	] );

	register_rest_route( 'dw/v1', '/community/status', [
		'methods'             => 'GET',
		'callback'            => function () {
			$count = wp_count_posts( 'dw_community' );
			return rest_ensure_response( [
				'status'    => 'ok',
				'published' => (int) ( $count->publish ?? 0 ),
			] );
		},
		'permission_callback' => '__return_true',
	] );
} );
