# Admin SPA Design Specification

## Architecture: Decoupled SPA
We will use a **Decoupled Architecture** where the React frontend consumes the Laravel API.

### Tech Stack
- **Frontend:** React 18, TypeScript, Vite
- **State:** TanStack Query v5 (Server), Zustand (Client)
- **UI:** Shadcn/UI (Tailwind CSS)
- **Auth:** Laravel Sanctum (Cookie-based Session)
- **Routing:** React Router v6 (Client-side)
- **Backend:** Laravel 10 (Existing) + API Resources

## Routing Strategy: "Strangler Fig"
To allow incremental migration, we will use a catch-all route in Laravel that serves the SPA for specific paths, while keeping legacy Blade routes active for others.

```php
// routes/admin.php
Route::get('/admin/app/{any?}', function () {
    return view('backend.spa_layout');
})->where('any', '.*');
```

## Authentication Flow
1. **Login:** User logs in via existing Blade form (or new React form later).
2. **Session:** Laravel sets a standard `laravel_session` cookie.
3. **API Requests:** React app (Axios) sends requests with `withCredentials: true`.
4. **Validation:** Laravel `Sanctum` middleware validates the session cookie.

## Directory Structure
```text
resources/js/admin-spa/
├── components/       # Shadcn UI components
├── features/         # Feature modules (auth, signals, users)
├── hooks/            # Custom React hooks
├── lib/              # Utilities (axios, query-client)
├── routes/           # React Router configuration
└── main.tsx          # Entry point
```

## Security
- **CSRF:** Axios automatically handles the `X-XSRF-TOKEN` header.
- **Permissions:** Frontend will fetch user permissions (`/api/admin/user`) on bootstrap and restrict UI elements accordingly.
