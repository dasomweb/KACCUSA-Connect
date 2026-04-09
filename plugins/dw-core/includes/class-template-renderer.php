<?php
/**
 * Block Template Renderer
 *
 * Renders page layouts by composing section templates
 * defined in block-templates/pages/*.json.
 *
 * @package DW_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DW_Template_Renderer {

	private static $instance = null;
	private $templates_path;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->templates_path = dirname( DW_CORE_PATH ) . '/../block-templates/';
	}

	/**
	 * Render a page layout by name.
	 *
	 * First checks the DB option (set by import-block-templates.php),
	 * then falls back to reading the JSON file directly.
	 *
	 * @param string $page_name Page name (e.g., 'home', 'portfolio').
	 */
	public function render( $page_name ) {
		$layout = get_option( "dw_page_layout_{$page_name}" );

		if ( ! $layout ) {
			$json_file = $this->templates_path . "pages/{$page_name}.json";
			if ( file_exists( $json_file ) ) {
				$layout = json_decode( file_get_contents( $json_file ), true );
			}
		}

		if ( ! $layout || empty( $layout['sections'] ) ) {
			return;
		}

		echo '<div class="dw-page-layout dw-page-' . esc_attr( $page_name ) . '">';

		foreach ( $layout['sections'] as $section ) {
			$this->render_section( $section );
		}

		echo '</div>';
	}

	/**
	 * Render a single section.
	 *
	 * @param array $section Section definition from page layout JSON.
	 */
	private function render_section( $section ) {
		if ( isset( $section['ref'] ) ) {
			$this->render_ref_section( $section['ref'] );
		} elseif ( isset( $section['type'] ) && 'query-loop' === $section['type'] ) {
			$this->render_query_loop_placeholder( $section );
		}
	}

	/**
	 * Render a referenced section template (reusable block).
	 *
	 * @param string $ref Reference path (e.g., 'sections/hero-section.json').
	 */
	private function render_ref_section( $ref ) {
		$template_name = basename( $ref, '.json' );

		// Try to find as reusable block
		$blocks = get_posts( [
			'post_type'   => 'wp_block',
			'title'       => $template_name . '-v1',
			'numberposts' => 1,
			'post_status' => 'publish',
		] );

		if ( $blocks ) {
			echo do_blocks( $blocks[0]->post_content );
			return;
		}

		// Fallback: try without version suffix
		$blocks = get_posts( [
			'post_type'   => 'wp_block',
			'title'       => $template_name,
			'numberposts' => 1,
			'post_status' => 'publish',
		] );

		if ( $blocks ) {
			echo do_blocks( $blocks[0]->post_content );
		}
	}

	/**
	 * Render query loop placeholder.
	 * Actual query parameters (posts_per_page, filters) are set in WP Admin.
	 *
	 * @param array $section Section definition with query loop config.
	 */
	private function render_query_loop_placeholder( $section ) {
		$post_type = $section['post_type'] ?? 'post';
		echo '<!-- DW Query Loop: ' . esc_html( $post_type ) . ' -->';
		echo '<div class="dw-query-loop dw-query-' . esc_attr( $post_type ) . '">';
		do_action( 'dw_query_loop_render', $post_type, $section );
		echo '</div>';
	}
}
