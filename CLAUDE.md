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

## Hosting: SiteGround
- WP-CLI available via SSH
- Deploy via GitHub Actions + SSH
