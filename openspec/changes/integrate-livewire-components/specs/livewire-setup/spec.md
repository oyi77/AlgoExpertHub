# Capability: Livewire Setup

## Overview
Configure and publish Livewire for production use, ensuring proper asset management, performance optimization, and integration with existing Laravel infrastructure.

---

## ADDED Requirements

### Requirement: Livewire Configuration Published
**Priority**: High  
**Rationale**: Provides control over Livewire behavior, asset paths, and performance settings.

The Livewire configuration file MUST be published to `config/livewire.php` to allow customization of application-specific settings such as asset URLs, middleware groups, and manifest paths.

#### Scenario: Publish Livewire configuration file
**Given** Livewire is installed via Composer  
**When** the developer runs `php artisan livewire:publish --config`  
**Then** a `config/livewire.php` file is created  
**And** the file contains default configuration values  
**And** the configuration includes asset_url, middleware, and layout settings

#### Scenario: Configure Livewire for production
**Given** the `config/livewire.php` file exists  
**When** the developer sets `'asset_url' => env('LIVEWIRE_ASSET_URL', null)`  
**And** sets `'manifest_path' => public_path('build/manifest.json')`  
**Then** Livewire assets are versioned correctly  
**And** browser caching works as expected

---

### Requirement: Livewire Assets Published
**Priority**: High  
**Rationale**: Ensures Livewire JavaScript and CSS are available and properly versioned.

Livewire's frontend assets (JavaScript and CSS) MUST be published to the public directory to ensure they are accessible to browsers and can be served via CDN if needed.

#### Scenario: Publish Livewire assets to public directory
**Given** Livewire is installed  
**When** the developer runs `php artisan livewire:publish --assets`  
**Then** Livewire JavaScript is published to `public/vendor/livewire/`  
**And** assets include proper version hashes  
**And** assets are accessible via HTTP

#### Scenario: Verify Livewire directives in layouts
**Given** master layouts exist for backend and frontend  
**When** the developer inspects layout files  
**Then** `@livewireStyles` is present in the `<head>` section  
**And** `@livewireScripts` is present before the closing `</body>` tag  
**And** directives are present in all master layouts (backend, frontend, landings)

---

### Requirement: Livewire Service Provider Configured
**Priority**: Medium  
**Rationale**: Allows customization of Livewire behavior and component discovery.

The Livewire service provider MUST be correctly configured to enable automatic component discovery and integration with the application's middleware stack.

#### Scenario: Configure component namespace
**Given** components are organized in `app/Http/Livewire/`  
**When** the developer configures the service provider  
**Then** Livewire automatically discovers components in subdirectories  
**And** components can be referenced using dot notation (e.g., `<livewire:admin.users.users-table />`)

#### Scenario: Configure update endpoint
**Given** the application uses a custom URL structure  
**When** the developer sets `'middleware_group' => 'web'` in config  
**Then** Livewire requests use the web middleware group  
**And** CSRF protection is applied automatically  
**And** session handling works correctly

---

### Requirement: Performance Optimization Configured
**Priority**: Medium  
**Rationale**: Ensures Livewire performs well under production load.

Performance optimizations MUST be enabled in production environments, including asset minification and proper caching headers.

#### Scenario: Enable asset minification
**Given** the application is in production mode  
**When** `APP_ENV=production` is set  
**Then** Livewire assets are minified  
**And** source maps are excluded

#### Scenario: Configure lazy loading
**Given** components can be lazy-loaded  
**When** the developer adds `lazy` attribute to component tag  
**Then** the component loads only when visible in viewport  
**And** a loading placeholder is shown during load

---

## MODIFIED Requirements

### Requirement: CSP Headers Allow Livewire
**Priority**: High  
**Rationale**: Livewire requires specific CSP directives for inline scripts and AJAX requests.  
**Related**: Extends existing `SecurityHeaders` middleware

The Content Security Policy MUST be updated to allow Livewire's inline scripts and AJAX connections to the application backend.

#### Scenario: Update CSP to allow Livewire scripts
**Given** the `SecurityHeaders` middleware exists  
**When** the developer updates the CSP header  
**Then** `script-src` includes `'unsafe-inline'` (already present)  
**And** `script-src` includes `'unsafe-eval'` (already present)  
**And** `connect-src` includes `'self'` (already present)  
**And** Livewire AJAX requests are not blocked

---

## Testing Requirements

### Requirement: Livewire Installation Verified
**Priority**: High

#### Scenario: Verify Livewire is installed
**Given** Composer dependencies are installed  
**When** the developer runs `composer show livewire/livewire`  
**Then** Livewire v3.7.3 or higher is listed  
**And** all dependencies are satisfied

#### Scenario: Verify Livewire assets are accessible
**Given** Livewire assets are published  
**When** the developer visits `/livewire/livewire.js`  
**Then** the JavaScript file is served  
**And** the response includes proper cache headers  
**And** the file size is reasonable (< 100KB minified)

#### Scenario: Verify Livewire directives render correctly
**Given** a page includes `@livewireStyles` and `@livewireScripts`  
**When** the page is rendered  
**Then** the HTML includes `<style>` tags for Livewire styles  
**And** the HTML includes `<script>` tags for Livewire JavaScript  
**And** no console errors appear in the browser
