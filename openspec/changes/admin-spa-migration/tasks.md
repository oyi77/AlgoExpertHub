# Admin SPA Migration Tasks

## Phase 1: Foundation (Completed)
- [x] Install Vite, React, and TypeScript dependencies.
- [x] Configure `vite.config.ts` and `tsconfig.json`.
- [x] Create SPA directory structure (`resources/js/admin-spa/`).
- [x] Implement Laravel catch-all route (`/admin/app/*`).
- [x] Create Blade entry point (`spa_layout.blade.php`).
- [x] Set up Axios with Sanctum credentials.
- [x] Set up TanStack Query provider.

## Phase 2: Core UI (In Progress)
- [x] Install Tailwind CSS and Shadcn/UI dependencies.
- [x] Configure global styles and theme variables.
- [x] Implement `AdminLayout` (Sidebar, Header).
- [ ] **Port Sidebar Logic**: Connect sidebar to real routing and permissions.
- [ ] **Dashboard Page**: Create the initial "Home" view with basic stats widgets.

## Phase 3: Feature Migration - Signals Module
- [ ] **API Preparation**: Ensure `Api/Admin/SignalController` supports all CRUD actions.
- [ ] **List View**: Create `SignalsTable` with pagination and filtering.
- [ ] **Create/Edit Form**: Implement `SignalForm` using React Hook Form.
- [ ] **Delete Action**: Add confirmation dialog and API hook.

## Phase 4: Feature Migration - Users Module
- [ ] **List View**: Create `UsersTable` with search and status toggles.
- [ ] **User Details**: Create a detailed profile view (Balances, Subscriptions).

## Phase 5: Cutover
- [ ] **Link Update**: Change main admin sidebar to point to SPA routes.
- [ ] **Cleanup**: Remove legacy Blade views for migrated modules.
