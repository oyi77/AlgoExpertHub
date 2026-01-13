## 2024-05-23 - [Prevent Redundant Eager Loading of Current User]
**Learning:** In user-scoped dashboard queries (e.g., `$user->transactions()`), eager loading `user` (`->with('user')`) is wasteful. It triggers an extra query to fetch the user record we already have in memory.
**Action:** Always verify if the relationship being eager loaded is already available in the current context (e.g., `auth()->user()`). If so, remove the `with()` clause.

## 2025-12-27 - [Idempotent Index Migrations]
**Learning:** To add indexes idempotently without `Schema::hasIndex` (which may be unavailable or unreliable depending on driver/version) and avoiding raw SQL, use `try-catch` on `QueryException`.
**Action:** Wrap `Schema::table` in `try-catch`, and strictly re-throw exceptions that do not match "Duplicate key name" or "already exists" error messages.
