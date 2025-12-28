# Design: Refactor SoC

## Architectural Patterns

### 1. Service Layer
- **Responsibility**: Handle all business logic, validation (beyond FormRequests), and orchestration of repositories.
- **Location**: `app/Services/`
- **Interface**: Services should be injected into Controllers.
- **Return Type**: Standardized DTOs or arrays `['status' => bool, 'data' => mixed, 'message' => string]`.

### 2. Repository Layer
- **Responsibility**: Handle all database interactions (Eloquent or Query Builder).
- **Location**: `app/Repositories/`
- **Pattern**: Interface-based repositories (`ReviewRepositoryInterface` -> `ReviewRepository`) are preferred for testability, but concrete classes are acceptable for simpler domains if consistent.
- **Injection**: Injected into Services (not Controllers).

### 3. Frontend Assets
- **CSS**: All styles must be in `public/css` or `resources/css` (compiled via Mix).
- **JS**: All scripts must be in `public/js` or `resources/js` (compiled via Mix).
- **Page-Specific JS**: Create `resources/js/pages/{page_name}.js` and `@push('scripts')` or import via Mix.
- **Livewire**: Use Livewire components for dynamic UI elements to replace complex jQuery logic where feasible.

## Livewire Integration
- **Installation**: Install `livewire/livewire`.
- **Layouts**: Update `layouts/app.blade.php` (and others) to include `@livewireStyles` and `@livewireScripts`.
- **Components**: New interactive features should be built as Livewire components in `app/Http/Livewire`.

## Migration Strategy
- **Iterative Approach**: Refactor one module at a time (e.g., Trading, Users, Admin).
- **Pilot**: Start with `TradingOperationsController` as the pilot for the full Service-Repository transition.
