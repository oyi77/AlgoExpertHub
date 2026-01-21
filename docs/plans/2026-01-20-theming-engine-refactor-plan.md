# Theming Engine Refactor Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Consolidate theme resolution and theme management paths while preserving existing behavior and leaving beta UI untouched.

**Architecture:** Keep the current theme layout under `resources/views/frontend` and `public/asset/frontend`. Standardize all view/asset resolution through `ThemeManager` + `themeView` (inheritance-aware). In Blade, use `Config::themeView()` as the canonical API; in PHP, use `Helper::themeView()`. Unify theme management controllers to a single service-backed flow. Avoid any changes to `user.beta.*` routes or beta UI pages.

**Tech Stack:** Laravel 10, Blade templates, PHP 8.1+, Eloquent, existing `ThemeManager` service.

---

## Constraints & Non-Goals
- Do **not** modify beta UI routes or views (anything under `user.beta.*`).
- Do **not** modify `main/resources/views/frontend/trading-v1/**`.
- Do **not** change theme directory layout or move assets.
- Do **not** introduce a new theming package or change the active theme storage (`configurations.theme`).
- Assets already use inheritance-aware helpers; no asset path rewrite unless explicitly required.

## Prerequisite (Spec-Driven Development)
- Create OpenSpec change proposal before implementation.
- Run `openspec validate <change-id> --strict` before starting tasks.
- Pause for approval after validation before touching code.

---

### Task 0: Inventory + guardrails

**Files:**
- Modify: `docs/plans/2026-01-20-theming-engine-refactor-plan.md` (append results)

**Step 1: Inventory Blade concatenations (frontend + addons + vendor + errors)**
Run:
- `rg -n "Helper::theme\(" main/resources/views -g "*.blade.php"`
- `rg -n "Config::theme\(" main/resources/views -g "*.blade.php"`
- `rg -n "Config::theme\(" main/addons -g "*.blade.php"`
Expected: List of Blade files to update (append exact file list to this plan).

**Step 2: Inventory PHP concatenations**
Run: `rg -n "Helper::theme\(" main/app -g "*.php"`
Expected: List of PHP files to update (append exact file list to this plan).

**Step 3: Inventory theme routes**
Run: `docker exec 1Panel-php8-mrTy php artisan route:list | rg -i theme`
Expected: List of theme-related endpoints; pick canonical endpoints and append to this plan.

**Step 4: Scope decision**
- Choose scope for Blade updates:
  - Option A: Core frontend only (`main/resources/views/frontend/**` excluding `trading-v1`)
  - Option B: Core + addon + vendor/error views
- Record decision in this plan before implementation.

**Step 5: Beta UI guardrails**
- Treat the following as no-touch zones:
  - `main/resources/js/**`
  - `main/resources/views/frontend/trading-v1/**`
  - `main/app/Http/Middleware/HandleInertiaRequests.php`
  - Any routes file defining `user.beta.*`
- Guard check before each commit:
  - `git diff --name-only | rg '^main/resources/js/'` → must be empty.

**Step 6: Task tracking**
- Create bd tasks from this plan before implementation.

---

### Task 1: Create OpenSpec proposal for theming refactor

**Files:**
- Create: `openspec/changes/<change-id>/proposal.md`
- Create: `openspec/changes/<change-id>/design.md`
- Create: `openspec/changes/<change-id>/tasks.md`

**Step 1: Draft proposal**
- Define scope: theme resolution unification + API/controller consolidation.
- Explicitly exclude `user.beta.*` routes, `trading-v1` views, and beta UI assets.

**Step 2: Draft design**
- Document current resolution path vs. new unified path.
- Call out inheritance-safe resolution via `Helper::themeView` + `Config::themeView`.

**Step 3: Draft tasks**
- Break implementation into controller/API updates and Blade updates.

**Step 4: Validate**
Run: `openspec validate <change-id> --strict`
Expected: PASS

**Step 5: Approval gate**
- Stop and request approval to proceed with implementation.

---

### Task 2: Consolidate theme management controllers

**Files:**
- Modify: `main/app/Http/Controllers/Backend/ConfigurationController.php`
- Modify: `main/app/Http/Controllers/Backend/Traits/HandlesThemeManagement.php`
- Modify: `main/routes/admin/system.php`
- Modify: `main/app/Services/ThemeManager.php`

**Step 1: Confirm real surface area**
- Use `route:list` inventory to confirm whether `ThemeManagementController` or `ThemeApiController` are registered.
- If not registered, leave them untouched and document as legacy.

