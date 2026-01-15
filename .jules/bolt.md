## 2024-05-23 - [Prevent Redundant Eager Loading of Current User]
**Learning:** In user-scoped dashboard queries (e.g., `$user->transactions()`), eager loading `user` (`->with('user')`) is wasteful. It triggers an extra query to fetch the user record we already have in memory.
**Action:** Always verify if the relationship being eager loaded is already available in the current context (e.g., `auth()->user()`). If so, remove the `with()` clause.

## 2024-05-24 - [Dashboard Aggregation Query Optimization]
**Learning:** Dashboard aggregation queries relying solely on `MONTHNAME` grouping without a date filter scan the entire table history and incorrectly merge data from different years. This causes both performance bottlenecks (full table scans) and functional bugs (incorrect graph data).
**Action:** Always constrain aggregation queries with a date range filter (e.g., `where('created_at', '>=', $startDate)`) when grouping by `MONTHNAME` or similar periodic functions, even if the graph only displays the last 12 months.
