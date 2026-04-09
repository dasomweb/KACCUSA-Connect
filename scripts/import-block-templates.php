<?php
/**
 * WP-CLI: Import Block Templates from JSON
 *
 * Reads block-templates/sections/ and block-templates/pages/
 * and registers them as reusable blocks or template parts.
 *
 * Usage: wp eval-file scripts/import-block-templates.php --allow-root
 *
 * @package KACCUSA-Connect
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	echo "This script must be run via WP-CLI.\n";
	exit( 1 );
}

$base = dirname( __DIR__ );
$sections_dir = "$base/block-templates/sections";
$pages_dir    = "$base/block-templates/pages";

$imported = 0;
$errors   = 0;

// Import section templates
if ( is_dir( $sections_dir ) ) {
	foreach ( glob( "$sections_dir/*.json" ) as $file ) {
		$data = json_decode( file_get_contents( $file ), true );
		if ( ! $data ) {
			WP_CLI::warning( "Failed to parse: $file" );
			$errors++;
			continue;
		}

		$template_name = $data['template_name'] ?? basename( $file, '.json' );
		$existing = get_posts( [
			'post_type'  => 'wp_block',
			'title'      => $template_name,
			'numberposts' => 1,
		] );

		$block_content = wp_json_encode( $data['blocks'] ?? $data );

		if ( $existing ) {
			wp_update_post( [
				'ID'           => $existing[0]->ID,
				'post_content' => $block_content,
			] );
			WP_CLI::log( "Updated section: $template_name" );
		} else {
			wp_insert_post( [
				'post_type'    => 'wp_block',
				'post_title'   => $template_name,
				'post_content' => $block_content,
				'post_status'  => 'publish',
			] );
			WP_CLI::log( "Created section: $template_name" );
		}
		$imported++;
	}
}

// Import page layout definitions
if ( is_dir( $pages_dir ) ) {
	foreach ( glob( "$pages_dir/*.json" ) as $file ) {
		$data = json_decode( file_get_contents( $file ), true );
		if ( ! $data ) {
			WP_CLI::warning( "Failed to parse: $file" );
			$errors++;
			continue;
		}

		$page_name = $data['page'] ?? basename( $file, '.json' );
		update_option( "dw_page_layout_{$page_name}", $data );
		WP_CLI::log( "Registered page layout: $page_name" );
		$imported++;
	}
}

WP_CLI::success( "Import complete: $imported templates processed, $errors errors." );
