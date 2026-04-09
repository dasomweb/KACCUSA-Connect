# CLAUDE.md - KACCUSA-Connect Project Rules

## Project Overview
WordPress Code-Driven Automation System for KACCUSA-Connect.
Theme: Kadence Pro | Hosting: SiteGround | CI/CD: GitHub Actions + WP-CLI

## Autonomous Execution Rules
- Complete tasks without confirmation prompts
- Git commit every change immediately
- Commit message format: `[type]: description` (feat, fix, design, ci, refactor, docs)
- Log all actions to TASK_LOG.md after completion

## File Structure Rules
- Theme settings      → /theme-config/settings.json
- Design tokens       → /design-tokens/*.json
- CPT definitions     → /includes/post-types/*.php
- Custom fields       → /includes/fields/*.php
- Block templates     → /block-templates/*.json
- Section templates   → /block-templates/sections/*.json
- Page layouts        → /block-templates/pages/*.json
- Page templates      → /templates/*.php
- WP-CLI scripts      → /scripts/*.php, /scripts/*.js, /scripts/*.sh
- GitHub Actions      → /.github/workflows/*.yml
- Core plugin         → /plugins/dw-core/

## Code Conventions
- PHP: WordPress coding standards, prefix functions with `dw_`
- JSON: 2-space indentation, use design token CSS variables (`var(--dw-color-*)`)
- CPT slugs: prefix with `dw_` (e.g., `dw_portfolio`, `dw_community`)
- Block templates: reference design tokens, never hardcode colors

## Forbidden Actions
- NEVER delete existing plugin logic
- NEVER create DB direct manipulation scripts
- NEVER modify wp-config.php
- NEVER commit .env files to Git
- NEVER hardcode colors/spacing in block templates (use design tokens)
- NEVER set posts_per_page or category filters in code (WP Admin territory)

## Code vs WP Admin Boundary
### Code manages (Git tracked):
- Page template files (.php)
- Section/page layout definitions (JSON)
- Card designs for Query Loop (token-referenced)
- Custom query logic (PHP plugins)
- Design tokens (colors, typography, spacing)

### WP Admin manages (DB, not tracked):
- posts_per_page settings
- Category/taxonomy filters
- Sort order preferences
- Individual page text/images content

## Section Style Rules

### Responsive Values (Required)
All padding, margin, fontSize, gap MUST use [desktop, tablet, mobile] 3-value arrays.

Reference:
- desktop  -> design-tokens/spacing.json section.padding-y.desktop
- tablet   -> desktop x 0.75 (round down)
- mobile   -> desktop x 0.5  (round down)

Example:
"topPadding": [80, 60, 40]   -> CORRECT
"topPadding": 80              -> FORBIDDEN (single value)

### Radius
- Reference design-tokens/spacing.json radius tokens
- Use numbers only without px (Kadence convention)
Example: "borderRadius": 12  -> CORRECT / "borderRadius": "12px"  -> FORBIDDEN

### Colors - Kadence Palette ONLY (Critical)
- NEVER use hex color values in block templates or rendered markup
- ALL colors must reference Kadence theme palette: palette1 through palette9
- Palette mapping is defined in design-tokens/palette-map.json
- Color changes are made ONLY in Kadence Customizer > Colors
- Font family is NEVER set in blocks - theme typography controls all fonts

Palette slots:
- palette1: Brand Primary | palette2: Brand Secondary | palette3: Brand Accent
- palette4: Text Secondary | palette5: Surface BG | palette6: Border
- palette7: White/Background | palette8: Text Primary | palette9: Success

### Backgrounds
- Reference design-tokens/backgrounds.json patterns
- Section backgrounds use palette-map.json section_backgrounds
- When background is dark, ALL inner text must use palette7 (white)
- Page background order follows backgrounds.json page-presets

### New Section Checklist
1. topPadding / bottomPadding -> [desktop, tablet, mobile] array
2. leftPadding / rightPadding -> [0, 0, 20] (mobile horizontal padding required)
3. borderRadius -> spacing.json radius token reference
4. backgroundColor -> backgrounds.json patterns reference
5. If dark background, change ALL inner text color to #FFFFFF

## Hosting: SiteGround
- WP-CLI available via SSH
- Deploy via GitHub Actions + SSH
