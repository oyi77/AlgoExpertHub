## 2024-05-23 - [Prevent Redundant Eager Loading of Current User]
**Learning:** In user-scoped dashboard queries (e.g., `$user->transactions()`), eager loading `user` (`->with('user')`) is wasteful. It triggers an extra query to fetch the user record we already have in memory.
**Action:** Always verify if the relationship being eager loaded is already available in the current context (e.g., `auth()->user()`). If so, remove the `with()` clause.

## 2024-05-23 - [Date Filtering for Aggregations]
**Learning:** Dashboard aggregations grouping by `MONTHNAME` must explicitly filter by a date range (e.g., `created_at >= $startDate`) to avoid full-table scans and incorrect merging of data from the same month across different years (e.g., June 2023 and June 2024).
**Action:** Always define an explicit `$startDate` and add a `where` clause to aggregation queries.
