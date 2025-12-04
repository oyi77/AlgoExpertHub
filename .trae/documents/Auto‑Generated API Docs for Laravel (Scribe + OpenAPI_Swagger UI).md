## Overview
Implement auto‑generated, interactive API documentation for the Laravel app using Scribe to discover routes, infer request/response details, and produce an OpenAPI spec. Serve a user‑friendly docs UI at `/docs` with real‑time testing and keep docs synchronized with code changes.

## Dependencies
1. Add `knuckleswtf/scribe` via Composer.
2. Publish Scribe config: `php artisan vendor:publish --provider="Knuckles\Scribe\ScribeServiceProvider" --tag=scribe-config`.
3. Optional Swagger UI: use generated OpenAPI (`openapi.yaml|json`) and serve with `swagger-ui-dist` assets under a route like `/swagger`.

## Route Discovery
1. Configure Scribe to scan all API routes:
   - Match prefixes: `api/*` and addon API routes (`addons/*/routes/api.php`).
   - Include route domains `*` and middleware group `api` (e.g., `throttle:api`, `auth:sanctum`).
2. Exclude non‑API web pages (`routes/web.php`) unless explicitly desired.

## Schemas & Parameters
1. Let Scribe infer request bodies and parameters from:
   - FormRequest classes (e.g., `app/Http/Requests/*`).
   - Inline `$request->validate([...])` arrays and `Validator::make(..., [...])` calls.
2. Capture path/query params directly from route definitions.
3. For dynamic/indirect rules (theme‑dependent or computed), add minimal docblocks only where needed (e.g., `@bodyParam field type description`), keeping manual annotation to a minimum.

## Examples & Errors
1. Enable Scribe `response_calls` to automatically call safe endpoints (primarily `GET`) in a local/dev environment to capture example responses.
2. For side‑effect endpoints, provide lightweight `@response` examples only where necessary.
3. Configure global error responses (validation 422, auth 401/403, not found 404) so the docs include common error codes and messages.

## Authentication
1. Configure docs to use `Authorization: Bearer {token}` for `auth:sanctum` endpoints.
2. Add a short docs intro on how to obtain a token and set it in the UI.
3. Ensure `Try It Out` respects headers and cookies where applicable.

## UI & Accessibility
1. Generate static docs to `public/docs` and ensure `/docs` serves `public/docs/index.html` (add a tiny web redirect route if needed).
2. Enable Scribe’s interactive "Try It Out" UI for real‑time testing.
3. Optionally expose `/openapi.json` or `/openapi.yaml` from `public/docs` and mount Swagger UI at `/swagger` using the generated spec.

## Synchronization
1. Add Composer scripts:
   - `docs:generate`: `php artisan scribe:generate`
   - `docs:openapi`: `php artisan scribe:generate --openapi 3.0`
2. Dev sync: add a lightweight watcher to rerun `docs:generate` on changes in `routes/**`, `app/Http/Controllers/**`, `app/Http/Requests/**`.
3. CI sync: include `docs:openapi` in pipeline to keep OpenAPI and UI up‑to‑date.

## Categorization
1. Auto‑group endpoints by route prefix (e.g., `webhook`, `providers`, `signals`, `admin`, `addons/*`).
2. Optionally add single‑line `@group` docblocks for controllers to refine grouping where helpful.

## Minimal Annotation Policy
1. Rely on automatic inference for most endpoints.
2. Use docblocks sparingly:
   - Only where rules are dynamic or indirect.
   - To add human‑friendly descriptions, examples, or special headers.
3. Avoid manual schema maintenance; prefer FormRequest rules and validation arrays as the source of truth.

## Verification
1. Generate docs locally, open `/docs`, validate that endpoints from `routes/api.php` and addons appear with methods, params, and schemas.
2. Test "Try It Out" against a dev server using a Sanctum token.
3. Confirm OpenAPI generation and render via Swagger UI at `/swagger` if enabled.

## Deliverables
- Working `/docs` interactive documentation.
- Generated `openapi.json|yaml` available under `public/docs`.
- Optional `/swagger` route using Swagger UI.
- Composer scripts and dev/CI sync setup.

## Notes for This Codebase
- API routes are defined in `routes/api.php` and addon `routes/api.php` files; controllers live under `app/Http/Controllers/**` and addons. Validation rules are largely inferable from FormRequests and inline arrays, minimizing manual annotations.
