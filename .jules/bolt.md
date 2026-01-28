## 2024-05-23 - [Prevent Redundant Eager Loading of Current User]
**Learning:** In user-scoped dashboard queries (e.g., `$user->transactions()`), eager loading `user` (`->with('user')`) is wasteful. It triggers an extra query to fetch the user record we already have in memory.
**Action:** Always verify if the relationship being eager loaded is already available in the current context (e.g., `auth()->user()`). If so, remove the `with()` clause.

## 2025-05-20 - [Optimizing Rolling Window Aggregations]
**Learning:** Aggregating user data for charts (e.g., "last 12 months") without a date filter causes full-table scans and merges historical data incorrectly (e.g., Jan 2023 + Jan 2024). Using `whereYear` is also incorrect as it cuts off data at year boundaries.
**Action:** Explicitly calculate the start date (e.g., `now()->subMonths(11)`) and add `where('created_at', '>=', $startDate)` to all aggregation queries. This ensures correctness and leverages `created_at` indexes.

## 2025-05-22 - [Integer Grouping for Date Aggregation]
**Learning:** Using `MONTHNAME(created_at)` for aggregation is locale-dependent (e.g., returns "January" or "Enero") and slower than integer-based grouping. It causes issues if the DB and App locales differ.
**Action:** Use `MONTH(created_at)` (returns 1-12) for grouping and map to month names in the application layer. Ensure cache keys are updated (e.g., `v2` -> `v3`) when changing the data structure.
