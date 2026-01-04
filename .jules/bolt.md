## 2024-05-23 - [Prevent Redundant Eager Loading of Current User]
**Learning:** In user-scoped dashboard queries (e.g., `$user->transactions()`), eager loading `user` (`->with('user')`) is wasteful. It triggers an extra query to fetch the user record we already have in memory.
**Action:** Always verify if the relationship being eager loaded is already available in the current context (e.g., `auth()->user()`). If so, remove the `with()` clause.

## 2025-12-31 - [Adding Compound Indexes for Recent History]
**Learning:** When queries filter by a column (e.g., `user_id`) and sort by another (e.g., `created_at` DESC), a single index on the filtering column is insufficient. The database still performs a filesort. A composite index `(user_id, created_at)` allows the database to fetch pre-sorted rows, making operations like `latest()->limit(N)` effectively O(1).
**Action:** Always check if `latest()` or `orderBy` is used on relations. If so, verify if a composite index covering the foreign key and the sort column exists.
