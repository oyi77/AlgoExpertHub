# Tasks: Implement Bot Landing Page

## Phase 1: Content Strategy

- [ ] **Task 1.1 — Competitive research + copy outline**
  - Review Cornix, 3Commas, Pionex, Coinrule marketing flows
  - Draft hero headline, subcopy, CTA text, features list, testimonials

- [ ] **Task 1.2 — Wireframe sections**
  - Produce low-fi layout covering hero, feature grid, pricing, integrations, FAQs, footer
  - Validate with product stakeholders before coding

## Phase 2: Blade Structure & Components

- [ ] **Task 2.1 — Scaffold landing namespace**
  - Create `resources/views/frontend/landings/bot-sales/layout.blade.php`
  - Define section stacks for hero, features, pricing, testimonials, FAQ, footer

- [ ] **Task 2.2 — Implement hero + CTA**
  - Build animated stats banner + CTA buttons (primary/secondary)
  - Include exchange logo marquee referencing Binance/Bybit/OKX assets

- [ ] **Task 2.3 — Feature + workflow sections**
  - Compose 3-card feature grid (Copy Trading, AI Signals, Risk Controls)
  - Add step-by-step workflow timeline component

- [ ] **Task 2.4 — Pricing + testimonials**
  - Render plan cards linked to existing subscription plans via helper
  - Add testimonial carousel pulling data from `content/landing/testimonials.php`

- [ ] **Task 2.5 — FAQ + footer**
  - FAQ accordion component with microcopy
  - Footer includes compliance text + contact links

## Phase 3: Styling & Assets

- [ ] **Task 3.1 — Add dedicated asset bundle**
  - Store CSS/JS in `public/asset/frontend/landings/bot-sales/`
  - Include glassmorphism palette, neon accents, responsive utilities

- [ ] **Task 3.2 — Media optimization**
  - Export hero illustration + exchange icons (WebP/SVG)
  - Lazy-load heavy imagery and ensure Lighthouse score > 90

- [ ] **Task 3.3 — Interaction polish**
  - Add smooth scroll for CTAs, animated statistics, reveal-on-scroll for sections
  - Ensure animations are reduced when `prefers-reduced-motion` set

## Phase 4: Integration & Config

- [ ] **Task 4.1 — Hook into theme resolution**
  - Update `Helper::landingView()` or equivalent to point to `frontend.landings.bot-sales.index`
  - Guard behind configuration flag (`configurations.landing_page = bot-sales`)

- [ ] **Task 4.2 — Admin toggle**
  - Add landing selector entry in Theme Settings so admins can enable bot landing
  - Include preview thumbnail + copy

## Phase 5: QA & Documentation

- [ ] **Task 5.1 — Cross-device QA**
  - Test on desktop (1440px), tablet (768px), mobile (375px)
  - Verify CTA links, plan modals, contact links

- [ ] **Task 5.2 — Update docs + marketing kit**
  - Document new landing in `docs/user-guides/marketing.md`
  - Share asset kit + copy deck with marketing team


