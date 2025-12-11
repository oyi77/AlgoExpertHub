## Objectives
- Elevate frontend and admin UI/UX while maintaining functional parity.
- Standardize design tokens and reusable components across themes.
- Improve performance (Core Web Vitals) and accessibility (WCAG 2.1 AA).
- Reduce plugin and asset bloat; modernize build pipeline with minimal risk.

## Current State Snapshot
- Laravel 9, Blade views; Bootstrap-based layouts; multiple frontend themes.
- jQuery plugins: Slick, WOW, Paroller, TweenMax, Odometer; icon fonts.
- Build: Laravel Mix; `resources/css/app.css` currently empty; assorted CSS per theme.
- Master layout for materialize theme: `main/resources/views/frontend/materialize/layout/master.blade.php`.
- Admin views: `main/resources/views/backend/**`, Bootstrap patterns.
- Limited tokens: font variables in `:root`; no centralized color/spacing tokens.

## Scope
- Frontend themes: `main/resources/views/frontend/*` and `main/public/asset/frontend/*/css/*.css`.
- Admin UI: `main/resources/views/backend/**` and `main/public/asset/backend/*/css/*.css`.
- Build tools: `main/webpack.mix.js` (and optional Vite migration).

## Workstreams

### UI Component Assessment
- Inventory components/partials in:
  - Frontend: `layout/*.blade.php`, `widgets/*.blade.php` (headers, footers, nav, hero, cards, modals, tables, forms).
  - Admin: `layout/*.blade.php`, pages like dashboard, settings, users, logs.
- Evaluate consistency: spacing scale, type scale, button styles, focus states, error states.
- Plugin usage audit: confirm where Slick/WOW/Paroller/TweenMax/Odometer are actually used; mark candidates for removal or replacement.
- Cross-browser/device QA: Chrome, Firefox, Edge, Safari 15+; Android Chrome; iOS Safari at breakpoints 360, 768, 1024, 1280.

### Design System Implementation
- Design tokens (CSS variables):
  - Create `main/resources/css/tokens.css` with:
    - Colors: `--color-primary-{50..900}`, `--color-secondary`, neutrals, semantic (`--color-success`, `--color-danger`).
    - Typography: `--font-sans`, `--font-mono`, `--font-size-{xs..2xl}`, `--line-{tight..relaxed}`.
    - Spacing: `--space-{1..10}` (4px scale), `--radius-{sm..xl}`, `--shadow-{sm..xl}`.
    - Motion: `--dur-{fast..slow}`, `--ease-standard` respecting `prefers-reduced-motion`.
  - Reference tokens in theme CSS and shared utilities.
- Utilities layer:
  - Add `main/resources/css/utilities.css` for spacing, typography helpers, focus ring, visually-hidden, and responsive helpers.
- Component patterns:
  - Introduce Blade partials under `main/resources/views/partials/ui/*` (buttons, inputs, selects, textareas, alerts, cards).
  - Normalize form markup and validation feedback with `aria-describedby`, `role="alert"`.
- Documentation:
  - Add `docs/design-system.md` and `docs/components/*.md` with usage and dos/don’ts.
  - Optional styleguide page to render components for QA.

### Performance Optimization
- CSS/JS delivery:
  - Consolidate theme CSS; extract critical CSS for above-the-fold; load rest deferred.
  - Defer non-essential scripts; page-level conditional loading for plugins.
- Build pipeline:
  - Enhance Mix: `mix.version()`, PurgeCSS, Autoprefixer, cssnano.
  - Evaluate incremental Vite adoption for faster builds and code-splitting.
- Fonts/images:
  - Preconnect to fonts, `font-display: swap`; limit font weights.
  - Responsive images with `srcset`/`sizes`; `loading="lazy"`; compress large assets (WebP/AVIF where safe).
- Caching/HTTP:
  - Verify cache headers; enable gzip/brotli for static assets in OpenResty.

### User Experience Enhancements
- Navigation:
  - Accessible mobile nav (offcanvas/collapse), keyboard operable, focus trap; clear active states.
- Forms:
  - Clear labels, hint text, error placement; inline validation; larger touch targets; disabled and loading states.