**Step 2: Lock canonical endpoints**
- Use Task 0 route inventory and decide the canonical endpoints + response shape.
- Define deprecation strategy for non-canonical endpoints (proxy vs. removal).

**Step 3: Write failing test**
- Add a feature test that asserts the canonical endpoint returns the expected response shape and theme list source.

**Step 4: Run the test to fail**
Run: `docker exec 1Panel-php8-mrTy php artisan test --filter ThemeApi` (or targeted test)
Expected: FAIL (duplicate endpoints or inconsistent results).

**Step 5: Implement unified flow**
- Route all theme listing/activation to `ThemeManager`.
- Remove/redirect duplicate endpoints to the canonical controller.

**Step 6: Run the test to pass**
Run: `docker exec 1Panel-php8-mrTy php artisan test --filter ThemeApi`
Expected: PASS

**Step 7: Commit**
```bash
git add main/app/Http/Controllers/Backend/ConfigurationController.php \
  main/app/Http/Controllers/Backend/Traits/HandlesThemeManagement.php \
  main/routes/admin/system.php \
  main/app/Services/ThemeManager.php

git commit -m "refactor: unify theme management flow"
```

---

### Task 3: Standardize view resolution usage in PHP code

**Files:**
- Modify: `main/app/Services/PaymentService.php`
- Modify: Any PHP files listed in Task 0 inventory
- Modify: `main/tests/Unit/HelperThemeInheritanceTest.php`

**Step 1: Extend existing theme inheritance tests**
- Add a focused test to `HelperThemeInheritanceTest` that covers view resolution when call sites use `themeView` vs concatenated `theme` strings.

**Step 2: Run the test to fail**
Run: `docker exec 1Panel-php8-mrTy php artisan test --filter HelperThemeInheritanceTest`
Expected: FAIL (before code update).

**Step 3: Implement minimal change**
- Replace `Helper::theme()` concatenations with `Helper::themeView()` in PHP call sites.
- Ensure output view names remain the same when files exist in the active theme.

**Step 4: Run the test to pass**
Run: `docker exec 1Panel-php8-mrTy php artisan test --filter HelperThemeInheritanceTest`
Expected: PASS

**Step 5: Commit**
```bash
git add main/app/Services/PaymentService.php main/tests/Unit/HelperThemeInheritanceTest.php

git commit -m "refactor: use themeView for inheritance"
```

---

### Task 4: Standardize Blade template resolution

**Files:**
- Modify: Blade files listed in Task 0 inventory (respect no-touch zones)
- Modify: `main/tests/Unit/HelperThemeInheritanceTest.php`

**Step 1: Extend existing tests if needed**
- Only add a Blade integration assertion if Task 3 tests do not cover template inheritance usage.

**Step 2: Run the test to fail**
Run: `docker exec 1Panel-php8-mrTy php artisan test --filter HelperThemeInheritanceTest`
Expected: FAIL (before template updates).

**Step 3: Implement minimal change**
- Replace `@extends(Config::theme().'layout.master')` with `@extends(Config::themeView('layout.master'))`.
- Replace similar `@include` usage.
- Do not change beta UI routes or views.

**Step 4: Run the test to pass**
Run: `docker exec 1Panel-php8-mrTy php artisan test --filter HelperThemeInheritanceTest`
Expected: PASS

**Step 5: Commit**
```bash
git add main/resources/views

git commit -m "refactor: use themeView in Blade templates"
```

---

### Task 5: Documentation updates

**Files:**
- Modify: `docs/architecture/overview.md`
- Modify: `docs/development/theme-development.md`

**Step 1: Document the unified theme resolution path**
- Explain that controllers/services/templates should call `themeView`.

**Step 2: Document the management API path**
- Describe the canonical theme management endpoints.

**Step 3: Commit**
```bash
git add docs/architecture/overview.md docs/development/theme-development.md

git commit -m "docs: clarify theme resolution and management"
```

---

## Verification Checklist (final)
- Route check: `docker exec 1Panel-php8-mrTy php artisan route:list | rg -i theme`
- View cache: `docker exec 1Panel-php8-mrTy php artisan view:clear`
- Config cache: `docker exec 1Panel-php8-mrTy php artisan config:clear`
- Tests: `docker exec 1Panel-php8-mrTy php artisan test --filter HelperThemeInheritanceTest`
- Manual smoke: switch theme in admin and verify view inheritance resolves without missing templates.

---

## Rollback Plan
- Revert commits in reverse order.
- Restore theme management routes to previous structure.
- Clear theme cache if needed.
