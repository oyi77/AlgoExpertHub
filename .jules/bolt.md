## 2024-05-23 - [Prevent Redundant Eager Loading of Current User]
**Learning:** In user-scoped dashboard queries (e.g., `$user->transactions()`), eager loading `user` (`->with('user')`) is wasteful. It triggers an extra query to fetch the user record we already have in memory.
**Action:** Always verify if the relationship being eager loaded is already available in the current context (e.g., `auth()->user()`). If so, remove the `with()` clause.

## 2024-05-24 - [Date Filtering for Aggregations]
**Learning:** Aggregation queries grouped by month (e.g., `GROUP BY MONTHNAME(created_at)`) must include a strict date range filter (e.g., `created_at >= 12 months ago`). Without it, data from previous years with the same month name gets summed into the current graph, causing incorrect totals and potential full-table scans.
**Action:** Always pair `GROUP BY` time buckets with a `WHERE` clause that limits the scan range to the intended visualization window.
