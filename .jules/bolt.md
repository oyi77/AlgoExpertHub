## 2024-05-23 - [Prevent Redundant Eager Loading of Current User]
**Learning:** In user-scoped dashboard queries (e.g., `$user->transactions()`), eager loading `user` (`->with('user')`) is wasteful. It triggers an extra query to fetch the user record we already have in memory.
**Action:** Always verify if the relationship being eager loaded is already available in the current context (e.g., `auth()->user()`). If so, remove the `with()` clause.

## 2024-05-24 - [Missing Indexes on Financial Tables]
**Learning:** Core financial tables `withdraws` and `deposits` were missing indexes on `user_id` and `status`, causing full table scans on every dashboard load. This is a critical oversight in the initial schema design for user-centric applications.
**Action:** Always verify schema definition for foreign keys and status columns used in high-traffic aggregation queries.
