## 2024-05-23 - [Prevent Redundant Eager Loading of Current User]
**Learning:** In user-scoped dashboard queries (e.g., `$user->transactions()`), eager loading `user` (`->with('user')`) is wasteful. It triggers an extra query to fetch the user record we already have in memory.
**Action:** Always verify if the relationship being eager loaded is already available in the current context (e.g., `auth()->user()`). If so, remove the `with()` clause.

## 2025-05-20 - [Optimizing Rolling Window Aggregations]
**Learning:** Aggregating user data for charts (e.g., "last 12 months") without a date filter causes full-table scans and merges historical data incorrectly (e.g., Jan 2023 + Jan 2024). Using `whereYear` is also incorrect as it cuts off data at year boundaries.
**Action:** Explicitly calculate the start date (e.g., `now()->subMonths(11)`) and add `where('created_at', '>=', $startDate)` to all aggregation queries. This ensures correctness and leverages `created_at` indexes.

## 2025-10-25 - [Robust Dashboard Aggregation]
**Learning:** Using MySQL's `MONTHNAME()` returns localized strings which prevents efficient index usage and complicates data mapping in PHP. It also breaks if the DB locale differs from the app locale.
**Action:** Use `MONTH()` (returns 1-12) for aggregations. It is faster, locale-neutral, and allows using integer keys in PHP arrays, which is slightly more efficient. Ensure loops use integer keys to match.
