<?php
/**
 * Design Tokens CSS Variable Injector
 *
 * Generates and enqueues CSS custom properties from design token JSON files.
 * This allows the front-end to reference tokens directly via var(--dw-*).
 *
 * @package DW_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DW_Design_Tokens {

	private static $instance = null;
	private $tokens_path;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->tokens_path = dirname( DW_CORE_PATH ) . '/../design-tokens/';
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_token_css' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_token_css' ] );
	}

	public function enqueue_token_css() {
		$css = $this->generate_css();
		if ( $css ) {
			wp_register_style( 'dw-design-tokens', false );
			wp_enqueue_style( 'dw-design-tokens' );
			wp_add_inline_style( 'dw-design-tokens', $css );
		}
	}

	public function generate_css() {
		$cache_key = 'dw_design_tokens_css';
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$css = ":root {\n";

		// Colors
		$colors = $this->load_token( 'colors.json' );
		if ( $colors ) {
			unset( $colors['$schema'] );
			foreach ( $colors as $group => $values ) {
				foreach ( $values as $key => $val ) {
					$css .= "  --dw-color-{$group}-{$key}: {$val};\n";
				}
			}
		}

		// Spacing
		$spacing = $this->load_token( 'spacing.json' );
		if ( $spacing ) {
			if ( isset( $spacing['tokens'] ) ) {
				foreach ( $spacing['tokens'] as $key => $val ) {
					$css .= "  --dw-space-{$key}: {$val};\n";
				}
			}
			if ( isset( $spacing['layout'] ) ) {
				foreach ( $spacing['layout'] as $key => $val ) {
					$css .= "  --dw-layout-{$key}: {$val};\n";
				}
			}
		}

		// Typography
		$typography = $this->load_token( 'typography.json' );
		if ( $typography ) {
			if ( isset( $typography['scale'] ) ) {
				foreach ( $typography['scale'] as $key => $val ) {
					$css .= "  --dw-font-size-{$key}: {$val};\n";
				}
			}
			if ( isset( $typography['line-height'] ) ) {
				foreach ( $typography['line-height'] as $key => $val ) {
					$css .= "  --dw-line-height-{$key}: {$val};\n";
				}
			}
		}

		// Borders
		$borders = $this->load_token( 'borders.json' );
		if ( $borders ) {
			if ( isset( $borders['radius'] ) ) {
				foreach ( $borders['radius'] as $key => $val ) {
					$css .= "  --dw-radius-{$key}: {$val};\n";
				}
			}
			if ( isset( $borders['shadow'] ) ) {
				foreach ( $borders['shadow'] as $key => $val ) {
					$css .= "  --dw-shadow-{$key}: {$val};\n";
				}
			}
		}

		$css .= "}\n";

		set_transient( $cache_key, $css, HOUR_IN_SECONDS );
		return $css;
	}

	private function load_token( $filename ) {
		$filepath = $this->tokens_path . $filename;
		if ( ! file_exists( $filepath ) ) {
			return null;
		}
		return json_decode( file_get_contents( $filepath ), true );
	}

	public static function flush_cache() {
		delete_transient( 'dw_design_tokens_css' );
	}
}
