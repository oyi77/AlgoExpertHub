## 2024-05-23 - [Prevent Redundant Eager Loading of Current User]
**Learning:** In user-scoped dashboard queries (e.g., `$user->transactions()`), eager loading `user` (`->with('user')`) is wasteful. It triggers an extra query to fetch the user record we already have in memory.
**Action:** Always verify if the relationship being eager loaded is already available in the current context (e.g., `auth()->user()`). If so, remove the `with()` clause.

## 2025-05-20 - [Optimizing Rolling Window Aggregations]
**Learning:** Aggregating user data for charts (e.g., "last 12 months") without a date filter causes full-table scans and merges historical data incorrectly (e.g., Jan 2023 + Jan 2024). Using `whereYear` is also incorrect as it cuts off data at year boundaries.
**Action:** Explicitly calculate the start date (e.g., `now()->subMonths(11)`) and add `where('created_at', '>=', $startDate)` to all aggregation queries. This ensures correctness and leverages `created_at` indexes.

## 2025-05-21 - [Efficient Date Grouping and Cache Versioning]
**Learning:** Grouping by `MONTHNAME(created_at)` is slower than `MONTH(created_at)` (string vs int) and introduces locale dependencies. Also, when changing data structure of cached items (e.g. changing map keys from string to int), cache keys must be versioned (e.g. `:v3`) to avoid runtime errors with stale data.
**Action:** Use `MONTH()` for grouping and integer keys for maps. Increment cache key version when modifying cached data structures.
