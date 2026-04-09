<?php
/**
 * WP-CLI Commands for DW Core
 *
 * Provides CLI commands to render pages from JSON templates.
 *
 * @package DW_Core
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) ) {
	return;
}

class DW_CLI_Commands {

	/**
	 * Render a page from its JSON template and update the post content.
	 *
	 * ## OPTIONS
	 *
	 * <page_name>
	 * : The page name matching block-templates/pages/<name>.json
	 *
	 * [--dry-run]
	 * : Output the markup without updating the post.
	 *
	 * [--post-id=<id>]
	 * : Explicit post ID to update. If omitted, looks up by page title.
	 *
	 * ## EXAMPLES
	 *
	 *     wp dw render home
	 *     wp dw render portfolio --dry-run
	 *     wp dw render community --post-id=14
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Named args.
	 */
	public function render( $args, $assoc_args ) {
		$page_name = $args[0];
		$dry_run   = isset( $assoc_args['dry-run'] );
		$post_id   = $assoc_args['post-id'] ?? null;

		$renderer = DW_Block_Renderer::instance();
		$markup   = $renderer->render_page( $page_name );

		if ( empty( $markup ) ) {
			WP_CLI::error( "No template found for page: {$page_name}" );
			return;
		}

		if ( $dry_run ) {
			WP_CLI::log( $markup );
			WP_CLI::success( "Dry run complete for: {$page_name} (" . strlen( $markup ) . " chars)" );
			return;
		}

		// Find the post
		if ( ! $post_id ) {
			$post_id = $this->find_page_id( $page_name );
		}

		if ( ! $post_id ) {
			WP_CLI::error( "Could not find page: {$page_name}. Use --post-id to specify." );
			return;
		}

		$result = wp_update_post( [
			'ID'           => $post_id,
			'post_content' => $markup,
		], true );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( "Failed to update post {$post_id}: " . $result->get_error_message() );
			return;
		}

		WP_CLI::success( "Page '{$page_name}' (ID:{$post_id}) updated with rendered markup (" . strlen( $markup ) . " chars)" );
	}

	/**
	 * Render ALL pages that have JSON templates.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Output stats without updating posts.
	 *
	 * ## EXAMPLES
	 *
	 *     wp dw render-all
	 *     wp dw render-all --dry-run
	 */
	public function render_all( $args, $assoc_args ) {
		$dry_run   = isset( $assoc_args['dry-run'] );
		$base      = dirname( DW_CORE_PATH ) . '/../block-templates/pages/';
		$renderer  = DW_Block_Renderer::instance();
		$count     = 0;

		if ( ! is_dir( $base ) ) {
			WP_CLI::error( "Pages directory not found: {$base}" );
			return;
		}

		foreach ( glob( $base . '*.json' ) as $file ) {
			$page_name = basename( $file, '.json' );
			$markup    = $renderer->render_page( $page_name );

			if ( empty( $markup ) ) {
				WP_CLI::warning( "Empty markup for: {$page_name}, skipping." );
				continue;
			}

			if ( $dry_run ) {
				WP_CLI::log( "Would render: {$page_name} (" . strlen( $markup ) . " chars)" );
				$count++;
				continue;
			}

			$post_id = $this->find_page_id( $page_name );
			if ( ! $post_id ) {
				WP_CLI::warning( "Page not found in WP: {$page_name}, skipping." );
				continue;
			}

			wp_update_post( [
				'ID'           => $post_id,
				'post_content' => $markup,
			] );

			WP_CLI::log( "Updated: {$page_name} (ID:{$post_id}, " . strlen( $markup ) . " chars)" );
			$count++;
		}

		WP_CLI::success( "{$count} pages " . ( $dry_run ? 'would be' : '' ) . " rendered." );
	}

	/**
	 * Render a single section template and output its markup.
	 *
	 * ## OPTIONS
	 *
	 * <section_name>
	 * : Section file name without extension (e.g., 'hero-section').
	 *
	 * [--background=<bg>]
	 * : Background pattern name from backgrounds.json (default: white).
	 *
	 * ## EXAMPLES
	 *
	 *     wp dw render-section hero-section
	 *     wp dw render-section cta-section --background=dark
	 */
	public function render_section( $args, $assoc_args ) {
		$section_name = $args[0];
		$bg_name      = $assoc_args['background'] ?? 'white';

		$renderer = DW_Block_Renderer::instance();
		$bg       = $renderer->get_background( $bg_name );
		$markup   = $renderer->render_section_ref( "sections/{$section_name}.json", $bg );

		if ( empty( $markup ) ) {
			WP_CLI::error( "Section not found or empty: {$section_name}" );
			return;
		}

		WP_CLI::log( $markup );
		WP_CLI::success( "Section '{$section_name}' rendered (" . strlen( $markup ) . " chars)" );
	}

	/**
	 * List all available section and page templates.
	 *
	 * ## EXAMPLES
	 *
	 *     wp dw list-templates
	 */
	public function list_templates( $args, $assoc_args ) {
		$base = dirname( DW_CORE_PATH ) . '/../block-templates/';

		WP_CLI::log( "=== Section Templates ===" );
		foreach ( glob( $base . 'sections/*.json' ) as $f ) {
			$data = json_decode( file_get_contents( $f ), true );
			$name = $data['template_name'] ?? basename( $f, '.json' );
			$desc = $data['description'] ?? '';
			$ver  = $data['version'] ?? '?';
			WP_CLI::log( "  {$name} (v{$ver}) - {$desc}" );
		}

		WP_CLI::log( "" );
		WP_CLI::log( "=== Page Templates ===" );
		foreach ( glob( $base . 'pages/*.json' ) as $f ) {
			$data     = json_decode( file_get_contents( $f ), true );
			$page     = $data['page'] ?? basename( $f, '.json' );
			$sections = count( $data['sections'] ?? [] );
			$preset   = $data['background_preset'] ?? 'none';
			WP_CLI::log( "  {$page} ({$sections} sections, bg-preset: {$preset})" );
		}
	}

	/**
	 * Find a page ID by its name/title.
	 */
	private function find_page_id( $page_name ) {
		$title = ucfirst( $page_name );
		$pages = get_posts( [
			'post_type'   => 'page',
			'title'       => $title,
			'numberposts' => 1,
			'post_status' => 'publish',
		] );

		return $pages ? $pages[0]->ID : null;
	}
}
