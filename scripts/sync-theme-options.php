<?php
/**
 * WP-CLI: Sync Design Tokens to Kadence Theme Options
 *
 * Usage: wp eval-file scripts/sync-theme-options.php --allow-root
 *
 * @package KACCUSA-Connect
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	echo "This script must be run via WP-CLI.\n";
	exit( 1 );
}

$base = dirname( __DIR__ );

// 1. Load design tokens
$colors     = json_decode( file_get_contents( "$base/design-tokens/colors.json" ), true );
$typography = json_decode( file_get_contents( "$base/design-tokens/typography.json" ), true );
$spacing    = json_decode( file_get_contents( "$base/design-tokens/spacing.json" ), true );

if ( ! $colors || ! $typography || ! $spacing ) {
	WP_CLI::error( 'Failed to parse design token JSON files. Please check the files.' );
	exit( 1 );
}

// 2. Update Kadence Pro global palette
$kadence = get_option( 'kadence_theme_settings', [] );

$kadence['palette'] = [
	[ 'color' => $colors['brand']['primary'],     'name' => 'Brand Primary',   'slug' => 'brand-primary' ],
	[ 'color' => $colors['brand']['secondary'],   'name' => 'Brand Secondary', 'slug' => 'brand-secondary' ],
	[ 'color' => $colors['brand']['accent'],      'name' => 'Brand Accent',    'slug' => 'brand-accent' ],
	[ 'color' => $colors['ui']['background'],     'name' => 'Background',      'slug' => 'background' ],
	[ 'color' => $colors['ui']['surface'],        'name' => 'Surface',         'slug' => 'surface' ],
	[ 'color' => $colors['text']['primary'],      'name' => 'Text Primary',    'slug' => 'text-primary' ],
	[ 'color' => $colors['text']['secondary'],    'name' => 'Text Secondary',  'slug' => 'text-secondary' ],
	[ 'color' => $colors['status']['success'],    'name' => 'Success',         'slug' => 'success' ],
	[ 'color' => $colors['status']['error'],      'name' => 'Error',           'slug' => 'error' ],
];

// 3. Update typography
$kadence['base_font_size']   = $typography['scale']['base'];
$kadence['body_font_family'] = [ $typography['fonts']['body']['family'], 'google' ];
$kadence['h1_font_family']   = [ $typography['fonts']['heading']['family'], 'google' ];
$kadence['h1_font_size']     = $typography['scale']['h1'];
$kadence['h2_font_size']     = $typography['scale']['h2'];
$kadence['h3_font_size']     = $typography['scale']['h3'];

// 4. Update spacing
$kadence['content_width'] = $spacing['layout']['content-max-width'];
$kadence['narrow_width']  = $spacing['layout']['narrow-width'];

update_option( 'kadence_theme_settings', $kadence );

// 5. Generate CSS variables file
$css = ":root {\n";
foreach ( $colors as $group => $values ) {
	foreach ( $values as $key => $val ) {
		$css .= "  --dw-color-{$group}-{$key}: {$val};\n";
	}
}
foreach ( $spacing['tokens'] as $key => $val ) {
	$css .= "  --dw-space-{$key}: {$val};\n";
}
$css .= "}\n";

$css_dir = get_template_directory() . '/assets/css';
if ( ! is_dir( $css_dir ) ) {
	wp_mkdir_p( $css_dir );
}
file_put_contents( $css_dir . '/design-tokens.css', $css );

// 6. Flush design token CSS cache
do_action( 'dw_tokens_synced' );

WP_CLI::success( 'Design tokens synced to Kadence theme settings.' );
WP_CLI::success( 'Palette, typography, and spacing updated.' );
WP_CLI::success( 'design-tokens.css generated.' );
WP_CLI::success( 'Token CSS cache flushed.' );
