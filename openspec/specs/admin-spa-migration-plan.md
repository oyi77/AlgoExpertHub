# Master Migration Plan: Blade to SPA (React)

**Target Architecture:** Decoupled Single Page Application (SPA)
**Tech Stack:** Laravel 10 (API) + React 18 (Vite) + TanStack Query + Shadcn/UI
**Strategy:** Strangler Fig Pattern (Progressive Migration)

---

## 1. Architecture Decision Matrix

We will proceed with a **Decoupled SPA Architecture** served within the Laravel ecosystem (Monorepo-style) initially, moving towards full separation.

| Feature | Inertia.js (Hybrid) | **Decoupled React (Selected)** | Justification |
| :--- | :--- | :--- | :--- |
| **Routing** | Server-side (Laravel) | **Client-side (React Router)** | Allows for instant transitions and complex UI states without server roundtrips. |
| **API** | Tightly coupled (Props) | **REST/JSON API** | Forces a clean API-First backend, future-proofing for mobile apps or external integrations. |
| **State** | Shared Props | **TanStack Query + Zustand** | Better handling of server state, caching, and optimistic updates for a trading platform. |
| **DevExp** | PHP + JS mixed | **Pure JS/TS Frontend** | Clear separation of concerns. Frontend devs don't need to know PHP. |

---

## 2. Phase 1: Frontend Foundation (Vite & React Setup)

The current system uses `Laravel Mix`. The first step is to modernize the build toolchain.

### 2.1 Install Vite & React
We will coexist `mix` (for legacy assets) and `vite` (for new React app) initially, or migrate completely if feasible.

**Tasks:**
- [ ] Remove `laravel-mix` and install `vite` + `laravel-vite-plugin`.
- [ ] Install React dependencies: `react`, `react-dom`, `react-router-dom`.
- [ ] Install Core UI Libraries: `lucide-react`, `clsx`, `tailwind-merge` (for Shadcn).
- [ ] Initialize Tailwind CSS (Required for Shadcn).

### 2.2 Directory Structure
Create a dedicated entry point for the SPA to avoid polluting the existing Blade logic.

```text
resources/
├── js/
│   ├── app.js (Legacy)
│   └── admin/               <-- NEW SPA ROOT
│       ├── main.tsx         <-- Entry Point
│       ├── App.tsx          <-- Router & Layout
│       ├── components/      <-- Shared UI (Shadcn)
│       ├── features/        <-- Feature Modules (Signals, Users)
│       ├── lib/             <-- Axios, Utils
│       └── hooks/           <-- Custom Hooks
```

### 2.3 The "Bridge" Route
We need a catch-all route in Laravel to serve the React app for specific paths.

*File: `routes/web.php`*
```php
// New SPA Routes (Any route starting with /admin/app/...)
Route::get('/admin/app/{any?}', function () {
    return view('admin-spa'); // A simple Blade file loading the Vite React entry
})->where('any', '.*')->middleware(['web', 'auth:admin']);
```

---

## 3. Phase 2: Backend Preparation (API-First)

**Status:** Excellent. The `Api/Admin` namespace already exists and uses Service classes.
**Goal:** Ensure 100% parity between `Backend/` controllers and `Api/Admin/` controllers.

### 3.1 Authentication Upgrade
Currently, `api/admin` routes use `web` middleware (Session). To support a true SPA experience:
- [ ] Install/Configure **Laravel Sanctum**.
- [ ] Update `config/auth.php` to support SPA authentication (cookie-based).
- [ ] Ensure `Api/Admin` routes accept `auth:sanctum` (or keep `web` + `auth:admin` if serving from same domain, but ensure CSRF handling is robust in Axios).

### 3.2 Standardization (The API Contract)
Ensure all `Api/Admin` controllers return a standardized structure.
- [ ] Implement `ApiResponseTrait` for consistent `{ success: true, data: ..., message: ... }` responses.
- [ ] **Audit:** Compare `Backend\SignalController` inputs/outputs with `Api\Admin\SignalController`.
- [ ] **Missing Endpoints:** Identify Blade actions that have no API equivalent (e.g., "Export to CSV", "Bulk Actions").

---

## 4. Phase 3: The "Strangler" Migration Strategy

We will not rewrite everything at once. We will migrate **feature by feature**.

### 4.1 The Hybrid Sidebar
Modify the legacy Blade Sidebar (`resources/views/backend/layout/sidebar.blade.php`) to link to new React routes for migrated features.

**Example Transition:**
- **Old:** `<a href="{{ route('admin.signals.index') }}">Signals</a>` (Loads Blade Page)
- **New:** `<a href="/admin/app/signals">Signals</a>` (Loads React App)

### 4.2 Migration Order
1.  **Dashboard (Read-only)**: Low risk, good for testing Charts/Stats API.
2.  **Signals (Core Feature)**: High value. Use `Api\Admin\SignalController`.
    -   Create `features/signals/SignalTable.tsx`.
    -   Create `features/signals/SignalForm.tsx`.
3.  **Users Management**: Complex (Modals, KYC, Balances).
4.  **Settings/Config**: Usually simple forms.

---

## 5. Phase 4: State Management & UI Components

### 5.1 Tech Stack Implementation
-   **Data Fetching:** TanStack Query (React Query v5).
    -   *Why?* Auto-caching, background refetching (crucial for live trading data), and deduping requests.
-   **Forms:** React Hook Form + Zod.
    -   *Why?* Performance (uncontrolled inputs) and type-safe validation (Zod schema matching Laravel FormRequests).
-   **UI Library:** Shadcn/UI.
    -   *Why?* Copy-paste capability allows total customization. Professional look.

### 5.2 Component Mapping
| Blade Component | React Equivalent (Shadcn) |
| :--- | :--- |
| `backend.layout.alert` | `components/ui/toast` |
| `backend.layout.sidebar` | `components/layout/Sidebar` |
| `backend.modal` | `components/ui/dialog` |
| `DataTable` (jQuery) | `TanStack Table` (Headless) + `ui/table` |

---

## 6. Phase 5: Testing & Cutover

### 6.1 Definition of Done (Per Feature)
- [ ] React UI matches or exceeds Blade design.
- [ ] All CRUD operations function via API.
- [ ] Form validation errors display correctly (from 422 API response).
- [ ] Loading states (Skeleton loaders) implemented.
- [ ] Mobile responsiveness verified.

### 6.2 The Switch
Once a feature is "Done":
1.  Update the Laravel Route to point to the SPA catch-all (or just update the Sidebar link).
2.  Remove the old Blade Controller methods (or mark `@deprecated`).
3.  Delete the old Blade views for that feature.

---

## 7. Immediate Next Steps (Execution)

1.  **Install Vite & React** in the project root.
2.  **Create the `admin-spa.blade.php`** entry view.
3.  **Set up the React Router** shell.
4.  **Migrate the "Signals" module** as the Pilot.
