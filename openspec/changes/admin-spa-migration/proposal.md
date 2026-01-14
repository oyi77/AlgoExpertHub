# Admin Panel SPA Migration Proposal

**Status:** Proposed
**Owner:** Sisyphus (Lead Architect)
**Date:** 2026-01-12

## Summary
Migrate the existing Laravel Blade admin panel to a **Decoupled Single Page Application (SPA)** using React 18, Vite, and Laravel Sanctum. This modernization aims to improve performance, maintainability, and user experience for administrative tasks.

## Motivation
- **Performance:** The current Blade/jQuery implementation suffers from full page reloads and slower interactivity.
- **Maintainability:** Decoupling the frontend allows for better separation of concerns and leverages modern React ecosystem tools (TanStack Query, Shadcn/UI).
- **Future-Proofing:** An API-first admin panel aligns with the platform's broader architectural goals.

## Scope
- **In Scope:**
    - Migration of all admin routes (`/admin/*`) to the React SPA.
    - Implementation of authentication via Laravel Sanctum (Session/Cookie mode).
    - Porting of core modules: Dashboard, Signals, Users, Plans, Trading.
- **Out of Scope:**
    - Public-facing user dashboard (handled separately).
    - Mobile app development.

## Risk Assessment
- **seo:** Irrelevant for admin panel.
- **Complexity:** High initial setup complexity for "Strangler Fig" routing bridge.
- **Data Parity:** ensuring all existing Blade features (especially complex forms) are fully replicated.
