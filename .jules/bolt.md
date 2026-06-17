## 2024-05-23 - [Prevent Redundant Eager Loading of Current User]
**Learning:** In user-scoped dashboard queries (e.g., `$user->transactions()`), eager loading `user` (`->with('user')`) is wasteful. It triggers an extra query to fetch the user record we already have in memory.
**Action:** Always verify if the relationship being eager loaded is already available in the current context (e.g., `auth()->user()`). If so, remove the `with()` clause.

## 2025-05-20 - [Optimizing Rolling Window Aggregations]
**Learning:** Aggregating user data for charts (e.g., "last 12 months") without a date filter causes full-table scans and merges historical data incorrectly (e.g., Jan 2023 + Jan 2024). Using `whereYear` is also incorrect as it cuts off data at year boundaries.
**Action:** Explicitly calculate the start date (e.g., `now()->subMonths(11)`) and add `where('created_at', '>=', $startDate)` to all aggregation queries. This ensures correctness and leverages `created_at` indexes.

## 2025-05-21 - [Dashboard Aggregation Optimization]
**Learning:** `MONTHNAME()` causes MySQL to return locale-dependent strings (e.g., "January") and likely prevents efficient indexing/grouping compared to integer-based `MONTH()`. Also, repeated calls to `auth()->id()` in closures add unnecessary overhead.
**Action:** Use `MONTH(created_at)` for groupings where possible. Assign `auth()->id()` to a local variable (e.g. `$userId`) before passing it to closures to avoid container resolution overhead.