- Micro-interactions:
  - Subtle transitions (150–200ms), skeletons/spinners for async; motion reduced for users opting out.
- Accessibility (WCAG 2.1 AA):
  - Contrast >= 4.5:1; visible focus; semantic landmarks; ARIA for icon-only controls; skip link; keyboard-only flows.

### Technology Evaluation
- Keep Bootstrap 5.3; reduce jQuery reliance; replace plugins with CSS/vanilla where feasible.
- Consider Blade UI Kit for richer, accessible components without SPA complexity.
- Pilot Vite migration alongside Mix; maintain parity; measure gains.

## Detailed Tasks (Per Area)
- Tokens: implement `tokens.css`; wire into master layouts; retrofit key components.
- Utilities: add `utilities.css`; replace ad-hoc spacing/typography and focus styles.
- Components: refactor buttons/forms/alerts/cards on Home, Login/Register, Admin Dashboard first.
- Accessibility: add labels, ARIA, focus ring, landmarks; fix contrast with tokenized palette.
- Performance: PurgeCSS, defer scripts, optimize fonts/images, lazy-load non-critical content.
- Plugin audit: remove or replace unused plugins; reduce icon font footprint if unused sets.
- Build upgrade: introduce Vite (optional), verify hashing and code-splitting; document migration.
- Docs: author design system and component docs; add a styleguide route (read-only).

## Success Metrics
- Lighthouse (mobile, simulated): Performance ≥ 85; Accessibility ≥ 90; Best Practices ≥ 90; SEO ≥ 90.
- Core Web Vitals:
  - LCP ≤ 2.5s (mobile) on Home and Admin Dashboard.
  - CLS ≤ 0.1 on all pages.
  - INP ≤ 200ms for common interactions.
- Asset Budgets:
  - Base CSS ≤ 200KB gzipped per theme; Base JS ≤ 300KB gzipped; reduce total requests by ≥ 25% on initial view.
  - Largest hero image ≤ 200KB; responsive images used for banners.
- Accessibility:
  - 0 critical/serious axe violations; 100% keyboard navigability for core flows; valid landmarks; alt text coverage.
- Responsiveness:
  - Layout integrity across target device matrix; no horizontal scroll at 360px.

## Definition of Done (DoD)
- Tokens & Utilities:
  - `tokens.css` and `utilities.css` exist, imported via build; at least 70% of pages reference tokens for colors/spacing/type.
- Components:
  - Button and form patterns adopted on Home, Login/Register, and Admin Dashboard; alerts/cards standardized; parity retained.
- Accessibility:
  - Axe scan shows 0 critical/serious issues; contrast passes; focus states visible; keyboard-only navigation possible on key flows.
- Performance:
  - Meets asset budgets; achieves target Lighthouse and CWV thresholds; defers non-critical scripts; fonts optimized.
- Plugins:
  - Unused plugins removed; remaining usage documented; no runtime errors; fallbacks exist.
- Build:
  - Mix optimized with versioning/PurgeCSS; optional Vite pilot compiles and serves assets with hashing; roll back path documented.
- Documentation:
  - `docs/design-system.md` and component docs authored; styleguide page available for QA.
- Verification:
  - Test matrix executed for supported browsers/devices; admin flows validated; no functional regressions.

## Verification Plan
- Pre/post Lighthouse and axe reports for Home and Admin Dashboard.
- Manual keyboard-only walkthroughs; screen reader spot checks (NVDA/VoiceOver).
- Responsive checks across breakpoints; RTL if applicable.
- Monitor errors via browser console; verify no 404s for assets.

## Risk & Mitigation
- Theme divergence: central tokens with theme overrides to avoid duplication.
- Plugin removal breakage: gradual per-page replacement with feature flags.
- Build migration risk: pilot Vite; keep Mix as fallback.
- Accessibility regressions: enforce CI checks with axe CLI (optional) and documented checklists.

## Deliverables
- Centralized `tokens.css` and `utilities.css`; updated master layouts and key pages using tokens.
- Reusable Blade UI partials; accessibility improvements applied.
- Optimized asset pipeline and report artifacts (Lighthouse/axe).
- Documentation (design system + components) and optional styleguide.

Approve this plan to proceed with implementation in small, verifiable increments while preserving functionality.