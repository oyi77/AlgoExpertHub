<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-05-21 -->

# Public

## Purpose

Publicly accessible static assets served directly by the web server. Contains CSS stylesheets for the trading platform's landing pages and UI components.

## Key Files

| File | Description |
|------|-------------|
| `css/tokens.css` | Design token definitions (colors, spacing, typography) |
| `css/trading-landing.css` | Trading platform landing page styles |
| `css/utilities.css` | Utility CSS classes for common patterns |

## Subdirectories

| Directory | Purpose |
|-----------|---------|
| `css/` | Stylesheets for the public-facing website |

## For AI Agents

### Working In This Directory
- This directory contains only top-level public static assets
- The main application's compiled assets live in `main/public/` instead
- The `asset` symlink at project root points to `main/public/asset`
- CSS changes here affect the public landing pages, not the authenticated application UI

### Common Patterns
- Design tokens in `tokens.css` should be the single source of truth for visual values
- Use utility classes from `utilities.css` before writing custom CSS
- Keep landing page styles separate from application styles

## Dependencies

### Internal
- `../AGENTS.md` — Project-wide rules
- `../main/resources/` — Blade views that reference these stylesheets
- `../main/public/` — Application-level compiled assets (separate from this directory)

### External
- CSS3
- Bootstrap 4 (referenced by the main application, not directly here)
