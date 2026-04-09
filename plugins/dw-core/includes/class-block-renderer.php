<?php
/**
 * Block Renderer Engine
 *
 * Converts section JSON templates into valid Kadence Blocks Gutenberg markup.
 * Reads design tokens for colors, spacing, backgrounds, and typography.
 *
 * Pipeline: JSON section → Kadence block markup → post_content
 *
 * @package DW_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DW_Block_Renderer {

	private static $instance = null;
	private $tokens_path;
	private $templates_path;
	private $colors;
	private $spacing;
	private $backgrounds;
	private $typography;
	private $borders;
	private $components;
	private $palette_map;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$base = dirname( DW_CORE_PATH ) . '/..';
		$this->tokens_path    = $base . '/design-tokens/';
		$this->templates_path = $base . '/block-templates/';
		$this->load_tokens();
	}

	private function load_tokens() {
		$this->colors      = $this->load_json( $this->tokens_path . 'colors.json' );
		$this->spacing     = $this->load_json( $this->tokens_path . 'spacing.json' );
		$this->backgrounds = $this->load_json( $this->tokens_path . 'backgrounds.json' );
		$this->typography  = $this->load_json( $this->tokens_path . 'typography.json' );
		$this->borders     = $this->load_json( $this->tokens_path . 'borders.json' );
		$this->components  = $this->load_json( $this->tokens_path . 'components.json' );
		$this->palette_map = $this->load_json( $this->tokens_path . 'palette-map.json' );
	}

	/**
	 * Resolve a semantic color name to Kadence palette reference.
	 *
	 * Kadence Blocks use 'palette1'-'palette9' in attrs.
	 * The theme Customizer controls the actual hex values.
	 * This ensures ALL colors are theme-controlled, never hardcoded.
	 *
	 * @param string $semantic Semantic name (e.g., 'text-primary', 'bg-dark').
	 * @return string Kadence palette key (e.g., 'palette8').
	 */
	public function palette( $semantic ) {
		return $this->palette_map['semantic'][ $semantic ] ?? 'palette8';
	}

	/**
	 * Get CSS variable for a palette slot.
	 * Use in inline styles where Kadence attrs don't apply.
	 *
	 * @param string $semantic Semantic color name.
	 * @return string CSS var (e.g., 'var(--global-palette1)').
	 */
	public function palette_css( $semantic ) {
		$slot = $this->palette( $semantic );
		$n    = str_replace( 'palette', '', $slot );
		return "var(--global-palette{$n})";
	}

	/**
	 * Get section background config using palette references.
	 *
	 * @param string $name Background name (white, light, dark, accent, brand).
	 * @return array { 'bg' => palette key, 'text' => palette key }
	 */
	public function section_bg( $name ) {
		return $this->palette_map['section_backgrounds'][ $name ] ?? [
			'bg'   => 'palette7',
			'text'  => 'palette8',
		];
	}

	private function load_json( $path ) {
		if ( ! file_exists( $path ) ) {
			return [];
		}
		$data = json_decode( file_get_contents( $path ), true );
		return is_array( $data ) ? $data : [];
	}

	/**
	 * Render a full page from its page layout JSON.
	 *
	 * @param string $page_name Page name (e.g., 'home', 'portfolio').
	 * @return string Gutenberg block markup.
	 */
	public function render_page( $page_name ) {
		$page_file = $this->templates_path . "pages/{$page_name}.json";
		$page_data = $this->load_json( $page_file );

		if ( empty( $page_data['sections'] ) ) {
			return '';
		}

		$bg_preset = $page_data['background_preset'] ?? null;
		$bg_order  = [];
		if ( $bg_preset && isset( $this->backgrounds['page-presets'][ $bg_preset ] ) ) {
			$bg_order = $this->backgrounds['page-presets'][ $bg_preset ];
		}

		$markup = '';
		foreach ( $page_data['sections'] as $i => $section ) {
			// Determine background from preset or section override
			$bg_name = $section['background'] ?? ( $bg_order[ $i ] ?? 'white' );
			$bg      = $this->resolve_background( $bg_name );

			if ( isset( $section['ref'] ) ) {
				$markup .= $this->render_section_ref( $section['ref'], $bg );
			} elseif ( isset( $section['type'] ) && 'query-loop' === $section['type'] ) {
				$markup .= $this->render_query_loop_section( $section, $bg );
			}
		}

		return $markup;
	}

	/**
	 * Render a single section from its JSON file reference.
	 *
	 * @param string $ref  Section file reference (e.g., 'sections/hero-section.json').
	 * @param array  $bg   Background config.
	 * @return string Block markup.
	 */
	public function render_section_ref( $ref, $bg = [] ) {
		$file = $this->templates_path . $ref;
		$data = $this->load_json( $file );

		if ( empty( $data['blocks'] ) ) {
			return '';
		}

		return $this->render_blocks( $data['blocks'], $bg );
	}

	/**
	 * Render a single section JSON (not from file, but from data).
	 *
	 * @param array $section_data Full section JSON data.
	 * @param array $bg           Background config.
	 * @return string Block markup.
	 */
	public function render_section_data( $section_data, $bg = [] ) {
		if ( empty( $section_data['blocks'] ) ) {
			return '';
		}
		return $this->render_blocks( $section_data['blocks'], $bg );
	}

	/**
	 * Resolve a background name to palette-based config.
	 *
	 * Returns both palette keys (for Kadence attrs) and
	 * CSS vars (for inline styles).
	 */
	private function resolve_background( $name ) {
		$section_bg = $this->section_bg( $name );
		$pattern    = $this->backgrounds['patterns'][ $name ] ?? null;

		return [
			'type'         => $pattern['type'] ?? 'color',
			'palette_bg'   => $section_bg['bg'],
			'palette_text' => $section_bg['text'],
			'overlay'      => $pattern['overlay'] ?? 'none',
		];
	}

	/**
	 * Render an array of blocks recursively into Gutenberg markup.
	 *
	 * @param array $blocks Block definitions.
	 * @param array $bg     Parent background config (for text color inheritance).
	 * @return string
	 */
	private function render_blocks( $blocks, $bg = [] ) {
		$markup = '';
		foreach ( $blocks as $block ) {
			$markup .= $this->render_block( $block, $bg );
		}
		return $markup;
	}

	/**
	 * Render a single block into Gutenberg markup.
	 */
	private function render_block( $block, $bg = [] ) {
		$name  = $block['blockName'] ?? '';
		$attrs = $block['attrs'] ?? [];
		$inner = $block['innerBlocks'] ?? [];

		// Apply background override to row layout
		if ( 'kadence/rowlayout' === $name && ! empty( $bg ) ) {
			$attrs = $this->apply_bg_to_row( $attrs, $bg );
		}

		// Route to specialized renderers
		switch ( $name ) {
			case 'kadence/rowlayout':
				return $this->render_rowlayout( $attrs, $inner, $bg );
			case 'kadence/column':
				return $this->render_column( $attrs, $inner, $bg );
			case 'kadence/advancedheading':
				return $this->render_heading( $attrs, $bg );
			case 'kadence/advancedbtn':
				return $this->render_btn_wrap( $attrs, $inner, $bg );
			case 'kadence/singlebtn':
				return $this->render_single_btn( $attrs );
			case 'kadence/icon':
				return $this->render_icon( $attrs );
			case 'core/image':
				return $this->render_image( $attrs );
			case 'kadence/iconlist':
				return $this->render_iconlist( $attrs, $inner );
			case 'core/separator':
				return $this->render_separator( $attrs );
			default:
				// Generic block passthrough
				return $this->render_generic( $name, $attrs, $inner, $bg );
		}
	}

	/**
	 * Apply background config to row layout attrs using Kadence palette.
	 */
	private function apply_bg_to_row( $attrs, $bg ) {
		if ( ! empty( $bg['palette_bg'] ) ) {
			// Kadence uses 'backgroundColorClass' for palette references
			$attrs['bgColor'] = $bg['palette_bg'];
		}
		return $attrs;
	}

	// ─── Row Layout ────────────────────────────────────────────

	private function render_rowlayout( $attrs, $inner_blocks, $bg ) {
		$json_attrs = wp_json_encode( $this->clean_attrs( $attrs ) );
		$uid        = $attrs['uniqueID'] ?? 'dw_row_' . wp_rand();
		$align      = isset( $attrs['align'] ) ? ' align' . $attrs['align'] : ' alignfull';

		$styles = $this->build_row_styles( $attrs );

		$out  = "<!-- wp:kadence/rowlayout {$json_attrs} -->\n";
		$out .= "<div class=\"wp-block-kadence-rowlayout{$align}\"{$styles}>\n";

		foreach ( $inner_blocks as $child ) {
			$out .= $this->render_block( $child, $bg );
		}

		$out .= "</div>\n";
		$out .= "<!-- /wp:kadence/rowlayout -->\n\n";

		return $out;
	}

	private function build_row_styles( $attrs ) {
		// Kadence handles bgColor via palette class, no inline style needed
		return '';
	}

	// ─── Column ────────────────────────────────────────────────

	private function render_column( $attrs, $inner_blocks, $bg ) {
		$json_attrs = ! empty( $attrs ) ? ' ' . wp_json_encode( $this->clean_attrs( $attrs ) ) : '';
		$styles     = $this->build_column_styles( $attrs );

		$align_class = '';
		if ( ! empty( $attrs['textAlign'] ) ) {
			$align_class = ' has-text-align-' . $attrs['textAlign'];
		}

		$out  = "<!-- wp:kadence/column{$json_attrs} -->\n";
		$out .= "<div class=\"wp-block-kadence-column{$align_class}\"{$styles}>\n";

		foreach ( $inner_blocks as $child ) {
			$out .= $this->render_block( $child, $bg );
		}

		$out .= "</div>\n";
		$out .= "<!-- /wp:kadence/column -->\n";

		return $out;
	}

	private function build_column_styles( $attrs ) {
		$styles = [];
		if ( ! empty( $attrs['background'] ) ) {
			$styles[] = 'background:' . $attrs['background'];
		}
		if ( ! empty( $attrs['borderRadius'] ) ) {
			$r = is_array( $attrs['borderRadius'] ) ? $attrs['borderRadius'][0] : $attrs['borderRadius'];
			$styles[] = 'border-radius:' . $r . 'px';
		}
		if ( ! empty( $attrs['padding'] ) && is_array( $attrs['padding'] ) ) {
			$styles[] = 'padding:' . implode( ' ', array_map( function( $v ) {
				return is_numeric( $v ) ? $v . 'px' : $v;
			}, $attrs['padding'] ) );
		}
		// Individual paddings (desktop value)
		foreach ( ['top', 'bottom', 'left', 'right'] as $side ) {
			$key = $side . 'Padding';
			if ( isset( $attrs[ $key ] ) && ! isset( $attrs['padding'] ) ) {
				$val = is_array( $attrs[ $key ] ) ? $attrs[ $key ][0] : $attrs[ $key ];
				$styles[] = "padding-{$side}:{$val}px";
			}
		}
		return $styles ? ' style="' . implode( ';', $styles ) . '"' : '';
	}

	// ─── Advanced Heading ──────────────────────────────────────

	private function render_heading( $attrs, $bg = [] ) {
		$level   = $attrs['level'] ?? 2;
		$tag     = $attrs['htmlTag'] ?? "h{$level}";
		$uid     = $attrs['uniqueID'] ?? 'dw_h_' . wp_rand();
		$align   = $attrs['align'] ?? null;
		$content = $attrs['content'] ?? '';

		// Use palette for colors - NEVER hardcode hex
		$color_palette = $attrs['colorPalette'] ?? null;
		if ( ! $color_palette && ! empty( $bg['palette_text'] ) ) {
			$color_palette = $bg['palette_text'];
		}
		if ( ! $color_palette ) {
			$color_palette = $this->palette( 'text-primary' );
		}

		// Set Kadence palette attr
		$attrs['color'] = $color_palette;

		// Remove fontFamily - let theme control it
		unset( $attrs['fontFamily'] );

		$json_attrs = wp_json_encode( $this->clean_attrs( $attrs ) );
		$align_class = $align ? " has-text-align-{$align}" : '';

		// Kadence resolves palette to CSS var automatically
		$out  = "<!-- wp:kadence/advancedheading {$json_attrs} -->\n";
		$out .= "<{$tag} class=\"kt-adv-heading{$align_class}\">{$content}</{$tag}>\n";
		$out .= "<!-- /wp:kadence/advancedheading -->\n";

		return $out;
	}

	// ─── Buttons ───────────────────────────────────────────────

	private function render_btn_wrap( $attrs, $inner_blocks, $bg ) {
		$json_attrs = wp_json_encode( $this->clean_attrs( $attrs ) );
		$align      = ! empty( $attrs['hAlign'] ) ? ' kt-btn-align-' . $attrs['hAlign'] : '';

		$out  = "<!-- wp:kadence/advancedbtn {$json_attrs} -->\n";
		$out .= "<div class=\"kt-btn-wrap{$align}\">\n";

		foreach ( $inner_blocks as $child ) {
			$out .= $this->render_block( $child, $bg );
		}

		$out .= "</div>\n";
		$out .= "<!-- /wp:kadence/advancedbtn -->\n";

		return $out;
	}

	private function render_single_btn( $attrs ) {
		$text   = $attrs['text'] ?? 'Click';
		$radius = $attrs['borderRadius'] ?? $this->get_radius( 'button' );

		// Use palette references for button colors
		$attrs['colorPalette']     = $attrs['colorPalette'] ?? $this->palette( 'btn-primary-text' );
		$attrs['backgroundPalette'] = $attrs['backgroundPalette'] ?? $this->palette( 'btn-primary-bg' );
		$attrs['borderRadius'] = $radius;

		$json_attrs = wp_json_encode( $this->clean_attrs( $attrs ) );

		$out  = "<!-- wp:kadence/singlebtn {$json_attrs} -->\n";
		$out .= "<div class=\"kt-btn-wrap\"><a class=\"kt-button\">{$text}</a></div>\n";
		$out .= "<!-- /wp:kadence/singlebtn -->\n";

		return $out;
	}

	// ─── Icon ──────────────────────────────────────────────────

	private function render_icon( $attrs ) {
		$json_attrs = wp_json_encode( $this->clean_attrs( $attrs ) );
		$out  = "<!-- wp:kadence/icon {$json_attrs} -->\n";
		$out .= "<div class=\"kt-svg-icon-wrap\"></div>\n";
		$out .= "<!-- /wp:kadence/icon -->\n";
		return $out;
	}

	// ─── Image ─────────────────────────────────────────────────

	private function render_image( $attrs ) {
		$slug  = $attrs['sizeSlug'] ?? 'large';
		$class = $attrs['className'] ?? '';
		$alt   = $attrs['alt'] ?? '';
		$src   = $attrs['src'] ?? '';
		$radius = isset( $attrs['borderRadius'] ) ? $attrs['borderRadius'] : null;

		$json_attrs = wp_json_encode( $this->clean_attrs( $attrs ) );

		$style = $radius !== null ? " style=\"border-radius:{$radius}px\"" : '';

		$out  = "<!-- wp:core/image {$json_attrs} -->\n";
		$out .= "<figure class=\"wp-block-image size-{$slug} {$class}\">";
		$out .= "<img src=\"{$src}\" alt=\"{$alt}\"{$style}/>";
		$out .= "</figure>\n";
		$out .= "<!-- /wp:core/image -->\n";

		return $out;
	}

	// ─── Separator ─────────────────────────────────────────────

	private function render_separator( $attrs ) {
		$json_attrs = ! empty( $attrs ) ? wp_json_encode( $this->clean_attrs( $attrs ) ) : '{}';
		return "<!-- wp:core/separator {$json_attrs} -->\n<hr class=\"wp-block-separator\"/>\n<!-- /wp:core/separator -->\n";
	}

	// ─── Icon List ─────────────────────────────────────────────

	private function render_iconlist( $attrs, $inner ) {
		$json_attrs = wp_json_encode( $this->clean_attrs( $attrs ) );
		$out  = "<!-- wp:kadence/iconlist {$json_attrs} -->\n";
		$out .= "<ul class=\"kt-svg-icon-list\">\n";
		foreach ( $inner as $item ) {
			$text = $item['attrs']['text'] ?? '';
			$out .= "<li>{$text}</li>\n";
		}
		$out .= "</ul>\n";
		$out .= "<!-- /wp:kadence/iconlist -->\n";
		return $out;
	}

	// ─── Query Loop Section ────────────────────────────────────

	private function render_query_loop_section( $section, $bg ) {
		$post_type    = $section['post_type'] ?? 'post';
		$bg_palette   = $bg['palette_bg'] ?? 'palette7';
		$text_palette = $bg['palette_text'] ?? 'palette8';

		$section_padding = $this->spacing['section']['padding-y'] ?? [ 'desktop' => 80, 'tablet' => 60, 'mobile' => 40 ];
		$py = [ $section_padding['desktop'], $section_padding['tablet'], $section_padding['mobile'] ];

		$attrs = [
			'uniqueID'     => 'dw_ql_' . $post_type,
			'columns'      => 1,
			'topPadding'   => $py,
			'bottomPadding'=> $py,
			'leftPadding'  => [0, 0, 20],
			'rightPadding' => [0, 0, 20],
			'bgColor'      => $bg_palette,
			'align'        => 'full',
			'maxWidth'     => 1200,
		];

		$json = wp_json_encode( $attrs );

		$out  = "<!-- wp:kadence/rowlayout {$json} -->\n";
		$out .= "<div class=\"wp-block-kadence-rowlayout alignfull\">\n";
		$out .= "<!-- wp:kadence/column -->\n<div class=\"wp-block-kadence-column\">\n";

		// Section heading - palette-based color
		$h_attrs = wp_json_encode([
			'level' => 2, 'color' => $text_palette,
			'fontSize' => ['36','28','24'], 'fontWeight' => '700',
			'align' => 'center', 'margin' => ['','','','40'],
		]);
		$heading = ucfirst( str_replace( 'dw_', '', $post_type ) );
		$out .= "<!-- wp:kadence/advancedheading {$h_attrs} -->\n";
		$out .= "<h2 class=\"kt-adv-heading has-text-align-center\">{$heading}</h2>\n";
		$out .= "<!-- /wp:kadence/advancedheading -->\n";

		// Query Loop block
		$out .= "<!-- wp:query {\"queryId\":0,\"query\":{\"postType\":\"{$post_type}\",\"perPage\":6},\"displayLayout\":{\"type\":\"flex\",\"columns\":3}} -->\n";
		$out .= "<div class=\"wp-block-query\">\n";
		$out .= "<!-- wp:post-template -->\n";
		$out .= "<!-- wp:post-featured-image {\"isLink\":true} /-->\n";
		$out .= "<!-- wp:post-title {\"isLink\":true} /-->\n";
		$out .= "<!-- wp:post-excerpt /-->\n";
		$out .= "<!-- /wp:post-template -->\n";
		$out .= "<!-- wp:query-pagination -->\n";
		$out .= "<!-- wp:query-pagination-previous /-->\n";
		$out .= "<!-- wp:query-pagination-numbers /-->\n";
		$out .= "<!-- wp:query-pagination-next /-->\n";
		$out .= "<!-- /wp:query-pagination -->\n";
		$out .= "</div>\n";
		$out .= "<!-- /wp:query -->\n";

		$out .= "</div>\n<!-- /wp:kadence/column -->\n";
		$out .= "</div>\n<!-- /wp:kadence/rowlayout -->\n\n";

		return $out;
	}

	// ─── Generic Block ─────────────────────────────────────────

	private function render_generic( $name, $attrs, $inner, $bg ) {
		$json_attrs = ! empty( $attrs ) ? ' ' . wp_json_encode( $this->clean_attrs( $attrs ) ) : '';
		$out = "<!-- wp:{$name}{$json_attrs} -->\n";

		if ( ! empty( $inner ) ) {
			foreach ( $inner as $child ) {
				$out .= $this->render_block( $child, $bg );
			}
		}

		$out .= "<!-- /wp:{$name} -->\n";
		return $out;
	}

	// ─── Utilities ─────────────────────────────────────────────

	/**
	 * Clean attrs for JSON encoding (remove internal-only keys).
	 */
	private function clean_attrs( $attrs ) {
		$skip = [ 'content' ];
		$clean = [];
		foreach ( $attrs as $k => $v ) {
			if ( ! in_array( $k, $skip, true ) ) {
				$clean[ $k ] = $v;
			}
		}
		return $clean;
	}

	/**
	 * Get responsive padding array from section tokens.
	 */
	public function get_section_padding_y() {
		$s = $this->spacing['section']['padding-y'] ?? [];
		return [ $s['desktop'] ?? 80, $s['tablet'] ?? 60, $s['mobile'] ?? 40 ];
	}

	public function get_section_padding_x() {
		$s = $this->spacing['section']['padding-x'] ?? [];
		return [ $s['desktop'] ?? 0, $s['tablet'] ?? 0, $s['mobile'] ?? 20 ];
	}

	public function get_radius( $key ) {
		return $this->spacing['radius'][ $key ] ?? 0;
	}

	public function get_background( $name ) {
		return $this->resolve_background( $name );
	}

	public function get_token( $type ) {
		switch ( $type ) {
			case 'colors':      return $this->colors;
			case 'spacing':     return $this->spacing;
			case 'typography':  return $this->typography;
			case 'backgrounds': return $this->backgrounds;
			case 'borders':     return $this->borders;
			case 'components':  return $this->components;
		}
		return [];
	}
}
