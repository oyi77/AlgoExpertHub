# view-cleanup Specification

## Purpose
TBD - created by archiving change refactor-soc. Update Purpose after archive.
## Requirements
### Requirement: External Script Files
Blade views SHALL NOT contain inline `<script>` tags with executable code. All JavaScript logic must be in external `.js` files loaded via `@push('scripts')` or Laravel Mix.

#### Scenario: No Inline Scripts
- **Given** a blade view file
- **When** the file is rendered
- **Then** it must NOT contain `<script>` tags with inline executable code (module imports or src attributes are allowed if using stack pushing properly, but preferred in Mix).
- **And** all logic should be loaded from external `.js` files.

### Requirement: External Style Files
Blade views SHALL NOT contain inline `<style>` tags or `style="..."` attributes (except for dynamic values). All CSS styling must be in external `.css` files loaded via `@push('styles')` or Laravel Mix.

#### Scenario: No Inline Styles
- **Given** a blade view file
- **When** the file is rendered
- **Then** it must NOT contain `<style>` tags or `style="..."` attributes (except for dynamic values like background-image).
- **And** all styling should be loaded from external `.css` files.

