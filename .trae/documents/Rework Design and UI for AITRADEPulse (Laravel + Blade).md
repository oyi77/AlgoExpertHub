## Goals (Finance-Grade Credibility)
- Present a modern, trustworthy interface suitable for a trading/financing app.
- Improve clarity of financial data, reduce visual noise, and align with industry UI patterns.
- Achieve responsive, accessible (WCAG AA), and performant UI with consistent theming.

## Current Stack (Constraint-Aware)
- Laravel + Blade views; multiple frontend/admin themes via static CSS.
- Build via Laravel Mix + PostCSS + PurgeCSS; compiled `public/css/app.css` and `public/js/app.js`.
- UI partials exist; charts/libs are not standardized.

## Visual Direction
- Color: neutral grays + finance blues; semantic tokens for success (green), warning (amber), danger (red), info (blue). Maintain high contrast for data tables and charts.
- Typography: legible, professional sans (system UI fallback or Inter). Tight line-height for data density; clear hierarchy for headings and key figures.
- Spacing: 4px base scale; consistent paddings/margins to reduce clutter.
- Iconography: clean, consistent SVG set; avoid skeuomorphic/dated gradients.

## Design System
- Tokens: CSS variables for color, typography, spacing, radius, shadow, z-index.
- Modes: `:root` for light; `[data-theme="dark"]` overrides for dark.
- Components: buttons, inputs, selects, textareas, checkbox/radio, toggles, tabs, cards, modals, toasts, alerts, badges, tables (sortable, paginated), pagination, navbar/side-nav, breadcrumb, tooltip.
- Documentation: component usage examples in Blade for reuse.

## Finance-Specific UX Patterns
- Dashboard: portfolio KPIs (balance, PnL, drawdown), bot status, recent trades, risk summary, alerts.
- Orders/Positions: dense table with sorting, column visibility, filters, sticky header, risk color-coding.
- Bot Management: status badges (Running, Paused, Error), last heartbeat, performance chart, configuration forms.
- Risk & Compliance: disclaimers, confirmations for risky actions, clear error states with recovery guidance.

## Data Visualization
- Lightweight, finance-suitable charts (e.g., candlestick, line, area). Evaluate TradingView Lightweight Charts or Highcharts; prefer small footprint and Blade-friendly initialization.
- Standardize chart theme (grid, axes, tooltip, colors) using tokens; skeleton/loading states.

## Theme & Layout Consolidation
- Unify frontend/admin layouts using shared header/nav/footer patterns.
- Move theme CSS sources into `resources/css/themes/*`; compile via Mix; load only active theme.
- Runtime theme toggle (`data-theme`) with persisted preference.

## Motion & Feedback
- Subtle micro-interactions (100–200ms, ease-out); focus on clarity: hover/focus states, toast confirmations, non-blocking loaders.
- Avoid flashy animations; prioritize perceived speed and precision.

## Accessibility & Trust
- Keyboard navigation, focus rings, skip links, semantic tables/forms.
- Color contrast meets AA; aria for modals/tooltips; form validation messages.
- Trust markers: security badges, 2FA prompts, audit logs visibility, clear terms/privacy links.

## Performance & Build Improvements
- PurgeCSS: expand content paths to all Blade + JS; safelist dynamic classes.
- Code-split vendor JS; defer non-critical scripts; inline critical CSS for above-the-fold.
- Keep Laravel Mix initially; consider Vite migration later (optional alignment with Horizon).

## Phased Execution
Phase 1 — Audit & Style Guide (1 week)
- Audit pages/components; capture UI pain points and outdated patterns.
- Define tokens and visual direction; produce mini style guide.

Phase 2 — Foundations (1–2 weeks)
- Implement `tokens.css`, base elements, and a curated `utilities.css`.
- Add light/dark mode via `data-theme`; integrate in master layouts.

Phase 3 — Component Library (2 weeks)
- Build finance-grade components (button/input/table/card/modal/navbar/badge/alert/pagination) as Blade components.
- Replace existing partials incrementally; document usage.

Phase 4 — Key Pages Refactor (2–3 weeks)
- Redesign Dashboard (KPIs, status, charts), Orders/Positions, Bot Management.
- Unify admin UI to match design system; improve table density and filters.

Phase 5 — Charts & Performance (1–2 weeks)
- Integrate chosen chart library; theme it via tokens.
- Tune PurgeCSS; split theme CSS; run Lighthouse and fix issues.

Phase 6 — Optional Vite Migration (post-UI)
- Migrate Mix → Vite if desired; validate dev/build parity and assets.

## Quick Wins (Immediate)
- New button/input styles with proper focus/hover; remove outdated gradients/shadows.
- Standard navbar with clear actions and account area; consistent spacing scale.
- Dark mode toggle; improved tables with sticky header and sortable columns.

## Deliverables
- Design tokens and component library with Blade components and CSS.
- Refactored layouts and critical pages; chart integrations.
- A11y/performance reports with before/after metrics; usage docs.

## Risks & Mitigation
- CSS regressions: phased rollout, feature flags, keep old assets until stable.
- PurgeCSS class removal: safelist strategy and avoid runtime-generated class names.
- Charting complexity: start with minimal set (candlestick + line); encapsulate init.

## Decisions Needed
- Approve color/typography direction (finance blues/neutrals, Inter/system UI).
- Choose chart library (TradingView Lightweight vs Highcharts).
- Decide whether to adopt Tailwind or keep curated utilities; and timing for Vite migration.

## Success Metrics
- Lighthouse performance ≥ 90, accessibility ≥ 90, best practices ≥ 95.
- Reduced CSS/JS payload; improved user task completion (e.g., bot start/stop, trade review) speed.
- Positive user feedback on clarity and trust in UI.